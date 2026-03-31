<?php
header('Content-Type: application/json');
session_start();

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

require_once '../dbConnection.php';

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$recipient_id = intval($_POST['recipient_id'] ?? 0);
if (!$recipient_id) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing recipient_id']);
    exit;
}

try {
    // Confirm recipient belongs to user
    $checkQuery = "SELECT recipient_id, is_default FROM recipients WHERE recipient_id = ? AND user_id = ?";
    $checkStmt = $conn->prepare($checkQuery);
    if (!$checkStmt) {
        throw new Exception($conn->error);
    }
    $checkStmt->bind_param('ii', $recipient_id, $user_id);
    $checkStmt->execute();
    $result = $checkStmt->get_result();

    if ($result->num_rows === 0) {
        http_response_code(403);
        echo json_encode(['error' => 'Recipient not found or unauthorized']);
        $checkStmt->close();
        exit;
    }

    $row = $result->fetch_assoc();
    $is_default = (int)$row['is_default'];
    $checkStmt->close();

    // First, set any orders referencing this recipient to NULL (preserve order history)
    $updateOrdersQuery = "UPDATE orders SET recipient_id = NULL WHERE recipient_id = ? AND user_id = ?";
    $updateOrdersStmt = $conn->prepare($updateOrdersQuery);
    if ($updateOrdersStmt) {
        $updateOrdersStmt->bind_param('ii', $recipient_id, $user_id);
        $updateOrdersStmt->execute();
        $updateOrdersStmt->close();
    }

    $deleteQuery = "DELETE FROM recipients WHERE recipient_id = ? AND user_id = ?";
    $deleteStmt = $conn->prepare($deleteQuery);
    if (!$deleteStmt) {
        throw new Exception($conn->error);
    }
    $deleteStmt->bind_param('ii', $recipient_id, $user_id);
    if (!$deleteStmt->execute()) {
        throw new Exception($deleteStmt->error);
    }
    $deleteStmt->close();

    // Normalize: exactly one default recipient per user if any recipient remains.
    $pickDefaultQuery = "SELECT recipient_id FROM recipients WHERE user_id = ? ORDER BY is_default DESC, recipient_id ASC LIMIT 1";
    $pickDefaultStmt = $conn->prepare($pickDefaultQuery);
    if ($pickDefaultStmt) {
        $pickDefaultStmt->bind_param('i', $user_id);
        $pickDefaultStmt->execute();
        $pickDefaultResult = $pickDefaultStmt->get_result();
        if ($pickDefaultRow = $pickDefaultResult->fetch_assoc()) {
            $default_id = (int)$pickDefaultRow['recipient_id'];
            $normalizeQuery = "UPDATE recipients SET is_default = CASE WHEN recipient_id = ? THEN 1 ELSE 0 END WHERE user_id = ?";
            $normalizeStmt = $conn->prepare($normalizeQuery);
            if ($normalizeStmt) {
                $normalizeStmt->bind_param('ii', $default_id, $user_id);
                $normalizeStmt->execute();
                $normalizeStmt->close();
            }
        }
        $pickDefaultStmt->close();
    }

    http_response_code(200);
    echo json_encode(['success' => true, 'message' => 'Recipient deleted successfully']);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
} finally {
    $conn->close();
}
