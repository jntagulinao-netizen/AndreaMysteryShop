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

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

$adminUserId = (int)$_SESSION['user_id'];
$draftId = isset($_POST['draft_id']) ? (int)$_POST['draft_id'] : 0;
$productName = trim((string)($_POST['product_name'] ?? ''));
$productDescription = trim((string)($_POST['product_description'] ?? ''));
$priceRaw = trim((string)($_POST['price'] ?? ''));
$productStockRaw = trim((string)($_POST['product_stock'] ?? ''));
$useNewCategory = (int)(($_POST['use_new_category'] ?? '0') === '1');
$categoryId = isset($_POST['category_id']) && $_POST['category_id'] !== '' ? (int)$_POST['category_id'] : null;
$newCategoryName = trim((string)($_POST['new_category_name'] ?? ''));
$pinnedImageKeyRaw = trim((string)($_POST['pinned_image_key'] ?? ''));
$pinnedImageIndex = isset($_POST['pinned_image_index']) ? (int)$_POST['pinned_image_index'] : 0;
$imageCount = isset($_POST['image_count']) ? (int)$_POST['image_count'] : 0;
$hasVideo = (int)(($_POST['has_video'] ?? '0') === '1');
$variantsRaw = trim((string)($_POST['variants'] ?? '[]'));

$finfo = new finfo(FILEINFO_MIME_TYPE);
$allowedImageMimes = [
    'image/jpeg' => 'jpg',
    'image/png' => 'png',
    'image/webp' => 'webp',
    'image/gif' => 'gif'
];
$allowedVideoMimes = [
    'video/mp4' => 'mp4',
    'video/webm' => 'webm',
    'video/quicktime' => 'mov'
];

$rootPath = realpath(__DIR__ . '/..');
$draftMediaAbsoluteDir = $rootPath ? ($rootPath . DIRECTORY_SEPARATOR . 'product_media' . DIRECTORY_SEPARATOR . 'drafts') : '';
if (!$draftMediaAbsoluteDir || (!is_dir($draftMediaAbsoluteDir) && !mkdir($draftMediaAbsoluteDir, 0775, true))) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Failed to prepare draft media directory']);
    exit;
}

function getUploadedMainImages(): array {
    $images = [];
    if (!isset($_FILES['images']) || !is_array($_FILES['images']['name'] ?? null)) {
        return $images;
    }
    $count = count($_FILES['images']['name']);
    for ($i = 0; $i < $count; $i++) {
        $error = $_FILES['images']['error'][$i] ?? UPLOAD_ERR_NO_FILE;
        if ($error === UPLOAD_ERR_NO_FILE) {
            continue;
        }
        $images[] = [
            'name' => $_FILES['images']['name'][$i] ?? '',
            'tmp_name' => $_FILES['images']['tmp_name'][$i] ?? '',
            'error' => (int)$error,
            'size' => (int)($_FILES['images']['size'][$i] ?? 0)
        ];
    }
    return $images;
}

function getUploadedVariantImages(): array {
    $variantImages = [];
    foreach ($_FILES as $field => $file) {
        $fieldName = (string)$field;
        $clientVariantId = 0;
        $sortOrder = 0;

        if (preg_match('/^variant_(\d+)_image_(\d+)$/', $fieldName, $matches)) {
            $clientVariantId = (int)$matches[1];
            $sortOrder = (int)$matches[2];
        } elseif (strpos($fieldName, 'variant_image_') === 0) {
            $clientVariantId = (int)substr($fieldName, strlen('variant_image_'));
        } else {
            continue;
        }

        if ($clientVariantId <= 0) {
            continue;
        }
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            continue;
        }

        if (!isset($variantImages[$clientVariantId])) {
            $variantImages[$clientVariantId] = [];
        }

        $variantImages[$clientVariantId][] = [
            'name' => $file['name'] ?? '',
            'tmp_name' => $file['tmp_name'] ?? '',
            'error' => (int)($file['error'] ?? UPLOAD_ERR_NO_FILE),
            'size' => (int)($file['size'] ?? 0),
            'sort_order' => $sortOrder
        ];
    }

    foreach ($variantImages as $variantId => $files) {
        usort($files, static function (array $a, array $b): int {
            return ((int)($a['sort_order'] ?? 0)) <=> ((int)($b['sort_order'] ?? 0));
        });
        $variantImages[$variantId] = $files;
    }

    return $variantImages;
}

