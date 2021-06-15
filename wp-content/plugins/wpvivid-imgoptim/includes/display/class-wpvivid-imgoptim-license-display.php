<?php
if (!defined('WPVIVID_IMGOPTIM_DIR'))
{
    die;
}

class WPvivid_ImgOptim_license_Display
{
    public function __construct()
    {
        add_filter('wpvivid_imgoptim_get_admin_menus',array($this,'get_admin_menus'),30);
        add_filter('wpvivid_imgoptim_get_screen_ids',array($this,'get_screen_ids'),15);

        add_action('wp_ajax_wpvivid_imgoptim_login',array( $this,'login'));
        add_action('wp_ajax_wpvivid_imgoptim_check_update',array( $this,'check_update'));
        add_action('wp_ajax_wpvivid_imgoptim_update',array( $this,'update'));
        //
        add_action('wpvivivd_image_optimization_license_box',array( $this,'license_box'));
        //
        add_action('wp_ajax_wpvivid_sign_up',array( $this,'sign_up'));
        //
        add_action('wp_ajax_wpvivid_remove_site',array( $this,'remove_site'));
    }

    public function license_box()
    {
        ?>
        <div class="wpvivid-one-coloum wpvivid-workflow wpvivid-clear-float" style="margin-bottom:1em;">
            <div id="wpvivid_pro_notice"></div>
            <div class="wpvivid-two-col">
                <h2 style="padding-left:0;">My Account</h2>
                <p>Get an API license to optimize images by creating a free account</p>
                <p>
                    <input type="text" id="wpvivid_get_email" class="regular-text" placeholder="Enter your email"/>
                </p>

                <p>
                    <input id="wpvivid_sign_up_btn" type="submit" class="button action top-action" value="Sign up, it's free">
                    <span class="spinner" id="wpvivid_sign_up_progress" style="float: none;margin:0px;display: none"></span>
                    <span> ,or <a href="https://wpvivid.com/my-account">sign up on wpvivid.com</a></span>
                </p>

                <p id="wpvivid_sign_up_msg" style="display: none">An API license has been sent to your email address, please check it.</p>
                <h2 style="padding-left:0;">Enter your API License below:</h2>
                <p>
                    <input id="wpvivid_account_license" type="text" class="regular-text" placeholder="Enter API License"/>
                </p>
                <p></p>
                <div style="margin-bottom: 10px; float: left; margin-left: 0; margin-right: 10px;">
                    <input id="wpvivid_active_btn" type="submit" class="button action top-action" value="Connect to Server"/>
                </div>

                <div class="spinner" id="wpvivid_login_box_progress" style="float: left; margin-left: 0; margin-right: 10px;"></div>
                <div style="float: left; margin-top: 4px;">
                    <span id="wpvivid_log_progress_text"></span>
                </div>
                <div style="clear: both;"></div>
            </div>
            <div class="wpvivid-two-col">
                <div>
                    <h2 style="padding-left:0;">Why need to sign up?</h2>
                    <p style="word-wrap:break-word;">As WPvivid image optimization API does not have a speed limit, to prevent abuse of our server resources and ensure a high-speed and safe optimization process for all users, it requires an authorization to access the API, which is 100% free and with 0 risk.
                    <h2 style="padding-left:0;">What about your personal information?</h2>
                    <p style="word-wrap:break-word;">Our servers do not receive any personal information of your website except for the images that you want to optimize. And we won’t keep any copies of your images on our sever.
                    <h2 style="padding-left:0;">What is the workflow of image optimization?</h2>
                    <p style="word-wrap:break-word;">step 1.Apply for WPvivid sever authentication.</p>
                    <p style="word-wrap:break-word;">step 2.Send images to the server.</p>
                    <p style="word-wrap:break-word;">step 3.The server performs optimization.</p>
                    <p style="word-wrap:break-word;">step 4.Send optimized images to your hosting server.</p>
                    <p style="word-wrap:break-word;">step 5.Delete images copies from WPvivid server.</p>
                </div>
            </div>
        </div>
        <script>
            jQuery('#wpvivid_active_btn').click(function()
            {
                wpvivid_image_opt_login();
            });

            function wpvivid_image_opt_login()
            {
                var license = jQuery('#wpvivid_account_license').val();
                var ajax_data={
                    'action':'wpvivid_imgoptim_login',
                    'license':license,
                };

                var login_msg = '<?php echo sprintf(__('Logging in to your %s account', 'wpvivid'), apply_filters('wpvivid_white_label_display', 'WPvivid')); ?>';
                wpvivid_lock_login(true);
                wpvivid_login_progress(login_msg);
                jQuery('#wpvivid_pro_notice').hide();
                wpvivid_post_request(ajax_data, function(data)
                {
                    var jsonarray = jQuery.parseJSON(data);
                    if (jsonarray.result === 'success')
                    {
                        wpvivid_login_progress('You have successfully logged in');
                        location.reload();
                    }
                    else
                    {
                        wpvivid_lock_login(false,jsonarray.error);
                    }
                }, function(XMLHttpRequest, textStatus, errorThrown)
                {
                    var error_message = wpvivid_output_ajaxerror('Update CDN setting', textStatus, errorThrown);
                    wpvivid_lock_login(false,error_message);
                });
            }

            function wpvivid_lock_login(lock,error='')
            {
                if(lock)
                {
                    jQuery('#wpvivid_active_btn').css({'pointer-events': 'none', 'opacity': '0.4'});
                    jQuery('#wpvivid_login_box_progress').show();
                    jQuery('#wpvivid_login_box_progress').addClass('is-active');
                }
                else
                {
                    jQuery('#wpvivid_log_progress_text').html('');
                    jQuery('#wpvivid_login_box_progress').hide();
                    jQuery('#wpvivid_login_box_progress').removeClass('is-active');
                    jQuery('#wpvivid_active_btn').css({'pointer-events': 'auto', 'opacity': '1'});

                    if(error!=='')
                    {
                        wpvivid_display_pro_notice('Error', error);
                    }
                }
            }

            function wpvivid_login_progress(log)
            {
                jQuery('#wpvivid_log_progress_text').html(log);
            }

            jQuery('#wpvivid_sign_up_btn').click(function()
            {
                wpvivid_sign_up();
            });

            function wpvivid_sign_up()
            {
                var email=jQuery('#wpvivid_get_email').val();
                jQuery('#wpvivid_get_email').prop('disabled', true);
                jQuery('#wpvivid_sign_up_btn').prop('disabled', true);
                jQuery('#wpvivid_pro_notice').hide();
                jQuery('#wpvivid_sign_up_progress').show();
                jQuery('#wpvivid_sign_up_progress').addClass('is-active');
                var ajax_data = {
                    'action': 'wpvivid_sign_up',
                    'email':email
                };
                wpvivid_post_request(ajax_data, function(data)
                {
                    jQuery('#wpvivid_sign_up_progress').hide();
                    jQuery('#wpvivid_sign_up_progress').removeClass('is-active');
                    jQuery('#wpvivid_get_email').prop('disabled', false);
                    jQuery('#wpvivid_sign_up_btn').prop('disabled', false);
                    try
                    {
                        var jsonarray = jQuery.parseJSON(data);
                        if (jsonarray.result === 'success')
                        {
                            jQuery('#wpvivid_sign_up_msg').show();
                        }
                        else if (jsonarray.result === 'failed')
                        {
                            wpvivid_display_pro_notice('Error', jsonarray.error);
                        }
                    }
                    catch(err)
                    {
                        wpvivid_display_pro_notice('Error', jsonarray.error);
                    }

                }, function(XMLHttpRequest, textStatus, errorThrown)
                {
                    var error_message = wpvivid_output_ajaxerror('enter api', textStatus, errorThrown);
                    alert(error_message);
                    jQuery('#wpvivid_sign_up_progress').hide();
                    jQuery('#wpvivid_sign_up_progress').removeClass('is-active');
                    jQuery('#wpvivid_get_email').prop('disabled', false);
                    jQuery('#wpvivid_sign_up_btn').prop('disabled', false);
                });
            }

            function wpvivid_display_pro_notice(notice_type, notice_message)
            {
                if(notice_type === 'Success')
                {
                    var div = "<div class='notice notice-success is-dismissible inline'><p>" + notice_message + "</p>" +
                        "<button type='button' class='notice-dismiss' onclick='click_dismiss_pro_notice(this);'>" +
                        "<span class='screen-reader-text'>Dismiss this notice.</span>" +
                        "</button>" +
                        "</div>";
                }
                else{
                    var div = "<div class=\"notice notice-error inline\"><p>Error: " + notice_message + "</p></div>";
                }
                jQuery('#wpvivid_pro_notice').show();
                jQuery('#wpvivid_pro_notice').html(div);
            }
        </script>
        <?php
    }

