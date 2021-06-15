<?php

if (!defined('WPVIVID_IMGOPTIM_DIR'))
{
    die;
}

class WPvivid_ImgOptim
{
    public $display;
    public $setting;
    public function __construct()
    {
        $this->load_dependencies();
        $this->load_ajax_hook();
        $this->fix_optimization_url();
        add_filter( 'manage_media_columns', array($this,'optimize_columns'));
        add_action( 'manage_media_custom_column', array($this, 'optimize_column_display'),10,2);

        add_action( 'delete_attachment', array( $this, 'delete_images' ), 20 );

        add_filter('wpvivid_get_admin_url',array($this,'get_admin_url'),10);

        add_action('admin_enqueue_scripts',array( $this,'enqueue_styles'));

        add_action( 'attachment_submitbox_misc_actions',  array( $this,'submitbox') );
        add_filter( 'attachment_fields_to_edit', array( $this,'attachment_fields_to_edit'), 9999, 2 );

        add_filter('wpvivid_is_image_optimized',array($this,'is_image_optimized'),10,2);
        $plugin_basename = plugin_basename( plugin_dir_path( __DIR__ ) . 'wpvivid-imgoptim.php' );
        add_filter('plugin_action_links_' . $plugin_basename, array( $this,'add_action_links'));
        add_filter('wpvivid_imgoptim_og_skip_file', array($this, 'og_skip_file'), 20, 2);
        add_filter('wpvivid_imgoptim_skip_file', array($this, 'skip_file'), 10, 3);
        add_filter('wpvivid_imgoptim_opt_skip_file', array($this, 'opt_skip_file'), 10, 4);
        //
    }

    public function fix_optimization_url()
    {
        $options=get_option('wpvivid_optimization_options',array());
        if(isset($options['region']))
        {
            if( ! function_exists('get_plugin_data') )
            {
                require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
            }
            $plugin_slug='wpvivid-backup-pro\/wpvivid-backup-pro.php';
            if(is_plugin_active($plugin_slug))
            {
            }
            else
            {
                delete_option('wpvivid_get_optimization_url');
            }
        }
    }

    public function add_action_links( $links )
    {
        $settings_link = array(
            '<a href="' . admin_url('admin.php?page=WPvivid_ImgOptim').'">' . __('Settings', WPVIVID_IMGOPTIM_SLUG) . '</a>',
        );
        return array_merge(  $settings_link, $links );
    }

    private function load_dependencies()
    {
        include_once WPVIVID_IMGOPTIM_DIR. '/includes/class-wpvivid-imgoptim-log.php';

        include_once WPVIVID_IMGOPTIM_DIR . '/includes/optimize/class-wpvivid-imgoptim-task.php';
        include_once WPVIVID_IMGOPTIM_DIR . '/includes/optimize/class-wpvivid-ngg-image-optimization.php';
        include_once WPVIVID_IMGOPTIM_DIR . '/includes/optimize/class-wpvivid-image-auto-optimization.php';
        new WPvivid_Image_Auto_Optimization();

        include_once WPVIVID_IMGOPTIM_DIR . '/includes/lazyload/class-wpvivid-lazy-load.php';
        new WPvivid_Lazy_Load();

        include_once WPVIVID_IMGOPTIM_DIR . '/includes/cdn/class-wpvivid-cdn.php';
        new WPvivid_CDN();

        if(is_admin())
        {
            include_once WPVIVID_IMGOPTIM_DIR . '/includes/display/class-wpvivid-imgoptim-display.php';
            $this->display=new WPvivid_ImgOptim_Display();
            include_once WPVIVID_IMGOPTIM_DIR . '/includes/display/class-wpvivid-imgoptim-license-display.php';
            new WPvivid_ImgOptim_license_Display();

            include_once WPVIVID_IMGOPTIM_DIR . '/includes/display/class-wpvivid-imgoptim-setting.php';
            $this->setting=new WPvivid_ImgOptim_Setting();

            include_once WPVIVID_IMGOPTIM_DIR . '/includes/display/class-wpvivid-lazy-load-display.php';
            new WPvivid_Lazy_Load_Display();

            include_once WPVIVID_IMGOPTIM_DIR . '/includes/display/class-wpvivid-cdn-display.php';
            new WPvivid_CDN_Display();
        }
    }

