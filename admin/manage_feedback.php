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
                case 'delete':
                    $stmt = $conn->prepare("DELETE FROM customer_feedback WHERE feedback_id = ?");
                    $stmt->execute([$_POST['feedback_id']]);
                    $message = "Feedback deleted successfully";
                    break;

                case 'update_status':
                    $stmt = $conn->prepare("
                        UPDATE customer_feedback 
                        SET status = ?
                        WHERE feedback_id = ?
                    ");
                    $stmt->execute([
                        $_POST['status'],
                        $_POST['feedback_id']
                    ]);
                    $message = "Feedback status updated successfully";
                    break;
            }
        }
    } catch (PDOException $e) {
        $error = "Database error: " . $e->getMessage();
    }
}

// Get feedback with order and customer details
$query = "
    SELECT f.*, o.order_id, o.total_amount, o.status as order_status,
           c.first_name, c.last_name, c.email, c.contact_number
    FROM customer_feedback f
    LEFT JOIN orders o ON f.order_id = o.order_id
    LEFT JOIN customers c ON f.customer_id = c.customer_id
    ORDER BY f.created_at DESC
";

$stmt = $conn->query($query);
$feedback_list = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Status options for dropdown
$statuses = ['PENDING', 'REVIEWED', 'RESOLVED', 'IGNORED'];
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

        .status-badge {
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 0.85rem;
        }

        .status-pending {
            background: #fff3cd;
            color: #856404;
        }

        .status-reviewed {
            background: #cce5ff;
            color: #004085;
        }

        .status-resolved {
            background: #d4edda;
            color: #155724;
        }

        .status-ignored {
            background: #fce3e3;
            color: #e74a3b;
        }

        .rating {
            color: #ffc107;
        }

        .feedback-card {
            background: white;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 15px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }

        .feedback-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }

        .feedback-content {
            color: var(--secondary-color);
            margin-bottom: 10px;
        }

        .feedback-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.9rem;
            color: var(--secondary-color);
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
            <div class="card-header">
                <h5 class="mb-0">Customer Feedback</h5>
            </div>
            <div class="card-body">
                <?php foreach ($feedback_list as $item): ?>
                    <div class="feedback-card">
                        <div class="feedback-header">
                            <div>
                                <h6 class="mb-0">
                                    <?php echo htmlspecialchars($item['first_name'] . ' ' . $item['last_name']); ?>
                                </h6>
                                <small class="text-muted">
                                    Order #<?php echo $item['order_id']; ?> | 
                                    <?php echo date('M d, Y', strtotime($item['order_date'])); ?>
                                </small>
                            </div>
                            <div>
                                <span class="status-badge status-<?php echo strtolower($item['order_status']); ?>">
                                    <?php echo $item['order_status']; ?>
                                </span>
                            </div>
                        </div>
                        
                        <div class="feedback-content">
                            <div class="rating mb-2">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <i class="fas fa-star <?php echo $i <= $item['rating'] ? 'text-warning' : 'text-muted'; ?>"></i>
                                <?php endfor; ?>
                            </div>
                            <p class="mb-0"><?php echo nl2br(htmlspecialchars($item['comment'])); ?></p>
                        </div>
                        
                        <div class="feedback-footer">
                            <div>
                                <i class="fas fa-envelope me-1"></i>
                                <?php echo htmlspecialchars($item['email']); ?>
                            </div>
                            <div>
                                <button type="button" class="btn btn-sm btn-outline-primary me-1" 
                                        onclick="updateStatus(<?php echo $item['feedback_id']; ?>, '<?php echo $item['order_status']; ?>')">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-danger" 
                                        onclick="deleteFeedback(<?php echo $item['feedback_id']; ?>)">
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
                    <h5 class="modal-title">Update Feedback Status</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="update_status">
                        <input type="hidden" name="feedback_id" id="update_feedback_id">
                        
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

    <!-- Delete Feedback Modal -->
    <div class="modal fade" id="deleteFeedbackModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Delete Feedback</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete this feedback? This action cannot be undone.</p>
                </div>
                <div class="modal-footer">
                    <form method="POST">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="feedback_id" id="delete_feedback_id">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger">Delete Feedback</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <?php include 'components/footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function updateStatus(feedbackId, currentStatus) {
            document.getElementById('update_feedback_id').value = feedbackId;
            document.getElementById('update_status').value = currentStatus;
            new bootstrap.Modal(document.getElementById('updateStatusModal')).show();
        }

        function deleteFeedback(feedbackId) {
            document.getElementById('delete_feedback_id').value = feedbackId;
            new bootstrap.Modal(document.getElementById('deleteFeedbackModal')).show();
        }
    </script>
