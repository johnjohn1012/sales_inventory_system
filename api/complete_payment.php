<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

// Debug: Log incoming data
error_log("Payment request data: " . print_r($_POST, true));

if (!isset($_POST['order_id']) || !isset($_POST['payment_method']) || !isset($_POST['amount_received'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required fields']);
    exit();
}

try {
    $conn->beginTransaction();

    // Get order details
    $stmt = $conn->prepare("SELECT total_amount, status FROM orders WHERE order_id = ?");
    $stmt->execute([$_POST['order_id']]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$order) {
        throw new Exception('Order not found');
    }

    // Debug: Log order details
    error_log("Order details: " . print_r($order, true));

    // Validate payment amount
    if (floatval($_POST['amount_received']) < floatval($order['total_amount'])) {
        throw new Exception('Insufficient payment amount');
    }

    // Update order status to PREPARING and payment status to PAID
    $stmt = $conn->prepare("
        UPDATE orders 
        SET status = 'PREPARING', 
            payment_status = 'PAID',
            payment_method = ?
        WHERE order_id = ?
    ");

    // Debug: Log SQL parameters
    error_log("Update parameters: method=" . $_POST['payment_method'] . ", order_id=" . $_POST['order_id']);
    
    $result = $stmt->execute([$_POST['payment_method'], $_POST['order_id']]);
    
    if (!$result) {
        throw new Exception('Failed to update order status: ' . implode(', ', $stmt->errorInfo()));
    }

    // Create notification for kitchen
    $stmt = $conn->prepare("
        INSERT INTO notifications (
            order_id, 
            message, 
            type
        ) VALUES (?, ?, ?)
    ");

    $result = $stmt->execute([
        $_POST['order_id'],
        'New paid order ready for preparation',
        'ORDER_PAID'
    ]);

    if (!$result) {
        throw new Exception('Failed to create notification: ' . implode(', ', $stmt->errorInfo()));
    }

    $conn->commit();
    echo json_encode(['success' => true, 'message' => 'Payment processed successfully']);

} catch (Exception $e) {
    error_log("Payment processing error: " . $e->getMessage());
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}