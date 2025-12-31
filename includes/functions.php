<?php
require_once 'vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Load environment variables if .env file exists
if (file_exists(__DIR__ . '/../.env')) {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
    $dotenv->load();
}

/**
 * Sanitize input data
 */
function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

/**
 * Generate a verification code
 */
function generate_verification_code() {
    // Generate a secure, URL-safe random token
    return bin2hex(random_bytes(32));
}

/**
 * Send verification email with link
 */
function send_verification_email_with_link($email, $first_name, $verification_code, $base_path = '') {
    // Load mailer configuration
    $mailerConfig = require __DIR__ . '/../config/mailer.php';
    // Load app configuration for the base URL
    $appConfig = require __DIR__ . '/../config/app.php';
    $mail = new PHPMailer(true);

    try {
        // Server settings
        $mail->isSMTP();
        // Set to SMTP::DEBUG_SERVER to see full transaction logs for debugging
        $mail->SMTPDebug = \PHPMailer\PHPMailer\SMTP::DEBUG_OFF;
        $mail->Host = $mailerConfig['host'];
        $mail->SMTPAuth = $mailerConfig['smtp_auth'];
        $mail->Username = $mailerConfig['username'];
        $mail->Password = $mailerConfig['password'];
        if ($mailerConfig['smtp_secure'] === 'tls') {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        } elseif ($mailerConfig['smtp_secure'] === 'ssl') {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        }
        $mail->Port = $mailerConfig['port'];

        // Recipients
        $mail->setFrom($mailerConfig['from_email'], $mailerConfig['from_name']);
        $mail->addAddress($email, $first_name);

        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Verify Your Email - Health & Safety Inspection System';

        $verification_link = rtrim($appConfig['url'], '/') . $base_path . "/verify_email.php?code=$verification_code&email=" . urlencode($email);

        $mail->Body = "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: linear-gradient(135deg, #FFF9C4 0%, #FFF59D 100%); padding: 20px; text-align: center; border-radius: 10px 10px 0 0; }
                .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 10px 10px; }
                .button { display: inline-block; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; margin: 20px 0; }
                .footer { text-align: center; margin-top: 20px; font-size: 12px; color: #666; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>Health & Safety Inspection System</h1>
                </div>
                <div class='content'>
                    <h2>Welcome, $first_name!</h2>
                    <p>Thank you for registering with the Health & Safety Inspection System. To complete your registration, please verify your email address by clicking the button below:</p>
                    <p style='text-align: center;'>
                        <a href='$verification_link' class='button'>Verify Email Address</a>
                    </p>
                    <p>If the button doesn't work, you can also copy and paste this link into your browser:</p>
                    <p><a href='$verification_link'>$verification_link</a></p>
                    <p>This verification link will expire in 30 minutes for security reasons.</p>
                    <p>If you didn't create an account, please ignore this email.</p>
                </div>
                <div class='footer'>
                    <p>&copy; 2024 Health & Safety Inspection System. All rights reserved.</p>
                </div>
            </div>
        </body>
        </html>";

        $mail->AltBody = "Welcome, $first_name!\\n\\nThank you for registering with the Health & Safety Inspection System. To complete your registration, please verify your email address by visiting: $verification_link\\n\\nThis verification link will expire in 30 minutes.\\n\\nIf you didn't create an account, please ignore this email.";

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Email sending failed: " . $mail->ErrorInfo);
        return false;
    }
}

/**
 * Send password reset email
 */
function send_password_reset_email($email, $first_name, $reset_token, $base_path = '') {
    // Load mailer configuration
    $mailerConfig = require __DIR__ . '/../config/mailer.php';
    // Load app configuration for the base URL
    $appConfig = require __DIR__ . '/../config/app.php';
    $mail = new PHPMailer(true);

    try {
        // Server settings
        $mail->isSMTP();
        // Set to SMTP::DEBUG_SERVER to see full transaction logs for debugging
        $mail->SMTPDebug = \PHPMailer\PHPMailer\SMTP::DEBUG_OFF;
        $mail->Host = $mailerConfig['host'];
        $mail->SMTPAuth = $mailerConfig['smtp_auth'];
        $mail->Username = $mailerConfig['username'];
        $mail->Password = $mailerConfig['password'];
        if ($mailerConfig['smtp_secure'] === 'tls') {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        } elseif ($mailerConfig['smtp_secure'] === 'ssl') {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        }
        $mail->Port = $mailerConfig['port'];

        // Recipients
        $mail->setFrom($mailerConfig['from_email'], $mailerConfig['from_name']);
        $mail->addAddress($email, $first_name);

        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Password Reset - Health & Safety Inspection System';

        $reset_link = rtrim($appConfig['url'], '/') . $base_path . "/reset_password.php?token=$reset_token";

        $mail->Body = "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: linear-gradient(135deg, #FFF9C4 0%, #FFF59D 100%); padding: 20px; text-align: center; border-radius: 10px 10px 0 0; }
                .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 10px 10px; }
                .button { display: inline-block; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; margin: 20px 0; }
                .footer { text-align: center; margin-top: 20px; font-size: 12px; color: #666; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>Health & Safety Inspection System</h1>
                </div>
                <div class='content'>
                    <h2>Password Reset Request</h2>
                    <p>Hello $first_name,</p>
                    <p>You have requested to reset your password. Click the button below to reset it:</p>
                    <p style='text-align: center;'>
                        <a href='$reset_link' class='button'>Reset Password</a>
                    </p>
                    <p>If the button doesn't work, you can also copy and paste this link into your browser:</p>
                    <p><a href='$reset_link'>$reset_link</a></p>
                    <p>This reset link will expire in 1 hour for security reasons.</p>
                    <p>If you didn't request this password reset, please ignore this email.</p>
                </div>
                <div class='footer'>
                    <p>&copy; 2024 Health & Safety Inspection System. All rights reserved.</p>
                </div>
            </div>
        </body>
        </html>";

        $mail->AltBody = "Hello $first_name,\\n\\nYou have requested to reset your password. Visit this link to reset it: $reset_link\\n\\nThis reset link will expire in 1 hour.\\n\\nIf you didn't request this password reset, please ignore this email.";

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Password reset email sending failed: " . $mail->ErrorInfo);
        return false;
    }
}

/**
 * Validate email format
 */
function is_valid_email($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Generate a secure random token
 */
function generate_secure_token($length = 32) {
    return bin2hex(random_bytes($length));
}

/**
 * Hash a password with proper cost
 */
function hash_password($password) {
    return password_hash($password, PASSWORD_DEFAULT, ['cost' => 12]);
}

/**
 * Verify password against hash
 */
function verify_password($password, $hash) {
    return password_verify($password, $hash);
}

/**
 * Check if password needs rehashing
 */
function password_needs_rehashing($hash) {
    return password_needs_rehash($hash, PASSWORD_DEFAULT, ['cost' => 12]);
}
?>
