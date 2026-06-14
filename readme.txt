=== Ticker - Sales Countdown Timer for WooCommerce ===
Contributors: wppoland
Tags: woocommerce, countdown, sale, urgency, timer
Requires at least: 6.5
Tested up to: 7.0
Requires PHP: 8.1
Stable tag: 0.1.0
Requires Plugins: woocommerce
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Live sale countdown timer for WooCommerce product pages. Accessible, no jQuery, no layout shift.

== Description ==

Ticker adds a live, ticking countdown to the end of a sale on your WooCommerce product pages — a proven way to turn "I'll think about it" into "buy now".

The end time is resolved on the server (so there is one source of truth) and counted down in the browser with a few lines of dependency-free vanilla JavaScript.

**Why Ticker?**

* **Reads your existing sale dates.** Ticker uses each product's native WooCommerce "Sale price dates" out of the box — set a sale end and the countdown appears automatically.
* **Or run a store-wide campaign.** Prefer one deadline for everything? Set a single global campaign end date.
* **No jQuery, no layout shift.** The markup ships server-rendered with reserved space; the timer fills in without shifting your layout (CLS-friendly).
* **Accessible.** ARIA `role="timer"` with a polite live region, screen-reader friendly labels, and motion that respects `prefers-reduced-motion`.
* **Graceful states.** Nothing to show? Nothing renders. Sale ended? A friendly expired message replaces the clock.
* **Themeable.** Restyle with CSS custom properties, or override the template from your theme (`yourtheme/ticker/single-product/countdown.php`).
* **Clean uninstall.** No custom tables. Settings live in `wp_options` and are removed on delete.
* **HPOS and Cart/Checkout Blocks compatible.**

**Features**

* Live countdown to a product's WooCommerce sale end date, or a global campaign end date you set
* Choose where the timer appears: product summary, before/after the add-to-cart form, or the product meta area
* Three display formats: days:hours:minutes:seconds, hours:minutes:seconds, or compact
* Optional heading and a customisable expired message
* Fully translatable (Text Domain `ticker`, translations in `/languages`)

== Installation ==

1. Install and activate WooCommerce (8.0 or later).
2. Upload the `ticker` folder to `/wp-content/plugins/`, or install directly from the WordPress plugin directory.
3. Activate the plugin through the **Plugins** screen.
4. Go to **WooCommerce → Ticker** and enable the countdown.
5. Set a sale end date on a product (Product data → General → Sale price dates), or set a global campaign end date in Ticker's settings. The countdown then appears on the product page.

== Frequently Asked Questions ==

= Does Ticker require WooCommerce? =
Yes. Ticker is a WooCommerce extension and requires WooCommerce 8.0 or later.

= Where does the countdown end time come from? =
By default, Ticker reads each product's native WooCommerce "Sale price dates → To" field. You can instead count down to a single store-wide campaign date, or mix both — a product sale date is used when present, with the global campaign date as a fallback.

= Does the timer cause layout shift? =
No. The countdown markup is rendered on the server with space reserved, so the browser fills in the numbers without shifting the page (good for Core Web Vitals / CLS).

= Is the timer accurate if the visitor's clock is wrong? =
The end moment is provided by the server as a fixed timestamp. The browser only formats the remaining time, so a misconfigured visitor clock cannot change the actual end time.

= What happens when the sale ends? =
The clock is replaced with a friendly expired message (which you can customise).

= What happens when I delete the plugin? =
The uninstall routine removes Ticker's options. No custom tables are created, so your database is left clean.

== Screenshots ==

1. Live sale countdown timer on a product page.
2. Admin settings page — countdown source, format, and placement.

== Changelog ==

= 0.1.0 =
* Initial release: live sale countdown from WooCommerce sale dates or a global campaign date, configurable placement and format, and a friendly expired state. Accessible, no jQuery, no layout shift.
