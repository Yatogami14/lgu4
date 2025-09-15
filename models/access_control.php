<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

const ROLE_PERMISSIONS = [
    'super_admin' => [
        'dashboard', 'inspections', 'businesses', 'user_management', 'analytics', 
        'violations', 'profile', 'schedule', 'active_sessions', 'inspectors', 
        'assigned_inspections'
    ],
    'admin' => [
        'dashboard', 'inspections', 'businesses', 'user_management', 'analytics', 
        'violations', 'profile', 'schedule', 'inspectors',
        'assigned_inspections'
    ],
    'inspector' => [
        'dashboard', 'assigned_inspections', 'profile'
    ],
    'business_owner' => [
        'dashboard', 'violations', 'profile', 'businesses'
    ],
    'community_user' => [
        'dashboard', 'violations', 'profile'
    ],
];

function currentUserHasPermission($permission) {
    $role = $_SESSION['user_role'] ?? 'guest';
    if ($role === 'super_admin' || $role === 'admin') {
        return true;
    }
    if (isset(ROLE_PERMISSIONS[$role])) {
        return in_array($permission, ROLE_PERMISSIONS[$role]);
    }
    return false;
}

function requirePermission($permission, $redirect_page = 'index.php') {
    if (!currentUserHasPermission($permission)) {
        $role = $_SESSION['user_role'] ?? 'guest';
        $base_path = '/lgu4/';
        switch ($role) {
            case 'business_owner':
                $redirect_url = $base_path . 'business/' . $redirect_page;
                break;
            case 'community_user':
                $redirect_url = $base_path . 'community/' . $redirect_page;
                break;
            default: // admin, super_admin, inspector, guest
                $redirect_url = $base_path . 'admin/' . $redirect_page;
        }
        
        header('Location: ' . $redirect_url . '?error=access_denied');
        exit;
    }
}
?>