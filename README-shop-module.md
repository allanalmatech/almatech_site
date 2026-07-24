# Shop Catalog + WhatsApp Orders Module

## 1) Requirements
- PHP 7.0+
- MySQL/MariaDB
- Apache with `mod_rewrite`
- Writable folder: `uploads/products/`

## 2) Database Setup
1. Create/select your database (example: `almatech_site`).
2. Import schema:
   - `sql/shop_schema.sql`
3. Import seed data:
   - `sql/seed.sql`

If you already installed an older version of this module, run:

```sql
ALTER TABLE categories ADD COLUMN cover_image VARCHAR(255) NULL AFTER description;
```

## 3) Configure App
Edit `.env` if needed:
- `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS`
- `BASE_URL` (example: `/almatech_site`)

Use `.env.example` as the template for required values.

## 4) Admin Access
- URL: `/admin/login.php`
- Default username: `admin`
- Default password: `Admin@123`

Change the admin password immediately after first login (or update hash directly in `admins` table).

## 5) Public Shop URLs
- Shop home: `/shop/`
- Category: `/shop/category/{category-slug}`
- Product: `/shop/product/{product-slug}`
- Search: `/shop/search?q=...`

## 6) Settings
In `/admin/settings.php`, configure:
- WhatsApp number
- Currency label
- Products per page
- Show/hide out-of-stock products

## 7) Uploads
- Product images are stored in `uploads/products/`
- Allowed formats: JPG, PNG, WEBP
- Max file size: 2MB
