<?php
declare(strict_types=1);
require_once __DIR__.'/admin_db.php';

function fo_generated_source(mixed $year,string $round): string {
    $year=filter_var($year,FILTER_VALIDATE_INT);
    $slug=trim(preg_replace('/[^a-z0-9]+/','-',strtolower(trim($round)))??'','-');
    if(!$year || $year<2000 || $year>2099 || $slug==='') return '';
    return 'https://selection.tamisemi.go.tz/allocations/'.$year.'/'.$slug.'/index.html';
}

function fo_source_valid(string $url): bool {
    $p=parse_url($url);
    return is_array($p) && ($p['scheme']??'')==='https' && ($p['host']??'')==='selection.tamisemi.go.tz'
        && !isset($p['user'],$p['pass']) && !isset($p['user']) && !isset($p['pass']) && !isset($p['port']) && !isset($p['query']) && !isset($p['fragment'])
        && preg_match('#^/allocations/20[0-9]{2}/[a-z0-9-]+/[Ii]ndex\.html$#D',$p['path']??'')===1;
}
function fo_cycles(?PDO $database=null): array {
    $database??=et_db();
    $rows=$database->query("SELECT * FROM form_one_cycles WHERE status='published' AND verified_at IS NOT NULL ORDER BY intake_year DESC,id DESC")->fetchAll();
    $default=$database->query("SELECT setting_value FROM app_settings WHERE setting_key='form_one_default_cycle'")->fetchColumn();
    $cycles=[];
    foreach($rows as $row) {
        if (!fo_source_valid($row['source_url'])) continue;
        $cycles[$row['cycle_key']]=$row+['url'=>$row['source_url'],'label'=>$row['intake_year'].' · '.$row['round_label'].' (PSLE '.$row['exam_year'].')'];
    }
    if (isset($cycles[$default])) $cycles=[$default=>$cycles[$default]]+$cycles;
    return $cycles;
}
function fo_validate_cycle(array $input,?callable $sourceValidator=null): array {
    $data=['cycle_key'=>trim((string)($input['cycle_key']??'')), 'round_label'=>trim((string)($input['round_label']??'')),
        'source_url'=>trim((string)($input['source_url']??'')), 'status'=>(string)($input['status']??'draft'),
        'intake_year'=>filter_var($input['intake_year']??'',FILTER_VALIDATE_INT), 'exam_year'=>filter_var($input['exam_year']??'',FILTER_VALIDATE_INT)];
    if(($input['source_mode']??'manual')==='auto') $data['source_url']=fo_generated_source($data['exam_year'],$data['round_label']);
    $errors=[];
    if(!preg_match('/^[a-z0-9][a-z0-9-]{2,59}$/D',$data['cycle_key'])) $errors[]='Cycle key iwe herufi ndogo/namba/dash (3–60).';
    if(mb_strlen($data['round_label'])<3 || mb_strlen($data['round_label'])>80) $errors[]='Jina la round liwe herufi 3–80.';
    if(!$data['intake_year'] || !$data['exam_year'] || $data['exam_year']<2010 || $data['intake_year']>2100 || $data['exam_year']>$data['intake_year'] || $data['intake_year']-$data['exam_year']>2) $errors[]='Hakiki exam year na intake year.';
    if(!($sourceValidator??'fo_source_valid')($data['source_url'])) $errors[]='Source iwe official HTTPS cycle index ya TAMISEMI; si arbitrary URL.';
    if(!in_array($data['status'],['draft','published','archived'],true)) $errors[]='Status si sahihi.';
    return [$data,$errors];
}
function fo_cycle_changed(array $old,array $new): bool {
    foreach(['cycle_key','intake_year','exam_year','round_label','source_url'] as $field) if((string)$old[$field] !== (string)$new[$field]) return true;
    return false;
}
