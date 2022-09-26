<?php
/**
 * Plugin Name:       WP Post Embeds
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

defined( 'ABSPATH' ) || die;

if ( ! defined( 'VG_POST_EMBEDS_PLUGIN' ) ) {
    define( 'VG_POST_EMBEDS_PLUGIN', __FILE__ );
}

if ( ! defined( 'VG_POST_EMBEDS_DIR' ) ) {
    define( 'VG_POST_EMBEDS_DIR', __DIR__ );
}

require_once __DIR__ . '/helper/main.php';
new wpPostEmbedsCustomizer();
