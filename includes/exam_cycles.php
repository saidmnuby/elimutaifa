<?php
declare(strict_types=1);
require_once __DIR__ . '/admin_db.php';

const ET_EXAM_LEVELS = ['acsee', 'csee', 'ftna', 'psle', 'sfna'];

function et_exam_key(string $level, int $year): string {
    if (!in_array($level, ET_EXAM_LEVELS, true) || $year < 2010 || $year > (int) date('Y')) {
        throw new InvalidArgumentException('INVALID_INPUT');
    }
    return 'exam_cycle_' . $level . '_' . $year;
}

/** Existing routes are imported explicitly; this is never used as a runtime fallback. */
function et_exam_legacy_cycle(string $level, int $year): array {
    et_exam_key($level, $year);
    $archive = $year <= 2023;
    $base = $archive ? 'https://maktaba.tetea.org/exam-results/' . strtoupper($level) . $year . '/'
        : 'https://' . ($level === 'acsee' && $year === 2026 ? 'matokeo.necta.go.tz' : 'onlinesys.necta.go.tz') . '/results/' . $year . '/' . $level . '/';
    $primary = in_array($level, ['psle', 'sfna'], true);
    return ['level'=>$level, 'year'=>$year, 'base_url'=>$base,
        'school_path'=>($archive ? '' : 'results/') . ($level === 'psle' ? 'shl_' : '') . '{school}.htm',
        'directory_path'=>$primary ? ($archive ? '' : 'results/') . ($level === 'sfna' ? 'distr_ps' : 'distr_') . '{district}.htm'
            : ($archive && $level === 'ftna' ? 'ftna.htm' : 'index.htm'),
        'school_case'=>$level === 'ftna' && !$archive ? 'upper' : 'lower',
        'school_format'=>$level === 'psle' && $year <= 2015 ? 'primary_p' : 'standard',
        'status'=>'published', 'inherited'=>true, 'verified_at'=>null, 'verification_report'=>'Imported existing mapping; live review required.',
        'sample_school'=>'', 'sample_district'=>''];
}

function et_exam_cycles(?string $level = null, ?PDO $database = null, bool $public = true): array {
    $database ??= et_db();
    $rows = $database->query("SELECT setting_key,setting_value FROM app_settings WHERE setting_key LIKE 'exam_cycle_%'")->fetchAll();
    $cycles = [];
    foreach ($rows as $row) {
        $cycle = json_decode($row['setting_value'], true);
        if (!is_array($cycle) || !isset($cycle['level'], $cycle['year']) || !is_int($cycle['year'])) continue;
        try { if ($row['setting_key'] !== et_exam_key($cycle['level'], $cycle['year'])) continue; } catch (Throwable) { continue; }
        if ($level !== null && $cycle['level'] !== $level) continue;
        if (et_exam_validate($cycle) !== []) continue;
        if ($public && ($cycle['status'] !== 'published' || (empty($cycle['verified_at']) && empty($cycle['inherited'])))) continue;
        $cycles[$cycle['level'] . '-' . $cycle['year']] = $cycle;
    }
    uasort($cycles, static fn(array $a, array $b): int => strcmp($a['level'], $b['level']) ?: $b['year'] <=> $a['year']);
    return $cycles;
}

function et_exam_cycle(string $level, int $year, ?PDO $database = null): array {
    et_exam_key($level, $year);
    $cycle = et_exam_cycles($level, $database)[$level . '-' . $year] ?? null;
    if (!$cycle) throw new RuntimeException('CYCLE_UNAVAILABLE');
    return $cycle;
}

