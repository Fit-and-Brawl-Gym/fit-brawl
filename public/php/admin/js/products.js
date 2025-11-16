let deleteId = null;
let deleteIds = [];

// Helper to get CSRF token
function getCsrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.content || '';
}

// Open side panel for adding
function openSidePanel() {
    document.getElementById('panelTitle').textContent = 'Add New Product';
    document.getElementById('productForm').reset();
    document.getElementById('productId').value = '';
    document.getElementById('existingImage').value = '';
    resetImagePreview();
    document.getElementById('sidePanel').classList.add('active');
}


function editProduct(product) {
    console.log("🟢 editProduct triggered:", product);
    document.getElementById('panelTitle').textContent = 'Edit Product';
    document.getElementById('productId').value = product.id;
    document.getElementById('productName').value = product.name;


    const categoryField = document.getElementById('productCategory');
    categoryField.value = product.category;


    document.getElementById('productStock').value = product.stock;


    const imageFile = product.image || product.image_path || '';
    document.getElementById('existingImage').value = imageFile;


    if (imageFile) {
        const preview = document.getElementById('imagePreview');
        const fileName = imageFile.split('/').pop();

        // Use environment-aware path
        const imagePath = `${window.UPLOADS_PATH}/products/${fileName}`;

        preview.style.backgroundImage = `url('${imagePath}')`;
        preview.style.backgroundSize = 'cover';
        preview.style.backgroundPosition = 'center';
        preview.innerHTML = '';
    } else {
        resetImagePreview();
    }

    document.getElementById('sidePanel').classList.add('active');
}

// Close side panel
function closeSidePanel() {
    document.getElementById('sidePanel').classList.remove('active');
}

// Preview image before upload
function previewImage(event) {
    const file = event.target.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function (e) {
            const preview = document.getElementById('imagePreview');
            preview.style.backgroundImage = `url('${e.target.result}')`;
            preview.style.backgroundSize = 'cover';
            preview.style.backgroundPosition = 'center';
            preview.innerHTML = '';
        };
        reader.readAsDataURL(file);
    }
}

function resetImagePreview() {
    const preview = document.getElementById('imagePreview');
    preview.style.backgroundImage = 'none';
    preview.innerHTML = '<i class="fa-solid fa-image"></i><p>Click to upload image</p>';
}

// Handle form submission
document.getElementById('productForm').addEventListener('submit', async (e) => {
    e.preventDefault();

    const formData = new FormData(e.target);
    formData.append('csrf_token', getCsrfToken());

    try {
        const response = await fetch('api/admin_products_api.php', {
            method: 'POST',
            body: formData
        });

        const result = await response.json();

        if (result.success) {
            closeSidePanel();
            location.reload();
        } else {
            alert('Error: ' + (result.message || 'Failed to save product'));
        }
    } catch (error) {
        console.error('Error:', error);
        alert('An error occurred. Please try again.');
    }
});

// Save product (add or edit)
async function saveProduct(formData) {
    try {
        const response = await fetch('api/product_actions.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                action: formData.id ? 'edit' : 'add',
                id: formData.id,
                name: formData.name,
                category: formData.category,
                stock: formData.stock,
                csrf_token: getCsrfToken()
            })
        });

        const result = await response.json();

        if (result.success) {
            alert('Product saved successfully!');
            closeSidePanel();
            location.reload(); // Reload to see changes
        } else {
            alert('Error: ' + result.message);
        }
    } catch (error) {
        console.error('Save error:', error);
        alert('An error occurred while saving');
    }
}

// Delete product
async function deleteProduct(id) {
    if (!confirm('Are you sure you want to delete this product?')) {
        return;
    }

    try {
        const response = await fetch('api/product_actions.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                action: 'delete',
                id: id,
                csrf_token: getCsrfToken()
            })
        });

        const result = await response.json();

        if (result.success) {
            alert('Product deleted successfully!');
            location.reload();
        } else {
            alert('Error: ' + result.message);
        }
    } catch (error) {
        console.error('Delete error:', error);
        alert('An error occurred while deleting');
    }
}

// Delete single product
function deleteProduct(id, name) {
    deleteId = id;
    deleteIds = [];
    document.getElementById('deleteMessage').textContent =
        `Are you sure you want to delete "${name}"? This action cannot be undone.`;
    document.getElementById('deleteModal').classList.add('active');
}

