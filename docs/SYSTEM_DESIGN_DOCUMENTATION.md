# Andrea Mystery Shop - System Design Documentation

## 1. Scope

- System name: Andrea Mystery Shop
- Purpose: E-commerce platform with customer, admin, and owner-admin capabilities.
- In scope:
  - User browsing, cart, checkout, orders, reviews, favorites, messaging
  - Admin product management, order/review management, messaging
  - Owner-only administrative access via 4-digit PIN and OTP-based reset
- Out of scope:
  - Third-party analytics platform integration
  - Payment gateway internals not implemented in this codebase

## 2. Actors

1. Customer/User
2. Admin
3. Owner Admin
4. SMTP Email Service (PHPMailer + Gmail SMTP)
5. MySQL Database

## 3. Context Diagram (Text)

1. User/Admin/Owner -> Web UI (PHP pages)
2. Web UI -> API Endpoints (fetch/AJAX and form submissions)
3. API/Page Handlers -> MySQL (read/write)
4. OTP/Notification Handlers -> SMTP Service (email dispatch)
5. Media-enabled modules -> File Storage directories (product/review/message/profile media)

## 4. Module Dependencies (Text Diagram)

1. Presentation Layer (Pages, JS, CSS)
	- Depends on: Application Services, Session/Auth, Static Assets
2. Application Services Layer (api/*.php and page handlers)
	- Depends on: Domain Modules, Data Access, Session/Auth, Notification Service
3. Domain Modules (Auth, Catalog, Cart, Orders, Reviews, Messaging, Recipients, Admin Ops, Owner Security)
	- Depends on: Data Access, Session/Auth, File Storage, Notification Service
4. Data Access Layer (dbConnection.php + SQL)
	- Depends on: MySQL schema and constraints
5. Infrastructure Services (SMTP, media directories, shared assets)
	- Used by: Domain and Application Services
6. Security Plane (session, role checks, owner checks, OTP/PIN validation)
	- Cross-cutting dependency for all protected pages and APIs

Primary flow:

Presentation -> Application Services -> Domain Modules -> Data Access -> Database

Side flows:

- Application Services -> SMTP (OTP emails)
- Domain Modules -> File Storage (media)
- All protected entry points -> Security Plane checks

## 5. Module Inventory

### 5.1 Core Infrastructure

- dbConnection.php
- main.css
- PROJECT_CONNECTIONS_OVERVIEW.md

### 5.2 Authentication and Session

- LogIn.php
- login_process.php
- logout.php
- register_process.php

### 5.3 OTP and Account Recovery

- forgot_password.php
- send_reset_otp.php
- resend_otp.php
- verify_otp.php
- verify_otp_process.php
- reset_password.php
- reset_password_process.php

### 5.4 Owner PIN and Owner OTP Reset

- owner_admin_access.php
- owner_send_reset_otp.php
- owner_resend_reset_otp.php
- owner_new_pin.php
- owner_new_pin_process.php
- owner_administrative_page.php

### 5.5 User Account and Dashboard

- account.php
- user_dashboard.php

### 5.6 Product Catalog and Discovery

- homePage.php
- category_products.php
- api/get-products.php
- api/get-product.php
- api/get-categories.php

### 5.7 Cart, Checkout, and Orders

- api/get-cart.php
- api/add-to-cart.php
- api/update-cart.php
- api/remove-from-cart.php
- api/checkout.php
- api/cancel-order.php
- api/confirm-delivery.php
- purchase_history.php
- admin_orders.php

### 5.8 Reviews and Review Media

- api/add-review.php
- api/get-reviews.php
- api/get-review-media.php
- user_review_media/

### 5.9 Messaging and Conversations

- messages.php
- message_helpers.php
- api/messages-ensure-conversation.php
- api/messages-get-conversations.php
- api/messages-get-messages.php
- api/messages-send.php
- api/messages-mark-read.php
- api/messages-delete.php
- message_media/

### 5.10 Recipient and Address Management

- api/get-recipients.php
- api/add-recipient.php
- api/update-recipient.php
- api/remove-recipient.php
- api/ph-address.php
- api/ph-address-offline.json

### 5.11 Admin Product Management

- admin_my_products.php
- admin_add_product.php
- admin_product_drafts.php
- admin_profile.php
- api/add-product-admin.php
- api/update-product-admin.php
- api/save-product-draft.php
- api/get-product-drafts.php
- api/delete-product-draft.php
- api/toggle-product-archive.php
- api/toggle-product-feature.php
- product_media/

### 5.12 Engagement and Personalization

- favorites.php
- recent_views.php
- api/toggle-favorite.php
- api/track-recent-view.php
- api/search-history.php

### 5.13 Static Pages and Informational Content

- about.php
- contact.php
- privacy.php

### 5.14 Email/OTP Infrastructure and Templates

- phpmailer/
- send_otp.php
- send_reset_otp.php
- SMTP_CONFIG_README.md
- otp-verification-email-template-main/

### 5.15 Development and Diagnostics

- test-api.php
- debug_media.log

## 6. Data Ownership per Module

1. Authentication and Session
	- Owns session identity and role context
2. User Profile and Account
	- Owns user account-facing profile data
3. Owner PIN/OTP Security
	- Owns access code hash and reset state
	- Key attributes: reset_otp, reset_otp_expires, reset_otp_verified
4. Catalog
	- Owns products, categories, product retrieval models
5. Cart
	- Owns temporary user purchase intent and quantities
6. Orders
	- Owns checkout outputs and order lifecycle states
7. Reviews
	- Owns review content and review-media metadata
8. Messaging
	- Owns conversations, messages, read/unread state, media refs
9. Recipients
	- Owns shipping recipient records and address selections
10. Admin Product Ops
	- Owns admin-side product mutation and publication state
11. Media Storage
	- Owns physical media files and storage paths

## 7. Interface Contracts (High-Level)

### 7.1 UI -> API

- Inputs: JSON/FormData + authenticated session cookie
- Outputs: JSON with standard shape
  - success (boolean)
  - data (optional)
  - message or error (optional)

### 7.2 API -> DB

- Inputs: validated parameters from UI layer
- Outputs: query result sets or mutation status
- Rules:
  - Session/role validation first
  - Prepared statements for DB operations

### 7.3 API -> SMTP

- Inputs: recipient email, OTP value, email template content
- Outputs: send status (success/failure)

### 7.4 Owner Reset Flow Contract

1. owner_send_reset_otp.php sends OTP
2. verify_otp.php collects OTP
3. verify_otp_process.php validates OTP and enters owner-reset-verified state
4. owner_new_pin.php collects new PIN + confirmation
5. owner_new_pin_process.php persists hashed PIN and clears reset state
6. Redirects back to owner_admin_access.php with flash feedback

## 8. Security and Access Controls

1. Session checks on protected pages and APIs
2. Role checks for admin functionality
3. Owner checks for owner-only administrative access
4. OTP expiration enforcement
5. PIN stored as hash (never plaintext)
6. Server-side validation for all critical forms/endpoints

## 9. Key Risks and Mitigations

1. OTP replay/stale OTP
	- Mitigation: expiry + verified flag + state reset after success
2. Unauthorized admin/owner access
	- Mitigation: session + role + owner gating
3. Partial-failure state inconsistency
	- Mitigation: deterministic redirects + flash messaging + reset-state cleanup
4. Media storage growth
	- Mitigation: dedicated media directories and cleanup governance

## 10. Future Enhancements

1. Rate limiting for OTP send/resend endpoints
2. Audit logging for owner/admin sensitive actions
3. Standardized API response/error codes
4. Stronger tokenized recovery flow to reduce session coupling

