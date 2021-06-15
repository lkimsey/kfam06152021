<?php

class BrizyPro_Main
{

    public function run()
    {

        $this->registerCustomPosts();

        if (is_admin()) {
            add_action('wp_loaded', array($this, 'wordpressLoaded'), 11); // we need to run this after free plugin
            // Recompile everyone to see the new items in the menu.
            add_filter('wp_update_nav_menu', array($this, 'wp_update_nav_menu'));
        }

        add_filter('brizy_editor_config_texts', array($this, 'filterBrizyEditorConfigTexts'));
        add_filter('brizy_editor_page_context', array($this, 'filterEditorPageContext'));
        add_filter('brizy_editor_config', array($this, 'filterBrizyEditorConfig'));
        add_filter('brizy_editor_config', array($this, 'addConfigDynamicContent'));

        add_action('brizy_preview_enqueue_scripts', array($this, 'actionBrizyPreviewEnqueueScripts'));

        add_filter('brizy_compiler_params', array($this, 'filterBrizyCompilerParams'));
        add_filter('brizy_providers', array($this, 'brizy_placeholders'), 10, 2);
        add_filter('brizy_context_create', array($this, 'createDynamicContentContext'), 10, 2);
        add_filter('brizy_loop_context_create', array($this, 'createDynamicContentLoopContext'), 10, 2);
        add_action('init', array($this, 'wordpressInit'));
        add_action('init', array($this, 'resetPermalinks'), -2000);
        add_action('wp_loaded', array( $this, 'flushRewriteRules' ) );
		add_filter( 'rewrite_rules_array', array( $this, 'addPostLoopPaginationRewriteRules' ), 1000 );
		add_filter( 'parse_query', array( $this, 'parseQuery' ) );
		add_filter( 'redirect_canonical', array( $this, 'templateRedirect' ), 10, 2 );
		add_action( 'template_redirect', [ $this, 'actionTemplateRedirect' ], 9 );
	}

    public function registerCustomPosts()
    {
        // register custom posts here
    }

    public function resetPermalinks()
    {

        $this->registerCustomPosts();

        if (defined('BRIZY_VERSION')) {
            Brizy_Editor::get()->registerCustomPostTemplates();
        }


        if (get_option('brizypro-regenerate-permalinks', false)) {
            flush_rewrite_rules();
            delete_option('brizypro-regenerate-permalinks');
        }
    }

    public function wordpressInit()
    {
        try {
            new BrizyPro_Admin_Forms_Proxy();
            new BrizyPro_Forms_ApiExtender();

            BrizyPro_Admin_Settings::_init();
            BrizyPro_Admin_Integrations::_init();
            BrizyPro_Admin_WhiteLabel::_init();
            BrizyPro_Admin_License::_init();

			if ( BrizyPro_Admin_License::_init()->getCurrentLicense() ) {
                add_action( 'brizy_editor_enqueue_scripts', [ $this, 'actionBrizyEditorEnqueueScripts' ] );
			}

        } catch (Exception $e) {}
    }

	/*
	 * If the current post uses our editor or it has a template of ours then we overwrite the wp option "Break comments into pages with ..."
	 * located in Dashboard -> Settings -> Discussion
	 * We do it because if it is disabled we can't create comments pagination in the comments placeholder.
	 */
	public function actionTemplateRedirect() {
		$pid = Brizy_Editor::get()->currentPostId();

		try {
			$use_editor = Brizy_Editor_Entity::isBrizyEnabled($pid);
		} catch ( Exception $e ) {
			$use_editor = false;
		}

		if ( ! Brizy_Admin_Templates::getTemplate() && ! $use_editor ) {
			return;
		}

		add_action( 'option_page_comments', '__return_true' );

		return;
	}

    public function wordpressLoaded()
    {
        BrizyPro_Admin_Updater::_init(BrizyPro_Config::UDPATE_LICENSE, 'brizy-pro/brizy-pro.php');
    }

    public function brizy_placeholders($providers)
    {

        if (class_exists('acf')) {
            $providers[] = new BrizyPro_Content_Providers_Acf();
        }

        if (function_exists('pods')) {
            $providers[] = new BrizyPro_Content_Providers_Pods();
        }

        if (class_exists('Types_Main')) {
            $providers[] = new BrizyPro_Content_Providers_Toolset();
        }

        if (class_exists('RWMB_Loader')) {
            $providers[] = new BrizyPro_Content_Providers_Metabox();
        }

        if ( class_exists( 'WooCommerce' ) ) {
			$providers[] = new BrizyPro_Content_Providers_Woocommerce(  );
		}

		$providers[] = new BrizyPro_Content_Providers_Wp(  );

        return $providers;
    }


