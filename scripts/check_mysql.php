<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once dirname(__DIR__) . '/includes/admin_auth.php';
require_once dirname(__DIR__) . '/includes/content.php';

if (in_array('--setup-account', $argv, true)) {
    putenv('ELIMUTAIFA_DB_DRIVER=mysql');
    putenv('ELIMUTAIFA_DB_USER=' . (getenv('ELIMUTAIFA_MIGRATION_USER') ?: 'root'));
    putenv('ELIMUTAIFA_DB_PASSWORD=' . (getenv('ELIMUTAIFA_MIGRATION_PASSWORD') ?: ''));
}

// All diagnostic writes are rolled back. Use a configured MySQL connection, never production credentials on CLI.
try {
    $database = et_db();
    if ($database->getAttribute(PDO::ATTR_DRIVER_NAME) !== 'mysql') {
        throw new RuntimeException('This check requires a MySQL connection.');
    }
    $database->beginTransaction();
    $key = 'migration-check-' . bin2hex(random_bytes(8));
    et_record_login_failure($database, $key, false, time());
    et_record_login_failure($database, $key, ['attempts' => 1, 'first_attempt_at' => time()], time());
    $statement = $database->prepare('SELECT attempts FROM login_attempts WHERE attempt_key=?');
    $statement->execute([$key]);
    if ((int) $statement->fetchColumn() !== 2) { throw new RuntimeException('Login conflict update failed.'); }
    et_monitoring_secret($database);
    $path = '/' . $key;
    $uniqueSql = et_conflict_sql($database, 'INSERT OR IGNORE INTO traffic_unique_visitors (day,path,visitor_hash) VALUES (?,?,?)');
    $unique = $database->prepare($uniqueSql);
    $unique->execute([gmdate('Y-m-d'), $path, str_repeat('a', 64)]);
    if ($unique->rowCount() !== 1) { throw new RuntimeException('Visitor insertion failed.'); }
    $unique->execute([gmdate('Y-m-d'), $path, str_repeat('a', 64)]);
    if ($unique->rowCount() !== 0) { throw new RuntimeException('Visitor duplicate handling failed.'); }
    $now = et_utc_now();
    foreach ([1, 2] as $iteration) {
        $database->prepare(et_conflict_sql($database, <<<'SQL'
INSERT INTO traffic_daily(day,path,views,unique_visitors,last_view_at) VALUES(?,?,1,1,?)
ON CONFLICT(day,path) DO UPDATE SET views=traffic_daily.views+1,
unique_visitors=traffic_daily.unique_visitors+excluded.unique_visitors,last_view_at=excluded.last_view_at
SQL))->execute([gmdate('Y-m-d'), $path, $now]);
    }
    $statement = $database->prepare('SELECT views FROM traffic_daily WHERE day=? AND path=?');
    $statement->execute([gmdate('Y-m-d'), $path]);
    if ((int) $statement->fetchColumn() !== 2) { throw new RuntimeException('Traffic aggregation failed.'); }
    et_record_system_event('migration_check', $key, 'info', ['request_path' => $path]);
    et_record_system_event('migration_check', $key, 'info', ['request_path' => $path]);
    $statement = $database->prepare('SELECT occurrences FROM system_events WHERE event_type=? AND request_path=?');
    $statement->execute(['migration_check', $path]);
    if ((int) $statement->fetchColumn() !== 2) { throw new RuntimeException('Event aggregation failed.'); }
    et_release_scheduled_content($database);
    et_build_sitemap_xml($database);
    $database->rollBack();
    echo "PASS: MySQL login throttling, unique visitors, traffic counters, system events, publication and sitemap queries; writes rolled back.\n";
} catch (Throwable $exception) {
    if (isset($database) && $database->inTransaction()) { $database->rollBack(); }
    fwrite(STDERR, 'FAIL: ' . $exception->getMessage() . "\n"); exit(1);
}
