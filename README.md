# Ticker — Sales Countdown Timer for WooCommerce

A live, accessible sale **countdown timer** plus optional **low-stock scarcity** messaging for
WooCommerce product pages. Self-contained (no runtime Composer dependencies), no jQuery, no layout
shift.

## What it does (FREE)

On single product pages, Ticker renders a live countdown to the end of a sale and, optionally, a
friendly "Only N left in stock" message.

- **End time source** (configurable):
  1. the product's native WooCommerce `Sale price dates → To`,
  2. a per-product campaign end date (post meta `_ticker_campaign_end`, set by PRO),
  3. a global campaign end date set in the plugin settings.
- The **server** computes the end timestamp; the **browser** only formats the remaining time, so a
  visitor's wrong clock can never change the actual end moment.
- Graceful states: nothing to show → renders nothing; sale ended → friendly expired message.

## Architecture

- **Bootstrap** (`ticker.php`): PHP/WC guards, HPOS + cart-blocks compat, boots on `init:0`, fires
  `do_action('ticker/booted', Plugin::instance())` from inside `Plugin::boot()` — the hook the PRO
  companion extends. No translation calls at `plugins_loaded` scope.
- **Autoload** (`autoload.php`): Composer autoloader when present, PSR-4 fallback otherwise (so the
  plugin works from a packaged ZIP without `vendor/`).
- **DI**: `src/Plugin.php` singleton + `src/Container.php` (with `has()`); services in
  `config/services.php`, boot order in `config/hooks.php`, defaults in `config/defaults.php`;
  `src/Migrator.php` for idempotent version migrations.
- **Core**: `src/Service/CountdownService.php` (resolve end time + scarcity, render),
  `src/Util/TemplateLoader.php` (theme-overridable templates),
  `templates/single-product/countdown.php`.
- **Admin**: `src/Admin/Settings.php` — a **WooCommerce → Ticker** submenu page (Settings API).
- **Assets**: `assets/{css,js}` — vanilla JS countdown, modern CSS with custom properties,
  `prefers-reduced-motion` and dark-mode aware.

## Gates

```bash
composer install
composer cs        # PHPCS (WordPress standard)
composer analyse   # PHPStan level 6 + WooCommerce stubs
```

## PRO companion

`wppoland/ticker-pro` (private) hooks `add_action('ticker/booted', …)`, bundles the Freemius SDK,
and extends the shared container with premium features (per-product campaign dates, scheduled and
recurring campaigns, more).
