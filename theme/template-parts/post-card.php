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
$cat_url    = $cat ? (string) get_term_meta( $cat->term_id, '_plataforma_category_url', true ) : '';
$is_event   = in_array( 'eventos', array_column( $categories, 'slug' ), true );
if ( $is_event && $cat_slug !== 'eventos' ) {
	foreach ( $categories as $_c ) {
		if ( $_c->slug === 'eventos' ) {
			$cat = $_c; $cat_slug = $_c->slug; $cat_name = $_c->name;
			$cat_url = (string) get_term_meta( $_c->term_id, '_plataforma_category_url', true );
			break;
		}
	}
}

$event_date     = $is_event ? (string) get_post_meta( $post_id, '_plataforma_event_date', true )     : '';
$event_location = $is_event ? (string) get_post_meta( $post_id, '_plataforma_event_location', true ) : '';

$card_classes = [ 'article-card' ];
if ( $is_event ) {
	$card_classes[] = 'article-card--event';
}
if ( has_post_thumbnail( $post_id ) ) {
	$card_classes[] = 'article-card--has-cover';
}
?>
<article
	class="<?php echo esc_attr( implode( ' ', $card_classes ) ); ?>"
	data-kind="<?php echo esc_attr( $cat_slug ); ?>"
	id="post-<?php the_ID(); ?>"
>
	<?php if ( has_post_thumbnail( $post_id ) ) : ?>
		<a class="article-card__cover-link" href="<?php the_permalink(); ?>" tabindex="-1" aria-hidden="true">
			<?php the_post_thumbnail( $is_event ? 'medium' : 'large', [ 'class' => 'article-card__cover' ] ); ?>
		</a>
	<?php endif; ?>

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
					<?php if ( $cat_url ) : ?>
						<a class="article-card__kind" href="<?php echo esc_url( $cat_url ); ?>"><?php echo esc_html( $cat_name ); ?></a>
					<?php else : ?>
						<span class="article-card__kind"><?php echo esc_html( $cat_name ); ?></span>
					<?php endif; ?>
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

	<?php if ( $is_event && ( $event_date || $event_location ) ) : ?>
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
	<?php endif; ?>

	<p class="article-card__excerpt"><?php echo esc_html( get_the_excerpt() ); ?></p>

	<?php
	// Build add-to-calendar-button config when this is an event with a date.
	$atcb_json = '';
	if ( $is_event && $event_date ) {
		$ts          = strtotime( $event_date );
		$tz_string   = wp_timezone_string() ?: 'Europe/Vienna';
		$atcb_config = [
			'name'        => get_the_title(),
			'description' => wp_strip_all_tags( get_the_excerpt() ),
			'startDate'   => wp_date( 'Y-m-d', $ts ),
			'startTime'   => wp_date( 'H:i',   $ts ),
			'endDate'     => wp_date( 'Y-m-d', $ts + HOUR_IN_SECONDS ),
			'endTime'     => wp_date( 'H:i',   $ts + HOUR_IN_SECONDS ),
			'timeZone'    => $tz_string,
			'listStyle'   => 'dropdown',
			'options'     => [ 'Apple', 'Google', 'iCal', 'Outlook.com' ],
			'iCalFileName' => sanitize_title( get_the_title() ) ?: 'evento',
		];
		if ( $event_location ) {
			$atcb_config['location'] = $event_location;
		}
		$atcb_json = wp_json_encode( $atcb_config );
	}
	?>
	<?php if ( $is_event && $event_date ) : ?>
		<button type="button" class="atcb-trigger" data-atcb="<?php echo esc_attr( $atcb_json ); ?>">
			<svg class="cal-icon" aria-hidden="true" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" width="20" height="20">
				<rect x="2" y="4" width="16" height="14" rx="2.5" stroke="#ffffff" stroke-width="1.6"/>
				<path d="M2 9h16" stroke="#ffffff" stroke-width="1.6"/>
				<path d="M6.5 2v4M13.5 2v4" stroke="#ffffff" stroke-width="1.6" stroke-linecap="round"/>
				<circle cx="6"  cy="12.5" r="0.8" fill="#ffffff"/>
				<circle cx="9"  cy="12.5" r="0.8" fill="#ffffff"/>
				<circle cx="12" cy="12.5" r="0.8" fill="#ffffff"/>
				<circle cx="6"  cy="15.5" r="0.8" fill="#ffffff"/>
				<circle cx="9"  cy="15.5" r="0.8" fill="#ffffff"/>
				<circle cx="12" cy="15.5" r="0.8" fill="#ffffff"/>
				<circle cx="19" cy="19"   r="4"   stroke="#ffffff" stroke-width="1.6"/>
				<path d="M19 16.8v4.4M16.8 19h4.4" stroke="#ffffff" stroke-width="1.6" stroke-linecap="round"/>
			</svg>
			<span>Añadir al calendario</span>
		</button>
	<?php endif; ?>

	<?php if ( ! $is_event ) :
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
			<?php
		endif;
	endif; ?>

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
