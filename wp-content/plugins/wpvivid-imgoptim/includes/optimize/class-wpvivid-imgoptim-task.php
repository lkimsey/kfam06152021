<?php

if (!defined('WPVIVID_IMGOPTIM_DIR'))
{
    die;
}


class WPvivid_ImgOptim_Task
{
    public $task;
    public $log=false;

    public $ngg=false;

    public function __construct()
    {
        if (class_exists( 'C_NextGEN_Bootstrap' ))
        {
            $this->ngg=new WPvivid_Ngg_Image_Optimize();
        }
    }

    public function cancel()
    {
        update_option('wpvivid_image_opt_task_cancel',true);
    }

    public function init_task($options=array())
    {
        $this->task=array();
        $need_optimize_images=$this->get_need_optimize_images();

        $this->task['images']=array();
        $this->task['ngg_images']=array();

        if(!empty($need_optimize_images))
        {
            foreach ($need_optimize_images as $image)
            {
                if($this->is_image_optimized($image))
                    continue;
                $sub_task['id']=$image;
                $sub_task['finished']=0;
                $this->task['images'][$image]=$sub_task;
            }
        }

        if($this->ngg!==false)
        {
            $need_ngg_optimize_images=$this->ngg->get_need_optimize_images();
            if(!empty($need_ngg_optimize_images))
            {
                foreach ($need_optimize_images as $image)
                {
                    if($this->ngg->is_image_optimized($image))
                        continue;
                    $sub_task['id']=$image;
                    $sub_task['finished']=0;
                    $this->task['ngg_images'][$image]=$sub_task;
                }
            }
        }

        if(empty($this->task['images'])&&empty($this->task['ngg_images']))
        {
            $ret['result']='failed';
            $ret['error']='All image(s) optimized successfully.';
            update_option('wpvivid_image_opt_task',$this->task);
            return $ret;
        }
        $options=apply_filters('wpvivid_optimization_custom_option',$options);
        $this->task['options']=$options;
        $this->task['status']='running';
        $this->task['last_update_time']=time();
        $this->task['retry']=0;
        $this->task['log']=uniqid('wpvivid-');
        $this->log=new WPvivid_Image_Optimize_Log();
        $this->log->CreateLogFile();
        $this->task['error']='';
        update_option('wpvivid_image_opt_task',$this->task);

        $ret['result']='success';
        return $ret;
    }

    public function init_manual_task($id,$options=array())
    {
        $this->task=array();

        if($this->is_image_optimized($id))
        {
            $ret['result']='failed';
            $ret['error']='All image(s) optimized successfully.';
            update_option('wpvivid_image_opt_task',$this->task);
            return $ret;
        }
        $sub_task['id']=$id;
        $sub_task['finished']=0;
        $this->task['images'][$id]=$sub_task;
        $options=apply_filters('wpvivid_optimization_custom_option',$options);
        $this->task['options']=$options;
        $this->task['status']='running';
        $this->task['last_update_time']=time();
        $this->task['retry']=0;
        $this->task['log']=uniqid('wpvivid-');
        $this->log=new WPvivid_Image_Optimize_Log();
        $this->log->CreateLogFile();
        $this->task['error']='';
        update_option('wpvivid_image_opt_task',$this->task);

        $ret['result']='success';
        return $ret;
    }

    public function set_options($options=array())
    {
        if(empty($options))
        {
            $options=get_option('wpvivid_optimization_options',array());
            $options=apply_filters('wpvivid_optimization_custom_option',$options);
            $this->task['options']=$options;

        }
        else
        {
            $options=apply_filters('wpvivid_optimization_custom_option',$options);
            $this->task['options']=$options;
        }
        $this->task['last_update_time']=time();
        update_option('wpvivid_image_opt_task',$this->task);
    }

    public function is_image_optimized($post_id)
    {
        $meta=get_post_meta($post_id,'wpvivid_image_optimize_meta',true);
        if(!empty($meta)&&isset($meta['size'])&&!empty($meta['size']))
        {
            foreach ($meta['size'] as $size_key => $size_data)
            {
                if(!isset($size_data['opt_status'])||$size_data['opt_status']==0)
                {
                    return false;
                }
            }

            return apply_filters('wpvivid_is_image_optimized',true,$post_id);
        }
        else
        {
            return false;
        }

    }

    public function is_image_progressing($post_id)
    {
        $this->task=get_option('wpvivid_image_opt_task',array());

        if(empty($this->task))
        {
           return false;
        }

        if(isset($this->task['images']))
        {
            if(!array_key_exists($post_id,$this->task['images']))
            {
                return false;
            }

            if(isset($this->task['status']))
            {
                if($this->task['status']=='error')
                {
                   return false;
                }
                else if($this->task['status']=='finished')
                {
                    return false;
                }
                else if($this->task['status']=='completed')
                {
                    return false;
                }
                else
                {
                    return true;
                }
            }
            else
            {
               return false;
            }
        }
        else
        {
            return false;
        }
    }

