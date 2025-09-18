<?php
// No session manager needed for a public page.
require_once 'config/database.php';
require_once 'models/Business.php';

$database = new Database();
$businessModel = new Business($database);

// Handle search
$search = $_GET['search'] ?? '';

// --- Pagination Logic ---
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$records_per_page = 10;
$offset = ($page - 1) * $records_per_page;

// Get total number of records for pagination
$total_records = $businessModel->countAllWithCompliance($search);
$total_pages = ceil($total_records / $records_per_page);

$businesses = $businessModel->readAllWithCompliance($search, $records_per_page, $offset);

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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Public Compliance Search - QC Protektado</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-50">
    <!-- Header -->
    <header class="bg-white shadow-sm sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <a href="index.php" class="flex items-center space-x-3">
                    <i class="fas fa-shield-alt text-blue-600 text-2xl"></i>
                    <span class="text-xl font-bold text-gray-900">QC Protektado</span>
                </a>
                <div class="flex items-center space-x-2">
                    <a href="index.php" class="text-sm font-medium text-gray-600 hover:text-blue-600 px-4 py-2 rounded-md">
                        <i class="fas fa-arrow-left mr-2"></i>Back to Home
                    </a>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="text-center mb-12">
            <h1 class="text-4xl font-extrabold text-gray-900">Business Compliance Search</h1>
            <p class="mt-2 text-lg text-gray-600">Search for local businesses to view their latest compliance status.</p>
        </div>

        <!-- Search Form -->
        <div class="bg-white rounded-lg shadow p-6 mb-8">
            <form method="GET" action="public_compliance_search.php">
                <div class="flex flex-col md:flex-row gap-4">
                    <div class="relative flex-grow">
                        <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search by name, address, or business type..." class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition">
                        <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                    </div>
                    <button type="submit" class="bg-blue-600 text-white px-6 py-3 rounded-lg font-semibold hover:bg-blue-700 transition-colors">
                        Search
                    </button>
                </div>
            </form>
        </div>

        <!-- Results Table -->
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Business Name</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Business Type</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Address</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Compliance Score</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php if (empty($businesses)): ?>
                            <tr>
                                <td colspan="4" class="text-center py-10 text-gray-500">
                                    <?php if (!empty($search)): ?>
                                        No businesses found for "<?php echo htmlspecialchars($search); ?>".
                                    <?php else: ?>
                                        Please enter a search term to find businesses.
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($businesses as $business): ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap"><div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($business['name']); ?></div></td>
                                <td class="px-6 py-4 whitespace-nowrap"><div class="text-sm text-gray-500"><?php echo htmlspecialchars($business['business_type']); ?></div></td>
                                <td class="px-6 py-4"><div class="text-sm text-gray-500"><?php echo htmlspecialchars($business['address']); ?></div></td>
                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    <span class="px-3 py-1 text-sm font-bold rounded-full <?php echo getComplianceBgColor($business['compliance_score']); ?> <?php echo getComplianceColor($business['compliance_score']); ?>">
                                        <?php echo ($business['compliance_score'] !== null) ? ($business['compliance_score'] . '%') : 'N/A'; ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <!-- Pagination Controls -->
            <?php if ($total_pages > 1): ?>
            <div class="px-6 py-4 flex items-center justify-between border-t border-gray-200">
                <div class="text-sm text-gray-600">
                    Showing <span class="font-medium"><?php echo min($offset + 1, $total_records); ?></span> to <span class="font-medium"><?php echo min($offset + $records_per_page, $total_records); ?></span> of <span class="font-medium"><?php echo $total_records; ?></span> results
                </div>
                <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                    <!-- Previous Button -->
                    <a href="?page=<?php echo max(1, $page - 1); ?>&search=<?php echo urlencode($search); ?>" class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 <?php echo ($page <= 1) ? 'opacity-50 cursor-not-allowed' : ''; ?>">
                        <span class="sr-only">Previous</span>
                        <i class="fas fa-chevron-left h-5 w-5"></i>
                    </a>
                    
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>" class="relative inline-flex items-center px-4 py-2 border text-sm font-medium <?php echo ($i == $page) ? 'z-10 bg-blue-50 border-blue-500 text-blue-600' : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50'; ?>">
                        <?php echo $i; ?>
                    </a>
                    <?php endfor; ?>

                    <!-- Next Button -->
                    <a href="?page=<?php echo min($total_pages, $page + 1); ?>&search=<?php echo urlencode($search); ?>" class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 <?php echo ($page >= $total_pages) ? 'opacity-50 cursor-not-allowed' : ''; ?>">
                        <span class="sr-only">Next</span>
                        <i class="fas fa-chevron-right h-5 w-5"></i>
                    </a>
                </nav>
            </div>
            <?php endif; ?>
        </div>
    </main>

    <!-- Footer -->
    <footer class="bg-gray-800 text-white mt-12">
        <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8 text-center">
            <p class="text-sm">&copy; <?php echo date('Y'); ?> HSI-QC Protektado. All rights reserved.</p>
        </div>
    </footer>
</body>
</html>