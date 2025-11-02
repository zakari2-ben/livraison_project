<?php
$host = 'localhost';
$db   = 'delivery_app_db'; // Nom de la base de données que tu as créé
$user = 'root';            // Utilisateur par défaut pour XAMPP/WAMP
$pass = '';                // Mot de passe vide par défaut pour XAMPP/WAMP
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    die("Erreur de connexion à la base de données: " . $e->getMessage());
}
?>
