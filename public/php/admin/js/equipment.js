document.addEventListener('DOMContentLoaded', () => {
    loadEquipment();

   
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
                alert('Failed to save equipment: ' + (data.message || 'Unknown error'));
            }
        } catch (err) {
            console.error('Error saving equipment:', err);
            alert('An error occurred. Please try again.');
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
        const reader = new FileReader();
        reader.onload = function (e) {
            preview.style.backgroundImage = `url('${e.target.result}')`;
            preview.style.backgroundSize = 'cover';
            preview.style.backgroundPosition = 'center';
            preview.innerHTML = '';
        };
        reader.readAsDataURL(file);
    } else {
        resetImagePreview();
    }
}

function resetImagePreview() {
    const preview = document.getElementById('imagePreview');
    preview.style.backgroundImage = 'none';
    preview.innerHTML = '<i class="fa-solid fa-image"></i><p>Click to upload image</p>';
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
// Filters (Search, Category, Tabs)
// ================================
document.getElementById('searchInput').addEventListener('input', filterEquipment);
document.getElementById('categoryFilter').addEventListener('change', filterEquipment);

document.querySelectorAll('.tab').forEach(tab => {
    tab.addEventListener('click', () => {
        document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
        tab.classList.add('active');
        const category = tab.dataset.category;
        document.getElementById('categoryFilter').value = category;
        filterEquipment();
    });
});

function filterEquipment() {
    const searchTerm = document.getElementById('searchInput').value.toLowerCase();
    const selectedCategory = document.getElementById('categoryFilter').value;
    const cards = document.querySelectorAll('.equipment-card');

    cards.forEach(card => {
        const name = card.querySelector('.equipment-name').textContent.toLowerCase();
        const category = card.dataset.category;
        const matchesSearch = name.includes(searchTerm);
        const matchesCategory = selectedCategory === 'all' || category === selectedCategory;
        card.style.display = matchesSearch && matchesCategory ? 'block' : 'none';
    });
}
