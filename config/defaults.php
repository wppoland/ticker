<?php
/**
 * Default settings, stored under the option key `ticker_settings`.
 *
 * @package Ticker
 *
 * @return array<string, mixed>
 */

declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

return array(
	'enabled'            => true,
	// 'sale' uses the WooCommerce sale end date; 'campaign' uses a fixed date set below.
	'source'             => 'sale',
	// Fixed campaign end (Y-m-d\TH:i, store timezone). Used when source = campaign or as fallback.
	'campaign_end'       => '',
	// Heading shown above the countdown. Empty hides it.
	'heading'            => '',
	// Display format: 'dhms' = DD:HH:MM:SS, 'hms' = HH:MM:SS, 'compact' = humanised.
	'format'             => 'dhms',
	// Placement hook key (see CountdownService::placements()).
	'placement'          => 'summary',
	// Message shown when the countdown reaches zero.
	'expired_message'    => '',
	// Stock scarcity message.
	'scarcity_enabled'   => false,
	'scarcity_threshold' => 5,
);
