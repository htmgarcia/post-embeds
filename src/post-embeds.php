<?php
/**
 * Plugin Name:       Post Embeds
 * Plugin URI:        https://postembeds.htmgarcia.com/
 * Description:       Customize your WordPress post embeds.
 * Author:            Valentin Garcia
 * Author URI:        https://postembeds.htmgarcia.com/
 * Version:           1.0.0
 * Text Domain:       post-embeds
 * License:           GPLv2 or later
 * Requires PHP:      5.6.20
 * Requires at least: 5.0
 */

defined( 'ABSPATH' ) || die;

if ( ! defined( 'VG_POST_EMBEDS_PLUGIN' ) ) {
    define( 'VG_POST_EMBEDS_PLUGIN', __FILE__ );
}

if ( ! defined( 'VG_POST_EMBEDS_DIR' ) ) {
    define( 'VG_POST_EMBEDS_DIR', __DIR__ );
}

register_activation_hook( __FILE__, function () {

    // Save default settings
    if ( ! get_option( 'vg_post_embeds_settings' ) ) {
        update_option(
            'vg_post_embeds_settings',
            [
                'style' => 'social-bird',
                'post_types' => [
                    'post' => 1,
                    'page' => 1
                ],
                'display_date' => [
                    'post' => 1,
                    'page' => 0
                ],
                'display_time' => [
                    'post' => 1,
                    'page' => 0
                ],
                'datetime_order' => 'time-date',
                'display_readmore' => [
                    'post' => 1
                ],
                'readmore_text' => '',
                'display_author' => [
                    'post' => 1
                ]
            ]
        );
    }
} );

require_once __DIR__ . '/helper/main.php';
new vgPostEmbedsCustomizer();
