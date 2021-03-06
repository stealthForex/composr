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
 * @package    ecommerce
 */

require_code('resource_fs');

/**
 * Hook class.
 */
class Hook_commandr_fs_usergroup_subscriptions extends Resource_fs_base
{
    public $file_resource_type = 'usergroup_subscription';

    /**
     * Standard Commandr-fs function for seeing how many resources are. Useful for determining whether to do a full rebuild.
     *
     * @param  ID_TEXT $resource_type The resource type
     * @return integer How many resources there are
     */
    public function get_resources_count($resource_type)
    {
        return $GLOBALS['FORUM_DB']->query_select_value('f_usergroup_subs', 'COUNT(*)');
    }

    /**
     * Standard Commandr-fs function for searching for a resource by label.
     *
     * @param  ID_TEXT $resource_type The resource type
     * @param  LONG_TEXT $label The resource label
     * @return array A list of resource IDs
     */
    public function find_resource_by_label($resource_type, $label)
    {
        $_ret = $GLOBALS['FORUM_DB']->query_select('f_usergroup_subs', array('id'), array($GLOBALS['FORUM_DB']->translate_field_ref('s_title') => $label), 'ORDER BY id');
        $ret = array();
        foreach ($_ret as $r) {
            $ret[] = strval($r['id']);
        }
        return $ret;
    }

    /**
     * Whether the filesystem hook is active.
     *
     * @return boolean Whether it is
     */
    protected function _is_active()
    {
        return (get_forum_type() == 'cns') && (!is_cns_satellite_site());
    }

    /**
     * Standard Commandr-fs date fetch function for resource-fs hooks. Defined when getting an edit date is not easy.
     *
     * @param  array $row Resource row (not full, but does contain the ID)
     * @return ?TIME The edit date or add date, whichever is higher (null: could not find one)
     */
    protected function _get_file_edit_date($row)
    {
        $query = 'SELECT MAX(date_and_time) FROM ' . get_table_prefix() . 'actionlogs WHERE ' . db_string_equal_to('param_a', strval($row['id'])) . ' AND  (' . db_string_equal_to('the_type', 'ADD_USERGROUP_SUBSCRIPTION') . ' OR ' . db_string_equal_to('the_type', 'EDIT_USERGROUP_SUBSCRIPTION') . ')';
        return $GLOBALS['SITE_DB']->query_value_if_there($query);
    }

    /**
     * Standard Commandr-fs add function for resource-fs hooks. Adds some resource with the given label and properties.
     *
     * @param  LONG_TEXT $filename Filename OR Resource label
     * @param  string $path The path (blank: root / not applicable)
     * @param  array $properties Properties (may be empty, properties given are open to interpretation by the hook but generally correspond to database fields)
     * @return ~ID_TEXT The resource ID (false: error, could not create via these properties / here)
     */
    public function file_add($filename, $path, $properties)
    {
        list($properties, $label) = $this->_file_magic_filter($filename, $path, $properties, $this->file_resource_type);

        require_code('ecommerce2');

        $description = $this->_default_property_str($properties, 'description');
        $cost = $this->_default_property_int($properties, 'cost');
        $length = $this->_default_property_int($properties, 'length');
        $length_units = $this->_default_property_str($properties, 'length_units');
        $auto_recur = $this->_default_property_int($properties, 'auto_recur');
        $group_id = $this->_default_property_group($properties, 'group_id');
        $uses_primary = $this->_default_property_int($properties, 'uses_primary');
        $enabled = $this->_default_property_int($properties, 'enabled');
        $mail_start = $this->_default_property_str($properties, 'mail_start');
        $mail_end = $this->_default_property_str($properties, 'mail_end');
        $mail_uhoh = $this->_default_property_str($properties, 'mail_uhoh');
        $_mails = $this->_default_property_str($properties, 'mails');

        $id = add_usergroup_subscription($label, $description, $cost, $length, $length_units, $auto_recur, $group_id, $uses_primary, $enabled, $mail_start, $mail_end, $mail_uhoh);

        if (isset($properties['mails'])) {
            table_from_portable_rows('f_usergroup_sub_mails', $properties['mails'], array('m_usergroup_sub_id' => $id), TABLE_REPLACE_MODE_NONE);
        }

        $this->_resource_save_extend($this->file_resource_type, strval($id), $filename, $label, $properties);

        return strval($id);
    }

