<?php
/**
 * Minimal dependency-injection container.
 *
 * @package Ticker
 */

declare(strict_types=1);

namespace Ticker;

defined( 'ABSPATH' ) || exit;

/**
 * Minimal dependency-injection container: lazy singletons.
 */
final class Container {

	/**
	 * Registered service factories, keyed by id.
	 *
	 * @var array<string, \Closure>
	 */
	private array $factories = array();

	/**
	 * Resolved singleton instances, keyed by id.
	 *
	 * @var array<string, mixed>
	 */
	private array $instances = array();

	/**
	 * Register a lazily-instantiated singleton factory.
	 *
	 * @param string   $id      Service id (typically a class name).
	 * @param \Closure $factory Factory returning the service instance.
	 */
	public function singleton( string $id, \Closure $factory ): void {
		$this->factories[ $id ] = $factory;
	}

	/**
	 * Resolve a service by id, instantiating it once and caching it.
	 *
	 * @param string $id Service id.
	 * @return mixed
	 * @throws \RuntimeException When the id is not registered.
	 */
	public function get( string $id ): mixed {
		if ( array_key_exists( $id, $this->instances ) ) {
			return $this->instances[ $id ];
		}

		if ( ! isset( $this->factories[ $id ] ) ) {
			throw new \RuntimeException( esc_html( sprintf( 'Service "%s" is not registered.', $id ) ) );
		}

		$this->instances[ $id ] = ( $this->factories[ $id ] )( $this );

		return $this->instances[ $id ];
	}

	/**
	 * Whether a service id is registered or already resolved.
	 *
	 * @param string $id Service id.
	 */
	public function has( string $id ): bool {
		return isset( $this->factories[ $id ] ) || array_key_exists( $id, $this->instances );
	}
}
