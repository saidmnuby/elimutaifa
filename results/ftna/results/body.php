<?php
    $successMessage = isset($_SESSION['success_message']) ? $_SESSION['success_message'] : '';
    $style = isset($_SESSION['style']) ? $_SESSION['style'] : '';
    unset($_SESSION['success_message']);
    unset($_SESSION['style']);

?>
<!DOCTYPE html>
<html lang="sw">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow, noarchive">
    <meta name="referrer" content="same-origin">
    <meta name="theme-color" content="#031B4E">
    <meta name="application-name" content="ElimuTaifa">
    <title>Matokeo ya FTNA | ElimuTaifa</title>
    <link rel="icon" type="image/x-icon" sizes="32x32" href="../../../assets/img/brand/favicon32px.ico">
    <link rel="icon" type="image/x-icon" sizes="16x16" href="../../../assets/img/brand/favicon16px.ico">
    <link rel="apple-touch-icon" href="../../../assets/img/brand/circle_logo.png">

    <!-- FontAwesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../../assets/css/style.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="../../../assets/css/consent.css">
    <script src="../../../assets/js/consent.js"></script>
    <script src="../../../assets/js/monitoring.js" defer></script>
    <link rel="stylesheet" href="../../../assets/css/education.css">
    <script src="../../../assets/js/script.js"></script>
    <script src="../../../assets/js/placements.js" defer></script>
</head>
<body class="education-page level-results" data-exam="ftna">
    <!-- Mobile Navigation Overlay -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <!-- Collapsible Sidebar -->
    <aside class="sidebar" id="sidebar">
        <ul class="sidebar-menu">
            <li>
                <a href="../../../" class="sidebar-item">
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
                <a href="../../csee/" class="sidebar-item" >
                   <i class="fa-solid fa-list-check"></i>
                    <span>FORM FOUR (CSEE)</span>
                </a>
            </li>
            <li>
            <li>
                <a href="../../ftna" class="sidebar-item active">
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
                <a href="../../../contribution/" class="sidebar-item">
                   <i class="fa-solid fa-comments"></i>
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
                    <span class="logo-icon brand-logo" role="img" aria-label="ElimuTaifa logo"></span>
                    <div class="header-title">
                        <span class="site-name">ElimuTaifa</span>
                        <p>#position for success</p>
                    </div>
                </div>
            </div>
            <div class="datetime-display">
                <div class="clock-container">
                    <span>Form Two National Assessment (FTNA)</span>
                </div>
            </div>
        </header>

        <!-- Dynamic Content Body -->
        <div data-et-placement-slot="top" data-et-placement-page="ftna-results" hidden></div>
        <main class="content-container">
            <div class="grid-layout">
                
                <!-- Secondary Info Section -->
                <section class="intro-card">
                    <div class="badge-fast">
                        <i class="fa-solid fa-bolt"></i> 
                        <span>FTNA Examination Results</span>
                    </div>
                    <h1 class="intro-title">Matokeo ya Form Two <?php echo htmlspecialchars($examYear);?> (FTNA)</h1>
                    <section class="seo-content">
                        <p>Angalia matokeo ya mtihani wa kidato cha pili
                            (FTNA) kwa mwaka <?php echo htmlspecialchars($examYear);?> kwa
                            kuandika index  namba ya mtihani na mwaka.
                        </p>
                    </section>
                </section>

                <?php if (!empty($result)): ?>
                <!-- Right Column Forms -->
                <div class="right-column">
                    <div class="card">
                    <h2 id="rh2">EXAMINATION RESULTS FTNA <?php echo htmlspecialchars($examYear);?></h2>
                    <h3 class="rh3"><?php echo htmlspecialchars($text);?></h3>
                    <h3 class="rh3" >CANDIDATE INDEX:<span style="text-decoration: underline;"><?php echo htmlspecialchars($candidate);?>/<?php echo htmlspecialchars($examYear); ?></span></h3>
                    <div class="card-one">
                        <table class="center">
                            <tr style="background-color: transparent;">
                                <th>CNO</th>
                                <th>SEX</th>
                                <th>DIVISION</th>
                                <th>AGGREGATE</th>
                                
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
                        <?php 
                        $_SESSION['error_message'] = "namba ya mtihani au ngazi ya Elimu siyo sahihi au haipatikani. Tafadhali hakiki namba, ngazi ya Elimu na mwaka kisha ujaribu tena. au";
                        $_SESSION['nectaStatement'] = "visit NECTA pages ▶▷";
                        $_SESSION['NECTA'] = "https://necta.go.tz";
                        $_SESSION['style'] = "warning-alert";
                        header("Location: ../error/");
                        exit();
                         ?>
                        
                    <?php endif; ?>

                    </div>
                    <a href="<?php echo htmlspecialchars($url) ?>" target="_blank" rel="noopener noreferrer">Thibitisha taarifa hizi kwenye ukurasa wa chanzo ▶</a>

                    </div>

                </div>
            </div>
        </main><div data-et-placement-slot="bottom" data-et-placement-page="ftna-results" hidden></div>

        <!-- Footer Banner -->
        <footer class="footer">
            <div>
               <span>&copy; 2026 ElimuTaifa <b>·</b> Techware47</span>
            </div>
            <div class="footer-links">
                <a href="../../../privacy/">Sera za Matumizi</a> |
                <a href="../../../privacy/">Faragha</a>
            </div>
        </footer>
    </div>

</body>
</html>
