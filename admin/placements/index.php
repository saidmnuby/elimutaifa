<?php
declare(strict_types=1);
require_once dirname(__DIR__,2).'/includes/admin_auth.php';
require_once dirname(__DIR__,2).'/includes/placements.php';
require_once dirname(__DIR__).'/_layout.php';
et_admin_boot(); $user=et_require_admin(); $database=et_db();
$page=max(1,filter_input(INPUT_GET,'page',FILTER_VALIDATE_INT)?:1);
$status=(string)($_GET['status']??'');
$where=in_array($status,['draft','published','archived'],true)?' WHERE status=?':'';
$params=$where!==''?[$status]:[];
$count=$database->prepare('SELECT COUNT(*) FROM placement_items'.$where); $count->execute($params);
$total=(int)$count->fetchColumn(); $pages=max(1,(int)ceil($total/20)); $page=min($page,$pages);
$query=$database->prepare('SELECT * FROM placement_items'.$where.' ORDER BY updated_at DESC,id DESC LIMIT 20 OFFSET '.(($page-1)*20));
$query->execute($params); $items=$query->fetchAll();
et_admin_header('Banners & placements',$user,'placements','../');
?>
<div class="admin-page-heading"><p>Matangazo ya mfumo na sponsors: chagua mahali, muonekano na ratiba. Rekodi <?= $total ?>.</p><a class="admin-button" href="edit.php">+ Ongeza placement</a></div>
<form method="get" class="form-section"><label>Status <select name="status"><option value="">Zote</option><?php foreach(['draft','published','archived'] as $option): ?><option<?= $option===$status?' selected':'' ?>><?= et_e($option) ?></option><?php endforeach; ?></select></label><button class="admin-button secondary small">Chuja</button></form>
<div class="table-wrap"><table class="admin-table"><thead><tr><th>Tangazo</th><th>Placement / targets</th><th>Status / ratiba (EAT)</th><th>Vitendo</th></tr></thead><tbody>
<?php if(!$items): ?><tr><td colspan="4">Hakuna placements.</td></tr><?php endif; ?>
<?php foreach($items as $item): ?><tr><td><strong><?= et_e($item['title']) ?></strong><small><?= et_e($item['kind']==='sponsor'?'Sponsor: '.$item['sponsor_name']:'Tangazo la mfumo') ?></small></td><td><?= et_e($item['format'].' / '.$item['slot']) ?><small><?= et_e(implode(', ',json_decode($item['targets'],true)?:[])) ?></small><small>Priority <?= (int)$item['priority'] ?></small></td><td><?= et_e($item['status']) ?><small>Start: <?= et_e(et_admin_datetime($item['starts_at'])) ?></small><small>End: <?= et_e(et_admin_datetime($item['ends_at'])) ?></small></td><td><a class="admin-button secondary small" href="edit.php?id=<?= (int)$item['id'] ?>">Hariri</a> <a href="preview.php?id=<?= (int)$item['id'] ?>">Preview</a></td></tr><?php endforeach; ?>
</tbody></table></div>
<?php et_admin_pagination($page,$pages,['status'=>$status],'Placement pages'); et_admin_footer(); ?>
