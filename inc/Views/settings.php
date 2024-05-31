<?php
/**
 * Settings Page.
 *
 * This template is responsible for the Settings
 * page in the plugin.
 *
 * @package WebPImageConverter
 * @since   1.0.2
 */

?>
<section class="wrap">
	<h1>WebP Image Converter</h1>
	<p>Manage your settings here.</p>

	<form method="POST" action="<?php echo esc_url( $_SERVER['REQUEST_URI'] ); ?>">
		<p>
			<label for="Quality">Quality (%)</label><br/>
			<input
				type="number"
				name="quality"
				min="0"
				max="100"
				placeholder="20"
				value="<?php esc_attr_e( get_option( 'webp_img_converter' )['quality'] ?? '' ); ?>"
			/>
		</p>

		<p>
			<label for="Engine">Engine</label><br/>
			<select name="engine">
			<?php
			$engines = [
				'gd'      => 'GD',
				'cwebp'   => 'CWebP',
				'ffmpeg'  => 'FFMpeg',
				'imagick' => 'Imagick',
				'gmagick' => 'Gmagick',
			];

			$engine = get_option( 'webp_img_converter' )['engine'] ?? '';

			foreach ( $engines as $key => $value ) {
				$selected = $engine === $key ? 'selected' : '';
				_e(
					sprintf(
						'<option value="%1$s" %3$s>%2$s</option>',
						esc_attr( $key ),
						esc_html( $value ),
						esc_html( $selected ),
					)
				);
			}
			?>
			</select>
		</p>

		<p>
			<button name="webp_save_settings" type="submit" class="button button-primary">
				<span>Save</span>
			</button>
		</p>

		<?php wp_nonce_field( 'webp_settings_action', 'webp_settings_nonce' ); ?>
	</form>
</section>