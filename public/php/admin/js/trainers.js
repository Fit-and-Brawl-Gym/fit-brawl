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

// Search functionality with substring matching
const searchInput = document.getElementById('searchInput');
const specializationFilter = document.getElementById('specializationFilter');
const statusFilter = document.getElementById('statusFilter');

let searchTimeout;
if (searchInput) {
    searchInput.addEventListener('input', () => {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            applyFiltersClientSide();
        }, 300); // Faster debounce for client-side
    });
}

if (specializationFilter) {
    specializationFilter.addEventListener('change', applyFiltersClientSide);
}

if (statusFilter) {
    statusFilter.addEventListener('change', applyFiltersClientSide);
}

function applyFiltersClientSide() {
    const searchTerm = searchInput ? searchInput.value.trim() : '';
    const specialization = specializationFilter ? specializationFilter.value : 'all';
    const status = statusFilter ? statusFilter.value : 'all';

    // Filter table rows
    const tableRows = document.querySelectorAll('#tableView tbody tr');
    tableRows.forEach(row => {
        const nameCell = row.querySelector('td:nth-child(1)')?.textContent || '';
        const contactCell = row.querySelector('td:nth-child(2)')?.textContent || '';
        const specializationCell = row.querySelector('td:nth-child(3) .specialization-badge')?.textContent.trim() || '';

        let matchesSearch = true;
        if (searchTerm) {
            const searchableText = `${nameCell} ${contactCell}`.toLowerCase();
            matchesSearch = searchableText.includes(searchTerm.toLowerCase());
        }

        const matchesSpecialization = specialization === 'all' || specialization === '' || specializationCell === specialization;
        const matchesStatus = status === 'all' || status === '';

        row.style.display = (matchesSearch && matchesSpecialization && matchesStatus) ? '' : 'none';
    });

    // Filter cards
    const cards = document.querySelectorAll('.trainer-card');
    cards.forEach(card => {
        const name = card.querySelector('.trainer-name')?.textContent || '';
        const email = card.querySelector('.trainer-email')?.textContent || '';
        const phone = card.querySelector('.trainer-phone')?.textContent || '';
        const specializationBadge = card.querySelector('.specialization-badge')?.textContent.trim() || '';
        const statusBadge = card.querySelector('.status-badge')?.textContent.trim() || '';

        let matchesSearch = true;
        if (searchTerm) {
            const searchableText = `${name} ${email} ${phone}`.toLowerCase();
            matchesSearch = searchableText.includes(searchTerm.toLowerCase());
        }

        const matchesSpecialization = specialization === 'all' || specialization === '' || specializationBadge === specialization;
        const matchesStatus = status === 'all' || status === '' || statusBadge === status;

        card.style.display = (matchesSearch && matchesSpecialization && matchesStatus) ? '' : 'none';
    });
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

const confirmDeleteBtn = document.getElementById('confirmDeleteBtn');
if (confirmDeleteBtn) {
    confirmDeleteBtn.addEventListener('click', () => {
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
}

const deleteModal = document.getElementById('deleteModal');
if (deleteModal) {
    deleteModal.addEventListener('click', (e) => {
        if (e.target.id === 'deleteModal' || e.target.classList.contains('modal-overlay')) {
            closeDeleteModal();
        }
    });
}

// Enhance dropdown styling on focus/blur
[
    specializationFilter,
    statusFilter
].filter(Boolean).forEach(select => {
    select.addEventListener('focus', () => {
        select.classList.add('focused');
    });
    select.addEventListener('blur', () => {
        select.classList.remove('focused');
    });
});
