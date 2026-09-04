<?php
include 'header.php'; // Include the header file
?>

<!DOCTYPE html>
<html lang="sw">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Matokeo ya Form Two (FTNA) - NECTA | G.R.F</title>
    <!-- FontAwesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="../../assets/css/consent.css">
    <script src="../../assets/js/consent.js"></script>
    <link rel="stylesheet" href="../assets/css/style.css">
    <script src="../assets/js/script.js"></script>

    <style></style>

</head>
<body>
    <section class="right-columnb">
        <section class="arlet-box <?php echo $style; ?>">
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
    <aside class="sidebar">
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
            <p>“Elimu ni msingi<br>wa maendeleo”</p>
        </div>
    </aside>

    

    <!-- Right Main Wrapper -->
    <div class="main-wrapper">
        
        <!-- Header Banner -->
        <header class="header-banner">
            <div class="logo-section">
                <i class="fa-solid fa-graduation-cap logo-icon"></i>
                <div class="header-title">
                    <h1>G.R.F </h1>
                    <p>Get Results Faster</p>
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
                    <h1 class="intro-title">MATOKEO YA NECTA <br> Form Two (FTNA)</h1>
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
    <form id="index1" method="post" action="results/index.php">

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
                <input type="text" class="form-input" id="candidate" name="candidate" placeholder="Andika index number ya mtihani hapa..." maxlength="14" required>
            </div>
            <div class="example-hint">Mfano: <span>PS170604-0001</span> Au <span>S3743/0037</span> Au <span>P3743/0037</span></div>
        </div>
    </div>

    <!-- Exam Selector Chips with data-value -->
    <label class="form-label"><i class="fa-solid fa-graduation-cap" style="color:var(--primary-green); margin-right: 4px;"></i> Chagua mtihani</label>
    <div class="options" id="exam-options">
        <div class="option active" data-value="acsee">FORM 6<span class="check">✓</span></div>
        <div class="option" data-value="csee">FORM 4</div>
        <div class="option" data-value="ftna">FORM 2</div>
        <div class="option" data-value="plse">STANDARD 7</div>
    </div>

    <!-- Year Selector Chips -->
    <label class="form-label"><i class="fa-regular fa-calendar-days" style="color:var(--primary-green); margin-right: 4px;"></i> Chagua mwaka</label>
    <div class="options" id="year-options">
        <div class="option" data-value="2023">2023</div>
        <div class="option" data-value="2024">2024</div>
        <div class="option active" data-value="2025">2025<span class="check">✓</span></div>
        <div class="option" data-value="2026">2026</div>
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
            <span>&copy; 2026 G.R.F <b>·</b> Techware47</span>
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