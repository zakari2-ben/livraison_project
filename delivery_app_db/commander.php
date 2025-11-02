<?php
// Connexion à la base de données via PDO
require "connexion.php";

$message_success = '';
$message_error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nom = $_POST['nom'] ?? '';
    $prenom = $_POST['prenom'] ?? '';
    $email = $_POST['email'] ?? '';
    $telephone = $_POST['telephone'] ?? '';
    $adresse = $_POST['adresse'] ?? '';
    $type_commande = $_POST['type_commande'] ?? '';
    $demande_exacte = $_POST['demande_exacte'] ?? '';

    // Validation simple
    if (empty($nom) || empty($prenom) || empty($email) || empty($telephone) || empty($adresse) || empty($type_commande) || empty($demande_exacte)) {
        $message_error = "Veuillez remplir tous les champs.";
    } else {
        try {
            // Insérer ou récupérer le client
            $stmt_client = $pdo->prepare("SELECT id FROM clients WHERE email = ?");
            $stmt_client->execute([$email]);
            $client = $stmt_client->fetch();

            if ($client) {
                $client_id = $client['id'];
            } else {
                $stmt_insert_client = $pdo->prepare("INSERT INTO clients (nom, prenom, email, telephone, adresse) VALUES (?, ?, ?, ?, ?)");
                $stmt_insert_client->execute([$nom, $prenom, $email, $telephone, $adresse]);
                $client_id = $pdo->lastInsertId();
            }

            // Récupérer les livreurs actifs
            // On s'assure qu'on récupère aussi le 'telephone' du livreur pour pouvoir le contacter
            $stmt_livreurs = $pdo->query("SELECT id, nom_livreur, prenom, telephone FROM livreurs WHERE statut = 'actif' LIMIT 10");
            $livreurs_actifs = $stmt_livreurs->fetchAll();

            if (empty($livreurs_actifs)) {
                $message_error = "Désolé, aucun livreur n'est disponible pour le moment. Veuillez réessayer plus tard.";
            } else {
                // Choisir un livreur aléatoire parmi les actifs
                $livreur_choisi = $livreurs_actifs[array_rand($livreurs_actifs)];
                $livreur_id = $livreur_choisi['id'];
                $livreur_nom_complet = htmlspecialchars($livreur_choisi['nom_livreur'] . ' ' . $livreur_choisi['prenom']);
                $livreur_tel = htmlspecialchars($livreur_choisi['telephone']);


                // Insérer la commande
                $stmt_commande = $pdo->prepare("INSERT INTO commandes (client_id, type_commande, demande_exacte, livreur_id, statut) VALUES (?, ?, ?, ?, 'en attente')");
                $stmt_commande->execute([$client_id, $type_commande, $demande_exacte, $livreur_id]);

                // Mettre à jour le statut du livreur à "inactif" après lui avoir assigné une commande
                // Cela est une stratégie simple, tu pourrais la modifier selon tes besoins
                $stmt_update_livreur_statut = $pdo->prepare("UPDATE livreurs SET statut = 'inactif' WHERE id = ?");
                $stmt_update_livreur_statut->execute([$livreur_id]);

                $message_success = "Votre commande a été envoyée avec succès au livreur " . $livreur_nom_complet . " (Tel: " . $livreur_tel . ")! Il vous contactera bientôt.";
            }

        } catch (\PDOException $e) {
            $message_error = "Erreur lors de l'enregistrement de la commande: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Passer une Commande</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        :root {
            --dark-blue-gradient: linear-gradient(135deg, #1A2980 0%, #26D0CE 100%); /* Dark Blue to Teal */
            --green-gradient: linear-gradient(135deg, #4CAF50 0%, #8BC34A 100%); /* Green to Light Green */
            --black-gradient: linear-gradient(135deg, #2C3E50 0%, #4A637F 100%); /* Dark Grey to Grey Blue */
            --glass-bg: rgba(255, 255, 255, 0.08); /* Lighter for forms */
            --glass-border: rgba(255, 255, 255, 0.15);
            --text-color-light: #f8f9fa;
            --input-bg: rgba(255, 255, 255, 0.15);
            --input-border: rgba(255, 255, 255, 0.3);
            --focus-glow: 0 0 0 0.25rem rgba(48, 140, 209, 0.25);
            --box-shadow-light: 0 4px 15px rgba(0, 0, 0, 0.2);
            --box-shadow-heavy: 0 8px 25px rgba(0, 0, 0, 0.4);
        }

        body {
            background: linear-gradient(135deg, #0f2027, #203a43, #2c5364); /* Dark, deep background */
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: var(--text-color-light);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px 0;
        }

        .container {
            background: var(--glass-bg);
            backdrop-filter: blur(15px);
            border: 1px solid var(--glass-border);
            padding: 40px;
            border-radius: 20px;
            box-shadow: var(--box-shadow-heavy);
            max-width: 800px;
            width: 100%;
            animation: fadeInScale 0.8s ease-out forwards;
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
            color: var(--text-color-light);
            text-shadow: 0 2px 5px rgba(0,0,0,0.4);
            font-weight: 700;
            margin-bottom: 30px;
            text-align: center;
        }

        .form-label {
            color: var(--text-color-light);
            font-weight: 500;
            margin-bottom: 8px;
            display: block;
        }

        .form-control,
        .form-select {
            background-color: var(--input-bg);
            border: 1px solid var(--input-border);
            color: var(--text-color-light);
            border-radius: 10px;
            padding: 12px 15px;
            transition: all 0.3s ease;
        }

        .form-control:focus,
        .form-select:focus {
            background-color: rgba(255, 255, 255, 0.25);
            border-color: #5bc0de; /* Brighter blue for focus */
            box-shadow: var(--focus-glow);
            color: var(--text-color-light);
        }

        .form-control::placeholder {
            color: rgba(255, 255, 255, 0.6);
        }

        /* Adjusting select options for dark background */
        .form-select option {
            background-color: #2c3e50; /* Dark background for options */
            color: var(--text-color-light);
        }
        .form-select option:checked {
            background-color: #1A2980; /* Highlight selected option */
            color: var(--text-color-light);
        }


        .btn-primary {
            background: var(--dark-blue-gradient);
            border: none;
            border-radius: 12px;
            padding: 15px 25px;
            font-weight: 600;
            font-size: 1.1rem;
            box-shadow: var(--box-shadow-light);
            transition: all 0.3s ease;
            color: var(--text-color-light);
        }

        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: var(--box-shadow-heavy);
            background: var(--dark-blue-gradient); /* Ensure gradient stays on hover */
        }

        .btn-secondary {
            background: var(--black-gradient);
            border: none;
            border-radius: 12px;
            padding: 12px 20px;
            font-weight: 500;
            color: var(--text-color-light);
            box-shadow: var(--box-shadow-light);
            transition: all 0.3s ease;
        }

        .btn-secondary:hover {
            transform: translateY(-2px);
            box-shadow: var(--box-shadow-heavy);
            background: var(--black-gradient); /* Ensure gradient stays on hover */
        }

        .alert {
            border-radius: 12px;
            padding: 15px 20px;
            font-weight: 500;
            margin-bottom: 25px;
            animation: slideInDown 0.5s ease-out forwards;
        }

        .alert-success {
            background: var(--green-gradient);
            border: 1px solid rgba(139, 195, 74, 0.5); /* Lighter green border */
            color: #fff;
            text-shadow: 0 1px 3px rgba(0,0,0,0.3);
        }

        .alert-danger {
            background: linear-gradient(135deg, #dc3545 0%, #e87e04 100%); /* Red to Orange */
            border: 1px solid rgba(220, 53, 69, 0.5);
            color: #fff;
            text-shadow: 0 1px 3px rgba(0,0,0,0.3);
        }

        @keyframes slideInDown {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Passer une nouvelle commande</h2>

        <?php if ($message_success): ?>
            <div class="alert alert-success text-center" role="alert">
                <i class="fas fa-check-circle me-2"></i><?php echo $message_success; ?>
            </div>
        <?php endif; ?>

        <?php if ($message_error): ?>
            <div class="alert alert-danger text-center" role="alert">
                <i class="fas fa-times-circle me-2"></i><?php echo $message_error; ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="commander.php">
            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="nom" class="form-label">Nom</label>
                    <input type="text" class="form-control" id="nom" name="nom" required>
                </div>
                <div class="col-md-6">
                    <label for="prenom" class="form-label">Prénom</label>
                    <input type="text" class="form-control" id="prenom" name="prenom" required>
                </div>
            </div>
            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" class="form-control" id="email" name="email" required>
                </div>
                <div class="col-md-6">
                    <label for="telephone" class="form-label">Téléphone</label>
                    <input type="tel" class="form-control" id="telephone" name="telephone" required>
                </div>
            </div>
            <div class="mb-3">
                <label for="adresse" class="form-label">Adresse de livraison</label>
                <textarea class="form-control" id="adresse" name="adresse" rows="3" required></textarea>
            </div>
            <div class="mb-3">
                <label for="type_commande" class="form-label">Type de commande</label>
                <select class="form-select" id="type_commande" name="type_commande" required>
                    <option value="">Choisissez un type</option>
                    <option value="livres">Livres</option>
                    <option value="viande">Viande</option>
                    <option value="legumes">Légumes</option>
                    <option value="fruits">Fruits</option>
                    <option value="objet personnel">Objet Personnel</option>
                    <option value="autres">Autres</option> </select>
            </div>
            <div class="mb-4"> <label for="demande_exacte" class="form-label">Demande exacte (Détaillez votre commande)</label>
                <textarea class="form-control" id="demande_exacte" name="demande_exacte" rows="5" required></textarea>
            </div>
            <button type="submit" class="btn btn-primary w-100 mb-3">
                <i class="fas fa-paper-plane me-2"></i>Envoyer la Commande
            </button>
            <div class="text-center">
                <a href="index.php" class="btn btn-secondary">
                    <i class="fas fa-home me-2"></i>Retour à l'accueil
                </a>
            </div>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>