<?php
/**
 * Plugin Name: Plataforma Social
 * Plugin URI:  https://vielac.at
 * Description: Likes, custom roles y categorías por defecto para Plataforma.
 * Version:     1.0.0
 * Author:      Plataforma
 * Text Domain: plataforma-social
 */

defined( 'ABSPATH' ) || exit;

// ---------------------------------------------------------------------------
// Activation
// ---------------------------------------------------------------------------

register_activation_hook( __FILE__, 'plataforma_activate' );

function plataforma_activate(): void {
	plataforma_register_roles();
	plataforma_create_default_categories();
}

// ---------------------------------------------------------------------------
// Custom roles
// ---------------------------------------------------------------------------

add_action( 'init', 'plataforma_register_roles' );

function plataforma_register_roles(): void {
	if ( ! get_role( 'miembro' ) ) {
		add_role( 'miembro', 'Miembro', [
			'read'            => true,
			'plataforma_like' => true,
		] );
	}

	if ( ! get_role( 'autor' ) ) {
		add_role( 'autor', 'Autor', [
			'read'            => true,
			'edit_posts'      => true,
			'delete_posts'    => true,
			'upload_files'    => true,
			'publish_posts'   => true,
			'plataforma_like' => true,
		] );
	}
}

// ---------------------------------------------------------------------------
// Default categories
// ---------------------------------------------------------------------------

function plataforma_create_default_categories(): void {
	$categories = [
		[ 'name' => 'Opinión',   'slug' => 'opinion'  ],
		[ 'name' => 'Noticias',  'slug' => 'noticias' ],
		[ 'name' => 'Eventos',   'slug' => 'eventos'  ],
	];

	foreach ( $categories as $cat ) {
		if ( ! term_exists( $cat['slug'], 'category' ) ) {
			wp_insert_term( $cat['name'], 'category', [ 'slug' => $cat['slug'] ] );
		}
	}
}

// ---------------------------------------------------------------------------
// Likes helpers (also used by the theme)
// ---------------------------------------------------------------------------

function plataforma_user_has_liked( int $post_id, int $user_id ): bool {
	if ( ! $user_id ) {
		return false;
	}
	$likes = get_post_meta( $post_id, '_plataforma_likes', true );
	return is_array( $likes ) && in_array( $user_id, $likes, true );
}

function plataforma_like_count( int $post_id ): int {
	$likes = get_post_meta( $post_id, '_plataforma_likes', true );
	return is_array( $likes ) ? count( $likes ) : 0;
}

// ---------------------------------------------------------------------------
// Like AJAX handlers
// ---------------------------------------------------------------------------

add_action( 'wp_ajax_plataforma_toggle_like',        'plataforma_ajax_toggle_like' );
add_action( 'wp_ajax_nopriv_plataforma_toggle_like', 'plataforma_ajax_like_nopriv' );

function plataforma_ajax_toggle_like(): void {
	check_ajax_referer( 'plataforma_like_nonce', '_wpnonce' );

	if ( ! current_user_can( 'plataforma_like' ) ) {
		wp_send_json_error( [ 'message' => 'Sin permiso para dar me gusta.' ], 403 );
	}

	$post_id = absint( $_POST['post_id'] ?? 0 );
	if ( ! $post_id || get_post_status( $post_id ) !== 'publish' ) {
		wp_send_json_error( [ 'message' => 'Publicación no válida.' ], 400 );
	}

	$user_id = get_current_user_id();
	$likes   = get_post_meta( $post_id, '_plataforma_likes', true );
	if ( ! is_array( $likes ) ) {
		$likes = [];
	}

	$key = array_search( $user_id, $likes, true );
	if ( $key !== false ) {
		array_splice( $likes, $key, 1 );
		$liked = false;
	} else {
		$likes[] = $user_id;
		$liked   = true;
	}

	update_post_meta( $post_id, '_plataforma_likes', $likes );

	wp_send_json_success( [
		'liked' => $liked,
		'count' => count( $likes ),
	] );
}

function plataforma_ajax_like_nopriv(): void {
	wp_send_json_error( [
		'message'  => 'Debes iniciar sesión para dar me gusta.',
		'loginUrl' => wp_login_url( wp_get_referer() ?: home_url( '/' ) ),
	], 401 );
}

// ---------------------------------------------------------------------------
// Frontend post submission AJAX
// ---------------------------------------------------------------------------

add_action( 'wp_ajax_plataforma_submit_post',        'plataforma_ajax_submit_post' );
add_action( 'wp_ajax_nopriv_plataforma_submit_post', 'plataforma_ajax_post_nopriv' );

function plataforma_ajax_submit_post(): void {
	check_ajax_referer( 'plataforma_post_nonce', '_wpnonce' );

	if ( ! current_user_can( 'publish_posts' ) ) {
		wp_send_json_error( [ 'message' => 'Sin permiso para publicar.' ], 403 );
	}

	$title    = sanitize_text_field( $_POST['post_title']   ?? '' );
	$excerpt  = sanitize_text_field( $_POST['post_excerpt'] ?? '' );
	$content  = wp_kses_post( $_POST['post_content']        ?? '' );
	$category = absint( $_POST['post_category']             ?? 0 );

	if ( ! $title || ! $content ) {
		wp_send_json_error( [ 'message' => 'Título y cuerpo son obligatorios.' ], 422 );
	}

	$post_id = wp_insert_post( [
		'post_title'    => $title,
		'post_excerpt'  => $excerpt,
		'post_content'  => $content,
		'post_status'   => 'publish',
		'post_author'   => get_current_user_id(),
		'post_category' => $category ? [ $category ] : [],
	], true );

	if ( is_wp_error( $post_id ) ) {
		wp_send_json_error( [ 'message' => $post_id->get_error_message() ], 500 );
	}

	wp_send_json_success( [ 'redirect' => get_permalink( $post_id ) ] );
}

function plataforma_ajax_post_nopriv(): void {
	wp_send_json_error( [
		'message'  => 'Debes iniciar sesión para publicar.',
		'loginUrl' => wp_login_url( home_url( '/' ) ),
	], 401 );
}

// ---------------------------------------------------------------------------
// Localise script data (runs after theme enqueues scripts, priority 20)
// ---------------------------------------------------------------------------

add_action( 'wp_enqueue_scripts', 'plataforma_localise_scripts', 20 );

function plataforma_localise_scripts(): void {
	if ( ! wp_script_is( 'plataforma-main', 'enqueued' ) ) {
		return;
	}

	wp_localize_script( 'plataforma-main', 'PlataformaData', [
		'ajaxUrl'    => admin_url( 'admin-ajax.php' ),
		'likeNonce'  => wp_create_nonce( 'plataforma_like_nonce' ),
		'postNonce'  => wp_create_nonce( 'plataforma_post_nonce' ),
		'loginUrl'   => wp_login_url( get_permalink() ?: home_url( '/' ) ),
		'isLoggedIn' => is_user_logged_in(),
		'canPost'    => current_user_can( 'publish_posts' ),
		'userId'     => get_current_user_id(),
	] );
}
