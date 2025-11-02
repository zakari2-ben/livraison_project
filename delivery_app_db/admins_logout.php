<?php
session_start();
session_destroy();
header("Location: admins_login.php");
exit();
?>