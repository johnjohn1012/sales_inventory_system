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

// Get filter parameters
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d', strtotime('-30 days'));
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');
$action_type = isset($_GET['action_type']) ? $_GET['action_type'] : '';
$search = isset($_GET['search']) ? $_GET['search'] : '';

// Build query
$query = "
    SELECT l.*, u.username, e.first_name, e.last_name
    FROM system_logs l
    LEFT JOIN users u ON l.user_id = u.user_id
    LEFT JOIN employees e ON u.employee_id = e.employee_id
    WHERE l.created_at BETWEEN :start_date AND :end_date
";

$params = [
    ':start_date' => $start_date . ' 00:00:00',
    ':end_date' => $end_date . ' 23:59:59'
];

if ($action_type) {
    $query .= " AND l.action = :action_type";
    $params[':action_type'] = $action_type;
}

if ($search) {
    $query .= " AND (l.description LIKE :search OR u.username LIKE :search OR e.first_name LIKE :search OR e.last_name LIKE :search)";
    $search_param = "%$search%";
    $params[':search'] = $search_param;
}

$query .= " ORDER BY l.created_at DESC";

// Get total records for pagination
$count_query = str_replace("l.*, u.username, e.first_name, e.last_name", "COUNT(*)", $query);
$stmt = $conn->prepare($count_query);
$stmt->execute($params);
$total_records = $stmt->fetchColumn();

// Pagination
$records_per_page = 20;
$total_pages = ceil($total_records / $records_per_page);
$current_page = isset($_GET['page']) ? max(1, min($total_pages, intval($_GET['page']))) : 1;
$offset = ($current_page - 1) * $records_per_page;

// Add pagination to query
$query .= " LIMIT :limit OFFSET :offset";
$params[':limit'] = $records_per_page;
$params[':offset'] = $offset;

// Get logs
$stmt = $conn->prepare($query);

// Bind all parameters
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
}

// Execute the query
$stmt->execute();
$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get unique action types for filter
$stmt = $conn->query("SELECT DISTINCT action FROM system_logs ORDER BY action");
$action_types = $stmt->fetchAll(PDO::FETCH_COLUMN);
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

        .log-card {
            background: white;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 15px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
            transition: all 0.3s ease;
        }

        .log-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        }

        .log-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }

        .log-content {
            color: var(--secondary-color);
            margin-bottom: 10px;
        }

        .log-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.9rem;
            color: var(--secondary-color);
        }

        .action-type {
            padding: 3px 8px;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: 500;
        }

        .type-create {
            background: #e3fcef;
            color: #1cc88a;
        }

        .type-update {
            background: #cce5ff;
            color: #004085;
        }

        .type-delete {
            background: #fce3e3;
            color: #e74a3b;
        }

        .type-login {
            background: #fff3cd;
            color: #856404;
        }

        .pagination .page-link {
            border-radius: 10px;
            margin: 0 2px;
            color: var(--primary-color);
        }

        .pagination .page-item.active .page-link {
            background: var(--primary-color);
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

        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">System Logs</h5>
            </div>
            <div class="card-body">
                <!-- Filters -->
                <form method="GET" class="row g-3 mb-4">
                    <div class="col-md-3">
                        <label class="form-label">Start Date</label>
                        <input type="date" class="form-control" name="start_date" value="<?php echo $start_date; ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">End Date</label>
                        <input type="date" class="form-control" name="end_date" value="<?php echo $end_date; ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Action Type</label>
                        <select class="form-select" name="action_type">
                            <option value="">All Actions</option>
                            <?php foreach ($action_types as $type): ?>
                                <option value="<?php echo $type; ?>" <?php echo $action_type === $type ? 'selected' : ''; ?>>
                                    <?php echo ucfirst(str_replace('_', ' ', $type)); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Search</label>
                        <input type="text" class="form-control" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search logs...">
                    </div>
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-filter me-2"></i>Apply Filters
                        </button>
                        <a href="manage_system_logs.php" class="btn btn-outline-secondary">
                            <i class="fas fa-times me-2"></i>Clear Filters
                        </a>
                    </div>
                </form>

                <!-- Logs -->
                <?php foreach ($logs as $log): ?>
                    <div class="log-card">
                        <div class="log-header">
                            <div>
                                <span class="action-type type-<?php echo strtolower($log['action']); ?>">
                                    <?php echo ucfirst(str_replace('_', ' ', $log['action'])); ?>
                                </span>
                            </div>
                            <small class="text-muted">
                                <?php echo date('M d, Y h:i A', strtotime($log['created_at'])); ?>
                            </small>
                        </div>
                        
                        <div class="log-content">
                            <p class="mb-1">
                                <?php echo htmlspecialchars($log['description']); ?>
                            </p>
                            <?php if ($log['username']): ?>
                                <p class="mb-0">
                                    <strong>By:</strong> <?php echo htmlspecialchars($log['first_name'] . ' ' . $log['last_name'] . ' (' . $log['username'] . ')'); ?>
                                </p>
                            <?php endif; ?>
                        </div>
                        
                        <div class="log-footer">
                            <div>
                                <strong>IP Address:</strong> 
                                <?php 
                                $ip = $log['ip_address'];
                                if ($ip === '::1') {
                                    echo 'localhost';
                                } else {    
                                    echo htmlspecialchars($ip);
                                }
                                ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>

                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                    <nav aria-label="Page navigation" class="mt-4">
                        <ul class="pagination justify-content-center">
                            <?php if ($current_page > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo $current_page - 1; ?>&start_date=<?php echo $start_date; ?>&end_date=<?php echo $end_date; ?>&action_type=<?php echo $action_type; ?>&search=<?php echo urlencode($search); ?>">
                                        <i class="fas fa-chevron-left"></i>
                                    </a>
                                </li>
                            <?php endif; ?>

                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                <li class="page-item <?php echo $i === $current_page ? 'active' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $i; ?>&start_date=<?php echo $start_date; ?>&end_date=<?php echo $end_date; ?>&action_type=<?php echo $action_type; ?>&search=<?php echo urlencode($search); ?>">
                                        <?php echo $i; ?>
                                    </a>
                                </li>
                            <?php endfor; ?>

                            <?php if ($current_page < $total_pages): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo $current_page + 1; ?>&start_date=<?php echo $start_date; ?>&end_date=<?php echo $end_date; ?>&action_type=<?php echo $action_type; ?>&search=<?php echo urlencode($search); ?>">
                                        <i class="fas fa-chevron-right"></i>
                                    </a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php include 'components/footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
