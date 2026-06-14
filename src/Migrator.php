<?php
/**
 * Idempotent schema/version migrations.
 *
 * @package Ticker
 */

declare(strict_types=1);

namespace Ticker;

defined( 'ABSPATH' ) || exit;

/**
 * Idempotent schema/version migrations, run on every boot. Compares a stored
 * option against VERSION and applies forward steps as needed.
 */
final class Migrator {

	private const OPTION = 'ticker_db_version';

	/**
	 * Run any pending forward migrations, then record the current version.
	 */
	public function maybeMigrate(): void {
		$current = (string) get_option( self::OPTION, '0' );

		if ( version_compare( $current, VERSION, '>=' ) ) {
			return;
		}

		// Future migrations (create tables / seed defaults) run here.

		update_option( self::OPTION, VERSION, false );
	}
}
