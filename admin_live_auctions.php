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
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Live Auctions - Admin</title>
  <link rel="stylesheet" href="main.css">
  <link rel="stylesheet" href="assets/css/local_swal.css">
  <script src="assets/js/local_swal.js"></script>
  <style>
    :root {
      --bg: #05060a;
      --panel: rgba(255, 255, 255, 0.06);
      --panel-strong: rgba(255, 255, 255, 0.1);
      --line: rgba(255, 255, 255, 0.12);
      --text: #f4f7fb;
      --muted: rgba(244, 247, 251, 0.72);
      --accent: #fbbf24;
      --danger: #ef4444;
      --warn: #f59e0b;
      --good: #34d399;
      --info: #60a5fa;
      --shadow: 0 24px 80px rgba(0, 0, 0, 0.45);
    }

    * { box-sizing: border-box; }

    html {
      background: var(--bg);
    }

    body {
      margin: 0;
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      background: radial-gradient(1200px 720px at 110% -10%, rgba(251, 191, 36, 0.14), transparent 56%), var(--bg);
      color: var(--text);
      min-height: 100vh;
    }

    body::before {
      content: '';
      position: fixed;
      inset: 0;
      pointer-events: none;
      background-image:
        linear-gradient(rgba(255,255,255,0.03) 1px, transparent 1px),
        linear-gradient(90deg, rgba(255,255,255,0.03) 1px, transparent 1px);
      background-size: 42px 42px;
      opacity: 0.28;
      mask-image: linear-gradient(180deg, rgba(0,0,0,0.9), transparent 90%);
    }

    .page {
      position: relative;
      z-index: 1;
      width: min(1320px, calc(100% - 40px));
      margin: 0 auto;
      padding: 96px 0 28px;
    }

    .topbar {
      position: fixed;
      top: 0;
      left: 0;
      right: 0;
      z-index: 60;
      padding: 12px 0;
      background: rgba(5, 6, 10, 0.76);
      border-bottom: 1px solid var(--line);
      backdrop-filter: blur(20px);
    }

    .topbar-inner {
      width: min(1320px, calc(100% - 40px));
      margin: 0 auto;
      display: flex;
      gap: 10px;
      align-items: center;
      background: rgba(255,255,255,0.04);
      border: 1px solid var(--line);
      border-radius: 14px;
      padding: 10px 12px;
      box-shadow: var(--shadow);
    }

    .back-btn {
      border: 1px solid rgba(255,255,255,0.14);
      background: rgba(255,255,255,0.05);
      color: var(--text);
      width: 38px;
      height: 38px;
      border-radius: 10px;
      font-size: 20px;
      cursor: pointer;
      transition: transform 0.2s ease, border-color 0.2s ease;
    }

    .back-btn:hover {
      transform: translateY(-1px);
      border-color: rgba(251, 191, 36, 0.45);
    }

    .title {
      flex: 1;
      font-size: 20px;
      font-weight: 800;
      letter-spacing: -0.02em;
      color: #f8fafc;
    }

    .new-btn {
      border: 0;
      background: linear-gradient(135deg, #fbbf24, #f59e0b);
      color: #101827;
      border-radius: 10px;
      padding: 10px 14px;
      font-size: 12px;
      font-weight: 800;
      cursor: pointer;
      box-shadow: 0 14px 30px rgba(251, 191, 36, 0.26);
      transition: transform 0.2s ease;
    }

    .new-btn:hover {
      transform: translateY(-1px);
    }

    .toolbar {
      margin-top: 12px;
      display: flex;
      gap: 10px;
      align-items: center;
      flex-wrap: wrap;
      background: rgba(255,255,255,0.04);
      border: 1px solid var(--line);
      border-radius: 14px;
      padding: 10px;
    }

    .view-tabs {
      display: inline-flex;
      gap: 8px;
      padding: 4px;
      border-radius: 999px;
      background: rgba(255,255,255,0.05);
      border: 1px solid rgba(255,255,255,0.12);
    }

    .view-tab {
      border: 1px solid transparent;
      background: transparent;
      color: rgba(255,255,255,0.82);
      border-radius: 999px;
      padding: 8px 12px;
      font-size: 12px;
      font-weight: 800;
      cursor: pointer;
      transition: all 0.2s ease;
    }

    .view-tab:hover {
      border-color: rgba(251, 191, 36, 0.34);
      color: #fff;
    }

    .view-tab.active {
      background: rgba(251, 191, 36, 0.16);
      border-color: rgba(251, 191, 36, 0.45);
      color: #fde7a6;
    }

    .search,
    .status-filter {
      border: 1px solid rgba(255,255,255,0.14);
      border-radius: 10px;
      padding: 10px 11px;
      font-size: 13px;
      background: rgba(255,255,255,0.05);
      color: var(--text);
      outline: none;
    }

    .search::placeholder {
      color: rgba(255,255,255,0.52);
    }

    .search {
      min-width: 240px;
      flex: 1;
    }

    .list {
      margin-top: 14px;
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(380px, 420px));
      justify-content: start;
      gap: 14px;
    }

    .card {
      background: #171922;
      border: 1px solid var(--line);
      border-radius: 16px;
      overflow: hidden;
      display: grid;
      grid-template-columns: 140px minmax(0, 1fr);
      min-height: 188px;
      box-shadow: 0 18px 44px rgba(0, 0, 0, 0.34);
      transition: transform 0.25s ease, border-color 0.25s ease;
      width: 100%;
      cursor: pointer;
    }

    .card:hover {
      transform: translateY(-4px);
      border-color: rgba(251, 191, 36, 0.45);
    }

    .thumb {
      background: linear-gradient(145deg, rgba(255,255,255,0.08), rgba(255,255,255,0.03));
      display: flex;
      align-items: center;
      justify-content: center;
      color: rgba(255,255,255,0.66);
      font-size: 12px;
      font-weight: 700;
    }

    .thumb img {
      width: 100%;
      height: 100%;
      object-fit: cover;
      display: block;
      filter: saturate(1.2) contrast(1.14) brightness(1.08);
    }

    .content {
      padding: 12px;
      display: grid;
      gap: 9px;
    }

    .status-pill {
      display: inline-flex;
      width: fit-content;
      border-radius: 999px;
      padding: 5px 10px;
      font-size: 10px;
      font-weight: 800;
      border: 1px solid rgba(255,255,255,0.2);
      background: rgba(255,255,255,0.06);
      color: rgba(255,255,255,0.88);
      text-transform: uppercase;
      letter-spacing: 0.4px;
    }

    .status-pill.active { border-color: rgba(52, 211, 153, 0.5); background: rgba(52, 211, 153, 0.16); color: #b6f5d9; }
    .status-pill.scheduled { border-color: rgba(96, 165, 250, 0.52); background: rgba(96, 165, 250, 0.16); color: #c7e2ff; }
    .status-pill.ended { border-color: rgba(203, 213, 225, 0.36); background: rgba(148, 163, 184, 0.16); color: #d9e1ec; }
    .status-pill.sold { border-color: rgba(251, 191, 36, 0.5); background: rgba(251, 191, 36, 0.16); color: #fde7a6; }
    .status-pill.cancelled { border-color: rgba(239, 68, 68, 0.52); background: rgba(239, 68, 68, 0.16); color: #fbc0c0; }

    .name {
      font-size: 17px;
      font-weight: 800;
      line-height: 1.24;
      color: #f8fafc;
      overflow-wrap: anywhere;
    }

    .meta {
      font-size: 12px;
      color: var(--muted);
      display: grid;
      gap: 3px;
      line-height: 1.4;
    }

    .meta strong {
      color: rgba(255,255,255,0.86);
      font-weight: 700;
    }

    .actions {
      margin-top: 4px;
      display: flex;
      align-items: stretch;
      gap: 8px;
    }

    .action-stack {
      flex: 1 1 auto;
      min-width: 0;
    }

    .closed-note {
      margin-top: 6px;
      font-size: 12px;
      color: rgba(255,255,255,0.68);
      border: 1px dashed rgba(255,255,255,0.2);
      border-radius: 10px;
      padding: 9px 10px;
      background: rgba(255,255,255,0.03);
    }

    .btn {
      border-radius: 10px;
      padding: 9px 10px;
      font-size: 12px;
      font-weight: 800;
      cursor: pointer;
      transition: transform 0.2s ease;
    }

    .btn:hover {
      transform: translateY(-1px);
    }

    .btn-cancel {
      border: 1px solid rgba(239, 68, 68, 0.45);
      background: rgba(239, 68, 68, 0.14);
      color: #fda4af;
    }

    .btn-force {
      border: 1px solid rgba(245, 158, 11, 0.45);
      background: rgba(245, 158, 11, 0.14);
      color: #f9c786;
    }

    .btn-extend {
      border: 1px solid rgba(96, 165, 250, 0.45);
      background: rgba(96, 165, 250, 0.14);
      color: #bedcff;
    }

    .extend-group {
      flex: 0 0 164px;
      display: grid;
      grid-template-columns: 64px minmax(92px, 1fr);
      gap: 6px;
    }

    .extend-input {
      width: 100%;
      border: 1px solid rgba(255,255,255,0.16);
      border-radius: 10px;
      padding: 8px;
      font-size: 12px;
      text-align: center;
      background: rgba(255,255,255,0.05);
      color: var(--text);
    }

    .empty {
      margin-top: 12px;
      border: 1px dashed rgba(255,255,255,0.24);
      border-radius: 14px;
      padding: 24px;
      background: rgba(255,255,255,0.04);
      text-align: center;
      color: #cbd5e1;
      font-size: 14px;
      font-weight: 700;
    }

    .note {
      margin-top: 10px;
      font-size: 12px;
      color: rgba(244, 247, 251, 0.66);
      text-align: right;
    }

    .modal-overlay {
      position: fixed;
      inset: 0;
      z-index: 200;
      display: none;
      align-items: center;
      justify-content: center;
      padding: 20px;
      background: rgba(3, 4, 8, 0.72);
      backdrop-filter: blur(16px);
    }

    .modal-overlay.open {
      display: flex;
    }

    .modal-shell {
      width: min(980px, 100%);
      max-height: min(92vh, 900px);
      overflow: hidden;
      border-radius: 22px;
      background: rgba(7, 10, 16, 0.98);
      border: 1px solid rgba(255,255,255,0.12);
      box-shadow: 0 30px 100px rgba(0, 0, 0, 0.6);
      display: grid;
      grid-template-rows: auto 1fr;
    }

    .modal-head {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 16px;
      padding: 16px 18px;
      border-bottom: 1px solid rgba(255,255,255,0.08);
      background: rgba(255,255,255,0.02);
    }

    .modal-title {
      margin: 0;
      font-size: 18px;
      font-weight: 800;
      letter-spacing: -0.02em;
      color: #f8fafc;
    }

    .modal-close {
      border: 1px solid rgba(255,255,255,0.12);
      background: rgba(255,255,255,0.05);
      color: #fff;
      width: 40px;
      height: 40px;
      border-radius: 10px;
      cursor: pointer;
      font-size: 18px;
      line-height: 1;
    }

    .modal-body {
      overflow: auto;
      padding: 16px;
    }

    .detail-layout {
      display: grid;
      grid-template-columns: 1fr 330px;
      gap: 14px;
    }

    .detail-card {
      border-radius: 14px;
      border: 1px solid rgba(255,255,255,0.12);
      background: rgba(255,255,255,0.05);
      padding: 14px;
    }

    .detail-grid {
      display: grid;
      gap: 8px;
      font-size: 13px;
      color: rgba(244,247,251,0.8);
      line-height: 1.45;
    }

    .detail-grid strong {
      color: #fff;
    }

    .detail-timer {
      font-size: 34px;
      font-weight: 900;
      letter-spacing: -0.03em;
      color: #fde68a;
      margin: 4px 0;
    }

    .detail-sub {
      font-size: 12px;
      color: rgba(255,255,255,0.68);
    }

    .recent-bids {
      display: grid;
      gap: 8px;
    }

    .recent-bid {
      display: flex;
      justify-content: space-between;
      gap: 10px;
      font-size: 12px;
      border-bottom: 1px solid rgba(255,255,255,0.08);
      padding-bottom: 8px;
    }

    .recent-bid:last-child {
      border-bottom: 0;
      padding-bottom: 0;
    }

    .click-tip {
      margin-top: 6px;
      font-size: 11px;
      color: rgba(255,255,255,0.58);
    }

    @media (max-width: 900px) {
      .page,
      .topbar-inner {
        width: calc(100% - 24px);
      }

      .card {
        grid-template-columns: 1fr;
      }

      .thumb {
        height: 190px;
      }

      .actions {
        flex-direction: column;
      }

      .extend-group {
        flex: 0 0 auto;
      }

      .detail-layout {
        grid-template-columns: 1fr;
      }

      .title {
        font-size: 18px;
      }
    }
  </style>
</head>
<body>
  <div class="topbar">
    <div class="topbar-inner">
      <button class="back-btn" type="button" onclick="window.location.href='admin_dashboard.php'">‹</button>
      <div class="title">Live Auction Management</div>
      <button class="new-btn" type="button" onclick="window.location.href='admin_add_auction.php'">+ New Auction</button>
    </div>
  </div>

  <div class="page">
    <div class="toolbar">
      <div class="view-tabs" role="tablist" aria-label="Auction view filter">
        <button id="tabActive" class="view-tab active" type="button" data-view="active" onclick="setView('active')">Active & Scheduled</button>
        <button id="tabClosed" class="view-tab" type="button" data-view="closed" onclick="setView('closed')">Ended / Closed</button>
      </div>
      <input id="searchInput" class="search" type="text" placeholder="Search auction by item name..." oninput="debouncedLoad()">
      <select id="statusFilter" class="status-filter" onchange="renderFiltered()">
        <option value="all">All Status</option>
        <option value="scheduled">Scheduled</option>
        <option value="active">Active</option>
        <option value="ended">Ended</option>
        <option value="sold">Sold</option>
        <option value="cancelled">Cancelled</option>
      </select>
    </div>

    <div id="listHost" class="list"></div>
    <div id="emptyState" class="empty" style="display:none;">No auctions found for this filter.</div>
    <div class="note">Tip: Extend works for scheduled/active auctions only. Force close immediately finalizes winner if reserve is met.</div>
  </div>

  <div id="auctionDetailModal" class="modal-overlay" aria-hidden="true">
    <div class="modal-shell" role="dialog" aria-modal="true" aria-labelledby="auctionDetailTitle">
      <div class="modal-head">
        <h2 id="auctionDetailTitle" class="modal-title">Auction Details</h2>
        <button class="modal-close" type="button" onclick="closeAuctionDetailModal()" aria-label="Close details">&times;</button>
      </div>
      <div class="modal-body">
        <div class="detail-layout">
          <div class="detail-card">
            <div id="detailMainGrid" class="detail-grid"></div>
          </div>
          <div class="detail-card">
            <div class="detail-sub">Time Remaining</div>
            <div id="detailTimer" class="detail-timer">--:--:--</div>
            <div id="detailSchedule" class="detail-sub"></div>
            <hr style="border:0;border-top:1px solid rgba(255,255,255,0.12);margin:12px 0;">
            <div class="detail-sub" style="margin-bottom:8px;">Recent Bids</div>
            <div id="detailRecentBids" class="recent-bids">
              <div class="detail-sub">Loading bids...</div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <script>
    let timer = null;
    let allListings = [];
    let currentView = 'active';
    let modalAuctionDetail = null;
    let modalTimer = null;

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

    async function readJsonResponse(res) {
      const raw = await res.text();
      try {
        return JSON.parse(raw);
      } catch (err) {
        throw new Error('Server returned non-JSON response');
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

    function formatCountdown(endAt) {
      if (!endAt) return '--:--:--';
      const target = new Date(String(endAt).replace(' ', 'T')).getTime();
      if (Number.isNaN(target)) return '--:--:--';
      const diff = Math.max(0, Math.floor((target - Date.now()) / 1000));
      const hours = String(Math.floor(diff / 3600)).padStart(2, '0');
      const minutes = String(Math.floor((diff % 3600) / 60)).padStart(2, '0');
      const seconds = String(diff % 60).padStart(2, '0');
      return `${hours}:${minutes}:${seconds}`;
    }

    function isManageableStatus(status) {
      const normalized = String(status || '').toLowerCase();
      return normalized === 'active' || normalized === 'scheduled';
    }

    function matchesCurrentView(item) {
      const status = String(item?.auction_status || '').toLowerCase();
      if (currentView === 'closed') {
        return ['ended', 'sold', 'cancelled'].includes(status);
      }
      return ['active', 'scheduled'].includes(status);
    }

    function setView(view) {
      currentView = view === 'closed' ? 'closed' : 'active';
      document.getElementById('tabActive').classList.toggle('active', currentView === 'active');
      document.getElementById('tabClosed').classList.toggle('active', currentView === 'closed');
      const selectedStatus = document.getElementById('statusFilter').value;
      if (currentView === 'closed' && (selectedStatus === 'active' || selectedStatus === 'scheduled')) {
        document.getElementById('statusFilter').value = 'all';
      }
      if (currentView === 'active' && ['ended', 'sold', 'cancelled'].includes(selectedStatus)) {
        document.getElementById('statusFilter').value = 'all';
      }
      renderFiltered();
    }

    function renderFiltered() {
      const status = document.getElementById('statusFilter').value;
      let listings = allListings.filter(matchesCurrentView);
      if (status !== 'all') {
        listings = listings.filter((item) => String(item.auction_status || '').toLowerCase() === status);
      }
      render(listings);
    }

    async function fetchAuctionDetail(auctionId) {
      const res = await fetch(`api/get-auction-detail.php?auction_id=${encodeURIComponent(String(auctionId))}`, { cache: 'no-store' });
      const data = await readJsonResponse(res);
      if (!res.ok || !data.success) {
        throw new Error(data.error || 'Unable to load auction detail');
      }
      return data;
    }

    function renderRecentBids(recentBids) {
      const host = document.getElementById('detailRecentBids');
      host.innerHTML = '';
      if (!Array.isArray(recentBids) || recentBids.length === 0) {
        host.innerHTML = '<div class="detail-sub">No bids yet for this auction.</div>';
        return;
      }
      recentBids.slice(0, 6).forEach((bid) => {
        const row = document.createElement('div');
        row.className = 'recent-bid';
        row.innerHTML = `
          <div>
            <strong>${String(bid.bidder || 'Bidder')}</strong><br>
            <span style="color:rgba(255,255,255,0.58);">${formatDate(bid.created_at)}</span>
          </div>
          <div><strong>${formatMoney(bid.bid_amount)}</strong></div>
        `;
        host.appendChild(row);
      });
    }

    function tickModalTimer() {
      if (!modalAuctionDetail) return;
      const status = String(modalAuctionDetail.auction_status || '').toLowerCase();
      const target = status === 'scheduled' ? modalAuctionDetail.start_at : modalAuctionDetail.end_at;
      document.getElementById('detailTimer').textContent = formatCountdown(target || '');
    }

    function closeAuctionDetailModal() {
      const modal = document.getElementById('auctionDetailModal');
      modal.classList.remove('open');
      modal.setAttribute('aria-hidden', 'true');
      if (modalTimer) {
        clearInterval(modalTimer);
        modalTimer = null;
      }
      modalAuctionDetail = null;
    }

    async function openAuctionDetailModal(auctionId, fallbackItem = null) {
      const modal = document.getElementById('auctionDetailModal');
      const mainGrid = document.getElementById('detailMainGrid');
      if (modalTimer) {
        clearInterval(modalTimer);
        modalTimer = null;
      }
      mainGrid.innerHTML = '<div>Loading full auction details...</div>';
      document.getElementById('detailRecentBids').innerHTML = '<div class="detail-sub">Loading bids...</div>';
      document.getElementById('detailSchedule').textContent = '';
      document.getElementById('detailTimer').textContent = '--:--:--';
      modal.classList.add('open');
      modal.setAttribute('aria-hidden', 'false');

      try {
        const data = await fetchAuctionDetail(auctionId);
        const item = data.auction || fallbackItem || {};
        modalAuctionDetail = item;
        document.getElementById('auctionDetailTitle').textContent = item.item_name || 'Auction Details';
        mainGrid.innerHTML = `
          <div><strong>Status:</strong> ${String(item.auction_status || 'N/A')}</div>
          <div><strong>Category:</strong> ${String(item.category_name || 'No Category')}</div>
          <div><strong>Condition:</strong> ${item.condition_grade ? `Condition ${item.condition_grade}` : 'Not set'}</div>
          <div><strong>Current Bid:</strong> ${formatMoney(item.current_bid !== null ? item.current_bid : item.starting_bid)}</div>
          <div><strong>Reserve Price:</strong> ${formatMoney(item.reserve_price)}</div>
          <div><strong>Bid Increment:</strong> ${formatMoney(item.bid_increment)}</div>
          <div><strong>Total Bids:</strong> ${Number(item.bid_count || 0)}</div>
          <div><strong>Winner:</strong> ${item.winner_name ? String(item.winner_name) : 'N/A'}</div>
          <div><strong>Description:</strong> ${String(item.item_description || item.product_description || item.description || 'No description')}</div>
          <div class="click-tip">Tip: Click outside this modal or press ESC to close.</div>
        `;
        document.getElementById('detailSchedule').textContent = `Starts: ${formatDate(item.start_at)} | Ends: ${formatDate(item.end_at)}`;
        renderRecentBids(Array.isArray(data.recent_bids) ? data.recent_bids : []);
        tickModalTimer();
        if (modalTimer) clearInterval(modalTimer);
        modalTimer = setInterval(tickModalTimer, 1000);
      } catch (err) {
        const item = fallbackItem || {};
        modalAuctionDetail = item;
        document.getElementById('auctionDetailTitle').textContent = item.item_name || 'Auction Details';
        mainGrid.innerHTML = `<div>${String(err.message || 'Unable to load full detail right now.')}</div>`;
        document.getElementById('detailSchedule').textContent = `Starts: ${formatDate(item.start_at)} | Ends: ${formatDate(item.end_at)}`;
      }
    }

    async function runAction(auctionId, action, extendMinutes) {
      const body = new FormData();
      body.append('auction_id', String(auctionId));
      body.append('action', action);
      if (action === 'extend') {
        body.append('extend_minutes', String(extendMinutes || 0));
      }

      const res = await fetch('api/admin-manage-auction.php', {
        method: 'POST',
        body
      });
      const data = await readJsonResponse(res);
      if (!res.ok || !data.success) {
        throw new Error(data.error || 'Action failed');
      }
      return data;
    }

    function render(listings) {
      const host = document.getElementById('listHost');
      const empty = document.getElementById('emptyState');
      host.innerHTML = '';

      if (!Array.isArray(listings) || listings.length === 0) {
        empty.style.display = 'block';
        return;
      }
      empty.style.display = 'none';

      listings.forEach((item) => {
        const card = document.createElement('article');
        card.className = 'card';
        card.setAttribute('role', 'button');
        card.setAttribute('tabindex', '0');
        card.setAttribute('aria-label', `Open details for ${item.item_name || 'auction'}`);
        card.addEventListener('click', () => openAuctionDetailModal(item.auction_id, item));
        card.addEventListener('keydown', (event) => {
          if (event.key === 'Enter' || event.key === ' ') {
            event.preventDefault();
            openAuctionDetailModal(item.auction_id, item);
          }
        });

        const thumb = document.createElement('div');
        thumb.className = 'thumb';
        if (item.cover_image) {
          const img = document.createElement('img');
          img.src = String(item.cover_image);
          img.alt = 'Auction image';
          thumb.appendChild(img);
        } else {
          thumb.textContent = 'No image';
        }

        const content = document.createElement('div');
        content.className = 'content';

        const status = document.createElement('span');
        status.className = `status-pill ${String(item.auction_status || '').toLowerCase()}`;
        status.textContent = String(item.auction_status || 'scheduled');

        const name = document.createElement('div');
        name.className = 'name';
        name.textContent = item.item_name || 'Untitled Auction';

        const meta = document.createElement('div');
        meta.className = 'meta';
        meta.innerHTML = `
          <div><strong>Category:</strong> ${item.category_name || 'No Category'}</div>
          <div><strong>Current Bid:</strong> ${formatMoney(item.current_bid !== null ? item.current_bid : item.starting_bid)}</div>
          <div><strong>Total Bids:</strong> ${Number(item.bid_count || 0)}</div>
          <div><strong>Ends:</strong> ${formatDate(item.end_at)}</div>
          <div><strong>Winner:</strong> ${item.winner_name ? item.winner_name : 'N/A'}</div>
          <div class="click-tip">Click card to open full bidding details.</div>
        `;

        content.appendChild(status);
        content.appendChild(name);
        content.appendChild(meta);

        if (isManageableStatus(item.auction_status)) {
          const actions = document.createElement('div');
          actions.className = 'actions';

          const leftWrap = document.createElement('div');
          leftWrap.className = 'action-stack';
          leftWrap.style.display = 'grid';
          leftWrap.style.gap = '6px';

          const cancelBtn = document.createElement('button');
          cancelBtn.className = 'btn btn-cancel';
          cancelBtn.type = 'button';
          cancelBtn.textContent = 'Cancel Auction';
          cancelBtn.addEventListener('click', async (event) => {
            event.stopPropagation();
            const confirmed = await showConfirm('Cancel Auction', 'Cancel this auction? This action cannot be undone.', 'Cancel Auction');
            if (!confirmed) return;
            try {
              await runAction(item.auction_id, 'cancel', 0);
              showAlert('success', 'Cancelled', 'Auction cancelled successfully.');
              await loadListings();
            } catch (err) {
              showAlert('error', 'Cancel Failed', err.message || 'Unable to cancel auction');
            }
          });

          const forceBtn = document.createElement('button');
          forceBtn.className = 'btn btn-force';
          forceBtn.type = 'button';
          forceBtn.textContent = 'Force Close';
          forceBtn.addEventListener('click', async (event) => {
            event.stopPropagation();
            const confirmed = await showConfirm('Force Close', 'Force close this auction now?', 'Force Close');
            if (!confirmed) return;
            try {
              await runAction(item.auction_id, 'force_close', 0);
              showAlert('success', 'Closed', 'Auction force-closed successfully.');
              await loadListings();
            } catch (err) {
              showAlert('error', 'Force Close Failed', err.message || 'Unable to force close auction');
            }
          });

          leftWrap.appendChild(cancelBtn);
          leftWrap.appendChild(forceBtn);

          const extendGroup = document.createElement('div');
          extendGroup.className = 'extend-group';

          const extendInput = document.createElement('input');
          extendInput.className = 'extend-input';
          extendInput.type = 'number';
          extendInput.min = '1';
          extendInput.max = '10080';
          extendInput.value = '30';
          extendInput.title = 'Minutes';
          extendInput.addEventListener('click', (event) => event.stopPropagation());

          const extendBtn = document.createElement('button');
          extendBtn.className = 'btn btn-extend';
          extendBtn.type = 'button';
          extendBtn.textContent = 'Extend';
          extendBtn.addEventListener('click', async (event) => {
            event.stopPropagation();
            const mins = Number(extendInput.value || 0);
            if (!Number.isFinite(mins) || mins <= 0) {
              showAlert('warning', 'Invalid Minutes', 'Enter valid minutes to extend.');
              return;
            }
            try {
              await runAction(item.auction_id, 'extend', Math.floor(mins));
              showAlert('success', 'Extended', `Auction extended by ${Math.floor(mins)} minutes.`);
              await loadListings();
            } catch (err) {
              showAlert('error', 'Extend Failed', err.message || 'Unable to extend auction');
            }
          });

          extendGroup.appendChild(extendInput);
          extendGroup.appendChild(extendBtn);

          actions.appendChild(leftWrap);
          actions.appendChild(extendGroup);
          content.appendChild(actions);
        } else {
          const closedNote = document.createElement('div');
          closedNote.className = 'closed-note';
          closedNote.textContent = 'This auction is closed. Management actions are disabled.';
          content.appendChild(closedNote);
        }

        card.appendChild(thumb);
        card.appendChild(content);

        host.appendChild(card);
      });
    }

    async function loadListings() {
      const host = document.getElementById('listHost');
      host.innerHTML = '<div class="empty" style="grid-column:1/-1;">Loading live auctions...</div>';

      try {
        const params = new URLSearchParams();
        const search = document.getElementById('searchInput').value.trim();
        params.set('status', 'all');
        if (search) params.set('search', search);

        const res = await fetch(`api/admin-get-auction-listings.php?${params.toString()}`, { cache: 'no-store' });
        const data = await readJsonResponse(res);
        if (!res.ok || !data.success) {
          throw new Error(data.error || 'Failed to load auctions');
        }
        allListings = Array.isArray(data.listings) ? data.listings : [];
        renderFiltered();
      } catch (err) {
        host.innerHTML = `<div class="empty" style="grid-column:1/-1;">${String(err.message || 'Unable to load auctions')}</div>`;
      }
    }

    function debouncedLoad() {
      if (timer) clearTimeout(timer);
      timer = setTimeout(loadListings, 260);
    }

    loadListings();
    setInterval(loadListings, 45000);

    document.getElementById('auctionDetailModal').addEventListener('click', (event) => {
      if (event.target && event.target.id === 'auctionDetailModal') {
        closeAuctionDetailModal();
      }
    });

    document.addEventListener('keydown', (event) => {
      if (event.key === 'Escape') {
        closeAuctionDetailModal();
      }
    });
  </script>
</body>
</html>
