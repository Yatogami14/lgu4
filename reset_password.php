<?php
session_start();
require_once 'config/database.php';
require_once 'models/User.php';

$database = new Database();
$user = new User($database);

$token = $_GET['token'] ?? $_POST['token'] ?? null;
$is_token_valid = false;
$user_id = null;

if ($token) {
    $user_id = $user->validatePasswordResetToken($token);
    if ($user_id) {
        $is_token_valid = true;
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && $is_token_valid) {
    if (!empty($_POST['new_password']) && $_POST['new_password'] === $_POST['confirm_new_password']) {
        if (strlen($_POST['new_password']) >= 6) {
            if ($user->updatePassword($user_id, $_POST['new_password'])) {
                $_SESSION['success_message'] = 'Your password has been reset successfully. Please log in.';
                header('Location: main_login.php');
                exit;
            } else {
                $error_message = 'Failed to update password. Please try again.';
            }
        } else {
            $error_message = 'Password must be at least 6 characters long.';
        }
    } else {
        $error_message = 'Passwords do not match or are empty.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - Health & Safety Inspection System</title>
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

    <div class="main-content-wrapper" style="justify-content: center;">
        <div class="login-container" style="animation: fadeIn 1s ease forwards; opacity: 1; position: relative; right: auto; top: auto; transform: none;">
            <div class="login-header">
                <h2>Reset Your Password</h2>
            </div>

            <?php if (isset($error_message)): ?>
                <div class="error-message">
                    <?php echo $error_message; ?>
                </div>
            <?php endif; ?>

            <?php if ($is_token_valid): ?>
                <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                    <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
                    
                    <div class="form-group">
                        <label for="new_password">New Password</label>
                        <div class="input-wrapper">
                            <i class="fas fa-lock"></i>
                            <input type="password" name="new_password" id="new_password" required minlength="8" placeholder="Enter new password">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="confirm_new_password">Confirm New Password</label>
                        <div class="input-wrapper">
                            <i class="fas fa-lock"></i>
                            <input type="password" name="confirm_new_password" id="confirm_new_password" required placeholder="Confirm new password">
                        </div>
                    </div>

                    <button type="submit" class="btn-primary">
                        Reset Password
                    </button>
                </form>
            <?php else: ?>
                <div class="error-message" style="text-align: center;">
                    <p>This password reset link is invalid or has expired.</p>
                    <a href="forgot_password.php" class="login-link" style="display: inline-block; margin-top: 1rem; color: var(--primary-dark); font-weight: 600;">Request a new link</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>