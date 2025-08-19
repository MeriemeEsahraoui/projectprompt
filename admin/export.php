<?php
// Include database configuration
require_once 'include/config.php';

// Get database connection
$pdo = getDatabaseConnection();

// Check if user is logged in
if (!isset($_SESSION['is_logged_in']) || $_SESSION['is_logged_in'] !== true) {
    header('HTTP/1.1 401 Unauthorized');
    echo json_encode(['error' => 'Unauthorized access']);
    exit;
}

// Get user information from session
$user_id = $_SESSION['user_id'] ?? 0;

// Get export type from request
$export_type = $_GET['type'] ?? 'properties';
$format = $_GET['format'] ?? 'csv';

// Validate export type
$allowed_types = ['properties', 'inquiries', 'all'];
if (!in_array($export_type, $allowed_types)) {
    header('HTTP/1.1 400 Bad Request');
    echo json_encode(['error' => 'Invalid export type']);
    exit;
}

// Validate format
$allowed_formats = ['csv', 'json'];
if (!in_array($format, $allowed_formats)) {
    header('HTTP/1.1 400 Bad Request');
    echo json_encode(['error' => 'Invalid export format']);
    exit;
}

try {
    $data = [];
    
    if ($export_type === 'properties' || $export_type === 'all') {
        // Get properties data
        $stmt = $pdo->prepare("
            SELECT 
                p.Id as property_id,
                p.name,
                p.description,
                p.location,
                p.price,
                p.type,
                p.number_of_bedrooms,
                p.number_of_bathrooms,
                p.property_image,
                p.created_at,
                CASE 
                    WHEN p.status = 0 THEN 'Available'
                    WHEN p.status = 1 THEN 'Rented'
                    WHEN p.status = 2 THEN 'Maintenance'
                    ELSE 'Unknown'
                END as status,
                p.map
            FROM property p 
            WHERE p.created_by = ? 
            ORDER BY p.created_at DESC
        ");
        $stmt->execute([$user_id]);
        $properties = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if ($export_type === 'properties') {
            $data = $properties;
        } else {
            $data['properties'] = $properties;
        }
    }
    
    if ($export_type === 'inquiries' || $export_type === 'all') {
        // Get inquiries data for user's properties
        $stmt = $pdo->prepare("
            SELECT 
                i.id as inquiry_id,
                i.fullname,
                i.email,
                i.phonenumber,
                i.message,
                i.created_at as inquiry_date,
                p.name as property_name,
                p.Id as property_id,
                p.location as property_location,
                p.price as property_price
            FROM inquire i 
            INNER JOIN property p ON i.property_id = p.Id 
            WHERE p.created_by = ? 
            ORDER BY i.created_at DESC
        ");
        $stmt->execute([$user_id]);
        $inquiries = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if ($export_type === 'inquiries') {
            $data = $inquiries;
        } else {
            $data['inquiries'] = $inquiries;
        }
    }
    
    // Generate filename with timestamp
    $timestamp = date('Y-m-d_H-i-s');
    $filename = "property_export_{$export_type}_{$timestamp}";
    
    if ($format === 'csv') {
        // Set CSV headers
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '.csv"');
        header('Pragma: no-cache');
        header('Expires: 0');
        
        $output = fopen('php://output', 'w');
        
        if ($export_type === 'all') {
            // Export properties first
            if (!empty($data['properties'])) {
                fputcsv($output, ['=== PROPERTIES ===']);
                fputcsv($output, array_keys($data['properties'][0]));
                foreach ($data['properties'] as $row) {
                    fputcsv($output, $row);
                }
                fputcsv($output, []); // Empty row
            }
            
            // Export inquiries
            if (!empty($data['inquiries'])) {
                fputcsv($output, ['=== INQUIRIES ===']);
                fputcsv($output, array_keys($data['inquiries'][0]));
                foreach ($data['inquiries'] as $row) {
                    fputcsv($output, $row);
                }
            }
        } else {
            // Single data type export
            if (!empty($data)) {
                fputcsv($output, array_keys($data[0]));
                foreach ($data as $row) {
                    fputcsv($output, $row);
                }
            }
        }
        
        fclose($output);
        
    } elseif ($format === 'json') {
        // Set JSON headers
        header('Content-Type: application/json');
        header('Content-Disposition: attachment; filename="' . $filename . '.json"');
        header('Pragma: no-cache');
        header('Expires: 0');
        
        // Add metadata
        $export_data = [
            'export_info' => [
                'type' => $export_type,
                'format' => $format,
                'exported_at' => date('Y-m-d H:i:s'),
                'user_id' => $user_id,
                'total_records' => $export_type === 'all' ? 
                    (count($data['properties'] ?? []) + count($data['inquiries'] ?? [])) : 
                    count($data)
            ],
            'data' => $data
        ];
        
        echo json_encode($export_data, JSON_PRETTY_PRINT);
    }
    
} catch (PDOException $e) {
    error_log("Export error: " . $e->getMessage());
    header('HTTP/1.1 500 Internal Server Error');
    echo json_encode(['error' => 'Failed to export data']);
}
?>