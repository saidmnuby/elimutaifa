<?php
declare(strict_types=1);
require_once __DIR__.'/_layout.php';
require_once dirname(__DIR__).'/includes/notifications.php';
et_admin_boot(); $user=et_require_admin_at_root(); et_require_owner($user); $db=et_db(); $error='';
$mainOwner=et_email_main_owner($db,(int)$user['id']);
$p=et_notification_preferences($db);
if($_SERVER['REQUEST_METHOD']==='POST') {
    try {
        if(!et_verify_csrf($_POST['csrf_token'] ?? null)) throw new RuntimeException('This request has expired. Reload the page.');
        et_email_require_main_owner($db,(int)$user['id']);
        $time=(string)($_POST['time'] ?? '07:00');
        if(!preg_match('/^(?:[01][0-9]|2[0-3]):[0-5][0-9]$/D',$time)) throw new RuntimeException('Choose a valid report time.');
        foreach(['login','created','critical','daily','weekly'] as $key) $p[$key]=isset($_POST[$key]);
        $p['time']=$time; $p['enabled_on']=$p['enabled_on'] ?? (new DateTimeImmutable('now',new DateTimeZone('Africa/Dar_es_Salaam')))->format('Y-m-d');
        $percent=trim((string)($_POST['progress'] ?? ''));
        if($percent!=='' && (!ctype_digit($percent) || (int)$percent>100)) throw new RuntimeException('Progress must be a whole number from 0 to 100.');
        et_email_save($db,'notification_preferences',json_encode($p,JSON_THROW_ON_ERROR));
        if($percent!=='') et_email_save($db,'notification_progress',json_encode(['percent'=>(int)$percent,'notes'=>mb_substr(trim((string)($_POST['notes'] ?? '')),0,1000),'at'=>et_utc_now()],JSON_THROW_ON_ERROR));
        else $db->prepare('DELETE FROM app_settings WHERE setting_key=?')->execute(['notification_progress']);
        et_audit((int)$user['id'],'notification_preferences_changed','notifications');
        et_flash('success','Notification settings saved.'); et_redirect('notifications.php');
    } catch(Throwable $e) { $error=$e instanceof PDOException ? 'Settings could not be saved. Try again.' : $e->getMessage(); }
}
$progress=json_decode(et_email_setting($db,'notification_progress') ?? '{}',true) ?: [];
et_admin_header('Notifications',$user,'notifications');
?>
<?php if($error): ?><div class="admin-alert error" role="alert"><?= et_e($error) ?></div><?php endif; ?>
<section class="admin-card" style="max-width:850px"><h2>Notifications and schedule</h2>
<p>Reports go to active owners with an account email. Sign-in alerts also go to the signed-in admin when their email is set.</p>
<form method="post">
<fieldset style="border:0;padding:0;margin:0"<?= $mainOwner?'':' disabled' ?>>
<input type="hidden" name="csrf_token" value="<?= et_e(et_csrf_token()) ?>">
<?php foreach(['login'=>['Admin sign-ins','Sent after password and any required 2FA are completed.'],'created'=>['New admin accounts','Notify the owner and the new admin.'],'critical'=>['Critical alerts','Fatal errors, repeated source errors and repeated failed sign-ins. Similar alerts are limited to once an hour.'],'daily'=>['Daily morning report','Yesterday’s page views, visitors and visitors with successful results.'],'weekly'=>['Friday system report','The previous seven complete days, source health and recorded progress.']] as $key=>[$title,$description]): ?>
<div style="margin-bottom:18px"><label><input type="checkbox" name="<?= $key ?>"<?= $p[$key]?' checked':'' ?>> <strong><?= et_e($title) ?></strong></label><p style="margin:5px 0 0;color:var(--muted)"><?= et_e($description) ?></p></div>
<?php endforeach; ?>
<div class="form-field"><label for="reportTime">Report time (East Africa Time)</label><input id="reportTime" name="time" type="time" value="<?= et_e($p['time']) ?>" required></div>
<details style="margin:22px 0"><summary>Record development progress (optional)</summary><p>This is an owner assessment, not an automatic score.</p><div class="form-field"><label for="progress">Progress percentage</label><input id="progress" name="progress" type="number" min="0" max="100" value="<?= et_e((string)($progress['percent'] ?? '')) ?>"><label for="notes">Assessment notes</label><textarea id="notes" name="notes" maxlength="1000"><?= et_e($progress['notes'] ?? '') ?></textarea></div></details>
<?php if($mainOwner): ?><button type="submit" class="admin-button">Save settings</button><?php endif; ?>
</fieldset>
<?php if(!$mainOwner): ?><p>Only the main owner can change system notification settings. Manage your notification email in Account.</p><?php endif; ?>
</form></section>
<?php et_admin_footer(); ?>
