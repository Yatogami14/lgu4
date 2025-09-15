<?php
require_once '../utils/session_manager.php';
require_once '../config/database.php';
require_once '../models/Inspection.php';
require_once '../models/User.php';
require_once '../utils/access_control.php';

requirePermission('assigned_inspections');

$database = new Database();
$db_core = $database->getConnection(Database::DB_CORE);
$db_scheduling = $database->getConnection(Database::DB_SCHEDULING);

$inspection = new Inspection($db_scheduling);
$user = new User($db_core);
$user->id = $_SESSION['user_id'];
$user->readOne();

// Inspectors see only their own assigned inspections
$inspections = $inspection->readByInspector($_SESSION['user_id']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Assigned Inspections - LGU Health & Safety</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-50 min-h-screen">
    <?php include '../includes/navigation.php'; ?>
    
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 md:ml-64 md:pt-24">
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-gray-900">My Inspection Assignments</h1>
            <p class="text-gray-600">Manage your scheduled, in-progress, and completed inspections.</p>
        </div>

        <?php if ($inspections->rowCount() > 0): ?>
        <div class="bg-white shadow overflow-hidden sm:rounded-lg">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Business</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Inspection Type</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Scheduled Date</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php while ($row = $inspections->fetch(PDO::FETCH_ASSOC)): ?>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900"><?php echo $row['business_name']; ?></div>
                                <div class="text-sm text-gray-500"><?php echo $row['business_address']; ?></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900"><?php echo $row['inspection_type']; ?></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900"><?php echo date('M j, Y', strtotime($row['scheduled_date'])); ?></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 py-1 text-xs font-medium rounded-full 
                                    <?php 
                                    switch($row['status']) {
                                        case 'scheduled': echo 'bg-blue-100 text-blue-800'; break;
                                        case 'in_progress': echo 'bg-yellow-100 text-yellow-800'; break;
                                        case 'completed': echo 'bg-green-100 text-green-800'; break;
                                        case 'cancelled': echo 'bg-red-100 text-red-800'; break;
                                        default: echo 'bg-gray-100 text-gray-800';
                                    }
                                    ?>">
                                    <?php echo ucfirst(str_replace('_', ' ', $row['status'])); ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <?php if ($row['status'] === 'scheduled' || $row['status'] === 'in_progress'): ?>
                                    <a href="inspection_form.php?id=<?php echo $row['id']; ?>" class="inline-flex items-center px-3 py-1.5 border border-transparent text-xs font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                        <i class="fas fa-play-circle -ml-0.5 mr-2 h-4 w-4"></i>
                                        <?php echo $row['status'] === 'scheduled' ? 'Start Inspection' : 'Continue'; ?>
                                    </a>
                                <?php elseif ($row['status'] === 'completed'): ?>
                                    <a href="inspection_view.php?id=<?php echo $row['id']; ?>" class="inline-flex items-center px-3 py-1.5 border border-gray-300 text-xs font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                        <i class="fas fa-file-alt -ml-0.5 mr-2 h-4 w-4"></i>
                                        View Report
                                    </a>
                                <?php else: ?>
                                    <a href="inspection_view.php?id=<?php echo $row['id']; ?>" class="text-gray-500 hover:text-gray-700">
                                        <i class="fas fa-eye mr-1"></i> View Details
                                    </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php else: ?>
        <div class="bg-white shadow sm:rounded-lg p-6 text-center">
            <i class="fas fa-info-circle text-4xl text-gray-400 mb-4"></i>
            <h3 class="text-lg font-medium text-gray-900">No Inspections Assigned</h3>
            <p class="mt-1 text-sm text-gray-500">You do not have any inspections assigned to you at the moment.</p>
        </div>
        <?php endif; ?>
    </main>
</body>
</html>