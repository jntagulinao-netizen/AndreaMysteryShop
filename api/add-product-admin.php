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

$productName = trim($_POST['product_name'] ?? '');
$productDescription = trim($_POST['product_description'] ?? '');
$priceRaw = trim($_POST['price'] ?? '');
$stockRaw = trim($_POST['product_stock'] ?? '');
$categoryMode = trim($_POST['category_mode'] ?? 'existing');
$categoryIdRaw = trim($_POST['category_id'] ?? '0');
$newCategoryName = trim($_POST['new_category_name'] ?? '');
$pinnedImageIndexRaw = trim($_POST['pinned_image_index'] ?? '0');
$draftIdRaw = trim($_POST['draft_id'] ?? '0');
$variantsRaw = trim($_POST['variants'] ?? '');

error_log('=== ADD PRODUCT API ===');
error_log('Product Name: ' . $productName);
error_log('Variants Raw: ' . $variantsRaw);
error_log('Variants Raw Length: ' . strlen($variantsRaw));

if ($productName === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Product name is required']);
    exit;
}

if (!is_numeric($stockRaw) || (int)$stockRaw < 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Stock must be a non-negative integer']);
    exit;
}

$price = 0.00;
$productStock = (int)$stockRaw;
$categoryId = 0;
$draftId = (is_numeric($draftIdRaw) && (int)$draftIdRaw > 0) ? (int)$draftIdRaw : 0;
$variants = [];

// Always validate and parse main product price (needed for both simple and variant products)
if (!is_numeric($priceRaw) || (float)$priceRaw < 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Price must be a non-negative number']);
    exit;
}
$price = round((float)$priceRaw, 2);

if ($variantsRaw !== '') {
    $decodedVariants = json_decode($variantsRaw, true);
    if (!is_array($decodedVariants)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid variants format']);
        exit;
    }

    error_log('Decoded variants count: ' . count($decodedVariants));
    error_log('Decoded variants: ' . json_encode($decodedVariants));

    foreach ($decodedVariants as $item) {
        if (!is_array($item)) {
            continue;
        }

        $variantId = (int)($item['id'] ?? 0);
        $variantName = trim((string)($item['name'] ?? ''));
        $variantPriceRaw = trim((string)($item['price'] ?? ''));
        $variantStockRaw = trim((string)($item['stock'] ?? ''));

        if ($variantName === '' && $variantPriceRaw === '' && $variantStockRaw === '') {
            continue;
        }

        if ($variantName === '') {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Each variant must have a name']);
            exit;
        }

        if ($variantId <= 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Each variant must have a valid ID']);
            exit;
        }

        if (!is_numeric($variantPriceRaw) || (float)$variantPriceRaw < 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Each variant price must be a non-negative number']);
            exit;
        }

        if (!is_numeric($variantStockRaw) || (int)$variantStockRaw < 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Each variant stock must be a non-negative integer']);
            exit;
        }

        $variants[] = [
            'id' => $variantId,
            'name' => $variantName,
            'price' => round((float)$variantPriceRaw, 2),
            'stock' => (int)$variantStockRaw
        ];
    }
}


$imageFiles = [];
if (isset($_FILES['images']) && is_array($_FILES['images']['name'])) {
    $count = count($_FILES['images']['name']);
    for ($i = 0; $i < $count; $i++) {
        $error = $_FILES['images']['error'][$i] ?? UPLOAD_ERR_NO_FILE;
        if ($error === UPLOAD_ERR_NO_FILE) {
            continue;
        }
        $imageFiles[] = [
            'name' => $_FILES['images']['name'][$i] ?? '',
            'tmp_name' => $_FILES['images']['tmp_name'][$i] ?? '',
            'error' => $error,
            'size' => (int)($_FILES['images']['size'][$i] ?? 0)
        ];
    }
}

if (count($imageFiles) > 8) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'You can upload up to 8 images only']);
    exit;
}

$pinnedImageIndex = is_numeric($pinnedImageIndexRaw) ? (int)$pinnedImageIndexRaw : 0;
if (!empty($imageFiles)) {
    if ($pinnedImageIndex < 0 || $pinnedImageIndex >= count($imageFiles)) {
        $pinnedImageIndex = 0;
    }
} else {
    $pinnedImageIndex = 0;
}

