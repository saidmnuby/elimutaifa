<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/includes/development.php';
$server = ['HTTP_HOST' => 'localhost', 'REMOTE_ADDR' => '127.0.0.1', 'SERVER_ADDR' => '127.0.0.1'];
$config = ['environment' => 'local', 'allow_owner_without_2fa' => true, 'expires_at' => time() + 60];
$originalEnvironment = getenv('ELIMUTAIFA_APP_ENV');
putenv('ELIMUTAIFA_APP_ENV');
try {
    $cases = [
        [true, $server, $config],
        [true, array_replace($server, ['HTTP_HOST' => 'localhost:8080']), $config],
        [true, ['HTTP_HOST' => '[::1]', 'REMOTE_ADDR' => '::1', 'SERVER_ADDR' => '::1'], $config],
        [false, $server, []],
        [false, $server, array_replace($config, ['environment' => 'production'])],
        [false, $server, array_replace($config, ['expires_at' => time() - 1])],
        [false, $server, array_replace($config, ['allow_owner_without_2fa' => false])],
        [false, array_replace($server, ['REMOTE_ADDR' => '192.168.1.20']), $config],
        [false, array_replace($server, ['SERVER_ADDR' => '192.168.1.20']), $config],
        [false, array_replace($server, ['HTTP_HOST' => 'elimutaifa.com']), $config],
        [false, array_replace($server, ['HTTP_HOST' => 'localhost.evil.example']), $config],
        [false, array_replace($server, ['HTTP_X_FORWARDED_FOR' => '127.0.0.1']), $config],
        [false, array_replace($server, ['HTTP_FORWARDED' => 'for=127.0.0.1']), $config],
        [false, [], $config],
    ];
    foreach ($cases as $index => [$expected, $request, $settings]) {
        if (et_local_development_access($request, $settings) !== $expected) {
            throw new RuntimeException('Development guard case failed: ' . $index);
        }
    }
    putenv('ELIMUTAIFA_APP_ENV=production');
    putenv('ELIMUTAIFA_APP_ENV=local');
    if (!et_owner_development_exception(['role' => 'owner'], $server, $config)
        || et_owner_development_exception(['role' => 'admin'], $server, $config)
        || et_owner_development_exception([], $server, $config)) {
        throw new RuntimeException('Only owner may use the development exception.');
    }
    putenv('ELIMUTAIFA_APP_ENV=production');
    if (et_local_development_access($server, $config)) { throw new RuntimeException('Production override failed.'); }
    echo "PASS: explicit local mode, expiry, IPv4/IPv6, remote clients, host spoofing, proxy headers and production override.\n";
} finally {
    putenv($originalEnvironment === false ? 'ELIMUTAIFA_APP_ENV' : 'ELIMUTAIFA_APP_ENV=' . $originalEnvironment);
}
