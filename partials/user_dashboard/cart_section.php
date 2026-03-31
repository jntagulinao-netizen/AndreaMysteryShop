<div class="cart-sidebar" id="cartSidebar">
  <div class="cart-header">
    Shopping Cart
    <span onclick="toggleCart()" class="cart-close-btn">×</span>
  </div>
  <div id="cartItems"></div>
  <div id="cartFooter">
    <div class="cart-total">
      <span>Total: ₱<span id="cartTotal">0</span></span>
    </div>
    <button class="checkout-btn hidden-inline" onclick="openCheckout()" id="checkoutBtn">Proceed to Checkout</button>
  </div>
</div>

<!-- Full Cart Page -->
<div class="cart-page" id="cartPage">
  <div class="cart-page-content">
    <div class="cart-page-header">
      <div class="cart-page-top-bar">
        <div class="cart-page-title" id="cartPageTitle">Cart</div>
        <input id="cartSearchInput" class="cart-search-input hidden" type="text" placeholder="Search cart items..." oninput="handleCartSearchInput(this.value)" />
        <div class="cart-page-actions">
          <button class="cart-icon-btn" onclick="toggleCartSearchInput();" aria-label="Show cart search">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="20" height="20"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
          </button>
          <button class="cart-icon-btn" onclick="clearCartItems();" aria-label="Remove selected items">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="20" height="20"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-2 14H7L5 6"/><path d="M10 11v6"/><path d="M14 11v6"/><path d="M9 6V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/></svg>
          </button>
        </div>
      </div>
      <span class="cart-page-close" onclick="closeCartPage()">×</span>
    </div>
    <div id="emptyCartContainer" class="empty-cart-msg">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>
      <h2>Your cart is empty</h2>
      <p>Start adding products to your cart!</p>
      <button class="continue-shopping-btn" onclick="closeCartPage()">Continue Shopping</button>
    </div>

    <div id="cartPageGrid" class="cart-grid hidden-inline">
      <div class="cart-items-list" id="cartPageItems"></div>
      <div class="cart-summary">
        <h2>Order Summary</h2>
        <div class="summary-row">
          <span>Subtotal:</span>
          <span id="summarySubtotal">₱0.00</span>
        </div>
        <div class="summary-row">
          <span>Shipping:</span>
          <span id="summaryShipping">FREE</span>
        </div>
        <div class="summary-total">
          <span>Total:</span>
          <span class="summary-total-price" id="summaryTotal">₱0.00</span>
        </div>
        <button class="checkout-btn" onclick="openCheckout(); closeCartPage();">Proceed to Checkout</button>
        <button class="continue-shopping-btn" onclick="closeCartPage()">Continue Shopping</button>
      </div>
    </div>
  </div>
</div>
