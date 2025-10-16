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
        card.innerHTML = `
      <h3>${eq.name}</h3>
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
            await fetch('api/admin_equipment_api.php?action=update', {
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
            await fetch('api/admin_equipment_api.php?action=delete', {
                method: 'POST',
                body: new URLSearchParams({ id })
            });
            loadEquipment();
        });
    });
}
