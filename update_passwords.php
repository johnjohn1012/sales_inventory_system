<?php
require_once 'config/database.php';

try {
    // Password to hash
    $password = '1234';
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Update passwords for all users
    $stmt = $conn->prepare("UPDATE users SET password = ?");
    $stmt->execute([$hashed_password]);

    echo "Passwords updated successfully!";
} catch (PDOException $e) {
    echo "Error updating passwords: " . $e->getMessage();
}
?> 