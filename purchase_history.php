<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: LogIn.php');
    exit;
}
$role = $_SESSION['user_role'] ?? 'user';
if ($role === 'admin') {
    header('Location: admin_dashboard.php');
    exit;
}

require_once 'dbConnection.php';
$userId = intval($_SESSION['user_id']);
$focusOrderId = intval($_GET['order_id'] ?? 0);

function format_peso_display($amount) {
    $value = (float)$amount;
    if (floor($value) == $value) {
        return number_format($value, 0, '.', ',');
    }
    return rtrim(rtrim(number_format($value, 2, '.', ','), '0'), '.');
}

// Handle archive toggle POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'toggle_archive') {
  $orderId = intval($_POST['order_id'] ?? 0);
  if ($orderId > 0) {
                $checkStmt = $conn->prepare('SELECT archived, status FROM orders WHERE order_id = ? AND user_id = ? AND binned = 0 LIMIT 1');
    $checkStmt->bind_param('ii', $orderId, $userId);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();
    if ($checkResult->num_rows > 0) {
      $row = $checkResult->fetch_assoc();
      $currentArchived = intval($row['archived'] ?? 0);
            $status = strtolower(trim((string)($row['status'] ?? '')));
            $canToggleArchive = in_array($status, ['reviewed', 'cancelled'], true) || ($currentArchived === 1);

            if ($canToggleArchive) {
                $newArchived = $currentArchived === 1 ? 0 : 1;
                $updateStmt = $conn->prepare('UPDATE orders SET archived = ? WHERE order_id = ? AND user_id = ?');
                $updateStmt->bind_param('iii', $newArchived, $orderId, $userId);
                $updateStmt->execute();
                $updateStmt->close();
            }
    }
    $checkStmt->close();
  }
  header('Location: purchase_history.php');
  exit;
}

// Fetch orders with items
$query = 'SELECT o.order_id, o.recipient_id, o.order_date, o.status, o.payment_method, o.total_amount, o.archived, 
                 oi.order_item_id, oi.product_id, oi.quantity, oi.price, 
                                 p.product_name, p.product_description,
                                 (SELECT pi.image_url
                                    FROM product_images pi
                                    WHERE pi.product_id = p.product_id
                                        AND LOWER(pi.image_url) REGEXP "\\\\.(jpg|jpeg|png|gif|webp)$"
                                    ORDER BY pi.is_pinned DESC, pi.image_id ASC
                                    LIMIT 1) AS product_image
          FROM orders o
          LEFT JOIN order_items oi ON o.order_id = oi.order_id
          LEFT JOIN products p ON oi.product_id = p.product_id
          WHERE o.user_id = ? AND o.binned = 0
          ORDER BY o.order_date DESC, o.order_id DESC';

$orderStmt = $conn->prepare($query);
$orderStmt->bind_param('i', $userId);
$orderStmt->execute();
$result = $orderStmt->get_result();

// Group orders with their items
$orders = [];
while ($row = $result->fetch_assoc()) {
    $orderId = $row['order_id'];
    if (!isset($orders[$orderId])) {
        $orders[$orderId] = [
            'order_id' => $orderId,
            'order_date' => $row['order_date'],
            'status' => $row['status'],
            'payment_method' => $row['payment_method'],
            'total_amount' => $row['total_amount'],
            'recipient_id' => $row['recipient_id'],
            'archived' => intval($row['archived'] ?? 0),
            'items' => []
        ];
    }
    if ($row['product_id']) {
        $orders[$orderId]['items'][] = [
            'order_item_id' => $row['order_item_id'],
            'product_id' => $row['product_id'],
            'product_name' => $row['product_name'],
            'product_description' => $row['product_description'],
            'product_image' => $row['product_image'],
            'quantity' => $row['quantity'],
            'price' => $row['price']
        ];
    }
}
$orderStmt->close();

// Determine whether a non-empty order item list exists
$hasOrderItems = false;
foreach ($orders as $order) {
    if (!empty($order['items'])) {
        $hasOrderItems = true;
        break;
    }
}

// Fetch recipient data for all orders
$recipientData = [];
foreach ($orders as $order) {
    if ($order['recipient_id'] && !isset($recipientData[$order['recipient_id']])) {
        $recipientQuery = 'SELECT recipient_name, phone_no, street_name, unit_floor, district, city, region 
                          FROM recipients WHERE recipient_id = ? AND user_id = ?';
        $recipientStmt = $conn->prepare($recipientQuery);
        $recipientStmt->bind_param('ii', $order['recipient_id'], $userId);
        $recipientStmt->execute();
        $recipientResult = $recipientStmt->get_result();
        if ($recipientResult->num_rows > 0) {
            $recipientData[$order['recipient_id']] = $recipientResult->fetch_assoc();
        }
        $recipientStmt->close();
    }
}

