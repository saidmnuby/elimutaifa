<?php
include 'validationEngine.php'; // Include the file with that validate input candidate ID and exam details and year.
include_once dirname(__DIR__, 3) . '/includes/result_request.php';
$result = [];

if ($candidate != '') {

    // NECTA URL
    

    $response = grf_fetch_result($url);
    $html = $response['html'];
    $statusCode = $response['status'];

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

    // Create DOM
    $dom = new DOMDocument();

    libxml_use_internal_errors(true);
    $dom->loadHTML($html);
    libxml_clear_errors();
    
    $paragraphs = $dom->getElementsByTagName("p");

    foreach ($paragraphs as $p) {
        $text = trim($p->textContent);
        if (preg_match('/^[A-Z]+\d+\s+.+/i', $text)) {
            break;
        }
    }

    // Get table rows
    $rows = $dom->getElementsByTagName("tr");

    foreach ($rows as $row) {

        $cells = $row->getElementsByTagName("td");

        // Make sure the row has enough cells
        if ($cells->length >= 5) {

            // Get CNO
            $cno = trim($cells[0]->textContent);

            // Check candidate ID
            if ($cno == $candidate) {

                // Get subjects cell
                $subjects = trim($cells[4]->textContent);

                //get sex cell..
                $sex = trim($cells[1]->textContent);

                //get aggt cell..
                $aggt = trim($cells[2]->textContent);

                //get div cell..
                $div = trim($cells[3]->textContent);

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
}


    if(empty($result)){
    et_record_system_event('result_parse_empty', 'No candidate row was found; the upstream layout or requested record may have changed.', 'warning', ['target_url' => $url ?? '', 'exam_type' => 'CSEE']);
    $_SESSION['error_title'] = "Errorr_<H002>";
    $_SESSION['error_message'] = "Hakiki taarifa au tembelea official pages za NECTA";
    $_SESSION['nectaStatement'] = "visit NECTA pages ▶▷";
    $_SESSION['NECTA'] = "https://necta.go.tz";
    $_SESSION['style'] = "warning-alert";
    header("Location: ../error/");
    exit();
                            
}else{
    $_SESSION['success_message'] = "Matokeo ya $candidate mwaka $examYear yamepatikana ";
    $_SESSION['style'] = "success";
}


