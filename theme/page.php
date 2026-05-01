<?php
/**
 * Generic static page template.
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>

<main class="layout layout--page">
	<article class="panel">
		<?php
		if ( have_posts() ) {
			the_post();
		}
		?>
		<h1><?php the_title(); ?></h1>
		<div class="article-single__body">
			<?php the_content(); ?>
		</div>
	</article>
</main>

<?php get_footer(); ?>
