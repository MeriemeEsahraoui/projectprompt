<?php
// PHP Form Handler at the top of the page
$form_message = '';
$form_success = false;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_contact'])) {
    
    // EMAIL CONFIGURATION
    $smtp_host = 'smtp.gmail.com';
    $smtp_port = 587;
    $smtp_username = 'younesaj20@gmail.com';                    // Your actual Gmail address
    $smtp_password = 'drzqstrzrzioagad';                       // Your app password
    $from_email = 'younesaj20@gmail.com';                      // Same as username
    $to_email = 'esahraouimerieme@gmail.com';         // Where to send emails

    // Get and validate form data
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $message = trim($_POST['message'] ?? '');

    // Validation
    $errors = [];
    
    if (empty($name)) {
        $errors[] = 'Name is required';
    }
    
    if (empty($email)) {
        $errors[] = 'Email is required';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address';
    }
    
    if (empty($message)) {
        $errors[] = 'Message is required';
    }

    if (empty($errors)) {
        // Clean inputs
        $name = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
        $email = htmlspecialchars($email, ENT_QUOTES, 'UTF-8');
        $phone = htmlspecialchars($phone, ENT_QUOTES, 'UTF-8');
        $message = htmlspecialchars($message, ENT_QUOTES, 'UTF-8');

        // Check if PHPMailer files exist
        if (!file_exists('phpmailer/PHPMailer.php')) {
            $form_message = 'PHPMailer is not installed. Please download PHPMailer files to the phpmailer/ folder.';
        } elseif (!file_exists('phpmailer/SMTP.php')) {
            $form_message = 'SMTP.php file is missing. Please download all PHPMailer files.';
        } elseif (!file_exists('phpmailer/Exception.php')) {
            $form_message = 'Exception.php file is missing. Please download all PHPMailer files.';
        } else {
            // Include PHPMailer files
            require_once 'phpmailer/PHPMailer.php';
            require_once 'phpmailer/SMTP.php';
            require_once 'phpmailer/Exception.php';
            
            try {
                // Create PHPMailer instance (without namespace)
                $mail = new PHPMailer\PHPMailer\PHPMailer(true);

                // Enable SMTP debugging (set to 0 in production)
                $mail->SMTPDebug = 0; // 0 = off, 1 = client messages, 2 = client and server messages
                $mail->Debugoutput = 'html';

                // SMTP Configuration
                $mail->isSMTP();
                $mail->Host = $smtp_host;
                $mail->SMTPAuth = true;
                $mail->Username = $smtp_username;
                $mail->Password = $smtp_password;
                $mail->SMTPSecure = 'tls'; // Enable TLS encryption
                $mail->Port = $smtp_port;
                
                // Additional SMTP options for better compatibility
                $mail->SMTPOptions = array(
                    'ssl' => array(
                        'verify_peer' => false,
                        'verify_peer_name' => false,
                        'allow_self_signed' => true
                    )
                );

                // Email Recipients
                $mail->setFrom($from_email, 'Elite Properties Contact Form');
                $mail->addAddress($to_email, 'Elite Properties Team');
                $mail->addReplyTo($email, $name);

                // Email Content
                $mail->isHTML(true);
                $mail->Subject = 'New Contact Form Submission from ' . $name;
                
                // Create beautiful HTML email body
                $emailBody = "
                <!DOCTYPE html>
                <html>
                <head>
                    <meta charset='UTF-8'>
                    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
                    <title>Contact Form Submission</title>
                    <style>
                        body { 
                            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
                            line-height: 1.6; 
                            color: #333; 
                            margin: 0; 
                            padding: 0; 
                            background-color: #f5f5f5;
                        }
                        .email-container { 
                            max-width: 600px; 
                            margin: 20px auto; 
                            background: #ffffff; 
                            border-radius: 12px; 
                            overflow: hidden; 
                            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
                        }
                        .header { 
                            background: linear-gradient(135deg, #4f46e5, #7c3aed); 
                            color: white; 
                            padding: 30px 20px; 
                            text-align: center; 
                        }
                        .header h1 { 
                            margin: 0; 
                            font-size: 28px; 
                            font-weight: 600;
                        }
                        .header p { 
                            margin: 8px 0 0 0; 
                            opacity: 0.9; 
                            font-size: 16px;
                        }
                        .content { 
                            padding: 30px; 
                        }
                        .field { 
                            margin-bottom: 25px; 
                            padding: 20px; 
                            background: #f8f9ff; 
                            border-radius: 8px; 
                            border-left: 4px solid #4f46e5; 
                        }
                        .field-label { 
                            font-weight: 600; 
                            color: #4f46e5; 
                            font-size: 14px; 
                            text-transform: uppercase; 
                            margin-bottom: 8px; 
                            display: block;
                            letter-spacing: 0.5px;
                        }
                        .field-value { 
                            color: #333; 
                            font-size: 16px; 
                            line-height: 1.6;
                            word-wrap: break-word;
                        }
                        .message-field {
                            background: #fff3cd;
                            border-left-color: #ffc107;
                        }
                        .footer { 
                            background: #2d3748; 
                            color: #e2e8f0; 
                            padding: 25px; 
                            text-align: center; 
                            font-size: 14px; 
                        }
                        .footer p { 
                            margin: 5px 0; 
                        }
                        .metadata {
                            background: #f7fafc;
                            border: 1px solid #e2e8f0;
                            border-radius: 6px;
                            padding: 15px;
                            margin-top: 20px;
                            font-size: 13px;
                            color: #4a5568;
                        }
                        .metadata strong {
                            color: #2d3748;
                        }
                    </style>
                </head>
                <body>
                    <div class='email-container'>
                        <div class='header'>
                            <h1> New Contact Inquiry</h1>
                            <p>Elite Properties Website</p>
                        </div>
                        <div class='content'>
                            <div class='field'>
                                <span class='field-label'> Contact Name</span>
                                <div class='field-value'>{$name}</div>
                            </div>
                            
                            <div class='field'>
                                <span class='field-label'> Email Address</span>
                                <div class='field-value'>{$email}</div>
                            </div>";
                
                if (!empty($phone)) {
                    $emailBody .= "
                            <div class='field'>
                                <span class='field-label'> Phone Number</span>
                                <div class='field-value'>{$phone}</div>
                            </div>";
                }
                
                $emailBody .= "
                            <div class='field message-field'>
                                <span class='field-label'> Message</span>
                                <div class='field-value'>" . nl2br($message) . "</div>
                            </div>
                            
                            <div class='metadata'>
                                <strong> Submission Details:</strong><br>
                                <strong>Date & Time:</strong> " . date('F j, Y \a\t g:i A T') . "<br>
                                <strong>IP Address:</strong> " . ($_SERVER['REMOTE_ADDR'] ?? 'Unknown') . "<br>
                                <strong>User Agent:</strong> " . ($_SERVER['HTTP_USER_AGENT'] ?? 'Unknown') . "<br>
                                <strong>Referrer:</strong> " . ($_SERVER['HTTP_REFERER'] ?? 'Direct')."
                            </div>
                        </div>
                        <div class='footer'>
                            <p><strong>This is an automated message from Elite Properties contact form.</strong></p>
                            <p>To respond to this inquiry, simply reply to this email.</p>
                            <p>Visit: <a href='#' style='color: #90cdf4;'>www.eliteproperties.com</a></p>
                        </div>f
                    </div>
                </body>
                </html>";
                
                $mail->Body = $emailBody;
                
                // Plain text version for email clients that don't support HTML
                $mail->AltBody = "
Elite Properties - New Contact Form Submission

Name: {$name}
Email: {$email}" . (!empty($phone) ? "\nPhone: {$phone}" : "") . "

Message:
{$message}

---
Submission Details:
Date: " . date('F j, Y \a\t g:i A T') . "
IP Address: " . ($_SERVER['REMOTE_ADDR'] ?? 'Unknown') . "

This email was sent from the Elite Properties contact form.
Reply directly to this email to respond to the inquiry.";

                // Send the email
                if ($mail->send()) {
                    $form_success = true;
                    $form_message = 'Thank you! Your message has been sent successfully via PHPMailer. We will get back to you soon.';
                    // Clear form data after successful submission
                    $name = $email = $phone = $message = '';
                } else {
                    $form_message = 'Message could not be sent. PHPMailer Error: ' . $mail->ErrorInfo;
                }

            } catch (Exception $e) {
                $form_message = 'Message could not be sent. PHPMailer Exception: ' . $e->getMessage();
                // Log error for debugging (uncomment for debugging)
                // error_log("PHPMailer Error: " . $e->getMessage());
            }
        }
    } else {
        $form_message = implode(' ', $errors);
    }
}

