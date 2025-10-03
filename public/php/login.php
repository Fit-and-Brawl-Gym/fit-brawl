<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Fit and Brawl</title>
    <link rel="stylesheet" href="../css/global.css">
    <link rel="stylesheet" href="../css/pages/login.css">
    <link rel="stylesheet" href="../css/components/footer.css"> 
    <link rel="stylesheet" href="../css/components/header.css">
    <link rel="shortcut icon" href="../../logo/plm-logo.png" type="image/x-icon">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">
    <script src="https://kit.fontawesome.com/7d9cda96f6.js" crossorigin="anonymous"></script>
</head>
<body>
    <!--Header-->
    <header>
        <div class="wrapper">
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
                    <li><a href="index.php">Home</a></li>
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
                    <img src="../../images/account-icon.svg" alt="Account" class="account-icon">
                    <div class="dropdown-menu">
                        <p>Hello, <?= htmlspecialchars($_SESSION['name']) ?></p>
                        <a href="profile.php">Profile</a>
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
    <main class="login-main">
        <section class="login-hero">
            <div class="hero-content">
                <div class="hero-line"></div>
                <h1 class="hero-title">
                    STRONG TODAY <span class="yellow">  STRONGER </span> TOMORROW
                </h1>
                <div class="hero-underline"></div>
            </div>

            <div class="login-modal">
                <div class="modal-header">
                    <h2>Sign in to access your account</h2>
                </div>

                <form class="login-form">
                    <h3>ARE YOU READY TO FOR THE NEXT CHALLENGE?</h3>

                    <div class="input-group">
                        <i class="fas fa-envelope"></i>
                        <input type="email" placeholder="Email" required>
                    </div>

                    <div class="input-group password-group">
                        <div class="icon-left">
                            <i class="fas fa-key"></i>
                        </div>
                        <input type="password" id="password" placeholder="Password" required>
                    </div>

                    <div class="form-options">
                        <label class="checkbox-container">
                            <input type="checkbox" id="remember">
                            <span class="checkmark"></span>
                            Remember me
                        </label>
                        <a href="forgot-password.php" class="forgot-password">Forgot Password?</a>
                    </div>

                    <button type="submit" class="login-btn">Log-in</button>

                    <p class="signup-link">
                        Don't have an account yet? <a href="sign-up.php">Create an account.</a>
                    </p>
                </form>
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
