<?php

// Retrieve values from GET parameters
$examLevel = isset($_POST['exam_level']) ? filter_var(trim($_POST['exam_level']), FILTER_SANITIZE_FULL_SPECIAL_CHARS) : 'acsee';
$examYear  = isset($_POST['exam_year']) ? filter_var(trim($_POST['exam_year']), FILTER_SANITIZE_NUMBER_INT) : '2025';
$candidate = isset($_POST['candidate']) ? filter_var(trim($_POST['candidate']), FILTER_SANITIZE_FULL_SPECIAL_CHARS) : '';



// Extract before the slash and convert to lowercase
$schoolCode = strtolower(explode('/', $candidate)[0]); // Outputs: p0652


// Validate NECTA format server-side
$nectaPattern = '/^(?:[SPE]Q?\d{4}\/\d{4}|PS\d{6,7}-\d{3,4})$/i';

if (!preg_match($nectaPattern, $candidate)) {
    // Handle invalid candidate number output
    die("Format ya namba ya mtihani sio sahihi.");
}

// Proceed with database query or web scraping using $examLevel, $examYear, $candidate

$result = [];

if ($candidate != '') {

    // NECTA URL
    $url = "https://matokeo.necta.go.tz/results/$examYear/$examLevel/results/$schoolCode.htm";

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


