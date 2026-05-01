/**
 * Mystery Box Animation for Auction Winners
 * Shows a mystery box animation when user has won auctions that haven't been checked out yet
 */

let mysteryBoxData = null;
let mysteryBoxShownThisSession = false;
let currentCarouselIndex = 0;

async function readJsonResponse(res) {
  const raw = await res.text();
  try {
    return JSON.parse(raw);
  } catch (err) {
    throw new Error('Server returned a non-JSON response.');
  }
}

async function checkMysteryBox() {
  // Only show on auction.php
  const path = window.location.pathname.toLowerCase();
  console.log('[MysteryBox] Current path:', path);
  
  if (!path.includes('auction.php')) {
    console.log('[MysteryBox] Not auction.php, skipping');
    return;
  }

  // Don't show again if already shown this session (use sessionStorage for refresh-based reset)
  if (sessionStorage.getItem('mysteryBoxShown') === 'true') {
    console.log('[MysteryBox] Already shown this session');
    return;
  }

  // Don't show if checkout modal is open
  if (document.getElementById('checkoutModal')?.classList.contains('show')) {
    console.log('[MysteryBox] Checkout modal is open');
    return;
  }

  console.log('[MysteryBox] Checking for won auctions...');

  try {
    const res = await fetch('api/get-user-bids.php?limit=200', { cache: 'no-store' });
    const data = await readJsonResponse(res);

    console.log('[MysteryBox] Response:', data);

    if (!res.ok || !data.success || !Array.isArray(data.bids)) {
      console.log('[MysteryBox] Invalid response');
      return;
    }

    // Find won but not checked out auctions
    const wonAuctions = [];
    data.bids.forEach((row) => {
      if (!row) return;
      if (row.auction_status !== 'sold') return;
      if (!row.is_winner || row.checked_out) return;
      if (!row.is_highest_bid_record) return;
      if (row.bid_status && String(row.bid_status).toLowerCase() !== 'valid') return;

      wonAuctions.push({
        auction_id: row.auction_id,
        item_name: row.item_name,
        cover_image: row.cover_image,
        sold_price: row.sold_price
      });
    });

    console.log('[MysteryBox] Won auctions found:', wonAuctions.length);

    if (wonAuctions.length === 0) {
      console.log('[MysteryBox] No won auctions to show');
      return;
    }

    // Store data and show animation
    mysteryBoxData = wonAuctions;
    currentCarouselIndex = 0;
    
    // Mark as shown for this session
    sessionStorage.setItem('mysteryBoxShown', 'true');

    console.log('[MysteryBox] Showing animation in 1.5s...');

    // Delay slightly to let page load first
    setTimeout(() => {
      showMysteryBoxAnimation();
    }, 1500);

  } catch (err) {
    console.error('[MysteryBox] Error:', err);
  }
}

function getMysteryBoxImage(coverImage) {
  // If cover_image is empty or just a filename, use product_media path
  if (!coverImage || coverImage === '') {
    return 'logo.jpg';
  }
  // If it's already a full path or URL, use as is
  if (coverImage.startsWith('http') || coverImage.startsWith('/') || coverImage.includes('product_media') || coverImage.includes('auction_media')) {
    return coverImage;
  }
  // Assume it's in product_media folder
  return 'product_media/' + coverImage;
}

function showMysteryBoxAnimation() {
  console.log('[MysteryBox] showMysteryBoxAnimation called');
  if (!mysteryBoxData || mysteryBoxData.length === 0) return;
  
  // Create overlay
  const overlay = document.createElement('div');
  overlay.className = 'mystery-box-overlay show';
  overlay.id = 'mysteryBoxOverlay';
  
  const container = document.createElement('div');
  container.className = 'mystery-box-container';
  
  // Title
  const title = document.createElement('div');
  title.className = 'mystery-box-title';
  title.textContent = '🎉 Congratulations!';
  container.appendChild(title);
  
  // Box content with carousel
  const content = document.createElement('div');
  content.className = 'mystery-box-content';
  
  const box = document.createElement('div');
  box.className = 'mystery-box';
  box.id = 'mysteryBox';
  content.appendChild(box);
  
  // Product carousel container
  const carousel = document.createElement('div');
  carousel.id = 'mysteryBoxCarousel';
  carousel.style.cssText = 'position:absolute;inset:0;display:flex;align-items:center;justify-content:center;';
  content.appendChild(carousel);
  
  // Carousel navigation (if multiple products)
  if (mysteryBoxData.length > 1) {
    const navLeft = document.createElement('button');
    navLeft.className = 'mystery-box-nav mystery-box-nav-left';
    navLeft.innerHTML = '&#10094;';
    navLeft.onclick = () => navigateCarousel(-1);
    content.appendChild(navLeft);
    
    const navRight = document.createElement('button');
    navRight.className = 'mystery-box-nav mystery-box-nav-right';
    navRight.innerHTML = '&#10095;';
    navRight.onclick = () => navigateCarousel(1);
    content.appendChild(navRight);
  }
  
  container.appendChild(content);
  
  // Product info
  const info = document.createElement('div');
  info.className = 'mystery-box-info';
  info.innerHTML = `
    <div class="mystery-box-product-name" id="mysteryBoxProductName">${mysteryBoxData[0].item_name}</div>
    <div class="mystery-box-product-count" id="mysteryBoxProductCount">${mysteryBoxData.length > 1 ? `Item 1 of ${mysteryBoxData.length}` : ''}</div>
  `;
  container.appendChild(info);
  
  // Progress bar
  const progress = document.createElement('div');
  progress.className = 'mystery-box-progress';
  progress.innerHTML = '<div class="mystery-box-progress-bar" id="mysteryBoxProgress" style="width:0%"></div>';
  container.appendChild(progress);
  
  // Close button
  const closeBtn = document.createElement('button');
  closeBtn.className = 'mystery-box-close';
  closeBtn.textContent = 'Claim Prize';
  closeBtn.onclick = closeMysteryBox;
  container.appendChild(closeBtn);
  
  overlay.appendChild(container);
  document.body.appendChild(overlay);
  
  // Show first product
  renderCarouselItem(0);
  
  // Trigger animation sequence
  setTimeout(() => {
    startBoxAnimation();
  }, 100);
}

