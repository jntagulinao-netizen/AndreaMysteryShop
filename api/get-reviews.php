<?php
require_once '../dbConnection.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

function mask_anonymous_name_display($name) {
    $raw = trim((string)$name);
    if ($raw === '') {
        return 'an***s';
    }

    $letters = preg_replace('/[^a-z0-9]/i', '', $raw);
    $letters = strtolower((string)$letters);
    if ($letters === '') {
        $letters = 'user';
    }

    $len = strlen($letters);
    if ($len <= 1) {
        return $letters . '***';
    }
    if ($len === 2) {
        return substr($letters, 0, 1) . '***';
    }
    if ($len === 3) {
        return substr($letters, 0, 1) . '***' . substr($letters, -1);
    }

    return substr($letters, 0, 2) . '***' . substr($letters, -1);
}

$productId = intval($_GET['product_id'] ?? 0);
$includeFamily = isset($_GET['include_family']) && $_GET['include_family'] === '1';

if ($productId <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid product ID']);
    exit;
}

try {
    $currentUserId = intval($_SESSION['user_id'] ?? 0);

    $anonymousColumnExists = false;
    $anonymousColumnCheck = $conn->query("SHOW COLUMNS FROM reviews LIKE 'is_anonymous'");
    if ($anonymousColumnCheck && $anonymousColumnCheck->num_rows > 0) {
        $anonymousColumnExists = true;
    }

    $adminReplyColumnExists = false;
    $adminReplyCheck = $conn->query("SHOW COLUMNS FROM reviews LIKE 'admin_reply'");
    if ($adminReplyCheck && $adminReplyCheck->num_rows > 0) {
        $adminReplyColumnExists = true;
    }

    $adminReplyAtColumnExists = false;
    $adminReplyAtCheck = $conn->query("SHOW COLUMNS FROM reviews LIKE 'admin_reply_at'");
    if ($adminReplyAtCheck && $adminReplyAtCheck->num_rows > 0) {
        $adminReplyAtColumnExists = true;
    }

    $adminReplyByColumnExists = false;
    $adminReplyByCheck = $conn->query("SHOW COLUMNS FROM reviews LIKE 'admin_reply_by'");
    if ($adminReplyByCheck && $adminReplyByCheck->num_rows > 0) {
        $adminReplyByColumnExists = true;
    } else {
        $conn->query("ALTER TABLE reviews ADD COLUMN admin_reply_by INT NULL AFTER admin_reply_at");
        $adminReplyByCheck = $conn->query("SHOW COLUMNS FROM reviews LIKE 'admin_reply_by'");
        if ($adminReplyByCheck && $adminReplyByCheck->num_rows > 0) {
            $adminReplyByColumnExists = true;
        }
    }

    $targetProductIds = [$productId];
    if ($includeFamily) {
        $familyRootId = $productId;
        $familyRootStmt = $conn->prepare('SELECT product_id, parent_product_id FROM products WHERE product_id = ? LIMIT 1');
        if ($familyRootStmt) {
            $familyRootStmt->bind_param('i', $productId);
            $familyRootStmt->execute();
            $familyRootResult = $familyRootStmt->get_result();
            if ($familyRootResult && ($familyRow = $familyRootResult->fetch_assoc())) {
                $parentId = isset($familyRow['parent_product_id']) ? intval($familyRow['parent_product_id']) : 0;
                if ($parentId > 0) {
                    $familyRootId = $parentId;
                }
            }
            $familyRootStmt->close();
        }

        $familyIds = [];
        $familyIdsStmt = $conn->prepare('SELECT product_id FROM products WHERE product_id = ? OR parent_product_id = ?');
        if ($familyIdsStmt) {
            $familyIdsStmt->bind_param('ii', $familyRootId, $familyRootId);
            $familyIdsStmt->execute();
            $familyIdsResult = $familyIdsStmt->get_result();
            while ($familyIdsResult && ($familyIdRow = $familyIdsResult->fetch_assoc())) {
                $pid = intval($familyIdRow['product_id'] ?? 0);
                if ($pid > 0) {
                    $familyIds[] = $pid;
                }
            }
            $familyIdsStmt->close();
        }

        if (!empty($familyIds)) {
            $targetProductIds = array_values(array_unique($familyIds));
        }
    }

    $query = 'SELECT r.review_id, r.product_id, r.user_id, r.rating, r.review_text, r.review_image, r.review_image_type, r.created_at, u.full_name';
    if ($anonymousColumnExists) {
        $query .= ', r.is_anonymous';
    }
    if ($adminReplyColumnExists) {
        $query .= ', r.admin_reply';
    }
    if ($adminReplyAtColumnExists) {
        $query .= ', r.admin_reply_at';
    }
    if ($adminReplyByColumnExists) {
        $query .= ', r.admin_reply_by';
        $query .= ', COALESCE(NULLIF(TRIM(admin_user.full_name), ""), "Admin") AS admin_reply_by_name';
    }
    $query .= ' FROM reviews r
              LEFT JOIN users u ON r.user_id = u.user_id
              LEFT JOIN users admin_user ON admin_user.user_id = r.admin_reply_by
              WHERE r.product_id IN (' . implode(',', array_fill(0, count($targetProductIds), '?')) . ')
              ORDER BY r.created_at DESC
              LIMIT 50';
    
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        throw new Exception('Database prepare error: ' . $conn->error);
    }
    
    $types = str_repeat('i', count($targetProductIds));
    $stmt->bind_param($types, ...$targetProductIds);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $rows = [];
    $reviewIds = [];
    while ($row = $result->fetch_assoc()) {
        $rows[] = $row;
        $reviewIds[] = intval($row['review_id']);
    }
    $stmt->close();

    $mediaByReview = [];
    $mediaTableExists = false;
    $tableCheck = $conn->query("SHOW TABLES LIKE 'review_media_files'");
    if ($tableCheck && $tableCheck->num_rows > 0) {
        $mediaTableExists = true;
    }

    if ($mediaTableExists && !empty($reviewIds)) {
        $placeholders = implode(',', array_fill(0, count($reviewIds), '?'));
        $mediaSql = "SELECT media_id, review_id, file_path, media_type, file_size FROM review_media_files WHERE review_id IN ($placeholders) ORDER BY media_id ASC";
        $mediaStmt = $conn->prepare($mediaSql);
        if ($mediaStmt) {
            $types = str_repeat('i', count($reviewIds));
            $mediaStmt->bind_param($types, ...$reviewIds);
            $mediaStmt->execute();
            $mediaResult = $mediaStmt->get_result();
            while ($mediaRow = $mediaResult->fetch_assoc()) {
                $rid = intval($mediaRow['review_id']);
                if (!isset($mediaByReview[$rid])) {
                    $mediaByReview[$rid] = [];
                }
                $mediaByReview[$rid][] = [
                    'media_id' => intval($mediaRow['media_id']),
                    'media_type' => $mediaRow['media_type'],
                    'file_size' => intval($mediaRow['file_size']),
                    'url' => 'api/get-review-media.php?media_id=' . intval($mediaRow['media_id'])
                ];
            }
            $mediaStmt->close();
        }
    }

    $reviews = [];
    foreach ($rows as $row) {
        $reviewId = intval($row['review_id']);
        $isAnonymous = isset($row['is_anonymous']) ? intval($row['is_anonymous']) === 1 : false;
        $rawName = $row['full_name'] ? trim((string)$row['full_name']) : 'Anonymous User';
        $mediaFiles = $mediaByReview[$reviewId] ?? [];

        if (empty($mediaFiles) && !empty($row['review_image']) && !empty($row['review_image_type'])) {
            $mediaFiles[] = [
                'media_id' => null,
                'media_type' => $row['review_image_type'],
                'file_size' => null,
                'url' => 'api/get-review-media.php?review_id=' . $reviewId
            ];
        }

        $hasMedia = !empty($mediaFiles);
        $reviews[] = [
            'review_id' => $reviewId,
            'product_id' => intval($row['product_id'] ?? 0),
            'user_id' => intval($row['user_id'] ?? 0),
            'is_mine' => $currentUserId > 0 && intval($row['user_id'] ?? 0) === $currentUserId,
            'user_name' => $isAnonymous ? mask_anonymous_name_display($rawName) : $rawName,
            'is_anonymous' => $isAnonymous,
            'rating' => intval($row['rating']),
            'review_text' => trim($row['review_text']),
            'created_at' => $row['created_at'],
            'admin_reply' => isset($row['admin_reply']) ? trim((string)$row['admin_reply']) : '',
            'admin_reply_at' => $row['admin_reply_at'] ?? null,
            'admin_reply_by' => isset($row['admin_reply_by']) ? intval($row['admin_reply_by']) : null,
            'admin_reply_by_name' => isset($row['admin_reply_by_name']) ? trim((string)$row['admin_reply_by_name']) : null,
            'has_media' => $hasMedia,
            'media_type' => $hasMedia ? ($mediaFiles[0]['media_type'] ?? null) : null,
            'media_files' => $mediaFiles
        ];
    }
    
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'reviews' => $reviews,
        'count' => count($reviews)
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error fetching reviews: ' . $e->getMessage()]);
}
?>
