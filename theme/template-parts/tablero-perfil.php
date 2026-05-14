<?php
/**
 * Tablero tab 3 — Edit profile, view groups, change password.
 */

defined( 'ABSPATH' ) || exit;

$user        = wp_get_current_user();
$description = (string) get_user_meta( $user->ID, 'description', true );
$all_groups  = function_exists( 'plataforma_get_groups' ) ? plataforma_get_groups() : [];
$user_groups = (array) ( get_user_meta( $user->ID, '_plataforma_groups', true ) ?: [] );

$member_names = [];
foreach ( $all_groups as $g ) {
	if ( in_array( $g['id'], $user_groups, true ) ) {
		$member_names[] = $g['name'];
	}
}
?>
<section class="tablero-perfil">

	<!-- ── Personal info ───────────────────────────────── -->
	<div class="tablero-perfil__block">
		<h2 class="tablero-perfil__section-title">Información personal</h2>

		<div id="profile-notice" class="notice" hidden aria-live="polite"></div>

		<form id="profile-form" class="profile-form" novalidate>
			<?php wp_nonce_field( 'plataforma_profile_nonce', '_wpnonce' ); ?>

			<div class="profile-avatar-wrap">
				<img id="avatar-preview"
				     src="<?php echo esc_url( function_exists( 'plataforma_get_avatar_url' ) ? plataforma_get_avatar_url( $user->ID, 96 ) : get_avatar_url( $user->ID, [ 'size' => 96 ] ) ); ?>"
				     alt="" width="96" height="96" class="profile-avatar__img">
				<div>
					<label class="btn-ghost profile-avatar__btn" for="avatar-file">
						Cambiar foto
						<input type="file" id="avatar-file" accept="image/*" hidden>
					</label>
					<span id="avatar-status" class="profile-avatar__status" aria-live="polite"></span>
				</div>
			</div>

			<label>
				Nombre que se muestra
				<input type="text" name="display_name"
				       value="<?php echo esc_attr( $user->display_name ); ?>" required>
			</label>

			<label>
				Email
				<input type="email" name="user_email"
				       value="<?php echo esc_attr( $user->user_email ); ?>" required>
			</label>

			<label>
				Presentación
				<textarea name="description" rows="4"
				          placeholder="Cuéntanos sobre ti…"><?php echo esc_textarea( $description ); ?></textarea>
			</label>

			<div class="profile-form__actions">
				<button type="submit" class="btn-primary">Guardar cambios</button>
			</div>
		</form>
	</div>

	<!-- ── Groups (read-only; assigned by admin) ───────── -->
	<?php if ( ! empty( $all_groups ) ) : ?>
	<div class="tablero-perfil__block tablero-perfil__groups">
		<h2 class="tablero-perfil__section-title">Mis grupos</h2>

		<?php if ( ! empty( $member_names ) ) : ?>
			<div class="group-pills">
				<?php foreach ( $member_names as $name ) : ?>
					<span class="group-pill"><?php echo esc_html( $name ); ?></span>
				<?php endforeach; ?>
			</div>
		<?php else : ?>
			<p class="tablero-perfil__note">Todavía no te han asignado ningún grupo.</p>
		<?php endif; ?>
	</div>
	<?php endif; ?>

	<!-- ── Password change ─────────────────────────────── -->
	<div class="tablero-perfil__block tablero-perfil__password">
		<h2 class="tablero-perfil__section-title">Cambiar contraseña</h2>

		<div id="password-notice" class="notice" hidden aria-live="polite"></div>

		<form id="password-form" class="profile-form" novalidate>
			<?php wp_nonce_field( 'plataforma_profile_nonce', '_wpnonce' ); ?>

			<label>
				Contraseña actual
				<input type="password" name="current_password" required autocomplete="current-password">
			</label>

			<label>
				Nueva contraseña <span class="tablero-perfil__hint">(mínimo 8 caracteres)</span>
				<input type="password" name="new_password" required autocomplete="new-password" minlength="8">
			</label>

			<label>
				Confirmar nueva contraseña
				<input type="password" name="confirm_password" required autocomplete="new-password">
			</label>

			<div class="profile-form__actions">
				<button type="submit" class="btn-primary">Cambiar contraseña</button>
			</div>
		</form>
	</div>

</section>
