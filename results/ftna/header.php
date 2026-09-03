<?php
include 'validationEngine.php'; 
// Include the file with that validate input candidate ID and exam details and year.
$result = [];
$text   = '';
$sex    = 'N/A';
$div    = 'N/A';
$aggt   = 'N/A';

if ($candidate !== '' && isset($url)) {

    $html = @file_get_contents($url);

    if ($html !== false) {
        $dom = new DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML($html);
        libxml_clear_errors();

        // Extract School Name / Heading Paragraph
        $paragraphs = $dom->getElementsByTagName("p");
        foreach ($paragraphs as $p) {
            $pText = trim($p->textContent);
            if (!empty($pText) && preg_match('/^[A-Z0-9\s\-]+$/i', $pText)) {
                $text = $pText;
                break;
            }
        }

        // Parse Table Rows
        $rows = $dom->getElementsByTagName("tr");

        foreach ($rows as $row) {
            $cells = $row->getElementsByTagName("td");

            if ($cells->length >= 4) {
                $cno = trim($cells->item(0)->textContent);

                if ($cno === $candidate) {
                    $prem_no  = trim($cells->item(1)->textContent);
                    $sex      = trim($cells->item(2)->textContent);
                    $subjects = trim($cells->item(3)->textContent);

                    // Extract Subjects & Grades
                    preg_match_all("/([A-Za-z\s]+)\s*-\s*([A-F])/i", $subjects, $matches);

                    for ($i = 0; $i < count($matches[1]); $i++) {
                        $subjName = trim($matches[1][$i]);

                        if (strcasecmp($subjName, 'Average Grade') === 0) {
                            continue;
                        }

                        $result[] = [
                            "subject" => $subjName,
                            "grade"   => strtoupper($matches[2][$i])
                        ];
                    }

                    break;
                }
            }
        }
    }
}

// Check if results were retrieved
if (empty($result)) {
    $_SESSION['error_message'] = "namba ya mtihani au ngazi ya Elimu siyo sahihi au haipatikani. Tafadhali hakiki namba, ngazi ya Elimu na mwaka kisha ujaribu tena.";
    $_SESSION['nectaStatement'] = "visit NECTA pages ▶▷";
    $_SESSION['NECTA'] = "https://necta.go.tz";
    $_SESSION['style'] = "warning-alert";
    header("Location: ../../error/");
    exit();
}

$_SESSION['success_message'] = "Matokeo ya $candidate mwaka $examYear yamepatikana";
$_SESSION['style'] = "success";

$successMessage = $_SESSION['success_message'];
$style          = $_SESSION['style'];
unset($_SESSION['success_message'], $_SESSION['style']);
?>