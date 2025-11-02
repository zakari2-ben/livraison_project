<?php
session_start();

// Redirection si l'utilisateur est déjà connecté
if (isset($_SESSION['livreur_id'])) {
    header("Location: livreur.php");
    exit();
}

$login_message = '';

// Connexion à la base de données (avec les paramètres par défaut de XAMPP/WAMP)
$host = 'localhost';
$db   = 'delivery_app_db'; // Nom de la base de données
$user = 'root';            // Utilisateur par défaut
$pass = '';                // Mot de passe vide par défaut
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

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'] ?? '';
    $cin = $_POST['cin'] ?? ''; // CIN as password

    if (empty($email) || empty($cin)) {
        $login_message = "<div class='alert alert-danger'><i class='fas fa-exclamation-triangle me-2'></i>Veuillez remplir tous les champs.</div>";
    } else {
        // Préparer et exécuter la requête pour trouver le livreur par email et CIN
        $stmt = $pdo->prepare("SELECT id, email, cin FROM livreurs WHERE email = ? AND cin = ?");
        $stmt->execute([$email, $cin]);
        $livreur = $stmt->fetch();

        if ($livreur) {
            // Authentification réussie
            $_SESSION['livreur_id'] = $livreur['id'];
            $_SESSION['livreur_email'] = $livreur['email'];
            header("Location: livreur.php");
            exit();
        } else {
            // Échec de l'authentification
            $login_message = "<div class='alert alert-danger'><i class='fas fa-times-circle me-2'></i>Email ou CIN incorrect.</div>";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion Livreur</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-gradient-start: #1a0033; /* Violet profond */
            --bg-gradient-end: #0a3d62;   /* Bleu sarcelle foncé */
            --card-bg: rgba(255, 255, 255, 0.07); /* Verre léger */
            --card-border: rgba(255, 255, 255, 0.15);
            --text-color: #e0e0e0; /* Blanc cassé pour le texte */
            --heading-color: #fff; /* Blanc pur pour les titres */
            
            --primary-accent: #33aaff; /* Bleu ciel vif */
            --success-color: #2ecc71; /* Vert émeraude */
            --warning-color: #f1c40f; /* Or ambré */
            --danger-color: #e74c3c; /* Rouge riche */
            
            --button-gradient-primary: linear-gradient(45deg, #33aaff, #007bff);
            --button-gradient-danger: linear-gradient(45deg, #e74c3c, #c0392b);

            --shadow-light: 0 5px 20px rgba(0, 0, 0, 0.3);
            --shadow-medium: 0 10px 30px rgba(0, 0, 0, 0.5);
            --shadow-heavy: 0 15px 40px rgba(0, 0, 0, 0.7);

            --input-bg: rgba(0, 0, 0, 0.3);
            --input-border: rgba(255, 255, 255, 0.2);
            --focus-glow: 0 0 0 0.25rem rgba(51, 170, 255, 0.4); /* Lueur bleu ciel */
        }

        body {
            background: linear-gradient(135deg, var(--bg-gradient-start), var(--bg-gradient-end));
            font-family: 'Montserrat', sans-serif;
            color: var(--text-color);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
            overflow: hidden;
            background-size: 200% 200%;
            animation: gradientBackground 15s ease infinite alternate;
        }

        @keyframes gradientBackground {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        .login-container {
            background: var(--card-bg);
            backdrop-filter: blur(20px);
            border: 1px solid var(--card-border);
            padding: 50px;
            border-radius: 30px;
            box-shadow: var(--shadow-heavy);
            max-width: 500px;
            width: 100%;
            animation: fadeInScale 0.8s ease-out forwards;
            position: relative;
        }

        @keyframes fadeInScale {
            from {
                opacity: 0;
                transform: scale(0.9);
            }
            to {
                opacity: 1;
                transform: scale(1);
            }
        }

        h2 {
            color: var(--heading-color);
            text-shadow: 0 4px 12px rgba(0, 0, 0, 0.6);
            font-weight: 700;
            margin-bottom: 40px;
            text-align: center;
            letter-spacing: 1.5px;
            font-size: 2.5rem;
        }
        h2 i {
            color: var(--primary-accent);
            font-size: 0.9em;
            vertical-align: middle;
            margin-right: 15px;
        }

        .form-label {
            color: var(--text-color);
            font-weight: 500;
            margin-bottom: 10px;
            display: block;
        }

        .form-control {
            background-color: var(--input-bg);
            border: 1px solid var(--input-border);
            color: var(--text-color);
            border-radius: 15px;
            padding: 16px 20px;
            transition: all 0.3s ease;
            font-size: 1.1rem;
        }
        .form-control:hover {
            border-color: rgba(255, 255, 255, 0.3);
        }
        .form-control:focus {
            background-color: rgba(0, 0, 0, 0.4);
            border-color: var(--primary-accent);
            box-shadow: var(--focus-glow);
            color: var(--text-color);
            outline: 0;
        }
        /* Style pour les champs de mot de passe pour masquer les caractères */
        .form-control[type="password"] {
            font-family: 'Montserrat', sans-serif; /* Assure que le font est cohérent */
            /* Utilisez un caractère non espacé pour une meilleure apparence des points */
            -webkit-text-security: disc;
            text-security: disc;
        }

        .btn-primary {
            background: var(--button-gradient-primary);
            border: none;
            border-radius: 20px;
            padding: 16px 35px;
            font-weight: 600;
            font-size: 1.2rem;
            box-shadow: var(--shadow-light);
            transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            width: 100%;
            margin-top: 30px;
        }
        .btn-primary:hover {
            transform: translateY(-6px) scale(1.02);
            box-shadow: var(--shadow-heavy);
            background: linear-gradient(45deg, #55bdff, #2299ff);
        }
        .btn-primary:active {
            transform: translateY(0);
            box-shadow: var(--shadow-light);
        }

        .alert {
            border-radius: 20px;
            padding: 20px 30px;
            font-weight: 500;
            margin-bottom: 30px;
            display: flex;
            align-items: center;
            gap: 15px;
            animation: slideInDown 0.7s cubic-bezier(0.25, 0.8, 0.25, 1) forwards;
            color: #fff;
            text-shadow: 0 1px 5px rgba(0,0,0,0.5);
            border: none;
            box-shadow: var(--shadow-medium);
            font-size: 1.1rem;
        }
        .alert-danger {
            background: var(--button-gradient-danger);
        }

        @keyframes slideInDown {
            from {
                opacity: 0;
                transform: translateY(-40px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <h2 class="mb-4">
            <i class="fas fa-motorcycle"></i> Connexion Livreur
        </h2>
        <?php echo $login_message; ?>
        <form method="POST" action="livreur_login.php">
            <div class="mb-4">
                <label for="email" class="form-label"><i class="fas fa-envelope me-2"></i>Email</label>
                <input type="email" class="form-control" id="email" name="email" required autocomplete="email">
            </div>
            <div class="mb-4">
                <label for="cin" class="form-label"><i class="fas fa-id-card me-2"></i>Mot de passe</label>
                <input type="password" class="form-control" id="cin" name="cin" required autocomplete="current-password">
            </div>
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-sign-in-alt"></i> Se connecter
            </button>
        </form>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>