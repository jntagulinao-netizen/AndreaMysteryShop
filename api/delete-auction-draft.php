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

$adminUserId = (int)$_SESSION['user_id'];
$draftId = isset($_POST['draft_id']) ? (int)$_POST['draft_id'] : 0;
if ($draftId <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid draft ID']);
    exit;
}

$rootPath = realpath(__DIR__ . '/..') ?: '';

try {
    $mediaStmt = $conn->prepare('SELECT file_path FROM auction_draft_media WHERE draft_id = ?');
    $mediaPaths = [];
    if ($mediaStmt) {
        $mediaStmt->bind_param('i', $draftId);
        $mediaStmt->execute();
        $res = $mediaStmt->get_result();
        while ($row = $res->fetch_assoc()) {
            $path = (string)($row['file_path'] ?? '');
            if ($path !== '') {
                $mediaPaths[] = $path;
            }
        }
        $mediaStmt->close();
    }

    $stmt = $conn->prepare('DELETE FROM auction_drafts WHERE draft_id = ? AND admin_user_id = ?');
    if (!$stmt) {
        throw new Exception('Failed to prepare delete');
    }
    $stmt->bind_param('ii', $draftId, $adminUserId);
    $stmt->execute();
    $deleted = $stmt->affected_rows > 0;
    $stmt->close();

    if (!$deleted) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Draft not found']);
        exit;
    }

    foreach ($mediaPaths as $relativePath) {
        if ($rootPath === '') {
            break;
        }
        $absolutePath = rtrim($rootPath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . ltrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $relativePath), DIRECTORY_SEPARATOR);
        if (is_file($absolutePath)) {
            @unlink($absolutePath);
        }
    }

    echo json_encode(['success' => true]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
