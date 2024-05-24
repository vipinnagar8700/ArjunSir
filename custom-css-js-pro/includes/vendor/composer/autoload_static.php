<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInitb6d802c6a5c6246ff9098f8e742b4370
{
    public static $prefixLengthsPsr4 = array (
        'S' => 
        array (
            'ScssPhp\\ScssPhp\\' => 16,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'ScssPhp\\ScssPhp\\' => 
        array (
            0 => __DIR__ . '/..' . '/scssphp/scssphp/src',
        ),
    );

    public static $prefixesPsr0 = array (
        'L' => 
        array (
            'Less' => 
            array (
                0 => __DIR__ . '/..' . '/wikimedia/less.php/lib',
            ),
        ),
    );

    public static $classMap = array (
        'Composer\\InstalledVersions' => __DIR__ . '/..' . '/composer/InstalledVersions.php',
        'lessc' => __DIR__ . '/..' . '/wikimedia/less.php/lessc.inc.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInitb6d802c6a5c6246ff9098f8e742b4370::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInitb6d802c6a5c6246ff9098f8e742b4370::$prefixDirsPsr4;
            $loader->prefixesPsr0 = ComposerStaticInitb6d802c6a5c6246ff9098f8e742b4370::$prefixesPsr0;
            $loader->classMap = ComposerStaticInitb6d802c6a5c6246ff9098f8e742b4370::$classMap;

        }, null, ClassLoader::class);
    }
}
