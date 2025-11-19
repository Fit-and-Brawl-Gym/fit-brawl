// =============================================
// Trainer Schedule Management JavaScript
// =============================================

// Modal & Form Elements
const addBlockModal = document.getElementById('addBlockModal');
const btnAddBlock = document.getElementById('btnAddBlock');
const closeModal = document.getElementById('closeModal');
const cancelModal = document.getElementById('cancelModal');
const addBlockForm = document.getElementById('addBlockForm');
const trainerSelect = document.getElementById('trainer');
const sessionSelect = document.getElementById('session');
const dateInput = document.getElementById('date');

// Bulk Actions
const selectAll = document.getElementById('selectAll');
const checkboxes = document.querySelectorAll('.block-checkbox');
const bulkActionsBar = document.getElementById('bulkActionsBar');
const selectedCount = document.getElementById('selectedCount');

// =======================
// Modal Open/Close
// =======================
btnAddBlock.addEventListener('click', async () => {
    // Show modal
    addBlockModal.classList.add('active');
    
    // Set date to today
    if (dateInput) dateInput.value = new Date().toISOString().split('T')[0];

    // Get submit button safely
    const submitBtn = addBlockForm?.querySelector('button[type="submit"]');
    if (!submitBtn) {
        console.error('Submit button not found');
        return;
    }

    if (trainerSelect.value) {
        submitBtn.disabled = true; // disable until sessions load
        await populateSessions(trainerSelect.value);
        submitBtn.disabled = false;
    } else {
        const fromSelect = document.getElementById('sessionFrom');
        const toSelect = document.getElementById('sessionTo');

        if (fromSelect) fromSelect.innerHTML = '<option value="">Select Trainer First</option>';
        if (toSelect) toSelect.innerHTML = '<option value="">Select Trainer First</option>';
    }
});


closeModal.addEventListener('click', closeModalFunc);
cancelModal.addEventListener('click', closeModalFunc);
addBlockModal.addEventListener('click', (e) => {
    if (e.target === addBlockModal) closeModalFunc();
});

function closeModalFunc() {
    addBlockModal.classList.remove('active');
    addBlockForm?.reset();

    const fromSelect = document.getElementById('sessionFrom');
    const toSelect = document.getElementById('sessionTo');

    if (fromSelect) fromSelect.innerHTML = '<option value="">Loading...</option>';
    if (toSelect) toSelect.innerHTML = '<option value="">Loading...</option>';
}

// =======================
// Populate Session Dropdown
// =======================
async function populateSessions(trainerId) {
    const fromSelect = document.getElementById('sessionFrom');
    const toSelect = document.getElementById('sessionTo');

    if (!fromSelect || !toSelect) {
        console.error('Session dropdowns not found');
        return false;
    }

    fromSelect.innerHTML = '<option value="">Loading...</option>';
    toSelect.innerHTML = '<option value="">Loading...</option>';

    try {
        const res = await fetch(`api/get_trainer_shift.php?trainer_id=${trainerId}`);
        const data = await res.json();

        if (!data.success || !data.shift) {
            const msg = data.message || 'No shifts available';
            fromSelect.innerHTML = `<option value="">${msg}</option>`;
            toSelect.innerHTML = `<option value="">${msg}</option>`;
            return false;
        }

        const shift = data.shift;
        fromSelect.innerHTML = '';
        toSelect.innerHTML = '';

        // Generate times in 30-minute intervals
        const times = generateTimeOptions(shift.start, shift.end);
        times.forEach(time => {
            const optionFrom = document.createElement('option');
            optionFrom.value = time;
            optionFrom.textContent = time;
            fromSelect.appendChild(optionFrom);

            const optionTo = document.createElement('option');
            optionTo.value = time;
            optionTo.textContent = time;
            toSelect.appendChild(optionTo);
        });

        return true;
    } catch (err) {
        console.error('Error fetching trainer shifts:', err);
        fromSelect.innerHTML = '<option value="">Error loading shifts</option>';
        toSelect.innerHTML = '<option value="">Error loading shifts</option>';
        return false;
    }
}

