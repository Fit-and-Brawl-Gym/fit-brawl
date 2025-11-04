// Trainer Schedules Management
document.addEventListener('DOMContentLoaded', function () {
    const forms = document.querySelectorAll('.schedule-form');
    const toast = document.getElementById('successToast');
    const searchInput = document.getElementById('searchTrainer');
    const filterStatus = document.getElementById('filterStatus');
    const filterSpecialization = document.getElementById('filterSpecialization');
    const sortBy = document.getElementById('sortBy');

    // View Toggle Functionality
    const toggleButtons = document.querySelectorAll('.toggle-btn');
    toggleButtons.forEach(btn => {
        btn.addEventListener('click', function () {
            const view = this.dataset.view;

            // Update active button
            toggleButtons.forEach(b => b.classList.remove('active'));
            this.classList.add('active');

            // Show/hide views
            document.querySelectorAll('.view-container').forEach(container => {
                container.classList.remove('active');
            });
            document.getElementById(view + 'View').classList.add('active');
        });
    });

    // Search Functionality
    if (searchInput) {
        searchInput.addEventListener('input', filterTrainers);
    }

    // Filter Functionality
    if (filterStatus) {
        filterStatus.addEventListener('change', filterTrainers);
    }
    if (filterSpecialization) {
        filterSpecialization.addEventListener('change', filterTrainers);
    }
    if (sortBy) {
        sortBy.addEventListener('change', sortTrainers);
    }

    function filterTrainers() {
        const searchTerm = searchInput ? searchInput.value.toLowerCase() : '';
        const statusFilter = filterStatus ? filterStatus.value : '';
        const specFilter = filterSpecialization ? filterSpecialization.value : '';

        const cards = document.querySelectorAll('.trainer-schedule-card');
        const rows = document.querySelectorAll('.trainer-row');

        // Filter cards
        cards.forEach(card => {
            const name = card.dataset.trainerName?.toLowerCase() || '';
            const status = card.dataset.status || '';
            const spec = card.dataset.specialization || '';

            const matchesSearch = name.includes(searchTerm);
            const matchesStatus = !statusFilter || status === statusFilter;
            const matchesSpec = !specFilter || spec === specFilter;

            if (matchesSearch && matchesStatus && matchesSpec) {
                card.style.display = '';
            } else {
                card.style.display = 'none';
            }
        });

        // Filter table rows
        rows.forEach(row => {
            const name = row.dataset.trainerName?.toLowerCase() || '';
            const status = row.dataset.status || '';
            const spec = row.dataset.specialization || '';

            const matchesSearch = name.includes(searchTerm);
            const matchesStatus = !statusFilter || status === statusFilter;
            const matchesSpec = !specFilter || spec === specFilter;

            if (matchesSearch && matchesStatus && matchesSpec) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    }

    function sortTrainers() {
        const sortValue = sortBy.value;
        const cardsContainer = document.querySelector('#cardsView');
        const cards = Array.from(document.querySelectorAll('.trainer-schedule-card'));

        cards.sort((a, b) => {
            if (sortValue === 'name') {
                return a.dataset.trainerName.localeCompare(b.dataset.trainerName);
            } else if (sortValue === 'days_off') {
                return parseInt(a.dataset.daysOff) - parseInt(b.dataset.daysOff);
            } else if (sortValue === 'status') {
                return a.dataset.status.localeCompare(b.dataset.status);
            }
            return 0;
        });

        cards.forEach(card => cardsContainer.appendChild(card));
    }

    // Handle checkbox visual state and validation
    document.querySelectorAll('.day-checkbox').forEach(label => {
        const checkbox = label.querySelector('input[type="checkbox"]');

        checkbox.addEventListener('change', function () {
            if (this.checked) {
                label.classList.add('checked');
            } else {
                label.classList.remove('checked');
            }

            // Update validation counter and button state
            const form = label.closest('form') || label.closest('.modal-content');
            if (form) {
                updateValidation(form);
            }
        });
    });

    // Validation Function - Enforce exactly 2 days off
    function updateValidation(form) {
        const checkboxes = form.querySelectorAll('input[type="checkbox"]:checked');
        const count = checkboxes.length;
        const counter = form.querySelector('.validation-counter');
        const countDisplay = counter?.querySelector('.days-selected');
        const submitBtn = form.querySelector('.btn-save');

        if (countDisplay) {
            countDisplay.textContent = count;
        }

        if (counter) {
            if (count === 2) {
                counter.classList.add('valid');
                if (submitBtn) submitBtn.disabled = false;
            } else {
                counter.classList.remove('valid');
                if (submitBtn) submitBtn.disabled = true;
            }
        }

        // Update card compliance badge
        const card = form.closest('.trainer-schedule-card');
        if (card) {
            const dayOffCount = card.querySelector('.day-off-count');
            const complianceBadge = card.querySelector('.compliance-badge');

            if (dayOffCount) {
                if (count === 2) {
                    dayOffCount.classList.remove('warning');
                    dayOffCount.classList.add('success');
                } else {
                    dayOffCount.classList.remove('success');
                    dayOffCount.classList.add('warning');
                }
                dayOffCount.querySelector('span').textContent = `${count} day${count !== 1 ? 's' : ''} off`;
            }

            if (complianceBadge) {
                if (count === 2) {
                    complianceBadge.style.display = 'none';
                } else {
                    complianceBadge.style.display = 'inline-flex';
                    complianceBadge.innerHTML = count < 2
                        ? '<i class="fas fa-exclamation-triangle"></i> Needs more days off'
                        : '<i class="fas fa-exclamation-triangle"></i> Too many days off';
                }
            }

            // Update card border
            if (count !== 2) {
                card.classList.add('non-compliant');
            } else {
                card.classList.remove('non-compliant');
            }
        }
    }

    // Handle form submissions
    forms.forEach(form => {
        const resetBtn = form.querySelector('.btn-reset');

        // Initialize validation on load
        updateValidation(form);

        form.addEventListener('submit', async function (e) {
            e.preventDefault();

            const checkedCount = this.querySelectorAll('input[type="checkbox"]:checked').length;

            // Validate exactly 2 days off
            if (checkedCount !== 2) {
                showToast('Please select exactly 2 days off', 'error');
                return;
            }

            const trainerId = this.dataset.trainerId;
            const formData = new FormData(this);
            formData.append('ajax', '1');
            formData.append('action', 'update_schedule');
            formData.append('trainer_id', trainerId);

            const submitBtn = this.querySelector('.btn-save');
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
            submitBtn.disabled = true;

            try {
                const response = await fetch('trainer_schedules.php', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    showToast('Schedule updated successfully!', 'success');

                    // Update table view if visible
                    updateTableView(trainerId, formData.getAll('day_offs[]'));
                } else {
                    showToast('Error: ' + (result.error || 'Failed to update schedule'), 'error');
                }
            } catch (error) {
                console.error('Error:', error);
                showToast('Network error. Please try again.', 'error');
            } finally {
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            }
        });

        // Handle reset button
        if (resetBtn) {
            resetBtn.addEventListener('click', function () {
                const form = this.closest('form');
                const checkboxes = form.querySelectorAll('input[type="checkbox"]');

                checkboxes.forEach(checkbox => {
                    checkbox.checked = false;
                    checkbox.closest('.day-checkbox').classList.remove('checked');
                });

                updateValidation(form);
            });
        }
    });

    // Table View: Edit Schedule Modal
    const editButtons = document.querySelectorAll('.btn-edit-schedule');
    const editModal = document.getElementById('editScheduleModal');
    const modalForm = document.getElementById('modalScheduleForm');

    editButtons.forEach(btn => {
        btn.addEventListener('click', function () {
            const trainerId = this.dataset.trainerId;
            const trainerName = this.dataset.trainerName;

            // Find the corresponding row
            const row = this.closest('tr');
            const dayCells = row.querySelectorAll('.day-cell');

            // Set modal title
            document.getElementById('modalTrainerName').textContent = trainerName;
            document.getElementById('modalTrainerId').value = trainerId;

            // Clear and set checkboxes based on current schedule
            const modalCheckboxes = modalForm.querySelectorAll('input[type="checkbox"]');
            const days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];

            modalCheckboxes.forEach((checkbox, index) => {
                const dayCell = dayCells[index];
                const isDayOff = dayCell.querySelector('.day-status').classList.contains('off');

                checkbox.checked = isDayOff;
                const label = checkbox.closest('.day-checkbox');
                if (isDayOff) {
                    label.classList.add('checked');
                } else {
                    label.classList.remove('checked');
                }
            });

            // Initialize validation
            updateValidation(modalForm);

            // Show modal
            editModal.classList.add('show');
        });
    });

    // Modal form submission
    if (modalForm) {
        modalForm.addEventListener('submit', async function (e) {
            e.preventDefault();

            const checkedCount = this.querySelectorAll('input[type="checkbox"]:checked').length;

            if (checkedCount !== 2) {
                showToast('Please select exactly 2 days off', 'error');
                return;
            }

            const trainerId = document.getElementById('modalTrainerId').value;
            const formData = new FormData(this);
            formData.append('ajax', '1');
            formData.append('action', 'update_schedule');
            formData.append('trainer_id', trainerId);

            const submitBtn = this.querySelector('.btn-save');
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
            submitBtn.disabled = true;

            try {
                const response = await fetch('trainer_schedules.php', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    showToast('Schedule updated successfully!', 'success');
                    closeEditModal();

                    // Reload page to update all views
                    setTimeout(() => location.reload(), 1000);
                } else {
                    showToast('Error: ' + (result.error || 'Failed to update schedule'), 'error');
                }
            } catch (error) {
                console.error('Error:', error);
                showToast('Network error. Please try again.', 'error');
            } finally {
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            }
        });
    }

    function updateTableView(trainerId, dayOffs) {
        const row = document.querySelector(`.btn-edit-schedule[data-trainer-id="${trainerId}"]`)?.closest('tr');
        if (!row) return;

        const days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
        const dayCells = row.querySelectorAll('.day-cell');

        dayCells.forEach((cell, index) => {
            const dayStatus = cell.querySelector('.day-status');
            const day = days[index];
            const isOff = dayOffs.includes(day);

            if (isOff) {
                dayStatus.classList.remove('working');
                dayStatus.classList.add('off');
                dayStatus.innerHTML = '<i class="fas fa-times-circle"></i>';
            } else {
                dayStatus.classList.remove('off');
                dayStatus.classList.add('working');
                dayStatus.innerHTML = '<i class="fas fa-check-circle"></i>';
            }
        });

        // Update compliance badge
        const complianceBadge = row.querySelector('.compliance-badge');
        if (dayOffs.length === 2) {
            complianceBadge.classList.remove('warning');
            complianceBadge.classList.add('success');
            complianceBadge.innerHTML = '<i class="fas fa-check-circle"></i> Compliant';
            row.classList.remove('non-compliant');
        } else {
            complianceBadge.classList.remove('success');
            complianceBadge.classList.add('warning');
            complianceBadge.innerHTML = `<i class="fas fa-exclamation-triangle"></i> ${dayOffs.length}/2`;
            row.classList.add('non-compliant');
        }
    }

    // Show toast notification
    function showToast(message, type = 'success') {
        if (!toast) return;

        const toastMessage = document.getElementById('toastMessage');
        const toastIcon = toast.querySelector('i');

        toastMessage.textContent = message;

        if (type === 'success') {
            // Green gradient for success
            toast.style.background = 'linear-gradient(135deg, #48c774 0%, #38a169 100%)';
            toastIcon.className = 'fas fa-check-circle';
        } else {
            // Red gradient for error
            toast.style.background = 'linear-gradient(135deg, #f56565 0%, #e53e3e 100%)';
            toastIcon.className = 'fas fa-exclamation-circle';
        }

        toast.classList.add('show');

        setTimeout(() => {
            toast.classList.remove('show');
        }, 3000);
    }
});

// Close modal function (global)
function closeEditModal() {
    const modal = document.getElementById('editScheduleModal');
    if (modal) {
        modal.classList.remove('show');
    }
}

// Close modal on outside click
document.addEventListener('click', function (e) {
    const modal = document.getElementById('editScheduleModal');
    if (e.target === modal) {
        closeEditModal();
    }
});

// Close modal on Escape key
document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') {
        closeEditModal();
        closeTrainerTooltip();
    }
});

