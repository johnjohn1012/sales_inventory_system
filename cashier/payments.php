<?php
session_start();
require_once '../config/database.php';

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
                case 'process_payment':
                    $order_id = $_POST['order_id'];
                    $amount_received = $_POST['amount_received'];
                    $payment_method = $_POST['payment_method'];
                    
                    // Get order total amount
                    $orderStmt = $conn->prepare("SELECT total_amount FROM orders WHERE order_id = ?");
                    $orderStmt->execute([$order_id]);
                    $orderTotal = $orderStmt->fetch(PDO::FETCH_ASSOC)['total_amount'];
                    
                    // Validate amount received
                    if ($amount_received < $orderTotal) {
                        throw new Exception("Amount received cannot be less than the total amount");
                    }
                    
                    // Calculate change
                    $change_amount = $amount_received - $orderTotal;
                    
                    // Start transaction
                    $conn->beginTransaction();
                    
                    // Create payment transaction with additional fields
                    $stmt = $conn->prepare("
                        INSERT INTO payment_transactions (
                            order_id, 
                            amount, 
                            amount_received,
                            change_amount,
                            payment_method, 
                            gcash_reference,
                            gcash_account,
                            status
                        )
                        VALUES (?, ?, ?, ?, ?, ?, ?, 'COMPLETED')
                    ");
                    
                    $gcash_reference = ($payment_method === 'GCASH') ? $_POST['gcash_reference'] : null;
                    $gcash_account = ($payment_method === 'GCASH') ? $_POST['gcash_account'] : null;
                    
                    $stmt->execute([
                        $order_id,
                        $orderTotal,
                        $amount_received,
                        $change_amount,
                        $payment_method,
                        $gcash_reference,
                        $gcash_account
                    ]);
                    
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
                    
                    $cash_revenue = $payment_method === 'CASH' ? $orderTotal : 0;
                    $gcash_revenue = $payment_method === 'GCASH' ? $orderTotal : 0;
                    $cash_orders = $payment_method === 'CASH' ? 1 : 0;
                    $gcash_orders = $payment_method === 'GCASH' ? 1 : 0;
                    
                    $stmt->execute([
                        $today,
                        $orderTotal,
                        $cash_revenue,
                        $gcash_revenue,
                        $cash_orders,
                        $gcash_orders
                    ]);
                    
                    // Update order status to PAID
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
                        VALUES (?, 'PROCESS_PAYMENT', CONCAT('Processed payment for order #', ?, ' using ', ?), ?)
                    ");
                    $stmt->execute([$_SESSION['user_id'], $order_id, $payment_method, $_SERVER['REMOTE_ADDR']]);
                    
                    $conn->commit();
                    
                    $_SESSION['success_message'] = "Payment processed successfully! Order has been sent to the kitchen.";
                    break;
            }
        } catch (PDOException $e) {
            $conn->rollBack();
            $_SESSION['error_message'] = "Database error: " . $e->getMessage();
        } catch (Exception $e) {
            $conn->rollBack();
            $_SESSION['error_message'] = $e->getMessage();
        }
    }
}

