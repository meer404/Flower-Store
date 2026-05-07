
## Bloom & Vine Flower Store

Bloom & Vine is a production-ready, bilingual (English and Kurdish) e-commerce platform for a flower shop. It includes a full customer experience, a permission-based admin panel, and a super admin control layer. This README is a complete, page-by-page and file-by-file reference for both developers and non-technical readers.

## Who This Is For

- Store owners and operators who want to understand the features.
- Developers and maintainers who need to work on every page, module, and flow.

## Core Features (Quick Overview)

- Bilingual interface (LTR English, RTL Kurdish).
- Secure authentication with Argon2ID password hashing.
- Cart, wishlist, reviews, and notifications.
- Order processing with delivery dates, extras, and payments.
- Admin management with granular permissions and audit logging.
- Super admin reporting, settings, and system controls.

## Tech Stack

- Backend: PHP 8.2+, MySQL 5.7+ or MariaDB 10.2+.
- Frontend: Tailwind CSS, HTML5, Vanilla JavaScript.
- Security: CSRF tokens, output escaping, prepared statements.

## Quick Start (Setup)

### Requirements

- PHP 8.2 or higher
- MySQL 5.7+ or MariaDB 10.2+
- Apache with mod_rewrite (or Nginx)
- File uploads enabled

### Installation

1. Place the project in your web root (XAMPP example):

```bash
cd /path/to/xampp/htdocs
```

2. Create the database:

```sql
CREATE DATABASE bloom_vine CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

3. Import the base schema:

```bash
mysql -u root -p bloom_vine < database/schema.sql
```

4. Run optional migrations:

```bash
php database/run_admin_permissions_migration.php
php database/run_delivery_date_migration.php
php database/run_payment_method_migration.php
```

5. Configure the database in `src/config/db.php`:

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'bloom_vine');
define('DB_USER', 'your_username');
define('DB_PASS', 'your_password');
```

6. Ensure `uploads/` is writable by the web server.

### Default Access

- Customer site: `http://localhost/flower-store/`
- Admin panel: `http://localhost/flower-store/admin/dashboard.php`

### Default Credentials

- Admin: `admin@bloomvine.com` / `admin123`
- Super admin: `superadmin@bloomvine.com` / `superadmin123`

Change these immediately after first login.

## High-Level System Flows

1. Browse and purchase: Home -> Shop -> Product -> Cart -> Checkout -> Payment -> Order
2. Admin product management: Login -> Products -> Add/Edit -> Publish -> Featured
3. Payment processing: Checkout -> FIB payment -> Webhook -> Order paid -> Notification
4. Permissions: Super admin assigns permissions -> Admin actions are enforced per permission
5. Language handling: `$_SESSION['lang']` drives all translations and layout direction

## Project Structure (Short)

```
admin/       Admin and super admin panels
database/    SQL schemas and migrations
src/         Core modules and shared UI
uploads/     Product images
*.php        Customer pages and action handlers
```

## Page-By-Page Documentation (Customer Site)

Each page includes its purpose, main actions, and important data or components used.

### index.php (Home)

- Purpose: Landing page and featured product showcase.
- Key features: hero section, featured products, category highlights.
- Data: pulls featured products and display stats (views, sales).

### shop.php (Shop Catalog)

- Purpose: Full product listing and filtering.
- Key features: category filter, price range, rating, search, and sorting.
- Data: paginated product query with filter parameters.

### product.php (Product Detail)

- Purpose: Detailed product view with image gallery and reviews.
- Key features: image gallery, add-to-cart, wishlist toggle, reviews display.
- Data: product details, variants, gallery images, reviews, and rating average.

### cart.php (Cart)

- Purpose: Review and update cart items before checkout.
- Key features: quantity update, remove item, calculate delivery tier, extras.
- Data: session cart (compound keys for variants) and totals calculation.

### cart_action.php (Cart Actions)

- Purpose: Handles cart changes via POST or GET actions.
- Actions: add, update quantity, remove, clear cart.
- Security: CSRF validation and safe redirect logic.

### checkout.php (Checkout)

- Purpose: Final order creation and payment selection.
- Key features: shipping address, delivery date, order extras, coupon support.
- Payments: FIB (online) and Cash on Delivery.
- Data: creates order, order items, and sends confirmation email.

