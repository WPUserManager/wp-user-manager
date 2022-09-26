<?php

declare(strict_types=1);

use Isolated\Symfony\Component\Finder\Finder;

// You can do your own things here, e.g. collecting symbols to expose dynamically
// or files to exclude.
// However beware that this file is executed by PHP-Scoper, hence if you are using
// the PHAR it will be loaded by the PHAR. So it is highly recommended to avoid
// to auto-load any code here: it can result in a conflict or even corrupt
// the PHP-Scoper analysis.

return [
    // The prefix configuration. If a non null value is be used, a random prefix
    // will be generated instead.
    //
    // For more see: https://github.com/humbug/php-scoper/blob/master/docs/configuration.md#prefix
    'prefix' => 'WPUM',

    // By default when running php-scoper add-prefix, it will prefix all relevant code found in the current working
    // directory. You can however define which files should be scoped by defining a collection of Finders in the
    // following configuration key.
    //
    // This configuration entry is completely ignored when using Box.
    //
    // For more see: https://github.com/humbug/php-scoper/blob/master/docs/configuration.md#finders-and-paths
    'finders' => [
        Finder::create()
            ->files()
            ->ignoreVCS(true)
            ->notName('/LICENSE|.*\\.md|.*\\.dist|Makefile|composer\\.json|composer\\.lock/')
            ->exclude([
                'doc',
                'test',
                'test_old',
                'tests',
                'Tests',
                'vendor-bin',
            ])
            ->in('vendor'),
        Finder::create()->append([
            'composer.json',
        ]),
    ],

    // List of excluded files, i.e. files for which the content will be left untouched.
    // Paths are relative to the configuration file unless if they are already absolute
    //
    // For more see: https://github.com/humbug/php-scoper/blob/master/docs/configuration.md#patchers
    'exclude-files' => [
		'vendor/htmlburger/carbon-fields/templates/Container/comment_meta.php',
		'vendor/htmlburger/carbon-fields/templates/Container/nav_menu_item.php',
	    'vendor/htmlburger/carbon-fields/templates/Container/network.php',
	    'vendor/htmlburger/carbon-fields/templates/Container/post_meta.php',
	    'vendor/htmlburger/carbon-fields/templates/Container/term_meta.php',
	    'vendor/htmlburger/carbon-fields/templates/Container/theme_options.php',
	    'vendor/htmlburger/carbon-fields/templates/Container/user_meta.php',
	    'vendor/htmlburger/carbon-fields/templates/Container/widget.php',
	    'vendor/htmlburger/carbon-fields/templates/Exception/incorrect-syntax.php',
    ],

    // When scoping PHP files, there will be scenarios where some of the code being scoped indirectly references the
    // original namespace. These will include, for example, strings or string manipulations. PHP-Scoper has limited
    // support for prefixing such strings. To circumvent that, you can define patchers to manipulate the file to your
    // heart contents.
    //
    // For more see: https://github.com/humbug/php-scoper/blob/master/docs/configuration.md#patchers
    'patchers' => [
	    function (string $filePath, string $prefix, string $contents): string {

		    $wordpress_functions = array(
			    'add_action',
			    'add_filter',
			    'apply_filters',
			    'do_action',
			    'update_site_option',
			    'delete_site_option',
			    'wp_die',
			    'check_ajax_referer',
			    'is_multisite',
			    'get_site_transient',
			    'set_site_transient',
			    'delete_site_transient',
			    'maybe_serialize',
			    'maybe_unserialize',
			    '__',
			    'wp_next_scheduled',
			    'wp_schedule_event',
			    'wp_unschedule_event',
			    'wp_clear_scheduled_hook',
			    'add_query_arg',
			    'wp_remote_post',
			    'esc_url_raw',
			    'wp_create_nonce',
			    'admin_url',
			    'check_ajax_referer',
			    'wp_convert_hr_to_bytes',
			    'trailingslashit',
				'untrailingslashit',
			    'plugins_url',
			    'content_url',
			    'site_url',
		    );

		    $wp_files = array(
			    'htmlburger/carbon-fields/core/Carbon_Fields.php'
		    );

		    foreach ( $wp_files as $wp_file ) {
			    if ( false !== strrpos( $filePath, $wp_file ) ) {
				    // Don't prefix WordPress functions
				    foreach ( $wordpress_functions as $wordpress_function ) {
					    $contents = str_replace( '\\' . $prefix . '\\' . $wordpress_function, $wordpress_function, $contents );
				    }

				    return $contents;
			    }
		    }

		    if ( false !== strrpos( $filePath, 'wpbp/widgets-helper/wph-widget.php' ) ) {
			    $contents = str_replace( $prefix . '\\\\WP_Widget', '\\WP_Widget', $contents );
			    $contents = str_replace( 'extends WP_Widget', 'extends \\WP_Widget', $contents );
		    }

		    if ( false !== strrpos( $filePath, 'htmlburger/carbon-fields/core/Field.php' ) ) {
			    $contents = str_replace( '\\\\Carbon_Fields\\\\Field', '\\\\' . $prefix . '\\\\Carbon_Fields\\\\Field', $contents );
		    }

		    if ( false !== strrpos( $filePath, 'htmlburger/carbon-fields/core/Container.php' ) ) {
			    $contents = str_replace( '\\\\Carbon_Fields\\\\Container', '\\\\' . $prefix . '\\\\Carbon_Fields\\\\Container', $contents );
		    }

		    if ( false !== strrpos( $filePath, 'wp-user-manager/wpum-blocks/blocks-loader.php' ) ) {
			    $contents = str_replace( 'WP_Block_Type_Registry', '\\WP_Block_Type_Registry', $contents );
		    }

		    if ( false !== strrpos( $filePath, 'wp-user-manager/wpum-blocks/blocks-loader.php' ) ) {
			    $contents = str_replace( 'new WPUserManagerBlocks\Loader()', 'new \\' . $prefix . '\\WPUserManagerBlocks\Loader()', $contents );
		    }

		    if ( false !== strrpos( $filePath, 'wp-user-manager/wpum-blocks/includes/classes/Loader.php' ) ) {
			    $contents = str_replace( $prefix . '\\\\register_block_type', '\\\\register_block_type', $contents );
			    $contents = str_replace( $prefix . '\\\\WPUM_Groups', '\\\\WPUM_Groups', $contents );
			    $contents = str_replace( $prefix . '\\\\WPUM_Frontend_Posting', '\\\\WPUM_Frontend_Posting', $contents );
		    }

			if ( false !== strrpos( $filePath, 'brain/cortex' ) ) {
			    $contents = str_replace( '\\' . $prefix . '\\WP', '\\WP', $contents );
		    }

		    if ( false !== strrpos( $filePath, 'wp-user-manager/wp-optionskit/includes/class-wpok-rest-server.php' ) ) {
			    $contents = str_replace( 'extends \\' . $prefix . '\\WP_Rest_Controller', 'extends \\WP_Rest_Controller', $contents );
			    $contents = str_replace( '\\' . $prefix . '\\WP_Error', '\\WP_Error', $contents );
			    $contents = str_replace( '\\' . $prefix . '\\WP_REST_Server', '\\WP_REST_Server', $contents );
		    }

		    return $contents;
	    },
    ],

    // List of symbols to consider internal i.e. to leave untouched.
    //
    // For more information see: https://github.com/humbug/php-scoper/blob/master/docs/configuration.md#excluded-symbols
    'exclude-namespaces' => [
        // 'Acme\Foo'                     // The Acme\Foo namespace (and sub-namespaces)
        // '~^PHPUnit\\\\Framework$~',    // The whole namespace PHPUnit\Framework (but not sub-namespaces)
        // '~^$~',                        // The root namespace only
        // '',                            // Any namespace
    ],
    'exclude-classes' => [
        // 'ReflectionClassConstant',
    ],
    'exclude-functions' => [
        // 'mb_str_split',
    ],
    'exclude-constants' => [
        // 'STDIN',
    ],
    // For more information see: https://github.com/humbug/php-scoper/blob/master/docs/configuration.md#exposed-symbols
    'expose-global-constants' => true,
    'expose-global-classes' => false,
    'expose-global-functions' => false,
    'expose-namespaces' => [
        // 'Acme\Foo'                     // The Acme\Foo namespace (and sub-namespaces)
        // '~^PHPUnit\\\\Framework$~',    // The whole namespace PHPUnit\Framework (but not sub-namespaces)
        // '~^$~',                        // The root namespace only
        // '',                            // Any namespace
    ],
    'expose-classes' => [],
    'expose-functions' => [],
    'expose-constants' => [],
];
