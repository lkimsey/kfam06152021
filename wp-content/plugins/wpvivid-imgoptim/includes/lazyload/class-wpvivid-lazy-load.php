<?php

if (!defined('WPVIVID_IMGOPTIM_DIR'))
{
    die;
}

class WPvivid_Lazy_Load
{
    function __construct()
    {
        $options=get_option('wpvivid_optimization_options',array());
        $enable=isset($options['lazyload']['enable'])?$options['lazyload']['enable']:false;

        if($enable)
        {
            add_action( 'wp_head', array( $this, 'lazyload_styles' ) );
            add_action('wp_enqueue_scripts', array($this, 'enqueue_lazyload'));
            add_action('template_redirect', array($this, 'replace_content'), 1);
            if ( ! isset( $options['lazyload']['content'] ) || !$options['lazyload']['content'] )
            {
                add_filter( 'the_content', array( $this, 'skip_lazyload' ), 1000 );
            }
            if ( ! isset( $options['lazyload']['thumbnails'] ) || !$options['lazyload']['thumbnails'] )
            {
                add_filter( 'post_thumbnail_html', array( $this, 'skip_lazyload' ), 1000 );
            }
        }
    }

    public function replace_content()
    {
        ob_start( array( $this, 'replace_img_tag' ) );
    }

    public function lazyload_styles()
    {
        $options=get_option('wpvivid_optimization_options',array());

        $options['lazyload']=isset($options['lazyload'])?$options['lazyload']:array();

        $options['lazyload']['animation']=isset($options['lazyload']['animation'])?$options['lazyload']['animation']:'fadein';

        if($options['lazyload']['animation']=='fadein')
        {
            ?>
            <style>
                img.lazy {
                    opacity: 0;
                }
                img:not(.initial) {
                    transition: opacity 1s;
                }
                img.initial,
                img.loaded,
                img.error {
                    opacity: 1;
                }

                img:not([src]) {
                    visibility: hidden;
                }
            </style>
            <?php
        }

    }

    public function enqueue_lazyload()
    {
        $options=get_option('wpvivid_optimization_options',array());

        if(isset($options['lazyload'])&&$options['lazyload']['enable'])
        {

            $options=get_option('wpvivid_optimization_options',array());

            $js = isset($options['lazyload']['js'] ) ? $options['lazyload']['js'] : 'footer';

            if($js=='footer')
            {
                $footer=true;
            }
            else
            {
                $footer=false;
            }

            wp_enqueue_script(WPVIVID_IMGOPTIM_SLUG.'_lazy_load', WPVIVID_IMGOPTIM_URL . '/includes/lazyload/lazyload.js', array('jquery'), WPVIVID_IMGOPTIM_VERSION,$footer);
            wp_enqueue_script( WPVIVID_IMGOPTIM_SLUG.'_lazy_load_init',WPVIVID_IMGOPTIM_URL . '/includes/lazyload/lazyload-init.js', array( 'jquery' ), WPVIVID_IMGOPTIM_VERSION,$footer);
        }
    }

    public function skip_lazyload($content)
    {
        $images = $this->get_images( $content );

        if ( empty( $images ) )
        {
            return $content;
        }

        foreach ( $images as $image )
        {
            $new_image = $image['tag'];

            $class = $this->get_attribute( $new_image, 'class' );
            if ( $class )
            {
                $this->remove_attribute( $new_image, 'class' );
                $class .= ' skip-lazy';
            } else {
                $class = 'skip-lazy';
            }
            $this->add_attribute( $new_image, 'class', $class );

            $content = str_replace( $image['tag'], $new_image, $content );
        }

        return $content;
    }

    public function replace_img_tag($content)
    {
        $images = $this->get_images( $content );

        if ( empty( $images ) )
        {
            return $content;
        }

        foreach ( $images as $image )
        {
            $tag     = $this->parse_image( $image );
            $content = str_replace( $image['tag'], $tag, $content );
        }
        return $content;
    }

    public function get_images( $content )
    {
        if ( preg_match( '/(?=<body).*<\/body>/is', $content, $body ) )
        {
            $content = $body[0];
        }

        $content = preg_replace( '/<!--(.*)-->/Uis', '', $content );

        $content = preg_replace('#<noscript(.*?)>(.*?)</noscript>#is', '', $content);

        if ( preg_match_all( '/<(?P<type>img|source|iframe)\b(?>\s+(?:src=[\'"](?P<src>[^\'"]*)[\'"]|srcset=[\'"](?P<srcset>[^\'"]*)[\'"])|[^\s>]+|\s+)*>/is', $content, $matches ) )
        {
            foreach ( $matches as $key => $unused )
            {
                if ( is_numeric( $key ) && $key > 0 )
                {
                    unset( $matches[ $key ] );
                }
            }
        }

        $images = array_map(array($this, 'process_image'), $matches[0],$matches['type'] );
        $images = array_filter( $images );

        if ( ! $images || ! is_array( $images ) )
        {
            return array();
        }

        return $images;
    }

