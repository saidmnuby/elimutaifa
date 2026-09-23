<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/includes/monitoring.php';
$db = new PDO('sqlite::memory:', null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
et_migrate_database($db);
function check_success(bool $condition, string $message): void {
    if (!$condition) throw new RuntimeException($message);
}
$_SERVER['REMOTE_ADDR'] = '192.0.2.1';
$_SERVER['HTTP_USER_AGENT'] = 'Test browser';
$count = static fn(): int => (int) $db->query("SELECT COUNT(DISTINCT visitor_hash) FROM traffic_unique_visitors WHERE path='@event/search-success'")->fetchColumn();
et_record_search_success($db);
et_record_search_success($db);
check_success($count() === 1, 'Repeated success must be deduplicated');
$_SERVER['REMOTE_ADDR'] = '192.0.2.2';
et_record_search_success($db);
check_success($count() === 2, 'Different visitor must be counted');
$_SERVER['REMOTE_ADDR'] = '192.0.2.3';
$_SERVER['HTTP_DNT'] = '1';
et_record_search_success($db);
check_success($count() === 2, 'DNT must be respected');
unset($_SERVER['HTTP_DNT']);
$_SERVER['HTTP_USER_AGENT'] = 'Googlebot';
et_record_search_success($db);
check_success($count() === 2, 'Bots must be excluded');
check_success((int) $db->query('SELECT COUNT(*) FROM traffic_daily')->fetchColumn() === 0, 'Success must not inflate page views');
echo "Search success monitoring: PASS\n";
