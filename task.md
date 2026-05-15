# Payaman Wishlist — Fitur yang Masih Kurang

Semua fitur di bawah ini **sudah ada di kompetitor tapi BELUM ada di Payaman Wishlist** (kecuali Promotional Email yang sudah diimplementasi).

Perbandingan dengan **YITH WooCommerce Wishlist** (~1jt install) & **TI WooCommerce Wishlist**.

---

## 🔴 High Priority (Dampak Sales & Marketing)

| Fitur | Deskripsi | Payaman | YITH | TI |
|-------|-----------|:-------:|:----:|:--:|
| **Promotional Email Campaigns** | Panel admin untuk kirim email promosi ke user yang punya produk tertentu di wishlist | ✅ | ✅ | ✅ |
| **Ask for an Estimate** | Tombol "Minta Penawaran" di halaman wishlist, user bisa kirim quote request ke admin | ❌ | ✅ | ✅ |
| **Quantity Management** | Atur jumlah/qty per item di wishlist (misal pengen 2 buah) | ❌ | ✅ | ✅ |
| **Add All to Cart** | Tombol "Tambah Semua ke Keranjang" — sekali klik semua item wishlist masuk cart | ❌ | ✅ | ✅ |

## 🟡 Medium Priority (User Experience)

| Fitur | Deskripsi | Payaman | YITH | TI |
|-------|-----------|:-------:|:----:|:--:|
| **Wishlist Widget** | Sidebar widget mini-cart untuk wishlist, bisa ditaruh di header/sidebar | ❌ | ✅ | ✅ |
| **Price Change Tracking** | Tampilkan perubahan harga sejak produk ditambahkan ke wishlist (misal: "Harga turun 20%") | ❌ | ✅ | ❌ |
| **Social Sharing (lebih luas)** | Share ke Facebook, Twitter, Pinterest, Email (saat ini hanya WhatsApp + copy link) | ❌ | ✅ | ✅ |
| **Multiple Page Layouts** | Pilihan layout halaman wishlist: Traditional, Modern, Image style | ❌ | ✅ | ❌ |
| **Search Public Wishlists** | Cari & telusuri wishlist publik milik user lain | ❌ | ❌ | ✅ |
| **Drag & Drop Sort** | Urutkan item di wishlist dengan drag & drop | ❌ | ✅ | ❌ |
| **PDF Download** | Download wishlist sebagai file PDF | ❌ | ✅ | ❌ |
| **Login di Halaman Wishlist** | Form login/register langsung di halaman wishlist (tanpa redirect) | ❌ | ❌ | ✅ |

## 🟢 Low Priority (Community & Advanced)

| Fitur | Deskripsi | Payaman | YITH | TI |
|-------|-----------|:-------:|:----:|:--:|
| **Follow Wishlists** | Ikuti wishlist user lain, notifikasi jika ada perubahan | ❌ | ❌ | ✅ |
| **Recent Wishlists Widget** | Tampilkan daftar wishlist terbaru (social proof) | ❌ | ❌ | ✅ |
| **Elementor Widget / Gutenberg Block** | Widget untuk page builder | ❌ | ✅ | ❌ |
| **WPML / Polylang Support** | Kompatibilitas plugin multilingual | ❌ | ✅ | ✅ |
| **Coupon/Discount Integration** | Kirim kupon diskon ke pemilik wishlist | ❌ | ✅ | ❌ |
| **Wishlist di My Account Tab** | Halaman wishlist sebagai tab di My Account WooCommerce | ❌ | ✅ | ✅ |

---

## ✅ Promotional Email — Fitur Saat Ini

### ✔️ Sudah Diimplementasi
- **Multi-product campaign** — Satu campaign bisa target banyak produk
- **3 mode kirim** — Immediate / Schedule (date + time) / Repeat (daily/weekly/monthly)
- **Pause & Resume** — Tahan jadwal tanpa hapus, resume otomatis atur ulang waktu jika lewat
- **Kirim ulang** — Send Now bisa kapan saja walau status sudah `sent`
- **WP Cron** — Cek schedule tiap 1 menit, kirim otomatis
- **Timezone handling** — Konversi browser → UTC → WP timezone
- **Tambah/Edit via modal** — Form popup untuk create & edit campaign
- **Table dengan refresh AJAX** — Gak perlu reload halaman
- **UI modern** — Icon buttons (Dashicons) + tooltip hover

### 🔜 Potensi Pengembangan Selanjutnya
- **Email preview** — Lihat hasil email dengan placeholder sebelum kirim
- **Test send** — Kirim test ke email admin dulu sebelum broadcast
- **Send log / history** — Catat riwayat pengiriman (kapan, ke berapa user, hasil)
- **Duplicate campaign** — Gandakan campaign yang sudah ada sebagai template
- **Queue system** — Kirim bertahap untuk ribuan user biar gak timeout
- **HTML email template** — Support HTML dengan branding
- **Bulk actions** — Select multi campaign → pause/resume/delete sekali klik

---

## 💡 Rekomendasi Prioritas Pengembangan

1. ~~**Promotional Email Campaigns**~~ ✅ Selesai
2. **Ask for an Estimate** — Fitur lead generation, banyak kompetitor punya
3. **Quantity + Add All to Cart** — Mempermudah konversi wishlist ke pembelian
4. **Social Sharing lebih luas** — Tambah Facebook, Twitter, Pinterest, Email
5. **Multiple Page Layouts** — Biar tampilan wishlist gak monoton
