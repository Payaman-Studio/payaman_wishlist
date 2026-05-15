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
			add_action('wp_ajax_payaman_wishlist_get_campaign', array($this, 'ajax_get_campaign'));
			add_action('wp_ajax_payaman_wishlist_update_campaign', array($this, 'ajax_update_campaign'));
			add_action('wp_ajax_payaman_wishlist_send_campaign', array($this, 'ajax_send_campaign'));
			add_action('wp_ajax_payaman_wishlist_delete_campaign', array($this, 'ajax_delete_campaign'));
			add_action('wp_ajax_payaman_wishlist_process_due', array($this, 'ajax_process_due'));
			add_action('wp_ajax_payaman_wishlist_get_campaigns', array($this, 'ajax_get_campaigns'));
			add_action('wp_ajax_payaman_wishlist_pause_campaign', array($this, 'ajax_pause_campaign'));
			add_action('wp_ajax_payaman_wishlist_resume_campaign', array($this, 'ajax_resume_campaign'));
			add_action('wp_ajax_payaman_wishlist_preview_campaign', array($this, 'ajax_preview_campaign'));
			add_action('wp_ajax_payaman_wishlist_test_campaign', array($this, 'ajax_test_campaign'));
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

		$name           = isset($_POST['name']) ? sanitize_text_field(wp_unslash($_POST['name'])) : '';
		$subject        = isset($_POST['subject']) ? sanitize_text_field(wp_unslash($_POST['subject'])) : '';
		$body           = isset($_POST['body']) ? wp_kses_post(wp_unslash($_POST['body'])) : '';
		$product_ids    = isset($_POST['product_ids']) ? (array) wp_unslash($_POST['product_ids']) : array();
		$send_type      = isset($_POST['send_type']) ? sanitize_text_field(wp_unslash($_POST['send_type'])) : 'immediate';
		$scheduled_at   = isset($_POST['scheduled_at']) ? sanitize_text_field(wp_unslash($_POST['scheduled_at'])) : '';
		$repeat_interval = isset($_POST['repeat_interval']) ? sanitize_text_field(wp_unslash($_POST['repeat_interval'])) : '';
		$tz_offset      = isset($_POST['tz_offset']) ? intval($_POST['tz_offset']) : 0;

		if (! $name || ! $subject || ! $body || empty($product_ids)) {
			wp_send_json_error(array('message' => __('Please fill in all fields.', 'payaman_wishlist')), 400);
		}

		if ($send_type === 'scheduled' && ! $scheduled_at) {
			wp_send_json_error(array('message' => __('Please select a schedule date and time.', 'payaman_wishlist')), 400);
		}

		if ($send_type === 'scheduled' && $scheduled_at) {
			$scheduled_at = payaman_wishlist_browser_to_utc($scheduled_at, $tz_offset);
		}

		$data = array(
			'name'        => $name,
			'subject'     => $subject,
			'body'        => $body,
			'product_ids' => $product_ids,
			'send_type'   => $send_type,
		);

		if ($send_type === 'scheduled') {
			$data['scheduled_at']    = $scheduled_at;
			$data['repeat_interval'] = $repeat_interval;
		}

		$campaigns = new Payaman_Wishlist_Campaigns();
		$id = $campaigns->create($data);

		if (! $id) {
			wp_send_json_error(array('message' => __('Failed to save campaign.', 'payaman_wishlist')), 500);
		}

		wp_send_json_success(array('message' => __('Campaign saved successfully.', 'payaman_wishlist'), 'id' => $id));
	}

	public function ajax_get_campaign()
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
		$campaign = $campaigns->get($campaign_id);

		if (! $campaign) {
			wp_send_json_error(array('message' => __('Campaign not found.', 'payaman_wishlist')), 404);
		}

		$product_names = array();
		foreach ($campaign['product_ids'] as $pid) {
			$p = wc_get_product($pid);
			$product_names[] = $p ? $p->get_name() : '#' . $pid;
		}

		$campaign['product_names'] = $product_names;

		wp_send_json_success($campaign);
	}

	public function ajax_update_campaign()
	{
		check_ajax_referer('payaman_wishlist_promo_email', 'nonce');

		if (! current_user_can('manage_options')) {
			wp_send_json_error(array('message' => __('Insufficient permissions.', 'payaman_wishlist')), 403);
		}

		$campaign_id = isset($_POST['campaign_id']) ? absint(wp_unslash($_POST['campaign_id'])) : 0;
		$name        = isset($_POST['name']) ? sanitize_text_field(wp_unslash($_POST['name'])) : '';
		$subject     = isset($_POST['subject']) ? sanitize_text_field(wp_unslash($_POST['subject'])) : '';
		$body        = isset($_POST['body']) ? wp_kses_post(wp_unslash($_POST['body'])) : '';
		$product_ids = isset($_POST['product_ids']) ? (array) wp_unslash($_POST['product_ids']) : array();
		$send_type   = isset($_POST['send_type']) ? sanitize_text_field(wp_unslash($_POST['send_type'])) : 'immediate';
		$scheduled_at = isset($_POST['scheduled_at']) ? sanitize_text_field(wp_unslash($_POST['scheduled_at'])) : '';
		$repeat_interval = isset($_POST['repeat_interval']) ? sanitize_text_field(wp_unslash($_POST['repeat_interval'])) : '';
		$tz_offset   = isset($_POST['tz_offset']) ? intval($_POST['tz_offset']) : 0;

		if (! $campaign_id || ! $name || ! $subject || ! $body || empty($product_ids)) {
			wp_send_json_error(array('message' => __('Please fill in all fields.', 'payaman_wishlist')), 400);
		}

		if ($send_type === 'scheduled' && ! $scheduled_at) {
			wp_send_json_error(array('message' => __('Please select a schedule date and time.', 'payaman_wishlist')), 400);
		}

		if ($send_type === 'scheduled' && $scheduled_at) {
			$scheduled_at = payaman_wishlist_browser_to_utc($scheduled_at, $tz_offset);
		}

		$data = array(
			'name'        => $name,
			'subject'     => $subject,
			'body'        => $body,
			'product_ids' => $product_ids,
			'send_type'   => $send_type,
		);

		if ($send_type === 'scheduled') {
			$data['scheduled_at']    = $scheduled_at;
			$data['repeat_interval'] = $repeat_interval;
		} else {
			$data['scheduled_at']    = null;
			$data['repeat_interval'] = '';
		}

		$campaigns = new Payaman_Wishlist_Campaigns();
		$campaigns->update($campaign_id, $data);

		wp_send_json_success(array('message' => __('Campaign updated successfully.', 'payaman_wishlist')));
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

	public function ajax_get_campaigns()
	{
		check_ajax_referer('payaman_wishlist_promo_email', 'nonce');

		if (! current_user_can('manage_options')) {
			wp_send_json_error(array('message' => __('Insufficient permissions.', 'payaman_wishlist')), 403);
		}

		$campaigns_obj = new Payaman_Wishlist_Campaigns();
		$campaigns = $campaigns_obj->get_all();

		foreach ($campaigns as &$c) {
			$c['next_send_display'] = ($c['status'] === 'scheduled' && $c['scheduled_at'])
				? payaman_wishlist_utc_to_wp_timezone($c['scheduled_at'])
				: '&mdash;';
			$c['type_display'] = 'Immediate';
			if ($c['send_type'] === 'scheduled') {
				$c['type_display'] = $c['repeat_interval']
					? 'Repeat ' . ucfirst($c['repeat_interval'])
					: 'Scheduled';
			}
		}

		wp_send_json_success($campaigns);
	}

	public function ajax_pause_campaign()
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
		$campaigns->pause($campaign_id);

		wp_send_json_success(array('message' => __('Campaign paused.', 'payaman_wishlist')));
	}

	public function ajax_resume_campaign()
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
		$result = $campaigns->resume($campaign_id);

		if (! $result) {
			wp_send_json_error(array('message' => __('Cannot resume this campaign.', 'payaman_wishlist')), 400);
		}

		$message = __('Campaign resumed. It will be processed at the next scheduled time.', 'payaman_wishlist');

		wp_send_json_success(array('message' => $message));
	}

	public function ajax_preview_campaign()
	{
		check_ajax_referer('payaman_wishlist_promo_email', 'nonce');

		if (! current_user_can('manage_options')) {
			wp_send_json_error(array('message' => __('Insufficient permissions.', 'payaman_wishlist')), 403);
		}

		$subject     = isset($_POST['subject']) ? sanitize_text_field(wp_unslash($_POST['subject'])) : '';
		$body        = isset($_POST['body']) ? wp_kses_post(wp_unslash($_POST['body'])) : '';
		$product_ids = isset($_POST['product_ids']) ? (array) wp_unslash($_POST['product_ids']) : array();

		$sample_products = array();
		if (! empty($product_ids)) {
			foreach ($product_ids as $pid) {
				$p = wc_get_product(absint($pid));
				if ($p) {
					$sample_products[] = $p;
				}
				if (count($sample_products) >= 3) break;
			}
		}

		$products_list = '';
		$count = count($sample_products);
		foreach ($sample_products as $i => $p) {
			$products_list .= ($i + 1) . '. ' . $p->get_name() . ' - ' . get_permalink($p->get_id()) . "\n";
		}

		$current_user = wp_get_current_user();
		$replacements = array(
			'{user_name}'     => $current_user ? $current_user->display_name : 'John Doe',
			'{site_name}'     => get_bloginfo('name'),
			'{count}'         => $count ?: 3,
			'{products_list}' => trim($products_list ?: "1. Sample Product - " . home_url()),
		);

		$rendered = Payaman_Wishlist_Campaigns::render_html_email($subject, $body, $replacements);

		wp_send_json_success(array(
			'subject' => $rendered['subject'],
			'html'    => $rendered['html'],
		));
	}

	public function ajax_test_campaign()
	{
		check_ajax_referer('payaman_wishlist_promo_email', 'nonce');

		if (! current_user_can('manage_options')) {
			wp_send_json_error(array('message' => __('Insufficient permissions.', 'payaman_wishlist')), 403);
		}

		$subject     = isset($_POST['subject']) ? sanitize_text_field(wp_unslash($_POST['subject'])) : '';
		$body        = isset($_POST['body']) ? wp_kses_post(wp_unslash($_POST['body'])) : '';
		$product_ids = isset($_POST['product_ids']) ? (array) wp_unslash($_POST['product_ids']) : array();

		if (! $subject || ! $body || empty($product_ids)) {
			wp_send_json_error(array('message' => __('Please fill in all fields first.', 'payaman_wishlist')), 400);
		}

		$sample_products = array();
		if (! empty($product_ids)) {
			foreach ($product_ids as $pid) {
				$p = wc_get_product(absint($pid));
				if ($p) {
					$sample_products[] = $p;
				}
				if (count($sample_products) >= 3) break;
			}
		}

		$products_list = '';
		$count = count($sample_products);
		foreach ($sample_products as $i => $p) {
			$products_list .= ($i + 1) . '. ' . $p->get_name() . ' - ' . get_permalink($p->get_id()) . "\n";
		}

		$current_user = wp_get_current_user();
		$replacements = array(
			'{user_name}'     => $current_user ? $current_user->display_name : 'John Doe',
			'{site_name}'     => get_bloginfo('name'),
			'{count}'         => $count ?: 3,
			'{products_list}' => trim($products_list ?: "1. Sample Product - " . home_url()),
		);

		$mailed = Payaman_Wishlist_Campaigns::send_email(
			$current_user->user_email,
			$subject,
			$body,
			$replacements
		);

		if ($mailed) {
			wp_send_json_success(array('message' => __('Test email sent to your email address.', 'payaman_wishlist')));
		} else {
			wp_send_json_error(array('message' => __('Failed to send test email.', 'payaman_wishlist')), 500);
		}
	}

	public function ajax_process_due()
	{
		check_ajax_referer('payaman_wishlist_promo_email', 'nonce');

		if (! current_user_can('manage_options')) {
			wp_send_json_error(array('message' => __('Insufficient permissions.', 'payaman_wishlist')), 403);
		}

		$campaigns = new Payaman_Wishlist_Campaigns();
		$results = $campaigns->process_due();

		$total = count($results);
		$message = sprintf(__('Processed %d scheduled campaign(s).', 'payaman_wishlist'), $total);

		wp_send_json_success(array('message' => $message, 'results' => $results));
	}
	}

	new Payaman_Wishlist_AJAX();
}
