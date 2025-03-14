<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'ADMIN') {
    header('Location: ../login.php');
    exit();
}

$message = '';
$error = '';

// Get filter parameters
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d', strtotime('-30 days'));
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');
$payment_method = isset($_GET['payment_method']) ? $_GET['payment_method'] : '';
$status = isset($_GET['status']) ? $_GET['status'] : '';
$search = isset($_GET['search']) ? $_GET['search'] : '';

// Build query
$query = "
    SELECT pt.*, o.order_id, o.total_amount as order_total,
           c.first_name, c.last_name, c.contact_number
    FROM payment_transactions pt
    JOIN orders o ON pt.order_id = o.order_id
    LEFT JOIN customers c ON o.customer_id = c.customer_id
    WHERE pt.created_at BETWEEN :start_date AND :end_date
";

$params = [
    ':start_date' => $start_date . ' 00:00:00',
    ':end_date' => $end_date . ' 23:59:59'
];

if ($payment_method) {
    $query .= " AND pt.payment_method = :payment_method";
    $params[':payment_method'] = $payment_method;
}

if ($status) {
    $query .= " AND pt.status = :status";
    $params[':status'] = $status;
}

if ($search) {
    $query .= " AND (c.first_name LIKE :search OR c.last_name LIKE :search OR c.contact_number LIKE :search)";
    $params[':search'] = "%$search%";
}

$query .= " ORDER BY pt.created_at DESC";

// Get total records for pagination
$count_query = str_replace("pt.*, o.order_id, o.total_amount as order_total, c.first_name, c.last_name, c.contact_number", "COUNT(*)", $query);
$stmt = $conn->prepare($count_query);
$stmt->execute($params);
$total_records = $stmt->fetchColumn();

// Pagination
$records_per_page = 20;
$total_pages = ceil($total_records / $records_per_page);
$current_page = isset($_GET['page']) ? max(1, min($total_pages, intval($_GET['page']))) : 1;
$offset = ($current_page - 1) * $records_per_page;

// Add pagination to query
$query .= " LIMIT :limit OFFSET :offset";

// Prepare the query
$stmt = $conn->prepare($query);

// Bind all parameters
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}

