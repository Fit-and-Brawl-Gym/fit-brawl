<?php
include_once('../../../includes/init.php');

// Only admins can access
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
  header("Location: ../login.php");
  exit();
}

// Fetch all equipment (order by columns that exist)
$orderCols = [];
$hasCategory = ($conn->query("SHOW COLUMNS FROM equipment LIKE 'category'")->num_rows > 0);
$hasName = ($conn->query("SHOW COLUMNS FROM equipment LIKE 'name'")->num_rows > 0);
if ($hasCategory)
  $orderCols[] = 'category';
if ($hasName)
  $orderCols[] = 'name';
if (empty($orderCols))
  $orderCols[] = 'id';

$sql = "SELECT * FROM equipment" . (count($orderCols) ? " ORDER BY " . implode(', ', $orderCols) : '');
$result = $conn->query($sql);
$equipment = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];

// Normalize rows so template keys exist
$expectedKeys = ['id', 'name', 'category', 'status', 'description'];
foreach ($equipment as &$it) {
  foreach ($expectedKeys as $k) {
    if (!array_key_exists($k, $it)) {
      $it[$k] = ($k === 'id') ? 0 : '';
    }
  }
}
unset($it);
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Equipment Management - Fit & Brawl Gym</title>
  <link rel="icon" type="image/png" href="../../../images/favicon-admin.png">
  <link rel="stylesheet" href="css/admin.css">
  <link rel="stylesheet" href="css/equipment.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>

