<?php
/**
 * Comments template.
 */

defined( 'ABSPATH' ) || exit;

if ( post_password_required() ) {
	return;
}
?>
<section id="comments" class="comments-section">

	<?php if ( have_comments() ) : ?>
		<h2 class="comments-section__title">
			<?php
			$count = get_comments_number();
			echo esc_html( sprintf(
				_n( '%s comentario', '%s comentarios', $count, 'plataforma' ),
				number_format_i18n( $count )
			) );
			?>
		</h2>

		<ol class="comment-list">
			<?php
			wp_list_comments( [
				'style'        => 'ol',
				'short_ping'   => true,
				'avatar_size'  => 40,
				'callback'     => 'plataforma_render_comment',
			] );
			?>
		</ol>

		<?php
		the_comments_pagination( [
			'prev_text' => '← Anteriores',
			'next_text' => 'Siguientes →',
		] );
		?>

	<?php else : ?>
		<h2 class="comments-section__title">Comentarios</h2>
		<p class="comments-section__empty">Sé el primero en comentar.</p>
	<?php endif; ?>

	<?php
	comment_form( [
		'title_reply'         => 'Deja un comentario',
		'title_reply_to'      => 'Responder a %s',
		'label_submit'        => 'Publicar comentario',
		'class_submit'        => 'btn-primary',
		'comment_field'       => '<p class="comment-form-comment"><label for="comment">Tu comentario</label><textarea id="comment" name="comment" rows="4" required></textarea></p>',
		'comment_notes_before'=> '',
		'comment_notes_after' => '',
	] );
	?>
</section>
