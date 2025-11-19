/**
 * Modern Time Selection for Reschedule Modal
 * Mirrors time-selection-modern-v2.js functionality
 */

window.RESCHEDULE_TEST = 'LOADED';
console.log('ðŸ”¥ RESCHEDULE JS FILE STARTED LOADING');

const RESCHEDULE_TRAINER_SHIFTS = {
    'Morning': { start: '07:00', end: '15:00', breakStart: '12:00', breakEnd: '13:00' },
    'Afternoon': { start: '11:00', end: '19:00', breakStart: '15:00', breakEnd: '16:00' },
    'Night': { start: '14:00', end: '22:00', breakStart: '18:00', breakEnd: '19:00' }
};

let currentRescheduleBooking = null;
let rescheduleState = {
    startTime: null,
    endTime: null,
    duration: null,
    trainerShift: 'Morning',
    customShift: null,
    availableSlots: [], 
    currentWeekUsageMinutes: 0,
    weeklyLimitHours: 48
};

window.rescheduleSelectedTrainerName = null;
window.rescheduleSelectedTrainerShift = null;
window.rescheduleSelectedTrainerShiftData = null;
// ===================================
// RESCHEDULE MODAL FUNCTIONS
// ===================================
function resetRescheduleModal() {
    // Reset form
    const form = document.getElementById('rescheduleForm');
    if (form) form.reset();

    // Hide all optional sections
    const elementsToHide = [
        'rescheduleTimeSummary',
        'rescheduleTimeSelectionLayout',
        'rescheduleAvailabilityBanner',
        'rescheduleDurationDisplay',
        'rescheduleWeeklyUsageInfo'
    ];
    elementsToHide.forEach(id => {
        const el = document.getElementById(id);
        if (el) el.style.display = 'none';
    });

    // Reset time selectors
    const startTimeSelect = document.getElementById('rescheduleStartTimeSelect');
    const endTimeSelect = document.getElementById('rescheduleEndTimeSelect');
    if (startTimeSelect) startTimeSelect.innerHTML = '<option value="">Select start time</option>';
    if (endTimeSelect) {
        endTimeSelect.innerHTML = '<option value="">Select start time first</option>';
        endTimeSelect.disabled = true;
    }

    // Reset trainer grid
    const trainersGrid = document.getElementById('rescheduleTrainersGrid');
    if (trainersGrid) {
        trainersGrid.innerHTML = '<p class="loading-text">Choose a date and class...</p>';
    }
}

// OPEN modal
console.log('ðŸ”¥ DEFINING openRescheduleModal FUNCTION');
window.openRescheduleModal = function(bookingId, element) {
    console.log('ðŸ”¥ openRescheduleModal CALLED');
    const bookingRow = element.closest('.booking-row');
    if (!bookingRow) {
        return;
    }

    const dateCell = bookingRow.querySelector('.booking-date-cell');
    const classCell = bookingRow.querySelector('.booking-class-cell');
    const trainerCell = bookingRow.querySelector('.booking-trainer-cell');
    const timeCell = bookingRow.querySelector('.booking-time-cell');

    // Store booking data
    currentRescheduleBooking = {
        id: bookingId,
        date: dateCell ? dateCell.textContent.trim() : '',
        class: classCell ? classCell.textContent.trim() : '',
        trainer: trainerCell ? trainerCell.textContent.trim() : '',
        time: timeCell ? timeCell.textContent.trim() : ''
    };


    // Populate original booking details
    const originalDateTimeEl = document.getElementById('originalDateTime');
    const originalTrainerEl = document.getElementById('originalTrainer');
    const originalClassEl = document.getElementById('originalClass');

    if (originalDateTimeEl) originalDateTimeEl.textContent = `${currentRescheduleBooking.date} ${currentRescheduleBooking.time}`;
    if (originalTrainerEl) originalTrainerEl.textContent = currentRescheduleBooking.trainer;
    if (originalClassEl) originalClassEl.textContent = currentRescheduleBooking.class;

    // Populate current booking summary
    populateCurrentBookingSummary();

    // Reset form
    const form = document.getElementById('rescheduleForm');
    if (form) form.reset();

    // Hide alert
    hideRescheduleAlert();

    // Hide all optional sections
    const elementsToHide = [
        'rescheduleTimeSummary',
        'rescheduleTimeSelectionLayout',
        'rescheduleAvailabilityBanner',
        'rescheduleDurationDisplay',
        'rescheduleWeeklyUsageInfo'
    ];

    elementsToHide.forEach(id => {
        const el = document.getElementById(id);
        if (el) {
            el.style.setProperty('display', 'none', 'important');
            el.classList.add('hidden');
        }
    });

    // Reset time selectors
    const startTimeSelect = document.getElementById('rescheduleStartTimeSelect');
    const endTimeSelect = document.getElementById('rescheduleEndTimeSelect');
    if (startTimeSelect) startTimeSelect.innerHTML = '<option value="">Select start time</option>';
    if (endTimeSelect) {
        endTimeSelect.innerHTML = '<option value="">Select start time first</option>';
        endTimeSelect.disabled = true;
    }

    // Reset trainer grid
    const trainersGrid = document.getElementById('rescheduleTrainersGrid');
    if (trainersGrid) {
        trainersGrid.innerHTML = '<p class="loading-text">Choose a date and class...</p>';
        document.querySelectorAll('#rescheduleTrainersGrid .trainer-card').forEach(card => {
            card.classList.remove('selected');
        });
    }

    // Set minimum date to today (disable past dates)
    const dateInput = document.getElementById('rescheduleDate');
    if (dateInput) {
        const today = new Date().toISOString().split('T')[0];
        dateInput.setAttribute('min', today);
        
        // Pre-fill with current booking date if it's in the future
        if (currentRescheduleBooking && currentRescheduleBooking.date) {
            // Parse the date from "December 25, 2025" format to "YYYY-MM-DD"
            const parsedDate = parseBookingDateToISO(currentRescheduleBooking.date);
            if (parsedDate && parsedDate >= today) {
                dateInput.value = parsedDate;
            } else {
                dateInput.value = today; // Default to today if current booking is in past
            }
        }
    }

    // Populate class options
    loadRescheduleClassOptions();
    
    // Pre-select the current class type after options are loaded
    setTimeout(() => {
        const classSelect = document.getElementById('rescheduleClass');
        if (classSelect && currentRescheduleBooking && currentRescheduleBooking.class) {
            classSelect.value = currentRescheduleBooking.class;
            // Trigger change to load trainers
            if (dateInput && dateInput.value) {
                loadRescheduleTrainersAndAvailability();
            }
        }
    }, 100);

    // Show modal
    const modal = document.getElementById('rescheduleModal');
    if (modal) {
        modal.style.display = 'flex';
        document.body.classList.add('modal-open');
    } else {
    }
};

window.closeRescheduleModal = function() {
    const modal = document.getElementById('rescheduleModal');
    if (modal) {
        modal.style.display = 'none';
        document.body.classList.remove('modal-open');
    }
    currentRescheduleBooking = null;
    showRescheduleSelectionContent()
};

function loadRescheduleClassOptions() {
    const classSelect = document.getElementById('rescheduleClass');
    if (!classSelect) {
        return;
    }
    
    classSelect.innerHTML = '<option value="">Choose a class...</option>';

    const classFilter = document.getElementById('classFilter');
    if (!classFilter) {
        return;
    }

    const classOptions = classFilter.querySelectorAll('option');
    classOptions.forEach((option, idx) => {
        if (idx > 0 && option.value !== 'all') {
            const opt = document.createElement('option');
            opt.value = option.value;
            opt.textContent = option.value;
            classSelect.appendChild(opt);
        }
    });
}

// ===== Hide all selection content =====
function hideRescheduleSelectionContent() {

    const form = document.getElementById('rescheduleForm');
    if (!form) return;

    const formGroups = form.querySelectorAll('.form-group');
    const formActions = form.querySelector('.form-actions');

    formGroups.forEach(group => {
        const isReason = group.classList.contains('reason-section') || (group.querySelector('label')?.textContent.includes('Reason'));
        if (isReason) {
            group.style.display = 'block'; // Keep reason visible
        } else {
            group.style.display = 'none'; // Hide everything else
        }
    });
    document.querySelector('.original-booking-info').style.display = 'none';
    // Hide form actions except submit/cancel if you want, or hide all
    if (formActions) formActions.style.display = 'none';

    // Show summary
    const summary = document.getElementById('rescheduleTimeSummary');
    if (summary) {
        summary.classList.remove('hidden');
        summary.style.setProperty('display', 'block', 'important');
    }
}   

