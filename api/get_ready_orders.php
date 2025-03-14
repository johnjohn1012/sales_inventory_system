<?php
require_once '../config/database.php';

try {
    $stmt = $conn->query("
        SELECT o.*, t.table_number,
        GROUP_CONCAT(
            CONCAT(oi.quantity, 'x ', p.name) 
            SEPARATOR '\n'
        ) as items
        FROM orders o
        JOIN tables t ON o.table_id = t.table_id
        JOIN order_items oi ON o.order_id = oi.order_id
        JOIN products p ON oi.product_id = p.product_id
        WHERE o.status = 'COMPLETED'
        GROUP BY o.order_id
        ORDER BY o.created_at ASC
    ");
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $output = '';
    foreach ($orders as $order) {
        $output .= '
        <div class="list-group-item">
            <div class="d-flex w-100 justify-content-between">
                <h5 class="mb-1">Order #' . $order['order_id'] . ' - Table ' . $order['table_number'] . '</h5>
                <small>' . date('H:i', strtotime($order['created_at'])) . '</small>
            </div>
            <pre class="mb-1">' . htmlspecialchars($order['items']) . '</pre>
            <button class="btn btn-success btn-sm" onclick="markOrderDelivered(' . $order['order_id'] . ')">
                Mark as Delivered
            </button>
        </div>';
    }

    echo $output;

} catch (PDOException $e) {
    http_response_code(500);
    echo "Error: " . $e->getMessage();
}
?> 