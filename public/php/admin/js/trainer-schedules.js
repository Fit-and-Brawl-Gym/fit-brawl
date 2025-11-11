// =============================================
// Trainer Schedule Management JavaScript
// =============================================

// Modal Management
const addBlockModal = document.getElementById('addBlockModal');
const btnAddBlock = document.getElementById('btnAddBlock');
const closeModal = document.getElementById('closeModal');
const cancelModal = document.getElementById('cancelModal');
const addBlockForm = document.getElementById('addBlockForm');

btnAddBlock.addEventListener('click', () => {
    addBlockModal.classList.add('active');
    document.getElementById('date').value = new Date().toISOString().split('T')[0];
});

closeModal.addEventListener('click', () => {
    addBlockModal.classList.remove('active');
    addBlockForm.reset();
});

cancelModal.addEventListener('click', () => {
    addBlockModal.classList.remove('active');
    addBlockForm.reset();
});

// Close modal on overlay click
addBlockModal.addEventListener('click', (e) => {
    if (e.target === addBlockModal) {
        addBlockModal.classList.remove('active');
        addBlockForm.reset();
    }
});

// Add Block Form Submission
addBlockForm.addEventListener('submit', async (e) => {
    e.preventDefault();

    const formData = new FormData(addBlockForm);
    formData.append('action', 'add_block');

    const submitBtn = addBlockForm.querySelector('button[type="submit"]');
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Blocking...';

    try {
        const response = await fetch('trainer-schedules.php?ajax=1', {
            method: 'POST',
            body: formData
        });

        const result = await response.json();

        if (result.success) {
            showToast('Schedule blocked successfully', 'success');
            setTimeout(() => window.location.reload(), 1000);
        } else {
            showToast(result.message || 'Failed to block schedule', 'error');
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="fa-solid fa-ban"></i> Block Schedule';
        }
    } catch (error) {
        console.error('Error:', error);

        showToast('An error occurred', 'error');
        submitBtn.disabled = false;
        submitBtn.innerHTML = '<i class="fa-solid fa-ban"></i> Block Schedule';
    }
});

// Filters
document.getElementById('trainerFilter').addEventListener('change', applyFilters);
document.getElementById('sessionFilter').addEventListener('change', applyFilters);
document.getElementById('dateFrom').addEventListener('change', applyFilters);
document.getElementById('dateTo').addEventListener('change', applyFilters);

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

    window.location.href = 'trainer-schedules.php' + (params.toString() ? '?' + params.toString() : '');
}

// Bulk Selection
const selectAll = document.getElementById('selectAll');
const checkboxes = document.querySelectorAll('.block-checkbox');
const bulkActionsBar = document.getElementById('bulkActionsBar');
const selectedCount = document.getElementById('selectedCount');

selectAll?.addEventListener('change', (e) => {
    checkboxes.forEach(cb => cb.checked = e.target.checked);
    updateBulkActions();
});

checkboxes.forEach(cb => {
    cb.addEventListener('change', updateBulkActions);
});

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

// Bulk Delete
document.querySelector('.bulk-actions .btn-delete')?.addEventListener('click', async () => {
    const selected = Array.from(checkboxes).filter(cb => cb.checked).map(cb => cb.value);

    if (selected.length === 0) return;

    if (!confirm(`Are you sure you want to delete ${selected.length} schedule block(s)?`)) {
        return;
    }

    const formData = new FormData();
    formData.append('action', 'bulk_delete');
    formData.append('ids', JSON.stringify(selected));

    try {
        const response = await fetch('trainer-schedules.php?ajax=1', {
            method: 'POST',
            body: formData
        });

        const result = await response.json();

        if (result.success) {
            showToast('Schedule blocks deleted successfully', 'success');
            setTimeout(() => window.location.reload(), 1000);
        } else {
            showToast('Failed to delete schedule blocks', 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showToast('An error occurred', 'error');
    }
});

// Single Delete
document.querySelectorAll('.btn-delete-single').forEach(btn => {
    btn.addEventListener('click', async (e) => {
        e.stopPropagation();
        const blockId = btn.dataset.id;

        if (!confirm('Are you sure you want to remove this schedule block?')) {
            return;
        }

        const formData = new FormData();
        formData.append('action', 'delete_block');
        formData.append('block_id', blockId);

        btn.disabled = true;
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i>';

        try {
            const response = await fetch('trainer-schedules.php?ajax=1', {
                method: 'POST',
                body: formData
            });

            const result = await response.json();

            if (result.success) {
                showToast('Schedule block removed successfully', 'success');
                setTimeout(() => window.location.reload(), 1000);
            } else {
                showToast('Failed to remove schedule block', 'error');
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

// Toast Notification
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

// Add CSS animations
const style = document.createElement('style');
style.textContent = `
    @keyframes slideIn {
        from {
            transform: translateX(400px);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }

    @keyframes slideOut {
        from {
            transform: translateX(0);
            opacity: 1;
        }
        to {
            transform: translateX(400px);
            opacity: 0;
        }
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

    .bulk-actions.active {
        display: flex;
    }

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

    .bulk-actions .btn-delete:hover {
        background: #c0392b;
    }
`;
document.head.appendChild(style);
