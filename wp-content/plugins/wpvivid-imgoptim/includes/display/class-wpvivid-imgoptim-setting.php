<?php

if (!defined('WPVIVID_IMGOPTIM_DIR'))
{
    die;
}


class WPvivid_ImgOptim_Setting
{
    public $main_tab;
    public function __construct()
    {
        add_filter('wpvivid_imgoptim_get_screen_ids',array($this,'get_screen_ids'),12);
        add_action('wp_ajax_wpvivid_set_general_image_optimize_setting', array($this, 'set_general_setting'));
        add_filter('wpvivid_imgoptim_get_admin_menus',array($this,'get_admin_menus'),23);
    }

    public function get_admin_menus()
    {
        $submenu['parent_slug']=WPVIVID_IMGOPTIM_SLUG;
        $submenu['page_title']= 'WPvivid Image Optimize';
        $submenu['menu_title']=__('Settings', 'wpvivid-imgoptim');
        $submenu['capability']='administrator';
        $submenu['menu_slug']='wpvivid-imgoptim-setting';
        $submenu['index']=5;
        $submenu['function']=array($this, 'display');
        $submenus[$submenu['menu_slug']]=$submenu;
        return $submenus;
    }

    public function get_screen_ids($screen_ids)
    {
        $screen_ids[]='wpvivid-imgoptim_page_wpvivid-imgoptim-setting';
        return $screen_ids;
    }

    public function display()
    {
        ?>
        <div class="wrap" style="max-width:1720px;">
            <h1>
                <?php
                _e( 'WPvivid Plugins Image Optimization - Settings','wpvivid-imgoptim');
                ?>
            </h1>
             <div id="poststuff">
                <div id="post-body" class="metabox-holder columns-2">
                    <div id="post-body-content">
                        <div class="meta-box-sortables ui-sortable">
                            <div class="wpvivid-backup">
                                <?php
                                $this->welcome_bar();
                                ?>
                                <div class="wpvivid-canvas wpvivid-clear-float">
                                    <?php
                                    if(!class_exists('WPvivid_Tab_Page_Container_Ex'))
                                        include_once WPVIVID_IMGOPTIM_DIR . '/includes/class-wpvivid-tab-page-container-ex.php';
                                    $this->main_tab=new WPvivid_Tab_Page_Container_Ex();

                                    $args['span_class']='dashicons dashicons-backup wpvivid-dashicons-blue';
                                    $args['span_style']='padding-right:0.5em;margin-top:0.1em;';
                                    $args['div_style']='display:block;';
                                    $args['is_parent_tab']=0;
                                    $tabs['general_setting']['title']=__('Image Optimization','wpvivid-imgoptim');
                                    $tabs['general_setting']['slug']='general_setting';
                                    $tabs['general_setting']['callback']=array($this, 'output_setting_ex');
                                    $tabs['general_setting']['args']=$args;
                                    $tabs=apply_filters('wpvivid_imgoptim_setting_tab',$tabs);

                                    foreach ($tabs as $key=>$tab)
                                    {
                                        $this->main_tab->add_tab($tab['title'],$tab['slug'],$tab['callback'], $tab['args']);
                                    }

                                    $this->main_tab->display();
                                    ?>
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
        <div class="wpvivid-welcome-bar-left">
            <p>
                <span class="dashicons dashicons-admin-generic wpvivid-dashicons-large wpvivid-dashicons-blue"></span>
                <span class="wpvivid-page-title"><?php _e('Settings', 'wpvivid-imgoptim'); ?></span>
            </p>
            <span class="about-description"><?php _e('Settings page of WPvivid Image Optimization plugin.', 'wpvivid-imgoptim'); ?></span>
        </div>
        <div class="wpvivid-nav-bar wpvivid-clear-float">
            <span class="dashicons dashicons-lightbulb wpvivid-dashicons-orange"></span>
            <span> <?php _e('All default settings are already optimal for most users, leave it as default or feel free to modify as per your preferences.', 'wpvivid-imgoptim'); ?></span>
        </div>
        <?php
    }

    public function sidebar()
    {
        global $wpvivid_imgoptim;
        $wpvivid_imgoptim->sidebar();
    }

