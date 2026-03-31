<?php
require_once '../dbConnection.php';

$reviewId = intval($_GET['review_id'] ?? 0);
$mediaId = intval($_GET['media_id'] ?? 0);

function serveFileMedia($absolutePath, $mimeType = null) {
    if (!file_exists($absolutePath) || !is_file($absolutePath)) {
        http_response_code(404);
        echo 'Media file not found';
        exit;
    }

    if (empty($mimeType)) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = $finfo ? finfo_file($finfo, $absolutePath) : 'application/octet-stream';
        if ($finfo) {
            finfo_close($finfo);
        }
    }

    header('Content-Type: ' . $mimeType);
    header('Content-Length: ' . filesize($absolutePath));
    header('Cache-Control: public, max-age=31536000');
    readfile($absolutePath);
    exit;
}

function tryServeRelativePath($relativePath, $mimeType = null) {
    if (empty($relativePath) || strpos($relativePath, '..') !== false) {
        return false;
    }
    $projectRoot = realpath(dirname(__DIR__));
    $absolutePath = realpath($projectRoot . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $relativePath));
    if (!$projectRoot || !$absolutePath) {
        return false;
    }
    if (stripos($absolutePath, $projectRoot) !== 0) {
        return false;
    }
    serveFileMedia($absolutePath, $mimeType);
    return true;
}

if ($mediaId > 0) {
    $tableCheck = $conn->query("SHOW TABLES LIKE 'review_media_files'");
    if ($tableCheck && $tableCheck->num_rows > 0) {
        $stmt = $conn->prepare('SELECT file_path, media_type FROM review_media_files WHERE media_id = ? LIMIT 1');
        $stmt->bind_param('i', $mediaId);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();

        if ($row && tryServeRelativePath($row['file_path'], $row['media_type'])) {
            exit;
        }
    }
}

if ($reviewId > 0) {
    $tableCheck = $conn->query("SHOW TABLES LIKE 'review_media_files'");
    if ($tableCheck && $tableCheck->num_rows > 0) {
        $stmt = $conn->prepare('SELECT file_path, media_type FROM review_media_files WHERE review_id = ? ORDER BY media_id ASC LIMIT 1');
        $stmt->bind_param('i', $reviewId);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();

        if ($row && tryServeRelativePath($row['file_path'], $row['media_type'])) {
            exit;
        }
    }

    $stmt = $conn->prepare('SELECT review_image, review_image_type FROM reviews WHERE review_id = ? LIMIT 1');
    $stmt->bind_param('i', $reviewId);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();

    if (!$row || empty($row['review_image']) || empty($row['review_image_type'])) {
        http_response_code(404);
        echo 'No media found';
        exit;
    }

    $legacyValue = $row['review_image'];
    $legacyType = $row['review_image_type'];

    if (is_string($legacyValue) && strpos($legacyValue, 'user_review_media/') === 0) {
        if (tryServeRelativePath($legacyValue, $legacyType)) {
            exit;
        }
    }

    header('Content-Type: ' . $legacyType);
    header('Cache-Control: public, max-age=31536000');
    echo $legacyValue;
    exit;
}

http_response_code(400);
echo 'Invalid media request';
?>