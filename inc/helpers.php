<?php

/**
 *  Update Gridly Wishlist Status
 */
add_action('wp_ajax_update_gridlywishlist', 'ajax_update_gridlywishlist_callback');
add_action('wp_ajax_nopriv_update_gridlywishlist', 'ajax_update_gridlywishlist_callback');
add_action('wp_ajax_gridlywishlist_bulk_remove', 'ajax_gridlywishlist_bulk_remove_callback');
add_action('wp_ajax_nopriv_gridlywishlist_bulk_remove', 'ajax_gridlywishlist_bulk_remove_callback');
add_action('wp_ajax_gridlywishlist_collection_create', 'ajax_gridlywishlist_collection_create');
add_action('wp_ajax_gridlywishlist_collection_update', 'ajax_gridlywishlist_collection_update');
add_action('wp_ajax_gridlywishlist_collection_delete', 'ajax_gridlywishlist_collection_delete');
add_action('wp_ajax_gridlywishlist_collection_move_items', 'ajax_gridlywishlist_collection_move_items');
add_action('wp_login', 'gridlywishlist_migrate_guest_to_user', 10, 2);

/**
 * Migrate guest wishlist cookie items to the logged-in user's default collection.
 * Fires on wp_login hook.
 *
 * @param string  $user_login Username.
 * @param WP_User $user       WP_User object.
 */
function gridlywishlist_migrate_guest_to_user($user_login, $user)
{
	if (empty($_COOKIE['gridlywishlist_product'])) {
		return;
	}

	$cookie_data = json_decode(wp_unslash($_COOKIE['gridlywishlist_product']), true);

	if (! is_array($cookie_data) || empty($cookie_data)) {
		return;
	}

	$user_id     = absint($user->ID);
	$product_ids = array_values(array_unique(array_filter(array_map('absint', $cookie_data))));

	if (! $user_id || empty($product_ids)) {
		return;
	}

	foreach ($product_ids as $product_id) {
		if ('product' !== get_post_type($product_id)) {
			continue;
		}

		// Insert ke collection DB (tabel baru)
		gridlywishlist_update_user_wishlists($user_id, $product_id, 'insert', '');

		// Sinkronkan juga ke post meta (sistem lama)
		$wishlists = gridlywishlist_get_wishlists($product_id);
		$user_key  = (string) $user_id;
		if (! in_array($user_key, $wishlists, true)) {
			$wishlists[] = $user_key;
			gridlywishlist_store_wishlists($product_id, $wishlists);
		}
	}

	// Instruksikan JS untuk menghapus cookie setelah redirect selesai
	add_action('wp_footer', 'gridlywishlist_clear_guest_cookie_script');
	add_action('login_footer', 'gridlywishlist_clear_guest_cookie_script');
}

/**
 * Output script to clear the guest wishlist cookie after migration.
 */
function gridlywishlist_clear_guest_cookie_script()
{
	echo '<script>if(typeof Cookies!=="undefined"){Cookies.remove("gridlywishlist_product",{path:"/"});}else{document.cookie="gridlywishlist_product=;expires=Thu, 01 Jan 1970 00:00:00 GMT;path=/"}</script>' . "\n";
}

function gridlywishlist_get_table_name($type)
{
	switch ($type) {
		case 'collections':
			return GRIDLYWISHLIST_TABLE_COLLECTIONS;
		case 'items':
			return GRIDLYWISHLIST_TABLE_ITEMS;
		default:
			return '';
	}
}

function gridlywishlist_maybe_install_tables()
{
	global $wpdb;
	require_once ABSPATH . 'wp-admin/includes/upgrade.php';

	$charset_collate = $wpdb->get_charset_collate();
	$collections_table = gridlywishlist_get_table_name('collections');
	$items_table = gridlywishlist_get_table_name('items');

	$collections_sql = "CREATE TABLE {$collections_table} (
		id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
		user_id BIGINT(20) UNSIGNED NOT NULL,
		slug VARCHAR(191) NOT NULL,
		name VARCHAR(255) NOT NULL,
		is_public TINYINT(1) NOT NULL DEFAULT 0,
		is_default TINYINT(1) NOT NULL DEFAULT 0,
		created_at DATETIME NOT NULL,
		updated_at DATETIME NOT NULL,
		PRIMARY KEY  (id),
		KEY user_id (user_id),
		KEY slug (slug)
	) {$charset_collate};";

	$items_sql = "CREATE TABLE {$items_table} (
		id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
		collection_id BIGINT(20) UNSIGNED NOT NULL,
		product_id BIGINT(20) UNSIGNED NOT NULL,
		variation_id BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
		added_at DATETIME NOT NULL,
		PRIMARY KEY  (id),
		KEY collection_id (collection_id),
		KEY product_id (product_id)
	) {$charset_collate};";

	dbDelta($collections_sql);
	dbDelta($items_sql);

	$installed_version = get_option('gridlywishlist_db_version');
	if ($installed_version !== GRIDLYWISHLIST_DB_VERSION) {
		update_option('gridlywishlist_db_version', GRIDLYWISHLIST_DB_VERSION);
	}
}

