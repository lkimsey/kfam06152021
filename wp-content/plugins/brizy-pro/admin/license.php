<?php

class BrizyPro_Admin_License
{

    const LICENSE_META_KEY = 'brizy-license-key';

    /**
     * @var Brizy_TwigEngine
     */
    public $twig;

    /**
     * @return BrizyPro_Admin_License
     * @throws Exception
     */
    public static function _init()
    {

        static $instance;

        return $instance ? $instance : $instance = new self();
    }

    public function getCurrentLicense()
    {
        if (is_multisite()) {
            $siteID = get_main_site_id();
            switch_to_blog($siteID);
            Brizy_Editor_Project::cleanClassCache();
        }

        $licenseData = Brizy_Editor_Project::get()->getMetaValue(self::LICENSE_META_KEY);

        if (is_multisite()) {
            restore_current_blog();
            Brizy_Editor_Project::cleanClassCache();
        }

        return $licenseData;
    }

    protected function updateLicense($licenseKey)
    {

        if (is_multisite()) {
            $siteID = get_main_site_id();
            switch_to_blog($siteID);
            Brizy_Editor_Project::cleanClassCache();
        }

        Brizy_Editor_Project::get()->setMetaValue(self::LICENSE_META_KEY, $licenseKey);
        Brizy_Editor_Project::get()->saveStorage();

        if (is_multisite()) {
            restore_current_blog();
            Brizy_Editor_Project::cleanClassCache();
        }
    }

    protected function removeLicense()
    {

        if (is_multisite()) {
            $siteID = get_main_site_id();
            switch_to_blog($siteID);
            Brizy_Editor_Project::cleanClassCache();
        }

        Brizy_Editor_Project::get()->removeMetaValue(self::LICENSE_META_KEY);
        Brizy_Editor_Project::get()->saveStorage();

        if (is_multisite()) {
            restore_current_blog();
            Brizy_Editor_Project::cleanClassCache();
        }
    }

    /**
     * BrizyPro_Admin_License constructor.
     *
     * @param Brizy_Editor_Project $project
     *
     * @throws Exception
     */
    private function __construct()
    {

        if (BrizyPro_Admin_WhiteLabel::_init()->getEnabled() && get_transient(
                                                                    BrizyPro_Admin_WhiteLabel::WL_SESSION_KEY
                                                                ) != 1) {
            return;
        }

        if (isset($_REQUEST['brz-action']) && $_REQUEST['brz-action'] == 'activate' && isset($_REQUEST['code']) && isset($_REQUEST['key'])) {
            add_action('admin_init', array($this, 'handleKeyActivation'), 10);
        }

        if (isset($_REQUEST['brz-action']) && $_REQUEST['brz-action'] == 'deactivate' && isset($_REQUEST['code']) && isset($_REQUEST['key'])) {
            add_action('admin_init', array($this, 'handleKeyDeactivation'), 10);
        }


        add_action('brizy_settings_tabs', [$this, 'addLicenseTab'], 10, 2);
        add_action('brizy_settings_render_tab', [$this, 'renderLicenseTab'], 10, 2);
        add_action('brizy_network_settings_tabs', [$this, 'addLicenseTab'], 10, 2);
        add_action('brizy_network_settings_render_tab', [$this, 'renderLicenseTab'], 10, 2);
        add_action('network_admin_menu', array($this, 'actionRegisterSubMenuLicensePage'), 9);
        add_action('admin_init', array($this, 'handleSubmit'), 10);


        if (is_multisite()) {
            $licenseData = $this->getCurrentLicense();
            if (isset($licenseData['key']) && $this->verifyForPrivateLicense($licenseData['key'])) {
                $this->removeLicense();
            }
        }
    }


    function handleSubmit()
    {
        if (count($_POST) == 0) {
            return;
        }

        if ( ! isset($_REQUEST['tab'])) {
            return;
        }
        if ($_REQUEST['tab'] != 'license') {
            return;
        }

        if ( ! isset($_POST['_wpnonce']) || ! wp_verify_nonce($_POST['_wpnonce'], 'validate-license')) {
            return;
        }

        $validLicense      = true;
        $isPersonalLicense = $this->verifyForPrivateLicense($_POST['key']);

        if (is_multisite()) {
            $validLicense = ! $isPersonalLicense;
        }

        // validate license
        if ($_REQUEST['license_form_action'] == 'activate' && ! $validLicense) {

            if (is_multisite()) {
                Brizy_Admin_Flash::instance()->add_error(
                    esc_html__("Sorry, you can’t use the Brizy Personal license in a multisite network.", 'brizy-pro')
                );
            }

            return;
        }

        $licenseData = $this->getCurrentLicense();
        $data        = BrizyPro_Config::getLicenseActivationData();
        $params      = [
            'market'               => $data['market'],
            'author'               => $data['author'],
            'theme_id'             => $data['theme_id'],
            'brizy-license-submit' => ucfirst($_REQUEST['license_form_action']),
            'key'                  => $_POST['key'],

        ];

        $params['request']  = array('domain' => home_url());
        $params['redirect'] = add_query_arg('brz-action', $licenseData ? 'deactivate' : 'activate', $this->getTabUrl());
        $url                = $_REQUEST['license_form_action'] == 'activate' ? BrizyPro_Config::ACTIVATE_LICENSE : BrizyPro_Config::DEACTIVATE_LICENSE;
        wp_redirect($url.'?'.http_build_query($params));
    }

