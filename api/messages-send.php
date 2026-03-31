<?php
header('Content-Type: application/json; charset=utf-8');
session_start();
require __DIR__ . '/../dbConnection.php';
require __DIR__ . '/../message_helpers.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Support both JSON and multipart/form-data payloads.
$isMultipart = !empty($_POST) || !empty($_FILES);
if ($isMultipart) {
    $messageText = trim((string)($_POST['message_text'] ?? ''));
    $conversationId = (int)($_POST['conversation_id'] ?? 0);
    $orderId = isset($_POST['order_id']) ? (int)$_POST['order_id'] : null;
    $targetUserId = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;
} else {
    $input = json_decode(file_get_contents('php://input'), true) ?? [];
    $messageText = trim((string)($input['message_text'] ?? ''));
    $conversationId = (int)($input['conversation_id'] ?? 0);
    $orderId = isset($input['order_id']) ? (int)$input['order_id'] : null;
    $targetUserId = isset($input['user_id']) ? (int)$input['user_id'] : 0;
}

$mediaToken = '';
$mediaPublicPath = '';
$mediaType = '';

if (isset($_FILES['attachment']) && is_array($_FILES['attachment']) && (int)($_FILES['attachment']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
    $fileError = (int)($_FILES['attachment']['error'] ?? UPLOAD_ERR_NO_FILE);
    if ($fileError !== UPLOAD_ERR_OK) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Failed to upload attachment']);
        exit;
    }

    $tmpName = (string)($_FILES['attachment']['tmp_name'] ?? '');
    $originalName = (string)($_FILES['attachment']['name'] ?? '');
    $fileSize = (int)($_FILES['attachment']['size'] ?? 0);

    if ($tmpName === '' || $fileSize <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid attachment file']);
        exit;
    }

    $maxUploadBytes = 25 * 1024 * 1024; // 25 MB
    if ($fileSize > $maxUploadBytes) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Attachment is too large (max 25MB)']);
        exit;
    }

    $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
    $allowedImageExt = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    $allowedVideoExt = ['mp4', 'webm', 'mov'];

    if (in_array($ext, $allowedImageExt, true)) {
        $mediaType = 'image';
    } elseif (in_array($ext, $allowedVideoExt, true)) {
        $mediaType = 'video';
    } else {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Unsupported file type']);
        exit;
    }

    $baseDir = __DIR__ . '/../message_media';
    $targetDir = $baseDir . '/' . ($mediaType === 'video' ? 'videos' : 'images');
    if (!is_dir($targetDir) && !mkdir($targetDir, 0777, true) && !is_dir($targetDir)) {
        throw new Exception('Failed to create media directory');
    }

    $safeName = 'msg_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    $targetPath = $targetDir . '/' . $safeName;
    if (!move_uploaded_file($tmpName, $targetPath)) {
        throw new Exception('Failed to save uploaded file');
    }

    $mediaPublicPath = 'message_media/' . ($mediaType === 'video' ? 'videos/' : 'images/') . $safeName;
    $mediaToken = '[CHAT_MEDIA]' . $mediaPublicPath . '|' . $mediaType . '[/CHAT_MEDIA]';
}

if ($messageText === '' && $mediaToken === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Message or attachment is required']);
    exit;
}

if (mb_strlen($messageText) > 3000) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Message is too long']);
    exit;
}

$finalMessageText = trim($messageText);
if ($mediaToken !== '') {
    $finalMessageText = trim($finalMessageText === '' ? $mediaToken : ($mediaToken . "\n" . $finalMessageText));
}

$currentUserId = (int)$_SESSION['user_id'];
$role = $_SESSION['user_role'] ?? 'user';
$isAdmin = strtolower((string)$role) === 'admin';

try {
    if ($conversationId <= 0) {
        if ($isAdmin) {
            if ($targetUserId <= 0) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'User is required']);
                exit;
            }
            $conversationId = messageEnsureConversation($conn, $targetUserId, $orderId, $currentUserId);
        } else {
            $conversationId = messageEnsureConversation($conn, $currentUserId, $orderId, messageGetDefaultAdminId($conn));
        }
    }

    if ($conversationId <= 0) {
        throw new Exception('Unable to create conversation');
    }

    $accessStmt = $conn->prepare('SELECT c.user_id, c.order_id, o.binned FROM conversations c LEFT JOIN orders o ON o.order_id = c.order_id WHERE c.conversation_id = ? LIMIT 1');
    if (!$accessStmt) {
        throw new Exception('Failed to prepare access query');
    }
    $accessStmt->bind_param('i', $conversationId);
    $accessStmt->execute();
    $accessRes = $accessStmt->get_result();
    $conversation = $accessRes ? $accessRes->fetch_assoc() : null;
    $accessStmt->close();

    if (!$conversation) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Conversation not found']);
        exit;
    }

    if (!$isAdmin && (int)$conversation['user_id'] !== $currentUserId) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Forbidden']);
        exit;
    }

    if (!$isAdmin && !is_null($conversation['order_id']) && (int)($conversation['binned'] ?? 0) === 1) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Conversation unavailable']);
        exit;
    }

    $senderRole = $isAdmin ? 'admin' : 'user';
    $senderId = $currentUserId;
    $mediaMime = isset($_FILES['attachment']['type']) ? $_FILES['attachment']['type'] : null;
    $mediaSize = isset($_FILES['attachment']['size']) ? (int)$_FILES['attachment']['size'] : null;
    $mediaOriginalName = isset($_FILES['attachment']['name']) ? $_FILES['attachment']['name'] : null;

    // Debug logging
    $debugLog = __DIR__ . '/../debug_media.log';
    file_put_contents($debugLog, date('c') . "\n" . json_encode([
        'mediaPublicPath' => $mediaPublicPath,
        'mediaType' => $mediaType,
        'mediaMime' => $mediaMime,
        'mediaSize' => $mediaSize,
        'mediaOriginalName' => $mediaOriginalName,
        'finalMessageText' => $finalMessageText
    ], JSON_PRETTY_PRINT) . "\n", FILE_APPEND);

    $ok = messageInsertFull($conn, $conversationId, $senderId, $senderRole, $finalMessageText, 'chat', null, $mediaPublicPath, $mediaType, $mediaMime, $mediaSize, $mediaOriginalName);

    // Log insert result
    file_put_contents($debugLog, "Insert result: " . var_export($ok, true) . "\n\n", FILE_APPEND);

    if (!$ok) {
        throw new Exception('Failed to save message');
    }

    echo json_encode([
        'success' => true,
        'conversation_id' => $conversationId,
        'media_path' => $mediaPublicPath,
        'media_type' => $mediaType
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to send message']);
}
