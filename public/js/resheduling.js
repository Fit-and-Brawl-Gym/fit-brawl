/**
 * Modern Time Selection for Reschedule Modal
 * Mirrors time-selection-modern-v2.js functionality
 */

window.RESCHEDULE_TEST = 'LOADED';
console.log('🔥 RESCHEDULE JS FILE STARTED LOADING');

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
    const form = document.getElementById('rescheduleForm');
    if (form) form.reset();

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

    const startTimeSelect = document.getElementById('rescheduleStartTimeSelect');
    const endTimeSelect = document.getElementById('rescheduleEndTimeSelect');
    if (startTimeSelect) startTimeSelect.innerHTML = '<option value="">Select start time</option>';
    if (endTimeSelect) {
        endTimeSelect.innerHTML = '<option value="">Select start time first</option>';
        endTimeSelect.disabled = true;
    }
     const trainersGrid = document.getElementById('rescheduleTrainersGrid');
    if (trainersGrid) {
        trainersGrid.innerHTML = '<p class="loading-text">Choose a date and class...</p>';
    }

    // Clear selected trainer state (kept separately so selection can be re-applied if needed)
    // Don't clear currentRescheduleBooking (we still need original booking)
    const trainerInput = document.getElementById('rescheduleTrainerInput');
    if (trainerInput) trainerInput.value = '';
    window.rescheduleSelectedTrainerName = null;
    window.rescheduleSelectedTrainerShift = null;
    window.rescheduleSelectedTrainerShiftData = null;
}