    private function load_ajax_hook()
    {
        add_action('wp_ajax_wpvivid_restore_single_image',array($this, 'restore_single_image'));
        add_action('wp_ajax_wpvivid_opt_single_image',array($this,'opt_single_image'));
        add_action('wp_ajax_wpvivid_get_opt_single_image_progress',array($this,'get_single_image_progress'));
        add_action('wp_ajax_wpvivid_set_optimization_settings',array($this,'set_optimization_settings'));
    }

    public function get_admin_url($admin_url)
    {
        if(is_multisite())
        {
            $admin_url = network_admin_url();
        }
        else
        {
            $admin_url =admin_url();
        }

        return $admin_url;
    }

    public function ajax_check_security($role='administrator')
    {
        check_ajax_referer( 'wpvivid_ajax', 'nonce' );
        $check=is_admin()&&current_user_can($role);
        $check=apply_filters('wpvivid_ajax_check_security',$check);
        if(!$check)
        {
            die();
        }
    }

    public function optimize_columns($defaults)
    {
        $defaults['wpvivid_imgoptim'] = __('WPvivid Imgoptim','wpvivid-imgoptim');
        $defaults=apply_filters('wpvivid_image_optimize_columns',$defaults);

        return $defaults;
    }

    public function optimize_column_display($column_name, $id )
    {
        if ( 'wpvivid_imgoptim' === $column_name )
        {
            echo wp_kses_post( $this->optimize_action_columns( $id ) );
        }
    }

    public function submitbox()
    {
        global $post;

        if(get_option('wpvivid_imgoptim_user',false)===false)
        {
            $url='admin.php?page=wpvivid-imgoptim-license';
            $html='<div class="misc-pub-section misc-pub-wpvivid"><h4>'.__('WPvivid Imgoptim','wpvivid-imgoptim').'</h4>';
            $html.='<p>'.__('Not set License','wpvivid-imgoptim').'</p>';
            $html.='<a href="'.$url.'">'.__('Check your Settings','wpvivid-imgoptim').'</a>';
            $html.='</div>';
        }
        else
        {
            $allowed_mime_types = array(
                'image/jpg',
                'image/jpeg',
                'image/png');

            if ( ! wp_attachment_is_image( $post->ID ) || ! in_array( get_post_mime_type( $post->ID ),$allowed_mime_types ) )
            {
                $html= __('Not support','wpvivid-imgoptim');
            }
            else
            {
                $meta=get_post_meta( $post->ID,'wpvivid_image_optimize_meta', true );
                $html='<div class="misc-pub-section misc-pub-wpvivid" data-id="'.$post->ID.'"><h4>'.__('WPvivid Imgoptim','wpvivid-imgoptim').'</h4>';
                $task=new WPvivid_ImgOptim_Task();

                if(!$task->is_image_optimized($post->ID))
                {
                    if($task->is_image_progressing($post->ID))
                    {
                        $html.= "<a  class='wpvivid-media-progressing button-primary' data-id='{$post->ID}'>".__('Optimizing...','wpvivid-imgoptim')."</a>";
                    }
                    else
                    {
                        $html.= "<a  class='wpvivid-media button-primary' data-id='{$post->ID}'>".__('Optimize','wpvivid-imgoptim')."</a>";
                    }
                }
                else
                {
                    $percent=round(100-($meta['sum']['opt_size']/$meta['sum']['og_size'])*100,2);
                    $html.='<ul>';
                    $html.= '<li><span>'.__('Optimized size','wpvivid-imgoptim').' : </span><strong>'.size_format($meta['sum']['opt_size'],2).'</strong></li>';
                    $html.= '<li><span>'.__('Saved','wpvivid-imgoptim').' : </span><strong>'.$percent.'%</strong></li>';
                    $html.= '<li><span>'.__('Original size','wpvivid-imgoptim').' : </span><strong>'.size_format($meta['sum']['og_size'],2).'</strong></li>';
                    $html.="<li><a  class='wpvivid-media-restore button-primary' data-id='{$post->ID}'>".__('Restore','wpvivid-imgoptim')."</a></li>";
                    $html.='</ul>';
                }

                $html.='</div>';
            }
        }

        $html=apply_filters('wpvivid_imgoptim_submitbox',$html);

        echo $html;
    }