function renderCarouselItem(index) {
  const carousel = document.getElementById('mysteryBoxCarousel');
  const productName = document.getElementById('mysteryBoxProductName');
  const productCount = document.getElementById('mysteryBoxProductCount');
  
  if (!carousel || !mysteryBoxData || !mysteryBoxData[index]) return;
  
  const item = mysteryBoxData[index];
  
  carousel.innerHTML = `
    <img class="mystery-box-product reveal" src="${getMysteryBoxImage(item.cover_image)}" alt="${item.item_name}">
  `;
  
  if (productName) {
    productName.textContent = item.item_name;
  }
  
  if (productCount && mysteryBoxData.length > 1) {
    productCount.textContent = `Item ${index + 1} of ${mysteryBoxData.length}`;
  }
  
  currentCarouselIndex = index;
}

function navigateCarousel(direction) {
  if (!mysteryBoxData || mysteryBoxData.length <= 1) return;
  
  let newIndex = currentCarouselIndex + direction;
  if (newIndex < 0) newIndex = mysteryBoxData.length - 1;
  if (newIndex >= mysteryBoxData.length) newIndex = 0;
  
  renderCarouselItem(newIndex);
}

function startBoxAnimation() {
  const box = document.getElementById('mysteryBox');
  const progress = document.getElementById('mysteryBoxProgress');
  
  if (!box || !progress) return;
  
  // Phase 1: Shake the box (0-3s)
  setTimeout(() => {
    box.classList.add('open');
  }, 1500);
  
  // Phase 2: Open box and show product (3-5s)
  setTimeout(() => {
    revealProducts();
  }, 4000);
  
  // Phase 3: Start progress bar
  let progressPercent = 0;
  const progressInterval = setInterval(() => {
    progressPercent += 2;
    progress.style.width = Math.min(progressPercent, 100) + '%';
    
    if (progressPercent >= 100) {
      clearInterval(progressInterval);
    }
  }, 300);
  
  // Auto-close after 15 seconds
  setTimeout(() => {
    const closeBtn = document.querySelector('.mystery-box-close');
    if (closeBtn) {
      closeBtn.textContent = 'Continue to Checkout';
    }
  }, 15000);
}

function revealProducts() {
  const box = document.getElementById('mysteryBox');
  
  if (!box) return;
  
  // Hide the box question mark
  box.style.opacity = '0';
  
  // Show confetti
  createConfetti();
}

function createConfetti() {
  const container = document.createElement('div');
  container.className = 'confetti-container';
  container.id = 'confettiContainer';
  document.body.appendChild(container);
  
  const colors = ['#fbbf24', '#f59e0b', '#10b981', '#3b82f6', '#8b5cf6', '#ec4899', '#ef4444'];
  const shapes = ['square', 'circle'];
  
  for (let i = 0; i < 50; i++) {
    const confetti = document.createElement('div');
    confetti.className = 'confetti-piece';
    
    const color = colors[Math.floor(Math.random() * colors.length)];
    const shape = shapes[Math.floor(Math.random() * shapes.length)];
    const left = Math.random() * 100;
    const delay = Math.random() * 0.5;
    const duration = 2 + Math.random() * 2;
    
    confetti.style.cssText = `
      left: ${left}%;
      background: ${color};
      border-radius: ${shape === 'circle' ? '50%' : '2px'};
      animation: confettiFall ${duration}s ease-out ${delay}s forwards;
    `;
    
    container.appendChild(confetti);
  }
  
  // Clean up confetti after animation
  setTimeout(() => {
    const existing = document.getElementById('confettiContainer');
    if (existing) {
      existing.remove();
    }
  }, 5000);
}

function closeMysteryBox() {
  const overlay = document.getElementById('mysteryBoxOverlay');
  if (overlay) {
    overlay.classList.remove('show');
    setTimeout(() => {
      overlay.remove();
    }, 400);
  }

  // Open checkout modal
  setTimeout(() => {
    if (mysteryBoxData && mysteryBoxData.length > 0) {
      // Navigate to bidding history to checkout
      window.location.href = 'bidding_history.php';
    }
  }, 500);
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', () => {
  console.log('[MysteryBox] DOM Content Loaded');
  setTimeout(checkMysteryBox, 500);
});

// Also run on load for safety
if (document.readyState === 'complete') {
  setTimeout(checkMysteryBox, 500);
} else {
  window.addEventListener('load', () => {
    setTimeout(checkMysteryBox, 500);
  });
}