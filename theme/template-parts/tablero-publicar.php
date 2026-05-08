<?php
/**
 * Tablero tab 2 — Compose / publish a new post.
 */

defined( 'ABSPATH' ) || exit;

if ( ! current_user_can( 'publish_posts' ) ) : ?>
	<div class="tablero-publicar__noperm">
		<p>Tu cuenta aún no tiene permiso para publicar. Contacta al administrador para solicitar el rol de Autor.</p>
	</div>
<?php else : ?>
	<div id="compose-notice" class="notice" hidden aria-live="polite"></div>

	<form id="article-form" class="article-form" novalidate>
		<?php get_template_part( 'template-parts/compose-fields' ); ?>

		<div class="tablero-publicar__actions">
			<button type="submit" class="btn-primary">Publicar en el muro</button>
		</div>
	</form>
<?php endif; ?>
