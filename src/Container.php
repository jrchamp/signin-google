<?php
/**
 * Class Container.
 *
 * This will be useful for creation of object.
 *
 * @package RtCamp\GoogleLogin
 * @since 1.0.0
 */

declare(strict_types=1);

namespace RtCamp\GoogleLogin;

use InvalidArgumentException;
use RtCamp\GoogleLogin\Modules\Login;
use RtCamp\GoogleLogin\Modules\Settings;
use RtCamp\GoogleLogin\Utils\Authenticator;
use RtCamp\GoogleLogin\Utils\GoogleClient;
use RtCamp\GoogleLogin\Utils\TokenVerifier;

/**
 * Class Container
 *
 * @package RtCamp\GoogleLogin
 */
class Container {
	/**
	 * Get the service object.
	 *
	 * @param string $service Service object in need.
	 *
	 * @return object
	 *
	 * @throws InvalidArgumentException Exception for invalid service.
	 */
	public function get( string $service ) {
		if ( ! in_array( $service, $this->container->keys(), true ) ) {
			$error_message = sprintf(
				/* translators: %$s is replaced with requested service name. */
				__( 'Invalid Service %s Passed to the container', 'login-with-google' ),
				$service
			);

			throw new InvalidArgumentException( esc_html( $error_message ) );
		}

		return $this->container[ $service ];
	}

	/**
	 * Define common services in container.
	 *
	 * All the module specific services will be defined inside
	 * respective module's container.
	 *
	 * @codeCoverageIgnore
	 *
	 * @return void
	 */
	public function define_services(): void {
		/**
		 * Define Settings service to add settings page and retrieve setting values.
		 *
		 * @return Settings
		 */
		$this->container['settings'] = function () {
			return new Settings();
		};

		/**
		 * Define the login flow service.
		 *
		 * @return Login
		 */
		$this->container['login_flow'] = function ( $c ) {
			return new Login( $c['gh_client'], $c['authenticator'] );
		};

		/**
		 * Define a service for Google OAuth client.
		 *
		 * @return GoogleClient
		 */
		$this->container['gh_client'] = function ( $c ) {
			$settings = $c['settings'];

			return new GoogleClient(
				array(
					'client_id'     => $settings->client_id,
					'client_secret' => $settings->client_secret,
					'redirect_uri'  => wp_login_url(),
				)
			);
		};

		/**
		 * Define Token Verifier Service.
		 *
		 * Useful in verifying JWT Auth token.
		 *
		 * @return TokenVerifier
		 */
		$this->container['token_verifier'] = function ( $c ) {
			return new TokenVerifier( $c['settings'] );
		};

		/**
		 * Authenticator utility.
		 *
		 * @return Authenticator
		 */
		$this->container['authenticator'] = function ( $c ) {
			return new Authenticator( $c['settings'] );
		};
	}
}
