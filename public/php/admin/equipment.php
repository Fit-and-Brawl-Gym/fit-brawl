<?php
include_once('../../../includes/init.php');
require_once __DIR__ . '/../../../includes/csp_nonce.php';
require_once __DIR__ . '/../../../includes/csrf_protection.php';

// Generate CSP nonces for this request
CSPNonce::generate();

// Only admins can access
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
  header("Location: ../login.php");
  exit();
}

// Generate CSRF token
$csrfToken = CSRFProtection::generateToken();

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
$expectedKeys = ['id', 'name', 'category', 'status', 'description', 'maintenance_start_date', 'maintenance_end_date', 'maintenance_reason'];
foreach ($equipment as &$it) {
  foreach ($expectedKeys as $k) {
    if (!array_key_exists($k, $it)) {
      $it[$k] = ($k === 'id') ? 0 : '';
    }
  }
}
unset($it);

// Calculate stats
$totalEquipment = count($equipment);
$availableEquipment = 0;
$maintenanceEquipment = 0;
$outOfOrderEquipment = 0;

foreach ($equipment as $item) {
  if ($item['status'] === 'Available') $availableEquipment++;
  elseif ($item['status'] === 'Maintenance') $maintenanceEquipment++;
  elseif ($item['status'] === 'Out of Order') $outOfOrderEquipment++;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf-token" content="<?= htmlspecialchars($csrfToken) ?>">
  <title>Equipment Management - Fit & Brawl Gym</title>
  <link rel="icon" type="image/png" href="<?= IMAGES_PATH ?>/favicon-admin.png">
  <link rel="stylesheet" href="<?= PUBLIC_PATH ?>/php/admin/css/admin.css">
  <link rel="stylesheet" href="<?= PUBLIC_PATH ?>/php/admin/css/dashboard.css">
  <link rel="stylesheet" href="<?= PUBLIC_PATH ?>/php/admin/css/equipment.css">
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

    <!-- Stats Cards -->
    <section class="stats-grid" style="margin-bottom: 24px; grid-template-columns: repeat(4, 1fr);">
      <div class="stat-card">
        <div class="stat-icon blue">
          <i class="fa-solid fa-dumbbell"></i>
        </div>
        <div class="stat-info">
          <h3><?= $totalEquipment ?></h3>
          <p>Total Equipment</p>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon green">
          <i class="fa-solid fa-circle-check"></i>
        </div>
        <div class="stat-info">
          <h3><?= $availableEquipment ?></h3>
          <p>Available</p>
        </div>
      </div>
      <div class="stat-card <?= $maintenanceEquipment > 0 ? 'has-alert' : '' ?>">
        <div class="stat-icon orange">
          <i class="fa-solid fa-wrench"></i>
        </div>
        <div class="stat-info">
          <h3><?= $maintenanceEquipment ?></h3>
          <p>Under Maintenance</p>
        </div>
        <?php if ($maintenanceEquipment > 0): ?>
          <a href="#" class="stat-action" onclick="document.getElementById('statusFilter').value='Maintenance'; filterEquipment(); return false;">
            <i class="fa-solid fa-arrow-right"></i>
          </a>
        <?php endif; ?>
      </div>
      <div class="stat-card <?= $outOfOrderEquipment > 0 ? 'has-alert' : '' ?>">
        <div class="stat-icon red">
          <i class="fa-solid fa-triangle-exclamation"></i>
        </div>
        <div class="stat-info">
          <h3><?= $outOfOrderEquipment ?></h3>
          <p>Out of Order</p>
        </div>
        <?php if ($outOfOrderEquipment > 0): ?>
          <a href="#" class="stat-action" onclick="document.getElementById('statusFilter').value='Out of Order'; filterEquipment(); return false;">
            <i class="fa-solid fa-arrow-right"></i>
          </a>
        <?php endif; ?>
      </div>
    </section>

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
      <select id="statusFilter" class="filter-dropdown">
        <option value="all">All Status</option>
        <option value="Available">Available</option>
        <option value="Maintenance">Maintenance</option>
        <option value="Out of Order">Out of Order</option>
      </select>
      <div class="view-toggle">
        <button class="view-btn active" data-view="table" title="Table View">
          <i class="fa-solid fa-table"></i>
        </button>
        <button class="view-btn" data-view="card" title="Card View">
          <i class="fa-solid fa-grip"></i>
        </button>
      </div>
      <button class="btn-primary" onclick="openSidePanel()">
        <i class="fa-solid fa-plus"></i> Add New Equipment
      </button>
    </div>

    <!-- Table View -->
    <div class="equipment-table-view active" id="tableView">
      <div class="table-container">
        <table class="data-table">
          <thead>
            <tr>
              <th width="80">Image</th>
              <th>Name</th>
              <th>Category</th>
              <th>Status</th>
              <th>Maintenance Schedule</th>
              <th>Description</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($equipment)): ?>
              <tr>
                <td colspan="7" style="text-align: center; padding: 40px; color: #999;">
                  <i class="fa-solid fa-dumbbell" style="font-size: 48px; margin-bottom: 12px; display: block;"></i>
                  No Equipment Found
                </td>
              </tr>
            <?php else: ?>
              <?php foreach ($equipment as $item): ?>
                <tr data-category="<?= htmlspecialchars($item['category']) ?>" data-status="<?= htmlspecialchars($item['status']) ?>" data-id="<?= $item['id'] ?>">
                  <td>
                    <?php
                    $rawImage = isset($item['image_path']) ? trim($item['image_path']) : '';
                    $imgSrc = '';
                    if ($rawImage !== '') {
                      $isExternal = preg_match('#^https?://#i', $rawImage) || strpos($rawImage, 'data:') === 0;
                      $startsWithDotDot = substr($rawImage, 0, 3) === '../';
                      $containsUploads = strpos($rawImage, 'uploads/') !== false;
                      $isBareFile = (strpos($rawImage, '/') === false && !$isExternal);

                      if ($isExternal) {
                        $imgSrc = $rawImage;
                      } elseif ($startsWithDotDot) {
                        $imgSrc = $rawImage;
                      } elseif ($containsUploads) {
                        $inner = preg_replace('#^uploads/#','',$rawImage);
                        $imgSrc = rtrim(UPLOADS_PATH,'/').'/'.ltrim($inner,'/');
                      } elseif ($isBareFile) {
                        $imgSrc = rtrim(UPLOADS_PATH,'/').'/equipment/'.$rawImage;
                      } else {
                        $imgSrc = $rawImage;
                      }

                      // Fallback slug
                      $fs = $_SERVER['DOCUMENT_ROOT'].$imgSrc;
                      if (!file_exists($fs)) {
                        $slug = strtolower(preg_replace('#[^a-z0-9]+#i','-',$item['name']));
                        foreach (['jpg','jpeg','png','webp'] as $ext) {
                          $candidate = rtrim(UPLOADS_PATH,'/').'/equipment/'.$slug.'.'.$ext;
                          if (file_exists($_SERVER['DOCUMENT_ROOT'].$candidate)) { $imgSrc = $candidate; break; }
                        }
                      }
                    }
                    ?>
                    <?php if ($imgSrc): ?>
                      <img src="<?= htmlspecialchars($imgSrc) ?>" alt="<?= htmlspecialchars($item['name']) ?>" class="equipment-thumb"
                           onerror="this.outerHTML='\x3cdiv class=\'equipment-thumb no-image\'\x3e\x3ci class=\'fa-solid fa-image\'\x3e\x3c/i\x3e\x3c/div\x3e'">
                    <?php else: ?>
                      <div class="equipment-thumb no-image"><i class="fa-solid fa-image"></i></div>
                    <?php endif; ?>
                  </td>
                  <td><strong><?= htmlspecialchars($item['name']) ?></strong></td>
                  <td><?= htmlspecialchars($item['category']) ?></td>
                  <td>
                    <span class="status-badge status-<?= strtolower(str_replace(' ', '-', $item['status'])) ?>">
                      <?= htmlspecialchars($item['status']) ?>
                    </span>
                  </td>
                  <td>
                    <?php if ($item['status'] === 'Maintenance' && !empty($item['maintenance_start_date'])): ?>
                      <div class="maintenance-info">
                        <div class="maintenance-dates">
                          <i class="fa-solid fa-calendar"></i>
                          <?= date('M d', strtotime($item['maintenance_start_date'])) ?> -
                          <?= date('M d, Y', strtotime($item['maintenance_end_date'])) ?>
                        </div>
                        <?php if (!empty($item['maintenance_reason'])): ?>
                          <div class="maintenance-reason"><?= htmlspecialchars($item['maintenance_reason']) ?></div>
                        <?php endif; ?>
                      </div>
                    <?php else: ?>
                      <span style="color: #999;">-</span>
                    <?php endif; ?>
                  </td>
                  <td><?= htmlspecialchars($item['description'] ?: 'N/A') ?></td>
                  <td>
                    <div class="action-buttons">
                      <button class="btn-secondary btn-small" onclick='editEquipment(<?= json_encode($item) ?>)' title="Edit">
                        <i class="fa-solid fa-pen"></i> Edit
                      </button>
                      <button class="btn-danger btn-small" onclick="deleteEquipment(<?= $item['id'] ?>, '<?= addslashes($item['name']) ?>')" title="Delete">
                        <i class="fa-solid fa-trash"></i> Delete
                      </button>
                    </div>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Cards View -->
    <div class="equipment-cards-view" id="cardsView">
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
          <div class="equipment-card" data-category="<?= htmlspecialchars($item['category']) ?>" data-status="<?= htmlspecialchars($item['status']) ?>"
            data-id="<?= $item['id'] ?>">
            <div class="card-header">
              <div class="equipment-image-wrapper">
                <?php
                $rawImage = isset($item['image_path']) ? trim($item['image_path']) : '';
                $resolved = '';
                if ($rawImage !== '') {
                  $isExternal = preg_match('#^https?://#i', $rawImage) || strpos($rawImage, 'data:') === 0;
                  $startsWithDotDot = substr($rawImage, 0, 3) === '../';
                  $containsUploads = strpos($rawImage, 'uploads/') !== false;
                  $isBareFile = (strpos($rawImage, '/') === false && !$isExternal);

                  if ($isExternal) {
                    $resolved = $rawImage;
                  } elseif ($startsWithDotDot) {
                    $resolved = $rawImage;
                  } elseif ($containsUploads) {
                    $inner = preg_replace('#^uploads/#','',$rawImage);
                    $resolved = rtrim(UPLOADS_PATH,'/').'/'.ltrim($inner,'/');
                  } elseif ($isBareFile) {
                    $resolved = rtrim(UPLOADS_PATH,'/').'/equipment/'.$rawImage;
                  } else {
                    $resolved = $rawImage;
                  }

                  // Fallback by slug + common extensions if file missing
                  $fs = $_SERVER['DOCUMENT_ROOT'].$resolved;
                  if (!file_exists($fs)) {
                    $slug = strtolower(preg_replace('#[^a-z0-9]+#i','-',$item['name']));
                    foreach (['jpg','jpeg','png','webp'] as $ext) {
                      $candidate = rtrim(UPLOADS_PATH,'/').'/equipment/'.$slug.'.'.$ext;
                      if (file_exists($_SERVER['DOCUMENT_ROOT'].$candidate)) { $resolved = $candidate; break; }
                    }
                  }
                }
                ?>
                <?php if ($resolved): ?>
                  <img src="<?= htmlspecialchars($resolved) ?>" alt="<?= htmlspecialchars($item['name']) ?>" class="equipment-thumb"
                       onerror="this.outerHTML='\x3cdiv class=\'equipment-thumb no-image\'\x3e\x3ci class=\'fa-solid fa-image\'\x3e\x3c/i\x3e\x3c/div\x3e'">
                <?php else: ?>
                  <div class="equipment-thumb no-image"><i class="fa-solid fa-image"></i></div>
                <?php endif; ?>
              </div>
              <span class="status-badge status-<?= strtolower(str_replace(' ', '-', $item['status'])) ?>">
                <?= htmlspecialchars($item['status']) ?>
              </span>
            </div>
            <h3 class="equipment-name"><?= htmlspecialchars($item['name']) ?></h3>
            <p class="equipment-category"><?= htmlspecialchars($item['category']) ?></p>

            <?php if ($item['status'] === 'Maintenance' && !empty($item['maintenance_start_date'])): ?>
              <div class="card-maintenance-info">
                <i class="fa-solid fa-wrench"></i>
                <div class="maintenance-schedule">
                  <strong>Maintenance:</strong>
                  <?= date('M d', strtotime($item['maintenance_start_date'])) ?> -
                  <?= date('M d, Y', strtotime($item['maintenance_end_date'])) ?>
                </div>
                <?php if (!empty($item['maintenance_reason'])): ?>
                  <div class="maintenance-reason-card"><?= htmlspecialchars($item['maintenance_reason']) ?></div>
                <?php endif; ?>
              </div>
            <?php endif; ?>

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
      <form id="equipmentForm" class="side-panel-body" method="post" enctype="multipart/form-data">
        <input type="hidden" id="equipmentId" name="id">

        <input type="hidden" id="existingImage" name="existing_image">

        <!-- Image Upload Preview -->
        <div class="form-group">
          <label>Equipment Image</label>
          <div class="image-upload-container">
            <div id="imagePreview" class="image-preview" onclick="document.getElementById('equipmentImage').click()">
              <i class="fa-solid fa-image"></i>
              <p>Click to upload or drag image here</p>
            </div>
            <input type="file" id="equipmentImage" name="image" accept="image/png,image/jpeg,image/jpg" style="display:none;"
              onchange="previewImage(event)">
            <button type="button" class="btn-secondary btn-small"
              onclick="document.getElementById('equipmentImage').click()">
              <i class="fa-solid fa-upload"></i> Choose Image
            </button>
            <p class="file-info">Accepted formats: JPG, PNG, WEBP • Max size: 5MB</p>
          </div>
        </div>
        <div class="form-group">
          <label for="equipmentName">Equipment Name *</label>
          <input type="text" id="equipmentName" name="name" required placeholder="e.g., Treadmill Pro X500, Dumbbell Set 5-50kg">
        </div>

        <div class="form-group">
          <label for="equipmentCategory">Category *</label>
          <select id="equipmentCategory" name="category" required>
            <option value="">-- Select Category --</option>
            <option value="Cardio">Cardio</option>
            <option value="Flexibility">Flexibility</option>
            <option value="Core">Core</option>
            <option value="Strength Training">Strength Training</option>
            <option value="Functional Training">Functional Training</option>
          </select>
        </div>

        <div class="form-group">
          <label for="equipmentStatus">Status *</label>
          <select id="equipmentStatus" name="status" required onchange="toggleMaintenanceFields()">
            <option value="Available">✅ Available</option>
            <option value="Maintenance">🔧 Maintenance</option>
            <option value="Out of Order">❌ Out of Order</option>
          </select>
        </div>

        <!-- Maintenance Schedule Fields (shown only when status is Maintenance) -->
        <div id="maintenanceFields" class="maintenance-section" style="display: none;">
          <div class="maintenance-header">
            <i class="fa-solid fa-wrench"></i>
            <h4>Maintenance Schedule</h4>
          </div>

          <div class="form-group">
            <label for="maintenanceStartDate">Maintenance Start Date *</label>
            <input type="date" id="maintenanceStartDate" name="maintenance_start_date"
                   min="<?= date('Y-m-d') ?>">
          </div>

          <div class="form-group">
            <label for="maintenanceEndDate">Expected End Date *</label>
            <input type="date" id="maintenanceEndDate" name="maintenance_end_date"
                   min="<?= date('Y-m-d') ?>">
          </div>

          <div class="form-group">
            <label for="maintenanceReason">Maintenance Reason</label>
            <textarea id="maintenanceReason" name="maintenance_reason" rows="3"
              placeholder="E.g., Routine maintenance, Repair broken part, Safety inspection..."></textarea>
          </div>
        </div>

        <div class="form-group">
          <label for="equipmentDescription">Description (Optional)</label>
          <textarea id="equipmentDescription" name="description" rows="4"
            placeholder="Add details about the equipment, features, usage instructions, or maintenance notes..."></textarea>
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

  <!-- DSA Utilities -->
  <script src="<?= PUBLIC_PATH ?>/js/dsa/dsa-utils.js?v=<?= time() ?>"></script>

  <script src="<?= PUBLIC_PATH ?>/php/admin/js/sidebar.js"></script>
  <script src="<?= PUBLIC_PATH ?>/php/admin/js/equipment.js?=v1"></script>

  <!-- DSA Integration -->
  <script src="<?= PUBLIC_PATH ?>/js/dsa/equipment-dsa-integration.js?v=<?= time() ?>"></script>
</body>

</html>
