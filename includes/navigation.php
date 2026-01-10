<?php
// Navigation component that can be included in any page
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include Tailwind config and dependencies
?>
<script src="https://cdn.tailwindcss.com"></script>
<script>
    tailwind.config = {
        theme: {
            extend: {
                fontFamily: { sans: ['Inter', 'sans-serif'] },
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
                animation: {
                    'fade-in': 'fadeIn 0.5s ease-out',
                    'slide-up': 'slideUp 0.5s ease-out',
                },
                keyframes: {
                    fadeIn: {
                        '0%': { opacity: '0' },
                        '100%': { opacity: '1' },
                    },
                    slideUp: {
                        '0%': { transform: 'translateY(20px)', opacity: '0' },
                        '100%': { transform: 'translateY(0)', opacity: '1' },
                    }
                }
            }
        }
    }
</script>
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
<?php

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

            <a href="<?php echo $base_path; ?>/index.php" class="flex items-center space-x-2 sm:space-x-4 min-w-0 flex-1 md:flex-none" title="Go to Homepage">
                <img src="<?php echo $base_path; ?>/logo/logo.jpeg?v=4" alt="Logo" class="h-8 w-auto">
                <div class="min-w-0 flex-1 md:flex-none">
                    <h1 class="text-sm sm:text-xl font-bold text-gray-900 truncate antialiased">LGU Health & Safety</h1>
                    <p class="text-xs sm:text-sm text-gray-900 hidden sm:block antialiased">Digital Inspection Platform</p>
                </div>
            </a>

            <div class="flex items-center space-x-1 sm:space-x-4 flex-shrink-0">
                <?php if (isset($_SESSION['user_id'])): ?>
                <!-- Notification Bell -->
                <div class="relative" id="notification-bell-container">
                    <button id="notification-bell-button" class="p-2 rounded-full text-gray-600 hover:bg-gray-100 hover:text-gray-800 focus:outline-none">
                        <i class="fas fa-bell"></i>
                        <!-- Notification dot with count -->
                        <span id="notification-count" class="absolute top-0 right-0 block h-4 w-4 rounded-full bg-red-600 text-white text-xs flex items-center justify-center ring-2 ring-white hidden"></span>
                    </button>
                    
                    <div id="notification-dropdown-menu" class="origin-top-right absolute right-0 mt-2 w-80 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5 focus:outline-none hidden">
                        <div class="flex justify-between items-center px-4 py-2 border-b">
                            <p class="text-sm font-medium text-gray-800">Notifications</p>
                        </div>
                        <div id="notification-list" class="py-1 max-h-80 overflow-y-auto" role="menu" aria-orientation="vertical" aria-labelledby="notification-bell-button">
                            <!-- Notifications will be dynamically inserted here -->
                        </div>
                        <div class="border-t">
                            <a href="#" class="block px-4 py-2 text-sm text-brand-600 hover:bg-brand-50 text-center" role="menuitem">
                                View All Notifications
                            </a>
                        </div>
                    </div>
                </div>

                <div class="flex items-center space-x-2">
                    <div class="relative">
                        <button id="user-menu-button" class="flex items-center space-x-2 focus:outline-none p-1 rounded-md hover:bg-gray-100">
                            <div class="w-8 h-8 bg-brand-500 rounded-full flex items-center justify-center text-white text-xs font-bold">
                                <?php echo substr($user->name, 0, 1); ?>
                            </div>
                            <div class="hidden lg:block text-left">
                                <p class="text-sm font-medium text-gray-900 antialiased"><?php echo $user->name; ?></p>
                                <p class="text-xs text-gray-900 antialiased"><?php echo ucfirst(str_replace('_', ' ', $user->role)); ?></p>
                            </div>
                            <i class="fas fa-chevron-down text-gray-400 text-xs ml-1"></i>
                        </button>

                        <div id="user-dropdown-menu" class="origin-top-right absolute right-0 mt-2 w-48 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5 focus:outline-none hidden">
                            <div class="py-1" role="menu" aria-orientation="vertical" aria-labelledby="user-menu-button">
                                <a href="<?php echo $portal_prefix; ?>profile.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100" role="menuitem">
                                    <i class="fas fa-user-circle mr-2"></i>Profile
                                </a>
                                <a href="<?php echo $base_path; ?>/logout.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100" role="menuitem">
                                    <i class="fas fa-sign-out-alt mr-2"></i>Logout
                                </a>
                            </div>
                        </div>
                    </div>


                <?php else: ?>
                <a href="public_login.php" class="bg-brand-500 text-white rounded-md px-4 py-2 text-sm hover:bg-brand-600">
                    Login
                </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</header>

