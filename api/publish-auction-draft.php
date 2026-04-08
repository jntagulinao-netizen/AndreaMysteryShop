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
if ($draftId <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid draft ID']);
    exit;
}

try {
    $conn->begin_transaction();

    $schemaCheck = $conn->query("SHOW COLUMNS FROM auction_listings LIKE 'auction_product_id'");
    if (!$schemaCheck || $schemaCheck->num_rows === 0) {
        throw new Exception('Auction checkout update is not installed. Run docs/AUCTION_WINNER_CHECKOUT_UPDATES.sql first.');
    }

    $draftStmt = $conn->prepare('SELECT draft_id, admin_user_id, category_id, item_name, item_description, condition_grade, starting_bid, reserve_price, bid_increment, start_at, end_at FROM auction_drafts WHERE draft_id = ? AND admin_user_id = ? LIMIT 1');
    if (!$draftStmt) {
        throw new Exception('Failed to prepare draft lookup');
    }
    $draftStmt->bind_param('ii', $draftId, $adminUserId);
    $draftStmt->execute();
    $draftRes = $draftStmt->get_result();
    $draft = $draftRes ? $draftRes->fetch_assoc() : null;
    $draftStmt->close();

    if (!$draft) {
        throw new Exception('Auction draft not found');
    }

    $itemName = trim((string)($draft['item_name'] ?? ''));
    $itemDescription = trim((string)($draft['item_description'] ?? ''));
    $conditionGrade = trim((string)($draft['condition_grade'] ?? ''));
    $startingBid = $draft['starting_bid'] !== null ? (float)$draft['starting_bid'] : null;
    $bidIncrement = $draft['bid_increment'] !== null ? (float)$draft['bid_increment'] : null;
    $startAt = (string)($draft['start_at'] ?? '');
    $endAt = (string)($draft['end_at'] ?? '');

    if ($itemName === '') {
        throw new Exception('Item name is required before publishing');
    }
    if ($itemDescription === '') {
        throw new Exception('Item description is required before publishing');
    }
    if ($conditionGrade === '') {
        throw new Exception('Condition is required before publishing');
    }
    if ($startingBid === null || $startingBid <= 0) {
        throw new Exception('Starting bid must be greater than zero before publishing');
    }
    if ($bidIncrement === null || $bidIncrement <= 0) {
        throw new Exception('Bid increment must be greater than zero before publishing');
    }
    if ($startAt === '' || $endAt === '') {
        throw new Exception('Start and end schedule are required before publishing');
    }
    if (strtotime($endAt) <= strtotime($startAt)) {
        throw new Exception('Auction end time must be later than start time');
    }

    $imageCount = 0;
    $videoCount = 0;
    $mediaCheckStmt = $conn->prepare('SELECT media_type, COUNT(*) AS media_count FROM auction_draft_media WHERE draft_id = ? GROUP BY media_type');
    if ($mediaCheckStmt) {
        $mediaCheckStmt->bind_param('i', $draftId);
        $mediaCheckStmt->execute();
        $mediaCheckRes = $mediaCheckStmt->get_result();
        while ($mediaRow = $mediaCheckRes->fetch_assoc()) {
            $type = (string)($mediaRow['media_type'] ?? '');
            $count = (int)($mediaRow['media_count'] ?? 0);
            if ($type === 'image') {
                $imageCount = $count;
            } elseif ($type === 'video') {
                $videoCount = $count;
            }
        }
        $mediaCheckStmt->close();
    }

    if ($imageCount <= 0) {
        throw new Exception('At least one image is required before publishing');
    }
    if ($videoCount <= 0) {
        throw new Exception('A product video is required before publishing');
    }

    if (auction_has_schedule_conflict($conn, $startAt, $endAt, $draftId)) {
        throw new Exception('Another auction already overlaps with this schedule. Choose a different time slot.');
    }

    $listingCategoryId = $draft['category_id'] !== null ? (int)$draft['category_id'] : 0;
    if ($listingCategoryId <= 0) {
        $fallbackCategoryStmt = $conn->prepare('SELECT category_id FROM categories ORDER BY category_id ASC LIMIT 1');
        if ($fallbackCategoryStmt) {
            $fallbackCategoryStmt->execute();
            $fallbackCategoryRes = $fallbackCategoryStmt->get_result();
            $fallbackCategory = $fallbackCategoryRes ? $fallbackCategoryRes->fetch_assoc() : null;
            $listingCategoryId = (int)($fallbackCategory['category_id'] ?? 0);
            $fallbackCategoryStmt->close();
        }
    }
    if ($listingCategoryId <= 0) {
        throw new Exception('Cannot publish auction without a valid category');
    }

    $status = (strtotime($startAt) <= strtotime(auction_now_string())) ? 'active' : 'scheduled';
    $publishedAt = auction_now_string();

    $productDescription = $itemDescription;
    $productInsert = $conn->prepare('INSERT INTO products (product_name, product_description, price, product_stock, category_id, average_rating, order_count, archived) VALUES (?, ?, ?, 1, ?, 0.00, 0, 1)');
    if (!$productInsert) {
        throw new Exception('Failed to create product record for auction');
    }
    $productInsert->bind_param('ssdi', $itemName, $productDescription, $startingBid, $listingCategoryId);
    $productInsert->execute();
    $auctionProductId = (int)$productInsert->insert_id;
    $productInsert->close();

    if ($auctionProductId <= 0) {
        throw new Exception('Failed to create product record for auction');
    }

    $insertListing = $conn->prepare('INSERT INTO auction_listings (source_draft_id, admin_user_id, auction_product_id, category_id, item_name, item_description, condition_grade, starting_bid, current_bid, reserve_price, bid_increment, start_at, end_at, auction_status, published_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NULL, ?, ?, ?, ?, ?, ?)');
    if (!$insertListing) {
        throw new Exception('Failed to prepare listing insert');
    }

    $categoryId = $listingCategoryId;
    $itemDescription = (string)($draft['item_description'] ?? '');
    $conditionGrade = (string)($draft['condition_grade'] ?? '');
    $reservePrice = $draft['reserve_price'] !== null ? (float)$draft['reserve_price'] : null;

    $insertListing->bind_param(
        'iiiisssdddssss',
        $draftId,
        $adminUserId,
        $auctionProductId,
        $categoryId,
        $itemName,
        $itemDescription,
        $conditionGrade,
        $startingBid,
        $reservePrice,
        $bidIncrement,
        $startAt,
        $endAt,
        $status,
        $publishedAt
    );
    $insertListing->execute();
    $auctionId = (int)$insertListing->insert_id;
    $insertListing->close();

    $mediaStmt = $conn->prepare('SELECT media_type, file_path, sort_order, is_pinned FROM auction_draft_media WHERE draft_id = ? ORDER BY sort_order ASC, media_id ASC');
    if ($mediaStmt) {
        $mediaStmt->bind_param('i', $draftId);
        $mediaStmt->execute();
        $mediaRes = $mediaStmt->get_result();

        $insertMedia = $conn->prepare('INSERT INTO auction_listing_media (auction_id, media_type, file_path, sort_order, is_pinned) VALUES (?, ?, ?, ?, ?)');
        $insertProductImage = $conn->prepare('INSERT INTO product_images (product_id, image_url, is_pinned) VALUES (?, ?, ?)');
        if (!$insertMedia) {
            throw new Exception('Failed to prepare media copy');
        }

        while ($m = $mediaRes->fetch_assoc()) {
            $mediaType = (string)($m['media_type'] ?? 'image');
            $filePath = (string)($m['file_path'] ?? '');
            $sortOrder = (int)($m['sort_order'] ?? 0);
            $isPinned = (int)($m['is_pinned'] ?? 0);
            if ($filePath === '') {
                continue;
            }
            $insertMedia->bind_param('issii', $auctionId, $mediaType, $filePath, $sortOrder, $isPinned);
            $insertMedia->execute();

            if ($mediaType === 'image' && $insertProductImage) {
                $insertProductImage->bind_param('isi', $auctionProductId, $filePath, $isPinned);
                $insertProductImage->execute();
            }
        }

        $insertMedia->close();
        if ($insertProductImage) {
            $insertProductImage->close();
        }
        $mediaStmt->close();
    }

    $deleteDraftMediaStmt = $conn->prepare('DELETE FROM auction_draft_media WHERE draft_id = ?');
    if ($deleteDraftMediaStmt) {
        $deleteDraftMediaStmt->bind_param('i', $draftId);
        $deleteDraftMediaStmt->execute();
        $deleteDraftMediaStmt->close();
    }

    $deleteDraftStmt = $conn->prepare('DELETE FROM auction_drafts WHERE draft_id = ? AND admin_user_id = ?');
    if ($deleteDraftStmt) {
        $deleteDraftStmt->bind_param('ii', $draftId, $adminUserId);
        $deleteDraftStmt->execute();
        $deleteDraftStmt->close();
    }

    $conn->commit();

    echo json_encode([
        'success' => true,
        'auction_id' => $auctionId,
        'message' => 'Auction draft published successfully'
    ]);
} catch (Exception $e) {
    $conn->rollback();
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
