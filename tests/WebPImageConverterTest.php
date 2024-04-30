<?php

namespace WebPImageConverter\Tests;

use Mockery;
use WP_Error;
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
					'basedir' => '/var/www/html/wp-content/uploads/2024/01/',
				]
			);

		$converter->set_image_source();

		$this->assertSame(
			'/var/www/html/wp-content/uploads/2024/01/sample.jpeg',
			$converter->abs_source
		);
		$this->assertConditionsMet();
	}

	public function test_set_image_destination() {
		$converter = Mockery::mock( WebPImageConverter::class )->makePartial();
		$converter->shouldAllowMockingProtectedMethods();

		// Plugin Source.
		Plugin::$source = 'https://example.com/wp-content/uploads/2024/01/sample.jpeg';

		// Image Source (Absolute Path).
		$converter->abs_source = '/var/www/html/wp-content/uploads/2024/01/sample.jpeg';

		$converter->set_image_destination();

		$this->assertSame(
			'/var/www/html/wp-content/uploads/2024/01/sample.webp',
			$converter->abs_dest
		);
		$this->assertSame(
			'https://example.com/wp-content/uploads/2024/01/sample.webp',
			$converter->rel_dest
		);
		$this->assertConditionsMet();
	}

	public function test_convert_fails_if_source_image_is_empty_and_returns_WP_error() {
		$converter = Mockery::mock( WebPImageConverter::class )->makePartial();
		$converter->shouldAllowMockingProtectedMethods();

		$converter->abs_source = '';

		$converter->shouldReceive( 'set_image_source' )
			->with()->once();

		$converter->shouldReceive( 'set_image_destination' )
			->with()->once();

		\WP_Mock::userFunction( '__' )
			->once()
			->with( 'Error: %s does not exist.', 'webp-img-converter' )
			->andReturn( 'Error: does not exist.' );

		$mock = Mockery::mock( WP_Error::class );

		$webp = $converter->convert();

		$this->assertInstanceOf( '\WP_Error', $webp );
		$this->assertConditionsMet();
	}

	public function test_convert_returns_same_if_destination_image_exists() {
		$converter = Mockery::mock( WebPImageConverter::class )->makePartial();
		$converter->shouldAllowMockingProtectedMethods();

		$converter->abs_source = __DIR__ . '/sample.jpeg';
		$converter->abs_dest   = __DIR__ . '/sample.webp';
		$converter->rel_dest   = str_replace( __DIR__, 'https://example.com/wp-content/uploads/2024/01', $converter->abs_dest );

		// Create Mock Images.
		$this->create_mock_image( $converter->abs_source );
		$this->create_mock_image( $converter->abs_dest );

		$converter->shouldReceive( 'set_image_source' )
			->with()->once();

		$converter->shouldReceive( 'set_image_destination' )
			->with()->once();

		$webp = $converter->convert();

		$this->assertSame( 'https://example.com/wp-content/uploads/2024/01/sample.webp', $webp );
		$this->assertConditionsMet();

		// Destroy Mock Images.
		$this->destroy_mock_image( $converter->abs_source );
		$this->destroy_mock_image( $converter->abs_dest );
	}

	public function test_convert_returns_webp() {
		$converter = Mockery::mock( WebPImageConverter::class )->makePartial();
		$converter->shouldAllowMockingProtectedMethods();

		$converter->abs_source = __DIR__ . '/sample.jpeg';
		$converter->abs_dest   = __DIR__ . '/sample.webp';
		$converter->rel_dest   = str_replace( __DIR__, 'https://example.com/wp-content/uploads/2024/01', $converter->abs_dest );

		// Create Mock Images.
		$this->create_mock_image( $converter->abs_source );

		$converter->shouldReceive( 'set_image_source' )
			->with()->once();

		$converter->shouldReceive( 'set_image_destination' )
			->with()->once();

		$converter->shouldReceive( 'get_options' )
			->once()
			->with()
			->andReturn(
				[
					'quality'     => 20,
					'max-quality' => 100,
					'converter'   => 'gd',
				]
			);

		$webp = $converter->convert();

		$this->assertTrue( file_exists( $converter->abs_dest ) );
		$this->assertSame( 'https://example.com/wp-content/uploads/2024/01/sample.webp', $webp );
		$this->assertConditionsMet();

		// Destroy Mock Images.
		$this->destroy_mock_image( $converter->abs_source );
		$this->destroy_mock_image( $converter->abs_dest );
	}

	public function test_convert_fails_on_empty_options_and_returns_WP_error() {
		$converter = Mockery::mock( WebPImageConverter::class )->makePartial();
		$converter->shouldAllowMockingProtectedMethods();

		$converter->abs_source = __DIR__ . '/sample.jpeg';
		$converter->abs_dest   = __DIR__ . '/sample.webp';
		$converter->rel_dest   = str_replace( __DIR__, 'https://example.com/wp-content/uploads/2024/01', $converter->abs_dest );

		// Create Mock Images.
		$this->create_mock_image( $converter->abs_source );

		$converter->shouldReceive( 'set_image_source' )
			->with()->once();

		$converter->shouldReceive( 'set_image_destination' )
			->with()->once();

		\WP_Mock::userFunction( '__' )
			->once()
			->with( 'Fatal Error: %s', 'webp-img-converter' )
			->andReturn( 'Fatal Error: %s' );

		$mock = Mockery::mock( WP_Error::class );

		$webp = $converter->convert();

		$this->assertInstanceOf( '\WP_Error', $webp );
		$this->assertConditionsMet();

		// Destroy Mock Images.
		$this->destroy_mock_image( $converter->abs_source );
	}

	public function create_mock_image( $image_file_name ) {
		// Create a blank image.
		$width  = 400;
		$height = 200;
		$image  = imagecreatetruecolor( $width, $height );

		// Set background color.
		$bg_color = imagecolorallocate( $image, 255, 255, 255 );
		imagefill( $image, 0, 0, $bg_color );
		imagejpeg( $image, $image_file_name );
	}

	public function destroy_mock_image( $image_file_name ) {
		if ( file_exists( $image_file_name ) ) {
			unlink( $image_file_name );
		}
	}
}
