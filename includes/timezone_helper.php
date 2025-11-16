<?php
/**
 * Timezone Helper Utilities
 * 
 * Provides functions for timezone conversion and formatting
 * All bookings use Philippines Time (Asia/Manila, UTC+8)
 * 
 * @package FitXBrawl
 * @since 1.0.0 (Time-Based Bookings)
 */

// Set default timezone for all date/time operations
date_default_timezone_set('Asia/Manila');

class TimezoneHelper {
    
    /**
     * System timezone
     */
    const TIMEZONE = 'Asia/Manila';
    const TIMEZONE_ABBR = 'PHT';
    const UTC_OFFSET = '+08:00';
    
    /**
     * Convert UTC datetime to Philippines Time
     * 
     * @param string|DateTime $utc_datetime UTC datetime
     * @return DateTime DateTime object in Asia/Manila timezone
     */
    public static function convertToManila($utc_datetime) {
        if ($utc_datetime instanceof DateTime) {
            $dt = clone $utc_datetime;
        } else {
            $dt = new DateTime($utc_datetime, new DateTimeZone('UTC'));
        }
        
        $dt->setTimezone(new DateTimeZone(self::TIMEZONE));
        return $dt;
    }
    
    /**
     * Convert Philippines Time to UTC
     * 
     * @param string|DateTime $manila_datetime Manila datetime
     * @return DateTime DateTime object in UTC timezone
     */
    public static function convertToUTC($manila_datetime) {
        if ($manila_datetime instanceof DateTime) {
            $dt = clone $manila_datetime;
        } else {
            $dt = new DateTime($manila_datetime, new DateTimeZone(self::TIMEZONE));
        }
        
        $dt->setTimezone(new DateTimeZone('UTC'));
        return $dt;
    }
    
    /**
     * Format datetime with PHT timezone indicator
     * 
     * @param string|DateTime $datetime Datetime to format
     * @param string $format PHP date format (default: 'M j, Y g:i A')
     * @param bool $include_timezone Whether to append ' PHT' (default: true)
     * @return string Formatted datetime string
     * 
     * Examples:
     * - formatPHT('2025-12-16 08:00:00') => 'Dec 16, 2025 8:00 AM PHT'
     * - formatPHT('2025-12-16 14:30:00', 'g:i A') => '2:30 PM PHT'
     * - formatPHT('2025-12-16 20:00:00', 'H:i', false) => '20:00'
     */
    public static function formatPHT($datetime, $format = 'M j, Y g:i A', $include_timezone = true) {
        if ($datetime instanceof DateTime) {
            $dt = clone $datetime;
        } else {
            $dt = new DateTime($datetime, new DateTimeZone(self::TIMEZONE));
        }
        
        $formatted = $dt->format($format);
        
        if ($include_timezone) {
            $formatted .= ' ' . self::TIMEZONE_ABBR;
        }
        
        return $formatted;
    }
    
    /**
     * Get current datetime in Philippines Time
     * 
     * @param string $format PHP date format (default: 'Y-m-d H:i:s')
     * @return string|DateTime Current datetime (string if format provided, DateTime otherwise)
     */
    public static function now($format = null) {
        $dt = new DateTime('now', new DateTimeZone(self::TIMEZONE));
        
        if ($format !== null) {
            return $dt->format($format);
        }
        
        return $dt;
    }
    
    /**
     * Create DateTime object in Philippines timezone
     * 
     * @param string $time Time string (e.g., '2025-12-16 08:00:00', 'tomorrow', '+1 day')
     * @return DateTime DateTime object in Asia/Manila timezone
     */
    public static function create($time = 'now') {
        return new DateTime($time, new DateTimeZone(self::TIMEZONE));
    }
    
    /**
     * Format time range (start - end) with duration
     * 
     * @param string|DateTime $start_time Start datetime
     * @param string|DateTime $end_time End datetime
     * @param bool $show_duration Whether to show duration in parentheses (default: true)
     * @return string Formatted time range
     * 
     * Examples:
     * - formatTimeRange('08:00', '09:30') => '8:00 AM - 9:30 AM (1h 30m) PHT'
     * - formatTimeRange('14:00', '15:00', false) => '2:00 PM - 3:00 PM PHT'
     */
    public static function formatTimeRange($start_time, $end_time, $show_duration = true) {
        $start = self::create($start_time);
        $end = self::create($end_time);
        
        $formatted = $start->format('g:i A') . ' - ' . $end->format('g:i A');
        
        if ($show_duration) {
            $duration = self::calculateDuration($start, $end);
            $formatted .= ' (' . $duration . ')';
        }
        
        $formatted .= ' ' . self::TIMEZONE_ABBR;
        
        return $formatted;
    }
    
    /**
     * Calculate duration between two times in human-readable format
     * 
     * @param string|DateTime $start_time Start datetime
     * @param string|DateTime $end_time End datetime
     * @return string Duration string (e.g., '1h 30m', '45m', '2h')
     */
    public static function calculateDuration($start_time, $end_time) {
        $start = $start_time instanceof DateTime ? $start_time : self::create($start_time);
        $end = $end_time instanceof DateTime ? $end_time : self::create($end_time);
        
        $diff = $start->diff($end);
        
        $hours = $diff->h + ($diff->days * 24);
        $minutes = $diff->i;
        
        $duration = '';
        if ($hours > 0) {
            $duration .= $hours . 'h';
        }
        if ($minutes > 0) {
            if ($duration) $duration .= ' ';
            $duration .= $minutes . 'm';
        }
        
        return $duration ?: '0m';
    }
    