// Helper: generate times in 30-minute intervals
function generateTimeOptions(start, end) {
    const times = [];
    let current = new Date(`1970-01-01T${start}`);
    const endTime = new Date(`1970-01-01T${end}`);

    while (current <= endTime) {
        const hh = current.getHours().toString().padStart(2, '0');
        const mm = current.getMinutes().toString().padStart(2, '0');
        times.push(`${hh}:${mm}`);
        current.setMinutes(current.getMinutes() + 30);
    }

    return times;
}

// Populate sessions on trainer change
trainerSelect.addEventListener('change', () => {
    if (trainerSelect.value) populateSessions(trainerSelect.value);
});

// =======================
// Form Submission
// =======================
addBlockForm.addEventListener('submit', async (e) => {
    e.preventDefault();

    const submitBtn = addBlockForm.querySelector('button[type="submit"]');
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Marking unavailable...';

    const from = document.getElementById('sessionFrom').value;
    const to = document.getElementById('sessionTo').value;

    if (!from || !to) {
        showToast('Please select both From and To times.', 'warning');
        document.getElementById(!from ? 'sessionFrom' : 'sessionTo').focus();
        submitBtn.disabled = false;
        submitBtn.innerHTML = '<i class="fa-solid fa-ban"></i> Mark Unavailable';
        return;
    }

    if (from >= to) {
        showToast('End time must be later than start time.', 'warning');
        document.getElementById('sessionTo').focus();
        submitBtn.disabled = false;
        submitBtn.innerHTML = '<i class="fa-solid fa-ban"></i> Mark Unavailable';
        return;
    }

    try {
        const formData = new FormData(addBlockForm);
        formData.append('action', 'add_block');

        const response = await fetch('trainer_availability.php?ajax=1', {
            method: 'POST',
            body: formData
        });

        const text = await response.text();
        let result;
        try {
            result = JSON.parse(text);
        } catch (err) {
            console.error('Invalid JSON response:', text);
            showToast('Server returned invalid response', 'error');
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="fa-solid fa-ban"></i> Mark Unavailable';
            return;
        }

        if (result.success) {
            showToast('Trainer marked unavailable successfully', 'success');
            setTimeout(() => window.location.reload(), 1000);
        } else {
            showToast(result.message || 'Failed to mark unavailable', 'error');
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="fa-solid fa-ban"></i> Mark Unavailable';
        }
    } catch (error) {
        console.error('AJAX error:', error);
        showToast('An error occurred', 'error');
        submitBtn.disabled = false;
        submitBtn.innerHTML = '<i class="fa-solid fa-ban"></i> Mark Unavailable';
    }
});

// =======================
// Filters
// =======================
['trainerFilter', 'sessionFilter', 'dateFrom', 'dateTo'].forEach(id => {
    document.getElementById(id)?.addEventListener('change', applyFilters);
});

function applyFilters() {
    const params = new URLSearchParams();
    const trainer = document.getElementById('trainerFilter').value;
    const session = document.getElementById('sessionFilter').value;
    const dateFrom = document.getElementById('dateFrom').value;
    const dateTo = document.getElementById('dateTo').value;

    if (trainer !== 'all') params.append('trainer', trainer);
    if (session !== 'all') params.append('session', session);
    if (dateFrom) params.append('date_from', dateFrom);
    if (dateTo) params.append('date_to', dateTo);

    window.location.href = 'trainer_availability.php' + (params.toString() ? '?' + params.toString() : '');
}

// =======================
// Bulk Selection
// =======================
selectAll?.addEventListener('change', (e) => {
    checkboxes.forEach(cb => cb.checked = e.target.checked);
    updateBulkActions();
});

checkboxes.forEach(cb => cb.addEventListener('change', updateBulkActions));

function updateBulkActions() {
    const selected = Array.from(checkboxes).filter(cb => cb.checked);
    const count = selected.length;

    if (count > 0) {
        bulkActionsBar.classList.add('active');
        selectedCount.textContent = `${count} selected`;
    } else {
        bulkActionsBar.classList.remove('active');
        if (selectAll) selectAll.checked = false;
    }
}

