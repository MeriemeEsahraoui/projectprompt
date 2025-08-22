<?php
// Include database configuration
require_once 'include/config.php';

// Get database connection
$pdo = getDatabaseConnection();

$error_message = '';
$success_message = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    
    if (empty($email)) {
        $error_message = 'Please enter your email address.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = 'Please enter a valid email address.';
    } else {
        try {
            // Check if user exists
            $stmt = $pdo->prepare("SELECT id, email, lastname FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            
            if ($user) {
                // Generate reset token
                $reset_token = bin2hex(random_bytes(32));
                $expires_at = date('Y-m-d H:i:s', strtotime('+1 hour')); // Token expires in 1 hour
                
                // Store reset token in database
                $stmt = $pdo->prepare("INSERT INTO password_resets (email, token, expires_at) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE token = ?, expires_at = ?");
                $stmt->execute([$email, $reset_token, $expires_at, $reset_token, $expires_at]);
                
                // Create reset link
                $reset_link = "http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . "/reset-password.php?token=" . $reset_token;
                
                // Email Configuration
                $smtp_host = 'smtp.gmail.com';
                $smtp_port = 587;
                $smtp_username = 'younesaj20@gmail.com';
                $smtp_password = 'drzqstrzrzioagad';
                $from_email = 'younesaj20@gmail.com';

                // Check if PHPMailer files exist
                if (!file_exists('../phpmailer/PHPMailer.php')) {
                    $error_message = 'PHPMailer is not installed. Please download PHPMailer files to the phpmailer/ folder.';
                } elseif (!file_exists('../phpmailer/SMTP.php')) {
                    $error_message = 'SMTP.php file is missing. Please download all PHPMailer files.';
                } elseif (!file_exists('../phpmailer/Exception.php')) {
                    $error_message = 'Exception.php file is missing. Please download all PHPMailer files.';
                } else {
                    // Include PHPMailer files
                    require_once '../phpmailer/PHPMailer.php';
                    require_once '../phpmailer/SMTP.php';
                    require_once '../phpmailer/Exception.php';
                    
                    try {
                        // Create PHPMailer instance
                        $mail = new PHPMailer\PHPMailer\PHPMailer(true);

                        // SMTP Configuration
                        $mail->isSMTP();
                        $mail->Host = $smtp_host;
                        $mail->SMTPAuth = true;
                        $mail->Username = $smtp_username;
                        $mail->Password = $smtp_password;
                        $mail->SMTPSecure = 'tls';
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
                        $mail->setFrom($from_email, 'Property Manager - Password Reset');
                        $mail->addAddress($email, $user['lastname']);

                        // Email Content
                        $mail->isHTML(true);
                        $mail->Subject = 'Password Reset Request - Property Manager';
                        
                        // Create beautiful HTML email body
                        $emailBody = "
                        <!DOCTYPE html>
                        <html>
                        <head>
                            <meta charset='UTF-8'>
                            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
                            <title>Password Reset Request</title>
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
                                    background: linear-gradient(135deg, #3b82f6, #1d4ed8); 
                                    color: white; 
                                    padding: 30px 20px; 
                                    text-align: center; 
                                }
                                .header h1 { 
                                    margin: 0; 
                                    font-size: 28px; 
                                    font-weight: 600;
                                }
                                .header .icon {
                                    font-size: 48px;
                                    margin-bottom: 15px;
                                }
                                .content { 
                                    padding: 40px 30px; 
                                    text-align: center;
                                }
                                .greeting {
                                    font-size: 18px;
                                    color: #374151;
                                    margin-bottom: 20px;
                                }
                                .message {
                                    font-size: 16px;
                                    color: #6b7280;
                                    margin-bottom: 30px;
                                    line-height: 1.6;
                                }
                                .reset-button { 
                                    display: inline-block; 
                                    background: linear-gradient(135deg, #3b82f6, #1d4ed8); 
                                    color: white; 
                                    padding: 15px 30px; 
                                    text-decoration: none; 
                                    border-radius: 8px; 
                                    font-weight: 600;
                                    font-size: 16px;
                                    margin: 20px 0;
                                    box-shadow: 0 4px 15px rgba(59, 130, 246, 0.3);
                                    transition: all 0.3s ease;
                                }
                                .reset-button:hover {
                                    transform: translateY(-2px);
                                    box-shadow: 0 6px 20px rgba(59, 130, 246, 0.4);
                                }
                                .link-box {
                                    background: #f3f4f6;
                                    border: 1px solid #e5e7eb;
                                    border-radius: 8px;
                                    padding: 15px;
                                    margin: 20px 0;
                                    word-break: break-all;
                                    font-size: 14px;
                                    color: #6b7280;
                                }
                                .warning {
                                    background: #fef3c7;
                                    border-left: 4px solid #f59e0b;
                                    padding: 15px;
                                    margin: 20px 0;
                                    border-radius: 0 8px 8px 0;
                                }
                                .warning-text {
                                    color: #92400e;
                                    font-weight: 600;
                                    margin: 0;
                                }
                                .footer { 
                                    background: #f9fafb; 
                                    border-top: 1px solid #e5e7eb;
                                    color: #6b7280; 
                                    padding: 25px; 
                                    text-align: center; 
                                    font-size: 14px; 
                                }
                                .footer p { 
                                    margin: 5px 0; 
                                }
                                .security-notice {
                                    background: #fef2f2;
                                    border: 1px solid #fecaca;
                                    border-radius: 8px;
                                    padding: 15px;
                                    margin: 20px 0;
                                    color: #991b1b;
                                    font-size: 14px;
                                }
                            </style>
                        </head>
                        <body>
                            <div class='email-container'>
                                <div class='header'>
                                    <div class='icon'>🔐</div>
                                    <h1>Password Reset Request</h1>
                                </div>
                                <div class='content'>
                                    <div class='greeting'>
                                        Hello " . htmlspecialchars($user['lastname']) . ",
                                    </div>
                                    
                                    <div class='message'>
                                        We received a request to reset your password for your Property Manager account. 
                                        If you requested this reset, click the button below to create a new password.
                                    </div>
                                    
                                    <a href='" . $reset_link . "' class='reset-button'>
                                        🔑 Reset My Password
                                    </a>
                                    
                                    <div class='message'>
                                        Or copy and paste this link into your browser:
                                    </div>
                                    
                                    <div class='link-box'>
                                        " . $reset_link . "
                                    </div>
                                    
                                    <div class='warning'>
                                        <p class='warning-text'>⚠️ This link will expire in 1 hour for security reasons.</p>
                                    </div>
                                    
                                    <div class='security-notice'>
                                        <strong>Security Notice:</strong> If you didn't request this password reset, 
                                        please ignore this email. Your account remains secure.
                                    </div>
                                </div>
                                <div class='footer'>
                                    <p><strong>Property Manager System</strong></p>
                                    <p>This is an automated security message.</p>
                                    <p>For support, contact us at younesaj20@gmail.com</p>
                                    <p style='margin-top: 15px; font-size: 12px; color: #9ca3af;'>
                                        Sent on " . date('F j, Y \a\t g:i A T') . "
                                    </p>
                                </div>
                            </div>
                        </body>
                        </html>";
                        
                        $mail->Body = $emailBody;
                        
                        // Plain text version for email clients that don't support HTML
                        $mail->AltBody = "
Property Manager - Password Reset Request

Hello " . $user['lastname'] . ",

We received a request to reset your password for your Property Manager account.

Reset Link: " . $reset_link . "

This link will expire in 1 hour for security reasons.

If you didn't request this password reset, please ignore this email.

---
Property Manager System
Support: younesaj20@gmail.com
Sent: " . date('F j, Y \a\t g:i A T') . "
                        ";

                        // Send the email
                        if ($mail->send()) {
                            $success_message = 'Password reset instructions have been sent to your email address via PHPMailer.';
                        } else {
                            $error_message = 'Failed to send reset email. PHPMailer Error: ' . $mail->ErrorInfo;
                        }

                    } catch (Exception $e) {
                        $error_message = 'Failed to send reset email. PHPMailer Exception: ' . $e->getMessage();
                        error_log("PHPMailer Error: " . $e->getMessage());
                    }
                }
            } else {
                // Don't reveal if email exists or not for security
                $success_message = 'If an account with that email exists, password reset instructions have been sent.';
            }
        } catch(PDOException $e) {
            $error_message = 'An error occurred. Please try again later.';
            error_log("Password reset error: " . $e->getMessage());
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Property Management - Forgot Password</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        primary: {
                            50: '#eff6ff',
                            500: '#3b82f6',
                            600: '#2563eb',
                            700: '#1d4ed8',
                        }
                    }
                }
            }
        }
    </script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Raleway:ital,wght@0,100..900;1,100..900&display=swap');
        body {
              font-family: "Raleway", sans-serif;
        }
    </style>
