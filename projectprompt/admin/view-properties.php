<?php
// Include database configuration (this will start the session)
require_once 'include/config.php';

// Check if user is logged in
if (!isset($_SESSION['is_logged_in']) || $_SESSION['is_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

// Get database connection
$pdo = getDatabaseConnection();

// Get user information from session
$user_id = $_SESSION['user_id'] ?? 0;

$success_message = '';
$error_message = '';

// Handle property editing
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_property'])) {
    $property_id = intval($_POST['property_id'] ?? 0);
    $name = trim($_POST['propertyName'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $location = trim($_POST['location'] ?? '');
    $price = floatval($_POST['price'] ?? 0);
    $type = $_POST['propertyType'] ?? '';
    $bedrooms = intval($_POST['bedrooms'] ?? 0);
    $bathrooms = floatval($_POST['bathrooms'] ?? 0);
    $status = $_POST['status'] ?? '';
    
    // Validation
    $errors = [];
    
    if (empty($name)) {
        $errors[] = 'Property name is required';
    }
    
    if (empty($description)) {
        $errors[] = 'Description is required';
    }
    
    if (empty($location)) {
        $errors[] = 'Location is required';
    }
    
    if ($price <= 0) {
        $errors[] = 'Valid price is required';
    }
    
    if (empty($type)) {
        $errors[] = 'Property type is required';
    }
    
    if ($bedrooms < 0) {
        $errors[] = 'Number of bedrooms is required';
    }
    
    if ($bathrooms <= 0) {
        $errors[] = 'Number of bathrooms is required';
    }
    
    if (empty($status)) {
        $errors[] = 'Property status is required';
    }
    
    if (empty($errors)) {
        try {
            // Handle image upload for edit
            $image_path = $_POST['existing_image'] ?? '';
            
            if (isset($_FILES['propertyImage']) && $_FILES['propertyImage']['error'] === UPLOAD_ERR_OK) {
                $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
                $max_size = 10 * 1024 * 1024; // 10MB
                
                $file = $_FILES['propertyImage'];
                
                if (in_array($file['type'], $allowed_types) && $file['size'] <= $max_size) {
                    // Create uploads directory if it doesn't exist
                    $upload_dir = 'uploads/properties/';
                    if (!file_exists($upload_dir)) {
                        mkdir($upload_dir, 0777, true);
                    }
                    
                    // Delete old image if exists
                    if (!empty($image_path) && file_exists($image_path)) {
                        unlink($image_path);
                    }
                    
                    // Generate unique filename
                    $file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
                    $filename = uniqid('property_' . $user_id . '_') . '.' . $file_extension;
                    $new_image_path = $upload_dir . $filename;
                    
                    if (move_uploaded_file($file['tmp_name'], $new_image_path)) {
                        $image_path = $new_image_path;
                    }
                }
            }
            
            // Convert text status to numeric if your database expects numeric values
            $status_to_save = $status;
            // You can uncomment this if your database uses numeric status
            
            $text_to_numeric = [
                'available' => 0,
                'rented' => 1,
                'maintenance' => 2,
                'sold' => 3
            ];
            if ($text_to_numeric[$status]) {
                $status_to_save = $text_to_numeric[$status];
            }
            
            
            $stmt = $pdo->prepare("
                UPDATE property 
                SET name = ?, description = ?, location = ?, price = ?, type = ?, 
                    number_of_bedrooms = ?, number_of_bathrooms = ?, status = ?, property_image = ?
                WHERE Id = ? AND created_by = ?
            ");
            
            $result = $stmt->execute([
                $name, $description, $location, $price, $type, 
                $bedrooms, $bathrooms, $status_to_save, $image_path,
                $property_id, $user_id
            ]);
            
            if ($result && $stmt->rowCount() > 0) {
                $success_message = "Property updated successfully!";
            } else {
                $error_message = "No changes were made or property not found.";
            }
            
        } catch (PDOException $e) {
            error_log("Edit property error: " . $e->getMessage());
            $error_message = "Failed to update property. Please try again. Error: " . $e->getMessage();
        }
    } else {
        $error_message = implode(', ', $errors);
    }
}

// Handle property deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_property'])) {
    $property_id = intval($_POST['property_id'] ?? 0);
    
    try {
        // First, get the property details for logging
        $stmt = $pdo->prepare("SELECT name, property_image FROM property WHERE Id = ? AND created_by = ?");
        $stmt->execute([$property_id, $user_id]);
        $property = $stmt->fetch();
        
        if ($property) {
            // Delete the property
            $stmt = $pdo->prepare("DELETE FROM property WHERE Id = ? AND created_by = ?");
            $stmt->execute([$property_id, $user_id]);
            
            // Delete associated image file if exists
            if (!empty($property['property_image']) && file_exists($property['property_image'])) {
                unlink($property['property_image']);
            }
            
            $success_message = "Property '{$property['name']}' has been deleted successfully.";
        } else {
            $error_message = "Property not found or you don't have permission to delete it.";
        }
    } catch (PDOException $e) {
        error_log("Delete property error: " . $e->getMessage());
        $error_message = "Failed to delete property. Please try again.";
    }
}

// Get filters from GET parameters
$search = $_GET['search'] ?? '';
$type_filter = $_GET['type'] ?? '';
$status_filter = $_GET['status'] ?? '';

// Build the query with filters
$query = "SELECT * FROM property WHERE created_by = ?";
$params = [$user_id];

if (!empty($search)) {
    $query .= " AND (name LIKE ? OR description LIKE ? OR location LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
}

if (!empty($type_filter)) {
    $query .= " AND type = ?";
    $params[] = $type_filter;
}

if (!empty($status_filter)) {
    // Simple and direct status filter - try multiple formats
    $query .= " AND status = ?";
    
    // Add numeric equivalent
    $status_to_num = [
        'available' => 0,
        'rented' => 1, 
        'maintenance' => 2,
        'sold' => 3
    ];
    $params[] = $status_to_num[$status_filter] ? $status_to_num[$status_filter] : $status_filter;
}

$query .= " ORDER BY created_at DESC";

try {
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $properties = $stmt->fetchAll();
    
    // Debug logging (remove in production)
    if (!empty($search) || !empty($type_filter) || !empty($status_filter)) {
        error_log("Filter Query: " . $query);
        error_log("Filter Params: " . json_encode($params));
        error_log("Results Count: " . count($properties));
    }
    
    // Debug status values in database (remove in production)
    if (!empty($status_filter)) {
        $debug_stmt = $pdo->prepare("SELECT DISTINCT status FROM property WHERE created_by = ?");
        $debug_stmt->execute([$user_id]);
        $status_values = $debug_stmt->fetchAll(PDO::FETCH_COLUMN);
        error_log("Available status values in DB: " . json_encode($status_values));
        error_log("Filtering for status: " . $status_filter);
    }
    
} catch (PDOException $e) {
    error_log("Fetch properties error: " . $e->getMessage());
    $properties = [];
    $error_message = "Failed to load properties.";
}

// Check for success message from redirect
if (isset($_SESSION['success_message'])) {
    $success_message = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}

// Handle status fix requests
if (isset($_GET['fix_status'])) {
    try {
        if ($_GET['fix_status'] === 'text') {
            // Convert numeric status to text
            $pdo->prepare("UPDATE property SET status = 'available' WHERE created_by = ? AND (status = '0' OR status = 0)")->execute([$user_id]);
            $pdo->prepare("UPDATE property SET status = 'rented' WHERE created_by = ? AND (status = '1' OR status = 1)")->execute([$user_id]);
            $pdo->prepare("UPDATE property SET status = 'maintenance' WHERE created_by = ? AND (status = '2' OR status = 2)")->execute([$user_id]);
            $pdo->prepare("UPDATE property SET status = 'sold' WHERE created_by = ? AND (status = '3' OR status = 3)")->execute([$user_id]);
            $success_message = "All status values converted to text format!";
        } elseif ($_GET['fix_status'] === 'numeric') {
            // Convert text status to numeric
            $pdo->prepare("UPDATE property SET status = 0 WHERE created_by = ? AND status = 'available'")->execute([$user_id]);
            $pdo->prepare("UPDATE property SET status = 1 WHERE created_by = ? AND status = 'rented'")->execute([$user_id]);
            $pdo->prepare("UPDATE property SET status = 2 WHERE created_by = ? AND status = 'maintenance'")->execute([$user_id]);
            $pdo->prepare("UPDATE property SET status = 3 WHERE created_by = ? AND status = 'sold'")->execute([$user_id]);
            $success_message = "All status values converted to numeric format!";
        }
        
        // Redirect to clear the fix_status parameter
        header('Location: view-properties.php');
        exit;
        
    } catch (PDOException $e) {
        $error_message = "Failed to fix status values: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Properties - Property Management</title>
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
    <main class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-8">
            <div>
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-2">Properties</h1>
                <p class="text-gray-600 dark:text-gray-400">Manage and view all your properties (<?php echo count($properties); ?> total)</p>
            </div>
            <div class="mt-4 sm:mt-0">
                <a href="add-property.php" class="inline-flex items-center px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white font-medium rounded-lg transition-colors">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                    </svg>
                    Add Property
                </a>
            </div>
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

        <!-- Filters -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 p-6 mb-8">
            <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <label for="search" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Search</label>
                    <input 
                        type="text" 
                        id="search" 
                        name="search"
                        value="<?php echo htmlspecialchars($search); ?>"
                        placeholder="Search properties..."
                        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400"
                    >
                </div>
                <div>
                    <label for="type" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Type</label>
                    <select 
                        id="type"
                        name="type"
                        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
                    >
                        <option value="">All Types</option>
                        <option value="apartment" <?php echo $type_filter === 'apartment' ? 'selected' : ''; ?>>Apartment</option>
                        <option value="house" <?php echo $type_filter === 'house' ? 'selected' : ''; ?>>House</option>
                        <option value="studio" <?php echo $type_filter === 'studio' ? 'selected' : ''; ?>>Studio</option>
                        <option value="condo" <?php echo $type_filter === 'condo' ? 'selected' : ''; ?>>Condo</option>
                        <option value="townhouse" <?php echo $type_filter === 'townhouse' ? 'selected' : ''; ?>>Townhouse</option>
                        <option value="villa" <?php echo $type_filter === 'villa' ? 'selected' : ''; ?>>Villa</option>
                        <option value="commercial" <?php echo $type_filter === 'commercial' ? 'selected' : ''; ?>>Commercial</option>
                    </select>
                </div>
                <div>
                    <label for="status" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Status</label>
                    <select 
                        id="status"
                        name="status"
                        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
                    >
                        <option value="">All Status</option>
                        <option value="available" <?php echo $status_filter === 'available' ? 'selected' : ''; ?>>Available</option>
                        <option value="rented" <?php echo $status_filter === 'rented' ? 'selected' : ''; ?>>Rented</option>
                        <option value="maintenance" <?php echo $status_filter === 'maintenance' ? 'selected' : ''; ?>>Under Maintenance</option>
                        <option value="sold" <?php echo $status_filter === 'sold' ? 'selected' : ''; ?>>Sold</option>
                    </select>
                </div>
                <div class="flex items-end space-x-2">
                    <button 
                        type="submit"
                        class="flex-1 px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-lg transition-colors"
                    >
                        Filter
                    </button>
                    <a 
                        href="view-properties.php"
                        class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors"
                    >
                        Clear
                    </a>
                </div>
            </form>
        </div>

        <!-- Properties Grid -->
        <?php if (empty($properties)): ?>
            <!-- Empty State -->
            <div class="text-center py-12">
                <div class="w-24 h-24 bg-gray-100 dark:bg-gray-800 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg class="w-12 h-12 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                    </svg>
                </div>
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">
                    <?php echo (!empty($search) || !empty($type_filter) || !empty($status_filter)) ? 'No properties found' : 'No properties yet'; ?>
                </h3>
                <p class="text-gray-600 dark:text-gray-400 mb-6">
                    <?php echo (!empty($search) || !empty($type_filter) || !empty($status_filter)) ? 'Try adjusting your search criteria.' : 'Get started by adding your first property.'; ?>
                </p>
                <a href="add-property.php" class="inline-flex items-center px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white font-medium rounded-lg transition-colors">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                    </svg>
                    Add Property
                </a>
            </div>
        <?php else: ?>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php foreach ($properties as $property): ?>
                    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 overflow-hidden hover:shadow-xl transition-shadow">
                        <?php if (!empty($property['property_image']) && file_exists($property['property_image'])): ?>
                            <div class="h-48 bg-gray-200 dark:bg-gray-700 overflow-hidden">
                                <img src="<?php echo htmlspecialchars($property['property_image']); ?>" alt="<?php echo htmlspecialchars($property['name']); ?>" class="w-full h-full object-cover">
                            </div>
                        <?php else: ?>
                            <div class="h-48 bg-gradient-to-br from-primary-400 to-primary-600 flex items-center justify-center">
                                <svg class="w-16 h-16 text-white opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                                </svg>
                            </div>
                        <?php endif; ?>
                        
                        <div class="p-6">
                            <div class="flex items-start justify-between mb-3">
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-white truncate"><?php echo htmlspecialchars($property['name']); ?></h3>
                                <?php
                                $status = $property['status'] ?? 'available';
                                // Handle numeric status values if they exist
                                if (is_numeric($status)) {
                                    $status_map = [
                                        '0' => 'available',
                                        '1' => 'rented', 
                                        '2' => 'maintenance',
                                        '3' => 'sold'
                                    ];
                                    $status = $status_map[$status] ?? 'available';
                                }
                                
                                $status_classes = [
                                    'available' => 'bg-green-100 dark:bg-green-900/20 text-green-800 dark:text-green-300',
                                    'rented' => 'bg-blue-100 dark:bg-blue-900/20 text-blue-800 dark:text-blue-300',
                                    'maintenance' => 'bg-yellow-100 dark:bg-yellow-900/20 text-yellow-800 dark:text-yellow-300',
                                    'sold' => 'bg-gray-100 dark:bg-gray-900/20 text-gray-800 dark:text-gray-300'
                                ];
                                $status_text = [
                                    'available' => 'Available',
                                    'rented' => 'Rented',
                                    'maintenance' => 'Maintenance',
                                    'sold' => 'Sold'
                                ];
                                ?>
                                <span class="px-2 py-1 text-xs font-medium rounded-full <?php echo $status_classes[$status] ?? $status_classes['available']; ?>">
                                    <?php echo $status_text[$status] ?? ucfirst($status); ?>
                                </span>
                            </div>
                            
                            <p class="text-gray-600 dark:text-gray-400 text-sm mb-3 line-clamp-2"><?php echo htmlspecialchars(substr($property['description'], 0, 100)) . (strlen($property['description']) > 100 ? '...' : ''); ?></p>
                            
                            <div class="flex items-center text-gray-500 dark:text-gray-400 text-sm mb-3">
                                <svg class="w-4 h-4 mr-1 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                </svg>
                                <span class="truncate"><?php echo htmlspecialchars($property['location']); ?></span>
                            </div>
                            
                            <div class="flex items-center justify-between mb-4">
                                <div class="flex items-center space-x-4 text-sm text-gray-600 dark:text-gray-400">
                                    <span><?php echo $property['number_of_bedrooms']; ?> bed</span>
                                    <span><?php echo $property['number_of_bathrooms']; ?> bath</span>
                                    <span class="capitalize"><?php echo htmlspecialchars($property['type']); ?></span>
                                </div>
                            </div>
                            
                            <div class="flex items-center justify-between">
                                <div class="text-2xl font-bold text-primary-600 dark:text-primary-400">
                                    $<?php echo number_format($property['price'], 0); ?>
                                </div>
                                <div class="flex space-x-2">
                                    <button onclick="viewProperty(<?php echo $property['Id']; ?>)" class="px-3 py-1 text-sm bg-primary-100 dark:bg-primary-900/20 text-primary-700 dark:text-primary-300 rounded-lg hover:bg-primary-200 dark:hover:bg-primary-900/30 transition-colors">
                                        View
                                    </button>
                                    <button onclick="editProperty(<?php echo $property['Id']; ?>)" class="px-3 py-1 text-sm bg-yellow-100 dark:bg-yellow-900/20 text-yellow-700 dark:text-yellow-300 rounded-lg hover:bg-yellow-200 dark:hover:bg-yellow-900/30 transition-colors">
                                        Edit
                                    </button>
                                    <button onclick="deleteProperty(<?php echo $property['Id']; ?>, '<?php echo htmlspecialchars(addslashes($property['name'])); ?>')" class="px-3 py-1 text-sm bg-red-100 dark:bg-red-900/20 text-red-700 dark:text-red-300 rounded-lg hover:bg-red-200 dark:hover:bg-red-900/30 transition-colors">
                                        Delete
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </main>

    <!-- Edit Property Modal -->
    <div id="editPropertyModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center p-4">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-2xl max-w-4xl w-full max-h-[90vh] overflow-y-auto">
            <div class="p-6">
                <div class="flex items-center justify-between mb-6">
                    <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Edit Property</h2>
                    <button id="closeEditModal" class="p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors">
                        <svg class="w-6 h-6 text-gray-500 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
                
                <form id="editPropertyForm" method="POST" enctype="multipart/form-data" class="space-y-6">
                    <input type="hidden" name="edit_property" value="1">
                    <input type="hidden" name="property_id" id="editPropertyId">
                    <input type="hidden" name="existing_image" id="editExistingImage">
                    
                    <!-- Property Name -->
                    <div>
                        <label for="editPropertyName" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Property Name <span class="text-red-500">*</span>
                        </label>
                        <input 
                            type="text" 
                            id="editPropertyName" 
                            name="propertyName" 
                            required
                            class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
                        >
                    </div>

                    <!-- Description -->
                    <div>
                        <label for="editDescription" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Description <span class="text-red-500">*</span>
                        </label>
                        <textarea 
                            id="editDescription" 
                            name="description" 
                            rows="4" 
                            required
                            class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent bg-white dark:bg-gray-700 text-gray-900 dark:text-white resize-none"
                        ></textarea>
                    </div>

                    <!-- Location -->
                    <div>
                        <label for="editLocation" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Location <span class="text-red-500">*</span>
                        </label>
                        <input 
                            type="text" 
                            id="editLocation" 
                            name="location" 
                            required
                            class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
                        >
                    </div>

                    <!-- Price and Type -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="editPrice" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Price <span class="text-red-500">*</span>
                            </label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <span class="text-gray-500 dark:text-gray-400 sm:text-sm">$</span>
                                </div>
                                <input 
                                    type="number" 
                                    id="editPrice" 
                                    name="price" 
                                    min="0" 
                                    step="0.01" 
                                    required
                                    class="w-full pl-8 pr-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
                                >
                            </div>
                        </div>

                        <div>
                            <label for="editPropertyType" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Property Type <span class="text-red-500">*</span>
                            </label>
                            <select 
                                id="editPropertyType" 
                                name="propertyType" 
                                required
                                class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
                            >
                                <option value="">Select property type</option>
                                <option value="apartment">Apartment</option>
                                <option value="house">House</option>
                                <option value="studio">Studio</option>
                                <option value="condo">Condo</option>
                                <option value="townhouse">Townhouse</option>
                                <option value="villa">Villa</option>
                                <option value="commercial">Commercial</option>
                            </select>
                        </div>
                    </div>

                    <!-- Bedrooms, Bathrooms, Status -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div>
                            <label for="editBedrooms" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Bedrooms <span class="text-red-500">*</span>
                            </label>
                            <select 
                                id="editBedrooms" 
                                name="bedrooms" 
                                required
                                class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
                            >
                                <option value="">Select bedrooms</option>
                                <option value="0">Studio (0 bedrooms)</option>
                                <option value="1">1 Bedroom</option>
                                <option value="2">2 Bedrooms</option>
                                <option value="3">3 Bedrooms</option>
                                <option value="4">4 Bedrooms</option>
                                <option value="5">5+ Bedrooms</option>
                            </select>
                        </div>

                        <div>
                            <label for="editBathrooms" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Bathrooms <span class="text-red-500">*</span>
                            </label>
                            <select 
                                id="editBathrooms" 
                                name="bathrooms" 
                                required
                                class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
                            >
                                <option value="">Select bathrooms</option>
                                <option value="1">1 Bathroom</option>
                                <option value="1.5">1.5 Bathrooms</option>
                                <option value="2">2 Bathrooms</option>
                                <option value="2.5">2.5 Bathrooms</option>
                                <option value="3">3 Bathrooms</option>
                                <option value="3.5">3.5 Bathrooms</option>
                                <option value="4">4+ Bathrooms</option>
                            </select>
                        </div>

                        <div>
                            <label for="editStatus" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Status <span class="text-red-500">*</span>
                            </label>
                            <select 
                                id="editStatus" 
                                name="status" 
                                required
                                class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
                            >
                                <option value="">Select status</option>
                                <option value="available">Available</option>
                                <option value="rented">Rented</option>
                                <option value="maintenance">Under Maintenance</option>
                                <option value="sold">Sold</option>
                            </select>
                        </div>
                    </div>

                    <!-- Current Image Display -->
                    <div id="currentImageContainer" class="hidden">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Current Image
                        </label>
                        <img id="currentImage" class="h-32 w-32 object-cover rounded-lg border border-gray-300 dark:border-gray-600 mb-4" alt="Current property image">
                    </div>

                    <!-- Image Upload -->
                    <div>
                        <label for="editPropertyImage" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Update Property Image (Optional)
                        </label>
                        <div class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 dark:border-gray-600 border-dashed rounded-lg hover:border-primary-400 dark:hover:border-primary-500 transition-colors">
                            <div class="space-y-1 text-center">
                                <svg class="mx-auto h-12 w-12 text-gray-400 dark:text-gray-500" stroke="currentColor" fill="none" viewBox="0 0 48 48">
                                    <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                                <div class="flex text-sm text-gray-600 dark:text-gray-400">
                                    <label for="editPropertyImage" class="relative cursor-pointer bg-white dark:bg-gray-800 rounded-md font-medium text-primary-600 dark:text-primary-400 hover:text-primary-500 dark:hover:text-primary-300">
                                        <span>Upload a file</span>
                                        <input id="editPropertyImage" name="propertyImage" type="file" accept="image/*" class="sr-only">
                                    </label>
                                    <p class="pl-1">or drag and drop</p>
                                </div>
                                <p class="text-xs text-gray-500 dark:text-gray-400">PNG, JPG, GIF up to 10MB</p>
                            </div>
                        </div>
                        <div id="editImagePreview" class="mt-4 hidden">
                            <img id="editPreviewImg" class="h-32 w-32 object-cover rounded-lg border border-gray-300 dark:border-gray-600" alt="New property preview">
                        </div>
                    </div>

                    <!-- Submit Buttons -->
                    <div class="flex items-center justify-end space-x-4 pt-6 border-t border-gray-200 dark:border-gray-700">
                        <button 
                            type="button"
                            onclick="closeEditPropertyModal()"
                            class="px-6 py-3 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors"
                        >
                            Cancel
                        </button>
                        <button 
                            type="submit" 
                            class="px-6 py-3 bg-primary-600 hover:bg-primary-700 text-white font-medium rounded-lg transition-all duration-200 transform hover:scale-[1.02] focus:ring-4 focus:ring-primary-200 dark:focus:ring-primary-800"
                        >
                            Update Property
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Property Details Modal -->
    <div id="propertyModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center p-4">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-2xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
            <div class="p-6">
                <div class="flex items-center justify-between mb-6">
                    <h2 class="text-2xl font-bold text-gray-900 dark:text-white" id="modalTitle">Property Details</h2>
                    <button id="closeModal" class="p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors">
                        <svg class="w-6 h-6 text-gray-500 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
                <div id="modalContent">
                    <!-- Modal content will be loaded here -->
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Form -->
    <form id="deleteForm" method="POST" style="display: none;">
        <input type="hidden" name="delete_property" value="1">
        <input type="hidden" name="property_id" id="deletePropertyId">
    </form>

    <script>
        // Property data for JavaScript (with debugging)
        const properties = <?php echo json_encode($properties); ?>;
        console.log('Loaded properties:', properties);

        function getStatusClasses(status) {
            // Handle numeric status values
            if (typeof status === 'number' || !isNaN(status)) {
                const statusMap = {
                    '0': 'available',
                    '1': 'rented',
                    '2': 'maintenance', 
                    '3': 'sold'
                };
                status = statusMap[status.toString()] || 'available';
            }
            
            const statusClasses = {
                'available': 'bg-green-100 dark:bg-green-900/20 text-green-800 dark:text-green-300',
                'rented': 'bg-blue-100 dark:bg-blue-900/20 text-blue-800 dark:text-blue-300',
                'maintenance': 'bg-yellow-100 dark:bg-yellow-900/20 text-yellow-800 dark:text-yellow-300',
                'sold': 'bg-gray-100 dark:bg-gray-900/20 text-gray-800 dark:text-gray-300'
            };
            return statusClasses[status] || statusClasses['available'];
        }

        function getStatusText(status) {
            // Handle numeric status values
            if (typeof status === 'number' || !isNaN(status)) {
                const statusMap = {
                    '0': 'available',
                    '1': 'rented',
                    '2': 'maintenance',
                    '3': 'sold'
                };
                status = statusMap[status.toString()] || 'available';
            }
            
            const statusText = {
                'available': 'Available',
                'rented': 'Rented',
                'maintenance': 'Maintenance',
                'sold': 'Sold'
            };
            return statusText[status] || status.charAt(0).toUpperCase() + status.slice(1);
        }

        function viewProperty(propertyId) {
            const property = properties.find(p => p.Id == propertyId);
            if (!property) {
                console.error('Property not found:', propertyId);
                return;
            }

            const modal = document.getElementById('propertyModal');
            const modalTitle = document.getElementById('modalTitle');
            const modalContent = document.getElementById('modalContent');

            modalTitle.textContent = property.name;
            
            // Convert numeric status if needed
            let displayStatus = property.status;
            if (typeof displayStatus === 'number' || !isNaN(displayStatus)) {
                const statusMap = {
                    '0': 'available',
                    '1': 'rented',
                    '2': 'maintenance',
                    '3': 'sold'
                };
                displayStatus = statusMap[displayStatus.toString()] || 'available';
            }

            modalContent.innerHTML = `
                ${property.property_image ? `
                    <div class="mb-6">
                        <img src="${property.property_image}" alt="${property.name}" class="w-full h-64 object-cover rounded-lg" onerror="this.style.display='none'">
                    </div>
                ` : ''}
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-3">Property Details</h3>
                        <div class="space-y-2 text-sm">
                            <div class="flex justify-between">
                                <span class="text-gray-600 dark:text-gray-400">Type:</span>
                                <span class="text-gray-900 dark:text-white capitalize">${property.type || 'N/A'}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600 dark:text-gray-400">Bedrooms:</span>
                                <span class="text-gray-900 dark:text-white">${property.number_of_bedrooms || 0}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600 dark:text-gray-400">Bathrooms:</span>
                                <span class="text-gray-900 dark:text-white">${property.number_of_bathrooms || 0}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600 dark:text-gray-400">Status:</span>
                                <span class="px-2 py-1 text-xs font-medium rounded-full ${getStatusClasses(displayStatus)}">
                                    ${getStatusText(displayStatus)}
                                </span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600 dark:text-gray-400">Price:</span>
                                <span class="text-lg font-bold text-primary-600 dark:text-primary-400">${parseFloat(property.price || 0).toLocaleString()}</span>
                            </div>
                        </div>
                    </div>
                    
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-3">Location & Description</h3>
                        <div class="space-y-3">
                            <div>
                                <span class="text-sm text-gray-600 dark:text-gray-400">Address:</span>
                                <p class="text-gray-900 dark:text-white">${property.location || 'N/A'}</p>
                            </div>
                            <div>
                                <span class="text-sm text-gray-600 dark:text-gray-400">Description:</span>
                                <p class="text-gray-900 dark:text-white text-sm leading-relaxed">${property.description || 'No description available'}</p>
                            </div>
                            <div>
                                <span class="text-sm text-gray-600 dark:text-gray-400">Added:</span>
                                <p class="text-gray-900 dark:text-white text-sm">${property.created_at ? new Date(property.created_at).toLocaleDateString() : 'N/A'}</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="flex justify-end space-x-3 mt-6 pt-6 border-t border-gray-200 dark:border-gray-700">
                    <button onclick="closePropertyModal()" class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                        Close
                    </button>
                    <button onclick="editProperty(${property.Id}); closePropertyModal();" class="px-4 py-2 bg-yellow-600 hover:bg-yellow-700 text-white rounded-lg transition-colors">
                        Edit Property
                    </button>
                    <button onclick="deleteProperty(${property.Id}, '${(property.name || '').replace(/'/g, "\\'")}'); closePropertyModal();" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg transition-colors">
                        Delete Property
                    </button>
                </div>
            `;

            modal.classList.remove('hidden');
            
            // Debug logging
            console.log('Opening modal for property:', property);
        }

        function editProperty(propertyId) {
            const property = properties.find(p => p.Id == propertyId);
            if (!property) {
                console.error('Property not found for editing:', propertyId);
                return;
            }

            // Convert numeric status to string if needed
            let displayStatus = property.status;
            if (typeof displayStatus === 'number' || !isNaN(displayStatus)) {
                const statusMap = {
                    '0': 'available',
                    '1': 'rented',
                    '2': 'maintenance',
                    '3': 'sold'
                };
                displayStatus = statusMap[displayStatus.toString()] || 'available';
            }

            // Populate the edit form
            document.getElementById('editPropertyId').value = property.Id;
            document.getElementById('editPropertyName').value = property.name || '';
            document.getElementById('editDescription').value = property.description || '';
            document.getElementById('editLocation').value = property.location || '';
            document.getElementById('editPrice').value = property.price || '';
            document.getElementById('editPropertyType').value = property.type || '';
            document.getElementById('editBedrooms').value = property.number_of_bedrooms || '';
            document.getElementById('editBathrooms').value = property.number_of_bathrooms || '';
            document.getElementById('editStatus').value = displayStatus || 'available';
            document.getElementById('editExistingImage').value = property.property_image || '';

            // Show current image if exists
            const currentImageContainer = document.getElementById('currentImageContainer');
            const currentImage = document.getElementById('currentImage');
            if (property.property_image) {
                currentImage.src = property.property_image;
                currentImageContainer.classList.remove('hidden');
            } else {
                currentImageContainer.classList.add('hidden');
            }

            // Show the edit modal
            document.getElementById('editPropertyModal').classList.remove('hidden');
            
            console.log('Opening edit modal for property:', property);
        }

        function closeEditPropertyModal() {
            document.getElementById('editPropertyModal').classList.add('hidden');
            // Reset form
            document.getElementById('editPropertyForm').reset();
            document.getElementById('currentImageContainer').classList.add('hidden');
            document.getElementById('editImagePreview').classList.add('hidden');
        }

        function closePropertyModal() {
            document.getElementById('propertyModal').classList.add('hidden');
        }

        function deleteProperty(propertyId, propertyName) {
            if (!confirm(`Are you sure you want to delete "${propertyName}"? This action cannot be undone.`)) {
                return;
            }

            document.getElementById('deletePropertyId').value = propertyId;
            document.getElementById('deleteForm').submit();
        }

        // Event listeners
        document.getElementById('closeModal').addEventListener('click', closePropertyModal);
        document.getElementById('closeEditModal').addEventListener('click', closeEditPropertyModal);

        // Close modals when clicking outside
        document.getElementById('propertyModal').addEventListener('click', (e) => {
            if (e.target.id === 'propertyModal') {
                closePropertyModal();
            }
        });

        document.getElementById('editPropertyModal').addEventListener('click', (e) => {
            if (e.target.id === 'editPropertyModal') {
                closeEditPropertyModal();
            }
        });

        // Image preview for edit modal
        const editImageInput = document.getElementById('editPropertyImage');
        const editImagePreview = document.getElementById('editImagePreview');
        const editPreviewImg = document.getElementById('editPreviewImg');

        editImageInput.addEventListener('change', (e) => {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = (e) => {
                    editPreviewImg.src = e.target.result;
                    editImagePreview.classList.remove('hidden');
                };
                reader.readAsDataURL(file);
            } else {
                editImagePreview.classList.add('hidden');
            }
        });

        // Auto-hide success/error messages after 5 seconds
        setTimeout(() => {
            const messages = document.querySelectorAll('[class*="bg-green-50"], [class*="bg-red-50"]');
            messages.forEach(message => {
                message.style.transition = 'opacity 0.5s';
                message.style.opacity = '0';
                setTimeout(() => message.remove(), 500);
            });
        }, 5000);
    </script>
</body>
</html>