function gridlywishlist_maybe_run_migrations()
{
	global $wpdb;
	$items_table = gridlywishlist_get_table_name('items');

	// v2.1.0 — Add variation_id column for variable product support.
	$has_column = $wpdb->get_results(
		$wpdb->prepare(
			'SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s AND COLUMN_NAME = %s',
			DB_NAME,
			$items_table,
			'variation_id'
		)
	);
	if (empty($has_column)) {
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$wpdb->query("ALTER TABLE {$items_table} ADD COLUMN variation_id BIGINT(20) UNSIGNED NOT NULL DEFAULT 0 AFTER product_id");
	}
}
function ajax_update_gridlywishlist_callback()
{
	check_ajax_referer('gridlywishlist_toggle', 'nonce');

	$product_id   = isset($_POST['product_id']) ? absint(wp_unslash($_POST['product_id'])) : 0;
	$fav_action   = isset($_POST['fav_action']) ? sanitize_key(wp_unslash($_POST['fav_action'])) : '';
	$collection_id = isset($_POST['collection_id']) ? sanitize_text_field(wp_unslash($_POST['collection_id'])) : '';
	$variation_id  = isset($_POST['variation_id']) ? absint(wp_unslash($_POST['variation_id'])) : 0;

	if (! $product_id || ! in_array($fav_action, array('insert', 'delete'), true)) {
		wp_send_json_error(array('message' => __('Invalid request data.', 'gridlywishlist')), 400);
	}

	if ('product' !== get_post_type($product_id)) {
		wp_send_json_error(array('message' => __('Invalid product.', 'gridlywishlist')), 400);
	}

	if ('yes' === gridlywishlist_setting('required_login', 'no') && ! is_user_logged_in()) {
		wp_send_json_error(array('message' => gridlywishlist_setting('required_login_message', __('You must be logged in.', 'gridlywishlist'))), 403);
	}

	$current_user_id = get_current_user_id();
	$fav_user = is_user_logged_in() ? (string) $current_user_id : (string) gridlywishlist_client_ip();
	$collection_record = $current_user_id ? gridlywishlist_validate_collection($current_user_id, $collection_id) : null;
	$collection_id = $collection_record ? $collection_record['id'] : '';
	$wishlists = gridlywishlist_get_wishlists($product_id);
	$user_still_has_product = false;
	if ($current_user_id) {
		$user_still_has_product = gridlywishlist_update_user_wishlists($current_user_id, $product_id, $fav_action, $collection_id, $variation_id);
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

	if (gridlywishlist_store_wishlists($product_id, $wishlists) === false) {
		wp_send_json_error(array('message' => __('Failed to update wishlists.', 'gridlywishlist')), 500);
	}

	$count = count($wishlists);
	$collections_summary = $current_user_id ? gridlywishlist_prepare_collections_response($current_user_id) : array();

	$response = array(
		'count' => 'yes' === gridlywishlist_setting('gridlywishlist_count', 'no') ? $count : '',
		'state' => in_array($fav_user, $wishlists, true) ? 'on' : 'off',
		'collection_id' => $collection_id,
		'collection_state' => ('insert' === $fav_action) ? 'on' : 'off',
		'collections' => $collections_summary,
	);

	wp_send_json_success($response);
}

/**
 * Handle bulk remove wishlists.
 */
function ajax_gridlywishlist_bulk_remove_callback()
{
	check_ajax_referer('gridlywishlist_toggle', 'nonce');

	$product_ids = isset($_POST['product_ids']) ? (array) wp_unslash($_POST['product_ids']) : array();
	$product_ids = array_values(array_unique(array_filter(array_map('absint', $product_ids))));
	$collection_id = isset($_POST['collection_id']) ? sanitize_text_field(wp_unslash($_POST['collection_id'])) : '';

	if (empty($product_ids)) {
		wp_send_json_error(array('message' => __('Invalid selection.', 'gridlywishlist')), 400);
	}

	if ('yes' === gridlywishlist_setting('required_login', 'no') && ! is_user_logged_in()) {
		wp_send_json_error(array('message' => gridlywishlist_setting('required_login_message', __('You must be logged in.', 'gridlywishlist'))), 403);
	}

	$current_user_id = get_current_user_id();
	$fav_user = is_user_logged_in() ? (string) $current_user_id : (string) gridlywishlist_client_ip();
	$collection_record = $current_user_id ? gridlywishlist_validate_collection($current_user_id, $collection_id, true) : null;
	$collection_id = $collection_record ? $collection_record['id'] : '';
	$removed  = array();

	foreach ($product_ids as $product_id) {
		if ('product' !== get_post_type($product_id)) {
			continue;
		}

		$wishlists = gridlywishlist_get_wishlists($product_id);
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

		if (gridlywishlist_store_wishlists($product_id, $wishlists) === false) {
			continue;
		}

		$removed[] = $product_id;

		if ($current_user_id) {
			gridlywishlist_update_user_wishlists($current_user_id, $product_id, 'delete', $collection_id);
		}
	}

	wp_send_json_success(array('removed' => $removed));
}

function ajax_gridlywishlist_collection_create()
{
	check_ajax_referer('gridlywishlist_toggle', 'nonce');

	if (! is_user_logged_in()) {
		wp_send_json_error(array('message' => __('You must be logged in to manage collections.', 'gridlywishlist')), 403);
	}

	$user_id   = get_current_user_id();
	$name      = isset($_POST['name']) ? sanitize_text_field(wp_unslash($_POST['name'])) : '';
	$is_public = ! empty($_POST['is_public']) && filter_var($_POST['is_public'], FILTER_VALIDATE_BOOLEAN);

	$result = gridlywishlist_create_collection($user_id, $name, $is_public);

	if (is_wp_error($result)) {
		wp_send_json_error(array('message' => $result->get_error_message()), 400);
	}

	wp_send_json_success(array(
		'collection'  => $result,
		'collections' => gridlywishlist_prepare_collections_response($user_id),
	));
}

function ajax_gridlywishlist_collection_update()
{
	check_ajax_referer('gridlywishlist_toggle', 'nonce');

	if (! is_user_logged_in()) {
		wp_send_json_error(array('message' => __('You must be logged in to manage collections.', 'gridlywishlist')), 403);
	}

	$user_id = get_current_user_id();
	$collection_id = isset($_POST['collection_id']) ? sanitize_text_field(wp_unslash($_POST['collection_id'])) : '';

	$args = array();
	if (isset($_POST['name'])) {
		$args['name'] = sanitize_text_field(wp_unslash($_POST['name']));
	}
	if (isset($_POST['is_public'])) {
		$args['is_public'] = filter_var($_POST['is_public'], FILTER_VALIDATE_BOOLEAN);
	}

	$result = gridlywishlist_update_collection($user_id, $collection_id, $args);
	if (is_wp_error($result)) {
		wp_send_json_error(array('message' => $result->get_error_message()), 400);
	}

	wp_send_json_success(array(
		'collection'  => $result,
		'collections' => gridlywishlist_prepare_collections_response($user_id),
	));
}

function ajax_gridlywishlist_collection_delete()
{
	check_ajax_referer('gridlywishlist_toggle', 'nonce');

	if (! is_user_logged_in()) {
		wp_send_json_error(array('message' => __('You must be logged in to manage collections.', 'gridlywishlist')), 403);
	}

	$user_id         = get_current_user_id();
	$collection_id   = isset($_POST['collection_id']) ? sanitize_text_field(wp_unslash($_POST['collection_id'])) : '';
	$target_id       = isset($_POST['target_collection_id']) ? sanitize_text_field(wp_unslash($_POST['target_collection_id'])) : '';

	$result = gridlywishlist_delete_collection($user_id, $collection_id, $target_id);
	if (is_wp_error($result)) {
		wp_send_json_error(array('message' => $result->get_error_message()), 400);
	}

	wp_send_json_success(array(
		'result'      => $result,
		'collections' => gridlywishlist_prepare_collections_response($user_id),
	));
}

function ajax_gridlywishlist_collection_move_items()
{
	check_ajax_referer('gridlywishlist_toggle', 'nonce');

	if (! is_user_logged_in()) {
		wp_send_json_error(array('message' => __('You must be logged in to manage collections.', 'gridlywishlist')), 403);
	}

	$user_id        = get_current_user_id();
	$product_ids    = isset($_POST['product_ids']) ? (array) wp_unslash($_POST['product_ids']) : array();
	$source_id      = isset($_POST['source_collection_id']) ? sanitize_text_field(wp_unslash($_POST['source_collection_id'])) : '';
	$target_id      = isset($_POST['target_collection_id']) ? sanitize_text_field(wp_unslash($_POST['target_collection_id'])) : '';

	$product_ids = array_values(array_unique(array_filter(array_map('absint', $product_ids))));

	if (empty($product_ids)) {
		wp_send_json_error(array('message' => __('No products selected.', 'gridlywishlist')), 400);
	}

	$result = gridlywishlist_move_items_between_collections($user_id, $product_ids, $target_id, $source_id);
	if (is_wp_error($result)) {
		wp_send_json_error(array('message' => $result->get_error_message()), 400);
	}

	wp_send_json_success(array(
		'collection'  => $result,
		'collections' => gridlywishlist_prepare_collections_response($user_id),
	));
}

/**
 * Get client IP Address
 */
function gridlywishlist_client_ip()
{
	$ipaddress = '';
	if (getenv('HTTP_CLIENT_IP')) {
		$ipaddress = getenv('HTTP_CLIENT_IP');
	} elseif (getenv('HTTP_X_FORWARDED_FOR')) {
		$ipaddress = getenv('HTTP_X_FORWARDED_FOR');
	} elseif (getenv('HTTP_X_FORWARDED')) {
		$ipaddress = getenv('HTTP_X_FORWARDED');
	} elseif (getenv('HTTP_FORWARDED_FOR')) {
		$ipaddress = getenv('HTTP_FORWARDED_FOR');
	} elseif (getenv('HTTP_FORWARDED')) {
		$ipaddress = getenv('HTTP_FORWARDED');
	} elseif (getenv('REMOTE_ADDR')) {
		$ipaddress = getenv('REMOTE_ADDR');
	} else {
		$ipaddress = 'UNKNOWN';
	}
	return $ipaddress;
}

/**
 * get gridlywishlist by product_id
 *
 * @param int $product_id ID Product
 *
 * @version 1.0.0
 */
function get_gridlywishlist($product_id)
{
	$wishlists = gridlywishlist_get_wishlists($product_id);
	return $wishlists ? count($wishlists) : '';
}

/**
 * Create default page to display list wishlist product
 *
 * @return [type] [description]
 */
function gridlywishlist_create_page()
{
	$gridlywishlist_list_page = array(
		'post_title'   => 'Wishlist',
		'post_type'    => 'page',
		'post_content' => '[gridlywishlist_list]',
		'post_status'  => 'publish',
		'post_author'  => 1,
	);
	wp_insert_post($gridlywishlist_list_page);
}

/**
 * Setup gridlywishlist default setting
 */
function gridlywishlist_default_setting()
{
	$default_settings = array(
		'enabled'                       => 'yes',
		'gridlywishlist_count'                    => 'no',
		'required_login'                => 'no',
		'display_position_button'       => 'after_add_to_cart',
		'type_active'                   => 'text',
		'enable_add_success_message'    => 'yes',
		'enable_remove_success_message' => 'yes',
		'display_on'                    => array(
			'single_product',
			'loop_product',
		),
		'button'                        => array(
			'text' => array(
				'val_on'  => 'Remove from Wishlist',
				'val_off' => '♡ Add to Wishlist',
			),
			'image' => array(
				'val_on'  => '',
				'val_off' => '',
			),
		),
		'messages'                      => array(
			'add_success_message'    => 'Success add to Wishlist',
			'remove_success_message' => 'Success remove from Wishlist',
			'required_login_message' => 'Please login to your account',
		),
	);
	update_option('gridlywishlist_settings', $default_settings);
	gridlywishlist_get_settings(true);
}

/**
 * Retrieve a public collection by its slug.
 * Returns array with collection data and product_ids, or null if not found / not public.
 *
 * @param string $slug Collection slug.
 * @return array|null
 */
function gridlywishlist_get_public_collection($slug)
{
	global $wpdb;
	$collections_table = gridlywishlist_get_table_name('collections');
	$items_table       = gridlywishlist_get_table_name('items');

	$slug = sanitize_text_field($slug);
	if (! $slug) {
		return null;
	}

	$row = $wpdb->get_row(
		$wpdb->prepare(
			"SELECT * FROM {$collections_table} WHERE slug = %s AND is_public = 1 LIMIT 1",
			$slug
		),
		ARRAY_A
	);

	if (! $row) {
		return null;
	}

	$product_ids = $wpdb->get_col(
		$wpdb->prepare(
			"SELECT product_id FROM {$items_table} WHERE collection_id = %d",
			(int) $row['id']
		)
	);

	return array(
		'id'          => $row['slug'],
		'name'        => $row['name'],
		'user_id'     => (int) $row['user_id'],
		'product_ids' => array_values(array_unique(array_map('absint', (array) $product_ids))),
	);
}

/**
 * Retrieve the wishlist list page URL.
 *
 * @return string
 */
function gridlywishlist_get_wishlist_page_url()
{
	$page = get_page_by_path('wishlist-list');
	if (! $page) {
		$page = get_page_by_title('Wishlist');
	}

	if ($page) {
		$url = get_permalink($page);
		if ($url) {
			return $url;
		}
	}

	return site_url('wishlist-list');
}

/**
 * Retrieve gridlywishlist settings with small in-request cache.
 *
 * @param bool $refresh Whether to refresh cache.
 * @return array
 */
function gridlywishlist_get_settings($refresh = false)
{
	static $settings = null;

	if ($refresh || ! is_array($settings)) {
		$settings = (array) get_option('gridlywishlist_settings', array());
	}

	return $settings;
}

/**
 * Get gridlywishlist setting drom db option
 *
 * @param  string $values option key to display
 * @return string
 */
function gridlywishlist_setting($values, $default = '')
{
	$gridlywishlist_opt = gridlywishlist_get_settings();

	switch ($values) {
		case 'val_on':
			return isset($gridlywishlist_opt['button']['text']['val_on']) ? $gridlywishlist_opt['button']['text']['val_on'] : $default;
		case 'val_off':
			return isset($gridlywishlist_opt['button']['text']['val_off']) ? $gridlywishlist_opt['button']['text']['val_off'] : $default;
		case 'image_val_on':
			return isset($gridlywishlist_opt['button']['image']['val_on']) ? $gridlywishlist_opt['button']['image']['val_on'] : $default;
		case 'image_val_off':
			return isset($gridlywishlist_opt['button']['image']['val_off']) ? $gridlywishlist_opt['button']['image']['val_off'] : $default;
		case 'add_success':
			return isset($gridlywishlist_opt['messages']['add_success_message']) ? $gridlywishlist_opt['messages']['add_success_message'] : $default;
		case 'remove_success':
			return isset($gridlywishlist_opt['messages']['remove_success_message']) ? $gridlywishlist_opt['messages']['remove_success_message'] : $default;
		case 'required_login_message':
			return isset($gridlywishlist_opt['messages']['required_login_message']) ? $gridlywishlist_opt['messages']['required_login_message'] : $default;
		default:
			return isset($gridlywishlist_opt[$values]) ? $gridlywishlist_opt[$values] : $default;
	}
}

/**
 * Normalize wishlist list stored in post meta.
 *
 * @param int $product_id Product ID.
 * @return array
 */
function gridlywishlist_get_wishlists($product_id)
{
	$product_id = absint($product_id);
	if (! $product_id) {
		return array();
	}

	$product_gridlywishlist = get_post_meta($product_id, 'gridlywishlist', true);
	if (empty($product_gridlywishlist)) {
		return array();
	}

	if (is_array($product_gridlywishlist)) {
		$wishlists = $product_gridlywishlist;
	} else {
		$product_gridlywishlist = trim((string) $product_gridlywishlist, ",");
		if ($product_gridlywishlist === '') {
			return array();
		}
		$wishlists = explode(',', $product_gridlywishlist);
	}

	$wishlists = array_map(
		function ($wishlist) {
			return (string) trim($wishlist);
		},
		(array) $wishlists
	);

	$wishlists = array_filter($wishlists, 'strlen');

	return array_values(array_unique($wishlists));
}

/**
 * Persist wishlist meta back to the product.
 *
 * @param int   $product_id Product ID.
 * @param array $wishlists  Gridly Wishlist identifiers.
 * @return bool
 */
function gridlywishlist_store_wishlists($product_id, array $wishlists)
{
	$product_id = absint($product_id);
	if (! $product_id) {
		return false;
	}

	$wishlists = array_values(array_unique(array_filter(array_map('strval', $wishlists), 'strlen')));

	if (empty($wishlists)) {
		return delete_post_meta($product_id, 'gridlywishlist');
	}

	$meta_value = ',' . implode(',', $wishlists) . ',';

	return update_post_meta($product_id, 'gridlywishlist', $meta_value);
}

/**
 * Retrieve wishlist products for a specific user.
 *
 * @param int $user_id User ID.
 * @return array
 */
function gridlywishlist_get_user_wishlists_list($user_id, $collection_identifier = '')
{
	$user_id = absint($user_id);
	if (! $user_id) {
		return array();
	}

	$collections = gridlywishlist_get_user_collections($user_id, true);
	if (empty($collections)) {
		return array();
	}

	$collection_ids = array();
	if ($collection_identifier) {
		$record = gridlywishlist_get_collection_record($user_id, $collection_identifier, false);
		if ($record) {
			$collection_ids[] = (int) $record['db_id'];
		}
	} else {
		foreach ($collections as $collection) {
			$collection_ids[] = (int) $collection['db_id'];
		}
	}

	if (empty($collection_ids)) {
		return array();
	}

	global $wpdb;
	$items_table = gridlywishlist_get_table_name('items');
	$placeholders = implode(',', array_fill(0, count($collection_ids), '%d'));
	$query = $wpdb->prepare(
		"SELECT DISTINCT product_id FROM {$items_table} WHERE collection_id IN ({$placeholders})",
		$collection_ids
	);

	$product_ids = $wpdb->get_col($query);
	return array_values(array_unique(array_map('absint', (array) $product_ids)));
}

/**
 * Update user meta cache for wishlists.
 *
 * @param int    $user_id
 * @param int    $product_id
 * @param string $fav_action
 * @return void
 */
function gridlywishlist_sanitize_collection_name($name)
{
	$name = wp_strip_all_tags($name);
	return trim($name);
}

function gridlywishlist_get_default_collection_id($user_id)
{
	$default = gridlywishlist_get_default_collection($user_id, true);
	return $default ? $default['slug'] : 'default_' . absint($user_id);
}

function gridlywishlist_get_default_collection($user_id, $create_if_missing = false)
{
	global $wpdb;
	$collections_table = gridlywishlist_get_table_name('collections');
	$user_id = absint($user_id);
	if (! $user_id) {
		return null;
	}
	$row = $wpdb->get_row(
		$wpdb->prepare(
			"SELECT * FROM {$collections_table} WHERE user_id = %d AND is_default = 1 LIMIT 1",
			$user_id
		),
		ARRAY_A
	);
	if ($row) {
		return array(
			'db_id'      => (int) $row['id'],
			'id'         => $row['slug'],
			'slug'       => $row['slug'],
			'name'       => $row['name'],
			'is_public'  => (bool) $row['is_public'],
			'is_default' => true,
			'items'      => array(),
		);
	}
	if (! $create_if_missing) {
		return null;
	}
	return gridlywishlist_insert_collection(
		$user_id,
		__('My Wishlist', 'gridlywishlist'),
		false,
		true,
		'default_' . $user_id
	);
}

function gridlywishlist_insert_collection($user_id, $name, $is_public = false, $is_default = false, $slug = '')
{
	global $wpdb;
	$collections_table = gridlywishlist_get_table_name('collections');
	$user_id = absint($user_id);
	if (! $user_id) {
		return null;
	}
	$name = gridlywishlist_sanitize_collection_name($name);
	if ('' === $name) {
		$name = __('My Wishlist', 'gridlywishlist');
	}
	if ('' === $slug) {
		$slug = wp_generate_uuid4();
	}
	$now = current_time('mysql');
	$wpdb->insert(
		$collections_table,
		array(
			'user_id'    => $user_id,
			'slug'       => $slug,
			'name'       => $name,
			'is_public'  => $is_public ? 1 : 0,
			'is_default' => $is_default ? 1 : 0,
			'created_at' => $now,
			'updated_at' => $now,
		),
		array('%d', '%s', '%s', '%d', '%d', '%s', '%s')
	);
	$insert_id = $wpdb->insert_id;
	return array(
		'db_id'      => $insert_id,
		'id'         => $slug,
		'slug'       => $slug,
		'name'       => $name,
		'is_public'  => (bool) $is_public,
		'is_default' => (bool) $is_default,
		'items'      => array(),
	);
}

function gridlywishlist_get_user_collections($user_id, $ensure_default = false)
{
	global $wpdb;
	$user_id = absint($user_id);
	if (! $user_id) {
		return array();
	}
	$collections_table = gridlywishlist_get_table_name('collections');
	$items_table = gridlywishlist_get_table_name('items');
	$rows = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT * FROM {$collections_table} WHERE user_id = %d ORDER BY is_default DESC, id ASC",
			$user_id
		),
		ARRAY_A
	);
	if (empty($rows) && $ensure_default) {
		gridlywishlist_migrate_user_collections($user_id);
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$collections_table} WHERE user_id = %d ORDER BY is_default DESC, id ASC",
				$user_id
			),
			ARRAY_A
		);
	}
	if (empty($rows)) {
		return array();
	}
	$collection_ids = array_map(function ($row) {
		return (int) $row['id'];
	}, $rows);
	$items_map = array();
	if (! empty($collection_ids)) {
		$placeholders = implode(',', array_fill(0, count($collection_ids), '%d'));
		$query = $wpdb->prepare(
			"SELECT collection_id, product_id FROM {$items_table} WHERE collection_id IN ({$placeholders})",
			$collection_ids
		);
		$items = $wpdb->get_results($query, ARRAY_A);
		foreach ($items as $item) {
			$key = (int) $item['collection_id'];
			if (! isset($items_map[$key])) {
				$items_map[$key] = array();
			}
			$items_map[$key][] = (int) $item['product_id'];
		}
	}
	$collections = array();
	foreach ($rows as $row) {
		$db_id = (int) $row['id'];
		$collections[$row['slug']] = array(
			'db_id'      => $db_id,
			'id'         => $row['slug'],
			'slug'       => $row['slug'],
			'name'       => $row['name'],
			'is_public'  => (bool) $row['is_public'],
			'is_default' => (bool) $row['is_default'],
			'items'      => isset($items_map[$db_id]) ? array_values(array_unique($items_map[$db_id])) : array(),
		);
	}
	return $collections;
}

