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
 * @package    core_fields
 */

/**
 * Hook class.
 */
class Hook_fields_date
{
    // ==============
    // Module: search
    // ==============

    /**
     * Get special Tempcode for inputting this field.
     *
     * @param  array $field The field details
     * @return ?array Specially encoded input detail rows (null: nothing special)
     */
    public function get_search_inputter($field)
    {
        $type = '_DATE';
        $special = $this->get_search_filter_from_env($field);
        $extra = '';
        $display = get_translated_text($field['cf_name']);

        $range_search = (option_value_from_field_array($field, 'range_search', 'off') == 'on');
        if ($range_search) {
            $type .= '_RANGE';
        }

        return array('NAME' => strval($field['id']) . $extra, 'DISPLAY' => $display, 'TYPE' => $type, 'SPECIAL' => $special);
    }

    /**
     * Get special SQL from POSTed parameters for this field.
     *
     * @param  array $field The field details
     * @param  integer $i We're processing for the ith row
     * @return ?array Tuple of SQL details (array: extra trans fields to search, array: extra plain fields to search, string: an extra table segment for a join, string: the name of the field to use as a title, if this is the title, extra WHERE clause stuff) (null: nothing special)
     */
    public function inputted_to_sql_for_search($field, $i)
    {
        $range_search = (option_value_from_field_array($field, 'range_search', 'off') == 'on');
        if ($range_search) {
            return null;
        }

        return exact_match_sql($field, $i, 'short', $this->get_search_filter_from_env($field));
    }

    /**
     * Get a search filter string from the environment.
     *
     * @param  array $field The field details
     * @return string Filter
     */
    public function get_search_filter_from_env($field)
    {
        $range_search = (option_value_from_field_array($field, 'range_search', 'off') == 'on');
        if ($range_search) {
            $_from = post_param_date('option_' . strval($field['id']) . '_from', true, false);
            $from = '';
            if (!is_null($_from)) {
                $from = date('Y-m-d H:i', $_from);
            }

            $_to = post_param_date('option_' . strval($field['id']) . '_to', true, false);
            $to = '';
            if (!is_null($_to)) {
                $to = date('Y-m-d H:i', $_to);
            }

            return $from . ';' . $to;
        }

        $filter = post_param_date('option_' . strval($field['id']), true);
        return is_null($filter) ? '' : date('Y-m-d H:i', $filter);
    }

    // ===================
    // Backend: fields API
    // ===================

    /**
     * Get some info bits relating to our field type, that helps us look it up / set defaults.
     *
     * @param  ?array $field The field details (null: new field)
     * @param  ?boolean $required Whether a default value cannot be blank (null: don't "lock in" a new default value)
     * @param  ?string $default The given default value as a string (null: don't "lock in" a new default value)
     * @return array Tuple of details (row-type,default-value-to-use,db row-type)
     */
    public function get_field_value_row_bits($field, $required = null, $default = null)
    {
        if ($required !== null) {
            if (($required) && ($default == '')) {
                $default = date('Y-m-d H:i', utctime_to_usertime());
            }
        }
        return array('short_unescaped', $default, 'short');
    }

    /**
     * Convert a field value to something renderable.
     *
     * @param  array $field The field details
     * @param  mixed $ev The raw value
     * @return mixed Rendered field (Tempcode or string)
     */
    public function render_field_value($field, $ev)
    {
        if (is_object($ev)) {
            return $ev;
        }

        if ($ev != '') {
            if (stripos($ev, 'now') !== false) {
                $time = time();
            } else {
                // Y-m-d H:i
                $bits = explode(' ', $ev, 2);
                $date_bits = explode((strpos($bits[0], '-') !== false) ? '-' : '/', $bits[0], 3);
                if (!array_key_exists(1, $date_bits)) {
                    $date_bits[1] = date('m');
                }
                if (!array_key_exists(2, $date_bits)) {
                    $date_bits[2] = date('Y');
                }
                if (!array_key_exists(1, $bits)) {
                    $bits[1] = '00:00:00';
                }
                $time_bits = explode(':', $bits[1], 3);
                if (!array_key_exists(1, $time_bits)) {
                    $time_bits[1] = '00';
                }
                if (!array_key_exists(2, $time_bits)) {
                    $time_bits[2] = '00';
                }
                $time = mktime(intval($time_bits[0]), intval($time_bits[1]), intval($time_bits[2]), intval($date_bits[1]), intval($date_bits[2]), intval($date_bits[0]));
            }
            $ev = get_timezoned_date($time, true, false, true, true);
        }
        return escape_html($ev);
    }

