<?php
include 'header.php'; // Include the header file
?>

<!DOCTYPE html>
<html lang="sw">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Matokeo ya Standard Two (PSLE) - NECTA | ElimuTaifa</title>
    <!-- FontAwesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="../../assets/css/consent.css">
    <script src="../../assets/js/consent.js"></script>
    <link rel="stylesheet" href="../assets/css/style.css">
    <script src="../assets/js/script.js"></script>


</head>
<body>
    <section class="right-columnb">
        <section class="alert-panel <?php echo htmlspecialchars($style, ENT_QUOTES, 'UTF-8'); ?>">
            <p><?php echo htmlspecialchars($error); ?><br><a href="<?php echo htmlspecialchars($necta); ?>" target="_blank" rel="noopener noreferrer"><?php echo htmlspecialchars($nectaStatement); ?></a></p>
            <button class="commit-button" onclick="window.location.href='../'">OK ▶</button>
        </section>
    </section>
    <main class="blur-background">
    <!--Background blur effect in the main content area -->
    </main> 

    <!-- Custom Dynamic Alert Container -->
     <div class="alert-box">
        <div id="alert-message" class="in-alert"></div>
    </div>

    <!-- Left Sidebar -->
    <aside class="sidebar" style="z-index:10;">
        <ul class="sidebar-menu">
            <a href="../../" class="sidebar-item">
                <i class="fa-solid fa-house"></i>
                <span>Nyumbani</span>
            </a>
            <a href="../" class="sidebar-item active">
                <i class="fa-solid fa-magnifying-glass"></i>
                <span>Angalia Matokeo</span>
            </a>
            <a href="../../contribution/" class="sidebar-item">
                <i class="fa-solid fa-circle-info"></i>
                <span>Maelekezo</span>
            </a>
            <a href="../../contribution/" class="sidebar-item">
                <i class="fa-solid fa-circle-question"></i>
                <span>Maswali Yanayoulizwa</span>
            </a>
            <a href="../../contribution/" class="sidebar-item">
                <i class="fa-solid fa-envelope"></i>
                <span>Wasiliana Nasi</span>
            </a>
        </ul>
        <div class="sidebar-quote">
            <p>“#position for success”</p>
        </div>
    </aside>

    

    <!-- Right Main Wrapper -->
    <div class="main-wrapper">
        
        <!-- Header Banner -->
        <header class="header-banner" style="z-index:10;">
            <div class="logo-section">
                <i class="fa-solid fa-graduation-cap logo-icon"></i>
                <div class="header-title">
                    <h1>ElimuTaifa </h1>
                    <p>#position for success</p>
                </div>
            </div>
            <div class="datetime-display">
                <i class="fa-regular fa-clock"></i>
                <span id="current-datetime">Ijumaa, 31 Mei 2025 | Saa 20:30</span>
            </div>
        </header>

        <!-- Main Body Content -->
        <main class="content-container">
            
            <!-- Two Column Section -->
            <div class="grid-layout">
                
                <!-- Left Column Info -->
                <div class="intro-card">
                    <h1 class="intro-title">MATOKEO YA NECTA <br> Standard Two (PSLE)</h1>
                    <p class="intro-desc">Mfumo huu unakusaidia kupata matokeo ya mtihani wa Kitaifa kwa haraka, kwa usalama na kwa urahisi.</p>
                    <div class="illustration-box" style="display: none;">
                        <!-- Standard vector graphic placeholder matching the book stack motif -->
                        <svg width="300" height="220" viewBox="0 0 300 220" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <rect x="50" y="160" width="200" height="25" rx="4" fill="#0F4BB8"/>
                            <rect x="50" y="130" width="200" height="25" rx="4" fill="#EAB308"/>
                            <rect x="50" y="100" width="200" height="25" rx="4" fill="#058249"/>
                            <!-- Graduation Cap -->
                            <polygon points="150,20 230,50 150,80 70,50" fill="#1E293B"/>
                            <rect x="110" y="62" width="80" height="20" rx="3" fill="#334155"/>
                            <path d="M220 50 L220 90" stroke="#F59E0B" stroke-width="3"/>
                            <circle cx="220" cy="92" r="5" fill="#F59E0B"/>
                        </svg>
                    </div>
                </div>

                <!-- Right Column Forms -->
                <div class="right-column">
                    <!-- Form 1: Search by Index Number -->
