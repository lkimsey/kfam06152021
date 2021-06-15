<?php

class BrizyPro_Content_Placeholders_Excerpt extends Brizy_Content_Placeholders_Simple
{

    /**
     * Brizy_Editor_Content_GenericPlaceHolder constructor.
     *
     * @param string $label
     * @param string $placeholder
     */
    public function __construct($label, $placeholder)
    {
        parent::__construct(
            $label,
            $placeholder,
            function (Brizy_Content_Context $context) {
                return $this->get_the_excerpt($context);
            }
        );
    }

    /**
     * It rewrites the function from wodpress core get_the_excerpt that applies the hook get_the_excerpt.
     * The hook get_the_excerpt has a handle wp_trim_excerpt that applies the hook the_content.
     * Applying the hook the_content will run an infinite loop because of some function like
     * Brizy_Admin_Templates->filterPageContent() which are also hanging at this hook.
     *
     * @param Brizy_Content_Context $context
     *
     * @return false|mixed|string
     */
    public function get_the_excerpt(Brizy_Content_Context $context)
    {
	    $post = $context->getWpPost();

	    if (Brizy_Editor_Entity::isBrizyEnabled($post->ID) && $post->post_excerpt==='') {
	        $post = Brizy_Editor_Entity::get($post->ID);
	        return $this->trim_exceerpt($post->get_compiled_page()->getPageContent(), $post);
	    }

	    if (doing_filter('get_the_excerpt') || doing_filter('brizy_dc_post_excerpt')) {
		    return '';
	    }

	    if (post_password_required($post)) {
		    return __('There is no excerpt because this is a protected post.', 'brizy-pro');
	    }

	    $applyFilters = apply_filters('brizy_dc_excerpt', apply_filters('get_the_excerpt', $post->post_excerpt, $post));

	    return $applyFilters;
    }

    public function trim_exceerpt($text, $post)
    {
        $raw_excerpt = $text;

        $text = strip_shortcodes($text);
        $text = Brizy_Content_PlaceholderExtractor::stripPlaceholders($text);
        $text = excerpt_remove_blocks($text);

        if(isset($_REQUEST['test'])) {
		    echo $text;
		    exit;
	    }

        /** This filter is documented in wp-includes/post-template.php */
        $text = str_replace(']]>', ']]&gt;', $text);

        /* translators: Maximum number of words used in a post excerpt. */
        $excerpt_length = intval(_x('55', 'excerpt_length'));

        /**
         * Filters the maximum number of words in a post excerpt.
         *
         * @param int $number The maximum number of words. Default 55.
         *
         * @since 2.7.0
         *
         */
        $excerpt_length = (int)apply_filters('excerpt_length', $excerpt_length);

        /**
         * Filters the string in the "more" link displayed after a trimmed excerpt.
         *
         * @param string $more_string The string shown within the more link.
         *
         * @since 2.9.0
         *
         */
        $excerpt_more = apply_filters('excerpt_more', ' '.'[&hellip;]');
        $text         = wp_trim_words($text, $excerpt_length, $excerpt_more);

        /**
         * Filters the trimmed excerpt string.
         *
         * @param string $text The trimmed text.
         * @param string $raw_excerpt The text prior to trimming.
         *
         * @since 2.8.0
         *
         */
        return apply_filters('wp_trim_excerpt', $text, $raw_excerpt);
    }

}
