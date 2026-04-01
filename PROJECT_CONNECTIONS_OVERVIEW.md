# AndreaMysteryShop Project Connections (Short Guide)

## 1) Big Picture
This is a PHP + MySQL app with server-rendered pages and JavaScript-driven interactions.

- Page files (root `.php`) render screens and enforce session/role access.
- API files (`api/*.php`) handle async actions and return JSON.
- Asset files (`assets/js`, `assets/css`) power frontend behavior and styling.
- Partials (`partials/...`) are reusable HTML blocks included by pages.
- `dbConnection.php` is the shared database connection used by APIs and some pages.

## 2) Request and Data Flow
Typical flow:

1. User opens a page (example: `user_dashboard.php`).
2. Page loads CSS/JS assets.
3. JS calls API endpoints with `fetch(...)`.
4. API validates session/input, reads/writes MySQL using `dbConnection.php`.
5. API returns JSON.
6. JS updates UI without full page reload.

## 3) Folder Roles and How They Connect

### Root pages (`*.php`)
- Examples: `user_dashboard.php`, `admin_dashboard.php`, `admin_my_products.php`, `messages.php`.
- Responsibilities:
  - Session/role checks.
  - Main layout and section containers.
  - Include or reference CSS, JS, and partials.

### API endpoints (`api/*.php`)
- Examples: `api/get-products.php`, `api/add-to-cart.php`, `api/messages-get-conversations.php`, `api/update-product-admin.php`.
- Responsibilities:
  - Parse request data (`$_GET`, `$_POST`, `$_FILES`).
  - Validate auth and business rules.
  - Run SQL operations.
  - Return JSON (`success`, payload, error message).

### Assets (`assets/js`, `assets/css`)
- JS examples: `assets/js/user_dashboard_app.js`, `assets/js/user_dashboard_cart.js`, `assets/js/category_products_custom_display.js`.
- CSS examples: `assets/css/user_dashboard_shared.css`, `assets/css/user_dashboard_cart.css`.
- Responsibilities:
  - JS: UI state, events, API calls, rendering.
  - CSS: page/feature-specific styling.

### Partials (`partials/user_dashboard/...`)
- Examples: `partials/user_dashboard/topbar_search.php`, catalog/cart fragments.
- Responsibilities:
  - Reuse repeated page sections.
  - Keep large pages maintainable.

### Database connection
- `dbConnection.php` creates `$conn` and sets charset.
- Included by API files (and some pages) for DB access.

## 4) Concrete Connection Examples

### Example A: Products list (User side)
- Page: `user_dashboard.php`
- JS: `assets/js/user_dashboard_app.js`
- API: `api/get-products.php`
- Flow:
  - Page loads JS.
  - JS fetches products from API.
  - API queries `products` and related tables.
  - JS renders product cards/modal content.

### Example B: Cart actions
- JS: `assets/js/user_dashboard_cart.js`
- APIs: `api/get-cart.php`, `api/add-to-cart.php`, `api/update-cart.php`, `api/remove-from-cart.php`
- Flow:
  - User clicks add/update/remove.
  - JS sends request to corresponding API.
  - API updates cart records.
  - JS refreshes cart view and badges.

### Example C: Messaging badges
- Page: `admin_dashboard.php` (and user dashboard top/bottom nav)
- API: `api/messages-get-conversations.php`
- Flow:
  - JS polls conversations.
  - Sums `unread_count`.
  - Updates desktop/mobile message badges.

### Example D: Admin product edit with variants/media
- Page: `admin_my_products.php`
- API: `api/update-product-admin.php`
- Flow:
  - Modal collects product, variants, images/video.
  - JS sends `FormData` including files and variant metadata.
  - API updates product rows, variant rows, media records, pin/delete rules.
  - Response returns updated media data for UI refresh.

## 5) Implementation Pattern You Can Reuse
When adding a new feature:

1. Add/extend API endpoint in `api/`.
2. Validate session and input early.
3. Use `dbConnection.php` and prepared statements.
4. Return consistent JSON shape (`success`, `error`, data).
5. Connect from JS via `fetch`.
6. Update page UI state after API success.
7. If UI block is reusable, move markup to `partials/`.
8. Add or extend scoped CSS/JS under `assets/`.

## 6) Quick Mental Model
- Root page = screen shell + access control.
- Partials = reusable HTML pieces.
- Assets JS = behavior + API communication.
- Assets CSS = visual layer.
- API = business logic + DB read/write.
- `dbConnection.php` = shared DB entry point.
