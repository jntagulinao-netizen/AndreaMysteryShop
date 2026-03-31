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

		.media-strip {
			display: grid;
			grid-template-columns: repeat(auto-fill, minmax(74px, 1fr));
			gap: 7px;
		}

		.media-tile {
			border: 1px solid #d4dce8;
			background: #fff;
			border-radius: 8px;
			height: 74px;
			overflow: hidden;
			position: relative;
		}

		.media-tile img {
			width: 100%;
			height: 100%;
			object-fit: cover;
		}

		.pin-dot {
			position: absolute;
			left: 5px;
			top: 5px;
			background: #2f5dd7;
			color: #fff;
			font-size: 10px;
			padding: 2px 5px;
			border-radius: 999px;
		}

		.help {
			font-size: 11px;
			color: #64748b;
		}

		.variant-list {
			display: grid;
			gap: 8px;
		}

		.variant-row {
			display: grid;
			grid-template-columns: 1.1fr 0.8fr 0.8fr auto;
			gap: 8px;
			padding: 8px;
			border: 1px solid #e2e8f2;
			border-radius: 10px;
			background: #fff;
		}

		.variant-media {
			grid-column: 1 / -1;
			display: grid;
			grid-template-columns: 74px 1fr;
			gap: 8px;
			align-items: start;
		}

		.variant-thumb {
			width: 74px;
			height: 74px;
			border: 1px solid #d4dce8;
			border-radius: 8px;
			background: #f8fafc;
			overflow: hidden;
			display: flex;
			align-items: center;
			justify-content: center;
			color: #94a3b8;
			font-size: 11px;
			font-weight: 700;
		}

		.variant-thumb img {
			width: 100%;
			height: 100%;
			object-fit: cover;
		}

		.variant-row .btn-remove {
			border: 1px solid #f3c7cd;
			background: #fff6f7;
			color: #c62839;
			border-radius: 8px;
			padding: 0 10px;
			font-size: 12px;
			font-weight: 700;
			cursor: pointer;
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
			.variant-media {
				grid-template-columns: 1fr;
			}
			.variant-thumb {
				width: 100%;
				height: 120px;
			}
			.actions {
				grid-template-columns: 1fr;
			}
			.new-btn {
				padding: 9px 10px;
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

		<div id="draftList" class="list">
			<div class="empty">Loading drafts...</div>
		</div>
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
					<div class="form-grid">
						<div class="form-group full">
							<label class="label">Product Name</label>
							<input id="mProductName" class="input" type="text" />
						</div>
						<div class="form-group full">
							<label class="label">Description</label>
							<textarea id="mDescription" class="textarea"></textarea>
						</div>
						<div class="form-group">
							<label class="label">Price</label>
							<input id="mPrice" class="input" type="number" min="0" step="0.01" />
						</div>
						<div class="form-group">
							<label class="label">Stock</label>
							<input id="mStock" class="input" type="number" min="0" step="1" />
						</div>

						<div class="form-group full">
							<label class="label">Category Mode</label>
							<div class="inline-row">
								<button id="btnExistingCategory" type="button" class="toggle-btn active" onclick="setCategoryMode(false)">Use Existing</button>
								<button id="btnNewCategory" type="button" class="toggle-btn" onclick="setCategoryMode(true)">Use New</button>
							</div>
						</div>

						<div id="existingCategoryBox" class="form-group full">
							<label class="label">Existing Category</label>
							<select id="mCategorySelect" class="select"></select>
						</div>

						<div id="newCategoryBox" class="form-group full hidden">
							<label class="label">New Category Name</label>
							<input id="mNewCategoryName" class="input" type="text" />
						</div>

						<div class="form-group full">
							<label class="label">Main Images</label>
							<div id="mImageStrip" class="media-strip"></div>
							<div class="help">Choose new images to replace existing draft images.</div>
							<input id="mImagesInput" class="input" type="file" accept="image/*" multiple />
						</div>

						<div class="form-group full">
							<label class="label">Video</label>
							<div id="mVideoInfo" class="help">No video</div>
							<input id="mVideoInput" class="input" type="file" accept="video/*" />
						</div>

						<div class="form-group full">
							<div class="inline-row" style="justify-content:space-between;">
								<label class="label" style="margin:0;">Variants</label>
								<button id="mAddVariantBtn" type="button" class="btn" onclick="addVariantRow()">+ Add Variant</button>
							</div>
							<div id="mVariantList" class="variant-list"></div>
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
		let modalDraft = null;
		let modalUseNewCategory = false;
		let modalNextVariantId = 1;
		let modalIsEditing = false;

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
			return document.getElementById(id);
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
			const hasCancel = !!options.showCancel;
			const iconMap = {
				success: '✓',
				error: '!',
				warning: '!'
			};

			icon.className = `swal-icon ${type}`;
			icon.textContent = iconMap[type] || '✓';
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

		function getDraftThumb(draft) {
			const imgPath = draft?.media?.images?.[0]?.path || '';
			if (imgPath) return imgPath;
			return 'data:image/svg+xml;utf8,' + encodeURIComponent('<svg xmlns="http://www.w3.org/2000/svg" width="600" height="360"><rect width="100%" height="100%" fill="#e2e8f0"/><text x="50%" y="50%" font-size="24" text-anchor="middle" dominant-baseline="middle" fill="#64748b">No Image</text></svg>');
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

		function renderImageStrip() {
			const strip = byId('mImageStrip');
			if (!strip || !modalDraft) return;

			const input = byId('mImagesInput');
			const selectedFiles = Array.from(input?.files || []);
			if (selectedFiles.length > 0) {
				strip.innerHTML = selectedFiles.map((_, idx) => `<div class="media-tile" id="newImgTile${idx}"></div>`).join('');
				selectedFiles.forEach((file, idx) => {
					const reader = new FileReader();
					reader.onload = (e) => {
						const tile = byId(`newImgTile${idx}`);
						if (!tile) return;
						tile.innerHTML = `<img src="${e.target.result}" alt="image"/>`;
					};
					reader.readAsDataURL(file);
				});
				return;
			}

			const images = Array.isArray(modalDraft?.media?.images) ? modalDraft.media.images : [];
			if (!images.length) {
				strip.innerHTML = '<div class="help">No images in this draft.</div>';
				return;
			}

			strip.innerHTML = images.map((img, idx) => {
				const pin = img?.is_pinned ? '<span class="pin-dot">Pinned</span>' : '';
				return `<div class="media-tile">${pin}<img src="${escapeHtml(img.path || '')}" alt="draft image" /></div>`;
			}).join('');
		}

		function getVariantRowsData() {
			const rows = Array.from(document.querySelectorAll('#mVariantList .variant-row'));
			return rows.map((row) => {
				const id = Number(row.dataset.variantId || 0);
				return {
					id,
					name: row.querySelector('.variant-name')?.value?.trim() || '',
					price: row.querySelector('.variant-price')?.value?.trim() || '',
					stock: row.querySelector('.variant-stock')?.value?.trim() || '',
					file: row.querySelector('.variant-image')?.files?.[0] || null,
					existingImagePath: row.dataset.existingImage || ''
				};
			}).filter((v) => v.name || v.price !== '' || v.stock !== '');
		}

		function addVariantRow(variant = null) {
			if (!modalIsEditing && variant === null) return;
			const list = byId('mVariantList');
			if (!list) return;
			const id = Number(variant?.id || 0) > 0 ? Number(variant.id) : modalNextVariantId++;
			if (id >= modalNextVariantId) modalNextVariantId = id + 1;

			const row = document.createElement('div');
			row.className = 'variant-row';
			row.dataset.variantId = String(id);
			if (variant?.existingImagePath) {
				row.dataset.existingImage = variant.existingImagePath;
			}

			row.innerHTML = `
				<input class="input variant-name" type="text" placeholder="Variant name" value="${escapeHtml(variant?.name || '')}" />
				<input class="input variant-price" type="number" min="0" step="0.01" placeholder="Price" value="${escapeHtml(variant?.price ?? '')}" />
				<input class="input variant-stock" type="number" min="0" step="1" placeholder="Stock" value="${escapeHtml(variant?.stock ?? '')}" />
				<button type="button" class="btn-remove" onclick="this.closest('.variant-row').remove()">Remove</button>
				<div class="variant-media">
					<div class="variant-thumb">${variant?.existingImagePath ? `<img src="${escapeHtml(variant.existingImagePath)}" alt="Variant image" />` : 'No image'}</div>
					<div style="display:grid;gap:5px;">
						<input class="input variant-image" type="file" accept="image/*" />
						<div class="help">${variant?.existingImagePath ? 'Existing variant image shown.' : 'Attach one variant image.'}</div>
					</div>
				</div>
			`;

			const fileInput = row.querySelector('.variant-image');
			const thumb = row.querySelector('.variant-thumb');
			if (fileInput) {
				fileInput.addEventListener('change', () => {
					if (fileInput.files && fileInput.files[0]) {
						row.dataset.existingImage = '';
						const help = row.querySelector('.help');
						if (help) help.textContent = 'New variant image selected.';
						const reader = new FileReader();
						reader.onload = (e) => {
							if (thumb) {
								thumb.innerHTML = `<img src="${e.target.result}" alt="Variant image" />`;
							}
						};
						reader.readAsDataURL(fileInput.files[0]);
					} else {
						if (thumb) {
							const existing = row.dataset.existingImage || '';
							thumb.innerHTML = existing ? `<img src="${escapeHtml(existing)}" alt="Variant image" />` : 'No image';
						}
					}
				});
			}
			list.appendChild(row);
		}

		function setEditorMode(isEditing) {
			modalIsEditing = !!isEditing;
			byId('modalOpenBtn')?.classList.toggle('active', !modalIsEditing);
			byId('modalEditBtn')?.classList.toggle('active', modalIsEditing);

			const controls = document.querySelectorAll('#draftModal .input, #draftModal .textarea, #draftModal .select, #draftModal .toggle-btn, #draftModal .variant-image, #draftModal .btn-remove');
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
				byId('mAddVariantBtn')?.setAttribute('disabled', 'disabled');
				setStatus('Preview mode. Click Edit to modify this draft.');
			} else {
				byId('btnSaveDraft')?.removeAttribute('disabled');
				byId('btnPublish')?.removeAttribute('disabled');
				byId('mAddVariantBtn')?.removeAttribute('disabled');
				setStatus('Editing enabled. Save as Draft or Publish when ready.');
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
			modalNextVariantId = 1;
			byId('draftModalTitle').textContent = `Edit Draft #${Number(draft.draft_id || 0)}`;
			byId('mProductName').value = draft.product_name || '';
			byId('mDescription').value = draft.product_description || '';
			byId('mPrice').value = draft.price || '';
			byId('mStock').value = draft.product_stock || '';
			byId('mNewCategoryName').value = draft.new_category_name || '';

			// Open in edit mode so Save as Draft / Publish works immediately.
			setEditorMode(true);
			setCategoryMode(Number(draft.use_new_category || 0) === 1);
			loadCategories(draft.category_id || '');

			const imageList = Array.isArray(draft?.media?.images) ? draft.media.images : [];
			const thumb = getDraftThumb(draft);
			byId('previewMain').src = thumb;
			byId('previewMeta').innerHTML = `
				<div><strong>Category:</strong> ${escapeHtml(draft.category_label || (draft.use_new_category ? draft.new_category_name : 'Uncategorized'))}</div>
				<div><strong>Price:</strong> ${draft.price !== '' ? `₱${formatPeso(draft.price)}` : 'Not set'}</div>
				<div><strong>Stock:</strong> ${draft.product_stock !== '' ? `${draft.product_stock}` : 'Not set'}</div>
				<div><strong>Images:</strong> ${imageList.length} ${draft?.media?.video ? '+ video' : ''}</div>
				<div><strong>Updated:</strong> ${escapeHtml(formatDate(draft.updated_at))}</div>
			`;

			const variantMap = (draft?.media?.variant_images && typeof draft.media.variant_images === 'object') ? draft.media.variant_images : {};
			const variantList = byId('mVariantList');
			if (variantList) variantList.innerHTML = '';
			(Array.isArray(draft.variants) ? draft.variants : []).forEach((v) => {
				addVariantRow({
					id: Number(v.id || 0),
					name: v.name || '',
					price: v.price ?? '',
					stock: v.stock ?? '',
					existingImagePath: variantMap[String(v.id)] || ''
				});
			});

			const videoPath = draft?.media?.video ? `Existing draft video: ${draft.media.video}` : 'No video in draft';
			byId('mVideoInfo').textContent = videoPath;
			byId('mImagesInput').value = '';
			byId('mVideoInput').value = '';
			renderImageStrip();
		}

		function openModal() {
			byId('draftModal')?.classList.add('show');
		}

		function closeModal() {
			byId('draftModal')?.classList.remove('show');
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
				if (!v.id) return 'Variant identifier is invalid.';
				if (forPublish && !v.name) return 'Each variant must have a name.';
				if (forPublish && (v.price === '' || Number(v.price) < 0)) return 'Each variant price must be non-negative.';
				if (forPublish && (v.stock === '' || Number(v.stock) < 0)) return 'Each variant stock must be non-negative.';
				if (forPublish && !v.file && !v.existingImagePath) return 'Each variant must have an image.';
			}

			const imgInput = byId('mImagesInput');
			const selectedImgs = Array.from(imgInput?.files || []);
			const existingImgs = Array.isArray(modalDraft?.media?.images) ? modalDraft.media.images : [];
			if (forPublish && selectedImgs.length === 0 && existingImgs.length === 0) {
				return 'At least one image is required.';
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

			const imageInput = byId('mImagesInput');
			const selectedImageFiles = Array.from(imageInput?.files || []);
			const existingImgs = Array.isArray(modalDraft?.media?.images) ? modalDraft.media.images : [];
			const hasNewImages = selectedImageFiles.length > 0;
			const imageCount = hasNewImages ? selectedImageFiles.length : existingImgs.length;
			const pinnedIndex = hasNewImages ? 0 : Math.max(0, existingImgs.findIndex((i) => !!i?.is_pinned));
			fd.append('pinned_image_index', String(pinnedIndex));
			fd.append('image_count', String(imageCount));

			const hasExistingVideo = !!modalDraft?.media?.video;
			const videoFile = byId('mVideoInput')?.files?.[0] || null;
			fd.append('has_video', String((videoFile || hasExistingVideo) ? 1 : 0));

			fd.append('use_new_category', modalUseNewCategory ? '1' : '0');
			fd.append('category_id', byId('mCategorySelect').value || '');
			fd.append('new_category_name', byId('mNewCategoryName').value || '');

			const variants = getVariantRowsData().map((v) => ({
				id: v.id,
				name: v.name,
				price: v.price,
				stock: v.stock
			}));
			fd.append('variants', JSON.stringify(variants));

			selectedImageFiles.slice(0, 8).forEach((file) => fd.append('images[]', file));
			if (videoFile) fd.append('video', videoFile);

			getVariantRowsData().forEach((v) => {
				if (v.file && Number(v.id) > 0) {
					fd.append(`variant_image_${Number(v.id)}`, v.file);
				}
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
					name: String(v.name || '').trim(),
					price: Number(v.price),
					stock: Number(v.stock || 0)
				}));
				fd.append('variants', JSON.stringify(variants));

				if (modalUseNewCategory) {
					fd.append('category_mode', 'new');
					fd.append('new_category_name', byId('mNewCategoryName').value.trim());
				} else {
					fd.append('category_mode', 'existing');
					fd.append('category_id', byId('mCategorySelect').value);
				}

				const imageFiles = Array.from(byId('mImagesInput')?.files || []);
				imageFiles.slice(0, 8).forEach((file) => fd.append('images[]', file));
				fd.append('pinned_image_index', '0');

				getVariantRowsData().forEach((v) => {
					if (v.file && Number(v.id) > 0) {
						fd.append(`variant_image_${Number(v.id)}`, v.file);
					}
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
				if (!drafts.length) {
					list.innerHTML = '<div class="empty">No drafts yet. Click + New Draft to start.</div>';
					return;
				}

				list.innerHTML = drafts.map((draft) => {
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

				await hydrateCardPreviews(drafts);
			} catch (err) {
				list.innerHTML = `<div class="empty">${escapeHtml(err.message || 'Unable to load drafts.')}</div>`;
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

		byId('mImagesInput')?.addEventListener('change', renderImageStrip);
		byId('mVideoInput')?.addEventListener('change', () => {
			const file = byId('mVideoInput')?.files?.[0] || null;
			byId('mVideoInfo').textContent = file ? `Selected video: ${file.name}` : (modalDraft?.media?.video ? `Existing draft video: ${modalDraft.media.video}` : 'No video in draft');
		});

		loadDrafts();
	</script>
</body>
</html>
