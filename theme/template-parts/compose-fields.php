<?php
/**
 * Shared post form fields — used by both the compose modal and /escribir/ page.
 * Must be wrapped in <form id="article-form" class="article-form"> by the caller.
 */

defined( 'ABSPATH' ) || exit;

$cats = get_categories( [ 'hide_empty' => false, 'orderby' => 'name' ] );
?>
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

<div id="event-fields" class="event-fields" hidden>
	<label>
		Cuándo
		<input type="datetime-local" name="event_date" id="event-date">
	</label>
	<label>
		Dónde
		<input type="text" name="event_location" id="event-location"
		       placeholder="Lugar del evento…" maxlength="200" autocomplete="off">
	</label>
</div>

<div class="cover-upload">
	<div class="cover-upload__preview" id="cover-preview" hidden></div>
	<div class="cover-upload__controls">
		<label class="btn-ghost cover-upload__btn" for="cover-image-input">
			<span aria-hidden="true">🖼</span>
			<span class="cover-upload__label-text">Añadir imagen de portada</span>
			<input type="file" id="cover-image-input"
			       accept="image/jpeg,image/png,image/gif,image/webp" hidden>
		</label>
		<button type="button" class="cover-upload__remove" id="cover-remove" hidden>
			Quitar
		</button>
	</div>
	<input type="hidden" name="cover_image_id" id="cover-image-id" value="">
</div>

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
