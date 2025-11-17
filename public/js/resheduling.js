/**
 * Modern Time Selection for Reschedule Modal
 * Mirrors time-selection-modern-v2.js functionality
 */

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
    availableSlots: [], 
    currentWeekUsageMinutes: 0,
    weeklyLimitHours: 48
};
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
window.openRescheduleModal = function(bookingId, element) {
    console.log('openRescheduleModal called with bookingId:', bookingId);
 
    const modalBody = document.querySelector('.modal-body');

    const bookingRow = element.closest('.booking-row');
    if (!bookingRow) return console.error('Could not find booking row');
    // Disable scroll
    document.body.classList.add('modal-open');
    document.documentElement.classList.add('modal-open'); // ensures html doesn't scroll

    // Store booking data
    currentRescheduleBooking = {
        id: bookingId,
        date: bookingRow.querySelector('.booking-date-cell')?.textContent.trim() || '',
        class: bookingRow.querySelector('.booking-class-cell')?.textContent.trim() || '',
        trainer: bookingRow.querySelector('.booking-trainer-cell')?.textContent.trim() || '',
        time: bookingRow.querySelector('.booking-time-cell')?.textContent.trim() || ''
    };
    console.log('Current reschedule booking:', currentRescheduleBooking);

    // Populate original booking details
    document.getElementById('originalDateTime').textContent = `${currentRescheduleBooking.date} ${currentRescheduleBooking.time}`;
    document.getElementById('originalTrainer').textContent = currentRescheduleBooking.trainer;
    document.getElementById('originalClass').textContent = currentRescheduleBooking.class;

    // Reset modal content
    resetRescheduleModal();

    // Populate current booking summary
    populateCurrentBookingSummary();

    // Populate class options
    loadRescheduleClassOptions();

    // Show modal
    const modal = document.getElementById('rescheduleModal');
    if (modal) {
        modal.style.display = 'flex';
        document.body.classList.add('modal-open');
        console.log('✅ Reschedule modal displayed');
    }
};

// CLOSE modal
window.closeRescheduleModal = function() {
     document.body.style.overflow = ''; 
    const modal = document.getElementById('rescheduleModal');
    if (modal) {
        modal.style.display = 'none';
        document.body.classList.remove('modal-open');
    }
    // Enable scroll
    document.body.classList.remove('modal-open');
    document.documentElement.classList.remove('modal-open');

    // Clear booking reference and reset modal
    currentRescheduleBooking = null;
    resetRescheduleModal();
};

window.openRescheduleModal = function(bookingId, element) {
    console.log('openRescheduleModal called with bookingId:', bookingId);
    
    const bookingRow = element.closest('.booking-row');
    if (!bookingRow) {
        console.error('Could not find booking row');
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

    console.log('Current reschedule booking:', currentRescheduleBooking);

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
        document.querySelectorAll('#rescheduleTrainersGrid .trainer-card').forEach(card => {
            card.classList.remove('selected');
        });
    }

    // Populate class options
    loadRescheduleClassOptions();

    // Show modal
    const modal = document.getElementById('rescheduleModal');
    if (modal) {
        modal.style.display = 'flex';
        document.body.classList.add('modal-open');
        console.log('✅ Reschedule modal displayed');
    } else {
        console.error('❌ Reschedule modal element not found!');
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
        console.error('rescheduleClass element not found');
        return;
    }
    
    classSelect.innerHTML = '<option value="">Choose a class...</option>';

    const classFilter = document.getElementById('classFilter');
    if (!classFilter) {
        console.error('classFilter element not found');
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
    if (summary) summary.style.display = 'block';
}   

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
    if (summary) summary.style.display = 'none';

    // Show current booking info
    document.querySelector('.original-booking-info').style.display = '';

    // Reset time selectors
    const startTimeSelect = document.getElementById('rescheduleStartTimeSelect');
    const endTimeSelect = document.getElementById('rescheduleEndTimeSelect');
    if (startTimeSelect) startTimeSelect.value = '';
    if (endTimeSelect) {
        endTimeSelect.value = '';
        endTimeSelect.disabled = true;
    }
}


