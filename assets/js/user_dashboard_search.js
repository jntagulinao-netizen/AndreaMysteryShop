function handleSearch() {
    openSearchPage();
    const query = document.getElementById('mainSearch').value.trim();
    document.getElementById('searchPageInput').value = query;
    handleSearchPage();
}

function handleSearchPage() {
    const input = document.getElementById('searchPageInput');
    const query = input.value.trim();
    if (!query) {
        alert('Please enter a search query.');
        return;
    }
    addToSearchHistory(query);
    document.getElementById('mainSearch').value = query;
    renderSearchResults(query);
    setSearchSectionsVisibility(true);
}

async function fetchSearchHistory() {
    try {
        const res = await fetch('api/search-history.php', { cache: 'no-store' });
        if (!res.ok) throw new Error('Failed to fetch search history');
        const data = await res.json();
        searchHistoryCache = Array.isArray(data.history) ? data.history : [];
    } catch (err) {
        console.error('Error fetching search history:', err);
        searchHistoryCache = [];
    }
    return searchHistoryCache;
}

async function addToSearchHistory(query) {
    const cleaned = (query || '').trim().slice(0, 80);
    if (!cleaned) return;

    try {
        const body = new URLSearchParams({ term: cleaned });
        const res = await fetch('api/search-history.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: body.toString()
        });
        if (!res.ok) throw new Error('Failed to save search history');
        await fetchSearchHistory();
        renderSearchHistory();
        renderDropdownHistory();
    } catch (err) {
        console.error('Error saving search history:', err);
    }
}

function renderProductCard(product) {
    const reviewCount = Number(product.reviewCount || 0);
    const stock = Number(product.groupStock ?? product.stock ?? 0);
    const orderCount = Number(product.groupOrderCount ?? product.orderCount ?? 0);
    const cardData = {
        ...product,
        reviewCount,
        groupStock: stock,
        groupOrderCount: orderCount
    };

    const options = {
        isOutOfStock: stock <= 0,
        avgRating: reviewCount > 0 ? Number(product.rating || 0).toFixed(1) : '0.0',
        variantCount: Number(product.variantCount || 0),
        priceDisplay: `₱${formatPeso(product.price)}`,
        productImage: Array.isArray(product.image) ? (product.image[0] || '') : (product.image || '')
    };

    return DashboardReusableUI.renderProductCard(cardData, options);
}

function renderProductsGrid(containerId, productsToRender) {
    const grid = document.getElementById(containerId);
    if (!grid) return;
    grid.innerHTML = productsToRender.map(renderProductCard).join('');
}

function renderSearchResults(query) {
    const results = products.filter(p => p.name.toLowerCase().includes(query.toLowerCase()));
    const grid = document.getElementById('searchResultsGrid');
    const noResults = document.getElementById('searchNoResults');
    if (results.length) {
        grid.innerHTML = results.map(renderProductCard).join('');
        if (noResults) noResults.style.display = 'none';
    } else {
        if (grid) grid.innerHTML = '';
        if (noResults) noResults.style.display = 'block';
    }
}

function renderBestSellers() {
    const best = products
        .slice()
        .sort((a, b) => (b.rating || 0) - (a.rating || 0) || (b.price || 0) - (a.price || 0))
        .slice(0, 5);
    const grid = document.getElementById('searchBestSellers');
    grid.innerHTML = best.map(renderProductCard).join('');
}

async function renderSearchHistory() {
    const history = await fetchSearchHistory();
    const list = document.getElementById('searchHistoryList');
    const showMoreBtn = document.getElementById('showMoreHistory');
    if (!list) return;
    if (!history.length) {
        list.innerHTML = '<div class="history-item">No history yet</div>';
        if (showMoreBtn) showMoreBtn.style.display = 'none';
        return;
    }
    const displayHistory = history.slice(0, 6);
    list.innerHTML = displayHistory.map(item => `<div class="history-item" onclick="event.stopPropagation(); selectHistoryItem('${item.term.replace(/'/g, "\\'")}')">${item.term}</div>`).join('');
    if (showMoreBtn) {
        showMoreBtn.style.display = history.length > 6 ? 'block' : 'none';
    }
}

function getSearchHistory() {
    return searchHistoryCache;
}

async function clearSearchHistory() {
    try {
        const res = await fetch('api/search-history.php', { method: 'DELETE' });
        if (!res.ok) throw new Error('Failed to clear search history');
        searchHistoryCache = [];
        renderSearchHistory();
        renderDropdownHistory();
    } catch (err) {
        console.error('Error clearing search history:', err);
    }
}

function toggleShowMoreHistory() {
    const history = getSearchHistory();
    const displayHistory = history.slice(0, 6);
    const list = document.getElementById('searchHistoryList');
    const showMoreBtn = document.getElementById('showMoreHistory');
    const arrow = document.getElementById('showMoreArrow');
    if (!list || !showMoreBtn || !arrow) return;
    if (arrow.classList.contains('rotated')) {
        // Currently showing all, switch to showing 6
        list.innerHTML = displayHistory.map(item => `<div class="history-item" onclick="event.stopPropagation(); selectHistoryItem('${item.term.replace(/'/g, "\\'")}')">${item.term}</div>`).join('');
        arrow.classList.remove('rotated');
    } else {
        // Currently showing 6, switch to showing all
        list.innerHTML = history.map(item => `<div class="history-item" onclick="event.stopPropagation(); selectHistoryItem('${item.term.replace(/'/g, "\\'")}')">${item.term}</div>`).join('');
        arrow.classList.add('rotated');
    }
}

