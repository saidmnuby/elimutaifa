<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once dirname(__DIR__) . '/includes/admin_db.php';
function et_setup_placements(PDO $database): void {
    $mysql=$database->getAttribute(PDO::ATTR_DRIVER_NAME)==='mysql';
    $id=$mysql?'BIGINT AUTO_INCREMENT PRIMARY KEY':'INTEGER PRIMARY KEY AUTOINCREMENT';
    $fk=$mysql?'BIGINT':'INTEGER';
    $database->exec('CREATE TABLE IF NOT EXISTS placement_items (
        id '.$id.', title VARCHAR(140) NOT NULL, description TEXT NOT NULL,
        kind VARCHAR(20) NOT NULL, sponsor_name VARCHAR(120) NOT NULL, format VARCHAR(20) NOT NULL,
        slot VARCHAR(20) NOT NULL, targets TEXT NOT NULL, priority INTEGER NOT NULL DEFAULT 0,
        popup_style VARCHAR(20) NOT NULL DEFAULT \'corner\', display_mode VARCHAR(20) NOT NULL DEFAULT \'session\',
        skip_delay INTEGER NOT NULL DEFAULT 0,
        image_url VARCHAR(2048) NOT NULL, destination_type VARCHAR(20) NOT NULL,
        external_url VARCHAR(2048) NOT NULL, content_id '.$fk.' NULL,
        status VARCHAR(20) NOT NULL, starts_at DATETIME NULL, ends_at DATETIME NULL,
        created_by '.$fk.' NOT NULL, updated_by '.$fk.' NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL,
        FOREIGN KEY(created_by) REFERENCES admin_users(id), FOREIGN KEY(updated_by) REFERENCES admin_users(id),
        FOREIGN KEY(content_id) REFERENCES content_items(id) ON DELETE SET NULL
    )'.($mysql?' ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin':''));
    $columns=$mysql
        ? array_column($database->query('SHOW COLUMNS FROM placement_items')->fetchAll(), 'Field')
        : array_column($database->query('PRAGMA table_info(placement_items)')->fetchAll(), 'name');
    foreach (['popup_style'=>"VARCHAR(20) NOT NULL DEFAULT 'corner'",'display_mode'=>"VARCHAR(20) NOT NULL DEFAULT 'session'",'skip_delay'=>'INTEGER NOT NULL DEFAULT 0'] as $column=>$definition) {
        if (!in_array($column,$columns,true)) { $database->exec('ALTER TABLE placement_items ADD COLUMN '.$column.' '.$definition); }
    }
    if ($mysql) {
        $check=$database->query("SHOW INDEX FROM placement_items WHERE Key_name='idx_placement_public'");
        if (!$check->fetch()) { $database->exec('CREATE INDEX idx_placement_public ON placement_items(status,priority,starts_at,ends_at)'); }
    } else { $database->exec('CREATE INDEX IF NOT EXISTS idx_placement_public ON placement_items(status,priority,starts_at,ends_at)'); }
}
if (realpath((string)($_SERVER['SCRIPT_FILENAME']??''))===__FILE__) { et_setup_placements(et_db()); echo "PASS: placement schema available.\n"; }
