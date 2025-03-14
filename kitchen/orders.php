<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in and has kitchen role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'KITCHEN') {
    header('Location: ../login.php');
    exit();
}

// Get active orders
try {
    $stmt = $conn->query("
        SELECT 
            o.*,
            t.table_number,
            CONCAT(
                '[',
                GROUP_CONCAT(
                    JSON_OBJECT(
                        'product_id', oi.product_id,
                        'name', p.name,
                        'quantity', oi.quantity,
                        'unit_price', oi.unit_price
                    )
                ),
                ']'
            ) as items
        FROM orders o 
        LEFT JOIN tables t ON o.table_id = t.table_id 
        LEFT JOIN order_items oi ON o.order_id = oi.order_id
        LEFT JOIN products p ON oi.product_id = p.product_id
        WHERE o.status IN ('PAID', 'PREPARING') 
        GROUP BY o.order_id, o.status, o.created_at, o.total_amount, t.table_number
        ORDER BY o.created_at ASC
    ");
    $active_orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Process items for each order
    foreach ($active_orders as &$order) {
        if (!$order['items']) {
            $order['items'] = '[]';
        }
    }
} catch (PDOException $e) {
    $error = "Database error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kitchen Orders - HarahQR Sales</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
    <style>
        .order-card {
            transition: all 0.3s ease;
            border: none;
            border-radius: 15px;
            overflow: hidden;
        }
        .order-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 15px rgba(0,0,0,0.1);
        }
        .timer {
            font-size: 1.1rem;
            font-weight: 500;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            background: rgba(0,0,0,0.1);
            margin-bottom: 1rem;
        }
        .timer.warning {
            background: rgba(255, 193, 7, 0.2);
            color: #856404;
        }
        .timer.danger {
            background: rgba(220, 53, 69, 0.2);
            color: #721c24;
        }
        .order-items {
            max-height: 200px;
            overflow-y: auto;
            padding-right: 10px;
        }
        .order-items::-webkit-scrollbar {
            width: 5px;
        }
        .order-items::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 10px;
        }
        .order-items::-webkit-scrollbar-thumb {
            background: #888;
            border-radius: 10px;
        }
        .order-item {
            padding: 0.5rem;
            margin-bottom: 0.5rem;
            border-radius: 8px;
            background: rgba(0,0,0,0.05);
            transition: all 0.3s ease;
        }
        .order-item:hover {
            background: rgba(0,0,0,0.1);
        }
    </style>
    <audio id="alertSound" preload="auto">
        <source src="../assets/audio/alert.mp3" type="audio/mpeg">
    </audio>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark">
    <div class="container">
        <a class="navbar-brand" href="#">
            <i class="fas fa-utensils me-2"></i>Kitchen Orders
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item me-3">
                    <button class="btn btn-link nav-link position-relative" data-bs-toggle="modal" data-bs-target="#notificationModal">
                        <i class="fas fa-bell"></i>
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger notification-badge" style="display: none;">
                            0
                        </span>
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

