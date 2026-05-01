<?php
/**
 * Template part: article card.
 * Must be called inside a WP_Query loop (the_post() already invoked).
 */

defined( 'ABSPATH' ) || exit;

$post_id    = get_the_ID();
$user_id    = get_current_user_id();
$liked      = plataforma_user_has_liked( $post_id, $user_id );
$like_count = plataforma_like_count( $post_id );
$categories = get_the_category();
$cat        = ! empty( $categories ) ? $categories[0] : null;
$cat_slug   = $cat ? $cat->slug : '';
$cat_name   = $cat ? $cat->name : '';
?>
<article
	class="article-card"
	data-kind="<?php echo esc_attr( $cat_slug ); ?>"
	id="post-<?php the_ID(); ?>"
>
	<div class="article-card__meta">
		<?php if ( $cat_name ) : ?>
			<span class="article-card__kind"><?php echo esc_html( $cat_name ); ?></span>
		<?php endif; ?>
		<a href="<?php echo esc_url( get_author_posts_url( get_the_author_meta( 'ID' ) ) ); ?>">
			<?php the_author(); ?>
		</a>
		<time datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>">
			<?php echo esc_html( get_the_date( 'j. F Y, H:i' ) ); ?>
		</time>
	</div>

	<h3><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>

	<p><?php echo esc_html( get_the_excerpt() ); ?></p>

	<div class="article-card__footer">
		<button
			class="like-btn<?php echo $liked ? ' is-liked' : ''; ?>"
			data-post-id="<?php echo esc_attr( $post_id ); ?>"
			aria-label="<?php echo $liked ? 'Quitar me gusta' : 'Me gusta'; ?>"
			aria-pressed="<?php echo $liked ? 'true' : 'false'; ?>"
		>
			<span class="like-heart" aria-hidden="true">♥</span>
			<span class="like-count"><?php echo esc_html( $like_count ); ?></span>
		</button>

		<a class="article-card__read-more" href="<?php the_permalink(); ?>">
			Leer más →
		</a>
	</div>
</article>
