let products = [];
        let cart = [];
        let filteredProducts = [];
        let cartSearchQuery = '';
        let selectedItems = new Set(); // Track which items user selected for checkout
        let buyNowProductId = null; // For Buy Now flow (buy only one product)
        let buyNowItems = []; // For Buy Now products (temporary, not added to persistent cart)
        let currentCategory = 'all'; // Track current category filter
        let searchHistoryCache = [];
        let currentProductImages = [];
        let currentProductVideoUrl = '';
        let currentProductMediaOptions = [];
        let lightboxCurrentIndex = 0;
        let lightboxTouchStartX = 0;
        let lightboxTouchStartY = 0;

        async function loadCategories() {
            try {
                console.log('loadCategories() starting...');
                const res = await fetch('api/get-categories.php');
                console.log('API Response Status:', res.status, res.ok);
                if (!res.ok) throw new Error('Failed to load categories');
                const data = await res.json();
                console.log('API Data Returned:', data);
                
                if (data.success && data.categories) {
                    console.log('Processing', data.categories.length, 'categories');
                    const buttonsContainer = document.getElementById('categoryButtons');
                    const moreButton = document.getElementById('moreCategoriesBtn');
                    const moreCategoriesContainer = document.getElementById('moreCategoriesContainer');
                    
                    console.log('buttonsContainer:', buttonsContainer);
                    console.log('moreButton:', moreButton);
                    console.log('moreCategoriesContainer:', moreCategoriesContainer);
                    
                    // Clear existing buttons
                    buttonsContainer.innerHTML = '';
                    moreCategoriesContainer.innerHTML = '';
                    
                    // Add "All" button first
                    const allBtn = DashboardReusableUI.createCategoryButton('All', 'all', () => {
                        document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
                        allBtn.classList.add('active');
                        filterByCategory('all');
                    }, true);
                    buttonsContainer.appendChild(allBtn);
                    console.log('All button added');
                    
                    // Show first 4 categories as buttons
                    data.categories.slice(0, 4).forEach(cat => {
                        console.log('Creating button for category:', cat.category_name);
                        const btn = DashboardReusableUI.createCategoryButton(cat.category_name, cat.category_name, () => {
                            // Remove active class from all buttons
                            document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
                            btn.classList.add('active');
                            filterByCategory(cat.category_name);
                        });
                        buttonsContainer.appendChild(btn);
                        console.log('Button added for:', cat.category_name);
                    });
                    
                    moreButton.style.display = 'inline-block';
                    moreButton.textContent = 'More Categories ▼';
                    moreCategoriesContainer.classList.remove('active');
                    moreCategoriesContainer.innerHTML = '';

                    if (data.categories.length > 4) {
                        console.log('Showing dropdown-style extra categories for', data.categories.length - 4, 'categories');
                        data.categories.slice(4).forEach(cat => {
                            const extraBtn = DashboardReusableUI.createCategoryButton(cat.category_name, cat.category_name, () => {
                                document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
                                extraBtn.classList.add('active');
                                filterByCategory(cat.category_name);
                            });
                            moreCategoriesContainer.appendChild(extraBtn);
                            console.log('Extra button added for:', cat.category_name);
                        });

                        moreButton.onclick = () => {
                            const isOpen = moreCategoriesContainer.classList.contains('active');
                            if (isOpen) {
                                moreCategoriesContainer.classList.remove('active');
                                moreButton.textContent = 'More Categories ▼';
                            } else {
                                moreCategoriesContainer.classList.add('active');
                                moreButton.textContent = 'Hide More Categories ▲';
                            }
                        };
                    } else {
                        moreButton.style.display = 'none';
                        moreCategoriesContainer.style.display = 'none';
                    }
                    console.log('loadCategories() completed successfully');
                } else {
                    console.warn('API returned success:false or no categories array. Data:', data);
                }
            } catch (err) {
                console.error('loadCategories Error:', err);
            }
        }

        function filterByCategory(categoryName) {
            currentCategory = categoryName;
            console.log('=== filterByCategory() called with categoryName:', categoryName);
            console.log('Total products available:', products.length);
            
            // Update button active states
            if (categoryName === 'all') {
                document.querySelectorAll('.filter-btn').forEach(btn => btn.classList.remove('active'));
                document.querySelector('[data-category="all"]').classList.add('active');
                filteredProducts = [...products];
                console.log('Filtering by ALL - showing all products:', filteredProducts.length);
            } else {
                document.querySelectorAll('.filter-btn').forEach(btn => btn.classList.remove('active'));
                const activeBtn = document.querySelector(`[data-category="${categoryName}"]`);
                if (activeBtn) activeBtn.classList.add('active');
                
                console.log('Filtering by category name:', categoryName);
                filteredProducts = products.filter(p => {
                    return p.categoryName === categoryName;
                });
                console.log('Filtered results:', filteredProducts.length, 'products');
            }
            
            console.log('About to render', filteredProducts.length, 'products');
            renderProducts(filteredProducts);
        }

        async function loadProducts() {
            try {
                const res = await fetch('api/get-products.php');
                if (!res.ok) throw new Error('Failed to load products');
                const data = await res.json();
                console.log('API Returned:', data.length, 'products', data);
                products = data.map(p => {
                    const reviewCount = Number(p.reviewCount) || 0;
                    const normalizedRating = reviewCount > 0 ? (Number(p.rating) || 0) : 0;
                    return {
                        id: p.id,
                        parent_product_id: p.parent_product_id || null,
                        name: p.name,
                        price: p.price,
                        originalPrice: p.original_price || null,
                        image: p.image || ['https://via.placeholder.com/900x600?text=No+Image'],
                        video_url: p.video_url || '',
                        rating: normalizedRating,
                        reviewCount: reviewCount,
                        orderCount: Number(p.orderCount) || 0,
                        category: p.category || 'general',
                        categoryName: p.categoryName || '',
                        stock: p.stock || 0,
                        desc: p.desc || p.product_description || '',
                        reviews: p.reviews || []
                    };
                });
                filteredProducts = [...products];
                console.log('Products Array:', products);
                console.log('Filtered Products:', filteredProducts);
                renderProducts();
                console.log('renderProducts() called');
            } catch (err) {
                console.error('loadProducts Error:', err);
                console.log('Displaying fallback sample products');
                // Fallback to sample products for testing
                products = [
                    { id: 1, name: 'Sample Product 1', price: 29.99, originalPrice: 39.99, image: ['https://via.placeholder.com/900x600?text=Product+1'], rating: 5, category: 1, categoryName: 'Electronics', stock: 10, desc: 'Sample product', reviews: [] },
                    { id: 2, name: 'Sample Product 2', price: 49.99, originalPrice: null, image: ['https://via.placeholder.com/900x600?text=Product+2'], rating: 4, category: 1, categoryName: 'Electronics', stock: 0, desc: 'Sample product', reviews: [] },
                    { id: 3, name: 'Sample Product 3', price: 19.99, originalPrice: null, image: ['https://via.placeholder.com/900x600?text=Product+3'], rating: 3, category: 2, categoryName: 'Clothing', stock: 5, desc: 'Sample product', reviews: [] }
                ];
                filteredProducts = [...products];
                renderProducts();
                console.log('Fallback products rendered');
            }
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

        function formatPeso(value) {
            const amount = Number(value || 0);
            if (!Number.isFinite(amount)) return '0';
            return amount.toLocaleString('en-PH', { minimumFractionDigits: 0, maximumFractionDigits: 2 });
        }

        function resolveItemImage(item) {
            const fallback = 'https://via.placeholder.com/160x160?text=No+Image';
            if (!item || typeof item !== 'object') return fallback;

            const candidates = [];
            if (Array.isArray(item.image)) candidates.push(...item.image);
            else if (typeof item.image === 'string') candidates.push(item.image);

            if (Array.isArray(item.images)) candidates.push(...item.images);
            else if (typeof item.images === 'string') candidates.push(item.images);

            if (typeof item.image_url === 'string') candidates.push(item.image_url);
            if (typeof item.thumbnail === 'string') candidates.push(item.thumbnail);

            for (const raw of candidates) {
                if (typeof raw !== 'string') continue;
                let normalized = raw.trim();
                if (!normalized || normalized.toLowerCase() === 'null') continue;

                if (/^(https?:)?\/\//i.test(normalized) || normalized.startsWith('data:') || normalized.startsWith('blob:')) {
                    return normalized;
                }

                normalized = normalized.replace(/\\+/g, '/').replace(/^\.\//, '');
                normalized = normalized.replace(/^[A-Za-z]:\/xampp\/htdocs\/AndreaMysteryShop\//i, '');
                normalized = normalized.replace(/^\/?xampp\/htdocs\/AndreaMysteryShop\//i, '');

                const marker = 'AndreaMysteryShop/';
                const markerIndex = normalized.toLowerCase().indexOf(marker.toLowerCase());
                if (markerIndex >= 0) {
                    normalized = normalized.slice(markerIndex + marker.length);
                }

                normalized = normalized.trim();
                if (normalized) return normalized;
            }

            return fallback;
        }

        function animateAddToCart(sourceEl, imageUrl) {
            const cartBtn = document.getElementById('topCartBtn');
            if (!cartBtn) return;

            const sourceRect = sourceEl ? sourceEl.getBoundingClientRect() : null;
            const cartRect = cartBtn.getBoundingClientRect();

            const startX = sourceRect ? (sourceRect.left + sourceRect.width / 2 - 27) : (window.innerWidth / 2 - 27);
            const startY = sourceRect ? (sourceRect.top + sourceRect.height / 2 - 27) : (window.innerHeight / 2 - 27);
            const endX = cartRect.left + cartRect.width / 2 - 27;
            const endY = cartRect.top + cartRect.height / 2 - 27;

            const flyer = document.createElement('img');
            flyer.className = 'fly-to-cart';
            flyer.src = imageUrl || 'https://via.placeholder.com/120x120?text=+';
            flyer.style.left = `${startX}px`;
            flyer.style.top = `${startY}px`;
            document.body.appendChild(flyer);

            const deltaX = endX - startX;
            const deltaY = endY - startY;
            const anim = flyer.animate([
                { transform: 'translate3d(0, 0, 0) scale(1)', opacity: 0.95 },
                { transform: `translate3d(${deltaX}px, ${deltaY}px, 0) scale(0.18)`, opacity: 0.2 }
            ], {
                duration: 1900,
                easing: 'cubic-bezier(.16,.84,.24,1)'
            });

            anim.onfinish = () => {
                if (flyer.parentNode) flyer.parentNode.removeChild(flyer);
                cartBtn.animate([
                    { transform: 'scale(1)' },
                    { transform: 'scale(1.14)' },
                    { transform: 'scale(1)' }
                ], { duration: 260, easing: 'ease-out' });
            };
        }

        function renderProductGallery(images, productName = 'Product image') {
            const gallery = document.getElementById('productGallery');
            if (!gallery) return;

            currentProductImages = Array.isArray(images) ? images.filter(Boolean) : [];
            if (!currentProductImages.length) {
                gallery.innerHTML = '';
                return;
            }

            currentProductMediaOptions = currentProductImages.map((img, index) => ({
                type: 'image',
                src: img,
                imageIndex: index
            }));

            if (currentProductVideoUrl) {
                currentProductMediaOptions.push({
                    type: 'video',
                    src: currentProductVideoUrl,
                    imageIndex: null
                });
            }

            const firstOption = currentProductMediaOptions[0];
            const firstPreviewHtml = firstOption && firstOption.type === 'video'
                ? `<video class="main-img" id="mainProductVideo" src="${firstOption.src}" controls playsinline style="cursor: auto; object-fit: contain;"></video>`
                : `<img class="main-img" id="mainProductImg" src="${firstOption ? firstOption.src : ''}" alt="${productName}" onclick="openImageLightbox(${firstOption ? firstOption.imageIndex : 0}, event)" />`;

            gallery.innerHTML = `
                <div class="main-gallery">
                    ${firstPreviewHtml}
                </div>
                <div class="gallery-thumbs">
                    ${currentProductMediaOptions.map((media, i) => {
                        if (media.type === 'video') {
                            return `<video class="thumb-img thumb-video ${i===0?'active':''}" onclick="switchImage(${i})" src="${media.src}" muted playsinline preload="metadata" title="Product video thumbnail"></video>`;
                        }
                        return `<img class="thumb-img ${i===0?'active':''}" onclick="switchImage(${i})" src="${media.src}" alt="thumb-${i}"/>`;
                    }).join('')}
                </div>
            `;
        }

        function renderProductVideo(videoUrl) {
            const gallery = document.getElementById('productGallery');
            if (!gallery) return;
            if (!videoUrl) {
                gallery.innerHTML = '';
                return;
            }

            gallery.innerHTML = `
                <div class="main-gallery">
                    <video class="main-img" src="${videoUrl}" controls playsinline style="cursor: auto; object-fit: contain;"></video>
                </div>
            `;
        }

        function refreshMediaTabsForProduct(product) {
            const photosTab = document.getElementById('photosTab');
            const videosTab = document.getElementById('videosTab');
            currentProductVideoUrl = String(product?.video_url || '').trim();

            // Video is now handled as a gallery option in Photos.
            currentMediaTab = 'photos';
            if (videosTab) {
                videosTab.style.display = 'none';
            }

            if (photosTab) {
                photosTab.classList.toggle('active', currentMediaTab === 'photos');
            }
            if (videosTab) {
                videosTab.classList.toggle('active', currentMediaTab === 'videos');
            }
        }

        function openImageLightbox(index = 0, event = null) {
            if (event) event.stopPropagation();
            if (!currentProductImages.length) return;

            const safeIndex = Math.max(0, Math.min(index, currentProductImages.length - 1));
            const lightbox = document.getElementById('imageLightbox');
            const image = document.getElementById('lightboxImage');
            if (!lightbox || !image) return;

            lightboxCurrentIndex = safeIndex;
            image.src = currentProductImages[lightboxCurrentIndex];
            lightbox.classList.add('show');
            document.body.style.overflow = 'hidden';
        }

        function showLightboxImage(index) {
            if (!currentProductImages.length) return;
            const image = document.getElementById('lightboxImage');
            if (!image) return;

            const total = currentProductImages.length;
            lightboxCurrentIndex = ((index % total) + total) % total;
            image.src = currentProductImages[lightboxCurrentIndex];
        }

        function navigateLightbox(step) {
            if (!currentProductImages.length) return;
            showLightboxImage(lightboxCurrentIndex + step);
        }

        function initializeImageLightboxGestures() {
            const lightbox = document.getElementById('imageLightbox');
            if (!lightbox || lightbox.dataset.swipeBound === '1') return;

            lightbox.addEventListener('touchstart', (event) => {
                const touch = event.changedTouches?.[0];
                if (!touch) return;
                lightboxTouchStartX = touch.clientX;
                lightboxTouchStartY = touch.clientY;
            }, { passive: true });

            lightbox.addEventListener('touchend', (event) => {
                const touch = event.changedTouches?.[0];
                if (!touch) return;

                const deltaX = touch.clientX - lightboxTouchStartX;
                const deltaY = touch.clientY - lightboxTouchStartY;
                const absX = Math.abs(deltaX);
                const absY = Math.abs(deltaY);

                // Horizontal swipe threshold for switching images.
                if (absX > 45 && absX > absY) {
                    navigateLightbox(deltaX < 0 ? 1 : -1);
                }
            }, { passive: true });

            lightbox.dataset.swipeBound = '1';
        }

        function closeImageLightbox(event = null) {
            if (event && event.target && event.target.id !== 'imageLightbox' && !event.target.classList.contains('image-lightbox-close')) {
                return;
            }

            const lightbox = document.getElementById('imageLightbox');
            if (!lightbox) return;
            lightbox.classList.remove('show');

            const checkoutOpen = document.getElementById('checkoutModal')?.classList.contains('show');
            const productOpen = document.getElementById('productModal')?.classList.contains('show');
            const successOpen = document.getElementById('successModal')?.classList.contains('show');
            const cartOpen = document.getElementById('cartPage')?.classList.contains('show');
            const searchOpen = document.getElementById('searchPage')?.style.display === 'block';
            document.body.style.overflow = (checkoutOpen || productOpen || successOpen || cartOpen || searchOpen) ? 'hidden' : '';
        }

        async function addToCartServer(productId, quantity=1, showAlert=true, sourceEl=null) {
            try {
                const body = new URLSearchParams();
                body.append('product_id', productId);
                body.append('quantity', quantity);

                const res = await fetch('api/add-to-cart.php', { method: 'POST', body });
                if (!res.ok) {
                    const err = await res.json();
                    throw new Error(err.error || 'Failed to add to cart');
                }

                const product = products.find(p => Number(p.id) === Number(productId));
                const imageUrl = Array.isArray(product?.image) ? product.image[0] : (product?.image || '');
                const sourceElement = sourceEl || document.querySelector(`#productModal.show #mainProductImg`) || document.querySelector(`.product-card[data-id="${productId}"] .product-card-img`);

                if (showAlert) {
                    animateAddToCart(sourceElement, imageUrl);
                }

                await loadCart();
                if (showAlert) {
                    await showLocalSweetAlert('success', 'Added to Cart', `${product?.name || 'Product'} was added to your cart.`, 1200);
                }
            } catch (err) {
                await showLocalSweetAlert('error', 'Add to Cart Failed', err.message || 'Unable to add item to cart.', 1600);
            }
        }

        let currentProductId = null;
        let currentProductIndex = null;

        function refreshOverlayState() {
            const checkoutOpen = document.getElementById('checkoutModal')?.classList.contains('show');
            const productOpen = document.getElementById('productModal')?.classList.contains('show');
            const successOpen = document.getElementById('successModal')?.classList.contains('show');
            document.body.classList.toggle('modal-overlay-active', !!(checkoutOpen || productOpen || successOpen));
        }

        function initializeProductModalLifecycle() {
            const modal = document.getElementById('productModal');
            if (!modal || modal.dataset.lifecycleBound === '1') return;

            modal.dataset.lifecycleBound = '1';
            // Reset to a safe hidden state on page load/reload.
            modal.classList.remove('show');
            modal.style.display = 'none';
            modal.style.pointerEvents = 'none';

            const closeBtn = modal.querySelector('.close-product');
            if (closeBtn) {
                closeBtn.addEventListener('click', (event) => {
                    event.preventDefault();
                    event.stopPropagation();
                    closeProductModal();
                });
            }

            modal.addEventListener('click', (event) => {
                if (event.target === modal) {
                    closeProductModal();
                }
            });
        }

        // Variant helper functions (global scope for use in openProductModal)
        function extractBaseProductName(fullName) {
            if (!fullName || typeof fullName !== 'string') return fullName;
            const lastDash = fullName.lastIndexOf(' - ');
            return lastDash > 0 ? fullName.substring(0, lastDash) : fullName;
        }

        function loadVariantsForProduct(product, allProducts) {
            const productId = Number(product.id);
            const parentId = product.parent_product_id ? Number(product.parent_product_id) : null;
            
            // If this product is a variant, find its main product and all siblings
            const mainProductId = parentId || productId;
            
            // Get main product and all its variants
            const variants = allProducts.filter(p => {
                const pId = Number(p.id);
                const pParentId = p.parent_product_id ? Number(p.parent_product_id) : null;
                // Include main product and all products where parent_product_id matches main product
                return pId === mainProductId || pParentId === mainProductId;
            });
            
            // Return sorted by price, main product first
            return variants.sort((a, b) => {
                if (!a.parent_product_id) return -1; // main product first
                if (!b.parent_product_id) return 1;
                return Number(a.price) - Number(b.price);
            });
        }

        function renderProducts(productsToRender = filteredProducts) {
            try {
                console.log('renderProducts() called with', productsToRender.length, 'products');
                const grid = document.getElementById('productsGrid');
                if (!grid) {
                    console.error('productsGrid element not found!');
                    return;
                }
                
                // Build one card per product family (main product + its variants)
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

                const groupedCards = Array.from(groupedProductsMap.entries()).map(([mainProductId, groupItems]) => {
                    const mainProduct = groupItems.find(item => !item.parent_product_id) || groupItems[0];
                    const priceValues = groupItems.map(item => Number(item.price || 0));
                    const stockValues = groupItems.map(item => Number(item.stock || 0));
                    const totalStock = stockValues.reduce((sum, value) => sum + value, 0);
                    const inStock = stockValues.some(value => value > 0);

                    return {
                        ...mainProduct,
                        mainProductId,
                        variantCount: groupItems.length > 1 ? groupItems.length : 0,
                        groupMinPrice: priceValues.length ? Math.min(...priceValues) : Number(mainProduct.price || 0),
                        groupMaxPrice: priceValues.length ? Math.max(...priceValues) : Number(mainProduct.price || 0),
                        groupStock: totalStock,
                        isGroupOutOfStock: !inStock,
                        groupOrderCount: groupItems.reduce((sum, item) => sum + Number(item.orderCount || 0), 0)
                    };
                });

                // Sort cards: product groups with any stock first
                const sortedCards = groupedCards.sort((a, b) => {
                    if (a.isGroupOutOfStock && !b.isGroupOutOfStock) return 1;
                    if (!a.isGroupOutOfStock && b.isGroupOutOfStock) return -1;
                    return 0;
                });

                console.log('Grouped product cards:', sortedCards.map(p => ({ name: p.name, stock: p.groupStock, variants: p.variantCount })));
                
                grid.innerHTML = sortedCards.map(p => {
                    const isOutOfStock = p.isGroupOutOfStock;
                    const avgRating = (p.rating || 0).toFixed(1);
                    const variantCount = p.variantCount;
                    const priceDisplay = `₱${formatPeso(p.price)}`;
                    const productImage = Array.isArray(p.image) && p.image.length > 0 ? p.image[0] : '';
                    return DashboardReusableUI.renderProductCard(p, {
                        isOutOfStock,
                        avgRating,
                        variantCount,
                        priceDisplay,
                        productImage
                    });
                }).join('');
                console.log('renderProducts() completed - rendered', sortedCards.length, 'product cards');
            } catch (err) {
                console.error('renderProducts() error:', err);
            }
        }

        function openProductModal(id) {
            try {
                const modal = document.getElementById('productModal');
                if (modal) {
                    modal.style.display = 'flex';
                    modal.style.pointerEvents = 'auto';
                }
                id = parseInt(id);
                currentProductId = id;
                currentProductIndex = products.findIndex(p => parseInt(p.id) === id);
                let product = products[currentProductIndex];
                
                // If product not found in products array, search in cart by product_id
                if (!product) {
                    let cartItem = cart.find(p => parseInt(p.product_id) === id);
                    if (!cartItem) {
                        console.error('Product not found with id:', id, 'in products or cart');
                        return;
                    }
                    // Try to find the full product data in products array
                    product = products.find(p => parseInt(p.id) === id);
                    // If still not found, use cart item but merge with any available product data
                    if (!product) {
                        product = cartItem;
                        currentProductIndex = products.length;
                        products.push(product);
                    }
                }
                
                console.log('Opening modal for product:', product);
                
                if (typeof initializeReviewMediaPicker === 'function') {
                    initializeReviewMediaPicker();
                }
                if (typeof clearSelectedReviewMediaFiles === 'function') {
                    clearSelectedReviewMediaFiles();
                }
                
                // Normalize image to array
                const images = Array.isArray(product.image) ? product.image : (product.image ? [product.image] : []);
                if (images.length === 0) {
                    console.error('No images found for product:', product);
                    return;
                }
                
                // Get stock from product data
                let stock = 0;
                if (product.stock !== undefined && product.stock !== null) {
                    stock = Number(product.stock);
                } else {
                    const fullProduct = products.find(p => parseInt(p.id) === id);
                    if (fullProduct && fullProduct.stock !== undefined) {
                        stock = Number(fullProduct.stock);
                    }
                }
                console.log('Product stock value:', stock);
                const isOutOfStock = stock === 0;
                
                const gallery = document.getElementById('productGallery');
                if (!gallery) {
                    console.error('productGallery element not found');
                    return;
                }
                refreshMediaTabsForProduct(product);
                renderProductGallery(images, product.name || 'Product image');
                currentMediaTab = 'photos';
                switchMediaTab('photos');
                
                document.getElementById('productTitle').textContent = product.name;
                document.getElementById('currentPrice').textContent = `₱${formatPeso(product.price)}`;
                const orig = document.getElementById('originalPrice');
                const disc = document.getElementById('discount');
                if (product.originalPrice) { orig.textContent = `₱${formatPeso(product.originalPrice)}`; orig.style.display = 'inline'; const savings = product.originalPrice - product.price; disc.textContent = `Save ₱${formatPeso(savings)}`; disc.style.display = 'inline'; }
                else { orig.style.display = 'none'; disc.style.display = 'none'; }
                const initialReviewCount = Number(product.reviewCount || 0);
                const initialRating = initialReviewCount > 0 ? Number(product.rating || 0).toFixed(1) : '0.0';
                document.getElementById('productRating').innerHTML = `★ ${initialRating} (${initialReviewCount} reviews)`;
                
                // Add stock information
                const stockInfo = document.querySelector('.product-price-section');
                if (stockInfo) {
                    const stockDiv = stockInfo.querySelector('.product-stock-info') || document.createElement('div');
                    stockDiv.className = 'product-stock-info';
                    stockDiv.innerHTML = `<span class="product-stock-info-value ${isOutOfStock ? 'out' : 'in'}">Stock: ${stock}</span>`;
                    if (!stockInfo.querySelector('.product-stock-info')) {
                        stockInfo.appendChild(stockDiv);
                    } else {
                        stockInfo.querySelector('.product-stock-info').innerHTML = stockDiv.innerHTML;
                    }
                }
                
                // Disable/enable action buttons based on stock
                const buyNowBtn = document.querySelector('.buy-now');
                const addCartBtn = document.querySelector('.add-cart');
                if (buyNowBtn) {
                    buyNowBtn.disabled = isOutOfStock;
                    buyNowBtn.style.opacity = isOutOfStock ? '0.5' : '1';
                    buyNowBtn.style.cursor = isOutOfStock ? 'not-allowed' : 'pointer';
                    buyNowBtn.textContent = isOutOfStock ? 'Out of Stock' : 'Buy Now';
                }
                if (addCartBtn) {
                    addCartBtn.disabled = isOutOfStock;
                    addCartBtn.style.opacity = isOutOfStock ? '0.5' : '1';
                    addCartBtn.style.cursor = isOutOfStock ? 'not-allowed' : 'pointer';
                    addCartBtn.textContent = isOutOfStock ? 'Out of Stock' : 'Add to Cart';
                }
                
                document.getElementById('productDesc').innerHTML = product.desc;
                
                // Load and render variants if they exist
                const variants = loadVariantsForProduct(product, products);
                console.log('Loaded variants for product:', { productId: product.id, variantCount: variants.length, variants });
                
                const variantsSection = document.getElementById('variantsSection');
                const variantsList = document.getElementById('variantsList');
                
                if (variants && variants.length > 1) {
                    variantsSection.style.display = 'block';
                    variantsList.innerHTML = variants.map((variant, index) => {
                        const isCurrentVariant = Number(variant.id) === Number(product.id);
                        const displayName = String(variant.name || '').trim() || 'Default';
                        const variantStock = Number(variant.stock || 0);
                        const isOutOfStock = variantStock === 0;

                        return DashboardReusableUI.renderVariantOption(variant, {
                            isCurrentVariant,
                            displayName,
                            variantStock,
                            isOutOfStock
                        }, formatPeso);
                    }).join('');
                } else {
                    variantsSection.style.display = 'none';
                }
                
                // Fetch reviews from database
                fetchProductReviews(id);
                
                renderRecommendations(product); renderStars(0); setActiveTab('mediaTab');
                const detail = document.querySelector('.product-detail'); if (detail) detail.scrollTop = 0;
                document.getElementById('productModal').classList.add('show');
                updateDetailObserver();
                document.body.style.overflow = 'hidden';
                refreshOverlayState();
            } catch (err) {
                console.error('Error opening product modal:', err);
            }
        }
        
        async function fetchProductReviews(productId) {
            try {
                console.log('Fetching reviews for product:', productId);
                const response = await fetch(`api/get-reviews.php?product_id=${productId}`);
                const data = await response.json();
                console.log('API Response:', data);
                
                if (data.success && data.reviews && data.reviews.length > 0) {
                    // Transform database reviews to match expected format
                    const reviews = data.reviews.map(r => {
                        console.log('Processing review:', r);
                        return {
                            review_id: r.review_id,
                            user: r.user_name || 'Anonymous User',
                            rating: parseInt(r.rating) || 0,
                            date: r.created_at || '2 days ago',
                            text: r.review_text || '[No text provided]',
                            has_media: r.has_media || false,
                            media_type: r.media_type || null,
                            media_files: Array.isArray(r.media_files) ? r.media_files : []
                        };
                    });
                    console.log('Transformed reviews:', reviews);
                    
                    // Calculate average rating
                    const avgRating = (reviews.reduce((sum, r) => sum + r.rating, 0) / reviews.length).toFixed(1);
                    const reviewCount = reviews.length;
                    
                    // Update the top product rating display
                    document.getElementById('productRating').innerHTML = `★ ${avgRating} (${reviewCount} reviews)`;

                    // Keep in-memory product rating in sync with DB average
                    const productIndex = products.findIndex(p => p.id === productId);
                    if (productIndex !== -1) {
                        products[productIndex].rating = parseFloat(avgRating);
                        products[productIndex].reviewCount = reviewCount;
                    }

                    renderProducts();
                    renderReviews(reviews);
                } else {
                    console.log('No reviews found or API error');
                    document.getElementById('productRating').innerHTML = `★ 0 (0 reviews)`;

                    const productIndex = products.findIndex(p => p.id === productId);
                    if (productIndex !== -1) {
                        products[productIndex].rating = 0;
                        products[productIndex].reviewCount = 0;
                    }

                    renderProducts();
                    renderReviews([]);
                }
            } catch (error) {
                console.error('Error fetching reviews:', error);
                document.getElementById('productRating').innerHTML = `★ 0 (0 reviews)`;
                renderReviews([]);
            }
        }

        function switchToVariant(variantId, clickedElement = null) {
            console.log('Switching to variant:', variantId);
            variantId = parseInt(variantId);
            
            // Find the variant product in the products array
            const variantProduct = products.find(p => parseInt(p.id) === variantId);
            if (!variantProduct) {
                console.error('Variant product not found:', variantId);
                return;
            }
            
            console.log('Found variant product:', variantProduct);
            
            // Update current product in modal
            currentProductId = variantId;
            currentProductIndex = products.findIndex(p => parseInt(p.id) === variantId);
            
            // Update product title
            document.getElementById('productTitle').textContent = variantProduct.name;
            
            // Update price
            document.getElementById('currentPrice').textContent = `₱${formatPeso(variantProduct.price)}`;
            
            // Update original price if exists
            const orig = document.getElementById('originalPrice');
            const disc = document.getElementById('discount');
            if (variantProduct.originalPrice) {
                orig.textContent = `₱${formatPeso(variantProduct.originalPrice)}`;
                orig.style.display = 'inline';
                const savings = variantProduct.originalPrice - variantProduct.price;
                disc.textContent = `Save ₱${formatPeso(savings)}`;
                disc.style.display = 'inline';
            } else {
                orig.style.display = 'none';
                disc.style.display = 'none';
            }
            
            // Update images
            const images = Array.isArray(variantProduct.image) ? variantProduct.image : (variantProduct.image ? [variantProduct.image] : []);
            if (images.length > 0) {
                refreshMediaTabsForProduct(variantProduct);
                renderProductGallery(images, variantProduct.name || 'Product image');
            }
            switchMediaTab(currentMediaTab);
            
            // Update stock
            const stock = Number(variantProduct.stock || 0);
            const isOutOfStock = stock === 0;
            const stockDiv = document.querySelector('.product-stock-info');
            if (stockDiv) {
                stockDiv.innerHTML = `<span class="product-stock-info-value ${isOutOfStock ? 'out' : 'in'}">Stock: ${stock}</span>`;
            }
            
            // Update action buttons
            const buyNowBtn = document.querySelector('.buy-now');
            const addCartBtn = document.querySelector('.add-cart');
            if (buyNowBtn) {
                buyNowBtn.disabled = isOutOfStock;
                buyNowBtn.style.opacity = isOutOfStock ? '0.5' : '1';
                buyNowBtn.style.cursor = isOutOfStock ? 'not-allowed' : 'pointer';
                buyNowBtn.textContent = isOutOfStock ? 'Out of Stock' : 'Buy Now';
            }
            if (addCartBtn) {
                addCartBtn.disabled = isOutOfStock;
                addCartBtn.style.opacity = isOutOfStock ? '0.5' : '1';
                addCartBtn.style.cursor = isOutOfStock ? 'not-allowed' : 'pointer';
                addCartBtn.textContent = isOutOfStock ? 'Out of Stock' : 'Add to Cart';
            }
            
            // Update variant selector - mark current variant as selected
            const variantOptions = document.querySelectorAll('.variant-option');
            variantOptions.forEach(opt => {
                opt.classList.remove('selected');
            });

            // Find and mark the clicked variant as selected
            if (clickedElement) {
                clickedElement.classList.add('selected');
            }
            
            console.log('Variant switched successfully to:', variantId);
        }

        function closeProductModal() { 
            const modal = document.getElementById('productModal');
            if (modal) {
                modal.classList.remove('show');
                // Ensure hidden modal layer never intercepts clicks.
                modal.style.pointerEvents = 'none';
                modal.style.display = 'none';
            }
            // Only reset overflow if search page is not open
            const searchPage = document.getElementById('searchPage');
            if (!searchPage || searchPage.style.display === 'none') {
                document.body.style.overflow = '';
            }
            refreshOverlayState();
            closeImageLightbox();
            clearSelectedReviewMediaFiles();
            currentProductIndex = null; 
            currentProductId = null;
        }

        function navigateProduct(delta) {
            if (currentProductIndex === null) return;
            const nextIndex = (currentProductIndex + delta + products.length) % products.length;
            openProductModal(products[nextIndex].id);
        }

        let currentMediaTab = 'photos';

        function switchImage(index) {
            const thumbs = document.querySelectorAll('.thumb-img');
            const gallery = document.getElementById('productGallery');
            const option = currentProductMediaOptions[index];
            if (!gallery || !option) return;

            thumbs.forEach((t, i) => t.classList.toggle('active', i === index));

            const mainGallery = gallery.querySelector('.main-gallery');
            if (!mainGallery) return;

            if (option.type === 'video') {
                mainGallery.innerHTML = `<video class="main-img" id="mainProductVideo" src="${option.src}" controls playsinline style="cursor: auto; object-fit: contain;"></video>`;
                return;
            }

            mainGallery.innerHTML = `<img class="main-img" id="mainProductImg" src="${option.src}" alt="Product image" onclick="openImageLightbox(${option.imageIndex}, event)" />`;
        }

        function switchMediaTab(tab) {
            const product = products.find(p => p.id === currentProductId);
            currentMediaTab = 'photos';
            const photosTab = document.getElementById('photosTab');
            const videosTab = document.getElementById('videosTab');
            if (photosTab) photosTab.classList.toggle('active', currentMediaTab === 'photos');
            if (videosTab) videosTab.classList.toggle('active', false);

            if (!product) return;

            if (!Array.isArray(product.image) || !product.image.length) return;
            renderProductGallery(product.image, product.name || 'Product image');
        }

        const detailObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const targetId = entry.target.id === 'mediaSection' ? 'mediaTab' : entry.target.id;
                    document.querySelectorAll('.detail-tab').forEach(tab => tab.classList.toggle('active', tab.dataset.target === targetId));
                }
            });
        }, { root: document.querySelector('.product-detail'), threshold: 0.4 });

        function setActiveTab(tabId) {
            document.querySelectorAll('.detail-tab').forEach(tab => tab.classList.toggle('active', tab.dataset.target === tabId));
        }

        function switchDetailTab(targetId) {
            setActiveTab(targetId);
            if (targetId === 'mediaTab') {
                const detail = document.querySelector('.product-detail');
                if (detail) {
                    detail.scrollTop = 0;
                    return;
                }
            }
            const target = document.getElementById(targetId);
            if (target) {
                target.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        }

        function updateDetailObserver() {
            detailObserver.disconnect();
            const sections = Array.from(document.querySelectorAll('.detail-section'));
            sections.push(document.getElementById('mediaSection'));
            sections.forEach(sec => { if (sec) detailObserver.observe(sec); });
        }

        let reviewMediaMap = {};

        function renderReviewMediaNode(media, variantClass = '') {
            return DashboardReusableUI.renderReviewMediaNode(media, variantClass);
        }

        function switchReviewMedia(reviewId, mediaIndex) {
            const list = reviewMediaMap[reviewId] || [];
            const media = list[mediaIndex];
            if (!media) return;

            const main = document.getElementById(`reviewMediaMain-${reviewId}`);
            if (main) {
                main.innerHTML = renderReviewMediaNode(
                    media,
                    'review-media-main'
                );
            }

            const thumbs = document.querySelectorAll(`.review-media-thumb[data-review-id="${reviewId}"]`);
            thumbs.forEach((thumb, idx) => {
                thumb.classList.toggle('active', idx === mediaIndex);
            });
        }

        function renderReviews(reviews) { 
            const container = document.getElementById('reviewList'); 
            const avg = reviews.length ? (reviews.reduce((sum, r) => sum + r.rating, 0)/reviews.length).toFixed(1):0; 
            document.getElementById('avgRating').innerHTML = `★ ${avg}`; 
            document.getElementById('reviewsCount').textContent = `${reviews.length} reviews`; 
            reviewMediaMap = {};
            
            container.innerHTML = reviews.map(r => {
                let mediaHtml = '';
                const files = Array.isArray(r.media_files) ? r.media_files : [];
                if (files.length > 0) {
                    const mediaList = files.map(file => ({
                        url: file.url || (file.media_id ? `api/get-review-media.php?media_id=${file.media_id}` : `api/get-review-media.php?review_id=${r.review_id}`),
                        media_type: file.media_type || r.media_type || ''
                    }));

                    reviewMediaMap[r.review_id] = mediaList;

                    if (mediaList.length > 1) {
                        const thumbsHtml = mediaList.map((media, idx) => {
                            if ((media.media_type || '').includes('video/')) {
                                return `<button type="button" class="review-media-thumb review-media-video-thumb ${idx === 0 ? 'active' : ''}" data-review-id="${r.review_id}" onclick="switchReviewMedia(${r.review_id}, ${idx})">VIDEO</button>`;
                            }
                            return `<img src="${media.url}" class="review-media-thumb ${idx === 0 ? 'active' : ''}" data-review-id="${r.review_id}" onclick="switchReviewMedia(${r.review_id}, ${idx})" alt="review-thumb-${idx}">`;
                        }).join('');

                        mediaHtml = `
                            <div class="review-media-group">
                                <div id="reviewMediaMain-${r.review_id}">${renderReviewMediaNode(mediaList[0], 'review-media-main')}</div>
                                <div class="review-media-thumbs">${thumbsHtml}</div>
                            </div>
                        `;
                    } else {
                        mediaHtml = renderReviewMediaNode(
                            mediaList[0],
                            'review-media-single'
                        );
                    }
                } else if (r.has_media) {
                    const mediaUrl = `api/get-review-media.php?review_id=${r.review_id}`;
                    mediaHtml = renderReviewMediaNode(
                        { url: mediaUrl, media_type: r.media_type || '' },
                        'review-media-single'
                    );
                }
                
                return `<div class="review-item"><div class="review-avatar">${r.user[0].toUpperCase()}</div><div class="review-content"><div class="review-header"><span class="reviewer-name">${r.user}</span><span class="review-stars">★${'★'.repeat(r.rating-1)}${'☆'.repeat(5-r.rating)}</span><span class="review-date">${r.date}</span></div><p class="review-text">${r.text}</p>${mediaHtml}</div></div>`;
            }).join('') || '<p class="empty-reviews-text">No reviews yet. Be the first!</p>';
        }

        function getRecommendationTokens(text) {
            const stopWords = new Set(['and', 'the', 'for', 'with', 'from', 'this', 'that', 'your', 'you', 'are', 'was', 'were', 'have', 'has', 'into', 'about']);
            return String(text || '')
                .toLowerCase()
                .replace(/[^a-z0-9\s]/g, ' ')
                .split(/\s+/)
                .filter(token => token.length >= 3 && !stopWords.has(token));
        }

        function renderRecommendations(product) {
            const list = document.getElementById('recommendationList');
            if (!list || !product) return;

            const baseName = String(product.name || '').toLowerCase();
            const baseCategory = String(product.categoryName || product.category || '').toLowerCase();
            const baseTokens = new Set(getRecommendationTokens(`${product.name || ''} ${product.desc || ''} ${product.categoryName || ''}`));

            const recommended = products
                .filter((candidate) => Number(candidate.id) !== Number(product.id))
                .map((candidate) => {
                    const candidateName = String(candidate.name || '').toLowerCase();
                    const candidateCategory = String(candidate.categoryName || candidate.category || '').toLowerCase();
                    const sameCategory = baseCategory && candidateCategory === baseCategory;
                    const similarName = !!baseName && !!candidateName && (candidateName.includes(baseName) || baseName.includes(candidateName));

                    const candidateTokens = new Set(getRecommendationTokens(`${candidate.name || ''} ${candidate.desc || ''} ${candidate.categoryName || ''}`));
                    let sharedTokenCount = 0;
                    baseTokens.forEach((token) => {
                        if (candidateTokens.has(token)) sharedTokenCount += 1;
                    });
                    const hasCommonTerms = sharedTokenCount > 0;

                    if (!(sameCategory || similarName || hasCommonTerms)) {
                        return null;
                    }

                    const score = (sameCategory ? 60 : 0) + (similarName ? 30 : 0) + Math.min(sharedTokenCount, 8) * 5 + (Number(candidate.orderCount || 0) * 0.01);
                    return { ...candidate, _score: score };
                })
                .filter(Boolean)
                .sort((a, b) => Number(b._score || 0) - Number(a._score || 0));

            if (!recommended.length) {
                list.innerHTML = '<p class="empty-reviews-text">No recommendations available.</p>';
                return;
            }

            list.innerHTML = recommended.map((rp) => {
                return DashboardReusableUI.renderRecommendationItem(rp, formatPeso);
            }).join('');
        }

        function openProductModalFromRecommendation(productId) {
            openProductModal(productId);
            requestAnimationFrame(() => {
                const detail = document.querySelector('.product-detail');
                if (detail) detail.scrollTop = 0;
                requestAnimationFrame(() => {
                    if (detail) detail.scrollTop = 0;
                });
            });
        }

        function renderStars(selected = 0) { const container = document.getElementById('starsInput'); container.innerHTML = [1,2,3,4,5].map(i => `<span class="star ${i<=selected ? 'selected' : ''}" onclick="setRating(${i})">★</span>`).join(''); }

        function setRating(stars) { currentRating = stars; renderStars(stars); }

        let currentRating = 0;
        let selectedReviewMediaFiles = [];

        function getMediaFileKey(file) {
            return `${file.name}::${file.size}::${file.lastModified}`;
        }

        function updateReviewMediaSummary() {
            const summary = document.getElementById('reviewMediaSummary');
            if (!summary) return;
            if (!selectedReviewMediaFiles.length) {
                summary.textContent = 'No files selected';
                return;
            }
            const totalBytes = selectedReviewMediaFiles.reduce((sum, file) => sum + (file.size || 0), 0);
            const totalMb = (totalBytes / (1024 * 1024)).toFixed(2);
            summary.textContent = `${selectedReviewMediaFiles.length} file(s) selected • ${totalMb}MB total`;
        }

        function appendReviewMediaFiles(newFiles) {
            if (!newFiles || !newFiles.length) return;
            const existing = new Set(selectedReviewMediaFiles.map(getMediaFileKey));
            newFiles.forEach(file => {
                const key = getMediaFileKey(file);
                if (!existing.has(key)) {
                    selectedReviewMediaFiles.push(file);
                    existing.add(key);
                }
            });
            updateReviewMediaSummary();
        }

        function clearSelectedReviewMediaFiles() {
            selectedReviewMediaFiles = [];
            const input = document.getElementById('reviewImage');
            if (input) input.value = '';
            updateReviewMediaSummary();
        }

        function initializeReviewMediaPicker() {
            const input = document.getElementById('reviewImage');
            if (!input || input.dataset.bound === '1') return;
            input.addEventListener('change', (e) => {
                appendReviewMediaFiles(Array.from(e.target.files || []));
                // Reset native input so user can pick additional files repeatedly.
                e.target.value = '';
            });
            input.dataset.bound = '1';
            updateReviewMediaSummary();
        }

        function toggleReviewForm() { const form = document.getElementById('reviewForm'); form.style.display = form.style.display === 'none' ? 'block' : 'none'; }

        async function submitReview() {
            const text = document.getElementById('reviewText').value.trim();
            const rating = currentRating;
            const productId = currentProductId;

            if (!rating || !text) {
                alert('Please select rating and write a review.');
                return;
            }

            const files = selectedReviewMediaFiles.slice();
            const totalBytes = files.reduce((sum, file) => sum + (file.size || 0), 0);
            if (totalBytes > 25 * 1024 * 1024) {
                alert('Total upload size exceeds 25MB.');
                return;
            }

            await addReview(productId, rating, text, files);
        }

        async function addReview(productId, rating, text, files = []) {
            try {
                const formData = new FormData();
                formData.append('product_id', productId);
                formData.append('rating', rating);
                formData.append('review_text', text);
                files.forEach(file => formData.append('review_media[]', file));

                const response = await fetch('api/add-review.php', {
                    method: 'POST',
                    body: formData
                });
                const data = await response.json();

                if (!data.success) {
                    alert('Error: ' + (data.message || 'Failed to submit review'));
                    return;
                }

                // Refresh stored product rating and review list
                await fetchProductReviews(productId);

                // Update product array rating and count for listing
                const product = products.find(p => p.id === productId);
                if (product) {
                    product.rating = parseFloat(data.average_rating || product.rating || 0);
                    product.reviewCount = product.reviewCount ? product.reviewCount + 1 : 1;
                }

                renderProducts();
                document.getElementById('reviewText').value = '';
                clearSelectedReviewMediaFiles();
                currentRating = 0;
                renderStars();
                toggleReviewForm();

                alert('Review posted! ✅');
            } catch (err) {
                console.error('addReview error', err);
                alert('Error submitting review');
            }
        }

        async function addToCartFromDetail(id, triggerEl = null) {
            const product = products.find(p => p.id === id);
            const stock = product?.stock || 0;
            if (stock === 0) {
                await showLocalSweetAlert('warning', 'Out of Stock', 'This product is out of stock.', 1400);
                return;
            }
            await addToCartServer(id, 1, true, triggerEl);
            showNotification('Added to cart!');
        }

        function buyNow() {
            const product = products.find(p => p.id === currentProductId);
            const stock = product?.stock || 0;
            if (stock === 0) {
                alert('This product is out of stock');
                return;
            }
            console.log('buyNow() called for product:', currentProductId);
            buyNowProductId = currentProductId;
            // Add to temporary buyNowItems but NOT to persistent cart
            buyNowItems = [{
                id: product.id,
                name: product.name,
                price: parseFloat(product.price),
                image: Array.isArray(product.image) ? product.image : [product.image],
                quantity: 1
            }];
            // Pre-select only this product for checkout
            selectedItems.clear();
            selectedItems.add(currentProductId);
            openCheckout();
            closeProductModal();
        }

        async function addToCart(id, triggerEl = null) {
            await addToCartServer(id, 1, true, triggerEl);
        }

        async function loadMessageUnreadCount() {
            const badge = document.getElementById('messageBadge');
            if (!badge) return;
            try {
                const res = await fetch('api/messages-get-conversations.php');
                if (!res.ok) throw new Error('Failed to fetch conversations');
                const data = await res.json();
                if (!data.success || !Array.isArray(data.conversations)) {
                    badge.style.display = 'none';
                    return;
                }
                const unreadTotal = data.conversations.reduce((sum, c) => sum + Number(c.unread_count || 0), 0);
                if (unreadTotal > 0) {
                    badge.textContent = unreadTotal > 99 ? '99+' : String(unreadTotal);
                    badge.style.display = 'inline-flex';
                } else {
                    badge.style.display = 'none';
                }
            } catch (err) {
                badge.style.display = 'none';
            }
        }

        let recipients = [];
        let selectedRecipientId = null;

        async function openCheckout() {
            console.log('openCheckout() called, selectedItems before:', Array.from(selectedItems));
            
            // Auto-select all cart items if none are selected
            if (selectedItems.size === 0 && cart.length > 0) {
                console.log('No items selected, auto-selecting all cart items');
                cart.forEach(item => selectedItems.add(item.id));
            }
            
            document.getElementById('checkoutModal').classList.add('show');
            document.body.style.overflow = 'hidden';
            refreshOverlayState();
            
            // Load recipients
            await loadRecipients();
            
            // Populate checkout summary with ONLY SELECTED items
            populateCheckoutSummary();
            
            // Update button state
            updateCheckoutButtonState();
            
            console.log('Checkout modal opened with selected items:', Array.from(selectedItems));
            
            // Setup payment method listener
            document.querySelectorAll('input[name="paymentMethod"]').forEach(radio => {
                radio.addEventListener('change', (e) => {
                    const gcashDetails = document.getElementById('gcashDetails');
                    gcashDetails.style.display = e.target.value === 'gcash' ? 'block' : 'none';
                });
            });
        }

        function closeCheckout() {
            document.getElementById('checkoutModal').classList.remove('show');
            document.body.style.overflow = '';
            refreshOverlayState();
            // Clear Buy Now items and selection when closing checkout
            buyNowItems = [];
            buyNowProductId = null;
            selectedItems.clear();
        }

        function populateCheckoutSummary() {
            const container = document.getElementById('checkoutCartItems');
            
            // If in buy now mode, use ONLY buy now items. Otherwise use cart items
            const itemsSource = buyNowItems.length > 0 ? buyNowItems : cart;
            
            // Show ONLY SELECTED items in checkout summary
            const itemsToShow = itemsSource.filter(item => selectedItems.has(item.id));
            
            // Calculate totals
            const updateTotals = () => {
                const subtotal = itemsToShow.reduce((sum, item) => sum + item.price * item.quantity, 0);
                const shipping = 0;
                const total = subtotal + shipping;
                
                document.getElementById('checkoutSubtotal').textContent = `₱${formatPeso(subtotal)}`;
                document.getElementById('checkoutShipping').textContent = shipping === 0 ? 'FREE' : `₱${formatPeso(shipping)}`;
                document.getElementById('checkoutTotal').textContent = `₱${formatPeso(total)}`;
            };
            
            // Render items with quantity controls
            container.innerHTML = itemsToShow.map(item => `
                <div class="order-item">
                    <img src="${resolveItemImage(item)}" alt="${item.name}" class="order-item-image">
                    <div class="order-item-info">
                        <div class="order-item-name">${item.name}</div>
                        <div class="order-item-price">₱${formatPeso(item.price)}</div>
                        <div class="checkout-qty-row">
                            <button type="button" class="qty-adj-btn minus" onclick="adjustCheckoutQty(${item.id}, -1)">−</button>
                            <span class="checkout-qty" id="qty-${item.id}">${item.quantity}</span>
                            <button type="button" class="qty-adj-btn plus" onclick="adjustCheckoutQty(${item.id}, 1)">+</button>
                        </div>
                        <div class="order-item-line-total">₱${formatPeso(item.price * item.quantity)}</div>
                    </div>
                </div>
            `).join('');
            
            updateTotals();
        }
        
        function adjustCheckoutQty(itemId, change) {
            console.log('=== adjustCheckoutQty() called for item:', itemId, 'change:', change);
            
            // If in buy now mode, use ONLY buy now items. Otherwise use cart items
            const itemsSource = buyNowItems.length > 0 ? buyNowItems : cart;
            
            // Find item in the appropriate source
            let item = itemsSource.find(i => i.id === itemId);
            
            if (!item) {
                console.error('Item not found:', itemId);
                return;
            }
            
            // Adjust quantity
            const newQty = item.quantity + change;
            if (newQty < 1) {
                alert('Quantity must be at least 1');
                return;
            }
            if (newQty > 100) {
                alert('Maximum quantity is 100');
                return;
            }
            
            item.quantity = newQty;
            console.log('Item quantity updated:', itemId, 'new qty:', newQty);
            
            // Update display
            document.getElementById(`qty-${itemId}`).textContent = newQty;
            
            // Recalculate totals
            const itemsToShow = itemsSource.filter(item => selectedItems.has(item.id));
            const subtotal = itemsToShow.reduce((sum, item) => sum + item.price * item.quantity, 0);
            const shipping = 0;
            const total = subtotal + shipping;
            
            // Update the subtotal section to show new price for this item
            const orderItems = document.querySelectorAll('.order-item');
            orderItems.forEach(el => {
                const itemElement = el.querySelector('.order-item-line-total');
                if (itemElement) {
                    const qtyEl = el.querySelector('.checkout-qty');
                    const priceText = el.querySelector('.order-item-price').textContent.replace(/[^0-9.]/g, '');
                    const price = parseFloat(priceText);
                    const qty = parseInt(qtyEl.textContent);
                    itemElement.textContent = `₱${formatPeso(price * qty)}`;
                }
            });
            
            document.getElementById('checkoutSubtotal').textContent = `₱${formatPeso(subtotal)}`;
            document.getElementById('checkoutShipping').textContent = shipping === 0 ? 'FREE' : `₱${formatPeso(shipping)}`;
            document.getElementById('checkoutTotal').textContent = `₱${formatPeso(total)}`;
        }

        async function handleCheckoutClick() {
            console.log('=== handleCheckoutClick() called ===');
            // Create a fake event object and call handleCheckout
            const fakeEvent = { preventDefault: () => {} };
            await handleCheckout(fakeEvent);
        }
        
        async function handleCheckout(e) {
            e.preventDefault();
            console.log('handleCheckout() called');
            console.log('selectedItems:', Array.from(selectedItems));
            console.log('Cart contents:', cart);
            
            // CHECK: Only proceed if items are selected
            if (selectedItems.size === 0) {
                showToast('Please select items to checkout first!', 'error');
                console.warn('Checkout attempted with no items selected');
                return;
            }
            
            // Verify selected items exist in the appropriate source
            const itemsSource = buyNowItems.length > 0 ? buyNowItems : cart;
            const itemsInCheckout = itemsSource.filter(item => selectedItems.has(item.id));
            if (itemsInCheckout.length === 0) {
                showToast('Selected items are not found!', 'error');
                console.error('Selected items not found:', selectedItems, itemsSource);
                return;
            }
            
            const paymentMethodRadio = document.querySelector('input[name="paymentMethod"]:checked');
            if (!paymentMethodRadio) {
                showToast('Please select a payment method', 'error');
                return;
            }
            const paymentMethod = paymentMethodRadio.value;
            console.log('Payment Method:', paymentMethod);
            let recipientId = selectedRecipientId;
            console.log('Recipient ID:', recipientId);
            
            // If no existing recipient selected, create new one
            if (!recipientId) {
                const recipientName = document.getElementById('recipientName').value;
                const phoneNo = document.getElementById('phoneNo').value;
                const streetName = document.getElementById('streetName').value;
                const unitFloor = document.getElementById('unitFloor').value;
                const district = document.getElementById('district').value;
                const city = document.getElementById('city').value;
                const region = document.getElementById('region').value;
                
                if (!recipientName || !phoneNo || !streetName || !city || !region) {
                    showToast('Please fill in all required recipient fields', 'error');
                    return;
                }
                
                try {
                    const body = new URLSearchParams();
                    body.append('recipient_name', recipientName);
                    body.append('phone_no', phoneNo);
                    body.append('street_name', streetName);
                    body.append('unit_floor', unitFloor);
                    body.append('district', district);
                    body.append('city', city);
                    body.append('region', region);
                    
                    const res = await fetch('api/add-recipient.php', { method: 'POST', body });
                    if (!res.ok) throw new Error('Failed to add recipient');
                    
                    const data = await res.json();
                    recipientId = data.recipient_id;
                } catch (err) {
                    showToast('Error adding recipient: ' + err.message, 'error');
                    return;
                }
            }
            
            // Place order ONLY for selected items
            const placeOrderBtn = document.getElementById('placeOrderBtn');
            placeOrderBtn.disabled = true;
            placeOrderBtn.classList.add('loading');
            placeOrderBtn.innerHTML = '<span class="loading-spinner"></span> Processing...';
            
            try {
                const inBuyNowMode = buyNowItems.length > 0;
                // Build items array with updated quantities from checkout (from appropriate source)
                const itemsSource = inBuyNowMode ? buyNowItems : cart;
                const selectedItemsWithQty = [];

                for (const selectedId of selectedItems) {
                    const item = itemsSource.find(i => i.id === selectedId || i.cart_item_id === selectedId);
                    if (!item) continue;

                    if (inBuyNowMode) {
                        selectedItemsWithQty.push({
                            product_id: item.id,
                            quantity: item.quantity
                        });
                    } else {
                        // cart items should be identified by cart_item_id
                        const cartItemId = item.cart_item_id || item.id;
                        selectedItemsWithQty.push({
                            cart_item_id: cartItemId,
                            quantity: item.quantity
                        });
                    }
                }
                
                const body = new URLSearchParams();
                body.append('recipient_id', recipientId);
                body.append('payment_method', paymentMethod);
                body.append('selected_items', JSON.stringify(selectedItemsWithQty));
                
                console.log('Sending checkout request with:', {
                    recipient_id: recipientId,
                    payment_method: paymentMethod,
                    selected_items: selectedItemsWithQty
                });
                
                const res = await fetch('api/checkout.php', { method: 'POST', body });
                console.log('API Response Status:', res.status);
                
                if (!res.ok) {
                    const errData = await res.json();
                    console.error('API Error:', errData);
                    throw new Error(errData.error || 'Checkout failed');
                }
                
                const data = await res.json();
                console.log('Order created successfully:', data);
                
                // Show success modal
                document.getElementById('successOrderId').textContent = data.order_id;
                document.getElementById('successModal').classList.add('show');
                
                // IMPORTANT: for cart flow, remove only selected items from cart; for buy-now flow, do not touch cart.
                if (!inBuyNowMode) {
                    cart = cart.filter(item => !selectedItems.has(item.id));
                    updateCartBadge();
                    renderCart();
                }

                // Clear buy now items after successful order
                buyNowItems = [];
                buyNowProductId = null;
                selectedItems.clear();
                
                // Close checkout modal after a short delay
                setTimeout(() => {
                    closeCheckout();
                    document.getElementById('checkoutForm').reset();
                    selectedRecipientId = null;
                }, 500);
                
            } catch (err) {
                showToast('Error: ' + err.message, 'error');
            } finally {
                placeOrderBtn.disabled = false;
                placeOrderBtn.classList.remove('loading');
                placeOrderBtn.innerHTML = '<span class="btn-icon">✓</span> Place Order';
            }
        }

        function showToast(message, type = 'info') {
            // Simple toast notification - can be enhanced with a better UI
            if (type === 'error') {
                console.error(message);
                alert(message); // Fallback to alert for now
            } else {
                console.log(message);
            }
        }

        function closeSuccessModal() {
            document.getElementById('successModal').classList.remove('show');
        }

        function filterProducts(query) { filteredProducts = products.filter(p => p.name.toLowerCase().includes(query.toLowerCase()) || p.category.includes(query.toLowerCase())); renderProducts(); }

        function sortProducts(mode) {
            if (mode === 'price-low') filteredProducts.sort((a,b)=>a.price-b.price);
            else if (mode==='price-high') filteredProducts.sort((a,b)=>b.price-a.price);
            else if (mode==='rating') filteredProducts.sort((a,b)=>b.rating-a.rating);
            else filteredProducts=[...products];
            renderProducts();
        }