function gridlywishlist_get_collection_record($user_id, $identifier, $fallback_to_default = true)
{
	global $wpdb;
	$user_id = absint($user_id);
	if (! $user_id) {
		return null;
	}
	$collections_table = gridlywishlist_get_table_name('collections');
	$row = null;
	if ($identifier) {
		if (is_numeric($identifier)) {
			$row = $wpdb->get_row(
				$wpdb->prepare(
					"SELECT * FROM {$collections_table} WHERE user_id = %d AND id = %d LIMIT 1",
					$user_id,
					(int) $identifier
				),
				ARRAY_A
			);
		}
		if (! $row) {
			$row = $wpdb->get_row(
				$wpdb->prepare(
					"SELECT * FROM {$collections_table} WHERE user_id = %d AND slug = %s LIMIT 1",
					$user_id,
					$identifier
				),
				ARRAY_A
			);
		}
	}
	if (! $row) {
		return $fallback_to_default ? gridlywishlist_get_default_collection($user_id, true) : null;
	}
	return array(
		'db_id'      => (int) $row['id'],
		'id'         => $row['slug'],
		'slug'       => $row['slug'],
		'name'       => $row['name'],
		'is_public'  => (bool) $row['is_public'],
		'is_default' => (bool) $row['is_default'],
		'items'      => array(),
	);
}

