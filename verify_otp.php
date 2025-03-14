<?php
session_start();
require_once 'config/database.php';

// Set timezone
date_default_timezone_set('Asia/Manila');

if (!isset($_SESSION['temp_user_id']) || !isset($_SESSION['temp_username'])) {
    header('Location: login.php');
    exit();
}

$error = '';
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $otp = $_POST['otp'] ?? '';
    
    if (empty($otp)) {
        $error = "Please enter the OTP code";
    } else {
        try {
            // Debug: Print the input and current time
            error_log("OTP Verification Attempt - User ID: " . $_SESSION['temp_user_id']);
            error_log("Entered OTP: " . $otp);
            error_log("Current time: " . date('Y-m-d H:i:s'));

            // Get the latest unused OTP for this user
            $stmt = $conn->prepare("
                SELECT * FROM two_factor_auth_codes 
                WHERE user_id = ? 
                AND code = ? 
                AND is_used = 0 
                AND expires_at > NOW()
                ORDER BY created_at DESC 
                LIMIT 1
            ");
            $stmt->execute([$_SESSION['temp_user_id'], $otp]);
            $code = $stmt->fetch(PDO::FETCH_ASSOC);

            // Debug: Print the database result
            error_log("Database result: " . ($code ? "Found" : "Not found"));
            if ($code) {
                error_log("Code details: " . print_r($code, true));
            }

            if ($code) {
                // Mark the code as used
                $stmt = $conn->prepare("
                    UPDATE two_factor_auth_codes 
                    SET is_used = 1 
                    WHERE code_id = ?
                ");
                $stmt->execute([$code['code_id']]);

                // Set the actual session variables
                $_SESSION['user_id'] = $_SESSION['temp_user_id'];
                $_SESSION['username'] = $_SESSION['temp_username'];
                $_SESSION['role'] = $_SESSION['temp_role'];

                // Set success message in session
                $_SESSION['otp_success'] = true;

                // Clear temporary session variables
                unset($_SESSION['temp_user_id']);
                unset($_SESSION['temp_username']);
                unset($_SESSION['temp_role']);

                // Redirect based on role
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
                        header('Location: login.php');
                }
                exit();
            } else {
                // Check if the code exists but is expired
                $stmt = $conn->prepare("
                    SELECT * FROM two_factor_auth_codes 
                    WHERE user_id = ? 
                    AND code = ? 
                    AND expires_at <= NOW()
                    ORDER BY created_at DESC 
                    LIMIT 1
                ");
                $stmt->execute([$_SESSION['temp_user_id'], $otp]);
                $expiredCode = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($expiredCode) {
                    error_log("Found expired code: " . print_r($expiredCode, true));
                    $error = "OTP code has expired. Please request a new one.";
                } else {
                    // Check if the code exists but is already used
                    $stmt = $conn->prepare("
                        SELECT * FROM two_factor_auth_codes 
                        WHERE user_id = ? 
                        AND code = ? 
                        AND is_used = 1
                        ORDER BY created_at DESC 
                        LIMIT 1
                    ");
                    $stmt->execute([$_SESSION['temp_user_id'], $otp]);
                    $usedCode = $stmt->fetch(PDO::FETCH_ASSOC);

                    if ($usedCode) {
                        $error = "This OTP code has already been used. Please request a new one.";
                    } else {
                        $error = "Invalid OTP code. Please try again.";
                    }
                }
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
    <title>Verify OTP - HarahQR Sales</title>
    <link rel="icon" href="images-harah/logos.png" type="image/png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Add SweetAlert2 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-image: url('images-harah/bg-harah.jpg');
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

        .otp-input {
            letter-spacing: 0.5em;
            font-size: 1.5em;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <div class="card-header">
                <h4 class="mb-0">Verify OTP</h4>
            </div>
            <div class="card-body">
                <?php if ($error): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle me-2"></i><?php echo $error; ?>
                    </div>
                <?php endif; ?>

                <p class="text-center mb-4">
                    Please enter the 6-digit code sent to your email
                </p>

                <form method="POST">
                    <div class="mb-4">
                        <input type="text" 
                               class="form-control otp-input" 
                               name="otp" 
                               maxlength="6" 
                               pattern="[0-9]{6}" 
                               required 
                               autofocus>
                    </div>

                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-check me-2"></i>Verify OTP
                    </button>
                </form>

                <div class="text-center mt-4">
                    <p class="mb-0">
                        Didn't receive the code? 
                        <a href="resend_otp.php" class="text-primary">Resend</a>
                    </p>
                </div>
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

        <?php if (isset($_SESSION['otp_success'])): ?>
            Swal.fire({
                icon: 'success',
                title: 'Success!',
                text: 'OTP verified successfully. Redirecting...',
                showConfirmButton: false,
                timer: 1500,
                timerProgressBar: true
            });
            <?php unset($_SESSION['otp_success']); ?>
        <?php endif; ?>
    </script>
</body>
</html> 