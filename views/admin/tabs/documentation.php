<?php
if (! defined('ABSPATH')) {
	exit;
}
?>
<div class="form-section" id="tab-documentation" style="display: none;">
	<div class="payaman-doc-wrap">

		<!-- Hero Banner -->
		<div class="payaman-doc-hero">
			<div class="payaman-doc-hero-icon">
				<svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
					<path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/>
					<path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/>
					<line x1="8" y1="7" x2="16" y2="7"/><line x1="8" y1="11" x2="14" y2="11"/>
					<line x1="8" y1="15" x2="12" y2="15"/>
				</svg>
			</div>
			<div class="payaman-doc-hero-text">
				<h2><?php esc_html_e('Payaman Wishlist Documentation', 'payaman-wishlist'); ?></h2>
				<p><?php esc_html_e('Payaman Wishlist is a flexible WooCommerce wishlist plugin that allows customers to save favorite items, organize public/private collections, and automates marketing email notifications.', 'payaman-wishlist'); ?></p>
			</div>
		</div>

		<!-- Installation -->
		<div class="payaman-doc-section">
			<div class="payaman-doc-section-header">
				<span class="payaman-doc-section-number">01</span>
				<h3><?php esc_html_e('Installation', 'payaman-wishlist'); ?></h3>
			</div>
			<div class="payaman-doc-steps">
				<div class="payaman-doc-step">
					<div class="payaman-doc-step-marker">
						<span class="payaman-doc-step-num">1</span>
						<div class="payaman-doc-step-line"></div>
					</div>
					<div class="payaman-doc-step-body">
						<h4><?php esc_html_e('Requirements', 'payaman-wishlist'); ?></h4>
						<p><?php esc_html_e('Ensure WooCommerce is installed and activated on your WordPress store.', 'payaman-wishlist'); ?></p>
					</div>
				</div>
				<div class="payaman-doc-step">
					<div class="payaman-doc-step-marker">
						<span class="payaman-doc-step-num">2</span>
						<div class="payaman-doc-step-line"></div>
					</div>
					<div class="payaman-doc-step-body">
						<h4><?php esc_html_e('Upload Plugin', 'payaman-wishlist'); ?></h4>
						<p><?php esc_html_e('Upload the plugin folder to the /wp-content/plugins/ directory, or install the ZIP file via Plugins > Add New.', 'payaman-wishlist'); ?></p>
					</div>
				</div>
				<div class="payaman-doc-step">
					<div class="payaman-doc-step-marker">
						<span class="payaman-doc-step-num">3</span>
						<div class="payaman-doc-step-line"></div>
					</div>
					<div class="payaman-doc-step-body">
						<h4><?php esc_html_e('Activate Plugin', 'payaman-wishlist'); ?></h4>
						<p><?php esc_html_e('Activate "Payaman Wishlist" from the Plugins menu in your WordPress dashboard.', 'payaman-wishlist'); ?></p>
					</div>
				</div>
				<div class="payaman-doc-step">
					<div class="payaman-doc-step-marker">
						<span class="payaman-doc-step-num">4</span>
						<div class="payaman-doc-step-line"></div>
					</div>
					<div class="payaman-doc-step-body">
						<h4><?php esc_html_e('Configuration', 'payaman-wishlist'); ?></h4>
						<p><?php esc_html_e('Navigate to Payaman Studio > Payaman Wishlist in the admin menu to customize plugin settings.', 'payaman-wishlist'); ?></p>
					</div>
				</div>
				<div class="payaman-doc-step">
					<div class="payaman-doc-step-marker">
						<span class="payaman-doc-step-num">5</span>
						<div class="payaman-doc-step-line"></div>
					</div>
					<div class="payaman-doc-step-body">
						<h4><?php esc_html_e('Create Wishlist Page', 'payaman-wishlist'); ?></h4>
						<p><?php esc_html_e('Create a new page with the shortcode [payaman_wishlist], then set this page under General Setting > Wishlist Page.', 'payaman-wishlist'); ?></p>
					</div>
				</div>
			</div>
		</div>

		<!-- Usage -->
		<div class="payaman-doc-section">
			<div class="payaman-doc-section-header">
				<span class="payaman-doc-section-number">02</span>
				<h3><?php esc_html_e('Usage', 'payaman-wishlist'); ?></h3>
			</div>

			<div class="payaman-doc-usage-grid">
				<div class="payaman-doc-usage-card">
					<div class="payaman-doc-usage-card-top">
						<svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="#e25555" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
							<path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/>
						</svg>
					</div>
					<h4><?php esc_html_e('Customers / Visitors', 'payaman-wishlist'); ?></h4>
					<ul>
						<li><?php esc_html_e('Click the Wishlist button on product items to bookmark them', 'payaman-wishlist'); ?></li>
						<li><?php esc_html_e('Visit the Wishlist page to view all saved products', 'payaman-wishlist'); ?></li>
						<li><?php esc_html_e('Create custom collections to categorize items (e.g. "Electronics", "Gift Ideas")', 'payaman-wishlist'); ?></li>
						<li><?php esc_html_e('Adjust product quantities directly on the wishlist page', 'payaman-wishlist'); ?></li>
						<li><?php esc_html_e('Move products directly to shopping cart with one click', 'payaman-wishlist'); ?></li>
						<li><?php esc_html_e('Remove or move products between collections easily', 'payaman-wishlist'); ?></li>
					</ul>
				</div>
				<div class="payaman-doc-usage-card">
					<div class="payaman-doc-usage-card-top">
						<svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="#2271b1" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
							<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
						</svg>
					</div>
					<h4><?php esc_html_e('Store Admin / Shop Manager', 'payaman-wishlist'); ?></h4>
					<ul>
						<li><?php esc_html_e('Configure button position and styling in the Button Setting tab', 'payaman-wishlist'); ?></li>
						<li><?php esc_html_e('Customize customer feedback notices in the Message tab', 'payaman-wishlist'); ?></li>
						<li><?php esc_html_e('Enable automated Stock & Price Drop notifications in the General tab', 'payaman-wishlist'); ?></li>
						<li><?php esc_html_e('Build and schedule promotional marketing campaigns in the Promotional Email tab', 'payaman-wishlist'); ?></li>
						<li><?php esc_html_e('Review real-time wishlist metrics and top desired items in the Dashboard tab', 'payaman-wishlist'); ?></li>
					</ul>
				</div>
			</div>
		</div>

		<!-- Features -->
		<div class="payaman-doc-section">
			<div class="payaman-doc-section-header">
				<span class="payaman-doc-section-number">03</span>
				<h3><?php esc_html_e('Features', 'payaman-wishlist'); ?></h3>
			</div>
			<div class="payaman-doc-features">
				<div class="payaman-doc-feature-card">
					<div class="payaman-doc-feature-card-accent" style="background:#e25555"></div>
					<div class="payaman-doc-feature-icon-wrap" style="background:#fef2f2;color:#e25555">❤️</div>
					<h4><?php esc_html_e('Wishlist Button', 'payaman-wishlist'); ?></h4>
					<p><?php esc_html_e('Customizable button display (text or image) with positions like After Add to Cart or overlay on product thumbnails.', 'payaman-wishlist'); ?></p>
				</div>
				<div class="payaman-doc-feature-card">
					<div class="payaman-doc-feature-card-accent" style="background:#8b5cf6"></div>
					<div class="payaman-doc-feature-icon-wrap" style="background:#f5f3ff;color:#8b5cf6">📁</div>
					<h4><?php esc_html_e('Wishlist Collections', 'payaman-wishlist'); ?></h4>
					<p><?php esc_html_e('Customers can create and organize multiple collections to group their favorite items with public/private visibility.', 'payaman-wishlist'); ?></p>
				</div>
				<div class="payaman-doc-feature-card">
					<div class="payaman-doc-feature-card-accent" style="background:#059669"></div>
					<div class="payaman-doc-feature-icon-wrap" style="background:#ecfdf5;color:#059669">📊</div>
					<h4><?php esc_html_e('Analytics Dashboard', 'payaman-wishlist'); ?></h4>
					<p><?php esc_html_e('Track total wishlisted products, collections, active users, and the top 10 most desired products.', 'payaman-wishlist'); ?></p>
				</div>
				<div class="payaman-doc-feature-card">
					<div class="payaman-doc-feature-card-accent" style="background:#d97706"></div>
					<div class="payaman-doc-feature-icon-wrap" style="background:#fffbeb;color:#d97706">📧</div>
					<h4><?php esc_html_e('Email Alerts', 'payaman-wishlist'); ?></h4>
					<p><?php esc_html_e('Automatic email notifications dispatched to users when wishlisted items come back in stock or drop in price.', 'payaman-wishlist'); ?></p>
				</div>
				<div class="payaman-doc-feature-card">
					<div class="payaman-doc-feature-card-accent" style="background:#2563eb"></div>
					<div class="payaman-doc-feature-icon-wrap" style="background:#eff6ff;color:#2563eb">📨</div>
					<h4><?php esc_html_e('Email Campaigns', 'payaman-wishlist'); ?></h4>
					<p><?php esc_html_e('Create targeted promotional email blasts for wishlisted items with scheduled recurring delivery (daily, weekly, monthly).', 'payaman-wishlist'); ?></p>
				</div>
				<div class="payaman-doc-feature-card">
					<div class="payaman-doc-feature-card-accent" style="background:#0891b2"></div>
					<div class="payaman-doc-feature-icon-wrap" style="background:#ecfeff;color:#0891b2">👤</div>
					<h4><?php esc_html_e('Guest Wishlist Support', 'payaman-wishlist'); ?></h4>
					<p><?php esc_html_e('Unregistered visitors can save items via browser cookies, which automatically migrate to their user account upon login.', 'payaman-wishlist'); ?></p>
				</div>
			</div>
		</div>

		<!-- Shortcode -->
		<div class="payaman-doc-section">
			<div class="payaman-doc-section-header">
				<span class="payaman-doc-section-number">04</span>
				<h3><?php esc_html_e('Shortcodes', 'payaman-wishlist'); ?></h3>
			</div>
			<div class="payaman-doc-code-box">
				<div class="payaman-doc-code-box-header">
					<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="16 18 22 12 16 6"/><polyline points="8 6 2 12 8 18"/></svg>
					<span><?php esc_html_e('Available Shortcode', 'payaman-wishlist'); ?></span>
				</div>
				<table class="payaman-doc-table">
					<thead>
						<tr>
							<th><?php esc_html_e('Shortcode', 'payaman-wishlist'); ?></th>
							<th><?php esc_html_e('Description', 'payaman-wishlist'); ?></th>
						</tr>
					</thead>
					<tbody>
						<tr>
							<td><code>[payaman_wishlist]</code></td>
							<td><?php esc_html_e('Renders the customer wishlist and collection interface.', 'payaman-wishlist'); ?></td>
						</tr>
					</tbody>
				</table>
			</div>
		</div>

		<!-- FAQ -->
		<div class="payaman-doc-section">
			<div class="payaman-doc-section-header">
				<span class="payaman-doc-section-number">05</span>
				<h3><?php esc_html_e('Frequently Asked Questions', 'payaman-wishlist'); ?></h3>
			</div>
			<div class="payaman-doc-faq">
				<div class="payaman-doc-faq-item">
					<div class="payaman-doc-faq-icon">❓</div>
					<div class="payaman-doc-faq-body">
						<h4><?php esc_html_e('How do I display the wishlist page?', 'payaman-wishlist'); ?></h4>
						<p><?php esc_html_e('Create a new WordPress page and paste the [payaman_wishlist] shortcode. Then select that page under Payaman Wishlist > General Setting > Wishlist Page.', 'payaman-wishlist'); ?></p>
					</div>
				</div>
				<div class="payaman-doc-faq-item">
					<div class="payaman-doc-faq-icon">❓</div>
					<div class="payaman-doc-faq-body">
						<h4><?php esc_html_e('What happens if a customer is not logged in?', 'payaman-wishlist'); ?></h4>
						<p><?php esc_html_e('Wishlist items are securely saved in browser cookies. Once the visitor registers or logs in, items are automatically migrated to their account.', 'payaman-wishlist'); ?></p>
					</div>
				</div>
				<div class="payaman-doc-faq-item">
					<div class="payaman-doc-faq-icon">❓</div>
					<div class="payaman-doc-faq-body">
						<h4><?php esc_html_e('How do automated email alerts work?', 'payaman-wishlist'); ?></h4>
						<p><?php esc_html_e('Enable "Price Drop" and/or "Back in Stock" in the General Setting tab. Notifications are dispatched automatically when stock is restored or product price drops.', 'payaman-wishlist'); ?></p>
					</div>
				</div>
				<div class="payaman-doc-faq-item">
					<div class="payaman-doc-faq-icon">❓</div>
					<div class="payaman-doc-faq-body">
						<h4><?php esc_html_e('Can I customize the wishlist button appearance?', 'payaman-wishlist'); ?></h4>
						<p><?php esc_html_e('Yes, customize button styling in the Button Setting tab. Choose between text or image buttons, set display positions, and configure label text.', 'payaman-wishlist'); ?></p>
					</div>
				</div>
			</div>
		</div>

	</div>
</div>
