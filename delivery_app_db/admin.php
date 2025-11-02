<?php
session_start();
// Connexion à la base de données (avec les paramètres par défaut de XAMPP/WAMP)
require "connexion.php";

// Protection simple pour l'admin
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: admin_login.php');
    exit();
}


// Récupérer les informations des livreurs avec les nouvelles colonnes (pour le filtre)
$stmt_livreurs_for_filter = $pdo->query("SELECT id, nom_livreur, prenom, statut FROM livreurs WHERE deleted_at IS NULL ORDER BY nom_livreur ASC"); // Only active drivers
$livreurs_for_filter = $stmt_livreurs_for_filter->fetchAll();

// Récupérer toutes les commandes avec les infos client et livreur
// Préparation des conditions de filtre
$where_clauses = [];
$params = [];

if (isset($_GET['filter_date']) && !empty($_GET['filter_date'])) {
    $filter_date = $_GET['filter_date'];
    // On veut les commandes de la date exacte
    $where_clauses[] = "DATE(c.date_commande) = ?";
    $params[] = $filter_date;
}

if (isset($_GET['filter_livreur']) && !empty($_GET['filter_livreur'])) {
    $filter_livreur = $_GET['filter_livreur'];
    $where_clauses[] = "c.livreur_id = ?";
    $params[] = $filter_livreur;
}

$sql_commandes = "
    SELECT
        c.id AS commande_id,
        cl.nom AS client_nom,
        cl.prenom AS client_prenom,
        cl.telephone AS client_tel,
        cl.adresse AS client_adresse,
        c.type_commande,
        c.demande_exacte,
        l.nom_livreur,
        l.prenom AS livreur_prenom,
        l.statut AS livreur_statut,
        c.statut AS commande_statut,
        c.date_commande,
        c.livreur_id
    FROM commandes c
    JOIN clients cl ON c.client_id = cl.id
    LEFT JOIN livreurs l ON c.livreur_id = l.id
";

if (!empty($where_clauses)) {
    $sql_commandes .= " WHERE " . implode(" AND ", $where_clauses);
}

$sql_commandes .= " ORDER BY c.date_commande DESC";

$stmt_commandes = $pdo->prepare($sql_commandes);
$stmt_commandes->execute($params);
$commandes = $stmt_commandes->fetchAll();

// Récupérer les informations des livreurs pour la gestion (tableau Livreurs) - Seulement les non supprimés
$stmt_livreurs = $pdo->query("SELECT id, nom_livreur, prenom, cin, email, telephone, situation_familiale, statut, date_embauche, age FROM livreurs WHERE deleted_at IS NULL ORDER BY nom_livreur ASC");
$livreurs = $stmt_livreurs->fetchAll();

// Récupérer les livreurs supprimés pour la nouvelle section
$stmt_archived_livreurs = $pdo->query("SELECT id, nom_livreur, prenom, cin, email, telephone, situation_familiale, statut, date_embauche, age, deleted_at FROM livreurs WHERE deleted_at IS NOT NULL ORDER BY deleted_at DESC");
$archived_livreurs = $stmt_archived_livreurs->fetchAll();

// Gérer l'ajout d'un admin inférieur
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action_admin'])) {
    if ($_POST['action_admin'] == 'add') {
        $nom = $_POST['nom_admin_add'] ?? '';
        $prenom = $_POST['prenom_admin_add'] ?? '';
        $email = $_POST['email_admin_add'] ?? '';
        $cin = $_POST['cin_admin_add'] ?? '';
        $date_embauche = $_POST['date_embauche_admin_add'] ?? date('Y-m-d');
        
        // Gestion de l'upload de la photo
        $photo_name = null;
        if (isset($_FILES['photo_admin_add']) && $_FILES['photo_admin_add']['error'] == UPLOAD_ERR_OK) {
            $upload_dir = 'images/';
            $file_tmp = $_FILES['photo_admin_add']['tmp_name'];
            $file_ext = strtolower(pathinfo($_FILES['photo_admin_add']['name'], PATHINFO_EXTENSION));
            $photo_name = uniqid('admin_', true) . '.' . $file_ext;
            move_uploaded_file($file_tmp, $upload_dir . $photo_name);
        }

        if (!empty($nom) && !empty($prenom) && !empty($email) && !empty($cin)) {
            try {
                $stmt_add_admin = $pdo->prepare("INSERT INTO admins_inf (nom, prenom, photo, email, cin, date_embauche) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt_add_admin->execute([
                    $nom,
                    $prenom,
                    $photo_name,
                    $email,
                    $cin,
                    $date_embauche
                ]);
                
                header('Location: admin.php#admins-inf-section');
                exit();
            } catch (\PDOException $e) {
                echo "<div class='alert alert-danger'>Erreur d'ajout de l'admin: " . $e->getMessage() . "</div>";
            }
        } else {
            echo "<div class='alert alert-danger'>Tous les champs obligatoires doivent être remplis.</div>";
        }
    }
}

// Gérer la mise à jour du statut d'une commande
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'update_commande_statut') {
    $commande_id = $_POST['commande_id'] ?? '';
    $new_statut = $_POST['new_statut'] ?? '';

    if (!empty($commande_id) && !empty($new_statut)) {
        try {
            $pdo->beginTransaction(); // Début de la transaction

            // 1. Mettre à jour le statut de la commande
            $stmt_update_commande = $pdo->prepare("UPDATE commandes SET statut = ? WHERE id = ?");
            $stmt_update_commande->execute([$new_statut, $commande_id]);

            // 2. Récupérer le livreur_id de la commande mise à jour
            $stmt_get_livreur_id = $pdo->prepare("SELECT livreur_id FROM commandes WHERE id = ?");
            $stmt_get_livreur_id->execute([$commande_id]);
            $livreur_data = $stmt_get_livreur_id->fetch();

            $livreur_id_associated = $livreur_data['livreur_id'] ?? null;

            if ($livreur_id_associated) {
                // 3. Mettre à jour le statut du livreur en fonction du statut de la commande
                $new_livreur_statut = null;
                if ($new_statut == 'terminee') {
                    $new_livreur_statut = 'actif';
                } elseif ($new_statut == 'affectee' || $new_statut == 'en cours') {
                    $new_livreur_statut = 'inactif';
                }

                if ($new_livreur_statut !== null) {
                    $stmt_update_livreur_statut = $pdo->prepare("UPDATE livreurs SET statut = ? WHERE id = ?");
                    $stmt_update_livreur_statut->execute([$new_livreur_statut, $livreur_id_associated]);
                }
            }

            $pdo->commit(); // Commit la transaction
            header('Location: admin.php#commandes-section'); // Rediriger vers la section des commandes
            exit();
        } catch (\PDOException $e) {
            $pdo->rollBack(); // Annuler la transaction en cas d'erreur
            echo "<div class='alert alert-danger'>Erreur de mise à jour du statut de la commande ou du livreur: " . $e->getMessage() . "</div>";
        }
    }
}

// Gérer la suppression d'une commande
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'delete_commande') {
    $commande_id = $_POST['commande_id_delete'] ?? '';

    if (!empty($commande_id)) {
        try {
            $stmt_delete_commande = $pdo->prepare("DELETE FROM commandes WHERE id = ?");
            $stmt_delete_commande->execute([$commande_id]);
            header('Location: admin.php#commandes-section'); // Rediriger vers la section des commandes
            exit();
        } catch (\PDOException $e) {
            echo "<div class='alert alert-danger'>Erreur de suppression de la commande: " . $e->getMessage() . "<br>Vérifiez les contraintes de clé étrangère dans votre base de données si le problème persiste.</div>";
        }
    }
}

