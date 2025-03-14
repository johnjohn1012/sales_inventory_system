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
                case 'add':
                    $conn->beginTransaction();
                    
                    // Insert employee
                    $stmt = $conn->prepare("
                        INSERT INTO employees (first_name, last_name, position, contact_number, email, address, hire_date, status)
                        VALUES (?, ?, ?, ?, ?, ?, ?, 'ACTIVE')
                    ");
                    $stmt->execute([
                        $_POST['first_name'],
                        $_POST['last_name'],
                        $_POST['position'],
                        $_POST['hire_date']
                    ]);
                    $employee_id = $conn->lastInsertId();

                    // Insert user account if username and password are provided
                    if (!empty($_POST['username']) && !empty($_POST['password'])) {
                        $stmt = $conn->prepare("
                            INSERT INTO users (employee_id, username, password, role)
                            VALUES (?, ?, ?, ?)
                        ");
                        $stmt->execute([
                            $employee_id,
                            $_POST['username'],
                            password_hash($_POST['password'], PASSWORD_DEFAULT),
                            $_POST['role']
                        ]);
                    }

                    $conn->commit();
                    $message = "Employee added successfully";
                    break;

                case 'edit':
                    $conn->beginTransaction();
                    
                    // Update employee
                    $stmt = $conn->prepare("
                        UPDATE employees 
                        SET first_name = ?, last_name = ?, position = ?, 
                            contact_number = ?, email = ?, address = ?, 
                            hire_date = ?, status = ?
                        WHERE employee_id = ?
                    ");
                    $stmt->execute([
                        $_POST['first_name'],
                        $_POST['last_name'],
                        $_POST['position'],
                        $_POST['contact_number'],
                        $_POST['email'],
                        $_POST['address'],
                        $_POST['hire_date'],
                        $_POST['status'],
                        $_POST['employee_id']
                    ]);

                    // Update user account if username is provided
                    if (!empty($_POST['username'])) {
                        $sql = "UPDATE users SET username = ?, role = ?";
                        $params = [$_POST['username'], $_POST['role']];

                        // Only update password if a new one is provided
                        if (!empty($_POST['password'])) {
                            $sql .= ", password = ?";
                            $params[] = password_hash($_POST['password'], PASSWORD_DEFAULT);
                        }

                        $sql .= " WHERE employee_id = ?";
                        $params[] = $_POST['employee_id'];

                        $stmt = $conn->prepare($sql);
                        $stmt->execute($params);
                    }

                    $conn->commit();
                    $message = "Employee updated successfully";
                    break;

                case 'delete':
                    // Check if employee has associated user account
                    $stmt = $conn->prepare("SELECT user_id FROM users WHERE employee_id = ?");
                    $stmt->execute([$_POST['employee_id']]);
                    $user = $stmt->fetch(PDO::FETCH_ASSOC);

                    $conn->beginTransaction();

                    // Delete user account if exists
                    if ($user) {
                        $stmt = $conn->prepare("DELETE FROM users WHERE employee_id = ?");
                        $stmt->execute([$_POST['employee_id']]);
                    }

                    // Delete employee
                    $stmt = $conn->prepare("DELETE FROM employees WHERE employee_id = ?");
                    $stmt->execute([$_POST['employee_id']]);

                    $conn->commit();
                    $message = "Employee deleted successfully";
                    break;
            }
        }
    } catch (PDOException $e) {
        if ($conn->inTransaction()) {
            $conn->rollBack();
        }
        $error = "Database error: " . $e->getMessage();
    }
}

