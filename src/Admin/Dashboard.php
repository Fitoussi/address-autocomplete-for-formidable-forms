<?php
/**
 * Local product overview for the standalone Address Autocomplete plugin.
 * This presentation-only page never reads licenses, creates forms or writes
 * settings. Actual configuration remains on the native Formidable Forms screen.
 *
 * @package FormidableGeolocationAutocomplete\Admin
 * @since 1.0.0
 */

namespace FormidableGeolocationAutocomplete\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Own local product-page metadata, routing and page-scoped presentation.
 *
 * @since 1.0.0
 */
final class Dashboard {

	/**
	 * Stable product-page identity independent of the installed folder name.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	private const PAGE_SLUG = 'frmgeoac-dashboard';

	/**
	 * Native WordPress page hook returned during menu registration.
	 *
	 * @since 1.0.0
	 * @var string|false
	 */
	private static $page_hook = '';

	/**
	 * Register the local product page before Formidable Forms initializes.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public static function register() {
		add_action( 'admin_menu', array( self::class, 'register_page' ), 30 );
		add_action( 'admin_enqueue_scripts', array( self::class, 'enqueue' ) );
		add_filter( 'plugin_action_links_' . FRMGEOAC_BASENAME, array( self::class, 'add_action_link' ), 120 );
	}

	/**
	 * Place the overview with its Formidable Forms host, without an account SDK.
	 * The Settings fallback keeps setup/help available while the host is inactive.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public static function register_page() {
		$title = __( 'Address Autocomplete', 'address-autocomplete-for-formidable-forms' );
		if ( class_exists( '\FrmAppHelper' ) ) {
			self::$page_hook = add_submenu_page( 'formidable', FRMGEOAC_PLUGIN_NAME, $title, 'manage_options', self::PAGE_SLUG, array( self::class, 'render' ) );
		} else {
			self::$page_hook = add_options_page( FRMGEOAC_PLUGIN_NAME, $title, 'manage_options', self::PAGE_SLUG, array( self::class, 'render' ) );
		}
	}

	/**
	 * Add the standalone plugin's overview, settings and documentation links.
	 *
	 * @since 1.0.0
	 * @param array $links Existing WordPress plugin action links.
	 * @return array
	 */
	public static function add_action_link( $links ) {
		return array(
			'overview' => '<a href="' . esc_url( self::get_url() ) . '">' . esc_html__( 'Overview', 'address-autocomplete-for-formidable-forms' ) . '</a>',
			'settings' => '<a href="' . esc_url( admin_url( 'admin.php?page=formidable-settings&t=frmgeo_geolocation_settings' ) ) . '">' . esc_html__( 'Settings', 'address-autocomplete-for-formidable-forms' ) . '</a>',
			'docs'     => '<a href="' . esc_url( FRMGEOAC_SITE_URL . '/docs/' ) . '" target="_blank" rel="noopener noreferrer">' . esc_html__( 'Docs', 'address-autocomplete-for-formidable-forms' ) . '</a>',
		) + $links;
	}

	/**
	 * Build a local navigation link using a whitelisted, non-mutating section.
	 *
	 * @since 1.0.0
	 * @param string $section Requested section.
	 * @return string
	 */
	public static function get_url( $section = 'overview' ) {
		$section = \in_array( $section, array( 'overview', 'comparison', 'help', 'products' ), true ) ? $section : 'overview';
		return add_query_arg( 'section', $section, admin_url( 'admin.php?page=' . self::PAGE_SLUG ) );
	}

