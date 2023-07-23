<?php

/**
 * Xperts University functions and definitions
 *
 * @link https://developer.wordpress.org/themes/basics/theme-functions/
 *
 * @package Flaggy
 */

 function flaggy_scripts(){

    // Main stylesheet
    wp_enqueue_style( 'flaggy-main-style', get_theme_file_uri('/css/main.css'));
	//wp_enqueue_style( 'flaggy-extra-style', get_theme_file_uri('/build/index.css'));

}

add_action( 'wp_enqueue_scripts', 'flaggy_scripts' );

function flaggy_config(){

	// This theme uses wp_nav_menu() in two locations.
	register_nav_menus(
		array(
			'flaggy_main_menu' 	=> esc_html__('Primary Menu', 'flaggy'),
		)
	);
}

add_action( 'after_setup_theme', 'flaggy_config', 0 );

function flaggy_widgets_init() {
	register_sidebar(
		array(
			'name'          => esc_html__( 'Footer Widget 1', 'flaggy' ),
			'id'            => 'footer-1',
			'description'   => esc_html__( 'Add widgets here.', 'flaggy' ),
			'before_widget' => '<section id="%1$s" class="widget %2$s">',
			'after_widget'  => '</section>',
			'before_title'  => '<h2 class="widget-title">',
			'after_title'   => '</h2>',
		)
	);
	register_sidebar(
		array(
			'name'          => esc_html__( 'Footer Widget 2', 'flaggy' ),
			'id'            => 'footer-2',
			'description'   => esc_html__( 'Add widgets here.', 'flaggy' ),
			'before_widget' => '<section id="%1$s" class="widget %2$s">',
			'after_widget'  => '</section>',
			'before_title'  => '<h2 class="widget-title">',
			'after_title'   => '</h2>',
		)
	);
	
}
add_action( 'widgets_init', 'flaggy_widgets_init' );

add_action( 'acf/include_fields', function() {
	if ( ! function_exists( 'acf_add_local_field_group' ) ) {
		return;
	}

	acf_add_local_field_group( array(
	'key' => 'group_64bbbb01554e0',
	'title' => 'Content Section',
	'fields' => array(
		array(
			'key' => 'field_64bbbb02ad233',
			'label' => 'Heading',
			'name' => 'heading',
			'aria-label' => '',
			'type' => 'text',
			'instructions' => '',
			'required' => 0,
			'conditional_logic' => 0,
			'wrapper' => array(
				'width' => '',
				'class' => '',
				'id' => '',
			),
			'default_value' => '',
			'maxlength' => '',
			'placeholder' => '',
			'prepend' => '',
			'append' => '',
		),
		array(
			'key' => 'field_64bbbbd4ad235',
			'label' => 'Content',
			'name' => 'content',
			'aria-label' => '',
			'type' => 'wysiwyg',
			'instructions' => '',
			'required' => 0,
			'conditional_logic' => 0,
			'wrapper' => array(
				'width' => '',
				'class' => '',
				'id' => '',
			),
			'default_value' => '',
			'tabs' => 'all',
			'toolbar' => 'full',
			'media_upload' => 1,
			'delay' => 0,
		),
	),
	'location' => array(
		array(
			array(
				'param' => 'post_type',
				'operator' => '==',
				'value' => 'post',
			),
		),
		array(
			array(
				'param' => 'post_type',
				'operator' => '==',
				'value' => 'page',
			),
		),
	),
	'menu_order' => 0,
	'position' => 'normal',
	'style' => 'default',
	'label_placement' => 'top',
	'instruction_placement' => 'label',
	'hide_on_screen' => '',
	'active' => true,
	'description' => '',
	'show_in_rest' => 0,
) );
} );

if( function_exists('acf_add_options_page') ) {
    
    acf_add_options_page(array(
        'page_title'    => 'Theme Global Settings',
        'menu_title'    => 'Theme Settings',
        'menu_slug'     => 'theme-global-settings',
        'capability'    => 'edit_posts',
        'redirect'      => false
    ));
}

add_action( 'acf/include_fields', function() {
	if ( ! function_exists( 'acf_add_local_field_group' ) ) {
		return;
	}

	acf_add_local_field_group( array(
	'key' => 'group_64bbcbca91bf8',
	'title' => 'Global Settings',
	'fields' => array(
		array(
			'key' => 'field_64bbcbcb0ff3f',
			'label' => 'Logo',
			'name' => 'logo',
			'aria-label' => '',
			'type' => 'image',
			'instructions' => '',
			'required' => 0,
			'conditional_logic' => 0,
			'wrapper' => array(
				'width' => '',
				'class' => '',
				'id' => '',
			),
			'return_format' => 'url',
			'library' => 'all',
			'min_width' => '',
			'min_height' => '',
			'min_size' => '',
			'max_width' => '',
			'max_height' => '',
			'max_size' => '',
			'mime_types' => '',
			'preview_size' => 'medium',
		),
		array(
			'key' => 'field_64bbced50ee02',
			'label' => 'Address',
			'name' => 'address',
			'aria-label' => '',
			'type' => 'textarea',
			'instructions' => '',
			'required' => 0,
			'conditional_logic' => 0,
			'wrapper' => array(
				'width' => '',
				'class' => '',
				'id' => '',
			),
			'default_value' => '',
			'maxlength' => '',
			'rows' => '',
			'placeholder' => '',
			'new_lines' => '',
		),
		array(
			'key' => 'field_64bbcee50ee03',
			'label' => 'Number',
			'name' => 'number',
			'aria-label' => '',
			'type' => 'text',
			'instructions' => '',
			'required' => 0,
			'conditional_logic' => 0,
			'wrapper' => array(
				'width' => '',
				'class' => '',
				'id' => '',
			),
			'default_value' => '',
			'maxlength' => '',
			'placeholder' => '',
			'prepend' => '',
			'append' => '',
		),
	),
	'location' => array(
		array(
			array(
				'param' => 'options_page',
				'operator' => '==',
				'value' => 'theme-global-settings',
			),
		),
	),
	'menu_order' => 0,
	'position' => 'normal',
	'style' => 'default',
	'label_placement' => 'top',
	'instruction_placement' => 'label',
	'hide_on_screen' => '',
	'active' => true,
	'description' => '',
	'show_in_rest' => 0,
) );
} );


function countries_data(){
	
	$url = 'https://restcountries.com/v3.1/all?fields=name,flags';
	$args = array(
	   'headers' => array(
	      'Content-Type' => 'application/json',
	   ),
	   );
	
	$response = wp_remote_get( $url, $args );
	
	$response_code = wp_remote_retrieve_response_code( $response );
	$results = json_decode( wp_remote_retrieve_body( $response ) );
	
	if($response_code !== 200){
		return 'something went wrong';
	}
	
	if($response_code == 200){

		foreach( $results as $result){
			$html .= '<div class="cards">'.PHP_EOL.
			        '<div class="card">'.PHP_EOL.
			         '<img src= "' . $result->flags->svg . '"/>'.PHP_EOL.
			         '<p>' . $result->name->official . '</p>'.PHP_EOL.
					 '</div>'.PHP_EOL.
					 '</div>';
		}
		return $html;
	}
}

add_shortcode( 'countries', 'countries_data' );