    public function get_need_optimize_images()
    {
        $query_images_args = array(
            'post_type'      => 'attachment',
            'post_mime_type' => 'image',
            'post_status'    => 'inherit',
            'posts_per_page' => - 1,
        );

        $query_images = new WP_Query( $query_images_args );

        $images = array();
        foreach ( $query_images->posts as $image )
        {
            if(get_post_mime_type($image->ID)=='image/jpeg'||get_post_mime_type($image->ID)=='image/jpg'||get_post_mime_type($image->ID)=='image/png')
            {
                $images[] = $image->ID ;
            }
        }
        return $images;
    }

    public function WriteLog($log,$type)
    {
        if (is_a($this->log, 'WPvivid_Image_Optimize_Log'))
        {
            $this->log->WriteLog($log,$type);
        }
        else
        {
            $this->log=new WPvivid_Image_Optimize_Log();
            $this->log->OpenLogFile();
            $this->log->WriteLog($log,$type);
        }
        $this->task=get_option('wpvivid_image_opt_task',array());
        $this->task['last_log']=$log;
        update_option('wpvivid_image_opt_task',$this->task);
    }

    public function get_last_log()
    {
        $this->task=get_option('wpvivid_image_opt_task',array());
        if(empty($this->task)||!isset($this->task['last_log']))
        {
            return 'Optimizing images...';
        }
        else
        {
            return $this->task['last_log'];
        }
    }

    public function do_optimize_image()
    {
        $this->task=get_option('wpvivid_image_opt_task',array());
        $cancel=get_option('wpvivid_image_opt_task_cancel',false);
        if($cancel)
        {
            $this->WriteLog('Cancel bulk optimization.','notice');

            include_once WPVIVID_IMGOPTIM_DIR. '/includes/class-wpvivid-imgoptim-connect-server.php';

            $server=new WPvivid_Image_Optimize_Connect_server();

            $info= get_option('wpvivid_imgoptim_user',false);
            $info=apply_filters('wpvivid_imgoptim_user_info',$info);
            if($info===false)
            {
                $ret['result']='failed';
                $ret['error']='Need login';
                return $ret;
            }

            $user_info=$info['token'];

            $server->delete_cache($user_info);
            $this->task['status']='finished';
            $this->task['last_update_time']=time();
            update_option('wpvivid_image_opt_task',$this->task);
            update_option('wpvivid_image_opt_task_cancel',false);
            $ret['result']='success';
            return $ret;
        }

        //$this->WriteLog('Start image optimization.','notice');

        if(empty($this->task)||!isset($this->task['images']))
        {
            $this->WriteLog('Cannot find optimization task, task quit.','notice');
            $ret['result']='success';
            return $ret;
        }

        $this->task['last_update_time']=time();
        update_option('wpvivid_image_opt_task',$this->task);

        $optimized=$this->do_unoptimized_image();

        if($optimized)
        {
            $ret['result']='success';
            return $ret;
        }
        else
        {
            if($this->ngg!==false)
            {
                $optimized=$this->do_ngg_unoptimized_image();
            }
        }

        if(!$optimized)
        {
            $this->finish_optimize_image();
        }
        $ret['result']='success';
        return $ret;
    }

    public function do_unoptimized_image()
    {
        $image_id=false;

        foreach ($this->task['images'] as $image)
        {
            if($image['finished']==0)
            {
                $image_id=$image['id'];
                break;
            }
        }

        if($image_id===false)
        {
            return false;
        }

        $this->task['status']='running';
        $this->task['last_update_time']=time();
        update_option('wpvivid_image_opt_task',$this->task);

        $this->WriteLog('Start optimizing image id:'.$image_id,'notice');

        $ret=$this->optimize_image($image_id);

        if($ret['result']=='success')
        {
            $this->WriteLog('Optimizing image id:'.$image_id.' succeeded.','notice');

            $this->task['images'][$image_id]['finished']=1;
            $this->task['status']='completed';
            $this->task['last_update_time']=time();
            $this->task['retry']=0;
            update_option('wpvivid_image_opt_task',$this->task);
        }
        else
        {
            $this->WriteLog('Optimizing image failed. Error:'.$ret['error'],'error');
            $this->task['status']='error';
            $this->task['error']=$ret['error'];
            $this->task['last_update_time']=time();
            update_option('wpvivid_image_opt_task',$this->task);
        }

        return true;
    }

    public function finish_optimize_image()
    {
        include_once WPVIVID_IMGOPTIM_DIR. '/includes/class-wpvivid-imgoptim-connect-server.php';

        $server=new WPvivid_Image_Optimize_Connect_server();

        $info= get_option('wpvivid_imgoptim_user',false);
        $info=apply_filters('wpvivid_imgoptim_user_info',$info);
        if($info===false)
        {
            $ret['result']='failed';
            $ret['error']='Need login';
            return $ret;
        }

        $user_info=$info['token'];

        $server->delete_cache($user_info);
        $this->task['status']='finished';
        $this->task['last_update_time']=time();
        update_option('wpvivid_image_opt_task',$this->task);

        $ret['result']='success';
        return $ret;
    }

