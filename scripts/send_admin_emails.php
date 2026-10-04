<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once dirname(__DIR__) . '/includes/notifications.php';
try {
    $db=et_db();
    // Deliver existing alerts first. A report/source failure must not block sign-ins.
    $counts=et_email_process($db);
    et_email_save($db,'notification_delivery_completed',et_utc_now());
    $checkFailed=false;
    try { et_notification_run($db); }
    catch (Throwable $e) {
        $checkFailed=true;
        error_log('ElimuTaifa notification checks failed: '.get_class($e).' at '.basename($e->getFile()).':'.$e->getLine());
    }
    $newCounts=et_email_process($db);
    foreach(['sent','failed'] as $key) $counts[$key]+=$newCounts[$key];
    et_email_save($db,'notification_delivery_completed',et_utc_now());
    echo 'Sent: ' . $counts['sent'] . '; failed: ' . $counts['failed'] . PHP_EOL;
    if($checkFailed) exit(1);
} catch (Throwable) { fwrite(STDERR,"Email worker could not run. Check sender configuration and database access.\n"); exit(1); }
