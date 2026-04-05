<div class="product-modal" id="productModal">
    <div class="product-detail">
        <span class="close-product" onclick="closeProductModal()">&times;</span>
        <div id="mediaSection" class="detail-section"></div>
        <div class="product-gallery" id="productGallery"></div>
        <div class="product-main-info">
            <div class="product-title-row">
                <h1 id="productTitle" class="product-title"></h1>
                <button type="button" id="favoriteBtn" class="favorite-btn" onclick="toggleFavoriteFromDetail(this)" aria-label="Add to favorites" title="Add to favorites">
                    <span class="favorite-btn-icon" aria-hidden="true">♡</span>
                    <span class="favorite-btn-text">Favorite</span>
                </button>
            </div>
            <div id="productRating" class="product-rating"></div>
            <div class="detail-tabs">
                <div class="detail-tab active" data-target="mediaTab" onclick="switchDetailTab('mediaTab')">Media</div>
                <div class="detail-tab" data-target="descTab" onclick="switchDetailTab('descTab')">Description</div>
                <div class="detail-tab" data-target="reviewsTab" onclick="switchDetailTab('reviewsTab')">Reviews</div>
                <div class="detail-tab" data-target="recTab" onclick="switchDetailTab('recTab')">Recommendations</div>
            </div>
            <div id="descTab" class="detail-section">
                <div class="product-price-section">
                    <span id="currentPrice" class="current-price"></span>
                    <span id="originalPrice" class="original-price hidden-inline"></span>
                    <span id="discount" class="discount hidden-inline"></span>
                </div>
                <div id="productDesc" class="product-desc"></div>
                <div id="variantsSection" class="variants-section hidden-inline">
                    <div class="variants-section-title">Available Options</div>
                    <div id="variantsList" class="variant-options"></div>
                </div>
            </div>

            <div id="reviewsTab" class="detail-section">
                <div class="reviews-section">
                    <div class="reviews-header">
                        <div>
                            <span class="reviews-count" id="reviewsCount"></span>
                            <span class="avg-rating" id="avgRating"></span>
                        </div>
                    </div>
                    <div class="review-list" id="reviewList"></div>
                    <div class="add-review-form hidden-inline" id="reviewForm">
                        <div class="stars-input" id="starsInput"></div>
                        <textarea class="review-input" id="reviewText" placeholder="Share your thoughts..."></textarea>
                        <input type="file" id="reviewImage" class="review-image-input" accept="image/*,video/*" multiple>
                        <div id="reviewMediaSummary" class="review-media-summary">No files selected</div>
                        <button class="submit-review-btn" onclick="submitReview()">Post Review</button>
                    </div>
                </div>
            </div>
            <div id="recTab" class="detail-section">
                <h3 class="recommendation-heading">Recommendation</h3>
                <div class="review-list" id="recommendationList"></div>
            </div>
        </div>
        <div class="detail-action-bar">
            <button class="action-btn buy-now" onclick="buyNow()">Buy Now</button>
            <button class="action-btn add-cart" onclick="addToCartFromDetail(currentProductId, this)">Add to Cart</button>
        </div>
    </div>
</div>

<div class="image-lightbox" id="imageLightbox" onclick="closeImageLightbox(event)">
    <span class="image-lightbox-close" onclick="closeImageLightbox(event)">&times;</span>
    <img id="lightboxImage" src="" alt="Full size product image">
</div>
