<?php
header('Content-Type: application/json; charset=utf-8');
session_start();
require __DIR__ . '/../dbConnection.php';
require_once __DIR__ . '/auction_helpers.php';

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
$itemName = trim((string)($_POST['item_name'] ?? ''));
$itemDescription = trim((string)($_POST['item_description'] ?? ''));
$conditionGrade = trim((string)($_POST['condition_grade'] ?? ''));
$categoryId = isset($_POST['category_id']) && $_POST['category_id'] !== '' ? (int)$_POST['category_id'] : null;
$startingBidRaw = trim((string)($_POST['starting_bid'] ?? ''));
$reservePriceRaw = trim((string)($_POST['reserve_price'] ?? ''));
$bidIncrementRaw = trim((string)($_POST['bid_increment'] ?? ''));
$startAtRaw = trim((string)($_POST['start_at'] ?? ''));
$endAtRaw = trim((string)($_POST['end_at'] ?? ''));
$removeExistingImage = ((string)($_POST['remove_existing_image'] ?? '0')) === '1';
$removeExistingVideo = ((string)($_POST['remove_existing_video'] ?? '0')) === '1';

$finfo = new finfo(FILEINFO_MIME_TYPE);
$allowedImageMimes = [
    'image/jpeg' => 'jpg',
    'image/png' => 'png',
    'image/webp' => 'webp',
    'image/gif' => 'gif'
];
$allowedVideoMimes = [
    'video/mp4' => 'mp4',
    'video/webm' => 'webm',
    'video/quicktime' => 'mov'
];

$rootPath = realpath(__DIR__ . '/..');
$draftMediaAbsoluteDir = $rootPath ? ($rootPath . DIRECTORY_SEPARATOR . 'auction_media' . DIRECTORY_SEPARATOR . 'drafts') : '';
if (!$draftMediaAbsoluteDir || (!is_dir($draftMediaAbsoluteDir) && !mkdir($draftMediaAbsoluteDir, 0775, true))) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Failed to prepare auction draft media directory']);
    exit;
}

function toDecimalOrNull(string $raw, string $field): ?float {
    if ($raw === '') {
        return null;
    }
    if (!is_numeric($raw) || (float)$raw < 0) {
        throw new Exception($field . ' must be a non-negative number');
    }
    return round((float)$raw, 2);
}

function toDateTimeOrNull(string $raw, string $field): ?string {
    if ($raw === '') {
        return null;
    }

    $candidate = str_replace('T', ' ', $raw);
    $tz = new DateTimeZone('Asia/Manila');
    $dt = DateTime::createFromFormat('Y-m-d H:i', $candidate, $tz);
    if (!$dt) {
        $dt = DateTime::createFromFormat('Y-m-d H:i:s', $candidate, $tz);
    }
    if (!$dt) {
        throw new Exception('Invalid ' . $field . ' datetime');
    }
    return $dt->format('Y-m-d H:i:s');
}

function saveAuctionDraftUpload(array $file, finfo $finfo, array $allowedMimes, string $prefix, string $absoluteDir): array {
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        throw new Exception('Invalid uploaded file');
    }

    $tmpName = (string)($file['tmp_name'] ?? '');
    if ($tmpName === '' || !is_uploaded_file($tmpName)) {
        throw new Exception('Invalid uploaded file source');
    }

    $mime = $finfo->file($tmpName);
    if (!isset($allowedMimes[$mime])) {
        throw new Exception('Unsupported file type');
    }

    $ext = $allowedMimes[$mime];
    $fileName = $prefix . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    $absolutePath = $absoluteDir . DIRECTORY_SEPARATOR . $fileName;
    $relativePath = 'auction_media/drafts/' . $fileName;

    if (!move_uploaded_file($tmpName, $absolutePath)) {
        throw new Exception('Failed to save uploaded file');
    }

    return ['absolute' => $absolutePath, 'relative' => $relativePath];
}

