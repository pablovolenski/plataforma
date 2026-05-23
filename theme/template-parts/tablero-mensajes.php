<?php
/**
 * Tablero tab 1 — Admin messages (plataforma_notice CPT) + outgoing contact form.
 */

defined( 'ABSPATH' ) || exit;

$notices = new WP_Query( [
	'post_type'      => 'plataforma_notice',
	'post_status'    => 'publish',
	'posts_per_page' => 20,
	'orderby'        => 'date',
	'order'          => 'DESC',
	'no_found_rows'  => true,
] );

$recipients = function_exists( 'plataforma_get_contact_recipients' )
	? plataforma_get_contact_recipients()
	: [];
$current_user = wp_get_current_user();
?>
<section class="tablero-mensajes">
	<?php if ( $notices->have_posts() ) : ?>
		<?php while ( $notices->have_posts() ) : $notices->the_post(); ?>
			<article class="notice-card">
				<header class="notice-card__head">
					<h2 class="notice-card__title"><?php the_title(); ?></h2>
					<time class="notice-card__date"
					      datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>">
						<?php echo esc_html( get_the_date( 'j \d\e F \d\e Y' ) ); ?>
					</time>
				</header>
				<div class="notice-card__body"><?php the_content(); ?></div>
			</article>
		<?php endwhile; wp_reset_postdata(); ?>
	<?php else : ?>
		<div class="tablero-mensajes__empty">
			<p>No hay mensajes del administrador todavía. ¡Vuelve pronto!</p>
		</div>
	<?php endif; ?>
</section>

<section class="contact-form-section">
	<h2 class="contact-form-section__title">Enviar mensajes</h2>
	<p class="contact-form-section__intro">
		¿Quieres comunicarte con un departamento o sección? Envía tu mensaje aquí.
	</p>

	<form id="contact-form" class="contact-form" novalidate>
		<?php wp_nonce_field( 'plataforma_contact_nonce', '_wpnonce' ); ?>

		<div class="form-row">
			<label for="contact-recipient">Destinatario</label>
			<select id="contact-recipient" name="recipient" required>
				<option value="">— Selecciona —</option>
				<?php foreach ( $recipients as $r ) : ?>
					<option value="<?php echo esc_attr( $r['id'] ); ?>"><?php echo esc_html( $r['name'] ); ?></option>
				<?php endforeach; ?>
			</select>
		</div>

		<div class="form-row">
			<label for="contact-subject">Asunto</label>
			<input type="text" id="contact-subject" name="subject" maxlength="120" required>
		</div>

		<div class="form-row">
			<label for="contact-message">Mensaje</label>
			<textarea id="contact-message" name="message" rows="6" required></textarea>
		</div>

		<div id="contact-notice" class="notice" hidden aria-live="polite"></div>

		<div class="form-actions">
			<button type="submit" class="btn-primary">Enviar mensaje</button>
		</div>
	</form>
</section>
