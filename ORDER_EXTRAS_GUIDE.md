# Order Extras Feature - Setup & Guide

## Overview
The Order Extras feature allows customers to add special items like greeting cards, small gifts, chocolates, candles, and balloons to their flower orders during checkout. Each extra has a professional image, description, and pricing. This increases the value of orders and improves customer satisfaction.

## What's Included

### New Database Tables
1. **`available_extras`** - Pre-configured extras available for selection
   - Contains all available extra items with pricing
   - Supports English and Kurdish names & descriptions
   - Includes icons and display order

2. **`order_extras`** - Stores extras selected for specific orders
   - Links extras to orders
   - Tracks quantity and unit price
   - Supports multiple extra types

### Available Extra Types
- **Greeting Cards** - Standard and luxury options
- **Small Gifts** - Scented candles, chocolate boxes, gift boxes with ribbon
- **Chocolate Boxes** - Premium chocolate assortments
- **Scented Candles** - Rose, lavender, and other fragrances
- **Balloons** - Helium balloon sets in various quantities

## Installation Steps

### Step 1: Run the Database Migration
Open your browser and navigate to:
```
http://localhost/Flower-Store/database/run_order_extras_migration.php
```

You should see a success message like:
```
✓ Order Extras Migration Completed Successfully!

✓ Created tables:
  - order_extras (stores extras selected for orders)
  - available_extras (pre-configured extras available for selection)

✓ Inserted 10 default extras
```

### Step 2: Verify Installation
The following has been automatically set up:

✓ Database schema created  
✓ 10 Default extras inserted  
✓ Translations added (English & Kurdish)  
✓ Checkout page updated with UI  
✓ Order processing updated to handle extras  
✓ Helper functions added  

## Features

### For Customers
- **Easy Selection**: View all available extras during checkout
- **Clear Pricing**: See the price for each extra item
- **Product Images**: See professional images of each extra item
- **Visual Feedback**: Selected items show a highlight
- **Updated Total**: Grand total automatically updates when extras are selected
- **Multiple Options**: Choose from 5 different extra categories
- **Descriptions**: Read detailed descriptions of each extra
- **Bilingual Support**: Full support in English and Kurdish

### For Admins/Developers
- **Database Functions**: Helper functions to retrieve and manage extras
  - `getAvailableExtras()` - Get all active extras
  - `getExtraById()` - Get a specific extra
  - `getExtraName()` - Get localized extra name
  - `getExtraDescription()` - Get localized description

- **Image Support**: Each extra can have a professional product image
  - Images stored in `uploads/extras/` folder
  - Fallback to icon if image missing
  - Responsive image display

- **Customizable**: Easy to add, edit, or remove extras from the database
- **Translations**: All UI text is translatable

## Image Support

The extras feature includes full image support for each item:

### How Images Work
1. **Display**: Images show in the checkout extras section
2. **Fallback**: If image URL is missing, the app shows a nice icon instead
3. **Responsive**: Images scale correctly on all devices
4. **Optional**: You don't need to add images immediately

### Where to Store Images
Store all extra images in: `uploads/extras/` folder

### Image Naming
Each default extra has a recommended filename:
- `greeting-card-standard.jpg`
- `greeting-card-luxury.jpg`
- `candle-scented.jpg`
- `chocolate-box.jpg`
- `gift-box-ribbon.jpg`
- `chocolate-premium.jpg`
- `candle-rose.jpg`
- `candle-lavender.jpg`
- `balloons-5.jpg`
- `balloons-10.jpg`

### Image Specifications
- **Format**: JPG or PNG
- **Size**: Recommended 300x300px minimum (500x500px ideal)
- **File Size**: Keep under 200KB for fast loading
- **Quality**: Use high-quality product photos for best results

### Adding Images to Extras
To add an image to an extra, update the database:
```sql
UPDATE available_extras 
SET image_url = 'uploads/extras/your-image.jpg' 
WHERE id = 1;
```

## About the Pre-configured Extras

### Greeting Cards
- **Standard Greeting Card** - $2.99
  - Beautiful greeting card with personal message
- **Luxury Greeting Card** - $4.99
  - Premium quality with embossing

### Small Gifts
- **Scented Candle** - $5.99
- **Chocolate Box** - $7.99
- **Gift Box with Ribbon** - $3.99

### Other Options
- **Premium Chocolate Assortment** - $12.99
- **Rose Scented Candle** - $6.99
- **Lavender Candle** - $6.99
- **Helium Balloon Set (5pcs)** - $8.99
- **Premium Balloon Combo (10pcs)** - $13.99

## Database Management

### Query the Available Extras
```sql
SELECT * FROM available_extras WHERE is_active = TRUE;
```

### Add a New Extra
```sql
INSERT INTO available_extras (
    extra_type, 
    name_en, 
    name_ku, 
    description_en, 
    description_ku, 
    price, 
    icon, 
    sort_order
) VALUES (
    'greeting_card',
    'Birthday Greeting Card',
    'کاغەزی جەژنی لەدایکبوون',
    'Special birthday greeting card',
    'کاغەزی تایبەتی جەژنی لەدایکبوون',
    3.49,
    'fas fa-envelope',
    3
);
```

### Update Extra Pricing
```sql
UPDATE available_extras SET price = 5.99 WHERE id = 1;
```

