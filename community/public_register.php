<?php
session_start();
require_once '../config/database.php';
require_once '../models/User.php';

// Check if user is already logged in
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$database = new Database();
$user = new User($database);

// Determine base path for assets
$base_path = rtrim(dirname(dirname($_SERVER['SCRIPT_NAME'])), '/\\');
if ($base_path === '/' || $base_path === '\\') $base_path = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user->name = $_POST['name'];
    $user->email = $_POST['email'];
    $user->password = $_POST['password'];
    $user->role = 'community_user'; // Automatically set role
    $user->department = null; 
    $user->certification = 'Community Member';

    if ($_POST['password'] !== $_POST['confirm_password']) {
        $error_message = "Passwords do not match.";
    } else {
        // The create method returns an array ['success' => bool, 'error' => string]
        $creation_result = $user->create();
        if ($creation_result['success']) {
            // Set a success message in the session and redirect to the main login page
            // This pattern is consistent with other registration forms
            $_SESSION['success_message'] = "Community account created successfully! You can now log in.";
            header('Location: ../main_login.php');
            exit();
        } else {
            $error_message = $creation_result['error'] ?: "Failed to create account. Please try again.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Community Registration - Digital Health & Safety Inspection Platform</title>
    <link rel="icon" type="image/png" href="<?php echo $base_path; ?>/logo/logo.jpeg">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo $base_path; ?>/assets/css/public_register.css">
</head>
<body>
    <div class="bg-decoration bg-decoration-1"></div>
    <div class="bg-decoration bg-decoration-2"></div>
    <div class="bg-decoration bg-decoration-3"></div>
    <div class="bg-decoration bg-decoration-4"></div>

    <img src="<?php echo $base_path; ?>/logo/logo.jpeg" alt="Health & Safety Inspection Watermark" class="watermark-logo">

    <a href="<?php echo $base_path; ?>/register_options.php" class="back-button">
        <i class="fas fa-arrow-left"></i>
        Back to Options
    </a>

    <div class="main-content-wrapper">
        <div class="logo-left">
            <img src="<?php echo $base_path; ?>/logo/logo.jpeg" alt="Health & Safety Inspection Logo">
            <h1>JOIN OUR COMMUNITY</h1>
            <p class="tagline">Report concerns and view safety ratings.</p>
        </div>

        <div class="login-container">
            <div class="login-header">
                <h2>Community Registration</h2>
                <p>Create your account to get started.</p>
            </div>

            <?php if (isset($error_message)): ?>
                <div class="error-message">
                    <?php echo $error_message; ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" id="registerForm">
                <div class="form-group">
                    <label for="name">Full Name</label>
                    <div class="input-wrapper">
                        <i class="fas fa-user"></i>
                        <input type="text" name="name" id="name" required placeholder="Enter your full name">
                    </div>
                </div>
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <div class="input-wrapper">
                        <i class="fas fa-envelope"></i>
                        <input type="email" name="email" id="email" required placeholder="Enter your email">
                    </div>
                </div>
                <div class="form-group">
                    <label for="password">Password</label>
                    <div class="input-wrapper">
                        <i class="fas fa-lock"></i>
                        <input type="password" name="password" id="password" required placeholder="Create a strong password" minlength="6">
                    </div>
                    <p style="font-size: 12px; color: #666; margin-top: 5px;">Minimum 6 characters</p>
                </div>
                <div class="form-group">
                    <label for="confirm_password">Confirm Password</label>
                    <div class="input-wrapper">
                        <i class="fas fa-lock"></i>
                        <input type="password" name="confirm_password" id="confirm_password" required placeholder="Confirm your password">
                    </div>
                </div>
                <button type="submit" class="btn-primary"><i class="fas fa-user-plus mr-2"></i>Create Account</button>
            </form>
            <p class="register-link">
                Already have an account? <a href="../main_login.php">Sign In</a>
            </p>
            <p class="footer">
                &copy; <?php echo date('Y'); ?> LGU Health & Safety Platform. All rights reserved.
            </p>
        </div>
    </div>
    <script>
        document.getElementById('registerForm')?.addEventListener('submit', function(e) {
            const password = document.getElementById('password');
            const confirmPassword = document.getElementById('confirm_password');
            if (password.value !== confirmPassword.value) {
                e.preventDefault();
                alert('Passwords do not match!');
                confirmPassword.focus();
            }
        });
    </script>
</body>
</html>