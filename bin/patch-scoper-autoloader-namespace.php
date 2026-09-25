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

parse_str( $argv[1], $args );

$scoper_path = './release/' . $args['version'] . '/scoped/vendor/composer';
$prefix      = $args['prefix'];

remove_prefix_namespace_in_autoloader_file( $scoper_path . '/autoload_static.php', $prefix );
remove_prefix_namespace_in_autoloader_file( $scoper_path . '/autoload_psr4.php', $prefix );
remove_prefix_namespace_in_autoloader_file( $scoper_path . '/autoload_classmap.php', $prefix );
update_prefixLengthsPsr4( $scoper_path . '/autoload_static.php', $prefix );
prefix_namespace_in_autoloader_file( $scoper_path . '/autoload_real.php', $prefix );
prefix_namespace_in_autoloader_file( $scoper_path . '/autoload_static.php', $prefix );
prefix_namespace_in_autoloader_file( $scoper_path . '/ClassLoader.php', $prefix );

function remove_prefix_namespace_in_autoloader_file( $file, $prefix ) {
	$path     = $file;
	$contents = file_get_contents( $path );
	$contents = str_replace( $prefix .'\\\\WPUserManager\\\\Stripe', 'WPUserManager\\\\Stripe', $contents );
	file_put_contents( $path, $contents );
}

function update_prefixLengthsPsr4( $file ) {
	$path     = $file;
	$contents = file_get_contents( $path );
	$contents = str_replace(  'WPUserManager\\\\Stripe\\\\\' => 26', 'WPUserManager\\\\Stripe\\\\\' => 21', $contents );
	file_put_contents( $path, $contents );
}
function prefix_namespace_in_autoloader_file( $file, $prefix ) {
	$path     = $file;
	$contents = file_get_contents( $path );
	$contents = str_replace( 'Composer\\Autoload', $prefix . '\\Composer\\Autoload', $contents );
	file_put_contents( $path, $contents );
}


