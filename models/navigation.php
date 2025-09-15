<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// This file is included from subdirectories, so the path to access_control is relative
require_once __DIR__ . '/../utils/access_control.php';

$current_user_role = $_SESSION['user_role'] ?? 'guest';
$user_name = $_SESSION['user_name'] ?? 'Guest';

// Define base paths
$admin_base = '/lgu4/admin/';
$business_base = '/lgu4/business/';
$community_base = '/lgu4/community/';

// Navigation items: [label, href, permission, icon]
$nav_items = [
    // Admin & Super Admin & Inspector
    ['Dashboard', $admin_base . 'index.php', 'dashboard', 'fa-tachometer-alt'],
    ['Analytics', $admin_base . 'analytics.php', 'analytics', 'fa-chart-line'],
    ['Inspections', $admin_base . 'inspections.php', 'inspections', 'fa-file-alt'],
    ['My Assignments', $admin_base . 'assigned_inspections.php', 'assigned_inspections', 'fa-tasks'],
    ['Schedule', $admin_base . 'schedule.php', 'schedule', 'fa-calendar-alt'],
    ['Businesses', $admin_base . 'businesses.php', 'businesses', 'fa-building'],
    ['Violations', $admin_base . 'violations.php', 'violations', 'fa-exclamation-triangle'],
    ['Inspectors', $admin_base . 'inspectors.php', 'inspectors', 'fa-users-cog'],
    ['User Management', $admin_base . 'user_management.php', 'user_management', 'fa-users'],
    ['Active Sessions', $admin_base . 'active_sessions.php', 'active_sessions', 'fa-plug'],
    
    // Business Owner
    ['My Business', $business_base . 'index.php', 'dashboard', 'fa-tachometer-alt'],
    ['My Violations', $business_base . 'violations.php', 'violations', 'fa-exclamation-triangle'],

    // Community User
    ['Community View', $community_base . 'index.php', 'dashboard', 'fa-tachometer-alt'],
    ['Report Violation', $community_base . 'violations.php', 'violations', 'fa-bullhorn'],
];

$current_page = basename($_SERVER['PHP_SELF']);
?>

<!-- Sidebar Navigation -->
<div class="hidden md:flex md:w-64 md:flex-col md:fixed md:inset-y-0">
    <div class="flex flex-col flex-grow bg-gray-800 pt-5 overflow-y-auto">
        <div class="flex items-center flex-shrink-0 px-4">
            <i class="fas fa-shield-alt text-2xl text-blue-400"></i>
            <span class="ml-3 text-white text-xl font-semibold">LGU Platform</span>
        </div>
        <div class="mt-5 flex-1 flex flex-col">
            <nav class="flex-1 px-2 pb-4 space-y-1">
                <?php foreach ($nav_items as $item): ?>
                    <?php if (currentUserHasPermission($item[2])): ?>
                        <?php
                            $is_active = ($current_page == basename($item[1]));
                            $active_class = $is_active ? 'bg-gray-900 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white';
                        ?>
                        <a href="<?php echo $item[1]; ?>" class="<?php echo $active_class; ?> group flex items-center px-2 py-2 text-sm font-medium rounded-md">
                            <i class="fas <?php echo $item[3]; ?> mr-3 flex-shrink-0 h-6 w-6 text-gray-400 group-hover:text-gray-300"></i>
                            <?php echo $item[0]; ?>
                        </a>
                    <?php endif; ?>
                <?php endforeach; ?>
            </nav>
        </div>
        <div class="flex-shrink-0 flex bg-gray-700 p-4 border-t border-gray-600">
            <div class="flex-shrink-0 w-full group block">
                <div class="flex items-center">
                    <div>
                        <img class="inline-block h-9 w-9 rounded-full" src="/lgu4/assets/images/default_avatar.png" alt="User Avatar" onerror="this.onerror=null;this.src='https://via.placeholder.com/100';">
                    </div>
                    <div class="ml-3 flex-1">
                        <p class="text-sm font-medium text-white"><?php echo htmlspecialchars($user_name); ?></p>
                        <a href="<?php echo $admin_base . 'profile.php'; ?>" class="text-xs font-medium text-gray-300 group-hover:text-gray-200">View profile</a>
                    </div>
                    <div class="ml-auto">
                         <a href="<?php echo $admin_base . 'logout.php'; ?>" title="Logout" class="text-gray-400 hover:text-white">
                            <i class="fas fa-sign-out-alt"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Top Bar for Mobile -->
<header class="md:hidden bg-gray-800 text-white p-4 flex justify-between items-center sticky top-0 z-20">
    <div class="flex items-center">
        <i class="fas fa-shield-alt text-2xl text-blue-400"></i>
        <span class="ml-3 text-white text-xl font-semibold">LGU Platform</span>
    </div>
    <button id="mobile-menu-button" class="text-white focus:outline-none">
        <i class="fas fa-bars text-xl"></i>
    </button>
</header>

<!-- Mobile Menu -->
<div id="mobile-menu" class="hidden md:hidden bg-gray-800 text-white">
    <nav class="px-2 pt-2 pb-3 space-y-1 sm:px-3">
        <?php foreach ($nav_items as $item): ?>
            <?php if (currentUserHasPermission($item[2])): ?>
                <a href="<?php echo $item[1]; ?>" class="block px-3 py-2 rounded-md text-base font-medium text-gray-300 hover:text-white hover:bg-gray-700"><i class="fas <?php echo $item[3]; ?> mr-3 w-6"></i><?php echo $item[0]; ?></a>
            <?php endif; ?>
        <?php endforeach; ?>
    </nav>
</div>

<script>
    document.getElementById('mobile-menu-button')?.addEventListener('click', function() {
        document.getElementById('mobile-menu').classList.toggle('hidden');
    });
</script>