// Get pending orders
try {
    $stmt = $conn->prepare("
        SELECT o.*, t.table_number, c.first_name, c.last_name, c.contact_number
        FROM orders o
        LEFT JOIN tables t ON o.table_id = t.table_id
        LEFT JOIN customers c ON o.customer_id = c.customer_id
        WHERE o.status = 'PENDING'
        ORDER BY o.created_at DESC
    ");
    $stmt->execute();
    $pending_orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $_SESSION['error_message'] = "Database error: " . $e->getMessage();
    $pending_orders = [];
}

// Get recent payments
try {
    $stmt = $conn->prepare("
        SELECT pt.*, o.order_id, o.total_amount, o.order_type, o.customer_id,
               t.table_number, c.first_name, c.last_name
        FROM payment_transactions pt
        JOIN orders o ON pt.order_id = o.order_id
        LEFT JOIN tables t ON o.table_id = t.table_id
        LEFT JOIN customers c ON o.customer_id = c.customer_id
        WHERE pt.status = 'COMPLETED'
        ORDER BY pt.created_at DESC
        LIMIT 10
    ");
    $stmt->execute();
    $recent_payments = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $_SESSION['error_message'] = "Database error: " . $e->getMessage();
    $recent_payments = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Process Payments - HarahQR Sales</title>
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

        .status-completed {
            background-color: #d4edda;
            color: #155724;
        }

        .status-pending {
            background-color: #fff3cd;
            color: #856404;
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

        .btn-pay {
            background-color: var(--success-color);
            color: white;
        }

        .btn-pay:hover {
            background-color: #169b6b;
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

        .payment-method-badge {
            padding: 0.25rem 0.5rem;
            border-radius: 50px;
            font-size: 0.875rem;
            font-weight: 500;
        }

        .payment-method-cash {
            background-color: #e3e6f0;
            color: var(--dark-color);
        }

        .payment-method-gcash {
            background-color: #cce5ff;
            color: #004085;
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
                        <a class="nav-link" href="index.php">
                            <i class="fas fa-home me-1"></i>Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="orders.php">
                            <i class="fas fa-clipboard-list me-1"></i>Orders
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="payments.php">
                            <i class="fas fa-money-bill-wave me-1"></i>Payments
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
        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i>
                <?php 
                echo $_SESSION['success_message'];
                unset($_SESSION['success_message']);
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i>
                <?php 
                echo $_SESSION['error_message'];
                unset($_SESSION['error_message']);
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Pending Payments -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title">
                    <i class="fas fa-clock me-2"></i>Pending Payments
                </h5>
            </div>
            <div class="card-body">
                <?php if (empty($pending_orders)): ?>
                    <div class="empty-state">
                        <i class="fas fa-money-bill-wave"></i>
                        <p>No pending payments at the moment.</p>
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
                                    <th>Created At</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($pending_orders as $order): ?>
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
                                        <td><?php echo date('M d, Y h:i A', strtotime($order['created_at'])); ?></td>
                                        <td>
                                            <button type="button" class="btn btn-action btn-view me-1" 
                                                    onclick="viewOrder(<?php echo $order['order_id']; ?>)">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <button type="button" class="btn btn-action btn-pay" 
                                                    onclick="processPayment(<?php echo $order['order_id']; ?>, <?php echo $order['total_amount']; ?>)">
                                                <i class="fas fa-money-bill-wave"></i>
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

        <!-- Recent Payments -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title">
                    <i class="fas fa-history me-2"></i>Recent Payments
                </h5>
            </div>
            <div class="card-body">
                <?php if (empty($recent_payments)): ?>
                    <div class="empty-state">
                        <i class="fas fa-history"></i>
                        <p>No recent payments found.</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Transaction ID</th>
                                    <th>Order ID</th>
                                    <th>Table</th>
                                    <th>Customer</th>
                                    <th>Amount</th>
                                    <th>Method</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_payments as $payment): ?>
                                    <tr>
                                        <td>#<?php echo $payment['transaction_id']; ?></td>
                                        <td>#<?php echo $payment['order_id']; ?></td>
                                        <td>
                                            <?php 
                                            if ($payment['order_type'] === 'QR') {
                                                echo $payment['table_number'] ? 'Table ' . $payment['table_number'] : 'N/A';
                                            } else {
                                                echo 'Walk-in';
                                            }
                                            ?>
                                        </td>
                                        <td>
                                            <?php 
                                            if (!empty($payment['first_name']) && !empty($payment['last_name'])) {
                                                echo htmlspecialchars($payment['first_name'] . ' ' . $payment['last_name']);
                                            } else {
                                                echo 'N/A';
                                            }
                                            ?>
                                        </td>
                                        <td>₱<?php echo number_format($payment['amount'], 2); ?></td>
                                        <td>
                                            <span class="payment-method-badge payment-method-<?php echo strtolower($payment['payment_method']); ?>">
                                                <?php echo $payment['payment_method']; ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('M d, Y h:i A', strtotime($payment['created_at'])); ?></td>
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
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Process Payment Modal -->
    <div class="modal fade" id="processPaymentModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Process Payment</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="process_payment">
                        <input type="hidden" name="order_id" id="payment_order_id">
                        
                        <!-- Total Amount Section -->
                        <div class="card mb-3">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div class="fw-bold">Total Amount</div>
                                    <div class="d-flex align-items-center">
                                        <span class="me-2">Order #<span id="display_order_id"></span></span>
                                    </div>
                                </div>
                                <div class="input-group mt-2">
                                    <span class="input-group-text">₱</span>
                                    <input type="text" class="form-control text-end" id="display_amount" readonly>
                                </div>
                            </div>
                        </div>

                        <!-- Payment Method Section -->
                        <div class="card mb-3">
                            <div class="card-body">
                                <div class="fw-bold mb-3">Payment Method</div>
                                <div class="d-flex gap-3">
                                    <div class="form-check flex-fill">
                                        <input class="form-check-input" type="radio" name="payment_method" id="cashMethod" value="CASH" checked onchange="handlePaymentMethodChange()">
                                        <label class="form-check-label w-100 p-2 border rounded" for="cashMethod">
                                            <i class="fas fa-money-bill me-2"></i>Cash
                                        </label>
                                    </div>
                                    <div class="form-check flex-fill">
                                        <input class="form-check-input" type="radio" name="payment_method" id="gcashMethod" value="GCASH" onchange="handlePaymentMethodChange()">
                                        <label class="form-check-label w-100 p-2 border rounded" for="gcashMethod">
                                            <i class="fas fa-mobile-alt me-2"></i>GCash
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Customer Payment Section -->
                        <div class="card mb-3">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div class="fw-bold">Customer Payment</div>
                                    <small class="text-muted">Enter amount received</small>
                                </div>
                                <div class="input-group mt-2">
                                    <span class="input-group-text">₱</span>
                                    <input type="number" step="0.01" class="form-control text-end" id="amount_received" name="amount_received" required>
                                </div>
                                <div class="form-text text-danger" id="amount_error" style="display: none;">
                                    Amount received must be greater than or equal to the total amount
                                </div>
                            </div>
                        </div>

                        <!-- Change Section -->
                        <div class="card mb-3">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div class="fw-bold">Change</div>
                                    <small class="text-muted">Amount to return</small>
                                </div>
                                <div class="input-group mt-2">
                                    <span class="input-group-text">₱</span>
                                    <input type="text" class="form-control text-end" id="change_amount" readonly>
                                </div>
                            </div>
                        </div>

                        <!-- GCash Details (Hidden by default) -->
                        <div id="gcash_details" style="display: none;">
                            <div class="card mb-3">
                                <div class="card-body">
                                    <div class="mb-3">
                                        <label for="gcash_reference" class="form-label">GCash Reference Number</label>
                                        <input type="text" class="form-control" id="gcash_reference" name="gcash_reference">
                                    </div>
                                    <div class="mb-3">
                                        <label for="gcash_account" class="form-label">Customer's GCash Number</label>
                                        <input type="text" class="form-control" id="gcash_account" name="gcash_account" pattern="[0-9]{11}">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer justify-content-between">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times me-2"></i>Cancel
                        </button>
                        <button type="submit" class="btn btn-primary" id="process_payment_btn">
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
        const processPaymentModal = new bootstrap.Modal(document.getElementById('processPaymentModal'));

        function viewOrder(orderId) {
            // Fetch order details using AJAX
            fetch(`get_order_details.php?order_id=${orderId}`)
                .then(response => response.json())
                .then(data => {
                    document.getElementById('orderDetails').innerHTML = data.html;
                    viewOrderModal.show();
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error loading order details');
                });
        }

        let currentTotalAmount = 0;

        function processPayment(orderId, amount) {
            document.getElementById('payment_order_id').value = orderId;
            document.getElementById('display_order_id').textContent = orderId;
            document.getElementById('display_amount').value = amount.toFixed(2);
            currentTotalAmount = amount;
            
            // Reset form fields
            document.getElementById('amount_received').value = '';
            document.getElementById('gcash_reference').value = '';
            document.getElementById('gcash_account').value = '';
            document.getElementById('change_amount').value = '0.00';
            document.getElementById('cashMethod').checked = true;
            document.getElementById('amount_error').style.display = 'none';
            document.getElementById('gcash_details').style.display = 'none';
            
            processPaymentModal.show();
        }

        function handlePaymentMethodChange() {
            const paymentMethod = document.querySelector('input[name="payment_method"]:checked').value;
            const gcashDetails = document.getElementById('gcash_details');
            
            if (paymentMethod === 'GCASH') {
                gcashDetails.style.display = 'block';
                document.getElementById('gcash_reference').required = true;
                document.getElementById('gcash_account').required = true;
            } else {
                gcashDetails.style.display = 'none';
                document.getElementById('gcash_reference').required = false;
                document.getElementById('gcash_account').required = false;
            }
        }

        // Add event listener for amount received input
        document.getElementById('amount_received').addEventListener('input', function(e) {
            const amountReceived = parseFloat(e.target.value) || 0;
            const changeAmount = amountReceived - currentTotalAmount;
            const changeField = document.getElementById('change_amount');
            const errorDiv = document.getElementById('amount_error');
            const submitBtn = document.getElementById('process_payment_btn');
            
            if (amountReceived >= currentTotalAmount) {
                changeField.value = changeAmount.toFixed(2);
                errorDiv.style.display = 'none';
                submitBtn.disabled = false;
            } else {
                changeField.value = '0.00';
                errorDiv.style.display = 'block';
                submitBtn.disabled = true;
            }
        });

        // Form validation before submit
        document.querySelector('#processPaymentModal form').addEventListener('submit', function(e) {
            const amountReceived = parseFloat(document.getElementById('amount_received').value) || 0;
            const paymentMethod = document.getElementById('payment_method').value;
            
            if (amountReceived < currentTotalAmount) {
                e.preventDefault();
                document.getElementById('amount_error').style.display = 'block';
                return;
            }
            
            if (paymentMethod === 'GCASH') {
                const reference = document.getElementById('gcash_reference').value;
                const account = document.getElementById('gcash_account').value;
                
                if (!reference || !account) {
                    e.preventDefault();
                    alert('Please fill in all GCash details');
                    return;
                }
                
                if (!/^\d{11}$/.test(account)) {
                    e.preventDefault();
                    alert('Please enter a valid 11-digit GCash number');
                    return;
                }
            }
        });
    </script>
</body>
</html> 