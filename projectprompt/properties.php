<?php 
// Include database configuration (this will start the session)
require_once 'include/config.php';
// Get database connection
$pdo = getDatabaseConnection();
// Pagination setup
$propertiesPerPage = 6;
$currentPage = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($currentPage - 1) * $propertiesPerPage;

// Search functionality
$searchLocation = isset($_GET['location']) ? trim($_GET['location']) : '';
$searchType = isset($_GET['type']) ? trim($_GET['type']) : '';
$searchPrice = isset($_GET['price']) ? floatval($_GET['price']) : 0;

// FIXED: Build search query properly
$whereConditions = ['1=1']; // Start with always-true condition
$params = [];

if (!empty($searchLocation)) {
    $whereConditions[] = 'location LIKE ?';
    $params[] = '%' . $searchLocation . '%';
}

if (!empty($searchType)) {
    $whereConditions[] = 'type = ?';
    $params[] = $searchType;
}

if ($searchPrice > 0) {
    $whereConditions[] = 'price <= ?';
    $params[] = $searchPrice;
}

$whereClause = implode(' AND ', $whereConditions);

// Debug information (will appear as HTML comments)
$debugInfo = [
    'whereClause' => $whereClause,
    'params' => $params,
    'searchLocation' => $searchLocation,
    'searchType' => $searchType,
    'searchPrice' => $searchPrice
];

// Get total count for pagination
try {
    $countStmt = $pdo->prepare("SELECT COUNT(*) FROM property WHERE $whereClause");
    $countStmt->execute($params);
    $totalProperties = $countStmt->fetchColumn();
    $totalPages = ceil($totalProperties / $propertiesPerPage);
} catch(PDOException $e) {
    $totalProperties = 0;
    $totalPages = 1;
    error_log("Error counting properties: " . $e->getMessage());
}

// Fetch properties
try {
    $sql = "SELECT * FROM property WHERE $whereClause ORDER BY created_at DESC LIMIT $propertiesPerPage OFFSET $offset";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $properties = $stmt->fetchAll();
} catch(PDOException $e) {
    $properties = [];
    error_log("Error fetching properties: " . $e->getMessage());
}

// Get property types for dropdown
try {
    $typesStmt = $pdo->prepare("SELECT DISTINCT type FROM property WHERE type IS NOT NULL AND type != '' ORDER BY type");
    $typesStmt->execute();
    $propertyTypes = $typesStmt->fetchAll(PDO::FETCH_COLUMN);
    if (empty($propertyTypes)) {
        $propertyTypes = ['house', 'apartment', 'condo', 'villa', 'townhouse', 'land'];
    }
} catch(PDOException $e) {
    $propertyTypes = ['house', 'apartment', 'condo', 'villa', 'townhouse', 'land'];
}

