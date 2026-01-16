# Admin Permission System - Implementation Guide

## Overview

This update implements a granular permission-based access control system for your Bloom & Vine flower store. Super Admins can now assign specific permissions to regular admins, restricting what actions they can perform.

## Key Features

✅ **Granular Permissions** - Assign specific permissions to each admin  
✅ **Permission Checkboxes** - Easy-to-use checkbox interface when creating/editing admins  
✅ **Super Admin Privileges** - Super admins automatically have all permissions  
✅ **Access Control** - Pages enforce permission requirements  
✅ **Sidebar Filtering** - Sidebar menu items hidden if admin lacks permissions  
✅ **Activity Logging** - Permission changes are logged  

## Available Permissions

When you create or edit an admin, you can assign any combination of these permissions:

1. **View Dashboard** - Access the admin dashboard
2. **Add, Edit & Delete Products** - Full product management
3. **Manage Categories** - Manage product categories
4. **View Orders & Details** - View all orders and order details
5. **Update Order Status** - Change order status
6. **Access Reports** - View system reports
7. **View Customer Users** - View customer user list
8. **Ban/Modify Customer Accounts** - Manage customer accounts
9. **System Settings** - Access system settings

## Step-by-Step Setup

### Step 1: Run the Migration

First, you need to create the admin_permissions table:

**Option A: Via Browser**
1. Open: `http://yoursite.com/flower-store/database/run_admin_permissions_migration.php`
2. You should see a success message

**Option B: Via MySQL**
1. Run the SQL from `database/add_admin_permissions.sql` in your MySQL client

### Step 2: Create/Update Admins with Permissions

1. Login as Super Admin
2. Go to **Super Admin** → **Admins**
3. Click **"Create New Admin"** (or **"Edit Permissions"** for existing admins)
4. Fill in admin details (name, email, password)
5. Select desired permissions using checkboxes
6. Click **"Create Admin"** or **"Save Permissions"**

### Step 3: Permission Effects

Once permissions are set:

- Regular admins can only access pages they have permission for
- Sidebar menu items are hidden for pages they can't access
- Direct URL access to restricted pages returns a "403 Forbidden" error
- All permission changes are logged in activity logs

## Code Integration

### For Developers

#### Check if Admin Has Permission

```php
// In any admin page
if (hasPermission('manage_products')) {
    // Admin can manage products
}

// Or require it (will deny access if missing)
requirePermission('manage_products');
```

#### Get All Permissions for Admin

```php
$permissions = getAdminPermissions($admin_id);
// Returns: ['view_dashboard', 'manage_products', ...]
```

#### Assign/Update Permissions

```php
$permissions = ['manage_products', 'manage_categories', 'view_orders'];
setAdminPermissions($admin_id, $permissions);
```

#### Get Available Permissions List

```php
$available = getAvailablePermissions();
// Returns array of all available permissions with descriptions
```

### Protected Pages

The following pages now enforce permission checks:

- `admin/products.php` - Requires `manage_products`
- `admin/add_product.php` - Requires `manage_products`
- `admin/edit_product.php` - Requires `manage_products`
- `admin/categories.php` - Requires `manage_categories`

You can add permission checks to other pages by adding this line after `requireAdmin()`:

```php
requirePermission('permission_name');
```

## Database Schema

A new table `admin_permissions` was created:

```sql
CREATE TABLE `admin_permissions` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `admin_id` INT UNSIGNED NOT NULL,
  `permission` VARCHAR(100) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `admin_permission` (`admin_id`, `permission`),
  KEY `admin_id` (`admin_id`),
  KEY `permission` (`permission`),
  CONSTRAINT `fk_permissions_admin` 
    FOREIGN KEY (`admin_id`) REFERENCES `users` (`id`) 
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

## Examples

### Example 1: Admin with Report-Only Access

1. Create admin: "John Reporter"
2. Assign permissions: ✓ View Dashboard, ✓ Access Reports, ✓ View Orders
3. Result: John can only view reports, dashboard, and orders - cannot modify anything

### Example 2: Product Manager Admin

1. Create admin: "Sarah Products"
2. Assign permissions: ✓ Add/Edit/Delete Products, ✓ Manage Categories, ✓ View Dashboard
3. Result: Sarah can manage all products and categories but cannot access orders or reports

### Example 3: Order Specialist Admin

1. Create admin: "Mike Orders"
2. Assign permissions: ✓ View Orders & Details, ✓ Update Order Status, ✓ View Dashboard
3. Result: Mike can view and update orders but cannot access product management

## Security Notes

- Super admins always have full access regardless of permission settings
- Permissions are checked on both sidebar display and page access
- Deleting an admin automatically removes all their permissions
- Permission changes are logged for audit trails
- Use strong passwords when creating admins
- Regularly review which admins have which permissions

## Troubleshooting

### Admin sees "Access denied" message

The admin doesn't have the required permission. Check their permissions in the Admin Management page.

### Admin can still see menu items they shouldn't have access to

Clear browser cache and refresh the page. The sidebar uses PHP to check permissions.

### Permission changes not taking effect

Make sure the page was reloaded. The sidebar is regenerated on each page load.

### Migration script returns error

1. Check your database connection
2. Make sure the `users` table exists
3. Verify database user has CREATE TABLE permissions
4. Check database error logs for more details

## Future Enhancements

Consider these additions:

- Permission role templates (pre-defined permission sets)
- Time-limited permissions (expire after X days)
- Resource-level permissions (specific product categories only)
- Two-factor authentication for admins
- More granular permissions (e.g., edit products created by others only)
- Permission audit log viewer

## Support

For issues or questions:

1. Check database/add_admin_permissions.sql for schema
2. Review src/functions.php for permission functions
3. Check admin/super_admin_admins.php for UI implementation
4. Enable error logging to see detailed error messages

---

**Last Updated:** January 16, 2026  
**Version:** 1.0  
**Compatibility:** PHP 7.4+, MySQL 5.7+
