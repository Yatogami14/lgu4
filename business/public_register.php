<?php
session_start();
require_once '../config/database.php';
require_once '../models/User.php';
require_once '../models/Business.php';

// Check if user is already logged in
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$database = new Database();
$db_core = $database->getConnection(Database::DB_CORE);
$user = new User($db_core);
$business = new Business($db_core);

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // --- User (Owner) Details ---
    $user->name = $_POST['owner_name'];
    $user->email = $_POST['owner_email'];
    $user->password = $_POST['password'];
    $user->role = 'business_owner'; // Automatically set role
    $user->department = $_POST['business_name']; // Use business name for department
    $user->certification = 'Business Owner';

    // --- Business Details ---
    $business->name = $_POST['business_name'];
    $business->address = $_POST['business_address'];
    $business->contact_number = $_POST['business_contact'];
    $business->email = $_POST['business_email'];
    $business->business_type = $_POST['business_type'];
    $business->registration_number = $_POST['business_reg_no'];

    // --- Transaction-like process ---
    // 1. Check if user email exists
    if ($user->emailExists()) {
        $error_message = "An account with this email already exists. Please use a different email or try logging in.";
    } else {
        // 2. Create the user
        if ($user->create()) {
            // 3. If user creation is successful, create the business and link it
            $business->owner_id = $user->id; // Link business to the new user
            if ($business->create()) {
                $success_message = "Business owner account and business profile created successfully! You can now login.";
            } else {
                // Rollback: Delete the user if business creation fails
                $user->delete(); 
                $error_message = "Failed to register your business. Please check business details and try again.";
            }
        } else {
            $error_message = "Failed to create user account. Please try again.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Public Registration - Digital Health & Safety Inspection Platform</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
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
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }
        .form-row {
            display: flex;
            gap: 1rem;
        }
        .form-row > div {
            flex: 1;
        }
        .business-fields-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }
        @media (max-width: 768px) {
            .form-grid, .form-row, .business-fields-grid {
                grid-template-columns: 1fr;
                display: block;
            }
            .form-row {
                flex-direction: column;
            }
        }
    </style>
</head>
<body class="font-sans">
    <div class="register-card p-8 w-full max-w-4xl mx-4">
        <div class="text-center mb-8">
            <div class="flex items-center justify-center mb-4">
                <i class="fas fa-building text-4xl logo"></i>
            </div>
            <h1 class="text-3xl font-bold text-gray-800 mb-2">Business Registration</h1>
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
            <!-- Owner Information -->
            <h2 class="text-xl font-semibold text-gray-700 border-b pb-2">Owner Information</h2>
            <div class="form-grid">
                <div>
                    <label for="owner_name" class="block text-sm font-medium text-gray-700 mb-2">Full Name</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-user text-gray-400"></i>
                        </div>
                        <input type="text" name="owner_name" id="owner_name" required
                               class="pl-10 w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200"
                               placeholder="Enter your full name">
                    </div>
                </div>
                <div>
                    <label for="owner_email" class="block text-sm font-medium text-gray-700 mb-2">Email Address (for Login)</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-envelope text-gray-400"></i>
                        </div>
                        <input type="email" name="owner_email" id="owner_email" required
                               class="pl-10 w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200"
                               placeholder="Enter your login email">
                    </div>
                </div>
            </div>

            <!-- Business Information -->
            <h2 class="text-xl font-semibold text-gray-700 border-b pb-2 mt-8">Business Information</h2>
            <div class="form-grid">
                <div>
                    <label for="business_name" class="block text-sm font-medium text-gray-700 mb-2">Business Name</label>
                    <input type="text" name="business_name" id="business_name" required class="w-full px-4 py-3 border border-gray-300 rounded-lg" placeholder="e.g., ABC Restaurant">
                </div>
                <div>
                    <label for="business_type" class="block text-sm font-medium text-gray-700 mb-2">Business Type</label>
                    <select name="business_type" id="business_type" required class="w-full px-4 py-3 border border-gray-300 rounded-lg">
                        <option value="">Select a type</option>
                        <option value="Restaurant">Restaurant</option>
                        <option value="Retail">Retail Store</option>
                        <option value="Office">Office Building</option>
                        <option value="Manufacturing">Manufacturing</option>
                        <option value="Healthcare">Healthcare Facility</option>
                        <option value="Education">Educational Institution</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
            </div>
            <div>
                <label for="business_address" class="block text-sm font-medium text-gray-700 mb-2">Business Address</label>
                <textarea name="business_address" id="business_address" rows="3" required class="w-full px-4 py-3 border border-gray-300 rounded-lg" placeholder="Enter complete business address"></textarea>
            </div>
            <div class="form-grid">
                <div>
                    <label for="business_contact" class="block text-sm font-medium text-gray-700 mb-2">Business Contact Number</label>
                    <input type="tel" name="business_contact" id="business_contact" required class="w-full px-4 py-3 border border-gray-300 rounded-lg" placeholder="e.g., +63 912 345 6789">
                </div>
                <div>
                    <label for="business_email" class="block text-sm font-medium text-gray-700 mb-2">Business Email</label>
                    <input type="email" name="business_email" id="business_email" required class="w-full px-4 py-3 border border-gray-300 rounded-lg" placeholder="e.g., contact@abcrestaurant.com">
                </div>
            </div>
             <div>
                <label for="business_reg_no" class="block text-sm font-medium text-gray-700 mb-2">Business Registration Number</label>
                <input type="text" name="business_reg_no" id="business_reg_no" required class="w-full px-4 py-3 border border-gray-300 rounded-lg" placeholder="e.g., DTI/SEC Registration No.">
            </div>

            <div class="form-row">
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
            </div>

            <button type="submit"
                    class="w-full bg-gradient-to-r from-blue-500 to-teal-500 text-white py-3 px-4 rounded-lg font-medium hover:from-blue-600 hover:to-teal-600 transition duration-200 transform hover:scale-105">
                <i class="fas fa-user-plus mr-2"></i>Create Account
            </button>
        </form>

        <div class="mt-6 text-center">
            <p class="text-gray-600">Already have an account?
                <a href="../main_login.php" class="text-blue-600 hover:text-blue-800 font-medium">Sign In</a>
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