// OPEN modal
console.log('🔥 DEFINING openRescheduleModal FUNCTION');
window.openRescheduleModal = function(bookingId, element) {
    console.log('🔥 openRescheduleModal CALLED');
    const bookingRow = element.closest('.booking-row');
    if (!bookingRow) {
        return;
    }

    // Extract booking values from the row (defensive)
    const dateCell = bookingRow.querySelector('.booking-date-cell');
    const classCell = bookingRow.querySelector('.booking-class-cell');
    const trainerCell = bookingRow.querySelector('.booking-trainer-cell');
    const timeCell = bookingRow.querySelector('.booking-time-cell');

    // Use data attributes if present (we added data-trainer-id earlier)
    const trainerIdAttr = bookingRow.dataset.trainerId || bookingRow.getAttribute('data-trainer-id') || null;

    // Construct a readable date string if date cell contains day/month elements
    let dateText = '';
    if (dateCell) {
        // try to extract useful textual content
        dateText = dateCell.textContent.trim();
    }

    currentRescheduleBooking = {
        id: bookingId || parseInt(bookingRow.dataset.bookingId || bookingRow.getAttribute('data-booking-id') || 0, 10),
        date: dateText || '',
        class: classCell ? classCell.textContent.trim() : '',
        trainer: trainerCell ? trainerCell.textContent.trim() : '',
        trainer_id: trainerIdAttr ? parseInt(trainerIdAttr, 10) : null,
        time: timeCell ? timeCell.textContent.trim() : ''
    };


    // Populate original booking details in the modal (these are the "Current Booking" items)
    const originalDateTimeEl = document.getElementById('originalDateTime');
    const originalTrainerEl = document.getElementById('originalTrainer');
    const originalClassEl = document.getElementById('originalClass');

    if (originalDateTimeEl) originalDateTimeEl.textContent = `${currentRescheduleBooking.date} ${currentRescheduleBooking.time}`.trim();
    if (originalTrainerEl) originalTrainerEl.textContent = currentRescheduleBooking.trainer || '-';
    if (originalClassEl) originalClassEl.textContent = currentRescheduleBooking.class || '-';

    // Populate class options
    loadRescheduleClassOptions();

    // Ensure date input can't pick past dates
    if (typeof window.__setRescheduleDateMin === 'function') {
        window.__setRescheduleDateMin();
    }
    if (typeof window.__attachRescheduleDateChangeGuard === 'function') {
        window.__attachRescheduleDateChangeGuard();
    }

    // Pre-fill the rescheduleDate with the original booking date if it's parseable and not past
    const dateInput = document.getElementById('rescheduleDate');
    if (dateInput && currentRescheduleBooking && currentRescheduleBooking.date) {
        // Try to parse original date into YYYY-MM-DD. The booking.date cell may be "19 Nov" or similar;
        // so try a best-effort parse using Date.
        let origDate = currentRescheduleBooking.date.trim();

        // If the string includes a year already in YYYY-MM-DD, keep it
        if (/^\d{4}-\d{2}-\d{2}$/.test(origDate)) {
            // ok
        } else {
            // attempt parse
            const parsed = new Date(origDate);
            if (!isNaN(parsed)) {
                const yyyy = parsed.getFullYear();
                const mm = String(parsed.getMonth() + 1).padStart(2, '0');
                const dd = String(parsed.getDate()).padStart(2, '0');
                origDate = `${yyyy}-${mm}-${dd}`;
            } else {
                // fallback: clear origDate to force user to choose
                origDate = '';
            }
        }

        const today = (window.__getTodayISO && typeof window.__getTodayISO === 'function') ? window.__getTodayISO() : (new Date()).toISOString().split('T')[0];
        if (origDate && origDate >= today) {
            dateInput.value = origDate;
            // reset trainer selection and hide times
            document.querySelectorAll('#rescheduleTrainersGrid .trainer-card').forEach(card => card.classList.remove('selected'));
            const trainerInput = document.getElementById('rescheduleTrainerInput');
            if (trainerInput) trainerInput.value = '';
            const timeLayout = document.getElementById('rescheduleTimeSelectionLayout');
            if (timeLayout) timeLayout.style.display = 'none';
            // optionally load trainers automatically
            loadRescheduleTrainersAndAvailability();
        } else {
            dateInput.value = '';
        }
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

    // Populate current booking summary (including duration)
    populateCurrentBookingSummary();

    // Finally show the modal
    const modal = document.getElementById('rescheduleModal');
    if (modal) {
        modal.style.display = 'flex';
        // prevent background scroll
        document.body.classList.add('modal-open');
        document.documentElement.classList.add('modal-open');
        // Also lock scroll using overflow as extra safety
        document.body.style.overflow = 'hidden';
        document.documentElement.style.overflow = 'hidden';
    } else {
        console.error('openRescheduleModal: rescheduleModal element not found');
    }
}

window.closeRescheduleModal = function() {
    const modal = document.getElementById('rescheduleModal');
    if (modal) {
        modal.style.display = 'none';
    }

    // Clear selected trainer highlight but keep original booking intact
    document.querySelectorAll('#rescheduleTrainersGrid .trainer-card').forEach(card => card.classList.remove('selected'));

    // Clear temporary selection state
    const trainerInput = document.getElementById('rescheduleTrainerInput');
    if (trainerInput) trainerInput.value = '';
    window.rescheduleSelectedTrainerName = null;

    currentRescheduleBooking = null;

    // Ensure selection content visible reset
    showRescheduleSelectionContent();

    // Restore page scrolling
    document.body.classList.remove('modal-open');
    document.documentElement.classList.remove('modal-open');
    document.body.style.overflow = '';
    document.documentElement.style.overflow = '';
};

(function() {
    /**
     * Return today's date in YYYY-MM-DD (local) format
     */
    function getTodayISO() {
        const d = new Date();
        const year = d.getFullYear();
        const month = String(d.getMonth() + 1).padStart(2, '0');
        const day = String(d.getDate()).padStart(2, '0');
        return `${year}-${month}-${day}`;
    }

    /**
     * Set the min attribute on the reschedule date input so past dates are disabled.
     * Call this on DOMContentLoaded and whenever opening the reschedule modal.
     */
    function setRescheduleDateMin() {
        const dateInput = document.getElementById('rescheduleDate');
        if (!dateInput) return;
        const today = getTodayISO();
        dateInput.min = today;

        // If there's a current value and it's in the past, clear or clamp to today
        if (dateInput.value) {
            if (dateInput.value < today) {
                // Clear selection so user must pick a valid date
                dateInput.value = '';
            }
        }
    }

    /**
     * Guard: when user changes the reschedule date, block past values (extra safe)
     */
    function attachRescheduleDateChangeGuard() {
        const dateInput = document.getElementById('rescheduleDate');
        if (!dateInput) return;
        dateInput.addEventListener('change', function() {
            const today = getTodayISO();
            if (!this.value) return;
            if (this.value < today) {
                // Reset and notify
                this.value = '';
                showToast('Cannot select a past date. Please choose today or a future date.', 'warning');
            } else {
                // Reset trainer selection when changing date (existing behavior pattern)
                document.querySelectorAll('#rescheduleTrainersGrid .trainer-card').forEach(card => card.classList.remove('selected'));
                const trainerInput = document.getElementById('rescheduleTrainerInput');
                if (trainerInput) trainerInput.value = '';

                // Hide time selection until trainer chosen again
                const timeLayout = document.getElementById('rescheduleTimeSelectionLayout');
                if (timeLayout) timeLayout.style.display = 'none';
                const banner = document.getElementById('rescheduleAvailabilityBanner');
                if (banner) banner.style.display = 'none';

                // Optionally trigger load of trainers/availability
                loadRescheduleTrainersAndAvailability();
            }
        });
    }

    // Expose helpers to file-local scope
    window.__setRescheduleDateMin = setRescheduleDateMin;
    window.__attachRescheduleDateChangeGuard = attachRescheduleDateChangeGuard;
    window.__getTodayISO = getTodayISO;
})();



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
            group.style.display = 'block';
        } else {
            group.style.display = 'none';
        }
    });

    // Keep original booking info visible (do not hide). Users must always see the current booking while choosing new time.
    const originalInfo = document.querySelector('.original-booking-info');
    if (originalInfo) originalInfo.style.display = 'none';
    if (formActions) formActions.style.display = 'none';

    const summary = document.getElementById('rescheduleTimeSummary');
    if (summary) summary.style.display = 'block';
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

    formGroups.forEach(group => {
        group.style.display = '';
    });

    if (formActions) formActions.style.display = 'flex';

    const summary = document.getElementById('rescheduleTimeSummary');
    if (summary) summary.style.display = 'none';

    const originalInfo = document.querySelector('.original-booking-info');
    if (originalInfo) originalInfo.style.display = '';

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
            showRescheduleSelectionContent();
        });
    }

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
        // Check if shift has ended for today
        const shiftEnded = isRescheduleTrainerShiftEnded(trainer, selectedDate);
        // Check if shift start time has passed
        const shiftStartPassed = isRescheduleTrainerShiftStartPassed(trainer, selectedDate);
        // Mark unavailable if shift ended OR if shift start time hasn't passed yet (no available times)
        const effectiveStatus = (shiftEnded || !shiftStartPassed) ? 'unavailable' : trainer.status;
        
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
        
        const statusText = (shiftEnded || !shiftStartPassed) ? 'Unavailable' : trainer.status;
        
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

    // Attach click listeners using delegation to avoid inline handlers and to ensure stable behavior.
    // Remove existing handlers first to avoid duplicates.
    trainersGrid.querySelectorAll('.trainer-card').forEach(card => {
        card.removeEventListener('click', trainerCardClickHandler);
        card.addEventListener('click', trainerCardClickHandler);
    });

    // After rendering, restore selected trainer highlight if any
    const trainerInput = document.getElementById('rescheduleTrainerInput');
    const selectedTrainerId = trainerInput?.value || null;
    const fallbackName = window.rescheduleSelectedTrainerName || null;

    if (selectedTrainerId) {
        const cardToSelect = trainersGrid.querySelector(`[data-trainer-id="${selectedTrainerId}"]`);
        if (cardToSelect) cardToSelect.classList.add('selected');
    } else if (fallbackName) {
        const cards = trainersGrid.querySelectorAll('.trainer-card');
        cards.forEach(c => {
            const nameEl = c.querySelector('.trainer-name');
            if (nameEl && nameEl.textContent.trim() === fallbackName) {
                c.classList.add('selected');
            }
        });
    }
}