function gridlywishlist_validate_collection($user_id, $collection_id = '', $allow_empty = false)
{
	if ($allow_empty && '' === $collection_id) {
		return null;
	}
	return gridlywishlist_get_collection_record($user_id, $collection_id, true);
}

function gridlywishlist_update_user_wishlists($user_id, $product_id, $fav_action, $collection_id = '', $variation_id = 0)
{
	global $wpdb;
	$user_id      = absint($user_id);
	$product_id   = absint($product_id);
	$variation_id = absint($variation_id);
	if (! $user_id || ! $product_id) {
		return false;
	}
	$collection = gridlywishlist_validate_collection($user_id, $collection_id);
	if (! $collection) {
		return false;
	}
	$collection_db_id = (int) $collection['db_id'];
	$items_table = gridlywishlist_get_table_name('items');
	if ('insert' === $fav_action) {
		$exists = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT id FROM {$items_table} WHERE collection_id = %d AND product_id = %d LIMIT 1",
				$collection_db_id,
				$product_id
			)
		);
		if (! $exists) {
			$wpdb->insert(
				$items_table,
				array(
					'collection_id' => $collection_db_id,
					'product_id'    => $product_id,
					'variation_id'  => $variation_id,
					'added_at'      => current_time('mysql'),
				),
				array('%d', '%d', '%d', '%s')
			);
		} elseif ($variation_id) {
			// Update variation_id jika item sudah ada
			$wpdb->update(
				$items_table,
				array('variation_id' => $variation_id),
				array('collection_id' => $collection_db_id, 'product_id' => $product_id),
				array('%d'),
				array('%d', '%d')
			);
		}
	} else {
		$wpdb->delete(
			$items_table,
			array(
				'collection_id' => $collection_db_id,
				'product_id'    => $product_id,
			),
			array('%d', '%d')
		);
	}
	$collections_table = gridlywishlist_get_table_name('collections');
	$user_still_has_product = (bool) $wpdb->get_var(
		$wpdb->prepare(
			"SELECT items.id FROM {$items_table} AS items INNER JOIN {$collections_table} AS col ON col.id = items.collection_id WHERE col.user_id = %d AND items.product_id = %d LIMIT 1",
			$user_id,
			$product_id
		)
	);
	return $user_still_has_product;
}

