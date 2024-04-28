# webp-image-converter

Convert your WordPress JPG/PNG images to WebP formats during runtime.

![screenshot](https://github.com/badasswp/webp-image-converter/assets/149586343/9c4a9cb2-63a0-462c-9ba1-a7adf23e51ea)

## Why WebP Image Converter?

As an internet user, you already know images can be the difference between a great website experience and a terrible one! Think about how often you've landed on a website and hit the back button because the home page was too busy or the banner image was taking so much time to load due to its size.

You may not realize it, but __imagery is a large part of it__. This plugin helps take care of all those concerns, by converting your WordPress images to WebP format during page load so that your site loads extremely fast, without any disruptions or downtime.

### Hooks

#### `webp_img_options`

This custom hook (filter) provides the ability to add custom options for your image conversions to WebP. For e.g. to perform a 50% quality, image conversion using the Imagick extension, you could do:

```php
add_filter( 'webp_img_options', [ $this, 'custom_options' ] );

public function custom_options( $options ): array {
    $options = wp_parse_args(
        [
            'quality'   => 50,
            'converter' => 'imagick',
        ],
        $options
    );

    return (array) $options;
}
```

**Properties**

- options _`{array}`_ By default this will be an associative array containing key, value options of each image conversion.
<br/>

#### `webp_img_after`

This custom hook (action) fires immediately after the image is converted to WebP. For e.g. you can capture errors to a custom post type of yours like so:

```php
add_action( 'webp_image_after', [ $this, 'log_webp_errors' ], 10, 2 );

public function log_webp_errors( $webp, $attachment_id ): void {
    if ( is_wp_error( $webp ) ) {
        wp_insert_post(
            [
                'post_type'   => 'webp_error',
                'post_title'  => (string) $webp->get_error_message(),
                'post_status' => 'publish',
            ]
        )
    }
}
```

**Properties**

- webp _`{string|WP_Error}`_ By default this will be the WebP return value after an image conversion is done. If successful, a string is returned, otherwise a WP_Error instance is.
- attachment_id _`{int}`_ By default this is the Image ID.
<br/>
