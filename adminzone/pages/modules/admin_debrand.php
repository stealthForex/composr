<?php /*

 Composr
 Copyright (c) ocProducts, 2004-2016

 See text/EN/licence.txt for full licencing information.


 NOTE TO PROGRAMMERS:
   Do not edit this file. If you need to make changes, save your changed file to the appropriate *_custom folder
   **** If you ignore this advice, then your website upgrades (e.g. for bug fixes) will likely kill your changes ****

*/

/**
 * @license    http://opensource.org/licenses/cpal_1.0 Common Public Attribution License
 * @copyright  ocProducts Ltd
 * @package    debrand
 */

/**
 * Module page class.
 */
class Module_admin_debrand
{
    /**
     * Find details of the module.
     *
     * @return ?array Map of module info (null: module is disabled).
     */
    public function info()
    {
        $info = array();
        $info['author'] = 'Chris Graham';
        $info['organisation'] = 'ocProducts';
        $info['hacked_by'] = null;
        $info['hack_version'] = null;
        $info['version'] = 2;
        $info['locked'] = false;
        return $info;
    }

    /**
     * Find entry-points available within this module.
     *
     * @param  boolean $check_perms Whether to check permissions.
     * @param  ?MEMBER $member_id The member to check permissions as (null: current user).
     * @param  boolean $support_crosslinks Whether to allow cross links to other modules (identifiable via a full-page-link rather than a screen-name).
     * @param  boolean $be_deferential Whether to avoid any entry-point (or even return null to disable the page in the Sitemap) if we know another module, or page_group, is going to link to that entry-point. Note that "!" and "browse" entry points are automatically merged with container page nodes (likely called by page-groupings) as appropriate.
     * @return ?array A map of entry points (screen-name=>language-code/string or screen-name=>[language-code/string, icon-theme-image]) (null: disabled).
     */
    public function get_entry_points($check_perms = true, $member_id = null, $support_crosslinks = true, $be_deferential = false)
    {
        return array(
            'browse' => array('SUPER_DEBRAND', 'menu/adminzone/style/debrand'),
        );
    }

    public $title;

    /**
     * Module pre-run function. Allows us to know metadata for <head> before we start streaming output.
     *
     * @return ?Tempcode Tempcode indicating some kind of exceptional output (null: none).
     */
    public function pre_run()
    {
        appengine_live_guard();

        $type = get_param_string('type', 'browse');

        require_lang('debrand');

        set_helper_panel_text(comcode_lang_string('DOC_SUPERDEBRAND'));

        $this->title = get_screen_title('SUPER_DEBRAND');

        return null;
    }

    /**
     * Execute the module.
     *
     * @return Tempcode The result of execution.
     */
    public function run()
    {
        if (!is_null($GLOBALS['CURRENT_SHARE_USER'])) {
            warn_exit(do_lang_tempcode('SHARED_INSTALL_PROHIBIT'));
        }

        require_lang('config');

        $type = get_param_string('type', 'browse');
        if ($type == 'browse') {
            return $this->browse();
        }
        if ($type == 'actual') {
            return $this->actual();
        }

        return new Tempcode();
    }