// Gérer l'ajout/suppression/modification des livreurs
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action_livreur'])) {
    if ($_POST['action_livreur'] == 'add') {
        $nom_livreur = $_POST['nom_livreur_add'] ?? '';
        $prenom_livreur = $_POST['prenom_livreur_add'] ?? '';
        $cin_livreur = $_POST['cin_livreur_add'] ?? '';
        $tele_livreur = $_POST['tele_livreur_add'] ?? '';
        $email_livreur = $_POST['email_livreur_add'] ?? '';
        $situation_familiale_livreur = $_POST['situation_familiale_livreur_add'] ?? '';
        $date_embauche_livreur = $_POST['date_embauche_livreur_add'] ?? null;
        $age_livreur = $_POST['age_livreur_add'] ?? null;

        if (!empty($nom_livreur) && !empty($prenom_livreur) && !empty($tele_livreur)) {
            try {
                $stmt_add_livreur = $pdo->prepare("INSERT INTO livreurs (nom_livreur, prenom, cin, email, telephone, situation_familiale, statut, date_embauche, age) VALUES (?, ?, ?, ?, ?, ?, 'inactif', ?, ?)");
                $stmt_add_livreur->execute([
                    $nom_livreur,
                    $prenom_livreur,
                    $cin_livreur,
                    $email_livreur,
                    $tele_livreur,
                    $situation_familiale_livreur,
                    $date_embauche_livreur,
                    $age_livreur
                ]);
                header('Location: admin.php#livreurs-section');
                exit();
            } catch (\PDOException $e) {
                echo "<div class='alert alert-danger'>Erreur d'ajout du livreur: " . $e->getMessage() . "</div>";
            }
        }
    } elseif ($_POST['action_livreur'] == 'update_statut') {
        $livreur_id = $_POST['livreur_id_update'] ?? '';
        $new_statut = $_POST['new_statut_livreur'] ?? '';
        if (!empty($livreur_id) && !empty($new_statut)) {
            try {
                $stmt_update_livreur = $pdo->prepare("UPDATE livreurs SET statut = ? WHERE id = ?");
                $stmt_update_livreur->execute([$new_statut, $livreur_id]);
                header('Location: admin.php#livreurs-section');
                exit();
            } catch (\PDOException $e) {
                echo "<div class='alert alert-danger'>Erreur de mise à jour du statut du livreur: " . $e->getMessage() . "</div>";
            }
        }
    } elseif ($_POST['action_livreur'] == 'soft_delete') {
        $livreur_id = $_POST['livreur_id_delete'] ?? '';
        if (!empty($livreur_id)) {
            try {
                $pdo->beginTransaction();
                $stmt_soft_delete_livreur = $pdo->prepare("UPDATE livreurs SET deleted_at = NOW() WHERE id = ?");
                $stmt_soft_delete_livreur->execute([$livreur_id]);

                $stmt_update_commandes = $pdo->prepare("UPDATE commandes SET livreur_id = NULL WHERE livreur_id = ?");
                $stmt_update_commandes->execute([$livreur_id]);

                $pdo->commit();
                header('Location: admin.php#livreurs-section');
                exit();
            } catch (\PDOException $e) {
                $pdo->rollBack();
                echo "<div class='alert alert-danger'>Erreur de suppression du livreur: " . $e->getMessage() . "</div>";
            }
        }
    } elseif ($_POST['action_livreur'] == 'restore') {
        $livreur_id = $_POST['livreur_id_restore'] ?? '';
        if (!empty($livreur_id)) {
            try {
                $stmt_restore_livreur = $pdo->prepare("UPDATE livreurs SET deleted_at = NULL WHERE id = ?");
                $stmt_restore_livreur->execute([$livreur_id]);
                header('Location: admin.php#archived-livreurs-section');
                exit();
            } catch (\PDOException $e) {
                echo "<div class='alert alert-danger'>Erreur de restauration du livreur: " . $e->getMessage() . "</div>";
            }
        }
    }
}

// Calculer les statistiques des commandes pour les cards
$commandes_en_attente = array_filter($commandes, function($c) {
    return $c['commande_statut'] == 'en attente';
});
$commandes_affectees = array_filter($commandes, function($c) {
    return $c['commande_statut'] == 'affectee';
});
$commandes_en_cours = array_filter($commandes, function($c) {
    return $c['commande_statut'] == 'en cours';
});
$commandes_terminees = array_filter($commandes, function($c) {
    return $c['commande_statut'] == 'terminee';
});
$commandes_annulees = array_filter($commandes, function($c) {
    return $c['commande_statut'] == 'annulee';
});

// Livreurs actifs pour la card (excluding soft-deleted)
$livreurs_actifs_count = array_filter($livreurs, function($l) {
    return $l['statut'] == 'actif';
});

