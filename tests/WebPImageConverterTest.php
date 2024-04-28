<?php

namespace WebPImageConverter\Tests;

use Mockery;
use WP_Mock\Tools\TestCase;
use WebPImageConverter\WebPImageConverter;

/**
 * @covers \WebPImageConverter\WebPImageConverter
 */
class WebPImageConverterTest extends TestCase {
	public function setUp(): void {
		\WP_Mock::setUp();
	}

	public function tearDown(): void {
		\WP_Mock::tearDown();
	}
}
