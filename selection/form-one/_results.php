<?php if ($action === 'candidate'): $row = $records[0]; ?>
<section class="card fo-result" aria-labelledby="fo-result-title">
    <div class="fo-result-header"><span class="fo-result-status">Selection imepatikana</span><h2 id="fo-result-title"><?= fo_e($row['candidate']) ?></h2><p>Kidato cha Kwanza · Intake <?= fo_e($cycleInfo['intake_year']) ?> · <?= fo_e($cycleInfo['round_label']) ?></p></div>
    <div class="fo-destination"><span>AMECHAGULIWA KUJIUNGA</span><h3><?= fo_e($row['destination']) ?></h3></div>
    <dl class="fo-result-details">
        <div><dt>Shule ya msingi</dt><dd><?= fo_e($heading) ?></dd></div>
        <div><dt>Jinsi</dt><dd><?= fo_e(match(strtoupper($row['sex'])) { 'KE','FEMALE','F'=>'Mwanamke', 'ME','MALE','M'=>'Mwanaume', default=>$row['sex'] }) ?></dd></div>
        <div><dt>Aina ya shule</dt><dd><?= fo_e($row['type']) ?></dd></div>
        <div><dt>Halmashauri ya sekondari</dt><dd><?= fo_e($row['council']) ?></dd></div>
    </dl>
    <div class="fo-result-joining"><h3>Maelekezo ya kujiunga</h3><?php if ($row['joining_url']): ?><a class="fo-joining-button" href="<?= fo_e($row['joining_url']) ?>" target="_blank" rel="noopener noreferrer">Fungua joining instructions (PDF) ↗</a><?php else: ?><p>Source haijaweka link ya joining instructions kwa selection hii.</p><?php endif; ?></div>
</section>
<?php else: ?>
<section class="school-search selection-student-search"><label class="form-label" for="selection-student-search">Tafuta mwanafunzi kwa namba ya PSLE</label><input class="form-input" type="search" id="selection-student-search" placeholder="Andika namba au sehemu yake…" autocomplete="off" aria-controls="selection-student-table"><p id="selection-student-count" role="status" aria-live="polite"></p></section>
<section class="card fo-table selection-student-list" tabindex="0" role="region" aria-label="Selections za shule; orodha inayoscroll"><div class="fo-results-heading"><h2>Selections za shule</h2><span><?= count($records) ?> wanafunzi</span></div>
<table id="selection-student-table"><caption class="fo-visually-hidden">Shule walizopangiwa wanafunzi wa <?= fo_e($heading) ?></caption><thead><tr><th>Namba ya PSLE</th><th>Shule aliyopangiwa</th><th>Aina / Halmashauri</th><th>Joining instructions</th></tr></thead><tbody>
<?php foreach ($records as $row): ?><tr data-selection-student="<?= fo_e($row['candidate']) ?>">
    <td data-label="Namba ya PSLE"><strong><?= fo_e($row['candidate']) ?></strong><small>Jinsi: <?= fo_e($row['sex']) ?></small></td>
    <td data-label="Shule aliyopangiwa"><?= fo_e($row['destination']) ?></td>
    <td data-label="Aina / Halmashauri"><?= fo_e($row['type']) ?><small><?= fo_e($row['council']) ?></small></td>
    <td data-label="Joining instructions"><?php if ($row['joining_url']): ?><a href="<?= fo_e($row['joining_url']) ?>" target="_blank" rel="noopener noreferrer">Fungua PDF ↗</a><?php else: ?><span class="fo-unavailable">Link haipo kwenye source</span><?php endif; ?></td>
</tr><?php endforeach; ?>
</tbody></table></section>
<p id="selection-student-empty" hidden role="status">Hakuna mwanafunzi anayelingana na namba hiyo katika orodha hii.</p>
<script src="../../assets/js/selection-students.js?v=20260916.1" defer></script>
<link rel="stylesheet" href="../../assets/css/selection-students.css?v=20260920.4">
<?php endif; ?>
