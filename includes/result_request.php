<?php
require_once __DIR__ . '/monitoring.php';
et_register_fatal_error_monitoring();

function grf_cached_result_response(string $cacheFile, int $minimumModifiedAt): ?array
{
    if (!is_file($cacheFile)) return null;
    $modifiedAt = filemtime($cacheFile);
    if ($modifiedAt === false || $modifiedAt < $minimumModifiedAt) return null;
    $html = file_get_contents($cacheFile);
    if (!is_string($html) || $html === '') return null;
    return ['html' => $html, 'fetched_at' => $modifiedAt];
}

function grf_fetch_result(string $url): array
{
    $parsedUrl = parse_url($url);
    $allowedHosts = [
        'onlinesys.necta.go.tz',
        'matokeo.necta.go.tz',
        'maktaba.tetea.org',
        'selection.tamisemi.go.tz',
        'selform.tamisemi.go.tz'
    ];

    if (
        !is_array($parsedUrl)
        || ($parsedUrl['scheme'] ?? '') !== 'https'
        || !in_array(strtolower($parsedUrl['host'] ?? ''), $allowedHosts, true)
    ) {
        return ['html' => false, 'status' => 400];
    }
    if (strtolower($parsedUrl['host']) === 'selection.tamisemi.go.tz'
        && (!empty($parsedUrl['user']) || !empty($parsedUrl['pass']) || isset($parsedUrl['port'])
            || !preg_match('#^/allocations/20[0-9]{2}/(?:[a-z0-9-]+/|jis/)#', $parsedUrl['path'] ?? '')
            || str_contains(rawurldecode($parsedUrl['path'] ?? ''), '..'))) {
        return ['html' => false, 'status' => 400];
    }

    if (strtolower($parsedUrl['host']) === 'selform.tamisemi.go.tz'
        && (isset($parsedUrl['user']) || isset($parsedUrl['pass']) || isset($parsedUrl['port']) || isset($parsedUrl['query']) || isset($parsedUrl['fragment'])
            || !preg_match('#^/[Cc]ontent/selection-and-allocation/20[0-9]{2}/[a-z0-9-]+/(?:[a-zA-Z0-9% _-]+/)*[Ii]ndex\\.html$#D', $parsedUrl['path'] ?? '')
            || str_contains(rawurldecode($parsedUrl['path'] ?? ''), '..'))) {
        return ['html' => false, 'status' => 400];
    }
    $cacheDirectory = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'grf-result-cache';
    if (!is_dir($cacheDirectory)) {
        if (!mkdir($cacheDirectory, 0700, true) && !is_dir($cacheDirectory)) {
            et_record_system_event('cache_directory_error', 'The result cache directory could not be created.', 'error');
            return ['html' => false, 'status' => 503];
        }
    }

    $clientKey = hash('sha256', $_SERVER['REMOTE_ADDR'] ?? 'unknown');
    $rateFile = $cacheDirectory . DIRECTORY_SEPARATOR . 'rate-' . $clientKey . '.json';
    $now = time();
    $windowStart = $now - 60;

    // Keep result pages for a day as a fallback, while rate-limit files expire
    // sooner so they cannot accumulate indefinitely.
    if (random_int(1, 100) === 1) {
        foreach (glob($cacheDirectory . DIRECTORY_SEPARATOR . '*') ?: [] as $file) {
            $maximumAge = str_starts_with(basename($file), 'result-') ? 86400 : 3600;
            if (is_file($file) && filemtime($file) < $now - $maximumAge) {
                unlink($file);
            }
        }
    }
    $rateHandle = fopen($rateFile, 'c+');

    if ($rateHandle === false) {
        et_record_system_event('rate_storage_error', 'The per-client rate-limit store could not be opened.', 'error');
        return ['html' => false, 'status' => 503];
    }

    flock($rateHandle, LOCK_EX);
    $rateData = json_decode(stream_get_contents($rateHandle) ?: '[]', true);
    $recentRequests = array_values(array_filter(
        is_array($rateData) ? $rateData : [],
        static fn ($timestamp): bool => is_int($timestamp) && $timestamp >= $windowStart
    ));

    if (count($recentRequests) >= 30) {
        flock($rateHandle, LOCK_UN);
        fclose($rateHandle);
        et_record_system_event('request_rate_limited', 'A client reached the result-request limit.', 'info');
        return ['html' => false, 'status' => 429];
    }

    $recentRequests[] = $now;
    ftruncate($rateHandle, 0);
    rewind($rateHandle);
    fwrite($rateHandle, json_encode($recentRequests));
    fflush($rateHandle);
    flock($rateHandle, LOCK_UN);
    fclose($rateHandle);

    // Keep a process-wide ceiling in addition to the per-client limit.
    $globalRateFile = $cacheDirectory . DIRECTORY_SEPARATOR . 'rate-global.json';
    $globalRateHandle = fopen($globalRateFile, 'c+');
    if ($globalRateHandle === false) {
        et_record_system_event('rate_storage_error', 'The global rate-limit store could not be opened.', 'error');
        return ['html' => false, 'status' => 503];
    }

    flock($globalRateHandle, LOCK_EX);
    $globalRateData = json_decode(stream_get_contents($globalRateHandle) ?: '[]', true);
    $recentGlobalRequests = array_values(array_filter(
        is_array($globalRateData) ? $globalRateData : [],
        static fn ($timestamp): bool => is_int($timestamp) && $timestamp >= $windowStart
    ));

    if (count($recentGlobalRequests) >= 180) {
        flock($globalRateHandle, LOCK_UN);
        fclose($globalRateHandle);
        et_record_system_event('global_rate_limited', 'The service reached the global result-request limit.', 'warning');
        return ['html' => false, 'status' => 429];
    }

    $recentGlobalRequests[] = $now;
    ftruncate($globalRateHandle, 0);
    rewind($globalRateHandle);
    fwrite($globalRateHandle, json_encode($recentGlobalRequests));
    fflush($globalRateHandle);
    flock($globalRateHandle, LOCK_UN);
    fclose($globalRateHandle);

    $cacheFile = $cacheDirectory . DIRECTORY_SEPARATOR . 'result-' . hash('sha256', $url) . '.html';
    $freshCache = grf_cached_result_response($cacheFile, $now - 300);
    if ($freshCache !== null) {
        return $freshCache + ['status' => 200, 'cache_state' => 'fresh'];
    }

    $request = curl_init($url);
    if ($request === false) {
        et_record_system_event('curl_initialization_failed', 'The upstream request could not be initialized.', 'error', ['target_url' => $url]);
        return ['html' => false, 'status' => 503];
    }

    $maximumResponseBytes = 2 * 1024 * 1024;
    curl_setopt_array($request, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_PROTOCOLS => CURLPROTO_HTTPS,
        CURLOPT_CONNECTTIMEOUT => 3,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_ENCODING => '',
        CURLOPT_USERAGENT => '#position for success/1.0',
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
        CURLOPT_NOPROGRESS => false,
        CURLOPT_XFERINFOFUNCTION => static function ($resource, $downloadTotal, $downloaded) use ($maximumResponseBytes): int {
            return $downloaded > $maximumResponseBytes ? 1 : 0;
        }
    ]);
    $html = curl_exec($request);
    $curlErrorCode = curl_errno($request);
    $statusCode = (int) curl_getinfo($request, CURLINFO_RESPONSE_CODE);
    curl_close($request);

    $upstreamUnavailable = $html === false || $html === '' || $statusCode < 200 || $statusCode >= 400;
    if ($upstreamUnavailable) {
        $eventType = $statusCode === 404 ? 'upstream_not_found' : 'upstream_request_failed';
        $severity = $statusCode >= 500 || $statusCode === 0 ? 'error' : 'warning';
        et_record_system_event($eventType, 'The external result source did not return a usable response.', $severity, [
            'target_url' => $url,
            'http_status' => $statusCode,
            'error_code' => $curlErrorCode > 0 ? 'CURL_' . $curlErrorCode : '',
        ]);
    } elseif ($statusCode >= 300) {
        et_record_system_event('upstream_redirect', 'The external result source returned a redirect.', 'warning', [
            'target_url' => $url,
            'http_status' => $statusCode,
        ]);
    }

    if ($html !== false && $html !== '' && $statusCode >= 200 && $statusCode < 400) {
        if (file_put_contents($cacheFile, $html, LOCK_EX) === false) {
            et_record_system_event('cache_write_error', 'A successful upstream response could not be cached.', 'warning', ['target_url' => $url]);
        }
    }

    // Do not replace a good cached page with an upstream failure. A short-lived
    // stale fallback keeps historical results available through temporary source
    // outages; 404s are excluded because they can mean a genuinely removed page.
    $staleCache = grf_cached_result_response($cacheFile, $now - 86400);
    if ($upstreamUnavailable && $statusCode !== 404 && $staleCache !== null) {
        et_record_system_event('upstream_stale_cache_served', 'A recent cached result was served after the external source failed.', 'warning', [
            'target_url' => $url,
            'http_status' => $statusCode,
            'error_code' => $curlErrorCode > 0 ? 'CURL_' . $curlErrorCode : '',
        ]);
        return [
            'html' => $staleCache['html'],
            'status' => 200,
            'fetched_at' => $staleCache['fetched_at'],
            'cache_state' => 'stale_fallback',
            'upstream_status' => $statusCode,
        ];
    }

    return ['html' => $html, 'status' => $statusCode, 'fetched_at' => $now];
}
