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
  <title>Upcoming Auctions</title>
  <link rel="stylesheet" href="main.css">
  <link rel="stylesheet" href="assets/css/local_swal.css">
  <script src="assets/js/local_swal.js"></script>
  <style>
    :root {
      --bg: #07090f;
      --panel: #171922;
      --line: rgba(255,255,255,0.12);
      --text: #f4f7fb;
      --muted: rgba(244,247,251,0.72);
      --accent: #f59e0b;
    }

    * { box-sizing: border-box; }
    body {
      margin: 0;
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      color: var(--text);
      background: radial-gradient(circle at top right, rgba(245, 158, 11, 0.08), transparent 36%), #05060a;
    }

    .wrap {
      max-width: 1320px;
      margin: 0 auto;
      padding: 28px 20px 48px;
    }

    .top {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 14px;
      flex-wrap: wrap;
      margin-bottom: 20px;
      background: linear-gradient(180deg, rgba(8,10,16,0.92), rgba(6,8,14,0.88));
      border: 1px solid rgba(255,255,255,0.08);
      border-radius: 20px;
      padding: 16px 18px;
      box-shadow: 0 18px 48px rgba(0,0,0,0.34);
    }

    .back-link {
      color: rgba(255,255,255,0.88);
      text-decoration: none;
      border: 1px solid var(--line);
      border-radius: 999px;
      padding: 10px 14px;
      font-weight: 700;
      background: rgba(255,255,255,0.04);
    }

    .title {
      margin: 0;
      font-size: clamp(28px, 4vw, 46px);
      letter-spacing: -0.03em;
      font-weight: 900;
      color: #f8fafc;
    }

    .subtitle {
      margin: 6px 0 0;
      color: #aeb6c7;
      font-size: 16px;
    }

    .grid {
      display: grid;
      grid-template-columns: repeat(3, minmax(0, 1fr));
      gap: 22px;
    }

    .card {
      position: relative;
      overflow: hidden;
      border-radius: 18px;
      background: var(--panel);
      border: 1px solid var(--line);
      box-shadow: 0 16px 44px rgba(0, 0, 0, 0.32);
      transition: transform 0.25s ease, border-color 0.25s ease;
    }

    .card:hover {
      transform: translateY(-4px);
      border-color: rgba(245, 158, 11, 0.45);
    }

    .image {
      position: relative;
      height: 320px;
      background: linear-gradient(135deg, rgba(255,255,255,0.06), rgba(255,255,255,0.02));
    }

    .image img {
      width: 100%;
      height: 100%;
      object-fit: cover;
      display: block;
    }

    .image::after {
      content: '';
      position: absolute;
      inset: 0;
      background: linear-gradient(180deg, rgba(8,10,18,0.08) 0%, rgba(8,10,18,0.58) 68%, rgba(20,22,30,0.96) 100%);
    }

    .chip {
      position: absolute;
      top: 12px;
      right: 12px;
      border-radius: 999px;
      padding: 7px 12px;
      font-size: 12px;
      font-weight: 800;
      border: 1px solid rgba(255,255,255,0.18);
      background: rgba(10, 12, 18, 0.62);
      color: rgba(255,255,255,0.88);
      z-index: 2;
    }

    .body {
      position: relative;
      margin-top: -122px;
      padding: 14px 22px 20px;
      display: grid;
      gap: 12px;
      z-index: 2;
    }

    .name {
      margin: 0;
      font-size: 34px;
      line-height: 1.08;
      letter-spacing: -0.02em;
      font-weight: 800;
    }

    .meta {
      color: var(--muted);
      font-size: 18px;
      line-height: 1.6;
      min-height: 28px;
    }

    .price-row {
      display: flex;
      justify-content: space-between;
      align-items: end;
      gap: 10px;
      border-top: 1px solid rgba(255,255,255,0.14);
      padding-top: 14px;
    }

    .price-label {
      font-size: 13px;
      color: rgba(255,255,255,0.58);
      margin-bottom: 8px;
      font-weight: 700;
    }

    .price-value {
      font-size: 32px;
      font-weight: 800;
    }

    .btn {
      border: 1px solid rgba(245, 158, 11, 0.38);
      border-radius: 14px;
      padding: 13px 14px;
      font-size: 17px;
      font-weight: 800;
      cursor: pointer;
      background: rgba(245, 158, 11, 0.14);
      color: #f59e0b;
      text-decoration: none;
      text-align: center;
      display: block;
    }

    .empty {
      margin-top: 24px;
      border-radius: 14px;
      padding: 20px;
      text-align: center;
      border: 1px solid rgba(255,255,255,0.14);
      background: #0f131d;
      color: #cbd5e1;
      font-size: 16px;
      font-weight: 700;
    }

    @media (max-width: 1120px) {
      .grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    }

    @media (max-width: 780px) {
      .wrap { padding: 20px 14px 36px; }
      .grid { grid-template-columns: 1fr; }
      .name { font-size: 28px; }
      .meta { font-size: 16px; }
      .price-value { font-size: 28px; }
      .btn { font-size: 16px; }
    }
  </style>
