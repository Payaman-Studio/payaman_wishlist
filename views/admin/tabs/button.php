<?php
if (! defined('ABSPATH')) {
	exit;
}

$payaman_wishlist_image_val_off = payaman_wishlist_setting('image_val_off');
$payaman_wishlist_image_val_on  = payaman_wishlist_setting('image_val_on');
?>
<div class="form-section" id="tab-button" style="display: none;">
	<table class="form-table">
		<tr class="payaman_wishlist_enabled_row">
			<th scope="row"><?php esc_html_e('Display Button On', 'payaman-wishlist'); ?></th>
			<td>
				<table class="payaman_wishlist-default-table" cellspacing="0" cellpadding="0">
					<tr>
						<td><?php esc_html_e('Single Product', 'payaman-wishlist'); ?></td>
						<td><?php esc_html_e('Product Loop', 'payaman-wishlist'); ?></td>
					</tr>
					<tr>
						<td>
							<label class="payaman_wishlist-switch">
								<input type="checkbox" name="display_on[]" value="single_product" <?php echo !empty(payaman_wishlist_setting('display_on')) && in_array('single_product', payaman_wishlist_setting('display_on')) ? 'checked' : ''; ?> />
								<span class="payaman_wishlist-slider round"></span>
							</label>
						</td>
						<td>
							<label class="payaman_wishlist-switch">
								<input type="checkbox" name="display_on[]" value="loop_product" <?php echo !empty(payaman_wishlist_setting('display_on')) && in_array('loop_product', payaman_wishlist_setting('display_on')) ? 'checked' : ''; ?> />
								<span class="payaman_wishlist-slider round"></span>
							</label>
						</td>
					</tr>
				</table>
				<p class="description"><?php esc_html_e('Choose where your wishlist button will appear or you can use shortcode [payaman_wishlist_button]', 'payaman-wishlist'); ?></p>
			</td>
		</tr>
		<tr class="payaman_wishlist_enabled_row">
			<th scope="row"><label for="display_position_button"><?php esc_html_e('Button Position', 'payaman-wishlist'); ?></label></th>
			<td>
				<select name="display_position_button" id="display_position_button">
					<?php
					$payaman_wishlist_button_positions = array(
						'after_add_to_cart' => __('After Add to Cart', 'payaman-wishlist'),
						'overlay_top_left'  => __('Overlay Product Image', 'payaman-wishlist'),
					);
					$payaman_wishlist_current_position = payaman_wishlist_setting('display_position_button', 'after_add_to_cart');
					foreach ($payaman_wishlist_button_positions as $payaman_wishlist_pos_value => $payaman_wishlist_pos_label) :
					?>
						<option value="<?php echo esc_attr($payaman_wishlist_pos_value); ?>" <?php selected($payaman_wishlist_current_position, $payaman_wishlist_pos_value); ?>><?php echo esc_html($payaman_wishlist_pos_label); ?></option>
					<?php endforeach; ?>
				</select>
				<p class="description"><?php esc_html_e('Control where the wishlist button is injected.', 'payaman-wishlist'); ?></p>
			</td>
		</tr>
		<tr class="payaman_wishlist_enabled_row">
			<th scope="row"><label for="payaman_wishlist_type"><?php esc_html_e('Type', 'payaman-wishlist'); ?></label></th>
			<td>
				<select name="payaman_wishlist_type" id="payaman_wishlist_type">
					<option value="text" <?php selected(payaman_wishlist_setting('type_active'), 'text'); ?>><?php esc_html_e('Text', 'payaman-wishlist'); ?></option>
					<option value="image" <?php selected(payaman_wishlist_setting('type_active'), 'image'); ?>><?php esc_html_e('Image', 'payaman-wishlist'); ?></option>
				</select>
			</td>
		</tr>
	</table>

	<div id="payaman_wishlist_type_selected_text">
		<table class="form-table">
			<tr class="payaman_wishlist_enabled_row">
				<th scope="row"><label><?php esc_html_e('Button Text Off', 'payaman-wishlist'); ?></label></th>
				<td>
					<input type="text" name="payaman_wishlist_text_val_off" value="<?php echo esc_attr(payaman_wishlist_setting('val_off')); ?>" class="regular-text" />
					<p class="description"><?php esc_html_e('Button Text settings when the product has not entered the wishlists list', 'payaman-wishlist'); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row"><label><?php esc_html_e('Button Text On', 'payaman-wishlist'); ?></label></th>
				<td>
					<input type="text" name="payaman_wishlist_text_val_on" value="<?php echo esc_attr(payaman_wishlist_setting('val_on')); ?>" class="regular-text" />
					<p class="description"><?php esc_html_e('Button Text settings when the product is in the wishlists list', 'payaman-wishlist'); ?></p>
				</td>
			</tr>
		</table>
	</div>

	<div id="payaman_wishlist_type_selected_image" style="display: none;">
		<table class="form-table">
			<tr class="payaman_wishlist_enabled_row">
				<th scope="row"><label><?php esc_html_e('Button Image Off', 'payaman-wishlist'); ?></label></th>
				<td>
					<input type="button" class="button payaman_wishlist_image_upload" data-ids="payaman_wishlist_image_val_off" data-values="<?php echo esc_attr($payaman_wishlist_image_val_off); ?>" value="<?php esc_attr_e('Upload image', 'payaman-wishlist'); ?>" />
					<input type='hidden' name='payaman_wishlist_image_val_off' id='payaman_wishlist_image_val_off' value='<?php echo esc_attr($payaman_wishlist_image_val_off); ?>'>
					<div class='image-preview-wrapper' style="margin-top: 10px;">
						<img id='preview-payaman_wishlist_image_val_off' src='<?php echo esc_url(wp_get_attachment_url($payaman_wishlist_image_val_off)); ?>' height='36'>
					</div>
				</td>
			</tr>
			<tr>
				<th scope="row"><label><?php esc_html_e('Button Image On', 'payaman-wishlist'); ?></label></th>
				<td>
					<input type="button" class="button payaman_wishlist_image_upload" data-ids="payaman_wishlist_image_val_on" data-values="<?php echo esc_attr($payaman_wishlist_image_val_on); ?>" value="<?php esc_attr_e('Upload image', 'payaman-wishlist'); ?>" />
					<input type='hidden' name='payaman_wishlist_image_val_on' id='payaman_wishlist_image_val_on' value='<?php echo esc_attr($payaman_wishlist_image_val_on); ?>'>
					<div class='image-preview-wrapper' style="margin-top: 10px;">
						<img id='preview-payaman_wishlist_image_val_on' src='<?php echo esc_url(wp_get_attachment_url($payaman_wishlist_image_val_on)); ?>' height='36'>
					</div>
				</td>
			</tr>
		</table>
	</div>
</div>
