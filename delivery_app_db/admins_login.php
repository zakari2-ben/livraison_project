<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion Administrateur</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            font-family: Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .login-container {
            background: white;
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.1);
            max-width: 400px;
            width: 100%;
        }
        
        .login-title {
            text-align: center;
            margin-bottom: 30px;
            color: #333;
            font-weight: bold;
        }
        
        .form-control {
            border-radius: 10px;
            border: 2px solid #e9ecef;
            padding: 12px 15px;
            margin-bottom: 20px;
        }
        
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        
        .btn-login {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 10px;
            padding: 12px;
            width: 100%;
            color: white;
            font-weight: bold;
            transition: all 0.3s ease;
        }
        
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
        }
        
        .alert {
            border-radius: 10px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <h2 class="login-title">Connexion Administrateur JR</h2>
        
        <form method="POST" action="">
            <div class="mb-3">
                <label class="form-label">Adresse e-mail :</label>
                <input type="email" class="form-control" name="email" required>
            </div>
            
            <!-- <div class="mb-3">
                <label class="form-label">Numéro CIN :</label>
                <input type="text" class="form-control" name="cin" required>
            </div> -->
            <div class="mb-3">
                <label class="form-label">password :</label>
                <input type="password" class="form-control" name="cin" required>
            </div>
            
            <button type="submit" class="btn btn-login">Se connecter</button>
        </form>
        
        <div class="text-center mt-3">
            <a href="index.php" class="text-decoration-none">Retour à la page d'accueil</a>
        </div>
    </div>

    <?php
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        // Database connection
        $servername = "localhost";
        $username = "root";
        $password = "";
        $dbname = "delivery_app_db";

        try {
            $conn = new PDO("mysql:host=$servername;dbname=$dbname;charset=utf8mb4", $username, $password);
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            $email = $_POST['email'];
            $cin = $_POST['cin'];
            
            // Check admin if exist
            $stmt = $conn->prepare("SELECT * FROM admins_inf WHERE email = :email AND cin = :cin");
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':cin', $cin);
            $stmt->execute();
            
            if ($stmt->rowCount() > 0) {
                $admin = $stmt->fetch(PDO::FETCH_ASSOC);
                
                // Start session and redirect
                session_start();
                $_SESSION['admin_id'] = $admin['id'];
                $_SESSION['admin_name'] = $admin['nom'] . ' ' . $admin['prenom'];
                $_SESSION['admin_email'] = $admin['email'];
                
                header("Location: admins_inf.php");
                exit();
            } else {
                echo '<div class="alert alert-danger mt-3">Identifiants de connexion incorrects</div>';
            }
            
        } catch(PDOException $e) {
            echo '<div class="alert alert-danger mt-3">Erreur de connexion à la base de données</div>';
        }
    }
    ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>