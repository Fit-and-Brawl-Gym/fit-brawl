<?php
/**
 * Email Service - Uses HTTP API instead of SMTP
 * Works on Render and other platforms that block SMTP ports
 *
 * Supports: Resend, SendGrid, Mailgun (via HTTP API)
 */

class EmailService {
    private static $provider = null;

    /**
     * Get environment variable (checks multiple sources)
     */
    private static function getEnvVar($key) {
        // Try $_SERVER first (Render uses this)
        if (isset($_SERVER[$key]) && $_SERVER[$key] !== '') {
            return $_SERVER[$key];
        }
        // Try $_ENV
        if (isset($_ENV[$key]) && $_ENV[$key] !== '') {
            return $_ENV[$key];
        }
        // Try getenv() as fallback
        return getenv($key);
    }

    /**
     * Send email using configured provider
     */
    public static function send($to, $subject, $htmlBody, $toName = null) {
        // Debug: Check environment variables from multiple sources
        $resendKey = self::getEnvVar('RESEND_API_KEY');
        $sendgridKey = self::getEnvVar('SENDGRID_API_KEY');
        
        error_log("EmailService: Checking providers - Resend: " . ($resendKey ? 'yes' : 'no') . ", SendGrid: " . ($sendgridKey ? 'yes' : 'no'));
        
        // Try SendGrid first if both are available (SendGrid has no domain requirement)
        // If only one is set, use that one
        if ($sendgridKey) {
            error_log("EmailService: Using SendGrid provider");
            return self::sendViaSendGrid($to, $subject, $htmlBody, $toName);
        }

        // Try Resend as fallback
        if ($resendKey) {
            error_log("EmailService: Using Resend provider");
            return self::sendViaResend($to, $subject, $htmlBody, $toName);
        }

        // Fallback to SMTP (won't work on Render free tier)
        error_log("EmailService: Falling back to SMTP (likely to fail on Render)");
        return self::sendViaSMTP($to, $subject, $htmlBody, $toName);
    }

    /**
     * Send via Resend API (HTTPS - works on Render!)
     * Free tier: 3,000 emails/month
     * https://resend.com
     */
    private static function sendViaResend($to, $subject, $htmlBody, $toName = null) {
        $apiKey = self::getEnvVar('RESEND_API_KEY');
        
        // For Resend free tier, must use onboarding@resend.dev or verified domain
        $fromEmail = self::getEnvVar('EMAIL_FROM') ?: 'onboarding@resend.dev';
        $fromName = self::getEnvVar('EMAIL_FROM_NAME') ?: 'Fit & Brawl Gym';

        $data = [
            'from' => "$fromName <$fromEmail>",
            'to' => [$toName ? "$toName <$to>" : $to],
            'subject' => $subject,
            'html' => $htmlBody,
        ];
        
        error_log("Resend: Sending email to $to from $fromEmail");

        $ch = curl_init('https://api.resend.com/emails');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $apiKey,
                'Content-Type: application/json',
            ],
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            error_log("Resend curl error: $error");
            return false;
        }

        $result = json_decode($response, true);

        if ($httpCode >= 200 && $httpCode < 300) {
            error_log("Email sent via Resend to: $to (ID: " . ($result['id'] ?? 'unknown') . ")");
            return true;
        } else {
            error_log("Resend API error ($httpCode): " . ($result['message'] ?? $response));
            return false;
        }
    }

    /**
     * Send via SendGrid API
     * Free tier: 100 emails/day
     */
    private static function sendViaSendGrid($to, $subject, $htmlBody, $toName = null) {
        $apiKey = self::getEnvVar('SENDGRID_API_KEY');
        $fromEmail = self::getEnvVar('EMAIL_FROM') ?: self::getEnvVar('EMAIL_USER') ?: 'noreply@fitxbrawl.com';
        $fromName = self::getEnvVar('EMAIL_FROM_NAME') ?: 'Fit & Brawl Gym';
        
        error_log("SendGrid: Sending email to $to from $fromEmail");

        $data = [
            'personalizations' => [[
                'to' => [['email' => $to, 'name' => $toName ?? '']],
                'subject' => $subject,
            ]],
            'from' => ['email' => $fromEmail, 'name' => $fromName],
            'content' => [['type' => 'text/html', 'value' => $htmlBody]],
        ];

        $ch = curl_init('https://api.sendgrid.com/v3/mail/send');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $apiKey,
                'Content-Type: application/json',
            ],
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_TIMEOUT => 30,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($httpCode >= 200 && $httpCode < 300) {
            error_log("Email sent via SendGrid to: $to");
            return true;
        } else {
            error_log("SendGrid API error ($httpCode): $response");
            return false;
        }
    }

    /**
     * Fallback: Send via SMTP (PHPMailer)
     * Note: Won't work on Render free tier due to blocked ports
     */
    private static function sendViaSMTP($to, $subject, $htmlBody, $toName = null) {
        require_once __DIR__ . '/mail_config.php';
        require_once __DIR__ . '/email_template.php';

        try {
            $mail = new PHPMailer\PHPMailer\PHPMailer(true);
            configureMailerSMTP($mail);

            if ($toName) {
                $mail->addAddress($to, $toName);
            } else {
                $mail->addAddress($to);
            }

            $mail->isHTML(true);
            $mail->Subject = $subject;
            applyEmailTemplate($mail, $htmlBody);

            $result = $mail->send();
            error_log("Email sent via SMTP to: $to");
            return $result;
        } catch (Exception $e) {
            error_log("SMTP email error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get the current email provider being used
     */
    public static function getProvider() {
        // Prioritize SendGrid (no domain requirement)
        if (self::getEnvVar('SENDGRID_API_KEY')) return 'SendGrid';
        if (self::getEnvVar('RESEND_API_KEY')) return 'Resend';
        return 'SMTP';
    }
}
