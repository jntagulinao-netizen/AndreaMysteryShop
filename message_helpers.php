<?php

function messageGetDefaultAdminId(mysqli $conn): int {
    $adminId = 0;
    $stmt = $conn->prepare('SELECT user_id FROM users WHERE LOWER(role) = "admin" ORDER BY user_id ASC LIMIT 1');
    if (!$stmt) {
        return 0;
    }
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res && ($row = $res->fetch_assoc())) {
        $adminId = (int)($row['user_id'] ?? 0);
    }
    $stmt->close();
    return $adminId;
}

function messageEnsureConversation(mysqli $conn, int $userId, ?int $orderId = null, ?int $adminId = null): int {
    if ($userId <= 0) {
        return 0;
    }

    $adminId = $adminId ?? messageGetDefaultAdminId($conn);

    if ($orderId !== null && $orderId > 0) {
        $find = $conn->prepare('SELECT conversation_id FROM conversations WHERE user_id = ? AND order_id = ? LIMIT 1');
        if ($find) {
            $find->bind_param('ii', $userId, $orderId);
            $find->execute();
            $res = $find->get_result();
            if ($res && ($row = $res->fetch_assoc())) {
                $find->close();
                return (int)$row['conversation_id'];
            }
            $find->close();
        }

        $insert = $conn->prepare('INSERT INTO conversations (user_id, admin_id, order_id, subject, created_at, updated_at, last_message_at) VALUES (?, ?, ?, ?, NOW(), NOW(), NOW())');
        if (!$insert) {
            return 0;
        }
        $subject = 'Order #' . $orderId;
        $insert->bind_param('iiis', $userId, $adminId, $orderId, $subject);
        if (!$insert->execute()) {
            $insert->close();
            return 0;
        }
        $conversationId = (int)$conn->insert_id;
        $insert->close();
        return $conversationId;
    }

    $findGeneral = $conn->prepare('SELECT conversation_id FROM conversations WHERE user_id = ? AND order_id IS NULL ORDER BY conversation_id ASC LIMIT 1');
    if ($findGeneral) {
        $findGeneral->bind_param('i', $userId);
        $findGeneral->execute();
        $res = $findGeneral->get_result();
        if ($res && ($row = $res->fetch_assoc())) {
            $findGeneral->close();
            return (int)$row['conversation_id'];
        }
        $findGeneral->close();
    }

    $insertGeneral = $conn->prepare('INSERT INTO conversations (user_id, admin_id, order_id, subject, created_at, updated_at, last_message_at) VALUES (?, ?, NULL, ?, NOW(), NOW(), NOW())');
    if (!$insertGeneral) {
        return 0;
    }
    $subject = 'General Support';
    $insertGeneral->bind_param('iis', $userId, $adminId, $subject);
    if (!$insertGeneral->execute()) {
        $insertGeneral->close();
        return 0;
    }
    $conversationId = (int)$conn->insert_id;
    $insertGeneral->close();
    return $conversationId;
}

function messageInsert(mysqli $conn, int $conversationId, int $senderId, string $senderRole, string $messageText, string $messageType = 'chat', ?string $orderStatus = null): bool {
    // Legacy insert for text-only messages
    return messageInsertFull($conn, $conversationId, $senderId, $senderRole, $messageText, $messageType, $orderStatus, null, null, null, null, null);
}

function messageInsertFull(mysqli $conn, int $conversationId, int $senderId, string $senderRole, string $messageText, string $messageType = 'chat', ?string $orderStatus = null, $mediaPath = null, $mediaType = null, $mediaMime = null, $mediaSize = null, $mediaOriginalName = null): bool {
    if ($conversationId <= 0 || trim($messageText) === '') {
        return false;
    }

    $senderRole = in_array($senderRole, ['user', 'admin', 'system'], true) ? $senderRole : 'system';
    $messageType = in_array($messageType, ['chat', 'order_notice', 'status_notice'], true) ? $messageType : 'chat';
    $isRead = 0;

    $stmt = $conn->prepare('INSERT INTO messages (conversation_id, sender_id, sender_role, message_text, media_path, media_type, media_mime, media_size, media_original_name, message_type, is_read, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())');
    if (!$stmt) {
        return false;
    }
    $safeText = trim($messageText);
    // Correct types: i = int, s = string, d = double
    // conversation_id (i), sender_id (i), sender_role (s), message_text (s), media_path (s), media_type (s), media_mime (s), media_size (i), media_original_name (s), message_type (s), is_read (i)
    $stmt->bind_param('iissssssisi', $conversationId, $senderId, $senderRole, $safeText, $mediaPath, $mediaType, $mediaMime, $mediaSize, $mediaOriginalName, $messageType, $isRead);
    $ok = $stmt->execute();
    $stmt->close();

    if (!$ok) {
        return false;
    }

    $update = $conn->prepare('UPDATE conversations SET last_message_at = NOW(), updated_at = NOW() WHERE conversation_id = ?');
    if ($update) {
        $update->bind_param('i', $conversationId);
        $update->execute();
        $update->close();
    }

    return true;
}
