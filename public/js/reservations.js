document.addEventListener('DOMContentLoaded', function() {
    // Initialize calendar
    let currentDate = new Date(2025, 8, 1); // September 2025
    let selectedDate = null;
    let currentClassFilter = 'all';
    let currentCoachFilter = 'all';
    let sessionsData = {}; // Store sessions by day

    // Calendar elements
    const calendarGrid = document.getElementById('calendarGrid');
    const scheduleCalendar = document.getElementById('scheduleCalendar');
    const monthDisplay = document.getElementById('monthDisplay');
    const monthName = document.getElementById('monthName');
    const yearDisplay = document.getElementById('yearDisplay');
    const prevMonthBtn = document.getElementById('prevMonth');
    const nextMonthBtn = document.getElementById('nextMonth');

    // Filter elements
    const filterBtns = document.querySelectorAll('.filter-btn');
    const coachSelect = document.getElementById('coachSelect');

    // Schedule details
    const scheduleDetails = document.getElementById('scheduleDetails');
    const closeDetailsBtn = document.getElementById('closeDetails');
    const scheduleTrainingBtn = document.getElementById('scheduleTrainingBtn');

    // Month navigation in schedule header
    const monthNavBtn = document.querySelector('#monthNavBtn');
    const monthDropdown = document.getElementById('monthDropdown');
    const currentMonthDisplay = document.getElementById('currentMonthDisplay');
    const monthOptions = document.querySelectorAll('.month-option');

    // Coach options for each class type
    const classCoachOptions = {
        'muay-thai': [
            { value: 'coach-carlo', text: 'Coach Carlo' }
        ],
        'boxing': [
            { value: 'coach-rieze', text: 'Coach Rieze' }
        ],
        'mma': [
            { value: 'coach-thei', text: 'Coach Thei' }
        ],
        'all': [
            { value: 'all', text: 'All Coaches' },
            { value: 'coach-carlo', text: 'Coach Carlo' },
            { value: 'coach-rieze', text: 'Coach Rieze' },
            { value: 'coach-thei', text: 'Coach Thei' }
        ]
    };

    // Coach mapping for each class type (default selection)
    const classCoachMapping = {
        'muay-thai': 'coach-carlo',
        'boxing': 'coach-rieze',
        'mma': 'coach-thei',
        'all': 'all'
    };

    // Fetch reservations from server
    async function fetchReservations() {
        const year = currentDate.getFullYear();
        const month = currentDate.getMonth() + 1;

        try {
            const response = await fetch(`api/get_reservations.php?year=${year}&month=${month}&class=${currentClassFilter}&coach=${currentCoachFilter}`);
            const data = await response.json();

            if (data.success) {
                sessionsData = data.reservations;
                renderSmallCalendar();
                renderLargeCalendar();
            }
        } catch (error) {
            console.error('Error fetching reservations:', error);
        }
    }

    // Fetch user bookings
    async function fetchUserBookings() {
        try {
            const response = await fetch('api/get_user_bookings.php');
            const data = await response.json();

            if (data.success) {
                updateBookingsList(data.bookings);
                updateStats(data.bookings);
            }
        } catch (error) {
            console.error('Error fetching bookings:', error);
        }
    }

    // Update bookings list
    function updateBookingsList(bookings) {
        const sessionsList = document.getElementById('sessionsList');

        if (bookings.length === 0) {
            sessionsList.innerHTML = '<p style="color: var(--color-text-muted); text-align: center; padding: var(--spacing-4);">No booked sessions</p>';
            return;
        }

        sessionsList.innerHTML = bookings.map(booking => {
            const datetime = new Date(booking.datetime);
            const dayName = datetime.toLocaleDateString('en-US', { weekday: 'short' });
            const day = datetime.getDate();
            const month = datetime.toLocaleDateString('en-US', { month: 'short' });
            const statusClass = booking.status.toLowerCase().replace(' ', '-');

            return `
                <div class="session-card">
                    <div class="session-info">
                        <div class="session-class">${booking.class_type}</div>
                        <div class="session-date">${month} ${day} (${dayName})</div>
                        <div class="session-time">${booking.time}</div>
                        <div class="session-status status-${statusClass}">
                            <i class="fas fa-${booking.status === 'Confirmed' ? 'check' : 'times'}-circle"></i> ${booking.status}
                        </div>
                    </div>
                </div>
            `;
        }).join('');
    }

    // Update stats
    function updateStats(bookings) {
        const currentMonth = new Date().getMonth();
        const sessionsThisMonth = bookings.filter(b => {
            const bookingDate = new Date(b.datetime);
            return bookingDate.getMonth() === currentMonth && b.status === 'Completed';
        }).length;

        document.getElementById('sessionsAttended').textContent = sessionsThisMonth;

        // Find next upcoming session
        const upcoming = bookings.find(b => new Date(b.datetime) > new Date() && b.status === 'Confirmed');
        if (upcoming) {
            const upcomingDate = new Date(upcoming.datetime);
            document.getElementById('upcomingClass').textContent = upcoming.class_type;
            document.getElementById('upcomingDate').textContent = upcomingDate.toLocaleDateString('en-US', {
                weekday: 'short',
                month: 'short',
                day: 'numeric',
                year: 'numeric'
            });
            document.getElementById('upcomingTrainer').textContent = upcoming.trainer_name;
            document.getElementById('trainerSubtext').textContent = `Next class with ${upcoming.trainer_name}`;
        }
    }

    // Update coach dropdown options based on class type
    function updateCoachDropdown(classType) {
        const options = classCoachOptions[classType];
        coachSelect.innerHTML = '';

        options.forEach(option => {
            const optionElement = document.createElement('option');
            optionElement.value = option.value;
            optionElement.textContent = option.text;
            coachSelect.appendChild(optionElement);
        });

        // Set default coach for this class type
        coachSelect.value = classCoachMapping[classType];
        currentCoachFilter = classCoachMapping[classType];
    }

    // Update month navigation text in schedule header
    function updateMonthNavText() {
        const monthNames = ['JANUARY', 'FEBRUARY', 'MARCH', 'APRIL', 'MAY', 'JUNE',
                          'JULY', 'AUGUST', 'SEPTEMBER', 'OCTOBER', 'NOVEMBER', 'DECEMBER'];
        if (currentMonthDisplay) {
            currentMonthDisplay.textContent = monthNames[currentDate.getMonth()];
        }

        // Update active state in dropdown
        monthOptions.forEach(option => {
            option.classList.remove('current');
            if (parseInt(option.dataset.month) === currentDate.getMonth()) {
                option.classList.add('current');
            }
        });
    }

    // Render small calendar (left sidebar)
    function renderSmallCalendar() {
        const year = currentDate.getFullYear();
        const month = currentDate.getMonth();

        // Update month display
        monthDisplay.textContent = String(month + 1).padStart(2, '0');
        const monthNames = ['JAN', 'FEB', 'MAR', 'APR', 'MAY', 'JUN', 'JUL', 'AUG', 'SEP', 'OCT', 'NOV', 'DEC'];
        monthName.textContent = monthNames[month];
        yearDisplay.textContent = year;

        // Update month nav text
        updateMonthNavText();

        // Clear calendar
        calendarGrid.innerHTML = '';

        // Add day headers
        const dayHeaders = ['S', 'M', 'T', 'W', 'T', 'F', 'S'];
        dayHeaders.forEach(day => {
            const header = document.createElement('div');
            header.className = 'calendar-day-header';
            header.textContent = day;
            calendarGrid.appendChild(header);
        });

        // Get first day of month and total days
        const firstDay = new Date(year, month, 1).getDay();
        const daysInMonth = new Date(year, month + 1, 0).getDate();
        const daysInPrevMonth = new Date(year, month, 0).getDate();

        // Previous month days
        for (let i = firstDay - 1; i >= 0; i--) {
            const day = document.createElement('div');
            day.className = 'calendar-day inactive';
            day.textContent = daysInPrevMonth - i;
            calendarGrid.appendChild(day);
        }

        // Current month days
        for (let i = 1; i <= daysInMonth; i++) {
            const day = document.createElement('div');
            day.className = 'calendar-day';
            day.textContent = i;

            // Check if this day has sessions
            const hasSession = sessionsData[i] && sessionsData[i].length > 0;
            if (hasSession) {
                day.classList.add('has-session');
            }

            // Selected state
            if (selectedDate && selectedDate.getDate() === i &&
                selectedDate.getMonth() === month &&
                selectedDate.getFullYear() === year) {
                day.classList.add('selected');
            }

            day.addEventListener('click', () => selectDate(new Date(year, month, i)));
            calendarGrid.appendChild(day);
        }

        // Next month days to fill grid
        const totalCells = calendarGrid.children.length - 7;
        const remainingCells = 35 - totalCells;
        for (let i = 1; i <= remainingCells; i++) {
            const day = document.createElement('div');
            day.className = 'calendar-day inactive';
            day.textContent = i;
            calendarGrid.appendChild(day);
        }
    }

    // Render large calendar (monthly schedule)
    function renderLargeCalendar() {
        const year = currentDate.getFullYear();
        const month = currentDate.getMonth();

        scheduleCalendar.innerHTML = '';

        const firstDay = new Date(year, month, 1).getDay();
        const daysInMonth = new Date(year, month + 1, 0).getDate();
        const daysInPrevMonth = new Date(year, month, 0).getDate();

        // Previous month days
        for (let i = firstDay - 1; i >= 0; i--) {
            const day = document.createElement('div');
            day.className = 'schedule-day inactive';
            const dayNum = document.createElement('div');
            dayNum.className = 'day-number';
            dayNum.textContent = daysInPrevMonth - i;
            day.appendChild(dayNum);
            scheduleCalendar.appendChild(day);
        }

        // Current month days
        for (let i = 1; i <= daysInMonth; i++) {
            const day = document.createElement('div');
            day.className = 'schedule-day';

            const dayNum = document.createElement('div');
            dayNum.className = 'day-number';
            dayNum.textContent = i;
            day.appendChild(dayNum);

            // Check for sessions on this day
            const daySessions = sessionsData[i];
            if (daySessions && daySessions.length > 0) {
                const indicator = document.createElement('div');
                indicator.className = 'day-indicator';

                daySessions.forEach((session, index) => {
                    if (index < 3) {
                        const dot = document.createElement('div');
                        dot.className = 'indicator-dot';
                        if (session.slots <= 2) dot.classList.add('warning');
                        indicator.appendChild(dot);
                    }
                });

                day.appendChild(indicator);
                day.addEventListener('click', () => showScheduleDetails(i, daySessions));
            }

            scheduleCalendar.appendChild(day);
        }

        // Fill remaining cells
        const totalCells = scheduleCalendar.children.length;
        const remainingCells = 35 - totalCells;
        for (let i = 1; i <= remainingCells; i++) {
            const day = document.createElement('div');
            day.className = 'schedule-day inactive';
            const dayNum = document.createElement('div');
            dayNum.className = 'day-number';
            dayNum.textContent = i;
            day.appendChild(dayNum);
            scheduleCalendar.appendChild(day);
        }
    }

    // Select date function
    function selectDate(date) {
        selectedDate = date;
        renderSmallCalendar();
    }

    // Show schedule details
    function showScheduleDetails(day, daySessions) {
        const session = daySessions[0]; // Show first session

        document.getElementById('detailClass').textContent = session.class;
        document.getElementById('detailTrainer').textContent = session.trainer;

        const monthNames = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        const dayNames = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
        const date = new Date(currentDate.getFullYear(), currentDate.getMonth(), day);
        const dateStr = `${dayNames[date.getDay()]}, ${monthNames[date.getMonth()]} ${day}, ${date.getFullYear()}; ${session.time}`;
        document.getElementById('detailDateTime').textContent = dateStr;

        document.getElementById('detailSlots').textContent = session.slots;
        document.getElementById('detailMaxSlots').textContent = session.max_slots;
        document.getElementById('detailSlots').style.color = session.slots <= 2 ? 'var(--color-warning)' : 'var(--color-success)';

        scheduleTrainingBtn.setAttribute('data-reservation-id', session.id);

        scheduleDetails.style.display = 'block';
    }

    // Close schedule details
    function closeScheduleDetails() {
        scheduleDetails.style.display = 'none';
    }

    // Book session
    async function bookSession(reservationId) {
        try {
            const formData = new FormData();
            formData.append('reservation_id', reservationId);

            const response = await fetch('api/book_session.php', {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            if (data.success) {
                alert('Training session scheduled! You will receive a confirmation email.');
                closeScheduleDetails();
                fetchReservations();
                fetchUserBookings();
            } else {
                alert('Error: ' + data.message);
            }
        } catch (error) {
            console.error('Error booking session:', error);
            alert('An error occurred. Please try again.');
        }
    }

    // Class filter functionality
    filterBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            filterBtns.forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            currentClassFilter = this.dataset.class;

            updateCoachDropdown(currentClassFilter);
            fetchReservations();
        });
    });

    // Coach select change
    coachSelect.addEventListener('change', function() {
        currentCoachFilter = this.value;
        fetchReservations();
    });

    // Month navigation
    prevMonthBtn.addEventListener('click', function() {
        currentDate = new Date(currentDate.getFullYear(), currentDate.getMonth() - 1, 1);
        fetchReservations();
    });

    nextMonthBtn.addEventListener('click', function() {
        currentDate = new Date(currentDate.getFullYear(), currentDate.getMonth() + 1, 1);
        fetchReservations();
    });

    // Toggle month dropdown
    if (monthNavBtn) {
        monthNavBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            monthDropdown.classList.toggle('active');
        });
    }

    // Handle month selection
    monthOptions.forEach(option => {
        option.addEventListener('click', function(e) {
            e.stopPropagation();
            const selectedMonth = parseInt(this.dataset.month);
            currentDate = new Date(currentDate.getFullYear(), selectedMonth, 1);
            monthDropdown.classList.remove('active');
            fetchReservations();
        });
    });

    // Close dropdown when clicking outside
    document.addEventListener('click', function(e) {
        if (monthDropdown && !monthNavBtn.contains(e.target)) {
            monthDropdown.classList.remove('active');
        }
    });

    // Close details button
    closeDetailsBtn.addEventListener('click', closeScheduleDetails);

    // Schedule training button
    scheduleTrainingBtn.addEventListener('click', function() {
        const reservationId = this.getAttribute('data-reservation-id');
        if (reservationId) {
            bookSession(reservationId);
        }
    });

    // Initialize
    updateCoachDropdown('all');
    fetchReservations();
    fetchUserBookings();
});
