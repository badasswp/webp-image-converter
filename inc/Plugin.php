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

final class Plugin {
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
	private WebPImageConverter $converter;

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
			static::$instance = new static();
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
		add_filter( 'post_thumbnail_html', [ $this, 'filter_post_thumbnail_html' ], 10, 5 );
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
		static::$source = wp_get_attachment_url( $attachment_id );

		// Bail out, if attachment is not an image.
		$filetype = wp_check_filetype( get_attached_file( $attachment_id ) );
		if ( false === strpos( $filetype['type'], 'image/' ) ) {
			return;
		}

		// Convert to WebP image.
		$webp = $this->converter->convert();
	}

	/**
	 * Generate WebP on post_thumbnail_html.
	 *
	 * Filter WP post thumbnail by grabbing the DOM and
	 * replacing with generated WebP images.
	 *
	 * @since 1.0.0
	 *
	 * @param string       $html         The post thumbnail HTML.
	 * @param int          $post_id      The post ID.
	 * @param int          $thumbnail_id The post thumbnail ID, or 0 if there isn't one.
	 * @param string|int[] $size         Requested image size.
	 * @param string|array $attr         Query string or array of attributes.
	 *
	 * @return string
	 */
	public function filter_post_thumbnail_html( $html, $post_id, $thumbnail_id, $size, $attr ): string {
		// Bail out, if empty or NOT image.
		if ( empty( $html ) || ! preg_match( '/<img.*>/', $html, $image ) ) {
			return $html;
		}

		// Get all image URLs.
		preg_match_all( '/http\S+\b/', $image[0], $image_urls );

		// Deal with all image src and srcset URLs.
		foreach( $image_urls[0] as $image_url ) {
			// Get source image.
			static::$source = $image_url;

			// Convert to WebP image.
			$webp = $this->converter->convert();

			// Replace image with WebP.
			if ( ! is_wp_error( $webp ) && file_exists( $this->converter->abs_dest ) ) {
				$html = str_replace( static::$source, $webp, $html );
			}
		}

		return $html;
	}
}
