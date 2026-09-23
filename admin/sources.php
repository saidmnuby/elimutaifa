<?php
declare(strict_types=1);

if (in_array($_GET['section'] ?? '', ['form-one','form-five'], true)) {
    define('ET_CYCLES_EMBEDDED', true);
    require __DIR__ . '/form-one/index.php';
    return;
}

require_once dirname(__DIR__) . '/includes/admin_auth.php';
require_once __DIR__ . '/_layout.php';
et_admin_boot();
$user = et_require_admin_at_root();

$levels = [
    ['ACSEE', '../results/acsee/', 'Candidate search', ['onlinesys.necta.go.tz', 'maktaba.tetea.org']],
    ['CSEE', '../results/csee/', 'Candidate search', ['onlinesys.necta.go.tz', 'maktaba.tetea.org']],
    ['FTNA', '../results/ftna/', 'Candidate search', ['onlinesys.necta.go.tz']],
    ['PSLE', '../results/psle/', 'Candidate and school search', ['onlinesys.necta.go.tz']],
    ['SFNA', '../results/sfna/', 'Candidate and school search', ['onlinesys.necta.go.tz']],
    ['Form One', '../selection/form-one/', 'Candidate selection and primary-school browsing', ['selection.tamisemi.go.tz']],
    ['Form Five / Colleges', '../selection/form-five/', 'School browsing (stage one)', ['selform.tamisemi.go.tz']],
];
et_admin_header('Result Pages', $user, 'sources');
?>
<div class="admin-page-heading"><div><p>Available result and selection services. The code allows only approved source URLs.</p></div></div>
<section class="admin-card" style="margin-bottom:18px"><h2>Form One selection</h2><p>Manage the intake year, PSLE year, selection round, source and default cycle.</p><a class="admin-button" href="sources.php?section=form-one">Manage cycles</a></section>
<section class="admin-card" style="margin-bottom:18px"><h2>Form Five / Colleges</h2><p>Manage the CSEE year, selection round, source and default cycle.</p><a class="admin-button" href="sources.php?section=form-five">Manage cycles</a></section>
<div class="table-wrap"><table class="admin-table"><thead><tr><th>Level</th><th>Flow</th><th>Allowed sources</th><th>Local status</th></tr></thead><tbody>
<?php foreach ($levels as [$level, $path, $flow, $hosts]): $exists = (is_file(__DIR__ . '/' . $path . 'index.html') || is_file(__DIR__ . '/' . $path . 'index.php')); ?><tr><td><strong><?= et_e($level) ?></strong></td><td><?= et_e($flow) ?></td><td><?= et_e(implode(', ', $hosts)) ?></td><td><span class="status-badge <?= $exists ? 'status-published' : 'status-spam' ?>"><?= $exists ? 'Configured' : 'Missing' ?></span> <a href="<?= et_e($path) ?>" target="_blank" rel="noopener">Open ↗</a></td></tr><?php endforeach; ?>
</tbody></table></div>
<section class="admin-card" style="margin-top:18px"><h2>Security boundary</h2><p>Admins can use only approved data sources. Changing the list of allowed sources requires a developer review and deployment.</p></section>
<?php et_admin_footer(); ?>