window.selectRescheduleTrainer = function(trainerId, trainerName, status) {
    
    if (status === 'unavailable') {
        showToast('This trainer is not available for the selected date', 'warning');
        return;
    }

    // If trainer is booked and NOT the original trainer, block selection
    if (normalizedStatus === 'booked' && !isOriginal) {
        showToast('This trainer is already booked for the selected session', 'warning');
        return;
    }


    const trainerInput = document.getElementById('rescheduleTrainerInput');
    if (!trainerInput) {
        trainerInput = document.createElement('input');
        trainerInput.type = 'hidden';
        trainerInput.id = 'rescheduleTrainerInput';
        document.getElementById('rescheduleForm').appendChild(trainerInput);
    }
    trainerInput.value = trainerId;

    // Save trainer name for summary rendering (fallback if DOM changes)
    window.rescheduleSelectedTrainerName = trainerName;
    
    // Visual selection: remove previous selection first
    document.querySelectorAll('#rescheduleTrainersGrid .trainer-card').forEach(card => card.classList.remove('selected'));
    
    // Get the selected trainer card to extract shift data and set selection
    const selectedCard = document.querySelector(`#rescheduleTrainersGrid .trainer-card[data-trainer-id="${trainerId}"]`);
    if (selectedCard) {
        // Store shift data attributes if they exist
        window.rescheduleSelectedTrainerShift = selectedCard.dataset.trainerShift;
        window.rescheduleSelectedTrainerShiftData = {
            shift_type: selectedCard.dataset.trainerShift,
            shift_start: selectedCard.dataset.shiftStart,
            shift_end: selectedCard.dataset.shiftEnd,
            break_start: selectedCard.dataset.breakStart,
            break_end: selectedCard.dataset.breakEnd
        };
        
        console.log('🔍 Selected trainer shift data:', {
            shift: window.rescheduleSelectedTrainerShift,
            shift_start: selectedCard.dataset.shiftStart,
            shift_end: selectedCard.dataset.shiftEnd,
            full_data: window.rescheduleSelectedTrainerShiftData
        });
        
        selectedCard.classList.add('selected');
        // ensure it's scrolled into view in the modal if necessary
        selectedCard.scrollIntoView({ block: 'nearest', inline: 'nearest' });
    }

    // Immediately update New Booking summary to reflect selected trainer
    updateNewBookingSummary();

    // Load availability for the chosen trainer/date (this will not clear current selection because we set trainerInput first)
    loadRescheduleTrainerAvailability();
};