function et_exam_validate(array $cycle): array {
    $errors = [];
    try { et_exam_key((string)($cycle['level'] ?? ''), (int)($cycle['year'] ?? 0)); } catch (Throwable) { $errors[] = 'Choose an examination and a year between 2010 and the current year.'; }
    $base = (string)($cycle['base_url'] ?? ''); $p = parse_url($base);
    $path = $p['path'] ?? '';
    $level = (string)($cycle['level'] ?? ''); $year = (int)($cycle['year'] ?? 0);
    $expectedPath = ($p['host'] ?? '') === 'maktaba.tetea.org'
        ? '#^/exam-results/' . preg_quote(strtoupper($level) . $year, '#') . '/$#D'
        : '#^/results/' . $year . '/' . preg_quote($level, '#') . '/$#D';
    if (!$p || ($p['scheme'] ?? '') !== 'https' || !in_array($p['host'] ?? '', ['onlinesys.necta.go.tz','matokeo.necta.go.tz','maktaba.tetea.org'], true)
        || isset($p['user']) || isset($p['pass']) || isset($p['port']) || isset($p['query']) || isset($p['fragment']) || !preg_match($expectedPath, $path)) {
        $errors[] = 'Use an approved HTTPS examination/year base URL ending in /.';
    }
    foreach (['school_path', 'directory_path'] as $field) {
        $template = (string)($cycle[$field] ?? '');
        $clean = str_replace(['{school}','{district}'], ['sample','0000'], $template);
        if ($clean === '' || !preg_match('#^[a-zA-Z0-9_/-]+\.(?:htm|html)$#D', $clean) || str_contains($clean, '..') || str_starts_with($clean, '/')) $errors[] = 'Use safe relative .htm/.html paths with {school} or {district} placeholders.';
    }
    if (substr_count((string)($cycle['school_path'] ?? ''), '{school}') !== 1 || str_contains((string)($cycle['school_path'] ?? ''), '{district}')) $errors[] = 'The school path must contain exactly one {school} placeholder.';
    if (in_array($level, ['psle','sfna'], true)) {
        if (substr_count((string)($cycle['directory_path'] ?? ''), '{district}') !== 1 || str_contains((string)($cycle['directory_path'] ?? ''), '{school}')) $errors[] = 'Primary examination directory paths require {district}.';
    } elseif (str_contains((string)($cycle['directory_path'] ?? ''), '{')) $errors[] = 'Secondary directory paths must be fixed filenames.';
    if (!in_array($cycle['school_case'] ?? '', ['lower','upper'], true) || !in_array($cycle['school_format'] ?? '', ['standard','primary_p'], true)
        || (($cycle['school_format'] ?? '') === 'primary_p' && $level !== 'psle')) $errors[] = 'Invalid school filename format.';
    if (!in_array($cycle['status'] ?? '', ['draft','published','suspended','archived'], true)) $errors[] = 'Invalid cycle status.';
    return array_unique($errors);
}

function et_exam_url(array $cycle, string $kind, string $code = ''): string {
    if (et_exam_validate($cycle) !== []) throw new InvalidArgumentException('INVALID_SOURCE');
    if ($kind === 'school') {
        $primary = in_array($cycle['level'], ['psle','sfna'], true);
        if (!preg_match($primary ? '/^PS\d{4,7}$/Di' : '/^[SP]Q?\d{4}$/Di', $code)) throw new InvalidArgumentException('INVALID_SCHOOL');
        if ($cycle['school_format'] === 'primary_p') $code = preg_replace('/^PS/i', 'P', $code);
        $code = $cycle['school_case'] === 'upper' ? strtoupper($code) : strtolower($code);
        $path = str_replace('{school}', $code, $cycle['school_path']);
    } elseif ($kind === 'directory') {
        if (str_contains($cycle['directory_path'], '{district}') && !preg_match('/^\d{4}$/D', $code)) throw new InvalidArgumentException('INVALID_DISTRICT');
        $path = str_replace('{district}', $code, $cycle['directory_path']);
    } else throw new InvalidArgumentException('INVALID_SOURCE');
    return $cycle['base_url'] . $path;
}

function et_exam_source_url(string $level, int $year, string $kind, string $code = ''): string {
    return et_exam_url(et_exam_cycle($level, $year), $kind, $code);
}

function et_exam_cache_revision(array $cycle): string {
    return hash('sha256', json_encode(array_intersect_key($cycle, array_flip(['base_url','school_path','directory_path','school_case','school_format'])), JSON_THROW_ON_ERROR));
}

/** URL caches also change namespace when an owner changes a mapping. */
function et_exam_cache_key(string $url): string {
    $path = (string)parse_url($url, PHP_URL_PATH);
    if (preg_match('#^/results/(20\d{2})/(acsee|csee|ftna|psle|sfna)/#i', $path, $m)) { $level = strtolower($m[2]); $year = (int)$m[1]; }
    elseif (preg_match('#^/exam-results/(ACSEE|CSEE|FTNA|PSLE|SFNA)(20\d{2})/#i', $path, $m)) { $level = strtolower($m[1]); $year = (int)$m[2]; }
    else return hash('sha256', $url);
    try { return hash('sha256', $url . '|' . et_exam_cache_revision(et_exam_cycle($level, $year))); }
    catch (Throwable) { return hash('sha256', $url); }
}

