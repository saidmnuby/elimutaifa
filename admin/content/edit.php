<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/includes/admin_auth.php';
require_once dirname(__DIR__, 2) . '/includes/content.php';
require_once dirname(__DIR__) . '/_layout.php';
et_admin_boot();
$user = et_require_admin();
$database = et_db();
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT) ?: 0;
$item = null;
if ($id > 0) {
    $statement = $database->prepare('SELECT * FROM content_items WHERE id = :id LIMIT 1');
    $statement->execute(['id' => $id]);
    $item = $statement->fetch();
    if (!$item) {
        http_response_code(404);
        exit('Content not found.');
    }
}

$values = $item ?: [
    'category' => 'announcement', 'title' => '', 'slug' => '', 'excerpt' => '', 'body' => '',
    'media_type' => 'none', 'media_url' => '', 'media_caption' => '',
    'audience' => 'all', 'source_name' => '', 'source_url' => '', 'destination_type' => 'internal',
    'external_url' => '', 'status' => 'draft', 'is_featured' => 0, 'is_popup' => 0,
    'published_at' => null, 'expires_at' => null,
];
$values['published_at'] = et_utc_datetime_to_local($values['published_at']);
$values['expires_at'] = et_utc_datetime_to_local($values['expires_at']);
$imageUrlValue = ($values['media_type'] ?? 'none') === 'image' && !et_is_managed_content_image((string) ($values['media_url'] ?? ''))
    ? (string) $values['media_url']
    : '';
