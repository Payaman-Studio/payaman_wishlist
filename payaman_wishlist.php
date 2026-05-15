<?php

/**
 * Plugin Name: Payaman Wishlist
 * Plugin URI: https://payamanstudio.com/plugin/payaman-wishlist/
 * Description: Payaman Wishlist for WooCommerce
 * Version: 1.0.2
 * Author: Payaman Studio
 * Author URI: https://payamanstudio.com/
 * Text Domain: payaman_wishlist
 * Domain Path: /lang
 *
 * WC requires at least: 6.5.3
 * WC tested up to: 9.3.2
 * 
 * Requires Plugins: woocommerce
 *
 * Copyright: © 2018 payaman_wishlist.
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 */

// exit if accessed directly.
if (! defined('ABSPATH')) {
	exit;
}

/**
 * Declare compatibility with WooCommerce HPOS (High-Performance Order Storage).
 */
add_action(
	'before_woocommerce_init',
	function () {
		if (class_exists(\Automattic\WooCommerce\Utilities\FeaturesUtil::class)) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', __FILE__, true);
		}
	}
);

if (! class_exists('Payaman_Wishlist')) {

	/**
	 * Main Class Payaman_Wishlist
	 */
	class Payaman_Wishlist
	{

		public $helpers;

		/**
		 * Class constructor.
		 *
		 * @return void
		 */
		public function __construct()
		{

			if (! in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
				add_action(
					'admin_notices',
					function () {
						$class   = 'notice notice-error';
						$message = __('WooCommerce needs to be installed and activated to use "Payaman Wishlist"', 'payaman_wishlist');

						printf('<div class="%1$s"><p>%2$s</p></div>', esc_attr($class), esc_html($message));
					}
				);
				return;
			}

			register_activation_hook(__FILE__, array($this, 'payaman_wishlist_install'));

			$this->define_constants();
			$this->includes();

			add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'));
			add_filter('plugin_action_links_' . plugin_basename(__FILE__), array($this, 'plugin_add_settings_link'));
			add_action('plugins_loaded', array($this, 'maybe_upgrade'));
		}

		/**
		 * Setup plugin constants.
		 *
		 * @return void
		 */
		public function define_constants()
		{
			global $wpdb;
			define('PAYAMAN_WISHLIST_VERSION', '1.0.0');
			define('PAYAMAN_WISHLIST_URL', untrailingslashit(plugin_dir_url(__FILE__)));
			define('PAYAMAN_WISHLIST_LINK', plugin_dir_url(__FILE__));
			define('PAYAMAN_WISHLIST_PATH', plugin_dir_path(__FILE__));
			define('PAYAMAN_WISHLIST_REL_PATH', dirname(plugin_basename(__FILE__)) . '/');
			define('PAYAMAN_WISHLIST_DB_VERSION', '2.2.0');
			define('PAYAMAN_WISHLIST_TABLE_COLLECTIONS', $wpdb->prefix . 'payaman_wishlist_collections');
			define('PAYAMAN_WISHLIST_TABLE_ITEMS', $wpdb->prefix . 'payaman_wishlist_collection_items');
			define('PAYAMAN_WISHLIST_TABLE_CAMPAIGNS', $wpdb->prefix . 'payaman_wishlist_campaigns');
			if (! defined('PAYAMAN_WISHLIST_COLLECTION_LIMIT')) {
				define('PAYAMAN_WISHLIST_COLLECTION_LIMIT', 20);
			}
		}

		/**
		 * Include required files.
		 *
		 * @return void
		 */
		private function includes()
		{
			include_once PAYAMAN_WISHLIST_PATH . 'inc/helpers.php';
			include_once PAYAMAN_WISHLIST_PATH . 'inc/analytics-helpers.php';
			include_once PAYAMAN_WISHLIST_PATH . 'inc/class-payaman_wishlist-admin-page.php';
			include_once PAYAMAN_WISHLIST_PATH . 'inc/class-payaman_wishlist-front.php';
			include_once PAYAMAN_WISHLIST_PATH . 'inc/class-payaman_wishlist-alerts.php';
			include_once PAYAMAN_WISHLIST_PATH . 'inc/class-payaman_wishlist-ajax.php';
			include_once PAYAMAN_WISHLIST_PATH . 'inc/class-payaman_wishlist-campaigns.php';
		}

		/**
		 * Method for handle what first plugin doing after active payaman_wishlist plugin
		 *
		 * @return [type] [description]
		 */
		public function payaman_wishlist_install()
		{
			if (get_page_by_title('Wishlist', ARRAY_A, 'page') == null) {
				payaman_wishlist_create_page();
			}
			if (! get_option('payaman_wishlist_settings')) {
				payaman_wishlist_default_setting();
			}
			payaman_wishlist_maybe_install_tables();
			payaman_wishlist_maybe_run_migrations();
		}

		public function maybe_upgrade()
		{
			payaman_wishlist_maybe_install_tables();
			payaman_wishlist_maybe_run_migrations();
		}

		/**
		 * Add setting quick link to the plugins list
		 *
		 * @param  array $links Links.
		 * @return array $links Links
		 */
		public function plugin_add_settings_link($links)
		{
			$settings_link = '<a href="' . admin_url('admin.php?page=payaman_wishlist') . '">' . __('Settings', 'payaman_wishlist') . '</a>';
			array_push($links, $settings_link);
			return $links;
		}

		/**
		 * Enqueue admin scripts and styles.
		 *
		 * @param string $page
		 */
		public function admin_enqueue_scripts($page)
		{
			$payamanstudio_pages = array(
				'toplevel_page_payamanstudio',
				'payaman-studio_page_payaman_wishlist',
				'toplevel_page_payaman_wishlist',
				'payaman_wishlist_page_payaman_wishlist-settings',
			);

			if (in_array($page, $payamanstudio_pages, true)) {
				wp_enqueue_style('wp-color-picker');
				wp_enqueue_style('payamanstudio-admin-style', PAYAMAN_WISHLIST_URL . '/assets/css/payamanstudio-admin.css', array(), PAYAMAN_WISHLIST_VERSION);
				wp_enqueue_script('payaman_wishlist-admin-script', PAYAMAN_WISHLIST_URL . '/assets/js/payaman_wishlist-admin-script.js', array('jquery', 'wp-color-picker'), PAYAMAN_WISHLIST_VERSION, true);

				if (class_exists('WooCommerce')) {
					wp_enqueue_script('wc-enhanced-select');
					wp_enqueue_style('woocommerce_admin_styles');
				}
			}
		}
	}

	new Payaman_Wishlist();
}
