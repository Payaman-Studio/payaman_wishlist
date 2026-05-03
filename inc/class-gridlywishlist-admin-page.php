<?php

/**
 * Admin Page GridlyWishlist
 *
 * @package gridlywishlist
 * @since 1.0.0
 * @version 1.0.0
 */
if (! defined('ABSPATH')) {
	exit;
}

if (! class_exists('GridlyWishlist_Admin_Page')) {

	/**
	 *
	 * Class for add admin page on wp-admin
	 */
	class GridlyWishlist_Admin_Page
	{

		/**
		 * Constructor.
		 *
		 * @version 1.0.0
		 */
		public function __construct()
		{
			add_action('admin_menu', array($this, 'gridlywishlist_admin_menu'));
			add_action('admin_init', array($this, 'gridlywishlist_save_settings'));
		}

		/**
		 * Create Menu GridlyWishlist Page
		 *
		 * @version 1.0.0
		 * @since 1.0.0
		 */
		public function gridlywishlist_admin_menu()
		{
			$parent_slug = 'gridlystudio';

			// Register the Gridly Studio container menu when needed so other plugins can hook into it.
			if (! isset($GLOBALS['admin_page_hooks'][$parent_slug])) {
				add_menu_page(
					__('Gridly Studio', 'gridlywishlist'),
					__('Gridly Studio', 'gridlywishlist'),
					'manage_options',
					$parent_slug,
					array($this, 'render_gridlystudio_dashboard'),
					GRIDLYWISHLIST_URL . '/assets/images/gridly_icon.svg',
					58
				);
			}

			add_submenu_page(
				$parent_slug,
				'Gridly Wishlist',
				'Gridly Wishlist',
				'manage_options',
				'gridlywishlist',
				array($this, 'gridlywishlist_handler')
			);
		}

		/**
		 * Callback gridlywishlist admin page untuk view
		 *
		 * @version 1.0.0
		 * @since 1.0.0
		 */
		public function gridlywishlist_handler()
		{
			$gridlywishlist_image_val_off = gridlywishlist_setting('image_val_off');
			$gridlywishlist_image_val_on  = gridlywishlist_setting('image_val_on');

			wp_enqueue_media();

			include_once GRIDLYWISHLIST_PATH . 'views/admin/page-setting.php';
		}

		/**
		 * Handle save setting gridlywishlist action
		 *
		 * @return [type] [description]
		 */
		public function gridlywishlist_save_settings()
		{
			if (isset($_POST['gridlywishlist_field_setting']) && wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['gridlywishlist_field_setting'])), 'gridlywishlist_action_setting')) {
				$post_data = wp_unslash($_POST);
				$display_on = isset($post_data['display_on']) ? array_map('sanitize_text_field', (array) $post_data['display_on']) : array();

				$gridlywishlist_settings = array(
					'enabled'                       => isset($post_data['gridlywishlist_enabled']) ? 'yes' : 'no',
					'gridlywishlist_count'            => isset($post_data['gridlywishlist_count']) ? 'yes' : 'no',
					'required_login'                => isset($post_data['gridlywishlist_required_login']) ? 'yes' : 'no',
					'display_position_button'       => isset($post_data['display_position_button']) ? sanitize_text_field($post_data['display_position_button']) : '',
					'type_active'                   => isset($post_data['gridlywishlist_type']) ? sanitize_text_field($post_data['gridlywishlist_type']) : 'text',
					'display_on'                    => array_values(array_unique($display_on)),
					'enable_add_success_message'    => isset($post_data['enable_add_success_message']) ? 'yes' : 'no',
					'enable_remove_success_message' => isset($post_data['enable_remove_success_message']) ? 'yes' : 'no',
					'enable_price_drop_alert'       => isset($post_data['enable_price_drop_alert']) ? 'yes' : 'no',
					'enable_stock_alert'            => isset($post_data['enable_stock_alert']) ? 'yes' : 'no',
					'remove_after_add_to_cart'      => isset($post_data['gridlywishlist_remove_after_add_to_cart']) ? 'yes' : 'no',
					'button'                        => array(
						'text'  => array(
							'val_on'  => isset($post_data['gridlywishlist_text_val_on']) ? sanitize_text_field($post_data['gridlywishlist_text_val_on']) : '',
							'val_off' => isset($post_data['gridlywishlist_text_val_off']) ? sanitize_text_field($post_data['gridlywishlist_text_val_off']) : '',
						),
						'image' => array(
							'val_on'  => isset($post_data['gridlywishlist_image_val_on']) ? absint($post_data['gridlywishlist_image_val_on']) : '',
							'val_off' => isset($post_data['gridlywishlist_image_val_off']) ? absint($post_data['gridlywishlist_image_val_off']) : '',
						),
					),
					'messages'                      => array(
						'add_success_message'    => isset($post_data['add_success_message']) ? sanitize_text_field($post_data['add_success_message']) : '',
						'remove_success_message' => isset($post_data['remove_success_message']) ? sanitize_text_field($post_data['remove_success_message']) : '',
						'required_login_message' => isset($post_data['required_login_message']) ? sanitize_text_field($post_data['required_login_message']) : '',
					),
				);

				update_option('gridlywishlist_settings', $gridlywishlist_settings);
				gridlywishlist_get_settings(true);
			}
		}

		/**
		 * Default Gridly Studio dashboard screen.
		 */
		public function render_gridlystudio_dashboard()
		{
			echo '<div class="wrap gridlystudio-admin"><h1>' . esc_html__('Gridly Studio Plugins', 'gridlywishlist') . '</h1><p>' . esc_html__('Select a plugin from the submenu to manage its settings.', 'gridlywishlist') . '</p></div>';
		}
	}

	new GridlyWishlist_Admin_Page();
}
