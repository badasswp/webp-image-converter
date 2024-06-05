<?php
/**
 * Main Plugin class.
 *
 * This class represents the core of the plugin.
 * It initializes the plugin, manages the singleton instance.
 *
 * @package WebPImageConverter
 */

namespace WebPImageConverter;

use DOMDocument;
use WebPImageConverter\WebPImageConverter;

class Plugin {
	/**
	 * Plugin instance.
	 *
	 * @since 1.0.0
	 *
	 * @var Plugin
	 */
	protected static $instance;

	/**
	 * Converter Instance.
	 *
	 * @since 1.0.0
	 *
	 * @var WebPImageConverter
	 */
	public WebPImageConverter $converter;

	/**
	 * Source Props.
	 *
	 * @since 1.0.0
	 *
	 * @var mixed[]
	 */
	public static $source;

	/**
	 * Plugin File.
	 *
	 * @since 1.0.2
	 *
	 * @var string
	 */
	public static $file = __FILE__;

	/**
	 * Set up.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function __construct() {
		$this->converter = new WebPImageConverter();
	}

	/**
	 * Get Instance.
	 *
	 * Return singeleton instance for Plugin.
	 *
	 * @since 1.0.0
	 *
	 * @return Plugin
	 */
	public static function get_instance(): Plugin {
		if ( null === static::$instance ) {
			static::$instance = new self();
		}

		return static::$instance;
	}

	/**
	 * Bind to WP.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function run(): void {
		add_action( 'add_attachment', [ $this, 'generate_webp_image' ], 10, 1 );
		add_filter( 'wp_generate_attachment_metadata', [ $this, 'generate_webp_srcset_images' ], 10, 3 );
		add_filter( 'render_block', [ $this, 'filter_render_image_block' ], 20, 2 );
		add_filter( 'wp_get_attachment_image', [ $this, 'filter_wp_get_attachment_image' ], 10, 5 );
		add_filter( 'post_thumbnail_html', [ $this, 'filter_post_thumbnail_html' ], 10, 5 );
		add_action( 'delete_attachment', [ $this, 'delete_webp_images' ], 10, 1 );
		add_action( 'admin_menu', [ $this, 'add_webp_image_menu' ] );
		add_action( 'webp_img_convert', [ $this, 'add_webp_meta_to_attachment' ], 10, 2 );
		add_filter( 'attachment_fields_to_edit', [ $this, 'add_webp_attachment_fields' ], 10, 2 );
		add_action( 'admin_init', [ $this, 'add_webp_settings' ] );
	}

	/**
	 * Generate WebP on add_attachment.
	 *
	 * This generates WebP images when users add new images
	 * to the WP media.
	 *
	 * @since 1.0.0
	 *
	 * @param  int $attachment_id Image ID.
	 * @return void
	 */
	public function generate_webp_image( $attachment_id ): void {
		// Get source props.
		static::$source = [
			'id'  => (int) $attachment_id,
			'url' => (string) wp_get_attachment_url( $attachment_id ),
		];

		// Convert to WebP image.
		$webp = $this->converter->convert();
	}

	/**
	 * Generate WebP images for metadata.
	 *
	 * Get WebP images for the various sizes generated by WP
	 * when the user adds a new image to the WP media.
	 *
	 * @since 1.0.0
	 *
	 * @param mixed[] $metadata      An array of attachment meta data.
	 * @param int     $attachment_id Attachment ID.
	 * @param string  $context       Additional context. Can be 'create' or 'update'.
	 *
	 * @return mixed[]
	 */
	public function generate_webp_srcset_images( $metadata, $attachment_id, $context ): array {
		// Get parent image URL.
		$img_url = (string) wp_get_attachment_image_url( $attachment_id );

		// Get image path prefix.
		$img_url_prefix = substr( $img_url, 0, (int) strrpos( $img_url, '/' ) );

		// Convert srcset images.
		foreach ( $metadata['sizes'] as $img ) {
			static::$source = [
				'id'  => (int) $attachment_id,
				'url' => trailingslashit( $img_url_prefix ) . $img['file'],
			];

			$this->converter->convert();
		}

		return $metadata;
	}