    public function set_general_setting()
    {
        global $wpvivid_imgoptim;
        $wpvivid_imgoptim->ajax_check_security('manage_options');

        try
        {
            if(isset($_POST['setting'])&&!empty($_POST['setting']))
            {
                $json_setting = sanitize_text_field($_POST['setting']);
                $json_setting = stripslashes($json_setting);
                $setting = json_decode($json_setting, true);
                if (is_null($setting))
                {
                    echo 'json decode failed';
                    die();
                }
                $ret = $this->check_setting_option($setting);
                echo json_encode($ret);
                die();
            }
            else
            {
                die();
            }
        }
        catch (Exception $error)
        {
            $message = 'An exception has occurred. class: '.get_class($error).';msg: '.$error->getMessage().';code: '.$error->getCode().';line: '.$error->getLine().';in_file: '.$error->getFile().';';
            error_log($message);
            echo json_encode(array('result'=>'failed','error'=>$message));
            die();
        }
    }

    public function output_setting_ex()
    {
        ?>
        <div>
            <?php
            $this->output_setting();
            ?>
            <div>
                <input class="button-primary" id="wpvivid_setting_general_save" type="submit" value="<?php esc_attr_e( 'Save Changes', 'wpvivid-imgoptim' ); ?>" />
            </div>
        </div>
        <script>
            jQuery('#wpvivid_setting_general_save').click(function()
            {
                wpvivid_set_general_settings();
            });

            function wpvivid_set_general_settings()
            {
                var json = {};

                var setting_data = wpvivid_ajax_data_transfer('setting');
                var json1 = JSON.parse(setting_data);

                jQuery.extend(json1, json);
                setting_data=JSON.stringify(json1);

                var ajax_data = {
                    'action': 'wpvivid_set_general_image_optimize_setting',
                    'setting': setting_data,
                };
                jQuery('#wpvivid_setting_general_save').css({'pointer-events': 'none', 'opacity': '0.4'});
                wpvivid_post_request(ajax_data, function (data)
                {
                    try
                    {
                        var jsonarray = jQuery.parseJSON(data);

                        jQuery('#wpvivid_setting_general_save').css({'pointer-events': 'auto', 'opacity': '1'});
                        if (jsonarray.result === 'success')
                        {
                            location.href='<?php echo apply_filters('wpvivid_get_admin_url', '') . 'admin.php?page='.apply_filters('wpvivid_white_label_plugin_name', 'wpvivid-imgoptim-setting') ?>';
                        }
                        else {
                            alert(jsonarray.error);
                        }
                    }
                    catch (err)
                    {
                        alert(err);
                        jQuery('#wpvivid_setting_general_save').css({'pointer-events': 'auto', 'opacity': '1'});
                    }
                }, function (XMLHttpRequest, textStatus, errorThrown)
                {
                    jQuery('#wpvivid_setting_general_save').css({'pointer-events': 'auto', 'opacity': '1'});
                    var error_message = wpvivid_output_ajaxerror('changing base settings', textStatus, errorThrown);
                    alert(error_message);
                });
            }
        </script>
        <?php
    }

