<?php
/**
 * Test shortcode module class.
 */

declare( strict_types=1 );

namespace RtCamp\GoogleLogin\Tests\Unit\Modules;

use RtCamp\GoogleLogin\Interfaces\Module as ModuleInterface;
use RtCamp\GoogleLogin\Utils\Helper;
use WP_Mock;
use Mockery;
use RtCamp\GoogleLogin\Modules\Shortcode as Testee;
use RtCamp\GoogleLogin\Tests\TestCase;
use RtCamp\GoogleLogin\Utils\GoogleClient;
use RtCamp\GoogleLogin\Modules\Assets;

/**
 * Class ShortCodeTest
 *
 * @coversDefaultClass \RtCamp\GoogleLogin\Modules\Shortcode
 *
 * @package RtCamp\GoogleLogin\Tests\Unit\Modules
 */
class ShortCodeTest extends TestCase {
	/**
	 * @var GoogleClient
	 */
	private $ghClientMock;

	/**
	 * @var Assets
	 */
	private $assetMock;

	/**
	 * @var Testee
	 */
	private $testee;

	/**
	 * Run before each test.
	 *
	 * @return void
	 */
	public function setUp(): void {
		$this->ghClientMock = $this->createMock( GoogleClient::class );
		$this->assetMock    = $this->createMock( Assets::class );

		$this->testee = new Testee( $this->ghClientMock, $this->assetMock );
	}

	/**
	 * @covers ::name
	 */
	public function testName() {
		$this->assertSame( 'shortcode', $this->testee->name() );
	}

	/**
	 * @covers ::__construct
	 */
	public function testImplementsModuleInterface() {
		$this->assertTrue( $this->testee instanceof ModuleInterface );
	}

	/**
	 * @covers ::init
	 */
	public function testInit() {
		$this->wpMockFunction(
			'add_shortcode',
			array(
				'google_login',
				array(
					$this->testee,
					'callback',
				),
			)
		);

		WP_Mock::expectFilterAdded( 'do_shortcode_tag', array( $this->testee, 'scan_shortcode' ), 10, 3 );

		$this->testee->init();
		$this->assertConditionsMet();
	}

	/**
	 * @covers ::callback
	 * @covers ::should_display
	 */
	public function testCallbackWhenUserIsLoggedIn() {
		$this->wpMockFunction(
			'get_permalink',
			array(),
			1,
			'https://example.com/'
		);

		WP_Mock::userFunction(
			'shortcode_atts',
			array(
				'args'       => array(
					array(
						'button_text'   => '',
						'force_display' => 'no',
						'redirect_to'   => 'https://example.com/',
					),
					array(),
					'google_login',
				),
				'times'      => 1,
				'return_arg' => 0,
			)
		);

		$this->wpMockFunction(
			'is_user_logged_in',
			array(),
			1,
			true
		);

		$shortcode = $this->testee->callback();

		$this->assertSame( '', $shortcode );
	}

	/**
	 * @covers ::callback
	 * @covers ::should_display
	 */
	public function testCallbackWhenUserIsLoggedOut() {
		WP_Mock::userFunction(
			'shortcode_atts',
			array(
				'args'       => array(
					array(
						'button_text'   => '',
						'force_display' => 'no',
						'redirect_to'   => null,
					),
					array(),
					'google_login',
				),
				'times'      => 1,
				'return_arg' => 0,
			)
		);

		$this->wpMockFunction(
			'is_user_logged_in',
			array(),
			1,
			false
		);

		WP_Mock::expectFilterAdded( 'rtcamp.google_redirect_url', array( $this->testee, 'redirect_url' ) );

		$this->wpMockFunction(
			'RtCamp\GoogleLogin\plugin',
			array(),
			1,
			(object) array(
				'template_dir' => '/some/path/templates',
			)
		);

		$this->wpMockFunction(
			'trailingslashit',
			array(),
			1,
			'/some/path/templates/'
		);

		$this->ghClientMock->expects( $this->once() )
			->method( 'authorization_url' )
			->willReturn( 'https://google.com/auth/' );

		$helperMock = Mockery::mock( 'alias:' . Helper::class );
		$helperMock->expects( 'render_template' )->once()->withArgs(
			array(
				'/some/path/templates/google-login-button.php',
				array(
					'button_text'   => '',
					'force_display' => 'no',
					'redirect_to'   => null,
					'login_url'     => 'https://google.com/auth/',
				),
				false,
			)
		)->andReturn( '' );

		$this->testee->callback();
		$this->assertConditionsMet();
	}

	/**
	 * @covers ::scan_shortcode
	 * @covers ::should_display
	 */
	public function testScanShortcodeForSinglePage() {
		$this->wpMockFunction(
			'is_single',
			array(),
			1,
			true
		);

		$this->wpMockFunction(
			'is_page',
			array(),
			0,
			true
		);

		$this->wpMockFunction(
			'is_user_logged_in',
			array(),
			1,
			false
		);

		$this->assetMock->expects( $this->once() )->method( 'enqueue_login_styles' );

		$output = $this->testee->scan_shortcode( 'Hello', 'google_login', array() );
		$this->assertSame( 'Hello', $output );
	}

	/**
	 * @covers ::scan_shortcode
	 * @covers ::should_display
	 */
	public function testScanShortcodeForDifferentTag() {
		$this->wpMockFunction(
			'is_single',
			array(),
			1,
			true
		);

		$this->wpMockFunction(
			'is_user_logged_in',
			array(),
			0,
			false
		);

		$this->assetMock->expects( $this->never() )->method( 'enqueue_login_styles' );

		$output = $this->testee->scan_shortcode( 'Hello', 'other_tag', array() );
		$this->assertSame( 'Hello', $output );
	}

	/**
	 * @covers ::redirect_url
	 */
	public function testRedirectURL() {
		$url                        = 'https://example.com/?redirect_to=https://example.com/wp-admin';
		$this->testee->redirect_uri = 'https://example.com/some-page';

		$this->wpMockFunction(
			'remove_query_arg',
			array(
				'redirect_to',
				$url,
			),
			1,
			'https://example.com/'
		);

		$r_url = $this->testee->redirect_url( $url );
		$this->assertSame( $r_url, 'https://example.com/' );
	}

	/**
	 * @covers ::state_redirect
	 */
	public function testStateRedirectWithRedirectUrl() {
		$this->testee->redirect_uri = 'https://example.com';

		$state = array(
			'provider'    => 'google',
			'redirect_to' => 'https://example.com',
		);

		$expected = $this->testee->state_redirect( $state );
		$this->assertSame( $expected, $state );
	}

	/**
	 * @covers ::state_redirect
	 */
	public function testStateRedirectWithoutRedirectUrl() {
		$this->testee->redirect_uri = null;

		$state = array(
			'provider' => 'google',
		);

		$expected = $this->testee->state_redirect( $state );
		$this->assertSame( $expected, $state );
	}
}
