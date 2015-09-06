<?php /*

 Composr
 Copyright (c) ocProducts, 2004-2015

 See text/EN/licence.txt for full licencing information.


 NOTE TO PROGRAMMERS:
   Do not edit this file. If you need to make changes, save your changed file to the appropriate *_custom folder
   **** If you ignore this advice, then your website upgrades (e.g. for bug fixes) will likely kill your changes ****

*/

/**
 * @license    http://opensource.org/licenses/cpal_1.0 Common Public Attribution License
 * @copyright  ocProducts Ltd
 * @package    core_configuration
 */

/**
 * Module page class.
 */
class Module_admin_config
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
        $info['version'] = 15;
        $info['locked'] = true;
        $info['update_require_upgrade'] = 1;
        return $info;
    }

    /**
     * Find entry-points available within this module.
     *
     * @param  boolean $check_perms Whether to check permissions.
     * @param  ?MEMBER $member_id The member to check permissions as (null: current user).
     * @param  boolean $support_crosslinks Whether to allow cross links to other modules (identifiable via a full-page-link rather than a screen-name).
     * @param  boolean $be_deferential Whether to avoid any entry-point (or even return NULL to disable the page in the Sitemap) if we know another module, or page_group, is going to link to that entry-point. Note that "!" and "browse" entry points are automatically merged with container page nodes (likely called by page-groupings) as appropriate.
     * @return ?array A map of entry points (screen-name=>language-code/string or screen-name=>[language-code/string, icon-theme-image]) (null: disabled).
     */
    public function get_entry_points($check_perms = true, $member_id = null, $support_crosslinks = true, $be_deferential = false)
    {
        $ret = array(
            'browse' => array('CONFIGURATION', 'menu/adminzone/setup/config/config'),
        );

        $ret['base'] = array('BASE_CONFIGURATION', $support_crosslinks && $be_deferential/*The virtual nodes for categories don't have an icon so match that*/ ? null : 'menu/adminzone/setup/config/base_config');

        if (!$be_deferential) {
            if (addon_installed('xml_fields')) {
                $ret['xml_fields'] = array('FIELD_FILTERS', 'menu/adminzone/setup/xml_fields');
            }

            if (addon_installed('breadcrumbs')) {
                $ret['xml_breadcrumbs'] = array('BREADCRUMB_OVERRIDES', 'menu/adminzone/structure/breadcrumbs');
            }

            if (is_null(get_value('brand_base_url'))) {
                $ret['upgrader'] = array('FU_UPGRADER_TITLE', 'menu/adminzone/tools/upgrade');
            }

            if (addon_installed('syndication')) {
                $ret['backend'] = array('FEEDS', 'links/rss');
            }

            if (addon_installed('code_editor')) {
                $ret['code_editor'] = array('CODE_EDITOR', 'menu/adminzone/tools/code_editor');
            }
        }

        return $ret;
    }

    public $title;
    public $category;

    /**
     * Module pre-run function. Allows us to know meta-data for <head> before we start streaming output.
     *
     * @return ?Tempcode Tempcode indicating some kind of exceptional output (null: none).
     */
    public function pre_run()
    {
        $type = get_param_string('type', 'browse');

        require_lang('config');

        if ($type == 'browse') {
            set_helper_panel_tutorial('tut_adv_configuration');

            $this->title = get_screen_title('CONFIGURATION');
        }

        if ($type == 'category') {
            /*Actually let's save the space  set_helper_panel_tutorial('tut_adv_configuration');*/

            $category = get_param_string('id');
            breadcrumb_set_parents(array(array('_SELF:_SELF:browse', do_lang_tempcode('CONFIGURATION'))));
            breadcrumb_set_self(do_lang_tempcode('CONFIG_CATEGORY_' . $category));

            $this->title = get_screen_title(do_lang_tempcode('CONFIG_CATEGORY_' . $category), false);

            $this->category = $category;
        }

        if ($type == 'set') {
            $category = get_param_string('id', 'MAIN');
            $this->title = get_screen_title(do_lang_tempcode('CONFIG_CATEGORY_' . $category), false);
        }

        if ($type == 'base') {
            $this->title = get_screen_title('CONFIGURATION');
        }

        if ($type == 'upgrader') {
            $this->title = get_screen_title('FU_UPGRADER_TITLE');
        }

        if ($type == 'backend') {
            $this->title = get_screen_title('FEEDS');
        }

        if ($type == 'code_editor') {
            $this->title = get_screen_title('CODE_EDITOR');
        }

        if ($type == 'xml_fields') {
            set_helper_panel_tutorial('tut_fields_filter');
            set_helper_panel_text(comcode_lang_string('DOC_FIELD_FILTERS'));

            $this->title = get_screen_title('FIELD_FILTERS');
        }

        if ($type == '_xml_fields') {
            $this->title = get_screen_title('FIELD_FILTERS');
        }

        if ($type == 'xml_breadcrumbs') {
            set_helper_panel_tutorial('tut_structure');
            set_helper_panel_text(comcode_lang_string('DOC_BREADCRUMB_OVERRIDES'));

            $this->title = get_screen_title('BREADCRUMB_OVERRIDES');
        }

        if ($type == '_xml_breadcrumbs') {
            $this->title = get_screen_title('BREADCRUMB_OVERRIDES');
        }

        return null;
    }

    /**
     * Execute the module.
     *
     * @return Tempcode The result of execution.
     */
    public function run()
    {
        require_all_lang();
        require_code('config2');

        $type = get_param_string('type', 'browse');

        if ($type == 'browse') {
            return $this->config_choose(); // List of categories
        }
        if ($type == 'category') {
            return $this->config_category(); // Category editing UI
        }
        if ($type == 'set') {
            return $this->config_set(); // Category editing actualiser
        }

        if (addon_installed('xml_fields')) {
            if ($type == 'xml_fields') {
                return $this->xml_fields();
            }
            if ($type == '_xml_fields') {
                return $this->_xml_fields();
            }
        }
        if (addon_installed('breadcrumbs')) {
            if ($type == 'xml_breadcrumbs') {
                return $this->xml_breadcrumbs();
            }
            if ($type == '_xml_breadcrumbs') {
                return $this->_xml_breadcrumbs();
            }
        }

        if ($type == 'base') {
            return $this->base();
        }
        if (is_null(get_value('brand_base_url'))) {
            if ($type == 'upgrader') {
                return $this->upgrader();
            }
        }
        if (addon_installed('syndication')) {
            if ($type == 'backend') {
                return $this->backend();
            }
        }
        if (addon_installed('code_editor')) {
            if ($type == 'code_editor') {
                return $this->code_editor();
            }
        }

        return new Tempcode();
    }

    /**
     * The UI to choose what configuration page to edit.
     *
     * @return Tempcode The UI
     */
    public function config_choose()
    {
        // Test to see if we have any ModSecurity issue that blocks config form submissions, via posting through some perfectly legitimate things that it might be paranoid about
        if (count($_POST) == 0) {
            $proper_url = build_url(array('page' => ''), '');
            $_proper_url = $proper_url->evaluate();
            $test_a = http_download_file($_proper_url, 0, false, true);
            $message_a = $GLOBALS['HTTP_MESSAGE'];
            if ($message_a == '200')
            {
                $test_b = http_download_file($_proper_url, 0, false, true, 'ocPortal', array('test_a' => '/usr/bin/unzip -o @_SRC_@ -x -d @_DST_@', 'test_b' => '<iframe src="http://example.com/"></iframe>', 'test_c' => '<script>console.log(document.cookie);</script>'));
                $message_b = $GLOBALS['HTTP_MESSAGE'];
                if ($message_b != '200')
                {
                    attach_message(do_lang_tempcode('MOD_SECURITY', escape_html($message_b)), 'warn');
                }
            }
        }

        // Find all categories
        $hooks = find_all_hooks('systems', 'config');
        $categories = array();
        foreach (array_keys($hooks) as $hook) {
            require_code('hooks/systems/config/' . filter_naughty($hook));
            $ob = object_factory('Hook_config_' . $hook);
            $option = $ob->get_details();
            if ((is_null($GLOBALS['CURRENT_SHARE_USER'])) || ($option['shared_hosting_restricted'] == 0)) {
                if (!is_null($ob->get_default())) {
                    $category = $option['category'];
                    if (!isset($categories[$category])) {
                        $categories[$category] = 0;
                    }
                    $categories[$category]++;
                }
            }
        }

        // Show all categories
        $categories_tpl = new Tempcode();
        ksort($categories);
        foreach ($categories as $category => $option_count) {
            // Some are skipped
            if (get_forum_type() != 'cns') {
                if ($category == 'USERS') {
                    continue;
                }
                if ($category == 'FORUMS') {
                    continue;
                }
            }
            if (has_no_forum()) {
                if ($category == 'FORUMS') {
                    continue;
                }
            }

            // Put together details...

            $url = build_url(array('page' => '_SELF', 'type' => 'category', 'id' => $category), '_SELF');

            $_category_name = do_lang('CONFIG_CATEGORY_' . $category, null, null, null, null, false);
            if (!$GLOBALS['SEMI_DEV_MODE']) {
                if (is_null($_category_name)) {
                    continue;
                }
            } else {
                $_category_name = do_lang('CONFIG_CATEGORY_' . $category); // We want to see an error
            }
            $category_name = do_lang_tempcode('CONFIG_CATEGORY_' . $category);

            $description = do_lang_tempcode('CONFIG_CATEGORY_DESCRIPTION__' . $category);

            $count = do_lang_tempcode('CATEGORY_SUBORDINATE_2', escape_html(integer_format($option_count)));

            $categories_tpl->attach(do_template('INDEX_SCREEN_FANCIER_ENTRY', array(
                '_GUID' => '6ba2b09432d06e7502c71e7aac2d3527',
                'COUNT' => $count,
                'NAME' => $category_name,
                'TITLE' => protect_from_escaping(do_lang('CONFIGURATION') . ': ' . $_category_name),
                'DESCRIPTION' => $description,
                'URL' => $url,
            )));
        }
        $categories_tpl->attach(do_template('INDEX_SCREEN_FANCIER_ENTRY', array(
            '_GUID' => '6fde99ae81367fb7405e94b6731a7d9a',
            'COUNT' => null,
            'TITLE' => protect_from_escaping(do_lang('CONFIGURATION') . ': ' . do_lang('BASE_CONFIGURATION')),
            'URL' => get_base_url() . '/config_editor.php',
            'NAME' => do_lang_tempcode('BASE_CONFIGURATION'),
            'DESCRIPTION' => do_lang_tempcode('DOC_BASE_CONFIGURATION'),
        )));

        // Wrapper
        return do_template('INDEX_SCREEN_FANCIER_SCREEN', array(
            '_GUID' => 'c8fdb2b481625d58b0b228c897fda72f',
            'TITLE' => $this->title,
            'PRE' => paragraph(do_lang_tempcode('CHOOSE_A_CONFIG_CATEGORY')),
            'CONTENT' => $categories_tpl,
            'POST' => '',
        ));
    }

    /**
     * The UI to edit a configuration page.
     *
     * @return Tempcode The UI
     */
    public function config_category()
    {
        require_javascript('checking');

        // Load up some basic details
        $category = $this->category;
        $post_url = build_url(array('page' => '_SELF', 'type' => 'set', 'id' => $category, 'redirect' => get_param_string('redirect', null)), '_SELF');
        $category_description = do_lang_tempcode('CONFIG_CATEGORY_DESCRIPTION__' . $category);

        // Find all options in category
        $hooks = find_all_hooks('systems', 'config');
        $rows = array();
        foreach (array_keys($hooks) as $hook) {
            require_code('hooks/systems/config/' . filter_naughty($hook));
            $ob = object_factory('Hook_config_' . $hook);
            $option = $ob->get_details();
            if ((is_null($GLOBALS['CURRENT_SHARE_USER'])) || ($option['shared_hosting_restricted'] == 0)) {
                if (!is_null($ob->get_default())) {
                    if ($category == $option['category']) {
                        if (!isset($option['order_in_category_group'])) {
                            $option['order_in_category_group'] = 100;
                        }
                        $option['ob'] = $ob;
                        $option['name'] = $hook;
                        $rows[$hook] = $option;
                    }
                }
            }
        }

        // Add in special ones
        if ($category == 'SITE') {
            $rows['timezone'] = array('name' => 'timezone', 'human_name' => 'TIME_ZONE', 'c_value' => '', 'type' => 'special', 'category' => 'SITE', 'group' => 'INTERNATIONALISATION', 'explanation' => 'DESCRIPTION_TIMEZONE_SITE', 'shared_hosting_restricted' => 0, 'order_in_category_group' => 1);
        }
        require_code('files');
        $upload_max_filesize = (ini_get('upload_max_filesize') == '0') ? do_lang('NA') : clean_file_size(php_return_bytes(ini_get('upload_max_filesize')));
        $post_max_size = (ini_get('post_max_size') == '0') ? do_lang('NA') : clean_file_size(php_return_bytes(ini_get('post_max_size')));

        // Sort generally, categorise into groups, sort the groups
        sort_maps_by($rows, 'order_in_category_group');
        $all_known_groups = array();
        foreach ($rows as $myrow) {
            $_group = do_lang($myrow['group']);

            $_group = strtolower(trim(preg_replace('#(&.*;)|[^\w\d\s]#U', '', $_group)));
            if ((array_key_exists($_group, $all_known_groups)) && ($all_known_groups[$_group] != $myrow['group'])) {
                $_group = 'std_' . $myrow['group']; // If cat names translate to same things or are in non-latin characters like Cyrillic
            }

            $all_known_groups[$_group] = $myrow['group'];
        }
        $advanced_key = strtolower(trim(preg_replace('#(&.*;)|[^\w\d\s]#U', '', do_lang('ADVANCED'))));
        ksort($all_known_groups);
        if (isset($all_known_groups[$advanced_key])) { // Advanced goes last
            $temp = $all_known_groups[$advanced_key];
            unset($all_known_groups[$advanced_key]);
            $all_known_groups[$advanced_key] = $temp;
        }
        $groups = array();
        foreach ($all_known_groups as $group_codename) {
            $group_rows = array();
            foreach ($rows as $myrow) {
                if ($myrow['group'] == $group_codename) {
                    $group_rows[] = $myrow;
                }
            }

            $groups[$group_codename] = $group_rows;
        }

        // Render option groups
        $groups_tempcode = new Tempcode();
        require_code('form_templates');
        $_groups = array();
        foreach ($groups as $group_codename => $rows) {
            $out = '';
            foreach ($rows as $myrow) {
                $name = $myrow['name']; // Can't get from array key, as sorting nuked it

                // Lang strings
                $human_name = do_lang_tempcode($myrow['human_name']);
                $_explanation = do_lang($myrow['explanation'], null, null, null, null, false);
                if (is_null($_explanation)) {
                    $_explanation = do_lang('CONFIG_GROUP_DEFAULT_DESCRIP_' . $myrow['group']);
                    $explanation = do_lang_tempcode('CONFIG_GROUP_DEFAULT_DESCRIP_' . $myrow['group']);
                } else {
                    $explanation = do_lang_tempcode($myrow['explanation']);
                }

                if (isset($myrow['required'])) {
                    $required = $myrow['required'];
                } else {
                    if ($myrow['type'] == 'integer') {
                        $required = true;
                    } elseif ($myrow['type'] == 'float') {
                        $required = true;
                    } elseif ($myrow['type'] == 'list') {
                        $required = true;
                    } else {
                        $required = false;
                    }
                }

                // Render field inputter
                switch ($myrow['type']) {
                    case 'special':
                        switch ($name) {
                            case 'timezone':
                                $list = '';
                                $timezone = get_site_timezone();
                                foreach (get_timezone_list() as $_timezone => $timezone_nice) {
                                    $list .= static_evaluate_tempcode(form_input_list_entry($_timezone, $_timezone == $timezone, $timezone_nice));
                                }
                                $out .= static_evaluate_tempcode(form_input_list($human_name, $explanation, 'timezone', make_string_tempcode($list)));
                                break;

                            default:
                                $ob = $myrow['ob'];
                                $out .= static_evaluate_tempcode($ob->field_inputter($name, $myrow, $human_name, $explanation));
                                break;
                        }
                        break;

                    case 'integer':
                        $out .= static_evaluate_tempcode(form_input_integer($human_name, $explanation, $name, intval(get_option($name)), $required));
                        break;

                    case 'float':
                        $out .= static_evaluate_tempcode(form_input_float($human_name, $explanation, $name, floatval(get_option($name)), $required));
                        break;

                    case 'line':
                    case 'transline':
                        $out .= static_evaluate_tempcode(form_input_line($human_name, $explanation, $name, get_option($name), $required));
                        break;

                    case 'text':
                    case 'transtext':
                        $out .= static_evaluate_tempcode(form_input_text($human_name, $explanation, $name, get_option($name), $required, null, true));
                        break;

                    case 'comcodeline':
                        $out .= static_evaluate_tempcode(form_input_line_comcode($human_name, $explanation, $name, get_option($name), $required));
                        break;

                    case 'comcodetext':
                        $out .= static_evaluate_tempcode(form_input_text_comcode($human_name, $explanation, $name, get_option($name), $required, null, true));
                        break;

                    case 'list':
                        $list = '';
                        if (!$required) {
                            $list .= static_evaluate_tempcode(form_input_list_entry('', false, do_lang_tempcode('NA_EM')));
                        }
                        $_value = get_option($name);
                        $values = explode('|', $myrow['list_options']);
                        foreach ($values as $value) {
                            $__value = str_replace(' ', '__', $value);
                            $_option_text = do_lang('CONFIG_OPTION_' . $name . '_VALUE_' . $__value, null, null, null, null, false);
                            if (!is_null($_option_text)) {
                                $option_text = do_lang_tempcode('CONFIG_OPTION_' . $name . '_VALUE_' . $__value);
                            } else {
                                $option_text = make_string_tempcode($value);
                            }
                            $list .= static_evaluate_tempcode(form_input_list_entry($value, $_value == $value, $option_text));
                        }
                        $out .= static_evaluate_tempcode(form_input_list($human_name, $explanation, $name, make_string_tempcode($list), null, false, false));
                        break;

                    case 'tick':
                        $out .= static_evaluate_tempcode(form_input_tick($human_name, $explanation, $name, get_option($name) == '1'));
                        break;

                    case 'username':
                        $out .= static_evaluate_tempcode(form_input_username($human_name, $explanation, $name, get_option($name), $required, false));
                        break;

                    case 'colour':
                        $out .= static_evaluate_tempcode(form_input_colour($human_name, $explanation, $name, get_option($name), $required));
                        break;

                    case 'date':
                        $out .= static_evaluate_tempcode(form_input_date($human_name, $explanation, $name, $required, false, false, intval(get_option($name)), 40, intval(date('Y')) - 20, null));
                        break;

                    case 'forum':
                        if ((get_forum_type() == 'cns') && (addon_installed('cns_forum'))) {
                            $current_setting = get_option($name);
                            if (!is_numeric($current_setting)) {
                                $_current_setting = $GLOBALS['FORUM_DB']->query_select_value_if_there('f_forums', 'id', array('f_name' => $current_setting));
                                if (is_null($_current_setting)) {
                                    if ($required) {
                                        $current_setting = strval(db_get_first_id());
                                        attach_message(do_lang_tempcode('FORUM_CURRENTLY_UNSET', $human_name), 'notice');
                                    } else {
                                        $current_setting = null;
                                    }
                                } else {
                                    $current_setting = strval($_current_setting);
                                }
                            }
                            $out .= static_evaluate_tempcode(form_input_tree_list($human_name, $explanation, $name, null, 'choose_forum', array(), $required, $current_setting));
                        } else {
                            $out .= static_evaluate_tempcode(form_input_line($human_name, $explanation, $name, get_option($name), $required));
                        }
                        break;

                    case 'forum_grouping':
                        if (get_forum_type() == 'cns') {
                            $tmp_value = $GLOBALS['FORUM_DB']->query_select_value_if_there('f_forum_groupings', 'id', array('c_title' => get_option($name)));

                            require_code('cns_forums2');
                            $_list = new Tempcode();
                            if (!$required) {
                                $_list->attach(form_input_list_entry('', false, do_lang_tempcode('NA_EM')));
                            }
                            $_list->attach(cns_create_selection_list_forum_groupings(null, $tmp_value));
                            $out .= static_evaluate_tempcode(form_input_list($human_name, $explanation, $name, $_list));
                        } else {
                            $out .= static_evaluate_tempcode(form_input_line($human_name, $explanation, $name, get_option($name), $required));
                        }
                        break;

                    case 'usergroup':
                    case 'usergroup_not_guest':
                        if (get_forum_type() == 'cns') {
                            $tmp_value = $GLOBALS['FORUM_DB']->query_select_value_if_there('f_groups', 'id', array($GLOBALS['FORUM_DB']->translate_field_ref('g_name') => get_option($name)));

                            require_code('cns_groups');
                            $_list = new Tempcode();
                            if (!$required) {
                                $_list->attach(form_input_list_entry('', false, do_lang_tempcode('NA_EM')));
                            }
                            $_list->attach(cns_create_selection_list_usergroups($tmp_value, $myrow['type'] == 'usergroup'));
                            $out .= static_evaluate_tempcode(form_input_list($human_name, $explanation, $name, $_list));
                        } else {
                            $out .= static_evaluate_tempcode(form_input_line($human_name, $explanation, $name, get_option($name), $required));
                        }
                        break;

                    default:
                        fatal_exit('Invalid config option type: ' . $myrow['type'] . ' (for ' . $myrow['name'] . ')');
                }
            }

            // Render group
            $group_title = do_lang_tempcode($group_codename);
            $_group_description = do_lang('CONFIG_GROUP_DESCRIP_' . $group_codename, escape_html($post_max_size), escape_html($upload_max_filesize), null, null, false);
            if (is_null($_group_description)) {
                $group_description = new Tempcode();
            } else {
                $group_description = do_lang_tempcode('CONFIG_GROUP_DESCRIP_' . $group_codename, escape_html($post_max_size), escape_html($upload_max_filesize));
            }
            $group = do_template('CONFIG_GROUP', array('_GUID' => '84c0db86002a33a383a7c2e195dd3913', 'GROUP_DESCRIPTION' => $group_description, 'GROUP_NAME' => $group_codename, 'GROUP' => $out, 'GROUP_TITLE' => $group_title));
            $groups_tempcode->attach($group->evaluate());
            $_groups[$group_codename] = $group_title;
        }

        list($warning_details, $ping_url) = handle_conflict_resolution();

        // Render
        return do_template('CONFIG_CATEGORY_SCREEN', array(
            '_GUID' => 'd01b28b71c38bbb52b6aaf877c7f7b0e',
            'CATEGORY_DESCRIPTION' => $category_description,
            '_GROUPS' => $_groups,
            'PING_URL' => $ping_url,
            'WARNING_DETAILS' => $warning_details,
            'TITLE' => $this->title,
            'URL' => $post_url,
            'GROUPS' => $groups_tempcode,
            'SUBMIT_ICON' => 'buttons__save',
            'SUBMIT_NAME' => do_lang_tempcode('SAVE'),
        ));
    }

    /**
     * The actualiser to edit a configuration page.
     *
     * @return Tempcode The UI
     */
    public function config_set()
    {
        require_code('input_filter_2');
        rescue_shortened_post_request();

        global $CONFIG_OPTIONS_CACHE;

        $category = get_param_string('id', 'MAIN');

        if (strtoupper(cms_srv('REQUEST_METHOD')) != 'POST') {
            warn_exit(do_lang_tempcode('INTERNAL_ERROR'));
        }

        // Make sure we haven't locked ourselves out due to short URL support
        if ((post_param_string('url_scheme', 'RAW') != 'RAW') && (substr(cms_srv('SERVER_SOFTWARE'), 0, 6) == 'Apache') && ((!file_exists(get_file_base() . DIRECTORY_SEPARATOR . '.htaccess')) || (strpos(file_get_contents(get_file_base() . DIRECTORY_SEPARATOR . '.htaccess'), 'RewriteEngine on') === false) || ((function_exists('apache_get_modules')) && (!in_array('mod_rewrite', apache_get_modules()))) || (http_download_file(get_base_url() . '/sitemap.htm', null, false, true) != '') && ($GLOBALS['HTTP_MESSAGE'] == '404'))) {
            warn_exit(do_lang_tempcode('BEFORE_MOD_REWRITE'));
        }

        // Make sure we haven't just locked staff out
        if (addon_installed('staff')) {
            $new_site_name = substr(post_param_string('site_name', ''), 0, 200);
            if (($new_site_name != '') && (get_option('is_on_sync_staff') === '1')) {
                $admin_groups = array_merge($GLOBALS['FORUM_DRIVER']->get_super_admin_groups(), $GLOBALS['FORUM_DRIVER']->get_moderator_groups());
                $staff = $GLOBALS['FORUM_DRIVER']->member_group_query($admin_groups, 100);
                if (count($staff) < 100) {
                    foreach ($staff as $row_staff) {
                        $member = $GLOBALS['FORUM_DRIVER']->mrow_id($row_staff);
                        if ($GLOBALS['FORUM_DRIVER']->is_staff($member)) {
                            $sites = get_cms_cpf('sites');
                            $sites = str_replace(', ' . get_site_name(), '', $sites);
                            $sites = str_replace(',' . get_site_name(), '', $sites);
                            $sites = str_replace(get_site_name() . ', ', '', $sites);
                            $sites = str_replace(get_site_name() . ',', '', $sites);
                            $sites = str_replace(get_site_name(), '', $sites);
                            if ($sites != '') {
                                $sites .= ', ';
                            }
                            $sites .= $new_site_name;
                            $GLOBALS['FORUM_DRIVER']->set_custom_field($member, 'sites', $sites);
                        }
                    }
                }
            }
        }

        // Empty thumbnail cache if needed
        if (function_exists('imagetypes')) {
            if ((!is_null(post_param_string('thumb_width', null))) && (post_param_string('thumb_width') != get_option('thumb_width'))) {
                require_code('caches3');
                erase_thumb_cache();
            }
        }

        // Find all options in category
        $hooks = find_all_hooks('systems', 'config');
        $rows = array();
        foreach (array_keys($hooks) as $hook) {
            require_code('hooks/systems/config/' . filter_naughty($hook));
            $ob = object_factory('Hook_config_' . $hook);
            $option = $ob->get_details();
            if ($category == $option['category']) {
                if ((is_null($GLOBALS['CURRENT_SHARE_USER'])) || ($option['shared_hosting_restricted'] == 0)) {
                    if (!is_null($ob->get_default())) {
                        $option['ob'] = $ob;
                        $rows[$hook] = $option;
                    }
                }
            }
        }

        // Add in special ones
        if ($category == 'SITE') {
            $rows['timezone'] = array('shared_hosting_restricted' => 0, 'type' => 'special');
        }

        // Go through all options on the page, saving
        foreach ($rows as $name => $myrow) {
            // Save
            if ($myrow['type'] == 'tick') {
                $value = strval(post_param_integer($name, 0));
            } elseif ($myrow['type'] == 'date') {
                $date_value = get_input_date($name);
                $value = is_null($date_value) ? '' : strval($date_value);
            } elseif ((($myrow['type'] == 'forum') || ($myrow['type'] == '?forum')) && (get_forum_type() == 'cns')) {
                $value = post_param_string($name);
                if (is_numeric($value)) {
                    $value = $GLOBALS['FORUM_DB']->query_select_value_if_there('f_forums', 'f_name', array('id' => post_param_integer($name)));
                }
                if (is_null($value)) {
                    $value = '';
                }
            } elseif (($myrow['type'] == 'forum_grouping') && (get_forum_type() == 'cns')) {
                $value = post_param_string($name);
                if (is_numeric($value)) {
                    $value = $GLOBALS['FORUM_DB']->query_select_value_if_there('f_forum_groupings', 'c_title', array('id' => post_param_integer($name)));
                }
                if (is_null($value)) {
                    $value = '';
                }
            } elseif ((($myrow['type'] == 'usergroup') || ($myrow['type'] == 'usergroup_not_guest')) && (get_forum_type() == 'cns')) {
                $_value = $GLOBALS['FORUM_DB']->query_select_value_if_there('f_groups', 'g_name', array('id' => post_param_integer($name)));
                if (is_null($_value)) {
                    $value = '';
                } else {
                    $value = get_translated_text($_value);
                }
            } else {
                $value = post_param_string($name, '');
            }

            // Hard-coded special options
            if ($name == 'timezone') {
                set_value('timezone', $value);
            } else {
                // If the option was changed
                $old_value = get_option($name);
                if (($old_value != $value) || ($CONFIG_OPTIONS_CACHE[$name]['c_set'] == 0)) {
                    set_option($name, $value);
                }
            }
        }

        // Clear some caching
        require_code('caches3');
        erase_comcode_page_cache();
        erase_block_cache();
        //persistent_cache_delete('OPTIONS');  Done by set_option / erase_persistent_cache
        erase_persistent_cache();
        erase_cached_templates();

        // Show it worked / Refresh
        $redirect = get_param_string('redirect', null);
        if ($redirect === null) {
            $url = build_url(array('page' => '_SELF', 'type' => 'browse'), '_SELF'); // ,'type'=>'category','id'=>$category
        } else {
            $url = make_string_tempcode($redirect);
        }
        return redirect_screen($this->title, $url, do_lang_tempcode('SUCCESS'));
    }

    /**
     * Redirect to the config_editor script.
     *
     * @return Tempcode The UI
     */
    public function base()
    {
        $keep = symbol_tempcode('KEEP', array('1'));
        $url = get_base_url() . '/config_editor.php' . $keep->evaluate();
        return redirect_screen($this->title, $url);
    }

    /**
     * Redirect to the upgrader script.
     *
     * @return Tempcode The UI
     */
    public function upgrader()
    {
        $keep = symbol_tempcode('KEEP', array('1'));
        $url = get_base_url() . '/upgrader.php' . $keep->evaluate();
        return redirect_screen($this->title, $url);
    }

    /**
     * Redirect to the backend script.
     *
     * @return Tempcode The UI
     */
    public function backend()
    {
        $keep = symbol_tempcode('KEEP', array('1'));
        $url = get_base_url() . '/backend.php' . $keep->evaluate();
        return redirect_screen($this->title, $url);
    }

    /**
     * Redirect to the code_editor script.
     *
     * @return Tempcode The UI
     */
    public function code_editor()
    {
        $keep = symbol_tempcode('KEEP', array('1'));
        $url = get_base_url() . '/code_editor.php' . $keep->evaluate();
        return redirect_screen($this->title, $url);
    }

    /**
     * The UI to edit the fields XML file.
     *
     * @return Tempcode The UI
     */
    public function xml_fields()
    {
        $post_url = build_url(array('page' => '_SELF', 'type' => '_xml_fields'), '_SELF');

        return do_template('XML_CONFIG_SCREEN', array(
            '_GUID' => 'cc21f921ecbdbdf83e1e28d2b3f75a3a',
            'TITLE' => $this->title,
            'POST_URL' => $post_url,
            'XML' => file_exists(get_custom_file_base() . '/data_custom/xml_config/fields.xml') ? file_get_contents(get_custom_file_base() . '/data_custom/xml_config/fields.xml') : file_get_contents(get_file_base() . '/data/xml_config/fields.xml'),
        ));
    }

    /**
     * The UI actualiser edit the fields XML file.
     *
     * @return Tempcode The UI
     */
    public function _xml_fields()
    {
        if (!file_exists(get_custom_file_base() . '/data_custom')) {
            require_code('files2');
            make_missing_directory(get_custom_file_base() . '/data_custom');
        }

        $myfile = @fopen(get_custom_file_base() . '/data_custom/xml_config/fields.xml', GOOGLE_APPENGINE ? 'wb' : 'at');
        if ($myfile === false) {
            intelligent_write_error(get_custom_file_base() . '/data_custom/xml_config/fields.xml');
        }
        @flock($myfile, LOCK_EX);
        if (!GOOGLE_APPENGINE) {
            ftruncate($myfile, 0);
        }
        $xml = post_param_string('xml');
        if (fwrite($myfile, $xml) < strlen($xml)) {
            warn_exit(do_lang_tempcode('COULD_NOT_SAVE_FILE'));
        }
        @flock($myfile, LOCK_UN);
        fclose($myfile);
        fix_permissions(get_custom_file_base() . '/data_custom/xml_config/fields.xml');
        sync_file(get_custom_file_base() . '/data_custom/xml_config/fields.xml');

        log_it('FIELD_FILTERS');

        return inform_screen($this->title, do_lang_tempcode('SUCCESS'));
    }

    /**
     * The UI to edit the breadcrumbs XML file.
     *
     * @return Tempcode The UI
     */
    public function xml_breadcrumbs()
    {
        $post_url = build_url(array('page' => '_SELF', 'type' => '_xml_breadcrumbs'), '_SELF');

        return do_template('XML_CONFIG_SCREEN', array(
            '_GUID' => '456f56149832d459bce72ca63a1578b9',
            'TITLE' => $this->title,
            'POST_URL' => $post_url,
            'XML' => file_exists(get_custom_file_base() . '/data_custom/xml_config/breadcrumbs.xml') ? file_get_contents(get_custom_file_base() . '/data_custom/xml_config/breadcrumbs.xml') : file_get_contents(get_file_base() . '/data/xml_config/breadcrumbs.xml'),
        ));
    }

    /**
     * The UI actualiser edit the breadcrumbs XML file.
     *
     * @return Tempcode The UI
     */
    public function _xml_breadcrumbs()
    {
        if (!file_exists(get_custom_file_base() . '/data_custom')) {
            require_code('files2');
            make_missing_directory(get_custom_file_base() . '/data_custom');
        }

        $myfile = @fopen(get_custom_file_base() . '/data_custom/xml_config/breadcrumbs.xml', GOOGLE_APPENGINE ? 'wb' : 'at');
        if ($myfile === false) {
            intelligent_write_error(get_custom_file_base() . '/data_custom/xml_config/breadcrumbs.xml');
        }
        @flock($myfile, LOCK_EX);
        if (!GOOGLE_APPENGINE) {
            ftruncate($myfile, 0);
        }
        $xml = post_param_string('xml');
        if (fwrite($myfile, $xml) < strlen($xml)) {
            warn_exit(do_lang_tempcode('COULD_NOT_SAVE_FILE'));
        }
        @flock($myfile, LOCK_UN);
        fclose($myfile);
        fix_permissions(get_custom_file_base() . '/data_custom/xml_config/breadcrumbs.xml');
        sync_file(get_custom_file_base() . '/data_custom/xml_config/breadcrumbs.xml');

        log_it('BREADCRUMB_OVERRIDES');

        return inform_screen($this->title, do_lang_tempcode('SUCCESS'));
    }
}
