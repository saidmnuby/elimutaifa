<?php
declare(strict_types=1);
require_once __DIR__.'/admin_email.php';
require_once __DIR__.'/exam_monitoring.php';

function et_notification_preferences(PDO $db): array {
    return array_replace(['login'=>true,'created'=>true,'critical'=>true,'daily'=>true,'weekly'=>true,'checks'=>true,'time'=>'07:00'],json_decode(et_email_setting($db,'notification_preferences') ?? '{}',true) ?: []);
}
/** Stable outbox keys prevent duplicate reports even with concurrent workers. */
function et_notification_queue(PDO $db,string $identity,string $title,string $body): int {
    if (et_email_setting($db,'admin_email_enabled') !== '1') return 0;
    $count=0; $seen=[];
    foreach ($db->query("SELECT id FROM admin_users WHERE role='owner' AND is_active=1 AND deleted_at IS NULL")->fetchAll() as $owner) {
        $email=et_email_address(et_email_setting($db,'admin_email_address_'.$owner['id']) ?? '');
        if (!$email || isset($seen[strtolower($email)])) continue;
        $seen[strtolower($email)]=true;
        $key='admin_email_outbox_'.substr(hash('sha256',$identity.'|'.strtolower($email)),0,32);
        $q=$db->prepare(et_conflict_sql($db,'INSERT INTO app_settings (setting_key,setting_value,updated_at) VALUES (?,?,?) ON CONFLICT(setting_key) DO NOTHING'));
        $q->execute([$key,json_encode(['status'=>'pending','event'=>'report','to'=>$email,'subject'=>'ElimuTaifa: '.$title,'body'=>$body,'created_at'=>et_utc_now(),'attempts'=>0],JSON_THROW_ON_ERROR),et_utc_now()]);
        $count += $q->rowCount();
    }
    return $count;
}
function et_notification_report(PDO $db,string $start,string $end,bool $weekly=false): string {
    $scalar=static function(string $sql,array $args=[]) use($db): int { $q=$db->prepare($sql); $q->execute($args); return (int)$q->fetchColumn(); };
    $views=$scalar('SELECT COALESCE(SUM(views),0) FROM traffic_daily WHERE day>=? AND day<=? AND path<>?',[$start,$end,'@event/search-success']);
    $visitors=$scalar('SELECT COUNT(DISTINCT visitor_hash) FROM traffic_unique_visitors WHERE day>=? AND day<=? AND path<>?',[$start,$end,'@event/search-success']);
    $success=$scalar('SELECT COUNT(DISTINCT visitor_hash) FROM traffic_unique_visitors WHERE day>=? AND day<=? AND path=?',[$start,$end,'@event/search-success']);
    $zone=new DateTimeZone('Africa/Dar_es_Salaam');
    $from=(new DateTimeImmutable($start.' 00:00:00',$zone))->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s');
    $until=(new DateTimeImmutable($end.' 00:00:00',$zone))->modify('+1 day')->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s');
    $errors=$scalar("SELECT COUNT(*) FROM system_events WHERE last_seen_at>=? AND last_seen_at<? AND severity IN ('error','critical')",[$from,$until]);
    $open=$scalar("SELECT COUNT(*) FROM system_events WHERE status='open' AND severity IN ('error','critical')");
    $body="ElimuTaifa ".($weekly?'weekly system report':'daily visitor report')."\nPeriod: $start to $end (EAT)\n\nPage views: $views\nEstimated unique visitors: $visitors\nVisitors with successful results: $success\nDistinct error groups active in this period: $errors\nOpen error groups: $open\n\nVisitor counts use anonymous browser/network identities, not verified people. Successful visitors are counted once per period, not once per search. Bots and browsers requesting Do Not Track are excluded.\n";
    if ($weekly) {
        $logins=$scalar("SELECT COUNT(*) FROM audit_logs WHERE action='login_succeeded' AND created_at>=? AND created_at<?",[$from,$until]);
        $changes=$scalar('SELECT COUNT(*) FROM audit_logs WHERE created_at>=? AND created_at<?',[$from,$until]);
        $published=$scalar("SELECT COUNT(*) FROM content_items WHERE status='published'");
        $cycles=et_exam_cycles(null,$db); $checked=0; $failed=0;
        foreach($cycles as $id=>$cycle) { $check=json_decode(et_email_setting($db,'notification_check_'.$id) ?? '{}',true) ?: []; if(!empty($check['at'])) { $checked++; if(empty($check['ok'])) $failed++; } }
        $progress=json_decode(et_email_setting($db,'notification_progress') ?? '{}',true) ?: [];
        $body.="\nAdmin sign-ins: $logins\nRecorded admin actions: $changes\nPublished content: $published\nPublished exam cycles: ".count($cycles)."\nCycles checked by automation: $checked\nLatest failed checks: $failed\n";
        $previousStart=(new DateTimeImmutable($start))->modify('-7 days')->format('Y-m-d');
        $previousEnd=(new DateTimeImmutable($start))->modify('-1 day')->format('Y-m-d');
        $previousViews=$scalar('SELECT COALESCE(SUM(views),0) FROM traffic_daily WHERE day>=? AND day<=? AND path<>?',[$previousStart,$previousEnd,'@event/search-success']);
        $pending=0; $deliveryFailed=0;
        foreach($db->query("SELECT setting_value FROM app_settings WHERE setting_key LIKE 'admin_email_outbox_%'")->fetchAll(PDO::FETCH_COLUMN) as $raw) { $item=json_decode($raw,true) ?: []; if(($item['status'] ?? '')==='pending') $pending++; if(($item['status'] ?? '')==='failed' && ($item['created_at'] ?? '') >= $from && ($item['created_at'] ?? '') < $until) $deliveryFailed++; }
        $free=disk_free_space(dirname(__DIR__));
        $body.="Previous week's page views: $previousViews\nView change: ".($views-$previousViews)."\nPending email notifications: $pending\nFailed email notifications created this period: $deliveryFailed\nAvailable disk space: ".($free===false?'Not available':round($free/1073741824,2).' GB')."\nPHP version: ".PHP_VERSION."\n";
        require_once __DIR__.'/ad_networks.php';
        $adUnits=et_ad_units($db); $adStats=et_ad_stats($db); $adFailures=array_sum(array_map(static fn($s)=>(int)($s['failed'] ?? 0),$adStats));
        $body.='Network ad units configured: '.count($adUnits)."\nLocal ad-script failure signals in the last seven days: $adFailures\nAd impressions, clicks and earnings: provider reports are not connected.\n";
        $body.=$progress ? 'Owner-recorded development progress: '.$progress['percent'].'% (recorded '.$progress['at']." UTC)\nNotes: ".$progress['notes']."\n" : "Development progress: no owner assessment recorded.\n";
        $body.="\nUptime, server response time, load capacity and search-engine reach are not measured by this report. Source checks test a directory and sample school; they do not test every school. Review Notifications & Checks and Traffic & Errors for details.\n";
    }
    return $body;
}
function et_notification_run(PDO $db,?DateTimeImmutable $now=null,?callable $check=null): void {
    $now=($now ?? new DateTimeImmutable())->setTimezone(new DateTimeZone('Africa/Dar_es_Salaam'));
    $p=et_notification_preferences($db);
    et_email_save($db,'notification_worker_started',et_utc_now());
    if(et_email_setting($db,'admin_email_enabled')==='1' && $now->format('H:i') >= $p['time']) {
        $end=$now->modify('-1 day')->format('Y-m-d');
        if($p['daily']) et_notification_queue($db,'daily-'.$now->format('Y-m-d'),'Daily report · '.$end,et_notification_report($db,$end,$end));
        // Catch up Friday's report after a weekend restart, once per week.
        $friday=$now->format('N')==='5' ? $now : $now->modify('last friday');
        if($p['weekly'] && $friday->format('Y-m-d') >= (json_decode(et_email_setting($db,'notification_preferences') ?? '{}',true)['enabled_on'] ?? $now->format('Y-m-d'))) {
            $weekEnd=$friday->modify('-1 day')->format('Y-m-d');
            et_notification_queue($db,'weekly-'.$friday->format('Y-m-d'),'Weekly system report · '.$friday->format('Y-m-d'),et_notification_report($db,$friday->modify('-7 days')->format('Y-m-d'),$weekEnd,true));
        }
    }
    if($p['critical']) {
        $free=disk_free_space(dirname(__DIR__));
        if($free!==false && $free<524288000) et_notification_queue($db,'disk-'.gmdate('Y-m-d-H'),'Low server disk space','Available disk space is below 500 MB. Review server storage.');
        $missing=array_filter(['curl','dom','pdo','openssl'],static fn($extension)=>!extension_loaded($extension));
        if($missing) et_notification_queue($db,'extensions-'.gmdate('Y-m-d-H'),'Required PHP extensions missing','Required extensions missing: '.implode(', ',$missing));
        $failed=0;
        foreach($db->query("SELECT setting_value FROM app_settings WHERE setting_key LIKE 'admin_email_outbox_%' AND updated_at >= '".gmdate('Y-m-d H:i:s',time()-3600)."'")->fetchAll(PDO::FETCH_COLUMN) as $raw) if((json_decode($raw,true)['status'] ?? '')==='failed') $failed++;
        if($failed) et_notification_queue($db,'delivery-'.gmdate('Y-m-d-H'),'Email delivery needs review',"$failed notification(s) failed in the past hour. Review the email connection and delivery history. If the email provider is unavailable, this alert also cannot be delivered.");
        $q=$db->prepare("SELECT * FROM system_events WHERE status='open' AND last_seen_at>=? AND (severity='critical' OR (severity='error' AND occurrences>=3)) ORDER BY last_seen_at DESC LIMIT 20");
        $q->execute([gmdate('Y-m-d H:i:s',time()-3600)]);
        foreach($q->fetchAll() as $event) et_notification_queue($db,'critical-'.$event['fingerprint'].'-'.substr($event['last_seen_at'],0,13),'Critical system alert',"A system problem needs review.\nType: ".$event['event_type']."\nSeverity: ".$event['severity']."\nOccurrences: ".$event['occurrences']."\nLast seen: ".$event['last_seen_at']." UTC\nReview Traffic & Errors.\n");
        $q=$db->prepare("SELECT COUNT(*) FROM audit_logs WHERE action='login_failed' AND created_at>=?"); $q->execute([gmdate('Y-m-d H:i:s',time()-900)]);
        if((int)$q->fetchColumn()>=5) et_notification_queue($db,'login-failures-'.gmdate('Y-m-d-H'),'Repeated failed admin sign-ins','At least five failed sign-ins were recorded in 15 minutes. Review the audit log and account security.');
    }
    if($p['checks']) {
        $cycles=et_exam_cycles(null,$db); $selected=null; $oldest=PHP_INT_MAX;
        foreach($cycles as $id=>$cycle) { $state=json_decode(et_email_setting($db,'notification_check_'.$id) ?? '{}',true) ?: []; $at=strtotime(($state['at'] ?? '1970-01-01').' UTC') ?: 0; if($at<$oldest) { $oldest=$at; $selected=[$id,$cycle,$state]; } }
        $last=(int)(et_email_setting($db,'notification_check_tick') ?? '0');
        if($selected && time()-$last>=300 && time()-$oldest>=86400) {
            et_email_save($db,'notification_check_tick',(string)time());
            [$id,$cycle,$state]=$selected;
            try { $candidate=$check ? $check($cycle) : et_exam_auto_check($cycle); if(et_exam_changed($cycle,$candidate)) throw new RuntimeException('The source layout changed. Review the saved paths in Result Pages.'); $state=['ok'=>true,'failures'=>0,'message'=>'Directory and sample school passed.']; }
            catch(Throwable $e) { $state=['ok'=>false,'failures'=>(int)($state['failures'] ?? 0)+1,'message'=>mb_substr($e->getMessage(),0,500)]; }
            $state['at']=et_utc_now(); et_email_save($db,'notification_check_'.$id,json_encode($state,JSON_THROW_ON_ERROR));
            if(!$state['ok'] && $state['failures']>=3 && $p['critical']) et_notification_queue($db,'source-'.$id.'-'.$now->format('Y-m-d'),'Source check failed · '.strtoupper($cycle['level']).' '.$cycle['year'],"Three or more consecutive checks failed.\n".$state['message']."\nThe saved source has not been changed. Review Result Pages.");
        }
    }
    et_email_save($db,'notification_worker_completed',et_utc_now());
}