	/**
	 * Render Image Block with WebP Images.
	 *
	 * Loop through each block and swap regular images for
	 * WebP versions.
	 *
	 * @since 1.0.0
	 *
	 * @param string  $html  Image HTML.
	 * @param mixed[] $block Block array.
	 *
	 * @return string
	 */
	public function filter_render_image_block( $html, $block ): string {
		// Bail out, if empty or NOT image.
		if ( empty( $html ) || ! preg_match( '/<img.*>/', $html, $image ) ) {
			return $html;
		}

		return $this->get_webp_image_html( $html );
	}

	/**
	 * Generate WebP on wp_get_attachment_image.
	 *
	 * Filter WP image on the fly for image display used in
	 * posts, pages and so on.
	 *
	 * @since 1.0.0
	 *
	 * @param string       $html          HTML img element or empty string on failure.
	 * @param int          $attachment_id Image attachment ID.
	 * @param string|int[] $size          Requested image size.
	 * @param bool         $icon          Whether the image should be treated as an icon.
	 * @param string[]     $attr          Array of attribute values for the image markup, keyed by attribute name.
	 *
	 * @return string
	 */
	public function filter_wp_get_attachment_image( $html, $attachment_id, $size, $icon, $attr ): string {
		if ( empty( $html ) ) {
			return $html;
		}

		$html = $this->get_webp_image_html( $html, $attachment_id );

		/**
		 * Filter WebP Image HTML.
		 *
		 * @since 1.0.0
		 *
		 * @param string $html          WebP Image HTML.
		 * @param int    $attachment_id Image ID.
		 *
		 * @return string
		 */
		return (string) apply_filters( 'webp_img_attachment_html', $html, $attachment_id );
	}

	/**
	 * Generate WebP on post_thumbnail_html.
	 *
	 * Filter WP post thumbnail by grabbing the DOM and
	 * replacing with generated WebP images.
	 *
	 * @since 1.0.0
	 *
	 * @param string         $html         The post thumbnail HTML.
	 * @param int            $post_id      The post ID.
	 * @param int            $thumbnail_id The post thumbnail ID, or 0 if there isn't one.
	 * @param string|int[]   $size         Requested image size.
	 * @param string|mixed[] $attr         Query string or array of attributes.
	 *
	 * @return string
	 */
	public function filter_post_thumbnail_html( $html, $post_id, $thumbnail_id, $size, $attr ): string {
		if ( empty( $html ) ) {
			return $html;
		}

		$html = $this->get_webp_image_html( $html, $thumbnail_id );

		/**
		 * Filter WebP Image Thumbnail HTML.
		 *
		 * @since 1.0.0
		 *
		 * @param string $html         WebP Image HTML.
		 * @param int    $thumbnail_id The post thumbnail ID, or 0 if there isn't one.
		 *
		 * @return string
		 */
		return (string) apply_filters( 'webp_img_thumbnail_html', $html, $thumbnail_id );
	}

	/**
	 * Get WebP image HTML.
	 *
	 * This generic method uses the original image HTML to generate
	 * a WebP-Image HTML. This is useful for images that pre-date the installation
	 * of the plugin on a WP Instance.
	 *
	 * @since 1.0.0
	 *
	 * @param string $html Image HTML.
	 * @param int    $id   Image Attachment ID.
	 *
	 * @return string
	 */
	protected function get_webp_image_html( $html, $id = 0 ): string {
		// Bail out, if empty or NOT image.
		if ( empty( $html ) || ! preg_match( '/<img.*>/', $html, $image ) ) {
			return $html;
		}

		// Get DOM object.
		$dom = new DOMDocument();
		$dom->loadHTML( $html, LIBXML_NOERROR );

		// Generate WebP images.
		foreach ( $dom->getElementsByTagName( 'img' ) as $image ) {
			// For the src image.
			$src = $image->getAttribute( 'src' );

			if ( empty( $src ) ) {
				return $html;
			}

			// For the srcset images.
			$srcset = $image->getAttribute( 'srcset' );

			if ( empty( $srcset ) ) {
				return $html;
			}

			preg_match_all( '/http\S+\b/', $srcset, $image_urls );

			foreach ( $image_urls[0] as $img_url ) {
				$html = $this->_get_webp_html( $img_url, $html, $id );
			}
		}

		return $html;
	}

