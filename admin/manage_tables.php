<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in and has admin role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'ADMIN') {
    header('Location: ../login.php');
    exit();
}

// Handle table creation
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $table_number = $_POST['table_number'];
        
        // Check if table number already exists
        $check_stmt = $conn->prepare("SELECT COUNT(*) as count FROM tables WHERE table_number = ?");
        $check_stmt->execute([$table_number]);
        $result = $check_stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result['count'] > 0) {
            $_SESSION['error'] = "Table {$table_number} already exists.";
        } else {
            $qr_code = 'table_' . $table_number . '_' . uniqid();
            $stmt = $conn->prepare("INSERT INTO tables (table_number, qr_code, status) VALUES (?, ?, 'AVAILABLE')");
            $stmt->execute([$table_number, $qr_code]);
            $_SESSION['success'] = "Table {$table_number} created successfully!";
        }
    } catch (PDOException $e) {
        $_SESSION['error'] = "Error creating table: " . $e->getMessage();
    }
    
    // Redirect to prevent form resubmission
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit();
}

// Get all tables
try {
    $stmt = $conn->query("SELECT * FROM tables ORDER BY table_number");
    $tables = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $_SESSION['error'] = "Database error: " . $e->getMessage();
}
?>

    <style>
    
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
        }

        .card:hover {
            transform: translateY(-5px);
        }

        .table-card {
            background: white;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .qr-code {
            text-align: center;
            margin: 15px 0;
        }

        .qr-code canvas {
            border-radius: 10px;
            padding: 10px;
            background: white;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .status-badge {
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 500;
        }

        .status-available { background: #e3fcef; color: #1cc88a; }
        .status-occupied { background: #ffe9e9; color: #e74a3b; }
        .status-ready { background: #fff4e5; color: #f6c23e; }
        .status-cleaning { background: #edf2f9; color: #858796; }

        .btn-add-table {
            background: linear-gradient(135deg, #4e73df, #224abe);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 10px;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .btn-add-table:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(78, 115, 223, 0.4);
            color: white;
        }

        .btn-view-order {
            background: #f8f9fa;
            border: none;
            padding: 8px 15px;
            border-radius: 8px;
            color: #4e73df;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .btn-view-order:hover {
            background: #4e73df;
            color: white;
        }
    </style>
    
    <?php include 'components/header.php'; ?>

    <?php include 'components/navbar.php'; ?>
 

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

        <div class="row mb-4">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title mb-4">Add New Table</h5>
                        <form method="POST" class="d-flex gap-3" id="addTableForm">
                            <input type="number" name="table_number" class="form-control" placeholder="Table Number" required>
                            <button type="submit" class="btn btn-add-table">
                                <i class="fas fa-plus me-2"></i>Add Table
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <?php foreach($tables as $table): ?>
            <div class="col-md-6 col-lg-4">
                <div class="table-card" data-table-id="<?php echo $table['table_id']; ?>">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="mb-0">Table <?php echo htmlspecialchars($table['table_number']); ?></h5>
                        <span class="status-badge status-<?php echo strtolower($table['status']); ?>">
                            <?php echo ucfirst(strtolower($table['status'])); ?>
                        </span>
                    </div>
                    
                    <div class="qr-code" id="qrcode-<?php echo $table['table_id']; ?>"></div>
                    
                    <div class="d-flex justify-content-between align-items-center mt-3">
                        <button class="btn btn-view-order" onclick="window.open('../order.php?table=<?php echo $table['qr_code']; ?>', '_blank')">
                            <i class="fas fa-external-link-alt me-2"></i>View Order Page
                        </button>
                        <button class="btn btn-link" onclick="downloadQR(<?php echo $table['table_id']; ?>, <?php echo $table['table_number']; ?>)">
                            <i class="fas fa-download me-2"></i>Download QR
                        </button>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php include 'components/footer.php'; ?>
  <!-- Scripts -->
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Generate QR codes for each table
        <?php foreach($tables as $table): ?>
        new QRCode(document.getElementById("qrcode-<?php echo $table['table_id']; ?>"), {
            text: window.location.origin + "/harahqrsales/order.php?table=<?php echo $table['qr_code']; ?>",
            width: 128,
            height: 128,
            colorDark: "#2c3e50",
            colorLight: "#ffffff",
            correctLevel: QRCode.CorrectLevel.H
        });
        <?php endforeach; ?>

        function downloadQR(tableId, tableNumber) {
            const canvas = document.querySelector(`#qrcode-${tableId} canvas`);
            const link = document.createElement('a');
            link.download = `table-${tableNumber}-qr.png`;
            link.href = canvas.toDataURL();
            link.click();
        }

        // Prevent form resubmission on page refresh
        if (window.history.replaceState) {
            window.history.replaceState(null, null, window.location.href);
        }

        // Real-time updates
        function updateTableStatuses() {
            fetch('../api/get_tables.php')
                .then(response => response.text())
                .then(html => {
                    const tempDiv = document.createElement('div');
                    tempDiv.innerHTML = html;
                    
                    // Update each table's status
                    tempDiv.querySelectorAll('.table-card').forEach(newTableCard => {
                        const tableId = newTableCard.getAttribute('data-table-id');
                        const currentTableCard = document.querySelector(`.table-card[data-table-id="${tableId}"]`);
                        
                        if (currentTableCard) {
                            const newStatus = newTableCard.querySelector('.status-badge').textContent.trim();
                            const currentStatus = currentTableCard.querySelector('.status-badge');
                            
                            // Update status badge
                            currentStatus.textContent = newStatus;
                            currentStatus.className = newTableCard.querySelector('.status-badge').className;
                            
                            // Update table card background if needed
                            currentTableCard.className = newTableCard.className;
                        }
                    });
                })
                .catch(error => console.error('Error updating table statuses:', error));
        }

        // Update every 5 seconds
        setInterval(updateTableStatuses, 5000);
    </script>
