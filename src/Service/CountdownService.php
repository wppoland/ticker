<?php
/**
 * Sale countdown timer service.
 *
 * @package Ticker\Service
 */

declare(strict_types=1);

namespace Ticker\Service;

defined( 'ABSPATH' ) || exit;

use Ticker\Contract\HasHooks;
use Ticker\Util\TemplateLoader;

/**
 * Renders a live sale countdown timer (and optional low-stock scarcity message)
 * on single product pages.
 *
 * The end timestamp is resolved server-side from one of:
 *  - the WooCommerce sale_price_dates_to (per product),
 *  - a per-product campaign end date (post meta `_ticker_campaign_end`),
 *  - a global campaign end date from settings.
 *
 * The browser only formats the remaining time; the source of truth is the
 * server-provided UTC timestamp, so clock skew never produces a wrong end time.
 *
 * @package Ticker\Service
 */
final class CountdownService implements HasHooks {

	private const OPTION = 'ticker_settings';

	private const PRODUCT_META = '_ticker_campaign_end';

	/**
	 * Constructor.
	 *
	 * @param TemplateLoader $template_loader Template loader utility.
	 */
	public function __construct(
		private readonly TemplateLoader $template_loader,
	) {
	}

	/**
	 * Supported placement hooks for the countdown on the single product page.
	 *
	 * Maps a stored placement key to a WooCommerce action hook and priority.
	 *
	 * @return array<string, array{hook: string, priority: int}>
	 */
	public static function placements(): array {
		return array(
			'summary'      => array(
				'hook'     => 'woocommerce_single_product_summary',
				'priority' => 25,
			),
			'before_cart'  => array(
				'hook'     => 'woocommerce_before_add_to_cart_form',
				'priority' => 10,
			),
			'after_cart'   => array(
				'hook'     => 'woocommerce_after_add_to_cart_form',
				'priority' => 10,
			),
			'product_meta' => array(
				'hook'     => 'woocommerce_product_meta_start',
				'priority' => 5,
			),
		);
	}

	/**
	 * Register WordPress hooks.
	 */
	public function registerHooks(): void {
		add_action( 'wp_enqueue_scripts', array( $this, 'register_assets' ) );

		$settings = $this->settings();

		if ( empty( $settings['enabled'] ) ) {
			return;
		}

		$placement  = (string) ( $settings['placement'] ?? 'summary' );
		$placements = self::placements();
		$target     = $placements[ $placement ] ?? $placements['summary'];

		add_action( $target['hook'], array( $this, 'render' ), $target['priority'] );
	}

	/**
	 * Register front-end assets. Enqueued on demand from render().
	 */
	public function register_assets(): void {
		wp_register_style(
			'ticker-countdown',
			\Ticker\Plugin::instance()->url( 'assets/css/ticker.css' ),
			array(),
			\Ticker\VERSION,
		);

		wp_register_script(
			'ticker-countdown',
			\Ticker\Plugin::instance()->url( 'assets/js/ticker.js' ),
			array(),
			\Ticker\VERSION,
			array(
				'in_footer' => true,
				'strategy'  => 'defer',
			),
		);
	}

