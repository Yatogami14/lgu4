<?php
require_once '../utils/session_manager.php';
require_once '../config/database.php';
require_once '../models/User.php';
require_once '../models/Business.php';
require_once '../models/Violation.php';
require_once '../utils/access_control.php';

// Check if user is logged in and has permission
requirePermission('violations');

$database = new Database();
$db_core = $database->getConnection(Database::DB_CORE);
$db_violations = $database->getConnection(Database::DB_VIOLATIONS);

// Get current user
$user = new User($db_core);
$user->id = $_SESSION['user_id'];
$user->readOne();

// Get businesses owned by the current user
$businessModel = new Business($db_core);
$owned_businesses = $businessModel->readByOwnerId($user->id);
$business_ids = array_map(fn($b) => $b['id'], $owned_businesses);

// Get violations for the owned businesses
$violationModel = new Violation($db_violations);
$violations = [];
$violationStats = ['total' => 0, 'open' => 0, 'in_progress' => 0, 'resolved' => 0];

if (!empty($business_ids)) {
    $violationsStmt = $violationModel->readAll($business_ids);
    $violations = $violationsStmt->fetchAll(PDO::FETCH_ASSOC);
    $violationStats = $violationModel->getViolationStats($business_ids);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Violations - Business Portal</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="min-h-screen bg-gray-50">
    <?php include '../includes/navigation.php'; ?>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 sm:py-8 md:ml-64 md:pt-24">
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-2xl font-bold">My Business Violations</h2>
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600">Total Violations</p>
                        <p class="text-2xl font-bold"><?php echo $violationStats['total'] ?? 0; ?></p>
                    </div>
                    <i class="fas fa-exclamation-triangle text-3xl text-red-500"></i>
                </div>
            </div>
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600">Open Violations</p>
                        <p class="text-2xl font-bold"><?php echo $violationStats['open'] ?? 0; ?></p>
                    </div>
                    <i class="fas fa-clock text-3xl text-yellow-500"></i>
                </div>
            </div>
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600">In Progress</p>
                        <p class="text-2xl font-bold"><?php echo $violationStats['in_progress'] ?? 0; ?></p>
                    </div>
                    <i class="fas fa-spinner text-3xl text-blue-500"></i>
                </div>
            </div>
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600">Resolved</p>
                        <p class="text-2xl font-bold"><?php echo $violationStats['resolved'] ?? 0; ?></p>
                    </div>
                    <i class="fas fa-check-circle text-3xl text-green-500"></i>
                </div>
            </div>
        </div>

        <!-- Violations Table -->
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Business</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Violation</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Severity</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Due Date</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php if (empty($violations)): ?>
                        <tr><td colspan="6" class="text-center py-10 text-gray-500">No violations found for your businesses.</td></tr>
                    <?php else: ?>
                        <?php foreach ($violations as $violation): ?>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($violation['business_name']); ?></div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm text-gray-900"><?php echo htmlspecialchars($violation['description']); ?></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 py-1 text-xs font-medium rounded-full 
                                    <?php echo $violation['severity'] == 'high' ? 'bg-red-100 text-red-800' : 
                                           ($violation['severity'] == 'medium' ? 'bg-yellow-100 text-yellow-800' : 'bg-green-100 text-green-800'); ?>">
                                    <?php echo htmlspecialchars($violation['severity']); ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 py-1 text-xs font-medium rounded-full 
                                    <?php echo $violation['status'] == 'open' ? 'bg-red-100 text-red-800' : 
                                           ($violation['status'] == 'in_progress' ? 'bg-yellow-100 text-yellow-800' : 'bg-green-100 text-green-800'); ?>">
                                    <?php echo str_replace('_', ' ', htmlspecialchars($violation['status'])); ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                <?php echo date('M j, Y', strtotime($violation['due_date'])); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <a href="../admin/inspection_view.php?id=<?php echo $violation['inspection_id']; ?>" class="text-green-600 hover:text-green-900">
                                    <i class="fas fa-eye"></i> View Details
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>