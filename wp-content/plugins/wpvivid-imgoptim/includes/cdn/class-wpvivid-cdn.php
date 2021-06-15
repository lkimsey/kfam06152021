<?php

if (!defined('WPVIVID_IMGOPTIM_DIR'))
{
    die;
}

class WPvivid_CDN
{
    public $options;

    function __construct()
    {
        $options=get_option('wpvivid_optimization_options',array());
        $enable=isset($options['cdn']['enable'])?$options['cdn']['enable']:false;
        if($enable)
        {
            add_action( 'template_redirect', array( $this, 'handle_rewrite_hook' ) );
            add_filter( 'the_content', array( $this, 'rewrite_the_content' ), 100 );

            add_action( 'send_headers', array( $this, 'add_headers' ), 0 );
        }
    }

    public function handle_rewrite_hook()
    {
        if(apply_filters('wpvivid_imgoptim_already_enable_cdn',false))
        {
            return;
        }
        ob_start( array( $this, 'rewrite' ) );
    }

    public function rewrite_the_content( $html )
    {
        if(apply_filters('wpvivid_imgoptim_already_enable_cdn',false))
        {
            return $html;
        }
        return $this->rewrite( $html );
    }

    public function add_headers()
    {
        // Add client hints.
        if(apply_filters('wpvivid_imgoptim_already_enable_cdn',false))
        {
            return;
        }
        header( 'Accept-CH: viewport-width, width, device-memory, dpr, downlink, ect' );
        $url = get_option( 'home' );
        $host    = wp_parse_url( $url, PHP_URL_HOST );
        if ( ! empty( $host ) ) {
            $protocol = (is_ssl()) ? "https://" : "http://";
            header( 'Link: <'.$protocol.$host.'>; rel=preconnect' );
            header( 'Feature-Policy: ch-viewport-width '.$protocol.$host.'; ch-width '.$protocol.$host.'; ch device-memory '.$protocol.$host.'; ch-dpr '.$protocol.$host.'; ch-downlink '.$protocol.$host.'; ch-ect '.$protocol.$host.';' );
        }
    }

    public function rewrite( $html )
    {
        $options=get_option('wpvivid_optimization_options',array());

        $this->options=isset($options['cdn'])?$options['cdn']:array();
        $this->options['cdn_url']=isset($options['cdn']['cdn_url'])?$options['cdn']['cdn_url']:get_site_url();
        $this->options['include_dir']=isset($options['cdn']['include_dir'])?$options['cdn']['include_dir']:'wp-content,wp-includes';
        $this->options['exclusions']=isset($options['cdn']['exclusions'])?$options['cdn']['exclusions']:'.php,.js,.css';
        $this->options['relative_path']=isset($options['cdn']['relative_path'])?$options['cdn']['relative_path']:true;
        $this->options['cdn_https']=isset($options['cdn']['cdn_https'])?$options['cdn']['cdn_https']:false;
        //$this->options['cdn_og_url']=isset($options['cdn']['cdn_og_url'])?$options['cdn']['cdn_og_url']:get_option('home');
        // Check if HTTPS and use cdn over HTTPS enabled.
        if ( !  $this->options['cdn_https'] && isset( $_SERVER['HTTPS'] ) && 'on' === $_SERVER['HTTPS'] )
        {
            return $html;
        }

        // Get dir scope in regex format.
        $dirs     = $this->get_dir_scope();
        $blog_url =  $this->options['cdn_https']
            ? '(https?:|)' . $this->relative_url( preg_quote( get_option('home'), '#' ) )
            : '(http:|)' . $this->relative_url( preg_quote( get_option('home'), '#' ) );

        // Regex rule start.
        $regex_rule = '#(?<=[(\"\'])';

        // Check if relative paths.
        if ($this->options['relative_path'] ) {
            $regex_rule .= '(?:' . $blog_url . ')?';
        } else {
            $regex_rule .= $blog_url;
        }

        // Regex rule end.
        $regex_rule .= '/(?:((?:' . $dirs . ')[^\"\')]+)|([^/\"\']+\.[^/\"\')]+))(?=[\"\')])#';

        // Call the cdn rewriter callback.
        $cdn_html = preg_replace_callback( $regex_rule, array( $this, 'rewrite_url' ), $html );

        return $cdn_html;
    }

