<?php 
// Include database configuration (this will start the session)
require_once 'include/config.php';
// Get database connection
$pdo = getDatabaseConnection();

// Fetch featured properties (limit to 6 for homepage)
try {
    $stmt = $pdo->prepare("SELECT * FROM property ORDER BY created_at DESC LIMIT 6");
    $stmt->execute();
    $featuredProperties = $stmt->fetchAll();
} catch(PDOException $e) {
    $featuredProperties = [];
    error_log("Error fetching properties: " . $e->getMessage());
}

// Handle contact form submission
$contactMessage = '';
$contactStatus = '';

if ($_POST && isset($_POST['contact_submit'])) {
    try {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $message = trim($_POST['message'] ?? '');
        
        // Basic validation
        if (empty($name) || empty($email) || empty($message)) {
            throw new Exception("Please fill in all required fields.");
        }
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Please enter a valid email address.");
        }
        
        // Create contacts table if it doesn't exist
        $createTableSQL = "CREATE TABLE IF NOT EXISTS contacts (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            email VARCHAR(255) NOT NULL,
            phone VARCHAR(20),
            message TEXT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";
        $pdo->exec($createTableSQL);
        
        // Insert contact inquiry
        $stmt = $pdo->prepare("INSERT INTO contacts (name, email, phone, message) VALUES (?, ?, ?, ?)");
        $stmt->execute([$name, $email, $phone, $message]);
        
        $contactMessage = "Thank you for your inquiry! We'll get back to you soon.";
        $contactStatus = 'success';
        
    } catch(Exception $e) {
        $contactMessage = $e->getMessage();
        $contactStatus = 'error';
    }
}

