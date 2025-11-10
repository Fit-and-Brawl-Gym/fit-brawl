// Trainer Schedule JavaScript

document.addEventListener('DOMContentLoaded', function() {
    const scheduleCalendar = document.getElementById('scheduleCalendar');
    const currentMonthDisplay = document.getElementById('currentMonthDisplay');
    const monthNavBtn = document.getElementById('monthNavBtn');
    const monthDropdown = document.getElementById('monthDropdown');
    const bookingsModal = document.getElementById('bookingsModal');
    const closeModal = document.getElementById('closeModal');
    const modalDate = document.getElementById('modalDate');

    let currentDate = new Date();
    let currentMonth = currentDate.getMonth();
    let currentYear = currentDate.getFullYear();

    const monthNames = [
        'JANUARY', 'FEBRUARY', 'MARCH', 'APRIL', 'MAY', 'JUNE',
        'JULY', 'AUGUST', 'SEPTEMBER', 'OCTOBER', 'NOVEMBER', 'DECEMBER'
    ];

    // Initialize calendar
    renderCalendar(currentMonth, currentYear);

    // Month navigation
    monthNavBtn.addEventListener('click', function(e) {
        e.stopPropagation();
        monthDropdown.classList.toggle('active');
    });

    // Close dropdown when clicking outside
    document.addEventListener('click', function() {
        monthDropdown.classList.remove('active');
    });

    // Month option click
    document.querySelectorAll('.month-option').forEach(option => {
        option.addEventListener('click', function() {
            currentMonth = parseInt(this.dataset.month);
            renderCalendar(currentMonth, currentYear);
            monthDropdown.classList.remove('active');
        });
    });

    // Close modal
    closeModal.addEventListener('click', function() {
        bookingsModal.classList.remove('active');
    });

    // Close modal when clicking outside
    bookingsModal.addEventListener('click', function(e) {
        if (e.target === bookingsModal) {
            bookingsModal.classList.remove('active');
        }
    });

    function renderCalendar(month, year) {
        scheduleCalendar.innerHTML = '';
        currentMonthDisplay.textContent = monthNames[month];

        const firstDay = new Date(year, month, 1).getDay();
        const daysInMonth = new Date(year, month + 1, 0).getDate();
        const daysInPrevMonth = new Date(year, month, 0).getDate();

        const today = new Date();
        const isCurrentMonth = today.getMonth() === month && today.getFullYear() === year;

        // Previous month days
        for (let i = firstDay - 1; i >= 0; i--) {
            const day = daysInPrevMonth - i;
            const dayElement = createDayElement(day, true, false);
            scheduleCalendar.appendChild(dayElement);
        }

        // Current month days
        for (let day = 1; day <= daysInMonth; day++) {
            const isToday = isCurrentMonth && day === today.getDate();
            const dayElement = createDayElement(day, false, isToday);

            // Add click event to show bookings
            dayElement.addEventListener('click', function() {
                showBookings(year, month, day);
            });

            scheduleCalendar.appendChild(dayElement);
        }

        // Next month days
        const totalCells = scheduleCalendar.children.length;
        const remainingCells = 42 - totalCells; // 6 rows x 7 days
        for (let day = 1; day <= remainingCells; day++) {
            const dayElement = createDayElement(day, true, false);
            scheduleCalendar.appendChild(dayElement);
        }

        // Check for bookings on each day
        checkBookingsForMonth(year, month);
    }

    function createDayElement(day, isOtherMonth, isToday) {
        const dayElement = document.createElement('div');
        dayElement.className = 'calendar-day';
        if (isOtherMonth) dayElement.classList.add('other-month');
        if (isToday) dayElement.classList.add('today');

        const dayNumber = document.createElement('span');
        dayNumber.className = 'day-number';
        dayNumber.textContent = day;

        dayElement.appendChild(dayNumber);
        return dayElement;
    }

    function checkBookingsForMonth(year, month) {
        if (!trainerId) return;

        // Fetch all reservations for this trainer for the month
        fetch(`../get_trainer_bookings.php?trainer_id=${trainerId}&year=${year}&month=${month + 1}`)
            .then(response => response.json())
            .then(data => {
                if (data.success && data.bookings) {
                    // Mark days with bookings
                    data.bookings.forEach(date => {
                        const day = new Date(date).getDate();
                        const dayElements = scheduleCalendar.querySelectorAll('.calendar-day:not(.other-month)');
                        if (dayElements[day - 1]) {
                            dayElements[day - 1].classList.add('has-bookings');
                        }
                    });
                }
            })
            .catch(error => console.error('Error fetching bookings:', error));
    }
    

    function showBookings(year, month, day) {
        const date = new Date(year, month, day);
        const formattedDate = formatDate(date);
        modalDate.textContent = formattedDate;

        // Clear previous bookings
        document.getElementById('morningBookings').innerHTML = '<p class="no-bookings">No bookings</p>';
        document.getElementById('afternoonBookings').innerHTML = '<p class="no-bookings">No bookings</p>';
        document.getElementById('eveningBookings').innerHTML = '<p class="no-bookings">No bookings</p>';

        // Fetch bookings for this date
        if (!trainerId) {
            bookingsModal.classList.add('active');
            return;
        }

        const dateStr = `${year}-${String(month + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`;

        fetch(`../get_daily_bookings.php?trainer_id=${trainerId}&date=${dateStr}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    displayBookings('morningBookings', data.morning || []);
                    displayBookings('afternoonBookings', data.afternoon || []);
                    displayBookings('eveningBookings', data.evening || []);
                }
                bookingsModal.classList.add('active');
            })
            .catch(error => {
                console.error('Error fetching daily bookings:', error);
                bookingsModal.classList.add('active');
            });
    }

    function displayBookings(containerId, bookings) {
        const container = document.getElementById(containerId);

        if (bookings.length === 0) {
            container.innerHTML = '<p class="no-bookings">No bookings</p>';
            return;
        }

        container.innerHTML = '';
        bookings.forEach(booking => {
            const bookingItem = document.createElement('div');
            bookingItem.className = 'booking-item';

            bookingItem.innerHTML = `
                <div class="booking-info">
                    <div class="booking-detail">
                        <strong>Member:</strong>
                        ${booking.member_name || 'N/A'}
                    </div>
                    <div class="booking-detail">
                        <strong>Class Type:</strong>
                        ${booking.class_type || 'N/A'}
                    </div>
                    <div class="booking-detail">
                        <strong>Time:</strong>
                        ${formatTime(booking.start_time)} - ${formatTime(booking.end_time)}
                    </div>
                    <div class="booking-detail">
                        <strong>Status:</strong>
                        ${booking.booking_status || 'confirmed'}
                    </div>
                </div>
            `;

            container.appendChild(bookingItem);
        });
    }

    function formatDate(date) {
        const days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
        const months = ['January', 'February', 'March', 'April', 'May', 'June',
                       'July', 'August', 'September', 'October', 'November', 'December'];

        return `${days[date.getDay()]}, ${months[date.getMonth()]} ${date.getDate()}, ${date.getFullYear()}`;
    }

    function formatTime(timeStr) {
        if (!timeStr) return 'N/A';

        const [hours, minutes] = timeStr.split(':');
        const hour = parseInt(hours);
        const ampm = hour >= 12 ? 'PM' : 'AM';
        const displayHour = hour % 12 || 12;

        return `${displayHour}:${minutes} ${ampm}`;
    }
});
