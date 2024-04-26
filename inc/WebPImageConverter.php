<?php
/**
 * Converter Class.
 *
 * This class is responsible for converting the
 * JPG/PNG images to WebP format.
 *
 * @package WebPImageConverter
 */

namespace WebPImageConverter;

use Exception;
use WebPConvert\WebPConvert;

class WebPImageConverter {
	/**
	 * Image source (absolute path).
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	public string $abs_source = '';

	/**
	 * Image destination (absolute path).
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	public string $abs_dest = '';

	/**
	 * Image destination (relative path).
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	public string $rel_dest = '';

	/**
	 * Convert to WebP image.
	 *
	 * This method is responsible for taking the source image
	 * and converting it to the WebP equivalent.
	 *
	 * @since 1.0.0
	 *
	 * @return string|\WP_Error
	 */
	public function convert() {
		// Set image source and destination.
		$this->set_image_source();
		$this->set_image_destination();

		// Bail out, if source image is empty.
		if ( ! file_exists( $this->abs_source ) ) {
			return new \WP_Error(
				'webp-img-error',
				sprintf(
					/* translators: Absolute path to Source Image. */
					__( 'Error: %s does not exist.', 'webp-img-converter' ),
					$this->abs_source
				)
			);
		}

		// Bail out, if dest. image exists.
		if ( file_exists( $this->abs_dest ) ) {
			return $this->rel_dest;
		}

		// Convert to WebP image.
		try {
			WebPConvert::convert( $this->abs_source, $this->abs_dest, $this->get_options() );
		} catch ( Exception $e ) {
			$error_msg = sprintf(
				/* translators: Exception error msg. */
				__( 'Fatal Error: %s', 'webp-img-converter' ),
				$e->getMessage()
			);
			error_log( $error_msg );

			return new \WP_Error( 'webp-img-error', $error_msg );
		}

		return $this->rel_dest;
	}

	/**
	 * Set Image source.
	 *
	 * Get the image's relative path and replace with
	 * absolute path.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	protected function set_image_source(): void {
		$img_uploads_dir  = wp_upload_dir();
		$this->abs_source = str_replace( $img_uploads_dir['baseurl'], $img_uploads_dir['basedir'], Plugin::$source );
	}

	/**
	 * Set Image destination.
	 *
	 * Using image sources, set absolute and relative
	 * paths for images.
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	protected function set_image_destination(): void {
		$image_extension = '.' . pathinfo( Plugin::$source, PATHINFO_EXTENSION );

		$this->abs_dest = str_replace( $image_extension, '.webp', $this->abs_source );
		$this->rel_dest = str_replace( $image_extension, '.webp', Plugin::$source );
	}

	/**
	 * Get Options.
	 *
	 * A list of Conversion options to be used
	 * when converting images to WebP format. E.g. quality...
	 *
	 * @since 1.0.0
	 *
	 * @return mixed[]
	 */
	protected function get_options(): array {
		$options = [
			'quality'     => 20,
			'max-quality' => 100,
			'converter'   => 'gd',
		];

		/**
		 * Get Conversion options.
		 *
		 * @since 1.0.0
		 *
		 * @param mixed[] $options Conversion options.
		 * @return mixed[]
		 */
		return (array) apply_filters( 'webp_img_conv_options', $options );
	}
}
