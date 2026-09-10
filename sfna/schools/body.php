<!DOCTYPE html>
<html lang="sw">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Matokeo ya darasala pili Necta | ElimuTaifa</title>
    
    <meta
    name="description"
    content="Angalia matokeo ya Darasa la nne (SFNA) ya NECTA kwa mwaka 2026. Tafuta matokeo kwa namba ya mtihani au angalia matokeo ya shule.">

    <link
    rel="canonical"
    href="https://elimutafuta.example/sfna/">

    <meta property="og:title"
    content="Matokeo ya Darasa la nne 2026 - NECTA">

    <meta property="og:description"
    content="Angalia matokeo ya Darasa la nne mwaka 2026 na Elimutaifa.">

    <meta property="og:type" content="website">
    
    <meta property="og:url"
    content="https://elimutaifa.com/sfna/">

    <meta property="og:image"
    content="https://elimutaifa.com/assets/img/psle-results.jpg">

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
<body>

    <?php if (!empty($successMessage)): ?>
    <div class="alert-box">
        <section class="in-alert success">
            <p>
                <?php echo htmlspecialchars($successMessage, ENT_QUOTES, 'UTF-8'); ?>
                <span class="close-btn" onclick="this.closest('.alert-box').style.display='none';">&times;</span>
            </p>
        </section>
    </div>
    <?php endif; ?>

    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <aside class="sidebar" id="sidebar">
        <ul class="sidebar-menu">
            <li>
                <a href="../../" class="sidebar-item">
                    <i class="fa-solid fa-house"></i>
                    <span>Nyumbani</span>
                </a>
            </li>
            <li>
                <a href="../../acsee/" class="sidebar-item">
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
                    <span>STANDARD 7 (PSLE)</span>
                </a>
            </li>
            <li>
                <a href="../../sfna/" class="sidebar-item active">
                   <i class="fa-solid fa-list-check"></i>
                    <span>STANDARD 4 (SFNA)</span>
                </a>
            </li>
            <li class="menu-divider"></li>
            <li>
                <a href="../../contribution/" class="sidebar-item">
                   <i class="fa-solid fa-comments"></i>
                    <span>Ask, Contribute, Comment</span>
                </a>
            </li>
        </ul>
        <div class="sidebar-quote">
            <p>“#position for success”</p>
        </div>
    </aside>

    <div class="main-wrapper">
        
        <header class="header-banner">
            <div class="header-left">
                <button class="mobile-menu-btn" id="menuToggle" aria-label="Fungua Menyu">
                    <i class="fa-solid fa-bars"></i>
                </button>
                <div class="logo-section">
                    <i class="fa-solid fa-graduation-cap logo-icon"></i>
                    <div class="header-title">
                        <h1>ElimuTaifa</h1>
                        <p>#position for success</p>
                    </div>
                </div>
            </div>
            <div class="datetime-display">
                <span>Standard Four National Assesment (SFNA)</span>
            </div>
        </header>

        <main class="content-container">
            <div class="grid-layout-results">
                
                <section class="intro-card">
                    <div class="badge-fast">
                        <i class="fa-solid fa-bolt"></i> SFNA Examination Results
                    </div>
                    <h2>MATOKEO YA NECTA -Darasa la nne (SFNA) <?php echo htmlspecialchars((string)$examYear, ENT_QUOTES, 'UTF-8'); ?></h2>
                    <h3 class="intro-title">
                        <?php echo htmlspecialchars(ucwords((string)$searchKey), ENT_QUOTES, 'UTF-8'); ?>,
                        <?php echo htmlspecialchars(ucwords((string)$regiontz), ENT_QUOTES, 'UTF-8'); ?>
                    </h3>
                </section>

                <div class="right-column-results">
                    <div class="card">
                        <div class="schoolCard">
                            <?php if (!empty($schools)): ?>
                                <?php foreach ($schools as $school): ?>
                                    <?php
                                    $schoolCode = $school['id'];
                                    $schoolCode = str_ireplace("ps","",$schoolCode);

                                    if (!empty($schoolCode) && $examYear !== null) {
                                        if ((int)$examYear > 2023) {
                                            $resultsUrl = "https://onlinesys.necta.go.tz/results/$examYear/sfna/results/ps$schoolCode.htm";
                                        } else {
                                            $resultsUrl = "https://maktaba.tetea.org/exam-results/SFNA$examYear/ps$schoolCode.htm";
                                        }
                                    }
                                    ?>
                                    <a href="<?php echo htmlspecialchars($resultsUrl, ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener noreferrer">
                                        <div class="oneSchool">
                                            <?php echo htmlspecialchars($school['name'], ENT_QUOTES, 'UTF-8'); ?>
                                        </div>
                                    </a>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <p class="no-results">No schools found.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

            </div>
        </main>

        <footer class="footer">
            <div class="footer-text">
                <span>&copy; <?php echo date('Y'); ?> ElimuTaifa <b>·</b> Techware47</span>
            </div>
            <div class="footer-links">
                <a href="../../privacy/">Sera za Matumizi</a> |
                <a href="../../privacy/">Faragha</a>
            </div>
        </footer>
    </div>

    <script>
        document.addEventListener("DOMContentLoaded", function () {
            const menuToggle = document.getElementById("menuToggle");
            const sidebar = document.getElementById("sidebar");
            const sidebarOverlay = document.getElementById("sidebarOverlay");

            function toggleMenu() {
                sidebar.classList.toggle("open");
                sidebarOverlay.classList.toggle("active");
            }

            if (menuToggle) menuToggle.addEventListener("click", toggleMenu);
            if (sidebarOverlay) sidebarOverlay.addEventListener("click", toggleMenu);

            function updateClock() {
                const now = new Date();
                const userTimeZone = Intl.DateTimeFormat().resolvedOptions().timeZone || 'Africa/Dar_es_Salaam';

                const timeFormatter = new Intl.DateTimeFormat('sw-TZ', {
                    timeZone: userTimeZone,
                    hour: '2-digit',
                    minute: '2-digit',
                    second: '2-digit',
                    hour12: false
                });

                const dateFormatter = new Intl.DateTimeFormat('sw-TZ', {
                    timeZone: userTimeZone,
                    weekday: 'short',
                    year: 'numeric',
                    month: 'short',
                    day: 'numeric'
                });

                const clockEl = document.getElementById('live-clock');
                const locEl = document.getElementById('location-text');

                if (clockEl) clockEl.textContent = timeFormatter.format(now);
                if (locEl) locEl.textContent = `${dateFormatter.format(now)} | ${userTimeZone}`;
            }

            updateClock();
            setInterval(updateClock, 1000);
        });
    </script>
</body>
</html>