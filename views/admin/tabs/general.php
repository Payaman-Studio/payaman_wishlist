<?php
if (! defined('ABSPATH')) {
	exit;
}
?>
<div class="form-section" id="tab-general" style="display: none;">
	<table class="form-table">
		<tr>
			<th scope="row"><label for="gridlywishlist_enabled"><?php esc_html_e('Enabled', 'gridlywishlist'); ?></label></th>
			<td>
				<label class="gridlywishlist-switch">
					<input type="checkbox" name="gridlywishlist_enabled" id="gridlywishlist_enabled" value="yes" <?php checked(gridlywishlist_setting('enabled'), 'yes'); ?> />
					<span class="gridlywishlist-slider round"></span>
				</label>
				<p class="description"><?php esc_html_e('Enable Gridly Wishlist functionality', 'gridlywishlist'); ?></p>
			</td>
		</tr>
		<tr>
			<th scope="row"><?php esc_html_e('Wishlist Page', 'gridlywishlist'); ?></th>
			<td>
				<a href="<?php echo esc_url(site_url('wishlist-list')); ?>" target="_blank"><?php esc_html_e('Page Wishlist', 'gridlywishlist'); ?></a>
				<p class="description"><?php esc_html_e('Default page to display wishlist list, or use shortcode [gridlywishlist_list]', 'gridlywishlist'); ?></p>
			</td>
		</tr>
		<tr>
			<th scope="row"><label for="gridlywishlist_count"><?php esc_html_e('Display Gridly Wishlist Number', 'gridlywishlist'); ?></label></th>
			<td>
				<label class="gridlywishlist-switch">
					<input type="checkbox" name="gridlywishlist_count" id="gridlywishlist_count" value="yes" <?php checked(gridlywishlist_setting('gridlywishlist_count'), 'yes'); ?> />
					<span class="gridlywishlist-slider round"></span>
				</label>
				<p class="description"><?php esc_html_e('Displays the number of wishlists in each product', 'gridlywishlist'); ?> <br /><span style="color: red;"><?php esc_html_e('! this will impact on the speed of your website', 'gridlywishlist'); ?></span></p>
			</td>
		</tr>
		<tr>
			<th scope="row"><?php esc_html_e('Display Button On', 'gridlywishlist'); ?></th>
			<td>
				<table class="gridlywishlist-default-table">
					<tr>
						<td width="50">
							<label class="gridlywishlist-switch">
								<input type="checkbox" name="display_on[]" value="single_product" <?php echo !empty(gridlywishlist_setting('display_on')) && in_array('single_product', gridlywishlist_setting('display_on')) ? 'checked' : ''; ?> />
								<span class="gridlywishlist-slider round"></span>
							</label>
						</td>
						<td><?php esc_html_e('Single Product', 'gridlywishlist'); ?></td>
					</tr>
					<tr>
						<td>
							<label class="gridlywishlist-switch">
								<input type="checkbox" name="display_on[]" value="loop_product" <?php echo !empty(gridlywishlist_setting('display_on')) && in_array('loop_product', gridlywishlist_setting('display_on')) ? 'checked' : ''; ?> />
								<span class="gridlywishlist-slider round"></span>
							</label>
						</td>
						<td><?php esc_html_e('Product Loop', 'gridlywishlist'); ?></td>
					</tr>
				</table>
				<p class="description"><?php esc_html_e('Choose where your wishlist button will appear or you can use shortcode [gridlywishlist_button]', 'gridlywishlist'); ?></p>
			</td>
		</tr>
		<tr>
			<th scope="row"><label for="display_position_button"><?php esc_html_e('Button Position', 'gridlywishlist'); ?></label></th>
			<td>
				<select name="display_position_button" id="display_position_button">
					<?php
					$button_positions = array(
						'after_add_to_cart' => __('After Add to Cart', 'gridlywishlist'),
						'overlay_top_left'  => __('Overlay Product Image', 'gridlywishlist'),
					);
					$current_position = gridlywishlist_setting('display_position_button', 'after_add_to_cart');
					foreach ($button_positions as $value => $label) :
					?>
						<option value="<?php echo esc_attr($value); ?>" <?php selected($current_position, $value); ?>><?php echo esc_html($label); ?></option>
					<?php endforeach; ?>
				</select>
				<p class="description"><?php esc_html_e('Control where the wishlist button is injected.', 'gridlywishlist'); ?></p>
			</td>
		</tr>
		<tr>
			<th scope="row"><label for="gridlywishlist_required_login"><?php esc_html_e('Required Login', 'gridlywishlist'); ?></label></th>
			<td>
				<label class="gridlywishlist-switch">
					<input type="checkbox" name="gridlywishlist_required_login" id="gridlywishlist_required_login" value="yes" <?php checked(gridlywishlist_setting('required_login'), 'yes'); ?> />
					<span class="gridlywishlist-slider round"></span>
				</label>
				<p class="description"><?php esc_html_e('Users must be logged in to add to wishlist', 'gridlywishlist'); ?></p>
			</td>
		</tr>
		<tr>
			<th scope="row"><label for="gridlywishlist_remove_after_add_to_cart"><?php esc_html_e('Remove After Add to Cart', 'gridlywishlist'); ?></label></th>
			<td>
				<label class="gridlywishlist-switch">
					<input type="checkbox" name="gridlywishlist_remove_after_add_to_cart" id="gridlywishlist_remove_after_add_to_cart" value="yes" <?php checked(gridlywishlist_setting('remove_after_add_to_cart'), 'yes'); ?> />
					<span class="gridlywishlist-slider round"></span>
				</label>
				<p class="description"><?php esc_html_e('Automatically remove product from wishlist after adding to cart', 'gridlywishlist'); ?></p>
			</td>
		</tr>
		<tr>
			<th scope="row"><?php esc_html_e('Marketing Alerts', 'gridlywishlist'); ?></th>
			<td>
				<label>
					<input type="checkbox" name="enable_price_drop_alert" value="yes" <?php checked(gridlywishlist_setting('enable_price_drop_alert'), 'yes'); ?> />
					<?php esc_html_e('Enable Price Drop Alerts', 'gridlywishlist'); ?>
				</label>
				<p class="description"><?php esc_html_e('Send email to users when a product in their wishlist has a price drop.', 'gridlywishlist'); ?></p>
				<br />
				<label>
					<input type="checkbox" name="enable_stock_alert" value="yes" <?php checked(gridlywishlist_setting('enable_stock_alert'), 'yes'); ?> />
					<?php esc_html_e('Enable Back in Stock Alerts', 'gridlywishlist'); ?>
				</label>
				<p class="description"><?php esc_html_e('Send email to users when an out-of-stock product in their wishlist becomes available.', 'gridlywishlist'); ?></p>
			</td>
		</tr>
	</table>
</div>
