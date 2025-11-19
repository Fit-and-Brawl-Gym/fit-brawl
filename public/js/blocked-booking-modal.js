/**
 * Blocked Booking Modal Handler
 * Shows warning modal when user has bookings marked as unavailable
 */

// Check for blocked bookings on page load
document.addEventListener('DOMContentLoaded', function() {
    checkForBlockedBookings();
});

async function checkForBlockedBookings() {
    try {
        const response = await fetch('/fit-brawl/public/php/api/get_blocked_bookings.php');
        const data = await response.json();

        if (data.success && data.blocked_bookings && data.blocked_bookings.length > 0) {
            showBlockedBookingsModal(data.blocked_bookings);
        }
    } catch (error) {
        console.error('Error checking blocked bookings:', error);
    }
}

function showBlockedBookingsModal(blockedBookings) {
    // Create modal HTML
    const modalHTML = `
        <div class="blocked-booking-modal show" id="blockedBookingModal">
            <div class="blocked-booking-content">
                <div class="blocked-booking-header">
                    <i class="fas fa-exclamation-triangle"></i>
                    <h2>Urgent: Booking Action Required</h2>
                    <button class="modal-close-btn" onclick="closeBlockedBookingModal()">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                
                <div class="blocked-booking-body">
                    <div class="urgent-notice">
                        <p>
                            <i class="fas fa-info-circle"></i>
                            The following booking(s) are no longer available due to trainer scheduling changes. 
                            Please reschedule or cancel within 24 hours, or they will be automatically cancelled.
                        </p>
                    </div>

                    <div class="blocked-booking-list">
                        ${blockedBookings.map(booking => generateBookingCard(booking)).join('')}
                    </div>
                </div>

                <div class="blocked-booking-footer">
                    <p>You will receive an email confirmation once you take action on these bookings.</p>
                </div>
            </div>
        </div>
    `;

    // Insert modal into page
    document.body.insertAdjacentHTML('beforeend', modalHTML);

    // Allow closing by clicking outside
    document.getElementById('blockedBookingModal').addEventListener('click', function(e) {
        if (e.target === this) {
            closeBlockedBookingModal();
        }
    });
}

function closeBlockedBookingModal() {
    const modal = document.getElementById('blockedBookingModal');
    if (modal) {
        modal.classList.remove('show');
        setTimeout(() => modal.remove(), 300);
    }
}

function generateBookingCard(booking) {
    const trainerPhoto = booking.trainer_photo && booking.trainer_photo !== 'account-icon.svg' 
        ? `/fit-brawl/uploads/trainers/${booking.trainer_photo}` 
        : '/fit-brawl/images/account-icon.svg';

    const bookingDate = new Date(booking.booking_date);
    const startTime = new Date(booking.start_time);
    const endTime = new Date(booking.end_time);

    const formattedDate = bookingDate.toLocaleDateString('en-US', { 
        weekday: 'long', 
        year: 'numeric', 
        month: 'long', 
        day: 'numeric' 
    });

    const formattedStartTime = startTime.toLocaleTimeString('en-US', { 
        hour: 'numeric', 
        minute: '2-digit',
        hour12: true 
    });

    const formattedEndTime = endTime.toLocaleTimeString('en-US', { 
        hour: 'numeric', 
        minute: '2-digit',
        hour12: true 
    });

    return `
        <div class="blocked-booking-item" data-booking-id="${booking.id}">
            <div class="booking-trainer-info">
                <img src="${trainerPhoto}" 
                     alt="${booking.trainer_name}" 
                     class="booking-trainer-photo"
                     onerror="this.src='/fit-brawl/images/account-icon.svg'">
                <div class="booking-trainer-name">${booking.trainer_name}</div>
            </div>

            <div class="booking-details">
                <div class="booking-detail-item">
                    <i class="fas fa-dumbbell"></i>
                    <span>${booking.class_type}</span>
                </div>
                <div class="booking-detail-item">
                    <i class="fas fa-calendar"></i>
                    <span>${formattedDate}</span>
                </div>
                <div class="booking-detail-item">
                    <i class="fas fa-clock"></i>
                    <span>${formattedStartTime} - ${formattedEndTime}</span>
                </div>
            </div>

            <div class="time-remaining">
                <i class="fas fa-hourglass-half"></i>
                Time remaining to take action:
                <strong>${booking.time_remaining} hours</strong>
            </div>

            <div class="booking-actions">
                <button class="btn-reschedule" onclick="rescheduleBlockedBooking(${booking.id})">
                    <i class="fas fa-calendar-alt"></i>
                    Reschedule
                </button>
                <button class="btn-cancel" onclick="cancelBlockedBooking(${booking.id})">
                    <i class="fas fa-times"></i>
                    Cancel Booking
                </button>
            </div>
        </div>
    `;
}

async function rescheduleBlockedBooking(bookingId) {
    // Close modal
    const modal = document.getElementById('blockedBookingModal');
    if (modal) {
        modal.remove();
    }

    // Redirect to reservations page with reschedule mode
    window.location.href = `/fit-brawl/public/php/reservations.php?reschedule=${bookingId}&from_blocked=true`;
}

async function cancelBlockedBooking(bookingId) {
    if (!confirm('Are you sure you want to cancel this booking? This action cannot be undone.')) {
        return;
    }

    try {
        // Get CSRF token
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
        
        // Create form data
        const formData = new FormData();
        formData.append('booking_id', bookingId);
        formData.append('from_blocked', 'true');
        formData.append('csrf_token', csrfToken);

        const response = await fetch('/fit-brawl/public/php/api/cancel_booking.php', {
            method: 'POST',
            body: formData
        });

        const data = await response.json();

        if (data.success) {
            showToast('Booking cancelled successfully', 'success');
            
            // Remove the booking card
            const bookingCard = document.querySelector(`[data-booking-id="${bookingId}"]`);
            if (bookingCard) {
                bookingCard.remove();
            }

            // Check if there are any remaining bookings
            const remainingBookings = document.querySelectorAll('.blocked-booking-item');
            if (remainingBookings.length === 0) {
                // Close modal if no more blocked bookings
                const modal = document.getElementById('blockedBookingModal');
                if (modal) {
                    modal.remove();
                }
                showToast('All blocked bookings have been resolved', 'success');
            }
        } else {
            showToast(data.message || 'Failed to cancel booking', 'error');
        }
    } catch (error) {
        console.error('Error cancelling booking:', error);
        showToast('An error occurred while cancelling the booking', 'error');
    }
}

function showToast(message, type = 'info') {
    // Check if global toast function exists (not this one)
    if (window.toast && typeof window.toast === 'function') {
        window.toast(message, type);
    } else if (window.Toastify) {
        // Use Toastify if available
        window.Toastify({
            text: message,
            duration: 3000,
            gravity: "top",
            position: "right",
            style: {
                background: type === 'success' ? '#2ecc71' : type === 'error' ? '#e74c3c' : '#3498db'
            }
        }).showToast();
    } else {
        // Fallback to alert
        alert(message);
    }
}
