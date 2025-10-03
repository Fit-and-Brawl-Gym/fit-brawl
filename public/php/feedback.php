<?php
// Check if this is an API request
if ($_SERVER['REQUEST_METHOD'] === 'POST' || (isset($_GET['api']) && $_GET['api'] === 'true')) {
    header('Content-Type: application/json');
    include '../../includes/db_connect.php';

    $method = $_SERVER['REQUEST_METHOD'];

    if ($method === 'POST') {
        $data = json_decode(file_get_contents("php://input"), true);
        $user_id = $data['user_id'];
        $message = $data['message'];

        $sql = "INSERT INTO feedback (user_id, message) VALUES (?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("is", $user_id, $message);

        if ($stmt->execute()) {
            echo json_encode(["status" => "success", "message" => "Feedback submitted"]);
        } else {
            echo json_encode(["status" => "error", "message" => $conn->error]);
        }
    }

    elseif ($method === 'GET') {
        $sql = "SELECT f.id, u.username, f.message, f.date
                FROM feedback f
                JOIN users u ON f.user_id = u.id
                ORDER BY f.date DESC";
        $result = $conn->query($sql);

        $feedbacks = [];
        while ($row = $result->fetch_assoc()) {
            $feedbacks[] = $row;
        }

        echo json_encode($feedbacks);
    }
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
    <link rel="stylesheet" href="../css/pages/feedback.css">
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
                    <li><a href="feedback.php" class="active">Feedback</a></li>
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
    <main>
        <div class="bg"></div>
        <div class="feedback-container">
            <div class="feedback-section">
                <div class="feedback-card left">
                    <img src="../../images/review1-pfp.png" alt="Reynaldo Chee">
                    <div class="bubble">
                      <h3>Reynaldo Chee – Body Builder</h3>
                      <p>The equipment are clean, very accommodating staffs, and the prices is not that bad</p>
                    </div>
                  </div>

                  <div class="feedback-card right">
                    <div class="bubble">
                      <h3>Rieze Venzon – Gym Rat</h3>
                      <p>Very cool ng ambiance, very presko, at magaganda tugtugan na pang motivation talaga!</p>
                    </div>
                    <img src="../../images/review2-pfp.png" alt="Rieze Venzon">
                  </div>
            </div>
        </div>
        <div class="feedback-button">
            <a href="feedback-form.php" class="floating-btn">
                Share your feedback!
            </a>

        </div>
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
