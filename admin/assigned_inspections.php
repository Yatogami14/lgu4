<?php
require_once '../utils/session_manager.php';
require_once '../config/database.php';
require_once '../models/Inspection.php';
require_once '../models/User.php';
require_once '../utils/access_control.php';

requirePermission('assigned_inspections');

$database = new Database();

$inspection = new Inspection($database);

// Admins see all assigned inspections
$inspections = $inspection->readAllAssigned();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Assigned Inspections - LGU Health & Safety</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
</head>
<body class="min-h-screen bg-gray-50">
    <?php include '../includes/navigation.php'; ?>
    
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4 sm:py-8 md:ml-64 md:pt-24">
        <div class="flex flex-col sm:flex-row justify-between items-center mb-6 gap-4">
            <div>
                <h2 class="text-2xl font-bold text-gray-900">All Assigned Inspections</h2>
                <p class="text-sm text-gray-600 mt-1">Overview of all inspections currently assigned to inspectors</p>
            </div>
            <div class="w-full sm:w-auto">
                <div class="relative">
                    <input type="text" id="searchInput" onkeyup="filterInspections()" placeholder="Search inspections..." class="pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-brand-500 focus:border-brand-500 w-full sm:w-64 text-sm">
                    <i class="fas fa-search absolute left-3 top-2.5 text-gray-400"></i>
                </div>
            </div>
        </div>

        <!-- Inspections Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6" id="inspectionsGrid">
            <?php foreach ($inspections as $row): ?>
            <div class="inspection-card bg-white rounded-xl shadow-sm border border-gray-200 hover:shadow-md transition-all duration-300" 
                 data-search-content="<?php echo htmlspecialchars(strtolower($row['business_name'] . ' ' . ($row['inspector_name'] ?? '') . ' ' . $row['inspection_type'])); ?>">
                <div class="p-6 border-b border-gray-100">
                    <div class="flex items-start justify-between mb-3">
                        <div class="flex-1">
                            <h3 class="text-lg font-bold text-gray-900 mb-1"><?php echo htmlspecialchars($row['business_name']); ?></h3>
                            <p class="text-sm text-gray-500 flex items-center">
                                <i class="fas fa-map-marker-alt mr-1.5 text-gray-400"></i>
                                <?php echo htmlspecialchars($row['business_address']); ?>
                            </p>
                        </div>
                        <div class="flex flex-col items-end space-y-2">
                            <span class="px-2.5 py-0.5 text-xs font-medium rounded-full 
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
                        </div>
                    </div>
                    
                    <div class="flex items-center text-sm text-gray-600 mb-2">
                        <i class="fas fa-user-shield mr-2 text-brand-500"></i>
                        <span class="font-medium"><?php echo htmlspecialchars($row['inspector_name'] ?? 'Unassigned'); ?></span>
                    </div>

                    <div class="flex items-center text-sm text-gray-600 mb-2">
                        <i class="fas fa-clipboard-check mr-2 text-brand-500"></i>
                        <span><?php echo htmlspecialchars($row['inspection_type']); ?></span>
                    </div>

                    <div class="flex items-center text-sm text-gray-600">
                        <i class="fas fa-calendar-alt mr-2 text-brand-500"></i>
                        <span><?php echo date('M j, Y \a\t g:i A', strtotime($row['scheduled_date'])); ?></span>
                    </div>
                </div>
                
                <div class="p-4 bg-gray-50 rounded-b-xl border-t border-gray-100">
                    <div class="flex justify-end space-x-2">
                        <a href="inspection_form.php?id=<?php echo $row['id']; ?>" class="inline-flex items-center px-3 py-1.5 text-sm font-medium text-brand-700 bg-brand-50 rounded-lg hover:bg-brand-100 transition-colors">
                            <i class="fas fa-edit mr-1.5"></i> Edit
                        </a>
                        <a href="inspection_view.php?id=<?php echo $row['id']; ?>" class="inline-flex items-center px-3 py-1.5 text-sm font-medium text-gray-700 bg-white border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors">
                            <i class="fas fa-eye mr-1.5"></i> View
                        </a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <?php if (empty($inspections)): ?>
            <div class="p-12 text-center text-gray-500">
                <div class="mx-auto w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mb-4">
                    <i class="fas fa-clipboard-list text-2xl text-gray-400"></i>
                </div>
                <h3 class="text-lg font-medium text-gray-900">No Assigned Inspections</h3>
                <p class="mt-1 text-sm">There are currently no inspections assigned to any inspector.</p>
            </div>
        <?php endif; ?>
    </div>

    <script>
        function filterInspections() {
            const search = document.getElementById('searchInput').value.toLowerCase();
            const cards = document.querySelectorAll('.inspection-card');

            cards.forEach(card => {
                const content = card.getAttribute('data-search-content');
                
                if (content.includes(search)) {
                    card.style.display = '';
                } else {
                    card.style.display = 'none';
                }
            });
        }
    </script>
</body>
</html>