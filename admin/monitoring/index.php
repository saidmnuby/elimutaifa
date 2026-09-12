<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/includes/admin_auth.php';
require_once dirname(__DIR__) . '/_layout.php';
et_admin_boot();
$user = et_require_admin();
$database = et_db();
$today = new DateTimeImmutable('today', new DateTimeZone('Africa/Dar_es_Salaam'));
$sevenDaysAgo = $today->modify('-6 days')->format('Y-m-d');
$thirtyDaysAgo = $today->modify('-29 days')->format('Y-m-d');

$trafficStatement = $database->prepare('SELECT COALESCE(SUM(views),0) AS views FROM traffic_daily WHERE day>=:start_day');
$trafficStatement->execute(['start_day'=>$sevenDaysAgo]);
$views7 = (int) $trafficStatement->fetchColumn();
$trafficStatement->execute(['start_day'=>$thirtyDaysAgo]);
$views30 = (int) $trafficStatement->fetchColumn();
$uniqueStatement = $database->prepare('SELECT COUNT(DISTINCT visitor_hash) FROM traffic_unique_visitors WHERE day>=:start_day');
$uniqueStatement->execute(['start_day'=>$sevenDaysAgo]);
$unique7 = (int) $uniqueStatement->fetchColumn();
$openEvents = (int) $database->query("SELECT COUNT(*) FROM system_events WHERE status='open'")->fetchColumn();

$dailyStatement = $database->prepare(<<<'SQL'
SELECT
    traffic.day,
    SUM(traffic.views) AS views,
    (
        SELECT COUNT(DISTINCT visitor.visitor_hash)
        FROM traffic_unique_visitors AS visitor
        WHERE visitor.day = traffic.day
    ) AS visitors
FROM traffic_daily AS traffic
WHERE traffic.day >= :start_day
GROUP BY traffic.day
ORDER BY traffic.day ASC
SQL);
$dailyStatement->execute(['start_day'=>$thirtyDaysAgo]);
$queriedDailyRows = $dailyStatement->fetchAll();
$dailyLookup = [];
foreach ($queriedDailyRows as $row) {
    $dailyLookup[$row['day']] = [
        'views' => (int) $row['views'],
        'visitors' => (int) $row['visitors'],
    ];
}
$dailyRows = [];
for ($daysAgo = 29; $daysAgo >= 0; $daysAgo--) {
    $day = $today->modify('-' . $daysAgo . ' days')->format('Y-m-d');
    $dailyRows[] = [
        'day' => $day,
        'views' => $dailyLookup[$day]['views'] ?? 0,
        'visitors' => $dailyLookup[$day]['visitors'] ?? 0,
    ];
}
$hasDailyTraffic = array_sum(array_column($dailyRows, 'views')) > 0;
$maxDailyTraffic = max(
    1,
    ...array_map(
        static fn(array $row): int => max((int) $row['views'], (int) $row['visitors']),
        $dailyRows
    )
);
$chartMax = max(4, (int) (ceil($maxDailyTraffic / 4) * 4));
$chartWidth = 960;
$chartHeight = 320;
$chartLeft = 58;
$chartRight = 20;
$chartTop = 20;
$chartBottom = 44;
$plotWidth = $chartWidth - $chartLeft - $chartRight;
$plotHeight = $chartHeight - $chartTop - $chartBottom;
$baselineY = $chartTop + $plotHeight;
$viewsPoints = [];
$visitorsPoints = [];
foreach ($dailyRows as $index => &$row) {
    $x = $chartLeft + (($index / (count($dailyRows) - 1)) * $plotWidth);
    $viewsY = $chartTop + ((1 - ((int) $row['views'] / $chartMax)) * $plotHeight);
    $visitorsY = $chartTop + ((1 - ((int) $row['visitors'] / $chartMax)) * $plotHeight);
    $row['x'] = number_format($x, 2, '.', '');
    $row['views_y'] = number_format($viewsY, 2, '.', '');
    $row['visitors_y'] = number_format($visitorsY, 2, '.', '');
    $viewsPoints[] = $row['x'] . ',' . $row['views_y'];
    $visitorsPoints[] = $row['x'] . ',' . $row['visitors_y'];
}
unset($row);
$viewsAreaPoints = $chartLeft . ',' . $baselineY . ' ' . implode(' ', $viewsPoints) . ' ' . ($chartLeft + $plotWidth) . ',' . $baselineY;
$axisTicks = [];
for ($step = 0; $step <= 4; $step++) {
    $axisTicks[] = [
        'y' => number_format($chartTop + (($step / 4) * $plotHeight), 2, '.', ''),
        'value' => (int) round($chartMax * (1 - ($step / 4))),
    ];
}
$dateLabelIndexes = [0, 7, 14, 21, 29];
$topStatement = $database->prepare('SELECT path,SUM(views) AS views,SUM(unique_visitors) AS unique_page_visitors FROM traffic_daily WHERE day>=:start_day GROUP BY path ORDER BY views DESC LIMIT 12');
$topStatement->execute(['start_day'=>$thirtyDaysAgo]);
$topPages = $topStatement->fetchAll();

