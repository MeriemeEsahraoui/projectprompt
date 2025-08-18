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

// Handle CSV export FIRST - before any HTML output
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    // Get filters from GET parameters for export
    $search = $_GET['search'] ?? '';
    $property_filter = $_GET['property'] ?? '';
    $date_filter = $_GET['date'] ?? '';
    $sort_by = $_GET['sort'] ?? 'created_at';
    $sort_order = $_GET['order'] ?? 'DESC';

    // Build the same query used for display
    $query = "
        SELECT i.*, p.name as property_name, p.location as property_location 
        FROM inquire i 
        INNER JOIN property p ON i.property_id = p.Id 
        WHERE p.created_by = ?
    ";
    $params = [$user_id];

    if (!empty($search)) {
        $query .= " AND (i.fullname LIKE ? OR i.email LIKE ? OR i.message LIKE ? OR p.name LIKE ?)";
        $search_param = "%$search%";
        $params[] = $search_param;
        $params[] = $search_param;
        $params[] = $search_param;
        $params[] = $search_param;
    }

    if (!empty($property_filter)) {
        $query .= " AND i.property_id = ?";
        $params[] = $property_filter;
    }

    if (!empty($date_filter)) {
        switch ($date_filter) {
            case 'today':
                $query .= " AND DATE(i.created_at) = CURDATE()";
                break;
            case 'week':
                $query .= " AND i.created_at >= DATE_SUB(NOW(), INTERVAL 1 WEEK)";
                break;
            case 'month':
                $query .= " AND i.created_at >= DATE_SUB(NOW(), INTERVAL 1 MONTH)";
                break;
            case 'year':
                $query .= " AND i.created_at >= DATE_SUB(NOW(), INTERVAL 1 YEAR)";
                break;
        }
    }

    // Add sorting
    $allowed_sort_columns = ['created_at', 'fullname', 'email', 'property_name'];
    $allowed_sort_orders = ['ASC', 'DESC'];

    if (in_array($sort_by, $allowed_sort_columns) && in_array(strtoupper($sort_order), $allowed_sort_orders)) {
        $query .= " ORDER BY " . ($sort_by === 'property_name' ? 'p.name' : 'i.' . $sort_by) . " " . strtoupper($sort_order);
    } else {
        $query .= " ORDER BY i.created_at DESC";
    }

    try {
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        $export_inquiries = $stmt->fetchAll();
        
        // Set headers for CSV download
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="inquiries_' . date('Y-m-d_H-i-s') . '.csv"');
        header('Pragma: no-cache');
        header('Expires: 0');
        
        // Create file pointer to output stream
        $output = fopen('php://output', 'w');
        
        // Add BOM for UTF-8 (helps with special characters in Excel)
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
        
        // Add CSV headers
        fputcsv($output, [
            'ID',
            'Full Name', 
            'Email',
            'Phone Number',
            'Property Name',
            'Property Location',
            'Message',
            'Inquiry Date',
            'Inquiry Time'
        ]);
        
        // Add data rows
        foreach ($export_inquiries as $inquiry) {
            fputcsv($output, [
                $inquiry['id'],
                $inquiry['fullname'],
                $inquiry['email'],
                $inquiry['phonenumber'] ?? '',
                $inquiry['property_name'],
                $inquiry['property_location'],
                $inquiry['message'],
                date('Y-m-d', strtotime($inquiry['created_at'])),
                date('H:i:s', strtotime($inquiry['created_at']))
            ]);
        }
        
        fclose($output);
        exit; // Important: exit after CSV export
        
    } catch (PDOException $e) {
        // Log error and redirect back with error message
        error_log("CSV Export error: " . $e->getMessage());
        $_SESSION['error_message'] = "Failed to export CSV. Please try again.";
        header('Location: ' . strtok($_SERVER["REQUEST_URI"], '?'));
        exit;
    }
}

$success_message = '';
$error_message = '';