    public function do_ngg_unoptimized_image()
    {
        $image_id=false;

        foreach ($this->task['ngg_images'] as $image)
        {
            if($image['finished']==0)
            {
                $image_id=$image['id'];
                break;
            }
        }

        if($image_id===false)
        {
            return false;
        }

        $this->task['status']='running';
        $this->task['last_update_time']=time();
        update_option('wpvivid_image_opt_task',$this->task);

        $this->WriteLog('Start optimizing ngg image id:'.$image_id,'notice');
        $ret=$this->optimize_ngg_image($image_id);

        if($ret['result']=='success')
        {
            $this->WriteLog('Optimizing ngg image id:'.$image_id.' succeeded.','notice');
            do_action('wpvivid_do_after_ngg_optimized',$image_id);

            $this->task['ngg_images'][$image_id]['finished']=1;
            $this->task['status']='completed';
            $this->task['last_update_time']=time();
            $this->task['retry']=0;
            update_option('wpvivid_image_opt_task',$this->task);
        }
        else
        {
            $this->WriteLog('Optimizing image failed. Error:'.$ret['error'],'error');
            $this->task['status']='error';
            $this->task['error']=$ret['error'];
            $this->task['last_update_time']=time();
            update_option('wpvivid_image_opt_task',$this->task);
        }

        return true;
    }

    public function optimize_image($image_id)
    {
        $files=array();

        $post_mime_type=get_post_mime_type($image_id);

        if($post_mime_type=='image/jpeg'||$post_mime_type=='image/jpg'||$post_mime_type=='image/png')
        {

        }
        else
        {
            $ret['result']='success';
            return $ret;
        }

        $file_path = get_attached_file( $image_id );
        $meta = wp_get_attachment_metadata( $image_id, true );
        $image_opt_meta = get_post_meta( $image_id, 'wpvivid_image_optimize_meta', true );
        if(empty($image_opt_meta))
        {
            $image_opt_meta=$this->init_image_opt_meta($image_id);
        }

        if(empty($meta['sizes']))
        {
            if(apply_filters('wpvivid_imgoptim_og_skip_file',false,$file_path))
            {
                $this->WriteLog('Skip file '.$file_path,'notice');
            }
            else
            {
                $files['og']=$file_path;
                if(!isset($image_opt_meta['size']['og']))
                {
                    $image_opt_meta['size']['og']['og_size']=filesize($file_path);
                    $image_opt_meta['sum']['og_size']+=$image_opt_meta['size']['og']['og_size'];
                    $image_opt_meta['size']['og']['opt_size']=0;
                    $image_opt_meta['size']['og']['opt_status']=0;
                    $image_opt_meta=apply_filters('wpvivid_imgoptim_generate_meta',$image_opt_meta,$image_id,'og');
                }
            }
        }
        else
        {
            foreach ( $meta['sizes'] as $size_key => $size_data )
            {
                $filename= path_join( dirname( $file_path ), $size_data['file'] );

                if(apply_filters('wpvivid_imgoptim_skip_file',false,$filename,$size_key))
                {
                    $this->WriteLog('Skip file '.$filename,'notice');
                    continue;
                }

                $files[$size_key] =$filename;
                if(!isset($image_opt_meta['size'][$size_key]))
                {
                    $image_opt_meta['size'][$size_key]['og_size']=filesize($filename);
                    $image_opt_meta['sum']['og_size']+=$image_opt_meta['size'][$size_key]['og_size'];
                    $image_opt_meta['size'][$size_key]['opt_size']=0;
                    $image_opt_meta['size'][$size_key]['opt_status']=0;
                    $image_opt_meta=apply_filters('wpvivid_imgoptim_generate_meta',$image_opt_meta,$image_id,$size_key);
                }
            }

            if(!in_array($file_path,$files))
            {
                if(apply_filters('wpvivid_imgoptim_og_skip_file',false,$file_path))
                {
                    $this->WriteLog('Skip file '.$file_path,'notice');
                }
                else
                {
                    $files['og']=$file_path;
                    if(!isset($image_opt_meta['size']['og']))
                    {
                        $image_opt_meta['size']['og']['og_size']=filesize($file_path);
                        $image_opt_meta['sum']['og_size']+=$image_opt_meta['size']['og']['og_size'];
                        $image_opt_meta['size']['og']['opt_size']=0;
                        $image_opt_meta['size']['og']['opt_status']=0;
                        $image_opt_meta=apply_filters('wpvivid_imgoptim_generate_meta',$image_opt_meta,$image_id,'og');
                    }
                }
            }
        }

        $image_opt_meta['last_update_time']=time();
        update_post_meta($image_id,'wpvivid_image_optimize_meta',$image_opt_meta);

        if($image_opt_meta['sum']['options']['backup'])
        {
            $this->WriteLog('Start backing up image(s).','notice');
            $this->backup($files,$image_id);
        }

        if($this->is_resize($image_id))
        {
            $this->WriteLog('Start resizing image id:'.$image_id,'notice');
            $this->resize($image_id);
        }

        $only_resize=isset($this->task['options']['only_resize'])?$this->task['options']['only_resize']:false;

        if($only_resize)
        {
            $ret['result']='success';
            return $ret;
        }

        $retry=0;

        if(empty($files))
        {
            $ret['result']='success';
            return $ret;
        }
        $ret['result']='success';

        foreach ($files as $size_key=>$file)
        {
            if(apply_filters('wpvivid_imgoptim_opt_skip_file',false,$file,$image_opt_meta,$size_key))
            {
                $this->WriteLog('Skip optimized size '.$size_key,'notice');
            }

            $type='';
            if(get_post_mime_type($image_id)=='image/jpeg')
            {
                $type='jpg';
            }
            else if(get_post_mime_type($image_id)=='image/jpg')
            {
                $type='jpg';
            }
            else if(get_post_mime_type($image_id)=='image/png')
            {
                $type='png';
            }

            $ret['result']='success';
            if(!empty($type))
            {
                if($image_opt_meta['size'][$size_key]['opt_status']==0)
                {
                    while ($retry<3)
                    {
                        $this->WriteLog('Start compressing image '.basename($file),'notice');
                        $ret=$this->compress_image($type,$file,$file,$this->task['options']);
                        if($ret['result']=='failed')
                        {
                            $this->WriteLog('Compressing image '.basename($file).' failed. Error:'.$ret['error'],'notice');
                            if(isset($ret['remain'])&&$ret['remain']==false)
                            {
                                return $ret;
                            }

                            $retry++;
                            $this->WriteLog('Start retrying optimization. Count:'.$retry,'notice');
                        }
                        else
                        {
                            $this->WriteLog('Compressing image '.basename($file).' succeeded.','notice');
                            $retry=0;
                            clearstatcache();
                            $image_opt_meta['size'][$size_key]['opt_size']=filesize($file);
                            $image_opt_meta['sum']['opt_size']+=$image_opt_meta['size'][$size_key]['opt_size'];
                            $image_opt_meta['size'][$size_key]['opt_status']=1;
                            $image_opt_meta['last_update_time']=time();
                            update_post_meta($image_id,'wpvivid_image_optimize_meta',$image_opt_meta);
                            break;
                        }
                    }

                    if($ret['result']=='failed')
                    {
                        $this->WriteLog('Compressing image '.basename($file).' failed. Error:'.$ret['error'],'error');
                        return $ret;
                    }
                }

                $image_opt_meta=apply_filters('wpvivid_do_optimized',$image_opt_meta,$image_id,$file,$size_key);
                update_post_meta($image_id,'wpvivid_image_optimize_meta',$image_opt_meta);
            }
        }
        $image_opt_meta['last_update_time']=time();
        update_post_meta($image_id,'wpvivid_image_optimize_meta',$image_opt_meta);

        if($ret['result']=='success')
        {
            do_action('wpvivid_do_after_optimized',$image_id);
        }
        else
        {
            $this->WriteLog('Optimize images '.$image_id.' failed.Error:'.$ret['error'],'notice');
        }

        $ret['result']='success';
        return $ret;
    }

