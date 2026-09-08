<?php
include 'validationEngine.php'; 
include_once dirname(__DIR__, 2) . '/includes/result_request.php';
// Include the file with that validate input candidate ID and exam details and year.
$result = [];
$text   = '';
$sex    = 'N/A';
$div    = 'N/A';
$aggt   = 'N/A';

if ($candidate !== '' && isset($url)) {

    $response = grf_fetch_result($url);
    $html = $response['html'];
    $statusCode = $response['status'];

    if ($html === false || $html === '' || $statusCode < 200 || $statusCode >= 400) {
        $_SESSION['error_title'] = "Errorr_<H001>";
        $_SESSION['error_message'] = "Taarifa hazipatikani katika data za mifumo, Tafathali jaribu  baadae.";
        $_SESSION['style'] = "warning-alert";
        header("Location: ../error/");
        exit();
    }

    if ($html !== false) {
        $dom = new DOMDocument();
        libxml_use_internal_errors(true);
        if (!@$dom->loadHTML($html)) {
        $_SESSION['error_title'] = "Errorr_<H001B>";
            $_SESSION['error_message'] = "Taarifa hazipatikani katika data za mifumo, Tafathali jaribu  baadae.";
            $_SESSION['style'] = "warning-alert";
            header("Location: ../error/");
            exit();
        }
        libxml_clear_errors();

        // Extract the school heading; keep a generic paragraph as a fallback.
        $paragraphs = $dom->getElementsByTagName("p");
        $schoolName = '';
        $schoolPattern = '/^.+\s-\s' . preg_quote($school_id, '/') . '$/i';
        foreach ($paragraphs as $p) {
            $pText = trim(preg_replace('/\s+/', ' ', $p->textContent));

            if (preg_match($schoolPattern, $pText)) {
                $text = $pText;
                break;
            }

            if ($schoolName === '' && !empty($pText) && preg_match('/^[A-Z0-9\s\-]+$/i', $pText)) {
                $schoolName = $pText;
            }
        }

        if ($text === '') {
            $text = $schoolName;
        }

        // Parse Table Rows
        $rows = $dom->getElementsByTagName("tr");

        foreach ($rows as $row) {
            $cells = $row->getElementsByTagName("td");

            if ($cells->length >= 4) {
                $cno = strtoupper(trim($cells->item(0)->textContent));

                if ($cno === strtoupper($candidate)) {
                    $prem_no  = trim($cells->item(1)->textContent);
                    $sex      = trim($cells->item(2)->textContent);
                    $subjects = trim($cells->item($cells->length - 1)->textContent);

                    // Extract Subjects & Grades
                    // Extract Subjects & Grades (supports &, ., and letters/spaces)

                    preg_match_all("/([A-Za-z\&\.\s]+)\s*-\s*([A-F])/i", $subjects, $matches);
                    for ($i = 0; $i < count($matches[1]); $i++) {
                        $subjName = trim($matches[1][$i]);
                        
                        if (strcasecmp($subjName, 'Average Grade') === 0) {
                            continue;
                        }
                        
                        $result[] = ["subject" => $subjName,"grade"   => strtoupper($matches[2][$i])];
                    }

                    break;
                }
            }
        }
    }
}

// Check if results were retrieved
if (empty($result)) {
    $_SESSION['error_title'] = "Errorr_<H002>";
    $_SESSION['error_message'] = "Hakiki taarifa au tembelea official pages za NECTA";
    $_SESSION['nectaStatement'] = "visit NECTA pages ▶▷";
    $_SESSION['NECTA'] = "https://necta.go.tz";
    $_SESSION['style'] = "warning-alert";
    header("Location: ../error/");
    exit();
}

$_SESSION['success_message'] = "Matokeo ya $candidate mwaka $examYear yamepatikana";
$_SESSION['style'] = "success";

$successMessage = $_SESSION['success_message'];
$style          = $_SESSION['style'];
unset($_SESSION['success_message'], $_SESSION['style']);
?>