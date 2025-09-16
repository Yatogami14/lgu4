<?php
require_once '../utils/session_manager.php';
require_once '../models/User.php';
require_once '../models/Inspection.php';
require_once '../models/Business.php';
require_once '../models/Notification.php';
require_once '../models/Violation.php';
require_once '../utils/access_control.php';

// Check if user is logged in and has permission to access this page
requirePermission('analytics');

$database = new Database();
$db_core = $database->getConnection(Database::DB_CORE);
$db_scheduling = $database->getConnection(Database::DB_SCHEDULING);
$db_violations = $database->getConnection(Database::DB_VIOLATIONS);

$user = new User($database);
$user->id = $_SESSION['user_id'];
$user->readOne();

$inspection = new Inspection($database);
$business = new Business($database);
$violation = new Violation($database);

// Get analytics data
$totalInspections = $inspection->countAll();
$totalBusinesses = $business->countAll();
$totalActiveViolations = $inspection->countActiveViolations();
$averageCompliance = $inspection->getAverageCompliance();

// Get data for charts
$businessCountByType = $business->getBusinessCountByType();
$violationStatsBySeverity = $violation->getViolationStatsBySeverity();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analytics - Digital Health & Safety Inspection Platform</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="min-h-screen bg-gray-50">
    <!-- Include Navigation -->
    <?php include '../includes/navigation.php'; ?>

    <!-- Main Content -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 sm:py-8 md:ml-64 md:pt-24">
        <h2 class="text-2xl font-bold mb-6">Analytics Dashboard</h2>

        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600">Total Inspections</p>
                        <p class="text-2xl font-bold"><?php echo $totalInspections; ?></p>
                    </div>
                    <i class="fas fa-file-alt text-3xl text-blue-500"></i>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600">Total Businesses</p>
                        <p class="text-2xl font-bold"><?php echo $totalBusinesses; ?></p>
                    </div>
                    <i class="fas fa-building text-3xl text-green-500"></i>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600">Active Violations</p>
                        <p class="text-2xl font-bold"><?php echo $totalActiveViolations; ?></p>
                    </div>
                    <i class="fas fa-exclamation-triangle text-3xl text-red-500"></i>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600">Average Compliance</p>
                        <p class="text-2xl font-bold"><?php echo $averageCompliance; ?>%</p>
                    </div>
                    <i class="fas fa-check-circle text-3xl text-purple-500"></i>
                </div>
            </div>
        </div>

        <!-- Charts -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-bold mb-4">Businesses by Type</h3>
                <canvas id="businessByTypeChart"></canvas>
            </div>
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-bold mb-4">Violations by Severity</h3>
                <canvas id="violationsBySeverityChart"></canvas>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <h3 class="text-lg font-bold mb-4">Recent Inspections</h3>
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Business</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Inspector</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Scheduled Date</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php 
                    $recent_inspections = $inspection->readRecent(5);
                    foreach ($recent_inspections as $recent): ?>
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-medium text-gray-900"><?php echo $recent['business_name']; ?></div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900"><?php echo $recent['inspector_name']; ?></div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900"><?php echo date('M j, Y', strtotime($recent['scheduled_date'])); ?></div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 py-1 text-xs font-medium rounded-full 
                                <?php echo $recent['status'] == 'completed' ? 'bg-green-100 text-green-800' : 
                                       ($recent['status'] == 'in_progress' ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800'); ?>">
                                <?php echo ucfirst($recent['status']); ?>
                            </span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Data from PHP
        const businessTypeData = <?php echo json_encode($businessCountByType); ?>;
        const violationSeverityData = <?php echo json_encode($violationStatsBySeverity); ?>;

        // Chart 1: Businesses by Type (Bar Chart)
        const businessCtx = document.getElementById('businessByTypeChart').getContext('2d');
        const businessLabels = businessTypeData.map(d => d.business_type);
        const businessCounts = businessTypeData.map(d => d.count);

        new Chart(businessCtx, {
            type: 'bar',
            data: {
                labels: businessLabels,
                datasets: [{
                    label: 'Number of Businesses',
                    data: businessCounts,
                    backgroundColor: 'rgba(54, 162, 235, 0.6)',
                    borderColor: 'rgba(54, 162, 235, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                }
            }
        });

        // Chart 2: Violations by Severity (Pie Chart)
        const violationCtx = document.getElementById('violationsBySeverityChart').getContext('2d');
        new Chart(violationCtx, {
            type: 'pie',
            data: {
                labels: Object.keys(violationSeverityData).map(s => s.charAt(0).toUpperCase() + s.slice(1)),
                datasets: [{
                    label: 'Violations',
                    data: Object.values(violationSeverityData),
                    backgroundColor: [
                        'rgba(75, 192, 192, 0.8)',  // low (green)
                        'rgba(255, 206, 86, 0.8)', // medium (yellow)
                        'rgba(255, 159, 64, 0.8)',  // high (orange)
                        'rgba(255, 99, 132, 0.8)'   // critical (red)
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
                    }
                }
            }
        });
    });
    </script>
</body>
</html>
