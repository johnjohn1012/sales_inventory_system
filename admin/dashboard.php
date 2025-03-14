<?php
session_start();
require_once '../config/database.php';
require_once 'components/stats.php';
require_once 'components/recent_orders.php';
require_once 'components/quick_actions.php';
require_once 'components/notification_modal.php';

// Check if user is logged in and has admin role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'ADMIN') {
    header('Location: ../login.php');
    exit();
}

// Get all dashboard data
$stats = getStats($conn);
$recent_orders = getRecentOrders($conn);

// Get statistics
try {
    // Total users
    $stmt = $conn->query("SELECT COUNT(*) as count FROM users");
    $users_count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

    // Total products
    $stmt = $conn->query("SELECT COUNT(*) as count FROM products");
    $products_count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

    // Total orders today
    $stmt = $conn->query("SELECT COUNT(*) as count, SUM(total_amount) as total FROM orders WHERE DATE(created_at) = CURDATE()");
    $orders = $stmt->fetch(PDO::FETCH_ASSOC);
    $orders_count = $orders['count'] ?? 0;
    $total_sales = $orders['total'] ?? 0;

    // Recent orders
    $stmt = $conn->query("
        SELECT o.*, t.table_number 
        FROM orders o 
        LEFT JOIN tables t ON o.table_id = t.table_id 
        ORDER BY o.created_at DESC 
        LIMIT 5
    ");
    $recent_orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $stats['error'] = "Database error: " . $e->getMessage();
}
?>

<?php include 'components/header.php'; ?>
<body>
    <?php include 'components/navbar.php'; ?>
    
    <div class="container mt-4">
        <?php if (isset($stats['error'])): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle me-2"></i>
                <?php echo $stats['error']; ?>
            </div>
        <?php endif; ?>

        <?php displayStats($stats); ?>

        <div class="row mt-4">
            <div class="col-md-4">
                <?php displayQuickActions(); ?>
            </div>
            <div class="col-md-8">
                <?php displayRecentOrders($recent_orders); ?>
            </div>
        </div>
    </div>

    <?php include 'components/footer.php'; ?>
    <?php displayNotificationModal(); ?>

    <!-- jQuery first, then Bootstrap Bundle (includes Popper) -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Initialize all dropdowns
        document.addEventListener('DOMContentLoaded', function() {
            var dropdowns = document.querySelectorAll('.dropdown-toggle');
            dropdowns.forEach(function(dropdown) {
                new bootstrap.Dropdown(dropdown);
            });
        });

        // Notifications functionality
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

        // Initialize notifications
        $(document).ready(function() {
            setInterval(fetchNotifications, 30000); // Check for new notifications every 30 seconds
            fetchNotifications(); // Initial fetch
            
            $('#notificationModal').on('show.bs.modal', function () {
                fetchNotifications();
            });
        });
    </script>
</body>
</html>