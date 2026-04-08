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
  <title>Auction House</title>
  <link rel="stylesheet" href="main.css">
  <link rel="stylesheet" href="assets/css/user_dashboard_checkout.css?v=20260407-1">
  <link rel="stylesheet" href="assets/css/local_swal.css">
  <script src="assets/js/local_swal.js"></script>
  <style>
    :root {
      --bg: #05060a;
      --bg-soft: #0b0d14;
      --panel: rgba(255, 255, 255, 0.06);
      --panel-strong: rgba(255, 255, 255, 0.1);
      --line: rgba(255, 255, 255, 0.1);
      --text: #f4f7fb;
      --muted: rgba(244, 247, 251, 0.72);
      --accent: #fbbf24;
      --accent-2: #ef4444;
      --good: #34d399;
      --shadow: 0 22px 80px rgba(0, 0, 0, 0.42);
    }

    * { box-sizing: border-box; }
    html {
      scroll-behavior: smooth;
      background: #05060a;
    }
    body {
      margin: 0;
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      background: #05060a;
      color: var(--text);
      min-height: 100vh;
      overflow-x: auto;
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
      mask-image: linear-gradient(180deg, rgba(0,0,0,0.8), transparent 90%);
      opacity: 0.35;
    }

    .wrap {
      position: relative;
      z-index: 1;
      min-height: 100vh;
    }

    .topbar {
      position: fixed;
      top: 0;
      left: 0;
      right: 0;
      z-index: 20;
      backdrop-filter: blur(20px);
      background: rgba(5, 6, 10, 0.78);
    }

    main {
      padding-top: 0;
    }

    .topbar-inner {
      max-width: 1320px;
      margin: 0 auto;
      padding: 14px 20px;
      display: flex;
      align-items: center;
      gap: 16px;
    }

    .brand {
      display: flex;
      align-items: center;
      gap: 12px;
      min-width: 0;
    }

    .brand-mark {
      width: 42px;
      height: 42px;
      border-radius: 14px;
      display: grid;
      place-items: center;
      background: linear-gradient(135deg, rgba(251, 191, 36, 0.18), rgba(239, 68, 68, 0.18));
      border: 1px solid rgba(255, 255, 255, 0.12);
      box-shadow: inset 0 1px 0 rgba(255,255,255,0.12);
      color: var(--accent);
      flex: 0 0 auto;
    }

    .brand-title {
      font-size: 18px;
      font-weight: 800;
      letter-spacing: 0.08em;
      text-transform: uppercase;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }

    .brand-subtitle {
      font-size: 12px;
      color: var(--muted);
      margin-top: 2px;
    }

    .top-actions {
      margin-left: auto;
      display: flex;
      align-items: center;
      gap: 10px;
      flex-wrap: wrap;
    }

    .chip, .nav-link {
      border: 1px solid rgba(255,255,255,0.1);
      background: rgba(255,255,255,0.04);
      color: var(--text);
      border-radius: 999px;
      padding: 10px 14px;
      text-decoration: none;
      font-size: 13px;
      font-weight: 700;
      cursor: pointer;
      transition: transform 0.2s ease, background 0.2s ease, border-color 0.2s ease;
      white-space: nowrap;
    }
    .chip:hover, .nav-link:hover {
      transform: translateY(-1px);
      border-color: rgba(251, 191, 36, 0.45);
      background: rgba(251, 191, 36, 0.08);
    }
    .nav-link.active {
      border-color: rgba(251, 191, 36, 0.5);
      background: rgba(251, 191, 36, 0.12);
      color: #fff;
    }

    .hero {
      position: relative;
      min-height: calc(100vh - 74px);
      display: grid;
      align-items: center;
      padding-top: var(--topbar-offset, 74px);
      overflow: hidden;
      border-bottom: 1px solid var(--line);
    }

    .hero-media {
      position: absolute;
      inset: 0;
    }

    .hero-media img {
      width: 100%;
      height: 100%;
      object-fit: cover;
      display: block;
      transform: scale(1.01);
      filter: saturate(1.22) contrast(1.18) brightness(1.16);
    }

    .hero-media::after {
      content: '';
      position: absolute;
      inset: 0;
      background:
        linear-gradient(90deg, rgba(5,6,10,0.78) 0%, rgba(5,6,10,0.48) 34%, rgba(5,6,10,0.06) 72%, rgba(5,6,10,0.42) 100%),
        linear-gradient(180deg, rgba(5,6,10,0.08), rgba(5,6,10,0.58));
    }

    .hero-inner {
      position: relative;
      max-width: none;
      width: 100%;
      min-width: 1120px;
      margin: 0;
      padding: clamp(36px, 6vh, 64px) 20px;
      display: grid;
      grid-template-columns: minmax(0, 1.2fr) 360px;
      gap: 24px;
      align-items: center;
    }

    .hero-copy-wrap {
      align-self: center;
    }

    .eyebrow {
      display: inline-flex;
      align-items: center;
      gap: 10px;
      padding: 10px 14px;
      border-radius: 999px;
      background: rgba(239, 68, 68, 0.92);
      color: #fff;
      font-size: 12px;
      font-weight: 800;
      letter-spacing: 0.08em;
      text-transform: uppercase;
      width: fit-content;
      box-shadow: 0 12px 30px rgba(239, 68, 68, 0.28);
      animation: floatIn 0.7s ease both;
    }

    .eyebrow-dot {
      width: 8px;
      height: 8px;
      border-radius: 999px;
      background: #fff;
      box-shadow: 0 0 0 0 rgba(255,255,255,0.6);
      animation: pulse 1.8s infinite;
    }

    .hero-title {
      margin: 18px 0 14px;
      font-size: clamp(42px, 7vw, 86px);
      line-height: 0.95;
      letter-spacing: -0.04em;
      max-width: 9ch;
      animation: rise 0.8s ease 0.1s both;
    }

    .hero-copy {
      max-width: 680px;
      color: var(--muted);
      font-size: 18px;
      line-height: 1.65;
      margin-bottom: 22px;
      animation: rise 0.8s ease 0.2s both;
    }

    .hero-actions {
      display: flex;
      gap: 12px;
      flex-wrap: wrap;
      margin-bottom: 22px;
      animation: rise 0.8s ease 0.3s both;
    }

    .primary-btn, .secondary-btn {
      border: 0;
      border-radius: 14px;
      padding: 14px 18px;
      font-size: 14px;
      font-weight: 800;
      cursor: pointer;
      transition: transform 0.2s ease, box-shadow 0.2s ease, background 0.2s ease;
      text-decoration: none;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 10px;
    }
    .primary-btn {
      background: linear-gradient(135deg, #fbbf24, #f59e0b);
      color: #0b1020;
      box-shadow: 0 18px 40px rgba(251, 191, 36, 0.28);
    }
    .primary-btn:hover { transform: translateY(-2px) scale(1.01); }
    .secondary-btn {
      background: rgba(255,255,255,0.06);
      color: #fff;
      border: 1px solid rgba(255,255,255,0.14);
    }
    .secondary-btn:hover { transform: translateY(-2px); background: rgba(255,255,255,0.1); }

    .hero-stats {
      display: grid;
      grid-template-columns: repeat(3, minmax(0, 1fr));
      gap: 14px;
      max-width: 680px;
      animation: rise 0.8s ease 0.4s both;
    }

    .stat {
      padding: 14px;
      border-radius: 16px;
      background: rgba(255,255,255,0.06);
      border: 1px solid rgba(255,255,255,0.1);
      backdrop-filter: blur(12px);
    }
    .stat-label {
      color: rgba(255,255,255,0.62);
      font-size: 12px;
      margin-bottom: 8px;
      text-transform: uppercase;
      letter-spacing: 0.08em;
      font-weight: 700;
    }
    .stat-value {
      font-size: 20px;
      font-weight: 800;
      letter-spacing: -0.02em;
    }
    .stat-sub {
      margin-top: 8px;
      color: rgba(255,255,255,0.66);
      font-size: 12px;
      line-height: 1.4;
    }

    .hero-panel {
      align-self: start;
      background: rgba(4, 6, 12, 0.72);
      border: 1px solid rgba(255,255,255,0.1);
      border-radius: 24px;
      padding: 20px;
      box-shadow: var(--shadow);
      backdrop-filter: blur(18px);
      display: grid;
      gap: 16px;
      animation: rise 0.8s ease 0.25s both;
    }

    .panel-top {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 12px;
    }

    .panel-badge {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      padding: 8px 12px;
      border-radius: 999px;
      background: rgba(52, 211, 153, 0.12);
      color: #9af0c8;
      font-size: 12px;
      font-weight: 800;
      text-transform: uppercase;
      letter-spacing: 0.08em;
    }

    .panel-image {
      width: 100%;
      height: 220px;
      border-radius: 18px;
      overflow: hidden;
      background: linear-gradient(135deg, rgba(255,255,255,0.06), rgba(255,255,255,0.02));
      border: 1px solid rgba(255,255,255,0.08);
      position: relative;
    }

    .panel-image::after {
      content: '';
      position: absolute;
      inset: 0;
      pointer-events: none;
      background:
        linear-gradient(120deg, rgba(255,255,255,0.24) 0%, rgba(255,255,255,0.05) 24%, rgba(255,255,255,0) 48%),
        radial-gradient(120% 70% at 80% 0%, rgba(255,225,140,0.16), rgba(255,225,140,0) 55%);
      mix-blend-mode: screen;
    }

    .panel-image img,
    .panel-image video {
      width: 100%;
      height: 100%;
      object-fit: cover;
      display: block;
      filter: saturate(1.24) contrast(1.18) brightness(1.14);
    }

    .panel-name {
      font-size: 30px;
      line-height: 1.05;
      font-weight: 800;
      letter-spacing: -0.03em;
      margin: 0;
    }
    .panel-desc {
      color: var(--muted);
      font-size: 15px;
      line-height: 1.6;
      margin: 0;
    }

    .panel-grid {
      display: grid;
      grid-template-columns: repeat(2, minmax(0, 1fr));
      gap: 12px;
    }

    .mini-box {
      border-radius: 16px;
      background: rgba(255,255,255,0.05);
      border: 1px solid rgba(255,255,255,0.08);
      padding: 14px;
    }
    .mini-label {
      color: rgba(255,255,255,0.6);
      font-size: 12px;
      text-transform: uppercase;
      letter-spacing: 0.08em;
      margin-bottom: 8px;
      font-weight: 700;
    }
    .mini-value {
      font-size: 20px;
      font-weight: 800;
    }
    .mini-help {
      color: rgba(255,255,255,0.58);
      font-size: 12px;
      margin-top: 8px;
      line-height: 1.45;
    }

    .section {
      max-width: 1320px;
      margin: 0 auto;
      padding: 34px 20px 48px;
    }

    #upcomingSection.section {
      max-width: none;
      margin: 0;
      padding: 0;
    }

    .upcoming-tone {
      background: linear-gradient(180deg, rgba(8,10,16,0.92), rgba(6,8,14,0.88));
      border-top: 1px solid rgba(255,255,255,0.08);
      border-bottom: 1px solid rgba(255,255,255,0.08);
      border-left: none;
      border-right: none;
      border-radius: 0;
      padding: 26px 20px 36px;
      box-shadow: 0 18px 48px rgba(0,0,0,0.34);
      margin-top: 0;
      width: 100%;
    }

    #upcomingSection .section-head,
    #upcomingSection .auction-grid,
    #upcomingSection .empty {
      max-width: 1320px;
      min-width: 1080px;
      margin-left: auto;
      margin-right: auto;
      padding-left: 20px;
      padding-right: 20px;
      box-sizing: border-box;
    }

    .section-head {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 16px;
      margin-bottom: 20px;
      flex-wrap: wrap;
    }

    .section-title {
      font-size: clamp(30px, 3.4vw, 48px);
      font-weight: 800;
      margin: 0;
      letter-spacing: -0.03em;
      color: #f8fafc;
    }
    .section-subtitle {
      color: #aeb6c7;
      margin-top: 8px;
      max-width: 58ch;
      line-height: 1.6;
    }

    .view-all-link {
      color: #ffb020;
      text-decoration: none;
      font-weight: 800;
      font-size: 18px;
      letter-spacing: -0.02em;
      transition: opacity 0.2s ease;
      white-space: nowrap;
    }
    .view-all-link:hover {
      opacity: 0.85;
    }

    .toolbar {
      display: flex;
      align-items: center;
      gap: 10px;
      flex-wrap: wrap;
    }

    .search {
      width: min(360px, 70vw);
      border: 1px solid rgba(255,255,255,0.12);
      border-radius: 14px;
      background: rgba(255,255,255,0.05);
      color: #fff;
      padding: 12px 14px;
      outline: none;
      font-size: 14px;
    }
    .search::placeholder { color: rgba(255,255,255,0.45); }

    .filter {
      border: 1px solid rgba(255,255,255,0.12);
      border-radius: 14px;
      background: rgba(255,255,255,0.05);
      color: #fff;
      padding: 12px 14px;
      outline: none;
      font-size: 14px;
    }

    .auction-grid {
      display: grid;
      grid-template-columns: repeat(3, minmax(320px, 1fr));
      gap: 22px;
    }

    .auction-card {
      position: relative;
      overflow: hidden;
      border-radius: 18px;
      background: #171922;
      border: 1px solid rgba(255,255,255,0.1);
      box-shadow: 0 16px 44px rgba(0, 0, 0, 0.32);
      transition: transform 0.25s ease, border-color 0.25s ease;
      min-height: 100%;
    }
    .auction-card:hover {
      transform: translateY(-4px);
      border-color: rgba(245, 158, 11, 0.45);
    }

    .auction-image {
      position: relative;
      height: 320px;
      background: linear-gradient(135deg, rgba(255,255,255,0.06), rgba(255,255,255,0.02));
    }
    .auction-image img {
      width: 100%;
      height: 100%;
      object-fit: cover;
      display: block;
      filter: saturate(1.2) contrast(1.16) brightness(1.13);
    }
    .auction-image::after {
      content: '';
      position: absolute;
      inset: 0;
      background:
        linear-gradient(120deg, rgba(255,255,255,0.2) 0%, rgba(255,255,255,0.05) 22%, rgba(255,255,255,0) 44%),
        linear-gradient(180deg, rgba(8,10,18,0) 0%, rgba(8,10,18,0.2) 62%, rgba(20,22,30,0.72) 100%);
      mix-blend-mode: screen, normal;
    }

    .card-body {
      position: relative;
      margin-top: -122px;
      padding: 14px 22px 20px;
      display: grid;
      gap: 12px;
      z-index: 2;
    }

    .card-topline {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 10px;
    }

    .status-pill {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      width: auto;
      border-radius: 999px;
      padding: 7px 12px;
      font-size: 12px;
      font-weight: 800;
      border: 1px solid rgba(255,255,255,0.18);
      background: rgba(10, 12, 18, 0.62);
      text-transform: none;
      letter-spacing: 0;
      margin-left: auto;
    }
    .status-pill.active,
    .status-pill.scheduled,
    .status-pill.sold,
    .status-pill.ended {
      color: rgba(255,255,255,0.88);
    }

    .card-title {
      margin: 0;
      font-size: 34px;
      line-height: 1.08;
      letter-spacing: -0.02em;
      font-weight: 800;
    }
    .card-desc {
      color: var(--muted);
      font-size: 16px;
      line-height: 1.6;
      min-height: 44px;
    }

    .info-grid {
      display: flex;
      justify-content: space-between;
      align-items: end;
      gap: 10px;
      border-top: 1px solid rgba(255,255,255,0.14);
      padding-top: 14px;
    }
    .info {
      border-radius: 0;
      padding: 0;
      background: transparent;
      border: none;
    }
    .info-label {
      font-size: 13px;
      color: rgba(255,255,255,0.52);
      margin-bottom: 8px;
      text-transform: none;
      letter-spacing: 0;
      font-weight: 700;
    }
    .info-value {
      font-size: 36px;
      font-weight: 800;
    }

    .card-footer {
      display: grid;
      gap: 10px;
      align-items: center;
      margin-top: 4px;
    }

    .watchers {
      color: rgba(255,255,255,0.68);
      font-size: 14px;
      display: flex;
      align-items: center;
      gap: 8px;
      justify-content: flex-end;
    }

    .card-btn {
      border: 1px solid rgba(245, 158, 11, 0.38);
      border-radius: 14px;
      padding: 13px 14px;
      font-size: 17px;
      font-weight: 800;
      cursor: pointer;
      background: rgba(245, 158, 11, 0.14);
      color: #f59e0b;
      transition: transform 0.2s ease, box-shadow 0.2s ease;
    }
    .card-btn:hover { transform: translateY(-1px); box-shadow: 0 16px 32px rgba(245,158,11,0.16); }

    .empty {
      margin-top: 18px;
      border-radius: 14px;
      padding: 24px;
      border: 1px solid rgba(255,255,255,0.14);
      background: #0f131d;
      color: #cbd5e1;
      text-align: center;
      font-weight: 700;
      width: 100%;
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
      width: min(1120px, 100%);
      max-height: min(92vh, 960px);
      overflow: hidden;
      border-radius: 28px;
      background: rgba(7, 10, 16, 0.98);
      border: 1px solid rgba(255,255,255,0.12);
      box-shadow: 0 30px 100px rgba(0, 0, 0, 0.6);
      display: grid;
      grid-template-rows: auto 1fr;
      animation: floatIn 0.25s ease both;
    }

    .modal-shell.narrow {
      width: min(760px, 100%);
    }

    .modal-head {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 16px;
      padding: 18px 20px;
      border-bottom: 1px solid rgba(255,255,255,0.08);
      background: rgba(255,255,255,0.02);
    }

    .modal-title {
      margin: 0;
      font-size: 18px;
      font-weight: 800;
      letter-spacing: -0.02em;
    }

    .modal-close {
      border: 1px solid rgba(255,255,255,0.12);
      background: rgba(255,255,255,0.05);
      color: #fff;
      width: 42px;
      height: 42px;
      border-radius: 12px;
      cursor: pointer;
      font-size: 20px;
      line-height: 1;
    }

    .modal-body {
      overflow: auto;
      padding: 20px;
    }

    .detail-layout {
      display: grid;
      grid-template-columns: minmax(0, 1fr) 650px;
      gap: 18px;
    }

    .detail-media {
      border-radius: 24px;
      overflow: hidden;
      background: rgba(255,255,255,0.04);
      border: 1px solid rgba(255,255,255,0.08);
      min-height: 100%;
      display: grid;
      gap: 12px;
      padding: 12px;
    }

    .detail-hero-image {
      width: 100%;
      aspect-ratio: 16 / 9;
      border-radius: 18px;
      overflow: hidden;
      background: linear-gradient(135deg, rgba(255,255,255,0.05), rgba(255,255,255,0.02));
    }

    .detail-hero-image img,
    .detail-hero-image video {
      width: 100%;
      height: 100%;
      object-fit: cover;
      display: block;
      filter: saturate(1.22) contrast(1.17) brightness(1.14);
    }

    .detail-strip {
      display: grid;
      grid-template-columns: repeat(3, minmax(0, 1fr));
      gap: 12px;
    }

    .detail-card {
      border-radius: 16px;
      padding: 14px;
      background: rgba(255,255,255,0.05);
      border: 1px solid rgba(255,255,255,0.08);
    }

    .detail-card-label {
      color: rgba(255,255,255,0.58);
      font-size: 11px;
      text-transform: uppercase;
      letter-spacing: 0.08em;
      margin-bottom: 8px;
      font-weight: 700;
    }

    .detail-card-value {
      font-size: 18px;
      font-weight: 800;
      letter-spacing: -0.02em;
    }

    .detail-info {
      display: grid;
      gap: 14px;
      align-content: start;
    }

    .detail-block {
      border-radius: 22px;
      padding: 18px;
      background: rgba(255,255,255,0.05);
      border: 1px solid rgba(255,255,255,0.08);
    }

    .detail-meta {
      display: flex;
      flex-wrap: wrap;
      gap: 8px;
      margin-bottom: 12px;
    }

    .detail-kicker {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      border-radius: 999px;
      padding: 7px 11px;
      background: rgba(255,255,255,0.06);
      border: 1px solid rgba(255,255,255,0.08);
      color: rgba(255,255,255,0.74);
      font-size: 12px;
      font-weight: 700;
    }

    .detail-name {
      margin: 0 0 10px;
      font-size: 30px;
      line-height: 1.02;
      letter-spacing: -0.04em;
    }

    .detail-description {
      margin: 0;
      color: var(--muted);
      line-height: 1.7;
      font-size: 15px;
    }

    .detail-price {
      margin-top: 14px;
      display: grid;
      gap: 8px;
    }

    .detail-price-label {
      color: rgba(255,255,255,0.58);
      font-size: 11px;
      text-transform: uppercase;
      letter-spacing: 0.08em;
      font-weight: 700;
    }

    .detail-price-value {
      font-size: 34px;
      font-weight: 900;
      letter-spacing: -0.04em;
    }

    .detail-actions {
      display: flex;
      gap: 10px;
      flex-wrap: wrap;
      margin-top: 16px;
    }

    .modal-btn {
      border: none;
      border-radius: 14px;
      padding: 13px 16px;
      font-size: 14px;
      font-weight: 800;
      cursor: pointer;
    }

    .modal-btn.primary {
      background: linear-gradient(135deg, #fbbf24, #f59e0b);
      color: #0b1020;
    }

    .modal-btn.secondary {
      background: rgba(255,255,255,0.06);
      color: #fff;
      border: 1px solid rgba(255,255,255,0.1);
    }

    .bid-form {
      display: grid;
      gap: 14px;
    }

    .bid-display {
      border-radius: 22px;
      padding: 18px;
      background: linear-gradient(180deg, rgba(251,191,36,0.12), rgba(255,255,255,0.04));
      border: 1px solid rgba(251,191,36,0.2);
    }

    .bid-display-label {
      color: rgba(255,255,255,0.68);
      font-size: 12px;
      text-transform: uppercase;
      letter-spacing: 0.08em;
      font-weight: 700;
    }

    .bid-display-value {
      margin-top: 8px;
      font-size: 36px;
      font-weight: 900;
      letter-spacing: -0.04em;
    }

    .bid-display-help {
      margin-top: 8px;
      color: rgba(255,255,255,0.66);
      line-height: 1.6;
      font-size: 14px;
    }

    .bid-input-row {
      display: grid;
      gap: 8px;
    }

    .bid-input-label {
      font-size: 12px;
      text-transform: uppercase;
      letter-spacing: 0.08em;
      font-weight: 700;
      color: rgba(255,255,255,0.62);
    }

    .bid-input {
      width: 100%;
      border-radius: 16px;
      border: 1px solid rgba(255,255,255,0.12);
      background: rgba(255,255,255,0.05);
      color: #fff;
      padding: 14px 15px;
      font-size: 18px;
      font-weight: 800;
      outline: none;
    }

    .quick-bids {
      display: flex;
      flex-wrap: wrap;
      gap: 10px;
    }

    .quick-bid {
      border-radius: 999px;
      border: 1px solid rgba(255,255,255,0.12);
      background: rgba(255,255,255,0.05);
      color: #fff;
      padding: 10px 14px;
      font-weight: 700;
      cursor: pointer;
    }

    .modal-note {
      font-size: 13px;
      color: rgba(255,255,255,0.66);
      line-height: 1.6;
      margin: 0;
    }

    .modal-alert {
      border-radius: 14px;
      padding: 12px 14px;
      background: rgba(255,255,255,0.05);
      border: 1px solid rgba(255,255,255,0.08);
      color: rgba(255,255,255,0.8);
      font-size: 14px;
      line-height: 1.5;
    }

    .recent-bids {
      display: grid;
      gap: 10px;
    }

    .recent-bid {
      display: flex;
      justify-content: space-between;
      gap: 10px;
      padding: 12px 0;
      border-bottom: 1px solid rgba(255,255,255,0.08);
    }

    .recent-bid:last-child { border-bottom: 0; padding-bottom: 0; }

    .recent-bid-name {
      font-weight: 700;
      font-size: 14px;
    }

    .recent-bid-time {
      color: rgba(255,255,255,0.56);
      font-size: 12px;
      margin-top: 4px;
    }

    .recent-bid-amount {
      font-weight: 800;
      white-space: nowrap;
    }

    body.modal-open {
      overflow: hidden;
    }

    .fade-in {
      animation: rise 0.6s ease both;
    }

    @keyframes rise {
      from { opacity: 0; transform: translateY(20px); }
      to { opacity: 1; transform: translateY(0); }
    }

    @keyframes floatIn {
      from { opacity: 0; transform: translateY(14px) scale(0.98); }
      to { opacity: 1; transform: translateY(0) scale(1); }
    }

    @keyframes pulse {
      0%, 100% { transform: scale(1); box-shadow: 0 0 0 0 rgba(255,255,255,0.4); }
      50% { transform: scale(1.12); box-shadow: 0 0 0 8px rgba(255,255,255,0); }
    }

    @media (max-width: 0px) {
      .hero-inner { grid-template-columns: 1fr; }
      .hero-panel { max-width: 780px; }
      .auction-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
      .section-title { font-size: clamp(24px, 4vw, 34px); }
      .view-all-link { font-size: 18px; }
      .status-pill { font-size: 12px; }
      .card-title { font-size: 30px; }
      .card-desc { font-size: 16px; }
      .info-label { font-size: 14px; }
      .info-value { font-size: 30px; }
      .watchers { font-size: 16px; }
      .card-btn { font-size: 20px; }
    }

    @media (max-width: 0px) {
      .topbar-inner { padding: 12px 14px; }
      .brand-title { font-size: 16px; }
      .top-actions { width: 100%; justify-content: flex-start; }
      .hero { min-height: auto; }
      .hero-inner { padding: 28px 14px 36px; }
      .hero-title { font-size: 44px; max-width: none; }
      .hero-copy { font-size: 16px; }
      .hero-stats { grid-template-columns: 1fr; }
      .panel-grid { grid-template-columns: 1fr; }
      .auction-grid { grid-template-columns: 1fr; }
      .section { padding: 26px 14px 40px; }
      #upcomingSection.section { padding: 0; }
      .upcoming-tone { padding: 22px 14px 30px; }
      .view-all-link { font-size: 16px; }
      .status-pill { font-size: 11px; }
      .card-title { font-size: 28px; }
      .card-desc { font-size: 15px; }
      .info-label { font-size: 13px; }
      .info-value { font-size: 26px; }
      .watchers { font-size: 14px; }
      .card-btn { font-size: 18px; }
    }

    @media (max-width: 780px) {
      body {
        overflow-x: hidden;
      }

      .wrap {
        width: 100%;
        zoom: 1;
      }

      main {
        width: var(--mobile-desktop-width, 820px);
        zoom: var(--mobile-desktop-scale, 1);
      }

      @supports not (zoom: 1) {
        main {
          transform: scale(var(--mobile-desktop-scale, 1));
          transform-origin: top left;
        }
      }

      .topbar-inner {
        max-width: 1320px;
        padding: 14px 16px;
        gap: 10px;
      }

      .nav-link,
      .chip {
        font-size: 14px;
        padding: 10px 14px;
      }

      .hero-inner {
        min-width: var(--mobile-structure-width, 820px);
        align-items: end;
      }

      .hero-copy-wrap {
        align-self: end;
      }

      #upcomingSection .section-head,
      #upcomingSection .auction-grid,
      #upcomingSection .empty {
        min-width: var(--mobile-structure-width, 820px);
        padding-left: 16px;
        padding-right: 16px;
        box-sizing: border-box;
      }

      .hero {
        align-items: end;
        padding-bottom: 20px;
        min-height: calc((100vh / var(--mobile-desktop-scale, 1)) - var(--topbar-offset, 74px));
      }

      .hero-media img {
        transform: none;
        object-fit: contain;
        object-position: center top;
        background: #05060a;
      }

      .panel-name {
        font-size: 34px;
      }

      .panel-desc {
        font-size: 17px;
        line-height: 1.68;
      }

      .mini-value {
        font-size: 24px;
        line-height: 1.2;
      }

      .panel-name,
      .panel-desc,
      .mini-label,
      .mini-value,
      .mini-help,
      #heroCategory,
      #heroEnds,
      .detail-name,
      .detail-description,
      .detail-card-value,
      .detail-kicker,
      .modal-note,
      .modal-alert {
        overflow-wrap: anywhere;
        word-break: break-word;
      }

      .watchers {
        font-size: 16px;
      }

      #heroWatchers,
      #heroWatchersSmall {
        display: none;
      }

      .modal-overlay {
        align-items: flex-start;
        justify-content: center;
        padding: calc(var(--topbar-offset, 74px) + 8px) 10px 16px;
        overflow-y: auto;
        -webkit-overflow-scrolling: touch;
      }

      .modal-overlay.open {
        display: flex;
      }

      .modal-shell,
      .modal-shell.narrow {
        width: 100%;
        max-height: none;
        min-height: calc(100vh - var(--topbar-offset, 74px) - 24px);
        border-radius: 18px;
      }

      .modal-head {
        position: sticky;
        top: 0;
        z-index: 2;
        background: rgba(7, 10, 16, 0.96);
      }

      .modal-body {
        overflow: visible;
        padding: 14px;
      }

      .detail-layout {
        grid-template-columns: 1fr;
        gap: 14px;
      }

      .detail-strip {
        grid-template-columns: 1fr;
      }

      .detail-name {
        font-size: 24px;
      }

      .detail-price-value {
        font-size: 28px;
      }

      .bid-display-value {
        font-size: 30px;
      }

      .detail-actions {
        display: grid;
      }

      .modal-btn {
        width: 100%;
        display: inline-flex;
        justify-content: center;
      }
    }
  </style>
