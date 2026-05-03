<?php

/**
 * Plugin Name: Gridly Wishlist
 * Plugin URI: https://gridlystudio.com/plugin/gridly-wishlist/
 * Description: Gridly Wishlist for WooCommerce
 * Version: 1.0.2
 * Author: Gridly Studio
 * Author URI: https://gridlystudio.com/
 * Text Domain: gridlywishlist
 * Domain Path: /lang
 *
 * WC requires at least: 6.5.3
 * WC tested up to: 9.3.2
 * 
 * Requires Plugins: woocommerce
 *
 * Copyright: © 2018 gridlywishlist.
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

if (! class_exists('GridlyWishlist')) {

	/**
	 * Main Class GridlyWishlist
	 */
	class GridlyWishlist
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
						$message = __('WooCommerce needs to be installed and activated to use "Gridly Wishlist"', 'gridlywishlist');

						printf('<div class="%1$s"><p>%2$s</p></div>', esc_attr($class), esc_html($message));
					}
				);
				return;
			}

			register_activation_hook(__FILE__, array($this, 'gridlywishlist_install'));

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
			define('GRIDLYWISHLIST_VERSION', '1.0.2');
			define('GRIDLYWISHLIST_URL', untrailingslashit(plugin_dir_url(__FILE__)));
			define('GRIDLYWISHLIST_LINK', plugin_dir_url(__FILE__));
			define('GRIDLYWISHLIST_PATH', plugin_dir_path(__FILE__));
			define('GRIDLYWISHLIST_REL_PATH', dirname(plugin_basename(__FILE__)) . '/');
			define('GRIDLYWISHLIST_DB_VERSION', '2.1.0');
			define('GRIDLYWISHLIST_TABLE_COLLECTIONS', $wpdb->prefix . 'gridlywishlist_collections');
			define('GRIDLYWISHLIST_TABLE_ITEMS', $wpdb->prefix . 'gridlywishlist_collection_items');
			if (! defined('GRIDLYWISHLIST_COLLECTION_LIMIT')) {
				define('GRIDLYWISHLIST_COLLECTION_LIMIT', 20);
			}
		}

		/**
		 * Include required files.
		 *
		 * @return void
		 */
		private function includes()
		{
			include_once GRIDLYWISHLIST_PATH . 'inc/helpers.php';
			include_once GRIDLYWISHLIST_PATH . 'inc/class-gridlywishlist-admin-page.php';
			include_once GRIDLYWISHLIST_PATH . 'inc/class-gridlywishlist-front.php';
			include_once GRIDLYWISHLIST_PATH . 'inc/class-gridlywishlist-alerts.php';
		}

		/**
		 * Method for handle what first plugin doing after active gridlywishlist plugin
		 *
		 * @return [type] [description]
		 */
		public function gridlywishlist_install()
		{
			if (get_page_by_title('Wishlist', ARRAY_A, 'page') == null) {
				gridlywishlist_create_page();
			}
			if (! get_option('gridlywishlist_settings')) {
				gridlywishlist_default_setting();
			}
			gridlywishlist_maybe_install_tables();
			gridlywishlist_maybe_run_migrations();
		}

		public function maybe_upgrade()
		{
			gridlywishlist_maybe_install_tables();
			gridlywishlist_maybe_run_migrations();
		}

		/**
		 * Add setting quick link to the plugins list
		 *
		 * @param  array $links Links.
		 * @return array $links Links
		 */
		public function plugin_add_settings_link($links)
		{
			$settings_link = '<a href="' . admin_url('admin.php?page=gridlywishlist') . '">' . __('Settings', 'gridlywishlist') . '</a>';
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
			$gridlystudio_pages = array(
				'toplevel_page_gridlystudio',
				'gridly-studio_page_gridlywishlist',
				'toplevel_page_gridlywishlist',
				'gridlywishlist_page_gridlywishlist-settings',
			);

			if (in_array($page, $gridlystudio_pages, true)) {
				wp_enqueue_style('wp-color-picker');
				wp_enqueue_style('gridlystudio-admin-style', GRIDLYWISHLIST_URL . '/assets/css/gridlystudio-admin.css', array(), GRIDLYWISHLIST_VERSION);
				wp_enqueue_script('gridlywishlist-admin-script', GRIDLYWISHLIST_URL . '/assets/js/gridlywishlist-admin-script.js', array('jquery', 'wp-color-picker'), GRIDLYWISHLIST_VERSION, true);
			}
		}
	}

	new GridlyWishlist();
}
