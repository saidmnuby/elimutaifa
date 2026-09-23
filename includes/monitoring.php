<?php
declare(strict_types=1);

require_once __DIR__ . '/admin_db.php';

function et_monitoring_enabled(): bool
{
    return getenv('ELIMUTAIFA_MONITORING_DISABLED') !== '1';
}

function et_monitoring_secret(PDO $database): string
{
    $statement = $database->prepare("SELECT setting_value FROM app_settings WHERE setting_key='monitoring_secret' LIMIT 1");
    $statement->execute();
    $secret = $statement->fetchColumn();
    if (is_string($secret) && strlen($secret) >= 32) {
        return $secret;
    }

    $secret = bin2hex(random_bytes(32));
    $statement = $database->prepare(et_conflict_sql($database, <<<'SQL'
INSERT INTO app_settings (setting_key, setting_value, updated_at)
VALUES ('monitoring_secret', :setting_value, :updated_at)
ON CONFLICT(setting_key) DO NOTHING
SQL));
    $statement->execute(['setting_value' => $secret, 'updated_at' => et_utc_now()]);
    $statement = $database->query("SELECT setting_value FROM app_settings WHERE setting_key='monitoring_secret' LIMIT 1");
    return (string) $statement->fetchColumn();
}

function et_normalize_public_path(string $path): string
{
    $parsedPath = parse_url($path, PHP_URL_PATH);
    $path = is_string($parsedPath) ? $parsedPath : '/';
    $path = preg_replace('/[\x00-\x1F\x7F]/u', '', $path) ?? '/';
    $path = preg_replace('/(?:PS\d{7}-\d{3,4}|[SPQ]{1,2}\d{4}(?:\/|%2F)\d{4})/i', '[candidate]', $path) ?? $path;
    $path = preg_replace('/[a-z0-9._%+-]+(?:@|%40)[a-z0-9.-]+\.[a-z]{2,}/i', '[email]', $path) ?? $path;
    $path = preg_replace('/[a-f0-9]{32,}/i', '[token]', $path) ?? $path;
    $path = '/' . ltrim($path, '/');
    return mb_substr($path, 0, 255);
}

function et_normalize_upstream_target(string $url): string
{
    $host = strtolower((string) parse_url($url, PHP_URL_HOST));
    $path = (string) parse_url($url, PHP_URL_PATH);
    $path = preg_replace('/distr_\d{4}\.htm/i', 'distr_0000.htm', $path) ?? $path;
    $path = preg_replace('/(?:shl_)?(?:pq|ps|s|p|q)\d{4,8}\.htm/i', 'school_0000.htm', $path) ?? $path;
    return mb_substr($host . $path, 0, 500);
}

function et_detect_exam_type(string $value): string
{
    foreach (['form-one'=>'FORM ONE','form-five'=>'FORM FIVE'] as $path=>$label) {
        if (stripos($value, $path) !== false) return $label;
    }
    foreach (['acsee', 'csee', 'ftna', 'psle', 'sfna'] as $exam) {
        if (stripos($value, $exam) !== false) {
            return strtoupper($exam);
        }
    }
    return '';
}

function et_record_page_view(string $path): void
{
    if (!et_monitoring_enabled()) {
        return;
    }
    $path = et_normalize_public_path($path);
    if (preg_match('#/(?:admin|api|assets|includes|scripts|storage|tests)(?:/|$)#i', $path)) {
        return;
    }
    $userAgent = mb_substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 500);
    if ($userAgent !== '' && preg_match('/bot|crawler|spider|slurp|monitoring|uptime/i', $userAgent)) {
        return;
    }

    try {
        $database = et_db();
        $visitorHash = hash_hmac(
            'sha256',
            ($_SERVER['REMOTE_ADDR'] ?? 'unknown') . '|' . $userAgent,
            et_monitoring_secret($database)
        );
        $nowTimestamp = time();
        $recentStatement = $database->prepare('SELECT last_recorded_at FROM traffic_recent WHERE visitor_hash=:visitor_hash AND path=:path');
        $recentStatement->execute(['visitor_hash' => $visitorHash, 'path' => $path]);
        $lastRecorded = $recentStatement->fetchColumn();
        if ($lastRecorded !== false && $nowTimestamp - (int) $lastRecorded < 10) {
            return;
        }

        $day = (new DateTimeImmutable('now', new DateTimeZone('Africa/Dar_es_Salaam')))->format('Y-m-d');
        $now = et_utc_now();
        $database->beginTransaction();
        $database->prepare(et_conflict_sql($database, <<<'SQL'
INSERT INTO traffic_recent (visitor_hash, path, last_recorded_at)
VALUES (:visitor_hash, :path, :last_recorded_at)
ON CONFLICT(visitor_hash, path) DO UPDATE SET last_recorded_at=excluded.last_recorded_at
SQL))->execute(['visitor_hash' => $visitorHash, 'path' => $path, 'last_recorded_at' => $nowTimestamp]);

        $uniqueStatement = $database->prepare(et_conflict_sql($database, <<<'SQL'
INSERT OR IGNORE INTO traffic_unique_visitors (day, path, visitor_hash)
VALUES (:day, :path, :visitor_hash)
SQL));
        $uniqueStatement->execute(['day' => $day, 'path' => $path, 'visitor_hash' => $visitorHash]);
        $uniqueIncrement = $uniqueStatement->rowCount() === 1 ? 1 : 0;

        $dailyStatement = $database->prepare(et_conflict_sql($database, <<<'SQL'
INSERT INTO traffic_daily (day, path, views, unique_visitors, last_view_at)
VALUES (:day, :path, 1, :unique_increment, :last_view_at)
ON CONFLICT(day, path) DO UPDATE SET
    views = traffic_daily.views + 1,
    unique_visitors = traffic_daily.unique_visitors + excluded.unique_visitors,
    last_view_at = excluded.last_view_at
SQL));
        $dailyStatement->execute(['day' => $day, 'path' => $path, 'unique_increment' => $uniqueIncrement, 'last_view_at' => $now]);
        $database->commit();

        if (random_int(1, 100) === 1) {
            $database->prepare('DELETE FROM traffic_unique_visitors WHERE day < :cutoff')->execute(['cutoff' => gmdate('Y-m-d', $nowTimestamp - 90 * 86400)]);
            $database->prepare('DELETE FROM traffic_daily WHERE day < :cutoff')->execute(['cutoff' => gmdate('Y-m-d', $nowTimestamp - 730 * 86400)]);
            $database->prepare('DELETE FROM traffic_recent WHERE last_recorded_at < :cutoff')->execute(['cutoff' => $nowTimestamp - 86400]);
        }
    } catch (Throwable $exception) {
        if (isset($database) && $database instanceof PDO && $database->inTransaction()) {
            $database->rollBack();
        }
        error_log('ElimuTaifa traffic monitoring error: ' . $exception->getMessage());
    }
}