    public function optimize_ngg_image($image_id)
    {
        $files=array();

        $file_path = $this->ngg->get_attached_file($image_id);
        $meta = $this->ngg->get_attachment_metadata( $image_id );
        $image_opt_meta = $this->ngg->get_ngg_meta( $image_id );
        if(empty($image_opt_meta))
        {
            $image_opt_meta=array();
            $image_opt_meta['sum']['og_size']=0;
            $image_opt_meta['sum']['opt_size']=0;
            $image_opt_meta['sum']['options']['mode']='lossless';

            $image_opt_meta['sum']['options']['backup']=$this->is_backup();
            $image_opt_meta['size']=array();
        }

        if(!empty($meta['sizes']))
        {
            foreach ( $meta['sizes'] as $size_key => $size_data )
            {
                $filename= $size_data['file'];

                if(!file_exists($filename))
                {
                    continue;
                }

                if(apply_filters('wpvivid_imgoptim_skip_file',false,$filename))
                {
                    $this->WriteLog('Skip file '.$filename,'notice');
                    continue;
                }

                $files[$size_key] =$filename;
                if(!isset($image_opt_meta['size'][$size_key]))
                {
                    $image_opt_meta['size'][$size_key]['og_size']=filesize($filename);
                    $image_opt_meta['sum']['og_size']+=$image_opt_meta['size'][$size_key]['og_size'];
                    $image_opt_meta['size'][$size_key]['opt_size']=0;
                    $image_opt_meta['size'][$size_key]['opt_status']=0;
                }
            }

            if(!in_array($file_path,$files))
            {
                if(file_exists($file_path))
                {
                    if(apply_filters('wpvivid_imgoptim_skip_file',false,$file_path))
                    {
                        $this->WriteLog('Skip file '.$file_path,'notice');
                    }
                    else
                    {
                        $files['og']=$file_path;
                        if(!isset($image_opt_meta['size']['og']))
                        {
                            $image_opt_meta['size']['og']['og_size']=filesize($file_path);
                            $image_opt_meta['sum']['og_size']+=$image_opt_meta['size']['og']['og_size'];
                            $image_opt_meta['size']['og']['opt_size']=0;
                            $image_opt_meta['size']['og']['opt_status']=0;
                        }
                    }
                }
            }
        }
        else
        {
            if(file_exists($file_path))
            {
                if(apply_filters('wpvivid_imgoptim_skip_file',false,$file_path))
                {
                    $this->WriteLog('Skip file '.$file_path,'notice');
                }
                else
                {
                    $files['og']=$file_path;
                    if(!isset($image_opt_meta['size']['og']))
                    {
                        $image_opt_meta['size']['og']['og_size']=filesize($file_path);
                        $image_opt_meta['sum']['og_size']+=$image_opt_meta['size']['og']['og_size'];
                        $image_opt_meta['size']['og']['opt_size']=0;
                        $image_opt_meta['size']['og']['opt_status']=0;
                    }
                }
            }
        }

        $this->ngg->update_ngg_meta($image_id,$image_opt_meta);

        if($image_opt_meta['sum']['options']['backup'])
        {
            $this->WriteLog('Start backing up image(s).','notice');
            $this->ngg_backup($files,$image_id);
        }
        $retry=0;

        $ret['result']='success';

        foreach ($files as $size_key=>$file)
        {
            if($image_opt_meta['size'][$size_key]['opt_status']==1)
            {
                $this->WriteLog('Skip optimized size '.$size_key,'notice');
                continue;
            }

            $type='';
            if($this->ngg->get_mime_type($file)=='image/jpeg')
            {
                $type='jpg';
            }
            else if($this->ngg->get_mime_type($file)=='image/jpg')
            {
                $type='jpg';
            }
            else if($this->ngg->get_mime_type($file)=='image/png')
            {
                $type='png';
            }

            $ret['result']='success';
            if(!empty($type))
            {
                while ($retry<3)
                {
                    $this->WriteLog('Start compressing image '.basename($file),'notice');
                    $ret=$this->compress_image($type,$file,$file,$this->task['options']);
                    if($ret['result']=='failed')
                    {
                        $this->WriteLog('Compressing image '.basename($file).' failed. Error:'.$ret['error'],'notice');
                        if(isset($ret['remain'])&&$ret['remain']==false)
                        {
                            return $ret;
                        }

                        $retry++;
                        $this->WriteLog('Start retrying optimization. Count:'.$retry,'notice');

                    }
                    else
                    {
                        $this->WriteLog('Compressing image '.basename($file).' succeeded..','notice');
                        $retry=0;
                        clearstatcache();
                        $image_opt_meta['size'][$size_key]['opt_size']=filesize($file);
                        $image_opt_meta['sum']['opt_size']+=$image_opt_meta['size'][$size_key]['opt_size'];
                        $image_opt_meta['size'][$size_key]['opt_status']=1;
                        $this->ngg->update_ngg_meta($image_id,$image_opt_meta);
                        break;
                    }
                }

                if($ret['result']=='failed')
                {
                    $this->WriteLog('Compressing image '.basename($file).' failed. Error:'.$ret['error'],'error');
                    return $ret;
                }
            }
        }

        $this->ngg->update_ngg_meta($image_id,$image_opt_meta);

        if($ret['result']!='success')
        {
            $this->WriteLog('Optimize images '.$image_id.' failed.Error:'.$ret['error'],'notice');
        }

        $ret['result']='success';
        return $ret;
    }

