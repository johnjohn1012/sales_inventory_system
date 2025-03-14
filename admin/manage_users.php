<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in and has admin role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'ADMIN') {
    header('Location: ../login.php');
    exit();
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['action'])) {
            switch ($_POST['action']) {
                case 'add':
                    // Check if username already exists
                    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM users WHERE username = ?");
                    $stmt->execute([$_POST['username']]);
                    $result = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if ($result['count'] > 0) {
                        $_SESSION['error'] = "Username already exists!";
                    } else {
                        $hashed_password = password_hash($_POST['password'], PASSWORD_DEFAULT);
                        $stmt = $conn->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, ?)");
                        $stmt->execute([
                            $_POST['username'],
                            $hashed_password,
                            $_POST['role']
                        ]);
                        $_SESSION['success'] = "User added successfully!";
                    }
                    break;

                case 'edit':
                    $updates = ["role = ?"];
                    $params = [$_POST['role']];

                    // Only update password if a new one is provided
                    if (!empty($_POST['password'])) {
                        $updates[] = "password = ?";
                        $params[] = password_hash($_POST['password'], PASSWORD_DEFAULT);
                    }

                    // Check if username is being changed
                    if ($_POST['original_username'] !== $_POST['username']) {
                        // Check if new username already exists
                        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM users WHERE username = ?");
                        $stmt->execute([$_POST['username']]);
                        $result = $stmt->fetch(PDO::FETCH_ASSOC);
                        
                        if ($result['count'] > 0) {
                            $_SESSION['error'] = "Username already exists!";
                            break;
                        }
                        $updates[] = "username = ?";
                        $params[] = $_POST['username'];
                    }

                    $params[] = $_POST['user_id']; // For WHERE clause
                    $sql = "UPDATE users SET " . implode(", ", $updates) . " WHERE user_id = ?";
                    
                    $stmt = $conn->prepare($sql);
                    $stmt->execute($params);
                    $_SESSION['success'] = "User updated successfully!";
                    break;

                case 'delete':
                    // Prevent deleting self
                    if ($_POST['user_id'] == $_SESSION['user_id']) {
                        $_SESSION['error'] = "You cannot delete your own account!";
                        break;
                    }

                    $stmt = $conn->prepare("DELETE FROM users WHERE user_id = ?");
                    $stmt->execute([$_POST['user_id']]);
                    $_SESSION['success'] = "User deleted successfully!";
                    break;
            }
        }
    } catch (PDOException $e) {
        $_SESSION['error'] = "Error: " . $e->getMessage();
    }
    
    header('Location: manage_users.php');
    exit();
}

