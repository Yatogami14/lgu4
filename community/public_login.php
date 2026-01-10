<?php
// The session_manager will start the session
require_once '../utils/session_manager.php';
require_once '../config/database.php';
require_once '../models/User.php';

// If user is already logged in, redirect to their dashboard
if (isset($_SESSION['user_id'])) {
    $role = $_SESSION['user_role'];
    if ($role == 'business_owner') {
        header('Location: ../business/business_landing.php');
    } else if ($role == 'community_user') {
        header('Location: community_landing.php');
    } else {
        // Fallback for other roles like admin/inspector
        header('Location: ../admin/index.php');
    }
    exit;
}

$database = new Database();
$user = new User($database);

// Determine base path for assets
$base_path = rtrim(dirname(dirname($_SERVER['SCRIPT_NAME'])), '/\\');
if ($base_path === '/' || $base_path === '\\') $base_path = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['email']) && isset($_POST['password'])) {
        $email = $_POST['email'];
        $password = $_POST['password'];

        $user->email = $email;
        $user->password = $password;

        if ($user->login()) {
            $_SESSION['user_id'] = $user->id;
            $_SESSION['user_role'] = $user->role;
            $_SESSION['user_name'] = $user->name;

            // Handle "Remember Me"
            if (isset($_POST['remember_me']) && $_POST['remember_me'] == '1') {
                $token_data = $user->generateRememberMeToken($user->id);
                if ($token_data) {
                    $cookie_value = $token_data['selector'] . ':' . $token_data['validator'];
                    setcookie('remember_me', $cookie_value, time() + (86400 * 30), "/"); // 30-day cookie
                }
            }
            
            // Redirect to appropriate landing page based on user role
            if ($user->role == 'business_owner') {
                header('Location: ../business/business_landing.php');
            } else if ($user->role == 'community_user') {
                header('Location: community_landing.php');
            } else {
                // Default fallback
                header('Location: community_landing.php');
            }
            exit;
        } else {
            $error_message = "Invalid email or password.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Public Login - HSI-QC Protektado</title>
    <link rel="icon" type="image/png" href="<?php echo $base_path; ?>/logo/logo.jpeg">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" integrity="sha512-SnH5WK+bZxgPHs44uWIX+LLJAJ9/2PkPKZ5QiAj6Ta86w+fsb2TkcmfRyVX3pBnMFcV7oQPJkl9QevSCWr3W6A==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo $base_path; ?>/assets/css/login.css">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
        }
    </style>
</head>
<body>

    <div class="bg-decoration bg-decoration-1"></div>
    <div class="bg-decoration bg-decoration-2"></div>
    <div class="bg-decoration bg-decoration-3"></div>
    <div class="bg-decoration bg-decoration-4"></div>

    <img src="<?php echo $base_path; ?>/logo/logo.jpeg" alt="Watermark" class="watermark-logo">

    <a href="<?php echo $base_path; ?>/index.php" class="back-button">
        <i class="fas fa-arrow-left"></i>
        Back to Home
    </a>

    <div class="main-content-wrapper">
        <div class="logo-left">
            <img src="<?php echo $base_path; ?>/logo/logo.jpeg" alt="Logo">
            <h1>HSI-QC Protektado</h1>
            <p class="tagline">Business & Community Portal</p>
        </div>

        <div class="login-container">
            <div class="login-logo">
                <img src="<?php echo $base_path; ?>/logo/logo.jpeg" alt="Logo">
            </div>

            <div class="login-header">
                <h2>Welcome Back!</h2>
                <p>Enter your credentials to access your account.</p>
            </div>

            <?php if (isset($error_message)): ?>
                <div class="error-message">
                    <i class="fas fa-exclamation-circle"></i> <?php echo $error_message; ?>
                </div>
            <?php endif; ?>

            <form method="POST" id="loginForm">
                <div class="form-group">
                    <div class="input-wrapper">
                        <i class="fas fa-envelope"></i>
                        <input type="email" name="email" id="email" required placeholder=" ">
                        <label for="email">Email Address</label>
                    </div>
                </div>

                <div class="form-group">
                    <div class="input-wrapper">
                        <i class="fas fa-lock"></i>
                        <input type="password" name="password" id="password" required placeholder=" ">
                        <button type="button" class="password-toggle" id="passwordToggle"><i class="fas fa-eye"></i></button>
                        <label for="password">Password</label>
                    </div>
                </div>

                <div class="form-options">
                    <div class="remember">
                        <input type="checkbox" id="remember_me" name="remember_me" value="1">
                        <label for="remember_me">Remember Me</label>
                    </div>
                    <a href="../forgot_password.php" class="forgot-password">Forgot Password?</a>
                </div>

                <button type="submit" class="btn-primary">Sign In</button>
            </form>

            <div class="register-link">
                Need an account? <a href="public_register.php">Register as Community Member</a>
            </div>

            <p class="footer">
                &copy; <?php echo date('Y'); ?> HSI-QC Protektado. All Rights Reserved.
            </p>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const passwordToggle = document.getElementById('passwordToggle');
            if (passwordToggle) {
                passwordToggle.addEventListener('click', function (e) {
                    const password = document.getElementById('password');
                    const icon = this.querySelector('i');
                    const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
                    password.setAttribute('type', type);
                    icon.classList.toggle('fa-eye');
                    icon.classList.toggle('fa-eye-slash');
                });
            }

            // Ripple Effect
            function createRipple(event) {
                const button = event.currentTarget;
                const circle = document.createElement("span");
                const diameter = Math.max(button.clientWidth, button.clientHeight);
                const radius = diameter / 2;

                circle.style.width = circle.style.height = `${diameter}px`;
                circle.style.left = `${event.clientX - button.getBoundingClientRect().left - radius}px`;
                circle.style.top = `${event.clientY - button.getBoundingClientRect().top - radius}px`;
                circle.classList.add("ripple");

                const ripple = button.getElementsByClassName("ripple")[0];
                if (ripple) ripple.remove();
                button.appendChild(circle);
            }
            document.querySelectorAll(".btn-primary").forEach(btn => btn.addEventListener("click", createRipple));
        });
    </script>
</body>
</html>
