<?php

if (!defined('WPVIVID_IMGOPTIM_DIR'))
{
    die;
}

class WPvivid_Image_Optimize_Connect_server
{
    private $url='https://wpvivid.com/wc-api/wpvivid_api';
    private $image_optimization_url='http://us1.wpvivid.com/';
    private $api_url='http://api.wpvivid.com/';

    public function __construct()
    {
        if(!class_exists('WPvivid_Image_Optimize_Crypt'))
        {
            include_once WPVIVID_IMGOPTIM_DIR. '/includes/class-wpvivid-imgoptim-crypt.php';
        }
    }

    public function login($user_info,$encrypt_user_info,$get_key=false)
    {
        if($get_key)
            $public_key='';
        else
            $public_key=get_option('wpvivid_connect_key','');

        if(empty($public_key))
        {
            $public_key=$this->get_key();
            if($public_key===false)
            {
                $ret['result']='failed';
                $ret['error']='An error occurred when connecting to WPvivid Backup Pro server. Please try again later or contact us.';
                return $ret;
            }
            update_option('wpvivid_connect_key',$public_key);
        }

        $crypt=new WPvivid_Image_Optimize_Crypt($public_key);

        if($encrypt_user_info)
        {
            $user_info=$crypt->encrypt_user_token($user_info);
            $user_info=base64_encode($user_info);
        }

        $crypt->generate_key();

        $json['user_info']=$user_info;
        $json['domain'] = strtolower(home_url());
        $json=json_encode($json);
        $data=$crypt->encrypt_message($json);

        $action='get_image_optimization_status';
        $url=$this->api_url;
        $url.='?action='.$action;
        $url.='&data='.rawurlencode(base64_encode($data));

        $ret=$this->remote_request($url);

        if($ret['result']=='success')
        {
            if($encrypt_user_info)
            {
                $ret['user_info']=$user_info;
            }
            return $ret;
        }
        else
        {
            return $ret;
        }
    }

