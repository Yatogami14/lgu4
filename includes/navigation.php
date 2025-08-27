<?php
// Navigation component that can be included in any page
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Use absolute paths to avoid issues when included from different directories
$rootPath = dirname(__DIR__);
require_once '../config/database.php';
require_once '../models/User.php';
require_once $rootPath . '/utils/access_control.php';

// Get current user info for navigation
if (isset($_SESSION['user_id'])) {
    $database = new Database();
    $db = $database->getConnection();
    $user = new User($db);
    $user->id = $_SESSION['user_id'];
    $user->readOne();
}
?>
<!-- Navigation Header -->
<header class="bg-white shadow-sm border-b">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center h-16">
            <div class="flex items-center space-x-2 sm:space-x-4 min-w-0 flex-1">
                <i class="fas fa-shield-alt text-blue-600 text-xl sm:text-2xl"></i>
                <div class="min-w-0 flex-1">
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
                <a href="admin_logout.php" class="bg-white border border-gray-300 rounded-md px-3 py-2 text-sm">
                    <i class="fas fa-sign-out-alt"></i>
                </a>
                <?php else: ?>
                <a href="user_logout.php" class="bg-white border border-gray-300 rounded-md px-3 py-2 text-sm">
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

<!-- Navigation Tabs -->
<?php if (isset($_SESSION['user_id'])): ?>
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-2">
    <div class="w-full overflow-x-auto">
        <div class="grid grid-cols-4 sm:grid-cols-4 lg:grid-cols-8 min-w-max bg-gray-100 rounded-lg p-1">
            <a href="index.php" class="flex items-center justify-center space-x-1 sm:space-x-2 px-2 sm:px-3 py-2 rounded-md text-sm font-medium <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'bg-white text-blue-600 shadow' : 'text-gray-600 hover:text-gray-900'; ?>">
                <i class="fas fa-chart-bar text-sm"></i>
                <span class="hidden sm:inline">Dashboard</span>
            </a>
            <?php if (currentUserHasPermission('inspections')): ?>
            <a href="inspections.php" class="flex items-center justify-center space-x-1 sm:space-x-2 px-2 sm:px-3 py-2 rounded-md text-sm font-medium <?php echo basename($_SERVER['PHP_SELF']) == 'inspections.php' ? 'bg-white text-blue-600 shadow' : 'text-gray-600 hover:text-gray-900'; ?>">
                <i class="fas fa-file-alt text-sm"></i>
                <span class="hidden sm:inline">Inspections</span>
            </a>
            <?php endif; ?>
            <?php if (currentUserHasPermission('schedule')): ?>
            <a href="schedule.php" class="flex items-center justify-center space-x-1 sm:space-x-2 px-2 sm:px-3 py-2 rounded-md text-sm font-medium <?php echo basename($_SERVER['PHP_SELF']) == 'schedule.php' ? 'bg-white text-blue-600 shadow' : 'text-gray-600 hover:text-gray-900'; ?>">
                <i class="fas fa-calendar text-sm"></i>
                <span class="hidden sm:inline">Schedule</span>
            </a>
            <?php endif; ?>
            <?php if (currentUserHasPermission('violations')): ?>
            <a href="violations.php" class="flex items-center justify-center space-x-1 sm:space-x-2 px-2 sm:px-3 py-2 rounded-md text-sm font-medium <?php echo basename($_SERVER['PHP_SELF']) == 'violations.php' ? 'bg-white text-blue-600 shadow' : 'text-gray-600 hover:text-gray-900'; ?>">
                <i class="fas fa-exclamation-triangle text-sm"></i>
                <span class="hidden sm:inline">Violations</span>
            </a>
            <?php endif; ?>
            <?php if (currentUserHasPermission('businesses')): ?>
            <a href="businesses.php" class="flex items-center justify-center space-x-1 sm:space-x-2 px-2 sm:px-3 py-2 rounded-md text-sm font-medium <?php echo basename($_SERVER['PHP_SELF']) == 'businesses.php' ? 'bg-white text-blue-600 shadow' : 'text-gray-600 hover:text-gray-900'; ?>">
                <i class="fas fa-users text-sm"></i>
                <span class="hidden lg:inline">Businesses</span>
            </a>
            <?php endif; ?>
            <?php if (currentUserHasPermission('inspectors')): ?>
            <a href="inspectors.php" class="flex items-center justify-center space-x-1 sm:space-x-2 px-2 sm:px-3 py-2 rounded-md text-sm font-medium <?php echo basename($_SERVER['PHP_SELF']) == 'inspectors.php' ? 'bg-white text-blue-600 shadow' : 'text-gray-600 hover:text-gray-900'; ?>">
                <i class="fas fa-user text-sm"></i>
                <span class="hidden lg:inline">Inspectors</span>
            </a>
            <?php endif; ?>
            
            <?php if (currentUserHasPermission('user_management')): ?>
            <a href="user_management.php" class="flex items-center justify-center space-x-1 sm:space-x-2 px-2 sm:px-3 py-2 rounded-md text-sm font-medium <?php echo basename($_SERVER['PHP_SELF']) == 'user_management.php' ? 'bg-white text-blue-600 shadow' : 'text-gray-600 hover:text-gray-900'; ?>">
                <i class="fas fa-users-cog text-sm"></i>
                <span class="hidden lg:inline">Community Users</span>
            </a>
            <?php endif; ?>
            
            <?php if ($_SESSION['user_role'] === 'inspector'): ?>
            <a href="assigned_inspections.php" class="flex items-center justify-center space-x-1 sm:space-x-2 px-2 sm:px-3 py-2 rounded-md text-sm font-medium <?php echo basename($_SERVER['PHP_SELF']) == 'assigned_inspections.php' ? 'bg-white text-blue-600 shadow' : 'text-gray-600 hover:text-gray-900'; ?>">
                <i class="fas fa-clipboard-list text-sm"></i>
                <span class="hidden lg:inline">My Assignments</span>
            </a>
            <?php endif; ?>
            <?php if (currentUserHasPermission('analytics')): ?>
            <a href="analytics.php" class="flex items-center justify-center space-x-1 sm:space-x-2 px-2 sm:px-3 py-2 rounded-md text-sm font-medium <?php echo basename($_SERVER['PHP_SELF']) == 'analytics.php' ? 'bg-white text-blue-600 shadow' : 'text-gray-600 hover:text-gray-900'; ?>">
                <i class="fas fa-chart-line text-sm"></i>
                <span class="hidden lg:inline">Analytics</span>
            </a>
            <?php endif; ?>
            <?php if (currentUserHasPermission('profile')): ?>
            <a href="profile.php" class="flex items-center justify-center space-x-1 sm:space-x-2 px-2 sm:px-3 py-2 rounded-md text-sm font-medium <?php echo basename($_SERVER['PHP_SELF']) == 'profile.php' ? 'bg-white text-blue-600 shadow' : 'text-gray-600 hover:text-gray-900'; ?>">
                <i class="fas fa-cog text-sm"></i>
                <span class="hidden lg:inline">Profile</span>
            </a>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php endif; ?>
