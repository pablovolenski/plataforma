<?php
/**
 * Author archive: profile header + list of posts by this author.
 * URL: /author/{username}/  (WordPress built-in author base)
 */

defined( 'ABSPATH' ) || exit;

get_header();

$author      = get_queried_object(); // WP_User
$bio         = get_the_author_meta( 'description', $author->ID );
$author_url  = get_author_posts_url( $author->ID );
$post_count  = count_user_posts( $author->ID, 'post', true );
?>

<main class="feed">

	<div class="author-profile">
		<a class="author-profile__avatar-link" href="<?php echo esc_url( $author_url ); ?>" aria-hidden="true" tabindex="-1">
			<?php echo get_avatar( $author->ID, 88, '', esc_attr( $author->display_name ), [ 'class' => 'author-profile__avatar' ] ); ?>
		</a>
		<div class="author-profile__body">
			<h1 class="author-profile__name"><?php echo esc_html( $author->display_name ); ?></h1>
			<?php if ( $bio ) : ?>
				<p class="author-profile__bio"><?php echo esc_html( $bio ); ?></p>
			<?php endif; ?>
			<p class="author-profile__stats">
				<?php echo esc_html( $post_count ); ?>
				<?php echo esc_html( _n( 'publicación', 'publicaciones', $post_count, 'plataforma' ) ); ?>
			</p>
		</div>
	</div>

	<section class="wall" aria-label="<?php echo esc_attr( sprintf( 'Publicaciones de %s', $author->display_name ) ); ?>">
		<div id="articles" class="articles">
			<?php
			if ( have_posts() ) :
				while ( have_posts() ) :
					the_post();
					get_template_part( 'template-parts/post-card' );
				endwhile;
				the_posts_pagination( [
					'prev_text' => '← Anterior',
					'next_text' => 'Siguiente →',
					'class'     => 'posts-pagination',
				] );
			else :
				?>
				<div class="empty-state">
					<?php echo esc_html( $author->display_name ); ?> aún no ha publicado nada.
				</div>
				<?php
			endif;
			?>
		</div>
	</section>

</main>

<?php get_footer(); ?>
