<?php
// Role-based access control utility

class AccessControl {
    
    // Define role permissions
    private static $permissions = [
        'super_admin' => [
            'dashboard' => true,
            'inspections' => true,
            'schedule' => true,
            'violations' => true,
            'businesses' => true,
            'inspectors' => true,
            'analytics' => true,
            'profile' => true,
            'user_management' => true,
            'system_config' => true
        ],
        'admin' => [
            'dashboard' => true,
            'inspections' => true,
            'schedule' => true,
            'violations' => true,
            'businesses' => true,
            'inspectors' => true,
            'analytics' => true,
            'profile' => true,
            'user_management' => false,
            'system_config' => false
        ],
        'inspector' => [
            'dashboard' => true,
            'inspections' => true,
            'schedule' => true,
            'violations' => true,
            'businesses' => true,
            'inspectors' => false,
            'analytics' => false,
            'profile' => true,
            'user_management' => false,
            'system_config' => false,
            'assigned_inspections' => true
        ],
        'business_owner' => [
            'dashboard' => true,
            'inspections' => false,
            'schedule' => false,
            'violations' => true,
            'businesses' => true,
            'inspectors' => false,
            'analytics' => false,
            'profile' => true,
            'user_management' => false,
            'system_config' => false
        ],
        'community_user' => [
            'dashboard' => true,
            'inspections' => false,
            'schedule' => false,
            'violations' => false,
            'businesses' => false,
            'inspectors' => false,
            'analytics' => false,
            'profile' => true,
            'user_management' => false,
            'system_config' => false
        ]
    ];

    // Check if user has permission for a specific resource
    public static function hasPermission($role, $resource) {
        if (!isset(self::$permissions[$role])) {
            return false;
        }
        
        return self::$permissions[$role][$resource] ?? false;
    }

    // Redirect user if they don't have permission
    public static function requirePermission($role, $resource, $redirectUrl = 'index.php') {
        if (!self::hasPermission($role, $resource)) {
            header("Location: $redirectUrl");
            exit;
        }
    }

    // Get all permissions for a role
    public static function getRolePermissions($role) {
        return self::$permissions[$role] ?? [];
    }

    // Check if user can access a page
    public static function canAccessPage($role, $page) {
        $pagePermissions = [
            'index.php' => 'dashboard',
            'inspections.php' => 'inspections',
            'schedule.php' => 'schedule',
            'violations.php' => 'violations',
            'businesses.php' => 'businesses',
            'inspectors.php' => 'inspectors',
            'analytics.php' => 'analytics',
            'profile.php' => 'profile',
            'user_management.php' => 'user_management',
            'system_config.php' => 'system_config'
        ];

        $resource = $pagePermissions[$page] ?? 'dashboard';
        return self::hasPermission($role, $resource);
    }
}

// Function to check if current user has permission
function currentUserHasPermission($resource) {
    if (!isset($_SESSION['user_role'])) {
        return false;
    }
    
    return AccessControl::hasPermission($_SESSION['user_role'], $resource);
}

// Function to require permission for current user
function requirePermission($resource, $redirectUrl = 'index.php') {
    if (!isset($_SESSION['user_role'])) {
        header("Location: login.php");
        exit;
    }
    
    AccessControl::requirePermission($_SESSION['user_role'], $resource, $redirectUrl);
}
?>
