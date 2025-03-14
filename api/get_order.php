<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

if (!isset($_GET['order_id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Order ID is required']);
    exit();
}

try {
    $stmt = $conn->prepare("
        SELECT 
            o.*,
            t.table_number,
            IFNULL(
                CONCAT(
                    '[',
                    GROUP_CONCAT(
                        JSON_OBJECT(
                            'product_id', oi.product_id,
                            'name', p.name,
                            'quantity', oi.quantity,
                            'unit_price', oi.unit_price
                        )
                    ),
                    ']'
                ),
                '[]'
            ) as items
        FROM orders o 
        LEFT JOIN tables t ON o.table_id = t.table_id 
        LEFT JOIN order_items oi ON o.order_id = oi.order_id
        LEFT JOIN products p ON oi.product_id = p.product_id
        WHERE o.order_id = ?
        GROUP BY o.order_id, o.status, o.created_at, o.total_amount, t.table_number
    ");
    
    $stmt->execute([$_GET['order_id']]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$order) {
        http_response_code(404);
        echo json_encode(['error' => 'Order not found']);
        exit();
    }

    echo json_encode($order);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
} 