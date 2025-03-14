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
        WHERE o.status = 'PENDING'
        GROUP BY o.order_id
        ORDER BY o.created_at ASC
    ");
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $output = '';
    foreach ($orders as $order) {
        $output .= '
        <tr>
            <td>' . $order['order_id'] . '</td>
            <td>' . $order['table_number'] . '</td>
            <td><pre class="mb-0">' . htmlspecialchars($order['items']) . '</pre></td>
            <td>₱' . number_format($order['total_amount'], 2) . '</td>
            <td>
                <button class="btn btn-primary btn-sm" onclick="showPaymentModal(' . $order['order_id'] . ', ' . $order['total_amount'] . ')">
                    Process Payment
                </button>
            </td>
        </tr>';
    }

    echo $output;

} catch (PDOException $e) {
    http_response_code(500);
    echo "Error: " . $e->getMessage();
}
?> 