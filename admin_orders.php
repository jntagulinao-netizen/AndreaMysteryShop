<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: LogIn.php');
    exit;
}
$role = $_SESSION['user_role'] ?? 'user';
if ($role !== 'admin') {
    // non-admins redirected to user dashboard
    header('Location: user_dashboard.php');
    exit;
}

require_once 'dbConnection.php';
require_once 'message_helpers.php';

$statusLabels = [
  'pending' => 'Pending',
  'processing' => 'Processing',
  'pickup' => 'Ready for Pickup',
  'pickedup' => 'Picked Up',
  'shipped' => 'Shipped',
  'delivered' => 'Delivered',
  'received' => 'Received',
  'reviewed' => 'Reviewed',
  'cancelled' => 'Cancelled'
];

function setAdminFlash($type, $message) {
  $_SESSION['admin_flash'] = ['type' => $type, 'message' => $message];
}

function getOrderMeta(mysqli $conn, int $orderId): ?array {
  $stmt = $conn->prepare('SELECT user_id, status, archived, binned, delivery_type FROM orders WHERE order_id = ? LIMIT 1');
  if (!$stmt) {
    return null;
  }
  $stmt->bind_param('i', $orderId);
  $stmt->execute();
  $result = $stmt->get_result();
  $row = $result ? $result->fetch_assoc() : null;
  $stmt->close();
  return $row ?: null;
}

function sendOrderStatusNotice(mysqli $conn, int $adminId, int $userId, int $orderId, string $messageText, ?string $orderStatus = null): void {
  if ($userId <= 0 || $orderId <= 0 || trim($messageText) === '') {
    return;
  }

  $conversationId = messageEnsureConversation($conn, $userId, $orderId, $adminId);
  if ($conversationId <= 0) {
    return;
  }

  $senderId = $adminId > 0 ? $adminId : 0;
  $senderRole = $adminId > 0 ? 'admin' : 'system';
  messageInsert($conn, $conversationId, $senderId, $senderRole, $messageText, 'status_notice', $orderStatus);
}

function getPrimaryOrderProductName(mysqli $conn, int $orderId): string {
  if ($orderId <= 0) {
    return 'your item';
  }

  $stmt = $conn->prepare('SELECT p.product_name FROM order_items oi LEFT JOIN products p ON oi.product_id = p.product_id WHERE oi.order_id = ? ORDER BY oi.order_item_id ASC LIMIT 1');
  if (!$stmt) {
    return 'your item';
  }

  $stmt->bind_param('i', $orderId);
  $stmt->execute();
  $result = $stmt->get_result();
  $row = $result ? $result->fetch_assoc() : null;
  $stmt->close();

  $name = trim((string)($row['product_name'] ?? ''));
  return $name !== '' ? $name : 'your item';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $action = $_POST['action'] ?? '';
  $orderId = intval($_POST['order_id'] ?? 0);

  if ($orderId <= 0) {
    setAdminFlash('error', 'Invalid order ID.');
    header('Location: admin_orders.php');
    exit;
  }

  $orderMeta = getOrderMeta($conn, $orderId);
  if ($orderMeta === null) {
    setAdminFlash('error', 'Order not found.');
    header('Location: admin_orders.php');
    exit;
  }

  $currentStatus = (string)($orderMeta['status'] ?? '');
  $orderUserId = (int)($orderMeta['user_id'] ?? 0);
  $adminId = (int)($_SESSION['user_id'] ?? 0);
  $isArchived = intval($orderMeta['archived'] ?? 0) === 1;
  $isBinned = intval($orderMeta['binned'] ?? 0) === 1;

  if ($action === 'cancel_order') {
    if ($isArchived || $isBinned) {
      setAdminFlash('error', 'Archived or binned orders cannot be cancelled.');
      header('Location: admin_orders.php');
      exit;
    }

    if ($currentStatus !== 'pending') {
      setAdminFlash('error', 'Admin can only cancel orders that are still pending.');
      header('Location: admin_orders.php');
      exit;
    }

    $cancelStmt = $conn->prepare('UPDATE orders SET status = "cancelled" WHERE order_id = ? AND status = "pending"');
    if (!$cancelStmt) {
      setAdminFlash('error', 'Failed to prepare cancellation query.');
      header('Location: admin_orders.php');
      exit;
    }
    $cancelStmt->bind_param('i', $orderId);
    $cancelStmt->execute();
    $updated = $cancelStmt->affected_rows > 0;
    $cancelStmt->close();

    if ($updated) {
      sendOrderStatusNotice(
        $conn,
        $adminId,
        $orderUserId,
        $orderId,
        'Hi! Your order #' . $orderId . ' has been cancelled by admin support. Please message us if you need help placing a new order.',
        'cancelled'
      );
    }

    setAdminFlash($updated ? 'success' : 'error', $updated ? 'Order cancelled successfully.' : 'Order cancellation failed.');
    header('Location: admin_orders.php');
    exit;
  }

  if ($action === 'advance_status') {
    if ($isArchived || $isBinned) {
      setAdminFlash('error', 'Archived or binned orders cannot be moved to another status.');
      header('Location: admin_orders.php');
      exit;
    }

    // Get delivery type to determine the correct flow
    $deliveryType = $orderMeta['delivery_type'] ?? 'delivery';
    
    // Define next status maps based on delivery type
    $deliveryNextStatusMap = [
      'pending' => 'processing',
      'processing' => 'shipped',
      'shipped' => 'delivered'
    ];
    
    $pickupNextStatusMap = [
      'pending' => 'processing', 
      'processing' => 'pickup',
      'pickup' => 'pickedup',
      'pickedup' => 'received'
    ];
    
    $nextStatusMap = ($deliveryType === 'pickup') ? $pickupNextStatusMap : $deliveryNextStatusMap;

    if (!isset($nextStatusMap[$currentStatus])) {
      setAdminFlash('error', 'This order cannot be advanced further in the admin flow.');
      header('Location: admin_orders.php');
      exit;
    }

    $nextStatus = $nextStatusMap[$currentStatus];
    $advanceStmt = $conn->prepare('UPDATE orders SET status = ? WHERE order_id = ? AND status = ?');
    if (!$advanceStmt) {
      setAdminFlash('error', 'Failed to prepare status update query.');
      header('Location: admin_orders.php');
      exit;
    }
    $advanceStmt->bind_param('sis', $nextStatus, $orderId, $currentStatus);
    $advanceStmt->execute();
    $updated = $advanceStmt->affected_rows > 0;
    $advanceStmt->close();

    if ($updated) {
      $primaryProductName = getPrimaryOrderProductName($conn, $orderId);
      $statusMessages = [
        'processing' => 'Great news! Your order for ' . $primaryProductName . ' is now Processing. We are preparing your items for shipment.',
        'pickup' => 'Your order for ' . $primaryProductName . ' is now Ready for Pickup. Please come to our store at your scheduled time.',
        'pickedup' => 'Your order for ' . $primaryProductName . ' has been picked up. Please confirm receipt.',
        'shipped' => 'Update: Your order for ' . $primaryProductName . ' has been Shipped and is now on the way.',
        'delivered' => 'Your order for ' . $primaryProductName . ' is marked Delivered. Please confirm once received.'
      ];
      $notice = $statusMessages[$nextStatus] ?? ('Your order for ' . $primaryProductName . ' status is now ' . ucfirst($nextStatus) . '.');
      sendOrderStatusNotice($conn, $adminId, $orderUserId, $orderId, $notice, $nextStatus);
    }

    setAdminFlash($updated ? 'success' : 'error', $updated ? 'Order moved to ' . ucfirst($nextStatus) . '.' : 'Order status update failed.');
    header('Location: admin_orders.php');
    exit;
  }

  if ($action === 'archive_order') {
    if (!in_array(strtolower($currentStatus), ['reviewed', 'cancelled', 'canceled'], true)) {
      setAdminFlash('error', 'Only reviewed or cancelled orders can be archived.');
      header('Location: admin_orders.php');
      exit;
    }

    if ($isArchived) {
      setAdminFlash('error', 'Order is already archived.');
      header('Location: admin_orders.php');
      exit;
    }

    if ($isBinned) {
      setAdminFlash('error', 'Binned orders cannot be archived again.');
      header('Location: admin_orders.php');
      exit;
    }

    $archiveStmt = $conn->prepare('UPDATE orders SET archived = 1, binned = 0 WHERE order_id = ? AND LOWER(status) IN ("reviewed", "cancelled") AND archived = 0');
    if (!$archiveStmt) {
      setAdminFlash('error', 'Failed to prepare archive query.');
      header('Location: admin_orders.php');
      exit;
    }
    $archiveStmt->bind_param('i', $orderId);
    $archiveStmt->execute();
    $updated = $archiveStmt->affected_rows > 0;
    $archiveStmt->close();

    if ($updated) {
      sendOrderStatusNotice(
        $conn,
        $adminId,
        $orderUserId,
        $orderId,
        'Admin update: Your order #' . $orderId . ' thread has been archived on the admin side. Your order records and reviews are preserved.',
        strtolower($currentStatus)
      );
    }

    setAdminFlash($updated ? 'success' : 'error', $updated ? 'Order archived successfully.' : 'Order archive failed.');
    header('Location: admin_orders.php');
    exit;
  }

  if ($action === 'move_to_bin') {
    if (!in_array(strtolower($currentStatus), ['reviewed', 'cancelled', 'canceled'], true)) {
      setAdminFlash('error', 'Only reviewed or cancelled orders can be moved to bin.');
      header('Location: admin_orders.php');
      exit;
    }

    if (!$isArchived || $isBinned) {
      setAdminFlash('error', 'Only archived orders can be moved to bin.');
      header('Location: admin_orders.php');
      exit;
    }

    $binStmt = $conn->prepare('UPDATE orders SET binned = 1 WHERE order_id = ? AND LOWER(status) IN ("reviewed", "cancelled", "canceled") AND archived = 1 AND binned = 0');
    if (!$binStmt) {
      setAdminFlash('error', 'Failed to prepare move-to-bin query.');
      header('Location: admin_orders.php');
      exit;
    }
    $binStmt->bind_param('i', $orderId);
    $binStmt->execute();
    $updated = $binStmt->affected_rows > 0;
    $binStmt->close();

    if ($updated) {
      sendOrderStatusNotice(
        $conn,
        $adminId,
        $orderUserId,
        $orderId,
        'Admin update: Order #' . $orderId . ' was moved to bin and is no longer visible on your side.',
        strtolower($currentStatus)
      );
    }

    setAdminFlash($updated ? 'success' : 'error', $updated ? 'Order moved to bin.' : 'Move to bin failed.');
    header('Location: admin_orders.php');
    exit;
  }

  if ($action === 'unarchive_order') {
    if (!in_array(strtolower($currentStatus), ['reviewed', 'cancelled', 'canceled'], true)) {
      setAdminFlash('error', 'Only reviewed or cancelled orders can be unarchived.');
      header('Location: admin_orders.php');
      exit;
    }

    if (!$isBinned) {
      setAdminFlash('error', 'Only binned orders can be restored.');
      header('Location: admin_orders.php');
      exit;
    }

    $unarchiveStmt = $conn->prepare('UPDATE orders SET archived = 0, binned = 0 WHERE order_id = ? AND LOWER(status) IN ("reviewed", "cancelled", "canceled") AND binned = 1');
    if (!$unarchiveStmt) {
      setAdminFlash('error', 'Failed to prepare undo archive query.');
      header('Location: admin_orders.php');
      exit;
    }
    $unarchiveStmt->bind_param('i', $orderId);
    $unarchiveStmt->execute();
    $updated = $unarchiveStmt->affected_rows > 0;
    $unarchiveStmt->close();

    if ($updated) {
      sendOrderStatusNotice(
        $conn,
        $adminId,
        $orderUserId,
        $orderId,
        'Admin update: Order #' . $orderId . ' has been restored from bin.',
        strtolower($currentStatus)
      );
    }

    setAdminFlash($updated ? 'success' : 'error', $updated ? 'Order restored from bin.' : 'Undo archive failed.');
    header('Location: admin_orders.php');
    exit;
  }

  setAdminFlash('error', 'Unknown action.');
  header('Location: admin_orders.php');
  exit;
}

