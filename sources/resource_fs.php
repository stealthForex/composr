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
 * @package    commandr
 */

/*
Resource-fs serves the 'var' parts of Commandr-fs. It binds Commandr-fs to a property/XML-based content model.

A programmer can also directly talk to Resource-fs to do abstracted CRUD operations on just about any kind of Composr resource.
i.e. Perform generalised operations on resource types without needing to know their individual APIs.

The user knows all of Commandr-fs as "The Composr Repository".
*/

/*
In Composr we have cms_merge and we have Resource-fs.

Resource-fs is intended for staging site functionality and backups, mainly.
cms_merge is intended to merge disparate sites in a more complete way.

There is overlap, but intentionally each approach is optimised in a different way.
*/

/**
 * Standard code module initialisation function.
 *
 * @ignore
 */
function init__resource_fs()
{
    require_code('commandr');
    require_code('database_relations');
    require_code('resource_fs_base_class');
    require_code('json');
    require_code('content');

    define('RESOURCEFS_DEFAULT_EXTENSION', 'cms');

    define('RESOURCEFS_SPECIAL_DIRECTORY_FILE', '_folder.' . RESOURCEFS_DEFAULT_EXTENSION);

    $GLOBALS['NO_QUERY_LIMIT'] = true;

    global $RESOURCEFS_LOGGER, $RESOURCEFS_LOGGER_LEVEL;
    $RESOURCEFS_LOGGER = null;
    $RESOURCEFS_LOGGER_LEVEL = 'notice';

    global $RESOURCEFS_ADD_ONLY;
    $RESOURCEFS_ADD_ONLY = false;

    define('TABLE_REPLACE_MODE_NONE', 0);
    define('TABLE_REPLACE_MODE_BY_EXTRA_FIELD_DATA', 1);
    define('TABLE_REPLACE_MODE_SEVERE', 2);
}

/**
 * Disengage logging.
 *
 * @param  string $level The minimum logging level
 * @set inform notice warn
 */
function resourcefs_logging__start($level = 'notice')
{
    global $RESOURCEFS_LOGGER, $RESOURCEFS_LOGGER_LEVEL;
    if ($RESOURCEFS_LOGGER !== null) {
        fclose($RESOURCEFS_LOGGER);
    }
    $RESOURCEFS_LOGGER = fopen(get_custom_file_base() . '/data_custom/resourcefs.log', 'at');
    $RESOURCEFS_LOGGER_LEVEL = $level;
}

/**
 * Log a message.
 *
 * @param  string $message The message
 * @param  ID_TEXT $type The template to use
 * @set    inform notice warn
 */
function resourcefs_logging($message, $type = 'warn')
{
    global $RESOURCEFS_LOGGER, $RESOURCEFS_LOGGER_LEVEL;
    if (!is_null($RESOURCEFS_LOGGER)) {
        if (($type == 'inform') && ($RESOURCEFS_LOGGER_LEVEL != 'inform')) {
            return;
        }
        if (($type == 'notice') && ($RESOURCEFS_LOGGER_LEVEL != 'inform') && ($RESOURCEFS_LOGGER_LEVEL != 'notice')) {
            return;
        }
        if (($type == 'warn') && ($RESOURCEFS_LOGGER_LEVEL != 'inform') && ($RESOURCEFS_LOGGER_LEVEL != 'notice') && ($RESOURCEFS_LOGGER_LEVEL != 'warn')) {
            return;
        }

        $message = date('d/m/Y H:i:s') . ': ' . $type . ': ' . $message . "\n";
        fwrite($RESOURCEFS_LOGGER, $message);
        if (running_script('execute_temp')) {
            print($message);
        }
    }
}

/**
 * Disengage logging.
 */
function resourcefs_logging__end()
{
    global $RESOURCEFS_LOGGER;
    if ($RESOURCEFS_LOGGER !== null) {
        fclose($RESOURCEFS_LOGGER);
    }
    $RESOURCEFS_LOGGER = null;
    sync_file(get_custom_file_base() . '/data_custom/resourcefs.log');
    fix_permissions(get_custom_file_base() . '/data_custom/resourcefs.log');
}

/**
 * Get the Commandr-fs object for a resource type.
 *
 * @param  ID_TEXT $resource_type The resource type
 * @return ?object The object (null: could not get one)
 */
