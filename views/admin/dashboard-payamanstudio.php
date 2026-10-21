<?php
if (! defined('ABSPATH')) {
	exit;
}

if (! function_exists('get_plugins')) {
	require_once ABSPATH . 'wp-admin/includes/plugin.php';
}

$payaman_wishlist_all_plugins    = get_plugins();
$payaman_wishlist_active_plugins = (array) get_option('active_plugins', array());
$payaman_wishlist_installed      = array();

foreach ($payaman_wishlist_all_plugins as $payaman_wishlist_file => $payaman_wishlist_data) {
	$payaman_wishlist_is_author = isset($payaman_wishlist_data['Author']) && stripos($payaman_wishlist_data['Author'], 'Payaman') !== false;
	$payaman_wishlist_is_name   = isset($payaman_wishlist_data['Name']) && stripos($payaman_wishlist_data['Name'], 'Payaman') !== false;

	if ($payaman_wishlist_is_author || $payaman_wishlist_is_name) {
		$payaman_wishlist_installed[$payaman_wishlist_file] = array(
			'name'        => $payaman_wishlist_data['Name'],
			'version'     => isset($payaman_wishlist_data['Version']) ? $payaman_wishlist_data['Version'] : '1.0.0',
			'description' => isset($payaman_wishlist_data['Description']) ? $payaman_wishlist_data['Description'] : '',
			'author'      => isset($payaman_wishlist_data['Author']) ? $payaman_wishlist_data['Author'] : 'Payaman Studio',
			'is_active'   => in_array($payaman_wishlist_file, $payaman_wishlist_active_plugins, true),
			'plugin_file' => $payaman_wishlist_file,
			'settings_url'=> (strpos($payaman_wishlist_file, 'payaman-wishlist') !== false || strpos($payaman_wishlist_file, 'payaman_wishlist') !== false)
				? admin_url('admin.php?page=payaman_wishlist')
				: ((strpos($payaman_wishlist_file, 'payaman-whatsapp-quick-buy') !== false || strpos($payaman_wishlist_file, 'payaman-wa') !== false)
					? admin_url('admin.php?page=payaman-wa-quick-buy')
					: ''),
		);
	}
}

$payaman_wishlist_ecosystem = array(
	array(
		'slug'        => 'payaman-wishlist',
		'name'        => 'Payaman Wishlist',
		'tagline'     => __('Smart Wishlist & Email Automation for WooCommerce', 'payaman-wishlist'),
		'description' => __('Allow customers to bookmark favorite products, organize public/private collections, and automate stock & price drop email retargeting.', 'payaman-wishlist'),
		'icon'        => 'dashicons-heart',
		'installed'   => true,
		'settings'    => admin_url('admin.php?page=payaman_wishlist'),
	),
	array(
		'slug'        => 'payaman-whatsapp-quick-buy',
		'name'        => 'Payaman WhatsApp Quick Buy',
		'tagline'     => __('Direct WhatsApp Checkout & Multi-Agent CS for WooCommerce', 'payaman-wishlist'),
		'description' => __('Accelerate purchases by letting customers order directly via WhatsApp with auto-generated order details and smart agent routing.', 'payaman-wishlist'),
		'icon'        => 'dashicons-format-chat',
		'installed'   => class_exists('Payaman_WhatsApp_Quick_Buy') || defined('PAYAMAN_WA_PLUGIN_DIR'),
		'settings'    => admin_url('admin.php?page=payaman-wa-quick-buy'),
	),
	array(
		'slug'        => 'payaman-points-rewards',
		'name'        => 'Payaman Points & Rewards',
		'tagline'     => __('Loyalty Program & Customer Engagement', 'payaman-wishlist'),
		'description' => __('Boost customer retention with shopping reward points, membership tiers, and discount coupon redemptions.', 'payaman-wishlist'),
		'icon'        => 'dashicons-awards',
		'installed'   => false,
		'url'         => 'https://payamanstudio.com/',
	),
	array(
		'slug'        => 'payaman-quick-view',
		'name'        => 'Payaman Quick View',
		'tagline'     => __('Fast Product Preview Modal', 'payaman-wishlist'),
		'description' => __('Enable shoppers to preview product details and add items to cart seamlessly without leaving catalog pages.', 'payaman-wishlist'),
		'icon'        => 'dashicons-visibility',
		'installed'   => false,
		'url'         => 'https://payamanstudio.com/',
	),
	array(
		'slug'        => 'payaman-checkout-field-editor',
		'name'        => 'Payaman Checkout Editor',
		'tagline'     => __('Custom Checkout Field Manager', 'payaman-wishlist'),
		'description' => __('Easily customize WooCommerce checkout fields, add custom input fields, and streamline the ordering process.', 'payaman-wishlist'),
		'icon'        => 'dashicons-cart',
		'installed'   => false,
		'url'         => 'https://payamanstudio.com/',
	),
);
?>