// Get statistics from database
try {
    $statsStmt = $pdo->prepare("SELECT 
        COUNT(*) as total_properties,
        COUNT(CASE WHEN status = 2 THEN 1 END) as sold_properties,
        COUNT(DISTINCT created_by) as agents
    FROM property");
    $statsStmt->execute();
    $stats = $statsStmt->fetch();
    
    // Mock additional stats
    $propertiesSold = $stats['sold_properties'] ?? 1200;
    $happyClients = intval($propertiesSold * 0.7); // Estimate based on sold properties
    $yearsExperience = 15;
    $successRate = 98;
    
} catch(PDOException $e) {
    // Fallback stats
    $propertiesSold = 1200;
    $happyClients = 850;
    $yearsExperience = 15;
    $successRate = 98;
}

include('include/head.php');
?>
<body class="bg-gray-100">

 <style>
        /* Custom styles for animations */
        .hero-overlay {
            background: linear-gradient(135deg, rgba(0,0,0,0.7) 0%, rgba(79,70,229,0.4) 100%);
        }
        
        /* Remove problematic initial styles and add fallbacks */ 
        .property-card,
        .fade-in-up,
        .scale-in,
        .slide-in-left,
        .slide-in-right {
            opacity: 1;
            transform: none;
            visibility: visible;
        }

        /* Add loading fallback */
        .gsap-loading {
            opacity: 0;
        }

        /* Ensure content is always visible */
        body {
            visibility: visible !important;
            opacity: 1 !important;
        }

        /* Fix any potential z-index issues */
        #header {
            z-index: 1000;
        }

        .custom-cursor {
            z-index: 9999;
        }
        
        /* Particle background */
        .particles {
            position: absolute;
            width: 100%;
            height: 100%;
            overflow: hidden;
        }
        
        .particle {
            position: absolute;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            pointer-events: none;
        }
        
        /* Smooth cursor */
        .custom-cursor {
            position: fixed;
            width: 20px;
            height: 20px;
            background: #4f46e5;
            border-radius: 50%;
            pointer-events: none;
            z-index: 9999;
            mix-blend-mode: difference;
            transition: transform 0.1s ease;
        }
        
        /* Enhanced hover effects */
        .property-card:hover {
            transform: translateY(-10px) scale(1.02);
        }
        
        .magnetic-button {
            transition: transform 0.3s cubic-bezier(0.25, 0.46, 0.45, 0.94);
        }

        /* Define header height variable for consistent spacing */
        :root {
            --header-height: 64px;
        }

        /* Success/Error message styles */
        .alert {
            padding: 1rem;
            margin-bottom: 1rem;
            border-radius: 0.5rem;
            font-weight: 500;
        }
        .alert-success {
            background-color: #d1fae5;
            border: 1px solid #10b981;
            color: #065f46;
        }
        .alert-error {
            background-color: #fee2e2;
            border: 1px solid #ef4444;
            color: #991b1b;
        }
    </style>
    
    <!-- Custom Cursor -->
    <div class="custom-cursor" id="cursor"></div>

    <!-- Header -->
   <?php include('include/header.php');?>
   
    <!-- Hero Section -->
    <section id="home" class="relative h-screen flex flex-col justify-center items-center bg-cover bg-center bg-no-repeat overflow-hidden pt-[var(--header-height)]" style="background-image: url('img/365706.jpg');">
        <!-- Particles Background -->
        <div class="particles" id="particles"></div>

        <!-- Hero Overlay -->
        <div class="absolute inset-0 bg-black/50"></div>

        <!-- Hero content -->
        <div class="text-center text-white px-4 max-w-4xl relative z-10">
            <div class="fade-in-up" id="heroTitle">
                <h2 class="text-5xl md:text-7xl font-bold mb-6 leading-tight">
                    Find Your 
                    <span class="text-transparent bg-clip-text bg-gradient-to-r from-indigo-400 to-purple-400" id="dreamText">Dream</span> 
                    Home
                </h2>
            </div>
            <div class="fade-in-up" id="heroSubtitle" style="animation-delay: 0.3s;">
                <p class="text-xl md:text-2xl mb-8 max-w-2xl mx-auto opacity-90">
                    Discover luxury properties, prime locations, and exceptional value in our curated collection of homes, villas, and land.
                </p>
            </div>
            <div class="fade-in-up" id="heroButton" style="animation-delay: 0.6s;">
                <a href="properties.php" class="inline-block bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 text-white font-semibold py-4 px-8 rounded-lg text-lg transition-all duration-300 transform hover:scale-105 magnetic-button shadow-2xl">
                    <span class="mr-2">Explore Listings</span>
                    <i class="fas fa-arrow-right"></i>
                </a>
            </div>
        </div>

        <!-- Scroll Indicator -->
        <div class="absolute bottom-8 left-1/2 transform -translate-x-1/2 fade-in-up z-10" id="scrollIndicator" style="animation-delay: 1s;">
            <div class="flex flex-col items-center text-white">
                <span class="text-sm mb-2 opacity-75">Scroll Down</span>
                <div class="w-6 h-10 border-2 border-white rounded-full flex justify-center">
                    <div class="w-1 h-3 bg-white rounded-full mt-2 animate-bounce"></div>
                </div>
            </div>
        </div>
    </section>

    <!-- Stats Section -->
    <section class="py-16 bg-gradient-to-r from-slate-900 to-indigo-900 text-white">
        <div class="container mx-auto px-4">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-8">
                <div class="text-center scale-in" data-stat="<?php echo $propertiesSold; ?>">
                    <div class="text-4xl md:text-5xl font-bold mb-2 text-indigo-400" id="stat1">0</div>
                    <div class="text-sm md:text-base opacity-75">Properties Sold</div>
                </div>
                <div class="text-center scale-in" data-stat="<?php echo $happyClients; ?>" style="animation-delay: 0.2s;">
                    <div class="text-4xl md:text-5xl font-bold mb-2 text-purple-400" id="stat2">0</div>
                    <div class="text-sm md:text-base opacity-75">Happy Clients</div>
                </div>
                <div class="text-center scale-in" data-stat="<?php echo $yearsExperience; ?>" style="animation-delay: 0.4s;">
                    <div class="text-4xl md:text-5xl font-bold mb-2 text-pink-400" id="stat3">0</div>
                    <div class="text-sm md:text-base opacity-75">Years Experience</div>
                </div>
                <div class="text-center scale-in" data-stat="<?php echo $successRate; ?>" style="animation-delay: 0.6s;">
                    <div class="text-4xl md:text-5xl font-bold mb-2 text-green-400" id="stat4">0</div>
                    <div class="text-sm md:text-base opacity-75">Success Rate %</div>
                </div>
            </div>
        </div>
    </section>

    <!-- Property Listings Section -->
    <section id="listings" class="py-16 bg-gray-100">
        <div class="container mx-auto px-4">
            <div class="text-center mb-12">
                <div class="fade-in-up">
                    <h2 class="text-4xl font-bold text-gray-800 mb-4">Featured Properties</h2>
                    <p class="text-xl text-gray-600 max-w-2xl mx-auto">Browse our selection of premium properties carefully chosen for their quality, location, and value.</p>
                </div>
            </div>

            <!-- Property Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8" id="propertyGrid">
                <?php if (!empty($featuredProperties)): ?>
                    <?php foreach ($featuredProperties as $property): ?>
                        <div class="property-card bg-white rounded-xl shadow-lg overflow-hidden transform transition-all duration-300 hover:shadow-2xl">
                            <div class="relative overflow-hidden">
                                <img src="admin/<?php echo htmlspecialchars($property['property_image']); ?>" 
                                     alt="<?php echo htmlspecialchars($property['name']); ?>" 
                                     class="w-full h-64 object-cover transition-transform duration-300">
                                <div class="absolute top-4 left-4">
                                    <span class="bg-indigo-600 text-white px-3 py-1 rounded-full text-sm font-semibold">
                                        <?php echo ucfirst(htmlspecialchars($property['type'])); ?>
                                    </span>
                                </div>
                                <div class="absolute top-4 right-4">
                                    <span class="bg-white/90 text-gray-800 px-3 py-1 rounded-full text-sm font-bold">
                                        $<?php echo number_format($property['price']); ?>
                                    </span>
                                </div>
                            </div>
                            <div class="p-6">
                                <h3 class="text-xl font-bold text-gray-800 mb-2">
                                    <?php echo htmlspecialchars($property['name']); ?>
                                </h3>
                                <p class="text-gray-600 mb-4 flex items-center">
                                    <i class="fas fa-map-marker-alt mr-2 text-indigo-600"></i>
                                    <?php echo htmlspecialchars($property['location']); ?>
                                </p>
                                <p class="text-gray-600 mb-4 line-clamp-2">
                                    <?php echo htmlspecialchars(substr($property['description'], 0, 100)) . (strlen($property['description']) > 100 ? '...' : ''); ?>
                                </p>
                                <div class="flex items-center justify-between text-sm text-gray-500 mb-4">
                                    <span class="flex items-center">
                                        <i class="fas fa-bed mr-1"></i>
                                        <?php echo $property['number_of_bedrooms']; ?> Bedrooms
                                    </span>
                                    <span class="flex items-center">
                                        <i class="fas fa-bath mr-1"></i>
                                        <?php echo $property['number_of_bathrooms']; ?> Bathrooms
                                    </span>
                                </div>
                                <a href="propertie.php?id=<?php echo $property['Id']; ?>" 
                                   class="w-full bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 text-white font-semibold py-3 px-6 rounded-lg transition-all duration-300 transform hover:scale-105 block text-center">
                                    View Details
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="col-span-full text-center py-12">
                        <div class="text-gray-500 text-xl">
                            <i class="fas fa-home mb-4 text-4xl"></i>
                            <p>No properties available at the moment.</p>
                            <p class="text-sm mt-2">Please check back later for new listings.</p>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- View More Button -->
            <div class="text-center mt-12 fade-in-up">
                 <a href="properties.php" class="bg-gradient-to-r from-slate-900 to-indigo-900 hover:from-slate-800 hover:to-indigo-800 text-white font-semibold py-4 px-8 rounded-lg text-lg transition-all duration-300 transform hover:scale-105 magnetic-button shadow-2xl">
                    <span class="mr-2">View All Properties</span>
                    <i class="fas fa-arrow-right"></i>
                 </a>
            </div>
        </div>
    </section>

    <!-- Contact Section -->
    <section id="contact" class="py-16 bg-white">
        <div class="container mx-auto px-4">
            <div class="max-w-4xl mx-auto">
                <div class="text-center mb-12 fade-in-up">
                    <h2 class="text-4xl font-bold text-gray-800 mb-4">Get In Touch</h2>
                    <p class="text-xl text-gray-600">Ready to find your dream property? Contact our expert team today.</p>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-12">
                    <!-- Contact Form -->
                    <div class="slide-in-left">
                        <?php if ($contactMessage): ?>
                            <div class="alert <?php echo $contactStatus === 'success' ? 'alert-success' : 'alert-error'; ?>">
                                <?php echo htmlspecialchars($contactMessage); ?>
                            </div>
                        <?php endif; ?>
                        
                        <form class="space-y-6" id="contactForm" method="POST">
                            <div class="fade-in-up">
                                <label for="name" class="block text-sm font-medium text-gray-700 mb-2">Full Name *</label>
                                <input type="text" id="name" name="name" required
                                       value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>"
                                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all duration-300 transform focus:scale-105" 
                                       placeholder="Your full name">
                            </div>
                            <div class="fade-in-up">
                                <label for="email" class="block text-sm font-medium text-gray-700 mb-2">Email Address *</label>
                                <input type="email" id="email" name="email" required
                                       value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all duration-300 transform focus:scale-105" 
                                       placeholder="your.email@example.com">
                            </div>
                            <div class="fade-in-up">
                                <label for="phone" class="block text-sm font-medium text-gray-700 mb-2">Phone Number</label>
                                <input type="tel" id="phone" name="phone"
                                       value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>"
                                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all duration-300 transform focus:scale-105" 
                                       placeholder="(555) 123-4567">
                            </div>
                            <div class="fade-in-up">
                                <label for="message" class="block text-sm font-medium text-gray-700 mb-2">Message *</label>
                                <textarea id="message" name="message" rows="5" required
                                          class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all duration-300 transform focus:scale-105" 
                                          placeholder="Tell us about your property needs..."><?php echo htmlspecialchars($_POST['message'] ?? ''); ?></textarea>
                            </div>
                            <div class="fade-in-up">
                                <button type="submit" name="contact_submit" 
                                        class="w-full bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 text-white font-semibold py-4 rounded-lg transition-all duration-300 transform hover:scale-105 magnetic-button shadow-2xl">
                                    <span class="mr-2">Send Message</span>
                                    <i class="fas fa-paper-plane"></i>
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Contact Information -->
                    <div class="space-y-8 slide-in-right">
                        <div class="fade-in-up">
                            <h3 class="text-2xl font-bold text-gray-800 mb-6">Contact Information</h3>
                            <div class="space-y-4">
                                <div class="flex items-center space-x-4 scale-in">
                                    <div class="bg-gradient-to-r from-indigo-100 to-purple-100 p-3 rounded-full">
                                        <i class="fas fa-phone text-indigo-600"></i>
                                    </div>
                                    <div>
                                        <p class="font-semibold text-gray-800">Phone</p>
                                        <p class="text-gray-600">(555) 123-4567</p>
                                    </div>
                                </div>
                                <div class="flex items-center space-x-4 scale-in">
                                    <div class="bg-gradient-to-r from-indigo-100 to-purple-100 p-3 rounded-full">
                                        <i class="fas fa-envelope text-indigo-600"></i>
                                    </div>
                                    <div>
                                        <p class="font-semibold text-gray-800">Email</p>
                                        <p class="text-gray-600">info@eliteproperties.com</p>
                                    </div>
                                </div>
                                <div class="flex items-center space-x-4 scale-in">
                                    <div class="bg-gradient-to-r from-indigo-100 to-purple-100 p-3 rounded-full">
                                        <i class="fas fa-map-marker-alt text-indigo-600"></i>
                                    </div>
                                    <div>
                                        <p class="font-semibold text-gray-800">Office</p>
                                        <p class="text-gray-600">123 Real Estate Ave<br>Suite 100, City, State 12345</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="fade-in-up">
                            <h4 class="text-lg font-semibold text-gray-800 mb-4">Office Hours</h4>
                            <div class="space-y-2 text-gray-600">
                                <p>Monday - Friday: 9:00 AM - 6:00 PM</p>
                                <p>Saturday: 10:00 AM - 4:00 PM</p>
                                <p>Sunday: By Appointment</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
   <?php include('include/footer.php');?>
   
    <!-- GSAP Animations Script -->
    <script>
        // Register GSAP plugins with error handling
        try {
            gsap.registerPlugin(ScrollTrigger, TextPlugin);
        } catch (error) {
            console.warn('GSAP plugins failed to load:', error);
        }

        // Ensure page is always visible
        document.documentElement.style.visibility = 'visible';
        document.body.style.visibility = 'visible';
        document.body.style.opacity = '1';

        // Initialize immediately without waiting
        initializePageImmediately();

        function initializePageImmediately() {
            // Remove any loading classes
            document.body.classList.remove('gsap-loading');
            
            // Ensure all content is visible
            makeAllContentVisible();
            
            // Initialize animations with fallbacks
            if (typeof gsap !== 'undefined') {
                initializeAnimations();
            } else {
                console.warn('GSAP not loaded, using fallback');
                initializeFallbacks();
            }
        }

        function makeAllContentVisible() {
            // Force all potentially hidden elements to be visible
            const elements = document.querySelectorAll('.property-card, .fade-in-up, .scale-in, .slide-in-left, .slide-in-right');
            elements.forEach(el => {
                el.style.opacity = '1';
                el.style.transform = 'none';
                el.style.visibility = 'visible';
            });
        }

        function initializeFallbacks() {
            // Fallback for when GSAP fails to load
            const allAnimatedElements = document.querySelectorAll('[class*="fade-"], [class*="slide-"], [class*="scale-"], .property-card');
            allAnimatedElements.forEach(el => {
                el.style.opacity = '1';
                el.style.transform = 'none';
                el.style.transition = 'all 0.3s ease';
            });
        }

        function initializeAnimations() {
            try {
                // Set initial states only after GSAP is confirmed to work
                gsap.set('.property-card', {y: 50, opacity: 0});
                gsap.set('.fade-in-up', {y: 30, opacity: 0});
                gsap.set('.scale-in', {scale: 0.8, opacity: 0});
                gsap.set('.slide-in-left', {x: -50, opacity: 0});
                gsap.set('.slide-in-right', {x: 50, opacity: 0});

                // Hero Section Animations - start immediately
                const heroTl = gsap.timeline();
                heroTl.from("#heroTitle", {duration: 1.5, y: 100, opacity: 0, ease: "power3.out"})
                      .from("#heroSubtitle", {duration: 1.2, y: 50, opacity: 0, ease: "power3.out"}, "-=1")
                      .from("#heroButton", {duration: 1, y: 30, opacity: 0, ease: "power3.out"}, "-=0.8")
                      .from("#scrollIndicator", {duration: 0.8, y: 20, opacity: 0, ease: "power3.out"}, "-=0.5");

                // Initialize scroll animations
                initializeScrollAnimations();
                initializeInteractiveElements();
                
            } catch (error) {
                console.warn('Animation initialization failed:', error);
                makeAllContentVisible();
            }
        }

        function initializeScrollAnimations() {
            try {
                // Stats Counter Animation with fallback
                ScrollTrigger.create({
                    trigger: "#stat1",
                    start: "top 80%",
                    onEnter: () => {
                        animateStats();
                    }
                });

                // Property Cards Animation with fallback
                ScrollTrigger.create({
                    trigger: "#propertyGrid",
                    start: "top 80%",
                    onEnter: () => {
                        gsap.to(".property-card", {
                            y: 0, 
                            opacity: 1, 
                            duration: 0.8, 
                            stagger: 0.2, 
                            ease: "power3.out"
                        });
                    }
                });

                // Animate other elements as they come into view
                gsap.utils.toArray('.fade-in-up').forEach(element => {
                    ScrollTrigger.create({
                        trigger: element,
                        start: "top 85%",
                        onEnter: () => {
                            gsap.to(element, {y: 0, opacity: 1, duration: 1, ease: "power3.out"});
                        }
                    });
                });

                gsap.utils.toArray('.scale-in').forEach(element => {
                    ScrollTrigger.create({
                        trigger: element,
                        start: "top 85%",
                        onEnter: () => {
                            gsap.to(element, {scale: 1, opacity: 1, duration: 0.8, ease: "back.out(1.7)"});
                        }
                    });
                });

                gsap.utils.toArray('.slide-in-left').forEach(element => {
                    ScrollTrigger.create({
                        trigger: element,
                        start: "top 85%",
                        onEnter: () => {
                            gsap.to(element, {x: 0, opacity: 1, duration: 1, ease: "power3.out"});
                        }
                    });
                });

                gsap.utils.toArray('.slide-in-right').forEach(element => {
                    ScrollTrigger.create({
                        trigger: element,
                        start: "top 85%",
                        onEnter: () => {
                            gsap.to(element, {x: 0, opacity: 1, duration: 1, ease: "power3.out"});
                        }
                    });
                });

                // Footer animation
                ScrollTrigger.create({
                    trigger: "footer",
                    start: "top 90%",
                    onEnter: () => {
                        gsap.fromTo("footer", {y: 100, opacity: 0}, {y: 0, opacity: 1, duration: 1, ease: "power3.out"});
                    }
                });

            } catch (error) {
                console.warn('Scroll animations failed:', error);
                // Fallback: make everything visible immediately
                makeAllContentVisible();
            }
        }

        function animateStats() {
            try {
                // Use dynamic values from PHP
                gsap.to("#stat1", {duration: 2, innerText: <?php echo $propertiesSold; ?>, snap: {innerText: 1}, ease: "power2.out"});
                gsap.to("#stat2", {duration: 2, innerText: <?php echo $happyClients; ?>, snap: {innerText: 1}, ease: "power2.out", delay: 0.2});
                gsap.to("#stat3", {duration: 2, innerText: <?php echo $yearsExperience; ?>, snap: {innerText: 1}, ease: "power2.out", delay: 0.4});
                gsap.to("#stat4", {duration: 2, innerText: <?php echo $successRate; ?>, snap: {innerText: 1}, ease: "power2.out", delay: 0.6});
            } catch (error) {
                // Fallback: set final values immediately
                document.getElementById('stat1').textContent = '<?php echo $propertiesSold; ?>';
                document.getElementById('stat2').textContent = '<?php echo $happyClients; ?>';
                document.getElementById('stat3').textContent = '<?php echo $yearsExperience; ?>';
                document.getElementById('stat4').textContent = '<?php echo $successRate; ?>';
            }
        }

        function initializeInteractiveElements() {
            try {
                // Header scroll effect
                ScrollTrigger.create({
                    start: "top -80",
                    end: 99999,
                    toggleClass: {className: "bg-slate-900/95 backdrop-blur-md", targets: "#header"}
                });

                // Dream text animation
                gsap.to("#dreamText", {
                    duration: 3,
                    backgroundPosition: "200% center",
                    ease: "none",
                    repeat: -1
                });

                // Parallax effect
                gsap.to("#home", {
                    yPercent: -50,
                    ease: "none",
                    scrollTrigger: {
                        trigger: "#home",
                        start: "top bottom",
                        end: "bottom top",
                        scrub: true
                    }
                });

            } catch (error) {
                console.warn('Interactive elements failed:', error);
            }
        }

        // Custom Cursor with error handling
        try {
            const cursor = document.getElementById('cursor');
            if (cursor) {
                let mouseX = 0, mouseY = 0;
                let cursorX = 0, cursorY = 0;

                document.addEventListener('mousemove', (e) => {
                    mouseX = e.clientX;
                    mouseY = e.clientY;
                });

                function animateCursor() {
                    cursorX += (mouseX - cursorX) * 0.1;
                    cursorY += (mouseY - cursorY) * 0.1;
                    cursor.style.left = cursorX + 'px';
                    cursor.style.top = cursorY + 'px';
                    requestAnimationFrame(animateCursor);
                }
                animateCursor();
            }
        } catch (error) {
            console.warn('Cursor animation failed:', error);
        }

        // Magnetic Button Effects with error handling
        document.addEventListener('DOMContentLoaded', function() {
            try {
                document.querySelectorAll('.magnetic-button').forEach(button => {
                    button.addEventListener('mouseenter', function(e) {
                        if (typeof gsap !== 'undefined') {
                            gsap.to(this, {duration: 0.3, scale: 1.05, ease: "power2.out"});
                        } else {
                            this.style.transform = 'scale(1.05)';
                        }
                    });
                    
                    button.addEventListener('mouseleave', function(e) {
                        if (typeof gsap !== 'undefined') {
                            gsap.to(this, {duration: 0.3, scale: 1, ease: "power2.out"});
                        } else {
                            this.style.transform = 'scale(1)';
                        }
                    });
                });
            } catch (error) {
                console.warn('Button effects failed:', error);
            }
        });

        // Particles with error handling
        function createParticles() {
            try {
                const particlesContainer = document.getElementById('particles');
                if (!particlesContainer) return;
                
                const particleCount = 50;

                for (let i = 0; i < particleCount; i++) {
                    const particle = document.createElement('div');
                    particle.className = 'particle';
                    particle.style.width = Math.random() * 4 + 2 + 'px';
                    particle.style.height = particle.style.width;
                    particle.style.left = Math.random() * 100 + '%';
                    particle.style.top = Math.random() * 100 + '%';
                    particlesContainer.appendChild(particle);

                    if (typeof gsap !== 'undefined') {
                        gsap.to(particle, {
                            duration: Math.random() * 20 + 10,
                            x: Math.random() * 200 - 100,
                            y: Math.random() * 200 - 100,
                            rotation: Math.random() * 360,
                            repeat: -1,
                            yoyo: true,
                            ease: "none"
                        });
                    }
                }
            } catch (error) {
                console.warn('Particles failed:', error);
            }
        }

        // Initialize particles after a short delay
        setTimeout(createParticles, 100);

        // Smooth Scrolling with fallback
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    if (typeof gsap !== 'undefined') {
                        gsap.to(window, {duration: 1.5, scrollTo: target, ease: "power2.inOut"});
                    } else {
                        target.scrollIntoView({behavior: 'smooth'});
                    }
                }
            });
        });

        // Form interactions with fallback
        document.addEventListener('DOMContentLoaded', function() {
            const formInputs = document.querySelectorAll('#contactForm input, #contactForm textarea');
            formInputs.forEach(input => {
                input.addEventListener('focus', function() {
                    if (typeof gsap !== 'undefined') {
                        gsap.to(this, {duration: 0.3, scale: 1.02, ease: "power2.out"});
                    } else {
                        this.style.transform = 'scale(1.02)';
                    }
                });
                
                input.addEventListener('blur', function() {
                    if (typeof gsap !== 'undefined') {
                        gsap.to(this, {duration: 0.3, scale: 1, ease: "power2.out"});
                    } else {
                        this.style.transform = 'scale(1)';
                    }
                });
            });
        });

        // Property Card Hover with fallback
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.property-card').forEach(card => {
                card.addEventListener('mouseenter', function() {
                    if (typeof gsap !== 'undefined') {
                        gsap.to(this, {duration: 0.3, y: -10, scale: 1.02, ease: "power2.out"});
                        gsap.to(this.querySelector('img'), {duration: 0.3, scale: 1.1, ease: "power2.out"});
                    } else {
                        this.style.transform = 'translateY(-10px) scale(1.02)';
                        const img = this.querySelector('img');
                        if (img) img.style.transform = 'scale(1.1)';
                    }
                });
                
                card.addEventListener('mouseleave', function() {
                    if (typeof gsap !== 'undefined') {
                        gsap.to(this, {duration: 0.3, y: 0, scale: 1, ease: "power2.out"});
                        gsap.to(this.querySelector('img'), {duration: 0.3, scale: 1, ease: "power2.out"});
                    } else {
                        this.style.transform = 'translateY(0) scale(1)';
                        const img = this.querySelector('img');
                        if (img) img.style.transform = 'scale(1)';
                    }
                });
            });
        });

        // Refresh ScrollTrigger on resize
        window.addEventListener('resize', function() {
            if (typeof ScrollTrigger !== 'undefined') {
                ScrollTrigger.refresh();
            }
        });

        // Final fallback - ensure everything is visible after 2 seconds
        setTimeout(function() {
            makeAllContentVisible();
            console.log('Fallback visibility applied');
        }, 2000);

        // Auto-hide success/error messages after 5 seconds
        document.addEventListener('DOMContentLoaded', function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                setTimeout(() => {
                    alert.style.transition = 'opacity 0.5s ease';
                    alert.style.opacity = '0';
                    setTimeout(() => {
                        alert.remove();
                    }, 500);
                }, 5000);
            });
        });
    </script>
</body>
</html>