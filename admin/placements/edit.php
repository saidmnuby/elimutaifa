<?php
declare(strict_types=1);
require_once dirname(__DIR__,2).'/includes/admin_auth.php';
require_once dirname(__DIR__,2).'/includes/placements.php';
require_once dirname(__DIR__).'/_layout.php';
et_admin_boot(); $user=et_require_admin(); $database=et_db();
$id=filter_input(INPUT_GET,'id',FILTER_VALIDATE_INT)?:0;
$item=null;
if($id>0) { $q=$database->prepare('SELECT * FROM placement_items WHERE id=?'); $q->execute([$id]); $item=$q->fetch(); if(!$item){http_response_code(404);exit('Placement not found');} }
$values=$item?:['title'=>'','description'=>'','kind'=>'system','sponsor_name'=>'','format'=>'banner','slot'=>'bottom',
    'targets'=>'["home"]','priority'=>0,'image_url'=>'','destination_type'=>'external','external_url'=>'','content_id'=>null,
    'status'=>'draft','starts_at'=>null,'ends_at'=>null,'popup_style'=>'corner','display_mode'=>'session','skip_delay'=>0];
$values['starts_at']=et_utc_datetime_to_local($values['starts_at']); $values['ends_at']=et_utc_datetime_to_local($values['ends_at']);
$errors=[];
if(($_SERVER['REQUEST_METHOD']??'')==='POST') {
    $values=array_merge($values,$_POST);
    // Uploaded paths are preserved server-side; users may supply only HTTPS image URLs.
    $input=$_POST; $image=trim((string)($_POST['image_url']??''));
    if(et_is_managed_content_image($image) && $image!==($item['image_url']??'')) { $errors[]='Upload a file or use an HTTPS link. Do not reuse another article’s uploaded file.'; }
    if(!et_verify_csrf($_POST['csrf_token']??null)) { $errors[]='This request has expired. Reload the page.'; }
    [$data,$validation]=et_validate_placement($input); $errors=array_merge($errors,$validation);
    if($data['destination_type']==='internal' && $data['content_id']) {
        $q=$database->prepare("SELECT id FROM content_items WHERE id=? AND destination_type='internal'"); $q->execute([$data['content_id']]);
        if(!$q->fetchColumn()) { $errors[]='Internal article not found.'; }
    }
    $uploaded=null;
    if(!$errors) { [$uploaded,$uploadError]=et_store_content_image_upload($_FILES['image']??null); if($uploadError){$errors[]=$uploadError;} if($uploaded){$data['image_url']=$uploaded;} }
    if(!$errors) {
        $database->beginTransaction();
        try {
            $data['updated_by']=(int)$user['id']; $data['updated_at']=et_utc_now();
            if($id>0) {
                $sets=implode(',',array_map(static fn($key)=>$key.'=:'.$key,array_keys($data)));
                $data['id']=$id; $database->prepare('UPDATE placement_items SET '.$sets.' WHERE id=:id')->execute($data);
            } else {
                $data['created_by']=(int)$user['id']; $data['created_at']=et_utc_now();
                $keys=array_keys($data); $database->prepare('INSERT INTO placement_items('.implode(',',$keys).') VALUES(:'.implode(',:',$keys).')')->execute($data);
                $id=(int)$database->lastInsertId();
            }
            et_audit((int)$user['id'],$item?'placement_updated':'placement_created','placement_item',$id,$data['title']);
            $database->commit();
            et_flash('success','Placement saved. Its schedule and selected pages control where it appears.'); et_redirect('./');
        } catch(Throwable $exception) {
            if($database->inTransaction()){$database->rollBack();}
            if($uploaded){et_delete_managed_content_image($uploaded);}
            $errors[]='Could not save the placement. Try again.';
        }
    }
    $values['targets']=json_encode(is_array($_POST['targets']??null)?$_POST['targets']:[]);
}
$articles=$database->query("SELECT id,title,status FROM content_items WHERE destination_type='internal' ORDER BY title LIMIT 500")->fetchAll();
$selected=json_decode((string)$values['targets'],true)?:[];
et_admin_header($item?'Edit placement':'Add placement',$user,'placements','../');
?>
<div class="admin-page-heading"><p>Choose the top, bottom or a pop-up. All pages means public pages that support placements.</p><a href="./">Back</a></div>
<?php foreach($errors as $error): ?><div class="admin-alert error" role="alert"><?= et_e($error) ?></div><?php endforeach; ?>
<form method="post" enctype="multipart/form-data" class="admin-form placement-editor"><input type="hidden" name="csrf_token" value="<?= et_e(et_csrf_token()) ?>">
<section class="form-section"><h2>Content and appearance</h2><div class="form-grid">
<div class="form-field full"><label>Title<input name="title" maxlength="140" value="<?= et_e($values['title']) ?>" required></label></div>
<div class="form-field full placement-description"><label>Description<textarea name="description" maxlength="300"><?= et_e($values['description']) ?></textarea></label></div>
<div class="form-field"><label>Type<select name="kind"><option value="system"<?= $values['kind']==='system'?' selected':'' ?>>Platform announcement</option><option value="sponsor"<?= $values['kind']==='sponsor'?' selected':'' ?>>Sponsor</option></select></label></div>
<div class="form-field"><label>Sponsor name<input name="sponsor_name" maxlength="120" value="<?= et_e($values['sponsor_name']) ?>"></label></div>
<div class="form-field"><label>Format<select name="format"><option value="banner"<?= $values['format']==='banner'?' selected':'' ?>>Banner</option><option value="card"<?= $values['format']==='card'?' selected':'' ?>>Card</option></select></label></div>
<div class="form-field"><label>Position<select name="slot"><option value="top"<?= $values['slot']==='top'?' selected':'' ?>>Above content</option><option value="bottom"<?= $values['slot']==='bottom'?' selected':'' ?>>Below content</option><option value="popup"<?= $values['slot']==='popup'?' selected':'' ?>>Pop-up</option></select></label></div>
<div class="form-field"><label>Pop-up style<select name="popup_style"><option value="corner"<?= $values['popup_style']==='corner'?' selected':'' ?>>Small corner pop-up</option><option value="interstitial"<?= $values['popup_style']==='interstitial'?' selected':'' ?>>Centre-screen announcement</option></select></label></div>
<div class="form-field"><label>Pop-up frequency<select name="display_mode"><option value="session"<?= $values['display_mode']==='session'?' selected':'' ?>>Once per session</option><option value="always"<?= $values['display_mode']==='always'?' selected':'' ?>>Every page visit</option><option value="three"<?= $values['display_mode']==='three'?' selected':'' ?>>Three times per browser</option></select></label></div>
<div class="form-field"><label>Wait before Skip is available (0–30 seconds)<input name="skip_delay" type="number" min="0" max="30" value="<?= (int)$values['skip_delay'] ?>"></label></div>
<div class="form-field"><label>Image HTTPS URL<input name="image_url" maxlength="2048" value="<?= et_e($values['image_url']) ?>"></label></div>
<div class="form-field"><label>Or upload an image (up to 5 MB)<input name="image" type="file" accept="image/jpeg,image/png,image/webp"></label></div>
</div></section>
<section class="form-section"><h2>Link and display pages</h2><div class="form-grid">
<div class="form-field"><label>Link type<select name="destination_type" data-placement-destination><option value="external"<?= $values['destination_type']==='external'?' selected':'' ?>>External HTTPS link</option><option value="internal"<?= $values['destination_type']==='internal'?' selected':'' ?>>Internal article</option></select></label></div>
<div class="form-field" data-placement-external><label>External HTTPS destination<input name="external_url" maxlength="2048" value="<?= et_e($values['external_url']) ?>"></label><small>Sponsors can use this link. An internal article is not required.</small></div>
<div class="form-field full" data-placement-internal><label>Internal article<select name="content_id"><option value="">Choose an article</option><?php foreach($articles as $article): ?><option value="<?= (int)$article['id'] ?>"<?= (int)$values['content_id']===(int)$article['id']?' selected':'' ?>><?= et_e($article['title'].' — '.$article['status']) ?></option><?php endforeach; ?></select></label><small>Choose this only for an internal article.</small></div>
<fieldset class="form-field full placement-targets"><legend>Where to show</legend><div class="placement-target-grid"><?php foreach(et_placement_targets() as $key=>$label): ?><label><input type="checkbox" name="targets[]" value="<?= et_e($key) ?>"<?= in_array($key,$selected,true)?' checked':'' ?>><?= et_e(et_admin_label($label)) ?></label><?php endforeach; ?></div></fieldset>
</div></section>
<section class="form-section"><h2>Publishing and schedule</h2><div class="form-grid">
<div class="form-field"><label>Status<select name="status"><option value="draft"<?= $values['status']==='draft'?' selected':'' ?>>Draft</option><option value="published"<?= $values['status']==='published'?' selected':'' ?>>Published (follows schedule)</option><option value="archived"<?= $values['status']==='archived'?' selected':'' ?>>Archived</option></select></label></div>
<div class="form-field"><label>Priority (0–100)<input name="priority" type="number" min="0" max="100" value="<?= (int)$values['priority'] ?>"></label></div>
<?php foreach(['starts_at'=>'Start (EAT), optional','ends_at'=>'End (EAT), optional'] as $field=>$label): ?><div class="form-field"><label><?= et_e(et_admin_label($label)) ?><input type="datetime-local" name="<?= $field ?>" value="<?= et_e($values[$field]) ?>"></label></div><?php endforeach; ?>
</div></section><div class="form-actions"><button class="admin-button">Save placement</button><?php if($item): ?><a class="admin-button secondary" href="preview.php?id=<?= $id ?>">Preview</a><?php endif; ?></div></form>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const type = document.querySelector('[data-placement-destination]');
    const external = document.querySelector('[data-placement-external]');
    const internal = document.querySelector('[data-placement-internal]');
    if (!type || !external || !internal) return;
    const sync = function () {
        const usesInternal = type.value === 'internal';
        external.hidden = usesInternal;
        internal.hidden = !usesInternal;
        external.querySelector('input').disabled = usesInternal;
        internal.querySelector('select').disabled = !usesInternal;
    };
    type.addEventListener('change', sync);
    sync();
});
</script>
<?php et_admin_footer(); ?>
