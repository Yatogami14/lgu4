<?php
require_once '../utils/session_manager.php';
require_once '../config/database.php';
require_once '../models/User.php';
require_once '../models/Business.php';
require_once '../models/Inspection.php';
require_once '../utils/access_control.php';

// Check if user is logged in
requirePermission('businesses');

$database = new Database();
$db_core = $database->getConnection(Database::DB_CORE);
$db_scheduling = $database->getConnection(Database::DB_SCHEDULING);

$user = new User($database);
$user->id = $_SESSION['user_id'];
$user->readOne();

$business = new Business($database);
$inspection = new Inspection($database);

// Get business ID from URL
$business_id = isset($_GET['id']) ? $_GET['id'] : null;

if (!$business_id) {
    // Redirect to an error page or display a message
    header('Location: error.php?message=Business ID not provided');
    exit;
}

$business->id = $business_id;
$business_data = $business->readOne();

if (!$business_data) {
    // Redirect to an error page or display a message
    header('Location: error.php?message=Business not found');
    exit;
}

$compliance_stats = $business->getComplianceStats($business_id);
$recent_inspections = $business->getRecentInspections($business_id, 5);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Business Details - Digital Health & Safety Inspection Platform</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="min-h-screen bg-gray-50">
    <?php include '../includes/navigation.php'; ?>

    <!-- Main Content -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 sm:py-8 md:ml-64 md:pt-24">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h2 class="text-2xl font-bold"><?php echo $business_data['name']; ?></h2>
                <p class="text-gray-600">Business Details & Compliance History</p>
            </div>
            <a href="businesses.php" class="bg-gray-300 text-gray-700 px-4 py-2 rounded-md hover:bg-gray-400">
                <i class="fas fa-arrow-left mr-2"></i>Back to Businesses
            </a>
        </div>

        <!-- Business Info -->
        <div class="bg-white rounded-lg shadow p-6 mb-6">
            <h3 class="text-lg font-bold mb-4">Business Information</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700">Business Name</label>
                        <p class="mt-1 text-gray-900"><?php echo $business_data['name']; ?></p>
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700">Registration Number</label>
                        <p class="mt-1 text-gray-900"><?php echo $business_data['registration_number']; ?></p>
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700">Business Type</label>
                        <p class="mt-1 text-gray-900"><?php echo $business_data['business_type']; ?></p>
                    </div>
                </div>
                <div>
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700">Address</label>
                        <p class="mt-1 text-gray-900"><?php echo $business_data['address']; ?></p>
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700">Contact Number</label>
                        <p class="mt-1 text-gray-900"><?php echo $business_data['contact_number']; ?></p>
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700">Email</label>
                        <p class="mt-1 text-gray-900"><?php echo $business_data['email']; ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Compliance Stats -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600">Total Inspections</p>
                        <p class="text-2xl font-bold"><?php echo $compliance_stats['total_inspections']; ?></p>
                    </div>
                    <i class="fas fa-file-alt text-3xl text-blue-500"></i>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600">Average Compliance</p>
                        <p class="text-2xl font-bold"><?php echo $compliance_stats['avg_compliance']; ?>%</p>
                    </div>
                    <i class="fas fa-check-circle text-3xl text-green-500"></i>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600">Total Violations</p>
                        <p class="text-2xl font-bold"><?php echo $compliance_stats['total_violations']; ?></p>
                    </div>
                    <i class="fas fa-exclamation-triangle text-3xl text-red-500"></i>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600">Completion Rate</p>
                        <p class="text-2xl font-bold"><?php echo $compliance_stats['compliance_rate']; ?>%</p>
                    </div>
                    <i class="fas fa-chart-line text-3xl text-purple-500"></i>
                </div>
            </div>
        </div>

        <!-- Recent Inspections -->
        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-bold mb-4">Recent Inspections</h3>
            <?php if (empty($recent_inspections)): ?>
                <p class="text-gray-500">No inspections recorded yet.</p>
            <?php else: ?>
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Inspector</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Compliance</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($recent_inspections as $inspection): ?>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900"><?php echo date('M j, Y', strtotime($inspection['scheduled_date'])); ?></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900"><?php echo $inspection['inspection_type']; ?></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900"><?php echo $inspection['inspector_name']; ?></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 py-1 text-xs font-medium rounded-full 
                                    <?php echo $inspection['status'] == 'completed' ? 'bg-green-100 text-green-800' : 
                                           ($inspection['status'] == 'in_progress' ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800'); ?>">
                                    <?php echo ucfirst($inspection['status']); ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium <?php echo $inspection['compliance_score'] >= 80 ? 'text-green-600' : 
                                                                   ($inspection['compliance_score'] >= 60 ? 'text-yellow-600' : 'text-red-600'); ?>">
                                    <?php echo $inspection['compliance_score'] ? $inspection['compliance_score'] . '%' : 'N/A'; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
