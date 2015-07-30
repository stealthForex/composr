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
 * @package    core
 */

/**
 * Standard code module initialisation function.
 */
function init__forum_stub()
{
    global $IS_SUPER_ADMIN_CACHE, $IS_STAFF_CACHE;
    $IS_SUPER_ADMIN_CACHE = array();
    $IS_STAFF_CACHE = array();
}

/**
 * Forum Driver base class.
 *
 * @package    core
 */

/**
 * Forum driver class.
 *
 * @package    core
 */
class Forum_driver_base
{
    public $connection;

    public $MEMBER_ROWS_CACHED = array();

    public $EMOTICON_CACHE = null;

    /**
     * Add the specified custom field to the forum (some forums implemented this using proper custom profile fields, others through adding a new field).
     *
     * @param  string $name The name of the new custom field
     */
    public function install_delete_custom_field($name)
    {
        if (method_exists($this, '_install_delete_custom_field')) {
            $this->_install_delete_custom_field($name);
        }
    }

    /**
     * Find the usergroup ID of the forum guest member.
     *
     * @return GROUP The usergroup ID of the forum guest member
     */
    public function get_guest_group()
    {
        return db_get_first_id();
    }

    /**
     * Get a URL to a forum member's member profile.
     *
     * @param  MEMBER $id The forum member
     * @param  boolean $definitely_profile Whether to be insistent that we go to the profile, rather than possibly starting an IM which can link to the profile
     * @param  boolean $tempcode_okay Whether it is okay to return the result using Tempcode (more efficient, and allows keep_* parameters to propagate which you almost certainly want!)
     * @return mixed The URL
     */
    public function member_profile_url($id, $definitely_profile = false, $tempcode_okay = false)
    {
        $url = mixed();

        if ((!$definitely_profile) && (get_option('username_click_im') == '1') && ($id != $this->get_guest_id()) && (addon_installed('chat')) && (has_privilege(get_member(), 'start_im'))) {
            $url = build_url(array('page' => 'chat', 'type' => 'browse', 'enter_im' => $id), get_module_zone('chat'));
            if (!$tempcode_okay) {
                $url = $url->evaluate();
            }
            return $url;
        }

        $url = $this->_member_profile_url($id, $tempcode_okay);
        if (($tempcode_okay) && (!is_object($url))) {
            $url = make_string_tempcode($url);
        }
        if ((get_forum_type() != 'none') && (get_forum_type() != 'cns') && (get_option('forum_in_portal') == '1')) {
            $url = build_url(array('page' => 'forums', 'url' => $url), get_module_zone('forums'));
            if (!$tempcode_okay) {
                $url = $url->evaluate();
            }
        }
        return $url;
    }

    /**
     * Get a hyperlink (i.e. HTML link, not just a URL) to a forum member's member profile.
     *
     * @param  MEMBER $id The forum member
     * @param  boolean $definitely_profile Whether to be insistent that we go to the profile, rather than possibly starting an IM which can link to the profile
     * @param  string $_username The username (blank: look it up)
     * @param  boolean $use_displayname Whether to use the displayname rather than the username (if we have them)
     * @return Tempcode The hyperlink
     */
    public function member_profile_hyperlink($id, $definitely_profile = false, $_username = '', $use_displayname = true)
    {
        if (is_guest($id)) {
            return ($_username == '') ? do_lang_tempcode('GUEST') : make_string_tempcode(escape_html($_username));
        }
        if ($_username == '') {
            $_username = $this->get_username($id, $use_displayname);
        }
        if (is_null($_username)) {
            return do_lang_tempcode('UNKNOWN');
        }
        $url = $this->member_profile_url($id, $definitely_profile, true);
        return hyperlink($url, $_username, false, true);
    }

    /**
     * Get a URL to a forum join page.
     *
     * @return mixed The URL
     */
    public function join_url()
    {
        $url = $this->_join_url();
        if ((get_forum_type() != 'none') && (get_forum_type() != 'cns') && (get_option('forum_in_portal') == '1')) {
            $url = build_url(array('page' => 'forums', 'url' => $url), get_module_zone('forums'));
        }
        return $url;
    }

