<?php
/**
 * Booking Validation Helper Functions
 * Handles all validation logic for the new session-based booking system
 */

class BookingValidator
{
    private $conn;

    public function __construct($connection)
    {
        $this->conn = $connection;
    }

    /**
     * Check if trainer specialization matches class type
     */
    public function validateSpecialization($trainer_id, $class_type)
    {
        $stmt = $this->conn->prepare("SELECT specialization FROM trainers WHERE id = ? AND deleted_at IS NULL");
        $stmt->bind_param("i", $trainer_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            return ['valid' => false, 'message' => 'Trainer not found'];
        }

        $trainer = $result->fetch_assoc();
        $stmt->close();

        if ($trainer['specialization'] !== $class_type) {
            return ['valid' => false, 'message' => "This trainer specializes in {$trainer['specialization']}, not {$class_type}"];
        }

        return ['valid' => true];
    }

    /**
     * Check if trainer is on day-off
     */
    public function validateDayOff($trainer_id, $booking_date)
    {
        $day_of_week = date('l', strtotime($booking_date));

        $stmt = $this->conn->prepare("
            SELECT is_day_off
            FROM trainer_day_offs
            WHERE trainer_id = ? AND day_of_week = ? AND is_day_off = 1
        ");
        $stmt->bind_param("is", $trainer_id, $day_of_week);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $stmt->close();
            return ['valid' => false, 'message' => 'Trainer is not available on ' . $day_of_week . 's'];
        }

        $stmt->close();
        return ['valid' => true];
    }

    /**
     * Check if trainer is blocked by admin
     */
    public function validateAdminBlock($trainer_id, $booking_date, $session_time)
    {
        $stmt = $this->conn->prepare("
            SELECT reason
            FROM trainer_availability_blocks
            WHERE trainer_id = ?
            AND date = ?
            AND (session_time = ? OR session_time = 'All Day')
            AND block_status = 'blocked'
        ");
        $stmt->bind_param("iss", $trainer_id, $booking_date, $session_time);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $block = $result->fetch_assoc();
            $stmt->close();
            $reason = $block['reason'] ? ': ' . $block['reason'] : '';
            return ['valid' => false, 'message' => 'Trainer unavailable for this session' . $reason];
        }

        $stmt->close();
        return ['valid' => true];
    }

    /**
     * Check if trainer already has booking for this session
     */
    public function validateTrainerAvailability($trainer_id, $booking_date, $session_time)
    {
        $stmt = $this->conn->prepare("
            SELECT id
            FROM user_reservations
            WHERE trainer_id = ?
            AND booking_date = ?
            AND session_time = ?
            AND booking_status = 'confirmed'
        ");
        $stmt->bind_param("iss", $trainer_id, $booking_date, $session_time);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $stmt->close();
            return ['valid' => false, 'message' => 'Trainer already has a booking for this session'];
        }

        $stmt->close();
        return ['valid' => true];
    }

    /**
     * Check facility capacity (max 2 trainers per class type per session)
     */
    public function validateFacilityCapacity($class_type, $booking_date, $session_time, $exclude_trainer_id = null)
    {
        $query = "
            SELECT COUNT(DISTINCT trainer_id) as trainer_count
            FROM user_reservations
            WHERE class_type = ?
            AND booking_date = ?
            AND session_time = ?
            AND booking_status = 'confirmed'
        ";

        if ($exclude_trainer_id) {
            $query .= " AND trainer_id != ?";
            $stmt = $this->conn->prepare($query);
            $stmt->bind_param("sssi", $class_type, $booking_date, $session_time, $exclude_trainer_id);
        } else {
            $stmt = $this->conn->prepare($query);
            $stmt->bind_param("sss", $class_type, $booking_date, $session_time);
        }

        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();

        $trainer_count = (int) $row['trainer_count'];

        if ($trainer_count >= 2) {
            return [
                'valid' => false,
                'message' => "The {$class_type} facility is at capacity for this session (2/2 trainers booked)",
                'count' => $trainer_count
            ];
        }

        return ['valid' => true, 'count' => $trainer_count];
    }