function get_resource_commandrfs_object($resource_type)
{
    $object = get_content_object($resource_type);
    if (is_null($object)) {
        return null;
    }
    $info = $object->info();
    $fs_hook = $info['commandr_filesystem_hook'];
    if (is_null($fs_hook)) {
        return null;
    }

    require_code('hooks/systems/commandr_fs/' . filter_naughty_harsh($fs_hook));
    $fs_object = object_factory('Hook_commandr_fs_' . filter_naughty_harsh($fs_hook), true);
    if (is_null($fs_object)) {
        return null;
    }
    return $fs_object;
}

/*
ADDRESSING SPACE POPULATION AND LOOKUP CAN HAPPEN OUTSIDE RESOURCE-FS OBJECTS;
THIS INCLUDES FILENAME STUFF, ALTHOUGH DELEGATED INTERNALLY TO THE RESOURCE-FS OBJECT WHICH HANDLES THE ACTUAL NAMING RULES;
ACTUAL FILESYSTEM INTERACTION IS DONE VIA A RESOURCE-FS OBJECT (fetch that via the get_resource_commandrfs_object function)
*/

/**
 * Generate, and save, a resource-fs moniker.
 *
 * @param  ID_TEXT $resource_type The resource type
 * @param  ID_TEXT $resource_id The resource ID
 * @param  ?LONG_TEXT $label The (new) label (null: lookup for specified resource)
 * @return array A triple: The moniker (may be new, or the prior one if the moniker did not need to change), the GUID, the label
 * @param  ?ID_TEXT $new_guid GUID to forcibly assign (null: don't force)
 * @param  boolean $definitely_new If we know this is new, i.e. has no existing moniker
 */
function generate_resourcefs_moniker($resource_type, $resource_id, $label = null, $new_guid = null, $definitely_new = false)
{
    if (!is_null($label)) {
        $label = substr($label, 0, 255);
    }

    static $cache = array();
    if (is_null($new_guid)) {
        if (isset($cache[$resource_type][$resource_id])) {
            return $cache[$resource_type][$resource_id];
        }
    }

    $resource_object = get_content_object($resource_type);
    if (is_null($resource_type)) {
        fatal_exit('Cannot load content object for ' . $resource_type);
    }
    $resource_info = $resource_object->info();
    $resourcefs_hook = $resource_info['commandr_filesystem_hook'];

    if (is_null($label)) {
        list($label) = content_get_details($resource_type, $resource_id, true);
        if (is_null($label)) {
            return array(null, null);
        }
    }

    $lookup = $definitely_new ? array() : $GLOBALS['SITE_DB']->query_select('alternative_ids', array('resource_moniker', 'resource_guid', 'resource_label'), array('resource_type' => $resource_type, 'resource_id' => $resource_id), '', 1);
    if (array_key_exists(0, $lookup)) {
        $no_exists_check_for = $lookup[0]['resource_moniker'];
        $guid = is_null($new_guid) ? $lookup[0]['resource_guid'] : $new_guid;

        if ((is_null($new_guid)) && ($lookup[0]['resource_label'] == $label)) {
            $ret = array($no_exists_check_for, $guid, $lookup[0]['resource_label']);
            $cache[$resource_type][$resource_id] = $ret;
            return $ret;
        }
    } else {
        $no_exists_check_for = mixed();
        require_code('global4');
        $guid = is_null($new_guid) ? generate_guid() : $new_guid;
    }

    require_code('urls2');
    $moniker = _generate_moniker($label);

    // Check it does not already exist
    $moniker_origin = $moniker;
    $next_num = 1;
    if (is_numeric($moniker)) {
        $moniker .= '_1';
    }
    $test = mixed();
    do {
        if (!is_null($no_exists_check_for)) {
            if ($moniker == $no_exists_check_for) { // This one is okay, we know it is safe, and no need to change it
                break;
            }
        }

        $where = array('resource_resourcefs_hook' => $resourcefs_hook, 'resource_moniker' => $moniker);
        $test = $GLOBALS['SITE_DB']->query_select_value_if_there('alternative_ids', 'resource_id', $where);
        $ok = (is_null($test)) && ($moniker != '_folder'/*reserved*/);
        if (!$ok) { // Oh dear, will pass to next iteration, but trying a new moniker
            $next_num++;
            $moniker = $moniker_origin . '_' . strval($next_num);
        }
    } while (!$ok);

    if (($moniker !== $no_exists_check_for) || (!is_null($new_guid))) {
        $GLOBALS['SITE_DB']->query_delete('alternative_ids', array('resource_type' => $resource_type, 'resource_id' => $resource_id), '', 1);

        $GLOBALS['SITE_DB']->query_insert('alternative_ids', array(
            'resource_type' => $resource_type,
            'resource_id' => $resource_id,
            'resource_moniker' => $moniker,
            'resource_label' => $label,
            'resource_guid' => $guid,
            'resource_resourcefs_hook' => $resourcefs_hook,
        ));
    }

    $ret = array($moniker, $guid, $label);
    $cache[$resource_type][$resource_id] = $ret;
    return $ret;
}

