<?php
session_start();
require_once 'config/database.php';

$order_id = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;
$customer_id = isset($_SESSION['customer_id']) ? $_SESSION['customer_id'] : null;

if (!$order_id || !$customer_id) {
    header('Location: index.php');
    exit();
}

try {
    // Get order details
    $stmt = $conn->prepare("
        SELECT o.*, t.table_number
        FROM orders o
        JOIN tables t ON o.table_id = t.table_id
        WHERE o.order_id = ? AND o.customer_id = ?
    ");
    $stmt->execute([$order_id, $customer_id]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$order) {
        die('Invalid order');
    }

    // Handle feedback submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $rating = isset($_POST['rating']) ? (int)$_POST['rating'] : 0;
        $comment = isset($_POST['comment']) ? trim($_POST['comment']) : '';

        if ($rating >= 1 && $rating <= 5) {
            $stmt = $conn->prepare("
                INSERT INTO customer_feedback (order_id, customer_id, rating, comment)
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([$order_id, $customer_id, $rating, $comment]);

            // Redirect to thank you page
            header('Location: thank_you.php?order_id=' . $order_id);
            exit();
        }
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
    <title>Order Feedback - HarahQR Sales</title>
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
        }

        .feedback-container {
            max-width: 600px;
            margin: 50px auto;
            padding: 30px;
            background: white;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .rating-stars {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin: 20px 0;
        }

        .star {
            font-size: 2rem;
            color: #ddd;
            cursor: pointer;
            transition: color 0.3s ease;
        }

        .star:hover,
        .star.active {
            color: #ffd700;
        }

        .comment-box {
            margin-top: 20px;
        }

        .btn-submit {
            background: var(--primary-color);
            border: none;
            padding: 10px 30px;
            border-radius: 25px;
            color: white;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .btn-submit:hover {
            background: #224abe;
            transform: translateY(-2px);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="feedback-container">
            <h2 class="text-center mb-4">How was your experience?</h2>
            <p class="text-center text-muted">Order #<?php echo $order_id; ?> - Table <?php echo $order['table_number']; ?></p>
            
            <form method="POST" id="feedbackForm">
                <div class="rating-stars">
                    <i class="fas fa-star star" data-rating="1"></i>
                    <i class="fas fa-star star" data-rating="2"></i>
                    <i class="fas fa-star star" data-rating="3"></i>
                    <i class="fas fa-star star" data-rating="4"></i>
                    <i class="fas fa-star star" data-rating="5"></i>
                </div>
                <input type="hidden" name="rating" id="ratingInput" required>
                
                <div class="comment-box">
                    <label for="comment" class="form-label">Additional Comments (Optional)</label>
                    <textarea class="form-control" id="comment" name="comment" rows="4" placeholder="Tell us about your experience..."></textarea>
                </div>

                <div class="text-center mt-4">
                    <button type="submit" class="btn btn-submit">Submit Feedback</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        const stars = document.querySelectorAll('.star');
        const ratingInput = document.getElementById('ratingInput');
        const form = document.getElementById('feedbackForm');

        stars.forEach(star => {
            star.addEventListener('click', () => {
                const rating = star.dataset.rating;
                ratingInput.value = rating;
                
                stars.forEach(s => {
                    s.classList.remove('active');
                    if (s.dataset.rating <= rating) {
                        s.classList.add('active');
                    }
                });
            });
        });

        form.addEventListener('submit', (e) => {
            if (!ratingInput.value) {
                e.preventDefault();
                alert('Please select a rating');
            }
        });
    </script>
</body>
</html> 