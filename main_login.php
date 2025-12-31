<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// The session_manager will start the session
require_once 'utils/session_manager.php';
require_once 'config/database.php';
require_once 'models/User.php';
require_once 'includes/functions.php';
require_once 'RateLimiter.php';

// Instantiate the database
$database = new Database();
$conn = $database->getConnection();

$user = new User($database);

// Check for "Remember Me" cookie before any other logic
if (isset($_COOKIE['remember_me']) && !isset($_SESSION['user_id'])) {
    list($selector, $validator) = explode(':', $_COOKIE['remember_me'], 2);

    if ($selector && $validator) {
        $user_data = $user->validateRememberMeToken($selector, $validator);

        if ($user_data && $user_data['status'] === 'active') {
            // Log the user in
            session_regenerate_id(true);
            $_SESSION['user_id'] = $user_data['id'];
            $_SESSION['user_role'] = $user_data['role'];
            $_SESSION['user_name'] = $user_data['name'];
            $_SESSION['login_time'] = time();

            // Generate a new token for security (prevents token theft and reuse)
            $new_token_data = $user->generateRememberMeToken($user_data['id']);
            if ($new_token_data) {
                $cookie_value = $new_token_data['selector'] . ':' . $new_token_data['validator'];
                setcookie('remember_me', $cookie_value, time() + (86400 * 30), "/"); // Reset cookie for another 30 days
            }

            // Redirect to the appropriate dashboard
            $role = $user_data['role'];
            $redirect_path = 'main_login.php'; // Default fallback
            switch ($role) {
                case 'super_admin': $redirect_path = 'admin/index.php'; break;
                case 'admin': $redirect_path = 'admin/index.php'; break;
                case 'inspector': $redirect_path = 'inspector/index.php'; break;
                case 'business_owner': $redirect_path = 'business/index.php'; break;
                case 'community_user': $redirect_path = 'community/index.php'; break;
            }
            header("Location: $redirect_path");
            exit();
        }
    }
}

// Security headers
header("X-Frame-Options: DENY");
header("X-Content-Type-Options: nosniff");
header("Referrer-Policy: strict-origin-when-cross-origin");
header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline';");

// CSRF token generation
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$errors = [];
$show_resend_option = false;
$unverified_email = '';
$auto_sent_verification = false;
$login_identifier = '';
$success_message = '';

// Check for a success message from registration or other redirects
if (isset($_SESSION['success_message'])) {
    $success_message = $_SESSION['success_message'];
    unset($_SESSION['success_message']); // Clear the message so it doesn't show again
}

