<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LUXE - Data Privacy Policy</title>
    <link rel="stylesheet" href="main.css">
    <style>
        /* Reset & base */
        *, *::before, *::after { box-sizing: border-box; }
        html, body { margin: 0; padding: 0; height: 100%; }
        body { padding-top:72px; font-family: system-ui, -apple-system, 'Segoe UI', Roboto, 'Helvetica Neue', Arial; background: #ffffff; color: #111827; -webkit-font-smoothing:antialiased; }

        .container { width: 100%; max-width: 1200px; margin: 0 auto; padding: 0 16px; }

        /* Hero */
        .hero { position:relative; height:70vh; display:flex; align-items:center; justify-content:center; overflow:hidden; }
        @media (min-width: 640px) { .hero { height:80vh; } }
        @media (min-width: 1024px) { .hero { height:90vh; } }
        .hero .bg-img { position:absolute; inset:0; }
        .hero .bg-img img { width:100%; height:100%; object-fit:cover; display:block; }
        .hero .overlay { position:absolute; inset:0; background: rgba(0,0,0,0.42); }
        .hero .hero-inner { position:relative; z-index:10; text-align:center; color:#fff; padding:0 18px; max-width:960px; }
        .hero h1 { margin:0 0 12px; font-size:30px; line-height:1.02; }
        @media (min-width:640px) { .hero h1 { font-size:36px; } }
        @media (min-width:768px) { .hero h1 { font-size:60px; } }
        @media (min-width:1024px) { .hero h1 { font-size:72px; } }
        .hero p { margin:0 0 18px; color:rgba(255,255,255,0.88); font-size:16px; }

        /* Hero animations: subtle background zoom + staggered fade/slide for content */
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

        /* Cards used in highlights */
        .card { background:#fff; border-radius:18px; padding:24px; text-align:center; box-shadow:0 20px 40px rgba(2,6,23,0.08); border:1px solid rgba(0,0,0,0.04); transition: all 0.3s ease; }
        .card .icon-wrap { width:64px; height:64px; border-radius:999px; display:flex; align-items:center; justify-content:center; margin:0 auto 18px; }
        .card:hover { transform:translateY(-6px); box-shadow:0 30px 50px rgba(2,6,23,0.12); }
        .card h3 { margin:0 0 12px; font-size:1.125rem; }
        .card p { margin:0; color:#4b5563; font-size:0.95rem; line-height:1.6; }

        /* Policy content cards */
        .policy-card { background:#fff; border-radius:18px; padding:16px; box-shadow:0 12px 30px rgba(2,6,23,0.06); border:1px solid rgba(0,0,0,0.04); }
        .policy-card .icon { width:40px; height:40px; display:flex; align-items:center; justify-content:center; border-radius:8px; }
        .policy-card h2 { margin:0 0 8px; font-size:1.125rem; }
        .policy-card p, .policy-card li { color:#4b5563; font-size:0.95rem; }

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

        /* Minimal utility mappings used by the restored markup (emulates needed Tailwind utilities) */
        .max-w-6xl { max-width: 72rem; }
        .max-w-4xl { max-width: 56rem; }
        .mx-auto { margin-left: auto; margin-right: auto; }
        .px-4 { padding-left: 1rem; padding-right: 1rem; }
        .py-12 { padding-top: 3rem; padding-bottom: 3rem; }
        .text-center { text-align: center; }
        .font-bold { font-weight: 700; }

        /* Typography */
        .text-sm { font-size: 0.875rem; }
        .text-base { font-size: 1rem; }
        .text-lg { font-size: 1.125rem; }
        .text-xl { font-size: 1.25rem; }
        .text-gray-900 { color: #111827; }
        .text-gray-600 { color: #4b5563; }
        .leading-relaxed { line-height: 1.6; }

        /* Spacing helpers */
        .mb-3 { margin-bottom: 0.75rem; }
        .mb-6 { margin-bottom: 1.5rem; }
        .mb-12 { margin-bottom: 3rem; }
        .mb-16 { margin-bottom: 4rem; }
        .p-6 { padding: 1.5rem; }
        .p-8 { padding: 2rem; }
        .p-10 { padding: 2.5rem; }

        /* Layout helpers */
        .grid { display: grid; }
        .grid-cols-1 { grid-template-columns: repeat(1, minmax(0, 1fr)); }
        .gap-6 { gap: 1.5rem; }
        .gap-8 { gap: 2rem; }

        /* Cards / visual helpers */
        .bg-white { background: #fff; }
        .rounded-2xl { border-radius: 1rem; }
        .rounded-full { border-radius: 9999px; }
        .shadow-lg { box-shadow: 0 20px 40px rgba(2,6,23,0.08); }
        .shadow-2xl { box-shadow: 0 30px 50px rgba(2,6,23,0.12); }
        .border { border-width: 1px; }
        .border-gray-100 { border-color: rgba(0,0,0,0.04); }
        .border-gray-700 { border-color: rgba(15,23,36,0.7); }
        .hover\:border-black\/10:hover { border-color: rgba(0,0,0,0.10); }
        .transition-all { transition: all 0.3s ease; }
        .duration-300 { transition-duration: 0.3s; }
        .transform { transform: translateZ(0); }
        .hover\:-translate-y-1:hover { transform: translateY(-4px); }

        /* Size helpers for icons */
        .w-16 { width: 4rem; }
        .h-16 { height: 4rem; }
        .w-8 { width: 2rem; }
        .h-8 { height: 2rem; }
        .w-10 { width: 2.5rem; }
        .h-10 { height: 2.5rem; }

        /* Group hover scale */
        .group:hover .group-hover\:scale-110 { transform: scale(1.10); transition: transform 0.3s ease; }

        /* Responsive overrides (sm = 640px, md = 768px) */
        @media (min-width: 640px) {
            .sm\:py-16 { padding-top: 4rem; padding-bottom: 4rem; }
            .sm\:p-10 { padding: 2.5rem; }
            .sm\:mb-12 { margin-bottom: 3rem; }
            .sm\:mb-16 { margin-bottom: 4rem; }
            .sm\:gap-8 { gap: 2rem; }
            .sm\:grid-cols-2 { grid-template-columns: repeat(2, minmax(0, 1fr)); }
            .sm\:w-20 { width: 5rem; }
            .sm\:h-20 { height: 5rem; }
            .sm\:h-10 { height: 2.5rem; }
            .sm\:w-10 { width: 2.5rem; }
            .sm\:text-base { font-size: 1rem; }
            .sm\:text-xl { font-size: 1.25rem; }
            .sm\:text-2xl { font-size: 1.5rem; }
        }

        @media (min-width: 768px) {
            .md\:grid-cols-3 { grid-template-columns: repeat(3, minmax(0, 1fr)); }
            .md\:text-3xl { font-size: 1.875rem; }
        }

        /* Policy section visual polish: larger icon containers, bigger icons, more breathing room */
        .policy-section .max-w-4xl > .bg-white.rounded-2xl { padding: 1.25rem; border-radius: 1.125rem; }
        .policy-section .flex { gap: 1rem; }
        .policy-section .flex-shrink-0 > div { width: 56px; height: 56px; border-radius: 12px; display:flex; align-items:center; justify-content:center; }
        .policy-section svg { width: 28px; height: 28px; }
        .policy-section h2 { font-size: 1.25rem; }
        .policy-section .text-sm { font-size: 0.98rem; }

        @media (min-width: 640px) {
            .policy-section .max-w-4xl > .bg-white.rounded-2xl { padding: 1.75rem; }
            .policy-section .flex-shrink-0 > div { width: 72px; height: 72px; }
            .policy-section svg { width: 32px; height: 32px; }
            .policy-section h2 { font-size: 1.5rem; }
        }

        @media (min-width: 1024px) {
            .policy-section .max-w-4xl > .bg-white.rounded-2xl { padding: 2rem; }
            .policy-section .flex-shrink-0 > div { width: 80px; height: 80px; }
            .policy-section svg { width: 36px; height: 36px; }
            .policy-section h2 { font-size: 1.625rem; }
        }

        /* Highlights polish: increase gap, icon container and svg sizes, and card padding */
        .highlights-section .grid { gap: 2.25rem; }
        .highlights-section .group { padding: 2.25rem; }
        .highlights-section .bg-gradient-to-br { width: 5rem; height: 5rem; }
        .highlights-section svg { width: 28px; height: 28px; }
        .highlights-section h3 { font-size: 1.125rem; }

        @media (min-width: 640px) {
            .highlights-section .grid { gap: 2.5rem; }
            .highlights-section .group { padding: 2.5rem; }
            .highlights-section .bg-gradient-to-br { width: 5.5rem; height: 5.5rem; }
            .highlights-section svg { width: 32px; height: 32px; }
            .highlights-section h3 { font-size: 1.25rem; }
        }

        @media (min-width: 1024px) {
            .highlights-section .grid { gap: 3rem; }
            .highlights-section .group { padding: 2.75rem; }
            .highlights-section .bg-gradient-to-br { width: 6.25rem; height: 6.25rem; }
            .highlights-section svg { width: 36px; height: 36px; }
            .highlights-section h3 { font-size: 1.35rem; }
        }

        /* Make Privacy Policy Content visually match the Highlights cards */
        .policy-section .bg-white.rounded-2xl {
            padding: 2.25rem;
            text-align: center;
            border-radius: 1rem;
            box-shadow: 0 20px 40px rgba(2,6,23,0.08);
            border: 1px solid rgba(0,0,0,0.04);
            transition: all 0.28s ease;
        }

        .policy-section .bg-white.rounded-2xl .flex { display:flex; flex-direction:column; align-items:center; text-align:center; gap:1rem; }
        .policy-section .flex-shrink-0 > div {
            width: 5rem; height: 5rem; border-radius: 9999px; display:flex; align-items:center; justify-content:center; margin:0 0 1rem;
        }
        .policy-section svg { width: 28px; height: 28px; }
        .policy-section h2 { margin: 0 0 0.75rem; font-size: 1.125rem; }
        .policy-section .text-sm { font-size: 0.95rem; }

        @media (min-width: 640px) {
            .policy-section .bg-white.rounded-2xl { padding: 2.5rem; }
            .policy-section .flex-shrink-0 > div { width: 5.75rem; height: 5.75rem; }
            .policy-section svg { width: 32px; height: 32px; }
            .policy-section h2 { font-size: 1.25rem; }
        }

        @media (min-width: 1024px) {
            .policy-section .bg-white.rounded-2xl { padding: 2.75rem; }
            .policy-section .flex-shrink-0 > div { width: 6.5rem; height: 6.5rem; }
            .policy-section svg { width: 36px; height: 36px; }
            .policy-section h2 { font-size: 1.375rem; }
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
                <a href="HomePage.php">Home</a>
                <a href="about.php">About</a>
                <a href="contact.php">Contact Us</a>
                <a href="privacy.php" class="active">Data Privacy</a>
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
            <a href="about.php">
                <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor"><circle cx="12" cy="8" r="3" stroke-width="1.5"></circle><path d="M6 20v-1a4 4 0 014-4h4a4 4 0 014 4v1" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path></svg>
                <span>About</span>
            </a>
            <a href="contact.php">
                <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M21 8V7a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v1" stroke-width="1.5"></path><rect x="3" y="8" width="18" height="11" rx="2" ry="2" stroke-width="1.5"></rect></svg>
                <span>Contact</span>
            </a>
            <a href="account.php" class="active">
                <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M12 2l7 4v6c0 5-3.58 9-7 10-3.42-1-7-5-7-10V6l7-4z" stroke-width="1.5"></path></svg>
                <span>Account</span>
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

            <div class="hero-inner">
                <h1 class="animate-up delay-2">Data Privacy Policy</h1>
                <p class="animate-up delay-3">Your privacy is important to us. Learn how we collect, use, and protect your information.</p>
                <p class="animate-up delay-4" style="margin-top:10px;opacity:0.9">Last Updated: February 20, 2026</p>
            </div>
        </section>

        <!-- Privacy Highlights -->
        <section class="py-12 sm:py-16 px-4 bg-gradient-to-b from-white to-gray-50 highlights-section">
            <div class="max-w-6xl mx-auto">
                <h2 class="text-xl sm:text-2xl md:text-3xl mb-6 sm:mb-12 text-center font-bold">Our Commitment to Privacy</h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-6 sm:gap-8 mb-12 sm:mb-16">
                    <!-- Secure Data Card -->
                    <div class="group bg-white rounded-2xl p-8 sm:p-10 text-center shadow-lg hover:shadow-2xl transition-all duration-300 border border-gray-100 hover:border-black/10 transform hover:-translate-y-1">
                        <div class="bg-gradient-to-br from-blue-50 to-blue-100 w-16 h-16 sm:w-20 sm:h-20 rounded-full flex items-center justify-center mx-auto mb-6 group-hover:scale-110 transition-transform duration-300">
                            <svg class="h-8 w-8 sm:h-10 sm:w-10 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                            </svg>
                        </div>
                        <h3 class="text-lg sm:text-xl mb-3 font-bold text-gray-900">Secure Data</h3>
                        <p class="text-sm sm:text-base text-gray-600 leading-relaxed">
                            All personal information is encrypted and stored securely with industry-leading protection standards.
                        </p>
                    </div>
                    
                    <!-- Transparency Card -->
                    <div class="group bg-white rounded-2xl p-8 sm:p-10 text-center shadow-lg hover:shadow-2xl transition-all duration-300 border border-gray-100 hover:border-black/10 transform hover:-translate-y-1">
                        <div class="bg-gradient-to-br from-green-50 to-green-100 w-16 h-16 sm:w-20 sm:h-20 rounded-full flex items-center justify-center mx-auto mb-6 group-hover:scale-110 transition-transform duration-300">
                            <svg class="h-8 w-8 sm:h-10 sm:w-10 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                            </svg>
                        </div>
                        <h3 class="text-lg sm:text-xl mb-3 font-bold text-gray-900">Transparency</h3>
                        <p class="text-sm sm:text-base text-gray-600 leading-relaxed">
                            Clear and honest information about how we collect, use, and protect your data at every step.
                        </p>
                    </div>
                    
                    <!-- Your Control Card -->
                    <div class="group bg-white rounded-2xl p-8 sm:p-10 text-center shadow-lg hover:shadow-2xl transition-all duration-300 border border-gray-100 hover:border-black/10 transform hover:-translate-y-1">
                        <div class="bg-gradient-to-br from-purple-50 to-purple-100 w-16 h-16 sm:w-20 sm:h-20 rounded-full flex items-center justify-center mx-auto mb-6 group-hover:scale-110 transition-transform duration-300">
                            <svg class="h-8 w-8 sm:h-10 sm:w-10 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4"></path>
                            </svg>
                        </div>
                        <h3 class="text-lg sm:text-xl mb-3 font-bold text-gray-900">Your Control</h3>
                        <p class="text-sm sm:text-base text-gray-600 leading-relaxed">
                            You maintain full control over your data. Access, update, or delete your information anytime.
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Privacy Policy Content (now matching Highlights layout) -->
        <section class="py-12 sm:py-16 px-4 bg-gray-50 policy-section">
            <div class="max-w-6xl mx-auto">
                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-6 sm:gap-8">
                    <!-- 1 -->
                    <div class="group bg-white rounded-2xl p-8 sm:p-10 text-center shadow-lg hover:shadow-2xl transition-all duration-300 border border-gray-100 hover:border-black/10 transform hover:-translate-y-1">
                        <div class="bg-blue-50 bg-gradient-to-br from-blue-50 to-blue-100 w-16 h-16 sm:w-20 sm:h-20 rounded-full flex items-center justify-center mx-auto mb-6 group-hover:scale-110 transition-transform duration-300">
                            <svg class="h-8 w-8 sm:h-10 sm:w-10 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        </div>
                        <h3 class="text-lg sm:text-xl mb-3 font-bold text-gray-900">1. Information We Collect</h3>
                        <div class="text-sm sm:text-base text-gray-600 leading-relaxed text-left mx-auto" style="max-width:40ch">
                            <p>We collect information you provide directly to us, including:</p>
                            <ul class="list-disc pl-5 mt-2">
                                <li>Name, email address, and contact information</li>
                                <li>Billing and shipping addresses</li>
                                <li>Payment information (processed securely through third-party payment processors)</li>
                                <li>Account credentials and preferences</li>
                                <li>Communication history and customer support interactions</li>
                            </ul>
                        </div>
                    </div>

                    <!-- 2 -->
                    <div class="group bg-white rounded-2xl p-8 sm:p-10 text-center shadow-lg hover:shadow-2xl transition-all duration-300 border border-gray-100 hover:border-black/10 transform hover:-translate-y-1">
                        <div class="bg-green-50 bg-gradient-to-br from-green-50 to-green-100 w-16 h-16 sm:w-20 sm:h-20 rounded-full flex items-center justify-center mx-auto mb-6 group-hover:scale-110 transition-transform duration-300">
                            <svg class="h-8 w-8 sm:h-10 sm:w-10 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                        </div>
                        <h3 class="text-lg sm:text-xl mb-3 font-bold text-gray-900">2. How We Use Your Information</h3>
                        <div class="text-sm sm:text-base text-gray-600 leading-relaxed text-left mx-auto" style="max-width:40ch">
                            <p>We use the information we collect to:</p>
                            <ul class="list-disc pl-5 mt-2">
                                <li>Process and fulfill your orders</li>
                                <li>Communicate with you about your purchases and account</li>
                                <li>Send you marketing communications (with your consent)</li>
                                <li>Improve our products, services, and customer experience</li>
                                <li>Detect and prevent fraud and unauthorized transactions</li>
                                <li>Comply with legal obligations</li>
                            </ul>
                        </div>
                    </div>

                    <!-- 3 -->
                    <div class="group bg-white rounded-2xl p-8 sm:p-10 text-center shadow-lg hover:shadow-2xl transition-all duration-300 border border-gray-100 hover:border-black/10 transform hover:-translate-y-1">
                        <div class="bg-purple-50 bg-gradient-to-br from-purple-50 to-purple-100 w-16 h-16 sm:w-20 sm:h-20 rounded-full flex items-center justify-center mx-auto mb-6 group-hover:scale-110 transition-transform duration-300">
                            <svg class="h-8 w-8 sm:h-10 sm:w-10 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        </div>
                        <h3 class="text-lg sm:text-xl mb-3 font-bold text-gray-900">3. Information Sharing</h3>
                        <div class="text-sm sm:text-base text-gray-600 leading-relaxed text-left mx-auto" style="max-width:40ch">
                            <p>We do not sell your personal information. We may share your information with:</p>
                            <ul class="list-disc pl-5 mt-2">
                                <li>Service providers who help us operate our business (shipping, payment processing, etc.)</li>
                                <li>Professional advisors and legal authorities when required by law</li>
                                <li>Business partners with your explicit consent</li>
                            </ul>
                        </div>
                    </div>

                    <!-- 4 -->
                    <div class="group bg-white rounded-2xl p-8 sm:p-10 text-center shadow-lg hover:shadow-2xl transition-all duration-300 border border-gray-100 hover:border-black/10 transform hover:-translate-y-1">
                        <div class="bg-red-50 bg-gradient-to-br from-red-50 to-red-100 w-16 h-16 sm:w-20 sm:h-20 rounded-full flex items-center justify-center mx-auto mb-6 group-hover:scale-110 transition-transform duration-300">
                            <svg class="h-8 w-8 sm:h-10 sm:w-10 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
                        </div>
                        <h3 class="text-lg sm:text-xl mb-3 font-bold text-gray-900">4. Data Security</h3>
                        <div class="text-sm sm:text-base text-gray-600 leading-relaxed text-left mx-auto" style="max-width:40ch">
                            <p>We implement industry-standard security measures to protect your personal information, including:</p>
                            <ul class="list-disc pl-5 mt-2">
                                <li>SSL/TLS encryption for data transmission</li>
                                <li>Secure data storage with encryption at rest</li>
                                <li>Regular security audits and updates</li>
                                <li>Limited employee access to personal information</li>
                                <li>Multi-factor authentication for account access</li>
                            </ul>
                        </div>
                    </div>

                    <!-- 5 -->
                    <div class="group bg-white rounded-2xl p-8 sm:p-10 text-center shadow-lg hover:shadow-2xl transition-all duration-300 border border-gray-100 hover:border-black/10 transform hover:-translate-y-1">
                        <div class="bg-indigo-50 bg-gradient-to-br from-indigo-50 to-indigo-100 w-16 h-16 sm:w-20 sm:h-20 rounded-full flex items-center justify-center mx-auto mb-6 group-hover:scale-110 transition-transform duration-300">
                            <svg class="h-8 w-8 sm:h-10 sm:w-10 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m0 0a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        </div>
                        <h3 class="text-lg sm:text-xl mb-3 font-bold text-gray-900">5. Your Rights</h3>
                        <div class="text-sm sm:text-base text-gray-600 leading-relaxed text-left mx-auto" style="max-width:40ch">
                            <p>You have the right to:</p>
                            <ul class="list-disc pl-5 mt-2">
                                <li>Access the personal information we hold about you</li>
                                <li>Request correction of inaccurate information</li>
                                <li>Request deletion of your personal information</li>
                                <li>Opt-out of marketing communications</li>
                                <li>Request a copy of your data in a portable format</li>
                                <li>Object to certain data processing activities</li>
                            </ul>
                        </div>
                    </div>

                    <!-- 6 -->
                    <div class="group bg-white rounded-2xl p-8 sm:p-10 text-center shadow-lg hover:shadow-2xl transition-all duration-300 border border-gray-100 hover:border-black/10 transform hover:-translate-y-1">
                        <div class="bg-yellow-50 bg-gradient-to-br from-yellow-50 to-yellow-100 w-16 h-16 sm:w-20 sm:h-20 rounded-full flex items-center justify-center mx-auto mb-6 group-hover:scale-110 transition-transform duration-300">
                            <svg class="h-8 w-8 sm:h-10 sm:w-10 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        </div>
                        <h3 class="text-lg sm:text-xl mb-3 font-bold text-gray-900">6. Cookies & Tracking</h3>
                        <div class="text-sm sm:text-base text-gray-600 leading-relaxed text-left mx-auto" style="max-width:40ch">
                            <p>We use cookies and similar tracking technologies to improve your browsing experience and analyze site traffic. You can control cookie preferences through your browser settings.</p>
                        </div>
                    </div>

                    <!-- 7 -->
                    <div class="group bg-white rounded-2xl p-8 sm:p-10 text-center shadow-lg hover:shadow-2xl transition-all duration-300 border border-gray-100 hover:border-black/10 transform hover:-translate-y-1">
                        <div class="bg-pink-50 bg-gradient-to-br from-pink-50 to-pink-100 w-16 h-16 sm:w-20 sm:h-20 rounded-full flex items-center justify-center mx-auto mb-6 group-hover:scale-110 transition-transform duration-300">
                            <svg class="h-8 w-8 sm:h-10 sm:w-10 text-pink-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        </div>
                        <h3 class="text-lg sm:text-xl mb-3 font-bold text-gray-900">7. Children's Privacy</h3>
                        <div class="text-sm sm:text-base text-gray-600 leading-relaxed text-left mx-auto" style="max-width:40ch">
                            <p>Our services are not intended for children under 13 years of age. We do not knowingly collect personal information from children.</p>
                        </div>
                    </div>

                    <!-- 8 -->
                    <div class="group bg-white rounded-2xl p-8 sm:p-10 text-center shadow-lg hover:shadow-2xl transition-all duration-300 border border-gray-100 hover:border-black/10 transform hover:-translate-y-1">
                        <div class="bg-cyan-50 bg-gradient-to-br from-cyan-50 to-cyan-100 w-16 h-16 sm:w-20 sm:h-20 rounded-full flex items-center justify-center mx-auto mb-6 group-hover:scale-110 transition-transform duration-300">
                            <svg class="h-8 w-8 sm:h-10 sm:w-10 text-cyan-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20H7m6-4v4m0 0h4"></path></svg>
                        </div>
                        <h3 class="text-lg sm:text-xl mb-3 font-bold text-gray-900">8. International Transfers</h3>
                        <div class="text-sm sm:text-base text-gray-600 leading-relaxed text-left mx-auto" style="max-width:40ch">
                            <p>Your information may be transferred to and processed in countries other than your own. We ensure appropriate safeguards are in place for such transfers.</p>
                        </div>
                    </div>

                    <!-- 9 -->
                    <div class="group bg-white rounded-2xl p-8 sm:p-10 text-center shadow-lg hover:shadow-2xl transition-all duration-300 border border-gray-100 hover:border-black/10 transform hover:-translate-y-1">
                        <div class="bg-orange-50 bg-gradient-to-br from-orange-50 to-orange-100 w-16 h-16 sm:w-20 sm:h-20 rounded-full flex items-center justify-center mx-auto mb-6 group-hover:scale-110 transition-transform duration-300">
                            <svg class="h-8 w-8 sm:h-10 sm:w-10 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                        </div>
                        <h3 class="text-lg sm:text-xl mb-3 font-bold text-gray-900">9. Changes to This Policy</h3>
                        <div class="text-sm sm:text-base text-gray-600 leading-relaxed text-left mx-auto" style="max-width:40ch">
                            <p>We may update this privacy policy from time to time. We will notify you of any material changes by posting the new policy on this page and updating the "Last Updated" date.</p>
                        </div>
                    </div>

            
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
