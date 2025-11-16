document.addEventListener('DOMContentLoaded', function () {
    // Admin booking state - extended from member version
    const bookingState = {
        userId: null,
        userName: null,
        userEmail: null,
        userPlan: null,
        membershipClassTypes: [],
        date: null,
        classType: null,
        trainerId: null,
        trainerName: null,
        startTime: null,
        endTime: null,
        duration: null,
        currentStep: 1,
        weeklyLimit: 48,
        weeklyLimitMinutes: 2880,
        currentWeekUsage: 0,
        currentWeekUsageMinutes: 0,
        selectedWeekUsage: 0,
        trainerShiftInfo: null,
        availableSlots: [],
        overrideWeeklyLimit: false
    };

    // Calendar state
    let currentMonth = new Date().getMonth();
    let currentYear = new Date().getFullYear();

    // Flatpickr instances
    let startTimePicker = null;
    let endTimePicker = null;

    // Initialize
    init();

    function init() {
        setupEventListeners();
        setupUserSelection();
        setupClassSelection();
        setupTrainerSelection();
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
            <i class="fa-solid ${icons[type]}"></i>
            <span>${message}</span>
        `;

        container.appendChild(toast);

        setTimeout(() => toast.classList.add('show'), 10);

        setTimeout(() => {
            toast.classList.remove('show');
            setTimeout(() => toast.remove(), 300);
        }, duration);
    }

    // ===================================
    // USER SELECTION (STEP 1)
    // ===================================
    function setupUserSelection() {
        const searchInput = document.getElementById('userSearch');
        const userCards = document.querySelectorAll('.user-card');

        // Search functionality
        searchInput.addEventListener('input', (e) => {
            const searchTerm = e.target.value.toLowerCase();
            userCards.forEach(card => {
                const username = card.dataset.username.toLowerCase();
                const email = card.dataset.email.toLowerCase();
                const match = username.includes(searchTerm) || email.includes(searchTerm);
                card.style.display = match ? 'block' : 'none';
            });
        });

        // User card selection
        userCards.forEach(card => {
            card.addEventListener('click', () => {
                // Deselect all
                userCards.forEach(c => c.classList.remove('selected'));
                // Select clicked card
                card.classList.add('selected');

                // Store user data
                bookingState.userId = card.dataset.userId;
                bookingState.userName = card.dataset.username;
                bookingState.userEmail = card.dataset.email;
                bookingState.userPlan = card.dataset.plan;
                
                // Parse class types
                const classTypesStr = card.dataset.classTypes;
                if (classTypesStr) {
                    const classTypes = classTypesStr.split(/\s*(?:,|and)\s*/i);
                    bookingState.membershipClassTypes = classTypes.map(t => t.trim()).filter(Boolean);
                } else {
                    bookingState.membershipClassTypes = [];
                }

                // Enable next button
                updateNextButton();
            });
        });
    }

    // ===================================
    // CLASS TYPE SELECTION (STEP 3)
    // ===================================
    function setupClassSelection() {
        const classCards = document.querySelectorAll('.class-card');

        classCards.forEach(card => {
            card.addEventListener('click', () => {
                const selectedClass = card.dataset.class;
                
                // Check if member has access to this class
                if (bookingState.membershipClassTypes.length > 0 &&
                    !bookingState.membershipClassTypes.includes(selectedClass)) {
                    showToast(`Member's plan does not include ${selectedClass}`, 'warning');
                    return;
                }

                // Deselect all
                classCards.forEach(c => c.classList.remove('selected'));
                // Select clicked card
                card.classList.add('selected');

                // Update state
                bookingState.classType = selectedClass;
                updateNextButton();
            });
        });
    }

    // ===================================
    // TRAINER SELECTION (STEP 4)
    // ===================================
    function setupTrainerSelection() {
        // Delegated event listener for dynamically loaded trainer cards
        document.getElementById('trainersGrid').addEventListener('click', (e) => {
            const trainerCard = e.target.closest('.trainer-card');
            if (!trainerCard) return;

            // Deselect all
            document.querySelectorAll('.trainer-card').forEach(c => c.classList.remove('selected'));
            // Select clicked card
            trainerCard.classList.add('selected');

            // Update state
            bookingState.trainerId = trainerCard.dataset.trainerId;
            bookingState.trainerName = trainerCard.dataset.trainerName;
            updateNextButton();
        });
    }

    // ===================================
    // LOAD TRAINERS FOR SELECTED CLASS
    // ===================================
    async function loadTrainers() {
        const grid = document.getElementById('trainersGrid');
        const loading = document.getElementById('loadingTrainers');

        loading.style.display = 'block';
        grid.innerHTML = '';

        try {
            const response = await fetch('../../api/get_trainers.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    class_type: bookingState.classType,
                    date: bookingState.date
                })
            });

            const data = await response.json();

            if (data.success && data.trainers) {
                if (data.trainers.length === 0) {
                    grid.innerHTML = '<p class="no-results">No trainers available for this class type on this day.</p>';
                } else {
                    data.trainers.forEach(trainer => {
                        const card = document.createElement('div');
                        card.className = 'trainer-card';
                        card.dataset.trainerId = trainer.id;
                        card.dataset.trainerName = trainer.name;

                        card.innerHTML = `
                            <div class="trainer-avatar">
                                <img src="${trainer.avatar || '../../../images/account-icon.svg'}" 
                                     alt="${trainer.name}">
                            </div>
                            <div class="trainer-info">
                                <h3>${trainer.name}</h3>
                                <p class="specialization">${trainer.specialization}</p>
                                ${trainer.bio ? `<p class="trainer-bio">${trainer.bio}</p>` : ''}
                            </div>
                        `;

                        grid.appendChild(card);
                    });
                }
            } else {
                grid.innerHTML = '<p class="error-message">Failed to load trainers</p>';
            }
        } catch (error) {
            console.error('Error loading trainers:', error);
            grid.innerHTML = '<p class="error-message">Error loading trainers</p>';
        } finally {
            loading.style.display = 'none';
        }
    }

    // ===================================
    // CALENDAR RENDERING
    // ===================================
    function renderCalendar() {
        const grid = document.getElementById('calendarGrid');
        const monthYear = document.getElementById('currentMonthYear');

        // Clear grid
        grid.innerHTML = '';

        // Update header
        const monthNames = ['January', 'February', 'March', 'April', 'May', 'June',
            'July', 'August', 'September', 'October', 'November', 'December'];
        monthYear.textContent = `${monthNames[currentMonth]} ${currentYear}`;

        // Get first day and number of days
        const firstDay = new Date(currentYear, currentMonth, 1).getDay();
        const daysInMonth = new Date(currentYear, currentMonth + 1, 0).getDate();

        // Day labels
        const dayLabels = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
        dayLabels.forEach(label => {
            const dayLabel = document.createElement('div');
            dayLabel.className = 'calendar-day-label';
            dayLabel.textContent = label;
            grid.appendChild(dayLabel);
        });

        // Empty cells for days before month start
        for (let i = 0; i < firstDay; i++) {
            const emptyDay = document.createElement('div');
            emptyDay.className = 'calendar-day empty';
            grid.appendChild(emptyDay);
        }

        // Calendar days
        const today = new Date();
        today.setHours(0, 0, 0, 0);

        for (let day = 1; day <= daysInMonth; day++) {
            const date = new Date(currentYear, currentMonth, day);
            const dateStr = formatDate(date);

            const dayCell = document.createElement('div');
            dayCell.className = 'calendar-day';
            dayCell.textContent = day;
            dayCell.dataset.date = dateStr;

            // Mark past dates as disabled
            if (date < today) {
                dayCell.classList.add('disabled');
            }

            // Mark selected date
            if (bookingState.date === dateStr) {
                dayCell.classList.add('selected');
            }

            // Click handler
            if (date >= today) {
                dayCell.addEventListener('click', () => selectDate(dateStr, dayCell));
            }

            grid.appendChild(dayCell);
        }
    }

    function selectDate(dateStr, dayCell) {
        // Deselect all days
        document.querySelectorAll('.calendar-day').forEach(d => d.classList.remove('selected'));
        // Select clicked day
        dayCell.classList.add('selected');

        // Update state
        bookingState.date = dateStr;

        // Load weekly usage for this week
        loadWeeklyBookings();

        updateNextButton();
    }

    // ===================================
    // LOAD WEEKLY BOOKINGS
    // ===================================
    async function loadWeeklyBookings() {
        if (!bookingState.userId || !bookingState.date) return;

        try {
            const response = await fetch('../../api/get_weekly_bookings.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    user_id: bookingState.userId,
                    date: bookingState.date
                })
            });

            const data = await response.json();

            if (data.success) {
                bookingState.currentWeekUsage = data.weekly_usage_hours || 0;
                bookingState.currentWeekUsageMinutes = data.weekly_usage_minutes || 0;

                // Display weekly usage
                const display = document.getElementById('weeklyUsageDisplay');
                if (display) {
                    const remaining = bookingState.weeklyLimit - bookingState.currentWeekUsage;
                    display.innerHTML = `
                        <div class="weekly-usage-card">
                            <i class="fa-solid fa-chart-line"></i>
                            <span>Weekly Usage: <strong>${bookingState.currentWeekUsage.toFixed(1)}h / ${bookingState.weeklyLimit}h</strong></span>
                            <span class="remaining">${remaining.toFixed(1)}h remaining</span>
                        </div>
                    `;
                }
            }
        } catch (error) {
            console.error('Error loading weekly bookings:', error);
        }
    }

    // ===================================
    // TIME PICKER INITIALIZATION (STEP 5)
    // ===================================
    function initializeTimePickers() {
        const startTimeInput = document.getElementById('startTime');
        const endTimeInput = document.getElementById('endTime');

        if (!startTimeInput || !endTimeInput) return;

        // Generate time slots (7 AM to 10 PM, 30-min intervals)
        const timeSlots = generateTimeSlots();

        // Initialize start time picker
        startTimePicker = flatpickr(startTimeInput, {
            enableTime: true,
            noCalendar: true,
            dateFormat: "h:i K",
            minuteIncrement: 30,
            minTime: "07:00",
            maxTime: "22:00",
            onChange: function (selectedDates, dateStr) {
                bookingState.startTime = dateStr;
                
                // Update end time minimum
                if (endTimePicker) {
                    const startDate = selectedDates[0];
                    if (startDate) {
                        const minEndTime = new Date(startDate.getTime() + 30 * 60000);
                        endTimePicker.set('minTime', minEndTime);
                    }
                }
                
                updateDurationDisplay();
            }
        });

        // Initialize end time picker
        endTimePicker = flatpickr(endTimeInput, {
            enableTime: true,
            noCalendar: true,
            dateFormat: "h:i K",
            minuteIncrement: 30,
            minTime: "07:30",
            maxTime: "22:00",
            onChange: function (selectedDates, dateStr) {
                bookingState.endTime = dateStr;
                updateDurationDisplay();
            }
        });
    }

    function generateTimeSlots() {
        const slots = [];
        const start = new Date();
        start.setHours(7, 0, 0, 0);

        for (let i = 0; i < 30; i++) { // 7 AM to 10 PM = 15 hours = 30 slots
            const time = new Date(start.getTime() + i * 30 * 60000);
            slots.push(formatTime(time));
        }

        return slots;
    }

    function formatTime(date) {
        let hours = date.getHours();
        const minutes = date.getMinutes();
        const ampm = hours >= 12 ? 'PM' : 'AM';
        hours = hours % 12 || 12;
        const minutesStr = minutes < 10 ? '0' + minutes : minutes;
        return `${hours}:${minutesStr} ${ampm}`;
    }

    // ===================================
    // LOAD TRAINER AVAILABILITY (STEP 5)
    // ===================================
    async function loadTrainerAvailability() {
        if (!bookingState.trainerId || !bookingState.date || !bookingState.classType) return;

        try {
            const response = await fetch('../../api/get_trainer_availability.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    trainer_id: bookingState.trainerId,
                    date: bookingState.date,
                    class_type: bookingState.classType
                })
            });

            const data = await response.json();

            if (data.success) {
                bookingState.availableSlots = data.available_slots || [];
                bookingState.trainerShiftInfo = data.shift_info || null;

                displayShiftInfo();
                displayAvailableSlots();
            } else {
                showToast('Failed to load trainer availability', 'error');
            }
        } catch (error) {
            console.error('Error loading trainer availability:', error);
            showToast('Error loading trainer availability', 'error');
        }
    }

    function displayShiftInfo() {
        const container = document.getElementById('trainerShiftInfo');
        if (!container) return;

        if (!bookingState.trainerShiftInfo) {
            container.innerHTML = '<p class="error-message">No shift information available</p>';
            return;
        }

        const shift = bookingState.trainerShiftInfo;
        const shiftIcons = {
            morning: 'fa-sun',
            afternoon: 'fa-cloud-sun',
            night: 'fa-moon'
        };

        container.innerHTML = `
            <div class="shift-badge">
                <i class="fa-solid ${shiftIcons[shift.shift_type] || 'fa-clock'}"></i>
                <span>${shift.shift_type.charAt(0).toUpperCase() + shift.shift_type.slice(1)} Shift</span>
            </div>
            <div class="shift-hours">
                <i class="fa-solid fa-clock"></i>
                <span>${shift.shift_start} - ${shift.shift_end}</span>
            </div>
            ${shift.break_start && shift.break_end ? `
                <div class="shift-break">
                    <i class="fa-solid fa-mug-hot"></i>
                    <span>Break: ${shift.break_start} - ${shift.break_end}</span>
                </div>
            ` : ''}
        `;
    }

    function displayAvailableSlots() {
        const grid = document.getElementById('slotsGrid');
        if (!grid) return;

        if (!bookingState.availableSlots || bookingState.availableSlots.length === 0) {
            grid.innerHTML = '<p class="no-results">No available slots for this trainer</p>';
            return;
        }

        grid.innerHTML = '';

        bookingState.availableSlots.forEach(slot => {
            const button = document.createElement('button');
            button.className = 'slot-button';
            button.textContent = `${slot.start} - ${slot.end}`;
            
            button.addEventListener('click', () => {
                selectTimeSlot(slot.start, slot.end);
            });

            grid.appendChild(button);
        });
    }

    function selectTimeSlot(startTime, endTime) {
        if (startTimePicker) {
            startTimePicker.setDate(startTime, true);
        }
        if (endTimePicker) {
            endTimePicker.setDate(endTime, true);
        }

        bookingState.startTime = startTime;
        bookingState.endTime = endTime;

        updateDurationDisplay();
    }

    // ===================================
    // DURATION CALCULATOR
    // ===================================
    function updateDurationDisplay() {
        if (!bookingState.startTime || !bookingState.endTime) {
            document.getElementById('bookingInfo').style.display = 'none';
            updateNextButton();
            return;
        }

        // Parse times
        const start = parseTime(bookingState.startTime);
        const end = parseTime(bookingState.endTime);

        if (!start || !end || end <= start) {
            document.getElementById('bookingInfo').style.display = 'none';
            bookingState.duration = null;
            updateNextButton();
            return;
        }

        // Calculate duration in minutes
        const durationMinutes = (end - start) / 1000 / 60;
        bookingState.duration = durationMinutes;

        // Calculate new weekly usage
        const newWeeklyUsageMinutes = bookingState.currentWeekUsageMinutes + durationMinutes;
        const newWeeklyUsageHours = newWeeklyUsageMinutes / 60;

        // Check if override is enabled
        const overrideEnabled = document.getElementById('overrideWeeklyLimit').checked;
        bookingState.overrideWeeklyLimit = overrideEnabled;

        // Display info
        const durationHours = durationMinutes / 60;
        const durationText = durationMinutes < 60 
            ? `${durationMinutes} minutes` 
            : `${durationHours.toFixed(1)} hours`;

        const weeklyUsageText = `${newWeeklyUsageHours.toFixed(1)} / ${bookingState.weeklyLimit}h`;
        const exceedsLimit = newWeeklyUsageMinutes > bookingState.weeklyLimitMinutes && !overrideEnabled;

        document.getElementById('durationDisplay').textContent = durationText;
        document.getElementById('weeklyUsageInfo').innerHTML = weeklyUsageText + 
            (exceedsLimit ? '<span style="color: #dc3545; margin-left: 10px;">Exceeds limit!</span>' : '');

        document.getElementById('bookingInfo').style.display = 'flex';

        updateNextButton();
    }

    function parseTime(timeStr) {
        // Parse "8:00 AM" format to Date
        const match = timeStr.match(/(\d+):(\d+)\s*(AM|PM)/i);
        if (!match) return null;

        let hours = parseInt(match[1]);
        const minutes = parseInt(match[2]);
        const ampm = match[3].toUpperCase();

        if (ampm === 'PM' && hours !== 12) hours += 12;
        if (ampm === 'AM' && hours === 12) hours = 0;

        const date = new Date();
        date.setHours(hours, minutes, 0, 0);
        return date;
    }

    // ===================================
    // WIZARD NAVIGATION
    // ===================================
    function setupEventListeners() {
        // Navigation buttons
        document.getElementById('nextBtn').addEventListener('click', nextStep);
        document.getElementById('prevBtn').addEventListener('click', prevStep);
        document.getElementById('confirmBtn').addEventListener('click', confirmBooking);

        // Calendar navigation
        document.getElementById('prevMonth').addEventListener('click', () => {
            currentMonth--;
            if (currentMonth < 0) {
                currentMonth = 11;
                currentYear--;
            }
            renderCalendar();
        });

        document.getElementById('nextMonth').addEventListener('click', () => {
            currentMonth++;
            if (currentMonth > 11) {
                currentMonth = 0;
                currentYear++;
            }
            renderCalendar();
        });

        // Override checkbox
        document.getElementById('overrideWeeklyLimit').addEventListener('change', (e) => {
            bookingState.overrideWeeklyLimit = e.target.checked;
            updateDurationDisplay();
        });
    }

    function nextStep() {
        if (!canProceedFromStep(bookingState.currentStep)) {
            return;
        }

        bookingState.currentStep++;

        // Execute step-specific actions
        if (bookingState.currentStep === 2) {
            renderCalendar();
            updateSelectedMemberInfo();
        } else if (bookingState.currentStep === 4) {
            loadTrainers();
        } else if (bookingState.currentStep === 5) {
            initializeTimePickers();
            loadTrainerAvailability();
        } else if (bookingState.currentStep === 6) {
            updateSummary();
        }

        updateWizardUI();
    }

    function prevStep() {
        if (bookingState.currentStep > 1) {
            bookingState.currentStep--;
            updateWizardUI();
        }
    }

    function updateWizardUI() {
        // Update step indicators
        document.querySelectorAll('.step-indicator').forEach((indicator, index) => {
            const stepNum = index + 1;
            if (stepNum < bookingState.currentStep) {
                indicator.classList.add('completed');
                indicator.classList.remove('active');
            } else if (stepNum === bookingState.currentStep) {
                indicator.classList.add('active');
                indicator.classList.remove('completed');
            } else {
                indicator.classList.remove('active', 'completed');
            }
        });

        // Show/hide wizard steps
        document.querySelectorAll('.wizard-step').forEach((step, index) => {
            step.classList.toggle('active', index + 1 === bookingState.currentStep);
        });

        // Update navigation buttons
        const prevBtn = document.getElementById('prevBtn');
        const nextBtn = document.getElementById('nextBtn');
        const confirmBtn = document.getElementById('confirmBtn');

        prevBtn.style.display = bookingState.currentStep > 1 ? 'block' : 'none';
        nextBtn.style.display = bookingState.currentStep < 6 ? 'block' : 'none';
        confirmBtn.style.display = bookingState.currentStep === 6 ? 'block' : 'none';

        updateNextButton();
    }

    function canProceedFromStep(step) {
        switch (step) {
            case 1:
                return bookingState.userId !== null;
            case 2:
                return bookingState.date !== null;
            case 3:
                return bookingState.classType !== null;
            case 4:
                return bookingState.trainerId !== null;
            case 5:
                const hasTime = bookingState.startTime && bookingState.endTime && bookingState.duration;
                const withinLimit = bookingState.overrideWeeklyLimit || 
                    (bookingState.currentWeekUsageMinutes + bookingState.duration <= bookingState.weeklyLimitMinutes);
                return hasTime && withinLimit;
            case 6:
                return true;
            default:
                return false;
        }
    }

    function updateNextButton() {
        const nextBtn = document.getElementById('nextBtn');
        const canProceed = canProceedFromStep(bookingState.currentStep);
        nextBtn.disabled = !canProceed;
        nextBtn.style.opacity = canProceed ? '1' : '0.5';
        nextBtn.style.cursor = canProceed ? 'pointer' : 'not-allowed';
    }

    function updateSelectedMemberInfo() {
        const container = document.getElementById('selectedMemberInfo');
        if (!container) return;

        container.innerHTML = `
            <div class="selected-member-card">
                <i class="fa-solid fa-user-check"></i>
                <div>
                    <strong>${bookingState.userName}</strong>
                    <p>${bookingState.userEmail}</p>
                    <span class="membership-badge ${bookingState.userPlan !== 'None' ? 'membership-active' : 'membership-none'}">
                        ${bookingState.userPlan}
                    </span>
                </div>
            </div>
        `;
    }

    function updateSummary() {
        document.getElementById('summaryMember').textContent = bookingState.userName;
        document.getElementById('summaryDate').textContent = formatDateDisplay(bookingState.date);
        document.getElementById('summaryClass').textContent = bookingState.classType;
        document.getElementById('summaryTrainer').textContent = bookingState.trainerName;
        document.getElementById('summaryTime').textContent = `${bookingState.startTime} - ${bookingState.endTime}`;
        
        const durationMinutes = bookingState.duration;
        const durationText = durationMinutes < 60 
            ? `${durationMinutes} minutes` 
            : `${(durationMinutes / 60).toFixed(1)} hours`;
        document.getElementById('summaryDuration').textContent = durationText;
    }

    // ===================================
    // CONFIRM BOOKING
    // ===================================
    async function confirmBooking() {
        const confirmBtn = document.getElementById('confirmBtn');
        confirmBtn.disabled = true;
        confirmBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Booking...';

        try {
            const startDateTime = convertToDateTime(bookingState.date, bookingState.startTime);
            const endDateTime = convertToDateTime(bookingState.date, bookingState.endTime);

            const response = await fetch('../../api/book_session.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    user_id: bookingState.userId,
                    trainer_id: bookingState.trainerId,
                    class_type: bookingState.classType,
                    start_time: startDateTime,
                    end_time: endDateTime,
                    override_weekly_limit: bookingState.overrideWeeklyLimit,
                    csrf_token: getCsrfToken()
                })
            });

            const data = await response.json();

            if (data.success) {
                showToast(`Booking confirmed for ${bookingState.userName}! Weekly usage: ${data.weekly_usage_hours?.toFixed(1)}h`, 'success', 5000);
                
                // Log admin action
                ActivityLogger.log('admin_booking_created', bookingState.userName, data.booking_id,
                    `Created booking: ${bookingState.classType} with ${bookingState.trainerName} on ${bookingState.date} at ${bookingState.startTime}-${bookingState.endTime}`);
                
                setTimeout(() => {
                    window.location.href = 'reservations.php';
                }, 2000);
            } else {
                showToast(data.message || 'Booking failed', 'error');
                confirmBtn.disabled = false;
                confirmBtn.innerHTML = '<i class="fa-solid fa-check"></i> Confirm Booking';
            }
        } catch (error) {
            console.error('Booking error:', error);
            showToast('An error occurred during booking', 'error');
            confirmBtn.disabled = false;
            confirmBtn.innerHTML = '<i class="fa-solid fa-check"></i> Confirm Booking';
        }
    }

    // ===================================
    // UTILITY FUNCTIONS
    // ===================================
    function convertToDateTime(dateStr, timeStr) {
        // Convert "2025-11-17" + "8:00 AM" to "2025-11-17 08:00:00"
        const match = timeStr.match(/(\d+):(\d+)\s*(AM|PM)/i);
        if (!match) return null;

        let hours = parseInt(match[1]);
        const minutes = match[2];
        const ampm = match[3].toUpperCase();

        if (ampm === 'PM' && hours !== 12) hours += 12;
        if (ampm === 'AM' && hours === 12) hours = 0;

        const hoursStr = String(hours).padStart(2, '0');
        return `${dateStr} ${hoursStr}:${minutes}:00`;
    }

    function getCsrfToken() {
        const meta = document.querySelector('meta[name="csrf-token"]');
        return meta ? meta.getAttribute('content') : '';
    }

    function formatDate(date) {
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');
        return `${year}-${month}-${day}`;
    }

    function formatDateDisplay(dateStr) {
        const date = new Date(dateStr + 'T00:00:00');
        const options = { year: 'numeric', month: 'long', day: 'numeric' };
        return date.toLocaleDateString('en-US', options);
    }

    // Mock ActivityLogger for client-side (actual logging happens server-side)
    const ActivityLogger = {
        log: (action, targetUser, targetId, details) => {
            console.log(`[ActivityLogger] ${action}:`, { targetUser, targetId, details });
        }
    };
});
