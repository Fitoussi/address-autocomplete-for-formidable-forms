<?php
/**
 * Build one standalone ZIP from an explicit source allowlist.
 * Usage: php tools/package/build.php [output-directory]
 * Run npm run build first. No dependency install, remote action or site mutation.
 *
 * @package FormidableGeolocationAutocomplete\Tools
 */

if ( 'cli' !== PHP_SAPI ) {
	exit;
}

$root = dirname( __DIR__, 2 );
$slug = 'address-autocomplete-for-formidable-forms';

try {
	if ( ! class_exists( 'ZipArchive' ) ) {
		throw new RuntimeException( 'Packaging requires the PHP zip extension.' );
	}
	$main = file_get_contents( $root . '/' . $slug . '.php' );
	if ( ! preg_match( '/^ \* Version:\s*([0-9]+\.[0-9]+\.[0-9]+(?:[-.][A-Za-z0-9.-]+)?)\s*$/m', $main, $match ) ) {
		throw new RuntimeException( 'Cannot read the plugin header version.' );
	}
	$version = $match[1];
	if ( false === strpos( $main, "define( 'FRMGEOAC_VERSION', '{$version}' );" ) || false === strpos( $main, "define( 'FRMGEOAC_PACKAGE_TYPE', 'free' );" ) ) {
		throw new RuntimeException( 'Plugin constants do not match the standalone header.' );
	}
	$readme = file_get_contents( $root . '/readme.txt' );
	if ( ! preg_match( '/^Stable tag:\s*' . preg_quote( $version, '/' ) . '\s*$/m', $readme ) ) {
		throw new RuntimeException( 'The readme stable tag must match the plugin version.' );
	}

	$required = array(
		'build/js/frontend/address-autocomplete.min.js',
		'build/js/admin/frmgeo-form-editor.min.js',
		'build/css/admin/frmgeo-admin.min.css',
		'build/css/admin/frmgeo-product-dashboard.min.css',
		'assets/css/address-autocomplete.css',
		'assets/css/choices.css',
		'assets/licenses/choices.txt',
	);
	foreach ( $required as $path ) {
		if ( ! is_file( $root . '/' . $path ) ) {
			throw new RuntimeException( 'Missing asset: ' . $path . '. Run npm run build first.' );
		}
	}

	$paths      = array( $slug . '.php', 'readme.txt', 'change_log.txt', 'src', 'assets', 'build', 'languages', 'build.js', 'package.json', 'package-lock.json' );
	$files      = array();
	$extensions = array( 'php', 'js', 'css', 'scss', 'svg', 'png', 'jpg', 'jpeg', 'webp', 'gif', 'json', 'map', 'txt', 'po', 'mo', 'pot' );
	foreach ( $paths as $path ) {
		$source = $root . '/' . $path;
		if ( is_link( $source ) || ! file_exists( $source ) ) {
			throw new RuntimeException( 'Missing or linked package input: ' . $path );
		}
		if ( is_file( $source ) ) {
			$files[ $path ] = $source;
			continue;
		}
		$iterator = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $source, FilesystemIterator::SKIP_DOTS ) );
		foreach ( $iterator as $file ) {
			if ( $file->isLink() ) {
				throw new RuntimeException( 'Linked files cannot be packaged: ' . $file->getPathname() );
			}
			if ( $file->isFile() && in_array( strtolower( $file->getExtension() ), $extensions, true ) ) {
				$relative           = str_replace( DIRECTORY_SEPARATOR, '/', substr( $file->getPathname(), strlen( $root ) + 1 ) );
				$files[ $relative ] = $file->getPathname();
			}
		}
	}
	ksort( $files );

	$output = $argv[1] ?? $root . '/dist';
	if ( ! is_dir( $output ) && ! mkdir( $output, 0755, true ) ) {
		throw new RuntimeException( 'Cannot create the output directory.' );
	}
	$destination = realpath( $output ) . '/' . $slug . '.' . $version . '.zip';
	$zip         = new ZipArchive();
	if ( true !== $zip->open( $destination, ZipArchive::CREATE | ZipArchive::EXCL ) ) {
		throw new RuntimeException( 'Cannot create ZIP; existing artifacts will not be overwritten. Use a fresh output directory.' );
	}
	foreach ( $files as $relative => $source ) {
		if ( ! $zip->addFile( $source, $slug . '/' . $relative ) ) {
			throw new RuntimeException( 'Cannot add package file: ' . $relative );
		}
	}
	if ( ! $zip->close() ) {
		throw new RuntimeException( 'Cannot finish the ZIP.' );
	}
	fwrite( STDOUT, $destination . PHP_EOL . 'SHA-256: ' . hash_file( 'sha256', $destination ) . PHP_EOL );
} catch ( Throwable $error ) {
	fwrite( STDERR, $error->getMessage() . PHP_EOL );
	exit( 1 );
}