    public function sign_up()
    {
        global $wpvivid_imgoptim;
        $wpvivid_imgoptim->ajax_check_security();

        if(isset($_POST['email']))
        {
            $email=sanitize_email($_POST['email']);
            if(empty($email))
            {
                $ret['result']='failed';
                $ret['error']='Invalid email address';
                echo json_encode($ret);
                die();
            }
            else
            {
                include_once WPVIVID_IMGOPTIM_DIR . '/includes/class-wpvivid-imgoptim-connect-server.php';

                $server=new WPvivid_Image_Optimize_Connect_server();

                $ret=$server->create_user($email);

                if($ret['result']=='success')
                {
                    $ret['msg']=__('Your account has been successfully created. Please check your mailbox, you are going to receive an email with API key.','wpvivid-imgoptim');
                }
                else
                {
                    if($ret['error_code']=='existing_user_login')
                    {
                        $ret['error']=__('Sorry, that email address is already used.','wpvivid-imgoptim');
                    }
                    else if($ret['error_code']=='existing_user_email')
                    {
                        $ret['error']=__('Sorry, that email address is already used.','wpvivid-imgoptim');
                    }
                }

                echo json_encode($ret);
            }
        }
        die();
    }

    public function remove_site()
    {
        global $wpvivid_imgoptim;
        $wpvivid_imgoptim->ajax_check_security();

        include_once WPVIVID_IMGOPTIM_DIR . '/includes/class-wpvivid-imgoptim-connect-server.php';

        $server=new WPvivid_Image_Optimize_Connect_server();

        $info= get_option('wpvivid_imgoptim_user',false);
        if($info===false)
        {
            $ret['result']='success';
            echo json_encode($ret);
            die();
        }

        $user_info=$info['token'];

        $ret=$server->remove_site($user_info);

        if($ret['result']=='success')
        {
            delete_option('wpvivid_imgoptim_user');
            delete_option('wpvivid_server_cache');
            $result['result']='success';
        }
        else
        {
            $result=$ret;
        }

        echo json_encode($result);

        die();
    }

