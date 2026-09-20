<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/admin_auth.php';
require_once __DIR__ . '/_layout.php';
et_admin_boot();
$user = et_require_admin_at_root();
$database = et_db();
$action = trim((string) ($_GET['action'] ?? ''));
$action = preg_match('/^[a-z0-9_-]{1,80}$/i', $action) ? $action : '';
$page = max(1, filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT) ?: 1);
$perPage = 50;
$parameters = [];
$visibility = et_audit_visibility_sql($user);
$where = ' WHERE ' . $visibility;
if ($action !== '') { $where .= ' AND audit_logs.action=:action'; $parameters['action'] = $action; }
$countStatement = $database->prepare('SELECT COUNT(*) FROM audit_logs' . $where);
$countStatement->execute($parameters);
$totalItems = (int) $countStatement->fetchColumn();
$totalPages = max(1, (int) ceil($totalItems / $perPage));
if ($page > $totalPages) { $page = $totalPages; }
$statement = $database->prepare(<<<SQL
SELECT audit_logs.*, admin_users.display_name
FROM audit_logs LEFT JOIN admin_users ON admin_users.id = audit_logs.admin_user_id
{$where}
ORDER BY audit_logs.id DESC LIMIT :limit OFFSET :offset
SQL);
foreach ($parameters as $key => $value) { $statement->bindValue(':' . $key, $value); }
$statement->bindValue(':limit', $perPage, PDO::PARAM_INT);
$statement->bindValue(':offset', ($page - 1) * $perPage, PDO::PARAM_INT);
$statement->execute();
$logs = $statement->fetchAll();
$actions = $database->query('SELECT DISTINCT action FROM audit_logs WHERE ' . $visibility . ' ORDER BY action')->fetchAll(PDO::FETCH_COLUMN);
et_admin_header('Audit Log', $user, 'audit');
?>
<div class="admin-page-heading"><div><p>Rekodi <?= number_format($totalItems) ?>; ukurasa <?= $page ?> wa <?= $totalPages ?>. Rekodi 50 tu zinaonyeshwa kwa wakati.</p></div></div>
<form method="get" class="form-section compact-filter"><div class="form-field"><label for="action">Action</label><select id="action" name="action"><option value="">Zote</option><?php foreach ($actions as $actionOption): ?><option value="<?= et_e($actionOption) ?>"<?= $action === $actionOption ? ' selected' : '' ?>><?= et_e(str_replace('_',' ',$actionOption)) ?></option><?php endforeach; ?></select></div><button class="admin-button secondary small" type="submit">Chuja</button></form>
<div class="table-wrap"><table class="admin-table"><thead><tr><th>Muda</th><th>Admin</th><th>Action</th><th>Entity</th><th>Details</th></tr></thead><tbody>
<?php if (!$logs): ?><tr><td colspan="5" class="empty-state">Hakuna rekodi.</td></tr><?php endif; ?>
<?php foreach ($logs as $log): ?><tr><td><?= et_e(et_admin_datetime($log['created_at'])) ?></td><td><?= et_e($log['display_name'] ?: 'System/unknown') ?></td><td><?= et_e(str_replace('_', ' ', $log['action'])) ?></td><td><?= et_e($log['entity_type']) ?><?= $log['entity_id'] ? ' #' . (int) $log['entity_id'] : '' ?></td><td><?= et_e($log['details']) ?></td></tr><?php endforeach; ?>
</tbody></table></div>
<?php et_admin_pagination($page, $totalPages, ['action' => $action], 'Kurasa za audit log'); ?>
<?php et_admin_footer(); ?>
