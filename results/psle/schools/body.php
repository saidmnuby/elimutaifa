
<!DOCTYPE html>
<html lang="sw" class="school-results-page">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow, noarchive">
    <meta name="referrer" content="same-origin">
    <meta name="theme-color" content="#031B4E">
    <meta name="application-name" content="ElimuTaifa">
    <title>Shule za PSLE | ElimuTaifa</title>
    <link rel="icon" type="image/x-icon" sizes="32x32" href="../../../assets/img/brand/favicon32px.ico">
    <link rel="icon" type="image/x-icon" sizes="16x16" href="../../../assets/img/brand/favicon16px.ico">
    <link rel="apple-touch-icon" href="../../../assets/img/brand/circle_logo.png">
    <!-- FontAwesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../../assets/css/style.css">
    <link rel="stylesheet" href="assets/css/style.css?v=20260912.2">
    <link rel="stylesheet" href="../../../assets/css/consent.css">
    <script src="../../../assets/js/consent.js"></script>
    <script src="../../../assets/js/monitoring.js" defer></script>
    <link rel="stylesheet" href="../../../assets/css/education.css?v=20260912.2">
    <script src="assets/js/script.js"></script>
    <script src="../../../assets/js/school-search.js" defer></script>
    
    <script src="../../../assets/js/placements.js" defer></script>
</head>
<body class="education-page level-schools" data-exam="psle">

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
                <a href="../../ftna/" class="sidebar-item">
                   <i class="fa-solid fa-list-check"></i>
                    <span>FORM TWO (FTNA)</span>
                </a>
            </li>
            <li>
                <a href="../" class="sidebar-item active">
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

    

    <!-- Right Main Wrapper -->
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
                        <h1>ElimuTaifa</h1>
                        <p>#position for success</p>
                    </div>
                </div>
            </div>
            <div class="datetime-display">
                <span>Primary School Leaving Examination (PSLE)</span>
            </div>
        </header>

        <!-- Main Body Content -->
        <div data-et-placement-slot="top" data-et-placement-page="psle-schools" hidden></div>
        <main class="content-container">
            

            <!-- Two Column Section -->
            <div class="grid-layout-results">
                
                <!-- Secondary Info Section -->
                <div class="school-context-column">
                    <section class="intro-card">
                        <div class="badge-fast">
                            <i class="fa-solid fa-bolt"></i> PSLE Examination Results
                        </div>
                        <h2>ORODHA YA SHULE · DARASA LA SABA (PSLE) <?php echo htmlspecialchars($examYear, ENT_QUOTES, 'UTF-8'); ?></h2>
                        <h3 class="intro-title">
                            <?php echo htmlspecialchars(ucwords($searchKey), ENT_QUOTES, 'UTF-8'); ?>,
                            <?php echo htmlspecialchars(ucwords($regiontz), ENT_QUOTES, 'UTF-8'); ?>
                        </h3>
                    </section>
                    <section class="school-history" aria-labelledby="recent-schools-title">
                        <h4 id="recent-schools-title"><i class="fa-solid fa-clock-rotate-left"></i> zimetefutwa hivi karibuni</h4>
                        <div class="recent-schools" id="recentSchools">
                            <p class="recent-schools-empty">Hakuna shule iliyochaguliwa bado.</p>
                        </div>
                    </section>
                </div>
                

                <div class="right-column-results">
                    <section class="school-search" role="search" aria-labelledby="school-search-label">
                        <label id="school-search-label" for="schoolSearch">Tafuta jina la shule</label>
                        <div class="school-search-control">
                            <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
                            <input id="schoolSearch" type="search" placeholder="Anza kuandika jina la shule..." autocomplete="off" spellcheck="false" aria-controls="schoolList" aria-describedby="schoolSearchSummary">
                        </div>
                        <p class="school-search-summary" id="schoolSearchSummary" aria-live="polite"></p>
                    </section>
                    <div class="card" tabindex="0" role="region" aria-label="Orodha ya shule">
                            <p class="school-search-empty" id="schoolSearchEmpty" hidden>Hakuna shule inayolingana na jina uliloandika.</p>
                            <div class="schoolCard" id="schoolList">
                                        <?php if (!empty($schools)): ?>
                                                <?php foreach ($schools as $school): ?>
                                                    
                                                    <?php
                                                    $schoolCode = $school['id'];
                                                    if ($examYear > 2023) {
                                                        $resultsurl = "https://onlinesys.necta.go.tz/results/$examYear/psle/results/shl_$schoolCode.htm";
                                                        } elseif ($examYear !== null && $examYear <= 2023) {
                                                            $resultsurl = "https://maktaba.tetea.org/exam-results/PSLE$examYear/shl_$schoolCode.htm";
                                                        }
                                                    ?>

                                                    <a href="<?php echo htmlspecialchars($resultsurl, ENT_QUOTES, 'UTF-8'); ?>" data-school-name="<?php echo htmlspecialchars($school['full'], ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener noreferrer">
                                                        <div class="oneSchool">
                                                            <?php echo htmlspecialchars($school['full'], ENT_QUOTES, 'UTF-8'); ?>
                                                    
                                                        </div>
                                                    </a>
                                                <?php endforeach; ?>
                                        <?php else: ?>
                                            <p class="no-results">Hakuna shule zilizopatikana.</p>
                                        <?php endif; ?>
                            </div>
                    </div>

                </div>
            </div>
        </main><div data-et-placement-slot="bottom" data-et-placement-page="psle-schools" hidden></div>

        <!-- Footer Banner -->
        <footer class="footer">
            <div class="footer-text">
            <span>&copy; 2026 ElimuTaifa <b>·</b> Techware47</span>
            </div>
            <div class="footer-links">
                <a href="../../../privacy/">Sera za Matumizi</a> |
                <a href="../../../privacy/">Faragha</a>
            </div>
        </footer>
    </div>

    
