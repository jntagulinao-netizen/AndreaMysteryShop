<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: LogIn.php');
    exit;
}

$role = $_SESSION['user_role'] ?? 'user';
if ($role !== 'user') {
    header('Location: admin_dashboard.php');
    exit;
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>My Bid History</title>
  <link rel="stylesheet" href="main.css">
  <link rel="stylesheet" href="assets/css/user_dashboard_checkout.css?v=20260508-2">
  <link rel="stylesheet" href="assets/css/local_swal.css">

  <link rel="stylesheet" href="assets/css/user_dashboard_search.css?v=20260508-2">
  <link rel="stylesheet" href="assets/css/user_dashboard_shared.css?v=20260508-2">
  <script src="assets/js/local_swal.js"></script>
 
  <style>
    :root {
      --bg: #06070d;
      --text: #f2f6fb;
      --muted: rgba(242, 246, 251, 0.7);
      --line: rgba(255,255,255,0.12);
      --panel: rgba(255,255,255,0.06);
      --accent: #f59e0b;
      --good: #10b981;
    }
    * { box-sizing: border-box; }
    html {
      min-height: 100%;
      background: #05060a;
    }
    body {
      margin: 0;
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      color: var(--text);
      background: #05060a;
      min-height: 100vh;
      display: flex;
      flex-direction: column;
    }
    .wrap {
      max-width: none;
      width: 100%;
      margin: 0;
      padding: 84px 20px 36px;
      min-height: calc(100vh - 56px);
      flex: 1;
    }
    .topbar {
      position: fixed;
      top: 0;
      left: 0;
      right: 0;
      z-index: 20;
      background: rgba(5,6,10,0.8);
      backdrop-filter: blur(14px);
      border-bottom: 1px solid var(--line);
    }
    .topbar-inner {
      max-width: none;
      margin: 0;
      padding: 12px 20px;
      display: flex;
      gap: 10px;
      align-items: center;
    }
    .link {
      border: 1px solid var(--line);
      background: var(--panel);
      color: #fff;
      border-radius: 999px;
      padding: 9px 12px;
      text-decoration: none;
      font-size: 13px;
      font-weight: 700;
    }
    .title {
      margin: 0;
      font-size: clamp(28px, 4vw, 44px);
      letter-spacing: -0.03em;
    }
    .subtitle {
      margin-top: 8px;
      color: var(--muted);
      line-height: 1.6;
      max-width: 70ch;
    }
    .list {
      margin-top: 18px;
      display: grid;
      gap: 12px;
    }
    .card {
      border: 1px solid var(--line);
      background: var(--panel);
      border-radius: 16px;
      padding: 12px;
      display: grid;
      grid-template-columns: 86px 1fr auto;
      gap: 12px;
      align-items: center;
    }
    .thumb {
      width: 86px;
      height: 86px;
      border-radius: 12px;
      object-fit: cover;
      border: 1px solid var(--line);
      background: rgba(255,255,255,0.08);
    }
    .meta-title {
      margin: 0;
      font-size: 17px;
      line-height: 1.2;
    }
    .meta-line {
      margin-top: 5px;
      color: var(--muted);
      font-size: 13px;
      line-height: 1.5;
    }
    .pill {
      display: inline-flex;
      border-radius: 999px;
      padding: 5px 9px;
      font-size: 11px;
      font-weight: 800;
      border: 1px solid var(--line);
      background: rgba(255,255,255,0.06);
      text-transform: uppercase;
      letter-spacing: 0.06em;
      margin-right: 6px;
    }
    .pill.active { background: rgba(16,185,129,0.14); border-color: rgba(16,185,129,0.24); color: #9bf0cd; }
    .pill.scheduled { background: rgba(59,130,246,0.16); border-color: rgba(59,130,246,0.25); color: #c6dbff; }
    .pill.sold { background: rgba(245,158,11,0.16); border-color: rgba(245,158,11,0.25); color: #ffe4b2; }
    .pill.ended { background: rgba(255,255,255,0.09); border-color: rgba(255,255,255,0.14); color: #e2e8f0; }
    .pill.highest { background: rgba(16,185,129,0.2); border-color: rgba(16,185,129,0.34); color: #a7f3d0; }
    .pill.ordered { background: rgba(59,130,246,0.18); border-color: rgba(59,130,246,0.28); color: #c6dbff; }
    .right { display: grid; gap: 8px; justify-items: end; }
    .amount { font-size: 16px; font-weight: 800; }
    .btn {
      border: none;
      border-radius: 10px;
      padding: 10px 12px;
      font-size: 13px;
      font-weight: 800;
      cursor: pointer;
    }
    .btn.checkout { background: linear-gradient(135deg, #10b981, #059669); color: #fff; }
    .btn.live { background: linear-gradient(135deg, #f59e0b, #d97706); color: #1f2937; }
    .empty {
      margin-top: 16px;
      border-radius: 14px;
      border: 1px dashed var(--line);
      background: rgba(255,255,255,0.04);
      padding: 22px;
      color: var(--muted);
      text-align: center;
    }

    .pagination {
      margin-top: 16px;
      display: flex;
      flex-wrap: wrap;
      gap: 8px;
      align-items: center;
      justify-content: center;
    }

    .page-btn {
      min-width: 38px;
      height: 38px;
      border-radius: 10px;
      border: 1px solid var(--line);
      background: rgba(255,255,255,0.06);
      color: #fff;
      font-size: 13px;
      font-weight: 700;
      cursor: pointer;
      padding: 0 12px;
    }

    .page-btn.active {
      background: linear-gradient(135deg, #f59e0b, #d97706);
      border-color: rgba(245,158,11,0.45);
      color: #111827;
    }

    .page-btn:disabled {
      opacity: 0.45;
      cursor: not-allowed;
    }
    .history-tabs {
      display: flex;
      gap: 12px;
      margin-bottom: 20px;
      flex-wrap: wrap;
    }
    .history-tab-btn {
      flex: 1;
      min-width: 140px;
      padding: 12px 16px;
      border: 1px solid var(--line);
      background: rgba(255, 255, 255, 0.06);
      color: var(--muted);
      border-radius: 12px;
      font-size: 14px;
      font-weight: 700;
      cursor: pointer;
      transition: all 0.2s ease;
    }
    .history-tab-btn:hover {
      background: rgba(255, 255, 255, 0.1);
    }
    .history-tab-btn.active {
      background: linear-gradient(135deg, #f59e0b, #d97706);
      border-color: rgba(245, 158, 11, 0.45);
      color: #111827;
    }
    .checkout-modal,
    .checkout-modal .checkout-container,
    .checkout-modal .checkout-header h1,
    .checkout-modal .section-title,
    .checkout-modal label,
    .checkout-modal input,
    .checkout-modal select,
    .checkout-modal .checkout-summary,
    .checkout-modal .price-row,
    .checkout-modal .checkout-terms-consent,
    .checkout-modal .schedule-date-note,
    .checkout-modal .schedule-slot-note,
    .checkout-modal .order-item-name,
    .checkout-modal .order-item-price,
    .checkout-modal .order-item-line-total {
      color: #111;
    }
    .checkout-modal input::placeholder,
    .checkout-modal textarea::placeholder {
      color: #6b7280;
    }
    .checkout-terms-modal,
    .checkout-terms-modal h2,
    .checkout-terms-modal p,
    .checkout-terms-modal label {
      color: #111;
    }
    .checkout-terms-card {
      background: #fff;
    }
    .checkout-terms-card h2 {
      color: #111;
    }
    .checkout-terms-body {
      color: #333;
    }
    .checkout-terms-modal-consent {
      color: #222;
    }
    .swal-overlay {
      z-index: 30010;
    }
    .local-swal-toast {
      z-index: 30020;
    }
    .card {
      cursor: pointer;
      transition: all 0.2s ease;
    }
    .card:hover {
      background: rgba(255, 255, 255, 0.08);
      border-color: rgba(245, 158, 11, 0.35);
      transform: translateY(-2px);
    }
    .bid-modal-overlay {
      position: fixed;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background: rgba(0, 0, 0, 0.7);
      display: none;
      align-items: center;
      justify-content: center;
      z-index: 1000;
      padding: 20px;
    }
    .bid-modal-overlay.show {
      display: flex;
    }
    .bid-modal-content {
      background: var(--panel);
      border: 1px solid var(--line);
      border-radius: 20px;
      max-width: 700px;
      width: 100%;
      max-height: 90vh;
      display: flex;
      flex-direction: column;
      overflow: hidden;
    }
    .bid-modal-header {
      display: flex;
      align-items: center;
      gap: 16px;
      padding: 20px;
      border-bottom: 1px solid var(--line);
    }
    .bid-modal-header img {
      width: 100px;
      height: 100px;
      border-radius: 12px;
      object-fit: cover;
      border: 1px solid var(--line);
    }
    .bid-modal-header .info {
      flex: 1;
    }
    .bid-modal-header .close-btn {
      background: rgba(255, 255, 255, 0.1);
      border: 1px solid var(--line);
      color: #fff;
      border-radius: 8px;
      width: 40px;
      height: 40px;
      cursor: pointer;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 20px;
    }
    .bid-modal-header .close-btn:hover {
      background: rgba(255, 255, 255, 0.15);
    }
    .bid-modal-title {
      margin: 0;
      font-size: 18px;
      font-weight: 800;
      line-height: 1.2;
    }
    .bid-modal-meta {
      color: var(--muted);
      font-size: 12px;
      margin-top: 5px;
      line-height: 1.4;
    }
    .bid-modal-body {
      flex: 1;
      overflow-y: auto;
      padding: 0;
    }
    .bid-modal-section {
      border-bottom: 1px solid var(--line);
    }
    .bid-modal-section:last-child {
      border-bottom: none;
    }
    .bid-modal-section-title {
      padding: 16px 20px 12px;
      font-size: 13px;
      font-weight: 800;
      text-transform: uppercase;
      letter-spacing: 0.06em;
      color: var(--muted);
    }
    .bid-history-item {
      padding: 12px 20px;
      border-bottom: 1px solid rgba(255, 255, 255, 0.06);
      display: grid;
      grid-template-columns: 1fr auto auto;
      gap: 16px;
      align-items: center;
    }
    .bid-history-item:last-child {
      border-bottom: none;
    }
    .bid-history-amount {
      font-weight: 800;
      font-size: 14px;
    }
    .bid-history-time {
      color: var(--muted);
      font-size: 12px;
      white-space: nowrap;
    }
    .bid-history-status {
      display: inline-flex;
      font-size: 11px;
      font-weight: 800;
      padding: 4px 8px;
      border-radius: 6px;
      background: rgba(16, 185, 129, 0.2);
      color: #a7f3d0;
    }
    .bid-modal-footer {
      padding: 16px 20px;
      border-top: 1px solid var(--line);
      display: flex;
      gap: 12px;
    }
    .bid-modal-footer .btn {
      flex: 1;
    }
    @media (max-width: 760px) {
      .card { grid-template-columns: 1fr; }
      .right { justify-items: start; }
      .thumb { width: 100%; height: 180px; }
      .bid-modal-header {
        flex-direction: column;
        text-align: center;
      }
      .bid-modal-header img {
        width: 120px;
        height: 120px;
      }
      .bid-modal-content {
        background: rgba(255,255,255,0.12);
      }
    }
  </style>
</head>
<body>
  <header class="topbar">
    <div class="topbar-inner">
      <a class="link" href="auction.php">Back to Live</a>
      <a class="link" href="user_dashboard.php">Home</a>
    </div>
  </header>

  <main class="wrap">
    <h1 class="title">My Bid History</h1>
    <div class="subtitle">Click on any bid to see all bids for that auction and checkout if you've won.</div>
    
    <div class="history-tabs">
      <button type="button" class="history-tab-btn active" data-history-tab="not-ordered">Not Ordered <span id="notOrderedTabCount"></span></button>
      <button type="button" class="history-tab-btn" data-history-tab="ordered">Ordered <span id="orderedTabCount"></span></button>
    </div>
    
    <div id="historyList" class="list" style="margin-top: 28px;"></div>
    <div id="pagination" class="pagination" style="display:none;"></div>
    <div id="emptyState" class="empty" style="display:none;">No bids found yet.</div>
  </main>

  <!-- Bid Details Modal -->
  <div id="bidModal" class="bid-modal-overlay">
    <div class="bid-modal-content">
      <div class="bid-modal-header">
        <img id="bidModalImage" src="logo.jpg" alt="Auction Item">
        <div class="info">
          <h2 class="bid-modal-title" id="bidModalTitle">Auction Item</h2>
          <div class="bid-modal-meta" id="bidModalMeta">Category · Status</div>
        </div>
        <button type="button" class="close-btn" id="bidModalCloseBtn">✕</button>
      </div>
      <div class="bid-modal-body">
        <div class="bid-modal-section">
          <div class="bid-modal-section-title">Auction Details</div>
          <div style="padding: 12px 20px; color: var(--muted); font-size: 13px; line-height: 1.6;">
            <div>Price: <span id="bidModalPrice" style="color: #fff; font-weight: 800;">N/A</span></div>
            <div>Ends: <span id="bidModalEnds" style="color: #fff;">N/A</span></div>
            <div style="margin-top: 8px;" id="bidModalCheckoutSection"></div>
          </div>
        </div>
        <div class="bid-modal-section">
          <div class="bid-modal-section-title">Bid History</div>
          <div id="bidModalBidList"></div>
        </div>
      </div>
      <div class="bid-modal-footer">
        <button type="button" class="btn live" id="bidModalBackBtn">Back to Live</button>
        <button type="button" class="btn checkout" id="bidModalCheckoutBtn" style="display:none;">Checkout Win</button>
      </div>
    </div>
  </div>

  <?php include __DIR__ . '/partials/user_dashboard/checkout_section.php'; ?>
  <script src="assets/js/user_dashboard_helpers.js?v=20260508-2"></script>
  <script src="assets/js/user_dashboard_recipients.js?v=20260508-2"></script>

  <script>
    let bidRows = [];
    let recipients = [];
    let selectedRecipientId = null;
    let selectedAuction = null;
    let selectedAuctionBidId = 0;
    const pageSize = 5;
    let currentPage = 1;
    let checkoutScheduleSlots = [];
    let availableDeliveryDates = [];
    let checkoutDeliveryType = 'delivery';
    const checkoutDeliveryFee = 38;
    let checkoutScheduleInitDone = false;
    let checkoutSubmitInFlight = false;
    
    // Main history tabs state
    let historyOrderedAuctions = [];
    let historyNotOrderedAuctions = [];
    let currentHistoryOrderedPage = 1;
    let currentHistoryNotOrderedPage = 1;
    let currentHistoryTab = 'not-ordered';
    const historyPageSize = 6;

    async function readJsonResponse(res) {
      const raw = await res.text();
      try {
        return JSON.parse(raw);
      } catch (err) {
        throw new Error('Server returned a non-JSON response.');
      }
    }

    function formatMoney(value) {
      if (value === null || value === undefined || Number.isNaN(Number(value))) return 'N/A';
      return `PHP ${Number(value).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
    }

    function formatDate(raw) {
      if (!raw) return 'Not set';
      const d = new Date(String(raw).replace(' ', 'T'));
      if (Number.isNaN(d.getTime())) return String(raw);
      return d.toLocaleString();
    }

    function showAlert(icon, title, text) {
      if (window.localSwalAlert) {
        return window.localSwalAlert(icon, title, text);
      }
      window.alert(text || title);
      return Promise.resolve();
    }

    function showLocalSweetAlert(type = 'success', title = 'Notice', text = '', duration = 1200) {
      return new Promise((resolve) => {
        const toast = document.createElement('div');
        toast.className = `local-swal-toast ${type}`;
        toast.innerHTML = `<div class="toast-title">${title}</div><div class="toast-text">${text}</div>`;
        document.body.appendChild(toast);
        requestAnimationFrame(() => toast.classList.add('show'));
        setTimeout(() => {
          toast.classList.remove('show');
          setTimeout(() => {
            if (toast.parentNode) toast.parentNode.removeChild(toast);
            resolve(true);
          }, 220);
        }, duration);
      });
    }

    function showLocalConfirmModal(title = 'Confirm', text = '', confirmText = 'Continue', cancelText = 'Cancel') {
      return new Promise((resolve) => {
        const overlay = document.createElement('div');
        overlay.className = 'local-confirm-overlay';

        const card = document.createElement('div');
        card.className = 'local-confirm-card';
        card.innerHTML = `
          <div class="local-confirm-title">${title}</div>
          <div class="local-confirm-text">${text}</div>
          <div class="local-confirm-actions">
            <button type="button" data-role="cancel" class="local-confirm-btn local-confirm-cancel">${cancelText}</button>
            <button type="button" data-role="confirm" class="local-confirm-btn local-confirm-submit">${confirmText}</button>
          </div>
        `;

        const cleanup = (result) => {
          if (overlay.parentNode) {
            overlay.parentNode.removeChild(overlay);
          }
          resolve(result);
        };

        card.querySelector('[data-role="cancel"]').onclick = () => cleanup(false);
        card.querySelector('[data-role="confirm"]').onclick = () => cleanup(true);
        overlay.onclick = (event) => {
          if (event.target === overlay) cleanup(false);
        };

        overlay.appendChild(card);
        document.body.appendChild(overlay);
      });
    }

    function getWinnerCheckoutAmount(detail) {
      if (!detail) return 0;
      if (detail.sold_price !== null && detail.sold_price !== undefined) return Number(detail.sold_price);
      if (detail.current_bid !== null && detail.current_bid !== undefined) return Number(detail.current_bid);
      return Number(detail.bid_amount || 0);
    }

    function peso(value) {
      return `₱${Number(value || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
    }

    function formatPeso(value) {
      const amount = Number(value || 0);
      if (!Number.isFinite(amount)) return '0';
      return amount.toLocaleString('en-PH', { minimumFractionDigits: 0, maximumFractionDigits: 2 });
    }

    function formatTime12Hour(value) {
      if (!value || typeof value !== 'string') return value;
      const parts = value.split(':');
      if (parts.length < 2) return value;
      let hour = parseInt(parts[0], 10);
      const minute = parts[1].padStart(2, '0');
      if (!Number.isFinite(hour)) return value;
      const suffix = hour >= 12 ? 'PM' : 'AM';
      hour = hour % 12;
      if (hour === 0) hour = 12;
      return minute === '00' ? `${hour} ${suffix}` : `${hour}:${minute} ${suffix}`;
    }

    function formatDeliveryDateLabel(dateString) {
      if (!dateString || typeof dateString !== 'string') return dateString;
      const date = new Date(dateString + 'T00:00:00');
      if (Number.isNaN(date.getTime())) return dateString;
      const options = { weekday: 'short', month: 'short', day: 'numeric' };
      return date.toLocaleDateString('en-US', options);
    }

    function setActiveScheduleDate(dateValue) {
      const buttons = document.querySelectorAll('.available-date-button');
      buttons.forEach((btn) => {
        btn.classList.toggle('active', btn.dataset.date === dateValue);
      });
      const dateDropdown = document.getElementById('availableDateDropdown');
      if (dateDropdown) {
        dateDropdown.value = dateValue || '';
      }
    }

    function openCheckout() {
      const modal = document.getElementById('checkoutModal');
      if (!modal) return;
      modal.classList.add('show');
      initCheckoutTermsControls();
      initCheckoutScheduleControls();
      initCheckoutDeliveryControls();
      setCheckoutTermsAccepted(false);
    }

    function closeCheckout() {
      const modal = document.getElementById('checkoutModal');
      if (!modal) return;
      modal.classList.remove('show');
      closeCheckoutTermsModal();
    }

    function setCheckoutTermsAccepted(accepted) {
      const checkboxMain = document.getElementById('checkoutTermsCheckbox');
      const checkboxModal = document.getElementById('checkoutTermsCheckboxModal');
      if (checkboxMain) checkboxMain.checked = Boolean(accepted);
      if (checkboxModal) checkboxModal.checked = Boolean(accepted);
    }

    function isCheckoutTermsAccepted() {
      return Boolean(document.getElementById('checkoutTermsCheckbox')?.checked);
    }

    function openCheckoutTermsModal() {
      const modal = document.getElementById('checkoutTermsModal');
      if (!modal) return;
      modal.classList.add('show');
      modal.setAttribute('aria-hidden', 'false');
      setCheckoutTermsAccepted(isCheckoutTermsAccepted());
    }

    function closeCheckoutTermsModal() {
      const modal = document.getElementById('checkoutTermsModal');
      if (!modal) return;
      modal.classList.remove('show');
      modal.setAttribute('aria-hidden', 'true');
      setCheckoutTermsAccepted(isCheckoutTermsAccepted());
    }

    function initCheckoutTermsControls() {
      const openBtn = document.getElementById('openCheckoutTermsBtn');
      const closeBtn = document.getElementById('closeCheckoutTermsBtn');
      const doneBtn = document.getElementById('checkoutTermsDoneBtn');
      const checkboxMain = document.getElementById('checkoutTermsCheckbox');
      const checkboxModal = document.getElementById('checkoutTermsCheckboxModal');
      const modal = document.getElementById('checkoutTermsModal');

      if (openBtn && !openBtn.dataset.boundTerms) {
        openBtn.dataset.boundTerms = '1';
        openBtn.addEventListener('click', () => {
          showLocalSweetAlert('info', 'Loading', 'Opening Terms & Conditions...', 700).then(() => {
            openCheckoutTermsModal();
          });
        });
      }
      if (closeBtn && !closeBtn.dataset.boundTerms) {
        closeBtn.dataset.boundTerms = '1';
        closeBtn.addEventListener('click', closeCheckoutTermsModal);
      }
      if (doneBtn && !doneBtn.dataset.boundTerms) {
        doneBtn.dataset.boundTerms = '1';
        doneBtn.addEventListener('click', closeCheckoutTermsModal);
      }
      if (checkboxMain && !checkboxMain.dataset.boundTerms) {
        checkboxMain.dataset.boundTerms = '1';
        checkboxMain.addEventListener('change', () => setCheckoutTermsAccepted(checkboxMain.checked));
      }
      if (checkboxModal && !checkboxModal.dataset.boundTerms) {
        checkboxModal.dataset.boundTerms = '1';
        checkboxModal.addEventListener('change', () => setCheckoutTermsAccepted(checkboxModal.checked));
      }
      if (modal && !modal.dataset.boundTerms) {
        modal.dataset.boundTerms = '1';
        modal.addEventListener('click', (event) => {
          if (event.target === modal) {
            closeCheckoutTermsModal();
          }
        });
      }
    }

    function renderWinnerCheckoutItems(detail) {
      const host = document.getElementById('checkoutCartItems');
      const subtotalEl = document.getElementById('checkoutSubtotal');
      const shippingEl = document.getElementById('checkoutShipping');
      const totalEl = document.getElementById('checkoutTotal');
      const amount = getWinnerCheckoutAmount(detail);
      const itemName = detail.item_name || 'Auction Item';
      const image = String(detail.cover_image || 'logo.jpg');

      host.innerHTML = `
        <div class="order-item" data-id="${String(detail.auction_id || '')}">
          <img src="${image}" alt="${itemName}" class="order-item-image">
          <div class="order-item-info">
            <div class="order-item-name">${itemName}</div>
            <div class="order-item-price">${peso(amount)}</div>
            <div class="checkout-qty-row">
              <button type="button" class="qty-adj-btn minus" disabled>-</button>
              <span class="checkout-qty">1</span>
              <button type="button" class="qty-adj-btn plus" disabled>+</button>
            </div>
            <div class="order-item-line-total">${peso(amount)}</div>
          </div>
        </div>
      `;

      subtotalEl.textContent = peso(amount);
      updateDeliveryFee();
    }

    async function initCheckoutScheduleControls() {
      if (checkoutScheduleInitDone) return;
      checkoutScheduleInitDone = true;

      const dateInput = document.getElementById('scheduleDate');
      const slotSelect = document.getElementById('scheduleSlot');
      const slotNote = document.getElementById('scheduleSlotNote');

      if (!dateInput || !slotSelect) return;

      await loadAvailableDeliveryDates();

      dateInput.addEventListener('change', (e) => {
        const selectedDate = e.target.value;
        setActiveScheduleDate(selectedDate);
        if (selectedDate) {
          loadAvailableSlots(selectedDate);
        } else {
          slotSelect.disabled = true;
          slotSelect.innerHTML = '<option value="">Select Date First</option>';
          if (slotNote) {
            slotNote.textContent = 'Select a date to see available time slots.';
          }
        }
      });
    }

    async function loadAvailableDeliveryDates() {
      const buttonsContainer = document.getElementById('availableDateButtons');
      const dropdown = document.getElementById('availableDateDropdown');
      const dateNote = document.getElementById('scheduleDateNote');
      const dateInput = document.getElementById('scheduleDate');
      if (!buttonsContainer || !dropdown || !dateNote || !dateInput) return;

      buttonsContainer.innerHTML = '';
      buttonsContainer.style.display = 'flex';
      dropdown.innerHTML = '<option value="">Loading dates...</option>';
      dropdown.hidden = true;
      dateNote.textContent = 'Loading available delivery dates...';

      try {
        const res = await fetch('api/get-delivery-dates.php');
        if (!res.ok) throw new Error('Failed to load available dates');
        const data = await res.json();
        const dates = Array.isArray(data.dates) ? data.dates : [];

        if (dates.length === 0) {
          buttonsContainer.innerHTML = '<div class="available-date-empty">No available dates</div>';
          buttonsContainer.style.display = 'block';
          dateNote.textContent = 'No delivery dates are currently available. Please try again later.';
          dropdown.innerHTML = '<option value="">No available dates</option>';
          dropdown.hidden = false;
          await checkoutLocalAlert('warning', 'No Available Delivery Dates', 'There are no available delivery dates at this time. Please try again later.');
          return;
        }

        dateNote.textContent = `Choose from ${dates.length} available delivery date${dates.length === 1 ? '' : 's'}.`;

        const currentValue = dateInput.value;
        const firstDate = dates[0].date;
        const isCurrentValid = currentValue && dates.some((d) => d.date === currentValue);
        if (!isCurrentValid) {
          dateInput.value = '';
        }
        dateInput.min = firstDate;
        dateInput.max = dates[dates.length - 1].date;
        setActiveScheduleDate(dateInput.value);

        availableDeliveryDates = dates.map((d) => d.date);

        if (window.flatpickrInstance) {
          window.flatpickrInstance.destroy();
        }
        if (window.flatpickr) {
          window.flatpickrInstance = window.flatpickr(dateInput, {
            enable: availableDeliveryDates,
            dateFormat: 'Y-m-d',
            minDate: firstDate,
            maxDate: dates[dates.length - 1].date,
            defaultDate: dateInput.value,
            onChange: (selectedDates, dateStr) => {
              setActiveScheduleDate(dateStr);
              loadAvailableSlots(dateStr);
            }
          });
        }

        buttonsContainer.innerHTML = '';
        buttonsContainer.style.display = 'flex';
        dropdown.innerHTML = '<option value="">Select a delivery date</option>';
        const showDropdown = dates.length > 3;

        dates.slice(0, 3).forEach((slotDate) => {
          const button = document.createElement('button');
          button.type = 'button';
          button.className = 'available-date-button';
          button.textContent = `${formatDeliveryDateLabel(slotDate.date)} (${slotDate.open_slots} open)`;
          button.dataset.date = slotDate.date;
          button.disabled = slotDate.open_slots <= 0;
          if (dateInput.value === slotDate.date) {
            button.classList.add('active');
          }
          button.addEventListener('click', () => {
            dateInput.value = slotDate.date;
            if (window.flatpickrInstance) {
              window.flatpickrInstance.setDate(slotDate.date);
            }
            setActiveScheduleDate(slotDate.date);
            loadAvailableSlots(slotDate.date);
          });
          buttonsContainer.appendChild(button);
        });

        if (showDropdown) {
          dropdown.hidden = false;
          dates.slice(3).forEach((slotDate) => {
            const option = document.createElement('option');
            option.value = slotDate.date;
            option.textContent = `${formatDeliveryDateLabel(slotDate.date)} (${slotDate.open_slots} open)`;
            dropdown.appendChild(option);
          });
          dropdown.value = '';
          dropdown.onchange = (event) => {
            const selectedDate = event.target.value;
            if (!selectedDate) return;
            dateInput.value = selectedDate;
            if (window.flatpickrInstance) {
              window.flatpickrInstance.setDate(selectedDate);
            }
            setActiveScheduleDate(selectedDate);
            loadAvailableSlots(selectedDate);
          };
        } else {
          dropdown.hidden = true;
        }

        loadAvailableSlots(dateInput.value);
      } catch (err) {
        console.error('loadAvailableDeliveryDates error:', err);
        dateNote.textContent = 'Unable to load delivery dates. Please refresh or try again later.';
        dropdown.innerHTML = '<option value="">Error loading dates</option>';
        dropdown.hidden = false;
      }
    }

    async function loadAvailableSlots(date) {
      const slotSelect = document.getElementById('scheduleSlot');
      const slotNote = document.getElementById('scheduleSlotNote');

      try {
        slotSelect.disabled = true;
        slotSelect.innerHTML = '<option value="">Loading...</option>';

        const res = await fetch(`api/get-delivery-slots.php?date=${encodeURIComponent(date)}`);
        if (!res.ok) throw new Error('Failed to load slots');

        const data = await res.json();
        checkoutScheduleSlots = data.slots || [];

        if (checkoutScheduleSlots.length === 0) {
          slotSelect.innerHTML = '<option value="">No slots available</option>';
          if (slotNote) {
            slotNote.textContent = 'No time slots available for this date. Please select another date.';
          }
          slotSelect.disabled = true;
          await checkoutLocalAlert('warning', 'No Available Time Slots', 'There are no available time slots for the selected date. Please choose a different date.');
        } else {
          slotSelect.innerHTML = '<option value="">Select Time Slot</option>';
          checkoutScheduleSlots.forEach((slot) => {
            const option = document.createElement('option');
            option.value = slot.time;
            option.textContent = `${formatTime12Hour(slot.time)} (${slot.available}/${slot.capacity} available)`;
            option.disabled = slot.available <= 0;
            slotSelect.appendChild(option);
          });
          slotSelect.disabled = false;
          if (slotNote) {
            slotNote.textContent = `Found ${checkoutScheduleSlots.filter((s) => s.available > 0).length} available time slots.`;
          }
        }
      } catch (err) {
        console.error('loadAvailableSlots error:', err);
        slotSelect.innerHTML = '<option value="">Error loading slots</option>';
        if (slotNote) {
          slotNote.textContent = 'Error loading time slots. Please try again.';
        }
        slotSelect.disabled = true;
      }
    }

    function initCheckoutDeliveryControls() {
      const deliveryRadios = document.querySelectorAll('input[name="deliveryType"]');
      deliveryRadios.forEach((radio) => {
        if (radio.dataset.boundDelivery) return;
        radio.dataset.boundDelivery = '1';
        radio.addEventListener('change', (e) => {
          checkoutDeliveryType = e.target.value;
          updateDeliveryFee();
          const dateInput = document.getElementById('scheduleDate');
          if (dateInput && dateInput.value) {
            loadAvailableSlots(dateInput.value);
          }
        });
      });
    }

    function updateDeliveryFee() {
      const subtotal = parseFloat(String(document.getElementById('checkoutSubtotal')?.textContent || '').replace(/[^0-9.]/g, '')) || 0;
      const shipping = checkoutDeliveryType === 'delivery' ? checkoutDeliveryFee : 0;
      const total = subtotal + shipping;

      const shippingEl = document.getElementById('checkoutShipping');
      const totalEl = document.getElementById('checkoutTotal');
      if (shippingEl) shippingEl.textContent = shipping === 0 ? 'FREE' : `₱${formatPeso(shipping)}`;
      if (totalEl) totalEl.textContent = `₱${formatPeso(total)}`;
    }

    function showCheckoutLocalSweetAlert(options) {
      const overlay = document.getElementById('checkoutLocalSwal');
      const icon = document.getElementById('checkoutLocalSwalIcon');
      const titleEl = document.getElementById('checkoutLocalSwalTitle');
      const textEl = document.getElementById('checkoutLocalSwalText');
      const actions = document.getElementById('checkoutLocalSwalActions');
      const confirmBtn = document.getElementById('checkoutLocalSwalConfirm');
      const cancelBtn = document.getElementById('checkoutLocalSwalCancel');
      if (!overlay || !icon || !titleEl || !textEl || !actions || !confirmBtn || !cancelBtn) {
        return;
      }

      const type = options.type || 'success';
      const hasCancel = !!options.showCancel;

      icon.className = `swal-icon ${type}`;
      icon.textContent = type === 'error' ? '!' : (type === 'warning' ? '⚠' : '✓');
      titleEl.textContent = options.title || 'Notice';
      textEl.textContent = options.text || '';

      confirmBtn.textContent = options.confirmText || 'OK';
      cancelBtn.textContent = options.cancelText || 'Cancel';
      cancelBtn.style.display = hasCancel ? 'block' : 'none';
      actions.style.gridTemplateColumns = hasCancel ? '1fr 1fr' : 'auto';

      confirmBtn.onclick = () => {
        overlay.classList.remove('show');
        if (typeof options.onConfirm === 'function') {
          options.onConfirm();
        }
      };

      cancelBtn.onclick = () => {
        overlay.classList.remove('show');
        if (typeof options.onCancel === 'function') {
          options.onCancel();
        }
      };

      overlay.classList.add('show');
    }

    function checkoutLocalAlert(type, title, text) {
      return new Promise((resolve) => {
        showCheckoutLocalSweetAlert({
          type,
          title,
          text,
          confirmText: 'OK',
          onConfirm: () => resolve(true)
        });
      });
    }

    function checkoutLocalConfirm(title, text, confirmText = 'Yes', cancelText = 'Cancel') {
      return new Promise((resolve) => {
        showCheckoutLocalSweetAlert({
          type: 'warning',
          title,
          text,
          showCancel: true,
          confirmText,
          cancelText,
          onConfirm: () => resolve(true),
          onCancel: () => resolve(false)
        });
      });
    }

    async function handleCheckoutClick() {
      if (checkoutSubmitInFlight) {
        return;
      }
      checkoutSubmitInFlight = true;
      const detail = selectedAuction;
      const placeOrderBtn = document.getElementById('placeOrderBtn');
      const deliveryTypeRadio = document.querySelector('input[name="deliveryType"]:checked');
      const scheduleDate = document.getElementById('scheduleDate')?.value || '';
      const scheduleSlot = document.getElementById('scheduleSlot')?.value || '';
      const recipientRadio = document.querySelector('input[name="recipient"]:checked');
      const resolvedRecipientId = Number(selectedRecipientId || recipientRadio?.value || 0);

      if (!detail || !detail.auction_id) {
        showAlert('error', 'Checkout Not Available', 'Winner checkout is not available for this auction.');
        checkoutSubmitInFlight = false;
        return;
      }
      if (!deliveryTypeRadio) {
        await checkoutLocalAlert('warning', 'Delivery Required', 'Please select pickup or delivery.');
        checkoutSubmitInFlight = false;
        return;
      }
      if (!scheduleDate) {
        await checkoutLocalAlert('warning', 'No Date Selected', 'Please select a delivery/pickup date from the available options.');
        checkoutSubmitInFlight = false;
        return;
      }
      if (!scheduleSlot) {
        await checkoutLocalAlert('warning', 'No Time Slot Selected', 'Please select a time slot for your order.');
        checkoutSubmitInFlight = false;
        return;
      }
      if (!isCheckoutTermsAccepted()) {
        showLocalSweetAlert('warning', 'Terms & Conditions', 'Please agree to the Terms & Conditions before placing your order.', 1700).then(() => {
          openCheckoutTermsModal();
        });
        checkoutSubmitInFlight = false;
        return;
      }
      if (!Number.isFinite(resolvedRecipientId) || resolvedRecipientId <= 0) {
        showAlert('warning', 'Recipient Required', 'Please select a recipient before placing order.');
        checkoutSubmitInFlight = false;
        return;
      }

      placeOrderBtn.disabled = true;
      placeOrderBtn.classList.add('loading');
      placeOrderBtn.innerHTML = '<span class="loading-spinner"></span> Processing...';
      try {
        const body = new URLSearchParams();
        body.set('auction_id', String(detail.auction_id || ''));
        body.set('bid_id', String(selectedAuctionBidId || 0));
        body.set('recipient_id', String(resolvedRecipientId));
        body.set('payment_method', 'cash');
        body.set('delivery_type', deliveryTypeRadio.value);
        body.set('schedule_date', scheduleDate);
        body.set('schedule_slot', scheduleSlot);

        const res = await fetch('api/checkout-auction.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
          body: body.toString()
        });
        const data = await readJsonResponse(res);
        if (!res.ok || !data.success) {
          throw new Error(data.error || 'Auction checkout failed');
        }

        document.getElementById('successOrderId').textContent = String(data.order_id || '0');
        document.getElementById('successModal').classList.add('show');

        setTimeout(() => {
          closeCheckout();
        }, 500);

        await loadHistory();
      } catch (err) {
        showAlert('error', 'Checkout Failed', String(err.message || 'Unable to checkout this auction.'));
      } finally {
        placeOrderBtn.disabled = false;
        placeOrderBtn.classList.remove('loading');
        placeOrderBtn.innerHTML = '<span class="btn-icon">✓</span> Place Order';
        checkoutSubmitInFlight = false;
      }
    }

    function closeSuccessModal() {
      const modal = document.getElementById('successModal');
      if (modal) modal.classList.remove('show');
    }

    async function beginCheckout(row) {
      selectedAuction = row;
      selectedAuctionBidId = Number(row.bid_id || 0);
      renderWinnerCheckoutItems(row);
      if (typeof loadRecipients === 'function') {
        await loadRecipients();
      }
      openCheckout();
    }

    function openBidModal(auctionId) {
      const bidModal = document.getElementById('bidModal');
      if (bidModal) {
        bidModal.classList.add('show');
        loadBidModalData(auctionId);
      }
    }

    function closeBidModal() {
      const bidModal = document.getElementById('bidModal');
      if (bidModal) {
        bidModal.classList.remove('show');
      }
    }

    async function loadBidModalData(auctionId) {
      try {
        // Find the first bid row for this auction to get basic info
        const baseBid = bidRows.find(r => Number(r.auction_id) === Number(auctionId));
        if (!baseBid) {
          throw new Error('Auction not found');
        }

        // Fetch full auction details from API
        const detailRes = await fetch(`api/get-auction-detail.php?auction_id=${encodeURIComponent(auctionId)}`, { cache: 'no-store' });
        const detailData = await readJsonResponse(detailRes);
        const detail = detailData.detail || baseBid;

        // Get all bids for this auction
        const allBidsForAuction = bidRows.filter(r => Number(r.auction_id) === Number(auctionId));



        // Update modal header
        document.getElementById('bidModalImage').src = String(detail.cover_image || 'logo.jpg');
        document.getElementById('bidModalImage').alt = String(detail.item_name || 'Auction');
        document.getElementById('bidModalTitle').textContent = String(detail.item_name || 'Auction Item');
        document.getElementById('bidModalMeta').textContent = `${String(detail.category_name || 'No Category')} · ${String(detail.auction_status || 'Scheduled')}`;

        // Update auction details
        const soldPrice = detail.sold_price !== null && detail.sold_price !== undefined ? Number(detail.sold_price) : null;
        const currentBid = detail.current_bid !== null && detail.current_bid !== undefined ? Number(detail.current_bid) : 0;
        const displayPrice = soldPrice !== null ? soldPrice : currentBid;
        document.getElementById('bidModalPrice').textContent = peso(displayPrice);
        document.getElementById('bidModalEnds').textContent = formatDate(detail.end_at);

        // Update checkout button visibility
        const isWinner = Boolean(detail.is_winner);
        const isSold = String(detail.auction_status || '').toLowerCase() === 'sold';
        const notCheckedOut = !Boolean(detail.checked_out);
        const checkoutSection = document.getElementById('bidModalCheckoutSection');
        const checkoutBtn = document.getElementById('bidModalCheckoutBtn');

        if (isWinner && isSold && notCheckedOut) {
          checkoutBtn.style.display = 'block';
          checkoutBtn.onclick = () => {
            closeBidModal();
            beginCheckout(baseBid);
          };
          checkoutSection.innerHTML = '<span class="pill highest">Highest Bid</span>';
        } else if (isWinner && isSold && !notCheckedOut) {
          checkoutBtn.style.display = 'none';
          checkoutSection.innerHTML = '<span class="pill ordered">Ordered</span>';
        } else {
          checkoutBtn.style.display = 'none';
          checkoutSection.innerHTML = '';
        }

        // Render bid history for this auction
        const bidList = document.getElementById('bidModalBidList');
        bidList.innerHTML = '';

        allBidsForAuction.sort((a, b) => {
          const timeA = new Date(String(a.created_at || '')).getTime();
          const timeB = new Date(String(b.created_at || '')).getTime();
          return timeB - timeA; // Most recent first
        });

        allBidsForAuction.forEach((bid) => {
          const item = document.createElement('div');
          item.className = 'bid-history-item';
          const isHighest = Boolean(bid.is_highest_bid_record);
          item.innerHTML = `
            <div>
              <div style="font-size: 12px; color: var(--muted);">
                ${isHighest ? '<span style="color: #a7f3d0; font-weight: 800;">Highest • </span>' : ''}
                ${formatDate(bid.created_at)}
              </div>
            </div>
            <div class="bid-history-amount">${peso(Number(bid.bid_amount || 0))}</div>
            <div class="bid-history-status">${isHighest ? 'HIGHEST' : 'BID'}</div>
          `;
          bidList.appendChild(item);
        });
      } catch (err) {
        console.error('loadBidModalData error:', err);
        showAlert('error', 'Load Failed', String(err.message || 'Unable to load auction details'));
        closeBidModal();
      }
    }

    function renderHistory(rows) {
      const list = document.getElementById('historyList');
      const empty = document.getElementById('emptyState');
      list.innerHTML = '';

      if (!Array.isArray(rows) || rows.length === 0) {
        empty.style.display = 'block';
        return;
      }
      empty.style.display = 'none';

      rows.forEach((row) => {
        const card = document.createElement('article');
        card.className = 'card';
        
        // Make card clickable
        card.style.cursor = 'pointer';
        card.addEventListener('click', () => {
          openBidModal(Number(row.auction_id || 0));
        });

        const thumb = document.createElement('img');
        thumb.className = 'thumb';
        thumb.src = String(row.cover_image || 'logo.jpg');
        thumb.alt = row.item_name || 'Auction';
        thumb.style.pointerEvents = 'none'; // Allow click to propagate

        const middle = document.createElement('div');
        const statusClass = String(row.auction_status || '').toLowerCase();
        const auctionId = Number(row.auction_id || 0);
        const isHighestBidRow = Boolean(row.is_highest_bid_record);
        const isOrdered = Boolean(row.checked_out);
        middle.innerHTML = `
          <div>
            <span class="pill ${statusClass}">${String(row.auction_status || 'scheduled')}</span>
            <span class="pill">${String(row.bid_status || 'valid')}</span>
            ${isHighestBidRow ? '<span class="pill highest">Highest Bid</span>' : ''}
            ${isHighestBidRow && isOrdered ? '<span class="pill ordered">Ordered</span>' : ''}
          </div>
          <h3 class="meta-title">${String(row.item_name || 'Auction Item')}</h3>
          <div class="meta-line">Category: ${String(row.category_name || 'No Category')}</div>
          <div class="meta-line">Your bid: ${formatMoney(row.bid_amount)} · ${formatDate(row.created_at)}</div>
          <div class="meta-line">Ends: ${formatDate(row.end_at)}</div>
        `;
        middle.style.pointerEvents = 'none';

        const right = document.createElement('div');
        right.className = 'right';
        right.style.pointerEvents = 'none';

        const amount = document.createElement('div');
        amount.className = 'amount';
        const bidAmountLabel = formatMoney(row.bid_amount);
        amount.textContent = row.sold_price !== null && row.sold_price !== undefined
          ? (row.is_winner ? `Winning bid ${bidAmountLabel}` : `Your bid ${bidAmountLabel}`)
          : `Your bid ${bidAmountLabel}`;
        right.appendChild(amount);

        card.appendChild(thumb);
        card.appendChild(middle);
        card.appendChild(right);
        list.appendChild(card);
      });
    }

    function getUniqueAuctionBids() {
      // Group bids by auction and get the highest bid for each
      const auctionMap = {};
      
      if (Array.isArray(bidRows)) {
        bidRows.forEach((row) => {
          const auctionId = Number(row.auction_id || 0);
          if (!auctionId) return;
          
          if (!auctionMap[auctionId]) {
            auctionMap[auctionId] = row;
          } else {
            // Keep the highest bid
            const current = auctionMap[auctionId];
            const currentAmount = Number(current.bid_amount || 0);
            const rowAmount = Number(row.bid_amount || 0);
            if (rowAmount > currentAmount) {
              auctionMap[auctionId] = row;
            }
          }
        });
      }
      
      // Convert to array and sort by most recent
      return Object.values(auctionMap).sort((a, b) => {
        const timeA = new Date(String(a.created_at || '')).getTime();
        const timeB = new Date(String(b.created_at || '')).getTime();
        return timeB - timeA;
      });
    }

    function switchHistoryTab(tabName) {
      // Update tab buttons
      const tabBtns = document.querySelectorAll('.history-tab-btn');
      tabBtns.forEach((btn) => {
        btn.classList.toggle('active', btn.dataset.historyTab === tabName);
      });

      currentHistoryTab = tabName;
      currentHistoryOrderedPage = 1;
      currentHistoryNotOrderedPage = 1;
      renderHistoryPage();
    }

    function renderHistoryPage() {
      const uniqueAuctions = getUniqueAuctionBids();
      
      // Separate into ordered and not ordered
      historyOrderedAuctions = uniqueAuctions.filter(row => Boolean(row.checked_out));
      historyNotOrderedAuctions = uniqueAuctions.filter(row => !Boolean(row.checked_out));

      // Update tab count badges
      document.getElementById('orderedTabCount').textContent = `(${historyOrderedAuctions.length})`;
      document.getElementById('notOrderedTabCount').textContent = `(${historyNotOrderedAuctions.length})`;

      // Get appropriate data based on current tab
      const currentData = currentHistoryTab === 'ordered' ? historyOrderedAuctions : historyNotOrderedAuctions;
      const currentPageVar = currentHistoryTab === 'ordered' ? currentHistoryOrderedPage : currentHistoryNotOrderedPage;
      const totalItems = currentData.length;
      const totalPages = Math.max(1, Math.ceil(totalItems / historyPageSize));
      
      // Validate current page
      const validPage = Math.min(Math.max(currentPageVar, 1), totalPages);
      if (currentHistoryTab === 'ordered') {
        currentHistoryOrderedPage = validPage;
      } else {
        currentHistoryNotOrderedPage = validPage;
      }

      // Paginate
      const start = (validPage - 1) * historyPageSize;
      const end = start + historyPageSize;
      const pageRows = currentData.slice(start, end);

      renderHistory(pageRows);
      renderHistoryPagination(totalItems, validPage);
    }

    function renderHistoryPagination(totalItems, currentPageNum) {
      const host = document.getElementById('pagination');
      host.innerHTML = '';

      if (!totalItems || totalItems === 0) {
        host.style.display = 'none';
        return;
      }

      const totalPages = Math.max(1, Math.ceil(totalItems / historyPageSize));
      host.style.display = totalPages > 1 ? 'flex' : 'none';
      if (totalPages <= 1) return;

      const prevBtn = document.createElement('button');
      prevBtn.type = 'button';
      prevBtn.className = 'page-btn';
      prevBtn.textContent = 'Prev';
      prevBtn.disabled = currentPageNum <= 1;
      prevBtn.addEventListener('click', () => {
        if (currentHistoryTab === 'ordered') {
          if (currentHistoryOrderedPage > 1) {
            currentHistoryOrderedPage -= 1;
            renderHistoryPage();
          }
        } else {
          if (currentHistoryNotOrderedPage > 1) {
            currentHistoryNotOrderedPage -= 1;
            renderHistoryPage();
          }
        }
      });
      host.appendChild(prevBtn);

      for (let page = 1; page <= totalPages; page += 1) {
        const pageBtn = document.createElement('button');
        pageBtn.type = 'button';
        pageBtn.className = `page-btn${page === currentPageNum ? ' active' : ''}`;
        pageBtn.textContent = String(page);
        pageBtn.addEventListener('click', () => {
          if (currentHistoryTab === 'ordered') {
            currentHistoryOrderedPage = page;
          } else {
            currentHistoryNotOrderedPage = page;
          }
          renderHistoryPage();
        });
        host.appendChild(pageBtn);
      }

      const nextBtn = document.createElement('button');
      nextBtn.type = 'button';
      nextBtn.className = 'page-btn';
      nextBtn.textContent = 'Next';
      nextBtn.disabled = currentPageNum >= totalPages;
      nextBtn.addEventListener('click', () => {
        const totalPages = Math.max(1, Math.ceil(totalItems / historyPageSize));
        if (currentHistoryTab === 'ordered') {
          if (currentHistoryOrderedPage < totalPages) {
            currentHistoryOrderedPage += 1;
            renderHistoryPage();
          }
        } else {
          if (currentHistoryNotOrderedPage < totalPages) {
            currentHistoryNotOrderedPage += 1;
            renderHistoryPage();
          }
        }
      });
      host.appendChild(nextBtn);
    }

    function computeAuctionStats() {
      // This is now handled by the API - each bid has is_highest_bid_record flag
    }

    async function loadHistory() {
      try {
        const res = await fetch('api/get-user-bids.php', { cache: 'no-store' });
        const data = await readJsonResponse(res);
        if (!res.ok || !data.success) {
          throw new Error(data.error || 'Failed to load bid history');
        }

        bidRows = Array.isArray(data.bids) ? data.bids : [];
        currentPage = 1;
        renderHistoryPage();
      } catch (err) {
        const list = document.getElementById('historyList');
        list.innerHTML = `<div class="empty">${String(err.message || 'Unable to load history')}</div>`;
        document.getElementById('pagination').style.display = 'none';
        showAlert('error', 'Load Failed', String(err.message || 'Unable to load history'));
      }
    }

    document.getElementById('checkoutModal').addEventListener('click', (event) => {
      if (event.target && event.target.id === 'checkoutModal') {
        closeCheckout();
      }
    });

    // Modal controls
    const bidModal = document.getElementById('bidModal');
    const bidModalCloseBtn = document.getElementById('bidModalCloseBtn');
    const bidModalBackBtn = document.getElementById('bidModalBackBtn');

    if (bidModalCloseBtn) {
      bidModalCloseBtn.addEventListener('click', closeBidModal);
    }

    if (bidModalBackBtn) {
      bidModalBackBtn.addEventListener('click', () => {
        window.location.href = 'auction.php';
      });
    }

    if (bidModal) {
      bidModal.addEventListener('click', (event) => {
        if (event.target === bidModal) {
          closeBidModal();
        }
      });
    }

    document.getElementById('placeOrderBtn')?.removeEventListener('click', handleCheckoutClick);
    initCheckoutTermsControls();

    // History tab buttons
    const historyTabBtns = document.querySelectorAll('.history-tab-btn');
    historyTabBtns.forEach((btn) => {
      btn.addEventListener('click', () => {
        switchHistoryTab(btn.dataset.historyTab);
      });
    });

    loadHistory();
  </script>
</body>
</html>
