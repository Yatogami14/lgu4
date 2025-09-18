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

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user->name = $_POST['name'];
    $user->email = $_POST['email'];
    $user->password = $_POST['password'];
    $user->role = $_POST['role'];
    $user->department = $_POST['department'];
    
    // Set certification based on role
    switch ($_POST['role']) {
        case 'super_admin':
            $user->certification = 'Super Administrator';
            break;
        case 'admin':
            $user->certification = 'Administrator';
            break;
        case 'inspector':
            $user->certification = 'Certified Inspector';
            break;
        case 'business_owner':
            $user->certification = 'Business Owner';
            break;
        case 'community_user':
            $user->certification = 'Community User';
            break;
        default:
            $user->certification = 'User';
    }
    
    // Check if email already exists before trying to create
    if ($user->emailExists()) {
        $error_message = "An account with this email already exists. Please use a different email.";
    } else {
        if ($user->create()) {
            $role_name = ucfirst(str_replace('_', ' ', $_POST['role']));
            $success_message = "$role_name account created successfully! You can now login.";
        } else {
            $error_message = "Failed to create account. Please try again.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Digital Health & Safety Inspection Platform</title>
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
        .register-card {
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
    <div class="register-card p-8 w-full max-w-md mx-4">
        <div class="text-center mb-8">
            <div class="flex items-center justify-center mb-4">
                <i class="fas fa-shield-alt text-4xl logo"></i>
            </div>
            <h1 class="text-3xl font-bold text-gray-800 mb-2">Admin Registration</h1>
            <p class="text-gray-600">Digital Inspection Platform</p>
        </div>

        <?php if (isset($success_message)): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg mb-6 flex items-center">
                <i class="fas fa-check-circle mr-2"></i>
                <?php echo $success_message; ?>
            </div>
        <?php endif; ?>

        <?php if (isset($error_message)): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg mb-6 flex items-center">
                <i class="fas fa-exclamation-circle mr-2"></i>
                <?php echo $error_message; ?>
            </div>
        <?php endif; ?>

    <form method="POST" class="space-y-6" id="registerForm">
            <div>
                <label for="role" class="block text-sm font-medium text-gray-700 mb-2">User Role</label>
                <select name="role" id="role" required 
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200">
                    <option value="super_admin">Super Admin</option>
                    <option value="admin">Admin</option>
                    <option value="inspector">Inspector</option>
                    <option value="business_owner">Business Owner</option>
                    <option value="community_user">Community User</option>
                </select>
            </div>
            <div>
                <label for="name" class="block text-sm font-medium text-gray-700 mb-2">Full Name</label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <i class="fas fa-user text-gray-400"></i>
                    </div>
                    <input type="text" name="name" id="name" required 
                           class="pl-10 w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200"
                           placeholder="Enter your full name">
                </div>
            </div>

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
                <label for="department" class="block text-sm font-medium text-gray-700 mb-2">Department</label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <i class="fas fa-building text-gray-400"></i>
                    </div>
                    <input type="text" name="department" id="department" required 
                           class="pl-10 w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200"
                           placeholder="Enter your department">
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
                           placeholder="Create a strong password" minlength="6">
                </div>
                <p class="text-xs text-gray-500 mt-1">Minimum 6 characters</p>
            </div>

            <div>
                <label for="confirm_password" class="block text-sm font-medium text-gray-700 mb-2">Confirm Password</label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <i class="fas fa-lock text-gray-400"></i>
                    </div>
                    <input type="password" name="confirm_password" id="confirm_password" required 
                           class="pl-10 w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200"
                           placeholder="Confirm your password">
                </div>
            </div>

            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                <div class="flex items-center">
                    <i class="fas fa-info-circle text-blue-500 mr-2"></i>
                    <span class="text-sm text-blue-700 font-medium">Super Admin Privileges</span>
                </div>
                <ul class="text-xs text-blue-600 mt-2 list-disc list-inside">
                    <li>Full system access and control</li>
                    <li>User management capabilities</li>
                    <li>System configuration rights</li>
                    <li>All inspection permissions</li>
                </ul>
            </div>

            <button type="submit" 
                    class="w-full bg-gradient-to-r from-purple-600 to-blue-600 text-white py-3 px-4 rounded-lg font-medium hover:from-purple-700 hover:to-blue-700 transition duration-200 transform hover:scale-105">
                <i class="fas fa-user-plus mr-2"></i>Create Super Admin Account
            </button>
        </form>

        <div class="mt-6 text-center">
            <p class="text-gray-600">Already have an account? 
                <a href="admin_login.php" class="text-blue-600 hover:text-blue-800 font-medium">Sign In</a>
            </p>
        </div>

        <div class="mt-8 pt-6 border-t border-gray-200">
            <div class="text-center">
                <p class="text-sm text-gray-600">© 2024 LGU Health & Safety Platform. All rights reserved.</p>
            </div>
        </div>
    </div>

    <script>
        // Password confirmation validation
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
