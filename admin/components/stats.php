<?php
function getStats($conn) {
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

        return [
            'users_count' => $users_count,
            'products_count' => $products_count,
            'orders_count' => $orders_count,
            'total_sales' => $total_sales
        ];
    } catch (PDOException $e) {
        return ['error' => "Database error: " . $e->getMessage()];
    }
}

function displayStats($stats) {
    if (isset($stats['error'])) {
        echo '<div class="alert alert-danger"><i class="fas fa-exclamation-circle me-2"></i>' . $stats['error'] . '</div>';
        return;
    }
?>
    <div class="row">
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon stat-users">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-title">Total Users</div>
                <div class="stat-value"><?php echo $stats['users_count']; ?></div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon stat-products">
                    <i class="fas fa-box"></i>
                </div>
                <div class="stat-title">Total Products</div>
                <div class="stat-value"><?php echo $stats['products_count']; ?></div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon stat-orders">
                    <i class="fas fa-shopping-cart"></i>
                </div>
                <div class="stat-title">Orders Today</div>
                <div class="stat-value"><?php echo $stats['orders_count']; ?></div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-icon stat-sales">
                    <i class="fas fa-dollar-sign"></i>
                </div>
                <div class="stat-title">Sales Today</div>
                <div class="stat-value"><?php echo number_format($stats['total_sales'], 2); ?></div>
            </div>
        </div>
    </div>
<?php
}
