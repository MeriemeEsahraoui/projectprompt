<?php
// Database configuration
$host = 'localhost:3307';
$dbname = 'property_db';
$username = 'root';
$password = '';

try {
    // Create PDO connection
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Get statistics for the About Us page
    $stats = [];
    
    // Total properties
    $stmt = $pdo->prepare("SELECT COUNT(*) as total_properties FROM property");
    $stmt->execute();
    $stats['properties'] = $stmt->fetch(PDO::FETCH_ASSOC)['total_properties'];
    
    // Active properties
    $stmt = $pdo->prepare("SELECT COUNT(*) as active_properties FROM property WHERE status = 0");
    $stmt->execute();
    $stats['active_properties'] = $stmt->fetch(PDO::FETCH_ASSOC)['active_properties'];
    
    // Average price
    $stmt = $pdo->prepare("SELECT AVG(price) as avg_price FROM property WHERE status = 0");
    $stmt->execute();
    $stats['avg_price'] = $stmt->fetch(PDO::FETCH_ASSOC)['avg_price'];
    
    // Property types count
    $stmt = $pdo->prepare("SELECT type, COUNT(*) as count FROM property WHERE status = 0 GROUP BY type");
    $stmt->execute();
    $property_types = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch(PDOException $e) {
    $error_message = "Connection failed: " . $e->getMessage();
    $stats = ['properties' => 0, 'active_properties' => 0, 'avg_price' => 0];
    $property_types = [];
}

// Helper function to format price
function formatPrice($price) {
    if ($price >= 1000000) {
        return '$' . number_format($price / 1000000, 1) . 'M';
    } elseif ($price >= 1000) {
        return '$' . number_format($price / 1000, 0) . 'K';
    } else {
        return '$' . number_format($price);
    }
}
include('include/head.php');
?>
<body>
      <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            background-color: #f8f9fa;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

        /* Header Navigation */
        .header-nav {
            background: linear-gradient(135deg, #1e3a5f 0%, #2c5282 100%);
            padding: 1rem 0;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .nav-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

        .logo {
            display: flex;
            align-items: center;
            color: white;
            font-size: 1.5rem;
            font-weight: bold;
            text-decoration: none;
        }

        .logo i {
            margin-right: 0.5rem;
            color: #60a5fa;
        }

        .nav-links {
            display: flex;
            list-style: none;
            gap: 2rem;
        }

        .nav-links a {
            color: white;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s ease;
            padding: 0.5rem 1rem;
            border-radius: 5px;
        }

        .nav-links a:hover,
        .nav-links a.active {
            background: rgba(255,255,255,0.1);
            color: #60a5fa;
        }

        /* Hero Section */
        .hero-section {
            background: linear-gradient(135deg, rgba(30, 58, 95, 0.9), rgba(44, 82, 130, 0.9)),
                        url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1000 300"><polygon fill="%23ffffff10" points="0,300 50,250 100,280 150,220 200,260 250,200 300,240 350,180 400,220 450,160 500,200 550,140 600,180 650,120 700,160 750,100 800,140 850,80 900,120 950,60 1000,100 1000,300"/></svg>') center/cover;
            color: white;
            padding: 6rem 0;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .hero-content h1 {
            font-size: 3.5rem;
            margin-bottom: 1rem;
            font-weight: 700;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
        }

        .hero-content p {
            font-size: 1.3rem;
            opacity: 0.9;
            max-width: 600px;
            margin: 0 auto;
        }

        /* Main Content */
        .main-content {
            padding: 4rem 0;
        }

        .section {
            margin-bottom: 4rem;
        }

        .section-title {
            font-size: 2.5rem;
            color: #1e3a5f;
            text-align: center;
            margin-bottom: 3rem;
            position: relative;
        }

        .section-title::after {
            content: '';
            position: absolute;
            bottom: -10px;
            left: 50%;
            transform: translateX(-50%);
            width: 80px;
            height: 3px;
            background: linear-gradient(135deg, #60a5fa, #2c5282);
            border-radius: 2px;
        }

        /* About Content */
        .about-content {
            background: white;
            border-radius: 15px;
            padding: 3rem;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            margin-bottom: 3rem;
        }

        .about-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 3rem;
            align-items: center;
        }

        .about-text h3 {
            color: #1e3a5f;
            font-size: 1.8rem;
            margin-bottom: 1rem;
        }

        .about-text p {
            color: #666;
            font-size: 1.1rem;
            margin-bottom: 1.5rem;
            text-align: justify;
        }

        .about-image {
            position: relative;
            border-radius: 15px;
            overflow: hidden;
            height: 300px;
            background: linear-gradient(135deg, #60a5fa, #2c5282);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 4rem;
            color: white;
        }

        /* Statistics */
        .stats-section {
            background: linear-gradient(135deg, #1e3a5f 0%, #2c5282 100%);
            color: white;
            padding: 4rem 0;
            margin: 4rem 0;
            border-radius: 15px;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 2rem;
            text-align: center;
        }

        .stat-item {
            padding: 2rem;
        }

        .stat-icon {
            font-size: 3rem;
            color: #60a5fa;
            margin-bottom: 1rem;
        }

        .stat-number {
            font-size: 2.5rem;
            font-weight: bold;
            color: white;
            display: block;
            margin-bottom: 0.5rem;
        }

        .stat-label {
            font-size: 1.1rem;
            opacity: 0.9;
        }

        /* Mission & Vision */
        .mission-vision {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 3rem;
            margin-bottom: 4rem;
        }

        .mission-box, .vision-box {
            background: white;
            border-radius: 15px;
            padding: 2.5rem;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            text-align: center;
            transition: transform 0.3s ease;
        }

        .mission-box:hover, .vision-box:hover {
            transform: translateY(-5px);
        }

        .mission-box h3, .vision-box h3 {
            color: #1e3a5f;
            font-size: 1.8rem;
            margin-bottom: 1.5rem;
        }

        .mission-box .icon, .vision-box .icon {
            font-size: 3rem;
            color: #60a5fa;
            margin-bottom: 1rem;
        }

        /* Team Section */
        .team-section {
            background: #f8f9fa;
            padding: 4rem 0;
            border-radius: 15px;
        }

        .team-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
            padding: 0 2rem;
        }

        .team-member {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            text-align: center;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
        }

        .team-member:hover {
            transform: translateY(-10px);
        }

        .member-photo {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background: linear-gradient(135deg, #60a5fa, #2c5282);
            margin: 0 auto 1.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.5rem;
            color: white;
            font-weight: bold;
            border: 4px solid #e3f2fd;
        }

        .member-name {
            font-size: 1.5rem;
            color: #1e3a5f;
            margin-bottom: 0.5rem;
            font-weight: 600;
        }

        .member-role {
            color: #60a5fa;
            font-weight: 600;
            margin-bottom: 1rem;
            font-size: 1.1rem;
        }

        .member-description {
            color: #666;
            font-size: 0.95rem;
            line-height: 1.6;
        }

        /* Values Section */
        .values-section {
            margin-top: 4rem;
        }

        .values-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 2rem;
        }

        .value-item {
            background: white;
            border-radius: 15px;
            padding: 2.5rem;
            text-align: center;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
        }

        .value-item:hover {
            transform: translateY(-5px);
        }

        .value-icon {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: linear-gradient(135deg, #60a5fa, #2c5282);
            margin: 0 auto 1.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            color: white;
        }

        .value-title {
            font-size: 1.5rem;
            color: #1e3a5f;
            margin-bottom: 1rem;
            font-weight: 600;
        }

        .value-description {
            color: #666;
            line-height: 1.6;
        }

        /* Property Types */
        .property-types {
            background: white;
            border-radius: 15px;
            padding: 3rem;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            margin-top: 3rem;
        }

        .types-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 2rem;
            margin-top: 2rem;
        }

        .type-item {
            text-align: center;
            padding: 1.5rem;
            border: 2px solid #e3f2fd;
            border-radius: 10px;
            transition: all 0.3s ease;
        }

        .type-item:hover {
            border-color: #60a5fa;
            background: #f8f9fa;
        }

        .type-icon {
            font-size: 2.5rem;
            margin-bottom: 1rem;
        }

        .type-name {
            font-size: 1.2rem;
            color: #1e3a5f;
            font-weight: 600;
            margin-bottom: 0.5rem;
            text-transform: capitalize;
        }

        .type-count {
            color: #60a5fa;
            font-weight: 600;
        }

        /* Footer */
        .footer {
            background: #1e3a5f;
            color: white;
            padding: 3rem 0 1rem;
            margin-top: 4rem;
        }

        .footer-content {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
            margin-bottom: 2rem;
        }

        .footer-section h3 {
            margin-bottom: 1rem;
            color: #60a5fa;
        }

        .footer-section p, .footer-section a {
            color: #cbd5e0;
            text-decoration: none;
            margin-bottom: 0.5rem;
            display: block;
        }

        .footer-section a:hover {
            color: #60a5fa;
        }

        .social-icons {
            display: flex;
            gap: 1rem;
            margin-top: 1rem;
        }

        .social-icon {
            width: 40px;
            height: 40px;
            background: #2c5282;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            text-decoration: none;
            transition: background 0.3s ease;
        }

        .social-icon:hover {
            background: #60a5fa;
        }

        .footer-bottom {
            border-top: 1px solid #2c5282;
            padding-top: 1rem;
            text-align: center;
            color: #cbd5e0;
        }

        /* Newsletter */
        .newsletter-form {
            display: flex;
            margin-top: 1rem;
        }

        .newsletter-form input {
            flex: 1;
            padding: 0.75rem;
            border: none;
            border-radius: 6px 0 0 6px;
            outline: none;
        }

        .newsletter-form button {
            background: #3498db;
            color: white;
            border: none;
            padding: 0.75rem 1rem;
            border-radius: 0 6px 6px 0;
            cursor: pointer;
            transition: background 0.3s ease;
        }

        .newsletter-form button:hover {
            background: #2980b9;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .hero-content h1 {
                font-size: 2.5rem;
            }

            .nav-links {
                display: none;
            }

            .about-grid {
                grid-template-columns: 1fr;
            }

            .mission-vision {
                grid-template-columns: 1fr;
            }

            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .team-grid, .values-grid {
                grid-template-columns: 1fr;
            }

            .about-content {
                padding: 2rem;
            }
        }

        .error-message {
            background: #fee2e2;
            color: #dc2626;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 2rem;
            text-align: center;
        }
    </style>
    <!-- Header Navigation -->
   <?php include('include/header.php');?>

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="container">
            <div class="hero-content">
                <h1>About Elite Properties</h1>
                <p>Découvrez notre histoire, notre mission et les valeurs qui nous guident dans l'excellence immobilière de luxe</p>
            </div>
        </div>
    </section>

    <!-- Main Content -->
    <main class="main-content">
        <div class="container">
            <?php if (isset($error_message)): ?>
                <div class="error-message">
                    <strong>Erreur de base de données:</strong> <?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>

            <!-- About Section -->
            <section class="section">
                <h2 class="section-title">Notre Histoire</h2>
                <div class="about-content">
                    <div class="about-grid">
                        <div class="about-text">
                            <h3>Depuis 2010</h3>
                            <p>
                                Fondée en 2010, Elite Properties s'est établie comme l'agence immobilière de luxe de référence dans la région. 
                                Nous nous spécialisons dans la mise en relation de clients exigeants avec des propriétés exceptionnelles qui dépassent leurs attentes.
                            </p>
                            <p>
                                Notre engagement envers l'excellence et le service personnalisé a fait de nous le choix de confiance pour les acheteurs et vendeurs de maisons de luxe.
                            </p>
                            <p>
                                Avec plus d'une décennie d'expérience sur le marché immobilier de luxe, nous comprenons qu'acheter ou vendre une maison est plus qu'une simple transaction—c'est une expérience qui change la vie.
                            </p>
                        </div>
                        <div class="about-image">
                            <i class="fas fa-building"></i>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Statistics -->
            <section class="stats-section">
                <div class="container">
                    <div class="stats-grid">
                        <div class="stat-item">
                            <div class="stat-icon">
                                <i class="fas fa-home"></i>
                            </div>
                            <span class="stat-number"><?php echo $stats['properties']; ?>+</span>
                            <span class="stat-label">Propriétés au Total</span>
                        </div>
                        <div class="stat-item">
                            <div class="stat-icon">
                                <i class="fas fa-check-circle"></i>
                            </div>
                            <span class="stat-number"><?php echo $stats['active_properties']; ?></span>
                            <span class="stat-label">Propriétés Actives</span>
                        </div>
                        <div class="stat-item">
                            <div class="stat-icon">
                                <i class="fas fa-dollar-sign"></i>
                            </div>
                            <span class="stat-number"><?php echo formatPrice($stats['avg_price']); ?></span>
                            <span class="stat-label">Prix Moyen</span>
                        </div>
                        <div class="stat-item">
                            <div class="stat-icon">
                                <i class="fas fa-award"></i>
                            </div>
                            <span class="stat-number">98%</span>
                            <span class="stat-label">Satisfaction Client</span>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Mission & Vision -->
            <section class="section">
                <div class="mission-vision">
                    <div class="mission-box">
                        <div class="icon">
                            <i class="fas fa-bullseye"></i>
                        </div>
                        <h3>Notre Mission</h3>
                        <p>
                            Fournir un service immobilier exceptionnel en connectant nos clients avec leurs propriétés de rêve, 
                            tout en maintenant les plus hauts standards d'intégrité et de professionnalisme.
                        </p>
                    </div>
                    <div class="vision-box">
                        <div class="icon">
                            <i class="fas fa-eye"></i>
                        </div>
                        <h3>Notre Vision</h3>
                        <p>
                            Être reconnu comme le leader incontournable du marché immobilier de luxe, 
                            en redéfinissant l'expérience client et en établissant de nouveaux standards d'excellence.
                        </p>
                    </div>
                </div>
            </section>

            <!-- Property Types -->
            <?php if (!empty($property_types)): ?>
            <section class="section">
                <h2 class="section-title">Types de Propriétés</h2>
                <div class="property-types">
                    <div class="types-grid">
                        <?php foreach ($property_types as $type): ?>
                            <div class="type-item">
                                <div class="type-icon">
                                    <?php
                                    switch(strtolower($type['type'])) {
                                        case 'villa': echo '🏡'; break;
                                        case 'apartment': echo '🏢'; break;
                                        case 'townhouse': echo '🏘️'; break;
                                        case 'studio': echo '🏠'; break;
                                        default: echo '🏠';
                                    }
                                    ?>
                                </div>
                                <div class="type-name"><?php echo ucfirst(htmlspecialchars($type['type'])); ?></div>
                                <div class="type-count"><?php echo $type['count']; ?> propriété(s)</div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </section>
            <?php endif; ?>

            <!-- Team Section -->
            <section class="team-section">
                <div class="container">
                    <h2 class="section-title">Notre Équipe</h2>
                    <div class="team-grid">
                        <div class="team-member">
                            <div class="member-photo">JS</div>
                            <h3 class="member-name">John Smith</h3>
                            <p class="member-role">Fondateur & PDG</p>
                            <p class="member-description">
                                Avec plus de 20 ans d'expérience dans l'immobilier de luxe, John a fondé Elite Properties 
                                avec la vision de redéfinir l'expérience client dans les transactions immobilières haut de gamme.
                            </p>
                        </div>
                        <div class="team-member">
                            <div class="member-photo">MJ</div>
                            <h3 class="member-name">Maria Johnson</h3>
                            <p class="member-role">Directrice des Ventes</p>
                            <p class="member-description">
                                Maria se spécialise dans les ventes résidentielles de luxe et a constamment été classée 
                                parmi le top 1% des agents au niveau national pendant les 8 dernières années.
                            </p>
                        </div>
                        <div class="team-member">
                            <div class="member-photo">DW</div>
                            <h3 class="member-name">David Wilson</h3>
                            <p class="member-role">Directeur de Gestion</p>
                            <p class="member-description">
                                David supervise nos services complets de gestion immobilière, garantissant des rendements 
                                optimaux et la maintenance pour nos clients investisseurs.
                            </p>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Values Section -->
            <section class="values-section">
                <h2 class="section-title">Nos Valeurs Fondamentales</h2>
                <div class="values-grid">
                    <div class="value-item">
                        <div class="value-icon">
                            <i class="fas fa-trophy"></i>
                        </div>
                        <h3 class="value-title">Excellence</h3>
                        <p class="value-description">
                            Nous visons l'excellence dans tous les aspects de notre service, 
                            de la consultation initiale à la clôture et au-delà.
                        </p>
                    </div>
                    <div class="value-item">
                        <div class="value-icon">
                            <i class="fas fa-handshake"></i>
                        </div>
                        <h3 class="value-title">Intégrité</h3>
                        <p class="value-description">
                            L'honnêteté et la transparence guident toutes nos interactions, 
                            construisant une confiance qui perdure bien après la transaction.
                        </p>
                    </div>
                    <div class="value-item">
                        <div class="value-icon">
                            <i class="fas fa-gem"></i>
                        </div>
                        <h3 class="value-title">Focus Luxe</h3>
                        <p class="value-description">
                            Notre focus exclusif sur les propriétés de luxe nous permet de fournir 
                            une expertise inégalée dans le segment haut de gamme.
                        </p>
                    </div>
                </div>
            </section>
        </div>
    </main>

    <!-- Footer -->
    <?php include('include/footer.php');?>

    <!-- JavaScript for Enhanced Interactivity -->
    <script>
        // Smooth scrolling for navigation links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                document.querySelector(this.getAttribute('href')).scrollIntoView({
                    behavior: 'smooth'
                });
            });
        });

        // Counter animation for statistics
        function animateCounters() {
            const counters = document.querySelectorAll('.stat-number');
            const speed = 200;

            counters.forEach(counter => {
                const target = parseInt(counter.innerText.replace(/[^\d]/g, ''));
                const count = +counter.innerText.replace(/[^\d]/g, '');
                const inc = target / speed;

                if (count < target) {
                    counter.innerText = Math.ceil(count + inc);
                    setTimeout(() => animateCounters(), 1);
                } else {
                    // Restore original format
                    const originalText = counter.getAttribute('data-original') || counter.innerText;
                    counter.innerText = originalText;
                }
            });
        }

        // Intersection Observer for animations
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -100px 0px'
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.opacity = '1';
                    entry.target.style.transform = 'translateY(0)';
                    
                    // Animate counters when stats section is visible
                    if (entry.target.classList.contains('stats-section')) {
                        // Store original text before animation
                        const counters = entry.target.querySelectorAll('.stat-number');
                        counters.forEach(counter => {
                            counter.setAttribute('data-original', counter.innerText);
                        });
                        setTimeout(animateCounters, 500);
                    }
                }
            });
        }, observerOptions);

        // Apply fade-in animation to sections
        document.addEventListener('DOMContentLoaded', () => {
            const sections = document.querySelectorAll('.section, .stats-section, .team-section, .mission-vision > div, .value-item');
            
            sections.forEach(section => {
                section.style.opacity = '0';
                section.style.transform = 'translateY(30px)';
                section.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
                observer.observe(section);
            });
        });

        // Newsletter form submission
        document.querySelector('.newsletter-form')?.addEventListener('submit', function(e) {
            e.preventDefault();
            const email = this.querySelector('input[type="email"]').value;
            
            if (email) {
                // Simulate subscription success
                const button = this.querySelector('button');
                const originalHTML = button.innerHTML;
                
                button.innerHTML = '✓';
                button.style.background = '#27ae60';
                
                setTimeout(() => {
                    button.innerHTML = originalHTML;
                    button.style.background = '#60a5fa';
                    this.querySelector('input[type="email"]').value = '';
                    alert('Thank you for subscribing to our newsletter!');
                }, 2000);
            } else {
                alert('Please enter a valid email address.');
            }
        });

        // Mobile menu toggle
        function createMobileMenu() {
            const nav = document.querySelector('.nav-container');
            const navLinks = document.querySelector('.nav-links');
            
            // Create mobile menu button
            const mobileMenuBtn = document.createElement('button');
            mobileMenuBtn.innerHTML = '<i class="fas fa-bars"></i>';
            mobileMenuBtn.classList.add('mobile-menu-btn');
            mobileMenuBtn.style.cssText = `
                display: none;
                background: none;
                border: none;
                color: white;
                font-size: 1.5rem;
                cursor: pointer;
                padding: 0.5rem;
            `;
            
            nav.appendChild(mobileMenuBtn);
            
            // Toggle mobile menu
            mobileMenuBtn.addEventListener('click', () => {
                navLinks.classList.toggle('mobile-active');
            });
            
            // Show mobile menu button on small screens
            function checkScreenSize() {
                if (window.innerWidth <= 768) {
                    mobileMenuBtn.style.display = 'block';
                    navLinks.style.cssText = `
                        position: absolute;
                        top: 100%;
                        left: 0;
                        right: 0;
                        background: #2c3e50;
                        flex-direction: column;
                        padding: 1rem;
                        transform: translateY(-100%);
                        opacity: 0;
                        visibility: hidden;
                        transition: all 0.3s ease;
                    `;
                } else {
                    mobileMenuBtn.style.display = 'none';
                    navLinks.style.cssText = '';
                    navLinks.classList.remove('mobile-active');
                }
            }
            
            // Add mobile-active styles
            const style = document.createElement('style');
            style.textContent = `
                .nav-links.mobile-active {
                    transform: translateY(0) !important;
                    opacity: 1 !important;
                    visibility: visible !important;
                }
                
                @media (max-width: 768px) {
                    .nav-container {
                        position: relative;
                    }
                }
            `;
            document.head.appendChild(style);
            
            checkScreenSize();
            window.addEventListener('resize', checkScreenSize);
        }
        
        // Initialize mobile menu
        createMobileMenu();

        // Add parallax effect to hero section
        window.addEventListener('scroll', () => {
            const scrolled = window.pageYOffset;
            const hero = document.querySelector('.hero-section');
            const rate = scrolled * -0.5;
            
            if (hero) {
                hero.style.transform = `translateY(${rate}px)`;
            }
        });

        // Add hover effects to team members
        document.querySelectorAll('.team-member').forEach(member => {
            member.addEventListener('mouseenter', function() {
                this.style.boxShadow = '0 20px 40px rgba(52, 152, 219, 0.3)';
            });
            
            member.addEventListener('mouseleave', function() {
                this.style.boxShadow = '0 10px 30px rgba(0,0,0,0.1)';
            });
        });

        // Add click effect to value items
        document.querySelectorAll('.value-item, .type-item').forEach(item => {
            item.addEventListener('click', function() {
                this.style.transform = 'scale(0.98)';
                setTimeout(() => {
                    this.style.transform = 'translateY(-5px)';
                }, 150);
            });
        });

        // Lazy loading for images (if any are added later)
        function lazyLoadImages() {
            const images = document.querySelectorAll('img[data-src]');
            const imageObserver = new IntersectionObserver((entries, observer) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        const img = entry.target;
                        img.src = img.dataset.src;
                        img.classList.remove('lazy');
                        imageObserver.unobserve(img);
                    }
                });
            });

            images.forEach(img => imageObserver.observe(img));
        }
        
        lazyLoadImages();

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
        
        // Initialize typing effect on page load
        window.addEventListener('load', () => {
            const heroTitle = document.querySelector('.hero-content h1');
            const originalText = heroTitle.innerText;
            typeWriter(heroTitle, originalText, 80);
        });

        // Add search functionality for team members
        function addSearchFunctionality() {
            const searchInput = document.createElement('input');
            searchInput.type = 'text';
            searchInput.placeholder = 'Rechercher un membre de l\'équipe...';
            searchInput.style.cssText = `
                width: 100%;
                max-width: 300px;
                padding: 0.75rem;
                margin-bottom: 2rem;
                border: 2px solid #e1e5e9;
                border-radius: 8px;
                font-size: 1rem;
                outline: none;
                transition: border-color 0.3s ease;
            `;
            
            searchInput.addEventListener('focus', () => {
                searchInput.style.borderColor = '#60a5fa';
            });
            
            searchInput.addEventListener('blur', () => {
                searchInput.style.borderColor = '#e1e5e9';
            });
            
            const teamSection = document.querySelector('.team-section .container');
            const teamTitle = teamSection.querySelector('.section-title');
            teamTitle.insertAdjacentElement('afterend', searchInput);
            
            // Search functionality
            searchInput.addEventListener('input', (e) => {
                const searchTerm = e.target.value.toLowerCase();
                const teamMembers = document.querySelectorAll('.team-member');
                
                teamMembers.forEach(member => {
                    const name = member.querySelector('.member-name').textContent.toLowerCase();
                    const role = member.querySelector('.member-role').textContent.toLowerCase();
                    
                    if (name.includes(searchTerm) || role.includes(searchTerm)) {
                        member.style.display = 'block';
                        member.style.opacity = '1';
                    } else {
                        member.style.display = 'none';
                        member.style.opacity = '0';
                    }
                });
            });
        }
        
        // Initialize search functionality
        addSearchFunctionality();

        // Add scroll-to-top button
        function addScrollToTop() {
            const scrollBtn = document.createElement('button');
            scrollBtn.innerHTML = '<i class="fas fa-arrow-up"></i>';
            scrollBtn.style.cssText = `
                position: fixed;
                bottom: 30px;
                right: 30px;
                width: 50px;
                height: 50px;
                background: #60a5fa;
                color: white;
                border: none;
                border-radius: 50%;
                cursor: pointer;
                font-size: 1.2rem;
                box-shadow: 0 4px 12px rgba(96, 165, 250, 0.4);
                transform: translateY(100px);
                transition: all 0.3s ease;
                z-index: 1000;
            `;
            
            document.body.appendChild(scrollBtn);
            
            // Show/hide button based on scroll position
            window.addEventListener('scroll', () => {
                if (window.pageYOffset > 300) {
                    scrollBtn.style.transform = 'translateY(0)';
                } else {
                    scrollBtn.style.transform = 'translateY(100px)';
                }
            });
            
            // Scroll to top on click
            scrollBtn.addEventListener('click', () => {
                window.scrollTo({
                    top: 0,
                    behavior: 'smooth'
                });
            });
            
            // Hover effect
            scrollBtn.addEventListener('mouseenter', () => {
                scrollBtn.style.background = '#2c5282';
                scrollBtn.style.transform = 'translateY(0) scale(1.1)';
            });
            
            scrollBtn.addEventListener('mouseleave', () => {
                scrollBtn.style.background = '#60a5fa';
                scrollBtn.style.transform = 'translateY(0) scale(1)';
            });
        }
        
        addScrollToTop();

        // Add testimonials section dynamically
        function addTestimonials() {
            const testimonialsHTML = `
                <section class="testimonials-section" style="background: white; padding: 4rem 0; margin: 4rem 0; border-radius: 15px; box-shadow: 0 10px 30px rgba(0,0,0,0.1);">
                    <div class="container">
                        <h2 class="section-title">Témoignages Clients</h2>
                        <div class="testimonials-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 2rem; margin-top: 2rem;">
                            <div class="testimonial-item" style="background: #f8f9fa; padding: 2rem; border-radius: 10px; text-align: center; border-left: 4px solid #60a5fa;">
                                <div style="color: #60a5fa; font-size: 2rem; margin-bottom: 1rem;">★★★★★</div>
                                <p style="font-style: italic; color: #666; margin-bottom: 1rem;">"Service exceptionnel et professionnalisme remarquable. Elite Properties a dépassé toutes nos attentes."</p>
                                <strong style="color: #1e3a5f;">- Sarah Martin</strong>
                            </div>
                            <div class="testimonial-item" style="background: #f8f9fa; padding: 2rem; border-radius: 10px; text-align: center; border-left: 4px solid #60a5fa;">
                                <div style="color: #60a5fa; font-size: 2rem; margin-bottom: 1rem;">★★★★★</div>
                                <p style="font-style: italic; color: #666; margin-bottom: 1rem;">"Une équipe formidable qui comprend vraiment les besoins de ses clients. Hautement recommandé!"</p>
                                <strong style="color: #1e3a5f;">- Ahmed Bennani</strong>
                            </div>
                            <div class="testimonial-item" style="background: #f8f9fa; padding: 2rem; border-radius: 10px; text-align: center; border-left: 4px solid #60a5fa;">
                                <div style="color: #60a5fa; font-size: 2rem; margin-bottom: 1rem;">★★★★★</div>
                                <p style="font-style: italic; color: #666; margin-bottom: 1rem;">"Processus fluide et transparent. Nous avons trouvé notre maison de rêve grâce à Elite Properties."</p>
                                <strong style="color: #1e3a5f;">- Michel Dubois</strong>
                            </div>
                        </div>
                    </div>
                </section>
            `;
            
            const valuesSection = document.querySelector('.values-section');
            valuesSection.insertAdjacentHTML('afterend', testimonialsHTML);
        }
        
        // Add testimonials after DOM is loaded
        document.addEventListener('DOMContentLoaded', () => {
            setTimeout(addTestimonials, 1000);
        });

        console.log('🏠 Elite Properties - Page À Propos chargée avec succès!');
    </script>

    <style>
        /* Additional animations and effects */
        @keyframes slideInFromLeft {
            0% {
                transform: translateX(-100%);
                opacity: 0;
            }
            100% {
                transform: translateX(0);
                opacity: 1;
            }
        }

        @keyframes slideInFromRight {
            0% {
                transform: translateX(100%);
                opacity: 0;
            }
            100% {
                transform: translateX(0);
                opacity: 1;
            }
        }

        @keyframes fadeInUp {
            0% {
                transform: translateY(30px);
                opacity: 0;
            }
            100% {
                transform: translateY(0);
                opacity: 1;
            }
        }

        @keyframes pulse {
            0%, 100% {
                transform: scale(1);
            }
            50% {
                transform: scale(1.05);
            }
        }

        .hero-content {
            animation: fadeInUp 1s ease-out;
        }

        .about-text {
            animation: slideInFromLeft 0.8s ease-out;
        }

        .about-image {
            animation: slideInFromRight 0.8s ease-out;
        }

        .value-icon:hover {
            animation: pulse 0.6s ease-in-out;
        }

        .member-photo:hover {
            animation: pulse 0.6s ease-in-out;
        }

        /* Loading animation */
        .loading {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: white;
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 9999;
            transition: opacity 0.5s ease;
        }

        .loading.hidden {
            opacity: 0;
            pointer-events: none;
        }

        .spinner {
            width: 50px;
            height: 50px;
            border: 4px solid #f3f3f3;
            border-top: 4px solid #3498db;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* Enhanced hover effects */
        .stat-item:hover {
            transform: translateY(-5px);
            transition: transform 0.3s ease;
        }

        .type-item:hover {
            transform: translateY(-3px) scale(1.02);
            box-shadow: 0 8px 25px rgba(52, 152, 219, 0.2);
        }

        /* Gradient text effect */
        .gradient-text {
            background: linear-gradient(135deg, #3498db, #2980b9);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        /* Add gradient text to section titles */
        .section-title {
            background: linear-gradient(135deg, #2c3e50, #3498db);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
    </style>
</body>
</html>