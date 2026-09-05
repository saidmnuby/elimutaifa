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
    <title>Angalia Matokeo ya Primary and Secondary</title>
    <!-- FontAwesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="../../assets/css/consent.css">
    <script src="../../assets/js/consent.js"></script>
    <script src="assets/js/script.js"></script>
    
</head>
<body>

    <!--this is   -->
    <div class="alert-box" >
        <section class="in-alert success">
            <p>
                <?php echo htmlspecialchars($successMessage); ?>
                <span class="close-btn" onclick="this.parentElement.parentElement.style.display='none';"> &times;</span></p>
        </section>

    </div>

    <!-- Mobile Navigation Overlay -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <!-- Collapsible Sidebar -->
    <aside class="sidebar" id="sidebar">
        <ul class="sidebar-menu">
            <li>
                <a href="../" class="sidebar-item">
                    <i class="fa-solid fa-house"></i>
                    <span>Nyumbani</span>
                </a>
            </li>
            <li>
                <a href="../../acsee/" class="sidebar-item active">
                   <i class="fa-solid fa-list-check"></i>
                    <span>FORM SIX (ACSEE)</span>
                </a>
            </li>
            <li>
                <a href="../../csee/" class="sidebar-item">
                   <i class="fa-solid fa-list-check"></i>
                    <span>FORM FOUR (CSEE)</span>
                </a>
            </li>
            <li>
                <a href="../../ftna/" class="sidebar-item">
                   <i class="fa-solid fa-list-check"></i>
                    <span>FORM TWO (FTNA)</span>
                </a>
            </li>
            <li>
                <a href="../../psle/" class="sidebar-item">
                   <i class="fa-solid fa-list-check"></i>
                    <span>STANDARD SEVEN</span>
                </a>
            </li>
            <li>
                <a href="../../sfna/" class="sidebar-item">
                   <i class="fa-solid fa-list-check"></i>
                    <span>STANDARD TWO</span>
                </a>
            </li><br>
            <li>
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

    

    <!-- Right Main Wrapper -->
    <div class="main-wrapper">
        
        <!-- Sticky Header Banner -->
        <header class="header-banner">
        <header class="header-banner home-header">
            <div class="header-left">
                <button class="mobile-menu-btn" id="menuToggle" aria-label="Fungua menyu"><i class="fa-solid fa-bars"></i></button>
                <div class="header-title"><h1>ElimuTaifa</h1><p>www.elimutaifa.com</p></div>
            </div>
            <div class="datetime-display"><i class="fa-solid fa-circle-info"></i><span>ABOUT-ELIMUTAIFA</span></div>
        </header>

        <!-- Main Body Content -->
        <main class="content-container">
            

            <!-- Two Column Section -->
            <div class="grid-layout">
                
                <!-- Secondary Info Section -->
                <section class="intro-card">
                    <div class="badge-fast">
                        <i class="fa-solid fa-bolt"></i> ACSEE Examination Results
                    </div>
                    <h2 >MATOKEO YA NECTA</h2>
                    <h3 class="intro-title">Form Six <br> (Kidato cha Sita)</h3>
                    <p class="intro-desc">Pata matokeo yako ya mtihani wa Kidato cha Sita (ACSEE) kwa haraka na kwa urahisi. Ingiza namba yako ya mtihani kisha uchague mwaka uliofanya mtihani.</p>
                </section>

                <?php if (!empty($result)): ?>
                <!-- Right Column Forms -->
                <div class="right-column">
                    <div class="card">
                    <h2 id="rh2">EXAMINATION RESULTS ACSEE<?php echo htmlspecialchars($examYear);?></h2>
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
                    <a href="<?php echo htmlspecialchars($url) ?>" target="_blank" rel="noopener noreferrer">Please Visit NECTA pages for confirmation ▶▷</a>

                    </div>

                </div>
            </div>
        </main>

        <!-- Footer Banner -->
        <footer class="footer">
            <div class="footer-text">
            <span>&copy; 2026 ElimuTaifaTaifa <b>·</b> Techware47</span>
            </div>
            <div class="footer-links">
                <a href="../../privacy/">Sera za Matumizi</a> |
                <a href="../../privacy/">Faragha</a>
            </div>
        </footer>
    </div>

    
</body>

</html>