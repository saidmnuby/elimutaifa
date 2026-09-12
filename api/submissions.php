<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/admin_db.php';
require_once dirname(__DIR__) . '/includes/monitoring.php';
et_register_fatal_error_monitoring();

header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');

function et_submission_response(int $status, bool $ok, string $message): never
{
    http_response_code($status);
    echo json_encode(['ok' => $ok, 'message' => $message], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST');
    et_submission_response(405, false, 'Method not allowed.');
}

if ((int) ($_SERVER['CONTENT_LENGTH'] ?? 0) > 20000) {
    et_submission_response(413, false, 'Ujumbe ni mkubwa kuliko kiwango kinachoruhusiwa.');
}

$fetchSite = strtolower((string) ($_SERVER['HTTP_SEC_FETCH_SITE'] ?? ''));
if ($fetchSite !== '' && !in_array($fetchSite, ['same-origin', 'none'], true)) {
    et_submission_response(403, false, 'Ombi halikukubalika.');
}

$contentType = strtolower((string) ($_SERVER['CONTENT_TYPE'] ?? ''));
$input = str_contains($contentType, 'application/json')
    ? json_decode(file_get_contents('php://input') ?: '{}', true)
    : $_POST;
if (!is_array($input)) {
    et_submission_response(400, false, 'Taarifa hazijasomeka.');
}
if (trim((string) ($input['website'] ?? '')) !== '') {
    et_submission_response(200, true, 'Ujumbe umepokelewa.');
}

$topic = trim((string) ($input['topic'] ?? ''));
$senderRole = trim((string) ($input['name'] ?? ''));
$contact = trim((string) ($input['contact'] ?? ''));
$message = trim((string) ($input['message'] ?? ''));
$allowedTopics = ['question', 'announcement', 'contribution', 'comment', 'report'];
if (
    !in_array($topic, $allowedTopics, true)
    || mb_strlen($senderRole) < 2 || mb_strlen($senderRole) > 80
    || mb_strlen($contact) > 120
    || mb_strlen($message) < 10 || mb_strlen($message) > 1000
) {
    et_submission_response(422, false, 'Kagua sehemu zote kisha ujaribu tena.');
}

$ipHash = hash('sha256', ($_SERVER['REMOTE_ADDR'] ?? 'unknown') . '|elimutaifa-submission-v1');
$database = et_db();
$database->exec("UPDATE submissions SET ip_hash='' WHERE ip_hash<>'' AND created_at < datetime('now', '-1 hour')");
$rateStatement = $database->prepare("SELECT COUNT(*) FROM submissions WHERE ip_hash=:ip_hash AND created_at >= datetime('now', '-1 hour')");
$rateStatement->execute(['ip_hash' => $ipHash]);
if ((int) $rateStatement->fetchColumn() >= 5) {
    et_submission_response(429, false, 'Umetuma ujumbe mara nyingi. Jaribu tena baada ya muda.');
}

$now = et_utc_now();
$statement = $database->prepare(<<<'SQL'
INSERT INTO submissions (topic, sender_role, contact, message, status, admin_note, ip_hash, created_at, updated_at)
VALUES (:topic, :sender_role, :contact, :message, 'new', '', :ip_hash, :created_at, :updated_at)
SQL);
$statement->execute([
    'topic' => $topic, 'sender_role' => $senderRole, 'contact' => $contact,
    'message' => $message, 'ip_hash' => $ipHash, 'created_at' => $now, 'updated_at' => $now,
]);
et_submission_response(201, true, 'Ujumbe wako umetumwa. Asante kwa kushiriki.');
