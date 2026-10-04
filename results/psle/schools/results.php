<?php
declare(strict_types=1);

require_once dirname(__DIR__, 3) . '/includes/page_headers.php';
require_once dirname(__DIR__, 3) . '/includes/validation.php';
require_once dirname(__DIR__, 3) . '/includes/exam_cycles.php';
require_once dirname(__DIR__, 3) . '/includes/result_request.php';
et_send_nonindex_page_headers();

function psle_school_subjects(string $value): array
{
    $matches = [];
    preg_match_all('/([A-Za-z&.\/\s]+?)\s*-\s*([A-F])/i', $value, $matches, PREG_SET_ORDER);
    $subjects = [];
    $averageGrade = '';

    foreach ($matches as $match) {
        $name = trim($match[1]);
        $grade = strtoupper($match[2]);
        if (strcasecmp($name, 'Average Grade') === 0) {
            $averageGrade = $grade;
            continue;
        }
        $subjects[] = ['name' => $name, 'grade' => $grade];
    }

    return ['average' => $averageGrade, 'subjects' => $subjects];
}

$examYear = filter_input(INPUT_GET, 'year', FILTER_VALIDATE_INT);
$schoolCode = is_string($_GET['school'] ?? null) ? strtoupper(trim($_GET['school'])) : '';
$schoolName = $schoolCode;
$candidates = [];
$sourceUrl = '';
$error = '';
$servedFromStaleCache = false;

if (!grf_is_valid_exam_year($examYear) || !preg_match('/^PS\d{7}$/D', $schoolCode)) {
    http_response_code(400);
    $error = 'Hakiki mwaka na namba ya shule kisha ujaribu tena.';
} else {
    try {
        $sourceUrl = et_exam_source_url('psle', $examYear, 'school', $schoolCode);
        $response = grf_fetch_result($sourceUrl);
    } catch (Throwable $exception) {
        $response = ['html'=>false, 'status'=>503];
    }
    $html = $response['html'] ?? false;
    $statusCode = $response['status'] ?? 0;
    $servedFromStaleCache = ($response['cache_state'] ?? '') === 'stale_fallback';

    if (!is_string($html) || $html === '' || $statusCode < 200 || $statusCode >= 400) {
        http_response_code($statusCode === 429 ? 429 : 503);
        $error = $statusCode === 429
            ? 'Umefikia kiwango cha maombi. Subiri dakika moja kisha ujaribu tena.'
            : 'Chanzo cha matokeo ya shule hakipatikani kwa sasa. Tafadhali jaribu tena baadaye.';
    } else {
        $previousLibxmlState = libxml_use_internal_errors(true);
        $dom = new DOMDocument();
        $loaded = @$dom->loadHTML($html, LIBXML_NONET);
        libxml_clear_errors();
        libxml_use_internal_errors($previousLibxmlState);

        if (!$loaded) {
            http_response_code(503);
            $error = 'Taarifa za matokeo hazikuweza kusomwa kwa sasa.';
            et_record_system_event('result_html_parse_failed', 'The upstream PSLE school result page could not be parsed as HTML.', 'error', ['target_url' => $sourceUrl, 'exam_type' => 'PSLE']);
        } else {
            foreach ($dom->getElementsByTagName('p') as $paragraph) {
                $paragraphText = trim(preg_replace('/\s+/', ' ', $paragraph->textContent) ?? '');
                if (preg_match('/^(.+)\s-\s' . preg_quote($schoolCode, '/') . '$/i', $paragraphText, $matches)) {
                    $schoolName = trim($matches[1]);
                    break;
                }
            }

            $candidatePattern = '/^' . preg_quote($schoolCode, '/') . '-\d{3,4}$/D';
            $candidateRows = [];
            foreach ($dom->getElementsByTagName('tr') as $tableRow) {
                $cells = [];
                foreach ($tableRow->getElementsByTagName('td') as $cell) {
                    $cells[] = trim(preg_replace('/\s+/', ' ', $cell->textContent) ?? '');
                }

                $candidateNumber = strtoupper($cells[0] ?? '');
                if (count($cells) < 4 || !preg_match($candidatePattern, $candidateNumber)) {
                    continue;
                }

                $candidateRows[$candidateNumber] = [
                    'number' => $candidateNumber,
                    'sex' => $cells[2] ?? '',
                    'subjects' => $cells[count($cells) - 1],
                ];
            }
            $candidates = array_values($candidateRows);

            if ($candidates === []) {
                http_response_code(503);
                $error = 'Hakuna matokeo ya watahiniwa yaliyopatikana kwa shule hii.';
                et_record_system_event('result_parse_empty', 'No PSLE candidate rows were found for the selected school.', 'warning', ['target_url' => $sourceUrl, 'exam_type' => 'PSLE']);
            } else {
                et_record_search_success();
            }
        }
    }
}

