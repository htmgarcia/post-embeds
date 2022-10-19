<?php
global $post;

$author_name    = get_the_author_meta( 'display_name' , $post->post_author );
$author_url     = esc_url( get_author_posts_url( $post->post_author ) );
?>
<div>
    <a href="<?php echo $author_url ?>">
        <?php echo $author_name ?>
    </a>
</div>