    /**
     * Get a URL to a forum 'user online' list.
     *
     * @param  boolean $tempcode_okay Whether it is okay to return the result using Tempcode (more efficient)
     * @return mixed The URL
     */
    public function users_online_url($tempcode_okay = false)
    {
        $url = $this->_users_online_url($tempcode_okay);
        if ((get_forum_type() != 'none') && (get_forum_type() != 'cns') && (get_option('forum_in_portal') == '1')) {
            $url = build_url(array('page' => 'forums', 'url' => $url), get_module_zone('forums'));
        }
        return $url;
    }

    /**
     * Get a URL to send a forum member a PM.
     *
     * @param  MEMBER $id The forum member
     * @param  boolean $tempcode_okay Whether it is okay to return the result using Tempcode (more efficient)
     * @return mixed The URL
     */
    public function member_pm_url($id, $tempcode_okay = false)
    {
        $url = $this->_member_pm_url($id, $tempcode_okay);
        if ((get_forum_type() != 'none') && (get_forum_type() != 'cns') && (get_option('forum_in_portal') == '1')) {
            $url = build_url(array('page' => 'forums', 'url' => $url), get_module_zone('forums'));
        }
        return $url;
    }

    /**
     * Get a URL to a forum.
     *
     * @param  integer $id The ID of the forum
     * @param  boolean $tempcode_okay Whether it is okay to return the result using Tempcode (more efficient)
     * @return mixed The URL
     */
    public function forum_url($id, $tempcode_okay = false)
    {
        $url = $this->_forum_url($id, $tempcode_okay);
        if ((get_forum_type() != 'none') && (get_forum_type() != 'cns') && (get_option('forum_in_portal') == '1')) {
            $url = build_url(array('page' => 'forums', 'url' => $url), get_module_zone('forums'));
        }
        return $url;
    }

    /**
     * Get a member's username.
     *
     * @param  MEMBER $id The member
     * @param  boolean $use_displayname Whether to use the displayname rather than the username (if we have them)
     * @return ?SHORT_TEXT The username (null: deleted member)
     */
    public function get_username($id, $use_displayname = false)
    {
        if ($id == $this->get_guest_id()) {
            require_code('lang');
            if (!function_exists('do_lang')) {
                return 'Guest';
            }
            $ret = do_lang('GUEST', null, null, null, null, false);
            if ($ret === null) {
                $ret = 'Guest';
            }
            return $ret;
        }

        global $USER_NAME_CACHE;
        if (isset($USER_NAME_CACHE[$id])) {
            $ret = $USER_NAME_CACHE[$id];
            if ($use_displayname) {
                $ret = get_displayname($ret);
            }
            return $ret;
        }

        $ret = $this->_get_username($id);
        if ($ret === '') {
            if (get_forum_type() == 'cns') {
                return uniqid('', false); // Let it get deleted at least
            }
            $ret = null; // Odd, but sometimes
        }
        $USER_NAME_CACHE[$id] = $ret;
        if ($use_displayname) {
            $ret = get_displayname($ret);
        }
        return $ret;
    }

    /**
     * Get a member's e-mail address.
     *
     * @param  MEMBER $id The member
     * @return SHORT_TEXT The e-mail address (blank: not known)
     */
    public function get_member_email_address($id)
    {
        global $MEMBER_EMAIL_CACHE;
        if (array_key_exists($id, $MEMBER_EMAIL_CACHE)) {
            return $MEMBER_EMAIL_CACHE[$id];
        }

        $ret = $this->_get_member_email_address($id);
        $MEMBER_EMAIL_CACHE[$id] = $ret;
        return $ret;
    }

