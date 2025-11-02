<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DZIKO-MOH-LIVRAISON</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --secondary-gradient: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            --success-gradient: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            --dark-bg: #0a0a0f;
            --card-bg: rgba(255, 255, 255, 0.03);
            --glass-border: rgba(255, 255, 255, 0.1);
            --text-primary: #ffffff;
            --text-secondary: rgba(255, 255, 255, 0.7);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: var(--dark-bg);
            color: var(--text-primary);
            overflow-x: hidden;
            line-height: 1.6;
        }

        /* Modern Glassmorphism Navbar */
        .navbar {
            background: rgba(10, 10, 15, 0.8);
            backdrop-filter: blur(20px);
            border-bottom: 1px solid var(--glass-border);
            padding: 1rem 0;
            position: fixed;
            width: 100%;
            top: 0;
            z-index: 1000;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .navbar.scrolled {
            background: rgba(10, 10, 15, 0.95);
            padding: 0.5rem 0;
        }

        .navbar-brand {
            font-weight: 900;
            font-size: 1.8rem;
            background: var(--primary-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .nav-link {
            color: var(--text-secondary) !important;
            font-weight: 500;
            margin: 0 0.5rem;
            padding: 0.5rem 1rem !important;
            border-radius: 8px;
            transition: all 0.3s ease;
            position: relative;
        }

        .nav-link:hover {
            color: var(--text-primary) !important;
            background: rgba(255, 255, 255, 0.05);
        }

        .nav-link.admin-link {
            background: var(--primary-gradient);
            color: white !important;
            border-radius: 20px;
            font-weight: 600;
        }

        .nav-link.admin-link:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 30px rgba(102, 126, 234, 0.4);
        }

        /* Hero Section avec animations 3D */
        .hero-section {
            min-height: 100vh;
            display: flex;
            align-items: center;
            background: 
                radial-gradient(circle at 20% 50%, rgba(120, 119, 198, 0.3) 0%, transparent 50%),
                radial-gradient(circle at 80% 20%, rgba(255, 119, 198, 0.3) 0%, transparent 50%),
                radial-gradient(circle at 40% 80%, rgba(120, 219, 255, 0.3) 0%, transparent 50%),
                var(--dark-bg);
            position: relative;
            overflow: hidden;
        }

        .hero-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.02'%3E%3Cpath d='m36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E") repeat;
            opacity: 0.3;
            animation: float 20s linear infinite;
        }

        @keyframes float {
            0% { transform: translateX(0) translateY(0); }
            100% { transform: translateX(-60px) translateY(-60px); }
        }

        .hero-content {
            position: relative;
            z-index: 2;
        }

        .hero-title {
            font-size: clamp(3rem, 8vw, 5.5rem);
            font-weight: 900;
            line-height: 1.1;
            margin-bottom: 2rem;
            background: linear-gradient(135deg, #fff 0%, #667eea 50%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            letter-spacing: -0.02em;
        }

        .hero-subtitle {
            font-size: 1.25rem;
            color: var(--text-secondary);
            margin-bottom: 3rem;
            max-width: 600px;
            font-weight: 400;
        }

        .cta-buttons {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .btn-primary-gradient {
            background: var(--primary-gradient);
            border: none;
            color: white;
            font-weight: 700;
            padding: 1rem 2.5rem;
            border-radius: 50px;
            font-size: 1.1rem;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 10px 40px rgba(102, 126, 234, 0.3);
            position: relative;
            overflow: hidden;
        }

        .btn-primary-gradient::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
            transition: left 0.6s;
        }

        .btn-primary-gradient:hover {
            transform: translateY(-3px);
            box-shadow: 0 20px 60px rgba(102, 126, 234, 0.4);
        }

        .btn-primary-gradient:hover::before {
            left: 100%;
        }

        .btn-outline-gradient {
            background: transparent;
            border: 2px solid;
            border-image: linear-gradient(135deg, #667eea, #764ba2) 1;
            color: var(--text-primary);
            font-weight: 600;
            padding: 1rem 2.5rem;
            border-radius: 50px;
            transition: all 0.3s ease;
        }

        .btn-outline-gradient:hover {
            background: var(--primary-gradient);
            color: white;
            transform: translateY(-2px);
        }

        /* Hero Visual avec éléments 3D */
        .hero-visual {
            position: relative;
            height: 600px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .delivery-illustration {
            position: relative;
            width: 400px;
            height: 400px;
            margin: 0 auto;
        }

        .main-circle {
            width: 300px;
            height: 300px;
            background: var(--primary-gradient);
            border-radius: 50%;
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 30px 80px rgba(102, 126, 234, 0.4);
            animation: pulse 4s ease-in-out infinite;
        }

        @keyframes pulse {
            0%, 100% { transform: translate(-50%, -50%) scale(1); }
            50% { transform: translate(-50%, -50%) scale(1.05); }
        }

        .main-circle i {
            font-size: 4rem;
            color: white;
        }

        .floating-element {
            position: absolute;
            width: 80px;
            height: 80px;
            background: var(--card-bg);
            backdrop-filter: blur(20px);
            border: 1px solid var(--glass-border);
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            animation: orbit 8s linear infinite;
        }

        .floating-element:nth-child(2) {
            top: 10%;
            right: 10%;
            color: #4ade80;
            animation-delay: 0s;
        }

        .floating-element:nth-child(3) {
            bottom: 20%;
            left: 5%;
            color: #60a5fa;
            animation-delay: 2.67s;
        }

        .floating-element:nth-child(4) {
            top: 60%;
            right: 5%;
            color: #fb7185;
            animation-delay: 5.33s;
        }

        @keyframes orbit {
            0% { transform: rotate(0deg) translateX(150px) rotate(0deg); }
            100% { transform: rotate(360deg) translateX(150px) rotate(-360deg); }
        }

        /* Stats Section */
        .stats-section {
            padding: 4rem 0;
            background: rgba(255, 255, 255, 0.02);
            border-top: 1px solid var(--glass-border);
            border-bottom: 1px solid var(--glass-border);
        }

        .stat-item {
            text-align: center;
            padding: 2rem;
        }

        .stat-number {
            font-size: 3rem;
            font-weight: 900;
            background: var(--primary-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            display: block;
        }

        .stat-label {
            color: var(--text-secondary);
            font-weight: 500;
            margin-top: 0.5rem;
        }

        /* Services Section */
        .services-section {
            padding: 8rem 0;
            position: relative;
        }

        .section-title {
            font-size: clamp(2.5rem, 6vw, 4rem);
            font-weight: 800;
            text-align: center;
            margin-bottom: 1rem;
            background: linear-gradient(135deg, #fff 0%, #667eea 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .section-subtitle {
            text-align: center;
            color: var(--text-secondary);
            font-size: 1.25rem;
            margin-bottom: 5rem;
            max-width: 600px;
            margin-left: auto;
            margin-right: auto;
        }

        .service-card {
            background: var(--card-bg);
            backdrop-filter: blur(20px);
            border: 1px solid var(--glass-border);
            border-radius: 24px;
            padding: 3rem 2rem;
            text-align: center;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
            height: 100%;
        }

        .service-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 2px;
            background: var(--primary-gradient);
            transform: scaleX(0);
            transition: transform 0.3s ease;
        }

        .service-card:hover {
            transform: translateY(-10px);
            background: rgba(255, 255, 255, 0.08);
            border-color: rgba(102, 126, 234, 0.3);
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        }

        .service-card:hover::before {
            transform: scaleX(1);
        }

        .service-icon {
            width: 80px;
            height: 80px;
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 2rem;
            font-size: 2rem;
            transition: all 0.3s ease;
        }

        .service-card:nth-child(1) .service-icon {
            background: linear-gradient(135deg, #4ade80, #22c55e);
            color: white;
        }

        .service-card:nth-child(2) .service-icon {
            background: linear-gradient(135deg, #60a5fa, #3b82f6);
            color: white;
        }

        .service-card:nth-child(3) .service-icon {
            background: linear-gradient(135deg, #fb7185, #f43f5e);
            color: white;
        }

        .service-card:hover .service-icon {
            transform: scale(1.1) rotate(5deg);
        }

        .service-card h3 {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 1rem;
            color: var(--text-primary);
        }

        .service-card p {
            color: var(--text-secondary);
            line-height: 1.6;
        }

        /* Process Section */
        .process-section {
            padding: 8rem 0;
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.05) 0%, rgba(118, 75, 162, 0.05) 100%);
        }

        .process-step {
            text-align: center;
            position: relative;
            margin-bottom: 3rem;
        }

        .step-number {
            width: 60px;
            height: 60px;
            background: var(--primary-gradient);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
            font-size: 1.5rem;
            font-weight: 700;
            color: white;
        }

        .step-title {
            font-size: 1.25rem;
            font-weight: 600;
            margin-bottom: 1rem;
            color: var(--text-primary);
        }

        .step-description {
            color: var(--text-secondary);
        }

        /* About Section */
        .about-section {
            padding: 8rem 0;
            background: var(--dark-bg);
        }

        .about-content {
            max-width: 800px;
            margin: 0 auto;
            text-align: center;
        }

        .about-lead {
            font-size: 1.5rem;
            color: var(--text-secondary);
            margin-bottom: 3rem;
            line-height: 1.6;
        }

        /* Footer */
        .footer {
            background: rgba(0, 0, 0, 0.5);
            border-top: 1px solid var(--glass-border);
            padding: 3rem 0;
            text-align: center;
        }

        .footer-content {
            color: var(--text-secondary);
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .cta-buttons {
                flex-direction: column;
                align-items: center;
            }

            .btn-primary-gradient,
            .btn-outline-gradient {
                width: 100%;
                max-width: 300px;
            }

            .delivery-illustration {
                width: 300px;
                height: 300px;
            }

            .main-circle {
                width: 200px;
                height: 200px;
            }

            .floating-element {
                width: 60px;
                height: 60px;
                font-size: 1.2rem;
            }

            .service-card {
                margin-bottom: 2rem;
            }

            .stats-section .row > div {
                margin-bottom: 2rem;
            }
        }

        /* Scroll animations */
        .fade-in-up {
            opacity: 0;
            transform: translateY(30px);
            transition: all 0.6s ease;
        }

        .fade-in-up.visible {
            opacity: 1;
            transform: translateY(0);
        }

        /* Loading animation */
        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: var(--dark-bg);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 9999;
            transition: opacity 0.5s ease;
        }

        .loading-spinner {
            width: 50px;
            height: 50px;
            border: 3px solid rgba(102, 126, 234, 0.3);
            border-top: 3px solid #667eea;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <!-- Loading Overlay -->
    <div class="loading-overlay" id="loadingOverlay">
        <div class="loading-spinner"></div>
    </div>

    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-bolt me-2"></i>DZIKO-MOH-LIVRAISON
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <i class="fas fa-bars text-white"></i>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="#home">Accueil</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#services">Services</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#process">Comment ça marche</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#about">À Propos</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="commander.php">Commander</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link admin-link" href="admins_login.php">
                            <i class="fas fa-user-shield me-1"></i>Admins
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section id="home" class="hero-section">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6">
                    <div class="hero-content fade-in-up">
                        <h1 class="hero-title">Livraison Ultra-Rapide en 30 Minutes</h1>
                        <p class="hero-subtitle">
                            Découvrez l'avenir de la livraison express. Commandez maintenant et recevez vos produits en un temps record, où que vous soyez.
                        </p>
                        <div class="cta-buttons">
                            <a href="commander.php" class="btn btn-primary-gradient">
                                <i class="fas fa-rocket me-2"></i>Commander Maintenant
                            </a>
                            <a href="#services" class="btn btn-outline-gradient">
                                Découvrir nos Services
                            </a>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="hero-visual fade-in-up">
                        <div class="delivery-illustration">
                            <div class="main-circle">
                                <i class="fas fa-shipping-fast"></i>
                            </div>
                            <div class="floating-element">
                                <i class="fas fa-clock"></i>
                            </div>
                            <div class="floating-element">
                                <i class="fas fa-box"></i>
                            </div>
                            <div class="floating-element">
                                <i class="fas fa-motorcycle"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Stats Section -->
    <section class="stats-section">
        <div class="container">
            <div class="row">
                <div class="col-md-3 col-sm-6">
                    <div class="stat-item fade-in-up">
                        <span class="stat-number" data-count="10000">0</span>
                        <div class="stat-label">Livraisons Réalisées</div>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6">
                    <div class="stat-item fade-in-up">
                        <span class="stat-number" data-count="30">0</span>
                        <div class="stat-label">Minutes Moyennes</div>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6">
                    <div class="stat-item fade-in-up">
                        <span class="stat-number" data-count="99">0</span>
                        <div class="stat-label">% de Satisfaction</div>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6">
                    <div class="stat-item fade-in-up">
                        <span class="stat-number" data-count="24">0</span>
                        <div class="stat-label">Heures / 7 Jours</div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Services Section -->
    <section id="services" class="services-section">
        <div class="container">
            <div class="fade-in-up">
                <h2 class="section-title">Nos Services Premium</h2>
                <p class="section-subtitle">
                    Une gamme complète de services de livraison adaptés à tous vos besoins quotidiens
                </p>
            </div>
            
            <div class="row">
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="service-card fade-in-up">
                        <div class="service-icon">
                            <i class="fas fa-book"></i>
                        </div>
                        <h3>Livres & Papeterie</h3>
                        <p>Tous vos livres scolaires, universitaires, romans et fournitures de bureau livrés rapidement et en parfait état.</p>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="service-card fade-in-up">
                        <div class="service-icon">
                            <i class="fas fa-apple-alt"></i>
                        </div>
                        <h3>Courses & Alimentaire</h3>
                        <p>Fruits frais, légumes, viandes, produits laitiers et épicerie fine directement de nos partenaires à votre porte.</p>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="service-card fade-in-up">
                        <div class="service-icon">
                            <i class="fas fa-gift"></i>
                        </div>
                        <h3>Objets Personnels</h3>
                        <p>Envoyez des cadeaux, documents importants ou objets personnels à vos proches en toute sécurité.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Process Section -->
    <section id="process" class="process-section">
        <div class="container">
            <div class="fade-in-up">
                <h2 class="section-title">Comment Ça Marche</h2>
                <p class="section-subtitle">
                    Un processus simple et efficace en 3 étapes seulement
                </p>
            </div>
            
            <div class="row">
                <div class="col-lg-4 col-md-6">
                    <div class="process-step fade-in-up">
                        <div class="step-number">1</div>
                        <h3 class="step-title">Passez Votre Commande</h3>
                        <p class="step-description">
                            Sélectionnez vos produits en ligne ou via notre application mobile intuitive.
                        </p>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6">
                    <div class="process-step fade-in-up">
                        <div class="step-number">2</div>
                        <h3 class="step-title">Suivi en Temps Réel</h3>
                        <p class="step-description">
                            Suivez votre livreur en direct grâce à notre système de géolocalisation avancé.
                        </p>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6">
                    <div class="process-step fade-in-up">
                        <div class="step-number">3</div>
                        <h3 class="step-title">Livraison Express</h3>
                        <p class="step-description">
                            Recevez vos produits en 30 minutes maximum, directement à votre domicile.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- About Section -->
    <section id="about" class="about-section">
        <div class="container">
            <div class="about-content fade-in-up">
                <h2 class="section-title">Notre Mission</h2>
                <p class="about-lead">
                    Révolutionner l'expérience de livraison en combinant technologie de pointe, 
                    service client exceptionnel et réseau de livreurs professionnels pour créer 
                    la solution de livraison la plus rapide et fiable du marché.
                </p>
                <p style="color: var(--text-secondary); font-size: 1.1rem;">
                    Avec notre plateforme intelligente et notre engagement envers l'excellence, 
                    nous connectons les gens à ce dont ils ont besoin, quand ils en ont besoin, 
                    où qu'ils soient.
                </p>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <p>&copy; <?php echo date("Y"); ?> DZIKO-MOH-LIVRAISON. Tous droits réservés. | Conçu avec ❤️ pour une expérience exceptionnelle</p>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Loading animation
        window.addEventListener('load', function() {
            setTimeout(() => {
                document.getElementById('loadingOverlay').style.opacity = '0';
                setTimeout(() => {
                    document.getElementById('loadingOverlay').style.display = 'none';
                }, 500);
            }, 1000);
        });

        // Navbar scroll effect
        window.addEventListener('scroll', function() {
            const navbar = document.querySelector('.navbar');
            if (window.scrollY > 50) {
                navbar.classList.add('scrolled');
            } else {
                navbar.classList.remove('scrolled');
            }
        });

        // Smooth scrolling for navigation links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });

        // Intersection Observer for animations
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };

        const observer = new IntersectionObserver(function(entries) {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('visible');
                }
            });
        }, observerOptions);

        // Observe all elements with fade-in-up class
        document.querySelectorAll('.fade-in-up').forEach(el => {
            observer.observe(el);
        });

        // Counter animation for stats
        function animateCounter(element, target) {
            let count = 0;
            const increment = target / 100;
            const timer = setInterval(() => {
                count += increment;
                if (count >= target) {
                    count = target;
                    clearInterval(timer);
                }
                element.textContent = Math.floor(count);
            }, 20);
        }

        // Stats counter observer
        const statsObserver = new IntersectionObserver(function(entries) {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const target = parseInt(entry.target.getAttribute('data-count'));
                    animateCounter(entry.target, target);
                    statsObserver.unobserve(entry.target);
                }
            });
        }, { threshold: 0.5 });

        // Observe all stat numbers
        document.querySelectorAll('[data-count]').forEach(el => {
            statsObserver.observe(el);
        });

        // Add parallax effect to hero section
        window.addEventListener('scroll', function() {
            const scrolled = window.pageYOffset;
            const heroSection = document.querySelector('.hero-section');
            if (heroSection) {
                heroSection.style.transform = `translateY(${scrolled * 0.5}px)`;
            }
        });

        // Mobile menu enhancement
        const navbarToggler = document.querySelector('.navbar-toggler');
        const navbarCollapse = document.querySelector('.navbar-collapse');
        
        if (navbarToggler && navbarCollapse) {
            navbarToggler.addEventListener('click', function() {
                this.classList.toggle('active');
            });

            // Close mobile menu when clicking on a link
            document.querySelectorAll('.nav-link').forEach(link => {
                link.addEventListener('click', function() {
                    if (window.innerWidth < 992) {
                        navbarCollapse.classList.remove('show');
                        navbarToggler.classList.remove('active');
                    }
                });
            });
        }

        // Add hover effects for service cards
        document.querySelectorAll('.service-card').forEach(card => {
            card.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-15px) scale(1.02)';
            });
            
            card.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0) scale(1)';
            });
        });

        // Floating elements animation enhancement
        document.querySelectorAll('.floating-element').forEach((element, index) => {
            element.style.animationDelay = `${index * 2.67}s`;
            
            // Add random float animation
            setInterval(() => {
                const randomY = Math.random() * 10 - 5;
                const randomX = Math.random() * 10 - 5;
                element.style.transform += ` translate(${randomX}px, ${randomY}px)`;
                
                setTimeout(() => {
                    element.style.transform = element.style.transform.replace(/ translate\([^)]*\)/g, '');
                }, 2000);
            }, 3000 + index * 1000);
        });

        // Add typing effect to hero title
        function typeWriter(element, text, speed = 100) {
            let i = 0;
            element.innerHTML = '';
            
            function type() {
                if (i < text.length) {
                    element.innerHTML += text.charAt(i);
                    i++;
                    setTimeout(type, speed);
                }
            }
            
            type();
        }

        // Initialize typing effect after page load
        setTimeout(() => {
            const heroTitle = document.querySelector('.hero-title');
            if (heroTitle) {
                const originalText = heroTitle.textContent;
                typeWriter(heroTitle, originalText, 50);
            }
        }, 1500);

        // Add particle effect to background
        function createParticles() {
            const particleContainer = document.createElement('div');
            particleContainer.style.cssText = `
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                pointer-events: none;
                z-index: 1;
                overflow: hidden;
            `;
            
            document.body.appendChild(particleContainer);
            
            for (let i = 0; i < 50; i++) {
                const particle = document.createElement('div');
                particle.style.cssText = `
                    position: absolute;
                    width: 2px;
                    height: 2px;
                    background: rgba(102, 126, 234, 0.3);
                    border-radius: 50%;
                    animation: float ${Math.random() * 10 + 10}s linear infinite;
                    left: ${Math.random() * 100}%;
                    top: ${Math.random() * 100}%;
                    animation-delay: ${Math.random() * 10}s;
                `;
                
                particleContainer.appendChild(particle);
            }
        }

        // Initialize particles
        setTimeout(createParticles, 2000);

        // Add scroll progress indicator
        function addScrollProgress() {
            const progressBar = document.createElement('div');
            progressBar.style.cssText = `
                position: fixed;
                top: 0;
                left: 0;
                width: 0%;
                height: 3px;
                background: linear-gradient(90deg, #667eea, #764ba2);
                z-index: 9999;
                transition: width 0.3s ease;
            `;
            
            document.body.appendChild(progressBar);
            
            window.addEventListener('scroll', () => {
                const winScroll = document.body.scrollTop || document.documentElement.scrollTop;
                const height = document.documentElement.scrollHeight - document.documentElement.clientHeight;
                const scrolled = (winScroll / height) * 100;
                progressBar.style.width = scrolled + '%';
            });
        }

        // Initialize scroll progress
        addScrollProgress();

        // Enhanced button interactions
        document.querySelectorAll('.btn-primary-gradient, .btn-outline-gradient').forEach(btn => {
            btn.addEventListener('click', function(e) {
                // Create ripple effect
                const ripple = document.createElement('span');
                const rect = this.getBoundingClientRect();
                const size = Math.max(rect.width, rect.height);
                const x = e.clientX - rect.left - size / 2;
                const y = e.clientY - rect.top - size / 2;
                
                ripple.style.cssText = `
                    position: absolute;
                    width: ${size}px;
                    height: ${size}px;
                    left: ${x}px;
                    top: ${y}px;
                    background: rgba(255, 255, 255, 0.3);
                    border-radius: 50%;
                    transform: scale(0);
                    animation: ripple 0.6s linear;
                    pointer-events: none;
                `;
                
                this.style.position = 'relative';
                this.style.overflow = 'hidden';
                this.appendChild(ripple);
                
                setTimeout(() => {
                    ripple.remove();
                }, 600);
            });
        });

        // Add ripple animation keyframes
        const style = document.createElement('style');
        style.textContent = `
            @keyframes ripple {
                to {
                    transform: scale(4);
                    opacity: 0;
                }
            }
        `;
        document.head.appendChild(style);
    </script>
</body>
</html>