<?php
/**
 * Helper class for all helper function.
 *
 * This class has been taken from Google Login plugin.
 *
 * @package GoogleLogin
 * @since 1.0.0
 */

declare(strict_types=1);

namespace GoogleLogin\Utils;

/**
 * Class Helper
 */
class Helper {

	/**
	 * URL to be redirected to post successful login.
	 *
	 * @var string
	 */
	public static $redirection_url = '';

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
	 * Get the redirection URL and set the redirection URL to the default URL.
	 *
	 * This function offers customization to the users for the URL that they want to be redirected to.
	 *
	 * @return string
	 */
	public static function get_redirect_url(): string {
		global $pagenow;

		// Initializing the default with admin URL.
		$default_redirect_url = admin_url();

		if ( 'wp-login.php' === $pagenow ) {
			// If any redirect_to query parameter is available.
			$redirect_to = filter_input( INPUT_GET, 'redirect_to', FILTER_SANITIZE_URL );

			// In case the query parameter is available.
			if ( ! empty( $redirect_to ) ) {
				$default_redirect_url = $redirect_to;
			}
		} else {
			$request_uri = filter_input( INPUT_SERVER, 'REQUEST_URI', FILTER_SANITIZE_URL );
			if ( empty( $request_uri ) ) {
				$default_redirect_url = home_url();
			} else {
				$default_redirect_url = home_url( trim( $request_uri ) );
			}
		}

		return $default_redirect_url;
	}
}