    public function optimize_action_columns($id)
    {
        if(get_option('wpvivid_imgoptim_user',false)===false)
        {
            $url='admin.php?page=wpvivid-imgoptim-license';
            $html='<div><p>'.__('Not set License','wpvivid-imgoptim').'</p>';
            $html.='<a href="'.$url.'">'.__('Check your Settings','wpvivid-imgoptim').'</a>';
            $html.='</div>';
        }
        else
        {
            $allowed_mime_types = array(
                'image/jpg',
                'image/jpeg',
                'image/png');

            if ( ! wp_attachment_is_image( $id ) || ! in_array( get_post_mime_type( $id ),$allowed_mime_types ) )
            {
                return __('Not support','wpvivid-imgoptim');
            }

            $meta=get_post_meta( $id,'wpvivid_image_optimize_meta', true );

            $html='<div class="wpvivid-media-item" data-id="'.$id.'">';
            $task=new WPvivid_ImgOptim_Task();

            if(!$task->is_image_optimized($id))
            {
                if($task->is_image_progressing($id))
                {
                    $html.= "<a  class='wpvivid-media-progressing button-primary' data-id='{$id}'>".__('Optimizing...','wpvivid-imgoptim')."</a>";
                }
                else
                {
                    $html.= "<a  class='wpvivid-media button-primary' data-id='{$id}'>".__('Optimize','wpvivid-imgoptim')."</a>";
                }
            }
            else
            {
                $percent=round(100-($meta['sum']['opt_size']/$meta['sum']['og_size'])*100,2);

                $html.='<ul>';
                $html.= '<li><span>'.__('Optimized size','wpvivid-imgoptim').' : </span><strong>'.size_format($meta['sum']['opt_size'],2).'</strong></li>';
                $html.= '<li><span>'.__('Saved','wpvivid-imgoptim').' : </span><strong>'.$percent.'%</strong></li>';
                $html.= '<li><span>'.__('Original size','wpvivid-imgoptim').' : </span><strong>'.size_format($meta['sum']['og_size'],2).'</strong></li>';
                $html.="<li><a  class='wpvivid-media-restore button-primary' data-id='{$id}'>".__('Restore','wpvivid-imgoptim')."</a></li>";
                $html.='</ul>';
            }

            $html.='</div>';
        }


        return $html;
    }