// ===== Proceed to Review Step =====
window.proceedToReview = function() {
    
    if (!rescheduleState.startTime || !rescheduleState.endTime) {
        showToast('Please select both start and end times', 'warning');
        return;
    }
    
    // Update summary with selected time info
    showRescheduleTimeSummary(rescheduleState);
    
    // Hide time selection and show summary
    hideRescheduleSelectionContent();
    
};

// ===== Show all selection content =====
function showRescheduleSelectionContent() {
    const form = document.getElementById('rescheduleForm');
    const formGroups = form.querySelectorAll('.form-group');
    const formActions = form.querySelector('.form-actions');
    
    // Show all form groups by removing inline styles
    formGroups.forEach(group => {
        group.style.display = ''; // <-- use CSS from stylesheet
    });

    // Show form actions (Keep cancel button visible)
    if (formActions) formActions.style.display = 'flex';

    // Hide summary
    const summary = document.getElementById('rescheduleTimeSummary');
    if (summary) {
        summary.classList.add('hidden');
        summary.style.setProperty('display', 'none', 'important');
    }

    // Show current booking info
    document.querySelector('.original-booking-info').style.display = '';

    // Keep existing time selections instead of resetting
    const startTimeSelect = document.getElementById('rescheduleStartTimeSelect');
    const endTimeSelect = document.getElementById('rescheduleEndTimeSelect');
    
    // Restore state values to selectors if they exist
    if (startTimeSelect && rescheduleState.startTime) {
        startTimeSelect.value = rescheduleState.startTime;
    }
    if (endTimeSelect && rescheduleState.endTime) {
        endTimeSelect.value = rescheduleState.endTime;
        endTimeSelect.disabled = false;
    }
    
    // Show Next button if both times are selected
    const nextBtn = document.getElementById('rescheduleNextBtn');
    if (nextBtn && rescheduleState.startTime && rescheduleState.endTime) {
        nextBtn.style.display = 'inline-flex';
        nextBtn.disabled = false;
    }
}


function handleRescheduleEndTimeSelect(timeStr, state) {

    const startMinutes = parseRescheduleTime(state.startTime);
    const endMinutes = parseRescheduleTime(timeStr);
    const durationMinutes = endMinutes - startMinutes;

    if (durationMinutes <= 0) {
        showToast('End time must be after start time', 'warning');
        return;
    }

    state.endTime = timeStr;
    state.duration = durationMinutes;

    showRescheduleDurationDisplay(durationMinutes, state);
    
    // Show Next button when time selection is complete
    const nextBtn = document.getElementById('rescheduleNextBtn');
    if (nextBtn) {
        nextBtn.style.display = 'inline-flex';
    }
}

function showRescheduleTimeSummary(state) {
    const summary = document.getElementById('rescheduleTimeSummary');
    const summaryTime = document.getElementById('rescheduleSummaryTime');
    const summaryDuration = document.getElementById('rescheduleSummaryDuration');
    const weeklyUsageDisplay = document.getElementById('rescheduleWeeklyUsageDisplay');

    if (!summary || !summaryTime || !summaryDuration) return;

    const timeSelection = document.getElementById('rescheduleTimeSelectionLayout');
    if (timeSelection) timeSelection.style.display = 'none';

    summary.style.display = 'block';
    summaryTime.textContent = `${formatRescheduleTime(state.startTime)} - ${formatRescheduleTime(state.endTime)}`;

    const hours = Math.floor(state.duration / 60);
    const mins = state.duration % 60;
    let durationText = '';
    if (hours > 0) durationText += `${hours} hour${hours > 1 ? 's' : ''}`;
    if (mins > 0) durationText += ` ${mins} minutes`;
    summaryDuration.textContent = durationText.trim();

    const newTotalMinutes = state.currentWeekUsageMinutes + state.duration;
    const newTotalHours = Math.round(newTotalMinutes / 60 * 10) / 10;
    const weeklyLimit = state.weeklyLimitHours || 48;
    const remainingHours = Math.max(0, weeklyLimit - newTotalHours);

    if (weeklyUsageDisplay) {
        weeklyUsageDisplay.textContent = `${newTotalHours}h / ${weeklyLimit}h (${remainingHours}h remaining)`;
    }
}

// Helper function to parse booking date (e.g., "December 25, 2025") to ISO format ("2025-12-25")
function parseBookingDateToISO(dateString) {
    try {
        // Try parsing formats like "December 25, 2025" or "Dec 25, 2025"
        const date = new Date(dateString);
        if (!isNaN(date.getTime())) {
            const year = date.getFullYear();
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const day = String(date.getDate()).padStart(2, '0');
            return `${year}-${month}-${day}`;
        }
    } catch (e) {
    }
    return null;
}

// Helper function to parse booking date (e.g., "December 25, 2025") to ISO format ("2025-12-25")
function parseBookingDateToISO(dateString) {
    try {
        // Try parsing formats like "December 25, 2025" or "Dec 25, 2025"
        const date = new Date(dateString);
        if (!isNaN(date.getTime())) {
            const year = date.getFullYear();
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const day = String(date.getDate()).padStart(2, '0');
            return `${year}-${month}-${day}`;
        }
    } catch (e) {
    }
    return null;
}

// Helper function to check if time is in the past for today's date
function isRescheduleTimePast(timeString, selectedDate) {
    const today = new Date().toISOString().split('T')[0];
    if (selectedDate !== today) {
        return false; // Not today, allow all times
    }
    
    const now = new Date();
    const currentMinutes = now.getHours() * 60 + now.getMinutes();
    
    // Parse time inline (can't use parseRescheduleTime as it's defined later)
    if (!timeString) return true; // If no time string, consider it past
    const parts = timeString.split(':');
    const slotMinutes = parseInt(parts[0]) * 60 + parseInt(parts[1]);
    
    return slotMinutes <= currentMinutes;
}

// Event listeners for date and class changes
document.addEventListener('DOMContentLoaded', function() {
    const rescheduleDate = document.getElementById('rescheduleDate');
    const rescheduleClass = document.getElementById('rescheduleClass');
    const rescheduleForm = document.getElementById('rescheduleForm');

    if (rescheduleDate) {
        rescheduleDate.addEventListener('change', function() {
            document.querySelectorAll('#rescheduleTrainersGrid .trainer-card').forEach(card => {
                card.classList.remove('selected');
            });
            const trainerInput = document.getElementById('rescheduleTrainerInput');
            if (trainerInput) trainerInput.value = '';
            
            document.getElementById('rescheduleTimeSelectionLayout').style.display = 'none';
            document.getElementById('rescheduleAvailabilityBanner').style.display = 'none';
            
            loadRescheduleTrainersAndAvailability();
        });
    }

    if (rescheduleClass) {
        rescheduleClass.addEventListener('change', function() {
            document.querySelectorAll('#rescheduleTrainersGrid .trainer-card').forEach(card => {
                card.classList.remove('selected');
            });
            const trainerInput = document.getElementById('rescheduleTrainerInput');
            if (trainerInput) trainerInput.value = '';
            
            document.getElementById('rescheduleTimeSelectionLayout').style.display = 'none';
            document.getElementById('rescheduleAvailabilityBanner').style.display = 'none';
            
            loadRescheduleTrainersAndAvailability();
        });
    }

    if (rescheduleForm) {
        rescheduleForm.addEventListener('submit', handleRescheduleFormSubmit);
    }

    const rescheduleChangeTime = document.getElementById('rescheduleChangeTime');
    if (rescheduleChangeTime) {
        rescheduleChangeTime.addEventListener('click', function(e) {
            e.preventDefault();
            hideRescheduleAlert();
            showRescheduleSelectionContent();
        });
    }

    // Close modal when clicking outside
    const modal = document.getElementById('rescheduleModal');
    if (modal) {
        modal.addEventListener('click', function(e) {
            if (e.target === modal) {
                closeRescheduleModal();
            }
        });
    }
});

// Load trainers for reschedule
function loadRescheduleTrainersAndAvailability() {
    const date = document.getElementById('rescheduleDate').value;
    const classType = document.getElementById('rescheduleClass').value;
    const trainersGrid = document.getElementById('rescheduleTrainersGrid');

    if (!trainersGrid) {
        return;
    }

    if (!date || !classType) {
        trainersGrid.innerHTML = '<p class="empty-message">Choose a date and class first</p>';
        document.getElementById('rescheduleTimeSelectionLayout').style.display = 'none';
        document.getElementById('rescheduleAvailabilityBanner').style.display = 'none';
        return;
    }

    trainersGrid.innerHTML = '<p class="loading-text"><i class="fas fa-spinner fa-spin"></i> Loading trainers...</p>';

    fetch(`api/get_available_trainers.php?date=${date}&session=Morning&class=${encodeURIComponent(classType)}`)
        .then(res => res.json())
        .then(data => {
            if (data.success && data.trainers && data.trainers.length > 0) {
                renderRescheduleTrainers(data.trainers);
            } else {
                trainersGrid.innerHTML = '<p class="empty-message">No trainers available for this date and class</p>';
                document.getElementById('rescheduleTimeSelectionLayout').style.display = 'none';
            }
        })
        .catch(err => {
            trainersGrid.innerHTML = '<p class="empty-message">Error loading trainers</p>';
        });
}

