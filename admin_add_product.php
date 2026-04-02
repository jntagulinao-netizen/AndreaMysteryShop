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

    .video-preview {
      width: 100%;
      margin-top: 10px;
      max-height: 240px;
      border: 1px solid var(--line);
      border-radius: 10px;
      background: #000;
    }

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

    .main-images-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(118px, 1fr));
      gap: 10px;
      margin-top: 10px;
    }
    .main-image-card {
      border: 1px solid #e2e2e2;
      border-radius: 10px;
      padding: 8px;
      background: #fff;
      position: relative;
    }
    .main-image-card.removed {
      opacity: 0.48;
      border-style: dashed;
    }
    .main-image-card img {
      width: 100%;
      height: 92px;
      border-radius: 8px;
      object-fit: cover;
      border: 1px solid #ededed;
      background: #f6f6f6;
      display: block;
      margin-bottom: 8px;
    }
    .main-image-actions {
      display: grid;
      gap: 6px;
    }
    .main-image-pin {
      display: flex;
      align-items: center;
      gap: 6px;
      font-size: 11px;
      color: #444;
      font-weight: 600;
    }
    .main-image-remove-btn {
      border: 1px solid #efc5ca;
      background: #fff4f5;
      color: #bb2532;
      border-radius: 8px;
      font-size: 11px;
      font-weight: 700;
      padding: 6px;
      cursor: pointer;
    }
    .main-image-badge {
      position: absolute;
      top: 12px;
      left: 12px;
      background: #2d68d8;
      color: #fff;
      font-size: 10px;
      font-weight: 700;
      padding: 3px 7px;
      border-radius: 999px;
    }

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
      align-items: flex-start;
    }
    .variant-images-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(110px, 1fr));
      gap: 8px;
    }
    .variant-image-card {
      border: 1px solid #e2e2e2;
      border-radius: 8px;
      padding: 6px;
      background: #fff;
      position: relative;
    }
    .variant-image-card.removed {
      opacity: 0.5;
      border-style: dashed;
    }
    .variant-image-card img {
      width: 100%;
      height: 74px;
      object-fit: cover;
      border-radius: 6px;
      border: 1px solid #ededed;
      background: #f6f6f6;
      display: block;
      margin-bottom: 6px;
    }
    .variant-image-actions {
      display: grid;
      gap: 5px;
    }
    .variant-image-pin {
      display: flex;
      align-items: center;
      gap: 5px;
      font-size: 10px;
      color: #444;
      font-weight: 600;
    }
    .variant-image-remove-btn {
      border: 1px solid #efc5ca;
      background: #fff4f5;
      color: #bb2532;
      border-radius: 6px;
      font-size: 10px;
      font-weight: 700;
      padding: 5px;
      cursor: pointer;
    }
    .variant-image-badge {
      position: absolute;
      top: 9px;
      left: 9px;
      background: #2d68d8;
      color: #fff;
      font-size: 9px;
      font-weight: 700;
      padding: 2px 6px;
      border-radius: 999px;
    }
    .variant-image-count {
      font-size: 11px;
      color: #666;
      font-weight: 600;
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
      .main-images-grid { grid-template-columns: repeat(3, minmax(0, 1fr)); }
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
          <div class="hint">Click Pin image on a preview to choose the pinned image.</div>
          <div id="imagesError" class="error-text hidden"></div>
          <div id="mainImagesGrid" class="main-images-grid">
            <div style="text-align:center;color:#999;font-size:13px;padding:20px;grid-column:1/-1;">No images selected</div>
          </div>
        </div>

        <div class="row">
          <div class="field-label">Product Video (0/1)</div>
          <input id="videoInput" type="file" name="video" accept="video/*">
          <div class="hint">Optional, max 1 video (MP4/WEBM/MOV).</div>
          <div id="videoName" class="hint"></div>
          <video id="videoPreview" class="video-preview hidden" controls playsinline></video>
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
          <div class="hint">Each variant can have 1 to 8 images. Pin one image as primary.</div>
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
    let pinnedImageKey = '';
    let selectedImageFiles = [];
    let existingDraftImagePaths = [];
    let existingDraftImageStates = {};
    let existingDraftVideoPath = '';
    let existingVariantImageMap = {};
    let variantTempIdCounter = 1;
    let variantsList = [];

    function byId(id) {
      return document.getElementById(id);
    }

    function escapeHtml(value) {
      if (!value) return '';
      const div = document.createElement('div');
      div.textContent = String(value);
      return div.innerHTML;
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
      const isWarning = type === 'warning';
      const hasCancel = !!options.showCancel;
      icon.className = `swal-icon ${isError ? 'error' : 'success'}`;
      icon.textContent = isError ? '!' : isWarning ? '⚠' : '✓';
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
      if (!input) return;
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
      return existingDraftImagePaths.map((path) => ({
        type: 'path',
        path,
        deleted: existingDraftImageStates[path] || false
      }));
    }

    function renderImagePreview() {
      const grid = byId('mainImagesGrid');
      const err = byId('imagesError');
      const items = getActiveImageItems();
      const label = byId('imagesLabel');

      const activeCount = items.filter(img => !img.deleted).length;
      label.innerHTML = `Product Images (${activeCount}/8) <span class="required-mark">*</span>`;

      if (activeCount <= 8) {
        err.classList.add('hidden');
      }

      applyPinnedMainImageFallback();

      const cards = [];

      // Render existing images
      existingDraftImagePaths.forEach((path, idx) => {
        const key = `e:${idx}`;
        const checked = pinnedImageKey === key ? 'checked' : '';
        const deleted = existingDraftImageStates[path] || false;
        const removedClass = deleted ? 'removed' : '';
        const buttonLabel = deleted ? 'Undo Remove' : 'Remove';
        cards.push(`
          <div class="main-image-card ${removedClass}">
            ${checked && !deleted ? '<span class="main-image-badge">Pinned</span>' : ''}
            <img src="${escapeHtml(path)}" alt="Main image ${idx + 1}">
            <div class="main-image-actions">
              <label class="main-image-pin">
                <input type="radio" name="mainPinnedImage" value="${key}" ${checked} ${deleted ? 'disabled' : ''}>
                <span>Pin image</span>
              </label>
              <button type="button" class="main-image-remove-btn" data-existing-index="${idx}">${buttonLabel}</button>
            </div>
          </div>
        `);
      });

      // Render new files
      selectedImageFiles.forEach((file, idx) => {
        const key = `n:${idx}`;
        const checked = pinnedImageKey === key ? 'checked' : '';
        cards.push(`
          <div class="main-image-card">
            ${checked ? '<span class="main-image-badge">Pinned</span>' : ''}
            <img src="" alt="New main image ${idx + 1}" data-file-index="${idx}">
            <div class="main-image-actions">
              <label class="main-image-pin">
                <input type="radio" name="mainPinnedImage" value="${key}" ${checked}>
                <span>Pin image</span>
              </label>
              <button type="button" class="main-image-remove-btn" data-new-index="${idx}">Remove</button>
            </div>
          </div>
        `);
      });

      if (!cards.length) {
        grid.innerHTML = '<div style="text-align:center;color:#999;font-size:13px;padding:20px;grid-column:1/-1;">No images selected</div>';
      } else {
        grid.innerHTML = cards.join('');
        
        // Load preview URLs for new files
        selectedImageFiles.forEach((file, idx) => {
          const reader = new FileReader();
          reader.onload = (e) => {
            const img = grid.querySelector(`img[data-file-index="${idx}"]`);
            if (img) img.src = e.target.result;
          };
          reader.readAsDataURL(file);
        });

        // Radio change handler
        grid.querySelectorAll('input[name="mainPinnedImage"]').forEach((radio) => {
          radio.addEventListener('change', () => {
            if (radio.checked) {
              pinnedImageKey = radio.value;
              renderImagePreview();
            }
          });
        });

        // Existing image remove buttons
        grid.querySelectorAll('[data-existing-index]').forEach((btn) => {
          btn.addEventListener('click', () => {
            const idx = Number(btn.getAttribute('data-existing-index'));
            if (Number.isInteger(idx) && idx < existingDraftImagePaths.length) {
              const path = existingDraftImagePaths[idx];
              existingDraftImageStates[path] = !existingDraftImageStates[path];
              applyPinnedMainImageFallback();
              renderImagePreview();
            }
          });
        });

        // New file remove buttons
        grid.querySelectorAll('[data-new-index]').forEach((btn) => {
          btn.addEventListener('click', () => {
            const idx = Number(btn.getAttribute('data-new-index'));
            if (Number.isInteger(idx) && selectedImageFiles[idx]) {
              selectedImageFiles.splice(idx, 1);
              applyPinnedMainImageFallback();
              renderImagePreview();
            }
          });
        });
      }
    }

    function applyPinnedMainImageFallback() {
      const allImages = [
        ...existingDraftImagePaths.map((path, idx) => ({ type: 'e', idx, deleted: existingDraftImageStates[path] })),
        ...selectedImageFiles.map((_, idx) => ({ type: 'n', idx, deleted: false }))
      ];

      const isValid = (() => {
        if (!pinnedImageKey) return false;
        if (pinnedImageKey.startsWith('e:')) {
          const idx = Number(pinnedImageKey.slice(2));
          const path = existingDraftImagePaths[idx];
          return path && !existingDraftImageStates[path];
        }
        if (pinnedImageKey.startsWith('n:')) {
          const idx = Number(pinnedImageKey.slice(2));
          return selectedImageFiles[idx];
        }
        return false;
      })();

      if (isValid) return;

      // Find first available existing image
      const firstExisting = existingDraftImagePaths.findIndex(path => !existingDraftImageStates[path]);
      if (firstExisting >= 0) {
        pinnedImageKey = `e:${firstExisting}`;
        return;
      }

      // Or first new file
      if (selectedImageFiles.length > 0) {
        pinnedImageKey = 'n:0';
        return;
      }

      pinnedImageKey = '';
    }

    function showVideoName() {
      const input = byId('videoInput');
      const view = byId('videoName');
      const preview = byId('videoPreview');
      if (!input || !view) return;
      const file = input.files && input.files[0] ? input.files[0] : null;
      if (file) {
        view.textContent = `Selected: ${file.name}`;
        if (preview) {
          preview.src = URL.createObjectURL(file);
          preview.classList.remove('hidden');
        }
        return;
      }
      if (existingDraftVideoPath) {
        const parts = String(existingDraftVideoPath).split(/[\\/]/);
        view.textContent = `Selected: ${parts[parts.length - 1] || 'existing draft video'}`;
        if (preview) {
          preview.src = existingDraftVideoPath;
          preview.classList.remove('hidden');
        }
        return;
      }
      view.textContent = '';
      if (preview) {
        preview.removeAttribute('src');
        preview.classList.add('hidden');
      }
    }

    function addVariantRow(name = '', price = '', stock = '', variantId = null, existingImages = []) {
      const rows = byId('variantRows');
      if (!rows) return;

      const tempId = variantTempIdCounter++;
      variantsList.push({
        id: variantId || 0,
        tempId: tempId,
        name: name,
        price: price,
        stock: stock,
        images: existingImages.length > 0 ? existingImages.map(path => ({ path, deleted: false, previewUrl: path })) : [],
        newImages: [],
        pinnedImageKey: ''
      });

      renderVariants();
    }

    function renderVariants() {
      const rows = byId('variantRows');
      if (!rows) return;

      rows.innerHTML = variantsList.map((variant, listIdx) => {
        return `
          <div class="variant-row" data-variant-list-idx="${listIdx}">
            <div>
              <input type="text" class="input variant-name" placeholder="Variant Name (e.g. Red 64GB)" value="${String(variant.name || '').replace(/</g, '&lt;')}">
            </div>
            <div>
              <input type="number" min="0" step="0.01" class="input variant-price" placeholder="Price" value="${variant.price || ''}">
            </div>
            <div>
              <input type="number" min="0" step="1" class="input variant-stock" placeholder="Stock" value="${variant.stock || ''}">
            </div>
            <div>
              <input type="file" class="input variant-image-input" accept="image/*" multiple title="Add variant images" data-variant-temp-id="${variant.tempId}">
              <div class="variant-image-count">0 / 8 images</div>
              <div class="variant-images-grid"></div>
            </div>
            <button type="button" class="variant-remove-btn">Remove</button>
          </div>
        `;
      }).join('');

      // Attach event listeners
      rows.querySelectorAll('.variant-row').forEach((row, idx) => {
        const variant = variantsList[idx];
        if (!variant) return;

        row.querySelector('.variant-name').addEventListener('change', (e) => {
          variant.name = e.target.value;
        });

        row.querySelector('.variant-price').addEventListener('change', (e) => {
          variant.price = e.target.value;
        });

        row.querySelector('.variant-stock').addEventListener('change', (e) => {
          variant.stock = e.target.value;
        });

        const imageInput = row.querySelector('.variant-image-input');
        if (imageInput) {
          imageInput.addEventListener('change', () => handleVariantImageInputChange(idx, row));
        }

        row.querySelector('.variant-remove-btn').addEventListener('click', () => {
          variantsList.splice(idx, 1);
          renderVariants();
        });

        renderVariantImageManager(idx, row);
      });
    }

    function handleVariantImageInputChange(variantListIdx, row) {
      const variant = variantsList[variantListIdx];
      if (!variant) return;

      const input = row.querySelector('.variant-image-input');
      if (!input || !input.files) return;

      const selectedFiles = Array.from(input.files);
      input.value = '';

      const totalImages = getVariantActiveImageCount(variant);
      const remainingSlots = 8 - totalImages;

      if (remainingSlots <= 0) {
        showLocalSweetAlert('error', 'Image Limit Reached', 'Each variant can keep up to 8 images only.');
        return;
      }

      const filesToAdd = selectedFiles.slice(0, remainingSlots);
      filesToAdd.forEach((file) => {
        const previewUrl = URL.createObjectURL(file);
        variant.newImages.push({ file, previewUrl });
      });

      if (selectedFiles.length > filesToAdd.length) {
        showLocalSweetAlert('warning', 'Image Limit', 'Some images were not added due to the 8-image limit.');
      }

      renderVariantImageManager(variantListIdx, row);
    }

    function getVariantActiveImageCount(variant) {
      if (!variant) return 0;
      const existingCount = (variant.images || []).filter(img => !img.deleted).length;
      const newCount = (variant.newImages || []).length;
      return existingCount + newCount;
    }

    function renderVariantImageManager(variantListIdx, row) {
      const variant = variantsList[variantListIdx];
      if (!variant) return;

      const grid = row.querySelector('.variant-images-grid');
      const countEl = row.querySelector('.variant-image-count');
      if (!grid || !countEl) return;

      applyVariantPinnedFallback(variant);

      const cards = [];

      // Existing images
      (variant.images || []).forEach((item, idx) => {
        const key = `e:${idx}`;
        const checked = variant.pinnedImageKey === key ? 'checked' : '';
        const removedClass = item.deleted ? 'removed' : '';
        const buttonLabel = item.deleted ? 'Undo' : 'Remove';
        cards.push(`
          <div class="variant-image-card ${removedClass}">
            ${checked && !item.deleted ? '<span class="variant-image-badge">Pinned</span>' : ''}
            <img src="${String(item.previewUrl || item.path).replace(/</g, '&lt;')}" alt="Variant image">
            <div class="variant-image-actions">
              <label class="variant-image-pin">
                <input type="radio" name="variantPinned_${variant.tempId}" value="${key}" ${checked} ${item.deleted ? 'disabled' : ''}>
                <span>Pin</span>
              </label>
              <button type="button" class="variant-image-remove-btn" data-existing-index="${idx}" data-variant-list-idx="${variantListIdx}">${buttonLabel}</button>
            </div>
          </div>
        `);
      });

      // New images
      (variant.newImages || []).forEach((item, idx) => {
        const key = `n:${idx}`;
        const checked = variant.pinnedImageKey === key ? 'checked' : '';
        cards.push(`
          <div class="variant-image-card">
            ${checked ? '<span class="variant-image-badge">Pinned</span>' : ''}
            <img src="${String(item.previewUrl || '').replace(/</g, '&lt;')}" alt="New variant image">
            <div class="variant-image-actions">
              <label class="variant-image-pin">
                <input type="radio" name="variantPinned_${variant.tempId}" value="${key}" ${checked}>
                <span>Pin</span>
              </label>
              <button type="button" class="variant-image-remove-btn" data-new-index="${idx}" data-variant-list-idx="${variantListIdx}">Remove</button>
            </div>
          </div>
        `);
      });

      grid.innerHTML = cards.length ? cards.join('') : '<div style="grid-column: 1/-1; padding: 20px; text-align: center; color: #999; font-size: 13px;">Click "Choose File" to add images (1-8)</div>';
      countEl.textContent = `${getVariantActiveImageCount(variant)} / 8 images`;

      // Radio buttons for pinning
      grid.querySelectorAll(`input[name="variantPinned_${variant.tempId}"]`).forEach((radio) => {
        radio.addEventListener('change', () => {
          if (radio.checked) {
            variant.pinnedImageKey = radio.value;
            renderVariantImageManager(variantListIdx, row);
          }
        });
      });

      // Remove buttons for existing images
      grid.querySelectorAll('[data-existing-index]').forEach((btn) => {
        btn.addEventListener('click', () => {
          const idx = Number(btn.getAttribute('data-existing-index'));
          if (Number.isInteger(idx) && variant.images[idx]) {
            variant.images[idx].deleted = !variant.images[idx].deleted;
            applyVariantPinnedFallback(variant);
            renderVariantImageManager(variantListIdx, row);
          }
        });
      });

      // Remove buttons for new images
      grid.querySelectorAll('[data-new-index]').forEach((btn) => {
        btn.addEventListener('click', () => {
          const idx = Number(btn.getAttribute('data-new-index'));
          if (Number.isInteger(idx) && variant.newImages[idx]) {
            const removed = variant.newImages.splice(idx, 1)[0];
            if (removed && removed.previewUrl) {
              URL.revokeObjectURL(removed.previewUrl);
            }
            applyVariantPinnedFallback(variant);
            renderVariantImageManager(variantListIdx, row);
          }
        });
      });
    }

    function applyVariantPinnedFallback(variant) {
      if (!variant) return;
      const key = variant.pinnedImageKey;

      const isValid = (() => {
        if (!key) return false;
        if (key.startsWith('e:')) {
          const idx = Number(key.slice(2));
          return Number.isInteger(idx) && variant.images[idx] && !variant.images[idx].deleted;
        }
        if (key.startsWith('n:')) {
          const idx = Number(key.slice(2));
          return Number.isInteger(idx) && variant.newImages[idx];
        }
        return false;
      })();

      if (isValid) return;

      const firstExisting = (variant.images || []).findIndex(img => !img.deleted);
      if (firstExisting >= 0) {
        variant.pinnedImageKey = `e:${firstExisting}`;
        return;
      }

      if ((variant.newImages || []).length > 0) {
        variant.pinnedImageKey = 'n:0';
        return;
      }

      variant.pinnedImageKey = '';
    }

    function getVariantsFromForm() {
      const rows = byId('variantRows');
      if (rows) {
        rows.querySelectorAll('.variant-row').forEach((row, idx) => {
          const variant = variantsList[idx];
          if (!variant) return;
          const nameInput = row.querySelector('.variant-name');
          const priceInput = row.querySelector('.variant-price');
          const stockInput = row.querySelector('.variant-stock');
          if (nameInput) variant.name = nameInput.value;
          if (priceInput) variant.price = priceInput.value;
          if (stockInput) variant.stock = stockInput.value;
        });
      }

      return variantsList.map((variant) => {
        const imageFiles = [];
        const existingDeletedPaths = {};

        // Collect new image files
        (variant.newImages || []).forEach((img) => {
          if (img.file) {
            imageFiles.push(img.file);
          }
        });

        // Track which existing images are deleted
        (variant.images || []).forEach((img, idx) => {
          if (img.deleted) {
            existingDeletedPaths[String(idx)] = true;
          }
        });

        return {
          id: variant.id,
          tempId: variant.tempId,
          name: String(variant.name || '').trim(),
          price: String(variant.price || '').trim(),
          stock: String(variant.stock || '').trim(),
          imageFiles: imageFiles,
          existingImages: (variant.images || []).filter(img => !img.deleted).map(img => img.path || img.previewUrl),
          pinnedImageKey: variant.pinnedImageKey || ''
        };
      });
    }

    function collectDraftPayload() {
      const variants = getVariantsFromForm().map((variant) => ({
        id: variant.id,
        temp_id: variant.tempId,
        name: variant.name,
        price: variant.price,
        stock: variant.stock,
        pinned_image_key: variant.pinnedImageKey || ''
      }));

      return {
        draft_id: currentDraftId > 0 ? currentDraftId : 0,
        product_name: byId('productName').value,
        product_description: byId('description').value,
        price: byId('price').value,
        product_stock: byId('stock').value,
        pinned_image_key: pinnedImageKey,
        image_count: getActiveImageItems().filter(img => !img.deleted).length,
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
        body.append('pinned_image_key', payload.pinned_image_key || '');
        body.append('image_count', String(payload.image_count || 0));
        body.append('has_video', String(payload.has_video || 0));
        body.append('use_new_category', payload.use_new_category ? '1' : '0');
        body.append('category_id', payload.category_id || '');
        body.append('new_category_name', payload.new_category_name || '');
        body.append('variants', JSON.stringify(payload.variants || []));
        body.append('deleted_image_paths', JSON.stringify(
          existingDraftImagePaths.filter(path => existingDraftImageStates[path])
        ));

        selectedImageFiles.slice(0, 8).forEach((file) => body.append('images[]', file));

        const videoFile = byId('videoInput').files && byId('videoInput').files[0] ? byId('videoInput').files[0] : null;
        if (videoFile) {
          body.append('video', videoFile);
          existingDraftVideoPath = '';
        }

        const variantFiles = getVariantsFromForm();
        variantFiles.forEach((variant) => {
          const variantUploadId = Number(variant.id || 0) > 0 ? Number(variant.id) : Number(variant.tempId || 0);
          if (!variantUploadId) return;
          (variant.imageFiles || []).forEach((file, idx) => {
            body.append(`variant_${variantUploadId}_image_${idx}`, file);
          });
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
          url.searchParams.delete('draft_id');
          window.history.replaceState({}, '', url.toString());
        }

        status.className = 'card ok-text';
        status.textContent = 'Draft saved to server successfully.';
        await showLocalSweetAlert('success', 'Draft Saved', 'Draft saved. You can manage it on the Drafts page.');

        // Reload the saved draft back into the form so the saved images/video stay visible.
        if (currentDraftId > 0) {
          await loadDraftFromServer(currentDraftId);
        } else {
          await loadCategories();
        }
      } catch (err) {
        status.className = 'card error-text';
        status.textContent = err.message || 'Failed to save draft.';
        await showLocalSweetAlert('error', 'Draft Save Failed', err.message || 'Unable to save draft.');
      }
    }

    function applyDraftToForm(draft) {
      if (!draft || typeof draft !== 'object') return;

      const productNameEl = byId('productName');
      const descriptionEl = byId('description');
      const priceEl = byId('price');
      const stockEl = byId('stock');
      const categorySelectEl = byId('categorySelect');
      const newCategoryNameEl = byId('newCategoryName');

      if (productNameEl) productNameEl.value = draft.product_name || '';
      if (descriptionEl) descriptionEl.value = draft.product_description || '';
      if (priceEl) priceEl.value = draft.price || '';
      if (stockEl) stockEl.value = draft.product_stock || '';
      if (categorySelectEl) categorySelectEl.value = draft.category_id || '';
      if (newCategoryNameEl) newCategoryNameEl.value = draft.new_category_name || '';

      pinnedImageKey = String(draft.pinned_image_key || '');
      toggleCategoryMode(Number(draft.use_new_category || 0) === 1);

      existingDraftImagePaths = Array.isArray(draft?.media?.images)
        ? draft.media.images.map((item) => String(item?.path || '')).filter(Boolean)
        : [];
      existingDraftImageStates = {};
      selectedImageFiles = [];

      if (Array.isArray(draft?.media?.images)) {
        const pinnedIdx = draft.media.images.findIndex((item) => !!item?.is_pinned);
        if (pinnedIdx >= 0) {
          pinnedImageKey = `e:${pinnedIdx}`;
        }
      }

      existingDraftVideoPath = String(draft?.media?.video || '');
      existingVariantImageMap = draft?.media?.variant_images && typeof draft.media.variant_images === 'object'
        ? draft.media.variant_images
        : {};

      variantsList = [];
      if (Array.isArray(draft.variants)) {
        draft.variants.forEach((variant) => {
          const variantId = Number(variant?.id || 0);
          const variantImages = Array.isArray(variant?.images) ? variant.images : [];
          addVariantRow(
            variant?.name || '',
            variant?.price || '',
            variant?.stock || '',
            variantId || null,
            variantImages
          );
        });
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
      const images = getActiveImageItems().filter(img => !img.deleted);
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

      const price = byId('price').value;
      if (price === '' || Number(price) < 0) return 'Price must be a non-negative number.';

      const stock = byId('stock').value;
      if (stock === '' || Number(stock) < 0) return 'Stock must be a non-negative number.';

      const variants = getVariantsFromForm();
      
      for (const variant of variants) {
        const variantName = String(variant.name || '').trim();
        const variantPrice = String(variant.price || '').trim();
        const variantStock = String(variant.stock || '').trim();
        const allImages = [...(variant.imageFiles || []), ...(variant.existingImages || [])];

        if (!variantName) {
          return 'Each variant must have a name.';
        }

        if (variantPrice === '' || Number.isNaN(Number(variantPrice)) || Number(variantPrice) < 0) {
          return 'Each variant price must be a non-negative number.';
        }

        if (variantStock === '' || Number.isNaN(Number(variantStock)) || Number(variantStock) < 0) {
          return 'Each variant stock must be a non-negative number.';
        }

        if (allImages.length === 0) {
          return 'Each variant must have at least one image.';
        }

        if (allImages.length > 8) {
          return 'Each variant can have up to 8 images only.';
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
      const activeImages = getActiveImageItems().filter(img => !img.deleted);
      const infoSummary = [
        `Name: ${byId('productName').value.trim()}`,
        `Description: ${byId('description').value.trim() ? 'Provided' : 'Missing'}`,
        `Category: ${categoryLabel}`,
        `Price: PHP ${byId('price').value}`,
        `Stock: ${byId('stock').value}`,
        `Images: ${activeImages.length}/8`,
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
          id: Number(variant.id || variant.tempId || 0),
          temp_id: Number(variant.tempId || 0),
          name: String(variant.name || '').trim(),
          price: Number(variant.price),
          stock: Number(variant.stock || 0),
          pinnedImageKey: variant.pinnedImageKey || ''
        }));

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

        // Add main product images (only non-deleted ones)
        const mainImageItems = getActiveImageItems().filter(img => !img.deleted);
        mainImageItems.forEach((item) => {
          if (item.type === 'file') {
            formData.append('images[]', item.file);
          }
        });
        formData.append('pinned_image_key', pinnedImageKey);

        // Add variant images
        variantsWithFiles.forEach((variant) => {
          (variant.imageFiles || []).forEach((file, idx) => {
            formData.append(`variant_${Number(variant.tempId)}_image_${idx}`, file);
          });
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
        existingDraftImageStates = {};
        existingDraftVideoPath = '';
        existingVariantImageMap = {};
        variantsList = [];
        pinnedImageKey = '';
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

    const imagesInput = byId('imagesInput');
    if (imagesInput) {
      imagesInput.addEventListener('change', handleImagesSelection);
    }
    
    const videoInput = byId('videoInput');
    if (videoInput) {
      videoInput.addEventListener('change', showVideoName);
    }

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
