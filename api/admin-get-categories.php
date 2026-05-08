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

try {
    $sql = "SELECT
                c.category_id,
                c.category_name,
                COALESCE(p.product_count, 0) AS product_count,
                COALESCE(pd.product_draft_count, 0) AS product_draft_count,
                COALESCE(al.auction_count, 0) AS auction_count,
                COALESCE(ad.auction_draft_count, 0) AS auction_draft_count
            FROM categories c
            LEFT JOIN (
                SELECT category_id, COUNT(*) AS product_count
                FROM products
                GROUP BY category_id
            ) p ON p.category_id = c.category_id
            LEFT JOIN (
                SELECT category_id, COUNT(*) AS product_draft_count
                FROM product_drafts
                GROUP BY category_id
            ) pd ON pd.category_id = c.category_id
            LEFT JOIN (
                SELECT category_id, COUNT(*) AS auction_count
                FROM auction_listings
                GROUP BY category_id
            ) al ON al.category_id = c.category_id
            LEFT JOIN (
                SELECT category_id, COUNT(*) AS auction_draft_count
                FROM auction_drafts
                GROUP BY category_id
            ) ad ON ad.category_id = c.category_id
            ORDER BY c.category_name ASC, c.category_id ASC";

    $result = $conn->query($sql);
    if (!$result) {
        throw new Exception('Database query failed: ' . $conn->error);
    }

    $categories = [];
    while ($row = $result->fetch_assoc()) {
        $productCount = (int)($row['product_count'] ?? 0);
        $productDraftCount = (int)($row['product_draft_count'] ?? 0);
        $auctionCount = (int)($row['auction_count'] ?? 0);
        $auctionDraftCount = (int)($row['auction_draft_count'] ?? 0);
        $usageCount = $productCount + $productDraftCount + $auctionCount + $auctionDraftCount;

        $categories[] = [
            'category_id' => (int)$row['category_id'],
            'category_name' => (string)($row['category_name'] ?? ''),
            'product_count' => $productCount,
            'product_draft_count' => $productDraftCount,
            'auction_count' => $auctionCount,
            'auction_draft_count' => $auctionDraftCount,
            'usage_count' => $usageCount,
            'is_empty' => $usageCount === 0
        ];
    }

    echo json_encode(['success' => true, 'categories' => $categories]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

$conn->close();
