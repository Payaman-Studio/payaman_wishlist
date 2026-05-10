<?php

if (! defined('ABSPATH')) {
	exit;
}

if (! class_exists('Payaman_Wishlist_Alerts')) {

	class Payaman_Wishlist_Alerts
	{
		public function __construct()
		{
			add_action('woocommerce_product_set_stock', array($this, 'handle_stock_change'));
			add_action('woocommerce_update_product', array($this, 'handle_product_update'), 10, 2);

			add_action('payaman_wishlist_bg_stock_alert', array($this, 'process_stock_alert'));
			add_action('payaman_wishlist_bg_price_alert', array($this, 'process_price_alert'));
		}

		public function handle_stock_change($product)
		{
			if (payaman_wishlist_setting('enable_stock_alert') !== 'yes') {
				return;
			}

			if ($product->is_in_stock()) {
				if (function_exists('as_enqueue_async_action')) {
					as_enqueue_async_action('payaman_wishlist_bg_stock_alert', array('product_id' => $product->get_id()));
				} else {
					$this->process_stock_alert($product->get_id());
				}
			}
		}

		public function handle_product_update($product_id, $product)
		{
			if (payaman_wishlist_setting('enable_price_drop_alert') !== 'yes') {
				return;
			}

			if ($product->is_on_sale()) {
				$last_alert = get_post_meta($product_id, '_payaman_wishlist_last_price_alert', true);
				$current_price = $product->get_price();

				if ($last_alert != $current_price) {
					update_post_meta($product_id, '_payaman_wishlist_last_price_alert', $current_price);
					
					if (function_exists('as_enqueue_async_action')) {
						as_enqueue_async_action('payaman_wishlist_bg_price_alert', array('product_id' => $product_id));
					} else {
						$this->process_price_alert($product_id);
					}
				}
			}
		}

		public function process_stock_alert($product_id)
		{
			$this->notify_users($product_id, 'stock');
		}

		public function process_price_alert($product_id)
		{
			$this->notify_users($product_id, 'price');
		}

		private function notify_users($product_id, $type)
		{
			$user_ids = payaman_wishlist_get_users_by_product($product_id);
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
					$subject = sprintf(__('Good news! %s is back in stock!', 'payaman_wishlist'), $product_name);
					$message = sprintf(
						__("Hi %s,\n\nThe product '%s' in your wishlist is now back in stock. Grab it before it's gone again!\n\nView product: %s", 'payaman_wishlist'),
						$user->display_name,
						$product_name,
						$product_url
					);
				} else if ($type === 'price') {
					$subject = sprintf(__('Price drop alert for %s!', 'payaman_wishlist'), $product_name);
					$message = sprintf(
						__("Hi %s,\n\nGreat news! The price of '%s' in your wishlist has just dropped. Check it out now!\n\nView product: %s", 'payaman_wishlist'),
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

	new Payaman_Wishlist_Alerts();
}