<?php
session_start();
require_once 'config/database.php';
require_once 'models/User.php';
require_once 'includes/functions.php';
require_once 'RateLimiter.php';

// Include PHPMailer
require_once 'vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

$base_path = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
if ($base_path === '/' || $base_path === '\\') $base_path = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Always show a generic success message to prevent email enumeration.
    $success_message = "If an account with that email exists, a password reset link has been sent.";

    $database = new Database();
    $conn = $database->getConnection();
    $user = new User($database);
    $rateLimiter = new RateLimiter($conn);

    $email = sanitize_input($_POST['email'] ?? '');
    $ip_address = $_SERVER['REMOTE_ADDR'];

    // Rate limit by IP address to prevent abuse.
    if (!$rateLimiter->isAllowed('password_reset', $ip_address)) {
        // We still show the success message, but we don't perform any action.
        // This prevents an attacker from knowing they've been rate-limited.
    } elseif (!empty($email) && is_valid_email($email)) {
        $rateLimiter->recordAttempt('password_reset', $ip_address);

        $user->email = $email;
        $user_data = $user->findByEmail();

        if ($user_data) {
            $token = $user->generatePasswordResetToken($email);
            if ($token) {
                // Use the centralized function to send the email
                if (!send_password_reset_email($email, $user_data['name'], $token, $base_path)) {
                    // Log the error, but don't expose it to the user.
                    error_log("Failed to send password reset email to {$email}.");
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - Health & Safety Inspection System</title>
    <link rel="icon" type="image/png" href="<?php echo $base_path; ?>/logo/logo.jpeg">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo $base_path; ?>/assets/css/auth.css">
    <style>
        body {
            background: #ffffff !important;
        }
        .login-container {
            background: #ffffff !important;
            backdrop-filter: none !important;
        }
        .login-header h2 {
            color: #1a202c;
            font-weight: 700;
        }
        .btn-primary {
            display: block;
            width: 100%;
            padding: 14px;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            color: #1f2937; /* Dark gray text for better readability on yellow */
            background: linear-gradient(135deg, #fef08a 0%, #facc15 100%); /* Brighter yellow gradient */
            cursor: pointer;
            text-align: center;
            transition: all 0.3s ease;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(250, 204, 21, 0.4);
        }
        /* Update blob colors for white theme */
        .bg-decoration-1 {
            background: radial-gradient(circle, #FFF59D 0%, transparent 70%);
        }
        .bg-decoration-2 {
            background: radial-gradient(circle, #e5e7eb 0%, transparent 70%);
        }
        .bg-decoration-3 {
            background: radial-gradient(circle, #FFF176 0%, transparent 70%);
        }
        .bg-decoration-4 {
            background: radial-gradient(circle, rgba(255, 249, 196, 0.4) 0%, transparent 70%);
        }
    </style>
</head>
<body>

    <div class="bg-decoration bg-decoration-1"></div>
    <div class="bg-decoration bg-decoration-2"></div>
    <div class="bg-decoration bg-decoration-3"></div>
    <div class="bg-decoration bg-decoration-4"></div>

    <img src="<?php echo $base_path; ?>/logo/logo.jpeg" alt="Health & Safety Inspection Watermark" class="watermark-logo">

    <a href="<?php echo $base_path; ?>/main_login.php" class="back-button">
        <i class="fas fa-arrow-left"></i>
        Back to Login
    </a>

    <div class="main-content-wrapper">
        <div class="logo-left">
            <img src="<?php echo $base_path; ?>/logo/logo.jpeg" alt="Health & Safety Inspection Logo">
            <h1>PASSWORD RESET</h1>
            <p class="tagline">Regain Access to Your Account</p>
        </div>

        <div class="login-container">
            <div class="login-header">
                <h2>Forgot Your Password?</h2>
                <p>No problem. Enter your email below and we'll send you a link to reset it.</p>
            </div>

            <?php if (isset($success_message)): ?>
                <div class="success-message">
                    <?php echo $success_message; ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <div class="input-wrapper">
                        <i class="fas fa-envelope text-gray-400"></i>
                        <input type="email" name="email" id="email" required placeholder="Enter your email">
                    </div>
                </div>

                <button type="submit" class="btn-primary">
                    Send Reset Link
                </button>
            </form>

            <p class="login-link">
                Remember your password? <a href="main_login.php">Sign In</a>
            </p>
        </div>
    </div>
</body>
</html>