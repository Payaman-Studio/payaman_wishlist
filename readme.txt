=== Gridly Wishlist ===
Contributors: gridlystudio
Tags: wishlist, woocommerce, favorites, collections, share wishlist
Requires at least: 5.8
Tested up to: 6.5
Stable tag: 1.0.2
Requires PHP: 7.4
WC requires at least: 6.5.3
WC tested up to: 9.3.2
License: GPLv3 or later
License URI: https://www.gnu.org/licenses/gpl-3.0.html

A simple and powerful wishlist plugin for WooCommerce with collection management, guest support, and public sharing.

== Description ==

**Gridly Wishlist** adds a fully-featured wishlist experience to your WooCommerce store. Customers can save products to named collections, manage multiple wishlists, and share their favourite picks with others.

= Key Features =

* **Add to Wishlist** — One-click button on product pages and product loops
* **Guest Support** — Guests can save wishlists via browser cookies, no login required
* **Auto Migration** — Cookie wishlists are automatically merged into the user's account upon login
* **Named Collections** — Logged-in users can organise products into multiple collections
* **Public Sharing** — Mark a collection as public and share its link with anyone
* **Bulk Actions** — Select multiple products to remove or move between collections at once
* **Overlay Mode** — Button can be placed as an overlay on the product image
* **Image or Text Buttons** — Choose custom images or text labels for the wishlist button
* **Translatable** — All strings go through WordPress i18n and are Loco Translate-ready

= Shortcodes =

* `[gridlywishlist_button]` — Display the wishlist toggle button for the current product
* `[gridlywishlist_button product_id="42"]` — Display for a specific product
* `[gridlywishlist_list]` — Display the current user's wishlist (use `?share={slug}` to view a public collection)

== Installation ==

1. Upload the `gridlywishlist` folder to the `/wp-content/plugins/` directory, or install via **Plugins → Add New** in your WordPress dashboard.
2. Activate the plugin through the **Plugins** screen.
3. Make sure **WooCommerce** is installed and active.
4. Go to **Gridly Studio → Gridly Wishlist** to configure the plugin settings.
5. Add the shortcode `[gridlywishlist_list]` to a page (e.g., a page named "Wishlist").

== Frequently Asked Questions ==

= Does it work with page caching plugins like WP Rocket? =

The wishlist button state is determined server-side at render time. For maximum compatibility with full-page caching, ensure the wishlist page itself is excluded from cache, or contact us for guidance.

= Can guests use the wishlist? =

Yes. Guests can add products to their wishlist via browser cookies. When they log in, those items are automatically moved to their account.

= How do I share a wishlist? =

1. Go to your wishlist page.
2. On any collection tab, click the 🔗 icon (only visible when the collection is set to **Public** in the collection settings).
3. Copy and share the generated URL.

= What shortcode do I use to display the wishlist? =

Add `[gridlywishlist_list]` to any page. The plugin automatically creates a **Wishlist** page during activation with this shortcode.

= How do I change the button text? =

Go to **Gridly Studio → Gridly Wishlist → Button Setting** to customise the "Add" and "Remove" button text or upload custom images.

== Screenshots ==

1. Wishlist button on a product page (text mode)
2. Wishlist list page with collection tabs
3. Collection management modal
4. Plugin settings page in wp-admin

== Changelog ==

= 1.0.2 =
* Added: WooCommerce HPOS (Custom Order Tables) compatibility declaration
* Fixed: Admin CSS URL generation using `plugin_dir_url()` for reliability
* Fixed: Missing `version` parameter on `wp_enqueue_style` and `wp_enqueue_script` calls
* Added: Proper output escaping (`esc_attr`, `esc_html`, `wp_kses_post`) across all views
* Added: All JS user-facing strings now use `wp_localize_script` for full translatability
* Added: Guest-to-user wishlist migration on login via `wp_login` hook
* Added: Public collection sharing via `?share={slug}` URL parameter
* Added: Share link (🔗) displayed next to public collection tabs

= 1.0.1 =
* Added: Multiple named collections per user
* Added: Bulk remove and bulk move between collections
* Added: Overlay button position mode
* Added: Custom button position configuration

= 1.0.0 =
* Initial release

== Upgrade Notice ==

= 1.0.2 =
Security and quality release. Update recommended for all users.
