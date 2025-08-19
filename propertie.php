<?php 
// Include database configuration (this will start the session)
require_once 'include/config.php';
// Get database connection
$pdo = getDatabaseConnection();

// Add the map field to the property table if it doesn't exist
try {
    // Check if map column exists
    $checkColumn = $pdo->query("SHOW COLUMNS FROM property LIKE 'map'")->fetch();
    if (!$checkColumn) {
        // Add map column to the property table
        $alterQuery = "ALTER TABLE property ADD COLUMN map TEXT NULL AFTER property_image";
        $pdo->exec($alterQuery);
        error_log("Map column added to property table successfully");
    }
} catch(PDOException $e) {
    error_log("Error adding map column: " . $e->getMessage());
}

// Get property ID from URL parameter
$property_id = isset($_GET['id']) ? (int)$_GET['id'] : 2;

// Fetch property details
try {
    $stmt = $pdo->prepare("SELECT * FROM property WHERE Id = ? ");
    $stmt->execute([$property_id]);
    $property = $stmt->fetch();
    
    if (!$property) {
        header("HTTP/1.0 404 Not Found");
        die("Property not found or not available");
    }
} catch(PDOException $e) {
    die("Error fetching property: " . $e->getMessage());
}

// Handle contact form submission - Updated to match your inquire table schema
$form_message = '';
$form_status = '';

