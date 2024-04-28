<?php

namespace WebPImageConverter\Tests;

use Mockery;
use WP_Mock\Tools\TestCase;
use WebPImageConverter\Plugin;

/**
 * @covers Plugin
 */
class PluginTest extends TestCase {
	public function setUp(): void {
		\WP_Mock::setUp();
	}

	public function tearDown(): void {
		\WP_Mock::tearDown();
	}

	public function test_plugin_class_get_instance_returns_singleton() {
		$instance = Plugin::get_instance();

		$this->assertInstanceOf( Plugin::class, $instance );

		$instance1 = Plugin::get_instance();
		$instance2 = Plugin::get_instance();

		$this->assertSame( $instance1, $instance2, 'Instances should be the same' );
		$this->assertConditionsMet();
	}
}
