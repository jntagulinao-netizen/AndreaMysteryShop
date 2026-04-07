<?php
header('Content-Type: application/json; charset=utf-8');
session_start();
require __DIR__ . '/../dbConnection.php';
require __DIR__ . '/../message_helpers.php';

error_log('=== CHECKOUT API CALLED ===');
error_log('User ID: ' . ($_SESSION['user_id'] ?? 'NOT SET'));
error_log('POST Data: ' . json_encode($_POST));

if (!isset($_SESSION['user_id'])) {
    error_log('ERROR: User not logged in');
    http_response_code(401);
    echo json_encode(['error' => 'Please login to checkout']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$userId = (int)$_SESSION['user_id'];
$recipientId = (int)($_POST['recipient_id'] ?? 0);
$paymentMethod = trim($_POST['payment_method'] ?? '');

// Get selected items with quantities (JSON format)
$selectedItemsStr = trim($_POST['selected_items'] ?? '');
$selectedItemsData = [];

if (!empty($selectedItemsStr)) {
    $decoded = json_decode($selectedItemsStr, true);
    if (is_array($decoded) && !empty($decoded)) {
        $selectedItemsData = $decoded;
    }
}

if (empty($selectedItemsData)) {
    http_response_code(400);
    echo json_encode(['error' => 'No items selected for checkout']);
    exit;
}

$cartItemIds = [];
$productItems = [];
foreach ($selectedItemsData as $itemData) {
    $cartItemId = isset($itemData['cart_item_id']) ? intval($itemData['cart_item_id']) : 0;
    $productId = isset($itemData['product_id']) ? intval($itemData['product_id']) : 0;
    $quantity = isset($itemData['quantity']) ? intval($itemData['quantity']) : 0;

    if ($quantity < 1) {
        http_response_code(400);
        echo json_encode(['error' => 'Each selected item must include quantity >= 1']);
        exit;
    }

    if ($cartItemId > 0) {
        $cartItemIds[] = $cartItemId;
        $itemData['source'] = 'cart';
    } elseif ($productId > 0) {
        $productItems[] = ['product_id' => $productId, 'quantity' => $quantity];
        $itemData['source'] = 'buy_now';
    } else {
        http_response_code(400);
        echo json_encode(['error' => 'Each selected item must include cart_item_id or product_id']);
        exit;
    }
}

$cartItemIds = array_unique($cartItemIds);



// Validate inputs
if (!$recipientId) {
    http_response_code(400);
    echo json_encode(['error' => 'Recipient is required']);
    exit;
}

if (!$paymentMethod || !in_array($paymentMethod, ['cash'], true)) {
    http_response_code(400);
    echo json_encode(['error' => 'Valid payment method is required (cash only)']);
    exit;
}

$items = [];

$conn->begin_transaction();
try {
    // Verify recipient belongs to user
    $recipientStmt = $conn->prepare('SELECT recipient_id, recipient_name, phone_no, street_name, unit_floor, district, city, region FROM recipients WHERE recipient_id = ? AND user_id = ?');
    $recipientStmt->bind_param('ii', $recipientId, $userId);
    $recipientStmt->execute();
    $recipientRes = $recipientStmt->get_result();
    
    if ($recipientRes->num_rows === 0) {
        throw new Exception('Invalid recipient selected');
    }
    $recipient = $recipientRes->fetch_assoc();

    // Fetch cart
    $cartStmt = $conn->prepare('SELECT cart_id FROM carts WHERE user_id = ? LIMIT 1');
    $cartStmt->bind_param('i', $userId);
    $cartStmt->execute();
    $cartRes = $cartStmt->get_result();
    $cart = $cartRes->fetch_assoc();
    
    if (!$cart) {
        throw new Exception('Cart not found');
    }
    $cartId = $cart['cart_id'];

    // Fetch selected cart items (if any) and product rows (if any)
    $cartItems = [];
    if (!empty($cartItemIds)) {
        $placeholders = implode(',', array_fill(0, count($cartItemIds), '?'));
        $query = "SELECT ci.cart_item_id, ci.product_id, ci.quantity AS cart_quantity, p.price, p.product_stock, p.product_name,
                 (SELECT pi.image_url
                    FROM product_images pi
                   WHERE pi.product_id = p.product_id
                   ORDER BY pi.is_pinned DESC, pi.image_id ASC
                   LIMIT 1) AS product_image
                  FROM cart_items ci
                  JOIN products p ON p.product_id = ci.product_id
                  WHERE ci.cart_id = ? AND ci.cart_item_id IN ($placeholders)";
        $itemsStmt = $conn->prepare($query);
        $types = 'i' . str_repeat('i', count($cartItemIds));
        $params = array_merge([$cartId], $cartItemIds);
        $itemsStmt->bind_param($types, ...$params);
        $itemsStmt->execute();
        $itemsRes = $itemsStmt->get_result();
        while ($row = $itemsRes->fetch_assoc()) {
            $cartItems[$row['cart_item_id']] = $row;
        }

        if (count($cartItems) !== count($cartItemIds)) {
            $missing = array_diff($cartItemIds, array_keys($cartItems));
            throw new Exception('Cart item not found: ' . implode(',', $missing));
        }
    }

    $productRows = [];
    if (!empty($productItems)) {
        $productIds = array_unique(array_column($productItems, 'product_id'));
        $placeholders = implode(',', array_fill(0, count($productIds), '?'));
        $productQuery = "SELECT p.product_id, p.price, p.product_stock, p.product_name,
                    (SELECT pi.image_url
                       FROM product_images pi
                      WHERE pi.product_id = p.product_id
                      ORDER BY pi.is_pinned DESC, pi.image_id ASC
                      LIMIT 1) AS product_image
                 FROM products p
                 WHERE p.product_id IN ($placeholders)";
        $prodStmt = $conn->prepare($productQuery);
        $prodStmt->bind_param(str_repeat('i', count($productIds)), ...$productIds);
        $prodStmt->execute();
        $prodRes = $prodStmt->get_result();
        while ($row = $prodRes->fetch_assoc()) {
            $productRows[$row['product_id']] = $row;
        }

        if (count($productRows) !== count($productIds)) {
            $missing = array_diff($productIds, array_keys($productRows));
            throw new Exception('Product not found: ' . implode(',', $missing));
        }
    }

    // Build order items using both cart and buy-now data
    $total = 0;
    $processedCartIds = [];

    // Map product_id => aggregated quantity and price to avoid duplicate order_items constraint key collision
    $aggregatedOrderItems = [];

    foreach ($selectedItemsData as $selectedItem) {
        $quantity = (int)$selectedItem['quantity'];

        if (isset($selectedItem['cart_item_id']) && $selectedItem['cart_item_id'] > 0) {
            $cartItemId = (int)$selectedItem['cart_item_id'];
            $item = $cartItems[$cartItemId] ?? null;
            if (!$item) {
                throw new Exception('Cart item not found: ' . $cartItemId);
            }
            if ($item['product_stock'] < $quantity) {
                throw new Exception('Insufficient stock for product ID ' . $item['product_id']);
            }

            $productId = $item['product_id'];
            if (!isset($aggregatedOrderItems[$productId])) {
                $aggregatedOrderItems[$productId] = [
                    'cart_item_ids' => [],
                    'quantity' => 0,
                    'price' => $item['price'],
                    'product_stock' => $item['product_stock'],
                    'product_name' => $item['product_name'],
                    'product_image' => $item['product_image'] ?? null
                ];
            }
            $aggregatedOrderItems[$productId]['cart_item_ids'][] = $cartItemId;
            $aggregatedOrderItems[$productId]['quantity'] += $quantity;
            $processedCartIds[] = $cartItemId;

        } else {
            $productId = (int)$selectedItem['product_id'];
            $product = $productRows[$productId] ?? null;
            if (!$product) {
                throw new Exception('Product not found: ' . $productId);
            }
            if ($product['product_stock'] < $quantity) {
                throw new Exception('Insufficient stock for product ID ' . $productId);
            }

            if (!isset($aggregatedOrderItems[$productId])) {
                $aggregatedOrderItems[$productId] = [
                    'cart_item_ids' => [],
                    'quantity' => 0,
                    'price' => $product['price'],
                    'product_stock' => $product['product_stock'],
                    'product_name' => $product['product_name'],
                    'product_image' => $product['product_image'] ?? null
                ];
            }
            $aggregatedOrderItems[$productId]['quantity'] += $quantity;
        }
    }

    foreach ($aggregatedOrderItems as $productId => $itemData) {
        if ($itemData['product_stock'] < $itemData['quantity']) {
            throw new Exception('Insufficient stock for product ID ' . $productId);
        }

        $items[] = [
            'cart_item_id' => !empty($itemData['cart_item_ids']) ? implode(',', $itemData['cart_item_ids']) : null,
            'product_id' => $productId,
            'quantity' => $itemData['quantity'],
            'price' => $itemData['price'],
            'product_name' => $itemData['product_name'] ?? ('Product #' . $productId),
            'product_image' => $itemData['product_image'] ?? null
        ];
        $total += $itemData['price'] * $itemData['quantity'];
    }

    if (empty($items)) {
        throw new Exception('No valid items selected for checkout');
    }

    // Create order
    $status = 'pending';
    $orderStmt = $conn->prepare('INSERT INTO orders (user_id, recipient_id, payment_method, status, total_amount, order_date) VALUES (?, ?, ?, ?, ?, NOW())');
    if (!$orderStmt) {
        throw new Exception('Failed to prepare order insert: ' . $conn->error);
    }
    $orderStmt->bind_param('iissd', $userId, $recipientId, $paymentMethod, $status, $total);
    if (!$orderStmt->execute()) {
        throw new Exception('Failed to insert order: ' . $orderStmt->error);
    }
    if ($orderStmt->affected_rows === 0) {
        throw new Exception('Order insert affected 0 rows');
    }
    $orderId = $conn->insert_id;
    error_log('Checkout: created order ' . $orderId . ' for user ' . $userId . ' total ' . $total);

    // Create order items and update stock for selected items
    foreach ($items as $item) {
        $itemPrice = $item['price'];
        $itemStmt = $conn->prepare('INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)');
        if (!$itemStmt) {
            throw new Exception('Failed to prepare order_items insert: ' . $conn->error);
        }
        $itemStmt->bind_param('iiid', $orderId, $item['product_id'], $item['quantity'], $itemPrice);
        if (!$itemStmt->execute()) {
            throw new Exception('Failed to insert order item: ' . $itemStmt->error);
        }
        if ($itemStmt->affected_rows === 0) {
            throw new Exception('Order item insert affected 0 rows for product ' . $item['product_id']);
        }

        // Decrement product stock
        $stockStmt = $conn->prepare('UPDATE products SET product_stock = product_stock - ?, order_count = order_count + ? WHERE product_id = ?');
        if (!$stockStmt) {
            throw new Exception('Failed to prepare stock update: ' . $conn->error);
        }
        $stockStmt->bind_param('iii', $item['quantity'], $item['quantity'], $item['product_id']);
        if (!$stockStmt->execute()) {
            throw new Exception('Failed to update stock for product ' . $item['product_id'] . ': ' . $stockStmt->error);
        }
        if ($stockStmt->affected_rows === 0) {
            throw new Exception('Stock update affected 0 rows for product ' . $item['product_id']);
        }
    }

    // Delete ONLY selected cart rows (if any)
    if (!empty($processedCartIds)) {
        $cartItemPlaceholders = implode(',', array_fill(0, count($processedCartIds), '?'));
        $deleteQuery = "DELETE FROM cart_items WHERE cart_id = ? AND cart_item_id IN ($cartItemPlaceholders)";
        $deleteStmt = $conn->prepare($deleteQuery);
        $deleteTypes = 'i' . str_repeat('i', count($processedCartIds));
        $deleteParams = array_merge([$cartId], $processedCartIds);
        $deleteStmt->bind_param($deleteTypes, ...$deleteParams);
        $deleteStmt->execute();
    }

    // Auto-create conversation and send initial admin/system order notice.
    $conversationId = messageEnsureConversation($conn, $userId, (int)$orderId, messageGetDefaultAdminId($conn));
    if ($conversationId > 0) {
        $itemCount = 0;
        $itemLines = [];
        $productNames = [];
        $primaryImage = '';
        foreach ($items as $row) {
            $itemCount += (int)($row['quantity'] ?? 0);
            $lineTotal = ((float)($row['price'] ?? 0)) * ((int)($row['quantity'] ?? 0));
            $productName = (string)($row['product_name'] ?? ('Product #' . (int)$row['product_id']));
            $productNames[] = $productName;
            if ($primaryImage === '' && !empty($row['product_image'])) {
                $primaryImage = (string)$row['product_image'];
            }
            $itemLines[] = '- ' . (string)($row['product_name'] ?? ('Product #' . (int)$row['product_id']))
                . ' | Qty: ' . (int)($row['quantity'] ?? 0)
                . ' | Unit: PHP ' . number_format((float)($row['price'] ?? 0), 2)
                . ' | Subtotal: PHP ' . number_format($lineTotal, 2);
        }

        $subject = '';
        if (!empty($productNames)) {
            $subject = $productNames[0];
            if (count($productNames) > 1) {
                $subject .= ' +' . (count($productNames) - 1) . ' more';
            }
        }
        if ($subject !== '') {
            $subjectStmt = $conn->prepare('UPDATE conversations SET subject = ? WHERE conversation_id = ?');
            if ($subjectStmt) {
                $subjectStmt->bind_param('si', $subject, $conversationId);
                $subjectStmt->execute();
                $subjectStmt->close();
            }
        }

        $addressParts = [];
        if (!empty($recipient['street_name'])) $addressParts[] = trim((string)$recipient['street_name']);
        if (!empty($recipient['unit_floor'])) $addressParts[] = trim((string)$recipient['unit_floor']);
        if (!empty($recipient['district'])) $addressParts[] = trim((string)$recipient['district']);
        if (!empty($recipient['city'])) $addressParts[] = trim((string)$recipient['city']);
        if (!empty($recipient['region'])) $addressParts[] = trim((string)$recipient['region']);
        $recipientAddress = !empty($addressParts) ? implode(', ', $addressParts) : 'N/A';
        $recipientName = !empty($recipient['recipient_name']) ? trim((string)$recipient['recipient_name']) : 'N/A';
        $recipientPhone = !empty($recipient['phone_no']) ? trim((string)$recipient['phone_no']) : 'N/A';

        $noticeText = "Hello! Thank you for your order.\n"
            . "Your order has been placed successfully.\n\n"
            . "Products: " . (!empty($productNames) ? implode(', ', $productNames) : 'N/A') . "\n"
            . "Order Details\n"
            . "Order Date: " . date('Y-m-d H:i:s') . "\n"
            . "Status: Pending\n"
            . "Payment: " . strtoupper($paymentMethod) . "\n"
            . "Recipient: " . $recipientName . "\n"
            . "Phone: " . $recipientPhone . "\n"
            . "Address: " . $recipientAddress . "\n"
            . "Total Items: " . $itemCount . "\n"
            . "Order Total: PHP " . number_format((float)$total, 2) . "\n\n"
            . "Products\n"
            . implode("\n", $itemLines) . "\n"
            . ($primaryImage !== '' ? ("\n[PRODUCT_IMAGE]" . $primaryImage . "[/PRODUCT_IMAGE]\n") : "\n")
            . "We'll send updates here as your order status changes.";

        messageInsert($conn, $conversationId, 0, 'system', $noticeText, 'order_notice', null);
    }

    $conn->commit();
    
    http_response_code(201);
    echo json_encode([
        'success' => true,
        'order_id' => $orderId,
        'total' => $total,
        'payment_method' => $paymentMethod,
        'message' => 'Order placed successfully'
    ]);
    
} catch (Exception $e) {
    $conn->rollback();
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
} finally {
    if (isset($recipientStmt)) $recipientStmt->close();
    if (isset($cartStmt)) $cartStmt->close();
    if (isset($itemsStmt)) $itemsStmt->close();
    if (isset($orderStmt)) $orderStmt->close();
    if (isset($itemStmt)) $itemStmt->close();
    if (isset($stockStmt)) $stockStmt->close();
    if (isset($deleteStmt)) $deleteStmt->close();
    $conn->close();
}
?>
