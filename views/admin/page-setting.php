<?php
if (! defined('ABSPATH')) {
	exit;
}
?>
<div class="wrap" id="payaman_wishlist-setting">
	<h1><?php esc_html_e('Payaman Wishlist', 'payaman_wishlist'); ?></h1>
	<hr />

	<ul class="payaman_wishlist-menu">
		<li><a href="#tab-dashboard" class="active"><?php esc_html_e('Dashboard', 'payaman_wishlist'); ?></a></li>
		<li><a href="#tab-general"><?php esc_html_e('General Setting', 'payaman_wishlist'); ?></a></li>
		<li><a href="#tab-button"><?php esc_html_e('Button Setting', 'payaman_wishlist'); ?></a></li>
		<li><a href="#tab-message"><?php esc_html_e('Message Setting', 'payaman_wishlist'); ?></a></li>
		<li><a href="#tab-promotional-email"><?php esc_html_e('Promotional Email', 'payaman_wishlist'); ?></a></li>
	</ul>

	<form method='post'>
		<?php
		include_once PAYAMAN_WISHLIST_PATH . 'views/admin/tabs/dashboard.php';
		include_once PAYAMAN_WISHLIST_PATH . 'views/admin/tabs/general.php';
		include_once PAYAMAN_WISHLIST_PATH . 'views/admin/tabs/button.php';
		include_once PAYAMAN_WISHLIST_PATH . 'views/admin/tabs/message.php';
		include_once PAYAMAN_WISHLIST_PATH . 'views/admin/tabs/promotional-email.php';
		?>

		<p class="submit" id="payaman_wishlist-submit-wrapper" style="display: none;">
			<input type="submit" name="submit" id="submit" class="button button-primary" value="<?php esc_attr_e('Save Settings', 'payaman_wishlist'); ?>">
		</p>

		<?php wp_nonce_field('payaman_wishlist_action_setting', 'payaman_wishlist_field_setting'); ?>
	</form>
</div>