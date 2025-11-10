document.addEventListener('DOMContentLoaded', function () {
    // Booking state
    const bookingState = {
        date: null,
        session: null,
        classType: null,
        trainerId: null,
        trainerName: null,
        currentStep: 1,
        facilityFull: false,
        hasAvailableTrainers: false,
        weeklyLimit: 12,
        currentWeekCount: 0,
        currentWeekRemaining: 12,
        selectedWeekCount: 0,
        selectedWeekFull: false
    };

    // Calendar state
    let currentMonth = new Date().getMonth();
    let currentYear = new Date().getFullYear();

    // Bookings data storage
    let allBookingsData = {
        upcoming: [],
        past: [],
        cancelled: [],
        all: [] // Store all bookings for week calculations
    };

    // Initialize
    init();

    function init() {
        loadWeeklyBookings();
        loadUserBookings();
        renderCalendar();
        setupEventListeners();
    }

    // ===================================
    // TOAST NOTIFICATIONS
    // ===================================
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

        setTimeout(() => {
            toast.classList.add('toast-exit');
            setTimeout(() => toast.remove(), 300);
        }, duration);
    }

    // ===================================
    // LOAD WEEKLY BOOKINGS COUNT
    // ===================================
    function loadWeeklyBookings() {
        fetch('api/get_user_bookings.php')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const count = data.summary.weekly_count;
                    const remaining = data.summary.weekly_remaining;
                    const limit = data.summary.weekly_limit || 12;

                    // Store current week data
                    bookingState.currentWeekCount = count;
                    bookingState.currentWeekRemaining = remaining;
                    bookingState.weeklyLimit = limit;

                    // Store all bookings for per-week calculations
                    allBookingsData.all = data.bookings || [];

                    const weeklyCountEl = document.getElementById('weeklyBookingsCount');
                    const weeklyTextEl = document.getElementById('weeklyProgressText');

                    if (weeklyCountEl) {
                        weeklyCountEl.textContent = count;
                    }
                    if (weeklyTextEl) {
                        if (remaining === 0) {
                            weeklyTextEl.textContent = `This week's limit reached (${limit} max)`;
                            weeklyTextEl.style.color = '#ff9800';
                        } else {
                            weeklyTextEl.textContent = `${remaining} bookings remaining this week`;
                            weeklyTextEl.style.color = '';
                        }
                    }
                }
            })
            .catch(error => {
                console.error('Error loading weekly bookings:', error);
            });
    }

    // Helper function to get week boundaries (Sunday to Saturday)
    function getWeekBoundaries(dateStr) {
        const date = new Date(dateStr + 'T00:00:00');
        const dayOfWeek = date.getDay(); // 0 (Sunday) to 6 (Saturday)

        // Calculate Sunday of the week
        const sunday = new Date(date);
        sunday.setDate(date.getDate() - dayOfWeek);
        sunday.setHours(0, 0, 0, 0);

        // Calculate Saturday of the week
        const saturday = new Date(sunday);
        saturday.setDate(sunday.getDate() + 6);
        saturday.setHours(0, 0, 0, 0);

        // Format dates as YYYY-MM-DD without timezone conversion
        const formatDate = (d) => {
            const year = d.getFullYear();
            const month = String(d.getMonth() + 1).padStart(2, '0');
            const day = String(d.getDate()).padStart(2, '0');
            return `${year}-${month}-${day}`;
        };

        return {
            start: formatDate(sunday),
            end: formatDate(saturday)
        };
    }

    // Check how many bookings are in a specific week
    function getBookingCountForWeek(dateStr) {
        const weekBounds = getWeekBoundaries(dateStr);

        const count = allBookingsData.all.filter(booking => {
            // Only count confirmed and completed bookings
            if (booking.status !== 'confirmed' && booking.status !== 'completed') {
                return false;
            }

            return booking.date >= weekBounds.start && booking.date <= weekBounds.end;
        }).length;

        return count;
    }

    function checkWeeklyLimitForDate(dateStr) {
        const count = getBookingCountForWeek(dateStr);
        bookingState.selectedWeekCount = count;
        bookingState.selectedWeekFull = count >= bookingState.weeklyLimit;

        // Update warning display
        updateWeeklyLimitWarning(dateStr);

        return !bookingState.selectedWeekFull;
    }

    function updateWeeklyLimitWarning(dateStr) {
        const wizardSection = document.querySelector('.booking-wizard-section');
        if (!wizardSection) return;

        const existingWarning = wizardSection.querySelector('.weekly-limit-warning');

        if (bookingState.selectedWeekFull) {
            const weekBounds = getWeekBoundaries(dateStr);
            const weekStart = new Date(weekBounds.start + 'T00:00:00');
            const weekEnd = new Date(weekBounds.end + 'T00:00:00');

            if (!existingWarning) {
                const warning = document.createElement('div');
                warning.className = 'weekly-limit-warning';
                warning.innerHTML = `
                    <i class="fas fa-exclamation-circle"></i>
                    <div>
                        <h3>Weekly Booking Limit Reached</h3>
                        <p>You've already booked ${bookingState.selectedWeekCount} sessions for the week of ${weekStart.toLocaleDateString('en-US', { month: 'short', day: 'numeric' })} - ${weekEnd.toLocaleDateString('en-US', { month: 'short', day: 'numeric' })} (maximum ${bookingState.weeklyLimit} per week). Please select a date from a different week.</p>
                    </div>
                `;
                wizardSection.insertBefore(warning, wizardSection.firstChild);
            } else {
                // Update existing warning
                existingWarning.querySelector('p').textContent =
                    `You've already booked ${bookingState.selectedWeekCount} sessions for the week of ${weekStart.toLocaleDateString('en-US', { month: 'short', day: 'numeric' })} - ${weekEnd.toLocaleDateString('en-US', { month: 'short', day: 'numeric' })} (maximum ${bookingState.weeklyLimit} per week). Please select a date from a different week.`;
            }
        } else {
            // Remove warning if week is not full
            if (existingWarning) {
                existingWarning.remove();
            }
        }
    }

    // ===================================
    // LOAD USER BOOKINGS
    // ===================================
    function loadUserBookings() {
        fetch('api/get_user_bookings.php')
            .then(response => response.json())
            .then(data => {
                console.log('Bookings API Response:', data); // Debug log
                if (data.success) {
                    console.log('Grouped bookings:', data.grouped); // Debug log
                    console.log('Summary:', data.summary); // Debug log
                    renderBookings(data.grouped);
                    // Counts are updated in applyBookingsFilter(), called by renderBookings()
                } else {
                    console.error('API returned error:', data.message);
                    document.getElementById('upcomingBookings').innerHTML =
                        `<p class="empty-message">${data.message || 'Failed to load bookings'}</p>`;
                    document.getElementById('pastBookings').innerHTML =
                        `<p class="empty-message">${data.message || 'Failed to load bookings'}</p>`;
                    document.getElementById('cancelledBookings').innerHTML =
                        `<p class="empty-message">${data.message || 'Failed to load bookings'}</p>`;
                }
            })
            .catch(error => {
                console.error('Error loading bookings:', error);
                document.getElementById('upcomingBookings').innerHTML =
                    '<p class="empty-message">Failed to load bookings</p>';
                document.getElementById('pastBookings').innerHTML =
                    '<p class="empty-message">Failed to load bookings</p>';
                document.getElementById('cancelledBookings').innerHTML =
                    '<p class="empty-message">Failed to load bookings</p>';
            });
    }

    function renderBookings(grouped) {
        console.log('Rendering bookings - upcoming:', grouped.upcoming?.length, 'today:', grouped.today?.length, 'past:', grouped.past?.length); // Debug log

        // Combine all bookings from server groups
        const allBookings = [...(grouped.today || []), ...(grouped.upcoming || []), ...(grouped.past || [])];

        // Get current date and time
        const now = new Date();
        const today = new Date(now);
        today.setHours(0, 0, 0, 0);

        const sessionEndTimes = {
            'Morning': 11,
            'Afternoon': 17,
            'Evening': 22
        };

        // Re-categorize bookings based on current time
        const upcomingList = [];
        const pastList = [];
        const cancelledList = [];

        allBookings.forEach(booking => {
            // Separate cancelled bookings first
            if (booking.status === 'cancelled') {
                cancelledList.push(booking);
                return;
            }

            const bookingDate = new Date(booking.date + 'T00:00:00');
            bookingDate.setHours(0, 0, 0, 0);

            // If booking date is in the future
            if (bookingDate > today) {
                upcomingList.push(booking);
            }
            // If booking date is today
            else if (bookingDate.getTime() === today.getTime()) {
                const currentHour = now.getHours();
                const sessionEnd = sessionEndTimes[booking.session_time] || 24;

                // If session hasn't ended yet, it's upcoming
                if (currentHour < sessionEnd) {
                    upcomingList.push(booking);
                } else {
                    // Session has ended, move to past
                    pastList.push(booking);
                }
            }
            // Booking date is in the past
            else {
                pastList.push(booking);
            }
        });

        // Sort upcoming by date ascending (earliest first), then by session time
        upcomingList.sort((a, b) => {
            const dateCompare = new Date(a.date) - new Date(b.date);
            if (dateCompare !== 0) return dateCompare;

            // If same date, sort by session time (Morning, Afternoon, Evening)
            const sessionOrder = { 'Morning': 1, 'Afternoon': 2, 'Evening': 3 };
            return sessionOrder[a.session_time] - sessionOrder[b.session_time];
        });

        // Sort past bookings by date descending (most recent first)
        pastList.sort((a, b) => {
            const dateCompare = new Date(b.date) - new Date(a.date);
            if (dateCompare !== 0) return dateCompare;

            // If same date, sort by session time (Evening, Afternoon, Morning - reverse)
            const sessionOrder = { 'Evening': 1, 'Afternoon': 2, 'Morning': 3 };
            return sessionOrder[a.session_time] - sessionOrder[b.session_time];
        });

        // Sort cancelled bookings by date descending (most recent first)
        cancelledList.sort((a, b) => {
            const dateCompare = new Date(b.date) - new Date(a.date);
            if (dateCompare !== 0) return dateCompare;

            const sessionOrder = { 'Evening': 1, 'Afternoon': 2, 'Morning': 3 };
            return sessionOrder[a.session_time] - sessionOrder[b.session_time];
        });

        // Store the full data for filtering
        allBookingsData.upcoming = upcomingList;
        allBookingsData.past = pastList;
        allBookingsData.cancelled = cancelledList;

        // Update stat cards with next upcoming session
        updateStatCards(upcomingList);

        // Apply current filter
        applyBookingsFilter();
    }

    function applyBookingsFilter() {
        const filterValue = document.getElementById('classFilter')?.value || 'all';

        // Filter upcoming bookings
        let filteredUpcoming = allBookingsData.upcoming;
        if (filterValue !== 'all') {
            filteredUpcoming = allBookingsData.upcoming.filter(booking =>
                booking.class_type === filterValue
            );
        }

        // Filter past bookings
        let filteredPast = allBookingsData.past;
        if (filterValue !== 'all') {
            filteredPast = allBookingsData.past.filter(booking =>
                booking.class_type === filterValue
            );
        }

        // Filter cancelled bookings
        let filteredCancelled = allBookingsData.cancelled;
        if (filterValue !== 'all') {
            filteredCancelled = allBookingsData.cancelled.filter(booking =>
                booking.class_type === filterValue
            );
        }

        renderBookingList('upcomingBookings', filteredUpcoming);
        renderBookingList('pastBookings', filteredPast);
        renderBookingList('cancelledBookings', filteredCancelled);

        // Update counts with filtered data
        document.getElementById('upcomingCount').textContent = filteredUpcoming.length;
        document.getElementById('pastCount').textContent = filteredPast.length;
        document.getElementById('cancelledCount').textContent = filteredCancelled.length;
    }

    function updateStatCards(upcomingList) {
        const upcomingClassEl = document.getElementById('upcomingClass');
        const upcomingDateEl = document.getElementById('upcomingDate');
        const upcomingTrainerEl = document.getElementById('upcomingTrainer');
        const trainerSubtextEl = document.getElementById('trainerSubtext');

        if (!upcomingList || upcomingList.length === 0) {
            if (upcomingClassEl) upcomingClassEl.textContent = '-';
            if (upcomingDateEl) upcomingDateEl.textContent = 'No upcoming sessions';
            if (upcomingTrainerEl) upcomingTrainerEl.textContent = '-';
            if (trainerSubtextEl) trainerSubtextEl.textContent = '-';
            return;
        }

        // Get current date and time
        const now = new Date();
        const today = new Date(now);
        today.setHours(0, 0, 0, 0);

        // Find the first truly upcoming booking (not past)
        let nextBooking = null;
        for (const booking of upcomingList) {
            const bookingDate = new Date(booking.date + 'T00:00:00');
            bookingDate.setHours(0, 0, 0, 0);

            // If booking is in the future, use it
            if (bookingDate > today) {
                nextBooking = booking;
                break;
            }

            // If booking is today, check if session hasn't ended yet
            if (bookingDate.getTime() === today.getTime()) {
                const currentHour = now.getHours();
                const sessionEnd = {
                    'Morning': 11,
                    'Afternoon': 17,
                    'Evening': 22
                };

                if (currentHour < (sessionEnd[booking.session_time] || 24)) {
                    nextBooking = booking;
                    break;
                }
            }
        }

        // If no valid upcoming booking found
        if (!nextBooking) {
            if (upcomingClassEl) upcomingClassEl.textContent = '-';
            if (upcomingDateEl) upcomingDateEl.textContent = 'No upcoming sessions';
            if (upcomingTrainerEl) upcomingTrainerEl.textContent = '-';
            if (trainerSubtextEl) trainerSubtextEl.textContent = '-';
            return;
        }

        // Parse the date properly
        const bookingDate = new Date(nextBooking.date + 'T00:00:00');
        bookingDate.setHours(0, 0, 0, 0);

        const tomorrow = new Date(today);
        tomorrow.setDate(tomorrow.getDate() + 1);

        const isToday = bookingDate.getTime() === today.getTime();
        const isTomorrow = bookingDate.getTime() === tomorrow.getTime();

        // Update class name
        if (upcomingClassEl) {
            upcomingClassEl.textContent = nextBooking.class_type;
        }

        // Update date info
        if (upcomingDateEl) {
            let dateText = '';
            if (isToday) {
                dateText = `Today, ${nextBooking.session_time}`;
            } else if (isTomorrow) {
                dateText = `Tomorrow, ${nextBooking.session_time}`;
            } else {
                dateText = `${bookingDate.toLocaleDateString('en-US', { month: 'short', day: 'numeric' })}, ${nextBooking.session_time}`;
            }
            upcomingDateEl.textContent = dateText;
        }

        // Update trainer info
        if (upcomingTrainerEl) {
            upcomingTrainerEl.textContent = nextBooking.trainer_name;
        }

        if (trainerSubtextEl) {
            trainerSubtextEl.textContent = nextBooking.session_hours || nextBooking.session_time;
        }
    }

    function renderBookingList(containerId, bookings) {
        const container = document.getElementById(containerId);
        console.log(`Rendering ${containerId}:`, bookings); // Debug log

        if (!bookings || bookings.length === 0) {
            container.innerHTML = '<p class="empty-message">No bookings found</p>';
            return;
        }

        // Time-related variables
        const now = new Date();
        const currentHour = now.getHours();
        const today = new Date(now);
        today.setHours(0, 0, 0, 0);

        const sessionStartTimes = {
            'Morning': 7,    // 7 AM
            'Afternoon': 12, // 12 PM
            'Evening': 17    // 5 PM
        };

        const sessionEndTimes = {
            'Morning': 11,
            'Afternoon': 17,
            'Evening': 22
        };

        container.innerHTML = bookings.map(booking => {
            // Determine if this booking can actually be cancelled based on current time and 12-hour policy
            const bookingDate = new Date(booking.date + 'T00:00:00');
            const sessionStartHour = sessionStartTimes[booking.session_time] || 7;

            // Create datetime for session start
            const sessionStartDateTime = new Date(booking.date + 'T00:00:00');
            sessionStartDateTime.setHours(sessionStartHour, 0, 0, 0);

            // Calculate hours until session starts
            const hoursUntilSession = (sessionStartDateTime - now) / (1000 * 60 * 60);

            let canActuallyCancelNow = false;
            let isWithinCancellationWindow = false;
            let hasSessionPassed = false;

            // Check if session is ongoing
            const todayStr = now.toISOString().split('T')[0];
            const isToday = booking.date === todayStr;

            const isOngoing = isToday && booking.status !== 'cancelled' && (
                (booking.session_time === 'Morning' && currentHour >= 7 && currentHour < 11) ||
                (booking.session_time === 'Afternoon' && currentHour >= 13 && currentHour < 17) ||
                (booking.session_time === 'Evening' && currentHour >= 18 && currentHour < 22)
            );

            // Check if session has ended for today
            const hasSessionEnded = isToday && (
                (booking.session_time === 'Morning' && currentHour >= 11) ||
                (booking.session_time === 'Afternoon' && currentHour >= 17) ||
                (booking.session_time === 'Evening' && currentHour >= 22)
            );

            if (booking.status === 'cancelled') {
                canActuallyCancelNow = false;
                isWithinCancellationWindow = false;
                hasSessionPassed = false;
            } else if (isOngoing) {
                canActuallyCancelNow = false;
                isWithinCancellationWindow = false;
                hasSessionPassed = false;
            } else if (booking.status === 'completed' || hasSessionEnded) {
                canActuallyCancelNow = false;
                hasSessionPassed = true;
            } else {
                // Can only cancel if more than 12 hours before session start
                if (hoursUntilSession > 12) {
                    canActuallyCancelNow = true;
                } else {
                    // Within 12 hours of session - cannot cancel
                    isWithinCancellationWindow = true;
                    canActuallyCancelNow = false;
                }
            }

            return `
                <div class="booking-item ${booking.status === 'cancelled' ? 'cancelled' : ''}">
                    <div class="booking-date-badge">
                        <div class="booking-day">${new Date(booking.date).getDate()}</div>
                        <div class="booking-month">${new Date(booking.date).toLocaleString('en-US', { month: 'short' })}</div>
                    </div>
                    <div class="booking-details">
                        <div class="booking-class">${booking.class_type}</div>
                        <div class="booking-info">
                            <span><i class="fas fa-clock"></i> ${booking.session_time} (${booking.session_hours})</span>
                            <span><i class="fas fa-user"></i> ${booking.trainer_name}</span>
                            <span><i class="fas fa-calendar"></i> ${booking.day_of_week}</span>
                        </div>
                    </div>
                    <div class="booking-actions">
                        ${(() => {
                    if (isOngoing) {
                        return `
                            <div class="booking-status-badge ongoing-badge">
                                <i class="fas fa-play-circle"></i>
                                <span>Ongoing Session</span>
                            </div>
                        `;
                    } else if (booking.status === 'cancelled') {
                        return `
                            <div class="booking-status-badge cancelled-badge">
                                <i class="fas fa-times-circle"></i>
                                <span>Cancelled</span>
                            </div>
                        `;
                    } else if (canActuallyCancelNow) {
                        return `
                            <button class="btn-cancel-booking" onclick="cancelBooking(${booking.id})">
                                <i class="fas fa-times"></i> Cancel
                            </button>
                        `;
                    } else if (isWithinCancellationWindow) {
                        return `
                            <div class="booking-status-badge warning-badge" title="Cannot cancel within 12 hours of session start">
                                <i class="fas fa-lock"></i>
                                <span>Cannot Cancel</span>
                            </div>
                        `;
                    } else {
                        return `
                            <div class="booking-status-badge completed-badge">
                                <i class="fas fa-check-circle"></i>
                                <span>Completed</span>
                            </div>
                        `;
                    }
                })()}
                    </div>
                </div>
            `;
        }).join('');
    }

    // ===================================
    // BOOKING TABS
    // ===================================
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.addEventListener('click', function () {
            const tab = this.getAttribute('data-tab');

            // Update active tab
            document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
            this.classList.add('active');

            // Update active content
            document.querySelectorAll('.bookings-list').forEach(list => list.classList.remove('active'));
            document.getElementById(tab + 'Bookings').classList.add('active');
        });
    });

    // ===================================
    // CANCEL BOOKING
    // ===================================
    window.cancelBooking = function (bookingId) {
        if (!confirm('Are you sure you want to cancel this booking? This action cannot be undone.')) {
            return;
        }

        fetch('api/cancel_booking.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `booking_id=${bookingId}`
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showToast('Booking cancelled successfully', 'success');
                    loadUserBookings();
                    loadWeeklyBookings();
                } else {
                    showToast(data.message || 'Failed to cancel booking', 'error');
                }
            })
            .catch(error => {
                console.error('Error cancelling booking:', error);
                showToast('[CANCEL] An error occurred. Please try again.', 'error');
            });
    };

    // ===================================
    // ===================================
    // CALENDAR RENDERING
    // ===================================

    // Function to check if all sessions are unavailable for a date
    function areAllSessionsUnavailable(dateStr) {
        const date = new Date(dateStr);
        const today = new Date();

        // Only check for today's date
        if (date.toDateString() !== today.toDateString()) {
            return false;
        }

        const currentHour = today.getHours();
        const currentMinute = today.getMinutes();
        const currentTotalMinutes = currentHour * 60 + currentMinute;

        // Session end times in minutes
        const sessionEndTimes = {
            'Morning': 11 * 60,      // 11:00 AM = 660 minutes
            'Afternoon': 17 * 60,    // 5:00 PM = 1020 minutes
            'Evening': 22 * 60       // 10:00 PM = 1320 minutes
        };

        // Check if all sessions have less than 30 minutes remaining
        let allUnavailable = true;
        for (const session in sessionEndTimes) {
            const endMinutes = sessionEndTimes[session];
            const minutesRemaining = endMinutes - currentTotalMinutes;

            if (minutesRemaining >= 30) {
                allUnavailable = false;
                break;
            }
        }

        return allUnavailable;
    }

    function renderCalendar() {
        const calendarTitle = document.getElementById('calendarTitle');
        const calendarDays = document.getElementById('calendarDays');

        const monthNames = ['JANUARY', 'FEBRUARY', 'MARCH', 'APRIL', 'MAY', 'JUNE',
            'JULY', 'AUGUST', 'SEPTEMBER', 'OCTOBER', 'NOVEMBER', 'DECEMBER'];

        calendarTitle.textContent = monthNames[currentMonth];

        const firstDay = new Date(currentYear, currentMonth, 1).getDay();
        const daysInMonth = new Date(currentYear, currentMonth + 1, 0).getDate();
        const daysInPrevMonth = new Date(currentYear, currentMonth, 0).getDate();

        const today = new Date();
        const todayDate = today.getDate();
        const todayMonth = today.getMonth();
        const todayYear = today.getFullYear();

        let html = '';

        // Previous month days (inactive)
        for (let i = firstDay - 1; i >= 0; i--) {
            html += `<div class="schedule-day inactive">
                <span class="day-number">${daysInPrevMonth - i}</span>
            </div>`;
        }

        // Current month days
        for (let day = 1; day <= daysInMonth; day++) {
            const date = new Date(currentYear, currentMonth, day);
            const dateStr = formatDate(date);
            const isPast = date < new Date(todayYear, todayMonth, todayDate);
            const isToday = day === todayDate && currentMonth === todayMonth && currentYear === todayYear;
            const isSelected = bookingState.date === dateStr;

            // Can only book up to 30 days ahead
            const maxDate = new Date();
            maxDate.setDate(maxDate.getDate() + 30);
            const isTooFar = date > maxDate;

            // Check if all sessions are unavailable for this date
            const allSessionsUnavailable = areAllSessionsUnavailable(dateStr);

            let classes = ['schedule-day'];
            if (isToday) classes.push('today');
            if (isSelected) classes.push('selected');
            if (isPast) classes.push('past-date');
            if (isTooFar) classes.push('too-far-advance');
            if (allSessionsUnavailable) classes.push('all-sessions-unavailable');

            const clickable = !isPast && !isTooFar && !allSessionsUnavailable;
            const onClick = clickable ? `onclick="selectDate('${dateStr}')"` : '';

            html += `<div class="${classes.join(' ')}" data-date="${dateStr}" ${onClick}>
                <span class="day-number">${day}</span>
            </div>`;
        }

        // Next month days (inactive)
        const totalCells = firstDay + daysInMonth;
        const remainingCells = 7 - (totalCells % 7);
        if (remainingCells < 7) {
            for (let day = 1; day <= remainingCells; day++) {
                html += `<div class="schedule-day inactive">
                    <span class="day-number">${day}</span>
                </div>`;
            }
        }

        calendarDays.innerHTML = html;
    }

    // Calendar navigation
    document.getElementById('prevMonth').addEventListener('click', function () {
        currentMonth--;
        if (currentMonth < 0) {
            currentMonth = 11;
            currentYear--;
        }
        renderCalendar();
    });

    document.getElementById('nextMonth').addEventListener('click', function () {
        currentMonth++;
        if (currentMonth > 11) {
            currentMonth = 0;
            currentYear++;
        }
        renderCalendar();
    });

    // Month dropdown navigation
    const monthNavBtn = document.getElementById('monthNavBtn');
    const monthDropdown = document.getElementById('monthDropdown');

    if (monthNavBtn && monthDropdown) {
        monthNavBtn.addEventListener('click', function (e) {
            e.stopPropagation();
            monthDropdown.classList.toggle('active');
        });

        // Close dropdown when clicking outside
        document.addEventListener('click', function () {
            monthDropdown.classList.remove('active');
        });

        // Month selection
        document.querySelectorAll('.month-option').forEach(option => {
            option.addEventListener('click', function (e) {
                e.stopPropagation();
                const selectedMonth = parseInt(this.getAttribute('data-month'));
                currentMonth = selectedMonth;
                renderCalendar();
                monthDropdown.classList.remove('active');
            });
        });
    }

    // ===================================
    // STEP 1: DATE SELECTION
    // ===================================
    window.selectDate = function (dateStr) {
        const dateElement = document.querySelector(`[data-date="${dateStr}"]`);

        // Don't allow clicking on past dates or too far dates
        if (dateElement && (dateElement.classList.contains('past-date') ||
            dateElement.classList.contains('too-far-advance') ||
            dateElement.classList.contains('inactive'))) {
            return;
        }

        bookingState.date = dateStr;

        // Check weekly limit for the selected week
        const canBook = checkWeeklyLimitForDate(dateStr);
        if (!canBook) {
            showToast(`The week containing ${new Date(dateStr + 'T00:00:00').toLocaleDateString('en-US', { month: 'short', day: 'numeric' })} is full (${bookingState.weeklyLimit}/${bookingState.weeklyLimit} sessions). Please select a date from another week.`, 'warning', 5000);
            // Still allow selection but show warning
        }

        // Update UI - Remove selected from all schedule-day elements
        document.querySelectorAll('.schedule-day').forEach(day => day.classList.remove('selected'));
        if (dateElement) {
            dateElement.classList.add('selected');
        }

        // Update session availability based on selected date
        if (typeof updateSessionAvailability === 'function') {
            updateSessionAvailability();
        }

        // Check session capacity for the selected date
        checkSessionCapacity();

        // Enable next button in the active step (will be checked again on click)
        const activeStep = document.querySelector('.wizard-step.active');
        const btnNext = activeStep ? activeStep.querySelector('.btn-next') : null;
        if (btnNext) {
            btnNext.disabled = false;
        }
    };

    // ===================================
    // STEP 2: SESSION SELECTION
    // ===================================

    // Function to check if session time has passed for today
    function isSessionPassed(sessionName) {
        if (!bookingState.date) return false;

        const selectedDate = new Date(bookingState.date);
        const today = new Date();

        // Only check for today's date
        if (selectedDate.toDateString() !== today.toDateString()) {
            return false;
        }

        const currentHour = today.getHours();
        const currentMinute = today.getMinutes();

        // Session end times
        const sessionEndTimes = {
            'Morning': 11,      // 11:00 AM
            'Afternoon': 17,    // 5:00 PM
            'Evening': 22       // 10:00 PM
        };

        const endHour = sessionEndTimes[sessionName];
        if (!endHour) return false;

        // Calculate minutes remaining until session ends
        const currentTotalMinutes = currentHour * 60 + currentMinute;
        const endTotalMinutes = endHour * 60;
        const minutesRemaining = endTotalMinutes - currentTotalMinutes;

        // Store whether session has completely passed or just within 30 min
        const hasPassed = minutesRemaining <= 0;
        const isTooClose = minutesRemaining < 30 && minutesRemaining > 0;

        // Store the type of restriction for better messaging
        if (hasPassed) {
            bookingState.sessionRestriction = 'passed';
        } else if (isTooClose) {
            bookingState.sessionRestriction = 'too_close';
        } else {
            bookingState.sessionRestriction = null;
        }

        // Block if less than 30 minutes remaining or session has passed
        return minutesRemaining < 30;
    }

    // Function to check session capacity
    function checkSessionCapacity() {
        if (!bookingState.date) return;

        // Check capacity for all sessions
        const sessions = ['Morning', 'Afternoon', 'Evening'];
        sessions.forEach(session => {
            fetch(`api/get_session_capacity.php?date=${bookingState.date}&session=${session}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const block = document.querySelector(`.session-block[data-session="${session}"]`);
                        if (block && data.is_full) {
                            block.classList.add('session-full');
                            // Add full indicator if not already present
                            if (!block.querySelector('.session-full-badge')) {
                                const badge = document.createElement('span');
                                badge.className = 'session-full-badge';
                                badge.innerHTML = '<i class="fas fa-users"></i> FULL';
                                block.querySelector('.session-header').appendChild(badge);
                            }
                        }
                    }
                })
                .catch(error => console.error(`Error checking ${session} capacity:`, error));
        });
    }

    // Function to update session blocks availability
    function updateSessionAvailability() {
        document.querySelectorAll('.session-block').forEach(block => {
            const session = block.getAttribute('data-session');
            const isPassed = isSessionPassed(session);

            // Remove full badge when checking time-based availability
            const existingBadge = block.querySelector('.session-full-badge');
            if (existingBadge) existingBadge.remove();
            block.classList.remove('session-full');

            if (isPassed) {
                block.classList.add('session-passed');
                block.style.opacity = '0.5';
                block.style.cursor = 'not-allowed';
            } else {
                block.classList.remove('session-passed');
                block.style.opacity = '';
                block.style.cursor = '';
            }
        });
    }

    document.querySelectorAll('.session-block').forEach(block => {
        block.addEventListener('click', function () {
            const session = this.getAttribute('data-session');

            // Check if selected week is full
            if (bookingState.selectedWeekFull) {
                const weekBounds = getWeekBoundaries(bookingState.date);
                const weekStart = new Date(weekBounds.start + 'T00:00:00');
                showToast(`This week (starting ${weekStart.toLocaleDateString('en-US', { month: 'short', day: 'numeric' })}) is full (${bookingState.weeklyLimit}/${bookingState.weeklyLimit} sessions). Please select a date from another week.`, 'warning', 5000);
                return;
            }

            // Check if session time has passed
            if (isSessionPassed(session)) {
                const message = bookingState.sessionRestriction === 'passed'
                    ? 'This session time has already concluded for the selected date. Please choose a future session.'
                    : 'This session is closing soon. Bookings require at least 30 minutes before the session ends.';
                showToast(message, 'warning', 5000);
                return;
            }

            bookingState.session = session;

            document.querySelectorAll('.session-block').forEach(b => b.classList.remove('selected'));
            this.classList.add('selected');

            // Enable Next button in the active step
            const activeStep = document.querySelector('.wizard-step.active');
            const btnNext = activeStep.querySelector('.btn-next');
            if (btnNext) btnNext.disabled = false;
        });
    });

    // ===================================
    // STEP 3: CLASS TYPE SELECTION
    // ===================================
    document.querySelectorAll('.class-card').forEach(card => {
        card.addEventListener('click', function () {
            const classType = this.getAttribute('data-class');
            bookingState.classType = classType;

            document.querySelectorAll('.class-card').forEach(c => c.classList.remove('selected'));
            this.classList.add('selected');

            // Enable Next button in the active step
            const activeStep = document.querySelector('.wizard-step.active');
            const btnNext = activeStep.querySelector('.btn-next');
            if (btnNext) btnNext.disabled = false;
        });
    });

    // ===================================
    // STEP 4: LOAD TRAINERS
    // ===================================
    function loadTrainers() {
        const { date, session, classType } = bookingState;
        const trainersGrid = document.getElementById('trainersGrid');
        const capacityInfo = document.getElementById('facilityCapacityInfo');

        trainersGrid.innerHTML = '<p class="loading-text">Loading trainers...</p>';
        capacityInfo.innerHTML = '<i class="fas fa-spinner fa-spin"></i> <span>Checking availability...</span>';

        fetch(`api/get_available_trainers.php?date=${date}&session=${session}&class=${encodeURIComponent(classType)}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    renderTrainers(data.trainers);
                    updateCapacityInfo(data);
                } else {
                    trainersGrid.innerHTML = `<p class="empty-message">${data.message}</p>`;
                }
            })
            .catch(error => {
                console.error('Error loading trainers:', error);
                trainersGrid.innerHTML = '<p class="empty-message">Failed to load trainers</p>';
            });
    }

    function renderTrainers(trainers) {
        const trainersGrid = document.getElementById('trainersGrid');

        if (trainers.length === 0) {
            trainersGrid.innerHTML = '<p class="empty-message">No trainers available for this session</p>';
            return;
        }

        trainersGrid.innerHTML = trainers.map(trainer => {
            // Escape HTML to prevent attribute breaking with quotes
            const escapedName = trainer.name.replace(/'/g, '&#39;').replace(/\"/g, '&quot;');
            // Always use account icon for trainer avatars
            const photoSrc = `../../images/account-icon.svg`;
            return `
            <div class="trainer-card ${trainer.status}"
                 data-trainer-id="${trainer.id}"
                 data-trainer-name="${escapedName}"
                 data-trainer-status="${trainer.status}"
                 onclick="selectTrainer(${trainer.id}, this.dataset.trainerName, this.dataset.trainerStatus)">
                <span class="trainer-status-badge ${trainer.status}">${trainer.status}</span>
                <img src="${photoSrc}"
                     alt="${escapedName}"
                     class="trainer-photo default-icon"
                     onerror="this.onerror=null; this.src='../../images/account-icon.svg'; this.classList.add('default-icon');">
                <h3 class="trainer-name">${trainer.name}</h3>
                <p class="trainer-specialty">${trainer.specialization}</p>
            </div>
        `;
        }).join('');
    }

    function updateCapacityInfo(data) {
        const capacityInfo = document.getElementById('facilityCapacityInfo');
        const used = data.facility_slots_used || 0;
        const max = data.facility_slots_max || 2;
        const available = data.available_count || 0;

        // Update booking state with capacity info
        bookingState.facilityFull = used >= max;
        bookingState.hasAvailableTrainers = available > 0;

        if (used >= max) {
            capacityInfo.innerHTML = `
                <i class="fas fa-exclamation-triangle"></i>
                <span>Facility at capacity (${used}/${max} trainers booked). Select an already booked trainer to join their session.</span>
            `;
            capacityInfo.style.background = 'rgba(255, 152, 0, 0.1)';
            capacityInfo.style.borderColor = '#ff9800';
            capacityInfo.style.color = '#ff9800';
        } else {
            capacityInfo.innerHTML = `
                <i class="fas fa-check-circle"></i>
                <span>${available} trainers available • Facility capacity: ${used}/${max}</span>
            `;
            capacityInfo.style.background = 'rgba(76, 175, 80, 0.1)';
            capacityInfo.style.borderColor = '#4CAF50';
            capacityInfo.style.color = '#4CAF50';
        }

        // Update wizard step buttons
        updateWizardStep();
    }

    window.selectTrainer = function (trainerId, trainerName, status) {
        if (status === 'unavailable') {
            showToast('This trainer is not available for the selected session', 'warning');
            return;
        }

        if (status === 'booked') {
            showToast('This trainer is already booked for the selected session', 'warning');
            return;
        }

        bookingState.trainerId = trainerId;
        bookingState.trainerName = trainerName;

        document.querySelectorAll('.trainer-card').forEach(card => card.classList.remove('selected'));
        document.querySelector(`[data-trainer-id="${trainerId}"]`).classList.add('selected');

        // Enable Next button in the active step
        const activeStep = document.querySelector('.wizard-step.active');
        const btnNext = activeStep ? activeStep.querySelector('.btn-next') : null;
        if (btnNext) btnNext.disabled = false;
    };

    // ===================================
    // STEP 5: UPDATE SUMMARY
    // ===================================
    function updateSummary() {
        const { date, session, classType, trainerName } = bookingState;

        const sessionHours = {
            'Morning': '7:00 AM - 11:00 AM',
            'Afternoon': '1:00 PM - 5:00 PM',
            'Evening': '6:00 PM - 10:00 PM'
        };

        document.getElementById('summaryDate').textContent = new Date(date).toLocaleDateString('en-US', {
            weekday: 'long',
            year: 'numeric',
            month: 'long',
            day: 'numeric'
        });
        document.getElementById('summarySession').textContent = `${session} (${sessionHours[session]})`;
        document.getElementById('summaryClass').textContent = classType;
        document.getElementById('summaryTrainer').textContent = trainerName;
    }

    // ===================================
    // CONFIRM BOOKING
    // ===================================
    const confirmBtn = document.getElementById('btnConfirmBooking');
    if (confirmBtn && !confirmBtn.hasAttribute('data-listener-attached')) {
        confirmBtn.setAttribute('data-listener-attached', 'true');
        confirmBtn.addEventListener('click', function handleBookingConfirm(e) {
            e.preventDefault(); // Prevent any default behavior

            const { trainerId, classType, date, session } = bookingState;
            const button = this;

            // Prevent double-clicking
            if (button.disabled) {
                return;
            }

            button.disabled = true;
            button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Booking...';

            const formData = new URLSearchParams();
            formData.append('trainer_id', trainerId);
            formData.append('class_type', classType);
            formData.append('booking_date', date);
            formData.append('session_time', session);

            fetch('api/book_session.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: formData
            })
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(data => {
                    console.log('Booking response:', data); // Debug log
                    if (data.success) {
                        console.log('Booking successful, showing toast'); // Debug log
                        showToast('Session booked successfully!', 'success');

                        // Show updated weekly count for the booked week
                        if (data.details && data.details.user_weekly_bookings !== undefined) {
                            const bookedDate = new Date(bookingState.date + 'T00:00:00');
                            const weekBounds = getWeekBoundaries(bookingState.date);
                            const weekStart = new Date(weekBounds.start + 'T00:00:00');
                            const weekEnd = new Date(weekBounds.end + 'T00:00:00');

                            setTimeout(() => {
                                showToast(
                                    `You now have ${data.details.user_weekly_bookings}/12 bookings for the week of ${weekStart.toLocaleDateString('en-US', { month: 'short', day: 'numeric' })} - ${weekEnd.toLocaleDateString('en-US', { month: 'short', day: 'numeric' })}`,
                                    'info',
                                    4000
                                );
                            }, 1000);
                        }

                        // Reset button state BEFORE resetting wizard
                        button.disabled = false;
                        button.innerHTML = '<i class="fas fa-check-circle"></i> Confirm Booking';

                        console.log('Reloading bookings data'); // Debug log
                        // Reload data first
                        loadWeeklyBookings();
                        loadUserBookings();

                        console.log('Attempting to reset wizard'); // Debug log
                        // Reset wizard after a short delay to allow data to load
                        setTimeout(() => {
                            try {
                                resetWizard();
                                console.log('Wizard reset successful'); // Debug log
                            } catch (err) {
                                console.error('Error resetting wizard:', err);
                                // Fallback: just reload the page
                                setTimeout(() => {
                                    window.location.reload();
                                }, 1000);
                            }
                        }, 500);
                    } else {
                        console.log('Booking failed:', data.message); // Debug log
                        showToast(data.message || 'Failed to book session', 'error');
                        button.disabled = false;
                        button.innerHTML = '<i class="fas fa-check-circle"></i> Confirm Booking';
                    }
                })
                .catch(error => {
                    console.error('Error in booking process (catch block):', error);
                    showToast('[BOOKING] An error occurred. Please try again.', 'error');
                    button.disabled = false;
                    button.innerHTML = '<i class="fas fa-check-circle"></i> Confirm Booking';
                });
        });
    }
    
    // ===================================
    // WIZARD NAVIGATION
    // ===================================
    function setupEventListeners() {
        // Add event listeners to all Next and Back buttons
        document.querySelectorAll('.btn-next').forEach(btn => {
            btn.addEventListener('click', nextStep);
        });
        document.querySelectorAll('.btn-back').forEach(btn => {
            btn.addEventListener('click', prevStep);
        });

        // Add event listener for class filter
        const classFilter = document.getElementById('classFilter');
        if (classFilter) {
            classFilter.addEventListener('change', applyBookingsFilter);
        }
    }

    function nextStep() {
        if (bookingState.currentStep < 5) {
            bookingState.currentStep++;
            updateWizardStep();

            // Load trainers when entering step 4
            if (bookingState.currentStep === 4) {
                loadTrainers();
            }

            // Update summary when entering step 5
            if (bookingState.currentStep === 5) {
                updateSummary();
            }
        }
    }

    function prevStep() {
        if (bookingState.currentStep > 1) {
            bookingState.currentStep--;
            updateWizardStep();
        }
    }

    function updateWizardStep() {
        const step = bookingState.currentStep;

        // Update step visibility
        document.querySelectorAll('.wizard-step').forEach((el, index) => {
            el.classList.toggle('active', index + 1 === step);
        });

        // Update all navigation buttons in all steps
        document.querySelectorAll('.btn-back').forEach(btn => {
            btn.style.display = step > 1 ? 'flex' : 'none';
        });

        document.querySelectorAll('.btn-next').forEach(btn => {
            btn.style.display = step < 5 ? 'flex' : 'none';
            btn.disabled = !canProceedFromStep(step);
        });

        // Scroll to top of wizard
        document.querySelector('.booking-wizard').scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    function canProceedFromStep(step) {
        // Check if selected week is full (only if date is selected)
        if (bookingState.date && bookingState.selectedWeekFull) {
            return false;
        }

        switch (step) {
            case 1: return bookingState.date !== null;
            case 2: return bookingState.session !== null;
            case 3:
                // Can proceed only if class is selected AND facility is not full
                if (bookingState.classType === null) return false;
                if (bookingState.facilityFull) return false;
                return true;
            case 4: return bookingState.trainerId !== null;
            case 5: return true;
            default: return false;
        }
    }

    function resetWizard() {
        bookingState.date = null;
        bookingState.session = null;
        bookingState.classType = null;
        bookingState.trainerId = null;
        bookingState.trainerName = null;
        bookingState.currentStep = 1;

        document.querySelectorAll('.selected').forEach(el => el.classList.remove('selected'));

        const selectedDateDisplay = document.getElementById('selectedDateDisplay');
        if (selectedDateDisplay) {
            selectedDateDisplay.style.display = 'none';
        }

        updateWizardStep();
        renderCalendar();
    }

    // ===================================
    // UTILITY FUNCTIONS
    // ===================================
    function formatDate(date) {
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');
        return `${year}-${month}-${day}`;
    }
});
