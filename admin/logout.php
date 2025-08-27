<?php
session_start();

// Store user role before destroying session
$user_role = isset($_SESSION['user_role']) ? $_SESSION['user_role'] : null;

session_unset();
session_destroy();

if ($user_role) {
    if ($user_role == 'admin' || $user_role == 'super_admin' || $user_role == 'inspector') {
        header('Location: admin_login.php');
    } else {
        header('Location: public_login.php');
    }
} else {
    header('Location: public_login.php');
}
exit;
?>
