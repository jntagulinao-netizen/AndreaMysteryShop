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
	<title>Product Drafts - Admin</title>
	<link rel="stylesheet" href="main.css">
	<style>
		* { box-sizing: border-box; }
		body {
			margin: 0;
			font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
			background: #f4f6fb;
			color: #1d2635;
		}

		.page {
			width: calc(100% - 48px);
			max-width: none;
			margin: 0 auto;
			padding: 98px 0 14px;
		}

		.topbar {
			position: fixed;
			top: 0;
			left: 0;
			right: 0;
			z-index: 120;
			background: #f4f6fb;
			padding: 10px 0 12px;
			border-bottom: 1px solid #e7edf6;
		}

		.topbar-inner {
			background: #fff;
			border: 1px solid #e2e8f2;
			border-radius: 12px;
			padding: 12px 14px;
			display: flex;
			align-items: center;
			gap: 10px;
			width: calc(100% - 48px);
			max-width: none;
			margin: 0 auto;
		}

		.back-btn {
			border: 1px solid #d5ddea;
			background: #fff;
			width: 36px;
			height: 36px;
			border-radius: 10px;
			cursor: pointer;
			font-size: 20px;
			line-height: 1;
			color: #334155;
		}

		.title {
			font-size: 20px;
			font-weight: 700;
			flex: 1;
		}

		.new-btn {
			border: none;
			background: #2f5dd7;
			color: #fff;
			border-radius: 10px;
			padding: 10px 14px;
			font-size: 13px;
			font-weight: 700;
			cursor: pointer;
		}

		.new-btn:hover { background: #2349b7; }

		.meta {
			color: #64748b;
			font-size: 13px;
			margin: 6px 2px 0;
			width: calc(100% - 48px);
			max-width: none;
			padding: 0 2px;
			margin-left: auto;
			margin-right: auto;
			text-align: center;
		}

		.filters {
			display: flex;
			gap: 10px;
			align-items: center;
			margin-bottom: 16px;
			flex-wrap: wrap;
		}

		.search-input {
			flex: 1;
			min-width: 200px;
			border: 1px solid #d8e0ed;
			border-radius: 8px;
			padding: 10px 12px;
			font-size: 13px;
			color: #334155;
		}

		.sort-select {
			border: 1px solid #d8e0ed;
			border-radius: 8px;
			padding: 10px 12px;
			font-size: 13px;
			color: #334155;
			background: #fff;
			cursor: pointer;
		}

		.pagination {
			display: flex;
			gap: 6px;
			align-items: center;
			justify-content: center;
			margin-top: 20px;
		}

		.pagination-btn {
			border: 1px solid #d8e0ed;
			background: #fff;
			color: #334155;
			border-radius: 6px;
			padding: 6px 10px;
			font-size: 12px;
			cursor: pointer;
			}

		.pagination-btn:hover {
			border-color: #2f5dd7;
			background: #f2f6ff;
		}

		.pagination-btn.active {
			border-color: #2f5dd7;
			background: #2f5dd7;
			color: #fff;
		}

		.pagination-btn:disabled {
			opacity: 0.5;
			cursor: not-allowed;
		}

		.pagination-info {
			font-size: 12px;
			color: #64748b;
		}

		.list {
			margin-top: 14px;
			display: grid;
			grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
			gap: 14px;
		}

		.draft-card {
			background: #fff;
			border: 1px solid #e2e8f2;
			border-radius: 12px;
			overflow: hidden;
			display: flex;
			flex-direction: column;
			cursor: pointer;
			transition: transform 0.14s ease, box-shadow 0.14s ease, border-color 0.14s ease;
		}

		.draft-card:hover {
			transform: translateY(-1px);
			border-color: #cbd5e1;
			box-shadow: 0 6px 20px rgba(15, 23, 42, 0.08);
		}

		.draft-cover {
			height: 170px;
			background: linear-gradient(140deg, #eef2ff, #f8fafc);
			position: relative;
			display: flex;
			align-items: center;
			justify-content: center;
		}

		.draft-cover img {
			width: 100%;
			height: 100%;
			object-fit: cover;
		}

		.draft-chip {
			position: absolute;
			top: 10px;
			left: 10px;
			background: rgba(15, 23, 42, 0.8);
			color: #fff;
			font-size: 11px;
			font-weight: 700;
			padding: 4px 8px;
			border-radius: 999px;
		}

		.draft-content {
			padding: 12px;
			display: flex;
			flex-direction: column;
			gap: 8px;
			width: 100%;
		}

		.draft-name {
			font-size: 16px;
			font-weight: 700;
			margin-bottom: 4px;
			color: #0f172a;
			line-height: 1.25;
			min-height: 38px;
		}

		.draft-stats {
			display: flex;
			gap: 8px;
			flex-wrap: wrap;
			color: #475569;
			font-size: 11px;
		}

		.draft-stat {
			background: #f8fafc;
			border: 1px solid #e2e8f2;
			border-radius: 999px;
			padding: 4px 8px;
		}

		.draft-updated {
			margin-top: 2px;
			color: #64748b;
			font-size: 12px;
		}

		.actions {
			display: grid;
			grid-template-columns: 1fr 1fr 1fr;
			gap: 8px;
			align-items: stretch;
		}

		.card-hint {
			font-size: 12px;
			font-weight: 700;
			color: #2f5dd7;
		}

		.btn {
			border: 1px solid #d4dce8;
			background: #fff;
			color: #334155;
			border-radius: 8px;
			padding: 8px 10px;
			font-size: 12px;
			font-weight: 700;
			cursor: pointer;
		}

		.btn:hover {
			background: #f8fafc;
		}

		.btn.open {
			border-color: #334155;
			color: #334155;
			background: #f8fafc;
		}

		.btn.edit {
			border-color: #2f5dd7;
			color: #2f5dd7;
			background: #f2f6ff;
		}

		.btn.delete {
			border-color: #f3c7cd;
			color: #c62839;
			background: #fff6f7;
		}

		.empty {
			background: #fff;
			border: 1px dashed #cfd8e3;
			color: #64748b;
			border-radius: 12px;
			padding: 24px;
			text-align: center;
			font-size: 14px;
		}

		.modal {
			position: fixed;
			inset: 0;
			background: rgba(15, 23, 42, 0.45);
			display: none;
			align-items: stretch;
			justify-content: stretch;
			z-index: 1000;
			padding: 0;
		}

		.modal.show {
			display: flex;
		}

		.modal-panel {
			width: 100%;
			height: 100%;
			max-height: 100vh;
			background: #fff;
			border-radius: 0;
			overflow: hidden;
			display: grid;
			grid-template-rows: auto 1fr auto;
		}

		.modal-header {
			display: flex;
			align-items: center;
			justify-content: space-between;
			gap: 8px;
			padding: 12px 14px;
			border-bottom: 1px solid #e2e8f2;
		}

		.modal-title {
			font-size: 18px;
			font-weight: 800;
			color: #0f172a;
		}

		.modal-close {
			border: 1px solid #d4dce8;
			background: #fff;
			color: #475569;
			border-radius: 8px;
			width: 34px;
			height: 34px;
			cursor: pointer;
			font-size: 18px;
			line-height: 1;
		}

		.modal-head-actions {
			display: flex;
			gap: 7px;
			align-items: center;
		}

		.mode-btn {
			border: 1px solid #d4dce8;
			background: #fff;
			color: #334155;
			border-radius: 8px;
			padding: 7px 10px;
			font-size: 12px;
			font-weight: 700;
			cursor: pointer;
		}

		.mode-btn.active {
			border-color: #2f5dd7;
			background: #f2f6ff;
			color: #2f5dd7;
		}

		.mode-btn.delete {
			border-color: #f3c7cd;
			background: #fff6f7;
			color: #c62839;
		}

		.modal-body {
			overflow: auto;
			padding: 14px;
			display: grid;
			grid-template-columns: 280px 1fr;
			gap: 14px;
		}

		.preview-card {
			border: 1px solid #e2e8f2;
			border-radius: 12px;
			padding: 10px;
			background: #f8fafc;
			height: fit-content;
		}

		.preview-main {
			width: 100%;
			height: 220px;
			object-fit: cover;
			border-radius: 10px;
			border: 1px solid #d8e0ed;
			background: #fff;
		}

		.preview-meta {
			font-size: 12px;
			color: #475569;
			margin-top: 8px;
			display: grid;
			gap: 5px;
		}

		.video-preview {
			width: 100%;
			margin-top: 10px;
			max-height: 240px;
			border: 1px solid #d8e0ed;
			border-radius: 10px;
			background: #000;
		}

		.form-grid {
			display: grid;
			grid-template-columns: 1fr 1fr;
			gap: 10px;
		}

		.form-group {
			display: grid;
			gap: 5px;
		}

		.form-group.full {
			grid-column: 1 / -1;
		}

		.label {
			font-size: 12px;
			font-weight: 700;
			color: #334155;
		}

		.input, .textarea, .select {
			width: 100%;
			border: 1px solid #d4dce8;
			border-radius: 8px;
			padding: 9px 10px;
			font-size: 13px;
			color: #1e293b;
			background: #fff;
		}

		.textarea {
			min-height: 90px;
			resize: vertical;
		}

		.inline-row {
			display: flex;
			align-items: center;
			gap: 8px;
			flex-wrap: wrap;
		}

		.toggle-btn {
			border: 1px solid #d4dce8;
			background: #fff;
			color: #334155;
			border-radius: 8px;
			padding: 7px 10px;
			font-size: 12px;
			font-weight: 700;
			cursor: pointer;
		}

		.toggle-btn.active {
			border-color: #2f5dd7;
			background: #f2f6ff;
			color: #2f5dd7;
		}

		.card {
			margin: 14px;
			background: #fff;
			border: 1px solid #e2e8f2;
			border-radius: 14px;
			overflow: hidden;
			box-shadow: 0 10px 24px rgba(17, 24, 39, 0.06);
		}

		.row {
			padding: 18px;
			border-bottom: 1px solid #e9eef2;
		}

		.row:last-child {
			border-bottom: none;
		}

		.field-label {
			font-size: 16px;
			font-weight: 700;
			margin-bottom: 10px;
			color: #18202a;
		}

		.required-mark {
			color: #e54c63;
		}

		.hint {
			color: #5f6c7a;
			font-size: 13px;
			margin-top: 6px;
		}

		.category-line {
			display: flex;
			align-items: center;
			gap: 8px;
			flex-wrap: wrap;
		}

		.new-cat-btn {
			border: 1px solid #d4dce8;
			background: #fff;
			color: #334155;
			border-radius: 8px;
			padding: 7px 10px;
			font-size: 12px;
			font-weight: 700;
			cursor: pointer;
		}

		.new-cat-btn:hover {
			border-color: #2f5dd7;
			background: #f2f6ff;
			color: #2f5dd7;
		}

		.dual-row {
			display: grid;
			grid-template-columns: 1fr 1fr;
			gap: 10px;
		}

		.error-text {
			color: #c62839;
			font-size: 12px;
			margin-top: 6px;
		}

		input[type="file"] {
			width: 100%;
			padding: 10px;
			border: 1px solid #d4dce8;
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

		.help {
			font-size: 11px;
			color: #64748b;
		}

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

		.variant-remove-btn:hover {
			border-color: #b8c4d6;
		}

		.variant-add-btn {
			margin-top: 10px;
		}

		.modal-footer {
			display: flex;
			justify-content: space-between;
			align-items: center;
			gap: 8px;
			padding: 12px 14px;
			border-top: 1px solid #e2e8f2;
			background: #fff;
		}

		.footer-msg {
			font-size: 12px;
			color: #64748b;
			min-height: 18px;
		}

		.footer-actions {
			display: flex;
			gap: 8px;
		}

		.btn.primary {
			border-color: #2f5dd7;
			background: #2f5dd7;
			color: #fff;
		}

		.btn.success {
			border-color: #198754;
			background: #198754;
			color: #fff;
		}

		.hidden {
			display: none !important;
		}

		.swal-overlay {
			position: fixed;
			inset: 0;
			background: rgba(15, 23, 42, 0.45);
			display: none;
			align-items: center;
			justify-content: center;
			z-index: 1300;
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
		.swal-icon.warning { background: #fff7e8; color: #b7791f; }

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

		@media (max-width: 780px) {
			.page {
				width: calc(100% - 24px);
				padding-top: 104px;
			}
			.topbar-inner,
			.meta {
				width: calc(100% - 24px);
			}
			.modal-body {
				grid-template-columns: 1fr;
			}
			.variant-row {
				grid-template-columns: 1fr;
			}
			.actions {
				grid-template-columns: 1fr;
			}
			.new-btn {
				padding: 9px 10px;
			}
			.filters {
				flex-direction: column;
				align-items: stretch;
			}
			.search-input,
			.sort-select {
				width: 100%;
			}
			.list {
				grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
			}
		}
	</style>
</head>
<body>
	<div class="page">
		<div class="topbar">
			<div class="topbar-inner">
				<button class="back-btn" type="button" onclick="window.location.href='admin_dashboard.php'">←</button>
				<div class="title">Product Drafts</div>
				<button class="new-btn" type="button" onclick="window.location.href='admin_add_product.php'">+ New Draft</button>
			</div>
			<div class="meta">Manage saved drafts and continue editing anytime.</div>
		</div>

		<div class="filters" style="margin: 14px;">
			<input type="text" id="draftSearchInput" class="search-input" placeholder="Search by product name..." onkeyup="applyDraftSearch()">
			<select id="draftSortSelect" class="sort-select" onchange="applyDraftSort(this.value)">
				<option value="">Sort by</option>
				<option value="newest">Newest to Oldest</option>
				<option value="oldest">Oldest to Newest</option>
			</select>
		</div>

		<div id="draftList" class="list" style="margin: 14px;">
			<div class="empty">Loading drafts...</div>
		</div>

		<div id="paginationContainer" class="pagination" style="margin: 14px;">
		</div>

	<div id="draftModal" class="modal">
		<div class="modal-panel" role="dialog" aria-modal="true" aria-labelledby="draftModalTitle">
			<div class="modal-header">
				<div id="draftModalTitle" class="modal-title">Edit Draft</div>
				<div class="modal-head-actions">
					<button type="button" id="modalOpenBtn" class="mode-btn active" onclick="setEditorMode(false)">Open</button>
					<button type="button" id="modalEditBtn" class="mode-btn" onclick="setEditorMode(true)">Edit</button>
					<button type="button" id="modalDeleteBtn" class="mode-btn delete" onclick="deleteModalDraft()">Delete</button>
					<button type="button" class="modal-close" onclick="closeModal()">×</button>
				</div>
			</div>
			<div class="modal-body">
				<div class="preview-card">
					<img id="previewMain" class="preview-main" alt="Draft preview" src="" />
					<div class="preview-meta" id="previewMeta"></div>
				</div>
					<div>
						<div class="card">
							<div class="row">
								<div class="field-label" id="imagesLabel">Product Images (0/8) <span class="required-mark">*</span></div>
								<input id="imagesInput" type="file" name="images[]" accept="image/*" multiple>
								<div class="hint">Optional, max 8 images. First image will be the cover image.</div>
								<div class="hint">Click Pin image on a preview to choose the pinned image.</div>
								<div id="imagesError" class="error-text hidden"></div>
								<div id="mainImagesGrid" class="main-images-grid"></div>
							</div>

							<div class="row">
								<div class="field-label">Product Video (0/1)</div>
								<input id="videoInput" type="file" name="video" accept="video/*">
								<div class="hint">Optional, max 1 video (MP4/WEBM/MOV).</div>
								<div id="mVideoInfo" class="hint"></div>
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
									<button type="button" class="new-cat-btn" onclick="setCategoryMode(true)">+ New Category</button>
								</div>
								<div id="newCategoryBox" class="hidden">
									<input id="newCategoryName" class="input" type="text" placeholder="Enter new category name">
									<div style="margin-top:8px;">
										<button type="button" class="new-cat-btn" onclick="setCategoryMode(false)">Use Existing Category</button>
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
								<button id="addVariantBtn" type="button" class="new-cat-btn variant-add-btn" onclick="addVariantRow()">+ Add Variant</button>
							</div>

							<div class="row">
								<div class="field-label">Product Description</div>
								<textarea id="description" name="product_description" class="textarea" placeholder="Write product details..."></textarea>
							</div>
						</div>
					</div>
			</div>
			<div class="modal-footer">
				<div id="mStatus" class="footer-msg"></div>
				<div class="footer-actions">
					<button type="button" class="btn" onclick="closeModal()">Close</button>
					<button type="button" id="btnSaveDraft" class="btn primary" onclick="saveModalDraft()">Save as Draft</button>
					<button type="button" id="btnPublish" class="btn success" onclick="publishModalDraft()">Publish</button>
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
				<button id="localSwalConfirm" type="button" class="swal-btn">OK</button>
			</div>
		</div>
	</div>

	<script>
		let cachedDrafts = [];
		let filteredDrafts = [];
		let currentDraftPage = 1;
		let draftSearchQuery = '';
		let draftSortMode = '';
		let draftPageSize = 12;
		let modalDraft = null;
		let modalUseNewCategory = false;
		let modalVariantsList = [];
		let modalVariantTempIdCounter = 1;
		let modalIsEditing = false;
		let modalMainImageStates = {};
		let modalNewMainImages = [];
		let modalPinnedImageKey = '';
		let modalPendingVideoPreviewUrl = '';

		function formatDate(value) {
			if (!value) return 'Unknown';
			const d = new Date(String(value).replace(' ', 'T'));
			if (Number.isNaN(d.getTime())) return value;
			return d.toLocaleString();
		}

		function formatPeso(value) {
			const amount = Number(value || 0);
			if (!Number.isFinite(amount)) return '0';
			return amount.toLocaleString('en-PH', { minimumFractionDigits: 0, maximumFractionDigits: 2 });
		}

		function escapeHtml(value) {
			return String(value ?? '')
				.replace(/&/g, '&amp;')
				.replace(/</g, '&lt;')
				.replace(/>/g, '&gt;')
				.replace(/"/g, '&quot;')
				.replace(/'/g, '&#39;');
		}

		function byId(id) {
			return document.getElementById(id) ||
				document.getElementById(id.startsWith('m') && id.length > 1 ? id.slice(1, 2).toLowerCase() + id.slice(2) : `m${id.charAt(0).toUpperCase()}${id.slice(1)}`);
		}

		function getVideoFileName(videoUrl) {
			const raw = String(videoUrl || '').trim();
			if (!raw) return '';
			const clean = raw.split('?')[0].split('#')[0];
			return clean.split(/[\\/]/).pop() || '';
		}

		function revokeModalPendingVideoPreview() {
			if (!modalPendingVideoPreviewUrl) return;
			URL.revokeObjectURL(modalPendingVideoPreviewUrl);
			modalPendingVideoPreviewUrl = '';
		}

		function renderModalVideoManager() {
			const input = byId('mVideoInput');
			const info = byId('mVideoInfo') || byId('videoName');
			const player = byId('mVideoPreview');
			if (!input || !info || !player) return;

			const selectedFile = input.files && input.files[0] ? input.files[0] : null;
			if (selectedFile) {
				revokeModalPendingVideoPreview();
				modalPendingVideoPreviewUrl = URL.createObjectURL(selectedFile);
				player.src = modalPendingVideoPreviewUrl;
				player.load();
				player.classList.remove('hidden');
				info.textContent = `Selected video: ${selectedFile.name}`;
				return;
			}

			revokeModalPendingVideoPreview();
			player.pause();
			player.src = '';
			player.removeAttribute('src');
			player.load();

			const existingVideo = String(modalDraft?.media?.video || '').trim();
			if (existingVideo) {
				player.src = existingVideo;
				player.load();
				player.classList.remove('hidden');
				const fileName = getVideoFileName(existingVideo);
				info.textContent = fileName ? `Existing draft video: ${fileName}` : 'Existing draft video is set.';
				return;
			}

			player.classList.add('hidden');
			info.textContent = 'No video in draft';
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
					resolve(true);
				};

				cancelBtn.onclick = () => {
					cleanup();
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

		function localConfirm(title, text, confirmText = 'Confirm', cancelText = 'Cancel') {
			return openLocalSweetAlert({
				type: 'warning',
				title,
				text,
				confirmText,
				cancelText,
				showCancel: true
			});
		}

		function setStatus(message, isError = false) {
			const el = byId('mStatus');
			if (!el) return;
			el.textContent = message || '';
			el.style.color = isError ? '#c62839' : '#64748b';
		}

		function applyModalPinnedMainImageFallback() {
			const isValid = (() => {
				if (!modalPinnedImageKey) return false;
				if (modalPinnedImageKey.startsWith('e:')) {
					const idx = Number(modalPinnedImageKey.slice(2));
					const path = modalDraft?.media?.images?.[idx];
					return path && !modalMainImageStates[String(idx)];
				}
				if (modalPinnedImageKey.startsWith('n:')) {
					const idx = Number(modalPinnedImageKey.slice(2));
					return modalNewMainImages[idx];
				}
				return false;
			})();

			if (isValid) return;

			// Find first available existing image
			const existingImages = Array.isArray(modalDraft?.media?.images) ? modalDraft.media.images : [];
			const firstExisting = existingImages.findIndex((_, idx) => !modalMainImageStates[String(idx)]);
			if (firstExisting >= 0) {
				modalPinnedImageKey = `e:${firstExisting}`;
				return;
			}

			// Or first new file
			if (modalNewMainImages.length > 0) {
				modalPinnedImageKey = 'n:0';
				return;
			}

			modalPinnedImageKey = '';
		}

		function getVariantRowsData() {
			return modalVariantsList.map((variant) => {
				const imageFiles = [];
				const existingImages = (variant.images || [])
					.filter(img => !img.deleted)
					.map(img => img.path || img.previewUrl);

				(variant.newImages || []).forEach((img) => {
					if (img.file) imageFiles.push(img.file);
				});

				return {
					id: variant.id,
					tempId: variant.tempId,
					name: variant.name,
					price: variant.price,
					stock: variant.stock,
					imageFiles,
					existingImages,
					pinnedImageKey: variant.pinnedImageKey || ''
				};
			}).filter((v) => v.name || v.price !== '' || v.stock !== '');
		}

		function addVariantRow(variant = null) {
			if (!modalIsEditing && variant === null) return;
			
			const tempId = modalVariantTempIdCounter++;
			const variantData = {
				id: variant?.id || 0,
				tempId: tempId,
				name: variant?.name || '',
				price: variant?.price || '',
				stock: variant?.stock || '',
				images: variant?.images && Array.isArray(variant.images) 
					? variant.images.map(img => ({ path: img, deleted: false, previewUrl: img }))
					: [],
				newImages: [],
				pinnedImageKey: ''
			};

			modalVariantsList.push(variantData);
			renderVariants();
		}

		function renderVariants() {
			const list = byId('variantRows');
			if (!list) return;

			list.innerHTML = modalVariantsList.map((variant, listIdx) => `
				<div class="variant-row" data-variant-list-idx="${listIdx}">
					<div>
						<input type="text" class="input variant-name" placeholder="Variant Name (e.g. Red 64GB)" value="${escapeHtml(variant.name || '')}">
					</div>
					<div>
						<input type="number" min="0" step="0.01" class="input variant-price" placeholder="Price" value="${escapeHtml(variant.price ?? '')}">
					</div>
					<div>
						<input type="number" min="0" step="1" class="input variant-stock" placeholder="Stock" value="${escapeHtml(variant.stock ?? '')}">
					</div>
					<div>
						<input type="file" class="input variant-image-input" accept="image/*" multiple title="Add variant images" data-variant-temp-id="${variant.tempId}">
						<div class="variant-image-count">0 / 8 images</div>
						<div class="variant-images-grid"></div>
					</div>
					<button type="button" class="variant-remove-btn">Remove</button>
				</div>
			`).join('');

			// Attach event listeners
			list.querySelectorAll('.variant-row').forEach((row, idx) => {
				const variant = modalVariantsList[idx];
				if (!variant) return;

				const nameInput = row.querySelector('.variant-name');
				const priceInput = row.querySelector('.variant-price');
				const stockInput = row.querySelector('.variant-stock');
				const removeBtn = row.querySelector('.variant-remove-btn');
				const imageInput = row.querySelector('.variant-image-input');
				const countEl = row.querySelector('.variant-image-count');
				const imagesGrid = row.querySelector('.variant-images-grid');

				if (nameInput) nameInput.addEventListener('change', (e) => { variant.name = e.target.value; });
				if (priceInput) priceInput.addEventListener('change', (e) => { variant.price = e.target.value; });
				if (stockInput) stockInput.addEventListener('change', (e) => { variant.stock = e.target.value; });

				if (removeBtn) {
					removeBtn.addEventListener('click', () => {
						modalVariantsList.splice(idx, 1);
						renderVariants();
					});
				}

				if (imageInput) {
					imageInput.addEventListener('change', () => {
						const files = Array.from(imageInput.files || []);
						imageInput.value = '';
						
						const totalImages = getVariantActiveImageCount(variant);
						const remainingSlots = 8 - totalImages;

						if (remainingSlots <= 0) {
							showLocalSweetAlert('error', 'Image Limit Reached', 'Each variant can keep up to 8 images only.');
							return;
						}

						const filesToAdd = files.slice(0, remainingSlots);
						filesToAdd.forEach((file) => {
							variant.newImages.push({
								file,
								previewUrl: URL.createObjectURL(file)
							});
						});

						renderVariantImageGrid(idx, variant, imagesGrid, countEl);
					});
				}

				renderVariantImageGrid(idx, variant, imagesGrid, countEl);
			});
		}

		function getVariantActiveImageCount(variant) {
			if (!variant) return 0;
			const existingCount = (variant.images || []).filter(img => !img.deleted).length;
			const newCount = (variant.newImages || []).length;
			return existingCount + newCount;
		}

		function renderVariantImageGrid(listIdx, variant, gridEl, countEl) {
			if (!gridEl || !countEl) return;

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
						<img src="${escapeHtml(item.previewUrl || item.path || '')}" alt="Variant image">
						<div class="variant-image-actions">
							<label class="variant-image-pin">
								<input type="radio" name="variantPin_${variant.tempId}" value="${key}" ${checked} ${item.deleted ? 'disabled' : ''}>
								<span>Pin</span>
							</label>
							<button type="button" class="variant-image-remove-btn" data-existing-index="${idx}" data-variant-list-idx="${listIdx}">${buttonLabel}</button>
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
						<img src="${escapeHtml(item.previewUrl || '')}" alt="New variant image">
						<div class="variant-image-actions">
							<label class="variant-image-pin">
								<input type="radio" name="variantPin_${variant.tempId}" value="${key}" ${checked}>
								<span>Pin</span>
							</label>
							<button type="button" class="variant-image-remove-btn" data-new-index="${idx}" data-variant-list-idx="${listIdx}">Remove</button>
						</div>
					</div>
				`);
			});

			gridEl.innerHTML = cards.length ? cards.join('') : '<div style="grid-column: 1/-1; padding: 20px; text-align: center; color: #999; font-size: 13px;">Click "Choose File" to add images (1-8)</div>';
			countEl.textContent = `${getVariantActiveImageCount(variant)} / 8 images`;

			// Radio button handlers
			gridEl.querySelectorAll(`input[name="variantPin_${variant.tempId}"]`).forEach((radio) => {
				radio.addEventListener('change', () => {
					if (radio.checked) {
						variant.pinnedImageKey = radio.value;
						renderVariantImageGrid(listIdx, variant, gridEl, countEl);
					}
				});
			});

			// Existing image remove buttons
			gridEl.querySelectorAll('[data-existing-index]').forEach((btn) => {
				btn.addEventListener('click', () => {
					const idx = Number(btn.getAttribute('data-existing-index'));
					if (Number.isInteger(idx) && variant.images[idx]) {
						variant.images[idx].deleted = !variant.images[idx].deleted;
						applyVariantPinnedFallback(variant);
						renderVariantImageGrid(listIdx, variant, gridEl, countEl);
					}
				});
			});

			// New image remove buttons
			gridEl.querySelectorAll('[data-new-index]').forEach((btn) => {
				btn.addEventListener('click', () => {
					const idx = Number(btn.getAttribute('data-new-index'));
					if (Number.isInteger(idx) && variant.newImages[idx]) {
						const removed = variant.newImages.splice(idx, 1)[0];
						if (removed && removed.previewUrl) {
							URL.revokeObjectURL(removed.previewUrl);
						}
						applyVariantPinnedFallback(variant);
						renderVariantImageGrid(listIdx, variant, gridEl, countEl);
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

		function setEditorMode(isEditing) {
			modalIsEditing = !!isEditing;
			byId('modalOpenBtn')?.classList.toggle('active', !modalIsEditing);
			byId('modalEditBtn')?.classList.toggle('active', modalIsEditing);

			// Handle all form inputs and controls
			const controls = document.querySelectorAll('#draftModal .input, #draftModal .textarea, #draftModal .select, #draftModal .toggle-btn, #draftModal .variant-remove-btn, #draftModal .new-cat-btn.variant-add-btn, #draftModal .variant-image-remove-btn');
			controls.forEach((el) => {
				if (modalIsEditing) {
					el.removeAttribute('disabled');
				} else {
					el.setAttribute('disabled', 'disabled');
				}
			});

			if (!modalIsEditing) {
				byId('btnSaveDraft')?.setAttribute('disabled', 'disabled');
				byId('btnPublish')?.setAttribute('disabled', 'disabled');
				byId('addVariantBtn')?.setAttribute('disabled', 'disabled');
				setStatus('Preview mode. Click Edit to modify this draft.');
			} else {
				byId('btnSaveDraft')?.removeAttribute('disabled');
				byId('btnPublish')?.removeAttribute('disabled');
				byId('addVariantBtn')?.removeAttribute('disabled');
				setStatus('Editing enabled. Save as Draft or Publish when ready.');
			}
		}

		function setCategoryMode(useNew) {
			if (!modalIsEditing) return;
			modalUseNewCategory = !!useNew;
			const existingBtn = byId('btnExistingCategory');
			const newBtn = byId('btnNewCategory');
			if (existingBtn) existingBtn.classList.toggle('active', !modalUseNewCategory);
			if (newBtn) newBtn.classList.toggle('active', modalUseNewCategory);
			byId('existingCategoryBox')?.classList.toggle('hidden', modalUseNewCategory);
			byId('newCategoryBox')?.classList.toggle('hidden', !modalUseNewCategory);
		}

		function getDraftThumb(draft) {
			const imgPath = draft?.media?.images?.[0]?.path || '';
			if (imgPath) return imgPath;
			return 'data:image/svg+xml;utf8,' + encodeURIComponent('<svg xmlns="http://www.w3.org/2000/svg" width="600" height="360"><rect width="100%" height="100%" fill="#e2e8f0"/><text x="50%" y="50%" font-size="24" text-anchor="middle" dominant-baseline="middle" fill="#64748b">No Image</text></svg>');
		}

		function renderImageStrip() {
			const grid = byId('mainImagesGrid');
			if (!grid || !modalDraft) return;

			const existingImages = Array.isArray(modalDraft?.media?.images) ? modalDraft.media.images : [];
			applyModalPinnedMainImageFallback();

			const cards = [];

			existingImages.forEach((img, idx) => {
				const key = `e:${idx}`;
				const checked = modalPinnedImageKey === key ? 'checked' : '';
				const deleted = modalMainImageStates[String(idx)] || false;
				const removedClass = deleted ? 'removed' : '';
				const buttonLabel = deleted ? 'Undo Remove' : 'Remove';
				cards.push(`
					<div class="main-image-card ${removedClass}">
						${checked && !deleted ? '<span class="main-image-badge">Pinned</span>' : ''}
						<img src="${escapeHtml(img.path || '')}" alt="Main image ${idx + 1}">
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

			modalNewMainImages.forEach((item, idx) => {
				const key = `n:${idx}`;
				const checked = modalPinnedImageKey === key ? 'checked' : '';
				cards.push(`
					<div class="main-image-card">
						${checked ? '<span class="main-image-badge">Pinned</span>' : ''}
						<img src="${escapeHtml(item.previewUrl || '')}" alt="New main image ${idx + 1}">
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
				grid.innerHTML = '<div style="text-align:center;color:#999;font-size:13px;padding:20px;grid-column:1/-1;">No images in this draft.</div>';
			} else {
				grid.innerHTML = cards.join('');

				grid.querySelectorAll('input[name="mainPinnedImage"]').forEach((radio) => {
					radio.addEventListener('change', () => {
						if (radio.checked) {
							modalPinnedImageKey = radio.value;
							renderImageStrip();
						}
					});
				});

				grid.querySelectorAll('[data-existing-index]').forEach((btn) => {
					btn.addEventListener('click', () => {
						const idx = Number(btn.getAttribute('data-existing-index'));
						if (Number.isInteger(idx)) {
							modalMainImageStates[String(idx)] = !modalMainImageStates[String(idx)];
							applyModalPinnedMainImageFallback();
							renderImageStrip();
						}
					});
				});

				grid.querySelectorAll('[data-new-index]').forEach((btn) => {
					btn.addEventListener('click', () => {
						const idx = Number(btn.getAttribute('data-new-index'));
						if (Number.isInteger(idx) && modalNewMainImages[idx]) {
							const removed = modalNewMainImages.splice(idx, 1)[0];
							if (removed && removed.previewUrl) {
								URL.revokeObjectURL(removed.previewUrl);
							}
							applyModalPinnedMainImageFallback();
							renderImageStrip();
						}
					});
				});
			}
		}

		async function loadCategories(selectedId = '') {
			const select = byId('mCategorySelect');
			if (!select) return;
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
				select.innerHTML = data.categories.map((cat) => {
					const id = Number(cat.category_id);
					const selected = String(id) === String(selectedId || '') ? ' selected' : '';
					return `<option value="${id}"${selected}>${escapeHtml(cat.category_name || '')}</option>`;
				}).join('');
			} catch (err) {
				select.innerHTML = '<option value="">Unable to load categories</option>';
			}
		}

		function fillModalForm(draft) {
			modalDraft = draft;
			modalVariantsList = [];
			modalVariantTempIdCounter = 1;
			modalMainImageStates = {};
			modalNewMainImages = [];
			modalPinnedImageKey = '';
			
			const modalTitleEl = byId('draftModalTitle');
			const mProductNameEl = byId('mProductName');
			const mDescriptionEl = byId('mDescription');
			const mPriceEl = byId('mPrice');
			const mStockEl = byId('mStock');
			const mNewCategoryNameEl = byId('mNewCategoryName');
			const previewMainEl = byId('previewMain');
			const previewMetaEl = byId('previewMeta');

			if (modalTitleEl) modalTitleEl.textContent = `Edit Draft #${Number(draft.draft_id || 0)}`;
			if (mProductNameEl) mProductNameEl.value = draft.product_name || '';
			if (mDescriptionEl) mDescriptionEl.value = draft.product_description || '';
			if (mPriceEl) mPriceEl.value = draft.price || '';
			if (mStockEl) mStockEl.value = draft.product_stock || '';
			if (mNewCategoryNameEl) mNewCategoryNameEl.value = draft.new_category_name || '';

			// Open in edit mode so Save as Draft / Publish works immediately.
			setEditorMode(true);
			setCategoryMode(Number(draft.use_new_category || 0) === 1);
			loadCategories(draft.category_id || '');

			const imageList = Array.isArray(draft?.media?.images) ? draft.media.images : [];
			
			// Set pinned image key from draft
			const pinnedIdx = imageList.findIndex((img) => img.is_pinned);
			if (pinnedIdx >= 0) {
				modalPinnedImageKey = `e:${pinnedIdx}`;
			}
			
			const thumb = getDraftThumb(draft);
			if (previewMainEl) previewMainEl.src = thumb;
			if (previewMetaEl) previewMetaEl.innerHTML = `
				<div><strong>Category:</strong> ${escapeHtml(draft.category_label || (draft.use_new_category ? draft.new_category_name : 'Uncategorized'))}</div>
				<div><strong>Price:</strong> ${draft.price !== '' ? `₱${formatPeso(draft.price)}` : 'Not set'}</div>
				<div><strong>Stock:</strong> ${draft.product_stock !== '' ? `${draft.product_stock}` : 'Not set'}</div>
				<div><strong>Images:</strong> ${imageList.length} ${draft?.media?.video ? '+ video' : ''}</div>
				<div><strong>Updated:</strong> ${escapeHtml(formatDate(draft.updated_at))}</div>
			`;

			(Array.isArray(draft.variants) ? draft.variants : []).forEach((v) => {
				const variantImages = Array.isArray(v.images) ? v.images : [];
				addVariantRow({
					id: Number(v.id || 0),
					name: v.name || '',
					price: v.price ?? '',
					stock: v.stock ?? '',
					images: variantImages
				});
			});

			const mImagesInputEl = byId('mImagesInput');
			if (mImagesInputEl) mImagesInputEl.value = '';
			
			const mVideoInputEl = byId('mVideoInput');
			if (mVideoInputEl) mVideoInputEl.value = '';

			renderModalVideoManager();
			
			renderImageStrip();
		}

		function openModal() {
			byId('draftModal')?.classList.add('show');
		}

		function closeModal() {
			byId('draftModal')?.classList.remove('show');
			revokeModalPendingVideoPreview();
			const player = byId('mVideoPreview');
			if (player) {
				player.pause();
				player.src = '';
				player.removeAttribute('src');
				player.classList.add('hidden');
			}
			modalDraft = null;
		}

		async function deleteModalDraft() {
			if (!modalDraft) return;
			const draftId = Number(modalDraft.draft_id || 0);
			if (!draftId) return;
			const confirmed = await localConfirm('Delete Draft', 'Delete this draft permanently?', 'Delete', 'Cancel');
			if (!confirmed) return;
			await deleteDraft(draftId, false);
			closeModal();
		}

		async function openDraftModal(draftId) {
			try {
				const res = await fetch(`api/get-product-drafts.php?draft_id=${encodeURIComponent(draftId)}`, { cache: 'no-store' });
				const data = await res.json();
				if (!res.ok || !data.success || !data.draft) {
					throw new Error(data.error || 'Unable to load draft');
				}
				fillModalForm(data.draft);
				openModal();
			} catch (err) {
				await showLocalSweetAlert('error', 'Open Draft Failed', err.message || 'Unable to open draft.');
			}
		}

		function validateModalForm(forPublish = false) {
			if (!modalDraft) return 'Draft not loaded.';
			const name = byId('mProductName').value.trim();
			const description = byId('mDescription').value.trim();
			const price = byId('mPrice').value.trim();
			const stock = byId('mStock').value.trim();
			if (forPublish) {
				if (!name) return 'Product name is required.';
				if (!description) return 'Description is required.';
				if (price === '' || Number(price) < 0) return 'Price must be non-negative.';
				if (stock === '' || Number(stock) < 0) return 'Stock must be non-negative.';
			}
			if (modalUseNewCategory) {
				if (!byId('mNewCategoryName').value.trim() && forPublish) return 'New category name is required.';
			} else if (!byId('mCategorySelect').value && forPublish) {
				return 'Please choose a category.';
			}

			const variants = getVariantRowsData();
			for (const v of variants) {
				if (forPublish && !v.name) return 'Each variant must have a name.';
				if (forPublish && (v.price === '' || Number(v.price) < 0)) return 'Each variant price must be non-negative.';
				if (forPublish && (v.stock === '' || Number(v.stock) < 0)) return 'Each variant stock must be non-negative.';
				const allImages = [...(v.imageFiles || []), ...(v.existingImages || [])];
				if (forPublish && allImages.length === 0) return 'Each variant must have at least one image.';
				if (forPublish && allImages.length > 8) return 'Each variant can have up to 8 images only.';
			}

			const existingImgs = Array.isArray(modalDraft?.media?.images) ? modalDraft.media.images : [];
			const nonDeletedExistingImgs = existingImgs.filter((_, idx) => !modalMainImageStates[String(idx)]);
			const totalMainImages = nonDeletedExistingImgs.length + modalNewMainImages.length;
			if (forPublish && totalMainImages === 0) {
				return 'At least one main image is required.';
			}
			if (forPublish && totalMainImages > 8) {
				return 'Main product can have up to 8 images only.';
			}

			return '';
		}

		function buildDraftPayloadFormData() {
			const fd = new FormData();
			fd.append('draft_id', String(Number(modalDraft?.draft_id || 0)));
			fd.append('product_name', byId('mProductName').value || '');
			fd.append('product_description', byId('mDescription').value || '');
			fd.append('price', byId('mPrice').value || '');
			fd.append('product_stock', byId('mStock').value || '');

			const existingImgs = Array.isArray(modalDraft?.media?.images) ? modalDraft.media.images : [];
			const nonDeletedExistingImgs = existingImgs.filter((_, idx) => !modalMainImageStates[String(idx)]);
			const hasNewImages = modalNewMainImages.length > 0;
			const totalImages = nonDeletedExistingImgs.length + modalNewMainImages.length;
			const imageCount = totalImages;
			fd.append('pinned_image_key', modalPinnedImageKey || '');
			fd.append('image_count', String(imageCount));

			const hasExistingVideo = !!modalDraft?.media?.video;
			const videoFile = byId('mVideoInput')?.files?.[0] || null;
			fd.append('has_video', String((videoFile || hasExistingVideo) ? 1 : 0));

			fd.append('use_new_category', modalUseNewCategory ? '1' : '0');
			fd.append('category_id', byId('mCategorySelect').value || '');
			fd.append('new_category_name', byId('mNewCategoryName').value || '');
			fd.append('deleted_image_paths', JSON.stringify(
				existingImgs
					.filter((_, idx) => modalMainImageStates[String(idx)])
					.map(img => img.path)
			));

			const variants = getVariantRowsData().map((v) => ({
				id: v.id,
				temp_id: v.tempId,
				name: v.name,
				price: v.price,
				stock: v.stock,
				pinnedImageKey: v.pinnedImageKey || ''
			}));
			fd.append('variants', JSON.stringify(variants));

			// Add new main images
			modalNewMainImages.slice(0, 8).forEach((img) => fd.append('images[]', img.file));
			if (videoFile) fd.append('video', videoFile);

			getVariantRowsData().forEach((v) => {
				const variantUploadId = Number(v.id || 0) > 0 ? Number(v.id) : Number(v.tempId || 0);
				if (!variantUploadId) return;
				(v.imageFiles || []).forEach((file, idx) => {
					fd.append(`variant_${variantUploadId}_image_${idx}`, file);
				});
			});

			return fd;
		}

		async function saveModalDraft() {
			if (!modalIsEditing) {
				setStatus('Switch to Edit mode first.', true);
				return;
			}
			if (!modalDraft || Number(modalDraft.draft_id || 0) <= 0) {
				setStatus('Draft is not ready yet. Re-open the draft and try again.', true);
				return;
			}
			const err = validateModalForm(false);
			if (err) {
				setStatus(err, true);
				return;
			}
			setStatus('Saving draft...');
			const saveBtn = byId('btnSaveDraft');
			if (saveBtn) saveBtn.disabled = true;
			try {
				const fd = buildDraftPayloadFormData();
				const res = await fetch('api/save-product-draft.php', { method: 'POST', body: fd });
				const raw = await res.text();
				let data = null;
				try {
					data = raw ? JSON.parse(raw) : null;
				} catch (_e) {
				}
				if (!res.ok || !data.success) {
					throw new Error((data && data.error) ? data.error : (raw || 'Failed to save draft'));
				}
				setStatus('Draft saved successfully.');
				await showLocalSweetAlert('success', 'Draft Saved', 'Draft saved successfully.');
				if (Number(data.draft_id || 0) > 0) {
					await openDraftModal(Number(data.draft_id));
				}
				await loadDrafts();
			} catch (e) {
				setStatus(e.message || 'Unable to save draft.', true);
				await showLocalSweetAlert('error', 'Draft Save Failed', e.message || 'Unable to save draft.');
			} finally {
				if (saveBtn) saveBtn.disabled = false;
			}
		}

		async function publishModalDraft() {
			if (!modalIsEditing) {
				setStatus('Switch to Edit mode first.', true);
				return;
			}
			if (!modalDraft || Number(modalDraft.draft_id || 0) <= 0) {
				setStatus('Draft is not ready yet. Re-open the draft and try again.', true);
				return;
			}
			const err = validateModalForm(true);
			if (err) {
				setStatus(err, true);
				return;
			}

			const confirmed = await localConfirm(
				'Publish Draft',
				'This will publish the draft and create the product listing. Continue?',
				'Continue',
				'Cancel'
			);
			if (!confirmed) {
				setStatus('Publish cancelled.');
				return;
			}

			setStatus('Publishing draft...');
			const publishBtn = byId('btnPublish');
			if (publishBtn) publishBtn.disabled = true;
			try {
				const fd = new FormData();
				fd.append('draft_id', String(Number(modalDraft?.draft_id || 0)));
				fd.append('product_name', byId('mProductName').value.trim());
				fd.append('product_description', byId('mDescription').value.trim());
				fd.append('price', byId('mPrice').value);
				fd.append('product_stock', String(Math.floor(Number(byId('mStock').value || 0))));

				const variants = getVariantRowsData().map((v) => ({
					id: Number(v.id),
					temp_id: Number(v.tempId || 0),
					name: String(v.name || '').trim(),
					price: Number(v.price),
					stock: Number(v.stock || 0),
					pinnedImageKey: v.pinnedImageKey || ''
				}));
				fd.append('variants', JSON.stringify(variants));

				if (modalUseNewCategory) {
					fd.append('category_mode', 'new');
					fd.append('new_category_name', byId('mNewCategoryName').value.trim());
				} else {
					fd.append('category_mode', 'existing');
					fd.append('category_id', byId('mCategorySelect').value);
				}

				// Add new main images from modalNewMainImages
				modalNewMainImages.slice(0, 8).forEach((img) => fd.append('images[]', img.file));
				fd.append('pinned_image_key', modalPinnedImageKey || '');

				getVariantRowsData().forEach((v) => {
					const variantUploadId = Number(v.id || 0) > 0 ? Number(v.id) : Number(v.tempId || 0);
					if (!variantUploadId) return;
					(v.imageFiles || []).forEach((file, idx) => {
						fd.append(`variant_${variantUploadId}_image_${idx}`, file);
					});
				});

				const videoFile = byId('mVideoInput')?.files?.[0] || null;
				if (videoFile) fd.append('video', videoFile);

				const res = await fetch('api/add-product-admin.php', { method: 'POST', body: fd });
				const raw = await res.text();
				let data = null;
				try {
					data = raw ? JSON.parse(raw) : null;
				} catch (_e) {
				}
				if (!res.ok || !data.success) {
					throw new Error((data && data.error) ? data.error : (raw || 'Publish failed'));
				}

				const draftId = Number(modalDraft?.draft_id || 0);
				if (draftId > 0) {
					const body = new URLSearchParams();
					body.append('draft_id', String(draftId));
					await fetch('api/delete-product-draft.php', {
						method: 'POST',
						headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
						body: body.toString()
					});
				}

				setStatus('Published successfully. Redirecting...');
				await showLocalSweetAlert('success', 'Published', 'Draft published successfully. Redirecting...');
				await loadDrafts();
				setTimeout(() => {
					closeModal();
					window.location.href = 'admin_my_products.php';
				}, 450);
			} catch (e) {
				setStatus(e.message || 'Unable to publish.', true);
				await showLocalSweetAlert('error', 'Publish Failed', e.message || 'Unable to publish.');
			} finally {
				if (publishBtn) publishBtn.disabled = false;
			}
		}

		function getWindowSize() {
			return window.innerWidth > 780 ? 'desktop' : 'mobile';
		}

		function updatePageSize() {
			draftPageSize = getWindowSize() === 'desktop' ? 12 : 4;
			currentDraftPage = 1;
		}

		function applyDraftSearch() {
			const search = document.getElementById('draftSearchInput');
			draftSearchQuery = search ? search.value.toLowerCase() : '';
			currentDraftPage = 1;
			renderDraftList();
		}

		function applyDraftSort(value) {
			draftSortMode = value;
			currentDraftPage = 1;
			renderDraftList();
		}

		function getFilteredAndSortedDrafts() {
			let results = cachedDrafts;

			if (draftSearchQuery) {
				results = results.filter(d => 
					String(d.product_name || '').toLowerCase().includes(draftSearchQuery)
				);
			}

			if (draftSortMode === 'newest') {
				results.sort((a, b) => {
					const dateA = new Date(String(a.updated_at || '')).getTime();
					const dateB = new Date(String(b.updated_at || '')).getTime();
					return dateB - dateA;
				});
			} else if (draftSortMode === 'oldest') {
				results.sort((a, b) => {
					const dateA = new Date(String(a.updated_at || '')).getTime();
					const dateB = new Date(String(b.updated_at || '')).getTime();
					return dateA - dateB;
				});
			}

			return results;
		}

		function renderPagination() {
			const container = document.getElementById('paginationContainer');
			if (!container) return;

			const totalPages = Math.ceil(filteredDrafts.length / draftPageSize);
			if (totalPages <= 1) {
				container.innerHTML = '';
				return;
			}

			let html = '';
			if (currentDraftPage > 1) {
				html += `<button class="pagination-btn" onclick="setDraftPage(${currentDraftPage - 1})">← Prev</button>`;
			}

			for (let i = Math.max(1, currentDraftPage - 1); i <= Math.min(totalPages, currentDraftPage + 1); i++) {
				html += `<button class="pagination-btn ${i === currentDraftPage ? 'active' : ''}" onclick="setDraftPage(${i})">${i}</button>`;
			}

			if (currentDraftPage < totalPages) {
				html += `<button class="pagination-btn" onclick="setDraftPage(${currentDraftPage + 1})">Next →</button>`;
			}

			html += `<span class="pagination-info"> Page ${currentDraftPage} of ${totalPages}</span>`;
			container.innerHTML = html;
		}

		function setDraftPage(page) {
			currentDraftPage = Math.max(1, Math.min(page, Math.ceil(filteredDrafts.length / draftPageSize)));
			renderDraftList();
		}

		function renderDraftList() {
			const list = document.getElementById('draftList');
			if (!list) return;

			filteredDrafts = getFilteredAndSortedDrafts();
			if (!filteredDrafts.length) {
				list.innerHTML = '<div class="empty">No drafts found.</div>';
				document.getElementById('paginationContainer').innerHTML = '';
				return;
			}

			const startIdx = (currentDraftPage - 1) * draftPageSize;
			const endIdx = startIdx + draftPageSize;
			const pageDrafts = filteredDrafts.slice(startIdx, endIdx);

			list.innerHTML = pageDrafts.map((draft) => {
				const name = draft.product_name || 'Untitled Draft';
				const priceText = draft.price !== null ? `₱${formatPeso(draft.price)}` : 'No price';
				const stockText = draft.product_stock !== null ? `${draft.product_stock} stock` : 'No stock';
				const variantsText = `${Number(draft.variant_count || 0)} variant(s)`;
				const mediaText = `${Number(draft.image_count || 0)} image(s)${Number(draft.has_video || 0) ? ' + video' : ''}`;
				const categoryText = draft.category_label || 'No category';
				const hasCover = Number(draft.image_count || 0) > 0;
				return `
					<div class="draft-card" onclick="openDraftModal(${Number(draft.draft_id)})" role="button" tabindex="0">
						<div class="draft-cover" id="cover-${Number(draft.draft_id)}" data-draft-id="${Number(draft.draft_id)}" data-has-cover="${hasCover ? '1' : '0'}">
							<span class="draft-chip">Draft #${Number(draft.draft_id)}</span>
							<div style="position:absolute;inset:0;display:flex;align-items:center;justify-content:center;font-size:14px;font-weight:700;color:#64748b;">${hasCover ? 'Loading preview...' : 'No image preview'}</div>
						</div>
						<div class="draft-content">
							<div class="draft-name">${escapeHtml(name)}</div>
							<div class="draft-stats">
								<span class="draft-stat">${escapeHtml(categoryText)}</span>
								<span class="draft-stat">${escapeHtml(priceText)}</span>
								<span class="draft-stat">${escapeHtml(stockText)}</span>
								<span class="draft-stat">${escapeHtml(variantsText)}</span>
								<span class="draft-stat">${escapeHtml(mediaText)}</span>
							</div>
							<div class="draft-updated">Updated: ${escapeHtml(formatDate(draft.updated_at))}</div>
							<div class="card-hint">Click card to open draft</div>
						</div>
					</div>
				`;
			}).join('');

			renderPagination();
			hydrateCardPreviews(pageDrafts);
		}

		async function loadDrafts() {
			const list = document.getElementById('draftList');
			if (!list) return;

			try {
				const res = await fetch('api/get-product-drafts.php', { cache: 'no-store' });
				const data = await res.json();
				if (!res.ok || !data.success) {
					throw new Error(data.error || 'Failed to load drafts');
				}

				const drafts = Array.isArray(data.drafts) ? data.drafts : [];
				cachedDrafts = drafts;
				updatePageSize();
				renderDraftList();
			} catch (err) {
				const list = document.getElementById('draftList');
				if (list) list.innerHTML = `<div class="empty">${escapeHtml(err.message || 'Unable to load drafts.')}</div>`;
			}
		}

		async function hydrateCardPreviews(drafts) {
			const targets = drafts.filter((d) => Number(d.image_count || 0) > 0).slice(0, 24);
			for (const draft of targets) {
				try {
					const res = await fetch(`api/get-product-drafts.php?draft_id=${encodeURIComponent(draft.draft_id)}`, { cache: 'no-store' });
					const data = await res.json();
					if (!res.ok || !data.success || !data.draft) continue;
					const first = data?.draft?.media?.images?.[0]?.path || '';
					if (!first) continue;
					const cover = byId(`cover-${Number(draft.draft_id)}`);
					if (!cover) continue;
					cover.innerHTML = `<span class="draft-chip">Draft #${Number(draft.draft_id)}</span><img src="${escapeHtml(first)}" alt="Draft image" />`;
				} catch (_e) {
				}
			}
		}

		async function deleteDraft(draftId, askConfirm = true) {
			if (askConfirm) {
				const confirmed = await localConfirm('Delete Draft', 'Delete this draft permanently?', 'Delete', 'Cancel');
				if (!confirmed) return;
			}

			try {
				const body = new URLSearchParams();
				body.append('draft_id', String(draftId));
				const res = await fetch('api/delete-product-draft.php', {
					method: 'POST',
					headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
					body: body.toString()
				});
				const data = await res.json();
				if (!res.ok || !data.success) {
					throw new Error(data.error || 'Delete failed');
				}
				await loadDrafts();
			} catch (err) {
				await showLocalSweetAlert('error', 'Delete Failed', err.message || 'Unable to delete draft.');
			}
		}

		document.addEventListener('click', (event) => {
			const modal = byId('draftModal');
			if (!modal || !modal.classList.contains('show')) return;
			if (event.target === modal) {
				closeModal();
			}
		});

		byId('mImagesInput')?.addEventListener('change', () => {
			const input = byId('mImagesInput');
			const files = Array.from(input?.files || []);
			input.value = '';

			const existingImages = Array.isArray(modalDraft?.media?.images) ? modalDraft.media.images : [];
			const nonDeletedExisting = existingImages.filter((_, idx) => !modalMainImageStates[String(idx)]);
			const totalImages = nonDeletedExisting.length + modalNewMainImages.length;
			const remainingSlots = 8 - totalImages;

			if (remainingSlots <= 0) {
				showLocalSweetAlert('error', 'Image Limit Reached', 'Main product images can have up to 8 images only.');
				return;
			}

			const filesToAdd = files.slice(0, remainingSlots);
			filesToAdd.forEach((file) => {
				modalNewMainImages.push({
					file,
					previewUrl: URL.createObjectURL(file)
				});
			});

			renderImageStrip();
		});
		byId('mVideoInput')?.addEventListener('change', () => {
			renderModalVideoManager();
		});

		window.addEventListener('resize', () => {
			updatePageSize();
			if (filteredDrafts.length > 0) {
				currentDraftPage = 1;
				renderDraftList();
			}
		});

		loadDrafts();
	</script>
</body>
</html>
