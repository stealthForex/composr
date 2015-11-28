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
 * @package    core_cns
 */

/**
 * Hook class.
 */
class Hook_profiles_tabs_edit_settings
{
    /**
     * Find whether this hook is active.
     *
     * @param  MEMBER $member_id_of The ID of the member who is being viewed
     * @param  MEMBER $member_id_viewing The ID of the member who is doing the viewing
     * @return boolean Whether this hook is active
     */
    public function is_active($member_id_of, $member_id_viewing)
    {
        if (post_param_integer('delete', 0) == 1) {
            return false; // So no form validation
        }

        return (($member_id_of == $member_id_viewing) || (has_privilege($member_id_viewing, 'assume_any_member')) || (has_privilege($member_id_viewing, 'member_maintenance')));
    }

    /**
     * Render function for profile tabs edit hooks.
     *
     * @param  MEMBER $member_id_of The ID of the member who is being viewed
     * @param  MEMBER $member_id_viewing The ID of the member who is doing the viewing
     * @param  boolean $leave_to_ajax_if_possible Whether to leave the tab contents NULL, if tis hook supports it, so that AJAX can load it later
     * @return ?array A tuple: The tab title, the tab body text (may be blank), the tab fields, extra JavaScript (may be blank) the suggested tab order, hidden fields (optional) (null: if $leave_to_ajax_if_possible was set), the icon
     */
    public function render_tab($member_id_of, $member_id_viewing, $leave_to_ajax_if_possible = false)
    {
        $order = 0;

        // Actualiser
        if ((post_param_string('submitting_settings_tab', null) !== null) || (fractional_edit())) {
            require_code('cns_members_action2');

            $is_ldap = cns_is_ldap_member($member_id_of);
            $is_httpauth = cns_is_httpauth_member($member_id_of);

            if (($is_ldap) || ($is_httpauth) || (($member_id_of != $member_id_viewing) && (!has_privilege($member_id_viewing, 'assume_any_member')))) {
                $password = null;
            } else {
                $password = post_param_string('edit_password', '');
                if ($password == '') {
                    $password = null;
                } else {
                    $password_confirm = trim(post_param_string('password_confirm'));
                    if ($password != $password_confirm) {
                        warn_exit(make_string_tempcode(escape_html(do_lang('PASSWORD_MISMATCH'))));
                    }
                }
            }

            $custom_fields = cns_get_all_custom_fields_match(
                $GLOBALS['FORUM_DRIVER']->get_members_groups($member_id_of), // groups
                (($member_id_of != $member_id_viewing) && (!has_privilege($member_id_viewing, 'view_any_profile_field'))) ? 1 : null, // public view
                (($member_id_of == $member_id_viewing) && (!has_privilege($member_id_viewing, 'view_any_profile_field'))) ? 1 : null, // owner view
                (($member_id_of == $member_id_viewing) && (!has_privilege($member_id_viewing, 'view_any_profile_field'))) ? 1 : null // owner set
            );
            $actual_custom_fields = ((post_param_integer('submitting_profile_tab', 0) == 1) || (fractional_edit())) ? cns_read_in_custom_fields($custom_fields, $member_id_of) : array();

            if (!fractional_edit()) {
                $pt_allow = array_key_exists('pt_allow', $_POST) ? implode(',', $_POST['pt_allow']) : '';
                $tmp_groups = $GLOBALS['CNS_DRIVER']->get_usergroup_list(true, true);
                $all_pt_allow = '';
                foreach (array_keys($tmp_groups) as $key) {
                    if ($key != db_get_first_id()) {
                        if ($all_pt_allow != '') {
                            $all_pt_allow .= ',';
                        }
                        $all_pt_allow .= strval($key);
                    }
                }
                if ($pt_allow == $all_pt_allow) {
                    $pt_allow = '*';
                }
                $pt_rules_text = post_param_string('pt_rules_text', null);
            } else {
                $pt_allow = null;
                $pt_rules_text = null;
            }

            if ((!fractional_edit()) && (has_privilege($member_id_viewing, 'member_maintenance'))) {
                $validated = post_param_integer('validated', 0);
                $primary_group = (($is_ldap) || (!has_privilege($member_id_viewing, 'assume_any_member'))) ? null : post_param_integer('primary_group', null);
                $is_perm_banned = post_param_integer('is_perm_banned', 0);
                $old_is_perm_banned = $GLOBALS['FORUM_DRIVER']->get_member_row_field($member_id_of, 'm_is_perm_banned');
                if ($old_is_perm_banned != $is_perm_banned) {
                    if ($is_perm_banned == 1) {
                        cns_ban_member($member_id_of);
                    } else {
                        cns_unban_member($member_id_of);
                    }
                }
                $highlighted_name = post_param_integer('highlighted_name', 0);
                if (has_privilege($member_id_viewing, 'probate_members')) {
                    $on_probation_until = post_param_date('on_probation_until');

                    $current__on_probation_until = $GLOBALS['FORUM_DRIVER']->get_member_row_field($member_id_of, 'm_on_probation_until');
                    if (((is_null($on_probation_until)) || ($on_probation_until <= time())) && ($current__on_probation_until > time())) {
                        log_it('STOP_PROBATION', strval($member_id_of), $GLOBALS['FORUM_DRIVER']->get_username($member_id_of));
                    } elseif ((!is_null($on_probation_until)) && ($on_probation_until > time()) && ($current__on_probation_until <= time())) {
                        log_it('START_PROBATION', strval($member_id_of), $GLOBALS['FORUM_DRIVER']->get_username($member_id_of));
                    } elseif ((!is_null($on_probation_until)) && ($current__on_probation_until > $on_probation_until) && ($on_probation_until > time()) && ($current__on_probation_until > time())) {
                        log_it('REDUCE_PROBATION', strval($member_id_of), $GLOBALS['FORUM_DRIVER']->get_username($member_id_of));
                    } elseif ((!is_null($on_probation_until)) && ($current__on_probation_until < $on_probation_until) && ($on_probation_until > time()) && ($current__on_probation_until > time())) {
                        log_it('EXTEND_PROBATION', strval($member_id_of), $GLOBALS['FORUM_DRIVER']->get_username($member_id_of));
                    }
                } else {
                    $on_probation_until = null;
                }
            } else {
                $validated = null;
                $primary_group = null;
                $highlighted_name = null;
                $on_probation_until = null;
            }
            if ((has_actual_page_access($member_id_viewing, 'admin_cns_members')) || (has_privilege($member_id_of, 'rename_self'))) {
                $username = ($is_ldap) ? null : post_param_string('edit_username', null/*May not be passed if username not editable for member type*/);
            } else {
                $username = null;
            }

            $email_address = trim(post_param_string('email_address', member_field_is_required($member_id_of, 'email_address', null, $member_id_viewing) ? false : ''));

            $theme = post_param_string('theme', null);

            if (fractional_edit()) {
                $preview_posts = null;
                $auto_monitor_contrib_content = null;
                $views_signatures = null;
                $timezone = null;
            } else {
                $preview_posts = post_param_integer('preview_posts', 0);
                $auto_monitor_contrib_content = null;//post_param_integer('auto_monitor_contrib_content',0);   Moved to notifications tab
                $views_signatures = post_param_integer('views_signatures', 0);
                $timezone = post_param_string('timezone', get_site_timezone());
            }

            require_code('temporal2');
            list($dob_year, $dob_month, $dob_day) = post_param_date_components('dob');
            if ((is_null($dob_year)) || (is_null($dob_month)) || (is_null($dob_day))) {
                if (member_field_is_required($member_id_of, 'dob', null, $member_id_viewing)) {
                    warn_exit(do_lang_tempcode('NO_PARAMETER_SENT', escape_html('dob')));
                }

                $dob_day = null;
                $dob_month = null;
                $dob_year = null;
            }

            cns_edit_member($member_id_of, $email_address, $preview_posts, $dob_day, $dob_month, $dob_year, $timezone, $primary_group, $actual_custom_fields, $theme, post_param_integer('reveal_age', 0), $views_signatures, $auto_monitor_contrib_content, post_param_string('language', null), post_param_integer('allow_emails', 0), post_param_integer('allow_emails_from_staff', 0), $validated, $username, $password, $highlighted_name, $pt_allow, $pt_rules_text, $on_probation_until);

            if (addon_installed('content_reviews')) {
                require_code('content_reviews2');
                content_review_set('member', strval($member_id_of));
            }

            if (!fractional_edit()) {
                // Secondary groups
                //if (array_key_exists('secondary_groups',$_POST)) { Can't use this line, because deselecting all will result in it not being passed
                if (!array_key_exists('secondary_groups', $_POST)) {
                    $_POST['secondary_groups'] = array();
                }

                require_code('cns_groups_action2');
                $members_groups = $GLOBALS['CNS_DRIVER']->get_members_groups($member_id_of);
                $group_count = $GLOBALS['FORUM_DB']->query_select_value('f_groups', 'COUNT(*)');
                $groups = list_to_map('id', $GLOBALS['FORUM_DB']->query_select('f_groups', array('*'), ($group_count > 200) ? array('g_is_private_club' => 0) : null));

                foreach ($_POST['secondary_groups'] as $group_id) { // Add to new secondary groups
                    $group = $groups[intval($group_id)];

                    if (($group['g_hidden'] == 1) && (!in_array($group['id'], $members_groups)) && (!has_privilege($member_id_viewing, 'see_hidden_groups'))) {
                        continue;
                    }

                    if ((!in_array($group['id'], $members_groups)) && ((has_privilege($member_id_viewing, 'assume_any_member')) || ($group['g_open_membership'] == 1))) {
                        cns_add_member_to_group($member_id_of, $group['id']);
                    }
                }
                foreach ($members_groups as $group_id) { // Remove from old secondary groups that member is no longer in
                    if (!in_array(strval($group_id), $_POST['secondary_groups'])) {
                        cns_member_leave_group($group_id, $member_id_of);
                    }
                }
                //}

                $GLOBALS['FORUM_DB']->query('DELETE FROM ' . $GLOBALS['FORUM_DB']->get_table_prefix() . 'f_member_known_login_ips WHERE i_member_id=' . strval($member_id_of) . ' AND ' . db_string_not_equal_to('i_val_code', '')); // So any re-confirms can happen

                if (addon_installed('awards')) {
                    require_code('awards');
                    handle_award_setting('member', strval($member_id_of));
                }

                attach_message(do_lang_tempcode('SUCCESS_SAVE'), 'inform');
            }
        } elseif (post_param_integer('validated', 0) == 1) { // Special support for just approving
            $GLOBALS['FORUM_DB']->query_update('f_members', array('m_validated' => 1), array('id' => $member_id_of), '', 1);

            require_code('mail');
            $_login_url = build_url(array('page' => 'login'), get_module_zone('login'), null, false, false, true);
            $login_url = $_login_url->evaluate();

            $username = $GLOBALS['FORUM_DRIVER']->get_member_row_field($member_id_of, 'm_username');
            $email_address = $GLOBALS['FORUM_DRIVER']->get_member_row_field($member_id_of, 'm_email_address');
            $join_time = $GLOBALS['FORUM_DRIVER']->get_member_row_field($member_id_of, 'm_join_time');

            // NB: Same mail also sent in cns_members_action2.php (validate upon full edit)
            require_code('mail');
            $_login_url = build_url(array('page' => 'login'), get_module_zone('login'), null, false, false, true);
            $login_url = $_login_url->evaluate();
            mail_wrap(do_lang('VALIDATED_MEMBER_SUBJECT', get_site_name(), null, get_lang($member_id_of)), do_lang('MEMBER_VALIDATED', get_site_name(), $username, $login_url, get_lang($member_id_of)), array($email_address), $username, '', '', 3, null, false, null, false, false, false, 'MAIL', false, null, null, $join_time);

            attach_message(do_lang_tempcode('SUCCESS_SAVE'), 'inform');
        }

        if ($leave_to_ajax_if_possible) {
            return null;
        }

        // UI

        $title = do_lang_tempcode('SETTINGS');

        $myrow = $GLOBALS['FORUM_DRIVER']->get_member_row($member_id_of);
        if (is_null($myrow)) {
            warn_exit(do_lang_tempcode('MEMBER_NO_EXIST'));
        }

        require_code('cns_members_action2');
        list($fields, $hidden) = cns_get_member_fields_settings(false, $member_id_of, null, $myrow['m_email_address'], $myrow['m_preview_posts'], $myrow['m_dob_day'], $myrow['m_dob_month'], $myrow['m_dob_year'], get_users_timezone($member_id_of), $myrow['m_theme'], $myrow['m_reveal_age'], $myrow['m_views_signatures'], $myrow['m_auto_monitor_contrib_content'], $myrow['m_language'], $myrow['m_allow_emails'], $myrow['m_allow_emails_from_staff'], $myrow['m_validated'], $myrow['m_primary_group'], $myrow['m_username'], $myrow['m_is_perm_banned'], '', $myrow['m_highlighted_name'], $myrow['m_pt_allow'], get_translated_text($myrow['m_pt_rules_text'], $GLOBALS['FORUM_DB']), $myrow['m_on_probation_until']);

        // Awards?
        if (addon_installed('awards')) {
            require_code('awards');
            $fields->attach(get_award_fields('member', strval($member_id_of)));
        }

        $redirect = get_param_string('redirect', null);
        if (!is_null($redirect)) {
            $hidden->attach(form_input_hidden('redirect', $redirect));
        }

        $hidden->attach(form_input_hidden('submitting_settings_tab', '1'));

        $script = find_script('username_check');
        $javascript = "
            var form=document.getElementById('email_address').form;
            form.prior_profile_edit_submit=form.onsubmit;
            form.onsubmit=function() {
                if (typeof form.elements['edit_password']!='undefined')
                {
                    if ((form.elements['password_confirm']) && (form.elements['password_confirm'].value!=form.elements['edit_password'].value))
                    {
                        window.fauxmodal_alert('" . php_addslashes(do_lang('PASSWORD_MISMATCH')) . "');
                        return false;
                    }
                }
                if (form.elements['edit_password'].value!='')
                {
                    var url='" . addslashes($script) . "?';
                    if (!do_ajax_field_test(url,'password='+window.encodeURIComponent(form.elements['edit_password'].value)))
                    {
                        document.getElementById('submit_button').disabled=false;
                        return false;
                    }
                }
                if (typeof form.prior_profile_edit_submit!='undefined' && form.prior_profile_edit_submit) return form.prior_profile_edit_submit();
                return true;
            };
        ";

        $text = '';

        return array($title, $fields, $text, $javascript, $order, $hidden, 'tabs/member_account/edit/settings');
    }
}
