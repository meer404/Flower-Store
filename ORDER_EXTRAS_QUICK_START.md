# Order Extras Feature - Quick Start

## 🚀 Get Started in 2 Minutes

### Step 1: Run the Migration (ONE TIME ONLY)
1. Open your browser
2. Go to: `http://localhost/Flower-Store/database/run_order_extras_migration.php`
3. You should see: ✓ Order Extras Migration Completed Successfully!

**That's it!** The database is now set up with:
- ✓ 10 pre-configured extras (cards, gifts, candles, balloons)
- ✓ All pricing set up
- ✓ English and Kurdish support

### Step 2: Test It Out (Optional)
1. Login to your store as a customer
2. Add flowers to your cart
3. Go to checkout
4. You'll see a new "Add Extras" section with options like:
   - 🎁 Greeting Cards ($2.99 - $4.99) with images
   - 🎀 Small Gifts ($3.99 - $7.99) with images
   - 🍫 Chocolate Boxes ($12.99) with images
   - 🕯️ Scented Candles ($5.99 - $6.99) with images
   - 🎈 Balloons ($8.99 - $13.99) with images
5. Each extra shows a professional product image
6. Select any extras
7. Watch the total price update automatically
8. Complete your order

**Note:** Images are optional - if you haven't added images yet, nice icons will show instead.

**Done!** Your customers can now add extras to their orders!

## 📋 What You Got

### Features
✅ Beautiful checkout UI for selecting extras  
✅ Real-time price calculation  
✅ Multiple extra types and options  
✅ Full English & Kurdish support  
✅ Mobile responsive design  
✅ Database integration  

### Pre-configured Items (10 Total)
| Item | Price | Type |
|------|-------|------|
| Standard Greeting Card | $2.99 | Card |
| Luxury Greeting Card | $4.99 | Card |
| Scented Candle | $5.99 | Gift |
| Chocolate Box | $7.99 | Gift |
| Gift Box with Ribbon | $3.99 | Gift |
| Premium Chocolate | $12.99 | Chocolate |
| Rose Candle | $6.99 | Candle |
| Lavender Candle | $6.99 | Candle |
| Balloon Set (5) | $8.99 | Balloons |
| Premium Balloons (10) | $13.99 | Balloons |

## 🎯 What Customers Will See

### During Checkout
1. All their flowers in order summary
2. **NEW** "Add Extras" section with:
   - Category tabs (Cards, Gifts, Candles, etc.)
   - Each extra with name, description, and price
   - Easy checkboxes to select items
   - Visual feedback when selected
3. **NEW** Extra subtotal displayed
4. Updated grand total automatically

### Example Order Calculation
```
Flowers: $45.00
Delivery: $5.00
Greeting Card: $2.99
Candle: $5.99
─────────────────
NEW TOTAL: $58.98
```

## 📊 Where to Find Information

- **Full Guide**: `ORDER_EXTRAS_GUIDE.md`
- **Database Files**: `database/add_order_extras.sql`
- **Translations**: `src/translations/en.php` and `ku.php`
- **Main Code**: `checkout.php` and `src/functions.php`

## 🔧 Quick Customization

### Add Images to Extras (Optional but Recommended!)
1. Create images for your extras (300x300px recommended)
2. Save them in: `uploads/extras/` folder
3. Name them: `greeting-card-standard.jpg`, `greeting-card-luxury.jpg`, etc.
4. Images will automatically appear on the checkout page!

**Free images:** You can find free product images on Unsplash, Pexels, or Pixabay

### Add a New Extra
```sql
INSERT INTO available_extras (
    extra_type, name_en, name_ku, 
    description_en, description_ku, 
    price, sort_order
) VALUES (
    'greeting_card',
    'My Custom Card',
    'کاغەزی تایبەتی من',
    'Custom card description',
    'وێنەکاری کاغەزی تایبەتی',
    2.99,
    4
);
```

### Change a Price
```sql
UPDATE available_extras SET price = 3.49 WHERE id = 1;
```

### Hide an Extra (Don't Delete)
```sql
UPDATE available_extras SET is_active = FALSE WHERE id = 1;
```

### Update an Extra's Image
```sql
UPDATE available_extras SET image_url = 'uploads/extras/new-image.jpg' WHERE id = 1;
```

## 🎨 How It's Styled

The extras section uses the same luxury design as the rest of the site:
- ✨ Clean, modern checkboxes with product images
- 🎯 Hover effects for better UX
- 💎 Consistent color scheme
- 📱 Fully responsive for mobile
- 🌍 Right-to-left support for Kurdish

## 📞 File Reference

| File | Purpose |
|------|---------|
| `checkout.php` | Checkout page with extras UI |
| `src/functions.php` | Helper functions for extras |
| `database/add_order_extras.sql` | SQL schema |
| `database/run_order_extras_migration.php` | Migration runner |
| `ORDER_EXTRAS_GUIDE.md` | Detailed documentation |

## ✅ Verification Checklist

After running the migration, you should have:
- [ ] `available_extras` table created
- [ ] `order_extras` table created
- [ ] 10 default extras inserted
- [ ] All translations added
- [ ] Checkout page updated

## 🐛 Quick Troubleshooting

| Issue | Solution |
|-------|----------|
| Extras not showing | Check migration ran successfully |
| Prices not updating | Clear cache, check JavaScript console |
| Order not saving | Check PHP error logs |
| Text in wrong language | Refresh page, check language setting |

## 🎓 Next Steps

1. ✅ Migration complete
2. ✅ Test with a customer account
3. ✅ Adjust prices if needed (see database section)
4. ✅ Add custom extras (see customization section)
5. ✅ Monitor which extras are popular

## 💡 Tips

- **Mobile Testing**: Test on mobile devices to see responsive design
- **Text Length**: Keep extra names under 30 characters
- **Pricing**: Use round numbers ($2.99, $4.99) for better conversions
- **Ordering**: Use `sort_order` to arrange extras how you prefer
- **Descriptions**: Add compelling descriptions in both languages

---

🎉 **You're all set!** Your flower store now has premium order extras!

For detailed information, see `ORDER_EXTRAS_GUIDE.md`
