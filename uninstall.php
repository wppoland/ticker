<?php
/**
 * Ticker uninstall routine.
 *
 * Removes plugin options when the user deletes the plugin. Ticker does not
 * create custom tables; settings live in wp_options only.
 *
 * @package Ticker
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

delete_option( 'ticker_settings' );
delete_option( 'ticker_db_version' );
