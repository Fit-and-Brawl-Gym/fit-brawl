// ===================================
// BOOKING PAGE V2 - SESSION-BASED BOOKING
// ===================================

document.addEventListener('DOMContentLoaded', function () {
    // Booking state
    const bookingState = {
        date: null,
        session: null,
        classType: null,
        trainerId: null,
        trainerName: null,
        currentStep: 1
    };

    // Calendar state
    let currentMonth = new Date().getMonth();
    let currentYear = new Date().getFullYear();

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

                    const weeklyCountEl = document.getElementById('weeklyBookingsCount');
                    const weeklyTextEl = document.getElementById('weeklyProgressText');

                    if (weeklyCountEl) {
                        weeklyCountEl.textContent = count;
                    }
                    if (weeklyTextEl) {
                        weeklyTextEl.textContent = `${remaining} bookings remaining this week`;
                    }
                }
            })
            .catch(error => {
                console.error('Error loading weekly bookings:', error);
            });
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
                    updateBookingCounts(data.summary);
                } else {
                    console.error('API returned error:', data.message);
                    document.getElementById('upcomingBookings').innerHTML =
                        `<p class="empty-message">${data.message || 'Failed to load bookings'}</p>`;
                }
            })
            .catch(error => {
                console.error('Error loading bookings:', error);
                document.getElementById('upcomingBookings').innerHTML =
                    '<p class="empty-message">Failed to load bookings</p>';
            });
    }

    function renderBookings(grouped) {
        console.log('Rendering bookings - upcoming:', grouped.upcoming?.length, 'today:', grouped.today?.length, 'past:', grouped.past?.length); // Debug log

        // Combine today and upcoming bookings for the "Upcoming" tab
        const upcomingList = [...(grouped.today || []), ...(grouped.upcoming || [])];

        // Sort upcoming by date ascending (earliest first), then by session time
        upcomingList.sort((a, b) => {
            const dateCompare = new Date(a.date) - new Date(b.date);
            if (dateCompare !== 0) return dateCompare;

            // If same date, sort by session time (Morning, Afternoon, Evening)
            const sessionOrder = { 'Morning': 1, 'Afternoon': 2, 'Evening': 3 };
            return sessionOrder[a.session_time] - sessionOrder[b.session_time];
        });

        // Sort past bookings by date descending (most recent first)
        const pastList = [...(grouped.past || [])];
        pastList.sort((a, b) => new Date(b.date) - new Date(a.date));

        renderBookingList('upcomingBookings', upcomingList);
        renderBookingList('pastBookings', pastList);
    }

    function renderBookingList(containerId, bookings) {
        const container = document.getElementById(containerId);
        console.log(`Rendering ${containerId}:`, bookings); // Debug log

        if (!bookings || bookings.length === 0) {
            container.innerHTML = '<p class="empty-message">No bookings found</p>';
            return;
        }

        container.innerHTML = bookings.map(booking => `
            <div class="booking-item">
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
                    ${booking.can_cancel ? `
                        <button class="btn-cancel-booking" onclick="cancelBooking(${booking.id})">
                            <i class="fas fa-times"></i> Cancel
                        </button>
                    ` : `
                        <button class="btn-cancel-booking" disabled title="Cannot cancel within 24 hours">
                            <i class="fas fa-ban"></i> Cannot Cancel
                        </button>
                    `}
                </div>
            </div>
        `).join('');
    }

    function updateBookingCounts(summary) {
        const upcomingTotal = (summary.upcoming || 0) + (summary.today || 0);
        document.getElementById('upcomingCount').textContent = upcomingTotal;
        document.getElementById('pastCount').textContent = summary.past || 0;
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
    // CALENDAR RENDERING
    // ===================================
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

            let classes = ['schedule-day'];
            if (isToday) classes.push('today');
            if (isSelected) classes.push('selected');
            if (isPast) classes.push('past-date');
            if (isTooFar) classes.push('too-far-advance');

            const clickable = !isPast && !isTooFar;
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

        // Update UI - Remove selected from all schedule-day elements
        document.querySelectorAll('.schedule-day').forEach(day => day.classList.remove('selected'));
        if (dateElement) {
            dateElement.classList.add('selected');
        }

        // Enable next button
        const btnNext = document.getElementById('btnNext');
        if (btnNext) {
            btnNext.disabled = false;
        }
    };

    // ===================================
    // STEP 2: SESSION SELECTION
    // ===================================
    document.querySelectorAll('.session-block').forEach(block => {
        block.addEventListener('click', function () {
            const session = this.getAttribute('data-session');
            bookingState.session = session;

            document.querySelectorAll('.session-block').forEach(b => b.classList.remove('selected'));
            this.classList.add('selected');

            document.getElementById('btnNext').disabled = false;
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

            document.getElementById('btnNext').disabled = false;
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

        trainersGrid.innerHTML = trainers.map(trainer => `
            <div class="trainer-card ${trainer.status}" 
                 data-trainer-id="${trainer.id}" 
                 data-trainer-name="${trainer.name}"
                 onclick="selectTrainer(${trainer.id}, '${trainer.name}', '${trainer.status}')">
                <span class="trainer-status-badge ${trainer.status}">${trainer.status}</span>
                <img src="../../uploads/trainers/${trainer.photo || 'default-trainer.jpg'}" 
                     alt="${trainer.name}" 
                     class="trainer-photo">
                <h3 class="trainer-name">${trainer.name}</h3>
                <p class="trainer-specialty">${trainer.specialization}</p>
            </div>
        `).join('');
    }

    function updateCapacityInfo(data) {
        const capacityInfo = document.getElementById('facilityCapacityInfo');
        const used = data.facility_slots_used || 0;
        const max = data.facility_slots_max || 2;
        const available = data.available_count || 0;

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
    }

    window.selectTrainer = function (trainerId, trainerName, status) {
        if (status === 'unavailable') {
            showToast('This trainer is not available for the selected session', 'warning');
            return;
        }

        bookingState.trainerId = trainerId;
        bookingState.trainerName = trainerName;

        document.querySelectorAll('.trainer-card').forEach(card => card.classList.remove('selected'));
        document.querySelector(`[data-trainer-id="${trainerId}"]`).classList.add('selected');

        document.getElementById('btnNext').disabled = false;
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

                        // Show updated weekly count
                        if (data.details && data.details.user_weekly_bookings) {
                            setTimeout(() => {
                                showToast(`You now have ${data.details.user_weekly_bookings}/12 bookings this week`, 'info');
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
        document.getElementById('btnNext').addEventListener('click', nextStep);
        document.getElementById('btnBack').addEventListener('click', prevStep);
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

        // Update navigation buttons
        const btnBack = document.getElementById('btnBack');
        const btnNext = document.getElementById('btnNext');

        btnBack.style.display = step > 1 ? 'flex' : 'none';
        btnNext.style.display = step < 5 ? 'flex' : 'none';

        // Disable next button by default (user must make selection)
        btnNext.disabled = !canProceedFromStep(step);

        // Scroll to top of wizard
        document.querySelector('.booking-wizard').scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    function canProceedFromStep(step) {
        switch (step) {
            case 1: return bookingState.date !== null;
            case 2: return bookingState.session !== null;
            case 3: return bookingState.classType !== null;
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
