<?php
/**
 * Plugin Name: Plataforma Social
 * Plugin URI:  https://vielac.at
 * Description: Likes, categorías por defecto, redirección post-login para Plataforma.
 * Version:     1.7.0
 * Author:      Plataforma
 * Text Domain: plataforma-social
 */

defined( 'ABSPATH' ) || exit;

if ( ! defined( 'PLATAFORMA_DB_VERSION' ) ) {
	define( 'PLATAFORMA_DB_VERSION', '1.7.0' );
}

// Hide the frontend admin bar for all users — access WP admin via /wp-admin
add_filter( 'show_admin_bar', '__return_false' );

// ---------------------------------------------------------------------------
// Activation
// ---------------------------------------------------------------------------

register_activation_hook( __FILE__, 'plataforma_activate' );

function plataforma_activate(): void {
	plataforma_create_default_categories();
	plataforma_cleanup_old_roles();
	plataforma_grant_notice_caps();
	update_option( 'plataforma_db_version', PLATAFORMA_DB_VERSION );
	flush_rewrite_rules( false );
}

// Run migrations on init when the plugin updates (also creates default pages)
add_action( 'init', 'plataforma_maybe_upgrade', 5 );

function plataforma_maybe_upgrade(): void {
	$stored = get_option( 'plataforma_db_version', '0' );
	if ( version_compare( $stored, PLATAFORMA_DB_VERSION, '<' ) ) {
		plataforma_cleanup_old_roles();
		plataforma_grant_notice_caps();
		plataforma_create_default_categories();
		plataforma_create_default_pages();
		update_option( 'plataforma_db_version', PLATAFORMA_DB_VERSION );
		flush_rewrite_rules( false );
	}
}

// ---------------------------------------------------------------------------
// Admin notices CPT (plataforma_notice) — only admins can create/edit
// ---------------------------------------------------------------------------

add_action( 'init', 'plataforma_register_notice_cpt', 3 );

function plataforma_register_notice_cpt(): void {
	register_post_type( 'plataforma_notice', [
		'label'           => 'Avisos',
		'labels'          => [
			'name'          => 'Avisos',
			'singular_name' => 'Aviso',
			'add_new_item'  => 'Nuevo aviso',
			'edit_item'     => 'Editar aviso',
			'all_items'     => 'Todos los avisos',
		],
		'public'             => false,
		'publicly_queryable' => false,
		'show_ui'            => true,
		'show_in_menu'       => true,
		'show_in_rest'       => true,
		'menu_icon'          => 'dashicons-megaphone',
		'menu_position'      => 5,
		'supports'           => [ 'title', 'editor', 'author' ],
		'capability_type'    => 'plataforma_notice',
		'map_meta_cap'       => true,
	] );
}

function plataforma_grant_notice_caps(): void {
	$admin = get_role( 'administrator' );
	if ( ! $admin ) {
		return;
	}
	foreach ( [
		'edit_plataforma_notice',
		'read_plataforma_notice',
		'delete_plataforma_notice',
		'edit_plataforma_notices',
		'edit_others_plataforma_notices',
		'publish_plataforma_notices',
		'read_private_plataforma_notices',
		'delete_plataforma_notices',
		'delete_private_plataforma_notices',
		'delete_published_plataforma_notices',
		'delete_others_plataforma_notices',
		'edit_private_plataforma_notices',
		'edit_published_plataforma_notices',
	] as $cap ) {
		$admin->add_cap( $cap );
	}
}

// ---------------------------------------------------------------------------
// User groups — option-based list managed by admin, assigned per user
// ---------------------------------------------------------------------------

/**
 * Returns all defined groups as [ ['id' => slug, 'name' => label], ... ]
 */
function plataforma_get_groups(): array {
	$raw    = get_option( 'plataforma_groups', '' );
	$lines  = array_filter( array_map( 'trim', explode( "\n", $raw ) ) );
	$groups = [];
	foreach ( $lines as $name ) {
		$groups[] = [
			'id'   => sanitize_title( $name ),
			'name' => $name,
		];
	}
	return $groups;
}

