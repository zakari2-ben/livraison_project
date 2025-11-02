<?php
session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: admins_login.php");
    exit();
}

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "delivery_app_db";

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname;charset=utf8mb4", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Get statistics
    $stats = [];
    
    // Total orders
    $stmt = $conn->query("SELECT COUNT(*) as total FROM commandes");
    $stats['total_orders'] = $stmt->fetch()['total'];
    
    // Pending orders
    $stmt = $conn->query("SELECT COUNT(*) as pending FROM commandes WHERE statut = 'en attente'");
    $stats['pending_orders'] = $stmt->fetch()['pending'];
    
    // Completed orders
    $stmt = $conn->query("SELECT COUNT(*) as completed FROM commandes WHERE statut = 'terminee'");
    $stats['completed_orders'] = $stmt->fetch()['completed'];
    
    // Cancelled orders
    $stmt = $conn->query("SELECT COUNT(*) as cancelled FROM commandes WHERE statut = 'annulee'");
    $stats['cancelled_orders'] = $stmt->fetch()['cancelled'];
    
    // Active delivery staff
    $stmt = $conn->query("SELECT COUNT(*) as active FROM livreurs WHERE statut = 'actif'");
    $stats['active_delivery'] = $stmt->fetch()['active'];

    // Get all delivery staff for pie chart
    $stmt = $conn->query("SELECT statut, COUNT(*) as count FROM livreurs GROUP BY statut");
    $livreur_stats = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    
    // Get recent orders
    $stmt = $conn->prepare("
        SELECT c.*, cl.nom, cl.prenom, cl.telephone, l.nom_livreur 
        FROM commandes c 
        LEFT JOIN clients cl ON c.client_id = cl.id 
        LEFT JOIN livreurs l ON c.livreur_id = l.id 
        ORDER BY c.date_commande DESC 
        LIMIT 10
    ");
    $stmt->execute();
    $recent_orders = $stmt->fetchAll();
    
    // Get delivery staff
    $stmt = $conn->query("SELECT * FROM livreurs ORDER BY nom_livreur");
    $delivery_staff = $stmt->fetchAll();
    
    // Get admins info
    $stmt = $conn->prepare("SELECT * FROM admins_inf WHERE id = :admin_id");
    $stmt->bindParam(':admin_id', $_SESSION['admin_id']);
    $stmt->execute();
    $current_admin = $stmt->fetch(PDO::FETCH_ASSOC);

    $stmt = $conn->query("SELECT * FROM admins_inf ORDER BY nom, prenom");
    $admins_inf = $stmt->fetchAll();

    // Get clients info
    $stmt = $conn->query("SELECT * FROM clients ORDER BY nom, prenom");
    $clients_inf = $stmt->fetchAll();
    
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tableau de Bord - Livraison Express</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --secondary-gradient: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            --success-gradient: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            --warning-gradient: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
            --danger-gradient: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
            --dark-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --glass-bg: rgba(255, 255, 255, 0.25);
            --glass-border: rgba(255, 255, 255, 0.18);
            --shadow-1: 0 8px 32px 0 rgba(31, 38, 135, 0.37);
            --shadow-2: 0 4px 15px 0 rgba(31, 38, 135, 0.2);
            --text-primary: #2d3748;
            --text-secondary: #718096;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            overflow-x: hidden;
        }

        /* Animated Background */
        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1000 1000"><defs><radialGradient id="a" cx="50%" cy="50%"><stop offset="0%" stop-color="%23ffffff" stop-opacity="0.1"/><stop offset="100%" stop-color="%23ffffff" stop-opacity="0"/></radialGradient></defs><circle cx="200" cy="200" r="100" fill="url(%23a)"><animate attributeName="cx" values="200;800;200" dur="20s" repeatCount="indefinite"/></circle><circle cx="800" cy="300" r="150" fill="url(%23a)"><animate attributeName="cy" values="300;700;300" dur="25s" repeatCount="indefinite"/></circle><circle cx="400" cy="600" r="80" fill="url(%23a)"><animate attributeName="r" values="80;120;80" dur="15s" repeatCount="indefinite"/></circle></svg>');
            animation: float 30s ease-in-out infinite;
            z-index: -1;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            50% { transform: translateY(-20px) rotate(5deg); }
        }

        /* Sidebar Styles */
        .sidebar {
            position: fixed;
            left: 0;
            top: 0;
            width: 280px;
            height: 100vh;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-right: 1px solid var(--glass-border);
            box-shadow: var(--shadow-1);
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            z-index: 1050; /* Z-index needs to be high for mobile overlay */
            overflow-y: auto;
        }

        .sidebar.collapsed {
            width: 80px;
        }

        .sidebar-header {
            padding: 30px 20px;
            text-align: center;
            border-bottom: 1px solid var(--glass-border);
            background: var(--primary-gradient);
            color: white;
            position: relative;
            overflow: hidden;
        }

        .sidebar-header::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
            animation: shimmer 3s ease-in-out infinite;
        }

        @keyframes shimmer {
            0%, 100% { transform: scale(0) rotate(0deg); }
            50% { transform: scale(1) rotate(180deg); }
        }

        .sidebar-header h4 {
            font-weight: 700;
            font-size: 1.4rem;
            margin: 0;
            position: relative;
            z-index: 2;
        }

        .sidebar-header .logo {
            font-size: 2.5rem;
            margin-bottom: 10px;
            position: relative;
            z-index: 2;
        }

        .toggle-btn {
            position: absolute;
            top: 20px;
            right: 20px;
            background: rgba(255, 255, 255, 0.2);
            border: none;
            color: white;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
            z-index: 3;
        }

        .toggle-btn:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: rotate(180deg);
        }

        .sidebar-menu {
            padding: 30px 0;
        }

        .menu-item {
            display: flex;
            align-items: center;
            padding: 18px 25px;
            color: var(--text-primary);
            text-decoration: none;
            border: none;
            background: none;
            width: 100%;
            text-align: left;
            font-weight: 500;
            font-size: 0.95rem;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            margin: 5px 15px;
            border-radius: 15px;
        }

        .menu-item:hover {
            background: var(--primary-gradient);
            color: white;
            transform: translateX(8px);
            box-shadow: var(--shadow-2);
        }

        .menu-item.active {
            background: var(--primary-gradient);
            color: white;
            box-shadow: var(--shadow-2);
        }


        .menu-item i {
            font-size: 1.2rem;
            margin-right: 15px;
            width: 20px;
            text-align: center;
        }

        .menu-text {
            transition: all 0.3s ease;
        }

        .sidebar.collapsed .menu-text, .sidebar.collapsed .notification-badge {
            opacity: 0;
            transform: translateX(-20px);
        }

        /* Main Content */
        .main-content {
            margin-left: 280px;
            padding: 20px;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            min-height: 100vh;
        }

        .main-content.expanded {
            margin-left: 80px;
        }

        /* Header */
        .header {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            padding: 25px 30px;
            border-radius: 20px;
            box-shadow: var(--shadow-1);
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border: 1px solid var(--glass-border);
        }
        
        /* NEW: Mobile Toggle Button */
        .mobile-menu-toggle {
            background: var(--primary-gradient);
            color: white;
            border: none;
            width: 45px;
            height: 45px;
            border-radius: 50%;
            font-size: 1.2rem;
            box-shadow: var(--shadow-2);
        }

        .admin-profile-header {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .admin-profile-header img,
        .admin-profile-header .placeholder {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            border: 3px solid transparent;
            background: var(--primary-gradient);
            background-clip: padding-box;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
        }

        .admin-profile-header .placeholder {
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 700;
            font-size: 1.3rem;
        }

        .welcome-text {
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--text-primary);
            margin: 0;
        }

        .logout-btn {
            background: var(--danger-gradient);
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 25px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 4px 15px rgba(250, 112, 154, 0.4);
        }

        .logout-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(250, 112, 154, 0.6);
            color: white;
        }

        /* Stats Cards */
        .stats-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 25px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: var(--shadow-1);
            text-align: center;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            border: 1px solid var(--glass-border);
            position: relative;
            overflow: hidden;
        }

        .stats-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.4), transparent);
            transition: left 0.6s ease;
        }

        .stats-card:hover::before {
            left: 100%;
        }

        .stats-card:hover {
            transform: translateY(-10px) scale(1.02);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
        }

        .stats-icon {
            font-size: 3.5rem;
            margin-bottom: 20px;
            background: var(--primary-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            position: relative;
            z-index: 2;
        }

        .stats-number {
            font-size: 3rem;
            font-weight: 800;
            margin-bottom: 15px;
            background: var(--primary-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            position: relative;
            z-index: 2;
        }

        .stats-label {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--text-secondary);
            position: relative;
            z-index: 2;
        }

        /* Content Sections */
        .content-section {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 25px;
            padding: 40px;
            margin-bottom: 30px;
            box-shadow: var(--shadow-1);
            border: 1px solid var(--glass-border);
            display: none;
            animation: slideIn 0.5s ease-out;
        }

        .content-section.active {
            display: block;
        }

        @keyframes slideIn {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .section-title {
            font-size: 2rem;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 30px;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .section-title i {
            background: var(--primary-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        /* Chart Containers */
        .chart-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 25px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: var(--shadow-1);
            border: 1px solid var(--glass-border);
            position: relative;
            overflow: hidden;
        }

        .chart-container::before {
            content: '';
            position: absolute;
            top: -2px;
            left: -2px;
            right: -2px;
            bottom: -2px;
            background: var(--primary-gradient);
            border-radius: 25px;
            z-index: -1;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .chart-container:hover::before {
            opacity: 0.1;
        }

        .chart-title {
            font-size: 1.4rem;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 25px;
            text-align: center;
        }

        /* Tables */
        .table-container {
            border-radius: 20px;
            overflow: hidden;
            box-shadow: var(--shadow-2);
            background: white;
            /* EDITED: Added horizontal scrolling for responsiveness */
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }

        .table {
            margin-bottom: 0;
            /* EDITED: Ensure table doesn't shrink unnecessarily */
            min-width: 700px;
        }

        .table thead th {
            background: var(--primary-gradient);
            color: white;
            border: none;
            font-weight: 600;
            padding: 20px 15px;
            text-transform: uppercase;
            font-size: 0.85rem;
            letter-spacing: 1px;
        }

        .table tbody tr {
            transition: all 0.3s ease;
        }

        .table tbody tr:hover {
            background: rgba(102, 126, 234, 0.05);
            /* Removed scale transform as it can be buggy with scrolling tables */
        }

        .table td {
            padding: 18px 15px;
            border-bottom: 1px solid #f1f3f4;
            vertical-align: middle;
        }

        /* Badges */
        .badge-status {
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .badge.bg-warning {
            background: #ffc107 !important;
        }
        
        .badge.bg-success {
            background: var(--success-gradient) !important;
        }
        
        .badge.bg-danger {
            background: var(--danger-gradient) !important;
        }
        
        .badge.bg-secondary {
            background: var(--dark-gradient) !important;
        }

        /* EDITED: Responsive Design */
        @media (max-width: 992px) {
            .sidebar {
                transform: translateX(-100%);
            }
            .sidebar.collapsed {
                transform: translateX(-100%);
            }
            .sidebar.mobile-open {
                transform: translateX(0);
            }

            .main-content, .main-content.expanded {
                margin-left: 0;
            }

            .toggle-btn {
                display: none; /* Hide desktop toggle on mobile */
            }
        }
        
        @media (max-width: 768px) {
            .header {
                flex-direction: row; /* Keep it as a row */
                justify-content: space-between;
                padding: 15px 20px;
            }
            
            .admin-profile-header {
                gap: 10px;
            }
            
            .welcome-text {
                font-size: 1.2rem;
            }
            .admin-profile-header img, .admin-profile-header .placeholder {
                width: 45px;
                height: 45px;
            }
            .logout-btn {
                padding: 8px 15px;
                font-size: 0.9rem;
            }

            .stats-card {
                margin-bottom: 20px;
            }
        }
        
        @media (max-width: 576px) {
             .admin-profile-header .welcome-text, .admin-profile-header p {
                 display: none; /* Hide text on very small screens to save space */
             }
        }


        /* Loading Animation */
        .loading {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(102, 126, 234, 0.3);
            border-radius: 50%;
            border-top-color: #667eea;
            animation: spin 1s ease-in-out infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        /* Pulse Animation for New Data */
        .pulse {
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }

        /* Notification Badge */
        .notification-badge {
            position: absolute;
            top: 10px;
            right: 15px;
            background: var(--danger-gradient);
            color: white;
            border-radius: 50%;
            width: 24px;
            height: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.75rem;
            font-weight: 700;
            animation: bounce 2s infinite;
            transition: all 0.3s ease;
        }

        @keyframes bounce {
            0%, 20%, 50%, 80%, 100% { transform: translateY(0); }
            40% { transform: translateY(-10px); }
            60% { transform: translateY(-5px); }
        }
    </style>
</head>
<body>
    <div class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <button class="toggle-btn" onclick="toggleSidebar()">
                <i class="fas fa-bars"></i>
            </button>
            <div class="logo">
                <i class="fas fa-shipping-fast"></i>
            </div>
            <h4>Livraison Express</h4>
        </div>
        <nav class="sidebar-menu">
            <button class="menu-item active" onclick="showSection('dashboard', this)">
                <i class="fas fa-chart-line"></i>
                <span class="menu-text">Tableau de Bord</span>
            </button>
            <button class="menu-item" onclick="showSection('orders', this)">
                <i class="fas fa-shopping-cart"></i>
                <span class="menu-text">Commandes</span>
                <?php if($stats['pending_orders'] > 0): ?>
                    <span class="notification-badge"><?php echo $stats['pending_orders']; ?></span>
                <?php endif; ?>
            </button>
            <button class="menu-item" onclick="showSection('delivery', this)">
                <i class="fas fa-motorcycle"></i>
                <span class="menu-text">Livreurs</span>
            </button>
            <button class="menu-item" onclick="showSection('clients', this)">
                <i class="fas fa-users"></i>
                <span class="menu-text">Clients</span>
            </button>
        </nav>
    </div>

    <div class="main-content" id="mainContent">
        <div class="header">
            <button class="mobile-menu-toggle d-block d-lg-none" onclick="toggleMobileSidebar()">
                 <i class="fas fa-bars"></i>
            </button>
        
            <div class="admin-profile-header">
                <?php if (isset($current_admin['photo']) && !empty($current_admin['photo']) && file_exists('images/' . $current_admin['photo'])): ?>
                    <img src="images/<?php echo htmlspecialchars($current_admin['photo']); ?>" alt="Admin Photo">
                <?php else: ?>
                    <div class="placeholder">
                        <?php echo strtoupper(substr($current_admin['nom'], 0, 1) . substr($current_admin['prenom'], 0, 1)); ?>
                    </div>
                <?php endif; ?>
                <div>
                    <h3 class="welcome-text">Bienvenue, <?php echo htmlspecialchars($_SESSION['admin_name']); ?></h3>
                    <p class="text-muted mb-0">Gérer votre plateforme de livraison</p>
                </div>
            </div>
            <div>
                <a href="admins_logout.php" class="logout-btn">
                    <i class="fas fa-sign-out-alt me-2"></i>Déconnexion
                </a>
            </div>
        </div>

        <div id="dashboard" class="content-section active">
            <div class="row">
                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="stats-card">
                        <div class="stats-icon">
                            <i class="fas fa-box-open"></i>
                        </div>
                        <div class="stats-number"><?php echo $stats['total_orders']; ?></div>
                        <div class="stats-label">Total Commandes</div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="stats-card <?php echo $stats['pending_orders'] > 0 ? 'pulse' : ''; ?>">
                        <div class="stats-icon" style="background: var(--warning-gradient); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div class="stats-number" style="background: var(--warning-gradient); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;"><?php echo $stats['pending_orders']; ?></div>
                        <div class="stats-label">En Attente</div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="stats-card">
                        <div class="stats-icon" style="background: var(--success-gradient); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div class="stats-number" style="background: var(--success-gradient); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;"><?php echo $stats['completed_orders']; ?></div>
                        <div class="stats-label">Terminées</div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="stats-card">
                        <div class="stats-icon" style="background: var(--danger-gradient); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;">
                            <i class="fas fa-times-circle"></i>
                        </div>
                        <div class="stats-number" style="background: var(--danger-gradient); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;"><?php echo $stats['cancelled_orders']; ?></div>
                        <div class="stats-label">Annulées</div>
                    </div>
                </div>
            </div>

            <div class="row mt-4">
                <div class="col-md-7">
                    <div class="chart-container">
                        <h5 class="chart-title">Statistiques des Commandes</h5>
                        <canvas id="ordersBarChart"></canvas>
                    </div>
                </div>
                <div class="col-md-5">
                    <div class="chart-container">
                        <h5 class="chart-title">Statut des Livreurs</h5>
                        <canvas id="deliveryPieChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <div id="orders" class="content-section">
            <h4 class="section-title">
                <i class="fas fa-list-ul"></i>
                Commandes Récentes
            </h4>
            <div class="table-container">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>N° Commande</th>
                            <th>Client</th>
                            <th>Type</th>
                            <th>Demande</th>
                            <th>Livreur</th>
                            <th>Statut</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($recent_orders as $order): ?>
                        <tr>
                            <td><strong>#<?php echo htmlspecialchars($order['id']); ?></strong></td>
                            <td><?php echo htmlspecialchars($order['nom'] . ' ' . $order['prenom']); ?></td>
                            <td><?php echo htmlspecialchars($order['type_commande']); ?></td>
                            <td title="<?php echo htmlspecialchars($order['demande_exacte']); ?>"><?php echo substr(htmlspecialchars($order['demande_exacte']), 0, 30) . '...'; ?></td>
                            <td><?php echo $order['nom_livreur'] ? htmlspecialchars($order['nom_livreur']) : '<em>Non assigné</em>'; ?></td>
                            <td>
                                <?php 
                                $status_classes = [
                                    'en attente' => 'bg-warning text-dark',
                                    'terminee' => 'bg-success text-white',
                                    'annulee' => 'bg-danger text-white'
                                ];
                                $status_text = [
                                    'en attente' => 'En attente',
                                    'terminee' => 'Terminée',
                                    'annulee' => 'Annulée'
                                ];
                                ?>
                                <span class="badge <?php echo $status_classes[$order['statut']] ?? 'bg-secondary text-white'; ?> badge-status">
                                    <?php echo $status_text[$order['statut']] ?? htmlspecialchars($order['statut']); ?>
                                </span>
                            </td>
                            <td><?php echo date('d/m/Y H:i', strtotime($order['date_commande'])); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <div id="delivery" class="content-section">
            <h4 class="section-title">
                <i class="fas fa-motorcycle"></i>
                Gestion des Livreurs
            </h4>
            <div class="table-container">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nom du Livreur</th>
                            <th>Téléphone</th>
                            <th>Statut</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($delivery_staff as $livreur): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($livreur['id']); ?></td>
                            <td><?php echo htmlspecialchars($livreur['nom_livreur']); ?></td>
                            <td><?php echo htmlspecialchars($livreur['telephone']); ?></td>
                            <td>
                                <?php
                                $livreur_status_classes = [
                                    'actif' => 'bg-success text-white',
                                    'inactif' => 'bg-secondary text-white',
                                    'en conge' => 'bg-info text-dark'
                                ];
                                ?>
                                <span class="badge <?php echo $livreur_status_classes[$livreur['statut']] ?? 'bg-dark text-white'; ?> badge-status">
                                    <?php echo htmlspecialchars(ucfirst($livreur['statut'])); ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div id="clients" class="content-section">
            <h4 class="section-title">
                <i class="fas fa-users"></i>
                Liste des Clients
            </h4>
            <div class="table-container">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nom & Prénom</th>
                            <th>Email</th>
                            <th>Téléphone</th>
                            <th>Adresse</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($clients_inf as $client): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($client['id']); ?></td>
                            <td><?php echo htmlspecialchars($client['nom'] . ' ' . $client['prenom']); ?></td>
                            <td><?php echo htmlspecialchars($client['email']); ?></td>
                            <td><?php echo htmlspecialchars($client['telephone']); ?></td>
                            <td><?php echo htmlspecialchars($client['adresse']); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const sidebar = document.getElementById('sidebar');
        const mainContent = document.getElementById('mainContent');

        // Function for desktop toggle (collapse/expand)
        function toggleSidebar() {
            sidebar.classList.toggle('collapsed');
            mainContent.classList.toggle('expanded');
        }

        // NEW: Function for mobile toggle (show/hide)
        function toggleMobileSidebar() {
            sidebar.classList.toggle('mobile-open');
        }

        function showSection(sectionId, element) {
            // Hide all sections
            document.querySelectorAll('.content-section').forEach(section => {
                section.classList.remove('active');
            });
            // Show the target section
            document.getElementById(sectionId).classList.add('active');
            
            // Update active state on menu items
            document.querySelectorAll('.menu-item').forEach(item => {
                item.classList.remove('active');
            });
            element.classList.add('active');
            
            // On mobile, hide sidebar after clicking a menu item
            if (window.innerWidth < 992) {
                sidebar.classList.remove('mobile-open');
            }
        }

        document.addEventListener('DOMContentLoaded', function () {
            // Data from PHP
            const orderStatsData = {
                pending: <?php echo $stats['pending_orders']; ?>,
                completed: <?php echo $stats['completed_orders']; ?>,
                cancelled: <?php echo $stats['cancelled_orders']; ?>
            };
            
            const livreurStatsData = <?php echo json_encode($livreur_stats); ?>;

            // Bar Chart for Orders
            const ctxBar = document.getElementById('ordersBarChart').getContext('2d');
            new Chart(ctxBar, {
                type: 'bar',
                data: {
                    labels: ['En Attente', 'Terminées', 'Annulées'],
                    datasets: [{
                        label: 'Nombre de Commandes',
                        data: [orderStatsData.pending, orderStatsData.completed, orderStatsData.cancelled],
                        backgroundColor: [
                            'rgba(255, 193, 7, 0.6)',
                            'rgba(75, 192, 192, 0.6)',
                            'rgba(255, 99, 132, 0.6)'
                        ],
                        borderColor: [
                            'rgba(255, 193, 7, 1)',
                            'rgba(75, 192, 192, 1)',
                            'rgba(255, 99, 132, 1)'
                        ],
                        borderWidth: 1,
                        borderRadius: 10
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });

            // Pie Chart for Delivery Staff
            const ctxPie = document.getElementById('deliveryPieChart').getContext('2d');
            new Chart(ctxPie, {
                type: 'doughnut',
                data: {
                    labels: Object.keys(livreurStatsData),
                    datasets: [{
                        label: 'Statut des Livreurs',
                        data: Object.values(livreurStatsData),
                        backgroundColor: [
                            'rgba(75, 192, 192, 0.7)',
                            'rgba(153, 102, 255, 0.7)',
                            'rgba(255, 159, 64, 0.7)',
                            'rgba(255, 99, 132, 0.7)'
                        ],
                        borderColor: '#fff',
                        borderWidth: 2
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            position: 'top',
                        }
                    }
                }
            });
        });
    </script>
</body>
</html>