// Helper function to check if trainer's shift has ended for today
function isRescheduleTrainerShiftEnded(trainer, selectedDate) {
    const today = new Date().toISOString().split('T')[0];
    if (selectedDate !== today) {
        return false; // Not today, shifts are valid
    }

    // Get shift end time
    let shiftEnd = trainer.shift_end;
    if (!shiftEnd) {
        const defaultShifts = {
            'Morning': '15:00',
            'Afternoon': '19:00',
            'Night': '22:00'
        };
        shiftEnd = defaultShifts[trainer.shift] || '22:00';
    }

    // Parse shift end time to minutes
    const parts = shiftEnd.split(':');
    const shiftEndMinutes = parseInt(parts[0]) * 60 + parseInt(parts[1]);

    // Get current time in minutes
    const now = new Date();
    const currentMinutes = now.getHours() * 60 + now.getMinutes();

    return currentMinutes >= shiftEndMinutes;
}

// Helper function to check if trainer's shift start time has passed for today
function isRescheduleTrainerShiftStartPassed(trainer, selectedDate) {
    const today = new Date().toISOString().split('T')[0];
    if (selectedDate !== today) {
        return true; // Not today, shift times are valid (future dates)
    }

    // Get shift start time
    let shiftStart = trainer.shift_start;
    if (!shiftStart) {
        const defaultShifts = {
            'Morning': '07:00',
            'Afternoon': '11:00',
            'Night': '14:00'
        };
        shiftStart = defaultShifts[trainer.shift] || '07:00';
    }

    // Parse shift start time to minutes
    const parts = shiftStart.split(':');
    const shiftStartMinutes = parseInt(parts[0]) * 60 + parseInt(parts[1]);

    // Get current time in minutes
    const now = new Date();
    const currentMinutes = now.getHours() * 60 + now.getMinutes();

    return currentMinutes >= shiftStartMinutes;
}

// Helper function to format shift time display
function formatRescheduleShiftTime(time) {
    if (!time) return '';
    const parts = time.split(':');
    const hours = parseInt(parts[0]);
    const minutes = parts[1];
    const period = hours >= 12 ? 'PM' : 'AM';
    const displayHours = hours % 12 || 12;
    return `${displayHours}:${minutes} ${period}`;
}