$eventStatus = trim((string) ($_GET['status'] ?? 'open'));
$eventStatus = in_array($eventStatus, ['open','resolved','all'], true) ? $eventStatus : 'open';
$severity = trim((string) ($_GET['severity'] ?? 'all'));
$severity = in_array($severity, ['all','info','warning','error','critical'], true) ? $severity : 'all';
$page = max(1, filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT) ?: 1);
$perPage = 25;
$where = [];
$parameters = [];
if ($eventStatus !== 'all') { $where[] = 'status=:status'; $parameters['status']=$eventStatus; }
if ($severity !== 'all') { $where[] = 'severity=:severity'; $parameters['severity']=$severity; }
$whereSql = $where ? ' WHERE ' . implode(' AND ', $where) : '';
$countStatement = $database->prepare('SELECT COUNT(*) FROM system_events' . $whereSql);
$countStatement->execute($parameters);
$totalEvents = (int) $countStatement->fetchColumn();
$totalPages = max(1, (int) ceil($totalEvents / $perPage));
if ($page > $totalPages) { $page = $totalPages; }
$eventStatement = $database->prepare('SELECT * FROM system_events' . $whereSql . ' ORDER BY last_seen_at DESC,id DESC LIMIT :limit OFFSET :offset');
foreach ($parameters as $key=>$value) { $eventStatement->bindValue(':'.$key,$value); }
$eventStatement->bindValue(':limit',$perPage,PDO::PARAM_INT);
$eventStatement->bindValue(':offset',($page-1)*$perPage,PDO::PARAM_INT);
$eventStatement->execute();
$events = $eventStatement->fetchAll();

