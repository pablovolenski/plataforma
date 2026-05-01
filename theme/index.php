<?php
/**
 * Main feed: full-width wall with compose bar at top (publishers only).
 * Filters are generated dynamically from WordPress categories.
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>

<main class="feed">

	<?php if ( current_user_can( 'publish_posts' ) ) : ?>
		<?php get_template_part( 'template-parts/compose-form' ); ?>
	<?php endif; ?>

	<section class="wall" aria-label="Muro público">
		<div class="wall__filters" id="filters" role="group" aria-label="Filtrar por categoría">
			<button class="filter-chip is-active" data-filter="all">Todo</button>
			<?php
			$filter_cats = get_categories( [
				'hide_empty' => false,
				'orderby'    => 'name',
				'order'      => 'ASC',
			] );
			foreach ( $filter_cats as $fcat ) :
				?>
				<button class="filter-chip" data-filter="<?php echo esc_attr( $fcat->slug ); ?>">
					<?php echo esc_html( $fcat->name ); ?>
				</button>
				<?php
			endforeach;
			?>
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
			else :
				?>
				<div class="empty-state">Aún no hay publicaciones.</div>
				<?php
			endif;
			?>
		</div>
	</section>

</main>

<?php get_footer(); ?>
