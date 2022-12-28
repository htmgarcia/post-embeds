<?php
/**
 * Post embeds class
 * Valentin Garcia
 * @htmgarcia
 * https://htmgarcia.com
 */

defined( 'ABSPATH' ) || die;

if( ! class_exists( 'vgPostEmbedsCustomizer' ) ) {

    class vgPostEmbedsCustomizer
    {
        public function __construct()
        {
            if( is_admin() ) {
                add_action( 'admin_menu', [$this, 'loadMenu'] );
            } else {

                add_action( 'wp', function()
                {
                    global $wp_query;

                    $enabled_post_types = self::supportedPostTypes();

                    // Check if current post is embed and post type is supported
                    if( $wp_query->is_embed && in_array( $wp_query->posts[0]->post_type, $enabled_post_types ) ) {

                        // Don't modify embeds when using default style (core embed design)
                        if( $this->singleSetting( 'style', 'social-bird' ) !== 'default' ) {
                            remove_action( 'embed_head', 'print_embed_styles' );
                            remove_action( 'embed_content_meta', 'print_embed_comments_button' );
                            remove_action( 'embed_content_meta', 'print_embed_sharing_button' );
                            remove_action( 'embed_footer', 'print_embed_sharing_dialog' );
                            remove_action( 'embed_footer', 'print_embed_scripts' );

                            add_action( 'embed_content_meta', [$this, 'footerMeta'] );
                            add_action( 'embed_content_meta', [$this, 'commentsMeta'] );
                            add_action( 'embed_footer', [$this, 'footerEmbed'] );
                            add_action( 'embed_footer', [$this, 'loadScripts'] );
                        }

                        add_action( 'embed_head', [$this, 'loadStyles'] );

                        add_filter( 'embed_template', [$this, 'loadEmbedTemplate'], 9999 );
                        add_filter( 'body_class', [$this, 'setBodyClasses'], 9999 );
                        add_filter( 'embed_site_title_html', [$this, 'siteLogo'], 9999 );
                        add_filter( 'excerpt_more', [$this, 'continueReading'], 9999 );

                        // Custom hooks
                        add_action( 'vg_post_embeds_datetime', [$this, 'dateTime'] );
                        add_action( 'vg_post_embeds_readmore', [$this, 'readmore'] );
                        add_action( 'vg_post_embeds_author', [$this, 'author'] );
                    }
                } );
            }
        }

        /*
         * Get supported post types according to settings
         *
         * @since 1.0.0
         * @return array Enabled post types
         */
        private function supportedPostTypes()
        {
            $settings           = get_option( 'vg_post_embeds_settings' );
            $post_types         = isset( $settings['post_types'] ) && is_array( $settings['post_types'] )
                                    ? $settings['post_types']
                                    : ['post' => 1, 'page' => 1];
            $enabled_post_types = [];

            foreach( $post_types as $post_type => $key ) {
                if( (bool) $key ) {
                    $enabled_post_types[] = $post_type;
                }
            }

            return $enabled_post_types;
        }

        /*
         * Override core/theme embed template
         *
         * @since 1.0.0
         * @param string $template The template file
         */
        public function loadEmbedTemplate( $template )
        {
            $style      = $this->singleSetting( 'style', 'social-bird' );
            $template   = file_exists( VG_POST_EMBEDS_DIR . '/templates/' . $style . '.php' )
                            ? VG_POST_EMBEDS_DIR . '/templates/' . $style . '.php'
                            : VG_POST_EMBEDS_DIR . '/templates/default.php';

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
            $css        = file_get_contents( dirname( __FILE__ ) . '/../assets/css/' . esc_html( $style ) . '.css' );

            printf(
                '<style%s>%s</style>',
                $type_attr, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                $css // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		    );
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

                $settings                   = get_option( 'vg_post_embeds_settings' );

                // CSS Style
                $style                      = isset( $settings['style'] ) ? esc_html( $settings['style'] ) : 'default';

                // Post Types
                $post_types['post']         = isset( $settings['post_types']['post'] ) && (bool) $settings['post_types']['post'] ? 'checked' : '';
                $post_types['page']         = isset( $settings['post_types']['page'] ) && (bool) $settings['post_types']['page'] ? 'checked' : '';

                // Date
                $display_date['post']       = isset( $settings['display_date']['post'] ) && (bool) $settings['display_date']['post'] ? 'checked' : '';
                $display_date['page']       = isset( $settings['display_date']['page'] ) && (bool) $settings['display_date']['page'] ? 'checked' : '';

                // Time
                $display_time['post']       = isset( $settings['display_time']['post'] ) && (bool) $settings['display_time']['post'] ? 'checked' : '';
                $display_time['page']       = isset( $settings['display_time']['page'] ) && (bool) $settings['display_time']['page'] ? 'checked' : '';

                // Date & Time Order
                $datetime_order             = isset( $settings['datetime_order'] ) ? esc_html( $settings['datetime_order'] ) : 'time-date';

                // Read More
                $display_readmore['post']   = isset( $settings['display_readmore']['post'] ) && (bool) $settings['display_readmore']['post'] ? 'checked' : '';

                // Read More Text
                $readmore_text              = isset( $settings['readmore_text'] ) ? esc_html( $settings['readmore_text'] ) : '';

                // Author
                $display_author['post']     = isset( $settings['display_author']['post'] ) && (bool) $settings['display_author']['post'] ? 'checked' : '';
                ?>

                <form method="post">
                    <?php wp_nonce_field( 'vg_post_embeds_settings_nonce', 'vg_post_embeds_settings_nonce_field' ) ?>
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <?php _e( 'Supported Post Types', 'post-embeds' ) ?>
                            </th>
                            <td>
                                <fieldset>
                                    <p>
                                        <label>
                                            <input type="checkbox" name="post_types[post]" value="1"
                                                <?php esc_attr_e( $post_types['post'] ) ?>
                                            />
                                            <?php _e( 'Post', 'post-embeds' ) ?>
                                        </label><br/>
                                        <label>
                                            <input type="checkbox" name="post_types[page]" value="1"
                                                <?php esc_attr_e( $post_types['page'] ) ?>
                                            />
                                            <?php _e( 'Page', 'post-embeds' ) ?>
                                        </label>
                                    </p>
                                    <p class="description">
                                        <?php
                                        _e( 'The settings below will apply to the checked post types.',
                                            'post-embeds'
                                        );
                                        ?>
                                    </p>
                                </fieldset>
                            </td>
                        </tr>
                    </table>

                    <hr />

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
                                        <option value="social-mark"
                                            <?php echo $style === 'social-mark' ? ' selected' : '' ?>
                                        >
                                            <?php esc_attr_e( 'Social Mark', 'post-embeds' ) ?>
                                        </option>
                                    </select>
                                </label>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <?php _e( 'Display Post Date', 'post-embeds' ) ?>
                            </th>
                            <td>
                                <fieldset>
                                    <p>
                                        <label>
                                            <input type="checkbox" name="display_date[post]" value="1"
                                                <?php esc_attr_e( $display_date['post'] ) ?>
                                            />
                                            <?php _e( 'Post', 'post-embeds' ) ?>
                                        </label><br/>
                                        <label>
                                            <input type="checkbox" name="display_date[page]" value="1"
                                                <?php esc_attr_e( $display_date['page'] ) ?>
                                            />
                                            <?php _e( 'Page', 'post-embeds' ) ?>
                                        </label>
                                    </p>
                                    <p class="description">
                                        <?php
                                        _e( 'The post date will be displayed to the checked post types.',
                                            'post-embeds'
                                        );
                                        ?>
                                    </p>
                                </fieldset>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <?php _e( 'Display Post Time', 'post-embeds' ) ?>
                            </th>
                            <td>
                                <fieldset>
                                    <p>
                                        <label>
                                            <input type="checkbox" name="display_time[post]" value="1"
                                                <?php esc_attr_e( $display_time['post'] ) ?>
                                            />
                                            <?php _e( 'Post', 'post-embeds' ) ?>
                                        </label><br/>
                                        <label>
                                            <input type="checkbox" name="display_time[page]" value="1"
                                                <?php esc_attr_e( $display_time['page'] ) ?>
                                            />
                                            <?php _e( 'Page', 'post-embeds' ) ?>
                                        </label>
                                    </p>
                                    <p class="description">
                                        <?php
                                        _e( 'The post time will be displayed to the checked post types.',
                                            'post-embeds'
                                        );
                                        ?>
                                    </p>
                                </fieldset>
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
                                        esc_html__(
                                            'Check %3$sDate Format%4$s and %3$sTime Format%4$s in %1$sSettings%2$s.',
                                            'post-embeds'
                                        ),
                                        '<a href="' . esc_url( admin_url( 'options-general.php' ) ) . '" target="_blank">',
                                        '</a>',
                                        '<strong>',
                                        '</strong>'
                                    );
                                    ?>
                                </p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <?php _e( 'Display Read More', 'post-embeds' ) ?>
                            </th>
                            <td>
                                <fieldset>
                                    <p>
                                        <label>
                                            <input type="checkbox" name="display_readmore[post]" value="1"
                                                <?php esc_attr_e( $display_readmore['post'] ) ?>
                                            />
                                            <?php _e( 'Post', 'post-embeds' ) ?>
                                        </label>
                                    </p>
                                    <p class="description">
                                        <?php
                                        _e( 'The read more will be displayed to the checked post types.',
                                            'post-embeds'
                                        );
                                        ?>
                                    </p>
                                </fieldset>
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
                                           value="<?php esc_attr_e( $readmore_text ) ?>"
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
                        <tr>
                            <th scope="row">
                                <?php _e( 'Display Author', 'post-embeds' ) ?>
                            </th>
                            <td>
                                <fieldset>
                                    <p>
                                        <label>
                                            <input type="checkbox" name="display_author[post]" value="1"
                                                <?php esc_attr_e( $display_author['post'] ) ?>
                                            />
                                            <?php _e( 'Post', 'post-embeds' ) ?>
                                        </label>
                                    </p>
                                    <p class="description">
                                        <?php
                                        _e( 'The author will be displayed to the checked post types.',
                                            'post-embeds'
                                        );
                                        ?>
                                    </p>
                                </fieldset>
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
         * @since 1.0.0
         */
        private function saveSettings()
        {
            if ( ! current_user_can( 'activate_plugins' ) ) {
                return false;
            }

            if ( isset( $_POST['save'] ) ) // phpcs:ignore WordPress.Security.NonceVerification.Missing -- we check nonce below
            {
                if ( ! isset( $_POST['vg_post_embeds_settings_nonce_field'] )
                    || ! wp_verify_nonce(
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

                $settings                               = get_option( 'vg_post_embeds_settings' );

                // CSS Style
                $settings['style']                      = isset( $_POST['style'] ) && ! empty( $_POST['style'] ) ? sanitize_text_field( $_POST['style'] ) : 'social-bird';

                // Post Types
                $settings['post_types']['post']         = isset( $_POST['post_types']['post'] ) ? 1 : 0;
                $settings['post_types']['page']         = isset( $_POST['post_types']['page'] ) ? 1 : 0;

                // Date
                $settings['display_date']['post']       = isset( $_POST['display_date']['post'] ) ? 1 : 0;
                $settings['display_date']['page']       = isset( $_POST['display_date']['page'] ) ? 1 : 0;

                // Time
                $settings['display_time']['post']       = isset( $_POST['display_time']['post'] ) ? 1 : 0;
                $settings['display_time']['page']       = isset( $_POST['display_time']['page'] ) ? 1 : 0;

                // Date & Time Order
                $settings['datetime_order']             = isset( $_POST['datetime_order'] ) && ! empty( $_POST['datetime_order'] ) ? sanitize_text_field( $_POST['datetime_order'] ) : 'time-date';

                // Read more
                $settings['display_readmore']['post']   = isset( $_POST['display_readmore']['post'] ) ? 1 : 0;

                // Read More Text
                $settings['readmore_text']              = isset( $_POST['readmore_text'] ) && ! empty( $_POST['readmore_text'] ) ? sanitize_text_field( $_POST['readmore_text'] ) : '';

                // Author
                $settings['display_author']['post']     = isset( $_POST['display_author']['post'] ) ? 1 : 0;

                update_option( 'vg_post_embeds_settings', $settings );

                $this->notifyMsg(
                    __( 'Settings saved successfully!', 'post-embeds' )
                );
            }
        }

        /*
         * Get value of a single setting
         *
         * @since 1.0.0
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
         * @since 1.0.0
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
            <div id="message" class="<?php esc_attr_e( $class ) ?>">
                <p>
                    <?php esc_html_e( $text ); ?>
                </p>
            </div>
            <?php
        }

        /*
         * Set <body> classes
         *
         * @since 1.0.0
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
         * @since 1.0.0
         */
        public function footerEmbed()
        {
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
         * @since 1.0.0
         */
        public function footerMeta()
        {
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
         * @since 1.0.0
         */
        public function siteLogo( $site_title )
        {
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
         * Remove native "Continue Reading" link
         *
         * @since 1.0.0
         *
         * @param string $more_string Default 'more' string.
         * @return string 'Continue reading' link or empty
         */
        public function continueReading( $more_string )
        {
            global $post;

            $enabled_post_types = self::supportedPostTypes();

            // Check if post type from current post is supported
            if( in_array( $post->post_type, $enabled_post_types ) ) {
                return '&hellip;';
            }

            return $more_string;
        }

        /**
         * Output comments counter
         *
         * @since 1.0.0
         */
        public function commentsMeta()
        {
        	if ( is_404() || ! ( get_comments_number() || comments_open() ) ) {
        		return;
        	}
        	?>
        	<div class="pe-embed-comments">
        		<a href="<?php comments_link(); ?>" target="_top">
        			<span class="dashicons dashicons-admin-comments"></span>
        			<?php
        			printf(
        				/* translators: %s: Number of comments.*/
        				_n( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        					'%s <span class="screen-reader-text">Comment</span>',
        					'%s <span class="screen-reader-text">Comments</span>',
        					get_comments_number()
        				),
        				esc_html( number_format_i18n( get_comments_number() ) )
        			);
        			?>
        		</a>
        	</div>
        	<?php
        }

        /**
         * Output the JavaScript
         *
         * @since 1.0.0
         */
        public function loadScripts()
        {
        	wp_print_inline_script_tag(
        		file_get_contents( VG_POST_EMBEDS_DIR . '/assets/js/script.js' )
        	);
        }

        /**
         * Date & Time embed output
         *
         * @since 1.0.0
         */
        public function dateTime()
        {
            global $post;

            $post_type = $post->post_type;

            if( ! in_array( $post_type, ['post','page'] ) ) {
                return;
            }

            $settings       = get_option( 'vg_post_embeds_settings' );
            $display_date   = isset( $settings['display_date'][$post_type] )
                            && (bool) $settings['display_date'][$post_type]
                                ? 1
                                : 0;
            $display_time   = isset( $settings['display_time'][$post_type] )
                            && (bool) $settings['display_time'][$post_type]
                                ? 1
                                : 0;

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
                    <p>
                        <?php
                        echo $output // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                        ?>
                    </p>
                </div>
                <?php
            }
        }

        /**
         * Read more embed output
         *
         * @since 1.0.0
         */
        public function readmore()
        {
            global $post;

            if( $post->post_type !== 'post' ) {
                return;
            }

            $settings                   = get_option( 'vg_post_embeds_settings' );
            $display_readmore['post']   = isset( $settings['display_readmore']['post'] ) && (bool) $settings['display_readmore']['post'] ? 1 : 0;
            $readmore_text              = $this->singleSetting( 'readmore_text', '' );

            if( $display_readmore['post'] ) {
                ?>
                <div class="pe-readmore">
                    <p>
                         <a href="<?php the_permalink( $post ); ?>" target="_top">
                             <?php
                             if( ! empty( $readmore_text ) ) {
                                 esc_html_e( $readmore_text );
                             } else {
                                 esc_html_e( 'Read more', 'post-embeds' );
                             }
                             ?>
                         </a>
                    </p>
                </div>
                <?php
            }
        }

        /**
         * Author embed output
         *
         * @since 1.0.0
         */
        public function author()
        {
            global $post;

            if( $post->post_type !== 'post' ) {
                return;
            }

            $settings       = get_option( 'vg_post_embeds_settings' );
            $display_author = isset( $settings['display_author']['post'] ) && (bool) $settings['display_author']['post'] ? 1 : 0;

            if( $display_author ) {
                $author_name    = get_the_author_meta( 'display_name' , $post->post_author );
                $author_url     = get_author_posts_url( $post->post_author );
                ?>
                <div class="pe-author">
                    <a href="<?php echo esc_url( $author_url ) ?>">
                        <?php esc_html_e( $author_name ) ?>
                    </a>
                </div>
                <?php
            }
        }
    }
}