$flash = $_SESSION['admin_flash'] ?? null;
unset($_SESSION['admin_flash']);
$allowedStatuses = ['all', 'pending', 'processing', 'shipped', 'delivered', 'reviewed', 'cancelled', 'archived', 'bin'];
$initialStatus = $_GET['status'] ?? 'all';
if (!in_array($initialStatus, $allowedStatuses, true)) {
  $initialStatus = 'all';
}
$focusOrderId = intval($_GET['order_id'] ?? 0);

$metrics = [
  'total_orders' => 0,
  'pending_orders' => 0,
  'processing_orders' => 0,
  'shipped_orders' => 0,
  'revenue' => 0.0,
  'converted_orders' => 0
];

$metricSql = 'SELECT
  COUNT(*) AS total_orders,
  SUM(CASE WHEN status = "pending" THEN 1 ELSE 0 END) AS pending_orders,
  SUM(CASE WHEN status = "processing" THEN 1 ELSE 0 END) AS processing_orders,
  SUM(CASE WHEN status = "shipped" THEN 1 ELSE 0 END) AS shipped_orders,
  SUM(CASE WHEN status IN ("delivered", "received", "reviewed") THEN total_amount ELSE 0 END) AS revenue,
  SUM(CASE WHEN status IN ("delivered", "received", "reviewed") THEN 1 ELSE 0 END) AS converted_orders
  FROM orders
  WHERE archived = 0 AND binned = 0';
$metricResult = $conn->query($metricSql);
if ($metricResult && $metricResult->num_rows > 0) {
  $metrics = array_merge($metrics, $metricResult->fetch_assoc());
}

$totalOrders = intval($metrics['total_orders'] ?? 0);
$convertedOrders = intval($metrics['converted_orders'] ?? 0);
$conversionRate = $totalOrders > 0 ? round(($convertedOrders / $totalOrders) * 100, 1) : 0;

$orders = [];
$orderSql = 'SELECT
  o.order_id,
  o.user_id,
  o.recipient_id,
  o.order_date,
  o.status,
  o.archived,
  o.binned,
  o.payment_method,
  o.total_amount,
  o.delivery_type,
  o.schedule_date,
  o.schedule_slot,
  u.full_name AS customer_name,
  oi.order_item_id,
  oi.product_id,
  oi.quantity,
  oi.price,
  p.product_name,
  p.product_description,
  (SELECT pi.image_url
   FROM product_images pi
   WHERE pi.product_id = p.product_id
     AND LOWER(pi.image_url) REGEXP "\\\\.(jpg|jpeg|png|gif|webp)$"
   ORDER BY pi.is_pinned DESC, pi.image_id ASC
   LIMIT 1) AS product_image
  FROM orders o
  LEFT JOIN users u ON o.user_id = u.user_id
  LEFT JOIN order_items oi ON o.order_id = oi.order_id
  LEFT JOIN products p ON oi.product_id = p.product_id
  ORDER BY o.order_date DESC, o.order_id DESC, oi.order_item_id ASC';

$orderResult = $conn->query($orderSql);
if ($orderResult) {
  while ($row = $orderResult->fetch_assoc()) {
    $orderId = intval($row['order_id']);
    if (!isset($orders[$orderId])) {
      $orders[$orderId] = [
        'order_id' => $orderId,
        'user_id' => intval($row['user_id'] ?? 0),
        'recipient_id' => intval($row['recipient_id'] ?? 0),
        'order_date' => $row['order_date'],
        'status' => $row['status'],
        'archived' => intval($row['archived'] ?? 0),
        'binned' => intval($row['binned'] ?? 0),
        'payment_method' => $row['payment_method'],
        'total_amount' => (float)($row['total_amount'] ?? 0),
        'delivery_type' => $row['delivery_type'],
        'schedule_date' => $row['schedule_date'],
        'schedule_slot' => $row['schedule_slot'],
        'customer_name' => $row['customer_name'] ?: 'Unknown',
        'items' => []
      ];
    }

    if (!empty($row['product_id'])) {
      $orders[$orderId]['items'][] = [
        'order_item_id' => intval($row['order_item_id'] ?? 0),
        'product_id' => intval($row['product_id'] ?? 0),
        'product_name' => $row['product_name'] ?: 'Product',
        'product_description' => $row['product_description'] ?: '',
        'product_image' => $row['product_image'] ?: 'logo.jpg',
        'quantity' => intval($row['quantity'] ?? 1),
        'price' => (float)($row['price'] ?? 0)
      ];
    }
  }
}

$orders = array_values($orders);

$recipientData = [];
foreach ($orders as $order) {
  $recipientId = intval($order['recipient_id'] ?? 0);
  if ($recipientId <= 0 || isset($recipientData[$recipientId])) {
    continue;
  }

  $recipientStmt = $conn->prepare('SELECT recipient_name, phone_no, street_name, unit_floor, district, city, region FROM recipients WHERE recipient_id = ? LIMIT 1');
  if ($recipientStmt) {
    $recipientStmt->bind_param('i', $recipientId);
    $recipientStmt->execute();
    $recipientResult = $recipientStmt->get_result();
    if ($recipientResult && $recipientResult->num_rows > 0) {
      $recipientData[$recipientId] = $recipientResult->fetch_assoc();
    }
    $recipientStmt->close();
  }
}

$latestReviewByUserProduct = [];
$reviewUserIds = [];
$reviewProductIds = [];
foreach ($orders as $order) {
  if (strtolower((string)($order['status'] ?? '')) !== 'reviewed') {
    continue;
  }
  $userId = intval($order['user_id'] ?? 0);
  $firstProductId = (!empty($order['items']) && isset($order['items'][0]['product_id'])) ? intval($order['items'][0]['product_id']) : 0;
  if ($userId <= 0 || $firstProductId <= 0) {
    continue;
  }
  $reviewUserIds[$userId] = true;
  $reviewProductIds[$firstProductId] = true;
}

if (!empty($reviewUserIds) && !empty($reviewProductIds)) {
  $userIds = array_map('intval', array_keys($reviewUserIds));
  $productIds = array_map('intval', array_keys($reviewProductIds));
  $userPlaceholders = implode(',', array_fill(0, count($userIds), '?'));
  $productPlaceholders = implode(',', array_fill(0, count($productIds), '?'));
  $reviewSql = "SELECT review_id, user_id, product_id, created_at
                FROM reviews
                WHERE user_id IN ($userPlaceholders)
                  AND product_id IN ($productPlaceholders)
                ORDER BY created_at DESC, review_id DESC";
  $reviewStmt = $conn->prepare($reviewSql);
  if ($reviewStmt) {
    $types = str_repeat('i', count($userIds) + count($productIds));
    $params = array_merge($userIds, $productIds);
    $reviewStmt->bind_param($types, ...$params);
    $reviewStmt->execute();
    $reviewRes = $reviewStmt->get_result();
    if ($reviewRes) {
      while ($row = $reviewRes->fetch_assoc()) {
        $key = intval($row['user_id']) . ':' . intval($row['product_id']);
        if (!isset($latestReviewByUserProduct[$key])) {
          $latestReviewByUserProduct[$key] = intval($row['review_id'] ?? 0);
        }
      }
    }
    $reviewStmt->close();
  }
}

