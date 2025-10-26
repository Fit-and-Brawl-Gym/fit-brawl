<?php
// ===========================================
// Admin Header
// ===========================================
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}
?>

<link rel="stylesheet" href="css/admin.css">

<header class="admin-header">
  <div class="header-left">
    <h2>Fit & Brawl Admin</h2>
  </div>

  <div class="header-right">
    <span class="welcome">
      Welcome, <?= htmlspecialchars($_SESSION['username'] ?? 'Admin'); ?>
    </span>
    <a href="../logout.php" class="logout-btn">Logout</a>
  </div>
</header>