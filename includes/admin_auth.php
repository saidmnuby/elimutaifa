<?php
declare(strict_types=1);

require_once __DIR__ . '/session.php';
require_once __DIR__ . '/admin_db.php';
require_once __DIR__ . '/monitoring.php';
et_register_fatal_error_monitoring();

const ET_ADMIN_IDLE_TIMEOUT = 1800;
const ET_ADMIN_ABSOLUTE_TIMEOUT = 28800;

function et_admin_boot(): void
{
    grf_start_session('Strict', 'elimutaifa_admin');
    header('X-Robots-Tag: noindex, nofollow', true);
    header('Cache-Control: no-store, max-age=0', true);

    if (!isset($_SESSION['et_admin_id'])) {
        return;
    }

    $now = time();
    $startedAt = (int) ($_SESSION['et_admin_started_at'] ?? 0);
    $lastActivity = (int) ($_SESSION['et_admin_last_activity'] ?? 0);
    if (
        $startedAt <= 0
        || $lastActivity <= 0
        || $now - $startedAt > ET_ADMIN_ABSOLUTE_TIMEOUT
        || $now - $lastActivity > ET_ADMIN_IDLE_TIMEOUT
    ) {
        et_admin_logout();
        et_flash('error', 'Kikao chako kimeisha. Ingia tena.');
        return;
    }

    $_SESSION['et_admin_last_activity'] = $now;
}

function et_admin_user(): ?array
{
    $userId = (int) ($_SESSION['et_admin_id'] ?? 0);
    if ($userId <= 0) {
        return null;
    }

    $statement = et_db()->prepare(
        'SELECT id, username, display_name, role, is_active, last_login_at, deleted_at FROM admin_users WHERE id = :id LIMIT 1'
    );
    $statement->execute(['id' => $userId]);
    $user = $statement->fetch();

    if (!$user || (int) $user['is_active'] !== 1 || $user['deleted_at'] !== null) {
        et_admin_logout();
        return null;
    }

    return $user;
}

function et_require_owner(array $user): void
{
    if (($user['role'] ?? '') !== 'owner') {
        http_response_code(403);
        exit('Owner access required.');
    }
}

function et_require_admin(): array
{
    $user = et_admin_user();
    if ($user === null) {
        et_redirect('../login.php');
    }

    return $user;
}

function et_require_admin_at_root(): array
{
    $user = et_admin_user();
    if ($user === null) {
        et_redirect('login.php');
    }

    return $user;
}

