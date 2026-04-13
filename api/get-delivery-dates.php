<?php
header('Content-Type: application/json; charset=utf-8');
require __DIR__ . '/../dbConnection.php';

$stmt = $conn->prepare("SELECT slot_date,
       SUM(GREATEST(max_orders - current_orders, 0)) AS open_slots,
       COUNT(*) AS total_slots
  FROM delivery_slots
 WHERE is_active = 1
   AND slot_date >= CURDATE()
   AND current_orders < max_orders
 GROUP BY slot_date
 ORDER BY slot_date ASC
 LIMIT 60");

if (!$stmt) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Failed to prepare date query']);
    exit;
}

$stmt->execute();
$result = $stmt->get_result();
$dates = [];
while ($row = $result->fetch_assoc()) {
    $dates[] = [
        'date' => $row['slot_date'],
        'open_slots' => (int)$row['open_slots'],
        'total_slots' => (int)$row['total_slots'],
    ];
}
$stmt->close();

echo json_encode([
    'success' => true,
    'dates' => $dates,
]);

$conn->close();
?>