</head>
<body class="bg-gray-50 dark:bg-gray-900 min-h-screen flex items-center justify-center p-4">
    <!-- Dark Mode Toggle -->
    <button id="darkModeToggle" class="fixed top-4 right-4 p-2 rounded-lg bg-white dark:bg-gray-800 shadow-lg border border-gray-200 dark:border-gray-700 hover:shadow-xl transition-all duration-200">
        <svg class="w-5 h-5 text-gray-600 dark:text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"></path>
        </svg>
    </button>

    <div class="w-full max-w-md">
        <!-- Logo/Header -->
        <div class="text-center mb-8">
            <div class="mx-auto w-16 h-16 bg-primary-500 rounded-xl flex items-center justify-center mb-4">
                <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"></path>
                </svg>
            </div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Reset Password</h1>
            <p class="text-gray-600 dark:text-gray-400 mt-2">Enter your email to receive reset instructions</p>
        </div>

        <!-- Error/Success Messages -->
        <?php if (!empty($error_message)): ?>
            <div class="mb-4 p-4 bg-red-50 dark:bg-red-900/20 rounded-lg border border-red-200 dark:border-red-800">
                <div class="flex items-center">
                    <svg class="w-5 h-5 text-red-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <div>
                        <p class="text-sm text-red-800 dark:text-red-200 font-medium">Error</p>
                        <p class="text-sm text-red-700 dark:text-red-300"><?php echo htmlspecialchars($error_message); ?></p>
                        <?php if (strpos($error_message, 'PHPMailer is not installed') !== false): ?>
                        <div class="mt-3 p-3 bg-yellow-100 dark:bg-yellow-900/20 border-l-4 border-yellow-400 rounded">
                            <p class="text-sm text-yellow-800 dark:text-yellow-200">
                                <strong>📁 Missing PHPMailer Files:</strong> Please download these files to <code class="bg-yellow-200 dark:bg-yellow-800 px-1 rounded">phpmailer/</code> folder:<br>
                                • <a href="https://raw.githubusercontent.com/PHPMailer/PHPMailer/master/src/PHPMailer.php" target="_blank" class="text-blue-600 dark:text-blue-400 underline hover:text-blue-800">PHPMailer.php</a><br>
                                • <a href="https://raw.githubusercontent.com/PHPMailer/PHPMailer/master/src/SMTP.php" target="_blank" class="text-blue-600 dark:text-blue-400 underline hover:text-blue-800">SMTP.php</a><br>
                                • <a href="https://raw.githubusercontent.com/PHPMailer/PHPMailer/master/src/Exception.php" target="_blank" class="text-blue-600 dark:text-blue-400 underline hover:text-blue-800">Exception.php</a>
                            </p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <?php if (!empty($success_message)): ?>
            <div class="mb-4 p-4 bg-green-50 dark:bg-green-900/20 rounded-lg border border-green-200 dark:border-green-800">
                <div class="flex items-center">
                    <svg class="w-5 h-5 text-green-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <div>
                        <p class="text-sm text-green-800 dark:text-green-200 font-medium">Email Sent Successfully! ✅</p>
                        <p class="text-sm text-green-700 dark:text-green-300"><?php echo htmlspecialchars($success_message); ?></p>
                        <p class="text-xs text-green-600 dark:text-green-400 mt-1">
                            📧 Check your inbox and spam folder. The reset link expires in 1 hour.
                        </p>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Forgot Password Form -->
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-xl border border-gray-200 dark:border-gray-700 p-8">
            <?php if (empty($success_message)): ?>
            <form method="POST" class="space-y-6">
                <!-- Email Field -->
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Email Address
                    </label>
                    <input 
                        type="email" 
                        id="email" 
                        name="email" 
                        required
                        value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                        class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 transition-all duration-200"
                        placeholder="Enter your email address"
                    >
                </div>

                <!-- Submit Button -->
                <button 
                    type="submit" 
                    class="w-full bg-primary-600 hover:bg-primary-700 text-white font-medium py-3 px-4 rounded-lg transition-all duration-200 transform hover:scale-[1.02] focus:ring-4 focus:ring-primary-200 dark:focus:ring-primary-800 shadow-lg"
                >
                    Send Reset Instructions
                </button>
            </form>
            <?php else: ?>
                <div class="text-center">
                    <div class="mx-auto w-16 h-16 bg-green-100 dark:bg-green-900/20 rounded-full flex items-center justify-center mb-4">
                        <svg class="w-8 h-8 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                        </svg>
                    </div>
                    <p class="text-gray-600 dark:text-gray-400 mb-6">Check your email for reset instructions</p>
                </div>
            <?php endif; ?>

            <!-- Back to Login -->
            <div class="mt-6 text-center">
                <a href="login.php" class="inline-flex items-center text-sm text-primary-600 hover:text-primary-700 dark:text-primary-400 dark:hover:text-primary-300">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                    Back to Login
                </a>
            </div>
        </div>
    </div>

    <script>
        // Dark mode toggle
        const darkModeToggle = document.getElementById('darkModeToggle');
        const html = document.documentElement;

        // Check for saved theme preference or default to light mode
        const currentTheme = localStorage.getItem('theme') || 'light';
        if (currentTheme === 'dark') {
            html.classList.add('dark');
        }

        darkModeToggle.addEventListener('click', () => {
            html.classList.toggle('dark');
            const theme = html.classList.contains('dark') ? 'dark' : 'light';
            localStorage.setItem('theme', theme);
        });
    </script>
</body>
</html>