/**
 * Generate, and save, a resource-fs moniker.
 *
 * @param  ID_TEXT $resource_type The resource type
 * @param  ID_TEXT $resource_id The resource ID
 */
function expunge_resourcefs_moniker($resource_type, $resource_id)
{
    $GLOBALS['SITE_DB']->query_delete('alternative_ids', array('resource_type' => $resource_type, 'resource_id' => $resource_id), '', 1);
}

/**
 * Find the resource GUID from the resource ID.
 *
 * @param  ID_TEXT $resource_type The resource type
 * @param  ID_TEXT $resource_id The resource ID
 * @return ?ID_TEXT The GUID (null: no match)
 */
function find_guid_via_id($resource_type, $resource_id)
{
    list(, $guid) = generate_resourcefs_moniker($resource_type, $resource_id);
    return $guid;
}

/**
 * Find the Commandr-fs (repository) filename from the resource ID.
 *
 * @param  ID_TEXT $resource_type The resource type
 * @param  ID_TEXT $resource_id The resource ID
 * @param  boolean $include_subpath Whether to include the subpath
 * @return ?ID_TEXT The filename (null: no match)
 */
function find_commandrfs_filename_via_id($resource_type, $resource_id, $include_subpath = false)
{
    $resourcefs_ob = get_resource_commandrfs_object($resource_type);
    $filename = $resourcefs_ob->convert_id_to_filename($resource_type, $resource_id);
    if (!is_null($filename)) {
        if ($include_subpath) {
            $subpath = $resourcefs_ob->search($resource_type, $resource_id, true);
            if (is_null($subpath)) {
                return null;
            }
            if ($subpath != '') {
                $filename = $subpath . '/' . $filename;
            }
        }
    }
    return $filename;
}

/**
 * Find the resource moniker from the resource ID.
 *
 * @param  ID_TEXT $resource_type The resource type
 * @param  ID_TEXT $resource_id The resource ID
 * @return ?ID_TEXT The moniker (null: no match)
 */
function find_moniker_via_id($resource_type, $resource_id)
{
    list($moniker) = generate_resourcefs_moniker($resource_type, $resource_id);
    return $moniker;
}

/**
 * Find the resource label from the resource ID.
 *
 * @param  ID_TEXT $resource_type The resource type
 * @param  ID_TEXT $resource_id The resource ID
 * @return ?SHORT_TEXT The label (null: no match)
 */
function find_label_via_id($resource_type, $resource_id)
{
    list(, , $label) = generate_resourcefs_moniker($resource_type, $resource_id);
    return $label;
}

/**
 * Find the resource ID from the resource moniker.
 *
 * @param  ID_TEXT $resource_type The resource type
 * @param  ID_TEXT $resource_moniker The moniker
 * @return ?ID_TEXT The ID (null: no match)
 */
function find_id_via_moniker($resource_type, $resource_moniker)
{
    static $cache = array();
    if (isset($cache[$resource_type][$resource_moniker])) {
        return $cache[$resource_type][$resource_moniker];
    }

    $where = array(
        'resource_type' => $resource_type,
        'resource_moniker' => $resource_moniker,
    );
    $ret = $GLOBALS['SITE_DB']->query_select_value_if_there('alternative_ids', 'resource_id', $where);

    $cache[$resource_type][$resource_moniker] = $ret;
    return $ret;
}

/**
 * Find the resource ID from the resource label.
 *
 * @param  ID_TEXT $resource_type The resource type
 * @param  LONG_TEXT $_resource_label The label
 * @param  ?string $subpath The subpath (null: don't care). It may end in "/*" if you want to look for a match under a certain directory
 * @return ?ID_TEXT The ID (null: no match)
 */
