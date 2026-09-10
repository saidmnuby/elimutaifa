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
<body class="education-page" data-exam="sfna">
    
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
                <a href="../../psle/" class="sidebar-item">
                   <i class="fa-solid fa-list-check"></i>
                    <span>STANDARD 7 (PSLE)</span>
                </a>
            </li>
            <li>
            <li>
                <a href="../../sfna/" class="sidebar-item active">
                   <i class="fa-solid fa-list-check"></i>
                    <span>STANDARD 4 (SFNA)</span>
                </a>
            </li><br>
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
                <span>Standard Four National Assesment (SFNA)</span>
            </div>
        </header>

        <!-- Dynamic Content Body -->
        <main class="content-container">
            <div class="grid-layout">
                <!-- Secondary Info Section -->
                <section class="intro-card">
                    <div class="badge-fast">
                        <i class="fa-solid fa-bolt"></i> 
                        <span></span>SFNA Examination Results
                    </div>
                    <h1 class="intro-title">Matokeo ya Darasa la nne 2026 (SFNA)</h1>
                    <section class="seo-content">
                        <p>Angalia matokeo ya mtihani wa Darasa la nne elimu ya msingi
                            (SFNA) kwa mwaka 2026 kwa
                            kuandika index  namba ya mtihani na mwaka  au kwa kuchagua mwaka mkoa na shule wilaya ili kuona matokeo yote ya shule.
                        </p>
                    </section>
                </section>
                <?php if (!empty($result)): ?>
                <!-- Right Column Forms -->
                <div class="right-column">
                    <div class="card">
                    <h2 id="rh2">EXAMINATION RESULTS PSLE <?php echo htmlspecialchars($examYear);?></h2>
                    <h3 class="rh3"><?php echo htmlspecialchars($text);?></h3>
                    <h3 class="rh3" >CANDIDATE INDEX:<span style="text-decoration: underline;"><?php echo htmlspecialchars($candidate); ?>-<?php echo htmlspecialchars($examYear);?></span></h3>
                    <div class="card-one">
                        <?php if ($examYear <= '2020') { ?>
                            
                        <table class="center">
                            <tr style="background-color: transparent;">
                                <th>CNO</th>
                                <th>SEX</th>
                                <th>NAME</th>
                                
                            </tr>
                                
                            <tr style="background-color: transparent;">
                                <td><?php echo htmlspecialchars($candidate); ?></td>
                                <td><?php echo htmlspecialchars($prem_no); ?></td>
                                <td><?php echo htmlspecialchars($sex); ?></td>
                               
                            </tr>
                        <?php }else{?>
                            
                        <table class="center">
                            <tr style="background-color: transparent;">
                                <th>CNO</th>
                                <th>PREM NO</th>
                                <th>SEX</th>
                                
                            </tr>
                                
                            <tr style="background-color: transparent;">
                                <td><?php echo htmlspecialchars($candidate); ?></td>
                                <td><?php echo htmlspecialchars($prem_no); ?></td>
                                <td><?php echo htmlspecialchars($sex); ?></td>
                               
                            </tr>

                        
                        <?php }?>

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
                        <?php $_SESSION['error_message'] = "namba ya mtihani au ngazi ya Elimu siyo sahihi au haipatikani. Tafadhali hakiki namba, ngazi ya Elimu na mwaka kisha ujaribu tena. au";
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
  <script src="../assets/js/region-municipalities.js"></script>
</html>
