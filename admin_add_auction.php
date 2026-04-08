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
  <title>Add Auction Item - Admin</title>
  <link rel="stylesheet" href="main.css">
  <link rel="stylesheet" href="assets/css/local_swal.css">
  <script src="assets/js/local_swal.js"></script>
  <style>
    :root {
      --bg-a: #f2f6fb;
      --bg-b: #eaf2ff;
      --card: #ffffff;
      --line: #d7e2f0;
      --line-strong: #c8d6e8;
      --text: #122033;
      --muted: #5f7086;
      --brand: #2f5dd7;
      --brand-strong: #234cb6;
      --focus: rgba(47, 93, 215, 0.16);
    }
    * { box-sizing: border-box; }
    body {
      margin: 0;
      font-family: 'Trebuchet MS', 'Segoe UI', Tahoma, sans-serif;
      background:
        radial-gradient(circle at 10% 10%, rgba(67, 124, 246, 0.10) 0, rgba(67, 124, 246, 0) 34%),
        radial-gradient(circle at 90% 15%, rgba(14, 116, 144, 0.08) 0, rgba(14, 116, 144, 0) 30%),
        linear-gradient(165deg, var(--bg-a) 0%, var(--bg-b) 100%);
      color: var(--text);
    }
    .container {
      width: calc(100% - 48px);
      max-width: none;
      margin: 0 auto;
      padding: 92px 0 110px;
    }
    .topbar {
      position: fixed;
      top: 0;
      left: 0;
      right: 0;
      z-index: 50;
      background: rgba(242, 246, 251, 0.82);
      backdrop-filter: blur(8px);
      border-bottom: 1px solid var(--line);
      padding: 12px 0;
    }
    .topbar-inner {
      width: calc(100% - 48px);
      max-width: none;
      margin: 0 auto;
      display: flex;
      gap: 10px;
      align-items: center;
      background: rgba(255, 255, 255, 0.95);
      border: 1px solid var(--line);
      border-radius: 12px;
      padding: 10px 12px;
      box-shadow: 0 8px 20px rgba(15, 23, 42, 0.06);
    }
    .back-btn {
      border: 1px solid var(--line-strong);
      background: #fff;
      width: 36px;
      height: 36px;
      border-radius: 9px;
      font-size: 20px;
      cursor: pointer;
      color: #1e293b;
    }
    .back-btn:hover { background: #f7fbff; }
    .title {
      flex: 1;
      font-size: 21px;
      font-weight: 700;
      letter-spacing: 0.2px;
    }
    .top-link {
      border: 1px solid #9cb3da;
      background: #eef4ff;
      color: var(--brand);
      border-radius: 9px;
      padding: 9px 12px;
      font-size: 12px;
      font-weight: 700;
      cursor: pointer;
    }
    .top-link:hover { background: #e6efff; }
    .card {
      background: var(--card);
      border: 1px solid var(--line);
      border-radius: 14px;
      padding: 16px;
      margin-top: 14px;
      box-shadow: 0 10px 22px rgba(30, 64, 175, 0.06);
    }
    .label {
      font-size: 13px;
      font-weight: 700;
      color: #274060;
      margin-bottom: 6px;
      display: block;
    }
    .field-guide {
      margin-top: 6px;
      font-size: 12px;
      color: var(--muted);
      line-height: 1.35;
    }
    .input, .textarea, .select {
      width: 100%;
      border: 1px solid #c8d6e8;
      border-radius: 9px;
      padding: 10px 12px;
      font-size: 14px;
      background: #fff;
      color: #1e293b;
      font-family: inherit;
      transition: border-color 0.15s ease, box-shadow 0.15s ease;
    }
    .textarea { min-height: 110px; resize: vertical; }
    .input:focus, .textarea:focus, .select:focus {
      outline: none;
      border-color: #7fa0ea;
      box-shadow: 0 0 0 3px var(--focus);
    }
    .grid {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 10px;
    }
    .hint {
      margin-top: 6px;
      font-size: 12px;
      color: #64748b;
    }
    .media-list {
      margin-top: 8px;
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
      gap: 8px;
    }
    .media-item {
      border: 1px solid #e2e8f0;
      border-radius: 8px;
      padding: 6px;
      background: #f8fafc;
    }
    .media-item img {
      width: 100%;
      height: 90px;
      object-fit: cover;
      border-radius: 6px;
      display: block;
    }
    .media-actions {
      margin-top: 6px;
      display: flex;
      gap: 6px;
      justify-content: flex-end;
    }
    .mini-btn {
      border: 1px solid #d1d9e5;
      background: #fff;
      color: #334155;
      border-radius: 7px;
      padding: 5px 8px;
      font-size: 11px;
      font-weight: 700;
      cursor: pointer;
    }
    .mini-btn.danger {
      border-color: #fecaca;
      background: #fef2f2;
      color: #b91c1c;
    }
    .video-preview {
      margin-top: 8px;
      border: 1px solid #d6e0ef;
      border-radius: 10px;
      padding: 8px;
      background: #f8fbff;
    }
    .video-preview video {
      width: 100%;
      max-height: 220px;
      border-radius: 8px;
      background: #000;
      display: block;
    }
    .video-preview-title {
      font-size: 12px;
      color: #36588f;
      font-weight: 700;
      margin-bottom: 6px;
    }
    .video-pill {
      margin-top: 8px;
      padding: 8px 10px;
      border: 1px dashed #9fb4d4;
      border-radius: 8px;
      font-size: 12px;
      color: #36588f;
      background: #f5f9ff;
    }
    .footer {
      position: fixed;
      left: 0;
      right: 0;
      bottom: 0;
      z-index: 40;
      background: #fff;
      border-top: 1px solid var(--line);
      padding: 10px 0;
      box-shadow: 0 -6px 18px rgba(15, 23, 42, 0.05);
    }
    .footer-inner {
      width: calc(100% - 48px);
      max-width: none;
      margin: 0 auto;
      display: flex;
      gap: 10px;
      align-items: center;
    }
    .btn {
      border: none;
      border-radius: 9px;
      padding: 11px 14px;
      font-size: 14px;
      font-weight: 700;
      cursor: pointer;
    }
    .btn-save {
      background: linear-gradient(135deg, var(--brand) 0%, var(--brand-strong) 100%);
      color: #fff;
    }
    .btn-publish {
      background: #0f8d4e;
      color: #fff;
    }
    .btn-publish:hover { filter: brightness(1.03); }
    .btn-save:hover { filter: brightness(1.04); }
    .status {
      font-size: 13px;
      color: #475569;
      flex: 1;
    }
    @media (max-width: 760px) {
      .grid { grid-template-columns: 1fr; }
      .title { font-size: 17px; }
    }
  </style>
</head>
<body>
  <div class="topbar">
    <div class="topbar-inner">
      <button class="back-btn" type="button" onclick="window.location.href='admin_dashboard.php'">‹</button>
      <div class="title">Add Auction Item</div>
      <button class="top-link" type="button" onclick="window.location.href='admin_auction_drafts.php'">Auction Drafts</button>
    </div>
  </div>

  <div class="container">
    <div class="card">
      <label class="label" for="itemName">Item Name</label>
      <input class="input" id="itemName" type="text" maxlength="180" placeholder="Rare signed collectible">
      <div class="field-guide">Use a clear title buyers can recognize quickly, such as brand, series, and year.</div>

      <label class="label" for="itemDescription" style="margin-top:12px;">Description</label>
      <textarea class="textarea" id="itemDescription" placeholder="Describe rarity, authenticity, and condition details..."></textarea>
      <div class="field-guide">Include authenticity notes, flaws, inclusions, and provenance to build bidder trust.</div>

      <div class="grid" style="margin-top:12px;">
        <div>
          <label class="label" for="conditionGrade">Condition</label>
          <select class="select" id="conditionGrade">
            <option value="">Select condition</option>
            <option value="Mint">Mint</option>
            <option value="Near Mint">Near Mint</option>
            <option value="Excellent">Excellent</option>
            <option value="Good">Good</option>
            <option value="Fair">Fair</option>
          </select>
          <div class="field-guide">Choose the closest condition so bidders can compare this item accurately.</div>
        </div>
        <div>
          <label class="label" for="categoryId">Category (optional)</label>
          <select class="select" id="categoryId"></select>
          <div class="field-guide">Category helps users find this auction faster in filters and search results.</div>
        </div>
      </div>

      <div class="grid" style="margin-top:12px;">
        <div>
          <label class="label" for="startingBid">Starting Bid (PHP)</label>
          <input class="input" id="startingBid" type="number" step="0.01" min="0" placeholder="1000.00">
          <div class="field-guide">Set the minimum opening amount. Lower starts usually attract more early bids.</div>
        </div>
        <div>
          <label class="label" for="reservePrice">Reserve Price (optional)</label>
          <input class="input" id="reservePrice" type="number" step="0.01" min="0" placeholder="5000.00">
          <div class="field-guide">Optional safety floor. The item is not sold if final bid is below this value.</div>
        </div>
      </div>

      <div class="grid" style="margin-top:12px;">
        <div>
          <label class="label" for="bidIncrement">Bid Increment (PHP)</label>
          <input class="input" id="bidIncrement" type="number" step="0.01" min="0" placeholder="100.00">
          <div class="field-guide">Controls minimum increase per bid. Keep it balanced for healthy competition.</div>
        </div>
        <div></div>
      </div>

      <div class="grid" style="margin-top:12px;">
        <div>
          <label class="label" for="startAt">Auction Start</label>
          <input class="input" id="startAt" type="datetime-local">
          <div class="field-guide">Set when bidding opens. Leave blank to finalize schedule later.</div>
        </div>
        <div>
          <label class="label" for="endAt">Auction End</label>
          <input class="input" id="endAt" type="datetime-local">
          <div class="field-guide">Set when bidding closes. End must be later than start time.</div>
        </div>
      </div>
      <div class="hint">You can leave schedule fields blank while drafting. Saved and published auctions cannot overlap with any active, scheduled, or draft auction window.</div>
    </div>

    <div class="card">
      <label class="label" for="imagesInput">Main Images</label>
      <input class="input" id="imagesInput" type="file" accept="image/*">
      <div class="field-guide">Upload one main image. Re-upload or remove if you selected the wrong file.</div>
      <div class="hint">Only one image is kept for each auction draft.</div>
      <div id="selectedImagePreview" class="media-list" style="display:none;"></div>
      <div id="existingImages" class="media-list" style="display:none;"></div>
    </div>

    <div class="card">
      <label class="label" for="videoInput">Video (optional)</label>
      <input class="input" id="videoInput" type="file" accept="video/mp4,video/webm,video/quicktime">
      <div class="field-guide">A short video can improve confidence by showing real condition and authenticity details.</div>
      <div class="hint">Only one video is kept for each auction draft.</div>
      <div id="selectedVideoPreview" class="video-preview" style="display:none;"></div>
      <div id="existingVideo" class="video-pill" style="display:none;"></div>
      <div id="existingVideoPreview" class="video-preview" style="display:none;"></div>
    </div>
  </div>

  <div class="footer">
    <div class="footer-inner">
      <button class="btn btn-save" type="button" id="saveBtn" onclick="saveDraft()">Save Auction Draft</button>
      <button class="btn btn-publish" type="button" id="publishBtn" onclick="publishDraft()">Publish Auction</button>
      <div id="statusText" class="status">Ready.</div>
    </div>
  </div>

  <script>
    let currentDraftId = <?php echo (int)$draftId; ?>;
    let removeExistingImage = false;
    let removeExistingVideo = false;

    function byId(id) {
      return document.getElementById(id);
    }

    function showAlert(icon, title, text) {
      if (window.localSwalAlert) {
        return window.localSwalAlert(icon, title, text);
      }
      window.alert(text || title);
      return Promise.resolve();
    }

    async function showConfirm(title, text, confirmText = 'Confirm') {
      if (window.localSwalConfirm) {
        return window.localSwalConfirm(title, text, confirmText);
      }
      return window.confirm(text || title);
    }

    function parseNumericInput(raw) {
      const val = String(raw || '').trim();
      if (val === '') return null;
      const num = Number(val);
      return Number.isFinite(num) ? num : NaN;
    }

    async function checkScheduleAvailability() {
      const startAt = byId('startAt').value;
      const endAt = byId('endAt').value;

      if (!startAt || !endAt) {
        return true;
      }

      const body = new FormData();
      body.append('draft_id', String(currentDraftId || 0));
      body.append('start_at', startAt);
      body.append('end_at', endAt);

      const res = await fetch('api/check-auction-schedule.php', {
        method: 'POST',
        body
      });
      const data = await res.json();
      if (!res.ok || !data.success) {
        throw new Error(data.error || 'Unable to validate auction schedule');
      }

      if (data.conflict) {
        await showAlert('warning', 'Schedule Conflict', data.message || 'Another auction already overlaps with this schedule.');
        return false;
      }

      return true;
    }

    async function validateDraftInputs(options = {}) {
      const forPublish = Boolean(options.forPublish);
      const itemName = byId('itemName').value.trim();
      const itemDescription = byId('itemDescription').value.trim();
      const conditionGrade = byId('conditionGrade').value;
      const startingBid = parseNumericInput(byId('startingBid').value);
      const reservePrice = parseNumericInput(byId('reservePrice').value);
      const bidIncrement = parseNumericInput(byId('bidIncrement').value);
      const startAt = byId('startAt').value;
      const endAt = byId('endAt').value;

      if (itemName.length < 3) {
        await showAlert('warning', 'Invalid Item Name', 'Item name must be at least 3 characters long.');
        byId('itemName').focus();
        return false;
      }

      if (startingBid !== null && (!Number.isFinite(startingBid) || startingBid < 0)) {
        await showAlert('warning', 'Invalid Starting Bid', 'Starting bid must be a valid non-negative number.');
        byId('startingBid').focus();
        return false;
      }

      if (reservePrice !== null && (!Number.isFinite(reservePrice) || reservePrice < 0)) {
        await showAlert('warning', 'Invalid Reserve Price', 'Reserve price must be a valid non-negative number.');
        byId('reservePrice').focus();
        return false;
      }

      if (Number.isFinite(startingBid) && Number.isFinite(reservePrice) && reservePrice < startingBid) {
        await showAlert('warning', 'Invalid Reserve Price', 'Reserve price cannot be lower than starting bid.');
        byId('reservePrice').focus();
        return false;
      }

      if (bidIncrement !== null && (!Number.isFinite(bidIncrement) || bidIncrement <= 0)) {
        await showAlert('warning', 'Invalid Bid Increment', 'Bid increment must be greater than 0.');
        byId('bidIncrement').focus();
        return false;
      }

      if (startAt && endAt) {
        const start = new Date(startAt).getTime();
        const end = new Date(endAt).getTime();
        if (!Number.isFinite(start) || !Number.isFinite(end) || end <= start) {
          await showAlert('warning', 'Invalid Schedule', 'End time must be later than start time.');
          byId('endAt').focus();
          return false;
        }
      }

      if (!forPublish) {
        return true;
      }

      const hasSelectedImage = Boolean(byId('imagesInput').files?.[0]);
      const hasExistingImage = byId('existingImages').style.display !== 'none' && byId('existingImages').children.length > 0;
      const hasSelectedVideo = Boolean(byId('videoInput').files?.[0]);
      const hasExistingVideo = byId('existingVideoPreview').style.display !== 'none' && byId('existingVideoPreview').children.length > 0;

      if (itemDescription.length < 8) {
        await showAlert('warning', 'Description Required', 'Please provide a meaningful item description before publishing.');
        byId('itemDescription').focus();
        return false;
      }

      if (!conditionGrade) {
        await showAlert('warning', 'Condition Required', 'Please select a condition before publishing.');
        byId('conditionGrade').focus();
        return false;
      }

      if (!Number.isFinite(startingBid) || startingBid <= 0) {
        await showAlert('warning', 'Starting Bid Required', 'Starting bid must be greater than 0 before publishing.');
        byId('startingBid').focus();
        return false;
      }

      if (!Number.isFinite(bidIncrement) || bidIncrement <= 0) {
        await showAlert('warning', 'Bid Increment Required', 'Bid increment must be greater than 0 before publishing.');
        byId('bidIncrement').focus();
        return false;
      }

      if (!startAt || !endAt) {
        await showAlert('warning', 'Schedule Required', 'Start and end schedule are required before publishing.');
        (!startAt ? byId('startAt') : byId('endAt')).focus();
        return false;
      }

      if (!hasSelectedImage && !hasExistingImage) {
        await showAlert('warning', 'Main Image Required', 'Please add a main image before publishing.');
        byId('imagesInput').focus();
        return false;
      }

      if (!hasSelectedVideo && !hasExistingVideo) {
        await showAlert('warning', 'Video Required', 'Please add a product video before publishing.');
        byId('videoInput').focus();
        return false;
      }

      return true;
    }

    function toLocalDateInput(val) {
      if (!val) return '';
      const d = new Date(String(val).replace(' ', 'T'));
      if (Number.isNaN(d.getTime())) return '';
      const pad = (n) => String(n).padStart(2, '0');
      return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}T${pad(d.getHours())}:${pad(d.getMinutes())}`;
    }

    async function loadCategories() {
      const sel = byId('categoryId');
      if (!sel) return;
      sel.innerHTML = '<option value="">No category</option>';
      try {
        const res = await fetch('api/get-categories.php', { cache: 'no-store' });
        const data = await res.json();
        const list = Array.isArray(data?.categories) ? data.categories : [];
        list.forEach((cat) => {
          const opt = document.createElement('option');
          opt.value = String(cat.category_id || '');
          opt.textContent = String(cat.category_name || 'Category');
          sel.appendChild(opt);
        });
      } catch (err) {
        // Keep default option only when categories fail to load.
      }
    }

    function renderExistingMedia(draft) {
      const existingImages = Array.isArray(draft?.media?.images) ? draft.media.images : [];
      const existingVideo = String(draft?.media?.video || '').trim();

      const imageHost = byId('existingImages');
      imageHost.innerHTML = '';
      if (existingImages.length > 0) {
        imageHost.style.display = 'grid';
        const img = existingImages[0];
        const card = document.createElement('div');
        card.className = 'media-item';
        const im = document.createElement('img');
        im.src = String(img.path || '');
        im.alt = 'Draft image';
        card.appendChild(im);

        const actions = document.createElement('div');
        actions.className = 'media-actions';
        const removeBtn = document.createElement('button');
        removeBtn.className = 'mini-btn danger';
        removeBtn.type = 'button';
        removeBtn.textContent = 'Remove image';
        removeBtn.addEventListener('click', () => {
          removeExistingImage = true;
          imageHost.style.display = 'none';
          imageHost.innerHTML = '';
        });
        actions.appendChild(removeBtn);
        card.appendChild(actions);
        imageHost.appendChild(card);
      } else {
        imageHost.style.display = 'none';
      }

      const videoHost = byId('existingVideo');
      const existingVideoPreview = byId('existingVideoPreview');
      if (existingVideo) {
        videoHost.style.display = 'block';
        const parts = existingVideo.split(/[\\/]/);
        videoHost.textContent = `Existing video: ${parts[parts.length - 1] || 'draft video'}`;

        existingVideoPreview.style.display = 'block';
        existingVideoPreview.innerHTML = '';
        const title = document.createElement('div');
        title.className = 'video-preview-title';
        title.textContent = 'Current video';
        const video = document.createElement('video');
        video.controls = true;
        video.preload = 'metadata';
        video.src = existingVideo;
        const actions = document.createElement('div');
        actions.className = 'media-actions';
        const removeBtn = document.createElement('button');
        removeBtn.className = 'mini-btn danger';
        removeBtn.type = 'button';
        removeBtn.textContent = 'Remove video';
        removeBtn.addEventListener('click', () => {
          removeExistingVideo = true;
          videoHost.style.display = 'none';
          existingVideoPreview.style.display = 'none';
          existingVideoPreview.innerHTML = '';
        });
        actions.appendChild(removeBtn);
        existingVideoPreview.appendChild(title);
        existingVideoPreview.appendChild(video);
        existingVideoPreview.appendChild(actions);
      } else {
        videoHost.style.display = 'none';
        existingVideoPreview.style.display = 'none';
        existingVideoPreview.innerHTML = '';
      }
    }

    function clearSelectedImagePreview() {
      const selectedHost = byId('selectedImagePreview');
      selectedHost.style.display = 'none';
      selectedHost.innerHTML = '';
      byId('imagesInput').value = '';
    }

    function clearSelectedVideoPreview() {
      const selectedVideoHost = byId('selectedVideoPreview');
      selectedVideoHost.style.display = 'none';
      selectedVideoHost.innerHTML = '';
      byId('videoInput').value = '';
    }

    function bindLocalMediaPreviews() {
      const imageInput = byId('imagesInput');
      const videoInput = byId('videoInput');
      const selectedImageHost = byId('selectedImagePreview');
      const selectedVideoHost = byId('selectedVideoPreview');

      imageInput.addEventListener('change', () => {
        const file = imageInput.files?.[0];
        selectedImageHost.innerHTML = '';
        if (!file) {
          selectedImageHost.style.display = 'none';
          return;
        }

        const card = document.createElement('div');
        card.className = 'media-item';
        const img = document.createElement('img');
        img.src = URL.createObjectURL(file);
        img.alt = 'Selected image';
        img.onload = () => URL.revokeObjectURL(img.src);

        const actions = document.createElement('div');
        actions.className = 'media-actions';
        const removeBtn = document.createElement('button');
        removeBtn.className = 'mini-btn danger';
        removeBtn.type = 'button';
        removeBtn.textContent = 'Remove selected';
        removeBtn.addEventListener('click', clearSelectedImagePreview);

        actions.appendChild(removeBtn);
        card.appendChild(img);
        card.appendChild(actions);
        selectedImageHost.appendChild(card);
        selectedImageHost.style.display = 'grid';
      });

      videoInput.addEventListener('change', () => {
        const file = videoInput.files?.[0];
        selectedVideoHost.innerHTML = '';
        if (!file) {
          selectedVideoHost.style.display = 'none';
          return;
        }

        const title = document.createElement('div');
        title.className = 'video-preview-title';
        title.textContent = 'Selected video';

        const video = document.createElement('video');
        video.controls = true;
        video.preload = 'metadata';
        video.src = URL.createObjectURL(file);
        video.onloadeddata = () => URL.revokeObjectURL(video.src);

        const actions = document.createElement('div');
        actions.className = 'media-actions';
        const removeBtn = document.createElement('button');
        removeBtn.className = 'mini-btn danger';
        removeBtn.type = 'button';
        removeBtn.textContent = 'Remove selected';
        removeBtn.addEventListener('click', clearSelectedVideoPreview);

        actions.appendChild(removeBtn);
        selectedVideoHost.appendChild(title);
        selectedVideoHost.appendChild(video);
        selectedVideoHost.appendChild(actions);
        selectedVideoHost.style.display = 'block';
      });
    }

    function applyDraft(draft) {
      if (!draft || typeof draft !== 'object') return;
      removeExistingImage = false;
      removeExistingVideo = false;
      clearSelectedImagePreview();
      clearSelectedVideoPreview();
      byId('itemName').value = draft.item_name || '';
      byId('itemDescription').value = draft.item_description || '';
      byId('conditionGrade').value = draft.condition_grade || '';
      byId('categoryId').value = draft.category_id || '';
      byId('startingBid').value = draft.starting_bid || '';
      byId('reservePrice').value = draft.reserve_price || '';
      byId('bidIncrement').value = draft.bid_increment || '';
      byId('startAt').value = toLocalDateInput(draft.start_at || '');
      byId('endAt').value = toLocalDateInput(draft.end_at || '');
      renderExistingMedia(draft);
    }

    async function loadDraft() {
      if (!currentDraftId || currentDraftId <= 0) return;
      try {
        byId('statusText').textContent = 'Loading selected draft...';
        const res = await fetch(`api/get-auction-drafts.php?draft_id=${encodeURIComponent(currentDraftId)}`, { cache: 'no-store' });
        const data = await res.json();
        if (!res.ok || !data.success || !data.draft) {
          throw new Error(data.error || 'Unable to load auction draft');
        }
        applyDraft(data.draft);
        byId('statusText').textContent = 'Draft loaded.';
      } catch (err) {
        byId('statusText').textContent = err.message || 'Failed to load draft.';
      }
    }

    async function saveDraft() {
      const saveBtn = byId('saveBtn');
      const status = byId('statusText');
      try {
        const okValidation = await validateDraftInputs({ forPublish: false });
        if (!okValidation) return;

        const okSchedule = await checkScheduleAvailability();
        if (!okSchedule) return;

        saveBtn.disabled = true;
        status.textContent = 'Saving auction draft...';

        const body = new FormData();
        body.append('draft_id', String(currentDraftId || 0));
        body.append('item_name', byId('itemName').value.trim());
        body.append('item_description', byId('itemDescription').value.trim());
        body.append('condition_grade', byId('conditionGrade').value);
        body.append('category_id', byId('categoryId').value);
        body.append('starting_bid', byId('startingBid').value.trim());
        body.append('reserve_price', byId('reservePrice').value.trim());
        body.append('bid_increment', byId('bidIncrement').value.trim());
        body.append('start_at', byId('startAt').value);
        body.append('end_at', byId('endAt').value);
        body.append('remove_existing_image', removeExistingImage ? '1' : '0');
        body.append('remove_existing_video', removeExistingVideo ? '1' : '0');

        const imageFile = byId('imagesInput').files?.[0];
        if (imageFile) {
          body.append('images[]', imageFile);
          body.set('remove_existing_image', '1');
        }

        const videoFile = byId('videoInput').files?.[0];
        if (videoFile) {
          body.append('video', videoFile);
          body.set('remove_existing_video', '1');
        }

        const res = await fetch('api/save-auction-draft.php', {
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

        clearSelectedImagePreview();
        clearSelectedVideoPreview();
        await loadDraft();
        status.textContent = 'Auction draft saved successfully.';
        showAlert('success', 'Draft Saved', 'Auction draft saved successfully.');
      } catch (err) {
        status.textContent = err.message || 'Save failed.';
        showAlert('error', 'Save Failed', err.message || 'Save failed.');
      } finally {
        saveBtn.disabled = false;
      }
    }

    async function publishDraft() {
      const publishBtn = byId('publishBtn');
      const status = byId('statusText');

      if (!currentDraftId || currentDraftId <= 0) {
        status.textContent = 'Please save the draft first before publishing.';
        showAlert('warning', 'Save Draft First', 'Please save the draft first before publishing.');
        return;
      }

      const okValidation = await validateDraftInputs({ forPublish: true });
      if (!okValidation) return;

      const okSchedule = await checkScheduleAvailability();
      if (!okSchedule) return;

      const ok = await showConfirm('Publish Auction', 'Publish this draft as a live auction listing?', 'Publish');
      if (!ok) {
        return;
      }

      try {
        publishBtn.disabled = true;
        status.textContent = 'Publishing draft...';

        const body = new FormData();
        body.append('draft_id', String(currentDraftId));

        const res = await fetch('api/publish-auction-draft.php', {
          method: 'POST',
          body
        });
        const data = await res.json();
        if (!res.ok || !data.success) {
          throw new Error(data.error || 'Failed to publish draft');
        }

        status.textContent = 'Auction published successfully.';
        showAlert('success', 'Published', 'Auction published successfully.');
        window.location.href = `admin_auction_drafts.php?published_id=${encodeURIComponent(String(data.auction_id || 0))}`;
      } catch (err) {
        status.textContent = err.message || 'Publish failed.';
        showAlert('error', 'Publish Failed', err.message || 'Publish failed.');
      } finally {
        publishBtn.disabled = false;
      }
    }

    (async function init() {
      bindLocalMediaPreviews();
      await loadCategories();
      await loadDraft();
    })();
  </script>
</body>
</html>