function renderSearchSuggestions(query) {
    const suggestionsEl = document.getElementById('searchSuggestions');
    if (!suggestionsEl) return;
    const cleaned = query.trim().toLowerCase();
    if (!cleaned) {
        suggestionsEl.style.display = 'none';
        suggestionsEl.innerHTML = '';
        return;
    }
    const matched = products
        .filter(p => {
            const normalizedName = (p.name || '').toLowerCase().trim();
            if (!normalizedName) return false;
            if (normalizedName.startsWith(cleaned)) return true;
            return normalizedName.split(/\s+/).some(word => word.startsWith(cleaned));
        })
        .slice(0, 8);
    if (!matched.length) {
        suggestionsEl.style.display = 'none';
        suggestionsEl.innerHTML = '';
        return;
    }
    suggestionsEl.style.display = 'block';
    suggestionsEl.innerHTML = matched.map(item => `
        <div class="search-suggestion-item" onclick="event.stopPropagation(); selectHistoryItem('${item.name.replace(/'/g, "\\'")}')">
            ${item.name} <small class="search-suggestion-price">₱${formatPeso(item.price)}</small>
        </div>
    `).join('');
}

function showSearchCloseButton() {
    const closeBtn = document.querySelector('.search-page .btn-close');
    if (closeBtn) closeBtn.style.display = 'block';
}

function openSearchPage() {
    const page = document.getElementById('searchPage');
    if (!page) return;
    closeSearchDropdown();
    page.style.display = 'block';
    document.body.style.overflow = 'hidden';
    const query = document.getElementById('mainSearch').value.trim();
    const searchPageInput = document.getElementById('searchPageInput');
    if (searchPageInput) searchPageInput.value = query;
    renderBestSellers();
    renderSearchHistory();
    if (query) {
        renderSearchResults(query);
        setSearchSectionsVisibility(true);
    } else {
        setSearchSectionsVisibility(false);
    }
    renderSearchSuggestions(query);
    if (searchPageInput) searchPageInput.focus();
    page.addEventListener('click', showSearchCloseButton);
}

function closeSearchPage() {
    const page = document.getElementById('searchPage');
    if (!page) return;
    page.style.display = 'none';
    document.body.style.overflow = '';
    document.getElementById('mainSearch').value = '';
    page.removeEventListener('click', showSearchCloseButton);
}

function selectHistoryItem(value) {
    console.log('selectHistoryItem called with:', value);

    // Step 1: Close dropdown
    closeSearchDropdown();

    // Step 2: Update main search input
    document.getElementById('mainSearch').value = value;

    // Step 3: Open search page
    const searchPage = document.getElementById('searchPage');
    searchPage.style.display = 'block';
    document.body.style.overflow = 'hidden';

    // Step 4: Update search input on search page
    const searchPageInput = document.getElementById('searchPageInput');
    searchPageInput.value = value;

    // Step 5: Add to search history
    addToSearchHistory(value);

    // Step 6: Wait a tiny bit then render results
    setTimeout(() => {
        console.log('About to render results. Products:', products.length);

        if (!products || products.length === 0) {
            console.log('No products loaded, loading them now...');
            loadProducts().then(() => {
                renderSearchResults(value);
                updateSearchPageDisplay();
            });
        } else {
            renderSearchResults(value);
            updateSearchPageDisplay();
        }
    }, 50);
}

function updateSearchPageDisplay() {
    setSearchSectionsVisibility(true);
}

function openSearchDropdown() {
    const dropdown = document.getElementById('searchDropdown');
    if (!dropdown) return;
    dropdown.style.display = 'block';
    renderBestSellers();
    renderSearchHistory();
}

function closeSearchDropdown() {
    const dropdown = document.getElementById('searchDropdown');
    if (!dropdown) return;
    dropdown.style.display = 'none';
    document.getElementById('dropdownArrow').classList.remove('rotated');
}

function toggleSearchDropdown() {
    const dropdown = document.getElementById('searchDropdown');
    const arrow = document.getElementById('dropdownArrow');
    if (!dropdown || !arrow) return;
    if (dropdown.style.display === 'block') {
        closeSearchDropdown();
    } else {
        dropdown.style.display = 'block';
        arrow.classList.add('rotated');
        renderDropdownBestSellers();
        renderDropdownHistory();
    }
}

function renderDropdownBestSellers() {
    const best = products
        .slice()
        .sort((a, b) => (b.rating || 0) - (a.rating || 0) || (b.price || 0) - (a.price || 0))
        .slice(0, 5);
    const container = document.getElementById('bestSellers');
    if (!container) return;
    container.innerHTML = best.map(p => `<div class="best-seller-item" onclick="event.stopPropagation(); selectHistoryItem('${p.name.replace(/'/g, "\\'")}'); closeSearchDropdown();">${p.name} <small>₱${formatPeso(p.price)}</small></div>`).join('');
}

async function renderDropdownHistory() {
    const history = await fetchSearchHistory();
    const list = document.getElementById('historyItems');
    if (!list) return;
    if (!history.length) {
        list.innerHTML = '<div class="history-item">No history yet</div>';
        return;
    }
    list.innerHTML = history.slice(0, 6).map(item => `<div class="history-item" onclick="event.stopPropagation(); selectHistoryItem('${item.term.replace(/'/g, "\\'")}'); closeSearchDropdown();">${item.term}</div>`).join('');
}
