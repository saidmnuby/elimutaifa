<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/includes/admin_auth.php';
require_once dirname(__DIR__) . '/includes/exam_monitoring.php';
require_once __DIR__ . '/_layout.php';
et_admin_boot(); $user = et_require_admin_at_root(); $database = et_db();
$owner = ($user['role'] ?? '') === 'owner';
$level = is_string($_GET['level'] ?? null) && in_array($_GET['level'], ET_EXAM_LEVELS, true) ? $_GET['level'] : 'acsee';
$year = filter_input(INPUT_GET, 'year', FILTER_VALIDATE_INT) ?: (int)date('Y');
$error = ''; $raw = null; $item = null;
try {
    $key = et_exam_key($level,$year);
    $q = $database->prepare('SELECT setting_value FROM app_settings WHERE setting_key=?'); $q->execute([$key]);
    $raw = $q->fetchColumn(); $raw = $raw === false ? null : $raw;
    $item = $raw === null ? null : json_decode($raw,true,512,JSON_THROW_ON_ERROR);
} catch (Throwable) { http_response_code(400); exit('Invalid examination cycle.'); }
$values = $item ?? et_exam_provider_cycle($level,$year,'necta');
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    et_require_owner($user);
    try {
        if (!et_verify_csrf($_POST['csrf_token'] ?? null)) throw new RuntimeException('Your session has expired. Reload the page and try again.');
        if (!hash_equals(hash('sha256',$raw ?? ''),(string)($_POST['revision'] ?? ''))) throw new RuntimeException('This cycle has changed. Reload the page before continuing.');
        $op = (string)($_POST['operation'] ?? '');
        if ($op === 'check') {
            set_time_limit(90);
            $provider = (string)($_POST['provider'] ?? et_exam_provider($values));
            $proposed = et_exam_provider_cycle($level,$year,$provider,$item);
            $values = et_exam_monitor_check($item,$proposed,(int)$user['id']);
            $message = !$values['last_check_ok'] ? 'The source check failed. ' . $values['verification_report']
                : ($values['status'] === 'published' ? 'The source check passed. This year is still available to users.' : 'The source check passed. Click Publish cycle to make this year available to users.');
            et_exam_store($database,$values,$raw,(int)$user['id'],'exam_cycle_checked');
            et_flash($values['last_check_ok'] ? 'success' : 'error',$message);
        } elseif ($op === 'publish') {
            if (!$item || empty($item['verified_at']) || ($item['last_check_ok'] ?? false) !== true
                || (strtotime($item['last_check_at'] . ' UTC') ?: 0) < time()-86400) throw new RuntimeException('Click Check source first. Publishing requires a successful check within the last 24 hours.');
            $values['status'] = 'published'; $values['inherited'] = false;
            et_exam_store($database,$values,$raw,(int)$user['id'],'exam_cycle_published');
            et_flash('success','This year is now published. Result searches and school lists use this source.');
        } elseif ($op === 'pause' || $op === 'archive') {
            if (!$item) throw new RuntimeException('Save or check this cycle first.');
            $values['status'] = $op === 'pause' ? 'suspended' : 'archived';
            et_exam_store($database,$values,$raw,(int)$user['id'],'exam_cycle_' . $op);
            et_flash('success','This year is now hidden from public search.');
        } elseif ($op === 'advanced') {
            foreach (['base_url','school_path','directory_path','school_case','school_format'] as $field) $values[$field] = trim((string)($_POST[$field] ?? ''));
            $values = et_exam_prepare_change($item,$values);
            et_exam_store($database,$values,$raw,(int)$user['id'],'exam_cycle_saved');
            et_flash('success','Settings saved. Click Check source before publishing the changes.');
        } elseif ($op === 'restore') {
            $revision = filter_var($_POST['history_revision'] ?? '',FILTER_VALIDATE_INT);
            if (!$item || $revision === false || $revision < 0) throw new RuntimeException('Choose a saved configuration.');
            $q = $database->prepare('SELECT setting_value FROM app_settings WHERE setting_key=?');
            $q->execute(['exam_history_' . $level . '_' . $year . '_' . $revision]); $snapshot = $q->fetchColumn();
            if ($snapshot === false) throw new RuntimeException('The saved configuration was not found.');
            $values = et_exam_prepare_change(null,json_decode($snapshot,true,512,JSON_THROW_ON_ERROR));
            et_exam_store($database,$values,$raw,(int)$user['id'],'exam_cycle_restored');
            et_flash('success','The configuration was restored as a draft. Check the source before publishing.');
        } else throw new RuntimeException('Invalid action.');
        et_redirect('sources.php?section=exams&level=' . $level . '&year=' . $year);
    } catch (Throwable $e) {
        if ($database->inTransaction()) $database->rollBack();
        $error = $e instanceof PDOException ? 'Save or check this cycle first. Reload ukurasa kisha jaribu tena.' : $e->getMessage();
    }
}
$cycles = et_exam_cycles(null,$database,false);
$successes = $database->query("SELECT setting_key,setting_value FROM app_settings WHERE setting_key LIKE 'exam_success_%'")->fetchAll(PDO::FETCH_KEY_PAIR);
$sourceEvents = $database->query("SELECT exam_type,target,SUM(occurrences) AS failures,MAX(last_seen_at) AS last_failure FROM system_events WHERE status='open' AND event_type IN ('upstream_not_found','upstream_request_failed','upstream_redirect') GROUP BY exam_type,target")->fetchAll();
$stats = []; $summary = ['healthy'=>0,'ready'=>0,'issue'=>0,'pending'=>0,'paused'=>0];
foreach ($cycles as $id=>$cycle) {
    $stats[$id] = ['failures'=>0,'last_failure'=>null]; $base = preg_replace('#^https://#','',$cycle['base_url']);
    foreach ($sourceEvents as $event) if ($event['exam_type'] === strtoupper($cycle['level']) && str_starts_with($event['target'],$base)) {
        if (($cycle['last_check_ok'] ?? false) && ($cycle['last_check_at'] ?? '') >= $event['last_failure']) continue;
        $stats[$id]['failures'] += (int)$event['failures'];
        $stats[$id]['last_failure'] = max($stats[$id]['last_failure'] ?? '',$event['last_failure']);
    }
    $summary[et_exam_health($cycle,$stats[$id]['failures'])['key']]++;
}
$selectedStats = $stats[$level . '-' . $year] ?? ['failures'=>0,'last_failure'=>null];
$health = et_exam_health($values,$selectedStats['failures']);
$q = $database->prepare('SELECT setting_value,updated_at FROM app_settings WHERE setting_key LIKE ? ORDER BY updated_at DESC LIMIT 10');
$q->execute(['exam_history_' . $level . '_' . $year . '_%']); $history = $q->fetchAll();
$csrf = et_csrf_token(); $revisionToken = hash('sha256',$raw ?? '');
et_admin_header('Examination monitoring',$user,'sources');
?>
<link rel="stylesheet" href="assets/exam-monitoring.css">
<script src="assets/exam-monitoring.js" defer></script>
<div class="admin-page-heading"><div><h1>Examination monitoring</h1><p>Choose a year, check its source, then publish. The system chooses a sample school for you.</p></div><a class="admin-button secondary" href="sources.php">Result Pages</a></div>
<?php if ($error !== ''): ?><div class="admin-alert error" role="alert"><?= et_e($error) ?></div><?php endif; ?>
<div class="stat-grid exam-monitor-stats">
<?php foreach (['healthy'=>'Healthy','ready'=>'Ready to publish','issue'=>'Needs attention','pending'=>'Check pending'] as $state=>$label): ?><div class="stat-card"><small><?= $label ?></small><strong><?= $summary[$state] ?></strong></div><?php endforeach; ?>
</div>
<section class="admin-card exam-monitor-card">
    <form method="get" action="sources.php" class="exam-cycle-picker"><input type="hidden" name="section" value="exams">
        <label class="form-field">Examination<select name="level"><?php foreach (ET_EXAM_LEVELS as $exam): ?><option value="<?= $exam ?>"<?= $level === $exam ? ' selected' : '' ?>><?= strtoupper($exam) ?></option><?php endforeach; ?></select></label>
        <label class="form-field">Year<input type="number" name="year" min="2010" max="<?= (int)date('Y') ?>" value="<?= $year ?>" required></label><button class="admin-button secondary" type="submit">Open cycle</button>
    </form>