    /**
     * Standard Commandr-fs load function for resource-fs hooks. Finds the properties for some resource.
     *
     * @param  SHORT_TEXT $filename Filename
     * @param  string $path The path (blank: root / not applicable). It may be a wildcarded path, as the path is used for content-type identification only. Filenames are globally unique across a hook; you can calculate the path using ->search.
     * @return ~array Details of the resource (false: error)
     */
    public function file_load($filename, $path)
    {
        list($resource_type, $resource_id) = $this->file_convert_filename_to_id($filename);

        $rows = $GLOBALS['FORUM_DB']->query_select('f_usergroup_subs', array('*'), array('id' => intval($resource_id)), '', 1);
        if (!array_key_exists(0, $rows)) {
            return false;
        }
        $row = $rows[0];

        $properties = array(
            'label' => get_translated_text($row['s_title'], $GLOBALS['FORUM_DB']),
            'description' => get_translated_text($row['s_description'], $GLOBALS['FORUM_DB']),
            'cost' => $row['s_cost'],
            'length' => $row['s_length'],
            'length_units' => $row['s_length_units'],
            'group_id' => remap_resource_id_as_portable('group', $row['s_group_id']),
            'enabled' => $row['s_enabled'],
            'mail_start' => $row['s_mail_start'],
            'mail_end' => $row['s_mail_end'],
            'mail_uhoh' => $row['s_mail_uhoh'],
            'uses_primary' => $row['s_uses_primary'],
            'mails' => table_to_portable_rows('f_usergroup_sub_mails', array('id', 'm_usergroup_sub_id'), array('m_usergroup_sub_id' => intval($resource_id))),
        );
        $this->_resource_load_extend($resource_type, $resource_id, $properties, $filename, $path);
        return $properties;
    }

    /**
     * Standard Commandr-fs edit function for resource-fs hooks. Edits the resource to the given properties.
     *
     * @param  ID_TEXT $filename The filename
     * @param  string $path The path (blank: root / not applicable)
     * @param  array $properties Properties (may be empty, properties given are open to interpretation by the hook but generally correspond to database fields)
     * @return ~ID_TEXT The resource ID (false: error, could not create via these properties / here)
     */
    public function file_edit($filename, $path, $properties)
    {
        list($resource_type, $resource_id) = $this->file_convert_filename_to_id($filename);
        list($properties,) = $this->_file_magic_filter($filename, $path, $properties, $this->file_resource_type);

        require_code('ecommerce2');

        $label = $this->_default_property_str($properties, 'label');
        $description = $this->_default_property_str($properties, 'description');
        $cost = $this->_default_property_int($properties, 'cost');
        $length = $this->_default_property_int($properties, 'length');
        $length_units = $this->_default_property_str($properties, 'length_units');
        $auto_recur = $this->_default_property_int($properties, 'auto_recur');
        $group_id = $this->_default_property_group($properties, 'group_id');
        $uses_primary = $this->_default_property_int($properties, 'uses_primary');
        $enabled = $this->_default_property_int($properties, 'enabled');
        $mail_start = $this->_default_property_str($properties, 'mail_start');
        $mail_end = $this->_default_property_str($properties, 'mail_end');
        $mail_uhoh = $this->_default_property_str($properties, 'mail_uhoh');
        $_mails = $this->_default_property_str($properties, 'mails');

        edit_usergroup_subscription(intval($resource_id), $label, $description, $cost, $length, $length_units, $auto_recur, $group_id, $uses_primary, $enabled, $mail_start, $mail_end, $mail_uhoh);

        if (isset($properties['mails'])) {
            table_from_portable_rows('f_usergroup_sub_mails', $properties['mails'], array('m_usergroup_sub_id' => intval($resource_id)), TABLE_REPLACE_MODE_BY_EXTRA_FIELD_DATA);
        }

        $this->_resource_save_extend($this->file_resource_type, $resource_id, $filename, $label, $properties);

        return $resource_id;
    }

    /**
     * Standard Commandr-fs delete function for resource-fs hooks. Deletes the resource.
     *
     * @param  ID_TEXT $filename The filename
     * @param  string $path The path (blank: root / not applicable)
     * @return boolean Success status
     */
    public function file_delete($filename, $path)
    {
        list($resource_type, $resource_id) = $this->file_convert_filename_to_id($filename);

        require_code('ecommerce2');
        delete_usergroup_subscription(intval($resource_id));

        return true;
    }

    /**
     * Get the resource ID for a filename (of file). Note that filenames are unique across all folders in a filesystem.
     *
     * @param  ID_TEXT $filename The filename, or filepath
     * @param  ?ID_TEXT $resource_type The resource type (null: assumption of only one folder resource type for this hook; only passed as non-null from overridden functions within hooks that are calling this as a helper function)
     * @return ?array A pair: The resource type, the resource ID (null: could not find)
     */
    public function file_convert_filename_to_id($filename, $resource_type = null)
    {
        if (is_null($resource_type)) {
            $resource_type = $this->file_resource_type;
        }

        $filename = preg_replace('#^.*/#', '', $filename); // Paths not needed, as filenames are globally unique; paths would not be in alternative_ids table

        $label = basename($filename, '.' . RESOURCE_FS_DEFAULT_EXTENSION); // Remove file extension from filename
        $resource_id = find_id_via_label($resource_type, $label);
        return array($resource_type, $resource_id);
    }
}
