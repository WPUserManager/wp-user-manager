# Widgets Helper Class
[![License](https://img.shields.io/badge/License-GPL%20v3-blue.svg)](http://www.gnu.org/licenses/gpl-3.0)
![Downloads](https://img.shields.io/packagist/dt/wpbp/widgets-helper.svg) 

A class that extends the built-in WP_Widget class to provide an easier/faster way to create Widgets for WordPress.   
This is a fork of the original version with updates from the pull request on the official projects and few little improvements.

## Features

Automatic fields creation  
Validation methods  
Filter methods  
Before/After form output methods  
Custom form fields creation   

## Install

`composer require wpbp/widgets-helper:dev-master`

[composer-php52](https://github.com/composer-php52/composer-php52) supported.

## Example

```php
class MV_My_Recent_Posts_Widget extends WPH_Widget {
	function __construct() {
		    // Widget Backend information
			$args = array(
				'label' => __( 'My Recent Posts', 'mv-my-recente-posts' ),
				'description' => __( 'My Recent Posts Widget Description', 'mv-my-recente-posts' ),
				'options' => array( 'cache' => true )
			);

			$args['fields'] = array(

				// Title field
				array(
				// field name/label
				'name' => __( 'Title', 'mv-my-recente-posts' ),
				// field description
				'desc' => __( 'Enter the widget title.', 'mv-my-recente-posts' ),
				// field id
				'id' => 'title',
				// field type ( text, checkbox, textarea, select, select-group )
				'type'=>'text',
				// class, rows, cols
				'class' => 'widefat',
				// default value
				'std' => __( 'Recent Posts', 'mv-my-recente-posts' ),
				/* Set the field validation type
					'alpha_dash' Returns FALSE if the value contains anything other than alpha-numeric characters, underscores or dashes
                    'alpha'	Returns FALSE if the value contains anything other than alphabetical characters
                    'alpha_numeric'	Returns FALSE if the value contains anything other than alpha-numeric characters
                    'numeric' Returns FALSE if the value contains anything other than numeric characters
                    'boolean' Returns FALSE if the value contains anything other than a boolean value ( true or false )

				   You can define custom validation methods. Make sure to return a boolean ( TRUE/FALSE )
					'validate' => 'my_custom_validation',
				   Will call for: $this->my_custom_validation( $value_to_validate );
				*/
				'validate' => 'alpha_dash',
				/* Filter data before entering the DB
					 strip_tags ( default )
					 wp_strip_all_tags
					 esc_attr
					 esc_url
					 esc_textarea
				*/
				'filter' => 'strip_tags|esc_attr'
				 ),
				// Amount Field
				array(
				'name' => __( 'Amount' ),
				'desc' => __( 'Select how many posts to show.', 'mv-my-recente-posts' ),
				'id' => 'amount',
				'type'=>'select',
				// selectbox fields
				'fields' => array(
						array(
							// option name
							'name'  => __( '1 Post', 'mv-my-recente-posts' ),
							// option value	
							'value' => '1'
						),
						array(
							'name'  => __( '2 Posts', 'mv-my-recente-posts' ),
							'value' => '2'
						),
						array(
							'name'  => __( '3 Posts', 'mv-my-recente-posts' ),
							'value' => '3'
						)
				 ),
				'validate' => 'my_custom_validation',
				'filter' => 'strip_tags|esc_attr',
				 ),
				// Output type checkbox
				array(
				'name' => __( 'Output as list', 'mv-my-recente-posts' ),
				'desc' => __( 'Wraps posts with the <li> tag.', 'mv-my-recente-posts' ),
				'id' => 'list',
				'type'=>'checkbox',
				// checked by default:
				'std' => 1, // 0 or 1
				'filter' => 'strip_tags|esc_attr',
				 ),
                // Taxonomy Field
    		    array(							
    			'name' => __( 'Taxonomy', 'mv-my-recente-posts' ),
    			'desc' => __( 'Set the taxonomy.', 'mv-my-recente-posts' ),
    			'id' => 'taxonomy',
    			'type' => 'taxonomy',
    			'class' => 'widefat',
    		    ),
    		    // Taxonomy Field
    		    array(
    			'name' => __( 'Taxonomy terms', $this->plugin_slug ),
    			'desc' => __( 'Set the taxonomy terms.', $this->plugin_slug ),
    			'id' => 'taxonomyterm',
    			'type' => 'taxonomyterm',
    			'taxonomy' => 'category',
    			'class' => 'widefat',
    		    ),
    		    // Pages Field
    		    array(
    			'name' => __( 'Pages', $this->plugin_slug ),
    			'desc' => __( 'Set the page.', $this->plugin_slug ),
    			'id' => 'pages',
    			'type' => 'pages',
    			'class' => 'widefat',
    		    ),
    		    // Post type Field
    		    array(
    			'name' => __( 'Post type', $this->plugin_slug ),
    			'desc' => __( 'Set the post type.', $this->plugin_slug ),
    			'id' => 'posttype',
    			'type' => 'posttype',
    			'posttype' => 'post',
    			'class' => 'widefat',
    		    ),
			 );

			$this->create_widget( $args );
		}

		/**
        * Custom validation for this widget 
        * 
        * @param string $value
        * @return boolean 
        */
		function my_custom_validation( $value )	{
			if ( strlen( $value ) > 1 )
				return false;
			else
				return true;
		}

		/**
         * Output function
         * 
         * @param array $args
         * @param array $instance
         */
		function widget( $args, $instance ) {
		    $out = $args[ 'before_widget' ];
			// And here do whatever you want
			$out  = $args['before_title'];
			$out .= $instance['title'];
			$out .= $args['after_title'];
			// here you would get the most recent posts based on the selected amount: $instance['amount']
			// Then return those posts on the $out variable ready for the output
			$out .= '<p>Hey There! </p>';
            $out .= $args[ 'after_widget' ];
			echo $out;
		}

	}

	// Register widget
	if ( ! function_exists( 'mv_my_register_widget' ) )	{
		function mv_my_register_widget() {
			register_widget( 'MV_My_Recent_Posts_Widget' );
		}
		add_action( 'widgets_init', 'mv_my_register_widget', 1 );
	}
```

## Credits

by @sksmatt  
www.mattvarone.com

Contributors:

Joachim Kudish ( @jkudish )  
Joaquin http://bit.ly/p18bOk  
markyoungdev http://bit.ly/GK6PwU  
riesurya https://github.com/sksmatt/WordPress-Widgets-Helper-Class/pull/7  
ghost https://github.com/sksmatt/WordPress-Widgets-Helper-Class/pull/5  
Mte90