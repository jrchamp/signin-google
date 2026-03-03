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
use GoogleLogin\GoogleClient;
use GoogleLogin\Authenticator;
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
		add_action( 'wp_login', array( $this, 'login_redirect' ) );

		/**
		 * Filters.
		 */
		// Priority is 20 because of issue: https://core.trac.wordpress.org/ticket/46748.
		add_filter( 'authenticate', array( $this, 'authenticate' ), 20 );
		add_filter( 'google_login_redirect_url', array( $this, 'redirect_url' ) );
		add_filter( 'google_login_state', array( $this, 'state_redirect' ) );
		add_filter( 'login_message', array( $this, 'login_button' ) );
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
	 * Add the login button to login form.
	 *
	 * @param string $message Message string.
	 * @return array
	 */
	public function login_button( $message ) {
		$login_url = services( 'google_client' )->authorization_url();

		if ( empty( $login_url ) ) {
			return $message;
		}

		if ( is_user_logged_in() ) {
			$button_text = __( 'Log out', 'google-login' );
			$button_url = wp_logout_url( $this->get_redirect_url() );
		} else {
			$button_url = $login_url;
			$button_text = __( 'Log in with Google', 'google-login' );
		}

		ob_start();
		?>
<div class="google_login message">
	<div class="google_login__button-container button-group">
		<a class="google_login__button button button-primary button-large aligncenter" rel="nofollow" href="<?php echo esc_url( $button_url ); ?>">
			<span class="google_login__google-icon"></span>
			<?php echo esc_html( $button_text ); ?>
		</a>
	</div>
</div>
		<?php
		$message .= ob_get_clean();
		return $message;
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
		$decoded_state = $state ? (array) ( json_decode( base64_decode( $state ) ) ) : null;    // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode

		if ( ! is_array( $decoded_state ) || empty( $decoded_state['provider'] ) || 'google' !== $decoded_state['provider'] ) {
			return $user;
		}

		if ( empty( $decoded_state['nonce'] ) || ! wp_verify_nonce( $decoded_state['nonce'], 'google_login' ) ) {
			return $user;
		}

		try {
			services( 'google_client' )->set_access_token( $code );
			$user = services( 'google_client' )->user();
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
