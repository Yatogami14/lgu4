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
    if ($_POST['password'] !== $_POST['confirm_password']) {
        $error_message = "Passwords do not match.";
    } else {
        $user->name = $_POST['name'];
        $user->email = $_POST['email'];
        $user->password = $_POST['password'];
        $user->role = $_POST['role'];
        $user->department = $_POST['department'] ?? null;
        
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
            default:
                $user->certification = 'Administrator'; // Default to Admin
        }

        $creation_result = $user->create();
        if ($creation_result['success']) {
            $role_name = ucfirst(str_replace('_', ' ', $_POST['role']));
            $success_message = "$role_name account created successfully! You can now login.";
        } else {
            $error_message = $creation_result['error'] ?? "Failed to create account. Please try again.";
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
    <link rel="stylesheet" href="<?php echo $base_path; ?>/assets/css/auth.css">
    <style>
        body {
            background: #ffffff !important;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .register-card {
            background: #ffffff !important;
            backdrop-filter: none !important;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            position: relative;
            z-index: 10;
        }
        .logo {
            color: #1a202c;
        }
        .btn-submit {
            width: 100%;
            padding: 14px;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            color: #1f2937;
            background: linear-gradient(135deg, #fef08a 0%, #facc15 100%);
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(250, 204, 21, 0.4);
        }
        /* Update blob colors for white theme */
        .bg-decoration-1 { background: radial-gradient(circle, #FFF59D 0%, transparent 70%); }
        .bg-decoration-2 { background: radial-gradient(circle, #e5e7eb 0%, transparent 70%); }
        .bg-decoration-3 { background: radial-gradient(circle, #FFF176 0%, transparent 70%); }
        .bg-decoration-4 { background: radial-gradient(circle, rgba(255, 249, 196, 0.4) 0%, transparent 70%); }
    </style>
</head>
<body class="font-sans">
    <div class="bg-decoration bg-decoration-1"></div>
    <div class="bg-decoration bg-decoration-2"></div>
    <div class="bg-decoration bg-decoration-3"></div>
    <div class="bg-decoration bg-decoration-4"></div>

    <div class="register-card p-8 w-full max-w-md mx-4">
        <div class="text-center mb-8">
            <a href="<?php echo $base_path; ?>/index.html" class="flex items-center justify-center mb-4" title="Go to Homepage">
                <img src="<?php echo $base_path; ?>/logo/logo.jpeg?v=4" alt="Logo" class="h-12 w-auto">
            </a>
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

            <button type="submit" class="btn-submit">
                <i class="fas fa-user-plus mr-2"></i>Create Admin Account
            </button>
        </form>

        <div class="mt-6 text-center">
            <p class="text-gray-600">Already have an account? 
                <a href="admin_login.php" class="text-blue-600 hover:text-blue-800 font-medium">Sign In</a>
            </p>
        </div>

        <div class="mt-8 pt-6 border-t border-gray-200">
            <div class="text-center">
                <p class="text-sm text-gray-600">© <?php echo date('Y'); ?> LGU Health & Safety Platform. All rights reserved.</p>
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

        // Show/hide department field based on role
        const roleSelect = document.getElementById('role');
        const departmentInput = document.getElementById('department');
        const departmentDiv = departmentInput.parentElement.parentElement; // The parent div of the input

        function toggleDepartmentField() {
            // Department is always required for admin/inspector roles.
            departmentDiv.style.display = 'block';
            departmentInput.required = true;
        }
        roleSelect.addEventListener('change', toggleDepartmentField);
        document.addEventListener('DOMContentLoaded', toggleDepartmentField); // Run on page load
    </script>
</body>
</html>
