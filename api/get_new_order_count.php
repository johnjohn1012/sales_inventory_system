<?php
require_once '../config/database.php';

try {
    $stmt = $conn->query("
        SELECT COUNT(*) as count
        FROM orders
        WHERE status = 'PAID'
    ");
    $result = $stmt->fetch();
    echo $result['count'];
} catch (PDOException $e) {
    http_response_code(500);
    echo "0";
}
?> 