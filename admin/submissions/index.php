<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/includes/admin_auth.php';
require_once dirname(__DIR__) . '/_layout.php';
et_admin_boot();
$user = et_require_admin();
$database = et_db();
$allowedStatuses = ['new' => 'New', 'review' => 'In review', 'resolved' => 'Resolved', 'spam' => 'Spam', 'archived' => 'Archived'];
$status = trim((string) ($_GET['status'] ?? 'new'));
$status = $status === 'all' || isset($allowedStatuses[$status]) ? $status : 'new';
$page = max(1, filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT) ?: 1);
$perPage = 20;
$offset = ($page - 1) * $perPage;
$sql = 'SELECT * FROM submissions';
$parameters = [];
if (isset($allowedStatuses[$status])) {
    $sql .= ' WHERE status = :status';
    $parameters['status'] = $status;
}
$countSql = 'SELECT COUNT(*) FROM submissions' . ($parameters ? ' WHERE status = :status' : '');
$countStatement = $database->prepare($countSql);
$countStatement->execute($parameters);
$totalItems = (int) $countStatement->fetchColumn();
$totalPages = max(1, (int) ceil($totalItems / $perPage));
if ($page > $totalPages) { $page = $totalPages; $offset = ($page - 1) * $perPage; }
$sql .= ' ORDER BY created_at DESC, id DESC LIMIT :limit OFFSET :offset';
$statement = $database->prepare($sql);
foreach ($parameters as $key => $value) { $statement->bindValue(':' . $key, $value); }
$statement->bindValue(':limit', $perPage, PDO::PARAM_INT);
$statement->bindValue(':offset', $offset, PDO::PARAM_INT);
$statement->execute();
$items = $statement->fetchAll();

et_admin_header('Ujumbe', $user, 'submissions', '../');
?>
<div class="admin-page-heading"><div><p>Default ni ujumbe mpya; rekodi <?= number_format($totalItems) ?>, ukurasa <?= $page ?> wa <?= $totalPages ?>.</p></div></div>
<form method="get" class="form-section compact-filter"><div class="form-field"><label for="status">Status</label><select id="status" name="status"><option value="all"<?= $status === 'all' ? ' selected' : '' ?>>Zote</option><?php foreach ($allowedStatuses as $key => $label): ?><option value="<?= et_e($key) ?>"<?= $status === $key ? ' selected' : '' ?>><?= et_e($label) ?></option><?php endforeach; ?></select></div><button class="admin-button secondary small" type="submit">Chuja</button></form>
<div class="table-wrap"><table class="admin-table"><thead><tr><th>Mtumaji</th><th>Ujumbe</th><th>Status</th><th>Action</th></tr></thead><tbody>
<?php if (!$items): ?><tr><td colspan="4" class="empty-state">Hakuna ujumbe unaolingana.</td></tr><?php endif; ?>
<?php foreach ($items as $item): ?><tr>
    <td><strong><?= et_e($item['sender_role']) ?></strong><small><?= et_e($item['topic']) ?> · <?= et_e($item['created_at']) ?> UTC</small><?php if ($item['contact'] !== ''): ?><small><?= et_e($item['contact']) ?></small><?php endif; ?></td>
    <td><div class="message-body"><?= et_e($item['message']) ?></div></td>
    <td><span class="status-badge status-<?= et_e($item['status']) ?>"><?= et_e($allowedStatuses[$item['status']] ?? $item['status']) ?></span></td>
    <td><form action="update.php" method="post" class="message-action-form"><input type="hidden" name="csrf_token" value="<?= et_e(et_csrf_token()) ?>"><input type="hidden" name="id" value="<?= (int) $item['id'] ?>"><input type="hidden" name="return_status" value="<?= et_e($status) ?>"><input type="hidden" name="return_page" value="<?= $page ?>"><select name="status" aria-label="Badilisha status"><?php foreach ($allowedStatuses as $key => $label): ?><option value="<?= et_e($key) ?>"<?= $item['status'] === $key ? ' selected' : '' ?>><?= et_e($label) ?></option><?php endforeach; ?></select><details class="note-editor"><summary><?= $item['admin_note'] !== '' ? 'Internal note ipo' : 'Ongeza internal note' ?></summary><textarea name="admin_note" maxlength="1000" placeholder="Internal note"><?= et_e($item['admin_note']) ?></textarea></details><button class="admin-button small" type="submit">Hifadhi</button></form></td>
</tr><?php endforeach; ?>
</tbody></table></div>
<?php et_admin_pagination($page, $totalPages, ['status' => $status], 'Kurasa za ujumbe'); ?>
<?php et_admin_footer(); ?>
