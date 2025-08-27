<?php
session_start();
session_unset();
session_destroy();
header('Location: public_login.php');
exit;
?>
