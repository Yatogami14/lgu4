<?php
session_start();
require_once 'config/database.php';
require_once 'models/User.php';

// Include PHPMailer
require_once 'vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Rate Limiting: Allow one request every 60 seconds to prevent abuse.
$rate_limit_seconds = 60;
if (isset($_SESSION['last_password_reset_request']) && (time() - $_SESSION['last_password_reset_request'] < $rate_limit_seconds)) {
    $time_left = $rate_limit_seconds - (time() - $_SESSION['last_password_reset_request']);
    $error_message = "You have requested a password reset recently. Please wait {$time_left} more seconds before trying again.";
} elseif ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Set the timestamp immediately to apply rate limit on the next attempt.
    $_SESSION['last_password_reset_request'] = time();

    $database = new Database();
    $user = new User($database);

    $email = $_POST['email'];
    $token = $user->generatePasswordResetToken($email);

    if ($token) {
        $reset_link = "http://" . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/\\') . "/reset_password.php?token=" . $token;

        // --- Send Email with PHPMailer ---
        $mailerConfig = require 'config/mailer.php';
        $mail = new PHPMailer(true);
        try {
            // --- Debugging Disabled ---
            // The connection is working. Debugging is no longer needed.
            // Set to SMTP::DEBUG_SERVER to re-enable if issues persist.
            $mail->SMTPDebug = SMTP::DEBUG_OFF;

            //Server settings
            $mail->isSMTP();
            $mail->Host       = $mailerConfig['host'];
            $mail->SMTPAuth   = $mailerConfig['smtp_auth'];
            $mail->Username   = $mailerConfig['username'];
            $mail->Password   = $mailerConfig['password'];
            if ($mailerConfig['smtp_secure'] === 'tls') {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            } elseif ($mailerConfig['smtp_secure'] === 'ssl') {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            }
            $mail->Port       = $mailerConfig['port'];

            //Recipients
            $mail->setFrom($mailerConfig['from_email'], $mailerConfig['from_name']);
            $mail->addAddress($email);

            //Content
            $mail->isHTML(true);
            $mail->Subject = 'Password Reset Request';
            $mail->Body    = "
                <p>Hello,</p>
                <p>You requested a password reset for your account. Please click the link below to reset your password:</p>
                <p><a href='{$reset_link}'>{$reset_link}</a></p>
                <p>If you did not request this, please ignore this email.</p>
                <p>This link will expire in 1 hour.</p>
                <br>
                <p>Thank you,<br>The LGU Inspection Platform Team</p>
            ";
            $mail->AltBody = "You requested a password reset. Copy and paste this link into your browser: {$reset_link}";

            $mail->send();
            $success_message = "If an account with that email exists, a password reset link has been sent.";
        } catch (Exception $e) {
            // Don't expose detailed errors to the user. Log them instead.
            $errorMessage = "Mailer Error: {$mail->ErrorInfo}";
            error_log($errorMessage);
            // For debugging, we can display the error. REMOVE this line in production.
            $error_message = "Could not send password reset email. Please contact support.";
        }

    } else {
        // Generic message to prevent email enumeration
        $success_message = "If an account with that email exists, a password reset link has been sent.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - Digital Health & Safety Inspection Platform</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .form-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
        }
    </style>
</head>
<body class="font-sans">
    <div class="form-card p-8 w-full max-w-md mx-4">
        <div class="text-center mb-8">
            <h1 class="text-3xl font-bold text-gray-800 mb-2">Forgot Your Password?</h1>
            <p class="text-gray-600">Enter your email to receive a reset link.</p>
        </div>

        <?php if (isset($error_message)): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg mb-6">
                <?php echo $error_message; ?>
            </div>
        <?php endif; ?>

        <?php if (isset($success_message)): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg mb-6">
                <?php echo $success_message; ?>
            </div>
        <?php endif; ?>

        <form method="POST" class="space-y-6">
            <div>
                <label for="email" class="block text-sm font-medium text-gray-700 mb-2">Email Address</label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <i class="fas fa-envelope text-gray-400"></i>
                    </div>
                    <input type="email" name="email" id="email" required 
                           class="pl-10 w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200"
                           placeholder="Enter your email">
                </div>
            </div>

            <button type="submit" 
                    class="w-full bg-gradient-to-r from-blue-600 to-purple-600 text-white py-3 px-4 rounded-lg font-medium hover:from-blue-700 hover:to-purple-700 transition duration-200 transform hover:scale-105">
                <i class="fas fa-paper-plane mr-2"></i>Send Reset Link
            </button>
        </form>

        <div class="mt-6 text-center">
            <p class="text-gray-600">Remember your password? 
                <a href="main_login.php" class="text-blue-600 hover:text-blue-800 font-medium">Sign In</a>
            </p>
        </div>
    </div>
</body>
</html>