function parseUploadedImages(): array {
    $images = [];
    if (!isset($_FILES['images']) || !is_array($_FILES['images']['name'] ?? null)) {
        return $images;
    }

    $count = count($_FILES['images']['name']);
    for ($i = 0; $i < $count; $i++) {
        $err = $_FILES['images']['error'][$i] ?? UPLOAD_ERR_NO_FILE;
        if ($err === UPLOAD_ERR_NO_FILE) {
            continue;
        }

        $images[] = [
            'name' => $_FILES['images']['name'][$i] ?? '',
            'tmp_name' => $_FILES['images']['tmp_name'][$i] ?? '',
            'error' => (int)$err,
            'size' => (int)($_FILES['images']['size'][$i] ?? 0)
        ];
    }

    return $images;
}

function getDraftPaths(mysqli $conn, int $draftId, string $type): array {
    $stmt = $conn->prepare('SELECT media_id, file_path, sort_order FROM auction_draft_media WHERE draft_id = ? AND media_type = ? ORDER BY sort_order ASC, media_id ASC');
    if (!$stmt) {
        return [];
    }

    $stmt->bind_param('is', $draftId, $type);
    $stmt->execute();
    $res = $stmt->get_result();

    $rows = [];
    while ($row = $res->fetch_assoc()) {
        $rows[] = [
            'media_id' => (int)($row['media_id'] ?? 0),
            'file_path' => (string)($row['file_path'] ?? ''),
            'sort_order' => (int)($row['sort_order'] ?? 0)
        ];
    }

    $stmt->close();
    return $rows;
}

function deleteDraftMediaRows(mysqli $conn, array $rows, string $rootPath): void {
    foreach ($rows as $row) {
        $mediaId = (int)($row['media_id'] ?? 0);
        if ($mediaId > 0) {
            $deleteStmt = $conn->prepare('DELETE FROM auction_draft_media WHERE media_id = ?');
            if ($deleteStmt) {
                $deleteStmt->bind_param('i', $mediaId);
                $deleteStmt->execute();
                $deleteStmt->close();
            }
        }

        $relativePath = (string)($row['file_path'] ?? '');
        if ($relativePath === '' || $rootPath === '') {
            continue;
        }
        $absolutePath = rtrim($rootPath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $relativePath);
        if (file_exists($absolutePath)) {
            @unlink($absolutePath);
        }
    }
}