include('include/head.php');
?>
<body class="bg-gray-100">
    <!-- Debug Information (visible in page source) -->
    <!-- DEBUG INFO: 
    Total Properties: <?php echo $totalProperties; ?>
    Properties Retrieved: <?php echo count($properties); ?>
    Where Clause: <?php echo htmlspecialchars($whereClause); ?>
    Parameters: <?php echo htmlspecialchars(json_encode($params)); ?>
    Current Page: <?php echo $currentPage; ?>
    Offset: <?php echo $offset; ?>
    -->

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

        /* Styles for the filter form within the hero */
        .filter-form-container {
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(10px);
            border-radius: 1rem;
            padding: 2rem;
            max-width: 900px;
            width: 100%;
        }
        .filter-input {
            background: rgba(255, 255, 255, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.3);
            color: white;
        }
        .filter-input::placeholder {
            color: rgba(255, 255, 255, 0.7);
        }
        .filter-input:focus {
            border-color: #818cf8;
            outline: none;
            box-shadow: 0 0 0 3px rgba(129, 140, 248, 0.5);
        }
        .filter-input option {
            background: #374151;
            color: white;
        }

        /* Debug styles */
        .debug-info {
            background: #f3f4f6;
            border: 1px solid #d1d5db;
            border-radius: 0.5rem;
            padding: 1rem;
            margin: 1rem 0;
            font-family: monospace;
            font-size: 0.875rem;
        }
    </style>
    
    <!-- Custom Cursor -->
    <div class="custom-cursor" id="cursor"></div>

    <!-- Header -->
    <?php include('include/header.php');?>

    <!-- Hero Section for Properties Page -->
    <section id="properties-hero" class="relative h-screen flex flex-col justify-center items-center bg-cover bg-center bg-no-repeat overflow-hidden pt-[var(--header-height)]" style="background-image: url('img/flowers-grass-ladder-.jpg');">
        <!-- Particles Background -->
        <div class="particles" id="particles-properties"></div>

        <!-- Optional dark overlay for better text readability -->
        <div class="absolute inset-0 bg-black/40"></div>

        <!-- Hero content with search and filters -->
        <div class="text-center text-white px-4 max-w-4xl relative z-10 w-full">
            <div class="fade-in-up" id="propertiesHeroTitle">
                <h2 class="text-5xl md:text-7xl font-bold mb-6 leading-tight">
                    Discover Your 
                    <span class="text-transparent bg-clip-text bg-gradient-to-r from-indigo-400 to-purple-400" id="perfectPropertyText">Perfect</span> 
                    Property
                </h2>
            </div>
            <div class="fade-in-up filter-form-container mt-8" id="filterFormContainer" style="animation-delay: 0.3s;">
                <form method="GET" action="" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 text-left">
                    <div>
                        <label for="location" class="block text-sm font-medium text-gray-200 mb-1">Location</label>
                        <input type="text" id="location" name="location" 
                               value="<?php echo htmlspecialchars($searchLocation); ?>"
                               placeholder="e.g., New York, Miami" 
                               class="filter-input w-full px-4 py-2 rounded-lg">
                    </div>
                    <div>
                        <label for="type" class="block text-sm font-medium text-gray-200 mb-1">Property Type</label>
                        <select id="type" name="type" class="filter-input w-full px-4 py-2 rounded-lg">
                            <option value="">Any Type</option>
                            <?php foreach ($propertyTypes as $type): ?>
                                <option value="<?php echo htmlspecialchars($type); ?>" 
                                        <?php echo $searchType === $type ? 'selected' : ''; ?>>
                                    <?php echo ucfirst(htmlspecialchars($type)); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label for="price" class="block text-sm font-medium text-gray-200 mb-1">Max Price</label>
                        <input type="number" id="price" name="price" 
                               value="<?php echo $searchPrice > 0 ? $searchPrice : ''; ?>"
                               placeholder="500000" 
                               class="filter-input w-full px-4 py-2 rounded-lg">
                    </div>
                    <div class="md:col-span-2 lg:col-span-1 flex items-end">
                        <button type="submit" class="w-full bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 text-white font-semibold py-2 px-4 rounded-lg transition-all duration-300 transform hover:scale-105 magnetic-button">
                            Search Properties
                        </button>
                    </div>
                </form>
                
                <!-- Clear filters button -->
                <?php if (!empty($searchLocation) || !empty($searchType) || $searchPrice > 0): ?>
                <div class="mt-4 text-center">
                    <a href="?" class="text-white/70 hover:text-white text-sm underline">
                        Clear All Filters
                    </a>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Scroll Indicator -->
        <div class="absolute bottom-8 left-1/2 transform -translate-x-1/2 fade-in-up z-10" id="scrollIndicatorProperties" style="animation-delay: 1s;">
            <div class="flex flex-col items-center text-white">
                <span class="text-sm mb-2 opacity-75">Scroll Down</span>
                <div class="w-6 h-10 border-2 border-white rounded-full flex justify-center">
                    <div class="w-1 h-3 bg-white rounded-full mt-2 animate-bounce"></div>
                </div>
            </div>
        </div>
    </section>

    <!-- Property Listings Section -->
    <section id="listings" class="py-16 bg-gray-100">
        <div class="container mx-auto px-4">
            <div class="text-center mb-12">
                <div class="fade-in-up">
                    <h2 class="text-4xl font-bold text-gray-800 mb-4">Available Properties</h2>
                    <p class="text-xl text-gray-600 max-w-2xl mx-auto">
                        <?php if ($totalProperties > 0): ?>
                            Showing <?php echo min($propertiesPerPage, $totalProperties - $offset); ?> of <?php echo $totalProperties; ?> properties
                        <?php else: ?>
                            No properties found matching your criteria
                        <?php endif; ?>
                    </p>
                </div>
            </div>

            <!-- Debug Information (remove in production) -->
            <?php if (isset($_GET['debug'])): ?>
            <div class="debug-info mb-8">
                <h3>Debug Information:</h3>
                <p><strong>Total Properties:</strong> <?php echo $totalProperties; ?></p>
                <p><strong>Properties Retrieved:</strong> <?php echo count($properties); ?></p>
                <p><strong>Where Clause:</strong> <?php echo htmlspecialchars($whereClause); ?></p>
                <p><strong>Parameters:</strong> <?php echo htmlspecialchars(json_encode($params)); ?></p>
                <p><strong>SQL Query:</strong> <?php echo htmlspecialchars($sql ?? 'Not set'); ?></p>
                <?php if (!empty($properties)): ?>
                <p><strong>First Property Columns:</strong> <?php echo implode(', ', array_keys($properties[0])); ?></p>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <!-- Property Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8" id="propertyGrid">
                <?php if (!empty($properties)): ?>
                    <?php foreach ($properties as $index => $property): ?>
                        <div class="property-card bg-white rounded-xl shadow-lg overflow-hidden transform transition-all duration-300 hover:shadow-2xl">
                            <div class="relative overflow-hidden">
                                <?php 
                                // Handle different possible image column names
                                $imageSrc = $property['property_image'] ?? $property['image'] ?? $property['photo'] ?? 'img/placeholder-property.jpg';
                                $propertyName = $property['name'] ?? $property['title'] ?? $property['property_name'] ?? 'Property Name Not Available';
                                $propertyLocation = $property['location'] ?? $property['address'] ?? 'Location not specified';
                                $propertyPrice = $property['price'] ?? 0;
                                $propertyType = $property['type'] ?? $property['property_type'] ?? 'Property';
                                $propertyStatus = $property['status'] ?? null;
                                $propertyDescription = $property['description'] ?? $property['details'] ?? 'No description available';
                                $bedrooms = $property['number_of_bedrooms'] ?? $property['bedrooms'] ?? $property['beds'] ?? 0;
                                $bathrooms = $property['number_of_bathrooms'] ?? $property['bathrooms'] ?? $property['baths'] ?? 0;
                                $createdAt = $property['created_at'] ?? $property['date_created'] ?? null;
                                $propertyId = $property['Id'] ?? $property['id'] ?? $property['property_id'] ?? 0;
                                ?>
                                
                                <img src="admin/<?php echo htmlspecialchars($imageSrc); ?>" 
                                     alt="<?php echo htmlspecialchars($propertyName); ?>" 
                                     class="w-full h-64 object-cover transition-transform duration-300"
                                     onerror="this.src='img/placeholder-property.jpg';">
                                     
                                <div class="absolute top-4 left-4">
                                    <span class="bg-indigo-600 text-white px-3 py-1 rounded-full text-sm font-semibold">
                                        <?php echo ucfirst(htmlspecialchars($propertyType)); ?>
                                    </span>
                                </div>
                                <div class="absolute top-4 right-4">
                                    <span class="bg-white/90 text-gray-800 px-3 py-1 rounded-full text-sm font-bold">
                                        $<?php echo number_format($propertyPrice); ?>
                                    </span>
                                </div>
                                <?php if ($propertyStatus == 2 || $propertyStatus === 'sold'): ?>
                                    <div class="absolute bottom-4 left-4">
                                        <span class="bg-red-600 text-white px-3 py-1 rounded-full text-sm font-semibold">
                                            SOLD
                                        </span>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="p-6">
                                <h3 class="text-xl font-bold text-gray-800 mb-2">
                                    <?php echo htmlspecialchars($propertyName); ?>
                                </h3>
                                <p class="text-gray-600 mb-4 flex items-center">
                                    <i class="fas fa-map-marker-alt mr-2 text-indigo-600"></i>
                                    <?php echo htmlspecialchars($propertyLocation); ?>
                                </p>
                                <p class="text-gray-600 mb-4 line-clamp-2">
                                    <?php echo htmlspecialchars(substr($propertyDescription, 0, 100)) . (strlen($propertyDescription) > 100 ? '...' : ''); ?>
                                </p>
                                <div class="flex items-center justify-between text-sm text-gray-500 mb-4">
                                    <span class="flex items-center">
                                        <i class="fas fa-bed mr-1"></i>
                                        <?php echo $bedrooms; ?> Bed<?php echo $bedrooms != 1 ? 's' : ''; ?>
                                    </span>
                                    <span class="flex items-center">
                                        <i class="fas fa-bath mr-1"></i>
                                        <?php echo $bathrooms; ?> Bath<?php echo $bathrooms != 1 ? 's' : ''; ?>
                                    </span>
                                    <span class="flex items-center text-indigo-600">
                                        <i class="fas fa-calendar mr-1"></i>
                                        <?php echo $createdAt ? date('M Y', strtotime($createdAt)) : 'Recent'; ?>
                                    </span>
                                </div>
                                <a href="propertie.php?id=<?php echo $propertyId; ?>" 
                                   class="w-full bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 text-white font-semibold py-3 px-6 rounded-lg transition-all duration-300 transform hover:scale-105 block text-center magnetic-button">
                                    View Details
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="col-span-full text-center py-12">
                        <div class="text-gray-500 text-xl">
                            <i class="fas fa-search mb-4 text-4xl"></i>
                            <p class="mb-2">No properties found</p>
                            <?php if ($totalProperties == 0): ?>
                                <p class="text-sm mb-4">There are no properties in the database yet.</p>
                                <a href="?debug=1" class="inline-block mt-2 bg-yellow-600 text-white px-4 py-2 rounded-lg hover:bg-yellow-700 transition-colors text-sm">
                                    View Debug Info
                                </a>
                            <?php else: ?>
                                <p class="text-sm">Try adjusting your search criteria</p>
                                <a href="?" class="inline-block mt-4 bg-indigo-600 text-white px-6 py-2 rounded-lg hover:bg-indigo-700 transition-colors">
                                    View All Properties
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
                <div class="flex justify-center items-center space-x-4 mt-12 fade-in-up">
                    <?php
                    $prevPage = max(1, $currentPage - 1);
                    $nextPage = min($totalPages, $currentPage + 1);
                    $queryParams = $_GET;
                    ?>
                    
                    <a href="?<?php echo http_build_query(array_merge($queryParams, ['page' => $prevPage])); ?>" 
                       class="bg-gray-200 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-300 transition-colors magnetic-button <?php echo $currentPage === 1 ? 'opacity-50 pointer-events-none' : ''; ?>">
                        Previous
                    </a>
                    
                    <span class="text-gray-700 font-semibold">
                        Page <?php echo $currentPage; ?> of <?php echo $totalPages; ?>
                    </span>
                    
                    <a href="?<?php echo http_build_query(array_merge($queryParams, ['page' => $nextPage])); ?>" 
                       class="bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700 transition-colors magnetic-button <?php echo $currentPage === $totalPages ? 'opacity-50 pointer-events-none' : ''; ?>">
                        Next
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- Footer -->
    <?php include('include/footer.php');?>

    <!-- GSAP Animations Script -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/gsap.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/ScrollTrigger.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/TextPlugin.min.js"></script>
    
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
            const elements = document.querySelectorAll('.property-card, .fade-in-up, .scale-in, .slide-in-left, .slide-in-right, #propertiesHeroTitle, #filterFormContainer, #scrollIndicatorProperties');
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

                // Hero Section Animations for Properties Page - start immediately
                const propertiesHeroTl = gsap.timeline();
                propertiesHeroTl.from("#propertiesHeroTitle", {duration: 1.5, y: 100, opacity: 0, ease: "power3.out"})
                                .from("#filterFormContainer", {duration: 1.2, y: 50, opacity: 0, ease: "power3.out"}, "-=1")
                                .from("#scrollIndicatorProperties", {duration: 0.8, y: 20, opacity: 0, ease: "power3.out"}, "-=0.5");

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
                // Property Cards Animation with stagger
                ScrollTrigger.batch(".property-card", {
                    onEnter: (elements) => {
                        gsap.fromTo(elements, 
                            { y: 100, opacity: 0 },
                            { 
                                y: 0, 
                                opacity: 1, 
                                duration: 1,
                                stagger: 0.15,
                                ease: "power3.out"
                            }
                        );
                    },
                    start: "top bottom-=100px"
                });

                // Fade in up elements
                ScrollTrigger.batch(".fade-in-up", {
                    onEnter: (elements) => {
                        gsap.fromTo(elements,
                            { y: 30, opacity: 0 },
                            { y: 0, opacity: 1, duration: 0.8, stagger: 0.1, ease: "power2.out" }
                        );
                    },
                    start: "top bottom-=50px"
                });

            } catch (error) {
                console.warn('Scroll animations failed:', error);
            }
        }

        function initializeInteractiveElements() {
            // Property card hover effects
            const propertyCards = document.querySelectorAll('.property-card');
            propertyCards.forEach(card => {
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

            // Magnetic button effects
            const magneticButtons = document.querySelectorAll('.magnetic-button');
            magneticButtons.forEach(button => {
                button.addEventListener('mouseenter', function() {
                    if (typeof gsap !== 'undefined') {
                        gsap.to(this, {duration: 0.3, scale: 1.05, ease: "power2.out"});
                    }
                });
                
                button.addEventListener('mouseleave', function() {
                    if (typeof gsap !== 'undefined') {
                        gsap.to(this, {duration: 0.3, scale: 1, ease: "power2.out"});
                    }
                });
            });
        }

        // Enhanced search functionality
        document.addEventListener('DOMContentLoaded', function() {
            const searchForm = document.querySelector('#filterFormContainer form');
            
            // Form validation
            if (searchForm) {
                searchForm.addEventListener('submit', function(e) {
                    const priceInput = document.getElementById('price');
                    const price = parseFloat(priceInput.value);
                    
                    if (price && price < 0) {
                        e.preventDefault();
                        alert('Please enter a valid price');
                        priceInput.focus();
                        return false;
                    }
                });
            }
        });

        // Lazy loading for property images
        document.addEventListener('DOMContentLoaded', function() {
            const images = document.querySelectorAll('.property-card img');
            
            if (window.IntersectionObserver) {
                const imageObserver = new IntersectionObserver((entries, observer) => {
                    entries.forEach(entry => {
                        if (entry.isIntersecting) {
                            const img = entry.target;
                            // Image is already loaded, just ensure it's visible
                            img.style.opacity = '1';
                            observer.unobserve(img);
                        }
                    });
                });

                images.forEach(img => {
                    img.style.opacity = '0';
                    img.style.transition = 'opacity 0.3s ease';
                    imageObserver.observe(img);
                });
            }
        });

        // Custom cursor functionality
        document.addEventListener('DOMContentLoaded', function() {
            const cursor = document.getElementById('cursor');
            
            if (cursor) {
                document.addEventListener('mousemove', function(e) {
                    if (typeof gsap !== 'undefined') {
                        gsap.to(cursor, {
                            x: e.clientX,
                            y: e.clientY,
                            duration: 0.1,
                            ease: "power2.out"
                        });
                    } else {
                        cursor.style.left = e.clientX + 'px';
                        cursor.style.top = e.clientY + 'px';
                    }
                });

                // Hide cursor on touch devices
                if ('ontouchstart' in window) {
                    cursor.style.display = 'none';
                }
            }
        });

        // Particles animation
        function createParticles() {
            const particlesContainer = document.getElementById('particles-properties');
            
            if (!particlesContainer) return;

            const particleCount = 50;
            
            for (let i = 0; i < particleCount; i++) {
                const particle = document.createElement('div');
                particle.className = 'particle';
                
                // Random size between 2px and 6px
                const size = Math.random() * 4 + 2;
                particle.style.width = size + 'px';
                particle.style.height = size + 'px';
                
                // Random position
                particle.style.left = Math.random() * 100 + '%';
                particle.style.top = Math.random() * 100 + '%';
                
                // Random animation duration
                const duration = Math.random() * 10 + 10;
                
                if (typeof gsap !== 'undefined') {
                    gsap.set(particle, {
                        x: Math.random() * 100 - 50,
                        y: Math.random() * 100 - 50
                    });
                    
                    gsap.to(particle, {
                        x: Math.random() * 200 - 100,
                        y: Math.random() * 200 - 100,
                        duration: duration,
                        repeat: -1,
                        yoyo: true,
                        ease: "none"
                    });
                } else {
                    // Fallback CSS animation
                    particle.style.animation = `float ${duration}s ease-in-out infinite`;
                }
                
                particlesContainer.appendChild(particle);
            }
        }

        // Initialize particles
        setTimeout(createParticles, 1000);

        // Smooth scrolling for anchor links
        document.addEventListener('DOMContentLoaded', function() {
            const scrollIndicator = document.getElementById('scrollIndicatorProperties');
            
            if (scrollIndicator) {
                scrollIndicator.addEventListener('click', function() {
                    const listingsSection = document.getElementById('listings');
                    if (listingsSection) {
                        listingsSection.scrollIntoView({
                            behavior: 'smooth'
                        });
                    }
                });
            }
        });

        // Price input formatting
        document.addEventListener('DOMContentLoaded', function() {
            const priceInput = document.getElementById('price');
            
            if (priceInput) {
                priceInput.addEventListener('input', function() {
                    let value = this.value.replace(/[^\d]/g, '');
                    if (value) {
                        // Visual formatting in placeholder/title
                        this.setAttribute('title', `${new Intl.NumberFormat().format(value)}`);
                    }
                });

                // Format on focus out
                priceInput.addEventListener('blur', function() {
                    if (this.value) {
                        const numValue = parseFloat(this.value);
                        if (!isNaN(numValue)) {
                            this.value = Math.round(numValue);
                        }
                    }
                });
            }
        });

        // Search suggestions (basic implementation)
        document.addEventListener('DOMContentLoaded', function() {
            const locationInput = document.getElementById('location');
            
            if (locationInput) {
                const suggestions = [
                    'New York', 'Miami', 'Los Angeles', 'Chicago', 'Boston', 
                    'San Francisco', 'Seattle', 'Denver', 'Austin', 'Portland',
                    'Toronto', 'Vancouver', 'Montreal', 'Ottawa',
                    'London', 'Paris', 'Berlin', 'Madrid', 'Rome',
                    'Casablanca', 'Rabat', 'Marrakech', 'Fez', 'Tangier'
                ];
                
                // Create datalist for autocomplete
                const datalist = document.createElement('datalist');
                datalist.id = 'location-suggestions';
                
                suggestions.forEach(city => {
                    const option = document.createElement('option');
                    option.value = city;
                    datalist.appendChild(option);
                });
                
                locationInput.setAttribute('list', 'location-suggestions');
                locationInput.parentNode.appendChild(datalist);
            }
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
            console.log('Fallback visibility applied - Properties page ready');
            
            // Log debug information
            console.log('Properties loaded:', document.querySelectorAll('.property-card').length);
            console.log('Total properties from PHP:', <?php echo $totalProperties; ?>);
        }, 2000);

        // Performance monitoring
        window.addEventListener('load', function() {
            console.log('Properties page loaded successfully');
            console.log(`Displaying ${<?php echo count($properties); ?>} properties out of ${<?php echo $totalProperties; ?>} total`);
            
            // Check if we have properties to display
            const propertyCards = document.querySelectorAll('.property-card');
            if (propertyCards.length === 0 && <?php echo $totalProperties; ?> > 0) {
                console.warn('Warning: Properties exist in database but cards are not displaying');
            }
        });

        // Add CSS keyframes for fallback animations
        const style = document.createElement('style');
        style.textContent = `
            @keyframes float {
                0%, 100% { transform: translateY(0px) translateX(0px); }
                33% { transform: translateY(-20px) translateX(10px); }
                66% { transform: translateY(10px) translateX(-10px); }
            }
            
            @keyframes fadeInUp {
                from {
                    opacity: 0;
                    transform: translateY(30px);
                }
                to {
                    opacity: 1;
                    transform: translateY(0);
                }
            }
            
            .property-card {
                animation: fadeInUp 0.6s ease forwards;
            }
            
            .property-card:nth-child(1) { animation-delay: 0.1s; }
            .property-card:nth-child(2) { animation-delay: 0.2s; }
            .property-card:nth-child(3) { animation-delay: 0.3s; }
            .property-card:nth-child(4) { animation-delay: 0.4s; }
            .property-card:nth-child(5) { animation-delay: 0.5s; }
            .property-card:nth-child(6) { animation-delay: 0.6s; }
        `;
        document.head.appendChild(style);
    </script>

    <!-- FontAwesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</body>
</html>