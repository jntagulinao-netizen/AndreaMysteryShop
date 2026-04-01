<?php
header('Content-Type: application/json; charset=utf-8');
session_start();

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$role = $_SESSION['user_role'] ?? 'user';
if ($role !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Forbidden']);
    exit;
}

require __DIR__ . '/../dbConnection.php';

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

$productId = intval($_POST['product_id'] ?? 0);
$name = trim((string)($_POST['product_name'] ?? ''));
$priceRaw = trim((string)($_POST['price'] ?? ''));
$stockRaw = trim((string)($_POST['product_stock'] ?? ''));
$description = trim((string)($_POST['product_description'] ?? ''));
$categoryId = intval($_POST['category_id'] ?? 0);

if ($productId <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid product ID']);
    exit;
}

if ($name === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Product name is required']);
    exit;
}

if (!is_numeric($priceRaw) || (float)$priceRaw < 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Price must be a valid non-negative number']);
    exit;
}

if (!is_numeric($stockRaw) || intval($stockRaw) < 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Quantity must be a valid non-negative integer']);
    exit;
}

if ($categoryId <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Category is required']);
    exit;
}

$price = round((float)$priceRaw, 2);
$stock = intval($stockRaw);
$variants = [];
$newVariants = [];
$newMainImage = null;
$additionalMainImages = [];
$variantAdditionalImageFiles = [];
$variantImageUpdates = [];
$removedExistingImages = [];
$pinnedMainImageSource = trim((string)($_POST['pinned_main_image_source'] ?? ''));
$pinnedMainImageUrl = trim((string)($_POST['pinned_main_image_url'] ?? ''));
$pinnedNewImageIndexRaw = trim((string)($_POST['pinned_new_image_index'] ?? ''));
$pinnedNewImageIndex = is_numeric($pinnedNewImageIndexRaw) ? intval($pinnedNewImageIndexRaw) : -1;
$switchMainVariantRaw = trim((string)($_POST['switch_main_variant'] ?? ''));
$switchMainVariantId = 0;
$switchMainVariantTempId = 0;
$removeExistingVideo = trim((string)($_POST['remove_existing_video'] ?? '0')) === '1';
$existingVideoUrl = trim((string)($_POST['existing_video_url'] ?? ''));
$newProductVideo = null;

if ($switchMainVariantRaw !== '') {
    if (preg_match('/^id:(\d+)$/', $switchMainVariantRaw, $idMatch)) {
        $switchMainVariantId = intval($idMatch[1]);
    } elseif (preg_match('/^temp:(\d+)$/', $switchMainVariantRaw, $tempMatch)) {
        $switchMainVariantTempId = intval($tempMatch[1]);
    } else {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid main variant selection']);
        exit;
    }
}

$removedExistingImagesRaw = trim((string)($_POST['removed_existing_images'] ?? ''));
if ($removedExistingImagesRaw !== '') {
    $decodedRemovedImages = json_decode($removedExistingImagesRaw, true);
    if (!is_array($decodedRemovedImages)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid removed images payload']);
        exit;
    }
    foreach ($decodedRemovedImages as $url) {
        $urlString = trim((string)$url);
        if ($urlString !== '') {
            $removedExistingImages[] = $urlString;
        }
    }
}

$variantsRaw = trim($_POST['variants'] ?? '');
if ($variantsRaw !== '') {
    $decodedVariants = json_decode($variantsRaw, true);
    if (is_array($decodedVariants)) {
        foreach ($decodedVariants as $item) {
            if (!is_array($item)) continue;
            
            $variantId = intval($item['id'] ?? 0);
            $variantPrice = trim((string)($item['price'] ?? ''));
            $variantStock = trim((string)($item['stock'] ?? ''));
            
            if ($variantId <= 0) continue;
            
            if (!is_numeric($variantPrice) || (float)$variantPrice < 0) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Variant price must be a non-negative number']);
                exit;
            }
            
            if (!is_numeric($variantStock) || intval($variantStock) < 0) {
                http_response_code(400);
                echo json_encode(['success' => false, 'error' => 'Variant stock must be a non-negative integer']);
                exit;
            }
            
            $variants[] = [
                'id' => $variantId,
                'name' => trim((string)($item['name'] ?? '')),
                'price' => round((float)$variantPrice, 2),
                'stock' => intval($variantStock)
            ];
        }
    }
}

$newVariantsRaw = trim((string)($_POST['new_variants'] ?? ''));
if ($newVariantsRaw !== '') {
    $decodedNewVariants = json_decode($newVariantsRaw, true);
    if (!is_array($decodedNewVariants)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid new variants payload']);
        exit;
    }

    foreach ($decodedNewVariants as $item) {
        if (!is_array($item)) {
            continue;
        }

        $tempId = intval($item['temp_id'] ?? 0);
        $variantName = trim((string)($item['name'] ?? ''));
        $variantPrice = trim((string)($item['price'] ?? ''));
        $variantStock = trim((string)($item['stock'] ?? ''));

        if ($tempId <= 0) {
            continue;
        }
        if ($variantName === '') {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Each new variant must have a name']);
            exit;
        }
        if (!is_numeric($variantPrice) || (float)$variantPrice < 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'New variant price must be a non-negative number']);
            exit;
        }
        if (!is_numeric($variantStock) || intval($variantStock) < 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'New variant stock must be a non-negative integer']);
            exit;
        }

        $newVariants[] = [
            'temp_id' => $tempId,
            'name' => $variantName,
            'price' => round((float)$variantPrice, 2),
            'stock' => intval($variantStock)
        ];
    }
}

