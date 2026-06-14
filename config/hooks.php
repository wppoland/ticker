<?php
/**
 * Boot order: services listed here are resolved from the container and have
 * their registerHooks() called during Plugin::boot(). Each must implement
 * Ticker\Contract\HasHooks. Admin-only services are appended only in wp-admin.
 *
 * @package Ticker
 *
 * @return array<class-string>
 */

declare(strict_types=1);

use Ticker\Admin\Settings;
use Ticker\Service\CountdownService;

defined( 'ABSPATH' ) || exit;

$ticker_hooks = array(
	CountdownService::class,
);

if ( is_admin() ) {
	$ticker_hooks[] = Settings::class;
}

return $ticker_hooks;
