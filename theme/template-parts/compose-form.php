<?php
/**
 * Template part: inline compose panel.
 * Only rendered when current_user_can('publish_posts') is true (gated in index.php).
 */

defined( 'ABSPATH' ) || exit;

$cats = get_categories( [ 'hide_empty' => false, 'orderby' => 'name' ] );
?>
<section class="panel composer" id="composer">
	<div class="panel__heading">
		<p class="eyebrow">Mesa de redacción</p>
		<h2>Escribir artículo</h2>
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
					<option value="<?php echo esc_attr( $cat->term_id ); ?>">
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

		<label>
			Resumen
			<textarea
				name="post_excerpt"
				rows="3"
				maxlength="180"
				placeholder="Un párrafo de resumen"
				required
			></textarea>
			<span class="char-counter" data-target="post_excerpt" data-max="180">0 / 180</span>
		</label>

		<label>
			Cuerpo del artículo
			<textarea
				name="post_content"
				rows="8"
				placeholder="Escribe el artículo completo"
				required
			></textarea>
		</label>

		<button type="submit">Publicar en el muro</button>
	</form>
</section>
