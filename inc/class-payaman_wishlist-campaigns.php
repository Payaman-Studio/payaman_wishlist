<?php

if (! defined('ABSPATH')) {
	exit;
}

if (! class_exists('Payaman_Wishlist_Campaigns')) {

	class Payaman_Wishlist_Campaigns
	{
		private $table;

		public function __construct()
		{
			$this->table = payaman_wishlist_get_table_name('campaigns');
		}

		public function create($data)
		{
			global $wpdb;

			$product_ids = array();
			if (! empty($data['product_ids']) && is_array($data['product_ids'])) {
				$product_ids = array_values(array_unique(array_filter(array_map('absint', $data['product_ids']))));
			}

			$wpdb->insert(
				$this->table,
				array(
					'name'           => sanitize_text_field($data['name']),
					'subject'        => sanitize_text_field($data['subject']),
					'body'           => wp_kses_post($data['body']),
					'product_ids'    => implode(',', $product_ids),
					'status'         => 'draft',
					'total_targeted' => 0,
					'total_sent'     => 0,
					'created_by'     => get_current_user_id(),
					'created_at'     => current_time('mysql'),
				),
				array('%s', '%s', '%s', '%s', '%s', '%d', '%d', '%d', '%s')
			);

			return $wpdb->insert_id;
		}

		public function get($id)
		{
			global $wpdb;
			$row = $wpdb->get_row(
				$wpdb->prepare("SELECT * FROM {$this->table} WHERE id = %d", $id),
				ARRAY_A
			);
			if ($row) {
				$row['product_ids'] = array_values(array_unique(array_filter(array_map('absint', explode(',', $row['product_ids'])))));
			}
			return $row;
		}

		public function get_all()
		{
			global $wpdb;
			$rows = $wpdb->get_results("SELECT * FROM {$this->table} ORDER BY created_at DESC", ARRAY_A);
			foreach ($rows as &$row) {
				$row['product_ids'] = array_values(array_unique(array_filter(array_map('absint', explode(',', $row['product_ids'])))));
			}
			return $rows;
		}

		public function delete($id)
		{
			global $wpdb;
			return $wpdb->delete($this->table, array('id' => $id), array('%d'));
		}

		public function send($id)
		{
			$campaign = $this->get($id);
			if (! $campaign) {
				return new WP_Error('not_found', __('Campaign not found.', 'payaman_wishlist'));
			}

			$product_ids = $campaign['product_ids'];
			if (empty($product_ids)) {
				return new WP_Error('no_products', __('No products in this campaign.', 'payaman_wishlist'));
			}

			$all_user_ids = array();
			foreach ($product_ids as $pid) {
				$users = payaman_wishlist_get_users_by_product($pid);
				$all_user_ids = array_merge($all_user_ids, $users);
			}
			$all_user_ids = array_values(array_unique(array_filter(array_map('absint', $all_user_ids))));

			if (empty($all_user_ids)) {
				return new WP_Error('no_users', __('No users have these products in their wishlist.', 'payaman_wishlist'));
			}

			$site_name = get_bloginfo('name');
			$sent_count = 0;

			foreach ($all_user_ids as $user_id) {
				$user = get_userdata($user_id);
				if (! $user) {
					continue;
				}

				$matching = payaman_wishlist_get_user_matching_products($user_id, $product_ids);
				if (empty($matching)) {
					continue;
				}

				$products_list = '';
				$count = count($matching);
				foreach ($matching as $i => $pid) {
					$p = wc_get_product($pid);
					if (! $p) {
						continue;
					}
					$products_list .= ($i + 1) . '. ' . $p->get_name() . ' - ' . get_permalink($pid) . "\n";
				}

				$replacements = array(
					'{user_name}'    => $user->display_name,
					'{site_name}'    => $site_name,
					'{count}'        => $count,
					'{products_list}' => trim($products_list),
				);

				$email_subject = str_replace(array_keys($replacements), array_values($replacements), $campaign['subject']);
				$email_body    = str_replace(array_keys($replacements), array_values($replacements), $campaign['body']);

				$mailed = wp_mail($user->user_email, $email_subject, $email_body);
				if ($mailed) {
					$sent_count++;
				}
			}

			global $wpdb;
			$wpdb->update(
				$this->table,
				array(
					'status'         => 'sent',
					'total_targeted' => count($all_user_ids),
					'total_sent'     => $sent_count,
					'sent_at'        => current_time('mysql'),
				),
				array('id' => $id),
				array('%s', '%d', '%d', '%s'),
				array('%d')
			);

			return array(
				'targeted' => count($all_user_ids),
				'sent'     => $sent_count,
			);
		}
	}

	function payaman_wishlist_get_user_matching_products($user_id, $product_ids)
	{
		global $wpdb;
		$items_table = payaman_wishlist_get_table_name('items');
		$collections_table = payaman_wishlist_get_table_name('collections');

		if (empty($product_ids)) {
			return array();
		}

		$placeholders = implode(',', array_fill(0, count($product_ids), '%d'));
		$query = $wpdb->prepare(
			"SELECT DISTINCT i.product_id FROM {$items_table} i
			 INNER JOIN {$collections_table} c ON c.id = i.collection_id
			 WHERE c.user_id = %d AND i.product_id IN ({$placeholders})",
			array_merge(array($user_id), $product_ids)
		);

		$results = $wpdb->get_col($query);
		return array_values(array_unique(array_map('absint', $results)));
	}

	new Payaman_Wishlist_Campaigns();
}
