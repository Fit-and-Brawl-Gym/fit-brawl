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

// Filters
let filterTimeout;
document.getElementById('searchInput').addEventListener('input', () => {
    clearTimeout(filterTimeout);
    filterTimeout = setTimeout(applyFilters, 500);
});

document.getElementById('statusFilter').addEventListener('change', applyFilters);
document.getElementById('trainerFilter').addEventListener('change', applyFilters);
document.getElementById('dateFrom').addEventListener('change', applyFilters);
document.getElementById('dateTo').addEventListener('change', applyFilters);

function applyFilters() {
    const params = new URLSearchParams();
    const search = document.getElementById('searchInput').value;
    const status = document.getElementById('statusFilter').value;
    const trainer = document.getElementById('trainerFilter').value;
    const dateFrom = document.getElementById('dateFrom').value;
    const dateTo = document.getElementById('dateTo').value;

    if (search) params.append('search', search);
    if (status !== 'all') params.append('status', status);
    if (trainer !== 'all') params.append('trainer', trainer);
    if (dateFrom) params.append('date_from', dateFrom);
    if (dateTo) params.append('date_to', dateTo);

    window.location.href = 'reservations.php' + (params.toString() ? '?' + params.toString() : '');
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

        // Create wrapper for positioning
        const wrapper = document.createElement('div');
        wrapper.style.position = 'relative';
        wrapper.style.display = 'inline-block';

        const dropdown = document.createElement('div');
        dropdown.className = 'status-dropdown';

        ['scheduled', 'completed', 'cancelled'].forEach(status => {
            if (status !== currentStatus) {
                const btn = document.createElement('button');
                btn.textContent = '✓ ' + ucFirst(status);
                btn.addEventListener('click', (e) => {
                    e.stopPropagation();
                    updateStatus(bookingId, status, badge);
                    dropdown.remove();
                });
                dropdown.appendChild(btn);
            }
        });

        wrapper.appendChild(dropdown);
        badge.parentElement.style.position = 'relative';
        badge.parentElement.appendChild(wrapper);

        // Close dropdown when clicking outside
        setTimeout(() => {
            document.addEventListener('click', (e) => {
                if (!dropdown.contains(e.target)) {
                    wrapper.remove();
                }
            }, { once: true });
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

    fetch('reservations.php?ajax=1', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `action=bulk_update&ids=${ids.join(',')}&status=${status}`
    })
        .then(r => r.json())
        .then(data => {
            if (data.success) location.reload();
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
        list.innerHTML = dayBookings.map(booking => `
            <div class="day-booking-item">
                <div class="day-booking-time">
                    ${formatTime(booking.start_time)} - ${formatTime(booking.end_time)}
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
        `).join('');
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
