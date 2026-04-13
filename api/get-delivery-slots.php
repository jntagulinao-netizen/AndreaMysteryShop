<?php
header('Content-Type: application/json; charset=utf-8');
require __DIR__ . '/../dbConnection.php';

// Get date parameter
$date = $_GET['date'] ?? '';
if (empty($date)) {
    http_response_code(400);
    echo json_encode(['error' => 'Date parameter is required']);
    exit;
}

// Validate date format
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid date format. Use YYYY-MM-DD']);
    exit;
}

// Check if date is in the future or today
$requestedDate = new DateTime($date);
$today = new DateTime();
$today->setTime(0, 0, 0);

if ($requestedDate < $today) {
    http_response_code(400);
    echo json_encode(['error' => 'Date must be today or in the future']);
    exit;
}

// Define available time slots
$timeSlots = [
    '09:00-11:00',
    '11:00-13:00',
    '13:00-15:00',
    '15:00-17:00',
    '17:00-19:00'
];

// Load slots from delivery_slots table for the requested date
$availableSlots = [];
$stmt = $conn->prepare("SELECT slot_id, slot_time, max_orders, current_orders FROM delivery_slots WHERE slot_date = ? AND is_active = 1 ORDER BY slot_time ASC");
$stmt->bind_param('s', $date);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $slotTime = date('H:i', strtotime($row['slot_time']));
    $maxOrders = (int)$row['max_orders'];
    $currentOrders = (int)$row['current_orders'];
    $remaining = max(0, $maxOrders - $currentOrders);

    $availableSlots[] = [
        'id' => (int)$row['slot_id'],
        'time' => $slotTime,
        'available' => $remaining,
        'capacity' => $maxOrders
    ];
}

$stmt->close();

echo json_encode([
    'success' => true,
    'date' => $date,
    'slots' => $availableSlots
]);

$conn->close();
?>