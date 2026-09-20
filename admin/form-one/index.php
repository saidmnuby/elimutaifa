<?php
declare(strict_types=1);
if (!defined('ET_CYCLES_EMBEDDED')) {
    header('Location: ../sources.php?section=form-one' . (isset($_GET['id']) ? '&id=' . (int)$_GET['id'] : ''), true, 307);
    exit;
}
require_once dirname(__DIR__,2).'/includes/admin_auth.php';
require_once dirname(__DIR__,2).'/includes/form_one.php';
require_once dirname(__DIR__,2).'/includes/form_five.php';
$isFormFive=($_GET['section']??'')==='form-five';
$cycleModule=$isFormFive?'form-five':'form-one';
$cycleTable=$isFormFive?'form_five_cycles':'form_one_cycles';
$cycleSetting=$isFormFive?'form_five_default_cycle':'form_one_default_cycle';
$cycleAudit=$isFormFive?'form_five':'form_one';
$cycleTitle=$isFormFive?'Form Five / Vyuo vya kati':'Form One';
$examLabel=$isFormFive?'CSEE':'PSLE';
$cycleVerify=$isFormFive?'f5_verify_cycle':'fo_verify_cycle';
$cycleClear=$isFormFive?'f5_clear_cycle_cache':'fo_clear_cycle_cache';
$cycleValidate=$isFormFive?'f5_validate_cycle':'fo_validate_cycle';
$generatedSource=static fn(array $v): string => $isFormFive?f5_generated_source($v['intake_year'],$v['round_label']):fo_generated_source($v['exam_year'],$v['round_label']);
require_once dirname(__DIR__).'/_layout.php';
et_admin_boot();$user=et_require_admin_at_root();$database=et_db();
$id=filter_input(INPUT_GET,'id',FILTER_VALIDATE_INT)?:0;
$q=$database->prepare('SELECT * FROM '.$cycleTable.' WHERE id=?');$q->execute([$id]);$item=$q->fetch();
$values=$item?:['cycle_key'=>'','intake_year'=>(int)date('Y')+1,'exam_year'=>(int)date('Y'),'round_label'=>'First selection','source_url'=>'','status'=>'draft'];
$errors=[];
$sourceMode=$item && $item['source_url']!==$generatedSource($item)?'manual':'auto';
if(($_SERVER['REQUEST_METHOD']??'')==='POST'){
    et_require_owner($user);
    if(!et_verify_csrf($_POST['csrf_token']??null)) {$errors[]='Ombi limeisha muda; refresh page.';}
    else {
        $operation=(string)($_POST['operation']??'save');
        try {
            if($operation==='verify'){
                if(!$item) throw new RuntimeException('Hifadhi Draft kwanza.');
                $report=$cycleVerify($item);
                $database->beginTransaction();
                $q=$database->prepare('SELECT * FROM '.$cycleTable.' WHERE id=?'.($database->getAttribute(PDO::ATTR_DRIVER_NAME)==='mysql'?' FOR UPDATE':''));
                $q->execute([$id]);$current=$q->fetch();
                if(!$current || fo_cycle_changed($item,$current)) throw new RuntimeException('Cycle imebadilishwa wakati wa verification. Jaribu tena.');
                $database->prepare('UPDATE '.$cycleTable.' SET verified_at=?,verified_by=?,verification_report=?,updated_at=? WHERE id=?')->execute([et_utc_now(),$user['id'],$report,et_utc_now(),$id]);
                et_audit((int)$user['id'],$cycleAudit.'_cycle_verified',$cycleAudit.'_cycle',$id,$report);$database->commit();
                et_flash('success','Source verification imepita. Hakiki sample kisha chagua Published.');
            } elseif($operation==='clear-cache'){
                if(!$item) throw new RuntimeException('Cycle haijapatikana.');
                $count=$cycleClear($item['source_url']);
                et_audit((int)$user['id'],$cycleAudit.'_cache_cleared',$cycleAudit.'_cycle',$id,$count.' snapshots removed');
                et_flash('success','Cache snapshots '.$count.' zimesafishwa; search inayofuata itachukua data mpya.');
            } elseif($operation==='save'){
                $exam=filter_var($_POST['exam_year']??'',FILTER_VALIDATE_INT);
                if(trim((string)($_POST['intake_year']??''))==='') $_POST['intake_year']=$exam?$exam+1:'';
                $_POST['cycle_key']=$item?$item['cycle_key']:(string)$_POST['intake_year'].'-'.trim(preg_replace('/[^a-z0-9]+/','-',strtolower((string)($_POST['round_label']??'')))??'','-');
                $values=array_merge($values,$_POST);
                $sourceMode=($_POST['source_mode']??'manual')==='auto'?'auto':'manual';
                [$data,$errors]=$cycleValidate($_POST);
                $values['source_url']=$data['source_url'];
                if(!$errors) {
                    $database->beginTransaction();
                    if($item){
                        $q=$database->prepare('SELECT * FROM '.$cycleTable.' WHERE id=?'.($database->getAttribute(PDO::ATTR_DRIVER_NAME)==='mysql'?' FOR UPDATE':''));
                        $q->execute([$id]);$item=$q->fetch();
                    }
                    $changed=!$item || fo_cycle_changed($item,$data);
                    if($changed && $item && $item['status']==='published') {
                        $data['status']='draft';
                        unset($_POST['make_default']);
                    }
                    if($data['status']==='published' && ($changed || !$item['verified_at'])) throw new RuntimeException('Hifadhi changes kama Draft, hakiki source, ndipo Publish.');
                    if(isset($_POST['make_default']) && $data['status']!=='published') throw new RuntimeException('Default lazima iwe Published.');
                    $data+=['updated_by'=>(int)$user['id'],'updated_at'=>et_utc_now()];
                    if($changed) $data+=['verified_at'=>null,'verified_by'=>null,'verification_report'=>null];
                    if($item){
                        $sets=implode(',',array_map(static fn($key)=>$key.'=:'.$key,array_keys($data)));
                        $data['id']=$id;$database->prepare('UPDATE '.$cycleTable.' SET '.$sets.' WHERE id=:id')->execute($data);
                    }else{
                        $data+=['created_by'=>(int)$user['id'],'created_at'=>et_utc_now()];
                        $keys=array_keys($data);$database->prepare('INSERT INTO '.$cycleTable.'('.implode(',',$keys).') VALUES(:'.implode(',:',$keys).')')->execute($data);$id=(int)$database->lastInsertId();
                    }
                    if(isset($_POST['make_default'])){
                        $database->prepare(et_conflict_sql($database,"INSERT INTO app_settings(setting_key,setting_value,updated_at) VALUES('{$cycleSetting}',:value,:now) ON CONFLICT(setting_key) DO UPDATE SET setting_value=excluded.setting_value,updated_at=excluded.updated_at"))->execute(['value'=>$data['cycle_key'],'now'=>et_utc_now()]);
                    }
                    et_audit((int)$user['id'],$item?$cycleAudit.'_cycle_updated':$cycleAudit.'_cycle_created',$cycleAudit.'_cycle',$id,$data['cycle_key'].' / '.$data['status']);$database->commit();
                    et_flash('success',$changed?'Cycle imehifadhiwa kama '.$data['status'].'. Hakiki source kabla ya Publish.':'Cycle imehifadhiwa.');
                }
            }else throw new RuntimeException('Operation si sahihi.');
            if(!$errors) et_redirect('sources.php?section='.$cycleModule.'&id='.$id);
        }catch(Throwable $error){
            if($database->inTransaction()) $database->rollBack();
            error_log('Form One cycle management: '.$error->getMessage());
            $errors[]=$error instanceof PDOException?'Cycle haijahifadhiwa. Hakiki duplicate cycle key au database.':$error->getMessage();
        }
    }
}
$items=$database->query('SELECT * FROM '.$cycleTable.' ORDER BY intake_year DESC,id DESC LIMIT 100')->fetchAll();
$default=$database->query("SELECT setting_value FROM app_settings WHERE setting_key='{$cycleSetting}'")->fetchColumn();
et_admin_header('Result Pages · '.$cycleTitle,$user,'sources');
?>
<div class="admin-page-heading"><p>Year, round na source management pekee; selections za wanafunzi hazihaririwi hapa.</p><div><a class="admin-button secondary" href="sources.php">← Result Pages</a> <a class="admin-button secondary" href="sources.php?section=<?= et_e($cycleModule) ?>">+ Cycle mpya</a></div></div>
<?php foreach($errors as $error): ?><div class="admin-alert error" role="alert"><?= et_e($error) ?></div><?php endforeach; ?>
<?php if($user['role']==='owner'): ?>
<form method="post" class="admin-form" data-cycle-module="<?= et_e($cycleModule) ?>">
<input type="hidden" name="csrf_token" value="<?= et_e(et_csrf_token()) ?>"><input type="hidden" name="operation" value="save">
<section class="form-section"><h2><?= $item?'Hariri cycle':'Ongeza cycle (Draft kwanza)' ?></h2><div class="form-grid">
<div class="form-field"><label>Mwaka wa <?= et_e($examLabel) ?><input name="exam_year" type="number" min="2010" max="2099" value="<?= et_e($values['exam_year']) ?>" required></label></div>
<div class="form-field"><label>Selection round<select name="round_label" required><?php $rounds=['First selection','Second selection','Third selection']; if(!in_array($values['round_label'],$rounds,true)) $rounds[]=$values['round_label']; foreach($rounds as $round): ?><option<?= $values['round_label']===$round?' selected':'' ?>><?= et_e($round) ?></option><?php endforeach; ?></select></label></div>
<div class="form-field"><label>Status<select name="status"><?php foreach(['draft','published','archived'] as $status): ?><option value="<?= $status ?>"<?= $values['status']===$status?' selected':'' ?>><?= ucfirst($status) ?></option><?php endforeach; ?></select></label></div>
<div class="check-row"><label><input type="checkbox" name="make_default" value="1"> Weka default (Published pekee)</label></div>
</div><p>Source: <span id="cycle-source-preview" style="overflow-wrap:anywhere"><?= et_e($sourceMode==='auto'?$generatedSource($values):$values['source_url']) ?></span></p>
<details<?= $sourceMode==='manual'?' open':'' ?>><summary>Chaguzi za ziada (ikiwa zinahitajika)</summary><div class="form-grid">
<div class="form-field"><label>Intake year tofauti<input name="intake_year" type="number" min="2010" max="2100" value="<?= et_e($item?$values['intake_year']:($_POST['intake_year']??'')) ?>" placeholder="Automatic: exam year + 1"></label><small>Kwa cycle mpya, acha wazi kupata exam year + 1.</small></div>
<div class="form-field"><label>Source URL mode<select name="source_mode" id="cycle-source-mode"><option value="auto"<?= $sourceMode==='auto'?' selected':'' ?>>Automatic</option><option value="manual"<?= $sourceMode==='manual'?' selected':'' ?>>Manual</option></select></label></div>
<div class="form-field full"><label>Official URL<input name="source_url" id="cycle-source-url" maxlength="2048" value="<?= et_e($sourceMode==='auto'?$generatedSource($values):$values['source_url']) ?>"<?= $sourceMode==='auto'?' readonly':'' ?> required></label><small>Hakiki source kabla ya Publish.</small></div>
</div></details><div>
</div><p>Verification: <?= $item && $item['verified_at']?et_e(et_admin_datetime($item['verified_at'])):'Bado' ?></p><?php if($item && $item['verification_report']): ?><p><?= et_e($item['verification_report']) ?></p><?php endif; ?>
<div class="form-actions"><button class="admin-button">Hifadhi</button><?php if($item): ?><a href="../selection/<?= et_e($cycleModule) ?>/" class="admin-button secondary" target="_blank" rel="noopener">Fungua module ↗</a><?php endif; ?></div></section>
</form>
<?php if($item): ?><div class="form-actions" style="margin:14px 0"><?php foreach(['verify'=>'Hakiki source (live sample)','clear-cache'=>'Safisha cache ya cycle'] as $op=>$label): ?><form method="post"><input type="hidden" name="csrf_token" value="<?= et_e(et_csrf_token()) ?>"><button class="admin-button secondary" name="operation" value="<?= $op ?>"><?= $label ?></button></form><?php endforeach; ?></div><?php endif; ?>
<?php else: ?><div class="admin-alert">Owner pekee anabadilisha cycle/source/default. Admin anaweza kuona cycles.</div><?php endif; ?>
<div class="table-wrap"><table class="admin-table"><thead><tr><th>Cycle</th><th>Status</th><th>Verification (EAT)</th><th>Vitendo</th></tr></thead><tbody><?php foreach($items as $row): ?><tr><td><?= et_e($row['intake_year'].' · '.$row['round_label']) ?><small><?= et_e($examLabel) ?> <?= (int)$row['exam_year'] ?> · <?= et_e($row['cycle_key']) ?></small></td><td><?= et_e($row['status']) ?><?= $row['cycle_key']===$default && $row['status']==='published'?' · Default':'' ?></td><td><?= $row['verified_at']?et_e(et_admin_datetime($row['verified_at'])):'Bado' ?></td><td><a href="sources.php?section=<?= et_e($cycleModule) ?>&amp;id=<?= (int)$row['id'] ?>">Fungua</a></td></tr><?php endforeach; ?></tbody></table></div>
<script src="assets/form-one-cycles.js?v=20260916.3"></script>
<?php et_admin_footer(); ?>
