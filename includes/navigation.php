<?php
// Navigation component that can be included in any page
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Use absolute paths to avoid issues when included from different directories
$rootPath = dirname(__DIR__);
require_once $rootPath . '/config/database.php';
require_once $rootPath . '/models/User.php';
require_once $rootPath . '/utils/access_control.php';

// Get current user info for navigation
if (isset($_SESSION['user_id'])) {
    $database = new Database();
    $user = new User($database);
    $user->id = $_SESSION['user_id'];
    $user->readOne();

    // Define base paths for different user portals to ensure correct linking
    $base_path = rtrim(dirname(dirname($_SERVER['SCRIPT_NAME'])), '/\\');
    $portal_prefix = $base_path;

    switch ($_SESSION['user_role']) {
        case 'super_admin':
        case 'admin':
            $portal_prefix .= '/admin/';
            break;
        case 'inspector':
            $portal_prefix .= '/inspector/';
            break;
        case 'business_owner':
            $portal_prefix .= '/business/';
            break;
        case 'community_user':
            $portal_prefix .= '/community/';
            break;
    }

    $current_user_role = $_SESSION['user_role'];
    $current_page = basename($_SERVER['PHP_SELF']);

    // Centralized navigation items array
    // Format: 'permission_key' => [options]
    $nav_items = [
        'dashboard' => ['label' => 'Dashboard', 'icon' => 'fa-chart-bar', 'href' => 'index.php'],
        'analytics' => ['label' => 'Analytics', 'icon' => 'fa-chart-line'],
        'inspections' => [
            'label' => 'Inspections', 
            'icon' => 'fa-file-alt', 
            'exclude_roles' => ['inspector', 'business_owner', 'community_user'],
            'label_overrides' => [
                'business_owner' => 'My Inspections'
            ]
        ],
        'assigned_inspections' => [
            'label' => 'My Assignments', 
            'icon' => 'fa-clipboard-list', 
            'roles' => ['inspector']
        ],
        'schedule' => ['label' => 'Schedule', 'icon' => 'fa-calendar-alt'],
        'businesses' => [
            'label' => 'Businesses', 
            'icon' => 'fa-building',
            'exclude_roles' => ['community_user'],
            'label_overrides' => [
                'business_owner' => 'My Businesses'
            ]
        ],
        'violations' => [
            'label' => 'Violations', 
            'icon' => 'fa-exclamation-triangle',
            'label_overrides' => [
                'community_user' => 'Report a Concern',
                'business_owner' => 'My Violations'
            ],
            'icon_overrides' => [
                'community_user' => 'fa-bullhorn'
            ]
        ],
        'checklist_management' => [
            'label' => 'Checklists', 
            'icon' => 'fa-tasks',
            'roles' => ['admin', 'super_admin']
        ],
        'inspection_types_management' => [
            'label' => 'Inspection Types',
            'icon' => 'fa-tags',
            'roles' => ['admin', 'super_admin']
        ],
        'user_management' => [
            'label' => 'User Management', 
            'icon' => 'fa-users-cog'
        ],
        'active_sessions' => [
            'label' => 'Active Sessions', 
            'icon' => 'fa-users-slash'
        ],
        'profile' => ['label' => 'Profile', 'icon' => 'fa-user-cog'],
    ];
}
?>
<!-- Navigation Header -->
<header class="bg-white shadow-sm border-b fixed top-0 left-0 right-0 z-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center h-16">
            <!-- Mobile menu button -->
            <button id="sidebar-toggle" class="md:hidden p-2 rounded-md text-gray-600 hover:text-gray-900 hover:bg-gray-100">
                <i class="fas fa-bars text-xl"></i>
            </button>

            <div class="flex items-center space-x-2 sm:space-x-4 min-w-0 flex-1 md:flex-none">
                <i class="fas fa-shield-alt text-blue-600 text-xl sm:text-2xl"></i>
                <div class="min-w-0 flex-1 md:flex-none">
                    <h1 class="text-sm sm:text-xl font-bold text-gray-900 truncate">LGU Health & Safety</h1>
                    <p class="text-xs sm:text-sm text-gray-600 hidden sm:block">Digital Inspection Platform</p>
                </div>
            </div>

            <div class="flex items-center space-x-1 sm:space-x-4 flex-shrink-0">
                <?php if (isset($_SESSION['user_id'])): ?>
                <div class="flex items-center space-x-2">
                    <div class="w-8 h-8 bg-blue-500 rounded-full flex items-center justify-center text-white text-xs font-bold">
                        <?php echo substr($user->name, 0, 1); ?>
                    </div>
                    <div class="hidden lg:block">
                        <p class="text-sm font-medium"><?php echo $user->name; ?></p>
                        <p class="text-xs text-gray-600"><?php echo ucfirst(str_replace('_', ' ', $user->role)); ?></p>
                    </div>
                </div>

                <?php if ($user->role == 'admin' || $user->role == 'super_admin' || $user->role == 'inspector'): ?>
                <a href="<?php echo $base_path; ?>/admin/admin_logout.php" class="bg-white border border-gray-300 rounded-md px-3 py-2 text-sm">
                    <i class="fas fa-sign-out-alt"></i>
                </a>
                <?php else: ?>
                <a href="<?php echo $portal_prefix; ?>logout.php" class="bg-white border border-gray-300 rounded-md px-3 py-2 text-sm">
                    <i class="fas fa-sign-out-alt"></i>
                </a>
                <?php endif; ?>

                <?php else: ?>
                <a href="public_login.php" class="bg-blue-600 text-white rounded-md px-4 py-2 text-sm hover:bg-blue-700">
                    Login
                </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</header>

