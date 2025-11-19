<?php
/**
 * Booking Validation Helper Functions
 * Handles all validation logic for time-based booking system
 * 
 * Features:
 * - 30-minute time slot granularity (7:00 AM - 10:00 PM PHT)
 * - Trainer shift enforcement (morning/afternoon/night)
 * - Break time validation (shift-specific)
 * - 10-minute buffer time between bookings
 * - 48-hour weekly limit per user
 * - Trainer conflict detection with buffer
 */

require_once __DIR__ . '/timezone_helper.php';

class BookingValidator
{
    private $conn;
    private $buffer_minutes = 10;
    private $weekly_hour_limit = 48;
    private $booking_start_hour = 7;
    private $booking_end_hour = 22;
    private $time_slot_minutes = 30;

    public function __construct($connection)
    {
        $this->conn = $connection;
        $this->loadConfig();
    }

    /**
     * Load configuration from booking_config table
     */
    private function loadConfig()
    {
        // Check if booking_config table exists
        $check = $this->conn->query("SHOW TABLES LIKE 'booking_config'");
        if ($check->num_rows === 0) {
            // Table doesn't exist yet, use defaults
            return;
        }
        
        $stmt = $this->conn->prepare("SELECT config_key, config_value FROM booking_config");
        if (!$stmt) {
            // Query failed, use defaults
            return;
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            switch ($row['config_key']) {
                case 'buffer_minutes':
                    $this->buffer_minutes = (int)$row['config_value'];
                    break;
                case 'weekly_hour_limit':
                    $this->weekly_hour_limit = (int)$row['config_value'];
                    break;
                case 'booking_start_hour':
                    $this->booking_start_hour = (int)$row['config_value'];
                    break;
                case 'booking_end_hour':
                    $this->booking_end_hour = (int)$row['config_value'];
                    break;
                case 'time_slot_minutes':
                    $this->time_slot_minutes = (int)$row['config_value'];
                    break;
            }
        }
        $stmt->close();
    }

