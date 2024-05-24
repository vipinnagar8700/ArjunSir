# Deprecating legacy class names

Please read [this article](https://dev.to/greg0ire/how-to-deprecate-a-type-in-php-48cf) to understand what is going on here.

Applied approach:

1. Add a namespace to a class and move it to a correct position inside the `application` directory.
2. Append a `class_alias()` to the bottom of the renamed class that references the old name:
    ```php
    /** @noinspection PhpIgnoredClassAliasDeclaration */
    class_alias( \OTGS\Toolset\Types\Filesystem\Directory::class, 'Toolset_Filesystem_Directory' );
    ```
   - This will make sure the old name is available whenever the class is loaded.
3. Create a new file for the old class in `application/legacy_aliases` and add a construct like this:
    ```php
    use OTGS\Toolset\Types\Filesystem\Directory;
   
    if ( false ) {
    	/** @deprecated  */
    	class Toolset_Filesystem_Directory extends Directory { }
    }    
    class_exists( Directory::class );
    ```
    - The class definition will make sure it is picked up by the script for generating classmaps,
    but it doesn't actually get defined here - it will only cause this file to be loaded.
    - `class_exists()` to the new call will make sure the new implementation is actually loaded 
    (and the `class_alias()` from the previous step is executed), when the legacy class is used in the code.
