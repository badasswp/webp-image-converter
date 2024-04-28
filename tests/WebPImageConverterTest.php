<?php

namespace WebPImageConverter\Tests;

use Mockery;
use WP_Mock\Tools\TestCase;
use WebPImageConverter\Plugin;
use WebPImageConverter\WebPImageConverter;

/**
 * @covers \WebPImageConverter\WebPImageConverter
 */
class WebPImageConverterTest extends TestCase {
	public function setUp(): void {
		\WP_Mock::setUp();

		$this->converter = new WebPImageConverter();
	}

	public function tearDown(): void {
		\WP_Mock::tearDown();
	}

	public function test_get_options_returns_default_settings() {
		$converter = Mockery::mock( WebPImageConverter::class )->makePartial();
		$converter->shouldAllowMockingProtectedMethods();

		\WP_Mock::expectFilter(
			'webp_img_options',
			[
				'quality'     => 20,
				'max-quality' => 100,
				'converter'   => 'gd',
			]
		);

		$options = $converter->get_options();

		$this->assertSame(
			$options,
			[
				'quality'     => 20,
				'max-quality' => 100,
				'converter'   => 'gd',
			]
		);
		$this->assertConditionsMet();
	}

	public function test_get_options_returns_modified_settings() {
		$converter = Mockery::mock( WebPImageConverter::class )->makePartial();
		$converter->shouldAllowMockingProtectedMethods();

		\WP_Mock::onFilter( 'webp_img_options' )
			->with(
				[
					'quality'     => 20,
					'max-quality' => 100,
					'converter'   => 'gd',
				]
			)
			->reply(
				[
					'quality'   => 50,
					'converter' => 'imagick',
				]
			);

		$options = $converter->get_options();

		$this->assertSame(
			$options,
			[
				'quality'   => 50,
				'converter' => 'imagick',
			]
		);
		$this->assertConditionsMet();
	}

	public function test_set_image_source() {
		$converter = Mockery::mock( WebPImageConverter::class )->makePartial();
		$converter->shouldAllowMockingProtectedMethods();

		Plugin::$source = 'https://example.com/wp-content/uploads/2024/01/sample.jpeg';

		\WP_Mock::userFunction( 'wp_upload_dir' )
			->once()
			->andReturn(
				[
					'baseurl' => 'https://example.com/wp-content/uploads/2024/01/',
					'basedir' => '/var/www/html/wp-content/uploads/2024/01/'
				]
			);

		$converter->set_image_source();

		$this->assertSame(
			'/var/www/html/wp-content/uploads/2024/01/sample.jpeg',
			$converter->abs_source
		);
		$this->assertConditionsMet();
	}
}
