<?php
require_once '../config/database.php';

try {
    $stmt = $conn->query("
        SELECT o.*, t.table_number
        FROM orders o
        JOIN tables t ON o.table_id = t.table_id
        WHERE o.status = 'PAID'
        ORDER BY o.created_at DESC
        LIMIT 10
    ");
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $output = '';
    foreach ($orders as $order) {
        $badge_class = $order['payment_method'] === 'CASH' ? 'bg-success' : 'bg-primary';
        $output .= '
        <div class="list-group-item">
            <div class="d-flex w-100 justify-content-between">
                <h6 class="mb-1">Order #' . $order['order_id'] . ' - Table ' . $order['table_number'] . '</h6>
                <span class="badge ' . $badge_class . '">' . $order['payment_method'] . '</span>
            </div>
            <p class="mb-1">₱' . number_format($order['total_amount'], 2) . '</p>
            <small class="text-muted">' . date('M d, Y H:i', strtotime($order['created_at'])) . '</small>
        </div>';
    }

    echo $output;

} catch (PDOException $e) {
    http_response_code(500);
    echo "Error: " . $e->getMessage();
}
?> 