<?php

if (!function_exists('format_phone_standard')) {
    /**
     * Normalize Philippine mobile numbers to the canonical "+63 9XX XXX XXXX" format.
     * Returns null when the input cannot be normalized to a valid 10-digit local number.
     */
    function format_phone_standard(?string $raw): ?string
    {
        if ($raw === null) {
            return null;
        }

        $digits = preg_replace('/[^0-9]/', '', $raw);
        if ($digits === null || $digits === '') {
            return null;
        }

        if (strpos($digits, '09') === 0 && strlen($digits) === 11) {
            $digits = '63' . substr($digits, 1); // drop leading 0
        } elseif (strpos($digits, '9') === 0 && strlen($digits) === 10) {
            $digits = '63' . $digits;
        }

        if (strpos($digits, '63') === 0 && strlen($digits) === 12) {
            $local = substr($digits, 2); // 10 digits (9XX XXX XXXX)
            return sprintf('+63 %s %s %s',
                substr($local, 0, 3),
                substr($local, 3, 3),
                substr($local, 6, 4)
            );
        }

        return null;
    }
}
