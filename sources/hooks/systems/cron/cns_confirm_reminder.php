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
 * @package    core_cns
 */

/**
 * Hook class.
 */
class Hook_cron_cns_confirm_reminder
{
    /**
     * Run function for CRON hooks. Searches for tasks to perform.
     */
    public function run()
    {
        if (get_forum_type() != 'cns') {
            return;
        }

        $time = time();
        $last_time = intval(get_value('last_confirm_reminder_time', null, true));
        if ($last_time == 0) {
            $last_time = time();
        }
        if ($last_time > time() - 24 * 60 * 60 * 2) {
            return;
        }
        set_value('last_confirm_reminder_time', strval($time), true);

        require_code('mail');
        require_lang('cns');

        push_db_scope_check(false);
        $rows = $GLOBALS['SITE_DB']->query('SELECT * FROM ' . $GLOBALS['SITE_DB']->get_table_prefix() . 'f_members WHERE ' . db_string_not_equal_to('m_validated_email_confirm_code', '') . ' AND m_join_time>' . strval($last_time - 24 * 60 * 60 * 2) . ' AND m_join_time<=' . strval($last_time));
        pop_db_scope_check();
        foreach ($rows as $row) {
            $coppa = (get_option('is_on_coppa') == '1') && (utctime_to_usertime(time() - mktime(0, 0, 0, $row['m_dob_month'], $row['m_dob_day'], $row['m_dob_year'])) / 31536000.0 < 13.0);
            if (!$coppa) {
                $zone = get_module_zone('join');
                if ($zone != '') {
                    $zone .= '/';
                }
                $url = get_base_url() . '/' . $zone . 'index.php?page=join&type=step4&email=' . rawurlencode($row['m_email_address']) . '&code=' . $row['m_validated_email_confirm_code'];
                $url_simple = get_base_url() . '/' . $zone . 'index.php?page=join&type=step4';
                $message = do_lang('CNS_SIGNUP_TEXT', comcode_escape(get_site_name()), comcode_escape($url), array($url_simple, $row['m_email_address'], strval($row['m_validated_email_confirm_code'])), $row['m_language']);
                mail_wrap(do_lang('CONFIRM_EMAIL_SUBJECT', get_site_name(), null, null, $row['m_language']), $message, array($row['m_email_address']), $row['m_username']);
            }
        }
    }
}
