# Order Extras Images - Quick Reference

## 📋 Image Files Checklist

Place these images in: `uploads/extras/` folder

```
Flower-Store/
├── uploads/
│   └── extras/
│       ├── greeting-card-standard.jpg    ← Standard Card ($2.99)
│       ├── greeting-card-luxury.jpg      ← Luxury Card ($4.99)
│       ├── candle-scented.jpg            ← Scented Candle ($5.99)
│       ├── chocolate-box.jpg             ← Chocolate Box ($7.99)
│       ├── gift-box-ribbon.jpg           ← Gift Box ($3.99)
│       ├── chocolate-premium.jpg         ← Premium Chocolate ($12.99)
│       ├── candle-rose.jpg               ← Rose Candle ($6.99)
│       ├── candle-lavender.jpg           ← Lavender Candle ($6.99)
│       ├── balloons-5.jpg                ← Balloons 5pcs ($8.99)
│       └── balloons-10.jpg               ← Balloons 10pcs ($13.99)
```

## ✅ Image Specifications

| Requirement | Specification |
|-------------|----------------|
| **Size** | 300-500 pixels (square recommended) |
| **Format** | JPG or PNG |
| **File Size** | Under 200KB each |
| **Quality** | High-quality product photos |
| **Location** | `uploads/extras/` |
| **Required** | Optional - icons show if missing |

## 🎬 Setup Steps

### 1️⃣ Create Folder
```bash
# Already created: Flower-Store/uploads/extras/
```

### 2️⃣ Get Images
- Use free images from Unsplash, Pexels, or Pixabay
- OR create custom images with Canva
- Recommended: 400x400px JPG files

### 3️⃣ Save to Folder
- Save images to: `uploads/extras/`
- Use exact filenames from checklist above
- Refresh browser to see changes

### 4️⃣ That's It!
Images will automatically appear on checkout page.

## 🔍 Free Image Sources

| Site | URL | Quality | License |
|------|-----|---------|---------|
| Unsplash | unsplash.com | ⭐⭐⭐⭐⭐ | Free Commercial |
| Pexels | pexels.com | ⭐⭐⭐⭐⭐ | Free Commercial |
| Pixabay | pixabay.com | ⭐⭐⭐⭐ | Free Commercial |
| Canva | canva.com | ⭐⭐⭐⭐ | Create Custom |

## 🎯 Search Keywords

Use these search terms to find suitable images:

- `greeting card luxury product`
- `chocolate box gift`
- `scented candle rose`
- `lavender candles gift`
- `helium balloons party`
- `gift box with ribbon`
- `premium chocolates assortment`

## 🚀 Quick Commands

### Check if Images Exist
```bash
# Windows PowerShell
dir "uploads\extras"

# Mac/Linux
ls uploads/extras/
```

### Delete All Images (Keep Folder)
```bash
# Windows PowerShell
Remove-Item "uploads\extras\*"

# Mac/Linux
rm uploads/extras/*
```

### Add Image URL in Database
```sql
UPDATE available_extras 
SET image_url = 'uploads/extras/greeting-card-standard.jpg' 
WHERE id = 1;
```

## 📸 Image Display Preview

On checkout page, each extra shows:

```
┌─────────────────────┐
│   [Product Image]   │  ← 112px tall image preview
│  (or fallback icon) │
├─────────────────────┤
│ ☑ Extra Name        │  ← Checkbox
│   Description       │
│   + $2.99           │  ← Price
└─────────────────────┘
```

## ⚡ Performance

### Load Time Impact
- **With optimized images**: Negligible (< 50ms extra)
- **With large images**: Noticeable slowdown
- **Without images**: Instant (uses icons)

### Tips for Speed
1. Compress images on TinyPNG.com
2. Use JPG for photos (smaller than PNG)
3. Keep under 100KB per image
4. Use WebP format (modern browsers)

## 🔄 Update Images

### Replace an Existing Image
1. Delete old image from folder
2. Add new image with same filename
3. Refresh browser (Ctrl+Shift+Del)

### Change Image URL in Database
```sql
UPDATE available_extras 
SET image_url = 'uploads/extras/new-filename.jpg'
WHERE id = 5;
```

### Disable All Images (Use Icons)
```sql
UPDATE available_extras 
SET image_url = NULL;
```

## 🆘 Troubleshooting

| Problem | Solution |
|---------|----------|
| Images not showing | Check filename spelling matches SQL |
| Images blurry | Use higher resolution (500x500px) |
| Slow loading | Reduce file size (compress on TinyPNG) |
| Wrong folder | Ensure `uploads/extras/` path is correct |
| Can't upload | Check server permissions, try JPG format |

## 📧 File Naming Reference

Copy-paste exact filenames:

```
greeting-card-standard.jpg
greeting-card-luxury.jpg
candle-scented.jpg
chocolate-box.jpg
gift-box-ribbon.jpg
chocolate-premium.jpg
candle-rose.jpg
candle-lavender.jpg
balloons-5.jpg
balloons-10.jpg
```

## ✨ Pro Tips

1. **Add watermarks** - Protect your images with watermarks
2. **Use consistent style** - Keep images consistent across category
3. **Test on mobile** - Images should look good on phones
4. **Seasonal rotation** - Update images seasonally
5. **High quality** - Better images = more sales

## 🎓 Next Steps

1. ✅ Images folder created
2. 🖼️ Find/create images (see Free Image Sources)
3. 📁 Save to `uploads/extras/`
4. 🌐 Refresh checkout page
5. 🎉 Done! Images show automatically

## 📚 Learn More

- **Full Guide**: See `ORDER_EXTRAS_IMAGES_SETUP.md`
- **General Guide**: See `ORDER_EXTRAS_GUIDE.md`
- **Quick Start**: See `ORDER_EXTRAS_QUICK_START.md`

---

**Remember**: Images are optional! The system works great with just icons if you don't add images yet. You can add them anytime.
