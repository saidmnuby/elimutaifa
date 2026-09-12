<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once dirname(__DIR__) . '/includes/admin_db.php';

$username = trim((string) ($argv[1] ?? ''));
$displayName = trim((string) ($argv[2] ?? ''));
$password = getenv('ELIMUTAIFA_ADMIN_PASSWORD');

if (!preg_match('/^[a-zA-Z0-9._-]{3,50}$/', $username)) {
    fwrite(STDERR, "Usage: set ELIMUTAIFA_ADMIN_PASSWORD, then run: php scripts/create_admin.php username \"Display Name\"\n");
    fwrite(STDERR, "Username must contain 3-50 letters, numbers, dots, underscores, or hyphens.\n");
    exit(1);
}
if ($displayName === '' || mb_strlen($displayName) > 80) {
    fwrite(STDERR, "Display name is required and must not exceed 80 characters.\n");
    exit(1);
}
if (!is_string($password) || strlen($password) < 12 || strlen($password) > 200) {
    fwrite(STDERR, "ELIMUTAIFA_ADMIN_PASSWORD must contain 12-200 characters.\n");
    exit(1);
}

$database = et_db();
$existingAdminCount = (int) $database->query("SELECT COUNT(*) FROM admin_users WHERE deleted_at IS NULL")->fetchColumn();
$role = $existingAdminCount === 0 ? 'owner' : 'admin';
$statement = $database->prepare('SELECT COUNT(*) FROM admin_users WHERE username = :username');
$statement->execute(['username' => $username]);
if ((int) $statement->fetchColumn() > 0) {
    fwrite(STDERR, "That admin username already exists.\n");
    exit(1);
}

$now = et_utc_now();
$statement = $database->prepare(<<<'SQL'
INSERT INTO admin_users (username, display_name, password_hash, role, is_active, created_at, updated_at)
VALUES (:username, :display_name, :password_hash, :role, 1, :created_at, :updated_at)
SQL);
$statement->execute([
    'username' => $username,
    'display_name' => $displayName,
    'password_hash' => password_hash($password, PASSWORD_DEFAULT),
    'role' => $role,
    'created_at' => $now,
    'updated_at' => $now,
]);

@chmod(et_database_path(), 0600);
echo "Admin account created for {$username}.\n";
echo "Role: {$role}\n";
echo "Open http://localhost/get-results-faster/admin/login.php\n";