// Statistiques des commandes par statut pour le mois en cours (pour le Chart)
$commandes_statuts_mois = [];
try {
    $stmt_statuts_mois = $pdo->prepare("
        SELECT statut, COUNT(id) AS count
        FROM commandes
        WHERE MONTH(date_commande) = MONTH(CURRENT_DATE())
          AND YEAR(date_commande) = YEAR(CURRENT_DATE())
        GROUP BY statut
    ");
    $stmt_statuts_mois->execute();
    $results = $stmt_statuts_mois->fetchAll();

    foreach ($results as $row) {
        $commandes_statuts_mois[$row['statut']] = $row['count'];
    }
} catch (\PDOException $e) {
    error_log("Erreur lors de la récupération des statuts de commande par mois: " . $e->getMessage());
    $commandes_statuts_mois = [];
}

// Assurer que tous les statuts possibles existent, même avec 0
$all_statuts = ['en attente', 'affectee', 'en cours', 'terminee', 'annulee'];
$final_commandes_statuts_mois = [];
foreach ($all_statuts as $statut) {
    $final_commandes_statuts_mois[$statut] = $commandes_statuts_mois[$statut] ?? 0;
}

// Convertir le tableau PHP en JSON pour le passer à JavaScript
$chart_data_json = json_encode($final_commandes_statuts_mois);

// LOGIQUE DE CALCUL DES SALAIRES
$base_salary = 6000;
$all_livreurs_for_salary_calc = array_merge($livreurs, $archived_livreurs);
foreach ($all_livreurs_for_salary_calc as &$livreur) {
    $augmentation_percentage = 0;
    $years_of_service = 0;

    if (!empty($livreur['date_embauche'])) {
        $date_embauche = new DateTime($livreur['date_embauche']);
        $now = new DateTime();
        $interval = $now->diff($date_embauche);
        $years_of_service = $interval->y;

        if ($years_of_service >= 15) {
            $augmentation_percentage = 0.12;
        } elseif ($years_of_service >= 10) {
            $augmentation_percentage = 0.08;
        } elseif ($years_of_service >= 5) {
            $augmentation_percentage = 0.05;
        } elseif ($years_of_service >= 2) {
            $augmentation_percentage = 0.02;
        }
    }
    $livreur['augmentation_percentage'] = $augmentation_percentage * 100;
    $livreur['years_of_service'] = $years_of_service;
    $livreur['calculated_salary'] = $base_salary * (1 + $augmentation_percentage);
}
unset($livreur);

// LOGIQUE POUR LE NOUVEAU GRAPHIQUE DES LIVREURS ACTIFS/INACTIFS
$livreurs_statut_data = [
    'actif' => 0,
    'inactif' => 0
];

foreach ($livreurs as $livreur_status) {
    if ($livreur_status['statut'] == 'actif') {
        $livreurs_statut_data['actif']++;
    } else {
        $livreurs_statut_data['inactif']++;
    }
}
$livreurs_statut_json = json_encode($livreurs_statut_data);

// LOGIQUE POUR LE NOUVEAU GRAPHIQUE D'AUGMENTATION DE SALAIRE
$salary_augmentation_data = [
    'no_augmentation' => 0,
    '2_percent' => 0,
    '5_percent' => 0,
    '8_percent' => 0,
    '12_percent' => 0
];

foreach ($all_livreurs_for_salary_calc as $livreur_salary) {
    if ($livreur_salary['augmentation_percentage'] == 0) {
        $salary_augmentation_data['no_augmentation']++;
    } elseif ($livreur_salary['augmentation_percentage'] == 2) {
        $salary_augmentation_data['2_percent']++;
    } elseif ($livreur_salary['augmentation_percentage'] == 5) {
        $salary_augmentation_data['5_percent']++;
    } elseif ($livreur_salary['augmentation_percentage'] == 8) {
        $salary_augmentation_data['8_percent']++;
    } elseif ($livreur_salary['augmentation_percentage'] == 12) {
        $salary_augmentation_data['12_percent']++;
    }
}
$salary_augmentation_json = json_encode($salary_augmentation_data);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - Gestion des Livraisons</title>
    <!-- <link rel="stylesheet" href="styleadmin.css"> -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --secondary-gradient: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            --success-gradient: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            --warning-gradient: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
            --danger-gradient: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
            --dark-gradient: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
            --glass-bg: rgba(255, 255, 255, 0.1);
            --glass-border: rgba(255, 255, 255, 0.2);
            --text-shadow: 0 2px 4px rgba(0,0,0,0.3);
            --box-shadow: 0 8px 32px rgba(0,0,0,0.1);
            --box-shadow-hover: 0 16px 64px rgba(0,0,0,0.2);
        }

        * {
            box-sizing: border-box;
        }

        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 50%, #f093fb 100%);
            background-attachment: fixed;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            min-height: 100vh;
            overflow-x: hidden;
        }

        .navbar-admin {
            background: var(--glass-bg);
            backdrop-filter: blur(20px);
            border-bottom: 1px solid var(--glass-border);
            box-shadow: var(--box-shadow);
        }

        .navbar-brand {
            font-weight: 700;
            font-size: 1.5rem;
            text-shadow: var(--text-shadow);
            background: linear-gradient(45deg, #fff, #f0f0f0);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .sidebar {
            height: 100vh;
            width: 280px;
            position: fixed;
            top: 0;
            left: 0;
            background: var(--glass-bg);
            backdrop-filter: blur(20px);
            padding-top: 80px;
            border-right: 1px solid var(--glass-border);
            box-shadow: var(--box-shadow);
            z-index: 1000;
            transition: transform 0.3s ease-in-out;
            transform: translateX(-100%);
        }

        .sidebar.show {
            transform: translateX(0%);
        }

        .sidebar .nav-link {
            color: rgba(255, 255, 255, 0.9);
            padding: 18px 25px;
            margin: 8px 15px;
            border-radius: 15px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            font-weight: 500;
            text-shadow: var(--text-shadow);
            border: 1px solid transparent;
        }

        .sidebar .nav-link:hover {
            background: var(--glass-bg);
            border: 1px solid var(--glass-border);
            transform: translateX(10px);
            box-shadow: var(--box-shadow);
        }

        .sidebar .nav-link.active {
            background: linear-gradient(135deg, rgba(255,255,255,0.2), rgba(255,255,255,0.1));
            border: 1px solid rgba(255,255,255,0.3);
            transform: translateX(10px);
            box-shadow: var(--box-shadow);
        }

        .content {
            margin-left: 0;
            padding: 20px;
            transition: margin-left 0.3s ease-in-out;
        }

        body.sidebar-open .content {
            margin-left: 280px;
        }

        .card-dashboard {
            background: var(--glass-bg);
            backdrop-filter: blur(20px);
            border: 1px solid var(--glass-border);
            border-radius: 20px;
            box-shadow: var(--box-shadow);
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            overflow: hidden;
            animation: fadeInUp 0.8s ease-out;
        }

        .card-dashboard.text-white.bg-primary {
            background: var(--primary-gradient) !important;
            border: none;
        }

        .card-dashboard.text-white.bg-success {
            background: var(--success-gradient) !important;
            border: none;
        }

        .card-dashboard.text-white.bg-warning {
            background: var(--warning-gradient) !important;
            border: none;
            color: #2c3e50 !important;
        }

        .card-dashboard.text-white.bg-info-dark {
            background: linear-gradient(135deg, #209cff 0%, #68e0cf 100%) !important;
            border: none;
        }

        .card-dashboard.text-white.bg-orange {
            background: linear-gradient(135deg, #ffc480 0%, #ff7e5f 100%) !important;
            border: none;
        }

        .card-dashboard.text-white.bg-purple {
            background: linear-gradient(135deg, #8A2387 0%, #E94057 100%) !important;
            border: none;
        }

        .card-dashboard.text-white.bg-teal {
            background: linear-gradient(135deg, #00C9FF 0%, #92FE9D 100%) !important;
            border: none;
        }

        .card-dashboard .card-body {
            padding: 2rem;
        }

        .card-dashboard .card-title {
            font-weight: 600;
            text-shadow: var(--text-shadow);
            margin-bottom: 1rem;
        }

        .card-dashboard .display-4 {
            font-weight: 700;
            text-shadow: var(--text-shadow);
        }

        .card-header-dashboard {
            background: var(--primary-gradient);
            color: white;
            border-bottom: none;
            border-radius: 20px 20px 0 0;
            padding: 1.5rem;
            font-weight: 600;
            text-shadow: var(--text-shadow);
        }

        .btn-add-livreur {
            background: var(--success-gradient);
            border: none;
            border-radius: 15px;
            padding: 12px 25px;
            font-weight: 600;
            text-shadow: var(--text-shadow);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: var(--box-shadow);
        }

        .btn-add-livreur:hover {
            transform: translateY(-3px);
            box-shadow: var(--box-shadow-hover);
            background: var(--success-gradient);
        }

        .table-responsive {
            border-radius: 20px;
            overflow: hidden;
            animation: fadeInUp 1s ease-out;
        }

        .table {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            margin-bottom: 0;
        }

        .table thead th {
            background: var(--primary-gradient);
            color: white;
            border: none;
            padding: 1rem;
            font-weight: 600;
            text-shadow: var(--text-shadow);
        }

        .table tbody tr {
            transition: all 0.3s ease;
            border-bottom: 1px solid rgba(0,0,0,0.1);
        }

        .table tbody tr:hover {
            background: rgba(102, 126, 234, 0.1);
            transform: scale(1.01);
        }

        .table tbody td {
            padding: 1rem;
            vertical-align: middle;
            border: none;
        }

        .badge {
            padding: 8px 12px;
            border-radius: 10px;
            font-weight: 500;
            text-shadow: var(--text-shadow);
        }

        .badge.bg-success {
            background: var(--success-gradient) !important;
        }

        .badge.bg-danger {
            background: var(--secondary-gradient) !important;
        }

        .btn {
            border-radius: 10px;
            font-weight: 500;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            border: none;
        }

        .btn:hover {
            transform: translateY(-2px);
        }

        .btn-danger {
            background: var(--secondary-gradient);
            box-shadow: var(--box-shadow);
        }

        .btn-info {
            background: var(--primary-gradient);
            box-shadow: var(--box-shadow);
        }

        .btn-primary {
            background: var(--primary-gradient);
            box-shadow: var(--box-shadow);
        }

        .btn-secondary {
            background: var(--dark-gradient);
            box-shadow: var(--box-shadow);
            color: white;
        }

        .form-select {
            border-radius: 10px;
            border: 1px solid var(--glass-border);
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            transition: all 0.3s ease;
            color: #333;
        }

        .form-select:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }

        input[type="date"].form-control {
            color-scheme: dark;
            background-color: rgba(255, 255, 255, 0.9);
            border-radius: 10px;
            border: 1px solid var(--glass-border);
            padding: 0.375rem 0.75rem;
        }

        input[type="date"].form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }

        .modal-content {
            background: var(--glass-bg);
            backdrop-filter: blur(20px);
            border: 1px solid var(--glass-border);
            border-radius: 20px;
            box-shadow: var(--box-shadow-hover);
        }

        .modal-header {
            background: var(--primary-gradient);
            color: white;
            border-bottom: none;
            border-radius: 20px 20px 0 0;
            text-shadow: var(--text-shadow);
        }

        .modal-body {
            padding: 2rem;
        }

        .form-control {
            border-radius: 10px;
            border: 1px solid var(--glass-border);
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            transition: all 0.3s ease;
            color: #333;
        }

        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
            background: rgba(255, 255, 255, 0.95);
        }

        .form-label {
            font-weight: 600;
            color: rgba(255, 255, 255, 0.9);
            text-shadow: var(--text-shadow);
        }

        h1, h2 {
            color: white;
            text-shadow: var(--text-shadow);
            font-weight: 700;
        }

        h1 {
            animation: fadeInUp 0.8s ease-out;
        }

        h2 {
            animation: fadeInUp 1s ease-out;
        }

        hr {
            border: none;
            height: 2px;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.5), transparent);
            margin: 2rem 0;
        }

        .alert {
            border-radius: 15px;
            border: none;
            backdrop-filter: blur(10px);
            box-shadow: var(--box-shadow);
        }

        @media (max-width: 991.98px) {
            /* No specific changes needed here for sidebar.show */
        }

        ::-webkit-scrollbar {
            width: 8px;
        }

        ::-webkit-scrollbar-track {
            background: rgba(255, 255, 255, 0.1);
        }

        ::-webkit-scrollbar-thumb {
            background: var(--primary-gradient);
            border-radius: 4px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: var(--secondary-gradient);
        }

        .avatar-circle {
            width: 35px;
            height: 35px;
            background: var(--primary-gradient);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 0.9rem;
        }

        .hidden-section {
            display: none;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark fixed-top navbar-admin">
        <div class="container-fluid">
            <button class="btn btn-outline-light me-3" type="button" data-bs-toggle="collapse" data-bs-target="#adminSidebar" aria-controls="adminSidebar" aria-expanded="false" aria-label="Toggle navigation" id="sidebarToggleBtn">
                <i class="fas fa-bars fa-lg"></i>
            </button>

            <a class="navbar-brand" href="#">
                <i class="fas fa-tachometer-alt me-2"></i>Dashboard Admin SR
            </a>
            <div class="d-flex ms-auto">
                <img src="images/14.jpg" alt="Logo Admin" class="me-3" style="height: 50px; width: 50px; border-radius: 100%; object-fit: cover;">
                
                <a href="admin_logout.php" class="btn btn-outline-light">
                    <i class="fas fa-sign-out-alt me-1"></i>Déconnexion
                </a>
            </div>
        </div>
    </nav>

    <div class="sidebar collapse" id="adminSidebar">
        <div class="pt-3 pb-2 mb-3">
            <h5 class="text-white text-center mb-4">
                <i class="fas fa-user-shield me-2"></i>Menu Admin
            </h5>
            <ul class="nav flex-column">
                <li class="nav-item">
                    <a class="nav-link active" href="#" onclick="showSection('apercu-section', this)">
                        <i class="fas fa-chart-line me-3"></i>Aperçu
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#" onclick="showSection('commandes-section', this)">
                        <i class="fas fa-list-alt me-3"></i>Commandes
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#" onclick="showSection('livreurs-section', this)">
                        <i class="fas fa-users me-3"></i>Livreurs
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#" onclick="showSection('salaries-section', this)">
                        <i class="fas fa-money-bill-wave me-3"></i>Salaires des livreurs
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#" onclick="showSection('archived-livreurs-section', this)">
                        <i class="fas fa-archive me-3"></i>Livreurs Archivés
                    </a>
                </li>
                
                <li class="nav-item">
                    <a class="nav-link" href="#" onclick="showSection('admins-inf-section', this)">
                        <i class="fas fa-user-shield me-3"></i>Admins Inférieurs
                    </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="#" onclick="showSection('admins-salaires-section', this)">
                    <i class="fas fa-money-bill-alt me-3"></i>Salaires des Admins
                </a>
            </li>


            </ul>
        </div>
    </div>

    <div class="content" id="mainContent">
        <div class="container-fluid">
            <div id="apercu-section">
                <h1 class="mb-4 pt-4">
                    <i class="fas fa-dashboard me-3"></i>Aperçu du Dashboard
                </h1>
                <hr>

                <div class="row mb-5">
                    <div class="col-lg-4 col-md-6 mb-4">
                        <div class="card card-dashboard text-white bg-primary">
                            <div class="card-body text-center">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h5 class="card-title mb-0">
                                        <i class="fas fa-shopping-cart me-2"></i>Total des Commandes
                                    </h5>
                                    <i class="fas fa-shopping-cart fa-2x opacity-50"></i>
                                </div>
                                <p class="card-text display-4"><?php echo count($commandes); ?></p>
                                <small class="opacity-75">Commandes enregistrées</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-4 col-md-6 mb-4">
                        <div class="card card-dashboard text-white bg-info-dark">
                            <div class="card-body text-center">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h5 class="card-title mb-0">
                                        <i class="fas fa-hourglass-start me-2"></i>Commandes En attente
                                    </h5>
                                    <i class="fas fa-hourglass-start fa-2x opacity-50"></i>
                                </div>
                                <p class="card-text display-4">
                                    <?php echo count($commandes_en_attente); ?>
                                </p>
                                <small class="opacity-75">En attente d'affectation</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-4 col-md-6 mb-4">
                        <div class="card card-dashboard text-white bg-orange">
                            <div class="card-body text-center">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h5 class="card-title mb-0">
                                        <i class="fas fa-user-check me-2"></i>Commandes Affectées
                                    </h5>
                                    <i class="fas fa-user-check fa-2x opacity-50"></i>
                                </div>
                                <p class="card-text display-4">
                                    <?php echo count($commandes_affectees); ?>
                                </p>
                                <small class="opacity-75">Commandes attribuées</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-4 col-md-6 mb-4">
                        <div class="card card-dashboard text-white bg-purple">
                            <div class="card-body text-center">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h5 class="card-title mb-0">
                                        <i class="fas fa-truck-ramp-box me-2"></i>Commandes En cours
                                    </h5>
                                    <i class="fas fa-truck-ramp-box fa-2x opacity-50"></i>
                                </div>
                                <p class="card-text display-4">
                                    <?php echo count($commandes_en_cours); ?>
                                </p>
                                <small class="opacity-75">Livraison en cours</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-4 col-md-6 mb-4">
                        <div class="card card-dashboard text-white bg-success">
                            <div class="card-body text-center">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h5 class="card-title mb-0">
                                        <i class="fas fa-check-circle me-2"></i>Commandes Terminées
                                    </h5>
                                    <i class="fas fa-check-circle fa-2x opacity-50"></i>
                                </div>
                                <p class="card-text display-4">
                                    <?php echo count($commandes_terminees); ?>
                                </p>
                                <small class="opacity-75">Livraisons réussies</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-4 col-md-6 mb-4">
                        <div class="card card-dashboard text-white bg-teal">
                            <div class="card-body text-center">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h5 class="card-title mb-0">
                                        <i class="fas fa-times-circle me-2"></i>Commandes Annulées
                                    </h5>
                                    <i class="fas fa-times-circle fa-2x opacity-50"></i>
                                </div>
                                <p class="card-text display-4">
                                    <?php echo count($commandes_annulees); ?>
                                </p>
                                <small class="opacity-75">Commandes annulées par le client</small>
                            </div>
                        </div>
                    </div>
                </div>

                <hr>

                <div class="row mb-5">
                    <div class="col-lg-6 mb-4">
                        <div class="card card-dashboard text-white">
                            <div class="card-header-dashboard">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-chart-pie me-2"></i>Statut des Commandes ce Mois-ci
                                </h5>
                            </div>
                            <div class="card-body bg-white rounded-bottom">
                                <canvas id="commandesStatutChart" width="400" height="200"></canvas>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-6 mb-4">
                        <div class="card card-dashboard text-white">
                            <div class="card-header-dashboard">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-users-cog me-2"></i>Statut des Livreurs
                                </h5>
                            </div>
                            <div class="card-body bg-white rounded-bottom">
                                <canvas id="livreursStatutChart" width="400" height="200"></canvas>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-6 mb-4">
                        <div class="card card-dashboard text-white">
                            <div class="card-header-dashboard">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-money-check-alt me-2"></i>Augmentation de Salaire (%)
                                </h5>
                            </div>
                            <div class="card-body bg-white rounded-bottom">
                                <canvas id="salaryAugmentationChart" width="400" height="200"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

            <hr>

            <div id="livreurs-section" class="hidden-section">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2>
                        <i class="fas fa-users me-3"></i>Gestion des Livreurs
                    </h2>
                    <div>
                        <button type="button" class="btn btn-add-livreur me-2" data-bs-toggle="modal" data-bs-target="#addLivreurModal">
                            <i class="fas fa-plus-circle me-2"></i>Ajouter un Livreur
                        </button>
                        <button type="button" class="btn btn-info" onclick="refreshLivreurs()">
                            <i class="fas fa-sync-alt me-2"></i>Mettre à jour Livreurs
                        </button>
                    </div>
                </div>

                <div class="table-responsive mb-5 card card-dashboard">
                    <div class="card-body p-0">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th><i class="fas fa-hashtag me-2"></i>ID</th>
                                    <th><i class="fas fa-user me-2"></i>Nom Complet</th>
                                    <th><i class="fas fa-id-card me-2"></i>CIN</th>
                                    <th><i class="fas fa-phone me-2"></i>Téléphone</th>
                                    <th><i class="fas fa-envelope me-2"></i>Email</th>
                                    <th><i class="fas fa-home me-2"></i>Situation Familiale</th>
                                    <th><i class="fas fa-calendar-alt me-2"></i>Date d'embauche</th>
                                    <th><i class="fas fa-user-circle me-2"></i>Âge</th>
                                    <th><i class="fas fa-toggle-on me-2"></i>Statut</th>
                                    <th><i class="fas fa-cogs me-2"></i>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($livreurs)): ?>
                                    <tr>
                                        <td colspan="10" class="text-center py-5">
                                            <i class="fas fa-users-slash fa-3x text-muted mb-3"></i>
                                            <br>
                                            <span class="text-muted">Aucun livreur actif pour le moment.</span>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($livreurs as $livreur): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($livreur['id']); ?></strong></td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="avatar-circle me-2">
                                                    <i class="fas fa-user"></i>
                                                </div>
                                                <?php echo htmlspecialchars($livreur['nom_livreur'] . ' ' . $livreur['prenom']); ?>
                                            </div>
                                        </td>
                                        <td><?php echo htmlspecialchars($livreur['cin'] ?? 'N/A'); ?></td>
                                        <td>
                                            <i class="fas fa-phone text-primary me-1"></i>
                                            <?php echo htmlspecialchars($livreur['telephone'] ?? 'N/A'); ?>
                                        </td>
                                        <td>
                                            <i class="fas fa-envelope text-info me-1"></i>
                                            <?php echo htmlspecialchars($livreur['email'] ?? 'N/A'); ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($livreur['situation_familiale'] ?? 'N/A'); ?></td>
                                        <td>
                                            <?php echo htmlspecialchars($livreur['date_embauche'] ? date('d/m/Y', strtotime($livreur['date_embauche'])) : 'N/A'); ?>
                                        </td>
                                        <td>
                                            <?php echo htmlspecialchars($livreur['age'] ?? 'N/A'); ?>
                                        </td>
                                        <td>
                                            <span class="badge <?php echo ($livreur['statut'] == 'actif') ? 'bg-success' : 'bg-danger'; ?>">
                                                <i class="fas <?php echo ($livreur['statut'] == 'actif') ? 'fa-check' : 'fa-times'; ?> me-1"></i>
                                                <?php echo htmlspecialchars(ucfirst($livreur['statut'])); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="d-flex gap-2">
                                                <form method="POST" action="admin.php" style="display: inline-block;">
                                                    <input type="hidden" name="action_livreur" value="update_statut">
                                                    <input type="hidden" name="livreur_id_update" value="<?php echo $livreur['id']; ?>">
                                                    <select name="new_statut_livreur" class="form-select form-select-sm" onchange="this.form.submit()">
                                                        <option value="actif" <?php echo ($livreur['statut'] == 'actif') ? 'selected' : ''; ?>>Actif</option>
                                                        <option value="inactif" <?php echo ($livreur['statut'] == 'inactif') ? 'selected' : ''; ?>>Inactif</option>
                                                    </select>
                                                </form>
                                                <form method="POST" action="admin.php" style="display: inline-block;" onsubmit="return confirm('Êtes-vous sûr de vouloir archiver ce livreur ? Il sera déplacé vers la section des livreurs archivés.');">
                                                    <input type="hidden" name="action_livreur" value="soft_delete">
                                                    <input type="hidden" name="livreur_id_delete" value="<?php echo $livreur['id']; ?>">
                                                    <button type="submit" class="btn btn-danger btn-sm">
                                                        <i class="fas fa-archive me-1"></i>Archiver
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <hr>

            <div id="commandes-section" class="hidden-section">
                <h2>
                    <i class="fas fa-list-alt me-3"></i>Détails des Commandes
                </h2>

                <div class="card card-dashboard mb-4 p-4">
                    <h5 class="card-title text-white mb-3"><i class="fas fa-filter me-2"></i>Filtrer les Commandes</h5>
                    <form method="GET" action="admin.php#commandes-section" class="row g-3 align-items-end">
                        <div class="col-md-4">
                            <label for="filter_date" class="form-label text-white">Date de commande:</label>
                            <input type="date" class="form-control" id="filter_date" name="filter_date" value="<?php echo htmlspecialchars($_GET['filter_date'] ?? ''); ?>">
                        </div>
                        <div class="col-md-4">
                            <label for="filter_livreur" class="form-label text-white">Livreur Affecté:</label>
                            <select class="form-select" id="filter_livreur" name="filter_livreur">
                                <option value="">Tous les livreurs</option>
                                <?php foreach ($livreurs_for_filter as $livreur_option): ?>
                                    <option value="<?php echo htmlspecialchars($livreur_option['id']); ?>"
                                        <?php echo (isset($_GET['filter_livreur']) && $_GET['filter_livreur'] == $livreur_option['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($livreur_option['nom_livreur'] . ' ' . $livreur_option['prenom']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4 d-flex justify-content-end">
                            <button type="submit" class="btn btn-primary me-2">
                                <i class="fas fa-search me-1"></i>Appliquer le filtre
                            </button>
                            <a href="admin.php#commandes-section" class="btn btn-secondary">
                                <i class="fas fa-redo me-1"></i>Réinitialiser
                            </a>
                        </div>
                    </form>
                </div>
                <div class="table-responsive card card-dashboard">
                    <div class="card-body p-0">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th><i class="fas fa-hashtag me-2"></i>ID Commande</th>
                                    <th><i class="fas fa-user me-2"></i>Client</th>
                                    <th><i class="fas fa-phone me-2"></i>Téléphone Client</th>
                                    <th><i class="fas fa-map-marker-alt me-2"></i>Adresse Client</th>
                                    <th><i class="fas fa-box me-2"></i>Type Commande</th>
                                    <th><i class="fas fa-clipboard me-2"></i>Demande Exacte</th>
                                    <th><i class="fas fa-truck me-2"></i>Livreur Affecté</th>
                                    <th><i class="fas fa-toggle-on me-2"></i>Statut Commande</th>
                                    <th><i class="fas fa-calendar me-2"></i>Date Commande</th>
                                    <th><i class="fas fa-cogs me-2"></i>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($commandes)): ?>
                                    <tr>
                                        <td colspan="10" class="text-center py-5">
                                            <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                                            <br>
                                            <span class="text-muted">Aucune commande pour le moment.</span>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($commandes as $commande): ?>
                                    <tr>
                                        <td><strong>#<?php echo htmlspecialchars($commande['commande_id']); ?></strong></td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="avatar-circle me-2">
                                                    <i class="fas fa-user-circle"></i>
                                                </div>
                                                <?php echo htmlspecialchars($commande['client_nom'] . ' ' . $commande['client_prenom']); ?>
                                            </div>
                                        </td>
                                        <td>
                                            <i class="fas fa-phone text-primary me-1"></i>
                                            <?php echo htmlspecialchars($commande['client_tel']); ?>
                                        </td>
                                        <td>
                                            <i class="fas fa-map-marker-alt text-danger me-1"></i>
                                            <?php echo htmlspecialchars($commande['client_adresse']); ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-info">
                                                <i class="fas fa-box me-1"></i>
                                                <?php echo htmlspecialchars(ucfirst($commande['type_commande'])); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="text-truncate" style="max-width: 200px;" title="<?php echo htmlspecialchars($commande['demande_exacte']); ?>">
                                                <?php echo nl2br(htmlspecialchars($commande['demande_exacte'])); ?>
                                            </div>
                                        </td>
                                        <td>
                                            <?php
                                            if ($commande['nom_livreur']) {
                                                echo '<div class="d-flex align-items-center">';
                                                echo '<i class="fas fa-user-tie text-success me-2"></i>';
                                                echo htmlspecialchars($commande['nom_livreur'] . ' ' . $commande['livreur_prenom']);
                                                echo '</div>';
                                            } else {
                                                echo '<span class="text-muted"><i class="fas fa-user-slash me-1"></i>Non affecté</span>';
                                            }
                                            ?>
                                        </td>
                                        <td>
                                            <form method="POST" action="admin.php" style="display: inline-block;">
                                                <input type="hidden" name="action" value="update_commande_statut">
                                                <input type="hidden" name="commande_id" value="<?php echo $commande['commande_id']; ?>">
                                                <select name="new_statut" class="form-select form-select-sm" onchange="this.form.submit()">
                                                    <option value="en attente" <?php echo ($commande['commande_statut'] == 'en attente') ? 'selected' : ''; ?>>En attente</option>
                                                    <option value="affectee" <?php echo ($commande['commande_statut'] == 'affectee') ? 'selected' : ''; ?>>Affectée</option>
                                                    <option value="en cours" <?php echo ($commande['commande_statut'] == 'en cours') ? 'selected' : ''; ?>>En cours</option>
                                                    <option value="terminee" <?php echo ($commande['commande_statut'] == 'terminee') ? 'selected' : ''; ?>>Terminée</option>
                                                    <option value="annulee" <?php echo ($commande['commande_statut'] == 'annulee') ? 'selected' : ''; ?>>Annulée</option>
                                                </select>
                                            </form>
                                        </td>
                                        <td>
                                            <i class="fas fa-calendar-alt text-info me-1"></i>
                                            <?php echo htmlspecialchars(date('d/m/Y H:i', strtotime($commande['date_commande']))); ?>
                                        </td>
                                        <td>
                                            <div class="d-flex gap-2">
                                                <button class="btn btn-info btn-sm" onclick="showDetails(<?php echo $commande['commande_id']; ?>)">
                                                    <i class="fas fa-eye me-1"></i>Voir Détails
                                                </button>
                                                <form method="POST" action="admin.php" style="display: inline-block;" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer cette commande ?');">
                                                    <input type="hidden" name="action" value="delete_commande">
                                                    <input type="hidden" name="commande_id_delete" value="<?php echo $commande['commande_id']; ?>">
                                                    <button type="submit" class="btn btn-danger btn-sm">
                                                        <i class="fas fa-trash me-1"></i>Supprimer
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <hr>

            <div id="salaries-section" class="hidden-section">
                <h2>
                    <i class="fas fa-money-bill-wave me-3"></i>Salaires des Livreurs
                </h2>

                <div class="table-responsive mb-5 card card-dashboard">
                    <div class="card-body p-0">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th><i class="fas fa-user me-2"></i>Nom</th>
                                    <th><i class="fas fa-user me-2"></i>Prénom</th>
                                    <th><i class="fas fa-calendar-alt me-2"></i>Date d'embauche</th>
                                    <th><i class="fas fa-calendar-check me-2"></i>Ancienneté(ans)</th>
                                    <th><i class="fas fa-user-circle me-2"></i>Âge</th>
                                    <th><i class="fas fa-percent me-2"></i>Augmentation (%)</th>
                                    <th><i class="fas fa-money-check-alt me-2"></i>Salaire Calculé (DH)</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $displayed_livreur_ids = [];
                                foreach ($all_livreurs_for_salary_calc as $livreur):
                                    if (in_array($livreur['id'], $displayed_livreur_ids)) {
                                        continue;
                                    }
                                    $displayed_livreur_ids[] = $livreur['id'];
                                ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($livreur['nom_livreur']); ?></td>
                                        <td><?php echo htmlspecialchars($livreur['prenom']); ?></td>
                                        <td><?php echo htmlspecialchars($livreur['date_embauche'] ? date('d/m/Y', strtotime($livreur['date_embauche'])) : 'N/A'); ?></td>
                                        <td>
                                            <?php echo htmlspecialchars($livreur['years_of_service'] ?? 0); ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($livreur['age'] ?? 'N/A'); ?></td>
                                        <td>
                                            <span class="badge bg-primary">
                                                <?php echo htmlspecialchars($livreur['augmentation_percentage']); ?>%
                                            </span>
                                        </td>
                                        <td>
                                            <strong><?php echo number_format($livreur['calculated_salary'], 2, ',', ' '); ?> DH</strong>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if (empty($all_livreurs_for_salary_calc)): ?>
                                    <tr>
                                        <td colspan="7" class="text-center py-5">
                                            <i class="fas fa-users-slash fa-3x text-muted mb-3"></i>
                                            <br>
                                            <span class="text-muted">Aucun livreur enregistré.</span>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <hr>

            <div id="archived-livreurs-section" class="hidden-section">
                <h2>
                    <i class="fas fa-archive me-3"></i>Livreurs Archivés
                </h2>

                <div class="table-responsive mb-5 card card-dashboard">
                    <div class="card-body p-0">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th><i class="fas fa-hashtag me-2"></i>ID</th>
                                    <th><i class="fas fa-user me-2"></i>Nom Complet</th>
                                    <th><i class="fas fa-id-card me-2"></i>CIN</th>
                                    <th><i class="fas fa-phone me-2"></i>Téléphone</th>
                                    <th><i class="fas fa-calendar-times me-2"></i>Date Archivage</th>
                                    <th><i class="fas fa-cogs me-2"></i>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($archived_livreurs)): ?>
                                    <tr>
                                        <td colspan="6" class="text-center py-5">
                                            <i class="fas fa-box-open fa-3x text-muted mb-3"></i>
                                            <br>
                                            <span class="text-muted">Aucun livreur archivé pour le moment.</span>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($archived_livreurs as $livreur): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($livreur['id']); ?></strong></td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="avatar-circle me-2">
                                                    <i class="fas fa-user-times"></i>
                                                </div>
                                                <?php echo htmlspecialchars($livreur['nom_livreur'] . ' ' . $livreur['prenom']); ?>
                                            </div>
                                        </td>
                                        <td><?php echo htmlspecialchars($livreur['cin'] ?? 'N/A'); ?></td>
                                        <td>
                                            <i class="fas fa-phone text-primary me-1"></i>
                                            <?php echo htmlspecialchars($livreur['telephone'] ?? 'N/A'); ?>
                                        </td>
                                        <td>
                                            <i class="fas fa-calendar-alt text-danger me-1"></i>
                                            <?php echo htmlspecialchars($livreur['deleted_at'] ? date('d/m/Y H:i', strtotime($livreur['deleted_at'])) : 'N/A'); ?>
                                        </td>
                                        <td>
                                            <form method="POST" action="admin.php" onsubmit="return confirm('Êtes-vous sûr de vouloir restaurer ce livreur ? Il redeviendra actif.');">
                                                <input type="hidden" name="action_livreur" value="restore">
                                                <input type="hidden" name="livreur_id_restore" value="<?php echo $livreur['id']; ?>">
                                                <button type="submit" class="btn btn-success btn-sm">
                                                    <i class="fas fa-undo-alt me-1"></i>Restaurer
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <hr>

