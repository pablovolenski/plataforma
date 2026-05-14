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

		<nav class="site-bar__nav" aria-label="Cuenta">
			<?php if ( is_user_logged_in() ) : ?>
				<span class="site-bar__user">
					Conectado como <strong><?php echo esc_html( wp_get_current_user()->display_name ); ?></strong>
				</span>

				<a class="site-bar__link" href="<?php echo esc_url( home_url( '/tablero/' ) ); ?>">Mi Espacio</a>

				<?php if ( current_user_can( 'publish_posts' ) ) : ?>
					<a class="site-bar__link site-bar__link--accent site-bar__write"
					   href="<?php echo esc_url( home_url( '/escribir/' ) ); ?>">
						<span aria-hidden="true">✎</span>
						<span class="site-bar__write-label">Escribir</span>
					</a>
				<?php endif; ?>

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

		<button class="site-bar__hamburger" id="nav-toggle"
		        aria-label="Abrir navegación" aria-expanded="false" aria-controls="site-bar-pages" type="button">
			<span class="hamburger-line"></span>
			<span class="hamburger-line"></span>
			<span class="hamburger-line"></span>
		</button>
	</div>

	<nav class="site-bar__pages" id="site-bar-pages" aria-label="Secciones">
		<?php wp_nav_menu( [
			'theme_location' => 'primary',
			'container'      => false,
			'menu_class'     => 'page-nav',
			'fallback_cb'    => 'plataforma_default_page_nav',
		] ); ?>

		<div class="site-bar__mobile-account">
			<?php if ( is_user_logged_in() ) : ?>
				<a class="site-bar__mobile-account-link" href="<?php echo esc_url( home_url( '/tablero/' ) ); ?>">
					<svg aria-hidden="true" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" width="18" height="18"><circle cx="12" cy="8" r="4" stroke="currentColor" stroke-width="1.6"/><path d="M4 20c0-3.314 3.582-6 8-6s8 2.686 8 6" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
					Mi Espacio
				</a>
				<a class="site-bar__mobile-account-link site-bar__mobile-account-link--out" href="<?php echo esc_url( wp_logout_url( home_url( '/' ) ) ); ?>">
					<svg aria-hidden="true" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" width="18" height="18"><path d="M15 12H3m0 0 4-4m-4 4 4 4" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/><path d="M9 7V5a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-8a2 2 0 0 1-2-2v-2" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
					Salir
				</a>
			<?php else : ?>
				<a class="site-bar__mobile-account-link site-bar__mobile-account-link--primary" href="<?php echo esc_url( home_url( '/ingresar/' ) ); ?>">
					Ingresar
				</a>
			<?php endif; ?>
		</div>
	</nav>
</header>

<div class="page-shell">
