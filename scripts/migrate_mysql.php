<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
require_once dirname(__DIR__) . '/includes/admin_db.php';

// Never overwrite an existing database. Source SQLite is preserved, including password hashes and IDs.
$tables = ['admin_users', 'content_items', 'submissions', 'login_attempts', 'audit_logs',
    'traffic_daily', 'traffic_unique_visitors', 'traffic_recent', 'system_events', 'app_settings'];
$root = dirname(__DIR__);
$source = null;
$target = null;
$resume = in_array('--resume-local', $argv, true);
try {
    if (!is_file($root . '/storage/database-migration.lock')) {
        throw new RuntimeException('Enable the database maintenance lock before migration.');
    }
    $config = et_database_config();
    $name = (string) $config['name'];
    $user = (string) $config['user'];
    if (!preg_match('/^[a-zA-Z0-9_]+$/D', $name) || !preg_match('/^[a-zA-Z0-9_]+$/D', $user)
        || strlen((string) $config['password']) < 24 || $user === 'root') {
        throw new RuntimeException('Configure a dedicated database/user and a strong password first.');
    }
    if ($config['host'] !== '127.0.0.1' || (string) $config['port'] !== '3306') {
        throw new RuntimeException('This local XAMPP setup script requires 127.0.0.1:3306.');
    }
    if (!is_file(et_database_path())) {
        throw new RuntimeException('Source SQLite database is missing.');
    }
    $server = new PDO('mysql:host=127.0.0.1;port=3306;charset=utf8mb4',
        getenv('ELIMUTAIFA_MIGRATION_USER') ?: 'root', getenv('ELIMUTAIFA_MIGRATION_PASSWORD') ?: '',
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    $check = $server->prepare('SELECT COUNT(*) FROM information_schema.schemata WHERE schema_name=?');
    $check->execute([$name]);
    $exists = (int) $check->fetchColumn() !== 0;
    if ($exists && !$resume) {
        throw new RuntimeException('Target database already exists; refusing to overwrite it.');
    }
    $account = $server->prepare('SELECT COUNT(*) FROM mysql.user WHERE User=?');
    $account->execute([$user]);
    $accountExists = (int) $account->fetchColumn() !== 0;
    if ($accountExists && !$resume) {
        throw new RuntimeException('Target user already exists; refusing to alter its privileges.');
    }
    if ($resume && (!$exists || !$accountExists || $name !== 'elimutaifa' || $user !== 'elimutaifa_app'
        || !is_file($root . '/storage/elimutaifa-before-resume-20260916.sql'))) {
        throw new RuntimeException('Resume requires the known local target/account and its pre-resume backup.');
    }
    $source = new PDO('sqlite:' . et_database_path(), null, null,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
    $source->exec('PRAGMA busy_timeout=10000');
    $backup = $root . '/storage/elimutaifa-before-mysql-' . gmdate('Ymd-His') . '.sqlite';
    $source->exec('VACUUM INTO ' . $source->quote($backup));
    // Exclusive lock drains active readers/writers and prevents concurrent changes throughout the copy.
    $source->exec('BEGIN EXCLUSIVE');
    if (!$resume) {
        $server->exec('CREATE DATABASE `' . $name . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_bin');
    }
    $target = et_open_mysql_database(array_replace($config, [
        'user' => getenv('ELIMUTAIFA_MIGRATION_USER') ?: 'root',
        'password' => getenv('ELIMUTAIFA_MIGRATION_PASSWORD') ?: '',
    ]));
    if (!$resume) {
        $schema = file_get_contents(__DIR__ . '/mysql-schema.sql');
        foreach (explode(';', (string) $schema) as $sql) {
            if (trim($sql) !== '') {
                $target->exec($sql);
            }
        }
    }
    if ($resume) {
        $existingTables = $target->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
        sort($existingTables);
        $expectedTables = $tables;
        sort($expectedTables);
        if ($existingTables !== $expectedTables) {
            throw new RuntimeException('Unexpected target tables; refusing to resynchronise.');
        }
    }
    $target->beginTransaction();
    if ($resume) {
        foreach (array_reverse($tables) as $table) {
            $target->exec('DELETE FROM `' . $table . '`');
        }
    }
    foreach ($tables as $table) {
        $columns = array_column($source->query('PRAGMA table_info(' . $table . ')')->fetchAll(), 'name');
        if ($columns === []) {
            throw new RuntimeException('Source schema is missing ' . $table);
        }
        $columnSql = implode(',', array_map(static fn(string $column): string => '`' . $column . '`', $columns));
        $insert = $target->prepare('INSERT INTO `' . $table . '` (' . $columnSql . ') VALUES ('
            . implode(',', array_fill(0, count($columns), '?')) . ')');
        $sourceRows = $source->query('SELECT ' . $columnSql . ' FROM `' . $table . '`');
        $count = 0;
        while ($row = $sourceRows->fetch(PDO::FETCH_NUM)) {
            $insert->execute($row);
            $count++;
        }
        if ((int) $target->query('SELECT COUNT(*) FROM `' . $table . '`')->fetchColumn() !== $count) {
            throw new RuntimeException('Row count mismatch: ' . $table);
        }
        // Compare every value, not just counts, after normalising PDO numeric return types.
        $order = implode(',', array_map(static fn(string $column): string => '`' . $column . '`', $columns));
        $left = $source->query('SELECT ' . $columnSql . ' FROM `' . $table . '` ORDER BY ' . $order);
        $right = $target->query('SELECT ' . $columnSql . ' FROM `' . $table . '` ORDER BY ' . $order);
        $normalise = static fn(array $row): array => array_map(static fn($value) => $value === null ? null : (string) $value, $row);
        while ($row = $left->fetch(PDO::FETCH_NUM)) {
            $copied = $right->fetch(PDO::FETCH_NUM);
            if ($copied === false || $normalise($row) !== $normalise($copied)) {
                throw new RuntimeException('Value mismatch: ' . $table);
            }
        }
        echo 'VERIFIED ' . $table . ': ' . $count . " rows\n";
    }
    $target->commit();
    $source->exec('ROLLBACK');
    $identity = $server->quote($user) . "@'127.0.0.1'";
    if (!$accountExists) {
        $server->exec('CREATE USER ' . $identity . ' IDENTIFIED BY ' . $server->quote($config['password']));
    }
    $server->exec('GRANT SELECT, INSERT, UPDATE, DELETE ON `' . $name . '`.* TO ' . $identity);
    et_open_mysql_database($config)->query('SELECT COUNT(*) FROM admin_users')->fetchColumn();
    echo "PASS: migration verified; dedicated runtime account connected.\n";
    echo "SQLite original and timestamped backup retained. Set driver=mysql, then remove maintenance lock.\n";
} catch (Throwable $exception) {
    if ($target instanceof PDO && $target->inTransaction()) {
        $target->rollBack();
    }
    if ($source instanceof PDO) {
        try { $source->exec('ROLLBACK'); } catch (Throwable $ignored) {}
    }
    // Do not delete partially provisioned databases automatically; preserve them for diagnosis.
    fwrite(STDERR, 'Migration stopped: ' . $exception->getMessage() . "\nSQLite preserved. Runtime configuration was not switched.\n");
    exit(1);
}
