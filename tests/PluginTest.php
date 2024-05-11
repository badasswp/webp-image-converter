<?php

namespace WebPImageConverter\Tests;

use Mockery;
use WP_Mock\Tools\TestCase;
use WebPImageConverter\Plugin;
use WebPImageConverter\WebPImageConverter;

/**
 * @covers \WebPImageConverter\Plugin
 */
class PluginTest extends TestCase {
	public $instance;

	public function setUp(): void {
		\WP_Mock::setUp();

		$this->instance = Plugin::get_instance();
	}

	public function tearDown(): void {
		\WP_Mock::tearDown();
	}

	public function test_get_instance_returns_singleton() {
		$instance = Plugin::get_instance();

		$this->assertInstanceOf( Plugin::class, $instance );

		$instance1 = Plugin::get_instance();
		$instance2 = Plugin::get_instance();

		$this->assertSame( $instance1, $instance2, 'Instances should be the same' );
		$this->assertConditionsMet();
	}

	public function test_run() {
		\WP_Mock::expectActionAdded( 'add_attachment', [ $this->instance, 'action_add_attachment' ] );
		\WP_Mock::expectFilterAdded( 'wp_get_attachment_image', [ $this->instance, 'filter_wp_get_attachment_image' ], 10, 5 );
		\WP_Mock::expectFilterAdded( 'post_thumbnail_html', [ $this->instance, 'filter_post_thumbnail_html' ], 10, 5 );
		\WP_Mock::expectFilterAdded( 'wp_generate_attachment_metadata', [ $this->instance, 'generate_webp_srcset_images' ], 10, 3 );
		\WP_Mock::expectActionAdded( 'delete_attachment', [ $this->instance, 'remove_webp_images' ] );

		$this->instance->run();

		$this->assertConditionsMet();
	}

	public function test_action_add_attachment_passes() {
		$this->instance->converter = Mockery::mock( WebPImageConverter::class )->makePartial();
		$this->instance->converter->shouldAllowMockingProtectedMethods();

		\WP_Mock::userFunction( 'wp_get_attachment_url' )
			->once()
			->with( 1 )
			->andReturn( 'https://example.com/wp-content/uploads/2024/01/sample.jpeg' );

		$this->instance->converter->shouldReceive( 'convert' )
			->once()
			->andReturn( 'https://example.com/wp-content/uploads/2024/01/sample.webp' );

		\WP_Mock::expectAction(
			'webp_img_after',
			'https://example.com/wp-content/uploads/2024/01/sample.webp',
			1
		);

		$this->instance->action_add_attachment( 1 );

		$this->assertConditionsMet();
	}

	public function test_filter_wp_get_attachment_image_fails_and_returns_empty_string() {
		$image = $this->instance->filter_wp_get_attachment_image( '', 1, [], true, [] );

		$this->assertSame( '', $image );
		$this->assertConditionsMet();
	}

	public function test_filter_wp_get_attachment_image_returns_img_html() {
		$instance = Mockery::mock( Plugin::class )->makePartial();
		$instance->shouldAllowMockingProtectedMethods();

		$instance->shouldReceive( 'get_webp_image_html' )
			->once()
			->with( '<img src="sample.jpeg"/>' )
			->andReturn( '<img src="sample.webp"/>' );

		\WP_Mock::onFilter( 'webp_img_attachment_html' )
			->with(
				'<img src="sample.webp"/>',
				1
			)
			->reply(
				'<img src="sample.webp"/>'
			);

		$image = $instance->filter_wp_get_attachment_image( '<img src="sample.jpeg"/>', 1, [], true, [] );

		$this->assertSame( '<img src="sample.webp"/>', $image );
		$this->assertConditionsMet();
	}

	public function test_filter_post_thumbnail_html_fails_and_returns_empty_string() {
		$image = $this->instance->filter_post_thumbnail_html( '', 1, [], true, [] );

		$this->assertSame( '', $image );
		$this->assertConditionsMet();
	}

	public function test_filter_post_thumbnail_html_returns_img_html() {
		$instance = Mockery::mock( Plugin::class )->makePartial();
		$instance->shouldAllowMockingProtectedMethods();

		$instance->shouldReceive( 'get_webp_image_html' )
			->once()
			->with( '<img src="sample.jpeg"/>' )
			->andReturn( '<img src="sample.webp"/>' );

		\WP_Mock::onFilter( 'webp_img_thumbnail_html' )
			->with(
				'<img src="sample.webp"/>',
				2
			)
			->reply(
				'<img src="sample.webp"/>'
			);

		$image = $instance->filter_post_thumbnail_html( '<img src="sample.jpeg"/>', 1, 2, [], [] );

		$this->assertSame( '<img src="sample.webp"/>', $image );
		$this->assertConditionsMet();
	}

	public function test_get_webp_image_html_returns_emtpy_image_if_empty() {
		$instance = Mockery::mock( Plugin::class )->makePartial();
		$instance->shouldAllowMockingProtectedMethods();

		$image = $instance->get_webp_image_html( '' );

		$this->assertSame( '', $image );
		$this->assertConditionsMet();
	}

	public function test_get_webp_image_html_returns_html_if_no_image_in_html() {
		$instance = Mockery::mock( Plugin::class )->makePartial();
		$instance->shouldAllowMockingProtectedMethods();

		$image = $instance->get_webp_image_html( '<div></div>' );

		$this->assertSame( '<div></div>', $image );
		$this->assertConditionsMet();
	}

	public function test_get_webp_image_html_returns_original_html_if_no_image_src_is_in_html() {
		$instance = Mockery::mock( Plugin::class )->makePartial();
		$instance->shouldAllowMockingProtectedMethods();

		$image = $instance->get_webp_image_html( '<figure><img src=""/></figure>' );

		$this->assertSame( '<figure><img src=""/></figure>', $image );
		$this->assertConditionsMet();
	}

	public function test_generate_webp_srcset_images() {
		$instance = Mockery::mock( Plugin::class )->makePartial();
		$instance->shouldAllowMockingProtectedMethods();

		$instance->converter = Mockery::mock( WebPImageConverter::class )->makePartial();
		$instance->converter->shouldAllowMockingProtectedMethods();

		$data = [
			'sizes' => [
				[
					'file' => 'sample1.jpeg',
				],
				[
					'file' => 'sample2.jpeg',
				],
				[
					'file' => 'sample3.jpeg',
				],
			],
		];

		\WP_Mock::userFunction( 'wp_get_attachment_image_url' )
			->once()
			->with( 1 )
			->andReturn( 'https://example.com/wp-content/uploads/2024/01/sample.jpeg' );

		\WP_Mock::userFunction( 'trailingslashit' )
			->times( 3 )
			->with( 'https://example.com/wp-content/uploads/2024/01' )
			->andReturn( 'https://example.com/wp-content/uploads/2024/01/' );

		$instance->converter->shouldReceive( 'convert' )
			->times( 3 );

		$srcset = $instance->generate_webp_srcset_images( $data, 1, 'create' );

		$this->assertConditionsMet();
	}
}