// Show all trainers tooltip
function showAllTrainers(element) {
    // Close any existing tooltip
    closeTrainerTooltip();

    const allTrainersData = element.dataset.allTrainers;
    const day = element.dataset.day;
    const total = element.dataset.total;
    const trainers = allTrainersData.split('|');

    // Create tooltip
    const tooltip = document.createElement('div');
    tooltip.className = 'trainer-tooltip';
    tooltip.id = 'trainerTooltip';

    // Create header
    const header = document.createElement('div');
    header.className = 'trainer-tooltip-header';
    header.innerHTML = `
        <h4><i class="fas fa-users"></i> ${day} - ${total} Available Trainers</h4>
        <button class="tooltip-close" onclick="closeTrainerTooltip()">
            <i class="fas fa-times"></i>
        </button>
    `;

    // Create list
    const list = document.createElement('div');
    list.className = 'trainer-tooltip-list';
    trainers.forEach(trainer => {
        const item = document.createElement('div');
        item.className = 'trainer-tooltip-item';
        item.innerHTML = `<i class="fas fa-check-circle"></i> ${trainer}`;
        list.appendChild(item);
    });

    tooltip.appendChild(header);
    tooltip.appendChild(list);
    document.body.appendChild(tooltip);

    // Position tooltip near the clicked element
    const rect = element.getBoundingClientRect();
    const tooltipRect = tooltip.getBoundingClientRect();

    // Calculate position (try to center below the element)
    let left = rect.left + (rect.width / 2) - (tooltipRect.width / 2);
    let top = rect.bottom + 10;

    // Adjust if tooltip goes off screen
    if (left + tooltipRect.width > window.innerWidth) {
        left = window.innerWidth - tooltipRect.width - 10;
    }
    if (left < 10) {
        left = 10;
    }
    if (top + tooltipRect.height > window.innerHeight) {
        top = rect.top - tooltipRect.height - 10;
    }

    tooltip.style.left = left + 'px';
    tooltip.style.top = top + 'px';

    // Close on outside click
    setTimeout(() => {
        document.addEventListener('click', closeTooltipOutside);
    }, 100);
}

function closeTrainerTooltip() {
    const tooltip = document.getElementById('trainerTooltip');
    if (tooltip) {
        tooltip.remove();
        document.removeEventListener('click', closeTooltipOutside);
    }
}

function closeTooltipOutside(e) {
    const tooltip = document.getElementById('trainerTooltip');
    if (tooltip && !tooltip.contains(e.target) && !e.target.classList.contains('more')) {
        closeTrainerTooltip();
    }
}