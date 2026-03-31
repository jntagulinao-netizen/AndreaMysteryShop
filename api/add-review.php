<?php
session_start();
require_once '../dbConnection.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Please log in to leave a review']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$contentType = $_SERVER['CONTENT_TYPE'] ?? '';
$isMultipart = stripos($contentType, 'multipart/form-data') !== false;
$input = [];

if (!$isMultipart) {
    $input = json_decode(file_get_contents('php://input'), true) ?? [];
}

$productId = intval($isMultipart ? ($_POST['product_id'] ?? 0) : ($input['product_id'] ?? 0));
$rating = intval($isMultipart ? ($_POST['rating'] ?? 0) : ($input['rating'] ?? 0));
$reviewText = trim($isMultipart ? ($_POST['review_text'] ?? '') : ($input['review_text'] ?? ''));
$isAnonymous = intval($isMultipart ? ($_POST['is_anonymous'] ?? 0) : ($input['is_anonymous'] ?? 0)) ? 1 : 0;
$legacyImageData = $input['image_data'] ?? null;
$userId = intval($_SESSION['user_id']);

if ($productId <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid product ID']);
    exit;
}

if ($rating < 1 || $rating > 5) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Rating must be between 1 and 5']);
    exit;
}

if (empty($reviewText) || strlen($reviewText) < 10) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Review must be at least 10 characters']);
    exit;
}

if (strlen($reviewText) > 1000) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Review must be less than 1000 characters']);
    exit;
}

$maxTotalBytes = 25 * 1024 * 1024;
$allowedImageMimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
$allowedVideoMimes = ['video/mp4', 'video/webm', 'video/quicktime', 'video/x-msvideo', 'video/x-matroska'];
$allowedMimes = array_merge($allowedImageMimes, $allowedVideoMimes);
$reviewMediaFiles = [];

if ($isMultipart && isset($_FILES['review_media'])) {
    $files = $_FILES['review_media'];
    $count = is_array($files['name']) ? count($files['name']) : 0;
    $totalBytes = 0;
    $finfo = finfo_open(FILEINFO_MIME_TYPE);

    for ($i = 0; $i < $count; $i++) {
        if ($files['error'][$i] === UPLOAD_ERR_NO_FILE) {
            continue;
        }
        if ($files['error'][$i] !== UPLOAD_ERR_OK) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Upload failed for one or more files']);
            exit;
        }

        $tmpPath = $files['tmp_name'][$i];
        $size = intval($files['size'][$i] ?? 0);
        $mimeType = finfo_file($finfo, $tmpPath) ?: ($files['type'][$i] ?? 'application/octet-stream');

        if (!in_array($mimeType, $allowedMimes, true)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid file type. Only images and videos are allowed']);
            exit;
        }

        $totalBytes += $size;
        if ($totalBytes > $maxTotalBytes) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Total upload size exceeds 25MB limit']);
            exit;
        }

        $reviewMediaFiles[] = [
            'tmp_path' => $tmpPath,
            'mime' => $mimeType,
            'size' => $size
        ];
    }

    if ($finfo) {
        finfo_close($finfo);
    }
}

// Backward compatibility for existing JSON base64 upload path
if (!$isMultipart && !empty($legacyImageData)) {
    if (preg_match('/^data:([a-zA-Z0-9\/\+.-]+);base64,(.+)$/', $legacyImageData, $matches)) {
        $mimeType = $matches[1];
        $base64Data = $matches[2];

        if (!in_array($mimeType, $allowedMimes, true)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid file type. Only images and videos are allowed']);
            exit;
        }

        $binary = base64_decode($base64Data, true);
        if ($binary === false) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid media payload']);
            exit;
        }

        if (strlen($binary) > $maxTotalBytes) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Total upload size exceeds 25MB limit']);
            exit;
        }

        $reviewMediaFiles[] = [
            'binary' => $binary,
            'mime' => $mimeType,
            'size' => strlen($binary)
        ];
    }
}

