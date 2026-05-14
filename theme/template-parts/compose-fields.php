<?php
/**
 * Shared post form fields — used by both the compose modal and /escribir/ page.
 * Must be wrapped in <form id="article-form" class="article-form"> by the caller.
 *
 * When editing, the global $plataforma_edit_post (WP_Post) is populated by the
 * caller and the form is pre-filled.
 */

defined( 'ABSPATH' ) || exit;

$cats        = get_categories( [ 'hide_empty' => false, 'orderby' => 'name' ] );
$edit_post   = isset( $GLOBALS['plataforma_edit_post'] ) && $GLOBALS['plataforma_edit_post'] instanceof WP_Post
	? $GLOBALS['plataforma_edit_post'] : null;
$edit_id     = $edit_post ? (int) $edit_post->ID : 0;
$edit_cats   = $edit_post ? wp_get_post_categories( $edit_id ) : [];
$edit_cat    = $edit_cats ? (int) $edit_cats[0] : 0;
$edit_title  = $edit_post ? $edit_post->post_title   : '';
$edit_body   = $edit_post ? $edit_post->post_content : '';
$edit_thumb  = $edit_id ? (int) get_post_thumbnail_id( $edit_id ) : 0;
$edit_thumb_url = $edit_thumb ? wp_get_attachment_image_url( $edit_thumb, 'large' ) : '';
$edit_evdate = $edit_id ? (string) get_post_meta( $edit_id, '_plataforma_event_date',     true ) : '';
$edit_evloc  = $edit_id ? (string) get_post_meta( $edit_id, '_plataforma_event_location', true ) : '';
$edit_ev_d   = $edit_evdate ? date( 'Y-m-d', strtotime( $edit_evdate ) ) : '';
$edit_ev_t   = $edit_evdate ? date( 'H:i',   strtotime( $edit_evdate ) ) : '';
?>
<?php wp_nonce_field( 'plataforma_post_nonce', '_wpnonce' ); ?>
<input type="hidden" name="plataforma_post_action" value="submit_article">
<?php if ( $edit_id ) : ?>
	<input type="hidden" name="post_id" value="<?php echo esc_attr( $edit_id ); ?>">
<?php endif; ?>

<label>
	Categoría
	<select name="post_category" required>
		<option value="">— Elegir —</option>
		<?php foreach ( $cats as $cat ) : ?>
			<option value="<?php echo esc_attr( $cat->term_id ); ?>"
			        data-slug="<?php echo esc_attr( $cat->slug ); ?>"
			        <?php selected( $edit_cat, $cat->term_id ); ?>>
				<?php echo esc_html( $cat->name ); ?>
			</option>
		<?php endforeach; ?>
	</select>
</label>

<div id="event-fields" class="event-fields" hidden>
	<div class="event-fields__datetime">
		<label>
			Fecha
			<input type="date" name="event_date_date" id="event-date-date" value="<?php echo esc_attr( $edit_ev_d ); ?>">
		</label>
		<label>
			Hora
			<input type="time" name="event_date_time" id="event-date-time" step="300" value="<?php echo esc_attr( $edit_ev_t ); ?>">
		</label>
	</div>
	<label>
		Dónde
		<div class="event-location-wrap">
			<input type="text" name="event_location" id="event-location"
			       value="<?php echo esc_attr( $edit_evloc ); ?>"
			       placeholder="Nombre del lugar o dirección…" maxlength="300" autocomplete="off"
			       role="combobox" aria-autocomplete="list" aria-expanded="false">
			<ul id="event-location-suggestions" class="location-suggestions" hidden role="listbox"></ul>
		</div>
	</label>
</div>

<div class="cover-upload <?php echo $edit_thumb_url ? 'cover-upload--has-image' : ''; ?>" id="cover-upload">
	<div class="cover-upload__preview" id="cover-preview"
	     <?php if ( ! $edit_thumb_url ) echo 'hidden'; ?>
	     <?php if ( $edit_thumb_url ) : ?>style="background-image:url('<?php echo esc_url( $edit_thumb_url ); ?>')"<?php endif; ?>></div>
	<div class="cover-upload__placeholder" id="cover-placeholder" <?php if ( $edit_thumb_url ) echo 'hidden'; ?>>
		<div class="cover-upload__placeholder-icon" aria-hidden="true">🖼️</div>
		<div class="cover-upload__placeholder-title">Añade una imagen de portada</div>
		<div class="cover-upload__placeholder-hint">Arrástrala aquí o haz clic para seleccionar (JPG, PNG, GIF, WebP)</div>
	</div>
	<div class="cover-upload__controls">
		<label class="btn-ghost cover-upload__btn" for="cover-image-input">
			<span aria-hidden="true">🖼</span>
			<span class="cover-upload__label-text"><?php echo $edit_thumb_url ? 'Cambiar imagen' : 'Elegir imagen'; ?></span>
			<input type="file" id="cover-image-input"
			       accept="image/jpeg,image/png,image/gif,image/webp" hidden>
		</label>
		<button type="button" class="cover-upload__remove" id="cover-remove" <?php if ( ! $edit_thumb_url ) echo 'hidden'; ?>>
			Quitar
		</button>
	</div>
	<input type="hidden" name="cover_image_id" id="cover-image-id" value="<?php echo esc_attr( $edit_thumb ?: '' ); ?>">
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
		value="<?php echo esc_attr( $edit_title ); ?>"
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
	><?php echo wp_kses_post( $edit_body ); ?></div>
	<input type="hidden" name="post_content" id="compose-content-hidden" value="<?php echo esc_attr( $edit_body ); ?>">
</div>

<div id="link-preview-container" class="link-preview-container" hidden></div>
<input type="hidden" name="link_preview" id="compose-link-preview-data">
