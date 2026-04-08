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
$auctionId = isset($_POST['auction_id']) ? (int)$_POST['auction_id'] : 0;
$action = trim((string)($_POST['action'] ?? ''));
$extendMinutes = isset($_POST['extend_minutes']) ? (int)$_POST['extend_minutes'] : 0;

if ($auctionId <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid auction ID']);
    exit;
}

if (!in_array($action, ['cancel', 'extend', 'force_close'], true)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid action']);
    exit;
}

try {
    $conn->begin_transaction();

    auction_sync_statuses($conn, $auctionId);

    $lockStmt = $conn->prepare('SELECT auction_id, auction_status, end_at FROM auction_listings WHERE auction_id = ? AND admin_user_id = ? FOR UPDATE');
    if (!$lockStmt) {
        throw new Exception('Failed to lock auction');
    }
    $lockStmt->bind_param('ii', $auctionId, $adminUserId);
    $lockStmt->execute();
    $lockRes = $lockStmt->get_result();
    $auction = $lockRes ? $lockRes->fetch_assoc() : null;
    $lockStmt->close();

    if (!$auction) {
        throw new Exception('Auction not found for this admin');
    }

    $status = (string)($auction['auction_status'] ?? 'scheduled');
    $now = date('Y-m-d H:i:s');

    if ($action === 'cancel') {
        if (in_array($status, ['sold', 'cancelled'], true)) {
            throw new Exception('Auction cannot be cancelled in its current state');
        }

        $cancelStmt = $conn->prepare('UPDATE auction_listings SET auction_status = \'cancelled\', closed_at = COALESCE(closed_at, ?) WHERE auction_id = ?');
        if (!$cancelStmt) {
            throw new Exception('Failed to cancel auction');
        }
        $cancelStmt->bind_param('si', $now, $auctionId);
        $cancelStmt->execute();
        $cancelStmt->close();
    }

    if ($action === 'extend') {
        if (!in_array($status, ['scheduled', 'active'], true)) {
            throw new Exception('Only scheduled or active auctions can be extended');
        }
        if ($extendMinutes <= 0 || $extendMinutes > 10080) {
            throw new Exception('Extend minutes must be between 1 and 10080');
        }

        $extendStmt = $conn->prepare('UPDATE auction_listings SET end_at = DATE_ADD(end_at, INTERVAL ? MINUTE), auction_status = CASE WHEN auction_status = \'ended\' THEN \'active\' ELSE auction_status END WHERE auction_id = ?');
        if (!$extendStmt) {
            throw new Exception('Failed to extend auction');
        }
        $extendStmt->bind_param('ii', $extendMinutes, $auctionId);
        $extendStmt->execute();
        $extendStmt->close();
    }

    if ($action === 'force_close') {
        if (in_array($status, ['sold', 'cancelled'], true)) {
            throw new Exception('Auction cannot be force-closed in its current state');
        }

        $closeStmt = $conn->prepare('UPDATE auction_listings SET end_at = ?, auction_status = CASE WHEN auction_status = \'scheduled\' THEN \'active\' ELSE auction_status END WHERE auction_id = ?');
        if (!$closeStmt) {
            throw new Exception('Failed to force close auction');
        }
        $closeStmt->bind_param('si', $now, $auctionId);
        $closeStmt->execute();
        $closeStmt->close();

        auction_sync_statuses($conn, $auctionId);
    }

    $conn->commit();

    echo json_encode(['success' => true, 'message' => 'Auction updated successfully']);
} catch (Exception $e) {
    $conn->rollback();
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
