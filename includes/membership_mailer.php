<?php
require_once __DIR__ . '/../vendor/autoload.php';
include_once __DIR__ . '/env_loader.php';
loadEnv(__DIR__ . '/env');

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/**
 * Send membership application acknowledgement email (status: pending)
 * @param string $email
 * @param string $name
 * @param string $plan
 * @param string $status
 * @return bool
 */
function sendMembershipApplicationEmail($email, $name, $plan, $status = 'pending') {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = getenv('EMAIL_HOST');
        $mail->SMTPAuth = true;
        $mail->Username = getenv('EMAIL_USER');
        $mail->Password = getenv('EMAIL_PASS');
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = getenv('EMAIL_PORT');

    $mail->setFrom(getenv('EMAIL_USER'), getenv('EMAIL_FROM_NAME') ?: 'FitXBrawl');
        $mail->addAddress($email, $name);

        $mail->isHTML(true);
    $mail->Subject = "Membership application received";

        $body = "<p>Hi " . htmlspecialchars($name) . ",</p>";
        $body .= "<p>Thank you — we have received your membership application for <strong>" . htmlspecialchars($plan) . "</strong>.</p>";
        $body .= "<p>Your application status is: <strong>" . htmlspecialchars(ucfirst($status)) . "</strong>. Please note that we will review your payment and supporting documents. An administrator will verify your payment and update your membership.</p>";
        $body .= "<p>Once the admin approves or rejects your application you will receive another email with the result and next steps.</p>";
    $body .= "<p>Thank you for choosing FitXBrawl.</p>";
    $body .= "<p>— FitXBrawl Team</p>";

        $mail->Body = $body;

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log('sendMembershipApplicationEmail failed: ' . $e->getMessage());
        return false;
    }
}


/**
 * Send membership decision email (approved or rejected)
 * @param string $email
 * @param string $name
 * @param string $plan
 * @param bool $accepted
 * @param string|null $start_date
 * @param string|null $end_date
 * @param string|null $remarks
 * @param array $perks
 * @return bool
 */
function sendMembershipDecisionEmail($email, $name, $plan, $accepted = false, $start_date = null, $end_date = null, $remarks = null, $perks = []) {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = getenv('EMAIL_HOST');
        $mail->SMTPAuth = true;
        $mail->Username = getenv('EMAIL_USER');
        $mail->Password = getenv('EMAIL_PASS');
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = getenv('EMAIL_PORT');

    $mail->setFrom(getenv('EMAIL_USER'), getenv('EMAIL_FROM_NAME') ?: 'FitXBrawl');
        $mail->addAddress($email, $name);

        $mail->isHTML(true);

        if ($accepted) {
            $mail->Subject = "Membership approved";
            $body = "<p>Hi " . htmlspecialchars($name) . ",</p>";
            $body .= "<p>Congratulations! Your membership application for <strong>" . htmlspecialchars($plan) . "</strong> has been <strong>accepted</strong>.</p>";
            if ($start_date || $end_date) {
                $body .= "<p>Your membership period: ";
                if ($start_date) $body .= "<strong>Start:</strong> " . htmlspecialchars($start_date) . " ";
                if ($end_date) $body .= "<strong>End:</strong> " . htmlspecialchars($end_date);
                $body .= "</p>";
            }
            if (!empty($perks)) {
                $body .= "<p>Your membership perks include:</p><ul>";
                foreach ($perks as $p) {
                    $body .= "<li>" . htmlspecialchars($p) . "</li>";
                }
                $body .= "</ul>";
            }
            $body .= "<p>We're excited to have you on board. Please visit your account to view membership benefits and start booking sessions.</p>";
            $body .= "<p>— FitXBrawl Team</p>";
        } else {
            $mail->Subject = "Membership application update";
            $body = "<p>Hi " . htmlspecialchars($name) . ",</p>";
            $body .= "<p>We reviewed your membership application for <strong>" . htmlspecialchars($plan) . "</strong>.</p>";
            $body .= "<p>Status: <strong>Rejected</strong>.</p>";
            if ($remarks) {
                $body .= "<p>Remarks from admin: " . nl2br(htmlspecialchars($remarks)) . "</p>";
            }
            $body .= "<p>If you have questions or want to resubmit with corrected information, please contact us.</p>";
            $body .= "<p>— FitXBrawl Team</p>";
        }

        $mail->Body = $body;

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log('sendMembershipDecisionEmail failed: ' . $e->getMessage());
        return false;
    }
}

?>
