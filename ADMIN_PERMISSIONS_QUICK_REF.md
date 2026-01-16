# Admin Permissions - Quick Reference Card

## 🚀 First Steps (Do This First!)

```
1. Run Migration:
   → http://yoursite.com/flower-store/database/run_admin_permissions_migration.php

2. Create Admin with Permissions:
   → Login as Super Admin
   → Go to: Admin → Super Admin → Admins
   → Click "Create New Admin"
   → Select permissions with checkboxes
   → Save

3. Test It:
   → Login as new admin
   → Try accessing restricted pages
   → Should get "Access denied" if no permission
```

## 📋 Permission Reference

| Name | Code | What It Does |
|------|------|-------------|
| View Dashboard | `view_dashboard` | See admin dashboard |
| Manage Products | `manage_products` | Add/edit/delete products |
| Manage Categories | `manage_categories` | Manage categories |
| View Orders | `view_orders` | View orders |
| Update Orders | `manage_orders` | Change order status |
| View Reports | `view_reports` | Access reports |
| View Users | `view_users` | See customer list |
| Manage Users | `manage_users` | Ban/modify users |
| System Settings | `system_settings` | System configuration |

## 💻 Code Usage

### Check Permission (Returns true/false)
```php
if (hasPermission('manage_products')) {
    echo "Can manage products!";
}
```

### Require Permission (Denies if missing)
```php
// In admin page after requireAdmin()
requirePermission('manage_products');
// Page stops here if no permission (403 error)
```

### Get All Permissions for Admin
```php
$perms = getAdminPermissions($admin_id);
// Returns: ['view_dashboard', 'manage_products', ...]
```

### Assign Permissions
```php
setAdminPermissions($admin_id, [
    'view_dashboard',
    'manage_products',
    'view_orders'
]);
```

## 🛡️ Security Rules

✅ **Super admins** - Always have all permissions  
✅ **Regular admins** - Only get assigned permissions  
✅ **No permissions** - Default for new admins  
✅ **Access denied** - 403 error on restricted pages  
✅ **Sidebar filtering** - Hidden if no permission  
✅ **Audit logging** - All changes tracked  

## 📊 Common Permission Sets

### Product Manager
```
☑ View Dashboard
☑ Manage Products
☑ Manage Categories
☐ Everything else
```

### Order Specialist
```
☑ View Dashboard
☑ View Orders
☑ Update Order Status
☐ Everything else
```

### Report Analyst
```
☑ View Dashboard
☑ View Reports
☑ View Orders
☐ Everything else
```

### Full Admin (except Super)
```
☑ All 9 permissions
```

### Read-Only
```
☑ View Dashboard
☑ View Orders
☑ View Reports
☑ View Users
☐ No modify permissions
```

## 🔧 Adding Permissions to Pages

To protect a new admin page:

```php
<?php
require_once __DIR__ . '/../src/functions.php';

requireAdmin();                          // Must be admin
requirePermission('permission_name');    // Add this line!

// Rest of page code...
?>
```

## ⚠️ Access Denied Screen

When admin lacks permission:

```
Error 403: Access Denied
You do not have permission to access this resource.
Required Permission: manage_products
Contact your Super Admin to request access.
```

## 🐛 Debugging

### Check What Permissions Admin Has
```php
echo json_encode(getAdminPermissions($user_id));
// Output: ["view_dashboard", "manage_products"]
```

### Check If Specific Permission Exists
```php
var_dump(hasPermission('manage_products', $user_id));
// Output: bool(true) or bool(false)
```

### View Database Permissions
```sql
SELECT admin_id, permission FROM admin_permissions ORDER BY admin_id;
```

## 📱 UI Elements

### Creating Admin - Permission Checkboxes
```
☑ View Dashboard
☐ Manage Products
☐ Manage Categories
☐ View Orders
☐ Update Order Status
☐ View Reports
☐ View Users
☐ Manage Users
☐ System Settings
```

### Editing Admin - Permission Modal
```
[Edit Permissions] button
→ Modal opens
→ Shows all permissions with current state
→ Click checkboxes to change
→ [Save] or [Cancel]
```

### Admin List - Permission Tags
```
Sarah Manager
Permissions: 3/9
✓ Manage Products  ✓ Manage Categories  ✓ View Dashboard
```

## 🔗 Important Files

| File | Purpose |
|------|---------|
| `src/functions.php` | Permission functions |
| `admin/super_admin_admins.php` | UI for manage permissions |
| `admin/sidebar.php` | Menu filtering |
| `database/add_admin_permissions.sql` | Database schema |
| `database/run_admin_permissions_migration.php` | Run migration |

## 📚 Documentation Files

- `ADMIN_PERMISSIONS_SETUP.md` - Quick setup guide
- `ADMIN_PERMISSIONS_GUIDE.md` - Complete guide
- `ADMIN_PERMISSIONS_UI_GUIDE.md` - UI/UX guide
- `ADMIN_PERMISSIONS_COMPLETE.md` - Full summary

## ✅ Verification Checklist

- [ ] Migration ran (no errors)
- [ ] Can create admin with permissions
- [ ] Admin sees restricted pages as "Access denied"
- [ ] Sidebar hides menu items for restricted admins
- [ ] Permission changes save correctly
- [ ] Super admin still has full access
- [ ] Permission tags display under admins
- [ ] Activity log shows permission changes
- [ ] Edit permissions modal works
- [ ] Multiple permissions can be selected

## 🆘 Quick Troubleshooting

| Problem | Quick Fix |
|---------|-----------|
| "Access denied" on all pages | Check admin_permissions table, ensure permissions assigned |
| Sidebar still shows restricted items | Clear browser cache, hard refresh (Ctrl+Shift+R) |
| Can't create admin | Check database, ensure users table exists |
| Migration fails | Verify database connection and permissions |
| Permission changes not working | Refresh page, check database entries |
| Super admin blocked | Verify role = 'super_admin' in users table |

## 📞 Getting Help

1. Check `ADMIN_PERMISSIONS_GUIDE.md` for full documentation
2. Review `database/add_admin_permissions.sql` for schema
3. Check `src/functions.php` for permission functions
4. Enable error logging to debug issues
5. Test in browser console with permission functions

## 🎯 Next Steps

1. ✅ Run migration
2. ✅ Create test admin with limited permissions
3. ✅ Test access restrictions
4. ✅ Train super admins on permission system
5. ✅ Create permission templates for your roles
6. ✅ Review existing admins and assign permissions
7. ✅ Monitor activity logs monthly

---

**Remember:** 
- Super admins always have full access
- Regular admins get only what you give them
- Always test permissions before production
- Keep an audit log of permission changes
- Review permissions quarterly

**Version:** 1.0 | **Date:** January 16, 2026
