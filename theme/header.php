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
				<a class="site-bar__link site-bar__link--accent" href="<?php echo esc_url( wp_login_url( get_permalink() ?: home_url( '/' ) ) ); ?>">
					Ingresar
				</a>
			<?php endif; ?>
		</nav>
	</div>
</header>

<div class="page-shell">
