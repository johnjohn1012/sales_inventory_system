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

// Get date range from request parameters
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d', strtotime('-30 days'));
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');

// Get sales data with pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;

// Get total count for pagination
$stmt = $conn->prepare("
    SELECT COUNT(*) 
    FROM sales 
    WHERE date BETWEEN :start_date AND :end_date
");
$stmt->execute([':start_date' => $start_date, ':end_date' => $end_date]);
$total_records = $stmt->fetchColumn();
$total_pages = ceil($total_records / $per_page);

// Get sales data
$query = "
    SELECT * FROM sales 
    WHERE date BETWEEN :start_date AND :end_date
    ORDER BY date DESC
";

// Add pagination to query
$query .= " LIMIT :limit OFFSET :offset";

// Prepare the query
$stmt = $conn->prepare($query);

// Bind all parameters
$stmt->bindValue(':start_date', $start_date);
$stmt->bindValue(':end_date', $end_date);
$stmt->bindValue(':limit', $per_page, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

// Execute the query
$stmt->execute();
$sales = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get summary statistics
$stmt = $conn->prepare("
    SELECT 
        SUM(total_revenue) as total_revenue,
        SUM(cash_revenue) as cash_revenue,
        SUM(gcash_revenue) as gcash_revenue,
        SUM(total_orders) as total_orders,
        SUM(cash_orders) as cash_orders,
        SUM(gcash_orders) as gcash_orders,
        AVG(total_revenue) as avg_revenue,
        AVG(total_orders) as avg_orders
    FROM sales 
    WHERE date BETWEEN :start_date AND :end_date
");
$stmt->execute([':start_date' => $start_date, ':end_date' => $end_date]);
$summary = $stmt->fetch(PDO::FETCH_ASSOC);

// Get daily sales trend
$stmt = $conn->prepare("
    SELECT date, total_revenue, total_orders
    FROM sales 
    WHERE date BETWEEN :start_date AND :end_date
    ORDER BY date ASC
");
$stmt->execute([':start_date' => $start_date, ':end_date' => $end_date]);
$daily_trend = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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

        .stat-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }

        .stat-card h6 {
            color: var(--secondary-color);
            margin-bottom: 10px;
        }

        .stat-card h3 {
            color: var(--primary-color);
            margin-bottom: 0;
        }

        .stat-card p {
            color: var(--secondary-color);
            margin: 0;
            font-size: 0.9rem;
        }

        .chart-container {
            position: relative;
            height: 300px;
            margin-bottom: 20px;
        }

        .pagination .page-link {
            border-radius: 10px;
            margin: 0 2px;
            color: var(--primary-color);
        }

        .pagination .page-item.active .page-link {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
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

        <!-- Date Range Filter -->
        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Start Date</label>
                        <input type="date" class="form-control" name="start_date" 
                               value="<?php echo $start_date; ?>" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">End Date</label>
                        <input type="date" class="form-control" name="end_date" 
                               value="<?php echo $end_date; ?>" required>
                    </div>
                    <div class="col-md-4 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-filter me-2"></i>Filter
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Summary Statistics -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="stat-card">
                    <h6>Total Revenue</h6>
                    <h3>₱<?php echo number_format($summary['total_revenue'], 2); ?></h3>
                    <p>Average: ₱<?php echo number_format($summary['avg_revenue'], 2); ?></p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <h6>Cash Revenue</h6>
                    <h3>₱<?php echo number_format($summary['cash_revenue'], 2); ?></h3>
                    <p><?php echo $summary['cash_orders']; ?> orders</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <h6>GCash Revenue</h6>
                    <h3>₱<?php echo number_format($summary['gcash_revenue'], 2); ?></h3>
                    <p><?php echo $summary['gcash_orders']; ?> orders</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <h6>Total Orders</h6>
                    <h3><?php echo $summary['total_orders']; ?></h3>
                    <p>Average: <?php echo round($summary['avg_orders'], 1); ?> per day</p>
                </div>
            </div>
        </div>

        <!-- Sales Trend Chart -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Daily Sales Trend</h5>
            </div>
            <div class="card-body">
                <div class="chart-container">
                    <canvas id="salesTrendChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Sales Table -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Sales Records</h5>
                <button type="button" class="btn btn-primary" onclick="exportSales()">
                    <i class="fas fa-download me-2"></i>Export
                </button>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Total Revenue</th>
                                <th>Cash Revenue</th>
                                <th>GCash Revenue</th>
                                <th>Total Orders</th>
                                <th>Cash Orders</th>
                                <th>GCash Orders</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($sales as $sale): ?>
                            <tr>
                                <td><?php echo date('M d, Y', strtotime($sale['date'])); ?></td>
                                <td>₱<?php echo number_format($sale['total_revenue'], 2); ?></td>
                                <td>₱<?php echo number_format($sale['cash_revenue'], 2); ?></td>
                                <td>₱<?php echo number_format($sale['gcash_revenue'], 2); ?></td>
                                <td><?php echo $sale['total_orders']; ?></td>
                                <td><?php echo $sale['cash_orders']; ?></td>
                                <td><?php echo $sale['gcash_orders']; ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                <nav aria-label="Page navigation" class="mt-4">
                    <ul class="pagination justify-content-center">
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $i; ?>&start_date=<?php echo $start_date; ?>&end_date=<?php echo $end_date; ?>">
                                <?php echo $i; ?>
                            </a>
                        </li>
                        <?php endfor; ?>
                    </ul>
                </nav>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php include 'components/footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Sales Trend Chart
        const ctx = document.getElementById('salesTrendChart').getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode(array_column($daily_trend, 'date')); ?>,
                datasets: [{
                    label: 'Revenue',
                    data: <?php echo json_encode(array_column($daily_trend, 'total_revenue')); ?>,
                    borderColor: '#4e73df',
                    tension: 0.1,
                    fill: false
                }, {
                    label: 'Orders',
                    data: <?php echo json_encode(array_column($daily_trend, 'total_orders')); ?>,
                    borderColor: '#1cc88a',
                    tension: 0.1,
                    fill: false
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });

        // Export functionality
        function exportSales() {
            window.location.href = `export_sales.php?start_date=<?php echo $start_date; ?>&end_date=<?php echo $end_date; ?>`;
        }
    </script>
