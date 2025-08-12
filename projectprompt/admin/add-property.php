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

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['propertyName'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $location = trim($_POST['location'] ?? '');
    $price = floatval($_POST['price'] ?? 0);
    $type = $_POST['propertyType'] ?? '';
    $bedrooms = intval($_POST['bedrooms'] ?? 0);
    $bathrooms = floatval($_POST['bathrooms'] ?? 0);
    $status = $_POST['status'] ?? '';
    $map_link = trim($_POST['mapLink'] ?? '');
    
    // Validation
    $errors = [];
    
    if (empty($name)) {
        $errors['propertyName'] = 'Property name is required';
    }
    
    if (empty($description)) {
        $errors['description'] = 'Description is required';
    }
    
    if (empty($location)) {
        $errors['location'] = 'Location is required';
    }
    
    if ($price <= 0) {
        $errors['price'] = 'Valid price is required';
    }
    
    if (empty($type)) {
        $errors['propertyType'] = 'Property type is required';
    }
    
    if ($bedrooms < 0) {
        $errors['bedrooms'] = 'Number of bedrooms is required';
    }
    
    if ($bathrooms <= 0) {
        $errors['bathrooms'] = 'Number of bathrooms is required';
    }
    
    if (empty($status)) {
        $errors['status'] = 'Property status is required';
    }
    
    // Validate Google Maps link (optional but if provided, should be valid)
    if (!empty($map_link)) {
        // Check if it's a valid Google Maps URL
        if (!preg_match('/^https:\/\/(www\.)?(google\.(com|[a-z]{2,3}(\.[a-z]{2})?)|maps\.google\.(com|[a-z]{2,3}(\.[a-z]{2})?))\/(maps|url)/', $map_link)) {
            $errors['mapLink'] = 'Please provide a valid Google Maps link';
        }
    }
    
    // Handle image upload
    $image_path = '';
    if (isset($_FILES['propertyImage']) && $_FILES['propertyImage']['error'] === UPLOAD_ERR_OK) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        $max_size = 10 * 1024 * 1024; // 10MB
        
        $file = $_FILES['propertyImage'];
        
        if (!in_array($file['type'], $allowed_types)) {
            $errors['propertyImage'] = 'Only JPG, PNG, and GIF images are allowed';
        } elseif ($file['size'] > $max_size) {
            $errors['propertyImage'] = 'Image size must be less than 10MB';
        } else {
            // Create uploads directory if it doesn't exist
            $upload_dir = 'uploads/properties/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            // Generate unique filename
            $file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $filename = uniqid('property_' . $user_id . '_') . '.' . $file_extension;
            $image_path = $upload_dir . $filename;
            
            if (!move_uploaded_file($file['tmp_name'], $image_path)) {
                $errors['propertyImage'] = 'Failed to upload image';
            }
        }
    }
    
    // If no validation errors, save to database
    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO property 
                (name, description, location, price, type, number_of_bedrooms, number_of_bathrooms, property_image, status, map, created_by) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $name,
                $description, 
                $location,
                $price,
                $type,
                $bedrooms,
                $bathrooms,
                $image_path,
                $status,
                $map_link,
                $user_id
            ]);
            
            $success_message = 'Property added successfully!';
            
            // Redirect after success to prevent resubmission
            $_SESSION['success_message'] = $success_message;
            header('Location: view-properties.php');
            exit;
            
        } catch (PDOException $e) {
            error_log("Add property error: " . $e->getMessage());
            $error_message = 'Failed to add property. Please try again.';
        }
    }
}

