<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit66f30362edd404bffa525b60dfe344dd
{
    public static $prefixLengthsPsr4 = array (
        'a' => 
        array (
            'apimatic\\jsonmapper\\' => 20,
        ),
        'U' => 
        array (
            'Unirest\\' => 8,
        ),
        'S' => 
        array (
            'Square\\' => 7,
        ),
        'C' => 
        array (
            'Core\\' => 5,
            'CoreInterfaces\\' => 15,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'apimatic\\jsonmapper\\' => 
        array (
            0 => __DIR__ . '/..' . '/apimatic/jsonmapper/src',
        ),
        'Unirest\\' => 
        array (
            0 => __DIR__ . '/..' . '/apimatic/unirest-php/src',
        ),
        'Square\\' => 
        array (
            0 => __DIR__ . '/..' . '/square/square/src',
        ),
        'Core\\' => 
        array (
            0 => __DIR__ . '/..' . '/apimatic/core/src',
        ),
        'CoreInterfaces\\' => 
        array (
            0 => __DIR__ . '/..' . '/apimatic/core-interfaces/src',
        ),
    );

    public static $prefixesPsr0 = array (
        'R' => 
        array (
            'Rs\\Json' => 
            array (
                0 => __DIR__ . '/..' . '/php-jsonpointer/php-jsonpointer/src',
            ),
        ),
    );

    public static $classMap = array (
        'Composer\\InstalledVersions' => __DIR__ . '/..' . '/composer/InstalledVersions.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInit66f30362edd404bffa525b60dfe344dd::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInit66f30362edd404bffa525b60dfe344dd::$prefixDirsPsr4;
            $loader->prefixesPsr0 = ComposerStaticInit66f30362edd404bffa525b60dfe344dd::$prefixesPsr0;
            $loader->classMap = ComposerStaticInit66f30362edd404bffa525b60dfe344dd::$classMap;

        }, null, ClassLoader::class);
    }
}