function find_id_via_label($resource_type, $_resource_label, $subpath = null)
{
    $resource_label = substr($_resource_label, 0, 255);

    static $cache = array();
    if (isset($cache[$resource_type][$resource_label][$subpath])) {
        return $cache[$resource_type][$resource_label][$subpath];
    }

    $commandrfs_ob = get_resource_commandrfs_object($resource_type);
    if (is_null($commandrfs_ob)) {
        fatal_exit('Cannot load resource-fs object for ' . $resource_type);
    }

    $ids = $GLOBALS['SITE_DB']->query_select('alternative_ids', array('resource_id'), array(
        'resource_type' => $resource_type,
        'resource_label' => $resource_label,
    ));
    $resource_ids = collapse_1d_complexity('resource_id', $ids);
    foreach ($resource_ids as $resource_id) {
        if (_check_id_match($commandrfs_ob, $resource_type, $resource_id, $subpath)) {
            $cache[$resource_type][$resource_label][$subpath] = $resource_id;
            return $resource_id;
        }
    }

    // No valid match, do a direct DB search without the benefit of the alternative_ids table
    $ids = $commandrfs_ob->find_resource_by_label($resource_type, $_resource_label);
    foreach ($ids as $resource_id) {
        if (_check_id_match($commandrfs_ob, $resource_type, $resource_id, $subpath)) {
            $cache[$resource_type][$resource_label][$subpath] = $resource_id;
            return $resource_id;
        }
    }

    // Still no valid match
    return null;
}

/**
 * Find if a resource matches search parameters.
 *
 * @param  object $commandrfs_ob Commandr-fs/Resource-fs object
 * @param  ID_TEXT $resource_type The resource type
 * @param  ID_TEXT $resource_id The resource ID
 * @param  ?string $subpath The subpath (null: don't care). It may end in "/*" if you want to look for a match under a certain directory
 * @return boolean Whether it matches
 * @ignore
 */
function _check_id_match($commandrfs_ob, $resource_type, $resource_id, $subpath)
{
    if ($subpath === null) {
        return true;
    } else {
        $this_subpath = $commandrfs_ob->search($resource_type, $resource_id, true);
        if (substr($subpath, -2) == '/*') {
            if (substr($this_subpath . '/', 0, strlen($subpath) - 1) == substr($subpath, 0, strlen($subpath) - 1)) {
                return true;
            }
        } else {
            if ($this_subpath == $subpath) {
                return true;
            }
        }
    }
    return false;
}

/**
 * Find the resource ID from the resource GUID. It is assumed you as the programmer already know the resource-type.
 *
 * @param  ID_TEXT $resource_guid The GUID
 * @return ?ID_TEXT The ID (null: no match)
 */
function find_id_via_guid($resource_guid)
{
    static $cache = array();
    if (isset($cache[$resource_guid])) {
        return $cache[$resource_guid];
    }

    $ret = $GLOBALS['SITE_DB']->query_select_value_if_there('alternative_ids', 'resource_id', array(
        'resource_guid' => $resource_guid,
    ));
    $cache[$resource_guid] = $ret;
    return $ret;
}

/**
 * Find the resource IDs from the resource GUIDs. This is useful if you need to resolve many GUIDs at once during performant-critical code.
 *
 * @param  array $guids The GUIDs
 * @return array Mapping between GUIDs and IDs (anything where there's no match will result in no array entry being present for that GUID)
 */
function find_ids_via_guids($guids)
{
    $or_list = '';
    foreach ($guids as $guid) {
        if ($or_list != '') {
            $or_list .= ' OR ';
        }
        $or_list .= db_string_equal_to('resource_guid', $guid);
    }
    $query = 'SELECT resource_id,resource_guid FROM ' . get_table_prefix() . 'alternative_ids WHERE ' . $or_list;
    $ret = $GLOBALS['SITE_DB']->query($query, null, null, false, true);
    return collapse_2d_complexity('resource_id', 'resource_guid', $ret);
}

/**
 * Find the resource ID from the Commandr-fs (repository) filename.
 *
 * @param  ID_TEXT $resource_type The resource type
 * @param  ID_TEXT $filename The filename
 * @return ?ID_TEXT The ID (null: no match)
 */
function find_id_via_commandrfs_filename($resource_type, $filename)
{
    $resourcefs_ob = get_resource_commandrfs_object($resource_type);
    $test = $resourcefs_ob->convert_filename_to_id($filename, $resource_type);
    if (is_null($test)) {
        return null;
    }
    list(, $resource_id) = $test;
    return $resource_id;
}