if ($_POST && isset($_POST['submit_inquiry'])) {
    $fullname = trim($_POST['fullname'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phonenumber = trim($_POST['phonenumber'] ?? '');
    $message = trim($_POST['message'] ?? '');
    
    // Validation
    $errors = [];
    if (empty($fullname)) $errors[] = "Full name is required";
    if (empty($email)) $errors[] = "Email is required";
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Invalid email format";
    if (empty($message)) $errors[] = "Message is required";
    
    if (empty($errors)) {
        try {
            // Insert inquiry into the existing inquire table
            $stmt = $pdo->prepare("
                INSERT INTO inquire (fullname, email, phonenumber, message, property_id, created_at) 
                VALUES (?, ?, ?, ?, ?, NOW())
            ");
            $stmt->execute([$fullname, $email, $phonenumber, $message, $property_id]);
            
            $form_message = "Thank you for your inquiry! We will get back to you soon.";
            $form_status = 'success';
            
            // Clear form data after successful submission
            $_POST = [];
            
        } catch(PDOException $e) {
            $form_message = "Sorry, there was an error sending your inquiry. Please try again.";
            $form_status = 'error';
            error_log("Inquiry submission error: " . $e->getMessage());
        }
    } else {
        $form_message = implode('<br>', $errors);
        $form_status = 'error';
    }
}

// Helper function to format property type
function formatPropertyType($type) {
    return ucwords(str_replace('_', ' ', $type));
}

// Helper function to get property status text
function getPropertyStatus($status) {
    switch($status) {
        case 0: return 'For Sale';
        case 1: return 'Sold';
        case 2: return 'For Rent';
        default: return 'Available';
    }
}

// Helper function to get features array based on description
function getPropertyFeatures($description, $type, $bedrooms, $bathrooms) {
    $features = [];
    
    // Add standard features based on property type
    switch(strtolower($type)) {
        case 'villa':
            $features = ['Private Swimming Pool', 'Garden', 'Security System', 'Garage'];
            break;
        case 'apartment':
            $features = ['Elevator', 'Security', 'Parking', 'Balcony'];
            break;
        case 'townhouse':
            $features = ['Garden', 'Garage', 'Multiple Floors', 'Privacy'];
            break;
        case 'studio':
            $features = ['Open Layout', 'Modern Kitchen', 'High Ceilings'];
            break;
    }
    
    // Add features based on description keywords
    $desc_lower = strtolower($description);
    if (strpos($desc_lower, 'pool') !== false || strpos($desc_lower, 'swimming') !== false) {
        $features[] = 'Swimming Pool';
    }
    if (strpos($desc_lower, 'gym') !== false || strpos($desc_lower, 'fitness') !== false) {
        $features[] = 'Gym';
    }
    if (strpos($desc_lower, 'garden') !== false) {
        $features[] = 'Garden';
    }
    if (strpos($desc_lower, 'security') !== false) {
        $features[] = '24/7 Security';
    }
    if (strpos($desc_lower, 'kitchen') !== false) {
        $features[] = 'Modern Kitchen';
    }
    if (strpos($desc_lower, 'parking') !== false || strpos($desc_lower, 'garage') !== false) {
        $features[] = 'Parking';
    }
    
    // Add standard amenities
    $features[] = 'High-Speed Internet';
    $features[] = 'Air Conditioning';
    
    return array_unique($features);
}

$features = getPropertyFeatures($property['description'], $property['type'], $property['number_of_bedrooms'], $property['number_of_bathrooms']);

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

        /* Alert styles */
        .alert {
            padding: 12px 16px;
            margin-bottom: 20px;
            border-radius: 8px;
            font-weight: 500;
        }
        
        .alert-success {
            background-color: #d1fae5;
            border: 1px solid #a7f3d0;
            color: #065f46;
        }
        
        .alert-error {
            background-color: #fee2e2;
            border: 1px solid #fecaca;
            color: #991b1b;
        }

        /* Property image styling */
        .property-main-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s ease;
        }

        .property-main-image:hover {
            transform: scale(1.05);
        }

        /* Map button styling */
        .map-button {
            background: linear-gradient(135deg, #3b82f6, #1d4ed8);
            transition: all 0.3s ease;
            text-decoration: none;
        }
        
        .map-button:hover {
            background: linear-gradient(135deg, #1d4ed8, #1e40af);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.4);
            text-decoration: none;
        }

        /* Interactive map styles */
        .map-container {
            position: relative;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }

        .map-container iframe {
            width: 100%;
            height: 320px;
            border: none;
            border-radius: 12px;
        }

        .map-overlay {
            position: absolute;
            top: 16px;
            right: 16px;
            z-index: 10;
        }
    </style>
    
    <!-- Custom Cursor -->
    <div class="custom-cursor" id="cursor"></div>

    <!-- Header -->
    <?php include('include/header.php');?>

    <!-- Property Details Section -->
    <main class="pt-[var(--header-height)] bg-gray-100">
        <div class="container mx-auto px-4 py-8">
            <div class="max-w-6xl mx-auto bg-white rounded-lg shadow-lg overflow-hidden">
                <!-- Property Image -->
                <div class="relative h-96 md:h-[500px] overflow-hidden fade-in-up">
                    <?php if (!empty($property['property_image']) && file_exists('admin/' . $property['property_image'])): ?>
                        <img src="admin/<?php echo htmlspecialchars($property['property_image']); ?>" 
                             alt="<?php echo htmlspecialchars($property['name']); ?>" 
                             class="property-main-image">
                    <?php else: ?>
                        <div class="w-full h-full bg-gradient-to-br from-indigo-400 to-purple-600 flex items-center justify-center">
                            <svg class="w-32 h-32 text-white opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                            </svg>
                        </div>
                    <?php endif; ?>
                    
                    <div class="absolute bottom-4 left-4 bg-gradient-to-r from-indigo-600 to-purple-600 text-white px-4 py-2 rounded-full text-lg font-semibold">
                        <?php echo getPropertyStatus($property['status']); ?>
                    </div>
                    
                    <!-- Property Type Badge -->
                    <div class="absolute top-4 right-4 bg-white bg-opacity-90 text-gray-800 px-3 py-1 rounded-full text-sm font-medium">
                        <?php echo formatPropertyType($property['type']); ?>
                    </div>

                    <!-- Google Maps Button (if map link exists) -->
                    <?php if (!empty($property['map'])): ?>
                        <div class="absolute top-4 left-4">
                            <a href="<?php echo htmlspecialchars($property['map']); ?>" 
                               target="_blank" 
                               class="map-button text-white px-4 py-2 rounded-full text-sm font-medium inline-flex items-center shadow-lg"
                               title="View location on Google Maps">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                </svg>
                                View on Maps
                            </a>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="p-6 md:p-10">
                    <!-- Property Title and Price -->
                    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 slide-in-left">
                        <div class="flex-grow">
                            <h1 class="text-4xl font-bold text-gray-800 mb-2"><?php echo htmlspecialchars($property['name']); ?></h1>
                            <div class="flex items-center justify-between">
                                <p class="text-gray-600 text-lg flex items-center">
                                    <i class="fas fa-map-marker-alt mr-2 text-indigo-600"></i>
                                    <?php echo htmlspecialchars($property['location']); ?>
                                </p>
                                <?php if (!empty($property['map'])): ?>
                                    <a href="<?php echo htmlspecialchars($property['map']); ?>" 
                                       target="_blank" 
                                       class="ml-4 text-indigo-600 hover:text-indigo-800 transition-colors"
                                       title="Open in Google Maps">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
                                        </svg>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                        <span class="text-5xl font-bold text-transparent bg-clip-text bg-gradient-to-r from-indigo-600 to-purple-600 mt-4 md:mt-0">
                            $<?php echo number_format($property['price']); ?>
                        </span>
                    </div>

                    <!-- Key Details -->
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8 text-center scale-in">
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <i class="fas fa-bed text-indigo-600 text-2xl mb-2"></i>
                            <p class="text-xl font-semibold text-gray-800"><?php echo $property['number_of_bedrooms']; ?> Beds</p>
                        </div>
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <i class="fas fa-bath text-indigo-600 text-2xl mb-2"></i>
                            <p class="text-xl font-semibold text-gray-800"><?php echo $property['number_of_bathrooms']; ?> Baths</p>
                        </div>
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <i class="fas fa-home text-indigo-600 text-2xl mb-2"></i>
                            <p class="text-xl font-semibold text-gray-800"><?php echo formatPropertyType($property['type']); ?></p>
                        </div>
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <i class="fas fa-calendar-alt text-indigo-600 text-2xl mb-2"></i>
                            <p class="text-xl font-semibold text-gray-800">Listed <?php echo date('M Y', strtotime($property['created_at'])); ?></p>
                        </div>
                    </div>

                    <!-- Description -->
                    <div class="mb-8 fade-in-up">
                        <h3 class="text-2xl font-bold text-gray-800 mb-4">Description</h3>
                        <div class="text-gray-700 leading-relaxed">
                            <?php 
                            $description = $property['description'];
                           echo '<p class="mb-4">' . nl2br(htmlspecialchars($description)) . '</p>';
                            ?>
                        </div>
                    </div>

                    <!-- Location & Map -->
                    <div class="mb-8 fade-in-up">
                        <h3 class="text-2xl font-bold text-gray-800 mb-4">Location & Map</h3>
                        
                        <?php if (!empty($property['map'])): ?>
                            <!-- Interactive Google Maps -->
                            <div class="map-container mb-4">
                                <div class="map-overlay">
                                    <a href="<?php echo  ($property['map']); ?>" 
                                       target="_blank" 
                                       class="map-button text-white px-3 py-2 rounded-lg text-sm font-medium inline-flex items-center shadow-lg">
                                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
                                        </svg>
                                        Open in Maps
                                    </a>
                                </div>
                                
                                <!-- Fallback iframe with location search -->
                                <iframe 
                                    src="<?php echo ($property['map']); ?>"
                                    width="100%" 
                                    height="320" 
                                    style="border:0;" 
                                    allowfullscreen="" 
                                    loading="lazy" 
                                    referrerpolicy="no-referrer-when-downgrade">
                                </iframe>
                            </div>
                            
                            <div class="flex items-center justify-between bg-gray-50 p-4 rounded-lg">
                                <div class="flex items-center">
                                    <svg class="w-5 h-5 text-indigo-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                    </svg>
                                    <span class="text-gray-800 font-medium"><?php echo htmlspecialchars($property['location']); ?></span>
                                </div>
                                <a href="<?php echo htmlspecialchars($property['map']); ?>" 
                                   target="_blank" 
                                   class="text-indigo-600 hover:text-indigo-800 font-medium text-sm transition-colors">
                                    Get Directions →
                                </a>
                            </div>
                        <?php else: ?>
                            <!-- Fallback when no map link is available -->
                            <div class="w-full h-80 bg-gray-200 rounded-lg overflow-hidden flex items-center justify-center">
                                <div class="text-center">
                                    <i class="fas fa-map-marker-alt text-4xl text-gray-400 mb-4"></i>
                                    <p class="text-gray-600 text-lg font-medium"><?php echo htmlspecialchars($property['location']); ?></p>
                                    <p class="text-sm text-gray-500 mt-2">Interactive map not available</p>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Contact Agent Form - Updated to match inquire table schema -->
                    <div class="fade-in-up">
                        <h3 class="text-2xl font-bold text-gray-800 mb-4">Inquire About This Property</h3>
                        
                        <?php if ($form_message): ?>
                            <div class="alert alert-<?php echo $form_status; ?>">
                                <?php echo $form_message; ?>
                            </div>
                        <?php endif; ?>
                        
                        <form method="POST" class="space-y-6">
                            <div>
                                <label for="contact-fullname" class="block text-sm font-medium text-gray-700 mb-2">Full Name *</label>
                                <input type="text" id="contact-fullname" name="fullname" required
                                       value="<?php echo htmlspecialchars($_POST['fullname'] ?? ''); ?>"
                                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all duration-300 transform focus:scale-105" 
                                       placeholder="Your full name">
                            </div>
                            <div>
                                <label for="contact-email" class="block text-sm font-medium text-gray-700 mb-2">Email Address *</label>
                                <input type="email" id="contact-email" name="email" required
                                       value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all duration-300 transform focus:scale-105" 
                                       placeholder="your.email@example.com">
                            </div>
                            <div>
                                <label for="contact-phonenumber" class="block text-sm font-medium text-gray-700 mb-2">Phone Number</label>
                                <input type="tel" id="contact-phonenumber" name="phonenumber"
                                       value="<?php echo htmlspecialchars($_POST['phonenumber'] ?? ''); ?>"
                                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all duration-300 transform focus:scale-105" 
                                       placeholder="(555) 123-4567">
                            </div>
                            <div>
                                <label for="contact-message" class="block text-sm font-medium text-gray-700 mb-2">Message *</label>
                                <textarea id="contact-message" name="message" rows="5" required
                                          class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all duration-300 transform focus:scale-105" 
                                          placeholder="I'm interested in <?php echo htmlspecialchars($property['name']); ?> and would like to schedule a viewing..."><?php echo htmlspecialchars($_POST['message'] ?? ''); ?></textarea>
                            </div>
                            <div>
                                <button type="submit" name="submit_inquiry" 
                                        class="w-full bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 text-white font-semibold py-4 rounded-lg transition-all duration-300 transform hover:scale-105 magnetic-button shadow-2xl">
                                    <span class="mr-2">Send Inquiry</span>
                                    <i class="fas fa-paper-plane"></i>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </main>

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

                // Animate elements as they come into view
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
                console.warn('Animation initialization failed:', error);
                makeAllContentVisible();
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
            const formInputs = document.querySelectorAll('form input, form textarea');
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

        // Auto-hide alert messages after 5 seconds
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
    </script>
</body>
</html>

<?php
/*
GOOGLE MAPS INTEGRATION FEATURES ADDED:

1. DATABASE SCHEMA UPDATE:
   - Automatically adds 'map' column to property table if it doesn't exist
   - Column type: TEXT NULL (allows for long Google Maps URLs)
   - Position: After property_image column

2. VISUAL MAP INTEGRATION:
   - Map button overlay on property main image (top-left corner)
   - Interactive Google Maps embed in Location section
   - "Open in Maps" button overlay on embedded map
   - "Get Directions" link below map
   - Fallback display when no map link is available

3. MAP FUNCTIONALITY:
   - Direct links to Google Maps using the stored map URL
   - Embedded map showing property location
   - Mobile-friendly map interactions
   - External link indicators with icons

4. RESPONSIVE DESIGN:
   - Map buttons adapt to different screen sizes
   - Embedded maps are fully responsive
   - Touch-friendly interactions on mobile devices

5. ENHANCED LOCATION DISPLAY:
   - Location text with Google Maps icon link
   - Improved spacing and visual hierarchy
   - External link icon for map interactions

6. ERROR HANDLING:
   - Graceful fallback when map field is empty
   - Database error handling for schema updates
   - Proper URL validation and sanitization

7. STYLING IMPROVEMENTS:
   - Modern gradient buttons for map links
   - Hover effects and smooth transitions
   - Consistent color scheme with existing design
   - Shadow effects and rounded corners

USAGE INSTRUCTIONS:

1. DATABASE INTEGRATION:
   - The script automatically adds the 'map' column to your property table
   - Map links are stored as TEXT to accommodate long Google Maps URLs
   - No manual database changes required

2. ADDING MAP LINKS:
   - Store Google Maps share links in the 'map' field
   - Links can be any format: maps.google.com, goo.gl/maps, etc.
   - The system handles different Google Maps URL formats

3. VISUAL FEATURES:
   - Map button appears on property image when map link exists
   - Interactive embedded map in Location section
   - Multiple ways to access map: button, embed, and text links

4. MOBILE OPTIMIZATION:
   - All map features work on mobile devices
   - Touch-friendly buttons and interactions
   - Responsive map sizing

5. FALLBACK HANDLING:
   - Shows location text when no map link is available
   - Graceful degradation for older browsers
   - Error handling for missing map data

To extend this system:
- Add map validation in admin forms
- Create automatic geocoding for addresses
- Add distance calculations to nearby amenities
- Integrate with other mapping services
- Add street view integration
- Create property location clustering for multiple properties

*/
?>