$statusDisplay = [
  'pending' => 'To Pay',
  'processing' => 'To Ship',
  'shipped' => 'To Receive',
  'delivered' => 'Order Delivered',
  'received' => 'Order Received',
  'reviewed' => 'Reviewed',
  'cancelled' => 'Cancelled'
];
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Admin Orders Center - Andrea Mystery Shop</title>
  <link rel="stylesheet" href="main.css">
  <style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    html, body { margin: 0; padding: 0; }
    body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f5f5f5; padding-bottom: 78px; }

    .page-container { width: calc(100% - 48px); max-width: none; margin: 0 auto; padding: 16px 0; }

    .page-header {
      position: sticky;
      top: 0;
      background: #fff;
      z-index: 120;
      display: flex;
      align-items: center;
      gap: 12px;
      padding: 12px 16px;
      border-radius: 12px;
      margin-bottom: 8px;
    }
    .back-arrow { cursor: pointer; font-size: 24px; color: #333; padding: 4px; line-height: 1; }
    .header-title { font-size: 18px; font-weight: 600; color: #333; flex: 1; }
    .header-meta { font-size: 12px; color: #777; }

    .search-filter-bar {
      position: sticky;
      top: 64px;
      z-index: 110;
      background: #f5f5f5;
      padding: 8px 0;
      margin-bottom: 0;
      display: flex;
      gap: 10px;
      align-items: center;
    }
    .search-wrapper {
      flex: 1;
      display: flex;
      align-items: center;
      background: #fff;
      border-radius: 10px;
      padding: 0 12px;
      border: 1px solid #f0a7a2;
    }
    .search-wrapper input {
      flex: 1;
      border: none;
      outline: none;
      padding: 10px 8px;
      font-size: 14px;
      background: transparent;
    }

    .filter-btn {
      background: #fff;
      border: 1px solid #ddd;
      border-radius: 8px;
      padding: 10px 16px;
      cursor: pointer;
      display: flex;
      align-items: center;
      gap: 4px;
      font-size: 14px;
      color: #333;
      position: relative;
    }
    .filter-btn:hover { background: #f9f9f9; }
    .filter-dropdown {
      position: absolute;
      top: 100%;
      right: 0;
      background: #fff;
      border: 1px solid #ddd;
      border-radius: 8px;
      box-shadow: 0 2px 8px rgba(0,0,0,0.1);
      min-width: 200px;
      z-index: 120;
      display: none;
    }
    .filter-dropdown.active { display: block; }
    .dropdown-item {
      padding: 12px 16px;
      border-bottom: 1px solid #f5f5f5;
      cursor: pointer;
      font-size: 13px;
      color: #333;
      transition: background 0.2s;
    }
    .dropdown-item:last-child { border-bottom: none; }
    .dropdown-item:hover { background: #f9f9f9; }
    .dropdown-item.active { background: #e22a39; color: #fff; font-weight: 600; }

    .topbar-menu { position: relative; }
    .menu-trigger {
      width: 34px;
      height: 34px;
      border: 1px solid #ddd;
      border-radius: 8px;
      background: #fff;
      color: #333;
      font-size: 18px;
      cursor: pointer;
      line-height: 1;
    }
    .menu-dropdown {
      position: absolute;
      top: calc(100% + 6px);
      right: 0;
      min-width: 170px;
      background: #fff;
      border: 1px solid #ddd;
      border-radius: 10px;
      box-shadow: 0 8px 20px rgba(0, 0, 0, 0.12);
      display: none;
      z-index: 130;
      overflow: hidden;
    }
    .menu-dropdown.active { display: block; }
    .menu-dropdown a {
      display: block;
      padding: 10px 12px;
      color: #333;
      text-decoration: none;
      font-size: 13px;
      border-bottom: 1px solid #f0f0f0;
    }
    .menu-dropdown a:last-child { border-bottom: none; }
    .menu-dropdown a:hover { background: #f8f8f8; }

    .tabs-container {
      position: sticky;
      top: 120px;
      z-index: 105;
      display: flex;
      gap: 0;
      border-bottom: 1px solid #ddd;
      background: #fff;
      padding: 0 16px;
      margin-bottom: 0;
      border-radius: 12px 12px 0 0;
      overflow-x: auto;
    }
    .tab {
      padding: 12px 16px;
      border: none;
      background: transparent;
      cursor: pointer;
      font-size: 13px;
      color: #666;
      border-bottom: 3px solid transparent;
      white-space: nowrap;
      font-weight: 500;
    }
    .tab.active { color: #e22a39; border-bottom-color: #e22a39; }

    .orders-container { background: #fff; border-radius: 0 0 12px 12px; overflow: hidden; }
    .order-group { border-bottom: 8px solid #f5f5f5; padding: 16px; }
    .order-group:last-child { border-bottom: none; }
    
    .pagination-wrap { display: flex; justify-content: center; gap: 8px; flex-wrap: wrap; padding: 16px; background: #fff; border-top: 1px solid #f5f5f5; }
    .pagination-btn { width: 38px; height: 38px; border: 1px solid #ddd; background: #fff; color: #333; border-radius: 6px; cursor: pointer; font-size: 14px; font-weight: 500; transition: all 0.2s; }
    .pagination-btn:hover:not(:disabled) { border-color: #2d68d8; color: #2d68d8; background: #f0f6ff; }
    .pagination-btn.active { background: #2d68d8; color: #fff; border-color: #2d68d8; }
    .pagination-btn:disabled { opacity: 0.45; cursor: not-allowed; }
    
    .order-focus {
      border: 2px solid #2d68d8;
      border-radius: 10px;
      box-shadow: 0 0 0 4px rgba(45, 104, 216, 0.12);
    }

    .store-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 16px; padding-bottom: 12px; border-bottom: 1px solid #eee; }
    .store-info { display: flex; align-items: center; gap: 8px; }
    .store-icon { width: 28px; height: 28px; border-radius: 4px; display: flex; align-items: center; justify-content: center; overflow: hidden; }
    .store-icon img { width: 100%; height: 100%; object-fit: contain; }
    .store-name { font-weight: 600; color: #333; font-size: 14px; }
    .store-arrow { color: #999; font-size: 16px; }
    .order-status { color: #e22a39; font-weight: 600; font-size: 14px; }

    .order-item { display: flex; gap: 12px; padding: 12px 0; border-bottom: 1px solid #f5f5f5; cursor: pointer; transition: background 0.2s; }
    .order-item:hover { background: #f9f9f9; }
    .order-item:last-child { border-bottom: none; }
    .item-image { width: 80px; height: 80px; flex-shrink: 0; background: #f5f5f5; border-radius: 8px; overflow: hidden; border: 1px solid #eee; }
    .item-image img { width: 100%; height: 100%; object-fit: cover; }
    .item-details { flex: 1; }
    .item-name { font-size: 13px; color: #333; font-weight: 600; line-height: 1.4; margin-bottom: 6px; }
    .item-attrs { font-size: 12px; color: #999; margin-bottom: 6px; }
    .item-badges { display: flex; flex-wrap: wrap; gap: 6px; margin-bottom: 8px; }
    .badge { background: #f0f8ff; border: 0.5px solid #cce2ff; color: #1a62c3; padding: 3px 8px; border-radius: 4px; font-size: 11px; }
    .item-meta { font-size: 12px; color: #7a7a7a; margin-bottom: 4px; }
    .item-bottom { display: flex; justify-content: space-between; align-items: flex-end; }
    .item-price { font-weight: 600; color: #333; font-size: 14px; }
    .item-qty { font-size: 12px; color: #666; }

    .timeline {
      display: grid;
      grid-template-columns: repeat(4, 1fr);
      gap: 6px;
      margin-top: 8px;
      margin-bottom: 12px;
    }
    .step { padding: 6px; border-radius: 8px; text-align: center; font-size: 11px; border: 1px solid #e6ebf7; color: #8b93a7; background: #fafbff; }
    .step.done { background: #e8f8ee; border-color: #bfe8cf; color: #0f7a40; font-weight: 700; }
    .step.current { background: #fef4dd; border-color: #ffdca0; color: #9b5d00; font-weight: 700; }

    .actions-row { display: flex; justify-content: space-between; align-items: center; margin-top: 12px; padding-top: 12px; border-top: 1px solid #f5f5f5; }
    .action-left { color: #666; font-size: 12px; }
    .action-buttons { display: flex; gap: 8px; flex-wrap: wrap; }
    .action-btn {
      padding: 8px 12px;
      border-radius: 6px;
      border: 1px solid #ddd;
      background: #fff;
      cursor: pointer;
      font-size: 12px;
      font-weight: 600;
      transition: all 0.2s;
      text-decoration: none;
      display: inline-flex;
      align-items: center;
      justify-content: center;
    }
    .action-btn.primary { background: #2d68d8; color: #fff; border-color: #2d68d8; }
    .action-btn.primary:hover { background: #1f56bf; }
    .action-btn.danger { background: #e22a39; color: #fff; border-color: #e22a39; }
    .action-btn.danger:hover { background: #c20000; }
    .action-btn.disabled { background: #d6d9e2; color: #7d8395; border-color: #d6d9e2; cursor: not-allowed; }

    .flash { padding: 10px 12px; border-radius: 10px; margin-bottom: 12px; font-size: 14px; }
    .flash.success { background: #e8f8ee; color: #0b6b32; border: 1px solid #bce8cd; }
    .flash.error { background: #fff1f1; color: #9c1f1f; border: 1px solid #f6c1c1; }

    .swal-overlay {
      position: fixed;
      inset: 0;
      background: rgba(15, 23, 42, 0.45);
      display: none;
      align-items: center;
      justify-content: center;
      z-index: 2000;
      padding: 20px;
    }
    .swal-overlay.show { display: flex; }
    .swal-card {
      width: 100%;
      max-width: 360px;
      background: #fff;
      border-radius: 14px;
      border: 1px solid #dde5ee;
      box-shadow: 0 18px 40px rgba(15, 23, 42, 0.25);
      text-align: center;
      padding: 20px 18px 16px;
      animation: swalIn .16s ease-out;
    }
    @keyframes swalIn {
      from { opacity: 0; transform: translateY(8px) scale(0.98); }
      to { opacity: 1; transform: translateY(0) scale(1); }
    }
    .swal-icon {
      width: 52px;
      height: 52px;
      border-radius: 50%;
      margin: 0 auto 10px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 28px;
      font-weight: 700;
    }
    .swal-icon.success { background: #e9f9ef; color: #0c8f3f; }
    .swal-icon.error { background: #ffecee; color: #c62839; }
    .swal-icon.warning { background: #fff6e5; color: #bb6a00; }
    .swal-title { font-size: 20px; font-weight: 700; color: #152033; margin-bottom: 8px; }
    .swal-text { font-size: 14px; color: #5f6d7f; margin-bottom: 14px; line-height: 1.45; }
    .swal-actions { display: grid; grid-template-columns: 1fr; gap: 8px; }
    .swal-actions.two { grid-template-columns: 1fr 1fr; }
    .swal-btn {
      border: none;
      border-radius: 10px;
      font-size: 14px;
      font-weight: 700;
      width: 100%;
      height: 42px;
      cursor: pointer;
    }
    .swal-btn.primary { background: #2d68d8; color: #fff; }
    .swal-btn.primary:hover { background: #1f56bf; }
    .swal-btn.secondary { background: #f2f5fb; color: #44546a; border: 1px solid #d5deea; }
    .swal-btn.secondary:hover { background: #e9eef7; }

    .empty-state { text-align: center; padding: 60px 20px; color: #999; }

    .modal { display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 1000; animation: slideUp 0.3s ease; }
    .modal.active { display: flex; }
    .modal-content { background: #f5f5f5; width: 100%; height: 100%; overflow-y: auto; animation: slideUp 0.3s ease; }
    @keyframes slideUp { from { transform: translateY(100%); } to { transform: translateY(0); } }

    .modal-header {
      display: flex;
      align-items: center;
      gap: 12px;
      padding: 16px;
      background: #fff;
      border-bottom: 1px solid #eee;
      position: sticky;
      top: 0;
      z-index: 20;
    }
    .modal-header .back-arrow { cursor: pointer; }
    .modal-header .header-title { flex: 1; }
    .modal-icons { display: flex; gap: 12px; align-items: center; }
    .modal-icons div { cursor: pointer; font-size: 20px; }

    .status-badge { display: flex; align-items: center; gap: 12px; padding: 16px; background: #fff; margin: 8px 16px 0; border-radius: 8px; }
    .status-badge-icon { font-size: 24px; }
    .status-badge-content h3 { font-size: 16px; font-weight: 600; color: #333; margin-bottom: 4px; }
    .status-badge-content p { font-size: 13px; color: #666; }

    .delivery-info { padding: 16px; background: #fff; margin: 8px 16px 0; border-radius: 8px; }
    .delivery-info-title { display: flex; align-items: center; gap: 8px; font-size: 14px; font-weight: 600; color: #333; margin-bottom: 12px; }
    .delivery-address { font-size: 13px; color: #666; line-height: 1.5; }
    .delivery-phone { font-size: 13px; font-weight: 600; color: #333; margin-bottom: 4px; }

    .order-detail-item { padding: 16px; background: #fff; margin: 8px 16px 0; border-radius: 8px; }
    .detail-store-header { display: flex; align-items: center; gap: 8px; margin-bottom: 16px; padding-bottom: 12px; border-bottom: 1px solid #eee; }
    .detail-store-info { display: flex; align-items: center; gap: 8px; flex: 1; }
    .detail-order-status { color: #e22a39; font-weight: 600; font-size: 14px; }

    .detail-item-content { display: flex; gap: 12px; }
    .detail-item-image { width: 100px; height: 100px; flex-shrink: 0; background: #f5f5f5; border-radius: 8px; overflow: hidden; border: 1px solid #eee; }
    .detail-item-image img { width: 100%; height: 100%; object-fit: cover; }
    .detail-item-details { flex: 1; }
    .detail-item-name { font-size: 14px; font-weight: 600; color: #333; line-height: 1.4; margin-bottom: 8px; }
    .detail-item-attrs { font-size: 12px; color: #666; margin-bottom: 8px; }
    .detail-item-price { font-size: 16px; font-weight: 600; color: #333; margin-bottom: 8px; }
    .detail-item-qty { font-size: 13px; color: #666; }

    .order-total { padding: 16px; background: #fff; margin: 8px 16px 0; border-radius: 8px; }
    .total-row { display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; }
    .total-label { font-size: 13px; color: #666; }
    .total-value { font-size: 14px; color: #333; }
    .total-final { display: flex; justify-content: space-between; align-items: center; padding-top: 12px; border-top: 1px solid #eee; }
    .total-final-label { font-size: 14px; font-weight: 600; color: #333; }
    .total-final-value { font-size: 18px; font-weight: 600; color: #333; }

    .order-number { padding: 12px 16px; background: #fff; margin: 8px 16px 0; border-radius: 8px; display: flex; justify-content: space-between; align-items: center; }
    .order-number-label { font-size: 13px; color: #666; }
    .order-number-value { font-size: 14px; color: #1a62c3; cursor: pointer; font-weight: 600; }

    .view-summary { padding: 12px 16px; background: #fff; margin: 8px 16px 16px; border-radius: 8px; cursor: pointer; display: flex; justify-content: space-between; align-items: center; }
    .view-summary-label { font-size: 14px; font-weight: 600; color: #1a62c3; }
    .view-summary-arrow { font-size: 16px; color: #1a62c3; }

    .order-actions { padding: 16px; background: #fff; margin: 8px 16px 16px; border-radius: 8px; display: flex; gap: 12px; }
    .order-actions form { flex: 1; }
    .order-actions button { width: 100%; padding: 12px; border-radius: 6px; border: 1px solid #ddd; background: #fff; cursor: pointer; font-size: 13px; font-weight: 600; transition: all 0.2s; }
    .order-actions button.action-primary { background: #2d68d8; color: #fff; border-color: #2d68d8; }
    .order-actions button.action-primary:hover { background: #1f56bf; }
    .order-actions button.action-danger { background: #e22a39; color: #fff; border-color: #e22a39; }
    .order-actions button.action-danger:hover { background: #c20000; }
    .order-actions button.action-disabled { background: #d6d9e2; color: #7d8395; border-color: #d6d9e2; cursor: not-allowed; }

    .user-review-card { padding: 14px 16px; background: #fff; margin: 8px 16px 0; border-radius: 8px; }
    .user-review-title { font-size: 14px; font-weight: 700; color: #1f2937; margin-bottom: 8px; }
    .user-review-list { display: grid; gap: 10px; }
    .user-review-item {
      border: 1px solid #eceff2;
      border-radius: 10px;
      padding: 10px;
      background: #fff;
    }
    .user-review-meta { font-size: 12px; color: #6b7280; margin-bottom: 6px; }
    .user-review-rating { font-size: 13px; color: #b45309; font-weight: 700; margin-bottom: 6px; }
    .user-review-text {
      font-size: 13px;
      color: #4b5563;
      line-height: 1.45;
      white-space: pre-wrap;
      margin-bottom: 8px;
      background: #f9fafb;
      border: 1px solid #eceff2;
      border-radius: 8px;
      padding: 8px;
    }
    .user-review-media { display: flex; gap: 8px; flex-wrap: wrap; }
    .user-review-media-item {
      width: 88px;
      height: 88px;
      border: 1px solid #e5e7eb;
      border-radius: 8px;
      background: #f3f4f6;
      overflow: hidden;
      cursor: zoom-in;
      position: relative;
      padding: 0;
    }
    .user-review-media-item img,
    .user-review-media-item video {
      width: 100%;
      height: 100%;
      object-fit: cover;
      display: block;
      border: none;
      background: #f3f4f6;
    }
    .user-review-media-view-badge {
      position: absolute;
      background: rgba(15, 23, 42, 0.78);
      color: #fff;
      font-size: 10px;
      font-weight: 700;
      line-height: 1;
      border-radius: 999px;
      padding: 4px 6px;
      letter-spacing: 0.2px;
    }
    .user-review-media-view-badge {
      right: 6px;
      bottom: 6px;
    }
    .review-manage-actions {
      margin-top: 8px;
      display: flex;
      justify-content: flex-end;
    }
    .review-manage-btn {
      width: auto;
      padding: 8px 10px;
    }

    .media-viewer-overlay {
      position: fixed;
      inset: 0;
      display: none;
      align-items: center;
      justify-content: center;
      background: rgba(0, 0, 0, 0.86);
      z-index: 2500;
      padding: 16px;
    }
    .media-viewer-overlay.active { display: flex; }
    .media-viewer-content {
      max-width: 96vw;
      max-height: 92vh;
      display: flex;
      align-items: center;
      justify-content: center;
    }
    .media-viewer-content img,
    .media-viewer-content video {
      max-width: 96vw;
      max-height: 92vh;
      width: auto;
      height: auto;
      object-fit: contain;
      border-radius: 10px;
      background: #000;
    }
    .media-viewer-close {
      position: absolute;
      top: 14px;
      right: 14px;
      width: 40px;
      height: 40px;
      border-radius: 999px;
      border: 1px solid rgba(255,255,255,0.45);
      background: rgba(0,0,0,0.4);
      color: #fff;
      font-size: 24px;
      line-height: 1;
      cursor: pointer;
    }

    @media (max-width: 768px) {
      .page-container { width: calc(100% - 24px); }
      .timeline { grid-template-columns: repeat(2, 1fr); }
    }
  </style>
</head>
<body>
  <div class="page-container">
    <div class="page-header">
      <div class="back-arrow" onclick="window.location.href='admin_dashboard.php'">‹</div>
      <div class="header-title">Admin Orders</div>
      <div class="header-meta">Updated <?php echo date('d/m/Y H:i:s'); ?></div>
      <div class="topbar-menu">
        <button type="button" class="menu-trigger" onclick="toggleTopbarMenu(event)">...</button>
        <div class="menu-dropdown" id="topbarMenuDropdown">
          <a href="admin_dashboard.php">Admin Dashboard</a>
           <a href="messages.php">Messages</a>
          <a href="admin_orders.php">Admin Orders</a>
          <a href="admin_my_products.php">My Products</a>
          <a href="admin_product_drafts.php">Product Drafts</a>
          <a href="admin_my_products.php?view=archived">Archived Products</a>
          <a href="admin_manage_reviews.php">Manage Reviews</a>
          <a href="admin_profile.php">Admin Profile</a>
          <a href="logout.php">Logout</a>
        </div>
      </div>
    </div>

    <div id="adminOrdersSection" class="admin-orders-section active">
      <?php if ($flash): ?>
        <div id="flashData" data-type="<?php echo htmlspecialchars($flash['type']); ?>" data-message="<?php echo htmlspecialchars($flash['message']); ?>" style="display:none;"></div>
      <?php endif; ?>

      <div class="search-filter-bar">
        <div class="search-wrapper">
          <input type="text" id="searchInput" placeholder="Search by order ID, customer, product..." onkeyup="applyFilters()" autocomplete="off">
        </div>
        <div style="position: relative;">
          <button class="filter-btn" onclick="toggleFilterDropdown()" title="Filter">⚙ Sort</button>
          <div id="filterDropdown" class="filter-dropdown">
            <div class="dropdown-item" onclick="applyDateSort('newest')">📅 Newest to Oldest</div>
            <div class="dropdown-item" onclick="applyDateSort('oldest')">📅 Oldest to Newest</div>
          </div>
        </div>
      </div>

      <div class="tabs-container">
        <button class="tab active" data-status="all" onclick="filterByStatus('all')">All</button>
        <button class="tab" data-status="pending" onclick="filterByStatus('pending')">To process</button>
        <button class="tab" data-status="processing" onclick="filterByStatus('processing')">To ship</button>
        <button class="tab" data-status="pickup" onclick="filterByStatus('pickup')">Pickups</button>
        <button class="tab" data-status="shipped" onclick="filterByStatus('shipped')">To receive</button>
        <button class="tab" data-status="delivered" onclick="filterByStatus('delivered')">Delivered</button>
        <button class="tab" data-status="reviewed" onclick="filterByStatus('reviewed')">Reviews</button>
        <button class="tab" data-status="cancelled" onclick="filterByStatus('cancelled')">Cancelled</button>
        <button class="tab" data-status="archived" onclick="filterByStatus('archived')">Archived</button>
        <button class="tab" data-status="bin" onclick="filterByStatus('bin')">Bin</button>
      </div>

      <div class="orders-container" id="ordersContainer">
        <div class="empty-state" id="emptyState" style="<?php echo empty($orders) ? '' : 'display:none;'; ?>">
          <p>No orders yet</p>
        </div>

        <?php foreach ($orders as $order): ?>
        <?php
          $status = $order['status'];
          $deliveryType = $order['delivery_type'] ?? 'delivery';
          
          // Define next status maps based on delivery type
          $deliveryNextStatusMap = [
            'pending' => 'processing',
            'processing' => 'shipped',
            'shipped' => 'delivered'
          ];
          
          $pickupNextStatusMap = [
            'pending' => 'processing', 
            'processing' => 'pickup',
            'pickup' => 'pickedup',
            'pickedup' => 'received'
          ];
          
          $statusMap = ($deliveryType === 'pickup') ? $pickupNextStatusMap : $deliveryNextStatusMap;
          $nextStatus = $statusMap[$status] ?? null;
          
          // Create linear flow for timeline based on delivery type
          $linearFlow = ($deliveryType === 'pickup') ? 
            ['pending', 'processing', 'pickup', 'pickedup'] : 
            ['pending', 'processing', 'shipped', 'delivered'];
          
          $timelineIndex = array_search($status, $linearFlow, true);
          
          $isArchived = intval($order['archived'] ?? 0) === 1;
          $isBinned = intval($order['binned'] ?? 0) === 1;
          if ($isArchived || $isBinned) {
            $nextStatus = null;
          }
          $canCancel = $status === 'pending';
          if ($isArchived || $isBinned) {
            $canCancel = false;
          }
          $canArchive = in_array(strtolower((string)$status), ['reviewed', 'cancelled', 'canceled'], true) && !$isArchived && !$isBinned;
          $canMoveToBin = in_array(strtolower((string)$status), ['reviewed', 'cancelled', 'canceled'], true) && $isArchived && !$isBinned;
          $canUnarchive = in_array(strtolower((string)$status), ['reviewed', 'cancelled', 'canceled'], true) && $isBinned;
          $statusText = $isBinned ? 'Binned' : ($isArchived ? 'Archived' : ($statusDisplay[$status] ?? ucfirst($status)));
          $recipient = isset($recipientData[$order['recipient_id']]) ? $recipientData[$order['recipient_id']] : null;
          $recipientName = $recipient['recipient_name'] ?? 'Recipient';
          $recipientPhone = $recipient['phone_no'] ?? '';
          $recipientParts = [];
          if ($recipient) {
            if (!empty($recipient['street_name'])) $recipientParts[] = $recipient['street_name'];
            if (!empty($recipient['unit_floor'])) $recipientParts[] = $recipient['unit_floor'];
            if (!empty($recipient['district'])) $recipientParts[] = $recipient['district'];
            if (!empty($recipient['city'])) $recipientParts[] = $recipient['city'];
            if (!empty($recipient['region'])) $recipientParts[] = $recipient['region'];
          }
          $recipientAddress = implode(', ', $recipientParts);
          $productList = implode(', ', array_map(function ($item) {
            return (string)($item['product_name'] ?? '');
          }, $order['items']));
        ?>
        <div class="order-group" data-status="<?php echo htmlspecialchars($status); ?>" data-archived="<?php echo $isArchived ? '1' : '0'; ?>" data-binned="<?php echo $isBinned ? '1' : '0'; ?>" data-order-id="<?php echo intval($order['order_id']); ?>" data-customer="<?php echo htmlspecialchars(strtolower((string)$order['customer_name'])); ?>" data-products="<?php echo htmlspecialchars(strtolower($productList)); ?>">
          <div class="store-header">
            <div class="store-info">
              <div class="store-icon"><img src="logo.jpg" alt="Logo"></div>
              <div class="store-name">Andrea Mystery Shop</div>
              <div class="store-arrow">›</div>
            </div>
            <div class="order-status"><?php echo htmlspecialchars($statusText); ?></div>
          </div>

          <?php if (!empty($order['items'])): ?>
            <?php foreach ($order['items'] as $item): ?>
              <?php $itemImage = !empty($item['product_image']) ? htmlspecialchars($item['product_image']) : 'logo.jpg'; ?>
              <div class="order-item" onclick="openOrderDetail(this)"
                data-order-id="<?php echo intval($order['order_id']); ?>"
                data-item-id="<?php echo intval($item['order_item_id']); ?>"
                data-product-id="<?php echo intval($item['product_id']); ?>"
                data-order-status="<?php echo htmlspecialchars($status); ?>"
                data-store-name="Andrea Mystery Shop"
                data-recipient-name="<?php echo htmlspecialchars($recipientName); ?>"
                data-recipient-phone="<?php echo htmlspecialchars($recipientPhone); ?>"
                data-recipient-address="<?php echo htmlspecialchars($recipientAddress); ?>"
                data-delivery-type="<?php echo htmlspecialchars($order['delivery_type'] ?? ''); ?>"
                data-schedule-date="<?php echo htmlspecialchars($order['schedule_date'] ?? ''); ?>"
                data-schedule-slot="<?php echo htmlspecialchars($order['schedule_slot'] ?? ''); ?>"
                data-customer-name="<?php echo htmlspecialchars($order['customer_name']); ?>"
                data-payment-method="<?php echo htmlspecialchars($order['payment_method'] ?: 'N/A'); ?>"
                data-order-date="<?php echo htmlspecialchars((string)$order['order_date']); ?>"
                data-total-amount="<?php echo htmlspecialchars(number_format((float)$order['total_amount'], 2)); ?>"
                data-item-count="<?php echo intval(count($order['items'])); ?>"
                data-is-archived="<?php echo $isArchived ? '1' : '0'; ?>"
                data-is-binned="<?php echo $isBinned ? '1' : '0'; ?>"
                data-next-status="<?php echo htmlspecialchars($nextStatus ?? ''); ?>">
                <div class="item-image">
                  <img src="<?php echo $itemImage; ?>" alt="Product">
                </div>
                <div class="item-details">
                  <div class="item-name"><?php echo htmlspecialchars(substr((string)$item['product_name'], 0, 60)); ?></div>
                  <div class="item-attrs">Variant details • Default</div>
                  <div class="item-badges">
                    <div class="badge">30 Days Free Returns</div>
                  </div>
                  <div class="item-meta">Customer: <?php echo htmlspecialchars($order['customer_name']); ?> | Payment: <?php echo htmlspecialchars($order['payment_method'] ?: 'N/A'); ?></div>
                  <div class="item-bottom">
                    <div class="item-price">₱<?php echo number_format((float)$item['price'], 2); ?></div>
                    <div class="item-qty">Qty: <?php echo intval($item['quantity']); ?></div>
                  </div>
                </div>
              </div>
            <?php endforeach; ?>
          <?php else: ?>
            <div class="order-item" onclick="openOrderDetail(this)"
              data-order-id="<?php echo intval($order['order_id']); ?>"
              data-item-id="0"
              data-product-id="0"
              data-order-status="<?php echo htmlspecialchars($status); ?>"
              data-store-name="Andrea Mystery Shop"
              data-recipient-name="<?php echo htmlspecialchars($recipientName); ?>"
              data-recipient-phone="<?php echo htmlspecialchars($recipientPhone); ?>"
              data-recipient-address="<?php echo htmlspecialchars($recipientAddress); ?>"
              data-delivery-type="<?php echo htmlspecialchars($order['delivery_type'] ?? ''); ?>"
              data-schedule-date="<?php echo htmlspecialchars($order['schedule_date'] ?? ''); ?>"
              data-schedule-slot="<?php echo htmlspecialchars($order['schedule_slot'] ?? ''); ?>"
              data-customer-name="<?php echo htmlspecialchars($order['customer_name']); ?>"
              data-payment-method="<?php echo htmlspecialchars($order['payment_method'] ?: 'N/A'); ?>"
              data-order-date="<?php echo htmlspecialchars((string)$order['order_date']); ?>"
              data-total-amount="<?php echo htmlspecialchars(number_format((float)$order['total_amount'], 2)); ?>"
              data-item-count="0"
              data-is-archived="<?php echo $isArchived ? '1' : '0'; ?>"
              data-is-binned="<?php echo $isBinned ? '1' : '0'; ?>"
              data-next-status="<?php echo htmlspecialchars($nextStatus ?? ''); ?>">
              <div class="item-image">
                <img src="logo.jpg" alt="Order">
              </div>
              <div class="item-details">
                <div class="item-name">Order #<?php echo intval($order['order_id']); ?></div>
                <div class="item-attrs">No items available</div>
                <div class="item-bottom">
                  <div class="item-price">₱<?php echo number_format((float)$order['total_amount'], 2); ?></div>
                  <div class="item-qty">Qty: 0</div>
                </div>
              </div>
            </div>
          <?php endif; ?>

          <div class="timeline">
            <?php foreach ($linearFlow as $idx => $step): ?>
              <?php
                $stepClass = 'step';
                if ($status !== 'cancelled' && $timelineIndex !== false && $idx < $timelineIndex) {
                  $stepClass = 'step done';
                } elseif ($status !== 'cancelled' && $timelineIndex !== false && $idx === $timelineIndex) {
                  $stepClass = 'step current';
                }
              ?>
              <div class="<?php echo $stepClass; ?>"><?php echo htmlspecialchars($statusLabels[$step]); ?></div>
            <?php endforeach; ?>
          </div>

          <div class="actions-row">
            <div class="action-left">More</div>
            <div style="text-align: right; margin-right: auto; padding-right: 16px;">
              <div style="font-size: 12px; color: #666; margin-bottom: 4px;">Total(<?php echo count($order['items']); ?> Item<?php echo count($order['items']) > 1 ? 's' : ''; ?>):</div>
              <div style="font-size: 16px; font-weight: 600; color: #333;">₱<?php echo number_format((float)$order['total_amount'], 2); ?></div>
            </div>
            <div class="action-buttons">
              <?php if (strtolower((string)$status) === 'reviewed' && $firstProductId > 0): ?>
                <a class="action-btn primary" href="admin_manage_reviews.php?focus_product_id=<?php echo intval($firstProductId); ?><?php echo $targetReviewId > 0 ? '&focus_review_id=' . intval($targetReviewId) : ''; ?>">Open this Review</a>
              <?php endif; ?>
              <?php if ($nextStatus): ?>
                <form method="POST" class="order-action-form" style="display:inline;" data-confirm-type="warning" data-confirm-title="Change Order Status" data-confirm-message="Move this order to <?php echo htmlspecialchars($statusLabels[$nextStatus]); ?>?">
                  <input type="hidden" name="action" value="advance_status">
                  <input type="hidden" name="order_id" value="<?php echo intval($order['order_id']); ?>">
                  <button type="submit" class="action-btn primary">Move to <?php echo htmlspecialchars($statusLabels[$nextStatus]); ?></button>
                </form>
              <?php endif; ?>
              <?php if ($canCancel): ?>
                <form method="POST" class="order-action-form" style="display:inline;" data-confirm-type="warning" data-confirm-title="Cancel Order" data-confirm-message="Cancel this order? Only pending orders can be cancelled.">
                  <input type="hidden" name="action" value="cancel_order">
                  <input type="hidden" name="order_id" value="<?php echo intval($order['order_id']); ?>">
                  <button type="submit" class="action-btn danger">Cancel Order</button>
                </form>
              <?php endif; ?>
              <?php if ($canArchive): ?>
                <form method="POST" class="order-action-form" style="display:inline;" data-confirm-type="warning" data-confirm-title="Archive Reviewed Order" data-confirm-message="Archive this reviewed order? This will not delete any user review.">
                  <input type="hidden" name="action" value="archive_order">
                  <input type="hidden" name="order_id" value="<?php echo intval($order['order_id']); ?>">
                  <button type="submit" class="action-btn">Archive</button>
                </form>
              <?php endif; ?>
              <?php if ($canMoveToBin): ?>
                <form method="POST" class="order-action-form" style="display:inline;" data-confirm-type="warning" data-confirm-title="Move to Bin" data-confirm-message="Move this archived order to bin?">
                  <input type="hidden" name="action" value="move_to_bin">
                  <input type="hidden" name="order_id" value="<?php echo intval($order['order_id']); ?>">
                  <button type="submit" class="action-btn">Move to Bin</button>
                </form>
              <?php endif; ?>
              <?php if ($canUnarchive): ?>
                <form method="POST" class="order-action-form" style="display:inline;" data-confirm-type="warning" data-confirm-title="Undo Archive" data-confirm-message="Restore this reviewed order from bin?">
                  <input type="hidden" name="action" value="unarchive_order">
                  <input type="hidden" name="order_id" value="<?php echo intval($order['order_id']); ?>">
                  <button type="submit" class="action-btn">Undo Archive</button>
                </form>
              <?php endif; ?>
            </div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
      <div id="paginationContainer" class="pagination-wrap"></div>
    </div>
  </div>

  <div class="modal" id="orderDetailModal">
    <div class="modal-content">
      <div class="modal-header">
        <div class="back-arrow" onclick="closeOrderDetail()">‹</div>
        <div class="header-title" id="modalTitle">Order Details</div>
      </div>

      <div class="status-badge">
        <div class="status-badge-icon">●</div>
        <div class="status-badge-content">
          <h3 id="statusTitle">Order Status</h3>
          <p id="statusMessage">Status details</p>
        </div>
      </div>

      <div class="delivery-info">
        <div class="delivery-info-title">📍 <span id="recipientLabel">Delivering To</span> <span id="recipientName">Recipient</span></div>
        <div id="recipientPhone" class="delivery-phone"></div>
        <div id="recipientAddress" class="delivery-address"></div>
        <div id="deliveryType" class="delivery-type" style="margin-top: 8px; font-size: 13px; color: #666;"></div>
        <div id="scheduleInfo" class="schedule-info" style="margin-top: 4px; font-size: 13px; color: #666;"></div>
        <div id="sellerAddress" class="seller-address" style="margin-top: 8px; font-size: 13px; color: #666;"></div>
      </div>

      <div class="order-detail-item">
        <div class="detail-store-header">
          <div class="detail-store-info">
            <div class="store-icon"><img src="logo.jpg" alt="Logo"></div>
            <div class="store-name" id="detailStoreName">Andrea Mystery Shop</div>
            <div class="store-arrow">›</div>
          </div>
          <div class="detail-order-status" id="detailStatus">Status</div>
        </div>

        <div class="detail-item-content">
          <div class="detail-item-image">
            <img id="detailItemImage" src="" alt="Product">
          </div>
          <div class="detail-item-details">
            <div class="detail-item-name" id="detailItemName">Product Name</div>
            <div class="detail-item-attrs">Variant details • Default</div>
            <div class="item-badges">
              <div class="badge">30 Days Free Returns</div>
            </div>
            <div class="detail-item-price" id="detailPrice">₱0.00</div>
            <div class="detail-item-qty" id="detailQty">Qty: 1</div>
          </div>
        </div>
      </div>

      <div class="order-total">
        <div class="total-row">
          <span class="total-label">Subtotal</span>
          <span class="total-value" id="subtotal">₱0.00</span>
        </div>
        <div class="total-row">
          <span class="total-label">Shipping</span>
          <span class="total-value">₱0.00</span>
        </div>
        <div class="total-final">
          <span class="total-final-label">Total</span>
          <span class="total-final-value" id="totalAmount">₱0.00</span>
        </div>
      </div>

      <div class="user-review-card" id="userReviewCard" style="display:none;">
        <div class="user-review-title">Customer Review(s)</div>
        <div class="user-review-list" id="userReviewList"></div>
      </div>

      <div class="order-number">
        <span class="order-number-label">Order No.</span>
        <span class="order-number-value" id="orderNumber" onclick="copyOrderNumber()">- Copy</span>
      </div>

      <div class="view-summary" onclick="toggleOrderSummary()">
        <span class="view-summary-label">View Order Summary</span>
        <span class="view-summary-arrow" id="summaryArrow">∨</span>
      </div>

      <div id="orderTimeline" style="display: none;">
        <div style="padding: 16px; background: #fff; margin: 8px 16px 0; border-radius: 8px;">
          <div style="margin-bottom: 16px;">
            <div style="font-size: 12px; color: #999; margin-bottom: 4px;">Order Time</div>
            <div style="font-size: 14px; color: #333; font-weight: 600;" id="orderTime">-</div>
          </div>
          <div style="margin-bottom: 16px;">
            <div style="font-size: 12px; color: #999; margin-bottom: 4px;">Ship Time</div>
            <div style="font-size: 14px; color: #333; font-weight: 600;" id="shipTime">-</div>
          </div>
          <div style="margin-bottom: 16px;">
            <div style="font-size: 12px; color: #999; margin-bottom: 4px;">Completed Time</div>
            <div style="font-size: 14px; color: #333; font-weight: 600;" id="completedTime">-</div>
          </div>
          <div id="cancelledTimeSection" style="margin-bottom: 16px; display: none;">
            <div style="font-size: 12px; color: #999; margin-bottom: 4px;">Cancelled Time</div>
            <div style="font-size: 14px; color: #333; font-weight: 600;" id="cancelledTime">-</div>
          </div>
        </div>
      </div>

      <div class="order-actions" id="orderActions">
        <form id="modalAdvanceForm" class="order-action-form" method="POST" style="display:none;" data-confirm-type="warning" data-confirm-title="Change Order Status" data-confirm-message="Move this order to next status?">
          <input type="hidden" name="action" value="advance_status">
          <input type="hidden" name="order_id" id="modalAdvanceOrderId" value="0">
          <button type="submit" id="modalAdvanceBtn" class="action-primary">Move to Next</button>
        </form>
        <form id="modalCancelForm" class="order-action-form" method="POST" style="display:none;" data-confirm-type="warning" data-confirm-title="Cancel Order" data-confirm-message="Cancel this order? Only pending orders can be cancelled.">
          <input type="hidden" name="action" value="cancel_order">
          <input type="hidden" name="order_id" id="modalCancelOrderId" value="0">
          <button type="submit" class="action-danger">Cancel Order</button>
        </form>
        <form id="modalArchiveForm" class="order-action-form" method="POST" style="display:none;" data-confirm-type="warning" data-confirm-title="Archive Reviewed Order" data-confirm-message="Archive this reviewed order? This will not delete any user review.">
          <input type="hidden" name="action" value="archive_order">
          <input type="hidden" name="order_id" id="modalArchiveOrderId" value="0">
          <button type="submit">Archive</button>
        </form>
        <form id="modalMoveToBinForm" class="order-action-form" method="POST" style="display:none;" data-confirm-type="warning" data-confirm-title="Move to Bin" data-confirm-message="Move this archived order to bin?">
          <input type="hidden" name="action" value="move_to_bin">
          <input type="hidden" name="order_id" id="modalMoveToBinOrderId" value="0">
          <button type="submit">Move to Bin</button>
        </form>
        <form id="modalUnarchiveForm" class="order-action-form" method="POST" style="display:none;" data-confirm-type="warning" data-confirm-title="Undo Archive" data-confirm-message="Restore this reviewed order from bin?">
          <input type="hidden" name="action" value="unarchive_order">
          <input type="hidden" name="order_id" id="modalUnarchiveOrderId" value="0">
          <button type="submit">Undo Archive</button>
        </form>
        <button type="button" id="modalNoAction" class="action-disabled" style="display:none;" disabled>No Admin Action Available</button>
      </div>
    </div>
  </div>

  <div id="localSwal" class="swal-overlay" role="dialog" aria-modal="true" aria-live="polite">
    <div class="swal-card">
      <div id="localSwalIcon" class="swal-icon success">✓</div>
      <div id="localSwalTitle" class="swal-title">Success</div>
      <div id="localSwalText" class="swal-text"></div>
      <div id="localSwalActions" class="swal-actions">
        <button id="localSwalCancel" type="button" class="swal-btn secondary" style="display:none;">Cancel</button>
        <button id="localSwalConfirm" type="button" class="swal-btn primary">OK</button>
      </div>
    </div>
  </div>

  <div id="mediaViewerOverlay" class="media-viewer-overlay" role="dialog" aria-modal="true" aria-label="Media Viewer">
    <button type="button" class="media-viewer-close" onclick="closeMediaViewer()">×</button>
    <div id="mediaViewerContent" class="media-viewer-content"></div>
  </div>


  <script>
    let currentStatus = '<?php echo htmlspecialchars($initialStatus, ENT_QUOTES); ?>';
    let currentSort = 'newest';
    const focusOrderId = <?php echo (int)$focusOrderId; ?>;
    
    const pageSize = 5;
    let currentPage = 1;

    const statusConfig = {
      pending: {
        title: 'To Pay',
        titleText: 'Payment Pending',
        message: 'Please complete your payment to process your order.'
      },
      processing: {
        title: 'To Ship',
        titleText: 'Processing',
        message: 'This order is being prepared for shipment.'
      },
      pickup: {
        title: 'Ready for Pickup',
        titleText: 'Ready for Pickup',
        message: 'This order is ready for customer pickup at the scheduled time.'
      },
      pickedup: {
        title: 'Picked Up',
        titleText: 'Picked Up',
        message: 'Customer has picked up this order.'
      },
      shipped: {
        title: 'To Receive',
        titleText: 'Shipped',
        message: 'The order is on the way to the recipient.'
      },
      delivered: {
        title: 'Order Delivered',
        titleText: 'Delivered',
        message: 'The package has been delivered successfully.'
      },
      received: {
        title: 'Order Received',
        titleText: 'Received',
        message: 'Customer marked this order as received.'
      },
      reviewed: {
        title: 'Reviewed',
        titleText: 'Reviewed',
        message: 'Customer has submitted a review for this order.'
      },
      cancelled: {
        title: 'Order Closed',
        titleText: 'Order Closed',
        message: 'This order was cancelled.'
      },
      archived: {
        title: 'Archived',
        titleText: 'Archived',
        message: 'This reviewed order has been archived by admin.'
      },
      bin: {
        title: 'Binned',
        titleText: 'In Bin',
        message: 'This archived order is in the bin and can be restored.'
      }
    };

    const statusLabels = {
      pending: 'Pending',
      processing: 'Processing',
      shipped: 'Shipped',
      delivered: 'Delivered',
      received: 'Received',
      reviewed: 'Reviewed',
      cancelled: 'Cancelled',
      archived: 'Archived',
      bin: 'Bin'
    };

    function updateEmptyState() {
      const groups = Array.from(document.querySelectorAll('.order-group'));
      const visible = groups.filter((g) => g.style.display !== 'none');
      const empty = document.getElementById('emptyState');
      if (empty) {
        empty.style.display = visible.length === 0 ? 'block' : 'none';
      }
    }

    function getMatchedOrderGroups() {
      return Array.from(document.querySelectorAll('.order-group')).filter((g) => g.dataset.matched === '1');
    }

    function scrollToOrdersTop() {
      const anchor = document.querySelector('.page-container');
      if (anchor && typeof anchor.scrollIntoView === 'function') {
        anchor.scrollIntoView({ behavior: 'smooth', block: 'start' });
      }
      const forceTop = () => {
        window.scrollTo(0, 0);
        document.documentElement.scrollTop = 0;
        document.body.scrollTop = 0;
      };
      forceTop();
      requestAnimationFrame(forceTop);
      setTimeout(forceTop, 60);
    }

    function goToPage(page) {
      const matchedGroups = getMatchedOrderGroups();
      const totalPages = Math.ceil(matchedGroups.length / pageSize);
      currentPage = Math.max(1, Math.min(page, totalPages || 1));
      applyPagination();
      scrollToOrdersTop();
    }

    function renderPagination(totalItems) {
      const container = document.getElementById('paginationContainer');
      if (!container) return;

      const totalPages = Math.ceil(totalItems / pageSize);
      if (totalPages <= 1) {
        container.innerHTML = '';
        return;
      }

      let html = '';
      html += `<button class="pagination-btn" onclick="goToPage(${currentPage - 1})" ${currentPage === 1 ? 'disabled' : ''}>« Prev</button>`;

      const start = Math.max(1, currentPage - 2);
      const end = Math.min(totalPages, currentPage + 2);

      if (start > 1) {
        html += `<button class="pagination-btn" onclick="goToPage(1)">1</button>`;
        if (start > 2) html += `<span style="align-self: center; color: #999;">...</span>`;
      }

      for (let i = start; i <= end; i++) {
        html += `<button class="pagination-btn ${i === currentPage ? 'active' : ''}" onclick="goToPage(${i})">${i}</button>`;
      }

      if (end < totalPages) {
        if (end < totalPages - 1) html += `<span style="align-self: center; color: #999;">...</span>`;
        html += `<button class="pagination-btn" onclick="goToPage(${totalPages})">${totalPages}</button>`;
      }

      html += `<button class="pagination-btn" onclick="goToPage(${currentPage + 1})" ${currentPage === totalPages ? 'disabled' : ''}>Next »</button>`;
      container.innerHTML = html;
    }

    function applyPagination() {
      const matchedGroups = getMatchedOrderGroups();
      const totalPages = Math.ceil(matchedGroups.length / pageSize);
      const startIdx = (currentPage - 1) * pageSize;
      const endIdx = startIdx + pageSize;

      document.querySelectorAll('.order-group').forEach((group) => {
        group.style.display = 'none';
      });

      matchedGroups.forEach((group, idx) => {
        if (idx >= startIdx && idx < endIdx) {
          group.style.display = 'block';
        }
      });

      renderPagination(matchedGroups.length);
      updateEmptyState();
    }

    function applyFilters() {
      const query = (document.getElementById('searchInput')?.value || '').toLowerCase().trim();
      document.querySelectorAll('.order-group').forEach((group) => {
        const status = group.dataset.status || '';
        const isArchived = group.dataset.archived === '1';
        const isBinned = group.dataset.binned === '1';
        const orderId = (group.dataset.orderId || '').toLowerCase();
        const customer = group.dataset.customer || '';
        const products = group.dataset.products || '';

        const statusMatch = currentStatus === 'all'
          ? (!isArchived && !isBinned)
          : (currentStatus === 'archived'
              ? (isArchived && !isBinned)
              : (currentStatus === 'bin'
                  ? isBinned
                  : (!isArchived && !isBinned && status === currentStatus)));
        const searchMatch = !query || orderId.includes(query) || customer.includes(query) || products.includes(query);
        group.dataset.matched = (statusMatch && searchMatch) ? '1' : '0';
      });

      applySorting();
      applyPagination();
    }

    function toggleFilterDropdown() {
      const dropdown = document.getElementById('filterDropdown');
      if (!dropdown) {
        return;
      }
      dropdown.classList.toggle('active');

      document.querySelectorAll('.dropdown-item').forEach((item) => item.classList.remove('active'));
      const items = document.querySelectorAll('.dropdown-item');
      if (currentSort === 'newest' && items[0]) {
        items[0].classList.add('active');
      }
      if (currentSort === 'oldest' && items[1]) {
        items[1].classList.add('active');
      }
    }

    function closeFilterDropdown() {
      const dropdown = document.getElementById('filterDropdown');
      if (dropdown) {
        dropdown.classList.remove('active');
      }
    }

    function applySorting() {
      const container = document.getElementById('ordersContainer');
      if (!container) {
        return;
      }

      const matchedOrders = Array.from(container.querySelectorAll('.order-group')).filter((group) => group.dataset.matched === '1');
      const unmatchedOrders = Array.from(container.querySelectorAll('.order-group')).filter((group) => group.dataset.matched !== '1');
      
      matchedOrders.sort((a, b) => {
        const aId = parseInt(a.dataset.orderId || '0', 10);
        const bId = parseInt(b.dataset.orderId || '0', 10);
        return currentSort === 'oldest' ? aId - bId : bId - aId;
      });

      [...matchedOrders, ...unmatchedOrders].forEach((orderEl) => container.appendChild(orderEl));
    }

    function applyDateSort(sortType) {
      currentSort = sortType;
      closeFilterDropdown();
      applyFilters();
      
      // Validate currentPage is still valid after sort reorders items
      const matchedGroups = getMatchedOrderGroups();
      const totalPages = Math.ceil(matchedGroups.length / pageSize);
      currentPage = Math.max(1, Math.min(currentPage, totalPages || 1));
      applyPagination();
    }

    function filterByStatus(status) {
      currentStatus = status;
      currentPage = 1;
      document.querySelectorAll('.tab').forEach((t) => t.classList.remove('active'));
      const active = document.querySelector(`.tab[data-status="${status}"]`);
      if (active) {
        active.classList.add('active');
      }
      applyFilters();
    }

    function focusTargetOrder(orderId) {
      if (!orderId) return;
      const target = document.querySelector(`.order-group[data-order-id="${orderId}"]`);
      if (!target) return;

      document.querySelectorAll('.order-group.order-focus').forEach(el => el.classList.remove('order-focus'));
      target.classList.add('order-focus');
      target.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }

    function toggleTopbarMenu(event) {
      event.stopPropagation();
      const dropdown = document.getElementById('topbarMenuDropdown');
      if (dropdown) {
        dropdown.classList.toggle('active');
      }
    }

    function formatDisplayDate(input) {
      if (!input) return '-';
      const parsed = new Date(input.replace(' ', 'T'));
      if (Number.isNaN(parsed.getTime())) return input;
      return parsed.toLocaleString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
      });
    }

    function formatPeso(value) {
      const amount = Number(value || 0);
      if (!Number.isFinite(amount)) return '0';
      return amount.toLocaleString('en-PH', { minimumFractionDigits: 0, maximumFractionDigits: 2 });
    }

    function showLocalSweetAlert(options) {
      const overlay = document.getElementById('localSwal');
      const icon = document.getElementById('localSwalIcon');
      const titleEl = document.getElementById('localSwalTitle');
      const textEl = document.getElementById('localSwalText');
      const actions = document.getElementById('localSwalActions');
      const confirmBtn = document.getElementById('localSwalConfirm');
      const cancelBtn = document.getElementById('localSwalCancel');
      if (!overlay || !icon || !titleEl || !textEl || !actions || !confirmBtn || !cancelBtn) {
        return;
      }

      const type = options.type || 'success';
      const hasCancel = !!options.showCancel;

      icon.className = `swal-icon ${type}`;
      icon.textContent = type === 'error' ? '!' : (type === 'warning' ? '?' : '✓');
      titleEl.textContent = options.title || 'Notice';
      textEl.textContent = options.text || '';

      confirmBtn.textContent = options.confirmText || 'OK';
      cancelBtn.textContent = options.cancelText || 'Cancel';
      cancelBtn.style.display = hasCancel ? 'inline-block' : 'none';
      actions.className = hasCancel ? 'swal-actions two' : 'swal-actions';

      confirmBtn.onclick = () => {
        overlay.classList.remove('show');
        if (typeof options.onConfirm === 'function') {
          options.onConfirm();
        }
      };

      cancelBtn.onclick = () => {
        overlay.classList.remove('show');
        if (typeof options.onCancel === 'function') {
          options.onCancel();
        }
      };

      overlay.classList.add('show');
    }

    function bindOrderActionConfirmations() {
      document.querySelectorAll('.order-action-form').forEach((form) => {
        form.addEventListener('submit', (event) => {
          event.preventDefault();
          event.stopPropagation();

          const title = form.dataset.confirmTitle || 'Confirm Action';
          const message = form.dataset.confirmMessage || 'Are you sure you want to continue?';
          const type = form.dataset.confirmType || 'warning';

          showLocalSweetAlert({
            type,
            title,
            text: message,
            showCancel: true,
            confirmText: 'Yes, Continue',
            cancelText: 'No',
            onConfirm: () => form.submit()
          });
        });
      });
    }

    function openMediaViewer(mediaUrl, mediaType) {
      const overlay = document.getElementById('mediaViewerOverlay');
      const content = document.getElementById('mediaViewerContent');
      if (!overlay || !content || !mediaUrl) return;

      content.innerHTML = '';
      const isVideo = String(mediaType || '').includes('video/');

      if (isVideo) {
        const video = document.createElement('video');
        video.src = mediaUrl;
        video.controls = true;
        video.autoplay = true;
        video.playsInline = true;
        content.appendChild(video);
      } else {
        const img = document.createElement('img');
        img.src = mediaUrl;
        img.alt = 'Review media full view';
        content.appendChild(img);
      }

      overlay.classList.add('active');
    }

    function closeMediaViewer() {
      const overlay = document.getElementById('mediaViewerOverlay');
      const content = document.getElementById('mediaViewerContent');
      if (!overlay || !content) return;
      content.innerHTML = '';
      overlay.classList.remove('active');
    }

    function generateVideoThumbFromUrlAO(url) {
      return new Promise((resolve) => {
        const source = String(url || '').trim();
        if (!source) {
          resolve('');
          return;
        }

        const video = document.createElement('video');
        video.preload = 'metadata';
        video.muted = true;
        video.playsInline = true;
        video.src = source;

        const finish = (thumb) => {
          video.removeAttribute('src');
          video.load();
          resolve(thumb || '');
        };

        video.addEventListener('loadedmetadata', () => {
          const duration = Number(video.duration || 0);
          const captureAt = duration > 0.5 ? Math.min(Math.max(duration * 0.1, 0.1), duration - 0.1) : 0;

          const captureFrame = () => {
            try {
              const canvas = document.createElement('canvas');
              canvas.width = video.videoWidth || 320;
              canvas.height = video.videoHeight || 180;
              const context = canvas.getContext('2d');
              if (!context) {
                finish('');
                return;
              }
              context.drawImage(video, 0, 0, canvas.width, canvas.height);
              finish(canvas.toDataURL('image/jpeg', 0.9));
            } catch (error) {
              finish('');
            }
          };

          if (captureAt > 0) {
            video.addEventListener('seeked', captureFrame, { once: true });
            video.currentTime = captureAt;
          } else {
            captureFrame();
          }
        }, { once: true });

        video.addEventListener('error', () => finish(''), { once: true });
      });
    }

    function maskReviewerName(name) {
      const raw = String(name || '').trim();
      if (!raw) return 'U***r';
      if (raw.length === 1) return `${raw}***`;
      if (raw.length === 2) return `${raw.charAt(0)}***${raw.charAt(1)}`;
      return `${raw.slice(0, 2)}***${raw.slice(-1)}`;
    }

    function clearReviewedOrderReviews() {
      const card = document.getElementById('userReviewCard');
      const list = document.getElementById('userReviewList');
      if (card) card.style.display = 'none';
      if (list) list.innerHTML = '';
    }

    function goToManageSpecificReview(reviewId, productId) {
      const normalizedProductId = Number(productId) || 0;
      const normalizedReviewId = Number(reviewId) || 0;
      let target = 'admin_manage_reviews.php';
      if (normalizedProductId > 0) {
        target += '?focus_product_id=' + encodeURIComponent(String(normalizedProductId));
        if (normalizedReviewId > 0) {
          target += '&focus_review_id=' + encodeURIComponent(String(normalizedReviewId));
        }
      }
      window.location.href = target;
    }

    async function loadReviewedOrderReviews(status, productId) {
      clearReviewedOrderReviews();
      if (status !== 'reviewed' || !productId) {
        return;
      }

      try {
        const response = await fetch(`api/get-reviews.php?product_id=${encodeURIComponent(productId)}`);
        const data = await response.json();
        if (!data || !data.success || !Array.isArray(data.reviews) || data.reviews.length === 0) {
          return;
        }

        const card = document.getElementById('userReviewCard');
        const list = document.getElementById('userReviewList');
        if (!card || !list) {
          return;
        }

        data.reviews.forEach((review) => {
          const item = document.createElement('div');
          item.className = 'user-review-item';

          const meta = document.createElement('div');
          meta.className = 'user-review-meta';
          meta.textContent = `By: ${review.is_anonymous ? maskReviewerName(review.user_name) : (review.user_name || 'User')} | ${review.created_at || ''} | Review #${Number(review.review_id) || 0}`;
          item.appendChild(meta);

          const rating = document.createElement('div');
          rating.className = 'user-review-rating';
          const stars = Math.max(0, Math.min(5, parseInt(review.rating, 10) || 0));
          rating.textContent = `Rating: ${'★'.repeat(stars)}${'☆'.repeat(5 - stars)}`;
          item.appendChild(rating);

          const text = document.createElement('div');
          text.className = 'user-review-text';
          text.textContent = review.review_text || 'No review text provided.';
          item.appendChild(text);

          const files = Array.isArray(review.media_files) ? review.media_files : [];
          if (files.length > 0) {
            const mediaWrap = document.createElement('div');
            mediaWrap.className = 'user-review-media';

            files.forEach((file) => {
              if (!file || !file.url) return;
              const mediaUrl = String(file.url);
              const mediaType = String(file.media_type || '');

              const tile = document.createElement('button');
              tile.type = 'button';
              tile.className = 'user-review-media-item';
              tile.addEventListener('click', () => openMediaViewer(mediaUrl, mediaType));

              const viewBadge = document.createElement('div');
              viewBadge.className = 'user-review-media-view-badge';
              viewBadge.textContent = 'VIEW';

              if (mediaType.includes('video/')) {
                const thumb = document.createElement('img');
                thumb.alt = 'Review video thumbnail';
                tile.appendChild(thumb);
                tile.appendChild(viewBadge);

                generateVideoThumbFromUrlAO(mediaUrl).then((thumbUrl) => {
                  thumb.src = thumbUrl || 'logo.jpg';
                });
              } else {
                const img = document.createElement('img');
                img.src = mediaUrl;
                img.alt = 'Review media';
                tile.appendChild(img);
                tile.appendChild(viewBadge);
              }

              mediaWrap.appendChild(tile);
            });

            item.appendChild(mediaWrap);
          }

          const manageBtn = document.createElement('button');
          manageBtn.type = 'button';
          manageBtn.className = 'action-btn primary review-manage-btn';
          manageBtn.textContent = 'Manage This Review';
          manageBtn.addEventListener('click', () => goToManageSpecificReview(Number(review.review_id) || 0, Number(review.product_id || productId) || 0));
          const manageActions = document.createElement('div');
          manageActions.className = 'review-manage-actions';
          manageActions.appendChild(manageBtn);
          item.appendChild(manageActions);

          list.appendChild(item);
        });

        card.style.display = 'block';
      } catch (error) {
        clearReviewedOrderReviews();
      }
    }

    function openOrderDetail(element) {
      const orderId = element.dataset.orderId || '';
      const productId = Number(element.dataset.productId || 0);
      const status = element.dataset.orderStatus || 'cancelled';
      const isArchived = element.dataset.isArchived === '1';
      const isBinned = element.dataset.isBinned === '1';
      const storeName = element.dataset.storeName || 'Andrea Mystery Shop';
      const itemCount = parseInt(element.dataset.itemCount || '0', 10);
      const totalAmount = element.dataset.totalAmount || '0.00';
      const nextStatus = element.dataset.nextStatus || '';
      const deliveryType = element.dataset.deliveryType || '';
      const scheduleDate = element.dataset.scheduleDate || '';
      const scheduleSlot = element.dataset.scheduleSlot || '';

      const itemName = element.querySelector('.item-name')?.textContent || `Order #${orderId}`;
      const itemPrice = element.querySelector('.item-price')?.textContent || `₱${formatPeso(totalAmount)}`;
      const itemQty = element.querySelector('.item-qty')?.textContent || 'Qty: 1';
      const itemImage = element.querySelector('.item-image img')?.src || 'logo.jpg';

      const config = isBinned ? statusConfig.bin : (isArchived ? statusConfig.archived : (statusConfig[status] || statusConfig.cancelled));

      document.getElementById('detailItemName').textContent = itemName;
      document.getElementById('detailPrice').textContent = itemPrice;
      document.getElementById('detailQty').textContent = itemQty;
      document.getElementById('detailItemImage').src = itemImage;
      document.getElementById('detailStoreName').textContent = storeName;
      document.getElementById('orderNumber').textContent = `${orderId} Copy`;
      document.getElementById('subtotal').textContent = itemPrice;
      document.getElementById('totalAmount').textContent = `₱${formatPeso(totalAmount)}`;
      document.getElementById('detailStatus').textContent = config.title;
      document.getElementById('modalTitle').textContent = config.title;
      document.getElementById('statusTitle').textContent = config.titleText;
      document.getElementById('statusMessage').textContent = config.message;

      const recipientLabelEl = document.getElementById('recipientLabel');
      if (recipientLabelEl) {
        if (status === 'reviewed') {
          recipientLabelEl.textContent = 'Reviewed By';
        } else if (status === 'cancelled') {
          recipientLabelEl.textContent = 'Cancelled By';
        } else if (status === 'delivered' || status === 'received') {
          recipientLabelEl.textContent = 'Received By';
        } else {
          recipientLabelEl.textContent = 'Delivering To';
        }
      }

      document.getElementById('recipientName').textContent = element.dataset.recipientName || 'Recipient';
      document.getElementById('recipientPhone').textContent = element.dataset.recipientPhone || '(+63) 000000000';
      document.getElementById('recipientAddress').textContent = element.dataset.recipientAddress || 'Address not available';

      // Update delivery info
      const deliveryTypeEl = document.getElementById('deliveryType');
      const scheduleInfoEl = document.getElementById('scheduleInfo');
      const sellerAddressEl = document.getElementById('sellerAddress');

      if (deliveryType) {
        deliveryTypeEl.textContent = `Delivery Type: ${deliveryType.charAt(0).toUpperCase() + deliveryType.slice(1)}`;
        deliveryTypeEl.style.display = 'block';
      } else {
        deliveryTypeEl.style.display = 'none';
      }

      if (scheduleDate && scheduleSlot) {
        const formattedDate = new Date(scheduleDate).toLocaleDateString('en-US', { 
          weekday: 'long', 
          year: 'numeric', 
          month: 'long', 
          day: 'numeric' 
        });
        scheduleInfoEl.textContent = `Scheduled for: ${formattedDate} at ${scheduleSlot}`;
        scheduleInfoEl.style.display = 'block';
      } else {
        scheduleInfoEl.style.display = 'none';
      }

      // Mock seller address
      sellerAddressEl.textContent = 'Seller Address: 123 Mystery Lane, Enigma City, Philippines';
      sellerAddressEl.style.display = 'block';
      loadReviewedOrderReviews(status, productId);

      const orderDateValue = element.dataset.orderDate || '';
      const baseDate = new Date(orderDateValue.replace(' ', 'T'));
      const orderDate = Number.isNaN(baseDate.getTime()) ? new Date() : baseDate;
      const shipDate = new Date(orderDate);
      const completedDate = new Date(orderDate);
      const cancelledDate = new Date(orderDate);

      document.getElementById('orderTime').textContent = formatDisplayDate(orderDate.toISOString());

      if (status === 'pending' || status === 'cancelled') {
        document.getElementById('shipTime').textContent = '-';
      } else {
        shipDate.setDate(shipDate.getDate() + 2);
        document.getElementById('shipTime').textContent = formatDisplayDate(shipDate.toISOString());
      }

      if (status === 'delivered' || status === 'received' || status === 'reviewed') {
        completedDate.setDate(completedDate.getDate() + 5);
        document.getElementById('completedTime').textContent = formatDisplayDate(completedDate.toISOString());
      } else {
        document.getElementById('completedTime').textContent = '-';
      }

      const cancelledTimeSection = document.getElementById('cancelledTimeSection');
      if (status === 'cancelled') {
        document.getElementById('cancelledTime').textContent = formatDisplayDate(cancelledDate.toISOString());
        cancelledTimeSection.style.display = 'block';
      } else {
        cancelledTimeSection.style.display = 'none';
      }

      const advanceForm = document.getElementById('modalAdvanceForm');
      const advanceOrderId = document.getElementById('modalAdvanceOrderId');
      const advanceBtn = document.getElementById('modalAdvanceBtn');
      const cancelForm = document.getElementById('modalCancelForm');
      const cancelOrderId = document.getElementById('modalCancelOrderId');
      const archiveForm = document.getElementById('modalArchiveForm');
      const archiveOrderId = document.getElementById('modalArchiveOrderId');
      const moveToBinForm = document.getElementById('modalMoveToBinForm');
      const moveToBinOrderId = document.getElementById('modalMoveToBinOrderId');
      const unarchiveForm = document.getElementById('modalUnarchiveForm');
      const unarchiveOrderId = document.getElementById('modalUnarchiveOrderId');
      const noActionBtn = document.getElementById('modalNoAction');

      advanceForm.style.display = 'none';
      cancelForm.style.display = 'none';
      archiveForm.style.display = 'none';
      moveToBinForm.style.display = 'none';
      unarchiveForm.style.display = 'none';
      noActionBtn.style.display = 'none';

      if (nextStatus && !isArchived && !isBinned) {
        advanceOrderId.value = orderId;
        advanceBtn.textContent = `Move to ${statusLabels[nextStatus] || nextStatus}`;
        advanceForm.style.display = 'block';
      }

      if (status === 'pending' && !isArchived && !isBinned) {
        cancelOrderId.value = orderId;
        cancelForm.style.display = 'block';
      }

      if (status === 'reviewed' || status === 'cancelled' || status === 'canceled') {
        if (isBinned) {
          unarchiveOrderId.value = orderId;
          unarchiveForm.style.display = 'block';
        } else if (isArchived) {
          moveToBinOrderId.value = orderId;
          moveToBinForm.style.display = 'block';
        } else {
          archiveOrderId.value = orderId;
          archiveForm.style.display = 'block';
        }
      }

      if (!nextStatus && status !== 'pending' && status !== 'reviewed' && status !== 'cancelled' && status !== 'canceled') {
        noActionBtn.style.display = 'block';
        noActionBtn.textContent = itemCount > 0 ? 'No Admin Action Available' : 'No Items in This Order';
      }

      const timeline = document.getElementById('orderTimeline');
      const summaryArrow = document.getElementById('summaryArrow');
      timeline.style.display = 'none';
      summaryArrow.textContent = '∨';

      document.getElementById('orderDetailModal').classList.add('active');
    }

    function closeOrderDetail() {
      document.getElementById('orderDetailModal').classList.remove('active');
    }

    function toggleOrderSummary() {
      const section = document.getElementById('orderTimeline');
      const arrow = document.getElementById('summaryArrow');
      section.style.display = section.style.display === 'none' ? 'block' : 'none';
      arrow.textContent = section.style.display === 'none' ? '∨' : '∧';
    }

    function copyOrderNumber() {
      const orderNumberText = document.getElementById('orderNumber').textContent.split(' ')[0];
      navigator.clipboard.writeText(orderNumberText);
      alert('Order number copied!');
    }

    document.getElementById('orderDetailModal').addEventListener('click', (event) => {
      if (event.target.id === 'orderDetailModal') {
        closeOrderDetail();
      }
    });

    document.addEventListener('click', (event) => {
      const dropdown = document.getElementById('topbarMenuDropdown');
      const menu = document.querySelector('.topbar-menu');
      if (dropdown && menu && !menu.contains(event.target)) {
        dropdown.classList.remove('active');
      }

      const filterBtn = document.querySelector('.filter-btn');
      const filterDropdown = document.getElementById('filterDropdown');
      if (filterDropdown && filterBtn && !filterBtn.contains(event.target) && !filterDropdown.contains(event.target)) {
        closeFilterDropdown();
      }

      const mediaOverlay = document.getElementById('mediaViewerOverlay');
      if (mediaOverlay && event.target === mediaOverlay) {
        closeMediaViewer();
      }
    });

    document.addEventListener('keydown', (event) => {
      if (event.key === 'Escape') {
        closeMediaViewer();
      }
    });

    bindOrderActionConfirmations();

    const flashData = document.getElementById('flashData');
    if (flashData) {
      const type = flashData.dataset.type === 'error' ? 'error' : 'success';
      const title = type === 'error' ? 'Action Failed' : 'Action Complete';
      const message = flashData.dataset.message || 'Operation complete.';
      showLocalSweetAlert({ type, title, text: message, confirmText: 'OK' });
    }

    filterByStatus(currentStatus);

    if (focusOrderId > 0) {
      const input = document.getElementById('searchInput');
      if (input) {
        input.value = String(focusOrderId);
      }
      applyFilters();
      setTimeout(() => focusTargetOrder(focusOrderId), 120);
    }

  </script>
</body>
</html>