/*
TABLE LEVEL
*/

/**
 * Transfer a table's contents to JSON format.
 *
 * @param  string $table Table name
 * @param  ?array $fields_to_skip Fields to not include in the table dump (null: none). Any keys from $where_map will also be skipped, as these are obviously constant for all rows returned.
 * @param  ?array $where_map Extra WHERE constraints (null: none)
 * @return string JSON data
 */
function table_to_json($table, $fields_to_skip = null, $where_map = null)
{
    return json_encode(table_to_portable_rows($table, $fields_to_skip, $where_map));
}

/**
 * Transfer a table's contents to portable rows.
 *
 * @param  string $table Table name
 * @param  ?array $fields_to_skip Fields to not include in the table dump (null: none). Any keys from $where_map will also be skipped, as these are obviously constant for all rows returned.
 * @param  ?array $where_map Extra WHERE constraints (null: none)
 * @param  ?object $connection Database connection to look up from (null: work out from table name)
 * @return array Portable rows
 */
function table_to_portable_rows($table, $fields_to_skip = null, $where_map = null, $connection = null)
{
    if (is_null($where_map)) {
        $where_map = array();
    }

    if (is_null($connection)) {
        $connection = (substr($table, 0, 2) == 'f_' && get_forum_type() == 'cns') ? $GLOBALS['FORUM_DB'] : $GLOBALS['SITE_DB'];
    }

    $db_fields = collapse_2d_complexity('m_name', 'm_type', $connection->query_select('db_meta', array('m_name', 'm_type'), array('m_table' => $table)));

    $rows = $connection->query_select($table, array('*'), $where_map);

    $relation_map = get_relation_map_for_table($table);

    if (is_null($fields_to_skip)) {
        $fields_to_skip = array();
    }
    $fields_to_skip = array_merge($fields_to_skip, array_keys($where_map));

    foreach ($rows as &$row) {
        foreach ($fields_to_skip as $field_to_skip) {
            unset($row[$field_to_skip]);
        }

        $row = table_row_to_portable_row($row, $db_fields, $relation_map, $connection);
    }

    return $rows;
}

/**
 * Transfer JSON format to a table.
 *
 * @param  string $table Table name
 * @param  mixed $json JSON data OR rows that are already decoded
 * @param  ?array $extra_field_data Extra data to add to each row (null: none)
 * @param  boolean $replace_mode Whether to fully replace the current table contents
 * @return boolean Success status
 */
function table_from_json($table, $json, $extra_field_data, $replace_mode)
{
    $rows = @json_decode($json, true);

    return table_from_portable_rows($table, $json, $extra_field_data, $replace_mode);
}

/**
 * Transfer portable rows to a table.
 *
 * @param  string $table Table name
 * @param  array $rows Portable rows
 * @param  ?array $extra_field_data Extra data to add to each row (null: none)
 * @param  boolean $replace_mode Whether to fully replace the current table contents
 * @param  ?object $connection Database connection to look up from (null: work out from table name)
 * @return boolean Success status
 */
