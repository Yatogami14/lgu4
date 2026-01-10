<?php
// No session manager needed for a public page.
require_once 'config/database.php';
require_once 'models/Business.php';

$database = new Database();
$businessModel = new Business($database);

// Determine base path for assets
$base_path = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
if ($base_path === '/' || $base_path === '\\') $base_path = '';

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
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        html { scroll-behavior: smooth; }
        .fade-in-element {
            opacity: 0;
            transform: translateY(20px);
            transition: opacity 0.6s ease-out, transform 0.6s ease-out;
        }
        .fade-in-element.is-visible {
            opacity: 1;
            transform: translateY(0);
        }
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        .animate-on-load {
            opacity: 0;
            animation-fill-mode: forwards;
        }
        .animate-headline { animation: fadeInUp 0.8s ease-out 0.2s forwards; }
        .animate-subheadline { animation: fadeInUp 0.8s ease-out 0.5s forwards; }
        .animate-search { animation: fadeInUp 0.8s ease-out 0.8s forwards; }
        .text-gradient {
            background-clip: text;
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-image: linear-gradient(to right, #009688, #00796B);
        }
    </style>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'brand': {
                            '100': '#E0F2F1',
                            '200': '#B2DFDB',
                            '400': '#4DB6AC',
                            '500': '#009688',
                            '600': '#00897B',
                            '700': '#00796B',
                            '800': '#00695C',
                            '900': '#004D40',
                        },
                    },
                    fontFamily: {
                        sans: ['Poppins', 'sans-serif'],
                        serif: ['Georgia', 'serif']
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-gradient-to-br from-gray-50 to-brand-50 flex flex-col min-h-screen font-sans">
    <!-- Header -->
    <header class="bg-white/95 backdrop-blur-sm shadow-lg sticky top-0 z-50 border-b border-brand-100">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <a href="<?php echo $base_path; ?>/index.php" class="flex items-center space-x-3 group" title="Go to Homepage">
                    <img src="<?php echo $base_path; ?>/logo/logo.jpeg?v=4" alt="Logo" class="h-8 w-auto rounded-full shadow-sm group-hover:shadow-md transition-shadow">
                    <span class="text-xl font-bold text-gray-900 group-hover:text-brand-600 transition-colors">QC <span class="text-brand-600">Protektado</span></span>
                </a>
                <div class="flex items-center space-x-2">
                    <a href="<?php echo $base_path; ?>/index.php" class="inline-flex items-center text-sm font-semibold text-gray-600 hover:text-brand-600 px-4 py-2 rounded-full border border-gray-200 hover:border-brand-300 hover:bg-brand-50 transition-all duration-200">
                        <i class="fas fa-arrow-left mr-2"></i>Back to Home
                    </a>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="flex-grow w-full max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <!-- Hero Section -->
        <div class="text-center mb-16 fade-in-element">
            <div class="inline-flex items-center justify-center w-20 h-20 bg-gradient-to-r from-brand-500 to-brand-600 rounded-full mb-6 shadow-lg">
                <i class="fas fa-search text-2xl text-white"></i>
            </div>
            <h1 class="text-5xl font-extrabold text-gray-900 animate-on-load animate-headline mb-4">
                Business <span class="text-gradient">Compliance Search</span>
            </h1>
            <p class="mt-4 max-w-2xl mx-auto text-xl text-gray-600 animate-on-load animate-subheadline leading-relaxed">
                Discover local businesses and their commitment to safety standards. Search by name, address, or business type to view compliance scores.
            </p>
        </div>

        <!-- Search Form -->
        <div class="bg-white rounded-2xl shadow-xl p-8 mb-12 animate-on-load animate-search border border-gray-100">
            <form method="GET" action="public_compliance_search.php">
                <div class="flex flex-col md:flex-row gap-4">
                    <div class="relative flex-grow">
                        <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search businesses by name, address, or type..." class="w-full pl-12 pr-4 py-4 text-lg border-2 border-gray-200 rounded-xl focus:ring-4 focus:ring-brand-100 focus:border-brand-400 transition-all duration-200 shadow-sm">
                        <i class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 text-xl"></i>
                    </div>
                    <button type="submit" class="bg-gradient-to-r from-brand-500 to-brand-600 text-white px-8 py-4 rounded-xl font-bold hover:from-brand-600 hover:to-brand-700 transition-all duration-200 shadow-lg hover:shadow-xl transform hover:-translate-y-0.5">
                        <i class="fas fa-search mr-2"></i>Search
                    </button>
                </div>
            </form>
        </div>

        <!-- Results -->
        <?php if (!empty($businesses)): ?>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8 mb-12">
            <?php foreach ($businesses as $business): ?>
            <div class="bg-white rounded-2xl shadow-lg hover:shadow-2xl transition-all duration-300 hover:-translate-y-2 border border-gray-100 overflow-hidden fade-in-element">
                <div class="p-6">
                    <div class="flex items-start justify-between mb-4">
                        <div class="flex-1">
                            <h3 class="text-xl font-bold text-gray-900 mb-2 line-clamp-2"><?php echo htmlspecialchars($business['name']); ?></h3>
                            <p class="text-sm text-gray-600 mb-2"><i class="fas fa-building mr-2 text-brand-500"></i><?php echo htmlspecialchars($business['business_type']); ?></p>
                            <p class="text-sm text-gray-500 line-clamp-2"><i class="fas fa-map-marker-alt mr-2 text-gray-400"></i><?php echo htmlspecialchars($business['address']); ?></p>
                        </div>
                        <div class="ml-4">
                            <div class="text-center">
                                <div class="inline-flex items-center justify-center w-16 h-16 rounded-full <?php echo getComplianceBgColor($business['compliance_score']); ?> shadow-md">
                                    <span class="text-lg font-bold <?php echo getComplianceColor($business['compliance_score']); ?>">
                                        <?php echo ($business['compliance_score'] !== null) ? $business['compliance_score'] : 'N/A'; ?>
                                    </span>
                                </div>
                                <p class="text-xs text-gray-500 mt-1 font-medium">
                                    <?php if ($business['compliance_score'] !== null): ?>
                                        Compliance
                                    <?php else: ?>
                                        Not Rated
                                    <?php endif; ?>
                                </p>
                            </div>
                        </div>
                    </div>
                    <a href="public_business_profile.php?id=<?php echo $business['id']; ?>" class="inline-flex items-center justify-center w-full bg-gradient-to-r from-brand-500 to-brand-600 text-white px-6 py-3 rounded-xl font-semibold hover:from-brand-600 hover:to-brand-700 transition-all duration-200 shadow-md hover:shadow-lg transform hover:scale-105">
                        <i class="fas fa-eye mr-2"></i>View Details
                    </a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <!-- No Results -->
        <div class="bg-white rounded-2xl shadow-xl p-16 text-center border border-gray-100 fade-in-element">
            <div class="inline-flex items-center justify-center w-24 h-24 bg-gray-100 rounded-full mb-6">
                <i class="fas fa-search text-4xl text-gray-400"></i>
            </div>
            <h3 class="text-2xl font-bold text-gray-900 mb-4">No Businesses Found</h3>
            <p class="text-lg text-gray-600 mb-6">
                <?php if (!empty($search)): ?>
                    We couldn't find any businesses matching "<?php echo htmlspecialchars($search); ?>".
                <?php else: ?>
                    Start your search to discover businesses and their compliance status.
                <?php endif; ?>
            </p>
            <?php if (!empty($search)): ?>
            <a href="public_compliance_search.php" class="inline-flex items-center bg-brand-500 text-white px-6 py-3 rounded-xl font-semibold hover:bg-brand-600 transition-colors">
                <i class="fas fa-times mr-2"></i>Clear Search
            </a>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
        <div class="bg-white rounded-2xl shadow-lg p-6 border border-gray-100 fade-in-element">
            <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
                <div class="text-sm text-gray-600">
                    <span class="font-medium"><?php echo min($offset + 1, $total_records); ?>-<?php echo min($offset + $records_per_page, $total_records); ?></span> of <span class="font-medium"><?php echo $total_records; ?></span> businesses
                </div>
                <nav class="flex items-center space-x-1" aria-label="Pagination">
                    <!-- Previous Button -->
                    <a href="?page=<?php echo max(1, $page - 1); ?>&search=<?php echo urlencode($search); ?>" class="inline-flex items-center px-3 py-2 rounded-lg border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 hover:text-gray-700 transition-colors <?php echo ($page <= 1) ? 'opacity-50 cursor-not-allowed pointer-events-none' : ''; ?>">
                        <i class="fas fa-chevron-left mr-1"></i>Previous
                    </a>

                    <div class="flex items-center space-x-1">
                        <?php
                        $start_page = max(1, $page - 2);
                        $end_page = min($total_pages, $page + 2);

                        if ($start_page > 1): ?>
                            <a href="?page=1&search=<?php echo urlencode($search); ?>" class="inline-flex items-center px-3 py-2 rounded-lg border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 hover:text-gray-700 transition-colors">1</a>
                            <?php if ($start_page > 2): ?>
                                <span class="inline-flex items-center px-2 py-2 text-gray-400">...</span>
                            <?php endif; ?>
                        <?php endif; ?>

                        <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
                            <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>" class="inline-flex items-center px-3 py-2 rounded-lg border text-sm font-medium transition-colors <?php echo ($i == $page) ? 'z-10 bg-brand-50 border-brand-500 text-brand-600 shadow-sm' : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50 hover:text-gray-700'; ?>">
                                <?php echo $i; ?>
                            </a>
                        <?php endfor; ?>

                        <?php if ($end_page < $total_pages): ?>
                            <?php if ($end_page < $total_pages - 1): ?>
                                <span class="inline-flex items-center px-2 py-2 text-gray-400">...</span>
                            <?php endif; ?>
                            <a href="?page=<?php echo $total_pages; ?>&search=<?php echo urlencode($search); ?>" class="inline-flex items-center px-3 py-2 rounded-lg border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 hover:text-gray-700 transition-colors"><?php echo $total_pages; ?></a>
                        <?php endif; ?>
                    </div>

                    <!-- Next Button -->
                    <a href="?page=<?php echo min($total_pages, $page + 1); ?>&search=<?php echo urlencode($search); ?>" class="inline-flex items-center px-3 py-2 rounded-lg border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 hover:text-gray-700 transition-colors <?php echo ($page >= $total_pages) ? 'opacity-50 cursor-not-allowed pointer-events-none' : ''; ?>">
                        Next<i class="fas fa-chevron-right ml-1"></i>
                    </a>
                </nav>
            </div>
        </div>
        <?php endif; ?>
    </main>

    <!-- Footer -->
    <footer class="bg-gradient-to-r from-gray-800 to-gray-900 text-white mt-16">
        <div class="max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
            <div class="text-center">
                <p class="text-sm text-gray-300">&copy; <?php echo date('Y'); ?> HSI-QC Protektado. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Intersection Observer for fade-in animations
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('is-visible');
                        observer.unobserve(entry.target);
                    }
                });
            }, { threshold: 0.1 });

            const elementsToAnimate = document.querySelectorAll('.fade-in-element');
            elementsToAnimate.forEach(element => {
                observer.observe(element);
            });
        });
    </script>
</body>
</html>
