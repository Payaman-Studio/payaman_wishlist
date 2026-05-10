<?php

/**
 * Admin Page Payaman_Wishlist
 *
 * @package payaman_wishlist
 * @since 1.0.0
 * @version 1.0.0
 */
if (! defined('ABSPATH')) {
	exit;
}

if (! class_exists('Payaman_Wishlist_Admin_Page')) {

	/**
	 *
	 * Class for add admin page on wp-admin
	 */
	class Payaman_Wishlist_Admin_Page
	{

		/**
		 * Constructor.
		 *
		 * @version 1.0.0
		 */
		public function __construct()
		{
			add_action('admin_menu', array($this, 'payaman_wishlist_admin_menu'));
			add_action('admin_init', array($this, 'payaman_wishlist_save_settings'));
		}

		/**
		 * Create Menu Payaman_Wishlist Page
		 *
		 * @version 1.0.0
		 * @since 1.0.0
		 */
		public function payaman_wishlist_admin_menu()
		{
			$parent_slug = 'payamanstudio';

			// Register the Payaman Studio container menu when needed so other plugins can hook into it.
			if (! isset($GLOBALS['admin_page_hooks'][$parent_slug])) {
				add_menu_page(
					__('Payaman Studio', 'payaman_wishlist'),
					__('Payaman Studio', 'payaman_wishlist'),
					'manage_options',
					$parent_slug,
					array($this, 'render_payamanstudio_dashboard'),
					PAYAMAN_WISHLIST_URL . '/assets/images/icon_payaman.svg',
					58
				);
			}

			add_submenu_page(
				$parent_slug,
				'Payaman Wishlist',
				'Payaman Wishlist',
				'manage_options',
				'payaman_wishlist',
				array($this, 'payaman_wishlist_handler')
			);
		}

		/**
		 * Callback payaman_wishlist admin page untuk view
		 *
		 * @version 1.0.0
		 * @since 1.0.0
		 */
		public function payaman_wishlist_handler()
		{
			$payaman_wishlist_image_val_off = payaman_wishlist_setting('image_val_off');
			$payaman_wishlist_image_val_on  = payaman_wishlist_setting('image_val_on');

			wp_enqueue_media();

			include_once PAYAMAN_WISHLIST_PATH . 'views/admin/page-setting.php';
		}

		/**
		 * Handle save setting payaman_wishlist action
		 *
		 * @return [type] [description]
		 */
		public function payaman_wishlist_save_settings()
		{
			if (isset($_POST['payaman_wishlist_field_setting']) && wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['payaman_wishlist_field_setting'])), 'payaman_wishlist_action_setting')) {
				$post_data = wp_unslash($_POST);
				$display_on = isset($post_data['display_on']) ? array_map('sanitize_text_field', (array) $post_data['display_on']) : array();

				$payaman_wishlist_settings = array(
					'enabled'                       => isset($post_data['payaman_wishlist_enabled']) ? 'yes' : 'no',
					'payaman_wishlist_count'        => isset($post_data['payaman_wishlist_count']) ? 'yes' : 'no',
					'required_login'                => isset($post_data['payaman_wishlist_required_login']) ? 'yes' : 'no',
					'display_position_button'       => isset($post_data['display_position_button']) ? sanitize_text_field($post_data['display_position_button']) : '',
					'type_active'                   => isset($post_data['payaman_wishlist_type']) ? sanitize_text_field($post_data['payaman_wishlist_type']) : 'text',
					'display_on'                    => array_values(array_unique($display_on)),
					'enable_add_success_message'    => isset($post_data['enable_add_success_message']) ? 'yes' : 'no',
					'enable_remove_success_message' => isset($post_data['enable_remove_success_message']) ? 'yes' : 'no',
					'enable_price_drop_alert'       => isset($post_data['enable_price_drop_alert']) ? 'yes' : 'no',
					'enable_stock_alert'            => isset($post_data['enable_stock_alert']) ? 'yes' : 'no',
					'remove_after_add_to_cart'      => isset($post_data['payaman_wishlist_remove_after_add_to_cart']) ? 'yes' : 'no',
					'button'                        => array(
						'text'  => array(
							'val_on'  => isset($post_data['payaman_wishlist_text_val_on']) ? sanitize_text_field($post_data['payaman_wishlist_text_val_on']) : '',
							'val_off' => isset($post_data['payaman_wishlist_text_val_off']) ? sanitize_text_field($post_data['payaman_wishlist_text_val_off']) : '',
						),
						'image' => array(
							'val_on'  => isset($post_data['payaman_wishlist_image_val_on']) ? absint($post_data['payaman_wishlist_image_val_on']) : '',
							'val_off' => isset($post_data['payaman_wishlist_image_val_off']) ? absint($post_data['payaman_wishlist_image_val_off']) : '',
						),
					),
					'messages'                   => array(
						'add_success_message'    => isset($post_data['add_success_message']) ? sanitize_text_field($post_data['add_success_message']) : '',
						'remove_success_message' => isset($post_data['remove_success_message']) ? sanitize_text_field($post_data['remove_success_message']) : '',
						'required_login_message' => isset($post_data['required_login_message']) ? sanitize_text_field($post_data['required_login_message']) : '',
					),
				);

				update_option('payaman_wishlist_settings', $payaman_wishlist_settings);
				payaman_wishlist_get_settings(true);
			}
		}

		/**
		 * Default Payaman Studio dashboard screen.
		 */
		public function render_payamanstudio_dashboard()
		{
			echo '<div class="wrap payamanstudio-admin"><h1>' . esc_html__('Payaman Studio', 'payaman_wishlist') . '</h1><p>' . esc_html__('Select a plugin from the submenu to manage its settings.', 'payaman_wishlist') . '</p></div>';
		}
	}

	new Payaman_Wishlist_Admin_Page();
}
