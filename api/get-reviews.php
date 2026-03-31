<?php
require_once '../dbConnection.php';

header('Content-Type: application/json');

$productId = intval($_GET['product_id'] ?? 0);

if ($productId <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid product ID']);
    exit;
}

try {
    $anonymousColumnExists = false;
    $anonymousColumnCheck = $conn->query("SHOW COLUMNS FROM reviews LIKE 'is_anonymous'");
    if ($anonymousColumnCheck && $anonymousColumnCheck->num_rows > 0) {
        $anonymousColumnExists = true;
    }

    $query = 'SELECT r.review_id, r.rating, r.review_text, r.review_image, r.review_image_type, r.created_at, u.full_name';
    if ($anonymousColumnExists) {
        $query .= ', r.is_anonymous';
    }
    $query .= ' FROM reviews r
              LEFT JOIN users u ON r.user_id = u.user_id
              WHERE r.product_id = ?
              ORDER BY r.created_at DESC
              LIMIT 50';
    
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        throw new Exception('Database prepare error: ' . $conn->error);
    }
    
    $stmt->bind_param('i', $productId);
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
            'user_name' => $isAnonymous ? 'Anonymous User' : ($row['full_name'] ? trim($row['full_name']) : 'Anonymous User'),
            'is_anonymous' => $isAnonymous,
            'rating' => intval($row['rating']),
            'review_text' => trim($row['review_text']),
            'created_at' => $row['created_at'],
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
