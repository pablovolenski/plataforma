<?php
/**
 * Dedicated full-page editor at /escribir/
 * Served via the rewrite rule registered in plataforma-social plugin.
 */

defined( 'ABSPATH' ) || exit;

// Redirect non-authors before any output
if ( ! is_user_logged_in() ) {
	wp_safe_redirect( wp_login_url( home_url( '/escribir/' ) ) );
	exit;
}
if ( ! current_user_can( 'publish_posts' ) ) {
	wp_safe_redirect( home_url( '/' ) );
	exit;
}

get_header();
?>

<main class="feed">
	<article class="article-single escribir-page">
		<h1 class="article-single__title escribir-page__title">Nueva publicación</h1>

		<div id="compose-notice" class="notice" hidden aria-live="polite"></div>

		<form id="article-form" class="article-form" novalidate>
			<?php get_template_part( 'template-parts/compose-fields' ); ?>

			<div class="escribir-page__actions">
				<a class="btn-ghost" href="<?php echo esc_url( home_url( '/' ) ); ?>">Cancelar</a>
				<button type="submit" class="btn-primary">Publicar en el muro</button>
			</div>
		</form>
	</article>
</main>

<?php get_footer(); ?>
