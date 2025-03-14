<?php
function getRecentOrders($conn) {
    try {
        $stmt = $conn->query("
            SELECT o.*, t.table_number 
            FROM orders o 
            LEFT JOIN tables t ON o.table_id = t.table_id 
            ORDER BY o.created_at DESC 
            LIMIT 5
        ");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return ['error' => "Database error: " . $e->getMessage()];
    }
}

function displayRecentOrders($orders) {
    if (isset($orders['error'])) {
        echo '<div class="alert alert-danger"><i class="fas fa-exclamation-circle me-2"></i>' . $orders['error'] . '</div>';
        return;
    }
?>
    <div class="recent-orders">
        <h5 class="mb-4">Recent Orders</h5>
        <?php foreach ($orders as $order): ?>
            <div class="order-item">
                <div class="order-info">
                    <div class="order-table">Table <?php echo htmlspecialchars($order['table_number']); ?></div>
                    <div class="order-time"><?php echo date('M j, Y g:i A', strtotime($order['created_at'])); ?></div>
                </div>
                <div class="order-status status-<?php echo strtolower($order['status']); ?>">
                    <?php echo ucfirst(strtolower($order['status'])); ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php
}
