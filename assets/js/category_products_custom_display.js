let currentSortMode = '';
let currentCategoryPage = null;

function getCategoryPageParam() {
    const value = new URLSearchParams(window.location.search).get('category');
    return (value || '').trim();
}

function updateSingleCategoryBodyState() {
    document.body.classList.toggle('single-category-view', !!currentCategoryPage);
}

function openCategoryPage(encodedCategory) {
    const category = decodeURIComponent(encodedCategory || '').trim();
    if (!category) return;
    const url = new URL(window.location.href);
    url.searchParams.set('category', category);
    window.location.href = url.toString();
}

function backToAllCategories() {
    const url = new URL(window.location.href);
    url.searchParams.delete('category');
    const query = url.searchParams.toString();
    window.location.href = query ? `${url.pathname}?${query}` : url.pathname;
}

function getCategoryProductCardHtml(p) {
    const isOutOfStock = !!p.isGroupOutOfStock;
    const avgRating = Number(p.reviewCount || 0) > 0 ? Number(p.rating || 0).toFixed(1) : '0.0';
    const image = Array.isArray(p.image) ? p.image[0] : (p.image || 'https://via.placeholder.com/900x600?text=No+Image');
    const priceDisplay = `₱${formatPeso(p.price)}`;

    return DashboardReusableUI.renderProductCard(p, {
        isOutOfStock,
        avgRating,
        variantCount: Number(p.variantCount || 0),
        priceDisplay,
        productImage: image
    });
}

