# DEV-NOTE: This file is for development tracking only. Do not expose in production.
# Task List — Payaman Wishlist Production-Ready

## Fase 1: Critical Bugs & Security

### 1.1 Fix nested `added_to_cart` event handler
- **File:** `assets/js/payaman_wishlist-script.js`
- **Action:** Pindahkan `$(document.body).on("added_to_cart", ...)` dari dalam click handler `collection-delete` ke level atas (dalam IIFE jQuery).
- **Verify:** Click delete sekali, lalu add to cart → handler cuma jalan sekali.

### 1.2 Decode / rewrite `page-setting.php`
- **File:** `views/admin/page-setting.php`
- **Action:** Tulis ulang pake HTML murni (tab navigation + include partials).
- **Alternatif:** Cari tau apakah ini ionCube / SourceGuardian. Kalau iya, minta source asli ke developer.

### 1.3 Generate UUID untuk default collection slug
- **File:** `inc/helpers.php` — function `payaman_wishlist_get_default_collection()`
- **Action:** Ganti slug `default_{user_id}` jadi `wp_generate_uuid4()`.
- **Concern:** Ini ngefek ke shared URL yang udah ada. Kudu migrasi atau fallback.

### 1.4 Pisah nonce per action group
- **File:** `inc/class-payaman_wishlist-ajax.php`
- **Action:** Buat nonce terpisah untuk: wishlist toggle, collection management, campaign management.
- **File:** `inc/class-payaman_wishlist-front.php`
- **Action:** Generate nonce sesuai kebutuhan di `wp_localize_script`.

### 1.5 Hapus/move `task.md`
- **Action:** Pindahin ke folder `.private/` atau tambahin ke `.gitignore`.
- **Atau:** Rename jadi `task.internal.md` dan block via `.htaccess`.

---

## Fase 2: Performance

### 2.1 Deprecate legacy post meta writes
- **File:** `inc/helpers.php`, `inc/class-payaman_wishlist-ajax.php`
- **Action:**
  1. Tambah opsi "use_legacy_meta" default `no`.
  2. Di `payaman_wishlist_update_user_wishlists()`, cuma tulis ke post meta kalo setting legacy ON.
  3. Di `payaman_wishlist_get_wishlists()`, fallback ke post meta cuma kalo setting legacy ON.
  4. Di `get_payaman_wishlist()`, ganti query ke DB langsung (COUNT dari items table).
- **Testing:** Pastikan wishlist count masih akurat pas legacy dimatikan.

### 2.2 Implement pagination di wishlist page
- **File:** `inc/class-payaman_wishlist-front.php` — function `wishlist_list()`
- **Action:**
  1. Ganti `posts_per_page => -1` jadi `posts_per_page => 20` + paged parameter.
  2. Tambah `paginate_links()` setelah table.
  3. Update JS bulk actions biar paham current page.
- **Default setting:** 20 items per page (bisa di-setting di admin).

### 2.3 Query-based wishlist count
- **File:** `inc/analytics-helpers.php`
- **Action:** Tambah function `payaman_wishlist_get_product_count($product_id)` yang query langsung dari items table (COUNT).
- **File:** `inc/helpers.php:224` — `get_payaman_wishlist()`
- **Action:** Ganti implementasi pake query ke items table via `payaman_wishlist_get_users_by_product()`.

### 2.4 Add composite indexes
- **File:** `inc/helpers.php` — function `payaman_wishlist_maybe_install_tables()`
- **Action:** Tambah migration v2.4.0 yang nambahin:
  ```sql
  ALTER TABLE wp_payaman_wishlist_collection_items ADD INDEX collection_product (collection_id, product_id);
  ALTER TABLE wp_payaman_wishlist_collection_items ADD INDEX product_added (product_id, added_at);
  ALTER TABLE wp_payaman_wishlist_collections ADD INDEX user_default (user_id, is_default);
  ```

### 2.5 Make cookie expiry configurable
- **File:** `inc/helpers.php:17-54`
- **Action:** Tambah setting "guest_cookie_days" di admin (default 30). Pake constant `PAYAMAN_WISHLIST_COOKIE_DAYS`.

---

## Fase 3: Code Quality & Maintainability

### 3.1 Ekstrak inline JS dari `promotional-email.php`
- **File:** `views/admin/tabs/promotional-email.php`
- **Action:**
  1. Pindahkan semua inline JS ke `assets/js/payaman_wishlist-campaigns.js`.
  2. Pake `wp_localize_script` untuk data yang di-passing.
  3. Enqueue di `payaman_wishlist.php` function `admin_enqueue_scripts()`.
- **Verify:** Semua fungsi campaign (CRUD, preview, test send, table refresh) tetep jalan.

### 3.2 Replace `alert()`, `confirm()`, `prompt()` dengan UI plugin
- **File:** `assets/js/payaman_wishlist-script.js`
- **Action:**
  - `alert()` → `showToast()` atau modal bawaan.
  - `confirm()` → modal konfirmasi dengan tombol Ya/Tidak.
  - `prompt()` → modal dengan input field.
- **Komponen yang kena:** rename collection, delete collection, add new collection.

