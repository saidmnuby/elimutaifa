<?php
declare(strict_types=1);
require_once __DIR__ . '/secondary_directory.php';
require_once __DIR__ . '/page_headers.php';
et_send_nonindex_page_headers();

function ss_e(mixed $value): string { return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }

/** Split NECTA's compact subject string into small, readable subject-grade labels. */
function ss_subjects(string $value): array
{
    $matches = [];
    preg_match_all('/(?:^|\s)([A-Za-z][A-Za-z0-9\/ ]*?)\s*-\s*[\'\"]?([A-Za-z*]+)[\'\"]?(?=\s|$)/u', trim($value), $matches, PREG_SET_ORDER);
    if (count($matches) < 2) return [$value];

    return array_map(static fn(array $match): string => trim($match[1]) . ' - ' . $match[2], $matches);
}

$year = filter_input(INPUT_GET, 'year', FILTER_VALIDATE_INT);
$year = is_int($year) ? $year : 2025;
$schoolCode = is_string($_GET['school'] ?? null) ? strtoupper(trim($_GET['school'])) : '';
$selected = null;
$rows = [];
$error = '';
$source = '';
$servedFromStaleCache = false;

try {
    if (!grf_is_valid_exam_year($year) || ($schoolCode !== '' && !preg_match('/^[SP]Q?\d{4}$/D', $schoolCode))) {
        throw new InvalidArgumentException('INVALID_INPUT');
    }
    if ($schoolCode !== '') {
        $selected = et_secondary_directory_school($level, $year, $schoolCode);
        if (!$selected) throw new InvalidArgumentException('SCHOOL_NOT_FOUND');
        $source = $selected['url'];
        $response = et_secondary_fetch_response($source);
        $servedFromStaleCache = ($response['cache_state'] ?? '') === 'stale_fallback';
        $rows = et_secondary_rows((string) $response['html'], $schoolCode, $level);
        if ($rows === []) throw new RuntimeException('SOURCE_FORMAT');
        et_record_search_success();
    }
} catch (Throwable $exception) {
    http_response_code($exception instanceof InvalidArgumentException ? 400 : ($exception->getMessage() === 'RATE_LIMIT' ? 429 : 503));
    $error = match ($exception->getMessage()) {
        'INVALID_INPUT' => 'Hakiki mwaka na code ya shule.',
        'SCHOOL_NOT_FOUND' => 'Shule haipo kwenye orodha ya mwaka huu.',
        'RATE_LIMIT' => 'Subiri dakika moja kisha ujaribu tena.',
        default => 'Matokeo ya shule hayapatikani kwa sasa. Jaribu tena baadaye.'
    };
    if (!$exception instanceof InvalidArgumentException) {
        et_record_system_event('school_results_unavailable', 'School result page could not be loaded.', 'warning', ['exam_type'=>strtoupper($level), 'target_url'=>$source]);
    }
}
$exam = strtoupper($level);
$sourceLabel = str_contains($source, 'maktaba.tetea.org') ? 'Maktaba ya TETEA ↗' : 'Chanzo rasmi ↗';
?>
<!doctype html>
<html lang="sw">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="robots" content="noindex,nofollow,noarchive">
<meta name="referrer" content="same-origin">
<meta name="theme-color" content="#031B4E">
<title><?= ss_e($exam) ?> · <?= ss_e($selected['name'] ?? 'Tafuta shule') ?> | ElimuTaifa</title>
<link rel="icon" href="../../../assets/img/brand/favicon32px.ico">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" integrity="sha512-iecdLmaskl7CVkqkXNQ/ZH/XLlvWZOJyj7Yy7tcenmpD1ypASozpmT/E0iPtmFIB46ZmdtAc9eNBvH0H/ZpiBw==" crossorigin="anonymous" referrerpolicy="no-referrer">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../../../assets/css/style.css">
<link rel="stylesheet" href="../../../assets/css/consent.css">
<link rel="stylesheet" href="../../../assets/css/education.css">
<link rel="stylesheet" href="../../../assets/css/secondary-schools.css?v=20260927.24">
<link rel="stylesheet" href="../../../assets/css/secondary-school-loading.css">
<script src="../../../assets/js/secondary-school-loading.js" defer></script>
<script src="../../../assets/js/consent.js" defer></script>
<script src="../../../assets/js/secondary-schools.js?v=20260927.5" defer></script>
<script src="../../../assets/js/monitoring.js" defer></script>
<script src="../../../assets/js/placements.js" defer></script>
</head>
<body class="education-page level-results" data-exam="<?= ss_e($level) ?>">
<div class="alert-box"><div id="alert-message" class="in-alert"></div></div>
<div class="sidebar-overlay" id="sidebarOverlay"></div>
<aside class="sidebar" id="sidebar">
    <ul class="sidebar-menu">
        <li><a class="sidebar-item" href="../../../"><i class="fa-solid fa-house"></i><span>Nyumbani</span></a></li>
        <?php foreach(['acsee'=>'FORM SIX (ACSEE)','csee'=>'FORM FOUR (CSEE)','ftna'=>'FORM TWO (FTNA)','psle'=>'STANDARD 7 (PSLE)','sfna'=>'STANDARD 4 (SFNA)'] as $key=>$label): ?>
            <li><a class="sidebar-item<?= $key === $level ? ' active' : '' ?>" href="../../<?= ss_e($key) ?>/"><i class="fa-solid fa-list-check"></i><span><?= ss_e($label) ?></span></a></li>
        <?php endforeach; ?>
        <li class="secondary-sidebar-gap"><a class="sidebar-item" href="../../../contribution/"><i class="fa-solid fa-comments"></i><span>Ask, Contribute, Comment</span></a></li>
    </ul>
    <div class="sidebar-quote"><p>“#Prepare for success”</p></div>
