/**
 * Modern Time Selection System for FitXBrawl
 * Implements professional scheduling interface similar to Calendly/Acuity
 */

// Global variables for time selection (exposed to window for cross-module access)
window.selectedDurationMinutes = 90; // Default 1.5 hours (recommended)
let selectedDurationMinutes = window.selectedDurationMinutes;

/**
 * Reset duration to default value
 */
function resetDurationSelection() {
    selectedDurationMinutes = 90;
    window.selectedDurationMinutes = 90;
    
    // Reset button states
    document.querySelectorAll('.duration-btn').forEach(btn => {
        btn.classList.remove('active');
        if (btn.dataset.duration === '90') {
            btn.classList.add('active');
        }
    });
    
    updateDurationBadge();
}

/**
 * Initialize the modern time selection interface
 */
function initializeModernTimeSelection() {
    console.log('Initializing modern time selection interface');
    
    const state = window.bookingState;
    
    // Reset duration if no time has been selected yet
    if (!state.startTime) {
        resetDurationSelection();
    }
    
    // Setup duration selector buttons
    setupDurationSelector(state);
    
    // Setup change time button
    const btnChangeTime = document.getElementById('btnChangeTime');
    if (btnChangeTime) {
        btnChangeTime.addEventListener('click', () => {
            showTimeSlotSelection(state);
        });
    }
}

/**
 * Setup event listeners for duration selection buttons
 */
function setupDurationSelector(bookingStateRef) {
    const state = bookingStateRef || window.bookingState;
    const durationButtons = document.querySelectorAll('.duration-btn');
    
    durationButtons.forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            
            // Remove active class from all buttons
            durationButtons.forEach(b => b.classList.remove('active'));
            
            // Add active class to clicked button
            btn.classList.add('active');
            
            const duration = btn.dataset.duration;
            
            if (duration === 'custom') {
                showCustomDurationInput(state);
            } else {
                selectedDurationMinutes = parseInt(duration);
                window.selectedDurationMinutes = selectedDurationMinutes;
                updateDurationBadge();
                generateModernTimeSlots(state);
            }
        });
    });
}

/**
 * Show custom duration input prompt
 */
function showCustomDurationInput(bookingStateRef) {
    const state = bookingStateRef || window.bookingState;
    const customDuration = prompt('Enter duration in minutes (minimum 30, maximum 480):', '60');
    if (customDuration) {
        const minutes = parseInt(customDuration);
        if (minutes >= 30 && minutes <= 480) {
            selectedDurationMinutes = minutes;
            window.selectedDurationMinutes = selectedDurationMinutes;
            updateDurationBadge();
            generateModernTimeSlots(state);
            
            // Update the custom button label
            const customBtn = document.querySelector('.duration-btn[data-duration="custom"]');
            if (customBtn) {
                const hours = Math.floor(minutes / 60);
                const mins = minutes % 60;
                let text = '';
                if (hours > 0) text += `${hours}h`;
                if (mins > 0) text += ` ${mins}m`;
                customBtn.querySelector('.duration-time').innerHTML = `<i class="fas fa-sliders-h"></i> ${text}`;
            }
        } else {
            showToast('Duration must be between 30 and 480 minutes (8 hours)', 'warning');
            document.querySelector('.duration-btn[data-duration="90"]').click();
        }
    } else {
        document.querySelector('.duration-btn[data-duration="90"]').click();
    }
}

/**
 * Update the duration badge display
 */
function updateDurationBadge() {
    const badge = document.getElementById('selectedDurationBadge');
    if (badge) {
        const hours = Math.floor(selectedDurationMinutes / 60);
        const mins = selectedDurationMinutes % 60;
        let text = '';
        if (hours > 0) text += `${hours} hour${hours > 1 ? 's' : ''}`;
        if (mins > 0) text += ` ${mins} min`;
        badge.textContent = text.trim();
    }
}

/**
 * Load trainer availability and display results
 */
