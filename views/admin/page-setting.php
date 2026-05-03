<div class="wrap" id="gridlywishlist-setting">
	<h1><?php esc_html_e('Gridly Wishlist Settings', 'gridlywishlist'); ?></h1>
	<hr />

	<ul class="gridlywishlist-menu">
		<li><a href="#tab-dashboard" class="active"><?php esc_html_e('Dashboard', 'gridlywishlist'); ?></a></li>
		<li><a href="#tab-general"><?php esc_html_e('General Setting', 'gridlywishlist'); ?></a></li>
		<li><a href="#tab-button"><?php esc_html_e('Button Setting', 'gridlywishlist'); ?></a></li>
		<li><a href="#tab-message"><?php esc_html_e('Message Setting', 'gridlywishlist'); ?></a></li>
	</ul>

	<div class="form-section" id="tab-dashboard">
		<?php
		$stats = gridlywishlist_get_stats();
		$top_products = gridlywishlist_get_top_products(10);
		?>
		<div class="gridlywishlist-stats-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px;">
			<div class="gridlywishlist-stat-card" style="background: #fff; padding: 20px; border: 1px solid #ccc; border-radius: 4px; text-align: center;">
				<h3 style="margin: 0 0 10px 0;"><?php esc_html_e('Total Wishlisted Items', 'gridlywishlist'); ?></h3>
				<span style="font-size: 24px; font-weight: bold; color: #0073aa;"><?php echo esc_html($stats['total_items']); ?></span>
			</div>
			<div class="gridlywishlist-stat-card" style="background: #fff; padding: 20px; border: 1px solid #ccc; border-radius: 4px; text-align: center;">
				<h3 style="margin: 0 0 10px 0;"><?php esc_html_e('Total Collections', 'gridlywishlist'); ?></h3>
				<span style="font-size: 24px; font-weight: bold; color: #0073aa;"><?php echo esc_html($stats['total_collections']); ?></span>
			</div>
			<div class="gridlywishlist-stat-card" style="background: #fff; padding: 20px; border: 1px solid #ccc; border-radius: 4px; text-align: center;">
				<h3 style="margin: 0 0 10px 0;"><?php esc_html_e('Active Users', 'gridlywishlist'); ?></h3>
				<span style="font-size: 24px; font-weight: bold; color: #0073aa;"><?php echo esc_html($stats['total_users']); ?></span>
			</div>
		</div>

		<h3><?php esc_html_e('Top 10 Most Desired Products', 'gridlywishlist'); ?></h3>
		<table class="wp-list-table widefat fixed striped">
			<thead>
				<tr>
					<th width="80"><?php esc_html_e('Image', 'gridlywishlist'); ?></th>
					<th><?php esc_html_e('Product Name', 'gridlywishlist'); ?></th>
					<th><?php esc_html_e('Price', 'gridlywishlist'); ?></th>
					<th width="150"><?php esc_html_e('Wishlist Count', 'gridlywishlist'); ?></th>
					<th width="100"><?php esc_html_e('Action', 'gridlywishlist'); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php if (! empty($top_products)) : ?>
					<?php foreach ($top_products as $product) : ?>
						<tr>
							<td><?php echo $product['image']; ?></td>
							<td><strong><?php echo esc_html($product['name']); ?></strong></td>
							<td><?php echo $product['price']; ?></td>
							<td><span class="badge" style="background: #0073aa; color: #fff; padding: 2px 8px; border-radius: 10px;"><?php echo esc_html($product['wishlist_count']); ?></span></td>
							<td><a href="<?php echo esc_url($product['link']); ?>" class="button button-small"><?php esc_html_e('Edit', 'gridlywishlist'); ?></a></td>
						</tr>
					<?php endforeach; ?>
				<?php else : ?>
					<tr>
						<td colspan="5"><?php esc_html_e('No data available yet.', 'gridlywishlist'); ?></td>
					</tr>
				<?php endif; ?>
			</tbody>
		</table>
	</div>

	<form method='post'>
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

		<div class="form-section" id="tab-button" style="display: none;">
			<table class="form-table">
				<tr>
					<th scope="row"><label for="gridlywishlist_type"><?php esc_html_e('Type', 'gridlywishlist'); ?></label></th>
					<td>
						<select name="gridlywishlist_type" id="gridlywishlist_type">
							<option value="text" <?php selected(gridlywishlist_setting('type_active'), 'text'); ?>><?php esc_html_e('Text', 'gridlywishlist'); ?></option>
							<option value="image" <?php selected(gridlywishlist_setting('type_active'), 'image'); ?>><?php esc_html_e('Image', 'gridlywishlist'); ?></option>
						</select>
					</td>
				</tr>
			</table>

			<div id="gridlywishlist_type_selected_text">
				<table class="form-table">
					<tr>
						<th scope="row"><label><?php esc_html_e('Button Text Off', 'gridlywishlist'); ?></label></th>
						<td>
							<input type="text" name="gridlywishlist_text_val_off" value="<?php echo esc_attr(gridlywishlist_setting('val_off')); ?>" class="regular-text" />
							<p class="description"><?php esc_html_e('Button Text settings when the product has not entered the wishlists list', 'gridlywishlist'); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label><?php esc_html_e('Button Text On', 'gridlywishlist'); ?></label></th>
						<td>
							<input type="text" name="gridlywishlist_text_val_on" value="<?php echo esc_attr(gridlywishlist_setting('val_on')); ?>" class="regular-text" />
							<p class="description"><?php esc_html_e('Button Text settings when the product is in the wishlists list', 'gridlywishlist'); ?></p>
						</td>
					</tr>
				</table>
			</div>

			<div id="gridlywishlist_type_selected_image" style="display: none;">
				<table class="form-table">
					<tr>
						<th scope="row"><label><?php esc_html_e('Button Image Off', 'gridlywishlist'); ?></label></th>
						<td>
							<input type="button" class="button gridlywishlist_image_upload" data-ids="gridlywishlist_image_val_off" data-values="<?php echo esc_attr($gridlywishlist_image_val_off); ?>" value="<?php esc_attr_e('Upload image', 'gridlywishlist'); ?>" />
							<input type='hidden' name='gridlywishlist_image_val_off' id='gridlywishlist_image_val_off' value='<?php echo esc_attr($gridlywishlist_image_val_off); ?>'>
							<div class='image-preview-wrapper' style="margin-top: 10px;">
								<img id='preview-gridlywishlist_image_val_off' src='<?php echo esc_url(wp_get_attachment_url($gridlywishlist_image_val_off)); ?>' height='36'>
							</div>
						</td>
					</tr>
					<tr>
						<th scope="row"><label><?php esc_html_e('Button Image On', 'gridlywishlist'); ?></label></th>
						<td>
							<input type="button" class="button gridlywishlist_image_upload" data-ids="gridlywishlist_image_val_on" data-values="<?php echo esc_attr($gridlywishlist_image_val_on); ?>" value="<?php esc_attr_e('Upload image', 'gridlywishlist'); ?>" />
							<input type='hidden' name='gridlywishlist_image_val_on' id='gridlywishlist_image_val_on' value='<?php echo esc_attr($gridlywishlist_image_val_on); ?>'>
							<div class='image-preview-wrapper' style="margin-top: 10px;">
								<img id='preview-gridlywishlist_image_val_on' src='<?php echo esc_url(wp_get_attachment_url($gridlywishlist_image_val_on)); ?>' height='36'>
							</div>
						</td>
					</tr>
				</table>
			</div>
		</div>

		<div class="form-section" id="tab-message" style="display: none;">
			<table class="form-table">
				<tr>
					<th scope="row"><label><?php esc_html_e('Add Success Message', 'gridlywishlist'); ?></label></th>
					<td>
						<label class="gridlywishlist-switch">
							<input type="checkbox" name="enable_add_success_message" value="yes" <?php checked(gridlywishlist_setting('enable_add_success_message'), 'yes'); ?>>
							<span class="gridlywishlist-slider round"></span>
						</label>
						<br /><br />
						<input type="text" name="add_success_message" value="<?php echo esc_attr(gridlywishlist_setting('add_success')); ?>" class="large-text">
					</td>
				</tr>
				<tr>
					<th scope="row"><label><?php esc_html_e('Remove Success Message', 'gridlywishlist'); ?></label></th>
					<td>
						<label class="gridlywishlist-switch">
							<input type="checkbox" name="enable_remove_success_message" value="yes" <?php checked(gridlywishlist_setting('enable_remove_success_message'), 'yes'); ?>>
							<span class="gridlywishlist-slider round"></span>
						</label>
						<br /><br />
						<input type="text" name="remove_success_message" value="<?php echo esc_attr(gridlywishlist_setting('remove_success')); ?>" class="large-text">
					</td>
				</tr>
				<tr>
					<th scope="row"><label><?php esc_html_e('Required Login Message', 'gridlywishlist'); ?></label></th>
					<td>
						<input type="text" name="required_login_message" value="<?php echo esc_attr(gridlywishlist_setting('required_login_message')); ?>" class="large-text">
					</td>
				</tr>
			</table>
		</div>

		<p class="submit" id="gridlywishlist-submit-wrapper" style="display: none;">
			<input type="submit" name="submit" id="submit" class="button button-primary" value="<?php esc_attr_e('Save Settings', 'gridlywishlist'); ?>">
		</p>

		<?php wp_nonce_field('gridlywishlist_action_setting', 'gridlywishlist_field_setting'); ?>
	</form>
</div>