    /**
     * The UI for managing super debranding.
     *
     * @return Tempcode The UI
     */
    public function browse()
    {
        require_code('form_templates');

        $rebrand_name = get_value('rebrand_name');
        if (is_null($rebrand_name)) {
            $rebrand_name = 'Composr';
        }
        $rebrand_base_url = get_brand_base_url();
        $company_name = get_value('company_name');
        if (is_null($company_name)) {
            $company_name = 'ocProducts';
        }
        $keyboard_map = file_exists(get_file_base() . '/pages/comcode/' . get_site_default_lang() . '/keymap.txt') ? file_get_contents(get_file_base() . '/pages/comcode/' . get_site_default_lang() . '/keymap.txt') : file_get_contents(get_file_base() . '/pages/comcode/' . fallback_lang() . '/keymap.txt');
        if (file_exists(get_file_base() . '/pages/comcode_custom/' . get_site_default_lang() . '/keymap.txt')) {
            $keyboard_map = file_get_contents(get_file_base() . '/pages/comcode_custom/' . get_site_default_lang() . '/keymap.txt');
        }
        if (file_exists(get_file_base() . '/adminzone/pages/comcode_custom/' . get_site_default_lang() . '/website.txt')) {
            $adminguide = file_get_contents(get_file_base() . '/adminzone/pages/comcode_custom/' . get_site_default_lang() . '/website.txt');
        } else {
            $adminguide = do_lang('ADMINGUIDE_DEFAULT_TRAINING');
        }
        if (file_exists(get_file_base() . '/adminzone/pages/comcode_custom/' . get_site_default_lang() . '/start.txt')) {
            $start_page = file_get_contents(get_file_base() . '/adminzone/pages/comcode_custom/' . get_site_default_lang() . '/start.txt');
        } elseif (file_exists(get_file_base() . '/adminzone/pages/comcode/' . get_site_default_lang() . '/start.txt')) {
            $start_page = file_exists(get_file_base() . '/adminzone/pages/comcode/' . get_site_default_lang() . '/start.txt') ? file_get_contents(get_file_base() . '/adminzone/pages/comcode/' . get_site_default_lang() . '/start.txt') : file_get_contents(get_file_base() . '/adminzone/pages/comcode/' . fallback_lang() . '/start.txt');
        } else {
            $start_page = do_lang('REBRAND_DASHBOARD');
        }

        $fields = new Tempcode();
        $fields->attach(form_input_line(do_lang_tempcode('REBRAND_NAME'), do_lang_tempcode('DESCRIPTION_REBRAND_NAME'), 'rebrand_name', $rebrand_name, true));
        $fields->attach(form_input_line(do_lang_tempcode('REBRAND_BASE_URL'), do_lang_tempcode('DESCRIPTION_BRAND_BASE_URL', escape_html('docs' . strval(cms_version()))), 'rebrand_base_url', $rebrand_base_url, true));
        $fields->attach(form_input_line(do_lang_tempcode('COMPANY_NAME'), '', 'company_name', $company_name, true));
        $fields->attach(form_input_text_comcode(do_lang_tempcode('ADMINGUIDE'), do_lang_tempcode('DESCRIPTION_ADMINGUIDE'), 'adminguide', $adminguide, true));
        $fields->attach(form_input_text_comcode(do_lang_tempcode('ADMINSTART_PAGE'), do_lang_tempcode('DESCRIPTION_ADMINSTART_PAGE'), 'start_page', $start_page, true));
        $fields->attach(form_input_text_comcode(do_lang_tempcode('KEYBOARD_MAP'), '', 'keyboard_map', $keyboard_map, true));
        $fields->attach(form_input_tick(do_lang_tempcode('DELETE_UN_PC'), do_lang_tempcode('DESCRIPTION_DELETE_UN_PC'), 'churchy', false));
        $fields->attach(form_input_tick(do_lang_tempcode('SHOW_DOCS'), do_lang_tempcode('DESCRIPTION_SHOW_DOCS'), 'show_docs', get_option('show_docs') == '1'));
        $fields->attach(form_input_upload(do_lang_tempcode('FAVICON'), do_lang_tempcode('DESCRIPTION_FAVICON'), 'favicon', false, find_theme_image('favicon'), null, true, str_replace(' ', '', get_option('valid_images'))));
        $fields->attach(form_input_upload(do_lang_tempcode('WEBCLIPICON'), do_lang_tempcode('DESCRIPTION_WEBCLIPICON'), 'webclipicon', false, find_theme_image('webclipicon'), null, true, str_replace(' ', '', get_option('valid_images'))));
        if (addon_installed('cns_avatars')) {
            $fields->attach(form_input_upload(do_lang_tempcode('SYSTEM_AVATAR'), do_lang_tempcode('DESCRIPTION_SYSTEM_AVATAR'), 'system_avatar', false, find_theme_image('cns_default_avatars/system'), null, true, str_replace(' ', '', get_option('valid_images'))));
        }

        $post_url = build_url(array('page' => '_SELF', 'type' => 'actual'), '_SELF');
        $submit_name = do_lang_tempcode('PROCEED');

        return do_template('FORM_SCREEN', array('_GUID' => 'fd47f191ac51f7754eb17e3233f53bcc', 'HIDDEN' => '', 'TITLE' => $this->title, 'URL' => $post_url, 'FIELDS' => $fields, 'TEXT' => do_lang_tempcode('WARNING_SUPER_DEBRAND_MAJOR_CHANGES'), 'SUBMIT_ICON' => 'buttons__proceed', 'SUBMIT_NAME' => $submit_name));
    }

