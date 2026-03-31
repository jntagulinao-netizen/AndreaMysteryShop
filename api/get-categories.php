<?php
header('Content-Type: application/json');
session_start();

require_once '../dbConnection.php';

try {
    // Fetch categories with product count and total stock
    // Group by category_name to avoid duplicates when same name exists with multiple IDs
    $query = "SELECT 
                c.category_name,
                COUNT(DISTINCT p.product_id) as product_count,
                COALESCE(SUM(p.product_stock), 0) as total_stock,
                MIN(c.category_id) as category_id
              FROM categories c
              LEFT JOIN products p ON c.category_id = p.category_id
              GROUP BY c.category_name
              ORDER BY c.category_name ASC";
    
    $result = $conn->query($query);
    
    if (!$result) {
        throw new Exception("Database query failed: " . $conn->error);
    }
    
    $categories = [];
    while ($row = $result->fetch_assoc()) {
        $categories[] = [
            'category_id' => $row['category_id'],
            'category_name' => $row['category_name'],
            'product_count' => (int)$row['product_count'],
            'total_stock' => (int)$row['total_stock']
        ];
    }
    
    echo json_encode([
        'success' => true,
        'categories' => $categories
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

$conn->close();
?>