function et_attempt_admin_login(string $username, string $password): array
{
    $username = trim($username);
    $attemptKey = hash('sha256', strtolower($username) . '|' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
    $now = time();
    $database = et_db();

    $attemptStatement = $database->prepare(
        'SELECT attempts, first_attempt_at, blocked_until FROM login_attempts WHERE attempt_key = :attempt_key'
    );
    $attemptStatement->execute(['attempt_key' => $attemptKey]);
    $attempt = $attemptStatement->fetch();
    if ($attempt && (int) $attempt['blocked_until'] > $now) {
        return ['ok' => false, 'message' => 'Jaribio limezuiwa kwa muda. Subiri dakika chache kisha ujaribu tena.'];
    }

    $statement = $database->prepare(
        'SELECT id, username, display_name, password_hash, role, is_active, deleted_at FROM admin_users WHERE username = :username LIMIT 1'
    );
    $statement->execute(['username' => $username]);
    $user = $statement->fetch();
    $validPassword = $user
        ? password_verify($password, (string) $user['password_hash'])
        : password_verify($password, '$2y$10$4Vq5R9WVeFuOtFEYz7f0hOzrL6m6vQIUZMMs3N.8iV7z5WF5mB9Qq');

    if (!$user || (int) $user['is_active'] !== 1 || $user['deleted_at'] !== null || !$validPassword) {
        et_record_login_failure($database, $attemptKey, $attempt, $now);
        et_audit(null, 'login_failed', 'admin_user', null, 'Username: ' . mb_substr($username, 0, 80));
        return ['ok' => false, 'message' => 'Jina la mtumiaji au nenosiri si sahihi.'];
    }

    $database->prepare('DELETE FROM login_attempts WHERE attempt_key = :attempt_key')
        ->execute(['attempt_key' => $attemptKey]);

    if (password_needs_rehash((string) $user['password_hash'], PASSWORD_DEFAULT)) {
        $database->prepare('UPDATE admin_users SET password_hash = :password_hash, updated_at = :updated_at WHERE id = :id')
            ->execute([
                'password_hash' => password_hash($password, PASSWORD_DEFAULT),
                'updated_at' => et_utc_now(),
                'id' => (int) $user['id'],
            ]);
    }

    session_regenerate_id(true);
    $_SESSION['et_admin_id'] = (int) $user['id'];
    $_SESSION['et_admin_started_at'] = $now;
    $_SESSION['et_admin_last_activity'] = $now;
    $database->prepare('UPDATE admin_users SET last_login_at = :last_login_at WHERE id = :id')
        ->execute(['last_login_at' => et_utc_now(), 'id' => (int) $user['id']]);
    et_audit((int) $user['id'], 'login_succeeded', 'admin_user', (int) $user['id']);

    return ['ok' => true, 'message' => ''];
}

function et_record_login_failure(PDO $database, string $attemptKey, array|false $attempt, int $now): void
{
    $window = 900;
    $firstAttempt = $attempt ? (int) $attempt['first_attempt_at'] : $now;
    $attempts = $attempt ? (int) $attempt['attempts'] + 1 : 1;
    if ($now - $firstAttempt > $window) {
        $firstAttempt = $now;
        $attempts = 1;
    }

    $blockedUntil = $attempts >= 5 ? $now + $window : 0;
    $statement = $database->prepare(<<<'SQL'
INSERT INTO login_attempts (attempt_key, attempts, first_attempt_at, blocked_until)
VALUES (:attempt_key, :attempts, :first_attempt_at, :blocked_until)
ON CONFLICT(attempt_key) DO UPDATE SET
    attempts = excluded.attempts,
    first_attempt_at = excluded.first_attempt_at,
    blocked_until = excluded.blocked_until
SQL);
    $statement->execute([
        'attempt_key' => $attemptKey,
        'attempts' => $attempts,
        'first_attempt_at' => $firstAttempt,
        'blocked_until' => $blockedUntil,
    ]);
}

function et_admin_logout(): void
{
    unset(
        $_SESSION['et_admin_id'],
        $_SESSION['et_admin_started_at'],
        $_SESSION['et_admin_last_activity'],
        $_SESSION['et_csrf_token']
    );
    session_regenerate_id(true);
}

function et_csrf_token(): string
{
    if (!isset($_SESSION['et_csrf_token']) || !is_string($_SESSION['et_csrf_token'])) {
        $_SESSION['et_csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['et_csrf_token'];
}

function et_verify_csrf(?string $token): bool
{
    $stored = $_SESSION['et_csrf_token'] ?? '';
    return is_string($token) && is_string($stored) && $stored !== '' && hash_equals($stored, $token);
}

function et_audit(?int $adminUserId, string $action, string $entityType, ?int $entityId = null, string $details = ''): void
{
    $statement = et_db()->prepare(<<<'SQL'
INSERT INTO audit_logs (admin_user_id, action, entity_type, entity_id, details, created_at)
VALUES (:admin_user_id, :action, :entity_type, :entity_id, :details, :created_at)
SQL);
    $statement->execute([
        'admin_user_id' => $adminUserId,
        'action' => $action,
        'entity_type' => $entityType,
        'entity_id' => $entityId,
        'details' => mb_substr($details, 0, 500),
        'created_at' => et_utc_now(),
    ]);
}

function et_flash(string $type, string $message): void
{
    $_SESSION['et_flash'] = ['type' => $type, 'message' => $message];
}

function et_take_flash(): ?array
{
    $flash = $_SESSION['et_flash'] ?? null;
    unset($_SESSION['et_flash']);
    return is_array($flash) ? $flash : null;
}

function et_redirect(string $location): never
{
    header('Location: ' . $location, true, 303);
    exit;
}

function et_e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
