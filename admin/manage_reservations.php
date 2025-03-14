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
                    // Check if table is available for the requested time
                    $stmt = $conn->prepare("
                        SELECT COUNT(*) FROM reservations 
                        WHERE table_id = ? 
                        AND reservation_date = ?
                        AND (
                            (start_time <= ? AND end_time > ?) OR
                            (start_time < ? AND end_time >= ?) OR
                            (start_time >= ? AND end_time <= ?)
                        )
                        AND status != 'CANCELLED'
                    ");
                    $stmt->execute([
                        $_POST['table_id'],
                        $_POST['reservation_date'],
                        $_POST['end_time'],
                        $_POST['start_time'],
                        $_POST['end_time'],
                        $_POST['start_time'],
                        $_POST['start_time'],
                        $_POST['end_time']
                    ]);
                    $count = $stmt->fetchColumn();

                    if ($count > 0) {
                        $error = "This table is already reserved for the selected time";
                        break;
                    }

                    $stmt = $conn->prepare("
                        INSERT INTO reservations (customer_name, contact_number, email, 
                                                table_id, reservation_date, start_time, 
                                                end_time, number_of_guests, special_requests, status)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'PENDING')
                    ");
                    $stmt->execute([
                        $_POST['customer_name'],
                        $_POST['contact_number'],
                        $_POST['email'],
                        $_POST['table_id'],
                        $_POST['reservation_date'],
                        $_POST['start_time'],
                        $_POST['end_time'],
                        $_POST['number_of_guests'],
                        $_POST['special_requests']
                    ]);
                    $message = "Reservation added successfully";
                    break;

                case 'update_status':
                    $stmt = $conn->prepare("
                        UPDATE reservations 
                        SET status = ?
                        WHERE reservation_id = ?
                    ");
                    $stmt->execute([
                        $_POST['status'],
                        $_POST['reservation_id']
                    ]);
                    $message = "Reservation status updated successfully";
                    break;

                case 'delete':
                    $stmt = $conn->prepare("DELETE FROM reservations WHERE reservation_id = ?");
                    $stmt->execute([$_POST['reservation_id']]);
                    $message = "Reservation deleted successfully";
                    break;
            }
        }
    } catch (PDOException $e) {
        $error = "Database error: " . $e->getMessage();
    }
}

// Get all tables
$stmt = $conn->query("
    SELECT * FROM tables 
    WHERE status = 'ACTIVE'
    ORDER BY table_number
");
$tables = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get all reservations with table details
$stmt = $conn->query("
    SELECT r.*, t.table_number
    FROM reservations r
    JOIN tables t ON r.table_id = t.table_id
    ORDER BY r.reservation_date DESC, r.reservation_time DESC
");
$reservations = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Status options for dropdown
$statuses = ['PENDING', 'CONFIRMED', 'SEATED', 'COMPLETED', 'CANCELLED'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Reservations - HarahQR Sales</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
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

        .status-badge {
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 0.85rem;
        }

        .status-pending {
            background: #fff3cd;
            color: #856404;
        }

        .status-confirmed {
            background: #cce5ff;
            color: #004085;
        }

        .status-seated {
            background: #d4edda;
            color: #155724;
        }

        .status-completed {
            background: #e3fcef;
            color: #1cc88a;
        }

        .status-cancelled {
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

        <div class="row">
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Add New Reservation</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <input type="hidden" name="action" value="add">
                            
                            <div class="mb-3">
                                <label class="form-label">Customer Name</label>
                                <input type="text" class="form-control" name="customer_name" required>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Contact Number</label>
                                <input type="tel" class="form-control" name="contact_number" required>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Email</label>
                                <input type="email" class="form-control" name="email">
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Table</label>
                                <select class="form-control" name="table_id" required>
                                    <option value="">Select Table</option>
                                    <?php foreach ($tables as $table): ?>
                                        <option value="<?php echo $table['table_id']; ?>">
                                            Table <?php echo $table['table_number']; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Reservation Date</label>
                                <input type="date" class="form-control" name="reservation_date" 
                                       min="<?php echo date('Y-m-d'); ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Start Time</label>
                                <input type="time" class="form-control" name="start_time" required>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">End Time</label>
                                <input type="time" class="form-control" name="end_time" required>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Number of Guests</label>
                                <input type="number" class="form-control" name="number_of_guests" 
                                       min="1" required>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Special Requests</label>
                                <textarea class="form-control" name="special_requests" rows="3"></textarea>
                            </div>
                            
                            <button type="submit" class="btn btn-primary w-100">Add Reservation</button>
                        </form>
                    </div>
                </div>
            </div>
            
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Current Reservations</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Customer</th>
                                        <th>Table</th>
                                        <th>Date & Time</th>
                                        <th>Guests</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($reservations as $reservation): ?>
                                    <tr>
                                        <td>
                                            <?php echo htmlspecialchars($reservation['customer_name']); ?>
                                            <br>
                                            <small class="text-muted">
                                                <?php echo htmlspecialchars($reservation['contact_number']); ?>
                                            </small>
                                        </td>
                                        <td>Table <?php echo $reservation['table_number']; ?></td>
                                        <td>
                                            <?php echo date('M d, Y', strtotime($reservation['reservation_date'])); ?>
                                            <br>
                                            <small class="text-muted">
                                                <?php echo date('h:i A', strtotime($reservation['reservation_time'])); ?>
                                            </small>
                                        </td>
                                        <td><?php echo $reservation['number_of_guests']; ?></td>
                                        <td>
                                            <span class="status-badge status-<?php echo strtolower($reservation['status']); ?>">
                                                <?php echo $reservation['status']; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <button type="button" class="btn btn-sm btn-outline-primary me-1" 
                                                    onclick="updateStatus(<?php echo $reservation['reservation_id']; ?>, '<?php echo $reservation['status']; ?>')">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button type="button" class="btn btn-sm btn-outline-danger" 
                                                    onclick="deleteReservation(<?php echo $reservation['reservation_id']; ?>)">
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
        </div>
    </div>

    <!-- Update Status Modal -->
    <div class="modal fade" id="updateStatusModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Update Reservation Status</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="update_status">
                        <input type="hidden" name="reservation_id" id="update_reservation_id">
                        
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

    <!-- Delete Reservation Modal -->
    <div class="modal fade" id="deleteReservationModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Delete Reservation</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete this reservation? This action cannot be undone.</p>
                </div>
                <div class="modal-footer">
                    <form method="POST">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="reservation_id" id="delete_reservation_id">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger">Delete Reservation</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <?php include 'components/footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function updateStatus(reservationId, currentStatus) {
            document.getElementById('update_reservation_id').value = reservationId;
            document.getElementById('update_status').value = currentStatus;
            new bootstrap.Modal(document.getElementById('updateStatusModal')).show();
        }

        function deleteReservation(reservationId) {
            document.getElementById('delete_reservation_id').value = reservationId;
            new bootstrap.Modal(document.getElementById('deleteReservationModal')).show();
        }
    </script>
