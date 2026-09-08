<?php
    $successMessage = isset($_SESSION['success_message']) ? $_SESSION['success_message'] : '';
    $style = isset($_SESSION['style']) ? $_SESSION['style'] : '';
    $candidate = isset($_SESSION['candidate']) ? $_SESSION['candidate'] : $candidate;
    $examYear = isset($_SESSION['examYear']) ? $_SESSION['examYear'] : $examYear;
    unset($_SESSION['success_message']);
    unset($_SESSION['style']);

?>

<!DOCTYPE html>
<html lang="sw">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Matokeo ya Form Five Necta | ElimuTaifa</title>
    
    <meta
    name="description"
    content="Angalia matokeo ya Form Four (CSEE) ya NECTA kwa mwaka 2026. Tafuta matokeo kwa namba ya mtihani.">

    <link
    rel="canonical"
    href="https://elimutafuta.com/csee/">

    <meta property="og:title"
    content="Matokeo ya Form Four 2026 - NECTA">

    <meta property="og:description"
    content="Angalia matokeo ya Foem Four mwaka 2026 na Elimutaifa.">

    <meta property="og:type" content="website">
    
    <meta property="og:url"
    content="https://elimutaifa.com/csee/">

    <meta property="og:image"
    content="https://elimutaifa.com/assets/images/psle-results.jpg">

    <!-- FontAwesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
     <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="../../assets/css/consent.css">
    <script src="../../assets/js/consent.js"></script>
    <link rel="stylesheet" href="../../assets/css/education.css">
    <script src="../../assets/js/script.js"></script>

</head>
<body class="education-page">

    <!-- Mobile Navigation Overlay -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <!-- Collapsible Sidebar -->
    <aside class="sidebar" id="sidebar">
        <ul class="sidebar-menu">
            <li>
                <a href="../../" class="sidebar-item">
                    <i class="fa-solid fa-house"></i>
                    <span>Nyumbani</span>
                </a>
            </li>
                <a href="../../acsee/" class="sidebar-item">
                   <i class="fa-solid fa-list-check"></i>
                    <span>FORM SIX (ACSEE)</span>
                </a>
            </li>
            <li>
                <a href="../../csee/" class="sidebar-item active" >
                   <i class="fa-solid fa-list-check"></i>
                    <span>FORM FOUR (CSEE)</span>
                </a>
            </li>
            <li>
            <li>
                <a href="../../ftna" class="sidebar-item">
                   <i class="fa-solid fa-list-check"></i>
                    <span>FORM TWO (FTNA)</span>
                </a>
            </li>
            <li>
                <a href="../../psle/" class="sidebar-item">
                   <i class="fa-solid fa-list-check"></i>
                    <span>STANDARD 7 (PSLE)</span>
                </a>
            </li>
            <li>
            <li>
                <a href="../../sfna/" class="sidebar-item">
                   <i class="fa-solid fa-list-check"></i>
                    <span>STANDARD 4 (SFNA)</span>
                </a>
            </li><br>
                <a href="../../contribution/" class="sidebar-item">
                   <i class="fa-solid fa-circle-info"></i>
                    <span>Ask, Contribute, Comment</span>
                </a>
            </li>
        </ul>
        <div class="sidebar-quote">
            <p>“#position for success”</p>
        </div>
    </aside>

    <!-- Main Content Area -->
    <div class="main-wrapper">
        
        <!-- Sticky Header Banner -->
        <header class="header-banner">
            <div class="header-left">
                <button class="mobile-menu-btn" id="menuToggle" aria-label="Fungua Menyu">
                    <i class="fa-solid fa-bars"></i>
                </button>
                <div class="logo-section">
                    <i class="fa-solid fa-graduation-cap logo-icon"></i>
                    <div class="header-title">
                        <span class="site-name">ElimuTaifa</span>
                        <p>#position for success</p>
                    </div>
                </div>
            </div>
            <div class="datetime-display">
                <span>Certificate of Secondary Education Examination (CSEE)</span>
            </div>
        </header>

        <!-- Dynamic Content Body -->
        <main class="content-container">
            <div class="grid-layout">
                
                <!-- Secondary Info Section -->
                <section class="intro-card">
                    <div class="badge-fast">
                        <i class="fa-solid fa-bolt"></i> 
                        <span>CSEE Examination Results</span>
                    </div>
                    <h1 class="intro-title">Matokeo ya Form Four 2026 (CSEE)</h1>
                    <section class="seo-content">
                        <p>Angalia matokeo ya mtihani wa kidato cha pili
                            (CSEE) mwaka 2026 kwa
                            kuandika index  namba ya mtihani na mwaka.
                        </p>
                    </section>
                </section>

                <?php if (!empty($result)): ?>
                <!-- Right Column Forms -->
                <div class="right-column">
                    <div class="card">
                    <h2 id="rh2">EXAMINATION RESULTS CSEE<?php echo htmlspecialchars($examYear);?></h2>
                    <h3 class="rh3"><?php echo htmlspecialchars($text);?></h3>
                    <h3 class="rh3" >CANDIDATE INDEX:<span style="text-decoration: underline;"><?php echo htmlspecialchars($candidate);?>/<?php echo htmlspecialchars($examYear); ?></span></h3>
                    <div class="card-one">
                        <table class="center">
                            <tr style="background-color: transparent;">
                                <th>CNO</th>
                                <th>SEX</th>
                                <th>DIVISION</th>
                                <th>AGGRIGATE</th>
                                
                            </tr>
                                
                            <tr style="background-color: transparent;">
                                <td><?php echo htmlspecialchars($candidate); ?></td>
                                <td><?php echo htmlspecialchars($sex); ?></td>
                                <td><?php echo htmlspecialchars($div); ?></td>
                                <td><?php echo htmlspecialchars($aggt); ?></td>
                               
                            </tr>

                        </table>
                    </div>

                    
                    <div class="card-one">

                        <table class="left">
                            <tr  style="background-color: transparent;">
                                <th>SUBJECT</th>
                                <th class="center">GRADE</th>
                                <th>COMMENT</th>
                                
                            </tr>
                            
                            
                            <?php foreach ($result as $subject): ?>

                            <tr>
                                <td><?php echo htmlspecialchars($subject["subject"]); ?></td>
                                <td class="center"><?php echo htmlspecialchars($subject["grade"]); ?></td>
                                <td>
                                    <?php 
                                    if ($subject["grade"] == "A") {
                                        $comment = "";
                                        echo htmlspecialchars($comment);

                                    }elseif($subject["grade"] == "B"){
                                        $comment = "";
                                        echo htmlspecialchars($comment);

                                    }elseif($subject["grade"] == "C"){
                                        $comment = "";
                                        echo htmlspecialchars($comment);

                                    }elseif($subject["grade"] == "D"){
                                        $comment = "";
                                        echo htmlspecialchars($comment);

                                    }elseif($subject["grade"] == "F"){
                                        $comment = "";
                                        echo htmlspecialchars($comment);
                                        
                                    }elseif($subject["grade"] == "X"){
                                        $comment = "";
                                        echo htmlspecialchars($comment);
                                        
                                    }else {
                                        $comment = "";
                                        echo htmlspecialchars($comment);
                                    }
                                    ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                                


                        </table>
                        
                    <?php elseif ($candidate != ''): ?>

                        <p>Candidate not found.</p>
                        <?php session_start();
                        $_SESSION['error_message'] = "namba ya mtihani au ngazi ya Elimu siyo sahihi au haipatikani. Tafadhali hakiki namba, ngazi ya Elimu na mwaka kisha ujaribu tena. au";
                        $_SESSION['nectaStatement'] = "visit NECTA pages ▶▷";
                        $_SESSION['NECTA'] = "https://necta.go.tz";
                        $_SESSION['style'] = "warning-alert";
                        header("Location: ../error/");
                        exit();
                         ?>
                        
                    <?php endif; ?>

                    </div>
                    <a href="<?php echo htmlspecialchars($url) ?>" target="_blank" rel="noopener noreferrer">Please Visit NECTA pages for confirmation ▶▷</a>

                    </div>

                </div>
            </div>
        </main>

        <!-- Footer Banner -->
        <footer class="footer">
            <div>
                <span>&copy; 2026 ElimuTaifa <b>·</b> Techware47</span>
            </div>
            <div class="footer-links">
                <a href="../privacy/">Sera za Matumizi</a> |
                <a href="../privacy/">Faragha</a>
            </div>
        </footer>
    </div>

