<?php
include 'header.php'; // Include the header file
?>
<!DOCTYPE html>
<html lang="sw">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Matokeo ya Form Six Necta | ElimuTaifa</title>
    
    <meta
    name="description"
    content="Angalia matokeo ya Form Four (aCSEE) ya NECTA kwa mwaka 2026. Tafuta matokeo kwa namba ya mtihani.">

    <link
    rel="canonical"
    href="https://elimutafuta.com/error/acsee/">

    <meta property="og:title"
    content="Matokeo ya Form Six 2026 - NECTA">

    <meta property="og:description"
    content="Angalia matokeo ya Form Six mwaka 2026 na Elimutaifa.">

    <meta property="og:type" content="website">
    
    <meta property="og:url"
    content="https://elimutaifa.com/error/acsee/">

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
    

    <!-- Custom Dynamic Alert Container -->
     <div class="alert-box">
        <div id="alert-message" class="in-alert"></div>
    </div>
    
    <section class="right-columnb">
        <section class="alert-panel <?php echo htmlspecialchars($style, ENT_QUOTES, 'UTF-8'); ?>">
            <p>
                <?php echo htmlspecialchars($error); ?><br>
                <a href="<?php echo htmlspecialchars($necta); ?>" target="_blank" rel="noopener noreferrer">
                    <?php echo htmlspecialchars($nectaStatement); ?>
                </a>
                <h6 style="color:black;margin-top:4px;text-align:left;"><?php echo htmlspecialchars($error_title); ?></h6>

            </p>
            <button 
 class="commit-button" onclick="window.location.href='../error/'">OK ▶</button>
        </section>
    </section>
    <main class="blur-background">
    <!--Background blur effect in the main content area -->
    </main> 

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
                <a href="../acsee/" class="sidebar-item active">
                   <i class="fa-solid fa-list-check"></i>
                    <span>FORM SIX (ACSEE)</span>
                </a>
            </li>
            <li>
                <a href="../csee/" class="sidebar-item" >
                   <i class="fa-solid fa-list-check"></i>
                    <span>FORM FOUR (CSEE)</span>
                </a>
            </li>
            <li>
                <a href="../ftna/" class="sidebar-item">
                   <i class="fa-solid fa-list-check"></i>
                    <span>FORM TWO (FTNA)</span>
                </a>
            </li>
            <li>
                <a href="../psle/" class="sidebar-item">
                   <i class="fa-solid fa-list-check"></i>
                    <span>STANDARD 7 (PSLE)</span>
                </a>
            </li>
            <li>
                <a href="../sfna/" class="sidebar-item">
                   <i class="fa-solid fa-list-check"></i>
                    <span>STANDARD 4 (SFNA)</span>
                </a>
            </li><br>
            <li>
                <a href="../contribution/" class="sidebar-item">
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
                    <i class="fa-solid fa-graduation-cap logo-icon"></i>
                    <div class="header-title">
                        <span class="site-name">ElimuTaifa</span>
                        <p>#position for success</p>
                    </div>
                </div>
            </div>
            <div class="datetime-display">
                <span>Advanced Certificate of Secondary Education Examination (ACSEE)</span>
            </div>
        </header>

        <!-- Dynamic Content Body -->
        <main class="content-container">
            <div class="grid-layout">
                
                <!-- Secondary Info Section -->
                <section class="intro-card">
                    <div class="badge-fast">
                        <i class="fa-solid fa-bolt"></i> 
                        <span>ACSEE Examination Results</span>
                    </div>
                    <h1 class="intro-title">Matokeo ya Form Six 2026 (ACSEE)</h1>
                    <section class="seo-content">
                        <p>Angalia matokeo ya mtihani wa kidato cha pili
                            (ACSEE) mwaka 2026 kwa
                            kuandika index  namba ya mtihani na mwaka.
                        </p>
                    </section>
                </section>

                <!-- Actionable Search Forms Column -->
                <section class="right-column">
                    
                    <!-- Search by Index Card -->
                    <div class="card">
                        <!-- Direct Action to Form Six (ACSEE) endpoint -->
                          <form id="index1" method="post" action="results/">
    <!-- Hidden inputs submitted to PHP -->

    <div class="card-header">
        <div class="card-title-group">
            <i class="fa-solid fa-list-check card-title-icon"></i>
            <span class="card-title">ANGALIA KWA INDEX NUMBER</span>
        </div>
    </div>

    <div class="form-group">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
            <span class="form-label" style="margin-bottom:0;">
            <i class="fa-regular fa-pen-to-square" style="color:var(--primary-green);padding-right: 2px;"></i>Andika INDEX Namba ya Mtihani hapa...</span>
        </div>
        
        <div class="dividexp">
            <div class="example-hint">Mfano: <br> <span>P3743/0037</span> <br> <span>P2173/0002 </span></div>
            <div class="input-with-icon">
                <i class="fa-regular fa-user"></i>
                <input type="text" class="form-input" id="candidate" name="candidate"  maxlength="14" >
            </div>
        </div>
    </div>

    <!-- Year Selector Chips --> 
    <label class="form-label"><i class="fa-regular fa-calendar-days" style="color:var(--primary-green); margin-right: 4px;"></i>Chagua mwaka</label>
    <div class="options" id="year-options" >
        <label class="year-label" for="year" >Mwaka :</label>
        <select id="year" name="examYear">
            <option value="none" disabled>Chagua Mwaka</option>
            <option value="2026" selected>2026</option>
            <option value="2025">2025</option>
            <option value="2024">2024</option>
            <option value="2023">2023</option>
            <option value="2022">2022</option>
            <option value="2021">2021</option>
                <option value="2020">2020</option>
                <option value="2019">2019</option>
                <option value="2018">2018</option>
                <option value="2017">2017</option>
                <option value="2016">2016</option>
                <option value="2015">2015</option>
                <option value="2014">2014</option>
        </select>
    </div>

    <button type="submit" class="btn btn-green">
        <i class="fa-solid fa-magnifying-glass"></i>
        ANGALIA
        <i class="fa-solid fa-arrow-right" style="margin-left: auto;"></i>
    </button>
</form>
                    </div>

                </section>
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
</html>
<?php session_destroy()?>