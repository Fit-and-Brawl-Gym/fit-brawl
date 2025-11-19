<?php
/**
 * Booking Conflict Notifier
 * Handles notifications when bookings become unavailable due to trainer blocks
 */

class BookingConflictNotifier
{
    private static $conn;

    public static function init($connection)
    {
        self::$conn = $connection;
    }

    /**
     * Notify user about blocked booking
     * Called when admin blocks a trainer's availability
     * 
     * @param int $booking_id The booking that was affected
     * @param string $user_id The user who owns the booking
     * @param array $booking_details Details about the booking
     * @param string $reason Admin's reason for blocking
     * @param string $admin_id Admin who created the block
     */
    public static function notifyBlockedBooking($booking_id, $user_id, $booking_details, $reason, $admin_id = null)
    {
        if (!self::$conn) {
            error_log("BookingConflictNotifier: Database connection not initialized");
            return false;
        }

        // Get trainer name
        $trainer_name = $booking_details['trainer_name'] ?? 'your trainer';
        $booking_date = date('F j, Y', strtotime($booking_details['booking_date']));
        $start_time = date('g:i A', strtotime($booking_details['start_time']));
        $end_time = date('g:i A', strtotime($booking_details['end_time']));
        $class_type = $booking_details['class_type'] ?? 'training';

        // Create notification message
        $title = "Booking Unavailable - Action Required";
        $message = "Your {$class_type} session with {$trainer_name} on {$booking_date} ({$start_time} - {$end_time}) is no longer available due to trainer scheduling changes.";
        
        if (!empty($reason)) {
            $message .= " Reason: {$reason}.";
        }
        
        $message .= " Please reschedule or cancel this booking within 24 hours, or it will be automatically cancelled.";

        // Insert notification
        $stmt = self::$conn->prepare("
            INSERT INTO user_notifications 
            (user_id, notification_type, title, message, admin_identifier, is_read, sent_via_email, created_at)
            VALUES (?, 'BOOKING_UNAVAILABLE', ?, ?, ?, 0, 1, NOW())
        ");

        $admin_identifier = $admin_id ? "Administrator" : "System";
        $stmt->bind_param('ssss', $user_id, $title, $message, $admin_identifier);
        
        if (!$stmt->execute()) {
            error_log("Failed to create notification for booking {$booking_id}: " . $stmt->error);
            return false;
        }

        // Mark the booking with unavailable timestamp
        $stmt = self::$conn->prepare("
            UPDATE user_reservations 
            SET unavailable_marked_at = NOW(), 
                booking_status = 'blocked'
            WHERE id = ?
        ");
        $stmt->bind_param('i', $booking_id);
        $stmt->execute();

        return true;
    }

    /**
     * Get all blocked bookings for a user that require action
     * 
     * @param string $user_id
     * @return array List of blocked bookings awaiting user action
     */
    public static function getBlockedBookingsRequiringAction($user_id)
    {
        if (!self::$conn) {
            return [];
        }

        $stmt = self::$conn->prepare("
            SELECT 
                ur.id,
                ur.booking_date,
                ur.start_time,
                ur.end_time,
                ur.class_type,
                ur.unavailable_marked_at,
                t.name as trainer_name,
                t.photo as trainer_photo,
                TIMESTAMPDIFF(HOUR, ur.unavailable_marked_at, NOW()) as hours_since_marked
            FROM user_reservations ur
            JOIN trainers t ON ur.trainer_id = t.id
            WHERE ur.user_id = ?
              AND ur.booking_status = 'blocked'
              AND ur.unavailable_marked_at IS NOT NULL
              AND TIMESTAMPDIFF(HOUR, ur.unavailable_marked_at, NOW()) < 24
            ORDER BY ur.unavailable_marked_at ASC
        ");

        $stmt->bind_param('s', $user_id);
        $stmt->execute();
        $result = $stmt->get_result();

        $blocked_bookings = [];
        while ($row = $result->fetch_assoc()) {
            $row['time_remaining'] = 24 - intval($row['hours_since_marked']);
            $blocked_bookings[] = $row;
        }

        return $blocked_bookings;
    }

    /**
     * Auto-cancel bookings that have been blocked for more than 24 hours
     * Should be run via cron job
     * 
     * @return int Number of bookings auto-cancelled
     */
    public static function autoCancelExpiredBlocks()
    {
        if (!self::$conn) {
            return 0;
        }

        // Find bookings blocked for more than 24 hours
        $stmt = self::$conn->prepare("
            SELECT 
                ur.id,
                ur.user_id,
                ur.booking_date,
                ur.start_time,
                ur.end_time,
                ur.class_type,
                t.name as trainer_name,
                u.email as user_email,
                u.username as user_name
            FROM user_reservations ur
            JOIN trainers t ON ur.trainer_id = t.id
            JOIN users u ON ur.user_id = u.id
            WHERE ur.booking_status = 'blocked'
              AND ur.unavailable_marked_at IS NOT NULL
              AND TIMESTAMPDIFF(HOUR, ur.unavailable_marked_at, NOW()) >= 24
        ");

        $stmt->execute();
        $result = $stmt->get_result();
        $expired_bookings = $result->fetch_all(MYSQLI_ASSOC);

        $cancelled_count = 0;

        foreach ($expired_bookings as $booking) {
            // Cancel the booking
            $upd_stmt = self::$conn->prepare("
                UPDATE user_reservations
                SET booking_status = 'cancelled',
                    cancelled_at = NOW()
                WHERE id = ?
            ");
            $upd_stmt->bind_param('i', $booking['id']);
            
            if ($upd_stmt->execute()) {
                $cancelled_count++;

                // Create cancellation notification
                $title = "Booking Auto-Cancelled";
                $booking_date = date('F j, Y', strtotime($booking['booking_date']));
                $start_time = date('g:i A', strtotime($booking['start_time']));
                $end_time = date('g:i A', strtotime($booking['end_time']));
                
                $message = "Your {$booking['class_type']} session with {$booking['trainer_name']} on {$booking_date} ({$start_time} - {$end_time}) has been automatically cancelled as no action was taken within 24 hours.";

                $notif_stmt = self::$conn->prepare("
                    INSERT INTO user_notifications 
                    (user_id, notification_type, title, message, admin_identifier, is_read, sent_via_email, created_at)
                    VALUES (?, 'BOOKING_AUTO_CANCELLED', ?, ?, 'System', 0, 0, NOW())
                ");
                $notif_stmt->bind_param('sss', $booking['user_id'], $title, $message);
                $notif_stmt->execute();

                // Send email notification
                if (!empty($booking['user_email'])) {
                    self::sendAutoCancellationEmail(
                        $booking['user_email'],
                        $booking['user_name'],
                        $booking['trainer_name'],
                        $booking_date,
                        $start_time . ' - ' . $end_time,
                        $booking['class_type']
                    );
                }

                error_log("Auto-cancelled booking {$booking['id']} for user {$booking['user_id']} - 24 hour deadline expired");
            }
        }

        return $cancelled_count;
    }

    /**
     * Send auto-cancellation email
     */
    private static function sendAutoCancellationEmail($email, $user_name, $trainer_name, $date, $time_range, $class_type)
    {
        require_once __DIR__ . '/mail_config.php';

        try {
            $mail = getMailer();
            $mail->addAddress($email, $user_name);
            $mail->Subject = 'Booking Auto-Cancelled - Fit & Brawl Gym';

            $mail->Body = "
                <h2>Booking Automatically Cancelled</h2>
                <p>Dear {$user_name},</p>
                <p>Your {$class_type} session has been automatically cancelled due to inaction:</p>
                <ul>
                    <li><strong>Trainer:</strong> {$trainer_name}</li>
                    <li><strong>Date:</strong> {$date}</li>
                    <li><strong>Time:</strong> {$time_range}</li>
                </ul>
                <p>This booking was marked as unavailable over 24 hours ago due to trainer scheduling changes, and no action was taken to reschedule or cancel it.</p>
                <p>You can book a new session anytime through your dashboard.</p>
                <p>If you have any questions, please contact our support team.</p>
                <br>
                <p>Best regards,<br>Fit & Brawl Gym Team</p>
            ";

            $mail->send();
            return true;
        } catch (Exception $e) {
            error_log("Failed to send auto-cancellation email to {$email}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Check if user has any pending blocked bookings
     * Quick check for dashboard badge
     */
    public static function hasPendingBlockedBookings($user_id)
    {
        if (!self::$conn) {
            return false;
        }

        $stmt = self::$conn->prepare("
            SELECT COUNT(*) as count
            FROM user_reservations
            WHERE user_id = ?
              AND booking_status = 'blocked'
              AND unavailable_marked_at IS NOT NULL
              AND TIMESTAMPDIFF(HOUR, unavailable_marked_at, NOW()) < 24
        ");

        $stmt->bind_param('s', $user_id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();

        return $result['count'] > 0;
    }
}
