<?php
require_once '../utils/session_manager.php';
require_once '../config/database.php';
require_once '../models/User.php';
require_once '../models/Inspection.php';
require_once '../models/Violation.php';
require_once '../models/Business.php';
require_once '../utils/access_control.php';

// Ensure user has permission to view the admin dashboard
requirePermission('dashboard');

$database = new Database();
$db_core = $database->getConnection(Database::DB_CORE);
$db_scheduling = $database->getConnection(Database::DB_SCHEDULING);
$db_violations = $database->getConnection(Database::DB_VIOLATIONS);

// Get current user info
$user = new User($db_core);
$user->id = $_SESSION['user_id'];
$user->readOne();

// Instantiate models
$inspectionModel = new Inspection($db_scheduling);
$businessModel = new Business($db_core);
$userModel = new User($db_core);
$violationModel = new Violation($db_violations);

// Fetch stats for dashboard cards
$inspectionStats = $inspectionModel->getInspectionStatsByStatus();
$totalInspections = array_sum($inspectionStats);
$businessStats = $businessModel->getBusinessStats();
$totalBusinesses = $businessStats['total'] ?? 0;
$totalInspectors = $userModel->countActiveInspectors();
$activeViolations = $inspectionModel->countActiveViolations();

// Fetch data for tables
$upcomingInspections = $inspectionModel->readUpcoming(5);
$recentInspections = $inspectionModel->readRecent(5);
$communityReports = $violationModel->readCommunityReportsAwaitingAction(5);

// Fetch data for charts
$complianceTrendData = $inspectionModel->getComplianceTrend(30);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Digital Health & Safety Inspection Platform</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="min-h-screen bg-gray-50">
    <?php include '../includes/navigation.php'; ?>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 sm:py-8 md:ml-64 md:pt-24">
        <div class="space-y-8">
            <!-- Welcome Header -->
            <div class="bg-gradient-to-r from-blue-600 to-purple-700 rounded-lg shadow-lg p-6 text-white">
                <h1 class="text-2xl font-bold">Welcome, <?php echo htmlspecialchars($user->name); ?>!</h1>
                <p class="text-blue-100">Here's a snapshot of the inspection platform.</p>
            </div>

            <!-- Stats Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-600">Total Businesses</p>
                            <p class="text-2xl font-bold"><?php echo $totalBusinesses; ?></p>
                        </div>
                        <i class="fas fa-building text-3xl text-blue-500"></i>
                    </div>
                </div>
                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-600">Total Inspections</p>
                            <p class="text-2xl font-bold"><?php echo $totalInspections; ?></p>
                        </div>
                        <i class="fas fa-file-alt text-3xl text-green-500"></i>
                    </div>
                </div>
                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-600">Total Inspectors</p>
                            <p class="text-2xl font-bold"><?php echo $totalInspectors; ?></p>
                        </div>
                        <i class="fas fa-users text-3xl text-purple-500"></i>
                    </div>
                </div>
                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-600">Active Violations</p>
                            <p class="text-2xl font-bold"><?php echo $activeViolations; ?></p>
                        </div>
                        <i class="fas fa-exclamation-triangle text-3xl text-red-500"></i>
                    </div>
                </div>
            </div>

            <!-- Charts -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                <div class="bg-white rounded-lg shadow p-6">
                    <h3 class="text-lg font-medium mb-4">Inspections by Status</h3>
                    <canvas id="inspectionsByStatusChart"></canvas>
                </div>
                <div class="bg-white rounded-lg shadow p-6">
                    <h3 class="text-lg font-medium mb-4">Compliance Trend (Last 30 Days)</h3>
                    <canvas id="complianceTrendChart"></canvas>
                </div>
            </div>

            <!-- Data Tables -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                <!-- Upcoming Inspections -->
                <div class="bg-white rounded-lg shadow">
                    <div class="p-4 border-b">
                        <h3 class="text-lg font-medium">Upcoming Inspections</h3>
                    </div>
                    <div class="p-4">
                        <ul class="divide-y divide-gray-200">
                            <?php if (!empty($upcomingInspections)): ?>
                                <?php foreach ($upcomingInspections as $inspection): ?>
                                <li class="py-3 flex justify-between items-center">
                                    <div>
                                        <p class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($inspection['business_name']); ?></p>
                                        <p class="text-sm text-gray-500"><?php echo htmlspecialchars($inspection['inspection_type']); ?></p>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-sm text-gray-700"><?php echo date('M j, Y', strtotime($inspection['scheduled_date'])); ?></p>
                                        <a href="inspection_view.php?id=<?php echo $inspection['id']; ?>" class="text-xs text-blue-500 hover:underline">View</a>
                                    </div>
                                </li>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <li class="py-3 text-sm text-gray-500">No upcoming inspections.</li>
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
                                    <a href="violations.php" class="text-xs text-green-600 hover:underline font-semibold">Create Inspection</a>
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

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Data from PHP
        const inspectionStatusData = <?php echo json_encode($inspectionStats); ?>;
        const complianceTrendData = <?php echo json_encode($complianceTrendData); ?>;

        // Chart 1: Inspections by Status (Doughnut Chart)
        const statusCtx = document.getElementById('inspectionsByStatusChart').getContext('2d');
        new Chart(statusCtx, {
            type: 'doughnut',
            data: {
                labels: Object.keys(inspectionStatusData).map(s => s.charAt(0).toUpperCase() + s.slice(1).replace('_', ' ')),
                datasets: [{
                    label: 'Inspections',
                    data: Object.values(inspectionStatusData),
                    backgroundColor: [
                        'rgba(54, 162, 235, 0.8)', // scheduled (blue)
                        'rgba(255, 206, 86, 0.8)', // in_progress (yellow)
                        'rgba(75, 192, 192, 0.8)', // completed (green)
                        'rgba(255, 99, 132, 0.8)',  // overdue (red)
                        'rgba(153, 102, 255, 0.8)',// cancelled (purple)
                        'rgba(255, 159, 64, 0.8)'  // requested (orange)
                    ],
                    borderColor: '#fff',
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    title: {
                        display: false,
                        text: 'Inspections by Status'
                    }
                }
            }
        });

        // Chart 2: Compliance Trend (Line Chart)
        const trendCtx = document.getElementById('complianceTrendChart').getContext('2d');
        const trendLabels = complianceTrendData.map(d => d.inspection_date);
        const trendScores = complianceTrendData.map(d => d.avg_score);

        new Chart(trendCtx, {
            type: 'line',
            data: {
                labels: trendLabels,
                datasets: [{
                    label: 'Average Compliance Score',
                    data: trendScores,
                    fill: true,
                    borderColor: 'rgb(75, 192, 192)',
                    backgroundColor: 'rgba(75, 192, 192, 0.2)',
                    tension: 0.1
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 100,
                        ticks: {
                            callback: function(value) {
                                return value + '%'
                            }
                        }
                    }
                }
            }
        });
    });
    </script>
</body>
</html>