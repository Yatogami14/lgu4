<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// If user is already logged in, redirect them to their dashboard
if (isset($_SESSION['user_id']) && isset($_SESSION['user_role'])) {
    $role = $_SESSION['user_role'];
    $redirect_path = '';
    switch ($role) {
        case 'super_admin':
        case 'admin':
            $redirect_path = 'admin/index.php';
            break;
        case 'inspector':
            $redirect_path = 'inspector/index.php';
            break;
        case 'business_owner':
            $redirect_path = 'business/index.php';
            break;
        case 'community_user':
            $redirect_path = 'community/index.php';
            break;
        default:
            // Fallback for unknown role, maybe logout and redirect to login
            header('Location: main_login.php?error=unknown_role');
            exit;
    }
    header("Location: $redirect_path");
    exit;
}


require_once 'config/database.php';
require_once 'models/User.php';

$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!empty($_POST['email']) && !empty($_POST['password'])) {
        $database = new Database();
        $user = new User($database);

        $user->email = trim($_POST['email']);
        $user->password = $_POST['password'];

        if ($user->login()) {
            // Store user data in session
            $_SESSION['user_id'] = $user->id;
            $_SESSION['user_name'] = $user->name;
            $_SESSION['user_role'] = $user->role;
            
            // Regenerate session ID to prevent session fixation
            session_regenerate_id(true);

            // Redirect to the appropriate dashboard
            $redirect_path = '';
            switch ($user->role) {
                case 'super_admin':
                case 'admin':
                    $redirect_path = 'admin/index.php';
                    break;
                case 'inspector':
                    $redirect_path = 'inspector/index.php';
                    break;
                case 'business_owner':
                    $redirect_path = 'business/index.php';
                    break;
                case 'community_user':
                    $redirect_path = 'community/index.php';
                    break;
                default:
                    $error_message = 'Invalid user role assigned.';
                    // Log this as it's a data integrity issue
                    error_log("User with ID {$user->id} has an invalid role: {$user->role}");
                    break;
            }
            if ($redirect_path) {
                header("Location: $redirect_path");
                exit;
            }
        } else {
            $error_message = 'Invalid email or password.';
        }
    } else {
        $error_message = 'Please enter both email and password.';
    }
}

// Handle error messages from URL
if (isset($_GET['error'])) {
    if ($_GET['error'] === 'unauthorized') {
        $error_message = 'You are not authorized to access that page. Please log in.';
    }
    if ($_GET['error'] === 'session_expired') {
        $error_message = 'Your session has expired. Please log in again.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - LGU Health & Safety Platform</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 flex items-center justify-center min-h-screen">
    <div class="w-full max-w-md">
        <div class="bg-white shadow-lg rounded-lg p-8">
            <div class="flex items-center justify-center mb-6">
                <i class="fas fa-shield-alt text-blue-600 text-4xl"></i>
                <h1 class="ml-4 text-2xl font-bold text-gray-800">LGU Inspection Platform</h1>
            </div>
            <h2 class="text-xl font-semibold text-center text-gray-700 mb-6">Sign in to your account</h2>

            <?php if (!empty($error_message)): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-md mb-4 text-sm" role="alert">
                    <i class="fas fa-exclamation-triangle mr-2"></i>
                    <span><?php echo htmlspecialchars($error_message); ?></span>
                </div>
            <?php endif; ?>

            <form action="main_login.php" method="POST" class="space-y-6">
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700">Email address</label>
                    <div class="mt-1 relative rounded-md shadow-sm">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-envelope text-gray-400"></i>
                        </div>
                        <input id="email" name="email" type="email" autocomplete="email" required
                               class="appearance-none block w-full px-3 py-2 pl-10 border border-gray-300 rounded-md placeholder-gray-400 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                    </div>
                </div>

                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700">Password</label>
                    <div class="mt-1 relative rounded-md shadow-sm">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-lock text-gray-400"></i>
                        </div>
                        <input id="password" name="password" type="password" autocomplete="current-password" required
                               class="appearance-none block w-full px-3 py-2 pl-10 border border-gray-300 rounded-md placeholder-gray-400 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                    </div>
                </div>

                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <input id="remember-me" name="remember-me" type="checkbox" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                        <label for="remember-me" class="ml-2 block text-sm text-gray-900"> Remember me </label>
                    </div>

                    <div class="text-sm">
                        <a href="#" class="font-medium text-blue-600 hover:text-blue-500"> Forgot your password? </a>
                    </div>
                </div>

                <div>
                    <button type="submit"
                            class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        Sign in
                    </button>
                </div>
            </form>
            <div class="mt-6 text-center">
                <p class="text-sm text-gray-600">
                    Not a registered user? 
                    <a href="public_register.php" class="font-medium text-blue-600 hover:text-blue-500">
                        Register here
                    </a>
                </p>
            </div>
        </div>
        <p class="mt-6 text-center text-xs text-gray-500">
            &copy; <?php echo date('Y'); ?> LGU Health & Safety Inspection Platform. All rights reserved.
        </p>
    </div>
</body>
</html>