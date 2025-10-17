let deleteId = null;
let deleteIds = [];

// Open side panel for adding
function openSidePanel() {
    document.getElementById('panelTitle').textContent = 'Add New Product';
    document.getElementById('productForm').reset();
    document.getElementById('productId').value = '';
    document.getElementById('existingImage').value = '';
    resetImagePreview();
    document.getElementById('sidePanel').classList.add('active');
}

// Open side panel for editing
function editProduct(product) {
    document.getElementById('panelTitle').textContent = 'Edit Product';
    document.getElementById('productId').value = product.id;
    document.getElementById('productName').value = product.name;
    document.getElementById('productCategory').value = product.category;
    document.getElementById('productBrand').value = product.brand || '';
    document.getElementById('productPrice').value = product.price;
    document.getElementById('productStock').value = product.stock;
    document.getElementById('productDescription').value = product.description || '';
    document.getElementById('existingImage').value = product.image || '';

    // Show existing image
    if (product.image) {
        const preview = document.getElementById('imagePreview');
        preview.style.backgroundImage = `url('../../../../uploads/products/${product.image}')`;
        preview.style.backgroundSize = 'cover';
        preview.style.backgroundPosition = 'center';
        preview.innerHTML = '';
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
    const rows = document.querySelectorAll('#productsTableBody tr');

    rows.forEach(row => {
        const name = row.querySelector('.product-name').textContent.toLowerCase();
        const category = row.dataset.category;
        const status = row.dataset.status;

        const matchesSearch = name.includes(searchTerm);
        const matchesCategory = selectedCategory === 'all' || category === selectedCategory;
        const matchesStatus = selectedStatus === 'all' || status === selectedStatus;

        if (matchesSearch && matchesCategory && matchesStatus) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
}