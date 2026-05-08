<?php
/**
 * Custom login page — served at /ingresar/
 * Fully themed, replaces wp-login.php for regular users.
 */

defined( 'ABSPATH' ) || exit;

// Already logged in → go to dashboard
if ( is_user_logged_in() ) {
	wp_safe_redirect( home_url( '/tablero/' ) );
	exit;
}

$error       = '';
$redirect_to = isset( $_GET['redirect_to'] ) ? esc_url_raw( wp_unslash( $_GET['redirect_to'] ) ) : home_url( '/tablero/' );

// PHP server-side fallback (no-JS login)
if ( 'POST' === $_SERVER['REQUEST_METHOD'] && ! empty( $_POST['plataforma_login_nonce'] ) ) {
	if ( wp_verify_nonce( wp_unslash( $_POST['plataforma_login_nonce'] ), 'plataforma_login_page' ) ) {
		$credentials = [
			'user_login'    => sanitize_user( wp_unslash( $_POST['log'] ?? '' ) ),
			'user_password' => wp_unslash( $_POST['pwd'] ?? '' ),
			'remember'      => ! empty( $_POST['rememberme'] ),
		];
		if ( $credentials['user_login'] && $credentials['user_password'] ) {
			$user = wp_signon( $credentials, is_ssl() );
			if ( ! is_wp_error( $user ) ) {
				wp_safe_redirect( $redirect_to ?: home_url( '/tablero/' ) );
				exit;
			}
			$error = 'Usuario o contraseña incorrectos.';
		} else {
			$error = 'Usuario y contraseña son obligatorios.';
		}
	}
}

get_header();
?>

<main class="ingresar-page">
	<div class="login-card">
		<h1 class="login-card__title"><?php bloginfo( 'name' ); ?></h1>
		<p class="login-card__subtitle">Ingresa a tu cuenta</p>

		<?php if ( $error ) : ?>
			<div class="notice notice--error" role="alert"><?php echo esc_html( $error ); ?></div>
		<?php endif; ?>

		<div id="login-notice" class="notice" hidden aria-live="polite"></div>

		<form id="login-form-page" class="login-form" method="post" novalidate>
			<?php wp_nonce_field( 'plataforma_login_page', 'plataforma_login_nonce' ); ?>
			<input type="hidden" name="redirect_to" value="<?php echo esc_attr( $redirect_to ); ?>">

			<label>
				Usuario o email
				<input
					type="text"
					name="log"
					required
					autocomplete="username"
					value="<?php echo esc_attr( wp_unslash( $_POST['log'] ?? '' ) ); ?>"
				>
			</label>

			<label>
				Contraseña
				<input type="password" name="pwd" required autocomplete="current-password">
			</label>

			<label class="login-form__remember">
				<input type="checkbox" name="rememberme" value="forever">
				Recordarme
			</label>

			<button type="submit" class="btn-primary btn-full">Ingresar</button>
		</form>

		<p class="login-card__forgot">
			<a href="<?php echo esc_url( wp_lostpassword_url() ); ?>">¿Olvidaste tu contraseña?</a>
		</p>
	</div>
</main>

<?php get_footer(); ?>
