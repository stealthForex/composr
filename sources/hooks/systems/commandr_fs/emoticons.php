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

require_code('resource_fs');

/**
 * Hook class.
 */
class Hook_commandr_fs_emoticons extends Resource_fs_base
{
    public $file_resource_type = 'emoticon';

    /**
     * Standard commandr_fs function for seeing how many resources are. Useful for determining whether to do a full rebuild.
     *
     * @param  ID_TEXT $resource_type The resource type
     * @return integer How many resources there are
     */
    public function get_resources_count($resource_type)
    {
        return $GLOBALS['FORUM_DB']->query_select_value('f_emoticons', 'COUNT(*)');
    }

    /**
     * Standard commandr_fs function for searching for a resource by label.
     *
     * @param  ID_TEXT $resource_type The resource type
     * @param  LONG_TEXT $label The resource label
     * @return array A list of resource IDs
     */
    public function find_resource_by_label($resource_type, $label)
    {
        $_ret = $GLOBALS['FORUM_DB']->query_select('f_emoticons', array('e_code'), array('e_code' => $label));
        $ret = array();
        foreach ($_ret as $r) {
            $ret[] = $r['e_code'];
        }
        return $ret;
    }

    /**
     * Standard commandr_fs introspection function.
     *
     * @return array The properties available for the resource type
     */
    protected function _enumerate_file_properties()
    {
        return array(
            'theme_img_code' => 'SHORT_TEXT',
            'relevance_level' => 'INTEGER',
            'use_topics' => 'BINARY',
            'is_special' => 'BINARY',
        );
    }

    /**
     * Standard commandr_fs add function for resource-fs hooks. Adds some resource with the given label and properties.
     *
     * @param  LONG_TEXT $filename Filename OR Resource label
     * @param  string $path The path (blank: root / not applicable)
     * @param  array $properties Properties (may be empty, properties given are open to interpretation by the hook but generally correspond to database fields)
     * @return ~ID_TEXT The resource ID (false: error, could not create via these properties / here)
     */
    public function file_add($filename, $path, $properties)
    {
        list($properties, $label) = $this->_file_magic_filter($filename, $path, $properties);

        require_code('cns_general_action');

        $theme_img_code = $this->_default_property_str($properties, 'theme_img_code');
        $relevance_level = $this->_default_property_int($properties, 'relevance_level');
        $use_topics = $this->_default_property_int($properties, 'use_topics');
        $is_special = $this->_default_property_int($properties, 'is_special');

        cns_make_emoticon($label, $theme_img_code, $relevance_level, $use_topics, $is_special);
        return $label;
    }

    /**
     * Standard commandr_fs load function for resource-fs hooks. Finds the properties for some resource.
     *
     * @param  SHORT_TEXT $filename Filename
     * @param  string $path The path (blank: root / not applicable). It may be a wildcarded path, as the path is used for content-type identification only. Filenames are globally unique across a hook; you can calculate the path using ->search.
     * @return ~array Details of the resource (false: error)
     */
    public function file_load($filename, $path)
    {
        list($resource_type, $resource_id) = $this->file_convert_filename_to_id($filename);

        $rows = $GLOBALS['FORUM_DB']->query_select('f_emoticons', array('*'), array('e_code' => $resource_id), '', 1);
        if (!array_key_exists(0, $rows)) {
            return false;
        }
        $row = $rows[0];

        return array(
            'label' => $row['e_code'],
            'theme_img_code' => $row['e_theme_img_code'],
            'relevance_level' => $row['e_relevance_level'],
            'use_topics' => $row['e_use_topics'],
            'is_special' => $row['e_is_special'],
        );
    }

    /**
     * Standard commandr_fs edit function for resource-fs hooks. Edits the resource to the given properties.
     *
     * @param  ID_TEXT $filename The filename
     * @param  string $path The path (blank: root / not applicable)
     * @param  array $properties Properties (may be empty, properties given are open to interpretation by the hook but generally correspond to database fields)
     * @return ~ID_TEXT The resource ID (false: error, could not create via these properties / here)
     */
    public function file_edit($filename, $path, $properties)
    {
        list($resource_type, $resource_id) = $this->file_convert_filename_to_id($filename);
        list($properties,) = $this->_file_magic_filter($filename, $path, $properties);

        require_code('cns_general_action2');

        $label = $this->_default_property_str($properties, 'label');
        $theme_img_code = $this->_default_property_str($properties, 'theme_img_code');
        $relevance_level = $this->_default_property_int($properties, 'relevance_level');
        $use_topics = $this->_default_property_int($properties, 'use_topics');
        $is_special = $this->_default_property_int($properties, 'is_special');

        cns_edit_emoticon($resource_id, $label, $theme_img_code, $relevance_level, $use_topics, $is_special);

        return $resource_id;
    }

    /**
     * Standard commandr_fs delete function for resource-fs hooks. Deletes the resource.
     *
     * @param  ID_TEXT $filename The filename
     * @param  string $path The path (blank: root / not applicable)
     * @return boolean Success status
     */
    public function file_delete($filename, $path)
    {
        list($resource_type, $resource_id) = $this->file_convert_filename_to_id($filename);

        require_code('cns_general_action2');
        cns_delete_emoticon($resource_id);

        return true;
    }
}