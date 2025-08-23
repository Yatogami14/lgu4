<?php
session_start();

// Check if user is already logged in
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Business Portal - LGU Health & Safety</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            min-height: 100vh;
        }
        .card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
            padding: 2rem;
            text-align: center;
        }
        .card:hover {
            transform: translateY(-5px);
        }
        .logo {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
        }
    </style>
</head>
<body class="font-sans">
    <div class="min-h-screen flex items-center justify-center px-4 py-12">
        <div class="text-center mb-12">
            <div class="flex items-center justify-center mb-6">
                <i class="fas fa-building text-6xl logo"></i>
            </div>
            <h1 class="text-4xl font-bold text-white mb-4">Business Portal</h1>
            <p class="text-xl text-white opacity-90">Digital Inspection Platform</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-8 max-w-4xl w-full">
            <div class="card">
                <h2 class="text-2xl font-bold text-gray-800 mb-2">Business Access</h2>
                <p class="text-gray-600">For business owners and community users</p>
                <a href="public_login.php" class="w-full bg-gradient-to-r from-blue-500 to-teal-500 text-white py-3 px-4 rounded-lg font-medium hover:from-blue-600 hover:to-teal-600 transition duration-200 block mt-4">
                    <i class="fas fa-sign-in-alt mr-2"></i>Business Login
                </a>
                <a href="public_register.php" class="w-full border border-blue-500 text-blue-500 py-3 px-4 rounded-lg font-medium hover:bg-blue-500 hover:text-white transition duration-200 block mt-2">
                    <i class="fas fa-user-plus mr-2"></i>Business Registration
                </a>
            </div>
        </div>

        <div class="mt-12 text-center">
            <p class="text-white opacity-80">© 2024 LGU Health & Safety Platform. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
