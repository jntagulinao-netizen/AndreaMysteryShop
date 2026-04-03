<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

require __DIR__ . '/../dbConnection.php';

$productId = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
$feature = isset($_POST['feature']) ? intval($_POST['feature']) : 0;
$feature = $feature === 1 ? 1 : 0;

if ($productId <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid product_id']);
    exit;
}

try {
    $conn->begin_transaction();

    $familyStmt = $conn->prepare('SELECT product_id, parent_product_id FROM products WHERE product_id = ? LIMIT 1');
    if (!$familyStmt) {
        throw new Exception('Failed to prepare product lookup');
    }
    $familyStmt->bind_param('i', $productId);
    if (!$familyStmt->execute()) {
        throw new Exception('Failed to fetch product');
    }
    $familyResult = $familyStmt->get_result();
    $selected = $familyResult ? $familyResult->fetch_assoc() : null;
    $familyStmt->close();

    if (!$selected) {
        throw new Exception('Product not found');
    }

    $mainProductId = !empty($selected['parent_product_id']) ? (int)$selected['parent_product_id'] : (int)$selected['product_id'];

    if ($feature === 1) {
        $limitStmt = $conn->prepare('SELECT COUNT(*) AS featured_count FROM products WHERE parent_product_id IS NULL AND featured = 1 AND archived = 0 AND product_id <> ?');
        if (!$limitStmt) {
            throw new Exception('Failed to validate featured limit');
        }
        $limitStmt->bind_param('i', $mainProductId);
        if (!$limitStmt->execute()) {
            throw new Exception('Failed to validate featured limit');
        }
        $limitRes = $limitStmt->get_result();
        $limitRow = $limitRes ? $limitRes->fetch_assoc() : null;
        $limitStmt->close();

        $featuredCount = (int)($limitRow['featured_count'] ?? 0);
        if ($featuredCount >= 3) {
            throw new Exception('Featured products are limited to 3 active product families.');
        }
    }

    $updateStmt = $conn->prepare('UPDATE products SET featured = ? WHERE product_id = ? OR parent_product_id = ?');
    if (!$updateStmt) {
        throw new Exception('Failed to prepare featured update');
    }
    $updateStmt->bind_param('iii', $feature, $mainProductId, $mainProductId);
    if (!$updateStmt->execute()) {
        throw new Exception('Failed to update featured state');
    }
    $affected = $updateStmt->affected_rows;
    $updateStmt->close();

    $conn->commit();

    echo json_encode([
        'success' => true,
        'featured' => $feature,
        'affected_rows' => $affected,
        'main_product_id' => $mainProductId
    ]);
} catch (Throwable $e) {
    $conn->rollback();
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
} finally {
    $conn->close();
}
