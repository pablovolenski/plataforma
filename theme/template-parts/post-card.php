<?php
/**
 * Template part: article card.
 * Must be called inside a WP_Query loop (the_post() already invoked).
 */

defined( 'ABSPATH' ) || exit;

$post_id    = get_the_ID();
$liked      = plataforma_user_has_liked( $post_id );
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
	<header class="article-card__head">
		<a class="article-card__avatar-link" href="<?php echo esc_url( get_author_posts_url( get_the_author_meta( 'ID' ) ) ); ?>" aria-hidden="true" tabindex="-1">
			<?php echo get_avatar( get_the_author_meta( 'ID' ), 40, '', '', [ 'class' => 'article-card__avatar' ] ); ?>
		</a>
		<div class="article-card__byline">
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
			</div>
		</div>
	</header>

	<h3 class="article-card__title">
		<a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
	</h3>

	<p class="article-card__excerpt"><?php echo esc_html( get_the_excerpt() ); ?></p>

	<?php if ( $cat_slug === 'eventos' ) :
		$event_date     = (string) get_post_meta( $post_id, '_plataforma_event_date', true );
		$event_location = (string) get_post_meta( $post_id, '_plataforma_event_location', true );
		if ( $event_date || $event_location ) :
	?>
	<div class="event-meta-strip">
		<?php if ( $event_date ) : ?>
			<span class="event-meta-strip__item">
				<span class="event-meta-strip__icon" aria-hidden="true">📅</span>
				<?php echo esc_html( date_i18n( 'j \d\e F, H:i', strtotime( $event_date ) ) ); ?>
			</span>
		<?php endif; ?>
		<?php if ( $event_location ) : ?>
			<span class="event-meta-strip__item">
				<span class="event-meta-strip__icon" aria-hidden="true">📍</span>
				<?php echo esc_html( $event_location ); ?>
			</span>
		<?php endif; ?>
	</div>
	<div class="cal-dropdown">
		<button type="button" class="cal-dropdown__toggle" aria-expanded="false" aria-haspopup="true">
			<span aria-hidden="true">🗓</span> Agregar al calendario
		</button>
		<div class="cal-dropdown__menu" hidden>
			<?php if ( function_exists( 'plataforma_google_calendar_url' ) ) : ?>
			<a class="cal-dropdown__item" href="<?php echo esc_url( plataforma_google_calendar_url( $post_id ) ); ?>" target="_blank" rel="noopener noreferrer">
				Google Calendar
			</a>
			<?php endif; ?>
			<a class="cal-dropdown__item" href="<?php echo esc_url( add_query_arg( 'plataforma_ical', $post_id, home_url( '/' ) ) ); ?>">
				iCal / Apple Calendar
			</a>
			<a class="cal-dropdown__item" href="<?php echo esc_url( add_query_arg( 'plataforma_ical', $post_id, home_url( '/' ) ) ); ?>">
				Outlook
			</a>
		</div>
	</div>
	<?php endif; endif; ?>

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

	<footer class="article-card__footer">
		<button
			class="like-btn<?php echo $liked ? ' is-liked' : ''; ?>"
			data-post-id="<?php echo esc_attr( $post_id ); ?>"
			aria-label="<?php echo $liked ? 'Quitar me gusta' : 'Me gusta'; ?>"
			aria-pressed="<?php echo $liked ? 'true' : 'false'; ?>"
			type="button"
		>
			<span class="like-heart" aria-hidden="true">♥</span>
			<span class="like-count"><?php echo esc_html( $like_count ); ?></span>
		</button>

		<?php get_template_part( 'template-parts/share-bar', null, [ 'compact' => true ] ); ?>

		<a class="article-card__read-more" href="<?php the_permalink(); ?>">
			Leer más →
		</a>
	</footer>
</article>
