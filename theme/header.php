<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<header class="site-bar">
	<div class="site-bar__inner">
		<a class="site-bar__brand" href="<?php echo esc_url( home_url( '/' ) ); ?>" aria-label="Inicio">
			<?php if ( has_custom_logo() ) : ?>
				<?php the_custom_logo(); ?>
			<?php else : ?>
				<span class="site-bar__brand-text"><?php bloginfo( 'name' ); ?></span>
			<?php endif; ?>
		</a>

		<nav class="site-bar__nav" aria-label="Cuenta">
			<?php if ( is_user_logged_in() ) : ?>
				<span class="site-bar__greeting">
					<?php echo esc_html( wp_get_current_user()->display_name ); ?>
				</span>
				<a class="site-bar__link" href="<?php echo esc_url( wp_logout_url( home_url( '/' ) ) ); ?>">
					Salir
				</a>
			<?php else : ?>
				<a
					class="site-bar__link site-bar__link--accent"
					href="<?php echo esc_url( wp_login_url( get_permalink() ?: home_url( '/' ) ) ); ?>"
					data-action="open-login"
				>Ingresar</a>
			<?php endif; ?>
		</nav>
	</div>
</header>

<?php if ( ! is_user_logged_in() ) : ?>
<dialog class="login-modal" id="login-modal" aria-labelledby="login-modal-title">
	<div class="login-modal__inner">
		<button type="button" class="login-modal__close" data-action="close-login" aria-label="Cerrar">×</button>
		<h2 id="login-modal-title">Ingresar</h2>

		<div id="login-notice" class="notice" hidden aria-live="polite"></div>

		<form
			id="login-form"
			class="login-form"
			action="<?php echo esc_url( wp_login_url( home_url( '/' ) ) ); ?>"
			method="post"
			novalidate
		>
			<input type="hidden" name="redirect_to" value="<?php echo esc_url( home_url( '/' ) ); ?>">

			<label>
				Usuario o email
				<input type="text" name="log" required autocomplete="username">
			</label>

			<label>
				Contraseña
				<input type="password" name="pwd" required autocomplete="current-password">
			</label>

			<label class="login-modal__remember">
				<input type="checkbox" name="rememberme" value="forever">
				Recordarme
			</label>

			<button type="submit" class="btn-primary btn-full">Ingresar</button>
		</form>

		<p class="login-modal__forgot">
			<a href="<?php echo esc_url( wp_lostpassword_url( home_url( '/' ) ) ); ?>">¿Olvidaste tu contraseña?</a>
		</p>
	</div>
</dialog>
<?php endif; ?>

<div class="page-shell">