<div id="admins-inf-section" class="hidden-section">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>
            <i class="fas fa-user-shield me-3"></i>Gestion des Admins Inférieurs
        </h2>
        <button type="button" class="btn btn-add-livreur" data-bs-toggle="modal" data-bs-target="#addAdminModal">
            <i class="fas fa-plus-circle me-2"></i>Ajouter Admin
        </button>
    </div>

    <div class="row">
        
        <?php
        // Récupérer les informations des admins inférieurs
        $stmt_admins = $pdo->query("SELECT * FROM admins_inf ORDER BY nom ASC");
        $admins = $stmt_admins->fetchAll();

        if (empty($admins)): ?>
            <div class="col-12 text-center py-5">
                <i class="fas fa-user-slash fa-3x text-muted mb-3"></i>
                <br>
                <span class="text-muted">Aucun admin inférieur enregistré.</span>
            </div>
        <?php else: ?>
            <?php foreach ($admins as $admin): ?>
            <div class="col-lg-4 col-md-6 mb-4">
                <div class="card card-dashboard text-white bg-primary">
                    <div class="card-body text-center">
                        <?php if ($admin['photo']): ?>
                            <img src="images/<?php echo htmlspecialchars($admin['photo']); ?>" class="rounded-circle mb-3" width="100" height="100" alt="Photo admin">
                        <?php else: ?>
                            <div class="avatar-circle mx-auto mb-3" style="width: 100px; height: 100px; font-size: 2rem;">
                                <i class="fas fa-user"></i>
                            </div>
                        <?php endif; ?>
                        <h4><?php echo htmlspecialchars($admin['nom'] . ' ' . $admin['prenom']); ?></h4>
                        <p class="mb-1"><i class="fas fa-envelope me-2"></i><?php echo htmlspecialchars($admin['email']); ?></p>
                        <p class="mb-3"><i class="fas fa-id-card me-2"></i><?php echo htmlspecialchars($admin['cin']); ?></p>
                        <button class="btn btn-light btn-sm" data-bs-toggle="modal" data-bs-target="#adminDetailsModal<?php echo $admin['id']; ?>">
                            <i class="fas fa-info-circle me-1"></i>Afficher plus de détails
                        </button>
                    </div>
                </div>
            </div>

            <!-- Modal pour les détails de l'admin -->
            <div class="modal fade" id="adminDetailsModal<?php echo $admin['id']; ?>" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Détails de l'admin</h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div class="text-center mb-4">
                                <?php if ($admin['photo']): ?>
                                    <img src="images/<?php echo htmlspecialchars($admin['photo']); ?>" class="rounded-circle" width="120" height="120" alt="Photo admin">
                                <?php else: ?>
                                    <div class="avatar-circle mx-auto" style="width: 120px; height: 120px; font-size: 2.5rem;">
                                        <i class="fas fa-user"></i>
                                    </div>
                                <?php endif; ?>
                                <h4 class="mt-3"><?php echo htmlspecialchars($admin['nom'] . ' ' . $admin['prenom']); ?></h4>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <p><strong><i class="fas fa-envelope me-2"></i>Email:</strong> <?php echo htmlspecialchars($admin['email']); ?></p>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <p><strong><i class="fas fa-id-card me-2"></i>CIN:</strong> <?php echo htmlspecialchars($admin['cin']); ?></p>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <p><strong><i class="fas fa-calendar-alt me-2"></i>Date d'embauche:</strong> 
                                        <?php echo $admin['date_embauche'] ? htmlspecialchars(date('d/m/Y', strtotime($admin['date_embauche']))) : 'N/A'; ?>
                                    </p>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <p><strong><i class="fas fa-clock me-2"></i>Ancienneté:</strong> 
                                        <?php 
                                        if ($admin['date_embauche']) {
                                            $dateEmbauche = new DateTime($admin['date_embauche']);
                                            $now = new DateTime();
                                            $interval = $now->diff($dateEmbauche);
                                            echo $interval->y . ' ans';
                                        } else {
                                            echo 'N/A';
                                        }
                                        ?>
                                    </p>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<hr>

