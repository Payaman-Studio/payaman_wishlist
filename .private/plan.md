# Payaman Wishlist — Production-Ready Plan

## Fase 1: Critical Bugs & Security (PRIORITAS)

| # | Issue | Lokasi | Dampak |
|---|-------|--------|--------|
| 1.1 | **Event handler `added_to_cart` nested di dalam click handler `collection-delete`** | `assets/js/payaman_wishlist-script.js:843-945` | Setiap klik delete, register `added_to_cart` baru. Multiple firing → error. |
| 1.2 | **`page-setting.php` binary encoded** | `views/admin/page-setting.php` | Ga bisa review, potensi backdoor. Harus ganti dengan view normal. |
| 1.3 | **Default collection slug pake `default_{user_id}` — predictable** | `inc/helpers.php:563` | Bisa ditebak. Harusnya pake UUID kayak collection lain. |
| 1.4 | **Nonce `payaman_wishlist_toggle` dipake untuk SEMUA action frontend** | `inc/class-payaman_wishlist-ajax.php` | Satu nonce untuk 6+ action beda. Risk of CSRF. Harus dipisah. |
| 1.5 | **`task.md` ekspos publik** | `task.md` | Roadmap kompetitor bisa dibaca siapa aja. |

## Fase 2: Performance (Scalability)

| # | Issue | Lokasi | Dampak |
|---|-------|--------|--------|
| 2.1 | **Double write: tiap toggle nulis ke collection_items + post meta** | `inc/helpers.php:725-788` + `inc/class-payaman_wishlist-ajax.php:66-85` | 2x write per toggle. Harus deprecate post meta. |
| 2.2 | **`posts_per_page => -1` di wishlist page** | `inc/class-payaman_wishlist-front.php:403,449` | User dengan 500+ produk → query berat + memory leak. |
| 2.3 | **No caching untuk wishlist count** | `inc/helpers.php:224-228` | `get_payaman_wishlist()` query post meta tiap render. |
| 2.4 | **Ga ada index composite di DB** | `inc/helpers.php:87-110` | `collection_id + product_id` ga di-index bareng. |
| 2.5 | **Cookie 30 hari ga bisa di-configure** | `inc/helpers.php:17-54` | Hardcoded. |

## Fase 3: Code Quality & Maintainability

| # | Issue | Lokasi | Dampak |
|---|-------|--------|--------|
| 3.1 | **~500 baris inline JS di admin view** | `views/admin/tabs/promotional-email.php:296-771` | Gak bisa di-minify, susah debug, gak bisa lint. |
| 3.2 | **Alert/confirm/prompt di frontend JS** | `assets/js/payaman_wishlist-script.js:4,418,483,728,777,809,843` | UX jelek, bloking. Ganti dengan modal bawaan. |
| 3.3 | **`showAlert` masih pake `window.alert()`** | `assets/js/payaman_wishlist-script.js:3-4` | Harusnya pake toast/modal. |
| 3.4 | **Ga ada error boundary / try-catch di AJAX handlers** | `inc/class-payaman_wishlist-ajax.php` | Error 500 return cryptic. |
| 3.5 | **Function naming tidak konsisten** | `inc/helpers.php:224` | `get_payaman_wishlist()` vs `payaman_wishlist_*()` |
| 3.6 | **Subquery tanpa parameterized collection_ids** | `inc/helpers.php:906-913` | SQL injection risk (minor, user_id sudah di-prepare). |

## Fase 4: Feature Gaps (Kompetitor)

| # | Fitur | Priority | Notes |
|---|-------|----------|-------|
| 4.1 | **Add All to Cart** | High | Tombol sekali klik masukin semua ke cart. |
| 4.2 | **Quantity Management** | High | Atur qty per item di wishlist. |
| 4.3 | **Ask for an Estimate** | Medium | Lead generation — kirim quote request ke admin. |
| 4.4 | **Social Sharing (Facebook, Twitter, Pinterest, Email)** | Medium | Saat ini cuma WA + copy link. |
| 4.5 | **Price Change Display** | Medium | "Harga turun 20% sejak ditambahkan". |
| 4.6 | **Multiple Page Layouts** | Medium | Traditional / Modern / Image style. |
| 4.7 | **Wishlist Widget** | Medium | Sidebar widget. |
| 4.8 | **Drag & Drop Sort** | Low | Urutin item. |
| 4.9 | **PDF Download** | Low | Export wishlist ke PDF. |
| 4.10 | **Login di halaman wishlist** | Low | Form login tanpa redirect. |
| 4.11 | **My Account Tab** | Low | Wishlist sebagai tab di WooCommerce My Account. |
| 4.12 | **Elementor/Gutenberg Widget** | Low | Page builder support. |

## Fase 5: DevOps & Distribution

| # | Issue | Keterangan |
|---|-------|------------|
| 5.1 | **Version bump** | Update `PAYAMAN_WISHLIST_VERSION` |
| 5.2 | **readme.txt** | Update tested up to, changelog, screenshots |
| 5.3 | **Hapus task.md dari production** | Pindah ke `.gitignore` atau folder non-publik |
| 5.4 | **Minify assets** | CSS/JS minified version |
| 5.5 | **PHPCS / lint** | Pastikan standar WordPress coding |

---

## Recommended Execution Order

```
Fase 1 → Fase 2 → Fase 3 → Fase 4 (High) → Fase 4 (Medium) → Fase 5
```

Prioritas awal: **1.1, 1.2, 2.1, 2.2, 3.1, 3.2** — ini yang paling berdampak langsung.