// Get all employees with their user accounts
$stmt = $conn->query("
    SELECT e.*, u.username, u.role
    FROM employees e
    LEFT JOIN users u ON e.employee_id = u.employee_id
    ORDER BY e.last_name, e.first_name
");
$employees = $stmt->fetchAll(PDO::FETCH_ASSOC);
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

        .status-badge {
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 0.85rem;
        }

        .status-active {
            background: #e3fcef;
            color: #1cc88a;
        }

        .status-inactive {
            background: #fce3e3;
            color: #e74a3b;
        }

        .status-on-leave {
            background: #fff3cd;
            color: #856404;
        }

        .status-terminated {
            background: #d4edda;
            color: #155724;
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

        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Manage Employees</h5>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addEmployeeModal">
                    <i class="fas fa-plus me-2"></i>Add Employee
                </button>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Position</th>
                                <th>Contact</th>
                                <th>Status</th>
                                <th>Username</th>
                                <th>Role</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($employees as $employee): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($employee['first_name'] . ' ' . $employee['last_name']); ?></td>
                                <td><?php echo htmlspecialchars($employee['position']); ?></td>
                                <td>
                                    <?php if ($employee['contact_number']): ?>
                                        <i class="fas fa-phone me-1"></i><?php echo htmlspecialchars($employee['contact_number']); ?>
                                    <?php endif; ?>
                                    <?php if ($employee['email']): ?>
                                        <br><i class="fas fa-envelope me-1"></i><?php echo htmlspecialchars($employee['email']); ?>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="status-badge status-<?php echo strtolower($employee['status']); ?>">
                                        <?php echo $employee['status']; ?>
                                    </span>
                                </td>
                                <td><?php echo $employee['username'] ? htmlspecialchars($employee['username']) : '-'; ?></td>
                                <td><?php echo $employee['role'] ? htmlspecialchars($employee['role']) : '-'; ?></td>
                                <td>
                                    <button type="button" class="btn btn-sm btn-outline-primary me-1" 
                                            onclick="editEmployee(<?php echo htmlspecialchars(json_encode($employee)); ?>)">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-danger" 
                                            onclick="deleteEmployee(<?php echo $employee['employee_id']; ?>)">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Employee Modal -->
    <div class="modal fade" id="addEmployeeModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add Employee</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add">
                        
                        <div class="mb-3">
                            <label class="form-label">First Name</label>
                            <input type="text" class="form-control" name="first_name" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Last Name</label>
                            <input type="text" class="form-control" name="last_name" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Position</label>
                            <input type="text" class="form-control" name="position" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Contact Number</label>
                            <input type="tel" class="form-control" name="contact_number">
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" name="email">
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Address</label>
                            <textarea class="form-control" name="address" rows="3"></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Hire Date</label>
                            <input type="date" class="form-control" name="hire_date" required>
                        </div>

                        <hr>

                        <div class="mb-3">
                            <label class="form-label">Username (Optional)</label>
                            <input type="text" class="form-control" name="username">
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Password (Optional)</label>
                            <input type="password" class="form-control" name="password">
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Role (Optional)</label>
                            <select class="form-control" name="role">
                                <option value="">Select Role</option>
                                <option value="ADMIN">Admin</option>
                                <option value="CASHIER">Cashier</option>
                                <option value="KITCHEN">Kitchen</option>
                                <option value="WAITER">Waiter</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Add Employee</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Employee Modal -->
    <div class="modal fade" id="editEmployeeModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Employee</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="edit">
                        <input type="hidden" name="employee_id" id="edit_employee_id">
                        
                        <div class="mb-3">
                            <label class="form-label">First Name</label>
                            <input type="text" class="form-control" name="first_name" id="edit_first_name" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Last Name</label>
                            <input type="text" class="form-control" name="last_name" id="edit_last_name" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Position</label>
                            <input type="text" class="form-control" name="position" id="edit_position" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Contact Number</label>
                            <input type="tel" class="form-control" name="contact_number" id="edit_contact_number">
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" name="email" id="edit_email">
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Address</label>
                            <textarea class="form-control" name="address" id="edit_address" rows="3"></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Hire Date</label>
                            <input type="date" class="form-control" name="hire_date" id="edit_hire_date" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Status</label>
                            <select class="form-control" name="status" id="edit_status" required>
                                <option value="ACTIVE">Active</option>
                                <option value="INACTIVE">Inactive</option>
                                <option value="ON_LEAVE">On Leave</option>
                                <option value="TERMINATED">Terminated</option>
                            </select>
                        </div>

                        <hr>

                        <div class="mb-3">
                            <label class="form-label">Username (Optional)</label>
                            <input type="text" class="form-control" name="username" id="edit_username">
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">New Password (Optional)</label>
                            <input type="password" class="form-control" name="password">
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Role (Optional)</label>
                            <select class="form-control" name="role" id="edit_role">
                                <option value="">Select Role</option>
                                <option value="ADMIN">Admin</option>
                                <option value="CASHIER">Cashier</option>
                                <option value="KITCHEN">Kitchen</option>
                                <option value="WAITER">Waiter</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Update Employee</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete Employee Modal -->
    <div class="modal fade" id="deleteEmployeeModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Delete Employee</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete this employee? This action cannot be undone.</p>
                </div>
                <div class="modal-footer">
                    <form method="POST">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="employee_id" id="delete_employee_id">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger">Delete Employee</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <?php include 'components/footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function editEmployee(employee) {
            document.getElementById('edit_employee_id').value = employee.employee_id;
            document.getElementById('edit_first_name').value = employee.first_name;
            document.getElementById('edit_last_name').value = employee.last_name;
            document.getElementById('edit_position').value = employee.position;
            document.getElementById('edit_contact_number').value = employee.contact_number || '';
            document.getElementById('edit_email').value = employee.email || '';
            document.getElementById('edit_address').value = employee.address || '';
            document.getElementById('edit_hire_date').value = employee.hire_date;
            document.getElementById('edit_status').value = employee.status;
            document.getElementById('edit_username').value = employee.username || '';
            document.getElementById('edit_role').value = employee.role || '';

            new bootstrap.Modal(document.getElementById('editEmployeeModal')).show();
        }

        function deleteEmployee(employeeId) {
            document.getElementById('delete_employee_id').value = employeeId;
            new bootstrap.Modal(document.getElementById('deleteEmployeeModal')).show();
        }
    </script>