    public function process_image($image,$type)
    {
        $attributes=$this->get_attributes($image,$type);

        $src=$this->get_src($attributes);
        $srcset=$this->get_srcset($attributes);
        if ( empty($src)&&empty($srcset) )
        {
            return false;
        }

        $data=array(
            'tag'              => $image,
            'attributes'       => $attributes,
            'src'              =>  $src,
            'srcset'           => $srcset,
            'type'             =>$type,
        );


        return $data;
    }

    public function get_attributes($image,$type='img')
    {
        if (function_exists("mb_convert_encoding"))
        {
            $image = mb_convert_encoding($image, 'HTML-ENTITIES', 'UTF-8');
        }

        if (class_exists('DOMDocument'))
        {
            $dom = new \DOMDocument();
            @$dom->loadHTML($image);
            $image = $dom->getElementsByTagName($type)->item(0);
            $attributes = array();

            /* This can happen with mismatches, or extremely malformed HTML.
            In customer case, a javascript that did  for (i<imgDefer) --- </script> */
            if (! is_object($image))
                return false;

            foreach ($image->attributes as $attr)
            {
                $attributes[$attr->nodeName] = $attr->nodeValue;
            }
            return $attributes;
        }
        else
        {
            $atts_pattern = '/(?<name>[^\s"\']+)\s*=\s*(["\'])\s*(?<value>.*?)\s*\2/s';

            if ( ! preg_match_all( $atts_pattern, $image, $tmp_attributes, PREG_SET_ORDER ) )
            {
                return false;
            }

            $attributes = array();

            foreach ( $tmp_attributes as $attribute )
            {
                $attributes[ $attribute['name'] ] = $attribute['value'];
            }

            return $attributes;
        }

    }

    public function get_src($attributes)
    {
        $src_source = false;
        $data_tag=array( 'data-lazy-src','data-src', 'src');

        foreach ( $data_tag as $src_attr )
        {
            if ( ! empty( $attributes[ $src_attr ] ) )
            {
                $src_source = $src_attr;
                break;
            }
        }

        if ( ! $src_source )
        {
            // No src attribute.
            return false;
        }

        $options=get_option('wpvivid_optimization_options',array());

        if(isset($options['lazyload']['extensions']))
        {
            $extensions=array();
            $extensions['webp']=1;
            foreach ($options['lazyload']['extensions'] as $key=>$enable)
            {
                if($enable)
                {
                    $extensions[$key]=1;
                }
            }
        }
        else
        {
            $extensions = array(
                'jpg|jpeg|jpe' => 1,
                'png'          => 1,
                'gif'          => 1,
                'webp'         => 1,
            );
        }

        /*
        $extensions = array(
            'jpg|jpeg|jpe' => 'image/jpeg',
            'png'          => 'image/png',
            'gif'          => 'image/gif',
            'webp'         =>'image/webp',
        );
        */
        $extensions = array_keys( $extensions );
        $extensions = implode( '|', $extensions );

        if ( ! preg_match( '@^(?<src>(?:(?:https?:)?//|/).+\.(?<extension>' . $extensions . '))(?<query>\?.*)?$@i', $attributes[ $src_source ], $src ) )
        {
            // Not a supported image format.
            return false;
        }

        $ret['src']=$attributes[ $src_source ];
        $ret['src_attr']=$src_source;

        return $ret;
    }

