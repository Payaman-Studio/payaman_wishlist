<?php

/**
 * Front Page GridlyWishlist
 *
 * @package gridlywishlist
 * @since 1.0.0
 * @version 1.0.0
 */
if (! defined('ABSPATH')) {
	exit;
}

if (! class_exists('GridlyWishlist_Front')) {

	/**
	 *
	 * Class for add admin page on wp-admin
	 */
	class GridlyWishlist_Front
	{

		/**
		 * Constructor.
		 *
		 * @version 1.0.0
		 */
		public function __construct()
		{
			if (is_admin()) {
				return;
			}

			if ('yes' !== gridlywishlist_setting('enabled', 'yes')) {
				return;
			}

			add_shortcode('gridlywishlist_button', array($this, 'shortcode'));
			add_shortcode('gridlywishlist_list', array($this, 'wishlist_list'));
			add_action('wp_enqueue_scripts', array($this, 'wp_enqueue_scripts'));
			add_action('wp_footer', array($this, 'render_modal_template'));
			add_action('woocommerce_add_to_cart', array($this, 'handle_add_to_cart'), 10, 6);
			$this->set_default_gridlywishlist_button();
		}

		/**
		 * Handle auto removal from wishlist when product added to cart.
		 */
		public function handle_add_to_cart($cart_item_key, $product_id, $quantity, $variation_id, $variation, $cart_item_data)
		{
			if (gridlywishlist_setting('remove_after_add_to_cart') !== 'yes') {
				return;
			}

			$user_id = get_current_user_id();

			// For Logged in users
			if ($user_id) {
				// Remove from ALL collections for this user
				global $wpdb;
				$items_table = gridlywishlist_get_table_name('items');
				$collections_table = gridlywishlist_get_table_name('collections');

				$wpdb->query(
					$wpdb->prepare(
						"DELETE i FROM {$items_table} i 
						 INNER JOIN {$collections_table} c ON i.collection_id = c.id 
						 WHERE c.user_id = %d AND i.product_id = %d",
						$user_id,
						$product_id
					)
				);

				// Also sync to legacy post meta
				$wishlists = gridlywishlist_get_wishlists($product_id);
				$user_key  = (string) $user_id;
				if (in_array($user_key, $wishlists, true)) {
					$wishlists = array_values(array_filter($wishlists, function($w) use ($user_key) { return $w !== $user_key; }));
					gridlywishlist_store_wishlists($product_id, $wishlists);
				}
			} else {
				// For Guests (Cookie based)
				if (isset($_COOKIE['gridlywishlist_product'])) {
					$cookie_data = json_decode(wp_unslash($_COOKIE['gridlywishlist_product']), true);
					if (is_array($cookie_data)) {
						$product_id_str = (string) $product_id;
						if (($key = array_search($product_id_str, $cookie_data)) !== false) {
							unset($cookie_data[$key]);
							$cookie_data = array_values($cookie_data);
							setcookie('gridlywishlist_product', wp_json_encode($cookie_data), time() + (30 * DAY_IN_SECONDS), '/');
						}
					}
				}

				// Also sync to legacy post meta using IP
				$ip = gridlywishlist_client_ip();
				$wishlists = gridlywishlist_get_wishlists($product_id);
				if (in_array($ip, $wishlists, true)) {
					$wishlists = array_values(array_filter($wishlists, function($w) use ($ip) { return $w !== $ip; }));
					gridlywishlist_store_wishlists($product_id, $wishlists);
				}
			}
		}

		/**
		 * Set default gridlywishlist button set
		 */
		public function set_default_gridlywishlist_button()
		{
			$display_on = (array) gridlywishlist_setting('display_on', array());
			$position_config = $this->get_button_position_config();

			if (in_array('single_product', $display_on, true)) {
				$single_hook = $position_config['single'];
				if (! empty($single_hook['hook'])) {
					add_action($single_hook['hook'], array($this, 'gridlywishlist_add_button'), isset($single_hook['priority']) ? $single_hook['priority'] : 10);
				}
			}
			if (in_array('loop_product', $display_on, true)) {
				$loop_hook = $position_config['loop'];
				if (! empty($loop_hook['hook'])) {
					add_action($loop_hook['hook'], array($this, 'gridlywishlist_add_button'), isset($loop_hook['priority']) ? $loop_hook['priority'] : 10);
				}
			}
		}

		/**
		 * Resolve button placement configuration.
		 *
		 * @return array
		 */
		protected function get_button_position_config()
		{
			$position = gridlywishlist_setting('display_position_button', 'after_add_to_cart');

			$configs = array(
				'after_add_to_cart' => array(
					'single' => array(
						'hook'     => 'woocommerce_after_add_to_cart_button',
						'priority' => 10,
					),
					'loop'   => array(
						'hook'     => 'woocommerce_after_shop_loop_item',
						'priority' => 15,
					),
				),
				'overlay_top_left'  => array(
					'single' => array(
						'hook'     => 'woocommerce_before_single_product_summary',
						'priority' => 25,
					),
					'loop'   => array(
						'hook'     => 'woocommerce_before_shop_loop_item',
						'priority' => 5,
					),
				),
			);

			if (! isset($configs[$position])) {
				$position = 'after_add_to_cart';
			}

			return $configs[$position];
		}

		/**
		 * Enqueue frontend scripts and styles.
		 */
		public function wp_enqueue_scripts()
		{
			$post          = get_post();
			$has_shortcode = $post instanceof WP_Post ? has_shortcode($post->post_content, 'gridlywishlist_list') : false;
			$should_enqueue = is_woocommerce() || is_cart() || $has_shortcode;
			$should_enqueue = apply_filters('gridlywishlist_allow_enqueue_scripts', $should_enqueue, $post);

			if (! $should_enqueue || 'yes' !== gridlywishlist_setting('enabled', 'yes')) {
				return;
			}

			$button_type = gridlywishlist_setting('type_active', 'text');

			if ('text' === $button_type) {
				$on_val  = gridlywishlist_setting('val_on');
				$off_val = gridlywishlist_setting('val_off');
			} else {
				$on_img_src  = wp_get_attachment_image_src(gridlywishlist_setting('image_val_on'), 'thumbnail', false);
				$off_img_src = wp_get_attachment_image_src(gridlywishlist_setting('image_val_off'), 'thumbnail', false);
				$on_val      = ! empty($on_img_src[0]) ? $on_img_src[0] : '';
				$off_val     = ! empty($off_img_src[0]) ? $off_img_src[0] : '';
			}

			$collections_response = array();
			$default_collection_id = '';
			$can_manage_collections = is_user_logged_in();
			if ($can_manage_collections) {
				$current_user_id = get_current_user_id();
				$collections_response = gridlywishlist_prepare_collections_response($current_user_id);
				$default_collection_id = gridlywishlist_get_default_collection_id($current_user_id);
			}

			$gridlywishlist_object = array(
				'ajax_url'                      => admin_url('admin-ajax.php'),
				'nonce'                         => wp_create_nonce('gridlywishlist_toggle'),
				'button_type'                   => $button_type,
				'required_login'                => gridlywishlist_setting('required_login', 'no'),
				'is_login'                      => is_user_logged_in(),
				'on_val'                        => $on_val,
				'off_val'                       => $off_val,
				'gridlywishlist_count'            => gridlywishlist_setting('gridlywishlist_count', 'no'),
				'enable_add_success_message'    => gridlywishlist_setting('enable_add_success_message', 'no'),
				'enable_remove_success_message' => gridlywishlist_setting('enable_remove_success_message', 'no'),
				'remove_after_add_to_cart'      => gridlywishlist_setting('remove_after_add_to_cart', 'no'),
				'add_success_message'           => gridlywishlist_setting('add_success'),
				'remove_success_message'        => gridlywishlist_setting('remove_success'),
				'required_login_message'        => gridlywishlist_setting('required_login_message', __('You must be logged in.', 'gridlywishlist')),
				'error_message'                 => __('Unable to update wishlist. Please try again.', 'gridlywishlist'),
				'wishlist_page_url'             => gridlywishlist_get_wishlist_page_url(),
				'collections'                   => $collections_response,
				'default_collection_id'         => $default_collection_id,
				'collection_limit'              => GRIDLYWISHLIST_COLLECTION_LIMIT,
				'can_manage_collections'        => $can_manage_collections,
				// i18n strings
				'i18n'                          => array(
					'fill_collection_name'    => __('Please fill in the collection name.', 'gridlywishlist'),
					'select_collection_first' => __('Please select a collection first.', 'gridlywishlist'),
					'collection_limit_reached' => __('Collection limit has been reached.', 'gridlywishlist'),
					'generic_error'           => __('An error occurred. Please try again.', 'gridlywishlist'),
					'no_collections_yet'      => __('No collections yet', 'gridlywishlist'),
				),
			);

			wp_enqueue_style('gridlywishlist-style', GRIDLYWISHLIST_URL . '/assets/css/gridlywishlist-style.css', array(), GRIDLYWISHLIST_VERSION);
			wp_enqueue_script('gridlywishlist-script', GRIDLYWISHLIST_URL . '/assets/js/gridlywishlist-script.js', array('jquery', 'js-cookie'), GRIDLYWISHLIST_VERSION, true);
			wp_localize_script('gridlywishlist-script', 'gridlywishlist_object', $gridlywishlist_object);
		}

		/**
		 * Create shortcode to dispaly gridlywishlist button
		 *
		 * @param array $attr shortcode attribute
		 *
		 * @version 1.0.0
		 */
		public function shortcode($attr = array())
		{
			$defaults = array(
				'class'      => '',
				'product_id' => 0,
			);
			$attr     = shortcode_atts($defaults, $attr, 'gridlywishlist_button');

			$product_id = absint($attr['product_id']);

			if (! $product_id && class_exists('WC_Product')) {
				global $product;
				if ($product instanceof WC_Product) {
					$product_id = $product->get_id();
				}
			}

			if (! $product_id) {
				return '';
			}

			if (! function_exists('wc_get_product')) {
				return '';
			}

			$product_object = wc_get_product($product_id);

			if (! $product_object) {
				return '';
			}

			$class         = trim($attr['class']);
			$position_class = 'gridlywishlist-position-' . sanitize_html_class(gridlywishlist_setting('display_position_button', 'after_add_to_cart'));
			$wrapper_class  = trim($class . ' ' . $position_class);
			$icon_wishlist = 'off';
			$cookie_gridlywishlist   = array();
			$current_collection_id = '';

			if (isset($_COOKIE['gridlywishlist_product'])) {
				$cookie_gridlywishlist = json_decode(wp_unslash($_COOKIE['gridlywishlist_product']), true);
				$cookie_gridlywishlist = is_array($cookie_gridlywishlist) ? array_map('absint', $cookie_gridlywishlist) : array();
			}

			if (is_user_logged_in() && $this->get_wishlist_by_user($product_id, get_current_user_id())) {
				$icon_wishlist = 'on';
				$current_collection_id = gridlywishlist_get_collection_for_product(get_current_user_id(), $product_id);
			} elseif (in_array($product_id, $cookie_gridlywishlist, true)) {
				$icon_wishlist = 'on';
			}

			$button_type = gridlywishlist_setting('type_active', 'text');
			$gridlywishlist_count  = '';

			if ('yes' === gridlywishlist_setting('gridlywishlist_count', 'no')) {
				$count_gridlywishlist = (int) get_gridlywishlist($product_id);
				if ('text' === $button_type) {
					$gridlywishlist_count = ' (' . esc_html($count_gridlywishlist) . ')';
				} else {
					$gridlywishlist_count = '<span class="count">' . esc_html($count_gridlywishlist) . '</span>';
				}
			}

			$component  = '<span class="gridlywishlist ' . esc_attr($wrapper_class) . '" data-product-id="' . esc_attr($product_id) . '" data-collection-id="' . esc_attr($current_collection_id) . '" data-variation-id="">';
			$component .= '<img src="' . esc_url(GRIDLYWISHLIST_URL . '/assets/images/loading.gif') . '" class="gridlywishlist-loading" data-product-id="' . esc_attr($product_id) . '" />';

			if ('text' === $button_type) {
				$button_label = trim(gridlywishlist_setting('val_' . $icon_wishlist) . ' ' . $gridlywishlist_count);
				$component   .= '<button type="button" class="gridlywishlist-button ' . esc_attr($icon_wishlist) . '" data-product-id="' . esc_attr($product_id) . '" data-collection-id="' . esc_attr($current_collection_id) . '" data-variation-id="">' . esc_html($button_label) . '</button>';
			} else {
				$on_img_src  = wp_get_attachment_image_src(gridlywishlist_setting('image_val_on'), 'thumbnail', false);
				$off_img_src = wp_get_attachment_image_src(gridlywishlist_setting('image_val_off'), 'thumbnail', false);
				$on_val      = ! empty($on_img_src[0]) ? $on_img_src[0] : '';
				$off_val     = ! empty($off_img_src[0]) ? $off_img_src[0] : '';

				$gridlywishlist_class  = 'on' === $icon_wishlist ? $on_val : $off_val;
				$component  .= '<span class="gridlywishlist_button_icon">';
				$component  .= '<img src="' . esc_url($gridlywishlist_class) . '" class="gridlywishlist-button ' . esc_attr($icon_wishlist) . '" data-product-id="' . esc_attr($product_id) . '" data-collection-id="' . esc_attr($current_collection_id) . '" data-variation-id="" />';
				$component  .= $gridlywishlist_count;
				$component  .= '</span>';
			}

			$component .= '</span>';

			return $component;
		}

		/**
		 * get gridlywishlist by user_id
		 *
		 * @param int $product_id ID Product
		 * @param int $user_id ID User Current Login
		 *
		 * @version 1.0.0
		 */
		public function get_wishlist_by_user($product_id, $user_id)
		{
			$product_id = absint($product_id);
			$user_id    = (string) absint($user_id);

			if (! $product_id || '' === $user_id) {
				return 0;
			}

			$wishlists = gridlywishlist_get_wishlists($product_id);

			return in_array($user_id, $wishlists, true) ? 1 : 0;
		}

		/**
		 * Get all product wishlist
		 *
		 * @return [type] [description]
		 */
		public function get_wishlist_product_by_user($user_id, $collection_id = '')
		{
			return gridlywishlist_get_user_wishlists_list($user_id, $collection_id);
		}

		public function gridlywishlist_add_button()
		{
			echo do_shortcode('[gridlywishlist_button]');
		}

		/**
		 * Display wishlist list with shortcode
		 *
		 * @return [type] [description]
		 */
		public function wishlist_list($atts = array())
		{
			$atts = shortcode_atts(
				array(
					'collection' => '',
				),
				$atts,
				'gridlywishlist_list'
			);

			$active_collection = isset($_GET['collection']) ? sanitize_text_field(wp_unslash($_GET['collection'])) : sanitize_text_field($atts['collection']);
			
			if (is_user_logged_in() && empty($active_collection)) {
				$active_collection = gridlywishlist_get_default_collection_id(get_current_user_id());
			}

			$list_wishlist = array();

			// Handle ?share={slug} — tampilkan koleksi publik milik user lain
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$share_slug = isset($_GET['share']) ? sanitize_text_field(wp_unslash($_GET['share'])) : '';
			if ($share_slug) {
				$public_collection = gridlywishlist_get_public_collection($share_slug);
				if (! $public_collection) {
					echo '<p class="gridlywishlist-empty-message">' . esc_html__('This wishlist collection is not available or is set to private.', 'gridlywishlist') . '</p>';
					return;
				}
				$list_wishlist = $public_collection['product_ids'];
				$list_wishlist = array_values(array_unique(array_filter($list_wishlist)));
				if (! empty($list_wishlist)) {
					$args = array(
						'post_type'           => 'product',
						'posts_per_page'      => -1,
						'post_status'         => 'publish',
						'post__in'            => $list_wishlist,
						'orderby'             => 'post__in',
						'ignore_sticky_posts' => true,
					);
					$products = new WP_Query($args);
					if ($products->have_posts()) {
						echo '<p class="gridlywishlist-shared-notice">'
							. sprintf(
								/* translators: %s: collection name */
								esc_html__('Shared collection: %s', 'gridlywishlist'),
								'<strong>' . esc_html($public_collection['name']) . '</strong>'
							)
							. '</p>';
						$this->render_wishlist_table($products, '');
						wp_reset_postdata();
					} else {
						$this->render_empty_message();
					}
				} else {
					$this->render_empty_message();
				}
				return;
			}

			if ('yes' === gridlywishlist_setting('required_login', 'no') && ! is_user_logged_in()) {
				echo apply_filters('woocommerce_checkout_must_be_logged_in_message', __('You must be logged in.', 'woocommerce'));
				return;
			}

			if (is_user_logged_in()) {
				$list_wishlist = $this->get_wishlist_product_by_user(get_current_user_id(), $active_collection);
			} elseif (isset($_COOKIE['gridlywishlist_product'])) {
				$cookie_gridlywishlist = json_decode(wp_unslash($_COOKIE['gridlywishlist_product']), true);
				if (is_array($cookie_gridlywishlist)) {
					$list_wishlist = array_map('absint', $cookie_gridlywishlist);
				}
			}

			$list_wishlist = array_values(array_unique(array_filter($list_wishlist)));

			if (! empty($list_wishlist)) {
				$args     = array(
					'post_type'           => 'product',
					'posts_per_page'      => -1,
					'post_status'         => 'publish',
					'post__in'            => $list_wishlist,
					'orderby'             => 'post__in',
					'ignore_sticky_posts' => true,
				);
				$products = new WP_Query($args);
				if ($products->have_posts()) {
					$this->render_wishlist_table($products, $active_collection);
					wp_reset_postdata();
				} else {
					$this->render_empty_message();
				}
			} else {
				$this->render_empty_message();
			}
		}

		/**
		 * Render wishlists table output.
		 *
		 * @param WP_Query $products
		 * @return void
		 */
		private function render_wishlist_table($products, $active_collection = '')
		{
			$empty_message = esc_html__('No wishlist products found.', 'gridlywishlist');
			$collections = is_user_logged_in() ? gridlywishlist_prepare_collections_response(get_current_user_id()) : array();
			$current_collection_id = $active_collection ? $active_collection : (is_user_logged_in() ? gridlywishlist_get_default_collection_id(get_current_user_id()) : '');
?>
			<div class="gridlywishlist-collections-container">
				<div class="gridlywishlist-collections-nav">
					<ul class="gridlywishlist-collection-tabs">
						<?php foreach ($collections as $collection) : 
							$is_active = $collection['id'] === $current_collection_id;
							$is_public = ! empty($collection['is_public']);
						?>
							<li class="<?php echo $is_active ? 'is-active' : ''; ?>">
								<a href="<?php echo esc_url(add_query_arg('collection', $collection['id'])); ?>" class="gridlywishlist-collection-link <?php echo $is_active ? 'active' : ''; ?>" data-collection-id="<?php echo esc_attr($collection['id']); ?>">
									<?php echo esc_html($collection['name']); ?> 
									<span class="gridlywishlist-tab-count">(<?php echo esc_html($collection['count']); ?>)</span>
								</a>
								<?php if ($is_active && is_user_logged_in()) : ?>
									<button type="button" class="gridlywishlist-collection-visibility" data-collection-id="<?php echo esc_attr($collection['id']); ?>" data-public="<?php echo $is_public ? '1' : '0'; ?>" title="<?php echo $is_public ? esc_attr__('Make Private', 'gridlywishlist') : esc_attr__('Make Public', 'gridlywishlist'); ?>">
										<?php echo $is_public ? '🌐' : '🔒'; ?>
									</button>
								<?php endif; ?>
							</li>
						<?php endforeach; ?>
					</ul>
				</div>

				<?php 
				// Find active collection data for share info
				$active_col_data = null;
				foreach ($collections as $col) {
					if ($col['id'] === $current_collection_id) {
						$active_col_data = $col;
						break;
					}
				}

				if ($active_col_data && ! empty($active_col_data['is_public']) && is_user_logged_in()) : 
					$share_url = add_query_arg('share', $active_col_data['id'], gridlywishlist_get_wishlist_page_url());
					$wa_message = sprintf(__('Check out my wishlist: %1$s - %2$s', 'gridlywishlist'), $active_col_data['name'], $share_url);
					$wa_url = 'https://wa.me/?text=' . rawurlencode($wa_message);
				?>
					<div class="gridlywishlist-collection-share-panel">
						<div class="gridlywishlist-share-label"><?php esc_html_e('Public Link:', 'gridlywishlist'); ?></div>
						<div class="gridlywishlist-share-controls">
							<input type="text" readonly value="<?php echo esc_url($share_url); ?>" class="gridlywishlist-share-url-input" />
							<button type="button" class="gridlywishlist-copy-share-url" data-url="<?php echo esc_url($share_url); ?>"><?php esc_html_e('Copy Link', 'gridlywishlist'); ?></button>
							<a href="<?php echo esc_url($wa_url); ?>" class="gridlywishlist-whatsapp-share" target="_blank">
								<span>WA</span> <?php esc_html_e('WhatsApp', 'gridlywishlist'); ?>
							</a>
						</div>
					</div>
				<?php endif; ?>
			</div>
			<div class="gridlywishlist-table-wrapper" data-empty-message="<?php echo esc_attr($empty_message); ?>" data-collection-id="<?php echo esc_attr($current_collection_id); ?>">
				<div class="gridlywishlist-bulk-toolbar">
					<div class="gridlywishlist-bulk-actions">
						<label class="gridlywishlist-bulk-select-all-wrapper">
							<input type="checkbox" class="gridlywishlist-bulk-select-all" />
							<span><?php esc_html_e('Select All', 'gridlywishlist'); ?></span>
						</label>
						<button type="button" class="button gridlywishlist-bulk-remove" disabled>
							<?php esc_html_e('Remove Selected', 'gridlywishlist'); ?>
						</button>
					</div>
					<div class="gridlywishlist-bulk-controls">
						<?php if (! empty($collections)) : ?>
							<div class="gridlywishlist-bulk-move">
								<select class="gridlywishlist-bulk-move-target">
									<option value=""><?php esc_html_e('Move to…', 'gridlywishlist'); ?></option>
									<?php foreach ($collections as $collection) : ?>
										<option value="<?php echo esc_attr($collection['id']); ?>"><?php echo esc_html($collection['name']); ?></option>
									<?php endforeach; ?>
								</select>
								<button type="button" class="button gridlywishlist-bulk-move-button" disabled><?php esc_html_e('Move Selected', 'gridlywishlist'); ?></button>
							</div>
						<?php endif; ?>
					</div>
				</div>
				<div class="gridlywishlist-table-responsive">
					<table class="gridlywishlist-table">
					<thead>
						<tr>
							<th class="gridlywishlist-col-checkbox">
								<input type="checkbox" class="gridlywishlist-bulk-select-all" />
							</th>
							<th><?php esc_html_e('Product', 'gridlywishlist'); ?></th>
							<th><?php esc_html_e('Price', 'gridlywishlist'); ?></th>
							<th><?php esc_html_e('Stock Status', 'gridlywishlist'); ?></th>
							<th><?php esc_html_e('Collection', 'gridlywishlist'); ?></th>
							<th class="gridlywishlist-col-actions"><?php esc_html_e('Actions', 'gridlywishlist'); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php
						while ($products->have_posts()) :
							$products->the_post();
							global $product;
							if (! $product) {
								continue;
							}
							$product_id = absint($product->get_id());
							$current_collection_for_product = '';
							$collection_name = '';
							if (is_user_logged_in()) {
								$current_collection_for_product = gridlywishlist_get_collection_for_product(get_current_user_id(), $product_id);
								if ($current_collection_for_product && ! empty($collections)) {
									foreach ($collections as $collection) {
										if ($collection['id'] === $current_collection_for_product) {
											$collection_name = $collection['name'];
											break;
										}
									}
								}
							}
						?>
							<tr data-product-id="<?php echo esc_attr($product_id); ?>" data-collection-id="<?php echo esc_attr($current_collection_for_product); ?>">
								<td class="gridlywishlist-col-checkbox">
									<input type="checkbox" class="gridlywishlist-bulk-checkbox" value="<?php echo esc_attr($product_id); ?>" />
								</td>
								<td class="gridlywishlist-table__product" data-title="<?php esc_attr_e('Product', 'gridlywishlist'); ?>">
									<a href="<?php the_permalink(); ?>">
										<?php echo wp_kses_post($product->get_image('thumbnail')); ?>
										<span class="gridlywishlist-table__title"><?php echo esc_html(get_the_title()); ?></span>
									</a>
								</td>
								<td class="gridlywishlist-table__price" data-title="<?php esc_attr_e('Price', 'gridlywishlist'); ?>">
									<?php echo wp_kses_post($product->get_price_html()); ?>
								</td>
								<td class="gridlywishlist-table__stock" data-title="<?php esc_attr_e('Stock Status', 'gridlywishlist'); ?>">
									<?php echo wp_kses_post($product->get_stock_status()); ?>
								</td>
								<td class="gridlywishlist-table__collection" data-title="<?php esc_attr_e('Collection', 'gridlywishlist'); ?>"><?php echo esc_html($collection_name); ?></td>
								<td class="gridlywishlist-table__actions" data-title="<?php esc_attr_e('Actions', 'gridlywishlist'); ?>">
									<?php woocommerce_template_loop_add_to_cart(); ?>
									<?php echo do_shortcode('[gridlywishlist_button product_id="' . $product_id . '"]'); ?>
								</td>
							</tr>
						<?php
						endwhile;
						?>
					</tbody>
				</table>
				</div>
			</div>
		<?php
		}

		/**
		 * Render empty state message.
		 */
		private function render_empty_message()
		{
			echo '<p class="gridlywishlist-empty-message">' . esc_html__('No wishlist products found.', 'gridlywishlist') . '</p>';
		}

		/**
		 * Render modal template for success messages.
		 */
		public function render_modal_template()
		{
			if ('yes' !== gridlywishlist_setting('enabled', 'yes')) {
				return;
			}

			$wishlist_url = gridlywishlist_get_wishlist_page_url();
?>
			<div id="gridlywishlist-modal" class="gridlywishlist-modal" aria-hidden="true" data-view="message">
				<div class="gridlywishlist-modal__backdrop" data-gridlywishlist-close></div>
				<div class="gridlywishlist-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="gridlywishlist-modal-message">
					<div class="gridlywishlist-modal__content">
						<div class="gridlywishlist-modal__view gridlywishlist-modal__view--message">
							<p id="gridlywishlist-modal-message" class="gridlywishlist-modal__message"></p>
							<div class="gridlywishlist-modal__actions">
								<a href="<?php echo esc_url($wishlist_url); ?>" class="button gridlywishlist-modal__view-link"><?php esc_html_e('View Wishlist', 'gridlywishlist'); ?></a>
								<button type="button" class="button gridlywishlist-modal__close" data-gridlywishlist-close><?php esc_html_e('Close', 'gridlywishlist'); ?></button>
							</div>
						</div>
						<div class="gridlywishlist-modal__view gridlywishlist-modal__view--manage">
							<h3><?php esc_html_e('Simpan ke Koleksi', 'gridlywishlist'); ?></h3>
							<div class="gridlywishlist-modal__manage-content">
								<div class="gridlywishlist-collection-select-wrapper">
									<label class="gridlywishlist-collection-select-label">
										<span><?php esc_html_e('Pilih Koleksi', 'gridlywishlist'); ?></span>
										<select class="gridlywishlist-collection-select"></select>
									</label>
									<a href="#" class="gridlywishlist-collection-create-toggle">+ <?php esc_html_e('Koleksi Baru', 'gridlywishlist'); ?></a>
								</div>
								
								<div class="gridlywishlist-collection-create" style="display: none;">
									<h4><?php esc_html_e('Buat Koleksi Baru', 'gridlywishlist'); ?></h4>
									<input type="text" class="gridlywishlist-collection-name" placeholder="<?php esc_attr_e('Nama koleksi', 'gridlywishlist'); ?>" />
									<label class="gridlywishlist-collection-public-label">
										<input type="checkbox" class="gridlywishlist-collection-public" />
										<?php esc_html_e('Jadikan koleksi publik', 'gridlywishlist'); ?>
									</label>
									<div class="gridlywishlist-collection-create-actions">
										<button type="button" class="button button-primary gridlywishlist-collection-create-submit"><?php esc_html_e('Buat', 'gridlywishlist'); ?></button>
										<button type="button" class="button gridlywishlist-collection-create-cancel"><?php esc_html_e('Batal', 'gridlywishlist'); ?></button>
									</div>
								</div>
							</div>
							<div class="gridlywishlist-modal__manage-actions">
								<button type="button" class="button button-primary gridlywishlist-collection-apply"><?php esc_html_e('Simpan', 'gridlywishlist'); ?></button>
								<button type="button" class="button gridlywishlist-modal__close" data-gridlywishlist-close><?php esc_html_e('Batal', 'gridlywishlist'); ?></button>
							</div>
						</div>
					</div>
				</div>
			</div>
<?php
		}
	}

	new GridlyWishlist_Front();
}
