<?php
// Set anti-cache headers to prevent Firefox from caching session state
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');
header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');

require_once '../../includes/session_manager.php';

// Initialize session manager (handles session_start internally)
SessionManager::initialize();

// Redirect logged-in users to logged-in homepage
if (SessionManager::isLoggedIn()) {
    header("Location: loggedin-index.php");
    exit;
}

// Set variables for header
$pageTitle = "Homepage - Fit and Brawl";
$currentPage = "home";
$additionalCSS = ["../css/pages/homepage.css"];

// Include header
require_once '../../includes/header.php';
?>

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

            <!-- Non-Member Pricing Table -->
            <div class="non-member-pricing">
                <h2 class="pricing-title">NON MEMBER SERVICES</h2>
                <div class="pricing-table-mini">
                    <a href="transaction_nonmember.php?service=daypass-gym" class="pricing-row">
                        <span class="price">150 PHP</span>
                        <span class="service">Day Pass: Gym Access</span>
                    </a>
                    <a href="transaction_nonmember.php?service=daypass-gym-student" class="pricing-row">
                        <span class="price">120 PHP</span>
                        <span class="service">Day Pass: Student Access</span>
                    </a>
                    <a href="transaction_nonmember.php?service=training-boxing" class="pricing-row">
                        <span class="price">380 PHP</span>
                        <span class="service">Training: Boxing</span>
                    </a>
                    <a href="transaction_nonmember.php?service=training-muaythai" class="pricing-row">
                        <span class="price">530 PHP</span>
                        <span class="service">Training: Muay Thai</span>
                    </a>
                    <a href="transaction_nonmember.php?service=training-mma" class="pricing-row">
                        <span class="price">630 PHP</span>
                        <span class="service">Training: MMA</span>
                    </a>
                </div>
                <a href="membership.php" class="view-all-btn">View All Plans</a>
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

<?php require_once '../../includes/footer.php'; ?>
