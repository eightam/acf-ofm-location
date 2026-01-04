<?php
/**
 * Plugin Name: ACF OpenFreeMap Location Field
 * Plugin URI: https://github.com/eightam/acf-ofm-location
 * Description: A simple location field for ACF using MapLibre GL and OpenFreeMap with geocoding support
 * Version: 1.0.0
 * Author: 8am GmbH
 * Author URI: https://8am.ch
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: acf-ofm-location
 * Requires at least: 5.8
 * Requires PHP: 7.4
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Define plugin constants
define( 'ACF_OFM_LOCATION_VERSION', '1.0.0' );
define( 'ACF_OFM_LOCATION_PATH', plugin_dir_path( __FILE__ ) );
define( 'ACF_OFM_LOCATION_URL', plugin_dir_url( __FILE__ ) );

/**
 * Main plugin class
 */
class ACF_OFM_Location {

	/**
	 * Constructor
	 */
	public function __construct() {
		// Check if ACF is active
		add_action( 'plugins_loaded', array( $this, 'init' ) );
	}

	/**
	 * Initialize the plugin
	 */
	public function init() {
		// Check if ACF is available
		if ( ! function_exists( 'acf_register_field_type' ) ) {
			add_action( 'admin_notices', array( $this, 'acf_not_active_notice' ) );
			return;
		}

		// Include required files
		$this->includes();

		// Initialize settings
		if ( is_admin() ) {
			new ACF_OFM_Location_Settings();
		}

		// Register field type
		add_action( 'acf/include_field_types', array( $this, 'register_field_type' ) );
	}

	/**
	 * Include required files
	 */
	private function includes() {
		require_once ACF_OFM_LOCATION_PATH . 'includes/class-acf-ofm-settings.php';
		require_once ACF_OFM_LOCATION_PATH . 'includes/class-acf-field-ofm-location.php';
	}

	/**
	 * Register the field type
	 */
	public function register_field_type() {
		acf_register_field_type( 'ACF_Field_OFM_Location' );
	}

	/**
	 * Display admin notice if ACF is not active
	 */
	public function acf_not_active_notice() {
		?>
		<div class="notice notice-error">
			<p><?php esc_html_e( 'ACF OpenFreeMap Location Field requires Advanced Custom Fields to be installed and active.', 'acf-ofm-location' ); ?></p>
		</div>
		<?php
	}
}

// Initialize the plugin
new ACF_OFM_Location();
