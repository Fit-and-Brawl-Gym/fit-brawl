// View toggle
document.querySelectorAll('.view-btn').forEach(btn => {
    btn.addEventListener('click', () => {
        document.querySelectorAll('.view-btn').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');

        document.querySelector('.table-view').classList.toggle('active', btn.dataset.view === 'table');
        document.getElementById('calendarView').classList.toggle('active', btn.dataset.view === 'calendar');

        if (btn.dataset.view === 'calendar') initCalendar();
    });
});

// Filters with DSA Fuzzy Search
let filterTimeout;
document.getElementById('searchInput').addEventListener('input', () => {
    clearTimeout(filterTimeout);
    filterTimeout = setTimeout(applyFiltersClientSide, 300);
});

document.getElementById('statusFilter').addEventListener('change', applyFiltersClientSide);
document.getElementById('trainerFilter').addEventListener('change', applyFiltersClientSide);
document.getElementById('dateFrom').addEventListener('change', applyFiltersClientSide);
document.getElementById('dateTo').addEventListener('change', applyFiltersClientSide);

function applyFiltersClientSide() {
    const searchTerm = document.getElementById('searchInput').value.trim();
    const selectedStatus = document.getElementById('statusFilter').value;
    const selectedTrainer = document.getElementById('trainerFilter').value;
    const dateFrom = document.getElementById('dateFrom').value;
    const dateTo = document.getElementById('dateTo').value;

    // Filter table rows
    const tableRows = document.querySelectorAll('.reservations-table tbody tr');
    let visibleCount = 0;

    tableRows.forEach(row => {
        // Skip the "no results" row
        if (row.querySelector('.no-results')) return;

        // Column 2: Client info (username and email)
        const clientCell = row.querySelector('td:nth-child(2)')?.textContent || '';
        // Column 3: Trainer name
        const trainerName = row.querySelector('td:nth-child(3)')?.textContent.trim() || '';
        // Data attribute: Trainer ID
        const trainerId = row.dataset.trainerId || '';
        // Column 5: Date & Time
        const dateCell = row.querySelector('td:nth-child(5) .booking-date')?.textContent.trim() || '';
        // Column 6: Status badge
        const statusBadge = row.querySelector('.status-badge')?.dataset.status || '';

        // Search filter with substring matching
        let matchesSearch = true;
        if (searchTerm) {
            const searchableText = `${clientCell} ${trainerName}`.toLowerCase();
            matchesSearch = searchableText.includes(searchTerm.toLowerCase());
        }

        // Status filter
        const matchesStatus = selectedStatus === 'all' || selectedStatus === '' || statusBadge === selectedStatus;

        // Trainer filter - compare by trainer ID from data attribute
        const matchesTrainer = selectedTrainer === 'all' || selectedTrainer === '' || trainerId === selectedTrainer;

        // Date range filter
        let matchesDateRange = true;
        if (dateFrom || dateTo) {
            // Parse the date from format "Mon dd, yyyy"
            if (dateCell && dateCell !== 'No date set') {
                const rowDate = new Date(dateCell);
                if (dateFrom) {
                    const fromDate = new Date(dateFrom);
                    fromDate.setHours(0, 0, 0, 0);
                    rowDate.setHours(0, 0, 0, 0);
                    if (rowDate < fromDate) matchesDateRange = false;
                }
                if (dateTo) {
                    const toDate = new Date(dateTo);
                    toDate.setHours(23, 59, 59, 999);
                    if (rowDate > toDate) matchesDateRange = false;
                }
            } else {
                matchesDateRange = false;
            }
        }

        const isVisible = matchesSearch && matchesStatus && matchesTrainer && matchesDateRange;
        row.style.display = isVisible ? '' : 'none';
        if (isVisible) visibleCount++;
    });

    // Update results count if element exists
    const resultsCount = document.getElementById('resultsCount');
    if (resultsCount) {
        resultsCount.textContent = `${visibleCount} reservation${visibleCount !== 1 ? 's' : ''}`;
    }
}