<body>
  <?php include 'admin_sidebar.php'; ?>

  <main class="admin-main">
    <!-- Header Section -->
    <header class="page-header">
      <div>
        <h1>Equipment Management</h1>
        <p class="subtitle">Manage gym equipment inventory and status</p>
      </div>
    </header>

    <!-- Search, Filter & Add Button -->
    <div class="toolbar">
      <div class="search-box">
        <i class="fa-solid fa-magnifying-glass"></i>
        <input type="text" id="searchInput" placeholder="Search equipment...">
      </div>
      <select id="categoryFilter" class="filter-dropdown">
        <option value="all">All Categories</option>
        <option value="Cardio">Cardio</option>
        <option value="Flexibility">Flexibility</option>
        <option value="Core">Core</option>
        <option value="Strength Training">Strength Training</option>
        <option value="Functional Training">Functional Training</option>
      </select>
      <button class="btn-primary" onclick="openSidePanel()">
        <i class="fa-solid fa-plus"></i> Add New Equipment
      </button>
    </div>

    <!-- Tabs -->
    <div class="tabs">
      <button class="tab active" data-category="all">All</button>
      <button class="tab" data-category="Cardio">🏃 Cardio</button>
      <button class="tab" data-category="Flexibility">🧘 Flexibility</button>
      <button class="tab" data-category="Core">💪 Core</button>
      <button class="tab" data-category="Strength Training">🏋️ Strength Training</button>
      <button class="tab" data-category="Functional Training">🦾 Functional Training</button>
    </div>

    <!-- Equipment Grid -->
    <div id="equipmentGrid" class="equipment-grid">
      <?php if (empty($equipment)): ?>
        <div class="empty-state">
          <i class="fa-solid fa-dumbbell"></i>
          <h3>No Equipment Found</h3>
          <p>Start by adding your first equipment item</p>
          <button class="btn-primary" onclick="openSidePanel()">Add Equipment</button>
        </div>
      <?php else: ?>
        <?php foreach ($equipment as $item): ?>
          <div class="equipment-card" data-category="<?= htmlspecialchars($item['category']) ?>"
            data-id="<?= $item['id'] ?>">
            <div class="card-header">
              <div class="equipment-icon">
                <?php
                $icons = [
                  'Cardio' => '🏃',
                  'Flexibility' => '🧘',
                  'Core' => '💪',
                  'Strength Training' => '🏋️',
                  'Functional Training' => '🦾'
                ];
                echo $icons[$item['category']] ?? '🔧';
                ?>
              </div>
              <span class="status-badge status-<?= strtolower(str_replace(' ', '-', $item['status'])) ?>">
                <?= htmlspecialchars($item['status']) ?>
              </span>
            </div>
            <h3 class="equipment-name"><?= htmlspecialchars($item['name']) ?></h3>
            <p class="equipment-category"><?= htmlspecialchars($item['category']) ?></p>
            <?php if (!empty($item['description'])): ?>
              <p class="equipment-desc"><?= htmlspecialchars($item['description']) ?></p>
            <?php endif; ?>
            <div class="card-actions">
              <button class="btn-edit" onclick='editEquipment(<?= json_encode($item) ?>)'>
                <i class="fa-solid fa-pen"></i> Edit
              </button>
              <button class="btn-delete" onclick="deleteEquipment(<?= $item['id'] ?>, '<?= addslashes($item['name']) ?>')">
                <i class="fa-solid fa-trash"></i> Delete
              </button>
            </div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </main>

  <!-- Side Panel -->
  <div id="sidePanel" class="side-panel">
    <div class="side-panel-overlay" onclick="closeSidePanel()"></div>
    <div class="side-panel-content">
      <div class="side-panel-header">
        <h2 id="panelTitle">Add New Equipment</h2>
        <button class="close-btn" onclick="closeSidePanel()">
          <i class="fa-solid fa-xmark"></i>
        </button>
      </div>
      <form id="equipmentForm" class="side-panel-body" method="post">
        <input type="hidden" id="equipmentId" name="id">

        <input type="hidden" id="existingImage" name="existing_image">

        <!-- Image Upload Preview -->
        <div class="form-group">
          <label>Equipment Image</label>
          <div class="image-upload-container">
            <div id="imagePreview" class="image-preview">
              <i class="fa-solid fa-image"></i>
              <p>Click to upload image</p>
            </div>
            <input type="file" id="equipmentImage" name="image" accept="image/*" style="display:none;"
              onchange="previewImage(event)">
            <button type="button" class="btn-secondary btn-small"
              onclick="document.getElementById('equipmentImage').click()">
              <i class="fa-solid fa-upload"></i> Choose Image
            </button>
          </div>
        </div>
        <div class="form-group">
          <label for="equipmentName">Equipment Name *</label>
          <input type="text" id="equipmentName" name="name" required placeholder="e.g., Treadmill Pro X500">
        </div>

        <div class="form-group">
          <label for="equipmentCategory">Category *</label>
          <select id="equipmentCategory" name="category" required>
            <option value="">Select category</option>
            <option value="Cardio">Cardio</option>
            <option value="Flexibility">Flexibility</option>
            <option value="Core">Core</option>
            <option value="Strength Training">Strength Training</option>
            <option value="Functional Training">Functional Training</option>
          </select>
        </div>

        <div class="form-group">
          <label for="equipmentStatus">Status *</label>
          <select id="equipmentStatus" name="status" required>
            <option value="Available">Available</option>
            <option value="Maintenance">Maintenance</option>
            <option value="Out of Order">Out of Order</option>
          </select>
        </div>

        <div class="form-group">
          <label for="equipmentDescription">Description</label>
          <textarea id="equipmentDescription" name="description" rows="4"
            placeholder="Optional description..."></textarea>
        </div>

        <div class="side-panel-footer">
          <button type="button" class="btn-secondary" onclick="closeSidePanel()">Cancel</button>
          <button type="submit" class="btn-primary">
            <i class="fa-solid fa-floppy-disk"></i> Save Equipment
          </button>
        </div>
      </form>
    </div>
  </div>

  <!-- Delete Confirmation Modal -->
  <div id="deleteModal" class="modal">
    <div class="modal-content">
      <h3>Delete Equipment</h3>
      <p id="deleteMessage">Are you sure you want to delete this equipment?</p>
      <div class="modal-actions">
        <button class="btn-secondary" onclick="closeDeleteModal()">Cancel</button>
        <button class="btn-danger" onclick="confirmDelete()">Delete</button>
      </div>
    </div>
  </div>

  <script src="js/equipment.js?=v1"></script>
</body>

</html>
