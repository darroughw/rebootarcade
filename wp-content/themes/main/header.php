<!DOCTYPE html>
<html class="no-js" <?php language_attributes(); ?>>
    <head>
        <meta charset="utf-8">
        <title><?php wp_title( '|', true, 'right' ); ?></title>
        <link rel="profile" href="http://gmpg.org/xfn/11">
        <link rel="pingback" href="<?php bloginfo( 'pingback_url' ); ?>">
        <meta name="author" content="">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <?php wp_head(); ?>

    </head>
    <body <?php body_class(); ?>>

           <!-- poster="reboot.jpg" -->
        <header class="header">
			<video playsinline autoplay muted loop poster="<?php echo get_template_directory_uri(); ?>/assets/images/reboot.png" id="bgvid">
                <source src="<?php echo get_template_directory_uri(); ?>/assets/video/reboot.mp4" type="video/mp4">
            </video>
		</header><!-- / .header -->