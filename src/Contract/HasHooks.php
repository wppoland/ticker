<?php
/**
 * HasHooks contract.
 *
 * @package Ticker\Contract
 */

declare(strict_types=1);

namespace Ticker\Contract;

defined( 'ABSPATH' ) || exit;

/**
 * A service that registers its own WordPress hooks during boot.
 */
interface HasHooks {

	/**
	 * Register the service's WordPress hooks.
	 */
	public function registerHooks(): void;
}
