<?php

require_once __DIR__ . '/result_request.php';

function grf_render_district_results(string $exam): void
{
    $year = filter_input(INPUT_GET, 'year', FILTER_VALIDATE_INT);
    $district = preg_replace('/\D/', '', (string) ($_GET['district'] ?? ''));
    $backUrl = './';

    if ($year === false || $year === null || $year < 2010 || $year > 2026 || !preg_match('/^\d{4}$/', $district)) {
        http_response_code(400);
        exit('Invalid examination year or district code.');
    }

    $sourceUrl = "https://onlinesys.necta.go.tz/results/{$year}/{$exam}/results/distr_{$district}.htm";
    $response = grf_fetch_result($sourceUrl);
    if ($response['html'] === false || $response['status'] < 200 || $response['status'] >= 400) {
        http_response_code(502);
        exit('Results are not available from NECTA at the moment. Please try again later.');
    }

    $dom = new DOMDocument();
    libxml_use_internal_errors(true);
    $dom->loadHTML($response['html']);
    libxml_clear_errors();
    $heading = "{$exam} {$year} school results";
    foreach ($dom->getElementsByTagName('h3') as $element) {
        $candidate = trim(preg_replace('/\s+/', ' ', $element->textContent));
        if ($candidate !== '') { $heading = $candidate; break; }
    }

    $schools = [];
    $sourceBase = dirname($sourceUrl) . '/';
    foreach ($dom->getElementsByTagName('a') as $link) {
        $name = trim(preg_replace('/\s+/', ' ', $link->textContent));
        $href = $link->getAttribute('href');
        if ($name !== '' && preg_match('/PS\d{7}-\d{3,4}|PS\d{7}/i', $name) && $href !== '') {
            $schools[] = ['name' => $name, 'url' => $sourceBase . ltrim($href, '/')];
        }
    }
    $schools = array_values(array_unique($schools, SORT_REGULAR));
    ?>
<!doctype html><html lang="sw"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>School results | ElimuTaifa</title><link rel="stylesheet" href="../assets/css/style.css"><style>.district-results{max-width:1100px;margin:32px auto;padding:0 20px}.district-head{background:#031b4e;color:#fff;border-radius:16px;padding:28px}.school-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:14px;margin-top:22px}.school-link{display:block;padding:16px;background:#fff;border:1px solid #c1d6ff;border-radius:12px;color:#031b4e;text-decoration:none;font-weight:600}.school-link:hover{border-color:#058249;box-shadow:0 4px 14px #0002}.meta{color:#53657c;margin-top:8px}.back{display:inline-block;margin:18px 0;color:#058249;font-weight:700}</style></head><body><main class="district-results"><a class="back" href="<?= htmlspecialchars($backUrl) ?>">&larr; Rudi kuchagua halmashauri</a><section class="district-head"><p>NECTA <?= htmlspecialchars(strtoupper($exam)) ?> <?= htmlspecialchars((string) $year) ?></p><h1><?= htmlspecialchars($heading) ?></h1><p><?= count($schools) ?> shule zimepatikana</p></section><section class="school-grid"><?php foreach ($schools as $school): ?><a class="school-link" href="<?= htmlspecialchars($school['url']) ?>" target="_blank" rel="noopener noreferrer"><?= htmlspecialchars($school['name']) ?></a><?php endforeach; ?></section><p class="meta">Orodha imetolewa kutoka ukurasa rasmi wa NECTA. <a href="<?= htmlspecialchars($sourceUrl) ?>" target="_blank" rel="noopener noreferrer">Fungua chanzo</a></p></main></body></html>
    <?php
}
