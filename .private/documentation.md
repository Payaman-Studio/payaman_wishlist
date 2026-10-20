# Payaman Wishlist — Documentation

This is a deeper usage guide than the plugin listing on WordPress.org (`readme.txt`). Use it as your support/knowledge-base page, or hand it to a customer who needs more detail than the FAQ covers.

## Requirements

|             | Minimum | Notes                                                |
| ----------- | ------- | ---------------------------------------------------- |
| WordPress   | 5.8     |                                                      |
| PHP         | 7.4     |                                                      |
| WooCommerce | 6.5.3   | Required — the plugin will not run without it active |

## 1. Installation

1. Install and activate **WooCommerce** first. Payaman Wishlist checks for it on load and shows an admin notice (without enabling any feature) until it's active.
2. Install Payaman Wishlist via **Plugins → Add New**, or upload the ZIP via **Plugins → Add New → Upload Plugin**.
3. Activate it. On activation the plugin automatically:
   - Creates a **Wishlist** page containing the `[payaman_wishlist_list]` shortcode (only if a page with that title doesn't already exist).
   - Creates its default settings.
   - Creates its three database tables (`wp_payaman_wishlist_collections`, `wp_payaman_wishlist_collection_items`, `wp_payaman_wishlist_campaigns`).
   - Schedules a WP-Cron event (`payaman_wishlist_cron`) that runs every minute to process due email campaigns.

## 2. Settings reference (Payaman Studio → Payaman Wishlist)

### General

| Setting                  | What it does                                                                                                        |
| ------------------------ | ------------------------------------------------------------------------------------------------------------------- |
| Enabled                  | Master on/off switch for the whole plugin                                                                           |
| Wishlist Page            | Shows the auto-created page URL; the same output is available anywhere via `[payaman_wishlist_list]`                |
| Display Wishlist Number  | Shows a per-product wishlist count. Adds a query per product, so test performance on large catalogs before enabling |
| Required Login           | If on, guests are blocked from adding to wishlist and see the "Required Login Message" instead                      |
| Remove After Add to Cart | Automatically removes an item from the wishlist once it's added to cart                                             |
| Marketing Alerts         | Two independent toggles: **Price Drop Alerts** and **Back in Stock Alerts** (see §4)                                |

### Button

| Setting               | What it does                                                |
| --------------------- | ----------------------------------------------------------- |
| Display Button On     | Single product page, product loops (archives/shop), or both |
| Button Position       | Where in the template the button is injected                |
| Type                  | Text or Image button                                        |
| Button Text Off / On  | Label shown when a product is not / is in the wishlist      |
| Button Image Off / On | Custom images for each state (Type = Image)                 |

### Messages

| Setting                | What it does                                                       |
| ---------------------- | ------------------------------------------------------------------ |
| Add Success Message    | Shown after a product is added                                     |
| Remove Success Message | Shown after a product is removed                                   |
| Required Login Message | Shown when Required Login is on and a guest tries to add a product |

### Email Campaigns

See §5 below — this tab is the campaign list and composer.

## 3. Collections

Logged-in users can organise wishlisted products into named collections (default limit: 20 per user, filterable via `PAYAMAN_WISHLIST_COLLECTION_LIMIT`).

- Every user gets a default collection automatically; it cannot be deleted.
- Deleting a non-default collection moves its items into the target collection you choose (or the default collection if none is chosen).
- A collection can be marked **Public**. Public collections get a shareable URL in the form `{wishlist-page}?share={slug}`, plus a one-tap WhatsApp share link.
- Guests don't get collections — their items live in a browser cookie and merge into their default collection automatically the moment they log in.

## 4. Back-in-stock & price-drop alerts

Enable these under **General → Marketing Alerts**. Both only notify **registered users** (guests aren't tracked by email, so they're not eligible).

- **Back in Stock**: fires on WooCommerce's `woocommerce_product_set_stock` hook when a product transitions to in-stock.
- **Price Drop**: fires on `woocommerce_update_product` when a product is on sale and its current price differs from the last price it alerted at (so the same sale price won't re-trigger repeatedly).
- If WooCommerce's **Action Scheduler** library is available, both alerts are queued as background actions (`payaman_wishlist_bg_stock_alert` / `payaman_wishlist_bg_price_alert`) so a product save/stock update isn't slowed down waiting on `wp_mail()`. If Action Scheduler isn't available, the email sends inline.
- Subject/body templates are editable under **Messages → Email Alerts**, with these merge tags: `{user_name}`, `{product_name}`, `{product_url}`, `{site_name}`.

## 5. Promotional email campaigns

Under **Email Campaigns**:

1. **New Campaign** → give it a name, pick one or more products, write a subject and body.
2. Insert merge tags by clicking the tag chips: `{user_name}`, `{site_name}`, `{count}`, `{products_list}`.
3. Choose a send type:
   - **Send Immediately**
   - **Schedule** — pick a date & time (your browser's local time; stored converted to the site timezone)
   - **Repeat** — Daily / Weekly / Monthly, recalculated automatically after each send
4. Use **Preview** to see the rendered email, or **Send Test** to email yourself before broadcasting.
5. A scheduled or repeating campaign can be **paused** and **resumed** without losing its configuration; resuming reschedules it if the original time has already passed.
6. **Send Now** works at any time, even on a campaign already marked `sent` — useful for a manual resend.

Recipients are every user who has **at least one** of the campaign's target products in any collection. `{products_list}` renders as a numbered list of just the targeted products, not the recipient's entire wishlist.

The WP-Cron event `payaman_wishlist_cron` checks for due campaigns every minute. On low-traffic sites where WP-Cron relies on page visits, consider a real server cron hitting `wp-cron.php` if scheduled sends feel delayed.

## 6. Shortcodes

| Shortcode                                   | Purpose                                                                             |
| ------------------------------------------- | ----------------------------------------------------------------------------------- |
| `[payaman_wishlist_button]`                 | Wishlist toggle button for the current product (use inside a product loop/template) |
| `[payaman_wishlist_button product_id="42"]` | Button for a specific product, usable outside the product context                   |
| `[payaman_wishlist_list]`                   | The full wishlist page: collection tabs, bulk actions, share link                   |

## 7. Developer notes

Two filters are currently exposed:

```php
// Customise the HTML wrapper used for every campaign/alert email.
add_filter( 'payaman_wishlist_email_template', function ( $template ) {
    return $template; // return your own wrapper HTML
} );

// Conditionally disable the front-end script/style enqueue on a given page.
add_filter( 'payaman_wishlist_allow_enqueue_scripts', function ( $allow ) {
    return $allow;
} );
```

Database tables (via `$wpdb->prefix`):

- `{prefix}payaman_wishlist_collections` — one row per user collection
- `{prefix}payaman_wishlist_collection_items` — products inside a collection (`product_id`, `variation_id`)
- `{prefix}payaman_wishlist_campaigns` — promotional email campaigns

Guest wishlist state (pre-login) is still tracked the legacy way via post meta on the product, separately from the collections tables, and is merged into the collections tables on login.

## 8. Troubleshooting

**Button doesn't appear on product pages.** Check **Button → Display Button On** includes "Single Product", and that your theme doesn't strip the `woocommerce_single_product_summary` hooks the button attaches to. Use the `[payaman_wishlist_button]` shortcode directly in the template as a fallback.

**Wishlist state looks wrong under full-page caching.** The button's on/off state is rendered server-side. If you run WP Rocket, LiteSpeed Cache, or similar, exclude the wishlist page from full-page cache, and confirm the plugin's AJAX endpoints aren't being cached.

**Scheduled campaign didn't send on time.** WP-Cron only runs on a site visit by default. Verify `payaman_wishlist_cron` is registered (a server-side real cron calling `wp-cron.php` on a schedule is the reliable fix), or use **Send Now** from the campaign list to trigger it manually.

**Alert emails aren't sending.** Confirm the relevant toggle is on under **General → Marketing Alerts**, that the product actually changed status (in-stock transition, or on-sale with a genuinely new price), and that `wp_mail()` is deliverable on your host (test with any other WordPress email, e.g. a password reset).
