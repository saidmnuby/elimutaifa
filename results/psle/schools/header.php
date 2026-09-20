<?php
include_once dirname(__DIR__, 3) . '/includes/result_request.php';


if ($districtz !== '' && isset($url)) {

    $response = grf_fetch_result($url);
    $html = $response['html'] ?? false;
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
    
    if (!@$dom->loadHTML($html)) {
        et_record_system_event('district_html_parse_failed', 'The upstream district page could not be parsed as HTML.', 'error', ['target_url' => $url ?? '', 'exam_type' => 'PSLE']);
        $_SESSION['error_title'] = "Errorr_<H001B>";
        $_SESSION['error_message'] = "Taarifa hazipatikani katika data za mfumo, Tafathali jaribu baadae.";
        $_SESSION['style'] = "warning-alert";
        header("Location: ../error/");
        exit();
    }
    libxml_clear_errors();

    // Target all anchor links containing school entries
    $links = $dom->getElementsByTagName("a");

    foreach ($links as $link) {
    $schoolText = trim(preg_replace('/\s+/', ' ', $link->textContent));
    
    // Capture group 1: Everything before " - PS" (School Name)
    // Capture group 2: The PS ID code (e.g. PS0203101)
    if (preg_match('/^(.*?)\s*-\s*(PS\d+)$/i', $schoolText, $matches)) {
        $schools[] = [
            'full' => $schoolText,
            'name' => trim($matches[1]),              // "AL - IRSHAAD PRIMARY SCHOOL"
            'id'   => strtolower(trim($matches[2]))   // "ps0203101"
        ];
    }
}
    if (empty($schools)) {
        et_record_system_event('district_school_list_empty', 'No school links were found; the district directory layout may have changed.', 'warning', ['target_url' => $url ?? '', 'exam_type' => 'PSLE']);
    }
}
?>