    public function output_setting()
    {
        $options=get_option('wpvivid_optimization_options',array());

        $keep_exif=isset($options['keep_exif'])?$options['keep_exif']:true;

        if($keep_exif)
        {
            $keep_exif='checked';
        }

        $quality=isset($options['quality'])?$options['quality']:'lossless';

        if($quality=='lossless')
        {
            $lossless='checked';
            $lossy='';
        }
        else
        {
            $lossy='checked';
            $lossless='';
        }

        if(isset($options['resize']))
        {
            $resize=$options['resize']['enable'];
            $resize_width=$options['resize']['width'];
            $resize_height=$options['resize']['height'];
        }
        else
        {
            $resize=true;
            $resize_width=2560;
            $resize_height=2560;
        }

        if($resize)
        {
            $resize='checked';
        }

        /*
        $only_resize=isset($options['only_resize'])?$options['only_resize']:false;

        if($only_resize)
        {
            $only_resize='checked';
        }
        */

        if(!isset($options['skip_size']))
        {
            $options['skip_size']=array();
        }

        global $_wp_additional_image_sizes;
        $intermediate_image_sizes = get_intermediate_image_sizes();
        $image_sizes=array();
        $image_sizes[ 'og' ]['skip']=isset($options['skip_size']['og'])?$options['skip_size']['og']:false;

        foreach ( $intermediate_image_sizes as $size_key )
        {
            if ( in_array( $size_key, array( 'thumbnail', 'medium', 'large' ), true ) )
            {
                $image_sizes[ $size_key ]['width']  = get_option( $size_key . '_size_w' );
                $image_sizes[ $size_key ]['height'] = get_option( $size_key . '_size_h' );
                $image_sizes[ $size_key ]['crop']   = (bool) get_option( $size_key . '_crop' );
                if(isset($options['skip_size'][$size_key])&&$options['skip_size'][$size_key])
                {
                    $image_sizes[ $size_key ]['skip']=true;
                }
                else
                {
                    $image_sizes[ $size_key ]['skip']=false;
                }
            }
            else if ( isset( $_wp_additional_image_sizes[ $size_key ] ) )
            {
                $image_sizes[ $size_key ] = array(
                    'width'  => $_wp_additional_image_sizes[ $size_key ]['width'],
                    'height' => $_wp_additional_image_sizes[ $size_key ]['height'],
                    'crop'   => $_wp_additional_image_sizes[ $size_key ]['crop'],
                );
                if(isset($options['skip_size'][$size_key])&&$options['skip_size'][$size_key])
                {
                    $image_sizes[ $size_key ]['skip']=true;
                }
                else
                {
                    $image_sizes[ $size_key ]['skip']=false;
                }
            }
        }

        if ( ! isset( $sizes['medium_large'] ) || empty( $sizes['medium_large'] ) )
        {
            $width  = intval( get_option( 'medium_large_size_w' ) );
            $height = intval( get_option( 'medium_large_size_h' ) );

            $image_sizes['medium_large'] = array(
                'width'  => $width,
                'height' => $height,
            );

            if(isset($options['skip_size']['medium_large'])&&$options['skip_size']['medium_large'])
            {
                $image_sizes[ 'medium_large' ]['skip']=true;
            }
            else
            {
                $image_sizes[ 'medium_large' ]['skip']=false;
            }
        }

        $is_auto=isset($options['auto_optimize'])?$options['auto_optimize']:true;

        if($is_auto)
        {
            $is_auto='checked';
        }
        else
        {
            $is_auto='';
        }

        $backup=isset($options['backup'])?$options['backup']:true;

        if($backup)
        {
            $backup='checked';
        }
        else
        {
            $backup='';
        }

        $backup_path=WPVIVID_IMGOPTIM_DEFAULT_SAVE_DIR;
        $path=str_replace(ABSPATH,'',WP_CONTENT_DIR);
        $backup_path_placeholder='.../'.$path.'/'.$backup_path;
        ?>

        <table class="widefat" style="border-left:none;border-top:none;border-right:none;">
            <tr>
                <td class="row-title" style="min-width:200px;"><label for="tablecell">Cloud Servers</label></td>
                <td>
                    <div>
                        <span>
                            <select id="" onchange="">
                                <option value="-1">North American - Free</option>
                                <option value="1" >Europe - Free - coming soon</option>
                            </select>
                        </span>
                        <p>Choosing the server closest to your website can speed up optimization process.</p>
                    </div>
                </td>
            </tr>
            <tr>
                <td class="row-title" style="min-width:200px;"><label for="tablecell">Compression mode</label></td>
                <td>
                    <fieldset>
                        <label class="wpvivid-radio" style="float:left; padding-right:1em;">
                            <input type="radio" option="setting" name="quality" value="lossless" <?php esc_attr_e($lossless); ?> /><?php _e('Lossless','wpvivid-imgoptim')?>
                            <span class="wpvivid-radio-checkmark"></span>
                        </label>
                        <label class="wpvivid-radio" style="float:left; padding-right:1em;"><?php _e('Lossy','wpvivid-imgoptim')?>
                            <input type="radio" option="setting" name="quality" value="lossy" <?php esc_attr_e($lossy); ?> />
                            <span class="wpvivid-radio-checkmark"></span>
                        </label>
                    </fieldset>
                    <p></p>
                    <div>
                        <label class="wpvivid-checkbox">
                            <span><?php _e('Leave EXIF data','wpvivid-imgoptim')?></span>
                            <input type="checkbox" option="setting" name="keep_exif" <?php esc_attr_e($keep_exif); ?>>
                            <span class="wpvivid-checkbox-checkmark"></span>
                        </label>
                    </div>
                </td>
            </tr>
            <tr>
                <td class="row-title" style="min-width:200px;"><label for="tablecell"><?php _e('Resizing large images','wpvivid-imgoptim')?></label></td>
                <td>
                    <div>
                        <label class="wpvivid-checkbox">
                            <span><?php _e('Enable auto-resizing large images','wpvivid-imgoptim')?></span>
                            <input type="checkbox"  option="setting" name="resize" <?php esc_attr_e($resize); ?> />
                            <span class="wpvivid-checkbox-checkmark"></span>
                        </label>
                    </div>
                    <p></p>
                    <input type="text" option="setting" name="resize_width" value="<?php esc_attr_e($resize_width); ?>" onkeyup="value=value.replace(/\D/g,'')" /> px
                    <p></p>
                    <input type="text" option="setting" name="resize_height" value="<?php esc_attr_e($resize_height); ?>" onkeyup="value=value.replace(/\D/g,'')" /> px
                </td>
            </tr>
            <tr>
                <td class="row-title" style="min-width:200px;"><label for="tablecell"><?php _e('Optimize different sizes of images','wpvivid-imgoptim')?></label></td>
                <td>
                    <?php
                    $first=true;
                    foreach ($image_sizes as $size_key=>$size)
                    {
                        if($size['skip'])
                        {
                            $checked='';
                        }
                        else
                        {
                            $checked='checked';
                        }

                        if($first)
                        {
                            $first=false;
                        }
                        else
                        {
                            echo '<p></p>';
                        }

                        if($size_key=='og')
                        {
                            $text='Original image';
                            echo '<label class="wpvivid-checkbox">
                                    <span>'.$text.'</span>
                                    <input type="checkbox" option="setting" name="'.$size_key.'" '.$checked.'/>
                                    <span class="wpvivid-checkbox-checkmark"></span>
                               </label>';
                        }
                        else
                        {
                            $text=$size_key.' ('.$size['width'].'x'.$size['height'].')';
                            echo '<label class="wpvivid-checkbox">
                                    <span>'.$text.'</span>
                                    <input type="checkbox" option="setting" name="'.$size_key.'" '.$checked.'/>
                                    <span class="wpvivid-checkbox-checkmark"></span>
                               </label>';
                        }
                    }
                    ?>
                </td>
            </tr>
            <tr>
                <td class="row-title" style="min-width:200px;"><label for="tablecell"><?php _e('Image backup','wpvivid-imgoptim')?></label></td>
                <td>
                    <label class="wpvivid-checkbox">
                        <span><?php _e('Enable image backup before optimization','wpvivid-imgoptim')?></span>
                        <input type="checkbox" option="setting" name="image_backup" <?php esc_attr_e($backup); ?> />
                        <span class="wpvivid-checkbox-checkmark"></span>
                    </label>
                    <p></p>
                    <span class="dashicons dashicons-portfolio wpvivid-dashicons-orange"></span>
                    <span><?php _e('Image backup folder','wpvivid-imgoptim')?>:</span>
                    <div id="wpvivid_image_custom_backup_path_placeholder">
                        <span><code><?php echo esc_html($backup_path_placeholder);?></code></span>
                    </div>
                </td>
            </tr>
            <tr>
                <td class="row-title" style="min-width:200px;">
                    <label for="tablecell"><?php _e('Real-Time Optimization','wpvivid-imgoptim')?></label>
                </td>
                <td>
                    <div>
                        <label class="wpvivid-checkbox">
                            <span><?php _e('Enable real-time optimization upon upload','wpvivid-imgoptim')?></span>
                            <input type="checkbox" option="setting" name="auto_optimize" <?php esc_attr_e($is_auto); ?> />
                            <span class="wpvivid-checkbox-checkmark"></span>
                        </label>
                    </div>
                </td>
            </tr>
        </table>

        <br>
        <script>
            jQuery('#wpvivid_image_custom_backup_path_placeholder_btn').click(function()
            {
                jQuery('#wpvivid_image_custom_backup_path_placeholder').hide();
                jQuery('#wpvivid_image_custom_backup_path').show();
            });
        </script>
        <?php
    }

