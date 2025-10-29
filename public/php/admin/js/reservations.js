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
let bookingsData = [];

// Fetch all bookings for calendar
async function fetchBookingsForCalendar() {
    try {
        const response = await fetch('reservations.php');
        const html = await response.text();
        
        // Parse bookings from the table
        const parser = new DOMParser();
        const doc = parser.parseFromString(html, 'text/html');
        const rows = doc.querySelectorAll('.reservations-table tbody tr');
        
        bookingsData = [];
        rows.forEach(row => {
            const dateCell = row.querySelector('.time-info');
            if (dateCell) {
                const dateText = dateCell.textContent.trim();
                const lines = dateText.split('\n').map(l => l.trim()).filter(l => l);
                if (lines.length >= 2) {
                    const datePart = lines[0]; // e.g., "Oct 29, 2025"
                    const timePart = lines[1]; // e.g., "10:00 - 11:00"
                    
                    // Parse the date
                    const dateMatch = datePart.match(/(\w+)\s+(\d+),\s+(\d+)/);
                    if (dateMatch) {
                        const [_, month, day, year] = dateMatch;
                        const monthNames = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
                        const monthIndex = monthNames.indexOf(month);
                        
                        if (monthIndex !== -1) {
                            const bookingDate = new Date(year, monthIndex, day);
                            const statusBadge = row.querySelector('.status-badge');
                            const status = statusBadge ? statusBadge.dataset.status : 'scheduled';
                            
                            bookingsData.push({
                                date: bookingDate,
                                dateStr: `${year}-${String(monthIndex + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`,
                                time: timePart,
                                status: status,
                                client: row.querySelector('td:nth-child(2) h4')?.textContent || '',
                                trainer: row.querySelector('td:nth-child(3)')?.textContent || '',
                                classType: row.querySelector('td:nth-child(4)')?.textContent || ''
                            });
                        }
                    }
                }
            }
        });
    } catch (error) {
        console.error('Error fetching bookings:', error);
    }
}

function initCalendar() {
    fetchBookingsForCalendar().then(() => {
        renderCalendar();
    });
    
    // Add navigation listeners
    const navBtns = document.querySelectorAll('.calendar-nav-btn');
    navBtns[0].onclick = () => {
        currentDate.setMonth(currentDate.getMonth() - 1);
        renderCalendar();
    };
    navBtns[1].onclick = () => {
        currentDate.setMonth(currentDate.getMonth() + 1);
        renderCalendar();
    };
}

function renderCalendar() {
    const year = currentDate.getFullYear();
    const month = currentDate.getMonth();
    
    // Update header
    const monthNames = ['January', 'February', 'March', 'April', 'May', 'June',
        'July', 'August', 'September', 'October', 'November', 'December'];
    document.getElementById('currentMonth').textContent = `${monthNames[month]} ${year}`;
    
    // Get first day of month and number of days
    const firstDay = new Date(year, month, 1).getDay();
    const daysInMonth = new Date(year, month + 1, 0).getDate();
    
    // Build calendar grid
    const grid = document.getElementById('calendarGrid');
    grid.innerHTML = '';
    
    // Add weekday headers
    const weekdays = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
    weekdays.forEach(day => {
        const weekdayDiv = document.createElement('div');
        weekdayDiv.className = 'calendar-weekday';
        weekdayDiv.textContent = day;
        grid.appendChild(weekdayDiv);
    });
    
    // Add empty cells for days before month starts
    for (let i = 0; i < firstDay; i++) {
        const emptyDiv = document.createElement('div');
        emptyDiv.className = 'calendar-day empty';
        grid.appendChild(emptyDiv);
    }
    
    // Add days of month
    for (let day = 1; day <= daysInMonth; day++) {
        const dateStr = `${year}-${String(month + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
        const dayBookings = bookingsData.filter(b => b.dateStr === dateStr);
        
        const dayDiv = document.createElement('div');
        dayDiv.className = 'calendar-day';
        if (dayBookings.length > 0) {
            dayDiv.classList.add('has-events');
        }
        
        // Check if today
        const today = new Date();
        if (day === today.getDate() && month === today.getMonth() && year === today.getFullYear()) {
            dayDiv.classList.add('today');
        }
        
        dayDiv.innerHTML = `
            <div class="calendar-day-number">${day}</div>
            ${dayBookings.length > 0 ? `<div class="calendar-day-count">${dayBookings.length} booking${dayBookings.length > 1 ? 's' : ''}</div>` : ''}
        `;
        
        // Add click handler to show bookings
        if (dayBookings.length > 0) {
            dayDiv.style.cursor = 'pointer';
            dayDiv.onclick = () => showDayBookings(dateStr, dayBookings);
        }
        
        grid.appendChild(dayDiv);
    }
}

function showDayBookings(dateStr, bookings) {
    const date = new Date(dateStr);
    const monthNames = ['January', 'February', 'March', 'April', 'May', 'June',
        'July', 'August', 'September', 'October', 'November', 'December'];
    const formattedDate = `${monthNames[date.getMonth()]} ${date.getDate()}, ${date.getFullYear()}`;
    
    let html = `
        <div class="day-bookings-modal">
            <div class="day-bookings-content">
                <div class="day-bookings-header">
                    <h3>Bookings for ${formattedDate}</h3>
                    <button class="close-modal">&times;</button>
                </div>
                <div class="day-bookings-list">
    `;
    
    bookings.forEach(booking => {
        const statusClass = booking.status || 'scheduled';
        html += `
            <div class="day-booking-item">
                <div class="booking-time">${booking.time}</div>
                <div class="booking-details">
                    <div class="booking-client"><strong>${booking.client}</strong></div>
                    <div class="booking-info">Trainer: ${booking.trainer}</div>
                    <div class="booking-info">Class: ${booking.classType}</div>
                </div>
                <span class="status-badge status-${statusClass}">${ucFirst(statusClass)}</span>
            </div>
        `;
    });
    
    html += `
                </div>
            </div>
        </div>
    `;
    
    const modalDiv = document.createElement('div');
    modalDiv.innerHTML = html;
    document.body.appendChild(modalDiv.firstElementChild);
    
    // Close modal handlers
    const modal = document.querySelector('.day-bookings-modal');
    modal.querySelector('.close-modal').onclick = () => modal.remove();
    modal.onclick = (e) => {
        if (e.target === modal) modal.remove();
    };
}

