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
	 * Source Image.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	public static $source;

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
		add_action( 'add_attachment', [ $this, 'action_add_attachment' ] );
		add_filter( 'wp_get_attachment_image', [ $this, 'filter_wp_get_attachment_image' ], 10, 5 );
		add_filter( 'post_thumbnail_html', [ $this, 'filter_post_thumbnail_html' ], 10, 5 );
		add_filter( 'wp_generate_attachment_metadata', [ $this, 'generate_webp_srcset_images' ], 10, 3 );
		add_action( 'delete_attachment', [ $this, 'remove_webp_images' ] );
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
	public function action_add_attachment( $attachment_id ): void {
		// Get source image.
		static::$source = (string) wp_get_attachment_url( $attachment_id );

		// Convert to WebP image.
		$webp = $this->converter->convert();

		/**
		 * Fires after Image is converted.
		 *
		 * @since 1.0.0
		 *
		 * @param string|\WP_Error $webp          WebP Image URL or WP Error.
		 * @param int              $attachment_id Image ID.
		 *
		 * @return void
		 */
		do_action( 'webp_img_after', $webp, $attachment_id );
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
	 *                                    See wp_get_attachment_image().
	 *
	 * @return string
	 */
	public function filter_wp_get_attachment_image( $html, $attachment_id, $size, $icon, $attr ): string {
		if ( empty( $html ) ) {
			return $html;
		}

		$html = $this->get_webp_image_html( $html );

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

		$html = $this->get_webp_image_html( $html );

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
	 * of the plugin.
	 *
	 * @since 1.0.0
	 *
	 * @param string $html Image HTML.
	 * @return string
	 */
	protected function get_webp_image_html( $html ): string {
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

			$html = $this->_get_webp_html( $html, $html );

			// For the srcset images.
			$srcset = $image->getAttribute( 'srcset' );

			if ( empty( $srcset ) ) {
				return $html;
			}

			preg_match_all( '/http\S+\b/', $srcset, $image_urls );

			foreach ( $image_urls[0] as $img_url ) {
				$html = $this->_get_webp_html( $img_url, $html );
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
	 *
	 * @return string
	 */
	protected function _get_webp_html( $img_url, $img_html ): string {
		// Set Source.
		static::$source = $img_url;

		// Convert image to WebP.
		$webp = $this->converter->convert();

		// Replace image with WebP.
		if ( ! is_wp_error( $webp ) && file_exists( $this->converter->abs_dest ) ) {
			return str_replace( static::$source, $webp, $img_html );
		}

		return $img_html;
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
	 * @param string  $context       Additional context. Can be 'create' or 'update.
	 *
	 * @return mixed[]
	 */
	public function generate_webp_srcset_images( $metadata, $attachment_id, $context ) {
		// Get parent image URL.
		$img_url = (string) wp_get_attachment_image_url( $attachment_id );

		// Get image path prefix.
		$img_url_prefix = substr( $img_url, 0, (int) strrpos( $img_url, '/' ) );

		// Convert srcset images.
		foreach ( $metadata['sizes'] as $img ) {
			static::$source = trailingslashit( $img_url_prefix ) . $img['file'];
			$this->converter->convert();
		}

		return $metadata;
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
	public function remove_webp_images( $attachment_id ) {
		if ( ! wp_attachment_is_image( $attachment_id ) ) {
			return;
		}

		// Get absolute path for main image.
		$main_image = (string) get_attached_file( $attachment_id );

		// Get WebP version of main image.
		$extension  = '.' . pathinfo( $main_image, PATHINFO_EXTENSION );
		$webp_image = str_replace( $extension, '.webp', $main_image );

		unlink( $webp_image );

		// Get attachment metadata.
		$metadata = wp_get_attachment_metadata( $attachment_id );

		// Remove metadata using main image absolute path.
		foreach ( $metadata['sizes'] ?? [] as $img ) {
			// Get absolute path of metadata image.
			$img_url_prefix = substr( $main_image, 0, (int) strrpos( $main_image, '/' ) );
			$metadata_image = trailingslashit( $img_url_prefix ) . $img['file'];

			// Get WebP version of metadata image.
			$metadata_extension  = '.' . pathinfo( $metadata_image, PATHINFO_EXTENSION );
			$webp_metadata_image = str_replace( $metadata_extension, '.webp', $metadata_image );

			unlink( $webp_metadata_image );
		}
	}
}