et_admin_header('Traffic & Errors', $user, 'monitoring', '../');
?>
<div class="admin-page-heading"><div><p>First-party traffic summary na matatizo yaliyokutana na watumiaji.</p></div></div>
<section class="stat-grid"><div class="stat-card"><small>Views · siku 7</small><strong><?= number_format($views7) ?></strong></div><div class="stat-card"><small>Approx. visitors · siku 7</small><strong><?= number_format($unique7) ?></strong></div><div class="stat-card"><small>Views · siku 30</small><strong><?= number_format($views30) ?></strong></div><div class="stat-card"><small>Open system events</small><strong><?= number_format($openEvents) ?></strong></div></section>
<div class="admin-grid monitoring-summary-grid">
    <section class="admin-card monitoring-traffic-card" aria-labelledby="traffic-chart-title">
        <div class="traffic-card-heading">
            <div><h2 id="traffic-chart-title">Traffic ya siku 30</h2><p>Mabadiliko ya matumizi kwa kila siku</p></div>
            <div class="traffic-legend" aria-label="Ufafanuzi wa graph"><span><i class="legend-views"></i>Views</span><span><i class="legend-visitors"></i>Visitors</span></div>
        </div>
        <?php if (!$hasDailyTraffic): ?>
            <div class="empty-state">Traffic data itaanza kuonekana baada ya watumiaji kufungua kurasa.</div>
        <?php else: ?>
            <div class="traffic-chart">
                <svg class="traffic-graph" viewBox="0 0 <?= $chartWidth ?> <?= $chartHeight ?>" role="img" aria-labelledby="traffic-graph-title traffic-graph-description">
                    <title id="traffic-graph-title">Graph ya views na visitors kwa siku 30</title>
                    <desc id="traffic-graph-description">Mstari wa kijani unaonyesha views na wa bluu unaonyesha visitors wa kila siku.</desc>
                    <defs>
                        <linearGradient id="views-area-gradient" x1="0" x2="0" y1="0" y2="1">
                            <stop offset="0%" stop-color="#159447" stop-opacity=".22" />
                            <stop offset="100%" stop-color="#159447" stop-opacity=".02" />
                        </linearGradient>
                    </defs>
                    <?php foreach ($axisTicks as $tick): ?>
                        <line class="traffic-grid-line" x1="<?= $chartLeft ?>" x2="<?= $chartLeft + $plotWidth ?>" y1="<?= $tick['y'] ?>" y2="<?= $tick['y'] ?>" />
                        <text class="traffic-axis-label traffic-y-label" x="<?= $chartLeft - 10 ?>" y="<?= (float) $tick['y'] + 4 ?>" text-anchor="end"><?= number_format($tick['value']) ?></text>
                    <?php endforeach; ?>
                    <?php foreach ($dateLabelIndexes as $labelIndex): $labelRow = $dailyRows[$labelIndex]; ?>
                        <text class="traffic-axis-label" x="<?= $labelRow['x'] ?>" y="<?= $chartHeight - 13 ?>" text-anchor="middle"><?= et_e((new DateTimeImmutable($labelRow['day']))->format('d M')) ?></text>
                    <?php endforeach; ?>
                    <polygon class="traffic-area" points="<?= et_e($viewsAreaPoints) ?>" />
                    <polyline class="traffic-series traffic-series-views" points="<?= et_e(implode(' ', $viewsPoints)) ?>" />
                    <polyline class="traffic-series traffic-series-visitors" points="<?= et_e(implode(' ', $visitorsPoints)) ?>" />
                    <?php foreach ($dailyRows as $row): ?>
                        <circle class="traffic-point traffic-point-views" cx="<?= $row['x'] ?>" cy="<?= $row['views_y'] ?>" r="4"><title><?= et_e($row['day']) ?>: <?= number_format((int) $row['views']) ?> views</title></circle>
                        <circle class="traffic-point traffic-point-visitors" cx="<?= $row['x'] ?>" cy="<?= $row['visitors_y'] ?>" r="4"><title><?= et_e($row['day']) ?>: <?= number_format((int) $row['visitors']) ?> visitors</title></circle>
                    <?php endforeach; ?>
                </svg>
            </div>
        <?php endif; ?>
    </section>
    <section class="admin-card monitoring-top-pages">
        <div class="top-pages-heading"><h2>Top pages · siku 30</h2><small>Views</small></div>
        <?php if (!$topPages): ?><div class="empty-state">Bado hakuna data.</div><?php else: ?><ul class="admin-list"><?php foreach ($topPages as $row): ?><li><div><strong class="path-text"><?= et_e($row['path']) ?></strong><small><?= number_format((int)$row['unique_page_visitors']) ?> unique page visits</small></div><strong><?= number_format((int)$row['views']) ?></strong></li><?php endforeach; ?></ul><?php endif; ?>
    </section>