function gridlywishlist_create_collection($user_id, $name, $is_public = false)
{
	$user_id = absint($user_id);
	if (! $user_id) {
		return new WP_Error('invalid_user', __('Invalid user.', 'gridlywishlist'));
	}
	$collections = gridlywishlist_get_user_collections($user_id, true);
	if (count($collections) >= GRIDLYWISHLIST_COLLECTION_LIMIT) {
		return new WP_Error('collection_limit', __('You have reached the maximum number of collections.', 'gridlywishlist'));
	}
	return gridlywishlist_insert_collection($user_id, $name, $is_public);
}

function gridlywishlist_update_collection($user_id, $collection_id, $args = array())
{
	global $wpdb;
	$user_id = absint($user_id);
	if (! $user_id) {
		return new WP_Error('invalid_user', __('Invalid user.', 'gridlywishlist'));
	}
	$record = gridlywishlist_get_collection_record($user_id, $collection_id, false);
	if (! $record) {
		return new WP_Error('not_found', __('Collection not found.', 'gridlywishlist'));
	}
	$data  = array();
	$where = array('id' => $record['db_id']);
	$formats = array();
	$where_formats = array('%d');
	if (isset($args['name'])) {
		$name = gridlywishlist_sanitize_collection_name($args['name']);
		if ($name !== '') {
			$data['name'] = $name;
			$formats[] = '%s';
		}
	}
	if (isset($args['is_public'])) {
		$data['is_public'] = $args['is_public'] ? 1 : 0;
		$formats[] = '%d';
	}
	if (! empty($data)) {
		$data['updated_at'] = current_time('mysql');
		$formats[] = '%s';
		$wpdb->update(gridlywishlist_get_table_name('collections'), $data, $where, $formats, $where_formats);
	}
	return gridlywishlist_get_collection_record($user_id, $record['id'], true);
}

