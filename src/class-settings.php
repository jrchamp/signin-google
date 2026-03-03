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
	private $options;

	/**
	 * Getters for settings values.
	 *
	 * @var string[]
	 */
	private $option_overrides = array(
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
		return $this->options[ $name ] ?? null;
	}

	/**
	 * Initialization of module.
	 */
	public function __construct() {
		$this->load_options();

		add_action( 'admin_init', array( $this, 'register_settings' ) );

		if ( is_multisite() ) {
			add_action(
				'network_admin_menu',
				function () {
					add_submenu_page(
						'settings.php',
						__( 'Google Login settings', 'google-login' ),
						__( 'Google Login', 'google-login' ),
						'manage_network_options',
						'google-login',
						array( $this, 'settings_page' )
					);
				}
			);
		} else {
			add_action(
				'admin_menu',
				function () {
					add_options_page(
						__( 'Google Login settings', 'google-login' ),
						__( 'Google Login', 'google-login' ),
						'manage_options',
						'google-login',
						array( $this, 'settings_page' )
					);
				}
			);
		}
	}

	/**
	 * Load options.
	 */
	private function load_options() {
		$this->options = get_site_option( 'google_login_settings', array() );

		foreach ( $this->option_overrides as $key => $constant_name ) {
			$this->options[ $key ] = defined( $constant_name ) ? constant( $constant_name ) : ( $this->options[ $key ] ?? '' );
		}
	}

	/**
	 * Sanitize settings.
	 *
	 * @param array $input Settings array.
	 * @return array
	 */
	public function sanitize_settings( $input ) {
		// Clean up the allowed domains string before it goes in the database.
		$allowed_domains = sanitize_text_field( $input['allowed_domains'] ?? '' );
		$allowed_domains = strtolower( $allowed_domains );
		$allowed_domains = explode( ',', $allowed_domains );
		$allowed_domains = array_map( 'trim', $allowed_domains );
		$allowed_domains = array_filter( $allowed_domains );
		$allowed_domains = implode( ',', $allowed_domains );

		return array(
			'client_id' => sanitize_text_field( $input['client_id'] ?? '' ),
			'client_secret' => sanitize_text_field( $input['client_secret'] ?? '' ),
			'registration_enabled' => rest_sanitize_boolean( $input['registration_enabled'] ?? '' ),
			'allowed_domains' => sanitize_text_field( $allowed_domains ),
		);
	}

	/**
	 * Register the settings, section and fields.
	 *
	 * @return void
	 */
	public function register_settings(): void {
		register_setting(
			'google_login',
			'google_login_settings',
			array(
				'type' => 'array',
				'description' => 'Google Login settings',
				'sanitize_callback' => array( $this, 'sanitize_settings' ),
				'show_in_rest' => false,
				'default' => array(
					'registration_enabled' => (bool) get_site_option( 'users_can_register', false ),
				),
			)
		);

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
			__( 'Allow New Users', 'google-login' ),
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
		<input type="text" size="80" name="google_login_settings[client_id]" id="client-id" value="<?php echo esc_attr( $this->client_id ); ?>" autocomplete="off" <?php $this->disabled( 'client_id' ); ?> />
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
		if ( defined( $this->option_overrides['client_secret'] ) ) {
			$client_secret = 'REDACTED';
		} else {
			$client_secret = $this->client_secret;
		}
		?>
		<input type="password" size="40" name="google_login_settings[client_secret]" id="client-secret" value="<?php echo esc_attr( $client_secret ); ?>" autocomplete="off" <?php $this->disabled( 'client_secret' ); ?> />
		<?php
	}

	/**
	 * User registration field.
	 *
	 * @return void
	 */
	public function user_registration(): void {
		?>
		<label>
			<input type="checkbox" name="google_login_settings[registration_enabled]" id="user-registration" value="1" <?php checked( $this->registration_enabled ); ?> <?php $this->disabled( 'registration_enabled' ); ?> />
			<?php esc_html_e( 'Allow new account creation?', 'google-login' ); ?>
		</label>
		<p>
		<span class="<?php echo esc_attr( 'notice notice-warning' ); ?>">
			<?php
			echo wp_kses_post(
				sprintf(
					__( 'Please note: This setting allows new users to be created even if new account registration is disabled.', 'google-login' ),
				)
			);
			?>
		</span>
		</p>
		<?php
	}

	/**
	 * Allowed domains for registration.
	 *
	 * If left blank, all domains are allowed.
	 *
	 * @return void
	 */
	public function allowed_domains(): void {
		?>
		<input type="text" size="40" name="google_login_settings[allowed_domains]" id="allowed_domains" value="<?php echo esc_attr( $this->allowed_domains ); ?>" autocomplete="off" <?php $this->disabled( 'allowed_domains' ); ?> />
		<p class="description">
			<?php echo esc_html( __( 'Use a comma to separate domains.', 'google-login' ) ); ?>
		</p>
		<?php
	}

	/**
	 * Render settings page.
	 *
	 * @return void
	 */
	public function settings_page(): void {
		if ( is_network_admin() && ! empty( $_POST['google_login_settings'] ) ) {
			check_admin_referer( 'google_login_settings' );
			if ( current_user_can( 'manage_network_options' )  ) {
				update_site_option( 'google_login_settings', $this->sanitize_settings( $_POST['google_login_settings'] ) );
				add_settings_error( 'google_login_settings', 'google_login_updated', __( 'Settings saved.', 'google-login' ), 'updated' );

				// Reload options.
				$this->load_options();
			}
		}
		?>
		<div class="wrap">
		<form method="post">
			<?php
			settings_errors( 'google_login_settings' );

			if ( is_network_admin() ) {
				wp_nonce_field( 'google_login_settings' );
			} else {
				settings_fields( 'google_login' );
			}

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
		$constant_name = $this->option_overrides[ $field ] ?? null;

		if ( isset( $constant_name ) && defined( $constant_name ) ) {
			disabled( true );
		}
	}
}
