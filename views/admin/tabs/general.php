<?php
if (! defined('ABSPATH')) {
	exit;
}
?>
<div class="form-section" id="tab-general" style="display: none;">
	<table class="form-table">
		<tr class="payaman_wishlist_enabled_row">
			<th scope="row"><label for="payaman_wishlist_enabled"><?php esc_html_e('Enabled', 'payaman_wishlist'); ?></label></th>
			<td>
				<label class="payaman_wishlist-switch">
					<input type="checkbox" name="payaman_wishlist_enabled" id="payaman_wishlist_enabled" value="yes" <?php checked(payaman_wishlist_setting('enabled'), 'yes'); ?> />
					<span class="payaman_wishlist-slider round"></span>
				</label>
				<p class="description"><?php esc_html_e('Enable Payaman Wishlist functionality', 'payaman_wishlist'); ?></p>
			</td>
		</tr>
		<tr class="payaman_wishlist_enabled_row">
			<th scope="row"><?php esc_html_e('Wishlist Page', 'payaman_wishlist'); ?></th>
			<td>
				<a href="<?php echo esc_url(site_url('wishlist-list')); ?>" target="_blank"><?php esc_html_e('Page Wishlist', 'payaman_wishlist'); ?></a>
				<p class="description"><?php esc_html_e('Default page to display wishlist list, or use shortcode [payaman_wishlist_list]', 'payaman_wishlist'); ?></p>
			</td>
		</tr>
		<tr class="payaman_wishlist_enabled_row">
			<th scope="row"><label for="payaman_wishlist_count"><?php esc_html_e('Display Payaman Wishlist Number', 'payaman_wishlist'); ?></label></th>
			<td>
				<label class="payaman_wishlist-switch">
					<input type="checkbox" name="payaman_wishlist_count" id="payaman_wishlist_count" value="yes" <?php checked(payaman_wishlist_setting('payaman_wishlist_count'), 'yes'); ?> />
					<span class="payaman_wishlist-slider round"></span>
				</label>
				<p class="description"><?php esc_html_e('Displays the number of wishlists in each product', 'payaman_wishlist'); ?> <br /><span style="color: red;"><?php esc_html_e('! this will impact on the speed of your website', 'payaman_wishlist'); ?></span></p>
			</td>
		</tr>
		
		<tr class="payaman_wishlist_enabled_row">
			<th scope="row"><label for="payaman_wishlist_required_login"><?php esc_html_e('Required Login', 'payaman_wishlist'); ?></label></th>
			<td>
				<label class="payaman_wishlist-switch">
					<input type="checkbox" name="payaman_wishlist_required_login" id="payaman_wishlist_required_login" value="yes" <?php checked(payaman_wishlist_setting('required_login'), 'yes'); ?> />
					<span class="payaman_wishlist-slider round"></span>
				</label>
				<p class="description"><?php esc_html_e('Users must be logged in to add to wishlist', 'payaman_wishlist'); ?></p>
			</td>
		</tr>
		<tr class="payaman_wishlist_enabled_row">
			<th scope="row"><label for="payaman_wishlist_remove_after_add_to_cart"><?php esc_html_e('Remove After Add to Cart', 'payaman_wishlist'); ?></label></th>
			<td>
				<label class="payaman_wishlist-switch">
					<input type="checkbox" name="payaman_wishlist_remove_after_add_to_cart" id="payaman_wishlist_remove_after_add_to_cart" value="yes" <?php checked(payaman_wishlist_setting('remove_after_add_to_cart'), 'yes'); ?> />
					<span class="payaman_wishlist-slider round"></span>
				</label>
				<p class="description"><?php esc_html_e('Automatically remove product from wishlist after adding to cart', 'payaman_wishlist'); ?></p>
			</td>
		</tr>
		<tr class="payaman_wishlist_enabled_row">
			<th scope="row"><?php esc_html_e('Marketing Alerts', 'payaman_wishlist'); ?></th>
			<td>
				<label>
					<input type="checkbox" name="enable_price_drop_alert" value="yes" <?php checked(payaman_wishlist_setting('enable_price_drop_alert'), 'yes'); ?> />
					<?php esc_html_e('Enable Price Drop Alerts', 'payaman_wishlist'); ?>
				</label>
				<p class="description"><?php esc_html_e('Send email to users when a product in their wishlist has a price drop.', 'payaman_wishlist'); ?></p>
				<br />
				<label>
					<input type="checkbox" name="enable_stock_alert" value="yes" <?php checked(payaman_wishlist_setting('enable_stock_alert'), 'yes'); ?> />
					<?php esc_html_e('Enable Back in Stock Alerts', 'payaman_wishlist'); ?>
				</label>
				<p class="description"><?php esc_html_e('Send email to users when an out-of-stock product in their wishlist becomes available.', 'payaman_wishlist'); ?></p>
			</td>
		</tr>
	</table>
</div>
