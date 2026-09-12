<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/includes/admin_auth.php';
et_admin_boot();
$user = et_require_admin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !et_verify_csrf($_POST['csrf_token'] ?? null)) {
    http_response_code(405);
    exit('Method not allowed');
}
$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT) ?: 0;
$status = trim((string) ($_POST['status'] ?? ''));
$note = trim((string) ($_POST['admin_note'] ?? ''));
$allowedStatuses = ['new', 'review', 'resolved', 'spam', 'archived'];
$returnStatus = (string) ($_POST['return_status'] ?? 'new');
$returnStatus = $returnStatus === 'all' || in_array($returnStatus, $allowedStatuses, true) ? $returnStatus : 'new';
$returnPage = max(1, filter_var($_POST['return_page'] ?? 1, FILTER_VALIDATE_INT) ?: 1);
$returnUrl = './?status=' . rawurlencode($returnStatus) . '&page=' . $returnPage;
if ($id <= 0 || !in_array($status, $allowedStatuses, true) || mb_strlen($note) > 1000) {
    et_flash('error', 'Taarifa za update si sahihi.');
    et_redirect($returnUrl);
}
$statement = et_db()->prepare('UPDATE submissions SET status=:status, admin_note=:admin_note, updated_at=:updated_at WHERE id=:id');
$statement->execute(['status' => $status, 'admin_note' => $note, 'updated_at' => et_utc_now(), 'id' => $id]);
et_audit((int) $user['id'], 'submission_updated', 'submission', $id, 'Status: ' . $status);
et_flash('success', 'Ujumbe umesasishwa.');
et_redirect($returnUrl);
