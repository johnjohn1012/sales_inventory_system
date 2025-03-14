<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in and has cashier role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'CASHIER') {
    header('Location: ../login.php');
    exit();
}

// Get completed orders with items
try {
    // First, let's get a count of all orders
    $totalOrdersStmt = $conn->query("SELECT COUNT(*) as total FROM orders");
    $totalOrders = $totalOrdersStmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Now get a count of paid orders
    $paidOrdersStmt = $conn->query("SELECT COUNT(*) as paid FROM orders WHERE payment_status = 'PAID'");
    $paidOrders = $paidOrdersStmt->fetch(PDO::FETCH_ASSOC)['paid'];
    
    // Get completed orders with items - modified query to show all paid orders
    $completedStmt = $conn->query("
        SELECT 
            o.order_id,
            o.table_id,
            o.order_type,
            o.status,
            o.payment_method,
            o.payment_status,
            o.total_amount,
            o.created_at,
            o.updated_at,
            t.table_number,
            t.status as table_status,
            GROUP_CONCAT(
                JSON_OBJECT(
                    'order_item_id', oi.order_item_id,
                    'product_id', oi.product_id,
                    'name', p.name,
                    'description', p.description,
                    'category_name', c.name,
                    'quantity', oi.quantity,
                    'unit_price', oi.unit_price,
                    'subtotal', oi.subtotal
                )
            ) as items_json
        FROM orders o 
        LEFT JOIN tables t ON o.table_id = t.table_id 
        LEFT JOIN order_items oi ON o.order_id = oi.order_id
        LEFT JOIN products p ON oi.product_id = p.product_id
        LEFT JOIN categories c ON p.category_id = c.category_id
        WHERE o.payment_status = 'PAID'
        GROUP BY 
            o.order_id, 
            o.table_id,
            o.order_type,
            o.status,
            o.payment_method,
            o.payment_status,
            o.total_amount,
            o.created_at,
            o.updated_at,
            t.table_number,
            t.status
        ORDER BY o.created_at DESC
        LIMIT 100
    ");
    $completed_orders = $completedStmt->fetchAll(PDO::FETCH_ASSOC);

    // Process the items_json into a proper JSON array
    foreach ($completed_orders as &$order) {
        if (!$order['items_json'] || $order['items_json'] === 'null') {
            $order['items'] = '[]';
        } else {
            $order['items'] = '[' . $order['items_json'] . ']';
        }
        unset($order['items_json']); // Remove the temporary field
    }
} catch (PDOException $e) {
    $error = "Database error: " . $e->getMessage();
    error_log("SQL Error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Completed Orders - HarahQR Sales</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        /* Custom responsive styles */
        @media (max-width: 768px) {
            .navbar-brand {
                font-size: 1.1rem;
            }
            
            .table-responsive {
                font-size: 0.9rem;
            }
            
            .btn-sm {
                padding: 0.25rem 0.5rem;
                font-size: 0.8rem;
            }
            
            .order-row td {
                padding: 0.5rem;
            }
            
            .modal-dialog {
                margin: 0.5rem;
                max-width: 98%;
            }
            
            .badge {
                font-size: 0.7rem;
            }
            
            .card-header h5 {
                font-size: 1.1rem;
            }
            
            .alert {
                font-size: 0.9rem;
            }
        }
        
        /* General improvements */
        .table > :not(caption) > * > * {
            padding: 0.75rem;
            vertical-align: middle;
        }
        
        .order-row:hover {
            background-color: rgba(0,0,0,0.02);
        }
        
        .modal-body {
            max-height: calc(100vh - 210px);
            overflow-y: auto;
        }
        
        /* Print styles */
        @media print {
            .no-print {
                display: none !important;
            }
            
            .table {
                font-size: 12px;
            }
            
            .receipt-container {
                max-width: 80mm;
                margin: 0 auto;
            }
        }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark">
    <div class="container">
        <a class="navbar-brand" href="orders.php">
            <i class="fas fa-cash-register me-2"></i>Cashier Orders
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item me-3">
                    <a href="orders.php" class="btn btn-outline-light">
                        <i class="fas fa-shopping-cart me-2"></i>Active Orders
                    </a>
                </li>
                <li class="nav-item me-3">
                    <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#tableViewModal">
                        <i class="fas fa-chair me-2"></i>View Tables
                    </button>
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
    <?php if (isset($error)): ?>
        <div class="alert alert-danger fade-in">
            <i class="fas fa-exclamation-circle me-2"></i>
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <!-- Debug Information -->
    <div class="alert alert-info mb-4">
     
        <p class="mb-1">Total Orders: <?php echo $totalOrders; ?></p>
        <p class="mb-0">Paid Orders: <?php echo $paidOrders; ?></p>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card fade-in">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-check-circle me-2"></i>Completed Orders
                    </h5>
                </div>
                <div class="card-body">
                    <?php if (empty($completed_orders)): ?>
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            No completed orders found. This could be because:
                            <ul class="mb-0 mt-2">
                                <li>No orders have been marked as paid yet</li>
                                <li>The orders haven't been properly completed</li>
                                <li>There might be an issue with the order status updates</li>
                            </ul>
                        </div>
                    <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Order ID</th>
                                    <th>Table</th>
                                    <th>Items</th>
                                    <th class="text-end">Total</th>
                                    <th>Status</th>
                                    <th>Payment</th>
                                    <th class="d-none d-md-table-cell">Date/Time</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($completed_orders as $order): ?>
                                    <tr class="order-row">
                                        <td>
                                            <span class="fw-bold">#<?php echo htmlspecialchars($order['order_id']); ?></span>
                                        </td>
                                        <td>
                                            <span class="badge bg-info">
                                                <i class="fas fa-chair me-1"></i>
                                                <?php echo htmlspecialchars($order['table_number']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php
                                            $items = json_decode($order['items'], true);
                                            $itemCount = is_array($items) ? count($items) : 0;
                                            ?>
                                            <button class="btn btn-info btn-sm" onclick="viewItems(<?php echo htmlspecialchars(json_encode($order)); ?>)">
                                                <i class="fas fa-list me-1"></i><span class="d-none d-md-inline">See </span>Items (<?php echo $itemCount; ?>)
                                            </button>
                                        </td>
                                        <td class="text-end">
                                            <span class="fw-bold text-success">
                                                ₱<?php echo number_format($order['total_amount'], 2); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?php 
                                                echo $order['status'] === 'COMPLETED' ? 'success' : 
                                                    ($order['status'] === 'DELIVERED' ? 'info' : 'warning'); 
                                            ?>">
                                                <?php echo htmlspecialchars($order['status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge bg-primary">
                                                <i class="fas fa-<?php echo $order['payment_method'] === 'CASH' ? 'money-bill-wave' : 'mobile-alt'; ?> me-1"></i>
                                                <?php echo $order['payment_method']; ?>
                                            </span>
                                        </td>
                                        <td class="d-none d-md-table-cell">
                                            <small class="text-muted">
                                                <?php echo date('M d, Y h:i A', strtotime($order['created_at'])); ?>
                                            </small>
                                        </td>
                                        <td>
                                            <button class="btn btn-primary btn-sm" onclick="viewReceipt(<?php echo htmlspecialchars(json_encode($order)); ?>)">
                                                <i class="fas fa-receipt me-1"></i><span class="d-none d-md-inline">Receipt</span>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

!-- Table View Modal -->
<div class="modal fade" id="tableViewModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-chair me-2"></i>Restaurant Tables
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <style>
                    .table-card {
                        background: white;
                        border-radius: 15px;
                        padding: 20px;
                        margin-bottom: 20px;
                        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
                        transition: transform 0.3s ease;
                    }

                    .table-card:hover {
                        transform: translateY(-5px);
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
                </style>

                <div class="row" id="tableContainer">
                    <?php
                    try {
                        $tableStmt = $conn->query("SELECT * FROM tables ORDER BY table_number");
                        while ($table = $tableStmt->fetch(PDO::FETCH_ASSOC)) {
                            echo "
                            <div class='col-md-6 col-lg-4 mb-4'>
                                <div class='table-card'>
                                    <div class='d-flex justify-content-between align-items-center mb-3'>
                                        <h5 class='mb-0'>Table {$table['table_number']}</h5>
                                        <span class='status-badge status-" . strtolower($table['status']) . "'>
                                            " . ucfirst(strtolower($table['status'])) . "
                                        </span>
                                    </div>
                                    
                                    <div class='qr-code' id='qrcode-{$table['table_id']}'></div>
                                    
                                    <div class='d-flex justify-content-between align-items-center mt-3'>";
                            
                            if ($table['status'] === 'AVAILABLE') {
                                echo "
                                <button class='btn btn-primary' onclick='window.open(\"../order.php?table={$table['qr_code']}\", \"_blank\")'>
                                    <i class='fas fa-plus me-2'></i>New Order
                                </button>";
                            } else {
                                echo "
                                <button class='btn btn-secondary' disabled>
                                    <i class='fas fa-lock me-2'></i>Table Occupied
                                </button>";
                            }
                            
                            echo "
                                <button class='btn btn-link' onclick='downloadQR({$table['table_id']}, {$table['table_number']})'>
                                    <i class='fas fa-download me-2'></i>Download QR
                                </button>
                                    </div>
                                </div>
                            </div>";
                        }
                    } catch (PDOException $e) {
                        echo "<div class='col-12'><div class='alert alert-danger'>Error loading tables</div></div>";
                    }
                    ?>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Items List Modal -->
<div class="modal fade" id="itemsModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-scrollable modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-list me-2"></i>Order Items
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <p class="mb-1"><strong id="itemsOrderId"></strong></p>
                    <p class="mb-1" id="itemsTableNumber"></p>
                </div>
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Item</th>
                                <th class="d-none d-md-table-cell">Category</th>
                                <th class="text-center">Qty</th>
                                <th class="text-end">Price</th>
                                <th class="text-end">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody id="itemsList">
                            <!-- Items will be dynamically inserted here -->
                        </tbody>
                        <tfoot>
                            <tr>
                                <th colspan="4" class="text-end">Total Amount:</th>
                                <th class="text-end" id="itemsTotalAmount"></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Receipt Modal -->
<div class="modal fade" id="receiptModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-receipt me-2"></i>Order Receipt
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="receiptContent" class="p-3">
                    <!-- Receipt content will be dynamically inserted here -->
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" onclick="printReceipt()">
                    <i class="fas fa-print me-2"></i>Print Receipt
                </button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
function viewReceipt(order) {
    const items = JSON.parse(order.items);
    const receiptDate = new Date(order.created_at).toLocaleString();
    
    let itemsHtml = '';
    items.forEach(item => {
        itemsHtml += `
            <tr>
                <td>${item.name}</td>
                <td class="text-center">${item.quantity}</td>
                <td class="text-end">₱${parseFloat(item.unit_price).toFixed(2)}</td>
                <td class="text-end">₱${parseFloat(item.subtotal).toFixed(2)}</td>
            </tr>
        `;
    });

    const receiptHtml = `
        <div class="receipt-container">
            <div class="text-center mb-4">
                <h4 class="mb-0">HarahQR Sales</h4>
                <p class="mb-0">Restaurant Receipt</p>
                <small class="text-muted">Date: ${receiptDate}</small>
            </div>
            
            <div class="mb-3">
                <p class="mb-1"><strong>Order #${order.order_id}</strong></p>
                <p class="mb-1">Table: ${order.table_number}</p>
                <p class="mb-1">Payment Method: ${order.payment_method}</p>
            </div>

            <div class="table-responsive">
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>Item</th>
                            <th class="text-center">Qty</th>
                            <th class="text-end">Price</th>
                            <th class="text-end">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${itemsHtml}
                    </tbody>
                    <tfoot>
                        <tr>
                            <th colspan="3" class="text-end">Total Amount:</th>
                            <th class="text-end">₱${parseFloat(order.total_amount).toFixed(2)}</th>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <div class="text-center mt-4">
                <p class="mb-1">Thank you for dining with us!</p>
                <small class="text-muted">Please come again</small>
            </div>
        </div>
    `;

    document.getElementById('receiptContent').innerHTML = receiptHtml;
    new bootstrap.Modal(document.getElementById('receiptModal')).show();
}

function printReceipt() {
    const receiptContent = document.getElementById('receiptContent').innerHTML;
    const printWindow = window.open('', '_blank', 'height=600,width=800');
    
    printWindow.document.write(`
        <!DOCTYPE html>
        <html>
            <head>
                <title>Print Receipt</title>
                <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
                <style>
                    body { 
                        padding: 20px;
                        font-family: 'Arial', sans-serif;
                    }
                    @media print {
                        body { 
                            padding: 0;
                            margin: 0;
                        }
                        .modal-footer { 
                            display: none; 
                        }
                        @page {
                            margin: 0.5cm;
                        }
                        .table {
                            width: 100%;
                            margin-bottom: 1rem;
                            border-collapse: collapse;
                        }
                        .table th,
                        .table td {
                            padding: 0.5rem;
                            border-bottom: 1px solid #dee2e6;
                        }
                        .text-end {
                            text-align: right;
                        }
                        .text-center {
                            text-align: center;
                        }
                    }
                </style>
            </head>
            <body>
                <div class="receipt-container">
                    ${receiptContent}
                </div>
            </body>
        </html>
    `);

    printWindow.document.close();
    
    printWindow.onload = function() {
        setTimeout(function() {
            printWindow.focus();
            printWindow.print();
            printWindow.onafterprint = function() {
                printWindow.close();
            };
        }, 250);
    };
}

function viewItems(order) {
    const items = JSON.parse(order.items);
    
    document.getElementById('itemsOrderId').textContent = `Order #${order.order_id}`;
    document.getElementById('itemsTableNumber').textContent = `Table ${order.table_number}`;
    
    let itemsHtml = '';
    items.forEach(item => {
        itemsHtml += `
            <tr>
                <td>${item.name}</td>
                <td class="d-none d-md-table-cell"><small class="text-muted">${item.category_name}</small></td>
                <td class="text-center">
                    <span class="badge bg-secondary">x${item.quantity}</span>
                </td>
                <td class="text-end">₱${parseFloat(item.unit_price).toFixed(2)}</td>
                <td class="text-end">₱${parseFloat(item.subtotal).toFixed(2)}</td>
            </tr>
        `;
    });
    document.getElementById('itemsList').innerHTML = itemsHtml;
    document.getElementById('itemsTotalAmount').textContent = `₱${parseFloat(order.total_amount).toFixed(2)}`;
    
    new bootstrap.Modal(document.getElementById('itemsModal')).show();
}





// Initialize QR codes when the modal is shown
$('#tableViewModal').on('shown.bs.modal', function () {
    initializeQRCodes();
});

// Add keyboard shortcut for table view
document.addEventListener('keydown', function(e) {
    if (e.ctrlKey && e.key === 't') { // Ctrl+T
        e.preventDefault();
        $('#tableViewModal').modal('show');
    }
});
</script>
</body>
</html> 