// Include your head section
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

        .gsap-loading {
            opacity: 0;
        }

        body {
            visibility: visible !important;
            opacity: 1 !important;
        }

        #header {
            z-index: 1000;
        }

        .custom-cursor {
            z-index: 9999;
        }
        
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
        
        .property-card:hover {
            transform: translateY(-10px) scale(1.02);
        }
        
        .magnetic-button {
            transition: transform 0.3s cubic-bezier(0.25, 0.46, 0.45, 0.94);
        }

        :root {
            --header-height: 64px;
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

    <!-- Dark Overlay -->
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

                <?php if (!empty($form_message)): ?>
                    <!-- Form Message -->
                    <div class="mb-8">
                        <?php if ($form_success): ?>
                            <div class="bg-green-50 border-l-4 border-green-400 p-6 rounded-lg shadow-md animate-pulse">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0">
                                        <i class="fas fa-check-circle text-green-400 text-2xl"></i>
                                    </div>
                                    <div class="ml-3">
                                        <h3 class="text-lg font-medium text-green-800">Email Sent Successfully! ✅</h3>
                                        <p class="mt-1 text-sm text-green-700"><?php echo htmlspecialchars($form_message); ?></p>
                                    </div>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="bg-red-50 border-l-4 border-red-400 p-6 rounded-lg shadow-md">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0">
                                        <i class="fas fa-exclamation-circle text-red-400 text-2xl"></i>
                                    </div>
                                    <div class="ml-3">
                                        <h3 class="text-lg font-medium text-red-800">PHPMailer Error ❌</h3>
                                        <p class="mt-1 text-sm text-red-700"><?php echo htmlspecialchars($form_message); ?></p>
                                        <?php if (strpos($form_message, 'PHPMailer is not installed') !== false): ?>
                                        <div class="mt-3 p-3 bg-yellow-100 border-l-4 border-yellow-400 rounded">
                                            <p class="text-sm text-yellow-800">
                                                <strong>📁 Missing Files:</strong> Please download these files to <code>phpmailer/</code> folder:<br>
                                                • <a href="https://raw.githubusercontent.com/PHPMailer/PHPMailer/master/src/PHPMailer.php" target="_blank" class="text-blue-600 underline">PHPMailer.php</a><br>
                                                • <a href="https://raw.githubusercontent.com/PHPMailer/PHPMailer/master/src/SMTP.php" target="_blank" class="text-blue-600 underline">SMTP.php</a><br>
                                                • <a href="https://raw.githubusercontent.com/PHPMailer/PHPMailer/master/src/Exception.php" target="_blank" class="text-blue-600 underline">Exception.php</a>
                                            </p>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-12">
                    <!-- Contact Form -->
                    <div class="slide-in-left">
                        <h3 class="text-3xl font-bold text-gray-800 mb-6 fade-in-up">Send Us a Message</h3>
                        <form class="space-y-6" method="POST" action="">
                            <div class="fade-in-up">
                                <label for="name" class="block text-sm font-medium text-gray-700 mb-2">
                                    Full Name <span class="text-red-500">*</span>
                                </label>
                                <input type="text" 
                                       id="name" 
                                       name="name" 
                                       value="<?php echo htmlspecialchars($name ?? ''); ?>"
                                       required 
                                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all duration-300 transform focus:scale-105" 
                                       placeholder="Your full name">
                            </div>
                            
                            <div class="fade-in-up">
                                <label for="email" class="block text-sm font-medium text-gray-700 mb-2">
                                    Email Address <span class="text-red-500">*</span>
                                </label>
                                <input type="email" 
                                       id="email" 
                                       name="email" 
                                       value="<?php echo htmlspecialchars($email ?? ''); ?>"
                                       required 
                                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all duration-300 transform focus:scale-105" 
                                       placeholder="your.email@example.com">
                            </div>
                            
                            <div class="fade-in-up">
                                <label for="phone" class="block text-sm font-medium text-gray-700 mb-2">
                                    Phone Number (Optional)
                                </label>
                                <input type="tel" 
                                       id="phone" 
                                       name="phone" 
                                       value="<?php echo htmlspecialchars($phone ?? ''); ?>"
                                       class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all duration-300 transform focus:scale-105" 
                                       placeholder="(555) 123-4567">
                            </div>
                            
                            <div class="fade-in-up">
                                <label for="message" class="block text-sm font-medium text-gray-700 mb-2">
                                    Message <span class="text-red-500">*</span>
                                </label>
                                <textarea id="message" 
                                          name="message" 
                                          rows="5" 
                                          required 
                                          class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all duration-300 transform focus:scale-105" 
                                          placeholder="Tell us about your property needs..."><?php echo htmlspecialchars($message ?? ''); ?></textarea>
                            </div>
                            
                            <div class="fade-in-up w-full">
                                <button type="submit" 
                                        name="submit_contact"
                                        class="block w-full text-center bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 text-white font-semibold py-4 rounded-lg transition-all duration-300 transform hover:scale-105 magnetic-button shadow-2xl focus:outline-none focus:ring-4 focus:ring-indigo-300">
                                    <span class="mr-2">Send via PHPMailer</span>
                                    <i class="fas fa-paper-plane"></i>
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Contact Information -->
                    <div class="space-y-8 slide-in-right">
                        <div class="fade-in-up">
                            <h3 class="text-3xl font-bold text-gray-800 mb-6">Contact Information</h3>
                            <div class="space-y-4">
                                <div class="flex items-center space-x-4 scale-in p-4 bg-gradient-to-r from-indigo-50 to-purple-50 rounded-lg hover:shadow-md transition-shadow duration-300">
                                    <div class="bg-gradient-to-r from-indigo-100 to-purple-100 p-3 rounded-full">
                                        <i class="fas fa-phone text-indigo-600"></i>
                                    </div>
                                    <div>
                                        <p class="font-semibold text-gray-800">Phone</p>
                                        <p class="text-gray-600">(555) 123-4567</p>
                                    </div>
                                </div>
                                
                                <div class="flex items-center space-x-4 scale-in p-4 bg-gradient-to-r from-indigo-50 to-purple-50 rounded-lg hover:shadow-md transition-shadow duration-300">
                                    <div class="bg-gradient-to-r from-indigo-100 to-purple-100 p-3 rounded-full">
                                        <i class="fas fa-envelope text-indigo-600"></i>
                                    </div>
                                    <div>
                                        <p class="font-semibold text-gray-800">Email</p>
                                        <p class="text-gray-600">younesaj20@gmail.com</p>
                                    </div>
                                </div>
                                
                                <div class="flex items-center space-x-4 scale-in p-4 bg-gradient-to-r from-indigo-50 to-purple-50 rounded-lg hover:shadow-md transition-shadow duration-300">
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
                            <div class="bg-gray-50 p-4 rounded-lg space-y-2 text-gray-600">
                                <div class="flex justify-between">
                                    <span>Monday - Friday:</span>
                                    <span class="font-medium text-gray-800">9:00 AM - 6:00 PM</span>
                                </div>
                                <div class="flex justify-between">
                                    <span>Saturday:</span>
                                    <span class="font-medium text-gray-800">10:00 AM - 4:00 PM</span>
                                </div>
                                <div class="flex justify-between">
                                    <span>Sunday:</span>
                                    <span class="font-medium text-gray-800">By Appointment</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Location Map -->
                <div class="mt-12 fade-in-up">
                    <h3 class="text-2xl font-bold text-gray-800 mb-6">Our Location</h3>
                    <div class="w-full h-80 bg-gray-200 rounded-lg overflow-hidden shadow-lg">
                        <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d53379.816710574276!2d-7.622173899919255!3d33.260246756806275!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0xda63ce8fa04c5cf%3A0xc1041e48089e20f!2sBerrechid!5e0!3m2!1sfr!2sma!4v1753782857720!5m2!1sfr!2sma" 
                                width="100%" 
                                height="100%" 
                                style="border:0;" 
                                allowfullscreen="" 
                                loading="lazy" 
                                referrerpolicy="no-referrer-when-downgrade">
                        </iframe>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
   <?php include('include/footer.php');?>
    
    <!-- JavaScript -->
    <script>
        // Ensure page is always visible
        document.documentElement.style.visibility = 'visible';
        document.body.style.visibility = 'visible';
        document.body.style.opacity = '1';

        // Initialize animations
        function initializePageImmediately() {
            makeAllContentVisible();
            
            if (typeof gsap !== 'undefined') {
                try {
                    gsap.registerPlugin(ScrollTrigger, TextPlugin);
                    initializeAnimations();
                } catch (error) {
                    console.warn('GSAP initialization failed:', error);
                }
            }
        }

        function makeAllContentVisible() {
            const elements = document.querySelectorAll('.property-card, .fade-in-up, .scale-in, .slide-in-left, .slide-in-right');
            elements.forEach(el => {
                el.style.opacity = '1';
                el.style.transform = 'none';
                el.style.visibility = 'visible';
            });
        }

        function initializeAnimations() {
            // Hero animations
            const contactHeroTl = gsap.timeline();
            contactHeroTl.from("#contactHeroTitle", {duration: 1.5, y: 100, opacity: 0, ease: "power3.out"});
            
            // Animated gradient text
            gsap.to("#touchText", {
                duration: 3,
                backgroundPosition: "200% center",
                ease: "none",
                repeat: -1
            });
            
            // Scroll animations
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
        }

        // Custom cursor
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

        // Magnetic button effects
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

        // Particles
        function createParticles() {
            try {
                const particlesContainer = document.getElementById('particles-contact');
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

        // Initialize everything
        document.addEventListener('DOMContentLoaded', function() {
            initializePageImmediately();
            createParticles();
        });

        // Auto-hide success message
        <?php if ($form_success): ?>
        setTimeout(function() {
            const successDiv = document.querySelector('.bg-green-50');
            if (successDiv) {
                successDiv.style.transition = 'opacity 0.5s ease-out';
                successDiv.style.opacity = '0';
                setTimeout(() => successDiv.remove(), 500);
            }
        }, 8000); // Hide after 8 seconds
        <?php endif; ?>

        // Form enhancement - show loading state during submission
        document.getElementById('contactForm').addEventListener('submit', function() {
            const submitBtn = document.querySelector('button[name="submit_contact"]');
            const originalText = submitBtn.innerHTML;
            
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Sending via PHPMailer...';
            
            // Re-enable after 10 seconds as fallback
            setTimeout(() => {
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
            }, 10000);
        });

        // Smooth scrolling
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

        // Final fallback
        setTimeout(function() {
            makeAllContentVisible();
            console.log('Fallback visibility applied');
        }, 2000);
    </script>
</body>
</html>