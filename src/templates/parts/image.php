<?php
$thumbnail_id = 0;

if ( has_post_thumbnail() ) {
    $thumbnail_id = get_post_thumbnail_id();
}

if ( 'attachment' === get_post_type() && wp_attachment_is_image() ) {
    $thumbnail_id = get_the_ID();
}

$thumbnail_id = apply_filters( 'embed_thumbnail_id', $thumbnail_id );

if ( $thumbnail_id ) {
    $aspect_ratio = 1;
    $measurements = array( 1, 1 );
    $image_size   = 'full'; // Fallback.

    $meta = wp_get_attachment_metadata( $thumbnail_id );
    if ( ! empty( $meta['sizes'] ) ) {
        foreach ( $meta['sizes'] as $size => $data ) {
            if ( $data['height'] > 0 && $data['width'] / $data['height'] > $aspect_ratio ) {
                $aspect_ratio = $data['width'] / $data['height'];
                $measurements = array( $data['width'], $data['height'] );
                $image_size   = $size;
            }
        }
    }

    $image_size = apply_filters( 'embed_thumbnail_image_size', $image_size, $thumbnail_id );

    $shape = $measurements[0] / $measurements[1] >= 1.75 ? 'rectangular' : 'square';

    $shape = apply_filters( 'embed_thumbnail_image_shape', $shape, $thumbnail_id );
}