// Bind LIMIT and OFFSET as integers
$stmt->bindValue(':limit', $records_per_page, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

// Execute the query
$stmt->execute();
$transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get unique payment methods and statuses for filters
$stmt = $conn->query("SELECT DISTINCT payment_method FROM payment_transactions ORDER BY payment_method");
$payment_methods = $stmt->fetchAll(PDO::FETCH_COLUMN);

$stmt = $conn->query("SELECT DISTINCT status FROM payment_transactions ORDER BY status");
$statuses = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Get summary statistics
$summary_query = "
    SELECT 
        COUNT(*) as total_transactions,
        SUM(CASE WHEN status = 'COMPLETED' THEN 1 ELSE 0 END) as completed_transactions,
        SUM(CASE WHEN status = 'PENDING' THEN 1 ELSE 0 END) as pending_transactions,
        SUM(CASE WHEN status = 'FAILED' THEN 1 ELSE 0 END) as failed_transactions,
        SUM(CASE WHEN status = 'COMPLETED' THEN total_amount ELSE 0 END) as total_amount
    FROM payment_transactions
    WHERE created_at BETWEEN :start_date AND :end_date
";

$stmt = $conn->prepare($summary_query);
$stmt->execute([
    ':start_date' => $start_date . ' 00:00:00',
    ':end_date' => $end_date . ' 23:59:59'
]);
$summary = $stmt->fetch(PDO::FETCH_ASSOC);
?>


    <style>
        :root {
            --primary-color: #4e73df;
            --secondary-color: #858796;
            --success-color: #1cc88a;
        }

   
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .card-header {
            background: white;
            border-bottom: 2px solid #f8f9fc;
            padding: 1.5rem;
            border-radius: 15px 15px 0 0 !important;
        }

        .btn-primary {
            background: var(--primary-color);
            border: none;
            padding: 8px 20px;
            border-radius: 25px;
        }

        .btn-primary:hover {
            background: #224abe;
        }

        .table th {
            border-top: none;
            font-weight: 600;
            color: var(--secondary-color);
        }

        .table td {
            vertical-align: middle;
        }

        .form-control {
            border-radius: 10px;
            border: 2px solid #e0e0e0;
            padding: 10px 15px;
        }

        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: none;
        }

        .alert {
            border-radius: 10px;
            border: none;
        }

        .alert-success {
            background: #e3fcef;
            color: #1cc88a;
        }

        .alert-danger {
            background: #fce3e3;
            color: #e74a3b;
        }

        .transaction-card {
            background: white;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 15px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
            transition: all 0.3s ease;
        }

        .transaction-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        }

        .transaction-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }

        .transaction-content {
            color: var(--secondary-color);
            margin-bottom: 10px;
        }

        .transaction-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.9rem;
            color: var(--secondary-color);
        }

        .payment-method {
            padding: 3px 8px;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: 500;
        }

        .method-cash {
            background: #e3fcef;
            color: #1cc88a;
        }

        .method-gcash {
            background: #cce5ff;
            color: #004085;
        }

        .status {
            padding: 3px 8px;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: 500;
        }

        .status-completed {
            background: #e3fcef;
            color: #1cc88a;
        }

        .status-pending {
            background: #fff3cd;
            color: #856404;
        }

        .status-failed {
            background: #fce3e3;
            color: #e74a3b;
        }

        .pagination .page-link {
            border-radius: 10px;
            margin: 0 2px;
            color: var(--primary-color);
        }

        .pagination .page-item.active .page-link {
            background: var(--primary-color);
            border-color: var(--primary-color);
        }

        .summary-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }

        .summary-card h6 {
            color: var(--secondary-color);
            margin-bottom: 10px;
        }

        .summary-card h3 {
            color: var(--primary-color);
            margin-bottom: 0;
        }

        .summary-card .trend {
            font-size: 0.9rem;
            color: var(--success-color);
        }

        .summary-card .trend.negative {
            color: #e74a3b;
        }
    </style>
<?php include 'components/header.php'; ?>

