document.addEventListener('DOMContentLoaded', function () {
    // Initialize calendar


    let currentDate = new Date(2025, 8, 1); // September 2025
    let selectedDate = null;
    let currentClassFilter = 'all';
    let currentCoachFilter = 'all';
    let currentSessionFilter = 'all';
    let sessionsData = {}; // Store sessions by day
    let trainersData = {};

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
    const sessionSelect = document.getElementById('sessionSelect');
    // Session picker event
    if (sessionSelect) {
        sessionSelect.addEventListener('change', function () {
            currentSessionFilter = sessionSelect.value;
            fetchReservations();
        });
    }

    // Schedule details
    const scheduleDetails = document.getElementById('scheduleDetails');
    const closeDetailsBtn = document.getElementById('closeDetails');
    const scheduleTrainingBtn = document.getElementById('scheduleTrainingBtn');

    // Month navigation in schedule header
    const monthNavBtn = document.querySelector('#monthNavBtn');
    const monthDropdown = document.getElementById('monthDropdown');
    const currentMonthDisplay = document.getElementById('currentMonthDisplay');
    const monthOptions = document.querySelectorAll('.month-option');

    async function fetchTrainers(classType = "all") {
        try {


            const response = await fetch("api/get_trainers.php");
            const data = await response.json();

            if (data.success) {
                trainersData = data.trainers;
                updateCoachDropdown(classType);
                checkSingleClassType();
                fetchReservations();
            } else {
                console.error('Failed to load trainers:', data.message);
            }
        } catch (err) {
            console.error('Error fetching trainers:', err);
        }
    }

    function checkSingleClassType() {
        const classTypes = Object.keys(trainersData);
        const filterContainer = document.querySelector('.class-filter');
        if (!filterContainer) return;


        filterContainer.style.display = classTypes.length <= 1 ? 'none' : 'flex';
    }

    function updateCoachDropdown(classType = "all") {
        if (!trainersData || typeof trainersData !== "object") {
            console.warn("No trainers data found");
            return;
        }

        const dropdown = document.getElementById("coachSelect");
        if (!dropdown) return;

        dropdown.innerHTML = "";

        const key = classType ? classType.toLowerCase().replace(/\s+/g, "-") : "all";
        const trainers = trainersData[key] || [];

        trainers.forEach(trainer => {
            const opt = document.createElement("option");
            opt.value = trainer.id;
            opt.textContent = trainer.name;
            dropdown.appendChild(opt);
        });

    }



    // Fetch reservations from server
    async function fetchReservations() {
        const year = currentDate.getFullYear();
        const month = currentDate.getMonth() + 1;

        try {
            const response = await fetch(`api/get_reservations.php?year=${year}&month=${month}&class=${currentClassFilter}&coach=${currentCoachFilter}&session=${currentSessionFilter}`);
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
        // Get allowed class types from filter buttons
        const allowedClassTypes = Array.from(document.querySelectorAll('.filter-btn')).map(btn => btn.dataset.class);
        for (let i = 1; i <= daysInMonth; i++) {
            const day = document.createElement('div');
            day.className = 'calendar-day';
            day.textContent = i;

            // Check if this day has sessions
            const hasSession = sessionsData[i] && sessionsData[i].length > 0;
            let onlyAllowed = true;
            if (hasSession) {
                // If any session for this day is not in allowedClassTypes, mark as unavailable
                onlyAllowed = sessionsData[i].every(session => allowedClassTypes.includes(session.class_slug));
                day.classList.add('has-session');
            }

            // If not allowed, disable day
            if (!onlyAllowed) {
                day.classList.add('inactive');
                day.title = 'Not available for your plan';
            } else {
                // Selected state
                if (selectedDate && selectedDate.getDate() === i &&
                    selectedDate.getMonth() === month &&
                    selectedDate.getFullYear() === year) {
                    day.classList.add('selected');
                }
                day.addEventListener('click', () => selectDate(new Date(year, month, i)));
            }
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
        const allowedClassTypes = Array.from(document.querySelectorAll('.filter-btn')).map(btn => btn.dataset.class);
        for (let i = 1; i <= daysInMonth; i++) {
            const day = document.createElement('div');
            day.className = 'schedule-day';

            const dayNum = document.createElement('div');
            dayNum.className = 'day-number';
            dayNum.textContent = i;
            day.appendChild(dayNum);

            // Check for sessions on this day
            const daySessions = sessionsData[i];
            let onlyAllowed = true;
            if (daySessions && daySessions.length > 0) {
                onlyAllowed = daySessions.every(session => allowedClassTypes.includes(session.class_slug));
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
            }

            if (!onlyAllowed) {
                day.classList.add('inactive');
                day.title = 'Not available for your plan';
            } else if (daySessions && daySessions.length > 0) {
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
                alert('Training session booked and registered successfully!');
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
        btn.addEventListener('click', async function () {
            filterBtns.forEach(b => b.classList.remove('active'));
            this.classList.add('active');

            currentClassFilter = this.dataset.class;
            console.log('Selected class:', currentClassFilter);

            await fetchTrainers(currentClassFilter);
            fetchReservations();
        });
    });


    // Coach select change
    coachSelect.addEventListener('change', function () {
        currentCoachFilter = this.value;
        fetchReservations();
    });

    // Month navigation
    prevMonthBtn.addEventListener('click', function () {
        currentDate = new Date(currentDate.getFullYear(), currentDate.getMonth() - 1, 1);
        fetchReservations();
    });

    nextMonthBtn.addEventListener('click', function () {
        currentDate = new Date(currentDate.getFullYear(), currentDate.getMonth() + 1, 1);
        fetchReservations();
    });

    // Toggle month dropdown
    if (monthNavBtn) {
        monthNavBtn.addEventListener('click', function (e) {
            e.stopPropagation();
            monthDropdown.classList.toggle('active');
        });
    }

    // Handle month selection
    monthOptions.forEach(option => {
        option.addEventListener('click', function (e) {
            e.stopPropagation();
            const selectedMonth = parseInt(this.dataset.month);
            currentDate = new Date(currentDate.getFullYear(), selectedMonth, 1);
            monthDropdown.classList.remove('active');
            fetchReservations();
        });
    });

    // Close dropdown when clicking outside
    document.addEventListener('click', function (e) {
        if (monthDropdown && !monthNavBtn.contains(e.target)) {
            monthDropdown.classList.remove('active');
        }
    });

    // Close details button
    closeDetailsBtn.addEventListener('click', closeScheduleDetails);

    // Schedule training button - Show confirmation modal
    scheduleTrainingBtn.addEventListener('click', function () {
        const reservationId = this.getAttribute('data-reservation-id');
        if (reservationId) {
            // Get session details from the displayed information
            const trainerName = document.querySelector('.detail-item:nth-child(1) .detail-value').textContent;
            const classType = document.querySelector('.detail-item:nth-child(2) .detail-value').textContent;
            const sessionDate = document.querySelector('.detail-item:nth-child(3) .detail-value').textContent;
            const sessionTime = document.querySelector('.detail-item:nth-child(4) .detail-value').textContent;
            
            showBookingConfirmation(reservationId, trainerName, classType, sessionDate, sessionTime);
        }
    });

    // Booking confirmation modal
    function showBookingConfirmation(reservationId, trainer, classType, date, time) {
        const modal = document.getElementById('confirmModal');
        const message = document.getElementById('confirmMessage');
        const yesBtn = document.getElementById('confirmYes');
        const noBtn = document.getElementById('confirmNo');

        message.innerHTML = `
            <div style="text-align: left; margin: 20px 0;">
                <p style="margin-bottom: 15px; font-size: 16px;">Are you sure you want to book this training session?</p>
                <div style="background: #f8f9fa; padding: 15px; border-radius: 8px; border-left: 4px solid var(--color-primary);">
                    <p style="margin: 5px 0;"><strong>Trainer:</strong> ${trainer}</p>
                    <p style="margin: 5px 0;"><strong>Class:</strong> ${classType}</p>
                    <p style="margin: 5px 0;"><strong>Date:</strong> ${date}</p>
                    <p style="margin: 5px 0;"><strong>Time:</strong> ${time}</p>
                </div>
            </div>
        `;

        modal.style.display = 'flex';

        // Handle confirmation
        const handleYes = () => {
            modal.style.display = 'none';
            bookSession(reservationId);
            yesBtn.removeEventListener('click', handleYes);
            noBtn.removeEventListener('click', handleNo);
        };

        const handleNo = () => {
            modal.style.display = 'none';
            yesBtn.removeEventListener('click', handleYes);
            noBtn.removeEventListener('click', handleNo);
        };

        yesBtn.addEventListener('click', handleYes);
        noBtn.addEventListener('click', handleNo);

        // Close on overlay click
        modal.addEventListener('click', (e) => {
            if (e.target === modal) {
                handleNo();
            }
        });
    }

    // Initialize
    (async () => {
        try {
            const firstBtn = document.querySelector('.class-filters .filter-btn') || document.querySelector('.filter-btn');

            if (firstBtn) {
                document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
                firstBtn.classList.add('active');
                currentClassFilter = firstBtn.dataset.class || 'all';
                await fetchTrainers(currentClassFilter);

            } else {
                await fetchTrainers('all');
            }

            await fetchUserBookings();
        } catch (err) {
            console.error('Initialization error:', err);
            fetchTrainers('all');
            fetchUserBookings();
        }
    })();

});
