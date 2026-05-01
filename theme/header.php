<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<div class="page-shell">

	<header class="wp-site-header">
		<a class="wp-site-header__brand" href="<?php echo esc_url( home_url( '/' ) ); ?>">
			<?php bloginfo( 'name' ); ?>
		</a>

		<nav class="wp-site-header__nav" aria-label="Cuenta">
			<?php if ( is_user_logged_in() ) : ?>
				<span class="wp-site-header__greeting">
					Hola, <?php echo esc_html( wp_get_current_user()->display_name ); ?>
				</span>
				<a href="<?php echo esc_url( wp_logout_url( home_url( '/' ) ) ); ?>">
					Salir
				</a>
			<?php else : ?>
				<a href="<?php echo esc_url( wp_login_url( get_permalink() ?: home_url( '/' ) ) ); ?>">
					Ingresar
				</a>
			<?php endif; ?>
		</nav>
	</header>