    /**
     * @param Brizy_Content_Context $context
     * @param WP_Post|WP_Term|WP_User|null $entity
     *
     * @return Brizy_Content_Context
     */
    public function createDynamicContentContext(Brizy_Content_Context $context, $entity = null)
    {

        $object = ['object_type' => '', 'object_id' => ''];

        if ($entity instanceof WP_Post) {
            $object = array('object_type' => 'post', 'object_id' => $entity->ID);
        }

        if (wp_doing_ajax()) {

            if ($entity instanceof WP_Term) {
                $object = [
                    'object_type' => 'tax',
                    'object_id'   => $entity->term_id,
                    'tax'         => $entity->taxonomy,
                ];
            }

            if ($entity instanceof WP_User) {
                $object = [
                    'object_type' => 'user',
                    'object_id'   => $entity->ID,
                ];
            }
        }

        if (is_author()) {
            $authorId = get_queried_object_id();
            $context->setAuthor($authorId);
            $object = ['object_type' => 'user', 'object_id' => $authorId];
        } elseif (is_tax() || is_category() || is_tag()) {
            $context->setTerm(get_queried_object()->term_id);
            $object = [
                'object_type' => 'tax',
                'object_id'   => get_queried_object()->term_id,
                'tax'         => get_queried_object()->taxonomy,
            ];
        }

        $context->setObjectData($object);

        return $context;
    }


    /**
     * @param Brizy_Content_Context $context
     * @param WP_Post|null $wp_post
     *
     * @return Brizy_Content_Context
     */
    public function createDynamicContentLoopContext(Brizy_Content_Context $context, WP_Post $wp_post = null)
    {

        if ( class_exists( 'WooCommerce' ) ) {
			global $product;
			$aProduct = $product instanceof WC_Product ? $product : wc_get_product( $wp_post );
			$context->setProduct( $aProduct );
		}

		$context->setObjectData( array( 'object_type' => 'post', 'object_id' => $wp_post->ID ) );

        return $context;
    }

    public function addPostLoopPaginationRewriteRules($allrules)
    {

        $brizyPaged = '/'.BrizyPro_Content_Placeholders_PostLoop::getPaginationKey().'/?([0-9]{1,})/?';
        $newRules   = array();

        foreach ($allrules as $regex => $url) {

            // 1. add brizyPaged reqest in $regex
            // 2. find the 'matches' count in $url
            // 2. add BrizyPro_Content_Placeholders_PostLoop::getPaginationKey() match in $url

            $regex            = str_replace('/?$', $brizyPaged, $regex);
            $count            = preg_match_all('/\((?!\?:)/i', $regex) ;
            $url              .= "&".BrizyPro_Content_Placeholders_PostLoop::getPaginationKey()."=\$matches[{$count}]";
            $url              = preg_replace('/paged=\$matches\[\d+\]/i', "paged=1", $url);
            $newRules[$regex] = $url;
        }


        $newRules['^'.BrizyPro_Content_Placeholders_PostLoop::getPaginationKey().'/?([0-9]{1,})/?'] =
            'index.php?'.BrizyPro_Content_Placeholders_PostLoop::getPaginationKey().'=$matches[1]&pagename=';

        return array_merge($newRules, $allrules);
    }

    public function templateRedirect($redirect_url, $requested_url)
    {

        // Post Paging
        if (is_singular() && $page = get_query_var('bpage')) {

            $redirect = @parse_url($requested_url);
            if (false === $redirect) {
                return $redirect_url;
            }

            if ( ! $redirect_url) {
                $redirect_url = get_permalink(get_queried_object_id());
            }

            if ($page > 1) {
                if (is_front_page()) {
                    return trailingslashit($redirect_url).user_trailingslashit("bpage/$page", 'paged');
                }
            }
        }

        return $redirect_url;
    }

