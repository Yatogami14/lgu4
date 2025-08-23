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
    <title>Admin Portal - LGU Health & Safety</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        .card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2); /* Increased shadow */
            transition: transform 0.3s ease, box-shadow 0.3s ease; /* Added transition for shadow */
            padding: 2rem;
            text-align: center;
        }
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 30px 60px rgba(0, 0, 0, 0.3); /* Shadow on hover */
        }
        .logo {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        h1 {
            font-size: 3rem; /* Increased font size */
        }
        h2 {
            font-size: 2.5rem; /* Increased font size */
        }
        p {
            margin-bottom: 1.5rem; /* Added spacing */
        }
    </style>
</head>
<body class="font-sans">
    <div class="min-h-screen flex items-center justify-center px-4 py-12">
        <div class="text-center mb-12">
            <div class="flex items-center justify-center mb-6">
                <i class="fas fa-shield-alt text-6xl logo"></i>
            </div>
            <h1 class="text-4xl font-bold text-white mb-4">Admin Portal</h1>
            <p class="text-xl text-white opacity-90">Digital Inspection Platform</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-8 max-w-4xl w-full">
            <div class="card">
                <h2 class="text-2xl font-bold text-gray-800 mb-2">Admin Access</h2>
                <p class="text-gray-600">For administrators and inspectors</p>
                <a href="admin_login.php" class="w-full bg-gradient-to-r from-purple-600 to-blue-600 text-white py-3 px-4 rounded-lg font-medium hover:from-purple-700 hover:to-blue-700 transition duration-200 block mt-4">
                    <i class="fas fa-sign-in-alt mr-2"></i>Admin Login
                </a>
                <a href="admin_register.php" class="w-full border border-purple-600 text-purple-600 py-3 px-4 rounded-lg font-medium hover:bg-purple-600 hover:text-white transition duration-200 block mt-2">
                    <i class="fas fa-user-plus mr-2"></i>Admin Registration
                </a>
            </div>
        </div>

        <div class="mt-12 text-center">
            <p class="text-white opacity-80">© 2024 LGU Health & Safety Platform. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