// Handle inquiry deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_inquiry'])) {
    $inquiry_id = intval($_POST['inquiry_id'] ?? 0);
    
    try {
        // First, get the inquiry details for logging
        $stmt = $pdo->prepare("
            SELECT i.*, p.name as property_name 
            FROM inquire i 
            LEFT JOIN property p ON i.property_id = p.Id 
            WHERE i.id = ? AND p.created_by = ?
        ");
        $stmt->execute([$inquiry_id, $user_id]);
        $inquiry = $stmt->fetch();
        
        if ($inquiry) {
            // Delete the inquiry
            $stmt = $pdo->prepare("
                DELETE i FROM inquire i 
                INNER JOIN property p ON i.property_id = p.Id 
                WHERE i.id = ? AND p.created_by = ?
            ");
            $stmt->execute([$inquiry_id, $user_id]);
            
            $success_message = "Inquiry from {$inquiry['fullname']} for '{$inquiry['property_name']}' has been deleted successfully.";
        } else {
            $error_message = "Inquiry not found or you don't have permission to delete it.";
        }
    } catch (PDOException $e) {
        error_log("Delete inquiry error: " . $e->getMessage());
        $error_message = "Failed to delete inquiry. Please try again.";
    }
}

// Handle bulk deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bulk_delete'])) {
    $inquiry_ids = $_POST['inquiry_ids'] ?? [];
    
    if (!empty($inquiry_ids)) {
        try {
            $placeholders = str_repeat('?,', count($inquiry_ids) - 1) . '?';
            $params = array_merge($inquiry_ids, [$user_id]);
            
            $stmt = $pdo->prepare("
                DELETE i FROM inquire i 
                INNER JOIN property p ON i.property_id = p.Id 
                WHERE i.id IN ($placeholders) AND p.created_by = ?
            ");
            $stmt->execute($params);
            
            $deleted_count = $stmt->rowCount();
            $success_message = "$deleted_count inquiries have been deleted successfully.";
        } catch (PDOException $e) {
            error_log("Bulk delete error: " . $e->getMessage());
            $error_message = "Failed to delete selected inquiries. Please try again.";
        }
    } else {
        $error_message = "Please select at least one inquiry to delete.";
    }
}

// Get filters from GET parameters
$search = $_GET['search'] ?? '';
$property_filter = $_GET['property'] ?? '';
$date_filter = $_GET['date'] ?? '';
$sort_by = $_GET['sort'] ?? 'created_at';
$sort_order = $_GET['order'] ?? 'DESC';

// Build the query with filters
$query = "
    SELECT i.*, p.name as property_name, p.location as property_location 
    FROM inquire i 
    INNER JOIN property p ON i.property_id = p.Id 
    WHERE p.created_by = ?
";
$params = [$user_id];

if (!empty($search)) {
    $query .= " AND (i.fullname LIKE ? OR i.email LIKE ? OR i.message LIKE ? OR p.name LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
}

if (!empty($property_filter)) {
    $query .= " AND i.property_id = ?";
    $params[] = $property_filter;
}

if (!empty($date_filter)) {
    switch ($date_filter) {
        case 'today':
            $query .= " AND DATE(i.created_at) = CURDATE()";
            break;
        case 'week':
            $query .= " AND i.created_at >= DATE_SUB(NOW(), INTERVAL 1 WEEK)";
            break;
        case 'month':
            $query .= " AND i.created_at >= DATE_SUB(NOW(), INTERVAL 1 MONTH)";
            break;
        case 'year':
            $query .= " AND i.created_at >= DATE_SUB(NOW(), INTERVAL 1 YEAR)";
            break;
    }
}

// Add sorting
$allowed_sort_columns = ['created_at', 'fullname', 'email', 'property_name'];
$allowed_sort_orders = ['ASC', 'DESC'];

if (in_array($sort_by, $allowed_sort_columns) && in_array(strtoupper($sort_order), $allowed_sort_orders)) {
    $query .= " ORDER BY " . ($sort_by === 'property_name' ? 'p.name' : 'i.' . $sort_by) . " " . strtoupper($sort_order);
} else {
    $query .= " ORDER BY i.created_at DESC";
}

try {
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $inquiries = $stmt->fetchAll();
    
} catch (PDOException $e) {
    error_log("Fetch inquiries error: " . $e->getMessage());
    $inquiries = [];
    $error_message = "Failed to load inquiries.";
}

// Get statistics
try {
    // Total inquiries count
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as total_inquiries 
        FROM inquire i 
        INNER JOIN property p ON i.property_id = p.Id 
        WHERE p.created_by = ?
    ");
    $stmt->execute([$user_id]);
    $total_inquiries = $stmt->fetchColumn();
    
    // Today's inquiries
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as today_inquiries 
        FROM inquire i 
        INNER JOIN property p ON i.property_id = p.Id 
        WHERE p.created_by = ? AND DATE(i.created_at) = CURDATE()
    ");
    $stmt->execute([$user_id]);
    $today_inquiries = $stmt->fetchColumn();
    
    // This week's inquiries
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as week_inquiries 
        FROM inquire i 
        INNER JOIN property p ON i.property_id = p.Id 
        WHERE p.created_by = ? AND i.created_at >= DATE_SUB(NOW(), INTERVAL 1 WEEK)
    ");
    $stmt->execute([$user_id]);
    $week_inquiries = $stmt->fetchColumn();
    
    // This month's inquiries
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as month_inquiries 
        FROM inquire i 
        INNER JOIN property p ON i.property_id = p.Id 
        WHERE p.created_by = ? AND i.created_at >= DATE_SUB(NOW(), INTERVAL 1 MONTH)
    ");
    $stmt->execute([$user_id]);
    $month_inquiries = $stmt->fetchColumn();
    
    // Most inquired properties
    $stmt = $pdo->prepare("
        SELECT p.name, p.location, COUNT(*) as inquiry_count 
        FROM inquire i 
        INNER JOIN property p ON i.property_id = p.Id 
        WHERE p.created_by = ? 
        GROUP BY i.property_id 
        ORDER BY inquiry_count DESC 
        LIMIT 5
    ");
    $stmt->execute([$user_id]);
    $popular_properties = $stmt->fetchAll();
    
    // Get properties for filter dropdown
    $stmt = $pdo->prepare("SELECT Id, name FROM property WHERE created_by = ? ORDER BY name");
    $stmt->execute([$user_id]);
    $properties_list = $stmt->fetchAll();
    
} catch (PDOException $e) {
    error_log("Statistics error: " . $e->getMessage());
    $total_inquiries = 0;
    $today_inquiries = 0;
    $week_inquiries = 0;
    $month_inquiries = 0;
    $popular_properties = [];
    $properties_list = [];
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
    <title>Inquiries Dashboard - Property Management</title>
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
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="bg-gray-50 dark:bg-gray-900 min-h-screen">
    <!-- Navigation -->
    <?php include('include/menu.php'); ?>

    <!-- Main Content -->
    <main class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-8">
            <div>
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-2">Inquiries Dashboard</h1>
                <p class="text-gray-600 dark:text-gray-400">Manage and track property inquiries (<?php echo count($inquiries); ?> total)</p>
            </div>
            <div class="mt-4 sm:mt-0 flex space-x-3">
                <button onclick="exportInquiries()" class="inline-flex items-center px-4 py-2 bg-green-600 hover:bg-green-700 text-white font-medium rounded-lg transition-colors">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    Export CSV
                </button>
                <button id="bulkDeleteBtn" onclick="showBulkDeleteConfirm()" class="inline-flex items-center px-4 py-2 bg-red-600 hover:bg-red-700 text-white font-medium rounded-lg transition-colors opacity-50" disabled>
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                    </svg>
                    Delete Selected
                </button>
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

        <!-- Statistics Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 p-6">
                <div class="flex items-center">
                    <div class="p-3 bg-blue-100 dark:bg-blue-900/20 rounded-lg">
                        <svg class="w-8 h-8 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Total Inquiries</h3>
                        <p class="text-3xl font-bold text-blue-600 dark:text-blue-400"><?php echo number_format($total_inquiries); ?></p>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 p-6">
                <div class="flex items-center">
                    <div class="p-3 bg-green-100 dark:bg-green-900/20 rounded-lg">
                        <svg class="w-8 h-8 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Today</h3>
                        <p class="text-3xl font-bold text-green-600 dark:text-green-400"><?php echo number_format($today_inquiries); ?></p>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 p-6">
                <div class="flex items-center">
                    <div class="p-3 bg-yellow-100 dark:bg-yellow-900/20 rounded-lg">
                        <svg class="w-8 h-8 text-yellow-600 dark:text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">This Week</h3>
                        <p class="text-3xl font-bold text-yellow-600 dark:text-yellow-400"><?php echo number_format($week_inquiries); ?></p>
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 p-6">
                <div class="flex items-center">
                    <div class="p-3 bg-purple-100 dark:bg-purple-900/20 rounded-lg">
                        <svg class="w-8 h-8 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">This Month</h3>
                        <p class="text-3xl font-bold text-purple-600 dark:text-purple-400"><?php echo number_format($month_inquiries); ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Most Inquired Properties -->
        <?php if (!empty($popular_properties)): ?>
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 p-6 mb-8">
            <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-4">Most Inquired Properties</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5 gap-4">
                <?php foreach ($popular_properties as $prop): ?>
                    <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                        <h4 class="font-semibold text-gray-900 dark:text-white text-sm truncate"><?php echo htmlspecialchars($prop['name']); ?></h4>
                        <p class="text-xs text-gray-600 dark:text-gray-400 truncate"><?php echo htmlspecialchars($prop['location']); ?></p>
                        <div class="flex items-center mt-2">
                            <div class="flex-1 bg-gray-200 dark:bg-gray-600 rounded-full h-2">
                                <div class="bg-primary-600 h-2 rounded-full" style="width: <?php echo min(100, ($prop['inquiry_count'] / max(1, $popular_properties[0]['inquiry_count'])) * 100); ?>%"></div>
                            </div>
                            <span class="ml-2 text-sm font-medium text-primary-600 dark:text-primary-400"><?php echo $prop['inquiry_count']; ?></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Filters -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 p-6 mb-8">
            <form method="GET" class="grid grid-cols-1 md:grid-cols-5 gap-4">
                <div>
                    <label for="search" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Search</label>
                    <input 
                        type="text" 
                        id="search" 
                        name="search"
                        value="<?php echo htmlspecialchars($search); ?>"
                        placeholder="Search inquiries..."
                        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400"
                    >
                </div>
                <div>
                    <label for="property" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Property</label>
                    <select 
                        id="property"
                        name="property"
                        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
                    >
                        <option value="">All Properties</option>
                        <?php foreach ($properties_list as $prop): ?>
                            <option value="<?php echo $prop['Id']; ?>" <?php echo $property_filter == $prop['Id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($prop['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label for="date" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Date Range</label>
                    <select 
                        id="date"
                        name="date"
                        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
                    >
                        <option value="">All Time</option>
                        <option value="today" <?php echo $date_filter === 'today' ? 'selected' : ''; ?>>Today</option>
                        <option value="week" <?php echo $date_filter === 'week' ? 'selected' : ''; ?>>This Week</option>
                        <option value="month" <?php echo $date_filter === 'month' ? 'selected' : ''; ?>>This Month</option>
                        <option value="year" <?php echo $date_filter === 'year' ? 'selected' : ''; ?>>This Year</option>
                    </select>
                </div>
                <div>
                    <label for="sort" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Sort By</label>
                    <select 
                        id="sort"
                        name="sort"
                        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-transparent bg-white dark:bg-gray-700 text-gray-900 dark:text-white"
                    >
                        <option value="created_at" <?php echo $sort_by === 'created_at' ? 'selected' : ''; ?>>Date</option>
                        <option value="fullname" <?php echo $sort_by === 'fullname' ? 'selected' : ''; ?>>Name</option>
                        <option value="email" <?php echo $sort_by === 'email' ? 'selected' : ''; ?>>Email</option>
                        <option value="property_name" <?php echo $sort_by === 'property_name' ? 'selected' : ''; ?>>Property</option>
                    </select>
                    <input type="hidden" name="order" value="<?php echo htmlspecialchars($sort_order); ?>">
                </div>
                <div class="flex items-end space-x-2">
                    <button 
                        type="submit"
                        class="flex-1 px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white rounded-lg transition-colors"
                    >
                        Filter
                    </button>
                    <a 
                        href="inquire.php"
                        class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors"
                    >
                        Clear
                    </a>
                </div>
            </form>
        </div>

        <!-- Inquiries Table -->
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
            <?php if (empty($inquiries)): ?>
                <!-- Empty State -->
                <div class="text-center py-12">
                    <div class="w-24 h-24 bg-gray-100 dark:bg-gray-700 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-12 h-12 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path>
                        </svg>
                    </div>
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">
                        <?php echo (!empty($search) || !empty($property_filter) || !empty($date_filter)) ? 'No inquiries found' : 'No inquiries yet'; ?>
                    </h3>
                    <p class="text-gray-600 dark:text-gray-400 mb-6">
                        <?php echo (!empty($search) || !empty($property_filter) || !empty($date_filter)) ? 'Try adjusting your search criteria.' : 'Inquiries will appear here when customers contact you about your properties.'; ?>
                    </p>
                </div>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th class="px-6 py-3 text-left">
                                    <input type="checkbox" id="selectAll" class="rounded border-gray-300 dark:border-gray-600 text-primary-600 focus:ring-primary-500">
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    <a href="?<?php echo http_build_query(array_merge($_GET, ['sort' => 'fullname', 'order' => ($sort_by === 'fullname' && $sort_order === 'ASC') ? 'DESC' : 'ASC'])); ?>" class="flex items-center hover:text-gray-700 dark:hover:text-gray-300">
                                        Contact Info
                                        <?php if ($sort_by === 'fullname'): ?>
                                            <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <?php if ($sort_order === 'ASC'): ?>
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"></path>
                                                <?php else: ?>
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                                <?php endif; ?>
                                            </svg>
                                        <?php endif; ?>
                                    </a>
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    <a href="?<?php echo http_build_query(array_merge($_GET, ['sort' => 'property_name', 'order' => ($sort_by === 'property_name' && $sort_order === 'ASC') ? 'DESC' : 'ASC'])); ?>" class="flex items-center hover:text-gray-700 dark:hover:text-gray-300">
                                        Property
                                        <?php if ($sort_by === 'property_name'): ?>
                                            <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <?php if ($sort_order === 'ASC'): ?>
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"></path>
                                                <?php else: ?>
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                                <?php endif; ?>
                                            </svg>
                                        <?php endif; ?>
                                    </a>
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Message</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    <a href="?<?php echo http_build_query(array_merge($_GET, ['sort' => 'created_at', 'order' => ($sort_by === 'created_at' && $sort_order === 'ASC') ? 'DESC' : 'ASC'])); ?>" class="flex items-center hover:text-gray-700 dark:hover:text-gray-300">
                                        Date
                                        <?php if ($sort_by === 'created_at'): ?>
                                            <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <?php if ($sort_order === 'ASC'): ?>
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"></path>
                                                <?php else: ?>
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                                <?php endif; ?>
                                            </svg>
                                        <?php endif; ?>
                                    </a>
                                </th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                            <?php foreach ($inquiries as $inquiry): ?>
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                                    <td class="px-6 py-4">
                                        <input type="checkbox" name="inquiry_ids[]" value="<?php echo $inquiry['id']; ?>" class="inquiry-checkbox rounded border-gray-300 dark:border-gray-600 text-primary-600 focus:ring-primary-500">
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex flex-col">
                                            <div class="text-sm font-medium text-gray-900 dark:text-white"><?php echo htmlspecialchars($inquiry['fullname']); ?></div>
                                            <div class="text-sm text-gray-500 dark:text-gray-400">
                                                <a href="mailto:<?php echo htmlspecialchars($inquiry['email']); ?>" class="hover:text-primary-600 dark:hover:text-primary-400"><?php echo htmlspecialchars($inquiry['email']); ?></a>
                                            </div>
                                            <?php if (!empty($inquiry['phonenumber'])): ?>
                                                <div class="text-sm text-gray-500 dark:text-gray-400">
                                                    <a href="tel:<?php echo htmlspecialchars($inquiry['phonenumber']); ?>" class="hover:text-primary-600 dark:hover:text-primary-400"><?php echo htmlspecialchars($inquiry['phonenumber']); ?></a>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex flex-col">
                                            <div class="text-sm font-medium text-gray-900 dark:text-white"><?php echo htmlspecialchars($inquiry['property_name']); ?></div>
                                            <div class="text-sm text-gray-500 dark:text-gray-400"><?php echo htmlspecialchars($inquiry['property_location']); ?></div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="text-sm text-gray-900 dark:text-white max-w-xs">
                                            <p class="truncate" title="<?php echo htmlspecialchars($inquiry['message']); ?>">
                                                <?php echo htmlspecialchars(substr($inquiry['message'], 0, 100)) . (strlen($inquiry['message']) > 100 ? '...' : ''); ?>
                                            </p>
                                            <?php if (strlen($inquiry['message']) > 100): ?>
                                                <button onclick="viewInquiry(<?php echo $inquiry['id']; ?>)" class="text-primary-600 dark:text-primary-400 hover:text-primary-800 dark:hover:text-primary-300 text-xs">
                                                    Read more
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="text-sm text-gray-900 dark:text-white">
                                            <?php echo date('M j, Y', strtotime($inquiry['created_at'])); ?>
                                        </div>
                                        <div class="text-sm text-gray-500 dark:text-gray-400">
                                            <?php echo date('g:i A', strtotime($inquiry['created_at'])); ?>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-right text-sm font-medium">
                                        <div class="flex items-center justify-end space-x-2">
                                            <button onclick="viewInquiry(<?php echo $inquiry['id']; ?>)" class="text-primary-600 dark:text-primary-400 hover:text-primary-900 dark:hover:text-primary-300 transition-colors">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                                </svg>
                                            </button>
                                            <a href="mailto:<?php echo htmlspecialchars($inquiry['email']); ?>?subject=Re: <?php echo urlencode($inquiry['property_name']); ?>&body=<?php echo urlencode("Hi {$inquiry['fullname']},\n\nThank you for your inquiry about {$inquiry['property_name']}.\n\nBest regards"); ?>" class="text-green-600 dark:text-green-400 hover:text-green-900 dark:hover:text-green-300 transition-colors">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                                                </svg>
                                            </a>
                                            <button onclick="deleteInquiry(<?php echo $inquiry['id']; ?>, '<?php echo htmlspecialchars(addslashes($inquiry['fullname'])); ?>', '<?php echo htmlspecialchars(addslashes($inquiry['property_name'])); ?>')" class="text-red-600 dark:text-red-400 hover:text-red-900 dark:hover:text-red-300 transition-colors">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                                </svg>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <!-- View Inquiry Modal -->
    <div id="inquiryModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center p-4">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-2xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
            <div class="p-6">
                <div class="flex items-center justify-between mb-6">
                    <h2 class="text-2xl font-bold text-gray-900 dark:text-white" id="modalTitle">Inquiry Details</h2>
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

    <!-- Bulk Delete Confirmation Modal -->
    <div id="bulkDeleteModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center p-4">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-2xl max-w-md w-full">
            <div class="p-6">
                <div class="flex items-center mb-4">
                    <div class="p-3 bg-red-100 dark:bg-red-900/20 rounded-lg mr-4">
                        <svg class="w-6 h-6 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white">Delete Selected Inquiries</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400">This action cannot be undone.</p>
                    </div>
                </div>
                <p class="text-gray-700 dark:text-gray-300 mb-6">
                    Are you sure you want to delete <span id="selectedCount" class="font-medium">0</span> selected inquiries?
                </p>
                <div class="flex justify-end space-x-3">
                    <button onclick="closeBulkDeleteModal()" class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                        Cancel
                    </button>
                    <button onclick="confirmBulkDelete()" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg transition-colors">
                        Delete Selected
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Form -->
    <form id="deleteForm" method="POST" style="display: none;">
        <input type="hidden" name="delete_inquiry" value="1">
        <input type="hidden" name="inquiry_id" id="deleteInquiryId">
    </form>

    <!-- Bulk Delete Form -->
    <form id="bulkDeleteForm" method="POST" style="display: none;">
        <input type="hidden" name="bulk_delete" value="1">
        <div id="bulkDeleteInputs"></div>
    </form>

    <script>
        // Inquiry data for JavaScript
        const inquiries = <?php echo json_encode($inquiries); ?>;
        console.log('Loaded inquiries:', inquiries);

        function viewInquiry(inquiryId) {
            const inquiry = inquiries.find(i => i.id == inquiryId);
            if (!inquiry) {
                console.error('Inquiry not found:', inquiryId);
                return;
            }

            const modal = document.getElementById('inquiryModal');
            const modalTitle = document.getElementById('modalTitle');
            const modalContent = document.getElementById('modalContent');

            modalTitle.textContent = `Inquiry from ${inquiry.fullname}`;

            modalContent.innerHTML = `
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-3">Contact Information</h3>
                        <div class="space-y-3">
                            <div>
                                <span class="text-sm text-gray-600 dark:text-gray-400">Name:</span>
                                <p class="text-gray-900 dark:text-white font-medium">${inquiry.fullname || 'N/A'}</p>
                            </div>
                            <div>
                                <span class="text-sm text-gray-600 dark:text-gray-400">Email:</span>
                                <p class="text-gray-900 dark:text-white">
                                    <a href="mailto:${inquiry.email}" class="text-primary-600 dark:text-primary-400 hover:underline">${inquiry.email || 'N/A'}</a>
                                </p>
                            </div>
                            ${inquiry.phonenumber ? `
                                <div>
                                    <span class="text-sm text-gray-600 dark:text-gray-400">Phone:</span>
                                    <p class="text-gray-900 dark:text-white">
                                        <a href="tel:${inquiry.phonenumber}" class="text-primary-600 dark:text-primary-400 hover:underline">${inquiry.phonenumber}</a>
                                    </p>
                                </div>
                            ` : ''}
                            <div>
                                <span class="text-sm text-gray-600 dark:text-gray-400">Inquiry Date:</span>
                                <p class="text-gray-900 dark:text-white">${new Date(inquiry.created_at).toLocaleString()}</p>
                            </div>
                        </div>
                    </div>
                    
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-3">Property Details</h3>
                        <div class="space-y-3">
                            <div>
                                <span class="text-sm text-gray-600 dark:text-gray-400">Property:</span>
                                <p class="text-gray-900 dark:text-white font-medium">${inquiry.property_name || 'N/A'}</p>
                            </div>
                            <div>
                                <span class="text-sm text-gray-600 dark:text-gray-400">Location:</span>
                                <p class="text-gray-900 dark:text-white">${inquiry.property_location || 'N/A'}</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="mt-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-3">Message</h3>
                    <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                        <p class="text-gray-900 dark:text-white leading-relaxed">${inquiry.message || 'No message provided'}</p>
                    </div>
                </div>
                
                <div class="flex justify-end space-x-3 mt-6 pt-6 border-t border-gray-200 dark:border-gray-700">
                    <button onclick="closeInquiryModal()" class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                        Close
                    </button>
                    <a href="mailto:${inquiry.email}?subject=Re: ${encodeURIComponent(inquiry.property_name)}&body=${encodeURIComponent(`Hi ${inquiry.fullname},\\n\\nThank you for your inquiry about ${inquiry.property_name}.\\n\\nBest regards`)}" class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg transition-colors">
                        Reply via Email
                    </a>
                    <button onclick="deleteInquiry(${inquiry.id}, '${inquiry.fullname.replace(/'/g, "\\'")}', '${inquiry.property_name.replace(/'/g, "\\'")}'); closeInquiryModal();" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg transition-colors">
                        Delete Inquiry
                    </button>
                </div>
            `;

            modal.classList.remove('hidden');
            console.log('Opening modal for inquiry:', inquiry);
        }

        function closeInquiryModal() {
            document.getElementById('inquiryModal').classList.add('hidden');
        }

        function deleteInquiry(inquiryId, contactName, propertyName) {
            if (!confirm(`Are you sure you want to delete the inquiry from "${contactName}" about "${propertyName}"? This action cannot be undone.`)) {
                return;
            }

            document.getElementById('deleteInquiryId').value = inquiryId;
            document.getElementById('deleteForm').submit();
        }

        // Bulk selection functionality
        const selectAllCheckbox = document.getElementById('selectAll');
        const inquiryCheckboxes = document.querySelectorAll('.inquiry-checkbox');
        const bulkDeleteBtn = document.getElementById('bulkDeleteBtn');

        function updateBulkDeleteButton() {
            const selectedCheckboxes = document.querySelectorAll('.inquiry-checkbox:checked');
            const isAnySelected = selectedCheckboxes.length > 0;
            
            bulkDeleteBtn.disabled = !isAnySelected;
            bulkDeleteBtn.classList.toggle('opacity-50', !isAnySelected);
            bulkDeleteBtn.classList.toggle('cursor-not-allowed', !isAnySelected);
        }

        if (selectAllCheckbox) {
            selectAllCheckbox.addEventListener('change', function() {
                inquiryCheckboxes.forEach(checkbox => {
                    checkbox.checked = this.checked;
                });
                updateBulkDeleteButton();
            });
        }

        inquiryCheckboxes.forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                const allChecked = Array.from(inquiryCheckboxes).every(cb => cb.checked);
                const anyChecked = Array.from(inquiryCheckboxes).some(cb => cb.checked);
                
                if (selectAllCheckbox) {
                    selectAllCheckbox.checked = allChecked;
                    selectAllCheckbox.indeterminate = anyChecked && !allChecked;
                }
                
                updateBulkDeleteButton();
            });
        });

        function showBulkDeleteConfirm() {
            const selectedCheckboxes = document.querySelectorAll('.inquiry-checkbox:checked');
            if (selectedCheckboxes.length === 0) return;

            document.getElementById('selectedCount').textContent = selectedCheckboxes.length;
            document.getElementById('bulkDeleteModal').classList.remove('hidden');
        }

        function closeBulkDeleteModal() {
            document.getElementById('bulkDeleteModal').classList.add('hidden');
        }

        function confirmBulkDelete() {
            const selectedCheckboxes = document.querySelectorAll('.inquiry-checkbox:checked');
            const bulkDeleteInputs = document.getElementById('bulkDeleteInputs');
            
            bulkDeleteInputs.innerHTML = '';
            selectedCheckboxes.forEach(checkbox => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'inquiry_ids[]';
                input.value = checkbox.value;
                bulkDeleteInputs.appendChild(input);
            });
            
            document.getElementById('bulkDeleteForm').submit();
        }

        // Export functionality
        function exportInquiries() {
            // Get current URL and add export parameter
            const currentUrl = new URL(window.location);
            currentUrl.searchParams.set('export', 'csv');
            
            // Create a temporary link and click it to trigger download
            const downloadLink = document.createElement('a');
            downloadLink.href = currentUrl.toString();
            downloadLink.style.display = 'none';
            document.body.appendChild(downloadLink);
            downloadLink.click();
            document.body.removeChild(downloadLink);
            
            // Show a brief loading message
            const exportBtn = document.querySelector('button[onclick="exportInquiries()"]');
            const originalText = exportBtn.innerHTML;
            exportBtn.innerHTML = `
                <svg class="w-5 h-5 mr-2 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                </svg>
                Exporting...
            `;
            exportBtn.disabled = true;
            
            // Reset button after 3 seconds
            setTimeout(() => {
                exportBtn.innerHTML = originalText;
                exportBtn.disabled = false;
            }, 3000);
        }

        // Event listeners
        document.getElementById('closeModal').addEventListener('click', closeInquiryModal);

        // Close modals when clicking outside
        document.getElementById('inquiryModal').addEventListener('click', (e) => {
            if (e.target.id === 'inquiryModal') {
                closeInquiryModal();
            }
        });

        document.getElementById('bulkDeleteModal').addEventListener('click', (e) => {
            if (e.target.id === 'bulkDeleteModal') {
                closeBulkDeleteModal();
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

        // Initialize bulk delete button state
        updateBulkDeleteButton();
    </script>
</body>
</html>

