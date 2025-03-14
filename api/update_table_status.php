<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in and has waiter role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'WAITER') {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized access']);
    exit();
}

// Check if required parameters are present
if (!isset($_POST['table_id']) || !isset($_POST['status'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required parameters']);
    exit();
}

$table_id = $_POST['table_id'];
$status = $_POST['status'];

// Validate status
$valid_statuses = ['AVAILABLE', 'OCCUPIED', 'READY', 'CLEANING'];
if (!in_array($status, $valid_statuses)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid status']);
    exit();
}

try {
    // Update table status
    $stmt = $conn->prepare("UPDATE tables SET status = ? WHERE table_id = ?");
    $stmt->execute([$status, $table_id]);

    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => true, 'message' => 'Table status updated successfully']);
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'Table not found']);
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?> 