    // ======================
    // Frontend: fields input
    // ======================

    /**
     * Get form inputter.
     *
     * @param  string $_cf_name The field name
     * @param  string $_cf_description The field description
     * @param  array $field The field details
     * @param  ?string $actual_value The actual current value of the field (null: none)
     * @param  boolean $new Whether this is for a new entry
     * @return ?Tempcode The Tempcode for the input field (null: skip the field - it's not input)
     */
    public function get_field_inputter($_cf_name, $_cf_description, $field, $actual_value, $new)
    {
        $time = mixed();

        if ((is_null($actual_value)) || ($actual_value == '')) {
            $time = null;
        } elseif ($actual_value == 'NOW') {
            $time = time();
        } else {
            // Y-m-d H:i
            $bits = explode(' ', $actual_value, 2);
            $date_bits = explode((strpos($bits[0], '-') !== false) ? '-' : '/', $bits[0], 3);
            if (!array_key_exists(1, $date_bits)) {
                $date_bits[1] = date('m');
            }
            if (!array_key_exists(2, $date_bits)) {
                $date_bits[2] = date('Y');
            }
            if (!array_key_exists(1, $bits)) {
                $bits[1] = '0';
            }
            $time_bits = explode(':', $bits[1], 3);
            if (!array_key_exists(1, $time_bits)) {
                $time_bits[1] = '00';
            }
            if (!array_key_exists(2, $time_bits)) {
                $time_bits[2] = '00';
            }

            $time = array(intval($time_bits[1]), intval($time_bits[0]), intval($date_bits[1]), intval($date_bits[2]), intval($date_bits[0]));
        }
        /*
        $min_year=1902; // 1902 is based on signed integer limit
        $max_year=2037; // 2037 is based on signed integer limit
        $years_to_show=$max_year-$min_year;
        ^^^ NONSENSE: Integers not used to save!
        */
        $min_year = intval(option_value_from_field_array($field, 'min_year', strval(intval(date('Y')) - 10)));
        $years_to_show = intval(option_value_from_field_array($field, 'max_year', strval(intval(date('Y')) + 10))) - $min_year;
        $input_name = empty($field['cf_input_name']) ? ('field_' . strval($field['id'])) : $field['cf_input_name'];
        return form_input_date($_cf_name, $_cf_description, $input_name, $field['cf_required'] == 1, ($field['cf_required'] == 0) && ($actual_value == ''), true, $time, $years_to_show, $min_year);
    }

    /**
     * Find the posted value from the get_field_inputter field
     *
     * @param  boolean $editing Whether we were editing (because on edit, it could be a fractional edit)
     * @param  array $field The field details
     * @param  ?string $upload_dir Where the files will be uploaded to (null: do not store an upload, return NULL if we would need to do so)
     * @param  ?array $old_value Former value of field (null: none)
     * @return ?string The value (null: could not process)
     */
    public function inputted_to_field_value($editing, $field, $upload_dir = 'uploads/catalogues', $old_value = null)
    {
        $id = $field['id'];
        $stub = 'field_' . strval($id);

        require_code('temporal2');
        list($year, $month, $day, $hour, $minute) = post_param_date_components($stub);
        if (is_null($year)) {
            return $editing ? STRING_MAGIC_NULL : '';
        }
        if (is_null($month)) {
            return $editing ? STRING_MAGIC_NULL : '';
        }
        if (is_null($day)) {
            return $editing ? STRING_MAGIC_NULL : '';
        }

        $_min_year = option_value_from_field_array($field, 'min_year', '');
        $_max_year = option_value_from_field_array($field, 'max_year', '');
        if ((($_min_year != '') && ($year < intval($_min_year))) || (($_max_year != '') && ($year > intval($_max_year)))) {
            warn_exit(do_lang_tempcode('INTERNAL_ERROR'));
        }

        return str_pad(strval($year), 4, '0', STR_PAD_LEFT) . '-' . str_pad(strval($month), 2, '0', STR_PAD_LEFT) . '-' . str_pad(strval($day), 2, '0', STR_PAD_LEFT) . ' ' . strval($hour) . ':' . str_pad(strval($minute), 2, '0', STR_PAD_LEFT);
    }
}
