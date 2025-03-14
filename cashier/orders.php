<?php
session_start();
require_once '../config/database.php';
require_once '../includes/anti_resubmission.php';

// Check if user is logged in and is a cashier
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'CASHIER') {
    header('Location: ../login.php');
    exit();
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        try {
            switch ($_POST['action']) {
                case 'update_status':
                    $order_id = $_POST['order_id'];
                    $new_status = $_POST['new_status'];
                    
                    $stmt = $conn->prepare("
                        UPDATE orders 
                        SET status = ? 
                        WHERE order_id = ?
                    ");
                    $stmt->execute([$new_status, $order_id]);
                    
                    // Log the action
                    $stmt = $conn->prepare("
                        INSERT INTO system_logs (user_id, action, description, ip_address)
                        VALUES (?, 'UPDATE_ORDER_STATUS', 'Updated order #' . ? . ' status to ' . ?, ?)
                    ");
                    $stmt->execute([$_SESSION['user_id'], $order_id, $new_status, $_SERVER['REMOTE_ADDR']]);
                    
                    handleFormSubmission("Order status updated successfully!");
                    break;
                    
                case 'delete_order':
                    $order_id = $_POST['order_id'];
                    
                    // First delete related records
                    $conn->beginTransaction();
                    
                    // Delete order items
                    $stmt = $conn->prepare("DELETE FROM order_items WHERE order_id = ?");
                    $stmt->execute([$order_id]);
                    
                    // Finally delete the order
                    $stmt = $conn->prepare("DELETE FROM orders WHERE order_id = ?");
                    $stmt->execute([$order_id]);
                    
                    $conn->commit();
                    
                    // Log the action
                    $stmt = $conn->prepare("
                        INSERT INTO system_logs (user_id, action, description, ip_address)
                        VALUES (?, 'DELETE_ORDER', 'Deleted order #' . ?, ?)
                    ");
                    $stmt->execute([$_SESSION['user_id'], $order_id, $_SERVER['REMOTE_ADDR']]);
                    
                    handleFormSubmission("Order deleted successfully!");
                    break;

                case 'process_payment':
                    $order_id = $_POST['order_id'];
                    $total_amount = $_POST['amount'];
                    $amount_paid = $_POST['payment_method'] === 'CASH' ? $_POST['customer_payment'] : $total_amount;
                    $change_amount = $_POST['payment_method'] === 'CASH' ? ($amount_paid - $total_amount) : 0;
                    $payment_method = $_POST['payment_method'];
                    
                    // Start transaction
                    $conn->beginTransaction();
                    
                    // Create payment transaction
                    $stmt = $conn->prepare("
                        INSERT INTO payment_transactions (order_id, total_amount, amount_paid, change_amount, payment_method, status)
                        VALUES (?, ?, ?, ?, ?, 'COMPLETED')
                    ");
                    $stmt->execute([$order_id, $total_amount, $amount_paid, $change_amount, $payment_method]);
                    
                    // Update or insert sales record for the day
                    $today = date('Y-m-d');
                    $stmt = $conn->prepare("
                        INSERT INTO sales (date, total_revenue, cash_revenue, gcash_revenue, total_orders, cash_orders, gcash_orders)
                        VALUES (?, ?, ?, ?, 1, ?, ?)
                        ON DUPLICATE KEY UPDATE
                        total_revenue = total_revenue + VALUES(total_revenue),
                        cash_revenue = cash_revenue + VALUES(cash_revenue),
                        gcash_revenue = gcash_revenue + VALUES(gcash_revenue),
                        total_orders = total_orders + 1,
                        cash_orders = cash_orders + VALUES(cash_orders),
                        gcash_orders = gcash_orders + VALUES(gcash_orders)
                    ");
                    
                    $cash_revenue = $payment_method === 'CASH' ? $total_amount : 0;
                    $gcash_revenue = $payment_method === 'GCASH' ? $total_amount : 0;
                    $cash_orders = $payment_method === 'CASH' ? 1 : 0;
                    $gcash_orders = $payment_method === 'GCASH' ? 1 : 0;
                    
                    $stmt->execute([
                        $today,
                        $total_amount,
                        $cash_revenue,
                        $gcash_revenue,
                        $cash_orders,
                        $gcash_orders
                    ]);
                    
                    // Update order status to PAID for kitchen
                    $stmt = $conn->prepare("
                        UPDATE orders 
                        SET status = 'PAID'
                        WHERE order_id = ?
                    ");
                    $stmt->execute([$order_id]);
                    
                    // Create notification for kitchen
                    $stmt = $conn->prepare("
                        INSERT INTO notifications (order_id, message, type)
                        VALUES (?, CONCAT('Order #', ?, ' has been paid and is ready for preparation'), 'ORDER_READY')
                    ");
                    $stmt->execute([$order_id, $order_id]);
                    
                    // Log the action
                    $stmt = $conn->prepare("
                        INSERT INTO system_logs (user_id, action, description, ip_address)
                        VALUES (?, 'PROCESS_PAYMENT', CONCAT('Processed payment for order #', ?, ' using ', ?, '. Total: ', ?, ', Paid: ', ?, ', Change: ', ?), ?)
                    ");
                    $stmt->execute([$_SESSION['user_id'], $order_id, $payment_method, $total_amount, $amount_paid, $change_amount, $_SERVER['REMOTE_ADDR']]);
                    
                    $conn->commit();
                    
                    handleFormSubmission("Payment processed successfully! Order has been sent to kitchen.");
                    break;
            }
        } catch (PDOException $e) {
            handleFormSubmission(null, "Database error: " . $e->getMessage());
        }
    }
}

// Get active orders (PENDING)
try {
    $stmt = $conn->prepare("
        SELECT o.*, t.table_number, c.first_name, c.last_name, c.contact_number
        FROM orders o 
        LEFT JOIN tables t ON o.table_id = t.table_id 
        LEFT JOIN customers c ON o.customer_id = c.customer_id
        LEFT JOIN payment_transactions pt ON o.order_id = pt.order_id
        WHERE o.status = 'PENDING'
        AND pt.transaction_id IS NULL
        ORDER BY o.created_at DESC
    ");
    $stmt->execute();
    $active_orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $_SESSION['error_message'] = "Database error: " . $e->getMessage();
    $active_orders = [];
}

// Get paid orders (PAID)
try {
    $stmt = $conn->prepare("
        SELECT o.*, t.table_number, c.first_name, c.last_name, c.contact_number
        FROM orders o
        LEFT JOIN tables t ON o.table_id = t.table_id
        LEFT JOIN customers c ON o.customer_id = c.customer_id
        LEFT JOIN payment_transactions pt ON o.order_id = pt.order_id
        WHERE o.status = 'PAID'
        ORDER BY o.created_at DESC
    ");
    $stmt->execute();
    $paid_orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $_SESSION['error_message'] = "Database error: " . $e->getMessage();
    $paid_orders = [];
}

// Get completed orders (COMPLETED)
try {
    $stmt = $conn->prepare("
        SELECT o.*, t.table_number, c.first_name, c.last_name, c.contact_number
        FROM orders o
        LEFT JOIN tables t ON o.table_id = t.table_id
        LEFT JOIN customers c ON o.customer_id = c.customer_id
        LEFT JOIN payment_transactions pt ON o.order_id = pt.order_id
        WHERE o.status = 'COMPLETED'
        ORDER BY o.created_at DESC
    ");
    $stmt->execute();
    $completed_orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $_SESSION['error_message'] = "Database error: " . $e->getMessage();
    $completed_orders = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Orders - HarahQR Sales</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #4e73df;
            --secondary-color: #858796;
            --success-color: #1cc88a;
            --warning-color: #f6c23e;
            --danger-color: #e74a3b;
            --info-color: #36b9cc;
            --light-color: #f8f9fc;
            --dark-color: #5a5c69;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background-color: var(--light-color);
        }

        .navbar {
            background: linear-gradient(135deg, var(--primary-color), #224abe);
            padding: 1rem;
        }

            .navbar-brand {
            color: white !important;
            font-weight: 600;
        }

        .nav-link {
            color: rgba(255, 255, 255, 0.8) !important;
            transition: color 0.3s ease;
        }

        .nav-link:hover {
            color: white !important;
        }

        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
            margin-bottom: 2rem;
        }

        .card-header {
            background-color: white;
            border-bottom: 1px solid #e3e6f0;
            padding: 1rem 1.25rem;
            border-radius: 15px 15px 0 0 !important;
        }

        .card-title {
            color: var(--dark-color);
            font-weight: 600;
            margin: 0;
        }

        .table {
            margin-bottom: 0;
        }

        .table th {
            border-top: none;
            background-color: var(--light-color);
            color: var(--dark-color);
            font-weight: 600;
        }

        .table td {
            vertical-align: middle;
        }

        .status-badge {
            padding: 0.5rem 0.75rem;
            border-radius: 50px;
            font-weight: 500;
            font-size: 0.875rem;
        }

        .status-pending {
            background-color: #fff3cd;
            color: #856404;
        }

        .status-preparing {
            background-color: #cce5ff;
            color: #004085;
        }

        .status-completed {
            background-color: #d4edda;
            color: #155724;
        }

        .status-delivered {
            background-color: #d1e7dd;
            color: #0f5132;
        }

        .status-paid {
            background-color: #cce5ff;
            color: #004085;
        }

        .btn-action {
            padding: 0.375rem 0.75rem;
            font-size: 0.875rem;
            border-radius: 5px;
            transition: all 0.3s ease;
        }

        .btn-view {
            background-color: var(--info-color);
            color: white;
        }

        .btn-view:hover {
            background-color: #2c9faf;
            color: white;
        }

        .btn-update {
            background-color: var(--warning-color);
            color: white;
        }

        .btn-update:hover {
            background-color: #dda20a;
            color: white;
        }

        .btn-delete {
            background-color: var(--danger-color);
            color: white;
        }

        .btn-delete:hover {
            background-color: #be2617;
            color: white;
            }
            
            .alert {
            border: none;
            border-radius: 10px;
            padding: 1rem 1.25rem;
            margin-bottom: 1.5rem;
        }

        .alert-success {
            background-color: #d4edda;
            color: #155724;
        }

        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
        }

        .modal-content {
            border: none;
            border-radius: 15px;
        }

        .modal-header {
            border-bottom: 1px solid #e3e6f0;
            padding: 1rem 1.25rem;
        }

        .modal-title {
            color: var(--dark-color);
            font-weight: 600;
        }
        
        .modal-body {
            padding: 1.25rem;
        }

        .modal-footer {
            border-top: 1px solid #e3e6f0;
            padding: 1rem 1.25rem;
        }

        .form-label {
            color: var(--dark-color);
            font-weight: 500;
        }

        .form-select {
            border-radius: 5px;
            border: 1px solid #d1d3e2;
            padding: 0.375rem 0.75rem;
        }

        .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(78, 115, 223, 0.25);
        }

        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }

        .btn-primary:hover {
            background-color: #2e59d9;
            border-color: #2653d4;
        }

        .btn-secondary {
            background-color: var(--secondary-color);
            border-color: var(--secondary-color);
        }

        .btn-secondary:hover {
            background-color: #6b6d7d;
            border-color: #656776;
        }

        .empty-state {
            text-align: center;
            padding: 3rem 1rem;
            color: var(--secondary-color);
        }

        .empty-state i {
            font-size: 3rem;
            margin-bottom: 1rem;
            color: var(--secondary-color);
        }

        .empty-state p {
            margin-bottom: 0;
            font-size: 1.1rem;
        }

        .payment-section {
            background-color: #f8f9fc;
            padding: 1.25rem;
            border-radius: 10px;
            border: 1px solid #e3e6f0;
        }

        .form-check {
            padding: 1rem;
            border: 1px solid #e3e6f0;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .form-check:hover {
            background-color: #f8f9fc;
        }

        .form-check-input:checked + .form-check-label {
            color: var(--primary-color);
        }

        .form-check-input:checked ~ .form-check {
            border-color: var(--primary-color);
            background-color: rgba(78, 115, 223, 0.1);
        }

        .input-group-text {
            border: none;
            color: var(--primary-color);
        }

        .form-control {
            border: 1px solid #e3e6f0;
        }

        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(78, 115, 223, 0.25);
        }

        .modal-header {
            border-radius: 10px 10px 0 0;
        }

        .modal-content {
            border: none;
            border-radius: 10px;
        }

        .btn-close-white {
            filter: brightness(0) invert(1);
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark mb-4">
        <div class="container-fluid">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-qrcode me-2"></i>HarahQR Sales
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="orders.php">
                            <i class="fas fa-clipboard-list me-1"></i>Orders
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="../logout.php">
                            <i class="fas fa-sign-out-alt me-1"></i>Logout
                    </a>
                </li>
            </ul>
        </div>
    </div>
</nav>

    <div class="container-fluid">
        <?php 
        displaySuccessMessage();
        displayErrorMessage();
        ?>

        <!-- Active Orders -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title">
                    <i class="fas fa-clock me-2"></i>Active Orders
                        </h5>
                    </div>
                    <div class="card-body">
                <?php if (empty($active_orders)): ?>
                    <div class="empty-state">
                        <i class="fas fa-clipboard-list"></i>
                        <p>No active orders at the moment.</p>
                            </div>
                <?php else: ?>
                        <div class="table-responsive">
                        <table class="table">
                                <thead>
                                    <tr>
                                        <th>Order ID</th>
                                        <th>Table</th>
                                    <th>Customer</th>
                                    <th>Total Amount</th>
                                        <th>Status</th>
                                    <th>Created At</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($active_orders as $order): ?>
                                    <tr>
                                        <td>#<?php echo $order['order_id']; ?></td>
                                        <td>
                                            <?php 
                                            if ($order['order_type'] === 'QR') {
                                                echo $order['table_number'] ? 'Table ' . $order['table_number'] : 'N/A';
                                            } else {
                                                echo 'Walk-in';
                                            }
                                            ?>
                                            </td>
                                            <td>
                                                <?php
                                            if ($order['customer_id']) {
                                                echo htmlspecialchars($order['first_name'] . ' ' . $order['last_name']);
                                            } else {
                                                echo 'N/A';
                                            }
                                            ?>
                                            </td>
                                        <td>₱<?php echo number_format($order['total_amount'], 2); ?></td>
                                        <td>
                                            <span class="status-badge <?php echo 'status-' . strtolower($order['status']); ?>">
                                                <?php echo $order['status']; ?>
                                                </span>
                                            </td>
                                        <td><?php echo date('M d, Y h:i A', strtotime($order['created_at'])); ?></td>
                                        <td>
                                            <button type="button" class="btn btn-action btn-view" 
                                                    onclick="viewOrder(<?php echo $order['order_id']; ?>, '<?php echo $order['status']; ?>')">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <button type="button" class="btn btn-action btn-update me-1" 
                                                    onclick="processPayment(<?php echo $order['order_id']; ?>, <?php echo $order['total_amount']; ?>)">
                                                <i class="fas fa-money-bill-wave"></i>
                                            </button>
                                            <button type="button" class="btn btn-action btn-delete" 
                                                    onclick="deleteOrder(<?php echo $order['order_id']; ?>)">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                <?php endif; ?>
        </div>
    </div>

        <!-- Paid Orders -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title">
                    <i class="fas fa-utensils me-2"></i>Preparing Orders
                        </h5>
                    </div>
                    <div class="card-body">
                <?php if (empty($paid_orders)): ?>
                    <div class="empty-state">
                        <i class="fas fa-utensils"></i>
                        <p>No orders being prepared at the moment.</p>
                            </div>
                <?php else: ?>
                        <div class="table-responsive">
                        <table class="table">
                                <thead>
                                    <tr>
                                        <th>Order ID</th>
                                        <th>Table</th>
                                    <th>Customer</th>
                                    <th>Total Amount</th>
                                        <th>Status</th>
                                    <th>Created At</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                <?php foreach ($paid_orders as $order): ?>
                                    <tr>
                                        <td>#<?php echo $order['order_id']; ?></td>
                                        <td>
                                            <?php 
                                            if ($order['order_type'] === 'QR') {
                                                echo $order['table_number'] ? 'Table ' . $order['table_number'] : 'N/A';
                                            } else {
                                                echo 'Walk-in';
                                            }
                                            ?>
                                            </td>
                                            <td>
                                                <?php
                                            if ($order['customer_id']) {
                                                echo htmlspecialchars($order['first_name'] . ' ' . $order['last_name']);
                                            } else {
                                                echo 'N/A';
                                            }
                                            ?>
                                            </td>
                                        <td>₱<?php echo number_format($order['total_amount'], 2); ?></td>
                                        <td>
                                            <span class="status-badge status-paid">
                                                <?php echo $order['status']; ?>
                                                </span>
                                            </td>
                                        <td><?php echo date('M d, Y h:i A', strtotime($order['created_at'])); ?></td>
                                        <td>
                                            <button type="button" class="btn btn-action btn-view" 
                                                    onclick="viewOrder(<?php echo $order['order_id']; ?>, '<?php echo $order['status']; ?>')">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                <?php endif; ?>
        </div>
    </div>

        <!-- Completed Orders -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title">
                    <i class="fas fa-check-circle me-2"></i>Completed Orders
                                </h5>
                        </div>
                        <div class="card-body">
                <?php if (empty($completed_orders)): ?>
                    <div class="empty-state">
                        <i class="fas fa-clipboard-check"></i>
                        <p>No completed orders found.</p>
                            </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Order ID</th>
                                    <th>Table</th>
                                    <th>Customer</th>
                                    <th>Total Amount</th>
                                    <th>Status</th>
                                    <th>Created At</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($completed_orders as $order): ?>
                                    <tr>
                                        <td>#<?php echo $order['order_id']; ?></td>
                                        <td>
                                <?php
                                            if ($order['order_type'] === 'QR') {
                                                echo $order['table_number'] ? 'Table ' . $order['table_number'] : 'N/A';
                                            } else {
                                                echo 'Walk-in';
                                            }
                                            ?>
                                        </td>
                                        <td>
                                <?php 
                                            if ($order['customer_id']) {
                                                echo htmlspecialchars($order['first_name'] . ' ' . $order['last_name']);
                                            } else {
                                                echo 'N/A';
                                            }
                                            ?>
                                        </td>
                                        <td>₱<?php echo number_format($order['total_amount'], 2); ?></td>
                                        <td>
                                            <span class="status-badge <?php echo 'status-' . strtolower($order['status']); ?>">
                                                <?php echo $order['status']; ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('M d, Y h:i A', strtotime($order['created_at'])); ?></td>
                                        <td>
                                            <button type="button" class="btn btn-action btn-view" 
                                                    onclick="viewOrder(<?php echo $order['order_id']; ?>, '<?php echo $order['status']; ?>')">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                                    </div>
                                <?php endif; ?>
                            </div>
                                </div>
                            </div>

    <!-- View Order Modal -->
    <div class="modal fade" id="viewOrderModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Order Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="orderDetails"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-2"></i>Close
                    </button>
                    <button type="button" class="btn btn-primary d-none" id="printReceiptBtn" onclick="printOrderReceipt()">
                        <i class="fas fa-receipt me-2"></i>Print Receipt
                    </button>
                </div>
            </div>
        </div>
    </div>
    
</div>

    <!-- Update Status Modal -->
    <div class="modal fade" id="updateStatusModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                    <h5 class="modal-title">Update Order Status</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
                <form method="POST">
            <div class="modal-body">
                        <input type="hidden" name="action" value="update_status">
                        <input type="hidden" name="order_id" id="update_order_id">
                        <div class="mb-3">
                            <label for="new_status" class="form-label">New Status</label>
                            <select class="form-select" id="new_status" name="new_status" required>
                                <option value="PENDING">Pending</option>
                                <option value="PAID">Paid</option>
                                <option value="COMPLETED">Completed</option>
                        </select>
                    </div>
                        </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Update Status</button>
                    </div>
                </form>
        </div>
    </div>
</div>

    <!-- Delete Order Modal -->
    <div class="modal fade" id="deleteOrderModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                    <h5 class="modal-title">Delete Order</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                    <p>Are you sure you want to delete this order? This action cannot be undone.</p>
            </div>
            <div class="modal-footer">
                    <form method="POST">
                        <input type="hidden" name="action" value="delete_order">
                        <input type="hidden" name="order_id" id="delete_order_id">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger">Delete Order</button>
                    </form>
            </div>
        </div>
    </div>
</div>

    <!-- Process Payment Modal -->
    <div class="modal fade" id="processPaymentModal" tabindex="-1">
        <div class="modal-dialog">
        <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">
                        <i class="fas fa-money-bill-wave me-2"></i>Process Payment
                </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
                <form method="POST" id="paymentForm">
            <div class="modal-body">
                        <input type="hidden" name="action" value="process_payment">
                        <input type="hidden" name="order_id" id="payment_order_id">
                        <input type="hidden" name="amount" id="payment_amount">
                        
                        <!-- Total Amount Section -->
                        <div class="payment-section mb-4">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <label class="form-label mb-0">Total Amount</label>
                                <span class="text-muted">Order #<span id="display_order_id"></span></span>
                            </div>
                            <div class="input-group input-group-lg">
                                <span class="input-group-text bg-light">
                                    <i class="fas fa-peso-sign"></i>
                                        </span>
                                <input type="text" class="form-control form-control-lg text-end fw-bold" 
                                       id="display_total" readonly>
                            </div>
                                    </div>
                                    
                        <!-- Payment Method Section -->
                        <div class="payment-section mb-4">
                            <label class="form-label mb-2">Payment Method</label>
                            <div class="d-flex gap-3">
                                <div class="form-check flex-grow-1">
                                    <input class="form-check-input" type="radio" name="payment_method" 
                                           id="cash_payment" value="CASH" checked onchange="togglePaymentInput()">
                                    <label class="form-check-label d-flex align-items-center" for="cash_payment">
                                        <i class="fas fa-money-bill-wave me-2"></i>Cash
                                    </label>
                                    </div>
                                <div class="form-check flex-grow-1">
                                    <input class="form-check-input" type="radio" name="payment_method" 
                                           id="gcash_payment" value="GCASH" onchange="togglePaymentInput()">
                                    <label class="form-check-label d-flex align-items-center" for="gcash_payment">
                                        <i class="fas fa-mobile-alt me-2"></i>GCash
                                    </label>
                                </div>
                </div>
            </div>

                        <!-- Cash Payment Section -->
                        <div class="payment-section mb-4" id="cash_payment_div">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <label class="form-label mb-0">Customer Payment</label>
                                <small class="text-muted">Enter amount received</small>
            </div>
                            <div class="input-group input-group-lg">
                                <span class="input-group-text bg-light">
                                    <i class="fas fa-peso-sign"></i>
                                </span>
                                <input type="number" step="0.01" class="form-control form-control-lg text-end" 
                                       id="customer_payment" name="customer_payment" required oninput="calculateChange()">
    </div>
</div>

                        <!-- Change Section -->
                        <div class="payment-section">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <label class="form-label mb-0">Change</label>
                                <small class="text-muted">Amount to return</small>
            </div>
                            <div class="input-group input-group-lg">
                                <span class="input-group-text bg-light">
                                    <i class="fas fa-peso-sign"></i>
                                </span>
                                <input type="text" class="form-control form-control-lg text-end fw-bold" 
                                       id="change_amount" readonly>
                </div>
                            <div id="insufficient_payment" class="text-danger mt-2" style="display: none;">
                                <i class="fas fa-exclamation-circle me-1"></i>Insufficient payment
                            </div>
                </div>
            </div>
            <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">
                            <i class="fas fa-times me-2"></i>Cancel
                        </button>
                        <button type="submit" class="btn btn-primary" id="submit_payment">
                            <i class="fas fa-check me-2"></i>Process Payment
                        </button>
            </div>
                </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
        // Initialize modals
        const viewOrderModal = new bootstrap.Modal(document.getElementById('viewOrderModal'));
        const updateStatusModal = new bootstrap.Modal(document.getElementById('updateStatusModal'));
        const deleteOrderModal = new bootstrap.Modal(document.getElementById('deleteOrderModal'));
        const processPaymentModal = new bootstrap.Modal(document.getElementById('processPaymentModal'));

        function viewOrder(orderId, status) {
            // Fetch order details using AJAX
            fetch(`get_order_details.php?order_id=${orderId}`)
                .then(response => response.json())
                .then(data => {
                    document.getElementById('orderDetails').innerHTML = data.html;
                    // Show print receipt button only for paid and completed orders
                    const printBtn = document.getElementById('printReceiptBtn');
                    if (status === 'PAID' || status === 'COMPLETED') {
                        printBtn.classList.remove('d-none');
                    } else {
                        printBtn.classList.add('d-none');
                    }
                    viewOrderModal.show();
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error loading order details');
                });
        }

        function updateStatus(orderId, currentStatus) {
            document.getElementById('update_order_id').value = orderId;
            document.getElementById('new_status').value = currentStatus;
            updateStatusModal.show();
        }

        function deleteOrder(orderId) {
            document.getElementById('delete_order_id').value = orderId;
            deleteOrderModal.show();
        }

        function processPayment(orderId, amount) {
            document.getElementById('payment_order_id').value = orderId;
            document.getElementById('payment_amount').value = amount;
            document.getElementById('display_order_id').textContent = orderId;
            document.getElementById('display_total').value = amount.toFixed(2);
            document.getElementById('customer_payment').value = '';
            document.getElementById('change_amount').value = '0.00';
            document.getElementById('submit_payment').disabled = true;
            togglePaymentInput();
            processPaymentModal.show();
        }

        function calculateChange() {
            const total = parseFloat(document.getElementById('display_total').value);
            const payment = parseFloat(document.getElementById('customer_payment').value) || 0;
            const change = Math.max(0, payment - total);
            const insufficientPaymentDiv = document.getElementById('insufficient_payment');
            const changeInput = document.getElementById('change_amount');
            
            // Update change amount display
            changeInput.value = change.toFixed(2);
            
            // Handle insufficient payment styling
            if (payment < total) {
                changeInput.classList.add('text-danger');
                insufficientPaymentDiv.style.display = 'block';
                document.getElementById('submit_payment').disabled = true;
            } else {
                changeInput.classList.remove('text-danger');
                insufficientPaymentDiv.style.display = 'none';
                document.getElementById('submit_payment').disabled = false;
            }
        }

        function togglePaymentInput() {
            const paymentMethod = document.querySelector('input[name="payment_method"]:checked').value;
            const cashPaymentDiv = document.getElementById('cash_payment_div');
            const customerPayment = document.getElementById('customer_payment');
            const insufficientPaymentDiv = document.getElementById('insufficient_payment');
            const changeInput = document.getElementById('change_amount');
            
            if (paymentMethod === 'CASH') {
                cashPaymentDiv.style.display = 'block';
                customerPayment.required = true;
                calculateChange();
            } else {
                cashPaymentDiv.style.display = 'none';
                customerPayment.required = false;
                customerPayment.value = document.getElementById('display_total').value;
                changeInput.value = '0.00';
                changeInput.classList.remove('text-danger');
                insufficientPaymentDiv.style.display = 'none';
                document.getElementById('submit_payment').disabled = false;
            }
        }

        function printOrderReceipt() {
            const content = document.getElementById('orderDetails').innerHTML;
            const printWindow = window.open('', '_blank');
            printWindow.document.write(`
                <!DOCTYPE html>
                <html>
                <head>
                    <title>Order Receipt - Harah Rubina Del Dios Farm</title>
                    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
                    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
                    <style>
                        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap');
                        
                        body {
                            font-family: 'Poppins', sans-serif;
                            padding: 20px;
                            background-color: #f8f9fa;
                        }
                        
                        .receipt-container {
                            max-width: 800px;
                            margin: 0 auto;
                            background-color: white;
                            padding: 30px;
                            border-radius: 10px;
                            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
                        }
                        
                        .receipt-header {
                            text-align: center;
                            padding-bottom: 20px;
                            border-bottom: 2px dashed #dee2e6;
                            margin-bottom: 20px;
                        }
                        
                        .receipt-header h3 {
                            font-weight: 700;
                            color: #2c3e50;
                            margin-bottom: 5px;
                            font-size: 24px;
                        }
                        
                        .receipt-header p {
                            color: #6c757d;
                            margin-bottom: 5px;
                            font-size: 14px;
                        }
                        
                        .receipt-body {
                            margin-bottom: 20px;
                        }
                        
                        .receipt-footer {
                            text-align: center;
                            padding-top: 20px;
                            border-top: 2px dashed #dee2e6;
                        }
                        
                        .receipt-footer p {
                            color: #6c757d;
                            font-size: 14px;
                            margin-bottom: 5px;
                        }
                        
                        .order-details {
                            margin: 15px 0;
                        }
                        
                        .order-item {
                            margin-bottom: 10px;
                        }
                        
                        .total-amount {
                            font-weight: 600;
                            font-size: 16px;
                            color: #2c3e50;
                            margin-top: 15px;
                            padding-top: 10px;
                            border-top: 1px solid #dee2e6;
                        }
                        
                        .table {
                            width: 100%;
                            margin-bottom: 1rem;
                            border-collapse: collapse;
                        }
                        
                        .table th,
                        .table td {
                            padding: 12px;
                            border-bottom: 1px solid #dee2e6;
                        }
                        
                        .table th {
                            font-weight: 600;
                            background-color: #f8f9fa;
                        }
                        
                        .table-responsive {
                            overflow: visible !important;
                        }
                        
                        .card {
                            border: 1px solid #dee2e6;
                            border-radius: 10px;
                            margin-bottom: 20px;
                        }
                        
                        .card-header {
                            background-color: #f8f9fa;
                            padding: 15px;
                            border-bottom: 1px solid #dee2e6;
                        }
                        
                        .card-body {
                            padding: 15px;
                        }
                        
                        .print-button {
                            background-color: #4e73df;
                            color: white;
                            border: none;
                            padding: 10px 20px;
                            border-radius: 5px;
                            cursor: pointer;
                            transition: background-color 0.3s;
                            margin-top: 20px;
                        }
                        
                        .print-button:hover {
                            background-color: #2e59d9;
                        }
                        
                        @media print {
                            body {
                                background-color: white;
                                padding: 0;
                                margin: 0;
                            }
                            
                            .receipt-container {
                                max-width: 100%;
                                box-shadow: none;
                                padding: 15px;
                            }
                            
                            .table-responsive {
                                overflow: visible !important;
                            }
                            
                            .table {
                                page-break-inside: auto;
                            }
                            
                            tr {
                                page-break-inside: avoid;
                                page-break-after: auto;
                            }
                            
                            .no-print {
                                display: none;
                            }
                            
                            .card {
                                border: none;
                            }
                            
                            .card-header {
                                background-color: transparent;
                            }
                        }
                    </style>
                </head>
                <body>
                    <div class="receipt-container">
                        <div class="receipt-header">
                            <h3>Harah Rubina Del Dios Farm</h3>
                            <p><i class="fas fa-map-marker-alt"></i> Brgy. Santo Nino, Manolo Fortich, Bukidnon</p>
                            <p><i class="fas fa-phone"></i> Contact: 09533480232 </p>
                            <p><i class="fas fa-clock"></i> ${new Date().toLocaleString()}</p>
                        </div>
                        <div class="receipt-body">
                            ${content}
                        </div>
                        <div class="receipt-footer">
                            <p>Thank you for choosing</p>
                            <p>Harah Rubina Del Dios Farm!</p>
                            <button class="print-button mt-4 no-print" onclick="window.print()">
                                <i class="fas fa-print me-2"></i>Print Receipt
                            </button>
                        </div>
                    </div>
                </body>
                </html>
            `);
            printWindow.document.close();
        }
</script>
    <?php addPreventResubmissionScript(); ?>
</body>
</html> 