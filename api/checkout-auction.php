<?php
header('Content-Type: application/json; charset=utf-8');
session_start();

require __DIR__ . '/../dbConnection.php';
require __DIR__ . '/../message_helpers.php';
require_once __DIR__ . '/auction_helpers.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Please login to checkout']);
    exit;
}

$role = $_SESSION['user_role'] ?? 'user';
if ($role !== 'user') {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Only buyer accounts can checkout auction wins']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

$userId = (int)$_SESSION['user_id'];
$auctionId = (int)($_POST['auction_id'] ?? 0);
$bidId = (int)($_POST['bid_id'] ?? 0);
$recipientId = (int)($_POST['recipient_id'] ?? 0);
$paymentMethod = trim((string)($_POST['payment_method'] ?? ''));

if ($auctionId <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid auction ID']);
    exit;
}
if ($recipientId <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Recipient is required']);
    exit;
}
if ($paymentMethod !== 'cash') {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Valid payment method is required (cash only)']);
    exit;
}

try {
    $conn->begin_transaction();

    $hasProductColumn = false;
    $colCheck = $conn->query("SHOW COLUMNS FROM auction_listings LIKE 'auction_product_id'");
    if ($colCheck && $colCheck->num_rows > 0) {
        $hasProductColumn = true;
    }

    $hasOrderLinkTable = false;
    $tableCheck = $conn->query("SHOW TABLES LIKE 'auction_order_links'");
    if ($tableCheck && $tableCheck->num_rows > 0) {
        $hasOrderLinkTable = true;
    }

    if (!$hasProductColumn || !$hasOrderLinkTable) {
        throw new Exception('Auction checkout update is not installed. Run docs/AUCTION_WINNER_CHECKOUT_UPDATES.sql first.');
    }

    auction_sync_statuses($conn, $auctionId);

    $recipientStmt = $conn->prepare('SELECT recipient_id, recipient_name, phone_no, street_name, unit_floor, district, city, region FROM recipients WHERE recipient_id = ? AND user_id = ? LIMIT 1');
    if (!$recipientStmt) {
        throw new Exception('Failed to validate recipient');
    }
    $recipientStmt->bind_param('ii', $recipientId, $userId);
    $recipientStmt->execute();
    $recipientRes = $recipientStmt->get_result();
    $recipient = $recipientRes ? $recipientRes->fetch_assoc() : null;
    $recipientStmt->close();

    if (!$recipient) {
        throw new Exception('Invalid recipient selected');
    }

    $auctionStmt = $conn->prepare('SELECT auction_id, auction_status, winner_user_id, sold_price, current_bid, starting_bid, item_name, item_description, category_id, auction_product_id FROM auction_listings WHERE auction_id = ? FOR UPDATE');
    if (!$auctionStmt) {
        throw new Exception('Failed to load auction');
    }
    $auctionStmt->bind_param('i', $auctionId);
    $auctionStmt->execute();
    $auctionRes = $auctionStmt->get_result();
    $auction = $auctionRes ? $auctionRes->fetch_assoc() : null;
    $auctionStmt->close();

    if (!$auction) {
        throw new Exception('Auction not found');
    }

    if ((string)$auction['auction_status'] !== 'sold') {
        throw new Exception('Checkout is only available for sold auctions');
    }

    if ((int)($auction['winner_user_id'] ?? 0) !== $userId) {
        throw new Exception('Only the winning bidder can checkout this auction');
    }

    if ($bidId > 0) {
        $winningBidStmt = $conn->prepare('SELECT bid_id, user_id FROM auction_bids WHERE auction_id = ? ORDER BY bid_amount DESC, bid_id DESC LIMIT 1');
        if (!$winningBidStmt) {
            throw new Exception('Failed to validate winning bid');
        }
        $winningBidStmt->bind_param('i', $auctionId);
        $winningBidStmt->execute();
        $winningBidRes = $winningBidStmt->get_result();
        $winningBidRow = $winningBidRes ? $winningBidRes->fetch_assoc() : null;
        $winningBidStmt->close();

        if (!$winningBidRow || (int)($winningBidRow['bid_id'] ?? 0) !== $bidId || (int)($winningBidRow['user_id'] ?? 0) !== $userId) {
            throw new Exception('Checkout is only available from your highest bid entry');
        }
    }

    $existingStmt = $conn->prepare('SELECT order_id FROM auction_order_links WHERE auction_id = ? LIMIT 1 FOR UPDATE');
    if (!$existingStmt) {
        throw new Exception('Failed to validate checkout state');
    }
    $existingStmt->bind_param('i', $auctionId);
    $existingStmt->execute();
    $existingRes = $existingStmt->get_result();
    $existingRow = $existingRes ? $existingRes->fetch_assoc() : null;
    $existingStmt->close();

    if ($existingRow) {
        throw new Exception('This auction has already been checked out');
    }

    $soldPrice = $auction['sold_price'] !== null ? (float)$auction['sold_price'] : null;
    $currentBid = $auction['current_bid'] !== null ? (float)$auction['current_bid'] : null;
    $startingBid = (float)($auction['starting_bid'] ?? 0);
    $orderPrice = $soldPrice !== null ? $soldPrice : ($currentBid !== null ? $currentBid : $startingBid);

    if ($orderPrice <= 0) {
        throw new Exception('Invalid sold price for this auction');
    }

    $auctionProductId = (int)($auction['auction_product_id'] ?? 0);
    $itemName = trim((string)($auction['item_name'] ?? 'Auction Item'));
    $itemDescription = trim((string)($auction['item_description'] ?? ''));
    $categoryId = (int)($auction['category_id'] ?? 0);

    if ($auctionProductId <= 0) {
        if ($categoryId <= 0) {
            $fallbackCategoryStmt = $conn->prepare('SELECT category_id FROM categories ORDER BY category_id ASC LIMIT 1');
            if ($fallbackCategoryStmt) {
                $fallbackCategoryStmt->execute();
                $fallbackCategoryRes = $fallbackCategoryStmt->get_result();
                $fallbackCategory = $fallbackCategoryRes ? $fallbackCategoryRes->fetch_assoc() : null;
                $categoryId = (int)($fallbackCategory['category_id'] ?? 0);
                $fallbackCategoryStmt->close();
            }
        }

        if ($categoryId <= 0) {
            throw new Exception('No valid category available for auction checkout');
        }

        $createProductStmt = $conn->prepare('INSERT INTO products (product_name, product_description, price, product_stock, category_id, average_rating, order_count, archived) VALUES (?, ?, ?, 1, ?, 0.00, 0, 1)');
        if (!$createProductStmt) {
            throw new Exception('Failed to create product record for auction');
        }
        $createProductStmt->bind_param('ssdi', $itemName, $itemDescription, $orderPrice, $categoryId);
        $createProductStmt->execute();
        $auctionProductId = (int)$createProductStmt->insert_id;
        $createProductStmt->close();

        if ($auctionProductId <= 0) {
            throw new Exception('Failed to create product record for auction');
        }

        $firstImageStmt = $conn->prepare('SELECT file_path, is_pinned FROM auction_listing_media WHERE auction_id = ? AND media_type = \'image\' ORDER BY is_pinned DESC, sort_order ASC, media_id ASC LIMIT 1');
        if ($firstImageStmt) {
            $firstImageStmt->bind_param('i', $auctionId);
            $firstImageStmt->execute();
            $firstImageRes = $firstImageStmt->get_result();
            $firstImage = $firstImageRes ? $firstImageRes->fetch_assoc() : null;
            $firstImageStmt->close();

            if ($firstImage && !empty($firstImage['file_path'])) {
                $insertImageStmt = $conn->prepare('INSERT INTO product_images (product_id, image_url, is_pinned) VALUES (?, ?, ?)');
                if ($insertImageStmt) {
                    $filePath = (string)$firstImage['file_path'];
                    $isPinned = (int)($firstImage['is_pinned'] ?? 0);
                    $insertImageStmt->bind_param('isi', $auctionProductId, $filePath, $isPinned);
                    $insertImageStmt->execute();
                    $insertImageStmt->close();
                }
            }
        }

        $setProductStmt = $conn->prepare('UPDATE auction_listings SET auction_product_id = ? WHERE auction_id = ?');
        if ($setProductStmt) {
            $setProductStmt->bind_param('ii', $auctionProductId, $auctionId);
            $setProductStmt->execute();
            $setProductStmt->close();
        }
    }

    $stockStmt = $conn->prepare('SELECT product_stock FROM products WHERE product_id = ? FOR UPDATE');
    if (!$stockStmt) {
        throw new Exception('Failed to validate product stock');
    }
    $stockStmt->bind_param('i', $auctionProductId);
    $stockStmt->execute();
    $stockRes = $stockStmt->get_result();
    $stockRow = $stockRes ? $stockRes->fetch_assoc() : null;
    $stockStmt->close();

    if (!$stockRow) {
        throw new Exception('Auction product record not found');
    }
    if ((int)$stockRow['product_stock'] < 1) {
        throw new Exception('This auction item is no longer available for checkout');
    }

    $status = 'pending';
    $orderStmt = $conn->prepare('INSERT INTO orders (user_id, recipient_id, payment_method, status, total_amount, order_date) VALUES (?, ?, ?, ?, ?, NOW())');
    if (!$orderStmt) {
        throw new Exception('Failed to create order');
    }
    $orderStmt->bind_param('iissd', $userId, $recipientId, $paymentMethod, $status, $orderPrice);
    $orderStmt->execute();
    $orderId = (int)$conn->insert_id;
    $orderStmt->close();

    if ($orderId <= 0) {
        throw new Exception('Failed to create order');
    }

    $itemStmt = $conn->prepare('INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, 1, ?)');
    if (!$itemStmt) {
        throw new Exception('Failed to create order item');
    }
    $itemStmt->bind_param('iid', $orderId, $auctionProductId, $orderPrice);
    $itemStmt->execute();
    $itemStmt->close();

    $decrementStmt = $conn->prepare('UPDATE products SET product_stock = product_stock - 1, order_count = order_count + 1 WHERE product_id = ? AND product_stock >= 1');
    if (!$decrementStmt) {
        throw new Exception('Failed to update stock');
    }
    $decrementStmt->bind_param('i', $auctionProductId);
    $decrementStmt->execute();
    if ($decrementStmt->affected_rows === 0) {
        $decrementStmt->close();
        throw new Exception('Failed to reserve auction stock');
    }
    $decrementStmt->close();

    $linkStmt = $conn->prepare('INSERT INTO auction_order_links (auction_id, order_id, user_id) VALUES (?, ?, ?)');
    if (!$linkStmt) {
        throw new Exception('Failed to finalize auction checkout');
    }
    $linkStmt->bind_param('iii', $auctionId, $orderId, $userId);
    $linkStmt->execute();
    $linkStmt->close();

    $conversationId = messageEnsureConversation($conn, $userId, (int)$orderId, messageGetDefaultAdminId($conn));
    if ($conversationId > 0) {
        $addressParts = [];
        if (!empty($recipient['street_name'])) $addressParts[] = trim((string)$recipient['street_name']);
        if (!empty($recipient['unit_floor'])) $addressParts[] = trim((string)$recipient['unit_floor']);
        if (!empty($recipient['district'])) $addressParts[] = trim((string)$recipient['district']);
        if (!empty($recipient['city'])) $addressParts[] = trim((string)$recipient['city']);
        if (!empty($recipient['region'])) $addressParts[] = trim((string)$recipient['region']);
        $recipientAddress = !empty($addressParts) ? implode(', ', $addressParts) : 'N/A';
        $recipientName = !empty($recipient['recipient_name']) ? trim((string)$recipient['recipient_name']) : 'N/A';
        $recipientPhone = !empty($recipient['phone_no']) ? trim((string)$recipient['phone_no']) : 'N/A';

        $noticeText = "Hello! Thank you for your auction checkout.\n"
            . "Your winning bid has been converted into an order.\n\n"
            . "Product: " . $itemName . "\n"
            . "Auction ID: #" . $auctionId . "\n"
            . "Order Date: " . date('Y-m-d H:i:s') . "\n"
            . "Status: Pending\n"
            . "Payment: " . strtoupper($paymentMethod) . "\n"
            . "Recipient: " . $recipientName . "\n"
            . "Phone: " . $recipientPhone . "\n"
            . "Address: " . $recipientAddress . "\n"
            . "Order Total: PHP " . number_format((float)$orderPrice, 2) . "\n\n"
            . "We'll send updates here as your order status changes.";

        messageInsert($conn, $conversationId, 0, 'system', $noticeText, 'order_notice', null);
    }

    $conn->commit();

    echo json_encode([
        'success' => true,
        'order_id' => $orderId,
        'auction_id' => $auctionId,
        'total' => $orderPrice,
        'message' => 'Auction checkout completed successfully'
    ]);
} catch (Exception $e) {
    $conn->rollback();
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
} finally {
    $conn->close();
}