	/**
	 * Load scoped styles on the product page or its native settings subview.
	 * Native settings need only our card styles, not framework dashboard assets.
	 *
	 * @since 1.0.0
	 * @param string $hook_suffix WordPress screen hook.
	 * @return void
	 */
	public static function enqueue( $hook_suffix ) {
		$is_dashboard = ! empty( self::$page_hook ) && self::$page_hook === $hook_suffix;
		// Navigation context only; no submitted settings or mutation are read here.
		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		$page    = isset( $_GET['page'] ) && \is_string( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';
		$subview = isset( $_GET['subview'] ) && \is_string( $_GET['subview'] ) ? sanitize_key( wp_unslash( $_GET['subview'] ) ) : '';
		// phpcs:enable WordPress.Security.NonceVerification.Recommended
		$is_settings = 'formidable-settings' === $page;

		if ( ! $is_dashboard && ! $is_settings ) {
			return;
		}

		wp_enqueue_style(
			'frmgeo-product-dashboard',
			FRMGEOAC_URL . 'build/css/admin/frmgeo-product-dashboard.min.css',
			array(),
			FRMGEOAC_VERSION
		);
	}

	/**
	 * Render overview discovery links with no inputs, licensing or remote content.
	 * Link to the internal feature overview first and external pricing second.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public static function render_settings_link() {
		?>
		<div class="frmgeo-product-settings__content">
			<p>
				<?php esc_html_e( 'Explore maps, current-location detection, dynamic fields, directions, nearby locations, and more with Formidable Geolocation.', 'address-autocomplete-for-formidable-forms' ); ?>
			</p>
			<div class="frmgeo-product-settings__actions">
				<a class="button button-primary" href="<?php echo esc_url( self::get_url() ); ?>">
					<?php esc_html_e( 'Explore features', 'address-autocomplete-for-formidable-forms' ); ?>
				</a>
				<a class="button button-secondary" href="<?php echo esc_url( FRMGEOAC_SITE_URL . '/pricing/' ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'View packages', 'address-autocomplete-for-formidable-forms' ); ?></a>
			</div>
		</div>
		<?php
	}

	/**
	 * Render the selected server-side tab; malformed requests fall back to Overview.
	 * WordPress already gates the submenu. The second check also protects direct
	 * callback invocation. The template receives public metadata/URLs only.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public static function render() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// This GET value selects presentation only: there is no save/delete action.
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$requested = isset( $_GET['section'] ) && \is_string( $_GET['section'] ) ? sanitize_key( wp_unslash( $_GET['section'] ) ) : '';
		$tabs      = array(
			'overview'   => __( 'Overview', 'address-autocomplete-for-formidable-forms' ),
			'comparison' => __( 'Compare Packages', 'address-autocomplete-for-formidable-forms' ),
			'help'       => __( 'Help', 'address-autocomplete-for-formidable-forms' ),
			'products'   => __( 'More Plugins', 'address-autocomplete-for-formidable-forms' ),
		);

		$version      = FRMGEOAC_VERSION;
		$section      = isset( $tabs[ $requested ] ) ? $requested : 'overview';
		$settings_url = admin_url( 'admin.php?page=formidable-settings&t=frmgeo_geolocation_settings' );
		$pricing_url  = FRMGEOAC_SITE_URL . '/pricing/';
		$docs_url     = FRMGEOAC_SITE_URL . '/docs/';
		$demo_url     = 'https://demo.formidablegeolocation.com/';
		$product_name = FRMGEOAC_PLUGIN_NAME;
		$package_name = FRMGEOAC_PACKAGE_LABEL;
		$features     = DashboardFeatures::get_features();
		$included     = array_filter(
			$features,
			static function ( $feature ) {
				return $feature['included'];
			}
		);
		$available    = array_filter(
			$features,
			static function ( $feature ) {
				return ! $feature['included'];
			}
		);
		$packages     = DashboardFeatures::get_packages();

		$hero_title     = __( 'Less typing. Better addresses.', 'address-autocomplete-for-formidable-forms' );
		$help_step      = __( 'Add our custom Address field, or enable autocomplete on a native Address field.', 'address-autocomplete-for-formidable-forms' );
		$feature_groups = array(
			'included'  => $included,
			'available' => $available,
		);

		require __DIR__ . '/views/dashboard.php';
	}

	/**
	 * Return bundled product descriptions without remote content or installers.
	 *
	 * @since 1.0.0
	 * @return array[] Public product names, descriptions and website URLs.
	 */
	public static function get_products() {
		return array(
			array(
				'name'        => 'Formidable Geolocation',
				'url'         => 'https://formidablegeolocation.com/',
				'description' => __( 'Maps, geocoding, dynamic fields, directions, nearby locations and more for Formidable Forms.', 'address-autocomplete-for-formidable-forms' ),
			),
			array(
				'name'        => 'GEO my WP',
				'url'         => 'https://geomywp.com/',
				'description' => __( 'Build location-based searches and directories for your WordPress site.', 'address-autocomplete-for-formidable-forms' ),
			),
			array(
				'name'        => 'Formidable Geolocation',
				'url'         => 'https://formidablegeolocation.com/',
				'description' => __( 'Add geolocation tools and connected location fields to Formidable Forms.', 'address-autocomplete-for-formidable-forms' ),
			),
			array(
				'name'        => 'Geolocation for Ninja Forms',
				'url'         => 'https://ninjageolocation.com/',
				'description' => __( 'Bring address, map and location workflows to Ninja Forms.', 'address-autocomplete-for-formidable-forms' ),
			),
			array(
				'name'        => 'WPForms Geolocation',
				'url'         => 'https://wpgeoforms.com/',
				'description' => __( 'Add address autocomplete, maps, coordinates and directions to WPForms.', 'address-autocomplete-for-formidable-forms' ),
			),
			array(
				'name'        => 'WP Job Manager Geolocation',
				'url'         => 'https://jobmanagergeolocation.com/',
				'description' => __( 'Add proximity searches and interactive maps to your WP Job Manager job board.', 'address-autocomplete-for-formidable-forms' ),
			),
		);
	}
}
