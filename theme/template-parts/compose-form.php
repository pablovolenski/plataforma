<?php
/**
 * Compose bar (Facebook-style top strip) + modal dialog with full form.
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
		aria-haspopup="dialog"
		aria-controls="composer-modal"
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
			aria-haspopup="dialog"
			aria-controls="composer-modal"
		>
			<span class="composer-bar__event-icon" aria-hidden="true">📅</span>
			<span>Añadir evento</span>
		</button>
	<?php endif; ?>
</div>

<dialog class="composer-modal" id="composer-modal" aria-labelledby="composer-modal-title">
	<div class="composer-modal__inner">
		<div class="composer-modal__head">
			<h2 id="composer-modal-title" class="composer-modal__title">Nueva publicación</h2>
			<button type="button" class="composer-modal__close" data-action="close-composer" aria-label="Cerrar">×</button>
		</div>

		<div id="compose-notice" class="notice" hidden aria-live="polite"></div>

		<form id="article-form" class="article-form" novalidate>
			<?php wp_nonce_field( 'plataforma_post_nonce', '_wpnonce' ); ?>
			<input type="hidden" name="plataforma_post_action" value="submit_article">

			<label>
				Categoría
				<select name="post_category" required>
					<option value="">— Elegir —</option>
					<?php foreach ( $cats as $cat ) : ?>
						<option value="<?php echo esc_attr( $cat->term_id ); ?>" data-slug="<?php echo esc_attr( $cat->slug ); ?>">
							<?php echo esc_html( $cat->name ); ?>
						</option>
					<?php endforeach; ?>
				</select>
			</label>

			<label>
				Título
				<input
					type="text"
					name="post_title"
					maxlength="90"
					placeholder="Escribe un titular"
					required
					autocomplete="off"
				>
				<span class="char-counter" data-target="post_title" data-max="90">0 / 90</span>
			</label>

			<div class="editor-wrap">
				<div class="editor-toolbar" id="editor-toolbar" role="toolbar" aria-label="Formato de texto">
					<button type="button" class="editor-btn" data-cmd="bold"       title="Negrita (Ctrl+B)"><b>B</b></button>
					<button type="button" class="editor-btn" data-cmd="italic"     title="Cursiva (Ctrl+I)"><i>I</i></button>
					<button type="button" class="editor-btn" data-cmd="createLink" title="Insertar enlace">🔗</button>
					<span class="editor-btn--separator" aria-hidden="true"></span>
					<label class="editor-btn editor-btn--file" title="Adjuntar imagen">
						📎
						<input type="file" id="compose-image-input" accept="image/jpeg,image/png,image/gif,image/webp" hidden>
					</label>
				</div>
				<div
					id="compose-editor"
					class="compose-editor"
					contenteditable="true"
					data-placeholder="Escribe tu publicación…"
					aria-label="Cuerpo de la publicación"
					role="textbox"
					aria-multiline="true"
				></div>
				<input type="hidden" name="post_content" id="compose-content-hidden">
			</div>

			<div id="link-preview-container" class="link-preview-container" hidden></div>
			<input type="hidden" name="link_preview" id="compose-link-preview-data">

			<div class="composer-modal__actions">
				<button type="button" class="btn-ghost" data-action="close-composer">Cancelar</button>
				<button type="submit" class="btn-primary">Publicar</button>
			</div>
		</form>
	</div>
</dialog>