    /**
     * Validate time format and 30-minute alignment
     */
    public function validateTimeFormat($start_time, $end_time)
    {
        try {
            $start = TimezoneHelper::create($start_time);
            $end = TimezoneHelper::create($end_time);
        } catch (Exception $e) {
            return ['valid' => false, 'message' => 'Invalid datetime format'];
        }

        // Check 30-minute alignment
        if (!TimezoneHelper::isThirtyMinuteAligned($start)) {
            return ['valid' => false, 'message' => 'Start time must be on 30-minute boundary (e.g., 8:00, 8:30)'];
        }

        if (!TimezoneHelper::isThirtyMinuteAligned($end)) {
            return ['valid' => false, 'message' => 'End time must be on 30-minute boundary (e.g., 9:00, 9:30)'];
        }

        // Check booking hours (7am - 10pm)
        if (!TimezoneHelper::isWithinBookingHours($start)) {
            return ['valid' => false, 'message' => 'Booking start time must be between 7:00 AM and 10:00 PM'];
        }

        if (!TimezoneHelper::isWithinBookingHours($end)) {
            return ['valid' => false, 'message' => 'Booking end time must be between 7:00 AM and 10:00 PM'];
        }

        // Check end is after start
        if ($end <= $start) {
            return ['valid' => false, 'message' => 'End time must be after start time'];
        }

        // Check minimum duration (30 minutes)
        $duration_minutes = TimezoneHelper::calculateDurationMinutes($start, $end);
        if ($duration_minutes < 30) {
            return ['valid' => false, 'message' => 'Minimum booking duration is 30 minutes'];
        }

        return [
            'valid' => true,
            'duration_minutes' => $duration_minutes,
            'duration_display' => TimezoneHelper::calculateDuration($start, $end)
        ];
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
     * Check if trainer is on day-off or has shift for this day
     */
    public function validateTrainerShift($trainer_id, $booking_date, $start_time, $end_time)
    {
        $day_of_week = TimezoneHelper::getDayOfWeek($booking_date);

        // Check if trainer has shift assigned for this day
        $stmt = $this->conn->prepare("
            SELECT shift_type, custom_start_time, custom_end_time, break_start_time, break_end_time
            FROM trainer_shifts
            WHERE trainer_id = ? AND day_of_week = ? AND is_active = 1
        ");
        $stmt->bind_param("is", $trainer_id, $day_of_week);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            $stmt->close();
            // Check old day-off system for backwards compatibility
            return $this->validateDayOff($trainer_id, $booking_date);
        }

        $shift = $result->fetch_assoc();
        $stmt->close();

        // Check if shift is 'none' (day off)
        if ($shift['shift_type'] === 'none') {
            return ['valid' => false, 'message' => 'Trainer is not available on ' . $day_of_week . 's'];
        }

        // Determine shift hours
        if ($shift['custom_start_time'] && $shift['custom_end_time']) {
            $shift_start = $booking_date . ' ' . $shift['custom_start_time'];
            $shift_end = $booking_date . ' ' . $shift['custom_end_time'];
        } else {
            // Default shift times
            $shift_times = [
                'morning' => ['07:00:00', '15:00:00'],    // 7am - 3pm
                'afternoon' => ['11:00:00', '19:00:00'],  // 11am - 7pm
                'night' => ['15:00:00', '22:00:00']       // 3pm - 10pm
            ];
            
            if (!isset($shift_times[$shift['shift_type']])) {
                return ['valid' => false, 'message' => 'Invalid shift type'];
            }
            
            $shift_start = $booking_date . ' ' . $shift_times[$shift['shift_type']][0];
            $shift_end = $booking_date . ' ' . $shift_times[$shift['shift_type']][1];
        }

        $start = TimezoneHelper::create($start_time);
        $end = TimezoneHelper::create($end_time);
        $shift_start_dt = TimezoneHelper::create($shift_start);
        $shift_end_dt = TimezoneHelper::create($shift_end);

        // Check if booking is within shift hours
        if ($start < $shift_start_dt || $end > $shift_end_dt) {
            $shift_display = TimezoneHelper::formatTimeRange($shift_start, $shift_end, false);
            return [
                'valid' => false,
                'message' => "Trainer's {$shift['shift_type']} shift is {$shift_display}. Booking must be within these hours."
            ];
        }

        // Check break time conflict
        // Allow bookings that span across breaks (start before, end after)
        // But prevent bookings that START or END during the break
        if ($shift['break_start_time'] && $shift['break_end_time']) {
            $break_start = $booking_date . ' ' . $shift['break_start_time'];
            $break_end = $booking_date . ' ' . $shift['break_end_time'];
            $break_start_dt = TimezoneHelper::create($break_start);
            $break_end_dt = TimezoneHelper::create($break_end);

            // Check if start time is during break (NOT allowed)
            if ($start >= $break_start_dt && $start < $break_end_dt) {
                $break_display = TimezoneHelper::formatTimeRange($break_start, $break_end, false);
                return [
                    'valid' => false,
                    'message' => "Cannot start training during break time ({$break_display})"
                ];
            }

            // Check if end time is during break (NOT allowed)
            if ($end > $break_start_dt && $end <= $break_end_dt) {
                $break_display = TimezoneHelper::formatTimeRange($break_start, $break_end, false);
                return [
                    'valid' => false,
                    'message' => "Cannot end training during break time ({$break_display})"
                ];
            }
            
            // Spanning across break is allowed (start before break, end after break)
        }

        return ['valid' => true, 'shift_type' => $shift['shift_type']];
    }

    /**
     * Legacy: Check if trainer is on day-off (for backwards compatibility)
     */
    public function validateDayOff($trainer_id, $booking_date)
    {
        $day_of_week = date('l', strtotime($booking_date));

        // Check trainer_shifts for day-offs (shift_type='none' OR is_active=0)
        $stmt = $this->conn->prepare("
            SELECT shift_type, is_active
            FROM trainer_shifts
            WHERE trainer_id = ? AND day_of_week = ?
        ");
        $stmt->bind_param("is", $trainer_id, $day_of_week);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $shift = $result->fetch_assoc();
            $stmt->close();
            
            // Day-off if shift_type is 'none' or is_active is 0
            if ($shift['shift_type'] === 'none' || $shift['is_active'] == 0) {
                return ['valid' => false, 'message' => 'Trainer is not available on ' . $day_of_week . 's'];
            }
            
            return ['valid' => true];
        }

        $stmt->close();
        
        // Fallback: Check legacy trainer_day_offs table
        $fallback_stmt = $this->conn->prepare("
            SELECT is_day_off
            FROM trainer_day_offs
            WHERE trainer_id = ? AND day_of_week = ? AND is_day_off = 1
        ");
        $fallback_stmt->bind_param("is", $trainer_id, $day_of_week);
        $fallback_stmt->execute();
        $fallback_result = $fallback_stmt->get_result();

        if ($fallback_result->num_rows > 0) {
            $fallback_stmt->close();
            return ['valid' => false, 'message' => 'Trainer is not available on ' . $day_of_week . 's'];
        }

        $fallback_stmt->close();
        return ['valid' => true];
    }

    /**
     * Check if trainer is blocked by admin for specific time range
     */
    public function validateAdminBlock($trainer_id, $start_time, $end_time)
    {
        $start = TimezoneHelper::create($start_time);
        $end = TimezoneHelper::create($end_time);
        $booking_date = $start->format('Y-m-d');

        $stmt = $this->conn->prepare("
            SELECT reason, is_all_day, block_start_time, block_end_time
            FROM trainer_availability_blocks
            WHERE trainer_id = ?
            AND date = ?
            AND block_status = 'blocked'
        ");
        $stmt->bind_param("is", $trainer_id, $booking_date);
        $stmt->execute();
        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            // Check all-day block
            if ($row['is_all_day']) {
                $stmt->close();
                $reason = $row['reason'] ? ': ' . $row['reason'] : '';
                return ['valid' => false, 'message' => 'Trainer unavailable on this date' . $reason];
            }

            // Check time-specific block
            if ($row['block_start_time'] && $row['block_end_time']) {
                $block_start = TimezoneHelper::create($row['block_start_time']);
                $block_end = TimezoneHelper::create($row['block_end_time']);

                // Check if booking overlaps with block
                if (!($end <= $block_start || $start >= $block_end)) {
                    $stmt->close();
                    $block_display = TimezoneHelper::formatTimeRange($block_start, $block_end, false);
                    $reason = $row['reason'] ? ': ' . $row['reason'] : '';
                    return [
                        'valid' => false,
                        'message' => "Trainer blocked during {$block_display}" . $reason
                    ];
                }
            }
        }

        $stmt->close();
        return ['valid' => true];
    }

    /**
     * Check if trainer has conflicting booking (includes 10-minute buffer)
     */
    public function validateTrainerAvailability($trainer_id, $start_time, $end_time, $exclude_booking_id = null)
    {
        $start = TimezoneHelper::create($start_time);
        $end = TimezoneHelper::create($end_time);

        // Add buffer time to both ends
        $buffer_start = clone $start;
        $buffer_start->modify("-{$this->buffer_minutes} minutes");
        $buffer_end = clone $end;
        $buffer_end->modify("+{$this->buffer_minutes} minutes");

        $query = "
            SELECT id, start_time, end_time, class_type
            FROM user_reservations
            WHERE trainer_id = ?
            AND booking_status = 'confirmed'
            AND start_time IS NOT NULL
            AND end_time IS NOT NULL
            AND (
                (start_time < ? AND end_time > ?)
                OR (start_time >= ? AND start_time < ?)
            )
        ";

        if ($exclude_booking_id) {
            $query .= " AND id != ?";
            $stmt = $this->conn->prepare($query);
            $buffer_end_str = TimezoneHelper::toMySQLDateTime($buffer_end);
            $buffer_start_str = TimezoneHelper::toMySQLDateTime($buffer_start);
            $stmt->bind_param("issssi", $trainer_id, $buffer_end_str, $buffer_start_str, 
                             $buffer_start_str, $buffer_end_str, $exclude_booking_id);
        } else {
            $stmt = $this->conn->prepare($query);
            $buffer_end_str = TimezoneHelper::toMySQLDateTime($buffer_end);
            $buffer_start_str = TimezoneHelper::toMySQLDateTime($buffer_start);
            $stmt->bind_param("issss", $trainer_id, $buffer_end_str, $buffer_start_str, 
                             $buffer_start_str, $buffer_end_str);
        }

        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $conflict = $result->fetch_assoc();
            $stmt->close();
            
            $conflict_time = TimezoneHelper::formatTimeRange($conflict['start_time'], $conflict['end_time'], true);
            return [
                'valid' => false,
                'message' => "Trainer has a conflicting {$conflict['class_type']} booking at {$conflict_time} (includes {$this->buffer_minutes}-min buffer)",
                'conflict_booking_id' => $conflict['id']
            ];
        }

        $stmt->close();
        return ['valid' => true];
    }

    /**
     * Check user weekly hour limit based on their membership plan
     */
    public function validateWeeklyLimit($user_id, $start_time, $end_time, $exclude_booking_id = null)
    {
        // Get user's membership plan and weekly hour limit
        $membership_query = "
            SELECT m.weekly_hours_limit, m.plan_name
            FROM user_memberships um
            JOIN memberships m ON um.plan_id = m.id
            WHERE um.user_id = ?
            AND um.membership_status = 'active'
            AND DATE_ADD(um.end_date, INTERVAL 3 DAY) >= CURDATE()
            ORDER BY um.end_date DESC
            LIMIT 1
        ";
        
        $stmt = $this->conn->prepare($membership_query);
        $stmt->bind_param("s", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $membership = $result->fetch_assoc();
        $stmt->close();
        
        if (!$membership) {
            return [
                'valid' => false,
                'message' => 'No active membership found'
            ];
        }
        
        $weekly_hour_limit = (int)$membership['weekly_hours_limit'];
        $plan_name = $membership['plan_name'];
        
        $start = TimezoneHelper::create($start_time);
        $end = TimezoneHelper::create($end_time);
        $week_info = TimezoneHelper::getWeekNumber($start);
        
        // Calculate week boundaries (Sunday to Saturday)
        $week_start = clone $start;
        $week_start->modify('Sunday this week')->setTime(0, 0, 0);
        $week_end = clone $week_start;
        $week_end->modify('+6 days')->setTime(23, 59, 59);

        // Calculate total minutes booked this week
        $query = "
            SELECT SUM(TIMESTAMPDIFF(MINUTE, start_time, end_time)) as total_minutes
            FROM user_reservations
            WHERE user_id = ?
            AND start_time >= ?
            AND start_time <= ?
            AND booking_status IN ('confirmed', 'completed')
            AND start_time IS NOT NULL
            AND end_time IS NOT NULL
        ";

        if ($exclude_booking_id) {
            $query .= " AND id != ?";
            $stmt = $this->conn->prepare($query);
            $week_start_str = TimezoneHelper::toMySQLDateTime($week_start);
            $week_end_str = TimezoneHelper::toMySQLDateTime($week_end);
            $stmt->bind_param("sssi", $user_id, $week_start_str, $week_end_str, $exclude_booking_id);
        } else {
            $stmt = $this->conn->prepare($query);
            $week_start_str = TimezoneHelper::toMySQLDateTime($week_start);
            $week_end_str = TimezoneHelper::toMySQLDateTime($week_end);
            $stmt->bind_param("sss", $user_id, $week_start_str, $week_end_str);
        }

        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();

        $total_minutes = (int)($row['total_minutes'] ?? 0);
        $new_booking_minutes = TimezoneHelper::calculateDurationMinutes($start, $end);
        $total_after_booking = $total_minutes + $new_booking_minutes;
        $limit_minutes = $weekly_hour_limit * 60;

        if ($total_after_booking > $limit_minutes) {
            $hours_used = floor($total_minutes / 60);
            $minutes_used = $total_minutes % 60;
            $hours_remaining = floor(($limit_minutes - $total_minutes) / 60);
            $minutes_remaining = ($limit_minutes - $total_minutes) % 60;
            
            $week_display = $week_start->format('M j') . ' - ' . $week_end->format('M j');
            
            return [
                'valid' => false,
                'message' => "Weekly limit exceeded. Your {$plan_name} plan allows {$weekly_hour_limit}h/week. You have {$hours_used}h {$minutes_used}m booked this week ({$week_display}). Only {$hours_remaining}h {$minutes_remaining}m remaining.",
                'total_minutes' => $total_minutes,
                'limit_minutes' => $limit_minutes,
                'remaining_minutes' => $limit_minutes - $total_minutes,
                'plan_name' => $plan_name,
                'weekly_hour_limit' => $weekly_hour_limit
            ];
        }

        return [
            'valid' => true,
            'total_minutes' => $total_minutes,
            'limit_minutes' => $limit_minutes,
            'remaining_minutes' => $limit_minutes - $total_minutes,
            'remaining_hours' => floor(($limit_minutes - $total_minutes) / 60)
        ];
    }

    /**
     * Check if user has overlapping booking
     */
    public function validateUserDoubleBooking($user_id, $start_time, $end_time, $exclude_booking_id = null)
    {
        $start = TimezoneHelper::create($start_time);
        $end = TimezoneHelper::create($end_time);

        $query = "
            SELECT id, start_time, end_time, class_type, trainer_id
            FROM user_reservations
            WHERE user_id = ?
            AND booking_status = 'confirmed'
            AND start_time IS NOT NULL
            AND end_time IS NOT NULL
            AND (
                (start_time < ? AND end_time > ?)
                OR (start_time >= ? AND start_time < ?)
            )
        ";

        if ($exclude_booking_id) {
            $query .= " AND id != ?";
            $stmt = $this->conn->prepare($query);
            $end_str = TimezoneHelper::toMySQLDateTime($end);
            $start_str = TimezoneHelper::toMySQLDateTime($start);
            $stmt->bind_param("sssssi", $user_id, $end_str, $start_str, $start_str, $end_str, $exclude_booking_id);
        } else {
            $stmt = $this->conn->prepare($query);
            $end_str = TimezoneHelper::toMySQLDateTime($end);
            $start_str = TimezoneHelper::toMySQLDateTime($start);
            $stmt->bind_param("sssss", $user_id, $end_str, $start_str, $start_str, $end_str);
        }

        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $existing = $result->fetch_assoc();
            $stmt->close();

            $existing_time = TimezoneHelper::formatTimeRange($existing['start_time'], $existing['end_time'], true);
            return [
                'valid' => false,
                'message' => "You already have a {$existing['class_type']} booking at {$existing_time}",
                'existing_booking_id' => $existing['id']
            ];
        }

        $stmt->close();
        return ['valid' => true];
    }

    /**
     * Legacy: Facility capacity check - DEPRECATED for time-based bookings
     * Kept for backwards compatibility with old session-based code
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
        $stmt->bind_param("s", $user_id);
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
     * Supports both legacy (session_time) and time-based (start_time) bookings
     */
    public function validateCancellation($booking_id, $user_id)
    {
        $stmt = $this->conn->prepare("
            SELECT booking_date, session_time, start_time, end_time, booking_status
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

        // Allow cancellation of confirmed bookings only (not completed, cancelled, or blocked)
        if (!in_array($booking['booking_status'], ['confirmed'])) {
            $statusMessage = $booking['booking_status'] === 'blocked' 
                ? 'This session is no longer available. The trainer blocked this time slot after your booking.' 
                : 'Only confirmed bookings can be cancelled. Current status: ' . $booking['booking_status'];
            return ['valid' => false, 'message' => $statusMessage];
        }

        // Determine session start time (time-based vs legacy)
        if (!empty($booking['start_time'])) {
            // Time-based booking
            $session_timestamp = strtotime($booking['start_time']);
        } else {
            // Legacy booking - use session_time
            $session_starts = [
                'Morning' => '07:00:00',
                'Afternoon' => '12:00:00',
                'Evening' => '17:00:00'
            ];
            
            $session_time = $booking['session_time'] ?? 'Morning';
            $session_datetime = $booking['booking_date'] . ' ' . $session_starts[$session_time];
            $session_timestamp = strtotime($session_datetime);
        }

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
     * Run all validations for a time-based booking
     * 
     * @param string $user_id User ID
     * @param int $trainer_id Trainer ID
     * @param string $class_type Class type (Boxing, Muay Thai, MMA, Gym)
     * @param string $start_time Booking start datetime (Y-m-d H:i:s PHT)
     * @param string $end_time Booking end datetime (Y-m-d H:i:s PHT)
     * @param int|null $exclude_booking_id Optional booking ID to exclude (for rescheduling)
     * @return array Validation result with 'valid' boolean and details
     */
    public function validateBooking($user_id, $trainer_id, $class_type, $start_time, $end_time, $exclude_booking_id = null, $skip_weekly_limit = false)
    {
        $booking_date = date('Y-m-d', strtotime($start_time));
        
        $validations = [
            'time_format' => $this->validateTimeFormat($start_time, $end_time),
            'membership' => $this->validateMembership($user_id),
            'booking_date' => $this->validateBookingDate($booking_date),
            'specialization' => $this->validateSpecialization($trainer_id, $class_type),
            'trainer_shift' => $this->validateTrainerShift($trainer_id, $booking_date, $start_time, $end_time),
            'admin_block' => $this->validateAdminBlock($trainer_id, $start_time, $end_time),
            'trainer_available' => $this->validateTrainerAvailability($trainer_id, $start_time, $end_time, $exclude_booking_id),
            'user_double_booking' => $this->validateUserDoubleBooking($user_id, $start_time, $end_time, $exclude_booking_id),
        ];
        
        // Only check weekly limit if not overridden (e.g., by admin)
        if (!$skip_weekly_limit) {
            $validations['weekly_limit'] = $this->validateWeeklyLimit($user_id, $start_time, $end_time, $exclude_booking_id);
        }

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

        $duration = $validations['time_format']['duration_display'];
        $remaining_hours = isset($validations['weekly_limit']) ? $validations['weekly_limit']['remaining_hours'] : null;

        return [
            'valid' => true,
            'message' => 'All validations passed',
            'duration' => $duration,
            'duration_minutes' => $validations['time_format']['duration_minutes'],
            'remaining_hours' => $remaining_hours,
            'shift_type' => $validations['trainer_shift']['shift_type'] ?? null
        ];
    }

    /**
     * Legacy: Session-based validation (for backwards compatibility)
     * @deprecated Use validateBooking() with start_time/end_time instead
     */
    public function validateBookingLegacy($user_id, $trainer_id, $class_type, $booking_date, $session_time)
    {
        $validations = [
            'membership' => $this->validateMembership($user_id),
            'booking_date' => $this->validateBookingDate($booking_date),
            'specialization' => $this->validateSpecialization($trainer_id, $class_type),
            'day_off' => $this->validateDayOff($trainer_id, $booking_date),
            'facility_capacity' => $this->validateFacilityCapacity($class_type, $booking_date, $session_time)
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
            'message' => 'All validations passed (legacy mode)',
            'facility_count' => $validations['facility_capacity']['count']
        ];
    }
}
?>