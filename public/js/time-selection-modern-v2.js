/**
 * Modern Time Selection Module V2
 * Start/End time picker with availability sidebar
 */

// Trainer shift configuration
const TRAINER_SHIFTS = {
    'Morning': { start: '07:00', end: '15:00', breakStart: '12:00', breakEnd: '13:00' },
    'Afternoon': { start: '11:00', end: '19:00', breakStart: '15:00', breakEnd: '16:00' },
    'Night': { start: '14:00', end: '22:00', breakStart: '18:00', breakEnd: '19:00' }
};

/**
 * Initialize time selection interface
 */
function initializeModernTimeSelection() {
    console.log('Initializing modern time selection V2 interface');

    const state = window.bookingState;

    // Note: Pickers will be setup after availability loads
    // Event listeners will be attached in setupTimePickers()
}

/**
 * Load trainer availability and display timeline
 */
function loadModernTrainerAvailability(bookingStateRef) {
    const state = bookingStateRef || window.bookingState;
    console.log('Loading trainer availability:', {
        trainerId: state.trainerId,
        date: state.date
    });

    if (!state.trainerId || !state.date) {
        console.error('Missing trainer ID or date');
        return;
    }

    const banner = document.getElementById('availabilityBanner');
    const timeSelectionLayout = document.getElementById('timeSelectionLayout');

    // Show loading state
    if (banner) {
        banner.innerHTML = `
            <div class="banner-loading">
                <i class="fas fa-spinner fa-spin"></i>
                <span>Loading trainer availability...</span>
            </div>
        `;
        banner.style.display = 'block';
    }

    // Fetch availability from API
    fetch(`api/get_available_trainers.php?date=${state.date}&session=Morning&class=${encodeURIComponent(state.classType)}`)
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            console.log('Availability response:', data);

            if (data.success && data.trainers) {
                const trainer = data.trainers.find(t => t.id == state.trainerId);

                if (trainer) {
                    // Store availability data
                    state.trainerShift = trainer.shift || 'Morning';
                    state.trainerName = trainer.name || '';
                    // Fix: Use 'photo' field and construct proper path
                    const photo = trainer.photo || trainer.avatar || '';
                    state.trainerAvatar = photo && photo !== 'account-icon.svg'
                        ? `/fit-brawl/uploads/trainers/${photo}`
                        : `/fit-brawl/images/account-icon.svg`;
                    state.trainerSpecialization = trainer.specialization || '';
                    state.availableSlots = trainer.available_slots || [];
                    state.currentWeekUsageMinutes = data.current_week_usage_minutes || 0;
                    state.weeklyLimitHours = data.weekly_limit_hours || 48;

                    // Store custom shift times if available, otherwise use defaults
                    if (trainer.shift_start && trainer.shift_end && trainer.break_start && trainer.break_end) {
                        state.customShift = {
                            start: trainer.shift_start,
                            end: trainer.shift_end,
                            breakStart: trainer.break_start,
                            breakEnd: trainer.break_end
                        };
                    } else {
                        state.customShift = null;
                    }

                    console.log('Trainer shift data:', {
                        shift: state.trainerShift,
                        customShift: state.customShift
                    });

                    // Show success banner
                    if (banner) {
                        banner.innerHTML = `
                            <div class="banner-success">
                                <i class="fas fa-check-circle"></i>
                                <span>Trainer available - Select your preferred time</span>
                            </div>
                        `;
                    }

                    // Show time selection layout
                    if (timeSelectionLayout) {
                        timeSelectionLayout.style.display = 'grid';
                    }

                    // Build availability timeline with trainer data
                    buildAvailabilityTimeline(state, trainer);

                    // Setup time pickers with constraints
                    setupTimePickers(state);

                } else {
                    showErrorBanner('Trainer not found in availability data');
                }
            } else {
                showErrorBanner(data.message || 'Failed to load availability');
            }
        })
        .catch(error => {
            console.error('Error fetching availability:', error);
            showErrorBanner('Network error loading availability');
        });
}

/**
 * Build and display availability timeline sidebar
 */