    public function attachment_fields_to_edit($form_fields, $post)
    {
        global $pagenow;

        if ( 'post.php' === $pagenow )
        {
            return $form_fields;
        }

        if(get_option('wpvivid_imgoptim_user',false)===false)
        {
            $url='admin.php?page=wpvivid-imgoptim-license';
            $html='<div><p>'.__('Not set License','wpvivid-imgoptim').'</p>';
            $html.='<a href="'.$url.'">'.__('Check your Settings','wpvivid-imgoptim').'</a>';
            $html.='</div>';
        }
        else
        {
            $allowed_mime_types = array(
                'image/jpg',
                'image/jpeg',
                'image/png');

            if ( ! wp_attachment_is_image( $post->ID ) || ! in_array( get_post_mime_type( $post->ID ),$allowed_mime_types ) )
            {
                $html= 'Not support';
            }
            else
            {
                $meta=get_post_meta( $post->ID,'wpvivid_image_optimize_meta', true );

                $html='<div class="wpvivid-media-attachment" data-id="'.$post->ID.'">';

                $task=new WPvivid_ImgOptim_Task();

                if(!$task->is_image_optimized($post->ID))
                {
                    if($task->is_image_progressing($post->ID))
                    {
                        $html.= "<a  class='wpvivid-media wpvivid-media-progressing button-primary' data-id='{$post->ID}'>".__('Optimizing...','wpvivid-imgoptim')."</a>";
                    }
                    else
                    {
                        $html.= "<a  class='wpvivid-media button-primary' data-id='{$post->ID}'>".__('Optimize','wpvivid-imgoptim')."</a>";
                    }
                }
                else
                {
                    $percent=round(100-($meta['sum']['opt_size']/$meta['sum']['og_size'])*100,2);

                    $html.='<ul>';
                    $html.= '<li><span>'.__('Optimized size','wpvivid-imgoptim').' : </span><strong>'.size_format($meta['sum']['opt_size'],2).'</strong></li>';
                    $html.= '<li><span>'.__('Saved','wpvivid-imgoptim').' : </span><strong>'.$percent.'%</strong></li>';
                    $html.= '<li><span>'.__('Original size','wpvivid-imgoptim').' : </span><strong>'.size_format($meta['sum']['og_size'],2).'</strong></li>';
                    $html.="<li><a  class='wpvivid-media-restore button-primary' data-id='{$post->ID}'>".__('Restore','wpvivid-imgoptim')."</a></li>";
                    $html.='</ul>';
                }
                $html.='</div>';
            }
        }

        $form_fields['wpvivid_imgoptim'] = array(
            'label'         => 'WPvivid Imgoptim',
            'input'         => 'html',
            'html'          => $html,
            'show_in_edit'  => true,
            'show_in_modal' => true,
        );

        $form_fields=apply_filters('wpvivid_attachment_fields_to_edit',$form_fields,$post);

        return $form_fields;
    }

    public function enqueue_styles()
    {
        if(get_current_screen()->id=='upload'||get_current_screen()->id=='attachment')
        {
            wp_enqueue_style(WPVIVID_IMGOPTIM_SLUG.'_Optimize_Display', WPVIVID_IMGOPTIM_URL . '/includes/display/css/wpvivid-upload.css', array(), WPVIVID_IMGOPTIM_VERSION, 'all');
            wp_enqueue_script(WPVIVID_IMGOPTIM_SLUG, WPVIVID_IMGOPTIM_URL . '/includes/display/js/wpvivid-imgoptim.js', array('jquery'), WPVIVID_IMGOPTIM_VERSION, false);
            wp_enqueue_script(WPVIVID_IMGOPTIM_SLUG.'_Optimize', WPVIVID_IMGOPTIM_URL . '/includes/display/js/optimize.js', array('jquery'), WPVIVID_IMGOPTIM_VERSION, true);
            wp_localize_script(WPVIVID_IMGOPTIM_SLUG, 'wpvivid_ajax_object', array('ajax_url' => admin_url('admin-ajax.php'),'ajax_nonce'=>wp_create_nonce('wpvivid_ajax')));
        }
    }

    public function flush($ret)
    {
        $text=json_encode($ret);
        if(!headers_sent()){
            header('Content-Length: '.( ( ! empty( $text ) ) ? strlen( $text ) : '0' ));
            header('Connection: close');
            header('Content-Encoding: none');
        }
        if (session_id())
            session_write_close();

        echo $text;

        if(function_exists('fastcgi_finish_request'))
        {
            fastcgi_finish_request();
        }
        else
        {
            if(ob_get_level()>0)
                ob_flush();
            flush();
        }
    }

