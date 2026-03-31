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

// POST only
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$data = [
    'recipient_id' => intval($_POST['recipient_id'] ?? 0),
    'recipient_name' => $_POST['recipient_name'] ?? '',
    'phone_no' => $_POST['phone_no'] ?? '',
    'street_name' => $_POST['street_name'] ?? '',
    'unit_floor' => $_POST['unit_floor'] ?? '',
    'district' => $_POST['district'] ?? '',
    'city' => $_POST['city'] ?? '',
    'region' => $_POST['region'] ?? '',
    'is_default' => isset($_POST['is_default']) && $_POST['is_default'] === 'true' ? 1 : 0
];

// Validate required fields
if (!$data['recipient_id'] || !$data['recipient_name'] || !$data['phone_no'] || !$data['street_name'] || !$data['city']) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required fields']);
    exit;
}

try {
    // Verify recipient belongs to user
    $checkQuery = "SELECT recipient_id FROM recipients WHERE recipient_id = ? AND user_id = ?";
    $checkStmt = $conn->prepare($checkQuery);
    if (!$checkStmt) {
        throw new Exception($conn->error);
    }
    
    $checkStmt->bind_param('ii', $data['recipient_id'], $user_id);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();
    
    if ($checkResult->num_rows === 0) {
        http_response_code(403);
        echo json_encode(['error' => 'Recipient not found or unauthorized']);
        $checkStmt->close();
        exit;
    }
    $checkStmt->close();
    
    // If setting as default, unset other defaults for this user
    if ($data['is_default']) {
        $updateDefaultQuery = "UPDATE recipients SET is_default = 0 WHERE user_id = ? AND recipient_id != ?";
        $updateDefaultStmt = $conn->prepare($updateDefaultQuery);
        if ($updateDefaultStmt) {
            $updateDefaultStmt->bind_param('ii', $user_id, $data['recipient_id']);
            $updateDefaultStmt->execute();
            $updateDefaultStmt->close();
        }
    }
    
    // Update recipient
    $query = "UPDATE recipients SET recipient_name = ?, phone_no = ?, street_name = ?, unit_floor = ?, district = ?, city = ?, region = ?, is_default = ? WHERE recipient_id = ? AND user_id = ?";
    $stmt = $conn->prepare($query);
    
    if (!$stmt) {
        throw new Exception($conn->error);
    }
    
    $stmt->bind_param('sssssssiii', $data['recipient_name'], $data['phone_no'], $data['street_name'], $data['unit_floor'], $data['district'], $data['city'], $data['region'], $data['is_default'], $data['recipient_id'], $user_id);
    
    if (!$stmt->execute()) {
        throw new Exception($stmt->error);
    }

    // Normalize: exactly one default recipient per user.
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

    // Return actual default state after normalization.
    $isDefaultQuery = "SELECT is_default FROM recipients WHERE recipient_id = ? AND user_id = ?";
    $isDefaultStmt = $conn->prepare($isDefaultQuery);
    $actualIsDefault = $data['is_default'];
    if ($isDefaultStmt) {
        $isDefaultStmt->bind_param('ii', $data['recipient_id'], $user_id);
        $isDefaultStmt->execute();
        $isDefaultResult = $isDefaultStmt->get_result();
        if ($isDefaultRow = $isDefaultResult->fetch_assoc()) {
            $actualIsDefault = (int)$isDefaultRow['is_default'];
        }
        $isDefaultStmt->close();
    }
    
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'recipient_id' => $data['recipient_id'],
        'is_default' => $actualIsDefault,
        'message' => 'Recipient updated successfully'
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
} finally {
    if (isset($stmt)) $stmt->close();
    $conn->close();
}
?>
