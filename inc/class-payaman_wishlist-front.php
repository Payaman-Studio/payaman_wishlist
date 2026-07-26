<?php

/**
 * Front Page Payaman_Wishlist
 *
 * @package payaman_wishlist
 * @since 1.0.0
 * @version 1.0.0
 */
if (! defined('ABSPATH')) {
	exit;
}

if (! class_exists('Payaman_Wishlist_Front')) {

	/**
	 *
	 * Class for add admin page on wp-admin
	 */
	class Payaman_Wishlist_Front
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

			if ('yes' !== payaman_wishlist_setting('enabled', 'yes')) {
				return;
			}

			add_shortcode('payaman_wishlist_button', array($this, 'shortcode'));
			add_shortcode('payaman_wishlist_list', array($this, 'wishlist_list'));
			add_action('wp_enqueue_scripts', array($this, 'wp_enqueue_scripts'));
			add_action('wp_footer', array($this, 'render_modal_template'));
			add_action('woocommerce_add_to_cart', array($this, 'handle_add_to_cart'), 10, 6);
			$this->set_default_payaman_wishlist_button();
		}

		/**
		 * Handle auto removal from wishlist when product added to cart.
		 */
		public function handle_add_to_cart($cart_item_key, $product_id, $quantity, $variation_id, $variation, $cart_item_data)
		{
			if (payaman_wishlist_setting('remove_after_add_to_cart') !== 'yes') {
				return;
			}

			$user_id = get_current_user_id();

			// For Logged in users
			if ($user_id) {
				// Remove from ALL collections for this user
				global $wpdb;
				$items_table = payaman_wishlist_get_table_name('items');
				$collections_table = payaman_wishlist_get_table_name('collections');

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
				$wishlists = payaman_wishlist_get_wishlists($product_id);
				$user_key  = (string) $user_id;
				if (in_array($user_key, $wishlists, true)) {
					$wishlists = array_values(array_filter($wishlists, function($w) use ($user_key) { return $w !== $user_key; }));
					payaman_wishlist_store_wishlists($product_id, $wishlists);
				}
			} else {
				// For Guests (Cookie based)
				if (isset($_COOKIE['payaman_wishlist_product'])) {
					$cookie_data = json_decode(wp_unslash($_COOKIE['payaman_wishlist_product']), true);
					if (is_array($cookie_data)) {
						$product_id_str = (string) $product_id;
						if (($key = array_search($product_id_str, $cookie_data)) !== false) {
							unset($cookie_data[$key]);
							$cookie_data = array_values($cookie_data);
							setcookie('payaman_wishlist_product', wp_json_encode($cookie_data), time() + (30 * DAY_IN_SECONDS), '/');
						}
					}
				}

				// Also sync to legacy post meta using IP
				$ip = payaman_wishlist_client_ip();
				$wishlists = payaman_wishlist_get_wishlists($product_id);
				if (in_array($ip, $wishlists, true)) {
					$wishlists = array_values(array_filter($wishlists, function($w) use ($ip) { return $w !== $ip; }));
					payaman_wishlist_store_wishlists($product_id, $wishlists);
				}
			}
		}

		/**
		 * Set default payaman_wishlist button set
		 */
		public function set_default_payaman_wishlist_button()
		{
			$display_on = (array) payaman_wishlist_setting('display_on', array());
			$position_config = $this->get_button_position_config();

			if (in_array('single_product', $display_on, true)) {
				$single_hook = $position_config['single'];
				if (! empty($single_hook['hook'])) {
					add_action($single_hook['hook'], array($this, 'payaman_wishlist_add_button'), isset($single_hook['priority']) ? $single_hook['priority'] : 10);
				}
			}
			if (in_array('loop_product', $display_on, true)) {
				$loop_hook = $position_config['loop'];
				if (! empty($loop_hook['hook'])) {
					add_action($loop_hook['hook'], array($this, 'payaman_wishlist_add_button'), isset($loop_hook['priority']) ? $loop_hook['priority'] : 10);
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
			$position = payaman_wishlist_setting('display_position_button', 'after_add_to_cart');

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
			$has_shortcode = $post instanceof WP_Post ? has_shortcode($post->post_content, 'payaman_wishlist_list') : false;
			$should_enqueue = is_woocommerce() || is_cart() || $has_shortcode;
			$should_enqueue = apply_filters('payaman_wishlist_allow_enqueue_scripts', $should_enqueue, $post);

			if (! $should_enqueue || 'yes' !== payaman_wishlist_setting('enabled', 'yes')) {
				return;
			}

			$button_type = payaman_wishlist_setting('type_active', 'text');

			if ('text' === $button_type) {
				$on_val  = payaman_wishlist_setting('val_on');
				$off_val = payaman_wishlist_setting('val_off');
			} else {
				$on_img_src  = wp_get_attachment_image_src(payaman_wishlist_setting('image_val_on'), 'thumbnail', false);
				$off_img_src = wp_get_attachment_image_src(payaman_wishlist_setting('image_val_off'), 'thumbnail', false);
				$on_val      = ! empty($on_img_src[0]) ? $on_img_src[0] : '';
				$off_val     = ! empty($off_img_src[0]) ? $off_img_src[0] : '';
			}

			$collections_response = array();
			$default_collection_id = '';
			$can_manage_collections = is_user_logged_in();
			if ($can_manage_collections) {
				$current_user_id = get_current_user_id();
				$collections_response = payaman_wishlist_prepare_collections_response($current_user_id);
				$default_collection_id = payaman_wishlist_get_default_collection_id($current_user_id);
			}

			$payaman_wishlist_object = array(
				'ajax_url'                      => admin_url('admin-ajax.php'),
				'nonce'                         => wp_create_nonce('payaman_wishlist_toggle'),
				'nonce_bulk'                    => wp_create_nonce('payaman_wishlist_bulk'),
				'nonce_collection'              => wp_create_nonce('payaman_wishlist_collection'),
				'button_type'                   => $button_type,
				'required_login'                => payaman_wishlist_setting('required_login', 'no'),
				'is_login'                      => is_user_logged_in(),
				'on_val'                        => $on_val,
				'off_val'                       => $off_val,
				'payaman_wishlist_count'            => payaman_wishlist_setting('payaman_wishlist_count', 'no'),
				'enable_add_success_message'    => payaman_wishlist_setting('enable_add_success_message', 'no'),
				'enable_remove_success_message' => payaman_wishlist_setting('enable_remove_success_message', 'no'),
				'remove_after_add_to_cart'      => payaman_wishlist_setting('remove_after_add_to_cart', 'no'),
				'add_success_message'            => payaman_wishlist_setting('add_success'),
				'remove_success_message'         => payaman_wishlist_setting('remove_success'),
				'required_login_message'        => payaman_wishlist_setting('required_login_message', __('You must be logged in.', 'payaman_wishlist')),
				'error_message'                 => __('Unable to update wishlist. Please try again.', 'payaman_wishlist'),
				'wishlist_page_url'             => payaman_wishlist_get_wishlist_page_url(),
				'cart_url'                      => wc_get_cart_url(),
				'collections'                   => $collections_response,
				'default_collection_id'         => $default_collection_id,
				'collection_limit'              => PAYAMAN_WISHLIST_COLLECTION_LIMIT,
				'can_manage_collections'        => $can_manage_collections,
				// i18n strings
				'i18n'                          => array(
					'fill_collection_name'    => __('Please fill in the collection name.', 'payaman_wishlist'),
					'select_collection_first' => __('Please select a collection first.', 'payaman_wishlist'),
					'collection_limit_reached' => __('Collection limit has been reached.', 'payaman_wishlist'),
					'generic_error'           => __('An error occurred. Please try again.', 'payaman_wishlist'),
					'no_collections_yet'      => __('No collections yet', 'payaman_wishlist'),
					'ok'                      => __('OK', 'payaman_wishlist'),
					'cancel'                  => __('Cancel', 'payaman_wishlist'),
					'yes'                     => __('Yes', 'payaman_wishlist'),
					'close'                   => __('Close', 'payaman_wishlist'),
					'view_wishlist'           => __('View Wishlist', 'payaman_wishlist'),
					'confirm_delete_collection' => __('Are you sure you want to delete this collection? Products will be moved to the default collection.', 'payaman_wishlist'),
					'rename_collection'       => __('Enter new name:', 'payaman_wishlist'),
					'add_all_to_cart'         => __('Add All to Cart', 'payaman_wishlist'),
					'adding_to_cart'          => __('Adding…', 'payaman_wishlist'),
					'view_cart'               => __('View Cart', 'payaman_wishlist'),
					'move_success'            => __('Items moved successfully.', 'payaman_wishlist'),
					'no_products_in_wishlist' => __('No products in this collection.', 'payaman_wishlist'),
				),
			);

			wp_enqueue_style('payaman_wishlist-style', PAYAMAN_WISHLIST_URL . '/assets/css/payaman_wishlist-style.css', array(), PAYAMAN_WISHLIST_VERSION);
			wp_enqueue_script('payaman_wishlist-script', PAYAMAN_WISHLIST_URL . '/assets/js/payaman_wishlist-script.js', array('jquery', 'js-cookie'), PAYAMAN_WISHLIST_VERSION, true);
			wp_localize_script('payaman_wishlist-script', 'payaman_wishlist_object', $payaman_wishlist_object);
		}

		/**
		 * Create shortcode to dispaly payaman_wishlist button
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
			$attr     = shortcode_atts($defaults, $attr, 'payaman_wishlist_button');

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
			$position_class = 'payaman_wishlist-position-' . sanitize_html_class(payaman_wishlist_setting('display_position_button', 'after_add_to_cart'));
			$wrapper_class  = trim($class . ' ' . $position_class);
			$icon_wishlist = 'off';
			$cookie_payaman_wishlist   = array();
			$current_collection_id = '';

			if (isset($_COOKIE['payaman_wishlist_product'])) {
				$cookie_payaman_wishlist = json_decode(wp_unslash($_COOKIE['payaman_wishlist_product']), true);
				$cookie_payaman_wishlist = is_array($cookie_payaman_wishlist) ? array_map('absint', $cookie_payaman_wishlist) : array();
			}

			if (is_user_logged_in() && $this->get_wishlist_by_user($product_id, get_current_user_id())) {
				$icon_wishlist = 'on';
				$current_collection_id = payaman_wishlist_get_collection_for_product(get_current_user_id(), $product_id);
			} elseif (in_array($product_id, $cookie_payaman_wishlist, true)) {
				$icon_wishlist = 'on';
			}

			$button_type = payaman_wishlist_setting('type_active', 'text');
			$payaman_wishlist_count  = '';

			if ('yes' === payaman_wishlist_setting('payaman_wishlist_count', 'no')) {
				$count_payaman_wishlist = (int) get_payaman_wishlist($product_id);
				if ('text' === $button_type) {
					$payaman_wishlist_count = ' (' . esc_html($count_payaman_wishlist) . ')';
				} else {
					$payaman_wishlist_count = '<span class="count">' . esc_html($count_payaman_wishlist) . '</span>';
				}
			}

			$component  = '<span class="payaman_wishlist ' . esc_attr($wrapper_class) . '" data-product-id="' . esc_attr($product_id) . '" data-collection-id="' . esc_attr($current_collection_id) . '" data-variation-id="">';
			$component .= '<img src="' . esc_url(PAYAMAN_WISHLIST_URL . '/assets/images/loading.gif') . '" class="payaman_wishlist-loading" data-product-id="' . esc_attr($product_id) . '" />';

			if ('text' === $button_type) {
				$button_label = trim(payaman_wishlist_setting('val_' . $icon_wishlist) . ' ' . $payaman_wishlist_count);
				$component   .= '<button type="button" class="payaman_wishlist-button ' . esc_attr($icon_wishlist) . '" data-product-id="' . esc_attr($product_id) . '" data-collection-id="' . esc_attr($current_collection_id) . '" data-variation-id="">' . esc_html($button_label) . '</button>';
			} else {
				$on_img_src  = wp_get_attachment_image_src(payaman_wishlist_setting('image_val_on'), 'thumbnail', false);
				$off_img_src = wp_get_attachment_image_src(payaman_wishlist_setting('image_val_off'), 'thumbnail', false);
				$on_val      = ! empty($on_img_src[0]) ? $on_img_src[0] : '';
				$off_val     = ! empty($off_img_src[0]) ? $off_img_src[0] : '';

				$payaman_wishlist_class  = 'on' === $icon_wishlist ? $on_val : $off_val;
				$component  .= '<span class="payaman_wishlist_button_icon">';
				$component  .= '<img src="' . esc_url($payaman_wishlist_class) . '" class="payaman_wishlist-button ' . esc_attr($icon_wishlist) . '" data-product-id="' . esc_attr($product_id) . '" data-collection-id="' . esc_attr($current_collection_id) . '" data-variation-id="" />';
				$component  .= $payaman_wishlist_count;
				$component  .= '</span>';
			}

			$component .= '</span>';

			return $component;
		}

		/**
		 * get payaman_wishlist by user_id
		 *
		 * @param int $product_id ID Product
		 * @param int $user_id ID User Current Login
		 *
		 * @version 1.0.0
		 */
		public function get_wishlist_by_user($product_id, $user_id)
		{
			$product_id = absint($product_id);
			$user_id    = absint($user_id);

			if (! $product_id || ! $user_id) {
				return 0;
			}

			if (payaman_wishlist_user_has_product($user_id, $product_id)) {
				return 1;
			}

			$wishlists = payaman_wishlist_get_wishlists($product_id);

			return in_array((string) $user_id, $wishlists, true) ? 1 : 0;
		}

		/**
		 * Get all product wishlist
		 *
		 * @return [type] [description]
		 */
		public function get_wishlist_product_by_user($user_id, $collection_id = '')
		{
			return payaman_wishlist_get_user_wishlists_list($user_id, $collection_id);
		}

		public function payaman_wishlist_add_button()
		{
			echo do_shortcode('[payaman_wishlist_button]');
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
				'payaman_wishlist_list'
			);

			$active_collection = isset($_GET['collection']) ? sanitize_text_field(wp_unslash($_GET['collection'])) : sanitize_text_field($atts['collection']);

			if (is_user_logged_in() && empty($active_collection)) {
				$active_collection = payaman_wishlist_get_default_collection_id(get_current_user_id());
			}

			$per_page = PAYAMAN_WISHLIST_ITEMS_PER_PAGE;
			$paged    = isset($_GET['paged']) ? max(1, absint($_GET['paged'])) : 1;
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended

			$list_wishlist = array();

			// Handle ?share={slug}
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$share_slug = isset($_GET['share']) ? sanitize_text_field(wp_unslash($_GET['share'])) : '';
			if ($share_slug) {
				$public_collection = payaman_wishlist_get_public_collection($share_slug);
				if (! $public_collection) {
					echo '<p class="payaman_wishlist-empty-message">' . esc_html__('This wishlist collection is not available or is set to private.', 'payaman_wishlist') . '</p>';
					return;
				}
				$list_wishlist = $public_collection['product_ids'];
				$list_wishlist = array_values(array_unique(array_filter($list_wishlist)));
				if (! empty($list_wishlist)) {
					$total      = count($list_wishlist);
					$offset     = ($paged - 1) * $per_page;
					$page_ids   = array_slice($list_wishlist, $offset, $per_page);
					$args = array(
						'post_type'           => 'product',
						'posts_per_page'      => $per_page,
						'post_status'         => 'publish',
						'post__in'            => $page_ids,
						'orderby'             => 'post__in',
						'ignore_sticky_posts' => true,
					);
					$products = new WP_Query($args);
					if ($products->have_posts()) {
						echo '<p class="payaman_wishlist-shared-notice">'
							. sprintf(
								esc_html__('Shared collection: %s', 'payaman_wishlist'),
								'<strong>' . esc_html($public_collection['name']) . '</strong>'
							)
							. '</p>';
						$this->render_wishlist_table($products, $active_collection);
						$this->render_pagination($total, $per_page, $paged);
						wp_reset_postdata();
					} else {
						$this->render_empty_message();
					}
				} else {
					$this->render_empty_message();
				}
				return;
			}

			if ('yes' === payaman_wishlist_setting('required_login', 'no') && ! is_user_logged_in()) {
				echo apply_filters('woocommerce_checkout_must_be_logged_in_message', __('You must be logged in.', 'woocommerce'));
				return;
			}

			if (is_user_logged_in()) {
				$list_wishlist = $this->get_wishlist_product_by_user(get_current_user_id(), $active_collection);
			} elseif (isset($_COOKIE['payaman_wishlist_product'])) {
				$cookie_payaman_wishlist = json_decode(wp_unslash($_COOKIE['payaman_wishlist_product']), true);
				if (is_array($cookie_payaman_wishlist)) {
					$list_wishlist = array_map('absint', $cookie_payaman_wishlist);
				}
			}

			$list_wishlist = array_values(array_unique(array_filter($list_wishlist)));
			$products = null;

			if (! empty($list_wishlist)) {
				$total    = count($list_wishlist);
				$offset   = ($paged - 1) * $per_page;
				$page_ids = array_slice($list_wishlist, $offset, $per_page);

				if (! empty($page_ids)) {
					$args = array(
						'post_type'           => 'product',
						'posts_per_page'      => $per_page,
						'post_status'         => 'publish',
						'post__in'            => $page_ids,
						'orderby'             => 'post__in',
						'ignore_sticky_posts' => true,
					);
					$products = new WP_Query($args);
				}
			}

			$this->render_wishlist_table($products, $active_collection);

			if (! empty($list_wishlist)) {
				$this->render_pagination(count($list_wishlist), $per_page, $paged);
			}

			if ($products) {
				wp_reset_postdata();
			}
		}

		/**
		 * Render pagination links.
		 *
		 * @param int $total
		 * @param int $per_page
		 * @param int $current
		 */
		private function render_pagination($total, $per_page, $current)
		{
			$total_pages = ceil($total / $per_page);
			if ($total_pages <= 1) {
				return;
			}

			$big   = 999999999;
			$args  = array(
				'base'      => str_replace($big, '%#%', esc_url(add_query_arg('paged', $big))),
				'format'    => '?paged=%#%',
				'current'   => $current,
				'total'     => $total_pages,
				'type'      => 'list',
				'prev_text' => '&laquo;',
				'next_text' => '&raquo;',
			);

			echo '<div class="payaman_wishlist-pagination">';
			echo paginate_links($args);
			echo '</div>';
		}


		/**
		 * Render wishlists table output.
		 *
		 * @param WP_Query|null $products
		 * @param string $active_collection
		 * @return void
		 */
		private function render_wishlist_table($products, $active_collection = '')
		{
			$empty_message = esc_html__('No wishlist products found.', 'payaman_wishlist');
			$collections = is_user_logged_in() ? payaman_wishlist_prepare_collections_response(get_current_user_id()) : array();
			$current_collection_id = $active_collection ? $active_collection : (is_user_logged_in() ? payaman_wishlist_get_default_collection_id(get_current_user_id()) : '');
?>
			<div class="payaman_wishlist-collections-container">
				<div class="payaman_wishlist-collections-header">
					<div class="payaman_wishlist-collections-nav">
						<ul class="payaman_wishlist-collection-tabs">
							<?php foreach ($collections as $collection) : 
								$is_active = $collection['id'] === $current_collection_id;
								$is_public = ! empty($collection['is_public']);
								$is_default = $collection['id'] === payaman_wishlist_get_default_collection_id(get_current_user_id());
							?>
								<li class="<?php echo $is_active ? 'is-active' : ''; ?>">
									<a href="<?php echo esc_url(add_query_arg('collection', $collection['id'])); ?>" class="payaman_wishlist-collection-link <?php echo $is_active ? 'active' : ''; ?>" data-collection-id="<?php echo esc_attr($collection['id']); ?>">
										<?php echo esc_html($collection['name']); ?> 
										<span class="payaman_wishlist-tab-count">(<?php echo esc_html($collection['count']); ?>)</span>
									</a>
									<?php if ($is_active && is_user_logged_in()) : ?>
										<div class="payaman_wishlist-collection-quick-actions">
											<button type="button" class="payaman_wishlist-collection-rename" data-id="<?php echo esc_attr($collection['id']); ?>" data-name="<?php echo esc_attr($collection['name']); ?>" title="<?php esc_attr_e('Rename', 'payaman_wishlist'); ?>">✏️</button>
											<button type="button" class="payaman_wishlist-collection-visibility" data-collection-id="<?php echo esc_attr($collection['id']); ?>" data-public="<?php echo $is_public ? '1' : '0'; ?>" title="<?php echo $is_public ? esc_attr__('Make Private', 'payaman_wishlist') : esc_attr__('Make Public', 'payaman_wishlist'); ?>">
												<?php echo $is_public ? '🌐' : '🔒'; ?>
											</button>
											<?php if (! $is_default) : ?>
												<button type="button" class="payaman_wishlist-collection-delete" data-id="<?php echo esc_attr($collection['id']); ?>" title="<?php esc_attr_e('Delete', 'payaman_wishlist'); ?>">🗑️</button>
											<?php endif; ?>
										</div>
									<?php endif; ?>
								</li>
							<?php endforeach; ?>
							<?php if (is_user_logged_in()) : ?>
								<li class="payaman_wishlist-add-new-tab">
									<button type="button" class="payaman_wishlist-collection-add-new" title="<?php esc_attr_e('Add New Collection', 'payaman_wishlist'); ?>">+ <?php esc_html_e('New', 'payaman_wishlist'); ?></button>
								</li>
							<?php endif; ?>
						</ul>
					</div>
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
					$share_url = add_query_arg('share', $active_col_data['id'], payaman_wishlist_get_wishlist_page_url());
					$wa_message = sprintf(__('Check out my wishlist: %1$s - %2$s', 'payaman_wishlist'), $active_col_data['name'], $share_url);
					$wa_url = 'https://wa.me/?text=' . rawurlencode($wa_message);
				?>
					<div class="payaman_wishlist-collection-share-panel">
						<div class="payaman_wishlist-share-label"><?php esc_html_e('Public Link:', 'payaman_wishlist'); ?></div>
						<div class="payaman_wishlist-share-controls">
							<input type="text" readonly value="<?php echo esc_url($share_url); ?>" class="payaman_wishlist-share-url-input" />
							<button type="button" class="payaman_wishlist-copy-share-url" data-url="<?php echo esc_url($share_url); ?>"><?php esc_html_e('Copy Link', 'payaman_wishlist'); ?></button>
							<a href="<?php echo esc_url($wa_url); ?>" class="payaman_wishlist-whatsapp-share" target="_blank">
								<span>WA</span> <?php esc_html_e('WhatsApp', 'payaman_wishlist'); ?>
							</a>
						</div>
					</div>
				<?php endif; ?>
			</div>
			<div class="payaman_wishlist-table-wrapper" data-empty-message="<?php echo esc_attr($empty_message); ?>" data-collection-id="<?php echo esc_attr($current_collection_id); ?>">
				<?php if ($products && $products->have_posts()) : ?>
					<div class="payaman_wishlist-bulk-toolbar">
						<div class="payaman_wishlist-bulk-actions">
							<label class="payaman_wishlist-bulk-select-all-wrapper">
								<input type="checkbox" class="payaman_wishlist-bulk-select-all" />
								<span><?php esc_html_e('Select All', 'payaman_wishlist'); ?></span>
							</label>
							<button type="button" class="button payaman_wishlist-bulk-remove" disabled>
								<?php esc_html_e('Remove Selected', 'payaman_wishlist'); ?>
							</button>
						</div>
						<div class="payaman_wishlist-bulk-controls">
							<button type="button" class="button button-primary payaman_wishlist-add-all-cart">
								<?php esc_html_e('Add All to Cart', 'payaman_wishlist'); ?>
							</button>
							<?php if (! empty($collections)) : ?>
								<div class="payaman_wishlist-bulk-move">
									<select class="payaman_wishlist-bulk-move-target">
										<option value=""><?php esc_html_e('Move to…', 'payaman_wishlist'); ?></option>
										<?php foreach ($collections as $collection) : ?>
											<option value="<?php echo esc_attr($collection['id']); ?>"><?php echo esc_html($collection['name']); ?></option>
										<?php endforeach; ?>
									</select>
									<button type="button" class="button payaman_wishlist-bulk-move-button" disabled><?php esc_html_e('Move Selected', 'payaman_wishlist'); ?></button>
								</div>
							<?php endif; ?>
						</div>
					</div>
					<div class="payaman_wishlist-cart-notice" style="display:none;"></div>
					<div class="payaman_wishlist-table-responsive">
						<table class="payaman_wishlist-table">
						<thead>
							<tr>
								<th class="payaman_wishlist-col-checkbox">
									<input type="checkbox" class="payaman_wishlist-bulk-select-all" />
								</th>
								<th><?php esc_html_e('Product', 'payaman_wishlist'); ?></th>
								<th><?php esc_html_e('Price', 'payaman_wishlist'); ?></th>
								<th><?php esc_html_e('Stock Status', 'payaman_wishlist'); ?></th>
								<th><?php esc_html_e('Collection', 'payaman_wishlist'); ?></th>
								<th class="payaman_wishlist-col-actions"><?php esc_html_e('Actions', 'payaman_wishlist'); ?></th>
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
									$current_collection_for_product = payaman_wishlist_get_collection_for_product(get_current_user_id(), $product_id);
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
									<td class="payaman_wishlist-col-checkbox">
										<input type="checkbox" class="payaman_wishlist-bulk-checkbox" value="<?php echo esc_attr($product_id); ?>" />
									</td>
									<td class="payaman_wishlist-table__product" data-title="<?php esc_attr_e('Product', 'payaman_wishlist'); ?>">
										<a href="<?php the_permalink(); ?>">
											<?php echo wp_kses_post($product->get_image('thumbnail')); ?>
											<span class="payaman_wishlist-table__title"><?php echo esc_html(get_the_title()); ?></span>
										</a>
									</td>
									<td class="payaman_wishlist-table__price" data-title="<?php esc_attr_e('Price', 'payaman_wishlist'); ?>">
										<?php echo wp_kses_post($product->get_price_html()); ?>
									</td>
									<td class="payaman_wishlist-table__stock" data-title="<?php esc_attr_e('Stock Status', 'payaman_wishlist'); ?>">
										<?php echo $product->is_in_stock() ? '<span class="payaman_wishlist-in-stock">' . esc_html__('In Stock', 'payaman_wishlist') . '</span>' : '<span class="payaman_wishlist-out-of-stock">' . esc_html__('Out of Stock', 'payaman_wishlist') . '</span>'; ?>
									</td>
									<td class="payaman_wishlist-table__collection" data-title="<?php esc_attr_e('Collection', 'payaman_wishlist'); ?>">
										<?php echo esc_html($collection_name); ?>
									</td>
									<td class="payaman_wishlist-table__actions" data-title="<?php esc_attr_e('Actions', 'payaman_wishlist'); ?>">
										<?php if ($product->is_type('simple')) : ?>
											<a href="<?php echo esc_url($product->add_to_cart_url()); ?>" data-quantity="1" class="button product_type_simple add_to_cart_button ajax_add_to_cart" data-product_id="<?php echo esc_attr($product_id); ?>" rel="nofollow"><?php esc_html_e('Add to Cart', 'payaman_wishlist'); ?></a>
										<?php else : ?>
											<a href="<?php the_permalink(); ?>" class="button"><?php esc_html_e('Select Options', 'payaman_wishlist'); ?></a>
										<?php endif; ?>
										<button type="button" class="payaman_wishlist-remove-item" data-product-id="<?php echo esc_attr($product_id); ?>" data-collection-id="<?php echo esc_attr($current_collection_id); ?>"><?php esc_html_e('Remove', 'payaman_wishlist'); ?></button>
									</td>
								</tr>
							<?php endwhile; ?>
						</tbody>
						</table>
					</div>
				<?php else : ?>
					<?php $this->render_empty_message(); ?>
				<?php endif; ?>
			</div>
		<?php
		}

		/**
		 * Render empty state message.
		 */
		private function render_empty_message()
		{
			echo '<p class="payaman_wishlist-empty-message">' . esc_html__('No wishlist products found.', 'payaman_wishlist') . '</p>';
		}

		/**
		 * Render modal template for success messages.
		 */
		public function render_modal_template()
		{
			if ('yes' !== payaman_wishlist_setting('enabled', 'yes')) {
				return;
			}

			$wishlist_url = payaman_wishlist_get_wishlist_page_url();
?>
			<div id="payaman_wishlist-toast" class="payaman_wishlist-toast" aria-live="polite"></div>
			
			<div id="payaman_wishlist-modal" class="payaman_wishlist-modal" aria-hidden="true" data-view="message">
				<div class="payaman_wishlist-modal__backdrop"></div>
				<div class="payaman_wishlist-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="payaman_wishlist-modal-message">
					<div class="payaman_wishlist-modal__content">
						<div class="payaman_wishlist-modal__view payaman_wishlist-modal__view--message">
							<p id="payaman_wishlist-modal-message" class="payaman_wishlist-modal__message"></p>
							<div class="payaman_wishlist-modal__actions"></div>
						</div>
						<div class="payaman_wishlist-modal__view payaman_wishlist-modal__view--confirm">
							<p class="payaman_wishlist-modal__message"></p>
							<div class="payaman_wishlist-modal__actions"></div>
						</div>
					</div>
				</div>
			</div>
<?php
		}
	}

	new Payaman_Wishlist_Front();
}
