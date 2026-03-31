(function (window) {
    let reviewMediaDelegationBound = false;

    function bindReviewMediaDelegatedClicks() {
        if (reviewMediaDelegationBound) {
            return;
        }

        document.addEventListener('click', (event) => {
            const target = event.target.closest('.js-review-media-open');
            if (!target) {
                return;
            }

            const mediaUrl = target.getAttribute('data-review-media-url') || '';
            const mediaType = target.getAttribute('data-review-media-type') || '';
            if (!mediaUrl) {
                return;
            }

            event.preventDefault();
            event.stopPropagation();
            openReviewMediaLightbox(mediaUrl, mediaType);
        });

        reviewMediaDelegationBound = true;
    }

    function ensureReviewMediaLightbox() {
        if (document.getElementById('reviewMediaLightboxOverlay')) {
            return;
        }

        const overlay = document.createElement('div');
        overlay.id = 'reviewMediaLightboxOverlay';
        overlay.style.position = 'fixed';
        overlay.style.inset = '0';
        overlay.style.background = 'rgba(0, 0, 0, 0.88)';
        overlay.style.display = 'none';
        overlay.style.alignItems = 'center';
        overlay.style.justifyContent = 'center';
        overlay.style.padding = '16px';
        overlay.style.zIndex = '50000';

        const closeBtn = document.createElement('button');
        closeBtn.type = 'button';
        closeBtn.textContent = 'x';
        closeBtn.setAttribute('aria-label', 'Close media viewer');
        closeBtn.style.position = 'absolute';
        closeBtn.style.top = '16px';
        closeBtn.style.right = '18px';
        closeBtn.style.width = '40px';
        closeBtn.style.height = '40px';
        closeBtn.style.borderRadius = '999px';
        closeBtn.style.border = '1px solid rgba(255,255,255,0.4)';
        closeBtn.style.background = 'rgba(0,0,0,0.35)';
        closeBtn.style.color = '#fff';
        closeBtn.style.fontSize = '24px';
        closeBtn.style.cursor = 'pointer';

        const mediaHolder = document.createElement('div');
        mediaHolder.id = 'reviewMediaLightboxHolder';
        mediaHolder.style.maxWidth = '96vw';
        mediaHolder.style.maxHeight = '92vh';
        mediaHolder.style.display = 'flex';
        mediaHolder.style.alignItems = 'center';
        mediaHolder.style.justifyContent = 'center';

        overlay.appendChild(closeBtn);
        overlay.appendChild(mediaHolder);
        document.body.appendChild(overlay);

        const close = () => {
            const holder = document.getElementById('reviewMediaLightboxHolder');
            if (holder) {
                holder.innerHTML = '';
            }
            overlay.style.display = 'none';
            document.body.style.overflow = '';
        };

        closeBtn.addEventListener('click', close);
        overlay.addEventListener('click', (event) => {
            if (event.target === overlay) {
                close();
            }
        });

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && overlay.style.display === 'flex') {
                close();
            }
        });

        bindReviewMediaDelegatedClicks();
    }

    function openReviewMediaLightbox(url, mediaType) {
        if (!url) return;
        ensureReviewMediaLightbox();

        const overlay = document.getElementById('reviewMediaLightboxOverlay');
        const holder = document.getElementById('reviewMediaLightboxHolder');
        if (!overlay || !holder) return;

        holder.innerHTML = '';

        const isVideo = String(mediaType || '').includes('video/');
        let node;
        if (isVideo) {
            node = document.createElement('video');
            node.src = url;
            node.controls = true;
            node.autoplay = true;
        } else {
            node = document.createElement('img');
            node.src = url;
            node.alt = 'Review media';
        }

        node.style.maxWidth = '100%';
        node.style.maxHeight = '100%';
        node.style.width = 'auto';
        node.style.height = 'auto';
        node.style.objectFit = 'contain';
        node.style.borderRadius = '8px';

        holder.appendChild(node);
        overlay.style.display = 'flex';
        document.body.style.overflow = 'hidden';
    }

    function openReviewMediaLightboxFromEncoded(encodedUrl, encodedMediaType) {
        const decodedUrl = decodeURIComponent(encodedUrl || '');
        const decodedType = decodeURIComponent(encodedMediaType || '');
        openReviewMediaLightbox(decodedUrl, decodedType);
    }

    function createCategoryButton(label, category, onClick, isActive) {
        const btn = document.createElement('button');
        btn.className = 'filter-btn' + (isActive ? ' active' : '');
        btn.setAttribute('data-category', category);
        btn.textContent = label;
        btn.onclick = onClick;
        return btn;
    }

    function renderProductCard(product, options) {
        const isOutOfStock = !!options.isOutOfStock;
        const avgRating = options.avgRating;
        const variantCount = options.variantCount;
        const priceDisplay = options.priceDisplay;
        const productImage = options.productImage;

        return `
            <div class="product-card ${isOutOfStock ? 'is-out-of-stock' : ''}" data-id="${product.id}" onclick="openProductModal(${product.id})">
                <div class="product-image product-image-relative">
                    <img src="${productImage}" alt="${product.name}" class="main-img"/>
                    ${variantCount > 0 ? `<span class="product-variant-badge">${variantCount} options</span>` : ''}
                    ${isOutOfStock ? '<div class="product-stock-overlay"><span class="product-stock-overlay-text">Out of Stock</span></div>' : ''}
                </div>
                <div class="product-info">
                    <div class="product-name">${product.name}</div>
                    <div class="product-rating">★ ${avgRating} <span class="product-reviews-meta">(${product.reviewCount} reviews)</span></div>
                    <div class="product-price">${priceDisplay}</div>
                    <div class="product-stock-meta ${isOutOfStock ? 'out' : 'in'}">Stock: ${product.groupStock}</div>
                    <div class="product-orders-meta">Orders: ${Number(product.groupOrderCount || 0)}</div>
                </div>
            </div>
        `;
    }

    function renderVariantOption(variant, options, formatPeso) {
        const isCurrentVariant = !!options.isCurrentVariant;
        const isOutOfStock = !!options.isOutOfStock;
        const variantStock = Number(options.variantStock || 0);
        const displayName = options.displayName;

        return `
            <div onclick="switchToVariant(${variant.id}, this)" class="variant-option ${isCurrentVariant ? 'selected' : ''} ${isOutOfStock ? 'is-out-of-stock' : ''}">
                <div class="variant-option-name ${isOutOfStock ? 'variant-name-out' : 'variant-name-in'}">${displayName}</div>
                <div class="variant-option-price">₱${formatPeso(variant.price)}</div>
                <div class="variant-option-stock ${variantStock > 0 ? 'in' : 'out'}">
                    ${variantStock > 0 ? `Stock: ${variantStock}` : 'Out of Stock'}
                </div>
            </div>
        `;
    }

    function renderReviewMediaNode(media, variantClass) {
        if (!media || !media.url) return '';
        const safeClass = variantClass || '';
        const safeUrl = String(media.url).replace(/"/g, '&quot;');
        const safeType = String(media.media_type || '').replace(/"/g, '&quot;');
        if ((media.media_type || '').includes('video/')) {
            return `<video src="${media.url}" class="review-image ${safeClass} js-review-media-open" data-review-media-url="${safeUrl}" data-review-media-type="${safeType}" controls style="cursor: zoom-in;"></video>`;
        }
        return `<img src="${media.url}" alt="Review image" class="review-image ${safeClass} js-review-media-open" data-review-media-url="${safeUrl}" data-review-media-type="${safeType}" style="cursor: zoom-in;">`;
    }

    function renderRecommendationItem(product, formatPeso) {
        const image = Array.isArray(product.image)
            ? (product.image[0] || 'https://via.placeholder.com/160x160?text=No+Image')
            : (product.image || 'https://via.placeholder.com/160x160?text=No+Image');

        return `
            <button type="button" class="review-item recommendation-item" onclick="openProductModalFromRecommendation(${Number(product.id)})">
                <img src="${image}" class="recommendation-thumb" alt="${product.name}" />
                <div class="recommendation-content">
                    <div class="recommendation-name">${product.name}</div>
                    <div class="recommendation-price">₱${formatPeso(product.price)}</div>
                </div>
            </button>
        `;
    }

    window.DashboardReusableUI = {
        createCategoryButton: createCategoryButton,
        renderProductCard: renderProductCard,
        renderVariantOption: renderVariantOption,
        renderReviewMediaNode: renderReviewMediaNode,
        renderRecommendationItem: renderRecommendationItem
    };

    window.openReviewMediaLightbox = openReviewMediaLightbox;
    window.openReviewMediaLightboxFromEncoded = openReviewMediaLightboxFromEncoded;

    bindReviewMediaDelegatedClicks();
})(window);