    public function check_setting_option($setting)
    {
        $options=get_option('wpvivid_optimization_options',array());

        if(isset($setting['auto_optimize']))
            $options['auto_optimize']=$setting['auto_optimize'];

        if(isset($setting['keep_exif']))
            $options['keep_exif']=$setting['keep_exif'];

        if(isset($setting['quality']))
            $options['quality']=$setting['quality'];

        if(isset($setting['resize']))
            $options['resize']['enable']=$setting['resize'];
        if(isset($setting['resize_width']))
            $options['resize']['width']=$setting['resize_width'];
        if(isset($setting['resize_height']))
            $options['resize']['height']=$setting['resize_height'];

        if(isset($setting['only_resize']))
            $options['only_resize']=$setting['only_resize'];


        $intermediate_image_sizes = get_intermediate_image_sizes();

        if(isset($setting['og']))
        {
            $options['skip_size']['og']=!$setting['og'];
        }
        else
        {
            $options['skip_size']['og']=false;
        }

        foreach ($intermediate_image_sizes as $size_key)
        {
            if(isset($setting[$size_key]))
            {
                $options['skip_size'][$size_key]=!$setting[$size_key];
            }
            else
            {
                $options['skip_size'][$size_key]=false;
            }
        }

        if(isset($setting['image_backup']))
            $options['backup']=$setting['image_backup'];

        delete_option('wpvivid_get_optimization_url');
        update_option('wpvivid_optimization_options',$options);

        $ret['result']='success';
        return $ret;
    }
}