<?php
require_once '../utils/session_manager.php';
require_once '../config/database.php';
require_once '../models/User.php';
require_once '../utils/access_control.php';

requirePermission('dashboard');

$database = new Database();
$db_core = $database->getConnection(Database::DB_CORE);

$user = new User($db_core);
$user->id = $_SESSION['user_id'];
$user->readOne();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Business Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="min-h-screen bg-gray-50">
    <?php include '../includes/navigation.php'; ?>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 sm:py-8 md:ml-64 md:pt-24">
        <div class="bg-gradient-to-r from-blue-600 to-purple-700 rounded-lg shadow-lg p-6 text-white">
            <h1 class="text-2xl font-bold">Welcome, <?php echo htmlspecialchars($user->name); ?>!</h1>
            <p class="text-blue-100">This is your business portal dashboard.</p>
        </div>

        <div class="mt-6 bg-white p-6 rounded-lg shadow">
            <h2 class="text-xl font-bold mb-4">Quick Links</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <a href="violations.php" class="bg-red-100 p-4 rounded-lg hover:bg-red-200 transition">
                    <h3 class="font-bold text-red-800"><i class="fas fa-exclamation-triangle mr-2"></i>View My Violations</h3>
                    <p class="text-sm text-red-700">Check the status of any violations for your businesses.</p>
                </a>
            </div>
        </div>
    </div>
</body>
</html>