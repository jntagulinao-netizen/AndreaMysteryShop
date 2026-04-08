<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

require __DIR__ . '/../dbConnection.php';

$productId = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
$archive = isset($_POST['archive']) ? intval($_POST['archive']) : 0;
$archive = $archive === 1 ? 1 : 0;

if ($productId <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid product_id']);
    exit;
}

try {
    $transactionStarted = false;
    $conn->begin_transaction();
    $transactionStarted = true;

    $familyStmt = $conn->prepare('SELECT product_id, parent_product_id FROM products WHERE product_id = ? LIMIT 1');
    if (!$familyStmt) {
        throw new Exception('Failed to prepare product lookup');
    }
    $familyStmt->bind_param('i', $productId);
    if (!$familyStmt->execute()) {
        throw new Exception('Failed to fetch product');
    }
    $familyResult = $familyStmt->get_result();
    $selected = $familyResult ? $familyResult->fetch_assoc() : null;
    $familyStmt->close();

    if (!$selected) {
        throw new Exception('Product not found');
    }

    $mainProductId = !empty($selected['parent_product_id']) ? (int)$selected['parent_product_id'] : (int)$selected['product_id'];

    if ($archive === 0) {
        $hasOrderLinkTable = false;
        $orderLinkTableCheck = $conn->query("SHOW TABLES LIKE 'auction_order_links'");
        if ($orderLinkTableCheck && $orderLinkTableCheck->num_rows > 0) {
            $hasOrderLinkTable = true;
        }

        $lockSql = 'SELECT l.auction_id FROM auction_listings l';
        if ($hasOrderLinkTable) {
            $lockSql .= ' LEFT JOIN auction_order_links aol ON aol.auction_id = l.auction_id';
        }
        $lockSql .= ' WHERE l.auction_product_id IN (SELECT product_id FROM products WHERE product_id = ? OR parent_product_id = ?)';
        $lockSql .= $hasOrderLinkTable
            ? ' AND (l.auction_status = \'sold\' OR aol.order_id IS NOT NULL)'
            : ' AND l.auction_status = \'sold\'';
        $lockSql .= ' LIMIT 1';

        $lockStmt = $conn->prepare($lockSql);
        if (!$lockStmt) {
            throw new Exception('Failed to validate auction linkage state');
        }
        $lockStmt->bind_param('ii', $mainProductId, $mainProductId);
        if (!$lockStmt->execute()) {
            throw new Exception('Failed to validate auction linkage state');
        }
        $lockRes = $lockStmt->get_result();
        $lockedAuction = $lockRes ? $lockRes->fetch_assoc() : null;
        $lockStmt->close();

        if ($lockedAuction) {
            throw new Exception('This product family came from a completed auction and cannot be unarchived.');
        }
    }

    $featuredValue = $archive === 1 ? 0 : null;
    $updateSql = $archive === 1
        ? 'UPDATE products SET archived = ?, featured = ? WHERE product_id = ? OR parent_product_id = ?'
        : 'UPDATE products SET archived = ? WHERE product_id = ? OR parent_product_id = ?';
    $updateStmt = $conn->prepare($updateSql);
    if (!$updateStmt) {
        throw new Exception('Failed to prepare archive update');
    }
    if ($archive === 1) {
        $updateStmt->bind_param('iiii', $archive, $featuredValue, $mainProductId, $mainProductId);
    } else {
        $updateStmt->bind_param('iii', $archive, $mainProductId, $mainProductId);
    }
    if (!$updateStmt->execute()) {
        throw new Exception('Failed to update archive state');
    }
    $affected = $updateStmt->affected_rows;
    $updateStmt->close();

    $conn->commit();

    echo json_encode([
        'success' => true,
        'archived' => $archive,
        'affected_rows' => $affected,
        'main_product_id' => $mainProductId
    ]);
} catch (Throwable $e) {
    if (!empty($transactionStarted)) {
        $conn->rollback();
    }

    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
} finally {
    $conn->close();
}
