<?php
/**
 * Autoloader for google-login.
 *
 * @package google-login
 */

spl_autoload_register(
	function ( $namespaced_class ) {
		$prefix = 'GoogleLogin\\';
		$base_dir = __DIR__ . '/src';

		// Does the class use our namespace prefix?
		if ( ! str_starts_with( $namespaced_class, $prefix ) ) {
			return;
		}

		// : "class-classname.php"
		$parts = explode( '\\', $namespaced_class );
		$last_index = count( $parts ) - 1;

		$parts[0] = $base_dir;
		$parts[ $last_index ] = 'class-' . strtolower( str_replace( '_', '-', $parts[ $last_index ] ) ) . '.php';

		$path = implode( '/', $parts );

		if ( file_exists( $path ) ) {
			require $path;
		}
	}
);
