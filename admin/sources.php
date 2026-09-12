<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/admin_auth.php';
require_once __DIR__ . '/_layout.php';
et_admin_boot();
$user = et_require_admin_at_root();

$levels = [
    ['ACSEE', '../acsee/', 'Candidate search', ['onlinesys.necta.go.tz', 'maktaba.tetea.org']],
    ['CSEE', '../csee/', 'Candidate search', ['onlinesys.necta.go.tz', 'maktaba.tetea.org']],
    ['FTNA', '../ftna/', 'Candidate search', ['onlinesys.necta.go.tz']],
    ['PSLE', '../psle/', 'Candidate and school search', ['onlinesys.necta.go.tz']],
    ['SFNA', '../sfna/', 'Candidate and school search', ['onlinesys.necta.go.tz']],
];
et_admin_header('Result Sources', $user, 'sources');
?>
<div class="admin-page-heading"><div><p>Muhtasari wa flows zilizopo. URLs za upstream zinadhibitiwa na allow-list kwenye code.</p></div></div>
<div class="table-wrap"><table class="admin-table"><thead><tr><th>Level</th><th>Flow</th><th>Allowed sources</th><th>Local status</th></tr></thead><tbody>
<?php foreach ($levels as [$level, $path, $flow, $hosts]): $exists = is_file(dirname(__DIR__) . '/' . strtolower($level) . '/index.html'); ?><tr><td><strong><?= et_e($level) ?></strong></td><td><?= et_e($flow) ?></td><td><?= et_e(implode(', ', $hosts)) ?></td><td><span class="status-badge <?= $exists ? 'status-published' : 'status-spam' ?>"><?= $exists ? 'Configured' : 'Missing' ?></span> <a href="<?= et_e($path) ?>" target="_blank" rel="noopener">Open ↗</a></td></tr><?php endforeach; ?>
</tbody></table></div>
<section class="admin-card" style="margin-top:18px"><h2>Security boundary</h2><p>Admin hii haikubali arbitrary scraping URL. Kubadilisha allow-list ya source ni deployment action inayohitaji code review.</p></section>
<?php et_admin_footer(); ?>
