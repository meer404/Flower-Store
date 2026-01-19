## Bloom & Vine Flower Store

A production-ready, bilingual e-commerce platform for a flower shop built with PHP 8.2+, MySQL, Tailwind CSS, and JavaScript. Features comprehensive admin management with granular permissions, dual-language support (English/Kurdish), and modern security practices.

## Project Description

Bloom & Vine is a full-featured online flower store designed to serve customers in both English and Kurdish (Sorani) languages. The platform provides a complete e-commerce solution with customer-facing shopping features, comprehensive order management, and a powerful admin panel with role-based access control.

**Target Users:**
- Customers seeking to purchase flowers for various occasions
- Store administrators managing products, orders, and inventory
- Super administrators overseeing system settings and user management

**Main Goals:**
- Provide seamless bilingual shopping experience with RTL support for Kurdish
- Enable efficient product and order management for administrators
- Ensure secure transactions and data protection
- Support granular permission-based access control for admin users

## Features

### Core Features
- **Dual Language Support**: Full English (LTR) and Kurdish/Sorani (RTL) translation system
- **Secure Authentication**: ARGON2ID password hashing with session management
- **Shopping Cart**: Session-based cart with real-time updates and quantity management
- **Wishlist System**: Save favorite products for later purchase
- **Product Reviews**: 5-star rating system with customer comments
- **Responsive Design**: Mobile-first approach with Tailwind CSS
- **Notification System**: Real-time notifications for order updates and system events

### Customer Features
- Browse products with category filters and search functionality
- Detailed product pages with image galleries and customer reviews
- Add products to cart or wishlist (requires login)
- Write and view product reviews with star ratings
- Complete checkout process with shipping address management
- View order history and track order status
- Manage user profile and account settings
- Receive notifications for order status changes

### Admin Features
- **Dashboard**: Sales statistics, order counts, and recent orders overview
- **Product Management**: Add, edit, delete products with multi-image gallery support
- **Category Management**: Create and manage product categories with bilingual names
- **Order Management**: View all orders, update status, add tracking numbers
- **Stock Management**: Track inventory levels and SKU codes
- **Featured Products**: Highlight specific products on the homepage
- **Pagination**: Efficient browsing of large datasets

### Super Admin Features
- **Admin Management**: Create and manage admin users with custom permissions
- **User Management**: View and manage customer accounts
- **System Settings**: Configure site-wide settings and preferences
- **Reports**: Access sales reports and analytics
- **Activity Logging**: Track all administrative actions
- **Permission System**: Granular control over admin capabilities

### System Features
- **Granular Permissions**: 9 distinct permission types for fine-grained access control
- **Activity Logging**: Comprehensive audit trail of all system actions
- **Notification System**: Automated notifications for order updates
- **Image Gallery**: Multiple images per product with thumbnail navigation
- **View Tracking**: Monitor product view counts
- **Sales Tracking**: Track sales count per product

## Technologies Used

### Backend
- **PHP**: Version 8.2+ with strict typing
- **Database**: MySQL 5.7+ / MariaDB 10.2+
- **PDO**: Prepared statements for SQL injection prevention
- **Session Management**: Secure session handling with CSRF protection

### Frontend
- **HTML5**: Semantic markup
- **CSS**: Tailwind CSS framework
- **JavaScript**: Vanilla JavaScript for interactivity
- **Responsive Design**: Mobile-first approach

### Security
- **Password Hashing**: ARGON2ID algorithm
- **CSRF Protection**: Token-based form protection
- **XSS Prevention**: Output sanitization with htmlspecialchars()
- **SQL Injection Prevention**: PDO with named parameters
- **File Upload Validation**: Type and size checks

### Database
- **Character Set**: UTF8MB4 for Kurdish/Arabic script support
- **Collation**: utf8mb4_unicode_ci
- **Storage Engine**: InnoDB with foreign key constraints

## Project Structure

