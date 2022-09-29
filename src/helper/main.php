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

                remove_action( 'embed_head', 'print_embed_styles' );
                remove_action( 'embed_content_meta', 'print_embed_comments_button' );
                remove_action( 'embed_content_meta', 'print_embed_sharing_button' );
                remove_action( 'embed_footer', 'print_embed_sharing_dialog' );
                remove_action( 'embed_footer', 'print_embed_scripts' );

                add_filter( 'embed_template', [$this, 'loadEmbedTemplate'], 9999 );
                add_filter( 'body_class', [$this, 'setBodyClasses'], 9999 );
                add_filter( 'embed_site_title_html', [$this, 'siteLogo'], 9999 );

                add_action( 'embed_head', [$this, 'loadStyles'] );
                add_action( 'embed_content_meta', [$this, 'footerMeta'] );
                add_action( 'embed_content_meta', [$this, 'commentsMeta'] );
                add_action( 'embed_footer', [$this, 'footerEmbed'] );
                add_action( 'embed_footer', [$this, 'loadScripts'] );
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
            $style = $this->singleSetting( 'style', 'custom' ) !== 'default'
                ? 'custom'
                : 'default';
            $template = VG_POST_EMBEDS_DIR . '/templates/' . $style . '.php';

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
                $style          = isset( $settings['style'] ) ? sanitize_text_field( $settings['style'] ) : 'default';
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
                                        <option value="social"
                                            <?php echo sanitize_text_field( $style ) === 'social' ? ' selected' : '' ?>
                                        >
                                            <?php esc_attr_e( 'Social', 'post-embeds' ) ?>
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

        /**
         * Footer output
         *
         * @since 0.0.1
         */
        function footerEmbed() {
        	if ( is_404() ) {
        		return;
        	}
        	?>
        	<div class="pe-embed-share-dialog hidden" role="dialog" aria-label="<?php esc_attr_e( 'Sharing options' ); ?>">
        		<div class="pe-embed-share-dialog-content">
        			<div class="pe-embed-share-dialog-text">
        				<ul class="pe-embed-share-tabs" role="tablist">
        					<li class="pe-embed-share-tab-button pe-embed-share-tab-button-wordpress" role="presentation">
        						<button type="button" role="tab" aria-controls="pe-embed-share-tab-wordpress" aria-selected="true" tabindex="0"><?php esc_html_e( 'WordPress Embed' ); ?></button>
        					</li>
        					<li class="pe-embed-share-tab-button pe-embed-share-tab-button-html" role="presentation">
        						<button type="button" role="tab" aria-controls="pe-embed-share-tab-html" aria-selected="false" tabindex="-1"><?php esc_html_e( 'HTML Embed' ); ?></button>
        					</li>
        				</ul>
        				<div id="pe-embed-share-tab-wordpress" class="pe-embed-share-tab" role="tabpanel" aria-hidden="false">
        					<input type="text" value="<?php the_permalink(); ?>" class="pe-embed-share-input" aria-describedby="pe-embed-share-description-wordpress" tabindex="0" readonly/>

        					<p class="pe-embed-share-description" id="pe-embed-share-description-wordpress">
        						<?php _e( 'Copy and paste this URL into your WordPress site to embed' ); ?>
        					</p>
        				</div>
        				<div id="pe-embed-share-tab-html" class="pe-embed-share-tab" role="tabpanel" aria-hidden="true">
        					<textarea class="pe-embed-share-input" aria-describedby="pe-embed-share-description-html" tabindex="0" readonly><?php echo esc_textarea( get_post_embed_html( 600, 400 ) ); ?></textarea>

        					<p class="pe-embed-share-description" id="pe-embed-share-description-html">
        						<?php _e( 'Copy and paste this code into your site to embed' ); ?>
        					</p>
        				</div>
        			</div>

        			<button type="button" class="pe-embed-share-dialog-close" aria-label="<?php esc_attr_e( 'Close sharing dialog' ); ?>">
        				<span class="dashicons dashicons-no"></span>
        			</button>
        		</div>
        	</div>
        	<?php
        }

        /**
         * Footer meta output
         *
         * @since 0.0.1
         */
        function footerMeta() {
            if ( is_404() ) {
        		return;
        	}
        	?>
        	<div class="pe-embed-share">
        		<button type="button" class="pe-embed-share-dialog-open" aria-label="<?php esc_attr_e( 'Open sharing dialog' ); ?>">
        			<span class="dashicons dashicons-share"></span>
        		</button>
        	</div>
        	<?php
        }

        /**
         * Site logo output
         *
         * @since 0.0.1
         */
        function siteLogo( $site_title ) {
        	$site_title = sprintf(
        		'<a href="%s" target="_top"><img src="%s" srcset="%s 2x" width="32" height="32" alt="" class="pe-embed-site-icon" /></a>',
        		esc_url( home_url() ),
        		esc_url( get_site_icon_url( 32, includes_url( 'images/w-logo-blue.png' ) ) ),
        		esc_url( get_site_icon_url( 64, includes_url( 'images/w-logo-blue.png' ) ) )
        	);

        	$site_title = '<div class="pe-embed-site-title">' . $site_title . '</div>';

            return $site_title;
        }

        /**
         * Output comments counter
         *
         * @since 0.0.1
         */
        function commentsMeta() {
        	if ( is_404() || ! ( get_comments_number() || comments_open() ) ) {
        		return;
        	}
        	?>
        	<div class="pe-embed-comments">
        		<a href="<?php comments_link(); ?>" target="_top">
        			<span class="dashicons dashicons-admin-comments"></span>
        			<?php
        			printf(
        				/* translators: %s: Number of comments. */
        				_n(
        					'%s <span class="screen-reader-text">Comment</span>',
        					'%s <span class="screen-reader-text">Comments</span>',
        					get_comments_number()
        				),
        				number_format_i18n( get_comments_number() )
        			);
        			?>
        		</a>
        	</div>
        	<?php
        }

        /**
         * Output the JavaScript
         *
         * @since 0.0.1
         */
        function loadScripts() {
        	wp_print_inline_script_tag(
        		file_get_contents( VG_POST_EMBEDS_DIR . '/assets/js/script.js' )
        	);
        }
    }
}
