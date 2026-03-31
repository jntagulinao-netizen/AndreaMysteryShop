<main>
    <div class="filters">
        <div id="categoryButtons" class="category-buttons-inline">
            <button class="filter-btn active" data-category="all" onclick="filterByCategory('all')">All</button>
        </div>
        <button id="moreCategoriesBtn" class="filter-btn more-cats-btn hidden-inline">More Categories ▼</button>
        <div id="moreCategoriesContainer" class="more-categories-dropdown"></div>
        <select class="sort-select" onchange="sortProducts(this.value)">
            <option value="">Sort</option>
            <option value="price-low">Price Low-High</option>
            <option value="price-high">Price High-Low</option>
            <option value="rating">Rating</option>
        </select>
    </div>
    <div class="products-grid" id="productsGrid"></div>
</main>
