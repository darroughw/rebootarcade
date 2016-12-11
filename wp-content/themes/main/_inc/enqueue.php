<?php
/* YOUR SCRIPTS AND STYLES
*
* This theme uses CodeKit to compile scripts and styles.
*
*
*/

function theme_scripts() {

    // Load concatenated and minified stylesheet
    wp_enqueue_style( 'monoton-style', 'https://fonts.googleapis.com/css?family=Iceland' );
	wp_enqueue_style( 'main-style', get_template_directory_uri().'/style.css' );

	// Load all other concatenated and minified scripts in the footer
	wp_enqueue_script( 'jquery', '', array(), false, true );
	wp_enqueue_script( 'scripts', get_template_directory_uri().ASSETS.'/js/app.js', array(), false, true );

	// Javasctipt object to privide the theme scripts with needed data. Add items as needed to the $obj_array.
	$obj_array = array( 'theme_location' => get_template_directory_uri(), 'env' => ENV, 'assets' => ASSETS, 'ajax_url' => admin_url( 'admin-ajax.php' ) , 'nonce' => wp_create_nonce( "main-ajax-nonce" ) );
    wp_localize_script( 'scripts', 'theme_vars', $obj_array );

}
add_action( 'wp_enqueue_scripts', 'theme_scripts' );
?>