    protected function get_dir_scope()
    {
        $dirs = trim( $this->options['include_dir'], ' ,' );
        if ( empty( $dirs ) )
        {
            $dirs = trim( 'wp-content,wp-includes', ' ,' );
        }

        $input = explode( ',', $dirs );

        return implode(
            '|',
            array_map(
                function ( $in ) {
                    $in = trim( $in );
                    $in = preg_quote( $in, '#' );
                    return $in;
                },
                $input
            )
        );
    }

    protected function relative_url( $url )
    {
        return substr( $url, strpos( $url, '//' ) );
    }

    protected function rewrite_url( $asset )
    {
        $asset_url = $asset[0];

        if ($this->exclude_asset($asset_url))
        {
            return $asset_url;
        }


        // Don't rewrite if in preview mode.
        if ( is_admin_bar_showing()
            && array_key_exists( 'preview', $_GET ) // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            && 'true' === $_GET['preview'] ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            return $asset_url;
        }

        // Prepend the path in case this installation is not at /.
        $blog_url = $this->relative_url(get_option('home'));
        $subst_urls = array( 'http:' . $blog_url );

        // Rewrite both http and https URLs if we ticked 'enable cdn for HTTPS connections'.
        if ( $this->options['cdn_https'] ) {
            $subst_urls[] = 'https:' . $blog_url;
        }

        // Is it a relative-protocol URL?.
        if ( strpos( $asset_url, '//' ) === 0 ) {
            return str_replace( $blog_url,  $this->options['cdn_url'], $asset_url );
        }

        // Check if not a relative path.
        if ( ! $this->options['relative_path'] || strstr( $asset_url, $blog_url ) ) {
            return str_replace( $subst_urls,  $this->options['cdn_url'], $asset_url );
        }

        // Relative URL.

        return  $this->options['cdn_url'] . $asset_url;
    }

    protected function exclude_asset( $asset )
    {
        // Excludes.
        $exclusions = trim( $this->options['exclusions'], ' ,' );

        $excludes = explode( ',', $exclusions );

        foreach ( $excludes as $exclude )
        {
            if ( $exclude && stripos( $asset, $exclude ) !== false )
            {
                return true;
            }
        }
        return false;
    }

    public function get_dashboard_screens($screens)
    {
        $screen['menu_slug']='wpvivid-backup';
        $screen['screen_id']='wpvivid-plugin_page_wpvivid-cdn';
        $screen['is_top']=false;
        $screens[]=$screen;
        return $screens;
    }

    public function get_dashboard_menu($submenus,$parent_slug)
    {
        $submenu['parent_slug'] = $parent_slug;
        $submenu['page_title'] = apply_filters('wpvivid_white_label_display', 'Lazyload');
        $submenu['menu_title'] = 'cdn Integration';
        $submenu['capability'] = 'administrator';
        $submenu['menu_slug'] = strtolower(sprintf('%s-cdn', apply_filters('wpvivid_white_label_slug', 'wpvivid')));
        $submenu['index'] = 9;
        $submenu['function'] = array($this, 'display');
        $submenus[$submenu['menu_slug']] = $submenu;
        return $submenus;
    }

    public function display()
    {
        ?>
        <div class="wrap">
            <div id="icon-options-general" class="icon32"></div>
            <h1><?php esc_attr_e( 'WPvivid Plugins - cdn', 'wpvivid' ); ?></h1>
            <div id="poststuff">
                <div id="post-body" class="metabox-holder columns-2">
                    <div id="post-body-content">
                        <div class="meta-box-sortables ui-sortable">
                            <div class="wpvivid-backup">
                                <?php
                                $this->welcome_bar();
                                ?>
                                <div class="wpvivid-canvas wpvivid-clear-float">
                                    <div class="wpvivid-one-coloum">
                                        <?php
                                        $this->setting();
                                        ?>
                                    </div>
                                </div>

                            </div>
                        </div>
                    </div>
                    <?php $this->sidebar();?>
                </div>
            </div>
        </div>
        <?php
    }

