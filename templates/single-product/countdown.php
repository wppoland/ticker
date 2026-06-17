<?php
/**
 * Sale countdown timer template.
 *
 * @var int|null $ticker_end_ts          End timestamp (UTC), or null when no countdown.
 * @var string   $ticker_format          Display format: dhms|hms|compact.
 * @var string   $ticker_heading         Optional heading above the timer.
 * @var string   $ticker_expired_message Message shown when the sale has ended.
 * @var int      $ticker_now             Current server timestamp (UTC).
 *
 * @package Ticker/Templates
 */

declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Template-scoped variables.

$ticker_end_ts          = isset( $ticker_end_ts ) && is_int( $ticker_end_ts ) ? $ticker_end_ts : null;
$ticker_format          = isset( $ticker_format ) ? (string) $ticker_format : 'dhms';
$ticker_heading         = isset( $ticker_heading ) ? (string) $ticker_heading : '';
$ticker_expired_message = isset( $ticker_expired_message ) ? (string) $ticker_expired_message : '';
$ticker_now             = isset( $ticker_now ) ? (int) $ticker_now : time();

if ( null === $ticker_end_ts ) {
	return;
}

$ticker_is_expired = $ticker_end_ts <= $ticker_now;

// Presentation only: seed the stopwatch sweep ring from the server clock so
// the seconds unit paints its arc on first frame, before the script runs.
$ticker_remaining    = max( 0, $ticker_end_ts - $ticker_now );
$ticker_sweep_deg    = (int) round( ( ( $ticker_remaining % 60 ) / 60 ) * 360 );
$ticker_show_seconds = 'compact' !== $ticker_format;
?>
<div class="ticker" role="group" aria-label="<?php esc_attr_e( 'Sale countdown', 'ticker' ); ?>">
	<div
		class="ticker__countdown<?php echo $ticker_is_expired ? ' is-expired' : ''; ?>"
		data-ticker-end="<?php echo esc_attr( (string) $ticker_end_ts ); ?>"
		data-ticker-format="<?php echo esc_attr( $ticker_format ); ?>"
		data-ticker-expired-text="<?php echo esc_attr( $ticker_expired_message ); ?>"
	>
		<?php if ( '' !== $ticker_heading ) : ?>
			<p class="ticker__heading"><?php echo esc_html( $ticker_heading ); ?></p>
		<?php endif; ?>

		<p class="ticker__expired" hidden<?php echo $ticker_is_expired ? ' data-active="1"' : ''; ?>>
			<?php echo esc_html( $ticker_expired_message ); ?>
		</p>

		<?php
		/*
		 * The visual timer ticks every second; announcing it would create
		 * per-second screen-reader noise. So the visible digits are hidden from
		 * assistive tech (aria-hidden) and a separate polite live region
		 * (.ticker__sr-status, a sibling of the clock so it survives the clock
		 * being hidden on expiry) announces only on a meaningful change: each
		 * minute boundary, and on expiry. %s is the remaining-time phrase, e.g.
		 * "2 hrs 5 min", assembled by the script from the unit labels.
		 */
		?>
		<span
			class="ticker__sr-status"
			role="status"
			aria-live="polite"
			data-ticker-status
			data-ticker-announce-template="<?php echo esc_attr( /* translators: %s is the remaining sale time, e.g. "2 hrs 5 min". */ __( 'Sale ends in %s', 'ticker' ) ); ?>"
		></span>

		<div class="ticker__clock"<?php echo $ticker_is_expired ? ' hidden' : ''; ?>>
			<span class="ticker__lead"><?php esc_html_e( 'Sale ends in', 'ticker' ); ?></span>
			<span class="ticker__units" role="timer" aria-hidden="true">
				<span class="ticker__unit ticker__unit--days"<?php echo 'dhms' === $ticker_format ? '' : ' hidden'; ?>>
					<span class="ticker__value" data-ticker-days>--</span>
					<span class="ticker__label"><?php esc_html_e( 'days', 'ticker' ); ?></span>
				</span>
				<span class="ticker__sep" aria-hidden="true"<?php echo 'dhms' === $ticker_format ? '' : ' hidden'; ?>>:</span>
				<span class="ticker__unit ticker__unit--hours">
					<span class="ticker__value" data-ticker-hours>--</span>
					<span class="ticker__label"><?php esc_html_e( 'hrs', 'ticker' ); ?></span>
				</span>
				<span class="ticker__sep" aria-hidden="true">:</span>
				<span class="ticker__unit ticker__unit--minutes">
					<span class="ticker__value" data-ticker-minutes>--</span>
					<span class="ticker__label"><?php esc_html_e( 'min', 'ticker' ); ?></span>
				</span>
				<span class="ticker__sep" aria-hidden="true"<?php echo 'compact' === $ticker_format ? ' hidden' : ''; ?>>:</span>
				<span class="ticker__unit ticker__unit--seconds"<?php echo $ticker_show_seconds ? ' style="--ticker-ring:' . esc_attr( (string) $ticker_sweep_deg ) . 'deg"' : ' hidden'; ?>>
					<span class="ticker__value" data-ticker-seconds>--</span>
					<span class="ticker__label"><?php esc_html_e( 'sec', 'ticker' ); ?></span>
				</span>
			</span>
		</div>
	</div>
</div>
<?php
// phpcs:enable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