function renderRescheduleTrainers(trainers) {
    const trainersGrid = document.getElementById('rescheduleTrainersGrid');

    if (!trainersGrid) {
        return;
    }

    if (!trainers || trainers.length === 0) {
        trainersGrid.innerHTML = '<p class="empty-message">No trainers available</p>';
        return;
    }

    const selectedDate = document.getElementById('rescheduleDate')?.value;

    trainersGrid.innerHTML = trainers.map(trainer => {
        // Debug: Log trainer data to see what we're working with
        console.log(`🔍 Trainer ${trainer.name}:`, {
            status: trainer.status,
            has_available_slots: trainer.has_available_slots,
            booked_slot_count: trainer.booked_slot_count,
            trainer_obj: trainer
        });
        
        // Check if shift has ended for today
        const shiftEnded = isRescheduleTrainerShiftEnded(trainer, selectedDate);
        // Check if shift start time has passed
        const shiftStartPassed = isRescheduleTrainerShiftStartPassed(trainer, selectedDate);
        // Check if trainer is fully booked (no available slots)
        // Check multiple indicators: explicit fully-booked status, has_available_slots flag, or booked_slot_count
        const isFullyBooked = trainer.status === 'fully-booked' || 
                             trainer.has_available_slots === false || 
                             (trainer.booked_slot_count > 0 && !trainer.has_available_slots);
        
        console.log(`   → shiftEnded: ${shiftEnded}, shiftStartPassed: ${shiftStartPassed}, isFullyBooked: ${isFullyBooked}`);
        
        // Mark unavailable if shift ended OR if shift start time hasn't passed yet (no available times)
        let effectiveStatus = (shiftEnded || !shiftStartPassed) ? 'unavailable' : trainer.status;
        // Override with fully-booked if applicable and not already unavailable
        if (isFullyBooked && effectiveStatus !== 'unavailable') {
            effectiveStatus = 'fully-booked';
        }
        
        console.log(`   → Final effectiveStatus: ${effectiveStatus}`);
        
        const escapedName = trainer.name.replace(/'/g, '&#39;').replace(/\"/g, '&quot;');
        const photoSrc = trainer.photo && trainer.photo !== 'account-icon.svg'
            ? `../../uploads/trainers/${trainer.photo}`
            : `../../images/account-icon.svg`;
        
        // Format shift times
        let shiftTimeDisplay = '';
        if (trainer.shift_start && trainer.shift_end) {
            shiftTimeDisplay = `<p class="trainer-shift-time"><i class="fas fa-clock"></i> ${formatRescheduleShiftTime(trainer.shift_start)} - ${formatRescheduleShiftTime(trainer.shift_end)}</p>`;
        } else {
            const defaultShifts = {
                'Morning': { start: '07:00', end: '15:00' },
                'Afternoon': { start: '11:00', end: '19:00' },
                'Night': { start: '14:00', end: '22:00' }
            };
            const shift = defaultShifts[trainer.shift] || defaultShifts['Morning'];
            shiftTimeDisplay = `<p class="trainer-shift-time"><i class="fas fa-clock"></i> ${formatRescheduleShiftTime(shift.start)} - ${formatRescheduleShiftTime(shift.end)}</p>`;
        }
        
        let statusText = 'Available';
        if (shiftEnded) {
            statusText = 'Shift Ended';
        } else if (!shiftStartPassed) {
            statusText = 'Unavailable';
        } else if (effectiveStatus === 'fully-booked') {
            statusText = 'Fully Booked';
        } else {
            statusText = trainer.status.charAt(0).toUpperCase() + trainer.status.slice(1);
        }
        
        return `
            <div class="trainer-card ${effectiveStatus}"
                 data-trainer-id="${trainer.id}"
                 data-trainer-name="${escapedName}"
                 data-trainer-status="${effectiveStatus}"
                 data-trainer-shift="${trainer.shift || 'Morning'}"
                 data-shift-start="${trainer.shift_start || ''}"
                 data-shift-end="${trainer.shift_end || ''}"
                 data-break-start="${trainer.break_start || ''}"
                 data-break-end="${trainer.break_end || ''}"
                 onclick="selectRescheduleTrainer(${trainer.id}, '${escapedName}', '${effectiveStatus}')">
                <span class="trainer-status-badge ${effectiveStatus}">${statusText}</span>
                <img src="${photoSrc}"
                     alt="${escapedName}"
                     class="trainer-photo ${trainer.photo && trainer.photo !== 'account-icon.svg' ? '' : 'default-icon'}"
                     onerror="this.onerror=null; this.src='../../images/account-icon.svg'; this.classList.add('default-icon');">
                <h3 class="trainer-name">${trainer.name}</h3>
                <p class="trainer-specialty">${trainer.specialization}</p>
                ${shiftTimeDisplay}
            </div>
        `;
    }).join('');
}

window.selectRescheduleTrainer = function(trainerId, trainerName, status) {
    
    if (status === 'unavailable') {
        showToast('This trainer is not available for the selected date', 'warning');
        return;
    }

    if (status === 'fully-booked') {
        showToast('This trainer is fully booked with no available time slots', 'warning');
        return;
    }

    if (status === 'booked') {
        showToast('This trainer is already booked for the selected session', 'warning');
        return;
    }


    const trainerInput = document.getElementById('rescheduleTrainerInput');
    if (!trainerInput) {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.id = 'rescheduleTrainerInput';
        input.value = trainerId;
        document.getElementById('rescheduleForm').appendChild(input);
    } else {
        trainerInput.value = trainerId;
    }

    // Save trainer name for summary
    window.rescheduleSelectedTrainerName = trainerName;

    document.querySelectorAll('#rescheduleTrainersGrid .trainer-card').forEach(card => {
        card.classList.remove('selected');
    });
    
    const selectedCard = document.querySelector(`[data-trainer-id="${trainerId}"]`);
    if (selectedCard) {
        selectedCard.classList.add('selected');
        
        // Store shift data from card attributes
        window.rescheduleSelectedTrainerShift = selectedCard.dataset.trainerShift;
        window.rescheduleSelectedTrainerShiftData = {
            shift_type: selectedCard.dataset.trainerShift,
            shift_start: selectedCard.dataset.shiftStart,
            shift_end: selectedCard.dataset.shiftEnd,
            break_start: selectedCard.dataset.breakStart,
            break_end: selectedCard.dataset.breakEnd
        };
        
        console.log('🔍 Selected trainer shift:', window.rescheduleSelectedTrainerShift, window.rescheduleSelectedTrainerShiftData);
    }

    loadRescheduleTrainerAvailability();
};

// Load trainer availability for reschedule
function loadRescheduleTrainerAvailability() {
    const date = document.getElementById('rescheduleDate').value;
    const trainerInput = document.getElementById('rescheduleTrainerInput');
    const trainerId = trainerInput ? trainerInput.value : '';
    const classType = document.getElementById('rescheduleClass').value;


    if (!date || !trainerId || !classType) {
        return;
    }


    const banner = document.getElementById('rescheduleAvailabilityBanner');
    if (banner) {
        banner.style.display = 'block';
        banner.innerHTML = `
            <div class="banner-loading">
                <i class="fas fa-spinner fa-spin"></i>
                <span>Loading trainer availability...</span>
            </div>
        `;
    }
    const timeLayout = document.getElementById('rescheduleTimeSelectionLayout');
    if (timeLayout) {
        timeLayout.classList.add('hidden');
        timeLayout.style.setProperty('display', 'none', 'important');
    }

    const formData = new FormData();
    formData.append('trainer_id', trainerId);
    formData.append('date', date);
    formData.append('class_type', classType);
    
    // Exclude current booking from availability check during reschedule
    
    if (currentRescheduleBooking && currentRescheduleBooking.id) {
        formData.append('exclude_booking_id', currentRescheduleBooking.id);
    }

    // Fetch both trainer availability and user bookings for conflict checking
    Promise.all([
        fetch('api/get_trainer_availability.php', { method: 'POST', body: formData }).then(res => res.json()),
        fetch('api/get_user_bookings.php', { method: 'GET', credentials: 'same-origin' }).then(res => res.json())
    ])
        .then(([availabilityData, bookingsData]) => {
            const data = availabilityData;
            
            // Filter user's bookings for the selected date, excluding current booking being rescheduled
            let userBookingsOnDate = [];
            if (bookingsData && bookingsData.success && bookingsData.bookings) {
                console.log('📦 All user bookings received:', bookingsData.bookings.length);
                console.log('🔍 Looking for bookings on date:', date);
                console.log('🔍 Current reschedule booking ID:', currentRescheduleBooking?.id);
                
                // Log all bookings for debugging
                bookingsData.bookings.forEach(b => {
                    console.log(`  - Booking ${b.id}: ${b.date}, ${b.start_time}-${b.end_time}, status: ${b.status}`);
                });
                
                userBookingsOnDate = bookingsData.bookings.filter(booking => {
                    const dateMatch = booking.date === date;
                    const statusOk = booking.status === 'confirmed';
                    const notCurrentBooking = booking.id !== (currentRescheduleBooking?.id);
                    const hasTime = booking.start_time && booking.end_time;
                    
                    if (!dateMatch) console.log(`  ❌ Booking ${booking.id}: date mismatch (${booking.date} !== ${date})`);
                    if (!statusOk) console.log(`  ❌ Booking ${booking.id}: status not confirmed (${booking.status})`);
                    if (!notCurrentBooking) console.log(`  ⏭️ Booking ${booking.id}: is current reschedule booking, skipping`);
                    if (!hasTime) console.log(`  ❌ Booking ${booking.id}: missing time data`);
                    
                    return dateMatch && statusOk && notCurrentBooking && hasTime;
                });
                
                console.log('✅ Loaded user bookings for conflict checking:', userBookingsOnDate.length, 'bookings');
                if (userBookingsOnDate.length > 0) {
                    console.log('📋 Existing bookings on', date, ':', userBookingsOnDate.map(b => `${b.start_time}-${b.end_time} (ID: ${b.id})`));
                } else {
                    console.log('ℹ️ No conflicting bookings found for', date);
                }
            } else {
                console.warn('⚠️ Could not load user bookings for conflict checking:', bookingsData);
            }
            
            // Store user bookings for conflict checking
            if (!window.rescheduleUserBookings) {
                window.rescheduleUserBookings = {};
            }
            window.rescheduleUserBookings[date] = userBookingsOnDate;
            console.log('💾 Stored in window.rescheduleUserBookings["' + date + '"]:', userBookingsOnDate.length, 'bookings');
            
            console.log('🔍 API Response:', data);
            console.log('🔍 Stored shift:', window.rescheduleSelectedTrainerShift);

            if (data.success && data.available_slots && data.available_slots.length > 0) {
                if (banner) banner.style.display = 'none';
                const timeLayout = document.getElementById('rescheduleTimeSelectionLayout');
                if (timeLayout) {
                    timeLayout.classList.remove('hidden');
                    timeLayout.style.setProperty('display', 'grid', 'important');
                }
                
                // Update global rescheduleState instead of creating a local one
                rescheduleState.startTime = null;
                rescheduleState.endTime = null;
                rescheduleState.duration = null;
                // Use stored shift or fallback to Morning
                rescheduleState.trainerShift = window.rescheduleSelectedTrainerShift || 'Morning';
                rescheduleState.customShift = null;
                rescheduleState.availableSlots = data.available_slots;
                rescheduleState.currentWeekUsageMinutes = data.current_week_usage_minutes || 0;
                rescheduleState.weeklyLimitHours = data.weekly_limit_hours || 48;

                // Use API shift_info if available
                if (data.shift_info && data.shift_info.start_time && data.shift_info.end_time) {
                    rescheduleState.customShift = {
                        start: data.shift_info.start_time.substring(0, 5),
                        end: data.shift_info.end_time.substring(0, 5),
                        breakStart: data.shift_info.break_start ? data.shift_info.break_start.substring(0, 5) : null,
                        breakEnd: data.shift_info.break_end ? data.shift_info.break_end.substring(0, 5) : null
                    };
                    if (data.shift_info.shift_type) {
                        rescheduleState.trainerShift = data.shift_info.shift_type.charAt(0).toUpperCase() + data.shift_info.shift_type.slice(1);
                    }
                } else if (window.rescheduleSelectedTrainerShiftData) {
                    // Fallback to stored shift data
                    const storedShift = window.rescheduleSelectedTrainerShiftData;
                    if (storedShift.shift_start && storedShift.shift_end) {
                        rescheduleState.customShift = {
                            start: storedShift.shift_start,
                            end: storedShift.shift_end,
                            breakStart: storedShift.break_start || null,
                            breakEnd: storedShift.break_end || null
                        };
                    }
                }

                console.log('🔍 Final rescheduleState:', rescheduleState);

                buildRescheduleAvailabilityTimeline(rescheduleState, data);
                setupRescheduleTimePickers(rescheduleState);
            } else {
                if (banner) {
                    banner.style.display = 'block';
                    banner.innerHTML = `
                        <div class="banner-error">
                            <i class="fas fa-exclamation-circle"></i>
                            <span>No available time slots for this trainer on the selected date</span>
                        </div>
                    `;
                }
                document.getElementById('rescheduleTimeSelectionLayout').style.display = 'none';
            }
        })
        .catch(err => {
            if (banner) {
                banner.style.display = 'block';
                banner.innerHTML = `
                    <div class="banner-error">
                        <i class="fas fa-exclamation-circle"></i>
                        <span>Error loading trainer availability</span>
                    </div>
                `;
            }
        });
}

// Helper function to check if time is in the past for today's date
function isRescheduleTimePast(timeString, selectedDate) {
    const today = new Date().toISOString().split('T')[0];
    if (selectedDate !== today) return false; // Not today, allow all times
    
    const now = new Date();
    const currentMinutes = now.getHours() * 60 + now.getMinutes();
    const slotMinutes = parseRescheduleTime(timeString);
    
    return slotMinutes <= currentMinutes;
}

function buildRescheduleAvailabilityTimeline(state, data) {
    const timeline = document.getElementById('rescheduleAvailabilityTimeline');
    if (!timeline) return;

    const shift = state.customShift || RESCHEDULE_TRAINER_SHIFTS[state.trainerShift] || RESCHEDULE_TRAINER_SHIFTS['Morning'];
    const slots = generateRescheduleTimeSlots(shift.start, shift.end, 30);

    let html = '';

    if (data.trainer_name) {
        const shiftHours = `${formatRescheduleTime(shift.start)} - ${formatRescheduleTime(shift.end)}`;
        const breakHours = `${formatRescheduleTime(shift.breakStart)} - ${formatRescheduleTime(shift.breakEnd)}`;
        
        html += `
            <div class="trainer-info-card">
                <div class="trainer-details">
                    <div class="trainer-name">${data.trainer_name || 'Trainer'}</div>
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
        const status = getRescheduleSlotStatus(slot, state, shift);
        const iconClass = status === 'available' ? 'fa-check-circle' : 
                         status === 'break' ? 'fa-coffee' :
                         status === 'booked' ? 'fa-times-circle' : 'fa-ban';
        const statusClass = `timeline-slot-${status}`;

        html += `
            <div class="timeline-slot ${statusClass}">
                <span class="slot-time">${formatRescheduleTime(slot)}</span>
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

function generateRescheduleTimeSlots(startTime, endTime, intervalMinutes) {
    const slots = [];
    let current = parseRescheduleTime(startTime);
    const end = parseRescheduleTime(endTime);

    while (current < end) {
        slots.push(formatRescheduleTimeFromMinutes(current));
        current += intervalMinutes;
    }

    return slots;
}

function getRescheduleSlotStatus(slotTime, state, shift) {
    // Check if time is in the past (only for today's bookings)
    const selectedDate = document.getElementById('rescheduleDate')?.value;
    if (selectedDate && isRescheduleTimePast(slotTime, selectedDate)) {
        return 'unavailable';
    }
    
    // Check if slot conflicts with user's existing bookings
    if (selectedDate && hasUserBookingConflict(slotTime, selectedDate)) {
        return 'booked';
    }
    
    if (isRescheduleBreakTime(slotTime, shift)) {
        return 'break';
    }

    if (isRescheduleSlotBooked(slotTime, state.availableSlots)) {
        return 'booked';
    }

    if (!isRescheduleWithinShift(slotTime, shift)) {
        return 'unavailable';
    }

    return 'available';
}

function hasUserBookingConflict(slotTime, date) {
    // Check if this slot conflicts with user's other bookings on the same date
    if (!window.rescheduleUserBookings || !window.rescheduleUserBookings[date]) {
        return false;
    }
    
    const userBookings = window.rescheduleUserBookings[date];
    if (userBookings.length === 0) {
        return false;
    }
    
    const slotMinutes = parseRescheduleTime(slotTime);
    
    for (const booking of userBookings) {
        const bookingStart = parseRescheduleTime(booking.start_time);
        const bookingEnd = parseRescheduleTime(booking.end_time);
        
        // Check if slot falls within this booking's time range
        if (slotMinutes >= bookingStart && slotMinutes < bookingEnd) {
            console.log(`⚠️ Slot ${slotTime} (${slotMinutes} mins) conflicts with existing booking: ${booking.start_time} (${bookingStart} mins) - ${booking.end_time} (${bookingEnd} mins)`);
            return true;
        }
    }
    
    return false;
}

function hasUserBookingRangeConflict(startMinutes, endMinutes, date) {
    // Check if a time range (start to end) conflicts with user's existing bookings
    if (!window.rescheduleUserBookings || !window.rescheduleUserBookings[date]) {
        return false;
    }
    
    const userBookings = window.rescheduleUserBookings[date];
    
    for (const booking of userBookings) {
        const bookingStart = parseRescheduleTime(booking.start_time);
        const bookingEnd = parseRescheduleTime(booking.end_time);
        
        // Check for any overlap between the proposed time range and existing booking
        // Two ranges overlap if: (start1 < end2) AND (end1 > start2)
        const hasOverlap = (startMinutes < bookingEnd) && (endMinutes > bookingStart);
        
        if (hasOverlap) {
            console.log(`⚠️ Time range ${startMinutes}-${endMinutes} conflicts with existing booking: ${booking.start_time} - ${booking.end_time}`);
            return true;
        }
    }
    
    return false;
}

function isRescheduleBreakTime(time, shift) {
    const timeMinutes = parseRescheduleTime(time);
    const breakStart = parseRescheduleTime(shift.breakStart);
    const breakEnd = parseRescheduleTime(shift.breakEnd);

    return timeMinutes >= breakStart && timeMinutes < breakEnd;
}

function isRescheduleSlotBooked(time, availableSlots) {
    if (!availableSlots || availableSlots.length === 0) return false;

    const timeMinutes = parseRescheduleTime(time);

    for (const slot of availableSlots) {
        if (slot.status === 'booked' || slot.status === 'unavailable') {
            const slotStart = parseRescheduleTime(slot.start_time);
            const slotEnd = parseRescheduleTime(slot.end_time);

            if (timeMinutes >= slotStart && timeMinutes < slotEnd) {
                return true;
            }
        }
    }

    return false;
}

function isRescheduleWithinShift(time, shift) {
    const timeMinutes = parseRescheduleTime(time);
    const shiftStart = parseRescheduleTime(shift.start);
    const shiftEnd = parseRescheduleTime(shift.end);

    return timeMinutes >= shiftStart && timeMinutes < shiftEnd;
}

function setupRescheduleTimePickers(state) {
    const shift = state.customShift || RESCHEDULE_TRAINER_SHIFTS[state.trainerShift] || RESCHEDULE_TRAINER_SHIFTS['Morning'];

    const startSelect = document.getElementById('rescheduleStartTimeSelect');
    const endSelect = document.getElementById('rescheduleEndTimeSelect');

    if (!startSelect || !endSelect) {
        return;
    }

    const startSlots = generateRescheduleStartTimeOptions(state, shift);
    startSelect.innerHTML = '<option value="">Select start time</option>' + startSlots;

    endSelect.innerHTML = '<option value="">Select start time first</option>';
    endSelect.disabled = true;
    
    // Check if ALL start times are unavailable by counting enabled vs disabled options
    const enabledOptions = (startSlots.match(/<option value="[^"]+">/g) || []).length;
    const disabledOptions = (startSlots.match(/disabled/g) || []).length;
    
    if (enabledOptions > 0 && disabledOptions === enabledOptions) {
        // All options are disabled
        const banner = document.getElementById('rescheduleAvailabilityBanner');
        if (banner) {
            banner.style.display = 'block';
            banner.innerHTML = `
                <div class="banner-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <span>⚠️ This trainer has no available time slots for the selected date (all times conflict with your existing bookings or are already booked)</span>
                </div>
            `;
        }
        // Disable the start select
        startSelect.disabled = true;
        
        // Update the trainer card to show "Fully Booked" status
        const trainerId = state.trainerId;
        const trainerCard = document.querySelector(`.trainer-card[data-trainer-id="${trainerId}"]`);
        if (trainerCard) {
            console.log(`🔄 Updating trainer ${trainerId} card to fully-booked status`);
            trainerCard.classList.remove('available');
            trainerCard.classList.add('fully-booked');
            trainerCard.dataset.trainerStatus = 'fully-booked';
            
            const statusBadge = trainerCard.querySelector('.trainer-status-badge');
            if (statusBadge) {
                statusBadge.className = 'trainer-status-badge fully-booked';
                statusBadge.textContent = 'Fully Booked';
            }
        }
    } else {
        // Re-enable if it was previously disabled
        startSelect.disabled = false;
    }

    startSelect.addEventListener('change', (e) => {
        if (e.target.value) {
            handleRescheduleStartTimeSelect(e.target.value, state, endSelect, shift);
            updateNewBookingSummary();
            
            // Hide Next button when start time changes
            const nextBtn = document.getElementById('rescheduleNextBtn');
            if (nextBtn) nextBtn.style.display = 'none';
        } else {
            state.startTime = null;
            state.endTime = null;
            state.duration = null;

            endSelect.innerHTML = '<option value="">Select start time first</option>';
            endSelect.disabled = true;
            endSelect.value = '';

            hideRescheduleDurationDisplay();
            updateNewBookingSummary();
            
            // Hide Next button
            const nextBtn = document.getElementById('rescheduleNextBtn');
            if (nextBtn) nextBtn.style.display = 'none';
        }
    });

    endSelect.addEventListener('change', (e) => {
        if (e.target.value) {
            handleRescheduleEndTimeSelect(e.target.value, state);
            updateNewBookingSummary();
            
            // Show Next button when end time is selected
            const nextBtn = document.getElementById('rescheduleNextBtn');
            if (nextBtn) {
                nextBtn.style.display = 'inline-flex';
                nextBtn.disabled = false;
            } else {
            }
        } else {
            state.endTime = null;
            state.duration = null;
            hideRescheduleDurationDisplay();
            updateNewBookingSummary();
            
            // Hide Next button
            const nextBtn = document.getElementById('rescheduleNextBtn');
            if (nextBtn) nextBtn.style.display = 'none';
        }
    });
}

function generateRescheduleStartTimeOptions(state, shift) {
    const slots = generateRescheduleTimeSlots(shift.start, shift.end, 30);

    const options = slots.map(timeStr => {
        const status = getRescheduleSlotStatus(timeStr, state, shift);
        const isDisabled = status === 'booked' || status === 'break' || status === 'unavailable';
        const label = formatRescheduleTime(timeStr);
        const unavailableText = isDisabled ? ' (unavailable)' : '';

        return `<option value="${timeStr}" ${isDisabled ? 'disabled' : ''}>${label}${unavailableText}</option>`;
    });

    return options.join('');
}

function handleRescheduleStartTimeSelect(timeStr, state, endSelect, shift) {

    state.startTime = timeStr;

    if (endSelect) {
        const endOptions = generateRescheduleEndTimeOptions(state, shift);
        if (endOptions) {
            endSelect.innerHTML = '<option value="">Select end time</option>' + endOptions;
            endSelect.disabled = false;
        } else {
            endSelect.innerHTML = '<option value="">No available end times</option>';
            endSelect.disabled = true;
        }
    }

    state.endTime = null;
    state.duration = null;
    hideRescheduleDurationDisplay();
}

function generateRescheduleEndTimeOptions(state, shift) {
    if (!state.startTime) return '';

    const startMinutes = parseRescheduleTime(state.startTime);
    const minEndMinutes = startMinutes + 30;
    const shiftEndMinutes = parseRescheduleTime(shift.end);
    const selectedDate = document.getElementById('rescheduleDate')?.value;

    const slots = [];
    for (let time = minEndMinutes; time <= shiftEndMinutes; time += 30) {
        slots.push(formatRescheduleTimeFromMinutes(time));
    }

    const options = slots.map(timeStr => {
        const endMinutes = parseRescheduleTime(timeStr);
        const isDuringBreak = isRescheduleBreakTime(timeStr, shift);
        const hasConflict = checkRescheduleTimeRangeConflict(startMinutes, endMinutes, state.availableSlots);
        
        // Check if the time range conflicts with user's existing bookings
        const hasUserConflict = selectedDate && hasUserBookingRangeConflict(startMinutes, endMinutes, selectedDate);

        const isDisabled = isDuringBreak || hasConflict || hasUserConflict;
        const label = formatRescheduleTime(timeStr);
        const unavailableText = isDisabled ? ' (unavailable)' : '';

        return `<option value="${timeStr}" ${isDisabled ? 'disabled' : ''}>${label}${unavailableText}</option>`;
    });

    return options.join('');
}

function handleRescheduleEndTimeSelect(timeStr, state) {

    const startMinutes = parseRescheduleTime(state.startTime);
    const endMinutes = parseRescheduleTime(timeStr);
    const durationMinutes = endMinutes - startMinutes;

    if (durationMinutes <= 0) {
        showToast('End time must be after start time', 'warning');
        return;
    }

    state.endTime = timeStr;
    state.duration = durationMinutes;

    showRescheduleDurationDisplay(durationMinutes, state);
    
    // Show Next button when time selection is complete
    const nextBtn = document.getElementById('rescheduleNextBtn');
    if (nextBtn) {
        nextBtn.style.display = 'inline-flex';
        nextBtn.disabled = false;
    }
}

function showRescheduleDurationDisplay(minutes, state) {
    const display = document.getElementById('rescheduleDurationDisplay');
    const valueSpan = document.getElementById('rescheduleDurationValue');
    const weeklyUsageInfo = document.getElementById('rescheduleWeeklyUsageInfo');
    const weeklyUsageText = document.getElementById('rescheduleWeeklyUsageText');

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

    if (weeklyUsageInfo && weeklyUsageText && state.currentWeekUsageMinutes !== undefined) {
        const weeklyLimit = state.weeklyLimitHours || 48;
        const newTotalMinutes = state.currentWeekUsageMinutes + minutes;
        const newTotalHours = Math.round(newTotalMinutes / 60 * 10) / 10;
        const remainingHours = Math.max(0, weeklyLimit - newTotalHours);

        weeklyUsageText.textContent = `This booking will bring you to ${newTotalHours}h of your ${weeklyLimit}h weekly limit (${remainingHours}h remaining)`;
        weeklyUsageInfo.style.display = 'flex';

        if (newTotalHours > weeklyLimit) {
            weeklyUsageInfo.classList.add('usage-exceeded');
        } else {
            weeklyUsageInfo.classList.remove('usage-exceeded');
        }
    }
}

function hideRescheduleDurationDisplay() {
    const display = document.getElementById('rescheduleDurationDisplay');
    const weeklyUsageInfo = document.getElementById('rescheduleWeeklyUsageInfo');

    if (display) display.style.display = 'none';
    if (weeklyUsageInfo) weeklyUsageInfo.style.display = 'none';
}

function showRescheduleTimeSummary(state) {
    const summary = document.getElementById('rescheduleTimeSummary');
    const summaryTime = document.getElementById('rescheduleSummaryTime');
    const summaryDuration = document.getElementById('rescheduleSummaryDuration');
    const weeklyUsageDisplay = document.getElementById('rescheduleWeeklyUsageDisplay');

    if (!summary || !summaryTime || !summaryDuration) return;

    const timeSelection = document.getElementById('rescheduleTimeSelectionLayout');
    if (timeSelection) timeSelection.style.display = 'none';

    summary.style.display = 'block';
    summaryTime.textContent = `${formatRescheduleTime(state.startTime)} - ${formatRescheduleTime(state.endTime)}`;

    const hours = Math.floor(state.duration / 60);
    const mins = state.duration % 60;
    let durationText = '';
    if (hours > 0) durationText += `${hours} hour${hours > 1 ? 's' : ''}`;
    if (mins > 0) durationText += ` ${mins} minutes`;
    summaryDuration.textContent = durationText.trim();

    const newTotalMinutes = state.currentWeekUsageMinutes + state.duration;
    const newTotalHours = Math.round(newTotalMinutes / 60 * 10) / 10;
    const weeklyLimit = state.weeklyLimitHours || 48;
    const remainingHours = Math.max(0, weeklyLimit - newTotalHours);

    if (weeklyUsageDisplay) {
        weeklyUsageDisplay.textContent = `${newTotalHours}h / ${weeklyLimit}h (${remainingHours}h remaining)`;
    }
}

/**
 * Update new booking summary as user makes selections
 */
function updateNewBookingSummary() {
    const dateInput = document.getElementById('rescheduleDate').value;
    const startTime = document.getElementById('rescheduleStartTimeSelect').value;
    const endTime = document.getElementById('rescheduleEndTimeSelect').value;
    const classType = document.getElementById('rescheduleClass').value;
    const trainerInput = document.getElementById('rescheduleTrainerInput');
    const trainerId = trainerInput ? trainerInput.value : '';
    const reason = document.getElementById('rescheduleReason').value;

    // Hide summary if not all fields filled
    if (!dateInput || !startTime || !endTime || !classType || !trainerId) {
        const summary = document.getElementById('rescheduleTimeSummary');
        if (summary) summary.style.display = 'none';
        return;
    }

    // Find trainer name from selected card
    let trainerName = '-';
    const selectedCard = document.querySelector(`[data-trainer-id="${trainerId}"]`);
    if (selectedCard) {
        const nameEl = selectedCard.querySelector('.trainer-name');
        trainerName = nameEl ? nameEl.textContent.trim() : '-';
    }


    // Format date
    const bookingDate = new Date(dateInput);
    const dateStr = bookingDate.toLocaleDateString('en-US', { 
        month: 'short', 
        day: 'numeric', 
        year: 'numeric' 
    });

    // Calculate duration
    const startMinutes = parseRescheduleTime(startTime);
    const endMinutes = parseRescheduleTime(endTime);
    const duration = endMinutes - startMinutes;

    // Format duration text
    const hours = Math.floor(duration / 60);
    const mins = duration % 60;
    let durationText = '';
    if (hours > 0) {
        durationText = `${hours} hour${hours > 1 ? 's' : ''}`;
        if (mins > 0) durationText += ` ${mins} minutes`;
    } else {
        durationText = `${mins} minutes`;
    }

    // Format times
    const formattedStartTime = formatRescheduleTime(startTime);
    const formattedEndTime = formatRescheduleTime(endTime);
    const dateTimeStr = `${dateStr} ${formattedStartTime} - ${formattedEndTime}`;

    // Update new booking display
    const newDateTimeEl = document.getElementById('newBookingDateTime');
    const newTrainerEl = document.getElementById('newBookingTrainer');
    const newClassEl = document.getElementById('newBookingClass');
    const newDurationEl = document.getElementById('newBookingDuration');

    if (newDateTimeEl) {
        newDateTimeEl.textContent = dateTimeStr;
    }
    
    if (newTrainerEl) {
        newTrainerEl.textContent = trainerName;
    }
    
    if (newClassEl) {
        newClassEl.textContent = classType;
    }
    
    if (newDurationEl) {
        newDurationEl.textContent = durationText;
    }

    // Update reason display
    const reasonDisplay = document.getElementById('rescheduleSummaryReason');
    if (reasonDisplay) {
        reasonDisplay.textContent = reason || 'No reason provided';
    }

    // Update weekly usage display
    const weeklyUsageDisplay = document.getElementById('rescheduleWeeklyUsageDisplay');
    if (weeklyUsageDisplay) {
        const currentWeekUsageMinutes = parseInt(
            document.getElementById('rescheduleModal')?.dataset.currentWeekUsageMinutes || '0'
        );
        const weeklyLimitHours = parseInt(
            document.getElementById('rescheduleModal')?.dataset.weeklyLimitHours || '48'
        );

        const newTotalMinutes = currentWeekUsageMinutes + duration;
        const newTotalHours = Math.round((newTotalMinutes / 60) * 10) / 10;
        const remainingHours = Math.max(0, weeklyLimitHours - newTotalHours);

        weeklyUsageDisplay.textContent = `${newTotalHours}h / ${weeklyLimitHours}h (${remainingHours}h remaining)`;

        // Add warning if exceeding limit
        const summaryInfo = document.querySelector('.reschedule-summary-info');
        if (summaryInfo) {
            if (newTotalHours > weeklyLimitHours) {
                summaryInfo.classList.add('usage-exceeded');
            } else {
                summaryInfo.classList.remove('usage-exceeded');
            }
        }
    }

    // Show the summary section
    const summaryEl = document.getElementById('rescheduleTimeSummary');
    if (summaryEl) {
        summaryEl.style.display = 'block';
    }
}

// Close modal when clicking outside
document.addEventListener('click', function(e) {
    const modal = document.getElementById('rescheduleModal');
    if (e.target === modal) {
        closeRescheduleModal();
    }
});

// ===================================
// CURRENT BOOKING DISPLAY FUNCTIONS
// ===================================

/**
 * Populate current booking details in the summary
 */
function populateCurrentBookingSummary() {
    if (!currentRescheduleBooking) {
        return;
    }


    // Update current booking comparison section
    const currentDateTimeEl = document.getElementById('currentBookingDateTime');
    const currentTrainerEl = document.getElementById('currentBookingTrainer');
    const currentClassEl = document.getElementById('currentBookingClass');

    if (currentDateTimeEl) {
        currentDateTimeEl.textContent = `${currentRescheduleBooking.date} ${currentRescheduleBooking.time}`;
    }
    
    if (currentTrainerEl) {
        currentTrainerEl.textContent = currentRescheduleBooking.trainer || '-';
    }
    
    if (currentClassEl) {
        currentClassEl.textContent = currentRescheduleBooking.class || '-';
    }

    // Fetch actual booking duration from server
    if (currentRescheduleBooking.id) {
        console.log('Fetching booking details for ID:', currentRescheduleBooking.id);
        fetch(`api/get_booking_details.php?booking_id=${currentRescheduleBooking.id}`)
            .then(res => {
                console.log('Response status:', res.status, res.statusText);
                if (!res.ok) {
                    throw new Error(`HTTP ${res.status}: ${res.statusText}`);
                }
                return res.json();
            })
            .then(data => {
                console.log('Booking details response:', data);
                if (data.success && data.booking) {
                    const booking = data.booking;
                    
                    // Calculate duration from start_time and end_time
                    if (booking.start_time && booking.end_time) {
                        try {
                            // Parse as Date objects (handles full datetime strings)
                            const startTime = new Date(booking.start_time);
                            const endTime = new Date(booking.end_time);
                            
                            // Calculate duration in minutes
                            const durationMinutes = Math.round((endTime - startTime) / (1000 * 60));
                            
                            if (isNaN(durationMinutes) || durationMinutes <= 0) {
                                throw new Error(`Invalid duration: ${durationMinutes} minutes`);
                            }
                            
                            const hours = Math.floor(durationMinutes / 60);
                            const mins = durationMinutes % 60;
                            let durationText = '';
                            
                            if (hours > 0) {
                                durationText = `${hours} hour${hours > 1 ? 's' : ''}`;
                                if (mins > 0) durationText += ` ${mins} minutes`;
                            } else {
                                durationText = `${mins} minutes`;
                            }
                            
                            // Update both the original duration display and the current booking summary
                            const originalDurationEl = document.getElementById('originalDuration');
                            const currentDurationEl = document.getElementById('currentBookingDuration');
                            
                            if (originalDurationEl) {
                                originalDurationEl.textContent = durationText;
                            }
                            
                            if (currentDurationEl) {
                                currentDurationEl.textContent = durationText;
                            }
                        } catch (parseError) {
                            console.error('Error parsing time:', parseError);
                            throw parseError;
                        }
                    } else {
                        throw new Error('No time data available');
                    }
                } else {
                    throw new Error('Invalid response');
                }
            })
            .catch(err => {
                console.error('Error fetching booking duration:', err);
                const originalDurationEl = document.getElementById('originalDuration');
                const currentDurationEl = document.getElementById('currentBookingDuration');
                
                if (originalDurationEl) originalDurationEl.textContent = 'N/A';
                if (currentDurationEl) currentDurationEl.textContent = 'N/A';
            });
    } else {
        const originalDurationEl = document.getElementById('originalDuration');
        const currentDurationEl = document.getElementById('currentBookingDuration');
        
        if (originalDurationEl) originalDurationEl.textContent = 'N/A';
        if (currentDurationEl) currentDurationEl.textContent = 'N/A';
    }
}

/**
 * Update new booking summary as user makes selections
 */
function updateNewBookingSummary() {
    const dateInput = document.getElementById('rescheduleDate').value;
    const startTime = document.getElementById('rescheduleStartTimeSelect').value;
    const endTime = document.getElementById('rescheduleEndTimeSelect').value;
    const classType = document.getElementById('rescheduleClass').value;
    const trainerInput = document.getElementById('rescheduleTrainerInput');
    const trainerId = trainerInput ? trainerInput.value : '';
    const reason = document.getElementById('rescheduleReason').value;

    // Hide summary if not all fields filled
    if (!dateInput || !startTime || !endTime || !classType || !trainerId) {
        const summary = document.getElementById('rescheduleTimeSummary');
        if (summary) summary.style.display = 'none';
        return;
    }

    // Find trainer name from selected card
    let trainerName = '-';
    const selectedCard = document.querySelector(`[data-trainer-id="${trainerId}"]`);
    if (selectedCard) {
        const nameEl = selectedCard.querySelector('.trainer-name');
        trainerName = nameEl ? nameEl.textContent.trim() : '-';
    }


    // Format date
    const bookingDate = new Date(dateInput);
    const dateStr = bookingDate.toLocaleDateString('en-US', { 
        month: 'short', 
        day: 'numeric', 
        year: 'numeric' 
    });

    // Calculate duration
    const startMinutes = parseRescheduleTime(startTime);
    const endMinutes = parseRescheduleTime(endTime);
    const duration = endMinutes - startMinutes;

    // Format duration text
    const hours = Math.floor(duration / 60);
    const mins = duration % 60;
    let durationText = '';
    if (hours > 0) {
        durationText = `${hours} hour${hours > 1 ? 's' : ''}`;
        if (mins > 0) durationText += ` ${mins} minutes`;
    } else {
        durationText = `${mins} minutes`;
    }

    // Format times
    const formattedStartTime = formatRescheduleTime(startTime);
    const formattedEndTime = formatRescheduleTime(endTime);
    const dateTimeStr = `${dateStr} ${formattedStartTime} - ${formattedEndTime}`;

    // Update new booking display
    const newDateTimeEl = document.getElementById('newBookingDateTime');
    const newTrainerEl = document.getElementById('newBookingTrainer');
    const newClassEl = document.getElementById('newBookingClass');
    const newDurationEl = document.getElementById('newBookingDuration');

    if (newDateTimeEl) {
        newDateTimeEl.textContent = dateTimeStr;
    }
    
    if (newTrainerEl) {
        newTrainerEl.textContent = trainerName;
    }
    
    if (newClassEl) {
        newClassEl.textContent = classType;
    }
    
    if (newDurationEl) {
        newDurationEl.textContent = durationText;
    }

    // Update reason display
    const reasonDisplay = document.getElementById('rescheduleSummaryReason');
    if (reasonDisplay) {
        reasonDisplay.textContent = reason || 'No reason provided';
    }

    // Update weekly usage display
    const weeklyUsageDisplay = document.getElementById('rescheduleWeeklyUsageDisplay');
    if (weeklyUsageDisplay) {
        const currentWeekUsageMinutes = parseInt(
            document.getElementById('rescheduleModal')?.dataset.currentWeekUsageMinutes || '0'
        );
        const weeklyLimitHours = parseInt(
            document.getElementById('rescheduleModal')?.dataset.weeklyLimitHours || '48'
        );

        const newTotalMinutes = currentWeekUsageMinutes + duration;
        const newTotalHours = Math.round((newTotalMinutes / 60) * 10) / 10;
        const remainingHours = Math.max(0, weeklyLimitHours - newTotalHours);

        weeklyUsageDisplay.textContent = `${newTotalHours}h / ${weeklyLimitHours}h (${remainingHours}h remaining)`;

        // Add warning if exceeding limit
        const summaryInfo = document.querySelector('.reschedule-summary-info');
        if (summaryInfo) {
            if (newTotalHours > weeklyLimitHours) {
                summaryInfo.classList.add('usage-exceeded');
            } else {
                summaryInfo.classList.remove('usage-exceeded');
            }
        }
    }

    // Show the summary section
    const summaryEl = document.getElementById('rescheduleTimeSummary');
    if (summaryEl) {
        summaryEl.style.display = 'block';
    }
}

// ===================================
// HELPER FUNCTIONS
// ===================================

/**
 * Parse time string (HH:MM) to minutes since midnight
 */
function parseRescheduleTime(timeStr) {
    if (!timeStr) return 0;
    
    // Extract time portion if this is a datetime string (e.g., "2025-11-21 17:00:00")
    let time = timeStr;
    if (timeStr.indexOf(' ') !== -1) {
        time = timeStr.split(' ')[1]; // Get the time part after the space
    }
    
    const parts = time.split(':');
    const hours = parseInt(parts[0]) || 0;
    const minutes = parseInt(parts[1]) || 0;
    return hours * 60 + minutes;
}

/**
 * Format minutes since midnight to HH:MM string
 */
function formatRescheduleTimeFromMinutes(minutes) {
    const hours = Math.floor(minutes / 60);
    const mins = minutes % 60;
    return `${String(hours).padStart(2, '0')}:${String(mins).padStart(2, '0')}`;
}

/**
 * Format time string to readable format (e.g., "2:30 PM")
 */
function formatRescheduleTime(timeStr) {
    if (!timeStr) return '-';
    const [hours, minutes] = timeStr.split(':');
    const hour = parseInt(hours);
    const minute = parseInt(minutes);
    const ampm = hour >= 12 ? 'PM' : 'AM';
    const displayHour = hour % 12 || 12;
    return `${displayHour}:${String(minute).padStart(2, '0')} ${ampm}`;
}

/**
 * Check if time range conflicts with booked slots
 */
function checkRescheduleTimeRangeConflict(startMinutes, endMinutes, availableSlots) {
    if (!availableSlots || availableSlots.length === 0) return false;

    for (const slot of availableSlots) {
        if (slot.status === 'booked' || slot.status === 'unavailable') {
            const slotStart = parseRescheduleTime(slot.start_time);
            const slotEnd = parseRescheduleTime(slot.end_time);

            // Check if there's any overlap
            if (startMinutes < slotEnd && endMinutes > slotStart) {
                return true;
            }
        }
    }

    return false;
}

/**
 * Show toast notification
 */
function showToast(message, type = 'info') {
    const toastContainer = document.getElementById('toastContainer') || createToastContainer();
    
    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    toast.innerHTML = `
        <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'times-circle' : type === 'warning' ? 'exclamation-circle' : 'info-circle'}"></i>
        <span>${message}</span>
    `;
    
    toastContainer.appendChild(toast);
    
    setTimeout(() => {
        toast.classList.add('show');
    }, 10);
    
    setTimeout(() => {
        toast.classList.remove('show');
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}

function createToastContainer() {
    const container = document.createElement('div');
    container.id = 'toastContainer';
    container.className = 'toast-container';
    document.body.appendChild(container);
    return container;
}

/**
 * Handle reschedule form submission
 */
async function handleRescheduleFormSubmit(e) {
    e.preventDefault();

    const date = document.getElementById('rescheduleDate')?.value;
    const classType = document.getElementById('rescheduleClass')?.value;
    const trainerId = document.getElementById('rescheduleTrainerInput')?.value;
    const start = document.getElementById('rescheduleStartTimeSelect')?.value;
    const end = document.getElementById('rescheduleEndTimeSelect')?.value;
    const reason = document.getElementById('rescheduleReason')?.value || '';

    if (!date || !classType || !trainerId || !start || !end) {
        showToast('Please complete all fields before confirming.', 'warning');
        return;
    }

    // Validate that selected time is not in the past
    const today = new Date().toISOString().split('T')[0];
    if (date === today) {
        const now = new Date();
        const currentMinutes = now.getHours() * 60 + now.getMinutes();
        
        // Parse start time
        const startParts = start.split(':');
        const startMinutes = parseInt(startParts[0]) * 60 + parseInt(startParts[1]);
        
        if (startMinutes <= currentMinutes) {
            showToast('Cannot book a time slot that has already passed. Please select a future time.', 'error');
            return;
        }
    }

    const startDateTime = `${date} ${start}:00`;
    const endDateTime = `${date} ${end}:00`;

    const formData = new FormData();
    formData.append('booking_id', currentRescheduleBooking.id);
    formData.append('booking_date', date);
    formData.append('class_type', classType);
    formData.append('trainer_id', trainerId);
    formData.append('start_time', startDateTime);
    formData.append('end_time', endDateTime);
    formData.append('reschedule_reason', reason);

    try {
        const res = await fetch('api/update_rescheduling.php', {
            method: 'POST',
            body: formData
        });

        const data = await res.json();

        if (data.success) {
            // Clear booking recovery state aggressively
            if (window.BookingRecovery) {
                window.BookingRecovery.clearState();
                console.log('✅ Cleared booking recovery state (1)');
            }
            
            showRescheduleAlert('Reschedule successful!', 'success');
            
            // Close the modal
            const modal = document.getElementById('rescheduleModal');
            if (modal) {
                modal.style.display = 'none';
            }
            
            // Reload after a delay to allow state clearing to complete
            setTimeout(() => {
                // Triple-clear: clear localStorage and sessionStorage with all possible key names
                try {
                    const keysToRemove = [
                        'booking_state',
                        'bookingRecoveryState',
                        'fit_brawl_booking_state',
                        'fit_brawl_booking_session'
                    ];
                    
                    keysToRemove.forEach(key => {
                        localStorage.removeItem(key);
                        sessionStorage.removeItem(key);
                    });
                    
                    console.log('✅ Cleared all booking storage directly');
                } catch (e) {
                    console.error('Error clearing storage:', e);
                }
                
                // Also use BookingRecovery API
                if (window.BookingRecovery) {
                    window.BookingRecovery.clearState();
                    console.log('✅ Cleared booking recovery state (2)');
                }
                
                location.reload();
            }, 1500);
        } else {
            showRescheduleAlert(data.message || 'Reschedule failed.', 'error');
        }
    } catch (err) {
        showRescheduleAlert('Server error while updating booking.', 'error');
    }
}

// Show alert in reschedule modal
window.showRescheduleAlert = function(message, type = 'error') {
    const alert = document.getElementById('rescheduleAlert');
    const messageEl = alert.querySelector('.reschedule-alert-message');
    
    if (!alert || !messageEl) return;
    
    // Remove previous type classes
    alert.classList.remove('error', 'success', 'warning');
    
    // Add new type class
    alert.classList.add(type);
    
    // Set message
    messageEl.textContent = message;
    
    // Show alert
    alert.style.display = 'block';
    
    // Scroll to alert
    alert.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
};

// Hide alert in reschedule modal
window.hideRescheduleAlert = function() {
    const alert = document.getElementById('rescheduleAlert');
    if (alert) {
        alert.style.display = 'none';
    }
};

// ===================================
// INITIALIZATION
// ===================================

document.addEventListener('DOMContentLoaded', function() {
    const rescheduleForm = document.getElementById('rescheduleForm');
    if (rescheduleForm) {
        rescheduleForm.addEventListener('submit', handleRescheduleFormSubmit);
    }
});
