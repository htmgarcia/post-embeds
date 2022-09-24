<?php
/**
 * @package Post Embeds
 * @since 0.0.1
 */

 if ( ! headers_sent() ) {
 	header( 'X-WP-embed: true' );
 }

 ?>
 <!DOCTYPE html>
 <html <?php language_attributes(); ?> class="no-js">
 <head>
 	<title><?php echo wp_get_document_title(); ?></title>
 	<meta http-equiv="X-UA-Compatible" content="IE=edge">
 	<?php
 	/**
 	 * Prints scripts or data in the embed template head tag.
 	 *
 	 * @since 4.4.0
 	 */
 	do_action( 'embed_head' );
 	?>
 </head>
 <body <?php body_class(); ?>>
<div class="wp-embed">
	<p class="wp-embed-heading"><?php _e( 'Oops! That embed cannot be found.' ); ?></p>

	<div class="wp-embed-excerpt">
		<p>
			<?php
			printf(
				/* translators: %s: A link to the embedded site. */
				__( 'It looks like nothing was found at this location. Maybe try visiting %s directly?' ),
				'<strong><a href="' . esc_url( home_url() ) . '">' . esc_html( get_bloginfo( 'name' ) ) . '</a></strong>'
			);
			?>
		</p>
	</div>

	<?php
	/** This filter is documented in wp-includes/theme-compat/embed-content.php */
	do_action( 'embed_content' );
	?>

	<div class="wp-embed-footer">
		<?php the_embed_site_title(); ?>
	</div>
</div>
</body>
</html>