    /**
     * Calculate duration in minutes
     * 
     * @param string|DateTime $start_time Start datetime
     * @param string|DateTime $end_time End datetime
     * @return int Duration in minutes
     */
    public static function calculateDurationMinutes($start_time, $end_time) {
        $start = $start_time instanceof DateTime ? $start_time : self::create($start_time);
        $end = $end_time instanceof DateTime ? $end_time : self::create($end_time);
        
        $diff = $start->diff($end);
        return ($diff->days * 24 * 60) + ($diff->h * 60) + $diff->i;
    }
    
    /**
     * Check if time is aligned to 30-minute boundary
     * 
     * @param string|DateTime $time Time to check
     * @return bool True if minutes are 00 or 30
     */
    public static function isThirtyMinuteAligned($time) {
        $dt = $time instanceof DateTime ? $time : self::create($time);
        $minutes = (int) $dt->format('i');
        
        return $minutes === 0 || $minutes === 30;
    }
    
    /**
     * Round time to nearest 30-minute boundary
     * 
     * @param string|DateTime $time Time to round
     * @param string $direction 'up', 'down', or 'nearest' (default: 'nearest')
     * @return DateTime Rounded datetime
     */
    public static function roundToThirtyMinutes($time, $direction = 'nearest') {
        $dt = $time instanceof DateTime ? clone $time : self::create($time);
        $minutes = (int) $dt->format('i');
        
        if ($minutes === 0 || $minutes === 30) {
            return $dt;
        }
        
        if ($direction === 'down') {
            // Round down to nearest 30-min boundary
            if ($minutes < 30) {
                $dt->setTime((int) $dt->format('H'), 0, 0);
            } else {
                $dt->setTime((int) $dt->format('H'), 30, 0);
            }
        } elseif ($direction === 'up') {
            // Round up to next 30-min boundary
            if ($minutes < 30) {
                $dt->setTime((int) $dt->format('H'), 30, 0);
            } else {
                $dt->modify('+1 hour');
                $dt->setTime((int) $dt->format('H'), 0, 0);
            }
        } elseif ($direction === 'nearest') {
            // Round to nearest 30-min boundary
            if ($minutes < 15) {
                $dt->setTime((int) $dt->format('H'), 0, 0);
            } elseif ($minutes < 45) {
                $dt->setTime((int) $dt->format('H'), 30, 0);
            } else {
                $dt->modify('+1 hour');
                $dt->setTime((int) $dt->format('H'), 0, 0);
            }
        }
        
        return $dt;
    }
    
    /**
     * Get day of week from date (Monday, Tuesday, etc.)
     * 
     * @param string|DateTime $date Date to check
     * @return string Day name (e.g., 'Monday')
     */
    public static function getDayOfWeek($date) {
        $dt = $date instanceof DateTime ? $date : self::create($date);
        return $dt->format('l');
    }
    
    /**
     * Check if time is within allowed booking hours (7am - 10pm)
     * 
     * @param string|DateTime $time Time to check
     * @return bool True if within 7am-10pm range
     */
    public static function isWithinBookingHours($time) {
        $dt = $time instanceof DateTime ? $time : self::create($time);
        $hour = (int) $dt->format('H');
        $minute = (int) $dt->format('i');
        
        // 7:00 AM to 10:00 PM (22:00)
        if ($hour < 7 || $hour > 22) {
            return false;
        }
        
        // If it's 22:00 (10pm), only allow exact time, not 22:01+
        if ($hour === 22 && $minute > 0) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Format datetime for MySQL DATETIME column
     * 
     * @param string|DateTime $datetime Datetime to format
     * @return string MySQL datetime format (Y-m-d H:i:s)
     */
    public static function toMySQLDateTime($datetime) {
        $dt = $datetime instanceof DateTime ? $datetime : self::create($datetime);
        return $dt->format('Y-m-d H:i:s');
    }
    
    /**
     * Format date for MySQL DATE column
     * 
     * @param string|DateTime $date Date to format
     * @return string MySQL date format (Y-m-d)
     */
    public static function toMySQLDate($date) {
        $dt = $date instanceof DateTime ? $date : self::create($date);
        return $dt->format('Y-m-d');
    }
    
    /**
     * Format time for MySQL TIME column
     * 
     * @param string|DateTime $time Time to format
     * @return string MySQL time format (H:i:s)
     */
    public static function toMySQLTime($time) {
        $dt = $time instanceof DateTime ? $time : self::create($time);
        return $dt->format('H:i:s');
    }
    
    /**
     * Get week number and year for booking limit calculations
     * 
     * @param string|DateTime $date Date to check
     * @return array ['week' => int, 'year' => int]
     */
    public static function getWeekNumber($date) {
        $dt = $date instanceof DateTime ? $date : self::create($date);
        
        return [
            'week' => (int) $dt->format('W'),
            'year' => (int) $dt->format('o') // ISO-8601 year
        ];
    }
}

?>