// Admin settings page: Settings > Grupos Plataforma
add_action( 'admin_menu', function () {
	add_options_page(
		'Grupos de usuarios',
		'Grupos Plataforma',
		'manage_options',
		'plataforma-grupos',
		'plataforma_groups_admin_page_html'
	);
} );

function plataforma_groups_admin_page_html(): void {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	if ( isset( $_POST['plataforma_groups_save'] ) ) {
		check_admin_referer( 'plataforma_groups_save' );
		$raw = sanitize_textarea_field( wp_unslash( $_POST['plataforma_groups_text'] ?? '' ) );
		update_option( 'plataforma_groups', $raw );
		echo '<div class="notice notice-success is-dismissible"><p>Grupos guardados.</p></div>';
	}

	$current = (string) get_option( 'plataforma_groups', '' );
	?>
	<div class="wrap">
		<h1>Grupos de usuarios</h1>
		<p>Un nombre de grupo por línea. El administrador asigna grupos a cada usuario desde su perfil.</p>
		<form method="post">
			<?php wp_nonce_field( 'plataforma_groups_save' ); ?>
			<textarea name="plataforma_groups_text" rows="12"
			          style="width:400px;max-width:100%;font-family:monospace;font-size:13px;"><?php echo esc_textarea( $current ); ?></textarea>
			<p>
				<input type="submit" name="plataforma_groups_save"
				       class="button button-primary" value="Guardar grupos">
			</p>
		</form>
	</div>
	<?php
}

// User edit screen: group assignment checkboxes (admin only)
add_action( 'show_user_profile', 'plataforma_user_groups_fields' );
add_action( 'edit_user_profile', 'plataforma_user_groups_fields' );

function plataforma_user_groups_fields( WP_User $user ): void {
	$groups = plataforma_get_groups();
	if ( empty( $groups ) ) {
		return;
	}
	$user_groups = (array) ( get_user_meta( $user->ID, '_plataforma_groups', true ) ?: [] );
	?>
	<h2>Grupos Plataforma</h2>
	<table class="form-table" role="presentation">
		<tr>
			<th scope="row"><label>Grupos asignados</label></th>
			<td>
				<?php foreach ( $groups as $g ) : ?>
					<label style="display:block;margin-bottom:5px;">
						<input
							type="checkbox"
							name="plataforma_groups[]"
							value="<?php echo esc_attr( $g['id'] ); ?>"
							<?php checked( in_array( $g['id'], $user_groups, true ) ); ?>
						>
						<?php echo esc_html( $g['name'] ); ?>
					</label>
				<?php endforeach; ?>
			</td>
		</tr>
	</table>
	<?php
}

add_action( 'personal_options_update', 'plataforma_save_user_groups' );
add_action( 'edit_user_profile_update', 'plataforma_save_user_groups' );

function plataforma_save_user_groups( int $user_id ): void {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$groups = array_map( 'sanitize_key', (array) ( $_POST['plataforma_groups'] ?? [] ) );
	update_user_meta( $user_id, '_plataforma_groups', $groups );
}

// User list: column showing assigned groups
add_filter( 'manage_users_columns', function ( $cols ) {
	$cols['plataforma_groups'] = 'Grupos';
	return $cols;
} );

add_filter( 'manage_users_custom_column', function ( $output, $col, $user_id ) {
	if ( 'plataforma_groups' !== $col ) {
		return $output;
	}
	$groups      = plataforma_get_groups();
	$user_groups = (array) ( get_user_meta( $user_id, '_plataforma_groups', true ) ?: [] );
	$names       = [];
	foreach ( $groups as $g ) {
		if ( in_array( $g['id'], $user_groups, true ) ) {
			$names[] = esc_html( $g['name'] );
		}
	}
	return $names ? implode( ', ', $names ) : '<span style="color:#999">—</span>';
}, 10, 3 );

// ---------------------------------------------------------------------------
// Login takeover — /ingresar/ replaces wp-login.php for non-admins
// ---------------------------------------------------------------------------

// Filter all login_url() calls to point to our page
add_filter( 'login_url', function ( $url, $redirect, $force_reauth ) {
	$login = home_url( '/ingresar/' );
	if ( $redirect ) {
		$login = add_query_arg( 'redirect_to', rawurlencode( $redirect ), $login );
	}
	return $login;
}, 10, 3 );

