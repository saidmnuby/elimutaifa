<?php
declare(strict_types=1);
// Routing assertions must never depend on the owner's current publication choices.
putenv('ELIMUTAIFA_DB_DRIVER=sqlite');
putenv('ELIMUTAIFA_DB_PATH=:memory:');
require_once dirname(__DIR__) . '/includes/exam_cycles.php';
$fixtureDatabase = et_db();
$fixtureInsert = $fixtureDatabase->prepare('INSERT INTO app_settings(setting_key,setting_value,updated_at) VALUES(?,?,?)');
foreach (ET_EXAM_LEVELS as $fixtureLevel) {
    for ($fixtureYear = 2010; $fixtureYear <= min(2026,(int)date('Y')); $fixtureYear++) {
        $fixtureInsert->execute([et_exam_key($fixtureLevel,$fixtureYear),json_encode(et_exam_legacy_cycle($fixtureLevel,$fixtureYear),JSON_THROW_ON_ERROR),et_utc_now()]);
    }
}
