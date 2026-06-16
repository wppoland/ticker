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

Adds a live sale countdown to WooCommerce product pages. No jQuery, no layout shift, accessible markup.

== Description ==

Ticker shows how much time is left on a sale, right on the product page. It reads the end time from each product's WooCommerce sale dates, or from a single campaign date you set for the whole store, and counts down to it.

The end time is worked out on the server, so there is one source of truth and a visitor's wrong system clock can't change when the sale actually ends. The browser only formats the remaining time, using a small vanilla-JavaScript script with no jQuery and no other dependencies.

The countdown is built around the WooCommerce sale you already run, so there is nothing extra to schedule:

* Reads each product's native "Sale price dates" out of the box. Set a sale end date and the countdown shows up on that product.
* Or set one store-wide campaign end date if you'd rather count everything down to the same moment. You can mix the two: the per-product sale date wins, and the campaign date fills in for products that don't have one.
* Pick where the timer goes: the product summary (below the price), before or after the add-to-cart form, or the product meta area.
* Three time formats: days/hours/minutes/seconds, hours/minutes/seconds, or a compact hours/minutes that drops the ticking seconds on longer campaigns.
* Optional heading above the clock, and your own wording for the message that replaces it once the sale is over.
* The markup is rendered server-side with the digit boxes already sized, so the timer doesn't push your layout around when JavaScript fills in the numbers (no CLS).
* Marked up with `role="timer"` and a polite live region so screen readers can announce it, and the label text is translatable.
* Restyle it with CSS custom properties, or copy the template into your theme at `yourtheme/ticker/single-product/countdown.php` to change the markup.
* No custom database tables. Settings sit in `wp_options` and are deleted when you remove the plugin.
* Declares HPOS and Cart/Checkout Blocks compatibility.

Source code and issue tracker live on GitHub: https://github.com/wppoland/ticker

== Installation ==

1. Install and activate WooCommerce 8.0 or later.
2. Upload the `ticker` folder to `/wp-content/plugins/`, or install it from the Plugins screen.
3. Activate Ticker through the **Plugins** screen.
4. Go to **WooCommerce → Ticker** and tick "Enable countdown".
5. Set a sale end date on a product (Product data → General → Sale price dates), or set a campaign end date in Ticker's settings. The countdown then appears on the product page.

== Frequently Asked Questions ==

= Does Ticker need WooCommerce? =
Yes. It hooks into WooCommerce product pages and sale dates, and needs WooCommerce 8.0 or later. If WooCommerce isn't active, Ticker stays quiet and shows an admin notice.

= Where does the end time come from? =
By default Ticker reads each product's "Sale price dates → To" value. You can switch the source to a single campaign date that applies across the store, or leave it on the sale date and set a campaign date as well: the product's own sale end is used when it has one, otherwise the campaign date.

= Will the timer move my page content around? =
No. The countdown is rendered on the server with the digit boxes already sized, so the browser drops the numbers into reserved space instead of reflowing the page. That keeps Cumulative Layout Shift at zero for the timer.

= What if the visitor's computer clock is wrong? =
The end moment is sent from the server as a fixed UTC timestamp. The browser only counts down to it, so a visitor's misconfigured clock changes nothing about when the sale ends.

= What shows after the sale ends? =
The clock is hidden and replaced by a short "sale ended" line. You can set your own wording for it, or leave it on the default.

= What happens when I delete Ticker? =
Its two options are removed and no tables are left behind, since Ticker never creates any.

== Screenshots ==

1. The sale countdown timer on a product page.
2. The settings page: countdown source, format, and placement.

== Changelog ==

= 0.1.0 =
* First release. Counts down to a product's WooCommerce sale end date or a store-wide campaign date, with configurable placement, three time formats, an optional heading, and a custom sale-ended message. Server-rendered, no jQuery, no layout shift.
</content>
</invoke>
