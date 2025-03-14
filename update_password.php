<?php
require_once 'config/database.php';

// Password to hash
$password = '1234';
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

// Update passwords for all users
$stmt = $conn->prepare("UPDATE users SET password = ?");
$stmt->execute([$hashed_password]);

echo "Passwords updated successfully!";
?> 