try {
    $startingBid = toDecimalOrNull($startingBidRaw, 'Starting bid');
    $reservePrice = toDecimalOrNull($reservePriceRaw, 'Reserve price');
    $bidIncrement = toDecimalOrNull($bidIncrementRaw, 'Bid increment');

    if ($itemName === '' && $startingBid === null && $startAtRaw === '' && $endAtRaw === '') {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Please enter at least the item name or auction details']);
        exit;
    }

    $startAt = toDateTimeOrNull($startAtRaw, 'start');
    $endAt = toDateTimeOrNull($endAtRaw, 'end');

    if ($startAt !== null && $endAt !== null && strtotime($endAt) <= strtotime($startAt)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'End time must be later than start time']);
        exit;
    }

    if ($startAt !== null && $endAt !== null && auction_has_schedule_conflict($conn, $startAt, $endAt, $draftId)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Another auction already overlaps with this schedule. Choose a different time slot.']);
        exit;
    }

    if ($startingBid !== null && $reservePrice !== null && $reservePrice < $startingBid) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Reserve price cannot be lower than starting bid']);
        exit;
    }

    $conn->begin_transaction();

    if ($draftId > 0) {
        $checkStmt = $conn->prepare('SELECT draft_id FROM auction_drafts WHERE draft_id = ? AND admin_user_id = ? LIMIT 1');
        if (!$checkStmt) {
            throw new Exception('Failed to prepare draft check');
        }
        $checkStmt->bind_param('ii', $draftId, $adminUserId);
        $checkStmt->execute();
        $checkRes = $checkStmt->get_result();
        $row = $checkRes ? $checkRes->fetch_assoc() : null;
        $checkStmt->close();

        if (!$row) {
            throw new Exception('Draft not found or not owned by this admin');
        }

        $updateStmt = $conn->prepare('UPDATE auction_drafts SET category_id = ?, item_name = ?, item_description = ?, condition_grade = ?, starting_bid = ?, reserve_price = ?, bid_increment = ?, start_at = ?, end_at = ? WHERE draft_id = ? AND admin_user_id = ?');
        if (!$updateStmt) {
            throw new Exception('Failed to prepare draft update');
        }
        $updateStmt->bind_param(
            'isssdddssii',
            $categoryId,
            $itemName,
            $itemDescription,
            $conditionGrade,
            $startingBid,
            $reservePrice,
            $bidIncrement,
            $startAt,
            $endAt,
            $draftId,
            $adminUserId
        );
        $updateStmt->execute();
        $updateStmt->close();
    } else {
        $insertStmt = $conn->prepare('INSERT INTO auction_drafts (admin_user_id, category_id, item_name, item_description, condition_grade, starting_bid, reserve_price, bid_increment, start_at, end_at, draft_status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, \'draft\')');
        if (!$insertStmt) {
            throw new Exception('Failed to prepare draft insert');
        }
        $insertStmt->bind_param(
            'iisssdddss',
            $adminUserId,
            $categoryId,
            $itemName,
            $itemDescription,
            $conditionGrade,
            $startingBid,
            $reservePrice,
            $bidIncrement,
            $startAt,
            $endAt
        );
        $insertStmt->execute();
        $draftId = (int)$insertStmt->insert_id;
        $insertStmt->close();
    }

    $existingImages = getDraftPaths($conn, $draftId, 'image');
    $uploadedImages = parseUploadedImages();

    if (count($uploadedImages) > 1) {
        throw new Exception('Only one image is allowed per auction draft');
    }

    if ($removeExistingImage || count($uploadedImages) > 0) {
        deleteDraftMediaRows($conn, $existingImages, (string)$rootPath);
        $existingImages = [];
    }

    $nextImageSort = 0;

    foreach ($uploadedImages as $imageFile) {
        $saved = saveAuctionDraftUpload($imageFile, $finfo, $allowedImageMimes, 'auction_draft_img', $draftMediaAbsoluteDir);

        $mediaStmt = $conn->prepare('INSERT INTO auction_draft_media (draft_id, media_type, file_path, sort_order, is_pinned) VALUES (?, \'image\', ?, ?, 0)');
        if (!$mediaStmt) {
            throw new Exception('Failed to prepare image media insert');
        }
        $mediaStmt->bind_param('isi', $draftId, $saved['relative'], $nextImageSort);
        $mediaStmt->execute();
        $mediaStmt->close();
        $nextImageSort++;
    }

    $hasNewVideoUpload = isset($_FILES['video']) && (int)($_FILES['video']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;
    $existingVideoRows = getDraftPaths($conn, $draftId, 'video');

    if ($removeExistingVideo || $hasNewVideoUpload) {
        deleteDraftMediaRows($conn, $existingVideoRows, (string)$rootPath);
    }

    if ($hasNewVideoUpload) {

        $savedVideo = saveAuctionDraftUpload($_FILES['video'], $finfo, $allowedVideoMimes, 'auction_draft_video', $draftMediaAbsoluteDir);
        $videoStmt = $conn->prepare('INSERT INTO auction_draft_media (draft_id, media_type, file_path, sort_order, is_pinned) VALUES (?, \'video\', ?, 0, 0)');
        if (!$videoStmt) {
            throw new Exception('Failed to prepare video media insert');
        }
        $videoStmt->bind_param('is', $draftId, $savedVideo['relative']);
        $videoStmt->execute();
        $videoStmt->close();
    }

    $conn->commit();

    echo json_encode([
        'success' => true,
        'draft_id' => $draftId,
        'message' => 'Auction draft saved successfully'
    ]);
} catch (Exception $e) {
    if ($conn->errno) {
        $conn->rollback();
    }
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
