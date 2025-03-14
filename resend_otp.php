<?php
session_start();
require_once 'config/database.php';
require_once 'config/email.php';

if (!isset($_SESSION['temp_user_id']) || !isset($_SESSION['temp_username'])) {
    header('Location: login.php');
    exit();
}

try {
    // Get user's email from employees table
    $stmt = $conn->prepare("
        SELECT e.email 
        FROM employees e 
        JOIN users u ON e.employee_id = u.employee_id 
        WHERE u.user_id = ?
    ");
    $stmt->execute([$_SESSION['temp_user_id']]);
    $employee = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$employee || !$employee['email']) {
        throw new Exception("No email found for this user");
    }

    // Generate new OTP
    $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    $expires_at = date('Y-m-d H:i:s', strtotime('+5 minutes'));

    // Save OTP to database
    $stmt = $conn->prepare("
        INSERT INTO two_factor_auth_codes (user_id, code, expires_at)
        VALUES (?, ?, ?)
    ");
    $stmt->execute([$_SESSION['temp_user_id'], $otp, $expires_at]);

    // Send OTP via email
    if (sendOTPEmail($employee['email'], $otp)) {
        header('Location: verify_otp.php?message=OTP sent successfully');
    } else {
        throw new Exception("Failed to send OTP email");
    }
} catch (Exception $e) {
    header('Location: verify_otp.php?error=' . urlencode($e->getMessage()));
}
exit(); 