function buildAvailabilityTimeline(state, trainer) {
    const timeline = document.getElementById('availabilityTimeline');
    if (!timeline) return;

    // Use custom shift from DB if available, otherwise use defaults
    const shift = state.customShift || TRAINER_SHIFTS[state.trainerShift] || TRAINER_SHIFTS['Morning'];
    const slots = generateTimeSlots(shift.start, shift.end, 30); // 30-min increments

    console.log('Building timeline with shift:', shift);

    // Add trainer info header
    let html = '';
    if (state.trainerName || state.trainerAvatar) {
        const shiftHours = `${formatTime(shift.start)} - ${formatTime(shift.end)}`;
        const breakHours = `${formatTime(shift.breakStart)} - ${formatTime(shift.breakEnd)}`;

        html += `
            <div class="trainer-info-card">
                <div class="trainer-avatar">
                    ${state.trainerAvatar ?
                        `<img src="${state.trainerAvatar}" alt="${state.trainerName || 'Trainer'}" onerror="this.onerror=null; this.src='/fit-brawl/images/account-icon.svg';" />` :
                        `<i class="fas fa-user-circle"></i>`
                    }
                </div>
                <div class="trainer-details">
                    <div class="trainer-name">${state.trainerName || 'Trainer'}</div>
                    <div class="trainer-shift-badge">
                        <i class="fas fa-clock"></i>
                        ${state.trainerShift.charAt(0).toUpperCase() + state.trainerShift.slice(1)} Shift
                    </div>
                    <div class="trainer-hours">${shiftHours}</div>
                    <div class="trainer-break">Break: ${breakHours}</div>
                </div>
            </div>
        `;
    }

    html += '<div class="timeline-slots">';

    slots.forEach(slot => {
        const status = getSlotStatus(slot, state, shift);
        const iconClass = status === 'available' ? 'fa-check-circle' :
                         status === 'break' ? 'fa-coffee' :
                         status === 'booked' ? 'fa-times-circle' : 'fa-ban';
        const statusClass = `timeline-slot-${status}`;

        html += `
            <div class="timeline-slot ${statusClass}">
                <span class="slot-time">${formatTime(slot)}</span>
                <span class="slot-status">
                    <i class="fas ${iconClass}"></i>
                    ${status === 'break' ? 'Break' : status === 'booked' ? 'Booked' : status === 'unavailable' ? 'Unavailable' : 'Available'}
                </span>
            </div>
        `;
    });

    html += '</div>';
    timeline.innerHTML = html;
}

/**
 * Generate time slots array
 */
function generateTimeSlots(startTime, endTime, intervalMinutes) {
    const slots = [];
    let current = parseTime(startTime);
    const end = parseTime(endTime);

    while (current < end) {
        slots.push(formatTimeFromMinutes(current));
        current += intervalMinutes;
    }

    return slots;
}

/**
 * Check if time is in the past for today's date
 */
function isTimePast(timeString, selectedDate) {
    const today = new Date().toISOString().split('T')[0];
    if (selectedDate !== today) {
        return false; // Not today, allow all times
    }

    const now = new Date();
    const currentMinutes = now.getHours() * 60 + now.getMinutes();

    // Parse time inline
    if (!timeString) return true;
    const parts = timeString.split(':');
    const slotMinutes = parseInt(parts[0]) * 60 + parseInt(parts[1]);

    return slotMinutes <= currentMinutes;
}

/**
 * Get status for a time slot
 */
function getSlotStatus(slotTime, state, shift) {
    // Check if time is in the past (only for today's bookings)
    if (state.date && isTimePast(slotTime, state.date)) {
        return 'unavailable';
    }

    // Check if it's break time
    if (isBreakTime(slotTime, shift)) {
        return 'break';
    }

    // Check if slot is booked
    if (isSlotBooked(slotTime, state.availableSlots)) {
        return 'booked';
    }

    // Check if outside shift hours
    if (!isWithinShift(slotTime, shift)) {
        return 'unavailable';
    }

    return 'available';
}

/**
 * Check if time is during break (within break range, not at break end)
 */
function isBreakTime(time, shift) {
    const timeMinutes = parseTime(time);
    const breakStart = parseTime(shift.breakStart);
    const breakEnd = parseTime(shift.breakEnd);

    // Time is during break if it's >= breakStart and < breakEnd
    return timeMinutes >= breakStart && timeMinutes < breakEnd;
}

/**
 * Check if slot is booked
 */