$variantImageUpdatesRaw = trim((string)($_POST['variant_image_updates'] ?? ''));
if ($variantImageUpdatesRaw !== '') {
    $decodedVariantImageUpdates = json_decode($variantImageUpdatesRaw, true);
    if (!is_array($decodedVariantImageUpdates)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid variant image updates payload']);
        exit;
    }

    foreach ($decodedVariantImageUpdates as $item) {
        if (!is_array($item)) {
            continue;
        }

        $variantId = intval($item['id'] ?? 0);
        $variantTempId = intval($item['temp_id'] ?? 0);
        if ($variantId <= 0 && $variantTempId <= 0) {
            continue;
        }

        $removedExisting = [];
        if (isset($item['removed_existing_images']) && is_array($item['removed_existing_images'])) {
            foreach ($item['removed_existing_images'] as $url) {
                $urlString = trim((string)$url);
                if ($urlString !== '') {
                    $removedExisting[] = $urlString;
                }
            }
        }

        $pinnedSource = trim((string)($item['pinned_source'] ?? ''));
        if ($pinnedSource !== 'existing' && $pinnedSource !== 'new') {
            $pinnedSource = '';
        }

        $pinnedExistingUrl = trim((string)($item['pinned_existing_url'] ?? ''));
        $pinnedNewImageIndexRaw = trim((string)($item['pinned_new_image_index'] ?? ''));
        $pinnedNewImageIndex = is_numeric($pinnedNewImageIndexRaw) ? intval($pinnedNewImageIndexRaw) : -1;

        $updateKey = $variantId > 0 ? $variantId : ('temp:' . $variantTempId);
        $variantImageUpdates[$updateKey] = [
            'temp_id' => $variantTempId,
            'removed_existing_images' => $removedExisting,
            'pinned_source' => $pinnedSource,
            'pinned_existing_url' => $pinnedExistingUrl,
            'pinned_new_image_index' => $pinnedNewImageIndex
        ];
    }
}

if (isset($_FILES['new_main_image']) && is_array($_FILES['new_main_image'])) {
    $imgError = (int)($_FILES['new_main_image']['error'] ?? UPLOAD_ERR_NO_FILE);
    if ($imgError !== UPLOAD_ERR_NO_FILE) {
        if ($imgError !== UPLOAD_ERR_OK) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Invalid image upload']);
            exit;
        }

        $tmpName = $_FILES['new_main_image']['tmp_name'] ?? '';
        $mime = $finfo->file($tmpName);
        if (!isset($allowedImageMimes[$mime])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Only JPG, PNG, WEBP, and GIF images are allowed']);
            exit;
        }

        $newMainImage = [
            'tmp_name' => $tmpName,
            'mime' => $mime,
            'ext' => $allowedImageMimes[$mime]
        ];
    }
}

if (isset($_FILES['additional_main_images'])) {
    $additionalFiles = $_FILES['additional_main_images'];
    $names = $additionalFiles['name'] ?? [];
    if (!is_array($names)) {
        $names = [$names];
        $tmpNames = [($additionalFiles['tmp_name'] ?? '')];
        $errors = [($additionalFiles['error'] ?? UPLOAD_ERR_NO_FILE)];
    } else {
        $tmpNames = is_array($additionalFiles['tmp_name'] ?? null) ? $additionalFiles['tmp_name'] : [];
        $errors = is_array($additionalFiles['error'] ?? null) ? $additionalFiles['error'] : [];
    }

    for ($i = 0; $i < count($names); $i++) {
        $imgError = (int)($errors[$i] ?? UPLOAD_ERR_NO_FILE);
        if ($imgError === UPLOAD_ERR_NO_FILE) {
            continue;
        }
        if ($imgError !== UPLOAD_ERR_OK) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Invalid main image upload']);
            exit;
        }

        $tmpName = $tmpNames[$i] ?? '';
        $mime = $finfo->file($tmpName);
        if (!isset($allowedImageMimes[$mime])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Only JPG, PNG, WEBP, and GIF images are allowed']);
            exit;
        }

        $additionalMainImages[] = [
            'tmp_name' => $tmpName,
            'mime' => $mime,
            'ext' => $allowedImageMimes[$mime]
        ];
    }
}

if ($newMainImage) {
    $additionalMainImages[] = $newMainImage;
    if ($pinnedMainImageSource === '') {
        $pinnedMainImageSource = 'new';
        $pinnedNewImageIndex = count($additionalMainImages) - 1;
    }
}

if (isset($_FILES['product_video']) && is_array($_FILES['product_video'])) {
    $videoError = (int)($_FILES['product_video']['error'] ?? UPLOAD_ERR_NO_FILE);
    if ($videoError !== UPLOAD_ERR_NO_FILE) {
        if ($videoError !== UPLOAD_ERR_OK) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Invalid video upload']);
            exit;
        }

        $tmpName = $_FILES['product_video']['tmp_name'] ?? '';
        $mime = $finfo->file($tmpName);
        if (!isset($allowedVideoMimes[$mime])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Only MP4, WEBM, and MOV videos are allowed']);
            exit;
        }

        $newProductVideo = [
            'tmp_name' => $tmpName,
            'mime' => $mime,
            'ext' => $allowedVideoMimes[$mime]
        ];
    }
}

