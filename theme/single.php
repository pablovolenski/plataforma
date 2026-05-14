<?php
/**
 * Single post view: full article with likes button.
 */

defined( 'ABSPATH' ) || exit;

get_header();

if ( have_posts() ) {
	the_post();
}

$post_id    = get_the_ID();
$liked      = plataforma_user_has_liked( $post_id );
$like_count = plataforma_like_count( $post_id );
$categories = get_the_category();
$cat        = ! empty( $categories ) ? $categories[0] : null;
$cat_name   = $cat ? $cat->name : '';
?>

<main class="feed feed--single">
	<article class="article-single">

		<header class="article-single__head">
			<a class="article-card__avatar-link" href="<?php echo esc_url( get_author_posts_url( get_the_author_meta( 'ID' ) ) ); ?>" aria-hidden="true" tabindex="-1">
				<?php echo get_avatar( get_the_author_meta( 'ID' ), 48, '', '', [ 'class' => 'article-card__avatar' ] ); ?>
			</a>
			<div>
				<a class="article-card__author" href="<?php echo esc_url( get_author_posts_url( get_the_author_meta( 'ID' ) ) ); ?>">
					<?php the_author(); ?>
				</a>
				<div class="article-card__meta">
					<?php if ( $cat_name ) : ?>
						<span class="article-card__kind"><?php echo esc_html( $cat_name ); ?></span>
					<?php endif; ?>
					<time datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>">
						<?php echo esc_html( get_the_date( 'j M Y, H:i' ) ); ?>
					</time>
					<?php if ( get_the_modified_time( 'U' ) - get_the_time( 'U' ) > 60 ) : ?>
						<span class="article-card__edited">
							· Editado <time datetime="<?php echo esc_attr( get_the_modified_date( 'c' ) ); ?>">
								<?php echo esc_html( get_the_modified_date( 'j M Y, H:i' ) ); ?>
							</time>
						</span>
					<?php endif; ?>
				</div>
			</div>
		</header>

		<h1 class="article-single__title"><?php the_title(); ?></h1>

		<?php if ( has_excerpt() ) : ?>
			<p class="article-single__summary"><?php the_excerpt(); ?></p>
		<?php endif; ?>

		<div class="article-single__body">
			<?php the_content(); ?>
		</div>

		<?php
		$lp = get_post_meta( $post_id, '_plataforma_link_preview', true );
		if ( is_array( $lp ) && ! empty( $lp['title'] ) ) :
			$lp_domain = ! empty( $lp['url'] ) ? (string) wp_parse_url( $lp['url'], PHP_URL_HOST ) : '';
			?>
			<a class="article-link-preview link-preview-card" href="<?php echo esc_url( $lp['url'] ); ?>" target="_blank" rel="noopener noreferrer">
				<?php if ( ! empty( $lp['image'] ) ) : ?>
					<img class="link-preview-card__img" src="<?php echo esc_url( $lp['image'] ); ?>" alt="" loading="lazy">
				<?php endif; ?>
				<div class="link-preview-card__body">
					<div class="link-preview-card__title"><?php echo esc_html( $lp['title'] ); ?></div>
					<?php if ( ! empty( $lp['description'] ) ) : ?>
						<div class="link-preview-card__desc"><?php echo esc_html( $lp['description'] ); ?></div>
					<?php endif; ?>
					<?php if ( $lp_domain ) : ?>
						<div class="link-preview-card__domain"><?php echo esc_html( $lp_domain ); ?></div>
					<?php endif; ?>
				</div>
			</a>
		<?php endif; ?>

		<div class="like-section">
			<button
				class="like-btn<?php echo $liked ? ' is-liked' : ''; ?>"
				data-post-id="<?php echo esc_attr( $post_id ); ?>"
				aria-label="<?php echo $liked ? 'Quitar me gusta' : 'Me gusta'; ?>"
				aria-pressed="<?php echo $liked ? 'true' : 'false'; ?>"
				type="button"
			>
				<span class="like-heart" aria-hidden="true">♥</span>
				<span class="like-count"><?php echo esc_html( $like_count ); ?></span>
				<span>Me gusta</span>
			</button>
		</div>

		<?php get_template_part( 'template-parts/share-bar', null, [ 'compact' => false ] ); ?>

		<a class="back-link" href="<?php echo esc_url( home_url( '/' ) ); ?>">
			← Volver al muro
		</a>

	</article>
</main>

<?php get_footer(); ?>
