<?php
session_start();

// Redirection si l'utilisateur n'est pas connecté
if (!isset($_SESSION['livreur_id'])) {
    header("Location: livreur_login.php");
    exit();
}

// Connexion à la base de données (avec les paramètres par défaut de XAMPP/WAMP)
require "connexion.php";

// Récupérer l'ID du livreur depuis la session
$livreur_id = $_SESSION['livreur_id'];

$livreur_message = '';

// Récupérer les informations du livreur (avec le prénom)
$stmt_livreur = $pdo->prepare("SELECT id, nom_livreur, prenom, statut FROM livreurs WHERE id = ?");
$stmt_livreur->execute([$livreur_id]);
$livreur = $stmt_livreur->fetch();

if (!$livreur) {
    // Si pour une raison quelconque le livreur n'est plus trouvé, déconnecter
    session_destroy();
    header("Location: livreur_login.php");
    exit();
}

// Traitement de la mise à jour du statut du livreur (manuelle)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['new_statut'])) {
    $new_statut = $_POST['new_statut'];
    try {
        $stmt_update = $pdo->prepare("UPDATE livreurs SET statut = ? WHERE id = ?");
        $stmt_update->execute([$new_statut, $livreur_id]);
        $livreur['statut'] = $new_statut; // Mettre à jour le statut dans l'objet local
        $livreur_message = "<div class='alert alert-success'><i class='fas fa-check-circle me-2'></i>Votre statut a été mis à jour à " . htmlspecialchars(ucfirst($new_statut)) . ".</div>";
    } catch (\PDOException $e) {
        $livreur_message = "<div class='alert alert-danger'><i class='fas fa-times-circle me-2'></i>Erreur lors de la mise à jour du statut: " . $e->getMessage() . "</div>";
    }
}

