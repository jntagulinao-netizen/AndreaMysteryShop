<?php
header('Content-Type: application/json; charset=utf-8');
session_start();
require __DIR__ . '/../dbConnection.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$role = $_SESSION['user_role'] ?? 'user';
if ($role !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Admin access required']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

$action = trim((string)($_POST['action'] ?? ''));
$categoryId = isset($_POST['category_id']) ? (int)$_POST['category_id'] : 0;
$newCategoryName = trim((string)($_POST['new_category_name'] ?? ''));

if ($categoryId <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid category ID']);
    exit;
}

function fetchCategoryUsage(mysqli $conn, int $categoryId): array {
    $counts = [
        'product_count' => 0,
        'product_draft_count' => 0,
        'auction_count' => 0,
        'auction_draft_count' => 0,
    ];

    $queries = [
        'product_count' => 'SELECT COUNT(*) AS count_value FROM products WHERE category_id = ?',
        'product_draft_count' => 'SELECT COUNT(*) AS count_value FROM product_drafts WHERE category_id = ?',
        'auction_count' => 'SELECT COUNT(*) AS count_value FROM auction_listings WHERE category_id = ?',
        'auction_draft_count' => 'SELECT COUNT(*) AS count_value FROM auction_drafts WHERE category_id = ?',
    ];

    foreach ($queries as $key => $sql) {
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            throw new Exception('Failed to check category usage');
        }
        $stmt->bind_param('i', $categoryId);
        $stmt->execute();
        $res = $stmt->get_result();
        $row = $res ? $res->fetch_assoc() : null;
        $counts[$key] = (int)($row['count_value'] ?? 0);
        $stmt->close();
    }

    return $counts;
}

try {
    $lookupStmt = $conn->prepare('SELECT category_id, category_name FROM categories WHERE category_id = ? LIMIT 1');
    if (!$lookupStmt) {
        throw new Exception('Failed to load category');
    }
    $lookupStmt->bind_param('i', $categoryId);
    $lookupStmt->execute();
    $lookupRes = $lookupStmt->get_result();
    $category = $lookupRes ? $lookupRes->fetch_assoc() : null;
    $lookupStmt->close();

    if (!$category) {
        throw new Exception('Category not found');
    }

    if ($action === 'rename') {
        if ($newCategoryName === '') {
            throw new Exception('Category name is required');
        }
        if (strlen($newCategoryName) < 4) {
            throw new Exception('Category name must be at least 4 characters');
        }

        $duplicateStmt = $conn->prepare('SELECT category_id FROM categories WHERE LOWER(category_name) = LOWER(?) AND category_id <> ? LIMIT 1');
        if (!$duplicateStmt) {
            throw new Exception('Failed to validate category name');
        }
        $duplicateStmt->bind_param('si', $newCategoryName, $categoryId);
        $duplicateStmt->execute();
        $duplicateRes = $duplicateStmt->get_result();
        $duplicate = $duplicateRes ? $duplicateRes->fetch_assoc() : null;
        $duplicateStmt->close();

        if ($duplicate) {
            throw new Exception('Another category already uses that name');
        }

        $updateStmt = $conn->prepare('UPDATE categories SET category_name = ? WHERE category_id = ?');
        if (!$updateStmt) {
            throw new Exception('Failed to prepare category update');
        }
        $updateStmt->bind_param('si', $newCategoryName, $categoryId);
        $updateStmt->execute();
        $updateStmt->close();

        echo json_encode([
            'success' => true,
            'message' => 'Category name updated successfully'
        ]);
        exit;
    }

    if ($action === 'delete') {
        $usage = fetchCategoryUsage($conn, $categoryId);
        $usageCount = array_sum($usage);
        if ($usageCount > 0) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => 'Category is not empty and cannot be deleted',
                'usage' => $usage
            ]);
            exit;
        }

        $deleteStmt = $conn->prepare('DELETE FROM categories WHERE category_id = ?');
        if (!$deleteStmt) {
            throw new Exception('Failed to prepare category delete');
        }
        $deleteStmt->bind_param('i', $categoryId);
        $deleteStmt->execute();
        $deleteStmt->close();

        echo json_encode([
            'success' => true,
            'message' => 'Category deleted successfully'
        ]);
        exit;
    }

    throw new Exception('Invalid action');
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
