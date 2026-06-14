<?php
/**
 * Autoloading: prefer Composer's vendor autoloader (the optimized classmap).
 * Fall back to a minimal PSR-4 autoloader so the plugin still boots if vendor/
 * is somehow absent (e.g. installed from a packaged ZIP without Composer).
 *
 * @package Ticker
 */

declare(strict_types=1);

namespace Ticker;

defined( 'ABSPATH' ) || exit;

$ticker_composer = __DIR__ . '/vendor/autoload.php';
if ( is_readable( $ticker_composer ) ) {
	require_once $ticker_composer;
	return;
}

spl_autoload_register(
	static function ( string $class_name ): void {
		$prefix   = 'Ticker\\';
		$base_dir = __DIR__ . '/src/';

		$len = strlen( $prefix );
		if ( strncmp( $prefix, $class_name, $len ) !== 0 ) {
			return;
		}

		$relative = substr( $class_name, $len );
		$file     = $base_dir . str_replace( '\\', '/', $relative ) . '.php';
		if ( is_readable( $file ) ) {
			require_once $file;
		}
	}
);
