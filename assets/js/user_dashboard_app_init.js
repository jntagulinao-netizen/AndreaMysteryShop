        document.addEventListener('DOMContentLoaded', () => {
            initializeProductModalLifecycle();
            initializeImageLightboxGestures();
            loadCategories();
            loadProducts();
            loadCart();
            loadMessageUnreadCount();
            initLiveAuctionBubbleDrag();
            loadLiveAuctionBubble();
            
            // Initialize Place Order button as enabled
            const placeOrderBtn = document.getElementById('placeOrderBtn');
            if (placeOrderBtn) {
                placeOrderBtn.disabled = false;
                console.log('Place Order button initialized and enabled');
            }

            const searchInput = document.getElementById('mainSearch');
            if (searchInput) {
                searchInput.addEventListener('click', openSearchPage);
            }

            const searchPageInput = document.getElementById('searchPageInput');
            if (searchPageInput) {
                searchPageInput.addEventListener('input', (event) => {
                    const value = event.target.value.trim();
                    if (value) {
                        document.getElementById('searchBestSellers').closest('.search-section').style.display = 'none';
                        document.getElementById('searchHistoryList').closest('.search-section').style.display = 'none';
                    } else {
                        document.getElementById('searchBestSellers').closest('.search-section').style.display = 'block';
                        document.getElementById('searchHistoryList').closest('.search-section').style.display = 'block';
                    }
                    renderSearchSuggestions(value);
                });

                searchPageInput.addEventListener('keydown', (event) => {
                    if (event.key === 'Enter') { event.preventDefault(); handleSearchPage(); }
                    if (event.key === 'Escape') { closeSearchPage(); }
                });
            }

            // ensure page shows in full mode and no backdrop behavior required
            document.getElementById('searchPage')?.classList.add('full-page-search');
            
            document.querySelectorAll('.filter-btn').forEach(btn=>btn.addEventListener('click', ()=>{ const cat = btn.dataset.category; if (!cat || cat === 'more') return; document.querySelectorAll('.filter-btn').forEach(b=>b.classList.remove('active')); btn.classList.add('active'); filteredProducts = cat==='all'? [...products] : products.filter(p=>p.category===cat); renderProducts(); }));

            window.setInterval(loadLiveAuctionBubble, 45000);
        });

        function handleGlobalClick(event) {
            const target = event.target;

            // Keep search page open while product modal is active.
            const productModalOpen = document.getElementById('productModal')?.classList.contains('show');
            if (!target.closest('.product-modal') && !productModalOpen) {
                if (!target.closest('.search-page') && !target.closest('.top-action-bar')) {
                    closeSearchPage();
                }
            }

            // Close more categories dropdown when clicking outside.
            const moreBtn = document.getElementById('moreCategoriesBtn');
            const moreContainer = document.getElementById('moreCategoriesContainer');
            if (moreBtn && moreContainer && moreContainer.classList.contains('active')) {
                if (!target.closest('#moreCategoriesBtn') && !target.closest('#moreCategoriesContainer')) {
                    moreContainer.classList.remove('active');
                    moreBtn.textContent = 'More Categories ▼';
                }
            }

            // Fallback delegated handler for cards rendered without inline onclick.
            // Skip cards that already have inline onclick to avoid double-opening.
            const card = target.closest('.product-card');
            if (card && !card.hasAttribute('onclick')) {
                const productId = parseInt(card.dataset.id, 10);
                if (Number.isFinite(productId)) {
                    openProductModal(productId);
                }
            }

            // Close checkout modal when clicking on its backdrop.
            if (target.classList && target.classList.contains('checkout-modal')) {
                target.classList.remove('show');
                document.body.style.overflow = '';
            }

            // Close menu dropdown when clicking outside.
            const menuBtn = document.querySelector('.menu-btn');
            const dropdown = document.getElementById('menuDropdown');
            if (dropdown && menuBtn && !menuBtn.contains(target) && !dropdown.contains(target)) {
                closeMenuDropdown();
            }
        }

        document.addEventListener('click', handleGlobalClick);

        document.addEventListener('keydown', (e) => {
            const lightboxOpen = document.getElementById('imageLightbox')?.classList.contains('show');
            if (lightboxOpen) {
                if (e.key === 'Escape') {
                    closeImageLightbox();
                    return;
                }
                if (e.key === 'ArrowLeft') {
                    navigateLightbox(-1);
                    return;
                }
                if (e.key === 'ArrowRight') {
                    navigateLightbox(1);
                    return;
                }
            }
            if (!document.getElementById('productModal').classList.contains('show')) return;
            if (e.key === 'ArrowLeft') navigateProduct(-1);
            if (e.key === 'ArrowRight') navigateProduct(1);
            if (e.key === 'Escape') closeProductModal();
        });

        // Menu dropdown functionality
        function toggleMenuDropdown() {
            const dropdown = document.getElementById('menuDropdown');
            dropdown.classList.toggle('active');
        }

        function closeMenuDropdown() {
            const dropdown = document.getElementById('menuDropdown');
            dropdown.classList.remove('active');
        }

        function goToAccount() {
            closeMenuDropdown();
            window.location.href = 'account.php';
        }

        function logoutUser() {
            closeMenuDropdown();
            window.location.href = 'logout.php';
        }

        function openMessages() {
            window.location.href = 'messages.php';
        }

        function openAuctions() {
            closeMenuDropdown();
            window.location.href = 'auction.php';
        }

        function showNotification(msg){ console.log(msg); }
