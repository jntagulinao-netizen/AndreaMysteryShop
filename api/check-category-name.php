<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

session_start();
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

$role = $_SESSION['user_role'] ?? 'user';
if ($role !== 'admin') {
    echo json_encode(['success' => false, 'error' => 'Not authorized']);
    exit;
}

require '../dbConnection.php';

$categoryName = trim($_GET['category_name'] ?? '');

if (empty($categoryName)) {
    echo json_encode(['success' => false, 'error' => 'Category name is required']);
    exit;
}

if (strlen($categoryName) < 4) {
    echo json_encode(['success' => false, 'error' => 'Category name must be at least 4 characters']);
    exit;
}

try {
    $stmt = $conn->prepare('SELECT category_id FROM categories WHERE LOWER(category_name) = LOWER(?) LIMIT 1');
    if (!$stmt) {
        throw new Exception('Database error');
    }
    
    $stmt->bind_param('s', $categoryName);
    $stmt->execute();
    $result = $stmt->get_result();
    $exists = $result->num_rows > 0;
    $stmt->close();
    
    echo json_encode([
        'success' => true,
        'exists' => $exists,
        'category_name' => $categoryName
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
