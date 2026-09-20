<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/includes/admin_auth.php';
require_once dirname(__DIR__, 2) . '/includes/content.php';
require_once dirname(__DIR__) . '/_layout.php';
et_admin_boot();
$user = et_require_admin();
$database = et_db();
if (et_refresh_content_states($database) > 0) {
    et_rebuild_sitemap($database);
}

$status = trim((string) ($_GET['status'] ?? ''));
$category = trim((string) ($_GET['category'] ?? ''));
$where = [];
$parameters = [];
if (array_key_exists($status, ET_CONTENT_STATUSES)) {
    $where[] = 'status = :status';
    $parameters['status'] = $status;
}
if (array_key_exists($category, ET_CONTENT_CATEGORIES)) {
    $where[] = 'category = :category';
    $parameters['category'] = $category;
}
$sql = 'SELECT id, title, slug, category, status, media_type, is_featured, is_popup, published_at, updated_at FROM content_items';
if ($where) {
    $sql .= ' WHERE ' . implode(' AND ', $where);
}
$sql .= ' ORDER BY updated_at DESC, id DESC LIMIT 100';
$statement = $database->prepare($sql);
$statement->execute($parameters);
$items = $statement->fetchAll();

et_admin_header('Maudhui', $user, 'content', '../');
?>
<div class="admin-page-heading"><div><p>Matangazo, habari, results, selection na admission. Owner anaweza kufuta kabisa maudhui yaliyowekwa archive.</p></div><a class="admin-button" href="edit.php">+ Ongeza maudhui</a></div>
<p><a href="../placements/">Simamia banners, sponsors na distributed placements →</a></p>
<form method="get" class="form-section" style="margin-bottom:16px">
    <div class="form-grid">
        <div class="form-field"><label for="status">Status</label><select id="status" name="status"><option value="">Zote</option><?php foreach (ET_CONTENT_STATUSES as $key => $label): ?><option value="<?= et_e($key) ?>"<?= $status === $key ? ' selected' : '' ?>><?= et_e($label) ?></option><?php endforeach; ?></select></div>
        <div class="form-field"><label for="category">Category</label><select id="category" name="category"><option value="">Zote</option><?php foreach (ET_CONTENT_CATEGORIES as $key => $label): ?><option value="<?= et_e($key) ?>"<?= $category === $key ? ' selected' : '' ?>><?= et_e($label) ?></option><?php endforeach; ?></select></div>
    </div>
    <button class="admin-button secondary small" type="submit" style="margin-top:12px">Chuja</button>
</form>
<div class="table-wrap">
    <table class="admin-table">
        <thead><tr><th>Kichwa</th><th>Status</th><th>Publication</th><th>Vitendo</th></tr></thead>
        <tbody>
        <?php if (!$items): ?><tr><td colspan="4" class="empty-state">Hakuna maudhui yanayolingana.</td></tr><?php endif; ?>
        <?php foreach ($items as $item): ?>
            <tr>
                <td><strong><?= et_e($item['title']) ?></strong><small><?= et_e(ET_CONTENT_CATEGORIES[$item['category']] ?? $item['category']) ?><?= $item['media_type'] === 'image' ? ' · Picha' : ($item['media_type'] === 'youtube' ? ' · YouTube' : '') ?><?= (int) $item['is_featured'] ? ' · Featured' : '' ?><?= (int) $item['is_popup'] ? ' · Pop-up' : '' ?></small></td>
                <td><?= et_admin_status_badge($item['status']) ?></td>
                <td><?= $item['published_at'] ? et_e(et_admin_datetime($item['published_at'])) : '—' ?><small>Updated <?= et_e(et_admin_datetime($item['updated_at'])) ?></small></td>
                <td><div class="table-actions"><a class="admin-button secondary small" href="edit.php?id=<?= (int) $item['id'] ?>">Hariri</a><?php if ($item['status'] !== 'archived'): ?><form method="post" action="archive.php" data-confirm="Archive maudhui haya?"><input type="hidden" name="csrf_token" value="<?= et_e(et_csrf_token()) ?>"><input type="hidden" name="id" value="<?= (int) $item['id'] ?>"><button class="admin-button danger small" type="submit">Archive</button></form><?php elseif (($user['role'] ?? '') === 'owner'): ?><form method="post" action="delete.php" data-confirm="Futa maudhui haya kabisa? Hatua hii haiwezi kurudishwa."><input type="hidden" name="csrf_token" value="<?= et_e(et_csrf_token()) ?>"><input type="hidden" name="id" value="<?= (int) $item['id'] ?>"><button class="admin-button danger small" type="submit">Futa kabisa</button></form><?php endif; ?></div></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php et_admin_footer(); ?>
