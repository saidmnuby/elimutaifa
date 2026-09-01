<?php
include 'validacsee.php'; // Include the file with valid advanced school IDs

// Retrieve values from GET parameters
$examLevel = isset($_POST['exam_level']) ? filter_var(trim($_POST['exam_level']), FILTER_SANITIZE_FULL_SPECIAL_CHARS) : 'acsee';
$examYear  = isset($_POST['exam_year']) ? filter_var(trim($_POST['exam_year']), FILTER_SANITIZE_NUMBER_INT) : '2025';
$candidate = isset($_POST['candidate']) ? filter_var(trim($_POST['candidate']), FILTER_SANITIZE_FULL_SPECIAL_CHARS) : '';



$parts = explode('/', $candidate);
$school_id = strtoupper(trim($parts[0]));
$schoolCode = strtolower(trim($parts[0]));

// Validate NECTA format server-side
$nectaPattern = '/^(?:[SPE]Q?\d{4}\/\d{4}|PS\d{6,7}-\d{3,4})$/i';

if (!preg_match($nectaPattern, $candidate)) {
    // Handle invalid candidate number output
    session_start();
    $_SESSION['error_message'] = "Invalid candidate ID or exam detailsB.";
    $_SESSION['style'] = "failed-alert";
        header("Location: ../error/");
        exit();
}

// Proceed with database query or web scraping using $examLevel, $examYear, $candidate
if ($examLevel === 'csee' && preg_match('/^[SP]/i', trim($candidate))) {
    if($examYear === '2026' || $examYear === '2025' || $examYear === '2024' || $examYear === '2023'){

        $url = "https://onlinesys.necta.go.tz/results/$examYear/$examLevel/results/$schoolCode.htm";

    }
    
} else {
 

if ($examLevel === 'acsee' && in_array($school_id, $valid_ids, true)) {
    if($examYear === '2023' || $examYear === '2024' || $examYear === '2025' ){

        $url = "https://onlinesys.necta.go.tz/results/$examYear/$examLevel/results/$schoolCode.htm";
        
    }elseif($examYear === '2026'){
        $url = "https://matokeo.necta.go.tz/results/$examYear/$examLevel/results/$schoolCode.htm";
    }
} else {
    session_start();
    $_SESSION['error_message'] = "Invalid candidate ID or exam detailsB.";
    $_SESSION['style'] = "failed-alert";
        header("Location: ../error/");
        exit();
}
  

}
$result = [];

if ($candidate != '') {

    // NECTA URL
    

    // Get webpage
    $html = file_get_contents($url);

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
?>