    public function parseQuery($wp_query)
    {

        if ( ! isset($wp_query->query['bpage'])) {
            return;
        }

        // Correct is_* for page_on_front and page_for_posts
        if ($wp_query->is_home && 'page' == get_option('show_on_front') && get_option('page_on_front')) {
            $_query = wp_parse_args($wp_query->query);
            // pagename can be set and empty depending on matched rewrite rules. Ignore an empty pagename.
            if (isset($_query['pagename']) && '' == $_query['pagename']) {
                unset($_query['pagename']);
            }

            unset($_query['embed']);

            if (empty($_query) || ! array_diff(
                    array_keys($_query),
                    array(
                        'preview',
                        'page',
                        'paged',
                        'cpage',
                        'bpage',
                    )
                )) {
                $wp_query->is_page               = true;
                $wp_query->is_home               = false;
                $wp_query->query_vars['page_id'] = (int)get_option('page_on_front');

                // Correct <!--nextpage--> for page_on_front
                if ( ! empty($wp_query->query_vars['paged'])) {
                    $wp_query->query_vars['page'] = $wp_query->query_vars['paged'];
                    unset($wp_query->query_vars['paged']);
                }
            }
        }

        if ($wp_query->query_vars['page_id']) {

            if ('page' == get_option('show_on_front') && $wp_query->query_vars['page_id'] == get_option(
                    'page_for_posts'
                )) {
                $this->is_page       = false;
                $this->is_home       = true;
                $this->is_posts_page = true;
            }

            if ($wp_query->query_vars['page_id'] == get_option('wp_page_for_privacy_policy')) {
                $this->is_privacy_policy = true;
            }
        }

        $wp_query->is_singular = $wp_query->is_single || $wp_query->is_page || $wp_query->is_attachment;
    }

    public function flushRewriteRules()
    {

        add_rewrite_tag('%'.BrizyPro_Content_Placeholders_PostLoop::getPaginationKey().'%', '([^&]+)');

        $get_option = get_option('brizy-pro-rewrite-rules-updated', false);

        if ($get_option) {
            return;
        }

        flush_rewrite_rules();

        add_option('brizy-pro-rewrite-rules-updated', 1);

        return;
    }

    /**
     * @param $texts
     *
     * @return array
     */
    public function filterBrizyEditorConfigTexts($texts)
    {

        $texts['Dynamic content'] = __('Dynamic content', 'brizy-pro');

        return $texts;
    }

    public function filterEditorPageContext($context)
    {

        $context['styles'][] = BrizyPro_Config::getEditorBuildUrl()."/css/editor.pro.css";

        return $context;
    }

    /**
     * @param $config
     *
     * @return mixed
     * @throws Exception
     */
    public function filterBrizyEditorConfig($config)
    {

        $accessType = 'admin';
        if (current_user_can(Brizy_Admin_Capabilities::CAP_EDIT_WHOLE_PAGE)) {
            $accessType = 'admin';
        } elseif (current_user_can(Brizy_Admin_Capabilities::CAP_EDIT_CONTENT_ONLY)) {
            $accessType = 'limited';
        }

        $config['user']['role']                         = $accessType;
        $prefix                                         = Brizy_Editor::prefix();
        $config['wp']['api']['getServiceAccounts']      = $prefix.BrizyPro_Forms_ApiExtender::AJAX_GET_SERVICE_ACCOUNTS;
        $config['wp']['api']['deleteServiceAccount']    = $prefix.BrizyPro_Forms_ApiExtender::AJAX_DELETE_SERVICE_ACCOUNT;
        $config['wp']['api']['authenticateIntegration'] = $prefix.BrizyPro_Forms_ApiExtender::AJAX_AUTHENTICATE_INTEGRATION;
        $config['wp']['api']['createIntegrationGroup']  = $prefix.BrizyPro_Forms_ApiExtender::AJAX_CREATE_GROUP;
        $config['wp']['api']['getAccountProperties']    = $prefix.BrizyPro_Forms_ApiExtender::AJAX_GET_ACCOUNT_PROPERTIES;

        //$config['wp']['api']['getIntegrationLists']     = BrizyPro_Forms_ApiExtender::AJAX_GET_LISTS;
        //$config['wp']['api']['getIntegrationFields']    = BrizyPro_Forms_ApiExtender::AJAX_GET_FIELDS;
        //$config['wp']['api']['createIntegrationFields'] = BrizyPro_Forms_ApiExtender::AJAX_CREATE_FIELDS;

        return $config;
    }

