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
$isArchivedView = (isset($_GET['view']) && $_GET['view'] === 'archived');
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title><?php echo $isArchivedView ? 'Archived Products - Admin' : 'My Products - Admin'; ?></title>
  <link rel="stylesheet" href="main.css">
  <style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    html, body { margin: 0; padding: 0; }
    body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f5f5f5; padding-bottom: 78px; color: #333; }

    .page-container { width: calc(100% - 48px); max-width: none; margin: 0 auto; padding: 16px 0; }

    .page-header {
      position: sticky;
      top: 0;
      background: #fff;
      z-index: 120;
      display: flex;
      align-items: center;
      gap: 12px;
      padding: 12px 16px;
      border-radius: 12px;
      margin-bottom: 12px;
    }
    .back-arrow { cursor: pointer; font-size: 24px; color: #333; padding: 4px; line-height: 1; }
    .header-title { font-size: 18px; font-weight: 600; color: #333; flex: 1; }
    .header-meta { font-size: 12px; color: #777; }

    .topbar-menu { position: relative; }
    .menu-trigger {
      width: 34px;
      height: 34px;
      border: 1px solid #ddd;
      border-radius: 8px;
      background: #fff;
      color: #333;
      font-size: 18px;
      cursor: pointer;
      line-height: 1;
    }
    .menu-dropdown {
      position: absolute;
      top: calc(100% + 6px);
      right: 0;
      min-width: 170px;
      background: #fff;
      border: 1px solid #ddd;
      border-radius: 10px;
      box-shadow: 0 8px 20px rgba(0, 0, 0, 0.12);
      display: none;
      z-index: 130;
      overflow: hidden;
    }
    .menu-dropdown.active { display: block; }
    .menu-dropdown a {
      display: block;
      padding: 10px 12px;
      color: #333;
      text-decoration: none;
      font-size: 13px;
      border-bottom: 1px solid #f0f0f0;
    }
    .menu-dropdown a:last-child { border-bottom: none; }
    .menu-dropdown a:hover { background: #f8f8f8; }

    .hero-banner {
      height: 220px;
      background: linear-gradient(120deg, #e22a39 0%, #fb8c00 100%);
      border-radius: 16px;
      color: #fff;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      text-align: center;
      margin-bottom: 24px;
      box-shadow: 0 10px 28px rgba(226, 42, 57, 0.25);
    }
    .hero-title { font-size: 34px; font-weight: 700; margin-bottom: 8px; }
    .hero-subtitle { font-size: 16px; opacity: 0.95; }

    .filters {
      display: flex;
      gap: 12px;
      margin-bottom: 24px;
      flex-wrap: wrap;
      align-items: center;
      position: relative;
      background: #fff;
      border-radius: 12px;
      padding: 12px;
      border: 1px solid #ececec;
    }
    .filter-btn {
      padding: 10px 18px;
      background: #fff;
      border: 2px solid #e22a39;
      color: #e22a39;
      border-radius: 25px;
      cursor: pointer;
      font-weight: 700;
      transition: all 0.3s;
    }
    .filter-btn.more-cats-btn { border-color: #999; color: #333; }
    .filter-btn.active,
    .filter-btn:hover { background: #e22a39; color: #fff; }

    .more-categories-dropdown {
      display: none;
      position: absolute;
      top: calc(100% + 6px);
      left: 12px;
      background: #fff;
      border: 1px solid #ddd;
      border-radius: 10px;
      box-shadow: 0 8px 20px rgba(0,0,0,0.15);
      padding: 8px;
      width: 220px;
      z-index: 999;
      max-height: 220px;
      overflow-y: auto;
      flex-direction: column;
      gap: 8px;
    }
    .more-categories-dropdown.active { display: flex; }
    .more-categories-dropdown .filter-btn {
      border: 1px solid #eee;
      color: #333;
      border-radius: 8px;
      padding: 8px 10px;
      text-align: left;
      font-size: 13px;
    }
    .more-categories-dropdown .filter-btn.active,
    .more-categories-dropdown .filter-btn:hover { border-color: #e22a39; background: #fef0ef; color: #e22a39; }

    .sort-select {
      padding: 10px 14px;
      border: 2px solid #ddd;
      border-radius: 25px;
      background: #fff;
      color: #333;
      font-weight: 600;
      cursor: pointer;
      margin-left: auto;
    }
    .view-switch {
      display: inline-flex;
      gap: 8px;
      align-items: center;
      margin-right: auto;
    }
    .view-switch a {
      text-decoration: none;
      border: 1px solid #ddd;
      background: #fff;
      color: #444;
      padding: 8px 12px;
      border-radius: 999px;
      font-size: 12px;
      font-weight: 700;
    }
    .view-switch a.active {
      border-color: #e22a39;
      background: #fff2f2;
      color: #c9182a;
    }

    .products-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 24px; }
    .product-card {
      background: #fff;
      border-radius: 0;
      overflow: hidden;
      box-shadow: 0 8px 25px rgba(0,0,0,0.08);
      transition: all 0.3s ease;
      cursor: pointer;
      display: flex;
      flex-direction: column;
      min-height: 430px;
    }
    .product-card:hover { transform: translateY(-8px); box-shadow: 0 15px 40px rgba(0,0,0,0.15); }
    .product-variant-badge {
      position: absolute;
      top: 8px;
      right: 8px;
      background: #e22a39;
      color: #fff;
      padding: 4px 10px;
      border-radius: 20px;
      font-size: 12px;
      font-weight: 700;
      z-index: 10;
    }
    .product-archived-badge {
      position: absolute;
      top: 8px;
      left: 8px;
      background: #555;
      color: #fff;
      padding: 4px 10px;
      border-radius: 20px;
      font-size: 12px;
      font-weight: 700;
      z-index: 10;
    }
    .product-image {
      height: 220px;
      flex: 0 0 220px;
      background: linear-gradient(45deg, #f0f0f0, #e0e0e0);
      display: flex;
      align-items: center;
      justify-content: center;
      position: relative;
      overflow: hidden;
    }
    .product-image img, .product-card-img { width: 100%; height: 100%; object-fit: cover; display: block; border-radius: 0; }
    .product-info { padding: 24px; display: flex; flex-direction: column; gap: 8px; flex: 1; }
    .product-name {
      font-size: 18px;
      font-weight: 700;
      line-height: 1.3;
      min-height: 46px;
      display: -webkit-box;
      -webkit-line-clamp: 2;
      -webkit-box-orient: vertical;
      overflow: hidden;
    }
    .product-rating { color: #ffa500; font-size: 14px; }
    .product-price { font-size: 24px; color: #e22a39; font-weight: 700; margin-top: auto; }
    .product-stock { margin-top: 8px; font-size: 13px; font-weight: 600; }

    .empty-state {
      text-align: center;
      padding: 48px 20px;
      background: #fff;
      border-radius: 12px;
      border: 1px solid #ececec;
      color: #777;
      font-size: 15px;
    }

    .product-modal {
      display: none;
      position: fixed;
      inset: 0;
      background: rgba(0, 0, 0, 0.55);
      z-index: 1000;
      align-items: center;
      justify-content: center;
      padding: 0;
    }
    .product-modal.active { display: flex; }
    .product-modal-card {
      width: 100%;
      height: 100%;
      background: #fff;
      border-radius: 0;
      overflow: hidden;
      display: flex;
      flex-direction: column;
    }
    .product-modal-header {
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 12px 16px;
      border-bottom: 1px solid #eee;
      font-size: 18px;
      font-weight: 700;
      position: sticky;
      top: 0;
      background: #fff;
      z-index: 10;
      flex-shrink: 0;
    }
    .product-modal-close {
      border: none;
      background: transparent;
      font-size: 26px;
      cursor: pointer;
      color: #666;
      line-height: 1;
    }
    .product-modal-content { padding: 16px; overflow-y: auto; flex: 1; }
    .modal-main-grid {
      display: grid;
      grid-template-columns: 320px 1fr;
      gap: 16px;
      align-items: start;
    }
    .modal-preview {
      border: 1px solid #eee;
      border-radius: 12px;
      padding: 12px;
      background: #fafafa;
      position: sticky;
      top: 0;
    }
    .product-modal-image { width: 100%; border-radius: 12px; border: 1px solid #eee; margin-bottom: 12px; }
    .product-modal-price { font-size: 26px; color: #e22a39; font-weight: 700; margin: 8px 0; }
    .product-modal-meta { color: #666; font-size: 13px; margin-bottom: 6px; }
    .product-modal-desc { color: #333; font-size: 13px; line-height: 1.5; margin-top: 8px; }

    .modal-workspace {
      border: 1px solid #eee;
      border-radius: 12px;
      overflow: hidden;
      background: #fff;
    }
    .modal-tabs {
      display: flex;
      gap: 0;
      border-bottom: 1px solid #eee;
      background: #fff;
    }
    .modal-tab {
      flex: 1;
      border: none;
      background: transparent;
      padding: 12px 14px;
      font-size: 13px;
      font-weight: 700;
      color: #666;
      cursor: pointer;
      border-bottom: 2px solid transparent;
    }
    .modal-tab.active {
      color: #e22a39;
      border-bottom-color: #e22a39;
      background: #fff8f8;
    }
    .modal-panel { display: none; padding: 14px; }
    .modal-panel.active { display: block; }

    .edit-form { display: grid; gap: 12px; }
    .edit-form-group { display: grid; gap: 6px; }
    .edit-form-group label { font-size: 13px; color: #444; font-weight: 600; }
    .edit-form-group input,
    .edit-form-group textarea,
    .edit-form-group select {
      width: 100%;
      border: 1px solid #ddd;
      border-radius: 8px;
      padding: 10px 12px;
      font-size: 14px;
      font-family: inherit;
      color: #333;
      background: #fff;
    }
    .edit-form-group textarea { min-height: 120px; resize: vertical; }
    .edit-actions { display: flex; justify-content: flex-end; gap: 10px; margin-top: 4px; }
    .edit-btn {
      border: 1px solid #ddd;
      background: #fff;
      color: #333;
      padding: 10px 14px;
      border-radius: 8px;
      cursor: pointer;
      font-size: 13px;
      font-weight: 600;
    }
    .edit-btn.primary { background: #e22a39; color: #fff; border-color: #e22a39; }
    .edit-btn.primary:hover { background: #c9182a; }
    .edit-btn.archive {
      background: #555;
      color: #fff;
      border-color: #555;
      margin-right: auto;
    }
    .edit-btn.archive.restore {
      background: #2e7d32;
      border-color: #2e7d32;
    }

    .variants-section {
      border: 1px solid #eee;
      border-radius: 10px;
      padding: 12px;
      background: #f9f9f9;
      margin-top: 12px;
    }
    .variants-section-title {
      font-size: 13px;
      font-weight: 700;
      color: #333;
      margin-bottom: 0;
    }
    .variants-section-header {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 10px;
      margin-bottom: 10px;
    }
    .add-variant-btn {
      border: 1px solid #b7d5ff;
      background: #eef5ff;
      color: #1f56bf;
      border-radius: 8px;
      font-size: 12px;
      font-weight: 700;
      padding: 7px 10px;
      cursor: pointer;
    }
    .add-variant-btn:hover {
      background: #dfeeff;
    }
    .variants-section-help {
      font-size: 12px;
      color: #666;
      margin-bottom: 10px;
    }
    .variant-edit-row {
      display: grid;
      grid-template-columns: 1fr 120px 120px 1fr;
      gap: 10px;
      align-items: center;
      padding: 10px;
      background: #fff;
      border: 1px solid #e0e0e0;
      border-radius: 8px;
      margin-bottom: 8px;
    }
    .variant-edit-row:last-child { margin-bottom: 0; }
    .variant-row-actions {
      display: flex;
      justify-content: flex-end;
      align-items: center;
      gap: 8px;
      margin-top: 8px;
    }
    .variant-main-btn {
      border: 1px solid #b7d5ff;
      background: #eef5ff;
      color: #1f56bf;
      border-radius: 8px;
      font-size: 11px;
      font-weight: 700;
      padding: 6px 10px;
      cursor: pointer;
    }
    .variant-main-btn.active {
      border-color: #1e6a36;
      background: #eaf8ef;
      color: #1e6a36;
    }
    .variant-remove-row-btn {
      border: 1px solid #efc5ca;
      background: #fff4f5;
      color: #bb2532;
      border-radius: 8px;
      font-size: 11px;
      font-weight: 700;
      padding: 6px 10px;
      cursor: pointer;
    }
    .variant-edit-row input {
      width: 100%;
      border: 1px solid #ddd;
      border-radius: 6px;
      padding: 8px 10px;
      font-size: 13px;
      color: #333;
    }
    .variant-images-cell {
      min-width: 0;
      display: grid;
      gap: 8px;
    }
    .variant-image-input-wrap {
      display: flex;
      flex-direction: column;
      gap: 4px;
      min-width: 0;
      width: 100%;
    }
    .variant-image-note {
      font-size: 11px;
      color: #777;
      line-height: 1.3;
    }
    .variant-image-input-wrap .variant-image {
      width: 100%;
      max-width: 100%;
      min-width: 0;
      box-sizing: border-box;
      font-size: 12px;
      padding: 6px 8px;
      overflow: hidden;
      text-overflow: ellipsis;
      white-space: nowrap;
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
    .main-images-section {
      border: 1px solid #eee;
      border-radius: 10px;
      padding: 12px;
      background: #fafafa;
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
    .main-image-card.removed {
      opacity: 0.48;
      border-style: dashed;
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
    .main-image-empty {
      font-size: 12px;
      color: #777;
      border: 1px dashed #d9d9d9;
      border-radius: 8px;
      padding: 10px;
      text-align: center;
      background: #fff;
      margin-top: 10px;
    }
    .main-image-limit {
      font-size: 12px;
      color: #555;
      margin-top: 8px;
      font-weight: 600;
    }

    .reviews-header-box {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 12px;
      margin-bottom: 12px;
      background: #fff3f1;
      border: 1px solid #ffd8d3;
      border-radius: 10px;
      padding: 10px 12px;
    }
    .reviews-header-box strong { color: #333; font-size: 14px; }
    .reviews-header-box span { color: #e22a39; font-weight: 700; font-size: 13px; }
    .review-list { display: grid; gap: 10px; }
    .review-item {
      border: 1px solid #eee;
      border-radius: 10px;
      padding: 10px 12px;
      background: #fff;
    }
    .review-top { display: flex; align-items: center; justify-content: space-between; margin-bottom: 6px; }
    .review-user { font-size: 13px; color: #333; font-weight: 700; }
    .review-date { font-size: 12px; color: #888; }
    .review-stars { color: #ffa500; font-size: 13px; margin-bottom: 6px; }
    .review-text { color: #444; font-size: 13px; line-height: 1.45; }
    .review-media { display: flex; flex-wrap: wrap; gap: 8px; margin-top: 8px; }
    .review-media img,
    .review-media video { width: 88px; height: 88px; object-fit: cover; border-radius: 8px; border: 1px solid #eee; background: #f7f7f7; }
    .review-media-group { margin-top: 12px; }
    .review-media-main { max-width: 100%; max-height: 320px; border-radius: 8px; display: block; margin-top: 0; }
    .review-media-single { max-width: 100%; max-height: 300px; border-radius: 8px; margin-top: 12px; display: block; }
    .review-media-thumbs { display: flex; gap: 8px; flex-wrap: wrap; margin-top: 8px; }
    .review-media-thumb { width: 56px; height: 56px; object-fit: cover; border: 2px solid #ddd; border-radius: 8px; cursor: pointer; }
    .review-media-thumb.active { border-color: #e22a39; }
    .review-media-video-thumb { background: #f5f5f5; color: #555; font-size: 10px; font-weight: 700; }
    .reviews-empty,
    .reviews-loading {
      text-align: center;
      font-size: 13px;
      color: #777;
      padding: 20px 12px;
      border: 1px dashed #ddd;
      border-radius: 10px;
      background: #fafafa;
    }
    .swal-overlay {
      position: fixed;
      inset: 0;
      background: rgba(15, 23, 42, 0.45);
      display: none;
      align-items: center;
      justify-content: center;
      z-index: 2000;
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
    .swal-icon.warning { background: #fff6e5; color: #bb6a00; }
    .swal-title { font-size: 20px; font-weight: 700; color: #152033; margin-bottom: 8px; }
    .swal-text { font-size: 14px; color: #5f6d7f; margin-bottom: 14px; line-height: 1.45; }
    .swal-actions { display: grid; grid-template-columns: 1fr; gap: 8px; }
    .swal-actions.two { grid-template-columns: 1fr 1fr; }
    .swal-btn {
      border: none;
      border-radius: 10px;
      font-size: 14px;
      font-weight: 700;
      width: 100%;
      height: 42px;
      cursor: pointer;
    }
    .swal-btn.primary { background: #2d68d8; color: #fff; }
    .swal-btn.primary:hover { background: #1f56bf; }
    .swal-btn.secondary { background: #f2f5fb; color: #44546a; border: 1px solid #d5deea; }
    .swal-btn.secondary:hover { background: #e9eef7; }

    @media (max-width: 768px) {
      .page-container { width: calc(100% - 24px); }
      .hero-banner { height: 170px; margin-bottom: 16px; }
      .hero-title { font-size: 26px; }
      .hero-subtitle { font-size: 14px; }
      .products-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 12px; }
      .product-card { min-height: 330px; }
      .product-image { height: 140px; flex-basis: 140px; }
      .product-info { padding: 12px; }
      .product-name { font-size: 14px; min-height: 38px; }
      .product-price { font-size: 18px; }
      .sort-select { margin-left: 0; width: 100%; }
      .modal-main-grid { grid-template-columns: 1fr; }
      .modal-preview { position: static; }
      .variants-section {
        padding: 10px;
      }
      .variant-edit-row {
        grid-template-columns: 1fr;
        gap: 8px;
        padding: 8px;
      }
      .variant-edit-row .variant-name,
      .variant-edit-row .variant-price,
      .variant-edit-row .variant-stock,
      .variant-edit-row .variant-images-cell {
        grid-column: 1;
      }
      .variant-images-cell {
        flex-direction: column;
        align-items: flex-start;
        gap: 8px;
      }
      .variant-image-preview {
        width: 56px;
        height: 56px;
      }
      .main-images-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
      }
    }

    @media (max-width: 520px) {
      .variant-edit-row { padding: 7px; }
      .variant-image-input-wrap .variant-image {
        width: 100%;
        font-size: 11px;
      }
      .main-images-grid {
        grid-template-columns: 1fr;
      }
    }
  </style>
</head>
<body>
  <div class="page-container">
    <div class="page-header">
      <div class="back-arrow" onclick="window.location.href='admin_dashboard.php'">‹</div>
      <div class="header-title">My Products</div>
      <div class="header-meta">Updated <?php echo date('d/m/Y H:i:s'); ?></div>
      <div class="topbar-menu">
        <button type="button" class="menu-trigger" onclick="toggleTopbarMenu(event)">...</button>
        <div class="menu-dropdown" id="topbarMenuDropdown">
          <a href="admin_dashboard.php">Admin Dashboard</a>
           <a href="messages.php">Messages</a>
          <a href="admin_orders.php">Admin Orders</a>
          <a href="admin_my_products.php">My Products</a>
          <a href="admin_product_drafts.php">Product Drafts</a>
          <a href="admin_my_products.php?view=archived">Archived Products</a>
          <a href="admin_manage_reviews.php">Manage Reviews</a>
         
          <a href="logout.php">Logout</a>
        </div>
      </div>
    </div>

    <div class="filters">
      <div class="view-switch">
        <a href="admin_my_products.php" class="<?php echo !$isArchivedView ? 'active' : ''; ?>">Active</a>
        <a href="admin_my_products.php?view=archived" class="<?php echo $isArchivedView ? 'active' : ''; ?>">Archived</a>
      </div>
      <input type="text" id="searchInput" placeholder="Search by product name..." style="flex: 1; max-width: 300px; border: 1px solid #ddd; border-radius: 8px; padding: 10px 12px; font-size: 14px; background: #fff;" onkeyup="applySearch()">
      <div id="categoryButtons" style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;">
        <button class="filter-btn active" data-category="all" onclick="filterByCategory('all')">All</button>
      </div>
      <button id="moreCategoriesBtn" class="filter-btn more-cats-btn" style="display:none;">More Categories ▼</button>
      <div id="moreCategoriesContainer" class="more-categories-dropdown"></div>

      <select class="sort-select" id="sortSelect" onchange="sortProducts(this.value)">
        <option value="">Sort</option>
        <option value="price-low">Price Low-High</option>
        <option value="price-high">Price High-Low</option>
        <option value="rating">Rating</option>
        <option value="stock-high">Stock High-Low</option>
        <option value="stock-low">Stock Low-High</option>
      </select>
    </div>

    <div class="products-grid" id="productsGrid"></div>
    <div id="emptyState" class="empty-state" style="display:none;">No products found for this filter.</div>
  </div>

  <div class="product-modal" id="productModal">
    <div class="product-modal-card">
      <div class="product-modal-header">
        <span>Product Manager</span>
        <button class="product-modal-close" onclick="closeProductModal()">×</button>
      </div>
      <div class="product-modal-content">
        <div class="modal-main-grid">
          <div class="modal-preview">
            <img id="modalImage" src="" alt="Product" class="product-modal-image">
            <h3 id="previewName"></h3>
            <div id="previewRating" class="product-modal-meta"></div>
            <div id="previewPrice" class="product-modal-price"></div>
            <div id="previewStock" class="product-modal-meta"></div>
            <div id="previewOrders" class="product-modal-meta"></div>
            <div id="previewCategory" class="product-modal-meta"></div>
            <div id="previewStatus" class="product-modal-meta"></div>
            <p id="previewDesc" class="product-modal-desc"></p>
          </div>

          <div class="modal-workspace">
            <div class="modal-tabs">
              <button type="button" class="modal-tab active" data-tab="reviews" onclick="switchModalTab('reviews')">User Reviews</button>
              <button type="button" class="modal-tab" data-tab="edit" onclick="switchModalTab('edit')">Edit Product</button>
            </div>

            <div id="reviewsPanel" class="modal-panel active">
              <div class="reviews-header-box">
                <strong>Customer Feedback</strong>
                <span id="reviewsCountBadge">0 reviews</span>
              </div>
              <div id="reviewsContent" class="reviews-loading">Loading reviews...</div>
            </div>

            <div id="editPanel" class="modal-panel">
              <form id="productEditForm" class="edit-form" onsubmit="saveProductChanges(event)">
                <input type="hidden" id="editProductId" value="0">

                <div class="edit-form-group">
                  <label for="editProductName">Product Name</label>
                  <input type="text" id="editProductName" required>
                </div>

                <div class="edit-form-group">
                  <label for="editProductPrice">Price</label>
                  <input type="number" id="editProductPrice" min="0" step="0.01" required>
                </div>

                <div class="edit-form-group">
                  <label for="editProductStock">Quantity</label>
                  <input type="number" id="editProductStock" min="0" step="1" required>
                </div>

                <div class="edit-form-group">
                  <label for="editProductCategory">Category</label>
                  <select id="editProductCategory" required></select>
                </div>

                <div class="edit-form-group">
                  <label for="editProductDesc">Product Description</label>
                  <textarea id="editProductDesc" placeholder="Enter product description"></textarea>
                </div>

                <div class="edit-form-group">
                  <label for="editProductImage">Add Main Product Images</label>
                  <input type="file" id="editProductImage" accept="image/*" multiple>
                  <small style="color:#777;font-size:12px;">Add more images for this main product. Maximum total is 8 images.</small>
                </div>

                <div class="edit-form-group">
                  <label for="editProductVideo">Product Video (0/1)</label>
                  <input type="file" id="editProductVideo" accept="video/mp4,video/webm,video/quicktime">
                  <small style="color:#777;font-size:12px;">Optional. Upload MP4, WEBM, or MOV. Selecting a new file replaces the current video.</small>
                  <div id="productVideoStatus" style="font-size:12px;color:#666;margin-top:6px;">No video uploaded.</div>
                  <div id="productVideoPreviewWrap" style="display:none;margin-top:8px;">
                    <video id="productVideoPreview" controls style="width:100%;max-height:240px;border:1px solid #eee;border-radius:8px;background:#f7f7f7;"></video>
                  </div>
                  <div style="margin-top:8px;">
                    <button type="button" id="videoToggleBtn" class="edit-btn" onclick="toggleProductVideoRemoval()" style="display:none;">Remove Existing Video</button>
                  </div>
                </div>

                <div class="main-images-section">
                  <div class="variants-section-title">Main Product Images</div>
                  <div class="variants-section-help">Pin one image as primary, remove unwanted ones, and keep up to 8 images total.</div>
                  <div id="mainImageLimitText" class="main-image-limit">0 / 8 images</div>
                  <div id="mainImagesGrid" class="main-images-grid"></div>
                  <div id="mainImagesEmpty" class="main-image-empty" style="display:none;">No main images selected. Add at least one image.</div>
                </div>

                <div id="variantsEditSection" class="variants-section" style="display:none;">
                  <div class="variants-section-header">
                    <div class="variants-section-title">Edit Variants</div>
                    <button type="button" id="addVariantBtn" class="add-variant-btn">Add New Variant</button>
                  </div>
                  <div class="variants-section-help">Upload an image only for variants you want to change.</div>
                  <div id="variantsEditRows"></div>
                </div>

                <div class="edit-actions">
                  <button type="button" id="archiveToggleBtn" class="edit-btn archive" onclick="toggleProductArchive()">Archive Product</button>
                  <button type="button" class="edit-btn" onclick="closeProductModal()">Cancel</button>
                  <button type="submit" class="edit-btn primary">Save Changes</button>
                </div>
              </form>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div id="localSwal" class="swal-overlay" role="dialog" aria-modal="true" aria-live="polite">
    <div class="swal-card">
      <div id="localSwalIcon" class="swal-icon success">✓</div>
      <div id="localSwalTitle" class="swal-title">Success</div>
      <div id="localSwalText" class="swal-text"></div>
      <div id="localSwalActions" class="swal-actions">
        <button id="localSwalCancel" type="button" class="swal-btn secondary" style="display:none;">Cancel</button>
        <button id="localSwalConfirm" type="button" class="swal-btn primary">OK</button>
      </div>
    </div>
  </div>


  <script src="assets/js/user_dashboard_reusable_ui.js?v=20260331-1"></script>
  <script>
    const isArchivedView = <?php echo $isArchivedView ? 'true' : 'false'; ?>;
    let products = [];
    let filteredProducts = [];
    let currentCategory = 'all';
    let currentSearchQuery = '';
    let currentSortMode = '';
    let categoryOptions = [];
    let activeModalTab = 'reviews';
    let reviewMediaMap = {};
    let currentEditingVariants = [];
    let currentMainImages = [];
    let newMainImageFiles = [];
    let pinnedMainImageKey = '';
    let currentVideoUrl = '';
    let removeExistingVideo = false;
    let pendingVideoPreviewUrl = '';
    let newVariantTempCounter = 1;
    let pendingMainVariantSelection = '';

    function revokeVariantImageUrls() {
      currentEditingVariants.forEach((variant) => {
        const images = Array.isArray(variant.newImages) ? variant.newImages : [];
        images.forEach((item) => {
          if (item && item.previewUrl) {
            URL.revokeObjectURL(item.previewUrl);
          }
        });
      });
    }

    function revokePendingVideoPreview() {
      if (pendingVideoPreviewUrl) {
        URL.revokeObjectURL(pendingVideoPreviewUrl);
        pendingVideoPreviewUrl = '';
      }
    }

    function renderVideoManager() {
      const input = document.getElementById('editProductVideo');
      const status = document.getElementById('productVideoStatus');
      const wrap = document.getElementById('productVideoPreviewWrap');
      const player = document.getElementById('productVideoPreview');
      const toggleBtn = document.getElementById('videoToggleBtn');
      if (!input || !status || !wrap || !player || !toggleBtn) {
        return;
      }

      const selectedFile = input.files && input.files[0] ? input.files[0] : null;
      if (selectedFile) {
        revokePendingVideoPreview();
        pendingVideoPreviewUrl = URL.createObjectURL(selectedFile);
        player.src = pendingVideoPreviewUrl;
        wrap.style.display = 'block';
        status.textContent = 'New video selected. It will replace the current video when saved.';
        toggleBtn.style.display = 'inline-block';
        toggleBtn.textContent = 'Clear Selected Video';
        return;
      }

      revokePendingVideoPreview();
      player.src = '';

      if (currentVideoUrl && !removeExistingVideo) {
        player.src = currentVideoUrl;
        wrap.style.display = 'block';
        status.textContent = 'Current product video is set.';
        toggleBtn.style.display = 'inline-block';
        toggleBtn.textContent = 'Remove Existing Video';
        return;
      }

      wrap.style.display = 'none';
      if (currentVideoUrl && removeExistingVideo) {
        status.textContent = 'Current video will be removed when you save changes.';
        toggleBtn.style.display = 'inline-block';
        toggleBtn.textContent = 'Undo Remove Video';
      } else {
        status.textContent = 'No video uploaded.';
        toggleBtn.style.display = currentVideoUrl ? 'inline-block' : 'none';
        toggleBtn.textContent = 'Remove Existing Video';
      }
    }

    function toggleProductVideoRemoval() {
      const input = document.getElementById('editProductVideo');
      if (!input) return;

      if (input.files && input.files[0]) {
        input.value = '';
        renderVideoManager();
        return;
      }

      if (currentVideoUrl) {
        removeExistingVideo = !removeExistingVideo;
        renderVideoManager();
      }
    }

    function openLocalSweetAlert(options = {}) {
      return new Promise((resolve) => {
        const overlay = document.getElementById('localSwal');
        const icon = document.getElementById('localSwalIcon');
        const titleEl = document.getElementById('localSwalTitle');
        const textEl = document.getElementById('localSwalText');
        const actions = document.getElementById('localSwalActions');
        const confirmBtn = document.getElementById('localSwalConfirm');
        const cancelBtn = document.getElementById('localSwalCancel');

        if (!overlay || !icon || !titleEl || !textEl || !actions || !confirmBtn || !cancelBtn) {
          resolve(true);
          return;
        }

        const type = options.type || 'success';
        const hasCancel = !!options.showCancel;
        icon.className = `swal-icon ${type}`;
        icon.textContent = type === 'error' ? '!' : (type === 'warning' ? '?' : '✓');
        titleEl.textContent = options.title || 'Notice';
        textEl.textContent = options.text || '';

        confirmBtn.textContent = options.confirmText || 'OK';
        cancelBtn.textContent = options.cancelText || 'Cancel';
        cancelBtn.style.display = hasCancel ? 'inline-block' : 'none';
        actions.className = hasCancel ? 'swal-actions two' : 'swal-actions';

        const cleanup = () => {
          overlay.classList.remove('show');
          confirmBtn.onclick = null;
          cancelBtn.onclick = null;
          overlay.onclick = null;
        };

        confirmBtn.onclick = () => {
          cleanup();
          resolve(true);
        };
        cancelBtn.onclick = () => {
          cleanup();
          resolve(false);
        };
        overlay.onclick = (event) => {
          if (event.target === overlay) {
            cleanup();
            resolve(false);
          }
        };

        overlay.classList.add('show');
      });
    }

    function localAlert(type, title, text) {
      return openLocalSweetAlert({ type, title, text, showCancel: false, confirmText: 'OK' });
    }

    function localConfirm(title, text, confirmText = 'Yes', cancelText = 'Cancel') {
      return openLocalSweetAlert({ type: 'warning', title, text, showCancel: true, confirmText, cancelText });
    }

    function toggleTopbarMenu(event) {
      event.stopPropagation();
      const dropdown = document.getElementById('topbarMenuDropdown');
      if (dropdown) {
        dropdown.classList.toggle('active');
      }
    }

    function updateEmptyState() {
      const grid = document.getElementById('productsGrid');
      const emptyState = document.getElementById('emptyState');
      if (!grid || !emptyState) {
        return;
      }
      emptyState.style.display = grid.children.length === 0 ? 'block' : 'none';
    }

    function escapeHtml(value) {
      return String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');
    }

    function formatReviewDate(value) {
      if (!value) return '-';
      const parsed = new Date(String(value).replace(' ', 'T'));
      if (Number.isNaN(parsed.getTime())) return value;
      return parsed.toLocaleString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
      });
    }

    function formatPeso(value) {
      const amount = Number(value || 0);
      if (!Number.isFinite(amount)) return '0';
      return amount.toLocaleString('en-PH', { minimumFractionDigits: 0, maximumFractionDigits: 2 });
    }

    function renderReusableReviewMediaNode(media, variantClass = '') {
      if (window.DashboardReusableUI && typeof window.DashboardReusableUI.renderReviewMediaNode === 'function') {
        return window.DashboardReusableUI.renderReviewMediaNode(media, variantClass);
      }

      if (!media || !media.url) return '';
      const safeClass = variantClass || '';
      if ((media.media_type || '').includes('video/')) {
        return `<video src="${media.url}" class="review-image ${safeClass}" controls></video>`;
      }
      return `<img src="${media.url}" alt="Review image" class="review-image ${safeClass}">`;
    }

    function switchReviewMedia(reviewId, mediaIndex) {
      const list = reviewMediaMap[reviewId] || [];
      const media = list[mediaIndex];
      if (!media) return;

      const main = document.getElementById(`reviewMediaMain-${reviewId}`);
      if (main) {
        main.innerHTML = renderReusableReviewMediaNode(media, 'review-media-main');
      }

      const thumbs = document.querySelectorAll(`.review-media-thumb[data-review-id="${reviewId}"]`);
      thumbs.forEach((thumb, idx) => {
        thumb.classList.toggle('active', idx === mediaIndex);
      });
    }

    function switchModalTab(tabName) {
      activeModalTab = tabName;
      document.querySelectorAll('.modal-tab').forEach((tab) => {
        tab.classList.toggle('active', tab.dataset.tab === tabName);
      });
      const reviewsPanel = document.getElementById('reviewsPanel');
      const editPanel = document.getElementById('editPanel');
      if (reviewsPanel) {
        reviewsPanel.classList.toggle('active', tabName === 'reviews');
      }
      if (editPanel) {
        editPanel.classList.toggle('active', tabName === 'edit');
      }
    }

    async function loadProductReviews(productId) {
      const content = document.getElementById('reviewsContent');
      const badge = document.getElementById('reviewsCountBadge');
      if (!content || !badge) {
        return;
      }

      content.className = 'reviews-loading';
      content.textContent = 'Loading reviews...';
      badge.textContent = '0 reviews';
      reviewMediaMap = {};

      try {
        const res = await fetch(`api/get-reviews.php?product_id=${encodeURIComponent(productId)}`);
        const data = await res.json();
        if (!res.ok || !data.success) {
          throw new Error(data.message || 'Failed to load reviews');
        }

        const reviews = Array.isArray(data.reviews) ? data.reviews : [];
        badge.textContent = `${reviews.length} review${reviews.length === 1 ? '' : 's'}`;

        if (reviews.length === 0) {
          content.className = 'reviews-empty';
          content.textContent = 'No reviews yet for this product.';
          return;
        }

        content.className = 'review-list';
        content.innerHTML = reviews.map((review) => {
          const rating = Number(review.rating) || 0;
          const stars = '★'.repeat(Math.max(0, Math.min(5, rating))) + '☆'.repeat(Math.max(0, 5 - Math.max(0, Math.min(5, rating))));
          const mediaFiles = Array.isArray(review.media_files) ? review.media_files : [];
          let mediaHtml = '';

          if (mediaFiles.length > 0) {
            const mediaList = mediaFiles.map((file) => ({
              url: file.url || (file.media_id ? `api/get-review-media.php?media_id=${file.media_id}` : `api/get-review-media.php?review_id=${review.review_id}`),
              media_type: file.media_type || review.media_type || ''
            }));

            reviewMediaMap[review.review_id] = mediaList;

            if (mediaList.length > 1) {
              const thumbsHtml = mediaList.map((media, idx) => {
                if ((media.media_type || '').includes('video/')) {
                  return `<button type="button" class="review-media-thumb review-media-video-thumb ${idx === 0 ? 'active' : ''}" data-review-id="${review.review_id}" onclick="switchReviewMedia(${review.review_id}, ${idx})">VIDEO</button>`;
                }
                return `<img src="${escapeHtml(media.url)}" class="review-media-thumb ${idx === 0 ? 'active' : ''}" data-review-id="${review.review_id}" onclick="switchReviewMedia(${review.review_id}, ${idx})" alt="review-thumb-${idx}">`;
              }).join('');

              mediaHtml = `
                <div class="review-media-group">
                  <div id="reviewMediaMain-${review.review_id}">${renderReusableReviewMediaNode(mediaList[0], 'review-media-main')}</div>
                  <div class="review-media-thumbs">${thumbsHtml}</div>
                </div>
              `;
            } else {
              mediaHtml = renderReusableReviewMediaNode(mediaList[0], 'review-media-single');
            }
          } else if (review.has_media) {
            const mediaUrl = `api/get-review-media.php?review_id=${review.review_id}`;
            mediaHtml = renderReusableReviewMediaNode(
              { url: mediaUrl, media_type: review.media_type || '' },
              'review-media-single'
            );
          }

          return `
            <div class="review-item">
              <div class="review-top">
                <div class="review-user">${escapeHtml(review.user_name || 'Anonymous User')}</div>
                <div class="review-date">${escapeHtml(formatReviewDate(review.created_at))}</div>
              </div>
              <div class="review-stars">${stars}</div>
              <div class="review-text">${escapeHtml(review.review_text || 'No written feedback.')}</div>
              ${mediaHtml}
            </div>
          `;
        }).join('');
      } catch (error) {
        content.className = 'reviews-empty';
        content.textContent = error.message || 'Unable to load reviews right now.';
      }
    }

    function filterByCategory(categoryName) {
      currentCategory = categoryName;
      document.querySelectorAll('.filter-btn').forEach((btn) => btn.classList.remove('active'));
      const activeBtn = document.querySelector(`[data-category="${categoryName}"]`);
      if (activeBtn) {
        activeBtn.classList.add('active');
      }

      applySearch();
    }

    function applySearch() {
      const query = (document.getElementById('searchInput')?.value || '').toLowerCase().trim();
      currentSearchQuery = query;

      if (currentCategory === 'all') {
        filteredProducts = products.filter((p) => {
          const matchesQuery = !query || p.name.toLowerCase().includes(query) || (p.desc || '').toLowerCase().includes(query);
          return matchesQuery;
        });
      } else {
        filteredProducts = products.filter((p) => {
          const matchesCategory = p.categoryName === currentCategory;
          const matchesQuery = !query || p.name.toLowerCase().includes(query) || (p.desc || '').toLowerCase().includes(query);
          return matchesCategory && matchesQuery;
        });
      }

      const sortValue = document.getElementById('sortSelect')?.value || '';
      renderProducts(applySort([...filteredProducts], sortValue));
    }

    function applySort(list, sortBy) {
      if (!sortBy) {
        return list;
      }

      if (sortBy === 'price-low') {
        return list.sort((a, b) => Number(a.price) - Number(b.price));
      }
      if (sortBy === 'price-high') {
        return list.sort((a, b) => Number(b.price) - Number(a.price));
      }
      if (sortBy === 'rating') {
        return list.sort((a, b) => Number(b.rating) - Number(a.rating));
      }
      if (sortBy === 'stock-high') {
        return list.sort((a, b) => Number(b.stock) - Number(a.stock));
      }
      if (sortBy === 'stock-low') {
        return list.sort((a, b) => Number(a.stock) - Number(b.stock));
      }
      return list;
    }

    function sortProducts(sortBy) {
      const sortValue = sortBy || '';
      currentSortMode = sortValue;
      renderProducts(applySort([...filteredProducts], sortValue));
    }

    function renderProducts(productsToRender = filteredProducts) {
      const grid = document.getElementById('productsGrid');
      if (!grid) {
        return;
      }

      const groupedProductsMap = new Map();
      productsToRender.forEach((p) => {
        const productId = Number(p.id);
        const parentId = p.parent_product_id ? Number(p.parent_product_id) : null;
        const mainProductId = parentId || productId;
        if (!groupedProductsMap.has(mainProductId)) {
          groupedProductsMap.set(mainProductId, []);
        }
        groupedProductsMap.get(mainProductId).push(p);
      });

      const groupedCards = Array.from(groupedProductsMap.values()).map((groupItems) => {
        const mainProduct = groupItems.find((item) => !item.parent_product_id) || groupItems[0];
        const stockValues = groupItems.map((item) => Number(item.stock || 0));
        const totalStock = stockValues.reduce((sum, value) => sum + value, 0);
        const inStock = stockValues.some((value) => value > 0);
        return {
          ...mainProduct,
          variantCount: groupItems.length > 1 ? groupItems.length : 0,
          groupStock: totalStock,
          isGroupOutOfStock: !inStock,
          isArchived: groupItems.some((item) => Number(item.archived || 0) === 1),
          groupOrderCount: groupItems.reduce((sum, item) => sum + Number(item.orderCount || 0), 0)
        };
      });

      const stockSorted = currentSortMode === 'stock-low'
        ? groupedCards
        : groupedCards.sort((a, b) => {
            if (a.isGroupOutOfStock && !b.isGroupOutOfStock) return 1;
            if (!a.isGroupOutOfStock && b.isGroupOutOfStock) return -1;
            return 0;
          });

      grid.innerHTML = stockSorted.map((p) => {
        const isOutOfStock = p.isGroupOutOfStock;
        const avgRating = (Number(p.rating) || 0).toFixed(1);
        const image = Array.isArray(p.image) && p.image.length ? p.image[0] : 'https://via.placeholder.com/900x600?text=No+Image';
        const stockColor = isOutOfStock ? '#e22a39' : '#27ae60';
        const isArchived = Number(p.archived || 0) === 1 || p.isArchived;
        const cardOpacity = (isOutOfStock || isArchived) ? 'opacity: 0.6;' : '';

        return `
          <div class="product-card" data-id="${p.id}" onclick="openProductModal(${p.id})" style="cursor: pointer; ${cardOpacity}">
            <div class="product-image" style="position: relative;">
              <img src="${image}" alt="${p.name}" class="main-img">
              ${isArchived ? '<span class="product-archived-badge">Archived</span>' : ''}
              ${p.variantCount > 0 ? `<span class="product-variant-badge">${p.variantCount} options</span>` : ''}
              ${isOutOfStock ? '<div style="position: absolute; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); display: flex; align-items: center; justify-content: center; border-radius: 8px;"><span style="color: white; font-weight: bold; font-size: 16px;">Out of Stock</span></div>' : ''}
            </div>
            <div class="product-info">
              <div class="product-name">${p.name}</div>
              <div class="product-rating">⭐ ${avgRating} <span style="color: #999; font-size: 13px;">(${p.reviewCount} reviews)</span></div>
              <div class="product-price">₱${formatPeso(p.price)}${p.originalPrice ? '<span style="font-size:16px;color:#999;text-decoration:line-through;margin-left:8px;">₱' + formatPeso(p.originalPrice) + '</span>' : ''}</div>
              <div style="margin-top: 8px; font-size: 13px; color: ${stockColor}; font-weight: 600;">Stock: ${p.groupStock}</div>
              <div style="margin-top: 4px; font-size: 13px; color: #666; font-weight: 600;">Orders: ${Number(p.groupOrderCount || 0)}</div>
            </div>
          </div>
        `;
      }).join('');

      updateEmptyState();
    }

    function extractBaseProductName(fullName) {
      if (!fullName || typeof fullName !== 'string') return fullName;
      const lastDash = fullName.lastIndexOf(' - ');
      return lastDash > 0 ? fullName.substring(0, lastDash) : fullName;
    }

    function loadVariantsForProduct(baseProductId, baseName, categoryId) {
      // Build variant family from parent_product_id relationship.
      const productId = Number(baseProductId);
      const selectedProduct = products.find((p) => Number(p.id) === productId);
      if (!selectedProduct) return [];

      const mainProductId = selectedProduct.parent_product_id
        ? Number(selectedProduct.parent_product_id)
        : productId;

      const family = products.filter((p) => {
        const pId = Number(p.id);
        const pParentId = p.parent_product_id ? Number(p.parent_product_id) : null;
        return pId === mainProductId || pParentId === mainProductId;
      });

      return family.sort((a, b) => {
        if (!a.parent_product_id) return -1;
        if (!b.parent_product_id) return 1;
        return Number(a.price || 0) - Number(b.price || 0);
      });
    }

    function buildVariantRowMarkup(variant) {
      const variantId = Number(variant.id || 0);
      const tempId = Number(variant.tempId || 0);
      const displayPrice = Number(variant.price || 0).toFixed(2);
      const displayStock = Math.floor(Number(variant.stock || 0));
      const selectionKey = variantId > 0 ? `id:${variantId}` : `temp:${tempId}`;
      const isMainSelection = pendingMainVariantSelection === selectionKey;
      const mainActionLabel = isMainSelection ? 'Main Product Selected' : 'Set as Main Product';

      return `
        <div class="variant-edit-row">
          <input type="hidden" class="variant-id" value="${variantId}">
          <input type="hidden" class="variant-temp-id" value="${tempId}">
          <input type="text" class="variant-name" placeholder="Variant Name" value="${escapeHtml(variant.name || '')}">
          <input type="number" class="variant-price" placeholder="Price" min="0" step="0.01" value="${displayPrice}">
          <input type="number" class="variant-stock" placeholder="Stock" min="0" step="1" value="${displayStock}">
          <div class="variant-images-cell" data-variant-temp-id="${tempId}">
            <div class="variant-image-input-wrap">
              <input type="file" class="variant-image" accept="image/*" multiple title="Add variant images">
              <small class="variant-image-note">Keep 1 to 8 images, choose one pinned, and remove unwanted ones.</small>
            </div>
            <div class="variant-image-count">0 / 8 images</div>
            <div class="variant-images-grid"></div>
            <div class="variant-row-actions">
              <button type="button" class="variant-main-btn ${isMainSelection ? 'active' : ''}" data-main-selection="${escapeHtml(selectionKey)}">${mainActionLabel}</button>
              ${variant.isNew ? '<button type="button" class="variant-remove-row-btn">Remove Variant</button>' : ''}
            </div>
          </div>
        </div>
      `;
    }

    function addNewVariantRow() {
      const baseName = extractBaseProductName((document.getElementById('editProductName')?.value || '').trim()) || 'Variant';
      const tempId = newVariantTempCounter++;

      currentEditingVariants.push({
        id: 0,
        tempId,
        isNew: true,
        name: `${baseName} - Variant ${tempId}`,
        price: 0,
        stock: 0,
        images: [],
        newImages: [],
        pinnedKey: ''
      });

      renderVariantsEditSectionFromState();
    }

    function renderVariantsEditSectionFromState() {
      const rows = document.getElementById('variantsEditRows');
      if (!rows) return;

      rows.innerHTML = currentEditingVariants.map((variant) => buildVariantRowMarkup(variant)).join('');

      rows.querySelectorAll('.variant-edit-row').forEach((row) => {
        const tempId = Number(row.querySelector('.variant-temp-id')?.value || 0);
        renderVariantImageManager(tempId, row);

        const fileInput = row.querySelector('.variant-image');
        if (fileInput) {
          fileInput.addEventListener('change', () => handleVariantImageInputChange(tempId, row, fileInput));
        }

        const removeBtn = row.querySelector('.variant-remove-row-btn');
        if (removeBtn) {
          removeBtn.addEventListener('click', () => {
            const index = currentEditingVariants.findIndex((variant) => Number(variant.tempId) === tempId);
            if (index === -1) return;
            const removedVariant = currentEditingVariants.splice(index, 1)[0];
            const removedSelectionKey = Number(removedVariant.id || 0) > 0
              ? `id:${Number(removedVariant.id)}`
              : `temp:${Number(removedVariant.tempId || 0)}`;
            if (pendingMainVariantSelection === removedSelectionKey) {
              pendingMainVariantSelection = '';
            }
            (removedVariant.newImages || []).forEach((item) => {
              if (item && item.previewUrl) {
                URL.revokeObjectURL(item.previewUrl);
              }
            });
            renderVariantsEditSectionFromState();
          });
        }

        const mainBtn = row.querySelector('.variant-main-btn');
        if (mainBtn) {
          mainBtn.addEventListener('click', () => {
            const selection = String(mainBtn.getAttribute('data-main-selection') || '').trim();
            if (!selection) return;
            pendingMainVariantSelection = pendingMainVariantSelection === selection ? '' : selection;
            renderVariantsEditSectionFromState();
          });
        }
      });
    }

    function renderVariantsEditSection(variants) {
      const section = document.getElementById('variantsEditSection');
      const rows = document.getElementById('variantsEditRows');
      const addBtn = document.getElementById('addVariantBtn');
      if (!section || !rows) return;

      revokeVariantImageUrls();

      const childVariants = Array.isArray(variants)
        ? variants.filter((v) => Number(v.parent_product_id || 0) > 0)
        : [];

      section.style.display = 'block';
      pendingMainVariantSelection = '';
      currentEditingVariants = childVariants.map((v) => ({
        id: Number(v.id),
        tempId: Number(v.id),
        isNew: false,
        name: v.name || '',
        price: Number(v.price || 0),
        stock: Number(v.stock || 0),
        images: normalizeVariantImages(v.image),
        newImages: [],
        pinnedKey: normalizeVariantImages(v.image).length > 0 ? 'e:0' : ''
      }));

      if (addBtn) {
        addBtn.onclick = addNewVariantRow;
      }

      renderVariantsEditSectionFromState();
    }

    function normalizeVariantImages(imageValue) {
      const fallbackPrefix = 'https://via.placeholder.com/';
      let urls = [];
      if (Array.isArray(imageValue)) {
        urls = imageValue;
      } else if (typeof imageValue === 'string') {
        urls = [imageValue];
      }
      return urls
        .map((url) => String(url || '').trim())
        .filter((url) => url && !url.startsWith(fallbackPrefix))
        .map((url) => ({ url, deleted: false }));
    }

    function findEditingVariantByTempId(tempId) {
      return currentEditingVariants.find((variant) => Number(variant.tempId) === Number(tempId)) || null;
    }

    function getVariantActiveImageCount(variant) {
      if (!variant) return 0;
      const existingCount = (variant.images || []).filter((item) => !item.deleted).length;
      const newCount = (variant.newImages || []).length;
      return existingCount + newCount;
    }

    function applyVariantPinnedFallback(variant) {
      if (!variant) return;
      const pinnedKey = variant.pinnedKey || '';

      const isPinnedValid = (() => {
        if (!pinnedKey) return false;
        if (pinnedKey.startsWith('e:')) {
          const index = Number(pinnedKey.slice(2));
          return Number.isInteger(index) && variant.images[index] && !variant.images[index].deleted;
        }
        if (pinnedKey.startsWith('n:')) {
          const index = Number(pinnedKey.slice(2));
          return Number.isInteger(index) && !!variant.newImages[index];
        }
        return false;
      })();

      if (isPinnedValid) return;

      const firstExistingIndex = (variant.images || []).findIndex((item) => !item.deleted);
      if (firstExistingIndex >= 0) {
        variant.pinnedKey = `e:${firstExistingIndex}`;
        return;
      }

      if ((variant.newImages || []).length > 0) {
        variant.pinnedKey = 'n:0';
        return;
      }

      variant.pinnedKey = '';
    }

    function renderVariantImageManager(tempId, row) {
      const variant = findEditingVariantByTempId(tempId);
      if (!variant || !row) return;

      const grid = row.querySelector('.variant-images-grid');
      const countEl = row.querySelector('.variant-image-count');
      if (!grid || !countEl) return;

      applyVariantPinnedFallback(variant);

      const cards = [];
      (variant.images || []).forEach((item, index) => {
        const key = `e:${index}`;
        const checked = variant.pinnedKey === key ? 'checked' : '';
        const removedClass = item.deleted ? 'removed' : '';
        const buttonLabel = item.deleted ? 'Undo' : 'Remove';
        cards.push(`
          <div class="variant-image-card ${removedClass}">
            ${checked && !item.deleted ? '<span class="variant-image-badge">Pinned</span>' : ''}
            <img src="${escapeHtml(item.url)}" alt="Variant image ${index + 1}">
            <div class="variant-image-actions">
              <label class="variant-image-pin">
                <input type="radio" name="variantPinned_${tempId}" value="${key}" ${checked} ${item.deleted ? 'disabled' : ''}>
                <span>Pin</span>
              </label>
              <button type="button" class="variant-image-remove-btn" data-existing-index="${index}">${buttonLabel}</button>
            </div>
          </div>
        `);
      });

      (variant.newImages || []).forEach((item, index) => {
        const key = `n:${index}`;
        const checked = variant.pinnedKey === key ? 'checked' : '';
        cards.push(`
          <div class="variant-image-card">
            ${checked ? '<span class="variant-image-badge">Pinned</span>' : ''}
            <img src="${escapeHtml(item.previewUrl)}" alt="New variant image ${index + 1}">
            <div class="variant-image-actions">
              <label class="variant-image-pin">
                <input type="radio" name="variantPinned_${tempId}" value="${key}" ${checked}>
                <span>Pin</span>
              </label>
              <button type="button" class="variant-image-remove-btn" data-new-index="${index}">Remove</button>
            </div>
          </div>
        `);
      });

      grid.innerHTML = cards.join('');
      countEl.textContent = `${getVariantActiveImageCount(variant)} / 8 images`;

      grid.querySelectorAll(`input[name="variantPinned_${tempId}"]`).forEach((radio) => {
        radio.addEventListener('change', () => {
          if (!radio.checked) return;
          variant.pinnedKey = radio.value;
          renderVariantImageManager(tempId, row);
        });
      });

      grid.querySelectorAll('[data-existing-index]').forEach((btn) => {
        btn.addEventListener('click', () => {
          const index = Number(btn.getAttribute('data-existing-index'));
          if (!Number.isInteger(index) || !variant.images[index]) return;
          variant.images[index].deleted = !variant.images[index].deleted;
          applyVariantPinnedFallback(variant);
          renderVariantImageManager(tempId, row);
        });
      });

      grid.querySelectorAll('[data-new-index]').forEach((btn) => {
        btn.addEventListener('click', () => {
          const index = Number(btn.getAttribute('data-new-index'));
          if (!Number.isInteger(index) || !variant.newImages[index]) return;
          const removed = variant.newImages.splice(index, 1)[0];
          if (removed && removed.previewUrl) {
            URL.revokeObjectURL(removed.previewUrl);
          }
          applyVariantPinnedFallback(variant);
          renderVariantImageManager(tempId, row);
        });
      });
    }

    async function handleVariantImageInputChange(tempId, row, input) {
      const variant = findEditingVariantByTempId(tempId);
      if (!variant || !input) return;

      const selectedFiles = Array.from(input.files || []);
      input.value = '';
      if (selectedFiles.length === 0) return;

      const remainingSlots = 8 - getVariantActiveImageCount(variant);
      if (remainingSlots <= 0) {
        await localAlert('warning', 'Image Limit Reached', 'Each variant can keep up to 8 images only.');
        renderVariantImageManager(tempId, row);
        return;
      }

      const filesToAdd = selectedFiles.slice(0, remainingSlots);
      filesToAdd.forEach((file) => {
        variant.newImages.push({
          file,
          previewUrl: URL.createObjectURL(file)
        });
      });

      if (selectedFiles.length > filesToAdd.length) {
        await localAlert('warning', 'Image Limit Reached', 'Only images within the 8-image limit were added for this variant.');
      }

      applyVariantPinnedFallback(variant);
      renderVariantImageManager(tempId, row);
    }

    function getPrimaryImage(imageValue) {
      const fallback = 'https://via.placeholder.com/80x80?text=No+Image';

      if (Array.isArray(imageValue) && imageValue.length > 0) {
        return String(imageValue[0] || fallback);
      }

      if (typeof imageValue === 'string') {
        const trimmed = imageValue.trim();
        if (!trimmed) {
          return fallback;
        }

        if (trimmed.startsWith('[') || trimmed.startsWith('{')) {
          try {
            const parsed = JSON.parse(trimmed);
            if (Array.isArray(parsed) && parsed.length > 0) {
              return String(parsed[0] || fallback);
            }
            if (parsed && typeof parsed === 'object' && Array.isArray(parsed.image) && parsed.image.length > 0) {
              return String(parsed.image[0] || fallback);
            }
          } catch (error) {
            return fallback;
          }
        }

        return trimmed;
      }

      if (imageValue && typeof imageValue === 'object' && Array.isArray(imageValue.image) && imageValue.image.length > 0) {
        return String(imageValue.image[0] || fallback);
      }

      return fallback;
    }

    function normalizeMainImages(imageList) {
      const placeholderPrefix = 'https://via.placeholder.com/';
      if (!Array.isArray(imageList)) return [];
      return imageList
        .map((url) => String(url || '').trim())
        .filter((url) => url && !url.startsWith(placeholderPrefix));
    }

    function getActiveMainImageCount() {
      const existingCount = currentMainImages.filter((item) => !item.deleted).length;
      return existingCount + newMainImageFiles.length;
    }

    function applyPinnedMainImageFallback() {
      const hasCurrentPinned = (() => {
        if (!pinnedMainImageKey) return false;
        if (pinnedMainImageKey.startsWith('e:')) {
          const idx = Number(pinnedMainImageKey.slice(2));
          return Number.isInteger(idx) && currentMainImages[idx] && !currentMainImages[idx].deleted;
        }
        if (pinnedMainImageKey.startsWith('n:')) {
          const idx = Number(pinnedMainImageKey.slice(2));
          return Number.isInteger(idx) && !!newMainImageFiles[idx];
        }
        return false;
      })();

      if (hasCurrentPinned) return;

      const firstExistingIndex = currentMainImages.findIndex((item) => !item.deleted);
      if (firstExistingIndex >= 0) {
        pinnedMainImageKey = `e:${firstExistingIndex}`;
        return;
      }

      if (newMainImageFiles.length > 0) {
        pinnedMainImageKey = 'n:0';
        return;
      }

      pinnedMainImageKey = '';
    }

    function renderMainImagesManager() {
      const grid = document.getElementById('mainImagesGrid');
      const emptyState = document.getElementById('mainImagesEmpty');
      const limitText = document.getElementById('mainImageLimitText');
      if (!grid || !emptyState || !limitText) return;

      applyPinnedMainImageFallback();

      const cards = [];

      currentMainImages.forEach((item, index) => {
        const key = `e:${index}`;
        const checked = pinnedMainImageKey === key ? 'checked' : '';
        const removedClass = item.deleted ? 'removed' : '';
        const buttonLabel = item.deleted ? 'Undo Remove' : 'Remove';
        const buttonTitle = item.deleted ? 'Restore this image' : 'Mark this image for deletion';
        cards.push(`
          <div class="main-image-card ${removedClass}">
            ${checked && !item.deleted ? '<span class="main-image-badge">Pinned</span>' : ''}
            <img src="${escapeHtml(item.url)}" alt="Main image ${index + 1}">
            <div class="main-image-actions">
              <label class="main-image-pin">
                <input type="radio" name="mainPinnedImage" value="${key}" ${checked} ${item.deleted ? 'disabled' : ''}>
                <span>Pin image</span>
              </label>
              <button type="button" class="main-image-remove-btn" data-existing-index="${index}" title="${buttonTitle}">${buttonLabel}</button>
            </div>
          </div>
        `);
      });

      newMainImageFiles.forEach((item, index) => {
        const key = `n:${index}`;
        const checked = pinnedMainImageKey === key ? 'checked' : '';
        cards.push(`
          <div class="main-image-card">
            ${checked ? '<span class="main-image-badge">Pinned</span>' : ''}
            <img src="${escapeHtml(item.previewUrl)}" alt="New main image ${index + 1}">
            <div class="main-image-actions">
              <label class="main-image-pin">
                <input type="radio" name="mainPinnedImage" value="${key}" ${checked}>
                <span>Pin image</span>
              </label>
              <button type="button" class="main-image-remove-btn" data-new-index="${index}" title="Remove this new image">Remove</button>
            </div>
          </div>
        `);
      });

      grid.innerHTML = cards.join('');
      emptyState.style.display = cards.length === 0 ? 'block' : 'none';
      limitText.textContent = `${getActiveMainImageCount()} / 8 images`;

      grid.querySelectorAll('input[name="mainPinnedImage"]').forEach((radio) => {
        radio.addEventListener('change', () => {
          if (!radio.checked) return;
          pinnedMainImageKey = radio.value;
          renderMainImagesManager();
        });
      });

      grid.querySelectorAll('[data-existing-index]').forEach((btn) => {
        btn.addEventListener('click', () => {
          const idx = Number(btn.getAttribute('data-existing-index'));
          if (!Number.isInteger(idx) || !currentMainImages[idx]) return;
          currentMainImages[idx].deleted = !currentMainImages[idx].deleted;
          applyPinnedMainImageFallback();
          renderMainImagesManager();
        });
      });

      grid.querySelectorAll('[data-new-index]').forEach((btn) => {
        btn.addEventListener('click', () => {
          const idx = Number(btn.getAttribute('data-new-index'));
          if (!Number.isInteger(idx) || !newMainImageFiles[idx]) return;
          const removed = newMainImageFiles.splice(idx, 1)[0];
          if (removed && removed.previewUrl) {
            URL.revokeObjectURL(removed.previewUrl);
          }
          applyPinnedMainImageFallback();
          renderMainImagesManager();
        });
      });
    }

    async function handleMainImageInputChange(event) {
      const input = event && event.target ? event.target : null;
      if (!input) return;
      const pickedFiles = Array.from(input.files || []);
      input.value = '';
      if (pickedFiles.length === 0) return;

      const remainingSlots = 8 - getActiveMainImageCount();
      if (remainingSlots <= 0) {
        await localAlert('warning', 'Image Limit Reached', 'You can keep up to 8 main images only.');
        return;
      }

      const filesToAdd = pickedFiles.slice(0, remainingSlots);
      filesToAdd.forEach((file) => {
        newMainImageFiles.push({
          file,
          previewUrl: URL.createObjectURL(file)
        });
      });

      if (pickedFiles.length > filesToAdd.length) {
        await localAlert('warning', 'Image Limit Reached', 'Only images within the 8-image limit were added.');
      }

      applyPinnedMainImageFallback();
      renderMainImagesManager();
    }

    function fillCategoryDropdown(selectedCategoryId) {
      const categorySelect = document.getElementById('editProductCategory');
      if (!categorySelect) {
        return;
      }

      if (!Array.isArray(categoryOptions) || categoryOptions.length === 0) {
        categorySelect.innerHTML = '<option value="">No categories available</option>';
        return;
      }

      categorySelect.innerHTML = categoryOptions.map((cat) => {
        const catId = Number(cat.category_id);
        const selected = Number(selectedCategoryId) === catId ? 'selected' : '';
        return `<option value="${catId}" ${selected}>${cat.category_name}</option>`;
      }).join('');
    }

    async function openProductModal(id) {
      const product = products.find((p) => Number(p.id) === Number(id));
      if (!product) {
        return;
      }

      if (categoryOptions.length === 0) {
        await loadCategories();
      }

      const image = Array.isArray(product.image) && product.image.length ? product.image[0] : 'https://via.placeholder.com/900x600?text=No+Image';
      document.getElementById('modalImage').src = image;
      document.getElementById('previewName').textContent = product.name || 'Product';
      document.getElementById('previewRating').textContent = `⭐ ${(Number(product.rating) || 0).toFixed(1)} (${product.reviewCount || 0} reviews)`;
      document.getElementById('previewPrice').textContent = `₱${formatPeso(product.price)}`;
      document.getElementById('previewStock').textContent = `Stock: ${Number(product.stock || 0)}`;
      document.getElementById('previewOrders').textContent = `Orders: ${Number(product.orderCount || 0)}`;
      document.getElementById('previewCategory').textContent = `Category: ${product.categoryName || 'N/A'}`;
      document.getElementById('previewStatus').textContent = `Status: ${Number(product.archived || 0) === 1 ? 'Archived (hidden from users)' : 'Active (visible to users)'}`;
      document.getElementById('previewDesc').textContent = product.desc || 'No description available.';
      document.getElementById('editProductId').value = String(product.id);
      document.getElementById('editProductName').value = product.name || '';
      document.getElementById('editProductPrice').value = Number(product.price || 0).toFixed(2);
      document.getElementById('editProductStock').value = Number(product.stock || 0);
      document.getElementById('editProductDesc').value = product.desc || '';
      const imageInput = document.getElementById('editProductImage');
      if (imageInput) {
        imageInput.value = '';
      }
      currentMainImages = normalizeMainImages(product.image).map((url) => ({ url, deleted: false }));
      newMainImageFiles.forEach((item) => {
        if (item && item.previewUrl) {
          URL.revokeObjectURL(item.previewUrl);
        }
      });
      newMainImageFiles = [];
      pinnedMainImageKey = currentMainImages.length > 0 ? 'e:0' : '';
      renderMainImagesManager();
      currentVideoUrl = String(product.video_url || '').trim();
      removeExistingVideo = false;
      const videoInput = document.getElementById('editProductVideo');
      if (videoInput) {
        videoInput.value = '';
      }
      renderVideoManager();
      const archiveToggleBtn = document.getElementById('archiveToggleBtn');
      if (archiveToggleBtn) {
        const archived = Number(product.archived || 0) === 1;
        archiveToggleBtn.textContent = archived ? 'Restore Product' : 'Archive Product';
        archiveToggleBtn.classList.toggle('restore', archived);
      }
      fillCategoryDropdown(product.category);
      
      const variants = loadVariantsForProduct(product.id, product.name, product.category);
      renderVariantsEditSection(variants);
      
      switchModalTab('reviews');
      await loadProductReviews(product.id);
      document.getElementById('productModal').classList.add('active');
    }

    async function saveProductChanges(event) {
      event.preventDefault();

      const productId = Number(document.getElementById('editProductId').value || 0);
      const productName = (document.getElementById('editProductName').value || '').trim();
      const price = document.getElementById('editProductPrice').value;
      const quantity = document.getElementById('editProductStock').value;
      const categoryId = Number(document.getElementById('editProductCategory').value || 0);
      const description = (document.getElementById('editProductDesc').value || '').trim();

      if (!productId || !productName) {
        await localAlert('warning', 'Missing Product Name', 'Product name is required.');
        return;
      }

      if (price === '' || Number(price) < 0) {
        await localAlert('warning', 'Invalid Price', 'Price must be a non-negative number.');
        return;
      }

      if (quantity === '' || Number(quantity) < 0) {
        await localAlert('warning', 'Invalid Quantity', 'Quantity must be a non-negative number.');
        return;
      }

      if (!categoryId) {
        await localAlert('warning', 'Category Required', 'Please select a category.');
        return;
      }

      try {
        const productVideoInput = document.getElementById('editProductVideo');
        const newVideoFile = productVideoInput && productVideoInput.files ? productVideoInput.files[0] : null;

        const activeMainImageCount = getActiveMainImageCount();
        if (activeMainImageCount < 1) {
          await localAlert('warning', 'Main Image Required', 'Please keep at least one main product image.');
          return;
        }
        if (activeMainImageCount > 8) {
          await localAlert('warning', 'Image Limit Reached', 'You can keep up to 8 main images only.');
          return;
        }

        const variantsToUpdate = [];
        const newVariantsToCreate = [];
        const variantImageUpdates = [];
        if (Array.isArray(currentEditingVariants) && currentEditingVariants.length > 0) {
          const variantRows = document.querySelectorAll('.variant-edit-row');
          variantRows.forEach((row) => {
            const variantId = Number(row.querySelector('.variant-id')?.value || 0);
            const variantTempId = Number(row.querySelector('.variant-temp-id')?.value || 0);
            const variantName = (row.querySelector('.variant-name')?.value || '').trim();
            const variantPrice = row.querySelector('.variant-price')?.value || '0';
            const variantStock = row.querySelector('.variant-stock')?.value || '0';

            if (!variantName) {
              throw new Error('Each variant must have a name.');
            }

            if (!variantPrice || Number(variantPrice) < 0) {
              throw new Error('All variant prices must be non-negative numbers.');
            }

            if (!variantStock || Number(variantStock) < 0) {
              throw new Error('All variant stocks must be non-negative numbers.');
            }

            const variantState = findEditingVariantByTempId(variantTempId);
            if (!variantState) {
              throw new Error('Variant image state is missing. Please reopen the product modal and try again.');
            }

            const activeVariantImageCount = getVariantActiveImageCount(variantState);
            if (activeVariantImageCount < 1) {
              throw new Error('Each variant must have at least one image.');
            }
            if (activeVariantImageCount > 8) {
              throw new Error('Each variant can keep up to 8 images only.');
            }

            const variantPayload = {
              name: variantName,
              price: Number(variantPrice).toFixed(2),
              stock: Math.floor(Number(variantStock))
            };

            if (variantId > 0) {
              variantsToUpdate.push({ id: variantId, ...variantPayload });
            } else {
              newVariantsToCreate.push({ temp_id: variantTempId, ...variantPayload });
            }

            applyVariantPinnedFallback(variantState);
            const removedExisting = (variantState.images || [])
              .filter((item) => item.deleted)
              .map((item) => item.url);

            let pinnedSource = '';
            let pinnedExistingUrl = '';
            let pinnedNewImageIndex = -1;
            const pinnedKey = String(variantState.pinnedKey || '');
            if (pinnedKey.startsWith('e:')) {
              const index = Number(pinnedKey.slice(2));
              if (Number.isInteger(index) && variantState.images[index] && !variantState.images[index].deleted) {
                pinnedSource = 'existing';
                pinnedExistingUrl = variantState.images[index].url;
              }
            } else if (pinnedKey.startsWith('n:')) {
              const index = Number(pinnedKey.slice(2));
              if (Number.isInteger(index) && variantState.newImages[index]) {
                pinnedSource = 'new';
                pinnedNewImageIndex = index;
              }
            }

            const variantImagePayload = {
              removed_existing_images: removedExisting,
              pinned_source: pinnedSource,
              pinned_existing_url: pinnedExistingUrl,
              pinned_new_image_index: pinnedNewImageIndex
            };

            if (variantId > 0) {
              variantImageUpdates.push({ id: variantId, ...variantImagePayload });
            } else {
              variantImageUpdates.push({ temp_id: variantTempId, ...variantImagePayload });
            }
          });
        }

        const body = new FormData();
        body.append('product_id', String(productId));
        body.append('product_name', productName);
        body.append('price', String(Number(price).toFixed(2)));
        body.append('product_stock', String(Math.floor(Number(quantity))));
        body.append('product_description', description);
        body.append('category_id', String(categoryId));
        if (variantsToUpdate.length > 0) {
          body.append('variants', JSON.stringify(variantsToUpdate));
        }
        if (newVariantsToCreate.length > 0) {
          body.append('new_variants', JSON.stringify(newVariantsToCreate));
        }
        body.append('variant_image_updates', JSON.stringify(variantImageUpdates));
        body.append('switch_main_variant', pendingMainVariantSelection);
        body.append('remove_existing_video', removeExistingVideo ? '1' : '0');
        body.append('existing_video_url', currentVideoUrl || '');
        if (newVideoFile) {
          body.append('product_video', newVideoFile);
        }

        const removedExistingImages = currentMainImages
          .filter((item) => item.deleted)
          .map((item) => item.url);
        body.append('removed_existing_images', JSON.stringify(removedExistingImages));

        if (pinnedMainImageKey.startsWith('e:')) {
          const pinnedExistingIndex = Number(pinnedMainImageKey.slice(2));
          const pinnedExisting = currentMainImages[pinnedExistingIndex];
          if (pinnedExisting && !pinnedExisting.deleted) {
            body.append('pinned_main_image_source', 'existing');
            body.append('pinned_main_image_url', pinnedExisting.url);
          }
        } else if (pinnedMainImageKey.startsWith('n:')) {
          const pinnedNewIndex = Number(pinnedMainImageKey.slice(2));
          if (Number.isInteger(pinnedNewIndex) && newMainImageFiles[pinnedNewIndex]) {
            body.append('pinned_main_image_source', 'new');
            body.append('pinned_new_image_index', String(pinnedNewIndex));
          }
        }

        newMainImageFiles.forEach((item) => {
          if (item && item.file) {
            body.append('additional_main_images[]', item.file);
          }
        });

        currentEditingVariants.forEach((variant) => {
          const variantId = Number(variant.id || 0);
          const variantTempId = Number(variant.tempId || 0);
          const newImages = Array.isArray(variant.newImages) ? variant.newImages : [];
          newImages.forEach((item) => {
            if (item && item.file) {
              if (variantId > 0) {
                body.append(`variant_additional_images_${variantId}[]`, item.file);
              } else if (variantTempId > 0) {
                body.append(`variant_additional_images_new_${variantTempId}[]`, item.file);
              }
            }
          });
        });

        const response = await fetch('api/update-product-admin.php', {
          method: 'POST',
          body
        });

        const data = await response.json();
        if (!response.ok || !data.success) {
          throw new Error(data.error || 'Failed to update product.');
        }

        const selectedCategory = categoryOptions.find((cat) => Number(cat.category_id) === categoryId);
        const categoryName = selectedCategory ? selectedCategory.category_name : '';
        const finalMainImages = Array.isArray(data.main_images) && data.main_images.length > 0
          ? data.main_images
          : currentMainImages.filter((item) => !item.deleted).map((item) => item.url).concat(newMainImageFiles.map((item) => item.previewUrl));

        products = products.map((product) => {
          if (Number(product.id) !== productId) {
            for (const variant of variantsToUpdate) {
              if (Number(product.id) === variant.id) {
                return {
                  ...product,
                  price: variant.price,
                  stock: variant.stock
                };
              }
            }
            return product;
          }
          return {
            ...product,
            name: productName,
            price: Number(price).toFixed(2),
            stock: Math.floor(Number(quantity)),
            desc: description,
            category: categoryId,
            categoryName,
            image: finalMainImages.length > 0 ? finalMainImages : product.image,
            video_url: typeof data.video_url === 'string' ? data.video_url : ''
          };
        });

        const updated = products.find((product) => Number(product.id) === productId);
        if (updated) {
          document.getElementById('previewName').textContent = updated.name || 'Product';
          document.getElementById('previewPrice').textContent = `₱${formatPeso(updated.price)}`;
          document.getElementById('previewStock').textContent = `Stock: ${Number(updated.stock || 0)}`;
          document.getElementById('previewCategory').textContent = `Category: ${updated.categoryName || 'N/A'}`;
          document.getElementById('previewDesc').textContent = updated.desc || 'No description available.';
        }

        closeProductModal();
        await loadProducts();
        filterByCategory(currentCategory);
        await localAlert('success', 'Saved', 'Product updated successfully.');
      } catch (error) {
        await localAlert('error', 'Update Failed', error.message || 'Failed to update product.');
      }
    }

    async function toggleProductArchive() {
      const productId = Number(document.getElementById('editProductId').value || 0);
      if (!productId) {
        await localAlert('error', 'Invalid Product', 'Invalid product selected.');
        return;
      }

      const product = products.find((p) => Number(p.id) === productId);
      if (!product) {
        await localAlert('error', 'Not Found', 'Product not found.');
        return;
      }

      const isArchived = Number(product.archived || 0) === 1;
      const nextArchiveState = isArchived ? 0 : 1;
      const confirmMessage = isArchived
        ? 'Restore this product family so it is visible to users again?'
        : 'Archive this product family so it is hidden from users?';

      const confirmed = await localConfirm(
        isArchived ? 'Restore Product Family' : 'Archive Product Family',
        confirmMessage,
        isArchived ? 'Restore' : 'Archive',
        'Cancel'
      );
      if (!confirmed) {
        return;
      }

      try {
        const payload = new URLSearchParams();
        payload.append('product_id', String(productId));
        payload.append('archive', String(nextArchiveState));

        const response = await fetch('api/toggle-product-archive.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
          },
          body: payload.toString()
        });

        const data = await response.json();
        if (!response.ok || !data.success) {
          throw new Error(data.error || 'Failed to update archive status.');
        }

        await loadProducts();
        filterByCategory(currentCategory);
        closeProductModal();
        await localAlert(
          'success',
          nextArchiveState === 1 ? 'Archived' : 'Restored',
          nextArchiveState === 1 ? 'Product archived successfully.' : 'Product restored successfully.'
        );
      } catch (error) {
        await localAlert('error', 'Archive Update Failed', error.message || 'Failed to update archive status.');
      }
    }

    function closeProductModal() {
      const modal = document.getElementById('productModal');
      if (modal) {
        modal.classList.remove('active');
      }
      revokePendingVideoPreview();
      revokeVariantImageUrls();
      currentEditingVariants = [];
      pendingMainVariantSelection = '';
    }

    async function loadProducts() {
      try {
        const res = await fetch('api/get-products.php?include_archived=1');
        if (!res.ok) {
          throw new Error('Failed to load products');
        }
        const data = await res.json();
        const allProducts = data.map((p) => {
          const reviewCount = Number(p.reviewCount) || 0;
          return {
            id: p.id,
            parent_product_id: p.parent_product_id || null,
            archived: Number(p.archived || 0),
            name: p.name,
            price: Number(p.price || 0).toFixed(2),
            originalPrice: p.original_price ? Number(p.original_price).toFixed(2) : null,
            image: p.image || ['https://via.placeholder.com/900x600?text=No+Image'],
            rating: reviewCount > 0 ? (Number(p.rating) || 0) : 0,
            reviewCount,
            orderCount: Number(p.orderCount) || 0,
            category: p.category,
            categoryName: p.categoryName || '',
            video_url: p.video_url || '',
            stock: Number(p.stock) || 0,
            desc: p.desc || ''
          };
        });

        products = allProducts.filter((p) => isArchivedView ? Number(p.archived) === 1 : Number(p.archived) === 0);

        filteredProducts = [...products];
        renderProducts(filteredProducts);
      } catch (error) {
        const grid = document.getElementById('productsGrid');
        if (grid) {
          grid.innerHTML = `<div class="empty-state" style="grid-column:1 / -1;">Unable to load products right now.</div>`;
        }
      }
    }

    async function loadCategories() {
      try {
        const res = await fetch('api/get-categories.php');
        if (!res.ok) {
          throw new Error('Failed to load categories');
        }
        const data = await res.json();
        if (!data.success || !Array.isArray(data.categories)) {
          return;
        }

        categoryOptions = data.categories;

        const buttonsContainer = document.getElementById('categoryButtons');
        const moreButton = document.getElementById('moreCategoriesBtn');
        const moreCategoriesContainer = document.getElementById('moreCategoriesContainer');
        if (!buttonsContainer || !moreButton || !moreCategoriesContainer) {
          return;
        }

        buttonsContainer.innerHTML = '';
        moreCategoriesContainer.innerHTML = '';

        const allBtn = document.createElement('button');
        allBtn.className = 'filter-btn active';
        allBtn.dataset.category = 'all';
        allBtn.textContent = 'All';
        allBtn.onclick = () => filterByCategory('all');
        buttonsContainer.appendChild(allBtn);

        data.categories.slice(0, 4).forEach((cat) => {
          const btn = document.createElement('button');
          btn.className = 'filter-btn';
          btn.dataset.category = cat.category_name;
          btn.textContent = cat.category_name;
          btn.onclick = () => filterByCategory(cat.category_name);
          buttonsContainer.appendChild(btn);
        });

        if (data.categories.length > 4) {
          moreButton.style.display = 'inline-block';
          moreButton.textContent = 'More Categories ▼';
          data.categories.slice(4).forEach((cat) => {
            const extraBtn = document.createElement('button');
            extraBtn.className = 'filter-btn';
            extraBtn.dataset.category = cat.category_name;
            extraBtn.textContent = cat.category_name;
            extraBtn.onclick = () => {
              filterByCategory(cat.category_name);
              moreCategoriesContainer.classList.remove('active');
              moreButton.textContent = 'More Categories ▼';
            };
            moreCategoriesContainer.appendChild(extraBtn);
          });
          moreButton.onclick = () => {
            const active = moreCategoriesContainer.classList.toggle('active');
            moreButton.textContent = active ? 'More Categories ▲' : 'More Categories ▼';
          };
        } else {
          moreButton.style.display = 'none';
          moreCategoriesContainer.classList.remove('active');
        }
      } catch (error) {
      }
    }

    document.getElementById('productModal').addEventListener('click', (event) => {
      if (event.target.id === 'productModal') {
        closeProductModal();
      }
    });

    document.addEventListener('click', (event) => {
      const dropdown = document.getElementById('topbarMenuDropdown');
      const menu = document.querySelector('.topbar-menu');
      if (dropdown && menu && !menu.contains(event.target)) {
        dropdown.classList.remove('active');
      }

      const moreButton = document.getElementById('moreCategoriesBtn');
      const moreDropdown = document.getElementById('moreCategoriesContainer');
      if (moreButton && moreDropdown && !moreButton.contains(event.target) && !moreDropdown.contains(event.target)) {
        moreDropdown.classList.remove('active');
        moreButton.textContent = 'More Categories ▼';
      }
    });

    const mainImageInput = document.getElementById('editProductImage');
    if (mainImageInput) {
      mainImageInput.addEventListener('change', handleMainImageInputChange);
    }

    const productVideoInput = document.getElementById('editProductVideo');
    if (productVideoInput) {
      productVideoInput.addEventListener('change', () => {
        removeExistingVideo = false;
        renderVideoManager();
      });
    }

    loadCategories();
    loadProducts();
  </script>
</body>
</html>