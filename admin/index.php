<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/admin_auth.php';
require_once dirname(__DIR__) . '/includes/content.php';
require_once __DIR__ . '/_layout.php';
et_admin_boot();
$user = et_require_admin_at_root();
$database = et_db();
if (et_refresh_content_states($database) > 0) {
    et_rebuild_sitemap($database);
}

$contentCounts = array_fill_keys(array_keys(ET_CONTENT_STATUSES), 0);
foreach ($database->query('SELECT status, COUNT(*) AS total FROM content_items GROUP BY status')->fetchAll() as $row) {
    $contentCounts[$row['status']] = (int) $row['total'];
}
$newSubmissions = (int) $database->query("SELECT COUNT(*) FROM submissions WHERE status = 'new'")->fetchColumn();
$openSystemEvents = (int) $database->query("SELECT COUNT(*) FROM system_events WHERE status = 'open'")->fetchColumn();
$trafficStart = (new DateTimeImmutable('today', new DateTimeZone('Africa/Dar_es_Salaam')))->modify('-6 days')->format('Y-m-d');
$trafficStatement = $database->prepare('SELECT COALESCE(SUM(views),0) FROM traffic_daily WHERE day>=:start_day');
$trafficStatement->execute(['start_day'=>$trafficStart]);
$views7 = (int) $trafficStatement->fetchColumn();
$popupTitle = $database->query(<<<'SQL'
SELECT title FROM content_items
WHERE status = 'published' AND is_popup = 1 AND published_at <= datetime('now')
  AND (expires_at IS NULL OR expires_at > datetime('now'))
ORDER BY published_at DESC LIMIT 1
SQL)->fetchColumn();
$recentContent = $database->query(
    'SELECT id, title, status, category, updated_at FROM content_items ORDER BY updated_at DESC LIMIT 10'
)->fetchAll();
$recentAudit = $database->query(
    'SELECT action, entity_type, details, created_at FROM audit_logs ORDER BY id DESC LIMIT 3'
)->fetchAll();

et_admin_header('Dashboard', $user);
?>
<section class="stat-grid" aria-label="Muhtasari">
    <div class="stat-card"><small>Published</small><strong><?= $contentCounts['published'] ?></strong></div>
    <div class="stat-card"><small>Drafts</small><strong><?= $contentCounts['draft'] ?></strong></div>
    <div class="stat-card"><small>Ujumbe mpya</small><strong><?= $newSubmissions ?></strong></div>
    <div class="stat-card"><small>Views · siku 7</small><strong><?= number_format($views7) ?></strong></div>
</section>
<div class="admin-grid dashboard-grid">
    <section class="admin-card dashboard-recent-card"><h2>Maudhui ya karibuni</h2>
        <?php if ($recentContent): ?><ul class="admin-list dashboard-recent-list">
            <?php foreach ($recentContent as $item): ?><li><div><strong><?= et_e($item['title']) ?></strong><small><?= et_e(ET_CONTENT_CATEGORIES[$item['category']] ?? $item['category']) ?> · <?= et_e($item['updated_at']) ?> UTC</small></div><div><?= et_admin_status_badge($item['status']) ?> <a href="content/edit.php?id=<?= (int) $item['id'] ?>">Hariri</a></div></li><?php endforeach; ?>
        </ul><?php else: ?><div class="empty-state">Bado hakuna maudhui. Anza kwa kuongeza tangazo.</div><?php endif; ?>
    </section>
    <div>
        <section class="admin-card"><h2>System health</h2><p><strong><?= number_format($openSystemEvents) ?></strong> open events.</p><a href="monitoring/">Angalia traffic na errors →</a></section>
        <section class="admin-card" style="margin-top:18px"><h2>Current pop-up</h2><p><?= $popupTitle ? et_e($popupTitle) : 'Hakuna pop-up inayotumika sasa.' ?></p></section>
        <section class="admin-card" style="margin-top:18px"><h2>Recent activity</h2>
            <?php if ($recentAudit): ?><ul class="admin-list"><?php foreach ($recentAudit as $log): ?><li><div><strong><?= et_e(str_replace('_', ' ', $log['action'])) ?></strong><small><?= et_e($log['created_at']) ?> UTC</small></div></li><?php endforeach; ?></ul><?php else: ?><p>Hakuna shughuli.</p><?php endif; ?>
        </section>
    </div>
</div>
<?php et_admin_footer(); ?>
