=== Payaman Wishlist ===
Contributors: payamanstudio
Tags: wishlist, woocommerce, favorites, collections, share wishlist
Requires at least: 5.8
Tested up to: 6.5
Stable tag: 1.0.2
Requires PHP: 7.4
WC requires at least: 6.5.3
WC tested up to: 9.3.2
License: GPLv3 or later
License URI: https://www.gnu.org/licenses/gpl-3.0.html

A wishlist plugin for WooCommerce with named collections, guest support, public sharing, restock/price alerts, and email campaigns.

== Description ==

**Payaman Wishlist** adds a fully-featured wishlist experience to your WooCommerce store. Customers can save products to named collections, manage multiple wishlists, share their favourite picks with others, and get notified when a wishlisted product comes back in stock or drops in price. Store owners get a campaign tool to email customers about the products sitting in their wishlist.

= Key Features =

**For your customers**

* **Add to Wishlist** — One-click button on product pages and product loops
* **Guest Support** — Guests can save wishlists via browser cookies, no login required
* **Auto Migration** — Cookie wishlists are automatically merged into the user's account upon login
* **Named Collections** — Logged-in users can organise products into multiple collections (e.g. "Birthday", "Home Office")
* **Public Sharing** — Mark a collection as public and share its link via a direct URL or a one-tap WhatsApp share
* **Bulk Actions** — Select multiple products to remove or move between collections at once
* **Overlay Mode** — Button can be placed as an overlay on the product image, or inline as text
* **Image or Text Buttons** — Choose custom images or text labels for the wishlist button
* **Back-in-Stock Alerts** — Customers are emailed automatically when a wishlisted product is restocked
* **Price-Drop Alerts** — Customers are emailed automatically when a wishlisted product goes on sale
* **Variation Support** — Wishlisted variable products remember the selected variation

**For store owners**

* **Promotional Email Campaigns** — Compose a campaign targeting one or more products and email every customer who has them in their wishlist, with merge tags for personalisation
* **Flexible Sending** — Send immediately, schedule for a specific date/time, or repeat daily, weekly, or monthly (processed by WP-Cron every minute)
* **Pause, Resume & Resend** — Pause a scheduled campaign without losing it, resume it later, or resend a campaign at any time
* **Wishlist Analytics** — Dashboard view of your most-wishlisted products and overall wishlist activity
* **Full Settings Control** — Toggle every feature, customise button labels/images, and edit every customer-facing message and email template from **Payaman Studio → Payaman Wishlist**
* **Translatable** — All strings go through WordPress i18n and are Loco Translate-ready
* **WooCommerce HPOS Compatible** — Declares compatibility with High-Performance Order Storage

= Shortcodes =

* `[payaman_wishlist_button]` — Display the wishlist toggle button for the current product
* `[payaman_wishlist_button product_id="42"]` — Display for a specific product
* `[payaman_wishlist_list]` — Display the current user's wishlist (use `?share={slug}` to view a public collection)

= Email Merge Tags =

Available when composing promotional email campaigns:

* `{user_name}` — Recipient's display name
* `{site_name}` — Your site name
* `{count}` — Number of wishlisted products included
* `{products_list}` — Numbered list of the targeted product(s)

Back-in-stock and price-drop alert emails support `{user_name}`, `{product_name}`, `{product_url}`, and `{site_name}`.

= For Developers =

* `payaman_wishlist_email_template` — filter the base HTML wrapper used for campaign and alert emails
* `payaman_wishlist_allow_enqueue_scripts` — filter whether the front-end assets are enqueued on the current page

== Installation ==

1. Make sure **WooCommerce** is installed and active — Payaman Wishlist requires it and will show an admin notice until it is.
2. Upload the `payaman_wishlist` folder to the `/wp-content/plugins/` directory, or install directly via **Plugins → Add New → search "Payaman Wishlist"** in your WordPress dashboard.
3. Activate the plugin through the **Plugins** screen. A **Wishlist** page with the `[payaman_wishlist_list]` shortcode is created automatically on activation.
4. Go to **Payaman Studio → Payaman Wishlist** in your dashboard menu to configure settings:
   * **General** — enable/disable the plugin, require login, back-in-stock and price-drop alerts
   * **Button** — where the button appears, its position, and text or image labels
   * **Messages** — customise add/remove confirmation text and the required-login message
   * **Email Campaigns** — create and schedule promotional emails
5. Add `[payaman_wishlist_button]` to a product template, or rely on the automatic placement configured in the **Button** tab.
6. (Optional) Add `[payaman_wishlist_list]` to any other page if you don't want to use the auto-created Wishlist page.

== Frequently Asked Questions ==

= Does WooCommerce need to be active? =

Yes. Payaman Wishlist requires WooCommerce and will display an admin notice, without enabling its features, until WooCommerce is installed and activated.

= Does it work with page caching plugins like WP Rocket? =

The wishlist button state is determined server-side at render time. For maximum compatibility with full-page caching, exclude the wishlist page itself from cache, or contact us for guidance.

= Can guests use the wishlist? =

Yes. Guests can add products to their wishlist via browser cookies. When they log in, those items are automatically merged into their account.

= How do I share a wishlist? =

1. Go to your wishlist page.
2. On any collection tab, click the 🔗 icon (only visible when the collection is set to **Public** in the collection settings).
3. Copy the generated link, or use the built-in WhatsApp share button.

= What shortcode do I use to display the wishlist? =

Add `[payaman_wishlist_list]` to any page. The plugin automatically creates a **Wishlist** page with this shortcode during activation.

= How do I change the button text or image? =

Go to **Payaman Studio → Payaman Wishlist → Button** to customise the "Add" and "Remove" button text, or upload custom images for each state.

= How do back-in-stock and price-drop alerts work? =

Turn them on under **General → Marketing Alerts**. When a wishlisted product is restocked or goes on sale, every customer who wishlisted it (and is a registered user) receives an automatic email. If Action Scheduler (bundled with WooCommerce) is available, alerts are queued in the background so they don't slow down the product update.

= How do promotional email campaigns work? =

Under **Email Campaigns**, create a campaign, pick one or more products, and choose to send immediately, schedule it, or repeat it. Every user who has at least one of the chosen products in their wishlist receives the email. A WP-Cron job checks for due campaigns every minute.

= Is it translation-ready? =

Yes. All strings go through WordPress's i18n functions and are ready for Loco Translate or GlotPress.

== Screenshots ==

1. Wishlist button on a product page (text mode)
2. Wishlist list page with collection tabs
3. Collection management modal
4. Plugin settings page in wp-admin
5. Promotional email campaign composer
6. Wishlist analytics dashboard

== Changelog ==

= 1.0.2 =
* Fixed: Internal version constant now matches the plugin header, so asset cache-busting works correctly on updates
* Fixed: Settings save now checks `manage_options` capability in addition to the nonce check
* Added: `Requires at least` / `Requires PHP` headers in the main plugin file
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