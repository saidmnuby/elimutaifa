<?php
declare(strict_types=1);
require_once dirname(__DIR__, 2).'/includes/form_five.php';
require_once dirname(__DIR__, 2).'/includes/selection_navigation.php';
require_once dirname(__DIR__, 2).'/includes/selection_activity.php';
$request=et_selection_request('form-five',false);
header('Cache-Control: no-store'); header('Referrer-Policy: same-origin');
function f5_e(mixed $value): string { return htmlspecialchars((string)$value,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8'); }
$cycles=[]; $cycle=''; $action=(string)($request['action']??'');
$region=(string)($request['region']??''); $council=(string)($request['council']??''); $school=(string)($request['school']??'');
$entries=[]; $records=[]; $error=''; $heading='Form Five selection'; $stage=''; $source=''; $fetchedAt=null;
try {
    $cycles=f5_cycles();
    if(!$cycles) throw new RuntimeException('NO_CYCLES');
    $cycle=(string)(($request['cycle']??'')?:array_key_first($cycles));
    if (!isset($cycles[$cycle])) throw new InvalidArgumentException('INVALID_SELECTION');
    if ($action!=='') {
        if (!in_array($action,['browse'],true)) throw new InvalidArgumentException('INVALID_SELECTION');
        $base=$cycles[$cycle]['url']; $response=f5_fetch($base); $entries=f5_links($response['html'],$base); $stage='region'; $heading='Chagua mkoa uliosoma';
        if ($region!=='') {
            $selected=f5_entry($entries,$region); $base=$selected['url']; $response=f5_fetch($base); $entries=f5_links($response['html'],$base); $stage='council'; $heading='Chagua halmashauri · '.$selected['name'];
        }
        if ($council!=='') {
            if ($region==='') throw new InvalidArgumentException('INVALID_SELECTION');
            $selected=f5_entry($entries,$council); $base=$selected['url']; $response=f5_fetch($base); $entries=f5_links($response['html'],$base,true); $stage='school'; $heading='Shule za sekondari · '.$selected['name'];
        }
        if ($school!=='') {
            if ($council==='') throw new InvalidArgumentException('INVALID_SELECTION');
            $selected=f5_entry($entries,$school); $base=$selected['url']; $response=f5_fetch($base); $records=f5_records($response['html'],$base,$school,(int)$cycles[$cycle]['exam_year']); $heading=$selected['name']; $entries=[]; $stage='records';
        }
        $source=$base; $fetchedAt=$response['fetched_at']??time();
    }
} catch (Throwable $exception) {
    $code=$exception->getMessage();
    et_selection_report_failure('form-five',$code,$base??'');
    $error=match($code) {
        'NO_CYCLES'=>'Hakuna selection cycle iliyochapishwa kwa sasa. Jaribu tena baadaye.',
        'INVALID_SELECTION'=>'Chaguo halijapatikana. Anza tena au tumia njia ya mkoa na halmashauri.',
        'RATE_LIMIT'=>'Requests zimekuwa nyingi. Subiri dakika moja kisha jaribu tena.',
        'SOURCE_FORMAT'=>'Muundo wa source haujatambulika au orodha haina candidate rows zinazotarajiwa. Hatuwezi kuthibitisha selection kwa sasa.',
        default=>'Chanzo cha TAMISEMI hakijapatikana kwa sasa. Jaribu tena baadaye; hili halimaanishi kwamba mwanafunzi hakuchaguliwa.'
    };
    $entries=[]; $records=[]; http_response_code($exception instanceof InvalidArgumentException?400:503);
}
$isResponse=$action!=='';
// Directory-only responses reuse the validated source hierarchy, not rendered HTML.
if (($_GET['directory'] ?? '') === '1') {
    header('Content-Type: application/json; charset=utf-8');
    header('X-Robots-Tag: noindex, nofollow, noarchive');
    echo json_encode(['stage' => $stage, 'error' => $error, 'entries' => array_map(
        static fn(array $entry): array => ['id' => $entry['id'], 'name' => $entry['name']],
        $entries
    )], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}
if ($error === '' && $stage === 'records' && !empty($records)) et_record_search_success();
if($isResponse || $error!=='') header('X-Robots-Tag: noindex, nofollow, noarchive');
$cycleInfo=$cycles[$cycle]??null;
$placementPage=et_selection_placement_page('form-five',$stage,$error!=='');
?>
<!doctype html><html lang="sw"><head><meta name="theme-color" content="#031B4E"><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title><?= f5_e($heading) ?> | ElimuTaifa</title><meta name="robots" content="<?= ($isResponse || $error!=='')?'noindex,nofollow,noarchive':'index,follow' ?>"><meta name="description" content="Tafuta uchaguzi wa Kidato cha Tano na vyuo vya kati kupitia shule ya sekondari kupitia ElimuTaifa."><link rel="canonical" href="https://saidmnuby.github.io/elimutaifa/b.io/elimutaifa/selection/form-five/"><link rel="icon" href="../../assets/img/brand/favicon32px.ico"><link rel="stylesheet" href="../../assets/css/style.css"><link rel="stylesheet" href="../../assets/css/education.css"><link rel="stylesheet" href="style.css?v=20260916.1"><link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"><script src="script.js" defer></script><script src="../../assets/js/placements.js" defer></script><script src="../../assets/js/monitoring.js" defer></script></head>
<body class="education-page <?= $isResponse?'level-results':'level-landing' ?>" data-exam="form-five"><div class="sidebar-overlay" id="sidebarOverlay"></div><?php require_once dirname(__DIR__, 2).'/includes/selection_sidebar.php'; et_selection_sidebar('form-five'); ?>
<div class="main-wrapper"><header class="header-banner"><div class="header-left"><button class="mobile-menu-btn" id="menuToggle" aria-label="Fungua menyu"><i class="fa-solid fa-bars"></i></button><div class="logo-section"><span class="logo-icon brand-logo" role="img" aria-label="ElimuTaifa logo"></span><div class="header-title"><h1>ElimuTaifa</h1><p>#Prepare for success</p></div></div></div><div class="datetime-display">Form Five Selection</div></header>

<div data-et-placement-slot="top" data-et-placement-page="<?= f5_e($placementPage) ?>" hidden></div>
<main class="content-container"><div class="fo-grid"><section class="intro-card fo-context">
    <div class="badge-fast">FORM FIVE SELECTION</div>
    <h2><?= f5_e($heading) ?></h2>
    <?php if($cycleInfo): ?><div class="fo-cycle"><strong>Intake <?= f5_e($cycleInfo['intake_year']) ?></strong><span>CSEE <?= f5_e($cycleInfo['exam_year']) ?> · <?= f5_e($cycleInfo['round_label']) ?></span></div><?php endif; ?>
    <?php if(!$isResponse): ?><p class="fo-guide">Chagua mkoa, halmashauri na shule uliyosoma.</p><?php endif; ?>
    <div class="fo-context-footer">
        <?php if($fetchedAt): ?><p class="fo-meta">Data: <?= f5_e((new DateTimeImmutable('@'.$fetchedAt))->setTimezone(new DateTimeZone('Africa/Dar_es_Salaam'))->format('d/m/Y H:i')) ?> EAT</p><?php endif; ?>
        <nav class="fo-context-actions" aria-label="Search na source"><a href="./">Search mpya</a><?php if($source): ?><a href="<?= f5_e($source) ?>" target="_blank" rel="noopener noreferrer">Source rasmi ↗</a><?php endif; ?></nav>
    </div>
</section>
<section class="right-column fo-column"><?php if($error): ?><div class="fo-error" role="alert"><?= f5_e($error) ?></div><?php endif; ?>
<?php if($cycles && (!$isResponse || $error)): ?><section class="card"><h2>Tafuta kupitia shule</h2><form method="get" action="./"><input type="hidden" name="action" value="browse"><label class="form-label" for="browse-cycle">Selection cycle</label><select class="form-input" id="browse-cycle" name="cycle"><?php foreach($cycles as $key=>$value): ?><option value="<?= f5_e($key) ?>"<?= $cycle===(string)$key?' selected':'' ?>><?= f5_e($value['label']) ?></option><?php endforeach; ?></select><p>Chagua mkoa, halmashauri na shule ya sekondari uliyosoma.</p><button class="btn-submit" type="submit">Chagua mkoa</button></form></section><?php endif; ?>
<?php if($entries): ?><section class="school-search"><label class="form-label" for="fo-filter">Tafuta <?= $stage==='school'?'jina/code ya shule':'jina' ?></label><input class="form-input" type="search" id="fo-filter" placeholder="Anza kuandika…"><p id="fo-count" aria-live="polite"></p></section><section class="card fo-list"><?php foreach($entries as $entry): ?><form method="get" action="./" data-fo-item="<?= f5_e($entry['name']) ?>"><input type="hidden" name="action" value="browse"><input type="hidden" name="cycle" value="<?= f5_e($cycle) ?>"><?php foreach(['region'=>$region,'council'=>$council] as $field=>$value): ?><?php if($field!==$stage && $value!==''): ?><input type="hidden" name="<?= $field ?>" value="<?= f5_e($value) ?>"><?php endif; ?><?php endforeach; ?><button class="fo-list-button" name="<?= $stage ?>" value="<?= f5_e($entry['id']) ?>"><?= f5_e($entry['name']) ?> <span>👆🏽</span></button></form><?php endforeach; ?><p id="fo-empty" hidden>Hakuna chaguo linalolingana.</p></section><?php endif; ?>
<?php if($records): require __DIR__ . '/_results.php'; endif; ?>
<script src="../../assets/js/selection-directory.js?v=20260920.2" defer></script>
</section></div></main><div data-et-placement-slot="bottom" data-et-placement-page="<?= f5_e($placementPage) ?>" hidden></div><footer class="footer"><div class="footer-text">© 2026 ElimuTaifa</div><div class="footer-links"><a href="../../privacy/">Faragha na sera za matumizi</a></div></footer></div></body></html>