### fib_payment.php (FIB Payment Page)

- Purpose: Dedicated payment UI for First Iraqi Bank integration.
- Key features: payment request, status polling, success/fail states.
- Data: uses payment ID and order ID for verification.

### fib_webhook.php (FIB Payment Webhook)

- Purpose: Receives payment callbacks from First Iraqi Bank.
- Actions: update order payment status, send email, create notification, clear cart.
- Security: validates input and expected order status before update.

### wishlist.php (Wishlist)

- Purpose: Shows saved products for logged-in users.
- Key features: remove item, add to cart.
- Data: user wishlist table joined to products.

### wishlist_action.php (Wishlist Actions)

- Purpose: Toggle wishlist via AJAX or form post.
- Actions: add or remove wishlist item.
- Security: login required, CSRF validation.

### review.php (Product Review)

- Purpose: Submit a review and rating for a product.
- Key features: 1 to 5 star rating, comment text.
- Rules: one review per user per product.

### account.php (My Account)

- Purpose: Customer dashboard for profile and orders.
- Key features: update profile, view order history, manage addresses.
- Data: user profile, past orders, and order status summary.

### order_details.php (Customer Order Details)

- Purpose: Full order breakdown for a single order.
- Key features: items, extras, delivery address, payment status.
- Data: order header, line items, and extras.

### notifications.php (Notifications)

- Purpose: User notifications and status updates.
- Key features: mark read/unread, view order and payment updates.
- Data: notification table ordered by latest.

### login.php (Login)

- Purpose: Authenticate users.
- Key features: email/password login, Google OAuth option.
- Data: session login, optional redirect target.

### register.php (Register)

- Purpose: Create a customer account.
- Key features: validation, Argon2ID hashing, auto-login.
- Rules: password length and email uniqueness enforcement.

### logout.php (Logout)

- Purpose: End session and return to home.
- Actions: session destroy, redirect.

### contact.php (Contact)

- Purpose: Contact form for customers.
- Key features: stores messages in database and optional auto-table creation.

### about.php / shipping.php / returns.php / terms.php / privacy.php

- Purpose: Static informational pages.
- Content source: `src/pages/en/` and `src/pages/ku/`.

### cookie.php (Cookie Notice)

- Purpose: Shows cookie policy and usage explanation.
- Content source: `src/pages/en/` and `src/pages/ku/`.

## Page-By-Page Documentation (Admin Panel)

All admin pages require an authenticated admin session and specific permissions.

### admin/dashboard.php

- Purpose: Admin overview dashboard.
- Key features: revenue stats, order counts, recent orders list, chart summary.

### admin/products.php

- Purpose: Product management list.
- Key features: edit, delete, view; prevents delete if linked to orders.

### admin/add_product.php

- Purpose: Create a new product.
- Key features: bilingual fields, variants, gallery images, featured flag.

### admin/edit_product.php

- Purpose: Update product data.
- Key features: edit product details, manage images and variants.

### admin/categories.php

- Purpose: Category CRUD.
- Key features: bilingual names, slug management.

### admin/expired_products.php

- Purpose: List expired products.
- Key features: show expired items and allow cleanup.

### admin/orders.php

- Purpose: Orders list and search.
- Key features: pagination, search, export (if permitted).

### admin/order_details.php

- Purpose: View and update order.
- Key features: update status, view delivery data, send notification email.

### admin/coupons.php

- Purpose: Manage coupon codes.
- Key features: type (percent/fixed), minimum order, usage limits, expiry.

### admin/contact_messages.php

- Purpose: Manage contact form submissions.
- Key features: reply by email, mark read, delete.

### admin/header.php / admin/sidebar.php / admin/footer.php

- Purpose: Shared admin layout components.
- Key features: navigation, permission-based menu display, consistent UI.

## Page-By-Page Documentation (Super Admin)

Super admin has full system control with all permissions.

### admin/super_admin_dashboard.php

- Purpose: High-level system analytics.
- Key features: total revenue, product counts, customer counts, top products.

### admin/super_admin_admins.php

- Purpose: Create and manage admin users.
- Key features: permission checkboxes, admin creation, updates, and removal.