```
flower-store/
├── admin/                          # Admin panel files
│   ├── dashboard.php              # Admin dashboard with statistics
│   ├── products.php               # Product management list
│   ├── add_product.php            # Add new products
│   ├── edit_product.php           # Edit existing products
│   ├── categories.php             # Category management
│   ├── orders.php                 # Order management list
│   ├── order_details.php          # Detailed order view
│   ├── super_admin_dashboard.php  # Super admin dashboard
│   ├── super_admin_admins.php     # Admin user management
│   ├── super_admin_users.php      # Customer user management
│   ├── super_admin_reports.php    # Sales reports and analytics
│   ├── super_admin_settings.php   # System settings
│   ├── header.php                 # Admin header component
│   ├── sidebar.php                # Admin sidebar navigation
│   └── footer.php                 # Admin footer component
│
├── database/                       # Database schemas and migrations
│   ├── schema.sql                 # Base database schema
│   ├── schema_extended.sql        # Extended features schema
│   ├── add_admin_permissions.sql  # Admin permissions table
│   ├── add_delivery_date.sql      # Delivery date feature
│   ├── add_payment_method.sql     # Payment method feature
│   ├── notifications_schema.sql   # Notifications system
│   ├── super_admin_migration.sql  # Super admin setup
│   ├── run_admin_permissions_migration.php
│   ├── run_delivery_date_migration.php
│   └── run_payment_method_migration.php
│
├── src/                            # Core application files
│   ├── config/
│   │   └── db.php                 # Database connection configuration
│   ├── translations/
│   │   ├── en.php                 # English translations
│   │   └── ku.php                 # Kurdish translations
│   ├── functions.php              # Helper functions and utilities
│   ├── language.php               # Language handler
│   ├── header.php                 # Customer-facing header
│   ├── components.php             # Reusable UI components
│   └── design_config.php          # Design system configuration
│
├── uploads/                        # Product images directory
│   └── .htaccess                  # Upload directory protection
│
├── index.php                       # Homepage
├── shop.php                        # Product catalog with filters
├── product.php                     # Product detail page
├── cart.php                        # Shopping cart
├── cart_action.php                 # Cart operations handler
├── checkout.php                    # Checkout process
├── wishlist.php                    # User wishlist
├── wishlist_action.php             # Wishlist operations handler
├── account.php                     # User account dashboard
├── order_details.php               # Customer order details
├── notifications.php               # User notifications
├── order_action.php                # Order operations handler
├── review.php                      # Product review submission
├── login.php                       # Login page
├── register.php                    # Registration page
├── logout.php                      # Logout handler
├── .htaccess                       # Apache configuration
└── README.md                       # This file
```

### Folder Explanations

