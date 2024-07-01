<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit63b70c2bf9f4141c4fcc4efda921beb5
{
    public static $prefixLengthsPsr4 = array (
        'W' => 
        array (
            'WebPImageConverter\\Tests\\' => 25,
            'WebPImageConverter\\' => 19,
            'WebPConvert\\' => 12,
        ),
        'L' => 
        array (
            'LocateBinaries\\' => 15,
        ),
        'I' => 
        array (
            'ImageMimeTypeSniffer\\' => 21,
            'ImageMimeTypeGuesser\\' => 21,
        ),
        'F' => 
        array (
            'FileUtil\\' => 9,
        ),
        'E' => 
        array (
            'ExecWithFallback\\' => 17,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'WebPImageConverter\\Tests\\' => 
        array (
            0 => __DIR__ . '/../..' . '/tests',
        ),
        'WebPImageConverter\\' => 
        array (
            0 => __DIR__ . '/../..' . '/inc',
        ),
        'WebPConvert\\' => 
        array (
            0 => __DIR__ . '/..' . '/rosell-dk/webp-convert/src',
        ),
        'LocateBinaries\\' => 
        array (
            0 => __DIR__ . '/..' . '/rosell-dk/locate-binaries/src',
        ),
        'ImageMimeTypeSniffer\\' => 
        array (
            0 => __DIR__ . '/..' . '/rosell-dk/image-mime-type-sniffer/src',
        ),
        'ImageMimeTypeGuesser\\' => 
        array (
            0 => __DIR__ . '/..' . '/rosell-dk/image-mime-type-guesser/src',
        ),
        'FileUtil\\' => 
        array (
            0 => __DIR__ . '/..' . '/rosell-dk/file-util/src',
        ),
        'ExecWithFallback\\' => 
        array (
            0 => __DIR__ . '/..' . '/rosell-dk/exec-with-fallback/src',
        ),
    );

    public static $classMap = array (
        'Composer\\InstalledVersions' => __DIR__ . '/..' . '/composer/InstalledVersions.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInit63b70c2bf9f4141c4fcc4efda921beb5::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInit63b70c2bf9f4141c4fcc4efda921beb5::$prefixDirsPsr4;
            $loader->classMap = ComposerStaticInit63b70c2bf9f4141c4fcc4efda921beb5::$classMap;

        }, null, ClassLoader::class);
    }
}