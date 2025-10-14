<?php
session_start();

// Redirect logged-in users to logged-in homepage
if(isset($_SESSION['email'])) {
    header("Location: loggedin-index.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fit and Brawl</title>
    <link rel="stylesheet" href="../css/global.css">
    <link rel="stylesheet" href="../css/pages/homepage.css">
    <link rel="stylesheet" href="../css/components/footer.css">
    <link rel="stylesheet" href="../css/components/header.css">
    <link rel="shortcut icon" href="../../logo/plm-logo.png" type="image/x-icon">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">
    <script src="https://kit.fontawesome.com/7d9cda96f6.js" crossorigin="anonymous"></script>
    <script src="../js/header-dropdown.js"></script>
    <script src="../js/hamburger-menu.js"></script>
</head>
<body>
    <!--Header-->
    <header>
        <div class="wrapper">
            <button class="hamburger-menu" aria-label="Toggle menu">
                <span></span>
                <span></span>
                <span></span>
            </button>
            <div class="title">
                <a href="index.php">
                    <img src="../../images/fnb-logo-yellow.svg" alt="Logo" class="fnb-logo">
                </a>
                <a href="index.php">
                    <img src="../../images/header-title.svg" alt="FITXBRAWL" class="logo-title">
                </a>
            </div>
            <nav class="nav-bar">
                <ul>
                    <li><a href="index.php" class="active">Home</a></li>
                    <li><a href="membership.php">Membership</a></li>
                    <li><a href="equipment.php">Equipment</a></li>
                    <li><a href="products.php">Products</a></li>
                    <li><a href="contact.php">Contact</a></li>
                    <li><a href="feedback.php">Feedback</a></li>
                </ul>
            </nav>
            <?php if(isset($_SESSION['email'])): ?>
                <!-- Logged-in dropdown -->
                <div class="account-dropdown">
                    <img src="../../uploads/avatars/<?= htmlspecialchars($_SESSION['avatar']) ?>"
             alt="Account" class="account-icon">
                    <div class="dropdown-menu">
                        <a href="user_profile.php">Profile</a>
                        <a href="logout.php">Logout</a>
                    </div>
                </div>
            <?php else: ?>
                <!-- Not logged-in -->
                <a href="login.php" class="account-link">
                    <img src="../../images/account-icon.svg" alt="Account" class="account-icon">
                </a>
            <?php endif; ?>
        </div>
    </header>

    <!--Main-->
    <main>
        <section class="homepage-hero">
            <div class="hero-content">
                <div class="hero-underline top-line"></div>
                <h1>
                    BUILD A <span class="yellow">BODY</span> THAT<span class="apostrophe">&#39;</span>S<br>
                    BUILT FOR <span class="yellow">BATTLE</span>
                </h1>
                <p class="hero-sub"><span class="sub-underline">Ready for the battle?</span></p>
                <a href="membership.php" class="hero-btn">Be a Member</a>
            </div>
        </section>

        <!-- Gym Overview Section -->
        <section class="gym-overview">
            <div class="overview-container">
                <h2 class="overview-title">
                    <span class="title-with-line">Our Story</span>
                </h2>
                <h3 class="overview-subtitle">
                    FORGED BY A <span class="highlight">FIGHTER.</span> BUILT FOR <span class="highlight">YOU.</span>
                </h3>
                <p class="overview-description">
                    Our story begins with our CEO, an MMA player and dedicated advocate for holistic health. They founded Fit and Brawl not just as a gym, but as a commitment to a training philosophy: that the discipline and intensity of combat sports are the fastest, most effective path to a sustainably healthy body. We teach you how to fight, and in the process, transform your life.
                </p>

                <div class="features-grid">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <img src="../../images/fm-icon.svg" alt="Fighter's Mindset">
                        </div>
                        <h4 class="feature-title">THE FIGHTER'S MINDSET</h4>
                        <p class="feature-text">
                            We teach the discipline and mental toughness forged by our MMA-trained founder. Apply this winning attitude to every challenge in your life.
                        </p>
                    </div>

                    <div class="feature-card">
                        <div class="feature-icon">
                            <img src="../../images/wbw-icon.svg" alt="Whole-body Wellness">
                        </div>
                        <h4 class="feature-title">WHOLE-BODY WELLNESS</h4>
                        <p class="feature-text">
                            Our training regimen is designed by an athlete focused on sustainable health. Get strength and conditioning that lasts, not just show muscles.
                        </p>
                    </div>

                    <div class="feature-card">
                        <div class="feature-icon">
                            <img src="../../images/act-icon.svg" alt="Authentic Combat Training">
                        </div>
                        <h4 class="feature-title">AUTHENTIC COMBAT TRAINING</h4>
                        <p class="feature-text">
                            Perfect your strike, kick, and clinch. We offer high-quality, authentic Boxing, MMA, and Muay Thai instruction for all levels, from beginner to pro.
                        </p>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <!--Footer-->
    <footer>
        <div class="container footer-flex">
            <div class="footer-logo-block">
                <img src="../../images/footer-title.png" alt="FITXBRAWL" class="footer-logo-title">
            </div>
            <div class="footer-menu-block">
                <div class="footer-menu-title">MENU</div>
                <ul class="footer-menu-list">
                    <li><a href="index.php">Home</a></li>
                    <li><a href="membership.php">Membership</a></li>
                    <li><a href="equipment.php">Equipment</a></li>
                    <li><a href="products.php">Products</a></li>
                    <li><a href="contact.php">Contact</a></li>
                    <li><a href="feedback.php">Feedback</a></li>
                </ul>
            </div>
            <div class="footer-contact-block">
                <div class="footer-contact-title">CONTACT</div>
                <div class="footer-contact-details">
                    1832 Oroquieta Rd, Santa Cruz, Manila,<br>
                    1008 Metro Manila<br><br>
                    Gmail: fitxbrawl@gmail.com
                </div>
            </div>
            <div class="footer-hours-block">
                <div class="footer-hours-title">OPENING HOURS</div>
                <div class="footer-hours-details">
                    Sun–Fri: 9AM to 10PM<br>
                    Saturday: 10AM to 7PM
                </div>
            </div>
        </div>
        <div class="copyright">
            <p>&copy; 2025 Fit X Brawl, All rights reserved.</p>
        </div>
    </footer>
</body>
</html>
