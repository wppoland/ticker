# Ticker

Ticker adds a live, accessible sale countdown timer to your WooCommerce product pages — a simple way to add urgency and turn browsers into buyers.

## Features

- Live countdown to a product's native WooCommerce sale end date, or to a store-wide campaign end date you set.
- End time is resolved on the server, so a visitor's wrong clock can never change the actual end moment; the browser only formats the remaining time.
- Choose where the timer appears and pick from three display formats, with an optional heading and a customisable expired message.
- Accessible `role="timer"` with a polite live region; no jQuery and no layout shift. Dark-mode and reduced-motion aware.
- Graceful states: nothing renders when there is nothing to show, and a friendly message replaces the clock once the sale ends.

## Installation

1. Upload the plugin to `/wp-content/plugins/ticker`, or install it from Plugins → Add New.
2. Activate it. WooCommerce must be active.
3. Go to WooCommerce → Ticker, enable the countdown, then set a sale end date on a product (or a global campaign end date in the settings).

## Frequently Asked Questions

**Where does the countdown end time come from?**
By default Ticker reads each product's native WooCommerce sale end date. You can instead count down to a single store-wide campaign date, or mix both — a product sale date is used when present, with the global date as a fallback.

**Is the timer accurate if the visitor's clock is wrong?**
Yes. The end moment is a fixed timestamp from the server; the browser only formats the remaining time.

Built by WPPoland — https://plogins.com

License: GPL-2.0-or-later
