<?php
/**
 * Tablero tab — list of the current user's own posts with edit/delete actions.
 */

defined( 'ABSPATH' ) || exit;

$user_id = get_current_user_id();
$query   = new WP_Query( [
	'author'         => $user_id,
	'post_status'    => [ 'publish', 'draft', 'pending', 'private' ],
	'posts_per_page' => 50,
	'orderby'        => 'modified',
	'order'          => 'DESC',
] );
?>
<section class="mis-pubs">
	<header class="mis-pubs__header">
		<h2 class="mis-pubs__title">Mis publicaciones</h2>
		<a class="btn-primary mis-pubs__new" href="<?php echo esc_url( home_url( '/escribir/' ) ); ?>">
			+ Nueva publicación
		</a>
	</header>

	<div id="mis-pubs-notice" class="notice" hidden aria-live="polite"></div>

	<?php if ( ! $query->have_posts() ) : ?>
		<p class="mis-pubs__empty">Aún no has publicado nada. <a href="<?php echo esc_url( home_url( '/escribir/' ) ); ?>">Crea tu primera publicación →</a></p>
	<?php else : ?>
		<ul class="mis-pubs__list">
			<?php while ( $query->have_posts() ) : $query->the_post();
				$post_id    = get_the_ID();
				$cats       = get_the_category();
				$cat_name   = ! empty( $cats ) ? $cats[0]->name : '';
				$status     = get_post_status();
				$published  = get_the_date( 'j M Y, H:i' );
				$modified   = get_the_modified_date( 'j M Y, H:i' );
				$was_edited = get_the_modified_time( 'U' ) - get_the_time( 'U' ) > 60;
				$thumb_url  = has_post_thumbnail() ? get_the_post_thumbnail_url( $post_id, 'thumbnail' ) : '';
			?>
				<li class="mis-pubs__item" data-post-id="<?php echo esc_attr( $post_id ); ?>">
					<?php if ( $thumb_url ) : ?>
						<a class="mis-pubs__thumb" href="<?php the_permalink(); ?>"
						   style="background-image:url('<?php echo esc_url( $thumb_url ); ?>')" aria-hidden="true"></a>
					<?php else : ?>
						<div class="mis-pubs__thumb mis-pubs__thumb--empty" aria-hidden="true">📝</div>
					<?php endif; ?>

					<div class="mis-pubs__body">
						<div class="mis-pubs__meta">
							<?php if ( $cat_name ) : ?>
								<span class="mis-pubs__kind"><?php echo esc_html( $cat_name ); ?></span>
							<?php endif; ?>
							<?php if ( $status !== 'publish' ) : ?>
								<span class="mis-pubs__status mis-pubs__status--<?php echo esc_attr( $status ); ?>">
									<?php echo esc_html( ucfirst( $status ) ); ?>
								</span>
							<?php endif; ?>
						</div>
						<h3 class="mis-pubs__heading">
							<a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
						</h3>
						<p class="mis-pubs__dates">
							<span>Publicado: <?php echo esc_html( $published ); ?></span>
							<?php if ( $was_edited ) : ?>
								<span class="mis-pubs__dates-edit">· Editado: <?php echo esc_html( $modified ); ?></span>
							<?php endif; ?>
						</p>
					</div>

					<div class="mis-pubs__actions">
						<a class="btn-ghost mis-pubs__edit"
						   href="<?php echo esc_url( add_query_arg( 'edit', $post_id, home_url( '/escribir/' ) ) ); ?>">
							Editar
						</a>
						<button type="button" class="mis-pubs__delete" data-post-id="<?php echo esc_attr( $post_id ); ?>"
						        aria-label="Eliminar publicación">
							Eliminar
						</button>
					</div>
				</li>
			<?php endwhile; wp_reset_postdata(); ?>
		</ul>
	<?php endif; ?>
</section>
