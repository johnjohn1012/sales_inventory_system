<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

try {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['table_id']) || !isset($data['items']) || empty($data['items'])) {
        throw new Exception('Invalid order data');
    }

    $conn->beginTransaction();

    // Calculate total amount
    $total_amount = 0;
    $order_items = [];
    
    foreach ($data['items'] as $item) {
        $stmt = $conn->prepare("SELECT price FROM products WHERE product_id = ?");
        $stmt->execute([$item['product_id']]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$product) {
            throw new Exception('Product not found');
        }
        
        $subtotal = $product['price'] * $item['quantity'];
        $total_amount += $subtotal;
        
        // Store item details for later insertion
        $order_items[] = [
            'product_id' => $item['product_id'],
            'quantity' => $item['quantity'],
            'unit_price' => $product['price'],
            'subtotal' => $subtotal
        ];
    }

    // Create the order with PENDING status
    $stmt = $conn->prepare("
        INSERT INTO orders (table_id, total_amount, status, payment_status, order_type) 
        VALUES (?, ?, 'PENDING', 'PENDING', 'QR')
    ");
    $stmt->execute([$data['table_id'], $total_amount]);
    
    $order_id = $conn->lastInsertId();

    // Insert order items
    $stmt = $conn->prepare("
        INSERT INTO order_items (order_id, product_id, quantity, unit_price, subtotal) 
        VALUES (?, ?, ?, ?, ?)
    ");
    
    foreach ($order_items as $item) {
        $stmt->execute([
            $order_id,
            $item['product_id'],
            $item['quantity'],
            $item['unit_price'],
            $item['subtotal']
        ]);
    }

    // Create notification for cashier
    $stmt = $conn->prepare("
        INSERT INTO notifications (order_id, message, type, is_read) 
        VALUES (?, 'New order received from Table #" . $_SESSION['table_number'] . "', 'ORDER_READY', 0)
    ");
    $stmt->execute([$order_id]);

    $conn->commit();
    
    echo json_encode(['success' => true, 'message' => 'Order placed successfully']);

} catch (Exception $e) {
    if (isset($conn) && $conn->inTransaction()) {
        $conn->rollBack();
    }
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
} 