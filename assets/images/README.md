# Home Page Images Guide

## Required Images for the Home Page

### 1. Hero Image
- **Path**: `assets/images/hero-web-development.jpg`
- **Dimensions**: 1200x800px (16:10 ratio)
- **Description**: Professional web development scene showing developers working, code on screen, or modern office setup
- **Style**: Bright, professional, with orange accent colors
- **Alt Text**: "Web Development and Digital Solutions"

### 2. Service Images
Each service needs an image named according to the service title:

#### Website Design
- **Path**: `assets/images/service-website-design.jpg`
- **Dimensions**: 400x300px (4:3 ratio)
- **Description**: Modern website design mockup or responsive design showcase

#### Digital Marketing  
- **Path**: `assets/images/service-digital-marketing.jpg`
- **Dimensions**: 400x300px (4:3 ratio)
- **Description**: Digital marketing analytics, social media, or SEO visualization

#### Branding & Design
- **Path**: `assets/images/service-branding-design.jpg`
- **Dimensions**: 400x300px (4:3 ratio)
- **Description**: Brand identity work, logo design, or creative design process

#### IT Support
- **Path**: `assets/images/service-it-support.jpg`
- **Dimensions**: 400x300px (4:3 ratio)
- **Description**: IT professional working, server room, or technical support scene

### 3. CTA Background Image
- **Path**: `assets/images/cta-background.jpg`
- **Dimensions**: 1920x600px (3.2:1 ratio)
- **Description**: Abstract technology background with subtle orange gradients
- **Style**: Professional, not distracting, with good contrast for text overlay

## Image Requirements

### Technical Specifications
- **Format**: JPG (for photos) or PNG (for graphics with transparency)
- **Quality**: High quality but optimized for web (max 200KB per image)
- **Compression**: Balanced - maintain quality while keeping file size reasonable
- **Color Profile**: sRGB

### Style Guidelines
- **Color Scheme**: Complement the orange theme (#f97316)
- **Lighting**: Bright, professional, well-lit
- **Composition**: Clean, uncluttered, focused on the subject
- **Consistency**: All images should have a consistent professional look

### Alternative Options
If you don't have custom images, you can use:
1. **Stock Photos**: Unsplash, Pexels, or Adobe Stock
2. **Placeholder Services**: 
   - `https://picsum.photos/1200/800` for hero
   - `https://picsum.photos/400/300` for services
   - `https://picsum.photos/1920/600` for CTA background

### Image Optimization
- Use tools like TinyPNG or ImageOptim
- Ensure images are responsive and load quickly
- Consider using WebP format for better compression

### Fallback Setup
The site will gracefully handle missing images:
- Missing hero image: Shows form only
- Missing service images: Shows icon-only cards
- Missing CTA background: Shows gradient background

## Implementation Notes

### Dynamic Image Loading
Images are loaded dynamically based on service titles:
```php
service-<?= strtolower(str_replace(' ', '-', $card['title'])) ?>.jpg
```

### Responsive Images
All images are responsive and will adapt to different screen sizes:
- Hero image: Fluid width, fixed height
- Service images: Fixed container, fluid image
- CTA background: Full width, covers entire area

### SEO Optimization
- All images have proper alt text
- Descriptive file names
- Optimized for fast loading
