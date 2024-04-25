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
}
