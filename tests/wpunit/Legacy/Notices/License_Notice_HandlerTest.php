<?php declare( strict_types=1 );

namespace StellarWP\Uplink\Tests\Legacy\Notices;

use StellarWP\Uplink\Config;
use StellarWP\Uplink\Legacy\License_Repository;
use StellarWP\Uplink\Legacy\Notices\License_Notice_Handler;
use StellarWP\Uplink\Notice\Notice_Controller;
use StellarWP\Uplink\Tests\Traits\With_Uopz;
use StellarWP\Uplink\Tests\UplinkTestCase;
use StellarWP\Uplink\Utils\Version;

/**
 * @since 3.0.0
 */
final class License_Notice_HandlerTest extends UplinkTestCase {

	use With_Uopz;

	/**
	 * @var License_Notice_Handler
	 */
	private $handler;

	/**
	 * @var int
	 */
	private $user_id;

	protected function setUp(): void {
		parent::setUp();

		// Always act as leader so Version::should_handle() doesn't block tests.
		$this->set_class_fn_return( Version::class, 'should_handle', true );

		$this->user_id = self::factory()->user->create( [ 'role' => 'administrator' ] );
		wp_set_current_user( $this->user_id );

		$this->handler = new License_Notice_Handler(
			new License_Repository(),
			Config::get_container()->get( Notice_Controller::class )
		);
	}

	protected function tearDown(): void {
		remove_all_filters( 'stellarwp/uplink/legacy_licenses' );
		wp_dequeue_script( 'stellarwp-uplink-notice-dismiss' );
		wp_deregister_script( 'stellarwp-uplink-notice-dismiss' );
		wp_set_current_user( 0 );
		parent::tearDown();
	}

	// ------------------------------------------------------------------
	// No output cases
	// ------------------------------------------------------------------

	/**
	 * @test
	 */
	public function it_renders_nothing_for_non_admin_user(): void {
		$subscriber_id = self::factory()->user->create( [ 'role' => 'subscriber' ] );
		wp_set_current_user( $subscriber_id );

		$this->add_licenses(
			[
				[
					'slug'      => 'give-recurring',
					'product'   => 'give',
					'is_active' => false,
				],
			]
		);

		$output = $this->capture_display();

		$this->assertSame( '', trim( $output ) );
	}

	/**
	 * @test
	 */
	public function it_renders_nothing_when_no_inactive_licenses(): void {
		$output = $this->capture_display();

		$this->assertSame( '', trim( $output ) );
	}

	/**
	 * @test
	 */
	public function it_renders_nothing_when_all_licenses_are_active(): void {
		$this->add_licenses(
			[
				[
					'slug'      => 'give-recurring',
					'product'   => 'give',
					'is_active' => true,
				],
			]
		);

		$output = $this->capture_display();

		$this->assertSame( '', trim( $output ) );
	}

	/**
	 * @test
	 */
	public function it_skips_dismissed_product(): void {
		$this->add_licenses(
			[
				[
					'slug'      => 'give-recurring',
					'product'   => 'give',
					'is_active' => false,
				],
			]
		);

		update_user_meta(
			$this->user_id,
			License_Notice_Handler::DISMISSED_META_KEY,
			[ 'legacy-give' => time() + 1000 ]
		);

		$output = $this->capture_display();

		$this->assertSame( '', trim( $output ) );
	}

	// ------------------------------------------------------------------
	// Renders notices
	// ------------------------------------------------------------------

	/**
	 * @test
	 */
	public function it_renders_notice_for_inactive_product(): void {
		$this->add_licenses(
			[
				[
					'slug'      => 'give-recurring',
					'product'   => 'give',
					'is_active' => false,
					'page_url'  => 'https://example.com/licenses',
				],
			]
		);

		$output = $this->capture_display();

		$this->assertStringContainsString( 'Give', $output );
		$this->assertStringContainsString( 'https://example.com/licenses', $output );
		$this->assertStringContainsString( 'data-uplink-notice-id="legacy-give"', $output );
	}

	/**
	 * @test
	 */
	public function it_renders_singular_message_for_one_addon(): void {
		$this->add_licenses(
			[
				[
					'slug'      => 'give-recurring',
					'product'   => 'give',
					'is_active' => false,
				],
			]
		);

		$output = $this->capture_display();

		$this->assertStringContainsString( '1 inactive Give license', $output );
		$this->assertStringNotContainsString( 'inactive Give licenses', $output );
	}

