/**
 * Preview Affected Bookings Before Blocking
 * Shows admin which user bookings will be marked as unavailable
 */

async function previewAffectedBookings(trainerId, date, blockStartTime, blockEndTime) {
    try {
        const response = await fetch('/fit-brawl/public/php/admin/api/preview_affected_bookings.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                trainer_id: trainerId,
                date: date,
                block_start_time: blockStartTime,
                block_end_time: blockEndTime
            })
        });

        const data = await response.json();

        if (data.success && data.affected_bookings && data.affected_bookings.length > 0) {
            showAffectedBookingsModal(data.affected_bookings, data.count);
            return true;
        } else {
            return false;
        }
    } catch (error) {
        console.error('Error previewing affected bookings:', error);
        return false;
    }
}

function showAffectedBookingsModal(bookings, count) {
    const modalHTML = `
        <div class="preview-modal-overlay" id="previewAffectedModal">
            <div class="preview-modal-content">
                <div class="preview-modal-header">
                    <h3><i class="fas fa-exclamation-triangle"></i> Affected Bookings Warning</h3>
                    <button class="preview-modal-close" onclick="closePreviewModal()">&times;</button>
                </div>
                <div class="preview-modal-body">
                    <div class="warning-notice">
                        <p><strong>${count} user booking(s)</strong> will be marked as unavailable and users will be notified to reschedule or cancel.</p>
                    </div>
                    <table class="affected-bookings-table">
                        <thead>
                            <tr>
                                <th>User</th>
                                <th>Date & Time</th>
                                <th>Class Type</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${bookings.map(booking => `
                                <tr>
                                    <td>
                                        <strong>${booking.user_name || booking.user_id}</strong><br>
                                        <small>${booking.user_email || 'No email'}</small>
                                    </td>
                                    <td>
                                        ${formatDate(booking.booking_date)}<br>
                                        <small>${formatTime(booking.start_time)} - ${formatTime(booking.end_time)}</small>
                                    </td>
                                    <td><span class="badge badge-class">${booking.class_type}</span></td>
                                    <td><span class="badge badge-confirmed">${booking.booking_status}</span></td>
                                </tr>
                            `).join('')}
                        </tbody>
                    </table>
                    <div class="action-notice">
                        <p><i class="fas fa-info-circle"></i> Users will receive:</p>
                        <ul>
                            <li>Email notification with reschedule/cancel options</li>
                            <li>In-app notification on their dashboard</li>
                            <li>24-hour deadline to take action</li>
                            <li>Automatic cancellation if no action taken</li>
                        </ul>
                    </div>
                </div>
                <div class="preview-modal-footer">
                    <button class="btn btn-secondary" onclick="closePreviewModal()">Cancel</button>
                    <button class="btn btn-danger" onclick="confirmBlock()">
                        <i class="fas fa-ban"></i> Proceed with Block
                    </button>
                </div>
            </div>
        </div>
    `;

    document.body.insertAdjacentHTML('beforeend', modalHTML);
}

function closePreviewModal() {
    const modal = document.getElementById('previewAffectedModal');
    if (modal) {
        modal.remove();
    }
}

function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', { 
        weekday: 'short', 
        year: 'numeric', 
        month: 'short', 
        day: 'numeric' 
    });
}

function formatTime(timeString) {
    const date = new Date(timeString);
    return date.toLocaleTimeString('en-US', { 
        hour: 'numeric', 
        minute: '2-digit',
        hour12: true 
    });
}

// CSS for preview modal
const previewModalCSS = `
<style>
.preview-modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.7);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 10000;
}

.preview-modal-content {
    background: white;
    border-radius: 12px;
    max-width: 800px;
    width: 90%;
    max-height: 80vh;
    overflow-y: auto;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
}

.preview-modal-header {
    background: #e74c3c;
    color: white;
    padding: 20px;
    border-radius: 12px 12px 0 0;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.preview-modal-header h3 {
    margin: 0;
    display: flex;
    align-items: center;
    gap: 10px;
}

.preview-modal-close {
    background: none;
    border: none;
    color: white;
    font-size: 28px;
    cursor: pointer;
    padding: 0;
    width: 30px;
    height: 30px;
    line-height: 1;
}

.preview-modal-body {
    padding: 20px;
}

.warning-notice {
    background: #fff3cd;
    border-left: 4px solid #ffc107;
    padding: 15px;
    margin-bottom: 20px;
    border-radius: 4px;
}

.affected-bookings-table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 20px;
}

.affected-bookings-table th {
    background: #f8f9fa;
    padding: 12px;
    text-align: left;
    font-weight: 600;
    border-bottom: 2px solid #dee2e6;
}

.affected-bookings-table td {
    padding: 12px;
    border-bottom: 1px solid #dee2e6;
}

.badge {
    display: inline-block;
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 12px;
    font-weight: 600;
}

.badge-class {
    background: #3498db;
    color: white;
}

.badge-confirmed {
    background: #2ecc71;
    color: white;
}

.action-notice {
    background: #e7f3ff;
    border-left: 4px solid #3498db;
    padding: 15px;
    border-radius: 4px;
}

.action-notice ul {
    margin: 10px 0 0 20px;
}

.preview-modal-footer {
    padding: 20px;
    border-top: 1px solid #dee2e6;
    display: flex;
    justify-content: flex-end;
    gap: 10px;
}
</style>
`;

// Inject CSS
if (!document.getElementById('preview-modal-styles')) {
    document.head.insertAdjacentHTML('beforeend', previewModalCSS.replace('<style>', '<style id="preview-modal-styles">'));
}
