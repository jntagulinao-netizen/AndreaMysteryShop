<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LUXE - Home</title>
     <link rel="stylesheet" href="main.css">
    <style>
        /* Reset & base */
        *, *::before, *::after { box-sizing: border-box; }
        html, body { margin: 0; padding: 0; height: 100%; }
        body { padding-top:72px; font-family: system-ui, -apple-system, 'Segoe UI', Roboto, 'Helvetica Neue', Arial; background: #ffffff; color: #111827; -webkit-font-smoothing:antialiased; }

        /* Utility-ish classes used in markup */
        .container { width: 100%; max-width: 1200px; margin: 0 auto; padding: 0 16px; }

        /* Hero */
        .hero { position:relative; height:70vh; display:flex; align-items:center; justify-content:center; overflow:hidden; }
        @media (min-width: 640px) { .hero { height:80vh; } }
        @media (min-width: 1024px) { .hero { height:90vh; } }
        .hero .bg-img { position:absolute; inset:0; }
        .hero .bg-img img { width:100%; height:100%; object-fit:cover; display:block; }
        .hero .overlay { position:absolute; inset:0; background: rgba(0,0,0,0.42); }
        .hero .hero-inner { position:relative; z-index:10; text-align:center; color:#fff; padding:0 18px; max-width:960px; }
        .hero h1 { margin:0 0 12px; font-size:28px; line-height:1.05; }
        @media (min-width:640px) { .hero h1 { font-size:36px; } }
        @media (min-width:768px) { .hero h1 { font-size:48px; } }
        @media (min-width:1024px) { .hero h1 { font-size:60px; } }
        .hero p { margin:0 0 18px; color:rgba(255,255,255,0.88); font-size:16px; }
        .hero .actions { display:flex; flex-direction:column; gap:12px; align-items:center; justify-content:center; margin-top:18px; }
        @media (min-width:640px) { .hero .actions { flex-direction:row; } }
        .btn { padding:14px 22px; border-radius:12px; cursor:pointer; border:0; font-weight:700; font-size:1rem; min-width:160px; transition: transform .18s ease, box-shadow .18s ease, background-color .18s ease, color .18s ease; will-change: transform; text-decoration: none; display: inline-block; }
        .btn:focus { outline:0; box-shadow: 0 0 0 6px rgba(37,99,235,0.10); }
        .btn-primary { background:#ffffff; color:#111827; min-width:180px; box-shadow: 0 12px 30px rgba(2,6,23,0.06); }
        .btn-primary:hover {
            box-shadow: 0 16px 36px rgba(2,6,23,0.08);
            background: #070707;
            color: #fff;
            animation: shake 0.8s cubic-bezier(.36,.07,.19,.97) infinite;
        }
        .btn-outline { background:transparent; color:#fff; border:2px solid rgba(255,255,255,0.9); min-width:160px; }
        .btn-outline:hover {
            box-shadow: 0 16px 36px rgba(2,6,23,0.08);
            background: #070707;
            color: #fff;
            border-color: #070707;
            animation: shake 0.8s cubic-bezier(.36,.07,.19,.97) infinite;
        }

        /* Categories */
        .categories { padding:48px 16px; background:#f9fafb; }
        .categories .container { max-width:1200px; }
        .categories h2 { text-align:center; margin-bottom:28px; font-size:28px; }
        .cat-grid { display:grid; grid-template-columns:1fr; gap:16px; }
        @media (min-width:640px) { .cat-grid { grid-template-columns:repeat(2,1fr); } }
        @media (min-width:1024px) { .cat-grid { grid-template-columns:repeat(3,1fr); } }
        .cat-card { background:#fff; padding:20px; border-radius:12px; text-align:center; transition:box-shadow .2s, transform .2s; cursor:pointer; }
        .cat-card:hover { box-shadow: 0 12px 30px rgba(2,6,23,0.08); transform:translateY(-4px); }
        .cat-icon { width:40px; height:40px; display:block; margin:0 auto 12px; color:#111827; transition:transform .18s ease; }
        @media (min-width:640px) { .cat-icon { width:48px; height:48px; margin-bottom:16px; } }
        .cat-card:hover .cat-icon { transform:scale(1.07); }

        /* Featured products */
        .featured { padding:48px 16px; }
        .featured .top-row { display:flex; flex-direction:column; gap:12px; align-items:flex-start; }
        @media (min-width:640px) { .featured .top-row { flex-direction:row; align-items:center; justify-content:space-between; } }
        .product-grid { display:grid; grid-template-columns:1fr; gap:16px; }
        @media (min-width:640px) { .product-grid { grid-template-columns:repeat(2,1fr); } }
        @media (min-width:1024px) { .product-grid { grid-template-columns:repeat(4,1fr); } }
        .product-card { background:#fff; border-radius:10px; overflow:hidden; box-shadow:0 8px 20px rgba(2,6,23,0.08); transition:transform .2s ease, box-shadow .2s ease; cursor:pointer; display:flex; flex-direction:column; }
        .product-card:hover { transform:translateY(-4px); box-shadow:0 14px 28px rgba(2,6,23,0.12); }
        .product-image { background:#f3f4f6; overflow:hidden; position:relative; padding-top:100%; }
        .product-image img { position:absolute; inset:0; width:100%; height:100%; object-fit:cover; transition:transform .3s; }
        .product-card:hover .product-image img { transform:scale(1.06); }
        .product-variant-badge { position:absolute; top:8px; right:8px; background:#e22a39; color:#fff; padding:4px 10px; border-radius:20px; font-size:11px; font-weight:700; z-index:2; }
        .product-info { padding:12px; }
        .product-name { font-size:15px; font-weight:700; line-height:1.3; margin-bottom:8px; min-height:38px; display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden; }
        .product-price { font-size:20px; color:#e22a39; font-weight:800; margin-top:8px; }
        .product-reviews-meta,
        .product-orders-meta,
        .product-stock-meta { font-size:12px; color:#6b7280; }
        .product-stock-meta.in { color:#16a34a; }
        .product-stock-meta.out { color:#dc2626; }
        .product-stock-overlay { position:absolute; inset:0; background:rgba(0,0,0,0.45); display:flex; align-items:center; justify-content:center; }
        .product-stock-overlay-text { color:#fff; font-weight:700; }
        .featured-empty { border:1px dashed #d1d5db; border-radius:10px; padding:20px; color:#6b7280; text-align:center; }
        .rating { display:inline-flex; align-items:center; gap:6px; color:#6b7280; font-size:13px; }

        /* CTA */
        .cta { padding:48px 16px; background:#000; color:#fff; text-align:center; }
        .cta .form { display:flex; flex-direction:column; gap:12px; margin-top:12px; max-width:540px; margin-left:auto; margin-right:auto; }
        @media (min-width:640px) { .cta .form { flex-direction:row; } }
        .cta input[type=email] { padding:12px 14px; border-radius:10px; border:0; font-size:14px; }
        .cta button { padding:10px 16px; border-radius:10px; background:#fff; color:#000; font-weight:600; border:0; cursor:pointer; }

        /* Footer */
        .site-footer { background:#0f1724; color:#d1d5db; padding:40px 16px; }
        .site-footer .footer-grid { display:grid; grid-template-columns:1fr; gap:18px; max-width:1200px; margin:0 auto; }
        @media (min-width:640px) { .site-footer .footer-grid { grid-template-columns:repeat(2,1fr); } }
        @media (min-width:1024px) { .site-footer .footer-grid { grid-template-columns:repeat(4,1fr); } }
        .site-footer a { color: #9ca3af; text-decoration:none; }
        .site-footer a:hover { color:#fff; }

        /* Footer icon sizing */
        .site-footer svg { width:18px; height:18px; display:inline-block; }
        @media (min-width:640px) { .site-footer svg { width:20px; height:20px; } }
        @media (min-width:1024px) { .site-footer svg { width:22px; height:22px; } }

        /* small animation */
        @keyframes fadeIn { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
        .animate-fade-in { animation: fadeIn 1s ease-out; }

        /* Hero animations copied from privacy.php for parity */
        .hero .hero-bg-zoom { animation: bgZoom 24s ease-in-out infinite; transform-origin: center; }
        @keyframes bgZoom { 0% { transform: scale(1); } 50% { transform: scale(1.06); } 100% { transform: scale(1); } }

        .hero .overlay { animation: overlayPulse 8s ease-in-out infinite alternate; }
        @keyframes overlayPulse { 0% { background: rgba(0,0,0,0.44); } 100% { background: rgba(0,0,0,0.30); } }

        .hero-inner .animate-up { opacity: 0; transform: translateY(10px); animation: fadeUp .6s cubic-bezier(.2,.9,.3,1) forwards; }
        .hero-inner .animate-up.delay-1 { animation-delay: 0.12s; }
        .hero-inner .animate-up.delay-2 { animation-delay: 0.26s; }
        .hero-inner .animate-up.delay-3 { animation-delay: 0.40s; }
        .hero-inner .animate-up.delay-4 { animation-delay: 0.56s; }
        @keyframes fadeUp { to { opacity: 1; transform: translateY(0); } }
        /* shake animation: combines a lift with a horizontal jitter */
        @keyframes shake {
            0% { transform: translateY(-2px) translateX(0); }
            10% { transform: translateY(-6px) translateX(-4px); }
            30% { transform: translateY(-6px) translateX(4px); }
            50% { transform: translateY(-6px) translateX(-2px); }
            70% { transform: translateY(-6px) translateX(2px); }
            90% { transform: translateY(-6px) translateX(-1px); }
            100% { transform: translateY(-2px) translateX(0); }
        }
    </style>
</head>
<body class="bg-white">

    <!-- Navigation -->
    <nav class="navbar">
    <div class="nav-wrapper">

        <!-- Logo (hardcoded image) -->
        <a href="homePage.php" class="logo">
            <img src="logo.jpg" alt="Andrea Mystery Shop" class="logo-img" />
            <span>Andrea Mystery Shop</span>
        </a>

        <!-- Right Side (links + login) -->
        <div class="right-nav">
            <div class="nav-desktop">
                <a href="HomePage.php" class="active">Home</a>
                <a href="about.php">About</a>
                <a href="contact.php">Contact Us</a>
                <a href="privacy.php">Data Privacy</a>
            </div>
            <div class="nav-actions">
                <a href="LogIn.php"><button>Login</button></a>
            </div>
        </div>

    </div>

    </nav>

    <!-- Mobile bottom navigation (Lazada-style) -->
    <nav class="mobile-bottom-nav fixed">
        <div class="mobile-nav-inner">
            <a href="homePage.php" class="active">
                <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M3 10.5L12 3l9 7.5V21a1 1 0 0 1-1 1h-5v-7H9v7H4a1 1 0 0 1-1-1V10.5z" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path></svg>
                <span>Home</span>
            </a>
            <a href="about.php">
                <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor"><circle cx="12" cy="8" r="3" stroke-width="1.5"></circle><path d="M6 20v-1a4 4 0 014-4h4a4 4 0 014 4v1" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path></svg>
                <span>About</span>
            </a>
            <a href="contact.php">
                <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M21 8V7a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v1" stroke-width="1.5"></path><rect x="3" y="8" width="18" height="11" rx="2" ry="2" stroke-width="1.5"></rect></svg>
                <span>Contact</span>
            </a>
            <a href="privacy.php">
                <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M12 2l7 4v6c0 5-3.58 9-7 10-3.42-1-7-5-7-10V6l7-4z" stroke-width="1.5"></path></svg>
                <span>Privacy</span>
            </a>
        </div>
    </nav>



    <!-- end .navbar -->

    <!-- Main Content -->
    <main>
        <!-- Hero Section -->
        <section class="hero">
            <div class="bg-img">
                <img src="heroBg.jpg"  alt="Mystery Shop" />
                <div class="overlay"></div>
            </div>

            <div class="hero-inner animate-fade-in">
                <h1 class="animate-up delay-2">Elevate Your Style</h1>
                <p class="animate-up delay-3">Discover premium products curated for the modern lifestyle</p>
                <div class="actions">
                    <a href="LogIn.php" class="btn btn-primary animate-up delay-4">Shop Now</a>
                    <a href="LogIn.php" class="btn btn-outline animate-up delay-4">View Collections</a>
                </div>
            </div>
        </section>

        <!-- Categories Section -->
        <section class="categories">
            <div class="container">
                <h2>Shop by Category</h2>
                <div class="cat-grid">
                    <div class="cat-card">
                        <svg class="cat-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                        </svg>
                        <h3 class="text-lg sm:text-xl mb-2 font-semibold">Fashion</h3>
                        <p class="text-sm sm:text-base text-gray-600">250+ Items</p>
                    </div>
                    <div class="cat-card">
                        <svg class="cat-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"></path>
                        </svg>
                        <h3 class="text-lg sm:text-xl mb-2 font-semibold">Accessories</h3>
                        <p class="text-sm sm:text-base text-gray-600">180+ Items</p>
                    </div>
                    <div class="cat-card">
                        <svg class="cat-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"></path>
                        </svg>
                        <h3 class="text-lg sm:text-xl mb-2 font-semibold">Electronics</h3>
                        <p class="text-sm sm:text-base text-gray-600">120+ Items</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Featured Products -->
        <section class="featured">
            <div class="container">
                <div class="top-row">
                    <h2>Featured Products</h2>
                    <a href="LogIn.php" class="btn btn-primary">View All</a>
                </div>
                
                <div class="product-grid" id="featuredProductsGrid"></div>
            </div>
        </section>

    </main>

     <!-- Footer -->
    <footer class="site-footer">
        <div class="container">
            <div class="footer-grid">
                <div>
                    <div style="display:flex;align-items:center;gap:10px;margin-bottom:12px;">
                        <img src="logo.jpg" alt="Andrea Mystery Shop" style="width:32px;height:32px;border-radius:8px;object-fit:cover;display:block;" />
                        <span style="font-weight:700;font-size:16px;color:#fff">Andrea Mystery Shop</span>
                    </div>
                    <p style="color:#9ca3af;font-size:13px;margin:0">Your destination for premium lifestyle products.</p>
                </div>
                <div>
                    <h4 style="color:#fff;font-weight:700;margin-bottom:8px">Quick Links</h4>
                    <ul style="list-style:none;padding:0;margin:0;color:#9ca3af;font-size:13px;line-height:1.9">
                        <li><a href="HomePage.php" style="color:inherit;text-decoration:none;opacity:0.9">Home</a></li>
                        <li><a href="about.php" style="color:inherit;text-decoration:none;opacity:0.9">About</a></li>
                        <li><a href="contact.php" style="color:inherit;text-decoration:none;opacity:0.9">Contact Us</a></li>
                    </ul>
                </div>
                <div>
                    <h4 style="color:#fff;font-weight:700;margin-bottom:8px">Legal</h4>
                    <ul style="list-style:none;padding:0;margin:0;color:#9ca3af;font-size:13px;line-height:1.9">
                        <li><a href="privacy.php" style="color:inherit;text-decoration:none;opacity:0.9">Data Privacy</a></li>
                        <li><a href="#" style="color:inherit;text-decoration:none;opacity:0.9">Terms of Service</a></li>
                        <li><a href="#" style="color:inherit;text-decoration:none;opacity:0.9">Return Policy</a></li>
                    </ul>
                </div>
                <div>
                    <h4 style="color:#fff;font-weight:700;margin-bottom:8px">Follow Us</h4>
                    <div style="display:flex;gap:12px;align-items:center">
                        <a href="#" style="color:inherit;opacity:0.9"><svg fill="currentColor" viewBox="0 0 24 24" style="width:18px;height:18px"><path d="M18 2h-3a5 5 0 00-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 011-1h3z"></path></svg></a>
                        <a href="#" style="color:inherit;opacity:0.9"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24" style="width:18px;height:18px"><rect x="2" y="2" width="20" height="20" rx="5" ry="5" stroke-width="2"></rect><path d="M16 11.37A4 4 0 1112.63 8 4 4 0 0116 11.37z" stroke-width="2"></path></svg></a>
                        <a href="#" style="color:inherit;opacity:0.9"><svg fill="currentColor" viewBox="0 0 24 24" style="width:18px;height:18px"><path d="M23 3a10.9 10.9 0 01-3.14 1.53 4.48 4.48 0 00-7.86 3v1A10.66 10.66 0 013 4s-4 9 5 13a11.64 11.64 0 01-7 2c9 5 20 0 20-11.5a4.5 4.5 0 00-.08-.83A7.72 7.72 0 0023 3z"></path></svg></a>
                    </div>
                </div>
            </div>
            <div style="border-top:1px solid rgba(255,255,255,0.06);margin-top:20px;padding-top:16px;text-align:center;color:#9ca3af;font-size:13px">
               
            </div>
        </div>
    </footer>
    <!-- mobile menu toggling removed -- using bottom fixed nav for mobile -->

    <script src="assets/js/user_dashboard_reusable_ui.js?v=20260403-1"></script>
    <script>
        function formatPeso(value) {
            const amount = Number(value || 0);
            if (!Number.isFinite(amount)) return '0';
            return amount.toLocaleString('en-PH', { minimumFractionDigits: 0, maximumFractionDigits: 2 });
        }

        function openProductModal() {
            window.location.href = 'LogIn.php';
        }

        async function loadFeaturedProducts() {
            const grid = document.getElementById('featuredProductsGrid');
            if (!grid) return;

            try {
                const response = await fetch('api/get-products.php');
                if (!response.ok) {
                    throw new Error('Failed to load featured products.');
                }

                const products = await response.json();
                const groupedFeaturedProducts = new Map();
                (Array.isArray(products) ? products : [])
                    .filter((item) => Number(item.archived || 0) === 0)
                    .filter((item) => Number(item.featured || 0) === 1)
                    .forEach((item) => {
                        const productId = Number(item.id);
                        const parentId = item.parent_product_id ? Number(item.parent_product_id) : null;
                        const mainProductId = parentId || productId;

                        if (!groupedFeaturedProducts.has(mainProductId)) {
                            groupedFeaturedProducts.set(mainProductId, []);
                        }

                        groupedFeaturedProducts.get(mainProductId).push(item);
                    });

                const featuredMainProducts = Array.from(groupedFeaturedProducts.values())
                    .map((groupItems) => {
                        const mainProduct = groupItems.find((item) => !item.parent_product_id) || groupItems[0];
                        const totalStock = groupItems.reduce((sum, item) => sum + Number(item.stock || 0), 0);
                        const totalOrders = groupItems.reduce((sum, item) => sum + Number(item.orderCount || 0), 0);

                        return {
                            ...mainProduct,
                            variantCount: groupItems.length > 1 ? groupItems.length : 0,
                            groupStock: totalStock,
                            groupOrderCount: totalOrders,
                            isGroupOutOfStock: totalStock <= 0
                        };
                    })
                    .filter((item) => Number(item.parent_product_id || 0) === 0 || item.parent_product_id === null)
                    .slice(0, 4);

                if (featuredMainProducts.length === 0) {
                    grid.innerHTML = '<div class="featured-empty">No featured products available right now.</div>';
                    return;
                }

                grid.innerHTML = featuredMainProducts.map((product) => {
                    const productImage = Array.isArray(product.image) && product.image.length
                        ? product.image[0]
                        : 'https://via.placeholder.com/900x600?text=No+Image';
                    const priceDisplay = `₱${formatPeso(product.price)}`;
                    const avgRating = (Number(product.rating) || 0).toFixed(1);

                    return window.DashboardReusableUI.renderProductCard(product, {
                        isOutOfStock: Number(product.isGroupOutOfStock || product.groupStock || 0) <= 0,
                        avgRating,
                        variantCount: Number(product.variantCount || 0),
                        priceDisplay,
                        productImage
                    });
                }).join('');
            } catch (error) {
                grid.innerHTML = '<div class="featured-empty">Unable to load featured products right now.</div>';
            }
        }

        loadFeaturedProducts();
    </script>

</body>
</html>

