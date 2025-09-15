<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

const ROLE_PERMISSIONS = [
    'super_admin' => [
        'dashboard', 'inspections', 'businesses', 'user_management', 'analytics', 
        'violations', 'profile', 'schedule', 'active_sessions', 'assigned_inspections', 'checklist_management', 'inspection_types_management'
    ],
    'admin' => [
        'dashboard', 'inspections', 'businesses', 'user_management', 'analytics', 
        'violations', 'profile', 'schedule', 'assigned_inspections', 'checklist_management', 'inspection_types_management'
    ],
    'inspector' => [
        'dashboard', 'assigned_inspections', 'profile', 'violations', 'inspections', 'businesses'
    ],
    'business_owner' => [
        'dashboard', 'violations', 'profile', 'businesses', 'inspections'
    ],
    'community_user' => [
        'dashboard', 'violations', 'profile', 'businesses', 'inspections'
    ],
];

function currentUserHasPermission($permission) {
    $role = $_SESSION['user_role'] ?? 'guest';
    if ($role === 'super_admin') { // Super admin has all permissions
        return true; 
    }
    if (isset(ROLE_PERMISSIONS[$role])) {
        return in_array($permission, ROLE_PERMISSIONS[$role]);
    }
    return false;
}

function requirePermission($permission, $redirect_page = 'index.php') {
    $role = $_SESSION['user_role'] ?? 'guest';
    $current_path = $_SERVER['SCRIPT_NAME'];

    // Prevent inspectors from accessing the /admin/ area directly.
    if (strpos($current_path, '/admin/') !== false && $role === 'inspector') {
        header('Location: /lgu4/inspector/index.php?error=access_denied');
        exit;
    }
    if (!currentUserHasPermission($permission)) {
        $current_page = basename($_SERVER['PHP_SELF']);
        $base_path = '/lgu4/';
        switch ($role) {
            case 'business_owner':
                $redirect_url = $base_path . 'business/' . $redirect_page;
                break;
            case 'community_user':
                $redirect_url = $base_path . 'community/' . $redirect_page;
                break;
            case 'inspector':
                // If redirecting to the same page would cause a loop, log the user out.
                if ($current_page === $redirect_page) {
                    header('Location: ' . $base_path . 'admin/admin_logout.php');
                    exit;
                }
                $redirect_url = $base_path . 'inspector/' . $redirect_page;
                break;
            default: // admin, super_admin, guest
                $redirect_url = $base_path . 'admin/' . $redirect_page;
        }
        
        header('Location: ' . $redirect_url . '?error=access_denied');
        exit;
    }
}
?>
