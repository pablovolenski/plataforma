<?php
/**
 * Role helpers — thin wrappers around the like meta.
 * The role definitions and AJAX handlers live in the plugin.
 */

defined( 'ABSPATH' ) || exit;

/**
 * Returns true if the given user has already liked the given post.
 * Guarded so the plugin and theme can coexist without fatal redeclaration errors.
 */
if ( ! function_exists( 'plataforma_user_has_liked' ) ) {
	function plataforma_user_has_liked( int $post_id, int $user_id ): bool {
		if ( ! $user_id ) {
			return false;
		}
		$likes = get_post_meta( $post_id, '_plataforma_likes', true );
		return is_array( $likes ) && in_array( $user_id, $likes, true );
	}
}

/**
 * Returns the total like count for a post.
 */
if ( ! function_exists( 'plataforma_like_count' ) ) {
	function plataforma_like_count( int $post_id ): int {
		$likes = get_post_meta( $post_id, '_plataforma_likes', true );
		return is_array( $likes ) ? count( $likes ) : 0;
	}
}