</section>
<section class="admin-card exam-monitor-card">
    <div class="exam-health-heading"><div><h2><?= strtoupper($level) ?> <?= $year ?></h2><p><?= et_e($health['hint']) ?></p></div><span class="exam-health exam-health-<?= $health['key'] ?>"><?= $health['label'] ?></span></div>
    <dl class="exam-monitor-details"><div><dt>Public status</dt><dd><?= ucfirst($values['status']) ?></dd></div><div><dt>Last check</dt><dd><?= et_e(et_admin_datetime($values['last_check_at'] ?? null)) ?></dd></div><div><dt>Last live response</dt><dd><?= et_e(et_admin_datetime($successes['exam_success_' . $level . '_' . $year] ?? null)) ?></dd></div><div><dt>Source failures</dt><dd><?= $selectedStats['failures'] ?></dd></div></dl>
    <?php if (($values['last_check_ok'] ?? null) === false): ?><div class="admin-alert error"><strong>Source check failed · <?= ($values['last_check_provider'] ?? '') === 'tetea' ? 'Maktaba / TETEA' : 'NECTA' ?></strong><p><?= et_e(et_exam_display_report($values['verification_report'] ?? '')) ?></p><?php if ($values['status'] === 'published'): ?><p>Public search still uses the saved configuration. This failed check did not change its source.</p><?php endif; ?><a href="monitoring/">View Traffic & Errors</a></div><?php elseif (!empty($values['verified_at'])): ?><p class="exam-check-note">The directory and one sample school passed the check. This does not check every school.</p><?php endif; ?>
    <?php if ($owner): ?>
    <form method="post" class="exam-check-form" data-exam-check><input type="hidden" name="csrf_token" value="<?= et_e($csrf) ?>"><input type="hidden" name="revision" value="<?= et_e($revisionToken) ?>"><input type="hidden" name="operation" value="check">
        <label class="form-field">Source provider<select name="provider"><option value="necta"<?= et_exam_provider($values) === 'necta' ? ' selected' : '' ?>>NECTA</option><option value="tetea"<?= et_exam_provider($values) === 'tetea' ? ' selected' : '' ?>>Maktaba / TETEA</option></select></label><button class="admin-button" type="submit">Check source</button><p data-exam-progress role="status" hidden>Checking the source. Looking for a directory and a sample school. Please wait.</p>
    </form>
    <div class="exam-cycle-actions">
    <?php if (($values['last_check_ok'] ?? false) && !empty($values['verified_at']) && $values['status'] !== 'published'): ?><form method="post"><input type="hidden" name="csrf_token" value="<?= et_e($csrf) ?>"><input type="hidden" name="revision" value="<?= et_e($revisionToken) ?>"><input type="hidden" name="operation" value="publish"><button class="admin-button" type="submit">Publish cycle</button></form><?php endif; ?>
    <?php if ($item): ?><form method="post" data-confirm="Ondoa cycle hii kwenye public search kwa muda?"><input type="hidden" name="csrf_token" value="<?= et_e($csrf) ?>"><input type="hidden" name="revision" value="<?= et_e($revisionToken) ?>"><input type="hidden" name="operation" value="pause"><button class="admin-button secondary" type="submit">Pause cycle</button></form><?php endif; ?>
    </div>
    <?php else: ?><p>Owners pekee wanaweza kufanya ukaguzi au kubadilisha cycle.</p><?php endif; ?>
    <details class="exam-advanced"<?= $error !== '' ? ' open' : '' ?>><summary>Advanced settings &amp; history</summary><p>Use these settings for unusual source paths. For normal checks, use Check source above.</p>
    <form method="post" class="admin-form"><input type="hidden" name="csrf_token" value="<?= et_e($csrf) ?>"><input type="hidden" name="revision" value="<?= et_e($revisionToken) ?>"><input type="hidden" name="operation" value="advanced"><fieldset class="exam-advanced-fields"<?= $owner ? '' : ' disabled' ?>>
    <?php foreach (['base_url'=>'Source base URL','school_path'=>'School path','directory_path'=>'Directory path'] as $field=>$label): ?><label class="form-field"><?= $label ?><input name="<?= $field ?>" value="<?= et_e($values[$field]) ?>" required></label><?php endforeach; ?>
    <label class="form-field">Filename case<select name="school_case"><option value="lower"<?= $values['school_case'] === 'lower' ? ' selected' : '' ?>>Lowercase</option><option value="upper"<?= $values['school_case'] === 'upper' ? ' selected' : '' ?>>Uppercase</option></select></label>
    <label class="form-field">School code format<select name="school_format"><option value="standard"<?= $values['school_format'] === 'standard' ? ' selected' : '' ?>>Standard</option><option value="primary_p"<?= $values['school_format'] === 'primary_p' ? ' selected' : '' ?>>Legacy PSLE</option></select></label><button class="admin-button secondary" type="submit">Save advanced settings</button></fieldset></form>
    <?php if ($item && $owner): ?><form method="post" class="exam-cycle-actions" data-confirm="Archive cycle hii na kuiondoa kwenye public search?"><input type="hidden" name="csrf_token" value="<?= et_e($csrf) ?>"><input type="hidden" name="revision" value="<?= et_e($revisionToken) ?>"><input type="hidden" name="operation" value="archive"><button class="admin-button danger" type="submit">Archive cycle</button></form><?php endif; ?>
    <?php if ($history): ?><h3>Previous configurations</h3><div class="table-wrap"><table class="admin-table"><thead><tr><th>Saved</th><th>Provider</th><th>Action</th></tr></thead><tbody><?php foreach ($history as $snapshot): $old = json_decode($snapshot['setting_value'],true); ?><tr><td><?= et_e(et_admin_datetime($snapshot['updated_at'])) ?></td><td><?= et_exam_provider($old) === 'tetea' ? 'Maktaba / TETEA' : 'NECTA' ?></td><td><?php if ($owner): ?><form method="post"><input type="hidden" name="csrf_token" value="<?= et_e($csrf) ?>"><input type="hidden" name="revision" value="<?= et_e($revisionToken) ?>"><input type="hidden" name="history_revision" value="<?= (int)($old['revision'] ?? 0) ?>"><input type="hidden" name="operation" value="restore"><button class="admin-button secondary small" type="submit">Restore as Draft</button></form><?php endif; ?></td></tr><?php endforeach; ?></tbody></table></div><?php endif; ?>
    </details>
