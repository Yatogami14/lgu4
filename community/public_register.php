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
    <title>Community Registration - Health & Safety Inspection System</title>
    <link rel="icon" type="image/png" href="<?php echo $base_path; ?>/logo/logo.jpeg">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" integrity="sha512-SnH5WK+bZxgPHs44uWIX+LLJAJ9/2PkPKZ5QiAj6Ta86w+fsb2TkcmfRyVX3pBnMFcV7oQPJkl9QevSCWr3W6A==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo $base_path; ?>/assets/css/login.css">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
        }
        .input-wrapper {
            position: relative;
        }
        .input-wrapper > i {
            position: absolute;
            top: 50%;
            left: 1.25rem;
            transform: translateY(-50%);
            color: #9ca3af;
            pointer-events: none;
            z-index: 10;
        }
        .input-wrapper input {
            padding-left: 3rem;
        }
        .password-toggle {
            position: absolute;
            top: 50%;
            right: 1.25rem;
            transform: translateY(-50%);
            background: none;
            border: none;
            cursor: pointer;
            color: #9ca3af;
            z-index: 10;
            padding: 0;
        }
        .password-toggle i {
            position: static !important;
            transform: none !important;
        }
    </style>
</head>
<body>

    <div class="bg-decoration bg-decoration-1"></div>
    <div class="bg-decoration bg-decoration-2"></div>
    <div class="bg-decoration bg-decoration-3"></div>
    <div class="bg-decoration bg-decoration-4"></div>

    <img src="<?php echo $base_path; ?>/logo/logo.jpeg" alt="Watermark" class="watermark-logo">

    <a href="<?php echo $base_path; ?>/register_options.php" class="back-button">
        <i class="fas fa-arrow-left"></i>
        Back to Options
    </a>

    <div class="main-content-wrapper">
        <div class="logo-left">
            <img src="<?php echo $base_path; ?>/logo/logo.jpeg" alt="Logo">
            <h1>HSI-QC Protektado</h1>
            <p class="tagline">Join our community to report concerns and view safety ratings.</p>
        </div>

        <div class="login-container">
            <div class="login-logo">
                <img src="<?php echo $base_path; ?>/logo/logo.jpeg" alt="Logo">
            </div>
            <div class="login-header">
                <h2>Create Account</h2>
                <p>Sign up as a community member.</p>
            </div>

            <?php if (isset($error_message)): ?>
                <div class="error-message">
                    <i class="fas fa-exclamation-circle"></i> <?php echo $error_message; ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" id="registerForm">
                
                <div class="form-group">
                    <div class="input-wrapper">
                        <i class="fas fa-user"></i>
                        <input type="text" name="name" id="name" required placeholder=" ">
                        <label for="name">Full Name</label>
                    </div>
                </div>

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
                        <input type="password" name="password" id="password" required minlength="6" placeholder=" ">
                        <button type="button" class="password-toggle" id="passwordToggle"><i class="fas fa-eye"></i></button>
                        <label for="password">Password</label>
                    </div>
                    <p class="input-hint">Minimum 6 characters</p>
                </div>

                <div class="form-group">
                    <div class="input-wrapper">
                        <i class="fas fa-lock"></i>
                        <input type="password" name="confirm_password" id="confirm_password" required placeholder=" ">
                        <label for="confirm_password">Confirm Password</label>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn-primary">Create Account</button>
                </div>

            </form>

            <div class="register-link">
                Already have an account? <a href="../main_login.php">Sign In</a>
            </div>
            
            <p class="footer">
                &copy; <?php echo date('Y'); ?> HSI-QC Protektado. All Rights Reserved.
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

        const togglePassword = document.getElementById('passwordToggle');
        if (togglePassword) {
            togglePassword.addEventListener('click', function (e) {
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
    </script>
</body>
</html>