function loadModernTrainerAvailability(bookingStateRef) {
    // Use the passed bookingState reference
    const state = bookingStateRef || window.bookingState;
    
    console.log('Loading trainer availability:', { trainerId: state.trainerId, date: state.date });
    if (!state.trainerId || !state.date) {
        console.error('Missing trainer ID or date');
        return;
    }

    const banner = document.getElementById('availabilityBanner');
    if (banner) {
        banner.innerHTML = '<div class="banner-loading"><i class="fas fa-spinner fa-spin"></i><span>Loading trainer availability...</span></div>';
    }

    const formData = new FormData();
    formData.append('trainer_id', state.trainerId);
    formData.append('date', state.date);
    formData.append('class_type', state.classType);

    fetch('api/get_trainer_availability.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        console.log('Trainer availability response:', data);
        if (data.success) {
            state.availableSlots = data.available_slots || [];
            state.trainerShiftInfo = data.shift_info;
            
            displayAvailabilityBanner(data.shift_info);
            showDurationSelector();
            generateModernTimeSlots(state);
        } else {
            showAvailabilityError(data.message || 'Failed to load availability');
        }
    })
    .catch(error => {
        console.error('Error loading availability:', error);
        showAvailabilityError('Error loading trainer availability');
    });
}

/**
 * Display trainer availability banner
 */
function displayAvailabilityBanner(shiftInfo) {
    const banner = document.getElementById('availabilityBanner');
    if (!banner) return;

    if (!shiftInfo) {
        banner.innerHTML = '<div class="banner-error"><i class="fas fa-exclamation-circle"></i><span>Trainer not available on this date</span></div>';
        return;
    }

    const shiftIcons = {
        'morning': 'fa-sun',
        'afternoon': 'fa-cloud-sun',
        'night': 'fa-moon'
    };
    const icon = shiftIcons[shiftInfo.shift_type] || 'fa-clock';
    const shiftName = shiftInfo.shift_type.charAt(0).toUpperCase() + shiftInfo.shift_type.slice(1);
    
    let breakInfo = '';
    if (shiftInfo.break_formatted) {
        breakInfo = `<span class="banner-break"><i class="fas fa-coffee"></i> Break: ${shiftInfo.break_formatted}</span>`;
    }

    banner.innerHTML = `
        <div class="banner-success">
            <i class="fas ${icon}"></i>
            <span><strong>${shiftName} Shift</strong> • ${shiftInfo.start_time_formatted} - ${shiftInfo.end_time_formatted}</span>
            ${breakInfo}
        </div>
    `;
    banner.classList.add('loaded');
}

/**
 * Show availability error message
 */
function showAvailabilityError(message) {
    const banner = document.getElementById('availabilityBanner');
    if (banner) {
        banner.innerHTML = `<div class="banner-error"><i class="fas fa-exclamation-circle"></i><span>${message}</span></div>`;
    }
    showToast(message, 'error');
}

/**
 * Show the duration selector section
 */
function showDurationSelector() {
    const durationSelector = document.getElementById('durationSelector');
    if (durationSelector) {
        durationSelector.style.display = 'block';
    }
}

/**
 * Generate and display available time slots based on selected duration
 */
