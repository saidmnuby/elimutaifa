<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/includes/admin_auth.php';
et_admin_boot();
$user = et_require_admin();
if ($_SERVER['REQUEST_METHOD']!=='POST' || !et_verify_csrf($_POST['csrf_token']??null)) { http_response_code(405); exit('Method not allowed'); }
$id=filter_input(INPUT_POST,'id',FILTER_VALIDATE_INT)?:0;
$status=(string)($_POST['status']??'');
if($id<=0 || !in_array($status,['open','resolved'],true)){et_flash('error','Invalid event update.');et_redirect('./');}
$resolvedAt=$status==='resolved'?et_utc_now():null;
$statement=et_db()->prepare('UPDATE system_events SET status=:status,resolved_at=:resolved_at WHERE id=:id');
$statement->execute(['status'=>$status,'resolved_at'=>$resolvedAt,'id'=>$id]);
et_audit((int)$user['id'],'system_event_'.$status,'system_event',$id);
$returnStatus=(string)($_POST['return_status']??'open');
$returnStatus=in_array($returnStatus,['open','resolved','all'],true)?$returnStatus:'open';
$returnSeverity=(string)($_POST['return_severity']??'all');
$returnSeverity=in_array($returnSeverity,['all','info','warning','error','critical'],true)?$returnSeverity:'all';
$returnPage=max(1,filter_var($_POST['return_page']??1,FILTER_VALIDATE_INT)?:1);
et_flash('success',$status==='resolved'?'Event marked as resolved.':'Event reopened.');
et_redirect('./?status='.rawurlencode($returnStatus).'&severity='.rawurlencode($returnSeverity).'&page='.$returnPage);