<!-- Sidebar Navigation -->
<?php if (isset($_SESSION['user_id'])): ?>
<div id="sidebar" class="fixed top-16 left-0 h-full w-64 bg-white shadow-lg border-r transform -translate-x-full md:translate-x-0 transition-transform duration-300 ease-in-out z-40">
    <div class="flex flex-col h-full">
        <nav class="flex-1 px-4 py-6 space-y-2">
            <?php foreach ($nav_items as $permission => $item): ?>
                <?php
                    // 1. Check base permission
                    if (!currentUserHasPermission($permission)) continue;

                    // 2. Check role-specific inclusion
                    if (isset($item['roles']) && !in_array($current_user_role, $item['roles'])) continue;

                    // 3. Check role-specific exclusion
                    if (isset($item['exclude_roles']) && in_array($current_user_role, $item['exclude_roles'])) continue;

                    // 4. Determine label, icon, and href
                    $label = $item['label_overrides'][$current_user_role] ?? $item['label'];
                    $icon = $item['icon_overrides'][$current_user_role] ?? $item['icon'];
                    $href = $portal_prefix . ($item['href'] ?? $permission . '.php');

                    // 5. Determine active state
                    $is_active = ($current_page == ($item['href'] ?? $permission . '.php'));
                    $active_class = $is_active ? 'bg-blue-50 text-blue-600 border-r-4 border-blue-600' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900';
                ?>
                <a href="<?php echo $href; ?>" class="flex items-center space-x-3 px-4 py-3 rounded-lg text-sm font-medium <?php echo $active_class; ?>">
                    <i class="fas <?php echo $icon; ?> text-lg w-6 text-center"></i>
                    <span><?php echo $label; ?></span>
                </a>
            <?php endforeach; ?>
        </nav>
    </div>
</div>

<!-- Overlay for mobile -->
<div id="sidebar-overlay" class="fixed inset-0 bg-black bg-opacity-50 z-30 md:hidden hidden"></div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const sidebar = document.getElementById('sidebar');
    const sidebarToggle = document.getElementById('sidebar-toggle');
    const sidebarOverlay = document.getElementById('sidebar-overlay');

    // Toggle sidebar on mobile
    sidebarToggle.addEventListener('click', function() {
        sidebar.classList.toggle('-translate-x-full');
        sidebarOverlay.classList.toggle('hidden');
    });

    // Close sidebar when clicking overlay
    sidebarOverlay.addEventListener('click', function() {
        sidebar.classList.add('-translate-x-full');
        sidebarOverlay.classList.add('hidden');
    });

    // Close sidebar on window resize if desktop
    window.addEventListener('resize', function() {
        if (window.innerWidth >= 768) { // md breakpoint
            sidebar.classList.remove('-translate-x-full');
            sidebarOverlay.classList.add('hidden');
        } else {
            sidebar.classList.add('-translate-x-full');
        }
    });
});
</script>
<?php endif; ?>
