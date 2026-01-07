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
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        /* General Body and Font Setup */
        :root {
            --color-primary: #facc15; /* yellow-400 */
            --color-primary-dark: #eab308; /* yellow-500 */
            --color-primary-darker: #ca8a04; /* yellow-600 */
            --color-bg: #f9fafb; /* gray-50 */
            --color-text: #111827; /* gray-900 */
            --color-text-muted: #6b7280; /* gray-500 */
            --color-card-bg: #ffffff;
            --color-border: #e5e7eb; /* gray-200 */
            --font-sans: 'Inter', sans-serif;
        }

        html {
            height: 100%;
        }

        body {
            font-family: var(--font-sans);
            background-color: var(--color-bg);
            color: var(--color-text);
            margin: 0;
            display: flex;
            min-height: 100%;
            line-height: 1.5;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }

        /* Animated Gradient Background Decorations */
        .bg-decoration {
            position: fixed;
            border-radius: 50%;
            filter: blur(80px);
            opacity: 0.4;
            z-index: -1;
            animation: blob-move 20s infinite alternate;
        }
        .bg-decoration-1 {
            width: 400px; height: 400px;
            top: -150px; left: -150px;
            background-color: #fef08a; /* yellow-200 */
        }
        .bg-decoration-2 {
            width: 300px; height: 300px;
            bottom: -100px; right: -100px;
            background-color: #fde047; /* yellow-300 */
            animation-delay: -5s;
        }
        .bg-decoration-3 {
            width: 250px; height: 250px;
            top: 50%; left: 50%;
            transform: translate(-50%, -50%);
            background-color: #fde047; /* yellow-300 */
            animation-delay: -10s;
        }
        .bg-decoration-4 { display: none; } /* Hide the 4th one */

        @keyframes blob-move {
            0% { transform: translate(0, 0) scale(1); }
            50% { transform: translate(100px, -50px) scale(1.2); }
            100% { transform: translate(-50px, 50px) scale(0.9); }
        }

        /* Hide watermark logo, it's distracting */
        .watermark-logo {
            display: none;
        }

        /* Main Layout: Split Screen */
        .main-content-wrapper {
            display: flex;
            width: 100%;
            flex-grow: 1;
            align-items: stretch;
        }

        .logo-left {
            width: 45%;
            background: linear-gradient(135deg, var(--color-primary), var(--color-primary-darker));
            color: white;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            padding: 40px;
            position: relative;
            overflow: hidden;
        }

        .logo-left::before {
            content: '';
            position: absolute;
            top: -50px; right: -50px;
            width: 200px; height: 200px;
            background: rgba(255,255,255,0.1);
            border-radius: 50%;
            animation: blob-move 25s infinite alternate;
            animation-delay: -3s;
        }

        .logo-left img {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            border: 4px solid rgba(255,255,255,0.5);
            box-shadow: 0 10px 25px rgba(0,0,0,0.2);
            margin-bottom: 24px;
        }

        .logo-left h1 {
            font-size: 2rem;
            font-weight: 800;
            margin: 0;
            letter-spacing: 1px;
            text-shadow: 0 2px 4px rgba(0,0,0,0.2);
        }

        .logo-left .tagline {
            font-size: 1.1rem;
            font-weight: 400;
            opacity: 0.9;
            margin-top: 8px;
        }

        .login-container {
            width: 55%;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 40px;
            background-color: var(--color-card-bg);
        }

        /* Responsive adjustments */
        @media (max-width: 992px) {
            .logo-left {
                display: none;
            }
            .login-container {
                width: 100%;
                max-width: 450px;
                margin: auto;
                background-color: transparent;
            }
            body {
                background-color: var(--color-bg);
            }
        }

        /* Form Container Elements */
        .login-logo {
            display: none; /* Hide this on desktop, it's redundant with the left panel */
        }
        @media (max-width: 992px) {
            .login-logo {
                display: block;
                margin-bottom: 24px;
            }
            .login-logo img {
                width: 80px;
                height: 80px;
                border-radius: 50%;
                box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            }
        }

        .login-header {
            text-align: center;
            margin-bottom: 32px;
        }

        .login-header h2 {
            font-size: 2rem;
            font-weight: 800;
            margin: 0 0 8px 0;
        }

        .login-header p {
            color: var(--color-text-muted);
            margin: 0;
        }

        /* Form Styling */
        #loginForm {
            width: 100%;
            max-width: 380px;
        }

        .form-group {
            margin-bottom: 24px;
        }

        .form-group label {
            display: block;
            font-size: 0.875rem;
            font-weight: 500;
            margin-bottom: 8px;
        }

        .input-wrapper {
            position: relative;
        }

        .input-wrapper i.fa-user,
        .input-wrapper i.fa-lock {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: #9ca3af; /* gray-400 */
        }

        .input-wrapper input {
            width: 100%;
            padding: 14px 16px 14px 48px;
            border: 1px solid var(--color-border);
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.2s, box-shadow 0.2s;
            box-sizing: border-box;
        }

        .input-wrapper input:focus {
            outline: none;
            border-color: var(--color-primary-dark);
            box-shadow: 0 0 0 3px rgba(250, 204, 21, 0.3); /* yellow-400 with opacity */
        }

        .password-toggle {
            position: absolute;
            right: 16px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            cursor: pointer;
            color: #9ca3af; /* gray-400 */
            padding: 0;
        }
        .password-toggle:hover {
            color: var(--color-text);
        }

        .form-options {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.875rem;
            margin-bottom: 24px;
        }

        .remember {
            display: flex;
            align-items: center;
        }

        .remember input[type="checkbox"] {
            width: 16px;
            height: 16px;
            border-radius: 4px;
            border: 1px solid var(--color-border);
            cursor: pointer;
            accent-color: var(--color-primary-darker);
        }

        .remember label {
            margin: 0 0 0 8px;
            cursor: pointer;
            color: var(--color-text-muted);
        }

        .forgot-password {
            color: var(--color-primary-darker);
            text-decoration: none;
            font-weight: 500;
        }
        .forgot-password:hover {
            text-decoration: underline;
        }

        /* Buttons */
        .btn-primary {
            width: 100%;
            padding: 16px;
            border: none;
            border-radius: 50px; /* Pill shape */
            background: var(--color-primary-dark);
            color: white;
            font-size: 1rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(234, 179, 8, 0.2);
        }

        .btn-primary:hover {
            background: var(--color-primary-darker);
            transform: translateY(-3px);
            box-shadow: 0 7px 20px rgba(234, 179, 8, 0.3);
        }

        .btn-primary:active {
            transform: translateY(-1px);
        }

        /* Links and Footer */
        .register-link {
            text-align: center;
            margin-top: 24px;
            font-size: 0.9rem;
            color: var(--color-text-muted);
        }

        .register-link a {
            color: var(--color-primary-darker);
            font-weight: 600;
            text-decoration: none;
        }
        .register-link a:hover {
            text-decoration: underline;
        }

        .footer {
            text-align: center;
            margin-top: 48px;
            font-size: 0.8rem;
            color: #9ca3af; /* gray-400 */
        }

        /* Back Button */
        .back-button {
            position: fixed;
            top: 20px;
            left: 20px;
            background: rgba(255,255,255,0.5);
            backdrop-filter: blur(5px);
            padding: 10px 15px;
            border-radius: 50px;
            text-decoration: none;
            color: var(--color-text);
            font-size: 0.9rem;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            z-index: 10;
        }
        .back-button:hover {
            background: white;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            transform: translateY(-2px);
        }
        @media (max-width: 992px) {
            .back-button {
                background: white;
            }
        }

        /* Error and Success Messages */
        .error-message, .success-message {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 8px;
            text-align: center;
            font-weight: 500;
            animation: fade-in 0.5s ease;
        }

        .error-message {
            background-color: #fef2f2; /* red-50 */
            color: #991b1b; /* red-800 */
            border: 1px solid #fecaca; /* red-300 */
        }

        .success-message {
            background-color: #f0fdf4; /* green-50 */
            color: #166534; /* green-800 */
            border: 1px solid #bbf7d0; /* green-300 */
        }

        @keyframes fade-in {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
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

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const passwordToggle = document.getElementById('passwordToggle');
            const passwordInput = document.getElementById('password');
            const icon = passwordToggle.querySelector('i');

            passwordToggle.addEventListener('click', function() {
                const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                passwordInput.setAttribute('type', type);
                icon.classList.toggle('fa-eye');
                icon.classList.toggle('fa-eye-slash');
            });
        });
    </script>
</body>
</html>