</head>
<body>
  <div class="wrap">
    <header class="topbar">
      <div class="topbar-inner">
        <a class="nav-link active" href="auction.php">Live</a>
        <a class="nav-link" href="#upcomingSection">Upcoming</a>
        <a class="nav-link" href="bidding_history.php">My Bids</a>
        <a class="nav-link" href="user_dashboard.php">Home</a>
      </div>
    </header>

    <main>
      <section class="hero">
        <div class="hero-media">
          <img id="heroImage" src="logo.jpg" alt="Featured auction">
        </div>

        <div class="hero-inner">
          <div class="hero-copy-wrap">
            <div id="heroBadge" class="eyebrow">
              <span class="eyebrow-dot"></span>
              <span>Live auction</span>
            </div>
            <h1 id="heroTitle" class="hero-title">Loading auction</h1>
            <p id="heroDescription" class="hero-copy">Please wait while we load the featured lot.</p>

            <div class="hero-actions">
              <button id="heroBidBtn" class="primary-btn" type="button">Place Bid</button>
              <button class="secondary-btn" type="button" onclick="document.getElementById('upcomingSection').scrollIntoView({ behavior: 'smooth' })">Browse lots</button>
            </div>

            <div class="hero-stats">
              <div class="stat">
                <div class="stat-label">Current Bid</div>
                <div id="heroCurrentBid" class="stat-value">PHP 0.00</div>
                <div class="stat-sub" id="heroBidSub">Minimum increment loading...</div>
              </div>
              <div class="stat">
                <div class="stat-label">Bid Count</div>
                <div id="heroBidCount" class="stat-value">0</div>
                <div class="stat-sub" id="heroBidSub2">Bids place the lot in motion.</div>
              </div>
              <div class="stat">
                <div class="stat-label">Time Remaining</div>
                <div id="heroTimer" class="stat-value">--:--:--</div>
                <div class="stat-sub" id="heroWatchers">Watchers update automatically.</div>
              </div>
            </div>
          </div>

          <aside class="hero-panel">
            <div class="panel-top">
              <div id="heroState" class="panel-badge">Loading</div>
              <div class="watchers" id="heroWatchersSmall"></div>
            </div>
            <div class="panel-image">
              <img id="heroPanelImage" src="logo.jpg" alt="Featured auction thumbnail">
              <video id="heroPanelVideo" controls playsinline preload="metadata" style="display:none;"></video>
            </div>
            <div>
              <h2 id="heroPanelTitle" class="panel-name">Featured Auction</h2>
              <p id="heroPanelDesc" class="panel-desc">Featured item description will appear here.</p>
            </div>
            <div class="panel-grid">
              <div class="mini-box">
                <div class="mini-label">Category</div>
                <div id="heroCategory" class="mini-value">-</div>
                <div class="mini-help">Curated live listing</div>
              </div>
              <div class="mini-box">
                <div class="mini-label">Ends</div>
                <div id="heroEnds" class="mini-value">--</div>
                <div class="mini-help">Auction closes on schedule</div>
              </div>
            </div>
          </aside>
        </div>
      </section>

      <section id="upcomingSection" class="section upcoming-tone">
        <div class="section-head">
          <div>
            <h2 class="section-title">Upcoming Auctions</h2>
            <div class="section-subtitle">Don't miss these exclusive items</div>
          </div>
          <a class="view-all-link" href="upcoming_auctions.php">View All &#8594;</a>
        </div>

        <div id="listHost" class="auction-grid"></div>
        <div id="emptyState" class="empty" style="display:none;">No upcoming bidding</div>
      </section>
    </main>
  </div>

  <div id="detailModal" class="modal-overlay" aria-hidden="true">
    <div class="modal-shell" role="dialog" aria-modal="true" aria-labelledby="detailModalTitle">
      <div class="modal-head">
        <h2 id="detailModalTitle" class="modal-title">Auction details</h2>
        <button class="modal-close" type="button" data-close-modal aria-label="Close detail modal">&times;</button>
      </div>
      <div class="modal-body">
        <div class="detail-layout">
          <div class="detail-media">
            <div class="detail-hero-image">
              <img id="detailImage" src="logo.jpg" alt="Auction detail image">
              <video id="detailVideo" controls playsinline preload="metadata" style="display:none;"></video>
            </div>
            <div class="detail-strip">
              <div class="detail-card">
                <div class="detail-card-label">Current bid</div>
                <div id="detailCurrentBid" class="detail-card-value">PHP 0.00</div>
              </div>
              <div class="detail-card">
                <div class="detail-card-label">Bids</div>
                <div id="detailBidCount" class="detail-card-value">0</div>
              </div>
              <div class="detail-card">
                <div class="detail-card-label">Time left</div>
                <div id="detailCountdown" class="detail-card-value">--:--:--</div>
              </div>
            </div>
            <div class="detail-block">
              <div class="detail-meta">
                <span id="detailStatus" class="detail-kicker">Loading</span>
                <span id="detailCategory" class="detail-kicker">-</span>
                <span id="detailCondition" class="detail-kicker">-</span>
              </div>
              <h3 id="detailName" class="detail-name">Auction item</h3>
              <p id="detailDescription" class="detail-description">Loading auction details...</p>
              <div class="detail-price">
                <div class="detail-price-label">Next minimum bid</div>
                <div id="detailNextBid" class="detail-price-value">PHP 0.00</div>
              </div>
              <div class="detail-actions">
                <button id="detailPlaceBidBtn" class="modal-btn primary" type="button">Place Bid</button>
                <button id="detailResultBtn" class="modal-btn secondary" type="button" style="display:none;">View Result</button>
                <button class="modal-btn secondary" type="button" data-close-modal>Close</button>
              </div>
            </div>
          </div>

          <aside class="detail-info">
            <div class="detail-block">
              <div class="detail-meta">
                <span class="detail-kicker">Auction timeline</span>
              </div>
              <p class="modal-note" id="detailTimeline">Start and end information will appear here.</p>
            </div>

            <div class="detail-block">
              <div class="detail-meta">
                <span class="detail-kicker">Recent bids</span>
              </div>
              <div id="recentBidsHost" class="recent-bids">
                <div class="modal-note">Loading recent bids...</div>
              </div>
            </div>

            <div class="detail-block">
              <div class="detail-meta">
                <span class="detail-kicker">Bid guide</span>
              </div>
              <p class="modal-note" id="detailBidGuide">Use the next minimum bid or a higher amount to outbid the current leader.</p>
            </div>
          </aside>
        </div>
      </div>
    </div>
  </div>

  <div id="bidModal" class="modal-overlay" aria-hidden="true">
    <div class="modal-shell narrow" role="dialog" aria-modal="true" aria-labelledby="bidModalTitle">
      <div class="modal-head">
        <h2 id="bidModalTitle" class="modal-title">Place Your Bid</h2>
        <button class="modal-close" type="button" data-close-modal aria-label="Close bid modal">&times;</button>
      </div>
      <div class="modal-body">
        <form id="bidForm" class="bid-form">
          <input type="hidden" id="bidAuctionId" name="auction_id" value="">
          <div class="bid-display">
            <div class="bid-display-label">Current required bid</div>
            <div id="bidCurrentDisplay" class="bid-display-value">PHP 0.00</div>
            <div id="bidCurrentHelp" class="bid-display-help">Loading auction requirements...</div>
          </div>

          <div class="bid-input-row">
            <div class="bid-input-label">Your bid amount</div>
            <input id="bidAmountInput" class="bid-input" name="bid_amount" type="number" min="0" step="0.01" placeholder="0.00" required>
          </div>

          <div class="quick-bids" id="quickBidButtons"></div>

          <div id="bidModalMessage" class="modal-alert">Enter a bid that meets or exceeds the minimum requirement.</div>

          <div class="detail-actions" style="margin-top:4px;">
            <button id="submitBidBtn" class="modal-btn primary" type="submit">Confirm Bid</button>
            <button class="modal-btn secondary" type="button" data-close-modal>Cancel</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <?php include __DIR__ . '/partials/user_dashboard/checkout_section.php'; ?>

  <script>
    function syncTopbarOffset() {
      const topbar = document.querySelector('.topbar');
      if (!topbar) return;
      document.documentElement.style.setProperty('--topbar-offset', `${topbar.offsetHeight}px`);
    }

    function syncMobileDesktopScale() {
      const wrap = document.querySelector('.wrap');
      if (!wrap) return;

      const mobileMaxWidth = 780;
      const desktopCanvasWidth = 820;
      const viewportWidth = Math.floor(
        window.visualViewport?.width || document.documentElement.clientWidth || window.innerWidth || 0
      );
      const isMobile = viewportWidth <= mobileMaxWidth;

      if (!isMobile) {
        document.documentElement.style.removeProperty('--mobile-desktop-width');
        document.documentElement.style.removeProperty('--mobile-desktop-scale');
        document.documentElement.style.removeProperty('--mobile-structure-width');
        return;
      }

      const scale = Math.min(1, viewportWidth / desktopCanvasWidth);
      document.documentElement.style.setProperty('--mobile-desktop-width', `${desktopCanvasWidth}px`);
      document.documentElement.style.setProperty('--mobile-desktop-scale', String(scale));
      document.documentElement.style.setProperty('--mobile-structure-width', `${desktopCanvasWidth}px`);
    }

    window.addEventListener('resize', syncTopbarOffset);
    window.addEventListener('load', syncTopbarOffset);
    window.addEventListener('resize', syncMobileDesktopScale);
    window.addEventListener('load', syncMobileDesktopScale);

    let searchTimer = null;
    let allListings = [];
    let featuredAuction = null;
    let countdownTimer = null;

    async function readJsonResponse(res) {
      const raw = await res.text();
      try {
        return JSON.parse(raw);
      } catch (err) {
        throw new Error('Server returned a non-JSON response. Run the auction SQL setup scripts and check PHP runtime errors.');
      }
    }

    function showAlert(icon, title, text) {
      if (window.localSwalAlert) {
        return window.localSwalAlert(icon, title, text);
      }
      alert(text || title);
      return Promise.resolve();
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
      const diff = Math.max(0, Math.floor((target - Date.now()) / 1000));
      const hours = String(Math.floor(diff / 3600)).padStart(2, '0');
      const minutes = String(Math.floor((diff % 3600) / 60)).padStart(2, '0');
      const seconds = String(diff % 60).padStart(2, '0');
      return `${hours}:${minutes}:${seconds}`;
    }

    function resolveAuctionDescription(item, fallback = 'Description will be updated soon.') {
      const candidates = [
        item?.item_description,
        item?.product_description,
        item?.description
      ];
      for (const value of candidates) {
        const text = String(value || '').trim();
        if (text !== '') return text;
      }
      return fallback;
    }

    function formatUpcomingLead(raw) {
      if (!raw) return 'Soon';
      const start = new Date(String(raw).replace(' ', 'T')).getTime();
      if (Number.isNaN(start)) return 'Soon';
      const diffSec = Math.max(0, Math.floor((start - Date.now()) / 1000));
      const hours = Math.floor(diffSec / 3600);
      const minutes = Math.floor((diffSec % 3600) / 60);
      return `${hours}h ${minutes}m`;
    }

    function clearFeaturedAuction() {
      featuredAuction = null;
      document.getElementById('heroImage').src = 'logo.jpg';
      document.getElementById('heroPanelImage').src = 'logo.jpg';
      const heroVideo = document.getElementById('heroPanelVideo');
      if (heroVideo) {
        heroVideo.pause();
        heroVideo.removeAttribute('src');
        heroVideo.load();
        heroVideo.style.display = 'none';
      }
      const heroPanelImage = document.getElementById('heroPanelImage');
      if (heroPanelImage) heroPanelImage.style.display = 'block';
      document.getElementById('heroTitle').textContent = 'No available bidding right now';
      document.getElementById('heroPanelTitle').textContent = 'No available bidding right now';
      document.getElementById('heroDescription').textContent = 'There are no active or upcoming auctions at the moment. Please check back soon.';
      document.getElementById('heroPanelDesc').textContent = 'When a new auction schedule is published, it will appear here automatically.';
      document.getElementById('heroCurrentBid').textContent = 'N/A';
      document.getElementById('heroBidCount').textContent = '0';
      document.getElementById('heroCategory').textContent = '-';
      document.getElementById('heroEnds').textContent = 'Not set';
      document.getElementById('heroState').textContent = 'Idle';
      document.getElementById('heroState').className = 'panel-badge';
      document.getElementById('heroWatchersSmall').textContent = '';
      document.getElementById('heroWatchers').textContent = '';
      document.getElementById('heroBidSub').textContent = 'No active increment.';
      document.getElementById('heroBidSub2').textContent = 'No live lot is available currently.';
      document.getElementById('heroTimer').textContent = '--:--:--';

      const heroButton = document.getElementById('heroBidBtn');
      heroButton.textContent = 'View My Bids';
      heroButton.onclick = () => {
        window.location.href = 'bidding_history.php';
      };

      document.querySelector('.hero-panel').onclick = null;
      document.querySelector('.hero-media').onclick = null;
    }

    let modalRefreshTimer = null;
    let currentDetailAuction = null;
    let currentDetailData = null;
    let currentBidTarget = null;
    let currentWinnerRecipients = [];
    let selectedWinnerRecipientId = 0;

    function syncModalBodyState() {
      const anyOpen = Boolean(
        document.getElementById('detailModal')?.classList.contains('open') ||
        document.getElementById('bidModal')?.classList.contains('open') ||
        document.getElementById('checkoutModal')?.classList.contains('show')
      );
      document.body.classList.toggle('modal-open', anyOpen);
    }

    function setModalState(modal, open) {
      if (!modal) return;
      modal.classList.toggle('open', open);
      modal.setAttribute('aria-hidden', open ? 'false' : 'true');
      syncModalBodyState();
    }

    function closeAllModals() {
      setModalState(document.getElementById('detailModal'), false);
      setModalState(document.getElementById('bidModal'), false);
      closeCheckout();
      syncModalBodyState();
    }

    function openDetail(auctionId) {
      openAuctionDetailModal(auctionId);
    }

    async function fetchAuctionDetail(auctionId) {
      const res = await fetch(`api/get-auction-detail.php?auction_id=${encodeURIComponent(String(auctionId))}`, { cache: 'no-store' });
      const data = await readJsonResponse(res);
      if (!res.ok || !data.success) {
        throw new Error(data.error || 'Failed to load auction details');
      }
      return data;
    }

    function getCoverImageFromDetail(detail) {
      const images = detail?.media?.images || [];
      if (images.length > 0 && images[0]?.path) {
        return String(images[0].path);
      }
      return 'logo.jpg';
    }

    function getNextRequiredBid(detail) {
      if (!detail) return 0;
      const current = detail.current_bid !== null ? Number(detail.current_bid) : Number(detail.starting_bid || 0);
      const increment = Math.max(0.01, Number(detail.bid_increment || 0));
      return Number((detail.current_bid !== null ? current + increment : current).toFixed(2));
    }

    function formatDateLong(raw) {
      if (!raw) return 'Not scheduled';
      const d = new Date(String(raw).replace(' ', 'T'));
      if (Number.isNaN(d.getTime())) return String(raw);
      return d.toLocaleString(undefined, {
        weekday: 'short',
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: 'numeric',
        minute: '2-digit'
      });
    }

    function getStatusLabel(status) {
      const value = String(status || 'scheduled');
      return value.replace(/^./, (char) => char.toUpperCase());
    }

    function renderRecentBids(recentBids) {
      const host = document.getElementById('recentBidsHost');
      host.innerHTML = '';
      if (!Array.isArray(recentBids) || recentBids.length === 0) {
        host.innerHTML = '<div class="modal-note">No bids yet. Be the first to move the lot.</div>';
        return;
      }

      recentBids.slice(0, 6).forEach((bid) => {
        const row = document.createElement('div');
        row.className = 'recent-bid';

        const left = document.createElement('div');
        const name = document.createElement('div');
        name.className = 'recent-bid-name';
        name.textContent = bid.bidder || 'Bidder';
        const time = document.createElement('div');
        time.className = 'recent-bid-time';
        time.textContent = formatDateLong(bid.created_at);
        left.appendChild(name);
        left.appendChild(time);

        const amount = document.createElement('div');
        amount.className = 'recent-bid-amount';
        amount.textContent = formatMoney(bid.bid_amount);

        row.appendChild(left);
        row.appendChild(amount);
        host.appendChild(row);
      });
    }

    function fillDetailModal(detail, recentBids) {
      currentDetailData = detail;
      const image = getCoverImageFromDetail(detail);
      const detailVideoPath = String(detail?.media?.video || '');
      const detailVideo = document.getElementById('detailVideo');
      const detailImage = document.getElementById('detailImage');
      document.getElementById('detailImage').src = image;
      if (detailVideo && detailImage) {
        if (detailVideoPath) {
          detailVideo.src = detailVideoPath;
          detailVideo.style.display = 'block';
          detailImage.style.display = 'none';
        } else {
          detailVideo.pause();
          detailVideo.currentTime = 0;
          detailVideo.style.display = 'none';
          detailImage.style.display = 'block';
        }
      }
      document.getElementById('detailModalTitle').textContent = detail.item_name || 'Auction details';
      document.getElementById('detailName').textContent = detail.item_name || 'Untitled Auction';
      document.getElementById('detailDescription').textContent = resolveAuctionDescription(detail);
      document.getElementById('detailStatus').textContent = getStatusLabel(detail.auction_status);
      document.getElementById('detailCategory').textContent = detail.category_name || 'No Category';
      document.getElementById('detailCondition').textContent = detail.condition_grade ? `Condition ${detail.condition_grade}` : 'Condition not listed';
      document.getElementById('detailCurrentBid').textContent = formatMoney(detail.current_bid !== null ? detail.current_bid : detail.starting_bid);
      document.getElementById('detailBidCount').textContent = String(Number(detail.bid_count || 0));
      document.getElementById('detailCountdown').textContent = formatCountdown(detail.end_at || '');
      document.getElementById('detailNextBid').textContent = formatMoney(getNextRequiredBid(detail));
      document.getElementById('detailTimeline').textContent = `Starts ${formatDateLong(detail.start_at)} and closes ${formatDateLong(detail.end_at)}.`;
      document.getElementById('detailBidGuide').textContent = detail.auction_status === 'active'
        ? 'Place a higher amount than the current required bid to secure your position.'
        : 'This lot is not active right now. You can still review the live details and bid history.';
      document.getElementById('detailPlaceBidBtn').disabled = detail.auction_status !== 'active';
      document.getElementById('detailPlaceBidBtn').textContent = detail.auction_status === 'active' ? 'Place Bid' : 'Bidding Closed';

      const resultBtn = document.getElementById('detailResultBtn');
      resultBtn.style.display = 'none';
      resultBtn.onclick = null;
      if (detail.auction_status === 'sold') {
        resultBtn.style.display = 'inline-flex';
        if (detail.is_winner && !detail.checked_out) {
          resultBtn.textContent = 'View Result';
          resultBtn.onclick = () => { window.location.href = 'purchase_history.php'; };
        } else if (detail.is_winner && detail.checked_out) {
          resultBtn.textContent = `Order #${detail.order_id}`;
          resultBtn.onclick = () => { window.location.href = 'purchase_history.php'; };
        } else {
          resultBtn.textContent = 'My Bid History';
          resultBtn.onclick = () => { window.location.href = 'bidding_history.php'; };
        }
      }

      renderRecentBids(recentBids || []);
    }

    function getWinnerCheckoutAmount(detail) {
      if (!detail) return 0;
      if (detail.sold_price !== null && detail.sold_price !== undefined) return Number(detail.sold_price);
      if (detail.current_bid !== null && detail.current_bid !== undefined) return Number(detail.current_bid);
      return Number(detail.starting_bid || 0);
    }

    function peso(value) {
      return `₱${Number(value || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
    }

    function openCheckout() {
      document.getElementById('checkoutModal').classList.add('show');
      initCheckoutTermsControls();
      setCheckoutTermsAccepted(false);
      syncModalBodyState();
    }

    function closeCheckout() {
      document.getElementById('checkoutModal').classList.remove('show');
      closeCheckoutTermsModal();
      syncModalBodyState();
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
        openBtn.addEventListener('click', openCheckoutTermsModal);
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
      const image = Array.isArray(detail?.media?.images) && detail.media.images.length > 0
        ? String(detail.media.images[0].path || 'logo.jpg')
        : 'logo.jpg';

      host.innerHTML = `
        <div class="order-item" data-id="${String(detail.auction_id || '')}">
          <img src="${image}" alt="${itemName}" class="order-item-image">
          <div class="order-item-info">
            <div class="order-item-name">${itemName}</div>
            <div class="order-item-price">${peso(amount)}</div>
            <div class="checkout-qty-row">
              <button type="button" class="qty-adj-btn minus">-</button>
              <span class="checkout-qty">1</span>
              <button type="button" class="qty-adj-btn plus">+</button>
            </div>
            <div class="order-item-line-total">${peso(amount)}</div>
          </div>
        </div>
      `;

      subtotalEl.textContent = peso(amount);
      if (shippingEl) shippingEl.textContent = 'FREE';
      totalEl.textContent = peso(amount);
    }

    function buildRecipientAddress(recipient) {
      const parts = [
        String(recipient.street_name || '').trim(),
        String(recipient.unit_floor || '').trim(),
        String(recipient.district || '').trim(),
        String(recipient.city || '').trim(),
        String(recipient.region || '').trim()
      ].filter(Boolean);
      return parts.length > 0 ? parts.join(', ') : 'N/A';
    }

    function renderWinnerRecipients() {
      const listHost = document.getElementById('recipientsContainer');
      const recipientsList = document.getElementById('recipientsList');
      const newRecipientForm = document.getElementById('newRecipientForm');
      listHost.innerHTML = '';
      selectedWinnerRecipientId = 0;

      if (newRecipientForm) newRecipientForm.classList.add('recipient-initial-hidden');
      if (recipientsList) recipientsList.classList.remove('recipient-initial-hidden');

      if (!Array.isArray(currentWinnerRecipients) || currentWinnerRecipients.length === 0) {
        listHost.innerHTML = '<p class="hint">No recipient found. Add one from your account first.</p>';
        return;
      }

      currentWinnerRecipients.forEach((recipient, index) => {
        const id = Number(recipient.recipient_id || 0);
        const checked = recipient.is_default || (selectedWinnerRecipientId === 0 && index === 0);
        if (checked) selectedWinnerRecipientId = id;

        const card = document.createElement('label');
        card.className = 'recipient-card';
        card.innerHTML = `
          <input type="radio" name="auctionWinnerRecipient" value="${id}" ${checked ? 'checked' : ''}>
          <div class="recipient-info">
            <div class="recipient-name">${String(recipient.recipient_name || 'Recipient')}</div>
            <div class="recipient-phone">${String(recipient.phone_no || 'N/A')}</div>
            <div class="recipient-address">${buildRecipientAddress(recipient)}</div>
          </div>
        `;
        listHost.appendChild(card);
      });

      listHost.querySelectorAll('input[name="auctionWinnerRecipient"]').forEach((input) => {
        input.addEventListener('change', () => {
          selectedWinnerRecipientId = Number(input.value || 0);
        });
      });
    }

    async function loadWinnerRecipients() {
      const placeOrderBtn = document.getElementById('placeOrderBtn');
      currentWinnerRecipients = [];

      try {
        const res = await fetch('api/get-recipients.php', { cache: 'no-store' });
        const data = await readJsonResponse(res);
        if (!res.ok || !Array.isArray(data.recipients)) {
          throw new Error(data.error || 'Failed to load recipients');
        }

        currentWinnerRecipients = data.recipients;
        renderWinnerRecipients();
        placeOrderBtn.disabled = currentWinnerRecipients.length === 0;
      } catch (err) {
        renderWinnerRecipients();
        placeOrderBtn.disabled = true;
        showAlert('error', 'Recipients Error', String(err.message || 'Unable to load recipients.'));
      }
    }

    async function handleCheckoutClick() {
      const detail = currentDetailData;
      const placeOrderBtn = document.getElementById('placeOrderBtn');

      if (!detail || detail.auction_status !== 'sold' || !detail.is_winner || detail.checked_out) {
        showAlert('error', 'Checkout Not Available', 'Winner checkout is not available for this auction.');
        return;
      }
      if (!isCheckoutTermsAccepted()) {
        showAlert('warning', 'Terms Required', 'Please agree to the Terms & Conditions before placing your order.');
        openCheckoutTermsModal();
        return;
      }
      if (!Number.isFinite(selectedWinnerRecipientId) || selectedWinnerRecipientId <= 0) {
        showAlert('warning', 'Recipient Required', 'Please select a recipient before placing order.');
        return;
      }

      placeOrderBtn.disabled = true;
      placeOrderBtn.classList.add('loading');
      placeOrderBtn.innerHTML = '<span class="loading-spinner"></span> Processing...';
      try {
        const body = new URLSearchParams();
        body.set('auction_id', String(detail.auction_id || ''));
        body.set('recipient_id', String(selectedWinnerRecipientId));
        body.set('payment_method', 'cash');

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
        showAlert('success', 'Order Placed', `Order #${String(data.order_id || '0')} placed successfully.`);

        setTimeout(() => {
          closeCheckout();
        }, 500);

        await refreshCurrentModal();
        await loadListings();
      } catch (err) {
        showAlert('error', 'Checkout Failed', String(err.message || 'Unable to checkout this auction.'));
      } finally {
        placeOrderBtn.disabled = false;
        placeOrderBtn.classList.remove('loading');
        placeOrderBtn.innerHTML = '<span class="btn-icon">✓</span> Place Order';
      }
    }

    function closeSuccessModal() {
      document.getElementById('successModal').classList.remove('show');
    }

    function saveNewRecipient() {
      window.location.href = 'user_dashboard.php';
    }

    function handleCancelRecipient() {
      const newForm = document.getElementById('newRecipientForm');
      const recipientsList = document.getElementById('recipientsList');
      if (newForm) newForm.classList.add('recipient-initial-hidden');
      if (recipientsList) recipientsList.classList.remove('recipient-initial-hidden');
    }

    async function openAuctionDetailModal(auctionId) {
      const modal = document.getElementById('detailModal');
      setModalState(modal, true);
      document.getElementById('recentBidsHost').innerHTML = '<div class="modal-note">Loading recent bids...</div>';
      document.getElementById('detailName').textContent = 'Loading...';
      currentDetailAuction = Number(auctionId);

      try {
        const data = await fetchAuctionDetail(auctionId);
        currentDetailData = data.auction || null;
        fillDetailModal(data.auction || {}, data.recent_bids || []);
      } catch (err) {
        document.getElementById('recentBidsHost').innerHTML = `<div class="modal-note">${String(err.message || 'Unable to load auction details')}</div>`;
        document.getElementById('detailDescription').textContent = String(err.message || 'Unable to load auction details');
      }
    }

    function openBidModal(detail) {
      if (!detail) return;
      currentBidTarget = detail;
      const modal = document.getElementById('bidModal');
      const requiredBid = getNextRequiredBid(detail);
      document.getElementById('bidAuctionId').value = String(detail.auction_id || '');
      document.getElementById('bidCurrentDisplay').textContent = formatMoney(requiredBid);
      document.getElementById('bidCurrentHelp').textContent = detail.auction_status === 'active'
        ? `Current bid is ${formatMoney(detail.current_bid !== null ? detail.current_bid : detail.starting_bid)} with an increment of ${formatMoney(detail.bid_increment)}.`
        : 'This auction is not active right now.';
      document.getElementById('bidAmountInput').value = requiredBid.toFixed(2);
      document.getElementById('bidAmountInput').min = requiredBid.toFixed(2);
      document.getElementById('bidAmountInput').step = '0.01';
      document.getElementById('bidModalMessage').textContent = `Minimum required bid is ${formatMoney(requiredBid)}.`;

      const quickHost = document.getElementById('quickBidButtons');
      quickHost.innerHTML = '';
      [1, 2, 5].forEach((multiplier) => {
        const quickBid = Number((requiredBid + (Math.max(0.01, Number(detail.bid_increment || 0)) * multiplier)).toFixed(2));
        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'quick-bid';
        button.textContent = `+${multiplier} inc (${formatMoney(quickBid)})`;
        button.addEventListener('click', () => {
          document.getElementById('bidAmountInput').value = quickBid.toFixed(2);
        });
        quickHost.appendChild(button);
      });

      setModalState(modal, true);
      document.getElementById('bidAmountInput').focus();
    }

    function openBidFromDetail() {
      if (currentDetailData) {
        openBidModal(currentDetailData);
      }
    }

    async function refreshCurrentModal() {
      if (!currentDetailAuction) return;
      try {
        const data = await fetchAuctionDetail(currentDetailAuction);
        currentDetailData = data.auction || null;
        fillDetailModal(data.auction || {}, data.recent_bids || []);
        if (document.getElementById('bidModal').classList.contains('open')) {
          openBidModal(data.auction || {});
        }
      } catch (err) {
        console.warn(err);
      }
    }

    function setFeaturedAuction(item) {
      featuredAuction = item || null;
      if (!featuredAuction) return;

      const image = String(item.cover_image || 'logo.jpg');
      const video = String(item.cover_video || '');
      const heroPanelVideo = document.getElementById('heroPanelVideo');
      const heroPanelImage = document.getElementById('heroPanelImage');
      document.getElementById('heroImage').src = image || 'logo.jpg';
      document.getElementById('heroPanelImage').src = image || 'logo.jpg';
      if (heroPanelVideo && heroPanelImage) {
        if (video) {
          heroPanelVideo.src = video;
          heroPanelVideo.style.display = 'block';
          heroPanelImage.style.display = 'none';
        } else {
          heroPanelVideo.pause();
          heroPanelVideo.removeAttribute('src');
          heroPanelVideo.load();
          heroPanelVideo.style.display = 'none';
          heroPanelImage.style.display = 'block';
        }
      }
      document.getElementById('heroTitle').textContent = item.item_name || 'Untitled Auction';
      document.getElementById('heroPanelTitle').textContent = item.item_name || 'Untitled Auction';
      document.getElementById('heroDescription').textContent = resolveAuctionDescription(item);
      document.getElementById('heroPanelDesc').textContent = resolveAuctionDescription(item);
      document.getElementById('heroCurrentBid').textContent = formatMoney(item.current_bid !== null ? item.current_bid : item.starting_bid);
      document.getElementById('heroBidCount').textContent = String(Number(item.bid_count || 0));
      document.getElementById('heroCategory').textContent = item.category_name || 'No Category';
      document.getElementById('heroEnds').textContent = formatDate(item.end_at || '');
      document.getElementById('heroState').textContent = String(item.auction_status || 'scheduled').replace(/^./, (c) => c.toUpperCase());
      document.getElementById('heroState').className = `panel-badge ${String(item.auction_status || '').toLowerCase()}`;
      document.getElementById('heroWatchersSmall').textContent = '';
      document.getElementById('heroWatchers').textContent = '';
      document.getElementById('heroBidSub').textContent = `Bid increment ${formatMoney(item.bid_increment)}.`;
      const isScheduled = String(item.auction_status || '').toLowerCase() === 'scheduled';
      document.querySelectorAll('.stat-label')[2].textContent = isScheduled ? 'Starts In' : 'Time Remaining';
      document.getElementById('heroBidSub2').textContent = item.auction_status === 'active'
        ? 'Bidding is open right now.'
        : isScheduled
          ? 'Next auction opening countdown is live.'
          : 'Auction status updates automatically.';
      document.getElementById('heroTimer').textContent = isScheduled ? formatCountdown(item.start_at || '') : formatCountdown(item.end_at || '');

      const heroButton = document.getElementById('heroBidBtn');
      heroButton.textContent = item.auction_status === 'active'
        ? `Place Bid ${formatMoney((item.current_bid !== null ? item.current_bid : item.starting_bid) + (item.bid_increment || 0))}`
        : isScheduled
          ? 'View Upcoming Auction'
        : item.auction_status === 'sold'
          ? 'View Result'
          : 'View Auction';
      heroButton.onclick = () => {
        if (item.auction_status === 'active') {
          openAuctionDetailModal(item.auction_id).then(() => {
            if (currentDetailData) {
              openBidModal(currentDetailData);
            }
          });
          return;
        }
        openAuctionDetailModal(item.auction_id);
      };

      document.querySelector('.hero-panel').onclick = () => openAuctionDetailModal(item.auction_id);
      document.querySelector('.hero-media').onclick = () => openAuctionDetailModal(item.auction_id);
    }

    function chooseFeatured(listings) {
      const active = listings.find((item) => item.auction_status === 'active');
      if (active) return active;
      const scheduled = listings
        .filter((item) => item.auction_status === 'scheduled' && item.start_at)
        .sort((a, b) => {
          const aTime = new Date(String(a.start_at).replace(' ', 'T')).getTime();
          const bTime = new Date(String(b.start_at).replace(' ', 'T')).getTime();
          return aTime - bTime;
        });
      if (scheduled.length > 0) return scheduled[0];
      return listings[0] || null;
    }

    function renderCard(item, index) {
      const card = document.createElement('article');
      card.className = 'auction-card fade-in';
      card.style.animationDelay = `${Math.min(index * 70, 350)}ms`;

      const image = document.createElement('div');
      image.className = 'auction-image';
      if (item.cover_image) {
        const img = document.createElement('img');
        img.src = String(item.cover_image);
        img.alt = item.item_name || 'Auction image';
        image.appendChild(img);
      }

      const body = document.createElement('div');
      body.className = 'card-body';

      const status = document.createElement('div');
      status.className = `status-pill ${String(item.auction_status || '').toLowerCase()}`;
      status.textContent = `Starts in ${formatUpcomingLead(item.start_at)}`;

      const title = document.createElement('h3');
      title.className = 'card-title';
      title.textContent = item.item_name || 'Untitled Auction';

      const desc = document.createElement('div');
      desc.className = 'card-desc';
      desc.textContent = item.category_name || 'Upcoming lot';

      const infoGrid = document.createElement('div');
      infoGrid.className = 'info-grid';
      infoGrid.innerHTML = `
        <div class="info">
          <div class="info-label">Current Bid</div>
          <div class="info-value">${formatMoney(item.current_bid !== null ? item.current_bid : item.starting_bid)}</div>
        </div>
      `;

      const footer = document.createElement('div');
      footer.className = 'card-footer';

      const btn = document.createElement('button');
      btn.className = 'card-btn';
      btn.type = 'button';
      btn.textContent = 'View Auction';
      btn.addEventListener('click', () => openDetail(item.auction_id));

      footer.appendChild(btn);

      body.appendChild(status);
      body.appendChild(title);
      body.appendChild(desc);
      body.appendChild(infoGrid);
      body.appendChild(footer);

      card.appendChild(image);
      card.appendChild(body);
      return card;
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

      listings.forEach((item, index) => host.appendChild(renderCard(item, index)));
    }

    function updateFeaturedTimer() {
      if (!featuredAuction) return;
      const isScheduled = String(featuredAuction.auction_status || '').toLowerCase() === 'scheduled';
      document.getElementById('heroTimer').textContent = isScheduled
        ? formatCountdown(featuredAuction.start_at || '')
        : formatCountdown(featuredAuction.end_at || '');
      if (currentDetailData) {
        document.getElementById('detailCountdown').textContent = formatCountdown(currentDetailData.end_at || '');
      }
    }

    async function loadListings() {
      const host = document.getElementById('listHost');
      host.innerHTML = '<div class="empty" style="grid-column:1/-1;">Loading auctions...</div>';
      try {
        const status = 'live';
        const params = new URLSearchParams();
        params.set('status', status);
        params.set('limit', '80');

        const res = await fetch(`api/get-auction-listings.php?${params.toString()}`, { cache: 'no-store' });
        const data = await readJsonResponse(res);
        if (!res.ok || !data.success) {
          throw new Error(data.error || 'Failed to load auctions');
        }

        allListings = Array.isArray(data.listings) ? data.listings : [];
        if (allListings.length > 0) {
          setFeaturedAuction(chooseFeatured(allListings));
          const upcoming = allListings
            .filter((item) => String(item.auction_status || '').toLowerCase() === 'scheduled')
            .sort((a, b) => {
              const aTime = new Date(String(a.start_at || '').replace(' ', 'T')).getTime();
              const bTime = new Date(String(b.start_at || '').replace(' ', 'T')).getTime();
              return aTime - bTime;
            })
            .slice(0, 3);
          render(upcoming);
        } else {
          clearFeaturedAuction();
          render([]);
        }
        syncMobileDesktopScale();
      } catch (err) {
        host.innerHTML = `<div class="empty" style="grid-column:1/-1;">${String(err.message || 'Unable to load auctions')}</div>`;
        syncMobileDesktopScale();
      }
    }

    document.querySelectorAll('[data-close-modal]').forEach((button) => {
      button.addEventListener('click', closeAllModals);
    });

    document.getElementById('detailModal').addEventListener('click', (event) => {
      if (event.target === event.currentTarget) closeAllModals();
    });
    document.getElementById('bidModal').addEventListener('click', (event) => {
      if (event.target === event.currentTarget) closeAllModals();
    });

    document.getElementById('detailPlaceBidBtn').addEventListener('click', () => {
      if (currentDetailData && currentDetailData.auction_status === 'active') {
        openBidModal(currentDetailData);
      }
    });

    document.getElementById('checkoutModal').addEventListener('click', (event) => {
      if (event.target && event.target.id === 'checkoutModal') {
        closeCheckout();
      }
    });

    initCheckoutTermsControls();

    document.getElementById('bidForm').addEventListener('submit', async (event) => {
      event.preventDefault();
      const submitBtn = document.getElementById('submitBidBtn');
      const message = document.getElementById('bidModalMessage');
      const formData = new FormData(event.currentTarget);
      const amount = Number(String(formData.get('bid_amount') || '').trim());
      const detail = currentBidTarget || currentDetailData;
      if (!detail) return;

      if (!Number.isFinite(amount) || amount <= 0) {
        message.textContent = 'Enter a valid bid amount.';
        showAlert('warning', 'Invalid Bid', 'Enter a valid bid amount.');
        return;
      }

      submitBtn.disabled = true;
      message.textContent = 'Submitting your bid...';

      try {
        const payload = new URLSearchParams();
        payload.set('auction_id', String(detail.auction_id || ''));
        payload.set('bid_amount', amount.toFixed(2));

        const res = await fetch('api/place-auction-bid.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
          body: payload.toString()
        });
        const data = await readJsonResponse(res);
        if (!res.ok || !data.success) {
          throw new Error(data.error || 'Failed to place bid');
        }

        message.textContent = data.message || 'Bid placed successfully.';
        showAlert('success', 'Bid Placed', data.message || 'Bid placed successfully.');
        setTimeout(() => {
          setModalState(document.getElementById('bidModal'), false);
          refreshCurrentModal();
          loadListings();
        }, 500);
      } catch (err) {
        message.textContent = String(err.message || 'Unable to place bid');
        showAlert('error', 'Bid Failed', String(err.message || 'Unable to place bid'));
      } finally {
        submitBtn.disabled = false;
      }
    });

    document.addEventListener('keydown', (event) => {
      if (event.key === 'Escape') {
        closeAllModals();
      }
    });

    loadListings();
    if (countdownTimer) clearInterval(countdownTimer);
    countdownTimer = setInterval(updateFeaturedTimer, 1000);
    setInterval(loadListings, 30000);
    modalRefreshTimer = setInterval(refreshCurrentModal, 10000);
  </script>
</body>
</html>