// Load trainer availability for reschedule (uses get_trainer_availability.php)
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
    document.getElementById('rescheduleTimeSelectionLayout').style.display = 'none';

    const formData = new FormData();
    formData.append('trainer_id', trainerId);
    formData.append('date', date);
    formData.append('class_type', classType);
    
    // Exclude current booking from availability check during reschedule
    
    if (currentRescheduleBooking && currentRescheduleBooking.id) {
        formData.append('exclude_booking_id', currentRescheduleBooking.id);
    }

    // Pass the current booking id to the availability API so it ignores the booking being rescheduled
    if (currentRescheduleBooking && currentRescheduleBooking.id) {
        formData.append('exclude_booking_id', currentRescheduleBooking.id);
    }

    fetch('api/get_trainer_availability.php', { method: 'POST', body: formData })
        .then(res => res.json())
        .then(data => {
            console.log('🔍 API Response:', data);
            console.log('🔍 Stored shift before processing:', window.rescheduleSelectedTrainerShift);

            if (data.success && data.available_slots && data.available_slots.length > 0) {
                if (banner) banner.style.display = 'none';
                document.getElementById('rescheduleTimeSelectionLayout').style.display = 'grid';
                
                // Update global rescheduleState instead of creating a local one
                rescheduleState.startTime = null;
                rescheduleState.endTime = null;
                rescheduleState.duration = null;
                // Use shift from stored trainer data or fallback to API data
                rescheduleState.trainerShift = window.rescheduleSelectedTrainerShift || 'Morning';
                rescheduleState.customShift = null;
                rescheduleState.availableSlots = data.available_slots;
                rescheduleState.currentWeekUsageMinutes = data.current_week_usage_minutes || 0;
                rescheduleState.weeklyLimitHours = data.weekly_limit_hours || 48;

                console.log('🔍 Initial rescheduleState.trainerShift:', rescheduleState.trainerShift);

                if (data.shift_info && data.shift_info.start_time && data.shift_info.end_time) {
                    rescheduleState.customShift = {
                        start: normalizeToHHMM(data.shift_info.start_time) || data.shift_info.start_time,
                        end: normalizeToHHMM(data.shift_info.end_time) || data.shift_info.end_time,
                        breakStart: data.shift_info.break_start ? normalizeToHHMM(data.shift_info.break_start) : null,
                        breakEnd: data.shift_info.break_end ? normalizeToHHMM(data.shift_info.break_end) : null
                    };
                    // Set trainer shift type from API data (this will override the stored shift if available)
                    if (data.shift_info.shift_type) {
                        rescheduleState.trainerShift = capitalizeFirstLetter(data.shift_info.shift_type);
                    }
                } else if (window.rescheduleSelectedTrainerShiftData) {
                    // Fallback to stored shift data if API doesn't provide it
                    const storedShift = window.rescheduleSelectedTrainerShiftData;
                    if (storedShift.shift_start && storedShift.shift_end) {
                        rescheduleState.customShift = {
                            start: normalizeToHHMM(storedShift.shift_start) || storedShift.shift_start,
                            end: normalizeToHHMM(storedShift.shift_end) || storedShift.shift_end,
                            breakStart: storedShift.break_start ? normalizeToHHMM(storedShift.break_start) : null,
                            breakEnd: storedShift.break_end ? normalizeToHHMM(storedShift.break_end) : null
                        };
                    }
                }

                console.log('🔍 Final rescheduleState:', {
                    trainerShift: rescheduleState.trainerShift,
                    customShift: rescheduleState.customShift
                });

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
function capitalizeFirstLetter(s) {
    if (!s) return s;
    return s.charAt(0).toUpperCase() + s.slice(1);
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

    // Prefer shift from API (state.customShift), fallback to static mapping by shift name
    const shift = state.customShift || RESCHEDULE_TRAINER_SHIFTS[state.trainerShift] || RESCHEDULE_TRAINER_SHIFTS['Morning'];
    const shiftStart = normalizeToHHMM(shift.start) || shift.start;
    const shiftEnd = normalizeToHHMM(shift.end) || shift.end;
    const breakStart = shift.breakStart ? normalizeToHHMM(shift.breakStart) : null;
    const breakEnd = shift.breakEnd ? normalizeToHHMM(shift.breakEnd) : null;

    const slots = generateRescheduleTimeSlots(shiftStart, shiftEnd, 30);

    let html = '';

    if (data.trainer_name) {
        const shiftHours = `${formatRescheduleTime(shiftStart)} - ${formatRescheduleTime(shiftEnd)}`;
        const breakHours = (breakStart && breakEnd) ? `${formatRescheduleTime(breakStart)} - ${formatRescheduleTime(breakEnd)}` : 'None';
        html += `
            <div class="trainer-info-card">
                <div class="trainer-details">
                    <div class="trainer-name">${data.trainer_name || 'Trainer'}</div>
                    <div class="trainer-shift-badge">
                        <i class="fas fa-clock"></i>
                        ${state.trainerShift} Shift
                    </div>
                    <div class="trainer-hours">${shiftHours}</div>
                    <div class="trainer-break">Break: ${breakHours}</div>
                </div>
            </div>
        `;
    }

    html += '<div class="timeline-slots">';

    slots.forEach(slot => {
        const status = getRescheduleSlotStatus(slot, state, { start: shiftStart, end: shiftEnd, breakStart, breakEnd });
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


function isRescheduleBreakTime(time, shift) {
    if (!shift.breakStart || !shift.breakEnd) return false;
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
            // slot.start_time may be datetime -> parseRescheduleTime accepts that now
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
    // Prefer customShift if provided
    const shift = state.customShift || RESCHEDULE_TRAINER_SHIFTS[state.trainerShift] || RESCHEDULE_TRAINER_SHIFTS['Morning'];
    const startSelect = document.getElementById('rescheduleStartTimeSelect');
    const endSelect = document.getElementById('rescheduleEndTimeSelect');

    if (!startSelect || !endSelect) {
        return;
    }

    const normalizedShift = {
        start: normalizeToHHMM(shift.start) || shift.start,
        end: normalizeToHHMM(shift.end) || shift.end,
        breakStart: shift.breakStart ? normalizeToHHMM(shift.breakStart) : null,
        breakEnd: shift.breakEnd ? normalizeToHHMM(shift.breakEnd) : null
    };

    const startSlots = generateRescheduleTimeSlots(normalizedShift.start, normalizedShift.end, 30);
    const options = startSlots.map(timeStr => {
        const status = getRescheduleSlotStatus(timeStr, state, normalizedShift);
        const isDisabled = status === 'booked' || status === 'break' || status === 'unavailable';
        const label = formatRescheduleTime(timeStr);
        const unavailableText = isDisabled ? ' (unavailable)' : '';
        return `<option value="${timeStr}" ${isDisabled ? 'disabled' : ''}>${label}${unavailableText}</option>`;
    });

    startSelect.innerHTML = '<option value="">Select start time</option>' + options.join('');
    endSelect.innerHTML = '<option value="">Select start time first</option>';
    endSelect.disabled = true;

    startSelect.addEventListener('change', (e) => {
        if (e.target.value) {
            handleRescheduleStartTimeSelect(e.target.value, state, endSelect, normalizedShift);
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

    const slots = [];
    for (let time = minEndMinutes; time <= shiftEndMinutes; time += 30) {
        slots.push(formatRescheduleTimeFromMinutes(time));
    }

    const options = slots.map(timeStr => {
        const endMinutes = parseRescheduleTime(timeStr);
        const isDuringBreak = isRescheduleBreakTime(timeStr, shift);
        const hasConflict = checkRescheduleTimeRangeConflict(startMinutes, endMinutes, state.availableSlots);
        const isDisabled = isDuringBreak || hasConflict;
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

function checkRescheduleTimeRangeConflict(startMinutes, endMinutes, availableSlots) {
    if (!availableSlots || availableSlots.length === 0) return false;

    for (const slot of availableSlots) {
        if (slot.status === 'booked' || slot.status === 'unavailable') {
            const slotStart = parseRescheduleTime(slot.start_time);
            const slotEnd = parseRescheduleTime(slot.end_time);
            if (startMinutes < slotEnd && endMinutes > slotStart) {
                return true;
            }
        }
    }
    return false;
}

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
 * Update new booking summary as user makes selections
 */
function updateNewBookingSummary() {
    const dateInput = document.getElementById('rescheduleDate')?.value;
    const startTime = document.getElementById('rescheduleStartTimeSelect')?.value;
    const endTime = document.getElementById('rescheduleEndTimeSelect')?.value;
    const classType = document.getElementById('rescheduleClass')?.value;
    const trainerInput = document.getElementById('rescheduleTrainerInput');
    const trainerId = trainerInput ? trainerInput.value : '';
    const reason = document.getElementById('rescheduleReason')?.value || '';

    // If any required is missing, hide summary
    if (!dateInput || !startTime || !endTime || !classType || !trainerId) {
        const summary = document.getElementById('rescheduleTimeSummary');
        if (summary) summary.style.display = 'none';
        return;
    }

    // Trainer name: prefer selected card text, fallback to stored name
    let trainerName = '-';
    const selectedCard = document.querySelector(`#rescheduleTrainersGrid .trainer-card[data-trainer-id="${trainerId}"]`);
    if (selectedCard) {
        const nameEl = selectedCard.querySelector('.trainer-name');
        trainerName = nameEl ? nameEl.textContent.trim() : (window.rescheduleSelectedTrainerName || '-');
    } else if (window.rescheduleSelectedTrainerName) {
        trainerName = window.rescheduleSelectedTrainerName;
    }

    // Format date/time/duration
    const bookingDate = new Date(dateInput);
    const dateStr = bookingDate.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
    const formattedStartTime = formatRescheduleTime(startTime);
    const formattedEndTime = formatRescheduleTime(endTime);
    const dateTimeStr = `${dateStr} ${formattedStartTime} - ${formattedEndTime}`;

    const startMinutes = parseRescheduleTime(startTime);
    const endMinutes = parseRescheduleTime(endTime);
    const duration = endMinutes - startMinutes;
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

        dateTimeStr,
        trainerName,
        classType,
        durationText
    });

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

function populateCurrentBookingSummary() {
    if (!currentRescheduleBooking) {
        console.warn('No current reschedule booking data available');
        // Ensure placeholders are shown
        const originalDateTimeEl = document.getElementById('originalDateTime');
        const originalTrainerEl = document.getElementById('originalTrainer');
        const originalClassEl = document.getElementById('originalClass');
        if (originalDateTimeEl) originalDateTimeEl.textContent = '-';
        if (originalTrainerEl) originalTrainerEl.textContent = '-';
        if (originalClassEl) originalClassEl.textContent = '-';
        return;
    }

    // Update current booking card immediately from existing data
    const currentDateTimeEl = document.getElementById('currentBookingDateTime');
    const currentTrainerEl = document.getElementById('currentBookingTrainer');
    const currentClassEl = document.getElementById('currentBookingClass');

    if (currentDateTimeEl) {
        currentDateTimeEl.textContent = `${currentRescheduleBooking.date} ${currentRescheduleBooking.time}`;
    }
    if (currentTrainerEl) currentTrainerEl.textContent = currentRescheduleBooking.trainer || '-';
    if (currentClassEl) currentClassEl.textContent = currentRescheduleBooking.class || '-';

    // Fetch actual booking duration from server
    if (currentRescheduleBooking.id) {
        console.log('📊 Fetching booking duration for ID:', currentRescheduleBooking.id);
        fetch(`api/get_booking_details.php?booking_id=${currentRescheduleBooking.id}`)
            .then(res => {
                console.log('📊 Response status:', res.status, res.statusText);
                if (!res.ok) {
                    throw new Error(`HTTP ${res.status}: ${res.statusText}`);
                }
                return res.json();
            })
            .then(data => {
                console.log('📊 Booking details response:', data);
                if (data.success && data.booking) {
                    const booking = data.booking;
                    
                    // Calculate duration from start_time and end_time
                    if (booking.start_time && booking.end_time) {
                        console.log('📊 Raw times:', booking.start_time, 'to', booking.end_time);
                        
                        try {
                            // Parse as Date objects (handles full datetime strings)
                            const startTime = new Date(booking.start_time);
                            const endTime = new Date(booking.end_time);
                            
                            // Calculate duration in minutes
                            const durationMinutes = Math.round((endTime - startTime) / (1000 * 60));
                            
                            console.log('📊 Calculated duration:', durationMinutes, 'minutes');
                            
                            if (isNaN(durationMinutes) || durationMinutes <= 0) {
                                throw new Error('Invalid duration calculated');
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
                            
                            console.log('📊 Duration text:', durationText);
                            
                            // Update both the original duration display and the current booking summary
                            const originalDurationEl = document.getElementById('originalDuration');
                            const currentDurationEl = document.getElementById('currentBookingDuration');
                            
                            if (originalDurationEl) {
                                originalDurationEl.textContent = durationText;
                                console.log('✅ Updated originalDuration to:', durationText);
                            }
                            
                            if (currentDurationEl) {
                                currentDurationEl.textContent = durationText;
                                console.log('✅ Updated currentBookingDuration to:', durationText);
                            }
                        } catch (parseError) {
                            console.error('❌ Error parsing time:', parseError);
                            throw parseError;
                        }
                    } else {
                        // Fallback if no time data
                        console.error('❌ No start_time or end_time in booking data');
                        throw new Error('No time data available');
                    }
                } else {
                    console.error('❌ Invalid API response:', data);
                    throw new Error('Invalid response');
                }
            })
            .catch(err => {
                console.error('❌ Error fetching booking duration:', err);
                const originalDurationEl = document.getElementById('originalDuration');
                const currentDurationEl = document.getElementById('currentBookingDuration');
                if (originalDurationEl) originalDurationEl.textContent = 'N/A';
                if (currentDurationEl) currentDurationEl.textContent = 'N/A';
            });
    } else {
        console.warn('⚠️ No booking ID available for duration fetch');
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

        dateTimeStr,
        trainerName,
        classType,
        durationText
    });

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
function normalizeToHHMM(timeStr) {
    if (!timeStr) return null;
    if (timeStr.indexOf(' ') !== -1) timeStr = timeStr.split(' ')[1];
    const parts = timeStr.split(':');
    if (parts.length >= 2) {
        const hh = String(parseInt(parts[0], 10)).padStart(2, '0');
        const mm = String(parseInt(parts[1], 10)).padStart(2, '0');
        return `${hh}:${mm}`;
    }
    return null;
}
function parseRescheduleTime(timeStr) {
    if (!timeStr) return 0;
    if (timeStr.indexOf(' ') !== -1) timeStr = timeStr.split(' ')[1];
    const parts = timeStr.split(':');
    const hours = parseInt(parts[0], 10) || 0;
    const minutes = parseInt(parts[1], 10) || 0;
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
    let t = timeStr;
    if (t.indexOf(' ') !== -1) t = t.split(' ')[1];
    const parts = t.split(':');
    const hour = parseInt(parts[0], 10) || 0;
    const minute = parseInt(parts[1], 10) || 0;
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
            showToast('Reschedule successful!', 'success');
            // Clear booking recovery state before reload
            if (window.BookingRecovery) {
                window.BookingRecovery.clearState();
            }
            setTimeout(() => location.reload(), 800);
            closeRescheduleModal();
        } else {
            showToast(data.message || 'Reschedule failed.', 'error');
        }
    } catch (err) {
        showToast('Server error while updating booking.', 'error');
    }
}


// ===================================
// INITIALIZATION
// ===================================
document.addEventListener('DOMContentLoaded', function() {
    // Attach form submit
    const rescheduleForm = document.getElementById('rescheduleForm');
    if (rescheduleForm) {
        rescheduleForm.addEventListener('submit', handleRescheduleFormSubmit);
    }

    // Ensure date guard functions are set at load (these are defined elsewhere in the file or in main bundle)
    if (typeof window.__setRescheduleDateMin === 'function') {
        window.__setRescheduleDateMin();
    }
    if (typeof window.__attachRescheduleDateChangeGuard === 'function') {
        window.__attachRescheduleDateChangeGuard();
    }
});