function isSlotBooked(time, availableSlots) {
    if (!availableSlots || availableSlots.length === 0) return false;

    const timeMinutes = parseTime(time);

    // Check if this time falls within any unavailable slot
    for (const slot of availableSlots) {
        if (slot.status === 'booked' || slot.status === 'unavailable') {
            const slotStart = parseTime(slot.start_time);
            const slotEnd = parseTime(slot.end_time);

            if (timeMinutes >= slotStart && timeMinutes < slotEnd) {
                return true;
            }
        }
    }

    return false;
}

/**
 * Check if time is within shift hours
 */
function isWithinShift(time, shift) {
    const timeMinutes = parseTime(time);
    const shiftStart = parseTime(shift.start);
    const shiftEnd = parseTime(shift.end);

    return timeMinutes >= shiftStart && timeMinutes < shiftEnd;
}

/**
 * Setup time pickers with constraints
 */
function setupTimePickers(state) {
    // Get shift info - use custom shift from DB if available, otherwise use defaults
    const shift = state.customShift || TRAINER_SHIFTS[state.trainerShift] || TRAINER_SHIFTS['Morning'];

    console.log('Setting up time pickers with shift:', shift);

    const startSelect = document.getElementById('startTimeSelect');
    const endSelect = document.getElementById('endTimeSelect');

    if (!startSelect || !endSelect) {
        console.error('Time select elements not found');
        return;
    }

    // Generate start time options
    const startSlots = generateStartTimeOptions(state, shift);
    startSelect.innerHTML = '<option value="">Select start time</option>' + startSlots;

    // Clear and disable end select initially
    endSelect.innerHTML = '<option value="">Select start time first</option>';
    endSelect.disabled = true;

    // Add change listeners
    startSelect.addEventListener('change', (e) => {
        if (e.target.value) {
            handleStartTimeSelect(e.target.value, state);
        } else {
            // User cleared the start time selection
            state.startTime = null;
            state.endTime = null;
            state.duration = null;

            // Reset end time dropdown
            endSelect.innerHTML = '<option value="">Select start time first</option>';
            endSelect.disabled = true;
            endSelect.value = '';

            hideDurationDisplay();
            updateNextButton();
        }
    });

    endSelect.addEventListener('change', (e) => {
        if (e.target.value) {
            handleEndTimeSelect(e.target.value, state);
        } else {
            // User cleared the end time selection
            state.endTime = null;
            state.duration = null;
            hideDurationDisplay();
            updateNextButton();
        }
    });

    console.log('Time dropdowns generated');
}

/**
 * Generate start time dropdown options
 */
function generateStartTimeOptions(state, shift) {
    const slots = generateTimeSlots(shift.start, shift.end, 30);

    let availableCount = 0;
    const options = slots.map(timeStr => {
        const status = getSlotStatus(timeStr, state, shift);
        const isDisabled = status === 'booked' || status === 'break' || status === 'unavailable';
        if (!isDisabled) availableCount++;

        const label = formatTime(timeStr);
        const unavailableText = isDisabled ? ' (unavailable)' : '';

        return `<option value="${timeStr}" ${isDisabled ? 'disabled' : ''}>${label}${unavailableText}</option>`;
    });

    // If no available times, return a helpful message
    if (availableCount === 0) {
        return '<option value="" disabled>No available times (shift ended or fully booked)</option>';
    }

    return options.join('');
}

/**
 * Handle start time selection
 */
function handleStartTimeSelect(timeStr, state) {
    console.log('Start time selected:', timeStr);

    // Store start time
    state.startTime = timeStr;

    // Save state after start time selection
    if (window.BookingRecovery) {
        window.BookingRecovery.saveState(state);
    }

    // Generate end time options - use custom shift if available
    const shift = state.customShift || TRAINER_SHIFTS[state.trainerShift] || TRAINER_SHIFTS['Morning'];
    const endSelect = document.getElementById('endTimeSelect');

    if (endSelect) {
        const endOptions = generateEndTimeOptions(state, shift);
        if (endOptions) {
            endSelect.innerHTML = '<option value="">Select end time</option>' + endOptions;
            endSelect.disabled = false;
        } else {
            endSelect.innerHTML = '<option value="">No available end times</option>';
            endSelect.disabled = true;
        }
    }

    // Clear previous end time
    state.endTime = null;
    state.duration = null;
    hideDurationDisplay();
    updateNextButton();
}

