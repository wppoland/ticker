<?php
/**
 * Admin settings page for Ticker.
 *
 * @package Ticker\Admin
 */

declare(strict_types=1);

namespace Ticker\Admin;

defined( 'ABSPATH' ) || exit;

use Ticker\Contract\HasHooks;
use Ticker\Service\CountdownService;

/**
 * Admin settings page for Ticker, registered under the WooCommerce menu.
 *
 * Settings are stored in `ticker_settings` (array).
 *
 * @package Ticker\Admin
 */
final class Settings implements HasHooks {

	private const OPTION  = 'ticker_settings';
	private const PAGE    = 'ticker-settings';
	private const SECTION = 'ticker_general';

	/**
	 * Register WordPress hooks.
	 */
	public function registerHooks(): void {
		add_action( 'admin_menu', array( $this, 'add_menu_page' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
	}

	/**
	 * Register the WooCommerce submenu page.
	 */
	public function add_menu_page(): void {
		add_submenu_page(
			'woocommerce',
			__( 'Ticker Settings', 'ticker' ),
			__( 'Ticker', 'ticker' ),
			'manage_woocommerce',
			self::PAGE,
			array( $this, 'render_page' ),
		);
	}

	/**
	 * Enqueue the admin stylesheet on the settings page only.
	 *
	 * @param string $hook The current admin page hook suffix.
	 */
	public function enqueue_admin_assets( string $hook ): void {
		if ( false === strpos( $hook, self::PAGE ) ) {
			return;
		}

		wp_enqueue_style(
			'ticker-admin',
			\Ticker\Plugin::instance()->url( 'assets/css/admin.css' ),
			array(),
			\Ticker\VERSION,
		);

		wp_enqueue_script(
			'ticker-admin',
			\Ticker\Plugin::instance()->url( 'assets/js/admin-ticker.js' ),
			array(),
			\Ticker\VERSION,
			array(
				'in_footer' => true,
				'strategy'  => 'defer',
			),
		);
	}

	/**
	 * Render an accessible inline help affordance ("?") paired with a tooltip.
	 *
	 * @param string $id   Unique tooltip id (also the popover target).
	 * @param string $text Help text.
	 */
	private function help_icon( string $id, string $text ): void {
		printf(
			'<button type="button" class="ticker-help" popovertarget="%1$s" aria-label="%2$s">?</button>',
			esc_attr( $id ),
			esc_attr__( 'More information', 'ticker' ),
		);
		printf(
			'<span id="%1$s" class="ticker-tip" role="tooltip" popover="auto">%2$s</span>',
			esc_attr( $id ),
			esc_html( $text ),
		);
	}

	/**
	 * Source choices, label keyed by stored value.
	 *
	 * @return array<string, string>
	 */
	private function source_choices(): array {
		return array(
			'sale'     => __( 'WooCommerce sale end date', 'ticker' ),
			'campaign' => __( 'Fixed campaign end date', 'ticker' ),
		);
	}

	/**
	 * Format choices, label keyed by stored value.
	 *
	 * @return array<string, string>
	 */
	private function format_choices(): array {
		return array(
			'dhms'    => __( 'Days : Hours : Minutes : Seconds', 'ticker' ),
			'hms'     => __( 'Hours : Minutes : Seconds', 'ticker' ),
			'compact' => __( 'Hours : Minutes (compact)', 'ticker' ),
		);
	}

	/**
	 * Placement choices, label keyed by stored value.
	 *
	 * @return array<string, string>
	 */
	private function placement_choices(): array {
		return array(
			'summary'      => __( 'Product summary (below price)', 'ticker' ),
			'before_cart'  => __( 'Before the add-to-cart form', 'ticker' ),
			'after_cart'   => __( 'After the add-to-cart form', 'ticker' ),
			'product_meta' => __( 'Product meta area', 'ticker' ),
		);
	}

	/**
	 * Register the settings, sections, and fields.
	 */
	public function register_settings(): void {
		register_setting(
			self::PAGE,
			self::OPTION,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( $this, 'sanitize' ),
			),
		);

		add_settings_section(
			self::SECTION,
			__( 'Sale Countdown Timer', 'ticker' ),
			static function (): void {
				echo '<div class="ticker-settings__intro">';
				echo '<h2>' . esc_html__( 'Create urgency with a live countdown', 'ticker' ) . '</h2>';
				echo '<p>' . esc_html__(
					'Show a ticking countdown to the end of a sale on your product pages, plus an optional "Only N left" scarcity nudge. The timer is calculated on the server and counted down in the browser — no layout shift, no jQuery.',
					'ticker',
				) . '</p>';
				echo '</div>';
			},
			self::PAGE,
		);

		$fields = array(
			'enabled'            => __( 'Enable countdown', 'ticker' ),
			'source'             => __( 'Countdown source', 'ticker' ),
			'campaign_end'       => __( 'Campaign end date', 'ticker' ),
			'heading'            => __( 'Heading', 'ticker' ),
			'format'             => __( 'Time format', 'ticker' ),
			'placement'          => __( 'Placement', 'ticker' ),
			'expired_message'    => __( 'Expired message', 'ticker' ),
			'scarcity_enabled'   => __( 'Stock scarcity message', 'ticker' ),
			'scarcity_threshold' => __( 'Scarcity threshold', 'ticker' ),
		);

		foreach ( $fields as $id => $label ) {
			add_settings_field(
				$id,
				$label,
				array( $this, 'render_' . $id ),
				self::PAGE,
				self::SECTION,
			);
		}
	}

	/**
	 * Read a single setting with the configured default applied.
	 *
	 * @param string $key Setting key.
	 * @return mixed
	 */
	private function value( string $key ): mixed {
		$defaults = require \Ticker\PLUGIN_DIR . '/config/defaults.php';
		$stored   = get_option( self::OPTION, array() );
		$merged   = array_merge( $defaults, is_array( $stored ) ? $stored : array() );

		return $merged[ $key ] ?? null;
	}

	/**
	 * Render the enabled checkbox.
	 */
	public function render_enabled(): void {
		$checked = (bool) $this->value( 'enabled' );
		?>
		<label for="ticker_enabled">
			<input type="checkbox" id="ticker_enabled" name="<?php echo esc_attr( self::OPTION ); ?>[enabled]" value="1" <?php checked( $checked, true ); ?> />
			<?php esc_html_e( 'Show the sale countdown timer on single product pages.', 'ticker' ); ?>
		</label>
		<?php
		$this->help_icon( 'ticker-tip-enabled', __( 'Master switch. When off, no countdown or scarcity message is rendered anywhere.', 'ticker' ) );
	}

	/**
	 * Render the source select.
	 */
	public function render_source(): void {
		$current = (string) $this->value( 'source' );
		?>
		<select id="ticker_source" name="<?php echo esc_attr( self::OPTION ); ?>[source]">
			<?php foreach ( $this->source_choices() as $value => $label ) : ?>
				<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $current, $value ); ?>><?php echo esc_html( $label ); ?></option>
			<?php endforeach; ?>
		</select>
		<?php
		$this->help_icon( 'ticker-tip-source', __( 'Use the native WooCommerce "Sale price dates" of each product, or count down to one fixed campaign date for the whole store. A per-product campaign date always takes priority when set.', 'ticker' ) );
		?>
		<p class="description"><?php esc_html_e( 'Where the end time comes from. The sale date is read per product; the campaign date applies store-wide.', 'ticker' ); ?></p>
		<?php
	}

