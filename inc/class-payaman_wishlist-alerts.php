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
			$site_name = get_bloginfo('name');

			$default_stock_subject = 'Good news! {product_name} is back in stock!';
			$default_stock_body = "Hi {user_name},\n\nThe product '{product_name}' in your wishlist is now back in stock!\n\nView product: {product_url}";
			$default_price_subject = 'Price drop alert for {product_name}!';
			$default_price_body = "Hi {user_name},\n\nGreat news! The price of '{product_name}' in your wishlist has just dropped.\n\nView product: {product_url}";

			$raw_subject = ($type === 'stock') ? payaman_wishlist_setting('email_stock_subject', $default_stock_subject) : payaman_wishlist_setting('email_price_subject', $default_price_subject);
			$raw_body = ($type === 'stock') ? payaman_wishlist_setting('email_stock_body', $default_stock_body) : payaman_wishlist_setting('email_price_body', $default_price_body);

			foreach ($user_ids as $user_id) {
				$user = get_userdata($user_id);
				if (! $user) continue;

				$replacements = array(
					'{user_name}'    => $user->display_name,
					'{product_name}' => $product_name,
					'{product_url}'  => $product_url,
					'{site_name}'    => $site_name
				);

				$subject = str_replace(array_keys($replacements), array_values($replacements), $raw_subject);
				$message = str_replace(array_keys($replacements), array_values($replacements), $raw_body);

				wp_mail($user->user_email, $subject, $message);
			}
		}
	}

	new Payaman_Wishlist_Alerts();
}