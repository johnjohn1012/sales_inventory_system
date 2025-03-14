<?php
require_once 'config/database.php';

try {
    // First, let's check if the database exists
    $conn->query("USE harah_sales");
    
    // Check if tables exist
    $tables = ['employees', 'users'];
    foreach ($tables as $table) {
        $result = $conn->query("SHOW TABLES LIKE '$table'");
        if ($result->rowCount() == 0) {
            die("Table '$table' does not exist!");
        }
    }

    // Clear existing data
    $conn->query("DELETE FROM users");
    $conn->query("DELETE FROM employees");

    // Insert employees
    $stmt = $conn->prepare("
        INSERT INTO employees (first_name, last_name, position, contact_number, email, address, hire_date, status) 
        VALUES 
        ('Margo', 'Admin', 'ADMIN', '09123456789', 'margo@harahqr.com', 'Manila', '2024-01-01', 'ACTIVE'),
        ('Clemens', 'Kitchen', 'KITCHEN', '09123456790', 'clemens@harahqr.com', 'Manila', '2024-01-01', 'ACTIVE'),
        ('Francis', 'Cashier', 'CASHIER', '09123456791', 'francis@harahqr.com', 'Manila', '2024-01-01', 'ACTIVE'),
        ('Khendal', 'Waiter', 'WAITER', '09123456792', 'khendal@harahqr.com', 'Manila', '2024-01-01', 'ACTIVE')
    ");
    $stmt->execute();

    // Get the employee IDs
    $employee_ids = $conn->query("SELECT employee_id FROM employees ORDER BY employee_id")->fetchAll(PDO::FETCH_COLUMN);

    // Insert users with proper password hash
    $password = '1234';
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    
    $stmt = $conn->prepare("
        INSERT INTO users (employee_id, username, password, role) 
        VALUES 
        (?, 'margo', ?, 'ADMIN'),
        (?, 'clemens', ?, 'KITCHEN'),
        (?, 'francis', ?, 'CASHIER'),
        (?, 'khendal', ?, 'WAITER')
    ");
    
    $stmt->execute([
        $employee_ids[0], $hashed_password,
        $employee_ids[1], $hashed_password,
        $employee_ids[2], $hashed_password,
        $employee_ids[3], $hashed_password
    ]);

    // Verify the data
    echo "<h2>Verifying Data:</h2>";
    
    // Check employees
    echo "<h3>Employees:</h3>";
    $employees = $conn->query("SELECT * FROM employees")->fetchAll(PDO::FETCH_ASSOC);
    echo "<pre>";
    print_r($employees);
    echo "</pre>";

    // Check users
    echo "<h3>Users:</h3>";
    $users = $conn->query("SELECT * FROM users")->fetchAll(PDO::FETCH_ASSOC);
    echo "<pre>";
    print_r($users);
    echo "</pre>";

    echo "<h2>Data has been reset and verified!</h2>";
    echo "<p>You can now try logging in with:</p>";
    echo "<ul>";
    echo "<li>Username: margo, Password: 1234</li>";
    echo "<li>Username: clemens, Password: 1234</li>";
    echo "<li>Username: francis, Password: 1234</li>";
    echo "<li>Username: khendal, Password: 1234</li>";
    echo "</ul>";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?> 