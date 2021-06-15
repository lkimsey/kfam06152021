<?php

if (!defined('WPVIVID_IMGOPTIM_DIR'))
{
    die;
}

class WPvivid_Lazy_Load_Display
{
    function __construct()
    {
        add_filter('wpvivid_imgoptim_get_admin_menus',array($this,'get_admin_menus'),24);
        add_filter('wpvivid_imgoptim_get_screen_ids',array($this,'get_screen_ids'),14);

        add_action('wp_ajax_wpvivid_lazyload_save',array($this, 'lazyload_save'));
    }

    public function get_screen_ids($screen_ids)
    {
        $screen_ids[]='wpvivid-imgoptim_page_wpvivid-lazyload';
        return $screen_ids;
    }

    public function get_admin_menus($submenus)
    {
        $submenu['parent_slug']=WPVIVID_IMGOPTIM_SLUG;
        $submenu['page_title']= 'WPvivid Backup';
        $submenu['menu_title']=__('Lazyload', 'wpvivid-imgoptim');
        $submenu['capability']='administrator';
        $submenu['menu_slug']='wpvivid-lazyload';
        $submenu['index']=2;
        $submenu['function']=array($this, 'display');
        $submenus[$submenu['menu_slug']]=$submenu;
        return $submenus;
    }

    public function display()
    {
        ?>
        <div class="wrap">
            <div id="icon-options-general" class="icon32"></div>
            <h1><?php esc_attr_e( 'WPvivid Plugins - Lazyload', 'wpvivid-imgoptim' ); ?></h1>
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
                <p></p>
                <div>
                    <span class="dashicons dashicons-update wpvivid-dashicons-large wpvivid-dashicons-green"></span>
                    <span class="wpvivid-page-title"><?php _e('Lazyload', 'wpvivid-imgoptim'); ?><span class="wpvivid-rectangle-small wpvivid-orange">beta</span>
            </span><p></p>
                </div>
                <span class="about-description"><?php _e('This page allows you to enable lazy loading on your website to delay loading images or medias on your website pages until they are needed.', 'wpvivid-imgoptim'); ?></span>
            </div>
            <div class="wpvivid-nav-bar wpvivid-clear-float">
                <span class="dashicons dashicons-lightbulb wpvivid-dashicons-orange"></span>
                <span> <?php _e('Enabling lazy loading can speed up your website pages loading time and improve your Google PageSpeed Insights score, which is recommended.', 'wpvivid-imgoptim'); ?></span>
            </div>
        </div>
        <?php
    }

    public function sidebar()
    {
        global $wpvivid_imgoptim;
        $wpvivid_imgoptim->sidebar();
    }

