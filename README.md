# 🌸 Bloom & Vine - Premium Flower E-Commerce Platform

![Bloom & Vine](https://img.shields.io/badge/Status-Production%20Ready-brightgreen?style=for-the-badge)
![PHP Version](https://img.shields.io/badge/PHP-8.2%2B-blue?style=for-the-badge)
![License](https://img.shields.io/badge/License-MIT-green?style=for-the-badge)
![Language](https://img.shields.io/badge/Languages-English%20%7C%20Kurdish-orange?style=for-the-badge)

> A beautiful, fully-responsive e-commerce platform for a premium online flower shop based in the Kurdistan Region, Iraq. Featuring bilingual support, secure authentication, advanced admin controls, and seamless payment integration.

---

## 🌐 Live Demo

**🎯 Visit the live store:** [https://flower.mir.codes/](https://flower.mir.codes/)

Experience Bloom & Vine's responsive design, smooth user experience, and bilingual interface (English & Kurdish) in action.

---

## ✨ Key Features

### 🛍️ **Customer Experience**
- ✅ **Fully Responsive Design** - Optimized for desktop, tablet, and mobile devices
- ✅ **Bilingual Interface** - Complete English (LTR) and Kurdish (RTL) support
- ✅ **User Authentication** - Secure login and registration with Argon2ID password hashing
- ✅ **Google OAuth** - Quick sign-up and login via Google accounts
- ✅ **Product Browsing** - Categorized shopping (Wedding, Anniversary, Birthday, Graduation, etc.)
- ✅ **Advanced Filters** - Search, category, price range, and rating filters
- ✅ **Shopping Cart** - Add/remove items, update quantities, calculate delivery tiers
- ✅ **Wishlist** - Save favorite products for later
- ✅ **Product Reviews** - 5-star rating system with customer reviews
- ✅ **Multiple Payment Methods** - First Iraqi Bank (FIB) online payments and Cash on Delivery

### 🎨 **Featured Products Showcase**
- Curated homepage featuring bestsellers and new arrivals
- Product statistics (views, sales, ratings)
- Category highlights and special collections

### 👥 **Admin Dashboard**
- ✅ **Product Management** - Add, edit, delete products with bilingual fields
- ✅ **Inventory Management** - Product variants, gallery images, expiry dates
- ✅ **Order Management** - View, filter, and update order statuses
- ✅ **Coupon System** - Create and manage discount codes with usage limits
- ✅ **Customer Support** - Manage contact form submissions and reply via email
- ✅ **Analytics Dashboard** - Revenue tracking, order statistics, and customer insights
- ✅ **Permission-Based Access** - Granular admin permissions and role management

### 👨‍💼 **Super Admin Controls**
- ✅ **Admin User Management** - Create and assign permissions to admin accounts
- ✅ **Customer Management** - Search, view, and manage customer accounts
- ✅ **Advanced Reporting** - Sales summaries, profit/loss analysis, and exports
- ✅ **System Settings** - Configure delivery fees, currency, email settings, and more
- ✅ **Audit Logging** - Track all admin actions for security and compliance

### 🔒 **Security & Performance**
- ✅ **CSRF Protection** - Token-based cross-site request forgery prevention
- ✅ **Input Validation** - Server-side validation and output escaping
- ✅ **Prepared Statements** - SQL injection protection with PDO
- ✅ **Secure Password Hashing** - Argon2ID algorithm for strong security
- ✅ **PWA Support** - Progressive web app features with offline support

---

## 🛠️ Tech Stack

| Layer | Technologies |
|-------|--------------|
| **Backend** | PHP 8.2+, MySQL 5.7+ / MariaDB 10.2+ |
| **Frontend** | HTML5, CSS3, Tailwind CSS, Vanilla JavaScript |
| **Security** | Argon2ID hashing, CSRF tokens, Prepared statements |
| **Payment** | First Iraqi Bank (FIB) API Integration |
| **Email** | PHPMailer, Gmail SMTP |
| **Authentication** | Google OAuth 2.0 |
| **PWA** | Service Workers, Web Manifest |

---

## 📋 Prerequisites

Before you begin, ensure you have the following installed:

- **PHP 8.2 or higher**
- **MySQL 5.7+ or MariaDB 10.2+**
- **Apache with mod_rewrite enabled** (or Nginx with proper rewrite rules)
- **Composer** (optional, for package management)
- **File upload permissions** on your server
- **OpenSSL** for secure connections

### System Requirements
- Minimum 500MB disk space
- 256MB RAM minimum (recommended 512MB+)
- Modern browser with JavaScript enabled

---

## 🚀 Installation Guide

### Step 1: Clone the Repository

```bash
# Clone the repository
git clone https://github.com/meer404/Flower-Store.git
cd Flower-Store
```

### Step 2: Setup Your Local Server

#### Option A: Using XAMPP (Windows/Mac/Linux)

```bash
# Place the project in htdocs
cd /path/to/xampp/htdocs
git clone https://github.com/meer404/Flower-Store.git
cd Flower-Store
```

#### Option B: Using Built-in PHP Server (Development Only)

```bash
# Start PHP development server
php -S localhost:8000
```

Then open your browser and navigate to `http://localhost:8000`

### Step 3: Create and Configure the Database

```bash
# Create a new database
mysql -u root -p

# In MySQL shell:
CREATE DATABASE bloom_vine CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
exit
```

### Step 4: Import Database Schema

```bash
# Import the base schema
mysql -u root -p bloom_vine < database/schema.sql
```

### Step 5: Run Database Migrations (Optional)

These migrations add additional features to your database:

```bash
# Admin permissions migration
php database/run_admin_permissions_migration.php

# Delivery date feature migration
php database/run_delivery_date_migration.php

# Payment method migration
php database/run_payment_method_migration.php
```

### Step 6: Configure Database Connection

Edit `src/config/db.php` with your database credentials:

```php
<?php
define('DB_HOST', 'localhost');      // Your database host
define('DB_NAME', 'bloom_vine');     // Database name
define('DB_USER', 'root');           // Database username
define('DB_PASS', 'your_password');  // Database password
define('DB_PORT', 3306);             // Database port (optional)
```

### Step 7: Set File Permissions

Ensure the `uploads/` directory is writable by the web server:

```bash
# Linux/Mac
chmod -R 755 uploads/
chmod -R 755 admin/
chmod -R 755 src/
```

### Step 8: Configure Email Settings (Optional)

Edit `src/gmail_config.php` to set up email notifications:

```php
<?php
define('GMAIL_ADDRESS', 'your-email@gmail.com');
define('GMAIL_APP_PASSWORD', 'your-app-specific-password');
```

---

## 🔐 Default Access Credentials

### 🌐 Access Points

| Access Point | URL |
|---|---|
| **Customer Store** | `http://localhost/Flower-Store/` or `http://localhost:8000` |
| **Admin Dashboard** | `http://localhost/Flower-Store/admin/dashboard.php` |
| **Super Admin Panel** | `http://localhost/Flower-Store/admin/super_admin_dashboard.php` |

### 👤 Default Accounts

| Role | Email | Password | Notes |
|------|-------|----------|-------|
| **Admin** | `admin@bloomvine.com` | `admin123` | Standard admin access |
| **Super Admin** | `superadmin@bloomvine.com` | `superadmin123` | Full system access |

⚠️ **IMPORTANT:** Change these credentials immediately after your first login in production!

---

## 📁 Project Structure

```
Flower-Store/
├── admin/                      # Admin and super admin panels
│   ├── dashboard.php          # Admin dashboard
│   ├── products.php           # Product management
│   ├── add_product.php        # Add new product
│   ├── edit_product.php       # Edit product
│   ├── orders.php             # Order management
│   ├── super_admin_*          # Super admin pages
│   └── ...
├── database/                   # Database schemas and migrations
│   ├── schema.sql             # Base database schema
│   ├── run_admin_permissions_migration.php
│   ├── run_delivery_date_migration.php
│   └── run_payment_method_migration.php
├── src/                        # Core modules and shared components
│   ├── config/                # Configuration files
│   │   └── db.php            # Database connection
│   ├── translations/          # Language files
│   │   ├── en.php            # English translations
│   │   └── ku.php            # Kurdish translations
│   ├── pages/                # Static content pages
│   │   ├── en/               # English pages
│   │   └── ku/               # Kurdish pages
│   ├── components.php         # Reusable UI components
│   ├── functions.php          # Helper functions
│   ├── header.php            # Navigation header
│   ├── email.php             # Email utilities
│   ├── FibService.php        # Payment integration
│   └── pwa_head.php          # PWA configuration
├── uploads/                    # Product images and media
├── public/                     # Static assets
│   ├── css/                   # Stylesheets
│   ├── js/                    # JavaScript files
│   ├── manifest.json          # PWA manifest
│   └── service-worker.js      # Service worker
├── index.php                   # Home page
├── shop.php                    # Product listing
├── product.php                # Product detail
├── cart.php                    # Shopping cart
├── checkout.php               # Checkout page
├── login.php                   # Login page
├── register.php               # Registration page
├── account.php                # User account
├── wishlist.php               # Wishlist page
├── contact.php                # Contact form
├── about.php, shipping.php, terms.php, privacy.php  # Static pages
└── README.md                   # This file
```

---

## 🔄 Main User Flows

### 1️⃣ **Customer Shopping Flow**
```
Home → Shop/Browse → View Product Details → Add to Cart → 
Wishlist (optional) → View Cart → Checkout → Select Payment → 
Complete Purchase → Order Confirmation
```

### 2️⃣ **Authentication Flow**
```
Register/Login → Email Verification (optional) → 
Dashboard Access → Account Management → Order History
```

### 3️⃣ **Admin Product Management**
```
Login → Products → Add/Edit → Upload Images → 
Set Variants → Publish → Feature (optional)
```

### 4️⃣ **Payment Processing**
```
Checkout → Select FIB Payment → Payment Gateway → 
Webhook Confirmation → Order Status Update → 
Customer Notification
```

---

## 🌍 Bilingual Support

Bloom & Vine supports both English and Kurdish with automatic:

- **Text Direction**: LTR for English, RTL for Kurdish
- **Translations**: Complete UI translations in `src/translations/`
- **Language Switching**: Seamless language toggle in navigation
- **SEO Support**: Language-specific meta tags and content

Switch languages using the language switcher in the header navigation.

---

## 🎨 Customization

### Update Shop Information

Edit `src/config/db.php` and `src/functions.php` to customize:
- Store name and branding
- Contact information
- Delivery zones and fees
- Currency settings
- Email signatures

### Modify Colors and Styling

All styling is managed through:
- **Tailwind CSS** for utility classes
- **`src/design_config.php`** for design system constants
- **CSS files** in `public/css/`

### Add New Pages

1. Create a new `.php` file in the root directory
2. Include the header: `<?php include 'src/header.php'; ?>`
3. Include the footer if needed
4. Use translation functions for multi-language support

---

## 🔒 Security Best Practices

✅ **Implemented Security Features:**
- Passwords hashed with Argon2ID (strongest algorithm)
- CSRF tokens on all forms
- SQL injection protection with prepared statements
- XSS protection with output escaping
- Secure session management
- Permission-based access control

**For Production Deployment:**
1. Use HTTPS/SSL certificates
2. Set strong database passwords
3. Change default admin credentials
4. Keep PHP and dependencies updated
5. Enable error logging (disable display_errors)
6. Set proper file permissions (644 for files, 755 for directories)
7. Configure email with valid SMTP credentials
8. Review `SECURITY.md` for detailed guidelines

---

## 📚 Documentation

The repository includes comprehensive documentation:

| Document | Purpose |
|----------|---------|
| **ADMIN_PERMISSIONS_INDEX.md** | Admin permissions reference |
| **ADMIN_PERMISSIONS_GUIDE.md** | Complete permission setup guide |
| **ADMIN_PERMISSIONS_QUICK_REF.md** | Quick permission reference |
| **FEATURES.md** | Detailed feature documentation |
| **SECURITY.md** | Security implementation details |
| **DELIVERY_DATE_FEATURE.md** | Delivery date feature guide |
| **PAYMENT_METHOD_FEATURE.md** | Payment method configuration |

---

## 🛠️ Troubleshooting

### Common Issues

**Problem:** 404 errors on all pages
- **Solution:** Ensure mod_rewrite is enabled in Apache, or configure Nginx rewrite rules

**Problem:** Database connection fails
- **Solution:** Verify credentials in `src/config/db.php`, ensure MySQL is running

**Problem:** File uploads not working
- **Solution:** Check `uploads/` directory permissions (should be 755)

**Problem:** Email notifications not sending
- **Solution:** Configure SMTP credentials in `src/gmail_config.php`

**Problem:** Language switching not working
- **Solution:** Ensure `$_SESSION['lang']` is properly set in `src/language.php`

For more troubleshooting help, check the documentation files or open an issue on GitHub.

---

## 📊 Database Schema Highlights

Core tables include:
- `users` - Customer accounts
- `admin_users` - Admin accounts with permissions
- `products` - Product catalog
- `categories` - Product categories (bilingual)
- `orders` - Customer orders
- `order_items` - Order line items
- `product_images` - Product gallery
- `reviews` - Product reviews and ratings
- `wishlist` - User wishlists
- `notifications` - User notifications
- `coupons` - Discount codes
- `activity_log` - Admin action audit trail
- `system_settings` - Configuration values

See `database/schema.sql` for complete schema details.

---

## 🤝 Contributing

We welcome contributions! Please follow these steps:

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/AmazingFeature`)
3. Commit your changes (`git commit -m 'Add AmazingFeature'`)
4. Push to the branch (`git push origin feature/AmazingFeature`)
5. Open a Pull Request

---

## 📞 Contact & Support

**Get in touch with the Bloom & Vine team:**

| Channel | Information |
|---------|-------------|
| 📧 **Email** | contact@mir.codes |
| 🌐 **Website** | https://flower.mir.codes |
| 💻 **GitHub** | https://github.com/meer404/Flower-Store |
| 👤 **Developer** | https://github.com/meer404 |

---

## 📜 License

This project is licensed under the **MIT License** - see below for details:

```
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
```

---

## 📈 Project Statistics

- **Version:** 2.0
- **Status:** Production Ready ✅
- **Created:** December 20, 2025
- **Last Updated:** May 16, 2026
- **Languages:** PHP, HTML, CSS, JavaScript
- **Language Support:** English, Kurdish
- **License:** MIT

---

## 🎯 Roadmap

Future enhancements planned for Bloom & Vine:

- [ ] Mobile app (iOS & Android)
- [ ] Multi-currency support
- [ ] Advanced inventory management
- [ ] Customer loyalty program
- [ ] Live chat support
- [ ] AI-powered recommendations
- [ ] Social media integration
- [ ] Enhanced analytics dashboard

---

## 💡 Tips for Success

1. **Read the Documentation** - Check the doc files in the repo for detailed guides
2. **Test Locally First** - Always test changes on your local environment
3. **Keep Backups** - Regular database backups are essential
4. **Monitor Logs** - Check error logs for issues
5. **Stay Updated** - Keep PHP and dependencies current
6. **Security First** - Follow security best practices from `SECURITY.md`

---

<div align="center">

**Made with 🌹 by the Bloom & Vine Development Team**

[Visit Store](https://flower.mir.codes/) • [GitHub](https://github.com/meer404/Flower-Store) • [Contact Us](contact@mir.codes)

</div>