    public function welcome_bar()
    {
        ?>
        <div class="wpvivid-welcome-bar wpvivid-clear-float">
            <div class="wpvivid-welcome-bar-left">

                <p><span class="dashicons dashicons-admin-site wpvivid-dashicons-large wpvivid-dashicons-green"></span><span class="wpvivid-page-title">CND Integration</span></p>
                <span class="about-description">The page allows to integrate a CDN service to your website to serve website content to visitors faster.</span>
            </div>
            <div class="wpvivid-welcome-bar-right">
                <p></p>
                <div style="float:right;">
                    <span>Local Time:</span>
                    <span>
                        <a href="<?php esc_attr_e(apply_filters('wpvivid_get_admin_url', '').'options-general.php'); ?>">
                            <?php
                            $offset=get_option('gmt_offset');
                            echo date("l, F d, Y H:i",time()+$offset*60*60);
                            ?>
                        </a>
                    </span>
                    <span class="dashicons dashicons-editor-help wpvivid-dashicons-editor-help wpvivid-tooltip">
                        <div class="wpvivid-left">
                            <!-- The content you need -->
                            <p>Clicking the date and time will redirect you to the WordPress General Settings page where you can change your timezone settings.</p>
                            <i></i> <!-- do not delete this line -->
                        </div>
                    </span>
                </div>
            </div>
            <div class="wpvivid-nav-bar wpvivid-clear-float">
                <span class="dashicons dashicons-lightbulb wpvivid-dashicons-orange"></span>
                <span>Integrating a CDN service can improve your website speed and <code>Google Pagespeed Insights score</code>, which is recommended.</span>
            </div>
        </div>
        <?php
    }

