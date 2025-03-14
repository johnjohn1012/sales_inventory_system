<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'ADMIN') {
    http_response_code(403);
    exit('Unauthorized');
}

try {
    // Get unread count
    $stmt = $conn->query("SELECT COUNT(*) FROM notifications WHERE is_read = 0");
    $unread_count = $stmt->fetchColumn();

    // Return JSON response
    header('Content-Type: application/json');
    echo json_encode([
        'unread_count' => $unread_count,
        'timestamp' => time()
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
} 