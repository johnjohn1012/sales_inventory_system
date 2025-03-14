<?php
$status_class = $table['order_status'] === 'AVAILABLE' ? 'success' : 
    ($table['order_status'] === 'READY' ? 'warning' : 
    ($table['order_status'] === 'COMPLETED' ? 'secondary' : 'danger'));

$status_text = $table['order_status'] === 'AVAILABLE' ? 'Available' : 
    ($table['order_status'] === 'READY' ? 'Order Ready' : 
    ($table['order_status'] === 'COMPLETED' ? 'Needs Cleaning' : 'Occupied'));

$status_icon = $table['order_status'] === 'AVAILABLE' ? 'check' : 
    ($table['order_status'] === 'READY' ? 'bell' : 
    ($table['order_status'] === 'COMPLETED' ? 'broom' : 'users'));

$data_status = $table['order_status'] === 'AVAILABLE' ? 'available' : 
    ($table['order_status'] === 'READY' ? 'ready' : 
    ($table['order_status'] === 'COMPLETED' ? 'needs-cleaning' : 'occupied'));
?>

<div class="table-card" data-status="<?php echo $data_status; ?>">
    <div class="table-number">
        <?php echo htmlspecialchars($table['table_number']); ?>
    </div>
    <div class="table-status">
        <i class="fas fa-<?php echo $status_icon; ?> me-1"></i>
        <?php echo $status_text; ?>
    </div>
    <?php if ($table['order_status'] === 'COMPLETED'): ?>
        <button class="btn btn-light btn-sm clean-button" onclick="markTableClean(<?php echo $table['table_id']; ?>)">
            <i class="fas fa-broom me-1"></i>Mark as Clean
        </button>
    <?php endif; ?>
</div> 