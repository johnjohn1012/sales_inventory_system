<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

// Validate input
if (!isset($_POST['order_id']) || !isset($_POST['status'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required fields']);
    exit();
}

try {
    $conn->beginTransaction();

    // Update order status
    $stmt = $conn->prepare("
        UPDATE orders 
        SET status = ?,
            updated_at = CURRENT_TIMESTAMP
        WHERE order_id = ?
    ");
    
    $result = $stmt->execute([$_POST['status'], $_POST['order_id']]);
    
    if (!$result) {
        throw new Exception('Failed to update order status');
    }

    // Get table number for the order
    $stmt = $conn->prepare("
        SELECT t.table_number 
        FROM orders o 
        LEFT JOIN tables t ON o.table_id = t.table_id 
        WHERE o.order_id = ?
    ");
    $stmt->execute([$_POST['order_id']]);
    $table_info = $stmt->fetch(PDO::FETCH_ASSOC);
    $table_number = $table_info ? $table_info['table_number'] : 'Unknown';

    // Create notification based on status
    $message = '';
    $type = '';
    
    if ($_POST['status'] === 'PREPARING') {
        $message = 'Table ' . $table_number . ' - Order #' . $_POST['order_id'] . ' is being prepared';
        $type = 'ORDER_PREPARING';
    } else if ($_POST['status'] === 'COMPLETED') {
        $message = 'Table ' . $table_number . ' - Order #' . $_POST['order_id'] . ' is ready for service';
        $type = 'ORDER_READY';

        // Insert notification for the waiter
        $stmt = $conn->prepare("INSERT INTO notifications (order_id, message, type, is_read) VALUES (?, ?, 'ORDER_READY', 0)");
        $result = $stmt->execute([$_POST['order_id'], $message]);

        if (!$result) {
            throw new Exception('Failed to create notification for waiter');
        }
    }

    if ($message && $type) {
        $stmt = $conn->prepare("
            INSERT INTO notifications (
                order_id,
                message,
                type,
                is_read
            ) VALUES (?, ?, ?, 0)
        ");
        
        $result = $stmt->execute([
            $_POST['order_id'],
            $message,
            $type
        ]);

        if (!$result) {
            throw new Exception('Failed to create notification');
        }
    }

    $conn->commit();
    echo json_encode(['success' => true, 'message' => 'Order status updated successfully']);

} catch (Exception $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
} 