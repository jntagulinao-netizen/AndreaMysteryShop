<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LUXE - Contact Us</title>
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
        /* Match original Tailwind sizes for contact page heading */
        .hero h1 { margin:0 0 12px; font-size:30px; line-height:1.02; }
        @media (min-width:640px) { .hero h1 { font-size:36px; } }
        @media (min-width:768px) { .hero h1 { font-size:60px; } }
        @media (min-width:1024px) { .hero h1 { font-size:72px; } }
        .hero p { margin:0 0 18px; color:rgba(255,255,255,0.88); font-size:16px; }
        .animate-fade-in { animation: fadeIn 1s ease-out; }

        /* Form grid helper */
        .two-col-grid { display:grid; grid-template-columns:1fr; gap:24px; }
        @media (min-width:768px) { .two-col-grid { grid-template-columns:repeat(2,1fr); } }

        .contact-section { padding:48px 16px; background:linear-gradient(180deg,#ffffff,#f8fafc); }

        /* Form elements - match Tailwind spacing and focus styles */
        input[type="text"], input[type="email"], textarea { width:100%; padding:12px 14px; border:2px solid #e5e7eb; border-radius:12px; font-size:14px; color:#111827; box-sizing:border-box; }
        input[type="text"]:focus, input[type="email"]:focus, textarea:focus { outline:0; border-color:#3b82f6; box-shadow:0 6px 20px rgba(59,130,246,0.12); }
        textarea { resize:none; }

        /* Contact info icons: ensure consistent size and centered background */
        .contact-section .bg-gradient-to-br { padding:12px; border-radius:10px; display:flex; align-items:center; justify-content:center; }
        .contact-section .bg-gradient-to-br svg { width:24px !important; height:24px !important; display:block; }

        /* Form heading */
        .form-heading { font-size:20px; margin:0; font-weight:700; color:#0f1724; }
        @media (min-width:640px) { .form-heading { font-size:24px; } }
        @media (min-width:768px) { .form-heading { font-size:30px; } }

        /* Primary gradient button */
        .btn-gradient { display:inline-block; background:linear-gradient(90deg,#2563eb,#7c3aed); color:#fff; border:none; padding:12px 18px; border-radius:12px; font-weight:600; cursor:pointer; box-shadow:0 12px 30px rgba(37,99,235,0.12); }
        .btn-gradient:hover { transform:translateY(-2px); }

        /* Footer svg sizing to match original */
        .site-footer svg { width:16px; height:16px; display:inline-block; }
        @media (min-width:640px) { .site-footer svg { width:20px; height:20px; } }
        @media (min-width:1024px) { .site-footer svg { width:24px; height:24px; } }

        /* FAQ icons sizing (ensure consistent size and alignment) */
        .faq-section svg { width:20px; height:20px; display:block; }
        @media (min-width:640px) { .faq-section svg { width:20px; height:20px; } }
        @media (min-width:1024px) { .faq-section svg { width:24px; height:24px; } }
        
        /* FAQ utility helpers to reproduce Tailwind look */
        .faq-section .bg-white { background:#fff; }
        .faq-section .p-6 { padding:1.5rem; }
        @media (min-width:640px) { .faq-section .sm\:p-8 { padding:2rem; } }
        .faq-section .rounded-2xl { border-radius:18px; }
        .faq-section .shadow-md { box-shadow:0 8px 20px rgba(2,6,23,0.06); }
        .faq-section .hover\:shadow-lg:hover { box-shadow:0 20px 40px rgba(2,6,23,0.08); }
        .faq-section .cursor-pointer { cursor:pointer; }
        .faq-section .hover\:bg-gray-50:hover { background:#f8fafc; }
        .faq-section .transition-all { transition: all 0.3s ease; }
        .faq-section .duration-300 { transition-duration:0.3s; }

        /* Flex / spacing utilities used in FAQ */
        .faq-section .flex { display:flex; }
        .faq-section .items-start { align-items:flex-start; }
        .faq-section .justify-between { justify-content:space-between; }
        .faq-section .space-x-4 > * + * { margin-left:1rem; }
        .faq-section .flex-1 { flex:1 1 auto; }
        .faq-section .mt-1 { margin-top:0.25rem; }

        /* Icon container + sizes */
        .faq-section .bg-blue-100 { background:#dbeafe; }
        .faq-section .bg-green-100 { background:#dcfce7; }
        .faq-section .bg-purple-100 { background:#f3e8ff; }
        .faq-section .bg-orange-100 { background:#fff7ed; }
        .faq-section .p-3 { padding:0.75rem; }
        .faq-section .rounded-lg { border-radius:0.5rem; }
        .faq-section .h-5 { height:1.25rem; }
        .faq-section .w-5 { width:1.25rem; }
        .faq-section .h-6 { height:1.5rem; }
        .faq-section .w-6 { width:1.5rem; }
        .faq-section .text-blue-600 { color:#2563eb; }
        .faq-section .text-green-600 { color:#10b981; }
        .faq-section .text-purple-600 { color:#8b5cf6; }
        .faq-section .text-orange-600 { color:#f97316; }

        /* Typography used in FAQ */
        .faq-section .text-sm { font-size:0.875rem; }
        @media (min-width:640px) { .faq-section .sm\:text-base { font-size:1rem; } }
        .faq-section .font-bold { font-weight:700; }
        .faq-section .text-gray-900 { color:#0f1724; }
        .faq-section .text-gray-600 { color:#4b5563; }
        .faq-section .mb-3 { margin-bottom:0.75rem; }
        .faq-section .leading-relaxed { line-height:1.6; }
        .rounded-2xl { border-radius:18px; }
        .shadow-lg { box-shadow:0 20px 40px rgba(2,6,23,0.08); }

        /* Footer */
        .site-footer { background:#0f1724; color:#d1d5db; padding:40px 16px; }
        .site-footer .footer-grid { display:grid; grid-template-columns:1fr; gap:18px; max-width:1200px; margin:0 auto; }
        @media (min-width:640px) { .site-footer .footer-grid { grid-template-columns:repeat(2,1fr); } }
        @media (min-width:1024px) { .site-footer .footer-grid { grid-template-columns:repeat(4,1fr); } }
        .site-footer a { color: #9ca3af; text-decoration:none; }
        .site-footer a:hover { color:#fff; }

        /* small animation */
        @keyframes fadeIn { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }

        /* Staggered entrance used by hero elements */
        .hero-inner .animate-up { opacity: 0; transform: translateY(10px); animation: fadeUp .6s cubic-bezier(.2,.9,.3,1) forwards; }
        .hero-inner .animate-up.delay-1 { animation-delay: 0.12s; }
        .hero-inner .animate-up.delay-2 { animation-delay: 0.26s; }
        .hero-inner .animate-up.delay-3 { animation-delay: 0.40s; }
        .hero-inner .animate-up.delay-4 { animation-delay: 0.56s; }
        @keyframes fadeUp { to { opacity: 1; transform: translateY(0); } }

        /* Stats helpers (if used) */
        .stats { padding:48px 16px; background:linear-gradient(90deg,#0b1220,#000); }
        .stats-grid { display:grid; gap:16px; grid-template-columns:repeat(2,1fr); max-width:1100px; margin:0 auto; }
        @media (min-width:768px) { .stats-grid { grid-template-columns:repeat(4,1fr); } }
        .stat-card { background:rgba(255,255,255,0.06); border-radius:16px; padding:20px; text-align:center; border:1px solid rgba(255,255,255,0.08); }
        .stat-number { font-weight:800; color:#fff; font-size:28px; margin-bottom:8px; }
        @media (min-width:640px) { .stat-number { font-size:36px; } }
        @media (min-width:1024px) { .stat-number { font-size:42px; } }
        .stat-sub { color:#d1d5db; font-size:14px; }

        /* Contact info cards hover: match .stat-card:hover */
        .contact-section .space-y-4 > div { transition: transform .2s ease, box-shadow .2s ease, background-color .2s ease; }
        .contact-section .space-y-4 > div:hover {
            transform: translateY(-6px);
            box-shadow: 0 16px 36px rgba(2,6,23,0.08);
            background: rgba(255,255,255,0.98);
        }
        .contact-section .space-y-4 > div:hover h3 {
            background: linear-gradient(90deg,#60a5fa,#06b6d4);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
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
                <a href="contact.php" class="active">Contact Us</a>
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
            <a href="about.php">
                <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor"><circle cx="12" cy="8" r="3" stroke-width="1.5"></circle><path d="M6 20v-1a4 4 0 014-4h4a4 4 0 014 4v1" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"></path></svg>
                <span>About</span>
            </a>
            <a href="contact.php" class="active">
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
                <h1 class="animate-up delay-2">Get in Touch</h1>
                <p class="animate-up delay-3">Have questions? We'd love to hear from you. Send us a message and we'll respond as soon as possible.</p>
            </div>
        </section>

        <!-- Contact Form & Info -->
        <section class="contact-section">
            <div class="container">
                <div class="two-col-grid">
                    <!-- Contact Form -->
                    <div style="background:#fff;border-radius:18px;padding:28px;box-shadow:0 20px 40px rgba(2,6,23,0.08);border:1px solid rgba(0,0,0,0.04)">
                        <div style="display:flex;align-items:center;gap:12px;margin-bottom:20px;">
                            <div style="height:6px;width:48px;border-radius:999px;background:linear-gradient(90deg,#2563eb,#7c3aed)"></div>
                            <h2 class="form-heading">Send us a Message</h2>
                        </div>
                        <form action="https://formspree.io/f/xvzbwvpa" method="POST" style="display:flex;flex-direction:column;gap:18px">
                            <!-- Honeypot to reduce spam -->
                            <input type="text" name="_gotcha" style="display:none" />

                            <div>
                                <label for="name" class="block mb-2.5 text-sm font-semibold text-gray-900">Full Name</label>
                                <input id="name" name="name" type="text" placeholder="Your Name" required />
                            </div>
                            <div>
                                <label for="email" class="block mb-2.5 text-sm font-semibold text-gray-900">Email Address</label>
                                <input id="email" name="_replyto" type="email" placeholder="john@example.com" required />
                            </div>
                            <div>
                                <label for="subject" class="block mb-2.5 text-sm font-semibold text-gray-900">Subject</label>
                                <input id="subject" name="subject" type="text" placeholder="How can we help?" />
                            </div>
                            <div>
                                <label for="message" class="block mb-2.5 text-sm font-semibold text-gray-900">Message</label>
                                <textarea id="message" name="message" placeholder="Your message here..." rows="5" required></textarea>
                            </div>
                            <button type="submit" class="btn-gradient" style="width:100%">Send Message</button>
                        </form>
                    </div>

                    <!-- Contact Information -->
                    <div style="display:flex;flex-direction:column;gap:20px">
                        <div>
                            <h2 class="text-xl sm:text-2xl md:text-3xl mb-3 font-bold text-gray-900">Contact Information</h2>
                            <p class="text-sm sm:text-base text-gray-600">
                                Reach out to us through any of these channels. We're here to help!
                            </p>
                        </div>

                        <div class="space-y-4">
                            <!-- Email Card -->
                            <div style="background:#fff;border-radius:12px;padding:20px;box-shadow:0 10px 30px rgba(2,6,23,0.06);border-left:4px solid #3b82f6">
                                <div class="flex items-start space-x-4">
                                    <div class="bg-gradient-to-br from-blue-100 to-blue-200 p-3 rounded-lg flex-shrink-0">
                                        <svg class="h-6 w-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                                        </svg>
                                    </div>
                                    <div class="flex-1">
                                        <h3 class="text-sm font-bold text-gray-900 mb-2">Email</h3>
                                        <p class="text-sm text-gray-600">support@luxe.com</p>
                                        <p class="text-sm text-gray-600">sales@luxe.com</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Phone Card -->
                            <div style="background:#fff;border-radius:12px;padding:20px;box-shadow:0 10px 30px rgba(2,6,23,0.06);border-left:4px solid #10b981">
                                <div class="flex items-start space-x-4">
                                    <div class="bg-gradient-to-br from-green-100 to-green-200 p-3 rounded-lg flex-shrink-0">
                                        <svg class="h-6 w-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path>
                                        </svg>
                                    </div>
                                    <div class="flex-1">
                                        <h3 class="text-sm font-bold text-gray-900 mb-2">Phone</h3>
                                        <p class="text-sm text-gray-600">+1 (555) 123-4567</p>
                                        <p class="text-sm text-gray-600">+1 (555) 987-6543</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Address Card -->
                            <div style="background:#fff;border-radius:12px;padding:20px;box-shadow:0 10px 30px rgba(2,6,23,0.06);border-left:4px solid #8b5cf6">
                                <div class="flex items-start space-x-4">
                                    <div class="bg-gradient-to-br from-purple-100 to-purple-200 p-3 rounded-lg flex-shrink-0">
                                        <svg class="h-6 w-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                        </svg>
                                    </div>
                                    <div class="flex-1">
                                        <h3 class="text-sm font-bold text-gray-900 mb-2">Address</h3>
                                        <p class="text-sm text-gray-600">
                                            123 Luxury Boulevard<br />
                                            New York, NY 10001<br />
                                            United States
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <!-- Hours Card -->
                            <div style="background:#fff;border-radius:12px;padding:20px;box-shadow:0 10px 30px rgba(2,6,23,0.06);border-left:4px solid #f97316">
                                <div class="flex items-start space-x-4">
                                    <div class="bg-gradient-to-br from-orange-100 to-orange-200 p-3 rounded-lg flex-shrink-0">
                                        <svg class="h-6 w-6 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                    </div>
                                    <div class="flex-1">
                                        <h3 class="text-sm font-bold text-gray-900 mb-2">Business Hours</h3>
                                        <p class="text-sm text-gray-600">Monday - Friday: 9AM - 6PM EST</p>
                                        <p class="text-sm text-gray-600">Saturday: 10AM - 4PM EST</p>
                                        <p class="text-sm text-gray-600">Sunday: Closed</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- FAQ Section -->
        <section class="faq-section" style="padding:48px 16px;background:#f9fafb;">
            <div class="container" style="max-width:900px;margin:0 auto;">
                <div style="text-align:center;margin-bottom:28px;">
                    <h2 style="font-size:22px;margin:0 0 8px;font-weight:700;color:#0f1724">Frequently Asked Questions</h2>
                    <div style="height:6px;width:96px;background:linear-gradient(90deg,#2563eb,#7c3aed);border-radius:999px;margin:10px auto 0"></div>
                </div>
                <div style="display:flex;flex-direction:column;gap:18px">
                    <!-- FAQ Item 1 -->
                    <div class="bg-white rounded-2xl shadow-md hover:shadow-lg transition-all duration-300 overflow-hidden border-l-4 border-blue-500">
                        <div class="p-6 sm:p-8 cursor-pointer hover:bg-gray-50 transition-colors">
                            <div class="flex items-start justify-between">
                                <div class="flex items-start space-x-4 flex-1">
                                    <div class="bg-blue-100 p-3 rounded-lg flex-shrink-0 mt-1">
                                        <svg class="h-5 w-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                    </div>
                                    <div>
                                        <h3 class="text-sm sm:text-base font-bold text-gray-900 mb-3">What is your return policy?</h3>
                                        <p class="text-sm sm:text-base text-gray-600 leading-relaxed">
                                            We offer a 30-day return policy for all unused items in their original packaging. If you're not satisfied with your purchase, we'll make it right!
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- FAQ Item 2 -->
                    <div class="bg-white rounded-2xl shadow-md hover:shadow-lg transition-all duration-300 overflow-hidden border-l-4 border-green-500">
                        <div class="p-6 sm:p-8 cursor-pointer hover:bg-gray-50 transition-colors">
                            <div class="flex items-start justify-between">
                                <div class="flex items-start space-x-4 flex-1">
                                    <div class="bg-green-100 p-3 rounded-lg flex-shrink-0 mt-1">
                                        <svg class="h-5 w-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                                        </svg>
                                    </div>
                                    <div>
                                        <h3 class="text-sm sm:text-base font-bold text-gray-900 mb-3">How long does shipping take?</h3>
                                        <p class="text-sm sm:text-base text-gray-600 leading-relaxed">
                                            Standard shipping takes 5-7 business days. Express shipping is available for 2-3 day delivery. We'll send you tracking information so you can monitor your order!
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- FAQ Item 3 -->
                    <div class="bg-white rounded-2xl shadow-md hover:shadow-lg transition-all duration-300 overflow-hidden border-l-4 border-purple-500">
                        <div class="p-6 sm:p-8 cursor-pointer hover:bg-gray-50 transition-colors">
                            <div class="flex items-start justify-between">
                                <div class="flex items-start space-x-4 flex-1">
                                    <div class="bg-purple-100 p-3 rounded-lg flex-shrink-0 mt-1">
                                        <svg class="h-5 w-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20H7m6-4v4m0 0h4"></path>
                                        </svg>
                                    </div>
                                    <div>
                                        <h3 class="text-sm sm:text-base font-bold text-gray-900 mb-3">Do you ship internationally?</h3>
                                        <p class="text-sm sm:text-base text-gray-600 leading-relaxed">
                                            Yes! We ship to over 25 countries worldwide. International shipping times vary by location (typically 7-14 business days). Additional customs fees may apply depending on your country.
                                        </p>
                                    </div>
                                </div>
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
    </footer>

        <!-- SweetAlert2 and AJAX form submit to show success/error modals -->
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const form = document.querySelector('form[action="https://formspree.io/f/xvzbwvpa"]');
                if (!form) return;

                form.addEventListener('submit', async function (e) {
                    e.preventDefault();
                    const submitBtn = form.querySelector('button[type="submit"]');
                    if (submitBtn) submitBtn.disabled = true;

                    const formData = new FormData(form);

                    try {
                        const res = await fetch(form.action, {
                            method: form.method || 'POST',
                            body: formData,
                            headers: {
                                'Accept': 'application/json'
                            }
                        });

                        if (res.ok) {
                            Swal.fire({
                                title: 'Message sent',
                                text: 'Thanks — we received your message and will reply shortly.',
                                icon: 'success',
                                confirmButtonText: 'OK'
                            });
                            form.reset();
                        } else {
                            let errText = 'There was a problem sending your message.';
                            try {
                                const data = await res.json();
                                if (data && data.error) errText = data.error;
                            } catch (err) { }
                            Swal.fire({ title: 'Oops...', text: errText, icon: 'error', confirmButtonText: 'OK' });
                        }
                    } catch (err) {
                        Swal.fire({ title: 'Network error', text: 'Unable to send message. Please try again later.', icon: 'error', confirmButtonText: 'OK' });
                    } finally {
                        if (submitBtn) submitBtn.disabled = false;
                    }
                });
            });
        </script>

    </body>
</html>
