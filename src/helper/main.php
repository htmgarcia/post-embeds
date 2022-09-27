<?php
/**
 * Post embeds class
 */

defined( 'ABSPATH' ) || die;

if( ! class_exists( 'wpPostEmbedsCustomizer' ) ) {

    class wpPostEmbedsCustomizer
    {
        public function __construct()
        {
            if( is_admin() ) {
                add_action( 'admin_menu', [$this, 'loadMenu'] );
            } else {
                add_filter( 'embed_template', [$this, 'loadEmbedTemplate'], 9999 );
                add_filter( 'body_class', [$this, 'setBodyClasses'], 9999 );
                add_action( 'embed_head', [$this, 'loadStyles'] );

                // Remove default CSS
                remove_action( 'embed_head', 'print_embed_styles' );
            }
        }

        /*
         * Override core/theme embed template
         *
         * @since 0.0.1
         * @param string $template The template file
         */
        public function loadEmbedTemplate( $template )
        {
            $style      = $this->singleSetting( 'style', 'custom' );
            $template   = VG_POST_EMBEDS_DIR . '/templates/' . $style . '.php';

            // Post not found or wrong URL
            if( is_404() ) {
                $template = VG_POST_EMBEDS_DIR . '/templates/404.php';
            }

            return $template;
        }

        /*
         * Load our own custom CSS for embeds
         */
        public function loadStyles()
        {
            $type_attr  = current_theme_supports( 'html5', 'style' ) ? '' : ' type="text/css"';
            $style      = $this->singleSetting( 'style', 'custom' );
            ?>
            <style<?php echo $type_attr; ?>>
        		<?php echo file_get_contents( dirname( __FILE__ ) . '/../assets/css/' . $style . '.css' ); ?>
        	</style>
            <?php
        }

        /*
         * Add admin menu
         */
        public function loadMenu()
        {
            if ( empty( $GLOBALS['admin_page_hooks']['vg_post_embeds'] ) ) {
                add_menu_page(
                    __( 'Post Embeds' , 'post-embeds' ),
                    __( 'Post Embeds' , 'post-embeds' ),
                    'manage_options',
                    'vg_post_embeds',
                    [$this, 'loadMainPage'],
                    'dashicons-embed-post'
                );
            }

            add_submenu_page(
                'vg_post_embeds',
                __( 'Settings' , 'post-embeds' ),
                __( 'Settings' , 'post-embeds' ),
                'manage_options',
                'vg_post_embeds_settings',
                [
                    $this, 'loadSettingsPage'
                ]
            );

        }

        public function loadMainPage()
        {
            echo 'Main page';
        }

        /*
         * Output settings page
         */
        public function loadSettingsPage()
        {
            ?>
            <div class="wrap">
                <h1>
                    <?php esc_html_e( 'Settings', 'post-embeds' ); ?>
                </h1>

                <?php
                self::saveSettings();

                $settings       = get_option( 'vg_post_embeds_settings' );
                $style          = isset( $settings['style'] ) ? sanitize_text_field( $settings['style'] ) : '';
                $default_design = isset( $settings['default_design'] ) && $settings['default_design'] ? 'checked' : '';
                    if ( ! isset( $settings['default_design'] ) ) {
                        $result = 'checked';
                    }
                ?>

                <form method="post">
                    <?php wp_nonce_field( 'vg_post_embeds_settings_nonce', 'vg_post_embeds_settings_nonce_field' ) ?>
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <?php _e( 'Embed style', 'post-embeds' ) ?>
                            </th>
                            <td>
                                <label>
                                    <select name="style">
                                        <option value="default"
                                            <?php echo sanitize_text_field( $style ) === 'default' ? ' selected' : '' ?>
                                        >
                                            <?php esc_attr_e( 'Default', 'post-embeds' ) ?>
                                        </option>
                                        <option value="custom"
                                            <?php echo sanitize_text_field( $style ) === 'custom' ? ' selected' : '' ?>
                                        >
                                            <?php esc_attr_e( 'Custom', 'post-embeds' ) ?>
                                        </option>
                                    </select>
                                </label>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <?php _e( 'Default embed design', 'post-embeds' ) ?>
                            </th>
                            <td>
                                <label>
                                    <input type="checkbox" name="default_design" value="1"
                                        <?php echo esc_attr( $default_design ) ?>
                                    />
                                </label>
                                <?php
                                esc_html_e(
                                    'If unchecked, core or theme styles for embeds won\'t load',
                                    'post-embeds'
                                )
                                ?>
                            </td>
                        </tr>
                    </table>
                    <p>
                        <button type="submit" class="button button-primary" name="save">
                            <?php esc_html_e( 'Save Settings', 'post-embeds' ) ?>
                        </button>
                    </p>
                </form>
            </div>
            <?php
        }

        /*
         * Save settings
         *
         * @since 0.0.1
         */
        private function saveSettings()
        {
            if ( ! current_user_can( 'activate_plugins' ) ) {
                return false;
            }

            if ( isset( $_POST['save'] ) ) // phpcs:ignore WordPress.Security.NonceVerification.Missing -- we check nonce below
            {
                if ( ! wp_verify_nonce(
                        sanitize_key( $_POST['vg_post_embeds_settings_nonce_field'] ),
                        'vg_post_embeds_settings_nonce'
                    )
                ) {
                    $this->notifyMsg(
                        __( 'An error ocurred. Please try again.', 'post-embeds' ),
                        'error'
                    );

                    return false;
                }

                $settings                   = get_option( 'vg_post_embeds_settings' );
                $settings['style']          = isset( $_POST['style'] ) && ! empty( $_POST['style'] ) ? sanitize_text_field( $_POST['style'] ) : '';
                $settings['default_design'] = isset( $_POST['default_design'] ) ? 1 : 0;

                update_option( 'vg_post_embeds_settings', $settings );

                $this->notifyMsg(
                    __( 'Settings saved successfully!', 'post-embeds' )
                );
            }
        }

        /*
         * Get value of a single setting
         *
         * @since 0.0.1
         * @param string $setting Single setting stored in vg_post_embeds_settings option
         * @param string $default Default value when not saved in database
         *
         * return mixed
         */
        public function singleSetting( $setting, $default )
        {
            $all        = get_option( 'vg_post_embeds_settings' );
            $setting    = isset( $all[$setting] ) && ! empty( $all[$setting] ) ? esc_html( $all[$setting] ) : $default;

            return $setting;
        }

        /*
         * Success or error messages
         *
         * @since 0.0.1
         * @param string $text The message to display
         * @param string $type 'error' or 'success' (default)
         */
        public function notifyMsg( $text, $type = 'success' )
        {
            if( $type === 'success' ) {
                $class = 'updated fade';
            } else {
                $class = 'error';
            }

            ?>
            <div id="message" class="<?php echo $class ?>">
                <p>
                    <?php echo $text; ?>
                </p>
            </div>
            <?php
        }

        /*
         * Set <body> classes
         *
         * @since 0.0.1
         */
        public function setBodyClasses( $classes )
        {
            unset($classes);
            $classes = ['pe-embed-responsive'];
            return $classes;
        }
    }
}
