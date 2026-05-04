<?php
/**
 * Plugin Name: Plataforma Social
 * Plugin URI:  https://vielac.at
 * Description: Likes, categorías por defecto, redirección post-login para Plataforma.
 * Version:     1.4.0
 * Author:      Plataforma
 * Text Domain: plataforma-social
 */

defined( 'ABSPATH' ) || exit;

const PLATAFORMA_DB_VERSION = '1.4.0';

// ---------------------------------------------------------------------------
// Activation
// ---------------------------------------------------------------------------

register_activation_hook( __FILE__, 'plataforma_activate' );

function plataforma_activate(): void {
	plataforma_create_default_categories();
	plataforma_cleanup_old_roles();
	update_option( 'plataforma_db_version', PLATAFORMA_DB_VERSION );
	flush_rewrite_rules( false );
}

// Run migrations on init when the plugin updates
add_action( 'init', 'plataforma_maybe_upgrade', 5 );

function plataforma_maybe_upgrade(): void {
	$stored = get_option( 'plataforma_db_version', '0' );
	if ( version_compare( $stored, PLATAFORMA_DB_VERSION, '<' ) ) {
		plataforma_cleanup_old_roles();
		update_option( 'plataforma_db_version', PLATAFORMA_DB_VERSION );
		flush_rewrite_rules( false );
	}
}

// ---------------------------------------------------------------------------
// Spanish URL slugs (set on every request, flushed once on upgrade)
// ---------------------------------------------------------------------------

add_action( 'init', 'plataforma_spanish_url_slugs', 1 );

function plataforma_spanish_url_slugs(): void {
	global $wp_rewrite;
	$wp_rewrite->author_base   = 'autor';
	$wp_rewrite->category_base = 'categoria';
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

	// Save link preview meta if provided
	if ( ! empty( $_POST['link_preview'] ) ) {
		$preview = json_decode( wp_unslash( $_POST['link_preview'] ), true );
		if ( is_array( $preview ) ) {
			update_post_meta( $post_id, '_plataforma_link_preview', [
				'title'       => sanitize_text_field( $preview['title']       ?? '' ),
				'description' => sanitize_text_field( $preview['description'] ?? '' ),
				'image'       => esc_url_raw( $preview['image']               ?? '' ),
				'url'         => esc_url_raw( $preview['url']                 ?? '' ),
			] );
		}
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
		'loginNonce' => wp_create_nonce( 'plataforma_login_nonce' ),
		'loginUrl'   => wp_login_url( get_permalink() ?: home_url( '/' ) ),
		'isLoggedIn' => is_user_logged_in(),
		'canPost'    => current_user_can( 'publish_posts' ),
		'userId'     => get_current_user_id(),
	] );
}

// ---------------------------------------------------------------------------
// AJAX login (nopriv — called from the inline login modal)
// ---------------------------------------------------------------------------

add_action( 'wp_ajax_nopriv_plataforma_ajax_login', 'plataforma_ajax_login' );

function plataforma_ajax_login(): void {
	check_ajax_referer( 'plataforma_login_nonce', '_wpnonce' );

	$credentials = [
		'user_login'    => sanitize_user( wp_unslash( $_POST['log'] ?? '' ) ),
		'user_password' => wp_unslash( $_POST['pwd'] ?? '' ),
		'remember'      => ! empty( $_POST['rememberme'] ),
	];

	if ( ! $credentials['user_login'] || ! $credentials['user_password'] ) {
		wp_send_json_error( [ 'message' => 'Usuario y contraseña son obligatorios.' ], 400 );
	}

	$user = wp_signon( $credentials, is_ssl() );

	if ( is_wp_error( $user ) ) {
		wp_send_json_error( [ 'message' => 'Usuario o contraseña incorrectos.' ], 401 );
	}

	wp_send_json_success( [ 'redirect' => home_url( '/' ) ] );
}

// ---------------------------------------------------------------------------
// AJAX image upload (for rich compose editor)
// ---------------------------------------------------------------------------

add_action( 'wp_ajax_plataforma_upload_image', 'plataforma_ajax_upload_image' );

