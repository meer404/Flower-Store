# Hardcoded English Strings Audit - Flower Store

This document lists all hardcoded English strings found in PHP files that are NOT wrapped in translation functions like `t()` or `$translations` array lookups. These strings need to be moved to the translation system for proper i18n support.

---

## Summary
- **Total Files with Issues**: 11
- **Total Hardcoded Strings**: 40+
- **Priority**: HIGH - User-facing content should all be translatable

---

## Files with Hardcoded Strings

### 1. [review.php](review.php) - CRITICAL
**Status**: Multiple hardcoded user-facing strings

| Line | Hardcoded String | Current Code | Should Be |
|------|------------------|--------------|-----------|
| 21 | "Invalid product" | `redirect('shop.php', e('Invalid product'), 'error');` | `redirect('shop.php', t('invalid_product'), 'error');` |
| 28 | "You have already reviewed this product" | `redirect('product.php?id=' . $productId, e('You have already reviewed this product'), 'error');` | `redirect(..., t('review_duplicate'), ...);` |
| 37 | "Product not found" | `redirect('shop.php', e('Product not found'), 'error');` | `redirect(..., t('product_not_found'), ...);` |
| 51 | "Please select a rating" | `$error = e('Please select a rating');` | `$error = t('please_select_rating');` |
| 62 | "Review submitted successfully!" | `redirect('product.php?id=' . $productId, e('Review submitted successfully!'), 'success');` | `redirect(..., t('review_success'), ...);` |
| 79 | "Write a Review" | `<title><?= e('Write a Review') ?> - Bloom & Vine</title>` | `<title><?= e(t('write_review')) ?> - Bloom & Vine</title>` |
| 88 | "Write a Review" | `<h1 class="text-3xl font-bold text-primary mb-4"><?= e('Write a Review') ?></h1>` | `<h1 ...><?= e(t('write_review')) ?></h1>` |
| 106 | "Rating" | `<label class="block text-sm font-medium text-primary mb-2"><?= e('Rating') ?> *</label>` | `<label ...><?= e(t('rating')) ?> *</label>` |
| 118 | "Your Review" | `<label for="comment" class="block text-sm font-medium text-primary mb-2"><?= e('Your Review') ?></label>` | `<label ...><?= e(t('your_review')) ?></label>` |
| 121 | "Share your experience with this product..." | `placeholder="<?= e('Share your experience with this product...') ?>"` | `placeholder="<?= e(t('review_placeholder')) ?>"` |
| 127 | "Submit Review" | `<?= e('Submit Review') ?>` | `<?= e(t('submit_review')) ?>` |
| 131 | "Cancel" | `<?= e('Cancel') ?>` | `<?= e(t('cancel')) ?>` |

---

### 2. [order_action.php](order_action.php) - HIGH PRIORITY
**Status**: Multiple error messages hardcoded

| Line | Hardcoded String | Context |
|------|------------------|---------|
| 22 | "Invalid request" | Error message in redirect |
| 30 | "Invalid security token" | Security validation error |
| 34 | "Invalid order ID" | Order validation error |
| 43 | "Invalid order status" | Status validation error |
| 52 | "Order not found" | Database query result |
| 87 | "Order status updated successfully" | Success message |
| 98 | "Order not found" | Database query result (duplicate) |
| 122 | "Tracking number updated successfully" | Success message |
| 129 | "Invalid payment status" | Validation error |
| 138 | "Order not found" | Database query result (duplicate) |
| 145 | "Payment status updated successfully" | Success message |
| 148 | "Invalid action" | Request validation |
| 151 | "An error occurred while updating the order" | Generic error message |

**All lines**: Use `redirect()` with `e()` wrapper instead of `t()`  
**Example**: Line 22: `redirect('admin/dashboard.php', e('Invalid request'), 'error');`

---

### 3. [wishlist_action.php](wishlist_action.php) - MEDIUM PRIORITY

| Line | Hardcoded String |
|------|------------------|
| 81 | "Added to wishlist" |
| 85 | "Removed from wishlist" |

**Current Code**:
```php
redirect('wishlist.php', e('Added to wishlist'), 'success');
redirect('wishlist.php', e('Removed from wishlist'), 'success');
```

