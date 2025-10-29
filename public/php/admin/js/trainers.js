// View Toggle
const viewBtns = document.querySelectorAll('.view-btn');
const tableView = document.getElementById('tableView');
const cardsView = document.getElementById('cardsView');

viewBtns.forEach(btn => {
    btn.addEventListener('click', () => {
        const view = btn.dataset.view;

        viewBtns.forEach(b => b.classList.remove('active'));
        btn.classList.add('active');

        if (view === 'table') {
            tableView.classList.add('active');
            cardsView.classList.remove('active');
        } else {
            tableView.classList.remove('active');
            cardsView.classList.add('active');
        }

        localStorage.setItem('trainersView', view);
    });
});

// Restore saved view preference
const savedView = localStorage.getItem('trainersView');
if (savedView === 'cards') {
    document.querySelector('.view-btn[data-view="cards"]').click();
}

// Search functionality
const searchInput = document.getElementById('searchInput');
const specializationFilter = document.getElementById('specializationFilter');
const statusFilter = document.getElementById('statusFilter');

let searchTimeout;
searchInput.addEventListener('input', () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        applyFilters();
    }, 500);
});

specializationFilter.addEventListener('change', applyFilters);
statusFilter.addEventListener('change', applyFilters);

function applyFilters() {
    const search = searchInput.value;
    const specialization = specializationFilter.value;
    const status = statusFilter.value;

    const params = new URLSearchParams();
    if (search) params.append('search', search);
    if (specialization !== 'all') params.append('specialization', specialization);
    if (status !== 'all') params.append('status', status);

    window.location.href = 'trainers.php' + (params.toString() ? '?' + params.toString() : '');
}

// Toggle Status
function toggleStatus(trainerId) {
    if (!confirm('Change trainer status?')) return;

    fetch('trainers.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `ajax=1&action=toggle_status&trainer_id=${trainerId}`
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Error: ' + (data.error || 'Unknown error'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred. Please try again.');
        });
}

// Delete Trainer
let trainerToDelete = null;

function deleteTrainer(trainerId, trainerName) {
    trainerToDelete = trainerId;
    document.getElementById('trainerNameToDelete').textContent = trainerName;
    document.getElementById('deleteModal').classList.add('active');
}

function closeDeleteModal() {
    trainerToDelete = null;
    document.getElementById('deleteModal').classList.remove('active');
}

document.getElementById('confirmDeleteBtn').addEventListener('click', () => {
    if (!trainerToDelete) return;

    fetch('trainers.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `ajax=1&action=delete_trainer&trainer_id=${trainerToDelete}`
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Error: ' + (data.error || 'Unknown error'));
                closeDeleteModal();
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred. Please try again.');
            closeDeleteModal();
        });
});

// Close modal when clicking outside
document.getElementById('deleteModal').addEventListener('click', (e) => {
    if (e.target.id === 'deleteModal' || e.target.classList.contains('modal-overlay')) {
        closeDeleteModal();
    }
});

// Enhance dropdown styling on focus/blur
[specializationFilter, statusFilter].forEach(select => {
    select.addEventListener('focus', () => {
        select.classList.add('focused');
    });
    select.addEventListener('blur', () => {
        select.classList.remove('focused');
    });
});