try {
    $productCheck = $conn->prepare('SELECT product_id FROM products WHERE product_id = ?');
    $productCheck->bind_param('i', $productId);
    $productCheck->execute();
    $productResult = $productCheck->get_result();
    if ($productResult->num_rows === 0) {
        throw new Exception('Product not found');
    }
    $productCheck->close();

    $indexCheck = $conn->query("SHOW INDEX FROM reviews WHERE Key_name='unique_user_product'");
    if ($indexCheck && $indexCheck->num_rows > 0) {
        $conn->query('ALTER TABLE reviews DROP INDEX unique_user_product');
    }

    $conn->query('CREATE TABLE IF NOT EXISTS review_media_files (
        media_id INT AUTO_INCREMENT PRIMARY KEY,
        review_id INT NOT NULL,
        file_path VARCHAR(255) NOT NULL,
        media_type VARCHAR(64) NOT NULL,
        file_size INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        CONSTRAINT fk_review_media_review FOREIGN KEY (review_id)
            REFERENCES reviews(review_id)
            ON DELETE CASCADE,
        INDEX idx_review_media_review_id (review_id)
    )');

    $anonymousColumnExists = false;
    $anonymousColumnCheck = $conn->query("SHOW COLUMNS FROM reviews LIKE 'is_anonymous'");
    if ($anonymousColumnCheck && $anonymousColumnCheck->num_rows > 0) {
        $anonymousColumnExists = true;
    } else {
        // Keep schema self-healing for deployments that have older review tables.
        $conn->query('ALTER TABLE reviews ADD COLUMN is_anonymous TINYINT(1) NOT NULL DEFAULT 0');
        $anonymousColumnCheck = $conn->query("SHOW COLUMNS FROM reviews LIKE 'is_anonymous'");
        if ($anonymousColumnCheck && $anonymousColumnCheck->num_rows > 0) {
            $anonymousColumnExists = true;
        }
    }

    $nullBlob = null;
    $nullType = null;
    if ($anonymousColumnExists) {
        $insertReview = $conn->prepare('INSERT INTO reviews (user_id, product_id, rating, review_text, review_image, review_image_type, is_anonymous, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())');
        $insertReview->bind_param('iiisssi', $userId, $productId, $rating, $reviewText, $nullBlob, $nullType, $isAnonymous);
    } else {
        $insertReview = $conn->prepare('INSERT INTO reviews (user_id, product_id, rating, review_text, review_image, review_image_type, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())');
        $insertReview->bind_param('iiisss', $userId, $productId, $rating, $reviewText, $nullBlob, $nullType);
    }
    if (!$insertReview->execute()) {
        if ($insertReview->errno === 1062) {
            $conn->query('ALTER TABLE reviews DROP INDEX IF EXISTS unique_user_product');
            $insertReview->close();
            if ($anonymousColumnExists) {
                $insertReview = $conn->prepare('INSERT INTO reviews (user_id, product_id, rating, review_text, review_image, review_image_type, is_anonymous, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())');
                $insertReview->bind_param('iiisssi', $userId, $productId, $rating, $reviewText, $nullBlob, $nullType, $isAnonymous);
            } else {
                $insertReview = $conn->prepare('INSERT INTO reviews (user_id, product_id, rating, review_text, review_image, review_image_type, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())');
                $insertReview->bind_param('iiisss', $userId, $productId, $rating, $reviewText, $nullBlob, $nullType);
            }
            $insertReview->execute();
        }
    }

    $reviewId = intval($conn->insert_id);
    $insertReview->close();

    if ($reviewId > 0 && !empty($reviewMediaFiles)) {
        $projectRoot = dirname(__DIR__);
        $imagesDir = $projectRoot . DIRECTORY_SEPARATOR . 'user_review_media' . DIRECTORY_SEPARATOR . 'images';
        $videosDir = $projectRoot . DIRECTORY_SEPARATOR . 'user_review_media' . DIRECTORY_SEPARATOR . 'videos';

        if (!is_dir($imagesDir)) {
            mkdir($imagesDir, 0775, true);
        }
        if (!is_dir($videosDir)) {
            mkdir($videosDir, 0775, true);
        }

        $insertMedia = $conn->prepare('INSERT INTO review_media_files (review_id, file_path, media_type, file_size) VALUES (?, ?, ?, ?)');
        $firstPath = null;
        $firstType = null;

        $extMap = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/webp' => 'webp',
            'video/mp4' => 'mp4',
            'video/webm' => 'webm',
            'video/quicktime' => 'mov',
            'video/x-msvideo' => 'avi',
            'video/x-matroska' => 'mkv'
        ];

        foreach ($reviewMediaFiles as $media) {
            $mimeType = $media['mime'];
            $isVideo = strpos($mimeType, 'video/') === 0;
            $targetDir = $isVideo ? $videosDir : $imagesDir;
            $ext = $extMap[$mimeType] ?? ($isVideo ? 'mp4' : 'bin');
            $fileName = 'review_' . $reviewId . '_' . uniqid('', true) . '.' . $ext;
            $absolutePath = $targetDir . DIRECTORY_SEPARATOR . $fileName;
            $relativePath = 'user_review_media/' . ($isVideo ? 'videos/' : 'images/') . $fileName;

            $saved = false;
            if (isset($media['tmp_path'])) {
                $saved = move_uploaded_file($media['tmp_path'], $absolutePath);
            } elseif (isset($media['binary'])) {
                $saved = file_put_contents($absolutePath, $media['binary']) !== false;
            }

            if (!$saved) {
                continue;
            }

            if ($firstPath === null) {
                $firstPath = $relativePath;
                $firstType = $mimeType;
            }

            $size = intval($media['size'] ?? 0);
            $insertMedia->bind_param('issi', $reviewId, $relativePath, $mimeType, $size);
            $insertMedia->execute();
        }

        $insertMedia->close();

        if ($firstPath !== null) {
            $legacyUpdate = $conn->prepare('UPDATE reviews SET review_image = ?, review_image_type = ? WHERE review_id = ?');
            $legacyUpdate->bind_param('ssi', $firstPath, $firstType, $reviewId);
            $legacyUpdate->execute();
            $legacyUpdate->close();
        }
    }

    $avgRating = $conn->prepare('SELECT AVG(rating) as avg_rating FROM reviews WHERE product_id = ?');
    $avgRating->bind_param('i', $productId);
    $avgRating->execute();
    $result = $avgRating->get_result();
    $row = $result->fetch_assoc();
    $averageRating = round($row['avg_rating'] ?? 0, 2);
    $avgRating->close();

    $updateProduct = $conn->prepare('UPDATE products SET average_rating = ? WHERE product_id = ?');
    $updateProduct->bind_param('di', $averageRating, $productId);
    $updateProduct->execute();
    $updateProduct->close();

    $updateOrderStatus = $conn->prepare(
        'UPDATE orders 
         SET status = "reviewed" 
         WHERE user_id = ? AND order_id IN (
             SELECT DISTINCT o.order_id 
             FROM orders o
             INNER JOIN order_items oi ON o.order_id = oi.order_id
             WHERE o.user_id = ? AND oi.product_id = ? AND o.status IN ("delivered", "received")
         )'
    );
    $updateOrderStatus->bind_param('iii', $userId, $userId, $productId);
    $updateOrderStatus->execute();
    $updateOrderStatus->close();

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'Review submitted successfully',
        'average_rating' => $averageRating,
        'review_id' => $reviewId
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>
