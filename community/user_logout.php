<?php
session_start();
session_unset();
session_destroy();
header('Location: ../main_login.php');
exit;
?>
