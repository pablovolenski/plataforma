<?php
/**
 * GEO surfaces:
 *   1. robots.txt — explicit allow rules for AI crawlers.
 *   2. /llms.txt — Markdown index for LLM retrieval (proposed by Anthropic).
 */

defined( 'ABSPATH' ) || exit;

// ---------------------------------------------------------------------------
// robots.txt — explicit AI-crawler allow list + sitemap
// ---------------------------------------------------------------------------
add_filter( 'robots_txt', 'plataforma_robots_txt', 10, 2 );

function plataforma_robots_txt( string $output, $public ): string {
	if ( ! $public ) {
		return $output;
	}

	$ai_bots = [
		'GPTBot', 'ClaudeBot', 'anthropic-ai', 'PerplexityBot',
		'Google-Extended', 'CCBot', 'Amazonbot', 'Bytespider',
		'Applebot-Extended', 'meta-externalagent',
	];

	$out = $output . "\n# AI crawlers — explicit allow\n";
	foreach ( $ai_bots as $bot ) {
		$out .= "User-agent: {$bot}\n";
		$out .= "Allow: /\n\n";
	}
	$out .= 'Sitemap: ' . home_url( '/wp-sitemap.xml' ) . "\n";
	return $out;
}

// ---------------------------------------------------------------------------
// /llms.txt — Markdown index of the site's content for LLM retrieval.
// See https://llmstxt.org/ (proposed 2024, adopted through 2025).
// ---------------------------------------------------------------------------
add_action( 'init', 'plataforma_llms_txt_rewrite' );

function plataforma_llms_txt_rewrite(): void {
	add_rewrite_rule( '^llms\.txt$', 'index.php?plataforma_llms=1', 'top' );
	add_rewrite_tag( '%plataforma_llms%', '1' );
}

add_action( 'template_redirect', 'plataforma_llms_txt_render' );

function plataforma_llms_txt_render(): void {
	if ( ! get_query_var( 'plataforma_llms' ) ) {
		return;
	}

	$cache = get_transient( 'plataforma_llms_txt' );
	if ( false === $cache ) {
		$cache = plataforma_llms_txt_build();
		set_transient( 'plataforma_llms_txt', $cache, HOUR_IN_SECONDS );
	}

	nocache_headers();
	header( 'Content-Type: text/markdown; charset=utf-8' );
	echo $cache;
	exit;
}

function plataforma_llms_txt_build(): string {
	$name    = get_bloginfo( 'name' );
	$tagline = get_bloginfo( 'description' );
	$home    = home_url( '/' );

	$out  = "# {$name}\n\n";
	$out .= "> {$tagline}\n\n";
	$out .= "Site home: {$home}\n\n";
	$out .= "This site publishes in Spanish. Every article has server-rendered German (/de/) and Brazilian Portuguese (/pt-br/) translations linked via hreflang.\n\n";

	$out .= "## Recent articles\n\n";
	$posts = get_posts( [
		'numberposts' => 50,
		'post_status' => 'publish',
		'post_type'   => 'post',
		'orderby'     => 'date',
		'order'       => 'DESC',
	] );

	foreach ( $posts as $p ) {
		$title   = get_the_title( $p );
		$url     = get_permalink( $p );
		$excerpt = has_excerpt( $p )
			? get_the_excerpt( $p )
			: wp_trim_words( wp_strip_all_tags( $p->post_content ), 30 );
		$out .= "- [{$title}]({$url}): {$excerpt}\n";
	}

	return $out;
}

// Flush rewrite rules once when this file's version tag changes.
add_action( 'admin_init', function () {
	$stamp = get_option( 'plataforma_llms_rules_v' );
	if ( '1' !== $stamp ) {
		flush_rewrite_rules( false );
		update_option( 'plataforma_llms_rules_v', '1' );
	}
} );
