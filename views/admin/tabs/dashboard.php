<?php
if (! defined('ABSPATH')) {
	exit;
}

$payaman_wishlist_stats = payaman_wishlist_get_stats();
$payaman_wishlist_top_products = payaman_wishlist_get_top_products(10);
?>
<div class="form-section" id="tab-dashboard">
	<div class="payaman_wishlist-stats-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px;">
		<div class="payaman_wishlist-stat-card" style="background: #fff; padding: 20px; border: 1px solid #ccc; border-radius: 4px; text-align: center;">
			<h3 style="margin: 0 0 10px 0;"><?php esc_html_e('Total Wishlisted Items', 'payaman_wishlist'); ?></h3>
			<span style="font-size: 24px; font-weight: bold; color: #0073aa;"><?php echo esc_html($payaman_wishlist_stats['total_items']); ?></span>
		</div>
		<div class="payaman_wishlist-stat-card" style="background: #fff; padding: 20px; border: 1px solid #ccc; border-radius: 4px; text-align: center;">
			<h3 style="margin: 0 0 10px 0;"><?php esc_html_e('Total Collections', 'payaman_wishlist'); ?></h3>
			<span style="font-size: 24px; font-weight: bold; color: #0073aa;"><?php echo esc_html($payaman_wishlist_stats['total_collections']); ?></span>
		</div>
		<div class="payaman_wishlist-stat-card" style="background: #fff; padding: 20px; border: 1px solid #ccc; border-radius: 4px; text-align: center;">
			<h3 style="margin: 0 0 10px 0;"><?php esc_html_e('Active Users', 'payaman_wishlist'); ?></h3>
			<span style="font-size: 24px; font-weight: bold; color: #0073aa;"><?php echo esc_html($payaman_wishlist_stats['total_users']); ?></span>
		</div>
	</div>

	<h3><?php esc_html_e('Top 10 Most Desired Products', 'payaman_wishlist'); ?></h3>
	<table class="wp-list-table widefat fixed striped">
		<thead>
			<tr>
				<th width="80"><?php esc_html_e('Image', 'payaman_wishlist'); ?></th>
				<th><?php esc_html_e('Product Name', 'payaman_wishlist'); ?></th>
				<th><?php esc_html_e('Price', 'payaman_wishlist'); ?></th>
				<th width="150"><?php esc_html_e('Wishlist Count', 'payaman_wishlist'); ?></th>
				<th width="100"><?php esc_html_e('Action', 'payaman_wishlist'); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php if (! empty($payaman_wishlist_top_products)) : ?>
				<?php foreach ($payaman_wishlist_top_products as $payaman_wishlist_product) : ?>
					<tr>
						<td><?php echo wp_kses_post($payaman_wishlist_product['image']); ?></td>
						<td><strong><?php echo esc_html($payaman_wishlist_product['name']); ?></strong></td>
						<td><?php echo wp_kses_post($payaman_wishlist_product['price']); ?></td>
						<td><span class="badge" style="background: #0073aa; color: #fff; padding: 2px 8px; border-radius: 10px;"><?php echo esc_html($payaman_wishlist_product['wishlist_count']); ?></span></td>
						<td><a href="<?php echo esc_url($payaman_wishlist_product['link']); ?>" class="button button-small"><?php esc_html_e('Edit', 'payaman_wishlist'); ?></a></td>
					</tr>
				<?php endforeach; ?>
			<?php else : ?>
				<tr>
					<td colspan="5"><?php esc_html_e('No data available yet.', 'payaman_wishlist'); ?></td>
				</tr>
			<?php endif; ?>
		</tbody>
	</table>
</div>