    public function get_key()
    {
        $options=array();
        $options['timeout']=30;
        $request=wp_remote_request($this->url.'?request=get_key',$options);

        if(!is_wp_error($request) && ($request['response']['code'] == 200))
        {
            $json= wp_remote_retrieve_body($request);
            $body=json_decode($json,true);
            if(is_null($body))
            {
                return false;
            }

            if($body['result']=='success')
            {
                $public_key=base64_decode($body['public_key']);
                if($public_key==null)
                {
                    return false;
                }
                else
                {
                    return $public_key;
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

    public function get_optimization_url()
    {
        $optimization_url=get_option('wpvivid_get_optimization_url','');
        if(empty($optimization_url))
        {
            $options=array();
            $options['timeout']=30;

            $params = array(
                "action"=>'get_optimization_url',
            );
            $optimization_options=get_option('wpvivid_optimization_options',array());
            $params=apply_filters('wpvivid_get_optimization_url_params',$params,$optimization_options);
            $url=$this->api_url. "?".http_build_query($params);
            $ret=$this->remote_request($url);

            if($ret['result']=='success')
            {
                update_option('wpvivid_get_optimization_url',$ret['url']);
                return $ret;
            }
            else
            {
                return $ret;
            }
        }
        else
        {
            $ret['result']='success';
            $ret['url']=$optimization_url;
            return $ret;
        }
    }

    public function remove_site($user_info)
    {
        $public_key=get_option('wpvivid_connect_key','');
        if(empty($public_key))
        {
            $public_key=$this->get_key();
            if($public_key===false)
            {
                $ret['result']='failed';
                $ret['error']='An error occurred when connecting to WPvivid Backup Pro server. Please try again later or contact us.';
                return $ret;
            }
            update_option('wpvivid_connect_key',$public_key);
        }

        $crypt=new WPvivid_Image_Optimize_Crypt($public_key);

        $crypt->generate_key();

        $json['user_info']=$user_info;
        $json['domain'] = strtolower(home_url());
        $json=json_encode($json);
        $data=$crypt->encrypt_message($json);

        $action='remove_active_site';
        $url=$this->api_url;
        $url.='?action='.$action;
        $url.='&data='.rawurlencode(base64_encode($data));

        $ret=$this->remote_request($url);

        if($ret['result']=='success')
        {
            return $ret;
        }
        else
        {
            return $ret;
        }
    }
    //Image Optimization

    public function get_image_optimization_status($user_info)
    {
        $public_key=get_option('wpvivid_connect_key','');
        if(empty($public_key))
        {
            $public_key=$this->get_key();
            if($public_key===false)
            {
                $ret['result']='failed';
                $ret['error']='An error occurred when connecting to WPvivid Backup Pro server. Please try again later or contact us.';
                return $ret;
            }
            update_option('wpvivid_connect_key',$public_key);
        }

        $crypt=new WPvivid_Image_Optimize_Crypt($public_key);

        $crypt->generate_key();

        $json['user_info']=$user_info;
        $json['domain'] = strtolower(home_url());
        $json=json_encode($json);
        $data=$crypt->encrypt_message($json);

        $action='get_image_optimization_status';
        $url=$this->api_url;
        $url.='?action='.$action;
        $url.='&data='.rawurlencode(base64_encode($data));

        $ret=$this->remote_request($url);

        if($ret['result']=='success')
        {
            return $ret;
        }
        else
        {
            return $ret;
        }
    }

    public function download_file($user_info,$file,$out)
    {
        $public_key=get_option('wpvivid_connect_key','');

        $crypt=new WPvivid_Image_Optimize_Crypt($public_key);

        $crypt->generate_key();

        $json['user_info']=$user_info;
        $json['domain'] = strtolower(home_url());
        $json=json_encode($json);
        $data=$crypt->encrypt_message($json);

        $params = array(
            "data" => rawurlencode(base64_encode($data)),
            'filename'=>basename($file),
            "download" => true,
        );
        $ret=$this->get_optimization_url();
        if($ret['result']=='success')
        {
            $this->image_optimization_url=$ret['url'];
        }
        else
        {
            return $ret;
        }
        $download_url=$this->image_optimization_url. "?".http_build_query($params);

        $filename=download_url($download_url,30);

        if(!is_wp_error($filename))
        {
            if(filesize($filename)==0)
            {
                $ret['result']='failed';
                $ret['error']='File size is 0.';
            }
            else
            {
                rename($filename,$out);
                $ret['result']='success';
                return $ret;
            }
        }
        else
        {
            $ret['result']='failed';
            if ( is_wp_error( $filename ) )
            {
                $error_message = $filename->get_error_message();
                $ret['error']="Sorry, something went wrong: $error_message. Please try again later or contact us.";
            } else {
                $ret['error']=$filename;
            }
            return $ret;
        }
    }

    public function compress_image_without_upload($user_info,$type,$file,$options)
    {
        $public_key=get_option('wpvivid_connect_key','');

        $crypt=new WPvivid_Image_Optimize_Crypt($public_key);

        $json['user_info']=$user_info;
        $json['domain'] = strtolower(home_url());
        $json=json_encode($json);
        $data=$crypt->encrypt_message($json);

        $option['quality']=isset($options['quality'])?$options['quality']:'lossless';
        $option['keep_exif']=isset($options['keep_exif'])?$options['keep_exif']:true;

        $params = array(
            "optimization"=>true,
            "data" => rawurlencode(base64_encode($data)),
            'filename'=>basename($file),
            "type" => $type,
            "option"=>$option
        );

        $params=apply_filters('wpvivid_compress_image_without_upload_params',$params,$options);

        $args['headers']=array('content-type' => 'Content-Type: text/html');
        $args['timeout']=30;
        $ret=$this->get_optimization_url();
        if($ret['result']=='success')
        {
            $this->image_optimization_url=$ret['url'];
        }
        else
        {
            return $ret;
        }
        $ret=$this->remote_request($this->image_optimization_url. "?".http_build_query($params),$args);

        if($ret['result']=='success')
        {
            if(isset($ret['output']))
            {
                $options=$ret['status'];
                $options['time']=time();
                update_option('wpvivid_server_cache',$options);
                return $ret;
            }
            else
            {
                $ret['result']='failed';
                $ret['error']='Optimization failed';
                return $ret;
            }

        }
        else
        {
            return $ret;
        }
    }

    public function upload_small_file($user_info,$type,$in,$out,$options)
    {
        $content=file_get_contents($in);

        if($content===false)
        {
            $ret['result']='failed';
            $ret['error']='file '.$in.' not found';
            return $ret;
        }
        $public_key=get_option('wpvivid_connect_key','');

        $crypt=new WPvivid_Image_Optimize_Crypt($public_key);

        $json['user_info']=$user_info;
        $json['domain'] = strtolower(home_url());
        $json=json_encode($json);
        $data=$crypt->encrypt_message($json);

        $option['quality']=isset($options['quality'])?$options['quality']:'lossless';
        $option['keep_exif']=isset($options['keep_exif'])?$options['keep_exif']:true;
        $params = array(
            "upload_and_optimization"=>true,
            "data" => rawurlencode(base64_encode($data)),
            'filename'=>basename($in),
            "type" => $type,
            "option"=>$option
        );
        $params=apply_filters('wpvivid_upload_small_file_params',$params,$options);
        $args['method']='PUT';
        if($type=='jpg')
        {
            $args['headers']=array('content-type' => 'Content-Type: image/jpeg');
        }
        else if($type=='png')
        {
            $args['headers']=array('content-type' => 'Content-Type: image/png');
        }
        else
        {
            $args['headers']=array('content-type' => 'Content-Type: text/html');
        }

        $args['body']=$content;
        $args['timeout']=30;
        $ret=$this->get_optimization_url();
        if($ret['result']=='success')
        {
            $this->image_optimization_url=$ret['url'];
        }
        else
        {
            return $ret;
        }
        $response=wp_remote_post($this->image_optimization_url. "?".http_build_query($params),$args);

        if(!is_wp_error($response) && ($response['response']['code'] == 200))
        {
            $json= wp_remote_retrieve_body($response);
            $body=json_decode($json,true);

            if(is_null($body))
            {
                $ret['result']='failed';
                $ret['error']=$json;
                return $ret;
            }

            if(!isset($body['result']))
            {
                $ret['result']='failed';
                $ret['error']='empty response';
                return $ret;
            }

            if($body['result']=='success')
            {
                $ret['result']='success';
                $content=base64_decode($body['content']);
                file_put_contents($out,$content);
                $options=$body['status'];
                $options['time']=time();
                update_option('wpvivid_server_cache',$options);
                return $ret;
            }
            else
            {
                return $body;
            }
        }
        else
        {
            $ret['result']='failed';
            if ( is_wp_error( $response ) )
            {
                $error_message = $response->get_error_message();
                $ret['error']="Sorry, something went wrong: $error_message. Please try again later or contact us.";
            } else {
                $ret['error']='code:'.$response['response']['code'].' '.$response['response']['message'];
            }
            return $ret;
        }
    }

    public function upload_loop($user_info,$filename,$offset,$upload_size,$chunk)
    {
        $public_key=get_option('wpvivid_connect_key','');

        $crypt=new WPvivid_Image_Optimize_Crypt($public_key);

        $json['user_info']=$user_info;
        $json['domain'] = strtolower(home_url());
        $json=json_encode($json);
        $data=$crypt->encrypt_message($json);

        $params = array(
            "data" => rawurlencode(base64_encode($data)),
            'filename'=>basename($filename),
            "upload_chunk"=>true,
            "offset"=>$offset,
            "size"=>$upload_size
        );
        $ret=$this->get_optimization_url();
        if($ret['result']=='success')
        {
            $this->image_optimization_url=$ret['url'];
        }
        else
        {
            return $ret;
        }
        $url=$this->image_optimization_url. "?".http_build_query($params);
        $args['method']='PUT';
        $args['headers']=array('content-type' => 'Content-Type: text/html');
        $args['body']=$chunk;
        $args['timeout']=30;

        $response=wp_remote_post($url,$args);

        if(!is_wp_error($response) && ($response['response']['code'] == 200))
        {
            $json= wp_remote_retrieve_body($response);
            $body=json_decode($json,true);

            if(is_null($body))
            {
                $ret['result']='failed';
                $ret['error']=$json;
                return $ret;
            }

            if(!isset($body['result']))
            {
                $ret['result']='failed';
                $ret['error']='empty response';
                return $ret;
            }

            if($body['result']=='success')
            {
                $ret['result']='success';
                return $ret;
            }
            else
            {
                return $body;
            }
        }
        else
        {
            $ret['result']='failed';
            if ( is_wp_error( $response ) )
            {
                $error_message = $response->get_error_message();
                $ret['error']="Sorry, something went wrong: $error_message. Please try again later or contact us.";
            } else {
                $ret['error']=$response['response']['message'];
            }
            return $ret;
        }
    }

    public function delete_exist_file($user_info,$file)
    {
        $public_key=get_option('wpvivid_connect_key','');

        $crypt=new WPvivid_Image_Optimize_Crypt($public_key);

        $json['user_info']=$user_info;
        $json['domain'] = strtolower(home_url());
        $json=json_encode($json);
        $data=$crypt->encrypt_message($json);

        $params = array(
            "data" => rawurlencode(base64_encode($data)),
            'filename'=>basename($file),
            "delete" => true
        );
        $ret=$this->get_optimization_url();
        if($ret['result']=='success')
        {
            $this->image_optimization_url=$ret['url'];
        }
        else
        {
            return $ret;
        }
        $url=$this->image_optimization_url. "?".http_build_query($params);
        $args['headers']=array('content-type' => 'Content-Type: text/html');
        $args['timeout']=30;

        $ret=$this->remote_request($url);

        if($ret['result']=='success')
        {
            return $ret;
        }
        else
        {
            return $ret;
        }
    }

    public function delete_cache($user_info)
    {
        $public_key=get_option('wpvivid_connect_key','');

        $crypt=new WPvivid_Image_Optimize_Crypt($public_key);

        $json['user_info']=$user_info;
        $json['domain'] = strtolower(home_url());
        $json=json_encode($json);
        $data=$crypt->encrypt_message($json);

        $params = array(
            "data" => rawurlencode(base64_encode($data)),
            "delete_cache" => true
        );
        $ret=$this->get_optimization_url();
        if($ret['result']=='success')
        {
            $this->image_optimization_url=$ret['url'];
        }
        else
        {
            return $ret;
        }
        $url=$this->image_optimization_url. "?".http_build_query($params);
        $ret=$this->remote_request($url);

        if($ret['result']=='success')
        {
            return $ret;
        }
        else
        {
            return $ret;
        }
    }

    public function remote_request($url,$body=array())
    {
        $options=array();
        $options['timeout']=30;
        if(empty($options['body']))
        {
            $options['body']=$body;
        }

        $retry=0;
        $max_retry=3;

        $ret['result']='failed';
        $ret['error']='remote request failed';

        while($retry<$max_retry)
        {
            $request=wp_remote_request($url,$options);

            if(!is_wp_error($request) && ($request['response']['code'] == 200))
            {
                $json= wp_remote_retrieve_body($request);
                $body=json_decode($json,true);

                if(is_null($body))
                {
                    $ret['result']='failed';
                    $ret['error']='Decoding json failed. Please try again later.'.'test:'.$json;
                    return $ret;
                }

                if(isset($body['result'])&&$body['result']=='success')
                {
                    return $body;
                }
                else
                {
                    if(isset($body['result'])&&$body['result']=='failed')
                    {
                        $ret['result']='failed';
                        $ret['error']=$body['error'];
                        if(isset($body['error_code']))
                        {
                            $ret['error_code']=$body['error_code'];
                        }
                    }
                    else if(isset($body['error']))
                    {
                        $ret['result']='failed';
                        $ret['error']=$body['error'];
                        if(isset($body['error_code']))
                        {
                            $ret['error_code']=$body['error_code'];
                        }
                    }
                    else
                    {
                        $ret['result']='failed';
                        $ret['error']='login failed';
                        $ret['test']=$body;
                    }
                }
            }
            else
            {
                $ret['result']='failed';
                if ( is_wp_error( $request ) )
                {
                    $error_message = $request->get_error_message();
                    $ret['error']="Sorry, something went wrong: $error_message. Please try again later or contact us.";
                }
                else if($request['response']['code'] != 200)
                {
                    $ret['error']=$request['response']['message'];
                }
                else {
                    $ret['error']=$request;
                }
            }

            $retry++;
        }

        return $ret;
    }

    public function remote_post($url,$body=array(),$timeout=120)
    {
        $options=array();
        $options['timeout']=$timeout;

        if(empty($options['body']))
        {
            $options['body']=$body;
        }

        $retry=0;
        $max_retry=3;

        $ret['result']='failed';
        $ret['error']='remote request failed';

        while($retry<$max_retry)
        {
            $request=wp_remote_post($url,$options);
            if(!is_wp_error($request) && ($request['response']['code'] == 200))
            {
                $json= wp_remote_retrieve_body($request);
                $body=json_decode($json,true);

                if(is_null($body))
                {
                    $ret['result']='failed';
                    $ret['error']=$json;
                }

                if(!isset($body['data']) && isset($body['result']) && $body['result'] == 'failed' && isset($body['error']) && $body['error'] == 'not allowed')
                {
                    $ret['result'] = 'failed';
                    $ret['error'] = 'need_reactive';
                    return $ret;
                }

                if(isset($body['result'])&&$body['result']=='success')
                {
                    return $body;
                }
                else if(isset($body['result'])&&$body['result']=='failed')
                {
                    $ret['result']='failed';
                    $ret['error']=$body['error'];
                }
                else
                {
                    $ret['result']='failed';
                    $ret['error']='empty body';
                }
            }
            else
            {
                $ret['result']='failed';
                if ( is_wp_error( $request ) )
                {
                    $error_message = $request->get_error_message();
                    $ret['error']="Sorry, something went wrong: $error_message. Please try again later or contact us.";
                }
                else if($request['response']['code'] != 200)
                {
                    $ret['error']=$request['response']['message'];
                }
                else {
                    $ret['error']=$request;
                }
            }

            $retry++;
        }

        return $ret;
    }

    public function clear_destination($path)
    {
        require_once( ABSPATH . 'wp-admin/includes/plugin-install.php' );
        require_once( ABSPATH . 'wp-admin/includes/class-wp-upgrader.php' );
        require_once( ABSPATH . 'wp-admin/includes/class-plugin-installer-skin.php' );
        require_once( ABSPATH . 'wp-admin/includes/class-plugin-upgrader.php' );

        WP_Filesystem();
        $skin     = new WP_Ajax_Upgrader_Skin();
        $upgrader = new Plugin_Upgrader( $skin );
        $upgrader->clear_destination($path);

    }

    public function create_user($email)
    {
        $public_key=get_option('wpvivid_connect_key','');

        if (!class_exists('WPvivid_crypt'))
            include_once(WPVIVID_IMGOPTIM_DIR . '/includes/class-wpvivid-imgoptim-crypt.php');
        $crypt=new WPvivid_Image_Optimize_Crypt($public_key);

        $json['email']=$email;
        $json['password'] =wp_generate_password( 12, false );
        $json=json_encode($json);

        $data=$crypt->encrypt_message($json);

        $url='https://wpvivid.com/wc-api/wpvivid_api';
        $url.='?request=create_user';
        $url.='&data='.rawurlencode(base64_encode($data));
        $args['headers']=array('content-type' => 'Content-Type: text/html');
        $args['timeout']=30;

        $ret=$this->remote_request($url);

        if($ret['result']=='success')
        {
            return $ret;
        }
        else
        {
            return $ret;
        }
    }

    public function server_status()
    {
        $ret=$this->get_optimization_url();
        if($ret['result']=='success')
        {
            $this->image_optimization_url=$ret['url'];
        }
        else
        {
            return $ret;
        }
        $url=$this->image_optimization_url;

        $options=array();
        $options['timeout']=30;

        $retry=0;
        $max_retry=3;

        $ret['result']='failed';
        $ret['error']='remote request failed';

        while($retry<$max_retry)
        {
            $time = -microtime(true);
            $request=wp_remote_request($url,$options);

            if(!is_wp_error($request) && ($request['response']['code'] == 200))
            {
                $ret['result']='success';
                unset($ret['error']);
                $end = sprintf('%f', $time += microtime(true));
                break;
            }
            else
            {
                $ret['result']='failed';
                if ( is_wp_error( $request ) )
                {
                    $error_message = $request->get_error_message();
                    $ret['error']="Sorry, something went wrong: $error_message. Please try again later or contact us.";
                }
                else if($request['response']['code'] != 200)
                {
                    $ret['error']=$request['response']['message'];
                }
                else {
                    $ret['error']=$request;
                }
            }

            $retry++;
        }


        if($ret['result']=='success')
        {
            $ret['time']=$end;
            return $ret;
        }
        else
        {
            return $ret;
        }

    }
}