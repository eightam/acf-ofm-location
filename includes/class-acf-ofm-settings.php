<?php
/**
 * Settings page for ACF OpenFreeMap Location Field
 *
 * @package ACF_OFM_Location
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Settings class
 */
class ACF_OFM_Location_Settings {

	/**
	 * Option name
	 */
	const OPTION_NAME = 'acf_ofm_location_settings';

	/**
	 * Constructor
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'add_settings_page' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
	}

	/**
	 * Add settings page to ACF menu
	 */
	public function add_settings_page() {
		add_submenu_page(
			'edit.php?post_type=acf-field-group',
			__( 'Location Field Settings', 'acf-ofm-location' ),
			__( 'Location Settings', 'acf-ofm-location' ),
			'manage_options',
			'acf-ofm-location-settings',
			array( $this, 'render_settings_page' )
		);
	}

	/**
	 * Register settings
	 */
	public function register_settings() {
		register_setting(
			'acf_ofm_location_settings',
			self::OPTION_NAME,
			array( $this, 'sanitize_settings' )
		);

		// General settings section
		add_settings_section(
			'acf_ofm_location_general',
			__( 'General Settings', 'acf-ofm-location' ),
			array( $this, 'general_section_callback' ),
			'acf-ofm-location-settings'
		);

		// Geocoding API
		add_settings_field(
			'geocoding_api',
			__( 'Geocoding API', 'acf-ofm-location' ),
			array( $this, 'geocoding_api_callback' ),
			'acf-ofm-location-settings',
			'acf_ofm_location_general'
		);

		// Style URL
		add_settings_field(
			'style_url',
			__( 'Map Style URL', 'acf-ofm-location' ),
			array( $this, 'style_url_callback' ),
			'acf-ofm-location-settings',
			'acf_ofm_location_general'
		);

		// Default latitude
		add_settings_field(
			'default_lat',
			__( 'Default Latitude', 'acf-ofm-location' ),
			array( $this, 'default_lat_callback' ),
			'acf-ofm-location-settings',
			'acf_ofm_location_general'
		);

		// Default longitude
		add_settings_field(
			'default_lng',
			__( 'Default Longitude', 'acf-ofm-location' ),
			array( $this, 'default_lng_callback' ),
			'acf-ofm-location-settings',
			'acf_ofm_location_general'
		);

		// Default zoom
		add_settings_field(
			'default_zoom',
			__( 'Default Zoom Level', 'acf-ofm-location' ),
			array( $this, 'default_zoom_callback' ),
			'acf-ofm-location-settings',
			'acf_ofm_location_general'
		);
	}

	/**
	 * General section callback
	 */
	public function general_section_callback() {
		echo '<p>' . esc_html__( 'Configure default settings for location fields. These can be overridden per field.', 'acf-ofm-location' ) . '</p>';
	}

	/**
	 * Geocoding API field callback
	 */
	public function geocoding_api_callback() {
		$options = get_option( self::OPTION_NAME, $this->get_defaults() );
		$value   = isset( $options['geocoding_api'] ) ? $options['geocoding_api'] : 'photon';
		?>
		<select name="<?php echo esc_attr( self::OPTION_NAME ); ?>[geocoding_api]">
			<option value="photon" <?php selected( $value, 'photon' ); ?>>Photon (photon.komoot.io)</option>
			<option value="nominatim" <?php selected( $value, 'nominatim' ); ?>>Nominatim (nominatim.openstreetmap.org)</option>
		</select>
		<p class="description"><?php esc_html_e( 'Select the geocoding service to use for address searches.', 'acf-ofm-location' ); ?></p>
		<?php
	}

	/**
	 * Style URL field callback
	 */
	public function style_url_callback() {
		$options = get_option( self::OPTION_NAME, $this->get_defaults() );
		$value   = isset( $options['style_url'] ) ? $options['style_url'] : 'https://tiles.openfreemap.org/styles/positron';
		?>
		<input type="url" name="<?php echo esc_attr( self::OPTION_NAME ); ?>[style_url]" value="<?php echo esc_attr( $value ); ?>" class="regular-text" />
		<p class="description"><?php esc_html_e( 'MapLibre GL style URL for the map tiles.', 'acf-ofm-location' ); ?></p>
		<?php
	}

