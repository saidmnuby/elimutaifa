<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/includes/admin_auth.php';
$database = et_open_database(':memory:');
$events = [
    ['login_failed','admin_user'], ['login_succeeded','admin_user'], ['logout','admin_user'],
    ['admin_created','admin_user'], ['admin_updated','admin_user'], ['admin_removed','admin_user'],
    ['password_changed','admin_user'], ['two_factor_enabled','admin_user'], ['two_factor_cli_reset','admin_user'],
    ['future_security_event','unknown'], ['login_failed','content_item'],
    ['content_created','content_item'], ['submission_updated','submission'], ['system_event_resolved','system_event'],
];
$insert = $database->prepare('INSERT INTO audit_logs(action,entity_type,details,created_at) VALUES(?,?,?,?)');
foreach ($events as [$action,$entity]) { $insert->execute([$action,$entity,'visibility fixture',et_utc_now()]); }
foreach ([['owner',count($events)],['admin',3],['unknown',0]] as [$role,$expected]) {
    $where = et_audit_visibility_sql(['role'=>$role]);
    $count = (int) $database->query('SELECT COUNT(*) FROM audit_logs WHERE '.$where)->fetchColumn();
    if ($count !== $expected) { throw new RuntimeException('Audit visibility count failed for '.$role); }
    $actions = $database->query('SELECT DISTINCT action FROM audit_logs WHERE '.$where)->fetchAll(PDO::FETCH_COLUMN);
    if ($role === 'admin' && in_array('login_failed',$actions,true)) { throw new RuntimeException('Sensitive filter option leaked'); }
    $query = $database->prepare('SELECT * FROM audit_logs WHERE '.$where.' AND action=?');
    $query->execute(['admin_created']);
    if ($role !== 'owner' && $query->fetch()) { throw new RuntimeException('Direct action filter bypassed visibility'); }
    $recent = $database->query('SELECT action FROM audit_logs WHERE '.$where.' ORDER BY id DESC LIMIT 3')->fetchAll(PDO::FETCH_COLUMN);
    if ($role === 'admin' && count($recent) !== 3) { throw new RuntimeException('Dashboard visibility failed'); }
}
echo "PASS: owner-only security/account audit events, operational admin events, counts, filters, direct filter requests and dashboard visibility.\n";
