<?php
require_once 'config/database.php';

try {
    // First, check if tables already exist
    $stmt = $conn->query("SELECT COUNT(*) as count FROM tables");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result['count'] == 0) {
        // Insert 5 tables with QR codes
        for ($i = 1; $i <= 5; $i++) {
            $qr_code = 'table_' . $i . '_' . uniqid();
            $stmt = $conn->prepare("INSERT INTO tables (table_number, qr_code, status) VALUES (?, ?, 'AVAILABLE')");
            $stmt->execute([$i, $qr_code]);
        }
        echo "Tables created successfully!";
    } else {
        echo "Tables already exist in the database.";
    }
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?> 