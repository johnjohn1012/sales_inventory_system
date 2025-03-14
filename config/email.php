<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../PHPMailer-master/src/Exception.php';
require_once __DIR__ . '/../PHPMailer-master/src/PHPMailer.php';
require_once __DIR__ . '/../PHPMailer-master/src/SMTP.php';

function sendOTPEmail($to, $otp) {
    $mail = new PHPMailer(true);

    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'korosomag030@gmail.com';
        $mail->Password = 'ubxs sgny imdd bajq';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        // Recipients
        $mail->setFrom('korosomag030@gmail.com', 'HarahQR Sales');
        $mail->addAddress($to);

        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Your Login OTP Code';
        $mail->Body = "
            <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
                <h2 style='color: #4e73df;'>HarahQR Sales Login Verification</h2>
                <p>Your OTP code is: <strong style='font-size: 24px; color: #4e73df;'>{$otp}</strong></p>
                <p>This code will expire in 5 minutes.</p>
                <p>If you didn't request this code, please ignore this email.</p>
            </div>
        ";

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Email sending failed: {$mail->ErrorInfo}");
        return false;
    }
} 