### Deactivate an Extra
```sql
UPDATE available_extras SET is_active = FALSE WHERE id = 1;
```

## How It Works

### During Checkout
1. Customer adds flowers to cart
2. Customer proceeds to checkout
3. On the checkout page, they see "Add Extras" section
4. They can select any combination of available extras
5. The total price automatically updates
6. They complete the order with selected extras

### Order Storage
When an order is placed:
1. The main order is created in the `orders` table
2. Order items (flowers) are saved in `order_items` table
3. Selected extras are saved in `order_extras` table
4. The grand total includes: product prices + delivery fee + extras total

### Admin View (Future Enhancement)
Admins can view order details including:
- What extras were included with each order
- Total spent on extras
- Customer preferences for extras

## Pricing Details

The order total calculation includes:
```
Grand Total = Product Total + Delivery Fee + Extras Total
```

For example:
- Flowers: $45.00
- Delivery: $5.00
- Greeting Card: $2.99
- Scented Candle: $5.99
- **Final Total: $58.98**

## Translation Support

All UI text is available in:
- **English** - Complete translations
- **Kurdish (Sorani)** - Complete translations

### Available Translation Keys
All new translation strings follow the naming convention:
- `add_extras` - "Add Extras"
- `extras_total` - "Extras Total"
- `greeting_cards` - "Greeting Cards"
- `small_gifts` - "Small Gifts"
- `chocolate_boxes` - "Chocolate Boxes"
- `scented_candles` - "Scented Candles"
- `balloons` - "Balloons"
- And more...

## Customization

### Adding New Extras
1. Navigate to your database
2. Insert a new row in the `available_extras` table
3. Fill in the details (name in English and Kurdish, price, description, etc.)
4. Set `is_active = TRUE` to make it available
5. The new extra will automatically appear on the checkout page

### Removing Extras
Instead of deleting, set `is_active = FALSE` to hide extras:
```sql
UPDATE available_extras SET is_active = FALSE WHERE id = 5;
```

### Modifying Prices
Update the `price` field for any extra:
```sql
UPDATE available_extras SET price = 7.99 WHERE name_en = 'Luxury Greeting Card';
```

## Technical Details

### Database Schema
```sql
CREATE TABLE available_extras (
    id INT PRIMARY KEY AUTO_INCREMENT,
    extra_type ENUM('greeting_card', 'small_gift', 'chocolate_box', 'candle', 'balloons'),
    name_en VARCHAR(255) NOT NULL,
    name_ku VARCHAR(255) NOT NULL,
    description_en TEXT,
    description_ku TEXT,
    price DECIMAL(10,2) NOT NULL,
    image_url VARCHAR(255),
    icon VARCHAR(50),
    is_active BOOLEAN,
    sort_order INT,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);

CREATE TABLE order_extras (
    id INT PRIMARY KEY AUTO_INCREMENT,
    order_id INT NOT NULL,
    extra_type ENUM(...),
    extra_name_en VARCHAR(255) NOT NULL,
    extra_name_ku VARCHAR(255) NOT NULL,
    quantity INT,
    unit_price DECIMAL(10,2) NOT NULL,
    created_at TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id)
);
```

### Image URL Column
The `image_url` column stores the path to the extra's product image:
- **Type**: VARCHAR(255)
- **Optional**: Yes - images are not required
- **Example**: `uploads/extras/greeting-card-standard.jpg`
- **Fallback**: If no image URL, the icon is displayed instead

## Testing

### Test Scenario 1: Add Extras to Order
1. Login as a customer
2. Add flowers to cart
3. Go to checkout
4. Select 2-3 extras
5. Verify the total updates correctly
6. Complete the order
7. Verify the order was saved with extras

### Test Scenario 2: View Order Details
1. Go to order history
2. View an order with extras
3. Verify extras are displayed correctly

## Troubleshooting

### Extras not showing on checkout
- Verify the migration was run successfully
- Check that `available_extras` table has entries with `is_active = TRUE`
- Clear browser cache

### Prices not calculating correctly
- Verify all extras have valid prices in the database
- Check that JavaScript is enabled in browser
- Verify the `data-extra-price` attributes on checkboxes

### Order not saving with extras
- Check database error logs
- Verify `order_extras` table exists
- Ensure order processing code is using the latest checkout.php

## Files Modified/Created

### New Files
- `database/add_order_extras.sql` - Migration SQL script
- `database/run_order_extras_migration.php` - Migration runner

### Modified Files
- `checkout.php` - Added extras UI and processing logic
- `src/functions.php` - Added helper functions
- `src/translations/en.php` - Added English translations
- `src/translations/ku.php` - Added Kurdish translations

## Future Enhancements

Possible additions:
1. **Admin Panel** - Manage extras through UI
2. **Analytics** - Track which extras are most popular
3. **Cart Persistence** - Remember selected extras in cart
4. **Customization** - Allow customers to add custom messages to extras
5. **Seasonal Options** - Add seasonal extras automatically
6. **Combo Deals** - Discounted bundles of extras

## Support

For issues or questions:
1. Check the database migration ran successfully
2. Verify all files were updated correctly
3. Check browser console for JavaScript errors
4. Review PHP error logs for database errors

---

**Feature Version:** 1.0  
**Status:** Ready for Production  
**Date Added:** March 2026
