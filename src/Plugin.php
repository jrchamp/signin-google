<?php
/**
 * Main plugin class.
 *
 * Setup and bootstrap everything from here.
 *
 * @package RtCamp\GoogleLogin
 * @since 1.0.0
 */

declare(strict_types=1);

namespace RtCamp\GoogleLogin;

use RtCamp\GoogleLogin\Container;

/**
 * Class Plugin.
 *
 * @package RtCamp\GoogleLogin
 */
class Plugin {

	/**
	 * Plugin version.
	 *
	 * @var string
	 */
	public $version = '1.4.2';

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
	 * Template directory path.
	 *
	 * @var string
	 */
	public $template_dir;

	/**
	 * DI Container.
	 *
	 * @var Container
	 */
	private $container;

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
	 * Plugin constructor.
	 *
	 * @param Container $container Container instance.
	 */
	public function __construct( Container $container ) {
		$this->container = $container;
	}

	/**
	 * Run the plugin
	 *
	 * @return void
	 */
	public function run(): void {
		$this->path         = dirname( __DIR__ );
		$this->url          = plugin_dir_url( trailingslashit( dirname( __DIR__ ) ) . 'login-with-google.php' );
		$this->template_dir = trailingslashit( $this->path ) . 'templates/';

		$this->container()->define_services();
		$this->activate_modules();

		add_action( 'init', array( $this, 'load_translations' ) );

		add_filter( 'plugin_action_links_' . plugin_basename( $this->path ) . '/login-with-google.php', array( $this, 'add_plugin_action_links' ) );
	}

	/**
	 *  Load the plugin translation if available.
	 *
	 * @return void
	 */
	public function load_translations(): void {
		load_plugin_textdomain( 'login-with-google', false, basename( plugin()->path ) . '/languages/' . get_locale() );
	}

	/**
	 * Return container object
	 *
	 * @return Container
	 */
	public function container(): Container {
		return $this->container;
	}

	/**
	 * Activate individual modules.
	 *
	 * @return void
	 */
	private function activate_modules(): void {
		foreach ( $this->active_modules as $module ) {
			$module_instance = $this->container()->get( $module );
			$module_instance->init();
		}
	}

	/**
	 * Add settings link to plugin actions
	 *
	 * @param  array $actions Plugin actions.
	 * @return array
	 */
	public function add_plugin_action_links( $actions ) {
		$new_actions = array();

		$new_actions['settings'] = sprintf(
			/* translators: %1$s: Setting name, %2$s: URL for settings page link. */
			'<a href="%1$s">%2$s</a>',
			esc_url( admin_url( 'options-general.php?page=login-with-google' ) ),
			esc_html__( 'Settings', 'login-with-google' )
		);

		return array_merge( $new_actions, $actions );
	}
}
