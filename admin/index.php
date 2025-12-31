<?php
require_once '../utils/session_manager.php';
require_once '../config/database.php';
require_once '../models/User.php';
require_once '../models/Inspection.php';
require_once '../models/Violation.php';
require_once '../models/Business.php';
require_once '../models/Notification.php';
require_once '../utils/access_control.php';

// Ensure user has permission to view the admin dashboard
requirePermission('dashboard');

$database = new Database();

// Get current user info
$user = new User($database);
$user->id = $_SESSION['user_id'];
$user->readOne();

// Instantiate models
$inspectionModel = new Inspection($database);
$businessModel = new Business($database);
$userModel = new User($database);
$violationModel = new Violation($database);

// Fetch stats that are NOT date-dependent for initial page load
$businessStats = $businessModel->getBusinessStats();
$totalBusinesses = $businessStats['total'] ?? 0;
$totalInspectors = $userModel->countActiveInspectors();
$pendingBusinessApprovals = $businessModel->countPending();

// Fetch data for tables
$upcomingInspections = $inspectionModel->readUpcoming(5);
$recentInspections = $inspectionModel->readRecent(5);
$communityReports = $violationModel->readCommunityReportsAwaitingAction(5);

// Get analytics data
$totalInspections = $inspectionModel->countAll();
$totalBusinesses = $businessModel->countAll();
$totalActiveViolations = $inspectionModel->countActiveViolations();
$averageCompliance = $inspectionModel->getAverageCompliance();

