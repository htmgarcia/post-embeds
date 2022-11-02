<?php
/**
 * @package WP Post Embeds
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
	<?php do_action( 'embed_head' ); ?>
</head>
<body <?php body_class(); ?>>
    <div class="pe-embed">

        <div class="pe-title">
            <?php the_embed_site_title(); ?>
            <div>
                <h4>
                    <a href="<?php echo get_site_url(); ?>" target="_top">
                        <?php echo esc_html( get_bloginfo( 'name' ) ) ?>
                    </a>
                </h4>
                <?php do_action( 'vg_post_embeds_datetime' ); // .pe-date ?>
            </div>
        </div>
        <?php
        // $thumbnail_id, $shape and $image_size values comes from image.php
        include_once 'parts/image.php';

        if( isset( $shape ) && $shape === 'square' ) {
            ?>
            <div class="pe-excerpt">
                <?php the_excerpt_embed(); ?>
            </div>
            <?php
        }

        $image_class = '';
        $image_class .= isset( $shape ) ? ' pe-image-shape-' . $shape : '';
        $image_class .= isset( $shape ) && $shape === 'square' ? ' pe-info-content' : '';
        ?>
        <div class="pe-excerpt-image<?php echo $image_class ?> pe-excerpt--no-image">
            <div class="pe-excerpt">
                <?php
                if( isset( $shape ) && $shape === 'square' ) {
                    ?>
                    <h4>
                        <a href="<?php the_permalink(); ?>" target="_top">
                            <?php the_title(); ?>
                        </a>
                    </h4>
                    <?php do_action( 'vg_post_embeds_author' ); // .pe-author ?>
                    <?php
                } else{
                    the_excerpt_embed();
                    do_action( 'embed_content' );
                }
                ?>
            </div>

            <?php
            if ( $thumbnail_id ) :
                $image_url = wp_get_attachment_image_url( $thumbnail_id, $image_size );
                ?>
                <div class="pe-image" style="background-image:url(<?php echo $image_url ?>);">
                    <a href="<?php the_permalink(); ?>" target="_top"></a>
                    <?php echo wp_get_attachment_image( $thumbnail_id, $image_size ); ?>
                </div>
                <?php
            endif;
            ?>
        </div>

        <?php if( ! isset( $shape ) || $shape !== 'square' ) : ?>
            <div class="pe-below-content pe-info-content">
                <h4>
                    <a href="<?php the_permalink(); ?>" target="_top">
                        <?php the_title(); ?>
                    </a>
                </h4>
                <?php do_action( 'vg_post_embeds_author' ); // .pe-author ?>
            </div>
        <?php endif; ?>

        <?php do_action( 'vg_post_embeds_readmore' ); // .pe-readmore ?>

        <div class="pe-footer">
            <div class="pe-embed-meta">
        		<?php do_action( 'embed_content_meta' ); ?>
        	</div>
        </div>

    </div>
    <?php do_action( 'embed_footer' ); ?>
</body>
</html>
