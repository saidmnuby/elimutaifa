<?php
declare(strict_types=1);
require_once __DIR__.'/form_one_cycles.php';

function f5_generated_source(mixed $intake,string $round): string {
    $url=fo_generated_source($intake,$round);
    return $url===''?'':str_replace(['selection.tamisemi.go.tz/allocations/'],['selform.tamisemi.go.tz/content/selection-and-allocation/'],$url);
}
function f5_source_valid(string $url): bool {
    $p=parse_url($url);
    return is_array($p) && ($p['scheme']??'')==='https' && ($p['host']??'')==='selform.tamisemi.go.tz'
        && !isset($p['user']) && !isset($p['pass']) && !isset($p['port']) && !isset($p['query']) && !isset($p['fragment'])
        && preg_match('#^/[Cc]ontent/selection-and-allocation/20[0-9]{2}/[a-z0-9-]+/[Ii]ndex\.html$#D',$p['path']??'')===1;
}
function f5_validate_cycle(array $input): array {
    if(($input['source_mode']??'manual')==='auto') $input['source_url']=f5_generated_source($input['intake_year']??'',(string)($input['round_label']??''));
    $input['source_mode']='manual';
    return fo_validate_cycle($input,'f5_source_valid');
}
function f5_cycles(?PDO $database=null): array {
    $database??=et_db();
    $rows=$database->query("SELECT * FROM form_five_cycles WHERE status='published' AND verified_at IS NOT NULL ORDER BY intake_year DESC,id DESC")->fetchAll();
    $default=$database->query("SELECT setting_value FROM app_settings WHERE setting_key='form_five_default_cycle'")->fetchColumn();
    $cycles=[];
    foreach($rows as $row) {
        if(!f5_source_valid($row['source_url'])) continue;
        $cycles[$row['cycle_key']]=$row+['url'=>$row['source_url'],'label'=>$row['intake_year'].' · '.$row['round_label'].' (CSEE '.$row['exam_year'].')'];
    }
    if(isset($cycles[$default])) $cycles=[$default=>$cycles[$default]]+$cycles;
    return $cycles;
}
function f5_track_cache_url(string $url): void {
    if(!preg_match('#^(https://selform\.tamisemi\.go\.tz/[Cc]ontent/selection-and-allocation/20[0-9]{2}/[a-z0-9-]+/)#',$url,$m)) return;
    $handle=@fopen(fo_cache_manifest($m[1].'index.html'),'c+');if(!$handle) return;
    try {
        if(!flock($handle,LOCK_EX)) return;
        $urls=json_decode(stream_get_contents($handle)?:'[]',true);$urls=is_array($urls)?$urls:[];
        if(!in_array($url,$urls,true)) { $urls[]=$url;ftruncate($handle,0);rewind($handle);fwrite($handle,json_encode($urls));fflush($handle); }
    } finally { flock($handle,LOCK_UN);fclose($handle); }
}
function f5_clear_cycle_cache(string $source): int {
    if(!f5_source_valid($source)) throw new InvalidArgumentException('INVALID_SELECTION');
    $file=fo_cache_manifest($source);$urls=is_file($file)?json_decode(file_get_contents($file)?:'[]',true):[];
    $urls=is_array($urls)?$urls:[];$urls[]=$source;$count=0;
    foreach(array_unique($urls) as $url) {
        if(!is_string($url) || !str_starts_with($url,dirname($source).'/') || !f5_url($url,$source)) continue;
        $target=sys_get_temp_dir().DIRECTORY_SEPARATOR.'grf-result-cache'.DIRECTORY_SEPARATOR.'result-'.hash('sha256',$url).'.html';
        if(is_file($target) && unlink($target)) $count++;
    }
    return $count;
}
function f5_verify_cycle(array $cycle,?callable $fetch=null): string {
    $source=$cycle['source_url'];if(!f5_source_valid($source)) throw new RuntimeException('INVALID_SOURCE');
    if(!$fetch) f5_clear_cycle_cache($source);
    $r=f5_fetch($source,$fetch);
    if(!preg_match('/KIDATO CHA TANO[^0-9]{0,60}'.preg_quote((string)$cycle['intake_year'],'/').'/iu',fo_text(fo_dom($r['html'])->textContent))) throw new RuntimeException('INTAKE_YEAR_MISMATCH');
    $regions=f5_links($r['html'],$source);
    $r=f5_fetch($regions[0]['url'],$fetch);$councils=f5_links($r['html'],$regions[0]['url']);
    $r=f5_fetch($councils[0]['url'],$fetch);$schools=f5_links($r['html'],$councils[0]['url'],true);
    $last=null;
    foreach(array_slice($schools,0,3) as $school) {
        try {
            $r=f5_fetch($school['url'],$fetch);$records=f5_records($r['html'],$school['url'],$school['id'],(int)$cycle['exam_year']);
            return count($regions).' regions; sample council: '.count($schools).' schools/centres; sample school: '.count($records).' selections. CSEE year checked.';
        } catch(RuntimeException $e) { $last=$e; }
    }
    throw $last??new RuntimeException('SOURCE_FORMAT');
}
