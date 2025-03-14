<?php
session_start();
require_once 'config/database.php';

$order_id = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;

if (!$order_id) {
    header('Location: index.php');
    exit();
}

try {
    // Get order details
    $stmt = $conn->prepare("
        SELECT o.*, t.table_number
        FROM orders o
        JOIN tables t ON o.table_id = t.table_id
        WHERE o.order_id = ?
    ");
    $stmt->execute([$order_id]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$order) {
        die('Invalid order');
    }
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thank You - HarahQR Sales</title>
    <link rel="icon" href="images-harah/logos.png" type="image/png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #4e73df;
            --secondary-color: #858796;
            --success-color: #1cc88a;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f8f9fc;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .thank-you-container {
            max-width: 600px;
            margin: 20px;
            padding: 40px;
            background: white;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
        }

        .thank-you-icon {
            font-size: 4rem;
            color: var(--success-color);
            margin-bottom: 20px;
        }

        .order-details {
            background: #f8f9fc;
            border-radius: 10px;
            padding: 20px;
            margin: 20px 0;
        }

        .btn-home {
            background: var(--primary-color);
            border: none;
            padding: 12px 30px;
            border-radius: 25px;
            color: white;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .btn-home:hover {
            background: #224abe;
            transform: translateY(-2px);
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .fade-in {
            animation: fadeIn 0.5s ease forwards;
        }
    </style>
</head>
<body>
    <div class="thank-you-container fade-in">
        <i class="fas fa-check-circle thank-you-icon"></i>
        <h2 class="mb-4">Thank You!</h2>
        <p class="text-muted mb-4">Your feedback has been recorded. We appreciate your input!</p>
        
        <div class="order-details">
            <h5>Order Details</h5>
            <p class="mb-1">Order #<?php echo $order_id; ?></p>
            <p class="mb-1">Table <?php echo $order['table_number']; ?></p>
            <p class="mb-0">Total Amount: ₱<?php echo number_format($order['total_amount'], 2); ?></p>
        </div>

        <div class="mt-4">
            <a href="index.php" class="btn btn-home">
                <i class="fas fa-home me-2"></i>Return to Home
            </a>
        </div>
    </div>

    <script>
        // Redirect to home page after 5 seconds
        setTimeout(() => {
            window.location.href = 'index.php';
        }, 5000);
    </script>
</body>
</html> 