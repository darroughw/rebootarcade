<?php
/*
*
* Theme helper functions
*
*
*/

function theme_wp_title( $title, $sep ) {
	if ( is_feed() ) {
		return $title;
	}

	global $page, $paged;

	// Add the blog name
	$title .= get_bloginfo( 'name', 'display' );

	// Add the blog description for the home/front page.
	$site_description = get_bloginfo( 'description', 'display' );
	if ( $site_description && ( is_home() || is_front_page() ) ) {
		$title .= " $sep $site_description";
	}

	// Add a page number if necessary:
	if ( $paged >= 2 || $page >= 2 ) {
		$title .= " $sep " . sprintf( __( 'Page %s' ), max( $paged, $page ) );
	}

	return $title;
}
add_filter( 'wp_title', 'theme_wp_title', 10, 2 );

function remove_admin_page() {
    remove_menu_page( 'edit-comments.php' );
    //remove_menu_page( 'tools.php' ); // Hides the tools page
    //remove_menu_page( 'index.php' ); // Hides the dashboard
}
add_action( 'admin_menu', 'remove_admin_page' );

// Adds ACF Pro options admin
if( function_exists('acf_add_options_page') ) {
	acf_add_options_page();
}

function remove_comment_support() {
    remove_post_type_support( 'post', 'comments' );
    remove_post_type_support( 'page', 'comments' );
}
// Removes from post and pages
add_action('init', 'remove_comment_support', 100);

// Removes from admin bar
function mytheme_admin_bar_render() {
    global $wp_admin_bar;
    $wp_admin_bar->remove_menu('comments');
    $wp_admin_bar->remove_menu('wp-logo');
}
add_action( 'wp_before_admin_bar_render', 'mytheme_admin_bar_render' );

// Adds featured image block
add_theme_support( 'post-thumbnails' );

// Formats the page content
if ( ! function_exists( 'page_content' ) ) :
function page_content($content){
    return apply_filters('the_content', $content);
}
endif;

if ( ! function_exists( 'theme_paging_nav' ) ) :
function theme_paging_nav() {

	if ( $GLOBALS['wp_query']->max_num_pages < 2 ) {
		return;
	}
	?>
	<nav class="navigation paging-navigation" role="navigation">
		<h1 class="screen-reader-text"><?php _e( 'Posts navigation' ); ?></h1>
		<div class="nav-links">

			<?php if ( get_next_posts_link() ) : ?>
			<div class="nav-previous"><?php next_posts_link( __( '<span class="meta-nav">&larr;</span> Older posts' ) ); ?></div>
			<?php endif; ?>

			<?php if ( get_previous_posts_link() ) : ?>
			<div class="nav-next"><?php previous_posts_link( __( 'Newer posts <span class="meta-nav">&rarr;</span>' ) ); ?></div>
			<?php endif; ?>

		</div><!-- .nav-links -->
	</nav><!-- .navigation -->
	<?php
}
endif;


if ( ! function_exists( 'theme_post_nav' ) ) :
function theme_post_nav() {

	$previous = ( is_attachment() ) ? get_post( get_post()->post_parent ) : get_adjacent_post( false, '', true );
	$next     = get_adjacent_post( false, '', false );

	if ( ! $next && ! $previous ) {
		return;
	}
	?>
	<nav class="navigation post-navigation" role="navigation">
		<h1 class="screen-reader-text"><?php _e( 'Post navigation', 'kirkpatrickprice' ); ?></h1>
		<div class="nav-links">
			<?php
				previous_post_link( '<div class="nav-previous">%link</div>', _x( '<span class="meta-nav">&larr;</span> %title', 'Previous post link', 'main' ) );
				next_post_link(     '<div class="nav-next">%link</div>',     _x( '%title <span class="meta-nav">&rarr;</span>', 'Next post link',     'main' ) );
			?>
		</div><!-- .nav-links -->
	</nav><!-- .navigation -->
	<?php
}
endif;

?>
