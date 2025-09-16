<?php
require_once '../utils/session_manager.php';
require_once '../config/database.php';
require_once '../models/User.php';
require_once '../models/Inspection.php';
require_once '../models/InspectionType.php';
require_once '../models/Violation.php';
require_once '../models/Business.php';
require_once '../utils/access_control.php';

requirePermission('dashboard');

$database = new Database();

$user = new User($database);
$user->id = $_SESSION['user_id'];
$user->readOne();

$inspection = new Inspection($database);
$business = new Business($database);
$violation = new Violation($database);
$inspectionTypeModel = new InspectionType($database);

// Handle form submissions for inspection request
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['request_inspection'])) {
    $inspection->business_id = $_POST['business_id'];
    $inspection->inspector_id = null; // Business owners cannot assign inspectors.
    $inspection->inspection_type_id = $_POST['inspection_type_id'];
    $inspection->scheduled_date = $_POST['preferred_date'];
    $inspection->status = 'requested'; // Status is now 'requested'
    $inspection->priority = 'low'; // Default priority for requests
    $inspection->notes = $_POST['notes'];

    if ($inspection->create()) {
        $_SESSION['success_message'] = 'Inspection requested successfully. An administrator will review it shortly.';
    } else {
        $_SESSION['error_message'] = 'Failed to request inspection.';
    }
    header('Location: index.php');
    exit;
}

// Get user-specific data for business owner
$user_businesses_stmt = $business->readByOwnerId($_SESSION['user_id']);
$user_businesses = $user_businesses_stmt->fetchAll(PDO::FETCH_ASSOC);
$business_ids = array_column($user_businesses, 'id');

// Initialize stats
$recent_inspections = [];
$total_inspections = 0;
$average_compliance = 0;
$open_violations = 0;
$compliance_trend = [];

if (!empty($business_ids)) {
    $recent_inspections = $inspection->readRecentForBusinesses($business_ids, 5);
    $total_inspections = $inspection->countAllForBusinesses($business_ids);
    $average_compliance = $inspection->getAverageComplianceForBusinesses($business_ids);
    $open_violations = $violation->countActiveForBusinesses($business_ids);
    $compliance_trend = $inspection->getComplianceTrendForBusinesses($business_ids, 30);
}

// Prepare data for the chart
$chart_labels = json_encode(array_column($compliance_trend, 'inspection_date'));
$chart_data = json_encode(array_column($compliance_trend, 'avg_score'));

// Get all inspection types for the modal
$allInspectionTypes = $inspectionTypeModel->readAll()->fetchAll(PDO::FETCH_ASSOC);

