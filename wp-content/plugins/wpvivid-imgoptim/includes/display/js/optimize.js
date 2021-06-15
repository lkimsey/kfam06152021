
window.wpvivid = window.wpvivid || {};

(function($, w, undefined)
{
    w.wpvivid.media={
        progress_queue:[],
        lock:false,
        init:function()
        {
            $( document ).on( 'click', '.wpvivid-media-item a.wpvivid-media', this.optimize_image );
            $( document ).on( 'click', '.wpvivid-media-item a.wpvivid-media-restore', this.restore_image);
            $( document ).on( 'click', '.misc-pub-wpvivid a.wpvivid-media-restore', this.restore_image_edit);
            $( document ).on( 'click', '.misc-pub-wpvivid a.wpvivid-media', this.optimize_image_edit);
            $( document ).on( 'click', '.wpvivid-media-attachment a.wpvivid-media', this.optimize_image_attachment);
            $( document ).on( 'click', '.wpvivid-media-attachment a.wpvivid-media-restore', this.restore_image_attachment);
            $( document ).on( 'click', '.thumbnail', this.get_attachment_progress);
            w.wpvivid.media.get_progress();
        },
        optimize_image:function ()
        {
            if(w.wpvivid.media.islockbtn())
            {
                return ;
            }
            var id=$( this ).data( 'id' );
            $( this ).html("Optimizing...");
            $( this ).removeClass('wpvivid-media');
            w.wpvivid.media.lockbtn(true);

            var ajax_data = {
                'action': 'wpvivid_opt_single_image',
                'id':id
            };
            wpvivid_post_request(ajax_data, function(data)
            {
                w.wpvivid.media.get_progress();

            }, function(XMLHttpRequest, textStatus, errorThrown)
            {
                w.wpvivid.media.get_progress();
            });
        },
        optimize_image_edit:function()
        {
            if(w.wpvivid.media.islockbtn())
            {
                return ;
            }
            var id=$( this ).data( 'id' );
            $( this ).html("Optimizing...");
            $( this ).removeClass('wpvivid-media');
            w.wpvivid.media.lockbtn(true);

            var ajax_data = {
                'action': 'wpvivid_opt_single_image',
                'id':id
            };
            wpvivid_post_request(ajax_data, function(data)
            {
                w.wpvivid.media.get_progress('edit');

            }, function(XMLHttpRequest, textStatus, errorThrown)
            {
                w.wpvivid.media.get_progress('edit');
            });
        },
        optimize_image_attachment:function()
        {
            if(w.wpvivid.media.islockbtn())
            {
                return ;
            }
            var id=$( this ).data( 'id' );
            $( this ).html("Optimizing...");
            $( this ).removeClass('wpvivid-media');
            w.wpvivid.media.lockbtn(true);

            var ajax_data = {
                'action': 'wpvivid_opt_single_image',
                'id':id
            };

            wpvivid_post_request(ajax_data, function(data)
            {
                w.wpvivid.media.get_progress('attachment');

            }, function(XMLHttpRequest, textStatus, errorThrown)
            {
                w.wpvivid.media.get_progress('attachment');
            });
        },
        optimize_timeout_image:function (page='media')
        {
            var ajax_data = {
                'action': 'wpvivid_opt_image',
            };
            wpvivid_post_request(ajax_data, function(data)
            {
                setTimeout(function ()
                {
                    w.wpvivid.media.get_progress(page);
                }, 1000);

            }, function(XMLHttpRequest, textStatus, errorThrown)
            {
                setTimeout(function ()
                {
                    w.wpvivid.media.get_progress(page);
                }, 1000);
            });
        },
        get_progress:function(page='media')
        {
            var ids=[];
            if(page=='media')
            {
                var media=$('.wpvivid-media-item');
                if ( media.length>0 )
                {
                    media.each( function()
                    {
                        ids.push( $( this ).data( 'id' ) );
                    } );
                }
            }
            else if(page=='attachment')
            {
                var id=$('.wpvivid-media-attachment').data( 'id' );
                ids.push(id );
            }
            else
            {
                var id=$('.misc-pub-wpvivid').data( 'id' );
                ids.push(id );
            }

            if(ids.length<1)
            {
                return;
            }
            var ids_json=JSON.stringify(ids);
            var ajax_data = {
                'action': 'wpvivid_get_opt_single_image_progress',
                ids:ids_json,
                page:page
            };

            wpvivid_post_request(ajax_data, function(data)
            {
                try
                {
                    var jsonarray = jQuery.parseJSON(data);
                    w.wpvivid.media.update(jsonarray,page);
                    if (jsonarray.result === 'success')
                    {
                        if(jsonarray.continue)
                        {
                            setTimeout(function ()
                            {
                                w.wpvivid.media.get_progress(page);
                            }, 1000);
                        }
                        else if(jsonarray.finished)
                        {
                            w.wpvivid.media.lockbtn(false);
                        }
                        else
                        {
                            w.wpvivid.media.optimize_timeout_image(page);
                        }

                    }
                    else
                    {
                        if(jsonarray.timeout)
                        {
                            w.wpvivid.media.optimize_timeout_image(page);
                        }
                        else
                        {
                            w.wpvivid.media.lockbtn(false);
                        }
                    }
                }
                catch(err)
                {
                    alert(err);
                    w.wpvivid.media.lockbtn(false);
                }

            }, function(XMLHttpRequest, textStatus, errorThrown)
            {
                w.wpvivid.media.lockbtn(false);
                var error_message = wpvivid_output_ajaxerror('get progress', textStatus, errorThrown);
                alert(error_message);
            });
        },
        update:function (jsonarray,page='media')
        {
            if(page=='edit')
            {
                var id=$('.misc-pub-wpvivid').data( 'id' );
                if(jsonarray.hasOwnProperty(id))
                {
                    $( '.misc-pub-wpvivid' ).html(jsonarray[id]['html']);
                }
            }
            else if(page=='attachment')
            {
                var media=$('.wpvivid-media-attachment');
                if ( media.length>0 )
                {
                    media.each( function()
                    {
                        var id=$( this ).data( 'id' );
                        if(jsonarray.hasOwnProperty(id))
                        {
                            $( this ).html(jsonarray[id]['html']);
                        }
                    } );
                }
            }
            else
            {
                var media=$('.wpvivid-media-item');
                if ( media.length>0 )
                {
                    media.each( function()
                    {
                        var id=$( this ).data( 'id' );
                        if(jsonarray.hasOwnProperty(id))
                        {
                            $( this ).html(jsonarray[id]['html']);
                        }
                    } );
                }
            }
        },
        lockbtn:function (status)
        {
            w.wpvivid.media.lock=status;
        },
        islockbtn:function ()
        {
            return w.wpvivid.media.lock;
        },
        restore_image:function()
        {
            if(w.wpvivid.media.islockbtn())
            {
                return ;
            }
            w.wpvivid.media.lockbtn(true);
            var id=$( this ).data( 'id' );

            $( this ).addClass("button-disabled");

            var ajax_data = {
                'action': 'wpvivid_restore_single_image',
                'id':id
            };
            wpvivid_post_request(ajax_data, function(data)
            {
                w.wpvivid.media.lockbtn(false);
                var jsonarray = jQuery.parseJSON(data);
                w.wpvivid.media.update(jsonarray);

            }, function(XMLHttpRequest, textStatus, errorThrown)
            {
                w.wpvivid.media.lockbtn(false);
                var error_message = wpvivid_output_ajaxerror('get progress', textStatus, errorThrown);
                alert(error_message);
            });
        },
        restore_image_edit:function ()
        {
            if(w.wpvivid.media.islockbtn())
            {
                return ;
            }
            w.wpvivid.media.lockbtn(true);
            var id=$( this ).data( 'id' );

            $( this ).addClass("button-disabled");

            var ajax_data = {
                'action': 'wpvivid_restore_single_image',
                'id':id,
                'page':'edit'
            };
            wpvivid_post_request(ajax_data, function(data)
            {
                w.wpvivid.media.lockbtn(false);
                var jsonarray = jQuery.parseJSON(data);
                w.wpvivid.media.update(jsonarray,'edit');

            }, function(XMLHttpRequest, textStatus, errorThrown)
            {
                w.wpvivid.media.lockbtn(false);
                var error_message = wpvivid_output_ajaxerror('get progress', textStatus, errorThrown);
                alert(error_message);
            });
        },
        restore_image_attachment:function ()
        {
            if(w.wpvivid.media.islockbtn())
            {
                return ;
            }
            w.wpvivid.media.lockbtn(true);
            var id=$( this ).data( 'id' );

            $( this ).addClass("button-disabled");

            var ajax_data = {
                'action': 'wpvivid_restore_single_image',
                'id':id,
                'page':'attachment'
            };

            wpvivid_post_request(ajax_data, function(data)
            {
                w.wpvivid.media.lockbtn(false);
                var jsonarray = jQuery.parseJSON(data);
                w.wpvivid.media.update(jsonarray,'attachment');

            }, function(XMLHttpRequest, textStatus, errorThrown)
            {
                w.wpvivid.media.lockbtn(false);
                var error_message = wpvivid_output_ajaxerror('get progress', textStatus, errorThrown);
                alert(error_message);
            });
        },
        get_attachment_progress:function ()
        {
            $(this).find('.wpvivid-media-attachment').each(function()
            {
                var id=$(this).data( 'id' );
                alert(id);
            });

        }
    };
    w.wpvivid.media.init();
})(jQuery, window);