<div id="admins-salaires-section" class="hidden-section">
    <h2>
        <i class="fas fa-money-bill-alt me-3"></i>Salaires des Admins Inférieurs
    </h2>

    <div class="table-responsive mb-5 card card-dashboard">
        <div class="card-body p-0">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th><i class="fas fa-user me-2"></i>Nom</th>
                        <th><i class="fas fa-user me-2"></i>Prénom</th>
                        <th><i class="fas fa-calendar-alt me-2"></i>Date d'embauche</th>
                        <th><i class="fas fa-calendar-check me-2"></i>Ancienneté (ans)</th>
                        <th><i class="fas fa-percent me-2"></i>Augmentation (%)</th>
                        <th><i class="fas fa-money-bill-wave me-2"></i>Salaire Calculé (DH)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $base_salary_admin = 8000; // Salaire de base pour les admins
                    $stmt_admins_salaries = $pdo->query("SELECT * FROM admins_inf ORDER BY nom ASC");
                    $admins_salaries = $stmt_admins_salaries->fetchAll();

                    if (empty($admins_salaries)): ?>
                        <tr>
                            <td colspan="6" class="text-center py-5">
                                <i class="fas fa-user-slash fa-3x text-muted mb-3"></i>
                                <br>
                                <span class="text-muted">Aucun admin inférieur enregistré.</span>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($admins_salaries as $admin): 
                            $augmentation_percentage = 0;
                            $years_of_service = 0;

                            if (!empty($admin['date_embauche'])) {
                                $date_embauche = new DateTime($admin['date_embauche']);
                                $now = new DateTime();
                                $interval = $now->diff($date_embauche);
                                $years_of_service = $interval->y;

                                // Calcul de l'augmentation basée sur l'ancienneté
                                if ($years_of_service >= 15) {
                                    $augmentation_percentage = 50;
                                } elseif ($years_of_service >= 10) {
                                    $augmentation_percentage = 35;
                                } elseif ($years_of_service >= 7) {
                                    $augmentation_percentage = 25;
                                } elseif ($years_of_service >= 5) {
                                    $augmentation_percentage = 17;
                                } elseif ($years_of_service >= 3) {
                                    $augmentation_percentage = 10;
                                } elseif ($years_of_service >= 1) {
                                    $augmentation_percentage = 5;
                                }
                            }
                            $calculated_salary = $base_salary_admin * (1 + ($augmentation_percentage / 100));
                        ?>
                            <tr>
                                <td><?php echo htmlspecialchars($admin['nom']); ?></td>
                                <td><?php echo htmlspecialchars($admin['prenom']); ?></td>
                                <td>
                                    <?php echo $admin['date_embauche'] ? htmlspecialchars(date('d/m/Y', strtotime($admin['date_embauche']))) : 'N/A'; ?>
                                </td>
                                <td><?php echo $years_of_service; ?></td>
                                <td>
                                    <span class="badge bg-primary">
                                        <?php echo $augmentation_percentage; ?>%
                                    </span>
                                </td>
                                <td>
                                    <strong><?php echo number_format($calculated_salary, 2, ',', ' '); ?> DH</strong>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

        </div>
    </div>

    <div class="modal fade" id="addLivreurModal" tabindex="-1" aria-labelledby="addLivreurModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addLivreurModalLabel">
                        <i class="fas fa-user-plus me-2"></i>Ajouter un nouveau Livreur
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form method="POST" action="admin.php">
                        <input type="hidden" name="action_livreur" value="add">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="nom_livreur_add" class="form-label">
                                    <i class="fas fa-user me-2"></i>Nom
                                </label>
                                <input type="text" class="form-control" id="nom_livreur_add" name="nom_livreur_add" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="prenom_livreur_add" class="form-label">
                                    <i class="fas fa-user me-2"></i>Prénom
                                </label>
                                <input type="text" class="form-control" id="prenom_livreur_add" name="prenom_livreur_add" required>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="cin_livreur_add" class="form-label">
                                    <i class="fas fa-id-card me-2"></i>CIN
                                </label>
                                <input type="text" class="form-control" id="cin_livreur_add" name="cin_livreur_add">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="tele_livreur_add" class="form-label">
                                    <i class="fas fa-phone me-2"></i>Téléphone
                                </label>
                                <input type="text" class="form-control" id="tele_livreur_add" name="tele_livreur_add" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="email_livreur_add" class="form-label">
                                <i class="fas fa-envelope me-2"></i>Email
                            </label>
                            <input type="email" class="form-control" id="email_livreur_add" name="email_livreur_add">
                        </div>
                        <div class="mb-3">
                            <label for="situation_familiale_livreur_add" class="form-label">
                                <i class="fas fa-home me-2"></i>Situation Familiale
                            </label>
                            <input type="text" class="form-control" id="situation_familiale_livreur_add" name="situation_familiale_livreur_add">
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="date_embauche_livreur_add" class="form-label">
                                    <i class="fas fa-calendar-alt me-2"></i>Date d'embauche
                                </label>
                                <input type="date" class="form-control" id="date_embauche_livreur_add" name="date_embauche_livreur_add">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="age_livreur_add" class="form-label">
                                    <i class="fas fa-user-circle me-2"></i>Âge
                                </label>
                                <input type="number" class="form-control" id="age_livreur_add" name="age_livreur_add" min="18" max="70">
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-plus-circle me-2"></i>Ajouter Livreur
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal pour ajouter un admin -->
<div class="modal fade" id="addAdminModal" tabindex="-1" aria-labelledby="addAdminModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addAdminModalLabel">
                    <i class="fas fa-user-plus me-2"></i>Ajouter un nouvel Admin
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form method="POST" action="admin.php" enctype="multipart/form-data">
                    <input type="hidden" name="action_admin" value="add">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="nom_admin_add" class="form-label">
                                <i class="fas fa-user me-2"></i>Nom
                            </label>
                            <input type="text" class="form-control" id="nom_admin_add" name="nom_admin_add" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="prenom_admin_add" class="form-label">
                                <i class="fas fa-user me-2"></i>Prénom
                            </label>
                            <input type="text" class="form-control" id="prenom_admin_add" name="prenom_admin_add" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="photo_admin_add" class="form-label">
                            <i class="fas fa-camera me-2"></i>Photo (optionnel)
                        </label>
                        <input type="file" class="form-control" id="photo_admin_add" name="photo_admin_add" accept="image/*">
                    </div>
                    <div class="mb-3">
                        <label for="email_admin_add" class="form-label">
                            <i class="fas fa-envelope me-2"></i>Email
                        </label>
                        <input type="email" class="form-control" id="email_admin_add" name="email_admin_add" required>
                    </div>
                    <div class="mb-3">
                        <label for="cin_admin_add" class="form-label">
                            <i class="fas fa-id-card me-2"></i>CIN
                        </label>
                            <input type="text" class="form-control" id="cin_admin_add" name="cin_admin_add" required>
                    </div>
                    <div class="mb-3">
                        <label for="date_embauche_admin_add" class="form-label">
                            <i class="fas fa-calendar-alt me-2"></i>Date d'embauche
                        </label>
                        <input type="date" class="form-control" id="date_embauche_admin_add" name="date_embauche_admin_add" required>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-plus-circle me-2"></i>Ajouter Admin
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <script>
        

        // Dans la partie JavaScript existante, ajoutez ces sections aux gestionnaires d'événements
