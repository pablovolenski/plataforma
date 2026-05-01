<?php
/**
 * SEO: Open Graph meta tags and JSON-LD structured data.
 */

defined( 'ABSPATH' ) || exit;

add_action( 'wp_head', 'plataforma_seo_head', 1 );

function plataforma_seo_head(): void {
	if ( is_single() ) {
		plataforma_seo_single();
	} elseif ( is_home() || is_front_page() ) {
		plataforma_seo_home();
	}
}

function plataforma_seo_single(): void {
	global $post;

	$title       = get_the_title( $post );
	$description = has_excerpt( $post ) ? get_the_excerpt( $post ) : wp_trim_words( get_the_content( null, false, $post ), 30 );
	$url         = get_permalink( $post );
	$date_pub    = get_the_date( 'c', $post );
	$date_mod    = get_the_modified_date( 'c', $post );
	$author_name = get_the_author_meta( 'display_name', $post->post_author );
	$site_name   = get_bloginfo( 'name' );
	$image       = get_the_post_thumbnail_url( $post, 'large' ) ?: '';

	// Open Graph
	echo '<meta property="og:type" content="article">' . "\n";
	echo '<meta property="og:site_name" content="' . esc_attr( $site_name ) . '">' . "\n";
	echo '<meta property="og:title" content="' . esc_attr( $title ) . '">' . "\n";
	echo '<meta property="og:description" content="' . esc_attr( $description ) . '">' . "\n";
	echo '<meta property="og:url" content="' . esc_url( $url ) . '">' . "\n";
	if ( $image ) {
		echo '<meta property="og:image" content="' . esc_url( $image ) . '">' . "\n";
	}

	// Twitter Card
	echo '<meta name="twitter:card" content="summary_large_image">' . "\n";
	echo '<meta name="twitter:title" content="' . esc_attr( $title ) . '">' . "\n";
	echo '<meta name="twitter:description" content="' . esc_attr( $description ) . '">' . "\n";

	// JSON-LD BlogPosting
	$schema = [
		'@context'         => 'https://schema.org',
		'@type'            => 'BlogPosting',
		'headline'         => $title,
		'description'      => $description,
		'url'              => $url,
		'datePublished'    => $date_pub,
		'dateModified'     => $date_mod,
		'author'           => [
			'@type' => 'Person',
			'name'  => $author_name,
		],
		'publisher'        => [
			'@type' => 'Organization',
			'name'  => $site_name,
			'url'   => home_url( '/' ),
		],
	];

	if ( $image ) {
		$schema['image'] = $image;
	}

	echo '<script type="application/ld+json">'
		. wp_json_encode( $schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT )
		. '</script>' . "\n";
}

function plataforma_seo_home(): void {
	$site_name = get_bloginfo( 'name' );
	$tagline   = get_bloginfo( 'description' );
	$url       = home_url( '/' );

	// Open Graph
	echo '<meta property="og:type" content="website">' . "\n";
	echo '<meta property="og:site_name" content="' . esc_attr( $site_name ) . '">' . "\n";
	echo '<meta property="og:title" content="' . esc_attr( $site_name ) . '">' . "\n";
	echo '<meta property="og:description" content="' . esc_attr( $tagline ) . '">' . "\n";
	echo '<meta property="og:url" content="' . esc_url( $url ) . '">' . "\n";

	// JSON-LD Blog
	$recent = get_posts( [ 'numberposts' => 10, 'post_status' => 'publish' ] );
	$posts_schema = [];
	foreach ( $recent as $p ) {
		$entry = [
			'@type'         => 'BlogPosting',
			'headline'      => get_the_title( $p ),
			'url'           => get_permalink( $p ),
			'datePublished' => get_the_date( 'c', $p ),
			'author'        => [
				'@type' => 'Person',
				'name'  => get_the_author_meta( 'display_name', $p->post_author ),
			],
		];
		$excerpt = get_the_excerpt( $p );
		if ( $excerpt ) {
			$entry['description'] = $excerpt;
		}
		$posts_schema[] = $entry;
	}

	$schema = [
		'@context'    => 'https://schema.org',
		'@type'       => 'Blog',
		'name'        => $site_name,
		'description' => $tagline,
		'url'         => $url,
	];

	if ( $posts_schema ) {
		$schema['blogPost'] = $posts_schema;
	}

	echo '<script type="application/ld+json">'
		. wp_json_encode( $schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT )
		. '</script>' . "\n";
}
