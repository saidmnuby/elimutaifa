<?php
declare(strict_types=1);
require_once __DIR__ . '/exam_cycles.php';

/** Keep the existing page design; render every year selector from published cycles. */
function et_exam_landing(string $level, string $template, ?PDO $database = null): void {
    header('Cache-Control: no-cache');
    $html = file_get_contents($template);
    if (!is_string($html)) throw new RuntimeException('Missing examination page.');
    echo et_exam_render_years($html, $level, $database);
}

function et_exam_render_years(string $html, string $level, ?PDO $database = null): string {
    try { $cycles = et_exam_cycles($level, $database); } catch (Throwable $e) { error_log('Exam cycle lookup failed: ' . $e->getMessage()); $cycles = []; }
    $options = '<option value="">Chagua mwaka wa matokeo</option>';
    foreach ($cycles as $cycle) $options .= '<option value="' . $cycle['year'] . '">' . $cycle['year'] . '</option>';
    if (!$cycles) $options = '<option value="">Hakuna mwaka unaopatikana kwa sasa</option>';
    $html = preg_replace_callback('/(<select\b[^>]*\bname="(?:examYear|finalexamYear|year)"[^>]*>).*?(<\/select>)/s',
        static fn(array $m): string => $m[1] . $options . $m[2], $html);
    // Service headings describe the examination, not a hardcoded release year.
    $html = str_replace([' 2026 (' . strtoupper($level) . ')', '(' . strtoupper($level) . ') mwaka 2026', '(' . strtoupper($level) . ') kwa mwaka 2026'],
        [' (' . strtoupper($level) . ')', '(' . strtoupper($level) . ')', '(' . strtoupper($level) . ')'], $html);
    return $html;
}
