<?php
/**
 * Plugin Name:       Post Embeds
 * Plugin URI:        https://htmgarcia.com
 * Description:       Customize your WordPress post embeds.
 * Author:            Valentin Garcia
 * Author URI:        https://htmgarcia.com
 * Version:           0.0.1
 * Text Domain:       post-embeds
 * License:           GPLv2 or later
 * Requires PHP:      5.6.20
 * Requires at least: 5.0
 */

if( ! class_exists( 'wpPostEmbedsCustomizer' ) ) {

    class wpPostEmbedsCustomizer
    {
        public function __construct()
        {
            add_filter( 'embed_template', [$this, 'loadEmbedTemplate'] );
            add_action( 'embed_head', [$this, 'loadStyles'] );

            // Remove default CSS
            remove_action( 'embed_head', 'print_embed_styles' );
        }

        public function loadEmbedTemplate( $template )
        {
            $template = dirname( __FILE__ ) . '/templates/default.php';

            // Post not found or wrong URL
            if( is_404() ) {
                $template = dirname( __FILE__ ) . '/templates/404.php';
            }

            return $template;
        }

        public function loadStyles ()
        {
            $type_attr = current_theme_supports( 'html5', 'style' ) ? '' : ' type="text/css"';
            ?>
            <style<?php echo $type_attr; ?>>
        		<?php echo file_get_contents( dirname( __FILE__ ) . '/assets/css/default.css' ); ?>
        	</style>
            <style<?php echo $type_attr; ?>>
            .wp-embed-featured-image {
                float: none !important;
                max-width: 100% !important;
                margin: 0 !important;
            }
            </style>
            <?php
        }

    }
}
new wpPostEmbedsCustomizer();
