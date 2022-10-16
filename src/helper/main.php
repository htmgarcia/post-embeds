<?php
/**
 * Post embeds class
 * Valentin Garcia
 * @htmgarcia
 * https://htmgarcia.com
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

                // Don't modify embeds if Style is 'default'
                if( $this->singleSetting( 'style', 'social-bird' ) !== 'default' ) {
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

                    // Custom hooks
                    add_action( 'vg_post_embeds_datetime', [$this, 'dateTime'] );
                    add_action( 'vg_post_embeds_readmore', [$this, 'readmore'] );
                }
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
            $style = $this->singleSetting( 'style', 'social-bird' ) !== 'default'
                ? 'custom' // custom.php template when style is different to 'default'
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
            $style      = $this->singleSetting( 'style', 'social-bird' );
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

            add_submenu_page(
                'options-general.php',
                __( 'Post Embeds' , 'post-embeds' ),
                __( 'Post Embeds' , 'post-embeds' ),
                'manage_options',
                'vg_post_embeds',
                [
                    $this, 'loadSettingsPage'
                ]
            );

        }

        /*
         * Output settings page
         */
        public function loadSettingsPage()
        {
            ?>
            <div class="wrap">
                <h1>
                    <?php esc_html_e( 'Post Embeds', 'post-embeds' ); ?>
                </h1>

                <?php
                self::saveSettings();

                $settings           = get_option( 'vg_post_embeds_settings' );

                // CSS Style
                $style              = isset( $settings['style'] ) ? esc_html( $settings['style'] ) : 'default';

                // Date & Time
                $display_date       = isset( $settings['display_date'] ) && (bool) $settings['display_date'] ? 'checked' : '';
                $display_time       = isset( $settings['display_time'] ) && (bool) $settings['display_time'] ? 'checked' : '';
                $datetime_order     = isset( $settings['datetime_order'] ) ? esc_html( $settings['datetime_order'] ) : 'time-date';

                // Read More
                $display_readmore   = isset( $settings['display_readmore'] ) && (bool) $settings['display_readmore'] ? 'checked' : '';
                $readmore_text      = isset( $settings['readmore_text'] ) ? esc_html( $settings['readmore_text'] ) : '';
                ?>

                <form method="post">
                    <?php wp_nonce_field( 'vg_post_embeds_settings_nonce', 'vg_post_embeds_settings_nonce_field' ) ?>
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <?php _e( 'Embed Style', 'post-embeds' ) ?>
                            </th>
                            <td>
                                <label>
                                    <select name="style">
                                        <option value="default"
                                            <?php echo $style === 'default' ? ' selected' : '' ?>
                                        >
                                            <?php esc_attr_e( 'Default', 'post-embeds' ) ?>
                                        </option>
                                        <option value="social-bird"
                                            <?php echo $style === 'social-bird' ? ' selected' : '' ?>
                                        >
                                            <?php esc_attr_e( 'Social Bird', 'post-embeds' ) ?>
                                        </option>
                                    </select>
                                </label>
                            </td>
                        </tr>
                    </table>

                    <hr />
                    <h2 class="title">
                        <?php _e( 'Date & Time', 'post-embeds' ) ?>
                    </h2>

                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <?php _e( 'Display Post Date', 'post-embeds' ) ?>
                            </th>
                            <td>
                                <label>
                                    <input type="checkbox" name="display_date" value="1"
                                        <?php esc_attr_e( $display_date ) ?>
                                    />
                                </label>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <?php _e( 'Display Post Time', 'post-embeds' ) ?>
                            </th>
                            <td>
                                <label>
                                    <input type="checkbox" name="display_time" value="1"
                                        <?php esc_attr_e( $display_time ) ?>
                                    />
                                </label>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <?php _e( 'Date & Time Order', 'post-embeds' ) ?>
                            </th>
                            <td>
                                <label>
                                    <select name="datetime_order">
                                        <option value="time-date"
                                            <?php echo sanitize_text_field( $datetime_order ) === 'time-date' ? ' selected' : '' ?>
                                        >
                                            <?php
                                            printf(
                                                esc_attr__( 'time %s date', 'post-embeds' ),
                                                '&bullet;'
                                            );
                                            ?>
                                        </option>
                                        <option value="date-time"
                                            <?php echo sanitize_text_field( $datetime_order ) === 'date-time' ? ' selected' : '' ?>
                                        >
                                            <?php
                                            printf(
                                                esc_attr__( 'date %s time', 'post-embeds' ),
                                                '&bullet;'
                                            );
                                            ?>
                                        </option>
                                    </select>
                                </label>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <?php _e( 'Date & Time Format', 'post-embeds' ) ?>
                            </th>
                            <td>
                                <p>
                                    <?php
                                    printf(
                                        __( 'Check %3$sDate Format%4$s and %3$sTime Format%4$s in %1$sSettings%2$s.', 'post-embeds' ),
                                        '<a href="' . admin_url( 'options-general.php' ) . '" target="_blank">',
                                        '</a>',
                                        '<strong>',
                                        '</strong>'
                                    );
                                    ?>
                                </p>
                            </td>
                        </tr>
                    </table>

                    <hr />
                    <h2 class="title">
                        <?php _e( 'Read More', 'post-embeds' ) ?>
                    </h2>

                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <?php _e( 'Display Read More', 'post-embeds' ) ?>
                            </th>
                            <td>
                                <label>
                                    <input type="checkbox" name="display_readmore" value="1"
                                        <?php esc_attr_e( $display_readmore ) ?>
                                    />
                                </label>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <?php _e( 'Read More Text', 'post-embeds' ) ?>
                            </th>
                            <td>
                                <label>
                                    <input type="text" name="readmore_text"
                                           class="regular-text"
                                           value="<?php esc_html_e( $readmore_text ) ?>"
                                    />
                                </label>
                                <p class="description">
                                    <?php
                                    _e( 'Replace default "Read More" text.',
                                        'post-embeds'
                                    );
                                    ?>
                                </p>
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

                $settings                       = get_option( 'vg_post_embeds_settings' );

                // CSS Style
                $settings['style']              = isset( $_POST['style'] ) && ! empty( $_POST['style'] ) ? sanitize_text_field( $_POST['style'] ) : 'social-bird';

                // Date & Time
                $settings['display_date']       = isset( $_POST['display_date'] ) ? 1 : 0;
                $settings['display_time']       = isset( $_POST['display_time'] ) ? 1 : 0;
                $settings['datetime_order']     = isset( $_POST['datetime_order'] ) && ! empty( $_POST['datetime_order'] ) ? sanitize_text_field( $_POST['datetime_order'] ) : 'time-date';

                // Readmore
                $settings['display_readmore']   = isset( $_POST['display_readmore'] ) ? 1 : 0;
                $settings['readmore_text']      = isset( $_POST['readmore_text'] ) && ! empty( $_POST['readmore_text'] ) ? sanitize_text_field( $_POST['readmore_text'] ) : '';

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
            $setting    = isset( $all[$setting] ) ? esc_html( $all[$setting] ) : $default;

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
        public function footerEmbed() {
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
        public function footerMeta() {
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
        public function siteLogo( $site_title ) {
        	$site_title = sprintf(
        		'<a href="%s" target="_top"><img src="%s" srcset="%s 2x" width="52" height="52" alt="" class="pe-embed-site-icon" /></a>',
        		esc_url( home_url() ),
        		esc_url( get_site_icon_url( 52, includes_url( 'images/w-logo-blue.png' ) ) ),
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
        public function commentsMeta() {
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
        public function loadScripts() {
        	wp_print_inline_script_tag(
        		file_get_contents( VG_POST_EMBEDS_DIR . '/assets/js/script.js' )
        	);
        }

        /**
         * Date & Time embed output
         *
         * @since 0.0.1
         */
        public function dateTime() {

            $display_date = (bool) $this->singleSetting( 'display_date', 1 );
            $display_time = (bool) $this->singleSetting( 'display_time', 1 );

            if( $display_date && $display_time ) {
                // Display date and time
                $datetime_order = $this->singleSetting( 'datetime_order', 'date-time' );

                if( $datetime_order === 'time-date' ) {
                    $format = '%1$s &bullet; %2$s';
                } else {
                    $format = '%2$s &bullet; %1$s';
                }

                $output = sprintf(
                    $format,
                    get_the_date( get_option('time_format') ),
                    get_the_date( get_option('date_format') )
                );
            } elseif( $display_date && ! $display_time ) {
                // Display only date
                $output = get_the_date( get_option('date_format') );
            } elseif( ! $display_date && $display_time ) {
                // Display only time
                $output = get_the_date( get_option('time_format') );
            } else {
                // Empty string when nothing will be displayed
                $output = '';
            }

            if( ! empty( $output ) ) {
                ?>
                <div class="pe-date">
                    <p><?php echo $output ?></p>
                </div>
                <?php
            }
        }

        /**
         * Readmore embed output
         *
         * @since 0.0.1
         */
        public function readmore()
        {
            global $post;

            $display_readmore   = (bool) $this->singleSetting( 'display_readmore', 1 );
            $readmore_text      = $this->singleSetting( 'readmore_text', '' );

            if( $display_readmore ) {
                ?>
                <div class="pe-readmore">
                    <p>
                         <a href="<?php the_permalink( $post ); ?>" target="_top">
                             <?php
                             if( ! empty( $readmore_text ) ) {
                                 esc_html_e( $readmore_text );
                             } else {
                                 esc_html_e( 'Read More', 'post-embeds' );
                             }
                             ?>
                         </a>
                    </p>
                </div>
                <?php
            }
        }
    }
}