    public function is_backup()
    {
        $this->task=get_option('wpvivid_image_opt_task',array());

        return isset($this->task['options']['backup'])?$this->task['options']['backup']:true;
    }

    public function is_resize($id)
    {
        $this->task=get_option('wpvivid_image_opt_task',array());

        $resize=isset($this->task['options']['resize'])?$this->task['options']['resize']:false;

        if($resize!==false&&$resize['enable'])
        {
            $meta =wp_get_attachment_metadata( $id );

            if ( ! empty( $meta['width'] ) && ! empty( $meta['height'] ) )
            {
                $old_width  = $meta['width'];
                $old_height = $meta['height'];
                $max_width=isset($resize['width'])?$resize['width']:1280;
                $max_height=isset($resize['height'])?$resize['height']:1280;

                if ( ( $old_width > $max_width && $max_width > 0 ) || ( $old_height > $max_height && $max_height > 0 ) )
                {
                    return true;
                }
            }

            return false;
        }
        else
        {
            return false;
        }
    }

    public function resize($image_id)
    {
        $file_path = get_attached_file( $image_id );

        $resize=isset($this->task['options']['resize'])?$this->task['options']['resize']:false;

        if($resize===false)
        {
            return true;
        }

        $max_width=isset($resize['width'])?$resize['width']:1280;
        $max_height=isset($resize['height'])?$resize['height']:1280;

        $data=image_make_intermediate_size($file_path,$max_width,$max_height);
        if(!$data)
        {
            return false;
        }

        $resize_path = path_join( dirname( $file_path ),$data['file']);
        if (!file_exists($resize_path))
        {
            return false;
        }

        @copy($resize_path,$file_path);
        $meta = wp_get_attachment_metadata($image_id);

        if(!empty($meta['sizes']))
        {
            $path_parts = pathinfo($resize_path );
            $filename   = ! empty( $path_parts['basename'] ) ? $path_parts['basename'] : $path_parts['filename'];
            $unlink=true;
            foreach ( $meta['sizes'] as $image_size )
            {
                if ( false === strpos( $image_size['file'], $filename ) )
                {
                    continue;
                }
                $unlink = false;
            }

            if($unlink)
            {
                @unlink($resize_path );
            }
        }
        else
        {
            @unlink( $resize_path );
        }

        $meta['width']=$data['width'];
        $meta['height']=$data['height'];
        wp_update_attachment_metadata( $image_id, $meta );
        return true;
    }