    public function get_screen_ids($screen_ids)
    {
        $screen_ids[]='wpvivid-imgoptim_page_wpvivid-imgoptim-license';
        return $screen_ids;
    }

    public function get_admin_menus($submenus)
    {
        $submenu['parent_slug']=WPVIVID_IMGOPTIM_SLUG;
        $submenu['page_title']= 'WPvivid Image Optimize';
        $submenu['menu_title']=__('API License', 'wpvivid-imgoptim');
        $submenu['capability']='administrator';
        $submenu['menu_slug']='wpvivid-imgoptim-license';
        $submenu['index']=5;
        $submenu['function']=array($this, 'display');
        $submenus[$submenu['menu_slug']]=$submenu;
        return $submenus;
    }

    public function display()
    {
        ?>
        <div class="wrap">
            <div id="icon-options-general" class="icon32"></div>
            <h1><?php esc_attr_e( 'WPvivid Imgoptim - API License', 'wpvivid-imgoptim' ); ?></h1>
            <div id="wpvivid_pro_notice">
            </div>
            <div id="poststuff">
                <div id="post-body" class="metabox-holder columns-2">
                    <!-- main content -->
                    <div id="post-body-content">
                        <div class="meta-box-sortables ui-sortable">
                            <div class="wpvivid-backup">
                                <?php $this->welcome_bar();?>
                                <div class="wpvivid-nav-bar wpvivid-clear-float">
                                    <span class="dashicons dashicons-lightbulb wpvivid-dashicons-orange"></span>
                                    <span> <?php _e('You can sign up on wpvivid.com to get an API license(it’s free).', 'wpvivid-imgoptim'); ?></span>
                                </div>
                                <div class="wpvivid-canvas wpvivid-clear-float">
                                    <div class="wpvivid-one-coloum">
                                            <?php
                                            if(get_option('wpvivid_imgoptim_user',false)===false)
                                            {
                                                $this->license_box();
                                            }
                                            else
                                            {
                                                ?>
                                        <div class="wpvivid-one-coloum wpvivid-workflow wpvivid-clear-float">
                                            <?php
                                                $this->status_bar();
                                                $this->user_bar();

                                                ?>
                                        </div>
                                                <script>
                                                    function wpvivid_display_pro_notice(notice_type, notice_message)
                                                    {
                                                        if(notice_type === 'Success')
                                                        {
                                                            var div = "<div class='notice notice-success is-dismissible inline'><p>" + notice_message + "</p>" +
                                                                "<button type='button' class='notice-dismiss' onclick='click_dismiss_pro_notice(this);'>" +
                                                                "<span class='screen-reader-text'>Dismiss this notice.</span>" +
                                                                "</button>" +
                                                                "</div>";
                                                        }
                                                        else{
                                                            var div = "<div class=\"notice notice-error inline\"><p>Error: " + notice_message + "</p></div>";
                                                        }
                                                        jQuery('#wpvivid_pro_notice').show();
                                                        jQuery('#wpvivid_pro_notice').html(div);
                                                    }
                                                </script>
                                                <?php
                                            }
                                            ?>
                                        <div style="clear: both;"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- sidebar -->
                    <?php $this->sidebar(); ?>
                    <!-- #postbox-container-1 .postbox-container -->
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
                <p><span class="dashicons dashicons-admin-network wpvivid-dashicons-large wpvivid-dashicons-green"></span><span class="wpvivid-page-title">API License</span></p>
                <span class="about-description"><?php _e('Enter your API license (it’s free) to activate WPvivid Image Optimization plugin.','wpvivid-imgoptim');?></span>
            </div>
        </div>
        <?php
    }

