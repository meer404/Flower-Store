# Order Extras Images - Setup Guide

## Quick Image Setup

### Step 1: Create the Images Folder
Make sure this folder exists in your project:
```
Flower-Store/uploads/extras/
```

### Step 2: Prepare Your Images
For best results, use images that are:
- **Size**: 300-500 pixels wide (square works best)
- **Format**: JPG or PNG
- **File Size**: Under 200KB each
- **Quality**: High-quality product photos

### Step 3: Add Images

Rename your images to match the defaults and place them in `uploads/extras/`:

| Image Name | Product |
|------------|---------|
| `greeting-card-standard.jpg` | Standard Greeting Card ($2.99) |
| `greeting-card-luxury.jpg` | Luxury Greeting Card ($4.99) |
| `candle-scented.jpg` | Scented Candle ($5.99) |
| `chocolate-box.jpg` | Chocolate Box ($7.99) |
| `gift-box-ribbon.jpg` | Gift Box with Ribbon ($3.99) |
| `chocolate-premium.jpg` | Premium Chocolate ($12.99) |
| `candle-rose.jpg` | Rose Scented Candle ($6.99) |
| `candle-lavender.jpg` | Lavender Candle ($6.99) |
| `balloons-5.jpg` | Balloon Set 5pcs ($8.99) |
| `balloons-10.jpg` | Balloon Set 10pcs ($13.99) |

### Step 4: Refresh Your Site
That's it! The images will automatically appear on the checkout page.

## Finding Free Images

You can get free product images from:

### Best Stock Photo Sites
1. **Unsplash** (unsplash.com)
   - 2.8M+ free photos
   - High quality
   - No credit required

2. **Pexels** (pexels.com)
   - 1000+ free photos
   - HD quality
   - Free commercial use

3. **Pixabay** (pixabay.com)
   - 3.7M+ free images
   - Various categories
   - Public domain

### Search Terms
- "greeting card product photo"
- "chocolate box luxury"
- "scented candle gift"
- "helium balloons party"
- "luxury gift box"
- "rose candle"
- "lavender candle"

## Customizing Images for Your Extras

### Edit or Update Images
If you want to change an image:

1. Replace the file in `uploads/extras/` folder
2. Use the same filename
3. Refresh your browser cache (Ctrl+Shift+Del on Windows)

### Create Custom Images
You can create custom images using:
- **Canva** (canva.com) - Easy design tool
- **Photoshop** - Professional editing
- **GIMP** - Free alternative to Photoshop

## Database - Manual Image URL Updates

If you need to update image URLs in the database:

### Update a Single Extra's Image
```sql
UPDATE available_extras 
SET image_url = 'uploads/extras/new-filename.jpg' 
WHERE id = 1;
```

### Check All Image URLs
```sql
SELECT id, name_en, image_url FROM available_extras;
```

### Clear All Images (Use Icons)
```sql
UPDATE available_extras SET image_url = NULL;
```

## Troubleshooting Images

### Images Not Showing?

**Check 1: File Path**
- Go to: `http://localhost/Flower-Store/uploads/extras/filename.jpg`
- If you see a 404 error, the file doesn't exist or wrong folder

**Check 2: File Name Spelling**
- Make sure filenames match exactly
- SQL UPDATE should match the filename in the folder

**Check 3: Refresh Cache**
- Press Ctrl+Shift+Del to clear cache
- Or use an incognito window

**Check 4: File Permissions**
- Ensure the `uploads/extras/` folder is readable
- Files should have 644 permissions

### Images Look Blurry?
- Use higher resolution images (500x500px or larger)
- Compress without losing quality using TinyPNG.com

### Images Take Too Long to Load?
- Reduce file size (keep under 200KB)
- Try JPG format instead of PNG
- Use tools like TinyPNG or ImageOptim

## Image Display Rules

The app displays images with these rules:

1. ✅ **If image_url exists and file exists** → Shows the image
2. ✅ **If image_url is empty** → Shows the icon instead
3. ✅ **If image_url points to missing file** → Shows the icon (graceful fallback)

This means images are totally optional! If you don't add them, icons display automatically.

## Performance Tips

### Optimize Image Load Time
1. **Compress images** - Use TinyPNG.com to compress
2. **Use WebP format** - Modern format, smaller file sizes
3. **Lazy load** - Browser automatically delays loading off-screen images

### File Size Targets
- JPG: 60-100KB per image
- PNG: 40-80KB per image
- WebP: 30-60KB per image

## Mobile Considerations

Images display beautifully on mobile:
- Automatically scales to fit device width
- Maintains aspect ratio
- Fast loading with optimized sizes

## Adding Custom Extras with Images

When adding a new extra to the database:

```sql
INSERT INTO available_extras (
    extra_type, 
    name_en, 
    name_ku, 
    description_en, 
    description_ku, 
    price, 
    image_url,
    icon,
    sort_order
) VALUES (
    'greeting_card',
    'Holiday Card',
    'کاغەزی جێژنی',
    'Special holiday greeting card',
    'کاغەزی تایبەتی جێژنی',
    3.99,
    'uploads/extras/holiday-card.jpg',
    'fas fa-envelope',
    4
);
```

## FAQ

**Q: Do I have to add images?**
A: No! The app works fine with just icons. Images are optional but recommended.

**Q: What size images should I use?**
A: 300-500px works great. Under 200KB file size for fast loading.

**Q: Can I use the same image for multiple extras?**
A: Yes! You can use the same image_url for different items if you want.

**Q: How do I change an image after uploading?**
A: Just replace the file in `uploads/extras/` with the same name and refresh.

**Q: Can I use external images (from another website)?**
A: Yes, set image_url to the full URL like `https://example.com/image.jpg`

**Q: What about copyright?**
A: Always check licenses. Stock sites like Unsplash are free for commercial use.

---

**Need help?** Check ORDER_EXTRAS_GUIDE.md for detailed information.