function gridlywishlist_delete_collection($user_id, $collection_id, $target_collection_id = '')
{
	global $wpdb;
	$user_id = absint($user_id);
	if (! $user_id) {
		return new WP_Error('invalid_user', __('Invalid user.', 'gridlywishlist'));
	}
	$collection = gridlywishlist_get_collection_record($user_id, $collection_id, false);
	if (! $collection) {
		return new WP_Error('not_found', __('Collection not found.', 'gridlywishlist'));
	}
	if (! empty($collection['is_default'])) {
		return new WP_Error('cannot_delete_default', __('Default collection cannot be deleted.', 'gridlywishlist'));
	}
	$target = gridlywishlist_get_collection_record($user_id, $target_collection_id, true);
	if ($target && $target['db_id'] === $collection['db_id']) {
		$target = gridlywishlist_get_default_collection($user_id, true);
	}
	$items_table = gridlywishlist_get_table_name('items');
	$wpdb->update(
		$items_table,
		array('collection_id' => $target['db_id']),
		array('collection_id' => $collection['db_id']),
		array('%d'),
		array('%d')
	);
	$moved = (int) $wpdb->rows_affected;
	$wpdb->delete(
		gridlywishlist_get_table_name('collections'),
		array('id' => $collection['db_id']),
		array('%d')
	);
	return array(
		'target_id' => $target['id'],
		'moved'     => $moved,
	);
}

