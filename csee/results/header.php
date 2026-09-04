<?php
include 'validationEngine.php'; // Include the file with that validate input candidate ID and exam details and year.
include_once dirname(__DIR__, 2) . '/includes/result_request.php';
$result = [];

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
    session_start();
    $_SESSION['error_message'] = "namba ya mtihani au kidato siyo sahihi. Tafadhali hakiki namba, kidato na mwaka kisha ujaribu tena.";
    $_SESSION['style'] = "warning-alert";
        header("Location: ../error/");
        exit();
                            
}else{
    $_SESSION['success_message'] = "Matokeo ya $candidate mwaka $examYear yamepatikana ";
    $_SESSION['style'] = "success";
}


