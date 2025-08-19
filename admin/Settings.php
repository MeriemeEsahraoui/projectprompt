<?php
// Include database configuration (this will start the session)
require_once 'include/config.php';
// Get database connection
$pdo = getDatabaseConnection();

// Check if user is logged in
if (!isset($_SESSION['is_logged_in']) || $_SESSION['is_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

// Get user information from session
$user_id = $_SESSION['user_id'] ?? 0;

$error_message = '';
$success_message = '';

// Get current user data
try {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        header('Location: login.php');
        exit;
    }
} catch (PDOException $e) {
    error_log("Get user error: " . $e->getMessage());
    $error_message = 'Failed to load user data.';
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'update_profile') {
        // Update profile information
        $firstname = trim($_POST['firstname'] ?? '');
        $lastname = trim($_POST['lastname'] ?? '');
        $email = trim($_POST['email'] ?? '');
        
        // Validation
        $errors = [];
        
        if (empty($firstname)) {
            $errors['firstname'] = 'First name is required';
        }
        
        if (empty($lastname)) {
            $errors['lastname'] = 'Last name is required';
        }
        
        if (empty($email)) {
            $errors['email'] = 'Email is required';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Valid email is required';
        } else {
            // Check if email is already taken by another user
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
            $stmt->execute([$email, $user_id]);
            if ($stmt->fetch()) {
                $errors['email'] = 'This email is already registered';
            }
        }
        
        if (empty($errors)) {
            try {
                $stmt = $pdo->prepare("UPDATE users SET firstname = ?, lastname = ?, email = ? WHERE id = ?");
                $stmt->execute([$firstname, $lastname, $email, $user_id]);
                
                // Update session data
                $_SESSION['user_firstname'] = $firstname;
                $_SESSION['user_lastname'] = $lastname;
                $_SESSION['user_email'] = $email;
                
                // Update local user data
                $user['firstname'] = $firstname;
                $user['lastname'] = $lastname;
                $user['email'] = $email;
                
                $success_message = 'Profile updated successfully!';
            } catch (PDOException $e) {
                error_log("Update profile error: " . $e->getMessage());
                $error_message = 'Failed to update profile. Please try again.';
            }
        }
    } elseif ($action === 'change_password') {
        // Change password
        $current_password = $_POST['current_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        
        // Validation
        $password_errors = [];
        
        if (empty($current_password)) {
            $password_errors['current_password'] = 'Current password is required';
        } elseif (!password_verify($current_password, $user['password'])) {
            $password_errors['current_password'] = 'Current password is incorrect';
        }
        
        if (empty($new_password)) {
            $password_errors['new_password'] = 'New password is required';
        } elseif (strlen($new_password) < 6) {
            $password_errors['new_password'] = 'Password must be at least 6 characters long';
        }
        
        if (empty($confirm_password)) {
            $password_errors['confirm_password'] = 'Please confirm your new password';
        } elseif ($new_password !== $confirm_password) {
            $password_errors['confirm_password'] = 'Passwords do not match';
        }
        
        if (empty($password_errors)) {
            try {
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                $stmt->execute([$hashed_password, $user_id]);
                
                $success_message = 'Password changed successfully!';
            } catch (PDOException $e) {
                error_log("Change password error: " . $e->getMessage());
                $error_message = 'Failed to change password. Please try again.';
            }
        }
    }
}

// Get user statistics
try {
    $stmt = $pdo->prepare("SELECT COUNT(*) as total_properties FROM property WHERE created_by = ?");
    $stmt->execute([$user_id]);
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $stmt = $pdo->prepare("SELECT COUNT(*) as available_properties FROM property WHERE created_by = ? AND status = 0");
    $stmt->execute([$user_id]);
    $available_stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $stmt = $pdo->prepare("SELECT COUNT(*) as rented_properties FROM property WHERE created_by = ? AND status = 1");
    $stmt->execute([$user_id]);
    $rented_stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Get inquiries count
    $stmt = $pdo->prepare("SELECT COUNT(*) as total_inquiries FROM inquire i INNER JOIN property p ON i.property_id = p.Id WHERE p.created_by = ?");
    $stmt->execute([$user_id]);
    $inquiry_stats = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Get stats error: " . $e->getMessage());
    $stats = ['total_properties' => 0];
    $available_stats = ['available_properties' => 0];
    $rented_stats = ['rented_properties' => 0];
    $inquiry_stats = ['total_inquiries' => 0];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - Property Management</title>
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
</head>
<body class="bg-gray-50 dark:bg-gray-900 min-h-screen">
    <!-- Navigation -->
    <?php include('include/menu.php'); ?>

    <!-- Main Content -->
    <main class="max-w-6xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-2">Account Settings</h1>
            <p class="text-gray-600 dark:text-gray-400">Manage your account preferences and profile information.</p>
        </div>

        <!-- Error/Success Messages -->
        <?php if (!empty($error_message)): ?>
            <div class="mb-6 p-4 bg-red-50 dark:bg-red-900/20 rounded-lg border border-red-200 dark:border-red-800">
                <div class="flex items-center">
                    <svg class="w-5 h-5 text-red-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <p class="text-sm text-red-800 dark:text-red-200"><?php echo htmlspecialchars($error_message); ?></p>
                </div>
            </div>
        <?php endif; ?>

        <?php if (!empty($success_message)): ?>
            <div class="mb-6 p-4 bg-green-50 dark:bg-green-900/20 rounded-lg border border-green-200 dark:border-green-800">
                <div class="flex items-center">
                    <svg class="w-5 h-5 text-green-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <p class="text-sm text-green-800 dark:text-green-200"><?php echo htmlspecialchars($success_message); ?></p>
                </div>
            </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Left Sidebar - Account Overview -->
            <div class="lg:col-span-1">
                <!-- Profile Card -->
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 p-6 mb-6">
                    <div class="text-center">
                        <div class="w-20 h-20 bg-primary-500 rounded-full flex items-center justify-center mx-auto mb-4">
                            <span class="text-2xl font-bold text-white">
                                <?php echo strtoupper(substr($user['firstname'], 0, 1) . substr($user['lastname'], 0, 1)); ?>
                            </span>
                        </div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                            <?php echo htmlspecialchars($user['firstname'] . ' ' . $user['lastname']); ?>
                        </h3>
                        <p class="text-sm text-gray-600 dark:text-gray-400"><?php echo htmlspecialchars($user['email']); ?></p>
                    </div>
                </div>

                <!-- Statistics Card -->
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 p-6">
                    <h4 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Your Portfolio</h4>
                    <div class="space-y-4">
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-gray-600 dark:text-gray-400">Total Properties</span>
                            <span class="text-lg font-semibold text-gray-900 dark:text-white"><?php echo $stats['total_properties']; ?></span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-gray-600 dark:text-gray-400">Available</span>
                            <span class="text-lg font-semibold text-green-600"><?php echo $available_stats['available_properties']; ?></span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-gray-600 dark:text-gray-400">Rented</span>
                            <span class="text-lg font-semibold text-blue-600"><?php echo $rented_stats['rented_properties']; ?></span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-gray-600 dark:text-gray-400">Total Inquiries</span>
                            <span class="text-lg font-semibold text-purple-600"><?php echo $inquiry_stats['total_inquiries']; ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Content - Settings Forms -->
            <div class="lg:col-span-2 space-y-8">
                <!-- Profile Information -->
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700">
                    <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Profile Information</h3>
                        <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">Update your account's profile information and email address.</p>
                    </div>
                    <form method="POST" class="p-6 space-y-6">
                        <input type="hidden" name="action" value="update_profile">
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- First Name -->
                            <div>
                                <label for="firstname" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    First Name <span class="text-red-500">*</span>
                                </label>
                                <input 
                                    type="text" 
                                    id="firstname" 
                                    name="firstname" 
                                    required
                                    value="<?php echo htmlspecialchars($user['firstname']); ?>"
                                    class="w-full px-4 py-3 border <?php echo isset($errors['firstname']) ? 'border-red-500' : 'border-gray-300 dark:border-gray-600'; ?> rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
                                    placeholder="Enter your first name"
                                >
                                <?php if (isset($errors['firstname'])): ?>
                                    <div class="text-red-500 text-sm mt-1"><?php echo htmlspecialchars($errors['firstname']); ?></div>
                                <?php endif; ?>
                            </div>

                            <!-- Last Name -->
                            <div>
                                <label for="lastname" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Last Name <span class="text-red-500">*</span>
                                </label>
                                <input 
                                    type="text" 
                                    id="lastname" 
                                    name="lastname" 
                                    required
                                    value="<?php echo htmlspecialchars($user['lastname']); ?>"
                                    class="w-full px-4 py-3 border <?php echo isset($errors['lastname']) ? 'border-red-500' : 'border-gray-300 dark:border-gray-600'; ?> rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
                                    placeholder="Enter your last name"
                                >
                                <?php if (isset($errors['lastname'])): ?>
                                    <div class="text-red-500 text-sm mt-1"><?php echo htmlspecialchars($errors['lastname']); ?></div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Email -->
                        <div>
                            <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Email Address <span class="text-red-500">*</span>
                            </label>
                            <input 
                                type="email" 
                                id="email" 
                                name="email" 
                                required
                                value="<?php echo htmlspecialchars($user['email']); ?>"
                                class="w-full px-4 py-3 border <?php echo isset($errors['email']) ? 'border-red-500' : 'border-gray-300 dark:border-gray-600'; ?> rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
                                placeholder="Enter your email address"
                            >
                            <?php if (isset($errors['email'])): ?>
                                <div class="text-red-500 text-sm mt-1"><?php echo htmlspecialchars($errors['email']); ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="flex justify-end">
                            <button 
                                type="submit" 
                                class="px-6 py-3 bg-primary-600 hover:bg-primary-700 text-white font-medium rounded-lg transition-all duration-200 transform hover:scale-[1.02] focus:ring-4 focus:ring-primary-200 dark:focus:ring-primary-800"
                            >
                                Update Profile
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Change Password -->
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700">
                    <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Change Password</h3>
                        <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">Ensure your account is using a long, random password to stay secure.</p>
                    </div>
                    <form method="POST" class="p-6 space-y-6">
                        <input type="hidden" name="action" value="change_password">
                        
                        <!-- Current Password -->
                        <div>
                            <label for="current_password" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Current Password <span class="text-red-500">*</span>
                            </label>
                            <input 
                                type="password" 
                                id="current_password" 
                                name="current_password" 
                                required
                                class="w-full px-4 py-3 border <?php echo isset($password_errors['current_password']) ? 'border-red-500' : 'border-gray-300 dark:border-gray-600'; ?> rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
                                placeholder="Enter your current password"
                            >
                            <?php if (isset($password_errors['current_password'])): ?>
                                <div class="text-red-500 text-sm mt-1"><?php echo htmlspecialchars($password_errors['current_password']); ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- New Password -->
                            <div>
                                <label for="new_password" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    New Password <span class="text-red-500">*</span>
                                </label>
                                <input 
                                    type="password" 
                                    id="new_password" 
                                    name="new_password" 
                                    required
                                    minlength="6"
                                    class="w-full px-4 py-3 border <?php echo isset($password_errors['new_password']) ? 'border-red-500' : 'border-gray-300 dark:border-gray-600'; ?> rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
                                    placeholder="Enter new password"
                                >
                                <?php if (isset($password_errors['new_password'])): ?>
                                    <div class="text-red-500 text-sm mt-1"><?php echo htmlspecialchars($password_errors['new_password']); ?></div>
                                <?php endif; ?>
                            </div>

                            <!-- Confirm Password -->
                            <div>
                                <label for="confirm_password" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Confirm Password <span class="text-red-500">*</span>
                                </label>
                                <input 
                                    type="password" 
                                    id="confirm_password" 
                                    name="confirm_password" 
                                    required
                                    minlength="6"
                                    class="w-full px-4 py-3 border <?php echo isset($password_errors['confirm_password']) ? 'border-red-500' : 'border-gray-300 dark:border-gray-600'; ?> rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
                                    placeholder="Confirm new password"
                                >
                                <?php if (isset($password_errors['confirm_password'])): ?>
                                    <div class="text-red-500 text-sm mt-1"><?php echo htmlspecialchars($password_errors['confirm_password']); ?></div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4">
                            <div class="flex items-start">
                                <svg class="w-5 h-5 text-blue-500 mr-2 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                <div class="text-sm text-blue-800 dark:text-blue-200">
                                    <p class="font-medium mb-1">Password Requirements:</p>
                                    <ul class="list-disc list-inside space-y-1">
                                        <li>At least 6 characters long</li>
                                        <li>Include a mix of letters, numbers, and symbols</li>
                                        <li>Avoid common passwords</li>
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <div class="flex justify-end">
                            <button 
                                type="submit" 
                                class="px-6 py-3 bg-red-600 hover:bg-red-700 text-white font-medium rounded-lg transition-all duration-200 transform hover:scale-[1.02] focus:ring-4 focus:ring-red-200 dark:focus:ring-red-800"
                            >
                                Change Password
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Data Export -->
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700">
                    <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Data Export</h3>
                        <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">Download your property data and inquiries in various formats.</p>
                    </div>
                    <div class="p-6 space-y-4">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <!-- Properties Export -->
                            <div class="bg-gradient-to-br from-blue-50 to-blue-100 dark:from-blue-900/20 dark:to-blue-800/20 rounded-lg p-4 border border-blue-200 dark:border-blue-700">
                                <div class="flex items-center mb-3">
                                    <svg class="w-6 h-6 text-blue-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                                    </svg>
                                    <h4 class="font-semibold text-gray-900 dark:text-white">Properties</h4>
                                </div>
                                <p class="text-sm text-gray-600 dark:text-gray-400 mb-4"><?php echo $stats['total_properties']; ?> properties in your portfolio</p>
                                <div class="flex space-x-2">
                                    <button onclick="exportData('properties', 'csv')" class="flex-1 px-3 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition-colors">
                                        CSV
                                    </button>
                                    <button onclick="exportData('properties', 'json')" class="flex-1 px-3 py-2 bg-blue-500 hover:bg-blue-600 text-white text-sm font-medium rounded-lg transition-colors">
                                        JSON
                                    </button>
                                </div>
                            </div>

                            <!-- Inquiries Export -->
                            <div class="bg-gradient-to-br from-purple-50 to-purple-100 dark:from-purple-900/20 dark:to-purple-800/20 rounded-lg p-4 border border-purple-200 dark:border-purple-700">
                                <div class="flex items-center mb-3">
                                    <svg class="w-6 h-6 text-purple-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
                                    </svg>
                                    <h4 class="font-semibold text-gray-900 dark:text-white">Inquiries</h4>
                                </div>
                                <p class="text-sm text-gray-600 dark:text-gray-400 mb-4"><?php echo $inquiry_stats['total_inquiries']; ?> inquiries received</p>
                                <div class="flex space-x-2">
                                    <button onclick="exportData('inquiries', 'csv')" class="flex-1 px-3 py-2 bg-purple-600 hover:bg-purple-700 text-white text-sm font-medium rounded-lg transition-colors">
                                        CSV
                                    </button>
                                    <button onclick="exportData('inquiries', 'json')" class="flex-1 px-3 py-2 bg-purple-500 hover:bg-purple-600 text-white text-sm font-medium rounded-lg transition-colors">
                                        JSON
                                    </button>
                                </div>
                            </div>

                            <!-- Complete Export -->
                            <div class="bg-gradient-to-br from-green-50 to-green-100 dark:from-green-900/20 dark:to-green-800/20 rounded-lg p-4 border border-green-200 dark:border-green-700">
                                <div class="flex items-center mb-3">
                                    <svg class="w-6 h-6 text-green-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                    </svg>
                                    <h4 class="font-semibold text-gray-900 dark:text-white">Complete Data</h4>
                                </div>
                                <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">All properties and inquiries</p>
                                <div class="flex space-x-2">
                                    <button onclick="exportData('all', 'csv')" class="flex-1 px-3 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-medium rounded-lg transition-colors">
                                        CSV
                                    </button>
                                    <button onclick="exportData('all', 'json')" class="flex-1 px-3 py-2 bg-green-500 hover:bg-green-600 text-white text-sm font-medium rounded-lg transition-colors">
                                        JSON
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Export Information -->
                        <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg p-4 mt-6">
                            <div class="flex items-start">
                                <svg class="w-5 h-5 text-gray-500 mr-2 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                <div class="text-sm text-gray-600 dark:text-gray-400">
                                    <p class="font-medium mb-1">Export Information:</p>
                                    <ul class="list-disc list-inside space-y-1">
                                        <li><strong>CSV:</strong> Perfect for Excel or spreadsheet applications</li>
                                        <li><strong>JSON:</strong> Structured data format for developers or backup purposes</li>
                                        <li><strong>Properties:</strong> Includes all property details, status, and creation dates</li>
                                        <li><strong>Inquiries:</strong> Contains customer inquiries with property references</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Account Actions -->
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700">
                    <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Account Actions</h3>
                        <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">Quick actions for your account management.</p>
                    </div>
                    <div class="p-6 space-y-4">
                        <div class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                            <div>
                                <h4 class="text-sm font-medium text-gray-900 dark:text-white">Session Management</h4>
                                <p class="text-sm text-gray-600 dark:text-gray-400">Log out from all devices</p>
                            </div>
                            <a 
                                href="logout.php" 
                                class="px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white text-sm font-medium rounded-lg transition-colors"
                            >
                                Logout
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Export Loading Modal -->
    <div id="exportModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
        <div class="bg-white dark:bg-gray-800 rounded-xl p-6 max-w-sm w-full mx-4">
            <div class="text-center">
                <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-primary-600 mx-auto mb-4"></div>
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">Preparing Export</h3>
                <p class="text-sm text-gray-600 dark:text-gray-400">Your data is being prepared for download...</p>
            </div>
        </div>
    </div>

    <script>
        // Password confirmation validation
        const newPassword = document.getElementById('new_password');
        const confirmPassword = document.getElementById('confirm_password');

        function validatePassword() {
            if (newPassword.value !== confirmPassword.value) {
                confirmPassword.setCustomValidity('Passwords do not match');
            } else {
                confirmPassword.setCustomValidity('');
            }
        }

        newPassword.addEventListener('input', validatePassword);
        confirmPassword.addEventListener('input', validatePassword);

        // Export data function
        function exportData(type, format) {
            // Show loading modal
            document.getElementById('exportModal').classList.remove('hidden');
            
            // Create a temporary link to trigger download
            const link = document.createElement('a');
            link.href = `export.php?type=${type}&format=${format}`;
            link.style.display = 'none';
            document.body.appendChild(link);
            
            // Trigger download
            link.click();
            
            // Clean up
            document.body.removeChild(link);
            
            // Hide loading modal after a delay
            setTimeout(() => {
                document.getElementById('exportModal').classList.add('hidden');
                
                // Show success message
                showNotification(`${type.charAt(0).toUpperCase() + type.slice(1)} data exported successfully as ${format.toUpperCase()}!`, 'success');
            }, 2000);
        }

        // Show notification function
        function showNotification(message, type = 'info') {
            const notification = document.createElement('div');
            const bgColor = type === 'success' ? 'bg-green-50 dark:bg-green-900/20 border-green-200 dark:border-green-800' : 
                           type === 'error' ? 'bg-red-50 dark:bg-red-900/20 border-red-200 dark:border-red-800' : 
                           'bg-blue-50 dark:bg-blue-900/20 border-blue-200 dark:border-blue-800';
            const textColor = type === 'success' ? 'text-green-800 dark:text-green-200' : 
                            type === 'error' ? 'text-red-800 dark:text-red-200' : 
                            'text-blue-800 dark:text-blue-200';
            const iconColor = type === 'success' ? 'text-green-500' : 
                            type === 'error' ? 'text-red-500' : 
                            'text-blue-500';
            
            notification.className = `fixed top-4 right-4 p-4 ${bgColor} rounded-lg border z-50 max-w-sm shadow-lg`;
            notification.innerHTML = `
                <div class="flex items-center">
                    <svg class="w-5 h-5 ${iconColor} mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        ${type === 'success' ? 
                            '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>' :
                        type === 'error' ? 
                            '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>' :
                            '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>'
                        }
                    </svg>
                    <p class="text-sm ${textColor}">${message}</p>
                    <button onclick="this.parentElement.parentElement.remove()" class="ml-auto ${textColor} hover:opacity-70">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
            `;
            
            document.body.appendChild(notification);
            
            // Auto remove after 5 seconds
            setTimeout(() => {
                if (notification.parentElement) {
                    notification.remove();
                }
            }, 5000);
        }

        // Auto-hide success/error messages after 5 seconds
        setTimeout(() => {
            const messages = document.querySelectorAll('.bg-green-50, .bg-red-50');
            messages.forEach(message => {
                message.style.transition = 'opacity 0.5s ease-out';
                message.style.opacity = '0';
                setTimeout(() => message.remove(), 500);
            });
        }, 5000);

        // Close modal when clicking outside
        document.getElementById('exportModal').addEventListener('click', function(e) {
            if (e.target === this) {
                this.classList.add('hidden');
            }
        });
    </script>
</body>
</html>