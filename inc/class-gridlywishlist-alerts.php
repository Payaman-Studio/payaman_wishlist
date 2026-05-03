<?php
/**
 * GridlyWishlist Alerts Class
 *
 * @package gridlywishlist
 * @version 1.0.0
 */

if (! defined('ABSPATH')) {
	exit;
}

if (! class_exists('GridlyWishlist_Alerts')) {

	class GridlyWishlist_Alerts
	{
		public function __construct()
		{
			// Hook for stock changes
			add_action('woocommerce_product_set_stock', array($this, 'handle_stock_change'));
			
			// Hook for general product updates (including price)
			add_action('woocommerce_update_product', array($this, 'handle_product_update'), 10, 2);
		}

		/**
		 * Handle stock changes.
		 */
		public function handle_stock_change($product)
		{
			if (gridlywishlist_setting('enable_stock_alert') !== 'yes') {
				return;
			}

			if ($product->is_in_stock()) {
				$this->notify_users($product->get_id(), 'stock');
			}
		}

		/**
		 * Handle product updates (price drop).
		 */
		public function handle_product_update($product_id, $product)
		{
			if (gridlywishlist_setting('enable_price_drop_alert') !== 'yes') {
				return;
			}

			// We need to compare current price with previous price.
			// This is slightly complex without a dedicated price history table.
			// For now, we'll check if it's on sale and it was NOT on sale before? 
			// Or just check if the current price is lower than regular price.
			
			if ($product->is_on_sale()) {
				// Simple logic: if it's on sale, notify once.
				// To avoid spam, we should ideally track if we already sent an alert for this sale.
				$last_alert = get_post_meta($product_id, '_gridlywishlist_last_price_alert', true);
				$current_price = $product->get_price();

				if ($last_alert != $current_price) {
					$this->notify_users($product_id, 'price');
					update_post_meta($product_id, '_gridlywishlist_last_price_alert', $current_price);
				}
			}
		}

		/**
		 * Notify users via email.
		 */
		private function notify_users($product_id, $type)
		{
			$user_ids = gridlywishlist_get_users_by_product($product_id);
			if (empty($user_ids)) {
				return;
			}

			$product = wc_get_product($product_id);
			$product_name = $product->get_name();
			$product_url = get_permalink($product_id);

			foreach ($user_ids as $user_id) {
				$user = get_userdata($user_id);
				if (! $user) continue;

				$to = $user->user_email;
				$subject = '';
				$message = '';

				if ($type === 'stock') {
					$subject = sprintf(__('Good news! %s is back in stock!', 'gridlywishlist'), $product_name);
					$message = sprintf(
						__("Hi %s,\n\nThe product '%s' in your wishlist is now back in stock. Grab it before it's gone again!\n\nView product: %s", 'gridlywishlist'),
						$user->display_name,
						$product_name,
						$product_url
					);
				} else if ($type === 'price') {
					$subject = sprintf(__('Price drop alert for %s!', 'gridlywishlist'), $product_name);
					$message = sprintf(
						__("Hi %s,\n\nGreat news! The price of '%s' in your wishlist has just dropped. Check it out now!\n\nView product: %s", 'gridlywishlist'),
						$user->display_name,
						$product_name,
						$product_url
					);
				}

				if ($subject && $message) {
					wp_mail($to, $subject, $message);
				}
			}
		}
	}

	new GridlyWishlist_Alerts();
}