<div class="container-fluid mt-4">
    <?php if (isset($error)): ?>
        <div class="alert alert-danger fade-in">
            <i class="fas fa-exclamation-circle me-2"></i>
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <div class="row" id="ordersContainer">
        <?php foreach ($active_orders as $order): ?>
            <div class="col-md-4 mb-4 fade-in">
                <div class="order-card card">
                    <div class="card-header bg-<?php echo $order['status'] === 'PENDING' ? 'warning' : 'info'; ?>">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">
                                <i class="fas fa-receipt me-2"></i>
                                Order #<?php echo htmlspecialchars($order['order_id']); ?>
                            </h5>
                            <span class="badge bg-light text-dark">
                                <i class="fas fa-chair me-1"></i>
                                Table <?php echo htmlspecialchars($order['table_number']); ?>
                            </span>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="timer mb-3" data-created="<?php echo htmlspecialchars($order['created_at']); ?>">
                            <i class="fas fa-clock me-2"></i>
                            Waiting time: <span class="waiting-time">0:00</span>
                        </div>
                        <h6 class="card-subtitle mb-3">
                            <i class="fas fa-clipboard-list me-2"></i>Items:
                        </h6>
                        <div class="order-items">
                            <?php
                            $items = json_decode($order['items'], true);
                            if ($items && is_array($items)):
                                foreach ($items as $item):
                                    if (isset($item['name']) && isset($item['quantity'])):
                            ?>
                                <div class="order-item d-flex justify-content-between align-items-center">
                                    <span>
                                        <i class="fas fa-utensils me-2"></i>
                                        <?php echo htmlspecialchars($item['name']); ?>
                                    </span>
                                    <span class="badge bg-primary rounded-pill">
                                        x<?php echo htmlspecialchars($item['quantity']); ?>
                                    </span>
                                </div>
                            <?php 
                                    endif;
                                endforeach;
                            else:
                            ?>
                                <div class="text-muted">
                                    <i class="fas fa-info-circle me-1"></i>No items found
                                </div>
                            <?php endif; ?>
                        </div>
                        <?php if ($order['status'] === 'PENDING'): ?>
                            <button class="btn btn-info w-100 mt-3" onclick="updateOrderStatus(<?php echo $order['order_id']; ?>, 'PREPARING')">
                                <i class="fas fa-fire me-2"></i>Start Preparing
                            </button>
                        <?php else: ?>
                            <button class="btn btn-success w-100 mt-3" onclick="updateOrderStatus(<?php echo $order['order_id']; ?>, 'COMPLETED')">
                                <i class="fas fa-check me-2"></i>Mark as Ready
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- Notification Modal -->
<div class="modal fade" id="notificationModal" tabindex="-1" aria-labelledby="notificationModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="notificationModalLabel">Notifications</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="notificationList" class="list-group">
                    <!-- Notifications will be loaded here -->
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<audio id="newOrderSound" src="../assets/notification.mp3"></audio>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<script>
function updateOrderStatus(orderId, status) {
    // Show loading state on the button
    const button = event.target;
    const originalText = button.innerHTML;
    button.disabled = true;
    button.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Updating...';

    $.ajax({
        url: '../api/update_order_status.php',
        method: 'POST',
        data: {
            order_id: orderId,
            status: status
        },
        success: function(response) {
            if (response.error) {
                alert('Error: ' + response.error);
                button.disabled = false;
                button.innerHTML = originalText;
                return;
            }
            // Instead of reloading, update the order card
            const $orderCard = $(button).closest('.order-card');
            if (status === 'PREPARING') {
                $orderCard.find('.card-header').removeClass('bg-warning').addClass('bg-info');
                $orderCard.find('button').removeClass('btn-info').addClass('btn-success')
                    .html('<i class="fas fa-check me-2"></i>Mark as Ready')
                    .attr('onclick', `updateOrderStatus(${orderId}, 'COMPLETED')`);
            } else if (status === 'COMPLETED') {
                $orderCard.fadeOut(500, function() {
                    $(this).remove();
                });
            }
            button.disabled = false;
            button.innerHTML = originalText;
        },
        error: function(xhr) {
            console.error('Error:', xhr.responseText);
            let errorMessage = 'Error updating order status.';
            try {
                const response = JSON.parse(xhr.responseText);
                if (response.error) {
                    errorMessage += ' ' + response.error;
                }
            } catch (e) {
                console.error('Parse error:', e);
            }
            alert(errorMessage);
            button.disabled = false;
            button.innerHTML = originalText;
        }
    });
}

function updateWaitingTimes() {
    $('.timer').each(function() {
        const $timer = $(this);
        const $timeSpan = $timer.find('.waiting-time');
        let currentTime = parseInt($timeSpan.data('current-time') || 0);
        
        // If this is the first time, initialize the current time
        if (!currentTime) {
            const createdAt = new Date($timer.data('created'));
            const now = new Date();
            currentTime = Math.floor((now - createdAt) / 1000);
            $timeSpan.data('current-time', currentTime);
        } else {
            // Increment the current time by 1 second
            currentTime++;
            $timeSpan.data('current-time', currentTime);
        }
        
        // Calculate minutes and seconds
        const minutes = Math.floor(currentTime / 60);
        const seconds = currentTime % 60;
        
        // Update the display
        const timeString = `${minutes}:${seconds.toString().padStart(2, '0')}`;
        $timeSpan.text(timeString);
        
        // Update styling
        if (minutes >= 15) {
            $timer.addClass('danger').removeClass('warning');
        } else if (minutes >= 10) {
            $timer.addClass('warning').removeClass('danger');
        } else {
            $timer.removeClass('warning danger');
        }
    });
}

