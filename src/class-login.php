<?php
/**
 * Login class.
 *
 * This will manage the login flow, which includes adding the
 * google login button on wp-login page, authorizing the user,
 * authenticating user and redirecting him to admin.
 *
 * @package GoogleLogin
 * @since 1.0.0
 */

declare(strict_types=1);

namespace GoogleLogin;

use WP_User;
use WP_Error;
use stdClass;
use Throwable;
use Exception;
use function GoogleLogin\services;

/**
 * Class Login.
 *
 * @package GoogleLogin
 */
class Login {
	/**
	 * Has been authenticated from plugin?
	 *
	 * @var bool
	 */
	private $authenticated = false;

	/**
	 * Initialize login object.
	 */
	public function __construct() {
		/**
		 * Actions.
		 */
		add_action( 'login_enqueue_scripts', array( $this, 'login_scripts' ) );
		add_action( 'login_footer', array( $this, 'login_button' ) );
		add_action( 'wp_login', array( $this, 'login_redirect' ) );

		/**
		 * Filters.
		 */
		// Priority is 20 because of issue: https://core.trac.wordpress.org/ticket/46748.
		add_filter( 'authenticate', array( $this, 'authenticate' ), 20 );
		add_filter( 'google_login_redirect_url', array( $this, 'redirect_url' ) );
		add_filter( 'google_login_state', array( $this, 'state_redirect' ) );
	}

	/**
	 * Get the redirection URL and set the redirection URL to the default URL.
	 *
	 * This function offers customization to the users for the URL that they want to be redirected to.
	 *
	 * @return string
	 */
	private function get_redirect_url(): string {
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

	/**
	 * Add the login button to login page.
	 */
	public function login_button() {
		$login_url = services( 'google_client' )->authorization_url();

		if ( empty( $login_url ) ) {
			return;
		}

		if ( is_user_logged_in() ) {
			$button_text = __( 'Log out', 'google-login' );
			$button_url = wp_logout_url( $this->get_redirect_url() );
		} else {
			$button_url = $login_url;
			$button_text = __( 'Sign in with Google', 'google-login' );
		}
		?>
		<div class="google-login-wrapper">
			<a rel="nofollow" href="<?php echo esc_url( $button_url ); ?>">
				<button class="google-login-button">
					<div class="google-login-button-content">
						<div class="google-login-button-icon">
							<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 48 48">
								<path fill="#ea4335" d="M24 9.5c3.54 0 6.71 1.22 9.21 3.6l6.85-6.85C35.9 2.38 30.47 0 24 0 14.62 0 6.51 5.38 2.56 13.22l7.98 6.19C12.43 13.72 17.74 9.5 24 9.5"/>
								<path fill="#4285f4" d="M46.98 24.55c0-1.57-.15-3.09-.38-4.55H24v9.02h12.94c-.58 2.96-2.26 5.48-4.78 7.18l7.73 6c4.51-4.18 7.09-10.36 7.09-17.65"/>
								<path fill="#fbbc05" d="M10.53 28.59c-.48-1.45-.76-2.99-.76-4.59s.27-3.14.76-4.59l-7.98-6.19C.92 16.46 0 20.12 0 24s.92 7.54 2.56 10.78z"/>
								<path fill="#34a853" d="M24 48c6.48 0 11.93-2.13 15.89-5.81l-7.73-6c-2.15 1.45-4.92 2.3-8.16 2.3-6.26 0-11.57-4.22-13.47-9.91l-7.98 6.19C6.51 42.62 14.62 48 24 48"/>
								<path fill="none" d="M0 0h48v48H0z"/>
							</svg>
						</div>
						<span class="google-login-button-text"><?php echo esc_html( $button_text ); ?></span>
					</div>
				</button>
			</a>
		</div>
		<?php
	}

	/**
	 * Add the login button to login page.
	 */
	public function login_scripts() {
		wp_enqueue_style(
			'google-login-css',
			plugins_url( 'assets/login.css', __DIR__ ),
			array( 'login' ),
			'1.0.0'
		);
	}

	/**
	 * Authenticate the user.
	 *
	 * @param WP_User|null $user User object. Default is null.
	 *
	 * @return WP_User|WP_Error
	 * @throws Exception During authentication.
	 */
	public function authenticate( $user = null ) {
		if ( $user instanceof WP_User ) {
			return $user;
		}

		$code = filter_input( INPUT_GET, 'code', FILTER_SANITIZE_FULL_SPECIAL_CHARS );

		if ( ! $code ) {
			return $user;
		}

		$state = filter_input( INPUT_GET, 'state', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
		$decoded_state = $state ? (array) ( json_decode( base64_decode( $state ) ) ) : null; // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode

		if ( ! is_array( $decoded_state ) || empty( $decoded_state['provider'] ) || 'google' !== $decoded_state['provider'] ) {
			return $user;
		}

		if ( empty( $decoded_state['nonce'] ) || ! wp_verify_nonce( $decoded_state['nonce'], 'google_login' ) ) {
			return $user;
		}

		try {
			$user = services( 'google_client' )->get_user_from_code( $code );
			$user = services( 'authenticator' )->authenticate( $user );

			if ( $user instanceof WP_User ) {
				$this->authenticated = true;
				return $user;
			}

			throw new Exception( __( 'Could not authenticate the user, please try again.', 'google-login' ) );

		} catch ( Throwable $e ) {
			return new WP_Error( 'google_login_failed', $e->getMessage() );
		}
	}

	/**
	 * Redirect URL.
	 *
	 * This is useful when redirect URL is present when
	 * trying to login to wp-admin.
	 *
	 * @param string $url Redirect URL address.
	 *
	 * @return string
	 */
	public function redirect_url( string $url ): string {
		return remove_query_arg( 'redirect_to', $url );
	}

	/**
	 * Add redirect_to location in state.
	 *
	 * @param array $state State data.
	 *
	 * @return array
	 */
	public function state_redirect( array $state ): array {
		$redirect_to = filter_input( INPUT_GET, 'redirect_to', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
		/**
		 * Filter the default redirect URL in case redirect_to param is not available.
		 * Default to admin URL.
		 *
		 * @param string $admin_url Admin URL address.
		 */
		$state['redirect_to'] = $redirect_to ?? admin_url();

		return $state;
	}

	/**
	 * Add a redirect once user has been authenticated successfully.
	 *
	 * @return void
	 */
	public function login_redirect(): void {
		$state = filter_input( INPUT_GET, 'state', FILTER_SANITIZE_FULL_SPECIAL_CHARS );

		if ( ! $state || ! $this->authenticated ) {
			return;
		}

		$state = base64_decode( $state );
		$state = $state ? json_decode( $state ) : null;

		if ( ( $state instanceof stdClass ) && ! empty( $state->provider ) && 'google' === $state->provider && ! empty( $state->redirect_to ) ) {
			wp_safe_redirect( $state->redirect_to, 302, 'Google Login' );
			exit;
		}
	}
}
