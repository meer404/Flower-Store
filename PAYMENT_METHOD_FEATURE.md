# Payment Method Feature

## Overview
The order system has been enhanced with full payment method support for Visa and Mastercard. Customers can now securely enter their card details during checkout, and the system stores payment information (securely, only last 4 digits) for order tracking.

## What's New

### 1. Database Changes
- Added `payment_method` column (ENUM: 'visa', 'mastercard')
- Added `card_last_four` column (stores last 4 digits of card)
- Added `cardholder_name` column
- Added `card_expiry_month` and `card_expiry_year` columns
- Added index on `payment_method` for better query performance

### 2. Checkout Process
- **Payment Method Selection**: Visual radio buttons for Visa and Mastercard
- **Card Details Form**:
  - Card Number (with automatic formatting)
  - Cardholder Name
  - Expiry Date (Month/Year dropdowns)
  - CVV (3-4 digits)
- **Real-time Validation**:
  - Card number format validation
  - Payment method-specific validation (Visa starts with 4, Mastercard 51-55)
  - Expiry date validation (cannot be expired)
  - CVV format validation

### 3. Security Features
- **Never stores full card number** - only last 4 digits
- **Never stores CVV** - only validated, not stored
- **Automatic formatting** - card numbers formatted with spaces
- **Client-side validation** - immediate feedback
- **Server-side validation** - secure validation on submission

### 4. Order Display
- Payment method shown in:
  - Order details page (with card brand icon)
  - User account order history
  - Admin dashboard orders table
- Shows:
  - Payment method type (Visa/Mastercard)
  - Last 4 digits of card
  - Cardholder name
  - Expiry date

### 5. Translations
- Added translations for all payment-related text in English and Kurdish
- All user-facing text is properly localized

## Installation

### Step 1: Run Database Migration
You have two options:

**Option A: Run the PHP migration script (Recommended)**
```bash
php database/run_payment_method_migration.php
```

**Option B: Run the SQL file directly**
```sql
-- Execute the SQL in database/add_payment_method.sql
-- Or run it through phpMyAdmin or your database tool
```

### Step 2: Verify Installation
1. Go to the checkout page
2. You should see payment method selection (Visa/Mastercard)
3. Fill in card details
4. Place an order
5. Check order details to see payment method displayed

## Features

### User Features
- **Visual Payment Selection**: Choose between Visa and Mastercard with branded buttons
- **Card Number Formatting**: Automatic spacing (1234 5678 9012 3456)
- **Real-time Validation**: Immediate feedback on card number format
- **Secure Storage**: Only last 4 digits stored, CVV never stored
- **Order History**: View payment method for all past orders

### Admin Features
- **Payment Tracking**: See payment method for all orders
- **Card Information**: View last 4 digits and cardholder name
- **Better Analytics**: Filter orders by payment method

## Technical Details

### Validation Rules

#### Card Number
- Visa: Must start with 4, 13-16 digits
- Mastercard: Must start with 51-55, 16 digits
- Automatic formatting with spaces every 4 digits

#### Expiry Date
- Cannot be in the past
- Month: 01-12
- Year: Current year to 10 years ahead

#### CVV
- 3-4 digits only
- Numeric characters only
- Never stored in database

### Security Best Practices
- ✅ Full card number never stored
- ✅ CVV never stored
- ✅ Only last 4 digits stored for reference
- ✅ Cardholder name stored for verification
- ✅ Expiry date stored for reference
- ✅ Server-side validation in addition to client-side

### Database Schema
```sql
ALTER TABLE `orders`
ADD COLUMN `payment_method` ENUM('visa', 'mastercard') DEFAULT NULL,
ADD COLUMN `card_last_four` VARCHAR(4) DEFAULT NULL,
ADD COLUMN `cardholder_name` VARCHAR(255) DEFAULT NULL,
ADD COLUMN `card_expiry_month` TINYINT(2) DEFAULT NULL,
ADD COLUMN `card_expiry_year` SMALLINT(4) DEFAULT NULL;

CREATE INDEX `idx_orders_payment_method` ON `orders` (`payment_method`);
```

## Files Modified

1. **checkout.php** - Added payment method selection and card details form
2. **order_details.php** - Display payment method with card information
3. **account.php** - Show payment method in order history
4. **admin/dashboard.php** - Display payment method in orders table
5. **src/translations/en.php** - Added English translations
6. **src/translations/ku.php** - Added Kurdish translations

## Files Created

1. **database/add_payment_method.sql** - SQL migration file
2. **database/run_payment_method_migration.php** - PHP migration script

## Usage Example

### For Customers
1. Add items to cart
2. Go to checkout
3. Fill in shipping address
4. Select delivery date
5. **Select payment method** (Visa or Mastercard)
6. **Enter card details**:
   - Card number (auto-formatted)
   - Cardholder name
   - Expiry date
   - CVV
7. Place order
8. Order is automatically marked as "paid"
9. View order details to see payment confirmation

### For Admins
1. Go to Admin Dashboard
2. View orders table
3. See payment method column with:
   - Card brand icon
   - Last 4 digits
4. Click on order to see full payment details

## Card Number Examples (for testing)

### Visa Test Numbers
- 4111 1111 1111 1111 (Valid format)
- 4000 0000 0000 0002 (Valid format)

### Mastercard Test Numbers
- 5555 5555 5555 4444 (Valid format)
- 5105 1051 0510 5100 (Valid format)

**Note**: These are format examples. Actual payment processing would require integration with a payment gateway.

## Payment Status

When a customer completes checkout with card details:
- Payment status is automatically set to **"paid"**
- This allows immediate order processing
- Admin can still manually update payment status if needed

## Future Enhancements (Optional)
- Integration with payment gateways (Stripe, PayPal, etc.)
- Saved payment methods for returning customers
- Multiple payment methods (PayPal, bank transfer, etc.)
- Payment method icons in more locations
- Payment analytics dashboard
- Refund processing
- Payment method change requests

## Security Notes

⚠️ **Important**: This implementation stores minimal card information for reference only. For production use with real payments, you should:

1. **Integrate with a Payment Gateway** (Stripe, PayPal, Square, etc.)
2. **Use PCI-DSS compliant solutions** - Never handle full card numbers yourself
3. **Use tokenization** - Store payment tokens instead of card details
4. **Implement SSL/TLS** - Encrypt all data in transit
5. **Regular security audits** - Ensure compliance with security standards

The current implementation is suitable for:
- Demo/test environments
- Educational purposes
- Systems that will integrate with payment gateways later

## Support
If you encounter any issues:
1. Verify the database migration ran successfully
2. Check that payment method columns exist in the `orders` table
3. Clear browser cache if payment form doesn't appear
4. Check PHP error logs for validation errors
5. Ensure JavaScript is enabled for client-side validation