	/**
	 * Default latitude field callback
	 */
	public function default_lat_callback() {
		$options = get_option( self::OPTION_NAME, $this->get_defaults() );
		$value   = isset( $options['default_lat'] ) ? $options['default_lat'] : '50.0';
		?>
		<input type="number" step="0.000001" name="<?php echo esc_attr( self::OPTION_NAME ); ?>[default_lat]" value="<?php echo esc_attr( $value ); ?>" class="regular-text" />
		<p class="description"><?php esc_html_e( 'Default center latitude (Europe: 50.0)', 'acf-ofm-location' ); ?></p>
		<?php
	}

	/**
	 * Default longitude field callback
	 */
	public function default_lng_callback() {
		$options = get_option( self::OPTION_NAME, $this->get_defaults() );
		$value   = isset( $options['default_lng'] ) ? $options['default_lng'] : '10.0';
		?>
		<input type="number" step="0.000001" name="<?php echo esc_attr( self::OPTION_NAME ); ?>[default_lng]" value="<?php echo esc_attr( $value ); ?>" class="regular-text" />
		<p class="description"><?php esc_html_e( 'Default center longitude (Europe: 10.0)', 'acf-ofm-location' ); ?></p>
		<?php
	}

	/**
	 * Default zoom field callback
	 */
	public function default_zoom_callback() {
		$options = get_option( self::OPTION_NAME, $this->get_defaults() );
		$value   = isset( $options['default_zoom'] ) ? $options['default_zoom'] : '6';
		?>
		<input type="number" step="1" min="0" max="20" name="<?php echo esc_attr( self::OPTION_NAME ); ?>[default_zoom]" value="<?php echo esc_attr( $value ); ?>" class="small-text" />
		<p class="description"><?php esc_html_e( 'Default zoom level (0-20)', 'acf-ofm-location' ); ?></p>
		<?php
	}

	/**
	 * Render settings page
	 */
	public function render_settings_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		?>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
			<form action="options.php" method="post">
				<?php
				settings_fields( 'acf_ofm_location_settings' );
				do_settings_sections( 'acf-ofm-location-settings' );
				submit_button( __( 'Save Settings', 'acf-ofm-location' ) );
				?>
			</form>
		</div>
		<?php
	}

	/**
	 * Sanitize settings
	 *
	 * @param array $input Input data.
	 * @return array Sanitized data.
	 */
	public function sanitize_settings( $input ) {
		$sanitized = array();

		// Sanitize geocoding API
		if ( isset( $input['geocoding_api'] ) && in_array( $input['geocoding_api'], array( 'photon', 'nominatim' ), true ) ) {
			$sanitized['geocoding_api'] = $input['geocoding_api'];
		} else {
			$sanitized['geocoding_api'] = 'photon';
		}

		// Sanitize style URL
		if ( isset( $input['style_url'] ) ) {
			$sanitized['style_url'] = esc_url_raw( $input['style_url'] );
		}

		// Sanitize default latitude
		if ( isset( $input['default_lat'] ) ) {
			$sanitized['default_lat'] = floatval( $input['default_lat'] );
		}

		// Sanitize default longitude
		if ( isset( $input['default_lng'] ) ) {
			$sanitized['default_lng'] = floatval( $input['default_lng'] );
		}

		// Sanitize default zoom
		if ( isset( $input['default_zoom'] ) ) {
			$sanitized['default_zoom'] = intval( $input['default_zoom'] );
		}

		/**
		 * Filter the sanitized settings before saving.
		 *
		 * @param array $sanitized The sanitized settings.
		 * @param array $input     The original input data.
		 */
		return apply_filters( 'acf_ofm_location/sanitize_settings', $sanitized, $input );
	}

	/**
	 * Get default settings
	 *
	 * @return array Default settings.
	 */
	public static function get_defaults() {
		return array(
			'geocoding_api' => 'photon',
			'style_url'     => 'https://tiles.openfreemap.org/styles/positron',
			'default_lat'   => 50.0,
			'default_lng'   => 10.0,
			'default_zoom'  => 6,
		);
	}

	/**
	 * Get a specific setting value
	 *
	 * @param string $key Setting key.
	 * @return mixed Setting value.
	 */
	public static function get_setting( $key ) {
		$options  = get_option( self::OPTION_NAME, self::get_defaults() );
		$defaults = self::get_defaults();

		$value = isset( $options[ $key ] ) ? $options[ $key ] : ( isset( $defaults[ $key ] ) ? $defaults[ $key ] : null );

		/**
		 * Filter a specific setting value.
		 *
		 * @param mixed  $value The setting value.
		 * @param string $key   The setting key.
		 */
		return apply_filters( 'acf_ofm_location/get_setting', $value, $key );
	}
}
