<?php
declare(strict_types=1);

function et_database_path(): string
{
    $configuredPath = getenv('ELIMUTAIFA_DB_PATH');
    if (is_string($configuredPath) && trim($configuredPath) !== '') {
        return $configuredPath;
    }

    return dirname(__DIR__) . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'elimutaifa.sqlite';
}

function et_open_database(string $path): PDO
{
    $directory = dirname($path);
    if (!is_dir($directory) && !mkdir($directory, 0700, true) && !is_dir($directory)) {
        throw new RuntimeException('Unable to create the application storage directory.');
    }

    $database = new PDO('sqlite:' . $path, null, null, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
    $database->exec('PRAGMA foreign_keys = ON');
    $database->exec('PRAGMA busy_timeout = 5000');
    et_migrate_database($database);

    return $database;
}

function et_db(): PDO
{
    static $database = null;
    if (!$database instanceof PDO) {
        if (is_file(dirname(__DIR__) . '/storage/database-migration.lock')) {
            throw new RuntimeException('Database maintenance is in progress. Please try again shortly.');
        }
        $config = et_database_config();
        $database = $config['driver'] === 'mysql'
            ? et_open_mysql_database($config)
            : et_open_database(et_database_path());
    }

    return $database;
}

function et_database_config(): array
{
    $file = dirname(__DIR__) . '/storage/database.php';
    $config = is_file($file) ? require $file : [];
    if (!is_array($config)) {
        throw new RuntimeException('Invalid database configuration.');
    }
    foreach (['driver', 'host', 'port', 'name', 'user', 'password'] as $key) {
        $value = getenv('ELIMUTAIFA_DB_' . strtoupper($key));
        if ($value !== false) {
            $config[$key] = $value;
        }
    }
    $config += ['driver' => 'sqlite', 'host' => '127.0.0.1', 'port' => '3306', 'name' => 'elimutaifa', 'user' => '', 'password' => ''];
    if (!in_array($config['driver'], ['sqlite', 'mysql'], true)) {
        throw new RuntimeException('Unsupported database driver.');
    }
    return $config;
}

function et_open_mysql_database(array $config): PDO
{
    if (!preg_match('/^[a-zA-Z0-9_]+$/D', (string) $config['name'])
        || !ctype_digit((string) $config['port'])
        || !preg_match('/^[a-zA-Z0-9.:-]+$/D', (string) $config['host'])) {
        throw new RuntimeException('Invalid MySQL connection settings.');
    }
    $database = new PDO(
        'mysql:host=' . $config['host'] . ';port=' . $config['port'] . ';dbname=' . $config['name'] . ';charset=utf8mb4',
        $config['user'], $config['password'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
         PDO::ATTR_EMULATE_PREPARES => false]
    );
    $database->exec("SET time_zone = '+00:00'");
    return $database;
}

/** Translate the application's conflict statements; keep SQLite available for isolated tests. */
function et_conflict_sql(PDO $database, string $sql): string
{
    if ($database->getAttribute(PDO::ATTR_DRIVER_NAME) !== 'mysql') {
        return $sql;
    }
    $sql = preg_replace('/ON CONFLICT\(([^)]+)\) DO NOTHING/', 'ON DUPLICATE KEY UPDATE $1=$1', $sql);
    $sql = preg_replace('/ON CONFLICT\([^)]+\) DO UPDATE SET/', 'ON DUPLICATE KEY UPDATE', $sql);
    $sql = preg_replace('/excluded\.([a-z_]+)/i', 'VALUES($1)', $sql);
    // No-op duplicate update reports zero affected rows, unlike INSERT IGNORE it does not hide invalid data.
    if (str_contains($sql, 'INSERT OR IGNORE INTO traffic_unique_visitors')) {
        $sql = str_replace('INSERT OR IGNORE', 'INSERT', $sql) . ' ON DUPLICATE KEY UPDATE visitor_hash=visitor_hash';
    }
    return $sql;
}

function et_migrate_database(PDO $database): void
{
    $database->exec(<<<'SQL'
CREATE TABLE IF NOT EXISTS admin_users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    username TEXT NOT NULL COLLATE NOCASE UNIQUE,
    display_name TEXT NOT NULL,
    password_hash TEXT NOT NULL,
    role TEXT NOT NULL DEFAULT 'admin',
    is_active INTEGER NOT NULL DEFAULT 1,
    deleted_at TEXT,
    last_login_at TEXT,
    created_at TEXT NOT NULL,
    updated_at TEXT NOT NULL
);

CREATE TABLE IF NOT EXISTS content_items (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    category TEXT NOT NULL,
    title TEXT NOT NULL,
    slug TEXT NOT NULL COLLATE NOCASE UNIQUE,
    excerpt TEXT NOT NULL,
    body TEXT NOT NULL,
    media_type TEXT NOT NULL DEFAULT 'none',
    media_url TEXT NOT NULL DEFAULT '',
    media_caption TEXT NOT NULL DEFAULT '',
    audience TEXT NOT NULL DEFAULT 'all',
    source_name TEXT NOT NULL DEFAULT '',
    source_url TEXT NOT NULL DEFAULT '',
    destination_type TEXT NOT NULL DEFAULT 'internal',
    external_url TEXT NOT NULL DEFAULT '',
    status TEXT NOT NULL DEFAULT 'draft',
    is_featured INTEGER NOT NULL DEFAULT 0,
    is_popup INTEGER NOT NULL DEFAULT 0,
    published_at TEXT,
    expires_at TEXT,
    created_by INTEGER NOT NULL,
    updated_by INTEGER NOT NULL,
    created_at TEXT NOT NULL,
    updated_at TEXT NOT NULL,
    FOREIGN KEY (created_by) REFERENCES admin_users(id),
    FOREIGN KEY (updated_by) REFERENCES admin_users(id)
);

CREATE INDEX IF NOT EXISTS idx_content_publication
ON content_items (status, published_at, expires_at, is_featured);

CREATE TABLE IF NOT EXISTS submissions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    topic TEXT NOT NULL,
    sender_role TEXT NOT NULL,
    contact TEXT NOT NULL DEFAULT '',
    message TEXT NOT NULL,
    status TEXT NOT NULL DEFAULT 'new',
    admin_note TEXT NOT NULL DEFAULT '',
    ip_hash TEXT NOT NULL,
    created_at TEXT NOT NULL,
    updated_at TEXT NOT NULL
);

CREATE INDEX IF NOT EXISTS idx_submissions_status
ON submissions (status, created_at);

CREATE TABLE IF NOT EXISTS login_attempts (
    attempt_key TEXT PRIMARY KEY,
    attempts INTEGER NOT NULL,
    first_attempt_at INTEGER NOT NULL,
    blocked_until INTEGER NOT NULL DEFAULT 0
);

CREATE TABLE IF NOT EXISTS audit_logs (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    admin_user_id INTEGER,
    action TEXT NOT NULL,
    entity_type TEXT NOT NULL,
    entity_id INTEGER,
    details TEXT NOT NULL DEFAULT '',
    created_at TEXT NOT NULL,
    FOREIGN KEY (admin_user_id) REFERENCES admin_users(id)
);

CREATE INDEX IF NOT EXISTS idx_audit_created
ON audit_logs (created_at);
CREATE INDEX IF NOT EXISTS idx_audit_action
ON audit_logs (action, created_at);

CREATE TABLE IF NOT EXISTS traffic_daily (
    day TEXT NOT NULL,
    path TEXT NOT NULL,
    views INTEGER NOT NULL DEFAULT 0,
    unique_visitors INTEGER NOT NULL DEFAULT 0,
    last_view_at TEXT NOT NULL,
    PRIMARY KEY (day, path)
);

CREATE TABLE IF NOT EXISTS traffic_unique_visitors (
    day TEXT NOT NULL,
    path TEXT NOT NULL,
    visitor_hash TEXT NOT NULL,
    PRIMARY KEY (day, path, visitor_hash)
);

CREATE TABLE IF NOT EXISTS traffic_recent (
    visitor_hash TEXT NOT NULL,
    path TEXT NOT NULL,
    last_recorded_at INTEGER NOT NULL,
    PRIMARY KEY (visitor_hash, path)
);

CREATE INDEX IF NOT EXISTS idx_traffic_day ON traffic_daily (day);

CREATE TABLE IF NOT EXISTS system_events (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    fingerprint TEXT NOT NULL UNIQUE,
    severity TEXT NOT NULL,
    event_type TEXT NOT NULL,
    exam_type TEXT NOT NULL DEFAULT '',
    request_path TEXT NOT NULL DEFAULT '',
    target TEXT NOT NULL DEFAULT '',
    http_status INTEGER,
    error_code TEXT NOT NULL DEFAULT '',
    message TEXT NOT NULL,
    occurrences INTEGER NOT NULL DEFAULT 1,
    status TEXT NOT NULL DEFAULT 'open',
    first_seen_at TEXT NOT NULL,
    last_seen_at TEXT NOT NULL,
    resolved_at TEXT
);

CREATE INDEX IF NOT EXISTS idx_system_events_status_seen
ON system_events (status, last_seen_at);
CREATE INDEX IF NOT EXISTS idx_system_events_type_seen
ON system_events (event_type, last_seen_at);

CREATE TABLE IF NOT EXISTS app_settings (
    setting_key TEXT PRIMARY KEY,
    setting_value TEXT NOT NULL,
    updated_at TEXT NOT NULL
);
SQL);

    et_add_column_if_missing($database, 'admin_users', 'role', "TEXT NOT NULL DEFAULT 'admin'");
    et_add_column_if_missing($database, 'admin_users', 'deleted_at', 'TEXT');
    et_add_column_if_missing($database, 'content_items', 'media_type', "TEXT NOT NULL DEFAULT 'none'");
    et_add_column_if_missing($database, 'content_items', 'media_url', "TEXT NOT NULL DEFAULT ''");
    et_add_column_if_missing($database, 'content_items', 'media_caption', "TEXT NOT NULL DEFAULT ''");
    $database->exec(<<<'SQL'
UPDATE admin_users
SET role = 'owner'
WHERE id = (SELECT MIN(id) FROM admin_users WHERE is_active = 1 AND deleted_at IS NULL)
  AND NOT EXISTS (SELECT 1 FROM admin_users WHERE role = 'owner' AND is_active = 1 AND deleted_at IS NULL)
SQL);
}

function et_add_column_if_missing(PDO $database, string $table, string $column, string $definition): void
{
    $columns = $database->query('PRAGMA table_info(' . $table . ')')->fetchAll();
    foreach ($columns as $existingColumn) {
        if (($existingColumn['name'] ?? '') === $column) {
            return;
        }
    }
    $database->exec('ALTER TABLE ' . $table . ' ADD COLUMN ' . $column . ' ' . $definition);
}

function et_utc_now(): string
{
    return gmdate('Y-m-d H:i:s');
}
