<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli') { http_response_code(404);exit; }
require_once dirname(__DIR__).'/includes/form_five.php';
$database=et_db();
$row=$database->query("SELECT * FROM form_five_cycles WHERE cycle_key='2026-first'")->fetch();
if(!$row || $row['source_url']!=='https://selform.tamisemi.go.tz/content/selection-and-allocation/2026/first-selection/index.html') throw new RuntimeException('Unexpected seed; use admin verification.');
if($row['verified_at'] || $row['updated_by'] || $row['status']!=='draft') { echo "SKIP: cycle already managed; use admin.\n";exit; }
$report=f5_verify_cycle($row);
$database->beginTransaction();
try {
    $database->prepare("UPDATE form_five_cycles SET status='published',verified_at=?,verification_report=?,updated_at=? WHERE id=?")->execute([et_utc_now(),$report,et_utc_now(),$row['id']]);
    $database->prepare(et_conflict_sql($database,"INSERT INTO app_settings(setting_key,setting_value,updated_at) VALUES('form_five_default_cycle','2026-first',:now) ON CONFLICT(setting_key) DO NOTHING"))->execute(['now'=>et_utc_now()]);
    $database->prepare('INSERT INTO audit_logs(admin_user_id,action,entity_type,entity_id,details,created_at) VALUES(NULL,?,?,?,?,?)')->execute(['form_five_cycle_initialized','form_five_cycle',$row['id'],'CLI: '.$report,et_utc_now()]);
    $database->commit();
    echo "PASS: verified initial cycle published. ".$report."\n";
} catch(Throwable $error) { if($database->inTransaction())$database->rollBack();throw $error; }
