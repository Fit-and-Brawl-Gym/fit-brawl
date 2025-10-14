<?php
session_start();
if (isset($_SESSION['admin_logged_in'])) {
    header('Location: dashboard.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    include '../../../includes/db_connect.php';
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    // Replace with your actual admin table/logic
    $sql = "SELECT * FROM users WHERE username = ? AND role = 'admin' LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('s', $username);
    $stmt->execute();
    $result = $stmt->get_result();
    $admin = $result->fetch_assoc();

    if ($admin && password_verify($password, $admin['password'])) {
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_username'] = $admin['username'];
        header('Location: dashboard.php');
        exit;
    } else {
        $error = 'Invalid username or password.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Login - Fit and Brawl</title>
    <link rel="stylesheet" href="../../css/global.css">
    <link rel="stylesheet" href="../../css/pages/homepage.css">
    <link rel="stylesheet" href="../../css/components/footer.css">
    <link rel="stylesheet" href="../../css/components/header.css">
    <link rel="shortcut icon" href="../../../images/fnb-icon.png" type="image/x-icon">
</head>
<body>
    <main>
        <section style="max-width:400px;margin:60px auto;padding:32px;background:#fff;border-radius:8px;box-shadow:0 2px 8px rgba(0,0,0,0.1);">
            <h2>Admin Login</h2>
            <?php if ($error): ?>
                <div style="color:red;margin-bottom:16px;"><?php echo $error; ?></div>
            <?php endif; ?>
            <form method="POST" autocomplete="off">
                <label for="username">Username</label><br>
                <input type="text" name="username" id="username" required><br><br>
                <label for="password">Password</label><br>
                <input type="password" name="password" id="password" required><br><br>
                <button type="submit">Login</button>
            </form>
        </section>
    </main>
</body>
</html>