function renderProducts(productsToRender = filteredProducts) {
    try {
        const grid = document.getElementById('productsGrid');
        if (!grid) return;

        if (!productsToRender || !productsToRender.length) {
            grid.innerHTML = `
                <div style="text-align:center;padding:60px 20px;color:#999;font-size:16px;">
                    <p>No products available.</p>
                </div>
            `;
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
            const mainProduct = groupItems.find(item => !item.parent_product_id) || groupItems[0];
            const priceValues = groupItems.map(item => Number(item.price || 0));
            const stockValues = groupItems.map(item => Number(item.stock || 0));
            const totalStock = stockValues.reduce((sum, value) => sum + value, 0);
            const inStock = stockValues.some(value => value > 0);

            return {
                ...mainProduct,
                variantCount: groupItems.length > 1 ? groupItems.length : 0,
                groupMinPrice: priceValues.length ? Math.min(...priceValues) : Number(mainProduct.price || 0),
                groupMaxPrice: priceValues.length ? Math.max(...priceValues) : Number(mainProduct.price || 0),
                groupStock: totalStock,
                isGroupOutOfStock: !inStock,
                groupOrderCount: groupItems.reduce((sum, item) => sum + Number(item.orderCount || 0), 0)
            };
        });

        if (currentSortMode === 'price-low') {
            groupedCards.sort((a, b) => Number(a.groupMinPrice) - Number(b.groupMinPrice));
        } else if (currentSortMode === 'price-high') {
            groupedCards.sort((a, b) => Number(b.groupMaxPrice) - Number(a.groupMaxPrice));
        } else if (currentSortMode === 'rating') {
            groupedCards.sort((a, b) => Number(b.rating || 0) - Number(a.rating || 0));
        } else {
            groupedCards.sort((a, b) => {
                if (a.isGroupOutOfStock && !b.isGroupOutOfStock) return 1;
                if (!a.isGroupOutOfStock && b.isGroupOutOfStock) return -1;
                return a.name.localeCompare(b.name);
            });
        }

        const groupedByCategory = groupedCards.reduce((acc, p) => {
            const category = p.categoryName || p.category || 'Uncategorized';
            if (!acc[category]) acc[category] = [];
            acc[category].push(p);
            return acc;
        }, {});

        if (currentCategoryPage) {
            const matchCategory = Object.keys(groupedByCategory).find(name => name.toLowerCase() === currentCategoryPage.toLowerCase());
            if (!matchCategory) {
                grid.innerHTML = `
                    <section class="category-block">
                        <div class="single-category-header" style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;gap:12px;flex-wrap:wrap;padding:12px 0;border-bottom:1px solid #eee;">
                            <h2 style="margin:0;font-size:22px;color:#222;">Category not found</h2>
                            <button class="view-all-btn" onclick="backToAllCategories()">Back to All Categories</button>
                        </div>
                        <p style="color:#666;">No products were found for this category.</p>
                    </section>
                `;
                return;
            }

            const oneCategoryProducts = groupedByCategory[matchCategory].slice().sort((a, b) => {
                if (currentSortMode === 'price-low') return Number(a.price) - Number(b.price);
                if (currentSortMode === 'price-high') return Number(b.price) - Number(a.price);
                if (currentSortMode === 'rating') return Number(b.rating || 0) - Number(a.rating || 0);
                const aStock = Number(a.stock || 0);
                const bStock = Number(b.stock || 0);
                if (aStock === 0 && bStock > 0) return 1;
                if (aStock > 0 && bStock === 0) return -1;
                return a.name.localeCompare(b.name);
            });

            if (oneCategoryProducts.length === 0) {
                grid.innerHTML = `
                    <section class="category-block">
                        <div class="single-category-header" style="display:flex;justify-content:space-between;align-items:center;margin-bottom:18px;gap:12px;flex-wrap:wrap;padding:12px 0;border-bottom:1px solid #eee;">
                            <h2 style="margin:0;font-size:28px;color:#111;">${matchCategory}</h2>
                            <button class="view-all-btn" onclick="backToAllCategories()">Back to All Categories</button>
                        </div>
                        <div style="text-align:center;padding:40px 20px;color:#999;font-size:16px;">
                            <p>No products available in this category.</p>
                        </div>
                    </section>
                `;
            } else {
                grid.innerHTML = `
                    <section class="category-block">
                        <div class="single-category-header" style="display:flex;justify-content:space-between;align-items:center;margin-bottom:18px;gap:12px;flex-wrap:wrap;padding:12px 0;border-bottom:1px solid #eee;">
                            <h2 style="margin:0;font-size:28px;color:#111;">${matchCategory}</h2>
                            <div class="category-header-actions">
                                <span style="font-size:14px;color:#777;">${oneCategoryProducts.length} products</span>
                                <button class="view-all-btn" onclick="backToAllCategories()">Back to All Categories</button>
                            </div>
                        </div>
                        <div class="products-grid">${oneCategoryProducts.map(getCategoryProductCardHtml).join('')}</div>
                    </section>
                `;
            }
            return;
        }

        const categorySections = Object.keys(groupedByCategory).sort().map((category) => {
            const sorted = groupedByCategory[category].slice().sort((a, b) => {
                if (currentSortMode === 'price-low') return Number(a.price) - Number(b.price);
                if (currentSortMode === 'price-high') return Number(b.price) - Number(a.price);
                if (currentSortMode === 'rating') return Number(b.rating || 0) - Number(a.rating || 0);
                const aStock = Number(a.stock || 0);
                const bStock = Number(b.stock || 0);
                if (aStock === 0 && bStock > 0) return 1;
                if (aStock > 0 && bStock === 0) return -1;
                return a.name.localeCompare(b.name);
            });

            if (sorted.length === 0) {
                return `
                    <section class="category-block">
                        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px;">
                            <h2 style="margin:0;font-size:22px;color:#222;">${category}</h2>
                            <div class="category-header-actions">
                                <span style="font-size:14px;color:#999;">No products</span>
                            </div>
                        </div>
                        <div style="padding:20px;text-align:center;color:#999;font-size:15px;">
                            <p>No products available in this category.</p>
                        </div>
                    </section>
                `;
            }

            return `
                <section class="category-block">
                    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px;">
                        <h2 style="margin:0;font-size:22px;color:#222;">${category}</h2>
                        <div class="category-header-actions">
                            <span style="font-size:14px;color:#777;">${sorted.length} items</span>
                            <button class="view-all-btn" onclick="openCategoryPage('${encodeURIComponent(category)}')">View All</button>
                        </div>
                    </div>
                    <div class="products-grid">${sorted.map(getCategoryProductCardHtml).join('')}</div>
                </section>
            `;
        }).join('');

        grid.innerHTML = categorySections;
    } catch (err) {
        console.error('renderProducts() error:', err);
    }
}

async function loadProducts() {
    try {
        const res = await fetch('api/get-products.php', { cache: 'no-store' });
        if (!res.ok) throw new Error('Failed to load products');
        const data = await res.json();
        products = data.map((p) => {
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
                reviews: p.reviews || [],
                isFavorite: !!p.is_favorite
            };
        });

        if (currentCategoryPage) {
            const targetCategory = currentCategoryPage.toLowerCase();
            filteredProducts = products.filter((p) => String(p.categoryName || p.category || '').toLowerCase() === targetCategory);
            currentCategory = currentCategoryPage;
        } else {
            filteredProducts = [...products];
        }

        renderProducts();
    } catch (err) {
        console.error('loadProducts Error:', err);
    }
}

function sortProducts(mode) {
    currentSortMode = mode || '';
    renderProducts();
}

document.addEventListener('DOMContentLoaded', () => {
    currentCategoryPage = getCategoryPageParam();
    updateSingleCategoryBodyState();
});
