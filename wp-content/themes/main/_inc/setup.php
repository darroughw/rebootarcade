<?php
$config_file = '{}';

if ( file_exists( get_template_directory().'/manifest.json') ) {
	$config_file = file_get_contents( get_template_directory().'/manifest.json');
} else {
    echo 'manifest.json file is missing in theme folder.';
    exit();
}

$config = json_decode($config_file, true);

// Where your assets folder be, yo.
$assets = ( strlen($config['assets'] ) )? '/'.$config['assets'] : '' ;
// The current theme location
$theme = ( strlen($config['theme'] ) )? $config['theme'] : '' ;

define('ASSETS', $assets);
define('ENV', $config['env'] );
?>
