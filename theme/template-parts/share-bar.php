<?php
/**
 * Share bar template part.
 * Usage:
 *   get_template_part( 'template-parts/share-bar', null, [ 'compact' => true ] );
 *
 * Modes:
 *   compact  → single button (Web Share API on mobile, dropdown on desktop)
 *   full     → inline row of all share buttons (used on single post pages)
 */

defined( 'ABSPATH' ) || exit;

$args     = isset( $args ) && is_array( $args ) ? $args : [];
$compact  = ! empty( $args['compact'] );

$post_id  = get_the_ID();
$url      = get_permalink( $post_id );
$title    = get_the_title( $post_id );
$excerpt  = wp_strip_all_tags( get_the_excerpt( $post_id ) );

// Direct share URLs (each opens in a new window)
$share = [
	'whatsapp' => 'https://wa.me/?text=' . rawurlencode( $title . ' ' . $url ),
	'facebook' => 'https://www.facebook.com/sharer/sharer.php?u=' . rawurlencode( $url ),
	'twitter'  => 'https://twitter.com/intent/tweet?url=' . rawurlencode( $url ) . '&text=' . rawurlencode( $title ),
	'linkedin' => 'https://www.linkedin.com/sharing/share-offsite/?url=' . rawurlencode( $url ),
	'telegram' => 'https://t.me/share/url?url=' . rawurlencode( $url ) . '&text=' . rawurlencode( $title ),
	'email'    => 'mailto:?subject=' . rawurlencode( $title ) . '&body=' . rawurlencode( $excerpt . "\n\n" . $url ),
];

// SVG icons (24x24 viewBox)
$icons = [
	'whatsapp' => '<svg viewBox="0 0 24 24" width="18" height="18" fill="currentColor" aria-hidden="true"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448L.057 24zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981zm11.387-5.464c-.074-.124-.272-.198-.57-.347-.297-.149-1.758-.868-2.031-.967-.272-.099-.47-.149-.669.149-.198.297-.768.967-.941 1.165-.173.198-.347.223-.644.074-.297-.149-1.255-.462-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.521.151-.172.2-.296.3-.495.099-.198.05-.372-.025-.521-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.501-.669-.51l-.57-.01c-.198 0-.52.074-.792.372s-1.04 1.016-1.04 2.479 1.065 2.876 1.213 3.074c.149.198 2.095 3.2 5.076 4.487.709.306 1.263.489 1.694.626.712.226 1.36.194 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.695.248-1.29.173-1.414z"/></svg>',
	'facebook' => '<svg viewBox="0 0 24 24" width="18" height="18" fill="currentColor" aria-hidden="true"><path d="M12 2C6.477 2 2 6.477 2 12c0 4.991 3.657 9.128 8.438 9.879V14.89h-2.54V12h2.54V9.797c0-2.506 1.492-3.89 3.777-3.89 1.094 0 2.238.195 2.238.195v2.46h-1.26c-1.243 0-1.63.771-1.63 1.562V12h2.773l-.443 2.89h-2.33v6.989C18.343 21.129 22 16.991 22 12c0-5.523-4.477-10-10-10z"/></svg>',
	'twitter'  => '<svg viewBox="0 0 24 24" width="18" height="18" fill="currentColor" aria-hidden="true"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231 5.45-6.231zm-1.161 17.52h1.833L7.084 4.126H5.117l11.966 15.644z"/></svg>',
	'linkedin' => '<svg viewBox="0 0 24 24" width="18" height="18" fill="currentColor" aria-hidden="true"><path d="M19 0h-14c-2.761 0-5 2.239-5 5v14c0 2.761 2.239 5 5 5h14c2.762 0 5-2.239 5-5V5c0-2.761-2.238-5-5-5zM8 19H5V8h3v11zM6.5 6.732c-.966 0-1.75-.79-1.75-1.764s.784-1.764 1.75-1.764 1.75.79 1.75 1.764-.783 1.764-1.75 1.764zM20 19h-3v-5.604c0-3.368-4-3.113-4 0V19h-3V8h3v1.765c1.396-2.586 7-2.777 7 2.476V19z"/></svg>',
	'telegram' => '<svg viewBox="0 0 24 24" width="18" height="18" fill="currentColor" aria-hidden="true"><path d="M12 2C6.477 2 2 6.477 2 12s4.477 10 10 10 10-4.477 10-10S17.523 2 12 2zm4.64 6.8c-.15 1.58-.8 5.42-1.13 7.19-.14.75-.42 1-.68 1.03-.58.05-1.02-.38-1.58-.75-.88-.58-1.38-.94-2.23-1.5-.99-.65-.35-1.01.22-1.59.15-.15 2.71-2.48 2.76-2.69.01-.03.01-.13-.06-.18-.07-.05-.18-.04-.25-.02-.11.02-1.85 1.18-5.22 3.46-.49.34-.94.51-1.34.5-.44-.01-1.29-.25-1.92-.46-.78-.25-1.39-.39-1.34-.83.03-.22.34-.45.93-.68 3.65-1.59 6.08-2.64 7.29-3.15 3.48-1.45 4.21-1.7 4.68-1.71.1 0 .34.02.49.15.13.11.16.26.18.36-.01.07.01.27 0 .44z"/></svg>',
	'email'    => '<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>',
	'copy'     => '<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg>',
	'share'    => '<svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><line x1="8.59" y1="13.51" x2="15.42" y2="17.49"/><line x1="15.41" y1="6.51" x2="8.59" y2="10.49"/></svg>',
];

