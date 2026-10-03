<?php
$style = isset($style) ? $style : 'warning-alert';
$necta = isset($necta) ? $necta : '';
$nectaStatement = isset($nectaStatement) ? $nectaStatement : '';
$alertTone = match ($style) {
    'failed-alert' => 'failed',
    'warning-alert' => 'warning',
    default => 'info'
};
$alertHeading = match ($alertTone) {
    'failed' => 'Tafadhali kagua taarifa zako',
    'warning' => 'Matokeo hayapatikani kwa sasa',
    default => 'Ombi halijakamilika'
};
$examLabel = trim((string) ($examLabel ?? ''));
$officialUrl = filter_var($necta, FILTER_VALIDATE_URL);
$officialHost = is_string($officialUrl) ? strtolower((string) parse_url($officialUrl, PHP_URL_HOST)) : '';
$showNectaLink = is_string($officialUrl)
    && parse_url($officialUrl, PHP_URL_SCHEME) === 'https'
    && in_array($officialHost, ['necta.go.tz', 'www.necta.go.tz'], true)
    && trim($nectaStatement) !== '';
?>
<section class="right-columnb">
    <section class="alert-panel <?php echo htmlspecialchars($style, ENT_QUOTES, 'UTF-8'); ?>" role="alert" aria-labelledby="error-title">
        <div class="alert-symbol alert-symbol-<?php echo htmlspecialchars($alertTone, ENT_QUOTES, 'UTF-8'); ?>" aria-hidden="true">
            <i class="fa-solid <?php echo $alertTone === 'info' ? 'fa-circle-info' : 'fa-triangle-exclamation'; ?>"></i>
        </div>
        <p class="alert-eyebrow">Taarifa za matokeo ya <?php echo htmlspecialchars($examLabel, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></p>
        <h1 id="error-title"><?php echo htmlspecialchars($alertHeading, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></h1>
        <p class="alert-message"><?php echo htmlspecialchars($error, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></p>
        <?php if ($showNectaLink): ?>
            <a class="official-results-link" href="<?php echo htmlspecialchars($officialUrl, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>" target="_blank" rel="noopener noreferrer">
                Tembelea tovuti rasmi ya NECTA <i class="fa-solid fa-arrow-up-right-from-square" aria-hidden="true"></i>
            </a>
        <?php endif; ?>
        <a class="commit-button" href="../"><i class="fa-solid fa-arrow-left" aria-hidden="true"></i> Rudi kwenye utafutaji wa <?php echo htmlspecialchars($examLabel, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></a>
    </section>
</section>