document.querySelectorAll('.sidebar .nav-link').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
        // ... code existant ...
        // Ajoutez ces lignes pour gérer les nouvelles sections
        document.getElementById('admins-inf-section').classList.add('hidden-section');
        document.getElementById('admins-salaires-section').classList.add('hidden-section');
        // ... suite du code existant ...
    });
});

// Dans le DOMContentLoaded, ajoutez ces vérifications
document.addEventListener('DOMContentLoaded', function() {
    // ... code existant ...
    if (window.location.hash === '#admins-inf-section') {
        document.getElementById('apercu-section').classList.add('hidden-section');
        // ... autres sections ...
        document.getElementById('admins-inf-section').classList.remove('hidden-section');
        document.querySelector('.sidebar a[onclick*="admins-inf-section"]').classList.add('active');
    } else if (window.location.hash === '#admins-salaires-section') {
        document.getElementById('apercu-section').classList.add('hidden-section');
        // ... autres sections ...
        document.getElementById('admins-salaires-section').classList.remove('hidden-section');
        document.querySelector('.sidebar a[onclick*="admins-salaires-section"]').classList.add('active');
    }
    // ... suite du code existant ...
});


        // Data from PHP for the charts
        const chartData = <?php echo $chart_data_json; ?>;
        const livreursStatutData = <?php echo $livreurs_statut_json; ?>;
        const salaryAugmentationData = <?php echo $salary_augmentation_json; ?>;

        // Configuration pour Chart.js (Commandes Statut)
        const labelsCommandes = Object.keys(chartData);
        const dataValuesCommandes = Object.values(chartData);

        const backgroundColorsCommandes = [
            'rgba(54, 162, 235, 0.8)',
            'rgba(255, 159, 64, 0.8)',
            'rgba(153, 102, 255, 0.8)',
            'rgba(75, 192, 192, 0.8)',
            'rgba(255, 99, 132, 0.8)'
        ];
        const borderColorsCommandes = [
            'rgba(54, 162, 235, 1)',
            'rgba(255, 159, 64, 1)',
            'rgba(153, 102, 255, 1)',
            'rgba(75, 192, 192, 1)',
            'rgba(255, 99, 132, 1)'
        ];

        const ctxCommandes = document.getElementById('commandesStatutChart').getContext('2d');
        const commandesStatutChart = new Chart(ctxCommandes, {
            type: 'doughnut',
            data: {
                labels: labelsCommandes.map(label => label.charAt(0).toUpperCase() + label.slice(1)),
                datasets: [{
                    label: 'Nombre de Commandes',
                    data: dataValuesCommandes,
                    backgroundColor: backgroundColorsCommandes,
                    borderColor: borderColorsCommandes,
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'right',
                        labels: {
                            color: '#333',
                            font: {
                                size: 14
                            }
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                let label = context.label || '';
                                if (label) {
                                    label += ': ';
                                }
                                if (context.parsed !== null) {
                                    label += context.parsed + ' commandes';
                                }
                                return label;
                            }
                        }
                    },
                    title: {
                        display: true,
                        text: 'Répartition des Commandes par Statut (Mois Actuel)',
                        color: '#333',
                        font: {
                            size: 18
                        }
                    }
                }
            }
        });

        // Configuration pour Chart.js (Livreurs Statut)
        const labelsLivreurs = ['Actifs', 'Inactifs'];
        const dataValuesLivreurs = [livreursStatutData.actif, livreursStatutData.inactif];

        const backgroundColorsLivreurs = [
            'rgba(40, 167, 69, 0.8)',
            'rgba(220, 53, 69, 0.8)'
        ];
        const borderColorsLivreurs = [
            'rgba(40, 167, 69, 1)',
            'rgba(220, 53, 69, 1)'
        ];

        const ctxLivreurs = document.getElementById('livreursStatutChart').getContext('2d');
        const livreursStatutChart = new Chart(ctxLivreurs, {
            type: 'doughnut',
            data: {
                labels: labelsLivreurs,
                datasets: [{
                    label: 'Nombre de Livreurs',
                    data: dataValuesLivreurs,
                    backgroundColor: backgroundColorsLivreurs,
                    borderColor: borderColorsLivreurs,
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'right',
                        labels: {
                            color: '#333',
                            font: {
                                size: 14
                            }
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                let label = context.label || '';
                                if (label) {
                                    label += ': ';
                                }
                                if (context.parsed !== null) {
                                    label += context.parsed + ' livreurs';
                                }
                                return label;
                            }
                        }
                    },
                    title: {
                        display: true,
                        text: 'Répartition des Livreurs par Statut',
                        color: '#333',
                        font: {
                            size: 18
                        }
                    }
                }
            }
        });

        // Configuration pour Chart.js (Augmentation de Salaire) - Version histogramme
        const labelsAugmentation = [
            'Pas d\'augmentation',
            '2%',
            '5%',
            '8%',
            '12%'
        ];
        const dataValuesAugmentation = [
            salaryAugmentationData.no_augmentation,
            salaryAugmentationData['2_percent'],
            salaryAugmentationData['5_percent'],
            salaryAugmentationData['8_percent'],
            salaryAugmentationData['12_percent']
        ];

        const backgroundColorsAugmentation = [
            'rgba(108, 117, 125, 0.8)',
            'rgba(255, 206, 86, 0.8)',
            'rgba(75, 192, 192, 0.8)',
            'rgba(153, 102, 255, 0.8)',
            'rgba(54, 162, 235, 0.8)'
        ];
        const borderColorsAugmentation = [
            'rgba(108, 117, 125, 1)',
            'rgba(255, 206, 86, 1)',
            'rgba(75, 192, 192, 1)',
            'rgba(153, 102, 255, 1)',
            'rgba(54, 162, 235, 1)'
        ];

        const ctxAugmentation = document.getElementById('salaryAugmentationChart').getContext('2d');
        const salaryAugmentationChart = new Chart(ctxAugmentation, {
            type: 'bar',
            data: {
                labels: labelsAugmentation,
                datasets: [{
                    label: 'Nombre de Livreurs',
                    data: dataValuesAugmentation,
                    backgroundColor: backgroundColorsAugmentation,
                    borderColor: borderColorsAugmentation,
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false,
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                let label = context.dataset.label || '';
                                if (label) {
                                    label += ': ';
                                }
                                if (context.parsed.y !== null) {
                                    label += context.parsed.y + ' livreurs';
                                }
                                return label;
                            }
                        }
                    },
                    title: {
                        display: true,
                        text: 'Répartition des Livreurs par Augmentation de Salaire',
                        color: '#333',
                        font: {
                            size: 18
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Nombre de livreurs',
                            color: '#333'
                        }
                    },
                    x: {
                        title: {
                            display: true,
                            text: 'Pourcentage d\'augmentation',
                            color: '#333'
                        }
                    }
                }
            }
        });

        function showDetails(commandeId) {
            alert('Fonctionnalité à implémenter : Détails de la commande #' + commandeId);
        }

        document.querySelectorAll('.sidebar .nav-link').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const targetId = this.getAttribute('onclick').match(/'(.*?)'/)[1];

                document.getElementById('apercu-section').classList.add('hidden-section');
                document.getElementById('commandes-section').classList.add('hidden-section');
                document.getElementById('livreurs-section').classList.add('hidden-section');
                document.getElementById('salaries-section').classList.add('hidden-section');
                document.getElementById('archived-livreurs-section').classList.add('hidden-section');

                document.getElementById(targetId).classList.remove('hidden-section');

                document.querySelectorAll('.sidebar .nav-link').forEach(navLink => {
                    navLink.classList.remove('active');
                });
                this.classList.add('active');

                document.getElementById(targetId).scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });

                const sidebar = document.getElementById('adminSidebar');
                const bsCollapse = new bootstrap.Collapse(sidebar, { toggle: false });
                if (sidebar.classList.contains('show')) {
                    bsCollapse.hide();
                }
            });
        });

        document.addEventListener('DOMContentLoaded', function() {
            const sidebar = document.getElementById('adminSidebar');
            const mainContent = document.getElementById('mainContent');
            const sidebarToggleBtn = document.getElementById('sidebarToggleBtn');

            mainContent.style.marginLeft = '0';

            sidebar.addEventListener('show.bs.collapse', function () {
                document.body.classList.add('sidebar-open');
                if (window.innerWidth >= 992) {
                    mainContent.style.marginLeft = '280px';
                }
            });

            sidebar.addEventListener('hide.bs.collapse', function () {
                document.body.classList.remove('sidebar-open');
                mainContent.style.marginLeft = '0';
            });

            window.addEventListener('resize', function() {
                if (sidebar.classList.contains('show') && window.innerWidth >= 992) {
                    mainContent.style.marginLeft = '280px';
                } else {
                    mainContent.style.marginLeft = '0';
                }
            });

            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.has('filter_date') || urlParams.has('filter_livreur') || window.location.hash === '#commandes-section') {
                document.getElementById('apercu-section').classList.add('hidden-section');
                document.getElementById('livreurs-section').classList.add('hidden-section');
                document.getElementById('salaries-section').classList.add('hidden-section');
                document.getElementById('archived-livreurs-section').classList.add('hidden-section');
                document.getElementById('commandes-section').classList.remove('hidden-section');

                document.querySelectorAll('.sidebar .nav-link').forEach(navLink => {
                    navLink.classList.remove('active');
                });
                document.querySelector('.sidebar a[onclick*="commandes-section"]').classList.add('active');
            } else {
                document.getElementById('commandes-section').classList.add('hidden-section');
                document.getElementById('livreurs-section').classList.add('hidden-section');
                document.getElementById('salaries-section').classList.add('hidden-section');
                document.getElementById('archived-livreurs-section').classList.add('hidden-section');
            }
        });

        function animateCounters() {
            const counters = document.querySelectorAll('.display-4');
            counters.forEach(counter => {
                const parts = counter.textContent.split('/');
                const target = parseInt(parts[0]);
                const total = parts[1] ? ' / ' + parts[1] : '';

                let current = 0;
                const increment = target / 50;
                const timer = setInterval(() => {
                    current += increment;
                    if (current >= target) {
                        counter.textContent = target + total;
                        clearInterval(timer);
                    } else {
                        counter.textContent = Math.floor(current) + total;
                    }
                }, 50);
            });
        }

        window.addEventListener('load', function() {
            setTimeout(animateCounters, 500);
        });

        function refreshLivreurs() {
            alert('La liste des livreurs a été rafraîchie. (Pour un rafraîchissement sans rechargement complet de la page, une implémentation AJAX serait nécessaire).');
            document.getElementById('livreurs-section').scrollIntoView({
                behavior: 'smooth',
                block: 'start'
            });
            window.location.reload();
        }
    </script>
</body>
</html>