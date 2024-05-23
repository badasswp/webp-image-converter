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
		\WP_Mock::expectActionAdded( 'add_attachment', [ $this->instance, 'generate_webp_image' ] );
		\WP_Mock::expectFilterAdded( 'wp_generate_attachment_metadata', [ $this->instance, 'generate_webp_srcset_images' ], 10, 3 );
		\WP_Mock::expectFilterAdded( 'render_block', [ $this->instance, 'filter_render_image_block' ], 20, 2 );
		\WP_Mock::expectFilterAdded( 'wp_get_attachment_image', [ $this->instance, 'filter_wp_get_attachment_image' ], 10, 5 );
		\WP_Mock::expectFilterAdded( 'post_thumbnail_html', [ $this->instance, 'filter_post_thumbnail_html' ], 10, 5 );
		\WP_Mock::expectActionAdded( 'delete_attachment', [ $this->instance, 'remove_webp_images' ] );
		\WP_Mock::expectActionAdded( 'admin_menu', [ $this->instance, 'add_webp_image_menu' ] );
		\WP_Mock::expectActionAdded( 'webp_img_convert', [ $this->instance, 'add_webp_meta_to_attachment' ] );

		$this->instance->run();

		$this->assertConditionsMet();
	}

	public function test_generate_webp_image_passes() {
		$this->instance->converter = Mockery::mock( WebPImageConverter::class )->makePartial();
		$this->instance->converter->shouldAllowMockingProtectedMethods();

		Plugin::$source = [
			'id'  => 1,
			'url' => 'https://example.com/wp-content/uploads/2024/01/sample.jpeg',
		];

		\WP_Mock::userFunction( 'wp_get_attachment_url' )
			->once()
			->with( 1 )
			->andReturn( 'https://example.com/wp-content/uploads/2024/01/sample.jpeg' );

		$this->instance->converter->shouldReceive( 'convert' )
			->once()
			->andReturn( 'https://example.com/wp-content/uploads/2024/01/sample.webp' );

		$this->instance->generate_webp_image( 1 );

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

	public function test_filter_render_image_block_returns_empty_string() {
		$instance = Mockery::mock( Plugin::class )->makePartial();
		$instance->shouldAllowMockingProtectedMethods();

		$image = $instance->filter_render_image_block( '', [] );

		$this->assertSame( '', $image );
		$this->assertConditionsMet();
	}

	public function test_filter_render_image_block_returns_img_html() {
		$instance = Mockery::mock( Plugin::class )->makePartial();
		$instance->shouldAllowMockingProtectedMethods();

		$instance->shouldReceive( 'get_webp_image_html' )
			->once()
			->with( '<img src="sample.jpeg"/>' )
			->andReturn( '<img src="sample.webp"/>' );

		$image = $instance->filter_render_image_block( '<img src="sample.jpeg"/>', [] );

		$this->assertSame( '<img src="sample.webp"/>', $image );
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
			->with( '<img src="sample.jpeg"/>', 1 )
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
			->with( '<img src="sample.jpeg"/>', 2 )
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

	public function test_get_webp_html_bails_out_and_returns_same_image_html() {
		$instance = Mockery::mock( Plugin::class )->makePartial();
		$instance->shouldAllowMockingProtectedMethods();

		$instance->converter = Mockery::mock( WebPImageConverter::class )->makePartial();
		$instance->converter->shouldAllowMockingProtectedMethods();

		$error = Mockery::mock( \WP_Error::class )->makePartial();

		$instance->converter->shouldReceive( 'convert' )
			->once()->with()
			->andReturn( $error );

		\WP_Mock::userFunction( 'is_wp_error' )
			->once()
			->with( $error )
			->andReturn( true );

		$img_html = $instance->_get_webp_html( 'https://example.com/wp-content/uploads/2024/01/sample.pdf', '<img src="https://example.com/wp-content/uploads/2024/01/sample.pdf"/>', 1 );

		$this->assertSame( $img_html, '<img src="https://example.com/wp-content/uploads/2024/01/sample.pdf"/>' );
		$this->assertConditionsMet();
	}

	public function test_get_webp_html_returns_new_image_html() {
		$instance = Mockery::mock( Plugin::class )->makePartial();
		$instance->shouldAllowMockingProtectedMethods();

		$instance->converter = Mockery::mock( WebPImageConverter::class )->makePartial();
		$instance->converter->shouldAllowMockingProtectedMethods();

		$this->create_mock_image( __DIR__ . '/sample.webp' );
		$instance->converter->abs_dest = __DIR__ . '/sample.webp';

		$error = Mockery::mock( \WP_Error::class )->makePartial();

		Plugin::$source['url'] = 'https://example.com/wp-content/uploads/2024/01/sample.jpeg';

		$instance->converter->shouldReceive( 'convert' )
			->once()->with()
			->andReturn( 'https://example.com/wp-content/uploads/2024/01/sample.webp' );

		\WP_Mock::userFunction( 'is_wp_error' )
			->once()
			->with( 'https://example.com/wp-content/uploads/2024/01/sample.webp' )
			->andReturn( false );

		$img_html = $instance->_get_webp_html( 'https://example.com/wp-content/uploads/2024/01/sample.jpeg', '<img src="https://example.com/wp-content/uploads/2024/01/sample.jpeg"/>', 1 );

		$this->assertSame( $img_html, '<img src="https://example.com/wp-content/uploads/2024/01/sample.webp"/>' );
		$this->assertConditionsMet();

		$this->destroy_mock_image( __DIR__ . '/sample.webp' );
	}

	public function test_remove_webp_images_fails_if_not_image() {
		$instance = Mockery::mock( Plugin::class )->makePartial();
		$instance->shouldAllowMockingProtectedMethods();

		\WP_Mock::userFunction( 'wp_attachment_is_image' )
			->once()
			->with( 1 )
			->andReturn( false );

		$image = $instance->remove_webp_images( 1 );

		$this->assertConditionsMet();
	}

	public function test_remove_webp_images_bails_if_no_parent_image_abs_path_or_metadata_is_found() {
		$instance = Mockery::mock( Plugin::class )->makePartial();
		$instance->shouldAllowMockingProtectedMethods();

		\WP_Mock::userFunction( 'wp_attachment_is_image' )
			->once()
			->with( 1 )
			->andReturn( true );

		\WP_Mock::userFunction( 'get_attached_file' )
			->once()
			->with( 1 )
			->andReturn( '' );

		\WP_Mock::userFunction( 'wp_get_attachment_metadata' )
			->once()
			->with( 1 )
			->andReturn( [] );

		$image = $instance->remove_webp_images( 1 );

		$this->assertConditionsMet();
	}

	public function test_remove_webp_images_removes_parent_webp_image() {
		$instance = Mockery::mock( Plugin::class )->makePartial();
		$instance->shouldAllowMockingProtectedMethods();

		\WP_Mock::userFunction( 'wp_attachment_is_image' )
			->once()
			->with( 1 )
			->andReturn( true );

		\WP_Mock::userFunction( 'get_attached_file' )
			->once()
			->with( 1 )
			->andReturn( __DIR__ . '/sample.jpeg' );

		\WP_Mock::expectAction( 'webp_img_delete', __DIR__ . '/sample.webp', 1 );

		\WP_Mock::userFunction( 'wp_get_attachment_metadata' )
			->once()
			->with( 1 )
			->andReturn( [] );

		// Create Mock Images.
		$this->create_mock_image( __DIR__ . '/sample.webp' );

		$image = $instance->remove_webp_images( 1 );

		$this->assertConditionsMet();
	}

	public function test_remove_webp_images_removes_webp_metadata_image() {
		$instance = Mockery::mock( Plugin::class )->makePartial();
		$instance->shouldAllowMockingProtectedMethods();

		\WP_Mock::userFunction( 'wp_attachment_is_image' )
			->once()
			->with( 1 )
			->andReturn( true );

		\WP_Mock::userFunction( 'get_attached_file' )
			->once()
			->with( 1 )
			->andReturn( __DIR__ . '/sample.jpeg' );

		\WP_Mock::expectAction( 'webp_img_delete', __DIR__ . '/sample.webp', 1 );

		\WP_Mock::userFunction(
			'trailingslashit',
			[
				'times'  => 3,
				'return' => function ( $text ) {
					return $text . '/';
				},
			]
		);

		\WP_Mock::userFunction( 'wp_get_attachment_metadata' )
			->once()
			->with( 1 )
			->andReturn(
				[
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
				]
			);

		\WP_Mock::expectAction( 'webp_img_metadata_delete', __DIR__ . '/sample1.webp', 1 );
		\WP_Mock::expectAction( 'webp_img_metadata_delete', __DIR__ . '/sample2.webp', 1 );
		\WP_Mock::expectAction( 'webp_img_metadata_delete', __DIR__ . '/sample3.webp', 1 );

		// Create Mock Images.
		$this->create_mock_image( __DIR__ . '/sample.webp' );
		$this->create_mock_image( __DIR__ . '/sample1.webp' );
		$this->create_mock_image( __DIR__ . '/sample2.webp' );
		$this->create_mock_image( __DIR__ . '/sample3.webp' );

		$image = $instance->remove_webp_images( 1 );

		$this->assertConditionsMet();
	}

	public function test_add_webp_image_menu() {
		\WP_Mock::userFunction( 'add_submenu_page' )
			->once()
			->with(
				'upload.php',
				'WebP Image Converter',
				'WebP Image Converter',
				'manage_options',
				'webp-image-converter',
				[ $this->instance, 'webp_image_menu_page' ]
			)
			->andReturn( null );

		$menu = $this->instance->add_webp_image_menu();

		$this->assertNull( $menu );
		$this->assertConditionsMet();
	}

	public function test_webp_image_menu_page() {
		$this->instance->webp_image_menu_page();

		$this->expectOutputString(
			'<div class="wrap">
				<h1>WebP Image Converter</h1>
				<p>Manage all your WebP generated images here.</p>
			</div>'
		);
		$this->assertConditionsMet();
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