function et_exam_changed(array $a, array $b): bool {
    foreach (['level','year','base_url','school_path','directory_path','school_case','school_format'] as $field) if (($a[$field] ?? null) !== ($b[$field] ?? null)) return true;
    return false;
}

function et_exam_link_matches(string $href, string $directoryUrl, string $schoolUrl): bool {
    $href = str_replace('\\', '/', trim($href));
    if ($href === $schoolUrl) return true;
    if ($href === '' || str_contains($href, ':') || str_starts_with($href, '//') || str_contains($href, '?') || str_contains($href, '#') || str_contains($href, '\\')) return false;
    $base = parse_url($directoryUrl);
    $path = str_starts_with($href, '/') ? $href : dirname($base['path']) . '/' . $href;
    $parts = [];
    foreach (explode('/', $path) as $part) {
        if ($part === '..') array_pop($parts);
        elseif ($part !== '' && $part !== '.') $parts[] = $part;
    }
    return 'https://' . $base['host'] . '/' . implode('/', $parts) === $schoolUrl;
}

function et_exam_prepare_change(?array $old, array $proposed): array {
    if (!$old || et_exam_changed($old, $proposed)) {
        $proposed['verified_at'] = null; $proposed['verified_by'] = null; $proposed['verification_report'] = '';
        $proposed['inherited'] = false; $proposed['status'] = 'draft';
        $proposed['last_check_at'] = null; $proposed['last_check_ok'] = null;
    }
    return $proposed;
}

function et_exam_record_source_success(string $url): void {
    if (!function_exists('et_monitoring_enabled') || !et_monitoring_enabled()) return;
    $path = (string)parse_url($url, PHP_URL_PATH);
    if (preg_match('#^/results/(20\d{2})/(acsee|csee|ftna|psle|sfna)/#i', $path, $m)) { $level = strtolower($m[2]); $year = (int)$m[1]; }
    elseif (preg_match('#^/exam-results/(ACSEE|CSEE|FTNA|PSLE|SFNA)(20\d{2})/#i', $path, $m)) { $level = strtolower($m[1]); $year = (int)$m[2]; }
    else return;
    try {
        $db = et_db(); $now = et_utc_now();
        $db->prepare(et_conflict_sql($db, 'INSERT INTO app_settings(setting_key,setting_value,updated_at) VALUES(:key,:value,:now) ON CONFLICT(setting_key) DO UPDATE SET setting_value=excluded.setting_value,updated_at=excluded.updated_at'))
            ->execute(['key'=>'exam_success_' . $level . '_' . $year, 'value'=>$now, 'now'=>$now]);
    } catch (Throwable $e) { error_log('Exam source monitoring: ' . $e->getMessage()); }
}

