<?php
declare(strict_types=1);
require_once __DIR__ . '/validation.php';
require_once __DIR__ . '/result_request.php';

function et_secondary_base(string $level, int $year): string
{
    if (!in_array($level, ['csee','acsee','ftna'], true) || !grf_is_valid_exam_year($year)) {
        throw new InvalidArgumentException('INVALID_INPUT');
    }
    if ($year <= 2023) return 'https://maktaba.tetea.org/exam-results/' . strtoupper($level) . $year . '/';
    $host = $level === 'acsee' && $year === 2026 ? 'matokeo.necta.go.tz' : 'onlinesys.necta.go.tz';
    return "https://$host/results/$year/$level/";
}

function et_secondary_dom(string $html): DOMDocument
{
    $previous = libxml_use_internal_errors(true);
    try {
        $dom = new DOMDocument();
        $dom->loadHTML('<?xml encoding="UTF-8">' . $html, LIBXML_NONET);
        return $dom;
    } finally { libxml_clear_errors(); libxml_use_internal_errors($previous); }
}

/** Only accept direct school links under the exact configured exam/year directory. */
function et_secondary_schools(string $html, string $base): array
{
    $schools = [];
    foreach (et_secondary_dom($html)->getElementsByTagName('a') as $link) {
        $href = trim($link->getAttribute('href'));
        if (str_starts_with($href, $base)) $href = substr($href, strlen($base));
        if (!preg_match('#^(?:results/)?([sp]q?\d{4})\.html?$#iD', $href, $match)) continue;
        $code = strtoupper($match[1]);
        $name = trim(preg_replace('/\s+/u', ' ', $link->textContent) ?? '');
        $nameWithoutCode = trim((string) preg_replace('/^' . preg_quote($code, '/') . '\s*[-–]?\s*/iu', '', $name));
        if ($nameWithoutCode !== '') $name = $nameWithoutCode;
        if ($name === '') continue;
        $schools[$code] = ['code'=>$code, 'name'=>$name, 'url'=>$base.$href];
    }
    uasort($schools, static fn(array $a,array $b): int => strnatcasecmp($a['name'],$b['name']));
    return $schools;
}

/** Extract text only, never render source HTML or executable links. */
function et_secondary_rows(string $html, string $school, string $level = 'csee'): array
{
    $rows = [];
    $dom = et_secondary_dom($html);
    foreach ($dom->getElementsByTagName('tr') as $tableRow) {
        $cells = [];
        foreach ($tableRow->childNodes as $cell) {
            if ($cell instanceof DOMElement && strtolower($cell->tagName) === 'td') {
                $cells[] = trim(preg_replace('/\s+/u', ' ', $cell->textContent) ?? '');
            }
        }
        $number = strtoupper($cells[0] ?? '');
        if (!preg_match('#^'.preg_quote($school,'#').'/\d{4}$#D', $number)) continue;

        if ($level === 'ftna') {
            // Older FTNA pages omit PReM number; the 2023 archive includes it and
            // the candidate name before sex. Keep only fields shown to the user.
            $sexAt = count($cells) >= 7 ? 3 : 2;
            $row = [$number, $cells[$sexAt] ?? '', $cells[$sexAt + 1] ?? '', $cells[$sexAt + 2] ?? '', $cells[$sexAt + 3] ?? ''];
        } else {
            $row = array_slice($cells, 0, 5);
        }
        if (preg_match('/^[MF]$/iD', $row[1]) && $row[4] !== '') {
            $rows[$number] = $row;
        }
    }

    // Some older archive pages close a row before opening the next one. DOM then
    // cannot associate every candidate's cells with a <tr>, so supplement the
    // row parser with a sequential read of the same cells.
    {
        $cells = $dom->getElementsByTagName('td');
        for ($i = 0; $i < $cells->length; $i++) {
            $number = strtoupper(trim($cells->item($i)->textContent));
            if (!preg_match('#^'.preg_quote($school,'#').'/\d{4}$#D', $number)) continue;
            if (isset($rows[$number])) continue;
            if ($level === 'ftna') {
                $sexAt = null;
                for ($offset = 1; $offset <= 3; $offset++) {
                    $candidate = trim($cells->item($i + $offset)?->textContent ?? '');
                    if (preg_match('/^[MF]$/iD', $candidate)) { $sexAt = $i + $offset; break; }
                }
                if ($sexAt === null) continue;
                $row = [
                    $number,
                    trim($cells->item($sexAt)->textContent),
                    trim($cells->item($sexAt + 1)?->textContent ?? ''),
                    trim($cells->item($sexAt + 2)?->textContent ?? ''),
                    trim($cells->item($sexAt + 3)?->textContent ?? ''),
                ];
            } else {
                $row = [];
                for ($offset = 0; $offset < 5; $offset++) {
                    $row[] = trim($cells->item($i + $offset)?->textContent ?? '');
                }
            }
            if (preg_match('/^[MF]$/iD', $row[1]) && $row[4] !== '') $rows[$number] = $row;
        }
    }
    return array_values($rows);
}

