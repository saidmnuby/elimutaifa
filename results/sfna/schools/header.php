<?php
// Ensure session is active for error propagation
require_once dirname(__DIR__, 3) . '/includes/session.php';
grf_start_session();

include_once dirname(__DIR__, 3) . '/includes/result_request.php';

// Initialize fallback variables to prevent Undefined Variable notices
$districtz      = $districtz ?? '';
$examYear       = $examYear ?? null;
$searchKey      = $searchKey ?? '';
$regiontz       = $regiontz ?? '';
$successMessage = $successMessage ?? '';

$schools = [];

if ($districtz !== '' && !empty($url)) {

    $response   = grf_fetch_result($url);
    $html       = $response['html'] ?? false;
    $statusCode = $response['status'] ?? 0;

    if ($html === false || $html === '' || $statusCode < 200 || $statusCode >= 400) {
        $_SESSION['error_title'] = 'Source unavailable';
        $_SESSION['error_message'] = $statusCode === 429
            ? 'Umefikia kiwango cha maombi. Subiri dakika moja kisha ujaribu tena.'
            : 'Chanzo cha matokeo hakipatikani kwa sasa. Tafadhali jaribu tena baadaye.';
        $_SESSION['style'] = 'warning-alert';
        header('Location: ../error/');
        exit;
    }

    $dom = new DOMDocument();
    libxml_use_internal_errors(true);
    
    // Prefix UTF-8 metadata to prevent encoding issues with Swahili/special characters
    $loaded = $dom->loadHTML('<?xml encoding="UTF-8">' . $html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
    
    if (!$loaded) {
        et_record_system_event('district_html_parse_failed', 'The upstream district page could not be parsed as HTML.', 'error', ['target_url' => $url ?? '', 'exam_type' => 'SFNA']);
        $_SESSION['error_title']   = "Error_<H001B>";
        $_SESSION['error_message'] = "Taarifa hazipatikani katika data za mfumo, Tafathali jaribu baadae.";
        $_SESSION['style']         = "warning-alert";
        header("Location: ../error/");
        exit();
    }
    libxml_clear_errors();

    $links   = $dom->getElementsByTagName("a");
    $baseUrl = et_exam_cycle('sfna', $examYear)['base_url'];

    foreach ($links as $link) {
        $schoolText = trim(preg_replace('/\s+/', ' ', $link->textContent));
        $href       = trim($link->getAttribute("href"));

        if (empty($schoolText) || $href === '#' || str_starts_with($href, 'javascript:')) {
            continue;
        }

        // Extract school ID/code using regex pattern matching on href (e.g., shl_ps0101.htm or ps0101)
        preg_match('/(?:shl_)?([a-zA-Z0-9]+)\.htm/i', $href, $matches);
        $schoolId = $matches[1] ?? '';

        // Resolve absolute URL
        $fullUrl = filter_var($href, FILTER_VALIDATE_URL) ? $href : $baseUrl . ltrim($href, '/');

        $schools[] = [
            'id'   => $schoolId,
            'name' => $schoolText,
            'url'  => $fullUrl
        ];
    }
    if (empty($schools)) {
        et_record_system_event('district_school_list_empty', 'No school links were found; the district directory layout may have changed.', 'warning', ['target_url' => $url ?? '', 'exam_type' => 'SFNA']);
    }
}
?>
