<?php
/**
 * Tablero tab 1 — Admin messages (plataforma_notice CPT).
 */

defined( 'ABSPATH' ) || exit;

$notices = new WP_Query( [
	'post_type'      => 'plataforma_notice',
	'post_status'    => 'publish',
	'posts_per_page' => 20,
	'orderby'        => 'date',
	'order'          => 'DESC',
	'no_found_rows'  => true,
] );
?>
<section class="tablero-mensajes">
	<?php if ( $notices->have_posts() ) : ?>
		<?php while ( $notices->have_posts() ) : $notices->the_post(); ?>
			<article class="notice-card">
				<header class="notice-card__head">
					<h2 class="notice-card__title"><?php the_title(); ?></h2>
					<time class="notice-card__date"
					      datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>">
						<?php echo esc_html( get_the_date( 'j \d\e F \d\e Y' ) ); ?>
					</time>
				</header>
				<div class="notice-card__body"><?php the_content(); ?></div>
			</article>
		<?php endwhile; wp_reset_postdata(); ?>
	<?php else : ?>
		<div class="tablero-mensajes__empty">
			<p>No hay mensajes del administrador todavía. ¡Vuelve pronto!</p>
		</div>
	<?php endif; ?>
</section>