function gridlywishlist_move_items_between_collections($user_id, array $product_ids, $target_collection_id, $source_collection_id = '')
{
	global $wpdb;
	$user_id = absint($user_id);
	if (! $user_id) {
		return new WP_Error('invalid_user', __('Invalid user.', 'gridlywishlist'));
	}
	$product_ids = array_values(array_unique(array_filter(array_map('absint', $product_ids))));
	if (empty($product_ids)) {
		return new WP_Error('invalid_products', __('No products selected.', 'gridlywishlist'));
	}
	$target = gridlywishlist_get_collection_record($user_id, $target_collection_id, true);
	if (! $target) {
		return new WP_Error('invalid_target', __('Target collection not found.', 'gridlywishlist'));
	}
	$items_table = gridlywishlist_get_table_name('items');
	if ($source_collection_id) {
		$source = gridlywishlist_get_collection_record($user_id, $source_collection_id, false);
		$source_db_id = $source ? $source['db_id'] : 0;
	} else {
		$source_db_id = 0;
	}
	if ($source_db_id) {
		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$items_table} WHERE collection_id = %d AND product_id IN (" . implode(',', array_fill(0, count($product_ids), '%d')) . ")",
				array_merge(array($source_db_id), $product_ids)
			)
		);
	} else {
		foreach ($product_ids as $product_id) {
			$wpdb->query(
				$wpdb->prepare(
					"DELETE FROM {$items_table} WHERE product_id = %d AND collection_id IN (SELECT id FROM " . gridlywishlist_get_table_name('collections') . " WHERE user_id = %d)",
					$product_id,
					$user_id
				)
			);
		}
	}
	foreach ($product_ids as $product_id) {
		$wpdb->insert(
			$items_table,
			array(
				'collection_id' => $target['db_id'],
				'product_id'    => $product_id,
				'added_at'      => current_time('mysql'),
			),
			array('%d', '%d', '%s')
		);
	}
	return gridlywishlist_get_collection_record($user_id, $target['id'], true);
}

function gridlywishlist_prepare_collections_response($user_id)
{
	global $wpdb;
	$user_id = absint($user_id);
	if (! $user_id) {
		return array();
	}
	$collections = gridlywishlist_get_user_collections($user_id, true);
	if (empty($collections)) {
		return array();
	}
	$collection_ids = array();
	foreach ($collections as $collection) {
		$collection_ids[$collection['db_id']] = $collection;
	}
	$items_table = gridlywishlist_get_table_name('items');
	$counts = array();
	if (! empty($collection_ids)) {
		$ids = array_keys($collection_ids);
		$placeholders = implode(',', array_fill(0, count($ids), '%d'));
		$query = $wpdb->prepare(
			"SELECT collection_id, COUNT(*) as total FROM {$items_table} WHERE collection_id IN ({$placeholders}) GROUP BY collection_id",
			$ids
		);
		$results = $wpdb->get_results($query, ARRAY_A);
		foreach ($results as $row) {
			$counts[(int) $row['collection_id']] = (int) $row['total'];
		}
	}
	$response = array();
	foreach ($collection_ids as $db_id => $collection) {
		$response[] = array(
			'id'        => $collection['id'],
			'name'      => $collection['name'],
			'is_public' => ! empty($collection['is_public']),
			'count'     => isset($counts[$db_id]) ? $counts[$db_id] : 0,
		);
	}
	return $response;
}

