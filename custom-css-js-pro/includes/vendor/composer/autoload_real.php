<?php

// autoload_real.php @generated by Composer

class ComposerAutoloaderInitb6d802c6a5c6246ff9098f8e742b4370
{
    private static $loader;

    public static function loadClassLoader($class)
    {
        if ('Composer\Autoload\ClassLoader' === $class) {
            require __DIR__ . '/ClassLoader.php';
        }
    }

    /**
     * @return \Composer\Autoload\ClassLoader
     */
    public static function getLoader()
    {
        if (null !== self::$loader) {
            return self::$loader;
        }

        require __DIR__ . '/platform_check.php';

        spl_autoload_register(array('ComposerAutoloaderInitb6d802c6a5c6246ff9098f8e742b4370', 'loadClassLoader'), true, true);
        self::$loader = $loader = new \Composer\Autoload\ClassLoader(\dirname(__DIR__));
        spl_autoload_unregister(array('ComposerAutoloaderInitb6d802c6a5c6246ff9098f8e742b4370', 'loadClassLoader'));

        require __DIR__ . '/autoload_static.php';
        call_user_func(\Composer\Autoload\ComposerStaticInitb6d802c6a5c6246ff9098f8e742b4370::getInitializer($loader));

        $loader->register(true);

        return $loader;
    }
}