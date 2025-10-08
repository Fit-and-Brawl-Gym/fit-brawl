<?php
require_once __DIR__ . '/../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function sendOTPEmail($email, $otp) {
    $mail = new PHPMailer(true);
    
    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'fitxbrawl.gym@gmail.com';
        $mail->Password = 'upqm ussn guky lfnj';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        // Recipients
        $mail->setFrom('fitxbrawl.gym@gmail.com', 'Fit X Brawl'); // Use same email as Username
        $mail->addAddress($email);

        // Content
        $mail->isHTML(true);
        $mail->Subject = 'fitxbrawl.gym@gmail.com';
        $mail->Body = "Your OTP for password reset is: <b>$otp</b><br>This OTP will expire in 5 minutes.";

        $mail->send();
        return true;
    } catch (Exception $e) {
        return false;
    }
}