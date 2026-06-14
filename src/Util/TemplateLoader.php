<?php
/**
 * Template loader utility.
 *
 * Resolves and includes templates with theme override support.
 * Lookup order: {theme}/ticker/{template}.php -> {plugin}/templates/{template}.php
 *
 * @package Ticker\Util
 */

declare(strict_types=1);

namespace Ticker\Util;

defined( 'ABSPATH' ) || exit;

use const Ticker\PLUGIN_DIR;

/**
 * Loads PHP templates with optional theme overrides.
 */
final class TemplateLoader {

	private const THEME_DIR = 'ticker';

	/**
	 * Include a template file, extracting $args as prefixed local variables.
	 *
	 * @param string               $template Template name (e.g. 'single-product/countdown').
	 * @param array<string, mixed> $args     Variables extracted into the template scope, prefixed with `ticker_`.
	 */
	public function include( string $template, array $args = array() ): void {
		$path = $this->locate( $template );

		if ( null === $path ) {
			return;
		}

		/**
		 * Filter template arguments before rendering.
		 *
		 * @param array<string, mixed> $args     Template arguments.
		 * @param string               $template Template name.
		 */
		$args = apply_filters( 'ticker/template/args', $args, $template );

		$ticker_scoped_args = array();
		foreach ( $args as $ticker_arg_key => $ticker_arg_value ) {
			if ( ! is_string( $ticker_arg_key ) || '' === $ticker_arg_key ) {
				continue;
			}
			$ticker_scoped_args[ str_starts_with( $ticker_arg_key, 'ticker_' ) ? $ticker_arg_key : 'ticker_' . $ticker_arg_key ] = $ticker_arg_value;
		}

		unset( $args, $ticker_arg_key, $ticker_arg_value );

		extract( $ticker_scoped_args, EXTR_SKIP ); // phpcs:ignore WordPress.PHP.DontExtract.extract_extract

		include $path;
	}

	/**
	 * Locate the absolute path of a template file, or return null if not found.
	 *
	 * @param string $template Template name without leading slash.
	 * @return string|null
	 */
	public function locate( string $template ): ?string {
		$template = ltrim( $template, '/' );

		if ( ! str_ends_with( $template, '.php' ) ) {
			$template .= '.php';
		}

		$theme_path = locate_template( self::THEME_DIR . '/' . $template );

		if ( '' !== $theme_path ) {
			/**
			 * Filter the resolved template path.
			 *
			 * @param string $theme_path Resolved path.
			 * @param string $template   Template name.
			 */
			return apply_filters( 'ticker/template/path', $theme_path, $template );
		}

		$plugin_path = PLUGIN_DIR . '/templates/' . $template;

		if ( file_exists( $plugin_path ) ) {
			/**
			 * Filter the resolved template path.
			 *
			 * @param string $plugin_path Resolved path.
			 * @param string $template    Template name.
			 */
			return apply_filters( 'ticker/template/path', $plugin_path, $template );
		}

		return null;
	}
}