### admin/super_admin_users.php

- Purpose: Customer management.
- Key features: search, status toggle, delete.

### admin/super_admin_reports.php

- Purpose: Reporting and analytics.
- Key features: sales summaries by period, profit and loss, exports.

### admin/super_admin_settings.php

- Purpose: System-wide settings.
- Key features: delivery fees, currency, exchange rates, email settings.

## Core Modules and Shared Components (src/)

### src/config/db.php

- Purpose: Database connection configuration.
- Used by: all pages via shared helpers.

### src/functions.php

- Purpose: Core helper functions and system utilities.
- Includes: sanitization, authentication, permission checks, CSRF, redirects,
  notifications, reporting helpers, flash messages, system settings.

### src/language.php

- Purpose: Language switch and RTL/LTR handling.
- Data: uses session variable `lang` to select translations.

### src/translations/en.php and src/translations/ku.php

- Purpose: Translation dictionaries for all UI text.

### src/components.php

- Purpose: Reusable UI components.
- Includes: buttons, cards, alerts, product cards, breadcrumbs, pagination.

### src/header.php

- Purpose: Customer-facing navigation bar.
- Features: language switcher, cart/wishlist badges, search, user menu.

### src/design_config.php

- Purpose: Design system configuration.
- Defines: fonts, colors, shadows, animation classes.

### src/email.php

- Purpose: Email sending utilities (PHPMailer and SMTP).
- Used for: order confirmations, status updates, contact replies.

### src/FibService.php

- Purpose: First Iraqi Bank payment integration.
- Provides: payment initiation, status checks, webhook handling.

### src/gmail_config.php

- Purpose: Gmail SMTP configuration for email delivery.

### src/pwa_head.php

- Purpose: Adds PWA meta tags and manifest links to page headers.

### src/pages/en/* and src/pages/ku/*

- Purpose: Static content for about, contact, cookie, privacy, returns, shipping, terms.

## Action Handlers and Integration Endpoints

### order_action.php

- Purpose: Admin-only order status update handler.
- Actions: validates permission, updates status, sends notifications.

### google_login.php

- Purpose: Starts Google OAuth login flow.
- Security: stores state and redirect target in session.

### google_callback.php

- Purpose: Completes Google OAuth.
- Actions: validates state, exchanges code, creates or logs in user.

### fib_payment.php and fib_webhook.php

- Purpose: Online payment with First Iraqi Bank.
- Actions: payment request, status checks, order update on webhook.

## PWA and Offline Support

- manifest.json: PWA metadata and app identity.
- service-worker.js: caching and offline handling.
- pwa.js: registers service worker and client-side PWA logic.
- offline.html: fallback offline page.

## Database Design (Summary)

Core tables include `users`, `admin_permissions`, `products`, `categories`,
`orders`, `order_items`, `product_images`, `reviews`, `wishlist`,
`notifications`, `activity_log`, and `system_settings`.

Schemas are stored in `database/` and include migration scripts for permissions,
delivery dates, payment methods, and notifications.

## Security and Data Protection

- Passwords are hashed with Argon2ID.
- All sensitive POST forms use CSRF tokens.
- Output is escaped with `htmlspecialchars` via helper functions.
- Database access uses PDO prepared statements.
- Permissions guard admin and super admin routes.

## Additional Documentation

See the documentation files in the repository for deeper guides:

- ADMIN_PERMISSIONS_INDEX.md
- ADMIN_PERMISSIONS_GUIDE.md
- ADMIN_PERMISSIONS_SETUP.md
- ADMIN_PERMISSIONS_QUICK_REF.md
- ADMIN_PERMISSIONS_UI_GUIDE.md
- FEATURES.md
- SECURITY.md
- DELIVERY_DATE_FEATURE.md
- PAYMENT_METHOD_FEATURE.md

## License

MIT License

Copyright (c) 2026 Bloom & Vine Flower Store

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
SOFTWARE.

## Author

Bloom & Vine Development Team

- GitHub: https://github.com/meer404
- Project: https://github.com/meer404/Flower-Store
- Contact: contact@mir.codes

Version: 2.0
Last Updated: May 7, 2026
Status: Production Ready