    public function setting()
    {
        $options=get_option('wpvivid_optimization_options',array());

        $options['cdn']=isset($options['cdn'])?$options['cdn']:array();

        $enable=isset($options['cdn']['enable'])?$options['cdn']['enable']:false;
        if($enable)
        {
            $enable='checked';
        }
        else
        {
            $enable='';
        }

        $cdn_url=isset($options['cdn']['cdn_url'])?$options['cdn']['cdn_url']:get_site_url();

        //$cdn_og_url=isset($options['cdn']['cdn_og_url'])?$options['cdn']['cdn_og_url']:get_option('home');

        $include_dir=isset($options['cdn']['include_dir'])?$options['cdn']['include_dir']:'wp-content,wp-includes';

        $exclusions=isset($options['cdn']['exclusions'])?$options['cdn']['exclusions']:'.php,.js,.css';

        $relative_path=isset($options['cdn']['relative_path'])?$options['cdn']['relative_path']:true;
        if($relative_path)
        {
            $relative_path='checked';
        }
        else
        {
            $relative_path='';
        }

        $cdn_https=isset($options['cdn']['cdn_https'])?$options['cdn']['cdn_https']:false;
        if($cdn_https)
        {
            $cdn_https='checked';
        }
        else
        {
            $cdn_https='';
        }
        ?>
        <div>
            <div>
                <label class="wpvivid-switch">
                    <input type="checkbox" option="cdn" name="enable" <?php esc_attr_e($enable); ?>>
                    <span class="wpvivid-slider wpvivid-round"></span>
                </label> <span>Enable CDN to deliver your content.</span>
            </div>
            <div style="margin:1em 0 1em 0;">
                <div style="border:1px solid #f1f1f1; margin-bottom:1em;" >
                    <div>
                        <div style="padding-left:1em;">
                            <p>
                                <span class="dashicons dashicons-admin-generic wpvivid-dashicons-green"></span><span>
                                    <strong>CDN Settings</strong>
                                </span>
                            </p>
                        </div>
                        <div class="wpvivid-two-col" style="padding-left:1em;">
                            <div style="border-left:4px solid #eee;padding-left:0.5em;padding-right:1em;">
                                <p>Please enter <code>CDN Url</code> (without trailing '/') to deliver your content via CDN service. </p>
                                <p>
                                    <input type="text" option="cdn" name="cdn_url" value="<?php esc_attr_e($cdn_url); ?>" placeholder="CDN Url,example:http://exampleCDN.com" style="width:100%;border:1px solid #aaa;">
                                </p>
                                <!--<p>Please enter <code>Origin Url</code> (without trailing '/') to deliver your content via cdn service. </p>
                                <p>
                                    <input type="text" option="cdn" name="cdn_og_url" value="<?php //esc_attr_e($cdn_og_url); ?>" placeholder="Origin Url" style="width:100%;border:1px solid #aaa;">
                                </p>-->
                            </div>

                            <div style="border-left:4px solid #eee;padding-left:0.5em;">
                                <p>
                                    <span><strong>Relative Path &  CDN Https</strong></span>
                                </p>
                                <p>
                                    <label>
                                        <input type="checkbox" checked="checked"><span>Enable CDN for relative path.</span>
                                    </label>
                                </p>
                                <p>
                                    <label>
                                        <input type="checkbox"><span>Enable CDN for https connections.</span>
                                    </label>
                                </p>
                            </div>
                        </div>
                        <div class="wpvivid-two-col" style="padding-left:1em;">
                            <div style="border-left:4px solid #eee;padding-left:0.5em;padding-right:1em;">
                                <p><span><strong>Included Directories</span></strong></p>
                                <p>Assets under the directories will be pointed to your CDN url. Separate directories by comma (,) .</p>
                                <p>
                                    <input type="text" placeholder="wp-contents,wp-includes" style="width:100%;border:1px solid #aaa;" option="cdn" name="include_dir" value="<?php esc_attr_e($include_dir); ?>">
                                </p>

                            </div>
                            <div style="border-left:4px solid #eee; padding-left:0.5em;padding-right:0.5em;">
                                <p><span><strong>Excluded Extension/Directories</span></strong></p>
                                <p>Enter the exclusions (extension and directories) separated by comma (,) .
                                <p><input type="text" placeholder=".php" style="width:100%;border:1px solid #aaa;" option="cdn" name="exclusions" value="<?php esc_attr_e($exclusions); ?>"></p>
                            </div>
                        </div>
                        <div style="clear:both;"></div>
                    </div>
                </div>
                <div><input class="button-primary" id="wpvivid_cdn_save" type="submit" value="Save changes"></div>
            </div>
        </div>
        <script>
            jQuery('#wpvivid_cdn_save').click(function()
            {
                wpvivid_cdn_save();
            });

            function wpvivid_cdn_save()
            {
                var cdn = wpvivid_ajax_data_transfer('cdn');
                var ajax_data = {
                    'action': 'wpvivid_cdn_save_addon',
                    'cdn':cdn
                };

                jQuery('#wpvivid_cdn_save').css({'pointer-events': 'none', 'opacity': '0.4'});
                wpvivid_post_request_addon(ajax_data, function (data)
                {
                    try
                    {
                        var jsonarray = jQuery.parseJSON(data);

                        jQuery('#wpvivid_cdn_save').css({'pointer-events': 'auto', 'opacity': '1'});
                        if (jsonarray.result === 'success')
                        {
                            location.reload();
                        }
                        else {
                            alert(jsonarray.error);
                        }
                    }
                    catch (err)
                    {
                        alert(err);
                        jQuery('#wpvivid_cdn_save').css({'pointer-events': 'auto', 'opacity': '1'});
                    }
                }, function (XMLHttpRequest, textStatus, errorThrown) {
                    jQuery('#wpvivid_cdn_save').css({'pointer-events': 'auto', 'opacity': '1'});
                    var error_message = wpvivid_output_ajaxerror('Update cdn setting', textStatus, errorThrown);
                    alert(error_message);
                });
            }
        </script>
        <?php
    }

