# webp-image-converter

Convert your WordPress JPG/PNG images to WebP formats during runtime.

[![Coverage Status](https://coveralls.io/repos/github/badasswp/webp-image-converter/badge.svg)](https://coveralls.io/github/badasswp/webp-image-converter)

![screenshot](https://github.com/badasswp/webp-image-converter/assets/149586343/9c4a9cb2-63a0-462c-9ba1-a7adf23e51ea)

## Download

Get the latest version from any of our [release tags](https://github.com/badasswp/webp-image-converter/releases).

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

**Parameters**

- options _`{array}`_ By default this will be an associative array containing key, value options of each image conversion.
<br/>

#### `webp_img_convert`

This custom hook (action) fires immediately after the image is converted to WebP. For e.g. you can capture errors to a custom post type of yours like so:

```php
add_action( 'webp_img_convert', [ $this, 'log_webp_errors' ], 10, 2 );

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

**Parameters**

- webp _`{string|WP_Error}`_ By default this will be the WebP return value after an image conversion is done. If successful, a string is returned, otherwise a WP_Error instance is.
- attachment_id _`{int}`_ By default this is the Image ID.
<br/>

#### `webp_img_attachment_html`

This custom hook (filter) provides the ability to modify the resulting WebP image HTML. For e.g. you can nest your image HTML into a figure element like so:

```php
add_filter( 'webp_img_attachment_html', [ $this, 'custom_img_html' ], 10, 2 );

public function custom_img_html( $html, $attachment_id ): string {
    return sprintf(
        '<figure>
          %s
          <figcaption>Image ID: %s</figcaption>
        </figure>',
        (string) $html,
        (string) $attchment_id
    );
}
```

**Parameters**

- webp _`{string}`_ By default this will be the image HTML.
- attachment_id _`{int}`_ By default this is the Image ID.
<br/>

#### `webp_img_thumbnail_html`

This custom hook (filter) provides the ability to modify the resulting WebP image HTML. For e.g. you can nest your image HTML into a figure element like so:

```php
add_filter( 'webp_img_thumbnail_html', [ $this, 'custom_img_html' ], 10, 2 );

public function custom_img_html( $html, $thumbnail_id ): string {
    return sprintf(
        '<figure>
          %s
          <figcaption>Image ID: %s</figcaption>
        </figure>',
        (string) $html,
        (string) $thumbnail_id
    );
}
```

**Parameters**

- webp _`{string}`_ By default this will be the image HTML.
- thumbnail_id _`{int}`_ By default this is the Image ID.
<br/>

---

## Development

### Setup

- Clone the repository.
- Make sure you have [Composer](https://getcomposer.org) and PHP `v7.4|v8.0` installed in your computer.
- Run `composer install` to build PHP dependencies.
- For local development, you can use [Docker](https://docs.docker.com/install/) or [Local by Flywheel](https://localwp.com/).

### Linting

```bash
# Run PHP Linting.
composer run lint

# Fix PHP Linting errors.
composer run lint:fix
```

### Testing

```bash
composer run test
```

### Static Analysis

```bash
composer run analyse
```

---

## Contribution

First, thank you for taking the time to contribute!

Contributing isn't just writing code - it's anything that improves the project.  All contributions are managed right here on Github.  Here are some ways you can help:

### Bugs

If you're running into an issue, please take a look through [existing issues](https://github.com/badasswp/webp-image-converter/issues) and [open a new one](https://github.com/badasswp/webp-image-converter/issues/new) if needed. If you're able, include steps to reproduce, environment information, and screenshots/screencasts as relevant. To create a branch that fixes a bug, please use the convention `fix/{issue-number}-your-branch-name` like so:

```
git checkout -b fix/1234-image-mime-type-bug
```

### Features

New features and enhancements are also managed via [issues](https://github.com/badasswp/webp-image-converter/issues). To create a branch that adds a feature, please use the convention `feat/{issue-number}-your-branch-name` like so:

```
git checkout -b feat/1234-image-error-logging-capability
```

### Pull Requests (PR)

Pull requests represent a proposed solution to a specified problem. They should always reference an issue that describes the problem in detail. Discussion on pull requests __should be limited to the pull request__, i.e. code review.