    public function addConfigDynamicContent($config)
    {
    	$postId = (int)$config['wp']['page'];
    	$postType = get_post_type($postId);
    	$usesEditor = Brizy_Editor_Entity::isBrizyEnabled($postId);
        $post                     = Brizy_Editor_Post::get($config['wp']['page']);
        $context                  = new Brizy_Content_Context(
            Brizy_Editor_Project::get(),
            $post,
            $post->getWpPost(),
            null
        );
        $provider                 = new Brizy_Content_PlaceholderProvider($context);
        $config['dynamicContent'] = $provider->getGroupedPlaceholders();
        $config['taxonomies']     = $this->getTaxonomyList();
        $config['postTypesTaxs']  = $this->getPostTypesTaxs();

        if ($usesEditor && Brizy_Admin_Templates::CP_TEMPLATE !== $postType) {

            if (isset($config['dynamicContent']['richText']['wp'])) {

                foreach ($config['dynamicContent']['richText']['wp'] as $index => $placeholder) {

                    if (is_a($placeholder, 'BrizyPro_Content_Placeholders_PostContent')) {
                        array_splice($config['dynamicContent']['richText']['wp'], $index, 1);
                        break;
                    }
                }
            }
        }

        if (BrizyPro_Admin_License::_init()->getCurrentLicense()) {
            $config['pro'] = array(
                'version'    => BRIZY_PRO_EDITOR_VERSION,
                'urls'       => BrizyPro_Config::getConfigUrls(),
                'whiteLabel' => BrizyPro_Admin_WhiteLabel::_init()->getEnabled(),
            );
        }

        return $config;
    }

    /**
     * @internal
     */
    public function actionBrizyEditorEnqueueScripts()
    {
        wp_enqueue_style(
            'brizy-pro-editor',
            BrizyPro_Config::getEditorBuildUrl().'/css/editor.pro.css',
            array('brizy-editor'),
            null
        );
        wp_enqueue_script(
            'brizy-pro-editor',
            BrizyPro_Config::getEditorBuildUrl().'/js/editor.pro.js',
            array('brizy-editor'),
            null,
            true
        );
    }

    /**
     * @internal
     */
    public function actionBrizyPreviewEnqueueScripts()
    {
        wp_enqueue_style(
            'brizy-pro-preview',
            BrizyPro_Config::getEditorBuildUrl().'/css/preview.pro.css',
            array('brizy-preview'),
            null
        );
        wp_enqueue_script(
            'brizy-pro-preview',
            BrizyPro_Config::getEditorBuildUrl().'/js/preview.pro.js',
            array('brizy-preview'),
            null,
            true
        );
    }

    /**
     * @internal
     */
    public function filterBrizyCompilerParams($params)
    {
        $params['has_pro']     = true;
        $params['pro_version'] = BRIZY_PRO_EDITOR_VERSION;
        $params["pro_url"]     = BrizyPro_Config::getCompilerDownloadUrl();

        return $params;
    }

    /**
     * @return array
     */
    private function getTaxonomyList()
    {

        $taxs = get_taxonomies(array('public' => true, 'show_ui' => true), 'objects');

        $result = array_map(
            function ($tax) {

                $terms = (array)get_terms(array('taxonomy' => $tax->name, 'hide_empty' => false));

                return (object)array(
                    'name'  => $tax->name,
                    'label' => $tax->labels->name,
                    'terms' => array_map(
                        function ($term) {
                            return (object)array(
                                'id'   => $term->term_id,
                                'name' => $term->name,
                            );
                        },
                        $terms
                    ),
                );

            },
            $taxs
        );

        $taxonomies = array_values(
            array_filter(
                $result,
                function ($term) {
                    return count($term->terms) > 0;
                }
            )
        );

        return $taxonomies;
    }

    private function getPostTypesTaxs()
    {

        $taxonomies = get_taxonomies(['public' => true, 'show_ui' => true], 'objects');
        $post_types = wp_list_pluck(get_post_types(['show_in_nav_menus' => true], 'objects'), 'label', 'name');
        $out        = [];

        foreach ($post_types as $post_type => $label) {

            foreach ($taxonomies as $tax => $tax_data) {
                if ( ! in_array($post_type, $tax_data->object_type)) {
                    continue;
                }

                if (isset($out[$post_type])) {
                    $out[$post_type]->taxonomies[] = (object)[
                        'id'   => $tax_data->name,
                        'name' => $tax_data->label,
                    ];
                } else {
                    $out[$post_type] = (object)[
                        'name'       => $post_type,
                        'label'      => $label,
                        'taxonomies' => [
                            (object)[
                                'id'   => $tax_data->name,
                                'name' => $tax_data->label,
                            ],
                        ],
                    ];
                }
            }
        }

        return array_values($out);
    }

    /**
     *  Recompile everyone to see the new items in the menu.
     */
    public function wp_update_nav_menu()
    {
        do_action('brizy_global_data_updated');
    }
}
