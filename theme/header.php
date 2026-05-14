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
		<?php if ( has_custom_logo() ) : ?>
			<?php the_custom_logo(); ?>
		<?php else : ?>
			<a class="site-bar__brand" href="<?php echo esc_url( home_url( '/' ) ); ?>" aria-label="Inicio">
				<span class="site-bar__brand-text"><?php bloginfo( 'name' ); ?></span>
			</a>
		<?php endif; ?>

		<button class="site-bar__hamburger" id="nav-toggle"
		        aria-label="Abrir navegación" aria-expanded="false" aria-controls="site-bar-pages" type="button">
			<span class="hamburger-line"></span>
			<span class="hamburger-line"></span>
			<span class="hamburger-line"></span>
		</button>

		<nav class="site-bar__nav" aria-label="Cuenta">
			<?php if ( is_user_logged_in() ) : ?>
				<a class="site-bar__link" href="<?php echo esc_url( home_url( '/tablero/' ) ); ?>">Mi Espacio</a>

				<?php if ( current_user_can( 'publish_posts' ) ) : ?>
					<a class="site-bar__link site-bar__link--accent site-bar__write"
					   href="<?php echo esc_url( home_url( '/escribir/' ) ); ?>">
						<span aria-hidden="true">✎</span>
						<span class="site-bar__write-label">Escribir</span>
					</a>
				<?php endif; ?>

				<span class="site-bar__user" title="<?php echo esc_attr( wp_get_current_user()->display_name ); ?>">
					<?php echo esc_html( wp_get_current_user()->display_name ); ?>
				</span>

				<a class="site-bar__btn site-bar__btn--ghost"
				   href="<?php echo esc_url( wp_logout_url( home_url( '/' ) ) ); ?>">
					Salir
				</a>
			<?php else : ?>
				<a class="site-bar__btn site-bar__btn--primary"
				   href="<?php echo esc_url( home_url( '/ingresar/' ) ); ?>">
					Ingresar
				</a>
			<?php endif; ?>
		</nav>
	</div>

	<nav class="site-bar__pages" id="site-bar-pages" aria-label="Secciones">
		<?php wp_nav_menu( [
			'theme_location' => 'primary',
			'container'      => false,
			'menu_class'     => 'page-nav',
			'fallback_cb'    => 'plataforma_default_page_nav',
		] ); ?>
	</nav>
</header>

<div class="page-shell">