<div class="wrap payamanstudio-admin" style="max-width: 1200px; margin-top: 20px;">

	<!-- Header Banner -->
	<div style="background: linear-gradient(135deg, #1d2327 0%, #2c3338 100%); color: #fff; padding: 30px; border-radius: 12px; margin-bottom: 25px; box-shadow: 0 4px 12px rgba(0,0,0,0.08);">
		<div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px;">
			<div>
				<div style="display: flex; align-items: center; gap: 12px; margin-bottom: 8px;">
					<img src="<?php echo esc_url(PAYAMAN_WISHLIST_URL . '/assets/images/icon_payaman.svg'); ?>" alt="Payaman Studio" style="width: 32px; height: 32px; filter: brightness(0) invert(1);">
					<h1 style="color: #fff; margin: 0; font-size: 24px; font-weight: 700; line-height: 1.2;">
						<?php esc_html_e('Payaman Studio', 'payaman-wishlist'); ?>
					</h1>
				</div>
				<p style="margin: 0; color: #c3c4c7; font-size: 14px; max-width: 600px;">
					<?php esc_html_e('Centralized management hub and WooCommerce plugin ecosystem by Payaman Studio to enhance your store conversion.', 'payaman-wishlist'); ?>
				</p>
			</div>
			<div>
				<a href="https://payamanstudio.com/" target="_blank" rel="noopener noreferrer" class="button" style="background: #2271b1; color: #fff; border: none; padding: 6px 18px; border-radius: 6px; font-weight: 600;">
					<?php esc_html_e('Visit Website', 'payaman-wishlist'); ?> &rarr;
				</a>
			</div>
		</div>
	</div>

	<!-- Section 1: Installed Plugins -->
	<div style="margin-bottom: 35px;">
		<div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 15px;">
			<h2 style="font-size: 18px; font-weight: 600; margin: 0; color: #1d2327;">
				<?php esc_html_e('Installed Payaman Plugins', 'payaman-wishlist'); ?>
			</h2>
			<span style="background: #e8f0fe; color: #1a73e8; padding: 3px 10px; border-radius: 12px; font-size: 12px; font-weight: 600;">
				<?php echo esc_html(count($payaman_wishlist_installed)); ?> <?php esc_html_e('Plugins Detected', 'payaman-wishlist'); ?>
			</span>
		</div>

		<div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(360px, 1fr)); gap: 20px;">
			<?php if (! empty($payaman_wishlist_installed)) : ?>
				<?php foreach ($payaman_wishlist_installed as $payaman_wishlist_plugin) : ?>
					<div style="background: #fff; border: 1px solid #c3c4c7; border-radius: 10px; padding: 22px; display: flex; flex-direction: column; justify-content: space-between; box-shadow: 0 1px 3px rgba(0,0,0,0.04); transition: transform 0.2s, box-shadow 0.2s;">
						<div>
							<div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 12px;">
								<div style="display: flex; align-items: center; gap: 10px;">
									<div style="width: 38px; height: 38px; background: #f0f6fc; border-radius: 8px; display: flex; align-items: center; justify-content: center; color: #2271b1;">
										<span class="dashicons dashicons-admin-plugins" style="font-size: 22px; width: 22px; height: 22px;"></span>
									</div>
									<div>
										<h3 style="margin: 0; font-size: 16px; font-weight: 700; color: #1d2327;">
											<?php echo esc_html($payaman_wishlist_plugin['name']); ?>
										</h3>
										<span style="font-size: 12px; color: #646970;">
											<?php echo esc_html('v' . $payaman_wishlist_plugin['version']); ?> &bull; <?php echo esc_html($payaman_wishlist_plugin['author']); ?>
										</span>
									</div>
								</div>
								<div>
									<?php if ($payaman_wishlist_plugin['is_active']) : ?>
										<span style="background: #edfaef; color: #1a7f37; border: 1px solid #46b450; padding: 2px 8px; border-radius: 10px; font-size: 11px; font-weight: 600;">
											<?php esc_html_e('Active', 'payaman-wishlist'); ?>
										</span>
									<?php else : ?>
										<span style="background: #f6f7f7; color: #646970; border: 1px solid #dcdcde; padding: 2px 8px; border-radius: 10px; font-size: 11px; font-weight: 600;">
											<?php esc_html_e('Inactive', 'payaman-wishlist'); ?>
										</span>
									<?php endif; ?>
								</div>
							</div>

							<p style="margin: 0 0 18px 0; font-size: 13px; color: #50575e; line-height: 1.5;">
								<?php echo wp_kses_post($payaman_wishlist_plugin['description']); ?>
							</p>
						</div>

						<div style="border-top: 1px solid #f0f0f1; padding-top: 15px; display: flex; justify-content: space-between; align-items: center;">
							<span style="font-size: 12px; color: #8c8f94;">
								<?php esc_html_e('WooCommerce Ready', 'payaman-wishlist'); ?>
							</span>
							<?php if ($payaman_wishlist_plugin['is_active'] && ! empty($payaman_wishlist_plugin['settings_url'])) : ?>
								<a href="<?php echo esc_url($payaman_wishlist_plugin['settings_url']); ?>" class="button button-primary" style="font-weight: 600;">
									<?php esc_html_e('Plugin Settings', 'payaman-wishlist'); ?> &rarr;
								</a>
							<?php endif; ?>
						</div>
					</div>
				<?php endforeach; ?>
			<?php endif; ?>
		</div>
	</div>

	<!-- Section 2: Payaman Studio Ecosystem -->
	<div>
		<div style="margin-bottom: 15px;">
			<h2 style="font-size: 18px; font-weight: 600; margin: 0 0 4px 0; color: #1d2327;">
				<?php esc_html_e('Payaman Studio Plugin Ecosystem', 'payaman-wishlist'); ?>
			</h2>
			<p style="margin: 0; color: #646970; font-size: 13px;">
				<?php esc_html_e('Collection of WooCommerce extensions designed to boost your online store capability.', 'payaman-wishlist'); ?>
			</p>
		</div>

		<div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(270px, 1fr)); gap: 18px;">
			<?php foreach ($payaman_wishlist_ecosystem as $payaman_wishlist_item) : ?>
				<div style="background: #fff; border: 1px solid #dcdcde; border-radius: 8px; padding: 18px; display: flex; flex-direction: column; justify-content: space-between;">
					<div>
						<div style="display: flex; align-items: center; gap: 10px; margin-bottom: 10px;">
							<div style="width: 32px; height: 32px; background: #f6f7f7; border-radius: 6px; display: flex; align-items: center; justify-content: center; color: #2271b1;">
								<span class="dashicons <?php echo esc_attr($payaman_wishlist_item['icon']); ?>" style="font-size: 18px; width: 18px; height: 18px;"></span>
							</div>
							<h4 style="margin: 0; font-size: 14px; font-weight: 700; color: #1d2327;">
								<?php echo esc_html($payaman_wishlist_item['name']); ?>
							</h4>
						</div>
						<div style="font-size: 11px; font-weight: 600; color: #2271b1; margin-bottom: 6px;">
							<?php echo esc_html($payaman_wishlist_item['tagline']); ?>
						</div>
						<p style="margin: 0 0 15px 0; font-size: 12px; color: #50575e; line-height: 1.5;">
							<?php echo esc_html($payaman_wishlist_item['description']); ?>
						</p>
					</div>

					<div style="border-top: 1px solid #f6f7f7; padding-top: 12px;">
						<?php if (! empty($payaman_wishlist_item['installed'])) : ?>
							<a href="<?php echo esc_url($payaman_wishlist_item['settings']); ?>" class="button button-secondary" style="width: 100%; text-align: center;">
								<?php esc_html_e('Manage Wishlist', 'payaman-wishlist'); ?>
							</a>
						<?php else : ?>
							<a href="<?php echo esc_url($payaman_wishlist_item['url']); ?>" target="_blank" rel="noopener noreferrer" class="button" style="width: 100%; text-align: center; color: #646970;">
								<?php esc_html_e('Learn More', 'payaman-wishlist'); ?> &nearr;
							</a>
						<?php endif; ?>
					</div>
				</div>
			<?php endforeach; ?>
		</div>
	</div>

</div>