$labels = [
	'whatsapp' => 'WhatsApp',
	'facebook' => 'Facebook',
	'twitter'  => 'X',
	'linkedin' => 'LinkedIn',
	'telegram' => 'Telegram',
	'email'    => 'Email',
];
?>

<?php if ( $compact ) : ?>

	<div class="share-dropdown" data-share-url="<?php echo esc_attr( $url ); ?>" data-share-title="<?php echo esc_attr( $title ); ?>">
		<button
			type="button"
			class="share-btn share-btn--toggle"
			aria-expanded="false"
			aria-haspopup="menu"
			aria-label="Compartir"
		>
			<?php echo $icons['share']; // phpcs:ignore ?>
			<span>Compartir</span>
		</button>

		<div class="share-dropdown__menu" role="menu" hidden>
			<?php foreach ( [ 'whatsapp', 'facebook', 'twitter', 'linkedin', 'email' ] as $platform ) : ?>
				<a class="share-dropdown__item share-dropdown__item--<?php echo esc_attr( $platform ); ?>" href="<?php echo esc_url( $share[ $platform ] ); ?>" target="_blank" rel="noopener" role="menuitem">
					<?php echo $icons[ $platform ]; // phpcs:ignore ?>
					<span><?php echo esc_html( $labels[ $platform ] ); ?></span>
				</a>
			<?php endforeach; ?>
			<button type="button" class="share-dropdown__item share-dropdown__item--copy" data-share-copy role="menuitem">
				<?php echo $icons['copy']; // phpcs:ignore ?>
				<span>Copiar enlace</span>
			</button>
		</div>
	</div>

<?php else : ?>

	<div class="share-row" data-share-url="<?php echo esc_attr( $url ); ?>" data-share-title="<?php echo esc_attr( $title ); ?>">
		<span class="share-row__label">Compartir:</span>
		<?php foreach ( [ 'whatsapp', 'facebook', 'twitter', 'linkedin', 'telegram', 'email' ] as $platform ) : ?>
			<a
				class="share-pill share-pill--<?php echo esc_attr( $platform ); ?>"
				href="<?php echo esc_url( $share[ $platform ] ); ?>"
				target="_blank"
				rel="noopener"
				aria-label="Compartir en <?php echo esc_attr( $labels[ $platform ] ); ?>"
				title="<?php echo esc_attr( $labels[ $platform ] ); ?>"
			>
				<?php echo $icons[ $platform ]; // phpcs:ignore ?>
			</a>
		<?php endforeach; ?>
		<button
			type="button"
			class="share-pill share-pill--copy"
			data-share-copy
			aria-label="Copiar enlace"
			title="Copiar enlace"
		>
			<?php echo $icons['copy']; // phpcs:ignore ?>
		</button>
	</div>

<?php endif; ?>
