<?php
/**
 * Main plugin orchestrator.
 *
 * @package Ticker
 */

declare(strict_types=1);

namespace Ticker;

use Ticker\Contract\HasHooks;

defined( 'ABSPATH' ) || exit;

/**
 * Plugin singleton: wires the DI container and boots every HasHooks service
 * listed in config/hooks.php, then fires `ticker/booted` so the PRO companion
 * can extend without modifying core files.
 */
final class Plugin {

	/**
	 * Shared singleton instance.
	 *
	 * @var Plugin|null
	 */
	private static ?self $instance = null;

	/**
	 * Dependency injection container.
	 *
	 * @var Container
	 */
	private Container $container;

	/**
	 * Whether the plugin has already booted.
	 *
	 * @var bool
	 */
	private bool $booted = false;

	/**
	 * Private constructor — use Plugin::instance().
	 */
	private function __construct() {
		$this->container = new Container();
		( require PLUGIN_DIR . '/config/services.php' )( $this->container );
	}

	/**
	 * Returns the shared plugin instance, creating it on first call.
	 */
	public static function instance(): self {
		return self::$instance ??= new self();
	}

	/**
	 * Returns the DI container.
	 */
	public function container(): Container {
		return $this->container;
	}

	/**
	 * Absolute filesystem path to the plugin directory (optional segment appended).
	 *
	 * @param string $relative Optional relative path to append.
	 */
	public function path( string $relative = '' ): string {
		return PLUGIN_DIR . ( '' !== $relative ? '/' . ltrim( $relative, '/' ) : '' );
	}

	/**
	 * URL to a plugin asset.
	 *
	 * @param string $relative Relative asset path.
	 */
	public function url( string $relative = '' ): string {
		return plugins_url( $relative, PLUGIN_FILE );
	}

	/**
	 * Boot the plugin: load i18n, run migrations, register hook subscribers.
	 */
	public function boot(): void {
		if ( $this->booted ) {
			return;
		}
		$this->booted = true;

		load_plugin_textdomain( 'ticker', false, dirname( plugin_basename( PLUGIN_FILE ) ) . '/languages' );

		$this->container->get( Migrator::class )->maybeMigrate();

		/**
		 * List of HasHooks service class names to boot.
		 *
		 * @var array<class-string<HasHooks>> $hooks
		 */
		$hooks = require PLUGIN_DIR . '/config/hooks.php';
		foreach ( $hooks as $id ) {
			if ( ! $this->container->has( $id ) ) {
				continue;
			}
			$service = $this->container->get( $id );
			if ( $service instanceof HasHooks ) {
				$service->registerHooks();
			}
		}

		/**
		 * Fires after the plugin has fully booted.
		 *
		 * PRO companions hook here to extend without modifying core files.
		 *
		 * @param Plugin $plugin The booted plugin instance.
		 */
		do_action( 'ticker/booted', $this );
	}
}
