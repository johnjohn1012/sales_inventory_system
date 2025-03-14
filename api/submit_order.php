<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

try {
    // Get POST data
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!$data) {
        throw new Exception('Invalid request data');
    }

    $conn->beginTransaction();

    // Create new customer record
    $stmt = $conn->prepare("
        INSERT INTO customers (first_name, last_name, email, contact_number)
        VALUES (?, ?, ?, ?)
    ");
    $stmt->execute([
        $data['first_name'],
        $data['last_name'],
        $data['email'] ?: null,
        $data['contact_number'] ?: null
    ]);
    $new_customer_id = $conn->lastInsertId();

    // Create order
    $stmt = $conn->prepare("
        INSERT INTO orders (table_id, customer_id, order_type, status, total_amount)
        VALUES (?, ?, 'QR', 'PENDING', ?)
    ");
    $stmt->execute([
        $data['table_id'],
        $new_customer_id,
        $data['total_amount']
    ]);
    $order_id = $conn->lastInsertId();

    // Insert order items
    $stmt = $conn->prepare("
        INSERT INTO order_items (order_id, product_id, quantity, unit_price, subtotal)
        VALUES (?, ?, ?, ?, ?)
    ");

    foreach ($data['items'] as $item) {
        $stmt->execute([
            $order_id,
            $item['product_id'],
            $item['quantity'],
            $item['price'],
            $item['quantity'] * $item['price']
        ]);
        $order_item_id = $conn->lastInsertId();

        // Handle modifications if any
        if (!empty($item['modifications'])) {
            $mod_stmt = $conn->prepare("
                INSERT INTO order_modifications (order_item_id, description)
                VALUES (?, ?)
            ");
            foreach ($item['modifications'] as $mod) {
                $mod_stmt->execute([$order_item_id, $mod]);
            }
        }
    }

    // Create notification for kitchen
    $stmt = $conn->prepare("
        INSERT INTO notifications (order_id, message, type)
        VALUES (?, 'New order received', 'ORDER_READY')
    ");
    $stmt->execute([$order_id]);

    // Update table status
    $stmt = $conn->prepare("
        UPDATE tables SET status = 'OCCUPIED'
        WHERE table_id = ?
    ");
    $stmt->execute([$data['table_id']]);

    $conn->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Order placed successfully',
        'order_id' => $order_id
    ]);

} catch (Exception $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?> 