function plataforma_ajax_upload_image(): void {
	check_ajax_referer( 'plataforma_post_nonce', '_wpnonce' );

	if ( ! current_user_can( 'publish_posts' ) ) {
		wp_send_json_error( [ 'message' => 'Sin permiso para subir archivos.' ], 403 );
	}

	if ( empty( $_FILES['file']['name'] ) ) {
		wp_send_json_error( [ 'message' => 'No se recibió ningún archivo.' ], 400 );
	}

	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once ABSPATH . 'wp-admin/includes/image.php';
	require_once ABSPATH . 'wp-admin/includes/media.php';

	$allowed_mimes = [
		'jpg|jpeg|jpe' => 'image/jpeg',
		'png'          => 'image/png',
		'gif'          => 'image/gif',
		'webp'         => 'image/webp',
	];

	$upload = wp_handle_upload( $_FILES['file'], [
		'test_form' => false,
		'mimes'     => $allowed_mimes,
	] );

	if ( isset( $upload['error'] ) ) {
		wp_send_json_error( [ 'message' => $upload['error'] ], 500 );
	}

	$attach_id = wp_insert_attachment( [
		'post_mime_type' => $upload['type'],
		'post_title'     => sanitize_file_name( basename( $upload['file'] ) ),
		'post_status'    => 'inherit',
	], $upload['file'] );

	if ( ! is_wp_error( $attach_id ) ) {
		wp_update_attachment_metadata(
			$attach_id,
			wp_generate_attachment_metadata( $attach_id, $upload['file'] )
		);
	}

	wp_send_json_success( [
		'url' => $upload['url'],
		'id'  => $attach_id,
	] );
}

// ---------------------------------------------------------------------------
// AJAX link preview scraper
// ---------------------------------------------------------------------------

add_action( 'wp_ajax_plataforma_fetch_link_preview',        'plataforma_ajax_link_preview' );
add_action( 'wp_ajax_nopriv_plataforma_fetch_link_preview', 'plataforma_ajax_link_preview' );

function plataforma_ajax_link_preview(): void {
	check_ajax_referer( 'plataforma_post_nonce', '_wpnonce' );

	$url = esc_url_raw( wp_unslash( $_POST['url'] ?? '' ) );

	if ( ! $url || ! filter_var( $url, FILTER_VALIDATE_URL ) ) {
		wp_send_json_error( [ 'message' => 'URL no válida.' ], 400 );
	}

	// Block private/loopback hosts
	$host = (string) parse_url( $url, PHP_URL_HOST );
	if ( ! $host || in_array( $host, [ 'localhost', '127.0.0.1', '::1' ], true ) ) {
		wp_send_json_error( [], 400 );
	}

	$response = wp_remote_get( $url, [
		'timeout'    => 6,
		'user-agent' => 'facebookexternalhit/1.1',
		'sslverify'  => false,
	] );

	if ( is_wp_error( $response ) ) {
		wp_send_json_error( [ 'message' => 'No se pudo cargar la URL.' ], 502 );
	}

	$html = wp_remote_retrieve_body( $response );

	// Helper: search og: meta in both attribute orders
	$og = function ( string $prop ) use ( $html ): string {
		// property before content
		if ( preg_match(
			'/<meta[^>]+property=["\']' . preg_quote( $prop, '/' ) . '["\'][^>]+content=["\']([^"\']*)["\'][^>]*>/i',
			$html, $m
		) ) {
			return html_entity_decode( $m[1], ENT_QUOTES | ENT_HTML5, 'UTF-8' );
		}
		// content before property
		if ( preg_match(
			'/<meta[^>]+content=["\']([^"\']*)["\'][^>]+property=["\']' . preg_quote( $prop, '/' ) . '["\'][^>]*>/i',
			$html, $m
		) ) {
			return html_entity_decode( $m[1], ENT_QUOTES | ENT_HTML5, 'UTF-8' );
		}
		return '';
	};

	$title = $og( 'og:title' );
	$desc  = $og( 'og:description' );
	$image = $og( 'og:image' );

	// Fallbacks
	if ( ! $title && preg_match( '/<title[^>]*>([^<]+)<\/title>/i', $html, $m ) ) {
		$title = html_entity_decode( $m[1], ENT_QUOTES | ENT_HTML5, 'UTF-8' );
	}
	if ( ! $desc && preg_match(
		'/<meta[^>]+name=["\']description["\'][^>]+content=["\']([^"\']*)["\'][^>]*>/i',
		$html, $m
	) ) {
		$desc = html_entity_decode( $m[1], ENT_QUOTES | ENT_HTML5, 'UTF-8' );
	}

	wp_send_json_success( [
		'title'       => wp_strip_all_tags( mb_substr( $title, 0, 120 ) ),
		'description' => wp_strip_all_tags( mb_substr( $desc, 0, 200 ) ),
		'image'       => esc_url_raw( $image ),
		'url'         => $url,
	] );
}

// ---------------------------------------------------------------------------
// /escribir/ rewrite: virtual route → page-escribir.php template
// ---------------------------------------------------------------------------

add_action( 'init', function () {
	add_rewrite_rule( '^escribir/?$', 'index.php?plataforma_escribir=1', 'top' );
}, 2 );

add_filter( 'query_vars', function ( $vars ) {
	$vars[] = 'plataforma_escribir';
	return $vars;
} );

add_filter( 'template_include', function ( $template ) {
	if ( get_query_var( 'plataforma_escribir' ) ) {
		$candidate = get_template_directory() . '/page-escribir.php';
		if ( file_exists( $candidate ) ) {
			return $candidate;
		}
	}
	return $template;
} );

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
