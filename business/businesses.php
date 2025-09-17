<?php
require_once '../utils/session_manager.php';
require_once '../config/database.php';
require_once '../models/User.php';
require_once '../models/Business.php';
require_once '../utils/access_control.php';

// Check if user is logged in and has permission
requirePermission('businesses');

$database = new Database();

// Get current user
$user = new User($database);
$user->id = $_SESSION['user_id'];
$user->readOne();

// Get businesses owned by the current user
$businessModel = new Business($database);
$owned_businesses = $businessModel->readByOwnerId($user->id);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Businesses - Business Portal</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="min-h-screen bg-gray-50">
    <?php include '../includes/navigation.php'; ?>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 sm:py-8 md:ml-64 md:pt-24">
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-2xl font-bold">My Businesses</h2>
        </div>

        <!-- Businesses Table -->
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Business Name</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Address</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Compliance Score</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php if (empty($owned_businesses)): ?>
                        <tr><td colspan="5" class="text-center py-10 text-gray-500">You have not been assigned to any businesses.</td></tr>
                    <?php else: ?>
                        <?php foreach ($owned_businesses as $business_row): ?>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap"><div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($business_row['name']); ?></div></td>
                            <td class="px-6 py-4"><div class="text-sm text-gray-900"><?php echo htmlspecialchars($business_row['address']); ?></div></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo htmlspecialchars($business_row['business_type']); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap"><span class="text-sm font-bold <?php echo ($business_row['compliance_score'] ?? 0) >= 80 ? 'text-green-600' : 'text-red-600'; ?>"><?php echo ($business_row['compliance_score'] ?? 'N/A'); ?>%</span></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium"><a href="business_view.php?id=<?php echo $business_row['id']; ?>" class="text-blue-600 hover:text-blue-900"><i class="fas fa-eye"></i> View Details</a></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>