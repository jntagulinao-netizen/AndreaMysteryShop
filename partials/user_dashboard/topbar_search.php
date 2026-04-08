<!-- Unified top action bar (search + icons) -->
<div class="top-action-bar">
  <div class="search-wrap">
    <svg viewBox="0 0 24 24" class="search-icon" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="7"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
    <input id="mainSearch" type="text" placeholder="Search for products..." autocomplete="off" readonly onclick="openSearchPage()" />
    <svg class="dropdown-arrow" id="dropdownArrow" onclick="toggleSearchDropdown()" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6,9 12,15 18,9"></polyline></svg>
    <div class="search-dropdown hidden-inline" id="searchDropdown">
      <div class="dropdown-section">
        <div class="dropdown-title">Top Best Sellers</div>
        <div class="best-sellers" id="bestSellers"></div>
      </div>
      <div class="dropdown-section">
        <div class="dropdown-title">Search History <button class="clear-history" id="clearHistoryBtn">Clear</button></div>
        <div class="history-items" id="historyItems"></div>
      </div>
    </div>
  </div>
  <div class="action-icons">
    <a href="auction.php" class="live-auction-bubble hidden-inline" id="liveAuctionBubble" aria-label="Go to live auction">
      <img src="logo.jpg" alt="Live auction" class="live-auction-bubble-thumb" id="liveAuctionBubbleThumb" loading="lazy" decoding="async">
      <span class="live-auction-bubble-text">Live bidding</span>
    </a>
    <button class="icon-btn" onclick="openAuctions()" aria-label="Auctions">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14.5 5.5l4 4"></path><path d="M5.5 14.5l4 4"></path><path d="M4 20l6.5-6.5"></path><path d="M9.5 10.5l6-6 4 4-6 6"></path><path d="M12 7l5 5"></path><path d="M2 22h8"></path></svg>
    </button>
    <button class="icon-btn" onclick="openMessages()" aria-label="Messages">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/><line x1="8" y1="9" x2="16" y2="9"/><line x1="8" y1="13" x2="14" y2="13"/></svg>
      <span class="message-badge" id="messageBadge">0</span>
    </button>
    <button id="topCartBtn" class="icon-btn" onclick="openCartPage()" aria-label="Cart">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6z"/></svg>
      <span class="cart-badge" id="cartBadge">0</span>
    </button>
    <button class="icon-btn menu-btn" onclick="toggleMenuDropdown()" aria-label="Menu">
      <svg viewBox="0 0 24 24" fill="currentColor"><circle cx="12" cy="5" r="2"/><circle cx="12" cy="12" r="2"/><circle cx="12" cy="19" r="2"/></svg>
      <div class="menu-dropdown" id="menuDropdown">
        <div class="menu-dropdown-item" onclick="window.location.href='user_dashboard.php'">Home</div>
        <div class="menu-dropdown-item" onclick="window.location.href='category_products.php'">Categories</div>
        <div class="menu-dropdown-item" onclick="openAuctions()">Auctions</div>
        <div class="menu-dropdown-item" onclick="goToAccount()">Account</div>
        <div class="menu-dropdown-item" onclick="logoutUser()">Logout</div>
      </div>
    </button>
  </div>
</div>

<div class="search-page hidden-inline" id="searchPage">
  <div class="search-page-inner">
    <div class="search-page-header">
      <button class="btn-close hidden-inline" onclick="closeSearchPage()">×</button>
      <input id="searchPageInput" type="text" placeholder="Search products..." autocomplete="off" />
    </div>
    <div id="searchSuggestions" class="search-suggestions hidden-inline"></div>
    <div class="search-page-body">
      <div class="search-section">
        <div class="section-header">
          <h3>Search History</h3>
          <button class="clear-history" onclick="clearSearchHistory()">Clear</button>
        </div>
        <div id="searchHistoryList" class="history-grid"></div>
        <button id="showMoreHistory" class="hidden-inline" onclick="toggleShowMoreHistory()"><svg id="showMoreArrow" class="show-more-arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6,9 12,15 18,9"></polyline></svg></button>
      </div>
      <div class="search-section">
        <h3>Top Best Sellers</h3>
        <div class="products-grid" id="searchBestSellers"></div>
      </div>
      <div class="search-section">
        <h3>Search Results</h3>
        <div class="products-grid" id="searchResultsGrid"></div>
        <div id="searchNoResults" class="no-results hidden-inline">No products found</div>
      </div>
    </div>
  </div>
</div>
