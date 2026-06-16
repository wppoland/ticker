<?php
/**
 * Plugin Name:       Ticker - Sales Countdown Timer for WooCommerce
 * Plugin URI:        https://plogins.com/ticker/
 * Description:        Show a live sale countdown timer on WooCommerce product pages to create urgency and turn browsers into buyers.
 * Version:           0.1.1
 * Requires at least: 6.5
 * Requires PHP:      8.1
 * Requires Plugins:  woocommerce
 * Author:            WPPoland
 * Author URI:        https://plogins.com/
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       ticker
 * Domain Path:       /languages
 * WC requires at least: 8.0
 *
 * @package Ticker
 */

declare(strict_types=1);

namespace Ticker;

defined( 'ABSPATH' ) || exit;

const VERSION     = '0.1.1';
const PLUGIN_FILE = __FILE__;
const PLUGIN_DIR  = __DIR__;

define( 'TICKER_DIR', plugin_dir_path( __FILE__ ) );
define( 'TICKER_URL', plugin_dir_url( __FILE__ ) );

require_once __DIR__ . '/autoload.php';

// HPOS + cart/checkout blocks compatibility.
add_action(
	'before_woocommerce_init',
	static function (): void {
		if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'cart_checkout_blocks', __FILE__, true );
		}
	}
);

add_action(
	'plugins_loaded',
	static function (): void {
		if ( ! class_exists( 'WooCommerce' ) ) {
			add_action(
				'admin_notices',
				static function (): void {
					echo '<div class="notice notice-error"><p>';
					echo esc_html__( 'Ticker - Sales Countdown Timer for WooCommerce requires WooCommerce to be active.', 'ticker' );
					echo '</p></div>';
				}
			);
			return;
		}

		// Boot on init:0 so translation calls are never made at plugins_loaded scope
		// (avoids the WP 6.7 _load_textdomain_just_in_time notice). The plugin fires
		// ticker/booted from inside Plugin::boot().
		add_action(
			'init',
			static function (): void {
				Plugin::instance()->boot();
			},
			0
		);
	},
	10
);
