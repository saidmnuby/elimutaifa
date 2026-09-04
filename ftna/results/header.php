<?php
include 'validationEngine.php'; // Include the file with that validate input candidate ID and exam details and year.
include_once dirname(__DIR__, 2) . '/includes/result_request.php';
$result = [];
$text = '';

if ($candidate != '') {

    // NECTA URL
    

    $response = grf_fetch_result($url);
    $html = $response['html'];
    $statusCode = $response['status'];

    if ($html === false || $html === '' || $statusCode < 200 || $statusCode >= 400) {
        $_SESSION['error_message'] = "Matokeo hayapatikani kwa sasa. Tafadhali jaribu tena baadaye.";
        $_SESSION['style'] = "warning-alert";
        header("Location: ../error/");
        exit();
    }

    // Create DOM
    $dom = new DOMDocument();

    libxml_use_internal_errors(true);
    $dom->loadHTML($html);
    libxml_clear_errors();
    
    $paragraphs = $dom->getElementsByTagName("p");
    $schoolPattern = '/^' . preg_quote($school_id, '/') . '\s*-\s*.+$/i';
    foreach ($paragraphs as $p) {
        $paragraphText = trim(preg_replace('/\s+/', ' ', $p->textContent));
        if (preg_match($schoolPattern, $paragraphText)) {
            $text = $paragraphText;
            break;
        }
    }

    // FTNA pages contain malformed rows, so read candidate cells in document order.
    $cells = $dom->getElementsByTagName("td");
    for ($i = 0; $i <= $cells->length - 6; $i++) {
        $cno = trim(preg_replace('/\s+/', ' ', $cells->item($i)->textContent));

        if ($cno === $candidate) {
            $sex = trim($cells->item($i + 2)->textContent);
            $aggt = trim($cells->item($i + 3)->textContent);
            $div = trim($cells->item($i + 4)->textContent);
            $subjects = trim(preg_replace('/\s+/', ' ', $cells->item($i + 5)->textContent));

                // Extract subjects and grades
                preg_match_all(
                    "/([A-Z\/ ]+)\s*-\s*'([A-Z])'/",
                    $subjects,
                    $matches
                );

                for ($i = 0; $i < count($matches[1]); $i++) {

                    $result[] = [
                        "subject" => trim($matches[1][$i]),
                        "grade" => $matches[2][$i]
                    ];
                }

                break;
        }
    }
}


    if(empty($result)){
    session_start();
    $_SESSION['error_message'] = "namba ya mtihani au kidato siyo sahihi. Tafadhali hakiki namba, kidato na mwaka kisha ujaribu tena.";
    $_SESSION['style'] = "warning-alert";
        header("Location: ../error/");
        exit();
                            
}else{
    $_SESSION['success_message'] = "Matokeo ya $candidate mwaka $examYear yamepatikana ";
    $_SESSION['style'] = "success";
}


