<?php
include_once('../../../includes/init.php');

// Only admins can access
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Manage Equipment | Fit & Brawl Admin</title>
  <link rel="stylesheet" href="/fit-brawl/public/php/admin/css/admin.css">
</head>
<body>
  <?php include_once('admin_header.php'); ?>
  <?php include_once('admin_sidebar.php'); ?>

  <main class="admin-main">
    <header>
      <h1>Equipment Management</h1>
      <p>View, update, or add new equipment.</p>
    </header>

    <!-- Add Equipment Form -->
    <section class="add-equipment">
      <h2>Add New Equipment</h2>
      <form id="addEquipmentForm">
        <input type="text" name="name" placeholder="Equipment Name" required>
        <input type="text" name="category" placeholder="Category" required>
        <textarea name="description" placeholder="Description (optional)"></textarea>
        <button type="submit">Add Equipment</button>
      </form>
    </section>

    <hr>

    <!-- Equipment Cards -->
    <section id="equipmentList" class="equipment-grid">
      <p>Loading equipment...</p>
    </section>
  </main>

  <?php include_once('admin_footer.php'); ?>
  <script src="/fit-brawl/public/php/admin/js/equipment.js"></script>
</body>
</html>