function et_secondary_fetch_response(string $url): array
{
    $response = grf_fetch_result($url);
    if (($response['status'] ?? 0) === 429) throw new RuntimeException('RATE_LIMIT');
    if (($response['status'] ?? 0) !== 200 || empty($response['html'])) throw new RuntimeException('SOURCE_UNAVAILABLE');
    return $response;
}

function et_secondary_fetch(string $url): string
{
    return (string) et_secondary_fetch_response($url)['html'];
}

function et_secondary_directory_source(string $level, int $year): string
{
    return et_secondary_base($level, $year) . ($level === 'ftna' && $year <= 2023 ? 'ftna.htm' : 'index.htm');
}

/** Public school names/codes only; these JSON files are blocked from web access. */
function et_secondary_directory_file(string $level, int $year): string
{
    et_secondary_base($level, $year); // validates both values
    return dirname(__DIR__) . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'secondary-directories'
        . DIRECTORY_SEPARATOR . $level . '-' . $year . '.json';
}

function et_secondary_directory_read(string $level, int $year): ?array
{
    $file = et_secondary_directory_file($level, $year);
    if (!is_file($file) || !is_readable($file)) return null;
    $handle = @fopen($file, 'rb');
    if (!$handle) return null;
    try {
        if (!flock($handle, LOCK_SH)) return null;
        $data = json_decode(stream_get_contents($handle) ?: '', true);
    } finally {
        flock($handle, LOCK_UN);
        fclose($handle);
    }
    if (!is_array($data) || ($data['version'] ?? null) !== 4 || !is_string($data['fetched_at'] ?? null) || !is_array($data['schools'] ?? null)) return null;
    $schools = [];
    foreach ($data['schools'] as $school) {
        if (!is_array($school) || !preg_match('/^[SP]Q?\d{4}$/D', (string) ($school['code'] ?? ''))
            || !is_string($school['name'] ?? null) || !is_string($school['url'] ?? null)) continue;
        $schools[$school['code']] = ['code'=>$school['code'], 'name'=>$school['name'], 'url'=>$school['url']];
    }
    return $schools === [] ? null : ['fetched_at'=>$data['fetched_at'], 'schools'=>$schools];
}

function et_secondary_directory_write(string $level, int $year, array $schools): void
{
    $file = et_secondary_directory_file($level, $year);
    $directory = dirname($file);
    if (!is_dir($directory) && !mkdir($directory, 0700, true) && !is_dir($directory)) throw new RuntimeException('CACHE_DIRECTORY');
    $handle = fopen($file, 'c+b');
    if (!$handle) throw new RuntimeException('CACHE_DIRECTORY');
    try {
        if (!flock($handle, LOCK_EX)) throw new RuntimeException('CACHE_DIRECTORY');
        $payload = json_encode(['version'=>4, 'fetched_at'=>et_utc_now(), 'schools'=>array_values($schools)], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE);
        if (!is_string($payload)) throw new RuntimeException('CACHE_DIRECTORY');
        ftruncate($handle, 0);
        rewind($handle);
        fwrite($handle, $payload);
        fflush($handle);
    } finally {
        flock($handle, LOCK_UN);
        fclose($handle);
    }
}

/**
 * Read an existing directory first. A released result directory changes rarely;
 * refresh after 30 days. If the source is unavailable, keep serving stale names.
 */
function et_secondary_directory_prepare(string $level, int $year): void
{
    $current = et_secondary_directory_read($level, $year);
    $fetchedAt = strtotime((string) ($current['fetched_at'] ?? '')) ?: 0;
    if ($current !== null && $fetchedAt >= time() - (30 * 86400)) return;

    try {
        $source = et_secondary_directory_source($level, $year);
        $schools = et_secondary_schools(et_secondary_fetch($source), et_secondary_base($level, $year));
        if ($schools === []) throw new RuntimeException('SOURCE_FORMAT');
        et_secondary_directory_write($level, $year, $schools);
    } catch (Throwable $exception) {
        if ($current === null) throw $exception;
        et_record_system_event('school_directory_refresh_failed', 'Saved school directory is being used because its source could not be refreshed.', 'warning', ['exam_type'=>strtoupper($level), 'target_url'=>et_secondary_directory_source($level, $year)]);
    }
}

function et_secondary_directory_search(string $level, int $year, string $query, int $limit = 40): array
{
    et_secondary_directory_prepare($level, $year);
    $query = trim(preg_replace('/\s+/u', ' ', $query) ?? '');
    if (mb_strlen($query) < 2) return [];
    $limit = max(1, min(50, $limit));
    $directory = et_secondary_directory_read($level, $year);
    if (!$directory) throw new RuntimeException('CACHE_DIRECTORY');
    $query = mb_strtolower($query);
    $matches = array_filter($directory['schools'], static fn(array $school): bool =>
        str_contains(mb_strtolower($school['name']), $query) || str_contains(mb_strtolower($school['code']), $query)
    );
    return array_slice(array_values($matches), 0, $limit);
}

function et_secondary_directory_school(string $level, int $year, string $code): ?array
{
    et_secondary_directory_prepare($level, $year);
    $directory = et_secondary_directory_read($level, $year);
    return $directory['schools'][$code] ?? null;
}
