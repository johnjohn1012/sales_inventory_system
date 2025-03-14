<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized access']);
    exit();
}

try {
    $role = $_SESSION['role'];
    $query = "SELECT n.*, o.table_id, t.table_number 
              FROM notifications n
              LEFT JOIN orders o ON n.order_id = o.order_id
              LEFT JOIN tables t ON o.table_id = t.table_id
              WHERE n.is_read = 0";
    
    // Filter notifications based on user role
    switch ($role) {
        case 'KITCHEN':
            $query .= " AND n.type = 'ORDER_PREPARING'";
            break;
        case 'WAITER':
            $query .= " AND n.type = 'ORDER_READY'";
            break;
        case 'CASHIER':
            $query .= " AND n.type IN ('PAYMENT', 'ORDER_PAID')";
            break;
        case 'ADMIN':
            // Admin can see all notifications
            break;
        default:
            throw new Exception('Invalid user role');
    }
    
    $query .= " ORDER BY n.created_at DESC";
    
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Format messages to include table number
    foreach ($notifications as &$notification) {
        if (isset($notification['table_number'])) {
            // If message doesn't already contain table number, add it
            if (strpos($notification['message'], 'Table') === false) {
                $notification['message'] = str_replace(
                    'Order #',
                    'Table ' . $notification['table_number'] . ' - Order #',
                    $notification['message']
                );
            }
        }
    }

    echo json_encode(['notifications' => $notifications]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
} 