<?php
session_start();
require_once 'config/database.php';
require_once 'models/User.php';
require_once 'includes/functions.php';
require_once 'RateLimiter.php';

// Include PHPMailer
require_once 'vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

$base_path = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
if ($base_path === '/' || $base_path === '\\') $base_path = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Always show a generic success message to prevent email enumeration.
    $success_message = "If an account with that email exists, a password reset link has been sent.";

    $database = new Database();
    $conn = $database->getConnection();
    $user = new User($database);
    $rateLimiter = new RateLimiter($conn);

    $email = sanitize_input($_POST['email'] ?? '');
    $ip_address = $_SERVER['REMOTE_ADDR'];

    // Rate limit by IP address to prevent abuse.
    if (!$rateLimiter->isAllowed('password_reset', $ip_address)) {
        // We still show the success message, but we don't perform any action.
        // This prevents an attacker from knowing they've been rate-limited.
    } elseif (!empty($email) && is_valid_email($email)) {
        $rateLimiter->recordAttempt('password_reset', $ip_address);

        $user->email = $email;
        $user_data = $user->findByEmail();

        if ($user_data) {
            $token = $user->generatePasswordResetToken($email);
            if ($token) {
                // Use the centralized function to send the email
                if (!send_password_reset_email($email, $user_data['name'], $token, $base_path)) {
                    // Log the error, but don't expose it to the user.
                    error_log("Failed to send password reset email to {$email}.");
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - Health & Safety Inspection System</title>
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
                    <h1 class="text-4xl font-black text-white mb-2 tracking-tight drop-shadow-sm">Password Recovery</h1>
                    <p class="text-yellow-50 text-lg font-medium max-w-md mx-auto leading-relaxed">
                        Regain access to your account securely.
                    </p>
                </div>
            </div>
        </div>

        <!-- Right Side - Form -->
        <div class="w-full lg:w-1/2 flex flex-col justify-center py-12 px-4 sm:px-6 lg:px-20 xl:px-24 bg-white relative">
            
            <!-- Back Button -->
            <a href="<?php echo $base_path; ?>/main_login.php" class="absolute top-6 left-6 inline-flex items-center text-sm font-medium text-gray-500 hover:text-yellow-600 transition-colors group">
                <i class="fas fa-arrow-left mr-2 transform group-hover:-translate-x-1 transition-transform"></i>
                Back to Login
            </a>

            <div class="mx-auto w-full max-w-sm lg:w-96">
                <div class="text-center lg:text-left">
                    <img src="<?php echo $base_path; ?>/logo/logo.jpeg" alt="Logo" class="h-16 w-16 mx-auto lg:hidden mb-6 rounded-full shadow-md">
                    <h2 class="text-3xl font-extrabold text-gray-900 tracking-tight">Forgot Password?</h2>
                    <p class="mt-2 text-sm text-gray-600">
                        Enter your email address and we'll send you a link to reset your password.
                    </p>
                </div>

                <div class="mt-8">
                    <?php if (isset($success_message)): ?>
                        <div class="mb-4 bg-green-50 border-l-4 border-green-500 p-4 rounded-r-md">
                            <div class="flex">
                                <div class="flex-shrink-0"><i class="fas fa-check-circle text-green-500"></i></div>
                                <div class="ml-3"><p class="text-sm text-green-700 font-medium"><?php echo $success_message; ?></p></div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" class="space-y-6">
                        <div>
                            <label for="email" class="block text-sm font-medium text-gray-700">Email Address</label>
                            <div class="mt-1 relative rounded-md shadow-sm">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <i class="fas fa-envelope text-gray-400"></i>
                                </div>
                                <input type="email" name="email" id="email" required 
                                    class="block w-full pl-10 pr-3 py-3 border border-gray-300 rounded-lg leading-5 bg-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:border-yellow-500 sm:text-sm transition duration-150 ease-in-out" 
                                    placeholder="Enter your email">
                            </div>
                        </div>

                        <div>
                            <button type="submit" class="w-full flex justify-center py-3 px-4 border border-transparent rounded-full shadow-sm text-sm font-bold text-white bg-yellow-500 hover:bg-yellow-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-yellow-500 transition-all transform hover:scale-[1.02] shadow-lg hover:shadow-yellow-500/30">
                                Send Reset Link
                            </button>
                        </div>
                    </form>

                    <div class="mt-6 text-center">
                        <p class="text-sm text-gray-600">
                            Remember your password? 
                            <a href="main_login.php" class="font-medium text-yellow-600 hover:text-yellow-500 transition-colors">
                                Sign In
                            </a>
                        </p>
                    </div>
                    
                    <p class="mt-8 text-center text-xs text-gray-500">
                        &copy; <?php echo date('Y'); ?> HSI-QC Protektado. All Rights Reserved.
                    </p>
                </div>
            </div>
        </div>
    </div>
</body>
</html>