    public function status_bar()
    {
        $current_version=WPVIVID_IMGOPTIM_VERSION;

        $image_optimization_info=get_option('wpvivid_imgoptim_user',false);

        if($image_optimization_info===false)
        {
            $active_status='Inactive';
        }
        else
        {
            $active_status='Active';
        }

        ?>
        <div class="wpvivid-two-col">
            <p>
                <span class="dashicons dashicons-awards wpvivid-dashicons-blue"></span>
                <span><?php _e('Current Version','wpvivid-imgoptim');?>: </span><span><?php echo esc_html($current_version); ?></span>
            </p>
            <p>
                <span class="dashicons dashicons-yes-alt wpvivid-dashicons-blue"></span>
                <span><?php _e('Status','wpvivid-imgoptim');?>: </span>
                <span><?php echo esc_html($active_status); ?></span>
            </p>
        </div>
        <?php
    }

    public function user_bar()
    {
        $user_info= get_option('wpvivid_imgoptim_user',false);
        ?>
        <div class="wpvivid-two-col" style="padding-right:1em;">
            <?php $this->sign_out_bar();?>
            <?php
            if($user_info===false)
            {
                $this->login_form();
            }
            else
            {
                $this->logged();
            }
            ?>
        </div>
        <?php
    }

    public function sign_out_bar()
    {
        if(isset($_REQUEST['sign_out']))
        {
            delete_option('wpvivid_imgoptim_user');
            $url='admin.php?page=wpvivid-imgoptim-license';
            ?>
            <script>
                location.href='<?php echo esc_url($url);?>';
            </script>
            <?php
        }
        $white_label_website_protocol='https';
        $white_label_website='wpvivid.com/my-account/license';
        $signout_url='admin.php?page=wpvivid-imgoptim-license&sign_out=1';
        ?>
        <span class="dashicons dashicons-businessman wpvivid-dashicons-green"></span>
        <span><a href="<?php echo esc_html($white_label_website_protocol); ?>://<?php echo esc_html($white_label_website); ?>" target="_blank"><?php _e('My Account','wpvivid-imgoptim');?></a></span>
        <script>
            jQuery('#wpvivid_dashboard_signout').click(function()
            {
                var descript = 'Are you sure you want to sign out?';
                var ret = confirm(descript);
                if(ret === true)
                {
                    location.href='<?php echo esc_url($signout_url);?>';
                }
            });
        </script>
        <?php
    }

