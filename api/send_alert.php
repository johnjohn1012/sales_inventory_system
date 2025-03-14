<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in and has cashier role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'CASHIER') {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized access']);
    exit();
}

try {
    // Insert a new notification with type SOUND_ALERT
    $stmt = $conn->prepare("
        INSERT INTO notifications (message, type, is_read, created_at)
        VALUES ('New order alert from cashier', 'SOUND_ALERT', 0, NOW())
    ");
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Alert sent successfully']);
    } else {
        throw new Exception('Failed to send alert');
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?> 