// Status inline edit
document.querySelectorAll('.status-badge').forEach(badge => {
    badge.addEventListener('click', (e) => {
        e.stopPropagation();

        // Remove any existing dropdowns first
        document.querySelectorAll('.status-dropdown').forEach(d => d.remove());

        const row = badge.closest('tr');
        const bookingId = row.dataset.id;
        const currentStatus = badge.dataset.status;
        const cell = badge.closest('td');

        // Create dropdown
        const dropdown = document.createElement('div');
        dropdown.className = 'status-dropdown';

        // Add all status options
        const statuses = [
            { value: 'confirmed', label: 'Confirmed', icon: '✓' },
            { value: 'completed', label: 'Completed', icon: '✓' },
            { value: 'cancelled', label: 'Cancelled', icon: '✓' }
        ];

        statuses.forEach(status => {
            const btn = document.createElement('button');
            btn.className = 'status-dropdown-item';

            // Mark currently selected status
            if (status.value === currentStatus) {
                btn.classList.add('active');
                btn.innerHTML = `<i class="fa-solid fa-check"></i> ${status.label} <span class="current-badge">(Current)</span>`;
            } else {
                btn.innerHTML = `<i class="fa-regular fa-circle"></i> ${status.label}`;
            }

            // Only allow changing to different status
            if (status.value !== currentStatus) {
                btn.addEventListener('click', (e) => {
                    e.stopPropagation();
                    updateStatus(bookingId, status.value, badge);
                    dropdown.remove();
                });
            } else {
                btn.style.cursor = 'default';
                btn.style.opacity = '0.7';
            }

            dropdown.appendChild(btn);
        });

        // Position dropdown relative to the table cell
        cell.style.position = 'relative';
        cell.appendChild(dropdown);

        // Close dropdown when clicking outside
        setTimeout(() => {
            const closeHandler = (e) => {
                if (!dropdown.contains(e.target) && !badge.contains(e.target)) {
                    dropdown.remove();
                    document.removeEventListener('click', closeHandler);
                }
            };
            document.addEventListener('click', closeHandler);
        }, 10);
    });
});

function updateStatus(bookingId, newStatus, badge) {
    fetch('reservations.php?ajax=1', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `action=update_status&booking_id=${bookingId}&new_status=${newStatus}`
    })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                badge.dataset.status = newStatus;
                badge.textContent = ucFirst(newStatus);
                badge.className = 'status-badge status-' + newStatus;
            }
        });
}

// Bulk actions
document.getElementById('selectAll').addEventListener('change', (e) => {
    document.querySelectorAll('.booking-checkbox').forEach(cb => cb.checked = e.target.checked);
    updateBulkActionsBar();
});

document.querySelectorAll('.booking-checkbox').forEach(cb => {
    cb.addEventListener('change', updateBulkActionsBar);
});

function updateBulkActionsBar() {
    const selected = document.querySelectorAll('.booking-checkbox:checked').length;
    const bar = document.getElementById('bulkActionsBar');
    document.getElementById('selectedCount').textContent = selected + ' selected';
    bar.classList.toggle('show', selected > 0);
}

document.querySelector('.bulk-actions .btn-complete')?.addEventListener('click', () => {
    bulkUpdate('completed');
});

document.querySelector('.bulk-actions .btn-cancel')?.addEventListener('click', () => {
    bulkUpdate('cancelled');
});

function bulkUpdate(status) {
    const ids = Array.from(document.querySelectorAll('.booking-checkbox:checked')).map(cb => cb.value);
    if (!ids.length) return;

    if (!confirm(`Are you sure you want to mark ${ids.length} reservation(s) as ${status}?`)) {
        return;
    }

    const formData = new URLSearchParams();
    formData.append('action', 'bulk_update');
    formData.append('status', status);
    // Send as array format
    ids.forEach(id => formData.append('ids[]', id));

    fetch('reservations.php?ajax=1', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: formData.toString()
    })
        .then(r => r.json())
        .then(data => {
            if (data.success) location.reload();
            else alert('Failed to update reservations. Please try again.');
        })
        .catch(err => {
            console.error('Error:', err);
            alert('An error occurred. Please try again.');
        });
}

function ucFirst(str) {
    return str.charAt(0).toUpperCase() + str.slice(1);
}

// Calendar view
let currentDate = new Date();
const bookings = window.bookingsData || [];
let calendarInitialized = false;

function initCalendar() {
    renderCalendar();

    // Only add event listeners once
    if (!calendarInitialized) {
        // Navigation
        document.getElementById('prevMonth')?.addEventListener('click', () => {
            currentDate.setMonth(currentDate.getMonth() - 1);
            renderCalendar();
        });

        document.getElementById('nextMonth')?.addEventListener('click', () => {
            currentDate.setMonth(currentDate.getMonth() + 1);
            renderCalendar();
        });

        // Modal close handlers
        document.getElementById('closeDayModal')?.addEventListener('click', closeDayModal);
        document.getElementById('dayModalOverlay')?.addEventListener('click', (e) => {
            if (e.target.id === 'dayModalOverlay') closeDayModal();
        });

        // ESC key to close modal
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') closeDayModal();
        });

        calendarInitialized = true;
    }
}

