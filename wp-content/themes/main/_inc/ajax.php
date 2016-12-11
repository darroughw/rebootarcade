<?php
/*

SAMPLE AJAX CALL

var data = { action: 'my_ajax',
            extra_param : extra_param,
            nonce: theme_vars.nonce
        };
var url = theme_vars.ajax_url;

$.getJSON(url, data, function(json){

});
 */

function my_ajax() {

    check_ajax_referer( 'main-ajax-nonce', 'nonce' );
    global $wpdb;

    // Do your stuff here

	wp_die();
}
add_action( 'wp_ajax_my_ajax', 'my_ajax' );
add_action( 'wp_ajax_nopriv_my_ajax', 'my_ajax' );

?>