foreach ($_FILES as $fileKey => $fileData) {
    if (strpos($fileKey, 'variant_image_') !== 0 || !is_array($fileData)) {
        continue;
    }

    $variantId = intval(substr($fileKey, strlen('variant_image_')));
    if ($variantId <= 0) {
        continue;
    }

    $imgError = (int)($fileData['error'] ?? UPLOAD_ERR_NO_FILE);
    if ($imgError === UPLOAD_ERR_NO_FILE) {
        continue;
    }

    if ($imgError !== UPLOAD_ERR_OK) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid variant image upload']);
        exit;
    }

    $tmpName = $fileData['tmp_name'] ?? '';
    $mime = $finfo->file($tmpName);
    if (!isset($allowedImageMimes[$mime])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Variant images must be JPG, PNG, WEBP, or GIF']);
        exit;
    }

    if (!isset($variantAdditionalImageFiles[$variantId])) {
        $variantAdditionalImageFiles[$variantId] = [];
    }

    $variantAdditionalImageFiles[$variantId][] = [
        'tmp_name' => $tmpName,
        'mime' => $mime,
        'ext' => $allowedImageMimes[$mime]
    ];
}

foreach ($_FILES as $fileKey => $fileData) {
    if (strpos($fileKey, 'variant_additional_images_new_') !== 0 || !is_array($fileData)) {
        continue;
    }

    $tempId = intval(substr($fileKey, strlen('variant_additional_images_new_')));
    if ($tempId <= 0) {
        continue;
    }

    $names = $fileData['name'] ?? [];
    if (!is_array($names)) {
        $names = [$names];
        $tmpNames = [($fileData['tmp_name'] ?? '')];
        $errors = [($fileData['error'] ?? UPLOAD_ERR_NO_FILE)];
    } else {
        $tmpNames = is_array($fileData['tmp_name'] ?? null) ? $fileData['tmp_name'] : [];
        $errors = is_array($fileData['error'] ?? null) ? $fileData['error'] : [];
    }

    for ($i = 0; $i < count($names); $i++) {
        $imgError = (int)($errors[$i] ?? UPLOAD_ERR_NO_FILE);
        if ($imgError === UPLOAD_ERR_NO_FILE) {
            continue;
        }
        if ($imgError !== UPLOAD_ERR_OK) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Invalid new variant image upload']);
            exit;
        }

        $tmpName = $tmpNames[$i] ?? '';
        $mime = $finfo->file($tmpName);
        if (!isset($allowedImageMimes[$mime])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'New variant images must be JPG, PNG, WEBP, or GIF']);
            exit;
        }

        if (!isset($variantAdditionalImageFiles['new:' . $tempId])) {
            $variantAdditionalImageFiles['new:' . $tempId] = [];
        }

        $variantAdditionalImageFiles['new:' . $tempId][] = [
            'tmp_name' => $tmpName,
            'mime' => $mime,
            'ext' => $allowedImageMimes[$mime]
        ];
    }
}

foreach ($_FILES as $fileKey => $fileData) {
    if (strpos($fileKey, 'variant_additional_images_') !== 0 || !is_array($fileData)) {
        continue;
    }

    $variantId = intval(substr($fileKey, strlen('variant_additional_images_')));
    if ($variantId <= 0) {
        continue;
    }

    $names = $fileData['name'] ?? [];
    if (!is_array($names)) {
        $names = [$names];
        $tmpNames = [($fileData['tmp_name'] ?? '')];
        $errors = [($fileData['error'] ?? UPLOAD_ERR_NO_FILE)];
    } else {
        $tmpNames = is_array($fileData['tmp_name'] ?? null) ? $fileData['tmp_name'] : [];
        $errors = is_array($fileData['error'] ?? null) ? $fileData['error'] : [];
    }

    for ($i = 0; $i < count($names); $i++) {
        $imgError = (int)($errors[$i] ?? UPLOAD_ERR_NO_FILE);
        if ($imgError === UPLOAD_ERR_NO_FILE) {
            continue;
        }
        if ($imgError !== UPLOAD_ERR_OK) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Invalid variant image upload']);
            exit;
        }

        $tmpName = $tmpNames[$i] ?? '';
        $mime = $finfo->file($tmpName);
        if (!isset($allowedImageMimes[$mime])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Variant images must be JPG, PNG, WEBP, or GIF']);
            exit;
        }

        if (!isset($variantAdditionalImageFiles[$variantId])) {
            $variantAdditionalImageFiles[$variantId] = [];
        }

        $variantAdditionalImageFiles[$variantId][] = [
            'tmp_name' => $tmpName,
            'mime' => $mime,
            'ext' => $allowedImageMimes[$mime]
        ];
    }
}

$checkCategoryStmt = $conn->prepare('SELECT 1 FROM categories WHERE category_id = ? LIMIT 1');
if (!$checkCategoryStmt) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Failed to validate category']);
    exit;
}
$checkCategoryStmt->bind_param('i', $categoryId);
$checkCategoryStmt->execute();
$categoryResult = $checkCategoryStmt->get_result();
$categoryExists = $categoryResult && $categoryResult->num_rows > 0;
$checkCategoryStmt->close();

if (!$categoryExists) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Selected category does not exist']);
    exit;
}

$conn->begin_transaction();

