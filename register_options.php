<?php
session_start();
// Determine base path for assets
$base_path = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
if ($base_path === '/' || $base_path === '\\') $base_path = '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registration Options - Health & Safety Inspection System</title>
    <link rel="icon" type="image/png" href="<?php echo $base_path; ?>/logo/logo.jpeg">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" integrity="sha512-SnH5WK+bZxgPHs44uWIX+LLJAJ9/2PkPKZ5QiAj6Ta86w+fsb2TkcmfRyVX3pBnMFcV7oQPJkl9QevSCWr3W6A==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { sans: ['Inter', 'sans-serif'] },
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
                    <h1 class="text-4xl font-black text-white mb-2 tracking-tight drop-shadow-sm">HSI-QC Protektado</h1>
                    <p class="text-yellow-50 text-lg font-medium max-w-md mx-auto leading-relaxed">
                        Join the platform to ensure safer communities through intelligent inspection management.
                    </p>
                </div>
            </div>
        </div>

        <!-- Right Side - Options -->
        <div class="w-full lg:w-1/2 flex flex-col justify-center py-12 px-4 sm:px-6 lg:px-20 xl:px-24 bg-white relative">
            
            <!-- Back Button -->
            <a href="<?php echo $base_path; ?>/main_login.php" class="absolute top-6 left-6 inline-flex items-center text-sm font-medium text-gray-500 hover:text-yellow-600 transition-colors group">
                <i class="fas fa-arrow-left mr-2 transform group-hover:-translate-x-1 transition-transform"></i>
                Back to Login
            </a>

            <div class="mx-auto w-full max-w-md">
                <div class="text-center lg:text-left">
                    <img src="<?php echo $base_path; ?>/logo/logo.jpeg" alt="Logo" class="h-16 w-16 mx-auto lg:hidden mb-6 rounded-full shadow-md">
                    <h2 class="text-3xl font-extrabold text-gray-900 tracking-tight">Create Account</h2>
                    <p class="mt-2 text-sm text-gray-600">
                        Choose your account type to get started.
                    </p>
                </div>

                <div class="mt-10 grid grid-cols-1 gap-5 sm:grid-cols-2">
                    
                    <!-- Business Owner Option -->
                    <a href="business/business_owner_register.php" class="relative group bg-white p-6 focus-within:ring-2 focus-within:ring-inset focus-within:ring-yellow-500 rounded-2xl shadow-sm hover:shadow-lg border border-gray-200 hover:border-yellow-400 transition-all duration-300 transform hover:-translate-y-1">
                        <div>
                            <span class="rounded-xl inline-flex p-3 bg-yellow-50 text-yellow-600 ring-4 ring-white group-hover:bg-yellow-500 group-hover:text-white transition-colors duration-300">
                                <i class="fas fa-store text-2xl"></i>
                            </span>
                        </div>
                        <div class="mt-4">
                            <h3 class="text-lg font-bold text-gray-900 group-hover:text-yellow-600 transition-colors">
                                Business Owner
                            </h3>
                            <p class="mt-2 text-sm text-gray-500 group-hover:text-gray-600">
                                Register your establishment and manage inspections.
                            </p>
                        </div>
                    </a>

                    <!-- Community Member Option -->
                    <a href="community/public_register.php" class="relative group bg-white p-6 focus-within:ring-2 focus-within:ring-inset focus-within:ring-green-500 rounded-2xl shadow-sm hover:shadow-lg border border-gray-200 hover:border-green-400 transition-all duration-300 transform hover:-translate-y-1">
                        <div>
                            <span class="rounded-xl inline-flex p-3 bg-green-50 text-green-600 ring-4 ring-white group-hover:bg-green-500 group-hover:text-white transition-colors duration-300">
                                <i class="fas fa-users text-2xl"></i>
                            </span>
                        </div>
                        <div class="mt-4">
                            <h3 class="text-lg font-bold text-gray-900 group-hover:text-green-600 transition-colors">
                                Community Member
                            </h3>
                            <p class="mt-2 text-sm text-gray-500 group-hover:text-gray-600">
                                Report concerns and view safety ratings.
                            </p>
                        </div>
                    </a>

                </div>

                <div class="mt-10">
                    <div class="relative">
                        <div class="absolute inset-0 flex items-center">
                            <div class="w-full border-t border-gray-300"></div>
                        </div>
                        <div class="relative flex justify-center text-sm">
                            <span class="px-2 bg-white text-gray-500">
                                Already have an account?
                            </span>
                        </div>
                    </div>

                    <div class="mt-6">
                        <a href="main_login.php" class="w-full flex justify-center py-3 px-4 border border-gray-300 rounded-full shadow-sm text-sm font-bold text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-yellow-500 transition-all">
                            Sign In
                        </a>
                    </div>
                </div>
                
                <p class="mt-8 text-center text-xs text-gray-500">
                    &copy; <?php echo date('Y'); ?> HSI-QC Protektado. All Rights Reserved.
                </p>
            </div>
        </div>
    </div>

</body>
</html>