# Super Admin System Documentation

## Overview
The Super Admin system provides complete control over the Bloom & Vine flower store, including comprehensive reporting, user management, admin management, and system settings.

## Installation

### 1. Database Migration
Run the SQL migration file to add super admin support:

```sql
-- Run this file:
database/super_admin_migration.sql
```

This will:
- Add `super_admin` role to the users table
- Create a default super admin user
- Create `system_settings` table
- Create `activity_log` table

### 2. Default Super Admin Credentials
- **Email:** superadmin@bloomvine.com
- **Password:** superadmin123

**⚠️ IMPORTANT:** Change the password immediately after first login!

## Features

### 1. Super Admin Dashboard (`admin/super_admin_dashboard.php`)
- Complete system overview
- Real-time statistics (total orders, revenue, products, customers)
- Daily, weekly, and monthly revenue tracking
- Recent orders and activity logs
- Quick access to all management functions

### 2. Reporting System (`admin/super_admin_reports.php`)
- **Daily Reports:** View sales and analytics for the current day
- **Weekly Reports:** View sales and analytics for the current week
- **Monthly Reports:** View sales and analytics for the current month
- **Yearly Reports:** View sales and analytics for the current year

**Report Features:**
- Sales trend charts (interactive Chart.js)
- Sales breakdown table
- Top products analysis
- Customer statistics
- Revenue and order metrics

### 3. User Management (`admin/super_admin_users.php`)
- View all users (customers, admins, super admins)
- Search and filter users by role
- View user statistics (orders, total spent)
- Delete users (except super admins)
- Pagination support

### 4. Admin Management (`admin/super_admin_admins.php`)
- Create new admin users
- View all administrators
- Delete admin users (super admins are protected)
- View admin statistics

### 5. System Settings (`admin/super_admin_settings.php`)
- **General Settings:**
  - Site name
  - Site email
  - Currency symbol

- **Financial Settings:**
  - Tax rate percentage
  - Default shipping cost

- **System Settings:**
  - Maintenance mode toggle
  - Maximum upload size

- **Database Information:**
  - Quick stats (total users, products, orders)

## Access Control

### Super Admin Functions
- `isSuperAdmin()` - Check if current user is super admin
- `requireSuperAdmin()` - Require super admin access (redirects if not)

### Admin Functions
- `isAdmin()` - Check if current user is admin or super admin
- `requireAdmin()` - Require admin access (redirects if not)

## Activity Logging

All super admin actions are automatically logged:
- User creation/deletion
- Admin creation/deletion
- System settings updates
- User logins

View logs in the dashboard's "Recent Activity" section.

## Database Tables

### `system_settings`
Stores system-wide configuration:
- `setting_key` - Unique setting identifier
- `setting_value` - Setting value
- `setting_type` - Type (string, number, boolean, json)
- `updated_by` - User who last updated
- `updated_at` - Last update timestamp

### `activity_log`
Tracks all super admin actions:
- `user_id` - User who performed the action
- `action` - Action type (e.g., 'user_deleted', 'admin_created')
- `entity_type` - Type of entity affected
- `entity_id` - ID of entity affected
- `description` - Additional details
- `ip_address` - IP address of user
- `user_agent` - Browser user agent
- `created_at` - Timestamp

## Functions Reference

### `logActivity($action, $entityType, $entityId, $description)`
Log an activity for tracking.

### `getSystemSetting($key, $default)`
Get a system setting value.

### `setSystemSetting($key, $value, $type)`
Set a system setting value.

### `getSalesReport($period)`
Get sales report data for a period (day, week, month, year).

## Security Features

1. **CSRF Protection:** All forms use CSRF tokens
2. **Role-Based Access:** Super admin functions are protected
3. **Activity Logging:** All actions are logged with IP and user agent
4. **Password Hashing:** Uses ARGON2ID for secure password storage
5. **Input Sanitization:** All inputs are sanitized

## Usage Examples

### Creating a New Admin
1. Go to Admin Management
2. Fill in the form (name, email, password)
3. Click "Create Admin"

### Viewing Reports
1. Go to Super Admin Dashboard
2. Click on "Daily Reports", "Weekly Reports", or "Monthly Reports"
3. View charts and statistics

### Managing Users
1. Go to User Management
2. Use search/filter to find users
3. View statistics or delete users

### Updating System Settings
1. Go to System Settings
2. Update any setting
3. Click "Save Settings"

## Navigation

Super Admin can access:
- **Super Admin Dashboard** - Main control panel
- **Daily/Weekly/Monthly Reports** - Analytics and reporting
- **User Management** - Manage all users
- **Admin Management** - Manage administrators
- **System Settings** - Configure system
- **Regular Admin Dashboard** - Standard admin functions
- **Products/Categories** - Product management
- **Store Front** - View the public store

## Notes

- Super admins cannot be deleted through the interface
- All super admin actions are logged
- Reports use Chart.js for interactive charts
- System settings are cached and updated in real-time
- Activity logs are retained for audit purposes

## Troubleshooting

### Cannot access super admin dashboard
- Ensure you've run the database migration
- Check that your user has `super_admin` role
- Verify session is active

### Reports showing no data
- Check that orders exist in the database
- Verify date ranges are correct
- Check database connection

### Settings not saving
- Verify CSRF token is valid
- Check database permissions
- Review error logs

## Support

For issues or questions, check:
- Database error logs
- PHP error logs
- Activity log table for recent actions

