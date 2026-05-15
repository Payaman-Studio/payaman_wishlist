<?php
/**
 * Payaman_Wishlist AJAX Handlers
 *
 * @package payaman_wishlist
 * @version 1.0.0
 */

if (! defined('ABSPATH')) {
	exit;
}

if (! class_exists('Payaman_Wishlist_AJAX')) {

	class Payaman_Wishlist_AJAX
	{
		public function __construct()
		{
			add_action('wp_ajax_update_payaman_wishlist', array($this, 'ajax_update_payaman_wishlist_callback'));
			add_action('wp_ajax_nopriv_update_payaman_wishlist', array($this, 'ajax_update_payaman_wishlist_callback'));
			add_action('wp_ajax_payaman_wishlist_bulk_remove', array($this, 'ajax_payaman_wishlist_bulk_remove_callback'));
			add_action('wp_ajax_nopriv_payaman_wishlist_bulk_remove', array($this, 'ajax_payaman_wishlist_bulk_remove_callback'));
			add_action('wp_ajax_payaman_wishlist_collection_create', array($this, 'ajax_payaman_wishlist_collection_create'));
			add_action('wp_ajax_payaman_wishlist_collection_update', array($this, 'ajax_payaman_wishlist_collection_update'));
			add_action('wp_ajax_payaman_wishlist_collection_delete', array($this, 'ajax_payaman_wishlist_collection_delete'));
			add_action('wp_ajax_payaman_wishlist_collection_move_items', array($this, 'ajax_payaman_wishlist_collection_move_items'));
			add_action('wp_ajax_payaman_wishlist_save_campaign', array($this, 'ajax_save_campaign'));
			add_action('wp_ajax_payaman_wishlist_send_campaign', array($this, 'ajax_send_campaign'));
			add_action('wp_ajax_payaman_wishlist_delete_campaign', array($this, 'ajax_delete_campaign'));
		}

		public function ajax_update_payaman_wishlist_callback()
		{
			check_ajax_referer('payaman_wishlist_toggle', 'nonce');

			$product_id   = isset($_POST['product_id']) ? absint(wp_unslash($_POST['product_id'])) : 0;
			$fav_action   = isset($_POST['fav_action']) ? sanitize_key(wp_unslash($_POST['fav_action'])) : '';
			$collection_id = isset($_POST['collection_id']) ? sanitize_text_field(wp_unslash($_POST['collection_id'])) : '';
			$variation_id  = isset($_POST['variation_id']) ? absint(wp_unslash($_POST['variation_id'])) : 0;

			if (! $product_id || ! in_array($fav_action, array('insert', 'delete'), true)) {
				wp_send_json_error(array('message' => __('Invalid request data.', 'payaman_wishlist')), 400);
			}

			if ('product' !== get_post_type($product_id)) {
				wp_send_json_error(array('message' => __('Invalid product.', 'payaman_wishlist')), 400);
			}

			if ('yes' === payaman_wishlist_setting('required_login', 'no') && ! is_user_logged_in()) {
				wp_send_json_error(array('message' => payaman_wishlist_setting('required_login_message', __('You must be logged in.', 'payaman_wishlist'))), 403);
			}

			$current_user_id = get_current_user_id();
			$fav_user = is_user_logged_in() ? (string) $current_user_id : (string) payaman_wishlist_client_ip();
			$collection_record = $current_user_id ? payaman_wishlist_validate_collection($current_user_id, $collection_id) : null;
			$collection_id = $collection_record ? $collection_record['id'] : '';
			$wishlists = payaman_wishlist_get_wishlists($product_id);
			$user_still_has_product = false;
			if ($current_user_id) {
				$user_still_has_product = payaman_wishlist_update_user_wishlists($current_user_id, $product_id, $fav_action, $collection_id, $variation_id);
			}

			if ('insert' === $fav_action && ! in_array($fav_user, $wishlists, true)) {
				$wishlists[] = $fav_user;
			} elseif ('delete' === $fav_action) {
				$should_remove_user = ! $current_user_id || ! $user_still_has_product;
				if ($should_remove_user) {
					$wishlists = array_values(array_filter(
						$wishlists,
						function ($wishlist) use ($fav_user) {
							return $wishlist !== $fav_user;
						}
					));
				}
			}

			if (payaman_wishlist_store_wishlists($product_id, $wishlists) === false) {
				wp_send_json_error(array('message' => __('Failed to update wishlists.', 'payaman_wishlist')), 500);
			}

			$count = count($wishlists);
			$collections_summary = $current_user_id ? payaman_wishlist_prepare_collections_response($current_user_id) : array();

			$response = array(
				'count' => 'yes' === payaman_wishlist_setting('payaman_wishlist_count', 'no') ? $count : '',
				'state' => in_array($fav_user, $wishlists, true) ? 'on' : 'off',
				'collection_id' => $collection_id,
				'collection_state' => ('insert' === $fav_action) ? 'on' : 'off',
				'collections' => $collections_summary,
			);

			wp_send_json_success($response);
		}

		public function ajax_payaman_wishlist_bulk_remove_callback()
		{
			check_ajax_referer('payaman_wishlist_toggle', 'nonce');

			$product_ids = isset($_POST['product_ids']) ? (array) wp_unslash($_POST['product_ids']) : array();
			$product_ids = array_values(array_unique(array_filter(array_map('absint', $product_ids))));
			$collection_id = isset($_POST['collection_id']) ? sanitize_text_field(wp_unslash($_POST['collection_id'])) : '';

			if (empty($product_ids)) {
				wp_send_json_error(array('message' => __('Invalid selection.', 'payaman_wishlist')), 400);
			}

			if ('yes' === payaman_wishlist_setting('required_login', 'no') && ! is_user_logged_in()) {
				wp_send_json_error(array('message' => payaman_wishlist_setting('required_login_message', __('You must be logged in.', 'payaman_wishlist'))), 403);
			}

			$current_user_id = get_current_user_id();
			$fav_user = is_user_logged_in() ? (string) $current_user_id : (string) payaman_wishlist_client_ip();
			$collection_record = $current_user_id ? payaman_wishlist_validate_collection($current_user_id, $collection_id, true) : null;
			$collection_id = $collection_record ? $collection_record['id'] : '';
			$removed  = array();

			foreach ($product_ids as $product_id) {
				if ('product' !== get_post_type($product_id)) {
					continue;
				}

				$wishlists = payaman_wishlist_get_wishlists($product_id);
				if (empty($wishlists)) {
					continue;
				}

				$wishlists = array_values(
					array_filter(
						$wishlists,
						function ($wishlist) use ($fav_user) {
							return $wishlist !== $fav_user;
						}
					)
				);

				if (payaman_wishlist_store_wishlists($product_id, $wishlists) === false) {
					continue;
				}

				$removed[] = $product_id;

				if ($current_user_id) {
					payaman_wishlist_update_user_wishlists($current_user_id, $product_id, 'delete', $collection_id);
				}
			}

			wp_send_json_success(array('removed' => $removed));
		}

		public function ajax_payaman_wishlist_collection_create()
		{
			check_ajax_referer('payaman_wishlist_toggle', 'nonce');

			if (! is_user_logged_in()) {
				wp_send_json_error(array('message' => __('You must be logged in to manage collections.', 'payaman_wishlist')), 403);
			}

			$user_id   = get_current_user_id();
			$name      = isset($_POST['name']) ? sanitize_text_field(wp_unslash($_POST['name'])) : '';
			$is_public = ! empty($_POST['is_public']) && filter_var($_POST['is_public'], FILTER_VALIDATE_BOOLEAN);

			$result = payaman_wishlist_create_collection($user_id, $name, $is_public);

			if (is_wp_error($result)) {
				wp_send_json_error(array('message' => $result->get_error_message()), 400);
			}

			wp_send_json_success(array(
				'collection'  => $result,
				'collections' => payaman_wishlist_prepare_collections_response($user_id),
			));
		}

		public function ajax_payaman_wishlist_collection_update()
		{
			check_ajax_referer('payaman_wishlist_toggle', 'nonce');

			if (! is_user_logged_in()) {
				wp_send_json_error(array('message' => __('You must be logged in to manage collections.', 'payaman_wishlist')), 403);
			}

			$user_id = get_current_user_id();
			$collection_id = isset($_POST['collection_id']) ? sanitize_text_field(wp_unslash($_POST['collection_id'])) : '';
			$name = isset($_POST['name']) ? sanitize_text_field(wp_unslash($_POST['name'])) : '';
			$is_public = isset($_POST['is_public']) ? filter_var($_POST['is_public'], FILTER_VALIDATE_BOOLEAN) : null;

			$args = array();
			if (! empty($name)) {
				$args['name'] = $name;
			}
			if (null !== $is_public) {
				$args['is_public'] = $is_public;
			}

			$result = payaman_wishlist_update_collection($user_id, $collection_id, $args);

			if (is_wp_error($result)) {
				wp_send_json_error(array('message' => $result->get_error_message()), 400);
			}

			wp_send_json_success(array(
				'collection' => $result,
				'collections' => payaman_wishlist_prepare_collections_response($user_id),
			));
		}

		public function ajax_payaman_wishlist_collection_delete()
		{
			check_ajax_referer('payaman_wishlist_toggle', 'nonce');

			if (! is_user_logged_in()) {
				wp_send_json_error(array('message' => __('You must be logged in to manage collections.', 'payaman_wishlist')), 403);
			}

			$user_id = get_current_user_id();
			$collection_id = isset($_POST['collection_id']) ? sanitize_text_field(wp_unslash($_POST['collection_id'])) : '';

			$result = payaman_wishlist_delete_collection($user_id, $collection_id);

			if (is_wp_error($result)) {
				wp_send_json_error(array('message' => $result->get_error_message()), 400);
			}

			wp_send_json_success(array(
				'message' => __('Collection deleted successfully.', 'payaman_wishlist'),
				'collections' => payaman_wishlist_prepare_collections_response($user_id),
			));
		}

		public function ajax_payaman_wishlist_collection_move_items()
		{
			check_ajax_referer('payaman_wishlist_toggle', 'nonce');

			if (! is_user_logged_in()) {
				wp_send_json_error(array('message' => __('You must be logged in to manage collections.', 'payaman_wishlist')), 403);
			}

			$user_id = get_current_user_id();
			$product_ids = isset($_POST['product_ids']) ? (array) wp_unslash($_POST['product_ids']) : array();
			$product_ids = array_values(array_unique(array_filter(array_map('absint', $product_ids))));
			$target_collection_id = isset($_POST['target_collection_id']) ? sanitize_text_field(wp_unslash($_POST['target_collection_id'])) : '';

			if (empty($product_ids)) {
				wp_send_json_error(array('message' => __('Invalid selection.', 'payaman_wishlist')), 400);
			}

			$result = payaman_wishlist_move_items_to_collection($user_id, $product_ids, $target_collection_id);

			if (is_wp_error($result)) {
				wp_send_json_error(array('message' => $result->get_error_message()), 400);
			}

			wp_send_json_success(array(
				'moved' => $product_ids,
				'collections' => payaman_wishlist_prepare_collections_response($user_id),
			));
		}
	public function ajax_save_campaign()
	{
		check_ajax_referer('payaman_wishlist_promo_email', 'nonce');

		if (! current_user_can('manage_options')) {
			wp_send_json_error(array('message' => __('Insufficient permissions.', 'payaman_wishlist')), 403);
		}

		$name        = isset($_POST['name']) ? sanitize_text_field(wp_unslash($_POST['name'])) : '';
		$subject     = isset($_POST['subject']) ? sanitize_text_field(wp_unslash($_POST['subject'])) : '';
		$body        = isset($_POST['body']) ? wp_kses_post(wp_unslash($_POST['body'])) : '';
		$product_ids = isset($_POST['product_ids']) ? (array) wp_unslash($_POST['product_ids']) : array();

		if (! $name || ! $subject || ! $body || empty($product_ids)) {
			wp_send_json_error(array('message' => __('Please fill in all fields.', 'payaman_wishlist')), 400);
		}

		$campaigns = new Payaman_Wishlist_Campaigns();
		$id = $campaigns->create(array(
			'name'        => $name,
			'subject'     => $subject,
			'body'        => $body,
			'product_ids' => $product_ids,
		));

		if (! $id) {
			wp_send_json_error(array('message' => __('Failed to save campaign.', 'payaman_wishlist')), 500);
		}

		wp_send_json_success(array('message' => __('Campaign saved successfully.', 'payaman_wishlist'), 'id' => $id));
	}

	public function ajax_send_campaign()
	{
		check_ajax_referer('payaman_wishlist_promo_email', 'nonce');

		if (! current_user_can('manage_options')) {
			wp_send_json_error(array('message' => __('Insufficient permissions.', 'payaman_wishlist')), 403);
		}

		$campaign_id = isset($_POST['campaign_id']) ? absint(wp_unslash($_POST['campaign_id'])) : 0;
		if (! $campaign_id) {
			wp_send_json_error(array('message' => __('Invalid campaign.', 'payaman_wishlist')), 400);
		}

		$campaigns = new Payaman_Wishlist_Campaigns();
		$result = $campaigns->send($campaign_id);

		if (is_wp_error($result)) {
			wp_send_json_error(array('message' => $result->get_error_message()), 400);
		}

		$message = sprintf(
			__('Campaign sent! %1$d of %2$d email(s) delivered.', 'payaman_wishlist'),
			$result['sent'],
			$result['targeted']
		);

		wp_send_json_success(array('message' => $message, 'sent' => $result['sent'], 'targeted' => $result['targeted']));
	}

	public function ajax_delete_campaign()
	{
		check_ajax_referer('payaman_wishlist_promo_email', 'nonce');

		if (! current_user_can('manage_options')) {
			wp_send_json_error(array('message' => __('Insufficient permissions.', 'payaman_wishlist')), 403);
		}

		$campaign_id = isset($_POST['campaign_id']) ? absint(wp_unslash($_POST['campaign_id'])) : 0;
		if (! $campaign_id) {
			wp_send_json_error(array('message' => __('Invalid campaign.', 'payaman_wishlist')), 400);
		}

		$campaigns = new Payaman_Wishlist_Campaigns();
		$campaigns->delete($campaign_id);

		wp_send_json_success(array('message' => __('Campaign deleted.', 'payaman_wishlist')));
	}
	}

	new Payaman_Wishlist_AJAX();
}
