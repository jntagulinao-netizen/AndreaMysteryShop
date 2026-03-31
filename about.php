<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LUXE - About Us</title>
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
        .hero .actions { display:flex; flex-direction:column; gap:10px; align-items:center; }
        @media (min-width:640px) { .hero .actions { flex-direction:row; } }
        .btn { padding:10px 18px; border-radius:8px; cursor:pointer; border:0; font-weight:600; }
        .btn-primary { background:#ffffff; color:#111827; }
        .btn-outline { background:transparent; color:#fff; border:2px solid rgba(255,255,255,0.9); }

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
        .product { cursor:pointer; }
        .product .img-wrap { background:#f3f4f6; border-radius:10px; overflow:hidden; position:relative; padding-top:100%; }
        .product .img-wrap img { position:absolute; inset:0; width:100%; height:100%; object-fit:cover; transition:transform .3s; }
        .product:hover .img-wrap img { transform:scale(1.08); }
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

        /* Hero animations (same as privacy/home) */
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
        /* Story + values + stats helpers */
        .story { padding:48px 16px; background: linear-gradient(90deg,#eef2ff,#eef0ff); }
        .story .story-grid { display:grid; gap:24px; grid-template-columns:1fr; max-width:1100px; margin:0 auto; }
        @media (min-width:768px) { .story .story-grid { grid-template-columns:1fr 1fr; align-items:center; } }
        .story-card { background:#fff; border-radius:18px; padding:28px; box-shadow:0 10px 30px rgba(2,6,23,0.06); border:1px solid rgba(59,130,246,0.06); }
        .rounded-2xl { border-radius:18px; }
        .shadow-lg { box-shadow:0 20px 40px rgba(2,6,23,0.08); }

        .values { padding:48px 16px; background:#f9fafb; }
        .value-grid { display:grid; gap:18px; grid-template-columns:1fr; max-width:1100px; margin:0 auto; }
        @media (min-width:768px) { .value-grid { grid-template-columns:repeat(2,1fr); } }
        @media (min-width:1024px) { .value-grid { grid-template-columns:repeat(4,1fr); } }
        .value-card { background:#fff; border-radius:18px; padding:28px; box-shadow:0 8px 28px rgba(0,0,0,0.06); border:1px solid rgba(0,0,0,0.04); text-align:center; }
        .value-card { transition: transform .2s ease, box-shadow .2s ease, background-color .2s ease; }

        /* Hover style: match .stat-card:hover behavior */
        .value-card:hover {
            background: rgba(255,255,255,0.98);
            transform: translateY(-6px);
            box-shadow: 0 16px 36px rgba(2,6,23,0.08);
            border-color: rgba(59,130,246,0.08);
        }
        .value-card:hover h3 {
            background: linear-gradient(90deg,#60a5fa,#06b6d4);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }

        .stats { padding:48px 16px; background:linear-gradient(90deg,#0b1220,#000); }
        .stats-grid { display:grid; gap:16px; grid-template-columns:repeat(2,1fr); max-width:1100px; margin:0 auto; }
        @media (min-width:768px) { .stats-grid { grid-template-columns:repeat(4,1fr); } }
        .stat-card { background:rgba(255,255,255,0.06); border-radius:16px; padding:20px; text-align:center; border:1px solid rgba(255,255,255,0.08); }
        .stat-number { font-weight:800; color:#fff; font-size:28px; margin-bottom:8px; }
        @media (min-width:640px) { .stat-number { font-size:36px; } }
        @media (min-width:1024px) { .stat-number { font-size:42px; } }
        .stat-sub { color:#d1d5db; font-size:14px; }
        .stat-card:hover { background: rgba(255,255,255,0.12); transform: translateY(-6px); }
        .stat-card:hover .stat-number { background: linear-gradient(90deg,#60a5fa,#06b6d4); -webkit-background-clip: text; background-clip: text; color: transparent; }

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
                <a href="HomePage.php">Home</a>
                <a href="about.php" class="active">About</a>
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
            <a href="homePage.php">
                <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M3 10.5L12 3l9 7.5V21a1 1 0 0 1-1 1h-5v-7H9v7H4a1 1 0 0 1-1-1V10.5z" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path></svg>
                <span>Home</span>
            </a>
            <a href="about.php" class="active">
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

 

    <!-- Main Content -->
    <main class>
        <!-- Hero Section -->
        <section class="hero">
            <div class="bg-img">
               <img src="heroBg.jpg"  alt="Mystery Shop" />
                <div class="overlay"></div>
            </div>

            <div class="hero-inner animate-fade-in">
                <h1 class="animate-up delay-2">About LUXE</h1>
                <p class="animate-up delay-4">We're on a mission to redefine online shopping by curating the finest selection of premium lifestyle products.</p>
            </div>
        </section>

        <!-- Story Section -->
        <section class="story">
            <div class="story-grid">
                <div class="story-card">
                    <div style="display:flex;align-items:center;gap:12px;margin-bottom:12px;">
                        <div style="height:6px;width:64px;border-radius:999px;background:linear-gradient(90deg,#2563eb,#7c3aed)"></div>
                        <h2 style="font-size:22px;margin:0;font-weight:700;color:#0f1724">Our Story</h2>
                    </div>
                    <div style="color:#475569;line-height:1.6">
                        <p>Founded in 2024, LUXE began with a simple vision: to create a premium e-commerce destination where quality meets style. What started as a small boutique has grown into a trusted source for discerning customers worldwide.</p>
                        <p style="margin-top:12px">We believe that shopping should be an experience, not just a transaction. That's why we meticulously curate every item in our collection, ensuring that each product tells a story and adds value to your life.</p>
                        <p style="margin-top:12px">Today, we serve thousands of satisfied customers across the globe, and we're just getting started.</p>
                    </div>
                </div>
                <div>
                    <img src="palipa.jpg" alt="About LUXE" class="rounded-2xl shadow-lg" style="width:100%;height:auto;object-fit:cover;display:block;" />
                </div>
            </div>
        </section>

        <!-- Values Section -->
        <section class="values">
            <div style="max-width:1100px;margin:0 auto;padding:0 16px;">
                <div style="text-align:center;margin-bottom:28px;">
                    <h2 style="font-size:24px;margin:0 0 8px;font-weight:700;color:#0f1724">What Drives Us</h2>
                    <div style="height:6px;width:96px;background:linear-gradient(90deg,#2563eb,#7c3aed);border-radius:999px;margin:10px auto 0"></div>
                </div>
                <div class="value-grid">
                    <div class="value-card">
                        <div style="display:flex;justify-content:center;margin-bottom:12px"><div style="background:linear-gradient(180deg,#eef2ff,#e9d5ff);padding:12px;border-radius:12px;display:inline-block"><svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#2563eb"><circle cx="12" cy="12" r="10" stroke-width="2"></circle><circle cx="12" cy="12" r="6" stroke-width="2"></circle><circle cx="12" cy="12" r="2" stroke-width="2"></circle></svg></div></div>
                        <h3 style="margin:0 0 8px;font-weight:700;color:#0f1724">Our Mission</h3>
                        <p style="color:#475569">To provide exceptional quality products that enhance everyday living and empower individuals to express their unique style.</p>
                    </div>
                    <div class="value-card">
                        <div style="display:flex;justify-content:center;margin-bottom:12px"><div style="background:linear-gradient(180deg,#ecfdf5,#dcfce7);padding:12px;border-radius:12px;display:inline-block"><svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#16a34a"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"></path></svg></div></div>
                        <h3 style="margin:0 0 8px;font-weight:700;color:#0f1724">Our Team</h3>
                        <p style="color:#475569">A passionate group of curators, designers, and customer experience experts dedicated to your satisfaction.</p>
                    </div>
                    <div class="value-card">
                        <div style="display:flex;justify-content:center;margin-bottom:12px"><div style="background:linear-gradient(180deg,#f5f3ff,#f0e9ff);padding:12px;border-radius:12px;display:inline-block"><svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#7c3aed"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4"></path></svg></div></div>
                        <h3 style="margin:0 0 8px;font-weight:700;color:#0f1724">Quality First</h3>
                        <p style="color:#475569">Every product is carefully selected and tested to ensure it meets our high standards of excellence.</p>
                    </div>
                    <div class="value-card">
                        <div style="display:flex;justify-content:center;margin-bottom:12px"><div style="background:linear-gradient(180deg,#fff1f2,#fff0f6);padding:12px;border-radius:12px;display:inline-block"><svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#ef4444"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364"></path></svg></div></div>
                        <h3 style="margin:0 0 8px;font-weight:700;color:#0f1724">Customer Love</h3>
                        <p style="color:#475569">Your trust and satisfaction drive everything we do. We're committed to exceeding your expectations.</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Stats Section -->
        <section class="stats">
            <div class="container">
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-number">50K+</div>
                        <p class="stat-sub">Happy Customers</p>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number">500+</div>
                        <p class="stat-sub">Premium Products</p>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number">25+</div>
                        <p class="stat-sub">Countries Served</p>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number">99%</div>
                        <p class="stat-sub">Satisfaction Rate</p>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <!-- Footer -->
    <footer class="site-footer">
        <div class="container">
            <div class="footer-grid">
                <div>
                    <div style="display:flex;align-items:center;gap:10px;margin-bottom:12px;">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" style="width:22px;height:22px;color:inherit"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path></svg>
                        <span style="font-weight:700;font-size:16px;color:#fff">LUXE</span>
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

    <!-- mobile menu toggle removed; mobile nav now fixed bottom -->

</body>
</html>
