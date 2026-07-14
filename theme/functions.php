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
	add_theme_support( 'custom-logo', [
		'height'      => 80,
		'width'       => 300,
		'flex-height' => true,
		'flex-width'  => true,
	] );

	register_nav_menus( [
		'primary' => 'Menú principal',
		'footer'  => 'Menú de pie de página',
	] );
}

// ---------------------------------------------------------------------------
// Assets
// ---------------------------------------------------------------------------

add_action( 'wp_enqueue_scripts', 'plataforma_enqueue_assets' );

function plataforma_enqueue_assets(): void {
	$version = wp_get_theme()->get( 'Version' );
	$uri     = get_template_directory_uri();

	// style.css — defines the :root CSS-variable tokens used throughout main.css
	wp_enqueue_style(
		'plataforma-style',
		get_stylesheet_uri(),
		[],
		$version
	);

	// Google Fonts: Montserrat (Soleil-style geometric sans-serif, free)
	wp_enqueue_style(
		'plataforma-fonts',
		'https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800&display=swap',
		[],
		null
	);

	wp_enqueue_style(
		'plataforma-main',
		$uri . '/assets/css/main.css',
		[ 'plataforma-fonts' ],
		$version
	);

	// main.js handles filters, compose form AJAX submit, char counters, share
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

// Preconnect to Google Fonts servers for faster loading
add_action( 'wp_head', 'plataforma_font_preconnect', 1 );

function plataforma_font_preconnect(): void {
	echo '<link rel="preconnect" href="https://fonts.googleapis.com">' . "\n";
	echo '<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>' . "\n";
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
	$logo_url   = '';
	$logo_id    = get_theme_mod( 'custom_logo' );
	if ( $logo_id ) {
		$logo_url = wp_get_attachment_image_url( $logo_id, 'full' );
	}

	$logo_css = $logo_url
		? 'background-image: url(' . esc_url( $logo_url ) . '); background-size: contain; width: 280px; height: 80px;'
		: 'background-image: none; font-size: 1.6rem; color: #c0391c;';

	echo '<style>
		body.login { background: #faf6f3; }
		#login h1 a {
			' . $logo_css . '
			display: block;
			margin: 0 auto 20px;
		}
		#loginform, #lostpasswordform {
			border-radius: 18px;
			box-shadow: 0 20px 50px rgba(80,30,20,0.11);
		}
		.wp-core-ui .button-primary {
			background: #c0391c;
			border-color: #9e2a0f;
			box-shadow: none;
			border-radius: 999px;
			padding: 10px 24px;
		}
		.wp-core-ui .button-primary:hover {
			background: #9e2a0f;
			border-color: #9e2a0f;
		}
		input[type=text]:focus, input[type=password]:focus {
			border-color: #c0391c;
			box-shadow: 0 0 0 2px rgba(192,57,28,0.2);
		}
	</style>';
}

add_filter( 'login_headerurl', function() { return home_url( '/' ); } );
add_filter( 'login_headertext', function() { return get_bloginfo( 'name' ); } );

// ---------------------------------------------------------------------------
// Custom excerpt length
// ---------------------------------------------------------------------------

add_filter( 'excerpt_length', function() { return 35; } );
add_filter( 'excerpt_more',   function() { return '…'; } );

// ---------------------------------------------------------------------------
// Comment list callback
// ---------------------------------------------------------------------------

function plataforma_render_comment( WP_Comment $comment, array $args, int $depth ): void {
	?>
	<li id="comment-<?php comment_ID(); ?>" <?php comment_class( 'comment-card' ); ?>>
		<article class="comment-card__body">
			<header class="comment-card__head">
				<?php echo get_avatar( $comment, 40, '', '', [ 'class' => 'comment-card__avatar' ] ); ?>
				<div class="comment-card__meta">
					<span class="comment-card__author"><?php echo esc_html( get_comment_author( $comment ) ); ?></span>
					<time class="comment-card__time" datetime="<?php comment_time( 'c' ); ?>">
						<?php echo esc_html( get_comment_date( 'j M Y, H:i', $comment ) ); ?>
					</time>
				</div>
			</header>
			<div class="comment-card__content"><?php comment_text(); ?></div>
			<footer class="comment-card__footer">
				<?php
				comment_reply_link( array_merge( $args, [
					'depth'     => $depth,
					'max_depth' => $args['max_depth'],
					'reply_text'=> 'Responder',
				] ), $comment );
				?>
			</footer>
		</article>
	<?php
	// Closing </li> is provided by wp_list_comments
}
