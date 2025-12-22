# Delivery Date Feature

## Overview
The order system has been enhanced to allow users to select a specific delivery date for their flower orders. This makes the ordering process more powerful and gives customers control over when they receive their flowers.

## What's New

### 1. Database Changes
- Added `delivery_date` column to the `orders` table (DATE type)
- Added index on `delivery_date` for better query performance

### 2. Checkout Process
- Users can now select a delivery date during checkout
- Date picker with minimum date validation (must be at least tomorrow)
- Clear visual feedback and instructions

### 3. Order Display
- Delivery dates are shown in:
  - Order details page
  - User account order history
  - Admin dashboard orders table
- Smart status indicators showing:
  - Days until delivery (for future dates)
  - "Delivery Today" (for today)
  - "Delivered" (for past dates)

### 4. Translations
- Added translations for delivery date in both English and Kurdish
- All user-facing text is properly localized

## Installation

### Step 1: Run Database Migration
You have two options:

**Option A: Run the PHP migration script (Recommended)**
```bash
php database/run_delivery_date_migration.php
```

**Option B: Run the SQL file directly**
```sql
-- Execute the SQL in database/add_delivery_date.sql
-- Or run it through phpMyAdmin or your database tool
```

### Step 2: Verify Installation
1. Go to the checkout page
2. You should see a "Delivery Date" field with a date picker
3. Try placing an order with a delivery date
4. Check the order details to see the delivery date displayed

## Features

### User Features
- **Date Selection**: Choose any date starting from tomorrow
- **Visual Feedback**: See how many days until delivery
- **Order History**: View delivery dates for all past orders
- **Bilingual Support**: Works in both English and Kurdish

### Admin Features
- **Order Management**: See delivery dates for all orders in the dashboard
- **Status Tracking**: Visual indicators for delivery status
- **Better Planning**: Plan deliveries based on selected dates

## Technical Details

### Validation Rules
- Delivery date is required
- Must be at least 1 day in the future (tomorrow or later)
- Stored as DATE type in database

### Display Format
- Order details: Full date format (e.g., "January 15, 2024")
- Order history: Full date format with icon
- Admin dashboard: Compact format with status indicators

### Database Schema
```sql
ALTER TABLE `orders`
ADD COLUMN `delivery_date` DATE DEFAULT NULL AFTER `shipping_address`;

CREATE INDEX `idx_orders_delivery_date` ON `orders` (`delivery_date`);
```

## Files Modified

1. **checkout.php** - Added date picker and validation
2. **order_details.php** - Display delivery date with status
3. **account.php** - Show delivery dates in order history
4. **admin/dashboard.php** - Display delivery dates in orders table
5. **src/translations/en.php** - Added English translations
6. **src/translations/ku.php** - Added Kurdish translations

## Files Created

1. **database/add_delivery_date.sql** - SQL migration file
2. **database/run_delivery_date_migration.php** - PHP migration script

## Usage Example

### For Customers
1. Add items to cart
2. Go to checkout
3. Fill in shipping address
4. **Select delivery date** (new!)
5. Place order
6. View order details to see delivery date confirmation

### For Admins
1. Go to Admin Dashboard
2. View orders table
3. See delivery date column with status indicators
4. Click on order to see full details including delivery date

## Future Enhancements (Optional)
- Email notifications for upcoming deliveries
- Calendar view for delivery scheduling
- Delivery time slots (morning/afternoon)
- Recurring delivery options
- Delivery date change requests

## Support
If you encounter any issues:
1. Verify the database migration ran successfully
2. Check that the `delivery_date` column exists in the `orders` table
3. Clear browser cache if date picker doesn't appear
4. Check PHP error logs for any validation errors

