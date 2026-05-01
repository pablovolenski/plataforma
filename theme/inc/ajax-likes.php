<?php
/**
 * Fallback AJAX guard: runs only if the plugin is inactive.
 * The real handlers live in plataforma-social/plataforma-social.php.
 */

defined( 'ABSPATH' ) || exit;

add_action( 'wp_ajax_plataforma_toggle_like',        'plataforma_theme_like_fallback' );
add_action( 'wp_ajax_nopriv_plataforma_toggle_like', 'plataforma_theme_like_fallback' );
add_action( 'wp_ajax_plataforma_submit_post',        'plataforma_theme_post_fallback' );
add_action( 'wp_ajax_nopriv_plataforma_submit_post', 'plataforma_theme_post_fallback' );

function plataforma_theme_like_fallback(): void {
	// Only fire when the plugin has not registered its own handler.
	if ( function_exists( 'plataforma_ajax_toggle_like' ) ) {
		return;
	}
	wp_send_json_error( [
		'message' => 'El plugin Plataforma Social no está activo. Actívalo en wp-admin.',
	], 503 );
}

function plataforma_theme_post_fallback(): void {
	if ( function_exists( 'plataforma_ajax_submit_post' ) ) {
		return;
	}
	wp_send_json_error( [
		'message' => 'El plugin Plataforma Social no está activo. Actívalo en wp-admin.',
	], 503 );
}
