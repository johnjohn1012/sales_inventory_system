<?php
session_start();
require_once '../config/database.php';

// Check if user is logged in and has waiter role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'WAITER') {
    header('Location: ../login.php');
    exit();
}

// Get all tables with their status
try {
    $stmt = $conn->query("SELECT * FROM tables ORDER BY table_number");
    $tables = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Database error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tables Management - HarahQR Sales</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
    <style>
        .restaurant-layout {
            max-width: 1200px;
            margin: 40px auto;
            padding: 40px;
            background: #f8f9fa;
            border-radius: 15px;
            position: relative;
        }

        .floor-plan {
            background: #fff;
            border: 3px solid #2c3e50;
            border-radius: 20px;
            padding: 40px;
            position: relative;
            min-height: 600px;
        }

        .entrance {
            position: absolute;
            bottom: -30px;
            left: 50%;
            transform: translateX(-50%);
            background: #e74c3c;
            color: white;
            padding: 5px 20px;
            border-radius: 10px;
            font-weight: 500;
            z-index: 1;
        }

        .entrance::before {
            content: '';
            position: absolute;
            top: -20px;
            left: 50%;
            transform: translateX(-50%);
            border-left: 15px solid transparent;
            border-right: 15px solid transparent;
            border-bottom: 20px solid #e74c3c;
        }

        .table-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 40px;
            padding: 20px;
        }

        .table-card {
            background: white;
            border: 2px solid #ddd;
            border-radius: 10px;
            padding: 20px;
            text-align: center;
            position: relative;
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }

        .table-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }

        .table-number {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 10px;
            color: #2c3e50;
        }

        .table-status {
            padding: 8px 15px;
            border-radius: 20px;
            font-weight: 500;
            margin-bottom: 10px;
            transition: all 0.3s;
        }

        .status-available {
            background: #4CAF50;
            color: white;
        }

        .status-occupied {
            background: #F44336;
            color: white;
        }

        .status-ready {
            background: #FFC107;
            color: black;
        }

        .status-cleaning {
            background: #9E9E9E;
            color: white;
        }

        .wall {
            position: absolute;
            background: #2c3e50;
        }

        .wall-top, .wall-bottom {
            height: 3px;
            left: 0;
            right: 0;
        }

        .wall-left, .wall-right {
            width: 3px;
            top: 0;
            bottom: 0;
        }

        .wall-top { top: 0; }
        .wall-bottom { bottom: 0; }
        .wall-left { left: 0; }
        .wall-right { right: 0; }

        .dropdown-menu {
            width: 200px;
            border-radius: 10px;
            box-shadow: 0 3px 15px rgba(0,0,0,0.2);
            border: none;
            padding: 8px;
        }

        .dropdown-item {
            padding: 12px 15px;
            cursor: pointer;
            border-radius: 8px;
            transition: all 0.2s;
        }

        .dropdown-item i {
            margin-right: 10px;
            width: 20px;
        }

        .dropdown-item:hover {
            background-color: #f8f9fa;
            transform: translateX(5px);
        }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark">
    <div class="container">
        <a class="navbar-brand" href="#">
            <i class="fas fa-chair me-2"></i>Tables Management
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

    <div class="restaurant-layout">
        <div class="floor-plan">
            <div class="wall wall-top"></div>
            <div class="wall wall-bottom"></div>
            <div class="wall wall-left"></div>
            <div class="wall wall-right"></div>
            <div class="entrance">
                <i class="fas fa-door-open me-2"></i>Entrance
            </div>
            <div class="table-grid">
                <?php foreach($tables as $table): ?>
                <div class="table-card" id="table-<?php echo $table['table_id']; ?>" onclick="showDropdown(event, <?php echo $table['table_id']; ?>)">
                    <div class="table-number">
                        <i class="fas fa-utensils me-2"></i>
                        Table <?php echo $table['table_number']; ?>
                    </div>
                    <div class="table-status status-<?php echo strtolower($table['status']); ?>" id="status-<?php echo $table['table_id']; ?>">
                        <i class="fas fa-circle me-2"></i>
                        <?php echo ucfirst(strtolower($table['status'])); ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<!-- Bootstrap Dropdown Menu (Hidden by default) -->
<div class="dropdown-menu" id="statusDropdown">
    <a class="dropdown-item" onclick="updateStatus(event, 'available')">
        <i class="fas fa-check text-success"></i> Available
    </a>
    <a class="dropdown-item" onclick="updateStatus(event, 'occupied')">
        <i class="fas fa-users text-danger"></i> Occupied
    </a>
    <a class="dropdown-item" onclick="updateStatus(event, 'ready')">
        <i class="fas fa-bell text-warning"></i> Ready
    </a>
    <a class="dropdown-item" onclick="updateStatus(event, 'cleaning')">
        <i class="fas fa-broom text-secondary"></i> Needs Cleaning
    </a>
</div>

<audio id="newOrderSound" src="../assets/notification.mp3"></audio>

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

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
let currentTable = null;
const dropdown = document.getElementById('statusDropdown');

function showDropdown(event, tableId) {
    event.stopPropagation();
    currentTable = tableId;
    
    // Position the dropdown below the clicked table
    const tableCard = document.getElementById(`table-${tableId}`);
    const rect = tableCard.getBoundingClientRect();
    
    dropdown.style.position = 'absolute';
    dropdown.style.display = 'block';
    dropdown.style.top = `${rect.bottom + window.scrollY}px`;
    dropdown.style.left = `${rect.left + (rect.width - dropdown.offsetWidth) / 2}px`;
}

function updateStatus(event, status) {
    event.stopPropagation();
    if (!currentTable) return;

    const statusElement = document.getElementById(`status-${currentTable}`);
    const tableCard = document.getElementById(`table-${currentTable}`);
    
    // Remove all existing status classes
    statusElement.classList.remove('status-available', 'status-occupied', 'status-ready', 'status-cleaning');
    
    // Add new status class
    statusElement.classList.add(`status-${status}`);
    
    // Update status text
    statusElement.textContent = status.charAt(0).toUpperCase() + status.slice(1);
    
    // Hide dropdown
    dropdown.style.display = 'none';
    
    // Send update to server
    $.ajax({
        url: '../api/update_table_status.php',
        method: 'POST',
        data: {
            table_id: currentTable,
            status: status.toUpperCase()
        },
        success: function(response) {
            console.log('Table status updated successfully');
        },
        error: function() {
            alert('Error updating table status. Please try again.');
        }
    });
}

// Close dropdown when clicking outside
document.addEventListener('click', function(event) {
    if (!event.target.closest('.table-card') && !event.target.closest('.dropdown-menu')) {
        dropdown.style.display = 'none';
    }
});

function checkNewOrders() {
    $.ajax({
        url: '../api/get_waiter_notifications.php',
        method: 'GET',
        success: function(response) {
            if (response.new_orders) {
                document.getElementById('newOrderSound').play();
                location.reload();
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

$(document).ready(function() {
    setInterval(checkNewOrders, 30000);
    setInterval(fetchNotifications, 30000);
    fetchNotifications();
    
    $('#notificationModal').on('show.bs.modal', function () {
        fetchNotifications();
    });
});
</script>
</body>
</html> 