</body>
<script>
    document.addEventListener("DOMContentLoaded", function () {
    const form = document.getElementById("index1");
    const candidateInput = document.getElementById("candidate");
    const alertEl = document.getElementById("alert-message");
    let alertTimer = null;

    // 1. Helper Function to Display Dynamic Alerts
    function showAlert(message, type = "failed") {
        if (!alertEl) return;

        alertEl.classList.remove("failed", "warning", "success", "show");
        alertEl.classList.add("in-alert", type);
        alertEl.textContent = message;
        
        // Restart animation reset
        alertEl.classList.remove("show");
        void alertEl.offsetWidth; // Trigger reflow
        alertEl.classList.add("show");

        if (alertTimer) clearTimeout(alertTimer);
        alertTimer = setTimeout(() => {
            alertEl.classList.remove("show");
        }, 4000);
    }

    // Simple CSEE index-number validation: S1234/0001 or P1234/0001.
    function validateIndexNumber(val) {
        const cleaned = val.trim().toUpperCase();
        const nectaRegex = /^(?:[SP]Q?\d{4}\/\d{4})$/i;
        return nectaRegex.test(cleaned);
    }

    // Validate before submitting the form.
    if (!form || !candidateInput || !alertEl) return;

    form.addEventListener("submit", function (e) {
        const rawValue = candidateInput.value.trim();

        if (!rawValue) {
            e.preventDefault();
            showAlert("Tafadhali ingiza Namba ya Mtihani (Index Number).", "failed");
            candidateInput.focus();
            return;
        }
        

        // Auto format to uppercase
        candidateInput.value = rawValue.toUpperCase();

        if (!validateIndexNumber(candidateInput.value)) {
            e.preventDefault();
            showAlert("Format ya Index Number siyo sahihi! Mfano sahihi: S3743/0037 au P2173/0002", "warning");
            candidateInput.focus();
            return;
        }

        showAlert("Inathibitisha matokeo...", "success");
    });
});
</script>
</html>
