<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once dirname(__DIR__) . '/includes/admin_db.php';
$database = et_db();
$mysql = $database->getAttribute(PDO::ATTR_DRIVER_NAME) === 'mysql';
$idType = $mysql ? 'BIGINT' : 'INTEGER';
$suffix = $mysql ? ' ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_bin' : '';
$database->exec('CREATE TABLE IF NOT EXISTS admin_two_factor (
 admin_user_id ' . $idType . ' PRIMARY KEY, secret VARCHAR(255) NOT NULL,
 last_counter BIGINT NOT NULL DEFAULT -1, enabled_at VARCHAR(19) NOT NULL,
 FOREIGN KEY(admin_user_id) REFERENCES admin_users(id))' . $suffix);
$database->exec('CREATE TABLE IF NOT EXISTS admin_recovery_codes (
 admin_user_id ' . $idType . ' NOT NULL, code_hash VARCHAR(64) NOT NULL,
 PRIMARY KEY(admin_user_id,code_hash), FOREIGN KEY(admin_user_id) REFERENCES admin_users(id))' . $suffix);
echo "PASS: 2FA schema available.\n";
