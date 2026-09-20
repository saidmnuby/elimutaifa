<?php
declare(strict_types=1);
require_once __DIR__.'/form_one.php';

require_once __DIR__.'/form_five_cycles.php';
function f5_url(string $href,string $base): ?string {
    if(preg_match('#^https://#i',$href)) $url=$href;
    elseif(str_starts_with($href,'/')) $url='https://selform.tamisemi.go.tz'.$href;
    else {
        $parts=[];
        foreach(explode('/',dirname((string)parse_url($base,PHP_URL_PATH)).'/'.$href) as $part) {
            if($part==='..') array_pop($parts); elseif($part!=='' && $part!=='.') $parts[]=$part;
        }
        $url='https://selform.tamisemi.go.tz/'.implode('/',$parts);
    }
    $p=parse_url($url);
    if(!$p || ($p['scheme']??'')!=='https' || ($p['host']??'')!=='selform.tamisemi.go.tz'
        || isset($p['user']) || isset($p['pass']) || isset($p['port']) || isset($p['query']) || isset($p['fragment'])
        || !preg_match('#^/[Cc]ontent/selection-and-allocation/20[0-9]{2}/[a-z0-9-]+/(?:[a-zA-Z0-9% _-]+/)*[a-zA-Z0-9% _.-]+$#D',$p['path']??'')
        || str_contains(rawurldecode($p['path']??''),'..')) return null;
    return str_replace(' ','%20',$url);
}
function f5_fetch(string $url,?callable $fetch=null): array {
    $response=($fetch??'grf_fetch_result')($url);
    if(($response['status']??0)===429) throw new RuntimeException('RATE_LIMIT');
    if(($response['status']??0)!==200 || !is_string($response['html']??null) || $response['html']==='') throw new RuntimeException('SOURCE_UNAVAILABLE');
    if(!$fetch) f5_track_cache_url($url);
    return $response;
}
function f5_links(string $html,string $base,bool $schools=false): array {
    $entries=[];$parent=dirname((string)parse_url($base,PHP_URL_PATH));
    foreach(fo_dom($html)->getElementsByTagName('a') as $a) {
        $url=f5_url($a->getAttribute('href'),$base);$name=fo_text($a->textContent);
        if(!$url || !$name) continue;
        $path=(string)parse_url($url,PHP_URL_PATH);
        if(strtolower(basename($path))!=='index.html' || dirname(dirname($path))!==$parent) continue;
        $id=rawurldecode(basename(dirname($path)));
        if(strtolower($id)==='placements') continue;
        if($schools) { if(!preg_match('/^[SP][0-9]{4}$/iD',$id)) continue;$id=strtoupper($id); }
        elseif(preg_match('/^[SP][0-9]/i',$id)) continue;
        $entries[$id]=['id'=>$id,'name'=>$name,'url'=>$url];
    }
    if(!$entries) throw new RuntimeException('SOURCE_FORMAT');
    return array_values($entries);
}
function f5_entry(array $entries,string $id): array {
    foreach($entries as $entry) if($entry['id']===$id) return $entry;
    throw new InvalidArgumentException('INVALID_SELECTION');
}
function f5_records(string $html,string $base,string $school,int $examYear): array {
    $rows=[];$xpath=new DOMXPath(fo_dom($html));
    foreach($xpath->query('//table[@id="students-table"]/tbody/tr') as $tr) {
        $cells=$xpath->query('./td',$tr); if($cells->length!==7) continue;
        $index=strtoupper(fo_text($cells->item(1)->textContent));
        if(!preg_match('/^'.preg_quote($school,'/').'\\.[0-9]{4}\\.'.$examYear.'$/D',$index)) continue;
        $destination=$cells->item(2);$joining=null;
        foreach($destination->getElementsByTagName('a') as $a) {
            $link=f5_url($a->getAttribute('href'),$base);
            if($link && preg_match('#/[Jj][Ii][Ss]/[^/]+\\.pdf$#iD',(string)parse_url($link,PHP_URL_PATH))) $joining=$link;
        }
        $rows[]=['candidate'=>$index,'destination'=>fo_text($destination->textContent),'course'=>fo_text($cells->item(3)->textContent),'type'=>fo_text($cells->item(4)->textContent),'council'=>fo_text($cells->item(5)->textContent),'info'=>fo_text($cells->item(6)->textContent),'joining_url'=>$joining];
    }
    if(!$rows) throw new RuntimeException('SOURCE_FORMAT');
    return $rows;
}