**Should Be**:
```php
redirect('wishlist.php', t('wishlist_added'), 'success');
redirect('wishlist.php', t('wishlist_removed'), 'success');
```

---

### 4. [admin/footer.php](admin/footer.php) - LOW TO MEDIUM PRIORITY
**Status**: Navigation links and footer text hardcoded

| Line | Hardcoded String | Code |
|------|------------------|------|
| 10 | "Bloom & Vine." | `<span>&copy; <?= date('Y') ?> Bloom & Vine.</span>` |
| 12 | "Admin Panel v2.0" | `<span class="font-medium text-purple-600">Admin Panel v2.0</span>` |
| 15 | "Support" | `<a href="#" class="hover:text-purple-600 transition-colors">Support</a>` |
| 16 | "Documentation" | `<a href="#" class="hover:text-purple-600 transition-colors">Documentation</a>` |
| 17 | "Visit Store" | `<a href="../index.php" class="hover:text-purple-600 transition-colors">Visit Store</a>` |

---

### 5. [admin/add_product.php](admin/add_product.php) - LOW TO MEDIUM PRIORITY
**Status**: UI descriptive text hardcoded

| Line | Hardcoded String | Code |
|------|------------------|------|
| 141 | "Create a new product for your store" | `<p class="text-gray-500 mt-1">Create a new product for your store</p>` |
| 144 | "Back to Products" | `<i class="fas fa-arrow-left"></i> Back to Products` |
| 163 | "Product Details" | `<h2 class="text-xl font-bold text-gray-800 mb-6 pb-2 border-b border-gray-100">Product Details</h2>` |
| 206 | "Pricing & Inventory" | `<h2 class="text-xl font-bold text-gray-800 mb-6 pb-2 border-b border-gray-100">Pricing & Inventory</h2>` |
| 237 | "Organization" | `<h2 class="text-lg font-bold text-gray-800 mb-4">Organization</h2>` |

---

### 6. [admin/categories.php](admin/categories.php) - LOW TO MEDIUM PRIORITY
**Status**: UI descriptive text and placeholder hardcoded

| Line | Hardcoded String | Code |
|------|------------------|------|
| 118 | "Manage product categories and organization" | `<p class="text-gray-500 mt-1">Manage product categories and organization</p>` |
| 167 | "e.g., weddings" | `placeholder="e.g., weddings"` |
| 170 | "URL-friendly version of the name" | `<p class="text-xs text-gray-500 mt-1">URL-friendly version of the name</p>` |

---

### 7. [admin/header.php](admin/header.php) - LOW PRIORITY
**Status**: Specific hardcoded dashboard title and language selector tooltip

| Line | Hardcoded String | Code | Note |
|------|------------------|------|------|
| 17 | "Dashboard" | `$pageTitle = 'Dashboard';` | Should use `t('dashboard')` |
| 29 | "Switch to Kurdish" / "Switch to English" | Ternary in title attribute | Should be translatable |

---

### 8. [src/components.php](src/components.php) - LOW PRIORITY
**Status**: Component text hardcoded

| Line | Hardcoded String | Context |
|------|------------------|---------|
| 150 | "Account" | Navigation link |
| 179 | "All rights reserved." | Footer text |

---

### 9. [src/design_config.php](src/design_config.php) - LOW PRIORITY
**Status**: Footer text hardcoded

| Line | Hardcoded String | Code |
|------|------------------|------|
| 179 | "All rights reserved." | `<p class="text-luxury-accentLight font-light tracking-wide">&copy; ' . e(date('Y')) . ' Bloom & Vine. ' . e('All rights reserved.') . '</p>` |

---

### 10. [admin/super_admin_users.php](admin/super_admin_users.php) - LOW PRIORITY
**Status**: Error message in die statement

| Line | Hardcoded String | Code |
|------|------------------|------|
| 18 | "Access denied. You need view_users or manage_users permission to access this page." | `die('Access denied. You need view_users or manage_users permission to access this page.');` |

**Note**: This is a permission check message - should be translatable for better UX

---

### 11. [src/functions.php](src/functions.php) - LOW PRIORITY
**Status**: Error message in die statement