// Bulk delete
function bulkDelete() {
    const checkboxes = document.querySelectorAll('.row-checkbox:checked');
    deleteIds = Array.from(checkboxes).map(cb => cb.value);
    deleteId = null;

    if (deleteIds.length === 0) return;

    document.getElementById('deleteMessage').textContent =
        `Are you sure you want to delete ${deleteIds.length} product(s)? This action cannot be undone.`;
    document.getElementById('deleteModal').classList.add('active');
}

function closeDeleteModal() {
    document.getElementById('deleteModal').classList.remove('active');
    deleteId = null;
    deleteIds = [];
}

async function confirmDelete() {
    try {
        let response;

        if (deleteIds.length > 0) {
            // Bulk delete
            response = await fetch('api/admin_products_api.php?bulk=1', {
                method: 'DELETE',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ ids: deleteIds })
            });
        } else if (deleteId) {
            // Single delete
            response = await fetch(`api/admin_products_api.php?id=${deleteId}`, {
                method: 'DELETE'
            });
        }

        const result = await response.json();

        if (result.success) {
            closeDeleteModal();
            location.reload();
        } else {
            alert('Error: ' + (result.message || 'Failed to delete product(s)'));
        }
    } catch (error) {
        console.error('Error:', error);
        alert('An error occurred. Please try again.');
    }
}

// Select all checkbox
function toggleSelectAll() {
    const selectAll = document.getElementById('selectAll');
    const checkboxes = document.querySelectorAll('.row-checkbox');
    checkboxes.forEach(cb => cb.checked = selectAll.checked);
    updateBulkDelete();
}

// Update bulk delete button
function updateBulkDelete() {
    const checkboxes = document.querySelectorAll('.row-checkbox:checked');
    const bulkBtn = document.getElementById('bulkDeleteBtn');
    const count = document.getElementById('selectedCount');

    if (checkboxes.length > 0) {
        bulkBtn.style.display = 'inline-flex';
        count.textContent = checkboxes.length;
    } else {
        bulkBtn.style.display = 'none';
        document.getElementById('selectAll').checked = false;
    }
}

// Search functionality
document.getElementById('searchInput').addEventListener('input', filterProducts);
document.getElementById('categoryFilter').addEventListener('change', filterProducts);
document.getElementById('statusFilter').addEventListener('change', filterProducts);

function filterProducts() {
    const searchTerm = document.getElementById('searchInput').value.toLowerCase();
    const selectedCategory = document.getElementById('categoryFilter').value;
    const selectedStatus = document.getElementById('statusFilter').value;

    // Filter table rows
    const rows = document.querySelectorAll('#productsTableBody tr');
    rows.forEach(row => {
        const name = (row.querySelector('.product-name') && row.querySelector('.product-name').textContent.toLowerCase()) || '';
        const category = (row.dataset.category || '').toString();
        const status = (row.dataset.status || '').toString();

        const matchesSearch = name.includes(searchTerm);
    const matchesCategory = selectedCategory === 'all' || category.toLowerCase() === selectedCategory.toLowerCase();
    const matchesStatus = selectedStatus === 'all' || status.toLowerCase() === selectedStatus.toLowerCase();

        row.style.display = (matchesSearch && matchesCategory && matchesStatus) ? '' : 'none';
    });

    // Filter card view
    const cards = document.querySelectorAll('.product-card');
    cards.forEach(card => {
        const name = (card.querySelector('.product-name') && card.querySelector('.product-name').textContent.toLowerCase()) || '';
        const category = (card.dataset.category || '').toString();
        const status = (card.dataset.status || '').toString();

        const matchesSearch = name.includes(searchTerm);
    const matchesCategory = selectedCategory === 'all' || category.toLowerCase() === selectedCategory.toLowerCase();
    const matchesStatus = selectedStatus === 'all' || status.toLowerCase() === selectedStatus.toLowerCase();

        card.style.display = (matchesSearch && matchesCategory && matchesStatus) ? '' : 'none';
    });
}

// View Toggle (Table vs Cards)
document.querySelectorAll('.view-btn').forEach(btn => {
    btn.addEventListener('click', () => {
        const view = btn.dataset.view;

        // Update active button
        document.querySelectorAll('.view-btn').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');

        // Toggle views
        const tableView = document.getElementById('tableView');
        const cardsView = document.getElementById('cardsView');

        if (view === 'table') {
            tableView.classList.add('active');
            cardsView.classList.remove('active');
        } else {
            tableView.classList.remove('active');
            cardsView.classList.add('active');
        }
    });
});
