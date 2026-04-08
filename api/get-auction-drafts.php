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

$adminUserId = (int)$_SESSION['user_id'];
$draftId = isset($_GET['draft_id']) ? (int)$_GET['draft_id'] : 0;

function fetchMedia(mysqli $conn, int $draftId): array {
    $stmt = $conn->prepare('SELECT media_type, file_path, sort_order, is_pinned FROM auction_draft_media WHERE draft_id = ? ORDER BY media_type ASC, sort_order ASC, media_id ASC');
    if (!$stmt) {
        return ['images' => [], 'video' => ''];
    }

    $stmt->bind_param('i', $draftId);
    $stmt->execute();
    $res = $stmt->get_result();

    $images = [];
    $video = '';

    while ($row = $res->fetch_assoc()) {
        $type = (string)($row['media_type'] ?? '');
        $path = (string)($row['file_path'] ?? '');
        if ($path === '') {
            continue;
        }

        if ($type === 'image') {
            $images[] = [
                'path' => $path,
                'is_pinned' => (int)($row['is_pinned'] ?? 0) === 1
            ];
        } elseif ($type === 'video' && $video === '') {
            $video = $path;
        }
    }

    $stmt->close();

    return ['images' => $images, 'video' => $video];
}

try {
    if ($draftId > 0) {
        $stmt = $conn->prepare('SELECT draft_id, category_id, item_name, item_description, condition_grade, starting_bid, reserve_price, bid_increment, start_at, end_at, draft_status, created_at, updated_at FROM auction_drafts WHERE draft_id = ? AND admin_user_id = ? LIMIT 1');
        if (!$stmt) {
            throw new Exception('Failed to prepare draft lookup');
        }
        $stmt->bind_param('ii', $draftId, $adminUserId);
        $stmt->execute();
        $res = $stmt->get_result();
        $row = $res ? $res->fetch_assoc() : null;
        $stmt->close();

        if (!$row) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Draft not found']);
            exit;
        }

        $draft = [
            'draft_id' => (int)$row['draft_id'],
            'category_id' => $row['category_id'] !== null ? (int)$row['category_id'] : '',
            'item_name' => (string)($row['item_name'] ?? ''),
            'item_description' => (string)($row['item_description'] ?? ''),
            'condition_grade' => (string)($row['condition_grade'] ?? ''),
            'starting_bid' => $row['starting_bid'] !== null ? (string)$row['starting_bid'] : '',
            'reserve_price' => $row['reserve_price'] !== null ? (string)$row['reserve_price'] : '',
            'bid_increment' => $row['bid_increment'] !== null ? (string)$row['bid_increment'] : '',
            'start_at' => $row['start_at'] ?? '',
            'end_at' => $row['end_at'] ?? '',
            'draft_status' => (string)($row['draft_status'] ?? 'draft'),
            'created_at' => $row['created_at'] ?? null,
            'updated_at' => $row['updated_at'] ?? null,
            'media' => fetchMedia($conn, (int)$row['draft_id'])
        ];

        echo json_encode(['success' => true, 'draft' => $draft]);
        exit;
    }

    $stmt = $conn->prepare('SELECT d.draft_id, d.item_name, d.starting_bid, d.reserve_price, d.bid_increment, d.start_at, d.end_at, d.updated_at, d.draft_status, c.category_name FROM auction_drafts d LEFT JOIN categories c ON c.category_id = d.category_id WHERE d.admin_user_id = ? ORDER BY d.updated_at DESC, d.draft_id DESC');
    if (!$stmt) {
        throw new Exception('Failed to prepare drafts list');
    }
    $stmt->bind_param('i', $adminUserId);
    $stmt->execute();
    $res = $stmt->get_result();

    $drafts = [];
    while ($row = $res->fetch_assoc()) {
        $coverStmt = $conn->prepare('SELECT file_path FROM auction_draft_media WHERE draft_id = ? AND media_type = \'image\' ORDER BY sort_order ASC, media_id ASC LIMIT 1');
        $cover = '';
        if ($coverStmt) {
            $id = (int)$row['draft_id'];
            $coverStmt->bind_param('i', $id);
            $coverStmt->execute();
            $coverRes = $coverStmt->get_result();
            $coverRow = $coverRes ? $coverRes->fetch_assoc() : null;
            $cover = (string)($coverRow['file_path'] ?? '');
            $coverStmt->close();
        }

        $drafts[] = [
            'draft_id' => (int)$row['draft_id'],
            'item_name' => (string)($row['item_name'] ?? ''),
            'category_name' => (string)($row['category_name'] ?? 'No Category'),
            'starting_bid' => $row['starting_bid'] !== null ? (float)$row['starting_bid'] : null,
            'reserve_price' => $row['reserve_price'] !== null ? (float)$row['reserve_price'] : null,
            'bid_increment' => $row['bid_increment'] !== null ? (float)$row['bid_increment'] : null,
            'start_at' => $row['start_at'] ?? '',
            'end_at' => $row['end_at'] ?? '',
            'draft_status' => (string)($row['draft_status'] ?? 'draft'),
            'updated_at' => $row['updated_at'] ?? '',
            'cover_image' => $cover
        ];
    }
    $stmt->close();

    echo json_encode(['success' => true, 'drafts' => $drafts]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
