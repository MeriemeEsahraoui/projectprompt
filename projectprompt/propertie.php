
  <?php include('include/head.php');?>
<body class="bg-gray-100">
  <style>
        /* Custom styles for animations */
        .hero-overlay {
            background: linear-gradient(135deg, rgba(0,0,0,0.7) 0%, rgba(79,70,229,0.4) 100%);
        }
        
        /* Removed .floating-element and @keyframes float */
        
        /* Remove problematic initial styles and add fallbacks */
        .property-card,
        .fade-in-up,
        .scale-in,
        .slide-in-left,
        .slide-in-right {
            /* Remove initial transforms that could hide content */
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
            --header-height: 64px; /* Approximate height of the header */
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
                <!-- Property Image Gallery/Carousel -->
                <div class="relative h-96 md:h-[500px] overflow-hidden fade-in-up">
                    <img src="img/Luxury Villa in Beverly Hills.jpg" alt="Luxury Villa Exterior" class="w-full h-full object-cover">
                    <!-- Add more images here for a carousel effect if desired -->
                    <div class="absolute bottom-4 left-4 bg-gradient-to-r from-indigo-600 to-purple-600 text-white px-4 py-2 rounded-full text-lg font-semibold">
                        For Sale
                    </div>
                </div>

                <div class="p-6 md:p-10">
                    <!-- Property Title and Price -->
                    <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 slide-in-left">
                        <div>
                            <h1 class="text-4xl font-bold text-gray-800 mb-2">Luxury Villa in Beverly Hills</h1>
                            <p class="text-gray-600 text-lg flex items-center">
                                <i class="fas fa-map-marker-alt mr-2 text-indigo-600"></i>
                                Beverly Hills, CA 90210
                            </p>
                        </div>
                        <span class="text-5xl font-bold text-transparent bg-clip-text bg-gradient-to-r from-indigo-600 to-purple-600 mt-4 md:mt-0">$2,850,000</span>
                    </div>

                    <!-- Key Details -->
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8 text-center scale-in">
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <i class="fas fa-bed text-indigo-600 text-2xl mb-2"></i>
                            <p class="text-xl font-semibold text-gray-800">4 Beds</p>
                        </div>
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <i class="fas fa-bath text-indigo-600 text-2xl mb-2"></i>
                            <p class="text-xl font-semibold text-gray-800">3 Baths</p>
                        </div>
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <i class="fas fa-ruler-combined text-indigo-600 text-2xl mb-2"></i>
                            <p class="text-xl font-semibold text-gray-800">250 m²</p>
                        </div>
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <i class="fas fa-car text-indigo-600 text-2xl mb-2"></i>
                            <p class="text-xl font-semibold text-gray-800">2 Garage</p>
                        </div>
                    </div>

                    <!-- Description -->
                    <div class="mb-8 fade-in-up">
                        <h3 class="text-2xl font-bold text-gray-800 mb-4">Description</h3>
                        <p class="text-gray-700 leading-relaxed">
                            Nestled in the prestigious hills of Beverly Hills, this exquisite villa offers unparalleled luxury and breathtaking city views. Boasting 250 square meters of meticulously designed living space, this property is a true masterpiece of modern architecture and sophisticated comfort. From the grand entrance to the expansive outdoor entertaining areas, every detail has been thoughtfully curated to provide an exceptional living experience.
                        </p>
                        <p class="text-gray-700 leading-relaxed mt-4">
                            The open-concept layout seamlessly connects the gourmet kitchen, elegant dining area, and spacious living room, all bathed in natural light. Retreat to one of the four generously sized bedrooms, each offering privacy and tranquility. The master suite is a sanctuary with a spa-like ensuite bathroom and a private balcony overlooking the stunning landscape. This is more than a home; it's a lifestyle.
                        </p>
                    </div>

                    <!-- Features & Amenities -->
                    <div class="mb-8 slide-in-right">
                        <h3 class="text-2xl font-bold text-gray-800 mb-4">Features & Amenities</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 text-gray-700">
                            <div class="flex items-center space-x-3">
                                <i class="fas fa-swimming-pool text-indigo-600"></i>
                                <span>Private Swimming Pool</span>
                            </div>
                            <div class="flex items-center space-x-3">
                                <i class="fas fa-dumbbell text-indigo-600"></i>
                                <span>Home Gym</span>
                            </div>
                            <div class="flex items-center space-x-3">
                                <i class="fas fa-wifi text-indigo-600"></i>
                                <span>High-Speed Internet</span>
                            </div>
                            <div class="flex items-center space-x-3">
                                <i class="fas fa-fan text-indigo-600"></i>
                                <span>Central Air Conditioning</span>
                            </div>
                            <div class="flex items-center space-x-3">
                                <i class="fas fa-fire-extinguisher text-indigo-600"></i>
                                <span>Fireplace</span>
                            </div>
                            <div class="flex items-center space-x-3">
                                <i class="fas fa-shield-alt text-indigo-600"></i>
                                <span>24/7 Security</span>
                            </div>
                            <div class="flex items-center space-x-3">
                                <i class="fas fa-utensils text-indigo-600"></i>
                                <span>Gourmet Kitchen</span>
                            </div>
                            <div class="flex items-center space-x-3">
                                <i class="fas fa-tree text-indigo-600"></i>
                                <span>Landscaped Garden</span>
                            </div>
                            <div class="flex items-center space-x-3">
                                <i class="fas fa-video text-indigo-600"></i>
                                <span>Home Theater</span>
                            </div>
                        </div>
                    </div>

                    <!-- Location Map (Placeholder) -->
                    <div class="mb-8 fade-in-up">
  <h3 class="text-2xl font-bold text-gray-800 mb-4">Location</h3>
  <div class="w-full h-80 bg-gray-200 rounded-lg overflow-hidden">
    <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d53379.816710574276!2d-7.622173899919255!3d33.260246756806275!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0xda63ce8fa04c5cf%3A0xc1041e48089e20f!2sBerrechid!5e0!3m2!1sfr!2sma!4v1753782857720!5m2!1sfr!2sma" width="1200" height="450" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
  </div>
</div>


                    <!-- Contact Agent Form -->
                    <div class="fade-in-up">
                        <h3 class="text-2xl font-bold text-gray-800 mb-4">Inquire About This Property</h3>
                        <form class="space-y-6">
                            <div>
                                <label for="contact-name" class="block text-sm font-medium text-gray-700 mb-2">Full Name</label>
                                <input type="text" id="contact-name" name="name" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all duration-300 transform focus:scale-105" placeholder="Your full name">
                            </div>
                            <div>
                                <label for="contact-email" class="block text-sm font-medium text-gray-700 mb-2">Email Address</label>
                                <input type="email" id="contact-email" name="email" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all duration-300 transform focus:scale-105" placeholder="your.email@example.com">
                            </div>
                            <div>
                                <label for="contact-phone" class="block text-sm font-medium text-gray-700 mb-2">Phone Number (Optional)</label>
                                <input type="tel" id="contact-phone" name="phone" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all duration-300 transform focus:scale-105" placeholder="(555) 123-4567">
                            </div>
                            <div>
                                <label for="contact-message" class="block text-sm font-medium text-gray-700 mb-2">Message</label>
                                <textarea id="contact-message" name="message" rows="5" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all duration-300 transform focus:scale-105" placeholder="I'm interested in this property and would like to schedule a viewing..."></textarea>
                            </div>
                            <div>
                                <button type="submit" class="w-full bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 text-white font-semibold py-4 rounded-lg transition-all duration-300 transform hover:scale-105 magnetic-button shadow-2xl">
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