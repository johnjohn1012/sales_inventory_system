<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in and is a cashier
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'CASHIER') {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized access']);
    exit();
}

// Check if order_id is provided
if (!isset($_GET['order_id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Order ID is required']);
    exit();
}

$order_id = $_GET['order_id'];

try {
    // Get order details
    $stmt = $conn->prepare("
        SELECT o.*, t.table_number, c.first_name, c.last_name, c.contact_number,
               pt.total_amount, pt.amount_paid, pt.change_amount, pt.payment_method, pt.created_at as payment_date
        FROM orders o
        LEFT JOIN tables t ON o.table_id = t.table_id
        LEFT JOIN customers c ON o.customer_id = c.customer_id
        LEFT JOIN payment_transactions pt ON o.order_id = pt.order_id
        WHERE o.order_id = ?
    ");
    $stmt->execute([$order_id]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$order) {
        throw new Exception('Order not found');
    }

    // Get order items
    $stmt = $conn->prepare("
        SELECT oi.*, p.name as product_name, p.description, c.name as category_name
        FROM order_items oi
        JOIN products p ON oi.product_id = p.product_id
        JOIN categories c ON p.category_id = c.category_id
        WHERE oi.order_id = ?
    ");
    $stmt->execute([$order_id]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Calculate total
    $total = array_sum(array_column($items, 'subtotal'));

    // Start building the HTML
    $html = '
    <div class="order-details">
        <div class="row mb-4">
            <div class="col-md-6">
                <h6 class="text-muted mb-2">Order Information</h6>
                <p class="mb-1"><strong>Order ID:</strong> #' . $order['order_id'] . '</p>
                <p class="mb-1"><strong>Order Type:</strong> ' . $order['order_type'] . '</p>
                <p class="mb-1"><strong>Status:</strong> ' . $order['status'] . '</p>
                <p class="mb-1"><strong>Created At:</strong> ' . date('M d, Y h:i A', strtotime($order['created_at'])) . '</p>
            </div>
            <div class="col-md-6">
                <h6 class="text-muted mb-2">Customer Information</h6>
                <p class="mb-1"><strong>Name:</strong> ' . ($order['customer_id'] ? htmlspecialchars($order['first_name'] . ' ' . $order['last_name']) : 'N/A') . '</p>
                <p class="mb-1"><strong>Contact:</strong> ' . ($order['contact_number'] ? htmlspecialchars($order['contact_number']) : 'N/A') . '</p>
                <p class="mb-1"><strong>Table:</strong> ' . ($order['order_type'] === 'QR' ? ($order['table_number'] ? 'Table ' . $order['table_number'] : 'N/A') : 'Walk-in') . '</p>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Item</th>
                        <th>Category</th>
                        <th>Quantity</th>
                        <th>Price</th>
                        <th>Subtotal</th>
                    </tr>
                </thead>
                <tbody>';

    foreach ($items as $item) {
        $html .= '
        <tr>
            <td>
                <strong>' . htmlspecialchars($item['product_name']) . '</strong><br>
                <small class="text-muted">' . htmlspecialchars($item['description']) . '</small>
            </td>
            <td>' . htmlspecialchars($item['category_name']) . '</td>
            <td class="text-center">' . $item['quantity'] . '</td>
            <td class="text-end">₱' . number_format($item['unit_price'], 2) . '</td>
            <td class="text-end">₱' . number_format($item['subtotal'], 2) . '</td>
        </tr>';
    }

    $html .= '
                </tbody>
                <tfoot>
                    <tr>
                        <th colspan="4" class="text-end">Total Amount:</th>
                        <th class="text-end">₱' . number_format($total, 2) . '</th>
                    </tr>
                </tfoot>
            </table>
        </div>';

    // After the order items table, add payment information section
    if ($order['status'] !== 'PENDING') {
        $html .= '<div class="card mt-4">
            <div class="card-header bg-light">
                <h6 class="mb-0"><i class="fas fa-money-bill-wave me-2"></i>Payment Information</h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <p class="mb-1"><strong>Payment Method:</strong> ' . $order['payment_method'] . '</p>
                        <p class="mb-1"><strong>Total Amount:</strong> ₱' . number_format($order['total_amount'], 2) . '</p>
                        <p class="mb-1"><strong>Amount Paid:</strong> ₱' . number_format($order['amount_paid'], 2) . '</p>
                        <p class="mb-1"><strong>Change Given:</strong> ₱' . number_format($order['change_amount'], 2) . '</p>
                    </div>
                    <div class="col-md-6">
                        <p class="mb-1"><strong>Payment Date:</strong> ' . date('M d, Y h:i A', strtotime($order['payment_date'])) . '</p>
                        <p class="mb-1"><strong>Payment Status:</strong> <span class="badge bg-success">Paid</span></p>
                    </div>
                </div>
            </div>
        </div>';
    }

    $html .= '</div>';

    echo json_encode(['html' => $html]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?> 