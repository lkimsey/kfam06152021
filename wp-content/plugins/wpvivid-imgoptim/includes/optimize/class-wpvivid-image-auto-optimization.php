<?php

if (!defined('WPVIVID_IMGOPTIM_DIR'))
{
    die;
}

class WPvivid_Image_Auto_Optimization
{
    public $auto_opt_ids;

    public function __construct()
    {
        $this->auto_opt_ids=array();

        add_action( 'add_attachment',                  array( $this, 'add_auto_opt_id' ), 1000 );
        add_filter( 'wp_generate_attachment_metadata', array( $this, 'update_auto_opt_id_status' ), 1000, 2 );
        add_filter( 'wp_update_attachment_metadata',   array( $this, 'auto_optimize' ), 1000, 2 );

        add_filter( 'wpvivid_allowed_image_auto_optimization',   array( $this, 'allowed_image_auto_optimization' ), 10 );
    }

    public function allowed_image_auto_optimization($allowed)
    {
        $options=get_option('wpvivid_optimization_options',array());

        $is_auto=isset($options['auto_optimize'])?$options['auto_optimize']:true;

        if($is_auto)
        {
            return true;
        }
        else
        {
            return false;
        }
    }

    public function add_auto_opt_id($attachment_id)
    {
        $is_auto=apply_filters('wpvivid_allowed_image_auto_optimization',false);

        if($is_auto)
        {
            $this->auto_opt_ids[$attachment_id]=0;
        }
    }

    public function update_auto_opt_id_status($metadata, $attachment_id)
    {
        if(isset( $this->auto_opt_ids[$attachment_id]))
        {
            if ( ! wp_attachment_is_image( $attachment_id ) )
            {
                unset($this->auto_opt_ids[$attachment_id]);
            }
            $this->auto_opt_ids[$attachment_id]=1;
        }

        return $metadata;
    }

    public function auto_optimize($metadata, $attachment_id)
    {
        set_time_limit(300);

        $is_auto=apply_filters('wpvivid_allowed_image_auto_optimization',false);

        if($is_auto)
        {
            if(isset($this->auto_opt_ids[$attachment_id])&&$this->auto_opt_ids[$attachment_id])
            {
                if(get_post_mime_type($attachment_id)=='image/jpeg'||get_post_mime_type($attachment_id)=='image/jpg'||get_post_mime_type($attachment_id)=='image/png')
                {
                    $task=new WPvivid_ImgOptim_Task();

                    $options=get_option('wpvivid_optimization_options',array());

                    $ret=$task->init_manual_task($attachment_id,$options);

                    if($ret['result']=='success')
                    {
                        $task->do_optimize_image();
                    }
                }
            }
        }

        return $metadata;
    }
}