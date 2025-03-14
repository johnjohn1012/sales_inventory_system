<?php
function displayQuickActions() {
?>
    <div class="quick-actions">
        <h5 class="mb-4">Quick Actions</h5>
        <a href="manage_products.php" class="action-card">
            <div class="action-icon">
                <i class="fas fa-box"></i>
            </div>
            <div>
                <h6 class="mb-1">Manage Products</h6>
                <small>Add, edit or remove products</small>
            </div>
        </a>
        <a href="manage_tables.php" class="action-card">
            <div class="action-icon">
                <i class="fas fa-chair"></i>
            </div>
            <div>
                <h6 class="mb-1">Manage Tables</h6>
                <small>Configure restaurant tables</small>
            </div>
        </a>
        <a href="view_orders.php" class="action-card">
            <div class="action-icon">
                <i class="fas fa-receipt"></i>
            </div>
            <div>
                <h6 class="mb-1">View Orders</h6>
                <small>Check all order history</small>
            </div>
        </a>
    </div>
<?php
}