	/**
	 * Render the countdown on the single product page (auto placement).
	 */
	public function render(): void {
		global $product;

		if ( ! $product instanceof \WC_Product ) {
			return;
		}

		$settings = $this->settings();

		$end_ts = $this->resolve_end_timestamp( $product, $settings );

		$scarcity = $this->resolve_scarcity( $product, $settings );

		// Nothing to show: no live countdown and no scarcity message. Hide rather
		// than render an empty shell.
		if ( ( null === $end_ts || $end_ts <= time() ) && null === $scarcity ) {
			// Still render an expired state if a countdown was configured.
			if ( null === $end_ts ) {
				return;
			}
		}

		wp_enqueue_style( 'ticker-countdown' );
		wp_enqueue_script( 'ticker-countdown' );

		$format = (string) ( $settings['format'] ?? 'dhms' );
		if ( ! in_array( $format, array( 'dhms', 'hms', 'compact' ), true ) ) {
			$format = 'dhms';
		}

		$expired_message = (string) ( $settings['expired_message'] ?? '' );
		if ( '' === $expired_message ) {
			$expired_message = __( 'This sale has ended.', 'ticker' );
		}

		ob_start();
		$this->template_loader->include(
			'single-product/countdown',
			array(
				'end_ts'          => $end_ts,
				'format'          => $format,
				'heading'         => (string) ( $settings['heading'] ?? '' ),
				'expired_message' => $expired_message,
				'scarcity'        => $scarcity,
				'now'             => time(),
			),
		);

		echo ob_get_clean(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Output is escaped inside the template.
	}

	/**
	 * Resolve the sale/campaign end timestamp (UTC), or null when none applies.
	 *
	 * @param \WC_Product          $product  The current product.
	 * @param array<string, mixed> $settings Plugin settings.
	 * @return int|null Unix timestamp (UTC), or null.
	 */
	private function resolve_end_timestamp( \WC_Product $product, array $settings ): ?int {
		$source = (string) ( $settings['source'] ?? 'sale' );

		$ts = null;

		if ( 'sale' === $source ) {
			$ts = $this->sale_end_timestamp( $product );
		}

		// Per-product campaign override (works for both sources, and as a sale fallback).
		if ( null === $ts ) {
			$product_end = $this->product_campaign_timestamp( $product );
			if ( null !== $product_end ) {
				$ts = $product_end;
			}
		}

		// Global campaign date as a final fallback / primary for 'campaign' source.
		if ( null === $ts ) {
			$ts = $this->global_campaign_timestamp( $settings );
		}

		/**
		 * Filter the resolved countdown end timestamp for a product.
		 *
		 * PRO uses this to inject scheduled/recurring campaign windows.
		 *
		 * @param int|null             $ts       Resolved UTC timestamp, or null.
		 * @param \WC_Product          $product  The product.
		 * @param array<string, mixed> $settings Plugin settings.
		 */
		$ts = apply_filters( 'ticker/end_timestamp', $ts, $product, $settings );

		return is_int( $ts ) ? $ts : null;
	}

	/**
	 * Read the WooCommerce sale end date for a product (UTC timestamp), or null.
	 *
	 * @param \WC_Product $product The product.
	 * @return int|null
	 */
	private function sale_end_timestamp( \WC_Product $product ): ?int {
		if ( ! $product->is_on_sale() ) {
			return null;
		}

		$date = $product->get_date_on_sale_to();

		if ( $date instanceof \WC_DateTime ) {
			$ts = $date->getTimestamp();
			return $ts > 0 ? $ts : null;
		}

		return null;
	}

	/**
	 * Read a per-product campaign end date from post meta, or null.
	 *
	 * Stored as a `Y-m-d\TH:i` string in the store timezone.
	 *
	 * @param \WC_Product $product The product.
	 * @return int|null
	 */
	private function product_campaign_timestamp( \WC_Product $product ): ?int {
		$raw = get_post_meta( $product->get_id(), self::PRODUCT_META, true );

		if ( ! is_string( $raw ) || '' === $raw ) {
			return null;
		}

		return $this->local_string_to_timestamp( $raw );
	}

	/**
	 * Read the global campaign end date from settings, or null.
	 *
	 * @param array<string, mixed> $settings Plugin settings.
	 * @return int|null
	 */
	private function global_campaign_timestamp( array $settings ): ?int {
		$raw = (string) ( $settings['campaign_end'] ?? '' );

		if ( '' === $raw ) {
			return null;
		}

		return $this->local_string_to_timestamp( $raw );
	}

	/**
	 * Convert a `Y-m-d\TH:i` store-timezone string to a UTC timestamp.
	 *
	 * @param string $local Local datetime string.
	 * @return int|null
	 */
	private function local_string_to_timestamp( string $local ): ?int {
		try {
			$tz = wp_timezone();
			$dt = \DateTimeImmutable::createFromFormat( 'Y-m-d\TH:i', $local, $tz );

			if ( false === $dt ) {
				// Tolerate a space separator as well.
				$dt = \DateTimeImmutable::createFromFormat( 'Y-m-d H:i', $local, $tz );
			}

			if ( false === $dt ) {
				return null;
			}

			return $dt->getTimestamp();
		} catch ( \Exception $e ) {
			return null;
		}
	}

	/**
	 * Resolve the scarcity message data for a product, or null when disabled
	 * or not applicable.
	 *
	 * @param \WC_Product          $product  The product.
	 * @param array<string, mixed> $settings Plugin settings.
	 * @return array{stock: int, message: string}|null
	 */
	private function resolve_scarcity( \WC_Product $product, array $settings ): ?array {
		if ( empty( $settings['scarcity_enabled'] ) ) {
			return null;
		}

		if ( ! $product->managing_stock() || ! $product->is_in_stock() ) {
			return null;
		}

		$stock = $product->get_stock_quantity();

		if ( ! is_int( $stock ) && ! is_numeric( $stock ) ) {
			return null;
		}

		$stock     = (int) $stock;
		$threshold = max( 1, (int) ( $settings['scarcity_threshold'] ?? 5 ) );

		if ( $stock <= 0 || $stock > $threshold ) {
			return null;
		}

		$message = sprintf(
			/* translators: %d: number of items left in stock. */
			_n( 'Only %d left in stock!', 'Only %d left in stock!', $stock, 'ticker' ),
			$stock,
		);

		return array(
			'stock'   => $stock,
			'message' => $message,
		);
	}

	/**
	 * Return the raw settings array.
	 *
	 * @return array<string, mixed>
	 */
	private function settings(): array {
		$defaults = require \Ticker\PLUGIN_DIR . '/config/defaults.php';
		$stored   = get_option( self::OPTION, array() );

		if ( ! is_array( $stored ) ) {
			$stored = array();
		}

		return array_merge( $defaults, $stored );
	}
}
