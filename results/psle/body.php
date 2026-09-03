<!DOCTYPE html>
<html lang="sw">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Angalia Matokeo ya Primary and Secondary</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <script src="assets/js/script.js"></script>
</head>
<body>

    <div class="alert-box">
        <section class="in-alert success">
            <p>
                <?php echo htmlspecialchars($successMessage); ?>
                <span class="close-btn" onclick="this.parentElement.parentElement.style.display='none';"> &times;</span>
            </p>
        </section>
    </div>

    <!-- Left Sidebar -->
    <aside class="sidebar">
        <ul class="sidebar-menu">
            <a href="../" class="sidebar-item active">
                <i class="fa-solid fa-house"></i>
                <span>Mwanzo</span>
            </a>
            <a href="#" class="sidebar-item">
                <i class="fa-solid fa-magnifying-glass"></i>
                <span>Angalia Matokeo</span>
            </a>
            <a href="#" class="sidebar-item">
                <i class="fa-solid fa-circle-info"></i>
                <span>Maelekezo</span>
            </a>
            <a href="#" class="sidebar-item">
                <i class="fa-solid fa-circle-question"></i>
                <span>Maswali Yanayoulizwa</span>
            </a>
            <a href="#" class="sidebar-item">
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

        <main class="content-container">
            <div class="grid-layout">
                <div class="intro-card">
                    <h1 class="intro-title">MATOKEO YA NECTA <br> Form Six, Form Four, Form Two na Darasa La Saba</h1>
                    <p class="intro-desc">Mfumo huu unakusaidia kupata matokeo ya mtihani wa Kitaifa kwa haraka, kwa usalama na kwa urahisi.</p>
                </div>

                <div class="right-column">
                    <div class="card">
                        <h2 id="rh2">EXAMINATION RESULTS <?php echo htmlspecialchars(strtoupper($examLevel));?> <?php echo htmlspecialchars($examYear);?></h2>
                        <h3 class="rh3"><?php echo htmlspecialchars($text);?></h3>
                        <h3 class="rh3">CANDIDATE INDEX: <span style="text-decoration: underline;"><?php echo htmlspecialchars($candidate);?>/<?php echo htmlspecialchars($examYear); ?></span></h3>
                        
                        <div class="card-one">
                            <table class="center" >
                                <tr style="background-color: transparent;text-align:left;"> 
                                    <th>CNO</th>
                                    <th>SEX</th>
                                </tr>
                                <tr style="background-color: transparent;text-align:left;">
                                    <td><?php echo htmlspecialchars($candidate); ?></td>
                                    <td><?php echo htmlspecialchars($sex); ?></td>
                                </tr>
                            </table>
                        </div>

                        <div class="card-one">
                            <table class="left">
                                <tr style="background-color: transparent;">
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
                                        $comments = [
                                            "A" => "Vyema Sana (Excellent)",
                                            "B" => "Vizuri (Very Good)",
                                            "C" => "Wastani (Good)",
                                            "D" => "Dhaifu (Satisfactory)",
                                            "F" => "Faili (Fail)",
                                            "X" => "Absent"
                                        ];
                                        echo htmlspecialchars($comments[$subject["grade"]] ?? "");
                                        ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </table>
                        </div>

                        <a href="<?php echo htmlspecialchars($url); ?>" target="_blank" rel="noopener noreferrer">Please Visit NECTA pages for confirmation ▶▷</a>
                    </div>
                </div>
            </div>
        </main>

        <footer class="footer">
            <div class="footer-text">
                &copy; 2026 G.R.F. All rights reserved. | Techware47
            </div>
            <div class="footer-links">
                <a href="#">Sera za Matumizi</a> |
                <a href="#">Faragha</a>
            </div>
        </footer>
    </div>
</body>
</html>