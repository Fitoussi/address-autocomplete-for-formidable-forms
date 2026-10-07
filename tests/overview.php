<?php
/**
 * Feature ordering and curated demo contracts; not installed-browser acceptance.
 *
 * @package AddressAutocomplete\Tests
 */

define( 'ABSPATH', __DIR__ . '/' );

function __( $text, $domain = '' ) {
	return $text;
}

$root = dirname( __DIR__ );
$slug = basename( $root );
$namespaces = array(
	'address-autocomplete-for-gravity-forms' => 'GravityGeolocationAutocomplete',
	'address-autocomplete-for-ninja-forms' => 'NinjaGeolocationAutocomplete',
	'address-autocomplete-for-formidable-forms' => 'FormidableGeolocationAutocomplete',
);
require $root . '/src/Admin/DashboardFeatures.php';
$class = $namespaces[ $slug ] . '\\Admin\\DashboardFeatures';

function overview_expect( $condition, $message ) {
	if ( ! $condition ) {
		throw new RuntimeException( $message );
	}
}

$features = $class::get_features();
$original = $features;
$overview = $class::order_for_overview( $features );
$order = array( 'autocomplete', 'location-map', 'geocoder', 'dynamic-fields', 'distance', 'pricing', 'directions', 'drawing', 'nearby', 'validation', 'entry-maps', 'search', 'entry-details', 'current-location', 'coordinates', 'location-controls', 'quick-setups', 'templates', 'gmw-integration' );
$expected = array_values( array_filter( $order, static function ( $id ) use ( $features ) { return isset( $features[ $id ] ); } ) );
overview_expect( array_keys( $overview ) === $expected, 'Major features precede supporting tools.' );
overview_expect( $features === $original && array_keys( $class::get_features() ) === array_keys( $original ), 'Comparison order and original catalog remain unchanged.' );
foreach ( $features as $id => $feature ) {
	overview_expect( $overview[ $id ] === $feature, 'Ordering must not change feature content or availability.' );
}
$extended = $class::order_for_overview( $features + array( 'future-tool' => array( 'title' => 'Future' ) ) );
overview_expect( isset( $extended['future-tool'] ), 'Future definitions must not disappear.' );
foreach ( array( 'quick-setups', 'templates', 'gmw-integration', 'entry-details', 'unknown' ) as $id ) {
	overview_expect( array() === $class::get_demo_links( $id ), 'No artificial demo link for unsupported utility cards.' );
}
$host = str_replace( array( 'address-autocomplete-for-', '-forms' ), '', $slug );
$base = 'https://demo.' . $host . 'geolocation.com/';
foreach ( array( 'distance' => 'distance-duration-price-calculations/', 'pricing' => 'distance-duration-price-calculations/', 'directions' => 'routes-directions/', 'drawing' => 'drawing-tools/', 'nearby' => 'nearest-locations/' ) as $id => $path ) {
	$links = $class::get_demo_links( $id );
	overview_expect( count( $links ) === 1 && $links[0]['url'] === $base . $path, 'Dedicated demo URL for ' . $id );
}
$map_links = $class::get_demo_links( 'entry-maps' );
overview_expect( count( $map_links ) === ( 'formidable' === $host ? 1 : 2 ), 'All verified entry-map demos are available.' );
foreach ( $map_links as $link ) {
	overview_expect( 0 === strpos( $link['url'], $base ) && '' !== $link['label'], 'Local product demo domain and useful label.' );
}
if ( 'gravity' === $host ) {
	overview_expect( $class::get_demo_links( 'search' )[0]['url'] === $base . 'full-search/', 'Gravity Search has a dedicated demo.' );
	overview_expect( array_search( 'search', $expected, true ) < array_search( 'quick-setups', $expected, true ), 'Gravity Search remains among the major capabilities.' );
}
$view = file_get_contents( $root . '/src/Admin/views/dashboard.php' );
overview_expect( false !== strpos( $view, 'dashicons-category' ) && false !== strpos( $view, 'get_demo_links(' ), 'Card rendering uses the package icon and curated demo links.' );
overview_expect( 1 === preg_match( '/target="_blank"\s+rel="noopener noreferrer"/', $view ), 'Outbound demos retain safe new-tab behavior, including formatted attributes.' );
require $root . '/src/Admin/Dashboard.php';
$dashboard_class = $namespaces[ $slug ] . '\\Admin\\Dashboard';
$products = $dashboard_class::get_products();
$search_products = array_values( array_filter( $products, static function ( $product ) { return 'Gravity Search' === $product['name']; } ) );
overview_expect( count( $products ) === 7 && count( $search_products ) === 1, 'One Gravity Search card, preserving all six existing product cards.' );
overview_expect( 'https://gravitygeolocation.com/solutions/gravity-search/' === $search_products[0]['url'], 'Gravity Search links to its solution page, not pricing or demos.' );
overview_expect( ! array_filter( $products, static function ( $product ) { return isset( $product['link_label'] ); } ), 'Product cards use one consistent CTA.' );
overview_expect( false !== strpos( $search_products[0]['description'], 'Gravity Forms entries' ), 'Clear Gravity Forms scope on all product pages.' );
overview_expect( false !== strpos( $view, "esc_html_e( 'Learn more'" ) && false === strpos( $view, "['link_label']" ), 'Every product card renders Learn more.' );
echo "Overview catalog, ordering, demo-link and More Plugins contracts passed.\n";
