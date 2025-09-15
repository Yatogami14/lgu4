<?php
require_once '../utils/session_manager.php';
require_once '../config/database.php';
require_once '../models/User.php';
require_once '../models/Business.php';
require_once '../models/Inspection.php';
require_once '../utils/access_control.php';

// Check if user is logged in and has permission to access this page
requirePermission('businesses');

$database = new Database();
$db_core = $database->getConnection(Database::DB_CORE);
$db_scheduling = $database->getConnection(Database::DB_SCHEDULING);

$user = new User($db_core);
$user->id = $_SESSION['user_id'];
$user->readOne();

$business = new Business($db_core);
$inspection = new Inspection($db_scheduling);

// Get all businesses
$businesses = $business->readAll();
$businessStats = $business->getBusinessStats();

// Display success/error messages
$success_message = $_SESSION['success_message'] ?? '';
$error_message = $_SESSION['error_message'] ?? '';
unset($_SESSION['success_message'], $_SESSION['error_message']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Businesses - Digital Health & Safety Inspection Platform</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="min-h-screen bg-gray-50">
    <!-- Include Navigation -->
    <?php include '../includes/navigation.php'; ?>

    <!-- Main Content -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 sm:py-8 md:ml-64 md:pt-24">
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-2xl font-bold">Business Directory</h2>
        </div>

        <!-- Success/Error Messages -->
        <?php if ($success_message): ?>
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-6" role="alert">
            <span class="block sm:inline"><?php echo $success_message; ?></span>
        </div>
        <?php endif; ?>

        <?php if ($error_message): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-6" role="alert">
            <span class="block sm:inline"><?php echo $error_message; ?></span>
        </div>
        <?php endif; ?>

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600">Total Businesses</p>
                        <p class="text-2xl font-bold"><?php echo $businessStats['total'] ?? 0; ?></p>
                    </div>
                    <i class="fas fa-building text-3xl text-blue-500"></i>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600">High Risk</p>
                        <p class="text-2xl font-bold"><?php echo $businessStats['high_risk'] ?? 0; ?></p>
                    </div>
                    <i class="fas fa-exclamation-triangle text-3xl text-red-500"></i>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600">Medium Risk</p>
                        <p class="text-2xl font-bold"><?php echo $businessStats['medium_risk'] ?? 0; ?></p>
                    </div>
                    <i class="fas fa-exclamation text-3xl text-yellow-500"></i>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600">Low Risk</p>
                        <p class="text-2xl font-bold"><?php echo $businessStats['low_risk'] ?? 0; ?></p>
                    </div>
                    <i class="fas fa-check-circle text-3xl text-green-500"></i>
                </div>
            </div>
        </div>

        <!-- Businesses Table -->
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Business Name</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Address</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Inspector</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Compliance</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Last Inspection</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php while ($business_row = $businesses->fetch(PDO::FETCH_ASSOC)): 
                        $stats = $business->getComplianceStats($business_row['id']);
                    ?>
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-medium text-gray-900"><?php echo $business_row['name']; ?></div>
                            <div class="text-sm text-gray-500"><?php echo $business_row['registration_number']; ?></div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="text-sm text-gray-900"><?php echo substr($business_row['address'], 0, 50) . '...'; ?></div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            <?php echo $business_row['business_type'] ?: 'N/A'; ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            <?php 
                            $inspector = $business->getInspector($business_row['id']);
                            echo $inspector ? htmlspecialchars($inspector['name']) : 'Unassigned';
                            ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center space-x-2">
                                <div class="w-16 bg-gray-200 rounded-full h-2">
                                    <div class="bg-green-600 h-2 rounded-full" style="width: <?php echo $stats['avg_compliance']; ?>%"></div>
                                </div>
                                <span class="text-sm font-medium"><?php echo $stats['avg_compliance']; ?>%</span>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            <?php 
                            $last_inspection_date = $business->getLastCompletedInspectionDate($business_row['id']);
                            echo $last_inspection_date ? date('M j, Y', strtotime($last_inspection_date)) : 'Never';
                            ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                            <a href="business_view.php?id=<?php echo $business_row['id']; ?>" class="text-blue-600 hover:text-blue-900 mr-3">
                                <i class="fas fa-eye"></i> View
                            </a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        // No script needed for this read-only view.
    </script>
</body>
</html>
