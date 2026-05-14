<?php
/**
 * People listing — served at /personas/
 * Shows all users in a card grid with dynamic category filter chips.
 */

defined( 'ABSPATH' ) || exit;

get_header();

$users  = get_users( [
	'orderby' => 'display_name',
	'order'   => 'ASC',
	'number'  => -1,
] );
$groups = function_exists( 'plataforma_get_groups' ) ? plataforma_get_groups() : [];
?>

<main class="personas-page">
	<div class="personas-page__inner wall__inner">

		<header class="personas-page__header">
			<h1 class="personas-page__title">Personas</h1>
		</header>

		<?php if ( ! empty( $groups ) ) : ?>
		<div class="personas-filters wall__filters" role="group" aria-label="Filtrar por categoría">
			<button class="filter-chip is-active" data-group="">Todos</button>
			<?php foreach ( $groups as $g ) : ?>
				<button class="filter-chip" data-group="<?php echo esc_attr( $g['id'] ); ?>">
					<?php echo esc_html( $g['name'] ); ?>
				</button>
			<?php endforeach; ?>
		</div>
		<?php endif; ?>

		<div class="personas-grid">
			<?php foreach ( $users as $user ) :
				$user_groups = (array) ( get_user_meta( $user->ID, '_plataforma_groups', true ) ?: [] );
				$bio         = (string) get_user_meta( $user->ID, 'description', true );
				$avatar_url  = function_exists( 'plataforma_get_avatar_url' )
					? plataforma_get_avatar_url( $user->ID, 80 )
					: get_avatar_url( $user->ID, [ 'size' => 80 ] );

				$member_groups = [];
				foreach ( $groups as $g ) {
					if ( in_array( $g['id'], $user_groups, true ) ) {
						$member_groups[] = $g;
					}
				}
			?>
			<article class="persona-card"
			         data-groups="<?php echo esc_attr( implode( ' ', $user_groups ) ); ?>">
				<img src="<?php echo esc_url( $avatar_url ); ?>"
				     alt="<?php echo esc_attr( $user->display_name ); ?>"
				     width="80" height="80" loading="lazy">
				<strong class="persona-card__name"><?php echo esc_html( $user->display_name ); ?></strong>
				<?php if ( $bio ) : ?>
					<p class="persona-card__bio"><?php echo esc_html( wp_trim_words( $bio, 20 ) ); ?></p>
				<?php endif; ?>
				<?php if ( ! empty( $member_groups ) ) : ?>
					<div class="persona-card__groups">
						<?php foreach ( $member_groups as $g ) : ?>
							<span class="group-pill"><?php echo esc_html( $g['name'] ); ?></span>
						<?php endforeach; ?>
					</div>
				<?php endif; ?>
			</article>
			<?php endforeach; ?>
		</div>

	</div>
</main>

<?php get_footer(); ?>