function gridlywishlist_get_collection_for_product($user_id, $product_id)
{
	global $wpdb;
	$user_id    = absint($user_id);
	$product_id = absint($product_id);
	if (! $user_id || ! $product_id) {
		return '';
	}
	$items_table = gridlywishlist_get_table_name('items');
	$collections_table = gridlywishlist_get_table_name('collections');
	$slug = $wpdb->get_var(
		$wpdb->prepare(
			"SELECT col.slug FROM {$items_table} AS items INNER JOIN {$collections_table} AS col ON col.id = items.collection_id WHERE col.user_id = %d AND items.product_id = %d LIMIT 1",
			$user_id,
			$product_id
		)
	);
	if ($slug) {
		return $slug;
	}
	return gridlywishlist_get_default_collection_id($user_id);
}

function gridlywishlist_migrate_user_collections($user_id)
{
	$user_id = absint($user_id);
	if (! $user_id) {
		return;
	}
	$existing_rows = gridlywishlist_get_user_collections($user_id, false);
	if (! empty($existing_rows)) {
		return;
	}
	$legacy_collections = get_user_meta($user_id, 'gridlywishlist_collections', true);
	if (! is_array($legacy_collections) || empty($legacy_collections)) {
		gridlywishlist_get_default_collection($user_id, true);
		return;
	}
	foreach ($legacy_collections as $legacy_id => $collection) {
		$name = isset($collection['name']) ? $collection['name'] : __('My Wishlist', 'gridlywishlist');
		$is_public = ! empty($collection['is_public']);
		$is_default = (strpos($legacy_id, 'default_') === 0);
		$slug = is_string($legacy_id) ? $legacy_id : wp_generate_uuid4();
		$new_collection = gridlywishlist_insert_collection($user_id, $name, $is_public, $is_default, $slug);
		$items = isset($collection['items']) ? (array) $collection['items'] : array();
		if (! empty($items)) {
			global $wpdb;
			$items_table = gridlywishlist_get_table_name('items');
			foreach ($items as $product_id) {
				$product_id = absint($product_id);
				if (! $product_id) {
					continue;
				}
				$wpdb->insert(
					$items_table,
					array(
						'collection_id' => $new_collection['db_id'],
						'product_id'    => $product_id,
						'added_at'      => current_time('mysql'),
					),
					array('%d', '%d', '%s')
				);
			}
		}
	}
	delete_user_meta($user_id, 'gridlywishlist_collections');
	delete_user_meta($user_id, 'gridlywishlist_products');
}

function gridlywishlist_fetch_legacy_user_products($user_id)
{
	$user_id = absint($user_id);
	if (! $user_id) {
		return array();
	}
	$products = get_user_meta($user_id, 'gridlywishlist_products', true);
	if (is_array($products) && ! empty($products)) {
		return array_values(array_unique(array_map('absint', $products)));
	}
	global $wpdb;
	$gridlywishlist_prepare = $wpdb->prepare(
		"SELECT post_id FROM $wpdb->postmeta WHERE meta_key = %s AND meta_value LIKE %s",
		'gridlywishlist',
		'%,' . $wpdb->esc_like($user_id) . ',%'
	);
	$product_ids = $wpdb->get_col($gridlywishlist_prepare);
	$product_ids = array_values(array_unique(array_map('absint', (array) $product_ids)));
	if (! empty($product_ids)) {
		update_user_meta($user_id, 'gridlywishlist_products', $product_ids);
	}
	return $product_ids;
}

/**
 * Get user IDs who have a specific product in their wishlist.
 *
 * @param int $product_id
 * @return array
 */
function gridlywishlist_get_users_by_product($product_id)
{
	global $wpdb;
	$items_table       = gridlywishlist_get_table_name('items');
	$collections_table = gridlywishlist_get_table_name('collections');

	$results = $wpdb->get_col(
		$wpdb->prepare(
			"SELECT DISTINCT c.user_id 
			 FROM {$collections_table} c
			 INNER JOIN {$items_table} i ON c.id = i.collection_id
			 WHERE i.product_id = %d AND c.user_id > 0",
			$product_id
		)
	);

	return array_map('absint', $results);
}

/**
 * Get top wishlisted products.
 *
 * @param int $limit
 * @return array
 */
function gridlywishlist_get_top_products($limit = 10)
{
	global $wpdb;
	$items_table = gridlywishlist_get_table_name('items');
	
	$results = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT product_id, COUNT(*) as wishlist_count 
			 FROM {$items_table} 
			 GROUP BY product_id 
			 ORDER BY wishlist_count DESC 
			 LIMIT %d",
			$limit
		),
		ARRAY_A
	);

	if (empty($results)) {
		return array();
	}

	foreach ($results as &$result) {
		$product = wc_get_product($result['product_id']);
		if ($product) {
			$result['name']  = $product->get_name();
			$result['price'] = $product->get_price_html();
			$result['image'] = $product->get_image('thumbnail');
			$result['link']  = get_edit_post_link($result['product_id']);
		} else {
			$result['name']  = __('Unknown Product', 'gridlywishlist');
			$result['price'] = '';
			$result['image'] = '';
			$result['link']  = '#';
		}
	}

	return $results;
}

/**
 * Get general wishlist stats.
 *
 * @return array
 */
function gridlywishlist_get_stats()
{
	global $wpdb;
	$items_table       = gridlywishlist_get_table_name('items');
	$collections_table = gridlywishlist_get_table_name('collections');

	$total_items       = $wpdb->get_var("SELECT COUNT(*) FROM {$items_table}");
	$total_collections = $wpdb->get_var("SELECT COUNT(*) FROM {$collections_table}");
	$total_users       = $wpdb->get_var("SELECT COUNT(DISTINCT user_id) FROM {$collections_table} WHERE user_id > 0");

	return array(
		'total_items'       => (int) $total_items,
		'total_collections' => (int) $total_collections,
		'total_users'       => (int) $total_users,
	);
}
