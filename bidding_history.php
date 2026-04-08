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
  <link rel="stylesheet" href="assets/css/local_swal.css">
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
    .modal-overlay {
      position: fixed;
      inset: 0;
      z-index: 50;
      display: none;
      align-items: center;
      justify-content: center;
      padding: 16px;
      background: rgba(0, 0, 0, 0.62);
      backdrop-filter: blur(8px);
    }
    .modal-overlay.open { display: flex; }
    .modal {
      width: min(620px, 100%);
      border-radius: 16px;
      border: 1px solid var(--line);
      background: #0b111d;
      overflow: hidden;
    }
    .modal-head {
      display: flex;
      justify-content: space-between;
      align-items: center;
      gap: 12px;
      padding: 14px;
      border-bottom: 1px solid var(--line);
    }
    .modal-body { padding: 14px; display: grid; gap: 12px; }
    .modal-close {
      width: 38px;
      height: 38px;
      border-radius: 10px;
      border: 1px solid var(--line);
      background: var(--panel);
      color: #fff;
      font-size: 19px;
      cursor: pointer;
    }
    .select {
      width: 100%;
      border-radius: 12px;
      border: 1px solid var(--line);
      background: rgba(255,255,255,0.05);
      color: #fff;
      padding: 11px;
      font-size: 14px;
    }
    .summary {
      border: 1px solid var(--line);
      border-radius: 12px;
      background: rgba(255,255,255,0.05);
      padding: 12px;
      display: grid;
      gap: 8px;
      font-size: 14px;
    }
    .row { display: flex; justify-content: space-between; gap: 10px; }
    .row.total { border-top: 1px solid var(--line); padding-top: 7px; font-weight: 800; }
    .message {
      border: 1px solid var(--line);
      border-radius: 10px;
      padding: 10px;
      background: rgba(255,255,255,0.04);
      color: var(--muted);
      font-size: 13px;
    }
    @media (max-width: 760px) {
      .card { grid-template-columns: 1fr; }
      .right { justify-items: start; }
      .thumb { width: 100%; height: 180px; }
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
    <div class="subtitle">This page shows your personal bid timeline. Multiple bids per auction are preserved, and won auctions can be checked out here.</div>
    <div id="historyList" class="list"></div>
    <div id="pagination" class="pagination" style="display:none;"></div>
    <div id="emptyState" class="empty" style="display:none;">No bids found yet.</div>
  </main>

  <div id="checkoutModal" class="modal-overlay" aria-hidden="true">
    <div class="modal" role="dialog" aria-modal="true" aria-labelledby="checkoutTitle">
      <div class="modal-head">
        <h2 id="checkoutTitle" style="margin:0;font-size:18px;">Checkout Your Win</h2>
        <button class="modal-close" type="button" id="closeCheckoutBtn">&times;</button>
      </div>
      <div class="modal-body">
        <select id="recipientSelect" class="select">
          <option value="">Loading recipients...</option>
        </select>
        <div class="summary">
          <div class="row"><span>Item</span><span id="checkoutItem">Auction Item</span></div>
          <div class="row"><span>Subtotal</span><span id="checkoutSubtotal">PHP 0.00</span></div>
          <div class="row"><span>Shipping</span><span>FREE</span></div>
          <div class="row total"><span>Total</span><span id="checkoutTotal">PHP 0.00</span></div>
        </div>
        <div id="checkoutMessage" class="message">Select recipient and place order.</div>
        <button id="placeOrderBtn" class="btn checkout" type="button">Place Order</button>
      </div>
    </div>
  </div>

  <script>
    let bidRows = [];
    let recipients = [];
    let selectedAuction = null;
    let selectedAuctionBidId = 0;
    const pageSize = 5;
    let currentPage = 1;

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

    function openCheckoutModal() {
      const modal = document.getElementById('checkoutModal');
      modal.classList.add('open');
      modal.setAttribute('aria-hidden', 'false');
      document.body.style.overflow = 'hidden';
    }

    function closeCheckoutModal() {
      const modal = document.getElementById('checkoutModal');
      modal.classList.remove('open');
      modal.setAttribute('aria-hidden', 'true');
      document.body.style.overflow = '';
    }

    async function loadRecipients() {
      const select = document.getElementById('recipientSelect');
      const placeOrderBtn = document.getElementById('placeOrderBtn');
      const message = document.getElementById('checkoutMessage');

      try {
        const res = await fetch('api/get-recipients.php', { cache: 'no-store' });
        const data = await readJsonResponse(res);
        if (!res.ok || !Array.isArray(data.recipients)) {
          throw new Error(data.error || 'Failed to load recipients');
        }

        recipients = data.recipients;
        select.innerHTML = '';
        if (recipients.length === 0) {
          select.innerHTML = '<option value="">No recipients found</option>';
          message.textContent = 'No recipient found. Add one from your account first.';
          placeOrderBtn.disabled = true;
          return;
        }

        recipients.forEach((recipient) => {
          const option = document.createElement('option');
          option.value = String(recipient.recipient_id || '');
          const city = String(recipient.city || '').trim();
          const region = String(recipient.region || '').trim();
          const suffix = [city, region].filter(Boolean).join(', ');
          option.textContent = `${String(recipient.recipient_name || 'Recipient')}${suffix ? ` | ${suffix}` : ''}`;
          if (recipient.is_default) option.selected = true;
          select.appendChild(option);
        });

        if (!Array.from(select.options).some((o) => o.selected)) {
          select.selectedIndex = 0;
        }

        placeOrderBtn.disabled = false;
        message.textContent = 'Select recipient and place order.';
      } catch (err) {
        select.innerHTML = '<option value="">Unable to load recipients</option>';
        placeOrderBtn.disabled = true;
        message.textContent = String(err.message || 'Unable to load recipients.');
        showAlert('error', 'Recipients Error', String(err.message || 'Unable to load recipients.'));
      }
    }

    async function beginCheckout(row) {
      selectedAuction = row;
      selectedAuctionBidId = Number(row.bid_id || 0);
      const amount = row.sold_price !== null && row.sold_price !== undefined
        ? Number(row.sold_price)
        : Number(row.current_bid !== null ? row.current_bid : row.bid_amount);
      document.getElementById('checkoutItem').textContent = row.item_name || 'Auction Item';
      document.getElementById('checkoutSubtotal').textContent = formatMoney(amount);
      document.getElementById('checkoutTotal').textContent = formatMoney(amount);
      await loadRecipients();
      openCheckoutModal();
    }

    async function placeWinnerOrder() {
      const message = document.getElementById('checkoutMessage');
      const button = document.getElementById('placeOrderBtn');
      const recipientId = Number(document.getElementById('recipientSelect').value || 0);

      if (!selectedAuction || !selectedAuction.auction_id) {
        message.textContent = 'Auction selection is missing.';
        showAlert('error', 'Missing Auction', 'Auction selection is missing.');
        return;
      }

      if (!Number.isFinite(recipientId) || recipientId <= 0) {
        message.textContent = 'Select a valid recipient before placing order.';
        showAlert('warning', 'Recipient Required', 'Select a valid recipient before placing order.');
        return;
      }

      button.disabled = true;
      message.textContent = 'Placing order...';
      try {
        const payload = new URLSearchParams();
        payload.set('auction_id', String(selectedAuction.auction_id));
        payload.set('bid_id', String(selectedAuctionBidId || 0));
        payload.set('recipient_id', String(recipientId));
        payload.set('payment_method', 'cash');

        const res = await fetch('api/checkout-auction.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
          body: payload.toString()
        });
        const data = await readJsonResponse(res);
        if (!res.ok || !data.success) {
          throw new Error(data.error || 'Auction checkout failed');
        }

        message.textContent = `Order #${data.order_id} placed successfully.`;
        showAlert('success', 'Order Placed', `Order #${data.order_id} placed successfully.`);
        setTimeout(() => {
          closeCheckoutModal();
          window.location.href = 'purchase_history.php';
        }, 700);
      } catch (err) {
        message.textContent = String(err.message || 'Unable to checkout this auction.');
        showAlert('error', 'Checkout Failed', String(err.message || 'Unable to checkout this auction.'));
      } finally {
        button.disabled = false;
      }
    }

    function getPageCount() {
      return Math.max(1, Math.ceil(bidRows.length / pageSize));
    }

    function renderPagination() {
      const host = document.getElementById('pagination');
      host.innerHTML = '';

      if (!Array.isArray(bidRows) || bidRows.length === 0) {
        host.style.display = 'none';
        return;
      }

      const totalPages = getPageCount();
      host.style.display = totalPages > 1 ? 'flex' : 'none';
      if (totalPages <= 1) return;

      const prevBtn = document.createElement('button');
      prevBtn.type = 'button';
      prevBtn.className = 'page-btn';
      prevBtn.textContent = 'Prev';
      prevBtn.disabled = currentPage <= 1;
      prevBtn.addEventListener('click', () => {
        if (currentPage > 1) {
          currentPage -= 1;
          renderHistoryPage();
        }
      });
      host.appendChild(prevBtn);

      for (let page = 1; page <= totalPages; page += 1) {
        const pageBtn = document.createElement('button');
        pageBtn.type = 'button';
        pageBtn.className = `page-btn${page === currentPage ? ' active' : ''}`;
        pageBtn.textContent = String(page);
        pageBtn.addEventListener('click', () => {
          currentPage = page;
          renderHistoryPage();
        });
        host.appendChild(pageBtn);
      }

      const nextBtn = document.createElement('button');
      nextBtn.type = 'button';
      nextBtn.className = 'page-btn';
      nextBtn.textContent = 'Next';
      nextBtn.disabled = currentPage >= totalPages;
      nextBtn.addEventListener('click', () => {
        if (currentPage < totalPages) {
          currentPage += 1;
          renderHistoryPage();
        }
      });
      host.appendChild(nextBtn);
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

        const thumb = document.createElement('img');
        thumb.className = 'thumb';
        thumb.src = String(row.cover_image || 'logo.jpg');
        thumb.alt = row.item_name || 'Auction';

        const middle = document.createElement('div');
        const statusClass = String(row.auction_status || '').toLowerCase();
        const isHighestBidRow = Boolean(row.is_highest_bid_record || row.is_current_highest);
        middle.innerHTML = `
          <div>
            <span class="pill ${statusClass}">${String(row.auction_status || 'scheduled')}</span>
            <span class="pill">${String(row.bid_status || 'valid')}</span>
            ${isHighestBidRow ? '<span class="pill highest">Highest Bid</span>' : ''}
          </div>
          <h3 class="meta-title">${String(row.item_name || 'Auction Item')}</h3>
          <div class="meta-line">Category: ${String(row.category_name || 'No Category')}</div>
          <div class="meta-line">Your bid: ${formatMoney(row.bid_amount)} · ${formatDate(row.created_at)}</div>
          <div class="meta-line">Ends: ${formatDate(row.end_at)}</div>
        `;

        const right = document.createElement('div');
        right.className = 'right';

        const amount = document.createElement('div');
        amount.className = 'amount';
        amount.textContent = row.sold_price !== null && row.sold_price !== undefined
          ? `Result ${formatMoney(row.sold_price)}`
          : `Current ${formatMoney(row.current_bid !== null ? row.current_bid : row.bid_amount)}`;
        right.appendChild(amount);

        if (row.auction_status === 'sold' && row.is_winner && isHighestBidRow && !row.checked_out) {
          const checkoutBtn = document.createElement('button');
          checkoutBtn.className = 'btn checkout';
          checkoutBtn.textContent = 'Checkout Win';
          checkoutBtn.addEventListener('click', () => beginCheckout(row));
          right.appendChild(checkoutBtn);
        } else {
          const liveBtn = document.createElement('button');
          liveBtn.className = 'btn live';
          liveBtn.textContent = 'Back to Live';
          liveBtn.addEventListener('click', () => { window.location.href = 'auction.php'; });
          right.appendChild(liveBtn);
        }

        card.appendChild(thumb);
        card.appendChild(middle);
        card.appendChild(right);
        list.appendChild(card);
      });
    }

    function renderHistoryPage() {
      const totalPages = getPageCount();
      if (currentPage > totalPages) currentPage = totalPages;
      if (currentPage < 1) currentPage = 1;

      const start = (currentPage - 1) * pageSize;
      const end = start + pageSize;
      const pageRows = Array.isArray(bidRows) ? bidRows.slice(start, end) : [];

      renderHistory(pageRows);
      renderPagination();
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

    document.getElementById('closeCheckoutBtn').addEventListener('click', closeCheckoutModal);
    document.getElementById('placeOrderBtn').addEventListener('click', placeWinnerOrder);
    document.getElementById('checkoutModal').addEventListener('click', (event) => {
      if (event.target === event.currentTarget) closeCheckoutModal();
    });

    loadHistory();
  </script>
</body>
</html>
