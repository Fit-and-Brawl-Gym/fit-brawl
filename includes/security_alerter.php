<?php
/**
 * Security Event Alerter
 * Sends alerts for critical and high-severity security events
 */
class SecurityAlerter {
    private static $conn = null;
    private static $alertThresholds = [
        'critical' => 1,  // Alert immediately for critical events
        'high' => 3,      // Alert after 3 high-severity events in window
        'medium' => 10    // Alert after 10 medium-severity events in window
    ];
    private static $alertWindow = 300; // 5 minutes in seconds
    private static $cooldownPeriod = 600; // 10 minutes cooldown between alerts for same event type
    private static $adminEmails = [];
    private static $enabled = true;

    /**
     * Initialize the alerter
     */
    public static function init($conn, $adminEmails = []) {
        self::$conn = $conn;
        self::$adminEmails = $adminEmails;

        // Load admin emails from environment if not provided
        if (empty(self::$adminEmails)) {
            $envEmail = getenv('ADMIN_EMAIL');
            if ($envEmail) {
                self::$adminEmails = array_filter(array_map('trim', explode(',', $envEmail)));
            }
        }

        // Check if alerting is enabled
        $alertingEnabled = getenv('SECURITY_ALERTING_ENABLED');
        if ($alertingEnabled !== null) {
            self::$enabled = filter_var($alertingEnabled, FILTER_VALIDATE_BOOLEAN);
        }
    }

    /**
     * Check if an alert should be sent and send it if needed
     */
    public static function checkAndAlert($eventType, $severity, $context = []) {
        if (!self::$enabled || empty(self::$adminEmails) || !self::$conn) {
            return false;
        }

        // Only alert for medium, high, or critical severity
        if (!in_array($severity, ['medium', 'high', 'critical'])) {
            return false;
        }

        $threshold = self::$alertThresholds[$severity] ?? 10;

        // For critical events, alert immediately
        if ($severity === 'critical') {
            return self::sendAlert($eventType, $severity, $context);
        }

        // For high and medium, check if threshold is reached
        $eventCount = self::getEventCountInWindow($eventType, $severity);

        if ($eventCount >= $threshold) {
            // Check cooldown to prevent spam
            if (self::isInCooldown($eventType, $severity)) {
                return false;
            }

            return self::sendAlert($eventType, $severity, $context, $eventCount);
        }

        return false;
    }