function table_from_portable_rows($table, $rows, $extra_field_data, $replace_mode, $connection = null)
{
    if (is_null($connection)) {
        $connection = (substr($table, 0, 2) == 'f_' && get_forum_type() == 'cns') ? $GLOBALS['FORUM_DB'] : $GLOBALS['SITE_DB'];
    }

    $db_fields = collapse_2d_complexity('m_name', 'm_type', $connection->query_select('db_meta', array('m_name', 'm_type'), array('m_table' => $table)));

    $lang_fields = array();
    $upload_fields = array();
    foreach ($db_fields as $db_field_name => $db_field_type) {
        $db_field_type = trim($db_field_type, '*?');

        if (strpos($db_field_type, '_TRANS') !== false) {
            $lang_fields[] = $db_field_name;
        }
        elseif ($db_field_type == 'URLPATH') {
            $upload_fields[] = $db_field_name;
        }
    }

    if ($replace_mode) {
        if (count($lang_fields) != 0 || count($upload_fields) != 0) {
            $old_rows = $connection->query_select($table, array_merge($lang_fields, $upload_fields));

            foreach ($old_rows as $old_row) {
                // Cleanup old language fields
                foreach ($lang_fields as $lang_field) {
                    delete_lang($old_row[$lang_field], $connection);
                }

                // Cleanup old files
                foreach ($upload_fields as $upload_field) {
                    @unlink(get_custom_file_base() . '/' . $old_row[$upload_field]);
                    sync_file(get_custom_file_base() . '/' . $old_row[$upload_field]);
                }
            }
        }

        // Delete old rows
        $connection->query_delete($table);
    } else {
        // For a poor-mans REPLACE INTO (which is a MySQL extension)
        $keys = array();
        foreach ($db_fields as $db_field) {
            if (substr($db_field['m_type'], 0, 1) == '*') {
                $keys[$db_field['m_name']] = true;
            }
        }
    }

    if ($rows === false) {
        return false;
    }

    $relation_map = get_relation_map_for_table($table);

    foreach ($rows as $row) {
        if (!is_null($extra_field_data)) {
            $row += $extra_field_data;
        }

        $row = table_row_from_portable_row($row, $db_fields, $relation_map, $connection);

        if ($replace_mode == TABLE_REPLACE_MODE_NONE) {
            if (count($lang_fields) != 0 || count($upload_fields) != 0) {
                $old_rows = $connection->query_select($table, array_merge($lang_fields, $upload_fields), array_intersect_key($row, $keys));

                foreach ($old_rows as $old_row) {
                    // Cleanup old language fields
                    foreach ($lang_fields as $lang_field) {
                        delete_lang($old_row[$lang_field], $connection);
                    }

                    // Cleanup old files
                    foreach ($upload_fields as $upload_field) {
                        @unlink(get_custom_file_base() . '/' . $old_row[$upload_field]);
                        sync_file(get_custom_file_base() . '/' . $old_row[$upload_field]);
                    }
                }
            }

            // Delete old row with same key
            $connection->query_delete($table, array_intersect_key($row, $keys));
        }

        $connection->query_insert($table, $row);
    }

    return true;
}

/*
ROW LEVEL
*/

/**
 * Make a table row a portable row.
 *
 * @param  array $row Table row
 * @param  array $db_fields A map of DB-style schema data for the fields we have in $row; helps us build portability
 * @param  array $relation_map Relation map
 * @param  ?object $connection Database connection to look up from (null: main site DB)
 * @return array Portable row
 */
function table_row_to_portable_row($row, $db_fields, $relation_map, $connection = null)
{
    if (is_null($connection)) {
        $connection = $GLOBALS['SITE_DB'];
    }

    foreach ($db_fields as $db_field_name => $db_field_type) {
        if (!isset($row[$db_field_name])) {
            continue;
        }

        $db_field_type = trim($db_field_type, '*?');

        if (strpos($db_field_type, '_TRANS') !== false) {
            $row[$db_field_name] = remap_trans_as_portable($row, $db_field_name, $connection);
        }

        elseif ($db_field_type == 'MEMBER') {
            $row[$db_field_name] = remap_resource_id_as_portable('member', $row[$db_field_name]);
        }

        elseif ($db_field_type == 'GROUP') {
            $row[$db_field_name] = remap_resource_id_as_portable('group', $row[$db_field_name]);
        }

        elseif ($db_field_type == 'TIME') {
            $row[$db_field_name] = remap_time_as_portable($row[$db_field_name]);
        }

        elseif ($db_field_type == 'URLPATH') {
            $row[$db_field_name] = remap_urlpath_as_portable($row[$db_field_name]);
        }

        elseif (isset($relation_map[$db_field_name])) {
            $row[$db_field_name] = remap_foreign_key_as_portable($relation_map[$db_field_name], $row[$db_field_name]);
        }
    }

    return $row;
}

/**
 * Make a portable row a table row.
 *
 * @param  array $row Portable row
 * @param  array $db_fields A map of DB-style schema data for the fields we have in $row; helps us build portability
 * @param  array $relation_map Relation map
 * @param  ?object $connection Database connection to look up from (null: main site DB)
 * @return array Table row
 */
