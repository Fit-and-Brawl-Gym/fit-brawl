<?php
include_once('../../../../includes/init.php');
header('Content-Type: application/json');
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require '../../vendor/autoload.php';

// Ensure email template is available to wrap replies
include_once __DIR__ . '/../../../../includes/email_template.php';

$action = $_GET['action'] ?? '';

if ($action === 'fetch') {
    $result = $conn->query("SELECT * FROM inquiries ORDER BY date_sent DESC");
    echo json_encode($result->fetch_all(MYSQLI_ASSOC));
    exit;
}

if ($action === 'mark_read') {
    $id = $_POST['id'] ?? 0;
    $conn->query("UPDATE inquiries SET status='Read' WHERE id=$id");
    echo json_encode(['success' => true]);
    exit;
}

if ($action === 'delete') {
    $id = $_POST['id'] ?? 0;
    $conn->query("DELETE FROM inquiries WHERE id=$id");
    echo json_encode(['success' => true]);
    exit;
}

if ($action === 'reply') {
    $email = $_POST['email'] ?? '';
    $subject = $_POST['subject'] ?? 'Response from Fit & Brawl';
    $message = $_POST['message'] ?? '';

    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = getenv('EMAIL_HOST');
        $mail->SMTPAuth = true;
        $mail->Username = getenv('EMAIL_USER');
        $mail->Password = getenv('EMAIL_PASS');
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        $mail->setFrom(getenv('EMAIL_USER'), 'Fit & Brawl');
        $mail->addAddress($email);
        $mail->isHTML(true);
    $mail->Subject = $subject;
    // sanitize and convert newlines for HTML, then apply template
    $bodyHtml = '<div>' . nl2br(htmlspecialchars($message)) . '</div>';
    applyEmailTemplate($mail, $bodyHtml);

        $mail->send();
        echo json_encode(['success' => true, 'msg' => 'Reply sent successfully!']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'msg' => "Mailer Error: {$mail->ErrorInfo}"]);
    }
    exit;
}

echo json_encode(['error' => 'Invalid action']);
?>