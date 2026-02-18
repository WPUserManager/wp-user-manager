<?php
/**
 * This helper is needed to "trick" composer autoloader to load the prefixed files
 * Otherwise if owncloud/core contains the same libraries ( i.e. guzzle ) it won't
 * load the files, as the file hash is the same and thus composer would think this was already loaded
 *
 * More information also found here: https://github.com/humbug/php-scoper/issues/298
 */


parse_str( $argv[1], $args );

$scoper_path = './release/' . $args['version'] . '/scoped/vendor/composer';

$static_loader_path = $scoper_path . '/autoload_static.php';
$static_loader      = file_get_contents( $static_loader_path );
$static_loader      = \preg_replace( '/\'([A-Za-z0-9]*?)\' => __DIR__ \. (.*?)/', '\'wpum$1\' => __DIR__ . $2', $static_loader );
file_put_contents( $static_loader_path, $static_loader );


$files_loader_path = $scoper_path . '/autoload_files.php';
$files_loader      = file_get_contents( $files_loader_path );
$files_loader      = \preg_replace( '/\'(.*?)\' => /', '\'wpum$1\' => ', $files_loader );
file_put_contents( $files_loader_path, $files_loader );
