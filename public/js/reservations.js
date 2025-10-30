document.addEventListener('DOMContentLoaded', function() {
    // Initialize calendar with current date
    const today = new Date();
    let currentDate = new Date(today.getFullYear(), today.getMonth(), 1);
    let currentClassFilter = 'all';
    let currentCoachFilter = 'all';
    let currentSessionFilter = 'all';
    let availableOnlyFilter = false;
    let upcomingOnlyFilter = true;
    let sessionsData = {}; // Store sessions by day
    let trainersData = {};

    // ==========================================
    // TOAST NOTIFICATION SYSTEM
    // ==========================================
    function showToast(message, type = 'success', duration = 4000) {
        const container = document.getElementById('toastContainer');
        const toast = document.createElement('div');
        toast.className = `toast toast-${type}`;

        const icons = {
            success: 'fa-check-circle',
            error: 'fa-times-circle',
            warning: 'fa-exclamation-triangle',
            info: 'fa-info-circle'
        };

        toast.innerHTML = `
            <i class="fas ${icons[type]} toast-icon"></i>
            <div class="toast-message">${message}</div>
            <button class="toast-close" onclick="this.parentElement.remove()">
                <i class="fas fa-times"></i>
            </button>
        `;

        container.appendChild(toast);

        // Auto remove after duration
        setTimeout(() => {
            toast.classList.add('toast-exit');
            setTimeout(() => toast.remove(), 300);
        }, duration);
    }

    // Custom confirm dialog
    function showConfirm(message) {
        return new Promise((resolve) => {
            const modal = document.getElementById('confirmModal');
            const messageEl = document.getElementById('confirmMessage');
            const yesBtn = document.getElementById('confirmYes');
            const noBtn = document.getElementById('confirmNo');

            messageEl.textContent = message;
            modal.classList.add('active');

            function cleanup() {
                modal.classList.remove('active');
                yesBtn.replaceWith(yesBtn.cloneNode(true));
                noBtn.replaceWith(noBtn.cloneNode(true));
            }

            document.getElementById('confirmYes').onclick = () => {
                cleanup();
                resolve(true);
            };

            document.getElementById('confirmNo').onclick = () => {
                cleanup();
                resolve(false);
            };

            modal.onclick = (e) => {
                if (e.target === modal) {
                    cleanup();
                    resolve(false);
                }
            };
        });
    }
    // ==========================================
    // END TOAST NOTIFICATION SYSTEM
    // ==========================================

    // Calendar elements
    const scheduleCalendar = document.getElementById('scheduleCalendar');

    // Filter elements
    const filterBtns = document.querySelectorAll('.filter-btn');
    const coachSelect = document.getElementById('coachSelect');
    const sessionSelect = document.getElementById('sessionSelect');
    const availableOnlyCheckbox = document.getElementById('availableOnly');
    const upcomingOnlyCheckbox = document.getElementById('upcomingOnly');

    // Session picker event
    if (sessionSelect) {
        sessionSelect.addEventListener('change', function() {
            currentSessionFilter = sessionSelect.value;
            closeScheduleDetails();
            fetchReservations();
        });
    }

    // Quick filter events
    if (availableOnlyCheckbox) {
        availableOnlyCheckbox.addEventListener('change', function() {
            availableOnlyFilter = this.checked;
            closeScheduleDetails();
            renderLargeCalendar();
        });
    }

    if (upcomingOnlyCheckbox) {
        upcomingOnlyCheckbox.addEventListener('change', function() {
            upcomingOnlyFilter = this.checked;
            closeScheduleDetails();
            renderLargeCalendar();
        });
    }

    // Schedule details
    const scheduleDetails = document.getElementById('scheduleDetails');
    const closeDetailsBtn = document.getElementById('closeDetails');

    // Month navigation in schedule header
    const monthNavBtn = document.querySelector('#monthNavBtn');
    const monthDropdown = document.getElementById('monthDropdown');
    const currentMonthDisplay = document.getElementById('currentMonthDisplay');
    const monthOptions = document.querySelectorAll('.month-option');

    async function fetchTrainers(classType = "all") {
    try {
        // Add timestamp to prevent caching
        const timestamp = new Date().getTime();
        const response = await fetch(`api/get_trainers.php?_t=${timestamp}`, {
            cache: 'no-store'
        });
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

    // Save current selection
    const previousSelection = currentCoachFilter;

    dropdown.innerHTML = "";

    // Add "All Coaches" option
    const allOption = document.createElement("option");
    allOption.value = "all";
    allOption.textContent = "All Coaches";
    dropdown.appendChild(allOption);

    const key = classType ? classType.toLowerCase().replace(/\s+/g, "-") : "all";
    const trainers = trainersData[key] || [];

    // Check if previously selected coach is still available
    let coachStillAvailable = false;

    trainers.forEach(trainer => {
        const opt = document.createElement("option");
        opt.value = trainer.id;
        opt.textContent = trainer.name;
        dropdown.appendChild(opt);

        if (trainer.id == previousSelection) {
            coachStillAvailable = true;
        }
    });

    // Restore selection if coach is still available, otherwise reset to "all"
    if (coachStillAvailable && previousSelection !== 'all') {
        dropdown.value = previousSelection;
        currentCoachFilter = previousSelection;
    } else {
        dropdown.value = "all";
        currentCoachFilter = "all";
    }

}



    // Fetch reservations from server
    async function fetchReservations() {
        const year = currentDate.getFullYear();
        const month = currentDate.getMonth() + 1;

        try {
            // Add timestamp to prevent caching
            const timestamp = new Date().getTime();
            const response = await fetch(`api/get_reservations.php?year=${year}&month=${month}&class=${currentClassFilter}&coach=${currentCoachFilter}&session=${currentSessionFilter}&_t=${timestamp}`, {
                cache: 'no-store',
                headers: {
                    'Cache-Control': 'no-cache',
                    'Pragma': 'no-cache'
                }
            });
            const data = await response.json();

            if (data.success) {
                console.log('Reservations data received:', data.reservations);
                console.log('Number of days with sessions:', Object.keys(data.reservations).length);
                sessionsData = data.reservations;
                console.log('Rendering calendar with updated data...');
                renderLargeCalendar();
                console.log('Calendar render complete');
            } else {
                console.error('Failed to fetch reservations:', data.message);
            }
        } catch (error) {
            console.error('Error fetching reservations:', error);
        }
    }

    // Fetch user bookings
    async function fetchUserBookings() {
        try {
            // Add timestamp to prevent caching
            const timestamp = new Date().getTime();
            const response = await fetch(`api/get_user_bookings.php?_t=${timestamp}`, {
                cache: 'no-store'
            });
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

        const now = new Date();

        const tableHTML = `
            <table class="sessions-table">
                <thead>
                    <tr>
                        <th>Class</th>
                        <th>Date</th>
                        <th>Time</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    ${bookings.map(booking => {
                        const datetime = new Date(booking.datetime);
                        const dayName = datetime.toLocaleDateString('en-US', { weekday: 'short' });
                        const day = datetime.getDate();
                        const month = datetime.toLocaleDateString('en-US', { month: 'short' });
                        const statusClass = booking.status.toLowerCase().replace(' ', '-');

                        // Check if booking is cancellable (at least 2 hours before and status is confirmed)
                        const hoursUntilSession = (datetime - now) / (1000 * 60 * 60);
                        const isCancellable = booking.status === 'Confirmed' && hoursUntilSession >= 2;
                        const isCancelled = booking.status === 'Cancelled';

                        let actionButton = '';
                        if (isCancellable) {
                            actionButton = `<button class="cancel-btn" data-booking-id="${booking.id}" title="Cancel this booking">
                                <i class="fas fa-times"></i> Cancel
                            </button>`;
                        } else if (isCancelled) {
                            actionButton = `<span class="cancelled-label">Cancelled</span>`;
                        } else if (hoursUntilSession < 2 && datetime > now) {
                            actionButton = `<span class="no-cancel-label" title="Too close to session time">Cannot cancel</span>`;
                        } else {
                            actionButton = `<span class="no-action-label">-</span>`;
                        }

                        return `
                            <tr class="${isCancelled ? 'cancelled-row' : ''}">
                                <td class="session-class" data-label="Class">${booking.class_type}</td>
                                <td class="session-date" data-label="Date">${month} ${day} (${dayName})</td>
                                <td class="session-time" data-label="Time">${booking.time}</td>
                                <td class="session-status status-${statusClass}" data-label="Status">
                                    <i class="fas fa-${booking.status === 'Confirmed' ? 'check' : 'times'}-circle"></i> ${booking.status}
                                </td>
                                <td class="session-action" data-label="Action">${actionButton}</td>
                            </tr>
                        `;
                    }).join('')}
                </tbody>
            </table>
        `;

        sessionsList.innerHTML = tableHTML;

        // Add event listeners to cancel buttons
        document.querySelectorAll('.cancel-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const bookingId = this.getAttribute('data-booking-id');
                cancelBooking(bookingId);
            });
        });
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


    // Render large calendar (monthly schedule)
    function renderLargeCalendar() {
        const year = currentDate.getFullYear();
        const month = currentDate.getMonth();
        const today = new Date();
        today.setHours(0, 0, 0, 0);
        const todayDateOnly = new Date(today.getFullYear(), today.getMonth(), today.getDate());

        // Calculate max booking date (1 month from today)
        const maxBookingDate = new Date();
        maxBookingDate.setMonth(maxBookingDate.getMonth() + 1);
        maxBookingDate.setHours(0, 0, 0, 0);

        updateMonthNavText();
        scheduleCalendar.innerHTML = '';

        const firstDay = new Date(year, month, 1).getDay();
        const daysInMonth = new Date(year, month + 1, 0).getDate();
        const daysInPrevMonth = new Date(year, month, 0).getDate();

        // Track first day with sessions for debugging
        let firstDayWithSessions = -1;
        for (let day in sessionsData) {
            if (sessionsData[day] && sessionsData[day].length > 0) {
                firstDayWithSessions = parseInt(day);
                break;
            }
        }

        // Previous month days
        for (let i = firstDay - 1; i >= 0; i--) {
            const day = document.createElement('div');
            day.className = 'schedule-day inactive past-date';
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

            const currentDayDate = new Date(year, month, i);
            currentDayDate.setHours(0, 0, 0, 0);

            // Check if date is in the past
            const isPastDate = currentDayDate < todayDateOnly;

            // Check if date is too far in advance (more than 1 month)
            const isTooFarAdvance = currentDayDate > maxBookingDate;

            // Check for sessions on this day
            let daySessions = sessionsData[i];
            let onlyAllowed = true;

            // Don't show sessions for past dates
            if (isPastDate) {
                day.classList.add('inactive', 'past-date');
                day.title = 'Past date - not available for booking';
                scheduleCalendar.appendChild(day);
                continue;
            }

            // Don't show sessions for dates too far in advance
            if (isTooFarAdvance) {
                day.classList.add('inactive', 'too-far-advance');
                day.title = 'Bookings limited to 1 month in advance';
                scheduleCalendar.appendChild(day);
                continue;
            }

            if (daySessions && daySessions.length > 0) {
                onlyAllowed = daySessions.every(session => allowedClassTypes.includes(session.class_slug));

                // Apply filters
                let filteredSessions = [...daySessions];

                if (availableOnlyFilter) {
                    filteredSessions = filteredSessions.filter(session => session.slots > 0);
                }

                if (upcomingOnlyFilter && currentDayDate < today) {
                    filteredSessions = [];
                }

                if (filteredSessions.length > 0) {
                    // Count sessions with available slots vs full sessions
                    const availableSessions = filteredSessions.filter(s => (s.slots || 0) > 0);
                    const fullSessions = filteredSessions.filter(s => (s.slots || 0) === 0);
                    const totalSessions = filteredSessions.length;
                    const availableCount = availableSessions.length;

                    // Debug logging for the first day with sessions
                    if (i === firstDayWithSessions) {
                        console.log(`Day ${i}: Total=${totalSessions}, Available=${availableCount}, Slots data:`,
                            filteredSessions.map(s => `ID:${s.id} slots:${s.slots}`));
                        firstDayWithSessions = -1; // Only log once
                    }

                    // Create session indicator
                    const sessionIndicator = document.createElement('div');
                    sessionIndicator.className = 'slot-indicator';

                    if (availableCount === 0) {
                        // All sessions are full
                        sessionIndicator.textContent = 'FULL';
                        sessionIndicator.classList.add('slot-full');
                    } else if (availableCount === totalSessions) {
                        // All sessions available
                        sessionIndicator.textContent = `${totalSessions} ${totalSessions === 1 ? 'session' : 'sessions'}`;
                        sessionIndicator.classList.add('slot-high');
                    } else {
                        // Some sessions available, some full
                        sessionIndicator.textContent = `${availableCount}/${totalSessions} open`;

                        const availabilityPercentage = (availableCount / totalSessions) * 100;
                        if (availabilityPercentage > 50) {
                            sessionIndicator.classList.add('slot-medium');
                        } else {
                            sessionIndicator.classList.add('slot-low');
                        }
                    }

                    day.appendChild(sessionIndicator);

                    day.addEventListener('click', () => showScheduleDetails(i, filteredSessions));
                } else {
                    daySessions = null; // No sessions after filtering
                }
            }

            if (!onlyAllowed) {
                day.classList.add('inactive');
                day.title = 'Not available for your plan';
            } else if (upcomingOnlyFilter && currentDayDate < today) {
                day.classList.add('inactive');
            } else if (!daySessions || daySessions.length === 0) {
                // No visual change, just no click handler
            }

            scheduleCalendar.appendChild(day);
        }

        // Fill remaining cells (next month preview)
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

    // Show schedule details
    function showScheduleDetails(day, daySessions) {
        const monthNames = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
        const dayNames = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
        const date = new Date(currentDate.getFullYear(), currentDate.getMonth(), day);
        const dateStr = `${dayNames[date.getDay()]}, ${monthNames[date.getMonth()]} ${day}, ${date.getFullYear()}`;

        document.getElementById('detailDate').textContent = dateStr;

        // Populate sessions list
        const sessionsListModal = document.getElementById('sessionsListModal');
        sessionsListModal.innerHTML = '';

        daySessions.forEach(session => {
            const sessionCard = document.createElement('div');
            sessionCard.className = 'session-card-modal';

            const isAvailable = (session.slots || 0) > 0;

            sessionCard.innerHTML = `
                <div class="session-card-header">
                    <div class="session-info">
                        <div class="session-class-type">
                            <i class="fas fa-dumbbell"></i> ${session.class}
                        </div>
                        <div class="session-trainer">
                            <i class="fas fa-user"></i> ${session.trainer}
                        </div>
                        <div class="session-time">
                            <i class="fas fa-clock"></i> ${session.time}
                        </div>
                    </div>
                    <div class="session-status ${isAvailable ? 'available' : 'full'}">
                        ${isAvailable ? '<i class="fas fa-check-circle"></i> Available' : '<i class="fas fa-times-circle"></i> Full'}
                    </div>
                </div>
                ${isAvailable ? `
                    <button class="book-session-btn" data-reservation-id="${session.id}">
                        <i class="fas fa-calendar-check"></i> Book This Session
                    </button>
                ` : ''}
            `;

            sessionsListModal.appendChild(sessionCard);
        });

        // Add click handlers for book buttons
        document.querySelectorAll('.book-session-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                const reservationId = btn.getAttribute('data-reservation-id');
                bookSession(reservationId);
            });
        });

        scheduleDetails.style.display = 'flex';
    }

    // Close schedule details
    function closeScheduleDetails() {
        scheduleDetails.style.display = 'none';
    }

    // Close modal when clicking outside of it
    scheduleDetails.addEventListener('click', function(e) {
        if (e.target === scheduleDetails) {
            closeScheduleDetails();
        }
    });

    // Close modal when pressing ESC key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && scheduleDetails.style.display === 'flex') {
            closeScheduleDetails();
        }
    });

    // Book session
    async function bookSession(reservationId) {
        try {
            console.log('Attempting to book session:', reservationId);
            const formData = new FormData();
            formData.append('reservation_id', reservationId);

            const response = await fetch('api/book_session.php', {
                method: 'POST',
                body: formData
            });

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const data = await response.json();
            console.log('Booking response:', data);

            if (data.success) {
                showToast('Training session booked successfully! See you at the gym!', 'success', 5000);
                closeScheduleDetails();

                // Delay to ensure database transaction is complete
                console.log('Waiting for database commit...');
                await new Promise(resolve => setTimeout(resolve, 800));

                // Refresh both calendar and bookings list
                console.log('Refreshing calendar and bookings after booking...');
                console.log('Before refresh - Current sessionsData:', JSON.stringify(sessionsData));

                // Fetch reservations first to update calendar
                await fetchReservations();
                // Then fetch user bookings
                await fetchUserBookings();

                console.log('After refresh - Updated sessionsData:', JSON.stringify(sessionsData));
                console.log('Refresh complete after booking');

                // Force a visual update
                renderLargeCalendar();
            } else {
                showToast(data.message || 'Failed to book session', 'error', 6000);
            }
        } catch (error) {
            console.error('Error booking session:', error);
            showToast('Network error. Please check your connection and try again.', 'error');
        }
    }

    // Cancel booking
    async function cancelBooking(bookingId) {
        const confirmed = await showConfirm('Are you sure you want to cancel this session? This action cannot be undone and your slot will be released.');

        if (!confirmed) {
            return;
        }

        try {
            console.log('Attempting to cancel booking:', bookingId);
            const formData = new FormData();
            formData.append('booking_id', bookingId);

            const response = await fetch('api/cancel_booking.php', {
                method: 'POST',
                body: formData
            });

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const data = await response.json();
            console.log('Cancellation response:', data);

            if (data.success) {
                showToast(data.message || 'Session cancelled successfully', 'success', 5000);

                // Delay to ensure database transaction is complete
                console.log('Waiting for database commit after cancellation...');
                await new Promise(resolve => setTimeout(resolve, 800));

                // Refresh bookings and reservations
                console.log('Refreshing calendar and bookings after cancellation...');
                console.log('Before refresh - Current sessionsData:', JSON.stringify(sessionsData));

                // Fetch reservations first to update calendar
                await fetchReservations();
                // Then fetch user bookings
                await fetchUserBookings();

                console.log('After refresh - Updated sessionsData:', JSON.stringify(sessionsData));
                console.log('Refresh complete after cancellation');

                // Force a visual update
                renderLargeCalendar();
            } else {
                showToast(data.message || 'Failed to cancel booking', 'error', 6000);
            }
        } catch (error) {
            console.error('Error cancelling booking:', error);
            showToast('Network error. Please check your connection and try again.', 'error');
        }
    }

    // Class filter functionality
    filterBtns.forEach(btn => {
    btn.addEventListener('click', async function() {
        filterBtns.forEach(b => b.classList.remove('active'));
        this.classList.add('active');

        currentClassFilter = this.dataset.class;
        console.log('Selected class:', currentClassFilter);

        closeScheduleDetails();
        await fetchTrainers(currentClassFilter);
        fetchReservations();
    });
});


    // Coach select change
    coachSelect.addEventListener('change', function() {
        currentCoachFilter = this.value;
        closeScheduleDetails();
        fetchReservations();
    });

    // Month navigation buttons
    const prevMonthBtn = document.getElementById('prevMonthBtn');
    const nextMonthBtn = document.getElementById('nextMonthBtn');

    if (prevMonthBtn) {
        prevMonthBtn.addEventListener('click', function() {
            currentDate = new Date(currentDate.getFullYear(), currentDate.getMonth() - 1, 1);
            closeScheduleDetails();
            fetchReservations();
        });
    }

    if (nextMonthBtn) {
        nextMonthBtn.addEventListener('click', function() {
            currentDate = new Date(currentDate.getFullYear(), currentDate.getMonth() + 1, 1);
            closeScheduleDetails();
            fetchReservations();
        });
    }

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

    // Book session buttons are now dynamically created in showScheduleDetails()

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
