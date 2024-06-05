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

		$_POST = [];
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
		\WP_Mock::expectActionAdded( 'add_attachment', [ $this->instance, 'generate_webp_image' ], 10, 1 );
		\WP_Mock::expectFilterAdded( 'wp_generate_attachment_metadata', [ $this->instance, 'generate_webp_srcset_images' ], 10, 3 );
		\WP_Mock::expectFilterAdded( 'render_block', [ $this->instance, 'filter_render_image_block' ], 20, 2 );
		\WP_Mock::expectFilterAdded( 'wp_get_attachment_image', [ $this->instance, 'filter_wp_get_attachment_image' ], 10, 5 );
		\WP_Mock::expectFilterAdded( 'post_thumbnail_html', [ $this->instance, 'filter_post_thumbnail_html' ], 10, 5 );
		\WP_Mock::expectActionAdded( 'delete_attachment', [ $this->instance, 'delete_webp_images' ], 10, 1 );
		\WP_Mock::expectActionAdded( 'admin_menu', [ $this->instance, 'add_webp_image_menu' ] );
		\WP_Mock::expectActionAdded( 'webp_img_convert', [ $this->instance, 'add_webp_meta_to_attachment' ], 10, 2 );
		\WP_Mock::expectFilterAdded( 'attachment_fields_to_edit', [ $this->instance, 'add_webp_attachment_fields' ], 10, 2 );
		\WP_Mock::expectActionAdded( 'admin_init', [ $this->instance, 'add_webp_settings' ] );

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

	public function test_delete_webp_images_fails_if_not_image() {
		$instance = Mockery::mock( Plugin::class )->makePartial();
		$instance->shouldAllowMockingProtectedMethods();

		\WP_Mock::userFunction( 'wp_attachment_is_image' )
			->once()
			->with( 1 )
			->andReturn( false );

		$image = $instance->delete_webp_images( 1 );

		$this->assertConditionsMet();
	}

	public function test_delete_webp_images_bails_if_no_parent_image_abs_path_or_metadata_is_found() {
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

		$image = $instance->delete_webp_images( 1 );

		$this->assertConditionsMet();
	}

	public function test_delete_webp_images_removes_parent_webp_image() {
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

		$image = $instance->delete_webp_images( 1 );

		$this->assertConditionsMet();
	}

	public function test_delete_webp_images_removes_webp_metadata_image() {
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

		$image = $instance->delete_webp_images( 1 );

		$this->assertConditionsMet();
	}

	public function test_add_webp_image_menu() {
		\WP_Mock::userFunction( 'add_submenu_page' )
			->once()
			->with(
				'upload.php',
				'Image Converter for WebP',
				'Image Converter for WebP',
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
		\WP_Mock::userFunction( 'plugin_dir_path' )
			->once()
			->with( Plugin::$file )
			->andReturn( './inc' );

		\WP_Mock::userFunction( 'wp_nonce_field' )
			->with( 'webp_settings_action', 'webp_settings_nonce' )
			->andReturn( '' );

		\WP_Mock::userFunction( 'esc_url' )
			->once()
			->with( '/wp-admin/upload.php?page=webp-image-converter' )
			->andReturn( '/wp-admin/upload.php?page=webp-image-converter' );

		\WP_Mock::userFunction( 'get_option' )
			->times( 2 )
			->with( 'webp_img_converter', [] )
			->andReturn(
				[
					'quality'   => 75,
					'converter' => 'imagick',
				]
			);

		\WP_Mock::userFunction( 'esc_attr' )
			->once()
			->with( 75 )
			->andReturn( '75' );

		\WP_Mock::userFunction(
			'esc_attr',
			[
				'times'  => 5,
				'return' => function ( $text ) {
					return $text;
				},
			]
		);

		\WP_Mock::userFunction(
			'esc_html',
			[
				'times'  => 10,
				'return' => function ( $text ) {
					return $text;
				},
			]
		);

		$_SERVER = [
			'REQUEST_URI' => '/wp-admin/upload.php?page=webp-image-converter',
		];

		ob_start();
		$this->instance->webp_image_menu_page();
		$output = ob_get_clean();

		$this->assertEquals(
			$output,
			file_get_contents( __DIR__ . '/Views/settings.html' )
		);
		$this->assertConditionsMet();
	}

	public function test_add_webp_settings_bails_out_if_POST_is_not_set() {
		$settings = $this->instance->add_webp_settings();

		$this->assertNull( $settings );
		$this->assertConditionsMet();
	}

	public function test_add_webp_settings_bails_out_if_any_nonce_settings_is_missing() {
		$_POST = [
			'webp_save_settings' => true,
		];

		$settings = $this->instance->add_webp_settings();

		$this->assertNull( $settings );
		$this->assertConditionsMet();
	}

	public function test_add_webp_settings_bails_out_if_nonce_verification_fails() {
		$_POST = [
			'webp_save_settings'  => true,
			'webp_settings_nonce' => 'a8vbq3cg3sa',
		];

		\WP_Mock::userFunction( 'wp_verify_nonce' )
			->once()
			->with( 'a8vbq3cg3sa', 'webp_settings_action' )
			->andReturn( false );

		$settings = $this->instance->add_webp_settings();

		$this->assertNull( $settings );
		$this->assertConditionsMet();
	}

	public function test_add_webp_settings_passes() {
		$_POST = [
			'webp_save_settings'  => true,
			'webp_settings_nonce' => 'a8vbq3cg3sa',
			'quality'             => 75,
			'converter'           => 'gd',
		];

		\WP_Mock::userFunction( 'wp_verify_nonce' )
			->times( 3 )
			->with( 'a8vbq3cg3sa', 'webp_settings_action' )
			->andReturn( true );

		\WP_Mock::userFunction( 'update_option' )
			->once()
			->with(
				'webp_img_converter',
				[
					'quality'   => 75,
					'converter' => 'gd',
				]
			)
			->andReturn( null );

		\WP_Mock::userFunction(
			'sanitize_text_field',
			[
				'times'  => 2,
				'return' => function ( $text ) {
					return $text;
				},
			]
		);

		$settings = $this->instance->add_webp_settings();

		$this->assertNull( $settings );
		$this->assertConditionsMet();
	}

	public function test_add_webp_meta_to_attachment_bails_out() {
		$webp = Mockery::mock( '\WP_Error' )->makePartial();

		\WP_Mock::userFunction( 'is_wp_error' )
			->once()
			->with( $webp )
			->andReturn( true );

		$this->instance->add_webp_meta_to_attachment( $webp, 1 );

		$this->assertConditionsMet();
	}

	public function test_add_webp_meta_to_attachment_updates_post_meta() {
		$webp = 'https://example.com/wp-content/uploads/2024/01/sample.webp';

		\WP_Mock::userFunction( 'is_wp_error' )
			->once()
			->with( $webp )
			->andReturn( false );

		\WP_Mock::userFunction( 'get_post_meta' )
			->once()
			->with( 1, 'webp_img', true )
			->andReturn( '' );

		\WP_Mock::userFunction( 'update_post_meta' )
			->once()
			->with( 1, 'webp_img', 'https://example.com/wp-content/uploads/2024/01/sample.webp' )
			->andReturn( null );

		$this->instance->add_webp_meta_to_attachment( $webp, 1 );

		$this->assertConditionsMet();
	}

	public function test_add_webp_attachment_fields_escapes_array_return_type() {
		$post     = Mockery::mock( \WP_Post::class )->makePartial();
		$post->ID = 1;

		\WP_Mock::userFunction( 'get_post_meta' )
			->once()
			->with( 1, 'webp_img', true )
			->andReturn( [] );

		$expected = $this->instance->add_webp_attachment_fields( [], $post );

		$this->assertSame(
			[
				'webp_img' => [
					'label' => 'WebP Image',
					'input' => 'text',
					'value' => '',
					'helps' => 'WebP Image generated by Image Converter for WebP.',
				],
			],
			$expected
		);
		$this->assertConditionsMet();
	}

	public function test_add_webp_attachment_fields() {
		$webp = 'https://example.com/wp-content/uploads/2024/01/sample.webp';

		$post     = Mockery::mock( \WP_Post::class )->makePartial();
		$post->ID = 1;

		\WP_Mock::userFunction( 'get_post_meta' )
			->once()
			->with( 1, 'webp_img', true )
			->andReturn( $webp );

		$expected = $this->instance->add_webp_attachment_fields( [], $post );

		$this->assertSame(
			[
				'webp_img' => [
					'label' => 'WebP Image',
					'input' => 'text',
					'value' => 'https://example.com/wp-content/uploads/2024/01/sample.webp',
					'helps' => 'WebP Image generated by Image Converter for WebP.',
				],
			],
			$expected
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
