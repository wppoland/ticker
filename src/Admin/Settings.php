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

	private const OPTION = 'ticker_settings';
	private const PAGE   = 'ticker-settings';

	private const SECTION_BEHAVIOUR  = 'ticker_behaviour';
	private const SECTION_APPEARANCE = 'ticker_appearance';
	private const SECTION_PLACEMENT  = 'ticker_placement';

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
			__( 'Ticker Settings', 'plogins-ticker' ),
			__( 'Ticker', 'plogins-ticker' ),
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
	}

	/**
	 * Source choices, label keyed by stored value.
	 *
	 * @return array<string, string>
	 */
	private function source_choices(): array {
		return array(
			'sale'     => __( 'WooCommerce sale end date', 'plogins-ticker' ),
			'campaign' => __( 'Fixed campaign end date', 'plogins-ticker' ),
		);
	}

	/**
	 * Format choices, label keyed by stored value.
	 *
	 * @return array<string, string>
	 */
	private function format_choices(): array {
		return array(
			'dhms'    => __( 'Days : Hours : Minutes : Seconds', 'plogins-ticker' ),
			'hms'     => __( 'Hours : Minutes : Seconds', 'plogins-ticker' ),
			'compact' => __( 'Hours : Minutes (compact)', 'plogins-ticker' ),
		);
	}

	/**
	 * Placement choices, label keyed by stored value.
	 *
	 * @return array<string, string>
	 */
	private function placement_choices(): array {
		return array(
			'summary'      => __( 'Product summary (below price)', 'plogins-ticker' ),
			'before_cart'  => __( 'Before the add-to-cart form', 'plogins-ticker' ),
			'after_cart'   => __( 'After the add-to-cart form', 'plogins-ticker' ),
			'product_meta' => __( 'Product meta area', 'plogins-ticker' ),
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
			self::SECTION_BEHAVIOUR,
			__( 'When the countdown runs', 'plogins-ticker' ),
			static function (): void {
				echo '<div class="ticker-settings__intro">';
				echo '<h2>' . esc_html__( 'Create urgency with a live countdown', 'plogins-ticker' ) . '</h2>';
				echo '<p>' . esc_html__(
					'Show a ticking countdown to the end of a sale on your product pages. The timer is calculated on the server and counted down in the browser, no layout shift, no jQuery.',
					'plogins-ticker',
				) . '</p>';
				echo '</div>';
				echo '<p class="ticker-settings__section-note">' . esc_html__(
					'Decide whether the timer shows and where its end time comes from.',
					'plogins-ticker',
				) . '</p>';
			},
			self::PAGE,
		);

		add_settings_section(
			self::SECTION_APPEARANCE,
			__( 'How it reads', 'plogins-ticker' ),
			static function (): void {
				echo '<p class="ticker-settings__section-note">' . esc_html__(
					'Tune the wording and the level of detail shoppers see. Sensible defaults already work, change these only to match your store’s voice.',
					'plogins-ticker',
				) . '</p>';
			},
			self::PAGE,
		);

		add_settings_section(
			self::SECTION_PLACEMENT,
			__( 'Where it appears', 'plogins-ticker' ),
			static function (): void {
				echo '<p class="ticker-settings__section-note">' . esc_html__(
					'Choose the spot on the single product page that fits your theme’s layout.',
					'plogins-ticker',
				) . '</p>';
			},
			self::PAGE,
		);

		$fields = array(
			'enabled'         => array( __( 'Enable countdown', 'plogins-ticker' ), self::SECTION_BEHAVIOUR ),
			'source'          => array( __( 'Countdown source', 'plogins-ticker' ), self::SECTION_BEHAVIOUR ),
			'campaign_end'    => array( __( 'Campaign end date', 'plogins-ticker' ), self::SECTION_BEHAVIOUR ),
			'heading'         => array( __( 'Heading', 'plogins-ticker' ), self::SECTION_APPEARANCE ),
			'format'          => array( __( 'Time format', 'plogins-ticker' ), self::SECTION_APPEARANCE ),
			'expired_message' => array( __( 'Expired message', 'plogins-ticker' ), self::SECTION_APPEARANCE ),
			'placement'       => array( __( 'Placement', 'plogins-ticker' ), self::SECTION_PLACEMENT ),
		);

		foreach ( $fields as $id => $field ) {
			// The enabled checkbox supplies its own wrapping <label>; the rest
			// rely on WP wrapping the <th> title in <label for> via label_for.
			$args = 'enabled' === $id ? array() : array( 'label_for' => 'ticker_' . $id );

			add_settings_field(
				$id,
				$field[0],
				array( $this, 'render_' . $id ),
				self::PAGE,
				$field[1],
				$args,
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
			<?php esc_html_e( 'Show the sale countdown timer on single product pages.', 'plogins-ticker' ); ?>
		</label>
		<p class="description"><?php esc_html_e( 'Master switch. When off, no countdown is rendered anywhere.', 'plogins-ticker' ); ?></p>
		<?php
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
		<p class="description"><?php esc_html_e( 'Where the end time comes from. The sale date is read per product; the campaign date applies store-wide and is also used as a fallback when a product has no sale end date.', 'plogins-ticker' ); ?></p>
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
		<p class="description">
			<?php
			printf(
				/* translators: %s: the site timezone string, e.g. Europe/Warsaw or UTC+2. */
				esc_html__( 'Interpreted in your site timezone (%s).', 'plogins-ticker' ),
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
			placeholder="<?php esc_attr_e( 'e.g. Hurry, offer ends soon!', 'plogins-ticker' ); ?>"
		/>
		<p class="description"><?php esc_html_e( 'Optional copy shown directly above the timer. Leave blank to show just the clock.', 'plogins-ticker' ); ?></p>
		<?php
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
		<p class="description"><?php esc_html_e( 'How the remaining time is displayed. Compact drops seconds for a calmer look on multi-day campaigns.', 'plogins-ticker' ); ?></p>
		<p class="ticker-settings__example">
			<span class="ticker-settings__example-label"><?php esc_html_e( 'Looks like:', 'plogins-ticker' ); ?></span>
			<?php
			$samples = array(
				'dhms'    => '02 : 18 : 45 : 09',
				'hms'     => '18 : 45 : 09',
				'compact' => '18 : 45',
			);
			$sample  = $samples[ $current ] ?? $samples['dhms'];
			?>
			<code class="ticker-settings__sample"><?php echo esc_html( $sample ); ?></code>
		</p>
		<?php
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
		<p class="description"><?php esc_html_e( 'Where the timer is inserted on the single product page.', 'plogins-ticker' ); ?></p>
		<?php
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
			placeholder="<?php esc_attr_e( 'This sale has ended.', 'plogins-ticker' ); ?>"
		/>
		<p class="description"><?php esc_html_e( 'Shown in place of the clock once the countdown reaches zero. Leave blank for the default message.', 'plogins-ticker' ); ?></p>
		<?php
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
			<p class="ticker-settings__lede"><?php esc_html_e( 'A live sale countdown for your product pages. The defaults below work out of the box, adjust only what you need.', 'plogins-ticker' ); ?></p>
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

		$sanitized = array(
			'enabled'         => ! empty( $raw['enabled'] ),
			'source'          => $source,
			'campaign_end'    => $campaign_end,
			'heading'         => sanitize_text_field( (string) ( $raw['heading'] ?? '' ) ),
			'format'          => $format,
			'placement'       => $placement,
			'expired_message' => sanitize_text_field( (string) ( $raw['expired_message'] ?? '' ) ),
		);

		return apply_filters( 'ticker_settings_sanitize', $sanitized, $raw );
	}
}
