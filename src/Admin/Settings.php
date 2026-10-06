<?php
/**
 * Autocomplete settings on Formidable's native settings screen.
 *
 * @package FormidableGeolocationAutocomplete\Admin
 * @since 1.0.0
 */

namespace FormidableGeolocationAutocomplete\Admin;
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Preserve the premium option while updating only free browser preferences.
 */
final class Settings {
	/**
	 * Register native settings hooks.
	 */
	public function __construct() {
		add_filter( 'frm_add_settings_section', array( $this, 'add_section' ), 50 );
		add_action( 'admin_init', array( $this, 'save' ) );
	}

	/**
	 * Configure add section.
	 *
	 * @param array $sections Native sections.
	 * @return array
	 */
	public function add_section( $sections ) {
		$sections['frmgeo_geolocation'] = array(
			'class'    => self::class,
			'function' => 'render',
			'name'     => __( 'Address Autocomplete', 'address-autocomplete-for-formidable-forms' ),
			'icon'     => 'frm_icon_font frm_location_icon',
		);
		return $sections;
	}

	/**
	 * Editable browser preferences only.
	 *
	 * @return array<string,string> Editable browser preferences only.
	 */
	public static function defaults() {
		return array(
			'google_maps_browser_api_key' => '',
			'google_maps_country'         => 'US',
			'google_maps_language'        => 'en',
		);
	}

	/**
	 * Configure merge settings.
	 *
	 * @param array $submitted Unslashed preferences.
	 * @param array $existing All stored preferences.
	 * @return array
	 */
	public static function merge_settings( $submitted, $existing ) {
		$result = is_array( $existing ) ? $existing : array();
		foreach ( self::defaults() as $key => $default ) {
			$value          = $submitted[ $key ] ?? $default;
			$result[ $key ] = is_scalar( $value ) ? sanitize_text_field( (string) $value ) : $default;
		}
		return $result;
	}

	/**
	 * Verify native context, capability and nonce before a narrow option update.
	 */
	public function save() {
		// phpcs:disable WordPress.Security.NonceVerification
		$action = isset( $_POST['frm_action'] ) && is_string( $_POST['frm_action'] ) ? sanitize_key( wp_unslash( $_POST['frm_action'] ) ) : '';
		$page   = isset( $_GET['page'] ) && is_string( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';
		$tab    = isset( $_GET['t'] ) && is_string( $_GET['t'] ) ? sanitize_key( wp_unslash( $_GET['t'] ) ) : '';
		if ( 'process-form' !== $action || 'formidable-settings' !== $page || 'frmgeo_geolocation_settings' !== $tab ) {
			return;
		}
		// phpcs:enable WordPress.Security.NonceVerification
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		check_admin_referer( 'frmgeoac_settings', 'frmgeoac_settings_nonce' );
		// Values are unslashed here and sanitized individually by merge_settings before storage.
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$submitted = isset( $_POST['frmgeo_settings'] ) && is_array( $_POST['frmgeo_settings'] ) ? wp_unslash( $_POST['frmgeo_settings'] ) : array();
		update_option( 'frmgeo_global_settings', self::merge_settings( $submitted, get_option( 'frmgeo_global_settings', array() ) ) );
	}

	/**
	 * Output three preferences within the host's existing form.
	 */
	public static function render() {
		$settings = get_option( 'frmgeo_global_settings', array() );
		$settings = is_array( $settings ) ? array_merge( self::defaults(), $settings ) : self::defaults();
		wp_nonce_field( 'frmgeoac_settings', 'frmgeoac_settings_nonce' );
		Dashboard::render_settings_link();
		$labels = array(
			'google_maps_browser_api_key' => __( 'Browser API Key', 'address-autocomplete-for-formidable-forms' ),
			'google_maps_country'         => __( 'Region Code', 'address-autocomplete-for-formidable-forms' ),
			'google_maps_language'        => __( 'Language', 'address-autocomplete-for-formidable-forms' ),
		);
		echo '<p>' . esc_html__( 'Use your own Google browser API key with Maps JavaScript API and Places API (New) enabled. Region influences results; field country restrictions limit suggestions.', 'address-autocomplete-for-formidable-forms' ) . '</p>';
		foreach ( $labels as $key => $label ) {
			$value = is_scalar( $settings[ $key ] ) ? (string) $settings[ $key ] : '';
			echo '<p><label class="frm_left_label" for="' . esc_attr( $key ) . '">' . esc_html( $label ) . '</label>';
			echo '<input class="frm_with_left_label" type="text" id="' . esc_attr( $key ) . '" name="frmgeo_settings[' . esc_attr( $key ) . ']" value="' . esc_attr( $value ) . '"></p>';
		}
	}

	/**
	 * Browser loader options, with no hidden paid settings exported.
	 *
	 * @return array Browser loader options, with no hidden paid settings exported.
	 */
	public static function get_config() {
		$all      = get_option( 'frmgeo_global_settings', array() );
		$all      = is_array( $all ) ? $all : array();
		$settings = self::merge_settings( $all, array() );
		return array(
			'googleMapsBrowserApiKey' => $settings['google_maps_browser_api_key'],
			'regionCode'              => $settings['google_maps_country'],
			'languageCode'            => $settings['google_maps_language'],
			'disableGoogleApi'        => (bool) apply_filters( 'frmgeo_disable_google_maps_api', ! empty( $all['disable_google_maps_api'] ) ),
		);
	}
}
