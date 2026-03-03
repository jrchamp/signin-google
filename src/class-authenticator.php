<?php
/**
 * Authenticator class.
 *
 * This will authenticate the user. Also responsible for registration
 * in case it is enabled in the settings.
 *
 * @package GoogleLogin
 * @since 1.0.0
 */

declare(strict_types=1);

namespace GoogleLogin;

use WP_User;
use stdClass;
use Exception;
use Throwable;
use InvalidArgumentException;
use GoogleLogin\Settings;
use function GoogleLogin\services;

/**
 * Class Authenticator
 *
 * @package GoogleLogin
 */
class Authenticator {
	/**
	 * Authenticate the user.
	 *
	 * If registration setting is on, user will be created if
	 * that user does not exist in the application.
	 *
	 * @param stdClass $user User data object returned by Google.
	 *
	 * @return WP_User
	 * @throws InvalidArgumentException For invalid registrations.
	 */
	public function authenticate( stdClass $user ): WP_User {
		if ( ! property_exists( $user, 'email' ) ) {
			throw new InvalidArgumentException( esc_html__( 'Email needs to be present for the user.', 'google-login' ) );
		}

		if ( email_exists( $user->email ) ) {
			return get_user_by( 'email', $user->email );
		}

		/**
		 * Check if we need to register the user.
		 *
		 * @param stdClass $user User object from google.
		 * @since 1.0.0
		 */
		return $this->register( $this->maybe_create_username( $user ) );
	}

	/**
	 * Checks if username exists, if it does, creates a
	 * unique username by appending digits.
	 *
	 * @param string $username Username.
	 *
	 * @return string
	 */
	public static function unique_username( string $username ): string {
		$uname = $username;
		$count = 1;

		while ( username_exists( $uname ) ) {
			$uname = $username . $count;
			++$count;
		}

		return $uname;
	}

	/**
	 * Register the new user if setting is on for registration.
	 *
	 * @param stdClass $user User object from google.
	 *
	 * @return WP_User|null
	 * @throws Throwable Invalid email registration.
	 * @throws Exception Registration is off.
	 */
	public function register( stdClass $user ): ?WP_User {
		$register = true === (bool) services( 'settings' )->registration_enabled || (bool) get_option( 'users_can_register', false );

		if ( ! $register ) {
			throw new Exception( esc_html__( 'Registration is not allowed.', 'google-login' ) );
		}

		try {
			$allowed_domains = services( 'settings' )->allowed_domains;
			if ( empty( $allowed_domains ) || $this->can_register_with_email( $user->email ) ) {
				$uid = wp_insert_user(
					array(
						'user_login' => self::unique_username( $user->login ),
						'user_pass'  => wp_generate_password( 18 ),
						'user_email' => $user->email,
						'first_name' => $user->given_name ?? '',
						'last_name'  => $user->family_name ?? '',
					)
				);

				add_user_meta( $uid, 'oauth_user', 1, true );
				add_user_meta( $uid, 'oauth_provider', 'google', true );

				return get_user_by( 'id', $uid );
			}

			/* translators: %s is replaced with email ID of user trying to register */
			throw new Exception( sprintf( __( 'Cannot register with this email: %s', 'google-login' ), $user->email ) );
		} catch ( Throwable $e ) {
			throw $e;
		}
	}

	/**
	 * Set auth cookies for WordPress login.
	 *
	 * @param WP_User $user WP User object.
	 *
	 * @return void
	 */
	public function set_auth_cookies( WP_User $user ) {
		wp_clear_auth_cookie();
		wp_set_current_user( $user->ID, $user->user_login );
		wp_set_auth_cookie( $user->ID );
	}

	/**
	 * Assign the `login` property to user object
	 * if it doesn't exists.
	 *
	 * @param stdClass $user User object.
	 *
	 * @return stdClass
	 */
	private function maybe_create_username( stdClass $user ): stdClass {
		if ( property_exists( $user, 'login' ) || ! property_exists( $user, 'email' ) ) {
			return $user;
		}

		$email = $user->email;
		$user_login = sanitize_user( current( explode( '@', $email ) ), true );
		$user_login = self::unique_username( $user_login );
		$user->login = $user_login;

		return $user;
	}

	/**
	 * Check if given email can be used for registration.
	 *
	 * @param string $email Email ID.
	 *
	 * @return bool
	 */
	private function can_register_with_email( string $email ): bool {
		$allowed_domains = explode( ',', services( 'settings' )->allowed_domains );
		$allowed_domains = array_map( 'strtolower', $allowed_domains );
		$allowed_domains = array_map( 'trim', $allowed_domains );
		$email_parts = explode( '@', $email );
		$email_parts = array_map( 'strtolower', $email_parts );

		return in_array( $email_parts[1], $allowed_domains, true );
	}
}