- **admin/**: Contains all administrative interface files with permission-based access control
- **database/**: SQL schemas and migration scripts for database setup and updates
- **src/**: Core application logic, configuration, translations, and shared components
- **uploads/**: Storage directory for product images with security protection
- **Root files**: Customer-facing pages for shopping, authentication, and account management

### Complete File Tree

```
flower-store/
│
├── 📁 admin/                                    (15 files)
│   ├── add_product.php                         # Add new products with images
│   ├── categories.php                          # Category CRUD operations
│   ├── dashboard.php                           # Admin dashboard & statistics
│   ├── edit_product.php                        # Edit products & galleries
│   ├── footer.php                              # Admin footer component
│   ├── header.php                              # Admin header component
│   ├── order_details.php                       # Admin order details view
│   ├── orders.php                              # Order management list
│   ├── products.php                            # Product management list
│   ├── sidebar.php                             # Admin sidebar navigation
│   ├── super_admin_admins.php                  # Admin user management
│   ├── super_admin_dashboard.php               # Super admin dashboard
│   ├── super_admin_reports.php                 # Sales reports & analytics
│   ├── super_admin_settings.php                # System settings management
│   └── super_admin_users.php                   # Customer user management
│
├── 📁 database/                                 (11 files)
│   ├── add_admin_permissions.sql               # Admin permissions table
│   ├── add_delivery_date.sql                   # Delivery date migration
│   ├── add_payment_method.sql                  # Payment method migration
│   ├── fix_superadmin_password.sql             # Password fix script
│   ├── notifications_schema.sql                # Notifications table
│   ├── run_admin_permissions_migration.php     # Run permissions migration
│   ├── run_delivery_date_migration.php         # Run delivery migration
│   ├── run_payment_method_migration.php        # Run payment migration
│   ├── schema.sql                              # Base database schema
│   ├── schema_extended.sql                     # Extended features schema
│   └── super_admin_migration.sql               # Super admin setup
│
├── 📁 src/                                      (7 files + 2 subdirs)
│   ├── 📁 config/
│   │   └── db.php                              # Database connection config
│   │
│   ├── 📁 translations/
│   │   ├── en.php                              # English translations
│   │   └── ku.php                              # Kurdish translations
│   │
│   ├── components.php                          # Reusable UI components
│   ├── design_config.php                       # Design system config
│   ├── functions.php                           # Helper functions
│   ├── header.php                              # Customer header component
│   └── language.php                            # Language handler
│
├── 📁 uploads/                                  (Product images)
│   └── .htaccess                               # Upload security config
│
├── 📄 Customer Pages (13 files)
│   ├── account.php                             # User account dashboard
│   ├── cart.php                                # Shopping cart page
│   ├── cart_action.php                         # Cart operations handler
│   ├── checkout.php                            # Checkout process
│   ├── index.php                               # Homepage
│   ├── login.php                               # Login page
│   ├── logout.php                              # Logout handler
│   ├── notifications.php                       # User notifications
│   ├── order_action.php                        # Order operations handler
│   ├── order_details.php                       # Customer order details
│   ├── product.php                             # Product detail page
│   ├── register.php                            # Registration page
│   ├── review.php                              # Product review submission
│   ├── shop.php                                # Product catalog with filters
│   ├── wishlist.php                            # User wishlist
│   └── wishlist_action.php                     # Wishlist operations handler
│
├── 📄 Configuration Files
│   ├── .htaccess                               # Apache configuration
│   └── fix_superadmin_password.php             # Password reset utility
│
└── 📄 Documentation Files (30+ files)
    ├── README.md                               # Original README
    ├── READMEV2.md                             # This comprehensive README
    ├── FEATURES.md                             # Detailed features documentation
    ├── ADMIN_PERMISSIONS_INDEX.md              # Permissions documentation index
    ├── ADMIN_PERMISSIONS_GUIDE.md              # Complete permissions guide
    ├── ADMIN_PERMISSIONS_SETUP.md              # Quick setup guide
    ├── ADMIN_PERMISSIONS_QUICK_REF.md          # Quick reference card
    ├── ADMIN_PERMISSIONS_UI_GUIDE.md           # Visual UI guide
    ├── ADMIN_PERMISSIONS_COMPLETE.md           # Full implementation summary
    ├── PERMISSION_SYSTEM_READY.md              # Permission system overview
    └── ... (additional documentation files)

Total Files: 60+ PHP files, 11 SQL files, 30+ documentation files
```

## Installation & Setup

### Requirements

- PHP 8.2 or higher
- MySQL 5.7+ or MariaDB 10.2+
- Apache web server with mod_rewrite enabled (or Nginx)
- Minimum 128MB PHP memory limit
- File upload support enabled in PHP

### Step 1: Clone the Project

```bash
# Clone or download the project to your web server directory
cd /path/to/xampp/htdocs
# Extract or clone the project
```

### Step 2: Database Setup

1. Create a new MySQL database:

```sql
CREATE DATABASE bloom_vine CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

2. Import the base schema:

```bash
mysql -u root -p bloom_vine < database/schema.sql
```

Or use phpMyAdmin to import `database/schema.sql`.

3. Run additional migrations (optional but recommended):

```bash
# For admin permissions system
php database/run_admin_permissions_migration.php

# For delivery date feature
php database/run_delivery_date_migration.php

# For payment method feature
php database/run_payment_method_migration.php
```

### Step 3: Configure Database Connection

Edit `src/config/db.php` and update the database credentials:

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'bloom_vine');
define('DB_USER', 'your_username');
define('DB_PASS', 'your_password');
```

### Step 4: Set File Permissions

Ensure the `uploads/` directory is writable:

**Linux/Mac:**
```bash
chmod 755 uploads/
```

**Windows:**
Right-click the `uploads` folder → Properties → Security → Edit → Add write permissions for the web server user.

### Step 5: Configure Web Server

**Apache (XAMPP/WAMP):**
- The `.htaccess` file is already configured
- Ensure `mod_rewrite` is enabled in Apache configuration
- Restart Apache

**Nginx:**
Configure your server block to point to the project root directory.

### Step 6: Access the Application

- **Customer Site**: `http://localhost/flower-store/`
- **Admin Panel**: `http://localhost/flower-store/admin/dashboard.php`

### Default Credentials

**Regular Admin:**
- Email: `admin@bloomvine.com`
- Password: `admin123`

**Super Admin:**
- Email: `superadmin@bloomvine.com`
- Password: `superadmin123`

**IMPORTANT**: Change these credentials immediately after first login!

## Database Design

### Main Tables and Relationships

#### users
Stores all user accounts (customers, admins, super admins).
- **Fields**: id, full_name, email, password_hash, phone, address, city, postal_code, country, role, created_at
- **Roles**: customer, admin, super_admin
- **Purpose**: Central authentication and user management

#### admin_permissions
Stores granular permissions for admin users.
- **Fields**: id, admin_id, permission, created_at
- **Relationship**: Foreign key to users table
- **Purpose**: Fine-grained access control for admin features

#### categories
Product categories with bilingual names.
- **Fields**: id, name_en, name_ku, slug, created_at
- **Purpose**: Organize products into categories

#### products
Complete product information with bilingual content.
- **Fields**: id, sku, category_id, name_en, name_ku, description_en, description_ku, price, stock_qty, weight, dimensions, image_url, is_featured, views, sales_count, created_at, updated_at
- **Relationship**: Foreign key to categories
- **Purpose**: Store all product data

#### product_images
Multiple images per product for gallery display.
- **Fields**: id, product_id, image_url, display_order, created_at
- **Relationship**: Foreign key to products
- **Purpose**: Support image galleries

#### orders
Customer orders with payment and shipping information.
- **Fields**: id, user_id, grand_total, payment_status, order_status, shipping_address, delivery_date, payment_method, card_last_four, cardholder_name, card_expiry_month, card_expiry_year, tracking_number, order_date
- **Relationship**: Foreign key to users
- **Purpose**: Track customer orders

#### order_items
Individual products within each order.
- **Fields**: id, order_id, product_id, quantity, unit_price
- **Relationships**: Foreign keys to orders and products
- **Purpose**: Store order line items

#### wishlist
Customer saved products for later.
- **Fields**: id, user_id, product_id, created_at
- **Relationships**: Foreign keys to users and products
- **Purpose**: Wishlist functionality

#### reviews
Product reviews and ratings from customers.
- **Fields**: id, product_id, user_id, rating, comment, created_at, updated_at
- **Relationships**: Foreign keys to products and users
- **Purpose**: Customer feedback system

#### notifications
System notifications for users.
- **Fields**: id, user_id, order_id, type, title, message, is_read, created_at
- **Relationships**: Foreign keys to users and orders
- **Purpose**: User notification system

#### system_settings
Configurable system-wide settings.
- **Fields**: id, setting_key, setting_value, setting_type, description, updated_at, updated_by
- **Purpose**: Store configuration values

#### activity_log
Audit trail of all administrative actions.
- **Fields**: id, user_id, action, entity_type, entity_id, description, ip_address, user_agent, created_at
- **Purpose**: Security and compliance tracking

## Usage Guide

### Customer Workflow

1. **Browse Products**: Visit homepage or shop page to view products
2. **Search & Filter**: Use category filters and search to find products
3. **View Details**: Click on products to see full details, images, and reviews
4. **Add to Cart/Wishlist**: Requires login for wishlist, cart works without login
5. **Checkout**: Enter shipping address and payment information
6. **Track Orders**: View order history and status in account dashboard
7. **Write Reviews**: Rate and review purchased products

### Admin Panel Usage

**Dashboard Access:**
- Navigate to `/admin/dashboard.php`
- Login with admin credentials
- View sales statistics and recent orders

**Product Management:**
1. Go to Products section
2. Click "Add Product" to create new products
3. Fill in bilingual product information (English and Kurdish)
4. Upload main product image and gallery images (max 5MB each)
5. Set price, stock quantity, SKU, and featured status
6. Click "Edit" to modify existing products
7. Delete individual gallery images as needed

**Category Management:**
1. Navigate to Categories section
2. Add new categories with English and Kurdish names
3. Create unique URL-friendly slugs
4. Edit or delete categories (cannot delete if products exist)

**Order Management:**
1. View all orders in Orders section
2. Click on orders to view detailed information
3. Update order status (pending, processing, shipped, delivered, cancelled)
4. Add tracking numbers for shipped orders
5. View customer information and shipping addresses

### Super Admin Features

**Admin Management:**
1. Navigate to Super Admin → Admins
2. Create new admin users with email and password
3. Assign granular permissions using checkboxes
4. Edit existing admin permissions
5. View admin activity logs

**Available Permissions:**
- `view_dashboard`: Access admin dashboard
- `manage_products`: Add, edit, delete products
- `manage_categories`: Manage product categories
- `view_orders`: View orders and details
- `manage_orders`: Update order status
- `view_reports`: Access sales reports
- `manage_admins`: Create and manage admins
- `view_users`: View customer accounts
- `manage_users`: Modify customer accounts
- `system_settings`: Access system settings

**User Management:**
1. Navigate to Super Admin → Users
2. View all customer accounts
3. Search and filter users
4. View user order history

**Reports:**
1. Navigate to Super Admin → Reports
2. View sales statistics by period (day, week, month, year)
3. See top-selling products
4. Analyze customer statistics

**System Settings:**
1. Navigate to Super Admin → Settings
2. Configure site name, email, currency
3. Set tax rates and shipping costs
4. Manage system-wide preferences

### Authentication Flow

**Registration:**
1. Navigate to `/register.php`
2. Enter full name, email, and password
3. Account created with 'customer' role
4. Automatic login after registration

**Login:**
1. Navigate to `/login.php`
2. Enter email and password
3. Redirects to previous page or homepage
4. Session maintained until logout

**Logout:**
1. Click logout link in navigation
2. Session destroyed
3. Redirects to homepage

## Security Considerations

### Input Validation
- All user inputs sanitized using `strip_tags()` and `trim()`
- Email validation using `filter_var()` with `FILTER_VALIDATE_EMAIL`
- Numeric inputs validated with type casting
- File upload validation for type and size

### SQL Injection Protection
- PDO with prepared statements and named parameters
- No direct SQL query concatenation
- Parameterized queries for all database operations
- Input validation before database operations

### Password Hashing
- ARGON2ID algorithm (most secure PHP hashing algorithm)
- Automatic salt generation
- Password verification using `password_verify()`
- Passwords never stored in plain text

### Session Management
- Secure session configuration
- Session regeneration on login
- CSRF token validation on all forms
- Session timeout handling
- Proper session destruction on logout

### XSS Prevention
- All output sanitized with `htmlspecialchars()`
- Helper function `e()` for consistent sanitization
- ENT_QUOTES flag to escape both single and double quotes
- UTF-8 encoding specified

### File Upload Security
- Allowed file types: JPEG, PNG, GIF, WebP
- Maximum file size: 5MB (configurable)
- File type validation using `mime_content_type()`
- Unique filename generation to prevent overwrites
- Upload directory outside document root (recommended)

### Access Control
- Role-based access (customer, admin, super_admin)
- Permission-based access for admin features
- `requireLogin()`, `requireAdmin()`, `requireSuperAdmin()` functions
- `requirePermission()` for granular control
- Automatic redirection for unauthorized access

### CSRF Protection
- Token generation using `random_bytes()`
- Token stored in session
- Token validation on all POST requests
- Token comparison using `hash_equals()` to prevent timing attacks

## Screenshots

### Customer Interface
![Homepage](screenshots/homepage.png)
*Homepage with featured products and category navigation*

![Product Details](screenshots/product-details.png)
*Product detail page with image gallery and reviews*

![Shopping Cart](screenshots/cart.png)
*Shopping cart with quantity management*

![Checkout](screenshots/checkout.png)
*Checkout page with shipping address form*

### Admin Panel
![Admin Dashboard](screenshots/admin-dashboard.png)
*Admin dashboard with sales statistics*

![Product Management](screenshots/admin-products.png)
*Product management interface*

![Order Management](screenshots/admin-orders.png)
*Order management with status updates*

### Super Admin
![Admin Management](screenshots/super-admin-admins.png)
*Admin user management with permissions*

![Sales Reports](screenshots/super-admin-reports.png)
*Sales reports and analytics*

## Future Improvements

### Planned Features
- Email notifications for order confirmations and status updates
- SMS notifications for order tracking
- Advanced search with filters (price range, rating, availability)
- Product variants (sizes, colors, arrangements)
- Coupon and discount code system
- Multiple payment gateway integrations (PayPal, Stripe)
- Real-time shipping cost calculator
- Product comparison feature
- Recently viewed products tracking
- Customer loyalty program
- Newsletter subscription system
- Social media integration (share products)
- Advanced analytics dashboard with charts
- Export functionality for reports (PDF, Excel)
- Bulk product import/export
- Multi-currency support
- Inventory alerts for low stock
- Customer reviews moderation
- Product recommendations based on purchase history
- Live chat support integration

### Technical Enhancements
- API development for mobile app integration
- Caching layer for improved performance (Redis/Memcached)
- CDN integration for static assets
- Database query optimization
- Automated backup system
- Error logging and monitoring
- Unit and integration testing
- Continuous integration/deployment pipeline
- Docker containerization
- Microservices architecture for scalability

## Contribution Guidelines

### How to Contribute

1. **Fork the Repository**: Create your own fork of the project
2. **Create a Branch**: Create a feature branch (`git checkout -b feature/AmazingFeature`)
3. **Make Changes**: Implement your feature or bug fix
4. **Follow Coding Standards**:
   - Use PHP 8.2+ features and strict typing
   - Follow PSR-12 coding standards
   - Add PHPDoc comments for all functions
   - Use meaningful variable and function names
   - Maintain bilingual support (English/Kurdish)
5. **Test Your Changes**: Ensure all features work correctly
6. **Commit Changes**: Use clear commit messages (`git commit -m 'Add some AmazingFeature'`)
7. **Push to Branch**: Push to your fork (`git push origin feature/AmazingFeature`)
8. **Open Pull Request**: Submit a pull request with detailed description

### Code Review Process

- All pull requests will be reviewed by maintainers
- Code must pass security review
- Features must include documentation updates
- Bug fixes should include test cases
- Maintain backward compatibility when possible

### Reporting Issues

- Use GitHub Issues to report bugs
- Provide detailed steps to reproduce
- Include PHP version and environment details
- Attach screenshots if applicable

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

**Bloom & Vine Development Team**

- GitHub: [@meer404](https://github.com/meer404)
- Project Repository: [Flower-Store](https://github.com/meer404/Flower-Store)
- Contact: contact@mir.codes

---

**Version**: 2.0  
**Last Updated**: January 19, 2026  
**Status**: Production Ready

For additional documentation on the admin permission system, see:
- `PERMISSION_SYSTEM_READY.md` - Quick start guide
- `ADMIN_PERMISSIONS_INDEX.md` - Complete documentation index
- `FEATURES.md` - Detailed feature documentation