$videoFile = null;
if (isset($_FILES['video']) && is_array($_FILES['video'])) {
    if (($_FILES['video']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
        $videoFile = [
            'name' => $_FILES['video']['name'] ?? '',
            'tmp_name' => $_FILES['video']['tmp_name'] ?? '',
            'error' => (int)($_FILES['video']['error'] ?? UPLOAD_ERR_NO_FILE),
            'size' => (int)($_FILES['video']['size'] ?? 0)
        ];
    }
}

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

function absolutePathFromRelative(string $relativePath): string {
    $root = realpath(__DIR__ . '/..');
    $normalized = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $relativePath);
    return rtrim((string)$root, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . ltrim($normalized, DIRECTORY_SEPARATOR);
}

foreach ($imageFiles as $file) {
    if ($file['error'] !== UPLOAD_ERR_OK) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'One of the uploaded images is invalid']);
        exit;
    }

    $mime = $finfo->file($file['tmp_name']);
    if (!isset($allowedImageMimes[$mime])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Only JPG, PNG, WEBP, and GIF images are allowed']);
        exit;
    }
}

$variantImageFiles = [];
if (count($variants) > 0) {
    foreach ($variants as $variant) {
        $variantId = (int)$variant['id'];
        $variantImageKey = 'variant_image_' . $variantId;

        if (!isset($_FILES[$variantImageKey])) {
            continue;
        }

        $variantImageFile = [
            'name' => $_FILES[$variantImageKey]['name'] ?? '',
            'tmp_name' => $_FILES[$variantImageKey]['tmp_name'] ?? '',
            'error' => (int)($_FILES[$variantImageKey]['error'] ?? UPLOAD_ERR_NO_FILE),
            'size' => (int)($_FILES[$variantImageKey]['size'] ?? 0)
        ];

        if ($variantImageFile['error'] === UPLOAD_ERR_NO_FILE) {
            continue;
        }

        if ($variantImageFile['error'] !== UPLOAD_ERR_OK) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Each variant must have one valid image']);
            exit;
        }

        $variantMime = $finfo->file($variantImageFile['tmp_name']);
        if (!isset($allowedImageMimes[$variantMime])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Variant image must be JPG, PNG, WEBP, or GIF']);
            exit;
        }

        $variantImageFiles[$variantId] = $variantImageFile;
    }
}

if ($videoFile) {
    if ($videoFile['error'] !== UPLOAD_ERR_OK) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Uploaded video is invalid']);
        exit;
    }

    $videoMime = $finfo->file($videoFile['tmp_name']);
    if (!isset($allowedVideoMimes[$videoMime])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Only MP4, WEBM, or MOV video is allowed']);
        exit;
    }
}

$uploadDirAbsolute = realpath(__DIR__ . '/..') . DIRECTORY_SEPARATOR . 'product_media';
if (!$uploadDirAbsolute || (!is_dir($uploadDirAbsolute) && !mkdir($uploadDirAbsolute, 0775, true))) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Failed to prepare upload directory']);
    exit;
}

$conn->begin_transaction();
$createdFiles = [];

