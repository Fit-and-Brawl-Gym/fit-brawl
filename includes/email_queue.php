<?php
/**
 * Email Queue System
 * Allows emails to be queued and sent in background, improving form submission speed.
 *
 * For Render/production: Emails are sent immediately but with optimized SMTP settings
 * For better performance: Set up a cron job to process the queue
 */

require_once __DIR__ . '/db_connect.php';

class EmailQueue {
    private static $conn = null;
    private static $initialized = false;

    /**
     * Initialize the email queue with database connection
     */
    public static function init($connection = null) {
        if ($connection) {
            self::$conn = $connection;
        } elseif (isset($GLOBALS['conn'])) {
            self::$conn = $GLOBALS['conn'];
        }

        if (self::$conn && !self::$initialized) {
            self::ensureTableExists();
            self::$initialized = true;
        }
    }

    /**
     * Ensure the email_queue table exists
     */
    private static function ensureTableExists() {
        if (!self::$conn) return;

        $sql = "CREATE TABLE IF NOT EXISTS email_queue (
            id INT AUTO_INCREMENT PRIMARY KEY,
            to_email VARCHAR(255) NOT NULL,
            to_name VARCHAR(255) DEFAULT NULL,
            subject VARCHAR(500) NOT NULL,
            body_html TEXT NOT NULL,
            body_text TEXT DEFAULT NULL,
            priority TINYINT DEFAULT 5,
            status ENUM('pending', 'processing', 'sent', 'failed') DEFAULT 'pending',
            attempts INT DEFAULT 0,
            max_attempts INT DEFAULT 3,
            error_message TEXT DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            processed_at TIMESTAMP NULL,
            INDEX idx_status_priority (status, priority, created_at),
            INDEX idx_created_at (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

        @self::$conn->query($sql);
    }

    /**
     * Queue an email for sending
     * For production on Render: sends immediately (no background workers)
     * For better performance with background workers: use actual queue
     *
     * @param string $toEmail
     * @param string $subject
     * @param string $bodyHtml
     * @param string|null $toName
     * @param string|null $bodyText
     * @param int $priority (1=highest, 10=lowest)
     * @return bool
     */
    public static function queue($toEmail, $subject, $bodyHtml, $toName = null, $bodyText = null, $priority = 5) {
        // On Render/production without background workers, send immediately
        // This is more reliable than trying to queue + process in same request
        $isProduction = getenv('APP_ENV') === 'production' || 
                        (defined('ENVIRONMENT') && ENVIRONMENT === 'production');
        
        // For high-priority emails (priority <= 2) or production, send immediately
        if ($priority <= 2 || $isProduction) {
            error_log("Sending email immediately to: $toEmail (priority: $priority, production: " . ($isProduction ? 'yes' : 'no') . ")");
            return self::sendImmediately($toEmail, $subject, $bodyHtml, $toName);
        }

        // For local development with database, try to queue
        if (!self::$conn) {
            self::init();
        }

        if (!self::$conn) {
            // Fallback: send immediately if no DB connection
            return self::sendImmediately($toEmail, $subject, $bodyHtml, $toName);
        }

        try {
            $stmt = self::$conn->prepare(
                "INSERT INTO email_queue (to_email, to_name, subject, body_html, body_text, priority)
                 VALUES (?, ?, ?, ?, ?, ?)"
            );
            $stmt->bind_param('sssssi', $toEmail, $toName, $subject, $bodyHtml, $bodyText, $priority);
            $result = $stmt->execute();
            $stmt->close();

            // Process queue in same request for now
            self::processQueue(1);

            return $result;
        } catch (Exception $e) {
            error_log("EmailQueue::queue error: " . $e->getMessage());
            // Fallback: send immediately
            return self::sendImmediately($toEmail, $subject, $bodyHtml, $toName);
        }
    }

    /**
     * Send email immediately (fallback or for high-priority emails)
     */
    public static function sendImmediately($toEmail, $subject, $bodyHtml, $toName = null) {
        require_once __DIR__ . '/mail_config.php';
        require_once __DIR__ . '/email_template.php';

        try {
            $mail = new PHPMailer\PHPMailer\PHPMailer(true);
            configureMailerSMTP($mail);

            // Optimize SMTP settings for speed
            $mail->Timeout = 15; // 15 second timeout
            $mail->SMTPKeepAlive = false; // Close after sending
            $mail->SMTPDebug = 0; // No debug output

            if ($toName) {
                $mail->addAddress($toEmail, $toName);
            } else {
                $mail->addAddress($toEmail);
            }

            $mail->isHTML(true);
            $mail->Subject = $subject;
            applyEmailTemplate($mail, $bodyHtml);

            $result = $mail->send();
            error_log("Email sent successfully to: $toEmail");
            return $result;
        } catch (Exception $e) {
            error_log("EmailQueue::sendImmediately error to $toEmail: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Process pending emails in the queue
     * Call this from a cron job or background process
     *
     * @param int $limit Maximum emails to process
     * @return array Results
     */
    public static function processQueue($limit = 10) {
        if (!self::$conn) {
            self::init();
        }

        if (!self::$conn) {
            return ['error' => 'No database connection'];
        }

        $results = ['sent' => 0, 'failed' => 0, 'errors' => []];

        // Get pending emails, ordered by priority and creation time
        $stmt = self::$conn->prepare(
            "SELECT id, to_email, to_name, subject, body_html, body_text, attempts, max_attempts
             FROM email_queue
             WHERE status = 'pending' AND attempts < max_attempts
             ORDER BY priority ASC, created_at ASC
             LIMIT ?"
        );
        $stmt->bind_param('i', $limit);
        $stmt->execute();
        $result = $stmt->get_result();
        $emails = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        require_once __DIR__ . '/mail_config.php';
        require_once __DIR__ . '/email_template.php';

        // Create single mailer instance for connection reuse
        $mail = new PHPMailer\PHPMailer\PHPMailer(true);
        configureMailerSMTP($mail);
        $mail->SMTPKeepAlive = true;

        foreach ($emails as $email) {
            // Mark as processing
            self::updateStatus($email['id'], 'processing');

            try {
                $mail->clearAddresses();
                $mail->clearAllRecipients();

                if ($email['to_name']) {
                    $mail->addAddress($email['to_email'], $email['to_name']);
                } else {
                    $mail->addAddress($email['to_email']);
                }

                $mail->isHTML(true);
                $mail->Subject = $email['subject'];
                applyEmailTemplate($mail, $email['body_html']);

                if ($mail->send()) {
                    self::updateStatus($email['id'], 'sent');
                    $results['sent']++;
                } else {
                    throw new Exception($mail->ErrorInfo);
                }
            } catch (Exception $e) {
                $attempts = $email['attempts'] + 1;
                $status = $attempts >= $email['max_attempts'] ? 'failed' : 'pending';
                self::updateStatus($email['id'], $status, $e->getMessage(), $attempts);
                $results['failed']++;
                $results['errors'][] = $e->getMessage();
            }
        }

        $mail->smtpClose();

        return $results;
    }

    /**
     * Update email status in queue
     */
    private static function updateStatus($id, $status, $errorMessage = null, $attempts = null) {
        if (!self::$conn) return;

        if ($attempts !== null) {
            $stmt = self::$conn->prepare(
                "UPDATE email_queue SET status = ?, error_message = ?, attempts = ?,
                 processed_at = CASE WHEN ? IN ('sent', 'failed') THEN NOW() ELSE processed_at END
                 WHERE id = ?"
            );
            $stmt->bind_param('ssisi', $status, $errorMessage, $attempts, $status, $id);
        } else {
            $stmt = self::$conn->prepare(
                "UPDATE email_queue SET status = ?,
                 processed_at = CASE WHEN ? IN ('sent', 'failed') THEN NOW() ELSE processed_at END
                 WHERE id = ?"
            );
            $stmt->bind_param('ssi', $status, $status, $id);
        }
        $stmt->execute();
        $stmt->close();
    }

    /**
     * Trigger background email processing
     * Uses non-blocking approach
     */
    private static function triggerBackgroundProcess() {
        // For Render/production: Process immediately but in optimized way
        // This runs in the same request but is faster due to connection reuse
        if (getenv('APP_ENV') === 'production' || defined('ENVIRONMENT') && ENVIRONMENT === 'production') {
            // Process up to 5 emails quickly
            register_shutdown_function(function() {
                EmailQueue::processQueue(5);
            });
        }
    }

    /**
     * Clean up old processed emails
     * @param int $daysOld Delete emails older than this many days
     */
    public static function cleanup($daysOld = 30) {
        if (!self::$conn) return;

        $stmt = self::$conn->prepare(
            "DELETE FROM email_queue
             WHERE status IN ('sent', 'failed')
             AND processed_at < DATE_SUB(NOW(), INTERVAL ? DAY)"
        );
        $stmt->bind_param('i', $daysOld);
        $stmt->execute();
        $deleted = $stmt->affected_rows;
        $stmt->close();

        return $deleted;
    }
}
