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

        <h4 class="pe-title">
            <a href="<?php the_permalink(); ?>" target="_top">
                 <?php the_title(); ?>
            </a>
        </h4>

        <div class="pe-excerpt">
            <?php the_excerpt_embed(); ?>
        </div>

        <div class="pe-content">
            <?php do_action( 'embed_content' ); ?>
        </div>

        <?php
        // $thumbnail_id, $shape and $image_size values comes from image.php
        include_once 'parts/image.php';
        if ( $thumbnail_id ) :
            ?>
            <div class="pe-image <?php echo $shape ?>">
                 <a href="<?php the_permalink(); ?>" target="_top">
                     <?php echo wp_get_attachment_image( $thumbnail_id, $image_size ); ?>
                 </a>
            </div>
            <?php
        endif;
        ?>

        <div class="pe-date">
            <p>6:44 PM &bullet; Aug 18, 2022</p>
        </div>

        <div class="pe-readmore">
            <p>
                 <a href="<?php the_permalink(); ?>" target="_top">
                     Continue reading...
                 </a>
            </p>
        </div>

        <div class="pe-footer">
            <?php the_embed_site_title(); ?>
        	<div class="pe-embed-meta">
        		<?php do_action( 'embed_content_meta' ); ?>
        	</div>
        </div>

    </div>
    <?php do_action( 'embed_footer' ); ?>
</body>
</html>
