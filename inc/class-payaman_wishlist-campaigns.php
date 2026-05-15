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

			$send_type = ! empty($data['send_type']) ? $data['send_type'] : 'immediate';
			$scheduled_at = ! empty($data['scheduled_at']) ? $data['scheduled_at'] : null;
			$repeat_interval = ! empty($data['repeat_interval']) ? $data['repeat_interval'] : '';
			$status = ($send_type === 'scheduled') ? 'scheduled' : 'draft';

			$wpdb->insert(
				$this->table,
				array(
					'name'            => sanitize_text_field($data['name']),
					'subject'         => sanitize_text_field($data['subject']),
					'body'            => wp_kses_post($data['body']),
					'product_ids'     => implode(',', $product_ids),
					'status'          => $status,
					'send_type'       => $send_type,
					'scheduled_at'    => $scheduled_at,
					'repeat_interval' => $repeat_interval,
					'total_targeted'  => 0,
					'total_sent'      => 0,
					'created_by'      => get_current_user_id(),
					'created_at'      => current_time('mysql'),
				),
				array('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%d', '%d', '%s')
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

		public function update($id, $data)
		{
			global $wpdb;

			$fields = array();
			$formats = array();

			$map = array(
				'name'            => '%s',
				'subject'         => '%s',
				'body'            => '%s',
				'send_type'       => '%s',
				'scheduled_at'    => '%s',
				'repeat_interval' => '%s',
			);

			if (isset($data['product_ids']) && is_array($data['product_ids'])) {
				$data['product_ids'] = implode(',', array_values(array_unique(array_filter(array_map('absint', $data['product_ids'])))));
			}

			foreach ($map as $key => $fmt) {
				if (array_key_exists($key, $data)) {
					if ($key === 'body') {
						$fields[$key] = wp_kses_post($data[$key]);
					} elseif ($key === 'name' || $key === 'subject') {
						$fields[$key] = sanitize_text_field($data[$key]);
					} elseif ($key === 'scheduled_at') {
						$fields[$key] = $data[$key] ?: null;
					} else {
						$fields[$key] = sanitize_text_field($data[$key]);
					}
					$formats[] = $fmt;
				}
			}

			if (empty($fields)) {
				return false;
			}

			if (isset($fields['send_type'])) {
				$fields['status'] = ($fields['send_type'] === 'scheduled') ? 'scheduled' : 'draft';
				$formats[] = '%s';
			}

			return $wpdb->update($this->table, $fields, array('id' => $id), $formats, array('%d'));
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

		public function get_due()
		{
			global $wpdb;
			$rows = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT * FROM {$this->table} WHERE status = 'scheduled' AND scheduled_at IS NOT NULL AND scheduled_at <= %s",
					gmdate('Y-m-d H:i:s')
				),
				ARRAY_A
			);
			foreach ($rows as &$row) {
				$row['product_ids'] = array_values(array_unique(array_filter(array_map('absint', explode(',', $row['product_ids'])))));
			}
			return $rows;
		}

		public static function get_html_template()
		{
			return '<!DOCTYPE html>
<html>
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0"></head>
<body style="margin:0;padding:0;background-color:#f4f4f5;font-family:-apple-system,BlinkMacSystemFont,\'Segoe UI\',Roboto,Arial,sans-serif;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f4f4f5;">
<tr><td align="center" style="padding:30px 15px;">
<table role="presentation" width="600" cellpadding="0" cellspacing="0" style="max-width:600px;">
<tr><td align="center" style="padding:0 0 20px 0;font-size:22px;font-weight:bold;color:#1d2327;">{site_name}</td></tr>
<tr><td style="background:#fff;padding:30px;border-radius:6px;box-shadow:0 1px 3px rgba(0,0,0,0.08);font-size:15px;line-height:1.6;color:#333;">
{email_body}
</td></tr>
<tr><td align="center" style="padding:20px 0 0 0;font-size:12px;color:#999;">
<p style="margin:0;">You received this because you have wishlisted items on {site_name}.</p>
<p style="margin:5px 0 0 0;">&copy; {site_name}</p>
</td></tr>
</table>
</td></tr>
</table>
</body>
</html>';
		}

		public static function render_html_email($subject, $body, $replacements = array())
		{
			$body_html = nl2br($body);

			$products_list = isset($replacements['{products_list}']) ? $replacements['{products_list}'] : '';

			if ($products_list && strpos($body_html, '{products_list}') !== false) {
				$lines = explode("\n", trim($products_list));
				$html_list = '<ol style="margin:10px 0;padding:0 0 0 20px;">';
				foreach ($lines as $line) {
					$line = trim($line);
					if (! $line) continue;
					if (preg_match('/^\d+\.\s(.+)/', $line, $m)) {
						$html_list .= '<li style="margin-bottom:4px;">' . esc_html($m[1]) . '</li>';
					} else {
						$html_list .= '<li style="margin-bottom:4px;">' . esc_html($line) . '</li>';
					}
				}
				$html_list .= '</ol>';
				$replacements['{products_list}'] = $html_list;
			}

			$body_html = str_replace(array_keys($replacements), array_values($replacements), $body_html);

			$template = self::get_html_template();
			$template_replacements = array(
				'{site_name}'  => get_bloginfo('name'),
				'{email_body}' => $body_html,
			);
			$full_html = str_replace(array_keys($template_replacements), array_values($template_replacements), $template);

			$subject_rendered = str_replace(array_keys($replacements), array_values($replacements), $subject);

			return array(
				'subject' => $subject_rendered,
				'html'    => $full_html,
			);
		}

		public static function send_email($to, $subject, $body, $replacements = array())
		{
			$rendered = self::render_html_email($subject, $body, $replacements);
			$headers = array('Content-Type: text/html; charset=UTF-8');
			return wp_mail($to, $rendered['subject'], $rendered['html'], $headers);
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
					'{user_name}'     => $user->display_name,
					'{site_name}'     => $site_name,
					'{count}'         => $count,
					'{products_list}' => trim($products_list),
				);

				$mailed = self::send_email($user->user_email, $campaign['subject'], $campaign['body'], $replacements);
				if ($mailed) {
					$sent_count++;
				}
			}

			$now = current_time('mysql');

			if (! empty($campaign['repeat_interval'])) {
				$next = $this->calculate_next_schedule($campaign['scheduled_at'], $campaign['repeat_interval']);
				global $wpdb;
				$wpdb->update(
					$this->table,
					array(
						'scheduled_at'    => $next,
						'total_targeted'  => count($all_user_ids),
						'total_sent'      => $sent_count,
						'sent_at'         => $now,
					),
					array('id' => $id),
					array('%s', '%d', '%d', '%s'),
					array('%d')
				);
			} else {
				global $wpdb;
				$wpdb->update(
					$this->table,
					array(
						'status'          => 'sent',
						'total_targeted'  => count($all_user_ids),
						'total_sent'      => $sent_count,
						'sent_at'         => $now,
					),
					array('id' => $id),
					array('%s', '%d', '%d', '%s'),
					array('%d')
				);
			}

			return array(
				'targeted' => count($all_user_ids),
				'sent'     => $sent_count,
			);
		}

		private function calculate_next_schedule($current_scheduled_at, $interval)
		{
			if (! $current_scheduled_at) {
				return null;
			}

			$time = strtotime($current_scheduled_at . ' UTC');

			switch ($interval) {
				case 'daily':
					$time = strtotime('+1 day', $time);
					break;
				case 'weekly':
					$time = strtotime('+1 week', $time);
					break;
				case 'monthly':
					$time = strtotime('+1 month', $time);
					break;
				default:
					return null;
			}

			return gmdate('Y-m-d H:i:s', $time);
		}

		public function pause($id)
		{
			global $wpdb;
			return $wpdb->update(
				$this->table,
				array('status' => 'paused'),
				array('id' => $id),
				array('%s'),
				array('%d')
			);
		}

		public function resume($id)
		{
			global $wpdb;

			$campaign = $this->get($id);
			if (! $campaign || $campaign['send_type'] !== 'scheduled') {
				return false;
			}

			$now_utc = gmdate('Y-m-d H:i:s');
			$scheduled_at = $campaign['scheduled_at'];

			if ($scheduled_at && $scheduled_at <= $now_utc) {
				$scheduled_at = gmdate('Y-m-d H:i:s', strtotime('+1 minute'));
			}

			return $wpdb->update(
				$this->table,
				array(
					'status'       => 'scheduled',
					'scheduled_at' => $scheduled_at,
				),
				array('id' => $id),
				array('%s', '%s'),
				array('%d')
			);
		}

		public function process_due()
		{
			$due = $this->get_due();
			$results = array();

			foreach ($due as $campaign) {
				$result = $this->send($campaign['id']);
				$results[] = array(
					'id'     => $campaign['id'],
					'name'   => $campaign['name'],
					'result' => is_wp_error($result) ? $result->get_error_message() : $result,
				);
			}

			return $results;
		}
	}

	function payaman_wishlist_normalize_datetime($datetime)
	{
		if (empty($datetime)) {
			return null;
		}

		$datetime = str_replace('T', ' ', $datetime);

		if (preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}$/', $datetime)) {
			$datetime .= ':00';
		}

		return $datetime;
	}

	function payaman_wishlist_browser_to_utc($datetime, $tz_offset_minutes)
	{
		if (empty($datetime)) {
			return null;
		}

		$datetime = str_replace('T', ' ', $datetime);
		if (preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}$/', $datetime)) {
			$datetime .= ':00';
		}

		$ts = strtotime($datetime . ' UTC');
		if ($ts === false) {
			return null;
		}

		$utc_ts = $ts + ($tz_offset_minutes * 60);
		return gmdate('Y-m-d H:i:s', $utc_ts);
	}

	function payaman_wishlist_utc_to_wp_timezone($datetime)
	{
		if (empty($datetime)) {
			return '';
		}

		$ts = strtotime($datetime . ' UTC');
		if ($ts === false) {
			return $datetime;
		}

		$wp_offset = (float) get_option('gmt_offset', 0);
		$wp_ts = $ts + ($wp_offset * 3600);
		return gmdate('Y-m-d H:i:s', $wp_ts);
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
