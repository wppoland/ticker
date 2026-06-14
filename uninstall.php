<?php
/**
 * Ticker uninstall routine.
 *
 * Removes plugin options when the user deletes the plugin. Ticker does not
 * create custom tables; settings live in wp_options only. Per-product campaign
 * end dates are stored as post meta and removed here too.
 *
 * @package Ticker
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

delete_option( 'ticker_settings' );
delete_option( 'ticker_db_version' );

// Remove per-product campaign end meta (set by Ticker PRO).
delete_post_meta_by_key( '_ticker_campaign_end' );
