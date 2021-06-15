<?php

if (!defined('WPVIVID_IMGOPTIM_DIR'))
{
    die;
}

class WPvivid_Ngg_Image_Optimize
{
    public function is_image_optimized($post_id)
    {
        $meta=$this->get_ngg_meta($post_id);
        if(!empty($meta)&&isset($meta['size'])&&!empty($meta['size']))
        {
            foreach ($meta['size'] as $size_key => $size_data)
            {
                if(!isset($size_data['opt_status'])||$size_data['opt_status']==0)
                {
                    return false;
                }
            }

            return true;
        }
        else
        {
            return false;
        }

    }

    public function get_need_optimize_images()
    {
        global $wpdb;

        @set_time_limit( 0 );

        $storage   = \C_Gallery_Storage::get_instance();

        $images = array();
        $ngg_table = $wpdb->prefix . 'ngg_pictures';
        $result    = $wpdb->get_results( "SELECT pid,filename FROM $ngg_table", ARRAY_A );

        if(!$result )
        {
            return $images;
        }

        foreach ( $result as $image )
        {
            $id        = absint( $image['pid'] );
            $file_path = $storage->get_image_abspath( $id );

            if ( ! $file_path || ! file_exists($file_path) )
            {
                continue;
            }

            $images[] = $id ;
        }

        return $images;
    }

    public function get_ngg_meta($id)
    {
        global $wpdb;
        $ngg_table = $wpdb->prefix . 'wpvivid_ngg_meta';
        $this->check_table();

        $result = $wpdb->get_results( "SELECT meta FROM $ngg_table WHERE id=$id", ARRAY_A );

        if(!$result )
        {
            return false;
        }

        return maybe_unserialize($result[0]['meta']);
    }

    public function update_ngg_meta($id,$image_opt_meta)
    {
        global $wpdb;
        $ngg_table = $wpdb->prefix . 'wpvivid_ngg_meta';
        $this->check_table();

        $result = $wpdb->get_results("SELECT * FROM $ngg_table WHERE id=$id", OBJECT_K);
        if(empty($result))
        {
            $data['id']=$id;
            $data['meta']=maybe_serialize($image_opt_meta);
            $wpdb->insert($ngg_table,$data);
        }
        else
        {
            $data['token']=$id;
            $where['meta']=maybe_serialize($image_opt_meta);
            $wpdb->update($ngg_table,$data,$where);
        }
    }

    public function check_table()
    {
        global $wpdb;

        $table_name = $wpdb->prefix . "wpvivid_ngg_meta";
        if($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name)
        {
            $sql = "CREATE TABLE $table_name (
                id int,
                meta longtext
                );";
            //reference to upgrade.php file
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            dbDelta( $sql );
        }
    }

    public function get_attached_file($id)
    {
        global $wpdb;

        $storage   = \C_Gallery_Storage::get_instance();

        $ngg_table = $wpdb->prefix . 'ngg_pictures';
        $result    = $wpdb->get_results( "SELECT pid,filename FROM $ngg_table WHERE pid=$id", ARRAY_A );

        if(!$result )
        {
            return false;
        }

        $id        = absint( $result[0]['pid'] );
        $file_path = $storage->get_image_abspath( $id );

        if ( ! $file_path || ! file_exists($file_path) )
        {
            return false;
        }

        return $file_path;
    }

    public function get_attachment_metadata($id)
    {
        $storage   = \C_Gallery_Storage::get_instance();

        $sizes=$storage->get_image_sizes($id);

        $meta['sizes']=array();

        if(empty($sizes))
        {
            return $meta;
        }
        else
        {
            foreach ($sizes as $size)
            {
                if($sizes=='backup')
                    continue;
                $meta['sizes'][$size]['file']=$storage->get_image_abspath($id,$size);
            }
            return $meta;
        }
    }

    public function get_mime_type($filename)
    {
        if (function_exists('exif_imagetype'))
        {
            if (($image_type = @exif_imagetype($filename)) !== FALSE)
            {
                return image_type_to_mime_type($image_type);
            }
        } else {
            $file_info = @getimagesize($filename);
            if (isset($file_info[2]))
            {
                return image_type_to_mime_type($file_info[2]);
            }
        }
        return false;
    }

    public function get_backup_folder($filename)
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

        $upload_root=$this->transfer_path(WP_CONTENT_DIR);
        $attachment_dir=dirname($filename);
        $attachment_dir=$this->transfer_path($attachment_dir);
        $sub_dir=str_replace($upload_root,'',$attachment_dir);
        $sub_dir=trim($sub_dir,'/\\' );
        $path=WP_CONTENT_DIR.DIRECTORY_SEPARATOR.$backup_path.DIRECTORY_SEPARATOR.'backup_image'.DIRECTORY_SEPARATOR.$sub_dir;

        if(!file_exists($path))
        {
            @mkdir($path,0777,true);
        }

        return $path.DIRECTORY_SEPARATOR.basename($filename);
    }

    private function transfer_path($path)
    {
        $path = str_replace('\\','/',$path);
        $values = explode('/',$path);
        return implode(DIRECTORY_SEPARATOR,$values);
    }
}