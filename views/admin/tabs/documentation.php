<?php
if (! defined('ABSPATH')) {
	exit;
}
?>

<div class="form-section" id="tab-documentation">
	<div class="payaman-doc-wrap">

		<div class="payaman-doc-hero">
			<div class="payaman-doc-hero-icon">
				<svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#2271b1" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
					<path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/>
					<path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/>
					<line x1="8" y1="7" x2="16" y2="7"/><line x1="8" y1="11" x2="14" y2="11"/>
					<line x1="8" y1="15" x2="12" y2="15"/>
				</svg>
			</div>
			<div class="payaman-doc-hero-text">
				<h2><?php esc_html_e('Dokumentasi Payaman Wishlist', 'payaman_wishlist'); ?></h2>
				<p><?php esc_html_e('Payaman Wishlist adalah plugin wishlist untuk WooCommerce yang memungkinkan pengguna menyimpan produk favorit mereka ke dalam daftar keinginan (wishlist) dan koleksi.', 'payaman_wishlist'); ?></p>
			</div>
		</div>

		<!-- Installation -->
		<div class="payaman-doc-section">
			<div class="payaman-doc-section-header">
				<span class="payaman-doc-section-number">01</span>
				<h3><?php esc_html_e('Instalasi', 'payaman_wishlist'); ?></h3>
			</div>
			<div class="payaman-doc-steps">
				<div class="payaman-doc-step">
					<div class="payaman-doc-step-marker">
						<span class="payaman-doc-step-num">1</span>
						<div class="payaman-doc-step-line"></div>
					</div>
					<div class="payaman-doc-step-body">
						<h4><?php esc_html_e('Persyaratan', 'payaman_wishlist'); ?></h4>
						<p><?php esc_html_e('Pastikan plugin WooCommerce sudah terinstal dan aktif di WordPress Anda.', 'payaman_wishlist'); ?></p>
					</div>
				</div>
				<div class="payaman-doc-step">
					<div class="payaman-doc-step-marker">
						<span class="payaman-doc-step-num">2</span>
						<div class="payaman-doc-step-line"></div>
					</div>
					<div class="payaman-doc-step-body">
						<h4><?php esc_html_e('Upload Plugin', 'payaman_wishlist'); ?></h4>
						<p><?php esc_html_e('Upload folder plugin payaman_wishlist ke direktori /wp-content/plugins/ atau instal langsung melalui menu Plugins > Add New.', 'payaman_wishlist'); ?></p>
					</div>
				</div>
				<div class="payaman-doc-step">
					<div class="payaman-doc-step-marker">
						<span class="payaman-doc-step-num">3</span>
						<div class="payaman-doc-step-line"></div>
					</div>
					<div class="payaman-doc-step-body">
						<h4><?php esc_html_e('Aktifkan Plugin', 'payaman_wishlist'); ?></h4>
						<p><?php esc_html_e('Aktifkan plugin "Payaman Wishlist" dari menu Plugins di dashboard WordPress.', 'payaman_wishlist'); ?></p>
					</div>
				</div>
				<div class="payaman-doc-step">
					<div class="payaman-doc-step-marker">
						<span class="payaman-doc-step-num">4</span>
						<div class="payaman-doc-step-line"></div>
					</div>
					<div class="payaman-doc-step-body">
						<h4><?php esc_html_e('Konfigurasi', 'payaman_wishlist'); ?></h4>
						<p><?php esc_html_e('Buka menu Payaman Studio > Payaman Wishlist di dashboard admin untuk melakukan pengaturan plugin.', 'payaman_wishlist'); ?></p>
					</div>
				</div>
				<div class="payaman-doc-step">
					<div class="payaman-doc-step-marker">
						<span class="payaman-doc-step-num">5</span>
						<div class="payaman-doc-step-line"></div>
					</div>
					<div class="payaman-doc-step-body">
						<h4><?php esc_html_e('Buat Halaman Wishlist', 'payaman_wishlist'); ?></h4>
						<p><?php esc_html_e('Buat halaman baru dengan shortcode [payaman_wishlist] untuk menampilkan halaman wishlist, lalu atur halaman tersebut di tab General Setting.', 'payaman_wishlist'); ?></p>
					</div>
				</div>
			</div>
		</div>

		<!-- Usage -->
		<div class="payaman-doc-section">
			<div class="payaman-doc-section-header">
				<span class="payaman-doc-section-number">02</span>
				<h3><?php esc_html_e('Penggunaan', 'payaman_wishlist'); ?></h3>
			</div>

			<div class="payaman-doc-usage-grid">
				<div class="payaman-doc-usage-card">
					<div class="payaman-doc-usage-card-top">
						<svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="#e25555" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
							<path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/>
						</svg>
					</div>
					<h4><?php esc_html_e('Pengunjung / Pembeli', 'payaman_wishlist'); ?></h4>
					<ul>
						<li><?php esc_html_e('Klik tombol Wishlist pada produk untuk menambahkan ke daftar keinginan', 'payaman_wishlist'); ?></li>
						<li><?php esc_html_e('Kunjungi halaman wishlist untuk melihat semua produk tersimpan', 'payaman_wishlist'); ?></li>
						<li><?php esc_html_e('Buat koleksi untuk mengelompokkan wishlist (misal: "Elektronik", "Hadiah")', 'payaman_wishlist'); ?></li>
						<li><?php esc_html_e('Atur jumlah quantity produk di wishlist', 'payaman_wishlist'); ?></li>
						<li><?php esc_html_e('Tambahkan produk ke keranjang langsung dari wishlist', 'payaman_wishlist'); ?></li>
						<li><?php esc_html_e('Hapus produk dari wishlist jika tidak diinginkan', 'payaman_wishlist'); ?></li>
					</ul>
				</div>
				<div class="payaman-doc-usage-card">
					<div class="payaman-doc-usage-card-top">
						<svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="#2271b1" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
							<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
						</svg>
					</div>
					<h4><?php esc_html_e('Admin / Pemilik Toko', 'payaman_wishlist'); ?></h4>
					<ul>
						<li><?php esc_html_e('Atur posisi & tampilan tombol wishlist di tab Button Setting', 'payaman_wishlist'); ?></li>
						<li><?php esc_html_e('Konfigurasi pesan yang ditampilkan ke pengguna di tab Message', 'payaman_wishlist'); ?></li>
						<li><?php esc_html_e('Aktifkan notifikasi email stok & harga di tab General Setting', 'payaman_wishlist'); ?></li>
						<li><?php esc_html_e('Buat & kelola kampanye email promosi di tab Promotional Email', 'payaman_wishlist'); ?></li>
						<li><?php esc_html_e('Pantau statistik wishlist di tab Dashboard', 'payaman_wishlist'); ?></li>
					</ul>
				</div>
			</div>
		</div>

		<!-- Features -->
		<div class="payaman-doc-section">
			<div class="payaman-doc-section-header">
				<span class="payaman-doc-section-number">03</span>
				<h3><?php esc_html_e('Fitur-Fitur', 'payaman_wishlist'); ?></h3>
			</div>
			<div class="payaman-doc-features">
				<div class="payaman-doc-feature-card">
					<div class="payaman-doc-feature-card-accent" style="background:#e25555"></div>
					<div class="payaman-doc-feature-icon-wrap" style="background:#fef2f2;color:#e25555">❤️</div>
					<h4><?php esc_html_e('Tombol Wishlist', 'payaman_wishlist'); ?></h4>
					<p><?php esc_html_e('Tampilan tombol wishlist yang dapat diatur (teks atau gambar), dengan posisi setelah Add to Cart atau overlay pada gambar produk.', 'payaman_wishlist'); ?></p>
				</div>
				<div class="payaman-doc-feature-card">
					<div class="payaman-doc-feature-card-accent" style="background:#8b5cf6"></div>
					<div class="payaman-doc-feature-icon-wrap" style="background:#f5f3ff;color:#8b5cf6">📁</div>
					<h4><?php esc_html_e('Koleksi Wishlist', 'payaman_wishlist'); ?></h4>
					<p><?php esc_html_e('Pengguna dapat membuat dan mengelola koleksi wishlist untuk mengelompokkan produk favorit mereka.', 'payaman_wishlist'); ?></p>
				</div>
				<div class="payaman-doc-feature-card">
					<div class="payaman-doc-feature-card-accent" style="background:#059669"></div>
					<div class="payaman-doc-feature-icon-wrap" style="background:#ecfdf5;color:#059669">📊</div>
					<h4><?php esc_html_e('Dashboard Statistik', 'payaman_wishlist'); ?></h4>
					<p><?php esc_html_e('Pantau total item wishlist, jumlah koleksi, pengguna aktif, dan produk paling diinginkan.', 'payaman_wishlist'); ?></p>
				</div>
				<div class="payaman-doc-feature-card">
					<div class="payaman-doc-feature-card-accent" style="background:#d97706"></div>
					<div class="payaman-doc-feature-icon-wrap" style="background:#fffbeb;color:#d97706">📧</div>
					<h4><?php esc_html_e('Alert Email', 'payaman_wishlist'); ?></h4>
					<p><?php esc_html_e('Notifikasi otomatis ke pengguna ketika stok produk tersedia kembali atau harga turun.', 'payaman_wishlist'); ?></p>
				</div>
				<div class="payaman-doc-feature-card">
					<div class="payaman-doc-feature-card-accent" style="background:#2563eb"></div>
					<div class="payaman-doc-feature-icon-wrap" style="background:#eff6ff;color:#2563eb">📨</div>
					<h4><?php esc_html_e('Kampanye Email', 'payaman_wishlist'); ?></h4>
					<p><?php esc_html_e('Buat kampanye email promosional untuk produk wishlist dengan penjadwalan otomatis (harian, mingguan, bulanan).', 'payaman_wishlist'); ?></p>
				</div>
				<div class="payaman-doc-feature-card">
					<div class="payaman-doc-feature-card-accent" style="background:#0891b2"></div>
					<div class="payaman-doc-feature-icon-wrap" style="background:#ecfeff;color:#0891b2">👤</div>
					<h4><?php esc_html_e('Dukungan Tamu', 'payaman_wishlist'); ?></h4>
					<p><?php esc_html_e('Pengguna yang belum login tetap dapat menggunakan wishlist melalui cookie, dan akan otomatis tersimpan saat login.', 'payaman_wishlist'); ?></p>
				</div>
			</div>
		</div>

		<!-- Shortcode -->
		<div class="payaman-doc-section">
			<div class="payaman-doc-section-header">
				<span class="payaman-doc-section-number">04</span>
				<h3><?php esc_html_e('Shortcode', 'payaman_wishlist'); ?></h3>
			</div>
			<div class="payaman-doc-code-box">
				<div class="payaman-doc-code-box-header">
					<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="16 18 22 12 16 6"/><polyline points="8 6 2 12 8 18"/></svg>
					<span><?php esc_html_e('Shortcode yang tersedia', 'payaman_wishlist'); ?></span>
				</div>
				<table class="payaman-doc-table">
					<thead>
						<tr>
							<th><?php esc_html_e('Shortcode', 'payaman_wishlist'); ?></th>
							<th><?php esc_html_e('Deskripsi', 'payaman_wishlist'); ?></th>
						</tr>
					</thead>
					<tbody>
						<tr>
							<td><code>[payaman_wishlist]</code></td>
							<td><?php esc_html_e('Menampilkan halaman wishlist pengguna', 'payaman_wishlist'); ?></td>
						</tr>
					</tbody>
				</table>
			</div>
		</div>

		<!-- FAQ -->
		<div class="payaman-doc-section">
			<div class="payaman-doc-section-header">
				<span class="payaman-doc-section-number">05</span>
				<h3><?php esc_html_e('FAQ', 'payaman_wishlist'); ?></h3>
			</div>
			<div class="payaman-doc-faq">
				<div class="payaman-doc-faq-item">
					<div class="payaman-doc-faq-icon">❓</div>
					<div class="payaman-doc-faq-body">
						<h4><?php esc_html_e('Bagaimana cara menampilkan halaman wishlist?', 'payaman_wishlist'); ?></h4>
						<p><?php esc_html_e('Buat halaman baru di WordPress dan masukkan shortcode [payaman_wishlist]. Kemudian atur halaman tersebut di Payaman Wishlist > General Setting > Wishlist Page.', 'payaman_wishlist'); ?></p>
					</div>
				</div>
				<div class="payaman-doc-faq-item">
					<div class="payaman-doc-faq-icon">❓</div>
					<div class="payaman-doc-faq-body">
						<h4><?php esc_html_e('Apa yang terjadi jika pengguna belum login?', 'payaman_wishlist'); ?></h4>
						<p><?php esc_html_e('Wishlist akan tersimpan di browser menggunakan cookie. Saat pengguna login, data wishlist akan otomatis dipindahkan ke akun mereka.', 'payaman_wishlist'); ?></p>
					</div>
				</div>
				<div class="payaman-doc-faq-item">
					<div class="payaman-doc-faq-icon">❓</div>
					<div class="payaman-doc-faq-body">
						<h4><?php esc_html_e('Bagaimana cara mengirim notifikasi email?', 'payaman_wishlist'); ?></h4>
						<p><?php esc_html_e('Aktifkan opsi "Price Drop" dan/atau "Back in Stock" di tab General Setting. Notifikasi akan terkirim otomatis ketika harga berubah atau stok tersedia.', 'payaman_wishlist'); ?></p>
					</div>
				</div>
				<div class="payaman-doc-faq-item">
					<div class="payaman-doc-faq-icon">❓</div>
					<div class="payaman-doc-faq-body">
						<h4><?php esc_html_e('Bisakah saya mengatur tampilan tombol wishlist?', 'payaman_wishlist'); ?></h4>
						<p><?php esc_html_e('Ya, Anda dapat mengatur tampilan tombol wishlist di tab Button Setting. Pilih antara teks atau gambar, atur posisi, dan kustomisasi label tombol.', 'payaman_wishlist'); ?></p>
					</div>
				</div>
			</div>
		</div>

	</div>
</div>
