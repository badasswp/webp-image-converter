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

		$this->instance->run();

		$this->assertConditionsMet();
	}
}
