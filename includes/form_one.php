<?php
declare(strict_types=1);
require_once __DIR__.'/result_request.php';
require_once __DIR__.'/district_directory.php';
require_once __DIR__.'/validation.php';
require_once __DIR__.'/form_one_cycles.php';

function fo_dom(string $html): DOMDocument {
    $previous=libxml_use_internal_errors(true);
    try { $dom=new DOMDocument(); if (!$dom->loadHTML('<?xml encoding="UTF-8">'.$html, LIBXML_NONET)) throw new RuntimeException('SOURCE_FORMAT'); return $dom; }
    finally { libxml_clear_errors(); libxml_use_internal_errors($previous); }
}
function fo_text(string $text): string { return trim(preg_replace('/\s+/u',' ',$text)??''); }
function fo_url(string $href,string $base): ?string {
    if (preg_match('#^https://#i',$href)) $url=$href;
    elseif (str_starts_with($href,'/')) $url='https://selection.tamisemi.go.tz'.$href;
    else {
        $path=dirname((string)parse_url($base,PHP_URL_PATH)).'/'.$href;
        $parts=[]; foreach (explode('/',$path) as $part) { if ($part==='..') array_pop($parts); elseif ($part!=='' && $part!=='.') $parts[]=$part; }
        $url='https://selection.tamisemi.go.tz/'.implode('/',$parts);
    }
    $p=parse_url($url);
    if (!$p || ($p['scheme']??'')!=='https' || ($p['host']??'')!=='selection.tamisemi.go.tz'
        || isset($p['user']) || isset($p['pass']) || isset($p['port']) || isset($p['query']) || isset($p['fragment'])
        || !preg_match('#^/allocations/20[0-9]{2}/(?:[a-z0-9-]+/|jis/)#',$p['path']??'')
        || str_contains(rawurldecode($p['path']??''),'..')) return null;
    return str_replace(' ','%20',$url);
}
function fo_fetch(string $url,?callable $fetch=null): array {
    $response=($fetch??'grf_fetch_result')($url);
    if (($response['status']??0)===429) throw new RuntimeException('RATE_LIMIT');
    if (($response['status']??0)!==200 || !is_string($response['html']??null) || $response['html']==='') throw new RuntimeException('SOURCE_UNAVAILABLE');
    if (!$fetch) fo_track_cache_url($url);
    return $response;
}
function fo_cache_manifest(string $source): string {
    return sys_get_temp_dir().DIRECTORY_SEPARATOR.'grf-result-cache'.DIRECTORY_SEPARATOR.'fo-manifest-'.hash('sha256',strtolower(dirname($source))).'.json';
}
function fo_track_cache_url(string $url): void {
    if(!preg_match('#^(https://selection\.tamisemi\.go\.tz/allocations/20[0-9]{2}/[a-z0-9-]+/)#',$url,$m)) return;
    $file=fo_cache_manifest($m[1].'index.html');
    $handle=@fopen($file,'c+'); if(!$handle) return;
    try {
        if(!flock($handle,LOCK_EX)) return;
        $urls=json_decode(stream_get_contents($handle)?:'[]',true);
        $urls=is_array($urls)?$urls:[];
        if(!in_array($url,$urls,true)) { $urls[]=$url; ftruncate($handle,0);rewind($handle);fwrite($handle,json_encode($urls));fflush($handle); }
    } finally { flock($handle,LOCK_UN);fclose($handle); }
}
function fo_clear_cycle_cache(string $source): int {
    if(!fo_source_valid($source)) throw new InvalidArgumentException('INVALID_SELECTION');
    $file=fo_cache_manifest($source); $urls=is_file($file)?json_decode(file_get_contents($file)?:'[]',true):[];
    $urls=is_array($urls)?$urls:[]; $urls[]=$source; $count=0;
    foreach(array_unique($urls) as $url) {
        if(!is_string($url) || !str_starts_with($url,dirname($source).'/') || !fo_url($url,$source)) continue;
        $target=sys_get_temp_dir().DIRECTORY_SEPARATOR.'grf-result-cache'.DIRECTORY_SEPARATOR.'result-'.hash('sha256',$url).'.html';
        if(is_file($target) && unlink($target)) $count++;
    }
    return $count;
}
function fo_verify_cycle(array $cycle,?callable $fetch=null): string {
    $source=$cycle['source_url']; if(!fo_source_valid($source)) throw new RuntimeException('INVALID_SOURCE');
    if(!$fetch) fo_clear_cycle_cache($source);
    $r=fo_fetch($source,$fetch);
    if(!preg_match('/KIDATO CHA KWANZA[^0-9]{0,30}'.preg_quote((string)$cycle['intake_year'],'/').'/iu',fo_text(fo_dom($r['html'])->textContent))) throw new RuntimeException('INTAKE_YEAR_MISMATCH');
    $regions=fo_links($r['html'],$source);
    $r=fo_fetch($regions[0]['url'],$fetch);$councils=fo_links($r['html'],$regions[0]['url']);
    $r=fo_fetch($councils[0]['url'],$fetch);$schools=fo_links($r['html'],$councils[0]['url'],true);
    $lastError=null;
    foreach(array_slice($schools,0,3) as $school) {
        try { $r=fo_fetch($school['url'],$fetch);$records=fo_records($r['html'],$school['url'],$school['id']);
            return count($regions).' regions; sample council: '.count($schools).' schools; sample school: '.count($records).' selections. Joining links optional.';
        } catch(RuntimeException $error) { $lastError=$error; }
    }
    throw $lastError??new RuntimeException('SOURCE_FORMAT');
}
function fo_links(string $html,string $base,bool $schools=false): array {
    $dom=fo_dom($html); $entries=[]; $parent=dirname((string)parse_url($base,PHP_URL_PATH)).'/';
    foreach ($dom->getElementsByTagName('a') as $a) {
        $url=fo_url($a->getAttribute('href'),$base); $text=fo_text($a->textContent);
        if (!$url || !$text) continue;
        $path=(string)parse_url($url,PHP_URL_PATH);
        if (strtolower(basename($path))!=='index.html' || dirname(dirname($path)).'/'!==rtrim($parent,'/').'/') continue;
        $id=rawurldecode(basename(dirname($path)));
        if (strtolower($id)==='placements') continue;
        if ($schools) { if (!preg_match('/\bPS\d{7}\b/i',$text,$m)) continue; $id=strtoupper($m[0]); }
        elseif (preg_match('/^PS\d+/i',$id)) continue;
        $entries[$id]=['id'=>$id,'name'=>$text,'url'=>$url];
    }
    if (!$entries) throw new RuntimeException('SOURCE_FORMAT');
    return array_values($entries);
}
function fo_entry(array $entries,string $id): array {
    foreach ($entries as $entry) if ($entry['id']===$id) return $entry;
    throw new InvalidArgumentException('INVALID_SELECTION');
}
function fo_normalize(string $name): string {
    $name=strtoupper(str_replace(['-','’'],[' ',"'"],$name));
    $name=str_replace([' MUNICIPAL',' DISTRICT',' CITY',' TOWN'],[' MC',' DC',' CC',' TC'],$name);
    $name=fo_text($name);
    if (!preg_match('/\s(?:DC|MC|CC|TC)$/',$name)) $name.=' DC';
    return $name;
}
function fo_candidate_location(string $candidate): array {
    if (!grf_is_valid_primary_candidate($candidate)) throw new InvalidArgumentException('INVALID_CANDIDATE');
    $district=substr(strtoupper($candidate),2,4);
    foreach (grf_district_directory() as $region=>$councils) foreach ($councils as $council=>$code) if ($code===$district) return [$region,$council];
    throw new RuntimeException('DIRECTORY_LOOKUP');
}
function fo_records(string $html,string $url,string $schoolCode): array {
    $dom=fo_dom($html); $records=[];
    foreach ($dom->getElementsByTagName('tr') as $tr) {
        $cells=$tr->getElementsByTagName('td'); if ($cells->length<6) continue;
        $index=strtoupper(fo_text($cells->item(1)->textContent));
        if (!grf_is_valid_primary_candidate($index) || explode('-',$index)[0]!==strtoupper($schoolCode)) continue;
        $joining=null;
        foreach ($cells->item(3)->getElementsByTagName('a') as $a) {
            $link=fo_url($a->getAttribute('href'),$url);
            if ($link && strtolower(pathinfo((string)parse_url($link,PHP_URL_PATH),PATHINFO_EXTENSION))==='pdf') { $joining=$link; break; }
        }
        $records[]=['candidate'=>$index,'sex'=>fo_text($cells->item(2)->textContent),'destination'=>fo_text($cells->item(3)->textContent),'type'=>fo_text($cells->item(4)->textContent),'council'=>fo_text($cells->item(5)->textContent),'joining_url'=>$joining];
    }
    if (!$records) throw new RuntimeException('SOURCE_FORMAT');
    return $records;
}
