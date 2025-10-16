document.addEventListener('DOMContentLoaded', () => {
    loadEquipment();

    // Add Equipment
    const form = document.getElementById('addEquipmentForm');
    form.addEventListener('submit', async e => {
        e.preventDefault();
        const formData = new FormData(form);
        const res = await fetch('api/admin_equipment_api.php?action=add', {
            method: 'POST',
            body: formData
        });
        const data = await res.json();
        if (data.success) {
            alert('Equipment added successfully!');
            form.reset();
            loadEquipment();
        } else {
            alert('Failed to add equipment.');
        }
    });
});

async function loadEquipment() {
    const list = document.getElementById('equipmentList');
    const res = await fetch('api/admin_admin_equipment_api.php?action=fetch');
    const data = await res.json();

    list.innerHTML = '';
    if (data.length === 0) {
        list.innerHTML = '<p>No equipment found.</p>';
        return;
    }

    data.forEach(eq => {
        const card = document.createElement('div');
        card.classList.add('equipment-card');
        card.dataset.category = eq.category; // Add this line
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
      <button class="delete-btn" data-id="${eq.id}">Delete</button>
    `;
        list.appendChild(card);
    });

    attachListeners();
}

function attachListeners() {
    // Update status
    document.querySelectorAll('.status-dropdown').forEach(sel => {
        sel.addEventListener('change', async e => {
            const id = e.target.dataset.id;
            const status = e.target.value;
            await fetch('api/admin_admin_equipment_api.php?action=update', {
                method: 'POST',
                body: new URLSearchParams({ id, status })
            });
        });
    });

    // Delete equipment
    document.querySelectorAll('.delete-btn').forEach(btn => {
        btn.addEventListener('click', async e => {
            if (!confirm('Are you sure you want to delete this equipment?')) return;
            const id = e.target.dataset.id;
            await fetch('api/admin_admin_equipment_api.php?action=delete', {
                method: 'POST',
                body: new URLSearchParams({ id })
            });
            loadEquipment();
        });
    });
}

let deleteId = null;

// Open side panel for adding
function openSidePanel() {
    document.getElementById('panelTitle').textContent = 'Add New Equipment';
    document.getElementById('equipmentForm').reset();
    document.getElementById('equipmentId').value = '';
    document.getElementById('sidePanel').classList.add('active');
}

// Open side panel for editing
function editEquipment(equipment) {
    document.getElementById('panelTitle').textContent = 'Edit Equipment';
    document.getElementById('equipmentId').value = equipment.id;
    document.getElementById('equipmentName').value = equipment.name;
    document.getElementById('equipmentCategory').value = equipment.category;
    document.getElementById('equipmentStatus').value = equipment.status;
    document.getElementById('equipmentDescription').value = equipment.description || '';
    document.getElementById('sidePanel').classList.add('active');
}

// Close side panel
function closeSidePanel() {
    document.getElementById('sidePanel').classList.remove('active');
}

// Handle form submission
document.getElementById('equipmentForm').addEventListener('submit', async (e) => {
    e.preventDefault();

    const formData = new FormData(e.target);
    const data = Object.fromEntries(formData);

    try {
        const response = await fetch('api/admin_equipment_api.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });

        const result = await response.json();

        if (result.success) {
            closeSidePanel();
            location.reload();
        } else {
            alert('Error: ' + (result.message || 'Failed to save equipment'));
        }
    } catch (error) {
        console.error('Error:', error);
        alert('An error occurred. Please try again.');
    }
});

// Delete equipment
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

    try {
        const response = await fetch(`api/admin_equipment_api.php?id=${deleteId}`, {
            method: 'DELETE'
        });

        const result = await response.json();

        if (result.success) {
            closeDeleteModal();
            location.reload();
        } else {
            alert('Error: ' + (result.message || 'Failed to delete equipment'));
        }
    } catch (error) {
        console.error('Error:', error);
        alert('An error occurred. Please try again.');
    }
}

// Search functionality
document.getElementById('searchInput').addEventListener('input', (e) => {
    const searchTerm = e.target.value.toLowerCase();
    filterEquipment();
});

// Category filter
document.getElementById('categoryFilter').addEventListener('change', filterEquipment);

// Tab filtering
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

        if (matchesSearch && matchesCategory) {
            card.style.display = 'block';
        } else {
            card.style.display = 'none';
        }
    });
}
