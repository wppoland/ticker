<?php
/**
 * Sale countdown timer template.
 *
 * @var int|null                              $ticker_end_ts          End timestamp (UTC), or null when no countdown.
 * @var string                                $ticker_format          Display format: dhms|hms|compact.
 * @var string                                $ticker_heading         Optional heading above the timer.
 * @var string                                $ticker_expired_message Message shown when the sale has ended.
 * @var array{stock: int, message: string}|null $ticker_scarcity      Scarcity data, or null.
 * @var int                                    $ticker_now             Current server timestamp (UTC).
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
$ticker_scarcity        = isset( $ticker_scarcity ) && is_array( $ticker_scarcity ) ? $ticker_scarcity : null;
$ticker_now             = isset( $ticker_now ) ? (int) $ticker_now : time();

$ticker_has_countdown = null !== $ticker_end_ts;
$ticker_is_expired    = $ticker_has_countdown && $ticker_end_ts <= $ticker_now;

if ( ! $ticker_has_countdown && null === $ticker_scarcity ) {
	return;
}
?>
<div class="ticker" role="group" aria-label="<?php esc_attr_e( 'Sale countdown', 'ticker' ); ?>">
	<?php if ( $ticker_has_countdown ) : ?>
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

			<div class="ticker__clock" aria-live="off"<?php echo $ticker_is_expired ? ' hidden' : ''; ?>>
				<span class="ticker__lead"><?php esc_html_e( 'Sale ends in', 'ticker' ); ?></span>
				<span class="ticker__units" role="timer" aria-live="polite">
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
					<span class="ticker__unit ticker__unit--seconds"<?php echo 'compact' === $ticker_format ? ' hidden' : ''; ?>>
						<span class="ticker__value" data-ticker-seconds>--</span>
						<span class="ticker__label"><?php esc_html_e( 'sec', 'ticker' ); ?></span>
					</span>
				</span>
			</div>
		</div>
	<?php endif; ?>

	<?php if ( null !== $ticker_scarcity ) : ?>
		<p class="ticker__scarcity" role="status">
			<span class="ticker__scarcity-dot" aria-hidden="true"></span>
			<?php echo esc_html( (string) $ticker_scarcity['message'] ); ?>
		</p>
	<?php endif; ?>
</div>
<?php
// phpcs:enable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
