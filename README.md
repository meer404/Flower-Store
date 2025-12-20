# Bloom & Vine Flower Store

A production-ready, dual-language (English/Kurdish) e-commerce application for a flower shop built with Vanilla PHP 8.2+, MySQL, Tailwind CSS, and JavaScript.

## Features

### Core Features
- **Dual Language Support**: English (LTR) and Kurdish/Sorani (RTL) with full translation system
- **Secure Authentication**: ARGON2ID password hashing, CSRF protection, session management
- **Shopping Cart**: Session-based cart with real-time updates and quantity management
- **Responsive Design**: Modern UI with Tailwind CSS and mobile-first approach
- **Security**: XSS prevention, SQL injection protection (PDO), CSRF tokens, file upload validation

### Customer Features
- **Product Catalog**: Browse products with category filters and search
- **Product Details**: Detailed product pages with image galleries, descriptions, and reviews
- **Wishlist**: Save favorite products for later
- **Reviews & Ratings**: Rate and review products (1-5 stars)
- **Order Management**: View order history and track order details
- **User Profile**: Manage account information, shipping address, and password
- **Advanced Search**: Search products by name, description, or category

### Admin Features
- **Dashboard**: View sales statistics, order counts, and recent orders
- **Product Management**: 
  - Add, edit, and delete products
  - Image gallery support (multiple images per product)
  - Featured products
  - Stock management
  - SKU tracking
- **Category Management**: Create and manage product categories
- **Order Management**: View all orders, order details, and update order status
- **Pagination**: Efficient browsing of large product and order lists

## Requirements

- PHP 8.2 or higher
- MySQL 5.7+ or MariaDB 10.2+
- Apache/Nginx web server
- mod_rewrite enabled (optional, for clean URLs)

## Installation

### 1. Database Setup

1. Create a MySQL database:
```sql
CREATE DATABASE bloom_vine CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

2. Import the schema:
```bash
mysql -u root -p bloom_vine < database/schema.sql
```

Or use phpMyAdmin to import `database/schema.sql`.

### 2. Database Configuration

Edit `src/config/db.php` and update the database credentials:

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'bloom_vine');
define('DB_USER', 'your_username');
define('DB_PASS', 'your_password');
```

### 3. File Permissions

Ensure the `uploads/` directory is writable:

```bash
mkdir uploads
chmod 755 uploads
```

On Windows, ensure the directory has write permissions.

### 4. Extended Database Schema

After importing the base schema, import the extended schema for advanced features:

```bash
mysql -u root -p bloom_vine < database/schema_extended.sql
```

Or use phpMyAdmin to import `database/schema_extended.sql`.

This adds support for:
- Wishlist functionality
- Product reviews and ratings
- Product image galleries
- Enhanced user profiles
- Order status tracking

### 5. Web Server Configuration

#### Apache (.htaccess)

Create a `.htaccess` file in the root directory (optional):

```apache
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
```

#### Nginx

Configure your server block to point to the project root directory.

### 6. Default Admin Credentials

- **Email**: admin@bloomvine.com
- **Password**: admin123

**⚠️ IMPORTANT**: Change these credentials immediately after first login!

## Directory Structure

```
bloom-store/
├── admin/
│   ├── dashboard.php          # Admin dashboard with statistics
│   ├── add_product.php        # Add new products
│   ├── edit_product.php       # Edit existing products
│   ├── products.php           # Product management list
│   └── categories.php         # Category management
├── database/
│   ├── schema.sql             # Base database schema
│   └── schema_extended.sql   # Extended schema (wishlist, reviews, etc.)
├── src/
│   ├── config/
│   │   └── db.php            # Database connection
│   ├── translations/
│   │   ├── en.php            # English translations
│   │   └── ku.php            # Kurdish translations
│   ├── functions.php         # Helper functions
│   └── language.php          # Language handler
├── uploads/                   # Product images directory
├── account.php               # User account dashboard
├── cart.php                  # Shopping cart
├── cart_action.php           # Cart operations
├── checkout.php              # Checkout process
├── index.php                 # Home page
├── login.php                 # Login page
├── logout.php                # Logout handler
├── order_details.php        # Order details view
├── product.php              # Product detail page
├── register.php             # Registration page
├── review.php               # Add review page
├── shop.php                 # Shop with filters
├── wishlist.php             # User wishlist
├── wishlist_action.php      # Wishlist operations
└── README.md
```

## Usage

### Language Switching

Users can switch between English and Kurdish using the language toggle in the navigation bar. The language preference is stored in the session and affects all text, including product names and descriptions.

### Customer Features

#### Shopping
1. Browse products on the home page or shop page
2. Use search and category filters to find products
3. Click on products to view detailed information
4. Add products to cart or wishlist (requires login)
5. Review cart and proceed to checkout
6. Enter shipping address and place order
7. View order history in account dashboard

#### Product Reviews
1. Navigate to a product detail page
2. Click "Write a Review" (if you haven't reviewed yet)
3. Rate the product (1-5 stars) and add a comment
4. Submit your review

#### Wishlist
1. Browse products
2. Click "Add to Wishlist" on any product
3. View your wishlist from the account dashboard
4. Add wishlist items directly to cart

### Admin Features

#### Product Management
1. Log in as admin
2. Navigate to Admin Dashboard → Products
3. Click "Add Product" to create new products
4. Fill in product details in both English and Kurdish
5. Upload main image and gallery images (max 5MB each, JPEG/PNG/GIF/WebP)
6. Set price, stock quantity, SKU, and featured status
7. Edit products by clicking "Edit" in the products list
8. Delete gallery images individually

#### Category Management
1. Navigate to Admin Dashboard → Categories
2. Add new categories with English and Kurdish names
3. Create unique slugs for each category
4. Edit or delete categories (cannot delete if products exist)

#### Order Management
1. View all orders in the dashboard
2. See order statistics (total sales, order count)
3. Click on orders to view details
4. Track order status and payment status

## Security Features

- **Password Hashing**: ARGON2ID algorithm
- **XSS Prevention**: All output sanitized with `htmlspecialchars()`
- **SQL Injection Prevention**: PDO with named parameters
- **CSRF Protection**: Tokens on all forms
- **File Upload Validation**: Type and size checks
- **Session Security**: Proper session management

## Customization

### Colors

The color palette is defined in Tailwind config:
- Primary: `#3A4B41` (Hunter Green)
- Secondary: `#E6CFA7` (Cream/Beige)

To change colors, update the Tailwind config script in each page's `<head>` section.

### Translations

Add or modify translations in:
- `src/translations/en.php` (English)
- `src/translations/ku.php` (Kurdish)

## Troubleshooting

### Database Connection Error

- Verify database credentials in `src/config/db.php`
- Ensure MySQL service is running
- Check database name matches

### Image Upload Fails

- Verify `uploads/` directory exists and is writable
- Check PHP `upload_max_filesize` and `post_max_size` settings
- Ensure directory permissions are correct

### Session Issues

- Verify `session_start()` is called before any output
- Check PHP `session.save_path` is writable
- Clear browser cookies if issues persist

## License

This project is provided as-is for educational and commercial use.

## Support

For issues or questions, please refer to the code comments or contact the development team.
