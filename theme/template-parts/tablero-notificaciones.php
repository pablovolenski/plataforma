<?php
/**
 * Template part: Notificaciones tab in Mi Espacio.
 */

defined( 'ABSPATH' ) || exit;

$user_id       = get_current_user_id();
$notifications = (array) ( get_user_meta( $user_id, '_plataforma_notifications', true ) ?: [] );
?>

<div class="notif-panel">
	<div class="notif-panel__head">
		<h2 class="notif-panel__title">Notificaciones</h2>
		<button type="button" class="btn-ghost notif-panel__push-btn" id="enable-push-btn">
			Activar notificaciones en este dispositivo
		</button>
	</div>

	<p id="push-status" class="notif-panel__push-status" aria-live="polite" hidden></p>

	<?php if ( empty( $notifications ) ) : ?>
		<p class="notif-panel__empty">No tienes notificaciones aún.</p>
	<?php else : ?>
		<ul class="notif-list" id="notif-list">
			<?php foreach ( $notifications as $notif ) :
				$read    = ! empty( $notif['read'] );
				$type    = $notif['type'] ?? '';
				$time    = $notif['created_at'] ?? '';
				$link    = '';
				$message = '';

				if ( $type === 'mention' ) {
					$author = esc_html( $notif['author_name'] ?? 'Alguien' );
					$ptitle = esc_html( $notif['post_title']  ?? '' );
					$message = $author . ' te mencionó' . ( $ptitle ? ' en: ' . $ptitle : '' );
					if ( ! empty( $notif['post_id'] ) ) {
						$link = get_permalink( (int) $notif['post_id'] );
					}
				} else {
					$message = esc_html( $notif['message'] ?? 'Nueva notificación' );
				}
				?>
				<li class="notif-card<?php echo $read ? '' : ' notif-card--unread'; ?>">
					<span class="notif-card__dot" aria-hidden="true"></span>
					<div class="notif-card__body">
						<?php if ( $link ) : ?>
							<a class="notif-card__message" href="<?php echo esc_url( $link ); ?>"><?php echo esc_html( $message ); ?></a>
						<?php else : ?>
							<span class="notif-card__message"><?php echo esc_html( $message ); ?></span>
						<?php endif; ?>
						<?php if ( $time ) : ?>
							<time class="notif-card__time" datetime="<?php echo esc_attr( $time ); ?>">
								<?php echo esc_html( human_time_diff( strtotime( $time ), current_time( 'timestamp' ) ) ) . ' atrás'; ?>
							</time>
						<?php endif; ?>
					</div>
				</li>
			<?php endforeach; ?>
		</ul>
	<?php endif; ?>
</div>