</body>

 <!-- Scripts -->
    <script>
        document.addEventListener("DOMContentLoaded", function () {
            const menuToggle = document.getElementById("menuToggle");
            const sidebar = document.getElementById("sidebar");
            const sidebarOverlay = document.getElementById("sidebarOverlay");
            let alertTimer = null;

            // 1. Mobile Sidebar Toggle
            function toggleMenu() {
                sidebar.classList.toggle("open");
                sidebarOverlay.classList.toggle("active");
            }

            if (menuToggle) menuToggle.addEventListener("click", toggleMenu);
            if (sidebarOverlay) sidebarOverlay.addEventListener("click", toggleMenu);
        });

    </script>
      <script>
    function updateClock() {
      const now = new Date();
      
      // 1. Inapata Timezone ya kifaa cha mtumiaji kulingana na eneo lake (mfano: Africa/Dar_es_Salaam)
      const userTimeZone = Intl.DateTimeFormat().resolvedOptions().timeZone;

      // 2. Inapanga muundo wa saa, tarehe na sekunde kulingana na Timezone hiyo
      const timeFormatter = new Intl.DateTimeFormat('sw-TZ', {
        timeZone: userTimeZone,
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit',
        hour12: false // Weka 'true' kama unataka AM/PM
      });

      const dateFormatter = new Intl.DateTimeFormat('sw-TZ', {
        timeZone: userTimeZone,
        weekday: 'short',
        year: 'numeric',
        month: 'short',
        day: 'numeric'
      });

      // 3. Inaweka matokeo kwenye HTML
      document.getElementById('live-clock').textContent = timeFormatter.format(now);
      document.getElementById('location-text').textContent = `${dateFormatter.format(now)} | ${userTimeZone}`;
    }

    // Isome mara moja na kuisasisha kila sekunde 1 (1000ms)
    updateClock();
    setInterval(updateClock, 1000);
  </script>
</html>
