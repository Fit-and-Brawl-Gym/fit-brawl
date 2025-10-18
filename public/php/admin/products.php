<?php
// filepath: c:\xampp\htdocs\fit-brawl\public\php\admin\products.php
include_once('../../../includes/init.php');

// Only admins can access
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}


if (isset($_GET['api']) && $_GET['api'] === 'true') {
    header('Content-Type: application/json');
    require_once __DIR__ . '/../../includes/db_connect.php';

    try {

        $sql = "SELECT id, name, category AS cat, stock, status, image_path AS image FROM products ORDER BY category, name";
        $result = $conn->query($sql);

        if (!$result) {
            throw new Exception($conn->error);
        }

        $products = [];
        while ($row = $result->fetch_assoc()) {
            // Normalize status
            $status = strtolower(trim($row['status'] ?? ''));
            if (str_contains($status, 'in')) {
                $row['status'] = 'in';
            } elseif (str_contains($status, 'low')) {
                $row['status'] = 'low';
            } else {
                $row['status'] = 'out';
            }
            

            if (empty($row['image'])) {
                $row['image'] = '../../../uploads/products' . strtolower(str_replace(' ', '-', $row['name'])) . '.jpg';
            }
        

            $products[] = $row;
        }

        echo json_encode(['success' => true, 'data' => $products], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}


$sql = "SELECT * FROM products ORDER BY category, name";
$result = $conn->query($sql);
$products = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];


unset($p);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Products Management - Fit & Brawl Gym</title>
    <link rel="stylesheet" href="css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>

<body>
    <?php include 'admin_sidebar.php'; ?>

    <main class="admin-main">
        <!-- Header Section -->
        <header class="page-header">
            <div>
                <h1>Products Management</h1>
                <p class="subtitle">Manage gym store inventory and stock levels</p>
            </div>
        </header>

        <!-- Toolbar -->
        <div class="toolbar">
            <div class="search-box">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input type="text" id="searchInput" placeholder="Search products...">
            </div>
            <select id="categoryFilter" class="filter-dropdown">
                <option value="all">All Categories</option>
                <option value="Supplements">Supplements</option>
                <option value="Hydration & Drinks">Hydration & Drinks</option>
                <option value="Snacks">Snacks</option>
                <option value="Boxing & Muay Thai Products">Boxing & Muay Thai Products</option>
            </select>
            <select id="statusFilter" class="filter-dropdown">
                <option value="all">All Status</option>
                <option value="In Stock">In Stock</option>
                <option value="Low Stock">Low Stock</option>
                <option value="Out of Stock">Out of Stock</option>
            </select>
            <button class="btn-primary" onclick="openSidePanel()">
                <i class="fa-solid fa-plus"></i> Add New Product
            </button>
            <button class="btn-danger" id="bulkDeleteBtn" style="display:none;" onclick="bulkDelete()">
                <i class="fa-solid fa-trash"></i> Delete Selected (<span id="selectedCount">0</span>)
            </button>
        </div>

        <!-- Products Table -->
        <div class="table-container">
            <?php if (empty($products)): ?>
                <div class="empty-state">
                    <i class="fa-solid fa-box"></i>
                    <h3>No Products Found</h3>
                    <p>Start by adding your first product</p>
                    <button class="btn-primary" onclick="openSidePanel()">Add Product</button>
                </div>
            <?php else: ?>
                <table class="products-table">
                    <thead>
                        <tr>
                            <th width="40">
                                <input type="checkbox" id="selectAll" onchange="toggleSelectAll()">
                            </th>
                            <th width="80">Image</th>
                            <th>Product Name</th>
                            <th>Category</th>
                            <th width="80">Stock</th>
                            <th width="120">Status</th>
                            <th width="160">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="productsTableBody">
                        <?php foreach ($products as $product): ?>
                            <tr data-category="<?= htmlspecialchars($product['category']) ?>"
                                data-status="<?= htmlspecialchars($product['status']) ?>" data-id="<?= $product['id'] ?>">
                                <td>
                                    <input type="checkbox" class="row-checkbox" value="<?= $product['id'] ?>"
                                        onchange="updateBulkDelete()">
                                </td>
                                <td>
                                    <?php if (!empty($product['image_path'])): ?>
                                        <img src="/fit-brawl/uploads/products/<?= htmlspecialchars($product['image_path']) ?>"
                                        alt="<?= htmlspecialchars($product['name']) ?>" class="product-thumb">
                                    <?php else: ?>
                                        <div class="product-thumb no-image">
                                            <i class="fa-solid fa-image"></i>
                                        </div>
                                    <?php endif; ?>
                                </td>

                                <td class="product-name"><?= htmlspecialchars($product['name']) ?></td>
                                <td><?= $product['category'] ?></td>

                                <td class="stock"><?= $product['stock'] ?></td>
                                <td>
                                    <?php
                                    // Convert "Out of Stock" -> "out-of-stock"
                                    $statusClass = strtolower(str_replace(' ', '-', $product['status']));
                                    ?>
                                    <span class="status-badge status-<?= $statusClass ?>">
                                        <?= htmlspecialchars($product['status']) ?>
                                    </span>
                                </td>
                                <td class="actions">
                                    <button class="btn-icon btn-edit" onclick='editProduct(<?= json_encode($product) ?>)'
                                        title="Edit">
                                        <i class="fa-solid fa-pen"></i>
                                    </button>
                                    <button class="btn-icon btn-delete"
                                        onclick="deleteProduct(<?= $product['id'] ?>, '<?= addslashes($product['name']) ?>')"
                                        title="Delete">
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </main>

    <!-- Side Panel -->
    <div id="sidePanel" class="side-panel">
        <div class="side-panel-overlay" onclick="closeSidePanel()"></div>
        <div class="side-panel-content">
            <div class="side-panel-header">
                <h2 id="panelTitle">Add New Product</h2>
                <button class="close-btn" onclick="closeSidePanel()">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
            <form id="productForm" class="side-panel-body" enctype="multipart/form-data" method="post">
                <input type="hidden" id="productId" name="id">
                <input type="hidden" id="existingImage" name="existing_image">

                <!-- Image Upload Preview -->
                <div class="form-group">
                    <label>Product Image</label>
                    <div class="image-upload-container">
                        <div id="imagePreview" class="image-preview">
                            <i class="fa-solid fa-image"></i>
                            <p>Click to upload image</p>
                        </div>
                        <input type="file" id="productImage" name="image" accept="image/*" style="display:none;"
                            onchange="previewImage(event)">
                        <button type="button" class="btn-secondary btn-small"
                            onclick="document.getElementById('productImage').click()">
                            <i class="fa-solid fa-upload"></i> Choose Image
                        </button>
                    </div>
                </div>

                <div class="form-group">
                    <label for="productName">Product Name *</label>
                    <input type="text" id="productName" name="name" required placeholder="e.g., Whey Protein Isolate">
                </div>

                <div class="form-group">
                    <label for="productCategory">Category *</label>
                    <select id="productCategory" name="category" required>
                        <option value="">Select category</option>
                        <option value="Supplements">Supplements</option>
                        <option value="Hydration & Drinks">Hydration & Drinks</option>
                        <option value="Snacks">Snacks</option>
                        <option value="Boxing & Muay Thai Products">Boxing & Muay Thai Products</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="productStock">Stock Quantity *</label>
                    <input type="number" id="productStock" name="stock" required min="0" placeholder="0">
                    <small class="form-hint">Status will auto-update: 0=Out of Stock, 1-10=Low Stock, 11+=In
                        Stock</small>
                </div>

             

                <div class="side-panel-footer">
                    <button type="button" class="btn-secondary" onclick="closeSidePanel()">Cancel</button>
                    <button type="submit" class="btn-primary">
                        <i class="fa-solid fa-floppy-disk"></i> Save Product
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div id="deleteModal" class="modal">
        <div class="modal-content">
            <h3>Delete Product</h3>
            <p id="deleteMessage">Are you sure you want to delete this product?</p>
            <div class="modal-actions">
                <button class="btn-secondary" onclick="closeDeleteModal()">Cancel</button>
                <button class="btn-danger" onclick="confirmDelete()">Delete</button>
            </div>
        </div>
    </div>

    <script src="js/products.js"></script>
</body>

</html>