    public function skip_size($size_key)
    {
        $this->task=get_option('wpvivid_image_opt_task',array());

        if(isset($this->task['options']['skip_size'])&&isset($this->task['options']['skip_size'][$size_key]))
        {
            return $this->task['options']['skip_size'][$size_key];
        }
        return false;
    }

    public function backup($files,$image_id)
    {
        $backup_meta=array();
        foreach ($files as $file)
        {
            if(file_exists($file))
            {
                $backup_dir=$this->get_backup_folder($file);

                if(!file_exists($backup_dir))
                {
                    @copy($file,$backup_dir);
                }
                $file_data['og_path']=wp_slash($file);
                $file_data['backup_path']=wp_slash($backup_dir);
                $backup_meta[]=$file_data;
            }
        }
        if(!empty($backup_meta))
        {
            update_post_meta($image_id,'wpvivid_backup_image_meta',$backup_meta);
        }
    }

    public function ngg_backup($files,$image_id)
    {
        $backup_meta=array();
        foreach ($files as $file)
        {
            if(file_exists($file))
            {
                $backup_dir=$this->ngg->get_backup_folder($file);

                if(!file_exists($backup_dir))
                {
                    @copy($file,$backup_dir);
                }
                $file_data['og_path']=wp_slash($file);
                $file_data['backup_path']=wp_slash($backup_dir);
                $backup_meta[]=$file_data;
            }
        }
        if(!empty($backup_meta))
        {
            update_post_meta($image_id,'wpvivid_backup_image_meta',$backup_meta);
        }
    }

    public function get_backup_folder( $attachment_path )
    {
        $options=get_option('wpvivid_optimization_options',array());
        $backup_path=isset($options['backup_path'])?$options['backup_path']:WPVIVID_IMGOPTIM_DEFAULT_SAVE_DIR;
        if(!is_dir(WP_CONTENT_DIR.DIRECTORY_SEPARATOR.$backup_path))
        {
            @mkdir(WP_CONTENT_DIR.DIRECTORY_SEPARATOR.$backup_path,0777,true);
            @fopen(WP_CONTENT_DIR.DIRECTORY_SEPARATOR.$backup_path.DIRECTORY_SEPARATOR.'index.html', 'x');
            $tempfile=@fopen(WP_CONTENT_DIR.DIRECTORY_SEPARATOR.$backup_path.DIRECTORY_SEPARATOR.'.htaccess', 'x');
            if($tempfile)
            {
                $text="deny from all";
                fwrite($tempfile,$text );
                fclose($tempfile);
            }
        }

        $upload_dir = wp_get_upload_dir();
        $upload_root=$this->transfer_path($upload_dir['basedir']);
        $attachment_dir=dirname($attachment_path);
        $attachment_dir=$this->transfer_path($attachment_dir);
        $sub_dir=str_replace($upload_root,'',$attachment_dir);
        $sub_dir=untrailingslashit($sub_dir);
        $path=WP_CONTENT_DIR.DIRECTORY_SEPARATOR.$backup_path.DIRECTORY_SEPARATOR.'backup_image'.DIRECTORY_SEPARATOR.$sub_dir;

        if(!file_exists($path))
        {
            @mkdir($path,0777,true);
        }

        return $path.DIRECTORY_SEPARATOR.basename($attachment_path);
    }

    private function transfer_path($path)
    {
        $path = str_replace('\\','/',$path);
        $values = explode('/',$path);
        return implode(DIRECTORY_SEPARATOR,$values);
    }

    public function compress_image($type,$in,$out,$options)
    {
        $size=filesize($in);

        if($size< 1024 *1024 *2)
        {
            include_once WPVIVID_IMGOPTIM_DIR. '/includes/class-wpvivid-imgoptim-connect-server.php';

            $server=new WPvivid_Image_Optimize_Connect_server();

            $info= get_option('wpvivid_imgoptim_user',false);
            $info=apply_filters('wpvivid_imgoptim_user_info',$info);
            if($info===false)
            {
                $ret['result']='failed';
                $ret['error']='Need login';
                return $ret;
            }

            $user_info=$info['token'];
            $ret=$server->upload_small_file($user_info,$type,$in,$out,$options);
        }
        else
        {
            $ret=$this->upload_file($type,$in,$out,$options);
        }

        return $ret;
    }

