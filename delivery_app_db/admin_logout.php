<?php
session_start();
session_unset(); // Supprime toutes les variables de session
session_destroy(); // Détruit la session
header('Location: admin_login.php'); // Redirige vers la page de login
exit();
?>