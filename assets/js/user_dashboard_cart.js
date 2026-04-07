async function loadCart() {
    try {
        const res = await fetch('api/get-cart.php');
        if (!res.ok) throw new Error('Failed to load cart');
        const data = await res.json();
        console.log('Cart API Response:', data);
        cart = data.items || [];
        console.log('Cart Array:', cart);
        renderCart();
        updateCartBadge();
    } catch (err) {
        console.error('loadCart', err);
    }
}

function getFilteredCartItems() {
    return cartSearchQuery
        ? cart.filter(item => (item.name || '').toLowerCase().includes(cartSearchQuery.toLowerCase()))
        : cart;
}

function renderSidebarCart(cartItems) {
    cartItems.innerHTML = cart.length ? cart.map(item => `
        <div class="cart-item-card cart-item-card-compact">
            <div class="cart-item-row">
                <input type="checkbox" class="cart-item-checkbox cart-item-select" ${selectedItems.has(item.id) ? 'checked' : ''} onchange="toggleItemSelection(${item.id})">
                <img src="${resolveItemImage(item)}" alt="${item.name}" class="cart-item-clickable" onclick="openProductModal(${item.product_id})">
                <div class="cart-item-content cart-item-clickable" onclick="openProductModal(${item.product_id})">
                    <p class="cart-item-title">${item.name}</p>
                    <p class="cart-item-variant">Qty: ${item.quantity}</p>
                    <p class="cart-item-price">₱${formatPeso(item.price)}</p>
                </div>
            </div>
        </div>
    `).join('') : '<p class="cart-empty-text">Cart is empty</p>';

    const checkoutBtn = document.getElementById('checkoutBtn');
    if (checkoutBtn) {
        checkoutBtn.style.display = selectedItems.size > 0 ? 'block' : 'none';
    }

    const cartTotal = document.getElementById('cartTotal');
    if (cartTotal) {
        const selectedTotal = cart
            .filter(item => selectedItems.has(item.id))
            .reduce((sum, item) => sum + item.price * item.quantity, 0);
        cartTotal.textContent = selectedTotal.toFixed(2);
    }
}

function renderCartPage() {
    const emptyContainer = document.getElementById('emptyCartContainer');
    const cartGrid = document.getElementById('cartPageGrid');
    const cartPageTitle = document.getElementById('cartPageTitle');
    const cartPageItems = document.getElementById('cartPageItems');

    if (!emptyContainer || !cartGrid || !cartPageTitle || !cartPageItems) {
        console.log('Cart page elements not found (may be on dashboard)');
        return;
    }

    const filteredCart = getFilteredCartItems();

    if (cart.length === 0) {
        emptyContainer.style.display = 'block';
        cartGrid.style.display = 'none';
        return;
    }

    if (filteredCart.length === 0) {
        emptyContainer.style.display = 'block';
        cartGrid.style.display = 'none';
        emptyContainer.innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg><h2>No cart items match your search</h2><p class="cart-empty-search-text">Try another term or clear the filter.</p><button class="continue-shopping-btn" onclick="clearCartSearch()">Clear Search</button>';
        return;
    }

    emptyContainer.style.display = 'none';
    cartGrid.style.display = 'grid';

    const selectedCount = selectedItems.size;
    const displayCount = cartSearchQuery ? filteredCart.length : cart.length;
    cartPageTitle.textContent = 'Shopping Cart (' + displayCount + ' items' + (selectedCount > 0 ? ', ' + selectedCount + ' selected' : '') + ')';

    const selectAllBtn = selectedItems.size === cart.length && cart.length > 0
        ? '<button onclick="deselectAll()" class="cart-select-all-btn cart-select-all-btn-secondary">Deselect All</button>'
        : '<button onclick="selectAll()" class="cart-select-all-btn">Select All</button>';

    const byStore = filteredCart.reduce((acc, item) => {
        const sellerName = String(item.seller || '').trim();
        const storeKey = sellerName || 'Andrea Mystery Shop';
        if (!acc[storeKey]) acc[storeKey] = [];
        acc[storeKey].push(item);
        return acc;
    }, {});

    const cartItemsHTML = Object.entries(byStore).map(([storeKey, items]) => {
        const storeName = storeKey;
        const showDefaultStoreLogo = storeName.toLowerCase() === 'andrea mystery shop';
        return `
        <div class="cart-seller-section">
            <div class="seller-header">
                <div class="seller-identity">
                    ${showDefaultStoreLogo ? '<img src="logo-removebg-preview.png" alt="Andrea Mystery Shop" class="seller-logo">' : ''}
                    ${storeName ? `<span class="seller-name">${storeName}</span>` : ''}
                </div>
            </div>
            ${items.map(item => `
                <div class="cart-item-card ${selectedItems.has(item.id) ? 'selected' : ''}">
                    <input type="checkbox" class="cart-item-checkbox cart-item-select" ${selectedItems.has(item.id) ? 'checked' : ''} onchange="toggleItemSelection(${item.id})">
                    <img src="${resolveItemImage(item)}" alt="${item.name}" class="cart-item-clickable" onclick="openProductModal(${item.product_id})">
                    <div class="cart-item-content cart-item-clickable" onclick="openProductModal(${item.product_id})">
                        <p class="cart-item-title">${item.name}</p>
                        <p class="cart-item-variant">Variant: ${item.variant || 'Default'}</p>
                        <p class="cart-item-price">₱${formatPeso(item.price)}</p>
                        <div class="action-row">
                            <div class="quantity-area">
                                <button onclick="updateQuantity(${item.id}, -1)" ${item.quantity <= 1 ? 'disabled' : ''}>-</button>
                                <span>${item.quantity}</span>
                                <button onclick="updateQuantity(${item.id}, 1)">+</button>
                            </div>
                            <div class="remove-item" onclick="removeFromCart(${item.id})">Remove</div>
                        </div>
                    </div>
                    <div class="price-highlight">₱${formatPeso(item.price * item.quantity)}</div>
                </div>
            `).join('')}
        </div>
    `;
    }).join('');

    cartPageItems.innerHTML = '<div class="cart-select-bar"><strong>Select items to checkout:</strong>' + selectAllBtn + '</div>' + cartItemsHTML;

    const selectedItemsList = cart.filter(item => selectedItems.has(item.id));
    const subtotal = selectedItemsList.reduce((sum, item) => sum + item.price * item.quantity, 0);
    const shipping = 0;
    const total = subtotal + shipping;

    const summarySubtotal = document.getElementById('summarySubtotal');
    const summaryShipping = document.getElementById('summaryShipping');
    const summaryTotal = document.getElementById('summaryTotal');
    if (summarySubtotal) summarySubtotal.textContent = '₱' + formatPeso(subtotal);
    if (summaryShipping) summaryShipping.textContent = shipping === 0 ? 'FREE' : '₱' + formatPeso(shipping);
    if (summaryTotal) summaryTotal.textContent = '₱' + formatPeso(total);
}