    public function setting()
    {
        $options=get_option('wpvivid_optimization_options',array());

        $options['lazyload']=isset($options['lazyload'])?$options['lazyload']:array();
        $enable=isset($options['lazyload']['enable'])?$options['lazyload']['enable']:false;
        if($enable)
        {
            $enable='checked';
        }
        else
        {
            $enable='';
        }


        if(isset($options['lazyload']['extensions']))
        {
            $jpg=array_key_exists('jpg|jpeg|jpe',$options['lazyload']['extensions'])?$options['lazyload']['extensions']['jpg|jpeg|jpe']:true;
            $png=array_key_exists('png',$options['lazyload']['extensions'])?$options['lazyload']['extensions']['png']:true;
            $gif=array_key_exists('png',$options['lazyload']['extensions'])?$options['lazyload']['extensions']['gif']:true;
            $svg=array_key_exists('png',$options['lazyload']['extensions'])?$options['lazyload']['extensions']['svg']:true;
            if($jpg)
                $jpg='checked';
            if($png)
                $png='checked';
            if($gif)
                $gif='checked';
            if($svg)
                $svg='checked';
        }
        else
        {
            $jpg='checked';
            $png='checked';
            $gif='checked';
            $svg='checked';
        }

        $content=isset($options['lazyload']['content'])?$options['lazyload']['content']:true;
        $thumbnails=isset($options['lazyload']['thumbnails'])?$options['lazyload']['thumbnails']:true;

        if($content)
            $content='checked';
        if($thumbnails)
            $thumbnails='checked';

        $js=isset($options['lazyload']['js'])?$options['lazyload']['js']:'footer';

        if($js=='footer')
        {
            $footer='checked';
            $header='';
        }
        else
        {
            $footer='';
            $header='checked';
        }


        $noscript=isset($options['lazyload']['noscript'])?$options['lazyload']['noscript']:true;

        if($noscript)
            $noscript='checked';

        //$fade_in

        $animation=isset($options['lazyload']['animation'])?$options['lazyload']['animation']:'fadein';
        if($animation=='fadein')
        {
            $fade_in='checked';
        }
        else
        {
            $fade_in='';
        }
        ?>
        <table class="widefat" style="border-left:none;border-top:none;border-right:none;">
            <tr>
                <td class="row-title" style="min-width:200px;">
                    <label for="tablecell"><?php _e('Enable/Disable lazyload', 'wpvivid-imgoptim'); ?></label>
                </td>
                <td>
                    <span>
                        <label class="wpvivid-switch">
                            <input type="checkbox" option="lazyload" name="enable" <?php esc_attr_e($enable); ?> >
                            <span class="wpvivid-slider wpvivid-round"></span>
                        </label>
                        <span>
                            <strong><?php _e('Enable lazyload', 'wpvivid-imgoptim'); ?></strong>
                        </span>
                        <?php _e('Once enabled, the plugin will delay loading images on your website site pages until visitors scroll down to them, hence speeding up your website pages loading time and improving your Google PageSpeed Insights score.', 'wpvivid-imgoptim'); ?>
                    </span>
                </td>
            </tr>
            <tr>
                <td class="row-title" style="min-width:200px;">
                    <label for="tablecell"><?php _e('Media type to lazyload', 'wpvivid-imgoptim'); ?></label>
                </td>
                <td>
                    <label class="wpvivid-checkbox">
                        <span>.jpg | .jpeg</span>
                        <input type="checkbox" option="lazyload" name="jpg" <?php esc_attr_e($jpg); ?> />
                        <span class="wpvivid-checkbox-checkmark"></span>
                    </label>
                    <p></p>
                    <label class="wpvivid-checkbox">
                        <span>.png</span>
                        <input type="checkbox" option="lazyload" name="png" <?php esc_attr_e($png); ?> />
                        <span class="wpvivid-checkbox-checkmark"></span>
                    </label>
                    <p></p>
                    <label class="wpvivid-checkbox">
                        <span>.gif</span>
                        <input type="checkbox" option="lazyload" name="gif" <?php esc_attr_e($gif); ?> />
                        <span class="wpvivid-checkbox-checkmark"></span>
                    </label>
                    <p></p>
                    <label class="wpvivid-checkbox">
                        <span>.svg</span>
                        <input type="checkbox" option="lazyload" name="svg" <?php esc_attr_e($svg); ?> />
                        <span class="wpvivid-checkbox-checkmark"></span>
                    </label>
                </td>
            </tr>
            <tr>
                <td class="row-title" style="min-width:200px;"><label for="tablecell"><?php _e('Lazyload works on locations', 'wpvivid-imgoptim'); ?></label></td>
                <td>
                    <label class="wpvivid-checkbox">
                        <span>Content</span>
                        <input type="checkbox" option="lazyload" name="content" <?php esc_attr_e($content); ?>>
                        <span class="wpvivid-checkbox-checkmark"></span>
                    </label>
                    <p></p>
                    <label class="wpvivid-checkbox">
                        <span>Thumbnails</span>
                        <input type="checkbox" option="lazyload" name="thumbnails" <?php esc_attr_e($thumbnails); ?>>
                        <span class="wpvivid-checkbox-checkmark"></span>
                    </label>
                </td>
            </tr>
            <tr>
                <td class="row-title" style="min-width:200px;"><label for="tablecell"><?php _e('Browsers compatibility', 'wpvivid-imgoptim'); ?></label></td>
                <td>
                    <div>
                        <label class="wpvivid-checkbox">
                            <span>Use <code>noscript</code> tag</span>
                            <input type="checkbox" option="lazyload" name="noscript" <?php esc_attr_e($noscript); ?> />
                            <span class="wpvivid-checkbox-checkmark"></span>
                        </label>
                    </div>
                </td>
            </tr>
            <tr>
                <td class="row-title" style="min-width:200px;">
                    <label for="tablecell"><?php _e('Location where scripts insert', 'wpvivid-imgoptim'); ?></label>
                </td>
                <td>
                    <p><?php _e('The', 'wpvivid-imgoptim'); ?> <code>wp_header()</code> <?php _e('and', 'wpvivid-imgoptim'); ?> <code>wp_footer()</code> <?php _e('function are required for your theme', 'wpvivid-imgoptim'); ?></p>
                    <fieldset>
                        <label class="wpvivid-radio" style="float:left; padding-right:1em;">
                            <input type="radio" option="lazyload" name="js" value="footer" <?php esc_attr_e($footer); ?> />footer
                            <span class="wpvivid-radio-checkmark"></span>
                        </label>
                        <label class="wpvivid-radio" style="float:left; padding-right:1em;">header
                            <input type="radio" option="lazyload" name="js" value="header" <?php esc_attr_e($header); ?> />
                            <span class="wpvivid-radio-checkmark"></span>
                        </label>
                    </fieldset>
                    <p><?php _e('The plugin will load it’s scripts in the footer by default to speed up page loading times. Switch to the header option if you have problems', 'wpvivid-imgoptim'); ?>
                </td>
            </tr>
            <tr>
                <td class="row-title" style="min-width:200px;">
                    <label for="tablecell"><?php _e('Animation', 'wpvivid'); ?></label>
                </td>
                <td>
                    <fieldset>
                        <label class="wpvivid-radio" style="float:left; padding-right:1em;">
                            <input readonly type="radio"option="lazyload" name="animation" value="fadein" <?php esc_attr_e($fade_in); ?> /><?php _e('Fade in', 'wpvivid'); ?>
                            <span class="wpvivid-radio-checkmark"></span>
                        </label>
                    </fieldset>
                </td>
            </tr>
        </table>
        <div style="padding:1em 1em 0 0;">
            <input id="wpvivid_lazyload_save" class="button-primary" type="submit" value="Save Changes">
        </div>
        <script>
            jQuery('#wpvivid_lazyload_save').click(function()
            {
                wpvivid_lazyload_save();
            });

            function wpvivid_lazyload_save()
            {
                var lazyload = wpvivid_ajax_data_transfer('lazyload');
                var ajax_data = {
                    'action': 'wpvivid_lazyload_save',
                    'lazyload':lazyload
                };

                jQuery('#wpvivid_lazyload_save').css({'pointer-events': 'none', 'opacity': '0.4'});
                wpvivid_post_request(ajax_data, function (data)
                {
                    try
                    {
                        var jsonarray = jQuery.parseJSON(data);

                        jQuery('#wpvivid_lazyload_save').css({'pointer-events': 'auto', 'opacity': '1'});
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
                        jQuery('#wpvivid_lazyload_save').css({'pointer-events': 'auto', 'opacity': '1'});
                    }
                }, function (XMLHttpRequest, textStatus, errorThrown) {
                    jQuery('#wpvivid_lazyload_save').css({'pointer-events': 'auto', 'opacity': '1'});
                    var error_message = wpvivid_output_ajaxerror('Update lazyload setting', textStatus, errorThrown);
                    alert(error_message);
                });
            }
        </script>
        <?php
    }