</div>
<section style="margin-top:22px">
<div class="admin-page-heading"><div><h2 style="margin:0">System events</h2><p><?= number_format($totalEvents) ?> events zinazolingana; matukio yanayofanana yanaunganishwa na `occurrences`.</p></div></div>
<form method="get" class="form-section compact-filter"><div class="form-field"><label for="status">Status</label><select id="status" name="status"><option value="open"<?= $eventStatus==='open'?' selected':'' ?>>Open</option><option value="resolved"<?= $eventStatus==='resolved'?' selected':'' ?>>Resolved</option><option value="all"<?= $eventStatus==='all'?' selected':'' ?>>Zote</option></select></div><div class="form-field"><label for="severity">Severity</label><select id="severity" name="severity"><option value="all">Zote</option><?php foreach (['info','warning','error','critical'] as $level): ?><option value="<?= $level ?>"<?= $severity===$level?' selected':'' ?>><?= ucfirst($level) ?></option><?php endforeach; ?></select></div><button class="admin-button secondary small" type="submit">Chuja</button></form>
<div class="table-wrap"><table class="admin-table"><thead><tr><th>Event</th><th>Location/source</th><th>Frequency</th><th>Action</th></tr></thead><tbody>
<?php if (!$events): ?><tr><td colspan="4" class="empty-state">Hakuna system events zinazolingana.</td></tr><?php endif; ?>
<?php foreach ($events as $event): ?><tr><td><span class="severity-badge severity-<?= et_e($event['severity']) ?>"><?= et_e(strtoupper($event['severity'])) ?></span><strong><?= et_e(str_replace('_',' ',$event['event_type'])) ?></strong><small><?= et_e($event['message']) ?></small><?php if ($event['http_status']): ?><small>HTTP <?= (int)$event['http_status'] ?> <?= et_e($event['error_code']) ?></small><?php endif; ?></td><td><span class="path-text"><?= et_e($event['request_path']) ?></span><?php if ($event['target']!==''): ?><small><?= et_e($event['target']) ?></small><?php endif; ?><?php if ($event['exam_type']!==''): ?><small>Exam: <?= et_e($event['exam_type']) ?></small><?php endif; ?></td><td><strong><?= number_format((int)$event['occurrences']) ?>×</strong><small>First: <?= et_e($event['first_seen_at']) ?> UTC</small><small>Last: <?= et_e($event['last_seen_at']) ?> UTC</small></td><td><form method="post" action="update.php"><input type="hidden" name="csrf_token" value="<?= et_e(et_csrf_token()) ?>"><input type="hidden" name="id" value="<?= (int)$event['id'] ?>"><input type="hidden" name="status" value="<?= $event['status']==='open'?'resolved':'open' ?>"><input type="hidden" name="return_status" value="<?= et_e($eventStatus) ?>"><input type="hidden" name="return_severity" value="<?= et_e($severity) ?>"><input type="hidden" name="return_page" value="<?= $page ?>"><button class="admin-button <?= $event['status']==='open'?'':'secondary' ?> small" type="submit"><?= $event['status']==='open'?'Mark resolved':'Reopen' ?></button></form></td></tr><?php endforeach; ?>
</tbody></table></div>
<?php if ($totalPages>1): ?><nav class="admin-pagination"><?php if($page>1):?><a href="?status=<?= et_e(rawurlencode($eventStatus)) ?>&severity=<?= et_e(rawurlencode($severity)) ?>&page=<?= $page-1 ?>">← Nyuma</a><?php endif;?><span>Ukurasa <?= $page ?> / <?= $totalPages ?></span><?php if($page<$totalPages):?><a href="?status=<?= et_e(rawurlencode($eventStatus)) ?>&severity=<?= et_e(rawurlencode($severity)) ?>&page=<?= $page+1 ?>">Mbele →</a><?php endif;?></nav><?php endif;?>
</section>
<?php et_admin_footer(); ?>