    /**
     * Check user weekly booking limit (max 12 per week)
     */
    public function validateWeeklyLimit($user_id, $booking_date)
    {
        // Calculate week boundaries (Sunday to Saturday) for the booking date
        $booking_timestamp = strtotime($booking_date);
        $day_of_week = date('w', $booking_timestamp); // 0 (Sunday) to 6 (Saturday)

        // Calculate Sunday of the week
        $week_start = date('Y-m-d', strtotime($booking_date . ' -' . $day_of_week . ' days'));

        // Calculate Saturday of the week
        $week_end = date('Y-m-d', strtotime($week_start . ' +6 days'));

        $stmt = $this->conn->prepare("
            SELECT COUNT(*) as booking_count
            FROM user_reservations
            WHERE user_id = ?
            AND booking_date BETWEEN ? AND ?
            AND booking_status IN ('confirmed', 'completed')
        ");
        $stmt->bind_param("iss", $user_id, $week_start, $week_end);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();

        $booking_count = (int) $row['booking_count'];

        if ($booking_count >= 12) {
            $week_start_formatted = date('M j', strtotime($week_start));
            $week_end_formatted = date('M j', strtotime($week_end));
            return [
                'valid' => false,
                'message' => "You have reached the maximum of 12 bookings for the week of {$week_start_formatted} - {$week_end_formatted}",
                'count' => $booking_count,
                'week_start' => $week_start,
                'week_end' => $week_end
            ];
        }

        return [
            'valid' => true,
            'count' => $booking_count,
            'remaining' => 12 - $booking_count,
            'week_start' => $week_start,
            'week_end' => $week_end
        ];
    }

    /**
     * Check if user already has a booking for this date and session (prevents double booking)
     */
    public function validateUserDoubleBooking($user_id, $booking_date, $session_time)
    {
        $stmt = $this->conn->prepare("
            SELECT class_type, trainer_id
            FROM user_reservations
            WHERE user_id = ?
            AND booking_date = ?
            AND session_time = ?
            AND booking_status = 'confirmed'
        ");
        $stmt->bind_param("iss", $user_id, $booking_date, $session_time);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $existing = $result->fetch_assoc();
            $stmt->close();

            // Get session time display
            $session_display = $session_time === 'Morning' ? '7-11 AM' :
                ($session_time === 'Afternoon' ? '1-5 PM' : '6-10 PM');

            return [
                'valid' => false,
                'message' => "You already have a {$existing['class_type']} booking for {$session_time} session ({$session_display}) on this date"
            ];
        }

        $stmt->close();
        return ['valid' => true];
    }

    /**
     * Check if user has active membership
     */
    public function validateMembership($user_id)
    {
        $stmt = $this->conn->prepare("
            SELECT id
            FROM user_memberships
            WHERE user_id = ?
            AND membership_status = 'active'
            AND end_date >= CURDATE()
        ");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            $stmt->close();
            return ['valid' => false, 'message' => 'You need an active membership to book sessions'];
        }

        $stmt->close();
        return ['valid' => true];
    }

    /**
     * Check if booking date is valid (not past, not too far future)
     */
    public function validateBookingDate($booking_date)
    {
        $today = date('Y-m-d');
        $max_date = date('Y-m-d', strtotime('+30 days'));

        if ($booking_date < $today) {
            return ['valid' => false, 'message' => 'Cannot book sessions in the past'];
        }

        if ($booking_date > $max_date) {
            return ['valid' => false, 'message' => 'Bookings are limited to 30 days in advance'];
        }

        return ['valid' => true];
    }

    /**
     * Check if cancellation is allowed (> 24 hours before session)
     */
    public function validateCancellation($booking_id, $user_id)
    {
        $stmt = $this->conn->prepare("
            SELECT booking_date, session_time, booking_status
            FROM user_reservations
            WHERE id = ? AND user_id = ?
        ");
        $stmt->bind_param("ii", $booking_id, $user_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            $stmt->close();
            return ['valid' => false, 'message' => 'Booking not found'];
        }

        $booking = $result->fetch_assoc();
        $stmt->close();

        if ($booking['booking_status'] !== 'confirmed') {
            return ['valid' => false, 'message' => 'Booking is not in confirmed status'];
        }

        // Get session start time based on session_time
        $session_starts = [
            'Morning' => '07:00:00',
            'Afternoon' => '12:00:00',
            'Evening' => '17:00:00'
        ];

        $session_datetime = $booking['booking_date'] . ' ' . $session_starts[$booking['session_time']];
        $session_timestamp = strtotime($session_datetime);
        $now_timestamp = time();
        $hours_until_session = ($session_timestamp - $now_timestamp) / 3600;

        if ($hours_until_session < 12) {
            return [
                'valid' => false,
                'message' => 'Cancellations must be made at least 12 hours before the session',
                'hours_remaining' => round($hours_until_session, 1)
            ];
        }

        return ['valid' => true];
    }

    /**
     * Run all validations for a booking
     */
    public function validateBooking($user_id, $trainer_id, $class_type, $booking_date, $session_time)
    {
        $validations = [
            'membership' => $this->validateMembership($user_id),
            'booking_date' => $this->validateBookingDate($booking_date),
            'user_double_booking' => $this->validateUserDoubleBooking($user_id, $booking_date, $session_time),
            'specialization' => $this->validateSpecialization($trainer_id, $class_type),
            'day_off' => $this->validateDayOff($trainer_id, $booking_date),
            'admin_block' => $this->validateAdminBlock($trainer_id, $booking_date, $session_time),
            'trainer_available' => $this->validateTrainerAvailability($trainer_id, $booking_date, $session_time),
            'facility_capacity' => $this->validateFacilityCapacity($class_type, $booking_date, $session_time),
            'weekly_limit' => $this->validateWeeklyLimit($user_id, $booking_date)
        ];

        foreach ($validations as $key => $result) {
            if (!$result['valid']) {
                return [
                    'valid' => false,
                    'failed_check' => $key,
                    'message' => $result['message'],
                    'details' => $result
                ];
            }
        }

        return [
            'valid' => true,
            'message' => 'All validations passed',
            'weekly_bookings' => $validations['weekly_limit']['count'],
            'facility_count' => $validations['facility_capacity']['count']
        ];
    }
}
?>