    public function get_srcset($attributes)
    {
        $srcset_source = false;

        $data_tag=array('data-lazy-srcset', 'data-srcset', 'srcset');

        /*
        $extensions = array(
            'jpg|jpeg|jpe' => 'image/jpeg',
            'png'          => 'image/png',
            'gif'          => 'image/gif',
            'webp'         =>'image/webp',
        );
        */
        $options=get_option('wpvivid_optimization_options',array());

        if(isset($options['lazyload']['extensions']))
        {
            $extensions=array();
            $extensions['webp']=1;
            foreach ($options['lazyload']['extensions'] as $key=>$enable)
            {
                if($enable)
                {
                    $extensions[$key]=1;
                }
            }
        }
        else
        {
            $extensions = array(
                'jpg|jpeg|jpe' => 1,
                'png'          => 1,
                'gif'          => 1,
                'webp'         => 1,
            );
        }

        $extensions = array_keys( $extensions );
        $extensions = implode( '|', $extensions );

        foreach ( $data_tag as $srcset_attr )
        {
            if ( ! empty( $attributes[ $srcset_attr ] ) )
            {
                $srcset_source = $srcset_attr;
                break;
            }
        }

        $ret['srcset_attr']=$srcset_source;
        $ret['srcs']=array();
        if ( $srcset_source )
        {
            $srcset = explode( ',', $attributes[ $srcset_source ] );

            foreach ( $srcset as $srcs )
            {
                $srcs = preg_split( '/\s+/', trim( $srcs ) );

                if ( count( $srcs ) > 2 )
                {
                    $descriptor = array_pop( $srcs );
                    $srcs       = array(implode( ' ', $srcs ), $descriptor);
                }

                if ( empty( $srcs[1] ) )
                {
                    $srcs[1] = '1x';
                }

                if ( ! preg_match( '@^(?<src>(?:https?:)?//.+\.(?<extension>' . $extensions . '))(?<query>\?.*)?$@i', $srcs[0], $src ) )
                {
                    continue;
                }

                $tmp_srcset=array(
                    'url'         => $srcs[0],
                    'descriptor'  => $srcs[1],
                );

                $ret['srcs'][]=$tmp_srcset;
            }
        }
        else
        {
            return false;
        }

        if(empty($ret['srcs']))
            return false;

        return $ret;
    }

    public function parse_image($image)
    {
        $src = $image['src']['src'];

        if($src!=false)
        {
            $is_gravatar = false !== strpos( $src, 'gravatar.com' );

            $ext = strtolower( pathinfo( $src, PATHINFO_EXTENSION ) );
            $ext = 'jpg' === $ext ? 'jpeg' : $ext;

            if ( ! $is_gravatar && ! in_array( $ext, array( 'jpeg', 'gif', 'png', 'svg', 'webp' ), true ) )
            {
                return $image['tag'];
            }
        }

        $new_image = $image['tag'];

        if ( $this->has_class($new_image, 'skip-lazy' ) )
        {
            return $new_image;
        }

        $attributes = array( 'src', 'sizes' );

        foreach ( $attributes as $attribute )
        {
            $attr = $this->get_attribute( $new_image, $attribute );
            if ( $attr )
            {
                $this->remove_attribute( $new_image, $attribute );
                $this->add_attribute( $new_image, "data-{$attribute}", $attr );
            }
        }

        $new_image = preg_replace( '/<(.*?)(srcset=)(.*?)>/i', '<$1data-$2$3>', $new_image );

        if($image['type']=='source')
        {

        }
        else
        {
            $class = $this->get_attribute( $new_image, 'class' );

            $image['class']=$class;
            if ( $class )
            {
                $class .= ' lazy';
            } else {
                $class = 'lazy';
            }
            $this->remove_attribute( $new_image, 'class' );
            $this->add_attribute( $new_image, 'class' ,$class);

            $options=get_option('wpvivid_optimization_options',array());
            $animation=isset($options['lazyload']['animation'])?$options['lazyload']['animation']:'fadein';
            $placeholder='';

            $this->add_attribute( $new_image, 'src', $placeholder);
        }

        $options=get_option('wpvivid_optimization_options',array());

        $noscript=isset($options['lazyload']['noscript'])?$options['lazyload']['noscript']:true;

        if(!$noscript)
        {
            $new_image .= '<noscript>' . $image['tag'] . '</noscript>';
        }
        return $new_image;
    }

    public function get_attribute( $element, $name )
    {
        preg_match( "/{$name}=['\"]([^'\"]+)['\"]/is", $element, $value );
        return isset( $value['1'] ) ? $value['1'] : '';
    }

    public function add_attribute( &$element, $name, $value = null ) {
        $closing = false === strpos( $element, '/>' ) ? '>' : ' />';
        if ( ! is_null( $value ) ) {
            $element = rtrim( $element, $closing ) . " {$name}=\"{$value}\"{$closing}";
        } else {
            $element = rtrim( $element, $closing ) . " {$name}{$closing}";
        }
    }

    public function remove_attribute( &$element, $attribute ) {
        $element = preg_replace( '/' . $attribute . '=[\'"](.*?)[\'"]/i', '', $element );
    }

    public function has_class($content,$needle)
    {
        $classes =$this->get_attribute( $content, 'class' );
        $classes = explode( ' ', $classes );

        foreach ( $classes as $class )
        {
            if($class==$needle)
            {
                return true;
            }
        }

        return false;
    }
}