// Get success/error messages
$success_message = $_SESSION['success_message'] ?? '';
$error_message = $_SESSION['error_message'] ?? '';
unset($_SESSION['success_message'], $_SESSION['error_message']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Business Owner Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="min-h-screen bg-gray-50">
    <?php include '../includes/navigation.php'; ?>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 sm:py-8 md:ml-64 md:pt-24">
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6">
            <div class="bg-gradient-to-r from-blue-600 to-purple-700 rounded-lg shadow-lg p-6 text-white flex-grow">
                <h1 class="text-2xl font-bold">Welcome, <?php echo htmlspecialchars($user->name); ?>!</h1>
                <p class="text-blue-100">This is your business portal dashboard.</p>
            </div>
            <div class="mt-4 md:mt-0 md:ml-4 flex-shrink-0">
                <button onclick="document.getElementById('requestModal').classList.remove('hidden')" 
                        class="w-full md:w-auto bg-white text-blue-700 font-semibold px-6 py-3 rounded-lg shadow-md hover:bg-gray-50 transition-colors">
                    <i class="fas fa-plus mr-2"></i>Request Inspection
                </button>
            </div>
        </div>

        <!-- Success/Error Messages -->
        <?php if ($success_message): ?>
        <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6" role="alert"><p><?php echo $success_message; ?></p></div>
        <?php endif; ?>
        <?php if ($error_message): ?>
        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6" role="alert"><p><?php echo $error_message; ?></p></div>
        <?php endif; ?>

        <div class="grid grid-cols-1 lg:grid-cols-3 lg:gap-8 mt-8">
            <!-- Left Column -->
            <div class="lg:col-span-2 space-y-8">
                <!-- Stats Cards -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="bg-white rounded-lg shadow p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm text-gray-600">Your Businesses</p>
                                <p class="text-2xl font-bold"><?php echo count($user_businesses); ?></p>
                            </div>
                            <i class="fas fa-building text-3xl text-blue-500"></i>
                        </div>
                    </div>
                    <div class="bg-white rounded-lg shadow p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm text-gray-600">Total Inspections</p>
                                <p class="text-2xl font-bold"><?php echo $total_inspections; ?></p>
                            </div>
                            <i class="fas fa-file-alt text-3xl text-green-500"></i>
                        </div>
                    </div>
                    <div class="bg-white rounded-lg shadow p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm text-gray-600">Open Violations</p>
                                <p class="text-2xl font-bold"><?php echo $open_violations; ?></p>
                            </div>
                            <i class="fas fa-exclamation-triangle text-3xl text-red-500"></i>
                        </div>
                    </div>
                    <div class="bg-white rounded-lg shadow p-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm text-gray-600">Avg. Compliance</p>
                                <p class="text-2xl font-bold"><?php echo $average_compliance; ?>%</p>
                            </div>
                            <i class="fas fa-check-circle text-3xl text-purple-500"></i>
                        </div>
                    </div>
                </div>

                <!-- Compliance Trend Chart -->
                <div class="bg-white rounded-lg shadow p-6">
                    <h3 class="text-lg font-bold mb-4">Compliance Score Trend (Last 30 Days)</h3>
                    <div>
                        <canvas id="complianceTrendChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- Right Column -->
            <div class="lg:col-span-1 space-y-8">
                <!-- Your Businesses -->
                <div class="bg-white rounded-lg shadow">
                    <div class="p-6">
                        <h3 class="text-lg font-bold mb-4">Your Businesses</h3>
                        <?php if (empty($user_businesses)): ?>
                            <p class="text-gray-500">No businesses are associated with your account.</p>
                        <?php else: ?>
                            <ul class="divide-y divide-gray-200">
                                <?php foreach ($user_businesses as $bus): ?>
                                    <li class="py-3">
                                        <a href="business_view.php?id=<?php echo $bus['id']; ?>" class="block hover:bg-gray-50 p-2 rounded-md transition-colors">
                                            <p class="font-medium text-gray-900"><?php echo htmlspecialchars($bus['name']); ?></p>
                                            <p class="text-sm text-gray-500 truncate"><?php echo htmlspecialchars($bus['address']); ?></p>
                                        </a>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Recent Inspections -->
                <div class="bg-white rounded-lg shadow">
                    <div class="p-6">
                        <h3 class="text-lg font-bold mb-4">Recent Inspections</h3>
                        <?php if (empty($recent_inspections)): ?>
                            <p class="text-gray-500">No inspections recorded for your businesses yet.</p>
                        <?php else: ?>
                            <ul class="divide-y divide-gray-200">
                                <?php foreach ($recent_inspections as $insp): ?>
                                    <li class="py-3 flex justify-between items-center">
                                        <div>
                                            <p class="font-medium text-gray-900"><?php echo htmlspecialchars($insp['business_name']); ?></p>
                                            <p class="text-sm text-gray-500"><?php echo htmlspecialchars($insp['inspection_type']); ?> on <?php echo date('M j, Y', strtotime($insp['scheduled_date'])); ?></p>
                                        </div>
                                        <a href="inspection_view.php?id=<?php echo $insp['id']; ?>" class="text-sm text-blue-600 hover:underline flex-shrink-0 ml-4">View</a>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Request Inspection Modal -->
    <div id="requestModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
        <div class="relative top-20 mx-auto p-5 border w-full max-w-lg shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <h3 class="text-lg font-medium text-gray-900">Request a New Inspection</h3>
                <form method="POST" class="mt-4 space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Business</label>
                        <select name="business_id" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                            <option value="">Select Business</option>
                            <?php foreach ($user_businesses as $business_row): ?>
                                <option value="<?php echo $business_row['id']; ?>"><?php echo htmlspecialchars($business_row['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Inspection Type</label>
                        <select name="inspection_type_id" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                            <option value="">Select Type</option>
                            <?php foreach ($allInspectionTypes as $type): ?>
                                <option value="<?php echo $type['id']; ?>"><?php echo htmlspecialchars($type['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Preferred Date</label>
                        <input type="date" name="preferred_date" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" min="<?php echo date('Y-m-d'); ?>">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Notes</label>
                        <textarea name="notes" placeholder="Any specific reason for this request?" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm"></textarea>
                    </div>
                    <div class="flex justify-end space-x-3">
                        <button type="button" onclick="document.getElementById('requestModal').classList.add('hidden')" 
                                class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400">
                            Cancel
                        </button>
                        <button type="submit" name="request_inspection" 
                                class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                            Submit Request
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        window.onclick = function(event) {
            const modal = document.getElementById('requestModal');
            if (event.target == modal) {
                modal.classList.add('hidden');
            }
        };

        // Compliance Trend Chart
        const ctx = document.getElementById('complianceTrendChart');
        if (ctx) {
            new Chart(ctx.getContext('2d'), {
                type: 'line',
                data: {
                    labels: <?php echo $chart_labels; ?>,
                    datasets: [{
                        label: 'Average Compliance Score',
                        data: <?php echo $chart_data; ?>,
                        borderColor: 'rgba(59, 130, 246, 1)',
                        backgroundColor: 'rgba(59, 130, 246, 0.2)',
                        fill: true,
                        tension: 0.4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            max: 100,
                            ticks: {
                                callback: function(value) { return value + '%' }
                            }
                        }
                    }
                }
            });
        }
    </script>
</body>
</html>