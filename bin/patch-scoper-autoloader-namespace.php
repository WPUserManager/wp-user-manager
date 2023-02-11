<?php
/**
 * Make sure the Composer\Autoload namespace is prefixed.
 * Needs to be run after php-scoper.
 *
 * `php ./patch-scoper-autoloader-namespace.php MY_PREFIX`
 */

if ( empty( $argv[1] ) ) {
	return;
}

$prefix      = $argv[1];
$scoper_path = './release/scoper/build/vendor/composer';

prefix_namespace_in_autoloader_file( $scoper_path . '/autoload_static.php', $prefix );
prefix_namespace_in_autoloader_file( $scoper_path . '/autoload_real.php', $prefix );
prefix_namespace_in_autoloader_file( $scoper_path . '/ClassLoader.php', $prefix );
prefix_namespace_in_classmap_file( $scoper_path . '/autoload_classmap.php', $prefix );
prefix_namespace_in_classmap_file( $scoper_path . '/autoload_static.php', $prefix );

function prefix_namespace_in_autoloader_file( $file, $prefix ) {
	$path     = $file;
	$contents = file_get_contents( $path );
	$contents = str_replace( 'Composer\\\\Autoload', $prefix . '\\\\Composer\\\\Autoload', $contents );
	file_put_contents( $path, $contents );
}

function prefix_namespace_in_classmap_file( $file, $prefix ) {
	$path     = $file;
	$contents = file_get_contents( $path );
	$contents = str_replace( "'Gamajo_Template_Loader", "'" . $prefix . "\\\\Gamajo_Template_Loader", $contents );
	$contents = str_replace( "'WP_Requirements_Check", "'" . $prefix . "\\\\WP_Requirements_Check", $contents );
	$contents = str_replace( "'NumberFormatter", "'" . $prefix . "\\\\NumberFormatter", $contents );
	file_put_contents( $path, $contents );
}
