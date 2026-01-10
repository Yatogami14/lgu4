<?php
require_once '../utils/session_manager.php';
require_once '../config/database.php';
require_once '../models/User.php';
require_once '../models/Business.php';
require_once '../models/Inspection.php';
require_once '../utils/access_control.php';

requirePermission('dashboard');

$database = new Database();

$user = new User($database);
$user->id = $_SESSION['user_id'];
$user->readOne();

$businessModel = new Business($database);
$inspectionModel = new Inspection($database);

// Handle search
$search = $_GET['search'] ?? '';

// Get stats
$total_businesses = $businessModel->countAll();
$avg_compliance = $inspectionModel->getAverageCompliance();
$business_stats = $businessModel->getBusinessStats();

// Get businesses
$businesses = $businessModel->readAllWithCompliance($search);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Community Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { sans: ['Poppins', 'sans-serif'] },
                }
            }
        }
    </script>
</head>
<body class="min-h-screen bg-gray-50 font-sans">
    <?php include '../includes/navigation.php'; ?>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 sm:py-8 md:ml-64 md:pt-24">
        <div class="bg-gradient-to-r from-green-500 to-cyan-500 rounded-lg shadow-lg p-6 text-white mb-8">
            <h1 class="text-2xl font-bold">Welcome, <?php echo htmlspecialchars($user->name); ?>!</h1>
            <p class="text-green-100">Explore local business compliance and help keep our community safe.</p>
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600">Total Businesses</p>
                        <p class="text-2xl font-bold"><?php echo $total_businesses; ?></p>
                    </div>
                    <i class="fas fa-building text-3xl text-blue-500"></i>
                </div>
            </div>
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600">Avg. Compliance</p>
                        <p class="text-2xl font-bold"><?php echo $avg_compliance; ?>%</p>
                    </div>
                    <i class="fas fa-check-circle text-3xl text-green-500"></i>
                </div>
            </div>
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600">High-Risk Businesses</p>
                        <p class="text-2xl font-bold"><?php echo $business_stats['high_risk'] ?? 0; ?></p>
                    </div>
                    <i class="fas fa-exclamation-triangle text-3xl text-red-500"></i>
                </div>
            </div>
        </div>

        <!-- Search and Business List -->
        <div class="bg-white rounded-lg shadow">
            <div class="p-6 border-b">
                <h3 class="text-lg font-bold">Find a Business</h3>
                <form method="GET" class="mt-4">
                    <div class="relative">
                        <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search by name, address, or type..." class="w-full pl-10 pr-4 py-2 border rounded-lg">
                        <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                    </div>
                </form>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Business Name</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Address</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Compliance Score</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php if (empty($businesses)): ?>
                            <tr><td colspan="4" class="text-center py-10 text-gray-500">No businesses found.</td></tr>
                        <?php else: ?>
                            <?php foreach ($businesses as $business): ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap"><div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($business['name']); ?></div></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo htmlspecialchars($business['business_type']); ?></td>
                                <td class="px-6 py-4"><div class="text-sm text-gray-900"><?php echo htmlspecialchars($business['address']); ?></div></td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="text-sm font-bold <?php echo ($business['compliance_score'] ?? 0) >= 80 ? 'text-green-600' : (($business['compliance_score'] ?? 0) >= 50 ? 'text-yellow-600' : 'text-red-600'); ?>">
                                        <?php echo ($business['compliance_score'] !== null) ? ($business['compliance_score'] . '%') : 'N/A'; ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>