    public function lazyload_save()
    {
        global $wpvivid_imgoptim;
        $wpvivid_imgoptim->ajax_check_security();

        if(isset($_POST['lazyload'])&&!empty($_POST['lazyload']))
        {
            $json_setting = sanitize_text_field($_POST['lazyload']);
            $json_setting = stripslashes($json_setting);
            $setting = json_decode($json_setting, true);
            if (is_null($setting))
            {
                die();
            }

            $options=get_option('wpvivid_optimization_options',array());

            $options['lazyload']['enable']=$setting['enable'];

            $options['lazyload']['extensions']['jpg|jpeg|jpe']=$setting['jpg'];
            $options['lazyload']['extensions']['png']=$setting['png'];
            $options['lazyload']['extensions']['gif']=$setting['gif'];
            $options['lazyload']['extensions']['svg']=$setting['svg'];

            $options['lazyload']['js']=$setting['js'];

            $options['lazyload']['animation']=$setting['lazyload_display'];

            $options['lazyload']['content']=$setting['content'];
            $options['lazyload']['thumbnails']=$setting['thumbnails'];

            $options['lazyload']['noscript']=$setting['noscript'];

            $options['lazyload']['animation']=$setting['animation'];
            update_option('wpvivid_optimization_options',$options);

            $ret['result']='success';
            echo json_encode($ret);
        }

        die();
    }
}