    public function sidebar()
    {
        global $wpvivid_imgoptim;
        $wpvivid_imgoptim->sidebar();
    }

    public function login_form()
    {
        ?>
        <form action="">
            <div style="margin-top: 10px; margin-bottom: 15px;">
                <input type="password" class="regular-text" id="wpvivid_account_license" placeholder="Enter a license" autocomplete="new-password" required="">
            </div>
            <div style="margin-bottom: 10px; float: left; margin-left: 0; margin-right: 10px;">
                <input class="button-primary" id="wpvivid_active_btn" type="button" value="Activate">
            </div>
            <div class="spinner" id="wpvivid_login_box_progress" style="float: left; margin-left: 0; margin-right: 10px;"></div>
            <div style="float: left; margin-top: 4px;">
                <span id="wpvivid_log_progress_text"></span>
            </div>
            <div style="clear: both;"></div>
        </form>
        <script>
            jQuery('#wpvivid_active_btn').click(function()
            {
                wpvivid_image_opt_login();
            });

            function wpvivid_image_opt_login()
            {
                var license = jQuery('#wpvivid_account_license').val();
                var ajax_data={
                    'action':'wpvivid_imgoptim_login',
                    'license':license,
                };

                var login_msg = '<?php echo __('Logging in to your Wpvivid account', 'wpvivid-imgoptim'); ?>';
                wpvivid_lock_login(true);
                wpvivid_login_progress(login_msg);
                jQuery('#wpvivid_pro_notice').hide();
                wpvivid_post_request(ajax_data, function(data)
                {
                    var jsonarray = jQuery.parseJSON(data);
                    if (jsonarray.result === 'success')
                    {
                        //need_active
                        wpvivid_login_progress('You have successfully logged in');
                        location.reload();
                    }
                    else
                    {
                        wpvivid_lock_login(false,jsonarray.error);
                    }
                }, function(XMLHttpRequest, textStatus, errorThrown)
                {
                });
            }

            function wpvivid_lock_login(lock,error='')
            {
                if(lock)
                {
                    jQuery('#wpvivid_active_btn').css({'pointer-events': 'none', 'opacity': '0.4'});
                    jQuery('#wpvivid_login_box_progress').show();
                    jQuery('#wpvivid_login_box_progress').addClass('is-active');
                }
                else
                {
                    jQuery('#wpvivid_log_progress_text').html('');
                    jQuery('#wpvivid_login_box_progress').hide();
                    jQuery('#wpvivid_login_box_progress').removeClass('is-active');
                    jQuery('#wpvivid_active_btn').css({'pointer-events': 'auto', 'opacity': '1'});

                    if(error!=='')
                    {
                        wpvivid_display_pro_notice('Error', error);
                    }
                }
            }

            function wpvivid_login_progress(log)
            {
                jQuery('#wpvivid_log_progress_text').html(log);
            }
        </script>
        <?php
    }

