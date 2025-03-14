<?php
require_once '../config/database.php';

try {
    $stmt = $conn->query("SELECT * FROM tables ORDER BY table_number");
    $tables = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($tables as $table) {
        echo '<div class="table-card" data-table-id="' . $table['table_id'] . '">';
        echo '<div class="d-flex justify-content-between align-items-center mb-3">';
        echo '<h5 class="mb-0">Table ' . htmlspecialchars($table['table_number']) . '</h5>';
        echo '<span class="status-badge status-' . strtolower($table['status']) . '">';
        echo ucfirst(strtolower($table['status']));
        echo '</span>';
        echo '</div>';
        echo '</div>';
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo "Error: " . $e->getMessage();
}
?> 