</section>
<section class="admin-card exam-monitor-card"><h2><?= strtoupper($level) ?> cycles</h2><p>Review the years for this examination. Previous errors remain available in Traffic & Errors.</p><div class="table-wrap"><table class="admin-table"><thead><tr><th>Year</th><th>Provider</th><th>Public status</th><th>Source health</th><th>Last check</th><th>Action</th></tr></thead><tbody>
<?php foreach ($cycles as $id=>$cycle): if ($cycle['level'] !== $level) continue; $state = et_exam_health($cycle,$stats[$id]['failures']); ?><tr><td><?= $cycle['year'] ?></td><td><?= et_exam_provider($cycle) === 'tetea' ? 'Maktaba / TETEA' : 'NECTA' ?></td><td><?= ucfirst($cycle['status']) ?></td><td><span class="exam-health exam-health-<?= $state['key'] ?>"><?= $state['label'] ?></span></td><td><?= et_e(et_admin_datetime($cycle['last_check_at'] ?? null)) ?></td><td><a class="admin-button secondary small" href="sources.php?section=exams&amp;level=<?= $level ?>&amp;year=<?= $cycle['year'] ?>">Review</a></td></tr><?php endforeach; ?>
</tbody></table></div><p>Manual checks run when you click Check source. Background sample checks are managed in Notifications &amp; Checks. The system does not switch providers automatically.</p></section>
<?php et_admin_footer(); ?>