// Redirect wp-login.php GET requests for non-admins
add_action( 'init', function () {
	$uri = $_SERVER['REQUEST_URI'] ?? '';
	if ( false === strpos( $uri, 'wp-login.php' ) ) {
		return;
	}
	// Let admins through and let POST (actual auth + WP nonces) through
	if ( 'POST' === $_SERVER['REQUEST_METHOD'] ) {
		return;
	}
	if ( is_user_logged_in() && current_user_can( 'manage_options' ) ) {
		return;
	}
	// Allow password-reset actions (the link in the email still works)
	$action = isset( $_GET['action'] ) ? sanitize_key( $_GET['action'] ) : 'login';
	if ( in_array( $action, [ 'rp', 'resetpass', 'lostpassword', 'confirmaction' ], true ) ) {
		return;
	}
	$redirect_to  = isset( $_GET['redirect_to'] ) ? esc_url_raw( wp_unslash( $_GET['redirect_to'] ) ) : home_url( '/tablero/' );
	wp_safe_redirect(
		add_query_arg( 'redirect_to', rawurlencode( $redirect_to ), home_url( '/ingresar/' ) )
	);
	exit;
}, 1 );

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
		[ 'name' => 'Paseos Urbanos', 'slug' => 'paseos-urbanos' ],
		[ 'name' => 'Historia Oral',  'slug' => 'historia-oral'  ],
		[ 'name' => 'Mapa Textil',    'slug' => 'mapa-textil'    ],
		[ 'name' => 'Arte',           'slug' => 'arte'           ],
		[ 'name' => 'Eventos',        'slug' => 'eventos'        ],
	];

	foreach ( $categories as $cat ) {
		if ( ! term_exists( $cat['slug'], 'category' ) ) {
			wp_insert_term( $cat['name'], 'category', [ 'slug' => $cat['slug'] ] );
		}
	}
}

// ---------------------------------------------------------------------------
// Default static pages (Über uns, Werke, Mitgliedschaft, Kontakt)
// ---------------------------------------------------------------------------

function plataforma_create_default_pages(): void {
	$pages = [
		[ 'title' => 'Über uns',       'slug' => 'uber-uns'       ],
		[ 'title' => 'Werke',          'slug' => 'werke'          ],
		[ 'title' => 'Mitgliedschaft', 'slug' => 'mitgliedschaft' ],
		[ 'title' => 'Kontakt',        'slug' => 'kontakt'        ],
	];
	foreach ( $pages as $p ) {
		if ( ! get_page_by_path( $p['slug'] ) ) {
			wp_insert_post( [
				'post_title'  => $p['title'],
				'post_name'   => $p['slug'],
				'post_status' => 'publish',
				'post_type'   => 'page',
				'post_author' => 1,
			] );
		}
	}
}

/**
 * Fallback primary nav rendered when no menu is assigned to the "primary" location.
 * Called by wp_nav_menu() via its fallback_cb argument.
 */
function plataforma_default_page_nav( array $args ): void {
	$home_active = is_front_page() || is_home();
	$items = [
		[ 'url' => home_url( '/' ),                'label' => 'Plataforma',    'class' => $home_active ? 'current-menu-item page-nav__home' : 'page-nav__home' ],
		[ 'url' => home_url( '/uber-uns/' ),       'label' => 'Über uns',      'class' => is_page( 'uber-uns' ) ? 'current-menu-item' : '' ],
		[ 'url' => home_url( '/werke/' ),          'label' => 'Werke',         'class' => is_page( 'werke' ) ? 'current-menu-item' : '' ],
		[ 'url' => home_url( '/mitgliedschaft/' ), 'label' => 'Mitgliedschaft','class' => is_page( 'mitgliedschaft' ) ? 'current-menu-item' : '' ],
		[ 'url' => home_url( '/kontakt/' ),        'label' => 'Kontakt',       'class' => is_page( 'kontakt' ) ? 'current-menu-item' : '' ],
	];
	echo '<ul class="page-nav">';
	foreach ( $items as $item ) {
		$class = $item['class'] ? ' class="' . esc_attr( trim( $item['class'] ) ) . '"' : '';
		printf(
			'<li%s><a href="%s">%s</a></li>',
			$class,
			esc_url( $item['url'] ),
			esc_html( $item['label'] )
		);
	}
	echo '</ul>';
}

