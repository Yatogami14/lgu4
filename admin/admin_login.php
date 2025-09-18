<?php
// The session_manager will start the session
require_once '../utils/session_manager.php';
require_once '../config/database.php';
require_once '../models/User.php';

// This block was redirecting any logged-in user away from this page.
// It's commented out to allow users to log in as an admin even if already logged in with another role.
// if (isset($_SESSION['user_id'])) {
//     header('Location: index.php');
//     exit;
// }

$database = new Database();
$user = new User($database);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
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

        header('Location: index.php');
        exit;
    } else {
        $error_message = "Invalid email or password.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Digital Health & Safety Inspection Platform</title>
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
        .login-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
        }
        .logo {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
        }
    </style>
</head>
<body class="font-sans">
    <div class="login-card p-8 w-full max-w-md mx-4">
        <div class="text-center mb-8">
            <div class="flex items-center justify-center mb-4">
                <i class="fas fa-shield-alt text-4xl logo"></i>
            </div>
            <h1 class="text-3xl font-bold text-gray-800 mb-2">Admin Portal</h1>
            <p class="text-gray-600">Digital Inspection Platform</p>
        </div>

        <?php if (isset($error_message)): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg mb-6 flex items-center">
                <i class="fas fa-exclamation-circle mr-2"></i>
                <?php echo $error_message; ?>
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

            <div>
                <label for="password" class="block text-sm font-medium text-gray-700 mb-2">Password</label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <i class="fas fa-lock text-gray-400"></i>
                    </div>
                    <input type="password" name="password" id="password" required 
                           class="pl-10 w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200"
                           placeholder="Enter your password">
                </div>
            </div>

            <div class="flex items-center justify-between text-sm mb-4">
                <div class="flex items-center">
                    <input id="remember_me" name="remember_me" type="checkbox" value="1" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                    <label for="remember_me" class="ml-2 block text-sm text-gray-900">
                        Remember me
                    </label>
                </div>
                <div>
                    <a href="../forgot_password.php" class="font-medium text-blue-600 hover:text-blue-800">
                        Forgot your password?
                    </a>
                </div>
            </div>

            <button type="submit" 
                    class="w-full bg-gradient-to-r from-blue-600 to-purple-600 text-white py-3 px-4 rounded-lg font-medium hover:from-blue-700 hover:to-purple-700 transition duration-200 transform hover:scale-105">
                <i class="fas fa-sign-in-alt mr-2"></i>Sign In
            </button>
        </form>

        <div class="mt-6 text-center">
            <p class="text-gray-600">Need to create an initial admin account?
                <a href="admin_register.php" class="text-blue-600 hover:text-blue-800 font-medium">
                    Register Here
                </a>
            </p>
        </div>

        <div class="mt-8 pt-6 border-t border-gray-200">
            <div class="text-center">
                <p class="text-sm text-gray-600">© 2024 LGU Health & Safety Platform. All rights reserved.</p>
            </div>
        </div>
    </div>
</body>
</html>
