<?php
declare(strict_types=1);
require_once dirname(__DIR__,2).'/includes/admin_auth.php';
require_once dirname(__DIR__,2).'/includes/placements.php';
require_once dirname(__DIR__).'/_layout.php';
et_admin_boot();$user=et_require_admin();$database=et_db();
$id=filter_input(INPUT_GET,'id',FILTER_VALIDATE_INT)?:0;
$q=$database->prepare('SELECT * FROM placement_items WHERE id=?');$q->execute([$id]);$item=$q->fetch();
if(!$item){http_response_code(404);exit('Placement not found');}
$script=str_replace('\\','/',(string)$_SERVER['SCRIPT_NAME']);$pos=strpos($script,'/admin/');$base=$pos===false?'':substr($script,0,$pos);
$entry=et_placement_entry($database,$item,$base);
et_admin_header('Placement preview',$user,'placements','../');
?>
<p>Preview ya placement iliyohifadhiwa; hapa ratiba/status hazizuii preview. Internal destination lazima iwe article iliyochapishwa. <a href="edit.php?id=<?= $id ?>">Hariri</a></p>
<?php if(!$entry): ?><p>Destination haipatikani au article haijachapishwa. Preview haijaonyeshwa.</p><?php else: ?>
<div data-et-placement-slot="<?= et_e($item['slot']) ?>" data-et-placement-page="home" hidden></div>
<script type="application/json" id="etPlacementPreview"><?= json_encode(['items'=>[$entry]],JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_THROW_ON_ERROR) ?></script>
<script src="../../assets/js/placements.js" defer></script>
<?php endif; et_admin_footer(); ?>
