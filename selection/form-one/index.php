<?php
declare(strict_types=1);
require_once dirname(__DIR__, 2).'/includes/form_one.php';
require_once dirname(__DIR__, 2).'/includes/selection_navigation.php';
require_once dirname(__DIR__, 2).'/includes/selection_activity.php';
$request=et_selection_request('form-one',true);
header('Cache-Control: no-store'); header('Referrer-Policy: same-origin');
function fo_e(mixed $value): string { return htmlspecialchars((string)$value,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8'); }
$cycles=[]; $cycle=''; $action=(string)($request['action']??'');
$region=(string)($request['region']??''); $council=(string)($request['council']??''); $school=(string)($request['school']??'');
$candidate=strtoupper(trim((string)($request['candidate']??''))); $entries=[]; $records=[]; $error=''; $heading='Form One selection'; $stage=''; $source=''; $fetchedAt=null;
try {
    $cycles=fo_cycles();
    if(!$cycles) throw new RuntimeException('NO_CYCLES');
    $cycle=(string)(($request['cycle']??'')?:array_key_first($cycles));
    if($action==='expired') throw new InvalidArgumentException('SEARCH_EXPIRED');
    if (!isset($cycles[$cycle])) throw new InvalidArgumentException('INVALID_SELECTION');
    if ($action!=='') {
        if (!in_array($action,['browse','candidate'],true)) throw new InvalidArgumentException('INVALID_SELECTION');
        if ($action==='candidate') [$hintRegion,$hintCouncil]=fo_candidate_location($candidate);
        $base=$cycles[$cycle]['url']; $response=fo_fetch($base); $entries=fo_links($response['html'],$base); $stage='region'; $heading='Chagua mkoa uliosoma';
        if ($action==='candidate') {
            $found=null; foreach ($entries as $entry) if (strtolower(str_replace(' ','-',$entry['id']))===$hintRegion) $found=$entry;
            if (!$found) throw new RuntimeException('DIRECTORY_LOOKUP'); $region=$found['id'];
        }
        if ($region!=='') {
            $selected=fo_entry($entries,$region); $base=$selected['url']; $response=fo_fetch($base); $entries=fo_links($response['html'],$base); $stage='council'; $heading='Chagua halmashauri · '.$selected['name'];
            if ($action==='candidate') {
                $found=null; foreach ($entries as $entry) if (fo_normalize($entry['name'])===fo_normalize($hintCouncil)) $found=$entry;
                if (!$found) throw new RuntimeException('DIRECTORY_LOOKUP'); $council=$found['id'];
            }
        }
        if ($council!=='') {
            if ($region==='') throw new InvalidArgumentException('INVALID_SELECTION');
            $selected=fo_entry($entries,$council); $base=$selected['url']; $response=fo_fetch($base); $entries=fo_links($response['html'],$base,true); $stage='school'; $heading='Shule za msingi · '.$selected['name'];
            if ($action==='candidate') $school=explode('-',$candidate)[0];
        }
        if ($school!=='') {
            if ($council==='') throw new InvalidArgumentException('INVALID_SELECTION');
            $selected=fo_entry($entries,$school); $base=$selected['url']; $response=fo_fetch($base); $records=fo_records($response['html'],$base,$school); $heading=$selected['name']; $entries=[]; $stage='records';
            if ($action==='candidate') {
                $records=array_values(array_filter($records,static fn($row)=>$row['candidate']===$candidate));
                if (!$records) { $error='Namba hii haijapatikana kwenye orodha ya shule katika round hii. Hii si uthibitisho wa mwisho kwamba mwanafunzi hakuchaguliwa.'; http_response_code(404); }
            }
        }
        $source=$base; $fetchedAt=$response['fetched_at']??time();
    }
} catch (Throwable $exception) {
    $code=$exception->getMessage();
    et_selection_report_failure('form-one',$code,$base??'');
    $error=match($code) {
        'NO_CYCLES'=>'Hakuna selection cycle iliyochapishwa kwa sasa. Jaribu tena baadaye.',
        'SEARCH_EXPIRED'=>'Search hii imeisha muda au si ya kikao hiki. Tafuta mwanafunzi tena hapa chini.',
        'INVALID_CANDIDATE'=>'Namba ya PSLE si sahihi. Mfano: PS2402026-0126.',
        'INVALID_SELECTION'=>'Chaguo halijapatikana. Anza tena au tumia njia ya mkoa na halmashauri.',
        'DIRECTORY_LOOKUP'=>'Hatukuweza kutambua council ya index hii. Tumia school browsing hapa chini.',
        'RATE_LIMIT'=>'Requests zimekuwa nyingi. Subiri dakika moja kisha jaribu tena.',
        'SOURCE_FORMAT'=>'Muundo wa source haujatambulika au orodha haina candidate rows zinazotarajiwa. Hatuwezi kuthibitisha selection kwa sasa.',
        default=>'Chanzo cha TAMISEMI hakijapatikana kwa sasa. Jaribu tena baadaye; hili halimaanishi kwamba mwanafunzi hakuchaguliwa.'
    };
    $entries=[]; $records=[]; http_response_code($exception instanceof InvalidArgumentException?400:503);
}
$isResponse=$action!=='';
if($isResponse || $error!=='') header('X-Robots-Tag: noindex, nofollow, noarchive');
$cycleInfo=$cycles[$cycle]??null;
$placementPage=et_selection_placement_page('form-one',$stage,$error!=='');
?>
<!doctype html><html lang="sw"><head><meta name="theme-color" content="#031B4E"><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title><?= fo_e($heading) ?> | ElimuTaifa</title><meta name="robots" content="<?= ($isResponse || $error!=='')?'noindex,nofollow,noarchive':'index,follow' ?>"><meta name="description" content="Tafuta uchaguzi wa Kidato cha Kwanza kwa namba ya PSLE au shule ya msingi kupitia ElimuTaifa."><link rel="canonical" href="https://elimutaifa.com/selection/form-one/"><link rel="icon" href="../../assets/img/brand/favicon32px.ico"><link rel="stylesheet" href="../../assets/css/style.css"><link rel="stylesheet" href="../../assets/css/education.css"><link rel="stylesheet" href="style.css?v=20260916.5"><link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"><script src="script.js" defer></script><script src="../../assets/js/placements.js" defer></script><script src="../../assets/js/monitoring.js" defer></script></head>
<body class="education-page <?= $isResponse?'level-results':'level-landing' ?>" data-exam="form-one"><div class="sidebar-overlay" id="sidebarOverlay"></div><?php require_once dirname(__DIR__, 2).'/includes/selection_sidebar.php'; et_selection_sidebar('form-one'); ?>
<div class="main-wrapper"><header class="header-banner"><div class="header-left"><button class="mobile-menu-btn" id="menuToggle" aria-label="Fungua menyu"><i class="fa-solid fa-bars"></i></button><div class="logo-section"><span class="logo-icon brand-logo" role="img" aria-label="ElimuTaifa logo"></span><div class="header-title"><h1>ElimuTaifa</h1><p>#Prepare for success</p></div></div></div><div class="datetime-display">Form One Selection</div></header>
<div data-et-placement-slot="top" data-et-placement-page="<?= fo_e($placementPage) ?>" hidden></div>
<main class="content-container"><div class="fo-grid"><section class="intro-card fo-context">
    <div class="badge-fast">FORM ONE SELECTION</div>
    <h2><?= fo_e($heading) ?></h2>
    <?php if($cycleInfo): ?><div class="fo-cycle"><strong>Intake <?= fo_e($cycleInfo['intake_year']) ?></strong><span>PSLE <?= fo_e($cycleInfo['exam_year']) ?> · <?= fo_e($cycleInfo['round_label']) ?></span></div><?php endif; ?>
    <?php if(!$isResponse): ?><p class="fo-guide">Tafuta kwa index number au chagua shule uliyosoma.</p><?php endif; ?>
    <div class="fo-context-footer">
        <?php if($fetchedAt): ?><p class="fo-meta">Data: <?= fo_e((new DateTimeImmutable('@'.$fetchedAt))->setTimezone(new DateTimeZone('Africa/Dar_es_Salaam'))->format('d/m/Y H:i')) ?> EAT</p><?php endif; ?>
        <nav class="fo-context-actions" aria-label="Search na source"><a href="./">Search mpya</a><?php if($source): ?><a href="<?= fo_e($source) ?>" target="_blank" rel="noopener noreferrer">Source rasmi ↗</a><?php endif; ?></nav>
    </div>
</section>
<section class="right-column fo-column"><?php if($error): ?><div class="fo-error" role="alert"><?= fo_e($error) ?></div><?php endif; ?>
<?php if($cycles && (!$isResponse || $error)): ?><section class="card"><h2>Tafuta mwanafunzi</h2><form method="post"><input type="hidden" name="action" value="candidate"><label class="form-label" for="candidate">Namba ya PSLE <span class="fo-example">Mfano: PS2402026-0126</span></label><input type="text" class="form-input" id="candidate" name="candidate" maxlength="15" pattern="[Pp][Ss][0-9]{7}-[0-9]{3,4}" value="<?= fo_e($candidate) ?>" required autocomplete="off"><label class="form-label" for="cycle">Selection cycle</label><select class="form-input" id="cycle" name="cycle"><?php foreach($cycles as $key=>$value): ?><option value="<?= fo_e($key) ?>"<?= $cycle===(string)$key?' selected':'' ?>><?= fo_e($value['label']) ?></option><?php endforeach; ?></select><button class="btn-submit" type="submit">Angalia selection</button></form></section><section class="card"><h2>Tafuta kupitia shule</h2><form method="get" action="./"><input type="hidden" name="action" value="browse"><label class="form-label" for="browse-cycle">Selection cycle</label><select class="form-input" id="browse-cycle" name="cycle"><?php foreach($cycles as $key=>$value): ?><option value="<?= fo_e($key) ?>"<?= $cycle===(string)$key?' selected':'' ?>><?= fo_e($value['label']) ?></option><?php endforeach; ?></select><p>Chagua mkoa, halmashauri na shule ya msingi uliyosoma.</p><button class="btn-submit" type="submit">Chagua mkoa</button></form></section><?php endif; ?>
<?php if($entries): ?><section class="school-search"><label class="form-label" for="fo-filter">Tafuta <?= $stage==='school'?'jina/code ya shule':'jina' ?></label><input class="form-input" type="search" id="fo-filter" placeholder="Anza kuandika…"><p id="fo-count" aria-live="polite"></p></section><section class="card fo-list"><?php foreach($entries as $entry): ?><form method="get" action="./" data-fo-item="<?= fo_e($entry['name']) ?>"><input type="hidden" name="action" value="browse"><input type="hidden" name="cycle" value="<?= fo_e($cycle) ?>"><?php foreach(['region'=>$region,'council'=>$council] as $field=>$value): ?><?php if($field!==$stage && $value!==''): ?><input type="hidden" name="<?= $field ?>" value="<?= fo_e($value) ?>"><?php endif; ?><?php endforeach; ?><button class="fo-list-button" name="<?= $stage ?>" value="<?= fo_e($entry['id']) ?>"><?= fo_e($entry['name']) ?> <span>👆🏽</span></button></form><?php endforeach; ?><p id="fo-empty" hidden>Hakuna chaguo linalolingana.</p></section><?php endif; ?>
<?php if($records): require __DIR__ . '/_results.php'; endif; ?>
</section></div></main><div data-et-placement-slot="bottom" data-et-placement-page="<?= fo_e($placementPage) ?>" hidden></div><footer class="footer"><div class="footer-text">© 2026 ElimuTaifa</div><div class="footer-links"><a href="../../privacy/">Faragha na sera za matumizi</a></div></footer></div></body></html>