function generateModernTimeSlots(bookingStateRef) {
    const state = bookingStateRef || window.bookingState;
    if (!state.availableSlots || state.availableSlots.length === 0) {
        showNoSlotsMessage();
        return;
    }

    const timeSlotsGrid = document.getElementById('timeSlotsGrid');
    const timeSlotsSection = document.getElementById('timeSlotsSection');
    const timeSlotsEmpty = document.getElementById('timeSlotsEmpty');
    
    if (!timeSlotsGrid || !timeSlotsSection) return;

    // Calculate available start times based on selected duration
    const validSlots = findValidSlots(state.availableSlots, selectedDurationMinutes);

    if (validSlots.length === 0) {
        timeSlotsSection.style.display = 'block';
        timeSlotsGrid.style.display = 'none';
        if (timeSlotsEmpty) timeSlotsEmpty.style.display = 'block';
        return;
    }

    timeSlotsGrid.innerHTML = '';
    timeSlotsGrid.style.display = 'block';
    if (timeSlotsEmpty) timeSlotsEmpty.style.display = 'none';
    timeSlotsSection.style.display = 'block';

    // Create table structure
    const table = document.createElement('table');
    table.className = 'time-slots-table';
    
    // Create table header
    const thead = document.createElement('thead');
    thead.innerHTML = `
        <tr>
            <th><i class="fas fa-play-circle"></i> Start Time</th>
            <th><i class="fas fa-stop-circle"></i> End Time</th>
            <th><i class="fas fa-hourglass-half"></i> Duration</th>
            <th>Action</th>
        </tr>
    `;
    table.appendChild(thead);
    
    // Create table body
    const tbody = document.createElement('tbody');
    validSlots.forEach(slot => {
        const row = document.createElement('tr');
        row.className = 'time-slot-row';
        row.innerHTML = `
            <td class="slot-start-time">
                <span class="time-value">${slot.start_formatted}</span>
            </td>
            <td class="slot-end-time">
                <span class="time-value">${slot.end_formatted}</span>
            </td>
            <td class="slot-duration">
                <span class="duration-badge">${Math.round(slot.duration_minutes)} min</span>
            </td>
            <td class="slot-action">
                <button type="button" class="select-slot-btn">
                    <i class="fas fa-check-circle"></i> Select
                </button>
            </td>
        `;
        
        // Add click handler to the select button
        const selectBtn = row.querySelector('.select-slot-btn');
        selectBtn.addEventListener('click', () => selectModernTimeSlot(slot, state));
        
        tbody.appendChild(row);
    });
    
    table.appendChild(tbody);
    timeSlotsGrid.appendChild(table);
}

/**
 * Find valid time slots that can accommodate the selected duration
 */
function findValidSlots(availableSlots, durationMinutes) {
    const validSlots = [];
    
    // Sort slots by start time
    const sortedSlots = [...availableSlots].sort((a, b) => {
        return new Date(a.start_time) - new Date(b.start_time);
    });

    // For each starting slot, check if we have enough continuous slots
    for (let i = 0; i < sortedSlots.length; i++) {
        const startSlot = sortedSlots[i];
        const startTime = new Date(startSlot.start_time);
        const requiredEndTime = new Date(startTime.getTime() + durationMinutes * 60000);
        
        // Check if we have continuous 30-min slots to cover the duration
        let currentTime = new Date(startTime);
        let canFit = true;
        
        while (currentTime < requiredEndTime) {
            const nextSlotTime = new Date(currentTime.getTime() + 30 * 60000);
            
            // Find if this 30-min slot exists
            const hasSlot = sortedSlots.some(slot => {
                const slotStart = new Date(slot.start_time);
                return Math.abs(slotStart - currentTime) < 60000; // Within 1 minute
            });
            
            if (!hasSlot) {
                canFit = false;
                break;
            }
            
            currentTime = nextSlotTime;
        }
        
        if (canFit) {
            validSlots.push({
                start_datetime: formatDateTimeForDB(startTime),
                end_datetime: formatDateTimeForDB(requiredEndTime),
                start_formatted: formatTime12Hour(startTime),
                end_formatted: formatTime12Hour(requiredEndTime),
                duration_minutes: durationMinutes
            });
        }
    }

    return validSlots;
}

/**
 * Handle time slot selection
 */
function selectModernTimeSlot(slot, bookingStateRef) {
    const state = bookingStateRef || window.bookingState;
    state.startTime = slot.start_datetime;
    state.endTime = slot.end_datetime;
    state.duration = slot.duration_minutes;
    
    showTimeSummary(slot, state);
    if (typeof updateNextButton === 'function') updateNextButton();
}

/**
 * Display selected time summary
 */