	/**
	 * Reusable method for obtaining new Image HTML string.
	 *
	 * @since 1.0.0
	 *
	 * @param string $img_url  Relative path to Image - 'https://example.com/wp-content/uploads/2024/01/sample.png'.
	 * @param string $img_html The Image HTML - '<img src="sample.png"/>'.
	 * @param int    $img_id   Image Attachment ID.
	 *
	 * @return string
	 */
	protected function _get_webp_html( $img_url, $img_html, $img_id ): string {
		// Set Source.
		static::$source = [
			'id'  => $img_id,
			'url' => $img_url,
		];

		// Convert image to WebP.
		$webp = $this->converter->convert();

		// Replace image with WebP.
		if ( ! is_wp_error( $webp ) && file_exists( $this->converter->abs_dest ) ) {
			return str_replace( static::$source['url'], $webp, $img_html );
		}

		return $img_html;
	}

	/**
	 * Remove WebP images.
	 *
	 * This method removes dynamically generated
	 * WebP image versions when the main image is deleted.
	 *
	 * @since 1.0.0
	 *
	 * @param int $attachment_id Attachment ID.
	 * @return void
	 */
	public function delete_webp_images( $attachment_id ): void {
		if ( ! wp_attachment_is_image( $attachment_id ) ) {
			return;
		}

		// Get absolute path for main image.
		$main_image = (string) get_attached_file( $attachment_id );

		// Ensure image exists before proceeding.
		if ( $main_image ) {
			$extension  = '.' . pathinfo( $main_image, PATHINFO_EXTENSION );
			$webp_image = str_replace( $extension, '.webp', $main_image );

			if ( file_exists( $webp_image ) ) {
				unlink( $webp_image );

				/**
				 * Fires after WebP Image has been deleted.
				 *
				 * @since 1.0.2
				 *
				 * @param string $webp_image    Absolute path to WebP image.
				 * @param int    $attachment_id Image ID.
				 *
				 * @return void
				 */
				do_action( 'webp_img_delete', $webp_image, $attachment_id );
			}
		}

		// Get attachment metadata.
		$metadata = wp_get_attachment_metadata( $attachment_id );

		// Remove metadata using main image absolute path.
		foreach ( $metadata['sizes'] ?? [] as $img ) {
			// Get absolute path of metadata image.
			$img_url_prefix = substr( $main_image, 0, (int) strrpos( $main_image, '/' ) );
			$metadata_image = trailingslashit( $img_url_prefix ) . $img['file'];

			// Ensure image exists before proceeding.
			if ( $metadata_image ) {
				// Get WebP version of metadata image.
				$metadata_extension  = '.' . pathinfo( $metadata_image, PATHINFO_EXTENSION );
				$webp_metadata_image = str_replace( $metadata_extension, '.webp', $metadata_image );

				if ( file_exists( $webp_metadata_image ) ) {
					unlink( $webp_metadata_image );

					/**
					 * Fires after WebP Metadata Image has been deleted.
					 *
					 * @since 1.0.2
					 *
					 * @param string $webp_metadata_image Absolute path to WebP image.
					 * @param int    $attachment_id       Image ID.
					 *
					 * @return void
					 */
					do_action( 'webp_img_metadata_delete', $webp_metadata_image, $attachment_id );
				}
			}
		}
	}

