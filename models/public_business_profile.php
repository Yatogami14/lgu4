<?php
// No session manager needed for a public page.
require_once 'config/database.php';
require_once 'models/Business.php';
require_once 'models/Inspection.php';
require_once 'models/Violation.php';

$database = new Database();
$businessModel = new Business($database);
$inspectionModel = new Inspection($database);
$violationModel = new Violation($database);

// Determine base path for assets
$base_path = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
if ($base_path === '/' || $base_path === '\\') $base_path = '';

// Get Business ID from URL
$business_id = isset($_GET['id']) && is_numeric($_GET['id']) ? (int)$_GET['id'] : 0;

$business = null;
$inspections = [];
$violations = [];
$error_message = '';

if ($business_id > 0) {
    $businessModel->id = $business_id;
    $business = $businessModel->readOne();

    // Ensure business exists and is publicly visible
    if (!$business || $business['status'] !== 'active') {
        $business = null; // Invalidate business data
        $error_message = "The business profile you are looking for could not be found or is not currently active.";
        http_response_code(404); // Set HTTP status to 404 Not Found
    } else {
        // Fetch related data
        $inspections = $inspectionModel->readByBusinessIds([$business_id]);
        $violations = $violationModel->readAll([$business_id]);
    }
} else {
    $error_message = "No business specified.";
}

// Helper functions for styling
function getComplianceColor($score) {
    if ($score === null) return 'text-gray-600';
    if ($score >= 80) return 'text-green-600';
    if ($score >= 50) return 'text-yellow-600';
    return 'text-red-600';
}

function getComplianceBgColor($score) {
    if ($score === null) return 'bg-gray-100';
    if ($score >= 80) return 'bg-green-100';
    if ($score >= 50) return 'bg-yellow-100';
    return 'bg-red-100';
}

function getStatusColor($status) {
    switch ($status) {
        case 'completed': return 'bg-green-100 text-green-800';
        case 'scheduled': return 'bg-blue-100 text-blue-800';
        case 'in_progress': return 'bg-yellow-100 text-yellow-800';
        case 'cancelled': return 'bg-red-100 text-red-800';
        default: return 'bg-gray-100 text-gray-800';
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $business ? htmlspecialchars($business['name']) : 'Business Profile'; ?> - QC Protektado</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-50 flex flex-col min-h-screen">
    <!-- Header -->
    <header class="bg-white shadow-sm sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <a href="<?php echo $base_path; ?>/index.html" class="flex items-center space-x-3" title="Go to Homepage">
                    <img src="<?php echo $base_path; ?>/logo/logo.jpeg?v=4" alt="Logo" class="h-8 w-auto">
                    <span class="text-xl font-bold text-gray-900">QC Protektado</span>
                </a>
                <div class="flex items-center space-x-2">
                    <a href="<?php echo $base_path; ?>/public_compliance_search.php" class="text-sm font-medium text-gray-600 hover:text-yellow-600 px-4 py-2 rounded-md">
                        <i class="fas fa-search mr-2"></i>Back to Search
                    </a>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="flex-grow w-full max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <?php if ($business): ?>
            <!-- Business Header -->
            <div class="bg-white rounded-lg shadow p-6 mb-8">
                <div class="flex flex-col md:flex-row md:items-center md:justify-between">
                    <div>
                        <h1 class="text-3xl font-extrabold text-gray-900"><?php echo htmlspecialchars($business['name']); ?></h1>
                        <p class="mt-1 text-md text-gray-500"><i class="fas fa-map-marker-alt mr-2"></i><?php echo htmlspecialchars($business['address']); ?></p>
                        <p class="mt-1 text-md text-gray-500"><i class="fas fa-briefcase mr-2"></i><?php echo htmlspecialchars($business['business_type']); ?></p>
                    </div>
                    <div class="mt-4 md:mt-0 text-center">
                        <p class="text-sm text-gray-500 uppercase font-bold">Compliance Score</p>
                        <div class="text-5xl font-bold <?php echo getComplianceColor($business['compliance_score']); ?>">
                            <?php echo ($business['compliance_score'] !== null) ? $business['compliance_score'] . '%' : 'N/A'; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Inspection History -->
            <div class="mb-8">
                <h2 class="text-2xl font-bold text-gray-800 mb-4">Inspection History</h2>
                <div class="bg-white rounded-lg shadow overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Inspection Type</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Inspector</th>
                                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Score</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php if (empty($inspections)): ?>
                                    <tr><td colspan="5" class="text-center py-6 text-gray-500">No inspection history found.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($inspections as $inspection): ?>
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo date('F j, Y', strtotime($inspection['scheduled_date'])); ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($inspection['inspection_type']); ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($inspection['inspector_name']); ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-center">
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo getStatusColor($inspection['status']); ?>">
                                                <?php echo ucfirst(str_replace('_', ' ', $inspection['status'])); ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-bold <?php echo getComplianceColor($inspection['compliance_score']); ?>">
                                            <?php echo ($inspection['compliance_score'] !== null) ? $inspection['compliance_score'] . '%' : 'N/A'; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        <?php else: ?>
            <!-- Error Message -->
            <div class="text-center py-20">
                <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100 mb-4">
                    <i class="fas fa-exclamation-triangle text-red-600 text-xl"></i>
                </div>
                <h2 class="text-2xl font-bold text-gray-800">Profile Not Found</h2>
                <p class="mt-2 text-gray-600"><?php echo htmlspecialchars($error_message); ?></p>
                <a href="public_compliance_search.php" class="mt-6 inline-block bg-yellow-400 text-gray-900 px-6 py-3 rounded-lg font-semibold hover:bg-yellow-500 transition-colors">
                    Return to Search
                </a>
            </div>
        <?php endif; ?>
    </main>

    <!-- Footer -->
    <footer class="bg-gray-800 text-white mt-auto">
        <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8 text-center">
            <p class="text-sm">&copy; <?php echo date('Y'); ?> HSI-QC Protektado. All rights reserved.</p>
        </div>
    </footer>
</body>
</html>