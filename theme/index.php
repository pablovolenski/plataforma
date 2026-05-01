<?php
/**
 * Main feed template: public wall + inline compose for autores.
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>

<main class="layout">

	<?php if ( current_user_can( 'publish_posts' ) ) : ?>
		<?php get_template_part( 'template-parts/compose-form' ); ?>
	<?php else : ?>
		<section class="panel composer">
			<div class="panel__heading">
				<p class="eyebrow">Red de publicación</p>
				<h2>Plataforma</h2>
			</div>
			<div class="access-note">
				<strong>Acceso:</strong>
				Las cuentas son asignadas por los administradores del Verein.
				<?php if ( ! is_user_logged_in() ) : ?>
					<br><a href="<?php echo esc_url( wp_login_url() ); ?>">Ingresar</a>
				<?php endif; ?>
			</div>
		</section>
	<?php endif; ?>

	<section class="panel wall">
		<div class="wall__header">
			<div class="panel__heading">
				<p class="eyebrow">Muro público</p>
				<h2>Publicaciones</h2>
			</div>

			<div class="filters" id="filters" role="group" aria-label="Filtrar por categoría">
				<button class="filter-chip is-active" data-filter="all">Todo</button>
				<button class="filter-chip" data-filter="opinion">Opinión</button>
				<button class="filter-chip" data-filter="noticias">Noticias</button>
				<button class="filter-chip" data-filter="eventos">Eventos</button>
			</div>
		</div>

		<div id="articles" class="articles" aria-live="polite">
			<?php
			$plataforma_query = new WP_Query( [
				'post_type'      => 'post',
				'post_status'    => 'publish',
				'posts_per_page' => 20,
				'orderby'        => 'date',
				'order'          => 'DESC',
			] );

			if ( $plataforma_query->have_posts() ) :
				while ( $plataforma_query->have_posts() ) :
					$plataforma_query->the_post();
					get_template_part( 'template-parts/post-card' );
				endwhile;
				wp_reset_postdata();
			else : ?>
				<div class="empty-state">Aún no hay publicaciones.</div>
			<?php endif; ?>
		</div>
	</section>

</main>

<?php get_footer(); ?>