function showTimeSummary(slot, bookingStateRef) {
    const state = bookingStateRef || window.bookingState;
    const summary = document.getElementById('timeSummary');
    const summaryTime = document.getElementById('summaryTime');
    const summaryDuration = document.getElementById('summaryDuration');
    const weeklyUsageDisplay = document.getElementById('weeklyUsageDisplay');
    
    if (!summary || !summaryTime || !summaryDuration) return;

    // Hide duration selector and slots grid
    const durationSelector = document.getElementById('durationSelector');
    const timeSlotsSection = document.getElementById('timeSlotsSection');
    if (durationSelector) durationSelector.style.display = 'none';
    if (timeSlotsSection) timeSlotsSection.style.display = 'none';

    // Show summary
    summary.style.display = 'block';
    summaryTime.textContent = `${slot.start_formatted} - ${slot.end_formatted}`;
    
    const hours = Math.floor(slot.duration_minutes / 60);
    const mins = slot.duration_minutes % 60;
    let durationText = '';
    if (hours > 0) durationText += `${hours} hour${hours > 1 ? 's' : ''}`;
    if (mins > 0) durationText += ` ${mins} minutes`;
    summaryDuration.textContent = durationText.trim();

    // Calculate and display weekly usage
    const newUsageMinutes = (state.currentWeekUsageMinutes || 0) + slot.duration_minutes;
    const newUsageHours = (newUsageMinutes / 60).toFixed(1);
    const remainingHours = ((2880 - newUsageMinutes) / 60).toFixed(1);
    
    if (weeklyUsageDisplay) {
        weeklyUsageDisplay.textContent = `${newUsageHours}h (${remainingHours}h remaining)`;
    }
}

/**
 * Show time slot selection interface (hide summary)
 */
function showTimeSlotSelection(bookingStateRef) {
    const state = bookingStateRef || window.bookingState;
    const summary = document.getElementById('timeSummary');
    const durationSelector = document.getElementById('durationSelector');
    const timeSlotsSection = document.getElementById('timeSlotsSection');
    
    if (summary) summary.style.display = 'none';
    if (durationSelector) durationSelector.style.display = 'block';
    if (timeSlotsSection) timeSlotsSection.style.display = 'block';
    
    state.startTime = null;
    state.endTime = null;
    state.duration = null;
    
    if (typeof updateNextButton === 'function') updateNextButton();
}

/**
 * Show message when no slots are available
 */
function showNoSlotsMessage() {
    const timeSlotsSection = document.getElementById('timeSlotsSection');
    if (timeSlotsSection) {
        timeSlotsSection.innerHTML = `
            <div class="no-slots-message">
                <i class="fas fa-calendar-times"></i>
                <p>No available time slots found</p>
                <small>The trainer may be fully booked on this date</small>
            </div>
        `;
        timeSlotsSection.style.display = 'block';
    }
}

/**
 * Format datetime for database (Y-m-d H:i:s)
 */
function formatDateTimeForDB(date) {
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    const hours = String(date.getHours()).padStart(2, '0');
    const minutes = String(date.getMinutes()).padStart(2, '0');
    return `${year}-${month}-${day} ${hours}:${minutes}:00`;
}

/**
 * Format time in 12-hour format with AM/PM
 */
function formatTime12Hour(date) {
    let hours = date.getHours();
    const minutes = date.getMinutes();
    const ampm = hours >= 12 ? 'PM' : 'AM';
    hours = hours % 12 || 12;
    const minutesStr = minutes === 0 ? '00' : String(minutes).padStart(2, '0');
    return `${hours}:${minutesStr} ${ampm}`;
}

// Initialize when DOM is loaded
if (typeof window !== 'undefined') {
    // Expose functions globally for reservations.js to use
    window.initializeModernTimeSelection = initializeModernTimeSelection;
    window.loadModernTrainerAvailability = loadModernTrainerAvailability;
    window.setupDurationSelector = setupDurationSelector;
    window.generateModernTimeSlots = generateModernTimeSlots;
    window.selectModernTimeSlot = selectModernTimeSlot;
    window.showTimeSlotSelection = showTimeSlotSelection;
    
    console.log('Modern time selection module loaded and exposed globally');
}
