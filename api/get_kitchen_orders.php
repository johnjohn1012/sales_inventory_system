<?php
require_once '../config/database.php';

try {
    // Get new orders
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
        WHERE o.status = 'PAID'
        GROUP BY o.order_id
        ORDER BY o.created_at ASC
    ");
    $new_orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get preparing orders
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
        WHERE o.status = 'PREPARING'
        GROUP BY o.order_id
        ORDER BY o.created_at ASC
    ");
    $preparing_orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $new_orders_html = '';
    foreach ($new_orders as $order) {
        $new_orders_html .= '
        <div class="card order-card mb-3">
            <div class="card-body">
                <h5 class="card-title">Order #' . $order['order_id'] . ' - Table ' . $order['table_number'] . '</h5>
                <pre class="card-text">' . htmlspecialchars($order['items']) . '</pre>
                <button class="btn btn-primary" onclick="updateOrderStatus(' . $order['order_id'] . ', \'PREPARING\')">
                    Start Preparing
                </button>
            </div>
        </div>';
    }

    $preparing_orders_html = '';
    foreach ($preparing_orders as $order) {
        $preparing_orders_html .= '
        <div class="card order-card mb-3 preparing">
            <div class="card-body">
                <h5 class="card-title">Order #' . $order['order_id'] . ' - Table ' . $order['table_number'] . '</h5>
                <pre class="card-text">' . htmlspecialchars($order['items']) . '</pre>
                <button class="btn btn-success" onclick="updateOrderStatus(' . $order['order_id'] . ', \'COMPLETED\')">
                    Mark as Ready
                </button>
            </div>
        </div>';
    }

    echo json_encode([
        'new_orders' => $new_orders_html,
        'preparing_orders' => $preparing_orders_html
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?> 