    public function restore_single_image()
    {
        $this->ajax_check_security();

        if(!isset($_POST['id'])||!is_string($_POST['id']))
        {
            die();
        }

        if(isset($_POST['page'])&&is_string($_POST['page']))
        {
            $page=sanitize_text_field($_POST['page']);
        }
        else
        {
            $page='media';
        }

        try
        {
            $id=sanitize_key($_POST['id']);

            $task=new WPvivid_ImgOptim_Task();
            $task->restore_image($id);

            if($page=='edit')
            {
                $html='<h4>'.__('WPvivid Imgoptim', 'wpvivid-imgoptim').'</h4>';
            }
            else
            {
                $html='';
            }

            if(!$task->is_image_optimized($id))
            {
                if($task->is_image_progressing($id))
                {
                    $html.= "<a  class='wpvivid-media-progressing button-primary' data-id='{$id}'>".__('Optimizing...', 'wpvivid-imgoptim')."</a>";
                }
                else
                {
                    $html.= "<a  class='wpvivid-media button-primary' data-id='{$id}'>".__('Optimize','wpvivid-imgoptim')."</a>";

                }
            }
            else
            {
                $meta=get_post_meta( $id,'wpvivid_image_optimize_meta', true );
                $percent=round(100-($meta['sum']['opt_size']/$meta['sum']['og_size'])*100,2);
                $html.='<ul>';
                $html.= '<li><span>'.__('Optimized size','wpvivid-imgoptim').' : </span><strong>'.size_format($meta['sum']['opt_size'],2).'</strong></li>';
                $html.= '<li><span>'.__('Saved','wpvivid-imgoptim').' : </span><strong>'.$percent.'%</strong></li>';
                $html.= '<li><span>'.__('Original size','wpvivid-imgoptim').' : </span><strong>'.size_format($meta['sum']['og_size'],2).'</strong></li>';
                $html.='<li><p style="border-bottom:1px solid #D2D3D6;"></p></li>';
                $html.="<li><a  class='wpvivid-media-restore button-primary' data-id='{$id}'>".__('Restore','wpvivid-imgoptim')."</a></li>";
                $html.='</ul>';
            }
            $ret[$id]['html']=$html;
            $ret['result']='success';

            echo json_encode($ret);
        }
        catch (Exception $error)
        {
            $message = 'An exception has occurred. class: '.get_class($error).';msg: '.$error->getMessage().';code: '.$error->getCode().';line: '.$error->getLine().';in_file: '.$error->getFile().';';
            error_log($message);
            echo json_encode(array('result'=>'failed','error'=>$message));
        }
        die();
    }

    public function opt_single_image()
    {
        $this->ajax_check_security();

        if(!isset($_POST['id']))
        {
            die();
        }

        set_time_limit(120);

        $task=new WPvivid_ImgOptim_Task();

        $id=sanitize_key($_POST['id']);

        $options=get_option('wpvivid_optimization_options',array());

        $ret=$task->init_manual_task($id,$options);

        $this->flush($ret);

        if($ret['result']=='success')
        {
            $task->do_optimize_image();
        }

        die();
    }

    public function get_single_image_progress()
    {
        $this->ajax_check_security();

        $task=new WPvivid_ImgOptim_Task();
        $ret=$task->get_manual_task_progress();

        if(!isset($_POST['ids'])||!is_string($_POST['ids']))
        {
            die();
        }

        $ids=sanitize_text_field($_POST['ids']);
        $ids=json_decode($ids,true);

        $running=false;

        if(isset($_POST['page']))
        {
            $page=sanitize_text_field($_POST['page']);
        }
        else
        {
            $page='media';
        }

        foreach ($ids as $id)
        {
            if(!$task->is_image_optimized($id))
            {
                if($task->is_image_progressing($id))
                {
                    $running=true;
                }
            }
        }

        foreach ($ids as $id)
        {
            if($page=='edit')
            {
                $html='<h4>'.__('WPvivid Imgoptim', 'wpvivid-imgoptim').'</h4>';
            }
            else
            {
                $html='';
            }

            if(!$task->is_image_optimized($id))
            {
                if($task->is_image_progressing($id))
                {
                    $html.= "<a  class='wpvivid-media-progressing button-primary' data-id='{$id}'>".__('Optimizing...', 'wpvivid-imgoptim')."</a>";
                }
                else
                {
                    if($running)
                    {
                        $html.= "<a  class='wpvivid-media button-primary button-disabled' data-id='{$id}'>".__('Optimize','wpvivid-imgoptim')."</a>";
                    }
                    else
                    {
                        $html.= "<a  class='wpvivid-media button-primary' data-id='{$id}'>".__('Optimize','wpvivid-imgoptim')."</a>";
                    }

                }
            }
            else
            {
                $meta=get_post_meta( $id,'wpvivid_image_optimize_meta', true );
                $percent=round(100-($meta['sum']['opt_size']/$meta['sum']['og_size'])*100,2);
                $html.='<ul>';
                $html.= '<li><span>'.__('Optimized size','wpvivid-imgoptim').' : </span><strong>'.size_format($meta['sum']['opt_size'],2).'</strong></li>';
                $html.= '<li><span>'.__('Saved','wpvivid-imgoptim').' : </span><strong>'.$percent.'%</strong></li>';
                $html.= '<li><span>'.__('Original size','wpvivid-imgoptim').' : </span><strong>'.size_format($meta['sum']['og_size'],2).'</strong></li>';
                $html.='<li><p style="border-bottom:1px solid #D2D3D6;"></p></li>';
                $html.="<li><a  class='wpvivid-media-restore button-primary' data-id='{$id}'>".__('Restore','wpvivid-imgoptim')."</a></li>";
                $html.='</ul>';
            }
            $ret[$id]['html']=$html;
        }

        echo json_encode($ret);

        die();
    }

