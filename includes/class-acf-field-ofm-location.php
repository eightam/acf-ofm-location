<?php
/**
 * ACF OpenFreeMap Location Field
 *
 * @package ACF_OFM_Location
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * ACF Location Field Class
 */
class ACF_Field_OFM_Location extends acf_field {

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->name     = 'ofm_location';
		$this->label    = __( 'Location (OpenFreeMap)', 'acf-ofm-location' );
		$this->category = 'jquery';
		$this->defaults = array(
			'geocoding_api'      => '',
			'style_url'          => '',
			'default_lat'        => '',
			'default_lng'        => '',
			'default_zoom'       => '',
			'expose_components'  => array( 'full_address', 'street', 'number', 'city', 'post_code', 'country', 'state', 'lat', 'lng' ),
		);

		parent::__construct();

		// Create companion field groups when a field group with location field is saved
		add_action( 'acf/update_field_group', array( $this, 'create_companion_field_group' ), 10, 1 );
		add_action( 'acf/init', array( $this, 'ensure_companion_field_groups' ), 20 );
	}

	/**
	 * Ensure companion field groups exist for all location fields
	 */
	public function ensure_companion_field_groups() {
		// Get all field groups
		$field_groups = acf_get_field_groups();

		foreach ( $field_groups as $field_group ) {
			$this->create_companion_field_group( $field_group );
		}
	}

	/**
	 * Create or update companion field group for a field group containing location fields
	 *
	 * @param array $field_group The field group array.
	 * @return void
	 */
	public function create_companion_field_group( $field_group ) {
		// Get all fields in this group
		$fields = acf_get_fields( $field_group );

		if ( ! $fields ) {
			return;
		}

		// Find location fields
		$location_fields = array();
		foreach ( $fields as $field ) {
			if ( isset( $field['type'] ) && 'ofm_location' === $field['type'] ) {
				$location_fields[] = $field;
			}
		}

		// No location fields in this group
		if ( empty( $location_fields ) ) {
			return;
		}

		// Create/update companion field group for each location field
		foreach ( $location_fields as $location_field ) {
			$this->create_component_fields_for_location( $location_field, $field_group );
		}
	}

	/**
	 * Create real ACF component fields for a location field
	 *
	 * @param array $location_field The location field array.
	 * @param array $parent_group   The parent field group.
	 * @return void
	 */
	public function create_component_fields_for_location( $location_field, $parent_group ) {
		$field_name = $location_field['name'];
		$field_label = $location_field['label'];

		// Companion field group key
		$companion_group_key = 'group_ofm_location_' . $field_name . '_components';

		// Prevent duplicate registration in the same request
		static $registered_groups = array();
		if ( isset( $registered_groups[ $companion_group_key ] ) ) {
			return;
		}
		$registered_groups[ $companion_group_key ] = true;

		// Component field definitions
		$components = array(
			'full_address' => 'Full Address',
			'street'       => 'Street',
			'number'       => 'Number',
			'city'         => 'City',
			'post_code'    => 'Post Code',
			'country'      => 'Country',
			'state'        => 'State',
			'lat'          => 'Latitude',
			'lng'          => 'Longitude',
		);

		// Build component fields array
		$component_fields = array();
		foreach ( $components as $component_key => $component_label ) {
			$component_fields[] = array(
				'key'               => $location_field['key'] . '_' . $component_key,
				'label'             => $field_label . ' - ' . $component_label,
				'name'              => $field_name . '_' . $component_key,
				'type'              => 'text',
				'instructions'      => '',
				'required'          => 0,
				'conditional_logic' => 0,
				'wrapper'           => array(
					'width' => '',
					'class' => 'acf-ofm-component-field',
					'id'    => '',
				),
				'default_value'     => '',
				'placeholder'       => '',
				'prepend'           => '',
				'append'            => '',
				'maxlength'         => '',
			);
		}

		// Prepare field group data
		$companion_group_data = array(
			'key'                   => $companion_group_key,
			'title'                 => $field_label . ' (Components)',
			'fields'                => $component_fields,
			'location'              => $parent_group['location'], // Same location rules as parent
			'menu_order'            => $parent_group['menu_order'] + 1,
			'position'              => 'normal',
			'style'                 => 'default',
			'label_placement'       => 'top',
			'instruction_placement' => 'label',
			'hide_on_screen'        => '',
			'active'                => true,
			'description'           => 'Auto-generated component fields for ' . $field_label . '. Do not edit manually.',
			'private'               => true, // Hide from ACF admin UI
		);

		// Register the companion field group as a local field group
		// This keeps it in code, not in the database, so it's fully managed by the plugin
		acf_add_local_field_group( $companion_group_data );
	}

	/**
	 * Render field settings
	 *
	 * @param array $field The field settings.
	 */
	public function render_field_settings( $field ) {
		// Get global defaults
		$global_geocoding_api = ACF_OFM_Location_Settings::get_setting( 'geocoding_api' );
		$global_style_url     = ACF_OFM_Location_Settings::get_setting( 'style_url' );
		$global_default_lat   = ACF_OFM_Location_Settings::get_setting( 'default_lat' );
		$global_default_lng   = ACF_OFM_Location_Settings::get_setting( 'default_lng' );
		$global_default_zoom  = ACF_OFM_Location_Settings::get_setting( 'default_zoom' );

		// Geocoding API
		acf_render_field_setting(
			$field,
			array(
				'label'        => __( 'Geocoding API', 'acf-ofm-location' ),
				'instructions' => sprintf(
					/* translators: %s: global setting value */
					__( 'Override global setting (currently: %s)', 'acf-ofm-location' ),
					esc_html( $global_geocoding_api )
				),
				'type'         => 'select',
				'name'         => 'geocoding_api',
				'choices'      => array(
					''          => __( 'Use Global Setting', 'acf-ofm-location' ),
					'photon'    => __( 'Photon (photon.komoot.io)', 'acf-ofm-location' ),
					'nominatim' => __( 'Nominatim (nominatim.openstreetmap.org)', 'acf-ofm-location' ),
				),
			)
		);

		// Style URL
		acf_render_field_setting(
			$field,
			array(
				'label'        => __( 'Map Style URL', 'acf-ofm-location' ),
				'instructions' => sprintf(
					/* translators: %s: global setting value */
					__( 'Override global setting (currently: %s)', 'acf-ofm-location' ),
					esc_html( $global_style_url )
				),
				'type'         => 'text',
				'name'         => 'style_url',
				'placeholder'  => esc_attr( $global_style_url ),
			)
		);

		// Default Latitude
		acf_render_field_setting(
			$field,
			array(
				'label'        => __( 'Default Latitude', 'acf-ofm-location' ),
				'instructions' => sprintf(
					/* translators: %s: global setting value */
					__( 'Override global setting (currently: %s)', 'acf-ofm-location' ),
					esc_html( $global_default_lat )
				),
				'type'         => 'number',
				'name'         => 'default_lat',
				'placeholder'  => esc_attr( $global_default_lat ),
				'step'         => '0.000001',
			)
		);

		// Default Longitude
		acf_render_field_setting(
			$field,
			array(
				'label'        => __( 'Default Longitude', 'acf-ofm-location' ),
				'instructions' => sprintf(
					/* translators: %s: global setting value */
					__( 'Override global setting (currently: %s)', 'acf-ofm-location' ),
					esc_html( $global_default_lng )
				),
				'type'         => 'number',
				'name'         => 'default_lng',
				'placeholder'  => esc_attr( $global_default_lng ),
				'step'         => '0.000001',
			)
		);

		// Default Zoom
		acf_render_field_setting(
			$field,
			array(
				'label'        => __( 'Default Zoom Level', 'acf-ofm-location' ),
				'instructions' => sprintf(
					/* translators: %s: global setting value */
					__( 'Override global setting (currently: %s)', 'acf-ofm-location' ),
					esc_html( $global_default_zoom )
				),
				'type'         => 'number',
				'name'         => 'default_zoom',
				'placeholder'  => esc_attr( $global_default_zoom ),
				'step'         => '1',
				'min'          => '0',
				'max'          => '20',
			)
		);

		// Expose Components
		acf_render_field_setting(
			$field,
			array(
				'label'        => __( 'Expose Individual Components', 'acf-ofm-location' ),
				'instructions' => __( 'Select which components should be available as individual ACF fields (for use in theme builders like Blocksy)', 'acf-ofm-location' ),
				'type'         => 'checkbox',
				'name'         => 'expose_components',
				'choices'      => array(
					'full_address' => __( 'Full Address', 'acf-ofm-location' ),
					'street'       => __( 'Street', 'acf-ofm-location' ),
					'number'       => __( 'Number', 'acf-ofm-location' ),
					'city'         => __( 'City', 'acf-ofm-location' ),
					'post_code'    => __( 'Post Code', 'acf-ofm-location' ),
					'country'      => __( 'Country', 'acf-ofm-location' ),
					'state'        => __( 'State', 'acf-ofm-location' ),
					'lat'          => __( 'Latitude', 'acf-ofm-location' ),
					'lng'          => __( 'Longitude', 'acf-ofm-location' ),
				),
				'layout'       => 'vertical',
			)
		);
	}

	/**
	 * Enqueue assets for field input
	 */
	public function input_admin_enqueue_scripts() {
		$url     = ACF_OFM_LOCATION_URL;
		$version = ACF_OFM_LOCATION_VERSION;

		// MapLibre GL JS (local)
		wp_enqueue_style( 'maplibre-gl', $url . 'assets/lib/maplibre-gl/maplibre-gl.css', array(), '4.7.1' );
		wp_enqueue_script( 'maplibre-gl', $url . 'assets/lib/maplibre-gl/maplibre-gl.js', array(), '4.7.1', true );

		// Plugin CSS
		wp_enqueue_style( 'acf-ofm-location', $url . 'assets/css/ofm-location.css', array(), $version );

		// Plugin JS
		wp_enqueue_script( 'acf-ofm-location', $url . 'assets/js/ofm-location.js', array( 'jquery', 'maplibre-gl', 'acf-input' ), $version, true );
	}

	/**
	 * Render field for input
	 *
	 * @param array $field The field settings.
	 */
	public function render_field( $field ) {
		// Get effective settings (field-specific or global)
		$geocoding_api = ! empty( $field['geocoding_api'] ) ? $field['geocoding_api'] : ACF_OFM_Location_Settings::get_setting( 'geocoding_api' );
		$style_url     = ! empty( $field['style_url'] ) ? $field['style_url'] : ACF_OFM_Location_Settings::get_setting( 'style_url' );
		$default_lat   = ! empty( $field['default_lat'] ) ? floatval( $field['default_lat'] ) : floatval( ACF_OFM_Location_Settings::get_setting( 'default_lat' ) );
		$default_lng   = ! empty( $field['default_lng'] ) ? floatval( $field['default_lng'] ) : floatval( ACF_OFM_Location_Settings::get_setting( 'default_lng' ) );
		$default_zoom  = ! empty( $field['default_zoom'] ) ? intval( $field['default_zoom'] ) : intval( ACF_OFM_Location_Settings::get_setting( 'default_zoom' ) );

		// Parse current value
		$value = wp_parse_args(
			$field['value'],
			array(
				'full_address' => '',
				'street'       => '',
				'number'       => '',
				'city'         => '',
				'post_code'    => '',
				'country'      => '',
				'state'        => '',
				'lat'          => '',
				'lng'          => '',
			)
		);

		// Build data attributes
		$data_attrs = array(
			'data-geocoding-api' => esc_attr( $geocoding_api ),
			'data-style-url'     => esc_attr( $style_url ),
			'data-default-lat'   => esc_attr( $default_lat ),
			'data-default-lng'   => esc_attr( $default_lng ),
			'data-default-zoom'  => esc_attr( $default_zoom ),
		);

		?>
		<div class="acf-ofm-location-field" <?php echo implode( ' ', array_map( function( $k, $v ) { return $k . '="' . $v . '"'; }, array_keys( $data_attrs ), $data_attrs ) ); ?>>

			<div class="acf-ofm-location-layout">
				<!-- Map Container -->
				<div class="acf-ofm-location-map-wrapper">
					<div class="acf-ofm-location-map"></div>
				</div>

				<!-- Sidebar -->
				<div class="acf-ofm-location-sidebar">
					<!-- Search Input -->
					<div class="acf-ofm-location-search">
						<input type="text" class="acf-ofm-location-search-input" placeholder="<?php esc_attr_e( 'Search for an address...', 'acf-ofm-location' ); ?>" />
						<div class="acf-ofm-location-search-results" style="display: none;"></div>
					</div>

					<!-- Current Location Display -->
					<div class="acf-ofm-location-current">
						<strong><?php esc_html_e( 'Current Location:', 'acf-ofm-location' ); ?></strong>
						<div class="acf-ofm-location-display">
							<?php echo ! empty( $value['full_address'] ) ? esc_html( $value['full_address'] ) : esc_html__( 'No location set', 'acf-ofm-location' ); ?>
						</div>
					</div>
				</div>
			</div>

			<!-- Hidden Inputs -->
			<input type="hidden" name="<?php echo esc_attr( $field['name'] ); ?>[full_address]" class="acf-ofm-location-full-address" value="<?php echo esc_attr( $value['full_address'] ); ?>" />
			<input type="hidden" name="<?php echo esc_attr( $field['name'] ); ?>[street]" class="acf-ofm-location-street" value="<?php echo esc_attr( $value['street'] ); ?>" />
			<input type="hidden" name="<?php echo esc_attr( $field['name'] ); ?>[number]" class="acf-ofm-location-number" value="<?php echo esc_attr( $value['number'] ); ?>" />
			<input type="hidden" name="<?php echo esc_attr( $field['name'] ); ?>[city]" class="acf-ofm-location-city" value="<?php echo esc_attr( $value['city'] ); ?>" />
			<input type="hidden" name="<?php echo esc_attr( $field['name'] ); ?>[post_code]" class="acf-ofm-location-post-code" value="<?php echo esc_attr( $value['post_code'] ); ?>" />
			<input type="hidden" name="<?php echo esc_attr( $field['name'] ); ?>[country]" class="acf-ofm-location-country" value="<?php echo esc_attr( $value['country'] ); ?>" />
			<input type="hidden" name="<?php echo esc_attr( $field['name'] ); ?>[state]" class="acf-ofm-location-state" value="<?php echo esc_attr( $value['state'] ); ?>" />
			<input type="hidden" name="<?php echo esc_attr( $field['name'] ); ?>[lat]" class="acf-ofm-location-lat" value="<?php echo esc_attr( $value['lat'] ); ?>" />
			<input type="hidden" name="<?php echo esc_attr( $field['name'] ); ?>[lng]" class="acf-ofm-location-lng" value="<?php echo esc_attr( $value['lng'] ); ?>" />
		</div>
		<?php
	}

	/**
	 * Format value for API
	 *
	 * @param mixed $value   The field value.
	 * @param int   $post_id The post ID.
	 * @param array $field   The field settings.
	 * @return mixed
	 */
	public function format_value( $value, $post_id, $field ) {
		// Return false if no value
		if ( empty( $value ) ) {
			return false;
		}

		// Ensure all keys exist
		$value = wp_parse_args(
			$value,
			array(
				'full_address' => '',
				'street'       => '',
				'number'       => '',
				'city'         => '',
				'post_code'    => '',
				'country'      => '',
				'state'        => '',
				'lat'          => '',
				'lng'          => '',
			)
		);

		/**
		 * Filter the formatted location value.
		 *
		 * @param array $value   The location data array.
		 * @param int   $post_id The post ID.
		 * @param array $field   The field settings.
		 */
		return apply_filters( 'acf_ofm_location/format_value', $value, $post_id, $field );
	}

	/**
	 * Update value before saving
	 *
	 * @param mixed $value   The field value.
	 * @param int   $post_id The post ID.
	 * @param array $field   The field settings.
	 * @return mixed
	 */
	public function update_value( $value, $post_id, $field ) {
		// If empty, delete all meta
		if ( empty( $value ) || ! is_array( $value ) ) {
			$this->delete_sub_fields( $post_id, $field );
			return $value;
		}

		// Sanitize all values
		$sanitized = array(
			'full_address' => isset( $value['full_address'] ) ? sanitize_text_field( $value['full_address'] ) : '',
			'street'       => isset( $value['street'] ) ? sanitize_text_field( $value['street'] ) : '',
			'number'       => isset( $value['number'] ) ? sanitize_text_field( $value['number'] ) : '',
			'city'         => isset( $value['city'] ) ? sanitize_text_field( $value['city'] ) : '',
			'post_code'    => isset( $value['post_code'] ) ? sanitize_text_field( $value['post_code'] ) : '',
			'country'      => isset( $value['country'] ) ? sanitize_text_field( $value['country'] ) : '',
			'state'        => isset( $value['state'] ) ? sanitize_text_field( $value['state'] ) : '',
			'lat'          => isset( $value['lat'] ) ? sanitize_text_field( $value['lat'] ) : '',
			'lng'          => isset( $value['lng'] ) ? sanitize_text_field( $value['lng'] ) : '',
		);

		/**
		 * Filter the location value before saving.
		 *
		 * @param array $sanitized The sanitized location data.
		 * @param array $value     The original submitted value.
		 * @param int   $post_id   The post ID.
		 * @param array $field     The field settings.
		 */
		$sanitized = apply_filters( 'acf_ofm_location/update_value', $sanitized, $value, $post_id, $field );

		// Store individual sub-fields
		$this->update_sub_fields( $post_id, $field, $sanitized );

		return $sanitized;
	}

	/**
	 * Update individual sub-fields
	 *
	 * @param int   $post_id The post ID.
	 * @param array $field   The field settings.
	 * @param array $value   The field value.
	 */
	private function update_sub_fields( $post_id, $field, $value ) {
		$field_name = $field['name'];
		$field_key  = $field['key'];

		// Store each component as a separate meta field for ACF compatibility
		foreach ( $value as $key => $val ) {
			$component_name = $field_name . '_' . $key;
			$component_key  = $field_key . '_' . $key;

			// Store the value
			update_post_meta( $post_id, $component_name, $val );

			// Store ACF field reference so get_field() works
			update_post_meta( $post_id, '_' . $component_name, $component_key );
		}

		/**
		 * Action fired after sub-fields are updated.
		 *
		 * @param int   $post_id    The post ID.
		 * @param array $field      The field settings.
		 * @param array $value      The field value.
		 * @param string $field_name The field name used for meta keys.
		 */
		do_action( 'acf_ofm_location/updated_sub_fields', $post_id, $field, $value, $field_name );
	}

	/**
	 * Delete individual sub-fields
	 *
	 * @param int   $post_id The post ID.
	 * @param array $field   The field settings.
	 */
	private function delete_sub_fields( $post_id, $field ) {
		$field_name = $field['name'];
		$keys       = array( 'full_address', 'street', 'number', 'city', 'post_code', 'country', 'state', 'lat', 'lng' );

		foreach ( $keys as $key ) {
			$component_name = $field_name . '_' . $key;
			delete_post_meta( $post_id, $component_name );
			delete_post_meta( $post_id, '_' . $component_name );
		}
	}

	/**
	 * Validate value before saving
	 *
	 * @param bool  $valid   Whether the value is valid.
	 * @param mixed $value   The field value.
	 * @param array $field   The field settings.
	 * @param array $input   The input name.
	 * @return bool|string
	 */
	public function validate_value( $valid, $value, $field, $input ) {
		// If field is required and no location set
		if ( $field['required'] && ( empty( $value ) || empty( $value['lat'] ) || empty( $value['lng'] ) ) ) {
			return __( 'Please select a location.', 'acf-ofm-location' );
		}

		// Validate coordinates if set
		if ( ! empty( $value['lat'] ) && ! empty( $value['lng'] ) ) {
			$lat = floatval( $value['lat'] );
			$lng = floatval( $value['lng'] );

			if ( $lat < -90 || $lat > 90 ) {
				return __( 'Invalid latitude value.', 'acf-ofm-location' );
			}

			if ( $lng < -180 || $lng > 180 ) {
				return __( 'Invalid longitude value.', 'acf-ofm-location' );
			}
		}

		return $valid;
	}
}