// Récupérer les commandes assignées à ce livreur
$stmt_commandes_livreur = $pdo->prepare("
    SELECT
        c.id AS commande_id,
        cl.nom AS client_nom,
        cl.prenom AS client_prenom,
        cl.telephone AS client_tel,
        cl.adresse AS client_adresse,
        c.type_commande,
        c.demande_exacte,
        c.statut AS commande_statut,
        c.date_commande
    FROM commandes c
    JOIN clients cl ON c.client_id = cl.id
    WHERE c.livreur_id = ?
    ORDER BY c.date_commande DESC
");
$stmt_commandes_livreur->execute([$livreur_id]);
$commandes_livreur = $stmt_commandes_livreur->fetchAll();

// Traitement de la mise à jour du statut d'une commande par le livreur
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action_commande_livreur']) && $_POST['action_commande_livreur'] == 'update_statut_commande') {
    $commande_id_to_update = $_POST['commande_id_to_update'] ?? '';
    $new_commande_statut = $_POST['new_commande_statut'] ?? '';

    if (!empty($commande_id_to_update) && !empty($new_commande_statut)) {
        try {
            $pdo->beginTransaction();

            $stmt_update_commande_livreur = $pdo->prepare("UPDATE commandes SET statut = ? WHERE id = ? AND livreur_id = ?");
            $stmt_update_commande_livreur->execute([$new_commande_statut, $commande_id_to_update, $livreur_id]);

            // NOUVELLE LOGIQUE: Mise à jour automatique du statut du livreur
            if ($new_commande_statut == 'terminee') {
                // Si la commande est "terminée", remettre le statut du livreur à "actif"
                $stmt_set_livreur_actif = $pdo->prepare("UPDATE livreurs SET statut = 'actif' WHERE id = ?");
                $stmt_set_livreur_actif->execute([$livreur_id]);
                $livreur['statut'] = 'actif'; // Mettre à jour le statut dans l'objet local
            } elseif ($new_commande_statut == 'affectee' || $new_commande_statut == 'en cours') {
                // Si la commande est "affectée" ou "en cours", mettre le statut du livreur à "inactif"
                $stmt_set_livreur_inactif = $pdo->prepare("UPDATE livreurs SET statut = 'inactif' WHERE id = ?");
                $stmt_set_livreur_inactif->execute([$livreur_id]);
                $livreur['statut'] = 'inactif'; // Mettre à jour le statut dans l'objet local
            }
            $pdo->commit();
            // Rediriger pour rafraîchir la page et montrer les changements
            header("Location: livreur.php");
            exit();
        } catch (\PDOException $e) {
            $pdo->rollBack();
            $livreur_message = "<div class='alert alert-danger'><i class='fas fa-times-circle me-2'></i>Erreur lors de la mise à jour de la commande: " . $e->getMessage() . "</div>";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel Livreur - <?php echo htmlspecialchars($livreur['nom_livreur'] . ' ' . $livreur['prenom']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /* Votre CSS existant ici */
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
            --button-gradient-success: linear-gradient(45deg, #2ecc71, #27ae60);
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
            padding: 50px 20px;
            overflow-x: hidden;
            background-size: 200% 200%; /* Pour une animation subtile */
            animation: gradientBackground 15s ease infinite alternate;
        }

        @keyframes gradientBackground {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        .container {
            background: var(--card-bg);
            backdrop-filter: blur(20px); /* Flou plus prononcé */
            border: 1px solid var(--card-border);
            padding: 50px;
            border-radius: 30px; /* Plus arrondi */
            box-shadow: var(--shadow-heavy);
            max-width: 1400px;
            width: 100%;
            animation: fadeInSlideUp 0.9s ease-out forwards;
            position: relative;
            z-index: 1; /* S'assurer qu'il est au-dessus de l'arrière-plan */
        }

        @keyframes fadeInSlideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        h1, h2 {
            color: var(--heading-color);
            text-shadow: 0 4px 12px rgba(0, 0, 0, 0.6);
            font-weight: 700;
            margin-bottom: 40px;
            text-align: center;
            letter-spacing: 1.5px;
            font-size: 2.8rem;
        }
        h2 {
            font-size: 2.2rem;
        }
        h1 i, h2 i {
            color: var(--primary-accent);
            font-size: 0.8em;
            vertical-align: middle;
            margin-right: 15px;
        }

        hr {
            border-top: 1px solid rgba(255, 255, 255, 0.3);
            margin-bottom: 50px;
        }

        .card {
            background: rgba(0, 0, 0, 0.15); /* Arrière-plan de carte légèrement plus foncé pour le contraste */
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 25px;
            box-shadow: var(--shadow-medium);
            margin-bottom: 50px;
            overflow: hidden;
            transition: all 0.4s cubic-bezier(0.25, 0.8, 0.25, 1);
        }
        .card:hover {
            box-shadow: var(--shadow-heavy);
            transform: translateY(-8px);
            background: rgba(0, 0, 0, 0.25);
        }

        .card-header {
            background: rgba(0, 0, 0, 0.4);
            color: var(--primary-accent);
            font-weight: 600;
            padding: 20px 30px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.15);
            font-size: 1.3rem;
            display: flex;
            align-items: center;
            gap: 15px;
        }
        .card-header i {
            color: var(--text-color);
            font-size: 1.1em;
        }

        .card-body {
            padding: 35px 30px;
        }

        .status-form {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 30px;
            justify-content: center;
        }
        .status-form p {
            margin-bottom: 0;
            font-size: 1.25rem;
            font-weight: 500;
            display: flex;
            align-items: center;
            color: var(--heading-color);
        }
        .status-form .badge {
            font-size: 1.3em;
            padding: 0.8em 1.4em;
            border-radius: 15px;
            min-width: 120px;
            text-align: center;
            transition: all 0.4s ease-in-out;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.4);
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .status-form .badge.bg-success {
            background: var(--button-gradient-success) !important;
        }
        .status-form .badge.bg-danger {
            background: var(--button-gradient-danger) !important;
        }

        .form-select,
        .form-control {
            background-color: var(--input-bg);
            border: 1px solid var(--input-border);
            color: var(--text-color);
            border-radius: 15px;
            padding: 16px 20px;
            transition: all 0.3s ease;
            appearance: none;
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3e%3cpath fill='none' stroke='%23a0a0a0' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M2 5l6 6 6-6'/%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right 1.2rem center;
            background-size: 20px 16px;
        }
        .form-select:hover, .form-control:hover {
            border-color: rgba(255, 255, 255, 0.3);
        }
        .form-select:focus,
        .form-control:focus {
            background-color: rgba(0, 0, 0, 0.4);
            border-color: var(--primary-accent);
            box-shadow: var(--focus-glow);
            color: var(--text-color);
            outline: 0;
        }

        .form-select option {
            background-color: var(--bg-gradient-start); /* Arrière-plan foncé pour les options */
            color: var(--text-color);
            padding: 12px;
        }
        .form-select option:checked {
            background-color: var(--bg-gradient-end);
            color: #fff;
        }

        .btn-primary {
            background: var(--button-gradient-primary);
            border: none;
            border-radius: 20px;
            padding: 16px 35px;
            font-weight: 600;
            font-size: 1.15rem;
            box-shadow: var(--shadow-light);
            transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }
        .btn-primary:hover {
            transform: translateY(-6px) scale(1.04);
            box-shadow: var(--shadow-heavy);
            background: linear-gradient(45deg, #55bdff, #2299ff); /* Plus lumineux au survol */
        }
        .btn-primary:active {
            transform: translateY(0);
            box-shadow: var(--shadow-light);
        }

        .alert {
            border-radius: 20px;
            padding: 25px 35px;
            font-weight: 500;
            margin-bottom: 50px;
            display: flex;
            align-items: center;
            gap: 15px;
            animation: slideInDown 0.7s cubic-bezier(0.25, 0.8, 0.25, 1) forwards;
            color: #fff;
            text-shadow: 0 1px 5px rgba(0,0,0,0.5);
            border: none;
            box-shadow: var(--shadow-medium);
            font-size: 1.15rem;
        }

        .alert-success {
            background: var(--button-gradient-success);
        }
        .alert-danger {
            background: var(--button-gradient-danger);
        }
        .alert-info {
            background: linear-gradient(45deg, #3498db, #2980b9); /* Couleur info */
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

        /* ****** Ajustements du tableau pour assurer la couleur ***** */
        .table,
        .table tbody,
        .table tbody tr,
        .table tbody tr td {
            background-color: rgba(0, 0, 0, 0.4) !important; /* La couleur demandée avec !important */
            color: var(--text-color); /* Assurer la couleur du texte */
        }

        .table thead {
            background: rgba(0, 0, 0, 0.5) !important; /* Arrière-plan de l'en-tête du tableau légèrement plus foncé */
        }
        .table th {
            border-color: rgba(0, 0, 0, 0.4) !important;
            color: var(--primary-accent); /* Couleur du texte dans l'en-tête du tableau */
            font-weight: 600;
            padding: 20px 18px;
            text-align: center;
            vertical-align: middle;
            font-size: 1.05rem;
            letter-spacing: 0.5px;
        }
        .table td {
            border-color: rgba(255, 255, 255, 0.15);
            vertical-align: middle;
            padding: 18px;
            text-align: center;
            font-size: 0.95rem;
        }

        .table-striped tbody tr:nth-of-type(odd) {
            background-color: rgba(0, 0, 0, 0.1) !important; /* Couleur des lignes impaires avec !important */
        }
        .table-striped tbody tr:hover {
            background-color: rgba(0, 0, 0, 0.35) !important; /* Couleur des lignes au survol avec !important */
            transition: background-color 0.3s ease;
        }

        .table-bordered th, .table-bordered td {
            border: 1px solid rgba(255, 255, 255, 0.25) !important;
        }

        /* Couleurs des badges pour le statut de la commande */
        .badge {
            padding: 0.7em 1.2em;
            border-radius: 0.8em;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 3px 10px rgba(0,0,0,0.3);
            text-transform: uppercase;
            letter-spacing: 0.7px;
            min-width: 90px;
        }
        .badge.bg-success { background: var(--button-gradient-success) !important; } /* Terminée */
        .badge.bg-info { background: linear-gradient(45deg, #3498db, #2980b9) !important; }    /* En cours */
        .badge.bg-danger { background: var(--button-gradient-danger) !important; }  /* Annulée */
        .badge.bg-warning {
            background: var(--warning-color) !important; /* Or ambré */
            color: #333 !important; /* Texte plus foncé pour le badge d'avertissement */
        }

        .client-phone-link {
            color: var(--primary-accent); /* Couleur du lien assortie à la couleur d'accentuation */
            text-decoration: none;
            transition: color 0.2s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        .client-phone-link:hover {
            color: var(--heading-color); /* Plus clair au survol */
            text-decoration: underline;
        }

        .logout-btn {
            position: absolute;
            top: 30px;
            right: 30px;
            background: linear-gradient(45deg, #e74c3c, #c0392b);
            color: white;
            border: none;
            border-radius: 15px;
            padding: 12px 25px;
            font-weight: 600;
            box-shadow: 0 5px 15px rgba(0,0,0,0.3);
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
        }
        .logout-btn:hover {
            background: linear-gradient(45deg, #c0392b, #a93226);
            transform: translateY(-3px) scale(1.02);
            box-shadow: 0 8px 20px rgba(0,0,0,0.4);
            color: white;
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="livreur_logout.php" class="logout-btn">
            <i class="fas fa-sign-out-alt"></i> Déconnexion
        </a>
        <h1 class="mb-4 text-center">
            <i class="fas fa-motorcycle"></i> Bienvenue, Livreur <?php echo htmlspecialchars($livreur['nom_livreur'] . ' ' . $livreur['prenom']); ?>!
        </h1>
        <hr>

        <?php echo $livreur_message; ?>

        <div class="card mb-4">
            <div class="card-header">
                <i class="fas fa-user-circle"></i> Votre Statut Actuel
            </div>
            <div class="card-body">
                <div class="status-form">
                    <p class="card-text mb-0">Statut:
                        <span class="badge <?php echo ($livreur['statut'] == 'actif') ? 'bg-success' : 'bg-danger'; ?>">
                            <?php echo htmlspecialchars(ucfirst($livreur['statut'])); ?>
                        </span>
                    </p>
                    <form method="POST" action="livreur.php" class="d-flex align-items-center flex-wrap gap-3">
                        <label for="new_statut" class="form-label mb-0 visually-hidden">Changer le statut</label>
                        <select name="new_statut" id="new_statut" class="form-select flex-grow-1">
                            <option value="actif" <?php echo ($livreur['statut'] == 'actif') ? 'selected' : ''; ?>>Actif</option>
                            <option value="inactif" <?php echo ($livreur['statut'] == 'inactif') ? 'selected' : ''; ?>>Inactif</option>
                        </select>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-sync-alt"></i> Mettre à jour le statut
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <h2 class="mb-3">
            <i class="fas fa-boxes"></i> Vos Commandes Assignées
        </h2>
        <?php if (empty($commandes_livreur)): ?>
            <div class="alert alert-info text-center" role="alert">
                <i class="fas fa-info-circle"></i> Vous n'avez pas de commandes assignées pour le moment.
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-bordered table-striped">
                    <thead>
                        <tr>

                            <th>Client</th>
                            <th>Téléphone</th>
                            <th>Adresse</th>
                            <th>Type</th>
                            <th>Détails</th>
                            <th>Statut</th>
                            <th>Date</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($commandes_livreur as $commande): ?>
                        <tr>

                            <td><?php echo htmlspecialchars($commande['client_nom'] . ' ' . $commande['client_prenom']); ?></td>
                            <td>
                                <a href="tel:<?php echo htmlspecialchars($commande['client_tel']); ?>" class="client-phone-link">
                                    <i class="fas fa-phone-alt"></i><?php echo htmlspecialchars($commande['client_tel']); ?>
                                </a>
                            </td>
                            <td><?php echo htmlspecialchars($commande['client_adresse']); ?></td>
                            <td><?php echo htmlspecialchars(ucfirst($commande['type_commande'])); ?></td>
                            <td><?php echo nl2br(htmlspecialchars($commande['demande_exacte'])); ?></td>
                            <td>
                                <span class="badge <?php
                                    if ($commande['commande_statut'] == 'terminee') echo 'bg-success';
                                    else if ($commande['commande_statut'] == 'en cours') echo 'bg-info';
                                    else if ($commande['commande_statut'] == 'annulee') echo 'bg-danger';
                                    else if ($commande['commande_statut'] == 'affectee') echo 'bg-info'; // Ajouté pour affectee si vous voulez une couleur spécifique, sinon elle sera warning par défaut
                                    else echo 'bg-warning';
                                ?>">
                                    <?php echo htmlspecialchars(ucfirst($commande['commande_statut'])); ?>
                                </span>
                            </td>
                            <td><?php echo htmlspecialchars($commande['date_commande']); ?></td>
                            <td>
                                <form method="POST" action="livreur.php">
                                    <input type="hidden" name="action_commande_livreur" value="update_statut_commande">
                                    <input type="hidden" name="commande_id_to_update" value="<?php echo $commande['commande_id']; ?>">
                                    <select name="new_commande_statut" class="form-select form-select-sm" onchange="this.form.submit()">
                                        <option value="en attente" <?php echo ($commande['commande_statut'] == 'en attente') ? 'selected' : ''; ?>>En attente</option>
                                        <option value="affectee" <?php echo ($commande['commande_statut'] == 'affectee') ? 'selected' : ''; ?>>Affectée</option>
                                        <option value="en cours" <?php echo ($commande['commande_statut'] == 'en cours') ? 'selected' : ''; ?>>En cours</option>
                                        <option value="terminee" <?php echo ($commande['commande_statut'] == 'terminee') ? 'selected' : ''; ?>>Terminée</option>
                                        <option value="annulee" <?php echo ($commande['commande_statut'] == 'annulee') ? 'selected' : ''; ?>>Annulée</option>
                                    </select>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>