<div class="checkout-modal" id="checkoutModal">
  <div class="checkout-container">
    <!-- Header with Back Arrow -->
    <div class="checkout-header">
      <span class="back-arrow" onclick="closeCheckout()">←</span>
      <h1>Checkout</h1>
    </div>

    <!-- Scrollable Content -->
    <div class="checkout-form">
      <form id="checkoutForm" class="checkout-form-content">
        <!-- Recipient Section -->
        <div class="form-section">
          <h2 class="section-title">📍 Shipping Information</h2>

          <!-- Existing Recipients List -->
          <div id="recipientsList" class="recipients-list recipient-initial-hidden">
            <label>Select Recipient</label>
            <div id="recipientsContainer" class="recipients-container"></div>
          </div>

          <!-- New Recipient Form -->
          <div id="newRecipientForm" class="new-recipient-form recipient-initial-hidden">
            <div class="form-group">
              <label for="recipientName">Full Name *</label>
              <input type="text" id="recipientName" name="recipientName" placeholder="John Doe" required>
            </div>
            <div class="form-group">
              <label for="phoneNo">Phone Number *</label>
              <input type="tel" id="phoneNo" name="phoneNo" placeholder="+63 9123456789" required>
            </div>
            <div class="form-group">
              <label for="streetName">Street Name/Number *</label>
              <input type="text" id="streetName" name="streetName" placeholder="123 Main Street" required>
            </div>
            <div class="form-group">
              <label for="unitFloor">Unit/Floor/Building</label>
              <input type="text" id="unitFloor" name="unitFloor" placeholder="Apt 4B, Unit 10">
            </div>
            <div class="form-row">
              <div class="form-group">
                <label for="region">Region *</label>
                <select id="region" name="region" required>
                  <option value="">Select Region</option>
                </select>
              </div>
              <div class="form-group">
                <label for="province">Province *</label>
                <select id="province" name="province" required disabled>
                  <option value="">Select Province</option>
                </select>
              </div>
            </div>
            <div class="form-group">
              <label for="city">City / Municipality *</label>
              <select id="city" name="city" required disabled>
                <option value="">Select City / Municipality</option>
              </select>
            </div>
            <div class="form-group">
              <label for="district">Barangay *</label>
              <select id="district" name="district" required disabled>
                <option value="">Select Barangay</option>
              </select>
            </div>
            <div class="form-group recipient-default-group">
              <label class="recipient-default-label">
                <input type="checkbox" id="setAsDefault" name="setAsDefault" class="recipient-default-checkbox">
                <span>Set as default recipient</span>
              </label>
            </div>
            <div class="recipient-form-actions">
              <button type="button" class="save-new-recipient-btn" onclick="saveNewRecipient()">Save Recipient</button>
              <button type="button" class="cancel-new-btn" onclick="handleCancelRecipient()">Cancel</button>
            </div>
          </div>
        </div>

        <!-- Order Items Section -->
        <div class="checkout-order-items-wrap">
          <h2 class="section-title checkout-order-items-title"><img src="logo.jpg" alt="Logo" class="checkout-order-items-logo">Order Items</h2>
          <div class="order-items" id="checkoutCartItems">
            <!-- Cart items will be populated here -->
          </div>
        </div>

        <!-- Shipping Option Section -->
        <div class="form-section">
          <h2 class="section-title">🚚 Shipping Option</h2>
          <label class="payment-option">
            <input type="radio" name="shippingMethod" value="standard" checked>
            <div class="payment-option-content">
              <span>Standard Local Shipping</span>
              <span class="shipping-free-label">FREE</span>
            </div>
          </label>
        </div>

        <!-- Payment Method Section -->
        <div class="form-section">
          <h2 class="section-title">💳 Payment Method</h2>

          <div class="payment-options">
            <label class="payment-option">
              <input type="radio" name="paymentMethod" value="cash" checked>
              <div class="payment-option-content">
                <span class="payment-icon">💵</span>
                <span class="payment-label">Cash on Delivery</span>
              </div>
            </label>

            <label class="payment-option">
              <input type="radio" name="paymentMethod" value="gcash">
              <div class="payment-option-content">
                <span class="payment-icon">📱</span>
                <span class="payment-label">GCash</span>
              </div>
            </label>
          </div>

          <!-- GCash Details (shown when selected) -->
          <div id="gcashDetails" class="payment-details hidden-inline">
            <p class="payment-info">For GCash payment, our team will send you instructions via SMS/Email after order confirmation.</p>
            <p class="payment-info payment-info-muted">Reference number will be provided with your order.</p>
          </div>
        </div>

        <!-- Order Summary Section -->
        <div class="checkout-summary">
          <h2 class="summary-title">Order Summary</h2>

          <div class="summary-divider"></div>

          <div class="price-breakdown">
            <div class="price-row">
              <span>Subtotal:</span>
              <span id="checkoutSubtotal">₱0.00</span>
            </div>
            <div class="price-row">
              <span>Shipping:</span>
              <span id="checkoutShipping">FREE</span>
            </div>
            <div class="price-row total">
              <span>Total:</span>
              <span id="checkoutTotal">₱0.00</span>
            </div>
          </div>

          <p class="order-terms">By placing your order, you agree to our Terms & Conditions</p>
        </div>
      </form>
    </div>

    <!-- Sticky Footer with Buttons -->
    <div class="form-actions">
      <button type="button" class="cancel-btn" onclick="closeCheckout()">Cancel</button>
      <button type="button" class="place-order-btn" id="placeOrderBtn" onclick="handleCheckoutClick()">
        <span class="btn-icon">✓</span>
        Place Order
      </button>
    </div>
  </div>
</div>

<!-- Success Modal -->
<div class="success-modal" id="successModal">
  <div class="success-modal-content">
    <div class="success-icon">✓</div>
    <h2>Order Placed Successfully!</h2>
    <p>Your order has been confirmed</p>
    <div class="order-id-display">Order #<span id="successOrderId">0</span></div>
    <p class="success-note-text">You will receive a confirmation email shortly</p>
    <button class="success-modal-btn" onclick="closeSuccessModal()">Continue Shopping</button>
  </div>
</div>
