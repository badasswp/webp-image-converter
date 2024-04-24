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
}