	/**
	 * Menu Service.
	 *
	 * This controls the menu display for the plugin.
	 *
	 * @since 1.0.2
	 *
	 * @return void
	 */
	public function add_webp_image_menu(): void {
		add_submenu_page(
			'upload.php',
			'Image Converter for WebP',
			'Image Converter for WebP',
			'manage_options',
			'webp-image-converter',
			[ $this, 'webp_image_menu_page' ]
		);
	}

	/**
	 * Menu Callback.
	 *
	 * This controls the display of the menu page.
	 *
	 * @since 1.0.2
	 *
	 * @return void
	 */
	public function webp_image_menu_page(): void {
		$settings = (string) plugin_dir_path( __FILE__ ) . '/Views/settings.php';

		if ( file_exists( $settings ) ) {
			require_once $settings;
		}
	}

	/**
	 * Save Plugin settings.
	 *
	 * This method handles all save actions for the fields
	 * on the Plugin's settings page.
	 *
	 * @since 1.0.2
	 *
	 * @return void
	 */
	public function add_webp_settings(): void {
		if ( ! isset( $_POST['webp_save_settings'] ) || ! isset( $_POST['webp_settings_nonce'] ) ) {
			return;
		}

		if ( ! wp_verify_nonce( $_POST['webp_settings_nonce'], 'webp_settings_action' ) ) {
			return;
		}

		$fields = [ 'quality', 'converter' ];

		update_option(
			'webp_img_converter',
			array_combine(
				$fields,
				array_map(
					function ( $field ) {
						if ( wp_verify_nonce( $_POST['webp_settings_nonce'], 'webp_settings_action' ) ) {
							return sanitize_text_field( $_POST[ $field ] ?? '' );
						}
					},
					$fields
				)
			)
		);
	}

	/**
	 * Add WebP meta to Attachment.
	 *
	 * This is responsible for creating meta data or logging errors
	 * depending on the conversion result ($webp).
	 *
	 * @since 1.0.2
	 *
	 * @param string|\WP_Error $webp          WebP's relative path.
	 * @param int              $attachment_id Image ID.
	 *
	 * @return void
	 */
	public function add_webp_meta_to_attachment( $webp, $attachment_id ): void {
		if ( ! is_wp_error( $webp ) && ! get_post_meta( $attachment_id, 'webp_img', true ) ) {
			update_post_meta( $attachment_id, 'webp_img', $webp );
		}
	}

	/**
	 * Get all Images and associated WebPs.
	 *
	 * This function grabs all Image attachments and
	 * associated WebP versions, if any.
	 *
	 * @since 1.0.2
	 *
	 * @return mixed[]
	 */
	protected function get_webp_images(): array {
		$posts = get_posts(
			[
				'post_type'      => 'attachment',
				'posts_per_page' => -1,
				'orderby'        => 'title',
			]
		);

		if ( ! $posts ) {
			return [];
		}

		$images = array_map(
			function ( $post ) {
				if ( $post instanceof \WP_Post && wp_attachment_is_image( $post ) ) {
					return [
						'guid' => $post->guid,
						'webp' => get_post_meta( (int) $post->ID, 'webp_img', true ) ?? '',
					];
				}
			},
			$posts
		);

		return $images;
	}

	/**
	 * Add attachment fields for WebP image.
	 *
	 * As the name implies, this logic creates a WebP field label
	 * in the WP attachment modal so users can see the path of the image's
	 * generated WebP version.
	 *
	 * @since 1.0.2
	 *
	 * @param mixed[]  $fields Fields Array.
	 * @param \WP_Post $post   WP Post.
	 *
	 * @return mixed[]
	 */
	public function add_webp_attachment_fields( $fields, $post ): array {
		$webp_img = get_post_meta( $post->ID, 'webp_img', true ) ?? '';

		$fields['webp_img'] = [
			'label' => 'WebP Image',
			'input' => 'text',
			'value' => (string) ( is_array( $webp_img ) ? '' : $webp_img ),
			'helps' => 'WebP Image generated by Image Converter for WebP.',
		];

		return $fields;
	}
}
