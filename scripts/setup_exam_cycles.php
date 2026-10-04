<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once dirname(__DIR__) . '/includes/exam_cycles.php';
$database = et_db(); $count = 0;
$database->beginTransaction();
try {
    $exists = $database->prepare('SELECT setting_value FROM app_settings WHERE setting_key=?');
    $insert = $database->prepare('INSERT INTO app_settings(setting_key,setting_value,updated_at) VALUES(?,?,?)');
    foreach (ET_EXAM_LEVELS as $level) {
        // Only import the pre-existing supported years, never assume future availability.
        for ($year = 2010; $year <= min(2026, (int)date('Y')); $year++) {
            $key = et_exam_key($level, $year); $exists->execute([$key]);
            if ($exists->fetchColumn() !== false) continue;
            $insert->execute([$key,json_encode(et_exam_legacy_cycle($level,$year),JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES),et_utc_now()]);
            $count++;
        }
    }
    if ($count) $database->prepare('INSERT INTO audit_logs(action,entity_type,details,created_at) VALUES(?,?,?,?)')->execute(['exam_cycles_imported','exam_cycle',$count . ' existing year mappings imported; live review required.',et_utc_now()]);
    $database->commit();
    echo 'PASS: ' . $count . ' existing examination cycles imported; existing settings preserved.' . PHP_EOL;
} catch (Throwable $e) {
    if ($database->inTransaction()) $database->rollBack();
    fwrite(STDERR, "Examination cycle setup failed. Check database configuration and logs.\n");
    error_log($e->getMessage()); exit(1);
}
