<?php
// Ensure session is active for error propagation
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include_once dirname(__DIR__, 2) . '/includes/result_request.php';

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
        if ($examYear === 2026) {
            $_SESSION['error_title']   = "Error_<X001>";
            $_SESSION['error_message'] = "Taarifa zitapatikana hivi karibun.Jaribu hivi baadae..";
            $_SESSION['style']         = "warning-alert";
            header("Location: ../error/");
            exit();
        }else{
            $_SESSION['error_title']   = "Error_<H001>";
            $_SESSION['error_message'] = "Taarifa hazipatikani katika data za mfumo, Tafathali jaribu baadae.";
            $_SESSION['style']         = "warning-alert";
            header("Location: ../error/");
            exit();
        }
    }

    $dom = new DOMDocument();
    libxml_use_internal_errors(true);
    
    // Prefix UTF-8 metadata to prevent encoding issues with Swahili/special characters
    $loaded = $dom->loadHTML('<?xml encoding="UTF-8">' . $html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
    
    if (!$loaded) {
        $_SESSION['error_title']   = "Error_<H001B>";
        $_SESSION['error_message'] = "Taarifa hazipatikani katika data za mfumo, Tafathali jaribu baadae.";
        $_SESSION['style']         = "warning-alert";
        header("Location: ../error/");
        exit();
    }
    libxml_clear_errors();

    $links   = $dom->getElementsByTagName("a");
    $baseUrl = "https://maktaba.tetea.org/exam-results/SFNA2023/";

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
}
?>