    public function upload_file($type,$file,$out,$options)
    {
        include_once WPVIVID_IMGOPTIM_DIR. '/includes/class-wpvivid-imgoptim-connect-server.php';

        $server=new WPvivid_Image_Optimize_Connect_server();

        $info= get_option('wpvivid_imgoptim_user',false);
        $info=apply_filters('wpvivid_imgoptim_user_info',$info);
        if($info===false)
        {
            $ret['result']='failed';
            $ret['error']='Need login';
            return $ret;
        }

        $user_info=$info['token'];
        $ret=$server->delete_exist_file($user_info,$file);

        if($ret['result']!='success')
        {
            return $ret;
        }

        $file_size=filesize($file);

        $offset=0;

        $handle=fopen($file,'rb');
        $upload_size=1024*1024*2;

        while(!feof($handle))
        {
            $data = fread($handle,$upload_size);

            $ret=$server->upload_loop($user_info,$file,$offset,$upload_size,$data);

            if($ret['result']=='success')
            {
                $offset+=$upload_size;
            }
            else
            {
                return $ret;
            }
        }

        fclose($handle);
        $ret=$server->compress_image_without_upload($user_info,$type,$file,$options);

        if($ret['result']!='success')
        {
            return $ret;
        }
        $ret=$server->download_file($user_info,$ret['output'],$out);

        return $ret;
    }

    public function get_task_status()
    {
        $this->task=get_option('wpvivid_image_opt_task',array());

        if(empty($this->task))
        {
            $ret['result']='failed';
            $ret['error']='All image(s) optimized successfully.';
            return $ret;
        }

        if($this->task['status']=='error')
        {
            $ret['result']='failed';
            $ret['error']=$this->task['error'];
        }
        else if($this->task['status']=='completed')
        {
            $ret['result']='success';
            $ret['status']='completed';
        }
        else if($this->task['status']=='finished')
        {
            $ret['result']='success';
            $ret['status']='finished';
        }
        else if($this->task['status']=='timeout')
        {
            $ret['result']='success';
            $ret['status']='completed';
        }
        else
        {
            $ret['result']='success';
            $ret['status']='running';
        }
        return $ret;
    }

    public function get_task_progress()
    {
        $this->task=get_option('wpvivid_image_opt_task',array());

        if(empty($this->task))
        {
            $ret['result']='failed';
            $ret['error']='All image(s) optimized successfully.';
            $ret['timeout']=0;
            $ret['percent']=0;
            $ret['total_images']=0;
            $ret['optimized_images']=0;
            $ret['log']='All image(s) optimized successfully.';
            return $ret;
        }

        if(isset($this->task['images']))
        {
            $ret['total_images']=sizeof($this->task['images']);
        }
        else
        {
            $ret['total_images']=0;
        }

        $ret['optimized_images']=0;
        if(isset($this->task['images']))
        {
            foreach ($this->task['images'] as $image)
            {
                if($image['finished'])
                {
                    $ret['optimized_images']++;
                }
            }
        }

        if(isset($this->task['status']))
        {
            if($this->task['status']=='error')
            {
                $ret['result']='failed';
                $ret['error']=$this->task['error'];
                $ret['timeout']=0;
                $ret['percent']= intval(($ret['optimized_images']/$ret['total_images'])*100);
                $ret['log']=$this->task['error'];
            }
            else if($this->task['status']=='finished')
            {
                $ret['result']='success';
                $ret['continue']=0;
                $ret['finished']=1;
                $ret['timeout']=0;
                $ret['percent']= 100;
                $ret['log']='Finish Optimizing images.';
            }
            else if($this->task['status']=='completed')
            {
                $ret['result']='success';
                $ret['continue']=0;
                $ret['finished']=0;
                $ret['timeout']=0;
                $ret['percent']= intval(($ret['optimized_images']/$ret['total_images'])*100);
                $ret['log']=$this->get_last_log();
                //$ret['log']='Optimizing images...';
            }
            else
            {
                if(isset($this->task['last_update_time']))
                {
                    if(time()-$this->task['last_update_time']>120)
                    {
                        $this->task['last_update_time']=time();
                        $this->task['retry']++;
                        $this->task['status']='timeout';
                        update_option('wpvivid_image_opt_task',$this->task);
                        if($this->task['retry']<3)
                        {
                            $ret['timeout']=1;
                        }
                        else
                        {
                            $ret['timeout']=0;
                            if($this->log)
                            {
                                $this->log=new WPvivid_Image_Optimize_Log();
                                $this->log->OpenLogFile();
                                $this->log->WriteLog('To many retry attempts. Task timed out.','error');
                                $this->log->Copy_To_Error();
                            }
                            update_option('wpvivid_image_opt_task',array());
                        }

                        $ret['result']='failed';
                        $ret['error']='task timeout';
                        $ret['percent']=0;
                        $ret['retry']=$this->task['retry'];
                        $ret['total_images']=0;
                        $ret['optimized_images']=0;
                        $ret['log']='task time out';
                    }
                    else
                    {
                        $ret['continue']=1;
                        $ret['finished']=0;
                        $ret['timeout']=0;
                        $ret['running_time']=time()-$this->task['last_update_time'];
                        $ret['result']='success';
                        $ret['percent']= intval(($ret['optimized_images']/$ret['total_images'])*100);

                        $ret['log']=$this->get_last_log();
                        //$ret['log']='Optimizing images...';
                    }
                }
                else
                {
                    $ret['result']='failed';
                    $ret['error']='not start task';
                    $ret['timeout']=0;
                    $ret['percent']=0;
                    $ret['total_images']=0;
                    $ret['optimized_images']=0;
                    $ret['log']='not start task';
                }
            }
        }
        else
        {
            $ret['result']='failed';
            $ret['error']='not start task';
            $ret['timeout']=0;
            $ret['percent']=0;
            $ret['total_images']=0;
            $ret['optimized_images']=0;
            $ret['log']='not start task';
        }


        return $ret;
    }

