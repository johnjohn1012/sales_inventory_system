<?php
session_start();
require_once 'config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id']) && !isset($_GET['table'])) {
    header('Location: login.php');
    exit();
}

// Get table information if QR code is scanned
$table_id = isset($_GET['table']) ? $_GET['table'] : null;

// Get user role if logged in
$user_role = isset($_SESSION['role']) ? $_SESSION['role'] : null;

// Redirect to appropriate dashboard based on role
if (isset($_SESSION['user_id']) && !$table_id) {
    switch ($user_role) {
        case 'ADMIN':
            header('Location: admin/dashboard.php');
            exit();
        case 'CASHIER':
            header('Location: cashier/orders.php');
            exit();
        case 'KITCHEN':
            header('Location: kitchen/orders.php');
            exit();
        case 'WAITER':
            header('Location: waiter/tables.php');
            exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HarahQR Sales</title>
    <!-- Bootstrap CSS -->
    <link rel="icon" href="images-harah/logos.png" type="image/png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .table-box {
            width: 150px;
            height: 150px;
            margin: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s;
        }
        .table-box:hover {
            transform: scale(1.05);
        }
        .available {
            background-color: #28a745;
            color: white;
        }
        .occupied {
            background-color: #dc3545;
            color: white;
        }
        .dirty {
            background-color: #ffc107;
            color: black;
        }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container">
        <a class="navbar-brand" href="#">HarahQR Sales</a>
        <?php if (isset($_SESSION['user_id'])): ?>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <?php if ($user_role === 'ADMIN'): ?>
                <li class="nav-item">
                    <a class="nav-link" href="admin/dashboard.php">Dashboard</a>
                </li>
                <?php endif; ?>
                <?php if ($user_role === 'CASHIER'): ?>
                <li class="nav-item">
                    <a class="nav-link" href="cashier/orders.php">Orders</a>
                </li>
                <?php endif; ?>
                <?php if ($user_role === 'KITCHEN'): ?>
                <li class="nav-item">
                    <a class="nav-link" href="kitchen/orders.php">Kitchen Orders</a>
                </li>
                <?php endif; ?>
                <?php if ($user_role === 'WAITER'): ?>
                <li class="nav-item">
                    <a class="nav-link" href="waiter/tables.php">Tables</a>
                </li>
                <?php endif; ?>
                <li class="nav-item">
                    <a class="nav-link" href="logout.php">Logout</a>
                </li>
            </ul>
        </div>
        <?php endif; ?>
    </div>
</nav>

<div class="container mt-4">
    <?php if ($table_id): ?>
        <!-- Customer Order View -->
        <div id="menuSection">
            <h2 class="mb-4">Table <?php echo htmlspecialchars($table_id); ?> - Menu</h2>
            <div class="row" id="menuItems">
                <!-- Menu items will be loaded here via AJAX -->
            </div>
            <div class="fixed-bottom bg-light p-3" id="cartSection" style="display: none;">
                <div class="container">
                    <div class="row">
                        <div class="col-8">
                            <h4>Your Order</h4>
                            <div id="cartItems"></div>
                        </div>
                        <div class="col-4 text-end">
                            <h4>Total: ₱<span id="totalAmount">0.00</span></h4>
                            <button class="btn btn-primary" onclick="submitOrder()">Place Order</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php else: ?>
        <!-- Staff View -->
        <?php if ($user_role === 'WAITER'): ?>
        <div class="row">
            <div class="col-12">
                <h2>Tables Status</h2>
                <div class="d-flex flex-wrap" id="tablesContainer">
                    <!-- Tables will be loaded here via AJAX -->
                </div>
            </div>
        </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<!-- Bootstrap JS and dependencies -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
<?php if ($table_id): ?>
// Customer-side JavaScript
let cart = [];

function loadMenu() {
    $.ajax({
        url: 'api/get_menu.php',
        method: 'GET',
        success: function(response) {
            $('#menuItems').html(response);
        }
    });
}

function addToCart(productId, name, price) {
    const existingItem = cart.find(item => item.productId === productId);
    if (existingItem) {
        existingItem.quantity++;
    } else {
        cart.push({
            productId: productId,
            name: name,
            price: price,
            quantity: 1
        });
    }
    updateCartDisplay();
}

function updateCartDisplay() {
    let cartHtml = '';
    let total = 0;
    
    cart.forEach(item => {
        const subtotal = item.price * item.quantity;
        total += subtotal;
        cartHtml += `
            <div class="d-flex justify-content-between mb-2">
                <span>${item.name} x ${item.quantity}</span>
                <span>₱${subtotal.toFixed(2)}</span>
            </div>
        `;
    });

    $('#cartItems').html(cartHtml);
    $('#totalAmount').text(total.toFixed(2));
    $('#cartSection').show();
}

function submitOrder() {
    if (cart.length === 0) {
        alert('Please add items to your cart first');
        return;
    }

    $.ajax({
        url: 'api/submit_order.php',
        method: 'POST',
        data: {
            table_id: <?php echo $table_id; ?>,
            items: JSON.stringify(cart)
        },
        success: function(response) {
            alert('Order submitted successfully!');
            cart = [];
            updateCartDisplay();
        },
        error: function() {
            alert('Error submitting order. Please try again.');
        }
    });
}

$(document).ready(function() {
    loadMenu();
});

<?php else: ?>
// Staff-side JavaScript
function loadTables() {
    $.ajax({
        url: 'api/get_tables.php',
        method: 'GET',
        success: function(response) {
            $('#tablesContainer').html(response);
        }
    });
}

function updateTableStatus(tableId, status) {
    $.ajax({
        url: 'api/update_table_status.php',
        method: 'POST',
        data: {
            table_id: tableId,
            status: status
        },
        success: function(response) {
            loadTables();
        }
    });
}

$(document).ready(function() {
    <?php if ($user_role === 'WAITER'): ?>
    loadTables();
    setInterval(loadTables, 30000); // Refresh every 30 seconds
    <?php endif; ?>
});
<?php endif; ?>
</script>

</body>
</html> 