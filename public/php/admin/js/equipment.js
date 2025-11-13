document.addEventListener('DOMContentLoaded', () => {
    // Equipment data is rendered by PHP, no need to load via AJAX

    const form = document.getElementById('equipmentForm');
    form.addEventListener('submit', async (e) => {
        e.preventDefault();

        const formData = new FormData(form);

        try {
            const res = await fetch('api/admin_equipment_api.php', {
                method: 'POST',
                body: formData,
            });

            const data = await res.json();
            if (data.success) {
                closeSidePanel();
                loadEquipment();
                location.reload();
            } else {
                console.error('Server error:', data);
                alert('Failed to save equipment: ' + (data.message || 'Unknown error'));
            }
        } catch (err) {
            console.error('Error saving equipment:', err);
            alert('An error occurred: ' + err.message);
        }
    });
});

// ================================
// Load Equipment Cards
// ================================
async function loadEquipment() {
    const list = document.getElementById('equipmentList');
    const res = await fetch('api/admin_equipment_api.php?action=fetch');
    const data = await res.json();

    list.innerHTML = '';
    if (data.length === 0) {
        list.innerHTML = '<p>No equipment found.</p>';
        return;
    }

    data.forEach(eq => {
        const card = document.createElement('div');
        card.classList.add('equipment-card');
        card.dataset.category = eq.category;
        card.innerHTML = `
            <h3 class="equipment-name">${eq.name}</h3>
            <p><b>Category:</b> ${eq.category}</p>
            <p><b>Status:</b>
                <select data-id="${eq.id}" class="status-dropdown">
                    ${['Available', 'Maintenance', 'Out of Order']
                        .map(opt => `<option value="${opt}" ${opt === eq.status ? 'selected' : ''}>${opt}</option>`)
                        .join('')}
                </select>
            </p>
            <p><b>Description:</b> ${eq.description || '—'}</p>
            <button class="edit-btn" data-equipment='${JSON.stringify(eq)}'>Edit</button>
            <button class="delete-btn" data-id="${eq.id}">Delete</button>
        `;
        list.appendChild(card);
    });

    attachListeners();
}

// ================================
// Attach Listeners
// ================================
function attachListeners() {
    // Update status
    document.querySelectorAll('.status-dropdown').forEach(sel => {
        sel.addEventListener('change', async e => {
            const id = e.target.dataset.id;
            const status = e.target.value;
            await fetch('api/admin_equipment_api.php?action=update', {
                method: 'POST',
                body: new URLSearchParams({ id, status }),
            });
        });
    });

    // Edit button
    document.querySelectorAll('.edit-btn').forEach(btn => {
        btn.addEventListener('click', e => {
            const equipment = JSON.parse(e.target.dataset.equipment);
            editEquipment(equipment);
        });
    });

    // Delete button
    document.querySelectorAll('.delete-btn').forEach(btn => {
        btn.addEventListener('click', async e => {
            if (!confirm('Are you sure you want to delete this equipment?')) return;
            const id = e.target.dataset.id;
            await fetch('api/admin_equipment_api.php?action=delete', {
                method: 'POST',
                body: new URLSearchParams({ id }),
            });
            loadEquipment();
        });
    });
}

// ================================
// Side Panel
// ================================
function openSidePanel() {
    document.getElementById('panelTitle').textContent = 'Add New Equipment';
    document.getElementById('equipmentForm').reset();
    resetImagePreview();
    document.getElementById('equipmentId').value = '';
    document.getElementById('sidePanel').classList.add('active');
}

function closeSidePanel() {
    document.getElementById('sidePanel').classList.remove('active');
}

// ================================
// Image Preview
// ================================
function previewImage(event) {
    const file = event.target.files[0];
    const preview = document.getElementById('imagePreview');

    if (file) {
        // Validate file type
        const validTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
        if (!validTypes.includes(file.type)) {
            alert('Invalid file type. Please upload JPG, PNG, or WEBP image.');
            event.target.value = '';
            resetImagePreview();
            return;
        }

        // Validate file size (5MB max)
        const maxSize = 5 * 1024 * 1024; // 5MB in bytes
        if (file.size > maxSize) {
            alert('File size exceeds 5MB. Please choose a smaller image.');
            event.target.value = '';
            resetImagePreview();
            return;
        }

        // Show preview
        const reader = new FileReader();
        reader.onload = function (e) {
            preview.innerHTML = `<img src="${e.target.result}" alt="Equipment preview">`;
            preview.classList.add('has-image');
        };
        reader.readAsDataURL(file);
    } else {
        resetImagePreview();
    }
}