function saveDraftUpload(array $file, finfo $finfo, array $allowedMimes, string $prefix, string $absoluteDir): array {
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        throw new Exception('Invalid uploaded file');
    }

    $mime = $finfo->file((string)($file['tmp_name'] ?? ''));
    if (!isset($allowedMimes[$mime])) {
        throw new Exception('Unsupported file type');
    }

    $ext = $allowedMimes[$mime];
    $fileName = $prefix . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    $absolutePath = $absoluteDir . DIRECTORY_SEPARATOR . $fileName;
    $relativePath = 'product_media/drafts/' . $fileName;

    if (!move_uploaded_file((string)$file['tmp_name'], $absolutePath)) {
        throw new Exception('Failed to save uploaded file');
    }

    return ['absolute' => $absolutePath, 'relative' => $relativePath];
}

function fetchDraftMediaPaths(mysqli $conn, int $draftId, ?string $role = null, ?int $clientVariantId = null): array {
    $sql = 'SELECT media_id, client_variant_id, file_path FROM product_draft_media WHERE draft_id = ?';
    $types = 'i';
    $params = [$draftId];

    if ($role !== null) {
        $sql .= ' AND media_role = ?';
        $types .= 's';
        $params[] = $role;
    }
    if ($clientVariantId !== null) {
        $sql .= ' AND client_variant_id = ?';
        $types .= 'i';
        $params[] = $clientVariantId;
    }

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        return [];
    }
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $res = $stmt->get_result();
    $rows = [];
    while ($row = $res->fetch_assoc()) {
        $rows[] = [
            'media_id' => (int)($row['media_id'] ?? 0),
            'client_variant_id' => (int)($row['client_variant_id'] ?? 0),
            'file_path' => (string)($row['file_path'] ?? '')
        ];
    }
    $stmt->close();
    return $rows;
}

function deleteDraftMediaRows(mysqli $conn, int $draftId, ?string $role = null, ?int $clientVariantId = null): void {
    $sql = 'DELETE FROM product_draft_media WHERE draft_id = ?';
    $types = 'i';
    $params = [$draftId];

    if ($role !== null) {
        $sql .= ' AND media_role = ?';
        $types .= 's';
        $params[] = $role;
    }
    if ($clientVariantId !== null) {
        $sql .= ' AND client_variant_id = ?';
        $types .= 'i';
        $params[] = $clientVariantId;
    }

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        return;
    }
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $stmt->close();
}

function absolutePathFromRelative(string $relativePath, string $rootPath): string {
    $normalized = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $relativePath);
    return rtrim($rootPath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . ltrim($normalized, DIRECTORY_SEPARATOR);
}

$price = null;
if ($priceRaw !== '') {
    if (!is_numeric($priceRaw) || (float)$priceRaw < 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Price must be a non-negative number']);
        exit;
    }
    $price = round((float)$priceRaw, 2);
}

$productStock = null;
if ($productStockRaw !== '') {
    if (!is_numeric($productStockRaw) || (int)$productStockRaw < 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Stock must be a non-negative integer']);
        exit;
    }
    $productStock = (int)$productStockRaw;
}

if ($imageCount < 0) {
    $imageCount = 0;
}
if ($pinnedImageIndex < 0) {
    $pinnedImageIndex = 0;
}
if ($pinnedImageKeyRaw !== '' && preg_match('/^[en]:(\d+)$/', $pinnedImageKeyRaw, $matches)) {
    $pinnedImageIndex = (int)$matches[1];
}

$variantsDecoded = json_decode($variantsRaw, true);
if (!is_array($variantsDecoded)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid variants format']);
    exit;
}

