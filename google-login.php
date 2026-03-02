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
use GoogleLogin\Modules\Login;
use GoogleLogin\Modules\Settings;
use GoogleLogin\Utils\Authenticator;
use GoogleLogin\Utils\GoogleClient;
use GoogleLogin\Utils\TokenVerifier;

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
					'Login with google Plugin requires PHP version 7.4 or higher. <br />Please ask your server administrator to update your environment to a newer PHP version',
					'google-login'
				);

				printf(
					'<div class="notice notice-error"><span class="notice-title">%1$s</span><p>%2$s</p></div>',
					esc_html__(
						'The plugin Login with Google has been deactivated',
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
 * Return the container instance.
 */
function container(): Container {
	static $container;

	if ( empty( $container ) ) {
		$container = new Container();
	}

	return $container;
}

/**
 * Return the Plugin instance.
 *
 * If reauth is set, redirect to login page.
 *
 * @return Plugin
 */
function plugin(): Plugin {
	static $plugin;

	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Nonce verification is not required here.
	if ( isset( $_GET['reauth'] ) && null !== sanitize_text_field( wp_unslash( $_GET['reauth'] ) ) ) {
		if ( ! empty( $_COOKIE[ LOGGED_IN_COOKIE ] ) ) {
			wp_safe_redirect( wp_login_url(), 302, 'Login with Google' );
			exit;
		}
	}

	if ( empty( $plugin ) ) {
		$plugin = new Plugin();
	}

	return $plugin;
}

/**
 * Let the magic happen by
 * running the plugin.
 */
add_action(
	'plugins_loaded',
	function () {
		plugin()->run();
	},
	100
);

/**
 * Class Plugin.
 *
 * @package GoogleLogin
 */
class Plugin {

	/**
	 * Plugin version.
	 *
	 * @var string
	 */
	public $version = '1.0.0';

	/**
	 * Plugin directory path.
	 *
	 * @var string
	 */
	public $path;

	/**
	 * Plugin's url.
	 *
	 * @var string
	 */
	public $url;

	/**
	 * List of active modules.
	 *
	 * @var string[]
	 */
	public $active_modules = array(
		'settings',
		'login_flow',
	);

	/**
	 * Run the plugin
	 *
	 * @return void
	 */
	public function run(): void {
		$this->path = __DIR__;
		$this->url = plugin_dir_url( trailingslashit( __DIR__ ) . 'google-login.php' );

		$this->activate_modules();

		add_action( 'init', array( $this, 'load_translations' ) );

		add_filter( 'plugin_action_links_' . plugin_basename( $this->path ) . '/google-login.php', array( $this, 'add_plugin_action_links' ) );
	}

	/**
	 *  Load the plugin translation if available.
	 *
	 * @return void
	 */
	public function load_translations(): void {
		load_plugin_textdomain( 'google-login', false, basename( plugin()->path ) . '/languages/' . get_locale() );
	}

	/**
	 * Activate individual modules.
	 *
	 * @return void
	 */
	private function activate_modules(): void {
		foreach ( $this->active_modules as $module ) {
			container()->get( $module );
		}
	}

	/**
	 * Add settings link to plugin actions
	 *
	 * @param  array $actions Plugin actions.
	 * @return array
	 */
	public function add_plugin_action_links( $actions ) {
		$new_actions = array(
			'settings' => sprintf(
				/* translators: %1$s: Setting name, %2$s: URL for settings page link. */
				'<a href="%1$s">%2$s</a>',
				esc_url( admin_url( 'options-general.php?page=google-login' ) ),
				esc_html__( 'Settings', 'google-login' )
			),
		);

		return array_merge( $new_actions, $actions );
	}
}

/**
 * Class Container
 *
 * @package GoogleLogin
 */
class Container {
	/**
	 * Services.
	 *
	 * @var array
	 */
	private $services = array();

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->services = array(
			/**
			 * Define Settings service to add settings page and retrieve setting values.
			 *
			 * @return Settings
			 */
			'settings' => function () {
				return new Settings();
			},

			/**
			 * Define the login flow service.
			 *
			 * @return Login
			 */
			'login_flow' => function () {
				return new Login( container()->get( 'google_client' ), container()->get( 'authenticator' ) );
			},

			/**
			 * Define a service for Google OAuth client.
			 *
			 * @return GoogleClient
			 */
			'google_client' => function () {
				$settings = container()->get( 'settings' );

				return new GoogleClient(
					array(
						'client_id'     => $settings->client_id,
						'client_secret' => $settings->client_secret,
						'redirect_uri'  => wp_login_url(),
					)
				);
			},

			/**
			 * Define Token Verifier Service.
			 *
			 * Useful in verifying JWT Auth token.
			 *
			 * @return TokenVerifier
			 */
			'token_verifier' => function () {
				return new TokenVerifier( container()->get( 'settings' ) );
			},

			/**
			 * Authenticator utility.
			 *
			 * @return Authenticator
			 */
			'authenticator' => function () {
				return new Authenticator( container()->get( 'settings' ) );
			},
		);
	}

	/**
	 * Get the service object.
	 *
	 * @param string $service Service needed.
	 *
	 * @return object
	 *
	 * @throws InvalidArgumentException Exception for invalid service.
	 */
	public function get( string $service ) {
		$maybe_callable = $this->services[ $service ] ?? throw new InvalidArgumentException();

		// Initialize objects the first time they are needed.
		if ( is_callable( $maybe_callable ) ) {
			$service_object = $maybe_callable();
			$this->services[ $service ] = $service_object;
		}

		return $this->services[ $service ];
	}
}
