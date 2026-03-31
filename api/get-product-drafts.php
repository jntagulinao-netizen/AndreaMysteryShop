<?php
header('Content-Type: application/json; charset=utf-8');
session_start();
require __DIR__ . '/../dbConnection.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$role = $_SESSION['user_role'] ?? 'user';
if ($role !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Admin access required']);
    exit;
}

$adminUserId = (int)$_SESSION['user_id'];
$draftId = isset($_GET['draft_id']) ? (int)$_GET['draft_id'] : 0;

function fetchDraftVariants(mysqli $conn, int $draftId): array {
    $stmt = $conn->prepare('SELECT variant_id, client_variant_id, variant_order, variant_name, variant_price, variant_stock FROM product_draft_variants WHERE draft_id = ? ORDER BY variant_order ASC, variant_id ASC');
    if (!$stmt) {
        return [];
    }
    $stmt->bind_param('i', $draftId);
    $stmt->execute();
    $res = $stmt->get_result();
    $variants = [];
    while ($row = $res->fetch_assoc()) {
        $clientVariantId = (int)($row['client_variant_id'] ?? 0);
        $variants[] = [
            'id' => $clientVariantId > 0 ? $clientVariantId : (int)$row['variant_id'],
            'order' => (int)$row['variant_order'],
            'name' => (string)($row['variant_name'] ?? ''),
            'price' => isset($row['variant_price']) ? (float)$row['variant_price'] : null,
            'stock' => isset($row['variant_stock']) ? (int)$row['variant_stock'] : null
        ];
    }
    $stmt->close();
    return $variants;
}

function fetchDraftMedia(mysqli $conn, int $draftId): array {
    $stmt = $conn->prepare('SELECT media_role, client_variant_id, file_path, sort_order, is_pinned FROM product_draft_media WHERE draft_id = ? ORDER BY sort_order ASC, media_id ASC');
    if (!$stmt) {
        return [
            'images' => [],
            'video' => '',
            'variant_images' => []
        ];
    }

    $stmt->bind_param('i', $draftId);
    $stmt->execute();
    $res = $stmt->get_result();

    $images = [];
    $video = '';
    $variantImages = [];

    while ($row = $res->fetch_assoc()) {
        $role = (string)($row['media_role'] ?? '');
        $path = (string)($row['file_path'] ?? '');
        $clientVariantId = (int)($row['client_variant_id'] ?? 0);

        if ($role === 'main_image' && $path !== '') {
            $images[] = [
                'path' => $path,
                'is_pinned' => (int)($row['is_pinned'] ?? 0) === 1
            ];
        } elseif ($role === 'video' && $path !== '') {
            $video = $path;
        } elseif ($role === 'variant_image' && $path !== '' && $clientVariantId > 0) {
            $variantImages[(string)$clientVariantId] = $path;
        }
    }

    $stmt->close();

    return [
        'images' => $images,
        'video' => $video,
        'variant_images' => $variantImages
    ];
}

try {
    if ($draftId > 0) {
        $stmt = $conn->prepare('SELECT draft_id, admin_user_id, product_name, product_description, price, product_stock, use_new_category, category_id, new_category_name, pinned_image_index, image_count, has_video, created_at, updated_at FROM product_drafts WHERE draft_id = ? AND admin_user_id = ? LIMIT 1');
        if (!$stmt) {
            throw new Exception('Failed to prepare draft lookup');
        }
        $stmt->bind_param('ii', $draftId, $adminUserId);
        $stmt->execute();
        $res = $stmt->get_result();
        $row = $res ? $res->fetch_assoc() : null;
        $stmt->close();

        if (!$row) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Draft not found']);
            exit;
        }

        $draftMedia = fetchDraftMedia($conn, (int)$row['draft_id']);

        $draft = [
            'draft_id' => (int)$row['draft_id'],
            'product_name' => (string)($row['product_name'] ?? ''),
            'product_description' => (string)($row['product_description'] ?? ''),
            'price' => $row['price'] !== null ? (string)$row['price'] : '',
            'product_stock' => $row['product_stock'] !== null ? (string)$row['product_stock'] : '',
            'use_new_category' => (int)($row['use_new_category'] ?? 0),
            'category_id' => $row['category_id'] !== null ? (int)$row['category_id'] : '',
            'new_category_name' => (string)($row['new_category_name'] ?? ''),
            'pinned_image_index' => (int)($row['pinned_image_index'] ?? 0),
            'image_count' => (int)($row['image_count'] ?? 0),
            'has_video' => (int)($row['has_video'] ?? 0),
            'created_at' => $row['created_at'] ?? null,
            'updated_at' => $row['updated_at'] ?? null,
            'media' => $draftMedia,
            'variants' => fetchDraftVariants($conn, (int)$row['draft_id'])
        ];

        echo json_encode(['success' => true, 'draft' => $draft]);
        exit;
    }

    $stmt = $conn->prepare('SELECT d.draft_id, d.product_name, d.price, d.product_stock, d.use_new_category, d.category_id, d.new_category_name, d.image_count, d.has_video, d.created_at, d.updated_at, c.category_name FROM product_drafts d LEFT JOIN categories c ON c.category_id = d.category_id WHERE d.admin_user_id = ? ORDER BY d.updated_at DESC, d.draft_id DESC');
    if (!$stmt) {
        throw new Exception('Failed to prepare drafts list');
    }
    $stmt->bind_param('i', $adminUserId);
    $stmt->execute();
    $res = $stmt->get_result();

    $drafts = [];
    while ($row = $res->fetch_assoc()) {
        $countStmt = $conn->prepare('SELECT COUNT(*) AS variant_count FROM product_draft_variants WHERE draft_id = ?');
        $variantCount = 0;
        if ($countStmt) {
            $draftIdRow = (int)$row['draft_id'];
            $countStmt->bind_param('i', $draftIdRow);
            $countStmt->execute();
            $countRes = $countStmt->get_result();
            $countRow = $countRes ? $countRes->fetch_assoc() : null;
            $variantCount = (int)($countRow['variant_count'] ?? 0);
            $countStmt->close();
        }

        $drafts[] = [
            'draft_id' => (int)$row['draft_id'],
            'product_name' => (string)($row['product_name'] ?? ''),
            'price' => $row['price'] !== null ? (float)$row['price'] : null,
            'product_stock' => $row['product_stock'] !== null ? (int)$row['product_stock'] : null,
            'category_label' => (int)($row['use_new_category'] ?? 0) === 1
                ? (string)($row['new_category_name'] ?? 'New Category')
                : (string)($row['category_name'] ?? 'No Category'),
            'image_count' => (int)($row['image_count'] ?? 0),
            'has_video' => (int)($row['has_video'] ?? 0),
            'variant_count' => $variantCount,
            'created_at' => $row['created_at'] ?? null,
            'updated_at' => $row['updated_at'] ?? null
        ];
    }
    $stmt->close();

    echo json_encode(['success' => true, 'drafts' => $drafts]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
