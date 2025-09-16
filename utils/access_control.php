<?php
/**
 * Handles user roles, permissions, and access control for the application.
 *
 * This file should be included after session_manager.php to ensure sessions are started.
 */

if (session_status() === PHP_SESSION_NONE) {
    // Fallback to start session if not already started.
    // It's better to include 'utils/session_manager.php' before this file.
    session_start();
}

/**
 * Defines permissions for each user role.
 * 'super_admin' has a special wildcard '*' which grants all permissions.
 */
const ROLE_PERMISSIONS = [
    'super_admin' => ['*'],
    'admin' => [
        'dashboard', 'inspections', 'businesses', 'user_management', 'analytics', 
        'violations', 'profile', 'schedule', 'active_sessions', 'assigned_inspections', 
        'checklist_management', 'inspection_types_management', 'inspectors'
    ],
    'inspector' => [
        'dashboard', 'assigned_inspections', 'profile', 'violations', 'inspections', 'businesses'
    ],
    'business_owner' => [
        'dashboard', 'violations', 'profile', 'businesses', 'inspections', 'analytics'
    ],
    'community_user' => [
        'dashboard', 'violations', 'profile', 'businesses', 'inspections'
    ],
    'guest' => [] // Guests have no permissions by default.
];

/**
 * Defines the base directory for each user role for redirection purposes.
 */
const ROLE_BASE_PATHS = [
    'super_admin' => '/admin/',
    'admin' => '/admin/',
    'inspector' => '/inspector/',
    'business_owner' => '/business/',
    'community_user' => '/community/',
    'guest' => '/' // Guests are handled as a special case in requirePermission.
];

/**
 * Checks if the currently logged-in user has a specific permission.
 *
 * @param string $permission The permission to check (e.g., 'dashboard', 'user_management').
 * @return bool True if the user has the permission, false otherwise.
 */
function currentUserHasPermission($permission)
{
    $role = $_SESSION['user_role'] ?? 'guest';

    if (!isset(ROLE_PERMISSIONS[$role])) {
        return false;
    }

    $permissions = ROLE_PERMISSIONS[$role];

    // Super admin has all permissions if '*' is present.
    if (in_array('*', $permissions, true)) {
        return true;
    }

    return in_array($permission, $permissions, true);
}

/**
 * Enforces a permission check. If the user does not have the required permission,
 * it redirects them to an appropriate page.
 *
 * @param string $permission The permission required to access the current page.
 * @param string $redirectPage The page to redirect to on failure, relative to the role's base path. Defaults to 'index.php'.
 */
function requirePermission($permission, $redirectPage = 'index.php')
{
    $currentPath = $_SERVER['SCRIPT_NAME'];
    $role = $_SESSION['user_role'] ?? 'guest';

    // Allow public access to login/registration pages without a permission check.
    $publicPages = ['main_login.php', 'admin_login.php', 'public_login.php', 'public_register.php', 'forgot_password.php', 'reset_password.php'];
    if (in_array(basename($currentPath), $publicPages, true)) {
        return;
    }

    // If user is not logged in (is a guest), redirect to the main login page.
    if ($role === 'guest') {
        header('Location: /main_login.php?error=login_required');
        exit;
    }

    // Check if the logged-in user has the required permission.
    if (!currentUserHasPermission($permission)) {
        // Determine the base path for redirection based on the user's role.
        $base_path = ROLE_BASE_PATHS[$role] ?? '/';
        $redirect_url = $base_path . $redirectPage;

        // Prevent redirection loops. If the target is the current page, redirect to the role's dashboard.
        if (strpos($currentPath, basename($redirect_url)) !== false && basename($redirect_url) !== 'index.php') {
            $redirect_url = $base_path . 'index.php';
        }

        header('Location: ' . $redirect_url . '?error=access_denied');
        exit;
    }
}
?>