    /**
     * Get count of events in the alert window
     */
    private static function getEventCountInWindow($eventType, $severity) {
        if (!self::$conn) {
            return 0;
        }

        $stmt = self::$conn->prepare("
            SELECT COUNT(*) as count
            FROM security_events
            WHERE event_type = ?
            AND severity = ?
            AND created_at >= DATE_SUB(NOW(), INTERVAL ? SECOND)
        ");

        if (!$stmt) {
            return 0;
        }

        $windowSeconds = self::$alertWindow;
        $stmt->bind_param("ssi", $eventType, $severity, $windowSeconds);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return (int)($result['count'] ?? 0);
    }

    /**
     * Check if alert is in cooldown period
     */
    private static function isInCooldown($eventType, $severity) {
        if (!self::$conn) {
            return false;
        }

        // Check if we've sent an alert for this event type/severity recently
        $stmt = self::$conn->prepare("
            SELECT COUNT(*) as count
            FROM security_alerts_sent
            WHERE event_type = ?
            AND severity = ?
            AND sent_at >= DATE_SUB(NOW(), INTERVAL ? SECOND)
        ");

        if (!$stmt) {
            // Table might not exist yet, create it
            self::ensureAlertTable();
            return false;
        }

        $cooldownSeconds = self::$cooldownPeriod;
        $stmt->bind_param("ssi", $eventType, $severity, $cooldownSeconds);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return (int)($result['count'] ?? 0) > 0;
    }

    /**
     * Send security alert email
     */
    private static function sendAlert($eventType, $severity, $context = [], $eventCount = 1) {
        if (empty(self::$adminEmails)) {
            return false;
        }

        try {
            require_once __DIR__ . '/mail_config.php';
            require_once __DIR__ . '/email_template.php';

            use PHPMailer\PHPMailer\PHPMailer;
            use PHPMailer\PHPMailer\Exception;

            $mail = new PHPMailer(true);

            $mail->isSMTP();
            $mail->Host = getenv('EMAIL_HOST');
            $mail->SMTPAuth = true;
            $mail->Username = getenv('EMAIL_USER');
            $mail->Password = getenv('EMAIL_PASS');
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = getenv('EMAIL_PORT');

            $mail->setFrom(getenv('EMAIL_USER'), 'Fit & Brawl Security System');

            // Add all admin emails
            foreach (self::$adminEmails as $email) {
                $mail->addAddress($email);
            }

            $mail->isHTML(true);
            $mail->Subject = "[{$severity}] Security Alert: {$eventType}";

            // Build alert content
            $severityColor = [
                'critical' => '#dc2626',
                'high' => '#ea580c',
                'medium' => '#f59e0b'
            ][$severity] ?? '#6b7280';

            $innerHtml = "<div style='border-left: 4px solid {$severityColor}; padding-left: 16px; margin-bottom: 20px;'>";
            $innerHtml .= "<h2 style='color: {$severityColor}; margin-top: 0;'>Security Alert: " . htmlspecialchars(ucfirst(str_replace('_', ' ', $eventType))) . "</h2>";
            $innerHtml .= "<p><strong>Severity:</strong> <span style='color: {$severityColor}; font-weight: bold;'>" . strtoupper($severity) . "</span></p>";

            if ($eventCount > 1) {
                $innerHtml .= "<p><strong>Event Count:</strong> {$eventCount} events in the last " . (self::$alertWindow / 60) . " minutes</p>";
            }

            $innerHtml .= "<p><strong>Time:</strong> " . date('Y-m-d H:i:s') . "</p>";

            // Add context details
            if (!empty($context)) {
                $innerHtml .= "<h3>Event Details:</h3><ul>";

                if (isset($context['ip_address'])) {
                    $innerHtml .= "<li><strong>IP Address:</strong> " . htmlspecialchars($context['ip_address']) . "</li>";
                }
                if (isset($context['user_id'])) {
                    $innerHtml .= "<li><strong>User ID:</strong> " . htmlspecialchars($context['user_id']) . "</li>";
                }
                if (isset($context['username'])) {
                    $innerHtml .= "<li><strong>Username:</strong> " . htmlspecialchars($context['username']) . "</li>";
                }
                if (isset($context['endpoint'])) {
                    $innerHtml .= "<li><strong>Endpoint:</strong> " . htmlspecialchars($context['endpoint']) . "</li>";
                }
                if (isset($context['details'])) {
                    $details = is_array($context['details']) ? json_encode($context['details'], JSON_PRETTY_PRINT) : $context['details'];
                    $innerHtml .= "<li><strong>Details:</strong> <pre style='background: #f3f4f6; padding: 8px; border-radius: 4px; overflow-x: auto;'>" . htmlspecialchars($details) . "</pre></li>";
                }

                $innerHtml .= "</ul>";
            }

            $innerHtml .= "<p style='margin-top: 20px; padding: 12px; background: #fef3c7; border-radius: 4px;'>";
            $innerHtml .= "<strong>Action Required:</strong> Please review the security events in the admin panel and take appropriate action if necessary.";
            $innerHtml .= "</p>";

            $innerHtml .= "</div>";

            applyEmailTemplate($mail, $innerHtml);

            $mail->send();

            // Record that alert was sent
            self::recordAlertSent($eventType, $severity);

            return true;

        } catch (Exception $e) {
            error_log("SecurityAlerter: Failed to send alert - " . $e->getMessage());
            return false;
        }
    }

    /**
     * Record that an alert was sent (for cooldown tracking)
     */
    private static function recordAlertSent($eventType, $severity) {
        if (!self::$conn) {
            return;
        }

        self::ensureAlertTable();

        $stmt = self::$conn->prepare("
            INSERT INTO security_alerts_sent (event_type, severity, sent_at)
            VALUES (?, ?, NOW())
        ");

        if ($stmt) {
            $stmt->bind_param("ss", $eventType, $severity);
            $stmt->execute();
            $stmt->close();
        }
    }

    /**
     * Ensure security_alerts_sent table exists
     */
    private static function ensureAlertTable() {
        if (!self::$conn) {
            return;
        }

        $sql = "CREATE TABLE IF NOT EXISTS security_alerts_sent (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    event_type VARCHAR(50) NOT NULL,
                    severity ENUM('low', 'medium', 'high', 'critical') NOT NULL,
                    sent_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    INDEX idx_event_type (event_type),
                    INDEX idx_severity (severity),
                    INDEX idx_sent_at (sent_at)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";

        self::$conn->query($sql);
    }

    /**
     * Manually trigger an alert (for testing or immediate alerts)
     */
    public static function triggerAlert($eventType, $severity, $context = []) {
        return self::sendAlert($eventType, $severity, $context, 1);
    }
}

