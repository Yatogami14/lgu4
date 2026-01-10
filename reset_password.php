<?php
session_start();
require_once 'config/database.php';
require_once 'models/User.php';

$database = new Database();
$user = new User($database);

$token = $_GET['token'] ?? $_POST['token'] ?? null;
$is_token_valid = false;
$user_id = null;

if ($token) {
    $user_id = $user->validatePasswordResetToken($token);
    if ($user_id) {
        $is_token_valid = true;
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && $is_token_valid) {
    if (!empty($_POST['new_password']) && $_POST['new_password'] === $_POST['confirm_new_password']) {
        if (strlen($_POST['new_password']) >= 6) {
            if ($user->updatePassword($user_id, $_POST['new_password'])) {
                $_SESSION['success_message'] = 'Your password has been reset successfully. Please log in.';
                header('Location: main_login.php');
                exit;
            } else {
                $error_message = 'Failed to update password. Please try again.';
            }
        } else {
            $error_message = 'Password must be at least 6 characters long.';
        }
    } else {
        $error_message = 'Passwords do not match or are empty.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - Health & Safety Inspection System</title>
    <link rel="icon" type="image/png" href="<?php echo $base_path; ?>/logo/logo.jpeg">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { sans: ['Poppins', 'sans-serif'] },
                    animation: {
                        'blob': 'blob 7s infinite',
                    },
                    keyframes: {
                        blob: {
                            '0%': { transform: 'translate(0px, 0px) scale(1)' },
                            '33%': { transform: 'translate(30px, -50px) scale(1.1)' },
                            '66%': { transform: 'translate(-20px, 20px) scale(0.9)' },
                            '100%': { transform: 'translate(0px, 0px) scale(1)' },
                        }
                    }
                }
            }
        }
    </script>
    <style>
        body { -webkit-font-smoothing: antialiased; -moz-osx-font-smoothing: grayscale; }
    </style>
</head>
<body class="bg-gray-50 font-sans text-gray-900 h-full">

    <div class="min-h-screen flex">
        
        <!-- Left Side - Branding (Hidden on mobile) -->
        <div class="hidden lg:flex lg:w-1/2 bg-gradient-to-br from-yellow-400 to-yellow-600 relative overflow-hidden items-center justify-center">
            <!-- Decorative Background Elements -->
            <div class="absolute top-0 left-0 w-full h-full bg-yellow-500 opacity-10 pattern-grid-lg"></div>
            <div class="absolute -top-24 -left-24 w-96 h-96 bg-yellow-300 rounded-full mix-blend-multiply filter blur-3xl opacity-30 animate-blob"></div>
            <div class="absolute -bottom-24 -right-24 w-96 h-96 bg-yellow-300 rounded-full mix-blend-multiply filter blur-3xl opacity-30 animate-blob animation-delay-2000"></div>
            
            <div class="relative z-10 text-center px-12">
                <div class="bg-white/20 backdrop-blur-lg rounded-3xl p-8 shadow-2xl border border-white/20">
                    <img src="<?php echo $base_path; ?>/logo/logo.jpeg" alt="Health & Safety Inspection Logo" class="h-32 w-32 mx-auto rounded-full shadow-lg mb-6 border-4 border-white/50">
                    <h1 class="text-4xl font-black text-white mb-2 tracking-tight drop-shadow-sm">Secure Reset</h1>
                    <p class="text-yellow-50 text-lg font-medium max-w-md mx-auto leading-relaxed">
                        Create a new, strong password for your account.
                    </p>
                </div>
            </div>
        </div>

        <!-- Right Side - Form -->
        <div class="w-full lg:w-1/2 flex flex-col justify-center py-12 px-4 sm:px-6 lg:px-20 xl:px-24 bg-white relative">
            
            <div class="mx-auto w-full max-w-sm lg:w-96">
                <div class="text-center lg:text-left">
                    <img src="<?php echo $base_path; ?>/logo/logo.jpeg" alt="Logo" class="h-16 w-16 mx-auto lg:hidden mb-6 rounded-full shadow-md">
                    <h2 class="text-3xl font-extrabold text-gray-900 tracking-tight">Reset Password</h2>
                    <p class="mt-2 text-sm text-gray-600">
                        Please enter your new password below.
                    </p>
                </div>

                <div class="mt-8">
                    <?php if (isset($error_message)): ?>
                        <div class="mb-4 bg-red-50 border-l-4 border-red-500 p-4 rounded-r-md animate-pulse">
                            <div class="flex">
                                <div class="flex-shrink-0"><i class="fas fa-exclamation-circle text-red-500"></i></div>
                                <div class="ml-3"><p class="text-sm text-red-700 font-medium"><?php echo $error_message; ?></p></div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if ($is_token_valid): ?>
                        <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" class="space-y-6">
                            <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
                            
                            <div>
                                <label for="new_password" class="block text-sm font-medium text-gray-700">New Password</label>
                                <div class="mt-1 relative rounded-md shadow-sm">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <i class="fas fa-lock text-gray-400"></i>
                                    </div>
                                    <input type="password" name="new_password" id="new_password" required minlength="8"
                                        class="block w-full pl-10 pr-3 py-3 border border-gray-300 rounded-lg leading-5 bg-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:border-yellow-500 sm:text-sm transition duration-150 ease-in-out" 
                                        placeholder="Enter new password">
                                </div>
                            </div>

                            <div>
                                <label for="confirm_new_password" class="block text-sm font-medium text-gray-700">Confirm New Password</label>
                                <div class="mt-1 relative rounded-md shadow-sm">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <i class="fas fa-lock text-gray-400"></i>
                                    </div>
                                    <input type="password" name="confirm_new_password" id="confirm_new_password" required
                                        class="block w-full pl-10 pr-3 py-3 border border-gray-300 rounded-lg leading-5 bg-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:border-yellow-500 sm:text-sm transition duration-150 ease-in-out" 
                                        placeholder="Confirm new password">
                                </div>
                            </div>

                            <div>
                                <button type="submit" class="w-full flex justify-center py-3 px-4 border border-transparent rounded-full shadow-sm text-sm font-bold text-white bg-yellow-500 hover:bg-yellow-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-yellow-500 transition-all transform hover:scale-[1.02] shadow-lg hover:shadow-yellow-500/30">
                                    Reset Password
                                </button>
                            </div>
                        </form>
                    <?php else: ?>
                        <div class="text-center">
                            <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100 mb-4">
                                <i class="fas fa-exclamation-triangle text-red-600 text-xl"></i>
                            </div>
                            <h3 class="text-lg font-medium text-gray-900">Invalid Link</h3>
                            <p class="mt-2 text-sm text-gray-500">This password reset link is invalid or has expired.</p>
                            <div class="mt-6">
                                <a href="forgot_password.php" class="font-medium text-yellow-600 hover:text-yellow-500 transition-colors">
                                    Request a new link
                                </a>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <p class="mt-8 text-center text-xs text-gray-500">
                        &copy; <?php echo date('Y'); ?> HSI-QC Protektado. All Rights Reserved.
                    </p>
                </div>
            </div>
        </div>
    </div>
</body>
</html>