    /**
     * Find whether a member is staff.
     *
     * @param  MEMBER $id The member
     * @param  boolean $skip_staff_filter Whether to avoid checking the staff filter (i.e. ignore M.S.N.'s)
     * @return boolean The answer
     */
    public function is_staff($id, $skip_staff_filter = false)
    {
        if (is_guest($id)) {
            return false;
        }

        if (!$skip_staff_filter) {
            global $IS_STAFF_CACHE;
            if (array_key_exists($id, $IS_STAFF_CACHE)) {
                return $IS_STAFF_CACHE[$id];
            }

            if ((isset($this->connection)) && (is_forum_db($this->connection)) && (get_option('is_on_staff_filter', true) === '1') && (get_forum_type() != 'none') && (!$GLOBALS['FORUM_DRIVER']->disable_staff_filter())) {
                if (stripos(get_cms_cpf('sites', $id), substr(get_site_name(), 0, 200)) === false) {
                    $IS_STAFF_CACHE[$id] = false;
                    return false;
                }
            }
        }

        $ret = $this->_is_staff($id);
        if (!$skip_staff_filter) {
            $IS_STAFF_CACHE[$id] = $ret;
        }
        return $ret;
    }

    /**
     * If we can't get a list of admins via a usergroup query, we have to disable the staff filter - else the staff filtering can cause disaster at the point of being turned on (because it can't automatically sync).
     *
     * @return boolean Whether the staff filter is disabled
     */
    public function disable_staff_filter()
    {
        if (method_exists($this, '_disable_staff_filter')) {
            return $this->_disable_staff_filter();
        }

        return false;
    }

    /**
     * Find whether a member is a super administrator.
     *
     * @param  MEMBER $id The member
     * @return boolean The answer
     */
    public function is_super_admin($id)
    {
        global $IS_SUPER_ADMIN_CACHE;
        if (isset($IS_SUPER_ADMIN_CACHE[$id])) {
            return $IS_SUPER_ADMIN_CACHE[$id];
        }

        if (is_guest($id)) {
            $IS_SUPER_ADMIN_CACHE[$id] = false;
            return false;
        }

        $ret = $this->_is_super_admin($id);
        $IS_SUPER_ADMIN_CACHE[$id] = $ret;
        return $ret;
    }

    /**
     * Get a list of the super admin usergroups.
     *
     * @return array The list of usergroups
     */
    public function get_super_admin_groups()
    {
        global $ADMIN_GROUP_CACHE;
        if ($ADMIN_GROUP_CACHE !== null) {
            return $ADMIN_GROUP_CACHE;
        }

        $ret = $this->_get_super_admin_groups();
        $ADMIN_GROUP_CACHE = $ret;
        return $ret;
    }

    /**
     * Get a list of the moderator usergroups.
     *
     * @return array The list of usergroups
     */
    public function get_moderator_groups()
    {
        global $MODERATOR_GROUP_CACHE, $IN_MINIKERNEL_VERSION;
        if ((!is_null($MODERATOR_GROUP_CACHE)) && ((!$IN_MINIKERNEL_VERSION) || ($MODERATOR_GROUP_CACHE != array()))) {
            return $MODERATOR_GROUP_CACHE;
        }

        $ret = $this->_get_moderator_groups();
        $MODERATOR_GROUP_CACHE = $ret;
        return $ret;
    }

