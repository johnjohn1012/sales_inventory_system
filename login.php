<?php
session_start();
require_once 'config/database.php';
require_once 'config/email.php';

// Set timezone
date_default_timezone_set('Asia/Manila');

// Redirect if already logged in
if (isset($_SESSION['user_id']) && isset($_SESSION['role'])) {
    switch ($_SESSION['role']) {
        case 'ADMIN':
            header('Location: admin/dashboard.php');
            break;
        case 'CASHIER':
            header('Location: cashier/orders.php');
            break;
        case 'KITCHEN':
            header('Location: kitchen/orders.php');
            break;
        case 'WAITER':
            header('Location: waiter/tables.php');
            break;
        default:
            // If role is not recognized, clear session and stay on login page
            session_destroy();
    }
    exit();
}

$error = '';
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = "Please enter both username and password";
    } else {
        try {
            $stmt = $conn->prepare("
                SELECT u.*, e.email 
                FROM users u 
                JOIN employees e ON u.employee_id = e.employee_id 
                WHERE u.username = ?
            ");
            $stmt->execute([$username]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && password_verify($password, $user['password'])) {
                // Generate OTP
                $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
                $expires_at = date('Y-m-d H:i:s', strtotime('+5 minutes'));

                // Debug: Print OTP details
                error_log("Generated OTP: " . $otp);
                error_log("Current time: " . date('Y-m-d H:i:s'));
                error_log("Expires at: " . $expires_at);

                // Save OTP to database
                $stmt = $conn->prepare("
                    INSERT INTO two_factor_auth_codes (user_id, code, expires_at)
                    VALUES (?, ?, ?)
                ");
                $stmt->execute([$user['user_id'], $otp, $expires_at]);

                // Debug: Check if OTP was saved
                $stmt = $conn->prepare("
                    SELECT * FROM two_factor_auth_codes 
                    WHERE user_id = ? 
                    AND code = ? 
                    ORDER BY created_at DESC 
                    LIMIT 1
                ");
                $stmt->execute([$user['user_id'], $otp]);
                $savedCode = $stmt->fetch(PDO::FETCH_ASSOC);
                error_log("Saved OTP in database: " . ($savedCode ? "Yes" : "No"));
                if ($savedCode) {
                    error_log("Saved OTP details: " . print_r($savedCode, true));
                }

                // Send OTP via email
                if (sendOTPEmail($user['email'], $otp)) {
                    // Store temporary session data
                    $_SESSION['temp_user_id'] = $user['user_id'];
                    $_SESSION['temp_username'] = $user['username'];
                    $_SESSION['temp_role'] = $user['role'];

                    // Set success message in session
                    $_SESSION['login_success'] = true;
                    header('Location: verify_otp.php');
                    exit();
                } else {
                    $error = "Failed to send OTP email. Please try again.";
                }
            } else {
                $error = "Invalid username or password";
            }
        } catch (PDOException $e) {
            error_log("Database error: " . $e->getMessage());
            $error = "Database error: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - HarahQR Sales</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Add SweetAlert2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-image: url('bg-harah.jpg');
            background-size: cover;
            background-position: center;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
            padding: 20px;
        }

        .container {
            width: 100%;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            width: 100%;
            max-width: 400px;
            margin: 0 auto;
        }

        .card-header {
            background: linear-gradient(135deg, #4e73df, #224abe);
            color: white;
            border-radius: 15px 15px 0 0 !important;
            padding: 1.5rem;
            text-align: center;
        }

        .card-body {
            padding: 2rem;
        }

        .form-control {
            border-radius: 10px;
            border: 2px solid #e0e0e0;
            padding: 10px 15px;
        }

        .form-control:focus {
            border-color: #4e73df;
            box-shadow: none;
        }

        .btn-primary {
            background: #4e73df;
            border: none;
            padding: 10px 20px;
            border-radius: 25px;
            width: 100%;
        }

        .btn-primary:hover {
            background: #224abe;
        }

        .alert {
            border-radius: 10px;
            border: none;
        }

        .alert-danger {
            background: #fce3e3;
            color: #e74a3b;
        }
    </style>
</head>
<body>
    <div class="container" >
        <div class="card">
            <div class="card-header">
                <h4 class="mb-0">Login to Harah QR Sales and Inventory System</h4>
            </div>
            <div class="card-body">
                <?php if ($error): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle me-2"></i><?php echo $error; ?>
                    </div>
                <?php endif; ?>

                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label">Username</label>
                        <input type="text" 
                               class="form-control" 
                               name="username" 
                               required 
                               autofocus>
                    </div>

                    <div class="mb-4">
                        <label class="form-label">Password</label>
                        <input type="password" 
                               class="form-control" 
                               name="password" 
                               required>
                    </div>

                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-sign-in-alt me-2"></i>Login
                    </button>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Add SweetAlert2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        <?php if ($error): ?>
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: '<?php echo $error; ?>',
                confirmButtonColor: '#4e73df'
            });
        <?php endif; ?>

        <?php if (isset($_SESSION['login_success'])): ?>
            Swal.fire({
                icon: 'success',
                title: 'Success!',
                text: 'Please check your email for the OTP code.',
                confirmButtonColor: '#4e73df'
            });
            <?php unset($_SESSION['login_success']); ?>
        <?php endif; ?>
    </script>
</body>
</html> 