function table_row_from_portable_row($row, $db_fields, $relation_map, $connection = null)
{
    if (is_null($connection)) {
        $connection = $GLOBALS['SITE_DB'];
    }

    foreach ($db_fields as $db_field_name => $db_field_type) {
        if (!isset($row[$db_field_name])) {
            continue;
        }

        $db_field_type = trim($db_field_type, '*?');

        if (strpos($db_field_type, '_TRANS') !== false) {
            $row += remap_portable_as_trans($row[$db_field_name], $db_field_name, $connection);
        }

        elseif ($db_field_type == 'MEMBER') {
            $row[$db_field_name] = remap_portable_as_resource_id('member', $row[$db_field_name]);
        }

        elseif ($db_field_type == 'GROUP') {
            $row[$db_field_name] = remap_portable_as_resource_id('group', $row[$db_field_name]);
        }

        elseif ($db_field_type == 'TIME') {
            $row[$db_field_name] = remap_portable_as_time($row[$db_field_name]);
        }

        elseif ($db_field_type == 'URLPATH') {
            $row[$db_field_name] = remap_portable_as_urlpath($row[$db_field_name]);
        }

        elseif (isset($relation_map[$db_field_name])) {
            $row[$db_field_name] = remap_portable_as_foreign_key($relation_map[$db_field_name], $row[$db_field_name]);
        }
    }

    return $row;
}

/*
FIELD LEVEL
*/

/**
 * Convert a timestamp to something portable (well, actually just make it nicer).
 *
 * @param  ?TIME $timestamp The timestamp (null: not set)
 * @return ?string Portable details (null: not set)
 */
function remap_time_as_portable($timestamp)
{
    if (is_null($timestamp)) {
        return null;
    }

    return date('Y-m-d H:i:s', $timestamp);
}

/**
 * Convert a portable timestamp to a real timestamp.
 *
 * @param  ?string $portable_data Portable details (null: not set)
 * @return ?integer The timestamp (null: not set)
 */
function remap_portable_as_time($portable_data)
{
    if (is_null($portable_data)) {
        return null;
    }

    return strtotime($portable_data);
}

/**
 * Convert a URL (external or internal) to something portable.
 *
 * @param  ?URLPATH $urlpath The URL (null: not set)
 * @return ?mixed Portable details (null: not set)
 */
function remap_urlpath_as_portable($urlpath)
{
    if (is_null($urlpath)) {
        return null;
    }

    if ($urlpath == '' || strpos($urlpath, ':') !== false) {
        return $urlpath;
    }

    $place = get_custom_file_base() . '/' . $urlpath;
    if (!file_exists($place)) {
        return $urlpath;
    }

    return array($urlpath, base64_encode(file_get_contents($place)));
}

/**
 * Convert a portable URL to a real URL.
 *
 * @param  ?string $portable_data Portable details (null: not set)
 * @param  boolean $ignore_conflicts Whether to ignore conflicts with existing files (=edit op, basically)
 * @return ?string The URL (null: not set)
 */
function remap_portable_as_urlpath($portable_data, $ignore_conflicts = false)
{
    if (!is_array($portable_data)) {
        return $portable_data;
    }

    $binary = base64_decode($portable_data[1]);

    $urlpath = $portable_data[0];

    $place = get_custom_file_base() . '/' . $urlpath;

    if ($ignore_conflicts) {
        // Hunt with sensible names until we don't get a conflict
        $i = 2;
        while (file_exists($place)) {
            $filename = strval($i) . preg_replace('#\..*\.#', '.', basename(urldecode($urlpath)));
            $place = get_custom_file_base() . '/' . dirname(urldecode($urlpath)) . '/' . $filename;
            $urlpath = dirname($urlpath) . '/' . urlencode($filename);
            $i++;
        }
    }

    file_put_contents($place, $binary);
    fix_permissions($place);
    sync_file($place);

    return $urlpath;
}

/**
 * Convert a foreign key to something portable.
 *
 * @param  array $_table_referenced The table the key is to
 * @param  ?mixed $id The key (null: not set)
 * @return ?array Portable ID details (null: not set)
 */
function remap_foreign_key_as_portable($_table_referenced, $id)
{
    if (is_null($id)) {
        return null;
    }

    list($table_referenced, $field_referenced/*not actually used, we assume it's the primary key*/) = $_table_referenced;

    $commandr_filesystem_hook = convert_composr_type_codes('table', $table_referenced, 'commandr_filesystem_hook');

    if (is_null($commandr_filesystem_hook)) {
        return $id; // No special Resource-fs to tie to, so we'll leave it alone
    }

    $resource_type = convert_composr_type_codes('table', $table_referenced, 'content_type');

    return remap_resource_id_as_portable($resource_type, $id);
}