    /**
     * Get a map of forum usergroups (id=>name).
     *
     * @param  boolean $hide_hidden Whether to obscure the name of hidden usergroups
     * @param  boolean $only_permissive Whether to only grab permissive usergroups
     * @param  boolean $force_show_all Do not limit things even if there are huge numbers of usergroups
     * @param  ?array $force_find Usergroups that must be included in the results (null: no extras must be)
     * @param  ?MEMBER $for_member Always return usergroups of this member (null: current member)
     * @param  boolean $skip_hidden Whether to completely skip hidden usergroups
     * @return array The map
     */
    public function get_usergroup_list($hide_hidden = false, $only_permissive = false, $force_show_all = false, $force_find = null, $for_member = null, $skip_hidden = false)
    {
        global $USERGROUP_LIST_CACHE;
        if ((!is_null($USERGROUP_LIST_CACHE)) && (isset($USERGROUP_LIST_CACHE[$hide_hidden][$only_permissive][$force_show_all][serialize($force_find)][$for_member][$skip_hidden]))) {
            return $USERGROUP_LIST_CACHE[$hide_hidden][$only_permissive][$force_show_all][serialize($force_find)][$for_member][$skip_hidden];
        }

        $ret = $this->_get_usergroup_list($hide_hidden, $only_permissive, $force_show_all, $force_find, $for_member, $skip_hidden);
        if (count($ret) != 0) { // Conditional is for when installing... can't cache at point of there being no usergroups
            if (is_null($USERGROUP_LIST_CACHE)) {
                $USERGROUP_LIST_CACHE = array();
            }
            $USERGROUP_LIST_CACHE[$hide_hidden][$only_permissive][$force_show_all][serialize($force_find)][$for_member][$skip_hidden] = $ret;
        }
        return $ret;
    }

    /**
     * Get a list of usergroups a member is in.
     *
     * @param  MEMBER $id The member
     * @param  boolean $skip_secret Whether to skip looking at secret usergroups.
     * @param  boolean $handle_probation Whether to take probation into account
     * @return array The list of usergroups
     */
    public function get_members_groups($id, $skip_secret = false, $handle_probation = true)
    {
        if ((is_guest($id)) && (get_forum_type() == 'cns')) {
            static $ret = null;
            if ($ret === null) {
                $ret = array(db_get_first_id());
            }
            return $ret;
        }

        global $USERS_GROUPS_CACHE;
        if (isset($USERS_GROUPS_CACHE[$id][$skip_secret][$handle_probation])) {
            return $USERS_GROUPS_CACHE[$id][$skip_secret][$handle_probation];
        }

        $ret = $this->_get_members_groups($id, $skip_secret, $handle_probation);
        $USERS_GROUPS_CACHE[$id][$skip_secret][$handle_probation] = $ret;
        return $ret;
    }

