<?php

function grf_fetch_result(string $url): array
{
    $cacheDirectory = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'grf-result-cache';
    if (!is_dir($cacheDirectory)) {
        mkdir($cacheDirectory, 0700, true);
    }

    $clientKey = hash('sha256', $_SERVER['REMOTE_ADDR'] ?? 'unknown');
    $rateFile = $cacheDirectory . DIRECTORY_SEPARATOR . 'rate-' . $clientKey . '.json';
    $now = time();
    $windowStart = $now - 60;
    $rateHandle = fopen($rateFile, 'c+');

    if ($rateHandle === false) {
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
        return ['html' => false, 'status' => 429];
    }

    $recentRequests[] = $now;
    ftruncate($rateHandle, 0);
    rewind($rateHandle);
    fwrite($rateHandle, json_encode($recentRequests));
    fflush($rateHandle);
    flock($rateHandle, LOCK_UN);
    fclose($rateHandle);

    $cacheFile = $cacheDirectory . DIRECTORY_SEPARATOR . 'result-' . hash('sha256', $url) . '.html';
    if (is_file($cacheFile) && filemtime($cacheFile) >= $now - 300) {
        $cachedHtml = file_get_contents($cacheFile);
        if ($cachedHtml !== false && $cachedHtml !== '') {
            return ['html' => $cachedHtml, 'status' => 200];
        }
    }

    $request = curl_init($url);
    curl_setopt_array($request, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_CONNECTTIMEOUT => 3,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_ENCODING => '',
        CURLOPT_USERAGENT => '#position for success/1.0',
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2
    ]);
    $html = curl_exec($request);
    $statusCode = (int) curl_getinfo($request, CURLINFO_RESPONSE_CODE);
    curl_close($request);

    if ($html !== false && $html !== '' && $statusCode >= 200 && $statusCode < 400) {
        file_put_contents($cacheFile, $html, LOCK_EX);
    }

    return ['html' => $html, 'status' => $statusCode];
}
