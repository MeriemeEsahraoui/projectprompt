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

    <!-- Contact Hero Section -->
   <section id="contact-hero" class="relative h-64 flex flex-col justify-center items-center bg-cover bg-center bg-no-repeat overflow-hidden pt-[var(--header-height)]" style="background-image: url('img/7_villa.jpg');">
    <!-- Particles Background -->
    <div class="particles" id="particles-contact"></div>

    <!-- Optional Dark Overlay -->
    <div class="absolute inset-0 bg-black/40"></div>

    <!-- Hero content -->
    <div class="text-center text-white px-4 max-w-4xl relative z-10">
        <div class="fade-in-up" id="contactHeroTitle">
            <h2 class="text-5xl md:text-7xl font-bold mb-4 leading-tight">
                Get In <span class="text-transparent bg-clip-text bg-gradient-to-r from-indigo-400 to-purple-400" id="touchText">Touch</span>
            </h2>
            <p class="text-xl md:text-2xl opacity-90">We're here to help you find your dream property.</p>
        </div>
    </div>
</section>


    <!-- Contact Content Section -->
    <section class="py-16 bg-white">
        <div class="container mx-auto px-4">
            <div class="max-w-4xl mx-auto">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-12">
                    <!-- Contact Form -->
                    <div class="slide-in-left">
                        <h3 class="text-3xl font-bold text-gray-800 mb-6 fade-in-up">Send Us a Message</h3>
                        <form class="space-y-6" id="contactForm">
                            <div class="fade-in-up">
                                <label for="name" class="block text-sm font-medium text-gray-700 mb-2">Full Name</label>
                                <input type="text" id="name" name="name" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all duration-300 transform focus:scale-105" placeholder="Your full name">
                            </div>
                            <div class="fade-in-up">
                                <label for="email" class="block text-sm font-medium text-gray-700 mb-2">Email Address</label>
                                <input type="email" id="email" name="email" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all duration-300 transform focus:scale-105" placeholder="your.email@example.com">
                            </div>
                            <div class="fade-in-up">
                                <label for="phone" class="block text-sm font-medium text-gray-700 mb-2">Phone Number (Optional)</label>
                                <input type="tel" id="phone" name="phone" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all duration-300 transform focus:scale-105" placeholder="(555) 123-4567">
                            </div>
                            <div class="fade-in-up">
                                <label for="message" class="block text-sm font-medium text-gray-700 mb-2">Message</label>
                                <textarea id="message" name="message" rows="5" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all duration-300 transform focus:scale-105" placeholder="Tell us about your property needs..."></textarea>
                            </div>
                          <div class="fade-in-up w-full">
    <a href="propertie.html"
       class="block w-full text-center bg-gradient-to-r from-indigo-600 to-purple-600 
              hover:from-indigo-700 hover:to-purple-700 text-white font-semibold 
              py-4 rounded-lg transition-all duration-300 transform hover:scale-105 
              magnetic-button shadow-2xl">
        <span class="mr-2">Send Message</span>
        <i class="fas fa-paper-plane"></i>
    </a>
</div>

                        </form>
                    </div>

                    <!-- Contact Information -->
                    <div class="space-y-8 slide-in-right">
                        <div class="fade-in-up">
                            <h3 class="text-3xl font-bold text-gray-800 mb-6">Contact Information</h3>
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
                            <h4 class="text-xl font-semibold text-gray-800 mb-4">Office Hours</h4>
                            <div class="space-y-2 text-gray-600">
                                <p>Monday - Friday: 9:00 AM - 6:00 PM</p>
                                <p>Saturday: 10:00 AM - 4:00 PM</p>
                                <p>Sunday: By Appointment</p>
                            </div>
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

                // Hero Section Animations for Contact Page - start immediately
                const contactHeroTl = gsap.timeline();
                contactHeroTl.from("#contactHeroTitle", {duration: 1.5, y: 100, opacity: 0, ease: "power3.out"});
                gsap.to("#touchText", {
                    duration: 3,
                    backgroundPosition: "200% center",
                    ease: "none",
                    repeat: -1
                });

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

        // Particles with error handling
        function createParticles() {
            try {
                const particlesContainer = document.getElementById('particles-contact'); // Use specific ID for this page
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