/**
 * Generate end time dropdown options
 */
function generateEndTimeOptions(state, shift) {
    if (!state.startTime) return '';

    const startMinutes = parseTime(state.startTime);
    const minEndMinutes = startMinutes + 30; // Minimum 30 minutes
    const shiftEndMinutes = parseTime(shift.end);

    // Generate slots from 30 min after start to end of shift (inclusive)
    const slots = [];
    for (let time = minEndMinutes; time <= shiftEndMinutes; time += 30) {
        slots.push(formatTimeFromMinutes(time));
    }

    const options = slots.map(timeStr => {
        const endMinutes = parseTime(timeStr);

        // Check if end time falls within break (NOT allowed)
        const isDuringBreak = isBreakTime(timeStr, shift);

        // Check if selecting this end time would conflict with bookings
        const hasConflict = checkTimeRangeConflict(startMinutes, endMinutes, state.availableSlots);

        const isDisabled = isDuringBreak || hasConflict;
        const label = formatTime(timeStr);
        const unavailableText = isDisabled ? ' (unavailable)' : '';

        return `<option value="${timeStr}" ${isDisabled ? 'disabled' : ''}>${label}${unavailableText}</option>`;
    });

    return options.join('');
}

/**
 * Handle end time selection
 */
function handleEndTimeSelect(timeStr, state) {
    console.log('End time selected:', timeStr);

    // Calculate duration
    const startMinutes = parseTime(state.startTime);
    const endMinutes = parseTime(timeStr);
    const durationMinutes = endMinutes - startMinutes;

    console.log('Duration calculated:', durationMinutes, 'minutes');

    if (durationMinutes <= 0) {
        alert('End time must be after start time');
        return;
    }

    // Store end time and duration
    state.endTime = timeStr;
    state.duration = durationMinutes;

    console.log('State updated:', {
        startTime: state.startTime,
        endTime: state.endTime,
        duration: state.duration
    });

    // Save state after time selection
    if (window.BookingRecovery) {
        window.BookingRecovery.saveState(state);
    }

    // Show duration display
    showDurationDisplay(durationMinutes, state);

    // Validate and update next button
    validateTimeSelection(state);
    updateNextButton();

    console.log('Next button should be enabled now');
}

/**
 * Show duration display
 */
function showDurationDisplay(minutes, state) {
    const display = document.getElementById('durationDisplay');
    const valueSpan = document.getElementById('durationValue');
    const weeklyUsageInfo = document.getElementById('weeklyUsageInfo');
    const weeklyUsageText = document.getElementById('weeklyUsageText');

    if (display && valueSpan) {
        const hours = Math.floor(minutes / 60);
        const mins = minutes % 60;
        let durationText = '';

        if (hours > 0) {
            durationText = `${hours} hour${hours > 1 ? 's' : ''}`;
            if (mins > 0) {
                durationText += ` ${mins} minutes`;
            }
        } else {
            durationText = `${mins} minutes`;
        }

        valueSpan.textContent = durationText;
        display.style.display = 'flex';
    }

    // Show weekly usage
    if (weeklyUsageInfo && weeklyUsageText && state.currentWeekUsageMinutes !== undefined) {
        const weeklyLimit = state.weeklyLimitHours || 48;
        const bookingHours = Math.floor(minutes / 60);
        const bookingMins = minutes % 60;
        const currentHours = Math.round(state.currentWeekUsageMinutes / 60 * 10) / 10;
        const newTotalMinutes = state.currentWeekUsageMinutes + minutes;
        const newTotalHours = Math.round(newTotalMinutes / 60 * 10) / 10;
        const remainingHours = Math.max(0, weeklyLimit - newTotalHours);

        // Format booking duration
        const bookingDuration = bookingMins > 0 ? `${bookingHours}h ${bookingMins}m` : `${bookingHours}h`;

        weeklyUsageText.textContent = `This ${bookingDuration} booking will bring you to ${newTotalHours}h of your ${weeklyLimit}h weekly limit (${remainingHours}h remaining)`;
        weeklyUsageInfo.style.display = 'flex';

        // Warn if exceeding limit
        if (newTotalHours > weeklyLimit) {
            weeklyUsageInfo.classList.add('usage-exceeded');
        } else {
            weeklyUsageInfo.classList.remove('usage-exceeded');
        }
    }
}

