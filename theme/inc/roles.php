<?php
/**
 * Like helpers — fallbacks if the plugin is inactive.
 * The canonical implementations live in plataforma-social.php; these
 * function_exists guards keep the site from fataling if the plugin is
 * deactivated or being upgraded.
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'plataforma_get_liker_identifier' ) ) {
	function plataforma_get_liker_identifier(): string {
		$user_id = get_current_user_id();
		if ( $user_id ) {
			return (string) $user_id;
		}
		$ip = $_SERVER['REMOTE_ADDR'] ?? '';
		return 'ip:' . hash_hmac( 'sha256', $ip, wp_salt( 'auth' ) );
	}
}

if ( ! function_exists( 'plataforma_user_has_liked' ) ) {
	function plataforma_user_has_liked( int $post_id, $identifier = null ): bool {
		$identifier = $identifier ?: plataforma_get_liker_identifier();
		if ( ! $identifier ) {
			return false;
		}
		$likes = get_post_meta( $post_id, '_plataforma_likes', true );
		return is_array( $likes ) && in_array( (string) $identifier, $likes, true );
	}
}

if ( ! function_exists( 'plataforma_like_count' ) ) {
	function plataforma_like_count( int $post_id ): int {
		$likes = get_post_meta( $post_id, '_plataforma_likes', true );
		return is_array( $likes ) ? count( $likes ) : 0;
	}
}