### 3.3 Add error boundaries di AJAX
- **File:** `inc/class-payaman_wishlist-ajax.php`
- **Action:** Wrap tiap handler dengan try-catch. Kalo catch error, kirim `wp_send_json_error` dengan message bersih (jangan expose stack trace).

### 3.4 Rename `get_payaman_wishlist()` → `payaman_wishlist_get_count()`
- **File:** search all references
- **Action:** Rename biar konsisten sama function lainnya.

### 3.5 Fix subquery di `payaman_wishlist_move_items_between_collections()`
- **File:** `inc/helpers.php:906-913`
- **Action:** Ganti subquery `SELECT id FROM ... WHERE user_id = %d` jadi JOIN atau pre-fetch collection_ids dulu.

### 3.6 Hapus emoji dari HTML (opsional)
- **File:** `inc/class-payaman_wishlist-front.php:496,498-501`
- **Action:** Ganti emoji ✏️🌐🔒🗑️ dengan icon (Dashicons atau SVG). Biar konsisten di semua browser/OS.

---

## Fase 4: Feature Gaps

### 4.1 Add All to Cart (High)
- **Action:**
  1. Tambah tombol "Add All to Cart" di bulk toolbar.
  2. Handler: loop semua product_id di collection → `WC()->cart->add_to_cart()`.
  3. Handle variable products (skip atau redirect ke produk).
  4. Pakai AJAX + progress indicator.

### 4.2 Quantity Management (High)
- **Action:**
  1. Tambah kolom "Qty" di wishlist table.
  2. Input number per item.
  3. Migration: tambah kolom `quantity` di `collection_items` table (default 1).
  4. Integrasi dengan Add All to Cart — pake quantity dari wishlist.

### 4.3 Ask for an Estimate (Medium)
- **File:** butuh view + handler + email template baru
- **Action:**
  1. Tombol "Ask for an Estimate" di wishlist page.
  2. Modal form: nama, email, pesan, list produk.
  3. Kirim email ke admin dengan detail quote request.
  4. Bisa di-setting email tujuan di admin (default: admin email).

### 4.4 Social Sharing (Medium)
- **File:** `inc/class-payaman_wishlist-front.php:526-541`
- **Action:** Tambah share links untuk:
  - Facebook: `https://www.facebook.com/sharer/sharer.php?u=`
  - Twitter: `https://twitter.com/intent/tweet?text=`
  - Pinterest: `https://pinterest.com/pin/create/button/?url=`
  - Email: `mailto:?subject=&body=`
- Gunakan icon (Dashicons atau font icon).

### 4.5 Price Change Display (Medium)
- **Action:**
  1. Simpan harga produk saat ditambahkan ke wishlist (`added_price` di items table).
  2. Di wishlist table, tampilkan "Harga turun X%" atau "Harga naik X%" atau "Harga stabil".
  3. Migration v2.5.0: tambah kolom `added_price DECIMAL(10,2) DEFAULT 0`.

### 4.6 Multiple Page Layouts (Medium)
- **Action:**
  1. Tambah setting "Wishlist Page Layout" → Traditional / Modern / Image.
  2. Traditional: table view (sekarang).
  3. Modern: grid cards.
  4. Image: full-width image grid.
  5. Pake CSS class switching di wrapper.

### 4.7 Wishlist Widget (Medium)
- **Action:**
  1. Buat `class-payaman_wishlist-widget.php` extending `WP_Widget`.
  2. Register widget di main plugin file.
  3. Tampilkan daftar wishlist user (terbatas 5 item, bisa expand).
  4. Styling sesuai theme.

### 4.8-4.12 (Low priority) — skip dulu, fokus ke high/medium dulu.

---

## Fase 5: DevOps & Distribution

### 5.1 Version bump
- **File:** `payaman_wishlist.php:7`, `payaman_wishlist.php:97`
- Ganti `Version: 1.0.2` → `1.2.0` dan `PAYAMAN_WISHLIST_VERSION` → `1.2.0`

### 5.2 Update readme.txt
- Update "Tested up to" ke WooCommerce version terbaru.
- Tambah changelog.
- Tambah screenshots.

### 5.3 Hapus task.md dari production
- Tambah `.gitignore` entry atau pindahin.
- Atau rename jadi `task.dev.md` dan block via `.htaccess`.

### 5.4 Minify assets
- Buat `.min.css` dan `.min.js` counterparts.
- Atau pake build script (webpack/gulp).

### 5.5 PHPCS lint
- Jalankan `phpcs --standard=WordPress` dan fix error.

---

## Execution Order Rekomendasi

```
Fase 1 → Fase 2 → Fase 3 → Fase 4.1 → 4.2 → 4.3 → 4.4 → 4.5 → Fase 5
```

## Estimasi

| Fase | Item | Estimasi |
|------|------|----------|
| 1 | Critical bugs | 2-3 hari |
| 2 | Performance | 2-3 hari |
| 3 | Code quality | 1-2 hari |
| 4 | Features (High) | 3-4 hari |
| 4 | Features (Medium) | 5-7 hari |
| 5 | DevOps | 1 hari |
| **Total** | | **14-20 hari** |
