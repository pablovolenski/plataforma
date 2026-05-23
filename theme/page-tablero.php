<?php
/**
 * Dashboard (tablero) — served at /tablero/
 * Three tabs: admin messages, compose form, profile editor.
 */

defined( 'ABSPATH' ) || exit;

if ( ! is_user_logged_in() ) {
	wp_safe_redirect(
		add_query_arg( 'redirect_to', urlencode( home_url( '/tablero/' ) ), home_url( '/ingresar/' ) )
	);
	exit;
}

$current_user = wp_get_current_user();
get_header();
?>

<main class="tablero">

	<header class="tablero__hero">
		<p class="tablero__welcome">Hola, <?php echo esc_html( $current_user->display_name ); ?></p>
		<h1 class="tablero__title">Tu espacio</h1>
	</header>

	<nav class="tablero__tabs" role="tablist" aria-label="Secciones del tablero">
		<button class="tablero__tab" role="tab"
		        data-tab="mensajes" aria-controls="tab-mensajes" aria-selected="true">
			Mensajes
		</button>
		<button class="tablero__tab" role="tab"
		        data-tab="mis-publicaciones" aria-controls="tab-mis-publicaciones" aria-selected="false">
			Mis publicaciones
		</button>
		<button class="tablero__tab" role="tab"
		        data-tab="notificaciones" aria-controls="tab-notificaciones" aria-selected="false">
			Notificaciones
			<span class="tab-badge" id="notif-badge" hidden></span>
		</button>
		<button class="tablero__tab" role="tab"
		        data-tab="agenda" aria-controls="tab-agenda" aria-selected="false">
			Agenda
		</button>
		<button class="tablero__tab" role="tab"
		        data-tab="perfil" aria-controls="tab-perfil" aria-selected="false">
			Mi Perfil
		</button>
	</nav>

	<div id="tab-mensajes" class="tablero__panel" role="tabpanel">
		<?php get_template_part( 'template-parts/tablero-mensajes' ); ?>
	</div>

	<div id="tab-mis-publicaciones" class="tablero__panel" role="tabpanel" hidden>
		<?php get_template_part( 'template-parts/tablero-mis-publicaciones' ); ?>
	</div>

	<div id="tab-notificaciones" class="tablero__panel" role="tabpanel" hidden>
		<?php get_template_part( 'template-parts/tablero-notificaciones' ); ?>
	</div>

	<div id="tab-agenda" class="tablero__panel" role="tabpanel" hidden>
		<?php get_template_part( 'template-parts/tablero-agenda' ); ?>
	</div>

	<div id="tab-perfil" class="tablero__panel" role="tabpanel" hidden>
		<?php get_template_part( 'template-parts/tablero-perfil' ); ?>
	</div>

</main>

<?php get_footer(); ?>
