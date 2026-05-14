<?php
/**
 * Compose bar (Facebook-style top strip) + inline expanding panel with full form.
 * Only rendered for users with publish_posts capability (gated in index.php).
 */

defined( 'ABSPATH' ) || exit;

$current_user = wp_get_current_user();
$cats         = get_categories( [ 'hide_empty' => false, 'orderby' => 'name' ] );

// Find the Eventos category id for the highlighted button preset
$eventos_id = 0;
foreach ( $cats as $cat ) {
	if ( $cat->slug === 'eventos' ) {
		$eventos_id = (int) $cat->term_id;
		break;
	}
}
?>
<div class="composer-bar" id="composer-bar">
	<button
		type="button"
		class="composer-bar__input-trigger"
		data-action="open-composer"
		aria-haspopup="true"
		aria-expanded="false"
		aria-controls="composer-panel"
	>
		<span class="composer-bar__avatar" aria-hidden="true">
			<?php echo get_avatar( $current_user->ID, 40, '', '', [ 'class' => 'composer-bar__avatar-img' ] ); ?>
		</span>
		<span class="composer-bar__placeholder">Reflexión rápida…</span>
	</button>

	<?php if ( $eventos_id ) : ?>
		<button
			type="button"
			class="composer-bar__event-btn"
			data-action="open-composer"
			data-category="<?php echo esc_attr( $eventos_id ); ?>"
			aria-haspopup="true"
			aria-expanded="false"
			aria-controls="composer-panel"
		>
			<svg class="cal-icon" aria-hidden="true" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" width="18" height="18">
				<rect x="2" y="4" width="20" height="17" rx="2.5" stroke="#ffffff" stroke-width="1.6"/>
				<path d="M2 9h20" stroke="#ffffff" stroke-width="1.6"/>
				<path d="M7 2v4M17 2v4" stroke="#ffffff" stroke-width="1.6" stroke-linecap="round"/>
				<circle cx="8"  cy="13.5" r="0.9" fill="#ffffff"/>
				<circle cx="12" cy="13.5" r="0.9" fill="#ffffff"/>
				<circle cx="16" cy="13.5" r="0.9" fill="#ffffff"/>
				<circle cx="8"  cy="17.5" r="0.9" fill="#ffffff"/>
				<circle cx="12" cy="17.5" r="0.9" fill="#ffffff"/>
			</svg>
			<span>Añadir evento</span>
		</button>
	<?php endif; ?>
</div>

<div class="composer-panel" id="composer-panel" aria-labelledby="composer-panel-title">
	<div class="composer-panel__inner">
		<div class="composer-panel__head">
			<h2 id="composer-panel-title" class="composer-panel__title">Nueva publicación</h2>
			<button type="button" class="composer-panel__close" data-action="close-composer" aria-label="Cerrar">×</button>
		</div>

		<div id="compose-notice" class="notice" hidden aria-live="polite"></div>

		<form id="article-form" class="article-form" novalidate>
			<?php get_template_part( 'template-parts/compose-fields' ); ?>

			<div class="composer-panel__actions">
				<button type="button" class="btn-ghost" data-action="close-composer">Cancelar</button>
				<button type="submit" class="btn-primary">Publicar</button>
			</div>
		</form>
	</div>
</div>
