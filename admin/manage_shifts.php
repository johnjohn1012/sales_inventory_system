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
                    $stmt = $conn->prepare("
                        INSERT INTO shift_schedules (name, start_time, end_time)
                        VALUES (?, ?, ?)
                    ");
                    $stmt->execute([
                        $_POST['name'],
                        $_POST['start_time'],
                        $_POST['end_time']
                    ]);
                    $message = "Shift schedule added successfully";
                    break;

                case 'edit':
                    $stmt = $conn->prepare("
                        UPDATE shift_schedules 
                        SET name = ?, start_time = ?, end_time = ?
                        WHERE schedule_id = ?
                    ");
                    $stmt->execute([
                        $_POST['name'],
                        $_POST['start_time'],
                        $_POST['end_time'],
                        $_POST['schedule_id']
                    ]);
                    $message = "Shift schedule updated successfully";
                    break;

                case 'delete':
                    // Check if shift is assigned to any staff
                    $stmt = $conn->prepare("SELECT COUNT(*) FROM staff_shifts WHERE schedule_id = ?");
                    $stmt->execute([$_POST['schedule_id']]);
                    $count = $stmt->fetchColumn();

                    if ($count > 0) {
                        $error = "Cannot delete shift schedule that is assigned to staff members";
                        break;
                    }

                    $stmt = $conn->prepare("DELETE FROM shift_schedules WHERE schedule_id = ?");
                    $stmt->execute([$_POST['schedule_id']]);
                    $message = "Shift schedule deleted successfully";
                    break;
            }
        }
    } catch (PDOException $e) {
        $error = "Database error: " . $e->getMessage();
    }
}

// Get all shift schedules
$stmt = $conn->query("
    SELECT s.*, 
           COUNT(DISTINCT ss.staff_shift_id) as assigned_count
    FROM shift_schedules s
    LEFT JOIN staff_shifts ss ON s.schedule_id = ss.schedule_id
    GROUP BY s.schedule_id
    ORDER BY s.start_time
");
$schedules = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
                <h5 class="mb-0">Manage Shift Schedules</h5>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addShiftModal">
                    <i class="fas fa-plus me-2"></i>Add Shift Schedule
                </button>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Start Time</th>
                                <th>End Time</th>
                                <th>Assigned Staff</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($schedules as $schedule): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($schedule['name']); ?></td>
                                <td><?php echo date('h:i A', strtotime($schedule['start_time'])); ?></td>
                                <td><?php echo date('h:i A', strtotime($schedule['end_time'])); ?></td>
                                <td><?php echo $schedule['assigned_count']; ?> staff member(s)</td>
                                <td>
                                    <button type="button" class="btn btn-sm btn-outline-primary me-1" 
                                            onclick="editShift(<?php echo htmlspecialchars(json_encode($schedule)); ?>)">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-danger" 
                                            onclick="deleteShift(<?php echo $schedule['schedule_id']; ?>)">
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

    <!-- Add Shift Modal -->
    <div class="modal fade" id="addShiftModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add Shift Schedule</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add">
                        
                        <div class="mb-3">
                            <label class="form-label">Shift Name</label>
                            <input type="text" class="form-control" name="name" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Start Time</label>
                            <input type="time" class="form-control" name="start_time" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">End Time</label>
                            <input type="time" class="form-control" name="end_time" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Add Shift Schedule</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Shift Modal -->
    <div class="modal fade" id="editShiftModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Shift Schedule</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="edit">
                        <input type="hidden" name="schedule_id" id="edit_schedule_id">
                        
                        <div class="mb-3">
                            <label class="form-label">Shift Name</label>
                            <input type="text" class="form-control" name="name" id="edit_name" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Start Time</label>
                            <input type="time" class="form-control" name="start_time" id="edit_start_time" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">End Time</label>
                            <input type="time" class="form-control" name="end_time" id="edit_end_time" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Update Shift Schedule</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete Shift Modal -->
    <div class="modal fade" id="deleteShiftModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Delete Shift Schedule</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete this shift schedule? This action cannot be undone.</p>
                </div>
                <div class="modal-footer">
                    <form method="POST">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="schedule_id" id="delete_schedule_id">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger">Delete Shift Schedule</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <?php include 'components/footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function editShift(schedule) {
            document.getElementById('edit_schedule_id').value = schedule.schedule_id;
            document.getElementById('edit_name').value = schedule.name;
            document.getElementById('edit_start_time').value = schedule.start_time;
            document.getElementById('edit_end_time').value = schedule.end_time;

            new bootstrap.Modal(document.getElementById('editShiftModal')).show();
        }

        function deleteShift(scheduleId) {
            document.getElementById('delete_schedule_id').value = scheduleId;
            new bootstrap.Modal(document.getElementById('deleteShiftModal')).show();
        }
    </script>
