	<footer class="site-foot">
		<?php
		$footer_pages = get_pages( [
			'sort_column' => 'menu_order',
			'number'      => 8,
			'post_status' => 'publish',
		] );
		if ( $footer_pages ) :
			?>
			<nav class="site-foot__nav" aria-label="Páginas del sitio">
				<?php foreach ( $footer_pages as $fp ) : ?>
					<a href="<?php echo esc_url( get_permalink( $fp->ID ) ); ?>"><?php echo esc_html( $fp->post_title ); ?></a>
				<?php endforeach; ?>
			</nav>
		<?php endif; ?>
		<p><?php bloginfo( 'name' ); ?> &copy; <?php echo esc_html( gmdate( 'Y' ) ); ?></p>
	</footer>

</div><!-- /.page-shell -->

<?php wp_footer(); ?>
</body>
</html>