// =======================
// Bulk Delete
// =======================
document.querySelector('.bulk-actions .btn-delete')?.addEventListener('click', async () => {
    const selected = Array.from(checkboxes)
        .filter(cb => cb.checked)
        .map(cb => cb.value);

    if (selected.length === 0) return;
    if (!confirm(`Are you sure you want to remove ${selected.length} unavailability entries?`)) return;

    const formData = new FormData();
    formData.append('action', 'bulk_delete');

    // Append each id individually so PHP gets an array
    selected.forEach(id => formData.append('ids[]', id));

    try {
        const response = await fetch('trainer_availability.php?ajax=1', {
            method: 'POST',
            body: formData
        });

        const text = await response.text();
        let result;
        try {
            result = JSON.parse(text);
        } catch (err) {
            console.error('Invalid JSON response:', text);
            showToast('Server returned invalid response', 'error');
            return;
        }

        if (result.success) {
            showToast('Unavailability entries removed successfully', 'success');
            setTimeout(() => window.location.reload(), 1000);
        } else {
            showToast(result.message || 'Failed to remove unavailability entries', 'error');
        }
    } catch (error) {
        console.error('AJAX error:', error);
        showToast('An error occurred', 'error');
    }
});

// =======================
// Single Delete
// =======================
document.querySelectorAll('.btn-delete-single').forEach(btn => {
    btn.addEventListener('click', async (e) => {
        e.stopPropagation();
        const blockId = btn.dataset.id;

        if (!confirm('Are you sure you want to remove this unavailability entry?')) return;

        const formData = new FormData();
        formData.append('action', 'delete_block');
        formData.append('block_id', blockId);

        btn.disabled = true;
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i>';

        try {
            const response = await fetch('trainer_availability.php?ajax=1', {
                method: 'POST',
                body: formData
            });

            const result = await response.json();

            if (result.success) {
                showToast('Unavailability entry removed successfully', 'success');
                setTimeout(() => window.location.reload(), 1000);
            } else {
                showToast('Failed to remove unavailability entry', 'error');
                btn.disabled = false;
                btn.innerHTML = '<i class="fa-solid fa-trash"></i>';
            }
        } catch (error) {
            console.error('Error:', error);
            showToast('An error occurred', 'error');
            btn.disabled = false;
            btn.innerHTML = '<i class="fa-solid fa-trash"></i>';
        }
    });
});

// =======================
// Toast Notifications
// =======================
function showToast(message, type = 'info') {
    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    toast.textContent = message;
    toast.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 16px 24px;
        background: ${type === 'success' ? '#27ae60' : type === 'error' ? '#e74c3c' : '#3498db'};
        color: white;
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        z-index: 10000;
        font-weight: 500;
        animation: slideIn 0.3s ease;
    `;
    document.body.appendChild(toast);

    setTimeout(() => {
        toast.style.animation = 'slideOut 0.3s ease';
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}

// =======================
// CSS Animations
// =======================
const style = document.createElement('style');
style.textContent = `
@keyframes slideIn {
    from { transform: translateX(400px); opacity: 0; }
    to { transform: translateX(0); opacity: 1; }
}
@keyframes slideOut {
    from { transform: translateX(0); opacity: 1; }
    to { transform: translateX(400px); opacity: 0; }
}
.bulk-actions {
    display: none;
    align-items: center;
    gap: 16px;
    padding: 16px;
    background: #f8f9fa;
    border-radius: 8px;
    margin-bottom: 20px;
}
.bulk-actions.active { display: flex; }
.bulk-actions .btn-delete {
    background: #e74c3c;
    color: white;
    border: none;
    padding: 10px 20px;
    border-radius: 6px;
    cursor: pointer;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 8px;
    transition: all 0.2s;
}
.bulk-actions .btn-delete:hover { background: #c0392b; }
`;
document.head.appendChild(style);
