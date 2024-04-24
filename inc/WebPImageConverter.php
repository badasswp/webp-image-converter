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
	 * Image source (relative path).
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	public string $rel_source = '';

	/**
	 * Image destination (relative path).
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	public string $rel_dest = '';

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
		$this->abs_source = str_replace( $img_uploads_dir['baseurl'] ?? '', $img_uploads_dir['basedir'] ?? '', Plugin::$source );
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
		$image_extension = '.' . pathinfo( $this->rel_source, PATHINFO_EXTENSION );

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
			'quality'     => 85,
			'max-quality' => 100,
			'converter'   => 'imagick',
		];

		/**
		 * Get Conversion options.
		 *
		 * @since 1.0.0
		 *
		 * @param mixed[] $options Conversion options.
		 * @return mixed[]
		 */
		return (array) apply_filters( 'webp-img-conv-options', $options );
	}
}