</aside>
<div class="main-wrapper">
<header class="header-banner">
    <div class="header-left">
        <button class="mobile-menu-btn" id="menuToggle" aria-label="Fungua menyu">☰</button>
        <div class="logo-section"><span class="logo-icon brand-logo" role="img" aria-label="ElimuTaifa logo"></span><div class="header-title"><span class="site-name">ElimuTaifa</span><p>#Prepare for success</p></div></div>
    </div>
    <div class="datetime-display"><span><?= ss_e($exam) ?> · School results</span></div>
</header>
<div data-et-placement-slot="top" data-et-placement-page="<?= ss_e($level) ?>" hidden></div>
<main class="content-container">
<div class="secondary-school-grid">
    <section class="intro-card secondary-school-context">
        <span class="secondary-school-eyebrow"><?= ss_e($exam) ?></span>
        <h2><?= ss_e($selected['name'] ?? 'Tafuta shule') ?></h2>
        <p><?= $selected ? 'Matokeo ya shule · ' : 'Tafuta kwa jina au code · ' ?><?= ss_e($year) ?></p>
        <?php if ($servedFromStaleCache): ?><p class="secondary-source-note" role="status">Inaoneshwa kutoka cache ya muda wakati source haipatikani. Jaribu tena baadaye kwa toleo jipya.</p><?php endif; ?>
        <nav class="secondary-school-links" aria-label="Njia za matokeo">
            <?php if ($selected): ?><a href="?year=<?= (int) $year ?>">Tafuta shule nyingine</a><?php endif; ?>
            <a href="../">Tafuta kwa index number</a>
            <?php if ($source): ?><a href="<?= ss_e($source) ?>" target="_blank" rel="noopener noreferrer"><?= ss_e($sourceLabel) ?></a><?php endif; ?>
        </nav>
    </section>
    <section class="right-column secondary-school-results">
        <?php if ($error): ?>
            <section class="card secondary-school-error" role="alert"><h2>Haikuwezekana kupata majibu</h2><p><?= ss_e($error) ?></p><a class="btn btn-green" href="?year=<?= (int) $year ?>">Rudi kutafuta shule</a></section>
        <?php elseif ($selected): ?>
            <section class="school-search secondary-candidate-filter">
                <label for="school-filter">Tafuta namba ya mwanafunzi</label>
                <input class="form-input" id="school-filter" type="search" autocomplete="off" placeholder="Andika sehemu ya namba…" aria-controls="school-result-table">
                <p id="school-count" role="status"></p>
            </section>
            <section class="card secondary-school-list" tabindex="0" aria-label="Matokeo ya shule">
                <div class="secondary-list-heading"><h2>Student Results</h2><span><?= count($rows) ?> wanafunzi</span></div>
                <table id="school-result-table"><thead><tr><th>Number</th><th>Sex</th><th>Aggregate</th><th>Division</th><th>Subjects</th></tr></thead>
                <tbody><?php foreach($rows as $row): ?><tr data-school-search="<?= ss_e($row[0]) ?>"><?php foreach($row as $column => $cell): ?><?php if ($column === 4): ?><td class="secondary-subjects"><?php foreach (ss_subjects($cell) as $subject): ?><span class="secondary-subject-chip"><?= ss_e($subject) ?></span><?php endforeach; ?></td><?php else: ?><td><?= ss_e($cell) ?></td><?php endif; ?><?php endforeach; ?></tr><?php endforeach; ?></tbody></table>
            </section>
            <p id="school-empty" hidden>Hakuna mwanafunzi anayelingana na namba hiyo.</p>
        <?php else: ?>
            <section class="card secondary-school-directory">
                <div class="secondary-list-heading"><h2>Andika jina la shule</h2><span>Angalau herufi 2</span></div>
                <div class="secondary-directory-filter">
                    <label class="secondary-directory-label" for="school-filter">Jina au code ya shule</label>
                    <input class="form-input" id="school-filter" type="search" autocomplete="off" placeholder="Mfano: Azania au P0101" data-level="<?= ss_e($level) ?>" data-year="<?= (int) $year ?>" aria-controls="school-directory-results">
                    <p id="school-count" role="status">Andika herufi 2 au zaidi.</p>
                </div>
                <div id="school-directory-results" class="secondary-school-matches" aria-live="polite"></div>
                <p id="school-directory-empty" class="secondary-directory-empty-state">Schools will appear here.</p>
            </section>
        <?php endif; ?>
    </section>
</div>
</main>
<footer class="footer"><a href="../../../privacy/">Faragha na sera za matumizi</a></footer>
</div>
</body>
</html>