	/**
	 * Render the campaign end datetime field.
	 */
	public function render_campaign_end(): void {
		$value = (string) $this->value( 'campaign_end' );
		?>
		<input
			type="datetime-local"
			id="ticker_campaign_end"
			name="<?php echo esc_attr( self::OPTION ); ?>[campaign_end]"
			value="<?php echo esc_attr( $value ); ?>"
		/>
		<?php
		$this->help_icon( 'ticker-tip-campaign', __( 'The store-wide campaign end, in your site timezone. Used when "Fixed campaign end date" is selected, or as a fallback when a product has no sale end date.', 'ticker' ) );
		?>
		<p class="description">
			<?php
			printf(
				/* translators: %s: the site timezone string, e.g. Europe/Warsaw or UTC+2. */
				esc_html__( 'Interpreted in your site timezone (%s).', 'ticker' ),
				esc_html( wp_timezone_string() ),
			);
			?>
		</p>
		<?php
	}

	/**
	 * Render the heading text field.
	 */
	public function render_heading(): void {
		$value = (string) $this->value( 'heading' );
		?>
		<input
			type="text"
			id="ticker_heading"
			name="<?php echo esc_attr( self::OPTION ); ?>[heading]"
			value="<?php echo esc_attr( $value ); ?>"
			class="regular-text"
			placeholder="<?php esc_attr_e( 'e.g. Hurry — offer ends soon!', 'ticker' ); ?>"
		/>
		<?php
		$this->help_icon( 'ticker-tip-heading', __( 'Optional copy shown directly above the timer. Leave blank to show just the clock.', 'ticker' ) );
	}

