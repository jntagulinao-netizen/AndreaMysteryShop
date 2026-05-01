/**
 * Winner Notification Animation for Auction Winners
 * Shows winning auction products in a carousel when user has won auctions that haven't been checked out yet
 */

let winnerData = null;
let currentWinnerIndex = 0;
let winnerAutoRotateInterval = null;
let winnerProgressInterval = null;
let winnerCheckoutTextTimeout = null;
let winnerImageSwapTimeout = null;
let winnerCheckScheduled = false;
let winnerNotificationOpen = false;

async function readJsonResponse(res) {
  const raw = await res.text();
  try {
    return JSON.parse(raw);
  } catch (err) {
    throw new Error('Server returned a non-JSON response.');
  }
}

async function checkWinnerNotification() {
  if (winnerNotificationOpen || document.getElementById('winnerNotifyOverlay')) {
    return;
  }

  // Only show on auction.php
  const path = window.location.pathname.toLowerCase();
  console.log('[WinnerNotify] Current path:', path);
  
  if (!path.includes('auction.php')) {
    console.log('[WinnerNotify] Not auction.php, skipping');
    return;
  }

  // Don't show if checkout modal is open
  if (document.getElementById('checkoutModal')?.classList.contains('show')) {
    console.log('[WinnerNotify] Checkout modal is open');
    return;
  }

  console.log('[WinnerNotify] Checking for won auctions...');

  try {
    const res = await fetch('api/get-user-bids.php?limit=200', { cache: 'no-store' });
    const data = await readJsonResponse(res);

    console.log('[WinnerNotify] Response:', data);

    if (!res.ok || !data.success || !Array.isArray(data.bids)) {
      console.log('[WinnerNotify] Invalid response');
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

    console.log('[WinnerNotify] Won auctions found:', wonAuctions.length);

    if (wonAuctions.length === 0) {
      console.log('[WinnerNotify] No won auctions to show');
      return;
    }

    // Store data and show animation
    winnerData = wonAuctions;
    currentWinnerIndex = 0;

    console.log('[WinnerNotify] Showing notification in 1.5s...');

    // Delay slightly to let page load first
    setTimeout(() => {
      showWinnerNotification();
    }, 1500);

  } catch (err) {
    console.error('[WinnerNotify] Error:', err);
  }
}

function getWinnerImage(coverImage) {
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

function showWinnerNotification() {
  console.log('[WinnerNotify] showWinnerNotification called');
  if (!winnerData || winnerData.length === 0) return;

  if (winnerNotificationOpen) return;

  // Clean up any stale duplicate overlays before creating a new one
  document.querySelectorAll('#winnerNotifyOverlay').forEach((node) => node.remove());
  
  // Create overlay
  const overlay = document.createElement('div');
  overlay.className = 'winner-notify-overlay show';
  overlay.id = 'winnerNotifyOverlay';
  winnerNotificationOpen = true;
  
  const container = document.createElement('div');
  container.className = 'winner-notify-container';
  
  // Title
  const title = document.createElement('div');
  title.className = 'winner-notify-title';
  title.innerHTML = '🎉 Congratulations! You Won!';
  container.appendChild(title);

  const subtitle = document.createElement('div');
  subtitle.className = 'winner-notify-subtitle';
  subtitle.textContent = 'Please claim your winning auction item.';
  container.appendChild(subtitle);
  
  // Carousel
  const carousel = document.createElement('div');
  carousel.className = 'winner-notify-carousel';
  carousel.id = 'winnerCarousel';
  
  // Product image
  const productWrapper = document.createElement('div');
  productWrapper.className = 'winner-product-wrapper';
  
  const productImage = document.createElement('img');
  productImage.className = 'winner-product-image';
  productImage.id = 'winnerProductImage';
  productImage.src = getWinnerImage(winnerData[0].cover_image);
  productImage.alt = winnerData[0].item_name;
  productWrapper.appendChild(productImage);
  
  carousel.appendChild(productWrapper);

  // Navigation arrows in a separate row so they do not cover the image
  if (winnerData.length > 1) {
    const navRow = document.createElement('div');
    navRow.className = 'winner-nav-row';

    const leftBtn = document.createElement('button');
    leftBtn.className = 'winner-nav winner-nav-left';
    leftBtn.type = 'button';
    leftBtn.textContent = '◀';
    leftBtn.setAttribute('aria-label', 'Previous item');
    leftBtn.onclick = () => {
      navigateWinner(-1);
      restartAutoRotation();
    };

    const rightBtn = document.createElement('button');
    rightBtn.className = 'winner-nav winner-nav-right';
    rightBtn.type = 'button';
    rightBtn.textContent = '▶';
    rightBtn.setAttribute('aria-label', 'Next item');
    rightBtn.onclick = () => {
      navigateWinner(1);
      restartAutoRotation();
    };

    navRow.appendChild(leftBtn);
    navRow.appendChild(rightBtn);

    container.appendChild(carousel);
    container.appendChild(navRow);
  } else {
    container.appendChild(carousel);
  }

  
  // Product info
  const info = document.createElement('div');
  info.className = 'winner-notify-info';

  const nameEl = document.createElement('div');
  nameEl.className = 'winner-product-name';
  nameEl.id = 'winnerProductName';
  nameEl.textContent = winnerData[0].item_name || 'Won Item';

  const priceEl = document.createElement('div');
  priceEl.className = 'winner-product-price';
  priceEl.id = 'winnerProductPrice';
  priceEl.textContent = formatPeso(winnerData[0].sold_price);

  const counterEl = document.createElement('div');
  counterEl.className = 'winner-product-counter';
  counterEl.id = 'winnerProductCounter';
  counterEl.textContent = `Item 1 of ${winnerData.length}`;

  info.appendChild(nameEl);
  info.appendChild(priceEl);
  info.appendChild(counterEl);
  container.appendChild(info);
  
  // Progress bar
  const progress = document.createElement('div');
  progress.className = 'winner-notify-progress';
  progress.innerHTML = '<div class="winner-progress-bar" id="winnerProgress" style="width:0%"></div>';
  container.appendChild(progress);
  
  // Action buttons
  const actions = document.createElement('div');
  actions.className = 'winner-notify-actions';
  
  const claimBtn = document.createElement('button');
  claimBtn.className = 'winner-btn winner-btn-primary';
  claimBtn.textContent = 'Claim Now';
  claimBtn.onclick = () => closeWinnerNotification(true);
  actions.appendChild(claimBtn);
  
  const dismissBtn = document.createElement('button');
  dismissBtn.className = 'winner-btn winner-btn-secondary';
  dismissBtn.textContent = 'Later';
  dismissBtn.onclick = () => closeWinnerNotification(false);
  actions.appendChild(dismissBtn);
  
  container.appendChild(actions);
  
  overlay.appendChild(container);
  document.body.appendChild(overlay);
  
  // Start progress bar
  startProgressBar();
  
  // Start auto-rotation if multiple items
  if (winnerData.length > 1) {
    startAutoRotation();
  }
}

function startAutoRotation() {
  // Clear any existing interval
  if (winnerAutoRotateInterval) {
    clearInterval(winnerAutoRotateInterval);
    winnerAutoRotateInterval = null;
  }
  
  // Auto-rotate every 4 seconds to reduce overlap with manual navigation
  winnerAutoRotateInterval = setInterval(() => {
    navigateWinner(1);
  }, 4000);
}

function restartAutoRotation() {
  if (winnerData && winnerData.length > 1) {
    startAutoRotation();
  }
}

function formatPeso(value) {
  const amount = Number(value || 0);
  if (!Number.isFinite(amount)) return '₱0';
  return '₱' + amount.toLocaleString('en-PH', { minimumFractionDigits: 0, maximumFractionDigits: 2 });
}

function navigateWinner(direction) {
  if (!winnerData || winnerData.length <= 1) return;
  
  let newIndex = currentWinnerIndex + direction;
  if (newIndex < 0) newIndex = winnerData.length - 1;
  if (newIndex >= winnerData.length) newIndex = 0;
  
  // Update image with fade effect
  const img = document.getElementById('winnerProductImage');
  const name = document.getElementById('winnerProductName');
  const price = document.getElementById('winnerProductPrice');
  const counter = document.getElementById('winnerProductCounter');
  
  if (img) {
    if (winnerImageSwapTimeout) {
      clearTimeout(winnerImageSwapTimeout);
      winnerImageSwapTimeout = null;
    }

    img.style.opacity = '0';

    winnerImageSwapTimeout = setTimeout(() => {
      img.src = getWinnerImage(winnerData[newIndex].cover_image);
      img.alt = winnerData[newIndex].item_name;
      img.style.opacity = '1';
      winnerImageSwapTimeout = null;
    }, 200);
  }
  
  if (name) {
    name.textContent = winnerData[newIndex].item_name;
  }

  if (price) {
    price.textContent = formatPeso(winnerData[newIndex].sold_price);
  }
  
  if (counter) {
    counter.textContent = `Item ${newIndex + 1} of ${winnerData.length}`;
  }
  
  currentWinnerIndex = newIndex;
}

function startProgressBar() {
  const progress = document.getElementById('winnerProgress');
  if (!progress) return;

  if (winnerProgressInterval) {
    clearInterval(winnerProgressInterval);
    winnerProgressInterval = null;
  }

  if (winnerCheckoutTextTimeout) {
    clearTimeout(winnerCheckoutTextTimeout);
    winnerCheckoutTextTimeout = null;
  }

  if (winnerImageSwapTimeout) {
    clearTimeout(winnerImageSwapTimeout);
    winnerImageSwapTimeout = null;
  }
  
  let progressPercent = 0;
  winnerProgressInterval = setInterval(() => {
    progressPercent += 2;
    progress.style.width = Math.min(progressPercent, 100) + '%';
    
    if (progressPercent >= 100) {
      clearInterval(winnerProgressInterval);
      winnerProgressInterval = null;
    }
  }, 300);
  
  // Auto update after 15 seconds
  winnerCheckoutTextTimeout = setTimeout(() => {
    const claimBtn = document.querySelector('.winner-btn-primary');
    if (claimBtn) {
      claimBtn.textContent = 'Go to Checkout';
    }
    winnerCheckoutTextTimeout = null;
  }, 15000);
}

function closeWinnerNotification(shouldRedirect = true) {
  const overlay = document.getElementById('winnerNotifyOverlay');

  if (winnerAutoRotateInterval) {
    clearInterval(winnerAutoRotateInterval);
    winnerAutoRotateInterval = null;
  }

  if (winnerProgressInterval) {
    clearInterval(winnerProgressInterval);
    winnerProgressInterval = null;
  }

  if (winnerCheckoutTextTimeout) {
    clearTimeout(winnerCheckoutTextTimeout);
    winnerCheckoutTextTimeout = null;
  }

  if (overlay) {
    if (shouldRedirect) {
      overlay.classList.remove('show');
      setTimeout(() => {
        overlay.remove();
        winnerNotificationOpen = false;
      }, 220);
    } else {
      // Remove immediately for Later so dismiss feels instant
      overlay.remove();
      winnerNotificationOpen = false;
    }
  } else {
    winnerNotificationOpen = false;
  }

  // Only redirect if shouldRedirect is true
  if (shouldRedirect) {
    setTimeout(() => {
      if (winnerData && winnerData.length > 0) {
        window.location.href = 'bidding_history.php';
      }
    }, 250);
  }
}

function scheduleWinnerNotificationCheck() {
  if (winnerCheckScheduled) return;
  winnerCheckScheduled = true;
  setTimeout(checkWinnerNotification, 500);
}

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', () => {
    console.log('[WinnerNotify] DOM Content Loaded');
    scheduleWinnerNotificationCheck();
  }, { once: true });
} else {
  scheduleWinnerNotificationCheck();
}