	/**
	 * @test
	 */
	public function it_renders_plural_message_for_multiple_addons(): void {
		$this->add_licenses(
			[
				[
					'slug'      => 'give-recurring',
					'product'   => 'give',
					'is_active' => false,
				],
				[
					'slug'      => 'give-stripe',
					'product'   => 'give',
					'is_active' => false,
				],
			]
		);

		$output = $this->capture_display();

		$this->assertStringContainsString( '2', $output );
		$this->assertStringContainsString( 'inactive Give licenses', $output );
	}

	/**
	 * @test
	 */
	public function it_groups_multiple_addons_under_same_product(): void {
		$this->add_licenses(
			[
				[
					'slug'      => 'give-recurring',
					'product'   => 'give',
					'is_active' => false,
				],
				[
					'slug'      => 'give-stripe',
					'product'   => 'give',
					'is_active' => false,
				],
				[
					'slug'      => 'give-fee',
					'product'   => 'give',
					'is_active' => false,
				],
			]
		);

		$output = $this->capture_display();

		$this->assertSame( 1, substr_count( $output, 'data-uplink-notice-id="legacy-give"' ) );
		$this->assertStringContainsString( '3', $output );
	}

	/**
	 * @test
	 */
	public function it_renders_separate_notices_for_different_products(): void {
		$this->add_licenses(
			[
				[
					'slug'      => 'give-recurring',
					'product'   => 'give',
					'is_active' => false,
				],
				[
					'slug'      => 'kadence-blocks',
					'product'   => 'kadence',
					'is_active' => false,
				],
			]
		);

		$output = $this->capture_display();

		$this->assertStringContainsString( 'data-uplink-notice-id="legacy-give"', $output );
		$this->assertStringContainsString( 'data-uplink-notice-id="legacy-kadence"', $output );
	}

	/**
	 * @test
	 */
	public function it_shows_notice_again_after_dismissal_expires(): void {
		$this->add_licenses(
			[
				[
					'slug'      => 'give-recurring',
					'product'   => 'give',
					'is_active' => false,
				],
			]
		);

		// Dismissal already expired.
		update_user_meta(
			$this->user_id,
			License_Notice_Handler::DISMISSED_META_KEY,
			[ 'legacy-give' => time() - 1 ]
		);

		$output = $this->capture_display();

		$this->assertStringContainsString( 'data-uplink-notice-id="legacy-give"', $output );
	}

	// ------------------------------------------------------------------
	// Script enqueue
	// ------------------------------------------------------------------

	/**
	 * @test
	 */
	public function it_enqueues_dismiss_script_when_notices_render(): void {
		$this->add_licenses(
			[
				[
					'slug'      => 'give-recurring',
					'product'   => 'give',
					'is_active' => false,
				],
			]
		);

		$this->capture_display();

		$this->assertTrue( wp_script_is( 'stellarwp-uplink-notice-dismiss', 'enqueued' ) );
	}

	/**
	 * @test
	 */
	public function it_does_not_enqueue_script_when_no_notices_render(): void {
		$this->capture_display();

		$this->assertFalse( wp_script_is( 'stellarwp-uplink-notice-dismiss', 'enqueued' ) );
	}

	// ------------------------------------------------------------------
	// Helpers
	// ------------------------------------------------------------------

	/**
	 * Run display() and return captured output.
	 */
	private function capture_display(): string {
		ob_start();
		$this->handler->display();
		return (string) ob_get_clean();
	}

	/**
	 * Add licenses to the filter.
	 *
	 * @param array<int, array<string, mixed>> $licenses
	 */
	private function add_licenses( array $licenses ): void {
		add_filter(
			'stellarwp/uplink/legacy_licenses',
			static function () use ( $licenses ) {
				return array_map(
					static function ( array $entry ): array {
						return array_merge(
							[
								'key'      => 'key-' . $entry['slug'],
								'name'     => $entry['slug'],
								'page_url' => 'https://example.com/licenses',
							],
							$entry
						);
					},
					$licenses
				);
			}
		);
	}
}
