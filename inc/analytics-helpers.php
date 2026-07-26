<?php
/**
 * Payaman_Wishlist Analytics Helpers
 *
 * @package payaman_wishlist
 * @version 1.0.0
 */

if (! defined('ABSPATH')) {
	exit;
}

/**
 * Get top wishlisted products.
 *
 * @param int $limit
 * @return array
 */
function payaman_wishlist_get_top_products($limit = 10)
{
	global $wpdb;
	$items_table = payaman_wishlist_get_table_name('items');
	
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
			$result['name']  = __('Unknown Product', 'payaman_wishlist');
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
function payaman_wishlist_get_stats()
{
	global $wpdb;
	$items_table       = payaman_wishlist_get_table_name('items');
	$collections_table = payaman_wishlist_get_table_name('collections');

	$total_items       = $wpdb->get_var("SELECT COUNT(*) FROM {$items_table}");
	$total_collections = $wpdb->get_var("SELECT COUNT(*) FROM {$collections_table}");
	$total_users       = $wpdb->get_var("SELECT COUNT(DISTINCT user_id) FROM {$collections_table} WHERE user_id > 0");

	return array(
		'total_items'       => (int) $total_items,
		'total_collections' => (int) $total_collections,
		'total_users'       => (int) $total_users,
	);
}

/**
 * Check if a specific user has a product in their wishlist.
 *
 * @param int $user_id
 * @param int $product_id
 * @return bool
 */
function payaman_wishlist_user_has_product($user_id, $product_id)
{
	global $wpdb;
	$items_table       = payaman_wishlist_get_table_name('items');
	$collections_table = payaman_wishlist_get_table_name('collections');

	$found = $wpdb->get_var(
		$wpdb->prepare(
			"SELECT COUNT(*) FROM {$items_table} i
			 INNER JOIN {$collections_table} c ON c.id = i.collection_id
			 WHERE c.user_id = %d AND i.product_id = %d",
			$user_id,
			$product_id
		)
	);

	return (bool) $found;
}

/**
 * Get user IDs who have a specific product in their wishlist.
 *
 * @param int $product_id
 * @return array
 */
function payaman_wishlist_get_users_by_product($product_id)
{
	global $wpdb;
	$items_table       = payaman_wishlist_get_table_name('items');
	$collections_table = payaman_wishlist_get_table_name('collections');

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
