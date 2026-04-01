<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: LogIn.php');
    exit;
}

$userId = (int)$_SESSION['user_id'];
$role = $_SESSION['user_role'] ?? 'user';
$isAdmin = strtolower((string)$role) === 'admin';
$requestedConversationId = isset($_GET['conversation_id']) ? (int)$_GET['conversation_id'] : 0;
$requestedOrderId = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;
$requestedProduct = trim((string)($_GET['product'] ?? ''));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Messages - Andrea Mystery Shop</title>
    <link rel="stylesheet" href="main.css" />
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f4f6fb; color: #1f2a3d; padding-top: 0 !important; padding-bottom: 70px; }
        .page { width: calc(100% - 48px); max-width: none; margin: 0 auto; padding: 90px 0 14px; }
        .header {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 16px;
            background: #fff;
            border-radius: 12px;
            border: 1px solid #e5ebf5;
            margin-bottom: 12px;
            position: fixed;
            top: 10px;
            left: 50%;
            transform: translateX(-50%);
            width: calc(100% - 48px);
            max-width: none;
            z-index: 1300;
        }
        .back { font-size: 22px; cursor: pointer; color: #3b4a66; }
        .title { font-size: 18px; font-weight: 700; flex: 1; }
        .pill { font-size: 12px; font-weight: 700; padding: 6px 10px; border-radius: 999px; background: #edf3ff; color: #2f5ec8; border: 1px solid #d4e3ff; }

        .layout { display: grid; grid-template-columns: 320px 1fr; gap: 12px; min-height: 72vh; }
        .panel { background: #fff; border: 1px solid #e5ebf5; border-radius: 12px; overflow: hidden; }

        .list-head { padding: 12px; border-bottom: 1px solid #eef2f9; }
        .list-controls { display: flex; gap: 8px; align-items: center; width: 100%; }
        .search { width: 100%; border: 1px solid #d8e0ee; border-radius: 10px; padding: 10px 12px; font-size: 14px; }
        .sort-select {
            border: 1px solid #d8e0ee;
            border-radius: 10px;
            padding: 10px 12px;
            font-size: 14px;
            background: #fff;
            color: #2c3b56;
            min-width: 156px;
        }
        .conversations { max-height: calc(72vh - 58px); overflow-y: auto; }
        .conversation-pagination {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
            padding: 10px 12px;
            border-top: 1px solid #eef2f9;
            background: #fff;
            min-height: 52px;
        }
        .pagination-btn {
            border: 1px solid #d8e0ee;
            background: #fff;
            color: #2d3c58;
            border-radius: 8px;
            padding: 6px 10px;
            font-size: 12px;
            font-weight: 700;
            cursor: pointer;
            min-width: 64px;
        }
        .pagination-btn:disabled {
            opacity: 0.45;
            cursor: not-allowed;
        }
        .pagination-pages {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            flex: 1;
            min-width: 0;
        }
        .pagination-page {
            border: 1px solid #d8e0ee;
            background: #fff;
            color: #344563;
            border-radius: 7px;
            min-width: 30px;
            height: 30px;
            padding: 0 8px;
            font-size: 12px;
            font-weight: 700;
            cursor: pointer;
        }
        .pagination-page.active {
            background: #e22a39;
            border-color: #e22a39;
            color: #fff;
        }
        .pagination-empty {
            display: block;
            font-size: 12px;
            color: #7b879f;
            text-align: center;
            width: 100%;
        }
        .conv { padding: 12px; border-bottom: 1px solid #f0f3f9; cursor: pointer; }
        .conv:hover { background: #f9fbff; }
        .conv.active { background: #eef4ff; }
        .conv-top { display: flex; align-items: center; gap: 8px; margin-bottom: 6px; }
        .conv-name { font-size: 14px; font-weight: 700; color: #1f2a3d; flex: 1; }
        .conv-time { font-size: 11px; color: #8a95ab; }
        .conv-subject { font-size: 12px; color: #46536f; margin-bottom: 4px; }
        .conv-last { font-size: 12px; color: #6c7993; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .unread { min-width: 18px; height: 18px; border-radius: 999px; background: #e22a39; color: #fff; font-size: 11px; display: inline-flex; align-items: center; justify-content: center; padding: 0 5px; }

        .chat-head { padding: 12px 14px; border-bottom: 1px solid #eef2f9; background: #fbfdff; }
        .chat-head-row { display: flex; align-items: flex-start; gap: 10px; }
        .chat-title { font-size: 15px; font-weight: 700; }
        .chat-sub { font-size: 12px; color: #66738c; margin-top: 2px; }
        .conversation-toggle {
            display: none;
            border: 1px solid #d8e0ee;
            background: #fff;
            color: #2d3c58;
            border-radius: 10px;
            padding: 8px 10px;
            font-size: 13px;
            font-weight: 700;
            cursor: pointer;
            white-space: nowrap;
        }
        .conversation-title-wrap { min-width: 0; flex: 1; }
        .drawer-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(20, 33, 58, 0.38);
            z-index: 1490;
        }
        .drawer-overlay.open { display: block; }

        body.message-fullscreen-mobile { padding-bottom: 0 !important; }
        body.message-fullscreen-mobile .mobile-bottom-nav.fixed { display: none !important; }

        .mobile-bottom-nav {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            z-index: 999;
            background: #fff;
            border-top: 1px solid #ddd;
            display: none;
        }
        .mobile-bottom-nav.fixed { display: flex; }
        .mobile-nav-inner {
            display: flex;
            justify-content: space-around;
            align-items: center;
            padding: 0 6px;
            width: 100%;
            height: 50px;
        }
        .mobile-nav-inner a {
            text-decoration: none;
            color: #555;
            font-size: 11px;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 4px;
            position: relative;
        }
        .mobile-nav-inner a svg { width: 20px; height: 20px; stroke-width: 1.5; }
        .mobile-nav-inner a.active { color: #e22a39; }
        .mobile-nav-msg-badge {
            position: absolute;
            top: -4px;
            right: -6px;
            min-width: 16px;
            height: 16px;
            border-radius: 999px;
            background: #e22a39;
            color: #fff;
            border: 1px solid #fff;
            font-size: 10px;
            font-weight: 700;
            line-height: 1;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0 4px;
        }

        .messages { padding: 14px; height: calc(72vh - 140px); overflow-y: auto; background: linear-gradient(180deg, #f9fbff 0%, #ffffff 20%); }
        .msg-row { display: flex; margin-bottom: 12px; }
        .msg-row.me { justify-content: flex-end; }
        .bubble { max-width: 72%; padding: 10px 12px; border-radius: 12px; font-size: 14px; line-height: 1.4; border: 1px solid transparent; }
        .bubble.me { background: #2f67d8; color: #fff; border-color: #2a5dc5; }
        .bubble.other { background: #fff; color: #1f2a3d; border-color: #dce6f4; }
        .bubble.system { background: #fff8ea; color: #6f4f12; border-color: #f3deaf; }
        .meta { font-size: 11px; color: #8a95ab; margin-top: 4px; }
        .msg-link { display: block; color: inherit; text-decoration: none; }
        .msg-link:hover { opacity: .95; }
        .msg-preview-image {
            width: 100%;
            max-width: 240px;
            max-height: 180px;
            object-fit: cover;
            border-radius: 10px;
            border: 1px solid rgba(0,0,0,.08);
            margin-bottom: 8px;
            display: block;
        }
        .order-notice-card {
            border: 1px solid #e6ddbf;
            background: #fffdf6;
            border-radius: 12px;
            overflow: hidden;
        }
        .order-notice-main {
            display: flex;
            gap: 10px;
            padding: 10px;
            text-decoration: none;
            color: inherit;
        }
        .order-notice-main:hover { background: #fff9e9; }
        .order-notice-thumb {
            width: 74px;
            height: 74px;
            border-radius: 10px;
            object-fit: cover;
            border: 1px solid rgba(0,0,0,.08);
            flex-shrink: 0;
        }
        .order-notice-title {
            font-size: 13px;
            font-weight: 700;
            color: #2b364f;
            margin-bottom: 3px;
        }
        .order-notice-summary {
            font-size: 12px;
            color: #55627a;
            line-height: 1.4;
        }
        .order-notice-actions {
            border-top: 1px solid #eee6ca;
            padding: 8px 10px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .order-jump-link {
            font-size: 12px;
            font-weight: 700;
            color: #1f56bf;
            text-decoration: none;
        }
        .order-jump-link:hover { text-decoration: underline; }
        .order-detail-toggle {
            border: 1px solid #d6deeb;
            background: #fff;
            color: #3f4f68;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 700;
            padding: 4px 8px;
            cursor: pointer;
        }
        .order-details-panel {
            border-top: 1px dashed #eadfb7;
            padding: 8px 10px 10px;
            font-size: 12px;
            color: #4d5d77;
            white-space: pre-line;
            line-height: 1.4;
            display: none;
        }
        .order-details-panel.open { display: block; }

        .composer {
            border-top: 1px solid #d8e3f4;
            padding: 10px;
            display: grid;
            grid-template-columns: auto minmax(0, 1fr) auto;
            gap: 8px;
            align-items: center;
            background: #f9fbff;
        }
        .composer textarea {
            width: 100%;
            resize: none;
            border: 2px solid #b8caeb;
            border-radius: 10px;
            padding: 10px;
            min-height: 46px;
            max-height: 140px;
            font-family: inherit;
            font-size: 14px;
            color: #1f2a3d;
            background: #fff;
            box-shadow: 0 1px 0 rgba(47, 93, 215, 0.08) inset;
        }
        .composer textarea:focus {
            outline: none;
            border-color: #5a86db;
            box-shadow: 0 0 0 3px rgba(90, 134, 219, 0.16);
        }
        .attach-btn {
            border: 1px solid #d0dcef;
            border-radius: 10px;
            background: #fff;
            color: #27487c;
            font-weight: 700;
            width: 40px;
            height: 40px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
        }
        .attach-btn:hover { background: #eef4ff; }
        .message-attachment-input { display: none; }
        .attachment-meta {
            grid-column: 1 / -1;
            display: none;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            background: #eef4ff;
            border: 1px solid #d1def7;
            border-radius: 9px;
            padding: 6px 10px;
            font-size: 12px;
            color: #27487c;
        }
        .attachment-meta.show { display: flex; }
        .attachment-clear {
            border: none;
            background: transparent;
            color: #45679b;
            font-size: 16px;
            font-weight: 700;
            cursor: pointer;
            line-height: 1;
        }
        .send { border: none; border-radius: 10px; background: #e22a39; color: #fff; font-weight: 700; padding: 0 16px; height: 40px; cursor: pointer; }
        .send:hover { background: #c91d2f; }
        .msg-preview-video {
            width: 100%;
            max-width: 300px;
            max-height: 220px;
            border-radius: 10px;
            border: 1px solid rgba(0,0,0,.1);
            margin-bottom: 8px;
            background: #000;
            display: block;
        }
        .empty { padding: 24px; color: #7b879f; text-align: center; }

        @media (max-width: 900px) {
            .layout { grid-template-columns: 1fr; }
            .conversations { max-height: 220px; }
            .messages { height: 52vh; }
        }

        @media (max-width: 768px) {
            .page { width: calc(100% - 24px); padding: 84px 0 14px; }
            .header {
                top: 8px;
                width: calc(100% - 24px);
                margin-bottom: 6px;
            }
            .layout {
                min-height: calc(100dvh - 96px);
            }
            .conversation-toggle {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                position: fixed;
                top: 88px;
                left: 20px;
                z-index: 1410;
            }
            .chat-head {
                position: fixed;
                top: 76px;
                left: 10px;
                right: 10px;
                z-index: 1400;
            }
            .chat-head-row {
                padding-left: 76px;
            }
            .conversation-panel {
                position: fixed;
                top: 0;
                left: 0;
                bottom: 0;
                width: min(86vw, 340px);
                max-width: 340px;
                border-radius: 0 14px 14px 0;
                z-index: 1500;
                transform: translateX(-104%);
                transition: transform 220ms ease;
                display: flex;
                flex-direction: column;
                overflow: hidden;
            }
            .conversation-panel.open { transform: translateX(0); }
            .conversation-panel .conversations {
                max-height: none;
                flex: 1;
                min-height: 0;
                padding-bottom: 58px;
            }
            .conversation-pagination {
                position: absolute;
                left: 0;
                right: 0;
                bottom: 0;
                z-index: 2;
                display: flex !important;
                box-shadow: 0 -6px 14px rgba(16, 24, 40, 0.08);
            }
            .list-controls {
                display: flex;
                flex-direction: column;
            }
            .sort-select { width: 100%; min-width: 0; }
            .mobile-bottom-nav { display: flex; }

            .layout > .panel:last-child {
                height: calc(100dvh - 170px);
                display: flex;
                flex-direction: column;
                padding-top: 66px;
            }
            .layout > .panel:last-child .messages {
                height: auto;
                flex: 1;
                min-height: 0;
                overflow-y: auto;
            }
            .layout > .panel:last-child .composer {
                position: sticky;
                bottom: 0;
                z-index: 3;
                background: #fff;
                margin-bottom: 0;
            }

            body.message-fullscreen-mobile .page {
                max-width: none;
                margin: 0;
                padding: 84px 10px 8px;
                min-height: 100dvh;
            }
            body.message-fullscreen-mobile .header {
                margin-bottom: 8px;
            }
            body.message-fullscreen-mobile .layout {
                min-height: calc(100dvh - 96px);
                gap: 0;
            }
            body.message-fullscreen-mobile .layout > .panel:last-child {
                height: calc(100dvh - 104px);
                display: flex;
                flex-direction: column;
                border-radius: 14px;
                padding-top: 66px;
            }
            body.message-fullscreen-mobile .messages {
                height: auto;
                flex: 1;
                min-height: 0;
            }
            body.message-fullscreen-mobile .composer {
                flex-shrink: 0;
                margin-bottom: 0;
            }
        }

        @media (min-width: 769px) {
            body { padding-bottom: 14px; }
        }
    </style>
</head>
<body>
    <div class="page">
        <div class="header">
            <div class="back" onclick="goBack()">&#8249;</div>
            <div class="title">Messages</div>
            <div class="pill"><?php echo $isAdmin ? 'Admin' : 'User'; ?></div>
        </div>

        <div class="layout">
            <div id="conversationPanel" class="panel conversation-panel">
                <div class="list-head">
                    <div id="listControls" class="list-controls">
                        <input id="searchInput" class="search" type="text" placeholder="Search conversation..." oninput="handleConversationFilterChange()" />
                        <select id="sortSelect" class="sort-select" onchange="handleConversationFilterChange()">
                            <option value="newest">Newest to Oldest</option>
                            <option value="oldest">Oldest to Newest</option>
                        </select>
                    </div>
                </div>
                <div id="conversationList" class="conversations"></div>
                <div id="conversationPagination" class="conversation-pagination"></div>
            </div>

            <div class="panel">
                <div class="chat-head">
                    <div class="chat-head-row">
                        <button type="button" class="conversation-toggle" onclick="toggleConversationDrawer(event)">☰ Chats</button>
                        <div class="conversation-title-wrap">
                            <div id="chatTitle" class="chat-title">Select a conversation</div>
                            <div id="chatSub" class="chat-sub">Order updates and chat will appear here.</div>
                        </div>
                    </div>
                </div>
                <div id="messageList" class="messages">
                    <div class="empty">No conversation selected.</div>
                </div>
                <div class="composer">
                    <label for="messageAttachment" class="attach-btn" title="Attach image/video">+</label>
                    <input id="messageAttachment" class="message-attachment-input" type="file" accept="image/*,video/*" onchange="handleMessageAttachment(event)" disabled>
                    <textarea id="messageInput" placeholder="Type a message..." disabled></textarea>
                    <button id="sendBtn" class="send" onclick="sendMessage()" disabled>Send</button>
                    <div id="attachmentMeta" class="attachment-meta">
                        <span id="attachmentMetaText">Attachment selected</span>
                        <button type="button" class="attachment-clear" onclick="clearMessageAttachment()">x</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div id="conversationDrawerOverlay" class="drawer-overlay" onclick="closeConversationDrawer()"></div>

    <?php if ($isAdmin): ?>
    <nav class="mobile-bottom-nav fixed">
        <div class="mobile-nav-inner">
            <a href="admin_dashboard.php">
                <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M3 10.5L12 3l9 7.5V21a1 1 0 0 1-1 1h-5v-7H9v7H4a1 1 0 0 1-1-1V10.5z" stroke-linecap="round" stroke-linejoin="round"></path></svg>
                <span>Home</span>
            </a>
            <a href="admin_orders.php">
                <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M21 8V7a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v1"></path><rect x="3" y="8" width="18" height="11" rx="2" ry="2"></rect></svg>
                <span>Orders</span>
            </a>
            <a href="admin_my_products.php">
                <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M3 7l9-4 9 4-9 4-9-4z"></path><path d="M3 17l9 4 9-4"></path><path d="M3 12l9 4 9-4"></path></svg>
                <span>Products</span>
            </a>
            <a href="messages.php" class="active">
                <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path></svg>
                <span>Messages</span>
            </a>
            <a href="admin_add_product.php">
                <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M12 5v14"></path><path d="M5 12h14"></path><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect></svg>
                <span>Add</span>
            </a>
        </div>
    </nav>
    <?php else: ?>
    <nav class="mobile-bottom-nav fixed">
        <div class="mobile-nav-inner">
            <a href="user_dashboard.php" class="active">
                <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M3 10.5L12 3l9 7.5V21a1 1 0 0 1-1 1h-5v-7H9v7H4a1 1 0 0 1-1-1V10.5z" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path></svg>
                <span>Home</span>
            </a>
            <a href="about.php">
                <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor"><circle cx="12" cy="8" r="3" stroke-width="1.5"></circle><path d="M6 20v-1a4 4 0 014-4h4a4 4 0 014 4v1" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path></svg>
                <span>Message</span>
            </a>
            <a href="category_products.php">
                <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><polygon points="16 8 12 11 8 16 16 8"></polygon></svg>
                <span>Explore</span>
            </a>
            <a href="account.php">
                <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 12c2.5 0 4.5-2 4.5-4.5S14.5 3 12 3 7.5 5 7.5 7.5 9.5 12 12 12z"></path><path d="M4 21c0-4.5 4-8 8-8s8 3.5 8 8"></path></svg>
                <span>Account</span>
            </a>
        </div>
    </nav>
    <?php endif; ?>

    <script>
        const currentUserId = <?php echo $userId; ?>;
        const currentRole = <?php echo json_encode($isAdmin ? 'admin' : 'user'); ?>;
        const requestedConversationId = <?php echo (int)$requestedConversationId; ?>;
        const requestedOrderId = <?php echo (int)$requestedOrderId; ?>;
        const requestedProduct = <?php echo json_encode($requestedProduct); ?>;
        let conversations = [];
        let activeConversationId = null;
        let activeConversationOrderId = null;
        let currentConversationPage = 1;
        const conversationsPerPage = 8;
        let preferredConversationId = requestedConversationId > 0 ? requestedConversationId : null;
        let preferredOrderId = requestedOrderId > 0 ? requestedOrderId : null;
        let targetConversationResolved = false;
        let ensureConversationAttempted = false;
        let hasAppliedContactPrefill = false;
        let pendingMessageAttachment = null;

        function handleConversationFilterChange() {
            currentConversationPage = 1;
            renderConversations();
        }

        function changeConversationPage(delta) {
            currentConversationPage += delta;
            renderConversations();
        }

        function goConversationPage(page) {
            currentConversationPage = page;
            renderConversations();
        }

        function renderConversationPagination(totalItems, totalPages) {
            const pagination = document.getElementById('conversationPagination');
            if (!pagination) return;

            if (totalItems <= 0) {
                pagination.innerHTML = '<div class="pagination-empty">No conversations</div>';
                return;
            }

            const canPrev = currentConversationPage > 1;
            const canNext = currentConversationPage < totalPages;
            let startPage = Math.max(1, currentConversationPage - 1);
            let endPage = Math.min(totalPages, startPage + 2);
            startPage = Math.max(1, endPage - 2);

            const pageButtons = [];
            for (let p = startPage; p <= endPage; p++) {
                pageButtons.push(
                    `<button type="button" class="pagination-page ${p === currentConversationPage ? 'active' : ''}" onclick="goConversationPage(${p})">${p}</button>`
                );
            }

            pagination.innerHTML = `
                <button type="button" class="pagination-btn" ${canPrev ? '' : 'disabled'} onclick="changeConversationPage(-1)">Prev</button>
                <div class="pagination-pages">${pageButtons.join('')}</div>
                <button type="button" class="pagination-btn" ${canNext ? '' : 'disabled'} onclick="changeConversationPage(1)">Next</button>
            `;
        }

        function goBack() {
            window.location.href = currentRole === 'admin' ? 'admin_dashboard.php' : 'user_dashboard.php';
        }

        function setAdminMessagesBadge(count) {
            if (currentRole !== 'admin') return;
            const links = document.querySelectorAll('.mobile-nav-inner a[href="messages.php"]');
            links.forEach((link) => {
                let badge = link.querySelector('.mobile-nav-msg-badge');
                if (count > 0) {
                    if (!badge) {
                        badge = document.createElement('span');
                        badge.className = 'mobile-nav-msg-badge';
                        link.appendChild(badge);
                    }
                    badge.textContent = count > 99 ? '99+' : String(count);
                } else if (badge) {
                    badge.remove();
                }
            });
        }

        function refreshMessageFullscreenState() {
            const isMobile = window.matchMedia('(max-width: 768px)').matches;
            const hasOpenConversation = !!activeConversationId;
            document.body.classList.toggle('message-fullscreen-mobile', isMobile && hasOpenConversation);
        }

        function formatTime(value) {
            if (!value) return '';
            const d = new Date(value.replace(' ', 'T'));
            if (Number.isNaN(d.getTime())) return value;
            return d.toLocaleString();
        }

        function getRequestedConversationFromLoadedList() {
            if (preferredConversationId) {
                const exact = conversations.find(c => Number(c.conversation_id) === Number(preferredConversationId));
                if (exact) {
                    preferredOrderId = exact.order_id || preferredOrderId;
                    return Number(exact.conversation_id);
                }
            }

            if (preferredOrderId) {
                const byOrder = conversations.find(c => Number(c.order_id) === Number(preferredOrderId));
                if (byOrder) {
                    preferredConversationId = Number(byOrder.conversation_id);
                    return Number(byOrder.conversation_id);
                }
            }

            return 0;
        }

        async function ensureOrderConversation(orderId) {
            if (!orderId || ensureConversationAttempted || currentRole === 'admin') {
                return 0;
            }

            ensureConversationAttempted = true;
            try {
                const res = await fetch('api/messages-ensure-conversation.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ order_id: Number(orderId) })
                });
                const data = await res.json();
                if (!data.success) {
                    return 0;
                }
                return Number(data.conversation_id || 0);
            } catch (err) {
                return 0;
            }
        }

        function applyContactPrefill() {
            if (hasAppliedContactPrefill) return;
            if (!requestedProduct && !preferredOrderId) return;

            const input = document.getElementById('messageInput');
            if (!input) return;

            if (input.value.trim()) {
                hasAppliedContactPrefill = true;
                return;
            }

            const parts = [];
            if (requestedProduct) {
                parts.push(`Hi, I have a question about \"${requestedProduct}\"`);
            } else {
                parts.push('Hi, I have a question about my order');
            }
            if (preferredOrderId) {
                parts.push(`(Order #${preferredOrderId})`);
            }
            input.value = `${parts.join(' ')}.`;
            hasAppliedContactPrefill = true;
        }

        async function loadConversations() {
            try {
                const res = await fetch('api/messages-get-conversations.php');
                const data = await res.json();
                if (!data.success) {
                    throw new Error(data.message || 'Failed to load conversations');
                }
                conversations = data.conversations || [];
                const unreadCount = conversations.reduce((sum, conversation) => {
                    return sum + Number(conversation.unread_count || 0);
                }, 0);
                setAdminMessagesBadge(unreadCount);
                renderConversations();

                if (!targetConversationResolved) {
                    let targetId = getRequestedConversationFromLoadedList();

                    if (!targetId && preferredOrderId) {
                        const ensuredId = await ensureOrderConversation(preferredOrderId);
                        if (ensuredId > 0) {
                            preferredConversationId = ensuredId;
                            targetId = ensuredId;
                            const existsInList = conversations.some(c => Number(c.conversation_id) === ensuredId);
                            if (!existsInList) {
                                const reloadRes = await fetch('api/messages-get-conversations.php');
                                const reloadData = await reloadRes.json();
                                if (reloadData.success) {
                                    conversations = reloadData.conversations || [];
                                    renderConversations();
                                }
                            }
                        }
                    }

                    if (targetId > 0) {
                        targetConversationResolved = true;
                        if (activeConversationId !== targetId) {
                            await selectConversation(targetId);
                        }
                        applyContactPrefill();
                        return;
                    }

                    targetConversationResolved = true;
                }

                if (!activeConversationId && conversations.length > 0) {
                    selectConversation(conversations[0].conversation_id);
                } else {
                    refreshMessageFullscreenState();
                }
            } catch (err) {
                document.getElementById('conversationList').innerHTML = '<div class="empty">Unable to load conversations.</div>';
                refreshMessageFullscreenState();
            }
        }

        function renderConversations() {
            const list = document.getElementById('conversationList');
            const search = document.getElementById('searchInput').value.trim().toLowerCase();
            const sortOrder = document.getElementById('sortSelect').value || 'newest';
            const filtered = conversations.filter(c => {
                const name = (c.user_name || c.subject || '').toLowerCase();
                const last = (c.last_message || '').toLowerCase();
                const orderText = c.order_id ? ('order #' + c.order_id) : '';
                return !search || name.includes(search) || last.includes(search) || orderText.includes(search);
            });

            filtered.sort((a, b) => {
                const at = Date.parse(String(a.last_message_at || '').replace(' ', 'T')) || 0;
                const bt = Date.parse(String(b.last_message_at || '').replace(' ', 'T')) || 0;
                return sortOrder === 'oldest' ? (at - bt) : (bt - at);
            });

            if (!filtered.length) {
                list.innerHTML = '<div class="empty">No conversations yet.</div>';
                renderConversationPagination(0, 0);
                return;
            }

            const totalPages = Math.max(1, Math.ceil(filtered.length / conversationsPerPage));
            if (currentConversationPage > totalPages) {
                currentConversationPage = totalPages;
            }
            if (currentConversationPage < 1) {
                currentConversationPage = 1;
            }

            const startIndex = (currentConversationPage - 1) * conversationsPerPage;
            const pageRows = filtered.slice(startIndex, startIndex + conversationsPerPage);

            list.innerHTML = pageRows.map(c => {
                const isActive = c.conversation_id === activeConversationId;
                const name = currentRole === 'admin' ? (c.user_name || ('User #' + c.user_id)) : (c.subject || 'Support');
                const subject = c.subject || (c.order_id ? ('Order #' + c.order_id) : 'General Support');
                const unread = c.unread_count > 0 ? `<span class="unread">${c.unread_count}</span>` : '';
                const previewText = summarizeConversationPreview(c.last_message || 'No messages yet');
                return `
                    <div class="conv ${isActive ? 'active' : ''}" onclick="selectConversation(${c.conversation_id})">
                        <div class="conv-top">
                            <div class="conv-name">${escapeHtml(name)}</div>
                            ${unread}
                        </div>
                        <div class="conv-subject">${escapeHtml(subject)}</div>
                        <div class="conv-last">${escapeHtml(previewText)}</div>
                        <div class="conv-time">${escapeHtml(formatTime(c.last_message_at))}</div>
                    </div>
                `;
            }).join('');

            renderConversationPagination(filtered.length, totalPages);
        }

        async function selectConversation(conversationId) {
            activeConversationId = conversationId;
            refreshMessageFullscreenState();
            renderConversations();
            document.getElementById('messageInput').disabled = false;
            document.getElementById('sendBtn').disabled = false;
            document.getElementById('messageAttachment').disabled = false;
            if (window.matchMedia('(max-width: 768px)').matches) {
                closeConversationDrawer();
            }

            try {
                const res = await fetch(`api/messages-get-messages.php?conversation_id=${conversationId}`);
                const data = await res.json();
                if (!data.success) {
                    throw new Error(data.message || 'Failed to load messages');
                }

                const conversation = data.conversation || {};
                activeConversationOrderId = conversation.order_id || null;
                const title = conversation.subject || (conversation.order_id ? `Order #${conversation.order_id}` : 'Support Chat');
                const adminUserName = String(conversation.user_name || '').trim();
                document.getElementById('chatTitle').textContent = title;
                document.getElementById('chatSub').textContent = currentRole === 'admin'
                    ? `Chat with ${adminUserName || ('User #' + conversation.user_id)}`
                    : 'Direct message with support/admin';

                renderMessages(data.messages || []);
                await markRead(conversationId);
                await loadConversations();
            } catch (err) {
                document.getElementById('messageList').innerHTML = '<div class="empty">Unable to load messages.</div>';
            }
        }

        function renderMessages(messages) {
            const list = document.getElementById('messageList');
            if (!messages.length) {
                list.innerHTML = '<div class="empty">No messages yet. Start the conversation below.</div>';
                return;
            }

            list.innerHTML = messages.map(m => {
                const isMine = (m.sender_role === currentRole) && (m.sender_id === currentUserId);
                const rowClass = isMine ? 'msg-row me' : 'msg-row';
                const bubbleClass = m.sender_role === 'system' ? 'bubble system' : (isMine ? 'bubble me' : 'bubble other');
                const label = m.sender_role === 'system' ? 'System' : (m.sender_role === 'admin' ? 'Admin' : 'User');
                const parsed = parseMessageContent(m.message_text || '');
                const targetUrl = getOrderTargetUrl();
                const isOrderNotice = (m.message_type === 'order_notice' || m.message_type === 'status_notice');
                const mediaHtml = parsed.chatMediaUrl
                    ? (parsed.chatMediaType === 'video'
                        ? `<video class="msg-preview-video" src="${escapeAttr(parsed.chatMediaUrl)}" controls playsinline preload="metadata"></video>`
                        : `<img class="msg-preview-image" src="${escapeAttr(parsed.chatMediaUrl)}" alt="Attachment">`)
                    : (parsed.imageUrl ? `<img class="msg-preview-image" src="${escapeAttr(parsed.imageUrl)}" alt="Product">` : '');
                const contentHtml = `${mediaHtml}<div>${formatMessageText(parsed.cleanText)}</div>`;
                const noticeHtml = isOrderNotice ? renderOrderNoticeCard(m, parsed, targetUrl) : contentHtml;
                // Add delete button if message is mine
                const deleteBtn = isMine ? `<button class="msg-delete-btn" onclick="deleteMessage(${m.message_id})" title="Delete">🗑️</button>` : '';
                // Add read/sent indicator if message is mine and not system
                let statusHtml = '';
                if (isMine && m.sender_role !== 'system') {
                    statusHtml = `<span class="msg-status">${m.is_read_visible ? 'Read' : 'Sent'}</span>`;
                }
                return `
                    <div class="${rowClass}">
                        <div class="${bubbleClass}">
                            ${noticeHtml}
                            <div class="meta">${escapeHtml(label)} · ${escapeHtml(formatTime(m.created_at))} ${statusHtml} ${deleteBtn}</div>
                        </div>
                    </div>
                `;
            }).join('');

            list.scrollTop = list.scrollHeight;
        }

        async function sendMessage() {
            const input = document.getElementById('messageInput');
            const text = input.value.trim();
            if (!activeConversationId || (!text && !pendingMessageAttachment)) return;

            try {
                const payload = new FormData();
                payload.append('conversation_id', String(activeConversationId));
                if (text) {
                    payload.append('message_text', text);
                }
                if (pendingMessageAttachment) {
                    payload.append('attachment', pendingMessageAttachment);
                }

                const res = await fetch('api/messages-send.php', {
                    method: 'POST',
                    body: payload
                });
                const data = await res.json();
                if (!data.success) {
                    throw new Error(data.message || 'Failed to send message');
                }
                input.value = '';
                clearMessageAttachment();
                await selectConversation(activeConversationId);
            } catch (err) {
                alert(err.message || 'Failed to send message.');
            }
        }

        function handleMessageAttachment(event) {
            const fileInput = event.target;
            const file = fileInput.files && fileInput.files[0] ? fileInput.files[0] : null;
            const meta = document.getElementById('attachmentMeta');
            const metaText = document.getElementById('attachmentMetaText');

            if (!file) {
                clearMessageAttachment();
                return;
            }

            const maxUploadBytes = 25 * 1024 * 1024;
            if (file.size > maxUploadBytes) {
                alert('Attachment is too large. Max allowed size is 25MB.');
                clearMessageAttachment();
                return;
            }

            const type = String(file.type || '').toLowerCase();
            if (!(type.startsWith('image/') || type.startsWith('video/'))) {
                alert('Only image or video files are allowed.');
                clearMessageAttachment();
                return;
            }

            pendingMessageAttachment = file;
            const sizeMb = (file.size / (1024 * 1024)).toFixed(2);
            metaText.textContent = `${file.name} (${sizeMb} MB)`;
            meta.classList.add('show');
        }

        function clearMessageAttachment() {
            pendingMessageAttachment = null;
            const input = document.getElementById('messageAttachment');
            const meta = document.getElementById('attachmentMeta');
            if (input) input.value = '';
            if (meta) meta.classList.remove('show');
        }

        async function markRead(conversationId) {
            await fetch('api/messages-mark-read.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ conversation_id: conversationId })
            });
        }

        function escapeHtml(value) {
            return String(value)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        function formatMessageText(value) {
            return escapeHtml(value).replace(/\n/g, '<br>');
        }

        function escapeAttr(value) {
            return String(value)
                .replace(/&/g, '&amp;')
                .replace(/"/g, '&quot;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;');
        }

        function parseMessageContent(value) {
            const text = String(value || '');
            const imageMatch = text.match(/\[PRODUCT_IMAGE\]([\s\S]*?)\[\/PRODUCT_IMAGE\]/i);
            const chatMediaMatch = text.match(/\[CHAT_MEDIA\]([\s\S]*?)\|([\s\S]*?)\[\/CHAT_MEDIA\]/i);
            const imageUrl = imageMatch ? String(imageMatch[1] || '').trim() : '';
            const chatMediaUrl = chatMediaMatch ? String(chatMediaMatch[1] || '').trim() : '';
            const chatMediaType = chatMediaMatch ? String(chatMediaMatch[2] || '').trim().toLowerCase() : '';
            const cleanText = text
                .replace(/\[PRODUCT_IMAGE\][\s\S]*?\[\/PRODUCT_IMAGE\]/ig, '')
                .replace(/\[CHAT_MEDIA\][\s\S]*?\[\/CHAT_MEDIA\]/ig, '')
                .trim();
            return { imageUrl, cleanText, chatMediaUrl, chatMediaType };
        }

        function summarizeConversationPreview(value) {
            const parsed = parseMessageContent(value || '');
            if (parsed.cleanText) {
                return parsed.cleanText;
            }
            if (parsed.chatMediaUrl) {
                return parsed.chatMediaType === 'video' ? 'Sent a video' : 'Sent an image';
            }
            if (parsed.imageUrl) {
                return 'Sent an image';
            }
            return 'No messages yet';
        }

        function parseNoticeParts(text) {
            const lines = String(text || '').split('\n').map(s => s.trim()).filter(Boolean);
            if (!lines.length) {
                return { title: 'Order Update', summary: '', details: '' };
            }

            const detailsIndex = lines.findIndex(l => l.toLowerCase() === 'order details');
            let summaryLines = [];
            let detailLines = [];

            if (detailsIndex >= 0) {
                summaryLines = lines.slice(0, detailsIndex);
                detailLines = lines.slice(detailsIndex);
            } else {
                summaryLines = lines.slice(0, Math.min(3, lines.length));
                detailLines = lines.slice(Math.min(3, lines.length));
            }

            const productLine = lines.find(l => l.toLowerCase().startsWith('products:')) || '';
            const title = productLine ? productLine.replace(/^products:\s*/i, '') : (summaryLines[0] || 'Order Update');
            const summary = summaryLines
                .filter(l => l.toLowerCase() !== 'hello! thank you for your order.' && !l.toLowerCase().startsWith('products:'))
                .join(' | ');
            const details = detailLines.join('\n');
            return { title, summary, details };
        }

        function renderOrderNoticeCard(message, parsed, targetUrl) {
            const parts = parseNoticeParts(parsed.cleanText);
            const detailsId = `notice-details-${message.message_id}`;
            const thumb = parsed.imageUrl
                ? `<img class="order-notice-thumb" src="${escapeAttr(parsed.imageUrl)}" alt="Product">`
                : '';

            return `
                <div class="order-notice-card">
                    <a class="order-notice-main" href="${escapeAttr(targetUrl)}">
                        ${thumb}
                        <div>
                            <div class="order-notice-title">${escapeHtml(parts.title)}</div>
                            <div class="order-notice-summary">${escapeHtml(parts.summary || 'Tap to view this order.')}</div>
                        </div>
                    </a>
                    <div class="order-notice-actions">
                        <a class="order-jump-link" href="${escapeAttr(targetUrl)}">Open order page</a>
                        <button type="button" class="order-detail-toggle" onclick="toggleNoticeDetails(event, '${escapeAttr(detailsId)}')">View details</button>
                    </div>
                    <div id="${escapeAttr(detailsId)}" class="order-details-panel">${escapeHtml(parts.details || parsed.cleanText)}</div>
                </div>
            `;
        }

        function toggleNoticeDetails(event, detailsId) {
            event.preventDefault();
            event.stopPropagation();
            const panel = document.getElementById(detailsId);
            if (!panel) return;
            panel.classList.toggle('open');
        }

        function getOrderTargetUrl() {
            const base = currentRole === 'admin' ? 'admin_orders.php' : 'purchase_history.php';
            if (!activeConversationOrderId) return base;
            return `${base}?order_id=${encodeURIComponent(activeConversationOrderId)}`;
        }

        function toggleConversationDrawer(event) {
            if (event) {
                event.preventDefault();
                event.stopPropagation();
            }
            const panel = document.getElementById('conversationPanel');
            const overlay = document.getElementById('conversationDrawerOverlay');
            if (!panel || !overlay) return;

            const shouldOpen = !panel.classList.contains('open');
            if (shouldOpen) {
                panel.classList.add('open');
                overlay.classList.add('open');
            } else {
                panel.classList.remove('open');
                overlay.classList.remove('open');
            }
        }

        function closeConversationDrawer() {
            const panel = document.getElementById('conversationPanel');
            const overlay = document.getElementById('conversationDrawerOverlay');
            if (panel) panel.classList.remove('open');
            if (overlay) overlay.classList.remove('open');
        }

        window.addEventListener('resize', function() {
            if (window.innerWidth > 768) {
                closeConversationDrawer();
            }
            refreshMessageFullscreenState();
        });

        document.getElementById('messageInput').addEventListener('keydown', function(e) {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                sendMessage();
            }
        });

        refreshMessageFullscreenState();
        loadConversations();
        setInterval(() => {
            loadConversations();
            if (activeConversationId) {
                selectConversation(activeConversationId);
            }
        }, 12000);


        // Local confirm modal for delete
function showLocalConfirmModal(title = 'Confirm', text = '', confirmText = 'Delete', cancelText = 'Cancel') {
    return new Promise((resolve) => {
        const overlay = document.createElement('div');
        overlay.style.position = 'fixed';
        overlay.style.inset = '0';
        overlay.style.background = 'rgba(30,40,60,0.18)';
        overlay.style.zIndex = '9999';
        overlay.style.display = 'flex';
        overlay.style.alignItems = 'center';
        overlay.style.justifyContent = 'center';

        const card = document.createElement('div');
        card.style.background = '#fff';
        card.style.borderRadius = '12px';
        card.style.boxShadow = '0 4px 32px rgba(0,0,0,0.13)';
        card.style.padding = '28px 24px 18px';
        card.style.maxWidth = '90vw';
        card.style.width = '340px';
        card.style.textAlign = 'center';
        card.innerHTML = `
            <div style="font-size:18px;font-weight:700;margin-bottom:10px;">${title}</div>
            <div style="font-size:14px;color:#444;margin-bottom:22px;">${text}</div>
            <div style="display:flex;gap:12px;justify-content:center;">
                <button id="localConfirmDeleteBtn" style="background:#e22a39;color:#fff;font-weight:700;border:none;border-radius:8px;padding:8px 18px;font-size:15px;cursor:pointer;">${confirmText}</button>
                <button id="localConfirmCancelBtn" style="background:#eee;color:#222;font-weight:700;border:none;border-radius:8px;padding:8px 18px;font-size:15px;cursor:pointer;">${cancelText}</button>
            </div>
        `;
        overlay.appendChild(card);
        document.body.appendChild(overlay);
        document.getElementById('localConfirmDeleteBtn').onclick = () => {
            document.body.removeChild(overlay);
            resolve(true);
        };
        document.getElementById('localConfirmCancelBtn').onclick = () => {
            document.body.removeChild(overlay);
            resolve(false);
        };
    });
}

// Local toast for feedback
function showLocalToast(text, type = 'success', duration = 1400) {
    const toast = document.createElement('div');
    toast.className = `local-toast ${type}`;
    toast.style.position = 'fixed';
    toast.style.bottom = '32px';
    toast.style.left = '50%';
    toast.style.transform = 'translateX(-50%)';
    toast.style.background = type === 'error' ? '#e22a39' : '#2f67d8';
    toast.style.color = '#fff';
    toast.style.fontWeight = '700';
    toast.style.fontSize = '15px';
    toast.style.padding = '12px 28px';
    toast.style.borderRadius = '8px';
    toast.style.boxShadow = '0 2px 12px rgba(0,0,0,0.13)';
    toast.style.zIndex = '9999';
    toast.textContent = text;
    document.body.appendChild(toast);
    setTimeout(() => {
        toast.style.transition = 'opacity 0.3s';
        toast.style.opacity = '0';
        setTimeout(() => { if (toast.parentNode) toast.parentNode.removeChild(toast); }, 320);
    }, duration);
}

// Replace deleteMessage to use local confirm
async function deleteMessage(messageId) {
    const confirmed = await showLocalConfirmModal('Delete Message', 'Are you sure you want to delete this message?', 'Delete', 'Cancel');
    if (!confirmed) return;
    try {
        const res = await fetch('api/messages-delete.php', {
            method: 'POST',
            body: new URLSearchParams({ message_id: messageId })
        });
        const data = await res.json();
        if (!data.success) throw new Error(data.message || 'Failed to delete message');
        await selectConversation(activeConversationId);
        showLocalToast('Message deleted', 'success');
    } catch (err) {
        showLocalToast(err.message || 'Failed to delete message', 'error');
    }
}
    </script>
</body>
</html>
