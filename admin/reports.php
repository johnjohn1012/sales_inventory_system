<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in and has admin role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'ADMIN') {
    header('Location: ../login.php');
    exit();
}

// Get date range from query parameters or use default (last 7 days)
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d', strtotime('-6 days'));

try {
    // Daily sales for the selected period
    $stmt = $conn->prepare("
        SELECT DATE(created_at) as date,
               COUNT(*) as total_orders,
               SUM(total_amount) as total_sales
        FROM orders
        WHERE DATE(created_at) BETWEEN ? AND ?
        GROUP BY DATE(created_at)
        ORDER BY date
    ");
    $stmt->execute([$start_date, $end_date]);
    $daily_sales = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Top selling products
    $stmt = $conn->prepare("
        SELECT p.name,
               COUNT(oi.order_item_id) as quantity_sold,
               SUM(oi.quantity * oi.unit_price) as total_sales
        FROM order_items oi
        JOIN products p ON oi.product_id = p.product_id
        JOIN orders o ON oi.order_id = o.order_id
        WHERE DATE(o.created_at) BETWEEN ? AND ?
        GROUP BY p.product_id
        ORDER BY quantity_sold DESC
        LIMIT 5
    ");
    $stmt->execute([$start_date, $end_date]);
    $top_products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Sales by category
    $stmt = $conn->prepare("
        SELECT c.name,
               COUNT(oi.order_item_id) as quantity_sold,
               SUM(oi.quantity * oi.unit_price) as total_sales
        FROM order_items oi
        JOIN products p ON oi.product_id = p.product_id
        JOIN categories c ON p.category_id = c.category_id
        JOIN orders o ON oi.order_id = o.order_id
        WHERE DATE(o.created_at) BETWEEN ? AND ?
        GROUP BY c.category_id
        ORDER BY total_sales DESC
    ");
    $stmt->execute([$start_date, $end_date]);
    $category_sales = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Overall statistics
    $stmt = $conn->prepare("
        SELECT COUNT(DISTINCT o.order_id) as total_orders,
               SUM(o.total_amount) as total_sales,
               AVG(o.total_amount) as average_order_value,
               COUNT(DISTINCT o.table_id) as tables_served
        FROM orders o
        WHERE DATE(o.created_at) BETWEEN ? AND ?
    ");
    $stmt->execute([$start_date, $end_date]);
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $error = "Database error: " . $e->getMessage();
}

// Prepare data for charts
$dates = [];
$sales = [];
$orders = [];
foreach ($daily_sales as $day) {
    $dates[] = date('M j', strtotime($day['date']));
    $sales[] = $day['total_sales'];
    $orders[] = $day['total_orders'];
}

$category_names = [];
$category_amounts = [];
foreach ($category_sales as $category) {
    $category_names[] = $category['name'];
    $category_amounts[] = $category['total_sales'];
}
?>


    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
       

        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }

        .card-header {
            background-color: white;
            border-bottom: 1px solid #eee;
            padding: 1.5rem;
            border-radius: 15px 15px 0 0 !important;
        }

        .stat-card {
            padding: 1.5rem;
            text-align: center;
            transition: transform 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            margin: 0 auto 15px;
        }

        .stat-orders { background: #e3fcef; color: #1cc88a; }
        .stat-sales { background: #e8f4ff; color: #4e73df; }
        .stat-average { background: #fff4e5; color: #f6c23e; }
        .stat-tables { background: #ffe9e9; color: #e74a3b; }

        .stat-title {
            color: #858796;
            font-size: 0.9rem;
            margin-bottom: 5px;
        }

        .stat-value {
            font-size: 1.8rem;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 0;
        }

        .table th {
            font-weight: 500;
            color: #4e73df;
        }

        .progress {
            height: 8px;
            border-radius: 4px;
        }

        .date-filter {
            background: white;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .form-control {
            border-radius: 8px;
            padding: 0.75rem 1rem;
            border: 1px solid #e0e0e0;
        }

        .form-control:focus {
            border-color: #4e73df;
            box-shadow: 0 0 0 0.2rem rgba(78,115,223,0.25);
        }
    </style>

    <?php include 'components/header.php'; ?>
    <?php include 'components/navbar.php'; ?>

   

    <div class="container mt-4">
        <div class="date-filter">
            <form method="GET" class="row g-3 align-items-center">
                <div class="col-md-4">
                    <label class="form-label">Start Date</label>
                    <input type="date" class="form-control" name="start_date" value="<?php echo $start_date; ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">End Date</label>
                    <input type="date" class="form-control" name="end_date" value="<?php echo $end_date; ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">&nbsp;</label>
                    <button type="submit" class="btn btn-primary d-block">
                        <i class="fas fa-filter me-2"></i>Filter
                    </button>
                </div>
            </form>
        </div>

        <div class="row">
            <div class="col-md-6 col-lg-3">
                <div class="card">
                    <div class="stat-card">
                        <div class="stat-icon stat-orders">
                            <i class="fas fa-shopping-cart"></i>
                        </div>
                        <div class="stat-title">Total Orders</div>
                        <div class="stat-value"><?php echo number_format($stats['total_orders']); ?></div>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="card">
                    <div class="stat-card">
                        <div class="stat-icon stat-sales">
                            <i class="fas fa-peso-sign"></i>
                        </div>
                        <div class="stat-title">Total Sales</div>
                        <div class="stat-value">₱<?php echo number_format($stats['total_sales'], 2); ?></div>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="card">
                    <div class="stat-card">
                        <div class="stat-icon stat-average">
                            <i class="fas fa-receipt"></i>
                        </div>
                        <div class="stat-title">Average Order Value</div>
                        <div class="stat-value">₱<?php echo number_format($stats['average_order_value'], 2); ?></div>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="card">
                    <div class="stat-card">
                        <div class="stat-icon stat-tables">
                            <i class="fas fa-chair"></i>
                        </div>
                        <div class="stat-title">Tables Served</div>
                        <div class="stat-value"><?php echo number_format($stats['tables_served']); ?></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mt-4">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Sales Overview</h5>
                    </div>
                    <div class="card-body">
                        <canvas id="salesChart"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Sales by Category</h5>
                    </div>
                    <div class="card-body">
                        <canvas id="categoryChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mt-4">
            <div class="col-lg-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Top Selling Products</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Product</th>
                                        <th>Quantity</th>
                                        <th>Sales</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($top_products as $product): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($product['name']); ?></td>
                                        <td><?php echo number_format($product['quantity_sold']); ?></td>
                                        <td>₱<?php echo number_format($product['total_sales'], 2); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Category Performance</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Category</th>
                                        <th>Items Sold</th>
                                        <th>Sales</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($category_sales as $category): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($category['name']); ?></td>
                                        <td><?php echo number_format($category['quantity_sold']); ?></td>
                                        <td>₱<?php echo number_format($category['total_sales'], 2); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php include 'components/footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Sales Overview Chart
        const salesCtx = document.getElementById('salesChart').getContext('2d');
        new Chart(salesCtx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode($dates); ?>,
                datasets: [
                    {
                        label: 'Sales (₱)',
                        data: <?php echo json_encode($sales); ?>,
                        borderColor: '#4e73df',
                        backgroundColor: 'rgba(78,115,223,0.1)',
                        tension: 0.4,
                        fill: true
                    },
                    {
                        label: 'Orders',
                        data: <?php echo json_encode($orders); ?>,
                        borderColor: '#1cc88a',
                        backgroundColor: 'rgba(28,200,138,0.1)',
                        tension: 0.4,
                        fill: true
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top'
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            drawBorder: false
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });

        // Category Sales Chart
        const categoryCtx = document.getElementById('categoryChart').getContext('2d');
        new Chart(categoryCtx, {
            type: 'doughnut',
            data: {
                labels: <?php echo json_encode($category_names); ?>,
                datasets: [{
                    data: <?php echo json_encode($category_amounts); ?>,
                    backgroundColor: [
                        '#4e73df',
                        '#1cc88a',
                        '#36b9cc',
                        '#f6c23e',
                        '#e74a3b'
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
    </script>
