# WP Requirements Check

Simple drop-in class to check minimum PHP and WordPress version requirements in your plugin.

## Usage

1. Run `composer require wearerequired/wp-requirements-check`
2. In your main plugin file, instantiate the class using something like this:

```php
$requirements_check = new WP_Requirements_Check( array(
	'title' => 'My awesome plugin',
	'php'   => '7.0',
	'wp'    => '4.7',
	'file'  => __FILE__,
	'i18n'  => array(
		/* translators: 1: plugin name. 2: minimum PHP version. */
		'php' => __( '&#8220;%1$s&#8221; requires PHP %2$s or higher. Please upgrade.', 'my-plugin' ),
		/* translators: 1: plugin name. 2: minimum WordPress version. */
		'wp'  => __( '&#8220;%1$s&#8221; requires WordPress %2$s or higher. Please upgrade.', 'my-plugin' ),
	),
) );

if ( $requirements_check->passes() ) {
	// Proceed.
}
```

## Credits

Thanks to Mark Jaquith for his [grunt-wp-plugin](https://github.com/markjaquith/grunt-wp-plugin) template which contains similar code.
