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

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['action'])) {
            switch ($_POST['action']) {
                case 'mark_read':
                    $stmt = $conn->prepare("
                        UPDATE notifications 
                        SET is_read = 1
                        WHERE notification_id = ?
                    ");
                    $stmt->execute([$_POST['notification_id']]);
                    $message = "Notification marked as read";
                    break;

                case 'mark_all_read':
                    $stmt = $conn->prepare("
                        UPDATE notifications 
                        SET is_read = 1
                        WHERE is_read = 0
                    ");
                    $stmt->execute();
                    $message = "All notifications marked as read";
                    break;

                case 'delete':
                    $stmt = $conn->prepare("DELETE FROM notifications WHERE notification_id = ?");
                    $stmt->execute([$_POST['notification_id']]);
                    $message = "Notification deleted successfully";
                    break;
            }
        }
    } catch (PDOException $e) {
        $error = "Database error: " . $e->getMessage();
    }
}

// Get notifications
$query = "
    SELECT n.*, o.order_id, o.total_amount, o.status as order_status,
           t.table_number, c.first_name as customer_first_name, c.last_name as customer_last_name
    FROM notifications n
    LEFT JOIN orders o ON n.order_id = o.order_id
    LEFT JOIN tables t ON o.table_id = t.table_id
    LEFT JOIN customers c ON o.customer_id = c.customer_id
    ORDER BY n.created_at DESC
";

$stmt = $conn->query($query);
$notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get unread count
$stmt = $conn->query("SELECT COUNT(*) FROM notifications WHERE is_read = 0");
$unread_count = $stmt->fetchColumn();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Notifications - HarahQR Sales</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #4e73df;
            --secondary-color: #858796;
            --success-color: #1cc88a;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f8f9fc;
        }

        .navbar {
            background: linear-gradient(135deg, #4e73df, #224abe);
            padding: 1rem;
        }

        .navbar-brand {
            font-weight: 600;
            color: white !important;
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

        .notification-card {
            background: white;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 15px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
            transition: all 0.3s ease;
        }

        .notification-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        }

        .notification-card.unread {
            border-left: 4px solid var(--primary-color);
        }

        .notification-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }

        .notification-content {
            color: var(--secondary-color);
            margin-bottom: 10px;
        }

        .notification-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.9rem;
            color: var(--secondary-color);
        }

        .notification-type {
            padding: 3px 8px;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: 500;
        }

        .type-order-ready {
            background: #e3fcef;
            color: #1cc88a;
        }

        .type-table-status {
            background: #cce5ff;
            color: #004085;
        }

        .type-payment {
            background: #fff3cd;
            color: #856404;
        }

        .badge {
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 0.85rem;
        }

        .badge-unread {
            background: var(--primary-color);
            color: white;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="#">
                <i class="fas fa-qrcode me-2"></i>
                Admin Dashboard
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="dashboard.php">
                            <i class="fas fa-home me-2"></i>Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="manage_products.php">
                            <i class="fas fa-box me-2"></i>Products
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="manage_categories.php">
                            <i class="fas fa-tags me-2"></i>Categories
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="manage_tables.php">
                            <i class="fas fa-chair me-2"></i>Tables
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="manage_users.php">
                            <i class="fas fa-users me-2"></i>Users
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="manage_employees.php">
                            <i class="fas fa-user-tie me-2"></i>Employees
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="manage_shifts.php">
                            <i class="fas fa-clock me-2"></i>Shifts
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="manage_staff_shifts.php">
                            <i class="fas fa-calendar-alt me-2"></i>Staff Shifts
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="manage_reservations.php">
                            <i class="fas fa-calendar-check me-2"></i>Reservations
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="manage_payment_transactions.php">
                            <i class="fas fa-money-bill-wave me-2"></i>Payments
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="manage_sales.php">
                            <i class="fas fa-chart-line me-2"></i>Sales
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="manage_feedback.php">
                            <i class="fas fa-comments me-2"></i>Feedback
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="manage_system_logs.php">
                            <i class="fas fa-history me-2"></i>System Logs
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="manage_notifications.php">
                            <i class="fas fa-bell me-2"></i>Notifications
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="reports.php">
                            <i class="fas fa-chart-bar me-2"></i>Reports
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../logout.php">
                            <i class="fas fa-sign-out-alt me-2"></i>Logout
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

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

        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">System Notifications</h5>
                <?php if ($unread_count > 0): ?>
                    <form method="POST" style="display: inline;">
                        <input type="hidden" name="action" value="mark_all_read">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-check-double me-2"></i>Mark All as Read
                        </button>
                    </form>
                <?php endif; ?>
            </div>
            <div class="card-body">
                <?php foreach ($notifications as $notification): ?>
                    <div class="notification-card <?php echo $notification['is_read'] ? '' : 'unread'; ?>">
                        <div class="notification-header">
                            <div>
                                <span class="notification-type type-<?php echo strtolower(str_replace('_', '-', $notification['type'])); ?>">
                                    <?php echo str_replace('_', ' ', $notification['type']); ?>
                                </span>
                                <?php if (!$notification['is_read']): ?>
                                    <span class="badge badge-unread ms-2">New</span>
                                <?php endif; ?>
                            </div>
                            <small class="text-muted">
                                <?php echo date('M d, Y h:i A', strtotime($notification['created_at'])); ?>
                            </small>
                        </div>
                        
                        <div class="notification-content">
                            <p class="mb-1">
                                <?php echo htmlspecialchars($notification['message']); ?>
                            </p>
                            <?php if ($notification['order_id']): ?>
                                <p class="mb-0">
                                    <strong>Order #<?php echo $notification['order_id']; ?></strong>
                                    <?php if ($notification['customer_first_name'] && $notification['customer_last_name']): ?>
                                        - <?php echo htmlspecialchars($notification['customer_first_name'] . ' ' . $notification['customer_last_name']); ?>
                                    <?php endif; ?>
                                </p>
                            <?php endif; ?>
                        </div>
                        
                        <div class="notification-footer">
                            <div>
                                <?php if (!$notification['is_read']): ?>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="action" value="mark_read">
                                        <input type="hidden" name="notification_id" value="<?php echo $notification['notification_id']; ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-check me-1"></i>Mark as Read
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </div>
                            <div>
                                <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this notification?');">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="notification_id" value="<?php echo $notification['notification_id']; ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <?php include 'components/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Check for new notifications every 30 seconds
        setInterval(function() {
            fetch('check_notifications.php')
                .then(response => response.json())
                .then(data => {
                    if (data.unread_count > <?php echo $unread_count; ?>) {
                        location.reload();
                    }
                });
        }, 30000);
    </script>
</body>
</html> 