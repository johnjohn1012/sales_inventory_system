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
                    // Check for overlapping shifts
                    $stmt = $conn->prepare("
                        SELECT COUNT(*) 
                        FROM staff_shifts ss1
                        JOIN shift_schedules s1 ON ss1.schedule_id = s1.schedule_id
                        JOIN shift_schedules s2 ON ? = s2.schedule_id
                        WHERE ss1.employee_id = ? 
                        AND ss1.shift_date = ?
                        AND (
                            (s1.start_time <= s2.end_time AND s1.end_time > s2.start_time) OR
                            (s1.start_time < s2.end_time AND s1.end_time >= s2.start_time) OR
                            (s1.start_time >= s2.start_time AND s1.end_time <= s2.end_time)
                        )
                    ");
                    $stmt->execute([
                        $_POST['schedule_id'],
                        $_POST['employee_id'],
                        $_POST['shift_date']
                    ]);
                    $count = $stmt->fetchColumn();

                    if ($count > 0) {
                        $error = "This employee already has a shift scheduled for this day and time";
                        break;
                    }

                    $stmt = $conn->prepare("
                        INSERT INTO staff_shifts (employee_id, schedule_id, shift_date)
                        VALUES (?, ?, ?)
                    ");
                    $stmt->execute([
                        $_POST['employee_id'],
                        $_POST['schedule_id'],
                        $_POST['shift_date']
                    ]);
                    $message = "Staff shift assigned successfully";
                    break;

                case 'delete':
                    $stmt = $conn->prepare("DELETE FROM staff_shifts WHERE staff_shift_id = ?");
                    $stmt->execute([$_POST['staff_shift_id']]);
                    $message = "Staff shift removed successfully";
                    break;
            }
        }
    } catch (PDOException $e) {
        $error = "Database error: " . $e->getMessage();
    }
}

// Get all employees
$stmt = $conn->query("
    SELECT e.*, u.username, u.role
    FROM employees e
    LEFT JOIN users u ON e.employee_id = u.employee_id
    WHERE e.status = 'ACTIVE'
    ORDER BY e.last_name, e.first_name
");
$employees = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get all shift schedules
$stmt = $conn->query("
    SELECT * FROM shift_schedules 
    ORDER BY start_time
");
$schedules = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get all staff shifts with employee and schedule details
$stmt = $conn->query("
    SELECT ss.*, 
           e.first_name, e.last_name,
           s.name as schedule_name, s.start_time, s.end_time
    FROM staff_shifts ss
    JOIN employees e ON ss.employee_id = e.employee_id
    JOIN shift_schedules s ON ss.schedule_id = s.schedule_id
    ORDER BY ss.shift_date, s.start_time
");
$staff_shifts = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get current week's dates
$current_week = [];
for ($i = 0; $i < 7; $i++) {
    $current_week[] = date('Y-m-d', strtotime("+$i days"));
}

// Days of the week for dropdown
$days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
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

        .shift-card {
            background: white;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 10px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }

        .shift-card h6 {
            margin: 0;
            color: var(--primary-color);
        }

        .shift-card p {
            margin: 5px 0;
            color: var(--secondary-color);
            font-size: 0.9rem;
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

        <div class="row">
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Assign New Shift</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <input type="hidden" name="action" value="add">
                            
                            <div class="mb-3">
                                <label class="form-label">Employee</label>
                                <select class="form-control" name="employee_id" required>
                                    <option value="">Select Employee</option>
                                    <?php foreach ($employees as $employee): ?>
                                        <option value="<?php echo $employee['employee_id']; ?>">
                                            <?php echo htmlspecialchars($employee['first_name'] . ' ' . $employee['last_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Shift Schedule</label>
                                <select class="form-control" name="schedule_id" required>
                                    <option value="">Select Shift</option>
                                    <?php foreach ($schedules as $schedule): ?>
                                        <option value="<?php echo $schedule['schedule_id']; ?>">
                                            <?php echo htmlspecialchars($schedule['name'] . ' (' . 
                                                date('h:i A', strtotime($schedule['start_time'])) . ' - ' . 
                                                date('h:i A', strtotime($schedule['end_time'])) . ')'); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Shift Date</label>
                                <input type="date" class="form-control" name="shift_date" required>
                            </div>
                            
                            <button type="submit" class="btn btn-primary w-100">Assign Shift</button>
                        </form>
                    </div>
                </div>
            </div>
            
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Current Shift Assignments</h5>
                    </div>
                    <div class="card-body">
                        <?php foreach ($current_week as $date): ?>
                            <h6 class="mt-4 mb-3"><?php echo date('l, F j', strtotime($date)); ?></h6>
                            <?php
                            $day_shifts = array_filter($staff_shifts, function($shift) use ($date) {
                                return $shift['shift_date'] === $date;
                            });
                            
                            if (empty($day_shifts)): ?>
                                <p class="text-muted">No shifts assigned</p>
                            <?php else: ?>
                                <?php foreach ($day_shifts as $shift): ?>
                                    <div class="shift-card">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <h6><?php echo htmlspecialchars($shift['first_name'] . ' ' . $shift['last_name']); ?></h6>
                                                <p class="mb-0">
                                                    <?php echo htmlspecialchars($shift['schedule_name']); ?> |
                                                    <?php echo date('h:i A', strtotime($shift['start_time'])); ?> - 
                                                    <?php echo date('h:i A', strtotime($shift['end_time'])); ?>
                                                </p>
                                            </div>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="staff_shift_id" value="<?php echo $shift['staff_shift_id']; ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-danger" 
                                                        onclick="return confirm('Are you sure you want to remove this shift assignment?')">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php include 'components/footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
