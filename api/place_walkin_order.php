<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in and has cashier role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'CASHIER') {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Unauthorized access']);
    exit();
}

// Get JSON data from request
$json = file_get_contents('php://input');
$data = json_decode($json, true);

// Validate required fields
if (!isset($data['table_id']) || !isset($data['items']) || empty($data['items'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Missing required fields']);
    exit();
}

try {
    // Start transaction
    $conn->beginTransaction();

    // Check if table exists and is available
    $tableStmt = $conn->prepare("SELECT * FROM tables WHERE table_id = ? AND status = 'AVAILABLE'");
    $tableStmt->execute([$data['table_id']]);
    $table = $tableStmt->fetch(PDO::FETCH_ASSOC);

    if (!$table) {
        throw new Exception('Selected table is not available');
    }

    // Calculate total amount
    $total_amount = 0;
    $orderItems = [];

    // Prepare statement for getting product details
    $productStmt = $conn->prepare("SELECT * FROM products WHERE product_id = ? AND is_available = 1 AND is_disabled = 0");

    foreach ($data['items'] as $item) {
        $productStmt->execute([$item['product_id']]);
        $product = $productStmt->fetch(PDO::FETCH_ASSOC);

        if (!$product) {
            throw new Exception('One or more products are not available');
        }

        $subtotal = $product['price'] * $item['quantity'];
        $total_amount += $subtotal;

        $orderItems[] = [
            'product_id' => $item['product_id'],
            'quantity' => $item['quantity'],
            'unit_price' => $product['price'],
            'subtotal' => $subtotal
        ];
    }

    // Create order
    $orderStmt = $conn->prepare("
        INSERT INTO orders (table_id, order_type, status, payment_status, total_amount)
        VALUES (?, 'WALK_IN', 'PENDING', 'PENDING', ?)
    ");
    $orderStmt->execute([$data['table_id'], $total_amount]);
    $orderId = $conn->lastInsertId();

    // Insert order items
    $itemStmt = $conn->prepare("
        INSERT INTO order_items (order_id, product_id, quantity, unit_price, subtotal)
        VALUES (?, ?, ?, ?, ?)
    ");

    foreach ($orderItems as $item) {
        $itemStmt->execute([
            $orderId,
            $item['product_id'],
            $item['quantity'],
            $item['unit_price'],
            $item['subtotal']
        ]);
    }

    // Update table status
    $updateTableStmt = $conn->prepare("UPDATE tables SET status = 'OCCUPIED' WHERE table_id = ?");
    $updateTableStmt->execute([$data['table_id']]);

    // Create notification
    $notificationStmt = $conn->prepare("
        INSERT INTO notifications (order_id, message, type)
        VALUES (?, ?, 'ORDER_READY')
    ");
    $notificationStmt->execute([
        $orderId,
        "New walk-in order #" . $orderId . " from Table " . $table['table_number']
    ]);

    // Commit transaction
    $conn->commit();

    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'order_id' => $orderId,
        'message' => 'Walk-in order created successfully'
    ]);

} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollBack();

    header('Content-Type: application/json');
    echo json_encode([
        'error' => $e->getMessage()
    ]);
} 