// Determine base path for assets
$base_path = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
if ($base_path === '/' || $base_path === '\\') $base_path = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // CSRF token validation
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $errors['general'] = "Security validation failed. Please try again.";
        // Regenerate token after failed validation
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    } else {
        // Sanitize input data
        $login_identifier = isset($_POST['login_identifier']) ? sanitize_input($_POST['login_identifier']) : '';
        $password = isset($_POST['password']) ? $_POST['password'] : '';
        $remember = isset($_POST['remember_me']) ? true : false;

        // Robust Rate Limiting
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $rateLimiter = new RateLimiter($conn);

        if (!$rateLimiter->isAllowed('login_failure', $ip_address)) {
            $errors['general'] = "Too many failed login attempts from this location. Please try again in 15 minutes.";
        } else {
            // Validation
            if (empty($login_identifier)) {
                $errors['login_identifier'] = "Email or username is required";
            }

            if (empty($password)) {
                $errors['password'] = "Password is required";
            }
        }

        // If no errors, proceed with login
        if (empty($errors)) {
            try {
                $user->email = $login_identifier; // Use email property to hold the identifier (email or username)
                $user->password = $password;

                if ($user->login()) {

                    // Check user status from the populated user object
                    if ($user->status === 'active') {
                        // Regenerate session ID to prevent session fixation
                        session_regenerate_id(true);

                        // Set session variables
                        $_SESSION['user_id'] = $user->id;
                        $_SESSION['user_role'] = $user->role;
                        $_SESSION['user_name'] = $user->name;
                        $_SESSION['login_time'] = time();

                        // Regenerate CSRF token
                        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

                        // Handle "Remember Me"
                        if ($remember) {
                            $token_data = $user->generateRememberMeToken($user->id);
                            if ($token_data) {
                                $cookie_value = $token_data['selector'] . ':' . $token_data['validator'];
                                setcookie('remember_me', $cookie_value, time() + (86400 * 30), "/"); // 30-day cookie
                            }
                        }

                        // Redirect to the appropriate dashboard based on role
                        $role = $user->role;
                        $redirect_path = 'main_login.php'; // Default to login page if role not found
                        switch ($role) {
                            case 'super_admin': $redirect_path = 'admin/index.php'; break;
                            case 'admin': $redirect_path = 'admin/index.php'; break;
                            case 'inspector': $redirect_path = 'inspector/index.php'; break;
                            case 'business_owner': $redirect_path = 'business/index.php'; break;
                            case 'community_user': $redirect_path = 'community/index.php'; break;
                        }
                        header("Location: $redirect_path");
                        exit();
                    } elseif ($user->status === 'pending_approval') {
                        if ($user->role === 'business_owner') {
                            $errors['general'] = "Your account is pending review. The Superadmin needs to verify your uploaded files before approval.";
                        } else {
                            $errors['general'] = "Your account is pending review by an administrator. You will be notified upon approval.";
                        }
                    } elseif ($user->status === 'rejected') {
                        $errors['general'] = "Your registration application has been rejected. Please contact support for more information.";
                    } else {
                        $errors['general'] = "Your account is currently inactive. Please contact support.";
                    }
                } else {
                    // Login failed (user not found or wrong password)
                    $errors['general'] = "Invalid email/username or password";
                    $rateLimiter->recordAttempt('login_failure', $ip_address);
                }
            } catch (PDOException $e) {
                error_log("Login error: " . $e->getMessage());
                $errors['general'] = "Login failed. Please try again.";
            }
        }

        // Regenerate CSRF token after form submission
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Health & Safety Inspection System</title>
    <link rel="icon" type="image/png" href="<?php echo $base_path; ?>/logo/logo.jpeg">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo $base_path; ?>/assets/css/login.css">
</head>
<body>

    <div class="bg-decoration bg-decoration-1"></div>
    <div class="bg-decoration bg-decoration-2"></div>
    <div class="bg-decoration bg-decoration-3"></div>
    <div class="bg-decoration bg-decoration-4"></div>

    <img src="<?php echo $base_path; ?>/logo/logo.jpeg" alt="Health & Safety Inspection Watermark" class="watermark-logo">

    <a href="<?php echo $base_path; ?>/index.html" class="back-button">
        <i class="fas fa-arrow-left"></i>
        Back to Home Page
    </a>

    <div class="main-content-wrapper">
        <div class="logo-left">
            <img src="<?php echo $base_path; ?>/logo/logo.jpeg" alt="Health & Safety Inspection Logo">
            <h1>HEALTH & SAFETY</h1>
            <p class="tagline">Inspection Management System</p>
        </div>

        <div class="login-container">
            <div class="login-logo">
            <img src="<?php echo $base_path; ?>/logo/logo.jpeg" alt="Health & Safety Inspection">
        </div>

        <div class="login-header">
            <h2>Welcome Back!</h2>
            <p>Enter your credentials to access your account.</p>
        </div>

        <?php if (!empty($errors['general'])): ?>
            <div class="error-message"><?php echo $errors['general']; ?></div>
        <?php endif; ?>

        <?php if (!empty($success_message)) : ?>
            <div class="success-message"><?php echo $success_message; ?></div>
        <?php endif; ?>

        <form id="loginForm" method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">

            <div class="form-group">
                <label for="login_identifier">Email or Username</label>
                <div class="input-wrapper">
                    <i class="fas fa-user"></i>
                    <input type="text" id="login_identifier" name="login_identifier" placeholder="e.g., john.doe@example.com" value="<?php echo htmlspecialchars($login_identifier); ?>" required>
                </div>
                <?php if (!empty($errors['login_identifier'])) : ?>
                    <div class="error-message" style="margin-top: 10px;"><?php echo $errors['login_identifier']; ?></div>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <div class="input-wrapper">
                    <i class="fas fa-lock"></i>
                    <input type="password" id="password" name="password" placeholder="Enter your password" required>
                    <button type="button" class="password-toggle" id="passwordToggle"><i class="fas fa-eye"></i></button>
                </div>
                <?php if (!empty($errors['password'])) : ?>
                    <div class="error-message" style="margin-top: 10px;"><?php echo $errors['password']; ?></div>
                <?php endif; ?>
            </div>

            <div class="form-options">
                <div class="remember">
                    <input type="checkbox" id="remember_me" name="remember_me">
                    <label for="remember_me">Remember Me</label>
                </div>
                <a href="forgot_password.php" class="forgot-password">Forgot Password?</a>
            </div>

            <button type="submit" class="btn-primary" id="loginButton">Login</button>
        </form>

        <p class="register-link">
            Don't have an account? <a href="register_options.php">Sign Up</a>
        </p>

        <p class="footer">
            &copy; <?php echo date('Y'); ?> Health & Safety Inspection. All Rights Reserved.
        </p>
        </div>
    </div>

</body>
</html>