    public function logged()
    {
        ?>
        <form action="">
            <div style="margin-top: 10px; margin-bottom: 15px;">
                <div>
                    <input class="button-primary" id="wpvivid_remove_site_btn" style="float: left;" type="button" value="Remove License">
                    <div class="spinner" id="wpvivid_login_box_progress" style="float: left; margin-left: 10px; margin-right: 10px;"></div>
                </div>
            </div>
            <div style="float: left; margin-top: 4px;">
                <span id="wpvivid_log_progress_text"></span>
            </div>
            <div style="clear: both;"></div>
        </form>
        <script>
            jQuery('#wpvivid_remove_site_btn').click(function()
            {
                wpvivid_remove_site();
            });

            function wpvivid_remove_site()
            {
                var ajax_data={
                    'action':'wpvivid_remove_site',
                };

                wpvivid_lock_login(true);
                jQuery('#wpvivid_pro_notice').hide();
                wpvivid_post_request(ajax_data, function(data)
                {
                    var jsonarray = jQuery.parseJSON(data);
                    if (jsonarray.result === 'success')
                    {
                        wpvivid_login_progress('You have successfully sign out');
                        location.reload();
                    }
                    else
                    {
                        wpvivid_lock_login(false,jsonarray.error);
                    }
                }, function(XMLHttpRequest, textStatus, errorThrown)
                {
                    var error_message = wpvivid_output_ajaxerror('Update CDN setting', textStatus, errorThrown);
                    wpvivid_lock_login(false,error_message);
                });
            }

            function wpvivid_lock_login(lock,error='')
            {
                if(lock)
                {
                    jQuery('#wpvivid_active_btn').css({'pointer-events': 'none', 'opacity': '0.4'});
                    jQuery('#wpvivid_login_box_progress').show();
                    jQuery('#wpvivid_login_box_progress').addClass('is-active');
                }
                else
                {
                    jQuery('#wpvivid_log_progress_text').html('');
                    jQuery('#wpvivid_login_box_progress').hide();
                    jQuery('#wpvivid_login_box_progress').removeClass('is-active');
                    jQuery('#wpvivid_active_btn').css({'pointer-events': 'auto', 'opacity': '1'});

                    if(error!=='')
                    {
                        wpvivid_display_pro_notice('Error', error);
                    }
                }
            }

            function wpvivid_login_progress(log)
            {
                jQuery('#wpvivid_log_progress_text').html(log);
            }
        </script>
        <?php
    }

    public function login()
    {
        global $wpvivid_imgoptim;
        $wpvivid_imgoptim->ajax_check_security();

        try
        {
            if(isset($_POST['license']))
            {
                if(empty($_POST['license']))
                {
                    $ret['result']='failed';
                    $ret['error']='A license is required.';
                    echo json_encode($ret);
                    die();
                }

                $license=sanitize_text_field($_POST['license']);
            }
            else
            {
                $ret['result']='failed';
                $ret['error']='Retrieving user information failed. Please try again later.';
                echo json_encode($ret);
                die();
            }

            include_once WPVIVID_IMGOPTIM_DIR . '/includes/class-wpvivid-imgoptim-connect-server.php';

            $server=new WPvivid_Image_Optimize_Connect_server();

            $ret=$server->login($license,true);
            if($ret['result']=='success')
            {
                $info['token']=$ret['user_info'];
                update_option('wpvivid_imgoptim_user',$info);

                $options=$ret['status'];
                $options['time']=time();
                update_option('wpvivid_server_cache',$options);
                $result['result']='success';
            }
            else
            {
                $result=$ret;
            }

            echo json_encode($result);
        }
        catch (Exception $e)
        {
            $ret['result']='failed';
            $ret['error']= $e->getMessage();
            echo json_encode($ret);
        }

        die();
    }
}