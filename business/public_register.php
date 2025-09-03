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
$db = $database->getConnection();
$user = new User($db);
$business = new Business($db);

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user->name = $_POST['name'];
    $user->email = $_POST['email'];
    $user->password = $_POST['password'];
    $user->role = $_POST['role'];
    $user->department = $_POST['department'] ?? '';
    
    // Set certification based on role
    switch ($_POST['role']) {
        case 'business_owner':
            $user->certification = 'Business Owner';
            break;
        case 'community_user':
            $user->certification = 'Community User';
            break;
        default:
            $user->certification = 'User';
    }

    // Create user account
    if ($user->create()) {
        // If business owner, create business record
        if ($_POST['role'] == 'business_owner' && !empty($_POST['business_name'])) {
            $business->name = $_POST['business_name'];
            $business->address = $_POST['business_address'];
            $business->owner_id = $user->id;
            $business->contact_number = $_POST['business_contact'];
            $business->email = $_POST['business_email'];
            $business->business_type = $_POST['business_type'];
            $business->registration_number = $_POST['registration_number'];
            $business->establishment_date = $_POST['establishment_date'];
            $business->inspection_frequency = $_POST['inspection_frequency'];
            
            // Set default compliance values
            $business->is_compliant = true;
            $business->compliance_score = 100;
            
            if ($business->create()) {
                $success_message = "Business Owner account and business registration created successfully! You can now login.";
            } else {
                $error_message = "User account created but business registration failed. Please contact support.";
            }
        } else {
            $role_name = ucfirst(str_replace('_', ' ', $_POST['role']));
            $success_message = "$role_name account created successfully! You can now login.";
        }
    } else {
        $error_message = "Failed to create account. Email might already exist.";
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
            <h1 class="text-3xl font-bold text-gray-800 mb-2">Business & Community Registration</h1>
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
                <label for="role" class="block text-sm font-medium text-gray-700 mb-2">Account Type</label>
                <select name="role" id="role" required
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200">
                    <option value="business_owner">Business Owner</option>
                    <option value="community_user">Community User</option>
                </select>
            </div>

            <div class="form-row">
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
            </div>

            <div>
                <label for="department" class="block text-sm font-medium text-gray-700 mb-2">Business/Organization (Optional)</label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <i class="fas fa-building text-gray-400"></i>
                    </div>
                    <input type="text" name="department" id="department"
                           class="pl-10 w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200"
                           placeholder="Enter business or organization name">
                </div>
            </div>

            <!-- Business Registration Fields (shown only for Business Owners) -->
            <div id="businessFields" class="business-fields-grid hidden">
                <div class="border-t border-gray-200 pt-4 col-span-2">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">Business Information</h3>
                </div>

                <div>
                    <label for="business_name" class="block text-sm font-medium text-gray-700 mb-2">Business Name *</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-store text-gray-400"></i>
                        </div>
                        <input type="text" name="business_name" id="business_name"
                               class="pl-10 w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200"
                               placeholder="Enter business name">
                    </div>
                </div>

                <div>
                    <label for="business_address" class="block text-sm font-medium text-gray-700 mb-2">Business Address *</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-map-marker-alt text-gray-400"></i>
                        </div>
                        <textarea name="business_address" id="business_address" rows="2" required
                               class="pl-10 w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200"
                               placeholder="Enter complete business address"></textarea>
                    </div>
                </div>

                <div>
                    <label for="business_contact" class="block text-sm font-medium text-gray-700 mb-2">Business Contact Number *</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-phone text-gray-400"></i>
                        </div>
                        <input type="tel" name="business_contact" id="business_contact" required
                               class="pl-10 w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200"
                               placeholder="Enter business phone number">
                    </div>
                </div>

                <div>
                    <label for="business_email" class="block text-sm font-medium text-gray-700 mb-2">Business Email</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-envelope text-gray-400"></i>
                        </div>
                        <input type="email" name="business_email" id="business_email"
                               class="pl-10 w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200"
                               placeholder="Enter business email">
                    </div>
                </div>

                <div>
                    <label for="business_type" class="block text-sm font-medium text-gray-700 mb-2">Business Type *</label>
                    <select name="business_type" id="business_type" required
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200">
                        <option value="">Select Business Type</option>
                        <option value="Restaurant">Restaurant</option>
                        <option value="Food Establishment">Food Establishment</option>
                        <option value="Hotel">Hotel</option>
                        <option value="Hospital">Hospital</option>
                        <option value="School">School</option>
                        <option value="Factory">Factory</option>
                        <option value="Office Building">Office Building</option>
                        <option value="Shopping Mall">Shopping Mall</option>
                        <option value="Construction Site">Construction Site</option>
                        <option value="Gas Station">Gas Station</option>
                        <option value="Other">Other</option>
                    </select>
                </div>

                <div>
                    <label for="registration_number" class="block text-sm font-medium text-gray-700 mb-2">Registration Number *</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-id-card text-gray-400"></i>
                        </div>
                        <input type="text" name="registration_number" id="registration_number" required
                               class="pl-10 w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200"
                               placeholder="Enter business registration number">
                    </div>
                </div>

                <div>
                    <label for="establishment_date" class="block text-sm font-medium text-gray-700 mb-2">Establishment Date *</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-calendar-alt text-gray-400"></i>
                        </div>
                        <input type="date" name="establishment_date" id="establishment_date" required
                               class="pl-10 w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200">
                    </div>
                </div>

                <div>
                    <label for="inspection_frequency" class="block text-sm font-medium text-gray-700 mb-2">Inspection Frequency</label>
                    <select name="inspection_frequency" id="inspection_frequency"
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200">
                        <option value="monthly">Monthly</option>
                        <option value="quarterly">Quarterly</option>
                        <option value="weekly">Weekly</option>
                    </select>
                    <p class="text-xs text-gray-500 mt-1">Frequency of health and safety inspections</p>
                </div>
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
                <a href="public_login.php" class="text-blue-600 hover:text-blue-800 font-medium">Sign In</a>
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

        // Show/hide business fields based on role selection
        document.getElementById('role')?.addEventListener('change', function() {
            const businessFields = document.getElementById('businessFields');
            if (this.value === 'business_owner') {
                businessFields.classList.remove('hidden');
                // Make business fields required
                document.getElementById('business_name').required = true;
                document.getElementById('business_address').required = true;
                document.getElementById('business_contact').required = true;
                document.getElementById('business_type').required = true;
                document.getElementById('registration_number').required = true;
                document.getElementById('establishment_date').required = true;
            } else {
                businessFields.classList.add('hidden');
                // Remove required attribute for non-business owners
                document.getElementById('business_name').required = false;
                document.getElementById('business_address').required = false;
                document.getElementById('business_contact').required = false;
                document.getElementById('business_type').required = false;
                document.getElementById('registration_number').required = false;
                document.getElementById('establishment_date').required = false;
            }
        });

        // Set default inspection frequency based on business type
        document.getElementById('business_type')?.addEventListener('change', function() {
            const inspectionFrequency = document.getElementById('inspection_frequency');
            const frequencyMap = {
                'Restaurant': 'monthly',
                'Food Establishment': 'monthly',
                'Hotel': 'quarterly',
                'Hospital': 'monthly',
                'School': 'quarterly',
                'Factory': 'monthly',
                'Office Building': 'quarterly',
                'Shopping Mall': 'quarterly',
                'Construction Site': 'weekly',
                'Gas Station': 'monthly',
                'Other': 'monthly'
            };
            
            if (frequencyMap[this.value]) {
                inspectionFrequency.value = frequencyMap[this.value];
            }
        });

        // Initialize on page load
        document.addEventListener('DOMContentLoaded', function() {
            const roleSelect = document.getElementById('role');
            if (roleSelect) {
                roleSelect.dispatchEvent(new Event('change'));
            }
        });
    </script>
</body>
</html>
