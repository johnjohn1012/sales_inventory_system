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
                case 'update_status':
                    $stmt = $conn->prepare("
                        UPDATE order_modifications 
                        SET status = ?
                        WHERE modification_id = ?
                    ");
                    $stmt->execute([
                        $_POST['status'],
                        $_POST['modification_id']
                    ]);
                    $message = "Modification status updated successfully";
                    break;

                case 'delete':
                    $stmt = $conn->prepare("DELETE FROM order_modifications WHERE modification_id = ?");
                    $stmt->execute([$_POST['modification_id']]);
                    $message = "Modification deleted successfully";
                    break;
            }
        }
    } catch (PDOException $e) {
        $error = "Database error: " . $e->getMessage();
    }
}

// Get all order modifications with order and customer details
$stmt = $conn->query("
    SELECT m.*, o.order_number, o.order_date,
           c.first_name, c.last_name, c.email,
           p.name as product_name,
           e.first_name as employee_first_name,
           e.last_name as employee_last_name
    FROM order_modifications m
    JOIN orders o ON m.order_id = o.order_id
    JOIN customers c ON o.customer_id = c.customer_id
    JOIN products p ON m.product_id = p.product_id
    JOIN employees e ON m.employee_id = e.employee_id
    ORDER BY m.created_at DESC
");
$modifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Status options for dropdown
$statuses = ['PENDING', 'APPROVED', 'REJECTED', 'COMPLETED'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Order Modifications - HarahQR Sales</title>
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

        .modal-content {
            border-radius: 15px;
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

        .status-badge {
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 0.85rem;
        }

        .status-pending {
            background: #fff3cd;
            color: #856404;
        }

        .status-approved {
            background: #d4edda;
            color: #155724;
        }

        .status-rejected {
            background: #fce3e3;
            color: #e74a3b;
        }

        .status-completed {
            background: #e3fcef;
            color: #1cc88a;
        }

        .modification-card {
            background: white;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 15px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }

        .modification-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }

        .modification-content {
            color: var(--secondary-color);
            margin-bottom: 10px;
        }

        .modification-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.9rem;
            color: var(--secondary-color);
        }

        .price-change {
            font-weight: 600;
        }

        .price-increase {
            color: #e74a3b;
        }

        .price-decrease {
            color: #1cc88a;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-qrcode me-2"></i>HarahQR Sales
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="manage_employees.php">Employees</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="manage_shifts.php">Shifts</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="manage_staff_shifts.php">Staff Shifts</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="manage_products.php">Products</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="manage_tables.php">Tables</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="manage_categories.php">Categories</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../logout.php">Logout</a>
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
            <div class="card-header">
                <h5 class="mb-0">Order Modifications</h5>
            </div>
            <div class="card-body">
                <?php foreach ($modifications as $mod): ?>
                    <div class="modification-card">
                        <div class="modification-header">
                            <div>
                                <h6 class="mb-0">
                                    Order #<?php echo $mod['order_number']; ?>
                                </h6>
                                <small class="text-muted">
                                    <?php echo date('M d, Y', strtotime($mod['order_date'])); ?>
                                </small>
                            </div>
                            <div>
                                <span class="status-badge status-<?php echo strtolower($mod['status']); ?>">
                                    <?php echo $mod['status']; ?>
                                </span>
                            </div>
                        </div>
                        
                        <div class="modification-content">
                            <p class="mb-1">
                                <strong>Customer:</strong> 
                                <?php echo htmlspecialchars($mod['first_name'] . ' ' . $mod['last_name']); ?>
                            </p>
                            <p class="mb-1">
                                <strong>Product:</strong> 
                                <?php echo htmlspecialchars($mod['product_name']); ?>
                            </p>
                            <p class="mb-1">
                                <strong>Requested by:</strong> 
                                <?php echo htmlspecialchars($mod['employee_first_name'] . ' ' . $mod['employee_last_name']); ?>
                            </p>
                            <p class="mb-1">
                                <strong>Modification Type:</strong> 
                                <?php echo $mod['modification_type']; ?>
                            </p>
                            <p class="mb-1">
                                <strong>Price Change:</strong> 
                                <span class="price-change <?php echo $mod['price_change'] > 0 ? 'price-increase' : 'price-decrease'; ?>">
                                    <?php echo $mod['price_change'] > 0 ? '+' : ''; ?>
                                    ₱<?php echo number_format($mod['price_change'], 2); ?>
                                </span>
                            </p>
                            <p class="mb-0">
                                <strong>Reason:</strong> 
                                <?php echo nl2br(htmlspecialchars($mod['reason'])); ?>
                            </p>
                        </div>
                        
                        <div class="modification-footer">
                            <div>
                                <i class="fas fa-envelope me-1"></i>
                                <?php echo htmlspecialchars($mod['email']); ?>
                            </div>
                            <div>
                                <button type="button" class="btn btn-sm btn-outline-primary me-1" 
                                        onclick="updateStatus(<?php echo $mod['modification_id']; ?>, '<?php echo $mod['status']; ?>')">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-danger" 
                                        onclick="deleteModification(<?php echo $mod['modification_id']; ?>)">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Update Status Modal -->
    <div class="modal fade" id="updateStatusModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Update Modification Status</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="update_status">
                        <input type="hidden" name="modification_id" id="update_modification_id">
                        
                        <div class="mb-3">
                            <label class="form-label">Status</label>
                            <select class="form-control" name="status" id="update_status" required>
                                <?php foreach ($statuses as $status): ?>
                                    <option value="<?php echo $status; ?>"><?php echo $status; ?></option>
                                <?php endforeach; ?>
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

    <!-- Delete Modification Modal -->
    <div class="modal fade" id="deleteModificationModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Delete Order Modification</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete this order modification? This action cannot be undone.</p>
                </div>
                <div class="modal-footer">
                    <form method="POST">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="modification_id" id="delete_modification_id">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger">Delete Modification</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <?php include 'components/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function updateStatus(modificationId, currentStatus) {
            document.getElementById('update_modification_id').value = modificationId;
            document.getElementById('update_status').value = currentStatus;
            new bootstrap.Modal(document.getElementById('updateStatusModal')).show();
        }

        function deleteModification(modificationId) {
            document.getElementById('delete_modification_id').value = modificationId;
            new bootstrap.Modal(document.getElementById('deleteModificationModal')).show();
        }
    </script>
</body>
</html> 