// Get all users
try {
    $stmt = $conn->query("SELECT * FROM users ORDER BY role, username");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Database error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users - HarahQR Sales</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
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

        .nav-link {
            color: white !important;
            opacity: 0.8;
            transition: all 0.3s ease;
        }

        .nav-link:hover {
            opacity: 1;
        }

        .nav-link.active {
            opacity: 1;
            font-weight: 500;
        }

        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .card-header {
            background-color: white;
            border-bottom: 1px solid #eee;
            padding: 1.5rem;
            border-radius: 15px 15px 0 0 !important;
        }

        .user-card {
            transition: transform 0.3s ease;
        }

        .user-card:hover {
            transform: translateY(-5px);
        }

        .btn-icon {
            width: 35px;
            height: 35px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        .btn-icon:hover {
            transform: translateY(-2px);
        }

        .modal-content {
            border-radius: 15px;
            border: none;
        }

        .modal-header {
            border-bottom: 1px solid #eee;
            padding: 1.5rem;
        }

        .modal-footer {
            border-top: 1px solid #eee;
            padding: 1.5rem;
        }

        .form-control, .form-select {
            border-radius: 8px;
            padding: 0.75rem 1rem;
            border: 1px solid #e0e0e0;
        }

        .form-control:focus, .form-select:focus {
            border-color: #4e73df;
            box-shadow: 0 0 0 0.2rem rgba(78,115,223,0.25);
        }

        .alert {
            border-radius: 10px;
            border: none;
        }

        .badge-admin { background-color: #4e73df !important; }
        .badge-waiter { background-color: #1cc88a !important; }
        .badge-kitchen { background-color: #f6c23e !important; }
        .badge-cashier { background-color: #e74a3b !important; }

        .badge {
            padding: 0.5em 1em;
            border-radius: 6px;
            font-weight: 500;
            color: white !important;
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
                        <a class="nav-link active" href="manage_users.php">
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
                        <a class="nav-link" href="manage_notifications.php">
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
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i>
                <?php 
                    echo $_SESSION['success'];
                    unset($_SESSION['success']);
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle me-2"></i>
                <?php 
                    echo $_SESSION['error'];
                    unset($_SESSION['error']);
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Manage Users</h5>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addUserModal">
                    <i class="fas fa-plus me-2"></i>Add New User
                </button>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Username</th>
                                <th>Role</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                            <tr class="user-card">
                                <td class="align-middle"><?php echo htmlspecialchars($user['username']); ?></td>
                                <td class="align-middle">
                                    <span class="badge badge-<?php echo strtolower($user['role']); ?>">
                                        <?php echo ucfirst(strtolower($user['role'])); ?>
                                    </span>
                                </td>
                                <td class="align-middle">
                                    <button class="btn btn-icon btn-warning me-2" 
                                            onclick="editUser(<?php echo htmlspecialchars(json_encode($user)); ?>)">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <?php if ($user['user_id'] != $_SESSION['user_id']): ?>
                                    <button class="btn btn-icon btn-danger"
                                            onclick="deleteUser(<?php echo $user['user_id']; ?>, '<?php echo htmlspecialchars($user['username']); ?>')">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Add User Modal -->
    <div class="modal fade" id="addUserModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="" method="POST">
                    <input type="hidden" name="action" value="add">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Username</label>
                            <input type="text" class="form-control" name="username" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Password</label>
                            <input type="password" class="form-control" name="password" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Role</label>
                            <select class="form-select" name="role" required>
                                <option value="">Select Role</option>
                                <option value="ADMIN">Admin</option>
                                <option value="WAITER">Waiter</option>
                                <option value="KITCHEN">Kitchen Staff</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Add User</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit User Modal -->
    <div class="modal fade" id="editUserModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="" method="POST">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="user_id" id="edit_user_id">
                    <input type="hidden" name="original_username" id="edit_original_username">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Username</label>
                            <input type="text" class="form-control" name="username" id="edit_username" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Password</label>
                            <input type="password" class="form-control" name="password">
                            <small class="text-muted">Leave empty to keep current password</small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Role</label>
                            <select class="form-select" name="role" id="edit_role" required>
                                <option value="">Select Role</option>
                                <option value="ADMIN">Admin</option>
                                <option value="WAITER">Waiter</option>
                                <option value="KITCHEN">Kitchen Staff</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Update User</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete User Modal -->
    <div class="modal fade" id="deleteUserModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Delete User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="" method="POST">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="user_id" id="delete_user_id">
                    <div class="modal-body">
                        <p>Are you sure you want to delete <strong id="delete_user_name"></strong>?</p>
                        <p class="text-danger mb-0">This action cannot be undone.</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger">Delete User</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php include 'components/footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function editUser(user) {
            document.getElementById('edit_user_id').value = user.user_id;
            document.getElementById('edit_username').value = user.username;
            document.getElementById('edit_original_username').value = user.username;
            document.getElementById('edit_role').value = user.role;

            const editModal = new bootstrap.Modal(document.getElementById('editUserModal'));
            editModal.show();
        }

        function deleteUser(userId, userName) {
            document.getElementById('delete_user_id').value = userId;
            document.getElementById('delete_user_name').textContent = userName;

            const deleteModal = new bootstrap.Modal(document.getElementById('deleteUserModal'));
            deleteModal.show();
        }
    </script>
</body>
</html> 