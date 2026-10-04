<?php
declare(strict_types=1);
require_once dirname(__DIR__,2).'/includes/admin_auth.php';
require_once dirname(__DIR__,2).'/includes/ad_networks.php';
require_once dirname(__DIR__).'/_layout.php';
et_admin_boot(); $user=et_require_admin(); $db=et_db(); $main=et_email_main_owner($db,(int)$user['id']); $error='';
$units=et_ad_units($db); $id=(string)($_GET['id'] ?? '');
$values=$units[$id] ?? ['name'=>'','provider'=>'adsense','slot'=>'bottom','status'=>'draft','publisher'=>'','unit_code'=>'','script_url'=>'','targets'=>['home'],'priority'=>0,'starts_at'=>null,'ends_at'=>null,'consent_ready'=>false];
if($_SERVER['REQUEST_METHOD']==='POST') {
    try {
        if(!$main) throw new RuntimeException('Only the main owner can configure network ads.');
        if(!et_verify_csrf($_POST['csrf_token'] ?? null)) throw new RuntimeException('This request has expired. Reload the page.');
        $unit=et_ad_validate($_POST);
        if($unit['status']==='published') foreach($units as $existingId=>$existing) {
            if($existingId===$id || $existing['status']!=='published' || $existing['provider']!==$unit['provider']) continue;
            if($existing['unit_code']===$unit['unit_code']) throw new RuntimeException('This ad unit is already published. Edit its display pages instead of publishing a duplicate.');
            if($unit['provider']==='adsense' && $existing['publisher']!==$unit['publisher']) throw new RuntimeException('Use the same AdSense publisher account for published units.');
        }
        if($id!=='' && !isset($units[$id])) throw new RuntimeException('Ad unit not found.');
        $id=$id ?: bin2hex(random_bytes(12)); $unit['id']=$id; $unit['updated_at']=et_utc_now();
        et_email_save($db,'ad_unit_'.$id,json_encode($unit,JSON_THROW_ON_ERROR));
        et_audit((int)$user['id'],'network_ad_saved','ad_unit',null,$unit['provider'].' '.$unit['name']);
        et_flash('success','Ad unit saved. Publishing follows your schedule and display pages.'); et_redirect('networks.php');
    } catch(Throwable $e) { $values=array_replace($values,$_POST); $error=$e instanceof PDOException?'Could not save this ad unit.':$e->getMessage(); }
}
$stats=et_ad_stats($db);
et_admin_header('Sponsors & Ads',$user,'placements','../');
?>
<link rel="stylesheet" href="../assets/sponsors.css?v=20261004.2">
<div class="admin-page-heading"><p>Manage approved network ad units without editing public page code.</p><a class="admin-button secondary" href="./">Direct sponsors</a></div>
<div class="sponsor-overview"><div>Configured ad units<strong><?= count($units) ?></strong><small>AdSense and Adsterra</small></div><div>Published units<strong><?= count(array_filter($units,static fn($unit)=>$unit['status']==='published')) ?></strong><small>Delivery also follows targeting and schedule</small></div><div>Script failures · 7 days<strong><?= array_sum(array_map(static fn($s)=>(int)($s['failed'] ?? 0),$stats)) ?></strong><small>Local signals, not provider reports</small></div></div>
<div class="admin-alert">Local metrics show placement delivery and script health. Provider impressions, clicks and earnings are available in <a href="https://www.google.com/adsense/" target="_blank" rel="noopener">AdSense</a> or <a href="https://publishers.adsterra.com/" target="_blank" rel="noopener">Adsterra</a>. Provider report APIs are not connected.</div>
<section class="admin-card"><h2>Network ad units</h2><div class="table-wrap"><table class="admin-table"><thead><tr><th>Ad unit</th><th>Display</th><th>Status</th><th>Local delivery · last 7 days</th><th>Actions</th></tr></thead><tbody>
<?php if(!$units): ?><tr><td colspan="5">No network ad units yet. Add your approved provider details below. Existing direct sponsors keep working.</td></tr><?php endif; ?>
<?php foreach($units as $unit): $s=$stats[$unit['id']] ?? []; ?><tr><td><strong><?= et_e($unit['name']) ?></strong><small><?= $unit['provider']==='adsense'?'Google AdSense':'Adsterra Native Banner' ?></small></td><td><?= et_e(ucfirst($unit['slot'])) ?><small><?= et_e(implode(', ',$unit['targets'])) ?></small></td><td><?= et_e(ucfirst($unit['status'])) ?><small>Start: <?= et_e(et_admin_datetime($unit['starts_at'])) ?></small><small>End: <?= et_e(et_admin_datetime($unit['ends_at'])) ?></small></td><td>Mounted: <?= (int)($s['mounted'] ?? 0) ?><small>Script ready: <?= (int)($s['loaded'] ?? 0) ?> · Failed: <?= (int)($s['failed'] ?? 0) ?></small></td><td><?php if($main): ?><a href="?id=<?= et_e($unit['id']) ?>">Edit / pause</a><?php else: ?>Main owner manages setup<?php endif; ?></td></tr><?php endforeach; ?>
</tbody></table></div><p>Signals are anonymous estimates, deduplicated per browser/network identity and event each hour. They are not ad impressions. Ad blockers and Do Not Track can reduce counts. Publishing does not prove provider approval or fill.</p></section>
<?php if($main): ?>
<?php if($error): ?><div class="admin-alert error"><?= et_e($error) ?></div><?php endif; ?>
<details class="sponsor-setup"<?= $id || $error?' open':'' ?>><summary><?= $id?'Edit network ad unit':'+ Add network ad unit' ?></summary>
<form method="post" class="admin-form"><input type="hidden" name="csrf_token" value="<?= et_e(et_csrf_token()) ?>">
<section class="form-section"><h2><?= $id?'Edit network ad unit':'Add network ad unit' ?></h2><p>Supported formats: responsive AdSense display ads and Adsterra Native Banner. Popunders, arbitrary HTML and network pop-ups are not used here.</p><div class="form-grid">
<div class="form-field"><label>Name<input name="name" maxlength="140" value="<?= et_e($values['name']) ?>" required></label></div>
<div class="form-field"><label>Provider<select name="provider" id="adProvider"><option value="adsense"<?= $values['provider']==='adsense'?' selected':'' ?>>Google AdSense</option><option value="adsterra"<?= $values['provider']==='adsterra'?' selected':'' ?>>Adsterra Native Banner</option></select></label></div>
<div class="form-field" data-provider="adsense"><label>Publisher ID<input name="publisher" placeholder="ca-pub-…" value="<?= et_e($values['publisher']) ?>"></label><small>Copy data-ad-client from your approved ad code.</small></div>
<div class="form-field"><label>Ad slot / Native Banner key<input name="unit_code" value="<?= et_e($values['unit_code']) ?>"></label><small>AdSense: data-ad-slot. Adsterra: the 32-character key in container-key.</small></div>
<div class="form-field full" data-provider="adsterra"><label>Native Banner script URL<input name="script_url" type="url" value="<?= et_e($values['script_url']) ?>"></label><small>Exact HTTPS URL ending in /your-key/invoke.js from your provider dashboard. No pasted scripts.</small></div>
<div class="form-field"><label>Position<select name="slot"><option value="top"<?= $values['slot']==='top'?' selected':'' ?>>Above content</option><option value="bottom"<?= $values['slot']==='bottom'?' selected':'' ?>>Below content</option></select></label></div>
<div class="form-field"><label>Priority<input name="priority" type="number" min="0" max="100" value="<?= (int)$values['priority'] ?>"></label><small>Shares the existing slot limits with direct sponsors. Higher priority wins.</small></div>
<div class="form-field"><label>Status<select name="status"><?php foreach(['draft','published','archived'] as $status): ?><option value="<?= $status ?>"<?= $values['status']===$status?' selected':'' ?>><?= ucfirst($status) ?></option><?php endforeach; ?></select></label><small>Choose Archived to pause delivery.</small></div>
<?php foreach(['starts_at'=>'Start (EAT)','ends_at'=>'End (EAT)'] as $field=>$label): ?><div class="form-field"><label><?= $label ?><input name="<?= $field ?>" type="datetime-local" value="<?= et_e($_SERVER['REQUEST_METHOD']==='POST' ? ($values[$field] ?? '') : et_utc_datetime_to_local($values[$field])) ?>"></label></div><?php endforeach; ?>
<fieldset class="form-field full placement-targets"><legend>Display pages</legend><div class="placement-target-grid"><?php foreach(et_placement_targets() as $key=>$label): ?><label><input type="checkbox" name="targets[]" value="<?= et_e($key) ?>"<?= in_array($key,is_array($values['targets'])?$values['targets']:[],true)?' checked':'' ?>><?= et_e(et_admin_label($label)) ?></label><?php endforeach; ?></div></fieldset>
<div class="form-field full sponsor-approval"><label class="sponsor-approval-choice" for="adConsentReady"><input id="adConsentReady" type="checkbox" name="consent_ready" aria-describedby="adConsentHelp"<?= !empty($values['consent_ready'])?' checked':'' ?>><span><strong>Ready to publish ads</strong><span>My provider has approved this site and ad unit. I have completed the required privacy and consent setup.</span></span></label><small id="adConsentHelp">Check this only when setup is complete. You can save a draft without checking it. This does not verify approval with your provider.</small></div>
</div><button class="admin-button" type="submit">Save ad unit</button> <a href="networks.php">New ad unit</a></section></form></details>
<script>document.addEventListener('DOMContentLoaded',function(){const select=document.getElementById('adProvider');function update(){document.querySelectorAll('[data-provider]').forEach(function(field){field.hidden=field.dataset.provider!==select.value;field.querySelectorAll('input').forEach(function(input){input.disabled=field.hidden;});});}select.addEventListener('change',update);update();});</script>
<?php endif; et_admin_footer(); ?>
