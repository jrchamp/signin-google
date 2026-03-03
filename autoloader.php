<?php
/**
 * Autoloader for google-login.
 *
 * @package google-login
 */

spl_autoload_register(
	function ( $class_name ) {
		static $class_map = array(
			'GoogleLogin\\Authenticator' => __DIR__ . '/src/class-authenticator.php',
			'GoogleLogin\\GoogleClient' => __DIR__ . '/src/class-googleclient.php',
			'GoogleLogin\\Login' => __DIR__ . '/src/class-login.php',
			'GoogleLogin\\Settings' => __DIR__ . '/src/class-settings.php',
		);

		if ( isset( $class_map[ $class_name ] ) ) {
			require $class_map[ $class_name ];
		}
	}
);