/**
 * Convert a portable foreign key to a real foreign key.
 *
 * @param  array $_table_referenced The table the key is to
 * @param  ?mixed $portable_data Portable ID details (null: not set)
 * @return ?mixed The key (null: not set)
 */
function remap_portable_as_foreign_key($_table_referenced, $portable_data)
{
    if (!is_array($portable_data)) {
        return $portable_data;
    }

    list($table_referenced, $field_referenced/*not actually used, we assume it's the primary key*/) = $_table_referenced;

    $resource_type = convert_composr_type_codes('table', $table_referenced, 'content_type');

    return remap_portable_as_resource_id($resource_type, $portable_data);
}

/**
 * Convert a local ID to something portable.
 *
 * @param  ID_TEXT $resource_type The resource type
 * @param  ?mixed $resource_id The resource ID (null: not set)
 * @return ?array Portable ID details (null: not set)
 */
function remap_resource_id_as_portable($resource_type, $resource_id)
{
    if (is_null($resource_id)) {
        return null;
    }

    if (is_integer($resource_id)) {
        $resource_id = strval($resource_id);
    }

    list($moniker, $guid, $label) = generate_resourcefs_moniker($resource_type, $resource_id);

    $resourcefs_ob = get_resource_commandrfs_object($resource_type);
    $subpath = $resourcefs_ob->search($resource_type, $resource_id, true);
    if (is_null($subpath)) {
        $subpath = '';
    }

    return array(
        'guid' => $guid,
        'label' => $label,
        'subpath' => $subpath,
        //'moniker'=>$moniker,   Given more effectively with label
        'id' => $resource_id // Not used, but useful to have anyway for debugging/manual-reflection
    );
}

/**
 * Convert a portable ID to something local.
 *
 * @param  ID_TEXT $resource_type The resource type
 * @param  ?mixed $portable_data Portable ID details (null: not set)
 * @return ?mixed The resource ID (null: not set)
 */
function remap_portable_as_resource_id($resource_type, $portable_data)
{
    if (!is_array($portable_data)) {
        return $portable_data;
    }

    //$resource_id = $portable_data['id']; Would not be portable between sites

    // Ideally, find via GUID
    $resource_id = array_key_exists('guid', $portable_data) ? find_id_via_guid($portable_data['guid']) : null;
    if (!is_null($resource_id)) {
        return $resource_id;
    }

    // Otherwise, use the label
    $resourcefs_ob = get_resource_commandrfs_object($resource_type);
    $subpath = array_key_exists('subpath', $portable_data) ? $portable_data['subpath'] : '';
    $resource_id = $resourcefs_ob->convert_label_to_id($portable_data['label'], $subpath, $resource_type, false, array_key_exists('guid', $portable_data) ? $portable_data['guid'] : null);

    return $resource_id;
}

/**
 * Find all translated strings for a language string ID. This is used as an intermediate step in creating multi-language portings.
 *
 * @param  array $db_row Database row
 * @param  string $field Database field
 * @param  object $connection Database connection to look up from
 * @return array Portable data
 */
function remap_trans_as_portable($db_row, $field, $connection)
{
    if (!multi_lang_content()) {
        if (isset($db_row[$field . '__source_user'])) {
            return array($db_row[$field], $db_row[$field . '__source_user']);
        } else {
            return $db_row[$field];
        }
    }

    return table_to_portable_rows('translate', array('id', 'text_parsed'), array('id' => $db_row[$field]), $connection);
}

/**
 * Find all translated strings for a language string ID. This is used as an intermediate step in creating multi-language portings.
 *
 * @param  array $portable_data Portable data
 * @param  string $field Database field
 * @param  object $connection Database connection to look up from
 * @return array Extra database row data
 */
function remap_portable_as_trans($portable_data, $field, $connection)
{
    if (!multi_lang_content()) {
        if (is_array($portable_data)) {
            return array($field => $portable_data[0], $field . '__source_user' => $portable_data[1]);
        } else {
            return array($field => $portable_data);
        }
    }

    $connection->query('LOCK TABLES ' . get_table_prefix() . 'translate', null, null, true);
    $id = $connection->query_select_value('translate', 'MAX(id)');
    $id = ($id === null) ? null : ($id + 1);

    table_from_portable_rows('translate', $portable_data, array('id' => $id, 'text_parsed' => ''), TABLE_REPLACE_MODE_NONE, $connection);

    $connection->query('UNLOCK TABLES', null, null, true);

    return array($field => $id);
}
