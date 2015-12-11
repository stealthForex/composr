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
 * @package    redirects_editor
 */

/**
 * Hook class.
 */
class Hook_commandr_fs_extended_config__redirects
{
    /**
     * Standard Commandr-fs date fetch function for resource-fs hooks. Defined when getting an edit date is not easy.
     *
     * @return ?TIME The edit date or add date, whichever is higher (null: could not find one)
     */
    public function get_edit_date()
    {
        $query = 'SELECT MAX(date_and_time) FROM ' . get_table_prefix() . 'adminlogs WHERE ' . db_string_equal_to('the_type', 'SET_REDIRECTS');
        return $GLOBALS['SITE_DB']->query_value_if_there($query);
    }

    /**
     * Standard Commandr-fs file reading function for commandr_fs hooks.
     *
     * @param  array $meta_dir The current meta-directory path
     * @param  string $meta_root_node The root node of the current meta-directory
     * @param  string $file_name The file name
     * @param  object $commandr_fs A reference to the Commandr filesystem object
     * @return ~string The file contents (false: failure)
     */
    public function read_file($meta_dir, $meta_root_node, $file_name, &$commandr_fs)
    {
        return table_to_json('redirects');
    }

    /**
     * Standard Commandr-fs file writing function for commandr_fs hooks.
     *
     * @param  array $meta_dir The current meta-directory path
     * @param  string $meta_root_node The root node of the current meta-directory
     * @param  string $file_name The file name
     * @param  string $contents The new file contents
     * @param  object $commandr_fs A reference to the Commandr filesystem object
     * @return boolean Success?
     */
    public function write_file($meta_dir, $meta_root_node, $file_name, $contents, &$commandr_fs)
    {
        return table_from_json('redirects', $contents, null, TABLE_REPLACE_MODE_SEVERE);
    }
}