    public function delete_images( $image_id )
    {
        if ( empty( $image_id ) )
        {
            return;
        }

        $this->delete_backup($image_id);

        do_action('wpvivid_delete_image',$image_id);
    }

    public function delete_backup($image_id)
    {
        $backup_image_meta = get_post_meta( $image_id, 'wpvivid_backup_image_meta', true );

        if(!empty($backup_image_meta))
        {
            foreach ($backup_image_meta as $meta)
            {
                if(file_exists($meta['backup_path']))
                    @unlink($meta['backup_path']);
            }

            delete_post_meta( $image_id, 'wpvivid_image_optimize_meta');
            delete_post_meta( $image_id, 'wpvivid_backup_image_meta');
        }
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
                                <a href="https://wpvivid.com/wpvivid-image-optimization-plugin-lazyload-images"><b><?php _e('Lazyload', 'wpvivid-imgoptim'); ?></b></a>
                                <small><span style="float: right;"><a href="#" style="text-decoration: none;"><span class="dashicons dashicons-migrate wpvivid-dashicons-grey"></span></a></span></small><br>
                            </li>
                            <li><span class="dashicons dashicons-cloud  wpvivid-dashicons-grey"></span>
                                <a href="https://docs.wpvivid.com/wpvivid-image-optimization-pro-integrate-cdn.html"><b><?php _e('cdn Integration', 'wpvivid-imgoptim'); ?></b></a>
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

    public function is_image_optimized($optimized,$post_id)
    {
        if($optimized===false)
        {
            return false;
        }

        $options=get_option('wpvivid_optimization_options',array());

        $only_resize=isset($options['only_resize'])?$options['only_resize']:false;

        if($only_resize)
        {
            return false;
        }

        return $optimized;
    }

    public function og_skip_file($skip,$filename)
    {
        if($skip)
        {
            return true;
        }

        if(!file_exists($filename))
        {
            return true;
        }
        else
        {
            return $skip;
        }
    }

    public function skip_file($skip,$filename,$key)
    {
        if($skip)
        {
            return true;
        }

        if($this->skip_size($key))
        {
            return true;
        }

        if(!file_exists($filename))
        {
            return true;
        }

        if(filesize($filename)>1024 *1024 *5)
        {
            return true;
        }

        return $skip;
    }

    public function skip_size($size_key)
    {
        $options=get_option('wpvivid_image_opt_task',array());

        if(isset($options['skip_size'])&&isset($options['skip_size'][$size_key]))
        {
            return $options['skip_size'][$size_key];
        }
        return false;
    }

    public function opt_skip_file($skip,$filename,$image_opt_meta,$key)
    {
        if($this->skip_size($key))
        {
            return true;
        }
        else
        {
            return false;
        }
    }
}