// Get data for analytics charts
$businessCountByType = $businessModel->getBusinessCountByType(); // This chart is not date-filtered
$date_range = $_GET['range'] ?? '30'; // Keep for dropdown selection state

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Digital Health & Safety Inspection Platform</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="min-h-screen bg-gray-50">
    <?php include '../includes/navigation.php'; ?>

    <div id="dashboard-container" class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 sm:py-8 md:ml-64 md:pt-24">
        <div class="space-y-8">
                <!-- Welcome Header and Date Filter -->
                <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                    <div class="bg-gradient-to-r from-yellow-400 to-yellow-600 rounded-lg shadow-lg p-6 text-gray-900 flex-grow">
                        <h1 class="text-2xl font-bold">Welcome, <?php echo htmlspecialchars($user->name); ?>!</h1>
                        <p class="text-blue-100">Here's a snapshot of the inspection platform.</p>
                    </div>
                    <div class="flex items-center gap-4">
                        <div class="bg-white p-4 rounded-lg shadow">
                            <select id="dateRangeFilter" class="border-gray-300 rounded-md shadow-sm">
                                <option value="7" <?php echo ($date_range == '7') ? 'selected' : ''; ?>>Last 7 Days</option>
                                <option value="30" <?php echo ($date_range == '30') ? 'selected' : ''; ?>>Last 30 Days</option>
                                <option value="90" <?php echo ($date_range == '90') ? 'selected' : ''; ?>>Last 90 Days</option>
                                <option value="all" <?php echo ($date_range == 'all') ? 'selected' : ''; ?>>All Time</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div id="dashboard-content">
                    <!-- Stats Cards -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 mt-8">
                        <a href="businesses.php" class="bg-white rounded-lg shadow p-6 hover:shadow-lg transition-shadow">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm text-gray-600">Total Businesses</p>
                                    <p class="text-2xl font-bold"><?php echo $totalBusinesses; ?></p>
                                </div>
                                <i class="fas fa-building text-3xl text-yellow-500"></i>
                            </div>
                        </a>
                        <a href="verify_business_owners.php" class="bg-white rounded-lg shadow p-6 hover:shadow-lg transition-shadow">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm text-gray-600">Pending Approvals</p>
                                    <p class="text-2xl font-bold text-orange-500"><?php echo $pendingBusinessApprovals; ?></p>
                                </div>
                                <i class="fas fa-user-clock text-3xl text-orange-500"></i>
                            </div>
                        </a>
                        <a href="inspections.php" class="bg-white rounded-lg shadow p-6 hover:shadow-lg transition-shadow">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm text-gray-600">Total Inspections</p>
                                    <p id="totalInspectionsStat" class="text-2xl font-bold text-gray-900">...</p>
                                </div>
                                <i class="fas fa-file-alt text-3xl text-green-600"></i>
                            </div>
                        </a>
                        <a href="user_management.php?role=inspector" class="bg-white rounded-lg shadow p-6 hover:shadow-lg transition-shadow">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm text-gray-600">Total Inspectors</p>
                                    <p class="text-2xl font-bold text-gray-900"><?php echo $totalInspectors; ?></p>
                                </div>
                                <i class="fas fa-users text-3xl text-gray-600"></i>
                            </div>
                        </a>
                        <a href="violations.php?status=active" class="bg-white rounded-lg shadow p-6 hover:shadow-lg transition-shadow">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm text-gray-600">Active Violations</p>
                                    <p id="activeViolationsStat" class="text-2xl font-bold text-gray-900">...</p>
                                </div>
                                <i class="fas fa-exclamation-triangle text-3xl text-red-600"></i>
                            </div>
                        </a>
                        <div class="bg-white rounded-lg shadow p-6">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm text-gray-600">Average Compliance</p>
                                    <p id="averageComplianceStat" class="text-2xl font-bold">...%</p>
                                </div>
                                <i class="fas fa-check-circle text-3xl text-green-600"></i>
                            </div>
                        </div>
                    </div>

                    <!-- Analytics Charts -->
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mt-8 mb-6">
                        <div class="bg-white rounded-lg shadow p-6">
                            <h3 class="text-lg font-medium mb-4">Inspections by Status</h3>
                            <canvas id="inspectionsByStatusChart"></canvas>
                        </div>
                        <div class="bg-white rounded-lg shadow p-6">
                            <h3 class="text-lg font-medium mb-4">Inspections by Priority</h3>
                            <canvas id="inspectionsByPriorityChart"></canvas>
                        </div>
                    </div>
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mt-8">
                        <div class="bg-white rounded-lg shadow p-6">
                            <h3 class="text-lg font-medium mb-4">Businesses by Type</h3>
                            <canvas id="businessByTypeChart" data-business-types='<?php echo htmlspecialchars(json_encode($businessCountByType), ENT_QUOTES, 'UTF-8'); ?>'></canvas>
                        </div>
                        <div class="bg-white rounded-lg shadow p-6">
                            <h3 class="text-lg font-medium mb-4">Violations by Severity</h3>
                            <canvas id="violationsBySeverityChart"></canvas>
                        </div>
                    </div>

                    <!-- Data Tables -->
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mt-8">
                        <!-- Upcoming Inspections -->
                        <div class="bg-white rounded-lg shadow">
                            <div class="p-4 border-b flex justify-between items-center">
                                <h3 class="text-lg font-medium">Upcoming Inspections</h3>
                                <div class="relative text-gray-900">
                                    <input type="text" id="upcomingInspectionsSearch" placeholder="Search..." class="pl-8 pr-2 py-1 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    <i class="fas fa-search absolute left-2.5 top-1/2 -translate-y-1/2 text-gray-400"></i>
                                </div>
                            </div>
                            <div class="p-4">
                                <ul id="upcomingInspectionsList" class="divide-y divide-gray-200">
                                    <?php if (!empty($upcomingInspections)): ?>
                                        <?php foreach ($upcomingInspections as $inspection): ?>
                                        <li class="py-3 flex justify-between items-center" data-search-term="<?php echo htmlspecialchars(strtolower($inspection['business_name'] . ' ' . $inspection['inspection_type'])); ?>">
                                            <div>
                                                <p class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($inspection['business_name']); ?></p>
                                                <p class="text-sm text-gray-500"><?php echo htmlspecialchars($inspection['inspection_type']); ?></p>
                                            </div>
                                            <div class="text-right">
                                                <p class="text-sm text-gray-700"><?php echo date('M j, Y', strtotime($inspection['scheduled_date'])); ?></p>
                                                <a href="inspection_view.php?id=<?php echo $inspection['id']; ?>" class="text-xs text-yellow-600 hover:underline">View</a>
                                            </div>
                                        </li>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <li id="noUpcomingInspections" class="py-3 text-sm text-gray-500">No upcoming inspections.</li>
                                    <?php endif; ?>
                                </ul>
                            </div>
                        </div>

                        <!-- Recent Activity -->
                        <div class="bg-white rounded-lg shadow p-6">
                            <div class="p-4 border-b">
                                <h3 class="text-lg font-medium">Recent Activity</h3>
                            </div>
                            <div class="p-4">
                                <ul class="divide-y divide-gray-200">
                                    <?php if (!empty($recentInspections)): ?>
                                        <?php foreach ($recentInspections as $inspection): ?>
                                        <li class="py-3 flex justify-between items-center">
                                            <div>
                                                <p class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($inspection['business_name']); ?></p>
                                                <p class="text-sm text-gray-500">
                                                    Inspection <?php echo htmlspecialchars($inspection['status']); ?> by 
                                                    <span class="font-semibold"><?php echo htmlspecialchars($inspection['inspector_name'] ?? 'N/A'); ?></span>
                                                </p>
                                            </div>
                                            <div class="text-right">
                                                <p class="text-sm text-gray-700"><?php echo date('M j, Y', strtotime($inspection['updated_at'])); ?></p>
                                                <span class="px-2 py-1 text-xs font-medium rounded-full 
                                                    <?php echo $inspection['status'] == 'completed' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'; ?>">
                                                    <?php echo ucfirst($inspection['status']); ?>
                                                </span>
                                            </div>
                                        </li>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <li class="py-3 text-sm text-gray-500">No recent activity.</li>
                                    <?php endif; ?>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>

            <!-- Community Reports Awaiting Action -->
            <div class="bg-white rounded-lg shadow">
                <div class="p-4 border-b flex justify-between items-center">
                    <h3 class="text-lg font-medium">Community Reports Awaiting Action</h3>
                    <a href="violations.php" class="text-sm text-blue-600 hover:underline">View All</a>
                </div>
                <div class="p-4">
                    <ul class="divide-y divide-gray-200">
                        <?php if (!empty($communityReports)): ?>
                            <?php foreach ($communityReports as $report): ?>
                            <li class="py-3 flex justify-between items-center">
                                <div>
                                    <p class="text-sm font-medium text-gray-900 truncate" title="<?php echo htmlspecialchars($report['description']); ?>">
                                        <?php echo htmlspecialchars(substr($report['description'], 0, 70)); ?><?php echo strlen($report['description']) > 70 ? '...' : ''; ?>
                                    </p>
                                    <p class="text-sm text-gray-500">
                                        For: <span class="font-semibold"><?php echo htmlspecialchars($report['business_name']); ?></span>
                                    </p>
                                </div>
                                <div class="text-right ml-4 flex-shrink-0">
                                    <p class="text-xs text-gray-500"><?php echo date('M j, Y', strtotime($report['created_at'])); ?></p>
                                    <a href="violations.php" class="text-xs text-yellow-600 hover:underline font-semibold">Create Inspection</a>
                                </div>
                            </li>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <li class="py-10 text-center text-sm text-gray-500"><i class="fas fa-check-circle text-green-500 text-2xl mb-2"></i><p>No community reports are awaiting action.</p></li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <script src="js/dashboard.js" defer></script>
</body>
</html>