function renderCalendar() {
    const year = currentDate.getFullYear();
    const month = currentDate.getMonth();

    // Update header
    const monthNames = ['January', 'February', 'March', 'April', 'May', 'June',
        'July', 'August', 'September', 'October', 'November', 'December'];
    document.getElementById('currentMonth').textContent = `${monthNames[month]} ${year}`;

    // Get first day and days in month
    const firstDay = new Date(year, month, 1).getDay();
    const daysInMonth = new Date(year, month + 1, 0).getDate();

    // Build calendar grid
    const grid = document.getElementById('calendarGrid');
    grid.innerHTML = '';

    // Add weekday headers
    const weekdays = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
    weekdays.forEach(day => {
        const header = document.createElement('div');
        header.className = 'calendar-weekday';
        header.textContent = day;
        grid.appendChild(header);
    });

    // Add empty cells for days before month starts
    for (let i = 0; i < firstDay; i++) {
        const empty = document.createElement('div');
        empty.className = 'calendar-day empty';
        empty.style.cursor = 'default';
        empty.style.opacity = '0.3';
        grid.appendChild(empty);
    }

    // Add day cells
    for (let day = 1; day <= daysInMonth; day++) {
        const dateStr = `${year}-${String(month + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
        const dayBookings = bookings.filter(b => b.date === dateStr);

        const cell = document.createElement('div');
        cell.className = 'calendar-day';
        if (dayBookings.length > 0) cell.classList.add('has-events');

        cell.innerHTML = `
            <div class="calendar-day-number">${day}</div>
            ${dayBookings.length > 0 ? `<div class="calendar-day-count">${dayBookings.length} booking${dayBookings.length > 1 ? 's' : ''}</div>` : ''}
        `;

        // Add click handler
        cell.addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation();
            console.log('Day clicked:', dateStr, day, monthNames[month], year);
            showDayBookings(dateStr, day, monthNames[month], year);
        });

        grid.appendChild(cell);
    }
}

function showDayBookings(date, day, monthName, year) {
    console.log('showDayBookings called:', date, day, monthName, year);
    console.log('Total bookings:', bookings.length);

    const dayBookings = bookings.filter(b => b.date === date);
    console.log('Bookings for this day:', dayBookings);

    const modal = document.getElementById('dayModal');
    const overlay = document.getElementById('dayModalOverlay');
    const list = document.getElementById('dayBookingsList');

    if (!overlay || !modal || !list) {
        console.error('Modal elements not found!');
        return;
    }

    // Update modal title
    const modalDateSpan = document.querySelector('#modalDate span');
    if (modalDateSpan) {
        modalDateSpan.textContent = `${monthName} ${day}, ${year}`;
    }

    // Populate bookings
    if (dayBookings.length === 0) {
        list.innerHTML = `
            <div class="day-modal-empty">
                <i class="fa-solid fa-calendar-xmark"></i>
                <p>No bookings for this day</p>
            </div>
        `;
    } else {
        list.innerHTML = dayBookings.map(booking => {
            // Format time display - prioritize start_time/end_time
            let timeDisplay = '';
            
            if (booking.start_time && booking.end_time) {
                // Time-based booking
                const startTime = new Date(booking.start_time);
                const endTime = new Date(booking.end_time);
                
                const startFormatted = startTime.toLocaleTimeString('en-US', { 
                    hour: 'numeric', 
                    minute: '2-digit', 
                    hour12: true 
                });
                const endFormatted = endTime.toLocaleTimeString('en-US', { 
                    hour: 'numeric', 
                    minute: '2-digit', 
                    hour12: true 
                });
                
                timeDisplay = `${startFormatted} - ${endFormatted}`;
            } else if (booking.session_time) {
                // Legacy session-based booking
                timeDisplay = booking.session_time;
            } else {
                timeDisplay = 'Time Not Set';
            }
            
            return `
            <div class="day-booking-item">
                <div class="day-booking-time">
                    ${timeDisplay}
                </div>
                <div class="day-booking-info">
                    <div class="day-booking-client">${escapeHtml(booking.username)}</div>
                    <div class="day-booking-trainer">
                        <i class="fa-solid fa-dumbbell"></i>
                        ${escapeHtml(booking.trainer_name)} - ${escapeHtml(booking.class_type)}
                    </div>
                </div>
                <span class="day-booking-status status-badge status-${booking.status}">
                    ${capitalize(booking.status)}
                </span>
            </div>
        `;
        }).join('');
    }

    // Show modal
    overlay.classList.add('active');
}

function closeDayModal() {
    document.getElementById('dayModalOverlay')?.classList.remove('active');
}

function formatTime(time) {
    if (!time) return 'N/A';
    const [hours, minutes] = time.split(':');
    const h = parseInt(hours);
    const period = h >= 12 ? 'PM' : 'AM';
    const hour12 = h % 12 || 12;
    return `${hour12}:${minutes} ${period}`;
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function capitalize(str) {
    if (!str) return '';
    return str.charAt(0).toUpperCase() + str.slice(1);
}
