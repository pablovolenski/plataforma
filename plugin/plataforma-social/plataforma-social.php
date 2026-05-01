<?php
/**
 * Plugin Name: Plataforma Social
 * Plugin URI:  https://vielac.at
 * Description: Likes, categorías por defecto, redirección post-login para Plataforma.
 * Version:     1.2.0
 * Author:      Plataforma
 * Text Domain: plataforma-social
 */

defined( 'ABSPATH' ) || exit;

const PLATAFORMA_DB_VERSION = '1.2.0';

// ---------------------------------------------------------------------------
// Activation
// ---------------------------------------------------------------------------

register_activation_hook( __FILE__, 'plataforma_activate' );

function plataforma_activate(): void {
	plataforma_create_default_categories();
	plataforma_cleanup_old_roles();
	update_option( 'plataforma_db_version', PLATAFORMA_DB_VERSION );
}

// Run migrations on init when the plugin updates
add_action( 'init', 'plataforma_maybe_upgrade', 5 );

function plataforma_maybe_upgrade(): void {
	$stored = get_option( 'plataforma_db_version', '0' );
	if ( version_compare( $stored, PLATAFORMA_DB_VERSION, '<' ) ) {
		plataforma_cleanup_old_roles();
		update_option( 'plataforma_db_version', PLATAFORMA_DB_VERSION );
	}
}

/**
 * Migrate users from old custom roles to built-in WP roles, then remove the
 * custom roles so the admin only sees standard "Autor"/"Suscriptor".
 */
function plataforma_cleanup_old_roles(): void {
	if ( get_role( 'autor' ) ) {
		$users = get_users( [ 'role' => 'autor' ] );
		foreach ( $users as $user ) {
			$user->set_role( 'author' );
		}
		remove_role( 'autor' );
	}
	if ( get_role( 'miembro' ) ) {
		$users = get_users( [ 'role' => 'miembro' ] );
		foreach ( $users as $user ) {
			$user->set_role( 'subscriber' );
		}
		remove_role( 'miembro' );
	}
}

// ---------------------------------------------------------------------------
// Default categories
// ---------------------------------------------------------------------------

function plataforma_create_default_categories(): void {
	$categories = [
		[ 'name' => 'Opinión',  'slug' => 'opinion'  ],
		[ 'name' => 'Noticias', 'slug' => 'noticias' ],
		[ 'name' => 'Eventos',  'slug' => 'eventos'  ],
	];

	foreach ( $categories as $cat ) {
		if ( ! term_exists( $cat['slug'], 'category' ) ) {
			wp_insert_term( $cat['name'], 'category', [ 'slug' => $cat['slug'] ] );
		}
	}
}

// ---------------------------------------------------------------------------
// Like identifier: user ID for logged-in, hashed IP for visitors
// ---------------------------------------------------------------------------

function plataforma_get_liker_identifier(): string {
	$user_id = get_current_user_id();
	if ( $user_id ) {
		return (string) $user_id;
	}
	$ip = $_SERVER['REMOTE_ADDR'] ?? '';
	return 'ip:' . hash_hmac( 'sha256', $ip, wp_salt( 'auth' ) );
}

function plataforma_user_has_liked( int $post_id, $identifier = null ): bool {
	$identifier = $identifier ?: plataforma_get_liker_identifier();
	if ( ! $identifier ) {
		return false;
	}
	$likes = get_post_meta( $post_id, '_plataforma_likes', true );
	return is_array( $likes ) && in_array( (string) $identifier, $likes, true );
}

function plataforma_like_count( int $post_id ): int {
	$likes = get_post_meta( $post_id, '_plataforma_likes', true );
	return is_array( $likes ) ? count( $likes ) : 0;
}

// ---------------------------------------------------------------------------
// Like AJAX (open to visitors with simple anti-bot for non-logged users)
// ---------------------------------------------------------------------------

add_action( 'wp_ajax_plataforma_toggle_like',        'plataforma_ajax_toggle_like' );
add_action( 'wp_ajax_nopriv_plataforma_toggle_like', 'plataforma_ajax_toggle_like' );

function plataforma_ajax_toggle_like(): void {
	check_ajax_referer( 'plataforma_like_nonce', '_wpnonce' );

	$post_id = absint( $_POST['post_id'] ?? 0 );
	if ( ! $post_id || get_post_status( $post_id ) !== 'publish' ) {
		wp_send_json_error( [ 'message' => 'Publicación no válida.' ], 400 );
	}

	$user_id = get_current_user_id();

	// Anti-bot for visitors only: honeypot + minimum time-on-page
	if ( ! $user_id ) {
		// Honeypot: should be empty
		if ( ! empty( $_POST['hp'] ) ) {
			wp_send_json_error( [ 'message' => 'Bot detectado.' ], 403 );
		}
		// Page must have been loaded for at least 800 ms
		$elapsed = absint( $_POST['t'] ?? 0 );
		if ( $elapsed < 800 ) {
			wp_send_json_error( [ 'message' => 'Espera un momento antes de reaccionar.' ], 429 );
		}
	}

	$identifier = plataforma_get_liker_identifier();

	$likes = get_post_meta( $post_id, '_plataforma_likes', true );
	if ( ! is_array( $likes ) ) {
		$likes = [];
	}

	$key = array_search( (string) $identifier, $likes, true );
	if ( $key !== false ) {
		array_splice( $likes, $key, 1 );
		$liked = false;
	} else {
		$likes[] = (string) $identifier;
		$liked   = true;
	}

	update_post_meta( $post_id, '_plataforma_likes', $likes );

	wp_send_json_success( [
		'liked' => $liked,
		'count' => count( $likes ),
	] );
}

// ---------------------------------------------------------------------------
// Frontend post submission AJAX
// ---------------------------------------------------------------------------

add_action( 'wp_ajax_plataforma_submit_post',        'plataforma_ajax_submit_post' );
add_action( 'wp_ajax_nopriv_plataforma_submit_post', 'plataforma_ajax_post_nopriv' );

function plataforma_ajax_submit_post(): void {
	check_ajax_referer( 'plataforma_post_nonce', '_wpnonce' );

	if ( ! current_user_can( 'publish_posts' ) ) {
		wp_send_json_error( [
			'message' => 'Tu cuenta no tiene permiso para publicar. Pide al administrador que te asigne el rol "Autor".',
		], 403 );
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
// Localise script data (priority 20: after theme enqueues)
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

// ---------------------------------------------------------------------------
// Redirect non-admins to home after login (no ugly backend)
// ---------------------------------------------------------------------------

add_filter( 'login_redirect', 'plataforma_login_redirect', 10, 3 );

function plataforma_login_redirect( $redirect_to, $request, $user ) {
	if ( $user instanceof WP_User && $user->ID && ! is_wp_error( $user ) ) {
		if ( ! user_can( $user, 'manage_options' ) ) {
			return home_url( '/' );
		}
	}
	return $redirect_to;
}
