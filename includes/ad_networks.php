<?php
declare(strict_types=1);
require_once __DIR__.'/placements.php';
require_once __DIR__.'/admin_email.php';
require_once __DIR__.'/monitoring.php';

function et_ad_units(PDO $db): array {
    $units=[];
    foreach($db->query("SELECT setting_key,setting_value FROM app_settings WHERE setting_key LIKE 'ad_unit_%'") as $row) {
        $unit=json_decode($row['setting_value'],true);
        if(is_array($unit) && preg_match('/^[a-f0-9]{24}$/D',(string)($unit['id'] ?? ''))) $units[$unit['id']]=$unit;
    }
    return $units;
}
function et_ad_validate(array $input): array {
    $unit=[];
    foreach(['name','provider','slot','status','publisher','unit_code','script_url','starts_at','ends_at'] as $key) $unit[$key]=trim((string)($input[$key] ?? ''));
    if(mb_strlen($unit['name'])<3 || mb_strlen($unit['name'])>140) throw new InvalidArgumentException('Use 3 to 140 characters for the name.');
    if(!in_array($unit['provider'],['adsense','adsterra'],true)) throw new InvalidArgumentException('Choose a supported provider.');
    if(!in_array($unit['slot'],['top','bottom'],true)) throw new InvalidArgumentException('Network ads use above or below content positions.');
    if(!in_array($unit['status'],['draft','published','archived'],true)) throw new InvalidArgumentException('Choose a valid status.');
    $unit['targets']=array_values(array_unique(is_array($input['targets'] ?? null) ? $input['targets'] : []));
    if(!$unit['targets']) throw new InvalidArgumentException('Choose at least one display page.');
    foreach($unit['targets'] as $target) if(!is_string($target) || !isset(et_placement_targets()[$target])) throw new InvalidArgumentException('Invalid display page.');
    $unit['priority']=filter_var($input['priority'] ?? 0,FILTER_VALIDATE_INT);
    if($unit['priority']===false || $unit['priority']<0 || $unit['priority']>100) throw new InvalidArgumentException('Priority must be 0 to 100.');
    if($unit['provider']==='adsense') {
        if(!preg_match('/^ca-pub-[0-9]{16}$/D',$unit['publisher']) || !preg_match('/^[0-9]{5,20}$/D',$unit['unit_code'])) throw new InvalidArgumentException('Enter the publisher ID and numeric ad slot from your AdSense display ad code.');
        $unit['script_url']='';
    } else {
        if(!preg_match('/^[a-f0-9]{32}$/D',$unit['unit_code']) || !et_is_safe_public_url($unit['script_url'])) throw new InvalidArgumentException('Enter the Native Banner key and HTTPS invoke.js URL supplied by Adsterra.');
        $url=parse_url($unit['script_url']);
        $host=strtolower((string)($url['host'] ?? ''));
        if(!preg_match('/^(?:[a-z0-9](?:[a-z0-9-]*[a-z0-9])?\.)+[a-z]{2,}$/D',$host) || filter_var($host,FILTER_VALIDATE_IP) || preg_match('/\.(?:local|internal|localhost)$/D',$host) || isset($url['user']) || isset($url['pass']) || isset($url['port'])) throw new InvalidArgumentException('Use the public HTTPS script domain supplied by Adsterra.');
        if(($url['scheme'] ?? '')!=='https' || isset($url['query']) || isset($url['fragment']) || ($url['path'] ?? '')!=='/'.$unit['unit_code'].'/invoke.js') throw new InvalidArgumentException('Use the exact HTTPS Native Banner URL ending in /your-key/invoke.js.');
        $unit['publisher']='';
    }
    $unit['consent_ready']=isset($input['consent_ready']);
    if($unit['status']==='published' && !$unit['consent_ready']) throw new InvalidArgumentException('Complete provider approval and required consent setup before publishing.');
    foreach(['starts_at','ends_at'] as $key) {
        $raw=$unit[$key]; $unit[$key]=$raw===''?null:et_local_datetime_to_utc($raw);
        if($raw!=='' && (!$unit[$key] || et_utc_datetime_to_local($unit[$key])!==$raw)) throw new InvalidArgumentException('Choose a valid schedule.');
    }
    if($unit['starts_at'] && $unit['ends_at'] && $unit['ends_at']<=$unit['starts_at']) throw new InvalidArgumentException('End time must follow start time.');
    return $unit;
}
function et_ad_token(PDO $db,string $id,string $page,int $expires): string {
    return hash_hmac('sha256',$id.'|'.$page.'|'.$expires,et_monitoring_secret($db));
}
function et_ad_public_units(PDO $db,string $page): array {
    $items=[]; $now=et_utc_now();
    foreach(et_ad_units($db) as $unit) {
        if($unit['status']!=='published' || empty($unit['consent_ready']) || ($unit['starts_at'] && $unit['starts_at']>$now) || ($unit['ends_at'] && $unit['ends_at']<=$now) || !et_placement_matches($unit['targets'],$page)) continue;
        $expires=time()+3600;
        $items[]=['id'=>'network-'.$unit['id'],'network_id'=>$unit['id'],'provider'=>$unit['provider'],'title'=>$unit['name'],'slot'=>$unit['slot'],'priority'=>$unit['priority'],'publisher'=>$unit['publisher'],'unit_code'=>$unit['unit_code'],'script_url'=>$unit['script_url'],'expires'=>$expires,'token'=>et_ad_token($db,$unit['id'],$page,$expires)];
    }
    return $items;
}
/** These are local delivery signals, never billable ad impressions or earnings. */
function et_ad_record(PDO $db,string $id,string $event): void {
    if(!et_monitoring_enabled() || ($_SERVER['HTTP_DNT'] ?? '')==='1' || preg_match('/bot|crawler|spider/i',$_SERVER['HTTP_USER_AGENT'] ?? '')) return;
    $day=(new DateTimeImmutable('now',new DateTimeZone('Africa/Dar_es_Salaam')))->format('Y-m-d');
    $visitor=hash_hmac('sha256',($_SERVER['REMOTE_ADDR'] ?? '').'|'.($_SERVER['HTTP_USER_AGENT'] ?? '').'|'.$day,et_monitoring_secret($db));
    $key='ad_event_'.hash('sha256',$id.'|'.$event.'|'.$visitor.'|'.gmdate('Y-m-d-H'));
    $db->beginTransaction();
    try {
        $q=$db->prepare(et_conflict_sql($db,'INSERT INTO app_settings(setting_key,setting_value,updated_at) VALUES(?,?,?) ON CONFLICT(setting_key) DO NOTHING'));
        $q->execute([$key,'1',et_utc_now()]);
        if($q->rowCount()===1) {
            $q=$db->prepare(et_conflict_sql($db,'INSERT INTO app_settings(setting_key,setting_value,updated_at) VALUES(?,?,?) ON CONFLICT(setting_key) DO UPDATE SET setting_value=app_settings.setting_value+1,updated_at=excluded.updated_at'));
            $q->execute(['ad_stat_'.$day.'_'.$id.'_'.$event,'1',et_utc_now()]);
        }
        $db->commit();
    } catch(Throwable $e) { if($db->inTransaction()) $db->rollBack(); throw $e; }
    if(random_int(1,20)===1) {
        $db->prepare("DELETE FROM app_settings WHERE setting_key LIKE 'ad_event_%' AND updated_at<?")->execute([gmdate('Y-m-d H:i:s',time()-86400)]);
        $db->prepare("DELETE FROM app_settings WHERE setting_key LIKE 'ad_stat_%' AND updated_at<?")->execute([gmdate('Y-m-d H:i:s',time()-60*86400)]);
    }
}
function et_ad_stats(PDO $db): array {
    $stats=[];
    $q=$db->prepare("SELECT setting_key,setting_value FROM app_settings WHERE setting_key LIKE 'ad_stat_%' AND updated_at>=?");
    $q->execute([gmdate('Y-m-d H:i:s',time()-7*86400)]);
    foreach($q as $row) if(preg_match('/^ad_stat_[0-9-]+_([a-f0-9]{24})_(mounted|loaded|failed)$/D',$row['setting_key'],$m)) $stats[$m[1]][$m[2]]=($stats[$m[1]][$m[2]] ?? 0)+(int)$row['setting_value'];
    return $stats;
}