$statusDisplay = [
    'pending' => 'To Pay',
    'processing' => 'To Ship',
    'shipped' => 'To Receive',
    'delivered' => 'Order Delivered',
    'received' => 'Order Received',
    'reviewed' => 'Reviewed',
    'cancelled' => 'Cancelled',
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>My Orders - Andrea Mystery Shop</title>
    <link rel="stylesheet" href="main.css" />
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        html, body { margin: 0; padding: 0; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f5f5f5; padding-bottom: 16px; }
        
        .page-container { width: calc(100% - 48px); max-width: none; margin: 0 auto; padding: 16px 0; }

        @media (max-width: 768px) {
            .page-container { width: calc(100% - 24px); }
        }
        
        /* Header */
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
        .search-filter-bar {
            position: sticky;
            top: 64px;
            z-index: 110;
            background: #f5f5f5;
            padding-top: 8px;
            padding-bottom: 8px;
        }
        .page-container > .tabs-container { position: sticky; top: 120px; z-index: 105; background: #f5f5f5; }
        .back-arrow { cursor: pointer; font-size: 24px; color: #333; padding: 4px; line-height: 1; }
        .header-title { font-size: 18px; font-weight: 600; color: #333; flex: 1; }
        
        /* Search & Filter Bar */
        .search-filter-bar { display: flex; gap: 10px; align-items: center; margin-bottom: 16px; }
        .search-wrapper { flex: 1; display: flex; align-items: center; background: #fff; border-radius: 10px; padding: 0 12px; border: 1px solid #f0a7a2; }
        .search-wrapper input { flex: 1; border: none; outline: none; padding: 10px 8px; font-size: 14px; background: transparent; }
        .search-wrapper input::placeholder { color: #999; }
        .search-suggestions { box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        .suggestion-item { padding: 12px 16px; border-bottom: 1px solid #f5f5f5; cursor: pointer; transition: background 0.2s; }
        .suggestion-item:last-child { border-bottom: none; }
        .suggestion-item:hover { background: #f9f9f9; }
        .suggestion-label { font-size: 12px; color: #999; margin-bottom: 4px; }
        .suggestion-text { font-size: 13px; color: #333; font-weight: 500; }
        .suggestion-type { font-size: 11px; color: #ccc; float: right; }
        
        .filter-btn { background: #fff; border: 1px solid #ddd; border-radius: 8px; padding: 10px 16px; cursor: pointer; display: flex; align-items: center; gap: 4px; font-size: 14px; color: #333; position: relative; }
        .filter-btn:hover { background: #f9f9f9; }
        
        /* Filter Dropdown Styles */
        .filter-dropdown { position: absolute; top: 100%; right: 0; background: #fff; border: 1px solid #ddd; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); min-width: 200px; z-index: 100; display: none; }
        .filter-dropdown.active { display: block; }
        .dropdown-item { padding: 12px 16px; border-bottom: 1px solid #f5f5f5; cursor: pointer; font-size: 13px; color: #333; transition: background 0.2s; }
        .dropdown-item:last-child { border-bottom: none; }
        .dropdown-item:hover { background: #f9f9f9; }
        .dropdown-item.active { background: #e22a39; color: #fff; font-weight: 600; }
        
        /* Tabs */
        .tabs-container { display: flex; gap: 0; border-bottom: 1px solid #ddd; background: #fff; padding: 0 16px; margin-bottom: 0; border-radius: 12px 12px 0 0; overflow-x: auto; }
        .tab { padding: 12px 16px; border: none; background: transparent; cursor: pointer; font-size: 13px; color: #666; border-bottom: 3px solid transparent; white-space: nowrap; font-weight: 500; }
        .tab.active { color: #e22a39; border-bottom-color: #e22a39; }
        
        /* Orders List */
        .orders-container { background: #fff; border-radius: 12px; overflow: hidden; }
        .order-group { border-bottom: 8px solid #f5f5f5; padding: 16px; }
        .order-group:last-child { border-bottom: none; }
        .order-focus {
            border: 2px solid #2d68d8;
            border-radius: 10px;
            box-shadow: 0 0 0 4px rgba(45, 104, 216, 0.12);
        }
        
        /* Store Header */
        .store-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 16px; padding-bottom: 12px; border-bottom: 1px solid #eee; }
        .store-info { display: flex; align-items: center; gap: 8px; }
        .store-icon { width: 28px; height: 28px; border-radius: 4px; display: flex; align-items: center; justify-content: center; overflow: hidden; }
        .store-icon img { width: 100%; height: 100%; object-fit: contain; }
        .store-name { font-weight: 600; color: #333; font-size: 14px; }
        .store-arrow { color: #999; font-size: 16px; cursor: pointer; }
        .order-status { color: #e22a39; font-weight: 600; font-size: 14px; }
        
        /* Order Items */
        .order-item { display: flex; gap: 12px; padding: 12px 0; border-bottom: 1px solid #f5f5f5; cursor: pointer; transition: background 0.2s; }
        .order-item:hover { background: #f9f9f9; }
        .order-item:last-child { border-bottom: none; }
        .item-image { width: 80px; height: 80px; flex-shrink: 0; background: #f5f5f5; border-radius: 8px; overflow: hidden; border: 1px solid #eee; }
        .item-image img { width: 100%; height: 100%; object-fit: cover; }
        
        .item-details { flex: 1; }
        .item-name { font-size: 13px; color: #333; font-weight: 500; line-height: 1.4; margin-bottom: 6px; }
        .item-attrs { font-size: 12px; color: #999; margin-bottom: 6px; }
        .item-badges { display: flex; flex-wrap: wrap; gap: 6px; margin-bottom: 8px; }
        .badge { background: #f0f8ff; border: 0.5px solid #cce2ff; color: #1a62c3; padding: 3px 8px; border-radius: 4px; font-size: 11px; }
        
        .item-bottom { display: flex; justify-content: space-between; align-items: flex-end; }
        .item-price { font-weight: 600; color: #333; font-size: 14px; }
        .item-qty { font-size: 12px; color: #666; }
        
        .actions-row { display: flex; justify-content: space-between; align-items: center; margin-top: 16px; padding-top: 12px; border-top: 1px solid #f5f5f5; }
        .action-left { color: #999; font-size: 13px; cursor: pointer; }
        .action-buttons { display: flex; gap: 10px; }
        .action-btn { padding: 8px 16px; border-radius: 6px; border: 1px solid #ddd; background: #fff; cursor: pointer; font-size: 12px; font-weight: 600; transition: all 0.2s; }
        .action-btn.secondary { color: #666; border-color: #ddd; }
        .action-btn.secondary:hover { background: #f9f9f9; }
        .action-btn.primary { background: #e22a39; color: #fff; border-color: #e22a39; }
        .action-btn.primary:hover { background: #c20000; }
        
        .empty-state { text-align: center; padding: 60px 20px; color: #999; }
        .empty-state p { margin-bottom: 16px; }

        .pagination-wrap {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 8px;
            padding: 14px 10px 4px;
            flex-wrap: wrap;
        }
        .pagination-btn {
            min-width: 38px;
            height: 38px;
            border: 1px solid #d9e2ef;
            background: #fff;
            color: #334155;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            padding: 0 10px;
        }
        .pagination-btn:hover { background: #f8fafc; }
        .pagination-btn.active {
            background: #2d68d8;
            border-color: #2d68d8;
            color: #fff;
        }
        .pagination-btn:disabled {
            opacity: 0.45;
            cursor: not-allowed;
        }
        
        /* Modal */
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
        
        .more-items { padding: 16px; background: #fff; margin: 8px 16px 16px; border-radius: 8px; }
        .more-items-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
        .more-item { background: #f5f5f5; border-radius: 8px; overflow: hidden; position: relative; }
        .more-item-image { width: 100%; aspect-ratio: 1; background: #eee; overflow: hidden; }
        .more-item-image img { width: 100%; height: 100%; object-fit: cover; }
        .more-item-overlay { position: absolute; top: 0; left: 0; right: 0; bottom: 0; display: flex; align-items: center; justify-content: center; background: rgba(0,0,0,0.1); }
        .more-item-overlay-text { font-size: 20px; }
        
        .order-actions { padding: 16px; background: #fff; margin: 8px 16px 16px; border-radius: 8px; display: flex; gap: 12px; }
        .order-actions button { flex: 1; padding: 12px; border-radius: 6px; border: 1px solid #ddd; background: #fff; cursor: pointer; font-size: 13px; font-weight: 600; transition: all 0.2s; }
        .order-actions button.action-primary { background: #e22a39; color: #fff; border-color: #e22a39; }
        .order-actions button.action-primary:hover { background: #c20000; }
        .order-actions button:hover { background: #f9f9f9; }

        /* Review Modal (Shopee-like mobile layout) */
        .review-modal-content {
            background: #f5f5f5;
            width: 100%;
            height: 100%;
            overflow-y: auto;
            animation: slideUp 0.3s ease;
        }
        .review-form-wrap {
            min-height: 100%;
            display: flex;
            flex-direction: column;
        }
        .review-form-body {
            padding: 14px 16px 140px;
        }
        .review-product-row {
            display: flex;
            align-items: center;
            gap: 12px;
            background: #fff;
            border-radius: 10px;
            padding: 10px;
            margin-bottom: 16px;
        }
        .review-product-thumb {
            width: 52px;
            height: 52px;
            border-radius: 8px;
            object-fit: cover;
            background: #eee;
            border: 1px solid #ededed;
            flex-shrink: 0;
        }
        .review-product-title {
            font-size: 15px;
            font-weight: 600;
            color: #1f1f1f;
            line-height: 1.35;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        .review-product-sub {
            margin-top: 2px;
            font-size: 13px;
            color: #9a9a9a;
        }
        .review-rating-block {
            background: #fff;
            border-radius: 10px;
            padding: 18px 14px;
            margin-bottom: 12px;
            text-align: center;
        }
        .review-stars {
            display: flex;
            justify-content: center;
            flex-direction: row-reverse;
            gap: 8px;
        }
        .review-stars input {
            position: absolute;
            opacity: 0;
            pointer-events: none;
        }
        .review-stars label {
            font-size: 46px;
            line-height: 1;
            color: #c7c7c7;
            cursor: pointer;
            transition: color 0.15s ease;
            user-select: none;
        }
        .review-stars label:hover,
        .review-stars label:hover ~ label,
        .review-stars input:checked ~ label {
            color: #f7b500;
        }
        .review-rating-hint {
            margin-top: 10px;
            font-size: 18px;
            font-weight: 600;
            color: #212121;
        }
        .review-rating-hint .required {
            color: #e22a39;
        }
        .review-field-title {
            font-size: 18px;
            font-weight: 700;
            color: #222;
            margin-bottom: 10px;
        }
        .review-text-wrap {
            background: #fff;
            border-radius: 10px;
            padding: 10px;
            margin-bottom: 18px;
            position: relative;
        }
        .review-textarea {
            width: 100%;
            min-height: 140px;
            border: none;
            resize: none;
            outline: none;
            font-family: inherit;
            font-size: 16px;
            color: #222;
            background: transparent;
            padding: 8px 8px 24px;
        }
        .review-textarea::placeholder {
            color: #acacac;
        }
        .review-char-count {
            position: absolute;
            right: 16px;
            bottom: 12px;
            font-size: 13px;
            color: #9a9a9a;
        }
        .review-upload-wrap {
            background: #fff;
            border-radius: 10px;
            padding: 12px;
            margin-bottom: 18px;
        }
        .review-upload-box {
            border: 1px solid #e6e6e6;
            background: #f7f7f7;
            border-radius: 10px;
            min-height: 140px;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            cursor: pointer;
            padding: 12px;
        }
        .review-upload-icon {
            font-size: 34px;
            line-height: 1;
            color: #8f8f8f;
            margin-bottom: 8px;
        }
        .review-upload-text {
            color: #8f8f8f;
            font-size: 15px;
        }
        .review-upload-note {
            margin-top: 8px;
            color: #a1a1a1;
            font-size: 12px;
            text-align: center;
        }
        .review-preview-media {
            max-width: 100%;
            max-height: 160px;
            border-radius: 8px;
            display: none;
            margin: 0 auto;
        }
        .review-upload-remove {
            margin-top: 10px;
            padding: 7px 12px;
            border: none;
            border-radius: 8px;
            background: #e22a39;
            color: #fff;
            cursor: pointer;
            font-weight: 600;
        }
        .review-anon-row {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 16px;
            color: #2a2a2a;
            margin-bottom: 16px;
        }
        .review-anon-row input[type="checkbox"] {
            width: 22px;
            height: 22px;
            accent-color: #e22a39;
            cursor: pointer;
        }
        .review-privacy-hidden {
            display: none;
        }
        .review-footer {
            position: sticky;
            bottom: 0;
            background: #f5f5f5;
            border-top: 1px solid #e8e8e8;
            padding: 10px 16px 12px;
        }
        .review-submit-btn {
            width: 100%;
            border: none;
            border-radius: 10px;
            background: #f7c31d;
            color: #161616;
            font-size: 14px;
            line-height: 1.2;
            font-weight: 700;
            padding: 11px 14px;
            cursor: pointer;
        }
        .review-step {
            margin-top: 8px;
            text-align: center;
            font-size: 14px;
            color: #9a9a9a;
        }

        @media (min-width: 769px) {
            #reviewModal .review-modal-content {
                max-width: 560px;
                height: auto;
                max-height: 92vh;
                margin: auto;
                border-radius: 12px;
            }
            #reviewModal .modal-header {
                border-radius: 12px 12px 0 0;
            }
            .review-submit-btn {
                font-size: 16px;
            }
        }
        
        .mobile-bottom-nav { position: fixed; bottom: 0; left: 0; right: 0; z-index: 999; background: #fff; border-top: 1px solid #ddd; display: none !important; }
        .mobile-bottom-nav.fixed { display: none !important; }
        body.modal-overlay-active .mobile-bottom-nav.fixed { display: none !important; }
        .mobile-nav-inner { display: flex; justify-content: space-around; align-items: center; padding: 0 6px; width: 100%; height: 50px; }
        .mobile-nav-inner a { text-decoration: none; color: #555; font-size: 11px; display: flex; flex-direction: column; align-items: center; gap: 4px; }
        .mobile-nav-inner a svg { width: 20px; height: 20px; stroke-width: 1.5; }
        .mobile-nav-inner a.active { color: #e22a39; }
        
        /* Sweet Alert Styles */
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
        .swal-icon.warning { background: #fff6e5; color: #bb6a00; }
        .swal-title { font-size: 20px; font-weight: 700; color: #152033; margin-bottom: 8px; }
        .swal-text { font-size: 14px; color: #5f6d7f; margin-bottom: 14px; line-height: 1.45; }
        .swal-actions { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; }
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
        
        @media (max-width: 768px) {
            .page-header { padding: 12px; }
            .tabs-container { padding: 0 12px; }
            .order-group { padding: 12px; }
            .store-header { margin-bottom: 12px; }
        }
    </style>
</head>
<body>
    <div class="page-container">
        <!-- Header -->
        <div class="page-header">
            <div class="back-arrow" onclick="window.location.href='account.php'">‹</div>
            <div class="header-title">My Orders</div>
        </div>
        
        <!-- Search & Filter -->
        <div class="search-filter-bar">
            <div class="search-wrapper" style="position: relative;">
                <input type="text" id="searchInput" placeholder="Search by product name, order ID..." onkeyup="handleSearch(event)" autocomplete="off">
                <div id="searchSuggestions" class="search-suggestions" style="display: none; position: absolute; top: 100%; left: 0; right: 0; background: #fff; border: 1px solid #ddd; border-top: none; border-radius: 0 0 8px 8px; max-height: 300px; overflow-y: auto; z-index: 100;"></div>
            </div>
            <div style="position: relative;">
                <button class="filter-btn" onclick="toggleFilterDropdown()" title="Filter">⚙ Sort</button>
                <div id="filterDropdown" class="filter-dropdown">
                    <div class="dropdown-item" onclick="applyDateSort('newest')">📅 Newest to Oldest</div>
                    <div class="dropdown-item" onclick="applyDateSort('oldest')">📅 Oldest to Newest</div>
                </div>
            </div>
        </div>
        
        <!-- Tabs -->
        <div class="tabs-container">
            <button class="tab active" data-status="all" onclick="filterByStatus('all')">All</button>
            <button class="tab" data-status="pending" onclick="filterByStatus('pending')">To pay</button>
            <button class="tab" data-status="processing" onclick="filterByStatus('processing')">To ship</button>
            <button class="tab" data-status="shipped" onclick="filterByStatus('shipped')">To receive</button>
            <button class="tab" data-status="delivered" onclick="filterByStatus('delivered')">Completed</button>
            <button class="tab" data-status="delivered-unreviewed" onclick="filterByStatus('delivered-unreviewed')">To review</button>
            <button class="tab" data-status="reviewed" onclick="filterByStatus('reviewed')">Reviewed</button>
            <button class="tab" data-status="cancelled" onclick="filterByStatus('cancelled')">Cancelled</button>
            <button class="tab" data-status="archived" onclick="filterByStatus('archived')">Archived</button>
        </div>
        
        <!-- Orders -->
        <div class="orders-container" id="ordersContainer">
            <div class="empty-state" id="noOrdersState" style="<?php echo (empty($orders) || !$hasOrderItems) ? '' : 'display:none;'; ?>">
                <p>No orders yet</p>
                <p style="font-size: 12px; color: #ccc;">Start shopping to see your orders here</p>
                <p style="margin-top: 12px;"><a href="user_dashboard.php" style="color: #e22a39; font-weight: 600; text-decoration: none;">Shop for more</a></p>
            </div>
            <?php if (!empty($orders) && $hasOrderItems): ?>
                <?php foreach ($orders as $order): ?>
                    <div class="order-group" data-order-id="<?php echo intval($order['order_id']); ?>" data-status="<?php echo htmlspecialchars($order['status']); ?>" data-archived="<?php echo intval($order['archived']); ?>">
                        <!-- Store Header -->
                        <div class="store-header">
                            <div class="store-info">
                                <div class="store-icon"><img src="logo.jpg" alt="Logo"></div>
                                <div class="store-name">Andrea Mystery Shop</div>
                                <div class="store-arrow">›</div>
                            </div>
                            <div class="order-status"><?php echo $statusDisplay[$order['status']] ?? ucfirst($order['status']); ?></div>
                        </div>
                        
                        <!-- Order Items -->
                        <?php foreach ($order['items'] as $item): 
                            $recipient = isset($recipientData[$order['recipient_id']]) ? $recipientData[$order['recipient_id']] : null;
                            $recipientName = $recipient ? htmlspecialchars($recipient['recipient_name']) : 'Recipient';
                            $recipientPhone = $recipient ? htmlspecialchars($recipient['phone_no']) : '';
                            $itemImage = !empty($item['product_image']) ? htmlspecialchars($item['product_image']) : 'logo.jpg';
                            $recipientAddress = '';
                            if ($recipient) {
                                $parts = [];
                                if (!empty($recipient['street_name'])) $parts[] = $recipient['street_name'];
                                if (!empty($recipient['unit_floor'])) $parts[] = $recipient['unit_floor'];
                                if (!empty($recipient['district'])) $parts[] = $recipient['district'];
                                if (!empty($recipient['city'])) $parts[] = $recipient['city'];
                                if (!empty($recipient['region'])) $parts[] = $recipient['region'];
                                $recipientAddress = htmlspecialchars(implode(', ', $parts));
                            }
                        ?>
                            <div class="order-item" onclick="openOrderDetail(this)" data-order-id="<?php echo $order['order_id']; ?>" data-item-id="<?php echo $item['order_item_id']; ?>" data-product-id="<?php echo $item['product_id']; ?>" data-order-status="<?php echo htmlspecialchars($order['status']); ?>" data-store-name="Andrea Mystery Shop" data-recipient-id="<?php echo htmlspecialchars($order['recipient_id']); ?>" data-recipient-name="<?php echo $recipientName; ?>" data-recipient-phone="<?php echo $recipientPhone; ?>" data-recipient-address="<?php echo $recipientAddress; ?>">
                                <div class="item-image">
                                    <img src="<?php echo $itemImage; ?>" alt="Product">
                                </div>
                                <div class="item-details">
                                    <div class="item-name"><?php echo htmlspecialchars(substr($item['product_name'], 0, 60)); ?></div>
                                    <div class="item-attrs">Variant details • Default</div>
                                    <div class="item-badges">
                                        <div class="badge">30 Days Free Returns</div>
                                    </div>
                                    <div class="item-bottom">
                                        <div>
                                            <div class="item-price">₱<?php echo format_peso_display($item['price']); ?></div>
                                        </div>
                                        <div class="item-qty">Qty: <?php echo intval($item['quantity']); ?></div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        
                        <!-- Total & Actions -->
                        <div class="actions-row">
                            <div class="action-left">More</div>
                            <div style="text-align: right; margin-right: auto; padding-right: 16px;">
                                <div style="font-size: 12px; color: #666; margin-bottom: 4px;">Total(<?php echo count($order['items']); ?> Item<?php echo count($order['items']) > 1 ? 's' : ''; ?>):</div>
                                <div style="font-size: 16px; font-weight: 600; color: #333;">₱<?php echo format_peso_display($order['total_amount']); ?></div>
                            </div>
                            <div class="action-buttons">
                                <?php if ($order['status'] === 'pending'): ?>
                                    <button class="action-btn secondary" onclick="cancelOrderDirect(<?php echo htmlspecialchars($order['order_id']); ?>, event)">Cancel</button>
                                <?php endif; ?>
                                <?php if (in_array(strtolower((string)$order['status']), ['reviewed', 'cancelled', 'canceled'], true) || intval($order['archived']) === 1): ?>
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="action" value="toggle_archive">
                                        <input type="hidden" name="order_id" value="<?php echo $order['order_id']; ?>">
                                        <button type="submit" class="action-btn secondary" onclick="confirmArchiveToggle(event, <?php echo $order['order_id']; ?>, <?php echo $order['archived']; ?>)"><?php echo $order['archived'] ? 'Unarchive' : 'Archive'; ?></button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        <div id="paginationContainer" class="pagination-wrap" style="display:none;"></div>
    </div>

    <!-- Order Detail Modal -->
    <div class="modal" id="orderDetailModal">
        <div class="modal-content">
            <!-- Modal Header -->
            <div class="modal-header">
                <div class="back-arrow" onclick="closeOrderDetail()">‹</div>
                <div class="header-title" id="modalTitle">Cancellation</div>
            </div>

            <!-- Status Badge -->
            <div class="status-badge">
                <div class="status-badge-icon">●</div>
                <div class="status-badge-content">
                    <h3 id="statusTitle">Order Closed</h3>
                    <p id="statusMessage">You have closed your order. We hope to see you again soon!</p>
                </div>
            </div>

            <!-- Delivery Info -->
            <div class="delivery-info">
                <div class="delivery-info-title">
                    📍 Delivering To <span id="recipientName">Recipient</span>
                </div>
                <div id="recipientPhone" class="delivery-phone"></div>
                <div id="recipientAddress" class="delivery-address"></div>
            </div>

            <!-- Order Item Detail -->
            <div class="order-detail-item">
                <div class="detail-store-header">
                    <div class="detail-store-info">
                        <div class="store-icon"><img src="logo.jpg" alt="Logo"></div>
                        <div class="store-name" id="detailStoreName">Andrea Mystery Shop</div>
                        <div class="store-arrow">›</div>
                    </div>
                    <div class="detail-order-status" id="detailStatus">Cancelled</div>
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

            <!-- Order Total -->
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

            <!-- Order Number -->
            <div class="order-number">
                <span class="order-number-label">Order No.</span>
                <span class="order-number-value" id="orderNumber" onclick="copyOrderNumber()">105776707717222262 Copy</span>
            </div>

            <!-- View Order Summary -->
            <div class="view-summary" onclick="toggleOrderSummary()">
                <span class="view-summary-label">View Order Summary</span>
                <span class="view-summary-arrow" id="summaryArrow">∨</span>
            </div>

            <!-- Order Timeline Section -->
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

            <!-- Action Buttons -->
            <div class="order-actions" id="orderActions">
                <!-- Buttons will be added by JavaScript based on status -->
            </div>
        </div>
    </div>

    <!-- Local Sweet Alert -->
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

    <!-- Review Modal -->
    <div class="modal" id="reviewModal">
        <div class="modal-content review-modal-content">
            <div class="review-form-wrap">
            <div class="modal-header">
                <div class="back-arrow" onclick="closeReviewModal()">‹</div>
                <div class="header-title">Write a Review</div>
            </div>
            
            <form id="reviewForm" class="review-form-body">
                <div class="review-product-row">
                    <img id="reviewProductImage" class="review-product-thumb" src="" alt="Product image">
                    <div>
                        <div id="reviewProductName" class="review-product-title">Product name</div>
                        <div id="reviewProductSub" class="review-product-sub"></div>
                    </div>
                </div>

                <div class="review-rating-block">
                    <div class="review-stars">
                        <input type="radio" id="rating5" name="rating" value="5" required>
                        <label for="rating5" title="5 stars">★</label>
                        <input type="radio" id="rating4" name="rating" value="4" required>
                        <label for="rating4" title="4 stars">★</label>
                        <input type="radio" id="rating3" name="rating" value="3" required>
                        <label for="rating3" title="3 stars">★</label>
                        <input type="radio" id="rating2" name="rating" value="2" required>
                        <label for="rating2" title="2 stars">★</label>
                        <input type="radio" id="rating1" name="rating" value="1" required>
                        <label for="rating1" title="1 star">★</label>
                    </div>
                    <div class="review-rating-hint">Rate your purchase<span class="required">*</span></div>
                </div>

                <div class="review-field-title">Write your review</div>
                <div class="review-text-wrap">
                    <textarea id="reviewTextarea" class="review-textarea" maxlength="300" placeholder="What did you like or dislike about this product?" required></textarea>
                    <div class="review-char-count"><span id="reviewCharCount">0</span>/300</div>
                </div>

                <div class="review-field-title">Add a photo or video</div>
                <div class="review-upload-wrap">
                    <div class="review-upload-box" id="uploadArea">
                        <input type="file" id="reviewMediaInput" accept="image/*,video/*" multiple style="display: none;">
                        <div id="uploadPlaceholder">
                            <div class="review-upload-icon">📷</div>
                            <div class="review-upload-text">Add photo or video</div>
                            <div class="review-upload-note">Multiple files allowed, max total 25MB</div>
                        </div>
                        <div id="uploadPreview" style="display: none; margin-top: 8px; width: 100%;">
                            <img id="uploadPreviewImg" class="review-preview-media" alt="Upload preview">
                            <video id="uploadPreviewVideo" class="review-preview-media" controls></video>
                            <div id="uploadSummary" style="margin-top: 8px; color: #666; font-size: 12px; text-align: center;"></div>
                            <div style="text-align:center;">
                                <button type="button" class="review-upload-remove" onclick="clearMediaUpload()">Remove</button>
                            </div>
                        </div>
                    </div>
                </div>

                <label class="review-anon-row" for="reviewAnonymousToggle">
                    <input type="checkbox" id="reviewAnonymousToggle">
                    <span>Post anonymously</span>
                </label>

                <div class="review-privacy-hidden">
                    <input type="radio" name="review_name_privacy" id="privacyRevealed" value="revealed" checked>
                    <input type="radio" name="review_name_privacy" id="privacyAnonymous" value="anonymous">
                </div>
                
                <input type="hidden" id="hiddenProductId" value="">
            </form>

            <div class="review-footer">
                <button type="button" class="review-submit-btn" onclick="submitReview()">Submit</button>
            </div>
            </div>
        </div>
    </div>

    <!-- Mobile Navigation -->
    <nav class="mobile-bottom-nav fixed">
        <div class="mobile-nav-inner">
            <a href="user_dashboard.php">
                <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M3 10.5L12 3l9 7.5V21a1 1 0 0 1-1 1h-5v-7H9v7H4a1 1 0 0 1-1-1V10.5z" stroke-linecap="round" stroke-linejoin="round"></path></svg>
                <span>Home</span>
            </a>
            <a href="about.php">
                <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor"><circle cx="12" cy="8" r="3"></circle><path d="M6 20v-1a4 4 0 014-4h4a4 4 0 014 4v1" stroke-linecap="round" stroke-linejoin="round"></path></svg>
                <span>Message</span>
            </a>
            <a href="category_products.php">
                <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polygon points="16 8 12 11 8 16 16 8"/></svg>
                <span>Explore</span>
            </a>
            <a href="account.php" class="active">
                <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M12 2l7 4v6c0 5-3.58 9-7 10-3.42-1-7-5-7-10V6l7-4z"></path></svg>
                <span>Account</span>
            </a>
        </div>
    </nav>

    <script>
        // Archive Functions
                function refreshOverlayState() {
                    const detailOpen = document.getElementById('orderDetailModal')?.classList.contains('active');
                    const reviewOpen = document.getElementById('reviewModal')?.classList.contains('active');
                    const alertOpen = document.getElementById('localSwal')?.classList.contains('show');
                    document.body.classList.toggle('modal-overlay-active', !!(detailOpen || reviewOpen || alertOpen));
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
          cancelBtn.style.display = hasCancel ? 'block' : 'none';
          actions.style.gridTemplateColumns = hasCancel ? '1fr 1fr' : 'auto';

          confirmBtn.onclick = () => {
            overlay.classList.remove('show');
                        refreshOverlayState();
            if (typeof options.onConfirm === 'function') {
              options.onConfirm();
            }
          };

          cancelBtn.onclick = () => {
            overlay.classList.remove('show');
                        refreshOverlayState();
            if (typeof options.onCancel === 'function') {
              options.onCancel();
            }
          };

          overlay.classList.add('show');
                    refreshOverlayState();
        }

                function localAlert(type, title, text) {
                    return new Promise((resolve) => {
                        showLocalSweetAlert({
                            type,
                            title,
                            text,
                            confirmText: 'OK',
                            onConfirm: () => resolve(true)
                        });
                    });
                }

                function localConfirm(title, text, confirmText = 'Yes', cancelText = 'Cancel') {
                    return new Promise((resolve) => {
                        showLocalSweetAlert({
                            type: 'warning',
                            title,
                            text,
                            showCancel: true,
                            confirmText,
                            cancelText,
                            onConfirm: () => resolve(true),
                            onCancel: () => resolve(false)
                        });
                    });
                }

        function confirmArchiveToggle(event, orderId, currentArchived) {
          event.preventDefault();
          const action = currentArchived ? 'Unarchive' : 'Archive';
          const message = currentArchived ? 'Restore this order to your active list?' : 'Move this order to archived?';
          showLocalSweetAlert({
            type: 'warning',
            title: action + ' Order',
            text: message,
            showCancel: true,
            confirmText: 'Yes, ' + action,
            cancelText: 'Cancel',
            onConfirm: () => {
              event.target.form.submit();
            }
          });
        }

        let currentStatus = 'all';
        let currentSort = 'newest';
        const pageSize = 5;
        let currentPage = 1;
        const focusOrderId = <?php echo (int)$focusOrderId; ?>;
        let allOrders = [];
        let reviewedOrders = new Set();
        let currentOrderGroup = null;

        function getMatchedOrderGroups() {
            return Array.from(document.querySelectorAll('.order-group')).filter(group => group.dataset.matched === '1');
        }

        function scrollToOrdersTop() {
            const anchor = document.querySelector('.page-container') || document.querySelector('.page-header');
            if (anchor && typeof anchor.scrollIntoView === 'function') {
                anchor.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }

            // Fallback for browsers/devices where smooth scroll can be ignored.
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
            const matched = getMatchedOrderGroups();
            const totalPages = Math.max(1, Math.ceil(matched.length / pageSize));
            currentPage = Math.min(Math.max(1, page), totalPages);
            applyPagination();
            scrollToOrdersTop();
        }

        function renderPagination(totalItems) {
            const container = document.getElementById('paginationContainer');
            if (!container) return;

            if (totalItems <= pageSize) {
                container.style.display = 'none';
                container.innerHTML = '';
                return;
            }

            const totalPages = Math.max(1, Math.ceil(totalItems / pageSize));
            container.style.display = 'flex';

            const prevDisabled = currentPage <= 1 ? 'disabled' : '';
            const nextDisabled = currentPage >= totalPages ? 'disabled' : '';

            const maxVisible = 5;
            let startPage = Math.max(1, currentPage - Math.floor(maxVisible / 2));
            let endPage = startPage + maxVisible - 1;
            if (endPage > totalPages) {
                endPage = totalPages;
                startPage = Math.max(1, endPage - maxVisible + 1);
            }

            let html = `<button class="pagination-btn" ${prevDisabled} onclick="goToPage(${currentPage - 1})">Prev</button>`;
            for (let p = startPage; p <= endPage; p += 1) {
                html += `<button class="pagination-btn ${p === currentPage ? 'active' : ''}" onclick="goToPage(${p})">${p}</button>`;
            }
            html += `<button class="pagination-btn" ${nextDisabled} onclick="goToPage(${currentPage + 1})">Next</button>`;
            container.innerHTML = html;
        }

        function applyPagination() {
            const matched = getMatchedOrderGroups();
            const totalItems = matched.length;
            const totalPages = Math.max(1, Math.ceil(totalItems / pageSize));
            currentPage = Math.min(Math.max(1, currentPage), totalPages);

            const startIndex = (currentPage - 1) * pageSize;
            const endIndex = startIndex + pageSize;

            document.querySelectorAll('.order-group').forEach(group => {
                group.style.display = 'none';
            });

            matched.forEach((group, index) => {
                if (index >= startIndex && index < endIndex) {
                    group.style.display = 'block';
                }
            });

            renderPagination(totalItems);
            updateEmptyState();
        }

        // Check if an order has been reviewed
        async function checkOrderReviewed(productId) {
            try {
                const response = await fetch(`api/get-reviews.php?product_id=${productId}`);
                const data = await response.json();
                if (data.success && data.reviews && data.reviews.length > 0) {
                    return true;
                }
            } catch(e) {
                console.error('Error checking reviews:', e);
            }
            return false;
        }

        // Collect all orders data on page load
        function initializeOrdersData() {
            allOrders = [];
            document.querySelectorAll('.order-group').forEach(group => {
                const orderId = group.querySelector('.order-item')?.dataset.orderId || '';
                const items = [];
                
                group.querySelectorAll('.order-item').forEach(item => {
                    const productName = item.querySelector('.item-name')?.textContent || '';
                    items.push(productName);
                });
                
                if (orderId) {
                    allOrders.push({
                        orderId,
                        items,
                        element: group,
                        status: group.dataset.status
                    });
                }
            });
        }

        function handleSearch(event) {
            const searchTerm = document.getElementById('searchInput').value.toLowerCase().trim();
            const suggestionsDiv = document.getElementById('searchSuggestions');
            
            if (searchTerm.length === 0) {
                suggestionsDiv.style.display = 'none';
                filterOrders();
                return;
            }
            
            // Generate suggestions
            const suggestions = [];
            const seenItems = new Set();
            
            allOrders.forEach(order => {
                // Match Order ID
                if (order.orderId.toLowerCase().includes(searchTerm)) {
                    suggestions.push({
                        type: 'order',
                        text: `Order #${order.orderId}`,
                        searchTerm: order.orderId
                    });
                }
                
                // Match Product Names
                order.items.forEach(item => {
                    const itemLower = item.toLowerCase();
                    if (itemLower.includes(searchTerm) && !seenItems.has(item)) {
                        seenItems.add(item);
                        suggestions.push({
                            type: 'product',
                            text: item,
                            searchTerm: item
                        });
                    }
                });
                

            });
            
            // Display suggestions
            if (suggestions.length > 0) {
                suggestionsDiv.innerHTML = suggestions.map((suggestion, index) => `
                    <div class="suggestion-item" onclick="selectSuggestion('${suggestion.searchTerm.replace(/'/g, "\\'")}')"
                        >
                        <div class="suggestion-label">${suggestion.type === 'order' ? 'Order ID' : 'Product'}</div>
                        <div class="suggestion-text">${suggestion.text}</div>
                    </div>
                `).join('');
                suggestionsDiv.style.display = 'block';
            } else {
                suggestionsDiv.innerHTML = '<div class="suggestion-item" style="cursor: default; color: #999;">No results found</div>';
                suggestionsDiv.style.display = 'block';
            }
            
            filterOrders();
        }

        function selectSuggestion(term) {
            document.getElementById('searchInput').value = term;
            document.getElementById('searchSuggestions').style.display = 'none';
            filterOrders();
        }

        function updateEmptyState() {
            const orderGroups = document.querySelectorAll('.order-group');
            const visibleOrders = Array.from(orderGroups).filter(group => group.style.display !== 'none');
            const noOrdersState = document.getElementById('noOrdersState');
            if (noOrdersState) {
                noOrdersState.style.display = visibleOrders.length === 0 ? 'block' : 'none';
            }
        }

        async function applyFilters() {
            const searchTerm = document.getElementById('searchInput').value.toLowerCase();
            
            for (const group of document.querySelectorAll('.order-group')) {
                let status = group.dataset.status;
                const archived = parseInt(group.dataset.archived || 0);
                const orderId = group.querySelector('.order-item')?.dataset.orderId || '';
                const productId = group.querySelector('.order-item')?.dataset.productId || '';
                const products = Array.from(group.querySelectorAll('.item-name')).map(el => el.textContent.toLowerCase()).join(' ');
                
                let shouldShow = false;
                
                // Handle archive filter first
                if (currentStatus === 'archived') {
                    // Show only archived orders
                    shouldShow = archived === 1;
                } else {
                    // For all other statuses, exclude archived orders
                    if (archived === 1) {
                        shouldShow = false;
                    } else {
                        // Handle status matching for different tabs
                        if (currentStatus === 'all') {
                            // "All" shows all non-archived orders
                            shouldShow = true;
                        } else if (currentStatus === 'delivered') {
                            // "Completed" shows delivered/received/reviewed statuses
                            shouldShow = status === 'delivered' || status === 'received' || status === 'reviewed';
                        } else if (currentStatus === 'delivered-unreviewed') {
                            // "To review" shows deliverable orders waiting rating: delivered or received
                            shouldShow = (status === 'delivered' || status === 'received');
                        } else if (currentStatus === 'reviewed') {
                            // "Reviewed" shows only reviewed status
                            shouldShow = status === 'reviewed';
                        } else {
                            // Other statuses match directly
                            shouldShow = status === currentStatus;
                        }
                    }
                }
                
                // Search only by order ID and product names (NO STORE NAMES)
                const searchMatch = !searchTerm || 
                    orderId.toLowerCase().includes(searchTerm) || 
                    products.includes(searchTerm);
                
                shouldShow = shouldShow && searchMatch;
                group.dataset.matched = shouldShow ? '1' : '0';
            }
            
            applySorting();
            applyPagination();
        }

        function filterByStatus(status) {
            currentStatus = status;
            currentPage = 1;
            document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
            document.querySelector(`.tab[data-status="${status}"]`).classList.add('active');
            applyFilters();
        }

        function filterOrders() {
            currentPage = 1;
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

        function toggleFilterDropdown() {
            const dropdown = document.getElementById('filterDropdown');
            dropdown.classList.toggle('active');
            
            // Highlight current sort option
            document.querySelectorAll('.dropdown-item').forEach(item => {
                item.classList.remove('active');
            });
            
            const items = document.querySelectorAll('.dropdown-item');
            if (currentSort === 'newest') {
                items[0].classList.add('active');
            } else if (currentSort === 'oldest') {
                items[1].classList.add('active');
            }
        }

        function closeFilterDropdown() {
            document.getElementById('filterDropdown').classList.remove('active');
        }

        function applySorting() {
            const container = document.getElementById('ordersContainer');
            const matchedOrders = getMatchedOrderGroups();
            const unmatchedOrders = Array.from(container.querySelectorAll('.order-group')).filter(group => group.dataset.matched !== '1');
            
            if (currentSort === 'newest') {
                matchedOrders.sort((a, b) => {
                    const aId = parseInt(a.querySelector('.order-item')?.dataset.orderId) || 0;
                    const bId = parseInt(b.querySelector('.order-item')?.dataset.orderId) || 0;
                    return bId - aId;
                });
            } else if (currentSort === 'oldest') {
                matchedOrders.sort((a, b) => {
                    const aId = parseInt(a.querySelector('.order-item')?.dataset.orderId) || 0;
                    const bId = parseInt(b.querySelector('.order-item')?.dataset.orderId) || 0;
                    return aId - bId;
                });
            }
            
            matchedOrders.forEach(order => container.appendChild(order));
            unmatchedOrders.forEach(order => container.appendChild(order));
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

        // Close dropdown when clicking outside
        document.addEventListener('click', function(e) {
            const filterBtn = document.querySelector('.filter-btn');
            const filterDropdown = document.getElementById('filterDropdown');
            if (filterDropdown && filterBtn && !filterBtn.contains(e.target) && !filterDropdown.contains(e.target)) {
                closeFilterDropdown();
            }
        });

        // Status configurations
        const statusConfig = {
            'pending': {
                title: 'To Pay',
                icon: '●',
                message: 'Please complete your payment to process your order.',
                titleText: 'Payment Pending',
                actions: ['Cancel Order']
            },
            'processing': {
                title: 'To Ship',
                icon: '●',
                message: 'Your order is being prepared for shipment.',
                titleText: 'Processing',
                actions: ['Contact Seller']
            },
            'shipped': {
                title: 'To Receive',
                icon: '●',
                message: 'Your order is on its way. Track your package for more details.',
                titleText: 'Shipped',
                actions: ['Contact Seller']
            },
            'delivered': {
                title: 'Order Delivered',
                icon: '●',
                message: 'Your order has been delivered. Please confirm receipt to rate.',
                titleText: 'Delivered',
                actions: ['Confirm Received']
            },
            'received': {
                title: 'Order Received',
                icon: '●',
                message: 'Thank you for confirming receipt. Ready for review.',
                titleText: 'Received',
                actions: ['Rate it', 'Buy again']
            },
            'reviewed': {
                title: 'Reviewed',
                icon: '●',
                message: 'Thank you for your review!',
                titleText: 'Reviewed',
                actions: ['Buy again']
            },
            'cancelled': {
                title: 'Order Closed',
                icon: '●',
                message: 'You have closed your order. We hope to see you again soon!',
                titleText: 'Order Closed',
                actions: ['Buy again']
            }
        };

        function openOrderDetail(element) {
            const orderId = element.dataset.orderId;
            const itemId = element.dataset.itemId;
            const status = element.dataset.orderStatus;
            const storeName = element.dataset.storeName;
            const recipientId = element.dataset.recipientId;
            const recipientName = element.dataset.recipientName;
            const recipientPhone = element.dataset.recipientPhone;
            const recipientAddress = element.dataset.recipientAddress;

            // Get item details from the DOM
            const itemName = element.querySelector('.item-name').textContent;
            const itemPrice = element.querySelector('.item-price').textContent;
            const itemQty = element.querySelector('.item-qty').textContent;
            const itemImage = element.querySelector('.item-image img').src;

            // Get parent order group for additional info
            const orderGroup = element.closest('.order-group');
            currentOrderGroup = orderGroup;
            const totalAmount = orderGroup.querySelector('.item-price').closest('.order-group').textContent;

            // Update modal content
            document.getElementById('detailItemName').textContent = itemName;
            document.getElementById('detailPrice').textContent = itemPrice;
            document.getElementById('detailQty').textContent = itemQty;
            document.getElementById('detailItemImage').src = itemImage;
            document.getElementById('detailStoreName').textContent = storeName;
            document.getElementById('orderNumber').textContent = orderId;
            document.getElementById('subtotal').textContent = itemPrice;
            
            // Store product ID for review modal
            const productId = element.dataset.productId || itemId;
            currentReviewProductId = productId;
            document.getElementById('hiddenProductId').value = productId;

            // Get total from the order group
            const totalText = orderGroup.querySelector('div[style*="text-align: right"]')?.querySelector('div:last-child')?.textContent;
            if (totalText) {
                document.getElementById('totalAmount').textContent = totalText;
            }

            // Update status-dependent content
            let configStatus = status;
            if (status === 'reviewed') {
                configStatus = 'reviewed';
            } else if (status === 'delivered') {
                configStatus = 'delivered';
            } else if (status === 'received') {
                configStatus = 'received';
            }
            const config = statusConfig[configStatus] || statusConfig['cancelled'];
            document.getElementById('modalTitle').textContent = config.title;
            document.getElementById('statusTitle').textContent = config.titleText;
            document.getElementById('statusMessage').textContent = config.message;
            document.getElementById('detailStatus').textContent = config.title;

            // Update recipient info from data attributes
            document.getElementById('recipientName').textContent = recipientName || 'Recipient';
            document.getElementById('recipientPhone').textContent = recipientPhone || '(+63) 000000000';
            document.getElementById('recipientAddress').textContent = recipientAddress || 'Address not available';

            // Update timeline information
            const orderDate = new Date();
            const shipDate = new Date();
            const completedDate = new Date();
            const cancelledDate = new Date();
            
            // Format dates
            const formatDate = (date) => {
                const options = { year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' };
                return date.toLocaleDateString('en-US', options);
            };
            
            document.getElementById('orderTime').textContent = formatDate(orderDate);
            
            // Ship time based on status
            if (status === 'pending' || status === 'cancelled') {
                document.getElementById('shipTime').textContent = '-';
            } else {
                shipDate.setDate(shipDate.getDate() + 2);
                document.getElementById('shipTime').textContent = formatDate(shipDate);
            }
            
            // Completed time based on status
            if (status === 'delivered' || status === 'completed') {
                completedDate.setDate(completedDate.getDate() + 5);
                document.getElementById('completedTime').textContent = formatDate(completedDate);
            } else {
                document.getElementById('completedTime').textContent = '-';
            }
            
            // Cancelled time based on status
            const cancelledTimeSection = document.getElementById('cancelledTimeSection');
            if (status === 'cancelled') {
                cancelledDate.setHours(cancelledDate.getHours());
                document.getElementById('cancelledTime').textContent = formatDate(cancelledDate);
                cancelledTimeSection.style.display = 'block';
            } else {
                cancelledTimeSection.style.display = 'none';
            }

            // Update action buttons
            const actionButtons = document.getElementById('orderActions');
            actionButtons.innerHTML = '';
            const activeProductName = (itemName || '').trim();
            config.actions.forEach(action => {
                const button = document.createElement('button');
                button.textContent = action;
                button.className = action === 'Buy again' || action === 'Rate it' ? 'action-primary' : '';
                button.onclick = () => handleAction(action, configStatus, orderId, activeProductName);
                actionButtons.appendChild(button);
            });

            // Show modal
            document.getElementById('orderDetailModal').classList.add('active');
            refreshOverlayState();
        }

        function closeOrderDetail() {
            document.getElementById('orderDetailModal').classList.remove('active');
            refreshOverlayState();
        }

        function toggleOrderSummary() {
            const section = document.getElementById('orderTimeline');
            const arrow = document.getElementById('summaryArrow');
            section.style.display = section.style.display === 'none' ? 'block' : 'none';
            arrow.textContent = section.style.display === 'none' ? '∨' : '∧';
        }

        async function copyOrderNumber() {
            const orderNumberText = document.getElementById('orderNumber').textContent.split(' ')[0];
            navigator.clipboard.writeText(orderNumberText);
            await localAlert('success', 'Copied', 'Order number copied!');
        }

        async function cancelOrderDirect(orderId, event) {
            event.stopPropagation();
            const confirmed = await localConfirm('Cancel Order', 'Are you sure you want to cancel this order?', 'Yes, Cancel', 'Keep Order');
            if(confirmed) {
                fetch('api/cancel-order.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        order_id: orderId
                    })
                })
                .then(response => response.json())
                .then(async data => {
                    if(data.success) {
                        await localAlert('success', 'Cancelled', 'Order cancelled successfully!');
                        location.reload();
                    } else {
                        await localAlert('error', 'Cancel Failed', data.message || 'Failed to cancel order');
                    }
                })
                .catch(async error => {
                    console.error('Error:', error);
                    await localAlert('error', 'Request Failed', 'Error cancelling order');
                });
            }
        }

        let currentReviewProductId = null;
        let currentReviewItemId = null;
        let selectedReviewMediaFilesPH = [];

        function getMediaFileKeyPH(file) {
            return `${file.name}::${file.size}::${file.lastModified}`;
        }

        function appendReviewMediaFilesPH(newFiles) {
            if (!newFiles || !newFiles.length) return;
            const existing = new Set(selectedReviewMediaFilesPH.map(getMediaFileKeyPH));
            newFiles.forEach(file => {
                const key = getMediaFileKeyPH(file);
                if (!existing.has(key)) {
                    selectedReviewMediaFilesPH.push(file);
                    existing.add(key);
                }
            });
        }
        
        async function handleAction(action, configStatus, orderId, productName = '') {
            switch(action) {
                case 'Cancel Order':
                    if(configStatus === 'pending') {
                        const confirmed = await localConfirm('Cancel Order', 'Are you sure you want to cancel this order?', 'Yes, Cancel', 'Keep Order');
                        if(confirmed) {
                            fetch('api/cancel-order.php', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                },
                                body: JSON.stringify({
                                    order_id: orderId
                                })
                            })
                            .then(response => response.json())
                            .then(async data => {
                                if(data.success) {
                                    await localAlert('success', 'Cancelled', 'Order cancelled successfully!');
                                    closeOrderDetail();
                                    setTimeout(() => location.reload(), 500);
                                } else {
                                    await localAlert('error', 'Cancel Failed', data.message || 'Failed to cancel order');
                                }
                            })
                            .catch(async error => {
                                console.error('Error:', error);
                                await localAlert('error', 'Request Failed', 'Error cancelling order');
                            });
                        }
                    } else {
                        await localAlert('warning', 'Cannot Cancel', 'Order can only be cancelled if payment is pending.');
                    }
                    break;
                case 'Contact Seller':
                    {
                        const params = new URLSearchParams();
                        if (orderId) {
                            params.set('order_id', String(orderId));
                        }
                        if (productName) {
                            params.set('product', productName);
                        }
                        const query = params.toString();
                        window.location.href = query ? (`messages.php?${query}`) : 'messages.php';
                    }
                    break;
                case 'Confirm Received':
                    {
                    const confirmed = await localConfirm('Confirm Receipt', 'Confirm that you have received this order?', 'Yes, Confirm', 'Not Yet');
                    if (confirmed) {
                        fetch('api/confirm-delivery.php', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({ order_id: orderId })
                        })
                        .then(response => response.json())
                        .then(async data => {
                            if (data.success) {
                                if (currentOrderGroup) {
                                    currentOrderGroup.dataset.status = 'received';
                                    const statusNode = currentOrderGroup.querySelector('.order-status');
                                    if (statusNode) {
                                        statusNode.textContent = 'Order Received';
                                    }
                                }
                                closeOrderDetail();
                                await localAlert('success', 'Received Confirmed', 'Order confirmed as received. You may now rate it.');
                                setTimeout(() => location.reload(), 200);
                            } else {
                                await localAlert('error', 'Confirmation Failed', data.message || 'Failed to confirm receipt');
                            }
                        })
                        .catch(async error => {
                            console.error('Error confirming receipt:', error);
                            await localAlert('error', 'Request Failed', 'Error confirming receipt');
                        });
                    }
                    }
                    break;
                case 'Rate it':
                    openReviewModal();
                    break;
                case 'Buy again':
                    window.location.href = 'user_dashboard.php';
                    break;
            }
        }
        
        function openReviewModal() {
            // Get product details from the currently open order detail modal
            const productId = document.getElementById('detailItemName')?.dataset?.productId;
            const orderId = document.getElementById('orderNumber').textContent;
            const detailItemNameEl = document.getElementById('detailItemName');
            const detailItemImageEl = document.getElementById('detailItemImage');
            const detailPriceEl = document.getElementById('detailPrice');
            currentReviewProductId = productId || '';
            currentReviewItemId = orderId;

            const reviewNameEl = document.getElementById('reviewProductName');
            const reviewImageEl = document.getElementById('reviewProductImage');
            const reviewSubEl = document.getElementById('reviewProductSub');
            if (reviewNameEl) {
                reviewNameEl.textContent = detailItemNameEl?.textContent?.trim() || 'Product';
            }
            if (reviewImageEl && detailItemImageEl?.src) {
                reviewImageEl.src = detailItemImageEl.src;
            }
            if (reviewSubEl) {
                reviewSubEl.textContent = detailPriceEl?.textContent?.trim() || '';
            }

            document.getElementById('reviewModal').classList.add('active');
            refreshOverlayState();
        }
        
        function closeReviewModal() {
            document.getElementById('reviewModal').classList.remove('active');
            document.getElementById('reviewForm').reset();
            const charCountEl = document.getElementById('reviewCharCount');
            if (charCountEl) {
                charCountEl.textContent = '0';
            }
            const anonymousToggleEl = document.getElementById('reviewAnonymousToggle');
            if (anonymousToggleEl) {
                anonymousToggleEl.checked = false;
            }
            const privacyRevealedEl = document.getElementById('privacyRevealed');
            if (privacyRevealedEl) {
                privacyRevealedEl.checked = true;
            }
            clearMediaUpload();
            refreshOverlayState();
        }
        
        function handleMediaUpload(filesInput = null) {
            const files = filesInput ? Array.from(filesInput) : [];
            
            if (!files.length) return;

            appendReviewMediaFilesPH(files);
            
            const totalSize = selectedReviewMediaFilesPH.reduce((sum, file) => sum + (file.size || 0), 0);
            if (totalSize > 25 * 1024 * 1024) {
                localAlert('warning', 'Upload Too Large', 'Total upload size exceeds 25MB');
                // Rollback append when total exceeds limit.
                const rollbackKeys = new Set(files.map(getMediaFileKeyPH));
                selectedReviewMediaFilesPH = selectedReviewMediaFilesPH.filter(f => !rollbackKeys.has(getMediaFileKeyPH(f)));
                return;
            }

            const firstFile = selectedReviewMediaFilesPH[0];
            const previewUrl = URL.createObjectURL(firstFile);
            
            const preview = document.getElementById('uploadPreview');
            const placeholder = document.getElementById('uploadPlaceholder');
            const previewImg = document.getElementById('uploadPreviewImg');
            const previewVideo = document.getElementById('uploadPreviewVideo');
            const uploadSummary = document.getElementById('uploadSummary');

            preview.style.display = 'block';
            placeholder.style.display = 'none';

            if (firstFile.type.startsWith('image/')) {
                previewImg.src = previewUrl;
                previewImg.style.display = 'block';
                previewVideo.style.display = 'none';
            } else if (firstFile.type.startsWith('video/')) {
                previewVideo.src = previewUrl;
                previewVideo.style.display = 'block';
                previewImg.style.display = 'none';
            }

            const totalMb = (totalSize / (1024 * 1024)).toFixed(2);
            uploadSummary.textContent = `${selectedReviewMediaFilesPH.length} file(s) selected • ${totalMb}MB total`;
        }
        
        function clearMediaUpload() {
            document.getElementById('reviewMediaInput').value = '';
            selectedReviewMediaFilesPH = [];
            document.getElementById('uploadPreview').style.display = 'none';
            document.getElementById('uploadPlaceholder').style.display = 'block';
            document.getElementById('uploadPreviewImg').src = '';
            document.getElementById('uploadPreviewVideo').src = '';
            document.getElementById('uploadSummary').textContent = '';
        }
        
        // Handle click on upload area
        document.addEventListener('DOMContentLoaded', function() {
            const uploadArea = document.getElementById('uploadArea');
            const fileInput = document.getElementById('reviewMediaInput');
            const reviewTextarea = document.getElementById('reviewTextarea');
            const reviewCharCount = document.getElementById('reviewCharCount');
            const anonymousToggle = document.getElementById('reviewAnonymousToggle');
            const privacyRevealed = document.getElementById('privacyRevealed');
            const privacyAnonymous = document.getElementById('privacyAnonymous');

            if (reviewTextarea && reviewCharCount) {
                reviewTextarea.addEventListener('input', () => {
                    reviewCharCount.textContent = String(reviewTextarea.value.length);
                });
            }

            if (anonymousToggle && privacyRevealed && privacyAnonymous) {
                anonymousToggle.addEventListener('change', () => {
                    if (anonymousToggle.checked) {
                        privacyAnonymous.checked = true;
                    } else {
                        privacyRevealed.checked = true;
                    }
                });
            }
            
            if (uploadArea && fileInput) {
                uploadArea.addEventListener('click', (e) => {
                    if (e.target.closest('.review-upload-remove')) {
                        return;
                    }
                    fileInput.click();
                });
                
                // Handle drag and drop
                uploadArea.addEventListener('dragover', (e) => {
                    e.preventDefault();
                    uploadArea.style.borderColor = '#e22a39';
                    uploadArea.style.background = '#fff0f0';
                });
                
                uploadArea.addEventListener('dragleave', () => {
                    uploadArea.style.borderColor = '#ddd';
                    uploadArea.style.background = '#fff';
                });
                
                uploadArea.addEventListener('drop', (e) => {
                    e.preventDefault();
                    uploadArea.style.borderColor = '#ddd';
                    uploadArea.style.background = '#fff';
                    
                    const files = e.dataTransfer.files;
                    if (files.length > 0) {
                        handleMediaUpload(files);
                    }
                });
                
                fileInput.addEventListener('change', (e) => {
                    handleMediaUpload(e.target.files);
                    // Reset native input so user can choose additional files repeatedly.
                    e.target.value = '';
                });
            }
        });

        async function submitReview() {
            const rating = document.querySelector('input[name="rating"]:checked')?.value;
            const reviewText = document.getElementById('reviewTextarea').value.trim();
            const productId = document.getElementById('hiddenProductId').value;
            const isAnonymous = document.querySelector('input[name="review_name_privacy"]:checked')?.value === 'anonymous' ? '1' : '0';
            const files = selectedReviewMediaFilesPH.slice();
            
            if (!rating) {
                await localAlert('warning', 'Rating Required', 'Please select a rating');
                return;
            }
            
            if (!reviewText) {
                await localAlert('warning', 'Review Required', 'Please enter your review');
                return;
            }

            const totalSize = files.reduce((sum, file) => sum + (file.size || 0), 0);
            if (totalSize > 25 * 1024 * 1024) {
                await localAlert('warning', 'Upload Too Large', 'Total upload size exceeds 25MB');
                return;
            }
            
            try {
                const formData = new FormData();
                formData.append('product_id', productId);
                formData.append('rating', rating);
                formData.append('review_text', reviewText);
                formData.append('is_anonymous', isAnonymous);
                files.forEach(file => formData.append('review_media[]', file));
                
                const response = await fetch('api/add-review.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                if (data.success) {
                    await localAlert('success', 'Review Posted', 'Thank you! Your review has been posted.');
                    closeReviewModal();
                    closeOrderDetail();
                    
                    // Refresh page to show updated status
                    setTimeout(() => {
                        location.reload();
                    }, 500);
                } else {
                    await localAlert('error', 'Review Failed', data.message || 'Failed to submit review');
                }
            } catch (error) {
                console.error('Error:', error);
                await localAlert('error', 'Request Failed', 'Error submitting review');
            }
        }

        function buyAgain() {
            window.location.href = 'user_dashboard.php';
        }

        // Close modal when clicking outside
        document.getElementById('orderDetailModal').addEventListener('click', function(e) {
            if(e.target === this) {
                closeOrderDetail();
            }
        });

        // Get status from URL if present
        window.addEventListener('load', function() {
            initializeOrdersData();
            refreshOverlayState();
            const urlParams = new URLSearchParams(window.location.search);
            const statusParam = urlParams.get('status');
            if (statusParam && document.querySelector(`.tab[data-status="${statusParam}"]`)) {
                filterByStatus(statusParam);
            } else {
                // Apply default filters if no status param
                applyFilters();
            }

            if (focusOrderId > 0) {
                const input = document.getElementById('searchInput');
                if (input) {
                    input.value = String(focusOrderId);
                }
                applyFilters();
                setTimeout(() => focusTargetOrder(focusOrderId), 120);
            }
        });
        
        // Close suggestions when clicking outside
        document.addEventListener('click', function(e) {
            const searchWrapper = document.querySelector('.search-wrapper');
            if (searchWrapper && !searchWrapper.contains(e.target)) {
                document.getElementById('searchSuggestions').style.display = 'none';
            }
        });
    </script>
</body>
</html>