$examYearLabel = is_int($examYear) ? $examYear : '';
$escapedSchoolName = htmlspecialchars($schoolName, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="sw">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow, noarchive">
    <meta name="referrer" content="same-origin">
    <meta name="theme-color" content="#031B4E">
    <meta name="application-name" content="ElimuTaifa">
    <title>Matokeo ya <?= $escapedSchoolName ?> <?= htmlspecialchars((string) $examYearLabel, ENT_QUOTES, 'UTF-8') ?> | ElimuTaifa</title>
    <link rel="icon" type="image/x-icon" href="../../../assets/img/brand/favicon32px.ico">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../../assets/css/style.css">
    <link rel="stylesheet" href="../../../assets/css/consent.css">
    <link rel="stylesheet" href="../../../assets/css/education.css">
    <link rel="stylesheet" href="../../../assets/css/secondary-schools.css?v=20260927.24">
    <link rel="stylesheet" href="assets/css/results.css">
    <script src="../../../assets/js/consent.js" defer></script>
    <script src="../../../assets/js/secondary-schools.js?v=20260927.5" defer></script>
    <script src="../../../assets/js/monitoring.js" defer></script>
    <script src="../../../assets/js/placements.js" defer></script>
</head>
<body class="education-page level-results" data-exam="psle">
    <div class="sidebar-overlay" id="sidebarOverlay"></div>
    <aside class="sidebar" id="sidebar">
        <ul class="sidebar-menu">
            <li><a href="../../../" class="sidebar-item"><i class="fa-solid fa-house"></i><span>Nyumbani</span></a></li>
            <li><a href="../../acsee/" class="sidebar-item"><i class="fa-solid fa-list-check"></i><span>FORM SIX (ACSEE)</span></a></li>
            <li><a href="../../csee/" class="sidebar-item"><i class="fa-solid fa-list-check"></i><span>FORM FOUR (CSEE)</span></a></li>
            <li><a href="../../ftna/" class="sidebar-item"><i class="fa-solid fa-list-check"></i><span>FORM TWO (FTNA)</span></a></li>
            <li><a href="../" class="sidebar-item active"><i class="fa-solid fa-list-check"></i><span>STANDARD 7 (PSLE)</span></a></li>
            <li><a href="../../sfna/" class="sidebar-item"><i class="fa-solid fa-list-check"></i><span>STANDARD 4 (SFNA)</span></a></li>
            <li><a href="../../../contribution/" class="sidebar-item"><i class="fa-solid fa-comments"></i><span>Ask, Contribute, Comment</span></a></li>
        </ul>
        <div class="sidebar-quote"><p>“#position for success”</p></div>
    </aside>
    <div class="main-wrapper">
        <header class="header-banner">
            <div class="header-left">
                <button class="mobile-menu-btn" id="menuToggle" aria-label="Fungua Menyu"><i class="fa-solid fa-bars"></i></button>
                <div class="logo-section">
                    <span class="logo-icon brand-logo" role="img" aria-label="ElimuTaifa logo"></span>
                    <div class="header-title"><span class="site-name">ElimuTaifa</span><p>#position for success</p></div>
                </div>
            </div>
            <div class="datetime-display"><span>Primary School Leaving Examination (PSLE)</span></div>
        </header>
        <div data-et-placement-slot="top" data-et-placement-page="psle-schools" hidden></div>
        <main class="content-container">
            <div class="secondary-school-grid psle-school-results">
                <section class="intro-card secondary-school-context">
                    <span class="secondary-school-eyebrow">PSLE</span>
                    <h2><?= $escapedSchoolName ?></h2>
                    <p>Matokeo ya shule · <?= htmlspecialchars((string) $examYearLabel, ENT_QUOTES, 'UTF-8') ?></p>
                    <?php if ($servedFromStaleCache): ?>
                        <p class="secondary-source-note" role="status">Matokeo yanaoneshwa kutoka kwenye nakala ya muda wakati chanzo hakipatikani.</p>
                    <?php endif; ?>
                    <nav class="secondary-school-links" aria-label="Njia za matokeo">
                        <a href="../">Tafuta shule nyingine</a>
                        <a href="../#index1">Tafuta kwa namba ya mtihani</a>
                        <?php if ($sourceUrl !== ''): ?>
                            <a href="<?= htmlspecialchars($sourceUrl, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener noreferrer">Thibitisha kwenye chanzo rasmi ↗</a>
                        <?php endif; ?>
                    </nav>
                </section>
                <section class="right-column secondary-school-results">
                    <?php if ($error !== ''): ?>
                        <section class="card secondary-school-error" role="alert">
                            <h2>Haikuwezekana kupata matokeo</h2>
                            <p><?= htmlspecialchars($error, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></p>
                            <a class="btn btn-green" href="../">Rudi kutafuta shule</a>
                        </section>
                    <?php else: ?>
                        <section class="school-search secondary-candidate-filter">
                            <label for="school-filter">Tafuta namba ya mtahiniwa</label>
                            <input class="form-input" id="school-filter" type="search" autocomplete="off" placeholder="Andika namba hapa..." aria-controls="school-result-table">
                            <p id="school-count" role="status"></p>
                        </section>
                        <section class="card secondary-school-list" tabindex="0" aria-label="Matokeo ya shule">
                            <div class="secondary-list-heading"><h2>Matokeo ya watahiniwa</h2><span><?= count($candidates) ?> watahiniwa</span></div>
                            <table id="school-result-table">
                                <thead><tr><th>Namba ya mtihani</th><th>Jinsia</th><th>Wastani</th><th>Masomo</th></tr></thead>
                                <tbody>
                                <?php foreach ($candidates as $candidate): ?>
                                    <?php $gradeData = psle_school_subjects($candidate['subjects']); ?>
                                    <tr data-school-search="<?= htmlspecialchars($candidate['number'], ENT_QUOTES, 'UTF-8') ?>">
                                        <td><?= htmlspecialchars($candidate['number'], ENT_QUOTES, 'UTF-8') ?></td>
                                        <td><?= htmlspecialchars($candidate['sex'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></td>
                                        <td><?= htmlspecialchars($gradeData['average'], ENT_QUOTES, 'UTF-8') ?></td>
                                        <td class="secondary-subjects">
                                            <?php if ($gradeData['subjects'] !== []): ?>
                                                <?php foreach ($gradeData['subjects'] as $subject): ?>
                                                    <span class="secondary-subject-chip"><?= htmlspecialchars($subject['name'] . ' - ' . $subject['grade'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></span>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <span class="secondary-subject-chip"><?= htmlspecialchars($candidate['subjects'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </section>
                        <p id="school-empty" hidden>Hakuna mtahiniwa anayelingana na namba hiyo.</p>
                    <?php endif; ?>
                </section>
            </div>
        </main>
        <div data-et-placement-slot="bottom" data-et-placement-page="psle-schools" hidden></div>
        <footer class="footer"><a href="../../../privacy/">Faragha na sera za matumizi</a></footer>
    </div>
</body>
</html>