<?php
require_once '../utils/session_manager.php';
require_once '../config/database.php';
require_once '../models/User.php';
require_once '../models/Inspection.php';
require_once '../utils/access_control.php';

requirePermission('dashboard');

$database = new Database();
$db_core = $database->getConnection(Database::DB_CORE);
$db_scheduling = $database->getConnection(Database::DB_SCHEDULING);

$user = new User($db_core);
$user->id = $_SESSION['user_id'];
$user->readOne();

$inspection = new Inspection($db_scheduling);
$assigned_inspections = $inspection->readByInspector($_SESSION['user_id'])->fetchAll(PDO::FETCH_ASSOC);

$stats = [
    'total_assigned' => count($assigned_inspections),
    'completed' => count(array_filter($assigned_inspections, fn($i) => $i['status'] === 'completed')),
    'in_progress' => count(array_filter($assigned_inspections, fn($i) => $i['status'] === 'in_progress')),
    'scheduled' => count(array_filter($assigned_inspections, fn($i) => $i['status'] === 'scheduled')),
];

$upcoming_inspections = array_filter($assigned_inspections, fn($i) => $i['status'] === 'scheduled' && strtotime($i['scheduled_date']) >= time());
usort($upcoming_inspections, fn($a, $b) => strtotime($a['scheduled_date']) <=> strtotime($b['scheduled_date']));
$upcoming_inspections = array_slice($upcoming_inspections, 0, 5);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inspector Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="min-h-screen bg-gray-50">
    <?php include '../includes/navigation.php'; ?>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 sm:py-8 md:ml-64 md:pt-24">
        <div class="space-y-6">
            <div class="bg-gradient-to-r from-blue-600 to-purple-700 rounded-lg shadow-lg p-6 text-white">
                <h1 class="text-2xl font-bold">Welcome, Inspector <?php echo htmlspecialchars($user->name); ?>!</h1>
                <p class="text-blue-100">Here is your personal dashboard.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <a href="assigned_inspections.php" class="bg-white rounded-lg shadow p-6 block hover:shadow-lg transition-shadow">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-600">Total Assigned</p>
                            <p class="text-2xl font-bold"><?php echo $stats['total_assigned']; ?></p>
                        </div>
                        <i class="fas fa-tasks text-3xl text-blue-500"></i>
                    </div>
                </a>
                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-600">Completed</p>
                            <p class="text-2xl font-bold"><?php echo $stats['completed']; ?></p>
                        </div>
                        <i class="fas fa-check-circle text-3xl text-green-500"></i>
                    </div>
                </div>
                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-600">In Progress</p>
                            <p class="text-2xl font-bold"><?php echo $stats['in_progress']; ?></p>
                        </div>
                        <i class="fas fa-spinner text-3xl text-yellow-500"></i>
                    </div>
                </div>
                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm text-gray-600">Scheduled</p>
                            <p class="text-2xl font-bold"><?php echo $stats['scheduled']; ?></p>
                        </div>
                        <i class="fas fa-calendar-alt text-3xl text-purple-500"></i>
                    </div>
                </div>
            </div>

            <!-- Upcoming Inspections -->
            <div class="bg-white rounded-lg shadow">
                <div class="p-4 border-b">
                    <h3 class="text-lg font-medium">Your Upcoming Inspections</h3>
                </div>
                <div class="p-4">
                    <ul class="divide-y divide-gray-200">
                        <?php if (!empty($upcoming_inspections)): ?>
                            <?php foreach ($upcoming_inspections as $inspection): ?>
                            <li class="py-3 flex justify-between items-center">
                                <div>
                                    <p class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($inspection['business_name']); ?></p>
                                    <p class="text-sm text-gray-500"><?php echo htmlspecialchars($inspection['inspection_type']); ?></p>
                                </div>
                                <div class="text-right">
                                    <p class="text-sm text-gray-700"><?php echo date('M j, Y', strtotime($inspection['scheduled_date'])); ?></p>
                                    <a href="inspection_form.php?id=<?php echo $inspection['id']; ?>" class="text-xs text-blue-500 hover:underline">Start Inspection</a>
                                </div>
                            </li>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <li class="py-3 text-sm text-gray-500">You have no upcoming inspections.</li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</body>
</html>