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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" integrity="sha512-SnH5WK+bZxgPHs44uWIX+LLJAJ9/2PkPKZ5QiAj6Ta86w+fsb2TkcmfRyVX3pBnMFcV7oQPJkl9QevSCWr3W6A==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { sans: ['Inter', 'sans-serif'] },
                    animation: {
                        'fade-in': 'fadeIn 0.5s ease-out',
                        'slide-up': 'slideUp 0.5s ease-out',
                    },
                    keyframes: {
                        fadeIn: {
                            '0%': { opacity: '0' },
                            '100%': { opacity: '1' },
                        },
                        slideUp: {
                            '0%': { transform: 'translateY(20px)', opacity: '0' },
                            '100%': { transform: 'translateY(0)', opacity: '1' },
                        }
                    }
                }
            }
        }
    </script>
    <style>
        body { -webkit-font-smoothing: antialiased; -moz-osx-font-smoothing: grayscale; }
    </style>
</head>
<body class="min-h-screen bg-gray-50 font-sans text-gray-900">
    <?php include '../includes/navigation.php'; ?>

    <div id="dashboard-container" class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 sm:py-8 md:ml-64 md:pt-24">
        <div class="space-y-8 animate-fade-in">
                <!-- Welcome Header and Date Filter -->
                <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                    <div class="bg-gradient-to-r from-yellow-500 to-yellow-600 rounded-2xl shadow-lg p-8 text-white flex-grow relative overflow-hidden">
                        <div class="absolute top-0 right-0 -mt-4 -mr-4 w-24 h-24 bg-white opacity-10 rounded-full blur-xl"></div>
                        <div class="absolute bottom-0 left-0 -mb-4 -ml-4 w-24 h-24 bg-white opacity-10 rounded-full blur-xl"></div>
                        <h1 class="text-3xl font-bold relative z-10">Welcome back, <?php echo htmlspecialchars($user->name); ?>!</h1>
                        <p class="text-yellow-50 mt-2 relative z-10">Here's what's happening in the inspection platform today.</p>
                    </div>
                    <div class="flex items-center gap-4">
                        <div class="bg-white p-2 rounded-xl shadow-sm border border-gray-200">
                            <select id="dateRangeFilter" class="border-none text-sm font-medium text-gray-600 focus:ring-0 cursor-pointer bg-transparent">
                                <option value="7" <?php echo ($date_range == '7') ? 'selected' : ''; ?>>Last 7 Days</option>
                                <option value="30" <?php echo ($date_range == '30') ? 'selected' : ''; ?>>Last 30 Days</option>
                                <option value="90" <?php echo ($date_range == '90') ? 'selected' : ''; ?>>Last 90 Days</option>
                                <option value="all" <?php echo ($date_range == 'all') ? 'selected' : ''; ?>>All Time</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div id="dashboard-content" class="animate-slide-up">
                    <!-- Stats Cards -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 mt-8">
                        <a href="businesses.php" class="group bg-white rounded-2xl shadow-sm border border-gray-100 p-6 hover:shadow-md transition-all duration-300 hover:-translate-y-1">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm font-medium text-gray-500">Total Businesses</p>
                                    <p class="text-3xl font-bold text-gray-900 mt-1"><?php echo $totalBusinesses; ?></p>
                                </div>
                                <div class="h-12 w-12 bg-yellow-50 rounded-full flex items-center justify-center text-yellow-600 group-hover:bg-yellow-100 transition-colors">
                                    <i class="fas fa-building text-xl"></i>
                                </div>
                            </div>
                        </a>
                        <a href="verify_business_owners.php" class="group bg-white rounded-2xl shadow-sm border border-gray-100 p-6 hover:shadow-md transition-all duration-300 hover:-translate-y-1">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm font-medium text-gray-500">Pending Approvals</p>
                                    <p class="text-3xl font-bold text-orange-600 mt-1"><?php echo $pendingBusinessApprovals; ?></p>
                                </div>
                                <div class="h-12 w-12 bg-orange-50 rounded-full flex items-center justify-center text-orange-600 group-hover:bg-orange-100 transition-colors">
                                    <i class="fas fa-user-clock text-xl"></i>
                                </div>
                            </div>
                        </a>
                        <a href="inspections.php" class="group bg-white rounded-2xl shadow-sm border border-gray-100 p-6 hover:shadow-md transition-all duration-300 hover:-translate-y-1">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm font-medium text-gray-500">Total Inspections</p>
                                    <p id="totalInspectionsStat" class="text-3xl font-bold text-gray-900 mt-1">...</p>
                                </div>
                                <div class="h-12 w-12 bg-blue-50 rounded-full flex items-center justify-center text-blue-600 group-hover:bg-blue-100 transition-colors">
                                    <i class="fas fa-clipboard-check text-xl"></i>
                                </div>
                            </div>
                        </a>
                        <a href="user_management.php?role=inspector" class="group bg-white rounded-2xl shadow-sm border border-gray-100 p-6 hover:shadow-md transition-all duration-300 hover:-translate-y-1">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm font-medium text-gray-500">Total Inspectors</p>
                                    <p class="text-3xl font-bold text-gray-900 mt-1"><?php echo $totalInspectors; ?></p>
                                </div>
                                <div class="h-12 w-12 bg-indigo-50 rounded-full flex items-center justify-center text-indigo-600 group-hover:bg-indigo-100 transition-colors">
                                    <i class="fas fa-users text-xl"></i>
                                </div>
                            </div>
                        </a>
                        <a href="violations.php?status=active" class="group bg-white rounded-2xl shadow-sm border border-gray-100 p-6 hover:shadow-md transition-all duration-300 hover:-translate-y-1">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm font-medium text-gray-500">Active Violations</p>
                                    <p id="activeViolationsStat" class="text-3xl font-bold text-red-600 mt-1">...</p>
                                </div>
                                <div class="h-12 w-12 bg-red-50 rounded-full flex items-center justify-center text-red-600 group-hover:bg-red-100 transition-colors">
                                    <i class="fas fa-exclamation-triangle text-xl"></i>
                                </div>
                            </div>
                        </a>
                        <div class="group bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm font-medium text-gray-500">Avg. Compliance</p>
                                    <p id="averageComplianceStat" class="text-3xl font-bold text-green-600 mt-1">...%</p>
                                </div>
                                <div class="h-12 w-12 bg-green-50 rounded-full flex items-center justify-center text-green-600">
                                    <i class="fas fa-chart-pie text-xl"></i>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Analytics Charts -->
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mt-8 mb-6">
                        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                            <h3 class="text-lg font-bold text-gray-900 mb-4">Inspections by Status</h3>
                            <div class="relative h-64">
                                <canvas id="inspectionsByStatusChart"></canvas>
                            </div>
                        </div>
                        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                            <h3 class="text-lg font-bold text-gray-900 mb-4">Inspections by Priority</h3>
                            <div class="relative h-64">
                                <canvas id="inspectionsByPriorityChart"></canvas>
                            </div>
                        </div>
                    </div>
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mt-8">
                        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                            <h3 class="text-lg font-bold text-gray-900 mb-4">Businesses by Type</h3>
                            <div class="relative h-64">
                                <canvas id="businessByTypeChart" data-business-types='<?php echo htmlspecialchars(json_encode($businessCountByType), ENT_QUOTES, 'UTF-8'); ?>'></canvas>
                            </div>
                        </div>
                        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                            <h3 class="text-lg font-bold text-gray-900 mb-4">Violations by Severity</h3>
                            <div class="relative h-64">
                                <canvas id="violationsBySeverityChart"></canvas>
                            </div>
                        </div>
                    </div>

                    <!-- Data Tables -->
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mt-8">
                        <!-- Upcoming Inspections -->
                        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                            <div class="p-6 border-b border-gray-100 flex justify-between items-center bg-gray-50/50">
                                <h3 class="text-lg font-bold text-gray-900">Upcoming Inspections</h3>
                                <div class="relative text-gray-900">
                                    <input type="text" id="upcomingInspectionsSearch" placeholder="Search..." class="pl-9 pr-3 py-1.5 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:border-transparent bg-white">
                                    <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-xs"></i>
                                </div>
                            </div>
                            <div class="p-0">
                                <ul id="upcomingInspectionsList" class="divide-y divide-gray-100">
                                    <?php if (!empty($upcomingInspections)): ?>
                                        <?php foreach ($upcomingInspections as $inspection): ?>
                                        <li class="p-4 hover:bg-gray-50 transition-colors flex justify-between items-center" data-search-term="<?php echo htmlspecialchars(strtolower($inspection['business_name'] . ' ' . $inspection['inspection_type'])); ?>">
                                            <div>
                                                <p class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($inspection['business_name']); ?></p>
                                                <p class="text-xs text-gray-500 mt-0.5"><i class="fas fa-clipboard-list mr-1"></i> <?php echo htmlspecialchars($inspection['inspection_type']); ?></p>
                                            </div>
                                            <div class="text-right">
                                                <p class="text-sm font-medium text-gray-700"><?php echo date('M j', strtotime($inspection['scheduled_date'])); ?></p>
                                                <a href="inspection_view.php?id=<?php echo $inspection['id']; ?>" class="text-xs text-yellow-600 hover:text-yellow-700 font-medium">View Details</a>
                                            </div>
                                        </li>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <li id="noUpcomingInspections" class="p-6 text-center text-sm text-gray-500">No upcoming inspections found.</li>
                                    <?php endif; ?>
                                </ul>
                            </div>
                        </div>

                        <!-- Recent Activity -->
                        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                            <div class="p-6 border-b border-gray-100 bg-gray-50/50">
                                <h3 class="text-lg font-bold text-gray-900">Recent Activity</h3>
                            </div>
                            <div class="p-0">
                                <ul class="divide-y divide-gray-100">
                                    <?php if (!empty($recentInspections)): ?>
                                        <?php foreach ($recentInspections as $inspection): ?>
                                        <li class="p-4 hover:bg-gray-50 transition-colors flex justify-between items-center">
                                            <div>
                                                <p class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($inspection['business_name']); ?></p>
                                                <p class="text-xs text-gray-500 mt-0.5">
                                                    Inspection <?php echo htmlspecialchars($inspection['status']); ?> by 
                                                    <span class="font-medium text-gray-700"><?php echo htmlspecialchars($inspection['inspector_name'] ?? 'N/A'); ?></span>
                                                </p>
                                            </div>
                                            <div class="text-right">
                                                <p class="text-xs text-gray-500 mb-1"><?php echo date('M j', strtotime($inspection['updated_at'])); ?></p>
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                                                    <?php echo $inspection['status'] == 'completed' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'; ?>">
                                                    <?php echo ucfirst($inspection['status']); ?>
                                                </span>
                                            </div>
                                        </li>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <li class="p-6 text-center text-sm text-gray-500">No recent activity recorded.</li>
                                    <?php endif; ?>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>

            <!-- Community Reports Awaiting Action -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden mt-8 animate-slide-up" style="animation-delay: 0.1s;">
                <div class="p-6 border-b border-gray-100 flex justify-between items-center bg-gray-50/50">
                    <h3 class="text-lg font-bold text-gray-900">Community Reports Awaiting Action</h3>
                    <a href="violations.php" class="text-sm font-medium text-yellow-600 hover:text-yellow-700 flex items-center">View All <i class="fas fa-arrow-right ml-1 text-xs"></i></a>
                </div>
                <div class="p-0">
                    <ul class="divide-y divide-gray-100">
                        <?php if (!empty($communityReports)): ?>
                            <?php foreach ($communityReports as $report): ?>
                            <li class="p-4 hover:bg-gray-50 transition-colors flex justify-between items-center">
                                <div>
                                    <p class="text-sm font-medium text-gray-900 truncate" title="<?php echo htmlspecialchars($report['description']); ?>">
                                        <?php echo htmlspecialchars(substr($report['description'], 0, 70)); ?><?php echo strlen($report['description']) > 70 ? '...' : ''; ?>
                                    </p>
                                    <p class="text-xs text-gray-500 mt-0.5">
                                        Reported for: <span class="font-medium text-gray-700"><?php echo htmlspecialchars($report['business_name']); ?></span>
                                    </p>
                                </div>
                                <div class="text-right ml-4 flex-shrink-0">
                                    <p class="text-xs text-gray-400 mb-1"><?php echo date('M j, Y', strtotime($report['created_at'])); ?></p>
                                    <a href="violations.php" class="inline-flex items-center px-2.5 py-1.5 border border-transparent text-xs font-medium rounded text-yellow-700 bg-yellow-100 hover:bg-yellow-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-yellow-500">
                                        Create Inspection
                                    </a>
                                </div>
                            </li>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <li class="p-10 text-center text-sm text-gray-500">
                                <div class="flex flex-col items-center justify-center">
                                    <div class="h-12 w-12 bg-green-50 rounded-full flex items-center justify-center mb-3">
                                        <i class="fas fa-check text-green-500 text-xl"></i>
                                    </div>
                                    <p>No community reports are awaiting action.</p>
                                </div>
                            </li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <script src="js/dashboard.js" defer></script>
</body>
</html>