<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/admin_db.php';
$requiredExtensions = ['curl', 'dom', 'fileinfo', 'libxml', 'pdo',
    et_database_config()['driver'] === 'mysql' ? 'pdo_mysql' : 'pdo_sqlite'];
$missing = array_filter($requiredExtensions, static fn (string $extension): bool => !extension_loaded($extension));

if ($missing !== []) {
    fwrite(STDERR, 'Missing PHP extensions: ' . implode(', ', $missing) . PHP_EOL);
    exit(1);
}

$storageDirectory = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'storage';
if (!is_dir($storageDirectory) || !is_writable($storageDirectory)) {
    fwrite(STDERR, "Storage directory is missing or not writable\n");
    exit(1);
}

$uploadDirectory = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'content';
if (!is_dir($uploadDirectory) || !is_writable($uploadDirectory)) {
    fwrite(STDERR, "Content image upload directory is missing or not writable\n");
    exit(1);
}

try {
    et_db()->query('SELECT COUNT(*) FROM admin_users')->fetchColumn();
    foreach (['form_one_cycles','form_five_cycles'] as $table) {
        et_db()->query('SELECT cycle_key,status,verified_at FROM '.$table.' LIMIT 1')->fetch();
    }
} catch (Throwable $exception) {
    fwrite(STDERR, "Database connection/schema check failed. Check protected database configuration and server logs.\n");
    exit(1);
}
echo "PASS: required PHP extensions, database, storage, and content uploads are available\n";