// ---------------------------------------------------------------------------
// Like identifier: user ID for logged-in, hashed IP for visitors
// (guarded: theme/inc/roles.php defines identical fallbacks when plugin is inactive)
// ---------------------------------------------------------------------------

if ( ! function_exists( 'plataforma_get_liker_identifier' ) ) {
	function plataforma_get_liker_identifier(): string {
		$user_id = get_current_user_id();
		if ( $user_id ) {
			return (string) $user_id;
		}
		$ip = $_SERVER['REMOTE_ADDR'] ?? '';
		return 'ip:' . hash_hmac( 'sha256', $ip, wp_salt( 'auth' ) );
	}
}

if ( ! function_exists( 'plataforma_user_has_liked' ) ) {
	function plataforma_user_has_liked( int $post_id, $identifier = null ): bool {
		$identifier = $identifier ?: plataforma_get_liker_identifier();
		if ( ! $identifier ) {
			return false;
		}
		$likes = get_post_meta( $post_id, '_plataforma_likes', true );
		return is_array( $likes ) && in_array( (string) $identifier, $likes, true );
	}
}

if ( ! function_exists( 'plataforma_like_count' ) ) {
	function plataforma_like_count( int $post_id ): int {
		$likes = get_post_meta( $post_id, '_plataforma_likes', true );
		return is_array( $likes ) ? count( $likes ) : 0;
	}
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
		'ajaxUrl'      => admin_url( 'admin-ajax.php' ),
		'likeNonce'    => wp_create_nonce( 'plataforma_like_nonce' ),
		'postNonce'    => wp_create_nonce( 'plataforma_post_nonce' ),
		'loginNonce'   => wp_create_nonce( 'plataforma_login_nonce' ),
		'profileNonce' => wp_create_nonce( 'plataforma_profile_nonce' ),
		'loginUrl'     => home_url( '/ingresar/' ),
		'tableroUrl'   => home_url( '/tablero/' ),
		'isLoggedIn'   => is_user_logged_in(),
		'canPost'      => current_user_can( 'publish_posts' ),
		'userId'       => get_current_user_id(),
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

	$requested   = isset( $_POST['redirect_to'] ) ? esc_url_raw( wp_unslash( $_POST['redirect_to'] ) ) : '';
	$redirect_to = ( $requested && wp_validate_redirect( $requested, false ) )
		? $requested
		: home_url( '/tablero/' );

	wp_send_json_success( [ 'redirect' => $redirect_to ] );
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
// Virtual routes — /ingresar/, /tablero/, /escribir/
// ---------------------------------------------------------------------------

add_action( 'init', function () {
	add_rewrite_rule( '^ingresar/?$', 'index.php?plataforma_ingresar=1', 'top' );
	add_rewrite_rule( '^tablero/?$',  'index.php?plataforma_tablero=1',  'top' );
	add_rewrite_rule( '^escribir/?$', 'index.php?plataforma_escribir=1', 'top' );
}, 2 );

add_filter( 'query_vars', function ( $vars ) {
	$vars[] = 'plataforma_ingresar';
	$vars[] = 'plataforma_tablero';
	$vars[] = 'plataforma_escribir';
	return $vars;
} );

add_filter( 'template_include', function ( $template ) {
	$map = [
		'plataforma_ingresar' => '/page-ingresar.php',
		'plataforma_tablero'  => '/page-tablero.php',
		'plataforma_escribir' => '/page-escribir.php',
	];
	foreach ( $map as $var => $file ) {
		if ( get_query_var( $var ) ) {
			$candidate = get_template_directory() . $file;
			if ( file_exists( $candidate ) ) {
				return $candidate;
			}
		}
	}
	return $template;
} );

// URI-based fallback: if rewrite-rule cache is stale the query var won't be
// set, so we catch the raw URI in template_redirect and serve directly.
add_action( 'template_redirect', 'plataforma_route_uri_fallback', 1 );

function plataforma_route_uri_fallback(): void {
	// Primary path already handled by template_include — nothing to do.
	if ( get_query_var( 'plataforma_ingresar' ) ||
	     get_query_var( 'plataforma_tablero' )  ||
	     get_query_var( 'plataforma_escribir' ) ) {
		return;
	}

	$request = trim( (string) parse_url( $_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH ), '/' );
	$map = [
		'ingresar' => '/page-ingresar.php',
		'tablero'  => '/page-tablero.php',
		'escribir' => '/page-escribir.php',
	];

	if ( isset( $map[ $request ] ) ) {
		$file = get_template_directory() . $map[ $request ];
		if ( file_exists( $file ) ) {
			status_header( 200 );
			include $file;
			exit;
		}
	}
}

// ---------------------------------------------------------------------------
// AJAX: Profile info update (display name, email, bio)
// ---------------------------------------------------------------------------

add_action( 'wp_ajax_plataforma_update_profile', 'plataforma_update_profile' );

function plataforma_update_profile(): void {
	check_ajax_referer( 'plataforma_profile_nonce', '_wpnonce' );

	$user_id = get_current_user_id();
	if ( ! $user_id ) {
		wp_send_json_error( [ 'message' => 'No autenticado.' ], 401 );
	}

	$data         = [ 'ID' => $user_id ];
	$display_name = sanitize_text_field( wp_unslash( $_POST['display_name'] ?? '' ) );
	$user_email   = sanitize_email( wp_unslash( $_POST['user_email'] ?? '' ) );
	$description  = sanitize_textarea_field( wp_unslash( $_POST['description'] ?? '' ) );

	if ( $display_name ) {
		$data['display_name'] = $display_name;
	}
	if ( $user_email && is_email( $user_email ) ) {
		$data['user_email'] = $user_email;
	}

	if ( count( $data ) > 1 ) {
		$result = wp_update_user( $data );
		if ( is_wp_error( $result ) ) {
			wp_send_json_error( [ 'message' => $result->get_error_message() ], 400 );
		}
	}

	update_user_meta( $user_id, 'description', $description );

	wp_send_json_success( [ 'message' => 'Perfil actualizado.' ] );
}

// ---------------------------------------------------------------------------
// AJAX: Password change
// ---------------------------------------------------------------------------

add_action( 'wp_ajax_plataforma_change_password', 'plataforma_change_password' );

function plataforma_change_password(): void {
	check_ajax_referer( 'plataforma_profile_nonce', '_wpnonce' );

	$user_id = get_current_user_id();
	if ( ! $user_id ) {
		wp_send_json_error( [ 'message' => 'No autenticado.' ], 401 );
	}

	$current = wp_unslash( $_POST['current_password'] ?? '' );
	$new     = wp_unslash( $_POST['new_password']     ?? '' );
	$confirm = wp_unslash( $_POST['confirm_password'] ?? '' );

	if ( ! $current || ! $new || ! $confirm ) {
		wp_send_json_error( [ 'message' => 'Todos los campos son obligatorios.' ], 400 );
	}
	if ( $new !== $confirm ) {
		wp_send_json_error( [ 'message' => 'Las contraseñas nuevas no coinciden.' ], 400 );
	}
	if ( strlen( $new ) < 8 ) {
		wp_send_json_error( [ 'message' => 'La contraseña debe tener al menos 8 caracteres.' ], 400 );
	}

	$user = get_user_by( 'id', $user_id );
	if ( ! wp_check_password( $current, $user->user_pass, $user_id ) ) {
		wp_send_json_error( [ 'message' => 'La contraseña actual es incorrecta.' ], 403 );
	}

	wp_set_password( $new, $user_id );
	wp_set_auth_cookie( $user_id, true ); // keep the user logged in after reset

	wp_send_json_success( [ 'message' => 'Contraseña actualizada correctamente.' ] );
}

// ---------------------------------------------------------------------------
// Redirect non-admins to /tablero/ after WP form login
// ---------------------------------------------------------------------------

add_filter( 'login_redirect', 'plataforma_login_redirect', 10, 3 );

function plataforma_login_redirect( $redirect_to, $request, $user ) {
	if ( $user instanceof WP_User && $user->ID && ! is_wp_error( $user ) ) {
		if ( ! user_can( $user, 'manage_options' ) ) {
			return home_url( '/tablero/' );
		}
	}
	return $redirect_to;
}