/** Successful parsed results, not search attempts. No candidate data is stored.
 * The reserved non-URL path keeps this event separate from page-view totals.
 * Uses the existing unique-visitor identity, deduplication and 90-day retention.
 */
function et_record_search_success(?PDO $database = null): void
{
    if (!et_monitoring_enabled() || ($_SERVER['HTTP_DNT'] ?? '') === '1') return;
    $userAgent = mb_substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 500);
    if (preg_match('/bot|crawler|spider|slurp|monitoring|uptime/i', $userAgent)) return;
    try {
        $database ??= et_db();
        $visitorHash = hash_hmac('sha256', ($_SERVER['REMOTE_ADDR'] ?? 'unknown') . '|' . $userAgent, et_monitoring_secret($database));
        $statement = $database->prepare(et_conflict_sql($database, <<<'SQL'
INSERT OR IGNORE INTO traffic_unique_visitors (day, path, visitor_hash)
VALUES (:day, :path, :visitor_hash)
SQL));
        $statement->execute([
            'day' => (new DateTimeImmutable('now', new DateTimeZone('Africa/Dar_es_Salaam')))->format('Y-m-d'),
            'path' => '@event/search-success',
            'visitor_hash' => $visitorHash,
        ]);
    } catch (Throwable $exception) {
        error_log('ElimuTaifa search monitoring error: ' . $exception->getMessage());
    }
}

function et_record_system_event(
    string $eventType,
    string $message,
    string $severity = 'error',
    array $context = []
): void {
    if (!et_monitoring_enabled()) {
        return;
    }
    $severity = in_array($severity, ['info', 'warning', 'error', 'critical'], true) ? $severity : 'error';
    $requestPath = et_normalize_public_path((string) ($context['request_path'] ?? ($_SERVER['REQUEST_URI'] ?? '/')));
    $target = isset($context['target_url']) ? et_normalize_upstream_target((string) $context['target_url']) : '';
    $examType = mb_substr((string) ($context['exam_type'] ?? et_detect_exam_type($target . ' ' . $requestPath)), 0, 20);
    $httpStatus = isset($context['http_status']) ? (int) $context['http_status'] : null;
    $errorCode = mb_substr((string) ($context['error_code'] ?? ''), 0, 80);
    $eventType = mb_substr(preg_replace('/[^a-z0-9_-]/i', '_', $eventType) ?? 'system_error', 0, 80);
    $message = mb_substr(trim($message), 0, 500);
    $fingerprint = hash('sha256', implode('|', [$eventType, $examType, $requestPath, $target, (string) $httpStatus, $errorCode]));
    $now = et_utc_now();

    try {
        $database = et_db();
        $statement = $database->prepare(et_conflict_sql($database, <<<'SQL'
INSERT INTO system_events
(fingerprint,severity,event_type,exam_type,request_path,target,http_status,error_code,message,occurrences,status,first_seen_at,last_seen_at,resolved_at)
VALUES
(:fingerprint,:severity,:event_type,:exam_type,:request_path,:target,:http_status,:error_code,:message,1,'open',:first_seen_at,:last_seen_at,NULL)
ON CONFLICT(fingerprint) DO UPDATE SET
    severity=excluded.severity,
    message=excluded.message,
    occurrences=system_events.occurrences + 1,
    status='open',
    last_seen_at=excluded.last_seen_at,
    resolved_at=NULL
SQL));
        $statement->execute([
            'fingerprint' => $fingerprint,
            'severity' => $severity,
            'event_type' => $eventType,
            'exam_type' => $examType,
            'request_path' => $requestPath,
            'target' => $target,
            'http_status' => $httpStatus,
            'error_code' => $errorCode,
            'message' => $message !== '' ? $message : 'System event',
            'first_seen_at' => $now,
            'last_seen_at' => $now,
        ]);
    } catch (Throwable $exception) {
        error_log('ElimuTaifa system monitoring error: ' . $exception->getMessage());
    }
}

function et_register_fatal_error_monitoring(): void
{
    static $registered = false;
    if ($registered || !et_monitoring_enabled()) {
        return;
    }
    $registered = true;
    register_shutdown_function(static function (): void {
        $error = error_get_last();
        if (!$error || !in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR], true)) {
            return;
        }
        et_record_system_event('php_fatal_error', 'A fatal PHP error interrupted a user request.', 'critical', [
            'error_code' => 'PHP_' . $error['type'],
            'request_path' => $_SERVER['REQUEST_URI'] ?? '/',
        ]);
    });
}
