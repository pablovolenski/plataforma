<?php
/**
 * Theme bootstrap: feature support, asset enqueueing, includes.
 */

defined( 'ABSPATH' ) || exit;

// ---------------------------------------------------------------------------
// Theme support
// ---------------------------------------------------------------------------

add_action( 'after_setup_theme', 'plataforma_theme_setup' );

function plataforma_theme_setup(): void {
	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'html5', [
		'search-form', 'comment-form', 'comment-list', 'gallery', 'caption',
	] );

	register_nav_menus( [ 'primary' => 'Menú principal' ] );
}

// ---------------------------------------------------------------------------
// Assets
// ---------------------------------------------------------------------------

add_action( 'wp_enqueue_scripts', 'plataforma_enqueue_assets' );

function plataforma_enqueue_assets(): void {
	$version = wp_get_theme()->get( 'Version' );
	$uri     = get_template_directory_uri();

	wp_enqueue_style(
		'plataforma-main',
		$uri . '/assets/css/main.css',
		[],
		$version
	);

	// main.js handles filters, compose form AJAX submit, char counters
	wp_enqueue_script(
		'plataforma-main',
		$uri . '/assets/js/main.js',
		[],
		$version,
		true   // in footer
	);

	// likes.js handles the like/unlike interaction
	wp_enqueue_script(
		'plataforma-likes',
		$uri . '/assets/js/likes.js',
		[],
		$version,
		true
	);
}

// ---------------------------------------------------------------------------
// Includes
// ---------------------------------------------------------------------------

require_once get_template_directory() . '/inc/roles.php';
require_once get_template_directory() . '/inc/seo.php';
require_once get_template_directory() . '/inc/frontend-post.php';
require_once get_template_directory() . '/inc/ajax-likes.php';

// ---------------------------------------------------------------------------
// Login page branding
// ---------------------------------------------------------------------------

add_action( 'login_enqueue_scripts', 'plataforma_login_styles' );

function plataforma_login_styles(): void {
	$version = wp_get_theme()->get( 'Version' );
	wp_enqueue_style(
		'plataforma-login',
		get_template_directory_uri() . '/assets/css/main.css',
		[],
		$version
	);
}

add_filter( 'login_headerurl', fn() => home_url( '/' ) );
add_filter( 'login_headertext', fn() => get_bloginfo( 'name' ) );

// ---------------------------------------------------------------------------
// Custom excerpt length
// ---------------------------------------------------------------------------

add_filter( 'excerpt_length', fn() => 35 );
add_filter( 'excerpt_more',   fn() => '…' );