/** Bypass stale/fresh cache: publication requires a live source sample. */
function et_exam_verify(array $cycle, ?callable $fetch = null): string {
    if (et_exam_validate($cycle) !== []) throw new RuntimeException('Correct the cycle fields before verification.');
    if (!$fetch) {
        require_once __DIR__ . '/result_request.php';
        $fetch = static fn(string $url): array => grf_fetch_result($url, true);
    }
    $school = strtoupper(trim((string)($cycle['sample_school'] ?? '')));
    $directory = et_exam_url($cycle, 'directory', trim((string)($cycle['sample_district'] ?? '')));
    $schoolUrl = et_exam_url($cycle, 'school', $school);
    foreach ([$directory, $schoolUrl] as $url) {
        $r = $fetch($url);
        if (($r['status'] ?? 0) !== 200 || !is_string($r['html'] ?? null) || ($r['cache_state'] ?? '') === 'stale_fallback') throw new RuntimeException('Live source unavailable. Keep this cycle as Draft and review Traffic & Errors.');
        $dom = new DOMDocument(); $previous = libxml_use_internal_errors(true);
        try { $dom->loadHTML($r['html'], LIBXML_NONET); } finally { libxml_clear_errors(); libxml_use_internal_errors($previous); }
        $textParts = [];
        foreach ((new DOMXPath($dom))->query('//text()') as $node) $textParts[] = $node->textContent;
        $text = preg_replace('/\s+/', ' ', implode(' ', $textParts)) ?? '';
        if (!preg_match('/\b' . $cycle['year'] . '\b/', $text) || !preg_match('/\b' . strtoupper($cycle['level']) . '\b/i', $text)) throw new RuntimeException('The sample does not identify the expected examination and year.');
        if ($url === $directory) {
            $found = false;
            foreach ($dom->getElementsByTagName('a') as $link) {
                $href = $link->getAttribute('href');
                if (et_exam_link_matches($href, $directory, $schoolUrl)) $found = true;
            }
            if (!$found) throw new RuntimeException('The sample school was not found in this directory.');
        } else {
            $found = false;
            foreach ($dom->getElementsByTagName('tr') as $row) {
                $cells = $row->getElementsByTagName('td');
                // Legacy SFNA has a separate final average-grade column;
                // detailed subjects are in the preceding column.
                $subjectsIndex = $cells->length - (($cycle['level'] === 'sfna' && $cycle['year'] <= 2021) ? 2 : 1);
                if ($cells->length >= 4 && preg_match('/^' . preg_quote($school, '/') . '(?:\/\d{4}|-\d{3,4})$/Di', trim($cells->item(0)->textContent))
                    && preg_match('/[A-Za-z].*[-=].*[A-F]/', $cells->item($subjectsIndex)->textContent)) $found = true;
            }
            // Older FTNA HTML has orphaned cells that libxml cannot attach to a
            // row. Use the same supported parser as public school browsing.
            if (!$found && in_array($cycle['level'], ['acsee','csee','ftna'], true)) {
                require_once __DIR__ . '/secondary_directory.php';
                foreach (et_secondary_rows($r['html'], $school, $cycle['level']) as $candidateRow) {
                    if (preg_match('/[A-Za-z].*[-=].*[A-F]/', $candidateRow[4] ?? '')) {
                        $found = true;
                        break;
                    }
                }
            }
            if (!$found) throw new RuntimeException('No readable candidate rows were found for the sample school. Review the result layout.');
        }
    }
    return 'Live directory and school sample passed for ' . strtoupper($cycle['level']) . ' ' . $cycle['year'] . '. Sample verification does not cover every school.';
}

/** Optimistic locking protects verification and concurrent owner edits. Audit is atomic. */
function et_exam_store(PDO $database, array $cycle, ?string $expected, int $ownerId, string $action): void {
    $errors = et_exam_validate($cycle);
    if ($errors) throw new RuntimeException(implode(' ', $errors));
    if ($cycle['status'] === 'published' && empty($cycle['verified_at']) && empty($cycle['inherited'])) throw new RuntimeException('Verify a live sample before publishing.');
    $key = et_exam_key($cycle['level'], $cycle['year']);
    $previous = $expected === null ? [] : json_decode($expected, true, 512, JSON_THROW_ON_ERROR);
    $cycle['revision'] = (int)($previous['revision'] ?? 0) + 1;
    $value = json_encode($cycle, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
    $database->beginTransaction();
    try {
        if ($expected !== null) {
            $q = $database->prepare('UPDATE app_settings SET setting_value=?,updated_at=? WHERE setting_key=? AND setting_value=?');
            $q->execute([$value, et_utc_now(), $key, $expected]);
            if ($q->rowCount() !== 1) throw new RuntimeException('The cycle changed. Reload and try again.');
            $database->prepare('INSERT INTO app_settings(setting_key,setting_value,updated_at) VALUES(?,?,?)')
                ->execute(['exam_history_' . $cycle['level'] . '_' . $cycle['year'] . '_' . (int)($previous['revision'] ?? 0), $expected, et_utc_now()]);
            if (et_exam_changed($previous, $cycle)) $database->prepare('DELETE FROM app_settings WHERE setting_key=?')->execute(['exam_success_' . $cycle['level'] . '_' . $cycle['year']]);
        } else {
            $database->prepare('INSERT INTO app_settings(setting_key,setting_value,updated_at) VALUES(?,?,?)')->execute([$key,$value,et_utc_now()]);
        }
        $database->prepare('INSERT INTO audit_logs(admin_user_id,action,entity_type,details,created_at) VALUES(?,?,?,?,?)')->execute([$ownerId,$action,'exam_cycle',$key . ' / ' . $cycle['status'] . ' / revision ' . $cycle['revision'] . ' / ' . $cycle['base_url'],et_utc_now()]);
        $database->commit();
    } catch (Throwable $e) { if ($database->inTransaction()) $database->rollBack(); throw $e; }
}