try {
    $draftMedia = [
        'main_images' => [],
        'video' => null,
        'variant_images' => []
    ];

    if ($draftId > 0) {
        $adminUserId = (int)$_SESSION['user_id'];
        $draftStmt = $conn->prepare('SELECT draft_id FROM product_drafts WHERE draft_id = ? AND admin_user_id = ? LIMIT 1');
        if (!$draftStmt) {
            throw new Exception('Failed to validate draft');
        }
        $draftStmt->bind_param('ii', $draftId, $adminUserId);
        $draftStmt->execute();
        $draftRow = $draftStmt->get_result()->fetch_assoc();
        $draftStmt->close();

        if (!$draftRow) {
            throw new Exception('Draft not found or not allowed');
        }

        $mediaStmt = $conn->prepare('SELECT media_role, client_variant_id, file_path, sort_order, is_pinned FROM product_draft_media WHERE draft_id = ? ORDER BY sort_order ASC, media_id ASC');
        if ($mediaStmt) {
            $mediaStmt->bind_param('i', $draftId);
            $mediaStmt->execute();
            $mediaRes = $mediaStmt->get_result();
            while ($mediaRow = $mediaRes->fetch_assoc()) {
                $role = (string)($mediaRow['media_role'] ?? '');
                $path = (string)($mediaRow['file_path'] ?? '');
                $clientVariantId = (int)($mediaRow['client_variant_id'] ?? 0);
                if ($path === '') {
                    continue;
                }

                if ($role === 'main_image') {
                    $draftMedia['main_images'][] = [
                        'path' => $path,
                        'is_pinned' => (int)($mediaRow['is_pinned'] ?? 0) === 1
                    ];
                } elseif ($role === 'video') {
                    $draftMedia['video'] = $path;
                } elseif ($role === 'variant_image' && $clientVariantId > 0) {
                    $draftMedia['variant_images'][$clientVariantId] = $path;
                }
            }
            $mediaStmt->close();
        }
    }

    if (count($imageFiles) === 0 && count($draftMedia['main_images']) === 0) {
        throw new Exception('At least one product image is required');
    }

    if (count($variants) > 0) {
        foreach ($variants as $variant) {
            $variantId = (int)$variant['id'];
            $hasUploadedVariantImage = isset($variantImageFiles[$variantId]);
            $hasDraftVariantImage = isset($draftMedia['variant_images'][$variantId]);
            if (!$hasUploadedVariantImage && !$hasDraftVariantImage) {
                throw new Exception('Each variant must have one image');
            }
        }
    }

    if ($categoryMode === 'new') {
        if ($newCategoryName === '') {
            throw new Exception('New category name is required');
        }

        $findCategoryStmt = $conn->prepare('SELECT category_id FROM categories WHERE LOWER(category_name) = LOWER(?) LIMIT 1');
        if (!$findCategoryStmt) {
            throw new Exception('Failed to prepare category lookup');
        }
        $findCategoryStmt->bind_param('s', $newCategoryName);
        $findCategoryStmt->execute();
        $findCategoryRes = $findCategoryStmt->get_result();
        $existingCategory = $findCategoryRes ? $findCategoryRes->fetch_assoc() : null;
        $findCategoryStmt->close();

        if ($existingCategory) {
            $categoryId = (int)$existingCategory['category_id'];
        } else {
            $insertCategoryStmt = $conn->prepare('INSERT INTO categories (category_name) VALUES (?)');
            if (!$insertCategoryStmt) {
                throw new Exception('Failed to prepare category insert');
            }
            $insertCategoryStmt->bind_param('s', $newCategoryName);
            if (!$insertCategoryStmt->execute()) {
                throw new Exception('Failed to create category');
            }
            $categoryId = (int)$conn->insert_id;
            $insertCategoryStmt->close();
        }
    } else {
        $categoryId = (int)$categoryIdRaw;
        if ($categoryId <= 0) {
            throw new Exception('Please select a category');
        }

        $checkCategoryStmt = $conn->prepare('SELECT 1 FROM categories WHERE category_id = ? LIMIT 1');
        if (!$checkCategoryStmt) {
            throw new Exception('Failed to validate category');
        }
        $checkCategoryStmt->bind_param('i', $categoryId);
        $checkCategoryStmt->execute();
        $categoryExists = $checkCategoryStmt->get_result()->num_rows > 0;
        $checkCategoryStmt->close();

        if (!$categoryExists) {
            throw new Exception('Selected category does not exist');
        }
    }

    $productsToCreate = [];
    
    // Always create the main product first (if variants exist, we still need the main product row)
    $productsToCreate[] = [
        'variant_id' => 0,
        'name' => $productName,
        'price' => $price,
        'stock' => $productStock,
        'isMainProduct' => true
    ];
    
    // Then add each variant as a separate product
    if (count($variants) > 0) {
        foreach ($variants as $variant) {
            $productsToCreate[] = [
                'variant_id' => (int)$variant['id'],
                'name' => $variant['name'],
                'price' => (float)$variant['price'],
                'stock' => (int)$variant['stock'],
                'isMainProduct' => false
            ];
        }
    }

    error_log('Products to create count: ' . count($productsToCreate));
    error_log('Products to create: ' . json_encode($productsToCreate));

    $insertProductStmt = $conn->prepare('INSERT INTO products (product_name, product_description, price, product_stock, category_id, average_rating, order_count) VALUES (?, ?, ?, ?, ?, 0.00, 0)');
    if (!$insertProductStmt) {
        throw new Exception('Failed to prepare product insert');
    }

    $insertVariantStmt = $conn->prepare('INSERT INTO products (product_name, product_description, price, product_stock, category_id, parent_product_id, average_rating, order_count) VALUES (?, ?, ?, ?, ?, ?, 0.00, 0)');
    if (!$insertVariantStmt) {
        throw new Exception('Failed to prepare variant insert');
    }

    $insertMediaStmt = $conn->prepare('INSERT INTO product_images (product_id, image_url, is_pinned) VALUES (?, ?, ?)');
    if (!$insertMediaStmt) {
        throw new Exception('Failed to prepare media insert');
    }

    $createdProductIds = [];
    $mainProductId = null;

    foreach ($productsToCreate as $productDef) {
        $variantId = (int)($productDef['variant_id'] ?? 0);
        $isMainProduct = (bool)($productDef['isMainProduct'] ?? false);
        $currentProductName = $productDef['name'];
        $currentPrice = (float)$productDef['price'];
        $currentProductStock = (int)($productDef['stock'] ?? 0);

        if ($isMainProduct) {
            // Main product: no parent_product_id
            $insertProductStmt->bind_param('ssdii', $currentProductName, $productDescription, $currentPrice, $currentProductStock, $categoryId);
            if (!$insertProductStmt->execute()) {
                throw new Exception('Failed to create product');
            }
            $productId = (int)$conn->insert_id;
            $mainProductId = $productId;
        } else {
            // Variant: include parent_product_id
            $insertVariantStmt->bind_param('ssdiii', $currentProductName, $productDescription, $currentPrice, $currentProductStock, $categoryId, $mainProductId);
            if (!$insertVariantStmt->execute()) {
                throw new Exception('Failed to create variant product');
            }
            $productId = (int)$conn->insert_id;
        }
        
        $createdProductIds[] = $productId;

        if ($isMainProduct) {
            // Main product: save all uploaded images
            if (!empty($imageFiles)) {
                foreach ($imageFiles as $index => $file) {
                    $mime = $finfo->file($file['tmp_name']);
                    $ext = $allowedImageMimes[$mime] ?? 'jpg';
                    $fileName = 'product_' . $productId . '_img_' . ($index + 1) . '_' . time() . '_' . bin2hex(random_bytes(3)) . '.' . $ext;
                    $targetAbsolute = $uploadDirAbsolute . DIRECTORY_SEPARATOR . $fileName;
                    $relativePath = 'product_media/' . $fileName;

                    if (!copy($file['tmp_name'], $targetAbsolute)) {
                        throw new Exception('Failed to save uploaded image');
                    }

                    $createdFiles[] = $targetAbsolute;
                    $isPinned = $index === $pinnedImageIndex ? 1 : 0;
                    $insertMediaStmt->bind_param('isi', $productId, $relativePath, $isPinned);
                    if (!$insertMediaStmt->execute()) {
                        throw new Exception('Failed to save image record');
                    }
                }
            } else {
                foreach ($draftMedia['main_images'] as $index => $draftImage) {
                    $sourceRelative = (string)($draftImage['path'] ?? '');
                    $sourceAbsolute = absolutePathFromRelative($sourceRelative);
                    if (!is_file($sourceAbsolute)) {
                        throw new Exception('Draft image file is missing');
                    }

                    $sourceExt = pathinfo($sourceAbsolute, PATHINFO_EXTENSION);
                    $ext = $sourceExt !== '' ? $sourceExt : 'jpg';
                    $fileName = 'product_' . $productId . '_img_' . ($index + 1) . '_' . time() . '_' . bin2hex(random_bytes(3)) . '.' . $ext;
                    $targetAbsolute = $uploadDirAbsolute . DIRECTORY_SEPARATOR . $fileName;
                    $relativePath = 'product_media/' . $fileName;

                    if (!copy($sourceAbsolute, $targetAbsolute)) {
                        throw new Exception('Failed to copy draft image');
                    }

                    $createdFiles[] = $targetAbsolute;
                    $isPinned = ((bool)($draftImage['is_pinned'] ?? false)) ? 1 : 0;
                    $insertMediaStmt->bind_param('isi', $productId, $relativePath, $isPinned);
                    if (!$insertMediaStmt->execute()) {
                        throw new Exception('Failed to save image record');
                    }
                }
            }
        } else if ($variantId > 0) {
            // Variant: save only its single variant image
            $variantImageFile = $variantImageFiles[$variantId] ?? null;
            if ($variantImageFile) {
                $variantMime = $finfo->file($variantImageFile['tmp_name']);
                $variantExt = $allowedImageMimes[$variantMime] ?? 'jpg';
                $variantFileName = 'product_' . $productId . '_variant_img_' . time() . '_' . bin2hex(random_bytes(3)) . '.' . $variantExt;
                $variantTargetAbsolute = $uploadDirAbsolute . DIRECTORY_SEPARATOR . $variantFileName;
                $variantRelativePath = 'product_media/' . $variantFileName;

                if (!copy($variantImageFile['tmp_name'], $variantTargetAbsolute)) {
                    throw new Exception('Failed to save variant image');
                }
            } else {
                $draftVariantPath = (string)($draftMedia['variant_images'][$variantId] ?? '');
                $sourceAbsolute = absolutePathFromRelative($draftVariantPath);
                if (!is_file($sourceAbsolute)) {
                    throw new Exception('Missing image for one of the variants');
                }

                $sourceExt = pathinfo($sourceAbsolute, PATHINFO_EXTENSION);
                $variantExt = $sourceExt !== '' ? $sourceExt : 'jpg';
                $variantFileName = 'product_' . $productId . '_variant_img_' . time() . '_' . bin2hex(random_bytes(3)) . '.' . $variantExt;
                $variantTargetAbsolute = $uploadDirAbsolute . DIRECTORY_SEPARATOR . $variantFileName;
                $variantRelativePath = 'product_media/' . $variantFileName;

                if (!copy($sourceAbsolute, $variantTargetAbsolute)) {
                    throw new Exception('Failed to copy draft variant image');
                }
            }

            $createdFiles[] = $variantTargetAbsolute;
            $isPinned = 1;
            $insertMediaStmt->bind_param('isi', $productId, $variantRelativePath, $isPinned);
            if (!$insertMediaStmt->execute()) {
                throw new Exception('Failed to save variant image record');
            }
        }

        if ($videoFile || !empty($draftMedia['video'])) {
            if ($videoFile) {
                $videoMime = $finfo->file($videoFile['tmp_name']);
                $videoExt = $allowedVideoMimes[$videoMime] ?? 'mp4';
                $videoName = 'product_' . $productId . '_video_' . time() . '_' . bin2hex(random_bytes(3)) . '.' . $videoExt;
                $videoTargetAbsolute = $uploadDirAbsolute . DIRECTORY_SEPARATOR . $videoName;
                $videoRelativePath = 'product_media/' . $videoName;

                if (!copy($videoFile['tmp_name'], $videoTargetAbsolute)) {
                    throw new Exception('Failed to save uploaded video');
                }
            } else {
                $sourceAbsolute = absolutePathFromRelative((string)$draftMedia['video']);
                if (!is_file($sourceAbsolute)) {
                    throw new Exception('Draft video file is missing');
                }

                $sourceExt = pathinfo($sourceAbsolute, PATHINFO_EXTENSION);
                $videoExt = $sourceExt !== '' ? $sourceExt : 'mp4';
                $videoName = 'product_' . $productId . '_video_' . time() . '_' . bin2hex(random_bytes(3)) . '.' . $videoExt;
                $videoTargetAbsolute = $uploadDirAbsolute . DIRECTORY_SEPARATOR . $videoName;
                $videoRelativePath = 'product_media/' . $videoName;

                if (!copy($sourceAbsolute, $videoTargetAbsolute)) {
                    throw new Exception('Failed to copy draft video');
                }
            }

            $createdFiles[] = $videoTargetAbsolute;
            $isPinned = 0;
            $insertMediaStmt->bind_param('isi', $productId, $videoRelativePath, $isPinned);
            if (!$insertMediaStmt->execute()) {
                throw new Exception('Failed to save video record');
            }
        }
    }

    $insertProductStmt->close();
    $insertVariantStmt->close();
    $insertMediaStmt->close();

    $conn->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Product created successfully',
        'product_id' => (int)$createdProductIds[0],
        'product_ids' => $createdProductIds,
        'created_count' => count($createdProductIds)
    ]);
} catch (Exception $e) {
    $conn->rollback();
    foreach ($createdFiles as $createdFile) {
        if (is_file($createdFile)) {
            @unlink($createdFile);
        }
    }
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
} finally {
    $conn->close();
}
