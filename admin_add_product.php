<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: LogIn.php');
    exit;
}
$role = $_SESSION['user_role'] ?? 'user';
if ($role !== 'admin') {
    header('Location: user_dashboard.php');
    exit;
}
$draftId = isset($_GET['draft_id']) ? (int)$_GET['draft_id'] : 0;
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Add Product - Admin</title>
  <link rel="stylesheet" href="main.css">
  <style>
    :root {
      --bg: #eef2f5;
      --panel: #ffffff;
      --line: #d8e0e7;
      --line-soft: #e9eef2;
      --text: #18202a;
      --muted: #5f6c7a;
      --brand: #2f5dd7;
      --brand-strong: #2248b0;
      --success: #0d8a3a;
      --error: #b00020;
    }

    * { margin: 0; padding: 0; box-sizing: border-box; }
    body {
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      color: var(--text);
      background:
        radial-gradient(circle at top right, #dfe8ff 0%, rgba(223,232,255,0) 42%),
        radial-gradient(circle at top left, #d9f3ff 0%, rgba(217,243,255,0) 38%),
        var(--bg);
    }

    .screen { width: calc(100% - 48px); max-width: none; margin: 0 auto; min-height: 100vh; padding-bottom: 168px; }
    .topbar {
      position: sticky;
      top: 0;
      z-index: 20;
      background: rgba(238, 242, 245, 0.9);
      backdrop-filter: blur(8px);
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 14px 14px 12px;
      border-bottom: 1px solid var(--line);
    }
    .topbar-title {
      position: absolute;
      left: 50%;
      transform: translateX(-50%);
      font-size: 28px;
      font-weight: 700;
      color: #111;
      letter-spacing: 0.2px;
    }
    .topbar-btn {
      border: 1px solid var(--line);
      background: #fff;
      width: 40px;
      height: 40px;
      border-radius: 10px;
      font-size: 22px;
      cursor: pointer;
      color: #333;
      line-height: 1;
      display: inline-flex;
      align-items: center;
      justify-content: center;
    }
    .topbar-btn:hover { border-color: #b6c5d5; }
    .topbar-actions { display: inline-flex; gap: 8px; align-items: center; }
    .topbar-link-btn {
      border: 1px solid #9cb0ca;
      background: #f2f6fc;
      color: #3153a9;
      height: 40px;
      border-radius: 10px;
      padding: 0 12px;
      font-size: 13px;
      font-weight: 700;
      cursor: pointer;
    }
    .topbar-link-btn:hover { background: #e8effc; }

    .hero {
      margin: 14px;
      background: linear-gradient(130deg, #1f3e8a 0%, #2f5dd7 54%, #4f78eb 100%);
      color: #fff;
      border-radius: 14px;
      padding: 18px;
      box-shadow: 0 12px 30px rgba(34, 72, 176, 0.3);
    }
    .hero h1 { font-size: 22px; margin-bottom: 6px; }
    .hero p { font-size: 14px; opacity: 0.94; }

    .card {
      margin: 14px;
      background: var(--panel);
      border: 1px solid var(--line);
      border-radius: 14px;
      overflow: hidden;
      box-shadow: 0 10px 24px rgba(17, 24, 39, 0.06);
    }

    .row {
      padding: 18px;
      border-bottom: 1px solid var(--line-soft);
    }
    .row:last-child { border-bottom: none; }

    .field-label {
      font-size: 16px;
      font-weight: 700;
      margin-bottom: 10px;
      color: var(--text);
    }
    .required-mark { color: #e54c63; }

    .hint { color: var(--muted); font-size: 13px; margin-top: 6px; }

    input[type="file"] {
      width: 100%;
      padding: 10px;
      border: 1px solid var(--line);
      border-radius: 10px;
      background: #f8fafc;
      color: #334155;
      font-size: 14px;
    }
    input[type="file"]::file-selector-button {
      border: 1px solid #c9d4e1;
      background: #fff;
      color: #1d355d;
      border-radius: 8px;
      padding: 8px 10px;
      margin-right: 10px;
      font-weight: 600;
      cursor: pointer;
    }

    .media-grid { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 10px; margin-top: 8px; }
    .media-tile {
      border: 1px dashed #c7d2de;
      border-radius: 10px;
      background: #f9fbfd;
      min-height: 94px;
      display: flex;
      align-items: center;
      justify-content: center;
      color: #667384;
      font-size: 12px;
      text-align: center;
      padding: 8px;
      overflow: hidden;
    }
    .media-tile.pinned {
      border-style: solid;
      border-color: #2f5dd7;
      box-shadow: 0 0 0 2px rgba(47, 93, 215, 0.2) inset;
    }
    .media-tile img { width: 100%; height: 100%; object-fit: cover; border-radius: 8px; }
    .pin-chip {
      position: absolute;
      top: 6px;
      left: 6px;
      background: rgba(47, 93, 215, 0.92);
      color: #fff;
      font-size: 11px;
      font-weight: 700;
      border-radius: 999px;
      padding: 3px 7px;
    }
    .pin-btn {
      position: absolute;
      right: 6px;
      bottom: 6px;
      border: 1px solid #c4cfde;
      background: rgba(255,255,255,0.95);
      color: #234794;
      border-radius: 8px;
      padding: 4px 7px;
      font-size: 11px;
      font-weight: 700;
      cursor: pointer;
    }
    .pin-btn:hover { border-color: #2f5dd7; }

    .input, .textarea, .select {
      width: 100%;
      border: 1px solid var(--line);
      border-radius: 10px;
      background: #fff;
      color: var(--text);
      padding: 12px;
      font-size: 15px;
      font-family: inherit;
    }
    .input:focus, .textarea:focus, .select:focus {
      outline: none;
      border-color: #7d9ef0;
      box-shadow: 0 0 0 3px rgba(47, 93, 215, 0.12);
    }
    .textarea { min-height: 110px; resize: vertical; }

    .dual-row {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 0;
    }

    .category-line {
      display: grid;
      grid-template-columns: 1fr auto;
      gap: 10px;
      align-items: center;
    }
    .new-cat-btn {
      border: 1px solid var(--brand);
      background: #fff;
      color: var(--brand);
      font-size: 13px;
      font-weight: 700;
      padding: 10px 12px;
      border-radius: 9px;
      cursor: pointer;
      white-space: nowrap;
    }
    .new-cat-btn:hover { background: #f3f7ff; }

    .variant-list {
      display: grid;
      gap: 10px;
      margin-top: 10px;
    }
    .variant-row {
      display: grid;
      grid-template-columns: 1fr 120px 120px 1fr auto;
      gap: 10px;
      align-items: center;
    }
    .variant-remove-btn {
      border: 1px solid #d7dee8;
      background: #fff;
      color: #5a6677;
      font-size: 13px;
      font-weight: 700;
      padding: 10px 12px;
      border-radius: 9px;
      cursor: pointer;
      white-space: nowrap;
    }
    .variant-remove-btn:hover { border-color: #b8c4d6; }
    .variant-add-btn { margin-top: 10px; }

    .hidden { display: none; }
    .error-text { color: var(--error); font-size: 13px; margin-top: 8px; }
    .ok-text { color: var(--success); font-size: 13px; margin-top: 8px; }

    .footer-actions {
      position: fixed;
      bottom: 50px;
      left: 50%;
      transform: translateX(-50%);
      width: calc(100% - 48px);
      max-width: none;
      background: rgba(238, 242, 245, 0.95);
      border-top: 1px solid var(--line);
      backdrop-filter: blur(8px);
      padding: 10px 12px 12px;
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 10px;
      z-index: 25;
    }
    .footer-btn {
      height: 48px;
      border-radius: 999px;
      font-size: 17px;
      font-weight: 700;
      cursor: pointer;
    }
    .btn-draft {
      border: 1px solid #9cb0ca;
      background: #f2f6fc;
      color: #3153a9;
    }
    .btn-draft:hover { background: #e8effc; }
    .btn-publish {
      border: none;
      background: linear-gradient(120deg, var(--brand) 0%, #496fe2 100%);
      color: #fff;
    }
    .btn-publish:hover { background: linear-gradient(120deg, var(--brand-strong) 0%, #3f63d2 100%); }
    .btn-publish[disabled] { opacity: 0.6; cursor: not-allowed; }

    .swal-overlay {
      position: fixed;
      inset: 0;
      background: rgba(15, 23, 42, 0.45);
      display: none;
      align-items: center;
      justify-content: center;
      z-index: 60;
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
    .swal-title { font-size: 20px; font-weight: 700; color: #152033; margin-bottom: 8px; }
    .swal-text { font-size: 14px; color: #5f6d7f; margin-bottom: 14px; line-height: 1.45; }
    .swal-actions {
      display: grid;
      grid-template-columns: 1fr;
      gap: 8px;
    }
    .swal-actions.two { grid-template-columns: 1fr 1fr; }
    .swal-btn {
      border: none;
      border-radius: 10px;
      background: #2f5dd7;
      color: #fff;
      font-size: 14px;
      font-weight: 700;
      width: 100%;
      height: 42px;
      cursor: pointer;
    }
    .swal-btn:hover { background: #2449b4; }
    .swal-btn.secondary {
      background: #f3f5f9;
      color: #3f4a5a;
      border: 1px solid #d9dee7;
    }
    .swal-btn.secondary:hover { background: #e9edf4; }

    @media (max-width: 700px) {
      .screen { width: calc(100% - 24px); }
      .footer-actions { width: calc(100% - 24px); }
      .topbar-title { font-size: 20px; }
      .hero h1 { font-size: 18px; }
      .hero p { font-size: 13px; }
      .media-grid { grid-template-columns: repeat(3, minmax(0, 1fr)); }
      .field-label { font-size: 16px; }
      .dual-row { grid-template-columns: 1fr; }
      .variant-row { grid-template-columns: 1fr; }
    }
  </style>
</head>
<body>
  <div class="screen">
    <div class="topbar">
      <button class="topbar-btn" type="button" onclick="window.location.href='admin_dashboard.php'">←</button>
      <div class="topbar-title">Add Product</div>
      <div class="topbar-actions">
        <button class="topbar-link-btn" type="button" onclick="window.location.href='admin_product_drafts.php'">Drafts</button>
      </div>
</div>

    <div class="hero">
      <h1>Create a Product Listing</h1>
      <p>Upload media, organize category, and publish a polished product entry in one flow.</p>
    </div>

    <form id="addProductForm" enctype="multipart/form-data" novalidate>
      <div class="card">
        <div class="row">
          <div class="field-label" id="imagesLabel">Product Images (0/8) <span class="required-mark">*</span></div>
          <input id="imagesInput" type="file" name="images[]" accept="image/*" multiple>
          <div class="hint">Optional, max 8 images. First image will be the cover image.</div>
          <div class="hint">Click Pin on a preview to choose the pinned image.</div>
          <div id="imagesError" class="error-text hidden"></div>
          <div id="imagePreviewGrid" class="media-grid">
            <div class="media-tile">No images selected</div>
          </div>
        </div>

        <div class="row">
          <div class="field-label">Product Video (0/1)</div>
          <input id="videoInput" type="file" name="video" accept="video/*">
          <div class="hint">Optional, max 1 video (MP4/WEBM/MOV).</div>
          <div id="videoName" class="hint"></div>
        </div>

        <div class="row">
          <div class="field-label">Product Name <span class="required-mark">*</span></div>
          <input id="productName" name="product_name" class="input" type="text" placeholder="Ex. Nikon Coolpix A300 Digital Camera" required>
        </div>

        <div class="row">
          <div class="field-label">Category <span class="required-mark">*</span></div>
          <div class="category-line" id="existingCategoryBox">
            <select id="categorySelect" name="category_id" class="select"></select>
            <button type="button" class="new-cat-btn" onclick="toggleCategoryMode(true)">+ New Category</button>
          </div>
          <div id="newCategoryBox" class="hidden">
            <input id="newCategoryName" class="input" type="text" placeholder="Enter new category name">
            <div style="margin-top:8px;">
              <button type="button" class="new-cat-btn" onclick="toggleCategoryMode(false)">Use Existing Category</button>
            </div>
          </div>
        </div>

        <div class="dual-row">
          <div class="row">
            <div class="field-label">Price (PHP) <span class="required-mark">*</span></div>
            <input id="price" name="price" class="input" type="number" min="0" step="0.01" required>
          </div>

          <div class="row">
            <div class="field-label">Stock Quantity <span class="required-mark">*</span></div>
            <input id="stock" name="product_stock" class="input" type="number" min="0" step="1" required>
          </div>
        </div>

        <div class="row">
          <div class="field-label">Product Variants (Optional)</div>
          <div class="hint">Add multiple variant names and prices to publish several products at once.</div>
          <div class="hint">Variant product name format: Base Product Name - Variant Name</div>
          <div class="hint">Each variant must have one image only.</div>
          <div class="hint">All variants will use the category selected in the main product section.</div>
          <div id="variantRows" class="variant-list"></div>
          <button type="button" class="new-cat-btn variant-add-btn" onclick="addVariantRow()">+ Add Variant</button>
        </div>

        <div class="row">
          <div class="field-label">Product Description</div>
          <textarea id="description" name="product_description" class="textarea" placeholder="Write product details..."></textarea>
        </div>
      </div>

      <div id="submitStatus" class="card hidden" style="padding:12px 16px;"></div>
    </form>
  </div>

  <div class="footer-actions">
    <button type="button" class="footer-btn btn-draft" onclick="saveDraft()">Save as Draft</button>
    <button id="publishBtn" type="button" class="footer-btn btn-publish" onclick="publishProduct()">Publish Product</button>
  </div>

  <div id="localSwal" class="swal-overlay" role="dialog" aria-modal="true" aria-live="polite">
    <div class="swal-card">
      <div id="localSwalIcon" class="swal-icon success">✓</div>
      <div id="localSwalTitle" class="swal-title">Success</div>
      <div id="localSwalText" class="swal-text"></div>
      <div id="localSwalActions" class="swal-actions">
        <button id="localSwalCancel" type="button" class="swal-btn secondary" style="display:none;">Cancel</button>
        <button id="localSwalConfirm" type="button" class="swal-btn">OK</button>
      </div>
    </div>
  </div>

  <script>
    let currentDraftId = <?php echo (int)$draftId; ?>;
    let useNewCategory = false;
    let pinnedImageIndex = 0;
    let selectedImageFiles = [];
    let existingDraftImagePaths = [];
    let existingDraftVideoPath = '';
    let existingVariantImageMap = {};
    let nextVariantId = 1;

    function byId(id) {
      return document.getElementById(id);
    }

    function openLocalSweetAlert(options = {}) {
      const overlay = byId('localSwal');
      const icon = byId('localSwalIcon');
      const titleEl = byId('localSwalTitle');
      const textEl = byId('localSwalText');
      const actions = byId('localSwalActions');
      const confirmBtn = byId('localSwalConfirm');
      const cancelBtn = byId('localSwalCancel');
      if (!overlay || !icon || !titleEl || !textEl || !actions || !confirmBtn || !cancelBtn) return Promise.resolve(true);

      const type = options.type || 'success';
      const isError = type === 'error';
      const hasCancel = !!options.showCancel;
      icon.className = `swal-icon ${isError ? 'error' : 'success'}`;
      icon.textContent = isError ? '!' : '✓';
      titleEl.textContent = options.title || 'Notice';
      textEl.textContent = options.text || '';

      confirmBtn.textContent = options.confirmText || 'OK';
      cancelBtn.textContent = options.cancelText || 'Cancel';
      cancelBtn.style.display = hasCancel ? 'block' : 'none';
      actions.className = hasCancel ? 'swal-actions two' : 'swal-actions';

      return new Promise((resolve) => {
        const cleanup = () => {
          overlay.classList.remove('show');
          confirmBtn.onclick = null;
          cancelBtn.onclick = null;
          overlay.onclick = null;
        };

        confirmBtn.onclick = () => {
          cleanup();
          if (typeof options.onConfirm === 'function') {
            options.onConfirm();
          }
          resolve(true);
        };

        cancelBtn.onclick = () => {
          cleanup();
          if (typeof options.onCancel === 'function') {
            options.onCancel();
          }
          resolve(false);
        };

        overlay.onclick = (event) => {
          if (event.target === overlay && hasCancel) {
            cleanup();
            resolve(false);
          }
        };

        overlay.classList.add('show');
      });
    }

    function showLocalSweetAlert(type, title, text) {
      return openLocalSweetAlert({
        type,
        title,
        text,
        confirmText: 'OK',
        showCancel: false
      });
    }

    function localConfirm(title, text, confirmText = 'Publish', cancelText = 'Cancel') {
      return openLocalSweetAlert({
        type: 'success',
        title,
        text,
        confirmText,
        cancelText,
        showCancel: true
      });
    }

    function closeLocalSweetAlert() {
      const overlay = byId('localSwal');
      if (overlay) overlay.classList.remove('show');
    }

    async function loadCategories() {
      const select = byId('categorySelect');
      select.innerHTML = '<option value="">Loading categories...</option>';

      try {
        const res = await fetch('api/get-categories.php', { cache: 'no-store' });
        const data = await res.json();

        if (!res.ok || !data.success || !Array.isArray(data.categories)) {
          throw new Error('Failed to load categories');
        }

        if (!data.categories.length) {
          select.innerHTML = '<option value="">No categories found</option>';
          return;
        }

        select.innerHTML = data.categories
          .map(cat => `<option value="${Number(cat.category_id)}">${String(cat.category_name || '').replace(/</g, '&lt;')}</option>`)
          .join('');
      } catch (err) {
        select.innerHTML = '<option value="">Unable to load categories</option>';
      }
    }

    function toggleCategoryMode(newMode) {
      useNewCategory = !!newMode;
      byId('existingCategoryBox').classList.toggle('hidden', useNewCategory);
      byId('newCategoryBox').classList.toggle('hidden', !useNewCategory);
    }

    function getFileKey(file) {
      return `${file.name}::${file.size}::${file.lastModified}`;
    }

    function handleImagesSelection() {
      const input = byId('imagesInput');
      const err = byId('imagesError');
      const incomingFiles = Array.from(input.files || []);

      if (incomingFiles.length > 0 && existingDraftImagePaths.length > 0) {
        existingDraftImagePaths = [];
      }

      const existingKeys = new Set(selectedImageFiles.map(getFileKey));
      incomingFiles.forEach((file) => {
        const key = getFileKey(file);
        if (!existingKeys.has(key) && selectedImageFiles.length < 8) {
          selectedImageFiles.push(file);
          existingKeys.add(key);
        }
      });

      if (selectedImageFiles.length >= 8 && incomingFiles.length > 0) {
        err.textContent = 'Maximum 8 images reached.';
        err.classList.remove('hidden');
      }

      // Reset so user can open picker again and add more files.
      input.value = '';
      renderImagePreview();
    }

    function getActiveImageItems() {
      if (selectedImageFiles.length > 0) {
        return selectedImageFiles.map((file) => ({ type: 'file', file }));
      }
      return existingDraftImagePaths.map((path) => ({ type: 'path', path }));
    }

    function renderImagePreview() {
      const grid = byId('imagePreviewGrid');
      const err = byId('imagesError');
      const items = getActiveImageItems();
      const label = byId('imagesLabel');

      label.textContent = `Product Images (${items.length}/8) `;
      const mark = document.createElement('span');
      mark.className = 'required-mark';
      mark.textContent = '*';
      label.appendChild(mark);

      if (items.length <= 8) {
        err.classList.add('hidden');
      }

      if (!items.length) {
        pinnedImageIndex = 0;
        grid.innerHTML = '<div class="media-tile">No images selected</div>';
        return;
      }

      if (pinnedImageIndex < 0 || pinnedImageIndex >= items.length) {
        pinnedImageIndex = 0;
      }

      grid.innerHTML = items.slice(0, 8).map(() => '<div class="media-tile">Loading...</div>').join('');
      const tiles = Array.from(grid.children);

      items.slice(0, 8).forEach((item, idx) => {
        const pinned = idx === pinnedImageIndex;
        tiles[idx].classList.toggle('pinned', pinned);
        tiles[idx].style.position = 'relative';

        const renderTile = (src) => {
          tiles[idx].innerHTML = `
            <img src="${src}" alt="preview">
            ${pinned ? '<span class="pin-chip">Pinned</span>' : ''}
            <button type="button" class="pin-btn" onclick="setPinnedImage(${idx})">${pinned ? 'Pinned' : 'Pin'}</button>
          `;
        };

        if (item.type === 'file') {
          const reader = new FileReader();
          reader.onload = (e) => renderTile(e.target.result);
          reader.readAsDataURL(item.file);
        } else {
          renderTile(item.path);
        }
      });
    }

    function setPinnedImage(index) {
      const files = selectedImageFiles;
      if (!files.length) return;
      pinnedImageIndex = Math.max(0, Math.min(index, files.length - 1));
      renderImagePreview();
    }

    function showVideoName() {
      const input = byId('videoInput');
      const view = byId('videoName');
      const file = input.files && input.files[0] ? input.files[0] : null;
      if (file) {
        view.textContent = `Selected: ${file.name}`;
        return;
      }
      view.textContent = existingDraftVideoPath ? 'Selected: existing draft video' : '';
    }

    function addVariantRow(name = '', price = '', stock = '', variantId = null, existingImagePath = '') {
      const rows = byId('variantRows');
      if (!rows) return;

      const row = document.createElement('div');
      row.className = 'variant-row';
      const resolvedVariantId = Number(variantId) > 0 ? Number(variantId) : nextVariantId++;
      if (resolvedVariantId >= nextVariantId) {
        nextVariantId = resolvedVariantId + 1;
      }
      row.dataset.variantId = String(resolvedVariantId);

      const nameInput = document.createElement('input');
      nameInput.type = 'text';
      nameInput.className = 'input variant-name';
      nameInput.placeholder = 'Variant Name (e.g. Red 64GB)';
      nameInput.value = name;

      const priceInput = document.createElement('input');
      priceInput.type = 'number';
      priceInput.min = '0';
      priceInput.step = '0.01';
      priceInput.className = 'input variant-price';
      priceInput.placeholder = 'Variant Price';
      priceInput.value = price;

      const stockInput = document.createElement('input');
      stockInput.type = 'number';
      stockInput.min = '0';
      stockInput.step = '1';
      stockInput.className = 'input variant-stock';
      stockInput.placeholder = 'Variant Stock';
      stockInput.value = stock;

      const imageInput = document.createElement('input');
      imageInput.type = 'file';
      imageInput.accept = 'image/*';
      imageInput.className = 'input variant-image';

      const existingImageHint = document.createElement('div');
      existingImageHint.className = 'help-text';
      existingImageHint.textContent = existingImagePath ? 'Existing variant image saved' : '';

      if (existingImagePath) {
        row.dataset.existingVariantImage = existingImagePath;
      }

      imageInput.addEventListener('change', () => {
        if (imageInput.files && imageInput.files[0]) {
          row.dataset.existingVariantImage = '';
          existingImageHint.textContent = '';
        }
      });

      const removeBtn = document.createElement('button');
      removeBtn.type = 'button';
      removeBtn.className = 'variant-remove-btn';
      removeBtn.textContent = 'Remove';
      removeBtn.addEventListener('click', () => row.remove());

      row.appendChild(nameInput);
      row.appendChild(priceInput);
      row.appendChild(stockInput);
      row.appendChild(imageInput);
      row.appendChild(existingImageHint);
      row.appendChild(removeBtn);
      rows.appendChild(row);
    }

    function getVariantsFromForm() {
      const rows = Array.from(document.querySelectorAll('#variantRows .variant-row'));
      const variants = [];

      rows.forEach((row) => {
        const name = row.querySelector('.variant-name')?.value?.trim() || '';
        const priceRaw = row.querySelector('.variant-price')?.value?.trim() || '';
        const stockRaw = row.querySelector('.variant-stock')?.value?.trim() || '';
        const imageInput = row.querySelector('.variant-image');
        const imageFile = imageInput && imageInput.files && imageInput.files[0] ? imageInput.files[0] : null;
        const existingImagePath = row.dataset.existingVariantImage || '';
        const variantId = Number(row.dataset.variantId || 0);

        if (!name && priceRaw === '' && stockRaw === '') {
          return;
        }

        variants.push({
          id: variantId,
          name,
          price: priceRaw,
          stock: stockRaw,
          imageFile,
          existingImagePath
        });
      });

      return variants;
    }

    function collectDraftPayload() {
      const variants = getVariantsFromForm().map((variant) => ({
        id: variant.id,
        name: variant.name,
        price: variant.price,
        stock: variant.stock
      }));

      return {
        draft_id: currentDraftId > 0 ? currentDraftId : 0,
        product_name: byId('productName').value,
        product_description: byId('description').value,
        price: byId('price').value,
        product_stock: byId('stock').value,
        pinned_image_index: pinnedImageIndex,
        image_count: getActiveImageItems().length,
        has_video: ((byId('videoInput').files && byId('videoInput').files[0]) || existingDraftVideoPath) ? 1 : 0,
        use_new_category: useNewCategory,
        category_id: byId('categorySelect').value,
        new_category_name: byId('newCategoryName').value,
        variants
      };
    }

    async function saveDraft() {
      const status = byId('submitStatus');
      try {
        const payload = collectDraftPayload();
        const body = new FormData();
        body.append('draft_id', String(payload.draft_id || 0));
        body.append('product_name', payload.product_name || '');
        body.append('product_description', payload.product_description || '');
        body.append('price', payload.price || '');
        body.append('product_stock', payload.product_stock || '');
        body.append('pinned_image_index', String(payload.pinned_image_index || 0));
        body.append('image_count', String(payload.image_count || 0));
        body.append('has_video', String(payload.has_video || 0));
        body.append('use_new_category', payload.use_new_category ? '1' : '0');
        body.append('category_id', payload.category_id || '');
        body.append('new_category_name', payload.new_category_name || '');
        body.append('variants', JSON.stringify(payload.variants || []));

        selectedImageFiles.slice(0, 8).forEach((file) => body.append('images[]', file));

        const videoFile = byId('videoInput').files && byId('videoInput').files[0] ? byId('videoInput').files[0] : null;
        if (videoFile) {
          body.append('video', videoFile);
          existingDraftVideoPath = '';
        }

        const variantFiles = getVariantsFromForm();
        variantFiles.forEach((variant) => {
          if (variant.imageFile && Number(variant.id || 0) > 0) {
            body.append(`variant_image_${Number(variant.id)}`, variant.imageFile);
          }
        });

        const res = await fetch('api/save-product-draft.php', {
          method: 'POST',
          body
        });
        const data = await res.json();
        if (!res.ok || !data.success) {
          throw new Error(data.error || 'Failed to save draft');
        }

        if (Number(data.draft_id || 0) > 0) {
          currentDraftId = Number(data.draft_id);
          const url = new URL(window.location.href);
          url.searchParams.set('draft_id', String(currentDraftId));
          window.history.replaceState({}, '', url.toString());
        }

        status.className = 'card ok-text';
        status.textContent = 'Draft saved to server successfully.';
        await showLocalSweetAlert('success', 'Draft Saved', 'Draft saved. You can manage it on the Drafts page.');
      } catch (err) {
        status.className = 'card error-text';
        status.textContent = err.message || 'Failed to save draft.';
        await showLocalSweetAlert('error', 'Draft Save Failed', err.message || 'Unable to save draft.');
      }
    }

    function applyDraftToForm(draft) {
      if (!draft || typeof draft !== 'object') return;

      byId('productName').value = draft.product_name || '';
      byId('description').value = draft.product_description || '';
      byId('price').value = draft.price || '';
      byId('stock').value = draft.product_stock || '';
      pinnedImageIndex = Number(draft.pinned_image_index || 0);
      toggleCategoryMode(Number(draft.use_new_category || 0) === 1);
      byId('categorySelect').value = draft.category_id || '';
      byId('newCategoryName').value = draft.new_category_name || '';

      existingDraftImagePaths = Array.isArray(draft?.media?.images)
        ? draft.media.images.map((item) => String(item?.path || '')).filter(Boolean)
        : [];
      selectedImageFiles = [];

      if (Array.isArray(draft?.media?.images)) {
        const pinnedIdx = draft.media.images.findIndex((item) => !!item?.is_pinned);
        if (pinnedIdx >= 0) {
          pinnedImageIndex = pinnedIdx;
        }
      }

      existingDraftVideoPath = String(draft?.media?.video || '');
      existingVariantImageMap = draft?.media?.variant_images && typeof draft.media.variant_images === 'object'
        ? draft.media.variant_images
        : {};

      const rows = byId('variantRows');
      if (rows) {
        rows.innerHTML = '';
        if (Array.isArray(draft.variants)) {
          draft.variants.forEach((variant) => {
            const variantId = Number(variant?.id || 0);
            const existingPath = variantId > 0 ? String(existingVariantImageMap[String(variantId)] || '') : '';
            addVariantRow(variant?.name || '', variant?.price || '', variant?.stock || '', variantId || null, existingPath);
          });
        }
      }

      renderImagePreview();
      showVideoName();
    }

    async function loadDraftFromServer(draftId) {
      const res = await fetch(`api/get-product-drafts.php?draft_id=${encodeURIComponent(draftId)}`, { cache: 'no-store' });
      const data = await res.json();
      if (!res.ok || !data.success || !data.draft) {
        throw new Error(data.error || 'Unable to load draft');
      }
      applyDraftToForm(data.draft);
    }

    function loadLocalDraft() {
      const raw = localStorage.getItem('admin_add_product_draft');
      if (!raw) return;
      try {
        const draft = JSON.parse(raw);
        applyDraftToForm(draft);
      } catch (e) {
      }
    }

    function validateForm() {
      const images = getActiveImageItems();
      if (images.length === 0) {
        return 'At least 1 product image is required.';
      }
      if (images.length > 8) {
        return 'You can upload up to 8 images only.';
      }

      const name = byId('productName').value.trim();
      if (!name) return 'Product name is required.';

      const description = byId('description').value.trim();
      if (!description) return 'Product description is required.';

      // Price is always required
      const price = byId('price').value;
      if (price === '' || Number(price) < 0) return 'Price must be a non-negative number.';

      // Stock is always required
      const stock = byId('stock').value;
      if (stock === '' || Number(stock) < 0) return 'Stock must be a non-negative number.';

      const variants = getVariantsFromForm();
      
      // Validate each variant
      for (const variant of variants) {
        const variantName = String(variant.name || '').trim();
        const variantPrice = String(variant.price || '').trim();
        const variantStock = String(variant.stock || '').trim();
        const variantImage = variant.imageFile || null;
        const existingVariantImage = variant.existingImagePath || '';
        const variantId = Number(variant.id || 0);

        if (!variantId) {
          return 'Variant identifier is invalid. Please remove and add the variant again.';
        }

        if (!variantName) {
          return 'Each variant must have a name.';
        }

        if (variantPrice === '' || Number.isNaN(Number(variantPrice)) || Number(variantPrice) < 0) {
          return 'Each variant price must be a non-negative number.';
        }

        if (variantStock === '' || Number.isNaN(Number(variantStock)) || Number(variantStock) < 0) {
          return 'Each variant stock must be a non-negative number.';
        }

        if (!variantImage && !existingVariantImage) {
          return 'Each variant must have one image.';
        }
      }

      if (useNewCategory) {
        if (!byId('newCategoryName').value.trim()) {
          return 'Please enter a new category name.';
        }
      } else if (!byId('categorySelect').value) {
        return 'Please choose an existing category.';
      }

      return '';
    }

    async function publishProduct() {
      const error = validateForm();
      if (error) {
        await showLocalSweetAlert('error', 'Validation Error', error);
        return;
      }

      const categoryLabel = useNewCategory
        ? (byId('newCategoryName').value.trim() || 'New Category')
        : (byId('categorySelect').selectedOptions[0]?.textContent || 'Existing Category');
      const infoSummary = [
        `Name: ${byId('productName').value.trim()}`,
        `Description: ${byId('description').value.trim() ? 'Provided' : 'Missing'}`,
        `Category: ${categoryLabel}`,
        `Price: PHP ${byId('price').value}`,
        `Stock: ${byId('stock').value}`,
        `Images: ${getActiveImageItems().length}/8`,
        `Video: ${((byId('videoInput').files && byId('videoInput').files[0]) || existingDraftVideoPath) ? 'Provided' : 'None'}`,
        `Variants: ${getVariantsFromForm().length}`
      ].join('\n');

      const proceed = await localConfirm(
        'Confirm Product Details',
        infoSummary,
        'Publish Now',
        'Review Again'
      );
      if (!proceed) {
        return;
      }

      const btn = byId('publishBtn');
      btn.disabled = true;
      btn.textContent = 'Publishing...';

      try {
        const formData = new FormData();
        const variantsWithFiles = getVariantsFromForm();
        const variants = variantsWithFiles.map((variant) => ({
          id: Number(variant.id || 0),
          name: String(variant.name || '').trim(),
          price: Number(variant.price),
          stock: Number(variant.stock || 0)
        }));

        console.log('=== PUBLISHING PRODUCT ===');
        console.log('Variants extracted from form:', variantsWithFiles.length, variantsWithFiles);
        console.log('Variants to send:', variants);
        console.log('Variant count:', variants.length);

        formData.append('product_name', byId('productName').value.trim());
        formData.append('product_description', byId('description').value.trim());
        formData.append('price', byId('price').value);
        formData.append('product_stock', String(Math.floor(Number(byId('stock').value || 0))));
        formData.append('variants', JSON.stringify(variants));
        if (currentDraftId > 0) {
          formData.append('draft_id', String(currentDraftId));
        }

        if (useNewCategory) {
          formData.append('category_mode', 'new');
          formData.append('new_category_name', byId('newCategoryName').value.trim());
        } else {
          formData.append('category_mode', 'existing');
          formData.append('category_id', byId('categorySelect').value);
        }

        const imageFiles = selectedImageFiles;
        imageFiles.slice(0, 8).forEach((file) => formData.append('images[]', file));
        formData.append('pinned_image_index', String(pinnedImageIndex));
        variantsWithFiles.forEach((variant) => {
          if (variant.imageFile && Number(variant.id || 0) > 0) {
            formData.append(`variant_image_${Number(variant.id)}`, variant.imageFile);
          }
        });

        const videoFile = byId('videoInput').files && byId('videoInput').files[0] ? byId('videoInput').files[0] : null;
        if (videoFile) {
          formData.append('video', videoFile);
        }

        const response = await fetch('api/add-product-admin.php', {
          method: 'POST',
          body: formData
        });

        const data = await response.json();
        if (!response.ok || !data.success) {
          throw new Error(data.error || 'Failed to create product');
        }

        if (currentDraftId > 0) {
          const removeBody = new URLSearchParams();
          removeBody.append('draft_id', String(currentDraftId));
          await fetch('api/delete-product-draft.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: removeBody.toString()
          });
        }
        localStorage.removeItem('admin_add_product_draft');
        if (Number(data.created_count || 1) > 1) {
          showLocalSweetAlert('success', 'Products Published', `${data.created_count} products were created successfully.`);
        } else {
          showLocalSweetAlert('success', 'Product Published', `Product ID ${data.product_id} has been created successfully.`);
        }

        byId('addProductForm').reset();
        selectedImageFiles = [];
        existingDraftImagePaths = [];
        existingDraftVideoPath = '';
        existingVariantImageMap = {};
        pinnedImageIndex = 0;
        toggleCategoryMode(false);
        const variantRows = byId('variantRows');
        if (variantRows) {
          variantRows.innerHTML = '';
        }
        renderImagePreview();
        showVideoName();
        await loadCategories();
      } catch (err) {
        showLocalSweetAlert('error', 'Publishing Error', err.message || 'Failed to create product');
      } finally {
        btn.disabled = false;
        btn.textContent = 'Publish Product';
      }
    }

    byId('imagesInput').addEventListener('change', handleImagesSelection);
    byId('videoInput').addEventListener('change', showVideoName);

    loadCategories().then(async () => {
      if (currentDraftId > 0) {
        try {
          await loadDraftFromServer(currentDraftId);
        } catch (err) {
          await showLocalSweetAlert('error', 'Draft Load Failed', err.message || 'Unable to load selected draft.');
        }
      } else {
        loadLocalDraft();
      }
    });

  </script>
</body>
</html>
