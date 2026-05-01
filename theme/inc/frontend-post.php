<?php
/**
 * Non-JS fallback: handles the compose form when submitted as a standard POST request.
 * The AJAX path is handled by the plugin (plataforma_ajax_submit_post).
 */

defined( 'ABSPATH' ) || exit;

add_action( 'init', 'plataforma_handle_post_submission', 1 );

function plataforma_handle_post_submission(): void {
	if ( ! isset( $_POST['plataforma_post_action'] ) || $_POST['plataforma_post_action'] !== 'submit_article' ) {
		return;
	}

	check_admin_referer( 'plataforma_post_nonce', '_wpnonce' );

	if ( ! current_user_can( 'publish_posts' ) ) {
		wp_die( 'Sin permiso para publicar.', 403 );
	}

	$title    = sanitize_text_field( $_POST['post_title']   ?? '' );
	$excerpt  = sanitize_text_field( $_POST['post_excerpt'] ?? '' );
	$content  = wp_kses_post( $_POST['post_content']        ?? '' );
	$category = absint( $_POST['post_category']             ?? 0 );

	if ( ! $title || ! $content ) {
		wp_safe_redirect( add_query_arg( 'plataforma_error', '1', home_url( '/' ) ) );
		exit;
	}

	$post_id = wp_insert_post( [
		'post_title'    => $title,
		'post_excerpt'  => $excerpt,
		'post_content'  => $content,
		'post_status'   => 'publish',
		'post_author'   => get_current_user_id(),
		'post_category' => $category ? [ $category ] : [],
	] );

	if ( is_wp_error( $post_id ) ) {
		wp_safe_redirect( add_query_arg( 'plataforma_error', '2', home_url( '/' ) ) );
		exit;
	}

	wp_safe_redirect( get_permalink( $post_id ) );
	exit;
}