function handleRescheduleEndTimeSelect(timeStr, state) {
    console.log('Reschedule end time selected:', timeStr);

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
    showRescheduleTimeSummary(state);
    
    // Hide all other content when selection is complete
    hideRescheduleSelectionContent();
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

// Event listeners for date and class changes
document.addEventListener('DOMContentLoaded', function() {
    const rescheduleDate = document.getElementById('rescheduleDate');
    const rescheduleClass = document.getElementById('rescheduleClass');
    const rescheduleForm = document.getElementById('rescheduleForm');

    if (rescheduleDate) {
        rescheduleDate.addEventListener('change', function() {
            console.log('Reschedule date changed to:', this.value);
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
            console.log('Reschedule class changed to:', this.value);
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
        console.error('rescheduleTrainersGrid not found');
        return;
    }

    if (!date || !classType) {
        trainersGrid.innerHTML = '<p class="empty-message">Choose a date and class first</p>';
        document.getElementById('rescheduleTimeSelectionLayout').style.display = 'none';
        document.getElementById('rescheduleAvailabilityBanner').style.display = 'none';
        return;
    }

    console.log('Loading reschedule trainers for date:', date, 'class:', classType);
    trainersGrid.innerHTML = '<p class="loading-text"><i class="fas fa-spinner fa-spin"></i> Loading trainers...</p>';

    fetch(`api/get_available_trainers.php?date=${date}&session=Morning&class=${encodeURIComponent(classType)}`)
        .then(res => res.json())
        .then(data => {
            console.log('Reschedule trainers response:', data);
            if (data.success && data.trainers && data.trainers.length > 0) {
                renderRescheduleTrainers(data.trainers);
            } else {
                trainersGrid.innerHTML = '<p class="empty-message">No trainers available for this date and class</p>';
                document.getElementById('rescheduleTimeSelectionLayout').style.display = 'none';
            }
        })
        .catch(err => {
            console.error('Error loading reschedule trainers:', err);
            trainersGrid.innerHTML = '<p class="empty-message">Error loading trainers</p>';
        });
}

function renderRescheduleTrainers(trainers) {
    const trainersGrid = document.getElementById('rescheduleTrainersGrid');

    if (!trainersGrid) {
        console.error('rescheduleTrainersGrid element not found');
        return;
    }

    if (!trainers || trainers.length === 0) {
        trainersGrid.innerHTML = '<p class="empty-message">No trainers available</p>';
        return;
    }

    trainersGrid.innerHTML = trainers.map(trainer => {
        const escapedName = trainer.name.replace(/'/g, '&#39;').replace(/\"/g, '&quot;');
        const photoSrc = trainer.photo && trainer.photo !== 'account-icon.svg'
            ? `../../uploads/trainers/${trainer.photo}`
            : `../../images/account-icon.svg`;
        return `
            <div class="trainer-card ${trainer.status}"
                 data-trainer-id="${trainer.id}"
                 data-trainer-name="${escapedName}"
                 data-trainer-status="${trainer.status}"
                 onclick="selectRescheduleTrainer(${trainer.id}, '${escapedName}', '${trainer.status}')">
                <span class="trainer-status-badge ${trainer.status}">${trainer.status}</span>
                <img src="${photoSrc}"
                     alt="${escapedName}"
                     class="trainer-photo ${trainer.photo && trainer.photo !== 'account-icon.svg' ? '' : 'default-icon'}"
                     onerror="this.onerror=null; this.src='../../images/account-icon.svg'; this.classList.add('default-icon');">
                <h3 class="trainer-name">${trainer.name}</h3>
                <p class="trainer-specialty">${trainer.specialization}</p>
            </div>
        `;
    }).join('');
}

window.selectRescheduleTrainer = function(trainerId, trainerName, status) {
    if (status === 'unavailable') {
        showToast('This trainer is not available for the selected date', 'warning');
        return;
    }

    if (status === 'booked') {
        showToast('This trainer is already booked for the selected session', 'warning');
        return;
    }

    console.log('Selected reschedule trainer:', trainerId, trainerName);

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

    document.querySelectorAll('#rescheduleTrainersGrid .trainer-card').forEach(card => {
        card.classList.remove('selected');
    });
    
    const selectedCard = document.querySelector(`[data-trainer-id="${trainerId}"]`);
    if (selectedCard) {
        selectedCard.classList.add('selected');
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
        console.warn('Missing required fields:', { date, trainerId, classType });
        return;
    }

    console.log('Loading reschedule trainer availability:', { date, trainerId, classType });

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

    fetch('api/get_trainer_availability.php', { method: 'POST', body: formData })
        .then(res => res.json())
        .then(data => {
            console.log('Reschedule availability response:', data);

            if (data.success && data.available_slots && data.available_slots.length > 0) {
                if (banner) banner.style.display = 'none';
                document.getElementById('rescheduleTimeSelectionLayout').style.display = 'grid';
                
                const rescheduleState = {
                    startTime: null,
                    endTime: null,
                    duration: null,
                    trainerShift: 'Morning',
                    customShift: null,
                    availableSlots: data.available_slots,
                    currentWeekUsageMinutes: data.current_week_usage_minutes || 0,
                    weeklyLimitHours: data.weekly_limit_hours || 48
                };

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
            console.error('Error loading reschedule availability:', err);
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
        console.error('Time select elements not found');
        return;
    }

    const startSlots = generateRescheduleStartTimeOptions(state, shift);
    startSelect.innerHTML = '<option value="">Select start time</option>' + startSlots;

    endSelect.innerHTML = '<option value="">Select start time first</option>';
    endSelect.disabled = true;

    startSelect.addEventListener('change', (e) => {
        if (e.target.value) {
            handleRescheduleStartTimeSelect(e.target.value, state, endSelect, shift);
            updateNewBookingSummary();
        } else {
            state.startTime = null;
            state.endTime = null;
            state.duration = null;

            endSelect.innerHTML = '<option value="">Select start time first</option>';
            endSelect.disabled = true;
            endSelect.value = '';

            hideRescheduleDurationDisplay();
            updateNewBookingSummary();
        }
    });

    endSelect.addEventListener('change', (e) => {
        if (e.target.value) {
            handleRescheduleEndTimeSelect(e.target.value, state);
            updateNewBookingSummary();
        } else {
            state.endTime = null;
            state.duration = null;
            hideRescheduleDurationDisplay();
            updateNewBookingSummary();
        }
    });
}

function generateRescheduleStartTimeOptions(state, shift) {
    const slots = generateRescheduleTimeSlots(shift.start, shift.end, 30);

    const options = slots.map(timeStr => {
        const status = getRescheduleSlotStatus(timeStr, state, shift);
        const isDisabled = status === 'booked' || status === 'break';
        const label = formatRescheduleTime(timeStr);
        const unavailableText = isDisabled ? ' (unavailable)' : '';

        return `<option value="${timeStr}" ${isDisabled ? 'disabled' : ''}>${label}${unavailableText}</option>`;
    });

    return options.join('');
}

function handleRescheduleStartTimeSelect(timeStr, state, endSelect, shift) {
    console.log('Reschedule start time selected:', timeStr);

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
    console.log('Reschedule end time selected:', timeStr);

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
    showRescheduleTimeSummary(state);
    
    // Hide all other content when selection is complete
    hideRescheduleSelectionContent();
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

    console.log('Trainer name found:', trainerName);

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

    console.log('Updating new booking summary:', {
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
        console.log('Updated newBookingDateTime:', dateTimeStr);
    }
    
    if (newTrainerEl) {
        newTrainerEl.textContent = trainerName;
        console.log('Updated newBookingTrainer:', trainerName);
    }
    
    if (newClassEl) {
        newClassEl.textContent = classType;
        console.log('Updated newBookingClass:', classType);
    }
    
    if (newDurationEl) {
        newDurationEl.textContent = durationText;
        console.log('Updated newBookingDuration:', durationText);
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
        console.log('Summary section shown');
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
        console.warn('No current reschedule booking data available');
        return;
    }

    console.log('Populating current booking summary:', currentRescheduleBooking);

    // Update current booking comparison section
    const currentDateTimeEl = document.getElementById('currentBookingDateTime');
    const currentTrainerEl = document.getElementById('currentBookingTrainer');
    const currentClassEl = document.getElementById('currentBookingClass');

    if (currentDateTimeEl) {
        currentDateTimeEl.textContent = `${currentRescheduleBooking.date} ${currentRescheduleBooking.time}`;
        console.log('Updated currentBookingDateTime:', currentDateTimeEl.textContent);
    }
    
    if (currentTrainerEl) {
        currentTrainerEl.textContent = currentRescheduleBooking.trainer || '-';
        console.log('Updated currentBookingTrainer:', currentTrainerEl.textContent);
    }
    
    if (currentClassEl) {
        currentClassEl.textContent = currentRescheduleBooking.class || '-';
        console.log('Updated currentBookingClass:', currentClassEl.textContent);
    }

    // Fetch actual booking duration from server
    if (currentRescheduleBooking.id) {
        fetch(`api/get_booking_duration.php?booking_id=${currentRescheduleBooking.id}`)
            .then(res => res.json())
            .then(data => {
                if (data.success && data.duration_minutes) {
                    const hours = Math.floor(data.duration_minutes / 60);
                    const mins = data.duration_minutes % 60;
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
                        console.log('Updated originalDuration:', durationText);
                    }
                    
                    if (currentDurationEl) {
                        currentDurationEl.textContent = durationText;
                        console.log('Updated currentBookingDuration:', durationText);
                    }
                } else {
                    // Fallback to 30 minutes
                    const originalDurationEl = document.getElementById('originalDuration');
                    const currentDurationEl = document.getElementById('currentBookingDuration');
                    
                    if (originalDurationEl) originalDurationEl.textContent = '30 minutes';
                    if (currentDurationEl) currentDurationEl.textContent = '30 minutes';
                }
            })
            .catch(err => {
                console.error('Error fetching booking duration:', err);
                const originalDurationEl = document.getElementById('originalDuration');
                const currentDurationEl = document.getElementById('currentBookingDuration');
                
                if (originalDurationEl) originalDurationEl.textContent = '30 minutes';
                if (currentDurationEl) currentDurationEl.textContent = '30 minutes';
            });
    } else {
        const originalDurationEl = document.getElementById('originalDuration');
        const currentDurationEl = document.getElementById('currentBookingDuration');
        
        if (originalDurationEl) originalDurationEl.textContent = '30 minutes';
        if (currentDurationEl) currentDurationEl.textContent = '30 minutes';
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

    console.log('Trainer name found:', trainerName);

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

    console.log('Updating new booking summary:', {
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
        console.log('Updated newBookingDateTime:', dateTimeStr);
    }
    
    if (newTrainerEl) {
        newTrainerEl.textContent = trainerName;
        console.log('Updated newBookingTrainer:', trainerName);
    }
    
    if (newClassEl) {
        newClassEl.textContent = classType;
        console.log('Updated newBookingClass:', classType);
    }
    
    if (newDurationEl) {
        newDurationEl.textContent = durationText;
        console.log('Updated newBookingDuration:', durationText);
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
        console.log('Summary section shown');
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
    const parts = timeStr.split(':');
    return parseInt(parts[0]) * 60 + parseInt(parts[1]);
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

    console.log(date);
    console.log(classType);
    console.log(trainerId);
    console.log(start);
    console.log(end);
    if (!date || !classType || !trainerId || !start || !end) {
        showToast('Please complete all fields before confirming.', 'warning');
        return;
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
        console.log('Update response:', data);

        if (data.success) {
            showToast('Reschedule successful!', 'success');
            setTimeout(() => location.reload(), 800);
            closeRescheduleModal();
        } else {
            showToast(data.message || 'Reschedule failed.', 'error');
        }
    } catch (err) {
        console.error(err);
        showToast('Server error while updating booking.', 'error');
    }
}
// ===================================
// INITIALIZATION
// ===================================

document.addEventListener('DOMContentLoaded', function() {
    const rescheduleForm = document.getElementById('rescheduleForm');
    if (rescheduleForm) {
        rescheduleForm.addEventListener('submit', handleRescheduleFormSubmit);
    }
});