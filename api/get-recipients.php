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

try {
    $query = "SELECT recipient_id, recipient_name, phone_no, street_name, unit_floor, district, city, region, is_default FROM recipients WHERE user_id = ? ORDER BY is_default DESC, recipient_id DESC";
    $stmt = $conn->prepare($query);
    
    if (!$stmt) {
        throw new Exception($conn->error);
    }
    
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $recipients = [];
    $default_recipient = null;
    while ($row = $result->fetch_assoc()) {
        $recipient = [
            'recipient_id' => $row['recipient_id'],
            'recipient_name' => $row['recipient_name'],
            'phone_no' => $row['phone_no'],
            'street_name' => $row['street_name'],
            'unit_floor' => $row['unit_floor'],
            'district' => $row['district'],
            'city' => $row['city'],
            'region' => $row['region'],
            'is_default' => (bool) $row['is_default']
        ];
        $recipients[] = $recipient;
        
        // Track default recipient
        if ($row['is_default']) {
            $default_recipient = $recipient;
        }
    }
    
    http_response_code(200);
    echo json_encode([
        'recipients' => $recipients,
        'default_recipient' => $default_recipient
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
} finally {
    if (isset($stmt)) $stmt->close();
    $conn->close();
}
?>