/**
 * Hide duration display
 */
function hideDurationDisplay() {
    const display = document.getElementById('durationDisplay');
    const weeklyUsageInfo = document.getElementById('weeklyUsageInfo');

    if (display) display.style.display = 'none';
    if (weeklyUsageInfo) weeklyUsageInfo.style.display = 'none';
}

/**
 * Validate time selection
 */
function validateTimeSelection(state) {
    if (!state.startTime || !state.endTime) return false;

    const startMinutes = parseTime(state.startTime);
    const endMinutes = parseTime(state.endTime);
    const durationMinutes = endMinutes - startMinutes;

    // Check minimum duration (30 minutes)
    if (durationMinutes < 30) {
        return false;
    }

    // Check weekly limit (use membership-specific limit)
    const weeklyLimit = state.weeklyLimitHours || 48;
    const weeklyLimitMinutes = weeklyLimit * 60;
    const newTotalMinutes = (state.currentWeekUsageMinutes || 0) + durationMinutes;
    if (newTotalMinutes > weeklyLimitMinutes) {
        return false;
    }

    // Check if times are available (not already booked)
    // Note: Break times are allowed per business logic
    const isAvailable = checkTimeRangeAvailable(startMinutes, endMinutes, state.availableSlots);

    return isAvailable;
}

/**
 * Check if time range conflicts with actual bookings (not breaks)
 */
function checkTimeRangeConflict(startMinutes, endMinutes, availableSlots) {
    if (!availableSlots || availableSlots.length === 0) return false;

    // Check each 30-minute slot in the range
    for (let time = startMinutes; time < endMinutes; time += 30) {
        const timeStr = formatTimeFromMinutes(time);

        // Check if this slot is actually booked (not just break time)
        for (const slot of availableSlots) {
            if (slot.status === 'booked' || slot.status === 'unavailable') {
                const slotStart = parseTime(slot.start_time);
                const slotEnd = parseTime(slot.end_time);

                if (time >= slotStart && time < slotEnd) {
                    return true; // Conflict found
                }
            }
        }
    }

    return false; // No conflict
}

/**
 * Check if time range is available
 */
function checkTimeRangeAvailable(startMinutes, endMinutes, availableSlots) {
    if (!availableSlots || availableSlots.length === 0) return true;

    // Check each slot in the range
    for (let time = startMinutes; time < endMinutes; time += 30) {
        if (isSlotBooked(formatTimeFromMinutes(time), availableSlots)) {
            return false;
        }
    }

    return true;
}

/**
 * Show error banner
 */
function showErrorBanner(message) {
    const banner = document.getElementById('availabilityBanner');
    if (banner) {
        banner.innerHTML = `
            <div class="banner-error">
                <i class="fas fa-exclamation-triangle"></i>
                <span>${message}</span>
            </div>
        `;
        banner.style.display = 'block';
    }
}

/**
 * Update next button state
 */
function updateNextButton() {
    if (typeof window.updateNextButton === 'function') {
        window.updateNextButton();
    }
}

/**
 * Parse time string (HH:MM) to minutes since midnight
 */
function parseTime(timeStr) {
    const [hours, minutes] = timeStr.split(':').map(Number);
    return hours * 60 + minutes;
}

/**
 * Format minutes since midnight to time string (HH:MM)
 */
function formatTimeFromMinutes(minutes) {
    const hours = Math.floor(minutes / 60);
    const mins = minutes % 60;
    return `${String(hours).padStart(2, '0')}:${String(mins).padStart(2, '0')}`;
}

/**
 * Format time for display (e.g., "2:30 PM")
 */
function formatTime(timeStr) {
    const [hours, minutes] = timeStr.split(':').map(Number);
    const period = hours >= 12 ? 'PM' : 'AM';
    const displayHours = hours % 12 || 12;
    return `${displayHours}:${String(minutes).padStart(2, '0')} ${period}`;
}

// Export functions to global scope
if (typeof window !== 'undefined') {
    window.initializeModernTimeSelection = initializeModernTimeSelection;
    window.loadModernTrainerAvailability = loadModernTrainerAvailability;
}