// Check for success message from redirect
if (isset($_SESSION['success_message'])) {
    $success_message = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Property - Property Management</title>
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
    <main class="max-w-4xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-2">Add New Property</h1>
            <p class="text-gray-600 dark:text-gray-400">Fill in the details below to add a new property to your portfolio.</p>
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

        <!-- Form -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700">
            <form method="POST" enctype="multipart/form-data" class="p-6 space-y-6">
                <!-- Property Name -->
                <div>
                    <label for="propertyName" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Property Name <span class="text-red-500">*</span>
                    </label>
                    <input 
                        type="text" 
                        id="propertyName" 
                        name="propertyName" 
                        required
                        value="<?php echo htmlspecialchars($_POST['propertyName'] ?? ''); ?>"
                        class="w-full px-4 py-3 border <?php echo isset($errors['propertyName']) ? 'border-red-500 dark:border-red-400' : 'border-gray-300 dark:border-gray-600'; ?> rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 transition-all duration-200"
                        placeholder="e.g., Sunset Villa, Downtown Apartment"
                    >
                    <?php if (isset($errors['propertyName'])): ?>
                        <div class="text-red-500 text-sm mt-1"><?php echo htmlspecialchars($errors['propertyName']); ?></div>
                    <?php endif; ?>
                </div>

                <!-- Description -->
                <div>
                    <label for="description" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Description <span class="text-red-500">*</span>
                    </label>
                    <textarea 
                        id="description" 
                        name="description" 
                        rows="4" 
                        required
                        class="w-full px-4 py-3 border <?php echo isset($errors['description']) ? 'border-red-500 dark:border-red-400' : 'border-gray-300 dark:border-gray-600'; ?> rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 transition-all duration-200 resize-none"
                        placeholder="Describe the property features, amenities, and highlights..."
                    ><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
                    <?php if (isset($errors['description'])): ?>
                        <div class="text-red-500 text-sm mt-1"><?php echo htmlspecialchars($errors['description']); ?></div>
                    <?php endif; ?>
                </div>

                <!-- Location -->
                <div>
                    <label for="location" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Location <span class="text-red-500">*</span>
                    </label>
                    <input 
                        type="text" 
                        id="location" 
                        name="location" 
                        required
                        value="<?php echo htmlspecialchars($_POST['location'] ?? ''); ?>"
                        class="w-full px-4 py-3 border <?php echo isset($errors['location']) ? 'border-red-500 dark:border-red-400' : 'border-gray-300 dark:border-gray-600'; ?> rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 transition-all duration-200"
                        placeholder="e.g., 123 Main St, New York, NY 10001"
                    >
                    <?php if (isset($errors['location'])): ?>
                        <div class="text-red-500 text-sm mt-1"><?php echo htmlspecialchars($errors['location']); ?></div>
                    <?php endif; ?>
                </div>

                <!-- Google Maps Link -->
                <div>
                    <label for="mapLink" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Google Maps Link (Optional)
                    </label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="w-5 h-5 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            </svg>
                        </div>
                        <input 
                            type="url" 
                            id="mapLink" 
                            name="mapLink" 
                            value="<?php echo htmlspecialchars($_POST['mapLink'] ?? ''); ?>"
                            class="w-full pl-12 pr-4 py-3 border <?php echo isset($errors['mapLink']) ? 'border-red-500 dark:border-red-400' : 'border-gray-300 dark:border-gray-600'; ?> rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 transition-all duration-200"
                            placeholder="https://maps.google.com/..."
                        >
                    </div>
                    <?php if (isset($errors['mapLink'])): ?>
                        <div class="text-red-500 text-sm mt-1"><?php echo htmlspecialchars($errors['mapLink']); ?></div>
                    <?php endif; ?>
                    <div class="mt-2 text-sm text-gray-500 dark:text-gray-400">
                        <p>To get a Google Maps link:</p>
                        <ol class="list-decimal list-inside mt-1 space-y-1">
                            <li>Go to <a href="https://maps.google.com" target="_blank" class="text-primary-600 dark:text-primary-400 hover:underline">Google Maps</a></li>
                            <li>Search for the property address</li>
                            <li>Click the "Share" button</li>
                            <li>Copy the link and paste it here</li>
                        </ol>
                    </div>
                </div>

                <!-- Price and Property Type Row -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Price -->
                    <div>
                        <label for="price" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Price <span class="text-red-500">*</span>
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <span class="text-gray-500 dark:text-gray-400 sm:text-sm">$</span>
                            </div>
                            <input 
                                type="number" 
                                id="price" 
                                name="price" 
                                min="0" 
                                step="0.01" 
                                required
                                value="<?php echo htmlspecialchars($_POST['price'] ?? ''); ?>"
                                class="w-full pl-8 pr-4 py-3 border <?php echo isset($errors['price']) ? 'border-red-500 dark:border-red-400' : 'border-gray-300 dark:border-gray-600'; ?> rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 transition-all duration-200"
                                placeholder="0.00"
                            >
                        </div>
                        <?php if (isset($errors['price'])): ?>
                            <div class="text-red-500 text-sm mt-1"><?php echo htmlspecialchars($errors['price']); ?></div>
                        <?php endif; ?>
                    </div>

                    <!-- Property Type -->
                    <div>
                        <label for="propertyType" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Property Type <span class="text-red-500">*</span>
                        </label>
                        <select 
                            id="propertyType" 
                            name="propertyType" 
                            required
                            class="w-full px-4 py-3 border <?php echo isset($errors['propertyType']) ? 'border-red-500 dark:border-red-400' : 'border-gray-300 dark:border-gray-600'; ?> rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent bg-white dark:bg-gray-700 text-gray-900 dark:text-white transition-all duration-200"
                        >
                            <option value="">Select property type</option>
                            <option value="apartment" <?php echo ($_POST['propertyType'] ?? '') === 'apartment' ? 'selected' : ''; ?>>Apartment</option>
                            <option value="house" <?php echo ($_POST['propertyType'] ?? '') === 'house' ? 'selected' : ''; ?>>House</option>
                            <option value="studio" <?php echo ($_POST['propertyType'] ?? '') === 'studio' ? 'selected' : ''; ?>>Studio</option>
                            <option value="condo" <?php echo ($_POST['propertyType'] ?? '') === 'condo' ? 'selected' : ''; ?>>Condo</option>
                            <option value="townhouse" <?php echo ($_POST['propertyType'] ?? '') === 'townhouse' ? 'selected' : ''; ?>>Townhouse</option>
                            <option value="villa" <?php echo ($_POST['propertyType'] ?? '') === 'villa' ? 'selected' : ''; ?>>Villa</option>
                            <option value="commercial" <?php echo ($_POST['propertyType'] ?? '') === 'commercial' ? 'selected' : ''; ?>>Commercial</option>
                        </select>
                        <?php if (isset($errors['propertyType'])): ?>
                            <div class="text-red-500 text-sm mt-1"><?php echo htmlspecialchars($errors['propertyType']); ?></div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Status and Room Details Row -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <!-- Status -->
                    <div>
                        <label for="status" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Property Status <span class="text-red-500">*</span>
                        </label>
                        <select 
                            id="status" 
                            name="status" 
                            required
                            class="w-full px-4 py-3 border <?php echo isset($errors['status']) ? 'border-red-500 dark:border-red-400' : 'border-gray-300 dark:border-gray-600'; ?> rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent bg-white dark:bg-gray-700 text-gray-900 dark:text-white transition-all duration-200"
                        >
                            <option value="">Select status</option>
                            <option value="available" <?php echo ($_POST['status'] ?? '') === 'available' ? 'selected' : ''; ?>>Available</option>
                            <option value="rented" <?php echo ($_POST['status'] ?? '') === 'rented' ? 'selected' : ''; ?>>Rented</option>
                            <option value="maintenance" <?php echo ($_POST['status'] ?? '') === 'maintenance' ? 'selected' : ''; ?>>Under Maintenance</option>
                            <option value="sold" <?php echo ($_POST['status'] ?? '') === 'sold' ? 'selected' : ''; ?>>Sold</option>
                        </select>
                        <?php if (isset($errors['status'])): ?>
                            <div class="text-red-500 text-sm mt-1"><?php echo htmlspecialchars($errors['status']); ?></div>
                        <?php endif; ?>
                    </div>

                    <!-- Bedrooms -->
                    <div>
                        <label for="bedrooms" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Number of Bedrooms <span class="text-red-500">*</span>
                        </label>
                        <select 
                            id="bedrooms" 
                            name="bedrooms" 
                            required
                            class="w-full px-4 py-3 border <?php echo isset($errors['bedrooms']) ? 'border-red-500 dark:border-red-400' : 'border-gray-300 dark:border-gray-600'; ?> rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent bg-white dark:bg-gray-700 text-gray-900 dark:text-white transition-all duration-200"
                        >
                            <option value="">Select bedrooms</option>
                            <option value="0" <?php echo ($_POST['bedrooms'] ?? '') === '0' ? 'selected' : ''; ?>>Studio (0 bedrooms)</option>
                            <option value="1" <?php echo ($_POST['bedrooms'] ?? '') === '1' ? 'selected' : ''; ?>>1 Bedroom</option>
                            <option value="2" <?php echo ($_POST['bedrooms'] ?? '') === '2' ? 'selected' : ''; ?>>2 Bedrooms</option>
                            <option value="3" <?php echo ($_POST['bedrooms'] ?? '') === '3' ? 'selected' : ''; ?>>3 Bedrooms</option>
                            <option value="4" <?php echo ($_POST['bedrooms'] ?? '') === '4' ? 'selected' : ''; ?>>4 Bedrooms</option>
                            <option value="5" <?php echo ($_POST['bedrooms'] ?? '') === '5' ? 'selected' : ''; ?>>5+ Bedrooms</option>
                        </select>
                        <?php if (isset($errors['bedrooms'])): ?>
                            <div class="text-red-500 text-sm mt-1"><?php echo htmlspecialchars($errors['bedrooms']); ?></div>
                        <?php endif; ?>
                    </div>

                    <!-- Bathrooms -->
                    <div>
                        <label for="bathrooms" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Number of Bathrooms <span class="text-red-500">*</span>
                        </label>
                        <select 
                            id="bathrooms" 
                            name="bathrooms" 
                            required
                            class="w-full px-4 py-3 border <?php echo isset($errors['bathrooms']) ? 'border-red-500 dark:border-red-400' : 'border-gray-300 dark:border-gray-600'; ?> rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent bg-white dark:bg-gray-700 text-gray-900 dark:text-white transition-all duration-200"
                        >
                            <option value="">Select bathrooms</option>
                            <option value="1" <?php echo ($_POST['bathrooms'] ?? '') === '1' ? 'selected' : ''; ?>>1 Bathroom</option>
                            <option value="1.5" <?php echo ($_POST['bathrooms'] ?? '') === '1.5' ? 'selected' : ''; ?>>1.5 Bathrooms</option>
                            <option value="2" <?php echo ($_POST['bathrooms'] ?? '') === '2' ? 'selected' : ''; ?>>2 Bathrooms</option>
                            <option value="2.5" <?php echo ($_POST['bathrooms'] ?? '') === '2.5' ? 'selected' : ''; ?>>2.5 Bathrooms</option>
                            <option value="3" <?php echo ($_POST['bathrooms'] ?? '') === '3' ? 'selected' : ''; ?>>3 Bathrooms</option>
                            <option value="3.5" <?php echo ($_POST['bathrooms'] ?? '') === '3.5' ? 'selected' : ''; ?>>3.5 Bathrooms</option>
                            <option value="4" <?php echo ($_POST['bathrooms'] ?? '') === '4' ? 'selected' : ''; ?>>4+ Bathrooms</option>
                        </select>
                        <?php if (isset($errors['bathrooms'])): ?>
                            <div class="text-red-500 text-sm mt-1"><?php echo htmlspecialchars($errors['bathrooms']); ?></div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Image Upload -->
                <div>
                    <label for="propertyImage" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Property Image (Optional)
                    </label>
                    <div class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 dark:border-gray-600 border-dashed rounded-lg hover:border-primary-400 dark:hover:border-primary-500 transition-colors">
                        <div class="space-y-1 text-center">
                            <svg class="mx-auto h-12 w-12 text-gray-400 dark:text-gray-500" stroke="currentColor" fill="none" viewBox="0 0 48 48">
                                <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                            <div class="flex text-sm text-gray-600 dark:text-gray-400">
                                <label for="propertyImage" class="relative cursor-pointer bg-white dark:bg-gray-800 rounded-md font-medium text-primary-600 dark:text-primary-400 hover:text-primary-500 dark:hover:text-primary-300 focus-within:outline-none focus-within:ring-2 focus-within:ring-offset-2 focus-within:ring-primary-500">
                                    <span>Upload a file</span>
                                    <input id="propertyImage" name="propertyImage" type="file" accept="image/*" class="sr-only">
                                </label>
                                <p class="pl-1">or drag and drop</p>
                            </div>
                            <p class="text-xs text-gray-500 dark:text-gray-400">PNG, JPG, GIF up to 10MB</p>
                        </div>
                    </div>
                    <?php if (isset($errors['propertyImage'])): ?>
                        <div class="text-red-500 text-sm mt-1"><?php echo htmlspecialchars($errors['propertyImage']); ?></div>
                    <?php endif; ?>
                    <div id="imagePreview" class="mt-4 hidden">
                        <img id="previewImg" class="h-32 w-32 object-cover rounded-lg border border-gray-300 dark:border-gray-600" alt="Property preview">
                    </div>
                </div>

                <!-- Submit Button -->
                <div class="flex items-center justify-end space-x-4 pt-6 border-t border-gray-200 dark:border-gray-700">
                    <a 
                        href="dashboard.php"
                        class="px-6 py-3 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors"
                    >
                        Cancel
                    </a>
                    <button 
                        type="submit" 
                        class="px-6 py-3 bg-primary-600 hover:bg-primary-700 text-white font-medium rounded-lg transition-all duration-200 transform hover:scale-[1.02] focus:ring-4 focus:ring-primary-200 dark:focus:ring-primary-800"
                    >
                        Add Property
                    </button>
                </div>
            </form>
        </div>
    </main>

    <script>
        // Image upload preview
        const imageInput = document.getElementById('propertyImage');
        const imagePreview = document.getElementById('imagePreview');
        const previewImg = document.getElementById('previewImg');

        imageInput.addEventListener('change', (e) => {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = (e) => {
                    previewImg.src = e.target.result;
                    imagePreview.classList.remove('hidden');
                };
                reader.readAsDataURL(file);
            } else {
                imagePreview.classList.add('hidden');
            }
        });

        // Map link validation
        const mapLinkInput = document.getElementById('mapLink');
        mapLinkInput.addEventListener('blur', function() {
            const value = this.value.trim();
            if (value && !value.match(/^https:\/\/(www\.)?(google\.(com|[a-z]{2,3}(\.[a-z]{2})?)|maps\.google\.(com|[a-z]{2,3}(\.[a-z]{2})?))\/(maps|url)/)) {
                this.setCustomValidity('Please enter a valid Google Maps link');
            } else {
                this.setCustomValidity('');
            }
        });
    </script>
</body>
</html>