$uploadDirAbsolute = realpath(__DIR__ . '/..') . DIRECTORY_SEPARATOR . 'product_media';
$createdFiles = [];
$deletedFilesAfterCommit = [];
$resolvedMainProductId = $productId;

try {
    if (count($variants) > 0) {
        foreach ($variants as $variant) {
            $variantId = intval($variant['id']);
            $variantName = trim((string)($variant['name'] ?? ''));
            $variantPrice = $variant['price'];
            $variantStock = intval($variant['stock']);
            if ($variantName === '') {
                throw new Exception('Variant name is required');
            }
            
            $variantStmt = $conn->prepare('UPDATE products SET product_name = ?, price = ?, product_stock = ? WHERE product_id = ?');
            if (!$variantStmt) {
                throw new Exception('Failed to prepare variant update query');
            }
            
            $variantStmt->bind_param('sdii', $variantName, $variantPrice, $variantStock, $variantId);
            if (!$variantStmt->execute()) {
                throw new Exception('Failed to update variant product');
            }
            $variantStmt->close();
        }
    }

    $newVariantIdMap = [];
    if (count($newVariants) > 0) {
        $insertNewVariantStmt = $conn->prepare('INSERT INTO products (product_name, product_description, price, product_stock, category_id, parent_product_id, average_rating, order_count) VALUES (?, ?, ?, ?, ?, ?, 0.00, 0)');
        if (!$insertNewVariantStmt) {
            throw new Exception('Failed to prepare new variant insert query');
        }

        foreach ($newVariants as $newVariant) {
            $tempId = intval($newVariant['temp_id']);
            $newVariantName = trim((string)$newVariant['name']);
            $newVariantPrice = (float)$newVariant['price'];
            $newVariantStock = intval($newVariant['stock']);

            if ($newVariantName === '') {
                throw new Exception('New variant name is required');
            }

            $newVariantDescription = $description;
            $insertNewVariantStmt->bind_param('ssdiii', $newVariantName, $newVariantDescription, $newVariantPrice, $newVariantStock, $categoryId, $productId);
            if (!$insertNewVariantStmt->execute()) {
                throw new Exception('Failed to create new variant');
            }

            $newVariantProductId = (int)$insertNewVariantStmt->insert_id;
            $newVariantIdMap[$tempId] = $newVariantProductId;
        }

        $insertNewVariantStmt->close();
    }

    if (count($newVariantIdMap) > 0) {
        foreach ($variantImageUpdates as $key => $updateCfg) {
            if (!is_array($updateCfg)) {
                continue;
            }
            $tempId = intval($updateCfg['temp_id'] ?? 0);
            if ($tempId <= 0 || !isset($newVariantIdMap[$tempId])) {
                continue;
            }

            $newVariantProductId = $newVariantIdMap[$tempId];
            $variantImageUpdates[$newVariantProductId] = [
                'removed_existing_images' => [],
                'pinned_source' => trim((string)($updateCfg['pinned_source'] ?? '')),
                'pinned_existing_url' => '',
                'pinned_new_image_index' => intval($updateCfg['pinned_new_image_index'] ?? -1)
            ];
            unset($variantImageUpdates[$key]);
        }

        foreach ($newVariantIdMap as $tempId => $newVariantProductId) {
            $tempFileKey = 'new:' . $tempId;
            $newVariantFiles = $variantAdditionalImageFiles[$tempFileKey] ?? [];
            if (count($newVariantFiles) < 1) {
                throw new Exception('Each new variant must have at least one image');
            }
            $variantAdditionalImageFiles[$newVariantProductId] = $newVariantFiles;
            unset($variantAdditionalImageFiles[$tempFileKey]);

            if (!isset($variantImageUpdates[$newVariantProductId])) {
                $variantImageUpdates[$newVariantProductId] = [
                    'removed_existing_images' => [],
                    'pinned_source' => '',
                    'pinned_existing_url' => '',
                    'pinned_new_image_index' => -1
                ];
            }
        }
    }

    if ($switchMainVariantId > 0 || $switchMainVariantTempId > 0) {
        $familyMainProductId = $productId;
        $selectedProductStmt = $conn->prepare('SELECT product_id, parent_product_id FROM products WHERE product_id = ? LIMIT 1');
        if (!$selectedProductStmt) {
            throw new Exception('Failed to validate selected product for main switch');
        }
        $selectedProductStmt->bind_param('i', $productId);
        if (!$selectedProductStmt->execute()) {
            throw new Exception('Failed to read selected product for main switch');
        }
        $selectedProductRes = $selectedProductStmt->get_result();
        $selectedProductRow = $selectedProductRes ? $selectedProductRes->fetch_assoc() : null;
        $selectedProductStmt->close();
        if (!$selectedProductRow) {
            throw new Exception('Selected product not found for main switch');
        }
        if (intval($selectedProductRow['parent_product_id'] ?? 0) > 0) {
            $familyMainProductId = intval($selectedProductRow['parent_product_id']);
        }

        $targetMainProductId = 0;
        if ($switchMainVariantId > 0) {
            $targetMainProductId = $switchMainVariantId;
        } elseif ($switchMainVariantTempId > 0 && isset($newVariantIdMap[$switchMainVariantTempId])) {
            $targetMainProductId = intval($newVariantIdMap[$switchMainVariantTempId]);
        }

        if ($targetMainProductId <= 0) {
            throw new Exception('Selected main variant is invalid or missing');
        }

        if ($targetMainProductId !== $familyMainProductId) {
            $targetVariantStmt = $conn->prepare('SELECT product_id, parent_product_id FROM products WHERE product_id = ? LIMIT 1');
            if (!$targetVariantStmt) {
                throw new Exception('Failed to validate target variant for main switch');
            }
            $targetVariantStmt->bind_param('i', $targetMainProductId);
            if (!$targetVariantStmt->execute()) {
                throw new Exception('Failed to read target variant for main switch');
            }
            $targetVariantRes = $targetVariantStmt->get_result();
            $targetVariantRow = $targetVariantRes ? $targetVariantRes->fetch_assoc() : null;
            $targetVariantStmt->close();
            if (!$targetVariantRow) {
                throw new Exception('Target variant not found for main switch');
            }

            if (intval($targetVariantRow['parent_product_id'] ?? 0) !== $familyMainProductId) {
                throw new Exception('You can only switch main product with variants in the same family');
            }

            $moveSiblingVariantsStmt = $conn->prepare('UPDATE products SET parent_product_id = ? WHERE parent_product_id = ? AND product_id <> ?');
            if (!$moveSiblingVariantsStmt) {
                throw new Exception('Failed to prepare variant family relink');
            }
            $moveSiblingVariantsStmt->bind_param('iii', $targetMainProductId, $familyMainProductId, $targetMainProductId);
            if (!$moveSiblingVariantsStmt->execute()) {
                throw new Exception('Failed to relink variant family');
            }
            $moveSiblingVariantsStmt->close();

            $moveOldMainStmt = $conn->prepare('UPDATE products SET parent_product_id = ? WHERE product_id = ?');
            if (!$moveOldMainStmt) {
                throw new Exception('Failed to prepare old main relink');
            }
            $moveOldMainStmt->bind_param('ii', $targetMainProductId, $familyMainProductId);
            if (!$moveOldMainStmt->execute()) {
                throw new Exception('Failed to relink old main product');
            }
            $moveOldMainStmt->close();

            $setNewMainStmt = $conn->prepare('UPDATE products SET parent_product_id = NULL WHERE product_id = ?');
            if (!$setNewMainStmt) {
                throw new Exception('Failed to prepare new main update');
            }
            $setNewMainStmt->bind_param('i', $targetMainProductId);
            if (!$setNewMainStmt->execute()) {
                throw new Exception('Failed to set new main product');
            }
            $setNewMainStmt->close();

            $resolvedMainProductId = $targetMainProductId;
        } else {
            $resolvedMainProductId = $familyMainProductId;
        }
    }

    $variantImageUpdateIds = array_values(array_unique(array_merge(
        array_map('intval', array_keys($variantImageUpdates)),
        array_map('intval', array_keys($variantAdditionalImageFiles))
    )));

    if (count($variantImageUpdateIds) > 0) {
        if (!$uploadDirAbsolute || (!is_dir($uploadDirAbsolute) && !mkdir($uploadDirAbsolute, 0775, true))) {
            throw new Exception('Failed to prepare upload directory');
        }

        foreach ($variantImageUpdateIds as $variantId) {
            if ($variantId <= 0) {
                continue;
            }

            $updateCfg = $variantImageUpdates[$variantId] ?? [
                'removed_existing_images' => [],
                'pinned_source' => '',
                'pinned_existing_url' => '',
                'pinned_new_image_index' => -1
            ];
            $newVariantImages = $variantAdditionalImageFiles[$variantId] ?? [];

            $existingVariantImages = [];
            $existingVariantImagesStmt = $conn->prepare('SELECT image_id, image_url, is_pinned FROM product_images WHERE product_id = ? ORDER BY is_pinned DESC, image_id ASC');
            if (!$existingVariantImagesStmt) {
                throw new Exception('Failed to load existing variant images');
            }
            $existingVariantImagesStmt->bind_param('i', $variantId);
            if (!$existingVariantImagesStmt->execute()) {
                throw new Exception('Failed to read existing variant images');
            }
            $existingVariantImagesRes = $existingVariantImagesStmt->get_result();
            while ($row = $existingVariantImagesRes->fetch_assoc()) {
                $existingVariantImages[] = [
                    'image_id' => (int)$row['image_id'],
                    'image_url' => (string)$row['image_url'],
                    'is_pinned' => (int)$row['is_pinned']
                ];
            }
            $existingVariantImagesStmt->close();

            $removedLookup = [];
            foreach (($updateCfg['removed_existing_images'] ?? []) as $url) {
                $urlString = trim((string)$url);
                if ($urlString !== '') {
                    $removedLookup[$urlString] = true;
                }
            }

            $variantImagesToDelete = [];
            $variantImagesToKeep = [];
            foreach ($existingVariantImages as $imageRow) {
                if (isset($removedLookup[$imageRow['image_url']])) {
                    $variantImagesToDelete[] = $imageRow;
                } else {
                    $variantImagesToKeep[] = $imageRow;
                }
            }

            $finalVariantImageCount = count($variantImagesToKeep) + count($newVariantImages);
            if ($finalVariantImageCount < 1) {
                throw new Exception('Each variant must have at least one image');
            }
            if ($finalVariantImageCount > 8) {
                throw new Exception('Each variant can keep up to 8 images only');
            }

            foreach ($variantImagesToDelete as $imageRow) {
                $deleteVariantImageStmt = $conn->prepare('DELETE FROM product_images WHERE image_id = ? AND product_id = ?');
                if (!$deleteVariantImageStmt) {
                    throw new Exception('Failed to prepare variant image delete query');
                }
                $imageId = (int)$imageRow['image_id'];
                $deleteVariantImageStmt->bind_param('ii', $imageId, $variantId);
                if (!$deleteVariantImageStmt->execute()) {
                    throw new Exception('Failed to delete selected variant image');
                }
                $deleteVariantImageStmt->close();

                $candidatePath = absolutePathFromRelative((string)$imageRow['image_url']);
                if (is_file($candidatePath)) {
                    $deletedFilesAfterCommit[] = $candidatePath;
                }
            }

            $insertedVariantImageIds = [];
            foreach ($newVariantImages as $variantImage) {
                $variantFileName = 'product_' . $variantId . '_variant_img_' . time() . '_' . bin2hex(random_bytes(3)) . '.' . $variantImage['ext'];
                $variantTargetAbsolute = $uploadDirAbsolute . DIRECTORY_SEPARATOR . $variantFileName;
                $variantRelativePath = 'product_media/' . $variantFileName;

                if (!copy($variantImage['tmp_name'], $variantTargetAbsolute)) {
                    throw new Exception('Failed to save variant image');
                }
                $createdFiles[] = $variantTargetAbsolute;

                $insertVariantImageStmt = $conn->prepare('INSERT INTO product_images (product_id, image_url, is_pinned) VALUES (?, ?, 0)');
                if (!$insertVariantImageStmt) {
                    throw new Exception('Failed to prepare variant image insert');
                }
                $insertVariantImageStmt->bind_param('is', $variantId, $variantRelativePath);
                if (!$insertVariantImageStmt->execute()) {
                    throw new Exception('Failed to save variant image record');
                }

                $insertedVariantImageIds[] = (int)$insertVariantImageStmt->insert_id;
                $variantImagesToKeep[] = [
                    'image_id' => (int)$insertVariantImageStmt->insert_id,
                    'image_url' => $variantRelativePath,
                    'is_pinned' => 0
                ];
                $insertVariantImageStmt->close();
            }

            $targetVariantPinnedImageId = 0;
            if (($updateCfg['pinned_source'] ?? '') === 'existing' && ($updateCfg['pinned_existing_url'] ?? '') !== '') {
                foreach ($variantImagesToKeep as $imageRow) {
                    if ((string)$imageRow['image_url'] === (string)$updateCfg['pinned_existing_url']) {
                        $targetVariantPinnedImageId = (int)$imageRow['image_id'];
                        break;
                    }
                }
            } elseif (($updateCfg['pinned_source'] ?? '') === 'new' && intval($updateCfg['pinned_new_image_index'] ?? -1) >= 0) {
                $pinnedNewIndex = intval($updateCfg['pinned_new_image_index']);
                if (isset($insertedVariantImageIds[$pinnedNewIndex])) {
                    $targetVariantPinnedImageId = (int)$insertedVariantImageIds[$pinnedNewIndex];
                }
            }

            if ($targetVariantPinnedImageId === 0) {
                foreach ($variantImagesToKeep as $imageRow) {
                    if ((int)$imageRow['is_pinned'] === 1) {
                        $targetVariantPinnedImageId = (int)$imageRow['image_id'];
                        break;
                    }
                }
            }
            if ($targetVariantPinnedImageId === 0 && count($variantImagesToKeep) > 0) {
                $targetVariantPinnedImageId = (int)$variantImagesToKeep[0]['image_id'];
            }

            if ($targetVariantPinnedImageId > 0) {
                $pinVariantStmt = $conn->prepare('UPDATE product_images SET is_pinned = CASE WHEN image_id = ? THEN 1 ELSE 0 END WHERE product_id = ?');
                if (!$pinVariantStmt) {
                    throw new Exception('Failed to prepare variant image pin update');
                }
                $pinVariantStmt->bind_param('ii', $targetVariantPinnedImageId, $variantId);
                if (!$pinVariantStmt->execute()) {
                    throw new Exception('Failed to update pinned variant image');
                }
                $pinVariantStmt->close();
            }
        }
    }

    $updateStmt = $conn->prepare('UPDATE products SET product_name = ?, price = ?, product_stock = ?, product_description = ?, category_id = ? WHERE product_id = ?');
    if (!$updateStmt) {
        throw new Exception('Failed to prepare update query');
    }

    $updateStmt->bind_param('sdisii', $name, $price, $stock, $description, $categoryId, $productId);
    if (!$updateStmt->execute()) {
        throw new Exception('Failed to update product');
    }

    $existingMainImages = [];
    $existingImagesStmt = $conn->prepare('SELECT image_id, image_url, is_pinned FROM product_images WHERE product_id = ? ORDER BY is_pinned DESC, image_id ASC');
    if (!$existingImagesStmt) {
        throw new Exception('Failed to load existing product images');
    }
    $existingImagesStmt->bind_param('i', $productId);
    if (!$existingImagesStmt->execute()) {
        throw new Exception('Failed to read existing product images');
    }
    $existingImagesRes = $existingImagesStmt->get_result();
    while ($row = $existingImagesRes->fetch_assoc()) {
        $existingMainImages[] = [
            'image_id' => (int)$row['image_id'],
            'image_url' => (string)$row['image_url'],
            'is_pinned' => (int)$row['is_pinned']
        ];
    }
    $existingImagesStmt->close();

    $removedLookup = [];
    foreach ($removedExistingImages as $url) {
        $removedLookup[$url] = true;
    }

    $imagesToDelete = [];
    $imagesToKeep = [];
    foreach ($existingMainImages as $imageRow) {
        if (isset($removedLookup[$imageRow['image_url']])) {
            $imagesToDelete[] = $imageRow;
        } else {
            $imagesToKeep[] = $imageRow;
        }
    }

    $finalMainImageCount = count($imagesToKeep) + count($additionalMainImages);
    if ($finalMainImageCount < 1) {
        throw new Exception('Main product must have at least one image');
    }
    if ($finalMainImageCount > 8) {
        throw new Exception('You can keep up to 8 main images only');
    }

    if (count($imagesToDelete) > 0) {
        foreach ($imagesToDelete as $imageRow) {
            $deleteImageStmt = $conn->prepare('DELETE FROM product_images WHERE image_id = ? AND product_id = ?');
            if (!$deleteImageStmt) {
                throw new Exception('Failed to prepare image delete query');
            }
            $imageId = (int)$imageRow['image_id'];
            $deleteImageStmt->bind_param('ii', $imageId, $productId);
            if (!$deleteImageStmt->execute()) {
                throw new Exception('Failed to delete selected image');
            }
            $deleteImageStmt->close();

            $candidatePath = absolutePathFromRelative((string)$imageRow['image_url']);
            if (is_file($candidatePath)) {
                $deletedFilesAfterCommit[] = $candidatePath;
            }
        }
    }

    $insertedMainImageIds = [];
    if (count($additionalMainImages) > 0) {
        if (!$uploadDirAbsolute || (!is_dir($uploadDirAbsolute) && !mkdir($uploadDirAbsolute, 0775, true))) {
            throw new Exception('Failed to prepare upload directory');
        }

        foreach ($additionalMainImages as $mainImage) {
            $newFileName = 'product_' . $productId . '_img_main_' . time() . '_' . bin2hex(random_bytes(3)) . '.' . $mainImage['ext'];
            $targetAbsolute = $uploadDirAbsolute . DIRECTORY_SEPARATOR . $newFileName;
            $relativePath = 'product_media/' . $newFileName;

            if (!copy($mainImage['tmp_name'], $targetAbsolute)) {
                throw new Exception('Failed to save uploaded main image');
            }
            $createdFiles[] = $targetAbsolute;

            $insertImageStmt = $conn->prepare('INSERT INTO product_images (product_id, image_url, is_pinned) VALUES (?, ?, 0)');
            if (!$insertImageStmt) {
                throw new Exception('Failed to prepare image insert');
            }
            $insertImageStmt->bind_param('is', $productId, $relativePath);
            if (!$insertImageStmt->execute()) {
                throw new Exception('Failed to save image record');
            }

            $insertedMainImageIds[] = (int)$insertImageStmt->insert_id;
            $imagesToKeep[] = [
                'image_id' => (int)$insertImageStmt->insert_id,
                'image_url' => $relativePath,
                'is_pinned' => 0
            ];
            $insertImageStmt->close();
        }
    }

    $targetPinnedImageId = 0;
    if ($pinnedMainImageSource === 'existing' && $pinnedMainImageUrl !== '') {
        foreach ($imagesToKeep as $imageRow) {
            if ((string)$imageRow['image_url'] === $pinnedMainImageUrl) {
                $targetPinnedImageId = (int)$imageRow['image_id'];
                break;
            }
        }
    } elseif ($pinnedMainImageSource === 'new' && $pinnedNewImageIndex >= 0) {
        if (isset($insertedMainImageIds[$pinnedNewImageIndex])) {
            $targetPinnedImageId = (int)$insertedMainImageIds[$pinnedNewImageIndex];
        }
    }

    if ($targetPinnedImageId === 0) {
        foreach ($imagesToKeep as $imageRow) {
            if ((int)$imageRow['is_pinned'] === 1) {
                $targetPinnedImageId = (int)$imageRow['image_id'];
                break;
            }
        }
    }
    if ($targetPinnedImageId === 0 && count($imagesToKeep) > 0) {
        $targetPinnedImageId = (int)$imagesToKeep[0]['image_id'];
    }

    if ($targetPinnedImageId > 0) {
        $pinStmt = $conn->prepare('UPDATE product_images SET is_pinned = CASE WHEN image_id = ? THEN 1 ELSE 0 END WHERE product_id = ?');
        if (!$pinStmt) {
            throw new Exception('Failed to prepare image pin update');
        }
        $pinStmt->bind_param('ii', $targetPinnedImageId, $productId);
        if (!$pinStmt->execute()) {
            throw new Exception('Failed to update pinned image');
        }
        $pinStmt->close();
    }

    $existingVideoRows = [];
    $existingVideosStmt = $conn->prepare('SELECT image_id, image_url FROM product_images WHERE product_id = ? AND LOWER(image_url) REGEXP "\\.(mp4|webm|mov)$" ORDER BY image_id ASC');
    if (!$existingVideosStmt) {
        throw new Exception('Failed to load existing product video');
    }
    $existingVideosStmt->bind_param('i', $productId);
    if (!$existingVideosStmt->execute()) {
        throw new Exception('Failed to read existing product video');
    }
    $existingVideosRes = $existingVideosStmt->get_result();
    while ($videoRow = $existingVideosRes->fetch_assoc()) {
        $existingVideoRows[] = [
            'image_id' => (int)$videoRow['image_id'],
            'image_url' => (string)$videoRow['image_url']
        ];
    }
    $existingVideosStmt->close();

    if ($removeExistingVideo || $newProductVideo || $existingVideoUrl !== '') {
        foreach ($existingVideoRows as $videoRow) {
            if (!$removeExistingVideo && !$newProductVideo && $existingVideoUrl !== '' && (string)$videoRow['image_url'] !== $existingVideoUrl) {
                continue;
            }
            $deleteVideoStmt = $conn->prepare('DELETE FROM product_images WHERE image_id = ? AND product_id = ?');
            if (!$deleteVideoStmt) {
                throw new Exception('Failed to prepare video delete query');
            }
            $videoImageId = (int)$videoRow['image_id'];
            $deleteVideoStmt->bind_param('ii', $videoImageId, $productId);
            if (!$deleteVideoStmt->execute()) {
                throw new Exception('Failed to delete existing product video');
            }
            $deleteVideoStmt->close();

            $candidateVideoPath = absolutePathFromRelative((string)$videoRow['image_url']);
            if (is_file($candidateVideoPath)) {
                $deletedFilesAfterCommit[] = $candidateVideoPath;
            }
        }
    }

    if ($newProductVideo) {
        if (!$uploadDirAbsolute || (!is_dir($uploadDirAbsolute) && !mkdir($uploadDirAbsolute, 0775, true))) {
            throw new Exception('Failed to prepare upload directory for video');
        }

        $videoFileName = 'product_' . $productId . '_video_' . time() . '_' . bin2hex(random_bytes(3)) . '.' . $newProductVideo['ext'];
        $videoTargetAbsolute = $uploadDirAbsolute . DIRECTORY_SEPARATOR . $videoFileName;
        $videoRelativePath = 'product_media/' . $videoFileName;

        if (!copy($newProductVideo['tmp_name'], $videoTargetAbsolute)) {
            throw new Exception('Failed to save uploaded video');
        }
        $createdFiles[] = $videoTargetAbsolute;

        $insertVideoStmt = $conn->prepare('INSERT INTO product_images (product_id, image_url, is_pinned) VALUES (?, ?, 0)');
        if (!$insertVideoStmt) {
            throw new Exception('Failed to prepare video insert query');
        }
        $insertVideoStmt->bind_param('is', $productId, $videoRelativePath);
        if (!$insertVideoStmt->execute()) {
            throw new Exception('Failed to save video record');
        }
        $insertVideoStmt->close();
    }

    $affected = $updateStmt->affected_rows;
    $updateStmt->close();

    $finalImages = [];
    $finalImagesStmt = $conn->prepare('SELECT image_url FROM product_images WHERE product_id = ? ORDER BY is_pinned DESC, image_id ASC');
    if ($finalImagesStmt) {
        $finalImagesStmt->bind_param('i', $productId);
        $finalImagesStmt->execute();
        $finalImagesRes = $finalImagesStmt->get_result();
        while ($imageRow = $finalImagesRes->fetch_assoc()) {
            $url = trim((string)($imageRow['image_url'] ?? ''));
            if ($url !== '') {
                $finalImages[] = $url;
            }
        }
        $finalImagesStmt->close();
    }

    $finalVideoUrl = '';
    $finalVideoStmt = $conn->prepare('SELECT image_url FROM product_images WHERE product_id = ? AND LOWER(image_url) REGEXP "\\.(mp4|webm|mov)$" ORDER BY image_id DESC LIMIT 1');
    if ($finalVideoStmt) {
        $finalVideoStmt->bind_param('i', $productId);
        $finalVideoStmt->execute();
        $finalVideoRes = $finalVideoStmt->get_result();
        $finalVideoRow = $finalVideoRes ? $finalVideoRes->fetch_assoc() : null;
        if ($finalVideoRow) {
            $finalVideoUrl = trim((string)($finalVideoRow['image_url'] ?? ''));
        }
        $finalVideoStmt->close();
    }

    $conn->commit();

    foreach ($deletedFilesAfterCommit as $deletedPath) {
        if (is_file($deletedPath)) {
            @unlink($deletedPath);
        }
    }

    if ($affected === 0) {
        $existsStmt = $conn->prepare('SELECT 1 FROM products WHERE product_id = ? LIMIT 1');
        if ($existsStmt) {
            $existsStmt->bind_param('i', $productId);
            $existsStmt->execute();
            $existsRes = $existsStmt->get_result();
            $exists = $existsRes && $existsRes->num_rows > 0;
            $existsStmt->close();
            if (!$exists) {
                http_response_code(404);
                echo json_encode(['success' => false, 'error' => 'Product not found']);
                exit;
            }
        }
    }

    echo json_encode([
        'success' => true,
        'message' => 'Product updated successfully',
        'product_id' => $productId,
        'main_product_id' => $resolvedMainProductId,
        'main_images' => $finalImages,
        'video_url' => $finalVideoUrl
    ]);
} catch (Exception $e) {
    $conn->rollback();
    foreach ($createdFiles as $createdFile) {
        if (is_file($createdFile)) {
            @unlink($createdFile);
        }
    }
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
} finally {
    $conn->close();
}
?>