$variants = [];
$variantOrder = 0;
$variantClientIds = [];
foreach ($variantsDecoded as $item) {
    if (!is_array($item)) {
        continue;
    }

    $variantName = trim((string)($item['name'] ?? ''));
    $variantPriceRaw = trim((string)($item['price'] ?? ''));
    $variantStockRaw = trim((string)($item['stock'] ?? ''));

    if ($variantName === '' && $variantPriceRaw === '' && $variantStockRaw === '') {
        continue;
    }

    $variantPrice = null;
    if ($variantPriceRaw !== '') {
        if (!is_numeric($variantPriceRaw) || (float)$variantPriceRaw < 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Variant price must be a non-negative number']);
            exit;
        }
        $variantPrice = round((float)$variantPriceRaw, 2);
    }

    $variantStock = null;
    if ($variantStockRaw !== '') {
        if (!is_numeric($variantStockRaw) || (int)$variantStockRaw < 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Variant stock must be a non-negative integer']);
            exit;
        }
        $variantStock = (int)$variantStockRaw;
    }

    $variants[] = [
        'client_variant_id' => 0,
        'variant_order' => $variantOrder++,
        'variant_name' => $variantName,
        'variant_price' => $variantPrice,
        'variant_stock' => $variantStock,
        'pinned_image_key' => trim((string)($item['pinned_image_key'] ?? $item['pinnedImageKey'] ?? ''))
    ];

    $clientVariantId = (int)($item['id'] ?? 0);
    if ($clientVariantId <= 0) {
        $clientVariantId = (int)($item['temp_id'] ?? 0);
    }
    if ($clientVariantId <= 0) {
        $clientVariantId = 100000 + $variantOrder;
    }

    $variants[count($variants) - 1]['client_variant_id'] = $clientVariantId;
    if ($clientVariantId > 0) {
        $variantClientIds[] = $clientVariantId;
    }
}

$mainImageFiles = getUploadedMainImages();
if (count($mainImageFiles) > 8) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Maximum 8 images allowed for draft']);
    exit;
}