| Line | Hardcoded String | Code |
|------|------------------|------|
| 1043 | "Access denied. You do not have permission to access this resource." | `die('Access denied. You do not have permission to access this resource.');` |

**Note**: Generic permission denied message - should be translatable

---

### 12. [src/config/db.php](src/config/db.php) - LOW PRIORITY
**Status**: Database error message hardcoded

| Line | Hardcoded String | Code |
|------|------------------|------|
| 48 | "Database connection failed. Please contact the administrator." | `die('Database connection failed. Please contact the administrator.');` |

**Note**: Critical system message - should be translatable

---

## Recommendations

### HIGH PRIORITY (Fix First)
1. **[review.php](review.php)** - All 12 instances are user-facing
2. **[order_action.php](order_action.php)** - All 13 instances are error/success messages shown to users

### MEDIUM PRIORITY (Fix Second)
3. **[wishlist_action.php](wishlist_action.php)** - 2 feedback messages
4. **[admin/add_product.php](admin/add_product.php)** - 5 UI sections
5. **[admin/categories.php](admin/categories.php)** - 3 UI descriptive texts

### LOW PRIORITY (Fix Last)
6. **[admin/footer.php](admin/footer.php)** - Navigation and footer
7. **[admin/header.php](admin/header.php)** - Dashboard title and tooltips
8. **[src/components.php](src/components.php)** - Shared component text
9. **[src/design_config.php](src/design_config.php)** - Footer text
10. **Error messages in die() statements** - Permission and system errors

---

## How to Fix

### Step 1: Add keys to language array
In `src/language.php` (English section):
```php
$translations['invalid_product'] = 'Invalid product';
$translations['review_duplicate'] = 'You have already reviewed this product';
// ... etc
```

### Step 2: Replace in PHP files
Change:
```php
redirect('shop.php', e('Invalid product'), 'error');
```

To:
```php
redirect('shop.php', t('invalid_product'), 'error');
```

### Step 3: Update HTML elements
Change:
```html
<h2><?= e('Product Details') ?></h2>
```

To:
```html
<h2><?= e(t('product_details')) ?></h2>
```

---

## Translation Key Suggestions

| English | Suggested Key | Usage |
|---------|---------------|-------|
| Invalid product | `invalid_product` | Error message |
| You have already reviewed this product | `review_duplicate` | Error message |
| Product not found | `product_not_found` | Error message |
| Please select a rating | `please_select_rating` | Validation |
| Review submitted successfully! | `review_success` | Success message |
| Write a Review | `write_review` | Page title/heading |
| Rating | `rating` | Form label |
| Your Review | `your_review` | Form label |
| Share your experience with this product... | `review_placeholder` | Input placeholder |
| Submit Review | `submit_review` | Button text |
| Cancel | `cancel` | Button text |
| Order status updated successfully | `order_status_updated` | Success message |
| Added to wishlist | `wishlist_added` | Success message |
| Removed from wishlist | `wishlist_removed` | Success message |
| Product Details | `product_details` | Section heading |
| Pricing & Inventory | `pricing_inventory` | Section heading |
| Organization | `organization` | Section heading |
| Create a new product for your store | `admin_add_product_desc` | Description text |
| Back to Products | `back_to_products` | Navigation link |
| Support | `support` | Link |
| Documentation | `documentation` | Link |
| Visit Store | `visit_store` | Link |
| All rights reserved | `all_rights_reserved` | Legal text |

---

## Automated Translation Commands

Once translation keys are added to `src/language.php`, use these replacements:

```bash
# In review.php
sed -i "s/e('Invalid product')/t('invalid_product')/g" review.php
sed -i "s/e('You have already reviewed this product')/t('review_duplicate')/g" review.php
# ... and so on
```

Or use VS Code Find & Replace with regex:
- Find: `e('([^']+)')`
- Replace: `t('translation_key')` (manual mapping required)

---

## Notes
- Some hardcoded strings are in HTML element attributes (title, alt, placeholder) - these also need translation
- Error messages in `die()` statements are typically not translated but should be for better UX
- Brand name "Bloom & Vine" appears to be intentionally left hardcoded
- Date format and dynamic year injection are acceptable practices