<div class="card">
    <form id="index1" method="post" action="../results/">

    <div class="card-header">
        <div class="card-title-group">
            <i class="fa-solid fa-list-check card-title-icon"></i>
            <span class="card-title">ANGALIA KWA INDEX NUMBER</span>
        </div>
    </div>

    <div class="form-group">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
            <span class="form-label" style="margin-bottom:0;">Andika INDEX Namba ya Mtihani hapa...</span>
        </div>
        
        <div class="dividexp">
            <div class="input-with-icon">
                <i class="fa-regular fa-user"></i>
                <input type="text" class="form-input" id="candidate" name="candidate" placeholder="Andika index number ya mtihani hapa..."  maxlength="14" required>
            </div>
            <div class="example-hint">Mfano: <span>PS1704061-0001</span></div>
        </div>
    </div>

    <!-- Year Selector -->
    <label class="form-label"><i class="fa-regular fa-calendar-days" style="color:var(--primary-green); margin-right: 4px;"></i> Chagua mwaka</label>
    <div class="options" id="year-options">
        <select id="year" name="examYear" required>
            <option value="2026">2026</option>
            <option value="2025" selected>2025</option>
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
            <option value="2013">2013</option>
            <option value="2012">2012</option>
            <option value="2011">2011</option>
            <option value="2010">2010</option>
        </select>
    </div>

    <button type="submit" class="btn btn-green">
        <i class="fa-solid fa-magnifying-glass"></i>
        ANGALIA
        <i class="fa-solid fa-arrow-right" style="margin-left: auto;"></i>
    </button>
</form>
</div>
                

                    <!-- Form 2: Search School -->
                    <div class="card">
                        <div class="school-card-header">
                            <i class="fa-solid fa-building-columns"></i>
                            <span>ANGALIA KWA KUTAFUTA SHULE</span>
                        </div>

                        <div class="select-grid">
                            <div class="form-group">
                                <label class="form-label">Chagua mkoa</label>
                                <div class="custom-select-wrapper">
                                    <i class="fa-solid fa-location-dot select-icon"></i>
                                    <select class="custom-select">
                                        <option selected>Dar es Salaam</option>
                                        <option>Arusha</option>
                                        <option>Dodoma</option>
                                        <option>Mwanza</option>
                                        <option>Mbeya</option>
                                    </select>
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="form-label">Chagua wilaya</label>
                                <div class="custom-select-wrapper">
                                    <i class="fa-solid fa-building-columns select-icon"></i>
                                    <select class="custom-select">
                                        <option selected>Ilala</option>
                                        <option>Kinondoni</option>
                                        <option>Temeke</option>
                                        <option>Ubungo</option>
                                        <option>Kigamboni</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <button class="btn btn-blue">
                            <i class="fa-solid fa-building-columns"></i>
                            ANGALIA SHULE
                            <i class="fa-solid fa-arrow-right" style="margin-left: auto;"></i>
                        </button>
                    </div>

                </div>
            </div>
        </main>

        <!-- Footer Banner -->
        <footer class="footer">
            <div class="footer-text">
            <span>&copy; 2026 ElimuTaifa <b>·</b> Techware47</span>
            </div>
            <div class="footer-links">
                <a href="../../privacy/">Sera za Matumizi</a> |
                <a href="../../privacy/">Faragha</a>
            </div>
        </footer>
    </div>

    <!-- Interactive Behavior JavaScript -->
</body>
</html>

<?php session_destroy()?>