<?php
declare(strict_types=1);

if (($_GET['section'] ?? '') === 'exams') {
    require __DIR__ . '/exam-monitoring.php';
    return;
}
if (in_array($_GET['section'] ?? '', ['form-one','form-five'], true)) {
    define('ET_CYCLES_EMBEDDED', true);
    require __DIR__ . '/form-one/index.php';
    return;
}

require_once dirname(__DIR__) . '/includes/admin_auth.php';
require_once __DIR__ . '/_layout.php';
et_admin_boot();
$user = et_require_admin_at_root();

et_admin_header('Result Pages', $user, 'sources');
?>
<div class="admin-page-heading"><div><p>Manage examination years and selection cycles.</p></div></div>
<section class="admin-card" style="margin-bottom:18px">
    <h2>Examination cycles</h2>
    <p>Monitor ACSEE, CSEE, FTNA, PSLE and SFNA sources. Check a provider, review its health and publish verified cycles.</p>
    <a class="admin-button" href="sources.php?section=exams">Open examination monitoring</a>
</section>
<section class="admin-card" style="margin-bottom:18px">
    <h2>Form One cycles</h2>
    <p>Manage PSLE years, intake years and selection rounds. Verify each cycle before publishing and choose the default for visitors.</p>
    <a class="admin-button" href="sources.php?section=form-one">Manage Form One cycles</a>
</section>
<section class="admin-card" style="margin-bottom:18px">
    <h2>Form Five / Colleges cycles</h2>
    <p>Manage CSEE years, intake years and selection rounds. Verify each cycle before publishing and choose the default for visitors.</p>
    <a class="admin-button" href="sources.php?section=form-five">Manage Form Five cycles</a>
</section>
<?php et_admin_footer(); ?>
