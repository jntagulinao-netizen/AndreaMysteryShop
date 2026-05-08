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
  <title>Auction Drafts - Admin</title>
  <link rel="stylesheet" href="main.css">
  <link rel="stylesheet" href="assets/css/local_swal.css">
  <script src="assets/js/local_swal.js"></script>
  <style>
    * { box-sizing: border-box; }
    body {
      margin: 0;
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      background: #f4f6fb;
      color: #0f172a;
    }
    .page {
      width: calc(100% - 48px);
      max-width: none;
      margin: 0 auto;
      padding: 96px 0 18px;
    }
    .topbar {
      position: fixed;
      top: 0;
      left: 0;
      right: 0;
      z-index: 60;
      background: #f4f6fb;
      border-bottom: 1px solid #e2e8f0;
      padding: 12px 0;
    }
    .topbar-inner {
      width: calc(100% - 48px);
      max-width: none;
      margin: 0 auto;
      display: flex;
      gap: 10px;
      align-items: center;
      background: #fff;
      border: 1px solid #e2e8f0;
      border-radius: 12px;
      padding: 10px 12px;
    }
    .back-btn {
      border: none;
      background: transparent;
      width: 36px;
      height: 36px;
      border-radius: 9px;
      font-size: 20px;
      cursor: pointer;
    }
    .title {
      flex: 1;
      font-size: 20px;
      font-weight: 700;
    }
    .new-btn {
      border: none;
      background: #2f5dd7;
      color: #fff;
      border-radius: 9px;
      padding: 9px 12px;
      font-size: 12px;
      font-weight: 700;
      cursor: pointer;
    }
    .meta {
      margin-top: 10px;
      font-size: 13px;
      color: #64748b;
      text-align: center;
    }
    .list {
      margin-top: 14px;
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
      gap: 12px;
    }
    .card {
      background: #fff;
      border: 1px solid #e2e8f0;
      border-radius: 12px;
      overflow: hidden;
      display: flex;
      flex-direction: column;
    }
    .cover {
      height: 160px;
      background: linear-gradient(140deg, #eef2ff, #f8fafc);
      display: flex;
      align-items: center;
      justify-content: center;
      color: #6b7280;
      font-size: 12px;
      font-weight: 700;
    }
    .cover img {
      width: 100%;
      height: 100%;
      object-fit: cover;
      display: block;
    }
    .content {
      padding: 12px;
      display: flex;
      flex-direction: column;
      gap: 8px;
    }
    .name {
      font-size: 16px;
      font-weight: 700;
      line-height: 1.25;
      min-height: 40px;
    }
    .info {
      font-size: 12px;
      color: #475569;
      display: grid;
      gap: 4px;
    }
    .actions {
      display: flex;
      gap: 8px;
      margin-top: 6px;
    }
    .btn {
      flex: 1;
      border-radius: 8px;
      padding: 8px 10px;
      font-size: 12px;
      font-weight: 700;
      cursor: pointer;
    }
    .btn-edit {
      border: 1px solid #a5b4fc;
      background: #eef2ff;
      color: #3730a3;
    }
    .btn-delete {
      border: 1px solid #fecaca;
      background: #fef2f2;
      color: #b91c1c;
    }
    .btn-publish {
      border: 1px solid #86efac;
      background: #ecfdf3;
      color: #166534;
    }
    .empty {
      margin-top: 14px;
      border: 1px dashed #cbd5e1;
      border-radius: 12px;
      padding: 22px;
      text-align: center;
      color: #64748b;
      font-size: 14px;
      background: #fff;
    }
  </style>
</head>
<body>
  <div class="topbar">
    <div class="topbar-inner">
      <button class="back-btn" type="button" onclick="window.location.href='admin_dashboard.php'">‹</button>
      <div class="title">Auction Drafts</div>
      <button class="new-btn" type="button" onclick="window.location.href='admin_add_auction.php'">+ New Auction</button>
    </div>
  </div>

  <div class="page">
    <div class="meta">Manage your auction draft uploads and continue editing anytime.</div>
    <div class="meta">Auction windows are checked against all saved and published auctions before publish.</div>
    <div id="draftList" class="list"></div>
    <div id="emptyState" class="empty" style="display:none;">No auction drafts yet. Create one using the New Auction button.</div>
  </div>

  <script>
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

    async function checkScheduleAvailability(draft) {
      const startAt = String(draft?.start_at || '').trim();
      const endAt = String(draft?.end_at || '').trim();

      if (!startAt || !endAt) {
        return true;
      }

      const body = new FormData();
      body.append('draft_id', String(draft?.draft_id || 0));
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

    async function checkCategoryNameExists(name) {
      const trimmed = String(name || '').trim();
      if (trimmed.length < 4) return { ok: false, reason: 'minlength' };
      try {
        const res = await fetch(`api/check-category-name.php?category_name=${encodeURIComponent(trimmed)}`, { cache: 'no-store' });
        const data = await res.json();
        if (!res.ok || !data.success) return { ok: false, reason: 'error' };
        return { ok: !data.exists, reason: data.exists ? 'exists' : 'available' };
      } catch (e) {
        return { ok: false, reason: 'error' };
      }
    }

    function formatCurrency(value) {
      if (value === null || value === undefined || Number.isNaN(Number(value))) return 'N/A';
      return `PHP ${Number(value).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
    }

    function formatDate(raw) {
      if (!raw) return 'Not set';
      const d = new Date(String(raw).replace(' ', 'T'));
      if (Number.isNaN(d.getTime())) return String(raw);
      return d.toLocaleString();
    }

    function renderDrafts(drafts) {
      const list = document.getElementById('draftList');
      const empty = document.getElementById('emptyState');
      list.innerHTML = '';

      if (!Array.isArray(drafts) || drafts.length === 0) {
        empty.style.display = 'block';
        return;
      }

      empty.style.display = 'none';

      drafts.forEach((draft) => {
        const card = document.createElement('article');
        card.className = 'card';

        const cover = document.createElement('div');
        cover.className = 'cover';
        if (draft.cover_image) {
          const img = document.createElement('img');
          img.src = String(draft.cover_image);
          img.alt = 'Auction draft image';
          cover.appendChild(img);
        } else {
          cover.textContent = 'No image yet';
        }

        const content = document.createElement('div');
        content.className = 'content';

        const name = document.createElement('div');
        name.className = 'name';
        name.textContent = draft.item_name || 'Untitled Auction Draft';

        const info = document.createElement('div');
        info.className = 'info';
          info.innerHTML = `
            <div>Start: ${String(draft.start_at || 'N/A')}</div>
            <div>End: ${String(draft.end_at || 'N/A')}</div>
            <div>Starting bid: ${formatCurrency(draft.starting_bid)}</div>
            <div>Category: ${String(draft.category_name || draft.new_category_name || 'No category')}</div>
          `;

          // If draft has a new category name, add availability badge and check
          if (draft.new_category_name && String(draft.new_category_name).trim()) {
            const badge = document.createElement('div');
            badge.style.marginTop = '6px';
            badge.textContent = 'Checking category...';
            badge.className = 'category-badge';
            info.appendChild(badge);
            (async () => {
              const chk = await checkCategoryNameExists(draft.new_category_name);
              if (chk.ok) {
                badge.textContent = 'New category available';
                badge.style.color = '#166534';
              } else if (chk.reason === 'minlength') {
                badge.textContent = 'Category name too short (min 4)';
                badge.style.color = '#b91c1c';
              } else if (chk.reason === 'exists') {
                badge.textContent = 'Category already exists';
                badge.style.color = '#b91c1c';
              } else {
                badge.textContent = 'Category check failed';
                badge.style.color = '#b91c1c';
              }
            })();
          }

        const actions = document.createElement('div');
        actions.className = 'actions';

        const editBtn = document.createElement('button');
        editBtn.className = 'btn btn-edit';
        editBtn.type = 'button';
        editBtn.textContent = 'Edit';
        editBtn.addEventListener('click', () => {
          window.location.href = `admin_add_auction.php?draft_id=${encodeURIComponent(draft.draft_id)}`;
        });

        const deleteBtn = document.createElement('button');
        deleteBtn.className = 'btn btn-delete';
        deleteBtn.type = 'button';
        deleteBtn.textContent = 'Delete';
        deleteBtn.addEventListener('click', async () => {
          const ok = await showConfirm('Delete Draft', 'Delete this auction draft?', 'Delete');
          if (!ok) return;

          try {
            const body = new FormData();
            body.append('draft_id', String(draft.draft_id));
            const res = await fetch('api/delete-auction-draft.php', {
              method: 'POST',
              body
            });
            const data = await res.json();
            if (!res.ok || !data.success) {
              throw new Error(data.error || 'Failed to delete draft');
            }
            showAlert('success', 'Deleted', 'Auction draft deleted successfully.');
            await loadDrafts();
          } catch (err) {
            showAlert('error', 'Delete Failed', err.message || 'Unable to delete draft');
          }
        });

        const publishBtn = document.createElement('button');
        publishBtn.className = 'btn btn-publish';
        publishBtn.type = 'button';
        publishBtn.textContent = 'Publish';
        publishBtn.addEventListener('click', async () => {
          const okPublish = await showConfirm('Publish Draft', 'Publish this auction draft now?', 'Publish');
          if (!okPublish) return;

          try {
            const okSchedule = await checkScheduleAvailability(draft);
            if (!okSchedule) return;
          } catch (err) {
            showAlert('error', 'Schedule Check Failed', err.message || 'Unable to validate draft schedule');
            return;
          }

          // If draft contains a new category, validate availability before publishing
          if (draft.new_category_name && String(draft.new_category_name).trim()) {
            const chk = await checkCategoryNameExists(draft.new_category_name);
            if (!chk.ok) {
              if (chk.reason === 'minlength') {
                showAlert('warning', 'Category Name', 'Draft uses a new category name that is too short (min 4 characters). Please edit the draft first.');
              } else if (chk.reason === 'exists') {
                showAlert('warning', 'Category Name', 'Draft uses a new category name that already exists. Please edit the draft first.');
              } else {
                showAlert('error', 'Category Check Failed', 'Unable to validate draft category name. Try again later.');
              }
              return;
            }
          }

          try {
            const body = new FormData();
            body.append('draft_id', String(draft.draft_id));
            const res = await fetch('api/publish-auction-draft.php', {
              method: 'POST',
              body
            });
            const data = await res.json();
            if (!res.ok || !data.success) {
              throw new Error(data.error || 'Failed to publish draft');
            }
            showAlert('success', 'Published', 'Auction draft published successfully.');
            window.location.href = `admin_auction_drafts.php?published_id=${encodeURIComponent(String(data.auction_id || 0))}`;
          } catch (err) {
            showAlert('error', 'Publish Failed', err.message || 'Unable to publish draft');
          }
        });

        actions.appendChild(editBtn);
        actions.appendChild(publishBtn);
        actions.appendChild(deleteBtn);

        content.appendChild(name);
        content.appendChild(info);
        content.appendChild(actions);

        card.appendChild(cover);
        card.appendChild(content);

        list.appendChild(card);
      });
    }

    async function loadDrafts() {
      const list = document.getElementById('draftList');
      list.innerHTML = '<div class="empty" style="grid-column:1/-1;">Loading auction drafts...</div>';
      try {
        const res = await fetch('api/get-auction-drafts.php', { cache: 'no-store' });
        const data = await res.json();
        if (!res.ok || !data.success) {
          throw new Error(data.error || 'Failed to load auction drafts');
        }
        renderDrafts(Array.isArray(data.drafts) ? data.drafts : []);
      } catch (err) {
        list.innerHTML = `<div class="empty" style="grid-column:1/-1;">${String(err.message || 'Unable to load drafts')}</div>`;
      }
    }

    loadDrafts();
  </script>
</body>
</html>