    /**
     * The actualiser for super debranding.
     *
     * @return Tempcode The UI
     */
    public function actual()
    {
        require_code('config2');

        if (is_null($GLOBALS['CURRENT_SHARE_USER'])) { // Only if not a shared install
            require_code('abstract_file_manager');
            force_have_afm_details();
        }

        set_value('rebrand_name', post_param_string('rebrand_name'));
        set_value('rebrand_base_url', post_param_string('rebrand_base_url'));
        set_value('company_name', post_param_string('company_name'));
        set_option('show_docs', post_param_string('show_docs', '0'));

        require_code('database_action');

        foreach (array(get_file_base() . '/pages/comcode_custom/' . get_site_default_lang(), get_file_base() . '/adminzone/pages/comcode_custom/' . get_site_default_lang()) as $dir) {
            if (!file_exists($dir)) {
                require_code('files2');
                make_missing_directory($dir);
            }
        }

        $keyboard_map_path = get_file_base() . '/pages/comcode_custom/' . get_site_default_lang() . '/keymap.txt';
        if (!file_exists(dirname($keyboard_map_path))) {
            require_code('files2');
            make_missing_directory(dirname($keyboard_map_path));
        }
        $myfile = @fopen($keyboard_map_path, 'wb');
        if ($myfile === false) {
            intelligent_write_error($keyboard_map_path);
        }
        $km = post_param_string('keyboard_map');
        if (fwrite($myfile, $km) < strlen($km)) {
            warn_exit(do_lang_tempcode('COULD_NOT_SAVE_FILE'));
        }
        fclose($myfile);
        fix_permissions($keyboard_map_path);
        sync_file($keyboard_map_path);

        $adminguide_path = get_file_base() . '/adminzone/pages/comcode_custom/' . get_site_default_lang() . '/website.txt';
        if (!file_exists(dirname($adminguide_path))) {
            require_code('files2');
            make_missing_directory(dirname($adminguide_path));
        }
        $adminguide = post_param_string('adminguide');
        $adminguide = str_replace('__company__', post_param_string('company_name'), $adminguide);
        $myfile = @fopen($adminguide_path, 'wb');
        if ($myfile === false) {
            intelligent_write_error($adminguide_path);
        }
        if (fwrite($myfile, $adminguide) < strlen($adminguide)) {
            warn_exit(do_lang_tempcode('COULD_NOT_SAVE_FILE'));
        }
        fclose($myfile);
        fix_permissions($adminguide_path);
        sync_file($adminguide_path);

        $start_path = get_file_base() . '/adminzone/pages/comcode_custom/' . get_site_default_lang() . '/start.txt';
        if (!file_exists($start_path)) {
            $start = post_param_string('start_page');
            $myfile = @fopen($start_path, 'wb');
            if ($myfile === false) {
                intelligent_write_error($start_path);
            }
            if (fwrite($myfile, $start) < strlen($start)) {
                warn_exit(do_lang_tempcode('COULD_NOT_SAVE_FILE'));
            }
            fclose($myfile);
            fix_permissions($start_path);
            sync_file($start_path);
        }

        if (is_null($GLOBALS['CURRENT_SHARE_USER'])) { // Only if not a shared install
            $critical_errors = file_get_contents(get_file_base() . '/sources/critical_errors.php');
            $critical_errors = str_replace('Composr', addslashes(post_param_string('rebrand_name')), $critical_errors);
            $critical_errors = str_replace('http://compo.sr', addslashes(post_param_string('rebrand_base_url')), $critical_errors);
            $critical_errors = str_replace('ocProducts', 'ocProducts/' . addslashes(post_param_string('company_name')), $critical_errors);
            $critical_errors_path = 'sources_custom/critical_errors.php';

            afm_make_file($critical_errors_path, $critical_errors, false);
        }

        $save_header_path = get_file_base() . '/themes/' . $GLOBALS['FORUM_DRIVER']->get_theme('') . '/templates_custom/GLOBAL_HTML_WRAP.tpl';
        if (!file_exists(dirname($save_header_path))) {
            require_code('files2');
            make_missing_directory(dirname($save_header_path));
        }
        $header_path = $save_header_path;
        if (!file_exists($header_path)) {
            $header_path = get_file_base() . '/themes/default/templates/GLOBAL_HTML_WRAP.tpl';
        }
        $header_tpl = file_get_contents($header_path);
        $header_tpl = str_replace('Copyright ocProducts Limited', '', $header_tpl);
        $myfile = @fopen($save_header_path, 'wb');
        if ($myfile === false) {
            intelligent_write_error($save_header_path);
        }
        if (fwrite($myfile, $header_tpl) < strlen($header_tpl)) {
            warn_exit(do_lang_tempcode('COULD_NOT_SAVE_FILE'));
        }
        fclose($myfile);
        fix_permissions($save_header_path);
        sync_file($save_header_path);

        if (post_param_integer('churchy', 0) == 1) {
            if (is_object($GLOBALS['FORUM_DB'])) {
                $GLOBALS['FORUM_DB']->query_delete('f_emoticons', array('e_code' => ':devil:'), '', 1);
            } else {
                $GLOBALS['SITE_DB']->query_delete('f_emoticons', array('e_code' => ':devil:'), '', 1);
            }
        }

        // Make sure some stuff is disabled for non-admin staff
        $staff_groups = $GLOBALS['FORUM_DRIVER']->get_moderator_groups();
        $disallowed_pages = array('admin_setupwizard', 'admin_addons', 'admin_backup', 'admin_errorlog', 'admin_import', 'admin_commandr', 'admin_phpinfo', 'admin_debrand');
        foreach (array_keys($staff_groups) as $id) {
            foreach ($disallowed_pages as $page) {
                $GLOBALS['SITE_DB']->query_delete('group_page_access', array('page_name' => $page, 'zone_name' => 'adminzone', 'group_id' => $id), '', 1); // in case already exists
                $GLOBALS['SITE_DB']->query_insert('group_page_access', array('page_name' => $page, 'zone_name' => 'adminzone', 'group_id' => $id));
            }
        }

        // Clean up the theme images
        //  background-image
        $theme = $GLOBALS['FORUM_DRIVER']->get_theme('');
        find_theme_image('background_image');
        //  logo/*
        if (addon_installed('zone_logos')) {
            $main_logo_url = find_theme_image('logo/-logo', false, true);

            $test = find_theme_image('logo/adminzone-logo', true);
            if ($test != '') {
                $GLOBALS['SITE_DB']->query_update('theme_images', array('path' => $main_logo_url), array('id' => 'logo/adminzone-logo', 'theme' => $theme), '', 1);
            }

            $test = find_theme_image('logo/cms-logo', true);
            if ($test != '') {
                $GLOBALS['SITE_DB']->query_update('theme_images', array('path' => $main_logo_url), array('id' => 'logo/cms-logo', 'theme' => $theme), '', 1);
            }

            $test = find_theme_image('logo/collaboration-logo', true);
            if ($test != '') {
                $GLOBALS['SITE_DB']->query_update('theme_images', array('path' => $main_logo_url), array('id' => 'logo/collaboration-logo', 'theme' => $theme), '', 1);
            }
        }

        // Various other icons
        require_code('uploads');
        $path = get_url('', 'favicon', 'themes/default/images_custom');
        if ($path[0] != '') {
            $GLOBALS['SITE_DB']->query_update('theme_images', array('path' => $path[0]), array('id' => 'favicon'));
        }
        $path = get_url('', 'webclipicon', 'themes/default/images_custom');
        if ($path[0] != '') {
            $GLOBALS['SITE_DB']->query_update('theme_images', array('path' => $path[0]), array('id' => 'webclipicon'));
        }
        if (addon_installed('cns_avatars')) {
            $path = get_url('', 'system_avatar', 'themes/default/images_custom');
            if ($path[0] != '') {
                $GLOBALS['SITE_DB']->query_update('theme_images', array('path' => $path[0]), array('id' => 'cns_default_avatars/system'));
            }
        }

        // Decache
        require_code('caches3');
        erase_cached_templates(false, null, TEMPLATE_DECACHE_WITH_CONFIG);
        erase_cached_templates(false, array('GLOBAL_HTML_WRAP'));

        // Redirect them back to editing screen
        $url = build_url(array('page' => '_SELF', 'type' => 'browse'), '_SELF');
        return redirect_screen($this->title, $url, do_lang_tempcode('SUCCESS'));
    }
}