$videoFile = null;
if (isset($_FILES['video']) && is_array($_FILES['video']) && (($_FILES['video']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE)) {
    $videoFile = [
        'name' => $_FILES['video']['name'] ?? '',
        'tmp_name' => $_FILES['video']['tmp_name'] ?? '',
        'error' => (int)($_FILES['video']['error'] ?? UPLOAD_ERR_NO_FILE),
        'size' => (int)($_FILES['video']['size'] ?? 0)
    ];
}

$variantImageFiles = getUploadedVariantImages();

$conn->begin_transaction();
$newlyCreatedFiles = [];

try {
    if ($draftId > 0) {
        $checkStmt = $conn->prepare('SELECT draft_id FROM product_drafts WHERE draft_id = ? AND admin_user_id = ? LIMIT 1');
        if (!$checkStmt) {
            throw new Exception('Failed to prepare ownership check');
        }
        $checkStmt->bind_param('ii', $draftId, $adminUserId);
        $checkStmt->execute();
        $existing = $checkStmt->get_result()->fetch_assoc();
        $checkStmt->close();

        if (!$existing) {
            throw new Exception('Draft not found or not allowed');
        }

        $updateStmt = $conn->prepare('UPDATE product_drafts SET product_name = ?, product_description = ?, price = ?, product_stock = ?, use_new_category = ?, category_id = ?, new_category_name = ?, pinned_image_index = ?, image_count = ?, has_video = ? WHERE draft_id = ? AND admin_user_id = ?');
        if (!$updateStmt) {
            throw new Exception('Failed to prepare draft update');
        }
        $updateStmt->bind_param(
            'ssdiiisiiiii',
            $productName,
            $productDescription,
            $price,
            $productStock,
            $useNewCategory,
            $categoryId,
            $newCategoryName,
            $pinnedImageIndex,
            $imageCount,
            $hasVideo,
            $draftId,
            $adminUserId
        );
        if (!$updateStmt->execute()) {
            throw new Exception('Failed to update draft');
        }
        $updateStmt->close();

        $clearVariantsStmt = $conn->prepare('DELETE FROM product_draft_variants WHERE draft_id = ?');
        if (!$clearVariantsStmt) {
            throw new Exception('Failed to clear old variants');
        }
        $clearVariantsStmt->bind_param('i', $draftId);
        $clearVariantsStmt->execute();
        $clearVariantsStmt->close();
    } else {
        $insertStmt = $conn->prepare('INSERT INTO product_drafts (admin_user_id, product_name, product_description, price, product_stock, use_new_category, category_id, new_category_name, pinned_image_index, image_count, has_video) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
        if (!$insertStmt) {
            throw new Exception('Failed to prepare draft insert');
        }
        $insertStmt->bind_param(
            'issdiiisiii',
            $adminUserId,
            $productName,
            $productDescription,
            $price,
            $productStock,
            $useNewCategory,
            $categoryId,
            $newCategoryName,
            $pinnedImageIndex,
            $imageCount,
            $hasVideo
        );
        if (!$insertStmt->execute()) {
            throw new Exception('Failed to create draft');
        }
        $draftId = (int)$conn->insert_id;
        $insertStmt->close();
    }

    if (!empty($variants)) {
        $insertVariantStmt = $conn->prepare('INSERT INTO product_draft_variants (draft_id, client_variant_id, variant_order, variant_name, variant_price, variant_stock) VALUES (?, ?, ?, ?, ?, ?)');
        if (!$insertVariantStmt) {
            throw new Exception('Failed to prepare variant insert');
        }

        foreach ($variants as $variant) {
            $clientVariantIdVal = (int)($variant['client_variant_id'] ?? 0);
            $variantOrderVal = (int)$variant['variant_order'];
            $variantNameVal = $variant['variant_name'];
            $variantPriceVal = $variant['variant_price'];
            $variantStockVal = $variant['variant_stock'];
            $insertVariantStmt->bind_param('iiisdi', $draftId, $clientVariantIdVal, $variantOrderVal, $variantNameVal, $variantPriceVal, $variantStockVal);
            if (!$insertVariantStmt->execute()) {
                throw new Exception('Failed to save variants');
            }
        }
        $insertVariantStmt->close();
    }

    // Clean up variant media for removed variants.
    $orphanRows = fetchDraftMediaPaths($conn, $draftId, 'variant_image', null);
    if (!empty($orphanRows)) {
        $validSet = array_flip($variantClientIds);
        foreach ($orphanRows as $row) {
            $clientVariantId = (int)($row['client_variant_id'] ?? 0);
            if ($clientVariantId > 0 && !isset($validSet[$clientVariantId])) {
                deleteDraftMediaRows($conn, $draftId, 'variant_image', $clientVariantId);
                $abs = absolutePathFromRelative((string)($row['file_path'] ?? ''), $rootPath ?: '');
                if ($abs && is_file($abs)) {
                    @unlink($abs);
                }
            }
        }
    }

    // Save new main images if uploaded.
    if (!empty($mainImageFiles)) {
        $existingMain = fetchDraftMediaPaths($conn, $draftId, 'main_image', null);
        foreach ($existingMain as $oldMedia) {
            $oldAbs = absolutePathFromRelative((string)$oldMedia['file_path'], $rootPath ?: '');
            if ($oldAbs && is_file($oldAbs)) {
                @unlink($oldAbs);
            }
        }
        deleteDraftMediaRows($conn, $draftId, 'main_image', null);

        $insertMediaStmt = $conn->prepare('INSERT INTO product_draft_media (draft_id, media_role, client_variant_id, file_path, sort_order, is_pinned) VALUES (?, ?, NULL, ?, ?, ?)');
        if (!$insertMediaStmt) {
            throw new Exception('Failed to prepare draft image insert');
        }

        foreach ($mainImageFiles as $idx => $file) {
            $saved = saveDraftUpload($file, $finfo, $allowedImageMimes, 'draft_' . $draftId . '_img_' . ($idx + 1), $draftMediaAbsoluteDir);
            $newlyCreatedFiles[] = $saved['absolute'];
            $role = 'main_image';
            $relativePath = $saved['relative'];
            $sortOrder = (int)$idx;
            $isPinned = $idx === $pinnedImageIndex ? 1 : 0;
            $insertMediaStmt->bind_param('issii', $draftId, $role, $relativePath, $sortOrder, $isPinned);
            if (!$insertMediaStmt->execute()) {
                throw new Exception('Failed to save draft image record');
            }
        }
        $insertMediaStmt->close();
    }

    // Save new video if uploaded.
    if ($videoFile) {
        $existingVideos = fetchDraftMediaPaths($conn, $draftId, 'video', null);
        foreach ($existingVideos as $oldMedia) {
            $oldAbs = absolutePathFromRelative((string)$oldMedia['file_path'], $rootPath ?: '');
            if ($oldAbs && is_file($oldAbs)) {
                @unlink($oldAbs);
            }
        }
        deleteDraftMediaRows($conn, $draftId, 'video', null);

        $savedVideo = saveDraftUpload($videoFile, $finfo, $allowedVideoMimes, 'draft_' . $draftId . '_video', $draftMediaAbsoluteDir);
        $newlyCreatedFiles[] = $savedVideo['absolute'];

        $insertVideoStmt = $conn->prepare('INSERT INTO product_draft_media (draft_id, media_role, client_variant_id, file_path, sort_order, is_pinned) VALUES (?, ?, NULL, ?, 0, 0)');
        if (!$insertVideoStmt) {
            throw new Exception('Failed to prepare draft video insert');
        }
        $videoRole = 'video';
        $videoRel = $savedVideo['relative'];
        $insertVideoStmt->bind_param('iss', $draftId, $videoRole, $videoRel);
        if (!$insertVideoStmt->execute()) {
            throw new Exception('Failed to save draft video record');
        }
        $insertVideoStmt->close();
    }

    // Save new variant images if uploaded.
    if (!empty($variantImageFiles)) {
        $variantPinnedByClientId = [];
        foreach ($variants as $variant) {
            $variantPinnedByClientId[(int)$variant['client_variant_id']] = (string)($variant['pinned_image_key'] ?? '');
        }

        $insertVariantMediaStmt = $conn->prepare('INSERT INTO product_draft_media (draft_id, media_role, client_variant_id, file_path, sort_order, is_pinned) VALUES (?, ?, ?, ?, ?, ?)');
        if (!$insertVariantMediaStmt) {
            throw new Exception('Failed to prepare draft variant image insert');
        }

        foreach ($variantImageFiles as $clientVariantId => $variantFilesForClient) {
            if (!in_array((int)$clientVariantId, $variantClientIds, true)) {
                continue;
            }

            $existingVariantMedia = fetchDraftMediaPaths($conn, $draftId, 'variant_image', (int)$clientVariantId);
            foreach ($existingVariantMedia as $oldMedia) {
                $oldAbs = absolutePathFromRelative((string)$oldMedia['file_path'], $rootPath ?: '');
                if ($oldAbs && is_file($oldAbs)) {
                    @unlink($oldAbs);
                }
            }
            deleteDraftMediaRows($conn, $draftId, 'variant_image', (int)$clientVariantId);

            $variantPinnedKey = (string)($variantPinnedByClientId[(int)$clientVariantId] ?? '');
            $pinnedVariantImageIdx = 0;
            if (preg_match('/^[en]:(\d+)$/', $variantPinnedKey, $pinnedMatches)) {
                $pinnedVariantImageIdx = (int)$pinnedMatches[1];
            }

            $mediaSortOrder = 0;
            foreach ($variantFilesForClient as $uploadedVariantFile) {
                $savedVariant = saveDraftUpload($uploadedVariantFile, $finfo, $allowedImageMimes, 'draft_' . $draftId . '_variant_' . (int)$clientVariantId . '_' . $mediaSortOrder, $draftMediaAbsoluteDir);
                $newlyCreatedFiles[] = $savedVariant['absolute'];
                $role = 'variant_image';
                $clientVariantIdVal = (int)$clientVariantId;
                $variantRel = $savedVariant['relative'];
                $sortOrder = $mediaSortOrder;
                $isPinned = $mediaSortOrder === $pinnedVariantImageIdx ? 1 : 0;
                $insertVariantMediaStmt->bind_param('isisii', $draftId, $role, $clientVariantIdVal, $variantRel, $sortOrder, $isPinned);
                if (!$insertVariantMediaStmt->execute()) {
                    throw new Exception('Failed to save draft variant image record');
                }
                $mediaSortOrder++;
            }
        }

        $insertVariantMediaStmt->close();
    }

    $conn->commit();
    echo json_encode([
        'success' => true,
        'draft_id' => $draftId,
        'message' => 'Draft saved successfully'
    ]);
} catch (Exception $e) {
    $conn->rollback();
    foreach ($newlyCreatedFiles as $absPath) {
        if (is_file($absPath)) {
            @unlink($absPath);
        }
    }
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