function renderCart() {
    try {
        console.log('renderCart() called with', cart.length, 'items');
        const cartItems = document.getElementById('cartItems');
        if (!cartItems) {
            console.log('cartItems element not found (may be on cart page)');
            return;
        }

        renderSidebarCart(cartItems);
        renderCartPage();
    } catch (err) {
        console.error('renderCart() error:', err);
    }
}

function toggleItemSelection(itemId) {
    if (selectedItems.has(itemId)) {
        selectedItems.delete(itemId);
    } else {
        selectedItems.add(itemId);
    }
    renderCart();
    updateCheckoutButtonState();
}

function selectAll() {
    cart.forEach(item => selectedItems.add(item.id));
    renderCart();
    updateCheckoutButtonState();
}

function deselectAll() {
    selectedItems.clear();
    renderCart();
    updateCheckoutButtonState();
}

function updateCheckoutButtonState() {
    const placeOrderBtn = document.getElementById('placeOrderBtn');
    if (!placeOrderBtn) return;

    // Always keep button enabled - validation happens in handleCheckout
    placeOrderBtn.disabled = false;
    console.log('Place Order button state: always enabled. Selected items=' + selectedItems.size);
}

function toggleCart() {
    const sidebar = document.getElementById('cartSidebar');
    sidebar.classList.toggle('open');
}

function openCartPage() {
    document.getElementById('cartPage').classList.add('show');
    document.body.style.overflow = 'hidden';
    const topBar = document.querySelector('.top-action-bar');
    if (topBar) topBar.style.display = 'none';
}

function closeCartPage() {
    document.getElementById('cartPage').classList.remove('show');
    document.body.style.overflow = '';
    const topBar = document.querySelector('.top-action-bar');
    if (topBar) topBar.style.display = 'flex';
    document.getElementById('cartSearchInput').value = '';
    cartSearchQuery = '';
    renderCart();
}

function handleCartSearchInput(value) {
    cartSearchQuery = value.trim().toLowerCase();
    renderCart();
}

function toggleCartSearchInput() {
    const input = document.getElementById('cartSearchInput');
    if (!input) return;
    input.classList.toggle('hidden');
    if (!input.classList.contains('hidden')) {
        input.focus();
    } else {
        cartSearchQuery = '';
        input.value = '';
        renderCart();
    }
}

function clearCartSearch() {
    const input = document.getElementById('cartSearchInput');
    if (input) input.value = '';
    cartSearchQuery = '';
    renderCart();
}

function updateCartBadge() {
    const badge = document.getElementById('cartBadge');
    if (!badge) return;
    badge.textContent = cart.reduce((sum, item) => sum + item.quantity, 0);
}

async function updateQuantity(id, change) {
    const target = cart.find(i => i.id === id || i.cart_item_id === id);
    if (!target) return;
    const newQuantity = Math.max(0, target.quantity + change);
    try {
        const body = new URLSearchParams();
        body.append('cart_item_id', id);
        body.append('product_id', target.product_id || id);
        body.append('quantity', newQuantity);
        const res = await fetch('api/update-cart.php', { method: 'POST', body });
        if (!res.ok) throw new Error('Could not update quantity');
        await loadCart();
    } catch (err) {
        alert(err.message);
    }
}

async function removeFromCart(id) {
    try {
        const body = new URLSearchParams();
        body.append('cart_item_id', id);
        const item = cart.find(i => i.id === id || i.cart_item_id === id);
        if (item && item.product_id) body.append('product_id', item.product_id);
        const res = await fetch('api/remove-from-cart.php', { method: 'POST', body });
        if (!res.ok) throw new Error('Could not remove item');
        await loadCart();
    } catch (err) {
        alert(err.message);
    }
}

async function clearCartItems() {
    const totalCartItems = cart.length;
    const selectedCount = selectedItems.size;

    if (selectedCount === 0) {
        alert('Please select cart items to remove.');
        return;
    }

    const removeAllSelected = selectedCount === totalCartItems;
    const confirmMessage = removeAllSelected
        ? 'Remove all items from your cart?'
        : `Remove ${selectedCount} selected item${selectedCount > 1 ? 's' : ''} from your cart?`;

    if (!confirm(confirmMessage)) {
        return;
    }

    // Remove selected items only; if all selected, this removes all.
    const itemIdsToRemove = Array.from(selectedItems);
    for (const itemId of itemIdsToRemove) {
        await removeFromCart(itemId);
    }

    selectedItems.clear();
    await loadCart();
    updateCartBadge();
}