    public function sidebar()
    {
        ?>
        <div id="postbox-container-1" class="postbox-container">
            <div class="meta-box-sortables ui-sortable">
                <div class="postbox  wpvivid-sidebar">
                    <h2 style="margin-top:0.5em;">
                        <span class="dashicons dashicons-book-alt wpvivid-dashicons-orange" ></span>
                        <span><?php esc_attr_e(
                                'Documentation', 'WpAdminStyle'
                            ); ?></span></h2>
                    <div class="inside" style="padding-top:0;">
                        <ul class="" >
                            <li>
                                <span class="dashicons dashicons-format-gallery  wpvivid-dashicons-grey"></span>
                                <a href="https://wpvivid.com/wpvivid-image-optimization-wordpress-plugin"><b><?php _e('Image Bulk Optimization', 'wpvivid-imgoptim'); ?></b></a>
                                <small><span style="float: right;"><a href="#" style="text-decoration: none;"><span class="dashicons dashicons-migrate wpvivid-dashicons-grey"></span></a></span></small><br>
                            </li>
                            <li><span class="dashicons dashicons-update  wpvivid-dashicons-grey"></span>
                                <a href="https://wpvivid.com/wpvivid-image-optimization-plugin-lazyload-images"><b><?php _e('Lazy Loading', 'wpvivid-imgoptim'); ?></b></a>
                                <small><span style="float: right;"><a href="#" style="text-decoration: none;"><span class="dashicons dashicons-migrate wpvivid-dashicons-grey"></span></a></span></small><br>
                            </li>
                            <li><span class="dashicons dashicons-admin-site  wpvivid-dashicons-grey"></span>
                                <a href="https://docs.wpvivid.com/wpvivid-image-optimization-pro-integrate-cdn.html"><b><?php _e('cdn Integration', 'wpvivid-imgoptim'); ?></b></a>
                                <small><span style="float: right;"><a href="#" style="text-decoration: none;"><span class="dashicons dashicons-migrate wpvivid-dashicons-grey"></span></a></span></small><br>
                            </li>
                            <li>
                                <span class="dashicons dashicons-format-image  wpvivid-dashicons-grey"></span>
                                <a href="https://docs.wpvivid.com/wpvivid-image-optimization-pro-convert-to-webp.html"><b><?php _e('Convert Images to WebP', 'wpvivid-imgoptim'); ?></b></a>
                                <small><span style="float: right;"><a href="#" style="text-decoration: none;"><span class="dashicons dashicons-migrate wpvivid-dashicons-grey"></span></a></span></small><br>
                            </li>
                        </ul>
                    </div>
                    <h2><span class="dashicons dashicons-businesswoman wpvivid-dashicons-green"></span>
                        <span><?php esc_attr_e(
                                'Support', 'WpAdminStyle'
                            ); ?></span></h2>
                    <div class="inside">
                        <ul class="">
                            <li><span class="dashicons dashicons-admin-comments wpvivid-dashicons-green"></span>
                                <a href="https://wordpress.org/support/plugin/wpvivid-imgoptim/"><b><?php _e('Get Support on Forum', 'wpvivid-imgoptim'); ?></b></a>
                                <br>
                                <?php _e('If you need any help with our plugin, start a thread on the plugin support forum and we will respond shortly.', 'wpvivid-imgoptim'); ?>
                            </li>
                        </ul>

                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    public function cdn_save()
    {
        global $wpvivid_backup_pro;
        $wpvivid_backup_pro->ajax_check_security();

        if(isset($_POST['cdn'])&&!empty($_POST['cdn']))
        {
            $json_setting = sanitize_text_field($_POST['cdn']);
            $json_setting = stripslashes($json_setting);
            $setting = json_decode($json_setting, true);
            if (is_null($setting))
            {
                die();
            }

            $options=get_option('wpvivid_optimization_options',array());

            $options['cdn']['enable']=$setting['enable'];
            $options['cdn']['cdn_url']=$setting['cdn_url'];
            //$options['cdn']['cdn_og_url']=$setting['cdn_og_url'];
            if($setting['enable']&&empty($setting['cdn_url']))
            {
                $ret['result']='failed';
                $ret['error']='cdn URL cannot be empty.';
                echo json_encode($ret);
                die();
            }
            $options['cdn']['include_dir']=$setting['include_dir'];
            $options['cdn']['exclusions']=$setting['exclusions'];
            $options['cdn']['relative_path']=$setting['relative_path'];
            $options['cdn']['cdn_https']=$setting['cdn_https'];

            update_option('wpvivid_optimization_options',$options);

            $ret['result']='success';
            echo json_encode($ret);
        }

        die();
    }
}