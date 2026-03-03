<?php
/**
 * Register the settings under settings page and also
 * provide the interface to retrieve the settings.
 *
 * @package GoogleLogin
 * @since 1.0.0
 */

declare(strict_types=1);

namespace GoogleLogin;

/**
 * Class Settings.
 *
 * @property string|null allowed_domains
 * @property string|null client_id
 * @property string|null client_secret
 * @property bool|null registration_enabled
 *
 * @package GoogleLogin
 */
class Settings {

	/**
	 * Settings values.
	 *
	 * @var array
	 */
	public $options;

	/**
	 * Getters for settings values.
	 *
	 * @var string[]
	 */
	private $getters = array(
		'client_id' => 'GOOGLE_LOGIN_CLIENT_ID',
		'client_secret' => 'GOOGLE_LOGIN_SECRET',
		'registration_enabled' => 'GOOGLE_LOGIN_REGISTRATION',
		'allowed_domains' => 'GOOGLE_LOGIN_DOMAINS',
	);

	/**
	 * Getter method.
	 *
	 * @param string $name Name of option to fetch.
	 */
	public function __get( string $name ) {
		if ( isset( $this->getters[ $name ] ) ) {
			$constant_name = $this->getters[ $name ];

			return defined( $constant_name ) ? constant( $constant_name ) : ( $this->options[ $name ] ?? '' );
		}

		return null;
	}

	/**
	 * Initialization of module.
	 */
	public function __construct() {
		$this->options = get_option( 'google_login_settings', array() );

		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action(
			'admin_menu',
			function () {
				add_options_page(
					__( 'Google Login settings', 'google-login' ),
					__( 'Google Login', 'google-login' ),
					'manage_options',
					'google-login',
					function () {
						?>
						<div class="wrap">
						<form action="options.php" method="post">
							<?php
							settings_fields( 'google_login' );
							do_settings_sections( 'google-login' );
							submit_button();
							?>
						</form>
						</div>
						<?php
					}
				);
			}
		);
	}

	/**
	 * Register the settings, section and fields.
	 *
	 * @return void
	 */
	public function register_settings(): void {
		register_setting( 'google_login', 'google_login_settings' );

		add_settings_section(
			'google_login_section',
			__( 'Google Login Settings', 'google-login' ),
			function () {
			},
			'google-login'
		);

		add_settings_field(
			'google_login_client_id',
			__( 'Client ID', 'google-login' ),
			array( $this, 'client_id_field' ),
			'google-login',
			'google_login_section',
			array( 'label_for' => 'client-id' )
		);

		add_settings_field(
			'google_login_client_secret',
			__( 'Client Secret', 'google-login' ),
			array( $this, 'client_secret_field' ),
			'google-login',
			'google_login_section',
			array( 'label_for' => 'client-secret' )
		);

		add_settings_field(
			'google_login_registration',
			__( 'Create New User', 'google-login' ),
			array( $this, 'user_registration' ),
			'google-login',
			'google_login_section',
			array( 'label_for' => 'user-registration' )
		);

		add_settings_field(
			'google_login_domains',
			__( 'Allowed Domains', 'google-login' ),
			array( $this, 'allowed_domains' ),
			'google-login',
			'google_login_section',
			array( 'label_for' => 'allowed_domains' )
		);
	}

	/**
	 * Render client ID field.
	 *
	 * @return void
	 */
	public function client_id_field(): void {
		?>
		<input type="text" name="google_login_settings[client_id]" id="client-id" value="<?php echo esc_attr( $this->client_id ); ?>" autocomplete="off" <?php $this->disabled( 'client_id' ); ?> />
		<p class="description">
		<?php
		echo wp_kses_post(
			sprintf(
				'<p>%1s <a target="_blank" href="%2s">%3s</a>.</p>',
				esc_html__( 'Create OAuth Client ID and Client Secret at', 'google-login' ),
				'https://console.developers.google.com/apis/dashboard',
				'console.developers.google.com'
			)
		);
		?>
		</p>
		<?php
	}

	/**
	 * Render client secret field.
	 *
	 * @return void
	 */
	public function client_secret_field(): void {
		?>
		<input type="password" name="google_login_settings[client_secret]" id="client-secret" value="<?php echo esc_attr( $this->client_secret ); ?>" autocomplete="off" <?php $this->disabled( 'client_secret' ); ?> />
		<?php
	}

	/**
	 * User registration field.
	 *
	 * This will tell us whether or not to create the user
	 * if the user does not exist on WP application.
	 *
	 * This is irrespective of registration flag present in Settings > General
	 *
	 * @return void
	 */
	public function user_registration(): void {
		?>
		<label style="display:block;margin-top:6px;">
			<input type="checkbox" name="google_login_settings[registration_enabled]" id="user-registration" value="1" <?php checked( $this->registration_enabled ); ?> <?php $this->disabled( 'registration_enabled' ); ?> />
			<?php esc_html_e( 'Create a new user account if it does not exist already', 'google-login' ); ?>
		</label>
		<p class="<?php echo esc_attr( 'error-message' ); ?>">
			<?php
			echo wp_kses_post(
				sprintf(
				/* translators: %1s will be replaced by page link */
					__( 'If this setting is checked, a new user will be created even if <a target="_blank" href="%1s">membership setting</a> is off.', 'google-login' ),
					is_multisite() ? 'network/settings.php' : 'options-general.php'
				)
			);
			?>
		</p>
		<?php
	}

	/**
	 * Allowed domains for registration.
	 *
	 * Only emails belonging to these domains would be preferred
	 * for registration.
	 *
	 * If left blank, all domains would be allowed.
	 *
	 * @return void
	 */
	public function allowed_domains(): void {
		?>
		<input type="text" name="google_login_settings[allowed_domains]" id="allowed_domains" value="<?php echo esc_attr( $this->allowed_domains ); ?>" autocomplete="off" <?php $this->disabled( 'allowed_domains' ); ?> />
		<p class="description">
			<?php echo esc_html( __( 'Use a comma to separate domains', 'google-login' ) ); ?>
		</p>
		<?php
	}

	/**
	 * Add settings sub-menu page in admin menu.
	 *
	 * @return void
	 */
	public function settings_page(): void {
		add_options_page(
			__( 'Google Login settings', 'google-login' ),
			__( 'Google Login', 'google-login' ),
			'manage_options',
			'google-login',
			array( $this, 'output' )
		);
	}

	/**
	 * Output the plugin settings.
	 *
	 * @return void
	 */
	public function output(): void {
		?>
		<div class="wrap">
		<form action='options.php' method='post'>
			<?php
			settings_fields( 'google_login' );
			do_settings_sections( 'google-login' );
			submit_button();
			?>
		</form>
		</div>
		<?php
	}

	/**
	 * Outputs the disabled attribute if needed.
	 *
	 * @param string $field Input field.
	 *
	 * @return void
	 */
	private function disabled( string $field ): void {
		$constant_name = $this->getters[ $field ] ?? null;

		if ( isset( $constant_name ) && defined( $constant_name ) ) {
			disabled( true );
		}
	}
}