	/**
	 * Render the format select.
	 */
	public function render_format(): void {
		$current = (string) $this->value( 'format' );
		?>
		<select id="ticker_format" name="<?php echo esc_attr( self::OPTION ); ?>[format]">
			<?php foreach ( $this->format_choices() as $value => $label ) : ?>
				<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $current, $value ); ?>><?php echo esc_html( $label ); ?></option>
			<?php endforeach; ?>
		</select>
		<?php
		$this->help_icon( 'ticker-tip-format', __( 'How the remaining time is displayed. Compact hides seconds for a calmer look on long campaigns.', 'ticker' ) );
	}

	/**
	 * Render the placement select.
	 */
	public function render_placement(): void {
		$current = (string) $this->value( 'placement' );
		?>
		<select id="ticker_placement" name="<?php echo esc_attr( self::OPTION ); ?>[placement]">
			<?php foreach ( $this->placement_choices() as $value => $label ) : ?>
				<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $current, $value ); ?>><?php echo esc_html( $label ); ?></option>
			<?php endforeach; ?>
		</select>
		<?php
		$this->help_icon( 'ticker-tip-placement', __( 'Where the timer is inserted on the single product page.', 'ticker' ) );
	}

	/**
	 * Render the expired message field.
	 */
	public function render_expired_message(): void {
		$value = (string) $this->value( 'expired_message' );
		?>
		<input
			type="text"
			id="ticker_expired_message"
			name="<?php echo esc_attr( self::OPTION ); ?>[expired_message]"
			value="<?php echo esc_attr( $value ); ?>"
			class="regular-text"
			placeholder="<?php esc_attr_e( 'This sale has ended.', 'ticker' ); ?>"
		/>
		<?php
		$this->help_icon( 'ticker-tip-expired', __( 'Shown in place of the clock once the countdown reaches zero. Leave blank for the default message.', 'ticker' ) );
	}

	/**
	 * Render the scarcity enabled checkbox.
	 */
	public function render_scarcity_enabled(): void {
		$checked = (bool) $this->value( 'scarcity_enabled' );
		?>
		<label for="ticker_scarcity_enabled">
			<input type="checkbox" id="ticker_scarcity_enabled" name="<?php echo esc_attr( self::OPTION ); ?>[scarcity_enabled]" value="1" <?php checked( $checked, true ); ?> />
			<?php esc_html_e( 'Show an "Only N left in stock" message when stock is low.', 'ticker' ); ?>
		</label>
		<?php
		$this->help_icon( 'ticker-tip-scarcity', __( 'Only shows for products that track stock and whose remaining quantity is at or below the threshold below.', 'ticker' ) );
	}

	/**
	 * Render the scarcity threshold field.
	 */
	public function render_scarcity_threshold(): void {
		$value = (int) $this->value( 'scarcity_threshold' );
		?>
		<input
			type="number"
			id="ticker_scarcity_threshold"
			name="<?php echo esc_attr( self::OPTION ); ?>[scarcity_threshold]"
			value="<?php echo esc_attr( (string) $value ); ?>"
			min="1"
			max="999"
			step="1"
			class="small-text"
		/>
		<?php
		$this->help_icon( 'ticker-tip-threshold', __( 'The scarcity message appears when remaining stock is this number or fewer (e.g. 5 means it shows at 5, 4, 3, 2 or 1 left).', 'ticker' ) );
	}

	/**
	 * Render the settings page.
	 */
	public function render_page(): void {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}
		?>
		<div class="wrap ticker-settings">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
			<form method="post" action="options.php">
				<?php
				settings_fields( self::PAGE );
				do_settings_sections( self::PAGE );
				submit_button();
				?>
			</form>
		</div>
		<?php
	}

	/**
	 * Sanitise and normalise the incoming settings array before saving.
	 *
	 * @param mixed $raw Raw POST data.
	 * @return array<string, mixed>
	 */
	public function sanitize( mixed $raw ): array {
		if ( ! is_array( $raw ) ) {
			return array();
		}

		$source = isset( $raw['source'] ) ? sanitize_key( (string) $raw['source'] ) : 'sale';
		if ( ! array_key_exists( $source, $this->source_choices() ) ) {
			$source = 'sale';
		}

		$format = isset( $raw['format'] ) ? sanitize_key( (string) $raw['format'] ) : 'dhms';
		if ( ! array_key_exists( $format, $this->format_choices() ) ) {
			$format = 'dhms';
		}

		$placement = isset( $raw['placement'] ) ? sanitize_key( (string) $raw['placement'] ) : 'summary';
		if ( ! array_key_exists( $placement, $this->placement_choices() ) ) {
			$placement = 'summary';
		}

		$campaign_end = sanitize_text_field( (string) ( $raw['campaign_end'] ?? '' ) );
		// Accept only the datetime-local shape; otherwise discard.
		if ( '' !== $campaign_end && 1 !== preg_match( '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}$/', $campaign_end ) ) {
			$campaign_end = '';
		}

		$threshold = absint( $raw['scarcity_threshold'] ?? 5 );
		if ( $threshold < 1 ) {
			$threshold = 1;
		}
		if ( $threshold > 999 ) {
			$threshold = 999;
		}

		return array(
			'enabled'            => ! empty( $raw['enabled'] ),
			'source'             => $source,
			'campaign_end'       => $campaign_end,
			'heading'            => sanitize_text_field( (string) ( $raw['heading'] ?? '' ) ),
			'format'             => $format,
			'placement'          => $placement,
			'expired_message'    => sanitize_text_field( (string) ( $raw['expired_message'] ?? '' ) ),
			'scarcity_enabled'   => ! empty( $raw['scarcity_enabled'] ),
			'scarcity_threshold' => $threshold,
		);
	}
}
