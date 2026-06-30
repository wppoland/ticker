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
 * Renders a live sale countdown timer on single product pages.
 *
 * The end timestamp is resolved server-side from one of:
 *  - the WooCommerce sale_price_dates_to (per product),
 *  - a global campaign end date from settings.
 *
 * The browser only formats the remaining time; the source of truth is the
 * server-provided UTC timestamp, so clock skew never produces a wrong end time.
 *
 * @package Ticker\Service
 */
final class CountdownService implements HasHooks {

	private const OPTION = 'ticker_settings';

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

		/**
		 * Filter whether the countdown timer is active and should render.
		 *
		 * @param bool        $active   Whether the timer is active.
		 * @param \WC_Product $product  The current product.
		 * @param array       $settings Ticker settings.
		 */
		if ( ! apply_filters( 'ticker/active', true, $product, $settings ) ) {
			return;
		}

		$end_ts = $this->resolve_end_timestamp( $product, $settings );

		// Nothing to show: no countdown configured. Hide rather than render an
		// empty shell. A configured-but-expired countdown still renders its
		// friendly expired message.
		if ( null === $end_ts ) {
			return;
		}

		wp_enqueue_style( 'ticker-countdown' );
		wp_enqueue_script( 'ticker-countdown' );

		$format = (string) ( $settings['format'] ?? 'dhms' );
		if ( ! in_array( $format, array( 'dhms', 'hms', 'compact' ), true ) ) {
			$format = 'dhms';
		}

		$expired_message = (string) ( $settings['expired_message'] ?? '' );
		if ( '' === $expired_message ) {
			$expired_message = __( 'This sale has ended.', 'plogins-ticker' );
		}

		ob_start();
		$this->template_loader->include(
			'single-product/countdown',
			array(
				'end_ts'          => $end_ts,
				'format'          => $format,
				'heading'         => (string) ( $settings['heading'] ?? '' ),
				'expired_message' => $expired_message,
				'now'             => time(),
				'product_id'      => $product->get_id(),
			),
		);

		$output = (string) ob_get_clean();

		if ( '' === $output ) {
			return;
		}

		echo $output; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Output is escaped inside the template.

		/**
		 * Fires after a sale countdown is rendered on a single product page.
		 *
		 * @param \WC_Product          $product  The current product.
		 * @param array<string, mixed> $settings Plugin settings.
		 */
		do_action( 'ticker/countdown_rendered', $product, $settings );
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

		// Global campaign date as a final fallback / primary for 'campaign' source.
		if ( null === $ts ) {
			$ts = $this->global_campaign_timestamp( $settings );
		}

		/**
		 * Filter the resolved countdown end timestamp (UTC).
		 *
		 * PRO and custom code can override or supply a per-product end time.
		 *
		 * @param int|null             $ts       Resolved timestamp, or null when none applies.
		 * @param \WC_Product          $product  Current product.
		 * @param array<string, mixed> $settings Plugin settings.
		 */
		$filtered = apply_filters( 'ticker/end_timestamp', $ts, $product, $settings );

		if ( is_int( $filtered ) ) {
			return $filtered;
		}

		return $ts;
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