function loadNewOrders() {
    $.ajax({
        url: '../api/get_kitchen_orders.php',
        method: 'GET',
        success: function(response) {
            if (response.new_orders) {
                document.getElementById('newOrderSound').play();
                // Instead of reloading, append new orders
                const $ordersContainer = $('#ordersContainer');
                const existingOrderIds = new Set($('.order-card').map(function() {
                    return $(this).find('h5').text().match(/#(\d+)/)[1];
                }).get());
                
                // Parse the new orders HTML
                const $newOrders = $(response.new_orders);
                $newOrders.each(function() {
                    const orderId = $(this).find('h5').text().match(/#(\d+)/)[1];
                    if (!existingOrderIds.has(orderId)) {
                        // Add the new order with a fade-in effect
                        $(this).addClass('fade-in').appendTo($ordersContainer);
                        // Initialize timer for the new order
                        const $timer = $(this).find('.timer');
                        const createdAt = new Date($timer.data('created'));
                        const now = new Date();
                        const currentTime = Math.floor((now - createdAt) / 1000);
                        $timer.find('.waiting-time').data('current-time', currentTime);
                    }
                });
            }
        }
    });
}

function fetchNotifications() {
    $.ajax({
        url: '../api/get_notifications.php',
        method: 'GET',
        success: function(response) {
            if (response.notifications && response.notifications.length > 0) {
                let notificationHtml = '';
                response.notifications.forEach(function(notification) {
                    notificationHtml += `
                        <div class="list-group-item">
                            <div class="d-flex w-100 justify-content-between">
                                <p class="mb-1">${notification.message}</p>
                                <small class="text-muted">${new Date(notification.created_at).toLocaleTimeString()}</small>
                            </div>
                        </div>`;
                });
                $('#notificationList').html(notificationHtml);
                $('.notification-badge').text(response.notifications.length).show();
            } else {
                $('#notificationList').html('<div class="list-group-item">No new notifications</div>');
                $('.notification-badge').hide();
            }
        },
        error: function(xhr) {
            console.error('Error fetching notifications:', xhr);
            $('#notificationList').html('<div class="list-group-item text-danger">Error loading notifications</div>');
        }
    });
}

let lastAlertTime = 0;

function checkForAlerts() {
    $.ajax({
        url: '../api/get_notifications.php',
        method: 'GET',
        success: function(response) {
            if (response.notifications && response.notifications.length > 0) {
                response.notifications.forEach(function(notification) {
                    // Check if this is a new sound alert
                    if (notification.type === 'SOUND_ALERT' && 
                        new Date(notification.created_at).getTime() > lastAlertTime) {
                        playAlertSound();
                        lastAlertTime = new Date(notification.created_at).getTime();
                    }
                });
            }
        }
    });
}

function playAlertSound() {
    const audio = document.getElementById('alertSound');
    if (audio) {
        audio.currentTime = 0; // Reset to start
        audio.play().catch(function(error) {
            console.log("Audio play failed:", error);
        });
        
        // Show visual alert
        Swal.fire({
            position: 'top-end',
            icon: 'warning',
            title: 'New Order Alert!',
            text: 'Cashier has sent a sound alert.',
            showConfirmButton: false,
            timer: 3000,
            toast: true
        });
    }
}

$(document).ready(function() {
    // Initial update
    updateWaitingTimes();
    
    // Update every second using a more reliable method
    const timerInterval = setInterval(updateWaitingTimes, 1000);
    
    // Other intervals
    setInterval(loadNewOrders, 30000);
    setInterval(fetchNotifications, 30000);
    fetchNotifications();
    
    $('#notificationModal').on('show.bs.modal', function () {
        fetchNotifications();
    });

    // Check for alerts every 2 seconds
    setInterval(checkForAlerts, 2000);
});
</script>
</body>
</html> 