<?php
/**
 * Service wiring. Returns a closure that registers every service into the
 * container. Bindings are lazy; admin services are guarded by is_admin().
 *
 * @package Ticker
 */

declare(strict_types=1);

use Ticker\Admin\Settings;
use Ticker\Container;
use Ticker\Migrator;
use Ticker\Service\CountdownService;
use Ticker\Util\TemplateLoader;

defined( 'ABSPATH' ) || exit;

return static function ( Container $c ): void {
	$c->singleton( Migrator::class, static fn (): Migrator => new Migrator() );

	$c->singleton( TemplateLoader::class, static fn (): TemplateLoader => new TemplateLoader() );

	$c->singleton(
		CountdownService::class,
		static fn (): CountdownService => new CountdownService(
			$c->get( TemplateLoader::class ),
		)
	);

	if ( is_admin() ) {
		$c->singleton(
			Settings::class,
			static fn (): Settings => new Settings(),
		);
	}
};
