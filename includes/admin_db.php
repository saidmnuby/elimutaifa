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
        $database = et_open_database(et_database_path());
    }

    return $database;
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