    /**
     * Get the current member's theme identifier.
     *
     * @param  ?ID_TEXT $zone_for The zone we are getting the theme for (null: current zone)
     * @return ID_TEXT The theme identifier
     */
    public function get_theme($zone_for = null)
    {
        global $SITE_INFO;

        if ($zone_for !== null) {
            $zone_theme = $GLOBALS['SITE_DB']->query_select_value('zones', 'zone_theme', array('zone_name' => $zone_for));
            if ($zone_theme != '-1') {
                if ((!isset($SITE_INFO['no_disk_sanity_checks'])) || ($SITE_INFO['no_disk_sanity_checks'] != '1')) {
                    if (!is_dir(get_custom_file_base() . '/themes/' . $zone_theme)) {
                        return $this->get_theme();
                    }
                }

                return $zone_theme;
            }
            return $this->get_theme();
        }

        global $USER_THEME_CACHE;
        if ($USER_THEME_CACHE !== null) {
            return $USER_THEME_CACHE;
        }

        global $IN_MINIKERNEL_VERSION;
        if (($IN_MINIKERNEL_VERSION) || (in_safe_mode())) {
            if ($zone_for === null) {
                $zone_for = get_zone_name();
            }
            return ($zone_for === 'adminzone' || $zone_for === 'cms') ? 'admin' : 'default';
        }

        // Try hardcoded in URL
        $USER_THEME_CACHE = filter_naughty(get_param_string('keep_theme', get_param_string('utheme', '-1')));
        if ($USER_THEME_CACHE != '-1') {
            if ((!is_dir(get_file_base() . '/themes/' . $USER_THEME_CACHE)) && (!is_dir(get_custom_file_base() . '/themes/' . $USER_THEME_CACHE))) {
                $theme = $USER_THEME_CACHE;
                $USER_THEME_CACHE = 'default';
                require_code('site');
                attach_message(do_lang_tempcode('NO_SUCH_THEME', escape_html($theme)), 'warn');
                $USER_THEME_CACHE = null;
            } else {
                global $ZONE;
                $zone_theme = ($ZONE === null) ? $GLOBALS['SITE_DB']->query_select_value_if_there('zones', 'zone_theme', array('zone_name' => get_zone_name())) : $ZONE['zone_theme'];

                require_code('permissions');
                if (($USER_THEME_CACHE == 'default') || ($USER_THEME_CACHE == $zone_theme) || (has_category_access(get_member(), 'theme', $USER_THEME_CACHE))) {
                    return $USER_THEME_CACHE;
                } else {
                    $theme = $USER_THEME_CACHE;
                    $USER_THEME_CACHE = 'default';
                    require_code('site');
                    attach_message(do_lang_tempcode('NO_THEME_PERMISSION', escape_html($theme)), 'warn');
                    $USER_THEME_CACHE = null;
                }
            }
        } else {
            $USER_THEME_CACHE = null;
        }

        // Try hardcoded in Composr
        global $ZONE;
        $zone_theme = ($ZONE === null) ? $GLOBALS['SITE_DB']->query_select_value_if_there('zones', 'zone_theme', array('zone_name' => get_zone_name())) : $ZONE['zone_theme'];
        $default_theme = ((get_page_name() == 'login') && (get_option('root_zone_login_theme') == '1')) ? $GLOBALS['SITE_DB']->query_select_value('zones', 'zone_theme', array('zone_name' => '')) : $zone_theme;
        if (($default_theme !== null) && ($default_theme != '-1')) {
            if ((!isset($SITE_INFO['no_disk_sanity_checks'])) || ($SITE_INFO['no_disk_sanity_checks'] != '1')) {
                if (!is_dir(get_custom_file_base() . '/themes/' . $default_theme)) {
                    $default_theme = '-1';
                }
            }
        }
        if ($default_theme != '-1') {
            $USER_THEME_CACHE = $default_theme;
            if ($USER_THEME_CACHE == '') {
                $USER_THEME_CACHE = 'default';
            }
            return $USER_THEME_CACHE;
        }
        if ($default_theme == '-1') {
            $default_theme = 'default';
        }

        // Get from forums
        $USER_THEME_CACHE = filter_naughty($this->_get_theme());
        if (($USER_THEME_CACHE == '') || (($USER_THEME_CACHE != 'default') && (!is_dir(get_custom_file_base() . '/themes/' . $USER_THEME_CACHE)))) {
            $USER_THEME_CACHE = 'default';
        }
        if ($USER_THEME_CACHE == '-1') {
            $USER_THEME_CACHE = 'default';
        }
        require_code('permissions');
        if (($USER_THEME_CACHE != 'default') && (!has_category_access(get_member(), 'theme', $USER_THEME_CACHE))) {
            $USER_THEME_CACHE = 'default';
        }

        if ($USER_THEME_CACHE == '') {
            $USER_THEME_CACHE = 'default';
        }

        return $USER_THEME_CACHE;
    }

    /**
     * Get the number of new forum posts on the system in the last 24 hours.
     *
     * @return integer Number of forum posts
     */
    public function get_num_new_forum_posts()
    {
        $value = strval($this->_get_num_new_forum_posts());
        return intval($value);
    }

    /**
     * Find whether a forum is threaded.
     *
     * @param  integer $topic_id The topic ID
     * @return boolean Whether it is
     */
    public function topic_is_threaded($topic_id)
    {
        return false;
    }

    /**
     * Load extra details for a list of posts. Does not need to return anything if forum driver doesn't support partial post loading (which is only useful for threaded topic partial-display).
     *
     * @param  AUTO_LINK $topic_id Topic the posts come from
     * @param  array $post_ids List of post IDs
     * @return array Extra details
     */
    public function get_post_remaining_details($topic_id, $post_ids)
    {
        return array();
    }
}