<?php include 'components/navbar.php'; ?>
  
    <div class="container mt-4">
        <?php if ($message): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i><?php echo $message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i><?php echo $error; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Summary Cards -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="summary-card">
                    <h6>Total Transactions</h6>
                    <h3><?php echo number_format($summary['total_transactions']); ?></h3>
                </div>
            </div>
            <div class="col-md-3">
                <div class="summary-card">
                    <h6>Completed Transactions</h6>
                    <h3><?php echo number_format($summary['completed_transactions']); ?></h3>
                    <span class="trend">
                        <?php echo round(($summary['completed_transactions'] / $summary['total_transactions']) * 100); ?>% success rate
                    </span>
                </div>
            </div>
            <div class="col-md-3">
                <div class="summary-card">
                    <h6>Pending Transactions</h6>
                    <h3><?php echo number_format($summary['pending_transactions']); ?></h3>
                </div>
            </div>
            <div class="col-md-3">
                <div class="summary-card">
                    <h6>Total Amount</h6>
                    <h3>₱<?php echo number_format($summary['total_amount'], 2); ?></h3>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Payment Transactions</h5>
            </div>
            <div class="card-body">
                <!-- Filters -->
                <form method="GET" class="row g-3 mb-4">
                    <div class="col-md-3">
                        <label class="form-label">Start Date</label>
                        <input type="date" class="form-control" name="start_date" value="<?php echo $start_date; ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">End Date</label>
                        <input type="date" class="form-control" name="end_date" value="<?php echo $end_date; ?>">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Payment Method</label>
                        <select class="form-select" name="payment_method">
                            <option value="">All Methods</option>
                            <?php foreach ($payment_methods as $method): ?>
                                <option value="<?php echo $method; ?>" <?php echo $payment_method === $method ? 'selected' : ''; ?>>
                                    <?php echo ucfirst($method); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Status</label>
                        <select class="form-select" name="status">
                            <option value="">All Statuses</option>
                            <?php foreach ($statuses as $s): ?>
                                <option value="<?php echo $s; ?>" <?php echo $status === $s ? 'selected' : ''; ?>>
                                    <?php echo ucfirst($s); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Search</label>
                        <input type="text" class="form-control" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search...">
                    </div>
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-filter me-2"></i>Apply Filters
                        </button>
                        <a href="manage_payment_transactions.php" class="btn btn-outline-secondary">
                            <i class="fas fa-times me-2"></i>Clear Filters
                        </a>
                        <a href="export_payment_transactions.php?<?php echo http_build_query($_GET); ?>" class="btn btn-success">
                            <i class="fas fa-file-export me-2"></i>Export to CSV
                        </a>
                    </div>
                </form>

                <!-- Transactions -->
                <?php foreach ($transactions as $transaction): ?>
                    <div class="transaction-card">
                        <div class="transaction-header">
                            <div>
                                <span class="payment-method method-<?php echo strtolower($transaction['payment_method']); ?>">
                                    <?php echo ucfirst($transaction['payment_method']); ?>
                                </span>
                                <span class="status status-<?php echo strtolower($transaction['status']); ?> ms-2">
                                    <?php echo ucfirst($transaction['status']); ?>
                                </span>
                            </div>
                            <small class="text-muted">
                                <?php echo date('M d, Y h:i A', strtotime($transaction['created_at'])); ?>
                            </small>
                        </div>
                        
                        <div class="transaction-content">
                            <p class="mb-1">
                                <strong>Amount:</strong> ₱<?php echo number_format($transaction['total_amount'], 2); ?>
                            </p>
                            <p class="mb-1">
                                <strong>Order #<?php echo $transaction['order_id']; ?></strong>
                            </p>
                            <p class="mb-1">
                                <strong>Customer:</strong> <?php echo htmlspecialchars($transaction['first_name'] . ' ' . $transaction['last_name']); ?>
                            </p>
                        </div>
                        
                        <div class="transaction-footer">
                            <div>
                                <strong>Transaction ID:</strong> <?php echo htmlspecialchars($transaction['transaction_id']); ?>
                            </div>
                            <?php if ($transaction['transaction_reference']): ?>
                                <div>
                                    <strong>Reference #:</strong> <?php echo htmlspecialchars($transaction['transaction_reference']); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>

                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                    <nav aria-label="Page navigation" class="mt-4">
                        <ul class="pagination justify-content-center">
                            <?php if ($current_page > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo $current_page - 1; ?>&start_date=<?php echo $start_date; ?>&end_date=<?php echo $end_date; ?>&payment_method=<?php echo $payment_method; ?>&status=<?php echo $status; ?>&search=<?php echo urlencode($search); ?>">
                                        <i class="fas fa-chevron-left"></i>
                                    </a>
                                </li>
                            <?php endif; ?>

                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                <li class="page-item <?php echo $i === $current_page ? 'active' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $i; ?>&start_date=<?php echo $start_date; ?>&end_date=<?php echo $end_date; ?>&payment_method=<?php echo $payment_method; ?>&status=<?php echo $status; ?>&search=<?php echo urlencode($search); ?>">
                                        <?php echo $i; ?>
                                    </a>
                                </li>
                            <?php endfor; ?>

                            <?php if ($current_page < $total_pages): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo $current_page + 1; ?>&start_date=<?php echo $start_date; ?>&end_date=<?php echo $end_date; ?>&payment_method=<?php echo $payment_method; ?>&status=<?php echo $status; ?>&search=<?php echo urlencode($search); ?>">
                                        <i class="fas fa-chevron-right"></i>
                                    </a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php include 'components/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