$youtubeUrlValue = ($values['media_type'] ?? 'none') === 'youtube' ? (string) ($values['media_url'] ?? '') : '';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!et_verify_csrf($_POST['csrf_token'] ?? null)) {
        $errors['form'] = 'This request has expired. Reload the page.';
        $values = array_merge($values, $_POST);
    } else {
        $imageUpload = isset($_FILES['media_image']) && is_array($_FILES['media_image']) ? $_FILES['media_image'] : null;
        $hasImageUpload = $imageUpload !== null && (int) ($imageUpload['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;
        $selectedMediaType = trim((string) ($_POST['media_type'] ?? 'none'));
        $imageUrlValue = trim((string) ($_POST['image_url'] ?? ''));
        $youtubeUrlValue = trim((string) ($_POST['youtube_url'] ?? ''));
        $validationInput = $_POST;
        $validationInput['media_url'] = '';
        if ($selectedMediaType === 'image') {
            if ($imageUrlValue !== '') {
                $validationInput['media_url'] = $imageUrlValue;
            } elseif (($item['media_type'] ?? '') === 'image') {
                $validationInput['media_url'] = (string) ($item['media_url'] ?? '');
            }
            if ($hasImageUpload) {
                $validationInput['_has_image_upload'] = true;
            }
        } elseif ($selectedMediaType === 'youtube') {
            $validationInput['media_url'] = $youtubeUrlValue;
        }
        [$data, $errors] = et_validate_content_input($validationInput);
        $values = array_merge($data, [
            'published_at' => (string) ($_POST['published_at'] ?? ''),
            'expires_at' => (string) ($_POST['expires_at'] ?? ''),
        ]);
        $newManagedImage = null;
        if ($hasImageUpload && $data['media_type'] !== 'image') {
            $errors['media_image'] = 'Select Image as the media type before uploading a file.';
        }
        if (!$errors && $hasImageUpload) {
            try {
                [$newManagedImage, $uploadError] = et_store_content_image_upload($imageUpload);
            } catch (Throwable $exception) {
                $uploadError = 'Could not save the image.';
            }
            if ($uploadError !== null || $newManagedImage === null) {
                $errors['media_image'] = $uploadError ?? 'Could not save the image.';
            } else {
                $data['media_url'] = $newManagedImage;
                $values['media_url'] = $newManagedImage;
            }
        }
        if ($data['status'] === 'published' && $data['published_at'] === null) {
            $data['published_at'] = et_utc_now();
        }

        if (!$errors) {
            $previousMediaUrl = (string) ($item['media_url'] ?? '');
            try {
                $database->beginTransaction();
                if ($data['is_popup'] === 1 && in_array($data['status'], ['published', 'scheduled'], true)) {
                    $database->exec('UPDATE content_items SET is_popup = 0 WHERE is_popup = 1');
                }
                $parameters = $data + ['updated_at' => et_utc_now(), 'updated_by' => (int) $user['id']];
                if ($id > 0) {
                    $parameters['id'] = $id;
                    $statement = $database->prepare(<<<'SQL'
UPDATE content_items SET
 category=:category, title=:title, slug=:slug, excerpt=:excerpt, body=:body,
 media_type=:media_type, media_url=:media_url, media_caption=:media_caption, audience=:audience,
 source_name=:source_name, source_url=:source_url, destination_type=:destination_type,
 external_url=:external_url, status=:status, is_featured=:is_featured, is_popup=:is_popup,
 published_at=:published_at, expires_at=:expires_at, updated_by=:updated_by, updated_at=:updated_at
WHERE id=:id
SQL);
                    $statement->execute($parameters);
                    et_audit((int) $user['id'], 'content_updated', 'content_item', $id, $data['title']);
                } else {
                    $parameters['created_at'] = et_utc_now();
                    $parameters['created_by'] = (int) $user['id'];
                    $statement = $database->prepare(<<<'SQL'
INSERT INTO content_items
(category,title,slug,excerpt,body,media_type,media_url,media_caption,audience,source_name,source_url,destination_type,external_url,status,is_featured,is_popup,published_at,expires_at,created_by,updated_by,created_at,updated_at)
VALUES
(:category,:title,:slug,:excerpt,:body,:media_type,:media_url,:media_caption,:audience,:source_name,:source_url,:destination_type,:external_url,:status,:is_featured,:is_popup,:published_at,:expires_at,:created_by,:updated_by,:created_at,:updated_at)
SQL);
                    $statement->execute($parameters);
                    $id = (int) $database->lastInsertId();
                    et_audit((int) $user['id'], 'content_created', 'content_item', $id, $data['title']);
                }
                $database->commit();
            } catch (Throwable $exception) {
                if ($database->inTransaction()) {
                    $database->rollBack();
                }
                if ($newManagedImage !== null) {
                    et_delete_managed_content_image($newManagedImage);
                }
                $errors['form'] = str_contains(strtolower($exception->getMessage()), 'unique')
                    ? 'This URL name is already used. Choose another.'
                    : 'Could not save the content.';
            }
            if (!$errors) {
                if ($previousMediaUrl !== '' && $previousMediaUrl !== $data['media_url'] && !et_delete_managed_content_image($previousMediaUrl)) {
                    et_record_system_event('content_media_delete_failed', 'An old managed content image could not be removed.', 'warning');
                }
                $sitemapUpdated = et_rebuild_sitemap($database);
                et_flash($sitemapUpdated ? 'success' : 'error', $sitemapUpdated ? 'Content saved.' : 'Content saved, but the sitemap could not be updated.');
                et_redirect('edit.php?id=' . $id);
            }
        }
    }
}

$currentImagePreview = ($values['media_type'] ?? 'none') === 'image'
    ? et_content_image_url($values, '../..')
    : '';

et_admin_header($id > 0 ? 'Edit content' : 'Add content', $user, 'content', '../');
?>
<div class="admin-page-heading"><div><p>Use simple language and add a source where needed.</p></div><?php if ($id > 0 && $item && $item['status'] === 'published' && $item['destination_type'] === 'internal'): ?><a class="admin-button secondary" href="../../announcements/<?= et_e(rawurlencode($item['slug'])) ?>/" target="_blank" rel="noopener">Preview ↗</a><?php endif; ?></div>
<?php if (isset($errors['form'])): ?><div class="admin-alert error" role="alert"><?= et_e($errors['form']) ?></div><?php endif; ?>
<form method="post" class="admin-form" enctype="multipart/form-data">
    <input type="hidden" name="csrf_token" value="<?= et_e(et_csrf_token()) ?>"><input type="hidden" name="id" value="<?= (int) $id ?>">
    <section class="form-section"><h2>Basic details</h2><div class="form-grid">
        <div class="form-field"><label for="category">Category</label><select id="category" name="category"><?php foreach (ET_CONTENT_CATEGORIES as $key => $label): ?><option value="<?= et_e($key) ?>"<?= $values['category'] === $key ? ' selected' : '' ?>><?= et_e(et_admin_label($label)) ?></option><?php endforeach; ?></select><?php if (isset($errors['category'])): ?><span class="form-error"><?= et_e($errors['category']) ?></span><?php endif; ?></div>
        <div class="form-field"><label for="audience">Audience</label><select id="audience" name="audience"><?php foreach (ET_CONTENT_AUDIENCES as $key => $label): ?><option value="<?= et_e($key) ?>"<?= $values['audience'] === $key ? ' selected' : '' ?>><?= et_e(et_admin_label($label)) ?></option><?php endforeach; ?></select></div>
        <div class="form-field full"><label for="title">Title</label><input id="title" name="title" maxlength="140" value="<?= et_e($values['title']) ?>" required><?php if (isset($errors['title'])): ?><span class="form-error"><?= et_e($errors['title']) ?></span><?php endif; ?></div>
        <div class="form-field full"><label for="slug">SEO slug</label><input id="slug" name="slug" maxlength="160" pattern="[a-z0-9-]+" value="<?= et_e($values['slug']) ?>" required><small>Example: form-five-selection-2026</small><?php if (isset($errors['slug'])): ?><span class="form-error"><?= et_e($errors['slug']) ?></span><?php endif; ?></div>
        <div class="form-field full"><label for="excerpt">Short description</label><textarea id="excerpt" name="excerpt" maxlength="300" required><?= et_e($values['excerpt']) ?></textarea><?php if (isset($errors['excerpt'])): ?><span class="form-error"><?= et_e($errors['excerpt']) ?></span><?php endif; ?></div>
        <div class="form-field full"><label for="body">Full text</label><textarea class="body-editor" id="body" name="body" maxlength="20000" required><?= et_e($values['body']) ?></textarea><small>Use plain text. Line breaks are kept.</small><?php if (isset($errors['body'])): ?><span class="form-error"><?= et_e($errors['body']) ?></span><?php endif; ?></div>
    </div></section>
    <section class="form-section media-section"><h2>Images and video</h2><p class="form-section-intro">Choose one image or video for an ElimuTaifa article. Videos play only when the reader presses Play.</p>
        <fieldset class="media-type-picker"><legend>Media type</legend><div class="media-choice-grid">
            <label class="media-choice"><input type="radio" name="media_type" value="none"<?= $values['media_type'] === 'none' ? ' checked' : '' ?>><span class="media-choice-icon" aria-hidden="true">—</span><span><strong>No media</strong><small>Text only</small></span></label>
            <label class="media-choice"><input type="radio" name="media_type" value="image"<?= $values['media_type'] === 'image' ? ' checked' : '' ?>><span class="media-choice-icon" aria-hidden="true">▧</span><span><strong>Image</strong><small>Upload or HTTPS URL</small></span></label>
            <label class="media-choice"><input type="radio" name="media_type" value="youtube"<?= $values['media_type'] === 'youtube' ? ' checked' : '' ?>><span class="media-choice-icon media-choice-video" aria-hidden="true">▶</span><span><strong>YouTube</strong><small>One embedded video</small></span></label>
        </div><?php if (isset($errors['media_type'])): ?><span class="form-error"><?= et_e($errors['media_type']) ?></span><?php endif; ?></fieldset>

        <div class="media-panel" data-media-panel="image"<?= $values['media_type'] === 'image' ? '' : ' hidden' ?>>
            <div class="media-panel-heading"><div><strong>Article image</strong><small>Upload an image or enter an HTTPS link. If you provide both, the uploaded image is used.</small></div></div>
            <div class="media-image-layout">
                <div class="media-preview" id="mediaImagePreview"<?= $currentImagePreview === '' ? ' hidden' : '' ?> data-existing-url="<?= et_e($currentImagePreview) ?>"><img id="mediaImagePreviewElement"<?= $currentImagePreview === '' ? '' : ' src="' . et_e($currentImagePreview) . '"' ?> alt="Image preview"><span id="mediaImagePreviewStatus"><?= $currentImagePreview === '' ? 'No image selected.' : 'This image will appear in the article.' ?></span></div>
                <div class="media-input-stack">
                    <div class="form-field"><label for="media_image">Upload image</label><input id="media_image" name="media_image" type="file" accept="image/jpeg,image/png,image/webp"><small>JPG, PNG or WebP, up to 5 MB.</small><?php if (isset($errors['media_image'])): ?><span class="form-error"><?= et_e($errors['media_image']) ?></span><?php endif; ?></div>
                    <div class="media-or" aria-hidden="true"><span>OR</span></div>
                    <div class="form-field"><label for="image_url">Image HTTPS link</label><input id="image_url" name="image_url" type="url" inputmode="url" maxlength="1000" value="<?= et_e($imageUrlValue) ?>" placeholder="https://example.com/picha.jpg"><small>Upload an image to avoid relying on another website.</small></div>
                </div>
            </div>
            <?php if (($values['media_type'] ?? '') === 'image' && isset($errors['media_url'])): ?><span class="form-error"><?= et_e($errors['media_url']) ?></span><?php endif; ?>
        </div>

        <div class="media-panel" data-media-panel="youtube"<?= $values['media_type'] === 'youtube' ? '' : ' hidden' ?>>
            <div class="media-panel-heading"><div><strong>YouTube video</strong><small>Paste a YouTube Watch, Share, Shorts, Live or Embed link. The admin preview does not play the video.</small></div></div>
            <div class="form-field"><label for="youtube_url">YouTube URL</label><input id="youtube_url" name="youtube_url" type="url" inputmode="url" maxlength="1000" value="<?= et_e($youtubeUrlValue) ?>" placeholder="https://www.youtube.com/watch?v=..."><?php if (($values['media_type'] ?? '') === 'youtube' && isset($errors['media_url'])): ?><span class="form-error"><?= et_e($errors['media_url']) ?></span><?php endif; ?></div>
            <div class="youtube-link-preview" id="youtubeLinkPreview" aria-live="polite"><span class="youtube-preview-visual"><img id="youtubePreviewImage" alt="" hidden><i class="youtube-preview-play" aria-hidden="true">▶</i></span><span><strong id="youtubePreviewTitle">Paste a YouTube link</strong><small id="youtubePreviewStatus">The video loads when the reader presses Play.</small></span><a id="youtubePreviewOpen" href="#" target="_blank" rel="noopener noreferrer" hidden>Open YouTube ↗</a></div>
        </div>

        <div class="media-panel media-caption-panel" data-media-panel="caption"<?= in_array($values['media_type'], ['image', 'youtube'], true) ? '' : ' hidden' ?>><div class="form-field"><label for="media_caption">Media description</label><input id="media_caption" name="media_caption" maxlength="200" value="<?= et_e($values['media_caption']) ?>" placeholder="Describe the image or name the video"><small>Optional, but helps people understand the image or video.</small><?php if (isset($errors['media_caption'])): ?><span class="form-error"><?= et_e($errors['media_caption']) ?></span><?php endif; ?></div></div>
        <p class="media-external-note" id="mediaExternalNote" hidden>An external destination opens another website. It cannot include an image or embedded video on ElimuTaifa.</p>
    </section>
    <section class="form-section"><h2>Source and link</h2><div class="form-grid">
        <div class="form-field"><label for="source_name">Source name</label><input id="source_name" name="source_name" maxlength="120" value="<?= et_e($values['source_name']) ?>"><?php if (isset($errors['source_name'])): ?><span class="form-error"><?= et_e($errors['source_name']) ?></span><?php endif; ?></div>
        <div class="form-field"><label for="source_url">Source URL</label><input id="source_url" name="source_url" type="url" maxlength="1000" value="<?= et_e($values['source_url']) ?>"><?php if (isset($errors['source_url'])): ?><span class="form-error"><?= et_e($errors['source_url']) ?></span><?php endif; ?></div>
        <div class="form-field"><label for="destination_type">When the link is clicked</label><select id="destination_type" name="destination_type"><option value="internal"<?= $values['destination_type'] === 'internal' ? ' selected' : '' ?>>Open an ElimuTaifa article</option><option value="external"<?= $values['destination_type'] === 'external' ? ' selected' : '' ?>>Open another website</option></select></div>
        <div class="form-field"><label for="external_url">External destination URL</label><input id="external_url" name="external_url" type="url" maxlength="1000" value="<?= et_e($values['external_url']) ?>"><?php if (isset($errors['external_url'])): ?><span class="form-error"><?= et_e($errors['external_url']) ?></span><?php endif; ?></div>
    </div></section>
    <section class="form-section"><h2>Publication</h2><div class="form-grid">
        <div class="form-field"><label for="status">Status</label><select id="status" name="status"><?php foreach (ET_CONTENT_STATUSES as $key => $label): ?><option value="<?= et_e($key) ?>"<?= $values['status'] === $key ? ' selected' : '' ?>><?= et_e(et_admin_label($label)) ?></option><?php endforeach; ?></select></div>
        <div class="form-field"><label for="published_at">Publish date/time</label><input id="published_at" name="published_at" type="datetime-local" value="<?= et_e($values['published_at']) ?>"><small>Africa/Dar_es_Salaam</small><?php if (isset($errors['published_at'])): ?><span class="form-error"><?= et_e($errors['published_at']) ?></span><?php endif; ?></div>
        <div class="form-field"><label for="expires_at">Expire date/time</label><input id="expires_at" name="expires_at" type="datetime-local" value="<?= et_e($values['expires_at']) ?>"><small>Optional.</small><?php if (isset($errors['expires_at'])): ?><span class="form-error"><?= et_e($errors['expires_at']) ?></span><?php endif; ?></div>
        <div class="form-field full check-row"><label><input type="checkbox" name="is_featured" value="1"<?= (int) $values['is_featured'] ? ' checked' : '' ?>> Show at the top of announcements</label><label><input type="checkbox" name="is_popup" value="1"<?= (int) $values['is_popup'] ? ' checked' : '' ?>> Show as a pop-up</label></div>
    </div></section>
    <div class="form-actions"><button class="admin-button" type="submit">Save content</button><a class="admin-button secondary" href="./">Back</a></div>
</form>
<?php if ($id > 0 && $item && $item['status'] === 'archived' && ($user['role'] ?? '') === 'owner'): ?>
<form class="content-danger-zone" method="post" action="delete.php" data-confirm="Permanently delete this content? This cannot be undone.">
    <input type="hidden" name="csrf_token" value="<?= et_e(et_csrf_token()) ?>"><input type="hidden" name="id" value="<?= (int) $id ?>">
    <div><strong>Danger zone</strong><small>This content is archived and can be permanently deleted.</small></div><button class="admin-button danger small" type="submit">Delete permanently</button>
</form>
<?php endif; ?>
<?php et_admin_footer(); ?>