    public function get_manual_task_progress()
    {
        $this->task=get_option('wpvivid_image_opt_task',array());

        if(empty($this->task))
        {
            $ret['result']='failed';
            $ret['error']='All image(s) optimized successfully.';
            $ret['timeout']=0;
            if(isset($this->task['error']))
                $ret['log']=($this->task['error']);
            else
                $ret['log']='All image(s) optimized successfully.';
            return $ret;
        }

        if(isset($this->task['status']))
        {
            if($this->task['status']=='error')
            {
                $ret['result']='failed';
                $ret['error']=$this->task['error'];
                $ret['timeout']=0;
                $ret['log']=$this->task['error'];
            }
            else if($this->task['status']=='finished')
            {
                $ret['result']='success';
                $ret['continue']=0;
                $ret['finished']=1;
                $ret['timeout']=0;
                $ret['log']='Finish Optimizing images.';
            }
            else if($this->task['status']=='completed')
            {
                $ret['result']='success';
                $ret['continue']=0;
                $ret['finished']=0;
                $ret['timeout']=0;
                $ret['log']='Optimizing images...';
            }
            else
            {
                if(isset($this->task['last_update_time']))
                {
                    if(time()-$this->task['last_update_time']>120)
                    {
                        $this->task['last_update_time']=time();
                        $this->task['retry']++;
                        $this->task['status']='timeout';
                        update_option('wpvivid_image_opt_task',$this->task);
                        if($this->task['retry']<3)
                        {
                            $ret['timeout']=1;
                        }
                        else
                        {
                            $ret['timeout']=0;
                            if($this->log)
                            {
                                $this->log=new WPvivid_Image_Optimize_Log();
                                $this->log->OpenLogFile();
                                $this->log->WriteLog('To many retry attempts. Task timed out.','error');
                                $this->log->Copy_To_Error();
                            }
                            update_option('wpvivid_image_opt_task',array());
                        }

                        $ret['result']='failed';
                        $ret['error']='task timeout';
                        $ret['retry']=$this->task['retry'];
                        $ret['log']='task time out';
                    }
                    else
                    {
                        $ret['continue']=1;
                        $ret['finished']=0;
                        $ret['timeout']=0;
                        $ret['running_time']=time()-$this->task['last_update_time'];
                        $ret['result']='success';
                        $ret['log']='Optimizing images...';
                    }
                }
                else
                {
                    $ret['result']='failed';
                    $ret['error']='not start task';
                    $ret['timeout']=0;
                    $ret['log']='not start task';
                }
            }
        }
        else
        {
            $ret['result']='failed';
            $ret['error']='not start task';
            $ret['timeout']=0;
            $ret['log']='not start task';
        }


        return $ret;
    }

    public function restore_image($image_id)
    {
        $backup_image_meta = get_post_meta( $image_id, 'wpvivid_backup_image_meta', true );

        if(empty($backup_image_meta))
            return false;

        if($backup_image_meta)
        {
            foreach ($backup_image_meta as $meta)
            {
                if(file_exists($meta['backup_path']))
                    @rename($meta['backup_path'],$meta['og_path']);
            }

            do_action('wpvivid_restore_image',$image_id);
            delete_post_meta( $image_id, 'wpvivid_image_optimize_meta');
            delete_post_meta( $image_id, 'wpvivid_backup_image_meta');
            return true;
        }
        else
        {
            return false;
        }
    }

    public function init_image_opt_meta($image_id)
    {
        $image_opt_meta['sum']['og_size']=0;
        $image_opt_meta['sum']['opt_size']=0;
        $image_opt_meta['sum']['options']['mode']='lossless';

        $image_opt_meta['sum']['options']['backup']=$this->is_backup();
        $image_opt_meta['size']=array();

        $image_opt_meta=apply_filters('wpvivid_init_image_opt_meta',$image_opt_meta,$image_id);

        return $image_opt_meta;
    }
}