    /**
     * @internal
     */
    function actionRegisterSubMenuLicensePage()
    {
        add_submenu_page(
            Brizy_Admin_NetworkSettings::menu_slug(),
            __('License', 'brizy'),
            __('License', 'brizy'),
            'manage_network',
            Brizy_Admin_NetworkSettings::menu_slug(),
            array($this, 'render')
        );
    }

    public function renderLicenseTab($content = '', $tab = '')
    {

        if ('license' !== $tab) {
            return $content;
        }

        $licenseData = $this->getCurrentLicense();
        $data        = BrizyPro_Config::getLicenseActivationData();

        $data['request']  = array('domain' => home_url());
        $data['redirect'] = add_query_arg('brz-action', $licenseData ? 'deactivate' : 'activate', $this->getTabUrl());

        if (is_null($licenseData)) {
            $licenseData = [];
        }

        // prepare license
        $key = isset($licenseData['key']) ? $licenseData['key'] : null;
        if ($key) {
            $l = strlen($licenseData['key']);
            $t = str_repeat('*', $l - 6);

            $key = substr($licenseData['key'], 0, 3).$t.substr($licenseData['key'], $l - 3, 3);
        }

        $context = array(
            'nonce'               => wp_nonce_field('validate-license', '_wpnonce', true, false),
            'action'              => $this->getTabUrl(),
            //$licenseData ? BrizyPro_Config::DEACTIVATE_LICENSE : BrizyPro_Config::ACTIVATE_LICENSE,
            'submit_label'        => $licenseData ? esc_html__('Deactivate', 'brizy-pro') : __('Activate', 'brizy-pro'),
            'license_form_action' => $licenseData ? 'deactivate' : 'activate',
            'license'             => $key,
            'licenseFull'         => $key,
            'licensed'            => $licenseData ? true : false,
            'message'             => isset($_REQUEST['message']) ? $_REQUEST['message'] : null,
            'data'                => $data,
        );

        return Brizy_TwigEngine::instance(BRIZY_PRO_PLUGIN_PATH."/admin/views/")->render('license.html.twig', $context);
    }

    public function addLicenseTab($tabs = '', $selected_tab = '')
    {

        if ((is_multisite() && is_network_admin()) || ! is_multisite()) {
            $tabs[] = [
                'id'          => 'license',
                'label'       => __('License', 'brizy-pro'),
                'is_selected' => $selected_tab == 'license',
                'href'        => $this->getTabUrl(),
            ];
        }

        return $tabs;
    }

    public function handleKeyActivation()
    {
        // handle key activation
        if ($_GET['brz-action'] == 'activate') {

            if ($_REQUEST['code'] == 'ok') {
                $data        = BrizyPro_Config::getLicenseActivationData();
                $data['key'] = $_REQUEST['key'];
                $this->updateLicense($data);
                Brizy_Admin_Flash::instance()->add_success(esc_html__($_REQUEST['message'], 'brizy-pro'));
            } else {
                Brizy_Admin_Flash::instance()->add_error(esc_html__($_REQUEST['message'], 'brizy-pro'));
            }

            wp_redirect($this->getTabUrl());
            exit;
        }
    }

    public function handleKeyDeactivation()
    {
        // handle key activation
        if ($_GET['brz-action'] == 'deactivate') {

            if ($_REQUEST['code'] == 'ok' || $_REQUEST['code'] == 'no_activation_found' || $_REQUEST['code'] == 'no_reactivation_allowed' || $_REQUEST['code'] == 'license_not_found') {
                Brizy_Admin_Flash::instance()->add_success(esc_html__($_REQUEST['message'], 'brizy-pro'));
            } else {
                Brizy_Admin_Flash::instance()->add_error(esc_html__($_REQUEST['message'], 'brizy-pro'));
            }

            $this->removeLicense();

            wp_redirect($this->getTabUrl());
            exit;
        }
    }

    private function getTabUrl()
    {

        if (is_multisite()) {
            return network_admin_url('admin.php?page='.Brizy_Admin_NetworkSettings::menu_slug(), false).'&tab=license';
        } else {
            return menu_page_url(
                       is_network_admin() ? Brizy_Admin_NetworkSettings::menu_slug() : Brizy_Admin_Settings::menu_slug(
                       ),
                       false
                   ).'&tab=license';
        }

    }

    private function verifyForPrivateLicense($licenseKey)
    {
        // validate license
        if (isset($licenseKey) && preg_match("/^(BPNW|BZS|BFP)/i", $licenseKey)) {
            return true;
        }

        return false;
    }
}