<!-- Sidebar Navigation -->
<?php if (isset($_SESSION['user_id'])): ?>
<div id="sidebar" class="fixed top-16 left-0 h-full w-64 bg-gray-800 shadow-lg transform -translate-x-full md:translate-x-0 transition-transform duration-300 ease-in-out z-40" style="background-color: var(--auth-bg-dark);">
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
                    $active_class = $is_active ? 'bg-brand-500 text-white' : 'text-gray-400 hover:bg-gray-700 hover:text-white';
                ?>
                <a href="<?php echo $href; ?>" class="flex items-center space-x-3 px-3 py-2 rounded-md text-sm font-medium <?php echo $active_class; ?> antialiased">
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
    const notificationBell = document.getElementById('notification-bell-button');
    const notificationDropdown = document.getElementById('notification-dropdown-menu');
    const notificationCount = document.getElementById('notification-count');
    const notificationList = document.getElementById('notification-list');

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

    const updateNotificationCount = async () => {
        try {
            const response = await fetch('<?php echo $base_path; ?>/api/fetch_notifications.php?type=count');
            if (!response.ok) {
                console.error('Network response was not ok for notification count.');
                return;
            }
            const data = await response.json();

            if (data.success && data.unread_count > 0) {
                notificationCount.textContent = data.unread_count > 9 ? '9+' : data.unread_count;
                notificationCount.classList.remove('hidden');
            } else {
                notificationCount.classList.add('hidden');
            }
        } catch (error) {
            console.error('Failed to fetch notification count:', error);
        }
    };

    const fetchFullNotificationList = async () => {
        try {
            const response = await fetch('<?php echo $base_path; ?>/api/fetch_notifications.php');
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            const data = await response.json();

            if (data.success) {
                // Update notification count badge
                if (data.unread_count > 0) {
                    notificationCount.textContent = data.unread_count > 9 ? '9+' : data.unread_count;
                    notificationCount.classList.remove('hidden');
                } else {
                    notificationCount.classList.add('hidden');
                }

                // Populate notification list
                notificationList.innerHTML = ''; // Clear existing
                if (data.notifications.length > 0) {
                    data.notifications.forEach(notif => {
                        const notifLink = document.createElement('a');
                        notifLink.href = notif.link || '#';
                        notifLink.className = `block px-4 py-3 text-sm hover:bg-gray-100 ${notif.is_read == 0 ? 'bg-yellow-50' : 'bg-white'}`;
                        notifLink.role = 'menuitem';

                        const messageP = document.createElement('p');
                        messageP.className = `font-medium text-gray-800 truncate`;
                        messageP.textContent = notif.message;

                        const timeP = document.createElement('p');
                        timeP.className = 'text-xs text-gray-500 mt-1';
                        timeP.textContent = new Date(notif.created_at).toLocaleString();

                        notifLink.appendChild(messageP);
                        notifLink.appendChild(timeP);
                        notificationList.appendChild(notifLink);
                    });
                } else {
                    notificationList.innerHTML = '<p class="text-center text-gray-500 py-4 text-sm">No notifications yet.</p>';
                }
            }
        } catch (error) {
            console.error('Failed to fetch notifications:', error);
            notificationList.innerHTML = '<p class="text-center text-red-500 py-4 text-sm">Could not load notifications.</p>';
        }
    };

    // Initial fetch for the count on page load
    updateNotificationCount();

    // Toggle notification dropdown and fetch full list on click
    notificationBell.addEventListener('click', function(event) {
        event.stopPropagation();
        const isHidden = notificationDropdown.classList.contains('hidden');
        if (isHidden) { fetchFullNotificationList(); } // Refresh list when opening
        notificationDropdown.classList.toggle('hidden');
    });

    // Dropdown menu for user avatar
    const userMenuButton = document.getElementById('user-menu-button');
    const userDropdownMenu = document.getElementById('user-dropdown-menu');

    if (userMenuButton && userDropdownMenu) {
        userMenuButton.addEventListener('click', function(event) { // Missing opening brace
            event.stopPropagation(); // Prevent click from immediately closing the dropdown
            userDropdownMenu.classList.toggle('hidden');
        }); // This should be a closing brace } and a closing parenthesis );

        // Close the dropdown if the user clicks outside of it
        document.addEventListener('click', function(event) {
            if (userDropdownMenu && !userMenuButton.contains(event.target) && !userDropdownMenu.contains(event.target)) {
                userDropdownMenu.classList.add('hidden');
            }
            if (notificationDropdown && !notificationBell.contains(event.target) && !notificationDropdown.contains(event.target)) {
                notificationDropdown.classList.add('hidden');
            }
        });

        // Close dropdown when a link inside is clicked
        const dropdownLinks = userDropdownMenu.querySelectorAll('a');
        dropdownLinks.forEach(function(link) {
            link.addEventListener('click', function() {
                userDropdownMenu.classList.add('hidden');
            });
        });
    }
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