function resetImagePreview() {
    const preview = document.getElementById('imagePreview');
    preview.classList.remove('has-image');
    preview.innerHTML = '<i class="fa-solid fa-image"></i><p>Click to upload or drag image here</p>';
}

// ================================
// Edit Equipment
// ================================
function editEquipment(equipment) {
    document.getElementById('panelTitle').textContent = 'Edit Equipment';
    document.getElementById('equipmentId').value = equipment.id;
    document.getElementById('equipmentName').value = equipment.name;
    document.getElementById('equipmentCategory').value = equipment.category;
    document.getElementById('equipmentStatus').value = equipment.status;
    document.getElementById('equipmentDescription').value = equipment.description || '';

    // Show existing image
    if (equipment.image_path) {
        const preview = document.getElementById('imagePreview');
        preview.style.backgroundImage = `url('${equipment.image_path}')`;
        preview.style.backgroundSize = 'cover';
        preview.style.backgroundPosition = 'center';
        preview.innerHTML = '';
    } else {
        resetImagePreview();
    }

    document.getElementById('sidePanel').classList.add('active');
}

// ================================
// Delete Modal (optional)
// ================================
let deleteId = null;

function deleteEquipment(id, name) {
    deleteId = id;
    document.getElementById('deleteMessage').textContent =
        `Are you sure you want to delete "${name}"? This action cannot be undone.`;
    document.getElementById('deleteModal').classList.add('active');
}

function closeDeleteModal() {
    document.getElementById('deleteModal').classList.remove('active');
    deleteId = null;
}

async function confirmDelete() {
    if (!deleteId) return;

    const response = await fetch(`api/admin_equipment_api.php?id=${deleteId}`, {
        method: 'DELETE',
    });

    const result = await response.json();
    if (result.success) {
        closeDeleteModal();
        loadEquipment();
        location.reload();
    } else {
        alert('Error deleting equipment.');
    }
}

// ================================
// Filters (Search, Category)
// ================================
document.getElementById('searchInput').addEventListener('input', filterEquipment);
document.getElementById('categoryFilter').addEventListener('change', filterEquipment);
var statusFilterEl = document.getElementById('statusFilter');
if (statusFilterEl) statusFilterEl.addEventListener('change', filterEquipment);

function filterEquipment() {
    const searchTerm = document.getElementById('searchInput').value.toLowerCase();
    const selectedCategory = document.getElementById('categoryFilter').value;
    const statusFilter = (document.getElementById('statusFilter') && document.getElementById('statusFilter').value) || 'all';

    // Filter cards
    const cards = document.querySelectorAll('.equipment-card');
    cards.forEach(card => {
        const name = card.querySelector('.equipment-name').textContent.toLowerCase();
    const category = (card.dataset.category || '').toString();
    const status = (card.dataset.status || '').toString();
        const matchesSearch = name.includes(searchTerm);
    const matchesCategory = selectedCategory === 'all' || category.toLowerCase() === selectedCategory.toLowerCase();
    const matchesStatus = statusFilter === 'all' || status.toLowerCase() === statusFilter.toLowerCase();
    card.style.display = (matchesSearch && matchesCategory && matchesStatus) ? 'block' : 'none';
    });

    // Filter table rows
    const rows = document.querySelectorAll('#tableView tbody tr[data-category]');
    rows.forEach(row => {
        const nameEl = row.querySelector('strong');
        const name = nameEl ? nameEl.textContent.toLowerCase() : '';
        const category = (row.dataset.category || '').toString();
        const status = (row.dataset.status || '').toString();
        const matchesSearch = name.includes(searchTerm);
        const matchesCategory = selectedCategory === 'all' || category.toLowerCase() === selectedCategory.toLowerCase();
        const matchesStatus = statusFilter === 'all' || status.toLowerCase() === statusFilter.toLowerCase();
        row.style.display = (matchesSearch && matchesCategory && matchesStatus) ? '' : 'none';
    });
}

// ================================
// View Toggle (Table vs Cards)
// ================================
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
