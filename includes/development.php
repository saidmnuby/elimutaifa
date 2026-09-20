<?php
declare(strict_types=1);

function et_owner_development_exception(array $user, ?array $server = null, ?array $config = null): bool
{
    return ($user['role'] ?? '') === 'owner' && et_local_development_access($server, $config);
}

/** Fail closed: an explicit, expiring local setting AND a direct loopback request are required. */
function et_local_development_access(?array $server = null, ?array $config = null): bool
{
    $server ??= $_SERVER;
    if ($config === null) {
        $file = dirname(__DIR__) . '/storage/development.php';
        $config = is_file($file) ? require $file : [];
    }
    if (!is_array($config)) { return false; }
    $environment = getenv('ELIMUTAIFA_APP_ENV');
    $environment = $environment === false ? ($config['environment'] ?? 'production') : $environment;
    if ($environment !== 'local' || ($config['allow_owner_without_2fa'] ?? false) !== true
        || !is_int($config['expires_at'] ?? null) || $config['expires_at'] <= time()) {
        return false;
    }
    foreach (['HTTP_FORWARDED', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED_HOST', 'HTTP_X_REAL_IP'] as $header) {
        if (array_key_exists($header, $server)) { return false; }
    }
    $loopback = ['127.0.0.1', '::1'];
    $host = parse_url('http://' . (string) ($server['HTTP_HOST'] ?? ''), PHP_URL_HOST);
    return in_array($server['REMOTE_ADDR'] ?? '', $loopback, true)
        && in_array($server['SERVER_ADDR'] ?? '', $loopback, true)
        && in_array(strtolower((string) $host), ['localhost', '127.0.0.1', '[::1]'], true);
}
