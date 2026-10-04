<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/content.php';
require_once dirname(__DIR__) . '/includes/monitoring.php';
et_register_fatal_error_monitoring();
header('Cache-Control: no-store, max-age=0');
$scriptName = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? '/announcements/index.php'));
$markerPosition = strpos($scriptName, '/announcements/index.php');
$basePath = $markerPosition === false ? '' : substr($scriptName, 0, $markerPosition);
$siteHome = ($basePath === '' ? '' : $basePath) . '/';
$announcementHome = ($basePath === '' ? '' : $basePath) . '/announcements/';
$database = et_db();
$slug = et_slugify((string) ($_GET['slug'] ?? ''));
$item = null;
$isDetail = $slug !== '';
if ($isDetail) {
    $statement = $database->prepare(<<<'SQL'
SELECT * FROM content_items
WHERE slug=:slug AND status IN ('published','scheduled') AND destination_type='internal'
  AND published_at IS NOT NULL AND published_at <= :now
  AND (expires_at IS NULL OR expires_at > :expires_now)
LIMIT 1
SQL);
    $statement->execute(['slug' => $slug, 'now' => et_utc_now(), 'expires_now' => et_utc_now()]);
    $item = $statement->fetch();
    if ($item) {
        header('Location: ' . $announcementHome . '#announcement-' . rawurlencode((string) $item['slug']), true, 301);
        exit;
    } else {
        http_response_code(404);
        et_record_system_event('announcement_not_found', 'A visitor requested an announcement that is unavailable.', 'warning');
    }
}
et_record_page_view($_SERVER['REQUEST_URI'] ?? '/announcements/');
$items = $isDetail ? [] : et_public_content($database, 100);
$title = $item ? $item['title'] . ' | ElimuTaifa' : ($isDetail ? 'Taarifa haijapatikana | ElimuTaifa' : 'Matangazo na Taarifa za Elimu Tanzania | ElimuTaifa');
$description = $item ? mb_substr((string) $item['excerpt'], 0, 160) : 'Soma matangazo, habari, matokeo, selection na admission mpya za elimu Tanzania kupitia ElimuTaifa.';
$canonical = $item ? 'https://saidmnuby.github.io/elimutaifa/announcements/' . rawurlencode((string) $item['slug']) . '/' : 'https://saidmnuby.github.io/elimutaifa/announcements/';
$imageUrl = $item ? et_content_image_url($item, $basePath) : '';
$youtubeId = $item ? et_content_youtube_id($item) : null;
$hasYoutube = $youtubeId !== null;
foreach ($items as $contentItem) {
    if (et_content_youtube_id($contentItem) !== null) { $hasYoutube = true; break; }
}
$youtubeCoverUrl = et_youtube_thumbnail_url($youtubeId);
$socialImage = $imageUrl !== '' ? $imageUrl : ($youtubeCoverUrl !== '' ? $youtubeCoverUrl : 'https://saidmnuby.github.io/elimutaifa/assets/img/brand/rectangle.jpg');
?>
<!doctype html>
<html lang="sw">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($title, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></title>
    <meta name="description" content="<?= htmlspecialchars($description, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>">
    <meta name="robots" content="<?= $item || !$isDetail ? 'index, follow, max-image-preview:large' : 'noindex, follow' ?>">
    <link rel="canonical" href="<?= htmlspecialchars($canonical, ENT_QUOTES, 'UTF-8') ?>">
    <meta property="og:type" content="<?= $item ? 'article' : 'website' ?>"><meta property="og:locale" content="sw_TZ"><meta property="og:site_name" content="ElimuTaifa">
    <meta property="og:title" content="<?= htmlspecialchars($title, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>"><meta property="og:description" content="<?= htmlspecialchars($description, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>"><meta property="og:url" content="<?= htmlspecialchars($canonical, ENT_QUOTES, 'UTF-8') ?>"><meta property="og:image" content="<?= htmlspecialchars($socialImage, ENT_QUOTES, 'UTF-8') ?>">
    <meta name="twitter:card" content="summary_large_image"><meta name="twitter:title" content="<?= htmlspecialchars($title, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>"><meta name="twitter:description" content="<?= htmlspecialchars($description, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>"><meta name="twitter:image" content="<?= htmlspecialchars($socialImage, ENT_QUOTES, 'UTF-8') ?>">
    <link rel="icon" href="<?= htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8') ?>/assets/img/brand/favicon32px.ico"><link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet"><link rel="stylesheet" href="<?= htmlspecialchars($announcementHome, ENT_QUOTES, 'UTF-8') ?>style.css?v=20261004.3">
    <?php if ($hasYoutube): ?><script src="<?= htmlspecialchars($announcementHome, ENT_QUOTES, 'UTF-8') ?>media.js" defer></script><?php endif; ?>
    <?php if ($item): ?><script type="application/ld+json"><?= json_encode(['@context'=>'https://schema.org','@type'=>'Article','headline'=>$item['title'],'description'=>$item['excerpt'],'datePublished'=>str_replace(' ','T',$item['published_at']).'Z','dateModified'=>str_replace(' ','T',$item['updated_at']).'Z','mainEntityOfPage'=>$canonical,'publisher'=>['@type'=>'Organization','name'=>'ElimuTaifa','url'=>'https://saidmnuby.github.io/elimutaifa/']], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?></script><?php endif; ?>
    <script src="../assets/js/placements.js" defer></script>
<script src="browse.js?v=20261004.1" defer></script><link rel="stylesheet" href="<?= htmlspecialchars($basePath,ENT_QUOTES,'UTF-8') ?>/assets/css/whatsapp-links.css?v=20261004.1"></head>
<body>
<header class="public-header"><a class="public-brand" href="<?= htmlspecialchars($siteHome, ENT_QUOTES, 'UTF-8') ?>"><img src="<?= htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8') ?>/assets/img/brand/circle_logo.png" alt=""><span>ElimuTaifa</span></a><nav><a href="<?= htmlspecialchars($siteHome, ENT_QUOTES, 'UTF-8') ?>#exam-levels">Matokeo</a><a href="<?= htmlspecialchars($announcementHome, ENT_QUOTES, 'UTF-8') ?>">Matangazo</a><a href="<?= htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8') ?>/contact/">Msaada</a></nav></header>
        <div data-et-placement-slot="top" data-et-placement-page="announcements" hidden></div>
<main class="public-main">
<?php if ($item): ?>
    <a class="back-link" href="<?= htmlspecialchars($siteHome, ENT_QUOTES, 'UTF-8') ?>">← Rudi nyumbani</a>
    <article class="article-card">
        <span class="public-eyebrow"><?= htmlspecialchars(ET_CONTENT_CATEGORIES[$item['category']] ?? 'Taarifa', ENT_QUOTES, 'UTF-8') ?></span>
        <h1><?= htmlspecialchars($item['title'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></h1>
        <p class="public-lead"><?= htmlspecialchars($item['excerpt'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></p>
        <div class="article-meta"><span>Kwa: <?= htmlspecialchars(ET_CONTENT_AUDIENCES[$item['audience']] ?? 'Wote', ENT_QUOTES, 'UTF-8') ?></span><span>•</span><time datetime="<?= htmlspecialchars(str_replace(' ', 'T', $item['published_at']), ENT_QUOTES, 'UTF-8') ?>Z"><?= htmlspecialchars((new DateTimeImmutable($item['published_at'],new DateTimeZone('UTC')))->setTimezone(new DateTimeZone('Africa/Dar_es_Salaam'))->format('j M Y · H:i \E\A\T'), ENT_QUOTES, 'UTF-8') ?></time></div>
        <?php if ($imageUrl !== ''): ?>
            <figure class="article-media"><img src="<?= htmlspecialchars($imageUrl, ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars($item['media_caption'] ?: $item['title'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>" loading="lazy" decoding="async" referrerpolicy="no-referrer"><?php if ($item['media_caption'] !== ''): ?><figcaption><?= htmlspecialchars($item['media_caption'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></figcaption><?php endif; ?></figure>
        <?php elseif ($youtubeId !== null): ?>
            <div class="article-video" data-youtube-id="<?= htmlspecialchars($youtubeId, ENT_QUOTES, 'UTF-8') ?>" data-youtube-cover="<?= htmlspecialchars($youtubeCoverUrl, ENT_QUOTES, 'UTF-8') ?>" data-video-title="<?= htmlspecialchars($item['media_caption'] ?: $item['title'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>"><a class="article-video-fallback" href="<?= htmlspecialchars($item['media_url'], ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener noreferrer"><img class="article-video-poster" src="<?= htmlspecialchars($youtubeCoverUrl, ENT_QUOTES, 'UTF-8') ?>" alt="" loading="lazy" decoding="async" referrerpolicy="no-referrer"><span class="article-video-trigger-overlay"><span class="article-video-play-icon" aria-hidden="true">▶</span><span class="article-video-trigger-copy"><strong><?= htmlspecialchars($item['media_caption'] ?: $item['title'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></strong><small>Fungua video kwenye YouTube</small></span></span></a></div>
            <?php if ($item['media_caption'] !== ''): ?><p class="article-media-caption"><?= htmlspecialchars($item['media_caption'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></p><?php endif; ?>
        <?php endif; ?>
        <div class="article-body"><?= htmlspecialchars($item['body'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></div>
        <?php if ($item['source_url'] !== ''): ?><div class="article-source">Chanzo: <a href="<?= htmlspecialchars($item['source_url'], ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener noreferrer"><?= htmlspecialchars($item['source_name'] ?: 'Fungua chanzo', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?> ↗</a></div><?php endif; ?>
    </article>
<?php elseif ($isDetail): ?>
    <span class="public-eyebrow">404</span><h1>Taarifa haijapatikana</h1><p class="public-lead">Huenda taarifa hii haijachapishwa, imeisha muda au imehamishwa.</p><a class="back-link" href="<?= htmlspecialchars($siteHome, ENT_QUOTES, 'UTF-8') ?>">← Rudi kwenye ElimuTaifa</a>
<?php else: ?>
    <section class="announcement-hero"><span class="public-eyebrow">TAARIFA ZA ELIMU</span><h1>Matangazo na taarifa za elimu</h1><p class="public-lead">Habari, matokeo, selection na admission — pata taarifa muhimu katika sehemu moja.</p><div class="announcement-hero-links"><a href="<?= htmlspecialchars($siteHome,ENT_QUOTES,'UTF-8') ?>#exam-levels">Tafuta matokeo <span aria-hidden="true">↗</span></a><a href="<?= htmlspecialchars($siteHome,ENT_QUOTES,'UTF-8') ?>#selections">Tafuta selection <span aria-hidden="true">↗</span></a></div></section>
    <form class="announcement-tools" hidden role="search" aria-label="Tafuta matangazo">
        <div><label for="announcement-search">Tafuta taarifa</label><input id="announcement-search" type="search" placeholder="Andika jina au maneno ya taarifa" autocomplete="off"></div>
        <div><label for="announcement-category">Aina ya taarifa</label><select id="announcement-category"><option value="">Taarifa zote</option><?php foreach(ET_CONTENT_CATEGORIES as $key=>$label): ?><option value="<?= htmlspecialchars($key,ENT_QUOTES,'UTF-8') ?>"><?= htmlspecialchars($label,ENT_QUOTES,'UTF-8') ?></option><?php endforeach; ?></select></div>
        <button type="reset">Futa filters</button>
    </form>
    <p class="announcement-count" id="announcement-count" aria-live="polite"><?= count($items) ?> taarifa</p>
    <div class="announcement-grid">
        <?php if (!$items): ?><div class="public-card"><h2>Hakuna tangazo jipya</h2><p>Rudi baadaye kuona taarifa mpya.</p></div><?php endif; ?>
        <?php foreach ($items as $listItem): $href = et_content_href($listItem, './'); $listImage = et_content_image_url($listItem, $basePath); $listVideoId = et_content_youtube_id($listItem); $isExternal = $listItem['destination_type'] === 'external'; ?>
            <article class="public-card" data-category="<?= htmlspecialchars($listItem['category'],ENT_QUOTES,'UTF-8') ?>" data-search="<?= htmlspecialchars($listItem['title'].' '.$listItem['excerpt'].' '.$listItem['body'],ENT_QUOTES | ENT_SUBSTITUTE,'UTF-8') ?>" id="announcement-<?= htmlspecialchars(rawurlencode((string) $listItem['slug']), ENT_QUOTES, 'UTF-8') ?>">
                <?php $previewImage=$listImage ?: ($listVideoId!==null ? et_youtube_thumbnail_url($listVideoId) : ''); ?>
                <?php if($previewImage!==''): ?><div class="announcement-preview"><img src="<?= htmlspecialchars($previewImage,ENT_QUOTES,'UTF-8') ?>" alt="" loading="lazy" decoding="async" referrerpolicy="no-referrer"><?php if($listVideoId!==null): ?><span>▶ Video</span><?php endif; ?></div><?php endif; ?>
                <span class="public-eyebrow"><?= htmlspecialchars(ET_CONTENT_CATEGORIES[$listItem['category']] ?? 'Taarifa', ENT_QUOTES, 'UTF-8') ?></span>
                <h2><?= htmlspecialchars($listItem['title'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></h2>
                <p><?= htmlspecialchars($listItem['excerpt'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></p>
                <div class="announcement-date"><time datetime="<?= htmlspecialchars(str_replace(' ','T',$listItem['published_at']),ENT_QUOTES,'UTF-8') ?>Z"><?= htmlspecialchars((new DateTimeImmutable($listItem['published_at'],new DateTimeZone('UTC')))->setTimezone(new DateTimeZone('Africa/Dar_es_Salaam'))->format('j M Y · H:i \E\A\T'),ENT_QUOTES,'UTF-8') ?></time><span><?= htmlspecialchars(ET_CONTENT_AUDIENCES[$listItem['audience']] ?? 'Wote',ENT_QUOTES,'UTF-8') ?></span></div>
                <?php if ($isExternal): ?>
                    <a class="announcement-external-link" href="<?= htmlspecialchars($href, ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener noreferrer">Fungua taarifa →</a>
                <?php else: ?>
                    <details class="announcement-details"><summary><span class="read-more">Soma zaidi</span><span class="read-less">Funga taarifa</span><span aria-hidden="true">↓</span></summary><div class="announcement-full-content">
                            <?php if ($listImage !== ''): ?><figure class="article-media"><img src="<?= htmlspecialchars($listImage, ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars($listItem['media_caption'] ?: $listItem['title'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>" loading="lazy" decoding="async" referrerpolicy="no-referrer"><?php if ($listItem['media_caption'] !== ''): ?><figcaption><?= htmlspecialchars($listItem['media_caption'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></figcaption><?php endif; ?></figure><?php elseif ($listVideoId !== null): ?><div class="article-video" data-youtube-id="<?= htmlspecialchars($listVideoId, ENT_QUOTES, 'UTF-8') ?>" data-youtube-cover="<?= htmlspecialchars(et_youtube_thumbnail_url($listVideoId), ENT_QUOTES, 'UTF-8') ?>" data-video-title="<?= htmlspecialchars($listItem['media_caption'] ?: $listItem['title'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>"><a class="article-video-fallback" href="<?= htmlspecialchars($listItem['media_url'], ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener noreferrer"><img class="article-video-poster" src="<?= htmlspecialchars(et_youtube_thumbnail_url($listVideoId), ENT_QUOTES, 'UTF-8') ?>" alt="" loading="lazy" decoding="async" referrerpolicy="no-referrer"><span class="article-video-trigger-overlay"><span class="article-video-play-icon" aria-hidden="true">▶</span><span class="article-video-trigger-copy"><strong><?= htmlspecialchars($listItem['media_caption'] ?: $listItem['title'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></strong><small>Fungua video kwenye YouTube</small></span></span></a></div><?php endif; ?>
                            <?php if(trim($listItem['body'])!==trim($listItem['excerpt'])): ?><div class="article-body"><?= htmlspecialchars($listItem['body'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></div><?php endif; ?>
                            <?php if ($listItem['source_url'] !== ''): ?><div class="article-source">Chanzo: <a href="<?= htmlspecialchars($listItem['source_url'], ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener noreferrer"><?= htmlspecialchars((string)(parse_url($listItem['source_url'],PHP_URL_HOST) ?: 'Fungua chanzo'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?> ↗</a></div><?php endif; ?>
                            <?php if(in_array($listItem['category'],['results','selection'],true)): ?><a class="announcement-action" href="<?= htmlspecialchars($siteHome,ENT_QUOTES,'UTF-8') ?>#<?= $listItem['category']==='results'?'exam-levels':'selections' ?>"><?= $listItem['category']==='results'?'Tafuta matokeo':'Tafuta selection' ?> →</a><?php endif; ?>
                    </div></details>
                <?php endif; ?>
            </article>
        <?php endforeach; ?>
    </div>
    <div class="announcement-empty" id="announcement-empty" hidden><h2>Hakuna taarifa inayolingana</h2><p>Jaribu maneno mengine au chagua taarifa zote.</p><button type="button" id="announcement-clear">Onyesha taarifa zote</button></div>
<?php endif; ?>
    <a class="et-whatsapp-channel" href="https://whatsapp.com/channel/0029VbDeDTQAzNbpq2EF6F2l" target="_blank" rel="noopener noreferrer"><img src="<?= htmlspecialchars($basePath,ENT_QUOTES,'UTF-8') ?>/assets/img/brand/whatsapp.svg" alt=""><span><strong>Fuata taarifa WhatsApp</strong><small>Jiunge na Channel ya ElimuTaifa kwa taarifa za elimu, matokeo na selection.</small></span><span aria-hidden="true">↗</span></a></main><div data-et-placement-slot="bottom" data-et-placement-page="announcements" hidden></div><footer class="public-footer">ElimuTaifa ni jukwaa huru la taarifa za elimu. <a href="<?= htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8') ?>/privacy/">Privacy Policy na Terms</a></footer>
</body></html>
