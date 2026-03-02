<?php
/**
 * Plugin Name: Google Login
 * Description: Authenticate users with Google.
 * Version: 1.0.0
 * Author: Jonathan Champ, rtCamp
 * Text Domain: google-login
 * Domain Path: /languages
 * License: GPLv2+
 * Requires at least: 5.5
 * Requires PHP: 7.4
 *
 * @package GoogleLogin
 * @since 1.0.0
 */

declare(strict_types=1);

namespace GoogleLogin;

use InvalidArgumentException;
use GoogleLogin\Authenticator;
use GoogleLogin\GoogleClient;
use GoogleLogin\Login;
use GoogleLogin\Settings;

// Prevent direct access.
defined( 'ABSPATH' ) || exit;

/**
 * PHP 7.4+ is required in order to use the plugin.
 */
if ( version_compare( PHP_VERSION, '7.4', '<' ) ) {
	$hooks = array(
		'admin_notices',
		'network_admin_notices',
	);

	foreach ( $hooks as $hook ) {
		add_action(
			$hook,
			function () {
				$message = __(
					'Google Login plugin requires PHP version 7.4 or higher.',
					'google-login'
				);

				printf(
					'<div class="notice notice-error"><span class="notice-title">%1$s</span><p>%2$s</p></div>',
					esc_html__(
						'The Google Login plugin has been deactivated',
						'google-login'
					),
					wp_kses( $message, array( 'br' => true ) )
				);

				deactivate_plugins( plugin_basename( __FILE__ ) );
			}
		);
	}

	return;
}

/**
 * Get a service object.
 *
 * @param string $service Service needed.
 *
 * @return object
 *
 * @throws InvalidArgumentException Exception for invalid service.
 */
function services( string $service ) {
	static $services = array(
		// Adds settings page and retrieves setting values.
		'settings' => Settings::class,

		// Hooks the login process.
		'login' => Login::class,

		// Provides a Google OAuth client.
		'google_client' => GoogleClient::class,

		// Handles WordPress authentication.
		'authenticator' => Authenticator::class,
	);

	$maybe_object = $services[ $service ] ?? throw new InvalidArgumentException();

	// Initialize objects the first time they are needed.
	if ( ! is_object( $maybe_object ) ) {
		$service_object = new $maybe_object();
		$services[ $service ] = $service_object;
	}

	return $services[ $service ];
}

// Initialize the plugin.
add_action(
	'plugins_loaded',
	function () {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Nonce verification is not required here.
		if ( isset( $_GET['reauth'] ) && null !== sanitize_text_field( wp_unslash( $_GET['reauth'] ) ) ) {
			if ( ! empty( $_COOKIE[ LOGGED_IN_COOKIE ] ) ) {
				wp_safe_redirect( wp_login_url(), 302, 'Google Login' );
				exit;
			}
		}

		$active_modules = array(
			'settings',
			'login',
		);
		foreach ( $active_modules as $module ) {
			services( $module );
		}
	},
	100
);

// Load the plugin translation if available.
add_action(
	'init',
	function () {
		load_plugin_textdomain( 'google-login', false, plugin_basename( __DIR__ ) . '/languages' );
	}
);

/**
 * Add settings link to plugin actions
 *
 * @param  array $actions Plugin actions.
 * @return array
 */
$add_plugin_action_links = function ( $actions ) {
	$new_actions = array(
		'settings' => sprintf(
			/* translators: %1$s: Setting name, %2$s: URL for settings page link. */
			'<a href="%1$s">%2$s</a>',
			esc_url( admin_url( 'options-general.php?page=google-login' ) ),
			esc_html__( 'Settings', 'google-login' )
		),
	);

	return array_merge( $new_actions, $actions );
};
add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), $add_plugin_action_links );