</head>
<body>
  <div class="wrap">
    <div class="top">
      <a class="back-link" href="auction.php">Back to Live</a>
      <div>
        <h1 class="title">Upcoming Auctions</h1>
        <p class="subtitle">Don't miss these exclusive items</p>
      </div>
    </div>

    <div id="upcomingGrid" class="grid"></div>
    <div id="emptyState" class="empty" style="display:none;">No upcoming bidding</div>
  </div>

  <script>
    function showAlert(icon, title, text) {
      if (window.localSwalAlert) {
        return window.localSwalAlert(icon, title, text);
      }
      window.alert(text || title);
      return Promise.resolve();
    }

    function formatMoney(value) {
      if (value === null || value === undefined || Number.isNaN(Number(value))) return 'N/A';
      return 'PHP ' + Number(value).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    function formatLead(raw) {
      if (!raw) return 'Soon';
      const start = new Date(String(raw).replace(' ', 'T')).getTime();
      if (Number.isNaN(start)) return 'Soon';
      const diffSec = Math.max(0, Math.floor((start - Date.now()) / 1000));
      const hours = Math.floor(diffSec / 3600);
      const minutes = Math.floor((diffSec % 3600) / 60);
      return hours + 'h ' + minutes + 'm';
    }

    function renderCard(item) {
      const article = document.createElement('article');
      article.className = 'card';

      const image = item.cover_image ? String(item.cover_image) : 'logo.jpg';
      const bid = item.current_bid !== null ? item.current_bid : item.starting_bid;

      article.innerHTML = `
        <div class="image">
          <img src="${image}" alt="${item.item_name || 'Auction item'}">
          <div class="chip">${formatLead(item.start_at)}</div>
        </div>
        <div class="body">
          <h3 class="name">${item.item_name || 'Untitled Auction'}</h3>
          <div class="meta">${item.category_name || 'Upcoming lot'}</div>
          <div class="price-row">
            <div>
              <div class="price-label">Current Bid</div>
              <div class="price-value">${formatMoney(bid)}</div>
            </div>
          </div>
          <a class="btn" href="auction.php">View Auction</a>
        </div>
      `;

      return article;
    }

    async function loadUpcoming() {
      const host = document.getElementById('upcomingGrid');
      const empty = document.getElementById('emptyState');
      host.innerHTML = '';
      empty.style.display = 'none';

      try {
        const params = new URLSearchParams();
        params.set('status', 'live');
        params.set('limit', '100');

        const res = await fetch('api/get-auction-listings.php?' + params.toString(), { cache: 'no-store' });
        const data = await res.json();
        if (!res.ok || !data.success || !Array.isArray(data.listings)) {
          throw new Error(data.error || 'Failed to load upcoming listings');
        }

        const upcoming = data.listings
          .filter((item) => String(item.auction_status || '').toLowerCase() === 'scheduled')
          .sort((a, b) => {
            const aTime = new Date(String(a.start_at || '').replace(' ', 'T')).getTime();
            const bTime = new Date(String(b.start_at || '').replace(' ', 'T')).getTime();
            return aTime - bTime;
          });

        if (upcoming.length === 0) {
          empty.style.display = 'block';
          return;
        }

        upcoming.forEach((item) => host.appendChild(renderCard(item)));
      } catch (err) {
        const message = String(err.message || 'No upcoming bidding');
        empty.textContent = message;
        empty.style.display = 'block';
        showAlert('error', 'Load Failed', message);
      }
    }

    loadUpcoming();
  </script>
</body>
</html>
